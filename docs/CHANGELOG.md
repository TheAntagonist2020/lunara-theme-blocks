# Lunara Film — Engineering Changelog & Handoff

**Scope:** all six `theantagonist2020` repositories behind lunarafilm.com.
**Period covered:** 2026-07-01 → 2026-07-05 (the "Design Spec 2.0" era).
**Audience:** any engineer or AI session picking this project up cold.

This is a chronological record of what shipped, why, and what's still open —
written so a new hand can get productive in one read instead of re-deriving
decisions from commit messages. Version numbers and dates below are pulled
directly from each repo's `git log`, not reconstructed from memory.

---

## 1. Repository map

| Repo | Purpose | Version at end of period |
|---|---|---|
| `lunara-theme-blocks` | The WordPress child theme. Nearly all front-end logic, all Control Desk admin UI, homepage composition, entity dossier templates. | **3.1.53** |
| `lunara-plugin-oscars-ledger` | The Academy Awards database: master table, reporting/derivation tables, admin UI, entity graph builder. | **2.7.73** |
| `lunara-plugin-core` | Shared content models: Reviews CPT, the entity graph (`movie`/`person`/`ledger_entry`), Debrief/Trinity ACF fields, Essay Builder fields. | **0.4.1** |
| `lunara-plugin-imdb-guard` | IMDb/TMDB validation, poster/backdrop sync, local batch poster-import toolkit. | **0.4.0** |
| `lunara-plugin-dispatch` | Journal/dispatch content tooling. | 3.0.15 (untouched this period) |
| `lunara-plugin-ai-assistant-classic` | AI assistant provider integration. | 0.6.0 (untouched this period) |

**Live site:** lunarafilm.com is WordPress.com Atomic with GitHub Deployments.
Dalton Johnson (site owner) deploys each repo's `main` branch manually after
merge — a merged PR is not automatically live. Always confirm deploy status
before assuming a fix is visible to readers.

**Branch convention:** all work in this period happened on
`claude/tmdb-key-rotation-9hsb3b` in every repo, merged to `main` via PR,
generally same-day.

---

## 2. Timeline

### Era 0 — Pre-existing state (before 2026-07-01)
Theme was on an older 3.1.x line; Oscars plugin had the master
`wp_academy_awards` table (Dalton's own uploaded data, ground truth) but no
reporting/derivation layer beyond basic display. No entity graph existed.
Homepage was Customizer/registry-rendered only. No live search, no Essay
Builder, no Hero Command.

### Era 1 — Oscars portal + hero stabilization (2026-07-01 → 07-02)
*Theme 3.1.17 → 3.1.21 · Oscars 2.7.61 → 2.7.66 · IMDb Guard 0.3.0*

- Oscars portal landing page redesigned: carousels, review schema, social
  cards, newsletter capture (theme 3.1.17).
- Portal cinematic layer moved to a critical-path shell block for LCP
  (3.1.18); Reviews page refit with a pinnable lead review (3.1.19);
  Pairing Desk homepage showcase introduced and hardened through several
  passes (3.1.21–3.1.23).
- **Atmosphere V1** shipped (3.1.24): grain, dolly, reduced-motion handling —
  the first deliberate motion-design layer. Still Gallery shot reel (3.1.25),
  reveal-system reconciliation (3.1.26), Pairing Desk copy made editable from
  Control Desk (3.1.27).
- Hero carousel instability surfaced and was chased across three releases
  (3.1.28–3.1.30: deck preload, Atmosphere v2, fail-open mount) before the
  **root cause** was found in 3.1.32: the hero's fade CSS was written for
  Splide v3 semantics but the site runs Splide v4, which fades via
  `translateX` stacking, not `opacity`/`display`. Slides were rendering but
  invisible. Fixed at the CSS layer, not by working around symptoms.
- Oscars side: guide-text repair, local-first imagery so poster/backdrop
  delivery doesn't depend on live TMDB calls, dossier hero posters
  (2.7.61); ceremony and category dossier premium passes (2.7.62–2.7.64);
  poster importer + Portrait Queue unstuck (2.7.65); legacy migration UI
  retired and non-public blocks hidden from the inserter (2.7.66).
- IMDb Guard 0.3.0: one-click "Fill Missing Posters" batch backfill.

### Era 2 — Design Spec 2.0, sprints 1–2 (2026-07-03 → 07-04 early)
*Theme 3.1.31 → 3.1.38*

Dalton supplied `LUNARA_FILM_DESIGN_SPEC 2.0` (19 numbered sections) as the
governing design document for the rest of this period. Work from here
forward is tracked against that spec.

- 3.1.31: block palette cleanup across theme + Oscars + Dispatch (unused
  Gutenberg blocks hidden from the inserter).
- 3.1.32–3.1.34: the hero fade fix (above) plus hero recomposition, Pairing
  Desk archive styling, and an "exclusivity claim" line making Pairing
  Desk's uniqueness legible to first-time visitors.
- **Sprint 1** (3.1.35, §5/§10): star-rating cascade animation, magnetic
  cursor-pull interactions on priority surfaces (fine-pointer only,
  reduced-motion off).
- **Sprint 2** (3.1.36, §2/§7): the Monolith letterbox effect (scroll-driven
  cinematic bars using native CSS scroll-timelines, not GSAP — survives
  script failure) and the "Method" footer curtain reveal.
- GSAP layer proper (3.1.37, §5): character-rise heading animation via
  SplitText, Trinity card fan-out — the two effects that actually needed
  GSAP over native CSS.
- 3.1.38: GSAP asset paths fixed for WP.com's deployer (which excludes
  `vendor/` directories — GSAP moved to `assets/lib/gsap/`), Latest Reviews
  homepage section restored after being silently dropped.

