# Design system, mise en page et finition

## Tokens (N0, N1, N2)

- N0 : couleurs, typo, espaces, rayons, durées, courbes. Seul endroit des valeurs brutes (`#hex`, `px`).
- N1 : composants (bouton, carte, champ, lien) en `var(--*)` uniquement.
- N2 : sections (bloc réutilisé d'une page à l'autre : héros, services, témoignages) et pages, qui assemblent des N1. Fichiers : `src/styles/sections/` et `src/styles/pages/` du socle statique. Avant toute classe nouvelle : étendre un token ou un composant existant.
- Toute `var(--x)` utilisée est définie. Une seule source par token, pas de doublon d'échelle.
- Un rôle visuel = un composant. États prévus : repos, survol, focus clavier, actif, désactivé, chargement, vide, erreur.
- Ordre des propriétés CSS : `position`, `display`, `width`, `height`, `margin`, `padding`, `border`, `font`, `color`, `background`, `transform`, `animation`, `transition`, `overflow`, `z-index`.

## Conventions de base (contrôle mesuré)

Ce qu'un intégrateur applique sans qu'on le lui écrive, quel que soit le client. Contrôle : `check-mise-en-page.mjs` (skill `web-audit`), dans `make verify`, à 390, 800, 1280 et 1920 px sur chaque page.

