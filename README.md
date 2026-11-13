# Modèle de site client Kouzia

Point de départ de chaque nouveau site client : le kit `.claude` commun (règles, skills, hooks), relié à la boîte à outils `../kouzia-boite-a-outils`.

## Créer un site client

Rien n'est créé avant l'information dont il dépend : l'interview définit le besoin et le type de projet, le client valide le cahier des charges, et seulement alors l'architecture est posée.

1. Copier ou cloner ce dépôt sous le nom du futur site (minuscules, mots séparés par `-`), par exemple `boulangerie-martin`.
2. `make deps` : installe tout en une commande (paquets système, extensions PHP, navigateur) et clone la boîte à outils à côté du dépôt (`../kouzia-boite-a-outils`) si elle n'y est pas. `make outils` montre l'état sans rien installer.
3. Ouvrir Claude Code dans le nouveau dossier : l'interview démarre (skill `interview`). Elle lance `make init` (squelette `docs/`, mode d'emploi en brouillon, adresse Git du modèle retirée), demande le lien du site actuel et les fichiers du client (rangés dans `docs/source/fournis/`), lance l'audit (`make audit URL=…`), puis fixe en premier le type de projet et le métier.
4. Mode d'emploi validé par l'humain, puis cahier des charges (`make livrables`) : **validation 1 du client** (`make valider ID=02-cahier-des-charges PAR="…"`), qui fige le type.
5. `make socle` : l'architecture déduite du type (statique pour une vitrine, dynamique PHP pour un site associatif, une boutique ou un espace client). Refusé tant que la validation 1 manque ou si le type et le socle ne vont pas ensemble. Le Makefile et ce README sont remplacés par ceux du socle ; toute autre collision arrête la copie.
6. Maquettes en texte puis wireframes : **validation 2**. Pages intégrées sur la charte neutre, puis les trois pistes de direction artistique montrées sur ces pages : **validation 3** (`PISTE=n`), posée par `make appliquer`. Finitions, recette : **validation 4**, puis mise en ligne.

On peut aussi demander à Claude : « crée le site <nom> depuis le modèle ».

## Contenu

- `.claude/rules/` : règles communes (`00-organisation` prime), dont `boite-a-outils` : lire l'index de la boîte avant de coder.
- `.claude/skills/` : procédures (interview, livraison, audit, design system, boîte à outils…). Modèle de socle statique PHP : `skills/web-new-site/modeles/php-statique/`.
- `.claude/hooks/` : vérification du mode d'emploi au démarrage, refus de l'arrêt d'un processus par son nom, refus d'une écriture qui enfreint une règle mécanique (tiret cadratin, `innerHTML`, couleur brute hors tokens, `outline` supprimé), rappel de `make verify` et de la livraison avant de rendre la main.
- `.claude/settings.json` : permissions de travail courantes accordées ; push, envoi, suppression et `sudo` toujours demandés ; `.env` et accès de déploiement jamais lus.
- `.claude/skills/livrables/` : chaîne des documents du client (gabarit unique, audit du site actuel, cahier des charges, wireframes ; direction artistique, maquettes, PV de recette et dossier de livraison à venir).
- `package.json` : outillage de contrôle et de documents (`playwright-core`, `axe-core`, `svgo`), jamais publié.
- `.claude/journal-kit.md` : historique des changements du kit.

## Faire évoluer le kit

Une amélioration utile à tous les sites se fait ici d'abord (proposer, valider, appliquer, noter au journal), puis se reporte dans les sites en cours qui en ont besoin.

La boîte à outils garde une copie de ce `.claude/` : après chaque changement du kit, la resynchroniser en gardant ses deux fichiers propres (`rules/projet.md`, `settings.json`), d'abord à blanc (`--dry-run --itemize-changes`) :

```bash
rsync -a --delete --exclude rules/projet.md --exclude settings.json --exclude settings.local.json --exclude etat/ kouzia-modele-site/.claude/ kouzia-boite-a-outils/.claude/
```

