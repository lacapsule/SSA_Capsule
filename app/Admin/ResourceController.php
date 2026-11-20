<?php
declare(strict_types=1);

namespace App\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Db;
use App\Core\HttpException;
use App\Core\Session;
use App\Core\Upload;
use App\Core\View;

/**
 * Liste, création, modification, ordre et suppression des fiches déclarées dans config/ressources.php.
 * Accès : administrateurs ; éditeurs sans périmètre ; éditeurs avec périmètre pour les ressources « partage »
 * ou dont la colonne « perimetre » vaut le leur (champ imposé, invisible dans leur formulaire).
 */
final class ResourceController
{
    /** Phrase ajoutée au message d'enregistrement quand l'adresse vient d'être placée sur la carte. */
    private static string $geoNote = '';

    /** Suggestions d'adresses pour le champ « address » (liste déroulante, public/assets/js/adresses.js). */
    public static function addresses(): void
    {
        Auth::require();
        $res = Resources::get((string) ($_GET['ressource'] ?? ''));
        $centre = $res['geocode']['centre'] ?? null;
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: private, max-age=300');
        echo json_encode(Geocoder::suggest((string) ($_GET['q'] ?? ''), is_array($centre) ? $centre : null), JSON_UNESCAPED_UNICODE);
    }

    public static function index(string $key): void
    {
        [$res] = self::authorize($key);
        [$where, $params] = self::fixedWhere($res);
        $rows = Db::run("SELECT * FROM {$res['table']}" . ($where !== '' ? " WHERE $where" : '') . " ORDER BY {$res['order']}", $params)->fetchAll();
        View::render(isset($res['tabs']) ? 'admin/resource/tabs' : 'admin/resource/index', [
            'title' => $res['label'],
            'key' => $key,
            'res' => $res,
            'rows' => $rows,
        ], 'admin/layout');
    }

    public static function create(string $key): void
    {
        [$res] = self::authorize($key);
        $defaults = array_map(static fn (array $f): mixed => $f['default'] ?? '', $res['fields']);
        // « Ajouter » depuis un groupe ou un onglet : le choix est déjà fait.
        foreach ($res['fields'] as $name => $f) {
            $wanted = $_GET[$name] ?? null;
            if ($f['type'] === 'select' && is_string($wanted) && isset($f['options'][$wanted])) {
                $defaults[$name] = $wanted;
            }
        }
        self::form($key, $res, null, $defaults, []);
    }

    public static function store(string $key): void
    {
        [$res] = self::authorize($key);
        Csrf::check();
        [$values, $errors] = self::input($res, null);
        if ($errors !== []) {
            self::form($key, $res, null, $values, $errors);
            return;
        }
        $values += self::geocode($res, $values, null);
        // created : la table a une colonne created_at obligatoire, remplie à la création (ajout du projet SSA).
        if (!empty($res['created'])) {
            $values['created_at'] = now();
        }
        if (isset($res['sortable'])) {
            [$scope, $params] = self::scope($res, $values);
            $values['position'] = (int) Db::run("SELECT COALESCE(MAX(position), 0) + 1 FROM {$res['table']} WHERE $scope", $params)->fetchColumn();
        }
        $cols = array_keys($values);
        Db::run(
            "INSERT INTO {$res['table']} (" . implode(', ', $cols) . ') VALUES (' . implode(', ', array_fill(0, count($cols), '?')) . ')',
            array_values($values)
        );
        Auth::log("$key.creation", self::name($values));
        Session::flash(ucfirst((string) $res['singular']) . ' ajouté' . self::agree($res) . '.' . self::$geoNote);
        throw HttpException::redirect(self::liste($key, $res, $values));
    }

    public static function edit(string $key, string $id): void
    {
        [$res] = self::authorize($key);
        $row = self::find($res, (int) $id);
        self::form($key, $res, $row, $row, []);
    }

