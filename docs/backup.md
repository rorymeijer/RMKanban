# Back-up & herstel

## Back-up maken

```bash
./backup.sh
```

Dit maakt:
- een `pg_dump` van de database (`./backups/daily/db-*.sql.gz`);
- een archief van de storage-map met uploads (`./backups/daily/storage-*.tar.gz`).

Op maandag wordt automatisch ook een wekelijkse kopie in `./backups/weekly/` gezet.

### Retentie

- **Dagelijks:** de laatste 7 back-ups blijven bewaard.
- **Wekelijks:** de laatste 4 back-ups blijven bewaard.

Oudere back-ups worden automatisch opgeruimd.

### Automatiseren

Zet `./backup.sh` in een cron op de host, bijvoorbeeld dagelijks om 03:00:

```cron
0 3 * * * cd /pad/naar/board && ./backup.sh >> ./backups/backup.log 2>&1
```

## Herstellen

```bash
./backup.sh --restore ./backups/daily/db-YYYYMMDD-HHMMSS.sql.gz
```

Het script vraagt om bevestiging, want dit **overschrijft** de huidige database.

## Waar staat de data?

Alle persistente data staat in named Docker-volumes:

- `pg_data` — de PostgreSQL-database;
- `meili_data` — de zoekindex (herbouwbaar);
- `app_storage` — uploads, gegenereerde geheimen en de installatievlag.

Neem deze volumes mee in je eigen off-site back-upstrategie voor volledige zekerheid.
