# Changelog

Alle noemenswaardige wijzigingen aan Board worden hier vastgelegd.
Dit project volgt [Semantic Versioning](https://semver.org/lang/nl/).

Bij elke release staan eventuele **handmatige stappen** vermeld die na
`./update.sh` nodig zijn.

## [1.0.0] - 2026-07-25

Eerste complete release: Fase 0 t/m 7. `git clone` → `docker compose up -d` →
Caddy → installer → werkend Kanban-platform.

### Licenties (los, gedockeriseerd systeem)

**Toegevoegd**

- Aparte licentieserver in [`license-server/`](license-server) (Laravel, Docker):
  pakketten en licenties aanmaken, uitgeven, upgraden en intrekken via een
  adminpaneel + status-API.
- Ed25519-getekende licentiesleutels die Board **offline** verifieert; online
  refresh voor upgrades/intrekking met respijtperiode.
- Handhaving in Board van numerieke limieten (gebruikers/workspaces/boards),
  opslag (GB), feature-flags en verloop+respijt. Pakketten zijn vrij samen te
  stellen. `LICENSE_ENFORCE=false` draait onbeperkt.
- Adminpagina in Board om een sleutel in te voeren en online te vernieuwen.
- **Handhaving in de code i.p.v. env/compose:** publieke sleutel + `enforce`
  staan als committed constanten in `config/license.php` (geen `LICENSE_*`-knop
  meer in `docker-compose.yml`/`.env.example`), zodat het geen instelling is die
  de klant even aanpast. Model is **altijd gelicentieerd**: zonder geldige
  licentie draait Board in **geblokkeerde modus** (`EnsureLicensed` → beheerder
  naar de licentiepagina, overige gebruikers naar een "licentie vereist"-scherm).
  Signing voorkomt namaak; de EULA blijft de uiteindelijke bescherming.
- **Licentiesleutel in de web-installer:** een optionele *Licentie*-stap in de
  installatiewizard (alleen bij een gelicentieerde build). De klant voert zijn
  sleutel dus in de UI in — niet in een env-bestand. Leeg laten = community-tier.
  Alleen de publieke verificatiesleutel blijft een vaste productconstante.

### Fase 7 — Productieklaar

**Toegevoegd**

- SSO via OIDC (authorization-code flow met discovery), optioneel naast
  wachtwoordlogin en gated door `OIDC_ENABLED`.
- Prometheus-metrics op `/metrics`, afschermbaar via `METRICS_TOKEN`.
- PWA: installeerbaar (manifest + icoon), service worker met offline-lezen van
  bezochte boards en een nette offline-pagina.
- Playwright end-to-end test voor de kritieke flow (verse start → installer →
  beheerder → board → kaart maken), plus een aparte CI-job.
- Productie-, beveiligings- en performance-documentatie in `docs/productie.md`.

**Handmatige stappen na update:** geen.

### Fase 6 — Automations & API

**Toegevoegd**

- No-code automations (Butler-achtig): triggers, condities en acties
  (toewijzen, labelen, verplaatsen, archiveren, reageren), met per-run logging en
  handmatig opnieuw uitvoeren.
- Uitgaande webhooks met HMAC-SHA256-signing en retries met exponentiële backoff
  (via een queue-job).
- REST API v1 (token-geauthenticeerd via Sanctum) met rate limiting per token en
  een zelf-gehoste OpenAPI 3.1-spec op `/api/docs` (geen CDN).
- Persoonlijke API-tokens met scopes.
- Import van Trello JSON-exports en export van een board als JSON.
- iCal-feed met de deadlines van een board.

**Handmatige stappen na update:** geen.

### Fase 5 — Weergaves & zoeken (backend)

**Toegevoegd**

- Full-text zoeken over kaarten via Laravel Scout/Meilisearch, afgeschermd tot
  boards waartoe de gebruiker toegang heeft.
- "Mijn werk": alle aan de gebruiker toegewezen of door hem aangemaakte kaarten
  over boards heen.
- Kaart-toewijzingen (assignees) met board-toegangscontrole.
- Opgeslagen weergaves (persoonlijk of gedeeld) per board, met view-type en
  filtercriteria.

**Frontend afgerond:** per board omschakelbare tabel-, kalender- en
tijdlijnweergave, plus een command palette (⌘K / Ctrl+K) voor navigeren en zoeken.

**Handmatige stappen na update:** geen.

### Fase 4 — Realtime & notificaties

**Toegevoegd**

- Reverb-broadcast (`CardMoved`) op een presence-channel per board — kaarten
  bewegen live en aanwezigheid is afgeschermd tot board-leden.
- In-app notificaties (database) plus e-mail bij @mentions, per gebruiker
  instelbaar via notificatievoorkeuren.
- Notificatie-endpoints: ophalen, als gelezen markeren, voorkeuren bijwerken.

**Handmatige stappen na update:** geen.

### Fase 3 — Kaartdetails (backend)

**Toegevoegd**

- Reacties op kaarten met @mention-herkenning (koppelt genoemde gebruikers).
- Checklists met items, toewijzing en een voortgangsberekening.
- Labels: aanmaken op boardniveau, koppelen/ontkoppelen aan kaarten (met
  board-validatie).
- Kaartrelaties (`blocks` / `related` / `duplicate`) met zelf-referentie- en
  board-validatie.
- Custom fields (tekst, getal, keuze, datum, checkbox, gebruiker) per board,
  met waarden per kaart in `jsonb` (GIN-index op PostgreSQL).
- Bijlagen-datamodel en markdown-beschrijving op kaarten.

**Frontend afgerond:** kaartdetail-modal (klik op een kaart) met live
markdown-preview, checklists met voortgangsbalk, reacties met @mentions, labels,
deadlines, assignees en kaartrelaties.

**Handmatige stappen na update:** geen.

### Fase 2 — Kern-kanban

**Toegevoegd**

- Boards aanmaken/bewerken/archiveren/herstellen; een nieuw board krijgt meteen
  drie standaardlijsten.
- Lijsten en kaarten aanmaken/bewerken, met WIP-limiet en inklapbare lijsten.
- Verplaatsen van lijsten en kaarten via LexoRank-strings — geen hernummering van
  hele lijsten, ook bij verplaatsen tussen lijsten.
- Drag & drop-board met dnd-kit inclusief volledige toetsenbordbediening en
  optimistische UI; kaarten en lijsten toevoegen vanuit het board.
- Archivering en een prullenbak met herstel; soft-deleted kaarten worden na 30
  dagen definitief verwijderd (`board:prune-trash`, dagelijks gepland).
- Audit-log: elke schrijfactie schrijft een activity-record met actor, diff en
  ip/user-agent.

**Handmatige stappen na update:** geen.

### Fase 1 — Auth & tenancy

**Toegevoegd**

- Rollen (`owner`, `admin`, `member`, `viewer`, `guest`) als enum met hiërarchie,
  plus `WorkspacePolicy` en `BoardPolicy` (globale beheerders omzeilen de policy).
- Effectieve board-rol: expliciet board-lidmaatschap wint, anders erft een
  niet-privé board de workspace-rol; privé-boards vereisen expliciet lidmaatschap.
- Tweestapsverificatie (TOTP, RFC 6238) volledig in pure PHP — geen externe
  afhankelijkheid. In-/uitschakelen, bevestigen, herstelcodes en een
  login-challenge (code of herstelcode).
- Zelfregistratie, standaard dicht, via `REGISTRATION_OPEN` te openen; een nieuwe
  gebruiker krijgt automatisch een eigen workspace.
- Workspace-uitnodigingen (aanmaken, accepteren via token, leden verwijderen).
- Adminpaneel-skelet (`/admin`, alleen voor globale beheerders) met kerncijfers en
  recente gebruikers.

**Handmatige stappen na update:** geen.

### Fase 0 — Infrastructuur & installer

**Toegevoegd**

- Docker-stack met één platte HTTP-poort (8080), zonder eigen reverse proxy of TLS:
  `web` (nginx), `app` (php-fpm), `postgres`, `valkey`, `meilisearch`, `reverb`,
  `queue-worker` en `scheduler`. `postgres` en `valkey` zijn alleen intern bereikbaar.
- Multi-stage Dockerfile (composer- en node-build in aparte stages, slanke non-root
  runtime), healthchecks en `depends_on: condition: service_healthy`, named volumes.
- Gebundelde PostgreSQL met vaste interne credentials; de gebruiker raakt de database
  nooit aan.
- Entrypoint-script dat `APP_KEY`/`APP_SECRET` genereert en persisteert, op de database
  wacht en `migrate --force` draait.
- `EnsureInstalled`-middleware die alle routes naar `/install` stuurt tot er een
  beheerder is, en de installer daarna permanent blokkeert.
- Meerstaps web-installer (toepassing → beheerder → optioneel SMTP) met race-bescherming
  (atomic lock + unieke constraints). Bij voltooien: beheerder + workspace + demo-board,
  automatisch inloggen.
- `GET /api/health` met `installed`-vlag, versie en status van
  database/redis/meilisearch/reverb.
- Kern-datamodel: users, settings, workspaces, workspace_members, boards, board_members,
  lists, cards, labels, activities — met LexoRank-ordening en soft deletes.
- Frontend-fundament: Inertia v2 + React 19 + TypeScript (strict) + Vite, Tailwind v4
  met licht/donker thema, i18n-laag (nl standaard, en), installer- en board-UI.
- Scripts: `update.sh` (met pre-migratie-dump en rollback), `backup.sh` (retentie +
  `--restore`), `console.sh`.
- CI (GitHub Actions): PHPStan level 8, Pest, Pint, ESLint en de Vite-build.
- Caddyfile-snippet in de README; documentatie in `docs/`.

**Handmatige stappen na update:** geen (eerste release).
