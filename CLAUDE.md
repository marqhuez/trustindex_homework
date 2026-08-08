# CLAUDE.md

Guidance for working in this repository.

## Stack

- Symfony 7.4, AssetMapper (no Node/npm build step) + Stimulus for JS.
- Tailwind CSS v4 via `symfonycasts/tailwind-bundle`, imported in `assets/styles/app.css` (`@import "tailwindcss"`), compiled by `php bin/console tailwind:build --watch` in dev.
- Stimulus controllers in `assets/controllers/*_controller.js` are auto-registered by name (e.g. `toast_controller.js` → `data-controller="toast"`) — no manual entry needed in `assets/controllers.json`.

## Design system

Tokens are defined once via Tailwind v4's CSS-first `@theme` block in `assets/styles/app.css` — extend that file rather than hardcoding raw hex/font values in templates.

**Color** (semantic names, not Tailwind's default palette):
- `paper` (#F5F7F3) — page background
- `paper-deep` (#E7ECE5) — borders, dividers, disabled/inactive surfaces
- `ink` (#16281F) — primary text
- `ink-soft` (#52604F) — secondary text, mono labels/eyebrows
- `emerald` / `emerald-dark` (#1F6F54 / #14503C) — brand, primary actions, links, success
- `gold` (#C8933C) — rating/star accent only
- `rust` (#B3492D) — errors

Avoid introducing Tailwind's default gray/blue/green palette in new UI — stick to these tokens (`bg-emerald`, `text-ink-soft`, etc.) so color stays consistent site-wide.

**Type:**
- Display (headings): `font-display` → Fraunces (serif, used sparingly for H1/H2)
- Body: `font-body` → Public Sans (default, set on `<body>`)
- Labels/eyebrows/mono data: `font-mono` → IBM Plex Mono, always `text-xs tracking-widest uppercase` for field labels and small eyebrow text (e.g. "New review", "Rating")

Fonts are loaded via Google Fonts `<link>` tags in `templates/base.html.twig` — add new weights/families there if needed, not via `@font-face` in CSS.

**Layout conventions:**
- Content is centered, `max-w-3xl`, generous padding (`px-6 py-16` for page body, `p-8` for cards).
- Cards: `rounded-2xl border border-paper-deep bg-white p-8 shadow-sm shadow-ink/5`.
- Form inputs: `rounded-lg border border-paper-deep bg-paper px-4 py-2.5 text-sm text-ink focus:border-emerald focus:ring-1 focus:ring-emerald focus:outline-none` — keep every input/select/textarea on this exact recipe so they look uniform. Native `<select>` elements need `appearance-none` plus a manually positioned chevron `<svg>` (see `templates/review/index.html.twig`) since browsers style selects differently from text inputs otherwise.
- Primary buttons: `rounded-full bg-emerald px-6 py-2.5 text-sm font-medium text-paper hover:bg-emerald-dark focus:ring-2 focus:ring-emerald focus:ring-offset-2 focus:ring-offset-white`.
- Field labels are mono eyebrows above the input, not inline/floating labels.

**Header:** sticky, `bg-white/90 backdrop-blur`, bottom border `border-paper-deep`, contains a "Home" link back to the main entry point — not a wordmark/logo treatment.

**Toasts:** flash messages render in `base.html.twig` (top-right stack), styled `bg-emerald`/`bg-rust` with a mono "Done"/"Error" eyebrow, auto-dismiss after 3s via `toast_controller.js` (fade + slide before removal). Any new flash category should follow this same pattern rather than introducing new toast markup elsewhere.

**Custom form controls:** when a field needs a richer widget than the Symfony Form default (e.g. the star rating in `ReviewType`/`review/index.html.twig`, built on an `expanded` `ChoiceType` + `rating_controller.js`), keep the underlying native inputs in the DOM (visually hidden with `sr-only`, not `display:none`) so the form still submits and is keyboard/screen-reader accessible — Stimulus only handles the visual layer on top.

## Symfony conventions used here

- Server-owned fields (`id`, `createdAt`, `updatedAt`) are excluded from Form types and set explicitly in the controller/service, never trusted from submitted data.
- Persistence logic (timestamping + `persist`/`flush`) lives in a dedicated service (e.g. `ReviewService`) rather than inline in the controller, so it can be reused by future non-web entry points (API, CLI). The service takes an already-populated, already-validated entity — it doesn't construct or validate it.
- Routes handle both GET and POST on the same action via `$form->handleRequest()`, re-rendering the same template with validation errors on failure rather than splitting GET/POST into separate routes.
