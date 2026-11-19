# Site de la SSA Pays de Morlaix (refonte)

Nouveau site de l'expérimentation de Sécurité Sociale de l'Alimentation du Pays de Morlaix (https://ssapaysdemorlaix.fr/) : site public bilingue français et breton, et espace d'administration pour que le collectif publie lui-même actualités, agenda et galerie.

Le site actuel (outil maison « CapsulePHP ») est reconstruit entièrement, avec les mêmes contenus. Son code reste sur les branches `main` et `dev` de ce dépôt ; la refonte vit sur la branche `refonte`.

Objectif, publics, arborescence, décisions et état des étapes : mode d'emploi `.claude/rules/projet.md` (il fait foi).

## Prérequis

- PHP 8.2 ou plus (8.4 en production) avec `pdo_sqlite`, `pdo_mysql`, `openssl`, `sodium`, `mbstring` (`make info` les vérifie) ; GNU make.
- Node.js pour les contrôles (`playwright-core`, `axe-core`, `svgo`), jamais publié.
- `make deps` installe tout en une commande (paquets, extensions PHP, navigateur, boîte à outils `../kouzia-boite-a-outils`). `make outils` montre l'état sans rien installer.

## Démarrer en local

```bash
make deps    # une fois par poste
make dev     # http://127.0.0.1:8080 ; au premier lancement, crée data/dev.sqlite avec les contenus et public/media/
```

- `make dev PORT=8090` pour un autre port, `HOST=0.0.0.0` pour tester depuis un téléphone du réseau local.
- `make contenus` fait la même préparation sans lancer le serveur ; il ne touche pas une base déjà là. `data/` et `public/media/` ne sont pas versionnés : un clone neuf sans eux affiche des pages sans photos ni contenus.
- `make db` recrée la base de dev (migrations, données de départ, puis contenus du site actuel par `scripts/importer-contenus.php`, voir « Contenus »).

## Commandes

| Commande | Rôle |
|----------|------|
| `make dev` | site en local |
| `make contenus` | crée la base de dev et les médias s'ils manquent |
| `make db` | recrée `data/dev.sqlite` avec les contenus |
| `make migrate` | applique les migrations sur la base du `.env` (production, préproduction), sans rien effacer |
| `make open` | ouvre l'adresse locale |
| `make info` | vérifie PHP et ses extensions |
| `make lint` | syntaxe de tous les fichiers PHP |
| `make verify` | tout ce qui doit être vert avant de livrer : lint, tests, dépôt, charte, mise en page |
| `make livrables` | documents du client (audit, cahier des charges, wireframes, direction artistique) |
| `php bin/traductions [contenu]` | traductions bretonnes manquantes ou périmées (interface, contenus) |
| `php scripts/cron.php` | tâches planifiées (à lancer toutes les 15 minutes par l'hébergeur) |

## Organisation du code

| Dossier | Contenu |
|---------|---------|
| `app/Core/` | moteur : routeur, requêtes, sessions, CSRF, en-têtes de sécurité, vues, e-mails, migrations |
| `app/<Domaine>/` | une fonction du site par dossier : `Actualites`, `Agenda`, `Galerie`, `Lieux`, `Contact`, `Pages`, `Langue`, `Admin`, `Reprise`, `Site` |
| `app/routes.php` | toutes les adresses du site |
| `templates/` | gabarits : `layout.php`, un dossier par domaine, `partials/`, `errors/` |
| `config/` | réglages : `site.php` (coordonnées, éditeur, hébergeur : une seule source), `langues.php`, `contenus.php`, `redirections.tsv` (anciennes adresses en 301) |
| `migrations/` | schéma de la base, appliqué dans l'ordre des numéros |
| `lang/` | textes d'interface en français et en breton |
| `public/` | seul dossier exposé : `index.php`, `router.php` (serveur local), `assets/`, `media/` (créé par l'import des contenus) |
| `scripts/` | outils : serveur de dev, migrations, cron, extraction et import des contenus |
| `tests/` | tests automatiques ; `make verify` les lance dès que `tests/run.php` existe (pas encore le cas) |
| `docs/` | `source/` (fichiers fournis, audit du site actuel), `livrables/`, `maquettes/`, `rapports/` |
| `data/` | bases SQLite et journaux, jamais versionné |

## Contenus

Les contenus viennent des pages publiques du site actuel, pas de sa base (décision du 2026-11-13) :

1. `node scripts/extraire-site-actuel.mjs` : écrit `docs/source/existant/contenus.json` et télécharge les médias.
2. `php scripts/importer-contenus.php [--remplacer]` : les verse dans la base et copie les images sous `public/media/`.

Les copies réduites des photos (400 à 1280 px, WebP) se créent au premier affichage dans `public/media/vignettes/` (`app/Site/Vignette.php`) ; ce dossier doit rester inscriptible par PHP sur le serveur, comme `public/media/`. Il se supprime sans risque : les copies se recréent.

`php scripts/donnees-volume.php` prépare des bases de test (vide, un élément, volume maximal) pour regarder les listes à tous les volumes. Jamais en production.

## Configuration

Copier `.env.example` en `.env` (jamais versionné) et renseigner `APP_ENV`, `APP_URL`, `APP_KEY`, `DB_DSN`, `DB_USER`, `DB_PASSWORD`. En production, le `.env` et les réglages SMTP sont posés par l'humain sur le serveur.

## Mise en ligne

Faite par Kouzia à partir du paquet d'installation et de ses consignes (étape 10 du mode d'emploi). Hébergement : La Capsule.
