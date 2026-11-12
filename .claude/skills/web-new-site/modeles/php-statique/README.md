# Modèle : site statique PHP à l'architecture Astro

Socle décrit dans la rule `web-architecture`. PHP fabrique le HTML en dev et au build ; en production, seul `dist/` est publié, sans PHP côté serveur.

## Démarrer un projet

1. À la racine du dépôt créé depuis le modèle : `make statique` (copie ce dossier, collisions contrôlées).
2. Créer `src/data/site.json` d'après `src/data/schemas/site.schema.json` (champs, longueurs et formes ; le build échoue tant qu'il manque ou s'en écarte, avec la liste des écarts), remplir `src/data/navigation.json` au fil des pages, et `SITE` dans le `Makefile`.
3. `.gitignore` fourni (`node_modules/`, `dist/`, `.env*`, `.claude/etat/`) : le garder (skill `web-git`).
4. `make deps` : installe tout en une commande (PHP, Node, outils d'images et de police, `lftp`, boîte à outils clonée à côté si absente, outillage npm verrouillé, navigateur si absent ; `sudo` demandé une fois). `make outils` montre l'état sans rien installer. Puis `make dev` et `make verify` : vert avant d'aller plus loin.

## Contenu

| Chemin | Rôle |
|--------|------|
| `bin/dev.php`, `bin/build.php`, `bin/preview.php` | serveur de dev, build vers `dist/`, prévisualisation |
| `src/lib/site.php` | `e()`, `data()`, `collection()`, `entree()`, `markdown()`, `layout()`, `component()`, `date_fr()` |
| `src/lib/routes.php` | routage par fichiers, rendu, CSS concaténée |
| `src/pages/` | une page = un fichier = une URL ; `404.php` obligatoire ; `[slug].php` pour une collection |
| `src/layouts/Base.php` | document commun : canonique, Open Graph, Twitter Card, icônes, fil d'Ariane visible et `BreadcrumbList` (prop `parents`), en-tête et pied de page ; à compléter : JSON-LD de l'activité (skill `web-seo-structured-data`) |
| `src/layouts/Contenu.php`, `src/layouts/Isolee.php` | familles de gabarits sur `Base` : page de contenu (colonne de lecture de 65 caractères), page isolée centrée (404, merci, plan du site) ; l'accueil utilise `Base` |
| `src/components/` | un bloc par fichier : `en-tete.php` (menu principal), `pied-de-page.php`, `fil-ariane.php` |
| `src/data/*.json`, `src/data/schemas/*.schema.json` | données du site, chacune validée par son schéma au chargement et au build (`site`, `navigation` dont chaque chemin doit être une page, `redirections`) |
| `src/styles/ordre.json`, `base/`, `composants/`, `sections/`, `pages/` | styles rangés par rôle, assemblés dans l'ordre de `ordre.json` en une feuille au nom haché ; un fichier non listé arrête le build |
| `src/partage/og.html`, `src/partage/logo.svg` | sources de l'image de partage et des icônes (`make images` → `public/`) |

Cibles de contrôle : `make verify` (lint, build, `check-dist`, ordre CSS, dépôt ; écrit `.claude/etat/verify-ok`, lu par le hook de fin de tour), `make a11y` (scan axe-core de chaque page).

Le build écrit `sitemap.xml` (pages sans `noindex`, `lastmod` tiré de Git), `robots.txt` et `llms.txt` depuis `site.json` et les pages : ne jamais les éditer dans `dist/`.

Le modèle est volontairement vide de contenu : rien de démo ne doit partir en production.
