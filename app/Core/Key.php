<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Clé secrète de l'application (signatures HMAC, chiffrement des secrets au repos).
 * APP_KEY du .env en production ; à défaut, clé locale générée une fois dans data/app.key (jamais versionnée, droits 0600).
 * Chaque usage reçoit sa propre sous-clé (derive) : une signature de formulaire ne peut pas servir de jeton d'accès.
 */
final class Key
{
    private static ?string $key = null;

    public static function app(): string
    {
        if (self::$key !== null) {
            return self::$key;
        }
        $env = (string) ($_ENV['APP_KEY'] ?? '');
        if ($env !== '') {
            return self::$key = $env;
        }
        if (!is_local() && PHP_SAPI !== 'cli') {
            error_log('APP_KEY vide dans le .env : clé locale data/app.key utilisée. Renseignez APP_KEY en production.');
        }
        $file = ROOT . '/data/app.key';
        if (!is_file($file)) {
            is_dir(dirname($file)) || mkdir(dirname($file), 0775, true);
            // Création exclusive : deux requêtes simultanées ne produisent pas deux clés différentes.
            $handle = @fopen($file, 'x');
            if ($handle !== false) {
                fwrite($handle, bin2hex(random_bytes(32)));
                fclose($handle);
                @chmod($file, 0600);
            }
        }
        $key = trim((string) file_get_contents($file));
        if ($key === '') {
            throw new \RuntimeException('Clé de l\'application illisible : renseignez APP_KEY dans le .env.');
        }
        return self::$key = $key;
    }

    /** Sous-clé binaire de 32 octets pour un usage nommé ('formulaires', 'secrets', 'acces-invite'...). */
    public static function derive(string $purpose): string
    {
        return hash_hmac('sha256', $purpose, self::app(), true);
    }
}
