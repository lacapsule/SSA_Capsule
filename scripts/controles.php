<?php
declare(strict_types=1);

/*
 * Contrôles qualité d'un projet PHP, sans dépendance : syntaxe, motifs dangereux, fichiers de sécurité, contraste AA des thèmes.
 *
 *   php scripts/controles.php                        tout
 *   php scripts/controles.php --seulement=contraste  une section : syntaxe, motifs, fichiers, contraste (plusieurs : virgules)
 *   php scripts/controles.php --tokens=base/tokens.css --detail
 *   php scripts/controles.php --racine=/autre/projet --paires=contrastes.json
 *
 * Contraste : lit un fichier de tokens CSS (:root = thème par défaut, puis chaque bloc [data-theme="..."] qui le complète),
 * résout les var(--x), compose les couleurs translucides sur leur fond, et calcule le rapport WCAG 2 de chaque paire
 * (texte 4,5:1, contours et éléments d'interface 3:1). Paires par défaut : noms des tokens de la boîte à outils Kouzia
 * (--color-text, --color-surface...) ; une paire dont un token manque est ignorée et comptée.
 *
 * Exception ponctuelle à un motif : commentaire « controles-ok : raison » sur la ligne ou la précédente.
 * Code de sortie : 1 s'il reste au moins un échec (les avertissements ne bloquent pas).
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$options = [];
foreach (array_slice($argv, 1) as $a) {
    if (preg_match('/^--([a-z-]+)(?:=(.*))?$/', $a, $m) === 1) {
        $options[$m[1]] = $m[2] ?? '1';
    }
}
$racine = rtrim((string) realpath((string) ($options['racine'] ?? dirname(__DIR__))), '/');
if ($racine === '' || !is_dir($racine)) {
    fwrite(STDERR, "Racine introuvable.\n");
    exit(2);
}
$sections = isset($options['seulement']) ? array_map('trim', explode(',', $options['seulement'])) : ['syntaxe', 'motifs', 'fichiers', 'contraste'];
$detail = isset($options['detail']);
$couleur = getenv('NO_COLOR') === false && function_exists('stream_isatty') && stream_isatty(STDOUT);

$compte = ['ok' => 0, 'avert' => 0, 'echec' => 0];
$ligne = static function (string $niveau, string $texte, string $conseil = '') use (&$compte, $couleur): void {
    $compte[$niveau]++;
    [$etiquette, $code] = ['ok' => ['[ OK  ]', '32'], 'avert' => ['[AVERT]', '33'], 'echec' => ['[ÉCHEC]', '31']][$niveau];
    echo '  ' . ($couleur ? "\033[{$code}m$etiquette\033[0m" : $etiquette) . ' ' . $texte . PHP_EOL;
    if ($conseil !== '') {
        echo '          -> ' . $conseil . PHP_EOL;
    }
};
$titre = static function (string $t) use ($couleur): void {
    echo PHP_EOL . ($couleur ? "\033[1m$t\033[0m" : $t) . PHP_EOL;
};
$relatif = static fn (string $p): string => ltrim(substr($p, strlen($racine)), '/');

/** Fichiers du projet d'une extension, hors dépendances, données et sorties. @return list<string> */
$fichiers = static function (array $extensions) use ($racine): array {
    $liste = [];
    $it = new RecursiveIteratorIterator(new RecursiveCallbackFilterIterator(
        new RecursiveDirectoryIterator($racine, FilesystemIterator::SKIP_DOTS),
        static fn (SplFileInfo $f): bool => !($f->isDir() && in_array($f->getFilename(), ['.git', 'vendor', 'node_modules', 'data', 'dist', 'var', '.claude'], true))
    ));
    foreach ($it as $f) {
        if ($f->isFile() && in_array(strtolower($f->getExtension()), $extensions, true)) {
            $liste[] = $f->getPathname();
        }
    }
    sort($liste, SORT_STRING);
    return $liste;
};

