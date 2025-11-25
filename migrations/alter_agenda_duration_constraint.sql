-- Migration: Modifier la contrainte de durée des événements pour permettre jusqu'à 30 jours
-- Date: 2025-01-XX
-- Description: Permet la création d'événements multi-jours (jusqu'à 30 jours)

-- Pour MySQL/MariaDB
ALTER TABLE agenda_events DROP CONSTRAINT IF EXISTS ck_agenda_duration;
ALTER TABLE agenda_events ADD CONSTRAINT ck_agenda_duration 
    CHECK (duration_minutes BETWEEN 30 AND 43200); -- 30 minutes à 30 jours (30 * 24 * 60)

-- Pour SQLite (nécessite de recréer la table)
-- Note: SQLite ne supporte pas ALTER TABLE pour modifier les CHECK constraints
-- Cette migration doit être appliquée manuellement ou via un script de migration

