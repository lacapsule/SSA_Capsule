<?php
declare(strict_types=1);

namespace App\Admin;

use App\Core\Db;
use App\Core\View;

/**
 * Accueil du tableau de bord : raccourcis et chiffres déclarés dans config/tableau-de-bord.php,
 * filtrés par rôle. Pas d'alerte technique : les erreurs vont au journal PHP.
 */
final class DashboardController
{
    public static function index(): void
    {
        $user = Admin::page();
        $isAdmin = $user['role'] === 'admin';
        $config = is_file(ROOT . '/config/tableau-de-bord.php') ? (array) require ROOT . '/config/tableau-de-bord.php' : [];
        $visible = static fn (array $item): bool => ($item['role'] ?? null) !== 'admin' || $isAdmin;

        $stats = [];
        foreach (array_filter((array) ($config['chiffres'] ?? []), $visible) as $s) {
            // Requête écrite dans la config (jamais depuis une saisie) ; une table absente ne casse pas l'accueil.
            try {
                $n = (int) Db::run((string) $s['sql'])->fetchColumn();
            } catch (\PDOException) {
                continue;
            }
            $stats[] = ['href' => (string) $s['href'], 'label' => (string) $s['label'], 'n' => $n];
        }

        View::render('admin/dashboard', [
            'title' => 'Tableau de bord',
            'user' => $user,
            'stats' => $stats,
            'actions' => array_values(array_filter((array) ($config['raccourcis'] ?? []), $visible)),
        ], (string) config('admin.layout', 'admin/layout'));
    }
}
