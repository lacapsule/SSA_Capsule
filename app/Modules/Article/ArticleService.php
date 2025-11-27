<?php

declare(strict_types=1);

namespace App\Modules\Article;

use App\Modules\Article\Dto\ArticleDTO;
use App\Support\ImageConverter;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * ArticleService
 *
 * Rôle :
 * - Orchestration métier autour des articles (lecture/écriture).
 * - Ne fait AUCUNE projection vers la vue (pas de formatage date/heure pour templates).
 *
 * Invariants :
 * - Les méthodes de lecture renvoient des flux paresseux (iterable<ArticleDTO>).
 * - Les mutations appliquent sanitize/validate avant persistance.
 * - Aucune dépendance à la session/HTTP ici.
 */
final class ArticleService
{
    private const SUPPORTED_FS_MEDIA_EXT = ['webp', 'jpg', 'jpeg', 'png', 'gif', 'mp4', 'webm', 'ogv', 'ogg', 'mov'];
    public function __construct(
        private ArticleRepository $articleRepository,
        private ArticleImageRepository $imageRepository,
    )
    {
    }

    /** Champs requis et optionnels pour create/update */
    private const REQUIRED_FIELDS = ['titre', 'resume', 'description', 'date_article', 'hours'];
    private const OPTIONAL_FIELDS = ['lieu'];

    /* =======================
       ======= Queries =======
       ======================= */

    public function countUpcoming(): int
    {
        return $this->articleRepository->countUpcoming();
    }

    public function getAllPaginated(int $limit, int $offset): iterable
    {
        $rows = $this->articleRepository->findAllPaginated($limit, $offset);
        return $this->asIterable($rows);
    }

    public function countAll(): int
    {
        return $this->articleRepository->countAll();
    }

    /**
     * Liste paginée (flux paresseux).
     * @return iterable<ArticleDTO>
     */
    public function getUpcomingPage(int $limit, int $offset): iterable
    {
        // Le repo peut retourner array|iterable — on unifie en flux paresseux
        $rows = $this->articleRepository->findUpcoming($limit, $offset);

        return $this->asIterable($rows);
    }

    /**
     * Tous les articles (si besoin réel).
     * @return iterable<ArticleDTO>
     */
    public function getAll(): iterable
    {
        $rows = $this->articleRepository->getAllWithAuthor();

        return $this->asIterable($rows);
    }

    public function getById(int $id): ?ArticleDTO
    {
        if ($id <= 0) {
            throw new \InvalidArgumentException('ID doit être positif.');
        }

        $article = $this->articleRepository->findById($id);

        if ($article === null) {
            return null;
        }

        $media = $this->imageRepository->findMediaByArticle($id);
        if ($media !== []) {
            return $article->withMedia($media);
        }

        $images = $this->imageRepository->findPathsByArticle($id);
        if ($images === []) {
            $images = $this->loadFilesystemMediaPaths($id);
        }

        return $images === [] ? $article : $article->withImages($images);
    }

    /* =======================
       ===== Mutations =======
       ======================= */

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $user  (doit contenir au moins 'id')
     * @return array{errors?: array<string,string>, data?: array<string,mixed>}
     */
    /**
     * @param array<int, array{tmp_name:string,name:string,type:string,error:int,size:int}> $imageFiles
     */
    public function create(array $input, array $user, array $imageFiles = []): array
    {
        $data = $this->sanitize($input);
        $errors = $this->validate($data);

        if ($errors !== []) {
            return ['errors' => $errors, 'data' => $data];
        }

        try {
            $payload = $this->toPersistenceArray($data) + [
                'author_id' => isset($user['id']) ? (int)$user['id'] : null,
            ];
            $articleId = $this->articleRepository->create($payload);

            if ($articleId > 0 && $imageFiles !== []) {
                $stored = $this->storeArticleImages($articleId, $imageFiles);
                if ($stored !== []) {
                    $this->imageRepository->replaceImages($articleId, $stored);
                    $this->articleRepository->update($articleId, ['image' => $stored[0]]);
                }
            }
        } catch (\Throwable $e) {
            error_log("❌ Article Create Error: " . $e->getMessage() . " | Trace: " . $e->getTraceAsString());
            return ['errors' => ['_global' => 'Erreur lors de la création: ' . $e->getMessage()], 'data' => $data];
        }

        return [];
    }

