<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Export tableur sans dépendance : CSV pour Excel et LibreOffice, vrai classeur .xlsx (extension zip de PHP).
 *
 *   Export::telecharger('Inscrits du 14 novembre', 'xlsx', [['Nom', 'Montant (€)', 'Inscrit le'], ['Durand', 12.5, '2026-11-02']],
 *       ['formats' => ['Montant (€)' => 'euros', 'Inscrit le' => 'date']]);
 *
 * Lignes : itérable de lignes (un générateur qui lit la base convient), la première est l'en-tête.
 * Valeurs : int et float restent des nombres, bool devient VRAI ou FAUX, DateTimeInterface une date, null une cellule vide.
 *
 * CSV : UTF-8 avec BOM (sinon Excel lit « Ã© »), séparateur point-virgule (Excel français), décimales à virgule,
 * cellules qui commencent par = + - @ tabulation ou retour préfixées d'une apostrophe (injection de formules).
 * XLSX : textes en chaînes en ligne, jamais interprétés comme formules ; nombres en nombres ; dates en dates ;
 * en-tête en gras, figé et filtrable ; largeur des colonnes ajustée au contenu.
 */
final class Export
{
    public const MIME_CSV = 'text/csv; charset=UTF-8';
    public const MIME_XLSX = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    /** Formats de colonne reconnus (option « formats ») => index du style dans styles.xml (normal, gras). */
    private const STYLES = [
        'texte' => [0, 1], 'entier' => [2, 3], 'decimal' => [4, 5], 'euros' => [6, 7],
        'date' => [8, 9], 'dateheure' => [10, 11], 'pourcentage' => [12, 13],
    ];
    private const MAX_CELLULE = 32767;   // limite d'Excel par cellule
    private const MAX_LIGNES = 1048576;  // limite d'Excel par feuille

    // CSV ---------------------------------------------------------------------

    /** Valeur d'une cellule CSV, sûre pour un tableur. */
    public static function cellule(mixed $v): string
    {
        if ($v === null) {
            return '';
        }
        if (is_bool($v)) {
            return $v ? 'oui' : 'non';
        }
        if (is_int($v)) {
            return (string) $v;
        }
        if (is_float($v)) {
            return str_replace('.', ',', rtrim(rtrim(number_format($v, 10, '.', ''), '0'), '.'));
        }
        if ($v instanceof \DateTimeInterface) {
            return $v->format($v->format('His') === '000000' ? 'd/m/Y' : 'd/m/Y H:i');
        }
        $s = (string) $v;
        // Un nombre écrit en texte (« -12,5 ») reste lisible ; tout le reste qui commence par un déclencheur de formule,
        // même après des espaces, est neutralisé (Excel, LibreOffice et Google Sheets l'exécuteraient).
        if (preg_match('/^\s*[=+\-@\t\r\n]/', $s) === 1 && preg_match('/^[+-]?\d+(?:[.,]\d+)?$/', $s) !== 1) {
            return "'" . $s;
        }
        return $s;
    }

    /**
     * Écrit un CSV dans un flux ouvert (php://output pour un envoi direct, sans tout garder en mémoire).
     * @param resource $flux
     * @param iterable<iterable<mixed>> $lignes
     */
    public static function ecrireCsv($flux, iterable $lignes, string $separateur = ';'): int
    {
        fwrite($flux, "\xEF\xBB\xBF");
        $n = 0;
        foreach ($lignes as $ligne) {
            $cellules = [];
            foreach ($ligne as $v) {
                $cellules[] = self::cellule($v);
            }
            // eol "\r\n" : fin de ligne de la RFC 4180, celle qu'attend Excel
            fputcsv($flux, $cellules, $separateur, '"', '', "\r\n");
            $n++;
        }
        return $n;
    }

    /** @param iterable<iterable<mixed>> $lignes */
    public static function csv(iterable $lignes, string $separateur = ';'): string
    {
        $h = fopen('php://temp', 'w+b');
        self::ecrireCsv($h, $lignes, $separateur);
        rewind($h);
        $csv = (string) stream_get_contents($h);
        fclose($h);
        return $csv;
    }

    // XLSX --------------------------------------------------------------------

    /**
     * Classeur .xlsx en mémoire (pour un gros export, xlsxFichier() puis readfile()).
     * @param array<string, iterable<iterable<mixed>>> $feuilles nom de la feuille => lignes
     * @param array<string, mixed> $options voir xlsxFichier()
     */
    public static function xlsx(array $feuilles, array $options = []): string
    {
        $chemin = tempnam(sys_get_temp_dir(), 'xlsx');
        try {
            self::xlsxFichier($feuilles, $chemin, $options);
            return (string) file_get_contents($chemin);
        } finally {
            @unlink($chemin);
        }
    }

