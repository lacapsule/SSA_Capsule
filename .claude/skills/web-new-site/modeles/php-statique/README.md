# Modèle : site statique PHP à l'architecture Astro

Socle décrit dans la rule `web-architecture`. PHP fabrique le HTML en dev et au build ; en production, seul `dist/` est publié, sans PHP côté serveur.

## Démarrer un projet

1. Copier le contenu de ce dossier à la racine du dépôt (avec le `.claude/` du kit).
2. Remplir `src/data/site.json` (`nom`, `description`, `url`, `zone`, `contact.email`, `contact.telephoneAffiche` ; `image`, chemin de l'image de partage 1200 × 630 ; `contentSignal`, ex. `search=yes, ai-input=yes, ai-train=no`, valeur de l'interview) et `SITE` dans le `Makefile`.
3. Ajouter `node_modules/`, `dist/`, `.env*` au `.gitignore` (skill `web-git`).
4. `make deps` (une fois), `make dev`, puis `make verify` : doit être vert avant d'aller plus loin.

## Contenu

| Chemin | Rôle |
|--------|------|
| `bin/dev.php`, `bin/build.php`, `bin/preview.php` | serveur de dev, build vers `dist/`, prévisualisation |
| `src/lib/site.php` | `e()`, `data()`, `collection()`, `entree()`, `markdown()`, `layout()`, `component()`, `date_fr()` |
| `src/lib/routes.php` | routage par fichiers, rendu, CSS concaténée |
| `src/pages/` | une page = un fichier = une URL ; `404.php` obligatoire ; `[slug].php` pour une collection |
| `src/layouts/Base.php` | gabarit minimal : canonique, Open Graph, Twitter Card, fil d'Ariane visible et `BreadcrumbList` (prop `parents` pour les niveaux intermédiaires) ; à compléter : navigation, JSON-LD de l'activité (skill `web-seo-structured-data`) |
| `src/styles/` | `tokens.css`, `base.css`, `components.css`, `pages.css`, concaténés dans cet ordre |
| `src/data/redirections.json` | `[{"de": "/ancienne", "vers": "/nouvelle/"}]`, actives en dev |

Le build écrit `sitemap.xml` (pages sans `noindex`, `lastmod` tiré de Git), `robots.txt` et `llms.txt` depuis `site.json` et les pages : ne jamais les éditer dans `dist/`.

Le modèle est volontairement vide de contenu : rien de démo ne doit partir en production.
