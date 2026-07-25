# Board License-server

Losstaande admin-app om **pakketten** en **licenties** voor Board te beheren.
Licenties worden Ed25519-getekend, zodat Board ze **offline** kan verifiëren; via
de status-API pikt Board online upgrades en intrekkingen op.

## Snel starten

```bash
cd license-server
cp .env.example .env          # zet in elk geval ADMIN_PASSWORD
docker compose up -d
```

Open **http://localhost:8090/admin** (basic-auth met `ADMIN_USER`/`ADMIN_PASSWORD`).

Bij de eerste start wordt automatisch een Ed25519-sleutelpaar gegenereerd. De
**publieke sleutel** staat bovenaan het adminpaneel — zet die in Board:

```
LICENSE_PUBLIC_KEY=<publieke sleutel>
LICENSE_SERVER_URL=https://licenties.jouwdomein.nl
```

## Wat kan een pakket afdwingen?

- **Numerieke limieten:** max. gebruikers, workspaces, boards (leeg = onbeperkt).
- **Opslaglimiet** (GB).
- **Feature-flags:** `automations`, `api`, `webhooks`, `sso`, `custom_fields`,
  `realtime`, `search`.
- **Verloop + respijt:** vervaldatum per licentie en een respijtperiode (dagen).

## Werkwijze

1. Maak een **pakket** aan met limieten + features.
2. Geef een **licentie** uit voor een klant (kies pakket, vul naam/e-mail en
   optioneel een vervaldatum in).
3. Klik **Sleutel** om de licentiesleutel te kopiëren; de klant plakt die in
   Board onder *Beheer → Licentie*.
4. **Upgraden** kan online: kies een ander pakket bij de licentie → de sleutel
   wordt opnieuw getekend en Board pikt hem bij de volgende `license:refresh` op.
5. **Intrekken** zet de status op `revoked`; Board valt bij de eerstvolgende
   refresh terug op de community-tier.

## API

`GET /api/licenses/{uuid}/status` → `{ "status": "active", "key": "<token>" }`
of `{ "status": "revoked" }`. Board gebruikt dit voor de online controle.

## Sleutelbeheer

- Genereer handmatig een sleutelpaar: `php artisan license:keygen`.
- De **privésleutel** blijft geheim op de licentieserver; deel alleen de
  **publieke sleutel** met Board.