    public static function update(string $key, string $id): void
    {
        [$res] = self::authorize($key);
        Csrf::check();
        $row = self::find($res, (int) $id);
        [$values, $errors] = self::input($res, $row);
        if ($errors !== []) {
            self::form($key, $res, $row, $values + $row, $errors);
            return;
        }
        $values += self::geocode($res, $values, $row);
        // Changement de groupe : la fiche passe en fin de son nouveau groupe.
        if (isset($res['sortable']) && self::scope($res, $values + $row) !== self::scope($res, $row)) {
            [$scope, $params] = self::scope($res, $values + $row);
            $values['position'] = (int) Db::run("SELECT COALESCE(MAX(position), 0) + 1 FROM {$res['table']} WHERE $scope", $params)->fetchColumn();
        }
        $set = implode(', ', array_map(static fn (string $c): string => "$c = ?", array_keys($values)));
        // Date écrite par PHP : portable SQLite et MySQL.
        $touch = !empty($res['timestamps']) ? ', updated_at = ?' : '';
        Db::run("UPDATE {$res['table']} SET $set$touch WHERE id = ?", [...array_values($values), ...(!empty($res['timestamps']) ? [now()] : []), $row['id']]);
        // Fichier remplacé : l'ancien est effacé s'il ne sert plus à aucune fiche.
        foreach ($res['fields'] as $name => $f) {
            if (in_array($f['type'], ['image', 'pdf'], true) && ($row[$name] ?? null) !== ($values[$name] ?? null)) {
                self::deleteFileIfUnused($res, $name, $row[$name] ?? null);
            }
        }
        Auth::log("$key.modification", self::name($values + $row));
        Session::flash('Modifications enregistrées.' . self::$geoNote);
        throw HttpException::redirect(self::liste($key, $res, $values + $row));
    }

