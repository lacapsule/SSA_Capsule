<?php

declare(strict_types=1);

namespace Capsule\Domain\Service;

use Capsule\Domain\Repository\PartnerLogoRepository;
use Capsule\Domain\Repository\PartnerSectionRepository;

/**
 * PartnersService
 * - Gère les sections de partenaires et leurs logos.
 * - Fournit des méthodes simples pour le dashboard et la home.
 */
final class PartnersService
{
    private const MAX_LOGO_SIZE = 2_097_152; // 2 Mo
    private const ALLOWED_LOGO_MIMES = [
        'image/png',
        'image/jpeg',
        'image/webp',
        'image/gif',
        'image/svg+xml',
    ];

    public function __construct(
        private readonly PartnerSectionRepository $sections,
        private readonly PartnerLogoRepository $logos,
    ) {
    }

    /**
     * @return array<int,array{
     *   id:int,name:string,slug:string,description:?string,kind:string,
     *   position:int,is_active:int,logos:array<int,array{id:int,name:string,url:string,logo:string,position:int}>
     * }>
     */
    public function getSectionsWithLogos(?string $kind = null): array
    {
        $sections = $kind === null
            ? $this->sections->findAllOrdered()
            : $this->sections->findByKind($kind);

        foreach ($sections as &$section) {
            $logos = $this->logos->findBySection($section['id']);
            $section['logos'] = array_map(
                function (array $row) {
                    $logoPath = (string) $row['logo_path'];
                    // Normaliser le chemin pour l'affichage public
                    // S'assurer qu'il commence par /assets/ et est valide
                    $normalizedPath = $this->normalizeLogoPath($logoPath);
                    return [
                        'id' => (int) $row['id'],
                        'name' => (string) $row['name'],
                        'url' => (string) $row['url'],
                        'logo' => $normalizedPath,
                        'position' => (int) $row['position'],
                    ];
                },
                $logos
            );
        }

        return $sections;
    }

    /**
     * @return array<int,array{name:string,role:string,url:string,logo:string}>
     */
    public function getFlatListByKind(string $kind): array
    {
        $sections = $this->getSectionsWithLogos($kind);
        $flat = [];

        foreach ($sections as $section) {
            foreach ($section['logos'] as $logo) {
                $flat[] = [
                    'name' => $logo['name'],
                    'role' => $kind,
                    'url' => $logo['url'],
                    'logo' => $logo['logo'],
                ];
            }
        }

        return $flat;
    }

    /**
     * @param array{name:string,description?:string,kind:string,position?:int,is_active?:int} $data
     */
    public function createSection(array $data): int
    {
        $name = trim($data['name'] ?? '');
        if ($name === '' || mb_strlen($name) > 255) {
            throw new \RuntimeException('Le nom de la section est requis et ne doit pas dépasser 255 caractères.');
        }

        return $this->sections->create([
            'name' => $name,
            'slug' => $this->slugify($name),
            'description' => isset($data['description']) ? trim((string) $data['description']) : null,
            'kind' => $this->sanitizeKind($data['kind'] ?? 'partenaire'),
            'position' => isset($data['position']) ? (int) $data['position'] : 0,
            'is_active' => isset($data['is_active']) ? (int) $data['is_active'] : 1,
        ]);
    }

    /**
     * @param array{name?:string,description?:string,kind?:string,position?:int,is_active?:int} $data
     */
    public function updateSection(int $sectionId, array $data): void
    {
        $section = $this->sections->findById($sectionId);
        if ($section === null) {
            throw new \RuntimeException('Section introuvable.');
        }

        $payload = [];

        if (array_key_exists('name', $data) && $data['name'] !== null) {
            $name = trim((string) $data['name']);
            if ($name === '' || mb_strlen($name) > 255) {
                throw new \RuntimeException('Le nom de la section est requis et ne doit pas dépasser 255 caractères.');
            }
            $payload['name'] = $name;
            $payload['slug'] = $this->slugify($name);
        }

        if (array_key_exists('description', $data)) {
            $payload['description'] = $data['description'] !== '' && $data['description'] !== null
                ? trim((string) $data['description'])
                : null;
        }

        if (array_key_exists('kind', $data) && $data['kind'] !== null) {
            $payload['kind'] = $this->sanitizeKind((string) $data['kind']);
        }

        if (array_key_exists('position', $data) && $data['position'] !== null) {
            $payload['position'] = (int) $data['position'];
        }

        if (array_key_exists('is_active', $data)) {
            $payload['is_active'] = isset($data['is_active']) ? (int) $data['is_active'] : 0;
        }

        if ($payload === []) {
            return;
        }

        $this->sections->update($sectionId, $payload);
    }

