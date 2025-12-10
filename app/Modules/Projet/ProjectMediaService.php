<?php

declare(strict_types=1);

namespace App\Modules\Projet;

use App\Support\ImageConverter;

final class ProjectMediaService
{
    private const DATA_FILE = '/data/projet_media.json';
    private const DEFAULTS = [
        'hero' => '/assets/img/projet/projetssa.webp',
        'illustration_top' => '/assets/img/projet/peep-102.svg',
        'illustration_bottom' => '/assets/img/projet/illustration_projet_ssa.png',
    ];

    /**
     * @return array{hero:string,illustration_top:string,illustration_bottom:string}
     */
    public function getMedia(): array
    {
        $data = $this->load();
        return [
            'hero' => $data['hero'] ?? self::DEFAULTS['hero'],
            'illustration_top' => $data['illustration_top'] ?? self::DEFAULTS['illustration_top'],
            'illustration_bottom' => $data['illustration_bottom'] ?? self::DEFAULTS['illustration_bottom'],
        ];
    }

    /**
     * @param array<string,array{tmp_name:string,name:string,type:string,error:int,size:int}> $files
     * @return array{success:bool,errors?:array<string,string>}
     */
    public function update(array $files): array
    {
        $data = $this->load();
        $errors = [];
        $updated = [];

        foreach (['hero', 'illustration_top', 'illustration_bottom'] as $key) {
            if (!isset($files[$key])) {
                continue;
            }
            $file = $files[$key];
            $error = $file['error'] ?? \UPLOAD_ERR_NO_FILE;
            if ($error === \UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if ($error !== \UPLOAD_ERR_OK) {
                $errors[$key] = 'Upload invalide';
                continue;
            }
            $saved = $this->saveImage($file, $key);
            if ($saved === null) {
                $errors[$key] = 'Impossible de sauvegarder le fichier';
                continue;
            }
            $updated[$key] = $saved;
        }

        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors];
        }

        if ($updated !== []) {
            $merged = array_merge($data, $updated);
            $this->persist($merged);
        }

        return ['success' => true];
    }

    private function saveImage(array $file, string $name): ?string
    {
        $destDir = $this->projectAssetsDir();
        if (!is_dir($destDir)) {
            @mkdir($destDir, 0775, true);
        }

        $filename = ImageConverter::convertUploadedFileForGallery(
            $file,
            $name,
            $destDir
        );

        if ($filename === null) {
            return null;
        }

        return '/assets/img/projet/' . $filename;
    }

    private function load(): array
    {
        $file = $this->dataFilePath();
        if (!is_file($file)) {
            return [];
        }
        $raw = file_get_contents($file);
        if ($raw === false) {
            return [];
        }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    private function persist(array $data): void
    {
        $file = $this->dataFilePath();
        $dir = dirname($file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private function dataFilePath(): string
    {
        $base = realpath(__DIR__ . '/../../..') ?: dirname(__DIR__, 3);
        return $base . self::DATA_FILE;
    }

    private function projectAssetsDir(): string
    {
        $base = realpath(__DIR__ . '/../../..') ?: dirname(__DIR__, 3);
        return $base . '/public/assets/img/projet';
    }
}

