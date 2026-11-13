<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Anti-robots des formulaires publics, sans session ni service tiers :
 * pot de miel (champ invisible), délai minimal signé HMAC (un robot envoie en moins de 3 s), limite de débit par usage et par IP.
 * Un robot reçoit la même réponse qu'un humain, sans effet. Une page restée ouverte trop longtemps (signature valide,
 * délai maximal dépassé) est signalée et la saisie rendue (RGAA 13.1, WCAG 2.2.1) au lieu d'être perdue en silence.
 */
final class FormGuard
{
    public const FORM_FIELD = '_form';
    public const TIME_FIELD = '_ts';

    /** Clés jamais gardées en session après une erreur. */
    private const SECRET_KEYS = '/^(code|otp|totp)$|pass|mot_de_passe|motdepasse|secret|token|jeton|csrf|iban|cvc|card_number|numero_carte/i';

    public static function honeypot(): string
    {
        return (string) config('forms.honeypot', 'site_web');
    }

    /** Champs cachés à placer dans le formulaire. $form : identifiant unique du formulaire ('contact'). */
    public static function fields(string $form): string
    {
        $ts = (string) time();
        $name = self::honeypot();
        $id = $name . '-' . $form;
        return '<input type="hidden" name="' . self::FORM_FIELD . '" value="' . e($form) . '">'
            . '<input type="hidden" name="' . self::TIME_FIELD . '" value="' . $ts . '.' . self::sign($form, $ts) . '">'
            . '<div class="hp" aria-hidden="true"><label for="' . e($id) . '">Ne pas remplir ce champ</label>'
            . '<input id="' . e($id) . '" name="' . e($name) . '" type="text" value="" tabindex="-1" autocomplete="off"></div>';
    }

    /** Vrai si l'envoi semble humain : pot de miel vide, signature valide, délai entre le minimum et le maximum. */
    public static function passes(): bool
    {
        $age = self::age();
        return $age !== null && $age >= (int) config('forms.min_seconds', 3) && $age <= (int) config('forms.max_age', 7200);
    }

    /** Vrai pour un envoi manifestement automatique (pot de miel rempli, signature absente ou fausse). */
    public static function isBot(): bool
    {
        return self::age() === null;
    }

    /** Page ouverte trop longtemps avant l'envoi : à signaler, saisie rendue. */
    public static function expired(): bool
    {
        $age = self::age();
        return $age !== null && $age > (int) config('forms.max_age', 7200);
    }

    /** Âge du formulaire en secondes, null si robot ou signature invalide. */
    private static function age(): ?int
    {
        if ((string) ($_POST[self::honeypot()] ?? '') !== '') {
            return null;
        }
        $form = (string) ($_POST[self::FORM_FIELD] ?? '');
        [$ts, $sig] = array_pad(explode('.', (string) ($_POST[self::TIME_FIELD] ?? ''), 2), 2, '');
        if ($form === '' || !ctype_digit($ts) || strlen($ts) > 12 || !hash_equals(self::sign($form, $ts), $sig)) {
            return null;
        }
        return time() - (int) $ts;
    }

    /**
     * Fenêtre glissante : au plus $max passages en $minutes pour cet usage et cette IP. Chaque appel compte un passage.
     * Dates calculées en PHP : portable SQLite et MySQL.
     */
    public static function rateLimited(string $bucket, int $max, int $minutes): bool
    {
        $client = self::client();
        if (random_int(1, 50) === 1) {
            self::purge();
        }
        $count = (int) Db::run(
            'SELECT COUNT(*) FROM rate_limits WHERE bucket = ? AND ip = ? AND created_at >= ?',
            [$bucket, $client, date('Y-m-d H:i:s', time() - $minutes * 60)]
        )->fetchColumn();
        if ($count >= $max) {
            return true;
        }
        Db::run('INSERT INTO rate_limits (bucket, ip, created_at) VALUES (?, ?, ?)', [$bucket, $client, now()]);
        return false;
    }

    /** Efface le compteur de cet usage pour cette IP (après une connexion réussie, par exemple). */
    public static function clear(string $bucket): void
    {
        Db::run('DELETE FROM rate_limits WHERE bucket = ? AND ip = ?', [$bucket, self::client()]);
    }

    /** Traces de plus d'un jour (appelé au hasard par rateLimited et par la tâche planifiée). @return int lignes supprimées */
    public static function purge(): int
    {
        return Db::run('DELETE FROM rate_limits WHERE created_at < ?', [date('Y-m-d H:i:s', time() - 86400)])->rowCount();
    }

    /**
     * Saisie à rendre au formulaire : chaînes seulement, sans champs techniques ni secrets (mot de passe, code, carte).
     * @param array<string, mixed> $input
     * @return array<string, string|list<string>>
     */
    public static function keepable(array $input): array
    {
        $out = [];
        foreach ($input as $key => $value) {
            $key = (string) $key;
            if (str_starts_with($key, '_') || $key === self::honeypot() || preg_match(self::SECRET_KEYS, $key) === 1) {
                continue;
            }
            if (is_string($value)) {
                $out[$key] = preg_match('/^.{0,5000}/su', $value, $m) === 1 ? $m[0] : '';
            } elseif (is_array($value)) {
                $out[$key] = array_values(array_filter($value, 'is_string'));
            }
        }
        return $out;
    }

    /** Client compté : IP (Request::ip) ; en IPv6, le préfixe /64, qu'un abonné reçoit en entier. */
    public static function client(): string
    {
        $ip = Request::ip();
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
            $bin = (string) inet_pton($ip);
            return (string) inet_ntop(substr($bin, 0, 8) . str_repeat("\0", 8)) . '/64';
        }
        return $ip;
    }

    private static function sign(string $form, string $ts): string
    {
        return hash_hmac('sha256', $form . '|' . $ts, Key::derive('formulaires'));
    }
}
