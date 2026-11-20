<?php
declare(strict_types=1);

namespace App\Actualites;

use App\Admin\Admin;
use App\Admin\Edition;
use App\Core\Auth;
use App\Core\Db;
use App\Core\HttpException;
use App\Core\Session;
use App\Core\Upload;

/**
 * Rubrique Actualités du tableau de bord : articles de la table articles (modules/actualites et migration 300), lus par
 * la page Actualités et les fiches /actualites/<titre>/. Photo principale, photos supplémentaires (article_images),
 * liens (JSON [{texte, url, libelle}]) et vidéo (JSON [{src, poster, duree}]), au format que lit le site public.
 */
final class ActualitesAdmin
{
    /** Liens affichés sous l'article : lignes vides proposées en plus des liens existants. */
    private const LIENS_VIDES = 2;

    /** @return array<string, array<string, mixed>> champs du formulaire (templates/admin/partials/field.php) */
    public static function champs(): array
    {
        return [
            'titre' => ['label' => 'Titre', 'type' => 'text', 'required' => true, 'max' => 190, 'large' => true],
            'date_article' => ['label' => 'Date', 'type' => 'date', 'required' => true, 'taille' => 'court', 'hint' => 'À venir : parution ce jour-là.'],
            'heure' => ['label' => 'Heure', 'type' => 'time', 'taille' => 'court', 'hint' => 'Facultative.'],
            'lieu' => ['label' => 'Lieu', 'type' => 'text', 'max' => 300, 'large' => true, 'hint' => 'Facultatif.'],
            'resume' => ['label' => 'Résumé', 'type' => 'textarea', 'rows' => 2, 'max' => 300, 'hint' => 'Une ou deux phrases, montrées dans la liste des actualités et sur l\'accueil.'],
            'description' => ['label' => 'Texte de l\'article', 'type' => 'textarea', 'rows' => 8, 'required' => true, 'hint' => 'Une ligne vide sépare deux paragraphes.'],
            'image' => ['label' => 'Fichier de la photo', 'type' => 'image', 'folder' => 'actualites', 'widths' => [1600], 'hint' => 'JPG, PNG ou WebP, 15 Mo au plus. Convertie en WebP.'],
            'image_alt' => ['label' => 'Description de la photo', 'type' => 'text', 'max' => 250, 'large' => true, 'hint' => 'Ce que montre la photo, en une phrase, pour les personnes qui ne la voient pas (exemple : « Une vingtaine de personnes en cercle pendant l\'atelier cuisine »).'],
            'image_decorative' => ['label' => 'Photo décorative', 'type' => 'checkbox', 'hint' => 'Seulement si la photo n\'apporte aucune information (fond, motif) : elle n\'a pas besoin de description.'],
            'is_published' => ['label' => 'Afficher sur le site', 'type' => 'checkbox', 'default' => 1],
        ];
    }

    public static function index(): void
    {
        Admin::page();
        $articles = Db::run('SELECT id, titre, date_article, is_published, image, videos, slug FROM articles ORDER BY date_article DESC, id DESC')->fetchAll();
        Edition::rendre('admin/actualites-ssa/index', ['title' => 'Actualités', 'articles' => $articles, 'aujourdhui' => date('Y-m-d')]);
    }

    public static function create(): void
    {
        Admin::page();
        self::formulaire(null, ['date_article' => date('Y-m-d'), 'is_published' => 1], [], [], [], null);
    }

    public static function store(): void
    {
        Admin::action();
        [$v, $err, $ligne, $liens] = self::saisie(null);
        if ($err !== []) {
            self::formulaire(null, $v, $err, $liens, [], null);
            return;
        }
        $ligne['slug'] = Edition::slugUnique('articles', $ligne['titre']);
        $ligne['created_at'] = now();
        $cols = array_keys($ligne);
        $id = Db::insert('INSERT INTO articles (' . implode(', ', $cols) . ') VALUES (' . implode(', ', array_fill(0, count($cols), '?')) . ')', array_values($ligne));
        $notes = self::medias($id, null);
        Auth::log('actualites.creation', $ligne['titre']);
        Session::flash('Article ajouté. Adresse sur le site : /actualites/' . $ligne['slug'] . '/' . $notes, $notes !== '' ? 'attention' : 'succes');
        throw HttpException::redirect(Admin::url('actualites/' . $id . '/'));
    }

