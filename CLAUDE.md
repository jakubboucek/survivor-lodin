# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## O projektu

Webová aplikace pro táborovou hru ("Survival"). Veřejná část zobrazuje dětem statistiky a pořadí
týmů. Za login-wallem mají pořadatelé přístup k administraci a interním nástrojům.

- **Jazyk rozhraní:** celá aplikace je v **češtině** (UI texty, šablony, hlášky).
- **Jazyk kódu:** názvy proměnných, tříd, metod, komentáře v kódu i SQL vždy **anglicky**.
  Code-related sem patří i **CI/workflow soubory, testy a commit messages** — taky **anglicky**
  (včetně názvů kroků/úloh, ty drž jako stručné štítky, ne souvětí).
- **Komunikace v tomto repu (CLAUDE.md, odpovědi):** česky.

## Technologický stack

- **PHP 8.5** — preferuj moderní jazykové konstrukce (typed properties, enums, readonly,
  first-class callable, match, named args, property hooks, `#[\Override]` apod.).
- **Nette Framework** (aplikační framework).
- **MariaDB 10.5** — produkční cíl je 10.5.29; lokální devstack běží na image
  `jakubboucek/lamp-devstack-mysql:10.5`, takže dev i produkce sedí na stejné major verzi.
- **Frontend:** Vite 6 + **Tailwind CSS v4**. Veřejná část má vlastní „parchment" téma (barevné
  tokeny v `assets/css/app.css`), administrace používá **daisyUI v5** (`assets/css/admin.css`).
  Viz sekce *Frontend* a *Vzhled a layouty*.

## Lokální vývoj (Docker)

