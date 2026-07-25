# Licenties

Board kan commercieel gelicentieerd worden met een aparte, gedockeriseerde
**licentieserver** (map [`license-server/`](../license-server)). Licenties zijn
Ed25519-getekend, zodat Board ze **offline** verifieert; online wordt periodiek
op upgrades en intrekking gecontroleerd.

## Hoe het werkt

1. Je draait de licentieserver (`license-server/`) en genereert een sleutelpaar
   (gebeurt automatisch bij de eerste start).
2. Als **leverancier** bak je de **publieke sleutel** in Board in via
   `LICENSE_PUBLIC_KEY` (en zet je `LICENSE_ENFORCE=true` + optioneel
   `LICENSE_SERVER_URL`). Dit is geen geheim en is voor al je klanten hetzelfde.
3. In de licentieserver maak je **pakketten** aan en geef je **licenties** uit.
4. De klant voert zijn **licentiesleutel** in tijdens de **installatie**
   (stap *Licentie*) — of later onder **Beheer → Licentie**. De sleutel komt dus
   nooit in een env-bestand.
5. Board verifieert de sleutel lokaal (offline) en dwingt de limieten en
   features af.

> De klant configureert alles in de web-installer op `domein.ext/install`,
> inclusief de licentiesleutel. Alleen de publieke verificatiesleutel is een
> vaste productconstante die jij als leverancier meelevert.

## Wat een pakket afdwingt

| Type | Voorbeeld |
|------|-----------|
| Numerieke limieten | max. gebruikers, workspaces, boards |
| Opslag | totale uploadopslag (GB) |
| Feature-flags | `automations`, `api`, `webhooks`, `sso`, `custom_fields`, `realtime`, `search` |
| Geldigheid | vervaldatum + respijtperiode (dagen) |

## Modi

- `LICENSE_ENFORCE=false` → alles onbeperkt (open-source/self-hosted zonder
  commerciële limieten).
- `LICENSE_ENFORCE=true` (standaard):
  - **Geldige licentie** → de limieten/features van het pakket.
  - **Geen/ongeldige/verlopen licentie** (voorbij respijt) → de **community-tier**
    (standaard: 3 gebruikers, 1 workspace, 3 boards, alleen `custom_fields`).

## Upgraden & intrekken (online)

- **Upgrade:** wijzig het pakket van een licentie in de licentieserver. De sleutel
  wordt opnieuw getekend; Board pikt hem op via `php artisan license:refresh`
  (dagelijks gepland) of de knop *Online vernieuwen* in het adminpaneel.
- **Intrekken:** zet de licentie op `revoked`. Board valt bij de eerstvolgende
  refresh terug op de community-tier.

## Handhavingspunten in Board

- Gebruikerslimiet: bij registratie en SSO-login.
- Board-limiet: bij het aanmaken van een board.
- Feature-flags: routes voor automations, webhooks en SSO zijn afgeschermd met de
  `feature:`-middleware.

Zie [`license-server/README.md`](../license-server/README.md) voor het draaien en
beheren van de licentieserver.
