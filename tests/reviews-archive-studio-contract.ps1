$ErrorActionPreference = 'Stop'

$themeRoot = Split-Path -Parent $PSScriptRoot
$testsRoot = Join-Path $themeRoot 'tests'

function Assert-True {
    param(
        [bool] $Condition,
        [string] $Message
    )

    if (-not $Condition) {
        throw $Message
    }
}

function Read-ThemeFile {
    param([string] $RelativePath)

    $path = Join-Path $themeRoot $RelativePath
    Assert-True (Test-Path -LiteralPath $path) "Missing expected file: $RelativePath"
    return Get-Content -LiteralPath $path -Raw
}

$runtime = & php (Join-Path $testsRoot 'reviews-archive-studio-runtime.php') 2>&1
Assert-True ($LASTEXITCODE -eq 0) ("Reviews Archive Studio runtime failed: " + ($runtime -join [Environment]::NewLine))
Assert-True (($runtime -join "`n") -match 'reviews-archive-studio-runtime: all assertions passed') 'The Reviews Archive Studio runtime did not report success.'

$loader          = Read-ThemeFile 'functions-loader.php'
$studio          = Read-ThemeFile 'inc/reviews-archive-studio.php'
$control         = Read-ThemeFile 'inc/control-desk.php'
$helpers         = Read-ThemeFile 'inc/helpers.php'
$rendering       = Read-ThemeFile 'inc/review-rendering.php'
$frontend        = Read-ThemeFile 'inc/frontend.php'
$critical        = Read-ThemeFile 'inc/review-archive-critical.php'
$archiveTemplate = Read-ThemeFile 'archive-review.php'
$pageTemplate    = Read-ThemeFile 'page-reviews.php'
$adminJs         = Read-ThemeFile 'assets/js/lunara-control-desk.js'
$routeCss        = Read-ThemeFile 'assets/css/lunara-review-archive.css'
$browserRuntime  = Read-ThemeFile 'tests/reviews-archive-first-paint-runtime.js'
$style           = Read-ThemeFile 'style.css'

# One focused module owns the bounded public configuration, preview, audit, and query contract.
Assert-True ($loader -match "require_once\s+\`$lunara_inc\s*\.\s*'reviews-archive-studio\.php';") 'The split loader must load the Reviews Archive Studio module.'
foreach ($function in @(
    'lunara_reviews_archive_studio_defaults',
    'lunara_reviews_archive_studio_expand_section_order',
    'lunara_reviews_archive_studio_get_new_fields',
    'lunara_reviews_archive_studio_scalar_or',
    'lunara_reviews_archive_studio_normalize_public_shape',
    'lunara_reviews_archive_studio_repair_public_config',
    'lunara_reviews_archive_studio_get_public_config',
    'lunara_reviews_archive_studio_degrade_invalid_references',
    'lunara_reviews_archive_studio_validate_post_id',
    'lunara_reviews_archive_studio_validate_attachment_id',
    'lunara_reviews_archive_studio_attachment_below_wide_target',
    'lunara_reviews_archive_studio_safe_https_url',
    'lunara_reviews_archive_studio_text',
    'lunara_reviews_archive_studio_textarea',
    'lunara_reviews_archive_studio_validate_config',
    'lunara_reviews_archive_studio_config_from_request',
    'lunara_reviews_archive_studio_apply_config',
    'lunara_reviews_archive_studio_get_revisions',
    'lunara_reviews_archive_studio_push_revision',
    'lunara_reviews_archive_studio_promote_config',
    'lunara_reviews_archive_studio_restore_revision',
    'lunara_reviews_archive_studio_cache_urls',
    'lunara_reviews_archive_studio_flush_route_cache',
    'lunara_reviews_archive_studio_timestamp',
    'lunara_reviews_archive_studio_store_preview',
    'lunara_reviews_archive_studio_get_preview_config',
    'lunara_reviews_archive_studio_invalid_stage_key',
    'lunara_reviews_archive_studio_feedback_key',
    'lunara_reviews_archive_studio_bound_invalid_stage',
    'lunara_reviews_archive_studio_store_invalid_stage',
    'lunara_reviews_archive_studio_get_invalid_stage',
    'lunara_reviews_archive_studio_clear_invalid_stage',
    'lunara_reviews_archive_studio_store_feedback',
    'lunara_reviews_archive_studio_bound_feedback_code',
    'lunara_reviews_archive_studio_validation_messages',
    'lunara_reviews_archive_studio_validation_message',
    'lunara_reviews_archive_studio_is_reviews_family_request',
    'lunara_reviews_archive_studio_send_private_no_store',
    'lunara_reviews_archive_studio_prepare_preview_response',
    'lunara_reviews_archive_studio_guard_preview_request',
    'lunara_reviews_archive_studio_preflight_preview_query',
    'lunara_reviews_archive_studio_get_newest_id',
    'lunara_reviews_archive_studio_get_lead_id',
    'lunara_reviews_archive_studio_priority_orderby',
    'lunara_reviews_archive_studio_retention_media_markup',
    'lunara_reviews_archive_studio_gallery_media_markup',
    'lunara_reviews_archive_studio_render_gallery',
    'lunara_reviews_archive_studio_is_gallery_request',
    'lunara_reviews_archive_studio_compose_retention_lane',
    'lunara_reviews_archive_studio_search_posts',
    'lunara_reviews_archive_studio_ajax_search_posts',
    'lunara_reviews_archive_studio_editor_posts',
    'lunara_reviews_archive_studio_render_control_surface',
    'lunara_reviews_archive_studio_handle_save',
    'lunara_control_desk_restore_reviews_archive_studio',
    'lunara_control_desk_preview_reviews_archive_studio'
)) {
    Assert-True ($studio -match ("function\s+" + [regex]::Escape($function) + "\s*\(")) "Missing bounded Reviews Studio function: $function"
}
Assert-True ($studio -match "define\(\s*'LUNARA_REVIEWS_ARCHIVE_STUDIO_OPTION'\s*,\s*'lunara_reviews_archive_studio_public'\s*\)") 'The Studio public option constant must keep its canonical name.'
Assert-True ($studio -match "define\(\s*'LUNARA_REVIEWS_ARCHIVE_STUDIO_REVISIONS_OPTION'\s*,\s*'lunara_reviews_archive_studio_revisions'\s*\)") 'The Studio revisions option constant must keep its canonical name.'
Assert-True ($studio -match "define\(\s*'LUNARA_REVIEWS_ARCHIVE_STUDIO_REVISION_LIMIT'\s*,\s*12\s*\)") 'Reviews Studio history must be bounded to twelve revisions.'

