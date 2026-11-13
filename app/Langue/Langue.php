<?php
declare(strict_types=1);

namespace App\Langue;

use App\Core\Router;

/**
 * Langue de la requête, portée par l'URL : langue par défaut à la racine (/a-propos/), les autres sous leur préfixe
 * (/en/about/). Ni cookie ni redirection selon le navigateur : une adresse = une langue, ce que les moteurs indexent.
 *
 * Les routes sont déclarées une seule fois, avec les chemins de la langue par défaut (chemins « internes ») ;
 * demarrer() retire le préfixe et traduit le chemin avant le routeur, url() fait l'inverse pour les liens.
 */
final class Langue
{
    /** @var array{defaut: string, langues: array<string, array<string, string>>, chemins: array<string, array<string, string>>, proposition: bool, champ_requis: string}|null */
    private static ?array $config = null;
    private static ?string $actuelle = null;
    private static string $chemin = '/';

    /** Réglages explicites (tests, script) ; sinon config('langues') ou config/langues.php. @param array<string, mixed> $config */
    public static function configurer(array $config): void
    {
        $langues = [];
        foreach ((array) ($config['langues'] ?? []) as $code => $l) {
            $code = strtolower((string) $code);
            if (preg_match('/^[a-z]{2,3}(-[a-z0-9]{2,8})?$/', $code) !== 1) {
                throw new \InvalidArgumentException("Code de langue invalide : $code");
            }
            $l = (array) $l;
            $langues[$code] = [
                'nom' => (string) ($l['nom'] ?? $code),
                'locale' => (string) ($l['locale'] ?? $code),
                'hreflang' => (string) ($l['hreflang'] ?? $code),
                'dir' => ($l['dir'] ?? 'ltr') === 'rtl' ? 'rtl' : 'ltr',
            ];
        }
        $defaut = strtolower((string) ($config['defaut'] ?? array_key_first($langues) ?? 'fr'));
        if (!isset($langues[$defaut])) {
            $langues = [$defaut => ['nom' => $defaut, 'locale' => $defaut, 'hreflang' => $defaut, 'dir' => 'ltr']] + $langues;
        }
        $chemins = [];
        foreach ((array) ($config['chemins'] ?? []) as $source => $traductions) {
            foreach ((array) $traductions as $code => $cible) {
                if (isset($langues[$code]) && $code !== $defaut && self::cheminValide((string) $source) && self::cheminValide((string) $cible)) {
                    $chemins[(string) $source][(string) $code] = (string) $cible;
                }
            }
        }
        self::$config = [
            'defaut' => $defaut,
            'langues' => $langues,
            'chemins' => $chemins,
            'proposition' => (bool) ($config['proposition'] ?? false),
            'champ_requis' => (string) ($config['champ_requis'] ?? 'titre'),
        ];
        self::$actuelle = null;
        self::$chemin = '/';
    }

    /** @return array{defaut: string, langues: array<string, array<string, string>>, chemins: array<string, array<string, string>>, proposition: bool, champ_requis: string} */
    public static function config(): array
    {
        if (self::$config === null) {
            $config = function_exists('config') ? config('langues') : null;
            if (!is_array($config) && defined('ROOT') && is_file(ROOT . '/config/langues.php')) {
                $config = require ROOT . '/config/langues.php';
            }
            self::configurer(is_array($config) ? $config : ['defaut' => 'fr', 'langues' => ['fr' => ['nom' => 'Français', 'locale' => 'fr_FR']]]);
        }
        return self::$config;
    }

    public static function defaut(): string
    {
        return self::config()['defaut'];
    }

    /** @return array<string, array{nom: string, locale: string, hreflang: string, dir: string}> */
    public static function langues(): array
    {
        return self::config()['langues'];
    }

    public static function existe(string $code): bool
    {
        return isset(self::config()['langues'][$code]);
    }

    public static function actuelle(): string
    {
        return self::$actuelle ?? self::defaut();
    }

    public static function definir(string $code): void
    {
        if (!self::existe($code)) {
            throw new \InvalidArgumentException("Langue non déclarée : $code");
        }
        self::$actuelle = $code;
    }

    /** Chemin interne de la requête (langue par défaut, sans préfixe) : celui que voit le routeur. */
    public static function chemin(): string
    {
        return self::$chemin;
    }

    public static function nom(?string $code = null): string
    {
        return self::langues()[$code ?? self::actuelle()]['nom'] ?? (string) $code;
    }

    public static function dir(?string $code = null): string
    {
        return self::langues()[$code ?? self::actuelle()]['dir'] ?? 'ltr';
    }

    public static function locale(?string $code = null): string
    {
        return self::langues()[$code ?? self::actuelle()]['locale'] ?? self::defaut();
    }

    public static function hreflang(?string $code = null): string
    {
        return self::langues()[$code ?? self::actuelle()]['hreflang'] ?? (string) $code;
    }

    public static function champRequis(): string
    {
        return self::config()['champ_requis'];
    }

