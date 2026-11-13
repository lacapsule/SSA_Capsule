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
| 03 | Wireframes | section « Arborescence » et `docs/maquettes/<page>.md` (skill `web-contenu`) | `make livrables` | **2** |
| 04 | Direction artistique et maquettes | couleurs, logo et polices de l'audit, champ `- metier:`, `donnees/directions.json`, puis les vraies pages du site intégré | `make livrables` | **3** : le client choisit une piste sur ses propres pages |
| 05 | PV de recette | `make verify`, `make a11y`, Lighthouse, checklist du skill `livraison` | à venir | **4**, avant mise en ligne |
| 06 | Dossier de livraison | mode d'emploi, réglages, procédure de mise en ligne | à venir | non |

Ordre imposé (chaque étape n'utilise que ce que les précédentes ont produit) : l'interview lance `make init` et `make audit` ; le cahier des charges validé (1) fige le type, donc l'architecture (`make socle` refuse avant) ; les wireframes validés (2) figent la structure ; les pages sont intégrées sur la charte neutre ; les pistes sont montrées sur ces pages et le client en choisit une (3), posée par `make appliquer` ; la recette (4) précède la mise en ligne. Type `outil-interne` : pas d'audit SEO ni d'exigence de référencement, une seule piste (coquille d'administration de la boîte, logo et accent du client).

Les livrables marqués « à venir » se produisent à la main avec le même gabarit, en attendant leur générateur.

## 2. Démarrer depuis un site existant

Quand l'humain donne le lien du site actuel, l'accord du client pour reprendre ses contenus est acquis (il demande un nouveau site). Ces étapes sont lancées par l'interview (skill `interview`, étape 0), pas avant elle.

1. `make init` (une fois) pose `docs/source/fournis/{logo,charte,photos,textes,documents}`, `docs/maquettes/`, `docs/rapports/` et le mode d'emploi en brouillon. Fichiers reçus du client : les ranger dans le bon sous-dossier de `docs/source/fournis/`, tels quels.
2. `make audit URL=https://site-actuel` (`MAX=80` pour un grand site) : parcourt les pages du même domaine et le sitemap, puis écrit `docs/source/existant/inventaire.json`, les captures (bureau et mobile) et les fichiers récupérés (logos, images). Génère ensuite le livrable 01.
3. Lire l'inventaire et le livrable 01 : chaque fait a sa page source et reste `à confirmer` tant que le client ne l'a pas validé. Il entre dans le mode d'emploi en `déduit` (skill `interview`), jamais directement dans le site.
4. Les anciennes adresses de l'inventaire deviennent `src/data/redirections.json` (301 vers la nouvelle page), une fois l'arborescence décidée.

## 3. Générer et valider