    /** Monter ou descendre une fiche d'un rang parmi celles du même groupe. */
    public static function move(string $key, string $id): void
    {
        [$res] = self::authorize($key);
        Csrf::check();
        $row = self::find($res, (int) $id);
        $tab = is_string($_POST['onglet'] ?? null) && preg_match('/^[a-z0-9_-]+$/', $_POST['onglet']) ? '?onglet=' . $_POST['onglet'] : '';
        if (isset($res['sortable'])) {
            [$scope, $params] = self::scope($res, $row);
            $ids = array_map('intval', Db::run("SELECT id FROM {$res['table']} WHERE $scope ORDER BY position, id", $params)->fetchAll(\PDO::FETCH_COLUMN));
            $i = array_search((int) $row['id'], $ids, true);
            $j = ($_POST['sens'] ?? '') === 'haut' ? $i - 1 : $i + 1;
            if ($i !== false && isset($ids[$j])) {
                [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]];
                $pdo = Db::pdo();
                $pdo->beginTransaction();
                foreach ($ids as $n => $rid) {
                    Db::run("UPDATE {$res['table']} SET position = ? WHERE id = ?", [$n + 1, $rid]);
                }
                $pdo->commit();
            }
        }
        throw HttpException::redirect("/admin/$key$tab#ligne-" . (int) $row['id']);
    }

    public static function destroy(string $key, string $id): void
    {
        [$res] = self::authorize($key);
        Csrf::check();
        $row = self::find($res, (int) $id);
        Db::run("DELETE FROM {$res['table']} WHERE id = ?", [$row['id']]);
        foreach ($res['fields'] as $name => $f) {
            if (in_array($f['type'], ['image', 'pdf'], true)) {
                self::deleteFileIfUnused($res, $name, $row[$name] ?? null);
            }
        }
        Auth::log("$key.suppression", self::name($row));
        Session::flash(ucfirst((string) $res['singular']) . ' supprimé' . self::agree($res) . '.');
        throw HttpException::redirect("/admin/$key");
    }

    /**
     * Ressource et compte, avec le périmètre appliqué : champ « perimetre » proposé aux comptes sans périmètre,
     * imposé (et retiré du formulaire) pour un éditeur qui en a un.
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    private static function authorize(string $key): array
    {
        $user = Auth::require();
        $res = Resources::get($key) ?? throw HttpException::notFound('Rubrique introuvable.');
        if (($res['role'] ?? null) === 'admin' && !Auth::isAdmin($user)) {
            throw HttpException::forbidden('Rubrique réservée aux administrateurs.');
        }
        $own = Auth::perimeter($user);
        $col = $res['perimetre'] ?? null;
        if ($col !== null) {
            if ($own === null) {
                $res['fields'][$col] ??= ['label' => 'Périmètre', 'type' => 'select', 'options' => ['' => 'Commun à tout le site'] + Auth::perimeters(), 'hint' => 'Les éditeurs de ce périmètre pourront modifier la fiche.'];
            } else {
                $res['fixed'][$col] = $own;
                unset($res['fields'][$col]);
            }
        } elseif ($own !== null && empty($res['partage'])) {
            throw HttpException::forbidden('Cette rubrique ne fait pas partie de votre périmètre.');
        }
        return [$res, $user];
    }

    /** @param array<string, mixed> $res @return array{0: string, 1: list<mixed>} */
    private static function fixedWhere(array $res): array
    {
        $where = [];
        $params = [];
        foreach ($res['fixed'] ?? [] as $col => $value) {
            $where[] = "$col IS ?";
            $params[] = $value;
        }
        return [implode(' AND ', $where), $params];
    }

    /**
     * @param array<string, mixed> $res
     * @param array<string, mixed>|null $row
     * @return array{0: array<string, mixed>, 1: array<string, string>}
     */
    private static function input(array $res, ?array $row): array
    {
        $values = [];
        $errors = [];
        foreach ($res['fields'] as $name => $f) {
            $raw = $_POST[$name] ?? null;
            $value = match ($f['type']) {
                'checkbox' => isset($_POST[$name]) ? 1 : 0,
                'number' => is_string($raw) && trim($raw) !== '' && is_numeric(str_replace(',', '.', trim($raw)))
                    ? (($f['step'] ?? '') === 'any' ? (float) str_replace(',', '.', $raw) : (int) $raw)
                    : null,
                'image', 'pdf' => isset($_POST[$name . '_remove']) ? null : ($row[$name] ?? null),
                default => is_string($raw) ? trim(str_replace("\r\n", "\n", $raw)) : '',
            };
            if (Upload::hasFile($name) && in_array($f['type'], ['image', 'pdf'], true)) {
                $up = $f['type'] === 'pdf'
                    ? Upload::pdf($name, (string) ($f['folder'] ?? 'documents'))
                    : Upload::image($name, (string) ($f['folder'] ?? 'images'), (array) ($f['widths'] ?? [1200]), isset($f['height']) ? (int) $f['height'] : null);
                if (is_string($up)) {
                    $errors[$name] = $up;
                } else {
                    $value = $up['path'];
                }
            }
            if ($value === '' && !in_array($f['type'], ['checkbox', 'number', 'image', 'pdf'], true)) {
                $value = null;
            }
            if ($value === null && $f['type'] === 'number' && isset($f['default'])) {
                $value = $f['default'];
            }
            if (!empty($f['required']) && $value === null && !isset($errors[$name])) {
                $errors[$name] = 'Champ obligatoire.';
            }
            if ($value !== null && !isset($errors[$name])) {
                $error = match ($f['type']) {
                    'email' => filter_var($value, FILTER_VALIDATE_EMAIL) ? null : 'Adresse e-mail invalide, par exemple nom@exemple.fr.',
                    'url' => preg_match('#^https?://[^\s<>"]+\.[^\s<>"]+$#i', (string) $value) ? null : 'Adresse web invalide : elle commence par https://',
                    'date' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $value) && strtotime((string) $value) !== false ? null : 'Date invalide.',
                    'select' => array_key_exists((string) $value, $f['options']) ? null : 'Choix invalide.',
                    'tel' => preg_match('/^[0-9 +().-]{6,25}$/', (string) $value) ? null : 'Numéro invalide.',
                    default => is_string($value) && (int) preg_match_all('/./su', $value) > (int) ($f['max'] ?? 20000) ? 'Texte trop long (' . (int) ($f['max'] ?? 20000) . ' caractères au plus).' : null,
                };
                if ($error !== null) {
                    $errors[$name] = $error;
                }
            }
            $values[$name] = $value;
        }
        return [($res['fixed'] ?? []) + $values, $errors];
    }

    /**
     * Latitude et longitude depuis l'adresse, quand elle est nouvelle ou modifiée. Introuvable : fiche hors carte.
     * @param array<string, mixed> $res @param array<string, mixed> $values @param array<string, mixed>|null $row
     * @return array<string, float|null>
     */
    private static function geocode(array $res, array $values, ?array $row): array
    {
        // field : un champ, ou une liste de champs réunis dans l'ordre (rue, code postal, commune) : une rue seule se
        // place mal (ajout du projet SSA, 2026-11-19).
        $fields = (array) ($res['geocode']['field'] ?? []);
        if ($fields === []) {
            return [];
        }
        $joindre = static fn (array $r): string => trim(implode(' ', array_filter(array_map(static fn (string $f): string => trim((string) ($r[$f] ?? '')), $fields))));
        $address = $joindre($values + ($row ?? []));
        if ($row !== null && $address === $joindre($row) && $row['latitude'] !== null) {
            return [];
        }
        if ($address === '') {
            return ['latitude' => null, 'longitude' => null];
        }
        $hit = Geocoder::locate($address, $res['geocode']['centre'] ?? null);
        if ($hit === null) {
            Session::flash('Adresse introuvable : la fiche est enregistrée, mais pas placée sur la carte. Vérifiez le numéro, la rue, le code postal et la ville.', 'error');
            return ['latitude' => null, 'longitude' => null];
        }
        self::$geoNote = ' Adresse trouvée : placé' . self::agree($res) . ' sur la carte (' . $hit['label'] . ').';
        return ['latitude' => $hit['lat'], 'longitude' => $hit['lon']];
    }

    /**
     * Fiches du même rang (même valeur des colonnes « sortable »), clause WHERE et paramètres.
     * @param array<string, mixed> $res @param array<string, mixed> $row
     * @return array{0: string, 1: list<mixed>}
     */
    private static function scope(array $res, array $row): array
    {
        $where = ['1 = 1'];
        $params = [];
        foreach ((array) $res['sortable'] as $col) {
            if (($row[$col] ?? null) === null) {
                $where[] = "$col IS NULL";
            } else {
                $where[] = "$col = ?";
                $params[] = $row[$col];
            }
        }
        return [implode(' AND ', $where), $params];
    }

    /**
     * @param array<string, mixed> $res @param array<string, mixed>|null $row
     * @param array<string, mixed> $values @param array<string, string> $errors
     */
    private static function form(string $key, array $res, ?array $row, array $values, array $errors): void
    {
        if ($errors !== []) {
            http_response_code(422);
        }
        View::render('admin/resource/form', [
            'title' => $row ? 'Modifier : ' . $res['singular'] : (empty($res['feminine']) ? 'Nouveau ' : 'Nouvelle ') . $res['singular'],
            'key' => $key,
            'res' => $res,
            'row' => $row,
            'values' => $values,
            'errors' => $errors,
        ], 'admin/layout');
    }

    /** @param array<string, mixed> $res @return array<string, mixed> */
    private static function find(array $res, int $id): array
    {
        $row = Db::run("SELECT * FROM {$res['table']} WHERE id = ?", [$id])->fetch();
        if (!$row) {
            throw HttpException::notFound('Fiche introuvable.');
        }
        foreach ($res['fixed'] ?? [] as $col => $wanted) {
            if ($row[$col] !== $wanted) {
                throw HttpException::notFound('Fiche introuvable.');
            }
        }
        return $row;
    }

    /** @param array<string, mixed> $res */
    private static function deleteFileIfUnused(array $res, string $column, ?string $path): void
    {
        if ($path !== null && $path !== '' && !Db::run("SELECT 1 FROM {$res['table']} WHERE $column = ?", [$path])->fetchColumn()) {
            Upload::delete($path);
        }
    }

    /** @param array<string, mixed> $values */
    /**
     * Liste à rouvrir après un enregistrement : sur l'onglet de la fiche quand la liste est rangée en onglets
     * (même calcul du nom d'onglet que templates/admin/resource/tabs.php), sinon la liste simple.
     * @param array<string, mixed> $res @param array<string, mixed> $values
     */
    private static function liste(string $key, array $res, array $values): string
    {
        if (!isset($res['tabs'])) {
            return "/admin/$key";
        }
        $v = strtolower((string) ($values[(string) $res['tabs']] ?? ''));
        return "/admin/$key?onglet=t-" . (preg_replace('/[^a-z0-9_-]/', '-', $v) ?: 'vide');
    }

    private static function name(array $values): string
    {
        return (string) ($values['nom'] ?? $values['name'] ?? $values['title'] ?? $values['id'] ?? '');
    }

    /** @param array<string, mixed> $res */
    private static function agree(array $res): string
    {
        return !empty($res['feminine']) ? 'e' : '';
    }
}
