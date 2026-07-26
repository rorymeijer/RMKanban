#!/usr/bin/env bash
#
# Board — back-up en herstel.
#
#   ./backup.sh              maak een back-up (pg_dump + storage)
#   ./backup.sh --restore <bestand>   zet een back-up terug
#
# Retentie: dagelijks 7 stuks, wekelijks 4 stuks.
#
set -euo pipefail

cd "$(dirname "$0")"

COMPOSE="docker compose"
BACKUP_DIR="./backups"
DAILY_DIR="$BACKUP_DIR/daily"
WEEKLY_DIR="$BACKUP_DIR/weekly"
TS="$(date +%Y%m%d-%H%M%S)"

GROEN='\033[32m'; BLAUW='\033[36m'; ROOD='\033[31m'; EINDE='\033[0m'
info()  { printf "${BLAUW}[backup]${EINDE} %s\n" "$*"; }
klaar() { printf "${GROEN}[backup]${EINDE} %s\n" "$*"; }
fout()  { printf "${ROOD}[backup]${EINDE} %s\n" "$*" >&2; }

maak_backup() {
    mkdir -p "$DAILY_DIR" "$WEEKLY_DIR"
    local db_dump="$DAILY_DIR/db-$TS.sql.gz"
    local storage_tar="$DAILY_DIR/storage-$TS.tar.gz"

    info "Database dumpen ..."
    $COMPOSE exec -T postgres pg_dump -U board -d board | gzip > "$db_dump"

    info "Storage archiveren ..."
    $COMPOSE exec -T app tar -czf - -C /var/www/html/storage/app . > "$storage_tar"

    klaar "Back-up gemaakt: $db_dump + $storage_tar"

    # Wekelijkse kopie op maandag.
    if [[ "$(date +%u)" == "1" ]]; then
        cp "$db_dump" "$WEEKLY_DIR/" && cp "$storage_tar" "$WEEKLY_DIR/"
        info "Wekelijkse kopie opgeslagen."
    fi

    # Retentie toepassen.
    prune "$DAILY_DIR" 14   # 7 dagen × (db + storage)
    prune "$WEEKLY_DIR" 8   # 4 weken × (db + storage)
}

prune() {
    local dir="$1" keep="$2"
    local files
    mapfile -t files < <(ls -1t "$dir" 2>/dev/null || true)
    if (( ${#files[@]} > keep )); then
        for f in "${files[@]:$keep}"; do
            rm -f "$dir/$f"
            info "Verwijderd (retentie): $f"
        done
    fi
}

herstel() {
    local bestand="$1"
    [[ -f "$bestand" ]] || { fout "Bestand niet gevonden: $bestand"; exit 1; }
    fout "LET OP: dit overschrijft de huidige database."
    read -r -p "Doorgaan? [j/N] " a
    [[ "${a:-N}" =~ ^[jJ]$ ]] || { info "Afgebroken."; exit 0; }

    info "Database terugzetten uit $bestand ..."
    gunzip -c "$bestand" | $COMPOSE exec -T postgres psql -U board -d board
    klaar "Database hersteld."
}

case "${1:-}" in
    --restore) herstel "${2:?Geef een back-upbestand op}" ;;
    "")        maak_backup ;;
    *)         fout "Onbekende optie: $1"; echo "Gebruik: ./backup.sh [--restore <bestand>]"; exit 1 ;;
esac
