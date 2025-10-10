<?php

namespace Capsule\Contracts;

/** Rendu de templates PHP “safe-by-default”. */
interface ViewRendererInterface
{
    /**
     * @param array<string,mixed> $data
     * @return string HTML
     *
     * Invariants:
     * - $templatePath est relatif (pas de .., pas de null byte)
     * - Encodage sortie = UTF-8
     * - Layout optionnel géré par l’implémentation
     */
    public function render(string $templatePath, array $data = []): string;

    /**
     * @param array<string,mixed> $data
     * @return string HTML fragment
     */
    public function renderComponent(string $componentPath, array $data = []): string;
}
