# Homepage Journal Mobile Runway

contract: Preserve the current homepage architecture, content queries, section order, desktop Reviews-first order, and mobile Journal-first order.

contract: Add a scoped mobile-only public CSS layer that turns homepage Journal cards below `820px` into a denser editorial runway, reducing dark no-visual chambers without hiding links, labels, trailer badges, titles, excerpts, dates, or images.

contract: Keep the existing Journal renderer in `functions.php` as source of truth for `has-visual` and `has-no-visual` card state; this pass does not change queries or post data.

contract: Preserve the visual lead card as the Journal-first editorial opener; compact the supporting cards and no-visual briefs below it.

contract: Do not change theme mods, post data, URLs, schema, image metadata, fonts, Review runway behavior, or Oscar Facts behavior.

invariant: Journal card imagery keeps a wide editorial crop and uses existing image sources.

invariant: No-visual Journal cards must read as intentional text briefs, not empty media chambers.

invariant: Mobile Journal compaction must not create horizontal overflow or break the mobile Journal-first homepage order.

test: `powershell -ExecutionPolicy Bypass -File tests\homepage-journal-mobile-runway.ps1`

test: Public homepage QA at `390`, `768`, and `1280` confirms no overflow, no broken images, one H1, no Control Desk leakage, mobile Journal-first, desktop Reviews-first, and a compact mobile Journal card run.

deferred: Rebuilding the entire homepage, changing Homepage Studio controls, changing Journal archive layout, changing Journal post content, and sourcing new Journal images.

## Working Notes

- The 390px full-page QA after the Review runway pass still showed text-led Journal cards reading like dark empty chambers.
- The target is denser live-desk rhythm, not hiding Journal copy or making the cards feel cheap.
