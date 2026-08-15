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
    Assert-True (Test-Path -LiteralPath $path) "Missing expected file: $RelativePath"
    return Get-Content -LiteralPath $path -Raw
}

$runtime = & php (Join-Path $PSScriptRoot 'journal-archive-studio-runtime.php') 2>&1
Assert-True ($LASTEXITCODE -eq 0) ("Journal Archive Studio runtime failed: " + ($runtime -join [Environment]::NewLine))

$browserLauncher = Join-Path $PSScriptRoot 'run-journal-archive-first-paint.ps1'
Assert-True (Test-Path -LiteralPath $browserLauncher) 'Missing the portable Journal Playwright launcher.'
$powerShellHost = (Get-Process -Id $PID).Path
Assert-True (Test-Path -LiteralPath $powerShellHost) 'The current PowerShell host could not be resolved for the portable Journal launcher.'
$browserOutput = & $powerShellHost -NoProfile -ExecutionPolicy Bypass -File $browserLauncher 2>&1
Assert-True ($LASTEXITCODE -eq 0) ("Journal exact first-paint browser gate failed: " + ($browserOutput -join [Environment]::NewLine))
$browserResult = (($browserOutput | ForEach-Object { "$_" }) -join [Environment]::NewLine) | ConvertFrom-Json
Assert-True ($browserResult.exactAggregate -eq $true) 'The portable Journal browser gate must replay the exact production aggregate by default.'
Assert-True ($browserResult.fixtureBytes -eq 69065 -and $browserResult.fixtureHash -eq '314c0ba9849a2f0be1dfcded682f0ca39c4a302f9f855453c2a2f41ead517192') 'The exact production critical fixture byte/hash lock must pass.'
Assert-True ($browserResult.aggregateBytes -eq 847152 -and $browserResult.aggregateHash -eq '6670f9bc1f11eb7c082e7c43e3f2ec66a2472d5131f3e3a935eb691415cfaeae') 'The decompressed production aggregate byte/hash lock must pass.'
Assert-True ($browserResult.routeBytes -le 40960) "Journal route CSS exceeds 40 KiB: $($browserResult.routeBytes)B."
Assert-True ($browserResult.headBytes -le 8192) "Journal variables plus critical seed exceed 8 KiB: $($browserResult.headBytes)B."
Assert-True ($browserResult.browserSkipped -ne $true) 'The exact Journal browser gate may never be skipped in CI or locally.'
Assert-True (@($browserResult.results).Count -eq 12) 'The exact Journal browser gate must execute all four scenarios at all three viewports.'
foreach ($browserCase in $browserResult.results) {
    Assert-True ($browserCase.exactAggregate -eq $true) "Browser case $($browserCase.scenario)/$($browserCase.width) did not use the exact aggregate."
    Assert-True ($browserCase.deliveryDelta.max -le 1) "Browser case $($browserCase.scenario)/$($browserCase.width) shifted $($browserCase.deliveryDelta.max)px during deferred delivery."
    Assert-True ($browserCase.criticalWithdrawalDelta.max -le 1) "Browser case $($browserCase.scenario)/$($browserCase.width) shifted $($browserCase.criticalWithdrawalDelta.max)px when stale critical CSS withdrew."
    Assert-True ($browserCase.seedPersistenceDelta.max -le 1) "Browser case $($browserCase.scenario)/$($browserCase.width) shifted $($browserCase.seedPersistenceDelta.max)px when the seed withdrew."
}

$priorUserProfile = $env:USERPROFILE
$priorHome = $env:HOME
try {
    $env:USERPROFILE = $null
    $env:HOME = $themeRoot
    $fallbackHome = & $powerShellHost -NoProfile -ExecutionPolicy Bypass -File $browserLauncher -ResolveHomeOnly 2>&1
    Assert-True ($LASTEXITCODE -eq 0 -and "$fallbackHome" -eq $themeRoot) 'The portable launcher must resolve HOME when Linux pwsh has no USERPROFILE.'
} finally {
    $env:USERPROFILE = $priorUserProfile
    $env:HOME = $priorHome
}

