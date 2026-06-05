# Lunara Film — Theme Architecture & Operations

**Last updated:** 2026-05-10
**Theme:** `lunara-film-premium-20260503-living-pulse` (Blocksy child)
**Live site:** https://lunarafilm.com
**WP.com Site ID:** `247355955` (Atomic, Business plan, MCP enabled)

This document is the canonical reference for how the theme is built, what's in it, how to deploy changes, and how to recover when things break. Read this **before** modifying anything.

---

## TL;DR — If you only read one section, read this

1. **The homepage is composed of Gutenberg blocks**, not hard-coded in `front-page.php`. To change layout, edit the Home page's content (block editor or MCP), not the template file.
2. **Five custom blocks live in `functions.php`**. They render server-side via callbacks and have editor-side previews. See [Custom Blocks Reference](#custom-blocks-reference).
3. **All deploys are scp from a Windows machine to WP.com Atomic SSH.** No CI/CD. See [Deploy Runbook](#deploy-runbook).
4. **The WP.com Edge cache TTL is 1 year.** Always Clear Cache after a deploy or visitors won't see changes for 12 months.
5. **A mu-plugin guards against a Blocksy doubled-header bug.** Don't remove `wp-content/mu-plugins/lunara-header-duplicate-guard.php`.
6. **Previous autonomous agents have stripped ~270 KB of code unannounced.** Snapshots live alongside theme files (`*.bak-*`) and on the server (`/srv/htdocs/lunara-live-*.tar.gz`). Always snapshot before destructive changes.

---

## File Map

```
/srv/htdocs/wp-content/themes/lunara-film-premium-20260503-living-pulse/
├── functions.php                  ~600 KB  THE MONOLITH. CPTs, blocks, helpers, customizer.
├── front-page.php                 ~1 KB    Minimal — just calls the_content() in a loop.
├── style.css                      ~370 KB  All custom CSS. Append-only convention.
├── header.php                     ~6 KB    Blocksy parent header + custom search panel injection.
├── single-journal.php             ~9 KB    Journal CPT single template (with strip-duplicate-hero fix).
├── single-review.php              ~16 KB   Review CPT single template (with TMDB error filter).
├── single.php                     ~45 KB   Standard editorial single template.
├── page-oscars.php                ~49 KB   Oscars portal landing page.
├── template-lunara-hub.php        ~1 KB    Page Template — full-width canvas for hub feature pages.
├── archive-*, page-*, etc.        Standard WP template hierarchy.
├── inc/                           DEAD CODE — Path B modular split, NOT loaded by functions.php.
├── *.bak-*                        Local snapshots from prior edits. Safe to ignore.
└── ARCHITECTURE.md                THIS FILE.

/srv/htdocs/wp-content/mu-plugins/
└── lunara-header-duplicate-guard.php   Always-on guard against Blocksy header doubling. DO NOT REMOVE.

/srv/htdocs/wp-content/plugins/
├── academy-awards-table-optimized/    Dalton's Oscars database plugin. Stores winners/nominees/films.
└── lunara-dispatch-automation_*/      Dalton's RSS-to-journal Claude API plugin.
```

**Local development workspace** (Dalton's machine):
```
C:\Users\silve_i21do49\Projects\lunara-dispatch\includes\lunara-film-premium-20260503-living-pulse\
```
This is the canonical local copy. **In sync with live as of last verified deploy.**

**Other folders that exist but are NOT canonical** (do not edit):
- `New folder/lunara-film-premium-20260319-dynamic-homepage_STALE-DO-NOT-EDIT/` — old monolith, renamed for safety.
- `New folder/lunara-film-premium-20260503-living-pulse/` — codex-built Git monorepo, partial state, not authoritative.
- `G:\lunara-world-site-codex-theme-sync-20260508/` — May 9 baseline used for the hybrid restore.

---

## Architecture Overview

### Path B — Homepage as Gutenberg Blocks

Up to 2026-05-10, `front-page.php` was a 1,150-line PHP template that hard-coded every homepage section. Layout changes required editing template code and scp-deploying.

The new approach (**Path B**):

```
Home Page (id 4055)             ← Database row, status=publish, slug=home
   ↓
   content = "<!-- wp:lunara/latest-reviews /-->
              <!-- wp:lunara/oscar-picks /-->
              <!-- wp:lunara/journal-lane /-->
              <!-- wp:lunara/oscar-facts /-->"
   ↓
front-page.php (30 lines)        ← Just calls the_content() in a loop
   ↓
WordPress block parser sees the comment markers, calls each block's
render_callback() in functions.php, output is sent to the browser.
```

**Why this matters:**
- Layout reorders are done in the block editor (drag) or via MCP `pages.update`. **No code changes.**
- Add any Gutenberg core block (Cover, Image, Heading, Group, Columns, etc.) between the Lunara blocks for ad-hoc content.
- Each block has per-instance attributes — different hub pages can have different heroes, different review counts, different CTA targets.

**Don't do this:** Edit `front-page.php` to change homepage layout. The file is intentionally minimal. Layout lives in the page content.

### Lunara Hub Page Template

Any Page can opt into the same full-width canvas as the homepage by selecting **Lunara Hub** from the Template dropdown in the editor (or setting `template: "template-lunara-hub.php"` via the API).

Use cases: awards-season landing pages, ceremony-week microsites, special editorial features, "Sinners Spotlight" type pages.

The template file (`template-lunara-hub.php`) is structurally identical to `front-page.php` — the only difference is one extra body class (`lunara-hub-page`) for CSS scoping if needed.

---

## Custom Blocks Reference

All blocks are in the **Theme** category in the block inserter. All have editor-side previews via `ServerSideRender`. All render via PHP callbacks (no static save markup).

### `lunara/cinematic-hero`

Full-viewport image-first opener. **Per-instance attributes** (sidebar panel "Hero Overrides"):

| Attribute | Type | Default | Notes |
|---|---|---|---|
| `overrideImageId` | number | 0 | WordPress attachment ID. MediaUpload picker. |
| `overrideKicker` | string | "" | All-caps small label above title. Default: "Latest Review" |
| `overrideTitle` | string | "" | Big serif headline. Default: latest review's title |
| `overrideExcerpt` | string | "" | Italic dek under title. Default: latest review's excerpt |
| `overrideUrl` | string | "" | Click-through URL. Default: latest review's permalink |
| `overrideCta` | string | "" | Button text. Default: "Read the review" |

**Three-tier resolution** for each field:
1. Block attribute (per-instance) — wins if set
2. Customizer override (site-wide, "Lunara Cinematic Hero" panel) — wins if attribute is blank
3. Auto value from latest published review — fallback

If no image is available from any tier, the block renders nothing.

**Render fn:** `lunara_render_cinematic_hero($attrs)`
**Data fn:** `lunara_get_cinematic_hero_data($attrs)`

### `lunara/latest-reviews`

Grid of recent published reviews. Sidebar panel "Latest Reviews Settings":

| Attribute | Type | Default | Notes |
|---|---|---|---|
| `count` | number | 8 | Number of reviews to show. Range 1-24. |
| `heading` | string | "" | Default: "Latest Reviews" |
| `kicker` | string | "" | Default: "Lunara Reviews" |
| `ctaLabel` | string | "" | Default: "All Reviews" |
| `ctaUrl` | string | "" | Default: home_url('/reviews/') |

**Render fn:** `lunara_render_homepage_latest_reviews($attrs)`
**Query helper:** `lunara_latest_reviews_query($count)` — cached 15 min, auto-busted on review save/delete.

### `lunara/journal-lane`

1 lead card (full-width) + 3 supporting cards (2x2 grid) from the most recent journal/post entries. No attributes yet.

**Render fn:** `lunara_render_homepage_journal_lane()`
**Query helper:** `lunara_home_dispatches_query(4)`

### `lunara/oscar-picks`

Horizontal carousel of curated `lunara_oscar_pick` CPT records. No attributes yet (filtering by ceremony year / category / status is a future addition).

**Render fn:** `lunara_render_oscar_picks_carousel($args)`
**Query helper:** `lunara_get_oscar_picks($args)`
**CPT:** `lunara_oscar_pick` (see [Custom Post Types](#custom-post-types))

### `lunara/oscar-facts`

Text-forward carousel of `oscar_fact` CPT records. Cards switch to 2-column layout when a featured image is set. No attributes yet.

**Render fn:** `lunara_render_oscar_facts_carousel($args)`
**Query helper:** `lunara_get_oscar_facts($args)`
**CPT:** `oscar_fact`

---

## Block Patterns

Inserterable from the editor → "+" → Patterns tab → **Lunara** category:

| Pattern | Layout |
|---|---|
| Lunara Homepage — Reviews-First | latest-reviews → oscar-picks → journal-lane → oscar-facts |
| Lunara Hub — With Cinematic Hero | cinematic-hero → latest-reviews → journal-lane → oscar-picks → oscar-facts |
| Lunara Hub — Awards Focus | cinematic-hero → oscar-picks → oscar-facts |
| Lunara Hub — Editorial Focus | cinematic-hero → journal-lane |
| Lunara Hub — Picks Spotlight | cinematic-hero (with override kicker) → oscar-picks |

**To add a new pattern:** edit `lunara_register_hub_block_patterns()` in `functions.php`.

---

## Custom Post Types

| CPT | Slug | Purpose | Featured Image | Notable Meta |
|---|---|---|---|---|
| `review` | reviews | Long-form criticism | Poster | `_lunara_year`, `_lunara_score`, `_lunara_imdb_title_id` |
| `journal` | journal | News, dispatches, trailer reactions | Hero image | (Section Images via meta box) |
| `lunara_oscar_pick` | oscar-picks | Predicted/actual Oscar winners with editorial photos | Behind-the-scenes shot | `_lunara_pick_film`, `_lunara_pick_person`, `_lunara_pick_ceremony_year`, `_lunara_pick_status`, `_lunara_pick_oscar_entity_url` |
| `oscar_fact` | oscar-facts | Trivia, records, firsts, family dynasties | Optional film still | `_lunara_fact_attribution`, `_lunara_fact_year`, `_lunara_fact_citation` |

Plus taxonomies: `oscar_pick_category` (Best Picture, Best Director, etc.), `oscar_fact_category` (firsts, records, family-dynasties, etc.), `journal_type`, `lunara_director`, `lunara_review_year`.

**Permalinks auto-flush** on first request after a new CPT is registered (one-shot via `lunara_oscar_cpts_maybe_flush_rewrites`).

---

## Customizer Panels

Accessible from **Appearance → Customize**:

- **Lunara Cinematic Hero** — site-wide hero overrides (image, kicker, title, excerpt, URL, CTA)
- **Lunara Global Design** — palette, typography, shell width, card styling
- **Lunara Header** — header padding, logo size, nav gap
- **Lunara Homepage Layout** — width, spacing, section order
- **Lunara Homepage Curation** — section editorial copy
- **Lunara Editorial Archives** — review/journal archive controls
- **Lunara Oscars Portal** — `/oscars/` landing page
- **Lunara Footer** — footer columns and legal row
- **Lunara Journal Single** — byline/date/reading-time toggles, title color picker
- **Lunara Homepage Pulse** — (legacy, may not affect anything since pulse panel is disabled)

**To add a customizer setting:** follow the existing pattern in `functions.php` — `customize_register` hook → `add_section`, `add_setting`, `add_control`.

---

## Deploy Runbook

### Required tooling

- **Windows machine** with PowerShell + OpenSSH (built into Win10+)
- **WP.com SSH key** configured in `~/.ssh/`
- **SSH alias**: `slowlymagneticcb9c284bdc-mrckz.wordpress.com@ssh.wp.com`

### Standard deploy pattern

Each file is uploaded individually via `scp`. There's no batch upload script (yet). Pattern:

```powershell
$src  = "C:\Users\silve_i21do49\Projects\lunara-dispatch\includes\lunara-film-premium-20260503-living-pulse"
$dest = "slowlymagneticcb9c284bdc-mrckz.wordpress.com@ssh.wp.com:/srv/htdocs/wp-content/themes/lunara-film-premium-20260503-living-pulse"

scp "$src\functions.php"  "$dest/functions.php"
scp "$src\style.css"      "$dest/style.css"
scp "$src\front-page.php" "$dest/front-page.php"
```

**For the mu-plugin:**
```powershell
scp "C:\Users\silve_i21do49\OneDrive\Desktop\New folder\06_MAINTENANCE_SCRIPTS\lunara-header-duplicate-guard.php" `
    slowlymagneticcb9c284bdc-mrckz.wordpress.com@ssh.wp.com:/srv/htdocs/wp-content/mu-plugins/lunara-header-duplicate-guard.php
```

### After every deploy

1. **Clear Cache** in WP.com Hosting Dashboard → Hosting → Cache → Clear Cache
2. *Or* in SSH: `wp cache flush`
3. Verify in a private/incognito window — `Ctrl+Shift+N` then visit `lunarafilm.com/?cb=$(date +%s)`

### Verifying a deploy actually landed

```bash
ls -lh /srv/htdocs/wp-content/themes/lunara-film-premium-20260503-living-pulse/{functions.php,style.css,front-page.php}
wc -l /srv/htdocs/wp-content/themes/lunara-film-premium-20260503-living-pulse/front-page.php
grep -c "lunara/latest-reviews" /srv/htdocs/wp-content/themes/lunara-film-premium-20260503-living-pulse/functions.php
```

Expected:
- `front-page.php` should be ~30 lines / ~1 KB. If it's 1000+ lines, the new file didn't deploy.
- `functions.php` `grep -c "lunara/latest-reviews"` should be `7` or higher.

### MCP-driven content updates (no scp needed)

For homepage layout changes, page edits, or any content stored in the database:

```
wpcom-mcp-content-authoring
  action: execute
  operation: pages.update
  wpcom_site: 247355955
  params: { id: 4055, content: "<!-- wp:lunara/X /--> ...", user_confirmed: "yes" }
```

Layout reorders take 5 seconds via MCP. Always default to `draft` for new pages; `publish` only when user explicitly says so.

### Cache layers (in order, most aggressive first)

1. **WP.com Cloudflare Edge** — TTL 1 year via `s-maxage=31536000`. Cleared via Dashboard → Clear Cache.
2. **WordPress object cache** — Cleared via `wp cache flush` in SSH.
3. **Browser** — Cleared via incognito window or `Ctrl+Shift+R`.

If a deploy doesn't show up: check the cache in that order. The Edge is by far the most common culprit.

---

## Emergency Procedures

### "The homepage is broken / blank"

1. **Don't panic.** A snapshot of the live theme exists on the server: `/srv/htdocs/lunara-live-REGRESSED-pre-rollback-20260510.tar.gz` (and possibly newer ones).
2. Restore via SSH:
   ```bash
   cd /srv/htdocs/wp-content/themes/
   tar xzf /srv/htdocs/lunara-live-REGRESSED-pre-rollback-20260510.tar.gz
   ```
3. Clear cache.
4. Triage what went wrong from the local workspace before re-deploying.

### "The Home page is empty in the editor"

The Home page (id 4055) lost its block content. Re-set via MCP:
```
operation: pages.update
params: {
  id: 4055,
  content: "<!-- wp:lunara/latest-reviews /-->\n\n<!-- wp:lunara/oscar-picks /-->\n\n<!-- wp:lunara/journal-lane /-->\n\n<!-- wp:lunara/oscar-facts /-->",
  user_confirmed: "yes"
}
```

### "An autonomous agent stripped the theme overnight"

Happened on 2026-05-09 → 10. Process for hybrid restore:

1. SSH in, snapshot the regressed live state to a tarball outside the themes dir
2. Restore from G:\ baseline (`G:\lunara-world-site-codex-theme-sync-20260508\`) — has the pre-regression theme files
3. Diff each file to identify legitimate post-regression additions worth preserving (e.g., search panel in header.php, TMDB error filter in single-review.php, strip-duplicate-hero in single-journal.php)
4. Apply preservations on top of the rolled-back files
5. Lint with `php -l`, deploy
6. Verify with `curl` + class-name greps

### "Doubled header on mobile"

The mu-plugin should handle this automatically. If it's still happening:

1. Verify mu-plugin is installed: `ls /srv/htdocs/wp-content/mu-plugins/lunara-header-duplicate-guard.php`
2. If missing, deploy: `scp [local] [server]/mu-plugins/lunara-header-duplicate-guard.php`
3. As emergency CSS-only fallback, paste into Customize → Additional CSS:
   ```css
   @media (max-width: 999.98px) {
     body header.ct-header [data-device="desktop"] { display: none !important; }
   }
   @media (min-width: 1000px) {
     body header.ct-header [data-device="mobile"] { display: none !important; }
   }
   ```

### "Cache won't clear no matter what I do"

1. SSH: `wp cache flush`
2. Dashboard: Clear Cache
3. Add a query string to the URL: `lunarafilm.com/?fresh=$(date +%s)` — Cloudflare treats unknown query strings as cache-misses
4. If nothing works, edit any Customizer setting trivially and save — forces a CSS regen which sometimes triggers a wider purge

---

## Backups & Snapshots

### Local snapshots (`.bak-*` files in theme directory)

Convention: snapshot a file before a destructive edit by appending `.bak-{description}-{YYYYMMDD}` to the filename. Example:

```bash
cp functions.php functions.php.bak-pre-pathB-20260510
```

These accumulate over time. Worth pruning every few months — keep the last ~5 of each file, delete older ones.

### Server-side snapshots

Long-term tarballs of the live theme are stored at `/srv/htdocs/lunara-live-*.tar.gz`. After a few days of stability, you can `rm` them:

```bash
ls -lh /srv/htdocs/lunara-live-*.tar.gz
rm /srv/htdocs/lunara-live-OLD-20260X.tar.gz
```

### Git repo (separate, optional)

A version-controlled monorepo exists at `New folder/lunara-film-premium-20260503-living-pulse/` (codex-built, branched, with PR template + CI). It's not currently in sync with the local canonical or live. If formalizing version control becomes a priority, that repo is the right starting point.

---

## Things to NOT Do

A short list of footguns that have caused damage in past sessions:

1. **Don't edit `front-page.php` to change homepage layout.** The file is minimal by design. Edit the Home page's content instead (block editor or MCP).
2. **Don't load files from `inc/`.** That directory exists from a half-completed Path B refactor in April. `functions.php` does not load it. Editing `inc/customizer.php` won't affect anything live. The only loaded inc/ file is `journal-cpt.php`, explicitly required from functions.php.
3. **Don't run autonomous theme rewrites overnight.** A previous agent stripped 270 KB of code without reporting it. Always snapshot first, always describe changes, always confirm with Dalton before destructive edits.
4. **Don't use `wp post update` from CLI** — the user account doesn't have edit permissions for some post types. Use MCP `pages.update` / `posts.update` instead.
5. **Don't deploy `--no-verify` or skip pre-commit hooks** if/when Git workflow is adopted.
6. **Don't trust `WebFetch` for class-name verification.** Its AI summarizer drops HTML class attributes. Use `curl` with `grep` instead.
7. **Don't assume default WP cache will clear on its own.** Manual Clear Cache after every deploy.

---

## Recent Major Changes Worth Knowing

(For full session-by-session log, see `CHANGELOG.md` if it exists.)

**2026-05-10:**
- Path B architecture shipped — homepage is now Gutenberg-block driven
- 5 custom blocks registered with editor previews
- Lunara Hub page template + 5 block patterns
- Reviews-first homepage order (vs. previous cinematic-hero-first)
- Mobile perf overhaul (`<img>` with srcset, fetchpriority=high, mobile-disabled Ken-Burns)
- Header duplicate-guard mu-plugin installed
- Hybrid restore from G:\ May 9 baseline (recovered ~45 functions stripped by autonomous agent overnight)
- Markdown asterisk parser bug fixed in Oscar Facts importer
- TMDB + Wikipedia auto-fill admin action for Oscar Fact images

---

## Who to Ask / Where to Look

- **For homepage layout questions:** edit the Home page (id 4055) via block editor or MCP. Don't touch templates.
- **For block behavior:** see [Custom Blocks Reference](#custom-blocks-reference) and the render functions in `functions.php` (search for `function lunara_render_*`).
- **For the Oscars data layer:** that's the `academy-awards-table-optimized` plugin, not this theme. Theme just consumes its data via helper functions.
- **For RSS-to-journal automation:** that's the `lunara-dispatch-automation` plugin.
- **For editorial decisions / design direction:** Dalton.
- **For the Lunara critic voice (Vera Calloway):** the `lunara-critic` skill.

---

End of doc. Keep it current.
