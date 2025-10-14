# --- Config par défaut (surchargable: `make up DC="docker compose -p myproj"`)
DC ?= docker compose
PHP ?= $(DC) exec -T web php
COMPOSER ?= $(DC) exec -T web composer

# Script local helper
LOCAL ?= bash ./setup-local.sh
PORT ?= 8080

.PHONY: up down restart build pull logs ps \
        setup setup-docker setup-dev install vendor-clean dump \
        phpstan test \
        pma pma-stop open-pma open-web open-doc \
        db-purge bash-db bash-web init \
        help \
        local-info local-deps local-init local-reset local-dev local-db-shell local-bin local-open local-health

# ---------- Aide ----------
help: ## Affiche cette aide
	@awk 'BEGIN {FS = ":.*##"; printf "\nTargets disponibles:\n\n"} /^[a-zA-Z0-9_.-]+:.*##/ { printf "  \033[36m%-22s\033[0m %s\n", $$1, $$2 }' $(MAKEFILE_LIST)
	@echo

# ---------- Infra (Docker) ----------
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

bash-db:   ## Shell dans le conteneur DB
	$(DC) exec db bash

bash-web:  ## Shell dans le conteneur web
	$(DC) exec web bash

# ---------- Bootstrap projet (host) ----------
init:      ## One-shot: installe les outils dev (host)
	composer require --dev phpstan/phpstan phpunit/phpunit
	@echo "✔ Tools added to composer.json (dev). Next runs will use 'install' only."

setup:     ## Installe les deps à partir du lock (host)
	composer install --no-interaction --prefer-dist --optimize-autoloader

setup-docker: ## Installe Docker (script maison)
	@echo "✔ Initialisation d'installation Docker pour Linux Mint..."
	bash scripts/setup-docker.sh

# Variante inside container (si Composer/PHP sont dans le conteneur 'web')
setup-dev: ## Installe deps dev dans le conteneur
	$(COMPOSER) install --no-interaction --prefer-dist

vendor-clean: ## Réinstalle vendor proprement (host)
	rm -rf vendor composer.lock
	composer install

dump: ## (Re)génère l'autoload Composer
	composer dump-autoload

# ---------- Qualité / Tests ----------
phpstan:   ## Analyse statique
	vendor/bin/phpstan analyse app src --level=6

test:      ## Tests unitaires
	vendor/bin/phpunit --testdox

# ---------- Outils pratiques (Docker) ----------
pma:       ## Démarre phpMyAdmin
	$(DC) up -d pma

pma-stop:  ## Stoppe phpMyAdmin
	$(DC) stop pma

open-pma:  ## Ouvre phpMyAdmin
	xdg-open http://localhost:8081

open-web:  ## Ouvre le site (Docker)
	xdg-open http://localhost:8080

open-doc:  ## Ouvre la doc
	xdg-open doc.md

info:      ## Infos PHP & SQLite (host)
	@php -v
	@sqlite3 --version || true

# =====================================================================
#                       Local (sans Docker)
# =====================================================================

local-info:  ## Etat local (versions + chemins)
	$(LOCAL) info

local-deps:  ## Installe extensions PHP nécessaires (dnf/apt)
	$(LOCAL) deps

local-init:  ## Crée data/, applique migration SQLite si absente, génère bin/ si manquants
	bash $(LOCAL) init

local-reset: ## Réinitialise la DB SQLite (ATTENTION: destructive)
	$(LOCAL) reset

local-dev:   ## Lance serveur PHP intégré (Ctrl+C pour arrêter)
	PORT=$(PORT) $(LOCAL) dev

local-db-shell: ## Ouvre un shell sqlite
	$(LOCAL) db.shell

local-bin:   ## (Re)génère bin/dev et bin/db si absents
	$(LOCAL) bin.install

local-open:  ## Ouvre http://localhost:PORT en local
	xdg-open http://localhost:$(PORT)

# (Optionnel) Health-check rapide (ex: SELECT 1 plus tard)
local-health: ## Placeholder health-check (à compléter si besoin)
	@echo "OK: placeholder"
