# Security-audit Hallo Invoicing — 2 juli 2026

Scope: volledige codebase (controllers, middleware, models, services, routes, views, config, dependencies). Wat zonder functionele impact kon, is direct gefixt. De rest staat onder "Advies".

## Direct gefixte kwetsbaarheden

### 1. MFA-bypass via /mfa/setup en /mfa/disable (hoog)
Een ingelogde sessie die nog níét MFA-geverifieerd was, kon `/mfa/setup` openen en daar het bestaande TOTP-secret + QR-code aflezen, of via `/mfa/disable` (alleen wachtwoord vereist) MFA volledig uitschakelen. Iemand met alleen een gestolen wachtwoord kon zo MFA omzeilen.
Fix in `MfaController`: setup/confirm weigeren zodra MFA al actief is; disable vereist buiten local een MFA-geverifieerde sessie.

### 2. Geen rate limiting op MFA-codes (hoog)
`/mfa/check` en `/mfa/confirm` waren onbeperkt aan te roepen — een 6-cijferige TOTP-code is dan brute-forcebaar. Fix: `throttle:6,1` op check, confirm en disable. Ook toegevoegd op registratie, wachtwoord-vergeten en de publieke uitnodigingsroutes (token-brute-force).

### 3. Productie-databasedumps in git (hoog)
`goforitsit_invoice.sql(.gz)` en twee `hallo_invoicing_dump_*.sql` stonden in git, terwijl de repo ook een GitHub-remote heeft. Dumps bevatten klantdata en wachtwoordhashes. Fix: u