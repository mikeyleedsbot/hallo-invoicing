# Security-audit Hallo Invoicing — 2 juli 2026

Scope: Laravel 12 app (facturen/offertes), lokale codebase. Review van auth/autorisatie,
input/output, mass assignment, uploads, PDF-generatie, config/secrets en dependencies.

## Samenvatting

De basis is degelijk: per-user data-isolatie via een global scope (`BelongsToUser`),
MFA (TOTP), account-approval-flow, encrypted secrets/tokens in de database, CSRF/hashing
standaard van Laravel, en Blade dat output automatisch escaped. Dependencies zijn actueel
en zonder bekende kwetsbaarheden.

Ik heb een aantal reële risico's gevonden en de veilige, niet-functionele fixes direct
doorgevoerd. Twee punten vragen jouw beslissing (productie-config en git-historie).

## Direct doorgevoerde fixes (geen functionele impact)

1. **MFA-bypass via `/mfa/setup` gedicht.** Een sessie met alleen een gestolen wachtwoord
   kon de setup-pagina openen en het TOTP-secret/QR opnieuw uitlezen, óók als MFA al actief
   was — daarmee kon MFA effectief omzeild worden. `setup()` en `confirm()` sturen nu door
   naar `mfa.verify` zodra MFA al aanstaat. Opnieuw instellen kan alleen via `disable`
   (wachtwoord + geverifieerde sessie) of een admin-reset.

2. **MFA `disable()` vereist een geverifieerde sessie.** Voorheen kon MFA met alleen het
   wachtwoord worden uitgezet — een bypass gelijkwaardig aan punt 1. Nu is een
   MFA-geverifieerde sessie vereist (in `local` niet afgedwongen, net als de rest van de
   MFA-flow).

3. **Rate limiting toegevoegd** op MFA-code-endpoints (`confirm`, `check`, `disable`:
   6/min), registratie en wachtwoord-reset-aanvraag (6/min), en de publieke
   uitnodigingsroutes (accept 20/min, activate 10/min). Beschermt tegen brute-force van
   TOTP-codes en invite-tokens.

4. **Admin-routes ook op route-niveau afgeschermd.** De admin-controllers hadden inline
   `abort_unless(is_admin)`, maar de routes zelf niet. Er is nu een `RequireAdmin`
   middleware (alias `admin`, was wel geregistreerd maar bestond niet als klasse) en de
   gebruikersbeheer- en e-mailinstellingen-routes draaien er nu doorheen (defense in depth).

5. **Session fixation voorkomen bij invite-activatie.** `session()->regenerate()` na
   `Auth::login()` in `InviteController::activate()`. Ook: afgewezen (`rejected`) accounts
   kunnen niet meer via een oude uitnodigingslink alsnog activeren.

6. **Security headers** op elke web-response (`SecurityHeaders` middleware):
   `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `Referrer-Policy`,
   en een restrictieve `Permissions-Policy`.

7. **PDF-template injectie afgedekt.** `field_positions` (user-input uit de editor) werd
   ongefilterd in inline-CSS van de dompdf-HTML geplaatst (`font-family`, `align`). Nu
   ge-whitelist via `safeFontFamily()`/`safeAlign()`. Voorkomt attribuut-/HTML-injectie in
   de renderstap. De ingevulde tekstwaarden waren al correct ge-escaped met
   `htmlspecialchars`.

8. **Database-dumps uit versiebeheer gehaald.** Vier `.sql`/`.sql.gz` dumps met klantdata
   en wachtwoord-hashes stonden getrackt in git. Uit de index verwijderd (`git rm --cached`,
   bestanden blijven lokaal staan) en `*.sql`/`*.sql.gz` toegevoegd aan `.gitignore`.
   Let op: zie punt B hieronder over de git-historie.

## Vraagt jouw beslissing

**A. Productie-configuratie (`.env`).** Lokaal staat `APP_ENV=local` en `APP_DEBUG=true` —
prima voor development. Zorg dat productie draait met `APP_ENV=production`, `APP_DEBUG=false`
(debug-pagina's lekken anders stacktraces/config), en `SESSION_SECURE_COOKIE=true` +
overweeg `SESSION_ENCRYPT=true`. De MFA-middleware slaat de MFA-check bewust over in
`local` — controleer dat productie niet per ongeluk op `local` draait, anders staat MFA uit.

**B. Git-historie van de dumps.** De dumps zijn nu untracked, maar staan nog in de
git-*historie* (en op de remotes bitbucket + github). Wie de historie kan clonen, heeft de
data nog. Advies: historie herschrijven (`git filter-repo`) of de repo's roteren, en —
belangrijker — de wachtwoord-hashes/секreten die in die dumps zaten als gecompromitteerd
beschouwen. Dit raakt de historie, dus ik heb het niet zelf uitgevoerd.

## Gecontroleerd en in orde

- **SQL-injectie**: geen `whereRaw`/`DB::raw`/string-concatenatie in queries (enige
  `DB::statement` is een migratiescript met statische SQL). Alles via Eloquent/bindings.
- **XSS**: de enige `{!! !!}` in views zijn de MFA-QR (server-gegenereerde SVG) en de
  factuur-footer, die met `nl2br(e(...))` correct ge-escaped is.
- **Mass assignment**: modellen gebruiken `$fillable`; controllers werken met
  `$request->validate()`. `is_admin` wordt nergens uit ongevalideerde input gezet.
- **Data-isolatie**: `BelongsToUser` global scope op alle domeinmodellen; IDOR op
  mailverbindingen expliciet afgevangen (`abort_unless(user_id === Auth::id())`).
- **Uploads**: template-logo/achtergrond gevalideerd (`image|mimes:jpg,jpeg,png|max:5120`),
  privaat opgeslagen en via een auth-route geserveerd — niet publiek.
- **Secrets**: `mfa_secret`, OAuth-secrets, mailtokens en API-key zijn `encrypted` cast en
  in `$hidden`.
- **Dependencies**: `laravel/framework 12.50`, `dompdf 3.1.4`, `google2fa`, `guzzle`,
  `commonmark`, `symfony/http-foundation` — geen openstaande advisories (Packagist security
  API, 2026-07-02). `npm audit`: 0 kwetsbaarheden.

## Verificatie

Draai lokaal `security-check.command` (nieuwe runner): `php -l` op de gewijzigde bestanden,
`composer audit`, `route:list` en de testsuite. PHP/Composer draaien niet in mijn omgeving,
dus dit is nog niet uitgevoerd — even lokaal draaien voor je commit.
