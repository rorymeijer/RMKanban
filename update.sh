#!/usr/bin/env bash
#
# Board — update vanuit de terminal.
#
#   git pull
#   ./update.sh
#
# Maakt vóór migraties automatisch een database-dump, bouwt de nieuwe images,
# draait migraties + cache-warming, doet een health-check en rolt bij falen terug
# naar de vorige image-tag en database-dump.
#
set -euo pipefail

cd "$(dirname "$0")"

BLAUW='\033[36m'; GEEL='\033[33m'; ROOD='\033[31m'; GROEN='\033[32m'; EINDE='\033[0m'
info()  { printf "${BLAUW}[update]${EINDE} %s\n" "$*"; }
waarschuw() { printf "${GEEL}[update]${EINDE} %s\n" "$*"; }
fout()  { printf "${ROOD}[update]${EINDE} %s\n" "$*" >&2; }
klaar() { printf "${GROEN}[update]${EINDE} %s\n" "$*"; }

COMPOSE="docker compose"
DC_EXEC="$COMPOSE exec -T app"
BACKUP_DIR="./backups"
TS="$(date +%Y%m%d-%H%M%S)"

# --- 1. Ongecommitte lokale wijzigingen? --------------------------------------
if [[ -n "$(git status --porcelain 2>/dev/null || true)" ]]; then
    waarschuw "Er zijn ongecommitte lokale wijzigingen. Commit of stash ze eerst."
    git status --short
    read -r -p "Toch doorgaan? [j/N] " antwoord
    [[ "${antwoord:-N}" =~ ^[jJ]$ ]] || { fout "Afgebroken."; exit 1; }
fi

# --- 2. Nieuwe/ontbrekende .env-keys signaleren -------------------------------
if [[ -f .env && -f .env.example ]]; then
    info "Controleren op nieuwe of ontbrekende .env-keys ..."
    huidige_keys=$(grep -oE '^[A-Z_]+=' .env | sort -u || true)
    voorbeeld_keys=$(grep -oE '^[A-Z_]+=' .env.example | sort -u || true)
    ontbrekend=$(comm -23 <(echo "$voorbeeld_keys") <(echo "$huidige_keys") || true)
    if [[ -n "$ontbrekend" ]]; then
        waarschuw "Deze keys staan in .env.example maar niet in .env:"
        echo "$ontbrekend" | sed 's/^/    /'
    fi
fi

# --- 3. Database-dump vóór migraties ------------------------------------------
mkdir -p "$BACKUP_DIR"
DUMP="$BACKUP_DIR/pre-update-$TS.sql.gz"
info "Database-dump maken naar $DUMP ..."
$COMPOSE exec -T postgres pg_dump -U board -d board | gzip > "$DUMP"
klaar "Dump gemaakt."

# Huidige image-tag onthouden voor rollback.
VORIGE_IMAGE="$(docker inspect --format='{{.Config.Image}}' "$($COMPOSE ps -q app)" 2>/dev/null || echo '')"

rollback() {
    fout "Update mislukt — terugrollen ..."
    if [[ -n "$VORIGE_IMAGE" ]]; then
        APP_VERSION="${VORIGE_IMAGE##*:}" $COMPOSE up -d --no-build app web reverb queue-worker scheduler || true
    fi
    info "Database terugzetten vanuit $DUMP ..."
    gunzip -c "$DUMP" | $COMPOSE exec -T postgres psql -U board -d board || true
    fout "Teruggerold naar de vorige versie."
    exit 1
}

# --- 4. Bouwen en starten -----------------------------------------------------
info "Images bouwen ($COMPOSE build --pull) ..."
$COMPOSE build --pull || rollback
info "Services starten ..."
$COMPOSE up -d --remove-orphans || rollback

# --- 5. Migraties + caches ----------------------------------------------------
info "Migraties en cache-warming ..."
$DC_EXEC php artisan migrate --force || rollback
$DC_EXEC php artisan config:cache
$DC_EXEC php artisan route:cache
$DC_EXEC php artisan view:cache
$DC_EXEC php artisan event:cache
$DC_EXEC php artisan queue:restart
$DC_EXEC php artisan scout:sync-index-settings 2>/dev/null || true

# --- 6. Health-check ----------------------------------------------------------
info "Health-check ..."
gezond=0
for _ in $(seq 1 20); do
    if curl -fsS "http://localhost:${APP_PORT:-8080}/api/health" >/dev/null 2>&1; then
        gezond=1; break
    fi
    sleep 3
done
[[ "$gezond" -eq 1 ]] || rollback

# --- 7. Versie + changelog ----------------------------------------------------
NIEUWE_VERSIE="$($DC_EXEC cat VERSION 2>/dev/null | tr -d '\r\n' || echo 'onbekend')"
klaar "Update voltooid. Nieuwe versie: ${NIEUWE_VERSIE}"
if [[ -f CHANGELOG.md ]]; then
    info "Laatste changelog-entry:"
    awk '/^## /{c++} c==1{print} c==2{exit}' CHANGELOG.md | sed 's/^/    /'
fi
