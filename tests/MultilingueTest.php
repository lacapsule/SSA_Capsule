<?php
declare(strict_types=1);

/**
 * Contrôles du module multilingue, sans framework, dans un projet monté sur gabarits/socle-php :
 *   php tests/MultilingueTest.php
 * Base SQLite jetable (migrations du projet appliquées), catalogues d'essai écrits dans un dossier temporaire :
 * le test ne dépend ni de la base de développement ni des vrais textes du site.
 */

$tmp = sys_get_temp_dir() . '/multilingue-test-' . bin2hex(random_bytes(4));
mkdir($tmp . '/lang', 0700, true);
$_ENV['APP_ENV'] = 'test';
$_ENV['APP_URL'] = 'https://exemple.org';
$_ENV['DB_DSN'] = 'sqlite:' . $tmp . '/test.sqlite';
putenv('DB_DSN=' . $_ENV['DB_DSN']);
require __DIR__ . '/../app/bootstrap.php';

use App\Core\Migrator;
use App\Langue\Contenu;
use App\Langue\Langue;
use App\Langue\Pluriel;
use App\Langue\Sitemap;
use App\Langue\T;

$echecs = 0;
$verifier = static function (bool $ok, string $quoi) use (&$echecs): void {
    echo ($ok ? 'ok     ' : 'ÉCHEC  ') . $quoi . "\n";
    $echecs += $ok ? 0 : 1;
};

Migrator::run();
Langue::configurer([
    'defaut' => 'fr',
    'langues' => [
        'fr' => ['nom' => 'Français', 'locale' => 'fr_FR'],
        'en' => ['nom' => 'English', 'locale' => 'en_GB'],
        'ar' => ['nom' => 'العربية', 'locale' => 'ar_AR', 'dir' => 'rtl'],
    ],
    'chemins' => ['/a-propos/' => ['en' => '/about/'], '/actualites/' => ['en' => '/news/']],
    'proposition' => true,
]);
file_put_contents($tmp . '/lang/fr.php', '<?php return ' . var_export([
    'menu.contact' => 'Contact',
    'menu.accueil' => 'Accueil',
    'seulement.fr' => 'Texte seulement en français',
    'bonjour' => 'Bonjour {nom}',
    'fichiers' => ['one' => '{n} fichier', 'other' => '{n} fichiers'],
    'resultats' => ['=0' => 'Aucun résultat', 'one' => '{n} résultat', 'other' => '{n} résultats'],
    'repli.pluriel' => ['one' => '{n} élément', 'other' => '{n} éléments'],
], true) . ';');
file_put_contents($tmp . '/lang/en.php', '<?php return ' . var_export([
    'menu.contact' => 'Contact us',
    'menu.accueil' => 'Home',
    'bonjour' => 'Hello {nom}',
    'fichiers' => ['one' => '{n} file', 'other' => '{n} files'],
    'resultats' => ['=0' => 'No results', 'one' => '{n} result', 'other' => '{n} results'],
], true) . ';');
T::dossier($tmp . '/lang');

// Textes d'interface : repli et variables
Langue::definir('en');
$verifier(T::t('menu.contact') === 'Contact us', 'clé traduite');
$verifier(T::t('seulement.fr') === 'Texte seulement en français', 'clé absente en anglais : repli sur le français');
$verifier(T::t('cle.inconnue') === 'cle.inconnue', 'clé inconnue partout : la clé elle-même, sans erreur');
$verifier(in_array('seulement.fr', T::manquantes(), true), 'clé manquante relevée pendant la requête');
$verifier(T::t('bonjour', ['nom' => 'Camille']) === 'Hello Camille', 'variable remplie');
$verifier(T::t('bonjour', ['nom' => '<b>']) === 'Hello <b>', 'variable rendue en texte brut (échappement par e() à l\'affichage)');
$verifier(T::t('menu.contact', [], 'fr') === 'Contact', 'langue explicite');

