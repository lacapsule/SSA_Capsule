#!/usr/bin/env bash
# SPDX-License-Identifier: GPL-3.0-or-later
# Pose le socle choisi à l'interview à la racine du dépôt (make statique ou make dynamique, Makefile du modèle).
#   statique  : modèle skills/web-new-site/modeles/php-statique/ du kit
#   dynamique : brique gabarits/socle-php de la boîte à outils (../kouzia-boite-a-outils, clonée par make deps)
# Collisions traitées une par une (rule web-method) : seuls le Makefile et le README.md du modèle de dépôt
# sont remplacés (ils décrivent le modèle, pas le site) ; toute autre collision arrête tout, rien n'est copié.
set -euo pipefail

socle="${1:-}"
racine="$(git rev-parse --show-toplevel 2>/dev/null || pwd)"
cd "$racine"

case "$socle" in
    statique) source_socle="$racine/.claude/skills/web-new-site/modeles/php-statique" ;;
    dynamique) source_socle="$(dirname "$racine")/kouzia-boite-a-outils/elements/gabarits/socle-php/source" ;;
    *) echo "Usage : $0 statique|dynamique" >&2; exit 2 ;;
esac

if [[ ! -d "$source_socle" ]]; then
    echo "Source du socle introuvable : $source_socle" >&2
    [[ "$socle" == dynamique ]] && echo "Boîte à outils absente : lancer make deps (elle est clonée à côté du dépôt)." >&2
    exit 1
fi
for deja in src app public; do
    if [[ -e "$deja" ]]; then
        echo "Un socle est déjà posé ($deja/ existe) : rien n'est copié." >&2
        exit 1
    fi
done

# .gitignore : fusionné (les lignes du socle absentes de celui du dépôt sont ajoutées), jamais écrasé.
remplacables=(Makefile README.md .gitignore)
collisions=()
while IFS= read -r -d '' f; do
    relatif="${f#"$source_socle"/}"
    [[ -e "$relatif" ]] || continue
    permis=0
    for r in "${remplacables[@]}"; do [[ "$relatif" == "$r" ]] && permis=1; done
    ((permis)) || collisions+=("$relatif")
done < <(find "$source_socle" -type f -not -path '*/node_modules/*' -print0)

if ((${#collisions[@]})); then
    echo "Fichiers déjà présents, à traiter à la main avant de poser le socle :" >&2
    printf '  %s\n' "${collisions[@]}" >&2
    exit 1
fi

gitignore_depot=""
[[ -f .gitignore ]] && gitignore_depot="$(cat .gitignore)"
for r in Makefile README.md; do
    [[ -e "$r" ]] && echo "remplacé : $r (décrivait le modèle de dépôt)"
done
# Copie fichier par fichier, sans node_modules éventuel de la source (l'outillage npm vit à la racine du dépôt).
(cd "$source_socle" && find . -name node_modules -prune -o \( -type f -o -type l \) -print0 | xargs -0 cp --parents -t "$racine")
if [[ -n "$gitignore_depot" && -f "$source_socle/.gitignore" ]]; then
    { printf '%s\n' "$gitignore_depot"; grep -vxF -f <(printf '%s\n' "$gitignore_depot") "$source_socle/.gitignore" || true; } > .gitignore
    echo "fusionné : .gitignore (lignes du socle ajoutées à celles du dépôt)"
fi

if [[ "$socle" == dynamique && ! -f .env && -f .env.example ]]; then
    cp .env.example .env
    echo "créé : .env depuis .env.example (APP_ENV=local, jamais versionné)"
fi

echo
echo "Socle $socle posé. Suite :"
echo "  make deps     outils, outillage npm, boîte à outils"
if [[ "$socle" == statique ]]; then
    echo "  créer src/data/site.json (champs et contraintes : src/data/schemas/site.schema.json)"
    echo "  make dev      puis make verify"
else
    echo "  régler config/app.php (NOTES.md de gabarits/socle-php, « Intégration pas à pas »)"
    echo "  make dev      puis make verify"
fi
