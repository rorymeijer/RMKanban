# Productie: beveiliging, monitoring & performance

## Achter de proxy

- `APP_URL` bepaalt schema + host; achter Caddy honoreert de app de
  `X-Forwarded-*`-headers (`TRUSTED_PROXIES=*` binnen het interne docker-netwerk).
- Reverb-websockets lopen via hetzelfde domein (paden `/app` en `/apps`).

## Monitoring

- **Health:** `GET /api/health` — `installed`, versie en status van
  database/redis/meilisearch/reverb. HTTP 200 zodra de database bereikbaar is.
- **Metrics:** `GET /metrics` in Prometheus-formaat. Scherm af met `METRICS_TOKEN`
  (dan vereist: `Authorization: Bearer <token>` of `?token=`).
- **Queues:** Laravel Horizon geeft inzicht in de queue (achter authenticatie).

## Beveiliging

- Registratie staat standaard dicht (`REGISTRATION_OPEN=false`).
- Tweestapsverificatie (TOTP) met herstelcodes.
- Rollen en policies schermen workspaces en boards af; globale beheerders hebben
  een expliciete bypass.
- Uitgaande webhooks zijn HMAC-SHA256-gesigneerd (`X-Board-Signature`).
- API-tokens hebben scopes en rate limiting per token.
- Privacy-by-default: geen externe SaaS, geen tracking, geen CDN's. Alle assets
  worden lokaal gebouwd en geserveerd.

## SSO via OIDC (optioneel)

Zet in `.env`:

```
OIDC_ENABLED=true
OIDC_ISSUER=https://idp.voorbeeld.nl
OIDC_CLIENT_ID=...
OIDC_CLIENT_SECRET=...
```

De inlogknop "Inloggen met SSO" verschijnt dan naast wachtwoordlogin. De app
gebruikt de authorization-code flow met discovery (`.well-known`).

## Performance

- PostgreSQL met `jsonb` + GIN-index voor custom fields.
- Indexen op `position`-kolommen (LexoRank) en veelgebruikte filters.
- Redis/Valkey voor cache, sessions en queues.
- OPcache aan met `validate_timestamps=0` in de productie-image.
- Vermijd N+1: relaties worden eager-loaded in de board- en dashboard-queries.
- Frontend: code-splitting per pagina, `immutable` cache-headers op gehashte
  build-assets, PWA-service worker voor offline-lezen.

## PWA

De app is installeerbaar (manifest + service worker). Eerder bezochte boards zijn
offline te lezen; bij geen verbinding verschijnt een nette offline-pagina.
