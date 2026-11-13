-- Limite de débit des formulaires (FormGuard::rateLimited) : une ligne par passage, purgée après un jour.
-- Renuméroter selon les migrations du projet.

CREATE TABLE rate_limits (
  id {{pk}},
  bucket VARCHAR(40) NOT NULL,
  ip VARCHAR(45) NOT NULL,
  created_at DATETIME NOT NULL
){{engine}};

CREATE INDEX idx_rate_limits ON rate_limits (bucket, ip, created_at);
CREATE INDEX idx_rate_limits_created ON rate_limits (created_at);
