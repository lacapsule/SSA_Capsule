<?php

declare(strict_types=1);

namespace App\Providers;

/**
 * PartnersProvider
 * - Source unique pour partenaires/financeurs (config statique ou chargement léger).
 * - Compatible preload/OPcache : données immuables en production.
 *
 * Invariants :
 * - Données de forme {name,role,url,logo}.
 * - Jamais d'I/O coûteuse par requête (si chargement fichier, cache mémoire process).
 */
final class PartnersProvider
{
    /** @var array<array{name:string,role:string,url:string,logo:string}> */
    private array $all;

    /**
     * @param array<array{name:string,role:string,url:string,logo:string}> $seed
     */
    public function __construct(array $seed = [])
    {
        // Defaults si aucun seed fourni : les tiens (extraits du contrôleur)
        $this->all = $seed ?: [
            ['name' => 'BUZUK', 'role' => 'partenaire', 'url' => 'https://buzuk.bzh/', 'logo' => '/assets/img/buzuk.webp'],
            ['name' => 'Région Bretagne', 'role' => 'financeur', 'url' => 'https://www.bretagne.bzh/', 'logo' => '/assets/img/bretagne.webp'],
            ['name' => 'ULAMIR-CPIE', 'role' => 'partenaire', 'url' => 'https://ulamir-cpie.bzh/', 'logo' => '/assets/img/ulamircpie.webp'],
            ['name' => 'Pôle ESS Pays de Morlaix', 'role' => 'partenaire', 'url' => 'https://www.adess29.fr/faire-reseau/le-pole-du-pays-de-morlaix/', 'logo' => '/assets/img/ess.webp'],
            ['name' => 'RESAM', 'role' => 'partenaire', 'url' => 'https://www.resam.net/', 'logo' => '/assets/img/resam.webp'],
            ['name' => 'Leader financement Européen', 'role' => 'financeur', 'url' => 'https://leaderfrance.fr/le-programme-leader/', 'logo' => '/assets/img/feader.webp'],
        ];
    }

    /** @return array<array{name:string,role:string,url:string,logo:string}> */
    public function all(): array
    {
        return $this->all;
    }

    /** @return array<array{name:string,role:string,url:string,logo:string}> */
    public function byRole(string $role): array
    {
        $out = [];
        foreach ($this->all as $p) {
            if ($p['role'] === $role) {
                $out[] = $p;
            }
        }

        return $out;
    }
}
