# savri-wordpress-plugin

WordPress-plugin **Savri Analytics** (v1.0.0) — integritetsvänlig
webbanalys utan cookies (GDPR-vänlig, inget cookie-banner behövs) för
Savri (savri.io). Hela pluginet är en enda PHP-fil,
`savri-analytics.php`, som injicerar Savris trackingscript via `wp_head`
och tillhandahåller en inställningssida under Inställningar → Savri
Analytics (Site ID, API-domän savri.io eller besokskollen.se, samt
valfri utökad tracking: utlänkar, nedladdningar, formulär, scrolldjup).

## Kommandon

Inga build-, test- eller npm/composer-kommandon — ren PHP i en fil.

- Lokal test: kopiera `savri-analytics.php` till
  `wp-content/plugins/savri-analytics/` i en WordPress-installation och
  aktivera.
- Release: zippa filen och publicera som GitHub-release
  (se installationsalternativen i `README.md`).

## Viktigt

- All logik ligger i klassen `Savri_Analytics` i `savri-analytics.php` —
  det finns inga andra källfiler.
- `readme.txt` är WordPress.org-formatet (Stable tag, Tested up to,
  changelog) och måste hållas i synk med versionen i plugin-headern i
  `savri-analytics.php` vid varje release.
- Text domain: `savri-analytics` — använd den i alla `__()`/`_e()`-anrop.

## Nätverkskunskap (vu-skill-catalog)

Innan större ändringar (feed-import, cache/ISR, SEO/indexering, cron, nya routes):
läs `~/projekt/vu-skill-catalog/INDEX.md` — en rad + beskrivning per löst problem
och playbook i nätverket. Öppna bara filer vars beskrivning träffar ärendet.