// Pluriels
$verifier(T::n('fichiers', 0, [], 'fr') === '0 fichier' && T::n('fichiers', 1, [], 'fr') === '1 fichier' && T::n('fichiers', 2, [], 'fr') === '2 fichiers', 'français : 0 et 1 au singulier');
$verifier(T::n('fichiers', 0) === '0 files' && T::n('fichiers', 1) === '1 file' && T::n('fichiers', 2) === '2 files', 'anglais : 0 au pluriel');
$verifier(T::n('fichiers', 1.5, [], 'fr') === '1,5 fichier' && T::n('fichiers', 1.5) === '1.5 files', 'décimales : 1,5 fichier, 1.5 files');
$verifier(T::n('resultats', 0) === 'No results' && T::n('resultats', 0, [], 'fr') === 'Aucun résultat', 'valeur exacte =0');
$verifier(T::n('repli.pluriel', 0) === '0 élément', 'repli sur le français avec les règles du français (0 au singulier)');
$verifier(T::n('fichiers', 1234, [], 'fr') === "1\u{202F}234 fichiers" && T::n('fichiers', 1234) === '1,234 files', 'nombre écrit à la façon de la langue');
$ar = array_map(static fn (int $n): string => Pluriel::categorie('ar', $n), [0, 1, 2, 3, 10, 11, 99, 100, 102]);
$verifier($ar === ['zero', 'one', 'two', 'few', 'few', 'many', 'many', 'other', 'other'], 'arabe : six catégories CLDR');
$ru = array_map(static fn (int $n): string => Pluriel::categorie('ru', $n), [1, 2, 5, 11, 21, 22, 25]);
$verifier($ru === ['one', 'few', 'many', 'many', 'one', 'few', 'many'], 'russe : one, few, many');
$verifier(Pluriel::requises('fr') === ['one', 'other'] && Pluriel::requises('ja') === ['other'], 'formes exigées par langue');

// Adresses : langue portée par l'URL
$a = Langue::analyser('/en/about/');
$verifier($a === ['langue' => 'en', 'chemin' => '/a-propos/', 'redirection' => null], '/en/about/ : anglais, chemin interne /a-propos/');
$verifier(Langue::analyser('/a-propos/') === ['langue' => 'fr', 'chemin' => '/a-propos/', 'redirection' => null], 'français à la racine');
$verifier(Langue::analyser('/fr/contact/')['redirection'] === '/contact/' && Langue::analyser('/fr')['redirection'] === '/', '/fr/... redirigé vers la racine (une seule adresse)');
$verifier(Langue::analyser('/en')['redirection'] === '/en/', '/en redirigé vers /en/');
$verifier(Langue::analyser('/EN/about/')['redirection'] === '/en/about/', 'préfixe en majuscules redirigé');
$verifier(Langue::analyser('/en/a-propos/')['redirection'] === '/en/about/', 'chemin français sous /en/ redirigé vers l\'adresse traduite');
$verifier(Langue::analyser('/en/news/12/')['chemin'] === '/actualites/12/', 'préfixe traduit : /en/news/12/ vers /actualites/12/');
$verifier(Langue::analyser('/es/page/')['langue'] === 'fr' && Langue::analyser('/es/page/')['chemin'] === '/es/page/', 'langue non déclarée : chemin ordinaire');
$verifier(Langue::url('/actualites/12/?page=2#haut', 'en') === '/en/news/12/?page=2#haut' && Langue::url('/', 'en') === '/en/' && Langue::url('/contact/', 'fr') === '/contact/', 'url() : préfixe, traduction, requête et ancre gardées');
$verifier(Langue::url('https://autre.exemple/', 'en') === 'https://autre.exemple/' && Langue::url('//autre.exemple/', 'en') === '//autre.exemple/', 'url() ne touche pas une adresse extérieure');
$allerRetour = true;
foreach (['/', '/a-propos/', '/actualites/', '/actualites/3/', '/contact/', '/fichier.pdf'] as $p) {
    foreach (['fr', 'en', 'ar'] as $l) {
        $lu = Langue::analyser(Langue::url($p, $l));
        $allerRetour = $allerRetour && $lu === ['langue' => $l, 'chemin' => $p, 'redirection' => null];
    }
}
$verifier($allerRetour, 'aller-retour url() puis analyser() sur 18 couples page et langue');