### Era 3 — The entity graph is born (2026-07-04)
*Theme 3.1.39–3.1.43 · Oscars 2.7.67–2.7.72 · Core 0.2.0*

This is the highest-leverage work of the period: turning the Oscars master
table into a real, queryable knowledge graph instead of a flat spreadsheet.

- **Core 0.2.0**: registers `movie`, `person`, `ledger_entry` as first-class
  post types with full ACF schema (release year, studio taxonomy, relational
  directors/cast, runtime, TMDB backdrop, Where-to-Watch repeater per
  spec §4A). Models only — nothing populated yet.
- **Oscars 2.7.67**: the Entity Graph Builder — a resumable, batched
  admin-driven process that derives `movie`/`person`/`ledger_entry` content
  from the master table. Handles tens of thousands of rows via AJAX
  self-looping + chained cron fallback.
- **2.7.68**: accuracy pass. Two real bugs fixed: (a) naive substring
  category matching had misclassified some ART DIRECTION credits as
  DIRECTING — replaced with AMPAS `award_class` + word-boundary regex; (b)
  release years were off by one because the code subtracted 1 from a field
  that already held the correct year.
- **2.7.69 "The Living Graph"**: integrity audit with one-click heal,
  auto-resync on data import, and a daily heartbeat cron that self-heals
  drift between master/reporting/graph without manual intervention.
- **2.7.70–2.7.71**: Winner Flag Backfill — the bundled dataset
  (`data/oscars.csv`) is winner-complete even where the live master table
  isn't; a format-immune matching key (ceremony + normalized category +
  sorted person/film token set) finds and flags the missing winners
  regardless of how differently the two datasets format the same row.
- **2.7.72 Name Repair**: U+FFFD mojibake in imported names (e.g.
  "Chloé Zhao" corrupted to "Chlo?-Zhao") healed by resolving the correct
  name via TMDB's `/find` endpoint against the IMDb bridge ID, then
  byte-exact `REPLACE()` across graph posts, `aat_entities`, and the master
  table.
