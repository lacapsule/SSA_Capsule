# Améliorations d'accessibilité

Ce document récapitule toutes les améliorations d'accessibilité apportées au site public, conformes aux normes WCAG 2.1 niveau AA.

## ✅ Améliorations implémentées

### 1. Navigation et structure

- **Skip link** : Ajout d'un lien "Aller au contenu principal" pour la navigation clavier
- **Landmarks ARIA** : 
  - `role="banner"` sur le header
  - `role="navigation"` sur les menus
  - `role="main"` sur le contenu principal
  - `role="contentinfo"` sur le footer
- **Hiérarchie des titres** : Respect de la hiérarchie H1 → H2 → H3
- **IDs uniques** : Tous les titres de section ont des IDs pour les ancres

### 2. Images

- **Alt descriptifs** : Toutes les images ont des attributs `alt` descriptifs
- **Images décoratives** : Utilisation de `alt=""` ou `role="presentation"` pour les images décoratives
- **Lazy loading** : Images non critiques avec `loading="lazy"`
- **fetchpriority** : Images critiques avec `fetchpriority="high"`
- **Dimensions** : Ajout de `width` et `height` pour éviter les layout shifts

### 3. Formulaires

- **Labels** : Tous les champs ont des labels (visuels ou `aria-label`)
- **Classe visually-hidden** : Labels masqués visuellement mais accessibles aux lecteurs d'écran
- **Validation** : 
  - `aria-required="true"` pour les champs obligatoires
  - `aria-invalid` pour les champs en erreur
  - Messages d'erreur avec `role="alert"` et `aria-live="polite"`
- **Honeypot** : Champs anti-spam avec `tabindex="-1"` et `aria-hidden="true"`

### 4. Navigation clavier

- **Focus visible** : Styles CSS pour rendre le focus visible sur tous les éléments interactifs
- **Ordre de tabulation** : Ordre logique respecté
- **Skip link** : Permet de sauter la navigation principale
- **Touches clavier** : Support des touches Entrée et Espace sur les éléments interactifs

### 5. Galerie et lightbox

- **Rôles ARIA** : `role="list"` et `role="listitem"` pour les listes
- **Lightbox accessible** :
  - `role="dialog"` avec `aria-modal="true"`
  - `aria-labelledby` pour le titre
  - `aria-hidden` géré dynamiquement
  - Navigation clavier (flèches, Escape)
  - Focus trap (focus sur le bouton de fermeture à l'ouverture)
  - Retour du focus à l'image source à la fermeture
- **Pagination** : `aria-current="page"` pour la page active, `aria-live="polite"` pour les infos

### 6. Carrousels

- **ARIA** : `aria-roledescription="carousel"` et `aria-label` pour les contrôles
- **Slides** : `aria-hidden` géré pour chaque slide
- **Dots** : `aria-selected` et `tabindex` gérés dynamiquement

### 7. Listes et structures

- **Rôles** : `role="list"` et `role="listitem"` sur toutes les listes
- **Sémantique** : Utilisation de `<ul>`, `<ol>`, `<dl>` appropriés
- **Listes de définitions** : Utilisation de `<dl>`, `<dt>`, `<dd>` pour les métadonnées

### 8. Liens et boutons

- **Labels descriptifs** : `aria-label` sur les liens iconiques
- **Nouvelle fenêtre** : `rel="noopener noreferrer"` et indication dans `aria-label`
- **Boutons** : Utilisation de `<button>` au lieu de `<a>` pour les actions
- **Groupes** : `role="group"` avec `aria-label` pour les groupes de boutons

### 9. Styles d'accessibilité

Fichier `accessibility.css` créé avec :
- **Skip link** : Styles pour le lien de saut
- **visually-hidden** : Classe pour masquer visuellement mais garder accessible
- **Focus visible** : Styles pour tous les éléments interactifs
- **États invalides** : Styles pour les champs de formulaire invalides
- **Reduced motion** : Respect de `prefers-reduced-motion`

### 10. Contraste et lisibilité

- **Couleurs** : Contraste suffisant pour les liens et textes
- **Focus** : Contraste élevé pour les indicateurs de focus
- **États** : États hover/focus/active bien différenciés

## 📋 Checklist WCAG 2.1

### Niveau A
- [x] Alternatives textuelles pour toutes les images
- [x] Contrôles par clavier
- [x] Pas de contenu clignotant
- [x] Structure sémantique
- [x] Labels pour les formulaires
- [x] Navigation cohérente

### Niveau AA
- [x] Contraste suffisant (4.5:1 pour le texte)
- [x] Redimensionnement du texte jusqu'à 200%
- [x] Focus visible
- [x] Navigation clavier complète
- [x] Messages d'erreur identifiables
- [x] Titres et labels descriptifs

### Niveau AAA (partiel)
- [x] Signification non portée uniquement par la couleur
- [x] Contraste amélioré pour certains éléments
- [ ] Langue des passages (à améliorer si nécessaire)

## 🔧 Outils de validation

### Tests recommandés

1. **WAVE** : https://wave.webaim.org/
   - Extension navigateur pour audit en temps réel
   - Détecte les erreurs et avertissements d'accessibilité

2. **axe DevTools** : https://www.deque.com/axe/devtools/
   - Extension Chrome/Firefox
   - Tests automatisés WCAG

3. **Lighthouse** : Outil intégré Chrome DevTools
   - Audit d'accessibilité inclus dans Lighthouse

4. **Navigation clavier** : Test manuel
   - Tabulation dans tout le site
   - Vérification du focus visible
   - Test du skip link

5. **Lecteur d'écran** : Test avec NVDA ou JAWS
   - Vérification de la navigation
   - Vérification des labels et descriptions

## 📝 Notes importantes

### Images
- Toutes les images doivent avoir un `alt` descriptif
- Images décoratives : `alt=""` ou `role="presentation"`
- Images importantes : `alt` descriptif du contenu

### Formulaires
- Tous les champs doivent avoir un label
- Messages d'erreur clairs et associés aux champs
- Indication visuelle et textuelle des champs obligatoires

### Navigation
- Le skip link doit être le premier élément focusable
- Ordre de tabulation logique
- Focus visible sur tous les éléments interactifs

### Contraste
- Minimum 4.5:1 pour le texte normal
- Minimum 3:1 pour le texte large (18pt+)
- Vérifier avec un outil de contraste

## 🚀 Prochaines améliorations possibles

1. **Landmarks supplémentaires** : Ajouter `role="search"` si une recherche existe
2. **Live regions** : Utiliser `aria-live` pour les mises à jour dynamiques
3. **Skip links multiples** : Ajouter des liens vers les sections principales
4. **Mode sombre** : Respecter `prefers-color-scheme`
5. **Taille de police** : Contrôle utilisateur pour ajuster la taille
6. **Langue** : Indiquer la langue des passages dans une autre langue

## 📚 Ressources

- [WCAG 2.1 Guidelines](https://www.w3.org/WAI/WCAG21/quickref/)
- [ARIA Authoring Practices](https://www.w3.org/WAI/ARIA/apg/)
- [WebAIM](https://webaim.org/)
- [A11y Project](https://www.a11yproject.com/)