// hreflang réciproques
$pages = ['/a-propos/' => ['fr', 'en', 'ar'], '/contact/' => ['fr', 'en'], '/actualites/3/' => ['fr', 'ar'], '/mentions/' => ['fr']];
$reciproques = true;
foreach ($pages as $chemin => $dispo) {
    $ensembles = [];
    foreach ($dispo as $l) {
        Langue::definir($l);
        $alt = Langue::alternatives($chemin, $dispo);
        $ensembles[] = $alt;
        $propre = Langue::absolue($chemin);
        $hrefs = array_column($alt, 'href');
        $reciproques = $reciproques && (count($dispo) === 1 ? $alt === [] : in_array($propre, $hrefs, true));
    }
    $reciproques = $reciproques && count(array_unique(array_map('serialize', $ensembles))) === 1;
}
$verifier($reciproques, 'hreflang : même ensemble depuis chaque version, chaque version s\'y trouve, aucun pour une page unilingue');
Langue::definir('en');
$alt = Langue::alternatives('/a-propos/', ['fr', 'en']);
$verifier(array_column($alt, 'href', 'hreflang') === ['fr' => 'https://exemple.org/a-propos/', 'en' => 'https://exemple.org/en/about/', 'x-default' => 'https://exemple.org/a-propos/'], 'hreflang absolus, x-default vers le français');
$verifier(array_column(Langue::alternatives('/x/', ['en', 'ar']), 'href', 'hreflang')['x-default'] === 'https://exemple.org/en/x/', 'x-default : première langue disponible si la page n\'existe pas en français');

// Partiels
ob_start();
partial('langue/head', ['chemin' => '/a-propos/', 'disponibles' => ['fr', 'en']]);
$head = (string) ob_get_clean();
$verifier(str_contains($head, '<link rel="canonical" href="https://exemple.org/en/about/">') && substr_count($head, 'hreflang=') === 3 && str_contains($head, 'og:locale" content="en_GB"') && str_contains($head, 'og:locale:alternate" content="fr_FR"'), 'head : canonical de la version anglaise, 3 hreflang, og:locale');
Langue::definir('ar');
ob_start();
partial('langue/head', ['chemin' => '/a-propos/', 'disponibles' => ['fr', 'en']]);
$head = (string) ob_get_clean();
$verifier(str_contains($head, 'noindex') && !str_contains($head, 'canonical') && !str_contains($head, 'hreflang'), 'head : page non traduite dans la langue affichée en noindex, sans canonical');
Langue::definir('en');
ob_start();
partial('langue/selecteur', ['chemin' => '/contact/', 'disponibles' => ['fr', 'en'], 'variante' => 'liste']);
$sel = (string) ob_get_clean();
$verifier(str_contains($sel, 'href="/contact/" hreflang="fr" lang="fr"') && str_contains($sel, 'href="/en/contact/" hreflang="en" lang="en" aria-current="true"') && str_contains($sel, 'href="/ar/" hreflang="ar" lang="ar"'), 'sélecteur : page équivalente, langue courante marquée, accueil de la langue si la page n\'y existe pas');
$verifier(!str_contains($sel, '<img') && str_contains($sel, '>العربية<'), 'sélecteur : noms dans leur langue, aucun drapeau');

// Proposition discrète
Langue::definir('fr');
$verifier(Langue::proposition('en-GB,en;q=0.9,fr;q=0.8', ['fr', 'en']) === 'en', 'navigateur anglais sur page française traduite : anglais proposé');
$verifier(Langue::proposition('en-GB,en;q=0.9', ['fr']) === null, 'pas de proposition vers une langue où la page n\'existe pas');
$verifier(Langue::proposition('fr-FR,fr;q=0.9,en;q=0.8', ['fr', 'en']) === null, 'navigateur déjà dans la langue affichée : rien');
$verifier(Langue::proposition('de;q=0.9,en;q=0.5', ['fr', 'en']) === 'en' && Langue::proposition('*', null) === null && Langue::proposition('<script>', null) === null, 'langue non déclarée ignorée, « * » et valeur malformée sans effet');