# Save/restore/preview handlers stay nonce- and capability-gated; the legacy
# Control Desk save registration delegates into the module.
Assert-True ($control -match "add_action\(\s*'admin_post_lunara_save_reviews_archive_studio'\s*,\s*'lunara_control_desk_save_reviews_archive_studio'\s*\)") 'The Reviews Studio save action must stay registered through the Control Desk owner.'
Assert-True ($control -match 'function\s+lunara_control_desk_save_reviews_archive_studio[\s\S]{0,400}lunara_reviews_archive_studio_handle_save') 'The legacy save handler must delegate to the module save flow when loaded.'
Assert-True ($studio -match "add_action\(\s*'admin_post_lunara_restore_reviews_archive_studio'\s*,\s*'lunara_control_desk_restore_reviews_archive_studio'\s*\)") 'The Reviews Studio restore action must be registered.'
Assert-True ($studio -match "add_action\(\s*'admin_post_lunara_preview_reviews_archive_studio'\s*,\s*'lunara_control_desk_preview_reviews_archive_studio'\s*\)") 'The Reviews Studio preview action must be registered.'
Assert-True ($studio -match "check_admin_referer\(\s*'lunara_save_reviews_archive_studio'\s*,\s*'lunara_reviews_archive_nonce'\s*\)") 'Reviews Studio save must retain its nonce.'
Assert-True ($studio -match "check_admin_referer\(\s*'lunara_restore_reviews_archive_studio'\s*,\s*'lunara_reviews_archive_restore_nonce'\s*\)") 'Reviews Studio restore must have its own nonce.'
Assert-True ($studio -match "check_admin_referer\(\s*'lunara_preview_reviews_archive_studio'\s*,\s*'lunara_reviews_archive_preview_nonce'\s*\)") 'Unsaved preview must use a nonce distinct from public save.'
Assert-True ($studio -match "current_user_can\(\s*'edit_theme_options'\s*\)") 'Reviews Studio mutations and previews must be capability-gated.'
Assert-True ($studio -match 'function\s+lunara_reviews_archive_studio_handle_save[\s\S]{0,600}current_user_can[\s\S]{0,300}check_admin_referer') 'The save flow must check capability before its nonce work.'
foreach ($noticeCode in @('reviews_archive_studio_saved','reviews_archive_studio_invalid','reviews_archive_studio_restored','reviews_archive_studio_restore_invalid','reviews_archive_studio_forbidden')) {
    Assert-True ($control -match ("'" + $noticeCode + "'\s*=>")) "Control Desk must visibly map Reviews handler result $noticeCode."
}
Assert-True ($studio -match 'lunara_reviews_archive_studio_store_invalid_stage\(\s*\$candidate\s*,\s*\$_POST[\s\S]{0,200}get_error_code') 'Rejected saves must retain a bounded private draft and allowlisted reason.'
Assert-True ($studio -match 'function\s+lunara_reviews_archive_studio_validation_messages[\s\S]*?reviews_archive_identity_required[\s\S]*?reviews_archive_geometry_invalid[\s\S]*?reviews_archive_preview_forbidden') 'Validator feedback must use an explicit safe reason allowlist.'
Assert-True ($studio -match 'Your rejected edits are restored below and remain private') 'The focused surface must explain that rejected values are private and public state is unchanged.'