$loader   = Read-ThemeFile 'functions-loader.php'
$studio   = Read-ThemeFile 'inc/journal-archive-studio.php'
$control  = Read-ThemeFile 'inc/control-desk.php'
$helpers  = Read-ThemeFile 'inc/helpers.php'
$archive  = Read-ThemeFile 'archive-journal.php'
$adminJs  = Read-ThemeFile 'assets/js/lunara-control-desk.js'
$style    = Read-ThemeFile 'style.css'
$frontend = Read-ThemeFile 'inc/frontend.php'
$customizer = Read-ThemeFile 'inc/customizer.php'
$shell    = Read-ThemeFile 'assets/css/lunara-shell.css'
$guardrails = Read-ThemeFile 'assets/css/lunara-public-guardrails.css'
$critical = Read-ThemeFile 'inc/journal-archive-critical.php'
$routeCss = Read-ThemeFile 'assets/css/lunara-journal-archive.css'
$browserLauncherSource = Read-ThemeFile 'tests/run-journal-archive-first-paint.ps1'
$browserRuntimeSource = Read-ThemeFile 'tests/journal-archive-first-paint-runtime.js'
$lintWorkflow = Read-ThemeFile '.github/workflows/lint.yml'
$nodePackage = Read-ThemeFile 'package.json'
$nodeLock = Read-ThemeFile 'package-lock.json'
$deployIgnore = Read-ThemeFile '.deployignore'
$gitIgnore = Read-ThemeFile '.gitignore'
$deployIgnoreLines = @($deployIgnore -split '\r?\n')
Assert-True ($browserLauncherSource -notmatch '(?m)&\s*powershell(?:\.exe)?\b') 'The portable Journal launcher must not invoke the Windows-only powershell command.'
Assert-True (($browserLauncherSource -notmatch '(?m)^\s*\$isWindows\s*=') -and ($browserLauncherSource -match '\$runningOnWindows')) 'The pwsh launcher must not collide with the read-only case-insensitive IsWindows automatic variable.'
Assert-True ($browserLauncherSource -match '/usr/bin/google-chrome[\s\S]*?/usr/bin/google-chrome-stable[\s\S]*?/usr/bin/chromium[\s\S]*?/usr/bin/chromium-browser') 'The portable Journal launcher must resolve system Chrome/Chromium on the Ubuntu lint runner.'
Assert-True ($browserRuntimeSource -match "require\(\s*'playwright'\s*\)[\s\S]*?require\(\s*'playwright-core'\s*\)") 'The browser runtime must support both the bundled Playwright package and the pinned CI playwright-core package.'
Assert-True (($nodePackage -match '"playwright-core"\s*:\s*"1\.62\.1"') -and ($nodeLock -match 'node_modules/playwright-core[\s\S]*?"version"\s*:\s*"1\.62\.1"')) 'The exact browser gate must pin playwright-core 1.62.1 in package and lock files.'
Assert-True ($lintWorkflow -match 'npm ci --ignore-scripts[\s\S]*?Theme contract tests') 'Lint CI must install the pinned browser dependency before executing the PowerShell contract suite.'
Assert-True (($deployIgnoreLines -contains 'package.json') -and ($deployIgnoreLines -contains 'package-lock.json') -and ($deployIgnoreLines -contains 'node_modules/**')) 'CI-only Node manifests and dependencies must remain excluded from theme deployment.'
Assert-True (@($gitIgnore -split '\r?\n') -contains 'node_modules/') 'Pinned CI dependencies must remain outside source control.'
$searchFunctionStart = $studio.IndexOf('function lunara_journal_archive_studio_search_posts')
Assert-True ($searchFunctionStart -ge 0) 'Missing the bounded private Journal search helper.'
$searchFunctionEnd = $studio.IndexOf('/**', $searchFunctionStart + 1)
Assert-True ($searchFunctionEnd -gt $searchFunctionStart) 'The bounded private Journal search helper could not be isolated.'
$searchFunction = $studio.Substring($searchFunctionStart, $searchFunctionEnd - $searchFunctionStart)
$editorPostsStart = $studio.IndexOf('function lunara_journal_archive_studio_editor_posts')
Assert-True ($editorPostsStart -ge 0) 'Missing the bounded initial Journal picker helper.'
$editorPostsEnd = $studio.IndexOf('/**', $editorPostsStart + 1)
Assert-True ($editorPostsEnd -gt $editorPostsStart) 'The bounded initial Journal picker helper could not be isolated.'
$editorPostsFunction = $studio.Substring($editorPostsStart, $editorPostsEnd - $editorPostsStart)

