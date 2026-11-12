-- Ajout du champ liens pour les articles (zone de liens cliquables, un par ligne : "label|url" ou "url")
ALTER TABLE articles ADD COLUMN liens TEXT;
