$ErrorActionPreference = 'Stop'

$themeRoot = Split-Path -Parent $PSScriptRoot

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
    Assert-True (Test-Path $path) "Missing expected file: $RelativePath"
    return Get-Content -Raw $path
}

$reviewRendering = Read-ThemeFile 'inc/review-rendering.php'
$reviewsCpt      = Read-ThemeFile 'inc/reviews-cpt.php'
$controlDesk     = Read-ThemeFile 'inc/control-desk.php'
$helpers         = Read-ThemeFile 'inc/helpers.php'
$frontend        = Read-ThemeFile 'inc/frontend.php'
$customizer      = Read-ThemeFile 'inc/customizer.php'
$archiveTemplate = Read-ThemeFile 'archive-review.php'
$pageTemplate    = Read-ThemeFile 'page-reviews.php'
$style           = Read-ThemeFile 'style.css'

# Featured card semantics: no full-card anchor may contain the Ledger link.
Assert-True ($reviewRendering -match '<div class="lunara-review-feature-layout lunara-review-feature-link">') 'Feature Review card must use a non-anchor layout wrapper.'
foreach ($linkClass in @('lunara-review-feature-media-link','lunara-review-feature-title-link','lunara-review-feature-cta')) {
    Assert-True ($reviewRendering.Contains($linkClass)) "Feature Review card must expose the separate $linkClass link."
}
Assert-True ($reviewRendering -notmatch '<a class="lunara-review-feature-link"[\s\S]{0,5000}lunara-review-feature-ledger') 'Feature Review card must not nest the Oscar Ledger anchor inside a full-card anchor.'

# Pin helpers must be live in the split loader, not trapped in functions.php fallback.
foreach ($helper in @('lunara_add_review_pin_meta_box','lunara_review_pin_meta_callback','lunara_save_review_pin_meta','lunara_get_pinned_review_id','lunara_set_pinned_review_id')) {
    Assert-True (($reviewsCpt + $reviewRendering) -match "function\s+$helper") "Active split modules must define $helper."
}
Assert-True (($reviewsCpt + $reviewRendering) -match "_lunara_review_pinned") 'Live pin helpers must keep the canonical pin metadata key.'
Assert-True ($reviewRendering -match 'lunara_reviews_archive_pinned_orderby') 'Pinned lead must be composed into SQL ordering so pagination stays unique.'
Assert-True ($reviewRendering -match 'add_filter\(\s*''posts_orderby''') 'Pinned lead must use a scoped posts_orderby filter.'
Assert-True ($reviewRendering -notmatch 'array_unshift\(\s*\$posts,\s*\$pinned_review') 'Renderer must not inject a tenth row after the paged query.'

# Focused Archive Studio controls reuse canonical storage and stay guarded.
foreach ($setting in @('lunara_reviews_archive_kicker','lunara_reviews_archive_title','lunara_reviews_archive_copy','lunara_reviews_archive_section_order')) {
    Assert-True ($controlDesk.Contains($setting)) "Reviews Archive Studio must edit $setting."
}
Assert-True ($controlDesk -match 'lunara_control_desk_reviews_archive_lead_options[\s\S]*posts_per_page[^\r\n]*=>\s*100') 'Lead selector must be a bounded published-Review query.'
Assert-True ($controlDesk -match 'lunara_set_pinned_review_id') 'Studio save must write through the canonical pin helper.'
Assert-True ($controlDesk -match 'lunara_get_reviews_archive_section_registry') 'Studio must render the canonical Reviews lane registry.'
Assert-True ($controlDesk -match 'lunara_sanitize_reviews_archive_section_order') 'Studio save must sanitize canonical lane order.'
Assert-True ($controlDesk -match 'check_admin_referer\(\s*''lunara_save_reviews_archive_studio''') 'Archive Studio must verify its nonce.'
Assert-True ($controlDesk -match 'current_user_can\(\s*''edit_theme_options''') 'Archive Studio must remain capability protected.'
Assert-True ($controlDesk -match 'lunara_control_desk_bounded_return_url') 'Archive Studio must keep a bounded return target.'

