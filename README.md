# 🧱 CapsulePHP

**Boilerplate PHP MVC léger et moderne (sans dépendance externe) - Dev Purpose**

> Pour sites statiques, associatifs ou petits back-offices sécurisés

---

## 🎯 Objectif

Fournir une base claire, maintenable et extensible pour :

* Créer un site vitrine dynamique ou un back-office léger
* Utiliser SQLite ou MySQL sans ORM ni dépendance externe
* Appliquer le modèle **MVC minimal**
* Gérer les **routes**, les **vues**, et l’**authentification** proprement
* Offrir une architecture modulaire **type framework**

---

## 🗂 Structure

```
src/                        # Composants internes (framework)
├── Framework/              # BaseController, Router, Kernel…
├── Http/                   # Middleware, Headers…
├── Security/               # Authenticator, PasswordHasher
├── Database/               # Connexion PDO / gestion SQLite
├── Lang/                   # Traductions multilingues

app/                        # Application métier
└── Controller/             # Contrôleurs liés aux pages

templates/                  # Vues HTML (layout + pages)
public/                     # Point d’entrée (index.php, login.php, etc.)
config/                     # Configuration des routes, DB, constantes
```

---

## 🚀 Lancer le projet

### 1. Cloner le dépôt

```bash
git clone https://github.com/tonuser/capsulephp.git
cd capsulephp
```

### 2. Démarrer un serveur local

```bash
php -S localhost:8000 -t public/
```

Ou via Apache :

```bash
bash bin/boot_apache.sh
```

---

## 🔐 Authentification

* 🔑 Système simple basé sur `Authenticator::login()` (PHP pur)
* 🔒 Middleware `AuthMiddleware::handle()` à inclure dans toute route protégée
* ✅ Gestion de session, mot de passe hashé, redirections

---

## 🛠 Exemple de route

```php
// config/routes.php

use App\Controller\MainController;

return [
    '/' => [ MainController::class, 'home' ],
    '/contact' => [ MainController::class, 'contact' ],
];
```

---

## 📄 Exemple de contrôleur

```php
class MainController extends AbstractController
{
    public function home(): void
    {
        echo $this->renderView('pages/home.php', ['title' => 'Accueil']);
    }
}
```

---

## 📦 Avantages

* 🧱 100% PHP natif, aucun framework externe
* 🔍 Code clair et lisible pour apprentissage ou projet associatif
* 🧩 Architecture extensible par dossiers (`lib/`)
* 📦 Support multilingue, sécurité headers, layouts, middleware, SQLite

---

## 📌 À venir

* [ ] Middleware CSRF
* [ ] Système de flash messages

# PHPCapsule
