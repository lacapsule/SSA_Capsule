<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Secrets au repos (secret TOTP, clés d'API) : libsodium secretbox sous une sous-clé de l'application (Key::derive).
 * Empreintes à clé (codes de secours, empreinte de session) : la base seule ne permet pas de les retrouver.
 */
final class Crypto
{
    private const PREFIX = 's1:';

    public static function seal(string $plain): string
    {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        return self::PREFIX . base64_encode($nonce . sodium_crypto_secretbox($plain, $nonce, Key::derive('secrets')));
    }

    /** Valeur en clair, null si absente, altérée ou chiffrée avec une autre clé. */
    public static function open(?string $sealed): ?string
    {
        if ($sealed === null || !str_starts_with($sealed, self::PREFIX)) {
            return null;
        }
        $raw = base64_decode(substr($sealed, strlen(self::PREFIX)), true);
        if ($raw === false || strlen($raw) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            return null;
        }
        $plain = sodium_crypto_secretbox_open(
            substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES),
            substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES),
            Key::derive('secrets')
        );
        return $plain === false ? null : $plain;
    }

    public static function mac(string $value): string
    {
        return hash_hmac('sha256', $value, Key::derive('empreintes'));
    }
}
