<?php
declare(strict_types=1);

namespace App\Agenda;

use App\Admin\Admin;
use App\Admin\Edition;
use App\Core\Auth;
use App\Core\Db;
use App\Core\HttpException;
use App\Core\Session;
use App\Site\Dates;

/**
 * Rubrique Agenda du tableau de bord : événements de la table events (modules/agenda-inscriptions), lus par la page
 * Agenda et les fiches /agenda/<titre>/. Adresse lisible posée à la création ; liens une adresse par ligne.
 */
final class AgendaAdmin
{
    /** Champs du formulaire, au format de templates/admin/partials/field.php. @return array<string, array<string, mixed>> */
    public static function champs(): array
    {
        $publics = array_map(static fn (string $cle): string => t($cle), Agenda::CATEGORIES);
        return [
            'title' => ['label' => 'Titre', 'type' => 'text', 'required' => true, 'max' => 190, 'large' => true],
            'category' => ['label' => 'Public', 'type' => 'select', 'required' => true, 'options' => $publics, 'hint' => 'Sert au filtre de la page Agenda.'],
            'date' => ['label' => 'Date', 'type' => 'date', 'required' => true],
            'debut' => ['label' => 'Heure de début', 'type' => 'time', 'required' => true],
            'fin' => ['label' => 'Heure de fin', 'type' => 'time', 'required' => true],
            'date_fin' => ['label' => 'Dernier jour (sur plusieurs jours)', 'type' => 'date', 'hint' => 'Laisser vide pour un événement sur une seule journée.'],
            'address' => ['label' => 'Lieu', 'type' => 'text', 'max' => 300, 'large' => true, 'hint' => 'Nom du lieu et commune, par exemple « locaux de l\'AFPA, Morlaix ».'],
            'description' => ['label' => 'Description', 'type' => 'textarea', 'hint' => 'Une ligne vide sépare deux paragraphes.'],
            'links' => ['label' => 'Liens', 'type' => 'textarea', 'hint' => 'Inscription, sondage, document : une adresse complète par ligne (https://…).'],
            'is_published' => ['label' => 'Afficher sur le site', 'type' => 'checkbox', 'default' => 1],
        ];
    }

    public static function index(): void
    {
        Admin::page();
        $maintenant = date('Y-m-d H:i');
        $passes = isset($_GET['passes']);
        $liste = Db::run('SELECT id, title, starts_at, ends_at, address, category, is_published, slug FROM events WHERE ends_at ' . ($passes ? '<' : '>=') . ' ? ORDER BY starts_at ' . ($passes ? 'DESC' : 'ASC'), [$maintenant])->fetchAll();
        $nombres = [
            'avenir' => (int) Db::run('SELECT COUNT(*) FROM events WHERE ends_at >= ?', [$maintenant])->fetchColumn(),
            'passes' => (int) Db::run('SELECT COUNT(*) FROM events WHERE ends_at < ?', [$maintenant])->fetchColumn(),
        ];
        Edition::rendre('admin/agenda-ssa/index', ['title' => 'Agenda', 'evenements' => $liste, 'passes' => $passes, 'nombres' => $nombres]);
    }

    public static function create(): void
    {
        Admin::page();
        self::formulaire(null, ['category' => 'pour_tous', 'is_published' => 1, 'debut' => '18:00', 'fin' => '20:00'], []);
    }

    public static function store(): void
    {
        Admin::action();
        [$valeurs, $erreurs, $ligne] = self::saisie();
        if ($erreurs !== []) {
            self::formulaire(null, $valeurs, $erreurs);
            return;
        }
        $ligne['slug'] = Edition::slugUnique('events', $ligne['title']);
        $ligne['created_at'] = now();
        $cols = array_keys($ligne);
        $id = Db::insert('INSERT INTO events (' . implode(', ', $cols) . ') VALUES (' . implode(', ', array_fill(0, count($cols), '?')) . ')', array_values($ligne));
        Auth::log('agenda.creation', $ligne['title']);
        Session::flash('Événement ajouté. Adresse sur le site : /agenda/' . $ligne['slug'] . '/');
        throw HttpException::redirect(Admin::url('agenda/' . $id . '/'));
    }

    public static function edit(string $id): void
    {
        Admin::page();
        $e = self::trouver($id);
        self::formulaire($e, self::versFormulaire($e), []);
    }

    public static function update(string $id): void
    {
        Admin::action();
        $e = self::trouver($id);
        [$valeurs, $erreurs, $ligne] = self::saisie();
        if ($erreurs !== []) {
            self::formulaire($e, $valeurs, $erreurs);
            return;
        }
        $ligne['updated_at'] = now();
        $set = implode(', ', array_map(static fn (string $c): string => "$c = ?", array_keys($ligne)));
        Db::run("UPDATE events SET $set WHERE id = ?", [...array_values($ligne), (int) $e['id']]);
        Auth::log('agenda.modification', $ligne['title']);
        Session::flash('Événement enregistré.');
        throw HttpException::redirect(Admin::url('agenda/' . (int) $e['id'] . '/'));
    }

