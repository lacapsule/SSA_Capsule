<?php

declare(strict_types=1);

namespace App\Controller;

use App\Lang\TranslationLoader;
use CapsuleLib\Core\RenderController;
use CapsuleLib\Http\RequestUtils;
use CapsuleLib\Http\FlashBag;
use CapsuleLib\Security\CsrfTokenManager;


final class CalendarController extends RenderController
{
    public function index(): void
    {
        echo $this->renderView('calendar/home.php', [
            'title'       => 'Calendrier',
            'isDashboard' => false,
            'isAdmin'     => isset($_SESSION['admin']),
            'user'        => $_SESSION['admin'] ?? [],
            'str'         => TranslationLoader::load(defaultLang: 'fr'),
        ]);
    }

    public function handlePost(): void
    {

        $this->generateICS();
        // Gérer la soumission du formulaire
        //
        //

        // if (isset($_POST['article_id']) && $_POST['article_id'] === 'generer_ics') {
        //     $this->generateICS();
        // } else {
        //     // Gérer le cas où 'article_id' n'est pas défini ou a une autre valeur
        //     echo "ID d'événement non spécifié ou invalide.";
        // }

        // if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        //     header('Location: /dashboard/articles/', true, 303);
        //     return;
        // }


    }

    public function generateICS(): void
    {

        // Exemple de données d'événement
        $date_debut = strtotime('2025-09-10 14:00:00');
        $date_fin = strtotime('2025-09-10 15:30:00');
        $objet = "Titre de l'événement";
        $lieu = "Paris";
        $details = "Description de l'événement";

        // Génération du contenu ICS
        $ics = "BEGIN:VCALENDAR\n";
        $ics .= "VERSION:2.0\n";
        $ics .= "PRODID:-//MonSite//FR\n";
        $ics .= "BEGIN:ARTICLE\n";
        $ics .= "DTSTART:" . date('Ymd\THis\Z', $date_debut) . "\n";
        $ics .= "DTEND:" . date('Ymd\THis\Z', $date_fin) . "\n";
        $ics .= "SUMMARY:" . addcslashes($objet, ",;\\") . "\n";
        $ics .= "LOCATION:" . addcslashes($lieu, ",;\\") . "\n";
        $ics .= "DESCRIPTION:" . addcslashes($details, ",;\\") . "\n";
        $ics .= "END:VARTICLE\n";
        $ics .= "END:VCALENDAR";

        // Envoi des en-têtes pour le téléchargement du fichier ICS
        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="article.ics"');
        echo $ics;
    }
}
