# Project "Board" — Projectbrief

> Dit is de originele opdracht, opgeslagen zoals gevraagd. De implementatie
> volgt strikt de fasering (Fase 0 t/m 7). Elke fase eindigt in een draaiende,
> geteste en gedocumenteerde staat.

## Project: "Board" — self-hosted, modern Kanban- en werkbeheerplatform

Bouw een self-hosted alternatief voor Trello/Linear-achtige tools. Werk strikt
in fases. Elke fase eindigt in een draaiende, geteste en gedocumenteerde staat.

Kernprincipe voor de gebruiker: `git clone` → `docker compose up -d` → zijn
bestaande Caddy naar de app wijzen → domein openen → web-installer verschijnt →
alleen beheerdersaccount invullen → klaar. De database is standaard al
geconfigureerd en wordt nooit door de gebruiker aangeraakt.

### 1. Stack (vast)

**Backend**
- PHP 8.4 + Laravel 12, strikte types, PHPStan level 8
- PostgreSQL 17 (jsonb voor custom fields, GIN-indexen)
- Valkey/Redis 7 voor cache, sessions, queues, rate limiting
- Laravel Horizon voor queue-inzicht, Reverb voor websockets
- Meilisearch voor full-text zoeken over kaarten en reacties

**Frontend**
- Inertia v2 + React 19 + TypeScript (strict), Vite 6
- Tailwind v4 + shadcn/ui componenten, Radix primitives
- TanStack Query voor server-state, Zustand voor lokale UI-state
- dnd-kit voor drag & drop (inclusief volledige toetsenbordbediening)
- Framer Motion voor subtiele transities — nooit animaties >200ms
- PWA: installeerbaar, service worker, offline-lezen van bezochte boards

**Uitgangspunten**
- Nederlandstalige UI als standaard (i18n-laag met nl + en, uitbreidbaar)
- Code, database en API in het Engels
- Geen externe SaaS-afhankelijkheden, geen tracking, geen CDN's
- Privacy-by-default

BELANGRIJK — de applicatie brengt GEEN reverse proxy of TLS mee. De container
exposet één platte HTTP-poort (standaard 8080). TLS, domeinnaam en certificaten
regelt de gebruiker in zijn bestaande Caddy.

### 2. Deployment — draaien achter bestaande Caddy

Compose-stack: `app` (php-fpm), `web` (nginx op 8080), `postgres`, `valkey`,
`meilisearch`, `reverb`, `queue-worker`, `scheduler`. postgres en valkey NIET
naar de host gemapt. Multi-stage Dockerfile, non-root. Healthchecks +
`depends_on: condition: service_healthy`. Named volumes.

Database standaard geconfigureerd: vaste interne credentials via env,
entrypoint genereert `APP_KEY`/`APP_SECRET`, wacht op postgres, draait
`migrate --force`. Nooit databasevragen in UI. TrustProxies op docker-range.
Reverb via hetzelfde domein (`/app` + `/apps`). Caddyfile-snippet in README.

Scripts: `update.sh`, `backup.sh`, `console.sh`. Versie uit git tag → VERSION.

### 2b. Web-installer

`EnsureInstalled`-middleware stuurt alles naar `/install` tot beheerder bestaat.
Meerstaps wizard: (1) Toepassing, (2) Beheerder, (3) optioneel SMTP. GEEN
database/redis-velden. Bij voltooien: beheerder + workspace + demo-board +
installatievlag + inloggen. Daarna `/install` geblokkeerd. Health endpoint
rapporteert `installed` + service-status. Race-conditie afvangen.

### 3. Datamodel

users, workspaces, workspace_members, boards, board_members, lists, cards,
labels, card_label, comments, attachments, checklists, checklist_items,
custom_fields, custom_field_values, automations, automation_runs, webhooks,
api_tokens, activities (audit log), notifications, saved_filters,
card_templates, card_links, settings.

- Ordening via LexoRank-strings
- Soft deletes + prullenbak (30 dagen)
- Elke schrijfactie → activity-record (actor, diff, ip/user-agent)

### 4. Functionaliteit

Board & kaarten, weergaves (bord/tabel/kalender/tijdlijn/mijn werk), filters,
realtime (Reverb), rollen (owner/admin/member/viewer/guest), notificaties,
automations (Butler-achtig), REST API + OpenAPI, webhooks, import/export, iCal,
adminpaneel, SSO/OIDC, 2FA, metrics.

### 5. UX

Donker/licht thema, command palette (⌘K), toetsenbord-first, WCAG 2.2 AA,
responsive tot 375px, skeleton-loaders, rustige strakke vormgeving.

### 6. Fasering

- **Fase 0** — Infrastructuur & installer
- **Fase 1** — Auth & tenancy
- **Fase 2** — Kern-kanban
- **Fase 3** — Kaartdetails
- **Fase 4** — Realtime & notificaties
- **Fase 5** — Weergaves & zoeken
- **Fase 6** — Automations & API
- **Fase 7** — Productieklaar

### 7. Kwaliteitseisen

- Pest-tests voor policies, kaartverplaatsing, automations, import/export,
  installer-flow (installed/niet-installed, dubbele-beheerder-race)
- Playwright e2e voor kritieke flow
- Alle migraties reversible
- Changelog per release
- Seeder met demo-board