    /**
     * Lecture d'un chemin demandé : langue, chemin interne et, s'il y a lieu, l'adresse unique vers laquelle rediriger
     * (/fr/contact/ vers /contact/, /en vers /en/, /EN/ vers /en/, /en/a-propos/ vers /en/about/).
     * @return array{langue: string, chemin: string, redirection: ?string}
     */
    public static function analyser(string $path): array
    {
        $defaut = self::defaut();
        if (preg_match('#^/([A-Za-z]{2,3}(?:-[A-Za-z0-9]{2,8})?)(/.*)?$#', $path, $m) !== 1) {
            return ['langue' => $defaut, 'chemin' => $path, 'redirection' => null];
        }
        $code = strtolower($m[1]);
        $reste = $m[2] ?? '';
        if (!self::existe($code)) {
            return ['langue' => $defaut, 'chemin' => $path, 'redirection' => null];
        }
        if ($code === $defaut) {
            return ['langue' => $defaut, 'chemin' => $reste ?: '/', 'redirection' => $reste !== '' ? $reste : '/'];
        }
        if ($reste === '') {
            return ['langue' => $code, 'chemin' => '/', 'redirection' => '/' . $code . '/'];
        }
        $interne = self::versInterne($reste, $code);
        // Chemin de la langue par défaut écrit sous le préfixe alors qu'il a une traduction : une seule adresse.
        $traduit = self::traduireChemin($interne, $code);
        if ($m[1] !== $code || $traduit !== $reste) {
            return ['langue' => $code, 'chemin' => $interne, 'redirection' => '/' . $code . $traduit];
        }
        return ['langue' => $code, 'chemin' => $interne, 'redirection' => null];
    }

    /**
     * À appeler dans public/index.php juste après le chargement des routes : redirige vers l'adresse unique (301),
     * fixe la langue et renvoie le chemin interne à passer au routeur et à Headers::send().
     */
    public static function demarrer(string $path, Router $router, string $method = 'GET'): string
    {
        $lecture = self::analyser($path);
        $lecture_seule = $method === 'GET' || $method === 'HEAD';
        $query = (string) ($_SERVER['QUERY_STRING'] ?? '');
        $query = $query !== '' ? '?' . $query : '';
        if ($lecture['redirection'] !== null && $lecture_seule) {
            redirect($lecture['redirection'] . $query, 301);
        }
        self::definir($lecture['langue']);
        self::$chemin = $lecture['chemin'];
        // Slash final sous un préfixe : même règle que le contrôleur frontal du socle, préfixe gardé. Le chemin est relu
        // avec le slash, car une adresse traduite (/en/about) ne se reconnaît qu'entière (/en/about/).
        $interne = $lecture['chemin'];
        if ($lecture_seule && $lecture['langue'] !== self::defaut() && !str_ends_with($path, '/')
            && !str_contains(basename($interne), '.') && !$router->has($interne)) {
            $avecSlash = self::analyser($path . '/')['chemin'];
            if ($router->has($avecSlash)) {
                redirect(self::url($avecSlash) . $query, 301);
            }
        }
        if (self::config()['proposition'] && PHP_SAPI !== 'cli' && !headers_sent()) {
            header('Vary: Accept-Language', false);
        }
        return $interne;
    }

    /**
     * Adresse d'une page dans une langue (langue courante par défaut), à partir de son chemin interne.
     * La requête (?page=2) et l'ancre (#contact) sont gardées.
     */
    public static function url(string $chemin, ?string $langue = null): string
    {
        $langue ??= self::actuelle();
        $suffixe = '';
        if (preg_match('/^([^?#]*)([?#].*)$/s', $chemin, $m) === 1) {
            [$chemin, $suffixe] = [$m[1], $m[2]];
        }
        if ($chemin === '' || $chemin[0] !== '/' || str_starts_with($chemin, '//')) {
            return $chemin . $suffixe;
        }
        if ($langue === self::defaut() || !self::existe($langue)) {
            return $chemin . $suffixe;
        }
        return '/' . $langue . self::traduireChemin($chemin, $langue) . $suffixe;
    }

    /** Adresse absolue (APP_URL), pour canonical, hreflang, sitemap et Open Graph. */
    public static function absolue(string $chemin, ?string $langue = null): string
    {
        return rtrim((string) ($_ENV['APP_URL'] ?? ''), '/') . self::url($chemin, $langue);
    }

    /**
     * Langues dans lesquelles une page existe, dans l'ordre de la configuration.
     * @param list<string>|null $disponibles null : toutes les langues déclarées
     * @return list<string>
     */
    public static function filtrer(?array $disponibles): array
    {
        $codes = array_keys(self::langues());
        return $disponibles === null ? $codes : array_values(array_filter($codes, static fn (string $c): bool => in_array($c, $disponibles, true)));
    }

