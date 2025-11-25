-- sqlfluff: disable LXR
PRAGMA foreign_keys = ON;
PRAGMA journal_mode = WAL;
PRAGMA synchronous = NORMAL;
-- sqlfluff: enable LXR

/* ===================== USERS ===================== */
CREATE TABLE IF NOT EXISTS users (
  id             INTEGER PRIMARY KEY AUTOINCREMENT,
  username       TEXT    NOT NULL UNIQUE,
  password_hash  TEXT    NOT NULL,
  role           TEXT    NOT NULL DEFAULT 'employee' CHECK (role IN ('admin','employee')),
  email          TEXT    NOT NULL,
  created_at     TEXT    NOT NULL DEFAULT (CURRENT_TIMESTAMP)
);

CREATE INDEX IF NOT EXISTS idx_users_role ON users(role);

/* ===================== ARTICLES ===================== */
/* On conserve ta structure actuelle (dates locales d’affichage).
   Si un jour tu veux tout passer en UTC, on migrera. */
CREATE TABLE IF NOT EXISTS articles (
  id            INTEGER PRIMARY KEY AUTOINCREMENT,
  titre         TEXT NOT NULL,
  resume        TEXT NOT NULL,
  description   TEXT,
  date_article  TEXT NOT NULL,     -- 'YYYY-MM-DD' (affichage)
  hours         TEXT NOT NULL,     -- 'HH:MM:SS'  (affichage)
  lieu          TEXT,
 image         TEXT,
  created_at    TEXT NOT NULL DEFAULT (CURRENT_TIMESTAMP),
  author_id     INTEGER NOT NULL,
  author        TEXT,
  FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_articles_date   ON articles(date_article);
CREATE INDEX IF NOT EXISTS idx_articles_author ON articles(author_id);

/* ===================== CONTACTS ===================== */
CREATE TABLE IF NOT EXISTS contacts (
  id          INTEGER PRIMARY KEY AUTOINCREMENT,
  nom         TEXT NOT NULL,
  email       TEXT NOT NULL,
  message     TEXT NOT NULL,
  ip          TEXT,
  created_at  TEXT NOT NULL DEFAULT (CURRENT_TIMESTAMP)
);

/* ===================== AGENDA (UTC, durée en minutes) ===================== */
/* Contrat :
   - Stockage UTC en TEXT ISO8601 'YYYY-MM-DD HH:MM:SS'
   - Durée en minutes (>= 30)
   - created_by lié à users(id) (optionnel)
*/
CREATE TABLE IF NOT EXISTS agenda_events (
  id               INTEGER PRIMARY KEY AUTOINCREMENT,
  title            TEXT NOT NULL,
  starts_at        TEXT NOT NULL,      -- UTC 'YYYY-MM-DD HH:MM:SS'
  duration_minutes INTEGER NOT NULL CHECK (duration_minutes BETWEEN 30 AND 43200), -- 30 minutes à 30 jours
  location         TEXT,
  color            TEXT    NOT NULL DEFAULT '#3788d8',
  description      TEXT,
  created_by       INTEGER,
  created_at       TEXT NOT NULL DEFAULT (CURRENT_TIMESTAMP),
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_agenda_starts_at ON agenda_events(starts_at);
CREATE INDEX IF NOT EXISTS idx_agenda_created_by ON agenda_events(created_by);

/* ===================== SEED ===================== */
INSERT INTO users (username, password_hash, role, email)
VALUES ('admin', '$2y$12$DdRaR1i6wNQbPGxbmgeB9OvAnhSzFvN98/wIBdO3w0Qcqsu62BMEy', 'admin', 'admin@example.org')
ON CONFLICT(username) DO NOTHING;

/* Exemples d’articles (inchangés) */
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