- **En-tête sur une seule ligne**, à toute largeur : logo à gauche (lien vers l'accueil), menu, puis actions (langue, bouton principal) à droite ; 5 rem de haut au plus. Quand le menu ne tient pas sur la ligne, un bouton « Menu » le remplace sur cette même ligne. Jamais de menu sur une deuxième rangée sous le logo.
- **Menu sobre** : page courante et survol marqués par un soulignement (filet fonctionnel) ou la couleur d'action, jamais par un fond ou une pastille derrière le lien. Contrôle : fond de la page courante.
- **Langue explicite** : dans l'en-tête, un bouton de langue (icône, langue affichée, liste des autres langues), jamais le nom d'une autre langue seul (« Brezhoneg » isolé ne se comprend pas). La liste complète en ligne reste au pied de page. Contrôle : lien `hreflang` seul dans l'en-tête.
- **De l'air** : grandes sections espacées de `--space-6` (4 à 7 rem) en hauteur, contenu à 3 rem au moins du bord haut de sa section sur grand écran, écarts généreux entre colonnes et cartes. Une page serrée paraît bon marché. Contrôle : marge haute mesurée à 1280 et 1920 px.
- **Tailles bornées**, dans l'échelle de la charte (`--step-*`, générée) : titre principal 2,75 rem au plus (4 rem pour le titre du haut de page, souvent le point focal), titres de section 2 rem au plus, texte courant 1,0625 rem (1,125 rem au plus), introduction 1,25 rem au plus. Un titre tient sur 3 lignes au plus à 1280 px : sinon le raccourcir ou réduire sa taille.
- **Pied de page en colonnes** (coordonnées, pages, légal, langue) sur grand écran, empilées sur téléphone, avec la ligne de crédit du prestataire et son lien (`.claude/kouzia.json`, une seule source ; complément propre au projet, ex. « au sein de La Capsule », dans la configuration du site). Contrôle : lien présent sur chaque page.
- **Page ou fenêtre** : un contenu partageable ou à référencer (événement, article, fiche, photo d'une galerie publiée) a sa vraie page et son adresse ; une fenêtre (`dialog`) peut l'ouvrir en amélioration progressive (le lien mène à la page, la fenêtre s'ouvre si JavaScript est là). Un contenu éphémère (confirmation, aide, agrandissement sans valeur propre) : fenêtre seule.
- **Un nouveau bloc a la finition d'une brique** : états de survol, focus, actif, icône, mise en forme (carte, vignette), comme une brique de la boîte ; jamais de simples liens texte là où le modèle attend un composant (ex. navigation précédent et suivant entre fiches : brique `sections/navigation-fiches`).
- **Choix nombreux** : jusqu'à 7 options (filtre, catégorie, onglet), une rangée de boutons visibles, un clic ; au-delà, une liste déroulante (`select`, ou champ à suggestions si l'on cherche dans la liste). Une longue liste de contenus (lieux, fiches) ne s'affiche pas en entier par défaut : phrase de synthèse (nombre, comment choisir), filtre et recherche, bouton « Tout afficher » ; tout le contenu reste dans le HTML (moteurs, sans JavaScript).
- **Un seul bouton principal par écran** ; les autres actions en bouton secondaire ou en lien.
- **Rien de fixe posé sur un contenu** (bouton de retour en haut, bandeau, bulle) : réserver sa place ou le mettre dans le flux.
- Aucun défilement horizontal.

## Qualité visuelle (contrôle mesuré et revue)

Les conventions disent ce qu'il ne faut pas faire ; une page doit aussi être réussie. La composition se dessine sur maquette avant l'intégration (skill `web-direction`), jamais en empilant des briques.

- **Un point focal par écran** : l'œil sait où aller (grand titre, photo, illustration, chiffre) ; jamais un écran fait seulement de blocs de même poids.
- **Rythme** : les sections se distinguent par le fond (blanc, teinte claire de la marque, aplat, photo), la disposition (colonnes, image à gauche ou à droite, pleine largeur) et le traitement du titre. Contrôle : pas plus de 3 grandes sections de suite au même fond et au même style de titre (3 en avertissement, 4 bloquant).
- **Le signe fort du client se voit** (personnage, illustration, photo, forme) dans plusieurs sections, comme fil conducteur, pas une seule fois en décor.
- **Photos et illustrations à leur taille** : une photo du client est assez grande pour se lire ; un voile sur une photo de haut de page garde la photo visible (dégradé ou voile léger, texte lisible par sa taille et son ombre plutôt que par une photo éteinte).
- **Pas de vide déséquilibré** : une liste à un ou deux éléments change de disposition (élément mis en avant, pleine largeur) au lieu de laisser deux tiers d'une ligne vides (skill `web-ui-states`, « Listes à tous les volumes »).
- **Illustrations entières et à leur taille** : un personnage n'est jamais rogné (mains, doigts, tête) par un masque ou un cadre ; il est plus grand que le rond ou l'aplat qui le porte et en déborde, jamais réduit à une vignette dans son rond. Le rond est sans anneau ni bordure décorative. Le bras ou le coude qui sort du rond reste entier : variante de masque du côté du bras (brique `composants/portail-personnage`, main droite ou main gauche), vérifiée sur capture pour chaque personnage.
- **Relief des personnages et illustrations** : jamais un dessin posé à plat sans profondeur. Le rond reste un aplat de la couleur de marque, sans dégradé (demande de l'humain : « pas de dégradé dans un fond jaune ») ; le relief vient d'une ombre douce dessous et du personnage qui déborde ; jamais d'ombre (`drop-shadow`) sur une image masquée, que le masque couperait net (filet clair au bord du bras, constaté sur la SSA). Tout en tokens (`color-mix` des couleurs de la charte), coupé en mode contrasté si besoin. Une illustration reprise d'une banque d'images ne garde que les couleurs de la charte : les autres sont remplacées (ex. le bleu-violet `#6c63ff` des dessins unDraw).
- **Un personnage, une place** : un même personnage ou une même illustration ne revient pas deux fois sur une page, et pas sur deux pages voisines du parcours (accueil, page suivante) quand d'autres existent. Inventaire des illustrations du client (audit, `docs/source/fournis/`, assets de la boîte du même style, par exemple `composants/personnages-illustres` recolorés aux couleurs du client) avant d'en placer une ; à défaut d'assez d'illustrations, le dire et demander les manquantes plutôt que répéter.
- **Logos de partenaires et financeurs** : jamais une bande maigre isolée entre deux sections ; ils rejoignent la section qui en parle (qui sommes-nous, partenaires) sur des cartouches de même hauteur, à une taille lisible. Tout logo d'une structure (partenaire, financeur, membre, client) mène à son site quand il existe : lien sur le logo ou sur toute la carte, adresse lue dans la même source que le logo (table ou configuration modifiable), jamais écrite dans le gabarit ; sans site connu, pas de lien inventé.
- **Mouvement attendu** : une bande de photos ou de logos que le client imagine animée se décide avec lui (défilement au geste, ou défilement continu avec pause, section « Mouvement »), jamais laissée immobile sans le dire ni animée sans décision écrite. Contrôle : mesures et captures de `check-mise-en-page.mjs` en mouvement réduit (état final).
- **Le pied de page ne domine pas** : il est plus calme que le contenu (fond sombre ou neutre, ou couleur de marque atténuée), jamais l'aplat le plus voyant de la page.
- Contrôle : revue visuelle indépendante de chaque page, face à la page remplacée et aux références (skill `web-direction`, « Revue visuelle »), verdict lu par `check-mise-en-page.mjs`.

## Univers du client

Un site se conçoit dans l'univers de son client, pas dans celui de la boîte à outils : couleurs et polices ne suffisent pas.

- L'univers se relève à l'audit (skill `livrables`, livrable 01, « Univers visuel ») : illustrations et icônes, forme des images (rondes, en carte, pleine largeur, détourées), ornements de titre (trait, soulignement), puces, fonds de section, cartes (arrondi, ombre), traitement des photos (voile, cadrage), pastilles et étiquettes.
- La piste fidèle reprend ces signes ; les deux autres les réinterprètent (livrable 04, « Univers »). Un signe sert la composition (fil conducteur, point focal), il ne se répète pas mécaniquement sur chaque titre ou chaque carte.
- Une page se conçoit avant de s'intégrer, face à la page qu'elle remplace et à des sites de référence du même métier (skill `web-contenu`, « Concevoir avant d'intégrer ») : elle doit être meilleure que les deux, pas seulement conforme aux règles.
- Une brique de la boîte est un point de départ (code, accessibilité, comportements), jamais l'apparence finale : chaque brique reprise est adaptée à l'univers (skill `boite-a-outils`, « Adapter à l'univers »).
- Contrôle : chaque page à côté de celle qu'elle remplace (`.claude/etat/comparaison/`, produit par `check-mise-en-page.mjs`), signes de l'univers repris nommés (skill `livraison`, ligne 5). Une page qui ressemble plus à la démo de la boîte qu'au client est à reprendre.

## Centrage (règle de décision, contrôle mesuré)

Un bloc plus étroit que son conteneur est centré, sauf s'il forme une colonne avec un voisin (barre latérale, sommaire, récapitulatif). Ne jamais laisser une colonne étroite collée à gauche avec un vide à droite.

- **Centrer** : en-tête d'une page isolée (connexion, confirmation, merci, 404, page légale, plan du site) ; état vide, état de réussite, résultat d'action ; frise d'étapes ; colonne de formulaire seule (adresse, profil, mot de passe, contact sans encart) avec son titre et ses boutons d'action ; barre d'onglets d'une colonne étroite (onglets segmentés pleine largeur de la colonne) ; groupe de boutons sous un texte centré ; boîte de dialogue ; grille de cartes plus étroite que le conteneur.
- **Aligner à gauche** : texte courant de plus de deux lignes (dans une colonne elle-même centrée), libellés et champs à l'intérieur d'un formulaire, tableaux, listes de données, menus, fil d'Ariane.
- Une page de contenu = en-tête et corps sur le même axe : en-tête centré, corps centré ; en-tête à gauche, corps aligné sur la même marge.
- Contrôle : marges gauche et droite mesurées (`getBoundingClientRect`), écart < 3 px à 360, 800, 1280 et 1920 px. Procédure et script : skill `web-centering`.

## Mise en page fluide

- Polices en tokens définis une fois (`--font-ui`, `--font-mono`), jamais de `font-family` en dur dans un composant.
- Typo et espacements en `clamp()` ou `rem` via tokens. `px` réservé aux filets de 1 px.
- 320 px à grand écran sans scroll horizontal. Grilles en `minmax`/`auto-fit`, pas en largeurs d'appareils. Mesure de lecture 65 caractères maximum.
- Interface centrée et adaptée jusqu'aux écrans de 27 pouces (2560 × 1440) : conteneur principal centré (`margin-inline: auto`), largeur maximale fluide qui grandit avec l'écran au lieu de figer une colonne étroite, typo, densité et cibles en `clamp()` qui suivent la largeur. Le texte courant garde sa mesure de lecture ; les listes, tableaux et tableaux de bord occupent la largeur utile. Contrôle visuel obligatoire à 360, 800, 1280, 1920 et 2560 px.
- Lisibilité : corps de texte ≥ 1,0625 rem ; un texte de plus de 3 lignes ne va jamais dans une carte.
- Accueil : un extrait de chaque rubrique, pas son contenu entier. Un catalogue, un parcours ou une longue FAQ a sa propre page et sa propre URL.
- Navigation : toute page est atteignable depuis le menu principal ; pas de page joignable uniquement par le plan du site ou un bouton.
- Header fixe : `scroll-padding-top` égal à sa hauteur réelle.
- Centrage optique dans une barre asymétrique : `position: absolute; left: 50%; translate: -50%`, hors du flux.

## Mouvement

- Le mouvement confirme une action, il ne décore pas. Animer `opacity` et `transform` uniquement.
- Retour ~150 ms, entrée ~300 ms, une courbe en token. Contenu lisible sans attendre l'animation.
- `prefers-reduced-motion: reduce` : animations et défilement doux coupés.
- Minimum attendu, posé dès l'intégration des pages de la maquette retenue (le client juge une page vivante, pas une page figée) et vérifié à la recette : survol et focus des cartes, boutons et liens d'action (`transform` et ombre ou fond, ~150 ms) ; entrée des éléments au défilement (`opacity` et `transform`, une fois, ~300 ms, en cascade courte entre cartes voisines, jamais sur ce qui est déjà à l'écran au chargement : ni retard de lecture ni de LCP ; contenu visible si JavaScript manque ; brique `composants/apparition-defilement`) ; ouverture et fermeture des volets, menus et fenêtres ; retour visuel d'un envoi de formulaire. Rien de plus sans raison écrite.
- Interdit : parallaxe, défilement détourné, boucle décorative, `filter: blur` sur grande surface. Seule exception, décidée avec le client et écrite dans « Décisions validées » : une bande de photos ou de logos qui défile en continu, avec bouton Pause visible (WCAG 2.2.2), arrêt au survol et au focus, bande fixe qu'on fait glisser en mouvement réduit, vignettes légères chargées à l'approche (briques `sections/bande-photos-defilante`, `sections/logos-defilants`).
- Interdit : la bordure gauche épaisse et colorée comme accent d'une carte, d'un encadré, d'un bandeau ou d'une tuile (cliché des interfaces générées), à l'écran comme dans les PDF et les e-mails. La hiérarchie passe par la taille, la graisse, l'espace, un fond ou un contour complet de 1 px. Seule exception : le filet d'une citation de message (blockquote).
- Interdit : le tiret cadratin déguisé (autre cliché des interfaces générées) : trait court en `::before`/`::after` devant un sur-titre, un intitulé ou une puce, `content` en tiret (y compris par échappement), image ou SVG de tiret. À la place : rien, une petite forme pleine ou une icône. Les filets fonctionnels (séparateur, fil d'étapes, poignée, frise) restent permis.

## Icônes et contenu

- Icônes en SVG local généré au build (ex. Font Awesome Free). Interdit : CDN, kit, police d'icônes, emoji ou caractère Unicode comme icône d'interface.
- Icône décorative `aria-hidden="true"` ; icône seule dans un contrôle : nom accessible sur le contrôle.
- Aucun texte de démo en production : une donnée manquante masque la section.
- Aucun terme interne dans un texte public (nom de variable, d'outil, de fonctionnalité technique) : se relire à la place du public du mode d'emploi.
