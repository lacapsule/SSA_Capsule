-- Actualités (modules/actualites) : catégories (principales et thématiques), articles, galerie. Jetons {{pk}} et {{engine}} du socle.
-- date_article : date de publication AAAA-MM-JJ (une date future programme la parution). Dates techniques écrites par PHP.

CREATE TABLE article_categories (
  id {{pk}},
  name VARCHAR(120) NOT NULL,
  slug VARCHAR(120) NOT NULL UNIQUE,
  is_theme INT NOT NULL DEFAULT 0,
  position INT NOT NULL DEFAULT 0
){{engine}};

CREATE TABLE articles (
  id {{pk}},
  titre VARCHAR(190) NOT NULL,
  resume TEXT NOT NULL,
  description MEDIUMTEXT NULL,
  date_article DATE NOT NULL,
  image VARCHAR(500) NULL,
  image_alt VARCHAR(250) NOT NULL DEFAULT '',
  category_id INT NULL,
  is_published INT NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  FOREIGN KEY (category_id) REFERENCES article_categories (id) ON DELETE SET NULL
){{engine}};

CREATE INDEX idx_articles_category ON articles (category_id);
CREATE INDEX idx_articles_date ON articles (date_article);

-- Thématiques secondaires d'un article (la catégorie principale est articles.category_id).
CREATE TABLE article_category_links (
  article_id INT NOT NULL,
  category_id INT NOT NULL,
  PRIMARY KEY (article_id, category_id),
  FOREIGN KEY (article_id) REFERENCES articles (id) ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES article_categories (id) ON DELETE CASCADE
){{engine}};

CREATE INDEX idx_article_category_links_category ON article_category_links (category_id);

-- Galerie, dans l'ordre d'affichage.
CREATE TABLE article_images (
  id {{pk}},
  article_id INT NOT NULL,
  path VARCHAR(500) NOT NULL,
  alt VARCHAR(250) NOT NULL DEFAULT '',
  position INT NOT NULL DEFAULT 0,
  FOREIGN KEY (article_id) REFERENCES articles (id) ON DELETE CASCADE
){{engine}};

CREATE INDEX idx_article_images_article ON article_images (article_id);

