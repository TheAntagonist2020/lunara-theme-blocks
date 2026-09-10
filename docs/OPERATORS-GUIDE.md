# Lunara Film — The Operator's Guide

*For the editor of lunarafilm.com. What is where, how to drive it, and where it can still go.*
*Current as of theme 3.1.83 · lunara-core 0.5.1 · oscars-ledger 2.7.74 · imdb-guard 0.4.0*

---

## 1. The site at a glance

**Lunara Film Living Pulse is a standalone theme.** The Blocksy parent is gone
(the `Template:` line was removed in 3.1.77); every template, style, and
behavior on the site is yours, in this repository. There is no upstream theme
that can change under you.

The stack:

| Piece | Repo | Job |
|---|---|---|
| Theme | `lunara-theme-blocks` | Everything visual + the Control Desk |
| Lunara Core | `lunara-plugin-core` | Content models: reviews, Film Dossiers, Talent, essay modules, graph growth |
| Oscars Ledger | `lunara-plugin-oscars-ledger` | The awards database (12,000+ entries, 3,515 derived facts) + search feed |
| IMDb Guard | `lunara-plugin-imdb-guard` | Poster/image backfill + local TMDB batch tools |
| Dispatch | `lunara-plugin-dispatch` | Journal lane blocks |
| AI Assistant | `lunara-plugin-ai-assistant-classic` | Private editorial suggestions (Claude/OpenAI/Gemini) |

**How to know what's live:** view source on any page and find the
`lunara-build` meta tag — `3.1.83+20260708-…` is version + deploy moment.
Or open **Control Desk → System Status → Deploy Truth**, which also runs a
drift sweep: any theme file edited outside the repo→deploy pipeline gets
named there before the next deploy can silently overwrite it.

**The release loop:** work lands in the repo → PR → CI lint gate (PHP
syntax, JS syntax, CSS brace balance on every PR) → merge to `main` → you
deploy (as a timestamped theme directory) → Deploy Truth confirms.
**Rollback is always one revert commit + redeploy.**

---

## 2. Daily driving — publishing

### A review, end to end
1. **Write it** — Reviews → Add New. The body supports everything, plus the
   **Essay Modules** panel (pullquote, inset frame, video spread, cinema
   banner, prose) rendered after the article.
2. **Details/IMDb** — set the IMDb title id (`tt…`). This wires the Oscar
   Ledger pill, Where-to-Watch, the schema graph, and search.
3. **The Trinity** — pick the three relational films (Theme Echo,
   Counter-Program, Career Context) in the movie pickers. These render the
   relational pairing cards; unset ones fall back to your legacy text.
4. **Trailer** — paste the URL in the trailer box. Readers get a
   click-to-load poster facade (no black iframe rectangle, no YouTube
   payload until they ask).
5. **Publish.** If the IMDb id has no Film Dossier yet, one is **spawned
   automatically as a draft** — the graph grows itself; you fill the
   dossier when you like. Drafts never render publicly.

### Backfilling old reviews
**Control Desk → Reviews → Relational Trinity** shows every published
review missing picks, oldest first, with per-relation counts. When the
legacy pair text can be matched to a movie safely (IMDb id exact, or exact
title with a remake guard), a **one-click Link button** appears — press it
and the ACF pick is written for you.

### Journal
Journal → Add New. Channel (journal_type), timestamps, and desk metadata
drive the archive's "desk" rhythm automatically. Inline images in journal
bodies are auto-framed (border, radius, shadow, never upscaled).

### Oscars data
You are the sole uploader. The master `wp_academy_awards` table is ground
truth; derivations (3,515 facts, entity graph, stats) rebuild from it.
**Oscars Integrity** in the Control Desk audits derivation completeness —
the guarded-insert log catches anything the schema rejects.

---

## 3. The Control Desk map

`wp-admin → Lunara` (the top-level menu). By tab:

### System Status
- **Deploy Truth** — live version, active theme directory, deploy moment,
  drift sweep, parent baseline.
- **Header Command** — the takeover switch (moot now that the theme is
  standalone: the Lunara header is always on).
- **Design Tokens** — *your dials.* Six palette dials (golds, navies, text
  tones) and five typographic-role dials (body / display / signature /
  glamour / label, each assignable to any house face). Only turned dials
  are output; **Reset** returns to pure stylesheet. Saves purge the cache.
