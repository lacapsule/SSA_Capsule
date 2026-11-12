# SPDX-License-Identifier: GPL-3.0-or-later
# Cibles des livrables, communes au modèle de dépôt et aux deux socles (inclus par chaque Makefile).
LIVRABLES := .claude/skills/livrables/scripts
.PHONY: audit livrables valider

audit: ## Audit du site actuel du client : make audit URL=https://site-actuel [MAX=40]
	@if [ -z "$(URL)" ]; then echo "Usage : make audit URL=https://site-actuel [MAX=40]"; exit 2; fi
	node $(LIVRABLES)/audit-existant.mjs $(URL) --max $(or $(MAX),40)
	node $(LIVRABLES)/generer.mjs 01-audit-existant

livrables: ## (Re)génère les documents du projet en HTML et PDF dans docs/livrables/ (charte Kouzia)
	node $(LIVRABLES)/generer.mjs

valider: ## Enregistre une validation du client : make valider ID=02-cahier-des-charges PAR="nom, moyen, date"
	@if [ -z "$(ID)" ] || [ -z "$(PAR)" ]; then echo 'Usage : make valider ID=<livrable> PAR="qui, comment"'; exit 2; fi
	node $(LIVRABLES)/generer.mjs --valider $(ID) "$(PAR)"