// ---------------------------------------------------------------------------------------------------------------
// 1. Syntaxe PHP
// ---------------------------------------------------------------------------------------------------------------
if (in_array('syntaxe', $sections, true)) {
    $titre('Syntaxe PHP');
    $php = $fichiers(['php']);
    $fautes = 0;
    foreach ($php as $f) {
        $sortie = [];
        exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($f) . ' 2>&1', $sortie, $code);
        if ($code !== 0) {
            $fautes++;
            $ligne('echec', 'Syntaxe : ' . $relatif($f), trim((string) ($sortie[0] ?? '')));
        }
    }
    if ($fautes === 0) {
        $ligne('ok', count($php) . ' fichiers PHP : syntaxe correcte');
    }
}

// ---------------------------------------------------------------------------------------------------------------
// 2. Motifs dangereux
// ---------------------------------------------------------------------------------------------------------------
if (in_array('motifs', $sections, true)) {
    $titre('Motifs dangereux');
    $motifs = [
        'php' => [
            ['echec', '/\beval\s*\(/', 'eval()', 'Jamais de code construit à l\'exécution.'],
            ['echec', '/\b(shell_exec|passthru|system|popen|exec|proc_open)\s*\([^;]*\$_(GET|POST|REQUEST|COOKIE|SERVER)/', 'saisie passée à une commande système', 'Jamais de saisie dans une commande : liste blanche, ou proc_open() avec un tableau.'],
            ['avert', '/\b(shell_exec|passthru|system|popen)\s*\(|(?<![\w>:$])exec\s*\(/', 'commande système', 'Chaque argument par escapeshellarg(), ou proc_open() avec un tableau d\'arguments, sans shell.'],
            ['echec', '/unserialize\s*\(\s*\$_(GET|POST|COOKIE|REQUEST)/', 'unserialize() d\'une saisie', 'json_decode() à la place.'],
            ['echec', '/\b(echo|print)\s+\$_(GET|POST|COOKIE|REQUEST|SERVER)\b/', 'saisie affichée sans échappement', 'Toujours e() (htmlspecialchars).'],
            ['echec', '/extract\s*\(\s*\$_(GET|POST|COOKIE|REQUEST)/', 'extract() d\'une saisie', 'Lire chaque champ attendu.'],
            ['echec', '/\bmd5\s*\(\s*\$(mdp|motDePasse|mot_de_passe|pass|password)/i', 'mot de passe haché en md5', 'password_hash() et password_verify().'],
            ['echec', '/\bmysql_[a-z_]+\s*\(/', 'extension mysql_*', 'PDO et requêtes préparées.'],
            ['avert', '/->(query|exec)\s*\([^;]*\.\s*\$_(GET|POST|REQUEST|COOKIE)/', 'saisie concaténée dans du SQL', 'Requête préparée avec paramètres.'],
            ['avert', '/\$_REQUEST\b/', '$_REQUEST', 'Lire $_GET ou $_POST selon la méthode attendue.'],
            ['avert', "/ini_set\(\s*'display_errors'\s*,\s*'1'\s*\)/", 'display_errors forcé à 1', 'Seulement en local (is_local()).'],
            ['avert', '/\bvar_dump\s*\(|\bprint_r\s*\([^,)]*\)\s*;/', 'trace de débogage', 'À retirer avant livraison.'],
        ],
        'js' => [
            ['echec', '/\.(inner|outer)HTML\s*\+?=|insertAdjacentHTML|document\.write\s*\(/', 'HTML injecté', 'createElement et textContent, ou un template cloné.'],
            ['echec', '/\beval\s*\(|new\s+Function\s*\(/', 'code construit à l\'exécution', 'À remplacer.'],
        ],
    ];
    $soi = (string) realpath(__FILE__);
    $trouves = 0;
    foreach (['php' => ['php'], 'js' => ['js', 'mjs']] as $famille => $ext) {
        foreach ($fichiers($ext) as $f) {
            if (realpath($f) === $soi || str_contains($f, '.min.')) {
                continue;
            }
            $lignes = file($f, FILE_IGNORE_NEW_LINES) ?: [];
            foreach ($lignes as $i => $texte) {
                $code = ltrim($texte);
                if (str_starts_with($code, '//') || str_starts_with($code, '*') || str_starts_with($code, '#')) {
                    continue;
                }
                foreach ($motifs[$famille] as [$niveau, $regex, $quoi, $conseil]) {
                    if (preg_match($regex, $texte) !== 1) {
                        continue;
                    }
                    if (str_contains($texte, 'controles-ok') || ($i > 0 && str_contains($lignes[$i - 1], 'controles-ok'))) {
                        continue;
                    }
                    $trouves++;
                    $ligne($niveau, $relatif($f) . ':' . ($i + 1) . " : $quoi", $conseil);
                }
            }
        }
    }
    if ($trouves === 0) {
        $ligne('ok', 'Aucun motif dangereux dans le PHP et le JavaScript');
    }
}

