<?php
declare(strict_types=1);

namespace App\Admin;

/**
 * Définitions des rubriques simples (config/ressources.php), vérifiées au chargement : les noms de table et de
 * colonne entrent dans les requêtes SQL, ils doivent être des identifiants simples.
 */
final class Resources
{
    public const TYPES = ['text', 'email', 'url', 'tel', 'textarea', 'date', 'number', 'checkbox', 'select', 'image', 'pdf', 'address'];
    private const IDENT = '/^[a-z_][a-z0-9_]*$/';

    /** @return array<string, array<string, mixed>> */
    public static function all(): array
    {
        static $all = null;
        if ($all !== null) {
            return $all;
        }
        $all = is_file(ROOT . '/config/ressources.php') ? (array) require ROOT . '/config/ressources.php' : [];
        foreach ($all as $key => &$res) {
            if (!preg_match('/^[a-z0-9-]+$/', (string) $key) || !preg_match(self::IDENT, (string) ($res['table'] ?? ''))) {
                throw new \LogicException("Ressource « $key » : clé ou table invalide.");
            }
            foreach (array_merge(array_keys($res['fields'] ?? []), array_keys($res['columns'] ?? []), (array) ($res['sortable'] ?? []), [(string) ($res['perimetre'] ?? 'id'), (string) ($res['tabs'] ?? 'id')]) as $col) {
                if (!preg_match(self::IDENT, (string) $col)) {
                    throw new \LogicException("Ressource « $key » : colonne invalide « $col ».");
                }
            }
            foreach ($res['fields'] as $name => $f) {
                if (!in_array($f['type'] ?? '', self::TYPES, true)) {
                    throw new \LogicException("Ressource « $key », champ « $name » : type inconnu.");
                }
            }
            // ORDER BY : colonnes, ASC/DESC, virgules ; rien d'autre (LOWER() refusé : trier sur une colonne déjà normalisée).
            if (!preg_match('/^[a-z0-9_ ,]+$/i', (string) ($res['order'] ?? 'id'))) {
                throw new \LogicException("Ressource « $key » : ordre invalide.");
            }
            $res += ['order' => 'id', 'intro' => '', 'columns' => [], 'singular' => 'fiche', 'feminine' => false];
        }
        unset($res);
        return $all;
    }

    /** @return array<string, mixed>|null */
    public static function get(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }
}
