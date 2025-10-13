#!/usr/bin/env bash
# setup-local.sh — Setup local pour SSA_CAPSULE (php -S + SQLite). MySQL optionnel plus tard.
# USAGE:
#   ./setup-local.sh info       # versions & état
#   ./setup-local.sh init       # crée data/, applique migrations SQLite si DB absente, génère scripts/manquants
#   ./setup-local.sh reset      # réinitialise la DB (SQLite) depuis migrations/tables.sql
#   ./setup-local.sh dev        # lance php -S localhost:${PORT:-8080} -t public
#   ./setup-local.sh deps       # installe php-pdo + sqlite3 (+ mysql) via dnf/apt, puis vérifie
#   ./setup-local.sh db.shell   # shell sqlite
#   ./setup-local.sh bin.install   # (ré)génère bin/dev et bin/db si absents
#   ./setup-local.sh make.inject   # ajoute des cibles Make (non destructif)
#
# ENV:
#   PORT=8080 PUBLIC_DIR=public DATA_DIR=./data MIG_FILE=./migrations/tables.sql

set -euo pipefail

ROOT="$(pwd)"
PUBLIC_DIR="${PUBLIC_DIR:-public}"
DATA_DIR="${DATA_DIR:-$ROOT/data}"
DB_SQLITE="${DB_SQLITE:-$DATA_DIR/database.sqlite}"

# migrations/tables.sql est ta source actuelle
MIG_FILE="${MIG_FILE:-$ROOT/migrations/sqlite_init.sql}"

need() { command -v "$1" >/dev/null 2>&1 || {
    echo "❌ Manque binaire: $1"
    exit 1
}; }

ensure_dirs() { mkdir -p "$DATA_DIR" "$ROOT/bin" "$ROOT/config"; }

ensure_public() {
    [[ -d "$PUBLIC_DIR" ]] || {
        echo "❌ Dossier public introuvable: $PUBLIC_DIR"
        exit 1
    }
    [[ -f "$PUBLIC_DIR/index.php" ]] || {
        cat >"$PUBLIC_DIR/index.php" <<'PHP'
<?php declare(strict_types=1);
echo "Capsule running (local).";
PHP
        echo "ℹ️  Généré $PUBLIC_DIR/index.php minimal (remplace-le par ton front controller)."
    }
}

ensure_config_php() {
    local cfg="$ROOT/config/database.php"
    [[ -f "$cfg" ]] && return 0
    cat >"$cfg" <<'PHP'
<?php
declare(strict_types=1);

return [
    'dsn'      => $_ENV['DB_DSN']      ?? 'sqlite:' . dirname(__DIR__) . '/data/database.sqlite',
    'user'     => $_ENV['DB_USER']     ?? null,
    'password' => $_ENV['DB_PASSWORD'] ?? null,
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ],
];
PHP
    echo "✅ Créé config/database.php (DSN par défaut = SQLite)."
}

detect_pkg() {
    if command -v dnf >/dev/null 2>&1; then
        echo "dnf"
    elif command -v apt-get >/dev/null 2>&1; then
        echo "apt"
    else
        echo "unknown"
    fi
}

install_deps() {
    local pm pkgs
    pm="$(detect_pkg)"

    case "$pm" in
        dnf)
            # Fedora / RHEL-like
            pkgs=(php-cli php-pdo php-sqlite3 php-mysqlnd)
            echo "🔧 Installer: ${pkgs[*]} (via dnf)"
            if command -v sudo >/dev/null 2>&1; then
                sudo dnf install -y "${pkgs[@]}"
            else
                dnf install -y "${pkgs[@]}"
            fi
            ;;
        apt)
            # Debian/Ubuntu — PDO est dans le coeur, on ajoute sqlite + mysql
            pkgs=(php-cli php-sqlite3 php-mysql)
            echo "🔧 Installer: ${pkgs[*]} (via apt)"
            if command -v sudo >/dev/null 2>&1; then
                sudo apt-get update
                sudo apt-get install -y "${pkgs[@]}"
            else
                apt-get update
                apt-get install -y "${pkgs[@]}"
            fi
            ;;
        *)
            echo "❌ Gestionnaire de paquets non supporté. Installe manuellement les paquets suivants :"
            echo "   - Fedora:  php-cli php-pdo php-sqlite3 php-mysqlnd"
            echo "   - Debian:  php-cli php-sqlite3 php-mysql"
            return 1
            ;;
    esac

    echo "✅ Dépendances installées. Vérification :"
    if command -v rg >/dev/null 2>&1; then
        php -m | rg -i 'pdo|sqlite|mysql' || true
    else
        php -m | grep -Ei 'pdo|sqlite|mysql' || true
    fi
    php -v
}

ensure_migration_file() {
    [[ -f "$MIG_FILE" ]] || {
        echo "❌ Migration introuvable: $MIG_FILE"
        exit 1
    }
}

