<?php

declare(strict_types=1);

namespace App\Modules\Article;

use App\Modules\Article\Dto\ArticleDTO;

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
    public function __construct(private ArticleRepository $articleRepository)
    {
    }

    /** Champs requis et optionnels pour create/update */
    private const REQUIRED_FIELDS = ['titre', 'resume', 'description', 'date_article', 'hours'];
    private const OPTIONAL_FIELDS = ['lieu', 'image'];

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

        return $this->articleRepository->findById($id);
    }

    /* =======================
       ===== Mutations =======
       ======================= */

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $user  (doit contenir au moins 'id')
     * @return array{errors?: array<string,string>, data?: array<string,mixed>}
     */
    public function create(array $input, array $user): array
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
            $this->articleRepository->create($payload);
        } catch (\Throwable $e) {
            // Log minimal (facultatif) : error_log($e->getMessage());
            return ['errors' => ['_global' => 'Erreur lors de la création.'], 'data' => $data];
        }

        return [];
    }

    /**
     * @param array<string,mixed> $input
     * @return array{errors?: array<string,string>, data?: array<string,mixed>}
     */
    public function update(int $id, array $input): array
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
        $this->articleRepository->delete($id);
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
}
