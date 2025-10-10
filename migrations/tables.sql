CREATE TABLE IF NOT EXISTS users (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    username      TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    role          TEXT NOT NULL DEFAULT 'employee', -- 'admin' ou 'employee'
    email         TEXT NOT NULL,
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS articles (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    titre       TEXT NOT NULL,
    description TEXT NOT NULL,
    date_article  DATE NOT NULL,    -- stocke YYYY-MM-DD mais utilise seulement MM-DD côté app ou requête
    hours       TIME NOT NULL,    -- juste HH:MM:SS
    lieu        TEXT,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    author_id   INTEGER NOT NULL,
    author      TEXT,
    FOREIGN KEY(author_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table pour les messages de contact
CREATE TABLE IF NOT EXISTS contacts (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    nom        TEXT NOT NULL,
    email      TEXT NOT NULL,
    message    TEXT NOT NULL,
    ip         TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS agenda_events (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title            VARCHAR(255)       NOT NULL,
  starts_at        DATETIME           NOT NULL,              -- début réel
  duration_minutes SMALLINT UNSIGNED  NOT NULL DEFAULT 60,   -- durée en minutes
  location         VARCHAR(255)       NULL,
  created_by       INT UNSIGNED       NULL,
  created_at       DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME           NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_starts_at (starts_at),
  INDEX idx_created_by (created_by),
  CONSTRAINT fk_agenda_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO users (username, password_hash, role, email) 
VALUES ('admin', '$2y$12$DdRaR1i6wNQbPGxbmgeB9OvAnhSzFvN98/wIBdO3w0Qcqsu62BMEy','admin', 'admin@example.org');

INSERT INTO agenda_events (title, starts_at, duration_minutes, location, created_by)
VALUES
 ('Réunion d’équipe', '2025-09-22 09:00:00', 90, 'Salle 101', 1),
 ('Point client',     '2025-09-23 14:30:00', 60, 'Bureau 202', 1);

INSERT INTO articles (titre, description, date_article, hours, lieu, image, author_id, author)
VALUES
  ('Réunion mensuelle', 'Présentation des avancées du projet', '2025-08-01', '18:00:00', 'Salle des fêtes', NULL, 1, 'admin'),
  ('Atelier alimentation durable', 'Initiation à la cuisine locale et responsable.', '2025-08-15', '14:00:00', 'Centre social', NULL, 1, 'admin'),
  ('Assemblée générale', 'AG annuelle de lassociation.', '2025-09-10', '17:00:00', 'Mairie de Morlaix', NULL, 1, 'admin');
