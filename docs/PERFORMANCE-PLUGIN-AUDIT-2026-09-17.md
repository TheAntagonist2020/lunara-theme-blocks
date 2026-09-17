# Lunara Film plugin performance audit — September 17, 2026

## Result

Production site 247355955 was inspected through the authenticated plugin inventory,
settings pages and anonymous Home, Journal, Reviews and Oscars responses.
Dalton explicitly authorized deactivation of unused or redundant plugins.

**Before: 59 installed / 37 active / 22 inactive. After: 59 installed / 34 active /
25 inactive.** Three plugins were deactivated, not uninstalled. No content,
media, backup files or saved patterns were deleted. Theme production remains
**3.2.85+20260917-031752**; the separate 3.2.86 source candidate is not deployed.

## Changes made live

| Item | Evidence | Action |
| --- | --- | --- |
| UpdraftPlus 1.26.7 | Neither files nor database has a schedule. Two old backup sets, March 19 and April 1; last displayed log reports binary ZIP error 127. | Plain deactivation; existing backup data retained. WordPress.com managed backups continue independently. |
| WP Social 3.2.1 | Every sharing and counter provider off; all automatic social-login placements off. Several login providers have saved ON states, but no published `xs_social` shortcode references, theme integration or controls on the four inspected routes. | Deactivated; configuration retained. |
| Galleryberg 1.2.0 | No references in published searchable content, non-public standard posts/pages, reusable patterns, theme source or inspected routes. Non-public custom post types were not exhaustively exported. | Deactivated reversibly; no gallery content deleted. |
| Jetpack Boost Image Guide | Diagnostic overlay was enabled; this is an administrator aid. | Switched off and confirmed after reload. Image CDN stays on. No visitor-speed claim is attributed to this change. |

The plugin API confirmed each deactivation and site reachability; a final inventory
confirmed all three inactive. Each can be restored with **Plugins → Activate**.
No cache purge or deployment was triggered.

## Measured public asset reduction

All four anonymous canonical routes returned HTTP 200, their expected build identity
and no missing-config/critical-error message after the final cleanup.

| Principal CSS bundle | Before, bytes | After, bytes | Reduction |
| --- | ---: | ---: | ---: |
| Home | 914,848 | 802,434 | 112,414 |
| Journal | 793,913 | 681,499 | 112,414 |
| Reviews | 806,148 | 693,734 | 112,414 |
| Oscars | 970,121 | 857,707 | 112,414 |

These are **uncompressed CSS source bytes**, not compressed network transfers or
a measured change in load time. The reduction is about 110 KiB per principal bundle,
or 11.6–14.2%. All resulting bundles have zero WP Social paths/`wslu` rules.
The removed segment included 67,937 bytes of social styles and 44,477 bytes of
icon-font declarations/rules. Font files were referenced externally; their download
savings were not measured. A further 2,995-byte WP Social JavaScript segment was
identified before cleanup but is not included in the verified CSS reduction.
Galleryberg/UpdraftPlus produced no separately claimed public byte saving.

Boost's async/noscript links repeat stylesheet URLs; that is not proof of duplicate
downloads. The two `rocket` text matches were WP Social icon names, not active
WP Rocket code.

## What should remain responsible for optimization

| Layer | Observed role | Decision |
| --- | --- | --- |
| WordPress.com hosting | Boost confirms managed page caching already runs. Hosting provides object caching. | Keep hosting cache; no additional cache plugin needed. |
| Jetpack core | Site Accelerator, image CDN and static-file acceleration enabled. Static acceleration covers supported core/Jetpack/WooCommerce files, not all theme/plugin assets. | Keep. |
| Jetpack Boost 4.7.1 | Critical CSS, LCP images, deferred JavaScript, concatenated JS/CSS, image CDN enabled. | Keep as the single CSS/JS optimization owner. |
| Cimo Premium 1.4.2 | Browser/upload-time compression; dashboard reports 739 optimized files and 418.26 MB saved. LQIP off, WordPress image scaling and all 13 thumbnail sizes on. | Keep for its distinct upload/storage work. It is not recompressing every visitor request. |
| WP Rocket | Already inactive before this audit. | Leave inactive; no running duplicate to remove. |

