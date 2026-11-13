<?php
declare(strict_types=1);

namespace App\Core;

/**
 * File d'e-mails différés (table outbox).
 * - Action annulable : l'e-mail part après UNDO_SECONDS seulement ; cancel() le retient tant qu'il n'est pas parti.
 * - Réponse rapide et identique : la page met le message en file et l'envoi a lieu après la réponse (flushAfterResponse).
 * - Échec passager (serveur injoignable, 4xx) : nouvel essai plus tard, au plus MAX_ATTEMPTS ; échec définitif gardé (failed_at).
 * Envoi des messages échus : flushAfterResponse() et la tâche planifiée (Outbox::flush()).
 */
final class Outbox
{
    /** Délai pendant lequel l'action peut être annulée, e-mail compris. */
    public const UNDO_SECONDS = 20;
    public const MAX_ATTEMPTS = 5;
    private const RETRY_SECONDS = 600;
    /** Message pris par un envoi interrompu (processus tué) : repris après ce délai. */
    private const CLAIM_TIMEOUT = 600;

    /** @return int identifiant, à garder pour cancel() */
    public static function queue(string $to, string $subject, string $text, ?string $html = null, ?string $replyTo = null, int $delay = self::UNDO_SECONDS): int
    {
        // Validation dès la mise en file : un message invalide échoue tout de suite, pas en silence plus tard.
        Mailer::compose($to, $subject, $text, $html, $replyTo, [], 'controle@exemple.org', '');
        return Db::insert(
            'INSERT INTO outbox (to_email, subject, body_text, body_html, reply_to, send_after, attempts, created_at) VALUES (?, ?, ?, ?, ?, ?, 0, ?)',
            [$to, $subject, $text, $html, $replyTo, date('Y-m-d H:i:s', time() + max(0, $delay)), now()]
        );
    }

    /** Retire un e-mail pas encore parti. @return bool vrai s'il a bien été retenu */
    public static function cancel(int $id): bool
    {
        return Db::run('DELETE FROM outbox WHERE id = ? AND claimed_at IS NULL AND failed_at IS NULL', [$id])->rowCount() === 1;
    }

    /** Envoie les messages échus. @return int nombre envoyé */
    public static function flush(int $limit = 10): int
    {
        $stale = date('Y-m-d H:i:s', time() - self::CLAIM_TIMEOUT);
        $due = Db::run(
            'SELECT * FROM outbox WHERE failed_at IS NULL AND send_after <= ? AND (claimed_at IS NULL OR claimed_at < ?) ORDER BY id LIMIT ' . max(1, $limit),
            [now(), $stale]
        )->fetchAll();
        $sent = 0;
        foreach ($due as $m) {
            // Prise conditionnelle : deux pages ou une page et la tâche planifiée n'envoient pas deux fois le même message.
            $claimed = Db::run('UPDATE outbox SET claimed_at = ? WHERE id = ? AND failed_at IS NULL AND (claimed_at IS NULL OR claimed_at < ?)', [now(), $m['id'], $stale])->rowCount() === 1;
            if (!$claimed) {
                continue;
            }
            if (Mailer::send((string) $m['to_email'], (string) $m['subject'], (string) $m['body_text'], $m['body_html'] !== null ? (string) $m['body_html'] : null, $m['reply_to'] !== null ? (string) $m['reply_to'] : null)) {
                Db::run('DELETE FROM outbox WHERE id = ?', [$m['id']]);
                $sent++;
                continue;
            }
            $attempts = (int) $m['attempts'] + 1;
            $error = preg_match('/^.{0,250}/su', (string) Mailer::$lastError, $cut) === 1 ? $cut[0] : 'erreur illisible';
            if (Mailer::temporary() && $attempts < self::MAX_ATTEMPTS) {
                Db::run('UPDATE outbox SET claimed_at = NULL, attempts = ?, last_error = ?, send_after = ? WHERE id = ?',
                    [$attempts, $error, date('Y-m-d H:i:s', time() + self::RETRY_SECONDS * $attempts), $m['id']]);
            } else {
                Db::run('UPDATE outbox SET claimed_at = NULL, attempts = ?, last_error = ?, failed_at = ? WHERE id = ?', [$attempts, $error, now(), $m['id']]);
                error_log('E-mail abandonné (outbox ' . $m['id'] . ') : ' . $error);
            }
        }
        return $sent;
    }

    /**
     * Envoi des messages échus une fois la réponse partie (PHP-FPM, LiteSpeed) : la page n'attend pas le serveur SMTP,
     * et la durée de la réponse ne dit pas si un e-mail a été envoyé (mot de passe oublié).
     */
    public static function flushAfterResponse(): void
    {
        static $registered = false;
        if ($registered) {
            return;
        }
        $registered = true;
        register_shutdown_function(static function (): void {
            if (function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
            } elseif (function_exists('litespeed_finish_request')) {
                litespeed_finish_request();
            }
            try {
                self::flush();
            } catch (\Throwable $e) {
                error_log('Outbox : ' . $e->getMessage());
            }
        });
    }

    /** Messages abandonnés, pour un tableau de bord. @return list<array<string, mixed>> */
    public static function failed(int $limit = 20): array
    {
        return Db::run('SELECT id, to_email, subject, attempts, last_error, failed_at FROM outbox WHERE failed_at IS NOT NULL ORDER BY failed_at DESC LIMIT ' . max(1, $limit))->fetchAll();
    }

    /** Remet un message abandonné en file (après correction des réglages). */
    public static function retry(int $id): bool
    {
        return Db::run('UPDATE outbox SET failed_at = NULL, attempts = 0, send_after = ? WHERE id = ? AND failed_at IS NOT NULL', [now(), $id])->rowCount() === 1;
    }
}
