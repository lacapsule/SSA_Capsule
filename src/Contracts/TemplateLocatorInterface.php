<?php

declare(strict_types=1);

namespace Capsule\Contracts;

/**
 * Mappe un nom logique → chemin absolu lisible.
 * Exemples:
 *  - page:dashboard/home      → .../templates/dashboard/home.tpl.php
 *  - component:dashboard/user → .../templates/components/dashboard/user.tpl.php
 *  - partial:header           → .../templates/partials/header.tpl.php
 */
interface TemplateLocatorInterface
{
    /**
     * @return string Chemin absolu existant et lisible.
     *
     * Invariants:
     * - Deny-by-default : seuls les prefixes configurés sont résolus.
     * - Pas de traversal (..), pas de null byte, pas de CR/LF.
     * - Résultat DOIT être sous la racine whitelistée du prefix.
     * - Extension normalisée (ex: .tpl.php).
     */
    public function locate(string $logicalName): string;
}
