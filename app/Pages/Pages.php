<?php
declare(strict_types=1);

namespace App\Pages;

use App\Core\View;
use App\Langue\Contenu;

/** Pages fixes : textes de config/contenus.php, version bretonne lue dans la table traductions (objet « page »). */
final class Pages
{
    /** @return array<string, string> */
    public static function textes(string $chemin): array
    {
        static $tous = null;
        $tous ??= (array) require ROOT . '/config/contenus.php';
        return (array) ($tous[$chemin] ?? []);
    }

    /** @param array<string, mixed> $donnees données en plus (listes de l'accueil) */
    public static function afficher(string $chemin, string $gabarit, array $donnees = []): void
    {
        $source = self::textes($chemin);
        $t = Contenu::traduire('page', $chemin, $source);
        View::render('pages/' . $gabarit, [
            'meta' => ['title' => $t['title'] ?? $t['titre'] ?? '', 'description' => $t['description'] ?? '', 'h1' => $t['titre'] ?? ''],
            'path' => $chemin,
            'disponibles' => Contenu::disponibles('page', $chemin),
            't' => $t,
        ] + $donnees, 'site/layout');
    }
}