**Jetpack core alone does not replace every Boost feature.** Jetpack plus Boost,
together with the host cache, is the existing coherent performance setup. Cimo
serves a different upload/storage purpose and is optional by workflow preference,
not an established runtime duplicate.

The Boost image-CDN and Jetpack Photon controls are synchronized in the installed
Boost 4.7.1 source. Switching off one can switch off the other; they do not indicate
two independent image-processing chains. The CDN qualities remain JPEG 82,
PNG 80 and WebP 80. Auto-resize lazy images remains off.

## Retained dependencies and limits

- Stackable is referenced by Home (4055), Journal article 33228, and saved pattern
  99603. Pattern **JOURNAL PATTERN GUTEN** also contains a GutenKit container.
  Both builders therefore remain. Their absence from the four rendered main
  routes alone was insufficient evidence to remove them.
- GutenKit base CSS contributed 1,218 bytes to each sampled bundle. This does
  not explain the large total stylesheet payload.
- Blocksy is the required parent theme; Companion's extension configuration and
  custom fonts warrant retention. ACF PRO/ACF Extended and GutenKit/Pro are
  dependency pairs, not automatically duplicates.
- Code Snippets Pro has four active functional snippets; the old visual snippets
  inspected are already inactive.
- WP All Export contains a Reviews export that last exported 261 records on
  July 28. It is configured, so it was retained. WordPress Importer remains an
  on-demand utility; no material runtime duplication was demonstrated.
- Jetpack Social publishing and VideoPress video hosting are distinct from
  WP Social's sharing/login/counter controls.
- The bespoke Lunara, AI/provider, MCP, security and editorial tools remain.
  This performance audit does not establish their individual use frequency.
- The 22 previously inactive plugins were not deleted. They were not part of
  the normal active-plugin runtime; deleting them is maintenance, not a proven
  public-page speed improvement.

## Initial active inventory and final decisions

| Plugin | Installed version | Final decision |
| --- | --- | --- |
| Advanced Custom Fields: Extended | 0.9.2.7 | Retained; no demonstrated performance redundancy |
| Advanced Custom Fields PRO | 6.8.7 | Retained; no demonstrated performance redundancy |
| AI | 1.3.0 | Retained; no demonstrated performance redundancy |
| AI Provider for Anthropic | 1.0.4 | Retained; no demonstrated performance redundancy |
| AI Provider for Google | 1.1.1 | Retained; no demonstrated performance redundancy |
| AI Provider for OpenAI | 1.1.0 | Retained; no demonstrated performance redundancy |
| Akismet Anti-spam: Spam Protection | 5.7.2 | Retained; no demonstrated performance redundancy |
| Blocksy Companion (Premium) | 2.1.56 | Keep: active Blocksy ecosystem/custom-font configuration |
| Cimo - Image Optimizer (Premium) | 1.4.2 | Keep: upload-time compression and storage savings |
| Classic Editor | 1.7.0 | Retained; no demonstrated performance redundancy |
| Code Snippets Pro (Premium) | 3.9.6 | Keep: four active functional snippets; 17 others already inactive |
| Deployer for Git (Pro) | 1.0.12 | Retained; no demonstrated performance redundancy |
| Galleryberg Gallery Block | 1.2.0 | Deactivated; no gallery dependencies found |
| GutenKit Blocks | 2.5.1 | Keep: saved Journal pattern contains GutenKit container |
| GutenKit Blocks Pro | 2.3.10 | Keep with GutenKit; Pro feature dependencies not fully enumerated |
| HappyFiles Pro | 1.9 | Retained; no demonstrated performance redundancy |
| IsOnWP MCP Abilities | 0.8.0 | Retained; no demonstrated performance redundancy |
| Jetpack | 16.3-a.1 | Keep: image/static CDN and site services |
| Jetpack Boost | 4.7.1 | Keep: CSS/JS and LCP optimization; Image Guide switched off |
| Jetpack Social | 9.0.3 | Retained; no demonstrated performance redundancy |
| Jetpack VideoPress | 3.4.1 | Retained; no demonstrated performance redundancy |
| LUNARA AI Assistant Classic | 0.6.1 | Retained; no demonstrated performance redundancy |
| Lunara Core | 0.8.11 | Retained; no demonstrated performance redundancy |
| Lunara Database Engine | 1.2.0 | Retained; no demonstrated performance redundancy |
| Lunara Dispatch Automation | 3.2.8 | Retained; no demonstrated performance redundancy |
| Lunara Editorial Spotlight Block | 1.0.0 | Retained; no demonstrated performance redundancy |
| Lunara Film - Academy Awards Database | 2.7.86 | Retained; no demonstrated performance redundancy |
| Lunara IMDb Guard | 0.4.1 | Retained; no demonstrated performance redundancy |
| LUNARA Journal Foundation | 1.3.1 | Retained; no demonstrated performance redundancy |
| MCP Adapter | 0.5.0 | Retained; no demonstrated performance redundancy |
| Secure MCP Server for Claude, ChatGPT, Gemini and other AI providers | 1.4.12 | Retained; no demonstrated performance redundancy |
| Stackable - Gutenberg Blocks (Premium) | 3.19.9 | Keep: content and saved Journal pattern depend on it |
| UpdraftPlus - Backup/Restore | 1.26.7 | Deactivated; no scheduled backups, two old sets retained |
| Wordfence Login Security | 1.1.18 | Retained; no demonstrated performance redundancy |
| WordPress Importer | 0.9.6 | Retained; no demonstrated performance redundancy |
| WP All Export | 1.5.0 | Keep: configured Reviews export, last run July 28 |
| Wp Social | 3.2.1 | Deactivated; inactive placements/providers and no shortcode usage |

