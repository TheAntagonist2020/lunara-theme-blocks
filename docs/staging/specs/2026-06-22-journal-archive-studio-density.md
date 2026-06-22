contract: Add a private Theme Studio panel named Journal Archive Studio that exposes bounded controls for the public Journal archive rhythm.

contract: Store new Journal archive tuning as theme mods only. Existing public URLs, Journal CPT queries, taxonomy filters, sort params, post content, and section-order settings remain unchanged.

contract: The admin save path uses `admin-post.php`, `edit_theme_options`, a nonce, sanitized select values, clamped numeric values, and reset checkboxes for numeric controls.

contract: Public rendering reads sanitized theme mods from a late scoped CSS layer. It applies only to `body.post-type-archive-journal` and does not affect Journal singles, homepage Journal lanes, Reviews, Oscars, or utility archives.

controls:
- `lunara_journal_archive_density`: compact, editorial, showcase.
- `lunara_journal_archive_lead_prominence`: restrained, standard, feature.
- `lunara_journal_archive_desk_rhythm`: quick, balanced, immersive.
- `lunara_journal_archive_section_gap`: bounded px rhythm between archive modules.
- `lunara_journal_archive_hero_min_height`: bounded px height for the top editorial command chamber.
- `lunara_journal_archive_card_min_height`: bounded px minimum for Journal archive cards.
- `lunara_journal_archive_media_min_height`: bounded px minimum for wide Journal card media.

invariant: No raw CSS textarea is exposed.

invariant: Missing featured images remain text-led cards, not empty media chambers.

invariant: Journal archive typography keeps the shared Lunara font system and uses scale, spacing, and density for visual variation.

invariant: The archive keeps exactly one H1.

test: `tests/theme-studio-journal-archive-density.ps1` proves the admin contract, control keys, nonce/capability checks, scoped CSS hook, CSS variables, and no raw CSS textarea.

test: PHP lint changed PHP files.

test: Visual QA for `/journal/` at 390, 768, and 1280 confirms no horizontal overflow, no broken images, no private/admin leakage, and a denser live-desk rhythm.

deferred: Drag/drop Journal archive section ordering inside Theme Studio. Existing Customizer-backed order remains the source for this pass.

deferred: A dynamic Journal carousel rail. This pass creates control foundation and rhythm polish only.

## Working notes

The Journal archive already owns `archive-journal.php`, section visibility checks, filters, sort controls, lead-entry promotion, and retention cards. This pass should mirror Reviews Archive Studio instead of changing archive data flow.
