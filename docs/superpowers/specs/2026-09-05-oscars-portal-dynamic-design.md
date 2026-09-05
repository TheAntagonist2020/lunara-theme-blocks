# Oscars Portal Dynamic Layer — Design (Theme 3.2.59)

**Status:** approved by Dalton, 2026-09-05, in conversation ("Approve as written").
**Scope:** the `/oscars/` portal only. Theme-only release, stacked on the
unreleased 3.2.58. No plugin change. Nothing deploys from this work.

## Why

Dalton's brief: "make sure the oscars page and the site itself is incredibly
dynamic." 3.2.58 fixed the geometry. The page still reads as static because:

- `assets/js/lunara-public-runtime.js` skips scroll reveals on the portal
  by design (the comment says so), and because the portal embeds the plugin
  hub, the *plugin-page* branch runs instead and reveals only `.aat-stat`
  and timeline nodes inside the research shell.
- The GSAP motion layer targets no portal selector.
- The animated counter fires only on `.aat-stat-number`.
- Every number on the page is fixed at render time. There is no season
  clock, no board summary, and no date-seeded content beyond the rotating
  ceremony showcase.
- The rotating winners carousel ships a blank first slot and tall, mostly
  empty cards (logged 2026-09-05, not fixed).

## What ships

### Motion and liveness (runtime + shell CSS)

1. **Portal reveals.** The reveal IIFE gains a portal branch, checked before
   the plugin-page branch. Every top-level section except the hero, and the
   cards inside link, spotlight, title, research, winners, facts, and board
   grids, get `lunara-reveal`; the grids get `lunara-reveal-stagger`. The
   generic `.lunara-reveal` rules in `style.css` (reduced-motion gated) do
   the rest. Elements already inside 90% of the viewport at boot are marked
   visible immediately. A 6-second safety timer marks anything still hidden
   visible, so no render harness or slow observer can leave a hole.
2. **Counters** extend from `.aat-stat-number` to the hero stat values, the
   Deep Cuts fact values, and the season-clock day count. Four-digit years
   are skipped so "2025" does not count up from zero.
3. **Navigator scroll-spy.** An IntersectionObserver with a
   `-35% 0px -55% 0px` band marks the navigator link whose section owns the
   middle of the viewport with `aria-current="location"`. Shell CSS styles
   that state.
4. **Poster hover depth** on title, spotlight, and winner cards, fine
   pointers only (`@media (hover: hover) and (pointer: fine)`).
5. **Rotating winners carousel:** diagnosed by offline render; fixed in
   the shell if the cause is CSS, in the card builder if it is data.

### Data liveness (PHP, inside existing slots)

6. **Season clock** in the hero, below the stat grid. One new Customizer
   theme mod, `lunara_oscars_next_ceremony_date` (`YYYY-MM-DD`, empty by
   default, sanitized by `lunara_oscars_sanitize_ceremony_date`). Phases by
   days-to-ceremony: `countdown` (>120), `season` (31–120), `final`
   (1–30), `tonight` (0), `settled` (-1 to -14). Past -14 the clock returns
   empty and renders nothing, so a stale date retires itself. The runtime
   recomputes the day count at view time from `data-lunara-season-clock`,
   so anonymous cached HTML never shows yesterday's number.
7. **Board summary chips** above the prediction board list: total calls,
   one chip per status present in canonical order (front-runner,
   contender, predicted, watchlist, won, missed), and "Revised {Mon d}"
   from the newest pick's modified time when WordPress exposes it. List
   markup is untouched.
8. **Today's Pull** in Deep Cuts, above the facts grid: one winning row from
   the ledger, chosen by a deterministic offset from (year, day-of-year),
   cached in `lunara_oscars_todays_pull_v1_{day}` for a day and cleared by
   the existing import flush. Links to the title, the person, the category,
   and the ceremony through the plugin's URL helpers.

### Byte-identical defaults

With no ceremony date set, no picks, and no ledger, every new renderer
returns the exact empty string. A default site renders 3.2.58 markup
byte-for-byte except for the runtime and shell additions.

## Constraints honored

- Route sheet has 880 bytes of headroom (44,120 / 45,000): **no route sheet
  changes.** All CSS lands in `lunara-shell.css` (184,028 / 204,800).
- Public runtime 11,759 / 20,480 bytes. Scroll carousel 6,214 / 10,240.
- Critical seed untouched (5,544 / 6,144).
- Studio slot registry untouched; the six sentinel ids untouched;
  `#oscars-board` emitted once; identity literals untouched.
- The board renderer stays anonymous-cacheable (no nonce, cookie, or
  user-conditional call) and still returns `''` for an empty board.
- New cache key is new (no shape change to an existing payload), and it is
  added to `lunara_flush_oscars_home_transients()`.
- No deploy, no cache operation, no production write.

## Not in scope, deliberately

- GSAP on the portal (about 110 KB more JS on a route already carrying
  132 KB of inline critical CSS).
- "On this day in Oscar history" from ceremony dates: the ledger carries no
  dates and 98 of them will not be typed from memory. A plugin-side dates
  table is the right home if Dalton wants it later.
- Site-wide motion vocabulary and the base stylesheet diet (carried).

## Proof

- `tests/fixtures/oscars-portal-dynamic-harness.php` executes the module's
  pure functions and renderers against stubs and reports JSON cases.
- `tests/oscars-portal-dynamic-contract.ps1` runs the harness and pins the
  static wiring: loader, template consumption through `function_exists`
  guards, Customizer registration, runtime branches, shell rules, flush key,
  and the anonymous-cacheable forbidden list on the new module.
- Release identity moves to `tests/release-identity-3-2-59.ps1`; every
  `3.2.58` pin in tests, CSS, and PHP moves to `3.2.59`; docs keep history.
- Mutation tests on the new contract, each restored from a `cp` backup.
- Offline renders at 390, 768, 1440, and 2560 pixels before and after.
