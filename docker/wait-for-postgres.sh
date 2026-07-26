#!/usr/bin/env bash
# Block until PostgreSQL accepts connections, or give up after ~60s.
set -euo pipefail

host="${DB_HOST:-postgres}"
port="${DB_PORT:-5432}"
user="${DB_USERNAME:-board}"

for i in $(seq 1 60); do
    if pg_isready -h "$host" -p "$port" -U "$user" >/dev/null 2>&1; then
        exit 0
    fi
    sleep 1
done

echo "PostgreSQL op ${host}:${port} was niet bereikbaar binnen 60 seconden." >&2
exit 1
