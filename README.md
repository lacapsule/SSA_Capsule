# Modèle de site client Kouzia

Point de départ de chaque nouveau site client : le kit `.claude` commun (règles, skills, hooks), relié à la boîte à outils `../kouzia-boite-a-outils`.

## Créer un site client

1. Copier ou cloner ce dépôt sous le nom du futur site (minuscules, mots séparés par `-`), par exemple `boulangerie-martin`.
2. `make init` : crée le squelette du projet (`docs/source/fournis/` pour les fichiers du client, rangés par logo, charte, photos, textes et documents ; `docs/maquettes/` ; `docs/rapports/` ; mode d'emploi en brouillon), sans jamais rien écraser.
3. `make deps` : installe tout en une commande (paquets système, extensions PHP, navigateur) et clone la boîte à outils à côté du dépôt (`../kouzia-boite-a-outils`) si elle n'y est pas. `make outils` montre l'état sans rien installer.
4. Ouvrir Claude Code dans le nouveau dossier. Le hook de démarrage voit que le mode d'emploi est absent ou en brouillon et lance l'interview (skill `interview`). Si le client a déjà un site : donner son lien (`make audit URL=https://site-actuel`) et ranger les fichiers qu'il fournit dans les sous-dossiers de `docs/source/fournis/` ; l'audit relève textes, logo, couleurs, polices, coordonnées et anciennes adresses.
5. Après l'interview, le mode d'emploi est écrit dans `.claude/rules/projet.md`, validé par l'humain. `make livrables` produit les documents du client (audit, cahier des charges…) en HTML et PDF à la charte Kouzia dans `docs/livrables/` ; `make valider ID=… PAR="…"` enregistre chacune des 4 validations du client.
6. Poser le socle choisi : `make statique` (vitrine) ou `make dynamique` (`gabarits/socle-php` de la boîte). Le Makefile et ce README sont remplacés par ceux du socle ; toute autre collision arrête la copie. Le travail commence ensuite étape par étape.

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

### Reste à faire (état au 2026-11-12)

But : un agent crée seul un site client sur mesure (branding repris du site actuel ou des fichiers fournis, métier, vrais besoins), avec tous les documents de conception et de livraison, uniformes. Décidé le 2026-11-12 : 4 validations du client (cahier des charges, wireframes, direction artistique et maquettes, recette), documents en HTML et PDF à la charte Kouzia (variante claire des tokens de la boîte, Exo 2). Pas de logo Kouzia : le nom est écrit en texte.

Fait (skill `livrables`) : 01 audit de l'existant, 02 cahier des charges, 03 wireframes, 04 direction artistique. Testés sur un faux site local seulement.

- [x] **04 Direction artistique** (2026-11-13) : 3 pistes (fidèle, modernisée, contrastée), couleurs de l'audit en tokens vérifiés clair et sombre (`lib/couleurs.mjs`), polices libres de Google Fonts en woff2 local (`lib/polices.mjs`), directions par métier (`donnees/directions.json`, champ `- metier:` du mode d'emploi). Reste : appliquer la piste validée au site (écrire `src/styles/base/tokens.css` et copier les polices dans `public/`), à faire avec le 05.
- [ ] **05 Maquettes** : captures du vrai site en construction (1280 et 390 px, clair et sombre), à partir de `check-rendu.mjs` ; validation 3 avec le 04.
- [ ] **06 PV de recette** : depuis `make verify`, `make a11y`, Lighthouse et la checklist du skill `livraison` ; validation 4 avant mise en ligne.
- [ ] **07 Dossier de livraison** : guide d'utilisation pour le client, dossier technique (hébergement, emplacement des accès, sauvegardes, mise en ligne, retour arrière).
- [ ] **Données de l'activité** : schéma `site.json` enrichi (adresse, horaires, coordonnées GPS, profils sociaux, SIREN, logo) et JSON-LD `LocalBusiness` généré (`Base.php` le marque encore « à compléter ») ; vérification du SIREN par l'API recherche-entreprises.api.gouv.fr.
- [ ] **Vitrine de base** assemblée dans le modèle statique depuis la boîte : accueil, services, à propos, contact, pages légales, accessibilité, plan du site, 404, `.htaccess` durci.
- [ ] **Reprise automatique de l'audit** : faits de l'inventaire versés au mode d'emploi en `déduit` ; anciennes adresses et colonne « Remplace » de l'arborescence converties en `src/data/redirections.json`.
- [ ] **Mode autonome** : brief en fichier avec schéma (URL, fichiers fournis, type de site, objectifs), parcours de bout en bout avec les 4 points d'arrêt.
- [ ] **Fichiers fournis** : lire une charte PDF (`pdftotext`) et les couleurs d'un logo fourni, pas seulement les lister.
- [ ] **Tester `make audit` sur de vrais sites en ligne** : site lourd en JavaScript, site qui bloque les robots.

Limites connues : dans le PDF, une page de wireframe très longue est réduite (vue téléphone petite, la version HTML est en taille réelle) ; 4 avertissements anciens de `check-repo` dans la boîte (IP privées dans les tests de `securite-api-fastify`).
