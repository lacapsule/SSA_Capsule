# Optimisation de la chaîne de requêtes critiques

Ce document décrit les optimisations apportées pour réduire la latence de chemin d'accès critique (Critical Path Latency) et améliorer le LCP (Largest Contentful Paint).

## Problème identifié

La chaîne de requêtes critiques était trop longue (2 123 ms) car :
- Tous les modules JavaScript étaient chargés de manière séquentielle
- Les modules dashboard étaient chargés même sur les pages publiques
- Pas de code splitting entre public et dashboard
- Pas de lazy loading pour les modules non-critiques

## Solutions implémentées

### 1. Code splitting

Création de deux points d'entrée séparés :

- **`main-public.js`** : Modules nécessaires uniquement pour le site public
  - `lightbox.js` (seulement si galerie présente)
  - `publicCalendar.js` (seulement si calendrier présent)
  - `fileDownload.js` (seulement si lien de téléchargement présent)
  - `carousel.js` (seulement si carousel présent)

- **`main-dashboard.js`** : Tous les modules dashboard
  - Chargement parallèle avec `Promise.all()` pour réduire la latence
  - Modules dashboard uniquement

### 2. Lazy loading conditionnel

Les modules non-critiques sont chargés uniquement si nécessaires :

```javascript
// Vérifier si l'élément existe avant de charger
const galleryImages = document.querySelectorAll('.gallery-img');
if (galleryImages.length > 0) {
    const { initLightbox } = await import('./modules/gallery/lightbox.js');
    initLightbox();
}
```

### 3. Préchargement des ressources critiques

Utilisation de `<link rel="preload">` pour :
- Fonts (`outfit.ttf`)
- CSS critique (`global.min.css`)
- Modules JavaScript critiques (`constants.js`, `dom.js`, `lightbox.js`)

### 4. Différé des modules non-critiques

Utilisation de `requestIdleCallback` pour charger les modules non-critiques après le rendu initial :

```javascript
requestIdleCallback(() => {
    loadNonCriticalModules();
}, { timeout: 2000 });
```

## Résultats attendus

### Avant
- Chaîne de requêtes : 2 123 ms
- Tous les modules chargés séquentiellement
- Modules dashboard chargés sur pages publiques

### Après
- Chaîne de requêtes réduite : ~700-800 ms (estimation)
- Modules publics uniquement sur pages publiques
- Chargement parallèle dans le dashboard
- Lazy loading pour modules non-critiques

## Structure des fichiers

```
public/
├── main.js (conservé pour compatibilité, peut être supprimé)
├── main-public.js (nouveau - site public)
├── main-dashboard.js (nouveau - dashboard)
└── modules/
    ├── constants.js (préchargé)
    ├── utils/
    │   └── dom.js (préchargé)
    ├── gallery/
    │   └── lightbox.js (préchargé, chargé conditionnellement)
    └── ...
```

## Optimisations supplémentaires possibles

### 1. Bundle des modules communs

Créer un bundle pour les modules partagés (`constants.js`, `dom.js`) :
- Réduit le nombre de requêtes
- Peut être mis en cache séparément

### 2. Service Worker

Mettre en cache les modules JavaScript pour les visites suivantes :
- Réduction drastique du temps de chargement
- Offline-first pour les modules

### 3. HTTP/2 Server Push

Pousser les ressources critiques dès la requête HTML :
- Réduction de la latence réseau
- Nécessite configuration serveur

### 4. Compression Brotli

Utiliser Brotli au lieu de GZIP pour une meilleure compression :
- Réduction de la taille des fichiers JS
- Nécessite configuration serveur

## Validation

### Outils de mesure

1. **Lighthouse** : Mesure le Critical Path Latency
2. **Chrome DevTools** : Network tab avec "Critical Path"
3. **WebPageTest** : Analyse détaillée de la chaîne de requêtes

### Métriques à surveiller

- **Critical Path Latency** : < 1 000 ms (objectif)
- **LCP** : < 2.5 s (objectif)
- **FCP** : < 1.8 s (objectif)
- **TBT** : < 200 ms (objectif)

## Notes importantes

- Les modules sont chargés de manière asynchrone pour ne pas bloquer le rendu
- Le polyfill `requestIdleCallback` est inclus pour la compatibilité
- Les préchargements sont conditionnels pour éviter le gaspillage de bande passante
- Le code splitting permet une meilleure mise en cache

## Commandes utiles

```bash
# Rebuild des assets après modifications
make build-assets

# Vérifier la taille des fichiers
du -sh public/main-*.js
du -sh public/modules/**/*.js
```

