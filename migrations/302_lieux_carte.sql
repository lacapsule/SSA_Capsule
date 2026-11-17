-- Lieux repris de la carte uMap du collectif (2026-11-16) : statut du calque, description courte, coordonnées pour
-- le lien d'itinéraire. L'adresse postale n'existe pas sur la carte : chaîne vide, affichée seulement si remplie.
ALTER TABLE lieux_conventionnes ADD COLUMN statut VARCHAR(20) NOT NULL DEFAULT 'ok';
ALTER TABLE lieux_conventionnes ADD COLUMN description TEXT NULL;
ALTER TABLE lieux_conventionnes ADD COLUMN latitude DECIMAL(9,6) NULL;
ALTER TABLE lieux_conventionnes ADD COLUMN longitude DECIMAL(9,6) NULL;
