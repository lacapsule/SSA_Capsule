-- Exemple : une table portable SQLite / MySQL. Renommer ou supprimer ce fichier dans le projet.
-- Un « ; » dans une chaîne ne coupe pas l'instruction : 'a;b' reste entier.

CREATE TABLE notes (
  id {{pk}},
  title VARCHAR(190) NOT NULL,
  body TEXT NOT NULL,
  created_at DATETIME NOT NULL
){{engine}};

CREATE INDEX idx_notes_created ON notes (created_at);

INSERT INTO notes (title, body, created_at) VALUES ('Bienvenue', 'Première note; avec un point-virgule et l''apostrophe.', '2026-01-01 00:00:00');
