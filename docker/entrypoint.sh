#!/usr/bin/env bash
#
# Board container entrypoint.
#
# Responsibilities (idempotent, safe to run on every boot):
#   1. Ensure APP_KEY and APP_SECRET exist, generating + persisting them on the
#      first ever boot so the user never has to touch secrets.
#   2. Wait until PostgreSQL accepts connections.
#   3. For the primary `app` role only: run migrations and warm caches.
#   4. Hand off to the requested process (php-fpm, reverb, queue, scheduler).
#
set -euo pipefail

APP_DIR=/var/www/html
SECRETS_FILE="${APP_DIR}/storage/app/private/board-secrets.env"
ROLE="${CONTAINER_ROLE:-app}"

log() { printf '\033[36m[entrypoint]\033[0m %s\n' "$*"; }

# --------------------------------------------------------------------------
# 1. Secrets — generated once, persisted on the storage volume, reused after.
# --------------------------------------------------------------------------
ensure_secrets() {
    mkdir -p "$(dirname "$SECRETS_FILE")"

    # Load anything we persisted on a previous boot.
    if [[ -f "$SECRETS_FILE" ]]; then
        # shellcheck disable=SC1090
        set -a; source "$SECRETS_FILE"; set +a
    fi

    local changed=0

    if [[ -z "${APP_KEY:-}" ]]; then
        log "APP_KEY leeg — genereer een nieuwe applicatiesleutel."
        APP_KEY="base64:$(head -c 32 /dev/urandom | base64)"
        export APP_KEY
        changed=1
    fi

    if [[ -z "${APP_SECRET:-}" ]]; then
        log "APP_SECRET leeg — genereer een nieuw applicatiegeheim."
        APP_SECRET="$(head -c 48 /dev/urandom | od -An -tx1 | tr -d ' \n')"
        export APP_SECRET
        changed=1
    fi

    if [[ "$changed" -eq 1 ]]; then
        {
            echo "APP_KEY=${APP_KEY}"
            echo "APP_SECRET=${APP_SECRET}"
        } > "$SECRETS_FILE"
        chmod 600 "$SECRETS_FILE"
        log "Secrets weggeschreven naar het storage-volume."
    fi
}

# --------------------------------------------------------------------------
# 2. Wait for PostgreSQL.
# --------------------------------------------------------------------------
wait_for_db() {
    log "Wachten op PostgreSQL op ${DB_HOST:-postgres}:${DB_PORT:-5432} ..."
    wait-for-postgres
    log "PostgreSQL is bereikbaar."
}

# --------------------------------------------------------------------------
# 3. Migrate + warm caches (primary role only, so only one service migrates).
# --------------------------------------------------------------------------
bootstrap_app() {
    log "Migraties draaien (php artisan migrate --force) ..."
    php artisan migrate --force --no-interaction

    if [[ "${APP_ENV:-production}" == "production" ]]; then
        log "Caches warmen (config/route/view/event) ..."
        php artisan config:cache
        php artisan route:cache
        php artisan view:cache
        php artisan event:cache
    fi

    php artisan storage:link --quiet 2>/dev/null || true
}

ensure_secrets

# The very first arg tells us what to run. Only real app processes need the db.
case "$ROLE" in
    app)
        wait_for_db
        bootstrap_app
        ;;
    reverb|queue|scheduler|horizon)
        wait_for_db
        ;;
    *)
        ;;
esac

log "Start rol '${ROLE}': $*"
exec "$@"