    /**
     * Liens hreflang d'une page : une entrée par langue où elle existe, plus x-default (langue par défaut, sinon la
     * première disponible). Le même ensemble est produit depuis chacune des versions : les liens sont réciproques.
     * Vide si la page n'existe que dans une langue.
     * @param list<string>|null $disponibles
     * @return list<array{hreflang: string, href: string, langue: string}>
     */
    public static function alternatives(string $chemin, ?array $disponibles = null): array
    {
        $codes = self::filtrer($disponibles);
        if (count($codes) < 2) {
            return [];
        }
        $liens = [];
        foreach ($codes as $code) {
            $liens[] = ['hreflang' => self::hreflang($code), 'href' => self::absolue($chemin, $code), 'langue' => $code];
        }
        $repli = in_array(self::defaut(), $codes, true) ? self::defaut() : $codes[0];
        $liens[] = ['hreflang' => 'x-default', 'href' => self::absolue($chemin, $repli), 'langue' => $repli];
        return $liens;
    }

    /**
     * Entrées du sélecteur de langue : chaque langue mène à la page équivalente si elle existe, sinon à son accueil.
     * @param list<string>|null $disponibles
     * @return list<array{code: string, nom: string, href: string, hreflang: string, dir: string, actuelle: bool, equivalente: bool}>
     */
    public static function selecteur(string $chemin, ?array $disponibles = null): array
    {
        $existe = self::filtrer($disponibles);
        $out = [];
        foreach (self::langues() as $code => $l) {
            $equivalente = in_array($code, $existe, true);
            $out[] = [
                'code' => $code,
                'nom' => $l['nom'],
                'href' => self::url($equivalente ? $chemin : '/', $code),
                'hreflang' => $l['hreflang'],
                'dir' => $l['dir'],
                'actuelle' => $code === self::actuelle(),
                'equivalente' => $equivalente,
            ];
        }
        return $out;
    }

    /**
     * Langue à proposer discrètement d'après l'en-tête Accept-Language : la mieux classée par le navigateur, déclarée,
     * où la page existe, différente de la langue affichée. Jamais de redirection.
     * @param list<string>|null $disponibles
     */
    public static function proposition(?string $acceptLanguage, ?array $disponibles = null): ?string
    {
        if (!self::config()['proposition'] || $acceptLanguage === null || $acceptLanguage === '') {
            return null;
        }
        $voeux = [];
        foreach (array_slice(explode(',', $acceptLanguage), 0, 12) as $i => $part) {
            if (preg_match('/^\s*([A-Za-z]{1,8}(?:-[A-Za-z0-9]{1,8})*|\*)\s*(?:;\s*q\s*=\s*([01](?:\.\d{0,3})?))?\s*$/', $part, $m) !== 1) {
                continue;
            }
            $q = isset($m[2]) ? (float) $m[2] : 1.0;
            if ($q > 0 && $m[1] !== '*') {
                $voeux[] = [strtolower($m[1]), $q, $i];
            }
        }
        usort($voeux, static fn (array $a, array $b): int => [$b[1], $a[2]] <=> [$a[1], $b[2]]);
        $existe = self::filtrer($disponibles);
        foreach ($voeux as [$tag]) {
            $code = self::existe($tag) ? $tag : (self::existe(explode('-', $tag)[0]) ? explode('-', $tag)[0] : null);
            if ($code === null) {
                continue;
            }
            // Première langue reconnue : si c'est celle affichée, le visiteur lit déjà sa langue préférée.
            return $code !== self::actuelle() && in_array($code, $existe, true) ? $code : null;
        }
        return null;
    }

    /** Chemin interne vers chemin traduit (sans préfixe), par la plus longue entrée de « chemins » qui s'applique. */
    private static function traduireChemin(string $chemin, string $langue): string
    {
        $meilleure = null;
        foreach (self::config()['chemins'] as $source => $traductions) {
            if (!isset($traductions[$langue])) {
                continue;
            }
            $applique = $chemin === $source || (str_ends_with($source, '/') && $source !== '/' && str_starts_with($chemin, $source));
            if ($applique && ($meilleure === null || strlen($source) > strlen($meilleure))) {
                $meilleure = $source;
            }
        }
        return $meilleure === null ? $chemin : self::config()['chemins'][$meilleure][$langue] . substr($chemin, strlen($meilleure));
    }

    /** Chemin traduit vers chemin interne (inverse de traduireChemin). */
    private static function versInterne(string $reste, string $langue): string
    {
        $meilleure = null;
        $cible = '';
        foreach (self::config()['chemins'] as $source => $traductions) {
            $t = $traductions[$langue] ?? null;
            if ($t === null) {
                continue;
            }
            $applique = $reste === $t || (str_ends_with($t, '/') && $t !== '/' && str_starts_with($reste, $t));
            if ($applique && strlen($t) > strlen($cible)) {
                [$meilleure, $cible] = [$source, $t];
            }
        }
        return $meilleure === null ? $reste : $meilleure . substr($reste, strlen($cible));
    }

    private static function cheminValide(string $chemin): bool
    {
        return preg_match('~^/(?!/)[^\s?#]*$~', $chemin) === 1;
    }
}
