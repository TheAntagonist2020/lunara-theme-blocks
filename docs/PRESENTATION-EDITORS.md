# Site Studio presentation controls

Guide to the implemented controls through Theme 3.2.79. This is a usage guide,
not proof of deployment. Check [SESSION-LOG.md](SESSION-LOG.md) for verified live
state and [SITE-COMPLETION-PLAN.md](SITE-COMPLETION-PLAN.md) for remaining acceptance.

Open **Site Studio**, choose **Home**, **Reviews**, **Journal** or **Oscars**,
then choose that page's editor. Find **Site Footer**, **Search**, **404 Recovery**
and **Global Design** in the searchable editor directory. A supported public
section's signed-in **Edit this section** link opens its presentation editor.

## Preview, apply and recover

1. Edit the settings. Your changes remain an unsaved candidate.
2. Select **Preview changes** and inspect desktop and mobile. Further edits
   make that preview stale; preview again before judging the result.
3. Select **Apply changes** to update the public presentation, or **Discard
   changes** to return to the saved settings.
4. Use **History** to restore an earlier saved presentation.

Preview links are private, expire and belong to their signed-in creator.
The editor protects unsaved work when changing destinations and keeps the
candidate when a save fails. Resolve the displayed field error and retry.
Opening an editor does not apply settings. Where activation or adoption is
offered, preview the replacement and Apply deliberately.

## Home

| Editor | What it controls |
| --- | --- |
| **Page layout** | Section visibility and separate desktop/mobile order. Drag or use the move buttons. |
| **Hero stories** | One large story at a time, drawn from published Reviews and Journal articles. |
| **Latest Reviews** | The homepage review carousel, with portrait artwork from each Review's Card / Poster settings. |
| **Journal stories** | The homepage Journal carousel; Reviews are excluded. |
| **Lunara Method** | The source Review, band copy and decorative backdrop for the three-film pairing section. |
| **Oscar Picks** | Authored forecast lineup, ceremony year, section copy, card count, spacing, timing and placement artwork. |
| **Oscar Facts** | Authored fact lineup, section copy, card count, spacing, timing and placement artwork. |

The three story carousels have independent **Automatic** and **Manual** modes.
Automatic selects the six newest eligible published stories by publication
date. Manual supports search, add/remove, drag and keyboard ordering, with
homepage image, focal-point and text overrides. Each carousel has its own
heading and rotation controls. Switching modes retains the manual list;
**Use this lineup in Manual** explicitly copies the automatic lineup.

An applied empty Manual carousel hides its section; unavailable stories are
flagged and skipped. One eligible story is static. These selections do not
rewrite articles or control the corresponding archive. Latest Reviews keeps
its previous presentation until its carousel settings are applied; after
adoption, its old block settings point to Site Studio.

Method selects a Review automatically or manually; its three films come from
that Review's pairing content. Backdrop replacement, source reset, fit, focal
point and zoom affect this placement. The backdrop is hidden at 820px and below.
An applied empty or unavailable Manual selection hides Method and warns you.

Picks and Facts offer **Keep current selection**, **Automatic** and **Manual**.
Automatic uses newest published items; Picks also uses the selected ceremony
year. Manual order survives mode changes. An empty Manual list hides the band.
Image controls can replace or inherit artwork and adjust fit, focal point and
zoom for the homepage. A Fact's artwork verification still governs whether
an image may appear; change that in the Fact editor when needed.
Use **Edit Picks** / **Edit Facts** for the underlying entries and source artwork.

## Reviews and Journal pages

**Reviews → Reviews page** and **Journal → Journal page** control archive copy,
section visibility/order, layout, **Stories**, **Gallery** and **Continue reading**.

For an archive still using legacy selection, choose **Use these story controls**
before changing Stories. An unrelated Content or Layout save keeps its existing
selection behavior. The Journal's legacy shared lead is identified separately.

The featured story can be newest published or a selected Manual lead. Manual
requires an available published story before Preview/Apply. **Manual priorities**
places your ordered choices first, then keeps the rest of the archive available;
it does not turn the archive into an exclusive carousel. Filters and sorting
still apply. An empty priority list means no priorities. Mode switches retain
your choices, and unavailable selections are flagged.

Gallery accepts up to 12 images with order, focal point, alt text, captions,
links and attribution. Continue reading has three reorderable cards with
visibility, destinations, labels and optional images; Journal also has card
headings and descriptions. Selected images need the requested credit/source
details. These archive image controls use focal points, without separate fit
or zoom controls. Review continuation image cards need an image to appear;
Journal continuation cards can display without one.

## Individual articles

**Reviews → Review article layout** changes shared reading density, hero and
Debrief emphasis, sidebar treatment, spacing, related-review count, spoilers,
trailers and pairing layout. **Open Review Studio** takes you to the Review
content owner for the actual text, score, film details, imagery and pairings.

**Journal → Journal article layout** changes headline size, hero fit/focal
point and author/date/reading-time visibility for every Journal article.
Its preview uses the newest eligible published article. **Article text, images
and gallery** links to that article's editor and the Journal article list.
Change the Featured image, image description, Media Gallery and article text
there. The layout editor frames the selected source image; it does not replace
the article's image or write its text.

## Oscars

**Oscars → Oscars page** controls the Portal's copy, eleven-section order,
available visibility switches, layout, Hero button labels, four Quick Start
cards and **Winner sections**. Quick Start cards have their own visibility,
copy and destination. Latest winners offers a fallback heading and link label;
the ceremony name takes precedence when available. Rotating winners has its
own copy, link label, count and interval in seconds; zero stops automatic
advancing. Winners come from the Academy records, not a manual story list.
These winner controls belong to the Portal; there is no separate Home Winners
editor. Homepage Picks and Facts remain separate authored placements.

**Oscars → Ledger layouts** controls shared ceremony, category, film and person
dossier presentation, including preset packages, emphasis and dimensions.
Preview the representative **Ceremony**, **Category**, **Film** and **Person**
pages before Apply. A preset changes the candidate, which you can fine-tune.
Use **Awards Tracker**, **Poster Library**, **Person Portrait Queue** and
**Ceremony Write-Ups** for Academy records and artwork. These layout controls change
their presentation, leaving category identities and records intact.

## Footer, Search and missing pages

**Site Footer** controls logo visibility, tagline, three column headings,
copyright and ordered links. Start with inherited links or **Use these lists**.
Add, hide, remove or reorder up to 12 links per column; choose a built-in
destination or a custom URL. Built-in destinations follow current site URLs.
An empty custom column stays empty. **Use inherited links** restores the
standard lists on Apply. The former Footer Customizer writers are retired.

**Search** controls its kicker, optional empty-search heading, excerpt length,
result treatment/focus and shared Search/404 appearance. Enable **Use this
heading for an empty search** to adopt that text; ordinary result pages keep
their dedicated heading. A blank search invites a query and reports zero
matches. **404 Recovery** controls missing-page copy, guidance and the first
recovery destination; all five recovery links remain available. Its private
preview remains a genuine 404. Shared appearance is edited in **Search**.

## Presentation versus publishing

Presentation uses **Preview / Apply / Discard / History**. Article owners use
**Save draft**, **Publish** or **Update**; Academy tools maintain their records
and artwork. These are separate actions with separate consequences. Restoring
presentation history does not roll back article text or Academy data.

Next: curate the opening Home lineups, preview the corresponding archive and
article layouts, then check the public pages after the intended deployment.
Publishing fresh coverage is a separate editorial step.
