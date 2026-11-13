#!/usr/bin/env bash
# SPDX-License-Identifier: GPL-3.0-or-later
# Pose l'architecture du projet à la racine du dépôt, d'après le type de site du mode d'emploi.
#   make socle      : socle déduit du type (rule types-de-site)
#   make statique   : modèle skills/web-new-site/modeles/php-statique/ du kit
#   make dynamique  : brique gabarits/socle-php de la boîte à outils (../kouzia-boite-a-outils, clonée par make deps)
# Garde : l'architecture dépend du type, et le type n'est figé qu'à la validation 1 du client (cahier des charges).
# Refus si le mode d'emploi n'est pas validé, si le cahier des charges n'est pas validé par le client,
# ou si le socle demandé ne convient pas au type.
# Collisions traitées une par une (rule web-method) : seuls le Makefile et le README.md du modèle de dépôt
# sont remplacés (ils décrivent le modèle, pas le site) ; toute autre collision arrête tout, rien n'est copié.
set -euo pipefail

socle="${1:-}"
racine="$(git rev-parse --show-toplevel 2>/dev/null || pwd)"
cd "$racine"

mode=".claude/rules/projet.md"
refuser() { echo "Socle non posé : $*" >&2; exit 1; }
[[ -f "$mode" ]] || refuser "pas de mode d'emploi (.claude/rules/projet.md) : l'interview d'abord."
type_site="$(grep -m1 -E '^- type:' "$mode" | sed -E 's/^- type:[[:space:]]*//; s/[[:space:]]*$//')"
statut="$(grep -m1 -E '^- statut:' "$mode" | sed -E 's/^- statut:[[:space:]]*//')"
[[ "$statut" == validé* ]] || refuser "mode d'emploi en statut « ${statut:-inconnu} » : le faire valider d'abord."
registre="docs/livrables/registre.json"
cahier="$( [[ -f "$registre" ]] && node -e 'const r=require(process.argv[1]);process.stdout.write(r.livrables?.["02-cahier-des-charges"]?.statut ?? "")' "$racine/$registre" || true)"
[[ "$cahier" == "validé" ]] || refuser "cahier des charges pas encore validé par le client (validation 1, statut « ${cahier:-non généré} ») : le type de projet, donc l'architecture, peut encore changer."

# Socles permis par type (rule types-de-site) ; le premier est celui de make socle.
case "$type_site" in
    vitrine) permis=(statique) ;;
    vitrine-formulaire) permis=(statique dynamique) ;;
    associatif|boutique|espace-client) permis=(dynamique) ;;
    outil-interne) refuser "type outil-interne : le socle application (logiciel) n'existe pas encore dans le kit (à décider : Fastify, React ou PHP)." ;;
    *) refuser "type de site absent ou invalide (« ${type_site:-rien} ») dans le mode d'emploi." ;;
esac
[[ "$socle" == "auto" ]] && socle="${permis[0]}"
convient=0
for p in "${permis[@]}"; do [[ "$p" == "$socle" ]] && convient=1; done
((convient)) || refuser "le socle « $socle » ne convient pas au type « $type_site » (permis : ${permis[*]})."
echo "Type « $type_site » : socle $socle."

case "$socle" in
    statique) source_socle="$racine/.claude/skills/web-new-site/modeles/php-statique" ;;
    dynamique) source_socle="$(dirname "$racine")/kouzia-boite-a-outils/elements/gabarits/socle-php/source" ;;
    *) echo "Usage : $0 auto|statique|dynamique" >&2; exit 2 ;;
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

# Le site porte la marque du client dès sa construction (jamais la charte de Kouzia de la boîte).
if [[ -f docs/source/existant/inventaire.json || -n "$(ls -A docs/source/fournis/logo 2>/dev/null | grep -v '^\.')" ]]; then
    node .claude/skills/livrables/scripts/generer.mjs --charte || echo "Charte du client non posée : make charte une fois make deps fait." >&2
fi

echo
echo "Socle $socle posé. Suite :"
echo "  make deps     outils, outillage npm, boîte à outils"
if [[ "$socle" == statique ]]; then
    echo "  créer src/data/site.json (champs et contraintes : src/data/schemas/site.schema.json)"
    echo "  make dev      puis make verify"
else
    echo "  régler config/app.php (NOTES.md de gabarits/socle-php, « Intégration pas à pas »)"
    echo "  lier public/assets/css/charte.css dans templates/layout.php (charte du client, make charte)"
    echo "  make dev      puis make verify"
fi
