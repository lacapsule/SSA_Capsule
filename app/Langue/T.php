<?php
declare(strict_types=1);

namespace App\Langue;

/**
 * Textes d'interface par clés (lang/<code>.php : tableau « clé => texte »), avec repli sur la langue par défaut,
 * puis sur la clé elle-même : une traduction manquante ne casse jamais la page.
 *
 *   T::t('menu.contact')                              « Contact »
 *   T::t('page.bonjour', ['nom' => $prenom])           « Bonjour {nom} » rempli (à échapper ensuite par e())
 *   T::n('recherche.resultats', $total)                pluriel de la langue : ['one' => '{n} résultat', 'other' => '{n} résultats']
 *
 * Un pluriel peut aussi prévoir une valeur exacte : ['=0' => 'Aucun résultat', 'one' => ..., 'other' => ...].
 * Le repli sur la langue par défaut applique les règles de pluriel de cette langue-là, pas celles de la page.
 * Les valeurs renvoyées sont du texte brut : toujours e() à l'affichage.
 */
final class T
{
    /** @var array<string, array<string, string|array<string, string>>> */
    private static array $catalogues = [];
    private static ?string $dossier = null;
    /** @var array<string, true> clés absentes de la langue demandée pendant la requête (aide au développement) */
    private static array $manquantes = [];

    /** Dossier des catalogues (défaut : ROOT/lang). Vide le cache. */
    public static function dossier(string $dossier): void
    {
        self::$dossier = rtrim($dossier, '/');
        self::$catalogues = [];
        self::$manquantes = [];
    }

    /** @return array<string, string|array<string, string>> */
    public static function catalogue(string $langue): array
    {
        if (isset(self::$catalogues[$langue])) {
            return self::$catalogues[$langue];
        }
        if (preg_match('/^[a-z]{2,3}(-[a-z0-9]{2,8})?$/', $langue) !== 1) {
            return [];
        }
        $fichier = (self::$dossier ?? (defined('ROOT') ? ROOT . '/lang' : 'lang')) . '/' . $langue . '.php';
        $donnees = is_file($fichier) ? require $fichier : [];
        return self::$catalogues[$langue] = is_array($donnees) ? $donnees : [];
    }

    /** @param array<string, string|int|float> $params */
    public static function t(string $cle, array $params = [], ?string $langue = null): string
    {
        [$valeur, $langueTrouvee] = self::chercher($cle, $langue ?? Langue::actuelle());
        if (is_array($valeur)) {
            $valeur = (string) ($valeur['other'] ?? reset($valeur));
        }
        return self::remplir((string) $valeur, $params, $langueTrouvee);
    }

    /** Texte au pluriel : {n} reçoit le nombre écrit à la façon de la langue. @param array<string, string|int|float> $params */
    public static function n(string $cle, int|float $nombre, array $params = [], ?string $langue = null): string
    {
        [$valeur, $langueTrouvee] = self::chercher($cle, $langue ?? Langue::actuelle());
        if (is_array($valeur)) {
            $exacte = '=' . (is_int($nombre) || (float) (int) $nombre === (float) $nombre ? (string) (int) $nombre : (string) $nombre);
            $valeur = (string) ($valeur[$exacte] ?? $valeur[Pluriel::categorie($langueTrouvee, $nombre)] ?? $valeur['other'] ?? reset($valeur));
        }
        return self::remplir((string) $valeur, $params + ['n' => self::nombre($nombre, $langueTrouvee)], $langueTrouvee);
    }

    /** Vrai si la clé existe dans cette langue (sans repli). */
    public static function a(string $cle, ?string $langue = null): bool
    {
        return array_key_exists($cle, self::catalogue($langue ?? Langue::actuelle()));
    }

    /** Clés demandées pendant la requête et absentes de la langue de la page. @return list<string> */
    public static function manquantes(): array
    {
        return array_keys(self::$manquantes);
    }

    /** Nombre écrit selon la langue : « 1 234,5 » en français (espace fine insécable), « 1,234.5 » en anglais. */
    public static function nombre(int|float $nombre, ?string $langue = null): string
    {
        $langue ??= Langue::actuelle();
        if (class_exists(\NumberFormatter::class)) {
            $f = new \NumberFormatter(Langue::existe($langue) ? Langue::locale($langue) : $langue, \NumberFormatter::DECIMAL);
            $texte = $f->format($nombre);
            if (is_string($texte)) {
                return $texte;
            }
        }
        $decimales = is_float($nombre) && (float) (int) $nombre !== $nombre ? min(3, strlen(rtrim(substr((string) $nombre, strpos((string) $nombre, '.') + 1), '0'))) : 0;
        return in_array(explode('-', $langue)[0], ['fr', 'de', 'es', 'it', 'pt', 'nl', 'pl', 'ru', 'uk', 'cs', 'sk'], true)
            ? number_format((float) $nombre, $decimales, ',', "\u{202F}")
            : number_format((float) $nombre, $decimales, '.', ',');
    }

    /** @return array{0: string|array<string, string>, 1: string} valeur et langue où elle a été trouvée */
    private static function chercher(string $cle, string $langue): array
    {
        $catalogue = self::catalogue($langue);
        if (array_key_exists($cle, $catalogue)) {
            return [$catalogue[$cle], $langue];
        }
        self::$manquantes[$cle] = true;
        $defaut = Langue::defaut();
        if ($langue !== $defaut && array_key_exists($cle, self::catalogue($defaut))) {
            return [self::catalogue($defaut)[$cle], $defaut];
        }
        return [$cle, $langue];
    }

    /** @param array<string, string|int|float> $params */
    private static function remplir(string $texte, array $params, string $langue): string
    {
        if ($params === []) {
            return $texte;
        }
        $remplacements = [];
        foreach ($params as $nom => $valeur) {
            $remplacements['{' . $nom . '}'] = is_int($valeur) || is_float($valeur) ? self::nombre($valeur, $langue) : (string) $valeur;
        }
        return strtr($texte, $remplacements);
    }
}
