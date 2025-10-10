# Agent Guidelines for SSA Website

## Build/Lint/Test Commands
- **Build**: `make setup-dev` (install deps in container) or `composer install`
- **Lint**: `vendor/bin/php-cs-fixer fix` (PSR-12 with custom rules)
- **Static Analysis**: `make phpstan` or `vendor/bin/phpstan analyse app src --level=6`
- **Tests**: `make test` or `vendor/bin/phpunit --testdox`
- **Single Test**: `vendor/bin/phpunit --filter TestName`
- **QA Suite**: `composer qa` (dump, cs, stan, test)

## Code Style Guidelines
- **PHP Version**: >=8.2 with strict types (`declare(strict_types=1)`)
- **Formatting**: PSR-12 with single quotes, single space around binary operators
- **Imports**: Group by vendor (Capsule\, App\), remove unused imports
- **Naming**: Classes in PascalCase, methods/vars in camelCase
- **Types**: Use type hints, return types, and PHPDoc annotations
- **Error Handling**: Use exceptions, avoid silent failures
- **Architecture**: Final classes, dependency injection, attribute routing
- **Testing**: PHPUnit with testdox, covers annotations

## Project Structure
- `src/`: Framework code (Capsule namespace)
- `app/`: Application code (App namespace) 
- `templates/`: View templates (.tpl.php)
- `tests/`: Test files with strict typing

## Architecture et Logiques Métier

### Framework Capsule
- **Kernel HTTP** : Pipeline middleware LIFO avec orchestration pure
- **Container DI** : Singleton avec factories pour injection de dépendances
- **Routage** : Système d'attributs avec découverte automatique des contrôleurs

### Système de Routage
```php
#[RoutePrefix('/dashboard')]
#[Route(path: '/users', methods: ['GET'])]
```
- **Middleware Pipeline** : ErrorBoundary → DebugHeaders → SecurityHeaders → AuthRequiredMiddleware
- **Découverte automatique** via `RouteScanner` dans `bootstrap/app.php`

### Authentification et Sécurité
- **Session PHP** sécurisée avec `session_regenerate_id()` et SameSite Strict
- **Rôles** : `admin` et `employee` (vérification via `CurrentUserProvider`)
- **CSRF** : Token unique par session, validation automatique sur POST
- **Échappement HTML** : Classe `Html` avec méthodes spécialisées par contexte

### Gestion des Données
- **Repositories** : `BaseRepository` avec CRUD générique, `ArticleRepository`, `UserRepository`
- **Services** : `ArticleService` (validation/sanitization), `UserService`, `PasswordService`
- **DTOs** : `ArticleDTO`, `UserDTO` - objets immutables pour transfert de données

### Système de Vues
- **Moteur** : `MiniMustache` avec syntaxe Mustache-like et partials dynamiques
- **Layout** : `layout.tpl.php` avec header/footer conditionnels
- **Composants** : `components/` pour éléments réutilisables, `dashboard/` pour admin

### Traduction Multilingue
- **Détection** : Session/URL avec fallback français
- **Chargement** : `TranslationLoader` charge toutes les traductions nécessaires
- **Fichiers** : `app/Lang/locales/{lang}/index.php`

### Logiques Métier Principales

#### Gestion des Événements (Articles)
- **CRUD complet** avec validation des dates/heures (YYYY-MM-DD, HH:MM:SS)
- **Validation** : Champs requis (`titre`, `resume`, `description`, `date_article`, `hours`)
- **Workflow PRG** : Post-Redirect-Get avec flash messages et état de formulaire
- **Filtrage** : Par auteur, date (événements à venir)

#### Gestion des Utilisateurs
- **Création** : Vérification unicité username/email, hash password
- **Rôles** : `admin` (accès dashboard), `employee` (utilisateur standard)
- **Changement mot de passe** : Validation ancien mot de passe, confirmation

#### Dashboard Admin
- **Protection** : Middleware `AuthRequiredMiddleware` pour routes `/dashboard/*`
- **Composants** : Gestion utilisateurs, articles, agenda, compte personnel
- **Navigation** : `SidebarLinksProvider` avec liens conditionnels par rôle

### Configuration et Infrastructure
- **Docker** : Services web (Apache/PHP), db (MariaDB), pma (phpMyAdmin)
- **Base de données** : Tables `users`, `articles`, `contacts` avec relations
- **Sécurité** : Headers CSP, HSTS, session sécurisée

### Patterns Importants
- **Dependency Injection** : Toutes les dépendances via container
- **Final Classes** : Classes marquées `final` sauf extension explicite
- **Type Hints** : Utilisation intensive des types PHP 8.2+
- **Error Boundaries** : Gestion centralisée des exceptions
- **CSRF Protection** : Obligatoire sur toutes les actions POST
- **HTML Escaping** : Toujours échapper les données utilisateur

### Fichiers de Configuration Clés
- `config/container.php` : Configuration DI complète
- `bootstrap/app.php` : Initialisation application + routage
- `public/index.php` : Point d'entrée avec middleware pipeline