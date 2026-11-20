<?php
declare(strict_types=1);

namespace App\Galerie;

use App\Admin\Admin;
use App\Admin\Edition;
use App\Core\Auth;
use App\Core\Db;
use App\Core\HttpException;
use App\Core\Session;
use App\Core\Upload;
use App\Site\Site;

/**
 * Rubrique Galerie du tableau de bord : photos de la table galerie_photos (24 par page sur /galerie/, ordre des
 * positions). Envoi groupé, descriptions, retrait, déplacement. Les photos de la mosaïque de l'accueil
 * (config/site.php, accueil.mosaique) ne se retirent pas : l'accueil les montre.
 */
final class GalerieAdmin
{
    public static function index(): void
    {
        Admin::page();
        $photos = Db::run('SELECT id, path, alt, position FROM galerie_photos ORDER BY position, id')->fetchAll();
        Edition::rendre('admin/galerie-ssa/index', ['title' => 'Galerie', 'photos' => $photos, 'accueil' => self::surAccueil(), 'sansDescription' => count(array_filter($photos, static fn (array $p): bool => trim((string) $p['alt']) === ''))]);
    }

    /** Envoi groupé : chaque photo ré-encodée en WebP, placée au début (par défaut) ou à la fin de la galerie. */
    public static function envoyer(): void
    {
        Admin::action();
        $fichiers = Upload::fichiers('photos');
        if ($fichiers === []) {
            Session::flash('Choisissez au moins une photo à envoyer.', 'attention');
            throw HttpException::redirect(Admin::url('galerie/'));
        }
        $auDebut = ($_POST['place'] ?? 'debut') !== 'fin';
        $bornes = Db::run('SELECT COALESCE(MIN(position), 0), COALESCE(MAX(position), 0) FROM galerie_photos')->fetch(\PDO::FETCH_NUM);
        // Au début : positions strictement avant la première photo (jamais égales : à égalité, l'ancienne passerait devant).
        $position = $auDebut ? (int) $bornes[0] - count($fichiers) - 1 : (int) $bornes[1];
        $ajoutees = 0;
        $refus = [];
        foreach ($fichiers as $f) {
            $r = is_string($f) ? $f : Upload::imageFichier($f, 'galerie', [1600]);
            if (is_string($r)) {
                $refus[] = $r;
                continue;
            }
            Db::run('INSERT INTO galerie_photos (path, alt, position, created_at) VALUES (?, ?, ?, ?)', [$r['path'], '', ++$position, now()]);
            $ajoutees++;
        }
        Auth::log('galerie.envoi', $ajoutees . ' photo(s)');
        $texte = $ajoutees . ' photo' . ($ajoutees > 1 ? 's ajoutées' : ' ajoutée') . ($auDebut ? ' au début de la galerie.' : ' à la fin de la galerie.')
            . ($ajoutees > 0 ? ' Décrivez maintenant chacune (ce qu\'elle montre), pour les personnes qui ne la voient pas.' : '');
        Session::flash($texte . ($refus !== [] ? ' Refusées : ' . implode(' ', $refus) : ''), $refus !== [] ? 'attention' : 'succes');
        // Photos ajoutées : on passe aussitôt à leur description (saisie guidée, décision du 2026-11-20).
        throw HttpException::redirect(Admin::url($ajoutees > 0 ? 'photos-a-decrire/' : 'galerie/'));
    }

    /** Descriptions et retraits, en un seul envoi. */
    public static function enregistrer(): void
    {
        Admin::action();
        $alts = (array) ($_POST['alt'] ?? []);
        $retraits = array_map('intval', (array) ($_POST['retirer'] ?? []));
        $accueil = self::surAccueil();
        $retirees = 0;
        $gardees = 0;
        foreach (Db::run('SELECT id, path, alt FROM galerie_photos')->fetchAll() as $p) {
            $id = (int) $p['id'];
            if (in_array($id, $retraits, true)) {
                if (in_array((string) $p['path'], $accueil, true)) {
                    $gardees++;
                    continue;
                }
                Db::run('DELETE FROM galerie_photos WHERE id = ?', [$id]);
                Upload::delete((string) $p['path']);
                $retirees++;
            } elseif (isset($alts[$id]) && trim((string) $alts[$id]) !== (string) $p['alt']) {
                Db::run('UPDATE galerie_photos SET alt = ? WHERE id = ?', [mb_substr(trim((string) $alts[$id]), 0, 250), $id]);
            }
        }
        Auth::log('galerie.modification', $retirees . ' retirée(s)');
        Session::flash('Galerie enregistrée.' . ($retirees > 0 ? ' ' . $retirees . ' photo' . ($retirees > 1 ? 's retirées.' : ' retirée.') : '')
            . ($gardees > 0 ? ' Les photos de la mosaïque de l\'accueil sont gardées.' : ''), $gardees > 0 ? 'attention' : 'succes');
        throw HttpException::redirect(Admin::url('galerie/'));
    }

    /** Déplacement d'une photo d'un cran (bouton « photo » = « id:haut » ou « id:bas »), positions réécrites. */
    public static function deplacer(): void
    {
        Admin::action();
        [$id, $sens] = array_pad(explode(':', (string) ($_POST['photo'] ?? ''), 2), 2, '');
        $ordre = array_map('intval', Db::run('SELECT id FROM galerie_photos ORDER BY position, id')->fetchAll(\PDO::FETCH_COLUMN));
        $i = array_search((int) $id, $ordre, true);
        $j = $i === false ? false : ($sens === 'haut' ? $i - 1 : $i + 1);
        if ($i !== false && $j >= 0 && $j < count($ordre)) {
            [$ordre[$i], $ordre[$j]] = [$ordre[$j], $ordre[$i]];
            Db::transaction(static function () use ($ordre): void {
                foreach ($ordre as $pos => $pid) {
                    Db::run('UPDATE galerie_photos SET position = ? WHERE id = ?', [$pos + 1, $pid]);
                }
            });
        }
        throw HttpException::redirect(Admin::url('galerie/') . '#photo-' . (int) $id);
    }

    /** @return list<string> chemins des photos montrées par la mosaïque de l'accueil */
    private static function surAccueil(): array
    {
        return array_values(array_map('strval', (array) (Site::get('accueil', [])['mosaique'] ?? [])));
    }
}