- Phase cards, source map, source-control anchors, OMDb queue.

### Homepage Board
- **Homepage Studio** — section order (desktop + mobile presets),
  visibility switches, density, review-grid columns. Saves write through
  to the Home page's blocks.
- **Pairing Desk Showcase** (the Lunara Method band):
  - Kicker / headline / paragraph copy.
  - **Featured review** — the marquee dial. Pick any published review and
    the whole band re-dresses itself (trio, credit, backdrop). Program a
    director season; "Automatic" follows your newest complete review.
  - **Backdrop image** — media-picker override for the band's cinematic
    background; Clear returns to the review's own hero.

### Reviews
Draft/pending pipeline with blockers, editor deep-links, AI suggestion
buttons, and the **Relational Trinity backfill** panel (see §2).

### Theme Studio / Brand
Logo sizing (desktop/tablet/mobile), brand imagery, and the Customizer
text mods for every archive and portal heading.

### Hero Command
The curated hero deck: slides, kickers, CTAs, overlay intensity, AJAX
search to add any post. (The homepage front desk currently supersedes the
hero on Home; the deck still drives anywhere the hero renders.)

### Oscars Integrity / Speed / Visual QA / AI Operator
Diagnostics: poster & route checks, payload watch, breakpoint list,
provider-routed private suggestions.

---

## 4. The homepage system

The Home page is **block-composed** (five section blocks) with the Studio
writing through to them — edit in either place, they stay in sync.
Top-to-bottom:

1. **Front desk masthead** — brand, dek, lead review, Journal/Oscars/search
   signal stack, route doors.
2. **Signal Bar** — the slim live strip (Review · Journal · Ledger · ⌘K
   Search). Reuses the masthead's own data; swipeable on phones.
3. **Only on Lunara / Pairing desk** — the Method marquee (§3).
4. **Journal lane**, **Oscar picks**, **Oscar facts** — carousels and lanes,
   all Studio-controlled.

---

## 5. Search Command

- **Open:** ⌘K (or `/`), the header search pill, or any search trigger.
- **Empty state:** suggested commands (latest review + evergreen ledger
  queries — filterable via `lunara_live_search_suggestions`).
- **Results:** grouped by desk — Reviews, Journal, Films, Talent, Stories,
  and **Oscar Ledger** (films *and people* straight from the awards
  database, with "2 wins · 6 nominations" meta). Filter chips across the
  top; full keyboard navigation.
- **Speed:** responses are memoized ten minutes per query — popular
  searches never touch SQL twice.
- **The branded route:** `/search/?q=…` renders through the theme's own
  search template; `/?s=` remains as legacy.

---

## 6. The motion system (Atmosphere I–III)

One law: **JS-off and reduced-motion readers get the exact site that
shipped without motion.** Every instrument is additive.

| Instrument | What it does |
|---|---|
| The Cut | View-transition match-cut between pages |
| The Dolly | Scroll parallax on heroes (Chromium desktop) |
| Rack Focus | Hover a poster, shelf-mates drop their key light |
| Room Tone | 35mm grain veil + vignette |
| Key Light | Gold shafts on the Oscars hero |
| Title Cards / Hairline / Lights Down | Hero text rise, gold playheads, dimming |
| **Hero Breath** | Slow imperceptible zoom on hero media |
| **Dust Motes** | Drifting particle veil on dark hero bands |
| **Tracking Shot** | Shelves ease in laterally with the scroll |

GSAP arms headings, grids, award records, essay modules, and the tracking
shelves; everything bails cleanly behind `prefers-reduced-motion`.

---

## 7. Where things live (theme internals)

- `style.css` — tokens (`:root` ~line 165: palette, faces, §18 fluid type),
  then all component CSS in labeled blocks. **Version header = release.**
- `functions-loader.php` — 16 labeled layers; each `inc/` module states its
  job at the top. Highlights: `debrief.php` (pairing engine),
  `entity-surfaces.php` (dossiers + schema graph), `hero-command.php`,
  `live-search.php`, `header-command.php` (header + off-canvas),
  `design-tokens.php` (the dials), `control-desk.php` (the whole desk),
  `geo.php` (`/llms.txt`).
