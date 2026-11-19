<?php
declare(strict_types=1);

namespace App\Core;

use App\Admin\Admin;

/**
 * Rôles, périmètres et journal, au-dessus de la garde commune (App\Admin\Admin, gabarits/socle-php).
 * La connexion elle-même vient de modules/authentification-admin ; ce module ajoute à la table admins
 * les colonnes role et perimetre (migration 032) et écrit le journal dans admin_log.
 * API stable, utilisée par les modules newsletter, crud-declaratif et pages-sections-markdown.
 */
final class Auth
{
    public const ROLES = ['admin' => 'Administrateur', 'editor' => 'Éditeur'];

    /** @return array<string, mixed>|null compte connecté (clés communes : id, email, name, role, perimetre) */
    public static function user(): ?array
    {
        return Admin::current();
    }

    /**
     * Compte connecté, avec le rôle demandé s'il est précisé. Sans compte : redirection vers la connexion ;
     * rôle insuffisant : 403 (HttpException, attrapée par le contrôleur frontal).
     * @return array<string, mixed>
     */
    public static function require(?string $role = null): array
    {
        $user = Admin::page();
        if ($role !== null && $user['role'] !== $role) {
            throw HttpException::forbidden('Accès réservé aux administrateurs.');
        }
        return $user;
    }

    /** @param array<string, mixed>|null $user compte donné, sinon le compte connecté */
    public static function isAdmin(?array $user = null): bool
    {
        $user ??= self::user();
        return ($user['role'] ?? '') === 'admin';
    }

    /**
     * Périmètre d'un compte : null pour un administrateur ou un éditeur sans périmètre (tout le contenu),
     * sinon la clé de config/perimetres.php.
     * @param array<string, mixed> $user
     */
    public static function perimeter(array $user): ?string
    {
        if (self::isAdmin($user)) {
            return null;
        }
        $p = (string) ($user['perimetre'] ?? '');
        return $p === '' ? null : $p;
    }

    /**
     * Le compte peut-il gérer un contenu rattaché à ce périmètre ? Un contenu sans périmètre (null) est commun :
     * seuls les comptes sans périmètre le gèrent.
     * @param array<string, mixed> $user
     */
    public static function allows(array $user, ?string $perimeter): bool
    {
        $own = self::perimeter($user);
        return $own === null || $own === $perimeter;
    }

    /** @return array<string, string> clé => libellé, depuis config/perimetres.php (vide : pas de périmètre) */
    public static function perimeters(): array
    {
        static $list = null;
        if ($list === null) {
            $file = ROOT . '/config/perimetres.php';
            $list = is_file($file) ? (array) require $file : [];
        }
        return $list;
    }

    /** Longueur en caractères (sans dépendre de mbstring). */
    public static function length(string $value): int
    {
        return text_length($value);
    }

    /**
     * Ligne du journal (table admin_log) : qui (compte connecté), quoi (action « objet.verbe »), sur quoi (texte libre court).
     * L'adresse du compte est recopiée dans actor : la ligne reste lisible si le compte est supprimé ensuite.
     */
    public static function log(string $action, ?string $target = null): void
    {
        $target = (string) preg_replace('/^(.{0,190}).*$/su', '$1', (string) $target);
        $user = self::user();
        Db::run(
            'INSERT INTO admin_log (admin_id, actor, action, target, ip, created_at) VALUES (?, ?, ?, ?, ?, ?)',
            [$user['id'] ?? null, $user['email'] ?? null, $action, $target, PHP_SAPI === 'cli' ? 'serveur' : Request::ip(), now()]
        );
    }

    /** Efface les lignes du journal plus anciennes que $days jours (bin/journal-purge). Renvoie le nombre effacé. */
    public static function purgeLog(int $days = 365): int
    {
        return Db::run('DELETE FROM admin_log WHERE created_at < ?', [date('Y-m-d H:i:s', time() - max(1, $days) * 86400)])->rowCount();
    }
}