sqlite_apply_migration_fresh() {
    need sqlite3
    ensure_migration_file
    : >"$DB_SQLITE"
    sqlite3 "$DB_SQLITE" <"$MIG_FILE"
    echo "✅ SQLite initialisée (fresh): $DB_SQLITE"
}

sqlite_apply_migration_if_absent() {
    need sqlite3
    ensure_migration_file
    if [[ -f "$DB_SQLITE" ]]; then
        echo "ℹ️  DB déjà présente: $DB_SQLITE (skip init). Utilise 'reset' si tu veux repartir de zéro."
    else
        : >"$DB_SQLITE"
        sqlite3 "$DB_SQLITE" <"$MIG_FILE"
        echo "✅ SQLite initialisée: $DB_SQLITE"
    fi
}

bin_install() {
    local db="$ROOT/bin/db" dev="$ROOT/bin/dev"
    if [[ ! -f "$db" ]]; then
        cat >"$db" <<'BASH'
#!/usr/bin/env bash
set -euo pipefail
DB="./data/database.sqlite"
SQL=""
if [[ -f "./migrations/tables.sql" ]]; then
  SQL="./migrations/tables.sql"
else
  echo "❌ Aucune migration trouvée (./migrations/tables.sql)"; exit 1
fi
mkdir -p ./data
case "${1:-}" in
  init)  : > "$DB"; sqlite3 "$DB" < "$SQL"; echo "DB initialized at $DB" ;;
  reset) rm -f "$DB"; sqlite3 "$DB" < "$SQL"; echo "DB reset" ;;
  shell) sqlite3 "$DB" ;;
  *) echo "Usage: bin/db {init|reset|shell}" >&2; exit 1;;
esac
BASH
        chmod +x "$db"
        echo "✅ Créé bin/db"
    fi
    if [[ ! -f "$dev" ]]; then
        cat >"$dev" <<'BASH'
#!/usr/bin/env bash
set -euo pipefail
PORT="${PORT:-8080}"
PHP_OPTS="-d display_errors=1 -d error_reporting=32767 -d zend.assertions=1 -d assert.exception=1"
echo "→ http://localhost:$PORT"
php $PHP_OPTS -S localhost:"$PORT" -t public
BASH
        chmod +x "$dev"
        echo "✅ Créé bin/dev"
    fi
}

make_inject() {
    local mk="$ROOT/Makefile"
    if [[ ! -f "$mk" ]]; then
        cat >"$mk" <<'MAKE'
.PHONY: dev db.init db.reset db.shell info
dev: ; bin/dev
db.init: ; bin/db init
db.reset: ; bin/db reset
db.shell: ; bin/db shell
info:
	@php -v
	@sqlite3 --version || true
MAKE
        echo "✅ Créé Makefile minimal."
        return
    fi

    # Ajoute cibles manquantes, sans détruire l’existant.
    add_target() { grep -qE "^$1:" "$mk" || printf "%s\n" "$2" >>"$mk"; }
    add_target "dev" $'dev: ; bin/dev'
    add_target "db.init" $'db.init: ; bin/db init'
    add_target "db.reset" $'db.reset: ; bin/db reset'
    add_target "db.shell" $'db.shell: ; bin/db shell'
    if ! grep -qE '^info:' "$mk"; then
        cat >>"$mk" <<'MAKE'

info:
	@php -v
	@sqlite3 --version || true
MAKE
    fi
    echo "✅ Makefile enrichi (non destructif)."
}

cmd="${1:-help}"
case "$cmd" in
    info)
        php -v
        php -m | grep -Ei 'pdo|sqlite|mysql' || true
        command -v sqlite3 && sqlite3 --version || true
        command -v mysql && mysql --version || echo "(mysql non installé)"
        echo "Public dir : $PUBLIC_DIR"
        echo "DB (SQLite): $DB_SQLITE"
        echo "Migration   : $MIG_FILE"
        ;;
    init)
        need php
        ensure_dirs
        ensure_public
        ensure_config_php
        bin_install
        make_inject
        sqlite_apply_migration_if_absent
        echo "🎉 Init terminé. Lance: ./setup-local.sh dev  (ou  make dev)"
        ;;
    reset)
        sqlite_apply_migration_fresh
        ;;
    dev)
        need php
        [[ -d "$PUBLIC_DIR" ]] || {
            echo "❌ $PUBLIC_DIR manquant"
            exit 1
        }
        echo "🔌 Serveur dev → http://localhost:${PORT:-8080}  (CTRL+C pour arrêter)"
        php -d display_errors=1 -d error_reporting=32767 -S "localhost:${PORT:-8080}" -t "$PUBLIC_DIR"
        ;;
    db.shell)
        need sqlite3
        sqlite3 "$DB_SQLITE"
        ;;
    bin.install)
        bin_install
        ;;
    make.inject)
        make_inject
        ;;
    *)
        echo "Usage: $0 {info|init|reset|dev|db.shell|bin.install|make.inject}"
        exit 1
        ;;
esac
