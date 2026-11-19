<?php
declare(strict_types=1);

/**
 * Contrôles de App\Core\Export, sans framework : php tests/ExportTest.php [dossier]
 * CSV (BOM, séparateur, formules neutralisées), XLSX (archive, XML bien formé, types et styles des cellules),
 * noms de fichier. Avec un dossier : y laisse essai.csv et essai.xlsx pour un contrôle dans un tableur.
 * XLSX : extensions zip et dom (ou simplexml) nécessaires.
 */

require __DIR__ . '/../app/Core/Export.php';

use App\Core\Export;

$echecs = 0;
$verifier = static function (bool $ok, string $quoi) use (&$echecs): void {
    echo ($ok ? 'ok     ' : 'ÉCHEC  ') . $quoi . "\n";
    $echecs += $ok ? 0 : 1;
};

// Cellules CSV
$verifier(Export::cellule('=SOMME(A1:A9)') === "'=SOMME(A1:A9)", 'formule = neutralisée');
$verifier(Export::cellule('+33 6 00 00 00 00') === "'+33 6 00 00 00 00", 'déclencheur + neutralisé');
$verifier(Export::cellule('@SUM(1)') === "'@SUM(1)", 'déclencheur @ neutralisé');
$verifier(Export::cellule("\t=1+1") === "'\t=1+1", 'tabulation de tête neutralisée');
$verifier(Export::cellule('  =1+1') === "'  =1+1", 'formule après des espaces neutralisée');
$verifier(Export::cellule('-12,5') === '-12,5', 'nombre négatif écrit en texte gardé');
$verifier(Export::cellule('-1+1') === "'-1+1", 'calcul déguisé en nombre neutralisé');
$verifier(Export::cellule(12.5) === '12,5' && Export::cellule(3) === '3' && Export::cellule(null) === '', 'nombres à la française, null vide');
$verifier(Export::cellule(new DateTimeImmutable('2026-11-14')) === '14/11/2026', 'date en jj/mm/aaaa');

$csv = Export::csv([['Nom', 'Note'], ['Durand; Marie', '=1+1'], ['Ligne "citée"', 2.75]]);
$verifier(str_starts_with($csv, "\xEF\xBB\xBFNom;Note\r\n"), 'CSV : BOM UTF-8, point-virgule, fin de ligne CRLF');
$verifier(str_contains($csv, "\"Durand; Marie\";'=1+1\r\n") && str_contains($csv, "\"Ligne \"\"citée\"\"\";2,75"), 'CSV : guillemets et séparateur dans une cellule');

// Noms de fichier
[$ascii, $utf8] = Export::nomsDeFichier('Inscrits « fête » été 2026 / salle B', 'xlsx');
$verifier(preg_match('/^[A-Za-z0-9._-]+\.xlsx$/', $ascii) === 1, "nom ASCII sûr ($ascii)");
$verifier(!str_contains($utf8, '/') && str_contains(rawurldecode($utf8), 'été'), 'nom UTF-8 encodé pour filename*, barre oblique retirée');
$verifier(Export::nomsDeFichier("../\r\n", 'csv')[0] === 'export.csv', 'nom vide ou dangereux remplacé par export');
$verifier(Export::colonne(0) === 'A' && Export::colonne(25) === 'Z' && Export::colonne(26) === 'AA' && Export::colonne(701) === 'ZZ', 'lettres de colonnes');

// XLSX
if (!class_exists(ZipArchive::class)) {
    echo "XLSX non contrôlé : extension zip absente.\n";
    exit($echecs > 0 ? 1 : 0);
}
$lignes = (static function () {
    yield ['Participant', 'Montant (€)', 'Inscrit le', 'Places', 'Présent', 'Note'];
    yield ['Camille Robert', 12.5, '2026-11-02', 2, true, '=HYPERLINK("http://exemple.invalid")'];
    yield ['Hugo Lefèvre', 0.3, new DateTimeImmutable('2026-11-03 14:30'), 1, false, "Deux lignes\net <balises> & co"];
    yield ['Inès Moreau', 1234567.891, 'pas une date', 3, null, "Caractère de contrôle \x07 retiré"];
})();
$chemin = tempnam(sys_get_temp_dir(), 'essai');
Export::xlsxFichier(['Inscrits / novembre' => $lignes, 'Inscrits / novembre ' => [['Seule', 'ligne']]], $chemin,
    ['formats' => ['Montant (€)' => 'euros', 'Inscrit le' => 'date']]);