// ---------------------------------------------------------------------------------------------------------------
// 3. Fichiers de sécurité
// ---------------------------------------------------------------------------------------------------------------
if (in_array('fichiers', $sections, true)) {
    $titre('Fichiers de sécurité');
    $public = is_dir($racine . '/public') ? $racine . '/public' : null;
    if ($public === null) {
        $ligne('avert', 'Pas de dossier public/ : contrôles du .htaccess ignorés');
    } else {
        $ht = is_file($public . '/.htaccess') ? (string) file_get_contents($public . '/.htaccess') : null;
        if ($ht === null) {
            $ligne('echec', 'public/.htaccess absent', 'Reprendre celui de gabarits/socle-php (ou modules/durcissement-apache).');
        } else {
            $manque = [];
            foreach (['-Indexes' => 'listing des dossiers coupé', 'ServerSignature Off' => 'signature du serveur coupée', '\\.(?!well-known' => 'fichiers cachés refusés', 'RewriteEngine On' => 'réécritures actives'] as $motif => $role) {
                if (!str_contains($ht, $motif)) {
                    $manque[] = $role;
                }
            }
            $manque === []
                ? $ligne('ok', 'public/.htaccess : listing coupé, signature coupée, fichiers cachés refusés')
                : $ligne('echec', 'public/.htaccess incomplet : ' . implode(', ', $manque));
        }
        foreach (['data', 'migrations', 'config', '.env'] as $sensible) {
            if (file_exists($public . '/' . $sensible)) {
                $ligne('echec', "public/$sensible existe : ce contenu serait servi par le web", 'Le placer à la racine du projet, hors de public/.');
            }
        }
    }
    $gi = is_file($racine . '/.gitignore') ? (string) file_get_contents($racine . '/.gitignore') : '';
    $ignores = array_map('trim', explode("\n", $gi));
    $manque = array_values(array_filter(['.env', '/data/'], static fn (string $m): bool => !in_array($m, $ignores, true) && !in_array(rtrim($m, '/'), $ignores, true) && !in_array(ltrim($m, '/'), $ignores, true)));
    $manque === []
        ? $ligne('ok', '.gitignore : .env et data/ ignorés')
        : $ligne('echec', '.gitignore ne contient pas : ' . implode(', ', $manque), 'Un secret ou une base versionnés se retrouvent dans chaque copie du dépôt.');
    if (is_dir($racine . '/.git')) {
        $suivis = [];
        exec('git -C ' . escapeshellarg($racine) . ' ls-files -- .env "*.sqlite" "data/*" 2>/dev/null', $suivis);
        $suivis === []
            ? $ligne('ok', 'Aucun .env, base ni fichier de data/ suivi par git')
            : $ligne('echec', 'Suivis par git : ' . implode(', ', array_slice($suivis, 0, 5)), 'git rm --cached, puis rotation des secrets concernés.');
    }
    if (is_file($racine . '/.env.example')) {
        $remplies = [];
        foreach (file($racine . '/.env.example', FILE_IGNORE_NEW_LINES) ?: [] as $l) {
            if (preg_match('/^\s*([A-Z0-9_]*(KEY|SECRET|PASSWORD|TOKEN)[A-Z0-9_]*)\s*=\s*(\S+)/', $l, $m) === 1) {
                $remplies[] = $m[1];
            }
        }
        $remplies === []
            ? $ligne('ok', '.env.example sans valeur secrète')
            : $ligne('echec', '.env.example contient une valeur pour : ' . implode(', ', $remplies), 'Laisser ces lignes vides.');
    }
    foreach (glob($racine . '/config/*.json') ?: [] as $j) {
        json_decode((string) file_get_contents($j));
        if (json_last_error() !== JSON_ERROR_NONE) {
            $ligne('echec', 'JSON invalide : ' . $relatif($j), json_last_error_msg());
        }
    }
}

