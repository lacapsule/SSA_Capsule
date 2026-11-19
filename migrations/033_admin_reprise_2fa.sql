-- Comptes repris de l'ancien site (décision du 2026-11-19) : connexion par mot de passe seul pendant 30 jours à partir
-- de la première connexion, puis double authentification obligatoire. reprise_2fa = 1 pour un compte repris ;
-- deux_facteurs_avant : échéance posée à la première connexion. Un compte créé ici (script, invitation) n'a pas ce délai.
ALTER TABLE admins ADD COLUMN reprise_2fa INT NOT NULL DEFAULT 0;
ALTER TABLE admins ADD COLUMN deux_facteurs_avant DATETIME NULL;
