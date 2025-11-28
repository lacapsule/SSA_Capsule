# Accessibilité et SEO

Ce document décrit les optimisations d'accessibilité et de SEO implémentées dans le projet, conformes aux normes WCAG et aux recommandations de Google.

## Accessibilité (WCAG)

### Attributs ARIA

- **Navigation** : `role="navigation"`, `aria-label` pour les menus
- **Régions** : `role="banner"`, `role="main"`, `role="contentinfo"`
- **Formulaires** : `aria-required`, `aria-invalid`, `aria-live` pour les erreurs
- **Carrousels** : `aria-roledescription`, `aria-label` pour les contrôles
- **Pagination** : `aria-current="page"` pour la page active

### Sémantique HTML

- Utilisation de balises sémantiques : `<header>`, `<nav>`, `<main>`, `<article>`, `<section>`, `<address>`
- Hiérarchie des titres respectée (`<h1>` → `<h2>` → `<h3>`)
- Utilisation de `<time>` avec attribut `datetime` pour les dates
- Utilisation de `<dl>`, `<dt>`, `<dd>` pour les listes de définitions

### Images

- Tous les attributs `alt` sont présents et descriptifs
- Images décoratives avec `alt=""` ou `role="presentation"`
- Images importantes avec `fetchpriority="high"`
- Images non critiques avec `loading="lazy"`

### Navigation clavier

- Tous les éléments interactifs sont accessibles au clavier
- Ordre de tabulation logique
- Focus visible sur les éléments interactifs
- Honeypot fields avec `tabindex="-1"` pour éviter la navigation clavier

### Formulaires

- Labels associés aux champs (visuellement ou via `aria-label`)
- Messages d'erreur avec `role="alert"` et `aria-live="polite"`
- Champs requis avec `aria-required="true"`
- États de validation avec `aria-invalid`

## SEO - Données structurées (Schema.org)

### Types de données structurées implémentés

1. **Organization** (`https://schema.org/Organization`)
   - Nom, URL, logo
   - Point de contact (email, téléphone)
   - Langues supportées

2. **WebSite** (`https://schema.org/WebSite`)
   - Nom, URL
   - Action de recherche

3. **Article** (`https://schema.org/Article`)
   - Titre, description, image
   - Date de publication, auteur
   - Éditeur (Organization)

4. **Event** (`https://schema.org/Event`)
   - Nom, description
   - Dates de début et fin
   - Lieu, organisateur

5. **BreadcrumbList** (`https://schema.org/BreadcrumbList`)
   - Navigation hiérarchique (à implémenter si nécessaire)

### Format JSON-LD

Toutes les données structurées sont injectées dans le `<head>` au format JSON-LD :

```html
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Organization",
  ...
}
</script>
```

## Meta tags SEO

### Open Graph

- `og:title` : Titre de la page
- `og:description` : Description
- `og:image` : Image de partage
- `og:url` : URL canonique
- `og:type` : Type de contenu (website, article)
- `og:site_name` : Nom du site
- `og:locale` : Locale (fr_FR, br_FR)

### Twitter Cards

- `twitter:card` : Type de carte (summary, summary_large_image)
- `twitter:title` : Titre
- `twitter:description` : Description
- `twitter:image` : Image

## Implémentation

### Service de données structurées

Le service `App\Support\StructuredDataService` génère les données structurées :

```php
use App\Support\StructuredDataService;

// Organisation
$org = StructuredDataService::organization([
    'type' => 'customer service',
    'email' => 'contact@example.com',
]);

// Article
$article = StructuredDataService::article([
    'title' => 'Titre',
    'description' => 'Description',
    'url' => 'https://example.com/article/1',
    // ...
]);

// Conversion en JSON-LD
$jsonLd = StructuredDataService::toJsonLd([$org, $article]);
```

### Injection dans les templates

Les données structurées sont injectées via le partial `structured-data.tpl.php` :

```mustache
{{> partial:structured-data }}
```

### Utilisation dans les contrôleurs

```php
$structuredData = [
    StructuredDataService::organization(),
    StructuredDataService::article($articleData),
];

return $this->page('index', [
    'structuredData' => StructuredDataService::toJsonLd($structuredData),
    // ... autres données
]);
```

## Pages avec données structurées

- **Page d'accueil** : Organization, WebSite
- **Articles** : Organization, Article
- **Événements** : Organization, Event (à implémenter dans le calendrier)

## Validation

### Outils de validation

- **Données structurées** : [Google Rich Results Test](https://search.google.com/test/rich-results)
- **Accessibilité** : [WAVE](https://wave.webaim.org/), [axe DevTools](https://www.deque.com/axe/devtools/)
- **SEO** : [Google Search Console](https://search.google.com/search-console)

### Checklist

- [x] Attributs ARIA sur les éléments interactifs
- [x] Sémantique HTML correcte
- [x] Images avec alt descriptifs
- [x] Navigation clavier fonctionnelle
- [x] Formulaires accessibles
- [x] Données structurées JSON-LD
- [x] Meta tags Open Graph
- [x] Meta tags Twitter Cards
- [ ] Breadcrumb avec données structurées (si nécessaire)
- [ ] Validation avec outils externes

## Améliorations futures

1. **Breadcrumb** : Ajouter un breadcrumb avec données structurées sur les pages profondes
2. **FAQ** : Ajouter le schema FAQPage si une section FAQ existe
3. **LocalBusiness** : Si l'organisation a une adresse physique, utiliser LocalBusiness
4. **VideoObject** : Pour les vidéos dans les articles
5. **Accessibilité** : Tests automatisés avec axe-core

