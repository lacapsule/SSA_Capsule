<?php

declare(strict_types=1);

namespace CapsuleLib\Core;

/**
 * Classe de base pour tous les contrôleurs.
 *
 * Fournit des méthodes utilitaires pour le rendu de vues avec layout
 * et la redirection interne vers des routes.
 *
 *
 * @version 1.0
 */
abstract class RenderController
{
    /**
     * Rend une vue HTML avec un layout global.
     *
     * @param string $template  Nom du fichier de vue à inclure, relatif au dossier /templates/.
     *                          Ex: 'pages/home.php'
     * @param array<string, mixed> $data  Données à injecter dans la vue (accessibles comme variables).
     *
     * @return string  Contenu HTML complet prêt à être affiché.
     *
     * @throws \InvalidArgumentException Si le fichier de template n'existe pas.
     */
    protected function renderView(string $template, array $data = []): string
    {
        $templatePath = dirname(__DIR__, 2) . '/templates/' . $template;

        if (!file_exists($templatePath)) {
            throw new \InvalidArgumentException("Template not found: {$templatePath}");
        }

        extract($data, EXTR_SKIP);
        ob_start();
        require $templatePath;
        $viewContent = ob_get_clean();

        ob_start();
        include dirname(__DIR__, 2) . '/templates/layout.php';
        return ob_get_clean();
    }

    /**
     * Redirige vers une autre route interne (via paramètre GET ?path=...).
     *
     * @param string $path   Route cible (ex: '/contact').
     * @param array<string, string|int|float> $params  Paramètres GET à inclure dans l'URL.
     *
     * @return never  Termine immédiatement l'exécution via `die()`.
     */
    protected function redirectToRoute(string $path, array $params = []): never
    {
        $uri = $_SERVER['SCRIPT_NAME'] . "?path=" . $path;

        if (!empty($params)) {
            $strParams = [];
            foreach ($params as $key => $val) {
                $strParams[] = urlencode((string) $key) . '=' . urlencode((string) $val);
            }
            $uri .= '&' . implode('&', $strParams);
        }

        header("Location: " . $uri);
        die;
    }

    /**
     * Rend un composant réutilisable (sans layout global).
     *
     * @param string $componentPath Chemin relatif à `templates/components/` (ex: 'hero.php')
     * @param array<string, mixed> $data Données injectées dans le scope local du composant
     *
     * @return string HTML du composant
     *
     * @throws \InvalidArgumentException Si le fichier de composant est introuvable
     */
    protected function renderComponent(string $componentPath, array $data = []): string
    {
        $path = dirname(__DIR__, 2) . '/templates/components/' . $componentPath;

        if (!file_exists($path)) {
            throw new \InvalidArgumentException("Component not found: {$path}");
        }

        extract($data, EXTR_SKIP);
        ob_start();
        require $path;
        return ob_get_clean();
    }
}
