# Review Pair It With Source Authority

contract: `Lunara > Theme Studio > Image Quality Console` includes a private read-only `Pair It With sources` lane for Review pairings.

contract: Pairing rows are derived from Review Debrief fields already parsed by `lunara_parse_pair_it_with_value()`: Theme Echo, Counter-Program, and Career Context.

contract: Each row shows the parent Review, pairing slot, expected title, IMDb title ID when present, resolved title when known, poster preview when explicitly resolved, status, warnings, and edit/view actions.

contract: Default Image Quality rendering uses lightweight pairing checks and defers poster HTML resolution. The dedicated `Pairing sources` surface resolves poster previews and can flag poster-only gaps.

invariant: This pass does not change Review content, Pair It With metadata, Oscar data, TMDb/OMDb data, images, URLs, schema, public rendering, or poster caches.

invariant: The lane is private/admin-only through the existing Control Desk capability gate and must not emit public CSS or public metadata.

invariant: Missing IMDb IDs, parser warnings, and resolved-title mismatches are treated as `Needs attention` in all pairing views. Missing posters are treated as `Needs attention` only when poster previews are intentionally resolved. Clean locked pairings are treated as `Ready`.

test: `powershell -ExecutionPolicy Bypass -File tests\theme-studio-review-pair-it-with-source-authority.ps1`

test: Existing Review Single and Image Quality contracts continue to pass.

deferred: Replacing Pair It With posters, importing corrected poster art, marking pairings manually verified, and auto-validating external poster content are deferred to a later mutation-enabled Image Authority pass.

## Working Notes

- The Sinners Pair It With QA found at least one poster/source mismatch after the visual controls shipped.
- The immediate need is a private inspection lane so mismatches become visible before the module is treated as finished.
- The existing Image Quality Console is the correct home because this is source authority, not public layout.
