# SPDX-License-Identifier: GPL-3.0-or-later
# Modèle de dépôt d'un site client : avant que le socle soit posé.
# Ce Makefile est remplacé par celui du socle (make statique ou make dynamique), qui reprend deps et outils.
KIT := .claude/skills

.DEFAULT_GOAL := help
.PHONY: help deps outils socle statique dynamique

-include .claude/skills/livrables/livrables.mk

help: ## Affiche cette aide
	@awk 'BEGIN {FS = ":.*##"; printf "\nCibles disponibles :\n\n"} /^[a-zA-Z0-9_.-]+:.*##/ { printf "  \033[36m%-10s\033[0m %s\n", $$1, $$2 }' $(MAKEFILE_LIST)
	@echo

deps: ## Installe tout en une commande : paquets système, boîte à outils (clonée à côté si absente), navigateur
	bash $(KIT)/web-audit/scripts/check-outils.sh --installer

outils: ## État des outils et de la boîte à outils, sans rien installer
	bash $(KIT)/web-audit/scripts/check-outils.sh

socle: ## Pose l'architecture déduite du type de site (après la validation 1 du client)
	bash $(KIT)/web-new-site/demarrer.sh auto

statique: ## Pose le socle statique PHP (vitrine, vitrine-formulaire), même garde que make socle
	bash $(KIT)/web-new-site/demarrer.sh statique

dynamique: ## Pose le socle dynamique PHP (gabarits/socle-php de la boîte), même garde que make socle
	bash $(KIT)/web-new-site/demarrer.sh dynamique
