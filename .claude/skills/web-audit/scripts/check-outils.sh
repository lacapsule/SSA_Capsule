#!/usr/bin/env bash
# SPDX-License-Identifier: GPL-3.0-or-later
# Outils dont le kit a besoin pour travailler sans aide : présent, absent, et commande d'installation.
# Usage : bash check-outils.sh    (code 1 si un outil obligatoire manque)
set -uo pipefail

gestionnaire=""
command -v apt-get >/dev/null && gestionnaire=apt
command -v dnf >/dev/null && gestionnaire=dnf

manque_obligatoire=0
manque_conseille=()

# nom | commande | obligatoire (1) ou conseillé (0) | paquet apt | paquet dnf | usage
outils=(
    "PHP 8.2+|php|1|php-cli php-mbstring|php-cli php-mbstring|build et serveurs du socle statique"
    "Node.js 20+|node|1|nodejs|nodejs|scripts de contrôle du kit"
    "npm|npm|1|npm|npm|outillage verrouillé (make outils)"
    "Git|git|1|git|git|historique, lastmod du sitemap"
    "make|make|1|make|make|commandes du projet"
    "curl|curl|1|curl|curl|contrôle des en-têtes et des pages servies"
    "Chrome ou Chromium|google-chrome chromium chromium-browser|1|chromium|chromium|rendu, accessibilité, captures, images de partage"
    "Firefox|firefox|0|firefox|firefox|captures à 390 px (Chrome sans interface impose une largeur minimale)"
    "cwebp|cwebp|0|webp|libwebp-tools|conversion WebP"
    "avifenc|avifenc|0|libavif-bin|libavif-tools|conversion AVIF"
    "pyftsubset|pyftsubset|0|fonttools|fonttools|sous-ensemble de police woff2"
    "lftp|lftp|0|lftp|lftp|mise en ligne FTP ou SFTP sans SSH (skill web-release)"
    "rsync|rsync|0|rsync|rsync|mise en ligne par SSH (skill web-release)"
)

trouve() {
    local c
    for c in $1; do command -v "$c" >/dev/null && { echo "$c"; return 0; }; done
    return 1
}

printf '%-20s %-8s %s\n' "Outil" "État" "Usage"
for ligne in "${outils[@]}"; do
    IFS='|' read -r nom commandes obligatoire apt dnf usage <<<"$ligne"
    if chemin="$(trouve "$commandes")"; then
        printf '%-20s %-8s %s\n' "$nom" "ok" "$usage"
    else
        paquet="$apt"; [[ "$gestionnaire" == dnf ]] && paquet="$dnf"
        if [[ "$obligatoire" == 1 ]]; then
            printf '%-20s %-8s %s\n' "$nom" "ABSENT" "$usage (obligatoire)"
            manque_obligatoire=1
        else
            printf '%-20s %-8s %s\n' "$nom" "absent" "$usage"
        fi
        manque_conseille+=("$paquet")
    fi
done

# Version de PHP : le socle exige 8.2.
if command -v php >/dev/null && ! php -r 'exit(PHP_VERSION_ID >= 80200 ? 0 : 1);'; then
    echo "PHP $(php -r 'echo PHP_VERSION;') trop ancien : 8.2 au moins."
    manque_obligatoire=1
fi

# Outillage npm du projet (playwright-core, axe-core, svgo), verrouillé par package-lock.json.
if [[ -f package.json ]]; then
    if [[ -d node_modules/playwright-core && -d node_modules/axe-core ]]; then
        echo "Outillage npm du projet : installé"
    else
        echo "Outillage npm du projet : à installer (npm ci)"
    fi
fi

if ((${#manque_conseille[@]})); then
    echo
    case "$gestionnaire" in
        apt) echo "Installation (par l'humain) : sudo apt-get install ${manque_conseille[*]}" ;;
        dnf) echo "Installation (par l'humain) : sudo dnf install ${manque_conseille[*]}" ;;
        *)   echo "Paquets à installer : ${manque_conseille[*]}" ;;
    esac
fi
echo "Chrome utilisé par les scripts : variable CHROME_PATH, sinon celui de Playwright (npx playwright-core install chromium)."
exit "$manque_obligatoire"
