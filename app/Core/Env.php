<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Lecture du fichier .env (jamais versionné) dans $_ENV.
 * Une variable déjà définie dans l'environnement du processus (hébergeur, tâche planifiée, tests) prime toujours sur le .env.
 */
final class Env
{
    /** Variables reprises de l'environnement du processus même absentes du .env. Ajouter ici celles du projet. */
    private const KEYS = ['APP_ENV', 'APP_URL', 'APP_KEY', 'DB_DSN', 'DB_USER', 'DB_PASSWORD', 'MAIL_CATCHER'];

    public static function load(string $file): void
    {
        foreach (self::KEYS as $key) {
            $real = getenv($key);
            if ($real !== false && !isset($_ENV[$key])) {
                $_ENV[$key] = $real;
            }
        }
        if (!is_file($file)) {
            return;
        }
        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = array_map('trim', explode('=', $line, 2));
            if (preg_match('/^[A-Z][A-Z0-9_]*$/', $key) !== 1) {
                continue;
            }
            // Valeur entre guillemets : guillemets retirés, contenu gardé tel quel (un « # » y reste littéral).
            if (preg_match('/^(["\'])(.*)\1$/', $value, $m) === 1) {
                $value = $m[2];
            }
            $real = getenv($key);
            $_ENV[$key] ??= $real !== false ? $real : $value;
        }
    }
}
