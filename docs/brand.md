# Brand & barevná paleta

Zadání od vedení (závazné). **Kódy barev jsou dané a odstín musí přesně sedět** — u definovaných
barev se používají přesně tyto hex hodnoty, neaproximovat.

## Koncept

Týmy = dvě rovnocenné zvířecí frakce (žádné dobro/zlo):

- 🟢 **HRDINOVÉ = Medvěd** — silný, statečný, ochránce. Lesní zelená + krém.
- 🟡 **PADOUŠI = Sršeň** — rychlý, drzý, bodá. Žlutá + černá. Drsný a „cool", ne směšný.

## Paleta (závazné hex)

| Skupina | Role | Hex |
|---------|------|-----|
| 🟢 Hrdinové | lesní zelená | `#2E7D32` |
| 🟢 Hrdinové | listový akcent | `#4CAF50` |
| 🟡 Padouši | zlatožlutá | `#F4B400` |
| 🟡 Padouši | černá | `#161616` |
| 🪵 Neutrály | dřevo | `#6B4A2B` |
| 🪵 Neutrály | kůra | `#3E2C1C` |
| 🪵 Neutrály | krém | `#EDE3CC` |
| 🪵 Neutrály | uhlová | `#1E1B16` |

## Jak to mapuje na současné téma (analýza)

Současné tokeny (`assets/css/app.css`, `@theme`) vznikly „od oka" z parchment reference. Vztah
k zadání:

| Současný token | Hodnota teď | Brand cíl | Akce |
|----------------|-------------|-----------|------|
| `parchment-*` (pozadí) | `#f1e7c9` | krém `#EDE3CC` | **re-anchor** na přesný krém + odvodit světlejší/tmavší kroky |
| `bark-600` | `#6e4524` | dřevo `#6B4A2B` | sjednotit na přesné dřevo (skoro shodné) |
| `bark-800` | `#382010` | kůra `#3E2C1C` | sjednotit na přesnou kůru |
| `bark-950` | `#160c05` | uhlová `#1E1B16` | sjednotit na uhlovou |
| `jungle-*` (olivová) | `#5f7138` | lesní zelená `#2E7D32` / list `#4CAF50` | **nahradit** olivovou za frakční zelenou Hrdinů |
| `ember-*` (oranžová) | `#c4682b` | — | **konflikt:** brand oranžovou nemá (viz níže) |
| — | — | žlutá `#F4B400` + černá `#161616` | **nové** tokeny frakce Padouchů |

### Doporučená struktura tokenů

- **Neutrály (chrome stránky):** `parchment/cream` (`#EDE3CC` kotva), `wood` (`#6B4A2B`),
  `bark` (`#3E2C1C`), `charcoal` (`#1E1B16`). Text/rámy/pozadí.
- **Frakce Hrdinové:** `hero` (`#2E7D32`), `hero-leaf` (`#4CAF50`).
- **Frakce Padouši:** `villain` (`#F4B400`), `villain-ink` (`#161616`).
- Frakční barvy se používají jen ve frakčním kontextu (karty týmů, řádky pořadí, odznaky),
  ne jako obecný chrome.

## Otevřené rozhodnutí (před implementací)

1. **Akcent `ember` (oranžová) nemá v brandu místo.** Teď ho používají odkazy/CTA. Možnosti:
   - (a) přepnout chrome akcent na neutrál (dřevo) nebo decentní zelenou,
   - (b) použít zlatožlutou `#F4B400` jako obecný akcent (ale to je barva Padouchů → mohlo by to
     vizuálně „nadržovat" jedné frakci).
   - Doporučení: chrome držet neutrální, frakční barvy nechat striktně pro frakce.
2. **Odvozené kroky škál** (parchment-50/200/300, bark-700/900…) se dopočítají kolem závazných
   kotev — kotevní odstíny musí sedět přesně, mezikroky jsou volné.
3. **Admin (daisyUI)** zůstává utilitární/neutrální; brand paleta je pro veřejnou část. Frakční
   barvy se v adminu objeví max. jako odznaky u týmů.
