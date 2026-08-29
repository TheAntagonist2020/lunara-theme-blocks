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

$runtime = & php (Join-Path $PSScriptRoot 'site-studio-runtime.php') 2>&1
Assert-True ($LASTEXITCODE -eq 0) ("Site Studio runtime failed: " + ($runtime -join [Environment]::NewLine))
$compositionRuntime = & php (Join-Path $PSScriptRoot 'home-block-composition-runtime.php') 2>&1
Assert-True ($LASTEXITCODE -eq 0) ("Homepage composition runtime failed: " + ($compositionRuntime -join [Environment]::NewLine))

$studio    = Read-ThemeFile 'inc/site-studio.php'
$registry  = Read-ThemeFile 'inc/site-studio-registry.php'
$control   = Read-ThemeFile 'inc/control-desk.php'
$blocks    = Read-ThemeFile 'inc/blocks.php'
$blockHub  = Read-ThemeFile 'inc/blocks-hub.php'
$hubEditor = Read-ThemeFile 'assets/js/lunara-blocks-hub.js'
$blockJs   = Read-ThemeFile 'assets/js/lunara-blocks.js'
$loader    = Read-ThemeFile 'functions-loader.php'
$functions = Read-ThemeFile 'functions.php'
$homeBlocks = Read-ThemeFile 'inc/home-blocks.php'
$helpers   = Read-ThemeFile 'inc/helpers.php'
$journalArchive = Read-ThemeFile 'inc/journal-archive-studio.php'
$style     = Read-ThemeFile 'style.css'
$controlCss = Read-ThemeFile 'assets/css/lunara-control-desk.css'
$studioCss = Read-ThemeFile 'assets/css/lunara-site-studio.css'
$editorCss = Read-ThemeFile 'assets/css/lunara-homepage-editor.css'

# Dedicated, capability-gated, focused admin surface.
Assert-True ($loader -match 'require_once\s+\$lunara_inc\s*\.\s*''site-studio\.php'';') 'The split loader must load the Site Studio module.'
Assert-True ($studio -match "add_submenu_page\([\s\S]*?'lunara-control-desk'[\s\S]*?'edit_theme_options'[\s\S]*?'lunara-site-studio'") 'Site Studio must be a discoverable Lunara submenu gated by edit_theme_options.'
Assert-True ($studio -match "current_user_can\(\s*'edit_theme_options'\s*\)") 'Site Studio must enforce capability inside its renderer.'
Assert-True ($control -notmatch "in_array\(\s*\$hook[\s\S]{0,180}lunara_page_lunara-site-studio") 'Site Studio must not receive the Control Desk bundle.'
Assert-True ($registry -match "'lunara-method'[\s\S]*?'homepage-structure'[\s\S]*?'reviews-archive'[\s\S]*?'journal-archive'") 'The unconditional Site Studio registry must expose the focused Homepage and Archive surfaces.'

