# Oscars Dossier Studio

Date: 2026-06-22
Milestone: Control Foundation / Route-Family Systems
Primary owner: Active Lunara theme source
Primary target: `Lunara > Theme Studio`

## Purpose

Give Dalton direct, bounded control over the Oscars route-family rhythm without changing Oscar data, public URLs, rewrite rules, or the active Oscars plugin schema.

This is the first Oscars-facing control proof after Brand Console, Homepage Studio, Reviews Archive Studio, and Review Single Studio. The goal is to move toward total customization freedom while preserving source-controlled, publication-grade guardrails.

## Public Contract

- Add a private `Oscars Dossier Studio` panel inside `Lunara > Theme Studio`.
- Store all values as sanitized theme mods with strict allowed values or numeric bounds.
- Do not add raw CSS textareas.
- Do not change public URLs, post content, Oscar result tables, ceremony write-up tables, rewrites, or plugin database schema.
- Render public CSS from the active theme only, scoped to Oscars route-family shells and existing Academy Awards plugin classes.
- Preserve the shared Lunara typography system.
- Preserve existing Oscars plugin behavior, including ceremony dossier modules, ceremony write-ups, major-race cards, title/person profile files, category pages, and ledger modes.

## MVP Controls

- `lunara_oscars_dossier_preset`
  - `historical-dossier`
  - `ceremony-feature`
  - `compact-ledger`
  - `profile-spotlight`
- `lunara_oscars_dossier_density`
  - `balanced`
  - `dense`
  - `showcase`
- `lunara_oscars_ceremony_rhythm`
  - `balanced`
  - `editorial`
  - `ledger`
- `lunara_oscars_major_race_prominence`
  - `standard`
  - `feature`
  - `compact`
- `lunara_oscars_profile_scale`
  - `standard`
  - `cinematic`
  - `compact`
- `lunara_oscars_writeup_prominence`
  - `inline`
  - `feature`
  - `compact`
- `lunara_oscars_dossier_section_gap`
  - bounded numeric value, 24 to 96
- `lunara_oscars_dossier_card_min`
  - bounded numeric value, 220 to 420

## Preset Intent

- `Historical Dossier`: default premium balance for ceremony/category pages.
- `Ceremony Feature`: gives ceremony editorial modules more first-screen authority.
- `Compact Ledger`: tightens the page when the route needs fast data scanning.
- `Profile Spotlight`: makes title/person profile pages feel more cinematic without breaking ledger utility.

## Admin Preview Contract

The panel should include preview links for:

- `/oscars/ceremony/98/`
- `/oscars/category/best-picture/`
- one title route
- one person route
- `/oscars/`

Preview links may include an admin-only preset query override. Anonymous users must not receive unsaved preview settings.

## Safety Invariants

- No private Theme Studio, nonce, user ID, source-map, or admin metadata appears in public HTML.
- Public CSS is scoped so non-Oscars pages are unaffected.
- Approved ceremony write-ups remain visible when a route has them.
- Major-race, critical-path, winner-circle, title, person, and category modules remain visible.
- No horizontal overflow at 390, 768, or 1280.
- No broken images or empty image wrappers are introduced.

## Test Contract

- Add a failing focused contract before production code.
- PHP lint all changed PHP files.
- Run `git diff --check`.
- Verify the Theme Studio panel renders only for capable admins.
- Verify controls save, persist, and clamp invalid values.
- Verify anonymous preview overrides are ignored.
- Verify admin preview overrides are applied only for capable users.
- Smoke public routes:
  - `/`
  - `/journal/`
  - `/reviews/`
  - `/reviews/sinners-2025/`
  - `/oscars/`
  - `/oscars/ceremony/98/`
  - `/oscars/category/best-picture/`
  - one title route
  - one person route
- Capture visual evidence under `10_VISUAL_EVIDENCE` after deployment verification.
