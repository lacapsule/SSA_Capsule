#!/usr/bin/env bash
# SPDX-License-Identifier: GPL-3.0-or-later
# Squelette de conception d'un nouveau projet (make init), avant l'audit et avant le socle :
#   docs/source/fournis/{logo,charte,photos,textes,documents}  fichiers remis par le client, tels quels
#   docs/maquettes/        maquettes en texte, une par page (skill web-contenu)
#   docs/rapports/         rapports hebdomadaires (skill rapport-hebdo)
#   .claude/rules/projet.md  mode d'emploi en brouillon, depuis le modèle (champ depot rempli)
# Ne remplace jamais un fichier existant : relancer est sans risque. Le site lui-même (src/ ou app/) vient
# ensuite, par make statique ou make dynamique, une fois le socle choisi à l'interview.
set -euo pipefail

racine="$(git rev-parse --show-toplevel 2>/dev/null || pwd)"
cd "$racine"
depot="$(basename "$racine")"

if [[ "$depot" == "kouzia-modele-site" ]]; then
    echo "Ici, c'est le modèle de dépôt : le copier ou le cloner sous le nom du site (ex. boulangerie-martin), puis make init dans la copie." >&2
    exit 1
fi

cree=()
# Un dépôt client cloné depuis le modèle garde son adresse : un push enverrait le site du client dans le modèle.
origine="$(git remote get-url origin 2>/dev/null || true)"
if [[ "$origine" == *kouzia-modele-site* ]]; then
    git remote remove origin
    cree+=("adresse « origin » du modèle retirée ($origine) : ajouter celle du dépôt du client (git remote add origin …)")
fi
for d in docs/source/fournis/logo docs/source/fournis/charte docs/source/fournis/photos docs/source/fournis/textes docs/source/fournis/documents docs/maquettes docs/rapports; do
    if [[ ! -d "$d" ]]; then
        mkdir -p "$d"
        cree+=("$d/")
    fi
    # Un dossier vide n'est pas suivi par Git : un fichier témoin le garde dans le dépôt.
    [[ -n "$(ls -A "$d")" ]] || touch "$d/.gitkeep"
done

mode=".claude/rules/projet.md"
if [[ ! -f "$mode" ]]; then
    aujourdhui="$(date +%F)"
    sed -e "s/^# Mode d'emploi : <nom du projet>/# Mode d'emploi : $depot/" \
        -e "s/^- depot: .*/- depot: $depot/" \
        -e "s/^- statut: .*/- statut: brouillon/" \
        -e "s/^- mise à jour: .*/- mise à jour: $aujourdhui/" \
        .claude/skills/interview/modele-mode-emploi.md > "$mode"
    cree+=("$mode (brouillon, à remplir par l'interview)")
fi

if ((${#cree[@]})); then
    echo "Créé :"
    printf '  %s\n' "${cree[@]}"
else
    echo "Rien à créer : le squelette existe déjà."
fi
cat <<'EOF'

Suite :
  ranger les fichiers du client dans docs/source/fournis/ (logo, charte, photos, textes, documents)
  make audit URL=https://site-actuel     si le client a déjà un site
  interview (Claude), puis make livrables
  make statique ou make dynamique        une fois le socle choisi
EOF