# One Method form and one secure storage path.
Assert-True ($control -match 'function\s+lunara_control_desk_render_pairing_desk_form\s*\(') 'The Lunara Method form must be a shared Control Desk renderer.'
Assert-True (($control | Select-String -Pattern 'name="lunara_home_pairing_desk_copy"' -AllMatches).Matches.Count -eq 1) 'The Method supporting-copy field must be rendered in exactly one shared form.'
Assert-True ($helpers -match 'function\s+lunara_home_pairing_desk_copy_defaults\s*\(') 'The Method editor and public renderer must share one fallback-copy source.'
Assert-True ($helpers -match 'No other film desk builds this rail\.') 'The shared Method fallback must include the complete public sentence.'
Assert-True ($control -match 'lunara_home_pairing_desk_copy_defaults\(\)') 'The Method form must preview the exact shared public fallback.'
Assert-True ($functions -match 'lunara_home_pairing_desk_copy_defaults\(\)') 'The public Method renderer must consume the shared fallback without changing its output.'
Assert-True ($control -match "check_admin_referer\(\s*'lunara_save_pairing_desk_copy'\s*,\s*'lunara_pairing_desk_copy_nonce'\s*\)") 'The existing Method save handler must retain its nonce.'
Assert-True ($control -match "current_user_can\(\s*'edit_theme_options'\s*\)") 'The existing Method save handler must retain its capability gate.'
foreach ($setting in @(
    'lunara_home_pairing_desk_kicker',
    'lunara_home_pairing_desk_title',
    'lunara_home_pairing_desk_copy',
    'lunara_home_pairing_desk_review_id',
    'lunara_home_pairing_desk_backdrop_id'
)) {
    Assert-True ($control -match [regex]::Escape($setting)) "The shared Method editor must retain $setting."
}
Assert-True ($control -match "'lunara_pairing_desk_return'[\s\S]*?'site-studio'") 'The Method handler must use a bounded return context for the direct Site Studio form.'
foreach ($returnContract in @(
    @{ Field = 'lunara_homepage_studio_return'; Surface = 'homepage-structure' },
    @{ Field = 'lunara_reviews_archive_return'; Surface = 'reviews-archive' }
)) {
    Assert-True ($control -match [regex]::Escape($returnContract.Field)) "$($returnContract.Surface) must post a bounded return context."
    $boundedCall = "lunara_control_desk_bounded_return_url\(\s*'" + [regex]::Escape($returnContract.Field) + "'\s*,\s*'" + [regex]::Escape($returnContract.Surface) + "'"
    Assert-True ($control -match $boundedCall) "$($returnContract.Surface) saves must return to the same focused Site Studio surface."
}
Assert-True ($journalArchive -match 'name="lunara_journal_archive_return"[\s\S]{0,120}?value="<\?php echo esc_attr\(\s*\$context\s*\)') 'The focused Journal Studio must post its bounded return context from its canonical owner.'
Assert-True ($journalArchive -match "lunara_control_desk_bounded_return_url\(\s*'lunara_journal_archive_return'\s*,\s*'journal-archive'[\s\S]{0,180}?surface=journal-archive'") 'Journal Archive saves must return to the focused Site Studio surface through the shared bounded resolver.'
Assert-True ($journalArchive -match "add_action\(\s*'admin_post_lunara_save_journal_archive_studio'\s*,\s*'lunara_control_desk_save_journal_archive_studio'\s*\)") 'The focused Journal owner must register its save handler exactly once.'
Assert-True ($control -match 'lunara_get_home_section_registry\(\)[\s\S]*?lunara_home_section_block_map\(\)') 'Homepage Structure visibility must derive from the canonical registry and six-renderer map.'
foreach ($setting in @(
    'lunara_home_show_hero',
    'lunara_home_show_latest_reviews',
    'lunara_home_show_pairing_desk',
    'lunara_home_show_dispatch',
    'lunara_home_show_oscar_picks',
    'lunara_home_show_oscar_facts'
)) {
    Assert-True ($helpers -match [regex]::Escape($setting)) "The canonical Homepage registry must represent $setting."
}
Assert-True ($control -match 'Hero Command owns the live front door|Fixed by Hero Command') 'Homepage Structure must label the cinematic Hero as fixed while Hero Command owns the front door.'
$presetStart = $control.IndexOf('function lunara_control_desk_homepage_order_preset_specs')
$presetEnd = $control.IndexOf('function lunara_control_desk_homepage_order_preset_value', $presetStart)
$presetSlice = $control.Substring($presetStart, $presetEnd - $presetStart)
Assert-True (($presetSlice | Select-String -Pattern "'pairing-desk'" -AllMatches).Matches.Count -ge 6) 'Every desktop and mobile Homepage preset must place the Lunara Method explicitly.'
Assert-True ($control -match 'function\s+lunara_control_desk_render_homepage_structure_controls\s*\(') 'Homepage Structure must have one focused six-lane renderer.'
Assert-True ($control -match 'name="lunara_homepage_studio_scope"\s+value="structure"') 'The focused Structure form must identify its bounded save scope.'
Assert-True ($control -match '''structure''\s*===\s*\$save_scope') 'The shared Homepage handler must avoid resetting advanced fields on a Structure-only save.'
Assert-True ($control -match 'Open Advanced Homepage Tuning') 'Focused Homepage Structure must link to the legacy advanced controls instead of rendering that full stack.'

# Front-page editor gets a direct way out of the massive canvas.
Assert-True ($blockHub -match "wp_localize_script\(\s*'lunara-blocks-hub'\s*,\s*'LunaraSiteStudioConfig'") 'Lunara Studio must receive editor-only Site Studio configuration.'
Assert-True ($hubEditor -match 'getCurrentPostId') 'Lunara Studio must identify the front page from editor state.'
Assert-True ($hubEditor -match 'Open Site Studio') 'The front-page callout must link directly to Site Studio.'
Assert-True ($hubEditor -match 'Edit Lunara Method') 'The front-page callout must link directly to the Lunara Method editor.'

# One canonical six-section editor kit, with compact cards instead of live SSR.
Assert-True ($blocks -match "add_action\(\s*'enqueue_block_editor_assets'") 'Homepage editor configuration must load only in the block editor.'
Assert-True ($blocks -match "wp_localize_script\(\s*'lunara-blocks'\s*,\s*'LunaraHomepageEditorConfig'") 'Homepage section cards must receive localized edit and view links.'
Assert-True ($blocks -match 'lunara_home_section_block_map\(\)') 'Editor configuration must use the canonical six-section block registry.'
Assert-True ($blockJs -match 'function\s+homepageSectionCard\s*\(') 'Homepage blocks must render the shared compact editor card.'
Assert-True ($blockJs -match 'MediaUpload[\s\S]*?overrideImageId') 'The canonical Hero inspector must preserve image select, replace, and clear controls.'
Assert-True ($blockJs -match "function\s+textarea\([\s\S]*?TextareaControl[\s\S]*?textarea\(\s*__\(\s*'Fallback excerpt'[\s\S]*?'overrideExcerpt'\s*\)") 'The canonical Hero inspector must preserve a multiline fallback excerpt control.'
Assert-True ($blocks -match "'editLabel'\s*=>\s*\`$edit_label") 'Compact cards must expose a localized edit label.'
foreach ($editLabel in @('Edit in Hero Command', 'Edit Lunara Method', 'Open Homepage Structure')) {
    Assert-True ($blocks -match [regex]::Escape($editLabel)) "Compact cards must name the true control owner: $editLabel."
}
foreach ($attribute in @('source', 'count', 'heading', 'kicker', 'ctaLabel', 'ctaUrl')) {
    Assert-True ($blockJs -match [regex]::Escape($attribute)) "The canonical Latest Reviews inspector must preserve $attribute."
}
foreach ($blockName in @(
    'lunara/cinematic-hero',
    'lunara/latest-reviews',
    'lunara/pairing-desk',
    'lunara/journal-lane',
    'lunara/oscar-picks',
    'lunara/oscar-facts'
)) {
    Assert-True ($blockJs -match [regex]::Escape($blockName)) "Canonical homepage editor kit must register $blockName."
}
$kitStart = $blockJs.IndexOf('Homepage section kit')
Assert-True ($kitStart -ge 0) 'Homepage section kit marker is missing.'
$kitEnd = $blockJs.IndexOf('End homepage section kit', $kitStart)
Assert-True ($kitEnd -gt $kitStart) 'Homepage section kit end marker is missing.'
$kit = $blockJs.Substring($kitStart, $kitEnd - $kitStart)
Assert-True ($kit -notmatch 'ServerSideRender') 'Homepage section kit must not render full public sections through ServerSideRender.'
Assert-True ($kit -notmatch '\bpreview\s*\(') 'Homepage section kit must use compact editor cards instead of the legacy preview helper.'
Assert-True ($editorCss -match 'max-width:\s*min\(' -and $editorCss -match 'overflow-wrap:\s*anywhere') 'Compact homepage cards must stay bounded inside the editor canvas.'
Assert-True ($studioCss -match '\.lunara-site-studio-map') 'The focused Site Studio must have a dedicated visual map treatment.'
Assert-True ($functions -notmatch 'lunara_enqueue_homepage_block_editor_assets') 'The duplicate inline homepage block registrar must remain retired.'
Assert-True ($functions -match "register_block_type\(\s*'lunara/latest-reviews'[\s\S]*?'editor_script'\s*=>\s*'lunara-blocks'") 'Latest Reviews must use the one canonical editor bundle.'
Assert-True ($functions -match "register_block_type\(\s*'lunara/latest-reviews'[\s\S]*?'category'\s*=>\s*'lunara'") 'Latest Reviews must live in the discoverable Lunara block category.'
Assert-True ($homeBlocks -match 'serialize_block\(\s*\$block\s*\)') 'Homepage Structure write-through must preserve noncanonical blocks instead of replacing the whole page.'

# Public renderers remain dynamic and unchanged in ownership.
foreach ($renderer in @(
    'lunara_render_cinematic_hero_carousel',
    'lunara_render_homepage_latest_reviews',
    'lunara_render_home_pairing_desk',
    'lunara_render_homepage_journal_lane',
    'lunara_render_oscar_picks_carousel',
    'lunara_render_oscar_facts_carousel'
)) {
    Assert-True ($functions -match [regex]::Escape($renderer)) "Public renderer ownership must retain $renderer."
}
Assert-True ($studio -notmatch "add_action\(\s*'wp_enqueue_scripts'") 'Site Studio must not add public assets.'
Assert-True ($style -match '(?m)^Version:\s*3\.2\.54\s*$') 'Theme version must be 3.2.54.'

Write-Host 'site-studio: all assertions passed.'
