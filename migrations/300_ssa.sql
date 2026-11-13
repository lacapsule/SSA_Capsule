-- Données propres au projet SSA Pays de Morlaix (mode d'emploi : Décisions validées, Arborescence).
-- Jetons {{pk}} et {{engine}} du socle : SQLite et MySQL. Dates écrites par PHP.

-- Adresses /actualites/<titre>/ et /agenda/<titre>/ : identifiant lisible unique, et ce que l'ancien site avait en plus.
ALTER TABLE articles ADD COLUMN slug VARCHAR(190) NULL;
ALTER TABLE articles ADD COLUMN lieu VARCHAR(300) NULL;
ALTER TABLE articles ADD COLUMN heure CHAR(5) NULL;
-- Liens utiles et vidéos : JSON (liste de {texte, url, libelle} ; liste de chemins sous public/media/).
ALTER TABLE articles ADD COLUMN liens TEXT NULL;
ALTER TABLE articles ADD COLUMN videos TEXT NULL;
-- Ancienne adresse /article/<n> : table des redirections 301.
ALTER TABLE articles ADD COLUMN ancien_numero INT NULL;
CREATE UNIQUE INDEX uq_articles_slug ON articles (slug);

ALTER TABLE events ADD COLUMN slug VARCHAR(190) NULL;
ALTER TABLE events ADD COLUMN links TEXT NULL;
CREATE UNIQUE INDEX uq_events_slug ON events (slug);

-- Partenaires et financeurs (logos de l'accueil et de la page Qui sommes-nous).
CREATE TABLE partenaires (
  id {{pk}},
  nom VARCHAR(160) NOT NULL,
  url VARCHAR(500) NULL,
  logo VARCHAR(500) NOT NULL,
  groupe VARCHAR(20) NOT NULL DEFAULT 'partenaire',
  position INT NOT NULL DEFAULT 0,
  CHECK (groupe IN ('partenaire', 'financeur'))
){{engine}};

-- Lieux conventionnés (liste à fournir par Géraldine Gabillet) : adresse et lien d'itinéraire, sans carte.
CREATE TABLE lieux_conventionnes (
  id {{pk}},
  nom VARCHAR(160) NOT NULL,
  adresse VARCHAR(300) NOT NULL,
  code_postal VARCHAR(10) NOT NULL,
  ville VARCHAR(120) NOT NULL,
  horaires TEXT NULL,
  position INT NOT NULL DEFAULT 0,
  is_published INT NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL
){{engine}};

-- Galerie, dans l'ordre d'affichage. alt vide tant que le texte de l'image n'est pas écrit.
CREATE TABLE galerie_photos (
  id {{pk}},
  path VARCHAR(500) NOT NULL,
  alt VARCHAR(250) NOT NULL DEFAULT '',
  position INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL
){{engine}};

-- Traduction bretonne : « auto » (Gallek) ou « humaine » (corrigée à la main, plus jamais écrasée par Gallek).
ALTER TABLE traductions ADD COLUMN origine VARCHAR(10) NOT NULL DEFAULT 'auto';