# One focused module owns the bounded public configuration, preview, audit, and query contract.
Assert-True ($loader -match "require_once\s+\`$lunara_inc\s*\.\s*'journal-archive-studio\.php';") 'The split loader must load the Journal Archive Studio module.'
foreach ($function in @(
    'lunara_journal_archive_studio_defaults',
    'lunara_journal_archive_studio_normalize_public_shape',
    'lunara_journal_archive_studio_get_public_config',
    'lunara_journal_archive_studio_validate_config',
    'lunara_journal_archive_studio_apply_config',
    'lunara_journal_archive_studio_promote_config',
    'lunara_journal_archive_studio_push_revision',
    'lunara_journal_archive_studio_get_revisions',
    'lunara_journal_archive_studio_restore_revision',
    'lunara_journal_archive_studio_store_preview',
    'lunara_journal_archive_studio_get_preview_config',
    'lunara_journal_archive_studio_prepare_preview_response',
    'lunara_journal_archive_studio_guard_preview_request',
    'lunara_journal_archive_studio_flush_route_cache',
    'lunara_journal_archive_studio_render_gallery',
    'lunara_journal_archive_studio_compose_retention_lane',
    'lunara_journal_archive_studio_render_sections',
    'lunara_journal_archive_studio_search_posts',
    'lunara_journal_archive_studio_ajax_search_posts',
    'lunara_journal_archive_studio_configure_query',
    'lunara_journal_archive_studio_priority_orderby'
)) {
    Assert-True ($studio -match ("function\s+" + [regex]::Escape($function) + "\s*\(")) "Missing bounded Journal Studio function: $function"
}
Assert-True ($studio -match "define\(\s*'LUNARA_JOURNAL_ARCHIVE_STUDIO_REVISION_LIMIT'\s*,\s*12\s*\)") 'Journal Studio history must be bounded to twelve revisions.'
Assert-True ($studio -match 'current_user_can\(\s*''edit_theme_options''\s*\)') 'Journal Studio mutations and previews must be capability-gated.'
Assert-True ($studio -match "check_admin_referer\(\s*'lunara_save_journal_archive_studio'\s*,\s*'lunara_journal_archive_nonce'\s*\)") 'Journal Studio save must retain its nonce.'
Assert-True ($studio -match "check_admin_referer\(\s*'lunara_restore_journal_archive_studio'\s*,\s*'lunara_journal_archive_restore_nonce'\s*\)") 'Journal Studio restore must have its own nonce.'
foreach ($noticeCode in @('journal_archive_studio_saved','journal_archive_studio_invalid','journal_archive_studio_restored','journal_archive_studio_restore_invalid','journal_archive_studio_forbidden')) {
    Assert-True ($control -match ("'" + $noticeCode + "'\s*=>")) "Control Desk must visibly map Journal handler result $noticeCode."
}
Assert-True ($studio -match 'lunara_journal_archive_studio_store_invalid_stage\(\s*\$candidate\s*,\s*\$_POST[\s\S]*?get_error_code') 'Rejected saves must retain a bounded private draft and allowlisted reason.'
Assert-True ($studio -match 'function\s+lunara_journal_archive_studio_validation_message[\s\S]*?journal_archive_identity_required[\s\S]*?journal_archive_geometry_invalid') 'Validator feedback must use an explicit safe reason allowlist.'
Assert-True ($studio -match 'Your rejected edits are restored below and remain private') 'The focused surface must explain that rejected values are private and public state is unchanged.'
Assert-True ($studio -match "admin_post_lunara_preview_journal_archive_studio") 'Unsaved Journal configuration must have a private preview handler.'
Assert-True ($studio -match "check_admin_referer\(\s*'lunara_preview_journal_archive_studio'\s*,\s*'lunara_journal_archive_preview_nonce'\s*\)") 'Unsaved preview must use a nonce distinct from public save.'
Assert-True ($studio -match 'set_transient[\s\S]*?lunara_journal_archive_preview') 'Unsaved previews must be short-lived and must not replace public state.'
Assert-True ($studio -match 'data-lunara-journal-preview-frame="desktop"[\s\S]*?data-lunara-journal-preview-frame="mobile"') 'Preview output must render actual desktop and mobile frames.'
Assert-True ($studio -match 'data-lunara-journal-preview-frame="mobile"[^>]*style="[^"]*width:\s*390px') 'The mobile preview frame must have a real 390px viewport.'
Assert-True ($studio -match 'hash_equals[\s\S]*?get_current_user_id') 'Preview retrieval must bind an unguessable token to the current authorized user.'
Assert-True ($studio -match 'nocache_headers\(\)[\s\S]*?Cache-Control:\s*private,\s*no-store') 'Preview wrapper and route responses must be explicitly non-cacheable.'
Assert-True ($studio -match "add_action\(\s*'template_redirect'\s*,\s*'lunara_journal_archive_studio_guard_preview_request'\s*,\s*0\s*\)") 'Every Journal preview query must be guarded before template rendering.'
Assert-True ($studio -match 'function\s+lunara_journal_archive_studio_prepare_preview_response[\s\S]*?lunara_journal_archive_studio_send_private_no_store[\s\S]*?status[^\r\n]*403') 'Preview responses must become no-store before invalid, expired, guessed, anonymous, or foreign tokens are denied.'

