<?php

declare(strict_types=1);

namespace App\Lang;

final class Translate
{
    /** @var array<string,string> */
    private static array $strings = [];

    /** Expose toutes les chaînes actuellement chargées. */
    public static function all(): array
    {
        return self::$strings;
    }

    /** Charge les chaînes pour la langue détectée et renvoie la langue effective. */
    public static function detectAndLoad(string $default = 'fr'): string
    {
        $supported = ['fr','br'];
        $lang = $default;


        // dev only:
        //error_log('GET lang=' . ($_GET['lang'] ?? '∅'));

        // 1) GET
        if (isset($_GET['lang'])) {
            $cand = strtolower(substr((string)$_GET['lang'], 0, 5));
            if (in_array($cand, $supported, true)) {
                $lang = $cand;
                $_SESSION['lang'] = $lang;
                // Cookie optionnel
                @setcookie('lang', $lang, [
                    'expires' => time() + 60 * 60 * 24 * 180,
                    'path' => '/',
                    'secure' => !empty($_SERVER['HTTPS']),
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]);
            } // 2) Session
        } elseif (!empty($_SESSION['lang']) && in_array($_SESSION['lang'], $supported, true)) {
            $lang = $_SESSION['lang'];

            // 3) Cookie
        } elseif (!empty($_COOKIE['lang']) && in_array($_COOKIE['lang'], $supported, true)) {
            $lang = $_COOKIE['lang'];
            $_SESSION['lang'] = $lang;
        }

        // 4) CHARGEMENT (fallback clé-à-clé : défaut -> langue)
        $defaultMap = self::loadFile($default);
        $langMap = $lang === $default ? [] : self::loadFile($lang);

        // default d’abord, puis langue pour écraser les clés
        self::$strings = array_replace($defaultMap, $langMap);

        return $lang;
    }

    /** @return array<string,string> */
    private static function loadFile(string $lang): array
    {
        $file = __DIR__ . "/locales/{$lang}/index.php";
        if (!is_file($file)) {
            // TEMP dev:
            //error_log("[i18n] missing file: {$file}");

            return [];
        }
        $data = require $file;
        if (!is_array($data)) {
            error_log("[i18n] file does not return array: {$file}");

            return [];
        }

        return $data;
    }
}
