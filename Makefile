# --- Config par défaut (surchargable: `make up DC="docker compose -p myproj"`)
DC ?= docker compose
PHP ?= $(DC) exec -T web php
COMPOSER ?= $(DC) exec -T web composer

.PHONY: up down restart build pull logs ps \
        setup setup-docker setup-dev install vendor-clean dump\
        phpstan test \
        pma pma-stop open-pma open-web open-doc \
        db-purge bash-db bash-web init

# ---------- Infra ----------
up:        ## Démarre l'infra (idempotent)
	$(DC) up -d

down:      ## Stoppe l'infra
	$(DC) down

restart:   ## Redémarre l'infra
	$(DC) down
	$(DC) up -d

build:     ## (Re)build les images
	$(DC) build

pull:      ## Pull les images
	$(DC) pull

logs:      ## Logs suivis
	$(DC) logs -f

ps:        ## Etat des conteneurs
	$(DC) ps

db-purge:  ## Stop + purge volumes (ATTENTION: destructive)
	$(DC) down -v

bash-db:
	$(DC) exec db bash

bash-web:
	$(DC) exec web bash

# ---------- Bootstrap projet (one-shot) ----------
init:      ## One-shot: installe les outils dev et prépare le projet
	# Installe les outils DEV (une seule fois au démarrage d'un poste)
	composer require --dev phpstan/phpstan phpunit/phpunit 
	@echo "✔ Tools added to composer.json (dev). Next runs will use 'install' only."

setup:     ## Installe les deps à partir du lock (reproductible)
	composer install --no-interaction --prefer-dist --optimize-autoloader

setup-docker:
	@echo "✔ Initialisation d'installation Docker pour Linux Mint..."
	bash scripts/setup-docker.sh

# Variante inside container (si Composer/PHP sont dans le conteneur 'web')
setup-dev: ## Installe deps dev dans le conteneur
	$(COMPOSER) install --no-interaction --prefer-dist

vendor-clean: ## Réinstalle vendor proprement (host)
	rm -rf vendor composer.lock
	composer install

dump:
	composer dump-autoload
# ---------- Qualité / Tests ----------
phpstan:   ## Analyse statique
	vendor/bin/phpstan analyse app src --level=6

test:      ## Tests unitaires
	vendor/bin/phpunit --testdox

# ---------- Outils pratiques ----------
pma:
	$(DC) up -d pma

pma-stop:
	$(DC) stop pma

open-pma:
	xdg-open http://localhost:8081

open-web:
	xdg-open http://localhost:8080

open-doc:
	xdg-open doc.md
