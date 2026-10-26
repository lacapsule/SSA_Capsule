# Modèle de site client Kouzia

Point de départ de chaque nouveau site client : le kit `.claude` commun (règles, skills, hooks), relié à la boîte à outils `../kouzia-boite-a-outils`.

## Créer un site client

1. Copier ce dossier dans `Documents/GitHub/`, sous le nom du futur dépôt (minuscules, mots séparés par `-`), par exemple `boulangerie-martin`. Le dossier doit rester à côté de `kouzia-boite-a-outils`.
2. Supprimer le `README.md` copié (il décrit le modèle, pas le site).
3. Ouvrir Claude Code dans le nouveau dossier. Le hook de démarrage voit qu'il n'y a pas de mode d'emploi et lance l'interview (skill `interview`).
4. Après l'interview, le mode d'emploi est écrit dans `.claude/rules/projet.md`, validé par l'humain, puis le travail commence étape par étape.

On peut aussi demander à Claude : « crée le site <nom> depuis le modèle ».

## Contenu

- `.claude/rules/` : règles communes (`00-organisation` prime), dont `boite-a-outils` : lire l'index de la boîte avant de coder.
- `.claude/skills/` : procédures (interview, livraison, audit, design system, boîte à outils…). Modèle de socle statique PHP : `skills/web-new-site/modeles/php-statique/`.
- `.claude/hooks/` : vérification du mode d'emploi au démarrage, refus de l'arrêt d'un processus par son nom.
- `.claude/journal-kit.md` : historique des changements du kit.

## Faire évoluer le kit

Une amélioration utile à tous les sites se fait ici d'abord (proposer, valider, appliquer, noter au journal), puis se reporte dans les sites en cours qui en ont besoin.
