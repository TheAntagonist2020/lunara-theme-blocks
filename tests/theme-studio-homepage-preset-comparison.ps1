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
$adminCss = Read-ThemeFile 'assets/css/lunara-control-desk.css'

Assert-True ($controlDesk -match 'function\s+lunara_control_desk_homepage_comparison_specs') 'Homepage Studio must define comparison specs for readable preset values.'
Assert-True ($controlDesk -match 'function\s+lunara_control_desk_homepage_preset_specs') 'Homepage Studio must define publication package presets.'
Assert-True ($controlDesk -match 'function\s+lunara_control_desk_homepage_active_preset_key') 'Homepage Studio must detect whether saved controls match a preset.'
Assert-True ($controlDesk -match 'function\s+lunara_control_desk_render_homepage_preset_comparison_item') 'Homepage Studio must render normalized comparison items.'
Assert-True ($controlDesk -match 'function\s+lunara_control_desk_render_homepage_preset_comparison_strip') 'Homepage Studio must render a preset comparison strip.'
Assert-True ($controlDesk -match 'function\s+lunara_control_desk_render_homepage_studio[\s\S]+lunara_control_desk_render_homepage_preset_comparison_strip\(\s*\$presets,\s*\$active_preset_key\s*\)') 'Homepage Studio must call the comparison strip renderer.'
Assert-True ($controlDesk -match 'lunara-control-desk-homepage-comparison-strip') 'Homepage comparison strip must use a stable wrapper class.'
Assert-True ($adminCss -match '\.lunara-control-desk-homepage-comparison-strip') 'Control Desk CSS must style the Homepage comparison strip.'

foreach ($preset in @(
    'trade-front-door',
    'journal-desk-day',
    'oscars-signature',
    'criticism-showcase'
)) {
    Assert-True ($controlDesk -match [regex]::Escape("'$preset'")) "Homepage comparison strip must include the $preset preset."
}

foreach ($label in @(
    'Front-door density',
    'Route-card prominence',
    'First-section rhythm',
    'Latest Reviews density',
    'Journal lane density',
    'Oscar Facts density',
    'Section order'
)) {
    Assert-True ($controlDesk -match [regex]::Escape($label)) "Homepage comparison strip must expose $label."
}

Assert-True ($controlDesk -match 'lunara_control_desk_homepage_select_specs\(\)') 'Homepage comparison strip must reuse existing select specs.'
Assert-True ($controlDesk -match 'lunara_control_desk_homepage_order_preset_specs\(\)') 'Homepage comparison strip must reuse existing order preset specs.'
Assert-True ($controlDesk -match "__\(\s*'Default'\s*,\s*'lunara-film'\s*\)") 'Homepage comparison strip must have a Default fallback for missing preset values.'
Assert-True ($controlDesk -match 'esc_html') 'Homepage comparison strip output must escape labels and values.'
Assert-True ($controlDesk -notmatch 'lunara-homepage-compare') 'Homepage comparison strip must not introduce a new public comparison query variable.'
Assert-True ($controlDesk -notmatch 'admin_post_lunara_save_homepage_preset') 'Homepage comparison strip must not introduce a new save handler in this read-only pass.'
Assert-True ($controlDesk -notmatch '<textarea[^>]+lunara_home') 'Homepage comparison strip must not expose raw CSS textareas.'

Write-Host 'Homepage preset comparison contract passed.'
