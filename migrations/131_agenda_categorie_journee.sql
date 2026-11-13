-- Agenda : catégorie et journée entière (modules/agenda-inscriptions), pour sections/admin-agenda.
-- Appliquée une seule fois par le Migrator du socle (table schema_migrations) : un ADD COLUMN simple suffit,
-- car « ADD COLUMN IF NOT EXISTS » n'existe ni en SQLite ni en MySQL (seulement en MariaDB).
-- category : clé de config agenda.categories (liste blanche vérifiée par PHP), NULL : sans catégorie.
-- all_day : 1 pour la journée entière ; les heures sont alors enregistrées 00:00 et 23:59.

ALTER TABLE events ADD COLUMN category VARCHAR(40) NULL;

ALTER TABLE events ADD COLUMN all_day INT NOT NULL DEFAULT 0;

-- Filtre de l'agenda d'administration par catégorie sur une période.
CREATE INDEX idx_events_category_starts_at ON events (category, starts_at);
