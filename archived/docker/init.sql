CREATE TABLE IF NOT EXISTS users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role          ENUM('admin', 'employee') NOT NULL DEFAULT 'employee',
    email         VARCHAR(255) NOT NULL,
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS articles (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    titre       VARCHAR(255) NOT NULL,
    resume      TEXT NOT NULL,
    description TEXT,
    date_article  DATE NOT NULL,
    hours       TIME NOT NULL,
    lieu        VARCHAR(255),
    image       VARCHAR(255),
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    author_id   INT NOT NULL,
    author      VARCHAR(255),
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS contacts (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    nom        VARCHAR(255) NOT NULL,
    email      VARCHAR(255) NOT NULL,
    message    TEXT NOT NULL,
    ip         VARCHAR(45),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;



INSERT INTO users (username, password_hash, role, email) 
VALUES ('admin', '$2y$12$DdRaR1i6wNQbPGxbmgeB9OvAnhSzFvN98/wIBdO3w0Qcqsu62BMEy','admin', 'admin@example.org');

-- Exemples d'événements supplémentaires pour alimenter ta table

INSERT INTO articles (titre, resume, description, date_article, hours, lieu, image, author_id, author) VALUES
('Conférence : Vers une agriculture bio locale', 
 'Regards croisés sur la production bio en Bretagne.', 
 'Un panel d’agriculteurs et d’experts partagera ses pratiques et répondra aux questions du public. Dégustation en fin de séance.', 
 '2025-10-05', '19:30:00', 'Salle municipale des Halles', NULL, 1, 'admin'),

('Forum citoyen alimentation', 
 'Espace d’échanges sur les enjeux de l’alimentation durable.', 
 'Tables rondes, ateliers participatifs et présentation des projets associatifs autour de l’alimentation en Pays de Morlaix.', 
 '2025-11-12', '15:00:00', 'Maison des associations', NULL, 1, 'admin'),

('Journée portes ouvertes', 
 'Découverte du projet Sécurité Sociale de l’Alimentation.', 
 'Visites guidées, stand d’informations et animations pour tous les âges. Venez nombreux découvrir nos actions !', 
 '2025-09-20', '10:00:00', 'ULAMIR CPIE', NULL, 1, 'admin'),

('Balade découverte : plantes comestibles', 
 'Sortie nature pour identifier et goûter les plantes locales.', 
 'Accompagnés d’un botaniste, partez sur les sentiers pour reconnaître les plantes sauvages comestibles et apprendre à les préparer.', 
 '2025-08-28', '09:30:00', 'Bois du Poan Ben', NULL, 1, 'admin'),

('Atelier cuisine anti-gaspi', 
 'Apprendre à cuisiner avec les restes.', 
 'Un chef vous guidera pour transformer vos restes de la semaine en plats savoureux, économiques et sains.', 
 '2025-09-18', '16:00:00', 'Cuisine du Centre Social', NULL, 1, 'admin'),

('Table ronde : accès à l’alimentation', 
 'Débat sur les inégalités et solutions concrètes.', 
 'Élus locaux, associations et citoyens débattront sur les obstacles à une alimentation saine et sur les pistes d’amélioration à l’échelle locale.', 
 '2025-11-27', '18:30:00', 'Salle du Conseil, Mairie', NULL, 1, 'admin'),

('Fête des partenaires', 
 'Moment festif pour remercier bénévoles et partenaires.', 
 'Animations, buffet et projection vidéo retraçant les moments forts de l’année. Entrée libre pour tous les membres et partenaires.', 
 '2025-12-12', '20:00:00', 'Salle Polyvalente', NULL, 1, 'admin');
