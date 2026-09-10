# Site Studio presentation controls

Guide for the Theme 3.2.64 candidate. Deployment and acceptance status belong in
`docs/SESSION-LOG.md`; this guide does not establish what is live.

Open **Site Studio** and choose the page or section you want to change. An
authenticated **Edit this section** link on a supported public section opens
its controls directly.

## One editing workflow

1. Make your changes. They remain an unsaved candidate inside the editor.
2. Select **Preview changes**, then inspect the desktop and mobile views.
   Later edits mark that preview stale; preview again to see them.
3. Select **Apply changes** to save the candidate for the public page, or
   **Discard changes** to return to the saved settings.
4. Use revision history to restore an earlier saved presentation.

A failed save keeps your unsaved work and displays the error. Preview links
are private, expire, and belong to the signed-in editor who created them.
Changing presentation does not rewrite the source article.

## Sections and ordering

Homepage has independent desktop and mobile orders. Choose the layout you
want to edit, then drag a section or use its move buttons. Moving a section
preserves visibility and the other layout's order. Reviews and Journal archives
use the same drag and keyboard controls for their section order.

Hero Carousel and Journal Carousel retain their independent Automatic/Manual
selection, image and text overrides, timing, and lineups. Automatic uses the
six newest eligible published stories. Switching modes keeps the manual list;
**Use this lineup in Manual** explicitly copies the automatic lineup. See
`docs/HOMEPAGE-CAROUSELS.md` for their full controls.

## Lunara Method

Choose **Automatic** for the newest eligible published Review with pairing
content, or **Manual** to search for and select a Review. The card shows the
selected title, date, and artwork. Switching to Automatic retains your manual
choice for later. An unavailable choice is flagged without exposing private
article details.

Edit the band's kicker, heading, and paragraph. The three pairing films still
come from the selected Review. These controls change the homepage placement;
edit the Review itself to change its pairing content.

The backdrop uses the common image controls: **Replace image**, **Remove
image**, and **Use source image**, plus focal point, fit, and zoom. The preview
shows the chosen frame. This is a decorative desktop backdrop; it remains
hidden at widths of 820px or less, as before.

Existing settings retain their old public behavior until Apply. Once an
explicit Manual mode is applied, an empty or unavailable choice hides Method
and shows an editor warning. Automatic retains the existing bounded eligibility
search. Restoring a historical revision restores its original settings and
removes framing overrides that did not exist in that revision.

## Oscars Portal

The Portal uses the same Preview, Apply, Discard, and history controls. Edit
its existing headings and labels, order all eleven sections with drag or move
buttons, and change visibility and presentation spacing, heights, and rhythm.

The board is driven by its content. Navigator visibility follows the doors
section; it is shown as a derived setting. Existing Portal settings and revision
history remain authoritative. The old Portal form routes to this workspace.

Homepage Oscar Picks/Facts and Academy record, poster, and portrait tools are
separate controls. They are not migrated by this release.

## Mobile artwork

The legacy homepage Journal layout now stacks artwork above full-width text
on phones and tablets up to 820px. Images have a stable frame and long headlines
remain readable. This does not automatically adopt the newer Journal carousel
or replace a saved lineup; use its own Apply action for that.

Homepage Oscars cards fit inside their panel, with one full card per view at
these widths. Portrait artwork uses its original proportions inside the image
frame instead of a desktop landscape thumbnail. The arrows and dots sit above
the cards. Desktop keeps its existing landscape presentation.

## Next editors

Continue with Homepage Oscar Picks/Facts and reusable media, then the Review,
Journal Desk, and Academy authoring surfaces. Each migration must reuse these
controls while preserving its publishing rules and saved content.
