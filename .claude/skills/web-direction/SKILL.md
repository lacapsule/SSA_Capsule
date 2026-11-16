---
name: web-direction
description: Conçoit l'apparence d'un site client avant de l'intégrer - concept visuel tiré de l'audit et de sites de référence du métier, trois maquettes composées (pas trois jeux de couleurs) sur les vrais contenus, choix du client, puis revue visuelle indépendante de chaque page intégrée face à l'ancienne. À utiliser après la validation des wireframes, avant d'intégrer une page, et avant de présenter une page à l'humain ou au client.
---

# Direction artistique et revue visuelle

Principe : une page réussie se dessine, elle ne s'obtient pas en respectant des règles. Les règles (`web-design`, `web-a11y`) disent ce qui est interdit ; ce skill dit comment arriver à une page meilleure que l'ancienne et que ses références. La composition (place et taille des images, rythme des sections, point focal, échelle des titres) se décide ici, sur maquette, avant l'intégration : l'intégration reproduit la maquette choisie, elle n'invente pas la mise en page.

Ordre (skill `web-new-site`, « Ordre de livraison ») : wireframes validés (2), puis concept, puis maquettes composées et choix du client (3), puis intégration.

## 1. Concept

Écrit dans `docs/maquettes/concept.md`, avant toute maquette. Sources : audit (livrable 01, « Univers visuel », captures de `docs/source/existant/`), fichiers fournis, champ `metier` et sa ligne de `livrables/donnees/directions.json` (`composition`, `eviter`).

```
Intention : <trois mots, ex. chaleureux, populaire, vivant>
Signe fort : <ce qui fait reconnaître le client et sera exploité partout : personnage, illustration, couleur, photo, forme>
Ce que l'ancien site fait bien : <à garder ou à faire mieux, regardé en grand sur les captures>
Ce qui l'affaiblit : <ce qu'on corrige>
Références : <3 sites réels du métier, ouverts et capturés dans docs/source/references/>
  <adresse> : hiérarchie, rythme, images, typographie, identité : <ce qu'on en retient pour ce client>
Point fort par page : <page> : <l'élément qui attire l'œil en premier et pourquoi>
```

Une référence est un vrai site, ouvert pour de vrai et capturé (jamais une adresse supposée). On en retient des choix (rythme, place des images, traitement des titres), pas une copie.

## 2. Trois maquettes composées

Après le concept, une maquette HTML par piste dans `docs/maquettes/pistes/<n>-<cle>/` (dossiers des pistes du livrable 04 : `1-fidele`, `2-modernisee`, `3-contrastee`) :

- `composition.md` : en tête, une phrase pour le client qui dit le principe de la composition ; ensuite, pour l'agent, ce qui la distingue des deux autres.
- `accueil.html` et au moins une page intérieure (même nom de fichier dans chaque piste), avec les vrais textes, photos et illustrations du client (chemins relatifs vers `docs/source/`), une feuille `composition.css` en `var(--…)` seulement : les tokens de la piste (couleurs, polices, arrondis) sont posés par-dessus à la capture. Chaque maquette lie aussi `../../../livrables/04-direction-artistique/pistes/<n>-<cle>/tokens.css` avant `composition.css`, pour s'ouvrir telle quelle dans un navigateur ; le PDF réduit les pages entières, le client regarde les maquettes en HTML ou en capture.
- **Les trois pistes diffèrent par la composition**, pas seulement par les couleurs : haut de page (photo pleine largeur, photo et texte côte à côte, aplat de couleur avec illustration…), rythme des fonds, place du signe fort, taille des images, traitement des titres, forme des blocs. Le générateur compare la composition des trois accueils (fond, colonnes et visuel de chaque section) et met le livrable en brouillon si deux pistes se ressemblent à 70 % ou plus.
- La fidèle garde la composition de l'ancien site en corrigeant ses défauts ; les deux autres partent du concept et des références.
- Briques de la boîte : un point de départ possible, jamais une obligation. Si aucune ne sert la composition, la maquette dessine un nouveau bloc ; après l'intégration, il est proposé à la boîte (skill `boite-a-outils`, « Ajouter »).

`make livrables` capture chaque maquette (1280 et 390 px) dans le livrable 04. Avant de le présenter : revue visuelle de chaque maquette (section 3), puis `make apercu ID=04-direction-artistique`. Validation 3 : `make valider ID=04-direction-artistique PAR="…" PISTE=n`, puis `make appliquer` (tokens de la piste dans le site). L'intégration reproduit ensuite la maquette retenue, page par page, en composants et sections du socle.

## 3. Revue visuelle

Jugement indépendant, avant de présenter une maquette ou une page intégrée. Celui qui a construit la page ne la juge pas seul : la revue est confiée à un sous-agent neuf (outil Agent), qui ne reçoit que les images et la grille, jamais l'avis de l'agent qui a construit.

1. Captures : la page (1280 et 390 px, `.claude/etat/comparaison/` pour une page intégrée, `docs/livrables/04-direction-artistique/pistes/*/maquettes/` pour une maquette), la page qu'elle remplace (`docs/source/existant/captures/`), les références du concept.
2. Demande au sous-agent : « Tu es directeur artistique. Compare la nouvelle page à l'ancienne et aux références, critère par critère, sans indulgence : hiérarchie (un point focal par écran, ordre de lecture évident), rythme (les sections se distinguent, aucune monotonie), images (taille, qualité, place des photos et illustrations du client, personnages entiers et jamais réduits à une vignette), typographie (échelle, caractère, contraste de tailles), identité (on reconnaît ce client, pas un gabarit), finition (alignements, espacements proportionnés et assez d'air entre les sections, aucun vide déséquilibré, états soignés, menu et langue sobres et explicites). Pour chaque critère : meilleure, égale ou moins bonne que l'ancienne, avec ce qu'on voit sur l'image. Puis les trois corrections les plus utiles. »
3. Écrire l'avis dans `docs/maquettes/revues/<page>.md` (même nom que la maquette en texte) :

```
Page : /<adresse>/  ·  revue du AAAA-MM-JJ  ·  captures : <fichiers>
Hiérarchie : meilleure | égale | moins bonne : <constat>
Rythme : …
Images : …
Typographie : …
Identité : …
Finition : …
Corrections : <les trois plus utiles>
Verdict : meilleure | à reprendre
```

- Verdict « meilleure » seulement si aucun critère n'est « moins bonne » et que la page fait mieux que l'ancienne sur au moins trois critères. Sinon « à reprendre » : corriger, recapturer, refaire la revue par un nouveau sous-agent.
- `check-mise-en-page.mjs` (dans `make verify`) bloque une page dont la revue n'est pas « meilleure » ; une page sans revue est un avertissement pendant la construction et une erreur en recette (`RECETTE=1`).
- La revue ne remplace pas le regard de l'humain : les captures côte à côte lui sont montrées avec la revue.