    public function deleteSection(int $sectionId): void
    {
        $section = $this->sections->findById($sectionId);
        if ($section === null) {
            throw new \RuntimeException('Section introuvable.');
        }

        // Supprimer tous les logos de la section
        $logos = $this->logos->findBySection($sectionId);
        foreach ($logos as $logo) {
            $this->deleteLogoFile($logo['logo_path']);
        }
        $this->logos->deleteBySection($sectionId);

        // Supprimer la section
        $this->sections->deleteSection($sectionId);
    }

    /**
     * @param array{name:string,url:string,position?:int} $data
     */
    public function createLogo(int $sectionId, array $data, array $file): void
    {
        // Vérifier que la section existe
        $section = $this->sections->findById($sectionId);
        if ($section === null) {
            throw new \RuntimeException('Section introuvable.');
        }

        // Valider les données
        $name = trim($data['name'] ?? '');
        $url = trim($data['url'] ?? '');

        if ($name === '' || mb_strlen($name) > 255) {
            throw new \RuntimeException('Le nom du logo est requis et ne doit pas dépasser 255 caractères.');
        }

        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \RuntimeException('Une URL valide est requise.');
        }

        $this->assertValidUpload($file);
        $customName = $data['custom_name'] ?? null;
        $path = $this->storeLogoFile($sectionId, $file, $customName);
        if ($path === null) {
            throw new \RuntimeException('Impossible d\'enregistrer ce logo.');
        }