// ---------------------------------------------------------------------------------------------------------------
// 4. Contraste AA de chaque thème
// ---------------------------------------------------------------------------------------------------------------
if (in_array('contraste', $sections, true)) {
    $titre('Contraste des thèmes (WCAG 2.2 AA)');
    $candidats = isset($options['tokens']) ? [(string) $options['tokens']] : ['public/assets/css/tokens.css', 'base/tokens.css', 'assets/css/tokens.css', 'src/styles/tokens.css'];
    $tokens = null;
    foreach ($candidats as $c) {
        $p = str_starts_with($c, '/') ? $c : $racine . '/' . $c;
        if (is_file($p)) {
            $tokens = $p;
            break;
        }
    }
    if ($tokens === null) {
        $ligne(isset($options['tokens']) ? 'echec' : 'avert', 'Fichier de tokens introuvable (' . implode(', ', $candidats) . ')', 'Préciser --tokens=chemin/tokens.css.');
    } else {
        echo '  Fichier : ' . (str_starts_with($tokens, $racine) ? $relatif($tokens) : $tokens) . PHP_EOL;
        $paires = [
            // Texte : 4,5:1
            ['--color-text', '--color-bg', 4.5], ['--color-text', '--color-surface', 4.5], ['--color-text', '--color-surface-2', 4.5],
            ['--color-text-muted', '--color-bg', 4.5], ['--color-text-muted', '--color-surface', 4.5], ['--color-text-muted', '--color-surface-2', 4.5],
            ['--color-accent', '--color-bg', 4.5], ['--color-accent', '--color-surface', 4.5],
            ['--color-accent-strong', '--color-bg', 4.5], ['--color-accent-strong', '--color-surface', 4.5],
            ['--color-accent-text', '--color-accent', 4.5], ['--color-accent-text', '--color-accent-strong', 4.5], ['--color-accent-text', '--color-danger', 4.5],
            ['--color-accent', '--color-accent-soft', 4.5, '--color-surface'],
            ['--color-danger', '--color-surface', 4.5], ['--color-danger', '--color-danger-soft', 4.5, '--color-surface'],
            ['--color-success', '--color-surface', 4.5], ['--color-success', '--color-success-soft', 4.5, '--color-surface'],
            ['--color-warning', '--color-surface', 4.5], ['--color-warning', '--color-warning-soft', 4.5, '--color-surface'],
            ['--color-info', '--color-surface', 4.5], ['--color-info', '--color-info-soft', 4.5, '--color-surface'],
            ['--color-indigo', '--color-surface', 4.5], ['--color-indigo', '--color-indigo-soft', 4.5, '--color-surface'],
            ['--color-text', '--color-danger-soft', 4.5, '--color-bg'],
            // Contours de contrôles, focus, séries de graphique : 3:1
            ['--color-border', '--color-bg', 3.0], ['--color-border', '--color-surface', 3.0], ['--color-border', '--color-surface-2', 3.0],
            ['--color-focus', '--color-bg', 3.0], ['--color-focus', '--color-surface', 3.0], ['--color-focus', '--color-surface-2', 3.0],
            ['--color-serie-1', '--color-surface', 3.0], ['--color-serie-2', '--color-surface', 3.0], ['--color-serie-3', '--color-surface', 3.0], ['--color-serie-4', '--color-surface', 3.0],
            ['--color-serie-1', '--color-surface-2', 3.0], ['--color-serie-2', '--color-surface-2', 3.0], ['--color-serie-3', '--color-surface-2', 3.0], ['--color-serie-4', '--color-surface-2', 3.0],
        ];
        if (isset($options['paires'])) {
            $j = json_decode((string) @file_get_contents((string) $options['paires']), true);
            if (!is_array($j)) {
                $ligne('echec', 'Fichier de paires illisible : ' . $options['paires'], 'JSON attendu : [["--texte", "--fond", 4.5], ["--avant", "--fond-translucide", 4.5, "--fond-dessous"]]');
                $j = [];
            }
            $paires = $j;
        }
        foreach (Contraste::themes((string) file_get_contents($tokens)) as $theme => $valeurs) {
            $echecs = 0;
            $ignorees = 0;
            $mesures = [];
            foreach ($paires as $p) {
                [$avant, $fond, $minimum] = [(string) $p[0], (string) $p[1], (float) $p[2]];
                $dessous = (string) ($p[3] ?? '--color-bg');
                $r = Contraste::rapport($valeurs, $avant, $fond, $dessous);
                if ($r === null) {
                    $ignorees++;
                    continue;
                }
                $mesures[] = [$avant, $fond, $minimum, $r];
                if ($r < $minimum) {
                    $echecs++;
                    $ligne('echec', sprintf('%s : %s sur %s = %s:1 (minimum %s:1)', $theme, $avant, $fond, Contraste::nombre($r), Contraste::nombre($minimum)),
                        'Corriger la valeur dans le fichier de tokens (assombrir ou éclaircir), jamais dans un composant.');
                } elseif ($detail) {
                    $ligne('ok', sprintf('%s : %s sur %s = %s:1', $theme, $avant, $fond, Contraste::nombre($r)));
                }
            }
            if ($echecs === 0 && !$detail) {
                $min = $mesures === [] ? null : min(array_map(static fn (array $m): float => $m[3] / $m[2], $mesures));
                $ligne('ok', sprintf('%s : %d paires conformes%s', $theme, count($mesures), $min !== null ? ' (marge la plus faible : ' . Contraste::nombre($min * 100, 0) . ' % du minimum)' : ''));
            }
            if ($ignorees > 0) {
                $ligne('avert', "$theme : $ignorees paire(s) ignorée(s) (token absent ou valeur non calculable, color-mix par exemple)");
            }
        }
    }
}

