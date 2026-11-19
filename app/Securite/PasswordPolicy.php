<?php
declare(strict_types=1);

namespace App\Securite;

/**
 * Politique de mot de passe partagée avec le script du champ (script.js, variante .champ-mdp--force).
 * Recommandations ANSSI et NIST SP 800-63B : une longueur minimale (12 par défaut) plutôt que des règles de
 * composition imposées, refus des mots de passe trop courants ou trop simples, pas de limite maximale basse
 * (128 par défaut, 64 au moins), tous les caractères acceptés (espaces, accents, emoji), collage permis.
 *
 * Même configuration des deux côtés : le gabarit (templates/partials/password.php) écrit min et max dans
 * data-mdp-min et data-mdp-max, les libellés des critères viennent de criteres() ; la liste des mots de passe
 * courants (config/mots-de-passe-courants.txt) est recopiée dans script.js (COURANTS), le test le vérifie.
 *
 *   $politique = PasswordPolicy::parDefaut();
 *   if (($erreur = $politique->erreur($_POST['password'] ?? '', $email)) !== null) { $errors['password'] = $erreur; }
 *   $hash = password_hash($mdp, PASSWORD_ARGON2ID);   // voir NOTES.md : bcrypt ignore au-delà de 72 octets
 */
final class PasswordPolicy
{
    public const MIN = 12;
    public const MAX = 128;

    /** @var array<string, true> */
    private array $courants;

    /** @param list<string>|null $courants liste des mots refusés (null : config/mots-de-passe-courants.txt) */
    public function __construct(
        public readonly int $min = self::MIN,
        public readonly int $max = self::MAX,
        ?array $courants = null,
    ) {
        if ($min < 8 || $max < 64 || $max < $min) {
            throw new \InvalidArgumentException('Politique invalide : 8 caractères minimum au moins, 64 maximum au moins.');
        }
        $this->courants = array_fill_keys(array_map(static fn (string $m): string => mb_strtolower(trim($m)), $courants ?? self::listeParDefaut()), true);
    }

    public static function parDefaut(): self
    {
        return new self();
    }

    /** @return list<string> */
    public static function listeParDefaut(): array
    {
        $lignes = file(dirname(__DIR__, 2) . '/config/mots-de-passe-courants.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        return array_values(array_filter(array_map('trim', $lignes), static fn (string $l): bool => $l !== '' && !str_starts_with($l, '#')));
    }

    /**
     * Critères affichés et cochés en direct, dans l'ordre (mêmes identifiants que data-critere du gabarit).
     * $identifiant : e-mail ou nom de compte ; le critère « différent de l'identifiant » n'apparaît qu'avec lui.
     * @return list<array{id: string, libelle: string, ok: bool}>
     */
    public function criteres(string $mdp = '', ?string $identifiant = null): array
    {
        $liste = [
            ['id' => 'longueur', 'libelle' => 'Au moins ' . $this->min . ' caractères', 'ok' => self::longueur($mdp) >= $this->min],
            ['id' => 'courant', 'libelle' => 'Pas un mot de passe courant ou trop simple', 'ok' => $mdp !== '' && !$this->estCourant($mdp)],
        ];
        if ($identifiant !== null) {
            $liste[] = ['id' => 'identifiant', 'libelle' => 'Ne contient pas votre identifiant', 'ok' => $mdp !== '' && !self::contientIdentifiant($mdp, $identifiant)];
        }
        return $liste;
    }

    /** Premier problème, en phrase, ou null si le mot de passe est accepté. */
    public function erreur(string $mdp, ?string $identifiant = null): ?string
    {
        $n = self::longueur($mdp);
        if ($n === 0) {
            return 'Saisissez un mot de passe.';
        }
        if ($n < $this->min) {
            return 'Trop court : ' . $n . ' caractère' . ($n > 1 ? 's' : '') . ' sur ' . $this->min . '.';
        }
        if ($n > $this->max) {
            return 'Trop long : ' . $this->max . ' caractères au plus.';
        }
        if ($this->estCourant($mdp)) {
            return 'Ce mot de passe est trop courant ou trop simple. Une courte phrase de plusieurs mots est plus sûre.';
        }
        if ($identifiant !== null && self::contientIdentifiant($mdp, $identifiant)) {
            return 'Le mot de passe ne doit pas contenir votre identifiant.';
        }
        return null;
    }

    public function estValide(string $mdp, ?string $identifiant = null): bool
    {
        return $this->erreur($mdp, $identifiant) === null;
    }

    /** Longueur en caractères (points de code), comme [...valeur].length côté navigateur. */
    public static function longueur(string $mdp): int
    {
        return mb_strlen($mdp, 'UTF-8');
    }

    /**
     * Courant : dans la liste, tel quel ou sans les chiffres et symboles du début et de la fin (« Motdepasse2026! ») ;
     * trop simple : un seul caractère répété ou une suite (« 123456789012 », « abcdefghijkl »).
     */
    public function estCourant(string $mdp): bool
    {
        $bas = mb_strtolower(trim($mdp), 'UTF-8');
        $coeur = (string) preg_replace('/^[^\p{L}]+|[^\p{L}]+$/u', '', $bas);
        return isset($this->courants[$bas]) || ($coeur !== '' && isset($this->courants[$coeur])) || self::tropSimple($bas);
    }

    private static function tropSimple(string $bas): bool
    {
        $car = preg_split('//u', $bas, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count(array_unique($car)) <= 2) {
            return true;
        }
        $codes = array_map('mb_ord', $car);
        foreach ([1, -1] as $sens) {
            $suite = true;
            for ($i = 1, $n = count($codes); $i < $n; $i++) {
                $ecart = $codes[$i] - $codes[$i - 1];
                // Chiffres en boucle : « 7890 » continue « 123456 ».
                $boucle = ctype_digit($car[$i] . $car[$i - 1]) && $ecart === -9 * $sens;
                if ($ecart !== $sens && !$boucle) {
                    $suite = false;
                    break;
                }
            }
            if ($suite) {
                return true;
            }
        }
        return false;
    }

    /** L'identifiant (ou la partie avant @ d'un e-mail, 3 caractères au moins) apparaît dans le mot de passe. */
    private static function contientIdentifiant(string $mdp, string $identifiant): bool
    {
        $id = mb_strtolower(trim($identifiant), 'UTF-8');
        $local = str_contains($id, '@') ? strstr($id, '@', true) : $id;
        $bas = mb_strtolower($mdp, 'UTF-8');
        foreach (array_unique([$id, (string) $local]) as $morceau) {
            if (mb_strlen($morceau) >= 3 && str_contains($bas, $morceau)) {
                return true;
            }
        }
        return false;
    }
}
