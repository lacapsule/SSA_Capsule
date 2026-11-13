# Architecture des sites

Le socle se choisit à l'interview, jamais par défaut. Trois socles possibles :

| Socle | Quand | Production |
|-------|-------|------------|
| **Statique PHP, architecture Astro** (recommandé pour une vitrine, à valider à l'interview) | contenu sans compte ni paiement | HTML de `dist/` seul, aucun PHP côté serveur |
| **Astro (l'outil)** | seulement sur demande explicite | HTML de `dist/` |
| **Dynamique PHP** | e-commerce, comptes, back-office, formulaires traités sur place | PHP + base de données sur le serveur |

## Commun aux trois

- Rangement Astro : `src/pages/` (une page = un fichier = une URL), `src/layouts/`, `src/components/`, `src/content/<collection>/*.md`, `src/data/*.json`, `src/styles/`, `public/` (copié tel quel). Une source de données unique alimente HTML, méta, JSON-LD et `llms.txt`.
- Commandes par `make` : `help`, `info`, `deps`, `dev`, `build`, `preview`, `lint`, `check`, `repo`, `verify`, `clean`. `make verify` = tout ce qui doit être vert avant livraison.
- URL avec barre finale, minuscules ASCII, mots séparés par `-`.
- Poser le socle : `make socle` (déduit du type de site, refusé tant que le cahier des charges n'est pas validé par le client, car le type peut encore changer), ou `make statique` / `make dynamique` sous la même garde ; installer : `make deps` (une commande, boîte à outils comprise). Type `outil-interne` : socle application à créer (base à décider).

## Couches (reprises du portail de CapsuleOS)

Chaque fichier appartient à une couche, qui dit ce qu'il contient et qui l'écrit. Une couche ne lit que les couches au-dessus d'elle ; une couche générée ne s'édite jamais.

| Couche | Statique PHP | Dynamique PHP (`socle-php`) | Écrit par |
|--------|--------------|-----------------------------|-----------|
| Données (vérité) | `src/data/*.json`, chacun validé par `src/data/schemas/<nom>.schema.json` ; `src/content/` | `config/<domaine>.php`, `migrations/`, base de données | humain (interview), agent |
| Moteur | `src/lib/`, `bin/` | `app/Core/`, `app/<Domaine>/`, `scripts/` | agent, kit, boîte |
| Vues | `src/layouts/` (une famille par gabarit : `Base`, `Contenu`, `Isolee`), `src/components/` (un bloc par fichier) | `templates/layout.php`, `templates/<domaine>/`, `templates/partials/<domaine>/` | agent |
| Styles | `src/styles/{base,composants,sections,pages}/`, assemblés dans l'ordre de `src/styles/ordre.json` ; `base/charte.css` généré (charte neutre, puis piste validée par `make appliquer`) | `public/assets/css/<domaine>.css`, `public/assets/css/charte.css` (généré) | agent ; charte : `make appliquer` |
| Entrées publiques | `src/pages/` (un fichier, une URL) | `public/index.php` et `app/<Domaine>/routes.php` | agent |
| Généré | `dist/` (HTML, feuille au nom haché, sitemap, robots, llms) | aucun | build |
| Exécution | aucune | `data/` (base de dev, journaux), jamais versionné | serveur |

- Donnée sans schéma, schéma non respecté, feuille de style absente de `ordre.json`, lien de menu vers une page inexistante : le build échoue avec la liste des écarts.
- Non repris de CapsuleOS : arborescence de type Linux (`etc/`, `usr/`, `srv/www/`), `<base href>` et contrat de routes publiques. Ils servent à déplacer des fichiers sans changer d'URL et à ouvrir le site sans serveur, deux besoins absents d'un site client.

## Statique PHP

- PHP ≥ 8.2 comme moteur de gabarits, sans framework ni Composer, `declare(strict_types=1)`, `e()` sur tout affichage.
- Dev : `php -S` + `bin/dev.php`, pages rendues à chaque requête, redirections et 404 actives. Build : `bin/build.php` → `dist/` (CSS au nom haché, `sitemap.xml`, `robots.txt`, `llms.txt`, `404.html`). Prévisualisation : `bin/preview.php` sert `dist/` comme l'hébergeur.
- **Le build échoue au moindre avertissement PHP** (gestionnaire d'erreurs qui lève une exception). Pas d'opérateur `@` pour masquer une erreur.
- Page dynamique `[slug].php` : renvoie `['chemins' => fn() => [...], 'page' => function (string $slug) {...}]`. Une page ne déclare pas de fonction nommée.
- Modèle : `skills/web-new-site/modeles/php-statique/`, posé par `make statique`.

## Dynamique PHP

- Même rangement des vues et du contenu. Code serveur dans `src/` hors du dossier public, seul `public/index.php` exposé.
- Point de départ : brique `gabarits/socle-php` de la boîte à outils, posée par `make dynamique` ; un module de la boîte se range par domaine (`app/<Domaine>/`, `templates/<domaine>/`, `config/<domaine>.php`, contrat des modules dans sa `NOTES.md`).
- Requêtes préparées uniquement, sessions sécurisées, CSRF sur tout POST, rôles déclarés par route, secrets dans `.env` non versionné. Skills `web-forms-security`, `web-security-headers`.
- Paiement par prestataire, jamais de carte stockée. Tests automatiques pour chaque règle métier et chaque droit (403).

## Astro

- Seulement si l'humain le demande. `package-lock.json` versionné, `npm ci`, `npm audit --omit=dev` avant production. Les cibles `make` appellent `npm`.
