# O&M-teknik – tilgængelig webapp fra Markdown

En lille webapp (PWA) bygget ud fra Markdown-filer. Den virker med VoiceOver og TalkBack, kan installeres som et ikon på hjemmeskærmen og virker offline efter første besøg.

Første indhold er **O&M-teknik**, en guide til instruktører i Orientering og Mobility. Opsætningen kan genbruges til andre samlinger: skift indholdet i `content/` og indstillingerne i `site.config.json`.

**Kort fortalt:**

1. Du skriver sider som tekstfiler i `content/`.
2. Kommandoen `npm run plugin` bygger appen og pakker den som `om-teknik-app.zip`.
3. Zip-filen uploades i WordPress som et plugin. Der er ingen FTP og ingen ændringer i temaet.
4. Borgeren åbner adressen eller scanner QR-koden på siden "Installér appen" og lægger appen på hjemmeskærmen.

---

## Indhold

- [Skriv indhold](#skriv-indhold)
- [Byg appen](#byg-appen)
- [Udgiv: tre muligheder](#udgiv-tre-muligheder)
- [Opdatér indholdet](#opdatér-indholdet)
- [Installation på telefonen](#installation-på-telefonen)
- [Tjek installérbarhed](#tjek-installérbarhed)
- [Cache-plugins og CDN](#cache-plugins-og-cdn)
- [Indstillinger (site.config.json)](#indstillinger-siteconfigjson)
- [Tjekliste til manuel test](#tjekliste-til-manuel-test)

---

## Skriv indhold

### Mapper og filnavne bestemmer menuen

```
content/
  index.md                      ← forsiden
  90-om-appen.md                ← side direkte under forsiden
  10-grundteknikker/            ← sektion i menuen
    index.md                    ← intro til sektionen (anbefales – giver titlen)
    10-stokteknik.md
    20-diagonal-teknik.md
    billeder/
      stokteknik-01.jpg
  20-faerden-i-trafik/
    index.md
    10-krydsning.md
    30-offentlig-transport/     ← sektioner kan ligge i sektioner
      index.md
      10-bus.md
```

- **Talpræfikset** (`10-`, `20-` …) styrer rækkefølgen og vises ikke. Brug spring af 10, så du kan indsætte en ny side som `15-…` senere.
- **Mapper** er sektioner. De kan indlejres i flere niveauer.
- **Adressen** laves ud fra filnavnet uden tal: `10-grundteknikker/10-stokteknik.md` bliver til `…/om-teknik/grundteknikker/stokteknik/`. Brug små bogstaver og bindestreger. Æ, ø og å bliver til ae, oe og aa i adressen.
- **Mapper uden `.md`-filer**, fx `billeder/`, bliver ikke vist i menuen.
- Navnene `soeg`, `installer`, `assets`, `media` og `icons` er reserveret af appen.

### Front matter (valgfrit)

Øverst i en fil kan du skrive indstillinger mellem to linjer med `---`:

```markdown
---
title: Krydsning af vej
menu_title: Krydsning
description: At krydse en vej uden lysregulering.
hidden: false
---

# Krydsning af vej

Brødtekst …
```

| Felt | Betydning |
|---|---|
| `title` | Sidens titel. Mangler den, bruges første `# overskrift`, ellers filnavnet. |
| `menu_title` | Kortere navn i menuen og i forrige/næste. |
| `description` | Kort tekst under linket i menuen (vises også til søgemaskiner). |
| `hidden: true` | Siden bygges, men vises ikke i menu, forrige/næste og søgning. |
| `menu: list` / `menu: tiles` | Kun i `index.md`: overstyrer menuvisningen for netop den sektion. |

### Overskrifter

- Første `# overskrift` bliver sidens hovedoverskrift (`<h1>`). Der må kun være én.
- Underoverskrifter skrives `##`, derefter `###`. Spring ikke niveauer over, fx fra `##` til `####`. Skærmlæserbrugere navigerer efter overskrifterne.

### Links mellem sider

Skriv almindelige relative links til `.md`-filen. De omskrives automatisk til de rigtige adresser:

```markdown
Se [Stokteknik](../10-grundteknikker/10-stokteknik.md).
Se [Grundteknikker](../10-grundteknikker/index.md).
```

Skriv linktekster, der giver mening alene: »Se [Lysregulerede kryds](…)« og ikke »klik [her](…)«. Skærmlæserbrugere hører ofte en liste med kun linktekster. Du kan også linke til filer som PDF'er, fx `[Folder (PDF)](filer/folder.pdf)`. De kopieres med i appen.

### Billeder og alt-tekst

```markdown
![En person står ved kantstenen og lytter efter trafikken](billeder/krydsning-01.jpg)

![Hånd på signalknappen](billeder/lyskryds-01.jpg "Pilen på knappen viser gåretningen.")

![dekorativ](billeder/pynt.jpg)
```

- **Alt-teksten** står i `[ ]`. Beskriv det, billedet viser og som er vigtigt for forståelsen, med en kort sætning. Skriv ikke »billede af«.
- **Billedtekst**: tekst i anførselstegn efter filnavnet vises under billedet (`<figure>`/`<figcaption>`). Billedet skal stå alene i sit afsnit, dvs. med en tom linje før og efter.
- **Dekorative billeder** (ren pynt): skriv `dekorativ` som alt-tekst. Så får billedet `alt=""`, og skærmlæseren springer det over.
- Vigtig information må aldrig *kun* stå i et billede. Skriv den også i teksten.
- Læg billederne i en `billeder/`-mappe ved siden af siden. JPEG og PNG i fuld størrelse er fint. Ved build bliver de skaleret (640 og 1280 px), konverteret til WebP med JPEG-fallback og får bredde og højde. Alle billeder undtagen det første på siden indlæses først, når man scroller hen til dem.

### Advarsler ved build

Build stopper ikke ved fejl, men skriver en advarsel ved:

- billeder uden alt-tekst
- manglende billedfiler
- døde links til `.md`-filer
- dublerede talpræfikser i samme mappe (fx to filer med `10-`)
- spring i overskriftsniveauer og mere end én `#`-overskrift
- dublerede sidetitler
- temafarve med for lav kontrast

Ret advarslerne, før du udgiver.

---

## Byg appen

Kræver [Node.js](https://nodejs.org) version 20 eller nyere. Første gang:

```bash
npm install
```

| Kommando | Hvad sker der |
|---|---|
| `npm run dev` | Starter en lokal server på http://localhost:8080/om-teknik/. Siden genindlæses automatisk, når du gemmer en fil. Der bruges ingen offline-cache under udvikling. |
| `npm run build` | Bygger den færdige app i `dist/`. |
| `npm run plugin` | Bygger `dist/` og pakker WordPress-pluginet `om-teknik-app.zip`. |

**Uden Node på din egen computer:** ret filerne direkte på GitHub (blyant-ikonet). Workflowet `.github/workflows/build.yml` bygger så automatisk ved hver ændring på `main`. Du finder den færdige `om-teknik-app.zip` under **Actions → seneste kørsel → Artifacts**.

---

## Udgiv: tre muligheder

Alle tre bygges fra de samme filer. Appen bruger kun relative stier, så den virker i enhver undermappe. Alle tre kræver HTTPS.

### A. WordPress-plugin (anbefalet, hvis appen skal ligge på jeres WordPress)

1. Kør `npm run plugin`, eller hent zip-filen fra GitHub Actions.
2. Gå til WordPress: **Plugins → Tilføj nyt → Upload plugin**, vælg `om-teknik-app.zip`, og tryk **Installér nu**.
3. Tryk **Aktivér**.
4. Appen ligger nu på `https://ditsite.dk/om-teknik/`.
5. Under **Indstillinger → O&M-teknik** kan du se linket, ændre adressen og åbne installationssiden med QR-kode.
6. Vil du have en knap på en almindelig side, så indsæt kortkoden `[om_teknik_link]` eller `[om_teknik_link tekst="Åbn guiden"]`.

Pluginet rører ikke temaet eller andre sider og opretter ingen tabeller. Det gemmer kun én indstilling, nemlig adressen. Permalinks må ikke stå på "Simpel". Giver appen 404, så gå til **Indstillinger → Permalinks** og tryk **Gem ændringer**.

### B. Statisk mappe via SFTP

1. Kør `npm run build`.
2. Upload **indholdet** af `dist/` til en undermappe på webhotellet, fx `public_html/om-teknik/`. Husk den skjulte fil `.htaccess`.
3. WordPress' standard-`.htaccess` serverer rigtige filer og mapper før sine egne regler, så appen virker ved siden af WordPress uden yderligere opsætning.

Den medfølgende `.htaccess` sætter de samme MIME-typer og cache-headers som pluginet (kræver Apache med `mod_headers`). Ligger både mappen og pluginet på samme adresse, vinder mappen.

### C. GitHub Pages (letteste redigering)

1. Gå til **Settings → Pages** på GitHub, og vælg **Source: GitHub Actions**. Bemærk, at Pages på private repositories kræver et betalt GitHub-abonnement.
2. Hver ændring på `main` bliver nu udgivet automatisk på `https://<bruger>.github.io/<repo>/`.

---

## Opdatér indholdet

1. Ret eller tilføj Markdown-filer, og ret eventuelle advarsler fra build.
2. Hæv `version` i `site.config.json`, fx `1.0.0` → `1.1.0`. Så kan du se i WordPress, at det er en ny version.
3. Kør `npm run plugin`, eller hent zip-filen fra GitHub Actions.
4. I WordPress: **Plugins → Tilføj nyt → Upload plugin**, vælg den nye zip, og tryk **Erstat nuværende med uploadet**. Indstillingerne bevares.
5. Næste gang brugerne åbner appen med internet, får de en diskret besked: **»Ny version klar – Genindlæs«**. Den bliver også læst op af skærmlæseren.

Service workerens cache får et versionsnummer ud fra indholdet. Derfor rulles en ny version kun ud, når noget faktisk er ændret.

---

## Installation på telefonen

Appen har en side, **Installér appen**, med trin-for-trin-vejledning til iPhone/iPad, Android og computer. Siden har også en **QR-kode** til appens adresse:

1. Instruktøren åbner "Installér appen" på sin egen telefon eller computer og viser QR-koden.
2. Borgeren scanner koden med kameraet, og appen åbner i browseren.
3. Borgeren følger vejledningen:
   - **iPhone:** Safari → Del (evt. under »Flere«) → Føj til hjemmeskærm → Tilføj.
   - **Android:** knappen »Installér O&M-teknik« eller Chrome-menuen → Installér app.
4. Åbn appen én gang med internet. Så er alt indhold gemt og virker i flytilstand.

---

## Tjek installérbarhed

1. Åbn appens adresse i Chrome eller Edge på en computer.
2. Åbn **DevTools** (F12) → fanen **Application**:
   - **Manifest**: navn, ikoner (192, 512 og maskable), `start_url` og `scope` skal stå uden fejl.
   - **Service workers**: `sw.js` skal stå som *activated and is running*, med scope = appens adresse.
   - **Cache storage**: en cache, der hedder `mdpwa:/om-teknik/:…`, med alle sider og billeder.
3. Sæt **Network → Offline**, og klik rundt. Alt skal virke.
4. Kør **Lighthouse** (fanen Lighthouse) med kategorierne *Accessibility* og *Best practices*.

---

## Cache-plugins og CDN

Bruger sitet et cache-plugin (WP Rocket, W3 Total Cache, LiteSpeed Cache, WP Super Cache …) eller et CDN (Cloudflare m.fl.):

- **Undtag stien `/om-teknik/` fra sidecache**, og især **`/om-teknik/sw.js`**. Bliver `sw.js` cachet, får brugerne aldrig nye versioner.
- Lad være med at minificere eller kombinere appens CSS og JS.
- Pluginet sender `Cache-Control: no-cache` på HTML og `sw.js` og sætter `DONOTCACHEPAGE`. Ikke alle cache-lag respekterer det, så undtag stien manuelt.

---

## Indstillinger (`site.config.json`)

| Felt | Betydning |
|---|---|
| `appName` | Appens fulde navn (titel, header, manifest). |
| `shortName` | Kort navn under ikonet på hjemmeskærmen (maks. ca. 12 tegn). |
| `description` | Beskrivelse til manifest og forside. |
| `version` | Versionsnummer (vises i footer og i WordPress). Hæv det ved hver udgivelse. |
| `basePath` | Standardadresse, fx `/om-teknik/`. Bruges som standard af pluginet og af `npm run dev`. Selve appen bruger relative stier og virker på enhver adresse. |
| `themeColor` | Temafarve (header, links, knapper). Skal have mindst 4.5:1 kontrast mod hvid. Build advarer ellers. |
| `backgroundColor` | Baggrund på opstartsskærmen. |
| `menu` | `"tiles"` (felter) eller `"list"` (liste). Begge er semantisk en liste med links. |
| `icon` | Kildeikon (SVG eller PNG på mindst 512×512). Hold motivet inden for de midterste 80 %. |
| `footer.colophon`, `footer.publisher` | Kolofon og afsender i footeren (Markdown tilladt, fx links). |
| `pages` | Titel og adresse for siderne "Søg" og "Installér appen". |
| `images` | Billedbredder og WebP/JPEG-kvalitet. |

---

## Teknik i korte træk

- `lib/content.mjs` læser `content/` til et træ.
- `lib/build.mjs` renderer Markdown (markdown-it + gray-matter), omskriver links, optimerer billeder (sharp) og genererer HTML, søgeindeks, manifest, ikoner og service worker.
- `assets/`: CSS, JavaScript (søgning, installation, opdateringsbesked, QR), service worker-skabelon, `.htaccess` og ikon.
- `plugin/om-teknik-app/` er pluginets PHP. Byggescriptet lægger appen i pluginets `app/`-mappe.
- Siderne er rigtige statiske HTML-sider, ikke en SPA. Det giver rigtige sideskift for skærmlæsere og virker uden JavaScript. Kun søgning, installationsknap, QR og offline kræver JavaScript.

---

## Tjekliste til manuel test

### VoiceOver på iPhone

- [ ] Første element ved swipe er »Spring til indhold«. Det springer til hovedindholdet.
- [ ] Rotor → **Overskrifter**: én overskrift på niveau 1 pr. side, ingen spring i niveauer.
- [ ] Rotor → **Landmærker**: banner, navigation (Genveje, Brødkrummer, Forrige og næste side), hovedindhold, sidefod.
- [ ] Forsiden og sektioner: menuen annonceres som »liste, X elementer«. Hvert punkt læser titlen som link.
- [ ] Brødkrummer: sidste led læses som »aktuel side«. »Tilbage til …« virker.
- [ ] Forrige/Næste læses som »Forrige side: Titel« / »Næste side: Titel«.
- [ ] Billeder læser alt-teksten. Dekorative billeder springes over. Billedtekster læses.
- [ ] Søg: skriv »stok«, og antallet af resultater annonceres uden at flytte fokus.
- [ ] Installér appen: trinene kan følges, og knapperne Del og Føj til hjemmeskærm kan findes med VoiceOver.
- [ ] Større tekst (Indstillinger → Tilgængelighed → Skærm og tekststørrelse → Større tekst): teksten vokser uden vandret scroll.

### TalkBack på Android

- [ ] Overskrifts- og landmærkenavigation (læsekontrol) virker som ovenfor.
- [ ] Menuen annonceres som liste med antal elementer.
- [ ] Forrige/Næste og brødkrummer giver mening ude af kontekst.
- [ ] Søgning: resultatantal annonceres.
- [ ] Knappen »Installér O&M-teknik« vises og kan aktiveres.
- [ ] Skriftstørrelse på maks. og visningsstørrelse på maks.: ingen vandret scroll, intet afskåret.

### Installation

- [ ] **iPhone (Safari):** Del → Føj til hjemmeskærm. Ikonet vises korrekt, appen åbner uden adresselinje, og »Tilbage« findes på alle sider undtagen forsiden.
- [ ] **Android (Chrome):** installér via knappen eller menuen. Ikonet er pænt beskåret (maskable), og appen åbner i eget vindue.
- [ ] QR-koden på »Installér appen« kan scannes og åbner appens adresse.

### Offline

- [ ] Åbn appen med internet, og vent på beskeden »Appen er gemt på enheden …«.
- [ ] Slå **flytilstand** til, og luk og åbn appen helt.
- [ ] Alle sider, billeder og søgningen virker.
- [ ] Udgiv en ny version, og åbn appen med internet. Beskeden »Ny version klar – Genindlæs« vises, og knappen henter den nye version.

### Visuelt

- [ ] 320 px bredde og 200 % zoom uden vandret scroll.
- [ ] Mørkt tema (systemindstilling) har læsbar kontrast.
- [ ] Synligt fokus ved tastaturnavigation (Tab) på alle links og knapper.
- [ ] Reduceret bevægelse: ingen animationer.
