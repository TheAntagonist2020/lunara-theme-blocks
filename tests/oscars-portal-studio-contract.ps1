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

$runtimeSource = Read-ThemeFile 'tests/oscars-portal-studio-runtime.php'
Assert-True ($runtimeSource -match 'proc_open\s*\(') 'Reader-degradation lanes must use proc_open for shell-free cross-platform child launches.'
Assert-True ($runtimeSource -match 'LUNARA_OSCARS_PORTAL_MODE.*\]') 'Reader-degradation lanes must pass LUNARA_OSCARS_PORTAL_MODE through the child environment array.'
Assert-True ($runtimeSource -notmatch '''LUNARA_OSCARS_PORTAL_MODE=''\s*\.\s*\$child_mode') 'Reader-degradation lanes must not interpolate environment assignments into a shell command.'

# The behavioral runtime executes first: accessors mode plus the two spawned
# reader-degradation lanes (degraded plugin, no plugin).
$runtime = & php (Join-Path $testsRoot 'oscars-portal-studio-runtime.php') 2>&1
Assert-True ($LASTEXITCODE -eq 0) ("Oscars Portal Studio runtime failed: " + ($runtime -join [Environment]::NewLine))
Assert-True (($runtime -join "`n") -match 'oscars-portal-studio-runtime: all assertions passed \(accessors mode\)') 'The Oscars Portal Studio runtime did not report success.'

$loader         = Read-ThemeFile 'functions-loader.php'
$family         = Read-ThemeFile 'inc/oscars-family.php'
$studio         = Read-ThemeFile 'inc/oscars-portal-studio.php'
$portalCritical = Read-ThemeFile 'inc/oscars-portal-critical.php'
$ledgerCritical = Read-ThemeFile 'inc/oscars-ledger-critical.php'
$portalRenderer = Read-ThemeFile 'inc/oscars-portal.php'
$frontend       = Read-ThemeFile 'inc/frontend.php'
$siteStudio     = Read-ThemeFile 'inc/site-studio.php'
$siteRegistry   = Read-ThemeFile 'inc/site-studio-registry.php'
$controlDesk    = Read-ThemeFile 'inc/control-desk.php'
$pageTemplate   = Read-ThemeFile 'page-oscars.php'
$style          = Read-ThemeFile 'style.css'

# The split loader must load all four new Oscars modules.
foreach ($module in @('oscars-family\.php', 'oscars-portal-studio\.php', 'oscars-portal-critical\.php', 'oscars-ledger-critical\.php')) {
    Assert-True ($loader -match ("require_once\s+\`$lunara_inc\s*\.\s*'" + $module + "';")) "The split loader must load $module."
}

# Function roster: the family boundary.
foreach ($function in @(
    'lunara_is_oscars_portal_route',
    'lunara_is_oscars_ledger_route',
    'lunara_is_oscars_route_family',
    'lunara_oscars_family_body_classes',
    'lunara_oscars_reader',
    'lunara_oscars_compose_hub_route_sections',
    'lunara_oscars_compose_entity_route_sections'
)) {
    Assert-True ($family -match ("function\s+" + [regex]::Escape($function) + "\s*\(")) "Missing Oscars family function: $function"
}

# Function roster: the Portal Studio.
foreach ($function in @(
    'lunara_oscars_portal_studio_slots',
    'lunara_oscars_portal_studio_visibility_owners',
    'lunara_oscars_portal_studio_identity_specs',
    'lunara_oscars_portal_studio_geometry_specs',
    'lunara_oscars_portal_studio_defaults',
    'lunara_oscars_portal_studio_scalar_or',
    'lunara_oscars_portal_studio_text',
    'lunara_oscars_portal_studio_expand_section_order',
    'lunara_oscars_portal_studio_get_new_fields',
    'lunara_oscars_portal_studio_has_saved_presentation',
    'lunara_oscars_portal_studio_normalize_public_shape',
    'lunara_oscars_portal_studio_repair_public_config',
    'lunara_oscars_portal_studio_get_public_config',
    'lunara_oscars_portal_studio_validate_config',
    'lunara_oscars_portal_studio_config_from_request',
    'lunara_oscars_portal_studio_apply_config',
    'lunara_oscars_portal_studio_get_revisions',
    'lunara_oscars_portal_studio_push_revision',
    'lunara_oscars_portal_studio_promote_config',
    'lunara_oscars_portal_studio_restore_revision',
    'lunara_oscars_portal_studio_cache_urls',
    'lunara_oscars_portal_studio_flush_route_cache',
    'lunara_oscars_portal_studio_timestamp',
    'lunara_oscars_portal_studio_store_preview',
    'lunara_oscars_portal_studio_get_preview_config',
    'lunara_oscars_portal_studio_invalid_stage_key',
    'lunara_oscars_portal_studio_feedback_key',
    'lunara_oscars_portal_studio_bound_invalid_stage',
    'lunara_oscars_portal_studio_store_invalid_stage',
    'lunara_oscars_portal_studio_get_invalid_stage',
    'lunara_oscars_portal_studio_clear_invalid_stage',
    'lunara_oscars_portal_studio_store_feedback',
    'lunara_oscars_portal_studio_bound_feedback_code',
    'lunara_oscars_portal_studio_validation_messages',
    'lunara_oscars_portal_studio_validation_message',
    'lunara_oscars_portal_studio_is_portal_family_request',
    'lunara_oscars_portal_studio_send_private_no_store',
    'lunara_oscars_portal_studio_prepare_preview_response',
    'lunara_oscars_portal_studio_guard_preview_request',
    'lunara_oscars_portal_studio_preflight_preview_query',
    'lunara_oscars_portal_render_sections',
    'lunara_control_desk_render_oscars_portal_studio',
    'lunara_control_desk_save_oscars_portal_studio',
    'lunara_control_desk_restore_oscars_portal_studio',
    'lunara_control_desk_preview_oscars_portal_studio'
)) {
    Assert-True ($studio -match ("function\s+" + [regex]::Escape($function) + "\s*\(")) "Missing Portal Studio function: $function"
}

# Function roster: the two critical-CSS modules.
foreach ($function in @(
    'lunara_oscars_portal_resolved_label_font_slug',
    'lunara_oscars_portal_uses_tiempos_label_face',
    'lunara_oscars_portal_minify_structural_css',
    'lunara_oscars_portal_variable_css',
    'lunara_oscars_portal_critical_css'
)) {
    Assert-True ($portalCritical -match ("function\s+" + [regex]::Escape($function) + "\s*\(")) "Missing portal critical function: $function"
}
foreach ($function in @(
    'lunara_oscars_ledger_minify_css',
    'lunara_oscars_ledger_uses_tiempos_label_face',
    'lunara_oscars_ledger_body_classes',
    'lunara_oscars_ledger_variable_css',
    'lunara_oscars_ledger_critical_css'
)) {
    Assert-True ($ledgerCritical -match ("function\s+" + [regex]::Escape($function) + "\s*\(")) "Missing ledger critical function: $function"
}

# Option constants: one bounded option, one bounded revision history.
Assert-True ($studio -match "define\(\s*'LUNARA_OSCARS_PORTAL_STUDIO_OPTION'\s*,\s*'lunara_oscars_portal_studio'\s*\)") 'The Portal Studio option constant must keep its canonical name.'
Assert-True ($studio -match "define\(\s*'LUNARA_OSCARS_PORTAL_STUDIO_REVISIONS_OPTION'\s*,\s*'lunara_oscars_portal_studio_revisions'\s*\)") 'The Portal Studio revisions option constant must keep its canonical name.'
Assert-True ($studio -match "define\(\s*'LUNARA_OSCARS_PORTAL_STUDIO_REVISION_LIMIT'\s*,\s*12\s*\)") 'Portal Studio history must be bounded to twelve revisions.'

# Save/restore/preview handlers: registered, nonce-bound, capability-gated,
# with the bounded Site Studio return URL.
Assert-True ($studio -match "add_action\(\s*'admin_post_lunara_save_oscars_portal_studio'\s*,\s*'lunara_control_desk_save_oscars_portal_studio'\s*\)") 'The Portal Studio save action must be registered.'
Assert-True ($studio -match "add_action\(\s*'admin_post_lunara_restore_oscars_portal_studio'\s*,\s*'lunara_control_desk_restore_oscars_portal_studio'\s*\)") 'The Portal Studio restore action must be registered.'
Assert-True ($studio -match "add_action\(\s*'admin_post_lunara_preview_oscars_portal_studio'\s*,\s*'lunara_control_desk_preview_oscars_portal_studio'\s*\)") 'The Portal Studio preview action must be registered.'
Assert-True ($studio -match "check_admin_referer\(\s*'lunara_save_oscars_portal_studio'\s*,\s*'lunara_oscars_portal_nonce'\s*\)") 'Portal Studio save must retain its nonce.'
Assert-True ($studio -match "check_admin_referer\(\s*'lunara_restore_oscars_portal_studio'\s*,\s*'lunara_oscars_portal_restore_nonce'\s*\)") 'Portal Studio restore must have its own nonce.'
Assert-True ($studio -match "check_admin_referer\(\s*'lunara_preview_oscars_portal_studio'\s*,\s*'lunara_oscars_portal_preview_nonce'\s*\)") 'Unsaved preview must use a nonce distinct from public save.'
Assert-True ($studio -match "current_user_can\(\s*'edit_theme_options'\s*\)") 'Portal Studio mutations and previews must be capability-gated.'
Assert-True ($studio -match 'function\s+lunara_control_desk_save_oscars_portal_studio[\s\S]{0,700}current_user_can[\s\S]{0,400}check_admin_referer') 'The save flow must check capability before its nonce work.'
Assert-True ($studio -match "lunara_control_desk_bounded_return_url\(\s*'lunara_oscars_portal_return'\s*,\s*'oscars-portal'") 'The save flow must resolve its redirect through the bounded return-URL helper.'
Assert-True ($studio -match 'lunara_oscars_portal_studio_store_invalid_stage\(\s*\$candidate\s*,\s*\$_POST[\s\S]{0,200}get_error_code') 'Rejected saves must retain a bounded private draft and allowlisted reason.'
Assert-True ($studio -match 'function\s+lunara_oscars_portal_studio_validation_messages[\s\S]*?oscars_portal_config_invalid[\s\S]*?oscars_portal_geometry_invalid[\s\S]*?oscars_portal_preview_forbidden') 'Validator feedback must use an explicit safe reason allowlist.'

# Preview responses become private no-store BEFORE any token denial can be
# computed; the guard and preflight hooks run before templates and queries.
Assert-True ($studio -match 'nocache_headers\(\)[\s\S]*?Cache-Control:\s*private,\s*no-store') 'Preview wrapper and route responses must be explicitly non-cacheable.'
Assert-True ($studio -match 'function\s+lunara_oscars_portal_studio_prepare_preview_response\((?:(?!function\s)[\s\S])*?lunara_oscars_portal_studio_send_private_no_store\((?:(?!function\s)[\s\S])*?403') 'Preview responses must become no-store before invalid, expired, guessed, anonymous, or foreign tokens are denied 403.'
Assert-True ($studio -match "add_action\(\s*'template_redirect'\s*,\s*'lunara_oscars_portal_studio_guard_preview_request'\s*,\s*0\s*\)") 'Every portal preview query must be guarded before template rendering.'
Assert-True ($studio -match "add_action\(\s*'pre_get_posts'\s*,\s*'lunara_oscars_portal_studio_preflight_preview_query'\s*,\s*1\s*\)") 'Invalid preview tokens must be denied before the portal query composes Studio state.'
# Anchored inside the get_preview_config body: the tempered (?!function\s)
# scan cannot skip past the next function boundary, so the revision-restore
# hash_equals can never satisfy this owner-binding assertion.
Assert-True ($studio -match 'function\s+lunara_oscars_portal_studio_get_preview_config\((?:(?!function\s)[\s\S])*?get_current_user_id\(\)(?:(?!function\s)[\s\S])*?hash_equals\(') 'Preview retrieval must bind an unguessable token to the current authorized user inside get_preview_config itself.'
Assert-True ($studio -match 'set_transient[\s\S]*?lunara_oscars_portal_preview_') 'Unsaved previews must be short-lived transients that never replace public state.'
Assert-True ($studio -match 'data-lunara-oscars-preview-frame="desktop"[\s\S]*?data-lunara-oscars-preview-frame="mobile"') 'Preview output must render actual desktop and mobile frames.'
Assert-True ($studio -match 'data-lunara-oscars-preview-frame="mobile"[^>]*style="[^"]*width:\s*390px') 'The mobile preview frame must have a real 390px viewport.'
# The preview family is exactly the theme-owned portal; ledger routes are
# contractually excluded so a token can never restyle a plugin page.
Assert-True ($studio -notmatch 'function\s+lunara_oscars_portal_studio_is_portal_family_request\((?:(?!function\s)[\s\S])*?aat_') 'The preview family body must never include plugin aat_* ledger routes.'
Assert-True ($studio -notmatch 'get_term_link') 'Portal Studio cache invalidation must never collect taxonomy term URLs.'

# The resolver validates the raw token exactly: no sanitize_key normalization
# may turn a corrupt near-match into a valid custom label choice. Tempered so
# the negative scan cannot escape past the next function boundary.
Assert-True ($portalCritical -notmatch 'function\s+lunara_oscars_portal_resolved_label_font_slug\((?:(?!function\s)[\s\S])*sanitize_key') 'The portal label resolver must validate the raw scalar token exactly instead of normalizing corrupt near-matches into valid custom choices.'
Assert-True ($pageTemplate -match 'lunara_oscars_portal_uses_tiempos_label_face\(\)[\s\S]{0,120}is-label-font-tiempos') 'The template must emit the Tiempos marker only through the resolver.'
Assert-True (([regex]::Matches($pageTemplate, 'is-label-font-tiempos')).Count -eq 1) 'Exactly one resolver-guarded template site may emit the Tiempos marker.'
Assert-True ($frontend -match "function\s+lunara_preload_oscars_portal_label_font[\s\S]{0,900}lunara_oscars_portal_uses_tiempos_label_face[\s\S]{0,500}TiemposText-Bold\.woff2[\s\S]{0,300}add_action\(\s*'wp_head'\s*,\s*'lunara_preload_oscars_portal_label_font'\s*,\s*4\s*\)") 'Only a default-token portal route may preload the licensed label face at wp_head 4.'

# All 8 #oscars-* anchors stay present in page-oscars.php; the board id lives
# in its renderer and the template links to it.
foreach ($anchorId in @('oscars-doors', 'oscars-spotlights', 'oscars-titles', 'oscars-research', 'oscars-reviews', 'oscars-winners', 'oscars-deep-cuts')) {
    Assert-True ($pageTemplate -match ('id="' + [regex]::Escape($anchorId) + '"')) "page-oscars.php must keep the #$anchorId section anchor."
}
Assert-True ($pageTemplate -match '#oscars-board') 'page-oscars.php must keep the #oscars-board navigator anchor.'
Assert-True ($portalRenderer -match 'id="oscars-board"') 'The Prediction Board renderer must keep the stable #oscars-board id.'

# Navigator link integrity at the source. The Winners grid is bound to ceremony
# data, not to a visibility dial, so its navigator link must share the section's
# gate rather than the dial alone — the asymmetry that left a dead #oscars-winners
# link on the live portal whenever the latest ceremony had no recorded winners.
Assert-True ($pageTemplate -match '\$has_latest_winners\s*=\s*\(\s*\$show_latest_winners\s*&&\s*!\s*empty\(\s*\$winner_cards\s*\)\s*\)') 'page-oscars.php must derive $has_latest_winners from both the visibility dial and the presence of winner cards.'
Assert-True ($pageTemplate -match '<\?php if \( \$has_latest_winners \) : \?><a href="#oscars-winners">') 'The Winners navigator link must be gated on $has_latest_winners, never on the visibility dial alone.'
Assert-True ($pageTemplate -notmatch '<\?php if \( \$show_latest_winners \) : \?><a href="#oscars-winners">') 'The dial-only Winners navigator gate must not return; it emits a link to a section that may not render.'
Assert-True ($pageTemplate -match '(?s)\$has_latest_winners.*<section id="oscars-winners"') 'The Winners gate must be resolved before the section it guards.'
# The gate has to be resolved before the navigator is emitted, not merely before
# the section — the navigator is what links to it.
$navigatorOffset = $pageTemplate.IndexOf('<a href="#oscars-winners">')
$gateOffset      = $pageTemplate.IndexOf('$has_latest_winners =')
Assert-True ($gateOffset -ge 0 -and $navigatorOffset -gt $gateOffset) 'The $has_latest_winners gate must be computed above the navigator that consumes it.'

# The portal root stamps the deployed theme version for the coherency sentinel.
Assert-True ($pageTemplate -match 'data-lunara-theme-version="<\?php echo esc_attr\( \(string\) wp_get_theme\(\)->get\( ''Version'' \) \); \?>"') 'The portal root must stamp data-lunara-theme-version.'
Assert-True ($pageTemplate -match 'id="primary" class="site-main lunara-oscars-portal') 'The portal root identity (#primary + lunara-oscars-portal) must be preserved.'

# Zero direct SQL in the migrated route family.
foreach ($sqlFreeFile in @('page-oscars.php', 'inc/oscars-family.php', 'inc/oscars-portal-studio.php', 'inc/oscars-portal-critical.php', 'inc/oscars-ledger-critical.php')) {
    $content = Read-ThemeFile $sqlFreeFile
    # Catches both the global variable and its $GLOBALS['wpdb'] alias spelling.
    Assert-True ($content -notmatch '\$wpdb|GLOBALS\[\s*[''"]wpdb') "$sqlFreeFile must carry zero direct `$wpdb references (including the `$GLOBALS['wpdb'] alias); the plugin reader is the only data door."
}

# Publication and cache authority stay bounded.
Assert-True ($studio -notmatch '\bwp_(insert|update)_post\s*\(') 'The Portal Studio must never insert, publish, schedule, or rewrite posts.'
Assert-True ($studio -notmatch '\bwp_cache_flush\s*\(') 'Portal saves must never trigger a global object-cache purge.'
Assert-True ($studio -match "wp_cache_delete\(\s*'oscars_portal_studio_public'\s*,\s*'lunara'\s*\)") 'Portal saves must invalidate only their route/data cache key.'
Assert-True ($studio -notmatch '\bwp_cache_(get|set)\s*\(') 'The public config resolver is deliberately uncached: no object-cache read or write may return to the module.'
Assert-True ($studio -notmatch 'rocket_clean_domain') 'Portal saves must never purge the full WP Rocket domain cache.'
$validationPos = $studio.IndexOf('function lunara_oscars_portal_studio_validate_config')
$applyPos      = $studio.IndexOf('function lunara_oscars_portal_studio_apply_config')
Assert-True ($validationPos -ge 0 -and $applyPos -gt 0) 'Validation and application must both exist.'

# Route delivery: cacheable portal stylesheet at 111, unaggregated and
# synchronous, with the Rocket RUCSS protection set for the inline layers.
Assert-True ($frontend -match "add_action\(\s*'wp_enqueue_scripts'\s*,\s*'lunara_enqueue_oscars_portal_styles'\s*,\s*111\s*\)") 'The portal route stylesheet must enqueue at 111.'
Assert-True ($frontend -match "add_action\(\s*'wp_head'\s*,\s*'lunara_output_oscars_portal_studio_css'\s*,\s*6\s*\)") 'Provenance-gated Studio vars must print at wp_head 6.'
Assert-True ($frontend -match "add_action\(\s*'wp_head'\s*,\s*'lunara_output_oscars_portal_critical_css'\s*,\s*7\s*\)") 'The structural seed must print at wp_head 7.'
Assert-True ($frontend -match "function\s+lunara_keep_oscars_portal_css_unaggregated[\s\S]{0,300}'lunara-oscars-portal'") 'Jetpack Boost must not aggregate the portal route stylesheet.'
Assert-True ($frontend -match "function\s+lunara_keep_oscars_portal_css_synchronous[\s\S]{0,300}'lunara-oscars-portal'") 'The portal route stylesheet must stay render-blocking.'
Assert-True ($frontend -match "lunara_rocket_preserve_oscars_portal_css[\s\S]{0,300}'lunara-oscars-portal\.css'") 'WP Rocket RUCSS must exclude the portal route stylesheet.'
Assert-True ($frontend -match "lunara_rocket_preserve_oscars_portal_inline_css[\s\S]{0,400}'lunara-oscars-portal-vars'[\s\S]{0,200}'lunara-oscars-portal-critical-css'") 'WP Rocket RUCSS must preserve both inline portal layers.'

# First-paint budgets: the cacheable route CSS stays inside 45 KB and the
# rendered inline layers (saved-provenance vars + structural seed) stay
# inside the 12 KB inline budget, measured by executing the real builders.
$portalCssBytes = (Get-Item -LiteralPath (Join-Path $themeRoot 'assets/css/lunara-oscars-portal.css')).Length
Assert-True ($portalCssBytes -gt 30000 -and $portalCssBytes -le 45000) "Cacheable portal route CSS must stay within 45 KB; measured $portalCssBytes bytes."
$emitted = & php (Join-Path $testsRoot 'oscars-portal-studio-runtime.php') --emit-portal-critical 2>&1
Assert-True ($LASTEXITCODE -eq 0) 'The portal critical-CSS emit fast path must execute successfully.'
$emittedLines = @($emitted | Where-Object { $_ -ne '' })
Assert-True ($emittedLines.Count -eq 2) 'The emit fast path must print the vars line and the seed line.'
$varsBytes = [Text.Encoding]::UTF8.GetByteCount($emittedLines[0])
$seedBytes = [Text.Encoding]::UTF8.GetByteCount($emittedLines[1])
Assert-True ($varsBytes -gt 0 -and $seedBytes -gt 0) 'Both rendered inline layers must be nonempty under saved provenance.'
Assert-True (($varsBytes + $seedBytes) -le 12288) "Rendered portal vars + seed must stay at or below 12 KB; measured $($varsBytes + $seedBytes) bytes."
Assert-True ($emittedLines[1].Contains('order:initial!important')) 'The rendered seed must carry the slot order:initial neutralization.'
Assert-True ($emittedLines[0].StartsWith('#primary.lunara-oscars-portal{--lunara-oscars-portal-section-gap:')) 'The rendered vars must stamp the saved geometry custom properties.'

# Site Studio registers both Oscars surfaces and the command index lists the
# Portal Studio.
Assert-True ($siteRegistry -match "'oscars-portal'\s*=>\s*array\([\s\S]{0,1800}'renderer'\s*=>\s*'lunara_control_desk_render_oscars_portal_studio'") 'The unconditional Site Studio registry must register the oscars-portal surface with the Portal Studio renderer.'
Assert-True ($siteRegistry -match "'oscars-ledger'\s*=>\s*array\([\s\S]{0,1800}'renderer'\s*=>\s*'lunara_control_desk_render_oscars_dossier_studio'") 'The unconditional Site Studio registry must register the oscars-ledger surface with the Dossier Studio renderer.'
Assert-True ($controlDesk -match "'Oscars Portal Studio'") 'The Theme Studio command index must list the Oscars Portal Studio.'
Assert-True ($controlDesk -match "'#lunara-oscars-portal-studio'") 'The command index entry must anchor to the Portal Studio surface.'

# Version lock: this intentionally asserts the NEXT reissue identity. It is
# EXPECTED to fail until the 3.2.58 version migration lands as its own step;
# every assertion above it must already pass on the pre-migration tree.
Assert-True ($style -match '(?m)^Version:\s*3\.2\.58\s*$') 'Theme version must be 3.2.58.'

Write-Host 'oscars-portal-studio: all assertions passed.'
