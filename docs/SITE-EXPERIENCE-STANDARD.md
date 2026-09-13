# Lunara site experience standard

Working direction from Dalton's September 12, 2026 request: the whole site
should feel unmistakably Lunara, and managing it should feel like using one
well-designed product. This is the implementation standard and open work list,
not a claim that the entire redesign has shipped.

## The reader experience

Lead with a film, an argument or a current story. Give the review archive room
to demonstrate the depth of the criticism, and give the Journal an immediately
visible sense of current coverage. Let Oscars combine arresting artwork with
useful paths into the ledger. Preserve the midnight palette, restrained gold,
licensed editorial typography and the Method's distinctive three-film idea.

Shared rules should make the pages feel related without making all four layouts
identical:

- One recognizable header, consistent navigation order and active-page state.
- A common reading width, spacing scale, button treatment and type hierarchy.
- Headlines wrap at word boundaries on phones. No clipped titles, squeezed
  cards, overlapping controls or artificially tall empty panels.
- Story imagery uses a stable landscape frame; posters and winner portraits
  keep their portrait shape. A chosen full-image fit shows the complete source.
  Source resolution and crop previews must agree with the public placement.
- Motion supports discovery. Visitors can stop it; keyboard focus, touch and
  reduced-motion preferences work consistently. Content remains readable when
  scripts do not load.
- Dates, archive counts and filters support the stories rather than dominating
  the opening screen. Performance is part of the visual quality.

## The editing experience

Start in Site Studio and choose Home, Reviews, Journal or Oscars. The editor
should answer: which page am I changing, which section, what will it look like,
and has it been applied? Article writing and Academy record maintenance retain
their own data owners; links must name those destinations honestly.

The shared workflow is: choose a page and section, edit content/layout/images,
Preview at desktop/tablet/mobile widths, then Apply. Discard and History must
remain easy to find. Navigation protects unsaved changes and pending saves.

Selection controls should use the same search, add/remove, drag and keyboard
ordering behavior. Automatic and manual modes retain independent selections;
explain the actual ordering rule. Unavailable selections and missing artwork
must be visible in the editor. Presentation overrides should not rewrite an
article. Existing presentation remains in place until an explicit Apply.

Group controls by the task: Content, Stories and images, Layout, and History
where those capabilities exist. Use Mobile only for settings that actually
differ on mobile, or for a clearly stated mobile behavior. Keep necessary
older controls reachable until equivalent shared controls are verified.

## Evidence and delivery sequence

The September 12 inspection still served build `3.2.67+20260911-195706` in
anonymous canary requests and the signed-in browser, despite the reported
3.2.68 deployment. Observations below describe that measured build.

Later September 12 acceptance verified `3.2.69+20260912-151630` on all three
anonymous reads, with both canonical canaries reporting `LIVE_COHERENT`.
The real Site Studio page navigation loads; the Journal preview uses a stable
390 by 844 mobile frame. Those observations are superseded where noted below.

The next acceptance verified `3.2.72+20260913-013957`: three anonymous build
reads agree and both canonical canaries report `LIVE_COHERENT`. Real Home,
Reviews, Journal and Oscars pages were inspected at 390 by 844 with no
horizontal document overflow. Home has separate artwork, copy and controls;
the archives have compact filters and readable full titles; Oscars retains
ordinary word wrapping and two portrait board columns. Physical-device testing
is not claimed.

| Area | Observed gap | Next implementation / acceptance |
| --- | --- | --- |
| Site Studio entry | Flat directory of page, section and utility destinations above every editor | Verified live in 3.2.69: four primary pages, contextual editors, collapsible searchable directory, guarded navigation. Home opens by default. |
| Inspector groups | Archive-wide geometry filed under Mobile; inconsistent task labels | Verified live in 3.2.69: archive Content/Layout groups, aligned Method and homepage Oscar labels, shared History. This does not add missing capabilities. |
| Reviews and Journal editing | Gallery and continuation artwork still require Classic controls | 3.2.70 story controls are shipped. 3.2.73 candidate adds Gallery and Continue reading with shared image selection, focal points, drag/keyboard order and canonical Preview/Apply/History. Individual article artwork remains with its article owner. |
| Oscars editing | Some buttons, Quick Start cards and winner tools still live in Classic controls | Map each live portal placement to its canonical owner; migrate related controls in complete groups. Homepage Picks/Facts controls do not substitute for portal editing. |
| Reviews opening | Large introductory/statistics panel preceded the lead review | Verified live through 3.2.72: compact introduction and filters, no public Reviews Command panel, visible portrait art and complete phone titles. |
| Journal opening | Title, counters and filter panels delayed visible story artwork | Verified live through 3.2.72: compact wrapping controls, stable 16:10 phone artwork and complete headlines. |
| Shared header | Home/Oscars omit the textual Search item present on Reviews/Journal | Resolve the live header/menu ownership and make navigation consistent without duplicating search controls. |
| Oscars mobile | Heading hyphenated ordinary words and nested gutters narrowed cards | Verified live at 390px in 3.2.72: intact words, compact navigation and two portrait board columns. Wider local matrix evidence remains in the release record. |

