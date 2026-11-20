<?php
declare(strict_types=1);

namespace App\Galerie;

use App\Admin\Admin;
use App\Admin\Edition;
use App\Core\Auth;
use App\Core\Db;
use App\Core\HttpException;
use App\Core\Session;

/**
 * Photos sans description (décision du 2026-11-20) : saisie guidée des textes de remplacement des photos de la galerie
 * et des photos principales des articles, par écrans de PAR_ECRAN photos. Une photo d'article marquée décorative sort
 * de la liste ; une photo de galerie se décrit toujours (c'est le contenu de la page). Aucun texte n'est deviné.
 */
final class DescriptionsAdmin
{
    public const PAR_ECRAN = 12;

    /** Nombre de photos à décrire (accueil du tableau de bord, galerie). */
    public static function nombre(): int
    {
        return (int) Db::run("SELECT (SELECT COUNT(*) FROM galerie_photos WHERE TRIM(alt) = '')
            + (SELECT COUNT(*) FROM articles WHERE COALESCE(image, '') <> '' AND TRIM(COALESCE(image_alt, '')) = '' AND image_decorative = 0)")->fetchColumn();
    }

    public static function index(): void
    {
        Admin::page();
        $total = self::nombre();
        $pages = max(1, (int) ceil($total / self::PAR_ECRAN));
        $page = min($pages, max(1, (int) ($_GET['page'] ?? 1)));
        Edition::rendre('admin/galerie-ssa/descriptions', [
            'title' => 'Photos sans description',
            'photos' => self::liste(($page - 1) * self::PAR_ECRAN),
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
        ]);
    }

    public static function enregistrer(): void
    {
        Admin::action();
        $alts = (array) ($_POST['alt'] ?? []);
        $deco = array_map('strval', (array) ($_POST['decorative'] ?? []));
        $faites = 0;
        foreach ($alts as $cle => $texte) {
            if (!is_string($cle) || preg_match('/^(g|a)-(\d+)$/', $cle, $m) !== 1) {
                continue;
            }
            $texte = mb_substr(trim((string) $texte), 0, 250);
            $id = (int) $m[2];
            if ($m[1] === 'g' && $texte !== '') {
                $faites += Db::run("UPDATE galerie_photos SET alt = ? WHERE id = ? AND TRIM(alt) = ''", [$texte, $id])->rowCount();
            } elseif ($m[1] === 'a' && ($texte !== '' || in_array($cle, $deco, true))) {
                $faites += Db::run("UPDATE articles SET image_alt = ?, image_decorative = ? WHERE id = ? AND TRIM(COALESCE(image_alt, '')) = ''",
                    [$texte, $texte === '' ? 1 : 0, $id])->rowCount();
            }
        }
        Auth::log('photos.descriptions', $faites . ' photo(s) décrite(s)');
        $reste = self::nombre();
        Session::flash($faites === 0 ? 'Aucune description enregistrée : écrivez au moins une description.' : ($faites . ' photo' . ($faites > 1 ? 's décrites' : ' décrite') . '.' . ($reste > 0 ? " Encore $reste à décrire." : ' Toutes les photos ont maintenant une description.')), $faites === 0 ? 'attention' : 'succes');
        $page = max(1, (int) ($_POST['page'] ?? 1));
        throw HttpException::redirect(Admin::url('photos-a-decrire/') . ($page > 1 && $reste > ($page - 1) * self::PAR_ECRAN ? '?page=' . $page : ''));
    }

    /** @return list<array{cle: string, src: string, origine: string, lien: string, article: bool}> */
    private static function liste(int $debut): array
    {
        $photos = [];
        foreach (Db::run("SELECT id, path, position FROM galerie_photos WHERE TRIM(alt) = '' ORDER BY position, id")->fetchAll() as $p) {
            $photos[] = ['cle' => 'g-' . (int) $p['id'], 'src' => (string) $p['path'], 'origine' => 'Galerie', 'lien' => Admin::url('galerie/') . '#photo-' . (int) $p['id'], 'article' => false];
        }
        foreach (Db::run("SELECT id, titre, image FROM articles WHERE COALESCE(image, '') <> '' AND TRIM(COALESCE(image_alt, '')) = '' AND image_decorative = 0 ORDER BY date_article DESC, id DESC")->fetchAll() as $a) {
            $photos[] = ['cle' => 'a-' . (int) $a['id'], 'src' => (string) $a['image'], 'origine' => 'Article « ' . $a['titre'] . ' »', 'lien' => Admin::url('actualites/' . (int) $a['id'] . '/'), 'article' => true];
        }
        // Articles d'abord : peu nombreux, et la photo principale est la plus vue (accueil, partage).
        usort($photos, static fn (array $x, array $y): int => (int) $y['article'] <=> (int) $x['article']);
        return array_slice($photos, $debut, self::PAR_ECRAN);
    }
}
