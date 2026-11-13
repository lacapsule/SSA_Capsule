---
name: web-contenu
description: Conçoit chaque page avant de l'intégrer (maquette en texte à faire valider) puis rédige ses textes à partir des seuls faits du mode d'emploi - patrons par type de page, intention de recherche, ton, longueurs, éléments manquants marqués. À utiliser avant d'intégrer une page, pour écrire ou reprendre un texte public, ou quand l'humain fournit du contenu brut à mettre en forme.
---

# Maquette en texte et rédaction

Principe : une page se conçoit pour son public (rule `00-organisation`, « Décider »), puis s'écrit avec des faits sourcés (rule `web-legal`, « Zéro fiction »). Ordre : maquette validée, puis textes, puis intégration.

## 1. Entrées

- Mode d'emploi : « Publics et action principale », « Ton et vocabulaire », « Données et contenus », « Contexte » (faits sourcés), questions du public (« Référencement »).
- Contenus fournis par l'humain (textes, documents, ancien site) : sources, jamais réécrits au point d'en changer le sens.
- Ce qui manque : marqué `à fournir : <qui>, <pour quand>` dans le mode d'emploi, et la section correspondante masquée.

## 2. Maquette en texte (à faire valider avant d'intégrer)

Une maquette par page de l'arborescence, dans `docs/maquettes/<page>.md` (« / » donne `accueil.md`, « /services/ » donne `services.md`, une partie variable donne `fiche` : « /actualites/<titre>/ » donne `actualites-fiche.md`, maquette commune à toutes les fiches ; une note après l'adresse est ignorée) : elle est la source des wireframes (skill `livrables`, livrable 03). Format :

```
Page : <titre>  ·  URL : /<adresse>/  ·  public : <qui>  ·  intention : « <question tapée> »
Action principale : <ce que le visiteur doit pouvoir faire en un écran>
Existant : <page remplacée, regardée en capture : ce qui marche, ce qui ne marche pas>
Références : <2 ou 3 adresses de sites réels du même type et du même métier> ; <ce qu'on en retient : ordre des blocs, densité, images, appels>

1. En-tête : logo, menu (<entrées>), appel à l'action « <libellé> »
2. <Section> : rôle (une phrase) ; contenu (faits, avec leur source) ; choix (`brique <id>`, `briques <id> + <id>` ou `nouveau bloc : <pourquoi aucune brique ne convient>`) ; ancien : <contenu du bloc remplacé> ; adaptation : <ce qui change pour l'univers du client>
3. ...
n. Pied de page : coordonnées, liens légaux, Accessibilité, plan du site

Masqué faute de donnée : <section> (à fournir : <qui>)
Liens entrants : <pages qui pointent ici>  ·  liens sortants : <voisines, rubrique>
```

