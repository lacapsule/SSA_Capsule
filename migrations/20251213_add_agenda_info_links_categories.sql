-- Ajout des colonnes info, links et catégorie sur agenda_events
ALTER TABLE agenda_events ADD COLUMN links TEXT;
ALTER TABLE agenda_events ADD COLUMN info TEXT;
ALTER TABLE agenda_events ADD COLUMN category_id INTEGER REFERENCES agenda_categories(id) ON DELETE SET NULL;

-- Table des catégories d'événements
CREATE TABLE IF NOT EXISTS agenda_categories (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL UNIQUE,
  label TEXT NOT NULL,
  color TEXT NOT NULL
);

-- Seed par défaut (idempotent)
INSERT OR IGNORE INTO agenda_categories (name, label, color) VALUES
 ('collectif', 'Collectif', '#fdb544'),
 ('experimentateurs', 'Expérimentateurs', '#3788d8'),
 ('pour_tous', 'Pour tous', '#43c466');