Vývoj běží výhradně přes docker-compose stack
[docker-lamp-devstack](https://github.com/jakubboucek/docker-lamp-devstack). **Nikdy nespouštěj
`php`, `composer` ani `mysql` přímo na hostu** — vždy přes kontejner služby `web`.

```bash
docker compose up -d          # nastartuje stack (web + mysqldb)
docker compose down           # zastaví stack
```

Aplikace běží na **http://localhost:8080** (port 80 v kontejneru → 8080 na hostu).

### Spouštění příkazů v kontejneru

Celý kořen repa je v kontejneru namountován do `/var/www/html`; webová aplikace tedy leží v
`/var/www/html/web` a CLI tooly mimo hosting v kořeni (`/var/www/html/…`). Příkazy pro webovou
aplikaci pouštěj s working directory `-w /var/www/html/web` (tam je `composer.json` aplikace):

```bash
docker compose exec -w /var/www/html/web web php …       # PHP CLI nad aplikací
docker compose exec -w /var/www/html/web web composer …  # Composer (v image předinstalován)

# CLI tool ležící mimo web/ (např. bin/) – pouštěj z kořene /var/www/html:
docker compose exec -w /var/www/html web php bin/<tool>.php
```

### Databáze

| Přístup            | Host      | Port    |
|--------------------|-----------|---------|
| Z PHP (kontejner)  | `mysqldb` | `3306`  |
| Z hostu (klient)   | `127.0.0.1` | `33060` |

Přihlášení: uživatel `root`, heslo `devstack`, databáze `default`.

```bash
# MySQL klient v kontejneru:
docker compose exec mysqldb mysql -uroot -pdevstack default
```

**Připojení k DB** je v `web/config/common.neon` (`database: dsn: 'mysql:host=mysqldb;dbname=default'`,
dev creds root/devstack). Na hostu je k dispozici i **Adminer na http://localhost:8088**
(server `mysqldb`).

**⚠️ Repo neobsahuje kompletní DB ani dump** a obsahovat nebude — v `/migrations/structures/` jsou
jen přírůstkové změny struktury. Funkční databázi (data) je nutné získat odjinud, naimportovat a
doaplikovat novější migrace.

### Konfigurace a první spuštění

`web/config/local.neon` je **povinný** — `Bootstrap.php` ho načítá **vždy** (ne podmíněně), bez něj
se aplikace nespustí. Je gitignorovaný; vytváří se zkopírováním verzovaného vzoru
`web/config/local.sample.neon`. Slouží k per-prostředí override (typicky DB creds na produkci;
známé domény jsou v `common.neon` jako `knownDomains`). Pro lokální dev stačí defaulty z `common.neon`.

Po čerstvém klonu (detailně v [README.md](README.md)) je potřeba: `composer install` (vendor není
v gitu), `mkdir -p web/temp web/log` (runtime adresáře nejsou v gitu) a `cp web/config/local.sample.neon
web/config/local.neon`.

## Adresářová struktura

**Na webhosting se nahrává pouze adresář `web/`** (jeho document root je `web/www`). Zbytek kořene
repa (CLI tooly, dev infrastruktura) na hosting nepatří, ale je dostupný v dev kontejneru.

```
survivor-lodin/             # kořen repa = celý projekt (mountuje se do /var/www/html)
├── docker-compose.yml      # jen lokální vývoj, na hosting se nenahrává
├── .docker/                # data MariaDB (gitignored), nenahrává se
├── bin/                    # CLI tooly MIMO hosting – spouští se lokálně v Dockeru (příklad)
├── assets/                 # FRONTEND zdroje – mimo hosting, build na hostu
│   ├── main.js + css/app.css     # veřejná část (parchment téma)
│   └── admin.js + css/admin.css  # administrace (daisyUI)
├── node_modules/           # npm závislosti (gitignored) – mimo hosting
├── package.json            # FE závislosti a scripty (npm run dev/build) – mimo hosting
├── vite.config.ts          # konfigurace Vite – mimo hosting
└── web/                    # << TENTO adresář se nahrává na webhosting
    ├── www/                # DOCUMENT ROOT (jediná veřejně přístupná část)
    │   ├── assets/         # Vite BUILD OUTPUT – VERZOVANÝ v gitu (commituje se, viz Frontend)
    │   └── img/            # statické obrázky (logo, pozadí; WEBP + AVIF) – servírují se z /img/
    ├── app/                # Nette aplikace (presentery, model, šablony) – mimo document root
    ├── config/             # NEON konfigurace
    ├── vendor/             # Composer závislosti (gitignored) – mimo document root
    ├── temp/               # cache (gitignored)
    └── log/                # logy (gitignored)
```

**Dvě roviny „co je kde dostupné":**
- **Hosting:** nahrává se jen `web/`, web servíruje pouze `web/www`; `app/`, `config/`, `vendor/`
  leží mimo document root, takže nejsou stažitelné z webu.
- **Dev kontejner:** mountuje se celý kořen, proto jsou v Dockeru dostupné i CLI tooly mimo `web/`
  (kvůli jiné verzi PHP na hostu je chceme spouštět v kontejneru).

Mapování v `docker-compose.yml` (zdroj pravdy, neupravovat): kořen repa (`.`) → `/var/www/html`,
`APACHE_DOCUMENT_ROOT` = `/var/www/html/web/www` (odpovídá `web/www`). Hostingovému `web/` tak
v kontejneru odpovídá `/var/www/html/web`.

## Frontend (Vite / npm)

Frontendový tooling **záměrně leží v kořeni repa, ne ve `web/`** — aby se `node_modules` ani
zdroje nenahrávaly na hosting. Na webhosting jde jen zbuilděný výstup ve `web/www/assets/`.

- **Zdroje:** `assets/`. **Dva entry pointy** (`vite.config.ts`: `entry: ['main.js', 'admin.js']`):
  - `main.js` → `css/app.css` (Tailwind + parchment téma) = **veřejná část**,
  - `admin.js` → `css/admin.css` (Tailwind + daisyUI) = **administrace**.

  Každá oblast má vlastní CSS bundle; sdílené JS závislosti (`nette-forms`) Vite automaticky vytáhne
  do společného chunku. Pozn.: Tailwind se mezi CSS soubory nesdílí (generuje se per-oblast podle
  `@source`).
- **Statické obrázky:** `web/www/img/` (logo, forest pozadí; varianty WEBP + AVIF). Servírují se
  přímo z `/img/...` (mimo Vite pipeline) — v CSS přes `image-set()`, v šablonách přes `<picture>`.
- **Build výstup:** `web/www/assets/` — **záměrně VERZOVANÝ v gitu, není v `.gitignore`**. Důvod:
  na projektu dělá víc lidí a build je napevno svázaný s verzí v gitu, takže při marginální změně
  nemusí nikdo před uploadem buildit. **Po změně čehokoli v `assets/` je proto nutné spustit
  `npm run build` a zbuilděný `web/www/assets/` commitnout** (jinak se rozejde se zdroji).
  `emptyOutDir: true` adresář při každém buildu vyčistí, takže nezůstávají osiřelé hashované
  soubory. Cestu řídí `outDir: '../web/www/assets'` ve `vite.config.ts` (relativně k Vite rootu
  `assets/`).
- **CI kontrola:** `.github/workflows/assets.yml` při push/PR rebuildne frontend (`npm ci` +
  `npm run build`) a **selže, pokud se `web/www/assets/` liší od commitnutého** — pojistka, že
  commitnutý build odpovídá zdrojům.
- **Node běží na HOSTU, ne v kontejneru** — devstack image je LAMP bez Node. Frontend příkazy
  pouštěj na hostu z kořene repa:

  ```bash
  npm install
  npm run dev      # Vite dev server + HMR (vývoj)
  npm run build    # produkční build do web/www/assets/
  ```

- **Napojení na PHP:** Nette Assets v `web/config/common.neon` (`assets: mapping: default:
  path: assets, type: vite`) čte manifest z `web/www/assets/.vite/`. `path: assets` je relativní
  k web rootu (`web/www`), takže **se přesunem zdrojů nemění** — dokud build míří do
  `web/www/assets`, PHP konfigurace zůstává.
- V šablonách se assety vkládají přes `{asset 'main.js'}` (veřejné layouty) resp. `{asset 'admin.js'}`
  (admin layout). `{asset}` vloží i CSS `<link>` napojené na entry.
- **Tailwind scan:** Tailwind nečte Latte, jen hrubě skenuje text souborů a hledá literály tříd.
  Šablony jsou mimo Vite root, proto v CSS musí být `@source "../../web/app/**/*.latte"` (resp. jen
  `Admin/**` v `admin.css`). Skládané názvy tříd (`text-{$x}`) se nedetekují — používej celé názvy
  nebo safelist přes `@source inline(...)`.
- **`tsconfig.json`** (kořen) má `moduleResolution: "bundler"` — bez něj IDE/TS nenajde typy balíčků,
  co exportují typy jen přes `exports` (např. `@tailwindcss/vite`).
- **Odsazení:** 4 mezery (PHP/JS/Latte), 2 mezery NEON/YAML — viz `.editorconfig`.

## Databázové migrace

Jakákoli změna struktury DB (DDL) se zakládá jako **SQL soubor v `/migrations/structures/`**
(adresář v kořeni repa, mimo `web/`).

- **Pojmenování:** `YYYY-MM-DD-XX-popis.sql`
  - `YYYY-MM-DD` — datum vzniku migrace,
  - `XX` — pořadové číslo v rámci dne, od `00` (pro případ více migrací během jednoho dne),
  - `popis` — krátký popis (anglicky, kebab-case).
  - Příklad: `2026-06-23-00-create-teams-table.sql`.
- **Kolace:** všechny tabulky a sloupce **vždy `utf8mb4_unicode_520_ci`** (charset `utf8mb4`).
  V každém `CREATE TABLE` proto `DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_520_ci`.
- **Transformace dat:** pokud změnu nelze rozumně vyjádřit v SQL (typicky transformace dat),
  použij analogicky **PHP soubor** se stejným pojmenováním (`…-popis.php`) ve stejném adresáři.
- **Spouštění:** migrace se na serveru **NEspouštějí automaticky** — vše aplikuje obsluha ručně.

## Členění aplikace, layouty a routování

Aplikace má **tři vizuální režimy (layouty)** v `web/app/Presentation/`:

| Zóna | Layout | Popis |
|------|--------|-------|
| **Intro** (před hrou) | `@cover.latte` | jen HP — forest pozadí (cover) přes celou stránku + vycentrované houpající se logo |
| **Veřejná část** (classic) | `@layout.latte` | výchozí pro ostatní public presentery — parchment téma + hnědý rám |
| **Administrace** | `Admin/@layout.latte` | modul `Admin`, daisyUI, jednoduché horizontální menu |

- Cover se nastaví v `HomePresenter::beforeRender()` přes `setLayout('cover')`; admin layout se
  aplikuje automaticky (leží v adresáři modulu `Admin/`); jinak platí výchozí `@layout.latte`.
- **Presentery** (mapping `App\Presentation\*\**Presenter`): `Home` (intro), `Teams` (ukázkové
  pořadí) = veřejná část; `Admin\Dashboard`, `Admin\QrCodes` (extends `Admin\BasePresenter` —
  připravené místo pro budoucí login); `Redirect` = QR přesměrovávač.

### Routování a subdomény (`App\Core\RouterFactory`)

Router rozlišuje dvě odnože **podle subdomény**. Aktuální základní doménu určuje
`App\Core\DomainProvider` z hostu requestu proti seznamu **`%knownDomains%`** (v `common.neon`,
např. `localhost`, `lodin.fun`, `localhost.bukajuv.net`): když host končí (na hranici labelu)
některou známou doménou, vrátí ji **bez subdomény** (`qr.lodin.fun` → `lodin.fun`,
`qr.localhost` → `localhost`); jinak vrátí host beze změny (subdomény pak nefungují, ale app jede).
Tím detekce funguje napříč prostředími bez per-env přepínání jediné domény.

- **`qr.<doména>`** → modul `Redirect`, routa `<code>` (mini odnož = QR přesměrovávač).
- **`<doména>`** (holá) → `admin[/...]` (modul `Admin`) + public catch-all
  `[<presenter>[/<action>[/<id>]]]` → `Home:default`.

Router (singleton, ale per-request) staví routy přes `withDomain()` s relativními maskami
(zachování portu na devu). Na devu fungují subdomény přes **`*.localhost`** — prohlížeč je řeší na
loopback nativně, **bez zásahu do `/etc/hosts`** (Chrome/Firefox; Safari `*.localhost` neumí). Tedy
`qr.localhost:8080/<kód>`, admin `localhost:8080/admin`.

### QR přesměrovávač

`Redirect:default(code)` vyhledá cíl přes `App\Model\QrCodeRepository` (tabulka `qr_code`) a udělá
**302 (dočasné!) přesměrování** — cíl lze v adminu přesměrovat, 301 by se zacachovalo natrvalo.
Správu kódů má `Admin\QrCodes`.

## Vzhled a layouty (Tailwind v4 + daisyUI)

- **Veřejná část** — „parchment / treasure-map" téma. Barevné tokeny v `assets/css/app.css` přes
  `@theme`: `parchment-*` (světle žluté pozadí), `bark-*` (hnědé: text/rámy), `ember-*` (akcent
  „pochodeň"), `jungle-*`. Hnědý rám kolem stránky řeší třída `.page-frame` (zatím CSS vignette
  placeholder — bude nahrazen `border-image` z dodaného PNG). Forest cover je třída `.forest-cover`.
- **Administrace** — **daisyUI v5**, jen neutrální `light` téma (`@plugin "daisyui"` v `admin.css`),
  utilitární vzhled bez barviček (`btn`, `table`, `navbar`, `menu`, `badge`…). `<html data-theme="light">`.
- Konkrétní prvky veřejné části dodává uživatel jako PNG (napojí se průběžně).

## Testování webu

Webovou část testuj přes **chrome-devtools-mcp proti `http://localhost:8080`** — ne přes čisté
curl, ať se ověří i klientské chování a Tracy výstup (viz skill `nette:tracy-debugging`,
Tracy mirroruje výstup do konzole, čti přes `list_console_messages`).

Používej **chrome-devtools-mcp** (vlastní izolovaná instance) — neovlivní cookies/přihlášení
uživatele v jeho prohlížeči. Rozšíření „Claude in Chrome" jen na výslovné vyžádání.

**Debugging:** při chybě čti **horní výjimku** v Tracy BlueScreen (přes `list_console_messages`),
ne grepem na tipované řetězce — snadno trefíš druhotný/zavádějící řádek. Pozor: v debug módu se
**`BadRequestException` (404) navenek vrací jako HTTP 500** (BlueScreen); v produkci je to korektní
404 přes `Error4xx`.

## Konvence pro Claude

- Dodržuj odlišení jazyků: **UI česky, kód anglicky** (viz výše).
- Tento `CLAUDE.md` udržuj aktuální — **všechny důležité poznatky o kódu/projektu zapisuj sem**
  (nebo do `docs/` linkovaných odsud), nikdy ne do osobní paměti.
