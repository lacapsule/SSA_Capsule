-- Rôles, périmètres et auteur du journal, ajoutés à la table admins de modules/authentification-admin (migration 030).
-- Une instruction par ALTER TABLE : portable SQLite et MySQL. Les comptes existants restent administrateurs.
-- role : 'admin' ou 'editor' (vérifié par l'application). perimetre : clé de config/perimetres.php, NULL pour tout le contenu.

ALTER TABLE admins ADD COLUMN role VARCHAR(20) NOT NULL DEFAULT 'admin';

ALTER TABLE admins ADD COLUMN perimetre VARCHAR(60) NULL;

-- actor : e-mail du compte au moment de l'action, lisible même après la suppression du compte.
ALTER TABLE admin_log ADD COLUMN actor VARCHAR(190) NULL;

CREATE INDEX idx_admin_log_action ON admin_log (action, id);
