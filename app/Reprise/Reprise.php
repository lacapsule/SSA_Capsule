<?php
declare(strict_types=1);

namespace App\Reprise;

use App\Core\Db;
use App\Langue\Langue;
use App\Site\Redirects;

/**
 * Anciennes adresses du site actuel, en 301 (mode d'emploi, Arborescence) :
 * /article/<n> vers l'article qui l'a repris (colonne ancien_numero, suit un changement de titre), sauf les cas fixes
 * de config/redirections.tsv ; ?lang=fr|br vers la même page dans la langue demandée, adresse sans paramètre de langue.
 */
final class Reprise
{
    public static function article(string $numero): void
    {
        if (!ctype_digit($numero)) {
            abort(404);
        }
        $fixe = Redirects::find('/article/' . $numero);
        if ($fixe !== null) {
            redirect(Langue::url($fixe), 301);
        }
        $slug = Db::run('SELECT slug FROM articles WHERE ancien_numero = ? AND is_published = 1', [(int) $numero])->fetchColumn();
        $slug === false ? abort(404) : redirect(Langue::url('/actualites/' . $slug . '/'), 301);
    }

    /**
     * Paramètre ?lang= de l'ancien site (langue gardée en session, une adresse pour deux langues) : 301 vers l'adresse
     * de la langue, sans ce paramètre ; ?page=n de l'accueil retiré aussi (pagination de blocs qui n'existent plus).
     * @param string $path chemin interne (après Langue::demarrer)
     */
    public static function parametreLangue(string $path): void
    {
        $langue = $_GET['lang'] ?? null;
        if (!is_string($langue) || !Langue::existe($langue)) {
            return;
        }
        // Slash final ajouté d'emblée (sauf fichier) : une seule redirection jusqu'à l'adresse définitive.
        if (!str_ends_with($path, '/') && !str_contains(basename($path), '.')) {
            $path .= '/';
        }
        $reste = array_diff_key($_GET, ['lang' => 1] + ($path === '/' ? ['page' => 1] : []));
        redirect(Langue::url($path, $langue) . ($reste ? '?' . http_build_query($reste) : ''), 301);
    }
}
