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

$controlDesk = Read-ThemeFile 'inc/control-desk.php'
$frontend = Read-ThemeFile 'inc/frontend.php'

foreach ($key in @(
    'lunara_utility_search_density',
    'lunara_utility_result_treatment',
    'lunara_utility_result_media',
    'lunara_utility_recovery_prominence'
)) {
    Assert-True ($controlDesk -match [regex]::Escape("'$key'")) "Utility Search Studio must define the $key select control."
    Assert-True ($frontend -match [regex]::Escape("'$key'")) "Utility Search public CSS must read the $key setting."
}

foreach ($key in @(
    'lunara_utility_section_gap',
    'lunara_utility_result_min_height',
    'lunara_utility_card_grid_min'
)) {
    Assert-True ($controlDesk -match [regex]::Escape("'$key'")) "Utility Search Studio must define the $key numeric control."
    Assert-True ($frontend -match [regex]::Escape("'$key'")) "Utility Search public CSS must read the $key setting."
}

foreach ($option in @(
    'compact',
    'editorial',
    'showcase',
    'list',
    'cards',
    'spotlight',
    'guarded',
    'poster-led',
    'text-led',
    'quiet',
    'standard',
    'strong'
)) {
    Assert-True ($controlDesk -match [regex]::Escape("'$option'")) "Utility Search Studio must support the $option option."
}

Assert-True ($controlDesk -match 'function\s+lunara_control_desk_utility_search_select_specs') 'Utility Search Studio must define select specs.'
Assert-True ($controlDesk -match 'function\s+lunara_control_desk_utility_search_number_specs') 'Utility Search Studio must define number specs.'
Assert-True ($controlDesk -match 'function\s+lunara_control_desk_render_utility_search_studio') 'Theme Studio must render a Utility Search Studio panel.'
Assert-True ($controlDesk -match 'lunara_control_desk_render_utility_search_studio\(\)') 'Theme Studio tab must call the Utility Search Studio renderer.'
Assert-True ($controlDesk -match 'id="lunara-theme-studio-utility-search-studio"') 'Utility Search Studio panel must have a stable anchor.'
Assert-True ($controlDesk -match 'admin_post_lunara_save_utility_search_studio') 'Utility Search Studio must save through admin-post.'
Assert-True ($controlDesk -match 'check_admin_referer\(\s*''lunara_save_utility_search_studio''') 'Utility Search Studio save handler must verify a nonce.'
Assert-True ($controlDesk -match 'current_user_can\(\s*''edit_theme_options''') 'Utility Search Studio must remain capability protected.'
Assert-True ($controlDesk -notmatch '<textarea[^>]+lunara_utility') 'Utility Search Studio must not expose raw CSS textareas.'

foreach ($preview in @(
    "home_url( '/?s=sinners' )",
    "home_url( '/definitely-not-a-real-lunara-route/' )"
)) {
    Assert-True ($controlDesk.Contains($preview)) "Utility Search Studio must include preview URL $preview."
}
Assert-True ($controlDesk -match "add_query_arg\(\s*'lunara-width'\s*,\s*'390'") 'Utility Search Studio must include 390px mobile preview links.'

foreach ($variable in @(
    '--lunara-utility-section-gap',
    '--lunara-utility-result-min-height',
    '--lunara-utility-result-grid-min',
    '--lunara-utility-result-media-fit',
    '--lunara-utility-result-copy-lines'
)) {
    Assert-True ($frontend.Contains($variable)) "Utility Search public CSS must emit $variable."
}

foreach ($selector in @(
    'body.search .lunara-search-page',
    'body.search .lunara-search-results-grid',
    'body.search .lunara-search-result-card',
    'body.search .lunara-search-oscar-grid',
    'body.search .lunara-search-empty-shell',
    'body.error404 .lunara-404-page',
    'body.error404 .lunara-404-panel'
)) {
    Assert-True ($frontend.Contains($selector)) "Utility Search public CSS must stay scoped to $selector."
}

Assert-True ($frontend -match 'is_search\(\)\s*\|\|\s*is_404\(\)') 'Utility Search CSS must stay scoped to Search and 404 routes.'
Assert-True ($frontend -match 'function\s+lunara_output_utility_search_studio_css') 'Utility Search Studio must emit a named public CSS function.'
Assert-True ($frontend -match 'add_action\(\s*''wp_head''\s*,\s*''lunara_output_utility_search_studio_css''') 'Utility Search public CSS must be hooked through wp_head.'
Assert-True ($frontend -match 'line-clamp') 'Utility Search controls must tune text depth, not only spacing.'

Write-Host 'Utility Search Studio controls contract passed.'