echo PHP_EOL . sprintf('%d réussi(s), %d avertissement(s), %d échec(s)', $compte['ok'], $compte['avert'], $compte['echec']) . PHP_EOL;
exit($compte['echec'] > 0 ? 1 : 0);

/** Lecture des tokens CSS et calcul du contraste WCAG 2. */
final class Contraste
{
    /**
     * Thèmes d'un fichier de tokens : « défaut (:root) » puis un thème par sélecteur [data-theme="x"] (qui complète :root).
     * Seuls les blocs de premier niveau sont lus (un :root dans un @media est ignoré). @return array<string, array<string, string>>
     */
    public static function themes(string $css): array
    {
        $css = (string) preg_replace('#/\*.*?\*/#s', '', $css);
        $base = [];
        $variantes = [];
        $profondeur = 0;
        $selecteur = '';
        $corps = '';
        $longueur = strlen($css);
        for ($i = 0; $i < $longueur; $i++) {
            $c = $css[$i];
            if ($c === '{') {
                $profondeur++;
                if ($profondeur === 1) {
                    $corps = '';
                    continue;
                }
            } elseif ($c === '}') {
                $profondeur--;
                if ($profondeur === 0) {
                    $sel = trim($selecteur);
                    $decl = self::declarations($corps);
                    if (preg_match('/\[data-theme\s*=\s*["\']?([\w-]+)["\']?\]/', $sel, $m) === 1) {
                        $variantes[$m[1]] = ($variantes[$m[1]] ?? []) + $decl;
                    } elseif (preg_match('/(^|,)\s*(:root|html)\s*(,|$)/', $sel) === 1) {
                        $base = $decl + $base;
                    }
                    $selecteur = '';
                    continue;
                }
            }
            if ($profondeur === 0) {
                $selecteur .= $c;
            } elseif ($profondeur === 1) {
                $corps .= $c;
            }
        }
        $themes = ['défaut (:root)' => $base];
        foreach ($variantes as $nom => $decl) {
            $themes["[data-theme=\"$nom\"]"] = $decl + $base;
        }
        return $themes;
    }