    public static function edit(string $id): void
    {
        Admin::page();
        $a = self::trouver($id);
        self::formulaire($a, $a, [], json_decode((string) ($a['liens'] ?? ''), true) ?: [], self::photos((int) $a['id']), self::video($a));
    }

    public static function update(string $id): void
    {
        Admin::action();
        $a = self::trouver($id);
        [$v, $err, $ligne, $liens] = self::saisie($a);
        if ($err !== []) {
            self::formulaire($a, $v + ['image' => $a['image']], $err, $liens, self::photos((int) $a['id']), self::video($a));
            return;
        }
        $ligne['updated_at'] = now();
        $set = implode(', ', array_map(static fn (string $c): string => "$c = ?", array_keys($ligne)));
        Db::run("UPDATE articles SET $set WHERE id = ?", [...array_values($ligne), (int) $a['id']]);
        $notes = self::medias((int) $a['id'], $a);
        Auth::log('actualites.modification', $ligne['titre']);
        Session::flash('Article enregistré.' . $notes, $notes !== '' ? 'attention' : 'succes');
        throw HttpException::redirect(Admin::url('actualites/' . (int) $a['id'] . '/'));
    }

    public static function delete(string $id): void
    {
        Admin::action();
        $a = self::trouver($id);
        foreach (self::photos((int) $a['id']) as $p) {
            Upload::delete((string) $p['path']);
        }
        Upload::delete((string) ($a['image'] ?? ''));
        Db::run('DELETE FROM article_images WHERE article_id = ?', [(int) $a['id']]);
        Db::run('DELETE FROM articles WHERE id = ?', [(int) $a['id']]);
        Db::run("DELETE FROM traductions WHERE objet = 'article' AND objet_id = ?", [(string) $a['id']]);
        Auth::log('actualites.suppression', (string) $a['titre']);
        Session::flash('Article supprimé.');
        throw HttpException::redirect(Admin::url('actualites/'));
    }

    /** @return array<string, mixed> */
    private static function trouver(string $id): array
    {
        $a = ctype_digit($id) ? Db::run('SELECT * FROM articles WHERE id = ?', [(int) $id])->fetch() : false;
        if ($a === false) {
            throw HttpException::notFound();
        }
        return $a;
    }

    /** @return list<array<string, mixed>> */
    private static function photos(int $id): array
    {
        return Db::run('SELECT id, path, alt, position FROM article_images WHERE article_id = ? ORDER BY position, id', [$id])->fetchAll();
    }

    /** @param array<string, mixed> $a @return ?array{src: string, poster: string, duree: int} */
    private static function video(array $a): ?array
    {
        $v = json_decode((string) ($a['videos'] ?? ''), true)[0] ?? null;
        return is_array($v) && !empty($v['src']) ? ['src' => (string) $v['src'], 'poster' => (string) ($v['poster'] ?? ''), 'duree' => (int) ($v['duree'] ?? 0)] : null;
    }