- Nom de zone : un nom reconnu par les wireframes (en-tête, fil d'Ariane, héros, services, réalisations, équipe, témoignages, tarifs, questions fréquentes, chiffres clés, étapes, formulaire, plan d'accès, appel à prendre contact, pied de page) ; un autre nom donne un bloc de texte. Listes entre parenthèses ; bouton : libellé entre « » juste après « bouton », « lien » ou « appel à l'action » (« bouton « Nous rejoindre » et « Agenda » ») ; tout autre texte entre « » (slogan, titre) est dessiné comme du texte ; nombre d'éléments écrit (« 3 services ») ; zone dont le contenu manque : « à fournir : <qui> » dans sa ligne, dessinée en pointillés avec la mention « À fournir ».
- Séparateur de la ligne `Page :` : « · » entouré d'espaces ; le point médian de l'écriture inclusive (« participant·es ») reste dans son champ.
- Ordre des sections : ce que le public cherche d'abord, pas l'ordre d'un modèle. Accueil : un extrait de chaque rubrique, pas son contenu entier (rule `web-design`).
- **Concevoir avant d'intégrer** : une page se conçoit, elle ne se monte pas à partir des briques disponibles. Avant d'écrire les zones :
  - `Existant :` regarder la capture de la page remplacée (`docs/source/existant/captures/`) avant toute conception, pas seulement l'inventaire : ce qui marche (à garder ou à faire mieux), ce qui ne marche pas (à corriger) ;
  - `Références :` deux ou trois sites réels du même type et du même métier (champ `metier`), cités par leur adresse et ouverts pour de vrai (jamais une adresse supposée), et ce qu'on en retient pour cette page : ordre des blocs, densité, place des images, appels à l'action. On reprend des choix, pas une copie ;
  - pour chaque zone, le choix et sa raison tirée de ces deux notes : `brique <id>` (une brique de la boîte, `INDEX.md`, rule `boite-a-outils`), `briques <id> + <id>` (mélange) ou `nouveau bloc : <raison>` (aucune ne convient ; s'il est réutilisable, proposé ensuite à la boîte, skill `boite-a-outils`, « Ajouter »). Dans les trois cas, son `adaptation` à l'univers du client (skill `boite-a-outils`, « Adapter à l'univers »).
  Existant, références, ancien contenu, choix et adaptation sont des notes pour l'agent : retirées du document du client et de l'empreinte du livrable. Les wireframes signalent une maquette sans `Existant` (quand l'audit existe) ou sans `Références`, et une zone sans choix.
- **Reprendre tout le contenu** : la nouvelle page reprend tout ce que dit la page qu'elle remplace (histoire, présentation, listes, objectifs, chiffres, noms, liens), bloc par bloc. Reformuler, réorganiser et compléter sont permis, en vérifiant chaque fait contre la source ; résumer une section en deux phrases ou en quelques puces ne l'est pas. Le texte se lit d'abord comme un rédacteur : ce qui est clair et juste se garde, ce qui est confus se réécrit, ce qui est faux ou périmé se signale au client. Pour chaque zone qui remplace un bloc de l'ancien site, la maquette note `ancien : <ce que contenait le bloc>`, et le nouveau bloc en contient au moins autant. Une suppression se justifie dans la maquette (`Retiré : « extrait » (raison)`) et se fait valider. Contrôle : `check-mise-en-page.mjs` compare, mot porteur par mot porteur, chaque passage de la page remplacée (colonne « Remplace » de l'arborescence, ou même adresse) au texte de la nouvelle page, et bloque tout passage ni repris ni justifié.
- **Existant mesuré** : la note `Existant :` reprend les signes de l'univers avec leurs mesures et leur effet (livrable 01, « Univers visuel » : hauteur du haut de page en part de l'écran, taille de l'illustration, image qui déborde de sa forme…), regardés en grand sur la capture. Le contrôle compare la hauteur du haut de page à celle de la page remplacée.
- **Ordre de construction** : d'abord la page qui porte l'action principale du premier public, avec ses contenus vivants (événements, actualités, lieux, réalisations), puis les autres pages de contenu ; les pages fixes (légales, plan du site) en dernier. Une page ne se présente pas tant qu'un bloc prévu par sa maquette y est absent ou vide.
- Validation : par le client, sur le livrable 03 (wireframes, validation 2), notée dans « Décisions validées » (date, page). Sans validation, rien n'est intégré.

## 3. Patrons par type de page

| Page | Doit dire, dans cet ordre | Données structurées |
|------|---------------------------|---------------------|
| Accueil | qui, quoi, où, pour qui (une phrase) ; action principale ; extrait de chaque rubrique ; preuve réelle si elle existe (réalisations, chiffres sourcés) ; coordonnées | `Organization` ou `LocalBusiness`, `WebSite` |
| Service ou activité | le besoin du public dit avec ses mots ; ce qui est fait, comment, délais ou conditions ; prix s'il est public ; question suivante (FAQ courte affichée) ; action | `Service`, `FAQPage` seulement si affichée à l'identique |
| Fiche (réalisation, produit, membre, événement) | ce que c'est, faits datés, visuel réel avec `alt` utile, voisines et rubrique | selon le type (`Product`, `Event`, `Person`…) |
| À propos | histoire et personnes réelles, valeurs démontrées par des faits, jamais par des adjectifs | `Organization`, `Person` |
| Contact | moyens dans l'ordre de préférence du client, horaires, adresse et itinéraire, délai de réponse réel | `LocalBusiness` (`geo`, horaires) |
| Pages légales, accessibilité, 404, plan du site | skills `web-legal-fr`, `web-a11y-audit`, gabarit `page-404` | aucune en plus |

## 4. Écrire

- Un fait par phrase, sujet et verbe d'abord. Phrases de 20 mots en moyenne, paragraphes de 3 phrases au plus.
- Les mots du public (questions de l'interview), pas le jargon du métier ni un terme interne (rule `web-design`, « Icônes et contenu »).
- Titre `h1` : l'intention de la page en clair. Intertitres qui se lisent seuls, sans saut de niveau.
- Liens : le texte dit la destination (« Voir les tarifs de la taille de haies »), jamais « cliquez ici » ni « en savoir plus » seul.
- Appel à l'action : un verbe et l'objet (« Demander un devis »), le même libellé partout pour la même action.
- `title` et description : patron du skill `web-seo-structured-data`, depuis les données de la page.
- Interdits : superlatif sans preuve (« le meilleur », « leader »), avis, chiffre, client, label ou certification non fournis et sourcés ; texte de remplissage ; tiret cadratin (rule `web-method`) ; texte dans une image.
- Une information n'existe qu'à un endroit (`src/data/`, `src/content/`) : téléphone, horaires, prix et adresse sont lus, jamais recopiés dans une page.

## 5. Relire avant de livrer

1. Chaque fait a sa source (mode d'emploi, document fourni) ; sinon il sort du texte.
2. Lu à la place du public : il trouve l'action principale en un écran, à 390 px comme au bureau.
3. Orthographe et typographie françaises : espace insécable avant `:` `;` `?` `!` et dans les nombres, guillemets « », majuscules accentuées.
4. Aucun `à fournir`, TODO ni texte de démo dans ce qui part en ligne (skill `livraison`, ligne 6).
5. Le texte modifié par l'humain après relecture prime : on reporte sa version dans la source, on ne la « corrige » pas.
