PRAGMA foreign_keys = ON;

-- USERS ---------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
  id             INTEGER PRIMARY KEY AUTOINCREMENT,
  username       TEXT NOT NULL UNIQUE,
  password_hash  TEXT NOT NULL,
  role           TEXT NOT NULL DEFAULT 'employee' CHECK (role IN ('admin','employee')),
  email          TEXT NOT NULL,
  created_at     TEXT NOT NULL DEFAULT (CURRENT_TIMESTAMP)
);

-- ARTICLES ------------------------------------------------
CREATE TABLE IF NOT EXISTS articles (
  id            INTEGER PRIMARY KEY AUTOINCREMENT,
  titre         TEXT NOT NULL,
  resume        TEXT NOT NULL,
  description   TEXT,
  date_article  TEXT NOT NULL,     -- 'YYYY-MM-DD'
  hours         TEXT NOT NULL,     -- 'HH:MM:SS'
  lieu          TEXT,
  image         TEXT,
  created_at    TEXT NOT NULL DEFAULT (CURRENT_TIMESTAMP),
  author_id     INTEGER NOT NULL,
  author        TEXT,
  FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_articles_date   ON articles(date_article);
CREATE INDEX IF NOT EXISTS idx_articles_author ON articles(author_id);

-- CONTACTS -----------------------------------------------
CREATE TABLE IF NOT EXISTS contacts (
  id          INTEGER PRIMARY KEY AUTOINCREMENT,
  nom         TEXT NOT NULL,
  email       TEXT NOT NULL,
  message     TEXT NOT NULL,
  ip          TEXT,
  created_at  TEXT NOT NULL DEFAULT (CURRENT_TIMESTAMP)
);

-- AGENDA (pour corriger "no such table: agenda_events")
CREATE TABLE IF NOT EXISTS agenda_events (
  id          INTEGER PRIMARY KEY AUTOINCREMENT,
  title       TEXT NOT NULL,
  location    TEXT,
  description TEXT,
  start_at    TEXT NOT NULL,      -- 'YYYY-MM-DD HH:MM:SS'
  end_at      TEXT,
  created_at  TEXT NOT NULL DEFAULT (CURRENT_TIMESTAMP)
);

CREATE INDEX IF NOT EXISTS idx_agenda_start ON agenda_events(start_at);

-- SEED ----------------------------------------------------
INSERT INTO users (username, password_hash, role, email)
VALUES ('admin', '$2y$12$DdRaR1i6wNQbPGxbmgeB9OvAnhSzFvN98/wIBdO3w0Qcqsu62BMEy', 'admin', 'admin@example.org')
ON CONFLICT(username) DO NOTHING;

INSERT INTO articles (titre, resume, description, date_article, hours, lieu, image, author_id, author) VALUES
('Conférence : Vers une agriculture bio locale',
 'Regards croisés sur la production bio en Bretagne.',
 'Un panel d’agriculteurs et d’experts partagera ses pratiques et répondra aux questions du public. Dégustation en fin de séance.',
 '2025-10-05', '19:30:00', 'Salle municipale des Halles', NULL, 1, 'admin'),

('Forum citoyen alimentation',
 'Espace d’échanges sur les enjeux de l’alimentation durable.',
 'Tables rondes, ateliers participatifs et présentation des projets associatifs autour de l’alimentation en Pays de Morlaix.',
 '2025-11-12', '15:00:00', 'Maison des associations', NULL, 1, 'admin'),

('Journée portes ouvertes',
 'Découverte du projet Sécurité Sociale de l’Alimentation.',
 'Visites guidées, stand d’informations et animations pour tous les âges. Venez nombreux découvrir nos actions !',
 '2025-09-20', '10:00:00', 'ULAMIR CPIE', NULL, 1, 'admin'),

('Balade découverte : plantes comestibles',
 'Sortie nature pour identifier et goûter les plantes locales.',
 'Accompagnés d’un botaniste, partez sur les sentiers pour reconnaître les plantes sauvages comestibles et apprendre à les préparer.',
 '2025-08-28', '09:30:00', 'Bois du Poan Ben', NULL, 1, 'admin'),

('Atelier cuisine anti-gaspi',
 'Apprendre à cuisiner avec les restes.',
 'Un chef vous guidera pour transformer vos restes de la semaine en plats savoureux, économiques et sains.',
 '2025-09-18', '16:00:00', 'Cuisine du Centre Social', NULL, 1, 'admin'),

('Table ronde : accès à l’alimentation',
 'Débat sur les inégalités et solutions concrètes.',
 'Élus locaux, associations et citoyens débattront sur les obstacles à une alimentation saine et sur les pistes d’amélioration à l’échelle locale.',
 '2025-11-27', '18:30:00', 'Salle du Conseil, Mairie', NULL, 1, 'admin'),

('Fête des partenaires',
 'Moment festif pour remercier bénévoles et partenaires.',
 'Animations, buffet et projection vidéo retraçant les moments forts de l’année. Entrée libre pour tous les membres et partenaires.',
 '2025-12-12', '20:00:00', 'Salle Polyvalente', NULL, 1, 'admin');
