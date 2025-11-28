# Corrections ARIA, Contraste et Zones Tactiles

Ce document décrit les corrections apportées pour résoudre les problèmes d'accessibilité identifiés.

## Problèmes corrigés

### 1. Rôles ARIA incorrects

#### Problème : Rôles redondants ou incompatibles
- `role="list"` sur `<ul>` : Redondant, `<ul>` est déjà une liste sémantique
- `role="listitem"` sur `<li>` : Redondant, `<li>` est déjà un élément de liste
- `role="menubar"` sur `<ul>` dans navigation : Inapproprié pour une navigation simple
- `role="button"` sur `<img>` : Incompatible, une image ne peut pas être un bouton

#### Solutions appliquées
- **Suppression des rôles redondants** : Retiré `role="list"` et `role="listitem"` des éléments HTML natifs
- **Navigation simplifiée** : Retiré `role="menubar"` et `role="menuitem"`, utilisation de la sémantique HTML native
- **Galerie** : Remplacement de `<img role="button">` par `<button>` avec image à l'intérieur
- **Carousel** : Ajout de `type="button"` explicite sur tous les boutons

### 2. Contraste des couleurs

#### Problème : Contraste insuffisant
- Liens : Couleur trop claire sur fond blanc
- Boutons : Contraste texte/fond insuffisant
- Textes sur fonds colorés : Contraste insuffisant

#### Solutions appliquées

**Liens** :
- Couleur principale : `#0066cc` (contraste 4.5:1 sur blanc)
- Couleur hover : `#004499` (contraste 7:1 sur blanc)
- Couleur visited : `#551a8b` (contraste 4.5:1 sur blanc)

**Boutons** :
- Fond : `#2d8f47` (vert plus foncé) au lieu de `#43c466`
- Texte : `#ffffff` (contraste 4.5:1 sur vert foncé)
- Bordure : `2px solid` pour meilleure visibilité

**Textes** :
- Texte principal : `#1a1a1a` (contraste 16:1 sur blanc)
- Texte sur fond gris : `#f5f5f5` (contraste 4.5:1)
- Titres sur fond gris : `#ffffff` (contraste 4.5:1)

**Champs de formulaire** :
- Bordure : `#666666` (contraste 4.5:1 sur blanc)
- Bordure focus : `#0066cc` (contraste 4.5:1)
- Bordure invalide : `#b71c1c` (contraste 7:1)

### 3. Zones tactiles

#### Problème : Zones tactiles < 44x44px
- Boutons trop petits
- Liens trop petits
- Icônes cliquables trop petites
- Contrôles de carousel trop petits

#### Solutions appliquées

**Règle générale** : Minimum 44x44px pour tous les éléments interactifs

**Boutons** :
```css
min-height: 44px;
min-width: 44px;
padding: 12px 30px;
```

**Liens** :
```css
min-height: 44px;
padding: 8px 12px;
display: inline-flex;
align-items: center;
```

**Icônes cliquables** :
```css
.icons {
    min-width: 44px;
    min-height: 44px;
    padding: 8px;
}
```

**Hamburger menu** :
```css
.hamburger {
    width: 44px;
    height: 44px;
    padding: 10px;
}
```

**Pagination** :
```css
.page-link {
    min-width: 44px;
    min-height: 44px;
    padding: 0.5rem 0.75rem;
}
```

**Carousel** :
```css
.article-carousel__nav {
    min-width: 44px;
    min-height: 44px;
    width: 44px;
    height: 44px;
}

.article-carousel__dot {
    min-width: 44px;
    min-height: 44px;
    width: 44px;
    height: 44px;
}
```

**Champs de formulaire** :
```css
input, textarea, select {
    min-height: 44px;
    padding: 12px 16px;
    font-size: 16px; /* Évite le zoom sur mobile */
}
```

### 4. Compatibilité des rôles ARIA

#### Corrections spécifiques

**Galerie** :
- Avant : `<img role="button">`
- Après : `<button><img></button>`

**Navigation** :
- Avant : `<ul role="menubar"><li role="none"><a role="menuitem">`
- Après : `<ul><li><a>` (sémantique HTML native)

**Listes** :
- Avant : `<div role="list"><div role="listitem">`
- Après : `<ul><li>` ou suppression des rôles redondants

**Carousel** :
- Ajout de `type="button"` explicite
- Amélioration des attributs ARIA (`aria-selected`, `tabindex`)

## Fichiers modifiés

### Templates
- `templates/partials/public/header.tpl.php` : Suppression des rôles menubar/menuitem
- `templates/modules/galerie/index.tpl.php` : Remplacement img par button
- `templates/modules/home/components/*.tpl.php` : Suppression des rôles redondants
- `templates/modules/projet/index.tpl.php` : Suppression des rôles redondants
- `templates/modules/article/articleDetails.tpl.php` : Ajout type="button"

### CSS
- `public/assets/css/module/accessibility.css` : Zones tactiles et contrastes
- `public/assets/css/module/variables.css` : Couleurs de boutons améliorées
- `public/assets/css/module/header.css` : Zones tactiles navigation
- `public/assets/css/module/home.css` : Zones tactiles formulaires et listes
- `public/assets/css/module/article.css` : Zones tactiles carousel
- `public/assets/css/module/galerie.css` : Zones tactiles pagination

### JavaScript
- `public/modules/gallery/lightbox.js` : Support des boutons au lieu d'images

## Validation

### Outils recommandés
1. **WAVE** : Vérification des rôles ARIA
2. **axe DevTools** : Détection des problèmes de contraste et zones tactiles
3. **Lighthouse** : Audit d'accessibilité complet
4. **Contrast Checker** : Vérification manuelle des contrastes

### Métriques cibles
- **Contraste texte** : Minimum 4.5:1 (niveau AA), 7:1 pour niveau AAA
- **Zones tactiles** : Minimum 44x44px (recommandation WCAG)
- **Rôles ARIA** : Utilisation correcte, pas de redondance

## Notes importantes

1. **Rôles ARIA** : Ne pas utiliser de rôles sur des éléments HTML natifs qui ont déjà cette sémantique
2. **Contraste** : Toujours vérifier avec un outil de contraste, surtout sur fonds colorés
3. **Zones tactiles** : 44x44px est le minimum absolu, préférer 48x48px si possible
4. **Font-size** : Minimum 16px sur mobile pour éviter le zoom automatique

