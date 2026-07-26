# Licenties

rmboard kan commercieel gelicentieerd worden met een aparte, gedockeriseerde
**licentieserver** (map [`license-server/`](../license-server)). Licenties zijn
Ed25519-getekend, zodat rmboard ze **offline** verifieert; online wordt periodiek
op upgrades en intrekking gecontroleerd.

## Hoe het werkt

1. Je draait de licentieserver (`license-server/`) en genereert een sleutelpaar
   (gebeurt automatisch bij de eerste start).
2. Als **leverancier** bak je de **publieke sleutel** (en optioneel de
   server-URL) in de broncode in via `config/license.php` — niet via env/compose.
   Dit is geen geheim en voor al je klanten hetzelfde; met alleen de publieke
   sleutel kan niemand een geldige licentie namaken.
3. In de licentieserver maak je **pakketten** aan en geef je **licenties** uit.
4. De klant voert zijn **licentiesleutel** in tijdens de **installatie**
   (stap *Licentie*) — of later onder **Beheer → Licentie**. De sleutel komt dus
   nooit in een env-bestand.
5. rmboard verifieert de sleutel lokaal (offline) en dwingt de limieten en
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

## Model: altijd gelicentieerd

rmboard is standaard **altijd gelicentieerd** en handhaving zit **in de code**
(`config/license.php`), bewust niet als env-/compose-knop:

- **Geldige licentie** → de limieten/features van het pakket.
- **Geen/ongeldige/verlopen licentie** (voorbij respijt) → **geblokkeerde modus**:
  de `EnsureLicensed`-middleware stuurt beheerders naar *Beheer → Licentie* om een
  sleutel in te voeren, en overige gebruikers naar een "licentie vereist"-scherm.

Waarom in de code en niet in compose? Zo is het geen instelling die de klant
even aanpast. Voor **lokale ontwikkeling** kun je `LICENSE_ENFORCE=false` in je
eigen `.env` zetten.

> **Eerlijk over de grens:** bij broncode-distributie draait de code op de
> hardware van de klant en is elke check technisch aan te passen. Ed25519-signing
> voorkomt dat iemand een **geldige licentie namaakt** (geen privésleutel), maar
> niet dat iemand de broncode patcht. De **licentieovereenkomst/EULA** is daarom
> de uiteindelijke, juridisch afdwingbare bescherming; deze opzet maakt omzeilen
> een bewuste inbreuk in plaats van één regel in een env-bestand. Wil je de
> drempel verder verhogen, distribueer dan een **prebuilt image** in plaats van
> broncode.

## Upgraden & intrekken (online)

- **Upgrade:** wijzig het pakket van een licentie in de licentieserver. De sleutel
  wordt opnieuw getekend; rmboard pikt hem op via `php artisan license:refresh`
  (dagelijks gepland) of de knop *Online vernieuwen* in het adminpaneel.
- **Intrekken:** zet de licentie op `revoked`. rmboard valt bij de eerstvolgende
  refresh terug op de community-tier.

## Handhavingspunten in rmboard

- Gebruikerslimiet: bij registratie en SSO-login.
- Board-limiet: bij het aanmaken van een board.
- Feature-flags: routes voor automations, webhooks en SSO zijn afgeschermd met de
  `feature:`-middleware.

Zie [`license-server/README.md`](../license-server/README.md) voor het draaien en
beheren van de licentieserver.