    public static function delete(string $id): void
    {
        Admin::action();
        $e = self::trouver($id);
        Db::run('DELETE FROM events WHERE id = ?', [(int) $e['id']]);
        Db::run("DELETE FROM traductions WHERE objet = 'evenement' AND objet_id = ?", [(string) $e['id']]);
        Auth::log('agenda.suppression', (string) $e['title']);
        Session::flash('Événement supprimé.');
        throw HttpException::redirect(Admin::url('agenda/'));
    }

    /** @return array<string, mixed> */
    private static function trouver(string $id): array
    {
        $e = ctype_digit($id) ? Db::run('SELECT * FROM events WHERE id = ?', [(int) $id])->fetch() : false;
        if ($e === false) {
            throw HttpException::notFound();
        }
        return $e;
    }

    /** @param array<string, mixed> $e @return array<string, mixed> */
    private static function versFormulaire(array $e): array
    {
        $debut = (string) $e['starts_at'];
        $fin = (string) $e['ends_at'];
        return [
            'title' => $e['title'], 'category' => $e['category'] ?: 'pour_tous', 'address' => $e['address'], 'description' => $e['description'],
            'links' => $e['links'], 'is_published' => $e['is_published'],
            'date' => substr($debut, 0, 10), 'debut' => substr($debut, 11, 5), 'fin' => substr($fin, 11, 5),
            'date_fin' => substr($fin, 0, 10) !== substr($debut, 0, 10) ? substr($fin, 0, 10) : '',
        ];
    }

    /**
     * Saisie du formulaire, vérifiée. @return array{0: array<string, mixed>, 1: array<string, string>, 2: array<string, mixed>}
     * valeurs à réafficher, erreurs par champ, colonnes de la table
     */
    private static function saisie(): array
    {
        $v = [];
        foreach (array_keys(self::champs()) as $nom) {
            $v[$nom] = $nom === 'is_published' ? (isset($_POST[$nom]) ? 1 : 0) : trim((string) ($_POST[$nom] ?? ''));
        }
        $err = [];
        if ($v['title'] === '') {
            $err['title'] = 'Indiquez le titre.';
        } elseif (mb_strlen($v['title']) > 190) {
            $err['title'] = '190 caractères au plus.';
        }
        if (!isset(Agenda::CATEGORIES[$v['category']])) {
            $err['category'] = 'Choisissez le public.';
        }
        if (!Edition::dateValide($v['date'])) {
            $err['date'] = 'Indiquez la date (jour, mois, année).';
        }
        if (!Edition::heureValide($v['debut'])) {
            $err['debut'] = 'Indiquez l\'heure de début, par exemple 18:00.';
        }
        if (!Edition::heureValide($v['fin'])) {
            $err['fin'] = 'Indiquez l\'heure de fin, par exemple 20:00.';
        }
        if ($v['date_fin'] !== '' && !Edition::dateValide($v['date_fin'])) {
            $err['date_fin'] = 'Date non valable.';
        }
        $debut = $v['date'] . ' ' . $v['debut'];
        $fin = ($v['date_fin'] !== '' ? $v['date_fin'] : $v['date']) . ' ' . $v['fin'];
        $datesLues = !isset($err['date']) && !isset($err['debut']) && !isset($err['fin']) && !isset($err['date_fin']);
        if ($datesLues && $fin <= $debut) {
            $err[$v['date_fin'] !== '' ? 'date_fin' : 'fin'] = 'La fin doit venir après le début.';
        }
        [$liens, $erreurLiens] = Edition::liensParLigne($v['links']);
        if ($erreurLiens !== null) {
            $err['links'] = $erreurLiens;
        }
        $ligne = [
            'title' => $v['title'], 'category' => $v['category'], 'starts_at' => $debut, 'ends_at' => $fin,
            'address' => $v['address'] !== '' ? mb_substr($v['address'], 0, 300) : null,
            'description' => $v['description'] !== '' ? $v['description'] : null,
            'links' => $liens !== [] ? implode("\n", $liens) : null,
            'is_published' => $v['is_published'],
        ];
        return [$v, $err, $ligne];
    }

    /** @param ?array<string, mixed> $e @param array<string, mixed> $valeurs @param array<string, string> $erreurs */
    private static function formulaire(?array $e, array $valeurs, array $erreurs): void
    {
        $titre = $e ? (string) $e['title'] : 'Nouvel événement';
        Edition::rendre('admin/agenda-ssa/form', [
            'title' => $titre, 'e' => $e, 'champs' => self::champs(), 'valeurs' => $valeurs, 'erreurs' => $erreurs,
            'fil' => [['libelle' => 'Tableau de bord', 'url' => Admin::url()], ['libelle' => 'Agenda', 'url' => Admin::url('agenda/')], ['libelle' => $titre]],
            'quand' => $e ? Dates::longue((string) $e['starts_at']) : '',
        ]);
    }
}
