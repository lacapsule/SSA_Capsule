-- E-mails différés (App\Core\Outbox) : annulables pendant quelques secondes, envoyés après la réponse ou par la tâche planifiée.
-- Renuméroter selon les migrations du projet.

CREATE TABLE outbox (
  id {{pk}},
  to_email VARCHAR(190) NOT NULL,
  subject VARCHAR(255) NOT NULL,
  body_text MEDIUMTEXT NOT NULL,
  body_html MEDIUMTEXT NULL,
  reply_to VARCHAR(190) NULL,
  send_after DATETIME NOT NULL,
  attempts INT NOT NULL DEFAULT 0,
  last_error VARCHAR(255) NULL,
  claimed_at DATETIME NULL,
  failed_at DATETIME NULL,
  created_at DATETIME NOT NULL
){{engine}};

CREATE INDEX idx_outbox_due ON outbox (failed_at, send_after);
