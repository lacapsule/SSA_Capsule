<?php

declare(strict_types=1);

namespace App\Controller;

use App\Lang\TranslationLoader;
use App\Service\ArticleService;
use CapsuleLib\Core\RenderController;
use CapsuleLib\Http\RequestUtils;

// TODO: : Mettre les articles dans le home
final class HomeController extends RenderController
{
    private ?array $strings = null;

    public function __construct(private ArticleService $articleService) {}

    /* ---------- Helpers ---------- */

    private function strings(): array
    {
        return $this->strings ??= TranslationLoader::load(defaultLang: 'fr');
    }

    /**
     * Base payload commun aux pages publiques.
     * @param array $extra Variables spécifiques à la vue
     * @param bool  $withArticles Injecte les articles à venir si true
     */
    private function base(array $extra = [], bool $withArticles = true): array
    {
        $base = [
            'showHeader' => true,
            'showFooter' => true,
            'str'        => $this->strings(),
        ];

        if ($withArticles) {
            $base['articles'] = $this->articleService->getUpcoming();
        }

        return array_replace($base, $extra);
    }

    /* ---------- Pages ---------- */

    public function home(): void
    {
        $articles = $this->articleService->getUpcoming();
        echo $this->renderView('pages/home.php', $this->base(['articles' => $articles]));
    }

    public function projet(): void
    {
        echo $this->renderView('pages/projet.php', $this->base());
    }

    public function galerie(): void
    {
        echo $this->renderView('pages/galerie.php', $this->base());
    }

    public function article(string|array $params): void
    {
        $id = is_array($params) ? (int)($params['id'] ?? 0) : (int)$params;
        if ($id <= 0) {
            http_response_code(400);
            echo 'Bad Request';
            return;
        }

        $dto = $this->articleService->getById($id);
        if (!$dto) {
            http_response_code(404);
            echo 'Not Found';
            return;
        }

        echo $this->renderView('pages/articleDetails.php', $this->base([
            'article' => $dto,
        ], /* withArticles */ false));
    }

    public function contactMail(): void
    {
        RequestUtils::ensurePostOrRedirect('/contact');

        if (isset($_POST['message']) && isset($_POST['email']) && isset($_POST['name'])) {
            $to = 'aurelien.corre@outlook.fr';
            $subject = 'Message de ' . $_POST['name'];
            $content = htmlspecialchars($_POST['message']);

            $message = '
            <html>
                <head>
                    <title>Nouveau message de ' . $_POST['name'] . '</title>
                </head>
                <body>
                    <p>' . $content . '</p>
                </body>
            </html>
     ';

            $headers[] = 'MIME-Version: 1.0';
            $headers[] = 'Content-type: text/html; charset=iso-8859-1';
            $headers[] = 'From: ' . $_POST['email'];
            $headers[] = 'Reply-To: ' . $_POST['email'];
            $headers[] = 'X-Mailer: PHP/' . phpversion();

            $sent = mail($to, $subject, $message, implode("\r\n", $headers));

            if ($sent) {
                echo "Votre message a bien été envoyé.";
            } else {
                $error = error_get_last();
                $details = isset($error['message']) ? $error['message'] : 'Erreur inconnue.';
                echo "Une erreur est survenue lors de l'envoi :<br><pre>{$details}</pre>";
            }
        } else {
            echo "Veuillez remplir tous les champs du formulaire.";
        }
    }
}