### Archive migration findings and resolution

September 12 mobile correction, Theme 3.2.72: the four landing pages
now share deliberate phone spacing and readable controls. Home separates
landscape artwork from its reading panel; Reviews and Journal compact their
filters and retain full headlines; Oscars keeps a two-column poster wall with
readable headings and compact supporting navigation. Route and critical CSS
agree on archive widths. These changes are deployed with the public acceptance
described above. They do not complete the remaining editor
capabilities or article and dossier layout work.

The 3.2.70 candidate adds `selection_version` inside each existing provider;
zero retains the old behavior, one enables the shared story rules. Merely
opening Stories or applying unrelated Content/Layout changes does not adopt
those rules. Reviews keeps its canonical pin as the active owner and remembers
an inactive manual ID in the same provider option. Automatic resolves the newest
publication before priority stories; private previews resolve their candidate
lead without changing the public pin.

Unavailable priority IDs remain recorded and are skipped publicly. Active
Manual candidates need a published lead before Preview or Apply. If an already
saved lead becomes unavailable, the read path recovers to Automatic, retains
the remembered ID and reports a warning. Empty priority lists mean no priority,
with the full archive still available. Classic controls must preserve unavailable
IDs without revealing unpublished metadata. Old revisions retain their original
selection semantics; restoring a newer revision can recover an unavailable lead
to Automatic. The findings below retain the original audit context.

The September 12 provider audit found behavioral differences that must be
resolved before making the controls look interchangeable:

- Journal's existing Shared lead reads `lunara_home_journal_lead_post_id`,
  independently of the new homepage carousel. Label this legacy behavior
  explicitly; Automatic must use publication date without that old pin.
  Owners: `inc/queries.php:236`, `inc/journal-archive-studio.php:1911`.
- Reviews Automatic clears its canonical review pin, but the public hero is
  the first query result. An older curated review can still come first.
  Test the actual renderer, not just the newest-review helper. Owners:
  `inc/reviews-archive-studio.php:1808`, `inc/review-rendering.php:1249`.
- Both Curated lanes prioritize selected IDs and then retain the rest of the
  archive. They are not exclusive manual carousels. Preserve browsing and
  filtering while explaining the selection model plainly.
- Both validators clear manual IDs when switching modes. Retain those lists
  deliberately during migration; merely exposing existing fields would lose
  selections. Current curated bounds are 1–24 unique published records of the
  correct type. Owners: Reviews provider around line 833; Journal around 962.
- Invalid saved manual leads currently fall back to Automatic/Shared. Deleted
  or private selections may also prevent restoring an old revision. Define
  visible warnings and recovery behavior; do not silently promise the
  homepage carousel's empty-manual behavior.
- Existing search helpers accept text or numeric IDs and filter to published
  records. Wrap those helpers in the shared Studio permission/nonce boundary.
  Reuse canonical transactions, cache invalidation and safe revision storage.

Both Reviews routes share `inc/review-rendering.php`; Journal uses
`archive-journal.php` and the provider query hook. Extend their actual-renderer
tests alongside the shared editorial workspace test. The current Reviews
"Newest Release" ordering uses WordPress publication date; labels and filtering
need to describe the real behavior. These are source-code findings, not claims
that a particular current live setting is broken. The observed Journal first
story was "Angel Finally Gets a Face That Can Fly."

Theme 3.2.73 migrates Gallery and Continue reading controls using the existing
archive providers. It remains a candidate until the separate release and live
acceptance steps are complete. Journal article and Review article presentation, site
footer, search/recovery, and Academy dossier routes also belong to the broader
site standard. Their inclusion here is an open work list, not evidence of parity.

For each delivery: verify actual renderer ownership, preserve saved state, test
the edited workflow and responsive output, run required theme checks, merge,
rebuild the exact rollback hatch, then verify the public version and canary
after Dalton's manual WordPress.com deployment. Do not call a merge a live fix.

Finish the functional migrations before hiding redundant controls. Finish the
public layout pass with a side-by-side review of all four pages using real
artwork. Fresh reviews/news and curated opening lineups remain editorial work;
the design surfaces published coverage rather than creating it.
