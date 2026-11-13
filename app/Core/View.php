<?php
declare(strict_types=1);

namespace App\Core;

/** Gabarits PHP de templates/ : contenu capturé puis inséré dans un gabarit de page ($content, déjà échappé par le contenu). */
final class View
{
    /** @param array<string, mixed> $data */
    public static function render(string $template, array $data = [], ?string $layout = null): void
    {
        $content = self::capture($template, $data);
        echo $layout === null ? $content : self::capture($layout, $data + ['content' => $content]);
    }

    /** @param array<string, mixed> $data */
    public static function capture(string $template, array $data = []): string
    {
        // Nom de gabarit écrit dans le code, jamais tiré de la requête : refusé s'il sort de templates/.
        if (preg_match('#^[a-z0-9_-]+(/[a-z0-9_-]+)*$#', $template) !== 1) {
            throw new \InvalidArgumentException("Nom de gabarit invalide : $template");
        }
        $__file = ROOT . '/templates/' . $template . '.php';
        extract($data, EXTR_SKIP);
        ob_start();
        try {
            require $__file;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return (string) ob_get_clean();
    }
}