Puis dans la boîte : `node outils/indexer.mjs`, `node .claude/skills/web-audit/scripts/check-repo.mjs`, commit.

### Reprendre sur un autre poste

1. Cloner `kouzia-modele-site` et `kouzia-boite-a-outils` côte à côte (ou seulement le premier : `make deps` clone la boîte à côté).
2. `make deps` dans `kouzia-modele-site`, puis `make outils` : tout doit être `ok`.

### Reste à faire (état au 2026-11-13)

But : un agent crée seul un site client sur mesure (branding repris du site actuel ou des fichiers fournis, métier, vrais besoins), avec tous les documents de conception et de livraison, uniformes. Décidé le 2026-11-12 et revu le 2026-11-13 : 4 validations du client (cahier des charges, qui fige le type et donc l'architecture ; wireframes ; choix d'une piste de direction artistique sur les vraies pages ; recette), documents en HTML et PDF à la charte Kouzia (variante claire des tokens de la boîte, Exo 2). Pas de logo Kouzia : le nom est écrit en texte.

Fait (skill `livrables`) : 01 audit de l'existant, 02 cahier des charges, 03 wireframes, 04 direction artistique et maquettes (pistes sur les vraies pages, `make appliquer`) ; parcours revu le 2026-11-13 (`make socle` et sa garde, interview qui lance `make init` et l'audit, domaine dans `site.json` seulement, chaîne adaptée à `outil-interne`). Testés sur un faux site local seulement.

- [x] **04 Direction artistique et maquettes** (2026-11-13) : 3 pistes (fidèle, modernisée, contrastée) en tokens vérifiés clair et sombre, rôles de zone (en-tête, haut de page) et accent par zone, polices libres de Google Fonts en woff2 local, directions par métier ; pistes montrées sur les vraies pages du site intégré ; `make valider … PISTE=n` puis `make appliquer` (écrit `base/charte.css`, copie les polices).
- [ ] **05 PV de recette** : depuis `make verify`, `make a11y`, Lighthouse et la checklist du skill `livraison` ; validation 4 avant mise en ligne.
- [ ] **06 Dossier de livraison** : guide d'utilisation pour le client, dossier technique (hébergement, emplacement des accès, sauvegardes, mise en ligne, retour arrière).
- [ ] **Socle application** pour le type `outil-interne` (logiciel) : base à décider (Fastify, React ou PHP) ; `make socle` le refuse en attendant.
- [ ] **Données de l'activité** : schéma `site.json` enrichi (adresse, horaires, coordonnées GPS, profils sociaux, SIREN, logo) et JSON-LD `LocalBusiness` généré (`Base.php` le marque encore « à compléter ») ; vérification du SIREN par l'API recherche-entreprises.api.gouv.fr.
- [ ] **Vitrine de base** assemblée dans le modèle statique depuis la boîte : accueil, services, à propos, contact, pages légales, accessibilité, plan du site, 404, `.htaccess` durci.
- [ ] **Reprise automatique de l'audit et de l'arborescence** : faits de l'inventaire versés au mode d'emploi en `déduit` ; arborescence du mode d'emploi convertie en `navigation.json` et en pages vides (aujourd'hui écrite trois fois) ; anciennes adresses et colonne « Remplace » converties en `src/data/redirections.json`.
- [ ] **Mode autonome** : brief en fichier avec schéma (URL, fichiers fournis, type de site, objectifs), parcours de bout en bout avec les 4 points d'arrêt.
- [ ] **Fichiers fournis** : lire une charte PDF (`pdftotext`) et les couleurs d'un logo fourni, pas seulement les lister.
- [ ] **Tester `make audit` sur de vrais sites en ligne** : site lourd en JavaScript, site qui bloque les robots.

Limites connues : dans le PDF, une page de wireframe très longue est réduite (vue téléphone petite, la version HTML est en taille réelle) ; 4 avertissements anciens de `check-repo` dans la boîte (IP privées dans les tests de `securite-api-fastify`).