- `assets/js/` — `lunara-gsap-motion.js` (arms), `lunara-live-search.js`
  (palette), `lunara-header.js` (off-canvas + zombie cleanup).
- **Templates** — complete hierarchy in the theme root; `header.php` /
  `footer.php` are self-sufficient (Blocksy calls are guarded no-ops).
- **Fonts** — licensed woff2s served from `wp-content/uploads/lunara-fonts/v1/`
  (Tiempos Text/Headline, GT Sectra, Canela Deck; Bebas ships in-theme).
  The zip masters live off-site with you — don't lose them; the repo
  deliberately carries no licensed binaries.
- **Editor truth** — `theme.json` presets mirror the real faces and palette,
  so block-editor content can't drift back to Georgia/Bebas.

---

## 8. Operations

- **Deploy:** push-button from `main` into a timestamped theme directory.
  Deploy Truth names the active directory, so "which copy is live" is
  never a mystery again.
- **Caching:** WP Rocket + WordPress.com edge. Hard rule learned twice:
  any inline CSS that hides or positions critical UI **must** carry a
  marker and be listed in the Rocket RUCSS exclusions (front door,
  header-command, shell-repair, design-tokens all are).
- **Analytics:** the Google tag (`GT-K4ZN5WZB`, via Site Kit) loads async
  on every page — collection is independent of any dashboard "connect"
  nags. Reconnect Site Kit's Google login whenever the dashboard asks;
  data never stops flowing.
- **CI:** every PR in all six repos runs the lint gate.
- **Open housekeeping:**
  - Rotate the exposed TMDB API key at themoviedb.org (still unconfirmed).
  - Delete leftover Blocksy Companion (free) if still listed in Plugins —
    the theme hides *and* deletes its zombie drawer either way.
  - Nine Customizer custom-CSS entries + two WPCode SEO snippets live
    outside the repo; worth folding in someday.
  - `docs/CHANGELOG.md` (the project history handoff) awaits your
    read-through before committing.

### Troubleshooting the classics
| Symptom | Cause | Fix |
|---|---|---|
| Site suddenly generic | WP fell back to a default theme | Reactivate *Lunara Film Living Pulse* (Appearance → Themes) |
| Element flashes/moves on load | Late-hooked CSS or RUCSS stripped a rule | Move CSS to `wp_head` + add marker to Rocket exclusions |
| Screenshot shows "empty" sections | Reveal animations pre-trigger state | Trust DOM counts, not full-page captures |
| Plugin delete fatals | Half-removed plugin's own code runs during delete | Remove its folder via SFTP or use recovery mode |
| Live ≠ repo version | Out-of-pipeline edit | Deploy Truth names the drifted files |

---

## 9. Further refinements — the menu

Ordered by value as I see it; each starts on a word.

1. **Per-section dials** — extend Design Tokens with per-band overrides
   (accent, spacing rhythm, backdrop treatment per homepage section).
2. **Marquee scheduling** — queue future Featured Reviews for the Method
   band with start dates (your Nolan season, pre-programmed to the Odyssey).
3. **Reviews desk modes** — grow the archive's sort lanes into true modes
   (Canon by rating, Director lanes via the existing taxonomy).
4. **Search previews** — poster thumbnails in palette results (imdb-guard
   already knows the artwork).
5. **Social share cards for the marquee** — auto-compose an OG image from
   the showcase (backdrop + trio) so shares carry the full stage.
6. **Exit Veil** (Atmosphere's deferred instrument) — a leaving-the-page
   dim that pairs with The Cut.
7. **Custom-CSS consolidation** — fold the nine Customizer entries into
   the repo, then retire them.
8. **Plugin sediment pruning** — Elementor/Pods/NinjaTables/TablePress
   leftovers; fewer moving parts, faster site.
9. **Performance pass** — font subsetting, preload the two above-the-fold
   faces, LCP audit per route family.
10. **Entity-page enrichment** — filmography timelines on Talent pages,
    ceremony-to-ceremony navigation on dossiers, all from data you
    already have.

---

*The one-line philosophy of the whole build: every element on the site is
either yours to dial from the Control Desk, yours to edit in the block
editor, or yours to change in this repo — and Deploy Truth will always
tell you which one is live.*
