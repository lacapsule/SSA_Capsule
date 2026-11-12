# SPDX-License-Identifier: GPL-3.0-or-later
# Raccourcis de développement du site (détails : setup-local.sh, .claude/rules/projet.md)
LOCAL ?= bash setup-local.sh
PORT  ?= 8000
PREVIEW_PORT ?= 8001
HOST  ?= localhost
# Domaine de production du projet (canonical, sitemap, audit)
SITE  ?= https://example.org
AUDIT := .claude/skills/web-audit/scripts

-include .claude/skills/livrables/livrables.mk

.DEFAULT_GOAL := help
.PHONY: help info deps outils dev open build preview lint check verify repo a11y images clean

# ---------- Aide ----------
help: ## Affiche cette aide
	@awk 'BEGIN {FS = ":.*##"; printf "\nCibles disponibles :\n\n"} /^[a-zA-Z0-9_.-]+:.*##/ { printf "  \033[36m%-10s\033[0m %s\n", $$1, $$2 }' $(MAKEFILE_LIST)
	@echo

# ---------- Mise en place ----------
info:    ## État local : PHP, extensions, dist/
	@$(LOCAL) info

deps:    ## Installe toutes les dépendances en une commande : paquets système, boîte à outils, outillage npm verrouillé, navigateur
	bash $(AUDIT)/check-outils.sh --installer

outils:  ## État des outils (rien n'est installé : make deps)
	bash $(AUDIT)/check-outils.sh

# ---------- Développement ----------
dev:     ## Serveur de dev, pages rendues à la volée (Ctrl+C pour arrêter)
	PORT=$(PORT) HOST=$(HOST) $(LOCAL) dev

open:    ## Ouvre le site de dev dans le navigateur
	xdg-open http://$(HOST):$(PORT)

build:   ## Génère le site statique dans dist/
	$(LOCAL) build

preview: build ## Construit puis sert dist/ comme l'hébergeur
	PREVIEW_PORT=$(PREVIEW_PORT) HOST=$(HOST) $(LOCAL) preview

# ---------- Qualité ----------
lint:    ## Syntaxe de tous les fichiers PHP
	@for f in $$(find bin src -name '*.php'); do php -l "$$f" >/dev/null || exit 1; done; echo "PHP : syntaxe OK"

check: build ## Build + audit de dist/ (SEO, liens, titres, CSS, poids) + ordre des propriétés CSS
	node $(AUDIT)/check-dist.mjs dist $(SITE)
	node $(AUDIT)/ordre-css.mjs --check src/styles

repo:    ## Aucun secret ni fichier sensible suivi par Git
	node $(AUDIT)/check-repo.mjs

verify: lint check repo ## Tout ce qui doit être vert avant de livrer
	@mkdir -p .claude/etat && date -Iseconds > .claude/etat/verify-ok

a11y: build ## Scan axe-core de chaque page du sitemap et de la 404 (serveur de prévisualisation lancé puis arrêté)
	@php -S $(HOST):$(PREVIEW_PORT) -t dist bin/preview.php >/dev/null 2>&1 & pid=$$!; \
	for i in 1 2 3 4 5 6 7 8 9 10; do curl -s -o /dev/null http://$(HOST):$(PREVIEW_PORT)/ && break; sleep 0.3; done; \
	node .claude/skills/web-a11y-audit/check-a11y.mjs http://$(HOST):$(PREVIEW_PORT); code=$$?; kill $$pid; exit $$code

images:  ## Image de partage (src/partage/og.html) et icônes (src/partage/logo.svg) vers public/
	node .claude/skills/web-media/scripts/images-partage.mjs \
	  $$([ -f src/partage/og.html ] && echo --og src/partage/og.html) \
	  $$([ -f src/partage/logo.svg ] && echo --logo src/partage/logo.svg)

clean:   ## Supprime dist/
	rm -rf dist
