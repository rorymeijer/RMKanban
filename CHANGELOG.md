# Changelog

Alle noemenswaardige wijzigingen aan Board worden hier vastgelegd.
Dit project volgt [Semantic Versioning](https://semver.org/lang/nl/).

Bij elke release staan eventuele **handmatige stappen** vermeld die na
`./update.sh` nodig zijn.

## [Unreleased]

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