    /**
     * @param array<string,mixed> $input
     * @return array{errors?: array<string,string>, data?: array<string,mixed>}
     */
    /**
     * @param array<int, array{tmp_name:string,name:string,type:string,error:int,size:int}> $newImages
     */
    public function update(int $id, array $input, array $newImages = []): array
    {
        if ($id <= 0) {
            return ['errors' => ['_global' => 'Identifiant invalide.'], 'data' => $input];
        }

        $data = $this->sanitize($input);
        $errors = $this->validate($data);

        if ($errors !== []) {
            return ['errors' => $errors, 'data' => $data];
        }

        try {
            $payload = $this->toPersistenceArray($data);
            $this->articleRepository->update($id, $payload);

            if ($newImages !== []) {
                $stored = $this->storeArticleImages($id, $newImages);
                if ($stored !== []) {
                    $existing = $this->imageRepository->findPathsByArticle($id);
                    $merged = array_merge($existing, $stored);
                    $this->imageRepository->replaceImages($id, $merged);
                    $this->articleRepository->update($id, ['image' => $merged[0]]);
                }
            }
        } catch (\Throwable $e) {
            // Log minimal (facultatif)
            return ['errors' => ['_global' => 'Erreur lors de la mise à jour.'], 'data' => $data];
        }

        return [];
    }

    public function delete(int $id): void
    {
        if ($id <= 0) {
            throw new \InvalidArgumentException('ID doit être positif.');
        }
        $this->imageRepository->deleteByArticle($id);
        $this->deleteArticleDirectory($id);
        $this->articleRepository->delete($id);
        $this->cleanupDanglingArticleDirectories();
    }

    /* =======================
       ===== Helpers =======
       ======================= */

    /**
     * Normalise toute source en flux paresseux.
     * @param iterable<ArticleDTO> $rows
     * @return iterable<ArticleDTO>
     */
    private function asIterable(iterable $rows): iterable
    {
        // Si array → devient lazy; si Generator → passthrough.
        yield from $rows;
    }

    /**
     * Normalise les données utilisateur (sans XSS ici).
     * - trim global
     * - requis: string non vide
     * - optionnels: null si vide
     *
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    private function sanitize(array $input): array
    {
        $out = [];

        foreach (array_merge(self::REQUIRED_FIELDS, self::OPTIONAL_FIELDS) as $field) {
            $val = isset($input[$field]) ? trim((string)$input[$field]) : '';
            $out[$field] = $val;
        }

        // Optionnels → null si vide
        foreach (self::OPTIONAL_FIELDS as $opt) {
            if ($out[$opt] === '') {
                $out[$opt] = null;
            }
        }

        return $out;
    }

    /**
     * @param array<string,mixed> $data
     * @return array<string,string> champ => message
     */
    private function validate(array $data): array
    {
        $errors = [];

        // Requis non vides
        foreach (self::REQUIRED_FIELDS as $f) {
            if ($data[$f] === '' || $data[$f] === null) {
                $errors[$f] = 'Ce champ est obligatoire.';
            }
        }

        // Date (YYYY-MM-DD ou JJ-MM-AAAA valide)
        if (!empty($data['date_article'])) {
            $dateStr = (string)$data['date_article'];
            
            // Essayer format input HTML (YYYY-MM-DD)
            $d = \DateTime::createFromFormat('Y-m-d', $dateStr);
            $ok = $d && $d->format('Y-m-d') === $dateStr;
            
            // Si échoue, essayer format classique (JJ-MM-AAAA)
            if (!$ok) {
                $d = \DateTime::createFromFormat('d-m-Y', $dateStr);
                $ok = $d && $d->format('d-m-Y') === $dateStr;
            }
            
            if (!$ok) {
                $errors['date_article'] = 'Format date invalide (attendu : AAAA-MM-JJ ou JJ-MM-AAAA)';
            }
        }

        // Heure (HH:MM ou HH:MM:SS)
        if (!empty($data['hours'])) {
            $h = \DateTime::createFromFormat('H:i:s', (string)$data['hours'])
              ?: \DateTime::createFromFormat('H:i', (string)$data['hours']);
            if (!$h) {
                $errors['hours'] = 'Format heure invalide (attendu : HH:MM ou HH:MM:SS)';
            }
        }

        return $errors;
    }

