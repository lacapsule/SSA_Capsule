-- Migration: Ajouter la table article_images pour gérer les galeries d'articles
-- Date: 2025-01-XX

-- MySQL / MariaDB
CREATE TABLE IF NOT EXISTS article_images (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    article_id  INT UNSIGNED NOT NULL,
    path        VARCHAR(255) NOT NULL,
    position    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_article_images_article (article_id),
    CONSTRAINT fk_article_images_article
        FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- SQLite (à appliquer manuellement si nécessaire)
-- CREATE TABLE article_images (
--     id          INTEGER PRIMARY KEY AUTOINCREMENT,
--     article_id  INTEGER NOT NULL,
--     path        TEXT NOT NULL,
--     position    INTEGER NOT NULL DEFAULT 0,
--     created_at  TEXT NOT NULL DEFAULT (CURRENT_TIMESTAMP),
--     FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE
-- );

