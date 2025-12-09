<?php

declare(strict_types=1);

namespace App\Dto;

/**
 * Data Transfer Object (DTO) pour un événement.
 *
 * Objet immuable et typé destiné au transport des données d’un événement.
 * Ne contient aucune logique métier, uniquement des propriétés en lecture seule.
 *
 * @property int $id Identifiant unique de l'événement
 * @property string $titre Titre de l'événement
 * @property string $description Description détaillée de l'événement
 * @property string $date_article Date de l'événement au format 'YYYY-MM-DD'
 * @property string $hours Heure de l'événement au format 'HH:MM:SS'
 * @property string|null $lieu Lieu de l'événement (optionnel)
 * @property string $created_at Date/heure de création de l'événement
 * @property int $author_id Identifiant de l'auteur/organisateur
 */
class ArticleDTO
{
    /**
     * @param int $id Identifiant unique de l'événement
     * @param string $titre Titre de l'événement
     * @param string $description Description détaillée de l'événement
     * @param string $date_article Date au format 'YYYY-MM-DD'
     * @param string $hours Heure au format 'HH:MM:SS'
     * @param string|null $lieu Lieu de l'événement (nullable)
     * @param string $created_at Date/heure de création
     * @param int $author_id Identifiant de l'auteur
     */
    public function __construct(
        public readonly int $id,
        public readonly string $titre,
        public readonly string $resume,
        public readonly ?string $description,
        public readonly string $date_article,
        public readonly string $hours,
        public readonly ?string $lieu,
        public readonly ?string $image,
        public readonly string $created_at,
        public readonly int $author_id,
        public ?string $author
    ) {}
}