# Preview responses are private before any token work; director routes are never preview-eligible.
Assert-True ($studio -match 'nocache_headers\(\)[\s\S]*?Cache-Control:\s*private,\s*no-store') 'Preview wrapper and route responses must be explicitly non-cacheable.'
Assert-True ($studio -match 'function\s+lunara_reviews_archive_studio_prepare_preview_response[\s\S]*?lunara_reviews_archive_studio_send_private_no_store[\s\S]*?status[^\r\n]*403') 'Preview responses must become no-store before invalid, expired, guessed, anonymous, or foreign tokens are denied.'
Assert-True ($studio -match "add_action\(\s*'template_redirect'\s*,\s*'lunara_reviews_archive_studio_guard_preview_request'\s*,\s*0\s*\)") 'Every Reviews preview query must be guarded before template rendering.'
Assert-True ($studio -match "add_action\(\s*'pre_get_posts'\s*,\s*'lunara_reviews_archive_studio_preflight_preview_query'\s*,\s*1\s*\)") 'Invalid preview tokens must be denied before the Reviews query composes Studio state.'
Assert-True ($studio -match 'hash_equals[\s\S]*?get_current_user_id') 'Preview retrieval must bind an unguessable token to the current authorized user.'
Assert-True ($studio -match 'set_transient[\s\S]*?lunara_reviews_archive_preview') 'Unsaved previews must be short-lived and must not replace public state.'
Assert-True ($studio -match 'data-lunara-reviews-preview-frame="desktop"[\s\S]*?data-lunara-reviews-preview-frame="mobile"') 'Preview output must render actual desktop and mobile frames.'
Assert-True ($studio -match 'data-lunara-reviews-preview-frame="mobile"[^>]*style="[^"]*width:\s*390px') 'The mobile preview frame must have a real 390px viewport.'

# Director exemption: the family, gallery gate, cache scope, shell, and
# authority variables all read pure defaults or exclude director term routes.
Assert-True ($studio -match "function\s+lunara_reviews_archive_studio_is_reviews_family_request\(\)\s*\{\s*return\s+is_post_type_archive\(\s*'review'\s*\)\s*\|\|\s*is_page\(\s*'reviews'\s*\)\s*\|\|\s*is_page_template\(\s*'page-reviews\.php'\s*\);") 'The preview-eligible family must be exactly the archive, hub page, and hub template.'
Assert-True ($studio -notmatch "function\s+lunara_reviews_archive_studio_is_reviews_family_request\(\)\s*\{[\s\S]{0,300}lunara_director") 'The preview family body must never include director term routes.'
Assert-True ($studio -match "function\s+lunara_reviews_archive_studio_is_gallery_request[\s\S]{0,240}is_tax\(\s*'lunara_director'\s*\)") 'The archive-only gallery must exclude director term routes by construction.'
Assert-True ($studio -match "\`$query->is_post_type_archive\(\s*'review'\s*\)\s*\|\|\s*\`$query->is_page\(\s*'reviews'\s*\)") 'The pre-query preflight must resolve only the archive and hub-page family.'
Assert-True ($studio -notmatch 'get_term_link') 'Studio cache invalidation must never collect director or taxonomy term URLs.'
Assert-True ($rendering -match '\$is_director_archive\s*\?\s*lunara_reviews_archive_studio_defaults\(\)\s*:\s*lunara_reviews_archive_studio_get_public_config\(\)') 'The shell must hand director archives the pure Studio defaults, never saved state.'
Assert-True ($frontend -match 'lunara_reviews_archive_studio_get_public_config\(\s*!\s*\$is_director_archive\s*\)') 'Authority geometry must disable preview overrides on Studio-exempt director routes.'

