=== {{APP_NAME}} – app ===
Contributors: {{CONTRIBUTORS}}
Tags: pwa, offline, accessibility, markdown, app
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: {{VERSION}}
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

{{DESCRIPTION}} Installérbar webapp (PWA), der virker offline og med VoiceOver og TalkBack.

== Description ==

Pluginet serverer en færdigbygget, statisk webapp på en adresse under dit websted, fx `https://eksempel.dk/{{BASE_SLUG}}/`.
Brugerne kan installere appen på hjemmeskærmen fra siden "Installér appen", og alt indhold virker uden internet efter første besøg.

* Ingen ændringer i temaet og ingen database-tabeller – kun én indstilling (adressen).
* Korrekte MIME-typer og cache-headers (`no-cache` på HTML og `sw.js`, lang cache på billeder).
* Indstillingsside under Indstillinger → {{APP_NAME}}.
* Kortkoden `[om_teknik_link]` indsætter en knap til appen på en almindelig side.

Kræver HTTPS. Undtag appens adresse (og især `sw.js`) fra sidecache i cache-plugins og CDN.

== Installation ==

1. Plugins → Tilføj nyt → Upload plugin → vælg `om-teknik-app.zip` → Installér nu.
2. Aktivér pluginet.
3. Åbn Indstillinger → {{APP_NAME}} for at se adressen eller ændre den.

== Frequently Asked Questions ==

= Hvordan opdaterer jeg indholdet? =

Byg en ny `om-teknik-app.zip`, og upload den på samme måde. Vælg "Erstat nuværende med uploadet". Brugerne får beskeden "Ny version klar – genindlæs".

= Appen viser 404 =

Gå til Indstillinger → Permalinks og tryk "Gem ændringer" for at genopbygge rewrite-reglerne. Permalinks må ikke stå på "Simpel".

== Changelog ==

= {{VERSION}} =
* Version bygget {{BUILT_AT}}.
