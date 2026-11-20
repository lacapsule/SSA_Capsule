-- Photo principale d'un article marquée décorative (seconde passe du tableau de bord, 2026-11-20) : sa description
-- reste vide sans compter parmi les photos à décrire (écran « Photos sans description », accueil du tableau de bord).
ALTER TABLE articles ADD COLUMN image_decorative INT NOT NULL DEFAULT 0;
