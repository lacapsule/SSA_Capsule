---
name: livrables
description: Produit les documents de conception et de livraison d'un site client, tous au même gabarit (charte Kouzia, HTML et PDF, versionnés) et générés depuis les sources du projet - audit du site actuel, cahier des charges, wireframes, direction artistique, maquettes, PV de recette, dossier de livraison - avec les 4 validations du client. À utiliser au démarrage d'un projet (après un lien vers le site actuel ou des fichiers fournis), à chaque étape de conception, et avant toute remise au client.
---

# Livrables d'un projet

Principe : un livrable ne s'écrit jamais à la main. Il est généré depuis la source du projet (mode d'emploi, données, inventaire de l'audit, site lui-même) : une correction se fait dans la source, puis `make livrables`. Gabarit unique : `gabarit/document.css` sur les tokens de la boîte (variante claire de la charte Kouzia, police Exo 2), relus à chaque génération.

## 1. La chaîne

| # | Livrable | Source | Commande | Validation du client |
|---|----------|--------|----------|----------------------|
| 01 | Audit de l'existant | site actuel et `docs/source/fournis/` | `make audit URL=https://site-actuel` | non (constat) |
| 02 | Cahier des charges | `.claude/rules/projet.md` et audit | `make livrables` | **1** |
| 03 | Wireframes | section « Arborescence » et maquettes en texte (skill `web-contenu`) | à venir | **2** |
| 04 | Direction artistique | couleurs, logo et polices de l'audit, métier du client | à venir | **3** (avec 05) |
| 05 | Maquettes | le site en construction, captures bureau et 390 px | à venir | **3** |
| 06 | PV de recette | `make verify`, `make a11y`, Lighthouse, checklist du skill `livraison` | à venir | **4**, avant mise en ligne |
| 07 | Dossier de livraison | mode d'emploi, réglages, procédure de mise en ligne | à venir | non |

Les livrables marqués « à venir » se produisent à la main avec le même gabarit, en attendant leur générateur.

## 2. Démarrer depuis un site existant

Quand l'humain donne le lien du site actuel, l'accord du client pour reprendre ses contenus est acquis (il demande un nouveau site).

1. Fichiers reçus du client (logo, charte, photos, textes) : les ranger dans `docs/source/fournis/`, tels quels.
2. `make audit URL=https://site-actuel` (`MAX=80` pour un grand site) : parcourt les pages du même domaine et le sitemap, puis écrit `docs/source/existant/inventaire.json`, les captures (bureau et mobile) et les fichiers récupérés (logos, images). Génère ensuite le livrable 01.
3. Lire l'inventaire et le livrable 01 : chaque fait a sa page source et reste `à confirmer` tant que le client ne l'a pas validé. Il entre dans le mode d'emploi en `déduit` (skill `interview`), jamais directement dans le site.
4. Les anciennes adresses de l'inventaire deviennent `src/data/redirections.json` (301 vers la nouvelle page), une fois l'arborescence décidée.

## 3. Générer et valider

- `make livrables` régénère tout ce qui a une source. Version : +1 quand le contenu change ; un livrable changé après sa validation repasse « à valider ». Registre : `docs/livrables/registre.json`.
- Un livrable porte un encadré « À compléter avant validation » tant que sa source a des trous (mode d'emploi en brouillon, `à confirmer`, `…`, section vide, SIREN absent).
- Le cahier des charges reste « Brouillon », et ne peut pas être validé, tant que le mode d'emploi n'est pas `validé` ou que l'encadré n'est pas vide. Un constat (audit) ne se valide jamais.
- Validation du client (mail, réunion, signature) : `make valider ID=02-cahier-des-charges PAR="Nom, par e-mail du AAAA-MM-JJ"`, seulement sur instruction de l'humain, puis une ligne dans « Décisions validées » du mode d'emploi.
- Les 4 validations du client sont des points d'arrêt : l'étape suivante ne commence pas avant (rule `00-organisation`, « Décider »).

## 4. Rangement

| Chemin | Contenu | Versionné |
|--------|---------|-----------|
| `docs/source/fournis/` | fichiers remis par le client, tels quels | oui |
| `docs/source/existant/` | inventaire, captures et fichiers récupérés par l'audit (généré) | oui |
| `docs/livrables/<id>/` | `index.html` et `<id>.pdf` de chaque livrable (généré) | oui |
| `docs/livrables/_commun/` | charte copiée depuis la boîte (généré) | oui |
| `docs/livrables/registre.json` | version, date, statut et validation de chaque livrable | oui |

Rien de `docs/` n'est publié en ligne (hors de `dist/` et de `public/`).

## 5. Règles d'écriture des livrables

- Texte pour le client : aucun terme interne (nom d'outil, de fichier du kit, de variable), pas de jargon sans explication.
- Aucun fait inventé : ce qui manque est écrit « à fournir » avec qui et pour quand (rule `web-legal`, « Zéro fiction »).
- L'emplacement des accès (FTP, DNS) n'apparaît jamais dans un livrable.
- Un nouveau livrable ou une nouvelle section : dans `scripts/generer.mjs`, depuis une source, avec la même charte.