    /**
     * Écrit un classeur .xlsx dans $chemin. Les lignes sont parcourues une seule fois (générateur accepté).
     * Options, pour toutes les feuilles ou par feuille (['Feuille' => [...]] dans 'feuilles') :
     *  - 'formats' => [libellé d'en-tête ou index de colonne => texte|entier|decimal|euros|date|dateheure|pourcentage]
     *     (une colonne « date » transforme aussi les textes AAAA-MM-JJ en vraies dates, avec l'heure si elle est donnée) ;
     *  - 'entete' => false pour une feuille sans ligne d'en-tête (ni gras, ni volet figé, ni filtre).
     * @param array<string, iterable<iterable<mixed>>> $feuilles
     * @param array<string, mixed> $options
     */
    public static function xlsxFichier(array $feuilles, string $chemin, array $options = []): void
    {
        if (!class_exists(\ZipArchive::class)) {
            throw new \RuntimeException("L'extension PHP zip est nécessaire pour l'export .xlsx (le CSV reste possible).");
        }
        if ($feuilles === []) {
            $feuilles = ['Feuille 1' => []];
        }
        $zip = new \ZipArchive();
        if ($zip->open($chemin, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Classeur impossible à créer.');
        }
        $temporaires = [];
        self::$filtres = [];
        try {
            $noms = self::nomsDeFeuilles(array_keys($feuilles));
            $i = 0;
            foreach ($feuilles as $cle => $lignes) {
                $i++;
                $opt = array_merge($options, (array) ($options['feuilles'][$cle] ?? []));
                $temporaires[] = $fichier = self::feuille($lignes, $opt);
                $zip->addFile($fichier, "xl/worksheets/sheet$i.xml");
            }
            $zip->addFromString('[Content_Types].xml', self::typesDeContenu(count($noms)));
            $zip->addFromString('_rels/.rels', self::XML . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
                . '</Relationships>');
            $sheets = '';
            $rels = '';
            foreach ($noms as $n => $nom) {
                $id = $n + 1;
                $sheets .= '<sheet name="' . self::x($nom) . '" sheetId="' . $id . '" r:id="rId' . $id . '"/>';
                $rels .= '<Relationship Id="rId' . $id . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $id . '.xml"/>';
            }
            $zip->addFromString('xl/workbook.xml', self::XML . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
                . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>' . $sheets . '</sheets>'
                . self::nomsDefinis($noms) . '</workbook>');
            $zip->addFromString('xl/_rels/workbook.xml.rels', self::XML . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                . $rels . '<Relationship Id="rId' . (count($noms) + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
                . '</Relationships>');
            $zip->addFromString('xl/styles.xml', self::styles());
            if ($zip->close() !== true) {
                throw new \RuntimeException('Classeur impossible à enregistrer.');
            }
        } finally {
            foreach ($temporaires as $t) {
                @unlink($t);
            }
        }
    }

    private const XML = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";

    /** Le filtre automatique d'une feuille est déclaré par un nom défini caché (_xlnm._FilterDatabase). @param list<string> $noms */
    private static function nomsDefinis(array $noms): string
    {
        $defs = '';
        foreach (self::$filtres as $i => $plage) {
            if ($plage !== null) {
                $defs .= '<definedName name="_xlnm._FilterDatabase" localSheetId="' . $i . '" hidden="1">'
                    . self::x("'" . str_replace("'", "''", $noms[$i]) . "'!" . $plage) . '</definedName>';
            }
        }
        self::$filtres = [];
        return $defs !== '' ? '<definedNames>' . $defs . '</definedNames>' : '';
    }

    /** @var list<?string> plage absolue du filtre de chaque feuille écrite ($A$1:$D$40) */
    private static array $filtres = [];

    /**
     * XML d'une feuille, écrit dans un fichier temporaire : les données sont écrites au fil de l'eau,
     * puis l'en-tête de la feuille (largeurs de colonnes, connues seulement à la fin) est ajouté devant.
     * @param iterable<iterable<mixed>> $lignes
     * @param array<string, mixed> $opt
     */
    private static function feuille(iterable $lignes, array $opt): string
    {
        $avecEntete = ($opt['entete'] ?? true) !== false;
        $formatsDemandes = (array) ($opt['formats'] ?? []);
        $donnees = fopen('php://temp/maxmemory:2097152', 'w+b');
        $largeurs = [];
        $formats = [];
        $r = 0;
        $nbColonnes = 0;
        foreach ($lignes as $ligne) {
            if ($r >= self::MAX_LIGNES) {
                throw new \RuntimeException('Plus de ' . self::MAX_LIGNES . ' lignes : limite d\'une feuille Excel.');
            }
            $ligne = is_array($ligne) ? array_values($ligne) : iterator_to_array($ligne, false);
            $estEntete = $avecEntete && $r === 0;
            if ($estEntete) {
                foreach ($ligne as $c => $libelle) {
                    $formats[$c] = $formatsDemandes[(string) $libelle] ?? $formatsDemandes[$c] ?? null;
                }
            } elseif (!$avecEntete && $r === 0) {
                foreach ($formatsDemandes as $c => $f) {
                    if (is_int($c)) {
                        $formats[$c] = $f;
                    }
                }
            }
            $xml = '<row r="' . ($r + 1) . '">';
            foreach ($ligne as $c => $v) {
                [$cellule, $largeur] = self::cellXml(self::colonne($c) . ($r + 1), $v, $estEntete, $estEntete ? null : ($formats[$c] ?? null));
                $xml .= $cellule;
                $largeurs[$c] = max($largeurs[$c] ?? 0, $largeur + ($estEntete ? 2 : 0));
            }
            fwrite($donnees, $xml . '</row>');
            $nbColonnes = max($nbColonnes, count($ligne));
            $r++;
        }

        $cols = '';
        if ($largeurs !== []) {
            ksort($largeurs);
            foreach ($largeurs as $c => $l) {
                // Largeur Excel ≈ nombre de caractères ; bornée pour qu'un long texte n'écrase pas le tableau
                $cols .= '<col min="' . ($c + 1) . '" max="' . ($c + 1) . '" width="' . min(60, max(8, $l + 2)) . '" customWidth="1"/>';
            }
            $cols = '<cols>' . $cols . '</cols>';
        }
        $figer = $avecEntete && $r > 1;
        $vue = $figer
            ? '<sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/><selection pane="bottomLeft" activeCell="A2" sqref="A2"/></sheetView>'
            : '<sheetView workbookViewId="0"/>';
        $plage = $figer && $nbColonnes > 0 ? 'A1:' . self::colonne($nbColonnes - 1) . $r : null;
        self::$filtres[] = $plage !== null ? '$A$1:$' . self::colonne($nbColonnes - 1) . '$' . $r : null;

        $fichier = tempnam(sys_get_temp_dir(), 'feuille');
        $sortie = fopen($fichier, 'wb');
        fwrite($sortie, self::XML . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetViews>' . $vue . '</sheetViews><sheetFormatPr defaultRowHeight="15"/>' . $cols . '<sheetData>');
        rewind($donnees);
        stream_copy_to_stream($donnees, $sortie);
        fclose($donnees);
        fwrite($sortie, '</sheetData>' . ($plage !== null ? '<autoFilter ref="' . $plage . '"/>' : '') . '</worksheet>');
        fclose($sortie);
        return $fichier;
    }

    /** @return array{0: string, 1: int} XML de la cellule et largeur approximative en caractères */
    private static function cellXml(string $ref, mixed $v, bool $gras, ?string $format): array
    {
        if ($v === null || $v === '') {
            return ['', 0];
        }
        $style = static fn (string $f): string => ($s = self::STYLES[$f][$gras ? 1 : 0] ?? 0) > 0 ? ' s="' . $s . '"' : '';
        if ($format === 'date' || $format === 'dateheure') {
            $serie = $v instanceof \DateTimeInterface ? self::serieDate($v) : (is_string($v) ? self::serieTexte($v) : null);
            if ($serie !== null) {
                $f = $format === 'dateheure' || floor($serie) != $serie ? 'dateheure' : 'date';
                return ['<c r="' . $ref . '"' . $style($f) . '><v>' . self::nombre($serie) . '</v></c>', $f === 'date' ? 10 : 16];
            }
        } elseif ($v instanceof \DateTimeInterface) {
            $serie = self::serieDate($v);
            $f = floor($serie) != $serie ? 'dateheure' : 'date';
            return ['<c r="' . $ref . '"' . $style($f) . '><v>' . self::nombre($serie) . '</v></c>', $f === 'date' ? 10 : 16];
        }
        if (is_bool($v)) {
            return ['<c r="' . $ref . '"' . $style('texte') . ' t="b"><v>' . ($v ? 1 : 0) . '</v></c>', 5];
        }
        if ((is_int($v) || is_float($v)) && is_finite((float) $v)) {
            $f = $format ?? (is_int($v) || floor($v) == $v ? 'entier' : 'decimal');
            if (!isset(self::STYLES[$f]) || $f === 'texte' || $f === 'date' || $f === 'dateheure') {
                $f = is_int($v) ? 'entier' : 'decimal';
            }
            $largeur = strlen(number_format(abs((float) $v), $f === 'entier' ? 0 : 2, ',', ' ')) + ($f === 'euros' ? 3 : 1);
            return ['<c r="' . $ref . '"' . $style($f) . '><v>' . self::nombre((float) $v) . '</v></c>', $largeur];
        }
        $texte = (string) (is_float($v) ? '' : $v);
        if (preg_match('//u', $texte) !== 1) {
            $texte = mb_convert_encoding($texte, 'UTF-8', 'Windows-1252'); // octets invalides : jamais un XML cassé
        }
        $texte = mb_substr($texte, 0, self::MAX_CELLULE);
        $largeur = 0;
        foreach (explode("\n", $texte) as $l) {
            $largeur = max($largeur, mb_strlen($l));
        }
        return ['<c r="' . $ref . '"' . $style('texte') . ' t="inlineStr"><is><t xml:space="preserve">' . self::x($texte) . '</t></is></c>', $largeur];
    }

    /** Nombre écrit pour le XML (point décimal, sans exposant pour les valeurs courantes). */
    private static function nombre(float $v): string
    {
        if (floor($v) == $v && abs($v) < 1e15) {
            return (string) (int) $v;
        }
        $s = sprintf('%.15G', $v); // 15 chiffres significatifs, comme les tableurs : 0,1 + 0,2 s'écrit 0.3
        return str_contains($s, 'E') ? rtrim(rtrim(sprintf('%.10F', $v), '0'), '.') : $s;
    }

    /** Numéro de série Excel : jours depuis le 30/12/1899 (heure en fraction de jour), dans le fuseau de la date. */
    public static function serieDate(\DateTimeInterface $d): float
    {
        $jour = gmmktime(0, 0, 0, (int) $d->format('n'), (int) $d->format('j'), (int) $d->format('Y'));
        $secondes = (int) $d->format('G') * 3600 + (int) $d->format('i') * 60 + (int) $d->format('s');
        return ($jour - gmmktime(0, 0, 0, 12, 30, 1899)) / 86400 + $secondes / 86400;
    }

    /** « 2026-03-31 » ou « 2026-03-31 14:30[:00] » vers un numéro de série, sinon null. */
    public static function serieTexte(string $s): ?float
    {
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2})(?::(\d{2}))?)?$/', trim($s), $m) !== 1
            || !checkdate((int) $m[2], (int) $m[3], (int) $m[1]) || (int) ($m[4] ?? 0) > 23 || (int) ($m[5] ?? 0) > 59) {
            return null;
        }
        $d = new \DateTimeImmutable(sprintf('%s-%s-%s %02d:%02d:%02d', $m[1], $m[2], $m[3], (int) ($m[4] ?? 0), (int) ($m[5] ?? 0), (int) ($m[6] ?? 0)));
        return self::serieDate($d);
    }

    /** 0 => A, 25 => Z, 26 => AA. */
    public static function colonne(int $i): string
    {
        $s = '';
        for ($i++; $i > 0; $i = intdiv($i - 1, 26)) {
            $s = chr(65 + ($i - 1) % 26) . $s;
        }
        return $s;
    }

    /** Noms de feuilles valides pour Excel : 31 caractères, sans \ / ? * [ ] :, ni apostrophe au bord, uniques. @param list<int|string> $cles @return list<string> */
    private static function nomsDeFeuilles(array $cles): array
    {
        $noms = [];
        foreach ($cles as $i => $cle) {
            $nom = trim((string) preg_replace('/\s+/u', ' ', str_replace(['\\', '/', '?', '*', '[', ']', ':'], ' ', (string) $cle)), " '");
            $nom = mb_substr($nom !== '' ? $nom : 'Feuille ' . ($i + 1), 0, 31);
            $base = $nom;
            for ($n = 2; in_array(mb_strtolower($nom), array_map('mb_strtolower', $noms), true); $n++) {
                $nom = mb_substr($base, 0, 31 - strlen(" ($n)")) . " ($n)";
            }
            $noms[] = $nom;
        }
        return $noms;
    }

    private static function typesDeContenu(int $nbFeuilles): string
    {
        $feuilles = '';
        for ($i = 1; $i <= $nbFeuilles; $i++) {
            $feuilles .= '<Override PartName="/xl/worksheets/sheet' . $i . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }
        return self::XML . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . $feuilles . '</Types>';
    }

    /**
     * Styles : police normale et grasse ; formats texte, entier (# ##0), décimal (# ##0,00), euros, date jj/mm/aaaa,
     * date et heure, pourcentage. Codes écrits à l'anglaise : le tableur les affiche selon la langue du poste.
     */
    private static function styles(): string
    {
        $xf = static fn (int $fmt, int $police): string => '<xf numFmtId="' . $fmt . '" fontId="' . $police . '" fillId="0" borderId="0" xfId="0"'
            . ($fmt > 0 ? ' applyNumberFormat="1"' : '') . ($police > 0 ? ' applyFont="1"' : '') . '/>';
        $cellXfs = '';
        foreach ([0, 3, 4, 164, 165, 166, 10] as $fmt) {
            $cellXfs .= $xf($fmt, 0) . $xf($fmt, 1);
        }
        return self::XML . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="3"><numFmt numFmtId="164" formatCode="#,##0.00\ &quot;€&quot;"/>'
            . '<numFmt numFmtId="165" formatCode="dd/mm/yyyy"/><numFmt numFmtId="166" formatCode="dd/mm/yyyy\ hh:mm"/></numFmts>'
            . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/><family val="2"/></font><font><b/><sz val="11"/><name val="Calibri"/><family val="2"/></font></fonts>'
            . '<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="14">' . $cellXfs . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }

    /** Texte pour le XML : caractères de contrôle interdits retirés, puis échappé. */
    private static function x(string $s): string
    {
        return htmlspecialchars((string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x{FFFE}\x{FFFF}]/u', '', $s), ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    // Téléchargement ----------------------------------------------------------

    /**
     * Noms du fichier pour Content-Disposition : [ASCII sûr pour filename, UTF-8 encodé pour filename*].
     * @return array{0: string, 1: string}
     */
    public static function nomsDeFichier(string $base, string $extension): array
    {
        $base = trim((string) preg_replace('/[\x00-\x1F\x7F"\\\\\/:*?<>|]+/u', ' ', $base));
        $base = mb_substr($base !== '' ? $base : 'export', 0, 100);
        $ascii = class_exists(\Transliterator::class)
            ? (string) \Transliterator::create('Any-Latin; Latin-ASCII')?->transliterate($base)
            : (string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $base);
        $ascii = trim((string) preg_replace('/[^A-Za-z0-9._-]+/', '-', $ascii), '-.');
        $extension = strtolower((string) preg_replace('/[^a-z0-9]/i', '', $extension));
        return [($ascii !== '' ? $ascii : 'export') . '.' . $extension, rawurlencode($base . '.' . $extension)];
    }

    /** En-têtes d'un téléchargement : pièce jointe, nom ASCII et UTF-8 (RFC 6266), pas de devinette de type, pas de cache. */
    public static function entetes(string $nomBase, string $extension, string $type, ?int $taille = null): void
    {
        [$ascii, $utf8] = self::nomsDeFichier($nomBase, $extension);
        header('Content-Type: ' . $type);
        header('Content-Disposition: attachment; filename="' . $ascii . '"; filename*=UTF-8\'\'' . $utf8);
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');
        if ($taille !== null) {
            header('Content-Length: ' . $taille);
        }
    }

    /**
     * Envoie l'export au navigateur. $format : csv ou xlsx (tout autre valeur : csv).
     * CSV envoyé au fil de l'eau ; XLSX écrit dans un fichier temporaire puis envoyé avec sa taille.
     * @param iterable<iterable<mixed>> $lignes
     * @param array<string, mixed> $options options de xlsxFichier()
     */
    public static function telecharger(string $nomBase, string $format, iterable $lignes, array $options = [], string $feuille = 'Export'): void
    {
        if ($format === 'xlsx') {
            $chemin = tempnam(sys_get_temp_dir(), 'xlsx');
            try {
                self::xlsxFichier([$feuille => $lignes], $chemin, $options);
                self::entetes($nomBase, 'xlsx', self::MIME_XLSX, (int) filesize($chemin));
                readfile($chemin);
            } finally {
                @unlink($chemin);
            }
            return;
        }
        self::entetes($nomBase, 'csv', self::MIME_CSV);
        $sortie = fopen('php://output', 'wb');
        self::ecrireCsv($sortie, $lignes);
        fclose($sortie);
    }
}
