# Modèle : site statique PHP à l'architecture Astro

Socle décrit dans la rule `web-architecture`. PHP fabrique le HTML en dev et au build ; en production, seul `dist/` est publié, sans PHP côté serveur.

## Démarrer un projet

1. Copier le contenu de ce dossier à la racine du dépôt (avec le `.claude/` du kit).
2. Créer `src/data/site.json` depuis `src/data/site.exemple.json` (le build échoue tant qu'il manque ; `nom`, `description`, `url`, `zone`, `contact.email`, `contact.telephoneAffiche` ; `image`, chemin de l'image de partage 1200 × 630 ; `contentSignal`, ex. `search=yes, ai-input=yes, ai-train=no`, valeur de l'interview) et `SITE` dans le `Makefile`.
3. `.gitignore` fourni (`node_modules/`, `dist/`, `.env*`, `.claude/etat/`) : le garder (skill `web-git`).
4. `make deps` : installe tout en une commande (PHP, Node, outils d'images et de police, `lftp`, outillage npm verrouillé, navigateur si absent ; `sudo` demandé une fois). `make outils` montre l'état sans rien installer. Puis `make dev` et `make verify` : vert avant d'aller plus loin.

## Contenu

| Chemin | Rôle |
|--------|------|
| `bin/dev.php`, `bin/build.php`, `bin/preview.php` | serveur de dev, build vers `dist/`, prévisualisation |
| `src/lib/site.php` | `e()`, `data()`, `collection()`, `entree()`, `markdown()`, `layout()`, `component()`, `date_fr()` |
| `src/lib/routes.php` | routage par fichiers, rendu, CSS concaténée |
| `src/pages/` | une page = un fichier = une URL ; `404.php` obligatoire ; `[slug].php` pour une collection |
| `src/layouts/Base.php` | gabarit minimal : canonique, Open Graph, Twitter Card, fil d'Ariane visible et `BreadcrumbList` (prop `parents` pour les niveaux intermédiaires) ; à compléter : navigation, JSON-LD de l'activité (skill `web-seo-structured-data`) |
| `src/styles/` | `tokens.css`, `base.css`, `components.css`, `pages.css`, concaténés dans cet ordre |
| `src/partage/og.html`, `src/partage/logo.svg` | sources de l'image de partage et des icônes (`make images` → `public/`) |
| `package.json`, `package-lock.json` | outillage de contrôle seulement (`playwright-core`, `axe-core`, `svgo`), jamais publié |
| `src/data/redirections.json` | `[{"de": "/ancienne", "vers": "/nouvelle/"}]`, actives en dev |

Cibles de contrôle : `make verify` (lint, build, `check-dist`, ordre CSS, dépôt ; écrit `.claude/etat/verify-ok`, lu par le hook de fin de tour), `make a11y` (scan axe-core de chaque page).

Le build écrit `sitemap.xml` (pages sans `noindex`, `lastmod` tiré de Git), `robots.txt` et `llms.txt` depuis `site.json` et les pages : ne jamais les éditer dans `dist/`.

Le modèle est volontairement vide de contenu : rien de démo ne doit partir en production.
