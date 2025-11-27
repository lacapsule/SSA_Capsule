#!/bin/bash
# Script pour copier les fichiers modifiés du worktree vers le dépôt principal

WORKTREE="/home/n0r3f/.cursor/worktrees/SSA_Capsule/TJ3Xv"
MAIN_REPO="/home/n0r3f/DEV/SSA_Capsule"

echo "Copie des fichiers du worktree vers le dépôt principal..."

# Fichiers modifiés
MODIFIED_FILES=(
    "app/Modules/Dashboard/DashboardPresenter.php"
    "app/Modules/Home/HomeController.php"
    "app/Modules/Home/HomeService.php"
    "app/Providers/SidebarLinksProvider.php"
    "bootstrap/app.php"
    "config/container.php"
    "migrations/sqlite_init.sql"
    "migrations/tables.sql"
    "public/index.php"
    "templates/layouts/dashboard.tpl.php"
    "templates/modules/home/components/contact.tpl.php"
)

# Fichiers nouveaux
NEW_FILES=(
    "app/Modules/Home/ContactRepository.php"
    "app/Modules/Home/ContactService.php"
    "app/Modules/Partners/PartnersController.php"
    "app/Support/Mailer.php"
    "public/assets/css/dashboard/dash-partners.css"
    "public/modules/dashboard/partners.js"
    "src/Domain/Repository/PartnerLogoRepository.php"
    "src/Domain/Repository/PartnerSectionRepository.php"
    "src/Domain/Service/PartnersService.php"
    "src/Http/Middleware/HealthCheckMiddleware.php"
    "templates/modules/dashboard/components/partners.tpl.php"
    "bin/import_partners_from_provider.php"
)

# Copier les fichiers modifiés
for file in "${MODIFIED_FILES[@]}"; do
    if [ -f "$WORKTREE/$file" ]; then
        echo "Copie: $file"
        mkdir -p "$(dirname "$MAIN_REPO/$file")"
        cp "$WORKTREE/$file" "$MAIN_REPO/$file"
    else
        echo "⚠️  Fichier non trouvé: $file"
    fi
done

# Copier les fichiers nouveaux
for file in "${NEW_FILES[@]}"; do
    if [ -f "$WORKTREE/$file" ]; then
        echo "Copie (nouveau): $file"
        mkdir -p "$(dirname "$MAIN_REPO/$file")"
        cp "$WORKTREE/$file" "$MAIN_REPO/$file"
    else
        echo "⚠️  Fichier non trouvé: $file"
    fi
done

# Copier le dossier rules s'il existe
if [ -d "$WORKTREE/rules" ]; then
    echo "Copie du dossier: rules/"
    cp -r "$WORKTREE/rules" "$MAIN_REPO/rules"
fi

# Copier le dossier app/Modules/Partners s'il existe
if [ -d "$WORKTREE/app/Modules/Partners" ]; then
    echo "Copie du dossier: app/Modules/Partners/"
    mkdir -p "$MAIN_REPO/app/Modules/Partners"
    cp -r "$WORKTREE/app/Modules/Partners"/* "$MAIN_REPO/app/Modules/Partners/"
fi

echo "✅ Copie terminée!"

