<?php

declare(strict_types=1);

namespace CapsuleLib\Contracts;

interface ViewRendererInterface
{
    /** @param array<string,mixed> $data */
    public function renderView(string $template, array $data = []): string;

    /** @param array<string,mixed> $data */
    public function renderComponent(string $component, array $data = []): string;
}
