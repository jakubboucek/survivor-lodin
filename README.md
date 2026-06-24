# Survivor Lodín

Webová aplikace pro táborovou hru. Veřejná část ukazuje dětem statistiky a pořadí týmů, za
login-wallem mají pořadatelé administraci a interní nástroje. Součástí je i mini odnož –
QR přesměrovávač na vlastní subdoméně.

Tenhle dokument popisuje, jak projekt rozjet lokálně po stažení z gitu. Architektura a konvence
jsou v [CLAUDE.md](CLAUDE.md).

## Co je potřeba

- **Docker** + **Docker Compose** – veškerý běh (PHP 8.5, Apache, MariaDB 10.5) jede v kontejnerech,
  nic se neinstaluje na hostu. `php`, `composer` ani `mysql` nikdy nespouštěj přímo na hostu.
- **Node.js** (LTS, vyzkoušeno na v24) – jen na hostu a jen pokud budeš sahat na frontend
  (build je verzovaný v gitu, takže pro pouhé spuštění appky Node nepotřebuješ).

## Rychlý start

```bash
# 1) Naklonuj repo a nastartuj stack
git clone <repo-url> survivor-lodin
cd survivor-lodin
docker compose up -d

# 2) Nainstaluj PHP závislosti (vendor/ není v gitu)
docker compose exec -w /var/www/html/web web composer install

# 3) Vytvoř runtime adresáře (temp/ a log/ nejsou v gitu)
mkdir -p web/temp web/log

# 4) Vytvoř lokální konfiguraci (POVINNÉ – viz níže)
cp web/config/local.sample.neon web/config/local.neon

# 5) Obstarej a naimportuj databázi (viz sekce Databáze)
```

Aplikace pak běží na:

| Co | URL |
|----|-----|
| Hlavní aplikace | http://localhost:8080 |
| QR přesměrovávač | http://qr.localhost:8080/&lt;kód&gt; |
| Adminer (správa DB) | http://localhost:8088 |

> Subdomény typu `qr.localhost` řeší prohlížeč nativně na loopback – **netřeba zasahovat do
> `/etc/hosts`**. (Chrome i Firefox; Safari `*.localhost` neumí spolehlivě.)

## Konfigurace: `local.neon` je povinný

`web/config/local.neon` se z `Bootstrap.php` **načítá vždy** – bez něj se aplikace nespustí.
Soubor je gitignorovaný (je per-prostředí), proto si ho musíš vytvořit zkopírováním vzoru:

```bash
cp web/config/local.sample.neon web/config/local.neon
```

- **Pro lokální vývoj nemusíš nic měnit** – výchozí hodnoty z `web/config/common.neon`
  (doména `localhost`, DB na devstack kontejneru) stačí.
- **Na produkci** v `local.neon` odkomentuj a nastav `appDomain` (skutečnou doménu) a přepiš
  přihlašovací údaje k databázi.

## Databáze

Struktura DB se vyvíjí **přírůstkovými migracemi** v `migrations/structures/`
(`YYYY-MM-DD-XX-popis.sql`). Migrace se aplikují **ručně**, neběží automaticky.

> **⚠️ Repo neobsahuje kompletní databázi a nebude ji obsahovat.** Migrace jsou jen dílčí změny
> struktury, ne kompletní schéma ani data. **Funkční databázi (data) si musíš obstarat odjinud**
> (od kolegy / z existující instance), naimportovat ji a teprve pak na ni doaplikovat případné
> novější migrace.

Devstack MariaDB: databáze `default`, uživatel `root`, heslo `devstack`.

```bash
# Import dumpu, který sis obstaral:
docker compose exec -T mysqldb mysql -uroot -pdevstack default < dump.sql

# Aplikace jedné migrace:
docker compose exec -T mysqldb mysql -uroot -pdevstack default < migrations/structures/2026-06-23-00-create-qr-code-table.sql

# Interaktivní klient:
docker compose exec mysqldb mysql -uroot -pdevstack default
```

Přístup zvenčí (DB klient na hostu): `127.0.0.1:33060`, nebo přes **Adminer** na
http://localhost:8088 (server `mysqldb`, root / devstack).

## Frontend

Zbuilděné assety (`web/www/assets/`) jsou **verzované v gitu**, takže po klonu appka funguje bez
buildění. Node je potřeba jen při práci na frontendu:

```bash
npm install        # na hostu, z kořene repa
npm run dev        # Vite dev server + HMR
npm run build      # produkční build do web/www/assets/
```

**Po jakékoli změně v `assets/` je nutné spustit `npm run build` a commitnout `web/www/assets/`**
(build je napevno svázaný s verzí v gitu).

## Užitečné příkazy

```bash
docker compose up -d        # nastartuj stack
docker compose down         # zastav stack
docker compose logs -f web  # logy aplikace

# PHP / Composer v kontejneru (working dir = web aplikace):
docker compose exec -w /var/www/html/web web php …
docker compose exec -w /var/www/html/web web composer …
```
