# Utility Search Preview Presets Spec

contract: Add named Utility Search packages inside `Lunara > Theme Studio > Utility Search Studio` that can be applied as saved bounded controls or previewed through an admin-only request parameter.

interface:
- Preset source: `lunara_control_desk_utility_search_preset_specs()`.
- Request-only preview key: `lunara-utility-preset`.
- Saved marker: `lunara_utility_search_preset`.
- Presets set only existing Utility Search Studio controls plus the saved marker.

preset packages:
- `balanced-desk`: default publication utility rhythm.
- `ledger-signal`: Oscar-led search and recovery emphasis.
- `criticism-run`: Review-led card spotlight.
- `journal-desk`: Journal/editorial-led result run.
- `navigation-clean`: compact utility index with Search recovery first.

invariant: Preview values affect only authenticated administrators who can `edit_theme_options`.

invariant: Anonymous users and logged-out crawlers ignore `?lunara-utility-preset=...`.

invariant: No raw CSS textarea, schema, URL, query, post-content, or route rewrite changes.

invariant: Search query eligibility remains WordPress-driven; presets only change display ordering/classes and CSS variables.

public surface:
- Search template reads safe preview values before saved theme mods when the current user can preview.
- 404 template reads safe preview values before saved theme mods when the current user can preview.
- Utility Search CSS reads safe preview values before saved theme mods when the current user can preview.
- Public classes remain scoped to Search and 404.

admin surface:
- Presets render as cards inside the existing Utility Search Studio form.
- Each card has Apply, Search, Ledger Search, 404, and 390px preview links.
- Apply uses the existing nonce/capability-protected `lunara_save_utility_search_studio` handler.
- Existing individual controls remain available below the preset cards.

test:
- Focused contract test proves preset specs, apply path, preview parameter, admin-only preview guard, Search/404 preview reads, scoped CSS, and no raw CSS textarea.
- Existing Utility Search Studio and Theme Studio command-index regressions continue to pass.

## Working notes

The feature intentionally does not create a separate “preview page.” It extends the current safe request-preview pattern already used by Review Single Studio and Oscars Dossier Studio.
