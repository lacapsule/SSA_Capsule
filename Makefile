# Développement local, sous Linux, macOS ou Windows (GNU make et PHP 8.2+ dans le PATH).
#   make dev        site sur http://127.0.0.1:8080 (crée la base de dev au premier lancement)
#   make db         recrée la base de dev (data/dev.sqlite)
#   make migrate    applique les migrations sur la base du .env (production, préproduction)
#   make open       ouvre l'adresse locale dans le navigateur
#   make info       vérifie PHP et ses extensions
#   make lint       vérifie la syntaxe de tous les fichiers PHP
#   make deps       installe toutes les dépendances (kit .claude : paquets, extensions PHP, boîte à outils ; bash requis)
#   make outils     état des outils, sans rien installer
#   make verify     tout ce qui doit être vert avant de livrer (lint, tests s'ils existent, dépôt)
# Options : make dev PORT=8090 HOST=0.0.0.0 (HOST=0.0.0.0 pour tester depuis un téléphone du réseau local).

PHP ?= php
HOST ?= 127.0.0.1
PORT ?= 8080
DEV := $(PHP) scripts/dev.php

ifeq ($(OS),Windows_NT)
# cmd.exe forcé, même si un sh (Git Bash) est dans le PATH.
SHELL := cmd.exe
OPEN := start ""
else ifeq ($(shell uname -s),Darwin)
OPEN := open
else
OPEN := xdg-open
endif

.DEFAULT_GOAL := help
.PHONY: help dev db migrate open info lint deps outils verify

help: ## Affiche cette aide
	@$(PHP) -r "foreach (file('Makefile') as $$l) if (preg_match('/^([a-z-]+):.*## (.*)$$/', $$l, $$m)) printf(\"  %%-10s %%s\n\", $$m[1], $$m[2]);"

dev: ## Lance le site en local (base de dev créée si absente)
	$(DEV) serve --host=$(HOST) --port=$(PORT)

db: ## Recrée la base de dev
	$(DEV) db

migrate: ## Applique les migrations sur la base du .env
	$(PHP) scripts/migrate.php

open: ## Ouvre http://HOST:PORT dans le navigateur
	$(OPEN) http://$(HOST):$(PORT)/

info: ## Vérifie PHP et les extensions nécessaires
	$(DEV) info

lint: ## Vérifie la syntaxe des fichiers PHP
	$(DEV) lint

KIT := .claude/skills/web-audit/scripts

# Livrables du client (audit, cahier des charges…) : fragment commun du kit, bash requis.
-include .claude/skills/livrables/livrables.mk

deps: ## Installe toutes les dépendances en une commande (paquets, extensions PHP, boîte à outils)
	bash $(KIT)/check-outils.sh --installer

outils: ## État des outils, sans rien installer
	bash $(KIT)/check-outils.sh

verify: lint ## Lint, tests (tests/run.php s'il existe), aucun secret suivi ; marque le contrôle vert pour le kit
	$(PHP) -r "if (is_file('tests/run.php')) { passthru(PHP_BINARY . ' tests/run.php', $$c); exit($$c); }"
	node $(KIT)/check-repo.mjs
	$(PHP) -r "is_dir('.claude/etat') || mkdir('.claude/etat', 0775, true); file_put_contents('.claude/etat/verify-ok', date('c') . PHP_EOL);"