        $this->logos->create([
            'section_id' => $sectionId,
            'name' => $name,
            'url' => $url,
            'logo_path' => $path,
            'position' => isset($data['position']) ? (int) $data['position'] : 0,
        ]);
    }

    /**
     * @param array{name?:string,url?:string,position?:int} $data
     */
    public function updateLogo(int $logoId, array $data, ?array $file = null): void
    {
        $logo = $this->logos->findById($logoId);
        if ($logo === null) {
            throw new \RuntimeException('Logo introuvable.');
        }

        $payload = [];

        if (array_key_exists('name', $data) && $data['name'] !== null) {
            $name = trim((string) $data['name']);
            if ($name === '' || mb_strlen($name) > 255) {
                throw new \RuntimeException('Le nom du logo est requis et ne doit pas dépasser 255 caractères.');
            }
            $payload['name'] = $name;
        }

        if (array_key_exists('url', $data) && $data['url'] !== null) {
            $url = trim((string) $data['url']);
            if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
                throw new \RuntimeException('Une URL valide est requise.');
            }
            $payload['url'] = $url;
        }

        if (array_key_exists('position', $data) && $data['position'] !== null) {
            $payload['position'] = (int) $data['position'];
        }

        if ($file !== null) {
            $this->assertValidUpload($file);
            $customName = $data['custom_name'] ?? null;
            $path = $this->storeLogoFile((int) $logo['section_id'], $file, $customName);
            if ($path !== null) {
                $this->deleteLogoFile($logo['logo_path']);
                $payload['logo_path'] = $path;
            }
        }

        if ($payload !== []) {
            $this->logos->update($logoId, $payload);
        }
    }

    public function deleteLogo(int $logoId): void
    {
        $logo = $this->logos->findById($logoId);
        if ($logo === null) {
            return;
        }

        $this->logos->delete($logoId);
        $this->deleteLogoFile($logo['logo_path']);
    }

    public function getSection(int $sectionId): ?array
    {
        return $this->sections->findById($sectionId);
    }

    private function sanitizeKind(string $kind): string
    {
        return in_array($kind, ['partenaire', 'financeur'], true) ? $kind : 'partenaire';
    }

    private function slugify(string $name): string
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug) ?? '';
        $slug = trim($slug, '-');

        return $slug !== '' ? $slug : 'section-' . uniqid();
    }

    private function storeLogoFile(int $sectionId, array $file, ?string $customName = null): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        $basePath = realpath(__DIR__ . '/../../..') ?: dirname(__DIR__, 2);
        $destDir = $basePath . '/public/assets/img/logos';
        if (!is_dir($destDir)) {
            @mkdir($destDir, 0755, true);
        }

        // Utiliser convertUploadedFileForGallery pour conversion WebP comme la galerie
        $filename = \App\Support\ImageConverter::convertUploadedFileForGallery(
            $file,
            $customName,
            $destDir,
            quality: 90,
            maxWidth: 800
        );

        if ($filename === null) {
            return null;
        }

        // Retourner le chemin public complet
        return '/assets/img/logos/' . $filename;
    }

    public function createLogoFromExistingFile(int $sectionId, array $data, string $publicPath): void
    {
        // Vérifier que la section existe
        $section = $this->sections->findById($sectionId);
        if ($section === null) {
            throw new \RuntimeException('Section introuvable.');
        }

        $name = trim($data['name'] ?? '');
        $url = trim($data['url'] ?? '');

        if ($name === '' || mb_strlen($name) > 255) {
            throw new \RuntimeException('Le nom du logo est requis et ne doit pas dépasser 255 caractères.');
        }

        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \RuntimeException('Une URL valide est requise.');
        }

        // Sécurité : vérifier que le chemin public est valide
        // Accepter les anciens chemins (/assets/img/partners/) pour compatibilité et les nouveaux (/assets/img/logos/)
        if ($publicPath === '' || (!str_starts_with($publicPath, '/assets/img/logos/') && !str_starts_with($publicPath, '/assets/img/partners/') && !str_starts_with($publicPath, '/assets/'))) {
            throw new \RuntimeException('Chemin de logo invalide. Doit commencer par /assets/img/logos/ ou /assets/img/partners/');
        }

        $this->logos->create([
            'section_id' => $sectionId,
            'name' => $name,
            'url' => $url,
            'logo_path' => $publicPath,
            'position' => isset($data['position']) ? (int) $data['position'] : 0,
        ]);
    }

    private function deleteLogoFile(string $publicPath): void
    {
        $absolute = $this->absolutePath($publicPath);
        if (is_file($absolute)) {
            @unlink($absolute);
        }
    }

    private function absolutePath(string $publicPath): string
    {
        $basePath = realpath(__DIR__ . '/../../..') ?: dirname(__DIR__, 2);
        $clean = '/' . ltrim($publicPath, '/');

        return $basePath . '/public' . $clean;
    }


    private function assertValidUpload(array $file): void
    {
        $size = (int)($file['size'] ?? 0);
        if ($size <= 0 || $size > self::MAX_LOGO_SIZE) {
            throw new \RuntimeException('Logo trop volumineux (max 2 Mo).');
        }

        $tmp = $file['tmp_name'] ?? '';
        if (!is_file($tmp)) {
            throw new \RuntimeException('Fichier temporaire introuvable.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string)$finfo->file($tmp);
        if (!in_array($mime, self::ALLOWED_LOGO_MIMES, true)) {
            throw new \RuntimeException('Format de logo non supporté.');
        }
    }

    /**
     * Normalise un chemin de logo pour l'affichage public.
     * S'assure que le chemin commence par /assets/ et est valide.
     */
    private function normalizeLogoPath(string $path): string
    {
        // Nettoyer le chemin
        $path = trim($path);
        if ($path === '') {
            return '/assets/img/logoSSA.png'; // Fallback
        }

        // S'assurer que le chemin commence par /
        if (!str_starts_with($path, '/')) {
            $path = '/' . $path;
        }

        // S'assurer que le chemin commence par /assets/
        if (!str_starts_with($path, '/assets/')) {
            // Si le chemin ne commence pas par /assets/, essayer de le corriger
            // Supprimer les préfixes incorrects
            $path = preg_replace('#^/(public/|assets/)?#', '', $path);
            $path = '/assets/' . ltrim($path, '/');
        }

        // Normaliser les séparateurs de chemin (remplacer \ par /)
        $path = str_replace('\\', '/', $path);

        // Supprimer les doubles slashes
        $path = preg_replace('#/+#', '/', $path);

        return $path;
    }
}


