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
/* Contraintes :
   - author_id obligatoire, lié à users(id)
   - resume doit être plus court que description (vérifié par CHECK)
*/
CREATE TABLE IF NOT EXISTS articles (
  id            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  titre         VARCHAR(255)    NOT NULL,
  resume        TEXT            NOT NULL,
  description   TEXT            NOT NULL,
  date_article  DATE            NOT NULL,        -- 'YYYY-MM-DD'
  hours         TIME            NULL,            -- 'HH:MM:SS'
  lieu          VARCHAR(255)    NULL,
  image         VARCHAR(255)    NULL,
  created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  author_id     INT UNSIGNED    NOT NULL,
  PRIMARY KEY (id),
  KEY idx_articles_date   (date_article),
  KEY idx_articles_author (author_id),
  CONSTRAINT fk_articles_author
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT ck_articles_resume_length
    CHECK (CHAR_LENGTH(resume) <= 500),
  CONSTRAINT ck_articles_resume_shorter
    CHECK (CHAR_LENGTH(description) > CHAR_LENGTH(resume))
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

/* Exemples d'articles - ✅ Colonne 'author' supprimée */
INSERT INTO articles (titre, resume, description, date_article, hours, lieu, image, author_id) VALUES
('Conférence : Vers une agriculture bio locale',
 'Regards croisés sur la production bio en Bretagne.',
 'Un panel d'agriculteurs et d'experts partagera ses pratiques et répondra aux questions du public. Dégustation en fin de séance.',
 '2025-10-05', '19:30:00', 'Salle municipale des Halles', NULL, 1),
('Forum citoyen alimentation',
 'Espace d'échanges sur les enjeux de l'alimentation durable.',
 'Tables rondes, ateliers participatifs et présentation des projets associatifs autour de l'alimentation en Pays de Morlaix.',
 '2025-11-12', '15:00:00', 'Maison des associations', NULL, 1),
('Journée portes ouvertes',
 'Découverte du projet Sécurité Sociale de l'Alimentation.',
 'Visites guidées, stand d'informations et animations pour tous les âges. Venez nombreux découvrir nos actions !',
 '2025-09-20', '10:00:00', 'ULAMIR CPIE', NULL, 1),
('Balade découverte : plantes comestibles',
 'Sortie nature pour identifier et goûter les plantes locales.',
 'Accompagnés d'un botaniste, partez sur les sentiers pour reconnaître les plantes sauvages comestibles et apprendre à les préparer.',
 '2025-08-28', '09:30:00', 'Bois du Poan Ben', NULL, 1),
('Atelier cuisine anti-gaspi',
 'Apprendre à cuisiner avec les restes.',
 'Un chef vous guidera pour transformer vos restes de la semaine en plats savoureux, économiques et sains.',
 '2025-09-18', '16:00:00', 'Cuisine du Centre Social', NULL, 1),
('Table ronde : accès à l'alimentation',
 'Débat sur les inégalités et solutions concrètes.',
 'Élus locaux, associations et citoyens débattront sur les obstacles à une alimentation saine et sur les pistes d'amélioration à l'échelle locale.',
 '2025-11-27', '18:30:00', 'Salle du Conseil, Mairie', NULL, 1),
('Fête des partenaires',
 'Moment festif pour remercier bénévoles et partenaires.',
 'Animations, buffet et projection vidéo retraçant les moments forts de l'année. Entrée libre pour tous les membres et partenaires.',
 '2025-12-12', '20:00:00', 'Salle Polyvalente', NULL, 1);

-- Octobre 2025
INSERT INTO agenda_events (title, starts_at, duration_minutes, location, description, created_by)
VALUES
 ('Sprint planning',      '2025-10-06 09:00:00', 60, 'Salle A', 'Itération S42', 1),
 ('Démo produit',         '2025-10-08 14:30:00', 45, 'Salle B', 'Client ACME', 1),
 ('One-to-one',           '2025-10-09 11:30:00', 30, 'Visio',   'Coaching', 1),
 ('Rétrospective',        '2025-10-10 16:00:00', 60, 'Salle A', 'Retro Sprint', 1),
 ('Atelier UX',           '2025-10-14 10:00:00', 90, 'Salle C', 'Parcours onboarding', 1);

-- Novembre 2025
INSERT INTO agenda_events (title, starts_at, duration_minutes, location, description, created_by)
VALUES
 ('Kickoff Q4',           '2025-11-03 09:00:00', 90, 'Grand amphi', 'Roadmap Q4', 1),
 ('Point client BEPO',    '2025-11-05 13:30:00', 60, 'Visio',       'Suivi lot 2', 1),
 ('Revue sécurité',       '2025-11-12 15:00:00', 60, 'Salle SecOps','Correctifs CVE', 1),
 ('Formation interne',    '2025-11-18 10:30:00', 120,'Salle D',     'Perf PHP 8.4', 1),
 ('Clôture trimestre',    '2025-11-28 17:00:00', 60, 'Salle A',     'KPI + Retro', 1);
