# Bijwerken

Board werk je bij vanaf de terminal:

```bash
git pull
./update.sh
```

## Wat `update.sh` doet

1. Controleert of er ongecommitte lokale wijzigingen zijn.
2. Vergelijkt `.env` met `.env.example` en waarschuwt over nieuwe/ontbrekende keys.
3. Maakt automatisch een `pg_dump` **vóór** de migraties (`./backups/pre-update-*.sql.gz`).
4. Bouwt de images (`docker compose build --pull`) en start ze (`up -d --remove-orphans`).
5. Draait `migrate --force` en warmt de caches
   (`config:cache`, `route:cache`, `view:cache`, `event:cache`), herstart de queue en
   synchroniseert de zoekindex.
6. Doet een health-check. **Faalt die, dan rolt het script automatisch terug** naar de
   vorige image-tag en zet het de database-dump terug.
7. Print de nieuwe versie en de laatste changelog-entry.

## Handmatige stappen

Sommige releases vereisen na `./update.sh` een handmatige actie. Die staan telkens
onderaan de betreffende entry in [`CHANGELOG.md`](../CHANGELOG.md) vermeld. Lees die
dus na een update even door.

## Terugrollen

`update.sh` rolt bij een mislukte health-check zelf terug. Wil je handmatig terug naar
een eerdere database-staat, gebruik dan een back-up:

```bash
./backup.sh --restore ./backups/pre-update-YYYYMMDD-HHMMSS.sql.gz
```
