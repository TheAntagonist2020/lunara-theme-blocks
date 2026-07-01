# Lunara Theme Technical Specification

purpose: Active source specification for the Lunara Film WordPress theme.

user: Lunara site administrator and collaborators maintaining public editorial surfaces, private control surfaces, and verification workflows.

use-case: Publish and tune a film publication site with Reviews, Journal, homepage editorial lanes, Oscar route bridges, media discipline, SEO metadata, and private Theme Studio controls.

architecture:
- WordPress theme source lives in `G:\lunara-backups\work\lunara-theme-blocks-20260513-2300`.
- Live theme path is `/home/151589083/htdocs/wp-content/themes/lunara-theme-blocks-20260513-2300`.
- `functions.php` loads `functions-loader.php`; `functions-loader.php` splits the theme into ordered `inc/` modules.
- Foundation modules load before customizer, CPT, metadata, carousel, Control Desk, Debrief, rendering, query, homepage, Oscars portal, block, migration, and frontend modules.
- Frontend route polish is currently emitted mainly through scoped PHP render/CSS/JS functions in `inc/frontend.php`.
- Private admin control surfaces are currently implemented mainly in `inc/control-desk.php`.

stack:
- WordPress theme PHP.
- Blocksy-compatible theme behavior with custom Lunara overrides.
- PowerShell contract tests under `tests/*.ps1`.
- Theme-owned JavaScript under `assets/js/`.
- Theme-owned CSS under `assets/css/`.
- Vendored Splide assets for the homepage Oscar Facts pilot under `assets/vendor/splide/`.

entry:
- `functions.php`: top-level theme bootstrap, asset enqueueing, theme setup, shared helpers, and fallback functions.
- `functions-loader.php`: ordered include loader for `inc/` modules.
- `front-page.php`: homepage template.
- `archive-review.php` and `page-reviews.php`: Reviews archive entry points.
- `archive-journal.php` and `single-journal.php`: Journal archive/single entry points.
- `single-review.php`: Review single entry point.
- `page-oscars.php`: Oscars page bridge.
- `inc/frontend.php`: route-scoped frontend CSS, JS, SEO metadata, search behavior, footer behavior, and homepage polish emitters.
- `inc/control-desk.php`: Lunara admin menu, Theme Studio, Brand Console, Homepage Studio, Image Quality Console, and private source/version panels.

contract:
- `review` is a public custom post type with `/reviews/{post-slug}/` rewrite behavior.
- `journal` is a public custom post type with `/journal/{post-slug}/` singles and `/journal/` archive behavior.
- `journal_type` is a taxonomy for Journal entries.
- Journal REST/meta fields registered by the theme include `_lunara_journal_kicker`, `_lunara_journal_signal_note`, and `_lunara_journal_featured`.
- Homepage section order and visibility are controlled by sanitized theme mods.
- Homepage Studio controls are stored as bounded theme mods and consumed by homepage rendering/CSS.
- Oscar Picks curation uses `lunara_home_oscar_picks_manual_order` when present and falls back to smart ordering when absent.
- Review image focus, Oscar Fact visual focus/treatment, brand controls, and route-family density controls are private admin controls exposed through Theme Studio/Control Desk.
- Public frontend surfaces must not leak Control Desk, admin-only source anchors, private review notes, API keys, or source metadata.

flow:
- Local change: edit theme source, update/add focused contract tests, run PHP lint for changed PHP files, run `git diff --check`.
- Deploy change: back up live changed files, deploy only changed runtime files, run remote PHP lint, confirm local/remote SHA256 hashes match, flush WordPress cache.
- Public verification: smoke key public routes, run responsive QA at `390`, `768`, and `1280`, and save evidence under the Desktop workspace `10_VISUAL_EVIDENCE` folder.
- Continuity update: append the Desktop changelog, active session log, and handoff file.

invariant:
- The shared Lunara typography system stays intact across route-family polish.
- Homepage desktop defaults keep Reviews early; mobile defaults keep Journal early unless changed through controls.
- Visual cards must preserve their intended media aspect discipline.
- Text-led cards must render as intentional editorial cards, not empty media chambers.
- No public route should expose private Control Desk/admin/source metadata.
- Cache is flushed after live code deploys.

constraint:
- New website evidence, screenshots, logs, handoffs, changelogs, and working artifacts do not belong under `C:\Users\silve_i21do49\OneDrive\Documents\New project`.
- Website evidence belongs under `C:\Users\silve_i21do49\OneDrive\Desktop\New folder\10_VISUAL_EVIDENCE`.
- Website logs and handoff files belong under `C:\Users\silve_i21do49\OneDrive\Desktop\New folder\09_DOCS_AND_NOTES`.
- Website changelog belongs at `C:\Users\silve_i21do49\OneDrive\Desktop\New folder\LUNARA_WORLD_CHANGELOG.md`.
- API keys and secrets stay outside the theme repository.
- Paid third-party carousel/gallery plugin assets are reference material unless deliberately adopted through source-controlled theme code.

convention:
- Staging specs live under `docs/staging/specs/YYYY-MM-DD-<topic>.md`.
- Staging plans live under `docs/staging/plans/YYYY-MM-DD-<topic>.md`.
- Focused verifier scripts live under `tests/*.ps1`.
- Runtime theme source commits update the private Control Desk source anchor separately when needed.
- Public route-family visual systems should be distinct but related: homepage, Reviews, Journal, Oscars, Search, and utility routes should not collapse into the same generic template.
- Prefer theme-owned source-controlled controls and bounded theme mods over raw CSS textareas.
- Prefer ACF/structured fields for editorial metadata consolidation when the need is data modeling rather than a new public route.