# Query wiring: item count and curated priority compose behind the untouched pin owner.
Assert-True ($studio -match "add_filter\(\s*'posts_orderby'\s*,\s*'lunara_reviews_archive_studio_priority_orderby'\s*,\s*19\s*,\s*2\s*\)") 'The curated CASE must run at priority 19, before the pin CASE prepends.'
Assert-True ($rendering -match "add_filter\(\s*'posts_orderby'\s*,\s*'lunara_reviews_archive_pinned_orderby'\s*,\s*20\s*,\s*2\s*\)") 'The existing pin orderby at priority 20 must stay untouched.'
Assert-True ($rendering -match '\$curated_id\s*&&\s*\$curated_id\s*!==\s*\$studio_pinned_id') 'The query composer must subtract the pinned ID from curated priority IDs.'
Assert-True ($rendering -match 'lunara_reviews_archive_studio_get_public_config\(\)[\s\S]{0,400}item_count[\s\S]{0,200}posts_per_page') 'The validated Studio item count must drive the Reviews posts_per_page.'
Assert-True (($studio -match '\$tie_direction\s*=') -and ($studio -match '\.ID\s')) 'Priority SQL must include a deterministic post-ID tie-breaker after the selected secondary sort.'
Assert-True ($studio -notmatch "'posts_per_page'\s*=>\s*-1") 'The private Reviews picker must never load the complete archive in one query.'

# AJAX search: authenticated, capability-first, nonce-bound, bounded to twenty.
Assert-True ($studio -match "add_action\(\s*'wp_ajax_lunara_reviews_archive_studio_search'\s*,\s*'lunara_reviews_archive_studio_ajax_search_posts'\s*\)") 'Older published Reviews must remain reachable through an authenticated server-side search action.'
Assert-True ($studio -notmatch 'wp_ajax_nopriv_lunara_reviews_archive_studio_search') 'The private Reviews picker must not expose an unauthenticated search endpoint.'
Assert-True ($studio -match "function\s+lunara_reviews_archive_studio_ajax_search_posts[\s\S]{0,1200}current_user_can\(\s*'edit_theme_options'\s*\)[\s\S]{0,600}check_ajax_referer\(\s*'lunara_reviews_archive_studio_search'\s*,\s*'nonce'\s*\)") 'Reviews search must enforce theme-edit capability before its own AJAX nonce.'
Assert-True ($studio -match 'min\(\s*20\s*,\s*\$limit\s*\)') 'Reviews post search must clamp to an explicit maximum of twenty results.'
Assert-True ($control -match "'reviewsSearchNonce'\s*=>\s*wp_create_nonce\(\s*'lunara_reviews_archive_studio_search'\s*\)") 'The private admin script must receive only the bounded Reviews search nonce.'
Assert-True (($adminJs -match 'archiveStudioVariant') -and ($adminJs -match 'lunara_reviews_archive_studio_search')) 'The shared admin picker must route Reviews searches to the Reviews AJAX action.'
Assert-True ($adminJs -match "lunara_reviews_archive_curated_ids\[\]") 'The curated-run builder must emit the Reviews curated field name.'

