# Modèle de site client Kouzia

Point de départ de chaque nouveau site client : le kit `.claude` commun (règles, skills, hooks), relié à la boîte à outils `../kouzia-boite-a-outils`.

## Créer un site client

1. Copier ou cloner ce dépôt sous le nom du futur site (minuscules, mots séparés par `-`), par exemple `boulangerie-martin`.
2. `make deps` : installe tout en une commande (paquets système, extensions PHP, navigateur) et clone la boîte à outils à côté du dépôt (`../kouzia-boite-a-outils`) si elle n'y est pas. `make outils` montre l'état sans rien installer.
3. Ouvrir Claude Code dans le nouveau dossier. Le hook de démarrage voit qu'il n'y a pas de mode d'emploi et lance l'interview (skill `interview`).
4. Après l'interview, le mode d'emploi est écrit dans `.claude/rules/projet.md`, validé par l'humain.
5. Poser le socle choisi : `make statique` (vitrine) ou `make dynamique` (`gabarits/socle-php` de la boîte). Le Makefile et ce README sont remplacés par ceux du socle ; toute autre collision arrête la copie. Puis `make deps` une fois de plus (outillage npm du socle) et le travail commence étape par étape.

On peut aussi demander à Claude : « crée le site <nom> depuis le modèle ».

## Contenu

- `.claude/rules/` : règles communes (`00-organisation` prime), dont `boite-a-outils` : lire l'index de la boîte avant de coder.
- `.claude/skills/` : procédures (interview, livraison, audit, design system, boîte à outils…). Modèle de socle statique PHP : `skills/web-new-site/modeles/php-statique/`.
- `.claude/hooks/` : vérification du mode d'emploi au démarrage, refus de l'arrêt d'un processus par son nom, refus d'une écriture qui enfreint une règle mécanique (tiret cadratin, `innerHTML`, couleur brute hors tokens, `outline` supprimé), rappel de `make verify` et de la livraison avant de rendre la main.
- `.claude/settings.json` : permissions de travail courantes accordées ; push, envoi, suppression et `sudo` toujours demandés ; `.env` et accès de déploiement jamais lus.
- `.claude/journal-kit.md` : historique des changements du kit.

## Faire évoluer le kit

Une amélioration utile à tous les sites se fait ici d'abord (proposer, valider, appliquer, noter au journal), puis se reporte dans les sites en cours qui en ont besoin.