    /** @return array<string, string> */
    private static function declarations(string $corps): array
    {
        $d = [];
        preg_match_all('/(--[\w-]+)\s*:\s*([^;]+);?/', $corps, $m, PREG_SET_ORDER);
        foreach ($m as $x) {
            $d[$x[1]] = trim($x[2]);
        }
        return $d;
    }

    /** Rapport de contraste, null si un token manque ou n'est pas une couleur calculable. @param array<string, string> $v */
    public static function rapport(array $v, string $avant, string $fond, string $dessous = '--color-bg'): ?float
    {
        $f = self::couleur($v, $fond);
        if ($f === null) {
            return null;
        }
        if ($f[3] < 1) {
            $d = self::couleur($v, $dessous);
            if ($d === null || $d[3] < 1) {
                return null;
            }
            $f = self::composer($f, $d);
        }
        $a = self::couleur($v, $avant);
        if ($a === null) {
            return null;
        }
        if ($a[3] < 1) {
            $a = self::composer($a, $f);
        }
        $la = self::luminance($a);
        $lf = self::luminance($f);
        return (max($la, $lf) + 0.05) / (min($la, $lf) + 0.05);
    }

    /** @param array<string, string> $v @return array{0: float, 1: float, 2: float, 3: float}|null r, g, b de 0 à 255, alpha de 0 à 1 */
    public static function couleur(array $v, string $nom, int $profondeur = 0): ?array
    {
        if ($profondeur > 10 || !isset($v[$nom])) {
            return null;
        }
        $val = strtolower(trim($v[$nom]));
        if (preg_match('/^var\(\s*(--[\w-]+)\s*(?:,\s*(.+))?\)$/', $val, $m) === 1) {
            $r = self::couleur($v, $m[1], $profondeur + 1);
            if ($r === null && isset($m[2])) {
                return self::couleur(['--repli' => $m[2]] + $v, '--repli', $profondeur + 1);
            }
            return $r;
        }
        if (preg_match('/^#([0-9a-f]{3,8})$/', $val, $m) === 1) {
            $h = $m[1];
            if (strlen($h) === 3 || strlen($h) === 4) {
                $h = implode('', array_map(static fn (string $c): string => $c . $c, str_split($h)));
            }
            if (strlen($h) !== 6 && strlen($h) !== 8) {
                return null;
            }
            return [hexdec(substr($h, 0, 2)), hexdec(substr($h, 2, 2)), hexdec(substr($h, 4, 2)), strlen($h) === 8 ? hexdec(substr($h, 6, 2)) / 255 : 1.0];
        }
        if (preg_match('/^rgba?\(\s*([\d.]+%?)[\s,]+([\d.]+%?)[\s,]+([\d.]+%?)\s*(?:[,\/]\s*([\d.]+%?))?\s*\)$/', $val, $m) === 1) {
            $canal = static fn (string $x): float => str_ends_with($x, '%') ? (float) $x * 2.55 : (float) $x;
            $alpha = isset($m[4]) && $m[4] !== '' ? (str_ends_with($m[4], '%') ? (float) $m[4] / 100 : (float) $m[4]) : 1.0;
            return [$canal($m[1]), $canal($m[2]), $canal($m[3]), max(0.0, min(1.0, $alpha))];
        }
        $noms = ['white' => [255, 255, 255], 'black' => [0, 0, 0]];
        return isset($noms[$val]) ? [...$noms[$val], 1.0] : null;
    }

    /** Couleur translucide posée sur un fond opaque. */
    private static function composer(array $haut, array $bas): array
    {
        $a = $haut[3];
        return [$haut[0] * $a + $bas[0] * (1 - $a), $haut[1] * $a + $bas[1] * (1 - $a), $haut[2] * $a + $bas[2] * (1 - $a), 1.0];
    }

    private static function luminance(array $c): float
    {
        $lin = static function (float $x): float {
            $x /= 255;
            return $x <= 0.04045 ? $x / 12.92 : (($x + 0.055) / 1.055) ** 2.4;
        };
        return 0.2126 * $lin($c[0]) + 0.7152 * $lin($c[1]) + 0.0722 * $lin($c[2]);
    }

    public static function nombre(float $n, int $decimales = 2): string
    {
        return number_format($n, $decimales, ',', ' ');
    }
}