# The utility toolbar follows the grid lane truthfully and hero-off retains one H1.
Assert-True ($customizer -match "'grid'\s*===\s*\`$slug[\s\S]{0,250}lunara-review-archive-slot-utility\{order:") 'Utility toolbar must inherit the configured grid lane order.'
Assert-True ($reviewRendering -match 'function\s+lunara_get_review_archive_lane_order') 'Public rendering must resolve canonical saved lane positions.'
foreach ($slot in @('hero','utility','grid','pagination','pairing-desk')) {
    Assert-True ($reviewRendering -match ('lunara-review-archive-slot-' + [regex]::Escape($slot) + '[^>]+style="order:<\?php[^>]+\$lane_orders\[''' + [regex]::Escape($slot) + '''\][\s\S]{0,160}!important;"')) "Rendered $slot must harden its canonical lane order against stale critical CSS."
}
Assert-True ($reviewRendering -match '!\s*\$show_hero[\s\S]{0,300}<h1[^>]+lunara-review-archive-fallback-title') 'Hero-off archive must emit a fallback H1.'
Assert-True ($reviewRendering -match '\$show_grid\s*&&\s*\$show_pagination\s*&&\s*\$has_posts\s*&&\s*!\s*empty\(\s*\$args\[''pagination''\]\s*\)') 'A one-Review final page must retain navigation after the lead is extracted.'

# Release-year filtering is server-rendered and composes with sort/query/pagination.
foreach ($helper in @('lunara_get_review_archive_year','lunara_get_review_archive_year_options','lunara_apply_review_archive_year_args','lunara_get_review_archive_query_args')) {
    Assert-True ($reviewRendering -match "function\s+$helper") "Review archive must define $helper."
}
Assert-True ($reviewRendering -match 'name="review_year"') 'Archive toolbar must expose a no-JS release-year selector.'
Assert-True ($reviewRendering -match "taxonomy'\s*=>\s*'lunara_review_year'") 'Year filter must target the Review Year taxonomy.'
Assert-True ($reviewRendering -match '\$query_args\[''tax_query''\]') 'Year filter must preserve and extend tax_query.'
Assert-True ($frontend -match '\$query->is_post_type_archive\(\s*''review''\s*\)[\s\S]{0,650}lunara_get_review_archive_query_args') 'The native Review CPT main query must apply the composed sort, year, and pin contract.'
Assert-True ($archiveTemplate.Contains("'review_year'")) 'Archive pagination must preserve review_year.'
Assert-True ($pageTemplate.Contains('lunara_get_review_archive_query_args')) 'Reviews page query must compose sort and year helpers.'
Assert-True ($pageTemplate.Contains("'review_year'")) 'Reviews page pagination must preserve review_year.'
Assert-True ($reviewRendering.Contains("'sort', 'modified_desc'")) 'Recently Updated CTA must use modified_desc, not date_desc.'

# Image delivery: native attachment markup, bounded TMDB candidates, exact unknown fallback.
Assert-True ($reviewRendering -match 'wp_get_attachment_image\(\s*\$attachment_id,\s*\$size') 'Attachment cards must use the requested registered image size.'
Assert-True ($reviewRendering -match 'lunara_get_review_remote_image_markup') 'Remote card art must use a bounded markup helper.'
foreach ($candidate in @('w342','w500','w780')) {
    Assert-True ($reviewRendering.Contains($candidate)) "TMDB card art must expose the bounded $candidate candidate."
}
Assert-True ($reviewRendering -match 'image\.tmdb\.org') 'TMDB candidates must be host-gated.'
Assert-True ($reviewRendering -match "\^/t/p/\(\?:w\\d\+\|original\)/") 'TMDB candidates must require the canonical image pathname.'
Assert-True ($reviewRendering -match 'seen_candidates') 'TMDB candidates must be distinct before receiving width descriptors.'
Assert-True ($reviewRendering -match 'return\s+array\([\s\S]{0,500}''url''\s*=>\s*\$source_url') 'Unknown remote art must retain its exact source URL.'
Assert-True ($reviewRendering -match '''loading''\s*=>\s*\$show_hero\s*\?\s*''lazy''\s*:\s*''eager''') 'Lead image must be lazy with the hero and eager only when the hero is off.'
Assert-True ($reviewRendering -notmatch 'lunara_render_review_grid_card\(\s*\$support_post->ID,\s*\$support_index') 'All supporting archive cards must remain lazy.'

# Static route CSS leaves HTML and loads only on Reviews routes.
$archiveCss = Read-ThemeFile 'assets/css/lunara-review-archive.css'
Assert-True ($archiveCss.Length -gt 30000) 'Reviews archive stylesheet must contain the extracted static presentation.'
Assert-True ($archiveCss.Contains('#primary.lra')) 'Archive CSS must stay scoped to the shared renderer-owned Reviews wrapper.'
Assert-True ($reviewRendering -match '\$classes\s*=\s*trim\(\s*''site-main lunara-archive-page lra ''\s*\.\s*\$base_classes\s*\)') 'Every Reviews archive renderer must receive the internal route marker.'
Assert-True ($archiveCss -match 'lunara-review-feature-card\.is-lead[\s\S]{0,500}lunara-review-feature-link[\s\S]{0,300}max-height:\s*760px') 'Desktop lead geometry must stay at or below the 760px release budget.'
Assert-True ($frontend -match "lunara_resolve_theme_asset\(\s*'assets/css/lunara-review-archive\.css'\s*\)") 'Frontend must resolve the route stylesheet.'
Assert-True ($frontend -match "wp_enqueue_style\([\s\S]{0,300}'lunara-review-archive'") 'Frontend must enqueue the Reviews archive stylesheet.'
Assert-True ($frontend -match "is_tax\(\s*'lunara_director'\s*\)") 'Director archives must receive the semantic feature-card stylesheet.'
Assert-True ($frontend -match 'function\s+lunara_phase1c_review_components_needed\(\)[\s\S]{0,350}is_tax\(\s*''lunara_director''\s*\)') 'Director archives must register the lunara-review-components dependency before the archive stylesheet.'
Assert-True ($frontend -match 'function\s+lunara_output_review_archive_authority_css\(\)[\s\S]{0,350}is_tax\(\s*''lunara_director''\s*\)') 'Director archives must receive safe Reviews geometry variables.'
Assert-True ($reviewRendering -match '\$is_director_archive\s*\|\|\s*\(\s*function_exists\(\s*''lunara_reviews_archive_section_is_enabled''') 'Archive Studio visibility must not suppress director archive structure.'
Assert-True ($reviewRendering -match '\$show_pairing_desk\s*=\s*!\s*\$is_director_archive') 'Pairing Desk must remain a real lane on both canonical Reviews index templates.'
Assert-True ($frontend -match '\$should_load\s*=\s*is_singular\(\s*''review''\s*\)[^;]+is_page_template\(\s*''page-reviews\.php''\s*\)[^;]+is_page\(\s*''reviews''\s*\)') 'Both Reviews index templates must receive the Pairing Desk aperture-mark variable.'
Assert-True ($frontend -match 'function\s+lunara_output_review_card_image_focus_css\(\)[\s\S]{0,350}is_page_template\(\s*''page-reviews\.php''\s*\)') 'A nonstandard page slug using page-reviews.php must retain Review image-focus variables.'
Assert-True ($frontend -match 'function\s+lunara_output_review_card_image_focus_css\(\)[\s\S]{0,450}is_tax\(\s*''lunara_director''\s*\)') 'Director archives must retain Review card crop-focus variables.'
Assert-True ($frontend -match '(?m)^\s*\.lunara-review-archive-page\s*[,\{]') 'Dynamic Reviews variables must target the archive-owned wrapper on slug-selected and template-selected pages.'
Assert-True ($frontend -match 'rocket_rucss_external_exclusions[\s\S]*lunara-review-archive\.css') 'Rocket must preserve the complete archive stylesheet.'
Assert-True ($frontend -match '<style id="lunara-review-archive-authority-vars">') 'Only dynamic Archive Studio variables may remain inline.'
Assert-True ($frontend -notmatch '<style id="lunara-review-archive-authority-css">') 'The 42KB archive cascade must no longer be inline.'
$dynamicVars = [regex]::Match($frontend, '<style id="lunara-review-archive-authority-vars">(?<css>[\s\S]*?)</style>')
Assert-True ($dynamicVars.Success) 'Dynamic Reviews Archive variables block is missing.'
$dynamicVarBytes = [Text.Encoding]::UTF8.GetByteCount($dynamicVars.Groups['css'].Value)
Assert-True ($dynamicVarBytes -lt 2048) "Dynamic Reviews Archive inline CSS exceeds 2 KB: $dynamicVarBytes bytes."
Assert-True ($style -notmatch 'body\.post-type-archive-review\s+\.lunara-review-archive-slot-(hero|grid|pagination|pairing-desk)[^}]*order\s*:') 'Legacy !important slot order must not defeat Archive Studio.'
Assert-True ($style -notmatch 'body\.post-type-archive-review\s+\.(?:lunara-archive-hero|lunara-review-archive-shell)[^}]*order\s*:') 'Legacy direct-element order aliases must not defeat Archive Studio.'
Assert-True ($archiveCss -notmatch 'lunara-review-archive-slot-(hero|utility|grid|pagination|pairing-desk)[^{]*\{[^}]*order\s*:') 'Cacheable Reviews CSS must not lock the four saved lane positions.'

$runtimeOutput = & php (Join-Path $PSScriptRoot 'reviews-archive-composition-runtime.php') 2>&1
Assert-True ($LASTEXITCODE -eq 0) ("Reviews Archive runtime failed: " + ($runtimeOutput -join [Environment]::NewLine))
Assert-True (($runtimeOutput -join "`n") -match 'all assertions passed') 'Reviews Archive runtime did not report success.'

$pinRuntimeOutput = & php (Join-Path $PSScriptRoot 'reviews-archive-pin-runtime.php') 2>&1
Assert-True ($LASTEXITCODE -eq 0) ("Reviews Archive pin runtime failed: " + ($pinRuntimeOutput -join [Environment]::NewLine))
Assert-True (($pinRuntimeOutput -join "`n") -match 'all assertions passed') 'Reviews Archive pin runtime did not report success.'

$versionLine = (Read-ThemeFile 'style.css' | Select-String -Pattern 'Version:\s*3\.2\.45').Matches.Count
Assert-True ($versionLine -ge 1) 'Theme version must be 3.2.45.'

Write-Host 'Theme 3.2.45 Reviews Archive composition contract passed.'
