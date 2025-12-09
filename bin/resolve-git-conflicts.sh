#!/bin/bash
#
# Script pour résoudre automatiquement les conflits Git
# Usage: ./bin/resolve-git-conflicts.sh [strategy]
# Strategies: ours, theirs, union, merge (default)
#

set -e

STRATEGY="${1:-merge}"

echo "🔍 Vérification de l'état Git..."

# Vérifier si on est dans un rebase
if [ -d ".git/rebase-merge" ] || [ -d ".git/rebase-apply" ]; then
    echo "📦 Rebase en cours détecté"
    REBASE_MODE=true
elif git merge HEAD &>/dev/null; then
    echo "🔀 Merge en cours détecté"
    REBASE_MODE=false
else
    echo "✅ Aucun conflit en cours"
    exit 0
fi

# Chercher les fichiers en conflit
CONFLICT_FILES=$(git diff --name-only --diff-filter=U 2>/dev/null || true)

if [ -z "$CONFLICT_FILES" ]; then
    echo "✅ Aucun fichier en conflit détecté"
    
    # Si on est en rebase et tout est résolu, continuer
    if [ "$REBASE_MODE" = true ]; then
        echo "▶️  Continuation du rebase..."
        # Utiliser le message du commit original si disponible
        if [ -f ".git/rebase-merge/message" ]; then
            git commit --amend -F .git/rebase-merge/message 2>/dev/null || git commit --amend --no-edit 2>/dev/null || true
        fi
        git rebase --continue || echo "⚠️  Le rebase nécessite peut-être une action manuelle"
    else
        echo "▶️  Finalisation du merge..."
        git commit --no-edit 2>/dev/null || echo "⚠️  Le merge nécessite peut-être un message de commit"
    fi
    exit 0
fi

echo "⚠️  Fichiers en conflit détectés:"
echo "$CONFLICT_FILES"
echo ""

# Résoudre automatiquement selon la stratégie
case "$STRATEGY" in
    ours)
        echo "📌 Stratégie: garder nos modifications (ours)"
        for file in $CONFLICT_FILES; do
            echo "  → Résolution de $file (ours)"
            git checkout --ours "$file" 2>/dev/null || true
            git add "$file" 2>/dev/null || true
        done
        ;;
    theirs)
        echo "📌 Stratégie: accepter leurs modifications (theirs)"
        for file in $CONFLICT_FILES; do
            echo "  → Résolution de $file (theirs)"
            git checkout --theirs "$file" 2>/dev/null || true
            git add "$file" 2>/dev/null || true
        done
        ;;
    union)
        echo "📌 Stratégie: union (garder les deux versions)"
        for file in $CONFLICT_FILES; do
            echo "  → Résolution de $file (union)"
            git checkout --union "$file" 2>/dev/null || true
            git add "$file" 2>/dev/null || true
        done
        ;;
    merge|*)
        echo "📌 Stratégie: résolution manuelle requise"
        echo ""
        echo "Pour résoudre automatiquement, utilisez:"
        echo "  - ./bin/resolve-git-conflicts.sh ours    (garder nos modifications)"
        echo "  - ./bin/resolve-git-conflicts.sh theirs  (accepter leurs modifications)"
        echo "  - ./bin/resolve-git-conflicts.sh union   (garder les deux versions)"
        echo ""
        echo "Ou résolvez manuellement les conflits dans:"
        for file in $CONFLICT_FILES; do
            echo "  - $file"
        done
        exit 1
        ;;
esac

# Vérifier s'il reste des conflits
REMAINING_CONFLICTS=$(git diff --check 2>/dev/null | grep -c "conflict" || echo "0")

if [ "$REMAINING_CONFLICTS" -gt 0 ]; then
    echo "⚠️  Il reste des marqueurs de conflit dans les fichiers"
    echo "Vérifiez manuellement les fichiers suivants:"
    git diff --check 2>/dev/null | grep "conflict" || true
    exit 1
fi

echo "✅ Tous les conflits ont été résolus"

# Continuer le rebase ou finaliser le merge
if [ "$REBASE_MODE" = true ]; then
    echo "▶️  Continuation du rebase..."
    if [ -f ".git/rebase-merge/message" ]; then
        git commit --amend -F .git/rebase-merge/message 2>/dev/null || git commit --amend --no-edit 2>/dev/null || true
    fi
    git rebase --continue || echo "⚠️  Le rebase nécessite peut-être une action manuelle"
else
    echo "▶️  Finalisation du merge..."
    git commit --no-edit 2>/dev/null || echo "⚠️  Le merge nécessite peut-être un message de commit"
fi

echo "✅ Terminé !"

