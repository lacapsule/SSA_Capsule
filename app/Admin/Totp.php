<?php
declare(strict_types=1);

namespace App\Admin;

/** Codes à usage unique basés sur le temps (TOTP, RFC 6238 sur HOTP, RFC 4226), sans dépendance. SHA-1, 6 chiffres, 30 s. */
final class Totp
{
    public const PERIOD = 30;
    public const DIGITS = 6;
    /** Pas acceptés autour du pas courant (décalage d'horloge du téléphone). */
    public const WINDOW = 1;
    private const B32 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /** Secret de 160 bits (recommandation RFC 4226), en base32. */
    public static function secret(): string
    {
        return self::base32Encode(random_bytes(20));
    }

    public static function base32Encode(string $bin): string
    {
        $bits = '';
        foreach (str_split($bin) as $c) {
            $bits .= str_pad(decbin(ord($c)), 8, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bits, 5) as $chunk) {
            $out .= self::B32[bindec(str_pad($chunk, 5, '0'))];
        }
        return $out;
    }

    public static function base32Decode(string $b32): string
    {
        $bits = '';
        $clean = strtoupper((string) preg_replace('/[\s=-]/', '', $b32));
        foreach ($clean === '' ? [] : str_split($clean) as $c) {
            $i = strpos(self::B32, $c);
            if ($i === false) {
                throw new \InvalidArgumentException('Clé base32 invalide');
            }
            $bits .= str_pad(decbin($i), 5, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach ($bits === '' ? [] : str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $out .= chr((int) bindec($byte));
            }
        }
        return $out;
    }

    public static function hotp(string $secret, int $counter): string
    {
        $mac = hash_hmac('sha1', pack('J', $counter), $secret, true);
        $offset = ord($mac[19]) & 0x0f;
        $bin = (unpack('N', substr($mac, $offset, 4))[1] & 0x7fffffff) % (10 ** self::DIGITS);
        return str_pad((string) $bin, self::DIGITS, '0', STR_PAD_LEFT);
    }

    /** Pas correspondant au code dans la fenêtre ±WINDOW, sinon null. L'appelant refuse un pas déjà utilisé (anti-rejeu). */
    public static function matchStep(string $secretB32, string $code, ?int $now = null): ?int
    {
        $given = (string) preg_replace('/\s/', '', $code);
        if (preg_match('/^\d{' . self::DIGITS . '}$/', $given) !== 1) {
            return null;
        }
        $secret = self::base32Decode($secretB32);
        $current = intdiv($now ?? time(), self::PERIOD);
        $matched = null;
        // Toujours toutes les comparaisons : la durée ne dit pas quel pas correspond.
        for ($d = -self::WINDOW; $d <= self::WINDOW; $d++) {
            if (hash_equals(self::hotp($secret, $current + $d), $given) && $matched === null) {
                $matched = $current + $d;
            }
        }
        return $matched;
    }

    /** URI lue par les applications d'authentification (QR code ou saisie manuelle). */
    public static function uri(string $secretB32, string $account, string $issuer): string
    {
        return 'otpauth://totp/' . rawurlencode("$issuer:$account") . '?' . http_build_query([
            'secret' => $secretB32, 'issuer' => $issuer, 'algorithm' => 'SHA1', 'digits' => self::DIGITS, 'period' => self::PERIOD,
        ], '', '&', PHP_QUERY_RFC3986);
    }
}