- **Theme 3.1.39 "Phase 2B"**: the graph gets front-end surfaces — `/film/`
  and `/talent/` dossier templates, archive index pages, JSON-LD (§11/§15).
  3.1.40–3.1.42: archive page-size enforcement (the first attempt used
  `pre_get_posts` priority 99, which ordered correctly but didn't override
  page size — fixed with `PHP_INT_MAX` + explicit `posts_per_archive_page`),
  WP Rocket self-purge on every theme version bump (a real problem: Rocket's
  Remove Unused CSS was serving stale used-CSS after deploys, explaining
  several "it looks different for me" reports).
- **3.1.43**: Oscar award-history ordering flipped to nominations-first,
  win as the finale, per Dalton's direct request.

### Era 4 — Relational Trinity, Hero Command, Phase 4 (2026-07-05 morning)
*Theme 3.1.44–3.1.46 · Core 0.3.0*

- **Core 0.3.0 + Theme 3.1.44**: the Debrief "Pair It With" trinity (Theme
  Echo / Counter-Program / Career Context, spec §13) upgraded from free-text
  fields to real ACF relationship pickers pointing at `movie` entities. The
  renderer tries the relational field first and falls back to the legacy
  text-parsing path when unset — so existing reviews keep rendering
  identically until an editor links a film.
- **Theme 3.1.45 "Hero Command"**: not in the original spec — added because
  Dalton explicitly asked for "total granular control" of the homepage hero
  after finding the overlay/truncation behavior too rigid. An admin-curated,
  ordered slide deck (any post type, any order, any count) with a global +
  per-slide overlay-intensity dial, replacing the old single-checkbox
  "feature this post" workflow as the primary curation surface.
- **Theme 3.1.46 "Phase 4 cutover"**: the entity graph stops being an
  island. Reviews grow a "Film Dossier" rail card linking to the matching
  `/film/` page (matched via the review's IMDb ID against the movie
  entity's IMDb ID — bidirectional bridge); footer gains Film Index /
  Talent Index links, gated on the entity post types actually being
  registered.

### Era 5 — Derivation integrity, the last two winners (2026-07-05)
*Oscars 2.7.73*

- Two documentary winners (*The True Glory*, ceremony 18; *First Steps*,
  ceremony 20) were flagged `winner=1` in master but produced no row in the
  derived facts table — found via the integrity audit's `lost_winner_rows`
  query. **Root cause:** slug-style local-name entity IDs
  (`lnm-the-governments-of-great-britain`, 36 chars) exceeded the reporting
  tables' `varchar(32)` entity-ID columns; strict-mode MySQL silently
  rejected the whole insert.
- **Fix, three layers deep** so this class of bug can't recur silently:
  (1) a versioned schema migration widens all six entity-ID columns to
  `varchar(64)`; (2) every derivation insert is now wrapped in a guard that
  clamps to column width, retries once with invalid UTF-8 stripped, and
  logs (table + row + SQL error) anything that still fails; (3) the
  integrity audit gained a **full derivation census** — every master row
  must produce a facts row, winner or not, not just a winner-only check
  (which had been hiding non-winner losses of the same kind).
- Result after this shipped and the builder re-ran: facts winners
  3,515/3,515, zero rows lost in derivation.

### Era 6 — Design Spec closeout: search, essays, blocks, GEO (2026-07-05 daytime)
*Theme 3.1.47–3.1.51 · Core 0.4.0*

- **3.1.47**: Essay Builder front-end renderer (prose, pull-quote, inset
  frame, video spread, cinematic banner — spec §12) paired with **Core
  0.4.0**'s ACF Flexible Content field registration (capped at 20 modules
  per §19A). Also formalized the spec's §18 fluid-clamp type tokens.
- **3.1.48**: Live Global Search — one public REST endpoint
  (`lunara/v1/search`) sweeping reviews, journal, films, and talent in a
  single query, surfaced through a ⌘K/`/`-triggered command-palette
  overlay (spec §6/§9). JS-off degrades to the normal `/?s=` results page.
