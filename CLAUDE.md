# CLAUDE.md — werkafspraken voor dit project

## Git commits & pushes

De sandbox kan `.git/index.lock` niet aanraken ("Operation not permitted"),
dus git-operaties via `mcp__workspace__bash` falen.

**Werkwijze:** Claude voert zelf `git-commit-push.command` uit via
computer-use (Finder → dubbelklikken). Niet aan de gebruiker vragen om
het te doen — Piet heeft expliciet gezegd: "dubbelklik het lekker zelf" /
"sla op dat je dat voortaan gewoon zelf doet".

Stappen:

1. Pas `git-commit-push.command` aan met een passende commit-message
   (format: korte titel + bullet-lijst, Nederlands).
2. `request_access` voor Finder als dat nog niet gebeurd is.
3. `open_application` → Finder, `cmd+shift+g`, type pad
   `/Users/pietkoorn/Sites/hallo-invoicing/`, Enter.
4. Dubbelklik `git-commit-push.command` in de lijst.
5. Wacht ~5 seconden, screenshot om "Klaar!" te verifi\u00ebren.
6. Push gaat naar `origin` (Bitbucket) \u00e9n `github` (GitHub mirror).

## Build (Tailwind / Vite)

Idem: bij nieuwe Tailwind-classes `build.command` zelf dubbelklikken via
computer-use. Heeft auto-recovery voor corrupte `node_modules/vite`.

## Stack

- Laravel 12 + Livewire + Tailwind CSS **3.1** (let op: geen `-950`
  shades, die komen pas vanaf 3.3 — gebruik `-900/20` of `-900/40`)
- Alpine.js voor kleine interacties
- PHP via Herd, dev-URL: `http://hallo-invoicing.test`
- Taal: Nederlands in UI, commits, comments

## Dark mode

Expliciet dark-variants opgeven op tekst in gekleurde callouts
(bijv. `text-blue-900 dark:text-blue-100`). Zonder dark-variant erft de
tekst van de parent en wordt vaak onleesbaar.
