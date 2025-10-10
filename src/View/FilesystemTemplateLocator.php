<?php

declare(strict_types=1);

namespace Capsule\View;

use Capsule\Contracts\TemplateLocatorInterface;

final class FilesystemTemplateLocator implements TemplateLocatorInterface
{
    /**
     * @param array<string,string> $roots ex:
     *  ['page'=>'.../templates/pages', 'component'=>'.../templates/components',
     *   'partial'=>'.../templates/partials', 'layout'=>'.../templates']
     */
    public function __construct(
        private array $roots,
        private string $defaultExt = '.tpl.php'
    ) {
        // Normalisation minimale des racines
        foreach ($this->roots as $k => $dir) {
            $real = realpath($dir);
            if ($real === false) {
                throw new \InvalidArgumentException("Template root not found for '{$k}': {$dir}");
            }
            $this->roots[$k] = rtrim($real, DIRECTORY_SEPARATOR);
        }
    }

    public function locate(string $logicalName): string
    {
        // Format attendu : "prefix:path/inside"
        $pos = strpos($logicalName, ':');
        $prefix = $pos === false ? 'page' : substr($logicalName, 0, $pos);
        $path = $pos === false ? $logicalName : substr($logicalName, $pos + 1);

        $base = $this->roots[$prefix] ?? null;
        if ($base === null) {
            throw new \InvalidArgumentException("Unknown template prefix: {$prefix}");
        }

        // Entrées invalides
        if (
            $path === '' ||
            str_contains($path, "\0") ||
            str_contains($path, "\r") ||
            str_contains($path, "\n") ||
            str_starts_with($path, '/') ||
            str_contains($path, '..')
        ) {
            throw new \InvalidArgumentException("Invalid template path: {$path}");
        }

        // Extension forcée si absente
        if (!str_ends_with($path, $this->defaultExt)) {
            $path .= $this->defaultExt;
        }

        $abs = $base . DIRECTORY_SEPARATOR . str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path);
        $real = realpath($abs);
        if ($real === false) {
            throw new \InvalidArgumentException("Template not found: {$abs}");
        }

        // Confinement à la racine
        if (!str_starts_with($real, $base . DIRECTORY_SEPARATOR)) {
            throw new \InvalidArgumentException("Template outside of base: {$real}");
        }

        // Lisibilité
        if (!is_file($real) || !is_readable($real)) {
            throw new \InvalidArgumentException("Template not readable: {$real}");
        }

        return $real;
    }
}
