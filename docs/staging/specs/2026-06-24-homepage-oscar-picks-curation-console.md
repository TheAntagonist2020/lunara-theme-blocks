# Homepage Oscar Picks Curation Console

contract: `Lunara > Theme Studio > Homepage Studio` exposes a private Oscar Picks curation console for administrators with `edit_theme_options`.

contract: The console stores one ordered homepage Oscar Picks ID list in the theme mod `lunara_home_oscar_picks_manual_order`.

contract: The public Oscar Picks rail honors the manual order when it is present, while preserving the existing smart date/year fallback when the manual order is empty.

data: `lunara_home_oscar_picks_manual_order` is a comma-separated list of unique published `lunara_oscar_pick` post IDs.

invariant: Saving the console cannot create, delete, publish, unpublish, or rewrite Oscar Pick posts.

invariant: Deleted, draft, duplicate, wrong-post-type, and malformed IDs are discarded before storage and before public rendering.

invariant: The existing Homepage Studio nonce/action remains the save path; no new public route, schema, CPT, taxonomy, query var, or raw CSS textarea is added.

test: `tests/homepage-oscar-picks-curation-console.ps1` verifies the theme mod key, save sanitization, admin render hooks, public query consumption, and fallback preservation.

deferred: Drag-and-drop sorting and image replacement are not part of this pass; up/down/remove controls and add-on-save candidate checkboxes are enough for v1.

## Working notes

Keep the interface inside Homepage Studio because the public rail is a homepage signature lane. Use existing Oscar Pick posts as source of truth and avoid moving this into the Oscars plugin.
