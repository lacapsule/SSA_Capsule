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

Une maquette par page, dans le chat ou dans `docs/maquettes/<page>.md`. Format :

```
Page : <titre>  ·  URL : /<adresse>/  ·  public : <qui>  ·  intention : « <question tapée> »
Action principale : <ce que le visiteur doit pouvoir faire en un écran>

1. En-tête : logo, menu (<entrées>), appel à l'action « <libellé> »
2. <Section> : rôle (une phrase) ; contenu (faits, avec leur source) ; brique (`sections/…` de la boîte ou nouvelle)
3. ...
n. Pied de page : coordonnées, liens légaux, Accessibilité, plan du site

Masqué faute de donnée : <section> (à fournir : <qui>)
Liens entrants : <pages qui pointent ici>  ·  liens sortants : <voisines, rubrique>
```

- Ordre des sections : ce que le public cherche d'abord, pas l'ordre d'un modèle. Accueil : un extrait de chaque rubrique, pas son contenu entier (rule `web-design`).
- Pour chaque section : une brique de la boîte si elle existe (lire `INDEX.md`, rule `boite-a-outils`), sinon le dire.
- Validation humaine explicite, notée dans « Décisions validées » (date, page). Sans validation, rien n'est intégré.

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
