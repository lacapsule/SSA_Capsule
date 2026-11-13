-- Agenda et inscriptions (modules/agenda-inscriptions). Jetons {{pk}} et {{engine}} du socle : SQLite et MySQL.
-- Dates en heure locale 'AAAA-MM-JJ HH:MM', écrites par PHP. INSERT IGNORE ou INSERT OR IGNORE choisi par le code selon le pilote.

CREATE TABLE events (
  id {{pk}},
  title VARCHAR(190) NOT NULL,
  starts_at CHAR(16) NOT NULL,
  ends_at CHAR(16) NOT NULL,
  organizer_name VARCHAR(160) NULL,
  organizer_email VARCHAR(254) NULL,
  organizer_phone VARCHAR(30) NULL,
  address VARCHAR(300) NULL,
  description TEXT NULL,
  materials TEXT NULL,
  image VARCHAR(500) NULL,
  image_alt VARCHAR(250) NULL,
  capacity INT NULL,
  registration_open INT NOT NULL DEFAULT 0,
  is_published INT NOT NULL DEFAULT 1,
  include_weekends INT NOT NULL DEFAULT 1,
  confirmation_email TEXT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CHECK (capacity IS NULL OR capacity > 0),
  CHECK (ends_at >= starts_at)
){{engine}};

CREATE INDEX idx_events_starts_at ON events (starts_at);

CREATE TABLE event_registrations (
  id {{pk}},
  event_id INT NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  first_name VARCHAR(100) NOT NULL,
  phone VARCHAR(30) NOT NULL,
  email VARCHAR(190) NOT NULL,
  participants INT NOT NULL DEFAULT 1,
  adults INT NOT NULL DEFAULT 0,
  children INT NOT NULL DEFAULT 0,
  ip VARCHAR(45) NULL,
  created_at DATETIME NOT NULL,
  CHECK (participants BETWEEN 1 AND 100),
  CHECK (adults >= 0 AND children >= 0),
  FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE
){{engine}};

CREATE INDEX idx_event_registrations_event ON event_registrations (event_id);
CREATE INDEX idx_event_registrations_ip ON event_registrations (ip, created_at);
CREATE UNIQUE INDEX uq_event_registrations_event_email ON event_registrations (event_id, email);

-- Modèles d'e-mail communs ; « event_confirmation » : confirmation d'inscription (JSON). « key » est réservé en MySQL.
CREATE TABLE email_templates (
  tpl_key VARCHAR(64) NOT NULL PRIMARY KEY,
  content TEXT NOT NULL,
  updated_at DATETIME NOT NULL
){{engine}};
