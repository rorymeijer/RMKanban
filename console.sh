#!/usr/bin/env bash
#
# Board — snelkoppeling naar artisan in de container.
#
#   ./console.sh migrate
#   ./console.sh tinker
#   ./console.sh queue:work
#
set -euo pipefail
cd "$(dirname "$0")"
exec docker compose exec app php artisan "$@"
