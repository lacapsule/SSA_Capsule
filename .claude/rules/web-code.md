# Code front (HTML, CSS, JS)

- Statique d'abord. Rendu serveur ou générateur statique pour le contenu ; JavaScript client seulement pour une interaction réelle (îlot, amélioration progressive).
- HTML sémantique avant ARIA : `button` pour une action, `a` pour une navigation, `details` pour un dépliant, `dialog` pour une modale. Le site reste utilisable sans JS pour le contenu et les formulaires.
- Pas de framework, préprocesseur ou bibliothèque sans besoin prouvé. Chaque dépendance ajoutée est justifiée par écrit.
- Interdit à l'exécution : `eval`, `new Function`, `innerHTML` et ses équivalents (`outerHTML`, `insertAdjacentHTML`, `DOMParser`, `createContextualFragment`) avec une donnée externe non échappée, `document.write`, `window.prompt`/`alert` comme interface. Seule exception à un équivalent : une page du même site analysée sans script (fenêtre qui charge un formulaire du back-office), justifiée en commentaire.
- CSS : propriétés rangées par familles (personnalisées, position, affichage, dimensions, marges, bordure, typographie, couleur, fond, ombre, transform, animation, transition, débordement, `z-index`), tri stable qui ne change pas la cascade. Contrôle : `node .claude/skills/web-audit/scripts/ordre-css.mjs --check <dossier des styles>` (dans `make check`) ; correction : la même commande sans `--check`, puis relire le diff. Code tiers (`vendor/`) laissé tel quel.
- Événements : délégation sur un conteneur, initialisation une seule fois (marqueur `data-*`), pas d'écouteur sur une interface masquée. Les identifiants partagés entre HTML, CSS et JS sont stables ; en renommer un oblige à mettre à jour les trois.
- État d'interface : la vue reflète toujours l'état après une action, sans rafraîchissement parasite (skill `web-ui-states`).
- Stockage navigateur : seulement des préférences légères. Ce qui se déduit en CSS ne se stocke pas.
- Erreurs : toute ressource absente ou erreur serveur aboutit à la page d'erreur du site (même layout que les autres pages, statut 404/500 réel), y compris pour les URL dynamiques (article, fiche) introuvables. Contrôlé à chaque livraison avec une URL inexistante.
- Navigateurs : tester Chromium, Firefox (Gecko) et Safari (WebKit), mobile compris. Cible : versions courantes, pas d'IE ni Edge legacy. Fonction récente : vérifier sa disponibilité (Baseline) ou prévoir un repli.
- JSON et données : valides, typées si possible (TypeScript, schéma), jamais de secret dedans.
- Liens et chemins : relatifs à la racine du site ou générés par le framework, jamais de chemin absolu de la machine de dev.
