<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\ArticleRepository;
use App\Dto\ArticleDTO;

final class ArticleService
{
    public function __construct(private ArticleRepository $articleRepository)
    {
    }

    /** Champs requis et optionnels (pour lisibilité & évolutivité) */
    private const REQUIRED_FIELDS = ['titre', 'resume', 'description', 'date_article', 'hours'];
    private const OPTIONAL_FIELDS = ['lieu', 'image'];

    /* =======================
       ======= Queries =======
       ======================= */

    /** @return ArticleDTO[] */
    public function getUpcoming(): array
    {
        return $this->articleRepository->upcoming();
    }

    /** @return ArticleDTO[] */
    public function getAll(): array
    {
        return $this->articleRepository->getAllWithAuthor();
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
     * Normalise les données utilisateur (sans sécurité XSS ici).
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

        // Date (YYYY-MM-DD) valide
        if (!empty($data['date_article'])) {
            $d = \DateTime::createFromFormat('Y-m-d', (string)$data['date_article']);
            $ok = $d && $d->format('Y-m-d') === $data['date_article'];
            if (!$ok) {
                $errors['date_article'] = 'Format date invalide (attendu : AAAA-MM-JJ)';
            }
        }

        // Heure (HH:MM ou HH:MM:SS) → on normalise en HH:MM:SS lors de la persistance
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
     * - date_article : YYYY-MM-DD
     * - hours        : normalisé en HH:MM:SS
     *
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    private function toPersistenceArray(array $data): array
    {
        $out = $data;

        // hours → HH:MM:SS
        if (!empty($out['hours'])) {
            $h = \DateTime::createFromFormat('H:i:s', (string)$out['hours'])
                ?: \DateTime::createFromFormat('H:i', (string)$out['hours']);
            if ($h) {
                $out['hours'] = $h->format('H:i:s');
            }
        }

        // date_article → garde YYYY-MM-DD tel quel (déjà validé)
        // Optionnels: null accepté

        return $out;
    }
}
