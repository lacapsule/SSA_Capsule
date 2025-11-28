#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Script d'import des partenaires statiques depuis PartnersProvider vers la base de données.
 * 
 * Usage: php bin/import_partners_from_provider.php
 * 
 * Ce script est destiné à être exécuté manuellement une seule fois pour migrer
 * les données statiques vers la base de données. Il ne doit pas être exécuté
 * automatiquement en production.
 */

use App\Providers\PartnersProvider;
use Capsule\Infrastructure\Database\SqliteConnection;
use Capsule\Domain\Repository\PartnerSectionRepository;
use Capsule\Domain\Repository\PartnerLogoRepository;
use Capsule\Domain\Service\PartnersService;

$baseDir = dirname(__DIR__);
require $baseDir . '/src/Autoload.php';

try {
    $pdo = SqliteConnection::getInstance();
    $sectionRepo = new PartnerSectionRepository($pdo);
    $logoRepo = new PartnerLogoRepository($pdo);
    $service = new PartnersService($sectionRepo, $logoRepo);

    $provider = new PartnersProvider();
    $groups = [
        'partenaire' => $provider->byRole('partenaire'),
        'financeur' => $provider->byRole('financeur'),
    ];

    echo "Import des partenaires statiques...\n\n";

    foreach ($groups as $kind => $entries) {
        if ($entries === []) {
            echo "  ⚠️  Aucun partenaire de type '{$kind}' trouvé.\n";
            continue;
        }

        // Vérifier si une section existe déjà pour ce type
        $existingSections = $sectionRepo->findByKind($kind);
        $sectionId = null;

        if ($existingSections !== []) {
            $section = $existingSections[0];
            $sectionId = $section['id'];
            echo "  ℹ️  Section '{$kind}' existe déjà (ID: {$sectionId}). Utilisation de cette section.\n";
        } else {
            // Créer une nouvelle section
            $sectionId = $service->createSection([
                'name' => ucfirst($kind),
                'description' => "Section importée depuis PartnersProvider ({$kind})",
                'kind' => $kind,
                'position' => $kind === 'partenaire' ? 0 : 1,
                'is_active' => 1,
            ]);
            echo "  ✅ Section '{$kind}' créée (ID: {$sectionId}).\n";
        }

        $imported = 0;
        $skipped = 0;

        // Récupérer les logos existants pour cette section
        $existingLogos = $logoRepo->findBySection($sectionId);
        $existingLogoPaths = array_column($existingLogos, 'logo_path');

        foreach ($entries as $index => $entry) {
            $logoPath = $entry['logo'] ?? '';
            
            // Sécurité : vérifier que le chemin est valide et commence par /assets/
            // Accepter les anciens chemins (/assets/img/) et les nouveaux (/assets/img/logos/)
            if ($logoPath === '' || !str_starts_with($logoPath, '/assets/')) {
                printf("  ⚠️  Logo ignoré pour %s (chemin invalide: %s)\n", $entry['name'], $logoPath);
                $skipped++;
                continue;
            }

            // Vérifier si le logo existe déjà
            if (in_array($logoPath, $existingLogoPaths, true)) {
                printf("  ℹ️  Logo déjà présent pour %s (skip)\n", $entry['name']);
                $skipped++;
                continue;
            }

            $absolutePath = $baseDir . '/public' . $logoPath;
            
            if (!file_exists($absolutePath)) {
                printf("  ⚠️  Logo introuvable pour %s (%s)\n", $entry['name'], $logoPath);
                $skipped++;
                continue;
            }

            try {
                $service->createLogoFromExistingFile(
                    $sectionId,
                    [
                        'name' => $entry['name'],
                        'url' => $entry['url'] ?? '#',
                        'position' => $index,
                    ],
                    $logoPath
                );

                printf("  ✅ %s importé (%s)\n", $entry['name'], $kind);
                $imported++;
            } catch (\Throwable $e) {
                printf("  ❌ Erreur lors de l'import de %s: %s\n", $entry['name'], $e->getMessage());
                $skipped++;
            }
        }

        echo "  📊 Résumé {$kind}: {$imported} importés, {$skipped} ignorés.\n\n";
    }

    echo "Import terminé.\n";
    exit(0);
} catch (\Throwable $e) {
    echo "❌ Erreur fatale: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

