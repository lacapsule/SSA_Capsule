-- Position d'un événement placée par l'IGN à l'enregistrement, depuis le serveur (décision du 2026-11-20) : point,
-- précision (adresse, quartier, lieu-dit), libellé reconnu par l'IGN et quartier dessiné (rues et cours d'eau dans un
-- rayon d'un kilomètre, JSON au format de config/carte-quartiers.json). Vide : liste versionnée des lieux d'événements
-- (config/lieux-evenements.tsv), sinon carte de la commune.
ALTER TABLE events ADD COLUMN latitude REAL NULL;
ALTER TABLE events ADD COLUMN longitude REAL NULL;
ALTER TABLE events ADD COLUMN position_precision VARCHAR(20) NULL;
ALTER TABLE events ADD COLUMN position_ign VARCHAR(300) NULL;
ALTER TABLE events ADD COLUMN quartier TEXT NULL;
