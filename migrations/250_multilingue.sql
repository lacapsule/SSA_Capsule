-- Traductions du contenu (modules/multilingue), champ par champ. Le texte d'origine reste dans la table de son module.
-- objet : « article », « page »… ; objet_id : identifiant dans ce module (« 12 », « /a-propos/ ») ; champ : « titre »…
-- Ligne de la langue par défaut : valeur vide, seulement l'empreinte (SHA-256) du texte d'origine, déclarée par
-- Contenu::source() ; ligne d'une autre langue : la traduction et l'empreinte du texte d'origine qu'elle traduit.
-- Empreintes différentes : traduction périmée. Jetons {{engine}} du socle : SQLite et MySQL. Dates écrites par PHP.
CREATE TABLE traductions (
  objet VARCHAR(60) NOT NULL,
  objet_id VARCHAR(190) NOT NULL,
  champ VARCHAR(60) NOT NULL,
  langue VARCHAR(12) NOT NULL,
  valeur MEDIUMTEXT NOT NULL,
  empreinte_source CHAR(64) NOT NULL,
  mis_a_jour DATETIME NOT NULL,
  PRIMARY KEY (objet, objet_id, champ, langue)
){{engine}};

CREATE INDEX idx_traductions_langue ON traductions (langue, objet);