- `make livrables` régénère tout ce qui a une source. Version : +1 quand le contenu change ; un livrable changé après sa validation repasse « à valider ». Registre : `docs/livrables/registre.json`.
- Un livrable porte un encadré « À compléter avant validation » tant que sa source a des trous (mode d'emploi en brouillon, `à confirmer`, `…`, section vide, SIREN absent).
- Le cahier des charges reste « Brouillon », et ne peut pas être validé, tant que le mode d'emploi n'est pas `validé` ou que l'encadré n'est pas vide. Un constat (audit) ne se valide jamais.
- Validation du client (mail, réunion, signature) : `make valider ID=02-cahier-des-charges PAR="Nom, par e-mail du AAAA-MM-JJ"`, seulement sur instruction de l'humain, puis une ligne dans « Décisions validées » du mode d'emploi. Pour le 04, la piste choisie est obligatoire : `PISTE=n`.
- Les 4 validations du client sont des points d'arrêt : l'étape suivante ne commence pas avant (rule `00-organisation`, « Décider »).

## 4. Wireframes

- Une page de l'arborescence = une maquette en texte `docs/maquettes/<page>.md` (« / » donne `accueil.md`, « /a/b/ » donne `a-b.md`), au format du skill `web-contenu`. Page sans maquette : signalée dans l'encadré, livrable en brouillon.
- Chaque zone numérotée devient un bloc de zoning reconnu par son nom (en-tête, héros, services, réalisations, équipe, témoignages, tarifs, questions, chiffres, étapes, formulaire, plan d'accès, appel, pied de page ; sinon bloc de texte). Le contenu de la ligne règle le dessin : liste entre parenthèses (menu, champs, éléments), libellés entre « » (boutons), nombre écrit (« 3 services »).
- Rendu neutre (aucune couleur de marque ni photo), capturé à 1280 et 390 px ; pages HTML consultables dans `docs/livrables/03-wireframes/pages/`.
- La brique de la boîte notée dans une zone reste pour l'agent : elle est retirée du document du client.
- Brouillon tant que le cahier des charges n'est pas validé (ordre de la chaîne).

## 5. Direction artistique

- Trois pistes : **fidèle** (couleurs et polices du site actuel, en-tête dans la couleur de marque), **modernisée** (mêmes teintes avec retenue, fond neutre, polices du métier, plus d'air), **contrastée** (marque en grand dans l'en-tête et le haut de page, polices à plus fort caractère, angles plus nets). Décision du 2026-11-13.
- Couleurs (`lib/couleurs.mjs`) : couleurs de marque prises dans le logo puis dans le site par poids ; jeu complet clair et sombre aux noms de `base/tokens.css` de la boîte, plus `--color-marque` et `--color-marque-texte` ; une couleur trop claire pour du texte est déclinée dans OKLCH (même teinte, clarté ajustée). Paires contrôlées : texte et texte secondaire, accent, messages, sur chaque fond (4,5:1), focus et contours (3:1), texte sur bouton et sur la couleur de marque.
- Polices (`lib/polices.mjs`) : libres (OFL), téléchargées une fois depuis Google Fonts en woff2 latin dans `docs/source/polices/` (décision du 2026-11-13) ; aucune requête vers Google une fois le site en ligne. Police du système du site actuel (Georgia, Arial…) : gardée sans fichier. Police inconnue de Google : à demander au client avec sa licence web.
- Métier (`donnees/directions.json`) : ton, deux paires de polices, arrondis, densité, style photo, sections de la boîte conseillées, à éviter. Un métier qui manque s'ajoute là, pas dans le code.
- Ce qui distingue les pistes passe uniquement par des tokens, pour que la planche et le site montrent la même chose : rôles de zone `--color-entete-fond`, `--color-entete-texte`, `--color-entete-accent`, `--color-heros-fond`, `--color-heros-texte`, `--color-heros-accent` (en-tête dans la couleur de marque, haut de page teinté, marque en grand). Dans une zone, liens et boutons prennent l'accent de la zone, décliné pour 4,5:1 sur son fond ; un bouton de zone a l'accent en fond et le fond de zone en texte.
- Chaque piste écrit `pistes/<n>-<nom>/tokens.css` (clair par défaut, sombre selon le système ou `data-theme`) et une planche avec les vrais textes du client, capturée en clair et en sombre.
- Maquettes (`lib/maquettes.mjs`) : une fois les pages intégrées (après la validation 2), le site est construit et servi en local (socle statique ; socle dynamique : `SITE_LOCAL=http://… make livrables`), et chaque piste est appliquée par-dessus à chaque page de l'arborescence (1280 et 390 px, accueil en sombre). Pages absentes : livrable en brouillon.
- Après la validation 3 : `make appliquer` écrit la piste dans `src/styles/base/charte.css` (socle statique ; dynamique : `public/assets/css/charte.css`) et copie ses polices dans `public/polices/`. `charte.css` est généré : charte neutre du modèle avant, piste validée après ; les autres tokens restent dans `base/tokens.css`.
- Brouillon tant que les wireframes ne sont pas validés ou que les pages ne sont pas intégrées.

## 6. Rangement

| Chemin | Contenu | Versionné |
|--------|---------|-----------|
| `docs/source/fournis/` | fichiers remis par le client, tels quels | oui |
| `docs/source/existant/` | inventaire, captures et fichiers récupérés par l'audit (généré) | oui |
| `docs/source/polices/` | polices libres téléchargées (woff2 latin, `police.json` avec licence et source) | oui |
| `docs/livrables/<id>/` | `index.html` et `<id>.pdf` de chaque livrable (généré) | oui |
| `docs/livrables/_commun/` | charte copiée depuis la boîte (généré) | oui |
| `docs/livrables/registre.json` | version, date, statut et validation de chaque livrable | oui |

Rien de `docs/` n'est publié en ligne (hors de `dist/` et de `public/`).

## 7. Règles d'écriture des livrables

- L'encadré « À compléter » est une liste de travail pour Kouzia : un livrable ne part au client qu'une fois l'encadré vide.
- Texte pour le client : aucun terme interne (nom d'outil, de fichier du kit, de variable), pas de jargon sans explication.
- Aucun fait inventé : ce qui manque est écrit « à fournir » avec qui et pour quand (rule `web-legal`, « Zéro fiction »).
- L'emplacement des accès (FTP, DNS) n'apparaît jamais dans un livrable.
- Un nouveau livrable ou une nouvelle section : dans `scripts/generer.mjs`, depuis une source, avec la même charte.
