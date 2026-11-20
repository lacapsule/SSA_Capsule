<?php
declare(strict_types=1);

namespace App\Admin;

use App\Core\Auth;
use App\Core\Db;
use App\Core\View;

/** Consultation du journal (administrateurs) : liste paginée, la plus récente d'abord, filtrable par compte et par action. */
final class AuditController
{
    public const PER_PAGE = 50;

    /** Libellés lisibles des actions connues ; une action inconnue s'affiche telle quelle. */
    public const LABELS = [
        'connexion' => 'Connexion',
        'deconnexion' => 'Déconnexion',
        'compte.creation' => 'Compte créé',
        'compte.modification' => 'Compte modifié',
        'compte.suppression' => 'Compte supprimé',
        'compte.activation' => 'Compte activé',
        'compte.lien' => 'Lien de mot de passe envoyé',
        'mot-de-passe.lien' => 'Lien de mot de passe créé',
        'mot-de-passe.change' => 'Mot de passe changé',
        'mot-de-passe.reinitialise.lien' => 'Mot de passe choisi par lien',
        'mot-de-passe.reinitialise' => 'Mot de passe réinitialisé sur le serveur',
        'mot-de-passe.reinitialise.2fa' => 'Mot de passe et double authentification réinitialisés',
        '2fa.activee' => 'Double authentification activée',
        'codes-secours.regeneres' => 'Codes de secours renouvelés',
        'code-secours.utilise' => 'Code de secours utilisé',
        'compte.mot_de_passe' => 'Mot de passe changé',
        'compte.mot_de_passe_oublie' => 'Mot de passe oublié demandé',
        'compte.lien_mot_de_passe' => 'Lien de mot de passe envoyé',
        // Actions des autres modules de la boîte à outils (pages, newsletter) ; celles des ressources
        // (« cle.creation », « cle.modification », « cle.suppression ») sont traduites par label().
        'page.modification' => 'Page modifiée',
        'lettre.enregistrement' => 'Lettre enregistrée',
        'lettre.envoi' => 'Lettre envoyée',
        'lettre.suppression' => 'Lettre supprimée',
        'modele.enregistrement' => 'Modèle enregistré',
        'modele.suppression' => 'Modèle supprimé',
        'abonne.ajout' => 'Abonné ajouté',
        'abonne.suppression' => 'Abonné effacé',
        'abonnes.import' => 'Abonnés importés',
        'abonnes.export' => 'Abonnés exportés',
    ];

    /** Verbes génériques « objet.verbe » (ressources du module crud-declaratif). */
    private const VERBS = ['creation' => 'ajout', 'modification' => 'modification', 'suppression' => 'suppression', 'descriptions' => 'descriptions écrites', 'deplacement' => 'ordre changé'];
    /** Rubriques écrites avec leurs accents (« actualites.creation » => « Actualités : ajout »). */
    private const OBJETS = ['actualites' => 'Actualités', 'agenda' => 'Agenda', 'galerie' => 'Galerie', 'photos' => 'Photos', 'lieux' => 'Lieux conventionnés', 'partenaires' => 'Partenaires et financeurs', 'sauvegarde' => 'Sauvegarde'];

    public static function index(): void
    {
        Admin::requireAdmin();
        $where = [];
        $params = [];
        // Compte : identifiant, ou « aucun » pour les actions sans compte connecté (mot de passe oublié, compte supprimé).
        $account = (string) ($_GET['compte'] ?? '');
        if ($account === 'aucun') {
            $where[] = 'a.admin_id IS NULL';
        } elseif (ctype_digit($account)) {
            $where[] = 'a.admin_id = ?';
            $params[] = (int) $account;
        } else {
            $account = '';
        }
        // Action exacte, ou famille « compte.* » (préfixe avant le point).
        $actions = Db::run('SELECT DISTINCT action FROM admin_log ORDER BY action')->fetchAll(\PDO::FETCH_COLUMN);
        $families = array_values(array_unique(array_filter(array_map(
            static fn (string $a): ?string => str_contains($a, '.') ? strstr($a, '.', true) . '.*' : null,
            $actions
        ))));
        $action = (string) ($_GET['action'] ?? '');
        if (in_array($action, $actions, true)) {
            $where[] = 'a.action = ?';
            $params[] = $action;
        } elseif (in_array($action, $families, true)) {
            $where[] = 'a.action LIKE ?';
            $params[] = substr($action, 0, -1) . '%';
        } else {
            $action = '';
        }
        $sql = ' FROM admin_log a LEFT JOIN admins u ON u.id = a.admin_id' . ($where !== [] ? ' WHERE ' . implode(' AND ', $where) : '');
        $total = (int) Db::run('SELECT COUNT(*)' . $sql, $params)->fetchColumn();
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min($pages, max(1, (int) ($_GET['page'] ?? 1)));
        $rows = Db::run(
            'SELECT a.id, a.actor, a.action, a.target, a.ip, a.created_at, a.admin_id, u.name, u.email' . $sql . ' ORDER BY a.id DESC LIMIT ' . self::PER_PAGE . ' OFFSET ' . (($page - 1) * self::PER_PAGE),
            $params
        )->fetchAll();

        View::render('admin/journal/index', [
            'title' => 'Journal',
            'rows' => $rows,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'account' => $account,
            'action' => $action,
            'accounts' => Db::run('SELECT id, name, email FROM admins ORDER BY LOWER(name), LOWER(email)')->fetchAll(),
            'actions' => $actions,
            'families' => $families,
        ], (string) config('admin.layout', 'admin/layout'));
    }

    public static function label(string $action): string
    {
        if (isset(self::LABELS[$action])) {
            return self::LABELS[$action];
        }
        // Variante écrite à la connexion (« connexion (développement, sans double authentification) ») : déjà en clair.
        if (str_contains($action, ' ')) {
            return ucfirst($action);
        }
        [$object, $verb] = array_pad(explode('.', $action, 2), 2, '');
        return isset(self::VERBS[$verb]) ? (self::OBJETS[$object] ?? ucfirst($object)) . ' : ' . self::VERBS[$verb] : $action;
    }

    /** Adresse de la page $n en gardant les filtres. */
    public static function pageUrl(int $n, string $account, string $action): string
    {
        $q = array_filter(['compte' => $account, 'action' => $action, 'page' => $n > 1 ? (string) $n : ''], static fn (string $v): bool => $v !== '');
        return Admin::url('journal/') . ($q !== [] ? '?' . http_build_query($q) : '');
    }
}