# The focused UI reuses canonical owners and bounded fields.
foreach ($setting in @(
    'lunara_reviews_archive_kicker',
    'lunara_reviews_archive_title',
    'lunara_reviews_archive_copy',
    'lunara_reviews_archive_section_order',
    'lunara_reviews_archive_density',
    'lunara_reviews_archive_lead_prominence',
    'lunara_reviews_archive_rail_density'
)) {
    Assert-True (($studio + $control) -match [regex]::Escape($setting)) "Reviews Studio must reuse existing owner $setting."
}
$reviewsUi = $studio + $control
Assert-True ($studio -match 'lunara_set_pinned_review_id') 'The Studio must write the lead only through the canonical pin helper.'
Assert-True ($studio -notmatch "'lead_id'\s*=>\s*\`$config\['lead_id'\]") 'The Studio option payload must never store a lead ID; the pin meta is the whole lead state.'
Assert-True ($reviewsUi -match 'name="lunara_reviews_archive_item_count"[\s\S]{0,200}min="4"[\s\S]{0,60}max="24"') 'Reviews page count must be bounded locally from four to twenty-four.'
Assert-True ($reviewsUi -match 'name="lunara_reviews_archive_curated_ids\[\]"') 'Curated entries must be chosen from published posts without raw ID entry.'
Assert-True ($reviewsUi -match "lunara_reviews_archive_retention\[%d\]\[image_id\]") 'Retention cards must use Media Library attachment IDs.'
Assert-True ($reviewsUi -match 'data-lunara-brand-media-picker') 'Retention media must use the existing WordPress Media Library picker.'
foreach ($galleryField in @('alt','caption','link_url','credit','source','source_url','focal_x','focal_y')) {
    Assert-True ($studio -match ("lunara_reviews_archive_gallery_" + [regex]::Escape($galleryField))) "Archive gallery editor is missing per-image field $galleryField."
}
Assert-True ($studio -match 'count\(\s*\$raw_items\s*\)\s*>\s*12') 'Archive gallery capacity must remain bounded to twelve Media Library images.'
Assert-True ($reviewsUi -match 'Configuration History[\s\S]*?Restore this revision') 'Reviews Studio must expose its bounded audit/restore history.'
Assert-True ($reviewsUi -match 'Preview unsaved desktop \+ mobile') 'Reviews Studio must offer a true pre-save responsive preview.'
Assert-True ($studio -match "'validator_result'\s*=>[\s\S]*?'prior_public'\s*=>") 'Every revision must record validation and prior-public audit semantics.'

# Public rendering consumes the last-valid config without publication authority.
Assert-True ($archiveTemplate -match 'lunara_reviews_archive_studio_get_public_config') 'The archive template must resolve the last-valid Studio identity.'
Assert-True ($pageTemplate -match 'lunara_reviews_archive_studio_get_public_config') 'The hub page template must resolve the last-valid Studio identity.'
Assert-True ($rendering -match 'lunara_reviews_archive_studio_compose_retention_lane\(\s*\$retention_showcase_markup\s*,\s*\$gallery_markup\s*,\s*\$has_posts\s*\)') 'The shell must delegate retention/gallery output to the behavior-tested compositor.'
Assert-True ($rendering -match 'lunara_reviews_archive_studio_is_gallery_request\(\)') 'The archive media lanes must be gated to the canonical root Reviews index.'
Assert-True ($studio -notmatch '\bwp_(insert|update)_post\s*\(') 'Reviews Studio must never insert, publish, schedule, or rewrite posts.'
Assert-True ($studio -notmatch "post_status\s*=>\s*'(publish|future)'") 'Reviews Studio must not grant publication or scheduling authority.'
Assert-True ($studio -notmatch '\bwp_cache_flush\s*\(') 'Reviews saves must never trigger a global object-cache purge.'
Assert-True ($studio -match "wp_cache_delete\(\s*'reviews_archive_studio_public'\s*,\s*'lunara'\s*\)") 'Reviews saves must invalidate only their route/data cache key.'
Assert-True ($studio -notmatch 'rocket_clean_domain') 'Reviews saves must never purge the full WP Rocket domain cache.'
$validationPos = $studio.IndexOf('lunara_reviews_archive_studio_validate_config')
$applyPos      = $studio.IndexOf('lunara_reviews_archive_studio_apply_config')
Assert-True ($validationPos -ge 0 -and $applyPos -gt $validationPos) 'Validation must exist before public state is applied.'