// Contenu en base
Contenu::source('article', '7', 'titre', 'Rentrée des ateliers');
Contenu::source('article', '7', 'resume', 'Les inscriptions ouvrent lundi.');
Contenu::enregistrer('article', '7', 'titre', 'en', 'Workshops are back', 'Rentrée des ateliers');
$ligne = ['id' => 7, 'titre' => 'Rentrée des ateliers', 'resume' => 'Les inscriptions ouvrent lundi.'];
$t = Contenu::traduire('article', '7', $ligne, 'en');
$verifier($t['titre'] === 'Workshops are back' && $t['resume'] === 'Les inscriptions ouvrent lundi.', 'surcharge par champ : titre traduit, résumé resté en français');
$verifier(Contenu::langueDe($t, 'titre') === 'en' && Contenu::langueDe($t, 'resume') === 'fr', 'langue réelle de chaque champ (lang="fr" à poser sur le résumé)');
$verifier(Contenu::traduire('article', '7', $ligne, 'fr')['titre'] === 'Rentrée des ateliers', 'langue par défaut : texte d\'origine');
$liste = Contenu::traduireListe('article', [$ligne, ['id' => 8, 'titre' => 'Assemblée générale', 'resume' => '']], 'id', 'en');
$verifier($liste[0]['titre'] === 'Workshops are back' && $liste[1]['titre'] === 'Assemblée générale', 'liste traduite en une requête, repli par ligne');
$verifier(Contenu::disponibles('article', '7') === ['fr', 'en'] && Contenu::disponibles('article', '8') === ['fr'], 'langues disponibles d\'après le champ requis');
$verifier(Contenu::etat('article', '7', 'titre', 'en') === 'a-jour' && Contenu::etat('article', '7', 'resume', 'en') === 'manquante', 'état : à jour, manquante');
Contenu::source('article', '7', 'titre', 'Rentrée des ateliers d\'automne');
$verifier(Contenu::etat('article', '7', 'titre', 'en') === 'perimee', 'texte d\'origine modifié : traduction périmée');
$revoir = Contenu::aRevoir(['en']);
$verifier(count($revoir) === 2 && $revoir[0]['etat'] === 'manquante' && $revoir[1]['etat'] === 'perimee', 'liste à revoir : résumé manquant, titre périmé');
Contenu::enregistrer('article', '7', 'titre', 'en', '   ', 'x');
$verifier(Contenu::traduire('article', '7', $ligne, 'en')['titre'] === 'Rentrée des ateliers', 'traduction vidée : retour au texte d\'origine');
$refus = false;
try {
    Contenu::enregistrer('article', '7', 'titre', 'fr', 'x', 'x');
} catch (InvalidArgumentException) {
    $refus = true;
}
$verifier($refus, 'traduction dans la langue par défaut refusée');
Contenu::supprimer('article', '7');
$verifier(Contenu::aRevoir() === [], 'contenu supprimé : traductions effacées');

// sitemap multilingue
$xml = Sitemap::xml([
    ['chemin' => '/a-propos/', 'langues' => ['fr', 'en'], 'modifie' => '2026-10-20 10:00:00'],
    ['chemin' => '/mentions/', 'langues' => ['fr'], 'modifie' => null],
]);
$verifier(substr_count($xml, '<url>') === 3 && substr_count($xml, '</url>') === 3, 'sitemap : une entrée par version (2 + 1)');
$verifier(substr_count($xml, '<xhtml:link') === 6 && str_contains($xml, '<loc>https://exemple.org/en/about/</loc>') && str_contains($xml, 'hreflang="x-default" href="https://exemple.org/a-propos/"'), 'sitemap : chaque version liste toutes les versions et x-default');
$verifier(str_contains($xml, 'xmlns:xhtml="http://www.w3.org/1999/xhtml"') && str_contains($xml, '<lastmod>2026-10-20</lastmod>'), 'sitemap : espace de noms xhtml, lastmod');
$xmlEchappe = Sitemap::xml([['chemin' => '/a?b=1&c=<2>', 'langues' => ['fr'], 'modifie' => null]]);
$verifier(str_contains($xmlEchappe, '/a?b=1&amp;c=&lt;2&gt;') && !str_contains($xmlEchappe, '&c='), 'sitemap : adresses échappées pour le XML');

Langue::definir('ar');
$verifier(Langue::dir() === 'rtl' && Langue::dir('fr') === 'ltr', 'sens d\'écriture par langue (dir="rtl")');

array_map('unlink', glob($tmp . '/lang/*') ?: []);
rmdir($tmp . '/lang');
array_map('unlink', glob($tmp . '/*') ?: []);
rmdir($tmp);
echo $echecs === 0 ? "\nTout est bon.\n" : "\n$echecs échec(s).\n";
exit($echecs === 0 ? 0 : 1);
