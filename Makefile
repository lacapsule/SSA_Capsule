DC ?= docker compose
PHP ?= $(DC) exec -T web php
COMPOSER ?= $(DC) exec -T web composer

# Script local helper
LOCAL ?= bash setup-local.sh
PORT ?= 8080

.PHONY: \
  help \
  version info deps init reset dev db-shell bin open open-pma doc health \
  up down restart build pull logs \
  d-init d-setup setup-docker setup-dev vendor-clean dump \
  phpstan test \
  pma pma-stop

# ---------- Aide ----------
help: ## Affiche cette aide
	@awk 'BEGIN {FS = ":.*##"; printf "\nTargets disponibles:\n\n"} /^[a-zA-Z0-9_.-]+:.*##/ { printf "  \033[36m%-22s\033[0m %s\n", $$1, $$2 }' $(MAKEFILE_LIST)
	@echo

# =====================================================================
#                       Local (sans Docker)
# =====================================================================

version:      ## Infos PHP & SQLite (host)
	@php -v
	@sqlite3 --version || true

info:         ## Etat local (versions + chemins)
	$(LOCAL) info

deps:         ## Installe extensions PHP nécessaires (dnf/apt)
	$(LOCAL) deps

init:         ## Crée data/, applique migration SQLite si absente, génère bin/ si manquants
	$(LOCAL) init

reset:        ## Réinitialise la DB SQLite (ATTENTION: destructive)
	$(LOCAL) reset

dev:          ## Lance serveur PHP intégré (Ctrl+C pour arrêter)
	PORT=$(PORT) $(LOCAL) dev

db-shell:     ## Ouvre un shell sqlite
	$(LOCAL) db.shell

bin:          ## (Re)génère bin/dev et bin/db si absents
	$(LOCAL) bin.install

open:         ## Ouvre http://localhost:PORT en local
	xdg-open http://localhost:$(PORT)

pma:     ## Ouvre phpMyAdmin (navigateur)
	xdg-open http://localhost:8081

doc:          ## Ouvre la doc
	xdg-open doc.md

dump:         ## (Re)génère l'autoload Composer
	composer dump-autoload