# Label typography: the resolver validates the raw token exactly (no
# sanitize_key normalization), and only the resolver emits the route marker.
Assert-True (($critical -match 'function\s+lunara_reviews_archive_resolved_label_font_slug') -and ($critical -match 'function\s+lunara_reviews_archive_uses_tiempos_label_face')) 'Reviews licensed-label activation must be derived from the resolved Studio font token.'
Assert-True ($critical -notmatch 'function\s+lunara_reviews_archive_resolved_label_font_slug[\s\S]{0,900}sanitize_key') 'The Reviews resolver must validate the raw scalar token exactly instead of normalizing corrupt near-matches into valid custom choices.'
Assert-True ($rendering -match 'lunara_reviews_archive_uses_tiempos_label_face\(\)[\s\S]{0,120}is-label-font-tiempos') 'The shared shell must emit the Tiempos marker only through the route-aware resolver.'
Assert-True (([regex]::Matches($rendering, 'is-label-font-tiempos')).Count -eq 1) 'Exactly one resolver-guarded shell site may emit the Tiempos marker.'
Assert-True (($archiveTemplate -notmatch 'is-label-font-tiempos') -and ($pageTemplate -notmatch 'is-label-font-tiempos')) 'Templates must never hard-code the Tiempos marker around the resolver.'
Assert-True ($routeCss -match '#primary#primary\.lunara-review-archive-page\.is-label-font-tiempos[\s\S]{0,1100}font-family\s*:\s*"Lunara Review Tiempos Text",\s*Georgia,\s*"Times New Roman",\s*serif\s*!important') 'The default label token must settle Reviews controls on the licensed Tiempos face with an owned fallback.'
Assert-True ($routeCss -notmatch 'is-label-font-tiempos[^}]*\.lunara-review-feature-(?:title|excerpt)|is-label-font-tiempos[^}]*\.lunara-archive-hero-(?:title|copy)') 'The route-owned Tiempos label face must never own Reviews prose or display typography.'
Assert-True ($frontend -match "function\s+lunara_preload_reviews_archive_label_font[\s\S]{0,900}lunara_reviews_archive_uses_tiempos_label_face[\s\S]{0,500}TiemposText-Bold\.woff2[\s\S]{0,300}add_action\(\s*'wp_head'\s*,\s*'lunara_preload_reviews_archive_label_font'\s*,\s*4\s*\)") 'Only a default-token Reviews route may preload the heavier licensed label face at wp_head 4.'
Assert-True ($frontend -match "function\s+lunara_keep_review_archive_css_unaggregated[\s\S]{0,500}lunara-review-archive[\s\S]{0,300}add_filter\(\s*'css_do_concat'") 'Jetpack Boost must not aggregate the Reviews route stylesheet.'

# First-paint budgets: the universal seed guards the retention slot inside 12 KB
# and the cacheable route CSS stays inside its 45 KB budget.
$seedPayload = @{
    orders = @{ hero = 1; utility = 2; grid = 2; pagination = 3; 'pairing-desk' = 4 }
    visibility = @{ hero = $true; grid = $true; 'pairing-desk' = $true }
} | ConvertTo-Json -Compress -Depth 5
$seedEncoded = [Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes($seedPayload))
$seedOutput = & php (Join-Path $testsRoot 'reviews-archive-critical-render.php') $seedEncoded
Assert-True ($LASTEXITCODE -eq 0) 'The pure critical-CSS renderer must execute successfully.'
$seed = ($seedOutput -join [Environment]::NewLine)
$seedBytes = [Text.Encoding]::UTF8.GetByteCount($seed)
Assert-True ($seedBytes -gt 0 -and $seedBytes -le 12288) "The universal structural seed must stay at or below 12 KB; measured $seedBytes bytes."
Assert-True ($seed.Contains('&>.lunara-review-archive-slot-retention{margin:0!important;order:var(--lunara-reviews-archive-order-grid,2)!important;width:100%!important}')) 'The structural seed must guard the retention slot with the grid-lane variable.'
Assert-True ($seed.Contains('& .lunara-review-archive-gallery{min-width:0!important;width:100%!important}')) 'The structural seed must guard archive gallery width before deferred CSS settles.'
$routeCssBytes = (Get-Item -LiteralPath (Join-Path $themeRoot 'assets/css/lunara-review-archive.css')).Length
Assert-True ($routeCssBytes -gt 30000 -and $routeCssBytes -le 45000) "Cacheable Reviews route CSS must stay within 45 KB; measured $routeCssBytes bytes."

# The first-paint browser fixture models the new Studio surfaces.
Assert-True ($browserRuntime -match 'class="lunara-review-archive-retention lunara-review-archive-slot-retention"') 'The first-paint fixture must model the Studio retention slot markup.'
Assert-True ($browserRuntime -match 'lunara-review-archive-gallery-media') 'The first-paint fixture must model the root archive gallery media chamber.'
Assert-True ($browserRuntime -match 'lunara-review-archive-page lra is-label-font-tiempos') 'The first-paint fixture must exercise the resolver-emitted Tiempos label marker.'
Assert-True ($browserRuntime -match 'showRetention:\s*false') 'The director first-paint fixture must omit the Studio-exempt retention lane.'

# Version lock: this intentionally asserts the NEXT reissue identity. It is
# EXPECTED to fail until the 3.2.50 version migration lands as its own step;
# every assertion above it must already pass on the pre-migration tree.
Assert-True ($style -match '(?m)^Version:\s*3\.2\.50\s*$') 'Theme version must be 3.2.50.'

Write-Host 'reviews-archive-studio: all assertions passed.'
