# Installatie

Board draait volledig in Docker en brengt geen reverse proxy of TLS mee. Je zet
het achter je bestaande Caddy.

## Vereisten

- Een server met Docker en de Docker Compose-plugin.
- Een bestaande Caddy (of andere reverse proxy) die TLS afhandelt.
- Een (sub)domein dat naar je server wijst.

## Stappen

1. **Clonen en opstarten**

   ```bash
   git clone <deze-repo> board
   cd board
   echo "APP_URL=https://board.voorbeeld.nl" > .env
   docker compose up -d
   ```

   Bij de eerste start gebeurt automatisch:
   - `APP_KEY` en `APP_SECRET` worden gegenereerd en op het storage-volume bewaard;
   - de app wacht tot PostgreSQL klaar is;
   - `php artisan migrate --force` draait de migraties.

2. **Caddy laten wijzen naar de app**

   Voeg toe aan je Caddyfile:

   ```caddy
   board.voorbeeld.nl {
       reverse_proxy localhost:8080
   }
   ```

   Herlaad Caddy (`caddy reload` of `systemctl reload caddy`).

3. **Installer doorlopen**

   Open `https://board.voorbeeld.nl`. De web-installer verschijnt:
   1. **Toepassing** — weergavenaam, standaardtaal, tijdzone.
   2. **Beheerder** — naam, gebruikersnaam, e-mail, wachtwoord (min. 12 tekens,
      hoofd-/kleine letters, cijfers en symbolen).
   3. **E-mail** (optioneel) — SMTP kan ook later in het adminpaneel.

   Er worden **nooit** database- of Redis-gegevens gevraagd; die zijn al klaar.

4. **Klaar**

   Na voltooien word je ingelogd en zie je een demo-board. De installer is daarna
   permanent geblokkeerd.

## Controleren of alles draait

```bash
curl -s https://board.voorbeeld.nl/api/health | jq
```

Je ziet `"installed": true` en de status van de services zodra alles gezond is.

## Belangrijke omgevingsvariabelen

| Variabele            | Standaard | Betekenis                                  |
|----------------------|-----------|--------------------------------------------|
| `APP_URL`            | —         | Je domein (schema + host). **Zelf zetten.**|
| `REGISTRATION_OPEN`  | `false`   | Zelfregistratie toestaan.                  |
| `MAX_UPLOAD_SIZE`    | `25`      | Max. uploadgrootte in MB.                  |
| `AUDIT_RETENTION_DAYS` | `365`   | Retentie van het audit-log.                |
| `MAIL_*`             | —         | SMTP (optioneel).                          |

Zie [`.env.example`](../.env.example) voor de volledige lijst.
