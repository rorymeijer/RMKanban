#!/usr/bin/env bash
#
# License-server entrypoint: genereert bij de eerste start APP_KEY en het
# Ed25519-sleutelpaar (en persisteert ze op het storage-volume), wacht op de
# database en draait de migraties.
#
set -euo pipefail

APP_DIR=/var/www/html
SECRETS_FILE="${APP_DIR}/storage/app/license-secrets.env"
ROLE="${CONTAINER_ROLE:-app}"

log() { printf '\033[36m[entrypoint]\033[0m %s\n' "$*"; }

mkdir -p "$(dirname "$SECRETS_FILE")"
if [[ -f "$SECRETS_FILE" ]]; then
    # shellcheck disable=SC1090
    set -a; source "$SECRETS_FILE"; set +a
fi

changed=0

if [[ -z "${APP_KEY:-}" ]]; then
    log "APP_KEY genereren."
    APP_KEY="base64:$(head -c 32 /dev/urandom | base64)"
    export APP_KEY
    changed=1
fi

if [[ -z "${LICENSE_PRIVATE_KEY:-}" ]]; then
    log "Ed25519-sleutelpaar genereren."
    eval "$(php -r '$k=sodium_crypto_sign_keypair(); echo "LICENSE_PRIVATE_KEY=".base64_encode(sodium_crypto_sign_secretkey($k))."\n"; echo "LICENSE_PUBLIC_KEY=".base64_encode(sodium_crypto_sign_publickey($k))."\n";' | sed 's/^/export /')"
    changed=1
fi

if [[ "$changed" -eq 1 ]]; then
    {
        echo "APP_KEY=${APP_KEY}"
        echo "LICENSE_PRIVATE_KEY=${LICENSE_PRIVATE_KEY}"
        echo "LICENSE_PUBLIC_KEY=${LICENSE_PUBLIC_KEY}"
    } > "$SECRETS_FILE"
    chmod 600 "$SECRETS_FILE"
    log "Sleutels weggeschreven naar het storage-volume."
fi

if [[ "$ROLE" == "app" ]]; then
    log "Wachten op PostgreSQL ..."
    for _ in $(seq 1 60); do
        if pg_isready -h "${DB_HOST:-postgres}" -p "${DB_PORT:-5432}" -U "${DB_USERNAME:-license}" >/dev/null 2>&1; then
            break
        fi
        sleep 1
    done
    log "Migraties draaien ..."
    php artisan migrate --force --no-interaction
    php artisan config:cache
    php artisan route:cache
fi

log "Start: $*"
exec "$@"