    /**
     * Champs texte et liens, vérifiés (les fichiers sont traités après, une fois l'article enregistré).
     * @param ?array<string, mixed> $a @return array{0: array<string, mixed>, 1: array<string, string>, 2: array<string, mixed>, 3: list<array{texte: string, url: string}>}
     */
    private static function saisie(?array $a): array
    {
        $v = [];
        foreach (self::champs() as $nom => $f) {
            if ($f['type'] !== 'image') {
                $v[$nom] = $f['type'] === 'checkbox' ? (isset($_POST[$nom]) ? 1 : 0) : trim((string) ($_POST[$nom] ?? ''));
            }
        }
        $err = [];
        if ($v['titre'] === '') {
            $err['titre'] = 'Indiquez le titre.';
        } elseif (mb_strlen($v['titre']) > 190) {
            $err['titre'] = '190 caractères au plus.';
        }
        if (!Edition::dateValide($v['date_article'])) {
            $err['date_article'] = 'Indiquez la date (jour, mois, année).';
        }
        if ($v['heure'] !== '' && !Edition::heureValide($v['heure'])) {
            $err['heure'] = 'Heure non valable, par exemple 18:30.';
        }
        if (mb_strlen($v['resume']) > 300) {
            $err['resume'] = '300 caractères au plus.';
        }
        if ($v['description'] === '') {
            $err['description'] = 'Écrivez le texte de l\'article.';
        }
        // Photo principale gardée ou envoyée : décrite, ou marquée décorative (décision du 2026-11-20).
        $aPhoto = Upload::hasFile('image') || ($a !== null && (string) ($a['image'] ?? '') !== '' && !isset($_POST['image_remove']));
        if ($aPhoto && $v['image_alt'] === '' && $v['image_decorative'] !== 1) {
            $err['image_alt'] = 'Décrivez la photo en une phrase, ou cochez « Photo décorative ».';
        } elseif (mb_strlen($v['image_alt']) > 250) {
            $err['image_alt'] = '250 caractères au plus.';
        }
        $liens = [];
        $textes = (array) ($_POST['liens_texte'] ?? []);
        foreach ((array) ($_POST['liens_url'] ?? []) as $i => $url) {
            $url = trim((string) $url);
            $texte = trim((string) ($textes[$i] ?? ''));
            if ($url === '' && $texte === '') {
                continue;
            }
            $liens[] = ['texte' => mb_substr($texte, 0, 160), 'url' => $url];
            if (!Edition::lienValide($url)) {
                $err['liens'] = 'Lien non valable : « ' . mb_strimwidth($url !== '' ? $url : $texte, 0, 60, '…') . ' ». Écrivez l\'adresse complète, commençant par https://.';
            }
        }
        $ligne = [
            'titre' => $v['titre'], 'date_article' => $v['date_article'], 'heure' => $v['heure'] !== '' ? $v['heure'] : null,
            'lieu' => $v['lieu'] !== '' ? mb_substr($v['lieu'], 0, 300) : null, 'resume' => $v['resume'] !== '' ? $v['resume'] : null,
            'description' => $v['description'], 'image_alt' => $v['image_decorative'] === 1 ? '' : $v['image_alt'], 'image_decorative' => $v['image_decorative'], 'is_published' => $v['is_published'],
            'liens' => $liens !== [] ? json_encode(array_map(static fn (array $l): array => $l + ['libelle' => (string) preg_replace('/^www\./', '', (string) parse_url($l['url'], PHP_URL_HOST))], $liens), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
        ];
        return [$v, $err, $ligne, $liens];
    }

    /**
     * Fichiers de l'article, après son enregistrement : photo principale, photos supplémentaires (descriptions, retrait,
     * ajout groupé), vidéo et son image d'attente. @param ?array<string, mixed> $a fiche avant modification
     * @return string remarques à montrer (fichier refusé), vide si tout est passé
     */
    private static function medias(int $id, ?array $a): string
    {
        $notes = [];
        // Photo principale
        if (isset($_POST['image_remove']) && $a !== null) {
            Upload::delete((string) $a['image']);
            Db::run('UPDATE articles SET image = NULL WHERE id = ?', [$id]);
        }
        if (Upload::hasFile('image')) {
            $r = Upload::image('image', 'actualites', [1600]);
            if (is_string($r)) {
                $notes[] = 'Photo principale non enregistrée : ' . $r;
            } else {
                $a !== null && Upload::delete((string) $a['image']);
                Db::run('UPDATE articles SET image = ? WHERE id = ?', [$r['path'], $id]);
            }
        }
        // Photos supplémentaires : descriptions et retraits, puis ajouts à la suite.
        $alts = (array) ($_POST['photos_alt'] ?? []);
        $retraits = array_map('intval', (array) ($_POST['photos_retirer'] ?? []));
        foreach (self::photos($id) as $p) {
            if (in_array((int) $p['id'], $retraits, true)) {
                Upload::delete((string) $p['path']);
                Db::run('DELETE FROM article_images WHERE id = ? AND article_id = ?', [(int) $p['id'], $id]);
            } elseif (isset($alts[$p['id']])) {
                Db::run('UPDATE article_images SET alt = ? WHERE id = ? AND article_id = ?', [mb_substr(trim((string) $alts[$p['id']]), 0, 250), (int) $p['id'], $id]);
            }
        }
        $position = (int) Db::run('SELECT COALESCE(MAX(position), 0) FROM article_images WHERE article_id = ?', [$id])->fetchColumn();
        foreach (Upload::fichiers('photos') as $f) {
            $r = is_string($f) ? $f : Upload::imageFichier($f, 'actualites', [1600]);
            if (is_string($r)) {
                $notes[] = $r;
                continue;
            }
            Db::run('INSERT INTO article_images (article_id, path, alt, position) VALUES (?, ?, ?, ?)', [$id, $r['path'], '', ++$position]);
        }
        // Vidéo et image d'attente
        $video = $a !== null ? self::video($a) : null;
        if (isset($_POST['video_remove']) && $video !== null) {
            Upload::delete($video['src']);
            Upload::delete($video['poster']);
            $video = null;
        }
        if (Upload::hasFile('video')) {
            $r = Upload::video('video', 'videos');
            if (is_string($r)) {
                $notes[] = 'Vidéo non enregistrée : ' . $r;
            } else {
                $video !== null && Upload::delete($video['src']);
                $video = ['src' => $r['path'], 'poster' => $video['poster'] ?? '', 'duree' => self::duree($r['path'])];
            }
        }
        if ($video !== null && Upload::hasFile('video_poster')) {
            $r = Upload::image('video_poster', 'videos', [1280]);
            if (is_string($r)) {
                $notes[] = 'Image d\'attente non enregistrée : ' . $r;
            } else {
                Upload::delete($video['poster']);
                $video['poster'] = $r['path'];
            }
        }
        Db::run('UPDATE articles SET videos = ? WHERE id = ?', [$video !== null ? json_encode([$video], JSON_UNESCAPED_SLASHES) : null, $id]);
        return $notes !== [] ? ' Attention : ' . implode(' ', $notes) : '';
    }

    /** Durée d'une vidéo en secondes (ffprobe s'il est installé sur le serveur), 0 sinon : la durée n'est alors pas affichée. */
    private static function duree(string $chemin): int
    {
        $fichier = ROOT . '/public' . $chemin;
        if (!is_file($fichier) || !function_exists('shell_exec') || trim((string) @shell_exec('command -v ffprobe')) === '') {
            return 0;
        }
        $s = shell_exec('ffprobe -v error -show_entries format=duration -of default=nw=1:nk=1 ' . escapeshellarg($fichier));
        return is_string($s) && is_numeric(trim($s)) ? (int) round((float) trim($s)) : 0;
    }

    /**
     * @param ?array<string, mixed> $a @param array<string, mixed> $valeurs @param array<string, string> $erreurs
     * @param list<array<string, string>> $liens @param list<array<string, mixed>> $photos @param ?array<string, mixed> $video
     */
    private static function formulaire(?array $a, array $valeurs, array $erreurs, array $liens, array $photos, ?array $video): void
    {
        $titre = $a ? (string) $a['titre'] : 'Nouvel article';
        Edition::rendre('admin/actualites-ssa/form', [
            'title' => $titre, 'a' => $a, 'champs' => self::champs(), 'valeurs' => $valeurs, 'erreurs' => $erreurs,
            'liens' => [...$liens, ...array_fill(0, self::LIENS_VIDES, ['texte' => '', 'url' => ''])], 'photos' => $photos, 'video' => $video,
            'fil' => [['libelle' => 'Tableau de bord', 'url' => Admin::url()], ['libelle' => 'Actualités', 'url' => Admin::url('actualites/')], ['libelle' => $titre]],
        ]);
    }
}