$zip = new ZipArchive();
$verifier($zip->open($chemin) === true, 'archive .xlsx lisible');
$attendues = ['[Content_Types].xml', '_rels/.rels', 'xl/workbook.xml', 'xl/_rels/workbook.xml.rels', 'xl/styles.xml', 'xl/worksheets/sheet1.xml', 'xl/worksheets/sheet2.xml'];
foreach ($attendues as $partie) {
    $xml = $zip->getFromName($partie);
    $ok = $xml !== false;
    if ($ok && class_exists(DOMDocument::class)) {
        $ok = (new DOMDocument())->loadXML($xml, LIBXML_NONET) === true;
    }
    $verifier($ok, "partie $partie présente et bien formée");
}
$feuille = (string) $zip->getFromName('xl/worksheets/sheet1.xml');
$classeur = (string) $zip->getFromName('xl/workbook.xml');
$verifier(str_contains($classeur, 'name="Inscrits novembre"') && str_contains($classeur, 'name="Inscrits novembre (2)"'), 'noms de feuilles nettoyés et rendus uniques');
$verifier(str_contains($feuille, '<c r="B2" s="6"><v>12.5</v></c>'), 'montant en nombre au format euros');
$verifier(str_contains($feuille, '<c r="C2" s="8"><v>46328</v></c>'), 'texte AAAA-MM-JJ devenu date (série 46328 = 02/11/2026)');
$verifier(preg_match('#<c r="C3" s="10"><v>46329\.6041666#', $feuille) === 1, 'DateTime avec heure en date et heure');
$verifier(str_contains($feuille, '<c r="C4" t="inlineStr"><is><t xml:space="preserve">pas une date</t>'), 'texte non daté laissé en texte');
$verifier(str_contains($feuille, '<c r="D2" s="2"><v>2</v></c>') && str_contains($feuille, '<c r="E2" t="b"><v>1</v></c>'), 'entier et booléen typés');
$verifier(str_contains($feuille, '<c r="A1" s="1" t="inlineStr">'), 'en-tête en gras');
$verifier(str_contains($feuille, 't="inlineStr"><is><t xml:space="preserve">=HYPERLINK(') && !str_contains($feuille, '<f>'), 'formule écrite en texte, jamais en formule');
$verifier(str_contains($feuille, '&lt;balises&gt; &amp; co') && !str_contains($feuille, "\x07"), 'texte échappé, caractère de contrôle retiré');
$verifier(str_contains($feuille, 'state="frozen"') && str_contains($feuille, '<autoFilter ref="A1:F4"/>') && str_contains($classeur, '_xlnm._FilterDatabase'), 'en-tête figé et filtre automatique');
$verifier(preg_match('#<col min="1" max="1" width="(\d+)"#', $feuille, $m) === 1 && (int) $m[1] >= 14, 'largeur de la première colonne ajustée');
$zip->close();
$verifier(str_starts_with(Export::xlsx(['Vide' => []]), "PK\x03\x04"), 'classeur vide valide');

if (isset($argv[1]) && is_dir($argv[1])) {
    copy($chemin, $argv[1] . '/essai.xlsx');
    file_put_contents($argv[1] . '/essai.csv', Export::csv([['Nom', 'Montant', 'Note'], ['Été', 12.5, '=1+1']]));
    echo "essai.xlsx et essai.csv écrits dans {$argv[1]}\n";
}
unlink($chemin);
exit($echecs > 0 ? 1 : 0);