# The focused UI aggregates the existing owners and clearly describes automatic versus curated behavior.
foreach ($setting in @(
    'lunara_journal_archive_kicker',
    'lunara_journal_archive_title',
    'lunara_journal_archive_copy',
    'lunara_home_journal_lead_post_id',
    'lunara_journal_archive_section_order',
    'lunara_journal_archive_density',
    'lunara_journal_archive_lead_prominence',
    'lunara_journal_archive_desk_rhythm'
)) {
    Assert-True (($studio + $control) -match [regex]::Escape($setting)) "Journal Studio must reuse existing owner $setting."
}
$journalUi = $studio + $control
Assert-True ($journalUi -match 'Automatic query[\s\S]*?Curated selection') 'The Studio must state whether the archive run is query-driven or curated.'
Assert-True ($journalUi -match 'Shared homepage lead[\s\S]*?Automatic newest[\s\S]*?Journal-only manual lead') 'The lead curator must expose shared, automatic, and archive-only manual states.'
Assert-True ($journalUi -match 'post_status[\s\S]*?publish') 'The lead and curated selectors must only list published Journal entries.'
Assert-True ($journalUi -match 'Validator[\s\S]*?Featured image[\s\S]*?Visual File Manager') 'The focused Studio must show validation/media status and link to the existing post-owned media tools.'
Assert-True ($journalUi -match 'name="lunara_journal_archive_item_count"[\s\S]*?min="4"[\s\S]*?max="24"') 'Journal page count must be bounded locally from four to twenty-four.'
Assert-True ($journalUi -match 'name="lunara_journal_archive_curated_ids\[\]"') 'Curated entries must be chosen from published posts without raw ID entry.'
Assert-True ($studio -notmatch "'posts_per_page'\s*=>\s*-1") 'The private Journal picker must never load the complete archive in one query.'
Assert-True (($searchFunction -match 'min\(\s*20\s*,\s*\$limit\s*\)') -and ($searchFunction -match '''posts_per_page''\s*=>\s*\$limit')) 'Journal post search must clamp and pass an explicit maximum of twenty results.'
Assert-True (($searchFunction -match "'cache_results'\s*=>\s*false") -and ($searchFunction -match "'update_post_meta_cache'\s*=>\s*false") -and ($searchFunction -match "'update_post_term_cache'\s*=>\s*false")) 'Journal post search must not warm result, meta, or taxonomy caches.'
Assert-True (($searchFunction -match 'get_posts\(\s*\$args\s*\)\s+as\s+\$post') -and ($searchFunction -match '\$post\s+instanceof\s+WP_Post')) 'Journal post search must consume one bounded WP_Post query without per-ID hydration.'
Assert-True (($editorPostsFunction -match '''post__in''\s*=>\s*\$missing') -and ($editorPostsFunction -match '''posts_per_page''\s*=>\s*count\(\s*\$missing\s*\)') -and ($editorPostsFunction -notmatch 'get_post\s*\(')) 'Configured older lead/curated choices must be retained through one bounded batch query rather than per-post lookups.'
Assert-True ($studio -match "add_action\(\s*'wp_ajax_lunara_journal_archive_studio_search'\s*,\s*'lunara_journal_archive_studio_ajax_search_posts'\s*\)") 'Older published Journal files must remain reachable through an authenticated server-side search action.'
Assert-True ($studio -notmatch "wp_ajax_nopriv_lunara_journal_archive_studio_search") 'The private Journal picker must not expose an unauthenticated search endpoint.'
Assert-True ($studio -match "function\s+lunara_journal_archive_studio_ajax_search_posts[\s\S]{0,1200}current_user_can\(\s*'edit_theme_options'\s*\)[\s\S]{0,600}check_ajax_referer\(\s*'lunara_journal_archive_studio_search'\s*,\s*'nonce'\s*\)") 'Journal search must enforce theme-edit capability and its own AJAX nonce.'
Assert-True (($control -match "'journalSearchUrl'\s*=>\s*admin_url\(\s*'admin-ajax\.php'\s*\)") -and ($control -match "'journalSearchNonce'\s*=>\s*wp_create_nonce\(\s*'lunara_journal_archive_studio_search'\s*\)")) 'The private admin script must receive only the bounded Journal search URL and nonce.'
Assert-True (($adminJs -match "setTimeout\([\s\S]{0,900}lunara_journal_archive_studio_search") -and ($adminJs -match "lunara_journal_archive_studio_search[\s\S]{0,900}fetch\(")) 'Journal title/ID search must debounce requests to the authenticated bounded endpoint.'
Assert-True ($adminJs -match "currentValue[\s\S]{0,900}seen\[currentValue\][\s\S]{0,200}currentValue") 'Remote Journal results must preserve an existing lead or curated picker selection.'
Assert-True ($studio -match 'get_the_title\(\s*\$post\s*\)') 'AJAX result labels must use the already-loaded WP_Post object rather than rehydrating each ID.'
Assert-True ($adminJs -match 'input\._lunaraJournalSearchRequest\s*=\s*\(input\._lunaraJournalSearchRequest[\s\S]{0,500}setJournalPostSearchBusy\(input,\s*false\)') 'Shortening a search must invalidate an in-flight response and clear its busy state.'
Assert-True ($journalUi -match "lunara_journal_archive_retention\[%d\]\[image_id\]") 'Retention cards must use Media Library attachment IDs.'
Assert-True ($journalUi -match 'data-lunara-brand-media-picker') 'Retention media must use the existing WordPress Media Library picker.'
foreach ($galleryControl in @('picker','move','replace','remove','clear')) {
    Assert-True ($journalUi -match ("data-lunara-journal-archive-gallery-" + $galleryControl)) "Archive gallery is missing its $galleryControl control."
}
foreach ($galleryField in @('alt','caption','link_url','credit','source','source_url','focal_x','focal_y')) {
    Assert-True ($studio -match ("lunara_journal_archive_gallery_" + [regex]::Escape($galleryField))) "Archive gallery editor is missing per-image field $galleryField."
}
Assert-True ($journalUi -match 'Configuration history[\s\S]*?Restore this revision') 'Journal Studio must expose its bounded audit/restore history.'
Assert-True ($journalUi -match 'Preview unsaved desktop \+ mobile') 'Journal Studio must offer a true pre-save responsive preview.'
Assert-True ($journalUi -match 'Term names, descriptions, and Journal assignments remain owned by the standard WordPress taxonomies') 'The focused UI must state that WordPress taxonomies retain term and assignment ownership.'
foreach ($archiveLabel in @(
    'desk_count', 'desk_latest', 'desk_mix', 'file_singular', 'file_plural', 'lane_singular', 'lane_plural',
    'filter_sections', 'filter_types', 'filter_topics', 'filter_archive_types', 'taxonomy_section_kicker', 'taxonomy_topic_kicker', 'taxonomy_type_kicker', 'filter_all',
    'toolbar_kicker', 'toolbar_title', 'sort_newest', 'sort_oldest', 'sort_updated',
    'lead_kicker', 'card_kicker', 'card_cta', 'retention_kicker', 'retention_title',
    'pagination_prev', 'pagination_next', 'empty_copy'
)) {
    Assert-True ($studio -match ("'" + [regex]::Escape($archiveLabel) + "'\s*=>")) "Archive-owned visible label is missing from revisionable data: $archiveLabel"
}
foreach ($taxonomyCap in @('journal_section', 'journal_topic', 'journal_type')) {
    Assert-True ($studio -match ("'" + $taxonomyCap + "'\s*=>\s*(8|10)")) "Focused Studio must own a bounded default cap for $taxonomyCap."
}

# Seven real server-rendered lanes share one canonical order and visibility registry.
foreach ($lane in @('hero', 'deskbar', 'filters', 'toolbar', 'grid', 'retention', 'pagination')) {
    Assert-True ($helpers -match ("'" + $lane + "'\s*=>")) "Canonical Journal registry is missing $lane."
}
Assert-True ($studio -match "'section_order'\s*=>\s*array\(\s*'hero',\s*'deskbar',\s*'filters',\s*'toolbar',\s*'grid',\s*'retention',\s*'pagination'\s*\)") 'The default order must preserve the proven production sequence.'
Assert-True ($studio -match 'function\s+lunara_journal_archive_studio_render_sections[\s\S]*?foreach\s*\(\s*\$order\s+as\s+\$slug\s*\)') 'The behavior-tested public renderer must emit lanes in saved DOM order.'
Assert-True ($archive -match '\$journal_section_markup\s*\[') 'The public archive must assemble server-rendered lane markup.'
Assert-True ($archive -match 'lunara_journal_archive_studio_render_sections\s*\(') 'The public archive must use the behavior-tested DOM-order renderer.'
Assert-True ($archive -notmatch 'lunara-journal-studio-public\.js') 'Journal Archive Studio must not add a public editor runtime.'
Assert-True ($studio -match "add_action\(\s*'pre_get_posts'\s*,\s*'lunara_journal_archive_studio_configure_query'") 'The bounded item count and priority order must be applied to the route query.'
Assert-True ($studio -match "add_filter\(\s*'posts_orderby'\s*,\s*'lunara_journal_archive_studio_priority_orderby'") 'Pinned and curated IDs must be ordered in SQL so pagination stays unique.'
Assert-True ($archive -notmatch 'array_unshift\s*\(') 'The template must not inject a pinned post after pagination.'
Assert-True ($archive -match '\$journal_section_markup\[''retention''\]\s*=\s*lunara_journal_archive_studio_compose_retention_lane\(\s*\$journal_retention_cards_markup\s*,\s*\$journal_gallery_markup') 'The template must delegate the actual retention/gallery output to the behavior-tested compositor.'
Assert-True ($studio -match 'function\s+lunara_journal_archive_studio_compose_retention_lane[\s\S]*?\$cards_markup\s*\.\s*\$gallery_markup\s*\.\s*''</section>''') 'The compositor must emit gallery markup after retention-card markup.'
Assert-True ($archive -match 'lunara_journal_archive_studio_is_gallery_request\(\)[\s\S]*?lunara_journal_archive_studio_render_gallery') 'The archive gallery must be gated to canonical /journal/ before rendering.'
Assert-True ($studio -match 'function\s+lunara_journal_archive_studio_is_gallery_request[\s\S]{0,240}!\s*is_paged\(\)') 'The archive-only gallery must not repeat on page two or later archive routes.'
Assert-True ($studio -match 'count\(\s*\$raw_items\s*\)\s*>\s*12') 'Archive gallery capacity must remain bounded to twelve Media Library images.'
Assert-True ($studio -match 'journal_archive_gallery_duplicate[\s\S]*?journal_archive_gallery_image_invalid[\s\S]*?journal_archive_gallery_provenance_required[\s\S]*?journal_archive_gallery_source_invalid') 'Gallery promotion must reject duplicate, missing/non-image, and unsafe or missing provenance data.'
Assert-True ($studio -match 'wp_get_attachment_image\([\s\S]*?lunara-journal-archive-gallery') 'Archive gallery must use native responsive server-rendered attachment markup.'
Assert-True (($studio -match '\$tie_direction\s*=') -and ($studio -match '\.ID\s*''\s*\.\s*\$tie_direction')) 'Priority SQL must include a deterministic post-ID tie-breaker after the selected secondary sort.'

# DOM order is the only public lane-order owner. A synchronous critical seed
# neutralizes stale optimizer payloads, while the route asset owns complete
# geometry including retention media and its focal-position settings.
Assert-True ($loader -match "require_once\s+\`$lunara_inc\s*\.\s*'journal-archive-critical\.php';") 'The split loader must load the Journal first-paint composer.'
Assert-True ($frontend -match "function\s+lunara_enqueue_journal_archive_styles[\s\S]{0,1000}assets/css/lunara-journal-archive\.css[\s\S]{0,500}wp_enqueue_style\([\s\S]{0,220}'lunara-journal-archive'") 'Journal-family routes must enqueue their own cacheable stylesheet.'
Assert-True ($frontend -match "add_action\(\s*'wp_head'\s*,\s*'lunara_output_journal_archive_critical_css'\s*,\s*9\s*\)") 'Journal structural geometry must be emitted synchronously before body paint.'
Assert-True ($frontend -match "function\s+lunara_keep_journal_archive_css_unaggregated[\s\S]{0,500}lunara-journal-archive[\s\S]{0,300}add_filter\(\s*'css_do_concat'") 'Jetpack Boost must not aggregate the Journal route stylesheet.'
Assert-True ($frontend -match "function\s+lunara_keep_journal_archive_css_synchronous[\s\S]{0,500}lunara-journal-archive[\s\S]{0,300}add_filter\(\s*'jetpack_boost_async_style'") 'Jetpack Boost must keep the Journal route stylesheet synchronous.'
Assert-True ($frontend -match "function\s+lunara_rocket_preserve_journal_archive_css[\s\S]{0,500}lunara-journal-archive\.css[\s\S]{0,300}add_filter\(\s*'rocket_rucss_external_exclusions'") 'WP Rocket used-CSS processing must preserve the Journal route asset.'
Assert-True ($frontend -match "function\s+lunara_rocket_preserve_journal_archive_inline_css[\s\S]{0,600}lunara-journal-archive-critical-css[\s\S]{0,350}add_filter\(\s*'rocket_rucss_inline_content_exclusions'") 'WP Rocket must preserve the Journal first-paint seed.'
Assert-True ($frontend -notmatch "add_action\(\s*'wp_footer'\s*,\s*'lunara_output_journal_archive_studio_css'") 'Journal geometry must never arrive as a footer style flip.'
Assert-True ($critical -match 'order:initial!important') 'The critical seed must neutralize stale numeric order rules.'
Assert-True ($critical -notmatch '(?<![a-z-])order:[1-9]') 'Critical CSS must not duplicate saved DOM order numerically.'
Assert-True ($routeCss -match '\.lunara-journal-archive-retention-media[\s\S]*?object-position:\s*var\(--lunara-retention-focus-x') 'Route CSS must own retention image geometry and focal position.'
Assert-True ($archive -match 'retention_media_markup[\s\S]*?retention_has_media[\s\S]*?lunara-journal-archive-retention-card<\?php echo \$retention_has_media') 'Retention cards must pre-resolve image markup and emit the media class only for nonempty native markup.'
Assert-True ($archive -notmatch "retention_card\['image_id'\]\s*\?\s*' has-media'") 'A stale retention attachment ID must never create an empty fixed-ratio chamber.'
Assert-True ($frontend -match 'lunara-journal-archive-gallery-item \.lunara-journal-archive-gallery-media img[\s\S]*?galleryItem\.remove\(\)[\s\S]*?gallery\.remove\(\)[\s\S]*?retentionLane\.remove\(\)') 'The tiny route media guard must remove a failed gallery figure, its empty wrapper, and a gallery-only outer retention shell.'
Assert-True ($frontend -match 'card\.classList\.remove\(''has-media''\)[\s\S]*?posterWrap\.remove\(\)') 'A failed Journal featured image must collapse its poster wrapper and preserve the text card.'
Assert-True ($routeCss -match '@media\s*\(max-width:\s*620px\)') 'Route CSS must own a deterministic phone layout.'
Assert-True ($style -notmatch 'body\.post-type-archive-journal\s+\.lunara-(?:archive-hero|journal-archive[^,{]*)\s*\{[^}]*(?<![a-z-])order:\s*[1-9]') 'style.css must not hard-code Journal lane order.'
Assert-True ($shell -notmatch 'body\.post-type-archive-journal\s+\.lunara-(?:archive-hero|journal-archive[^,{]*|editorial-archive[^,{]*)\s*\{[^}]*(?<![a-z-])order:\s*[1-9]') 'The shell asset must not hard-code Journal lane order.'
Assert-True ($guardrails -notmatch 'body\.post-type-archive-journal\s+\.lunara-journal-archive[^,{]*\s*\{[^}]*(?<![a-z-])order:\s*[1-9]') 'Late guardrails must not hard-code Journal lane order.'
Assert-True ($customizer -notmatch "lunara-journal-archive-page > \.lunara-journal-archive-slot-.*?\{order:") 'Customizer CSS must not become a duplicate Journal order owner.'
Assert-True ($customizer -match 'hero, deskbar, filters, toolbar, grid, retention, pagination') 'The legacy Customizer help must enumerate the full seven-lane order.'
Assert-True ($helpers -match 'function\s+lunara_sanitize_journal_archive_section_order[\s\S]{0,260}lunara_journal_archive_studio_expand_section_order') 'The actual Customizer sanitizer must use canonical legacy four-to-seven lane expansion.'

# Invalid input cannot mutate public state, and routine curation cannot publish or schedule posts.
$validationPos = $studio.IndexOf('lunara_journal_archive_studio_validate_config')
$applyPos      = $studio.IndexOf('lunara_journal_archive_studio_apply_config')
Assert-True ($validationPos -ge 0 -and $applyPos -gt $validationPos) 'Validation must exist before public state is applied.'
Assert-True ($studio -notmatch '\bwp_(insert|update)_post\s*\(') 'Journal Studio must never insert, publish, schedule, or rewrite posts.'
Assert-True ($studio -notmatch "post_status\s*=>\s*'(publish|future)'") 'Journal Studio must not grant publication or scheduling authority.'
Assert-True ($studio -notmatch '\bwp_cache_flush\s*\(') 'Journal saves must never trigger a global object-cache purge.'
Assert-True ($studio -match "wp_cache_delete\(\s*'journal_archive_studio_public'\s*,\s*'lunara'\s*\)") 'Journal saves must invalidate only their route/data cache key.'
Assert-True ($studio -match "'/journal/'[\s\S]*?'/journal_section/'[\s\S]*?'/journal_topic/'[\s\S]*?'/journal_type/'") 'Targeted invalidation must name the Journal archive and all Journal taxonomy route families.'
Assert-True ($studio -match 'get_terms\([\s\S]*?get_term_link\([\s\S]*?rocket_clean_files\(\s*\$urls\s*\)') 'The bounded cleaner must resolve actual term URLs and use a supported per-URL cache API when available.'
Assert-True ($studio -notmatch 'rocket_clean_domain') 'Journal saves must never purge the full WP Rocket domain cache.'
Assert-True ($studio -match "'validator_result'\s*=>[\s\S]*?'prior_public'\s*=>") 'Every revision must record validation and prior-public audit semantics.'
Assert-True ($style -match '(?m)^Version:\s*3\.2\.45\s*$') 'Theme version must be 3.2.45.'

Write-Host 'journal-archive-studio: all assertions passed.'
