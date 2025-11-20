/* ======================================================================
   MySQL / MariaDB schema — UTC + invariants alignés sur la version SQLite
   Stockage UTC (DATETIME) — pensez à SET time_zone = '+00:00' côté connexion.
   ====================================================================== */
-- sqlfluff: disable
SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET sql_mode = 'STRICT_ALL_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';
-- sqlfluff: enable

-- Moteur/Collation (adaptez la collation si MariaDB ancien)
-- MySQL 8.x : utf8mb4_0900_ai_ci ; MariaDB : utf8mb4_general_ci
-- (Ligne ENGINE/COLLATE répétée sur chaque table)
-- ----------------------------------------------------------------------

/* ===================== USERS ===================== */
CREATE TABLE IF NOT EXISTS users (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username       VARCHAR(191)  NOT NULL,
  password_hash  VARCHAR(255)  NOT NULL,
  role           ENUM('admin','employee') NOT NULL DEFAULT 'employee',
  email          VARCHAR(255)  NOT NULL,
  created_at     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/* ===================== ARTICLES ===================== */
/* On garde la sémantique “date_article (DATE), hours (TIME)”
   et on ajoute la colonne `image` qui était utilisée au seed. */
CREATE TABLE IF NOT EXISTS articles (
  id            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  titre         VARCHAR(255)    NOT NULL,
  resume        TEXT            NULL,            -- présent dans SQLite ; optionnel ici
  description   TEXT            NOT NULL,
  date_article  DATE            NOT NULL,        -- 'YYYY-MM-DD'
  hours         TIME            NOT NULL,        -- 'HH:MM:SS'
  lieu          VARCHAR(255)    NULL,
  image         VARCHAR(255)    NULL,
  created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  author_id     INT UNSIGNED    NOT NULL,
  author        VARCHAR(191)    NULL,
  PRIMARY KEY (id),
  KEY idx_articles_date   (date_article),
  KEY idx_articles_author (author_id),
  CONSTRAINT fk_articles_author
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/* ===================== CONTACTS ===================== */
CREATE TABLE IF NOT EXISTS contacts (
  id          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  nom         VARCHAR(255)  NOT NULL,
  email       VARCHAR(255)  NOT NULL,
  message     TEXT          NOT NULL,
  ip          VARCHAR(64)   NULL,
  created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/* ===================== AGENDA (UTC, durée en minutes) ===================== */
/* Contrat :
   - starts_at en UTC (DATETIME)
   - duration_minutes en [30, 480] (vérifié par CHECK si supporté, sinon triggers/app)
   - created_by nullable, FK users(id), ON DELETE SET NULL
*/
CREATE TABLE IF NOT EXISTS agenda_events (
  id               INT UNSIGNED       NOT NULL AUTO_INCREMENT,
  title            VARCHAR(255)       NOT NULL,
  starts_at        DATETIME           NOT NULL,              -- UTC
  duration_minutes SMALLINT UNSIGNED  NOT NULL DEFAULT 60,
  location         VARCHAR(255)       NULL,
  color            VARCHAR(7)         NOT NULL DEFAULT '#3788d8',
  description      TEXT               NULL,
  created_by       INT UNSIGNED       NULL,
  created_at       DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME           NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_agenda_starts_at (starts_at),
  KEY idx_agenda_created_by (created_by),
  CONSTRAINT fk_agenda_user
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT ck_agenda_duration
    CHECK (duration_minutes BETWEEN 30 AND 480)  -- MySQL 8.0.16+ & MariaDB 10.4+ appliquent le CHECK
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/* ===================== SEED ===================== */
INSERT INTO users (username, password_hash, role, email)
VALUES ('admin', '$2y$12$DdRaR1i6wNQbPGxbmgeB9OvAnhSzFvN98/wIBdO3w0Qcqsu62BMEy', 'admin', 'admin@example.org')
ON DUPLICATE KEY UPDATE email = VALUES(email);

INSERT INTO agenda_events (title, starts_at, duration_minutes, location, created_by)
VALUES
 ('Réunion d’équipe', '2025-09-22 09:00:00', 90, 'Salle 101', 1),
 ('Point client',     '2025-09-23 14:30:00', 60, 'Bureau 202', 1);

/* Les seeds d’articles correspondent aux colonnes (incluant image). */
INSERT INTO articles (titre, resume, description, date_article, hours, lieu, image, author_id, author) VALUES
 ('Réunion mensuelle',
  'Point d’étape du projet',
  'Présentation des avancées et Q/R.',
  '2025-08-01', '18:00:00', 'Salle des fêtes', NULL, 1, 'admin'),
 ('Atelier alimentation durable',
  'Initiation à la cuisine locale',
  'Découverte d’ingrédients et recettes responsables.',
  '2025-08-15', '14:00:00', 'Centre social', NULL, 1, 'admin'),
 ('Assemblée générale',
  'Assemblée annuelle',
  'Rapports, votes et perspectives de l’association.',
  '2025-09-10', '17:00:00', 'Mairie de Morlaix', NULL, 1, 'admin');