- **3.1.49**: GSAP motion extended to the surfaces it hadn't reached yet —
  entity dossier headings, index grids (batched `ScrollTrigger.batch` so
  long grids don't all animate at once), award-record rows, and Essay
  Builder modules (with a slow scale-settle on the cinematic banner).
- **3.1.50 "Hybrid homepage composition"**: resolved Dalton's "I want
  granular control and this doesn't feel like it" complaint about the
  homepage editor showing shortcode-like content instead of real blocks.
  The Home page's Gutenberg blocks now ARE the homepage — reorder by
  dragging, hide/show by delete/insert, every block server-side-rendered
  live in the editor. `front-page.php` forks on whether the front page
  contains any Lunara section block: if yes, blocks render; if no, the old
  Customizer-registry path runs untouched (the built-in rollback). Homepage
  Studio's publication-package presets still work — applying one now
  rewrites the block list to match instead of writing theme_mods only.
  The hero block's render callback was also corrected to call the
  Hero-Command-aware carousel renderer instead of the old static hero.
- **3.1.51**: closed three spec items in one release — JSON-LD `@graph`
  consolidation with stable `@id`s so a Review's `itemReviewed` and its
  film dossier are the same graph node (§11/§15); `/llms.txt` plus
  per-nomination machine-readable award strings for AI-search retrieval
  (§16 GEO); progressive `@view-transition` cross-fades on same-origin
  navigation (§17). A pre-ship unit test caught a real ordinal bug
  ("93th" instead of "93rd") before it shipped.
- **Core 0.4.1**: pure label change. The `movie` CPT surfaced in wp-admin
  as "Movies," which read as duplicate/competing content next to the
  Reviews menu — renamed to "Film Dossiers" (and `person` → "Talent") to
  make the distinction between *your writing* (Reviews) and *the reference
  graph* (Film Dossiers/Talent) legible in the admin UI. No functional
  change — CPT keys, slugs (`/film/`, `/talent/`), and ACF locations are
  untouched.
- **IMDb Guard 0.4.0**: the local TMDB poster-batch PowerShell toolkit
  (previously living only on Dalton's machine, with a hardcoded API key)
  moved into version control with the key removed from every file
  (resolved from an env var, a git-ignored local file, or an interactive
  prompt — never committed) and a one-click manifest exporter added to the
  plugin's admin screen.

### Era 7 — The masthead flash (2026-07-05 evening)
*Theme 3.1.52–3.1.53*

Reader-reported: the homepage's LUNARA FILM wordmark rendered giant and
centered for roughly half a second on load, then snapped down into its
real two-column layout. Diagnosed in two passes, both confirmed against a
screen recording and (for the second) a live browser probe measuring the
logo element's bounding box every animation frame:

- **3.1.52**: gave the logo `<img>` its settled width/height as an inline
  style attribute (via CSS custom properties so responsive breakpoints
  still override it) so no caching/optimization layer could defer it, and
  excluded the front-door style block from WP Rocket's Remove Unused CSS
  pipeline. This fixed the "giant" half of the symptom but not the "snap."
- **3.1.53**: found the real cause — the masthead's two-column
  `grid-template-columns` rule lived in a function literally named
  `..._first_viewport_polish_css` but hooked to `wp_footer` at priority
  135. The browser painted the entire first viewport single-column and
  only reflowed once the parser reached the bottom of a ~500KB document.
  Moved the hook to `wp_head` (verified the CSS has no rendered-content
  dependency that required footer timing) and added it to the same Rocket
  exclusion list. Live probe after 3.1.52 measured a real 475ms single→
  two-column reflow; re-verification of 3.1.53 once deployed is the open
  item (see §4 below).

---

## 3. Current state (as of last commit in this period)

- **Design Spec 2.0 completion: 14 of 19 sections fully shipped, 5 in
  documented polish** (clamp-token migration across legacy call sites,
  mobile HUD formal audit, homepage rhythm coherence pass, and two backlog
  items — Atmosphere v3, content-ops tooling — that were never numbered
  spec sections to begin with).
- The Oscars data is canonically correct: master table winner-complete at
  3,515 rows, facts table matching exactly, 5,249 `movie` posts / 8,427
  `person` posts / 12,118 `ledger_entry` posts, daily self-healing
  integrity heartbeat active.
- All shortcode-stored page content was migrated to native block markup
  (`academy_awards` → `academy-awards/database`, `lunara_awards_tracker_v2`
  → `academy-awards/tracker-v2`, `lunara_reviews` → `lunara/reviews`) via
  direct content edits — no code change required, since the target blocks
  already existed and simply weren't being used by those pages.
- Homepage composition is hybrid: blocks own the page, Studio packages
  write through to the same blocks.

## 4. Open items / next up

1. **Verify 3.1.53 live.** A live browser probe (Playwright against the
   real homepage, sampling the masthead logo's bounding box per animation
   frame) was built and run once against 3.1.52's fix — it caught the
   footer-hook bug. The same probe needs to run again once 3.1.53 is
   deployed; expected result is a single unchanging geometry sample.
2. **Clamp-token migration.** §8/§18's fluid-type tokens
   (`--lunara-display-text`, `--lunara-header-text`, `--lunara-body-text`)
   exist and are used by new surfaces (Essay Builder); legacy `font-size`
   declarations elsewhere in `style.css` haven't been migrated onto them
   yet. Do this in 2–3 bounded passes with before/after screenshots at
   375/768/1440px — not one big sweep — so any regression is attributable.
3. **Mobile HUD formal audit** (§9/§14): a scripted 390px sweep across all
   template types checking `touch-action`/`overscroll-behavior` on every
   horizontal rail and tap-target sizing. Not started.
4. **Homepage coherence pass**: one deliberate read of the fully-composed
   front door at three widths for rhythm/spacing/duplicate-signal issues.
   Not started.
5. **Atmosphere v3** (not a numbered spec item, a standing backlog entry):
   Tracking Shot, Dust Motes, Hero Breath — the remaining motion-design
   pieces from the original Atmosphere roadmap.
6. **Content-ops tooling** (recommended, not requested): a Trinity backfill
   screen to bulk-convert legacy Pair-It-With text into the new relational
   picker fields, and an auto-grow-on-publish hook so a review with an
   unrecognized IMDb ID spawns its own `movie` entity instead of waiting
   for the next full graph rebuild.
7. **Reliability tooling + the Blocksy exit** (recommended, discussed with
   Dalton, not started): a deploy-truth panel in Control Desk showing
   GitHub `main` version vs. live version per repo; CI lint/smoke gates;
   and — as a larger, explicitly staged project — building a Lunara-owned
   header/nav to replace the last real Blocksy dependency, ending on a
   one-line `Template:` removal from `style.css` (safe because theme_mods
   are keyed to the stylesheet directory name, not the parent).

## 5. Standing operational notes

- **Security**: the TMDB API key that was hardcoded in Dalton's local
  `.bat` launchers (now fixed in `imdb-guard` 0.4.0 — key never committed)
  should be rotated at themoviedb.org and re-entered in IMDb Guard's
  settings screen. Unconfirmed whether this has been done.
- **Deploys are manual.** A merged PR is not live until Dalton deploys that
  repo's `main` via WP.com's GitHub Deployments. Do not assume a fix is
  visible to readers from a merge notification alone — check the live
  version (`style.css` header for the theme; there is no external version
  probe for the plugins) or ask.
- **Data provenance**: `wp_academy_awards` (the master table) is Dalton's
  own manually uploaded data and is ground truth. He is the sole uploader.
  Never treat the bundled `data/oscars.csv` dataset in the Oscars repo as
  more authoritative than the live master table — it's a supplementary
  winner-completeness reference used only for format-immune backfill
  matching, not a replacement source.
- **Rollback pattern used throughout this period**: every additive
  feature (Hero Command, hybrid homepage composition, the Trinity
  relational fields) was built to fall back to its pre-existing behavior
  when unconfigured/unpopulated, rather than requiring a flag flip or code
  revert to undo. Preserve this pattern in future work on this codebase.
