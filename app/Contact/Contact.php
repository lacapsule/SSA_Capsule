<?php
declare(strict_types=1);

namespace App\Contact;

use App\Core\Csrf;
use App\Core\FormGuard;
use App\Core\Mailer;
use App\Core\Session;
use App\Core\View;
use App\Langue\Contenu;
use App\Pages\Pages;
use App\Site\Site;

/**
 * Contact (maquette docs/maquettes/contact.md). Décision du 2026-11-13 : les messages partent par e-mail à l'adresse
 * du collectif et ne sont pas conservés sur le site (envoi immédiat, aucune file en base).
 * Protection (modules/securite-formulaires) : origine, pot de miel, délai signé, limite de débit par IP.
 */
final class Contact
{
    private const FORM = 'contact';
    // Bornes identiques aux maxlength du formulaire.
    private const MAX = ['name' => 120, 'email' => 190, 'objet' => 150, 'message' => 5000];

    public static function form(): void
    {
        Session::resume();
        $old = Session::old(self::FORM);
        $t = Contenu::traduire('page', '/contact/', Pages::textes('/contact/'));
        View::render('pages/contact', [
            'meta' => ['title' => $t['titre'], 'description' => $t['description'], 'h1' => $t['titre']],
            'path' => '/contact/',
            'disponibles' => Contenu::disponibles('page', '/contact/'),
            't' => $t,
            'values' => $old,
            'errors' => is_array($old['_errors'] ?? null) ? $old['_errors'] : [],
            'alert' => is_string($old['_error'] ?? null) ? $old['_error'] : null,
            'sent' => isset($_GET['envoye']),
            'noindex' => isset($_GET['envoye']),
        ], 'site/layout');
    }

    public static function send(): void
    {
        Csrf::checkOrigin();
        $input = FormGuard::keepable($_POST);
        $merci = lurl('/contact/') . '?envoye=1';
        $retour = lurl('/contact/');

        // Robot ou envoi trop rapide : même réponse qu'un envoi réussi (rien ne révèle le filtre).
        if (FormGuard::isBot() || !FormGuard::passes()) {
            redirect($merci);
        }
        if (FormGuard::expired()) {
            self::back($input, t('contact.erreur_expire'), $retour);
        }
        if (FormGuard::rateLimited(self::FORM, 5, 60)) {
            self::back($input, t('contact.erreur_debit'), $retour);
        }

        $champ = static fn (string $k): string => trim((string) ($input[$k] ?? ''));
        $name = $champ('name');
        $email = $champ('email');
        $objet = $champ('objet');
        $message = $champ('message');
        $errors = [];
        if ($name === '' || text_length($name) > self::MAX['name'] || preg_match('/[\r\n]/', $name) === 1) {
            $errors['name'] = t('contact.erreur_nom');
        }
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || text_length($email) > self::MAX['email']) {
            $errors['email'] = t('contact.erreur_email');
        }
        if (text_length($objet) > self::MAX['objet'] || preg_match('/[\r\n]/', $objet) === 1) {
            $errors['objet'] = t('contact.erreur_objet');
        }
        if (text_length($message) < 10 || text_length($message) > self::MAX['message']) {
            $errors['message'] = t('contact.erreur_message');
        }
        if ($errors !== []) {
            Session::old(self::FORM, $input + ['_errors' => $errors]);
            redirect($retour);
        }

        $sujet = 'Site SSA : ' . ($objet !== '' ? $objet : 'message de ' . $name);
        $texte = "Message envoyé depuis le formulaire de contact du site.\n\nNom : $name\nE-mail : $email\n"
            . ($objet !== '' ? "Objet : $objet\n" : '') . "\n$message\n";
        if (!Mailer::send((string) Site::get('email'), $sujet, $texte, replyTo: $email)) {
            self::back($input, t('contact.erreur_envoi', ['email' => (string) Site::get('email')]), $retour);
        }
        redirect($merci);
    }

    /** @param array<string, mixed> $input */
    private static function back(array $input, string $alert, string $retour): never
    {
        Session::old(self::FORM, $input + ['_error' => $alert]);
        redirect($retour);
    }
}
