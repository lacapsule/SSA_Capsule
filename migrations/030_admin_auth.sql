-- Back-office : comptes administrateur avec double authentification (TOTP), codes de secours, journal,
-- liens « mot de passe oublié » à usage unique (jeton gardé haché en sha256, purgé par scripts/cron.php).
-- Renuméroter selon les migrations du projet ; à appliquer après la table rate_limits (modules/securite-formulaires).

CREATE TABLE admins (
  id {{pk}},
  email VARCHAR(190) NOT NULL UNIQUE,
  name VARCHAR(120) NOT NULL DEFAULT '',
  password_hash VARCHAR(255) NOT NULL,
  totp_secret TEXT NULL,
  totp_enabled_at DATETIME NULL,
  totp_last_step BIGINT NULL,
  failed_logins INT NOT NULL DEFAULT 0,
  locked_until DATETIME NULL,
  created_at DATETIME NOT NULL,
  last_login_at DATETIME NULL
){{engine}};

CREATE TABLE admin_recovery_codes (
  id {{pk}},
  admin_id INT NOT NULL,
  code_hash VARCHAR(64) NOT NULL,
  used_at DATETIME NULL,
  FOREIGN KEY (admin_id) REFERENCES admins (id) ON DELETE CASCADE
){{engine}};

CREATE TABLE admin_log (
  id {{pk}},
  admin_id INT NULL,
  action VARCHAR(80) NOT NULL,
  target VARCHAR(190) NOT NULL DEFAULT '',
  ip VARCHAR(45) NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL
){{engine}};

CREATE TABLE admin_password_tokens (
  id {{pk}},
  admin_id INT NOT NULL,
  token_hash VARCHAR(64) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (admin_id) REFERENCES admins (id) ON DELETE CASCADE
){{engine}};

CREATE INDEX idx_admin_recovery ON admin_recovery_codes (admin_id, code_hash);
CREATE INDEX idx_admin_log ON admin_log (admin_id, created_at);
CREATE INDEX idx_admin_password_tokens ON admin_password_tokens (admin_id, created_at);
