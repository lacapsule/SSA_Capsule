# Optimisation des performances

Ce document décrit les optimisations mises en place pour améliorer le score ecoindex et les performances générales du site.

## Optimisations implémentées

### 1. CSS et JavaScript

- **Concaténation** : Les fichiers CSS sont concaténés en un seul fichier (`global.min.css` et `dashboard.min.css`)
- **Minification** : Réduction de ~30-40% de la taille des fichiers
- **Réduction des requêtes HTTP** : De 6+ fichiers CSS à 1 seul fichier pour le dashboard

**Utilisation** :
```bash
make build-assets
```

Les fichiers minifiés sont automatiquement utilisés dans les templates.

### 2. Compression et Cache

- **Compression GZIP** : Activée pour HTML, CSS, JS, SVG et fonts
- **Cache navigateur** : Headers de cache optimisés (1 an pour les assets statiques)
- **Cache-Control** : Headers `immutable` pour les assets versionnés

### 3. Images

- **Lazy loading** : Toutes les images (sauf celles au-dessus de la ligne de flottaison) utilisent `loading="lazy"`
- **fetchpriority** : Images critiques (logo, bannière) utilisent `fetchpriority="high"`
- **Format WebP** : Les images sont converties en WebP pour réduire la taille

**Optimisation manuelle des images** :
```bash
php bin/optimize-images.php
```

### 4. Polices

- **Preload** : La police principale est préchargée avec `<link rel="preload">`
- **Format optimisé** : Utilisation de TTF (peut être converti en WOFF2 pour une meilleure compression)

### 5. Requêtes HTTP

- **Réduction du nombre de requêtes** : 
  - CSS : De 6+ fichiers à 1 fichier
  - Images : Lazy loading pour réduire les requêtes initiales
  - Modules JS : Conservés en modules ES6 pour le tree-shaking

## Résultats attendus

- **Poids de la page** : < 1.024 Mo (cible ecoindex)
- **Requêtes HTTP** : < 40 requêtes (cible ecoindex)
- **Complexité DOM** : < 600 éléments (déjà respecté)

## Commandes utiles

```bash
# Construire les assets optimisés
make build-assets

# Optimiser les images
php bin/optimize-images.php

# Vérifier les tailles
du -sh public/assets/css/*.min.css
du -sh public/assets/img
```

## Améliorations futures possibles

1. **Conversion des polices en WOFF2** : Réduction supplémentaire de ~30%
2. **Service Worker** : Mise en cache des assets pour les visites suivantes
3. **CDN** : Distribution des assets via un CDN
4. **HTTP/2 Server Push** : Push des assets critiques
5. **Critical CSS** : Extraction du CSS critique pour le rendu initial

## Notes

- Les fichiers `.min.css` et `.min.js` sont générés automatiquement
- Les templates utilisent automatiquement les versions minifiées
- En développement, vous pouvez utiliser les fichiers non-minifiés en modifiant les templates

