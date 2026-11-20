<?php
declare(strict_types=1);

/*
 * Règles du tableau de bord propres au projet : liens saisis par un éditeur (rule lecons-securite), adresses lisibles
 * uniques, dates et heures, délai de double authentification des comptes repris (décision du 2026-11-19).
 * Chaque test reçoit une base neuve où les migrations du projet sont appliquées (tests/run.php).
 */

use App\Admin\AdminAuth;
use App\Admin\Edition;
use App\Core\Db;

$compte = static function (PDO $db, int $reprise, ?string $totp = null, ?string $avant = null): array {
    $db->prepare('INSERT INTO admins (email, name, password_hash, reprise_2fa, totp_enabled_at, deux_facteurs_avant, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)')
        ->execute(['personne' . random_int(1, 1_000_000) . '@exemple.invalid', 'Personne', password_hash('x', PASSWORD_DEFAULT), $reprise, $totp, $avant, now()]);
    return Db::run('SELECT * FROM admins WHERE id = ?', [(int) $db->lastInsertId()])->fetch();
};

return [
    'photos sans description : galerie et photos d\'article comptées, photo décorative ou décrite exclue' => static function (PDO $db): void {
        $db->exec("INSERT INTO galerie_photos (path, alt, position, created_at) VALUES ('/media/a.webp', '', 1, '2026-11-20'), ('/media/b.webp', 'Un atelier', 2, '2026-11-20')");
        $article = $db->prepare("INSERT INTO articles (titre, resume, description, date_article, image, image_alt, image_decorative, slug, created_at) VALUES (?, '', 'Texte', '2026-11-20', ?, ?, ?, ?, '2026-11-20')");
        $article->execute(['A', '/media/c.webp', '', 0, 'a']);
        $article->execute(['B', '/media/d.webp', '', 1, 'b']);
        $article->execute(['C', '/media/e.webp', 'Une réunion', 0, 'c']);
        $article->execute(['D', null, '', 0, 'd']);
        affirmer_egal(2, App\Galerie\DescriptionsAdmin::nombre());
    },

    'compte du poste de développement : refusé hors du serveur intégré de PHP, même avec le bon mot de passe' => static function (PDO $db): void {
        $db->prepare("INSERT INTO admins (email, name, password_hash, role, created_at) VALUES (?, 'Dev', ?, 'admin', ?)")
            ->execute([AdminAuth::DEV_EMAIL, password_hash(AdminAuth::DEV_PASSWORD, PASSWORD_DEFAULT), now()]);
        affirmer(!AdminAuth::developpementLocal(), 'les tests ne tournent pas sur le serveur intégré');
        affirmer_egal(AdminAuth::LOGIN_FAILED, AdminAuth::password(AdminAuth::DEV_EMAIL, AdminAuth::DEV_PASSWORD));
    },

    'lien d\'un éditeur : schémas dangereux et redirections détournées refusés' => static function (): void {
        foreach (['javascript:alert(1)', 'JavaScript:alert(1)', 'data:text/html,x', 'vbscript:x', '//autre.exemple', '/\\autre.exemple', "/\tchemin", 'ftp://exemple.org', 'https://', 'exemple.org', ''] as $l) {
            affirmer(!Edition::lienValide($l), "refusé : « $l »");
        }
        foreach (['https://framadate.org/essai', 'http://exemple.org/page?x=1', '/lieux-conventionnes/'] as $l) {
            affirmer(Edition::lienValide($l), "accepté : « $l »");
        }
    },

    'liens par ligne : une erreur nomme le lien fautif' => static function (): void {
        [$liens, $err] = Edition::liensParLigne("https://a.exemple.org\n\njavascript:alert(1)\n");
        affirmer_egal(2, count($liens));
        affirmer($err !== null && str_contains($err, 'javascript:alert(1)'), 'message qui nomme le lien');
        [, $err] = Edition::liensParLigne("https://a.exemple.org\nhttps://b.exemple.org");
        affirmer_egal(null, $err);
    },

    'adresse lisible : minuscules ASCII, unique dans la table' => static function (PDO $db): void {
        affirmer_egal('reunion-mensuelle-la-collective-d-animation', Edition::slug('Réunion mensuelle : La Collective d\'Animation !'));
        $db->prepare("INSERT INTO events (title, starts_at, ends_at, slug, created_at) VALUES ('A', '2026-01-01 10:00', '2026-01-01 11:00', 'atelier-cuisine', ?)")->execute([now()]);
        $db->prepare("INSERT INTO events (title, starts_at, ends_at, slug, created_at) VALUES ('A', '2026-01-01 10:00', '2026-01-01 11:00', 'atelier-cuisine-2', ?)")->execute([now()]);
        affirmer_egal('atelier-cuisine-3', Edition::slugUnique('events', 'Atelier cuisine'));
        affirmer_egal('nouvel-atelier', Edition::slugUnique('articles', 'Nouvel atelier'));
    },

    'adresse lisible : table hors liste refusée' => static function (): void {
        $refus = false;
        try {
            Edition::slugUnique('admins', 'x');
        } catch (LogicException) {
            $refus = true;
        }
        affirmer($refus, 'table admins refusée');
    },

    'dates et heures : calendrier vérifié' => static function (): void {
        affirmer(Edition::dateValide('2026-02-28') && !Edition::dateValide('2026-02-30') && !Edition::dateValide('28/02/2026'), 'dates');
        affirmer(Edition::heureValide('09:30') && Edition::heureValide('23:59') && !Edition::heureValide('24:00') && !Edition::heureValide('9h'), 'heures');
    },

    'compte repris sans double authentification : délai de 30 jours posé à la première connexion' => static function (PDO $db) use ($compte): void {
        $c = $compte($db, 1);
        $avant = AdminAuth::delaiReprise($c);
        affirmer($avant !== null, 'délai accordé');
        $jours = (int) round(((new DateTimeImmutable((string) $avant))->getTimestamp() - (new DateTimeImmutable(now()))->getTimestamp()) / 86400);
        affirmer_egal(AdminAuth::DELAI_REPRISE_JOURS, $jours, 'échéance à 30 jours');
        affirmer_egal($avant, (string) Db::run('SELECT deux_facteurs_avant FROM admins WHERE id = ?', [$c['id']])->fetchColumn(), 'échéance enregistrée une fois');
    },

    'compte repris : plus de délai une fois l\'échéance passée ou la double authentification active' => static function (PDO $db) use ($compte): void {
        affirmer_egal(null, AdminAuth::delaiReprise($compte($db, 1, null, '2020-01-01 00:00:00')), 'échéance passée : activation exigée');
        affirmer_egal(null, AdminAuth::delaiReprise($compte($db, 1, '2026-01-01 10:00:00')), 'double authentification active : pas de délai');
    },

    'compte créé ici (script, invitation) : aucun délai' => static function (PDO $db) use ($compte): void {
        affirmer_egal(null, AdminAuth::delaiReprise($compte($db, 0)));
    },
];
