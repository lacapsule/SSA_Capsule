<?php

declare(strict_types=1);

namespace App\Support;

final class Mailer
{
    /**
     * Envoie un email texte en UTF-8.
     */
    public static function send(
        string $to,
        string $subject,
        string $body,
        string $fromEmail,
        string $fromName = '',
        ?string $replyToEmail = null,
        ?string $replyToName = null
    ): bool {
        $safeSubject = self::sanitizeHeaderValue($subject);
        $encodedSubject = mb_encode_mimeheader($safeSubject, 'UTF-8');

        $headers = [];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/plain; charset=UTF-8';

        $fromHeader = $fromName !== ''
            ? mb_encode_mimeheader($fromName, 'UTF-8') . " <{$fromEmail}>"
            : $fromEmail;
        $headers[] = "From: {$fromHeader}";

        if ($replyToEmail !== null && $replyToEmail !== '') {
            $replyHeader = $replyToName !== null && $replyToName !== ''
                ? mb_encode_mimeheader($replyToName, 'UTF-8') . " <{$replyToEmail}>"
                : $replyToEmail;
            $headers[] = "Reply-To: {$replyHeader}";
        }

        // Empêcher l'injection d'entêtes en supprimant les sauts de ligne
        $cleanTo = self::sanitizeHeaderValue($to);

        return mail($cleanTo, $encodedSubject, $body, implode("\r\n", $headers));
    }

    private static function sanitizeHeaderValue(string $value): string
    {
        return preg_replace('/[\r\n]+/', ' ', trim($value)) ?? '';
    }
}