    /**
     * Transforme les données validées en format prêt pour la DB.
     * - date_article : normalisé en YYYY-MM-DD
     * - hours        : normalisé en HH:MM:SS
     *
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    private function toPersistenceArray(array $data): array
    {
        $out = $data;

        // date_article → YYYY-MM-DD (normaliser JJ-MM-AAAA si nécessaire)
        if (!empty($out['date_article'])) {
            $dateStr = (string)$out['date_article'];
            
            // Si déjà au format YYYY-MM-DD, garder
            if (\DateTime::createFromFormat('Y-m-d', $dateStr) && 
                \DateTime::createFromFormat('Y-m-d', $dateStr)->format('Y-m-d') === $dateStr) {
                $out['date_article'] = $dateStr;
            } else {
                // Convertir JJ-MM-AAAA → YYYY-MM-DD
                $d = \DateTime::createFromFormat('d-m-Y', $dateStr);
                if ($d) {
                    $out['date_article'] = $d->format('Y-m-d');
                }
            }
        }

        // hours → HH:MM:SS
        if (!empty($out['hours'])) {
            $h = \DateTime::createFromFormat('H:i:s', (string)$out['hours'])
              ?: \DateTime::createFromFormat('H:i', (string)$out['hours']);
            if ($h) {
                $out['hours'] = $h->format('H:i:s');
            }
        }

        return $out;
    }

    /**
     * @param array<int, array{tmp_name:string,name:string,type:string,error:int,size:int}> $imageFiles
     * @return list<string> chemins publics vers les médias sauvegardés
     */
    private function storeArticleImages(int $articleId, array $imageFiles): array
    {
        $destDir = $this->articleMediaRoot() . '/' . $articleId;
        $saved = [];

        foreach ($imageFiles as $file) {
            if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                continue;
            }

            $path = ImageConverter::saveArticleMedia($file, $articleId, $destDir);
            if ($path !== null) {
                $saved[] = $path;
            }
        }

