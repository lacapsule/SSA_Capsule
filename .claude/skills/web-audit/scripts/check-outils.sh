#!/usr/bin/env bash
# SPDX-License-Identifier: GPL-3.0-or-later
# Outils dont le kit a besoin pour travailler sans aide. Une seule liste, deux usages :
#   bash check-outils.sh               état : présent ou absent (code 1 si un outil obligatoire manque)
#   bash check-outils.sh --installer   installe tout ce qui manque en une fois (sudo demandé une fois),
#                                      puis l'outillage npm verrouillé du projet (npm ci) et, sans Chrome,
#                                      le Chromium de Playwright
#   bash check-outils.sh --simuler     affiche les commandes d'installation sans rien lancer
# Dans un projet : make deps (installer) et make outils (état).
set -uo pipefail

mode=etat
case "${1:-}" in
    --installer) mode=installer ;;
    --simuler) mode=simuler ;;
    "") ;;
    *) echo "Usage : $0 [--installer|--simuler]" >&2; exit 2 ;;
esac

gestionnaire=""
command -v apt-get >/dev/null && gestionnaire=apt
command -v dnf >/dev/null && gestionnaire=dnf

# nom | commandes acceptées | obligatoire (1) ou conseillé (0) | paquets apt | paquets dnf | usage
outils=(
    "PHP 8.2+|php|1|php-cli php-mbstring|php-cli php-mbstring|build et serveurs du socle statique"
    "Node.js 20+|node|1|nodejs|nodejs|scripts de contrôle du kit"
    "npm|npm|1|npm|npm|outillage verrouillé du projet"
    "Git|git|1|git|git|historique, lastmod du sitemap"
    "make|make|1|make|make|commandes du projet"
    "curl|curl|1|curl|curl|contrôle des en-têtes et des pages servies"
    "Firefox|firefox|0|firefox|firefox|captures à 390 px (Chrome sans interface impose une largeur minimale)"
    "cwebp|cwebp|0|webp|libwebp-tools|conversion WebP"
    "avifenc|avifenc|0|libavif-bin|libavif-tools|conversion AVIF"
    "pyftsubset|pyftsubset|0|fonttools|fonttools|sous-ensemble de police woff2"
    "lftp|lftp|0|lftp|lftp|mise en ligne FTP ou SFTP sans SSH (skill web-release)"
    "rsync|rsync|0|rsync|rsync|mise en ligne par SSH (skill web-release)"
)

trouve() {
    local c
    for c in $1; do command -v "$c" >/dev/null && return 0; done
    return 1
}

chrome() {
    [[ -n "${CHROME_PATH:-}" && -x "$CHROME_PATH" ]] && return 0
    trouve "google-chrome chromium chromium-browser" && return 0
    compgen -G "$HOME/.cache/ms-playwright/chromium*" >/dev/null
}

manque_obligatoire=0
paquets=()

printf '%-20s %-8s %s\n' "Outil" "État" "Usage"
for ligne in "${outils[@]}"; do
    IFS='|' read -r nom commandes obligatoire apt dnf usage <<<"$ligne"
    if trouve "$commandes"; then
        printf '%-20s %-8s %s\n' "$nom" "ok" "$usage"
        continue
    fi
    if [[ "$obligatoire" == 1 ]]; then
        printf '%-20s %-8s %s\n' "$nom" "ABSENT" "$usage (obligatoire)"
        manque_obligatoire=1
    else
        printf '%-20s %-8s %s\n' "$nom" "absent" "$usage"
    fi
    if [[ "$gestionnaire" == dnf ]]; then paquets+=($dnf); else paquets+=($apt); fi
done

if chrome; then
    printf '%-20s %-8s %s\n' "Chrome ou Chromium" "ok" "rendu, accessibilité, captures, images de partage"
else
    printf '%-20s %-8s %s\n' "Chrome ou Chromium" "ABSENT" "rendu, accessibilité, captures (installé par Playwright avec --installer)"
    manque_obligatoire=1
fi

# Versions minimales.
if command -v php >/dev/null; then
    if ! php -r 'exit(PHP_VERSION_ID >= 80200 ? 0 : 1);'; then
        echo "PHP $(php -r 'echo PHP_VERSION;') trop ancien : 8.2 au moins."; manque_obligatoire=1
    elif ! php -r 'exit(extension_loaded("mbstring") ? 0 : 1);'; then
        echo "Extension PHP mbstring absente."; manque_obligatoire=1; paquets+=(php-mbstring)
    fi
fi
if command -v node >/dev/null && (( $(node -p 'process.versions.node.split(".")[0]') < 20 )); then
    echo "Node.js $(node -v) trop ancien : 20 au moins (paquet de la distribution trop vieux : installer depuis nodejs.org ou nvm)."
    manque_obligatoire=1
fi

npm_a_installer=0
if [[ -f package.json && -f package-lock.json ]]; then
    if [[ -d node_modules/playwright-core && -d node_modules/axe-core ]]; then
        echo "Outillage npm du projet : installé"
    else
        echo "Outillage npm du projet : à installer (npm ci)"
        npm_a_installer=1
        manque_obligatoire=1
    fi
fi

# Commandes d'installation, dans l'ordre.
commandes=()
if ((${#paquets[@]})); then
    mapfile -t paquets < <(printf '%s\n' "${paquets[@]}" | awk '!vu[$0]++')
    case "$gestionnaire" in
        apt) commandes+=("sudo apt-get update" "sudo apt-get install -y ${paquets[*]}") ;;
        dnf) commandes+=("sudo dnf install -y ${paquets[*]}") ;;
        *)   echo "Gestionnaire de paquets non reconnu : installer à la main ${paquets[*]}" ;;
    esac
fi
((npm_a_installer)) && commandes+=("npm ci --ignore-scripts")
chrome || commandes+=("npx --yes playwright-core@1.65.0 install chromium")

if [[ "$mode" == etat ]]; then
    if ((${#commandes[@]})); then
        echo
        echo "Pour tout installer en une commande : make deps (ou bash $0 --installer)"
    fi
    exit "$manque_obligatoire"
fi

if ((${#commandes[@]} == 0)); then
    echo; echo "Rien à installer."; exit 0
fi

echo
for c in "${commandes[@]}"; do
    echo "> $c"
    if [[ "$mode" == installer ]]; then
        bash -c "$c" || { echo "Échec : $c" >&2; exit 1; }
    fi
done
[[ "$mode" == simuler ]] && exit 0

echo
echo "Contrôle après installation :"
exec bash "$0"