## Open performance work

Boost reports 24 generated critical-CSS files, with two generation failures.
Its advanced view groups errors around redirected or missing pick/fact taxonomy
URLs. No regeneration was requested during this cleanup. The Oscars inline
Boost critical CSS was 132,041 bytes before cleanup; large shared/theme CSS and
critical-CSS scope merit the next focused pass.

The dashboard's C / mobile 64 / desktop 75 scores are historical cached values,
not a fresh benchmark. No Lighthouse score, Core Web Vitals gain, end-to-end
export/backup test or cross-browser regression suite is claimed.

Theme 3.2.86 commit `b4c388d` fixes Journal responsive-image width hints.
Validation: two PHP syntax checks, one existing responsive-media runtime, diff
whitespace review, and 13 local Chrome iframe scenarios across grid breakpoints
and all density modes. All images loaded, CSS math parsed, and selected source
candidates matched expectation; width differences were at most 0.133 CSS px
under the current fractional device scale. No main merge or theme deployment
occurred.

Local evidence is under
`_carousel-artifacts/plugin-performance-20260917/` in the parent workspace:
before/after public HTML and asset bundles, `cleanup-verification.json`,
`focused-checks-record.txt`, and the local Journal sizing fixture.

## Primary references

- [WordPress.com cache architecture](https://wordpress.com/support/clear-your-sites-cache/)
- [Jetpack Site Accelerator](https://jetpack.com/support/site-accelerator/)
- [Jetpack Boost features](https://jetpack.com/support/jetpack-boost/)
- [Installed Boost 4.7.1 CDN mapping](https://plugins.svn.wordpress.org/jetpack-boost/tags/4.7.1/compatibility/jetpack.php)
- [Installed Boost 4.7.1 bidirectional synchronization](https://plugins.svn.wordpress.org/jetpack-boost/tags/4.7.1/compatibility/lib/class-sync-jetpack-module-status.php)
- [Cimo upload architecture](https://docs.wpcimo.com/article/795-frequently-asked-questions)
- [UpdraftPlus deactivation behavior](https://teamupdraft.com/updraftplus/changelog/)