        return $saved;
    }

    /**
     * @return list<array{id:int,article_id:int,path:string,position:int}>
     */
    public function getMediaList(int $articleId): array
    {
        return $this->imageRepository->findMediaByArticle($articleId);
    }

    /**
     * @return array{success?:true,error?:string}
     */
    public function deleteMedia(int $articleId, int $mediaId): array
    {
        if ($articleId <= 0 || $mediaId <= 0) {
            return ['error' => 'Identifiants invalides.'];
        }

        $media = $this->imageRepository->findMediaById($mediaId);
        if ($media === null || (int)$media['article_id'] !== $articleId) {
            return ['error' => 'Média introuvable.'];
        }

        $this->imageRepository->deleteById($mediaId);
        $this->imageRepository->resequencePositions($articleId);
        $this->deleteMediaFile((string) $media['path']);
        $this->refreshArticleCover($articleId);
        $this->cleanupArticleDirectoryIfUnused($articleId);
        $this->cleanupDanglingArticleDirectories();

        return ['success' => true];
    }

    /**
     * @return array{success?:true,error?:string,path?:string}
     */
    public function renameMedia(int $articleId, int $mediaId, string $newName): array
    {
        if ($articleId <= 0 || $mediaId <= 0) {
            return ['error' => 'Identifiants invalides.'];
        }

        $media = $this->imageRepository->findMediaById($mediaId);
        if ($media === null || (int)$media['article_id'] !== $articleId) {
            return ['error' => 'Média introuvable.'];
        }

        $sanitized = $this->sanitizeFilename($newName);
        if ($sanitized === '') {
            return ['error' => 'Nom de fichier invalide.'];
        }

        $oldPublicPath = (string) $media['path'];
        $oldAbsolute = $this->absoluteMediaPath($oldPublicPath);
        if (!is_file($oldAbsolute)) {
            return ['error' => 'Fichier introuvable sur le disque.'];
        }

        $extension = strtolower((string) pathinfo($oldPublicPath, PATHINFO_EXTENSION));
        $extension = $extension !== '' ? $extension : 'webp';

        $publicDir = rtrim(str_replace('\\', '/', dirname($oldPublicPath)), '/');
        if ($publicDir === '' || $publicDir === '.') {
            $publicDir = '/assets/img/articles';
        }

        $newPublicPath = $this->buildUniquePublicPath($publicDir, $sanitized, $extension);
        $newAbsolute = $this->absoluteMediaPath($newPublicPath);

        if (!$this->moveFile($oldAbsolute, $newAbsolute)) {
            return ['error' => 'Impossible de renommer le fichier.'];
        }

        $this->imageRepository->updatePath($mediaId, $newPublicPath);
        $this->refreshArticleCover($articleId);

        return [
            'success' => true,
            'path' => $newPublicPath,
        ];
    }

    private function deleteArticleDirectory(int $articleId): void
    {
        $dir = $this->articleMediaRoot() . '/' . $articleId;
        $this->removeDirectory($dir);
    }

    private function deleteMediaFile(string $publicPath): void
    {
        $absolute = $this->absoluteMediaPath($publicPath);
        if (is_file($absolute)) {
            @unlink($absolute);
        }
    }

    private function absoluteMediaPath(string $publicPath): string
    {
        $clean = '/' . ltrim($publicPath, '/');
        $basePath = realpath(__DIR__ . '/../../..') ?: dirname(__DIR__, 2);

        return $basePath . '/public' . $clean;
    }

    private function buildUniquePublicPath(string $publicDir, string $filename, string $extension): string
    {
        $baseDir = '/' . ltrim($publicDir, '/');
        $counter = 0;
        do {
            $suffix = $counter === 0 ? '' : '-' . $counter;
            $candidate = sprintf('%s/%s%s.%s', $baseDir, $filename, $suffix, $extension);
            $counter++;
            $absolute = $this->absoluteMediaPath($candidate);
        } while (is_file($absolute));

        return $candidate;
    }

    private function moveFile(string $from, string $to): bool
    {
        $dir = dirname($to);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        if (@rename($from, $to)) {
            return true;
        }

        if (@copy($from, $to)) {
            @unlink($from);
            return true;
        }

        return false;
    }

    private function refreshArticleCover(int $articleId): void
    {
        $media = $this->imageRepository->findMediaByArticle($articleId);
        $cover = $media[0]['path'] ?? null;
        $this->articleRepository->update($articleId, ['image' => $cover]);
    }

    private function sanitizeFilename(string $name): string
    {
        $clean = preg_replace('/[^a-zA-Z0-9_-]/', '-', $name);
        $clean = preg_replace('/-+/', '-', $clean ?? '');

        return trim((string) $clean, '-');
    }

    private function articleMediaRoot(): string
    {
        return (realpath(__DIR__ . '/../../..') ?: dirname(__DIR__, 2)) . '/public/assets/img/articles';
    }

    private function loadFilesystemMediaPaths(int $articleId): array
    {
        $dir = $this->articleMediaRoot() . '/' . $articleId;
        if (!is_dir($dir)) {
            return [];
        }

        $paths = [];
        $iterator = new FilesystemIterator($dir, FilesystemIterator::SKIP_DOTS);

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $ext = strtolower($file->getExtension());
            if (!in_array($ext, self::SUPPORTED_FS_MEDIA_EXT, true)) {
                continue;
            }

            $paths[] = '/assets/img/articles/' . $articleId . '/' . $file->getFilename();
        }

        sort($paths);

        return $paths;
    }

    private function cleanupArticleDirectoryIfUnused(int $articleId): void
    {
        $dir = $this->articleMediaRoot() . '/' . $articleId;
        if (!is_dir($dir)) {
            return;
        }

        $article = $this->articleRepository->findById($articleId);
        if ($article === null) {
            $this->deleteArticleDirectory($articleId);
            return;
        }

        if ($this->imageRepository->findMediaByArticle($articleId) !== []) {
            return;
        }

        if ($this->articleHasLegacyCover($article)) {
            return;
        }

        $this->deleteArticleDirectory($articleId);
    }

    private function cleanupDanglingArticleDirectories(): void
    {
        $root = $this->articleMediaRoot();
        if (!is_dir($root)) {
            return;
        }

        $iterator = new FilesystemIterator($root, FilesystemIterator::SKIP_DOTS);
        foreach ($iterator as $entry) {
            if (!$entry->isDir()) {
                continue;
            }

            $folder = $entry->getFilename();
            if (!ctype_digit($folder)) {
                $this->removeDirectory($entry->getPathname());
                continue;
            }

            $this->cleanupArticleDirectoryIfUnused((int) $folder);
        }
    }

    private function articleHasLegacyCover(ArticleDTO $article): bool
    {
        if ($article->image === null) {
            return false;
        }

        $needle = '/articles/' . $article->id . '/';

        return str_contains((string) $article->image, $needle);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isDir()) {
                @rmdir($file->getPathname());
            } else {
                @unlink($file->getPathname());
            }
        }

        @rmdir($dir);
    }

}
