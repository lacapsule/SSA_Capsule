-- Ajout de champs optionnels pour les articles : info + lien d'inscription
ALTER TABLE articles ADD COLUMN info TEXT;
ALTER TABLE articles ADD COLUMN inscription_link TEXT;

