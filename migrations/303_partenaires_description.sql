-- Structures porteuses lues dans la table des partenaires (tableau de bord, 2026-11-19) : rôle en une ligne, affiché
-- sous le nom sur l'accueil et la page Projet. Les financeurs n'en ont pas.
ALTER TABLE partenaires ADD COLUMN description VARCHAR(300) NULL;
