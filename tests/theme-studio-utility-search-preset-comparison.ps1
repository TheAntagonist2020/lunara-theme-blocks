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

Assert-True ($controlDesk -match 'function\s+lunara_control_desk_render_utility_search_preset_comparison_strip') 'Utility Search Studio must render a preset comparison strip.'
Assert-True ($controlDesk -match 'function\s+lunara_control_desk_render_utility_search_preset_comparison_item') 'Utility Search comparison strip must render normalized preset items.'
Assert-True ($controlDesk -match 'function\s+lunara_control_desk_render_utility_search_studio[\s\S]+lunara_control_desk_render_utility_search_preset_comparison_strip\(\s*\$presets,\s*\$active_preset_key\s*\)') 'Utility Search Studio must call the comparison strip renderer.'
Assert-True ($controlDesk -match 'lunara-control-desk-utility-comparison-strip') 'Utility Search comparison strip must use a stable wrapper class.'
Assert-True ($adminCss -match '\.lunara-control-desk-utility-comparison-strip') 'Control Desk CSS must style the comparison strip.'

foreach ($preset in @(
    'balanced-desk',
    'ledger-signal',
    'criticism-run',
    'journal-desk',
    'navigation-clean'
)) {
    Assert-True ($controlDesk -match [regex]::Escape("'$preset'")) "Comparison strip must be based on existing preset specs that include $preset."
}

foreach ($label in @(
    'Density',
    'Result treatment',
    'Result media',
    'Search lead focus',
    'Spotlight type',
    '404 primary path',
    'Section gap',
    'Result minimum height',
    'Card grid minimum'
)) {
    Assert-True ($controlDesk -match [regex]::Escape($label)) "Comparison strip must expose $label."
}

Assert-True ($controlDesk -match 'lunara_control_desk_utility_search_preset_specs\(\)') 'Comparison strip must reuse the existing Utility Search preset specs.'
Assert-True ($controlDesk -match "__\(\s*'Default'\s*,\s*'lunara-film'\s*\)") 'Comparison strip must have a Default fallback for missing preset values.'
Assert-True ($controlDesk -match 'esc_html') 'Comparison strip output must escape labels and values.'
Assert-True ($controlDesk -notmatch 'lunara-utility-compare') 'Comparison strip must not introduce a new public comparison query variable.'
Assert-True ($controlDesk -notmatch '<textarea[^>]+lunara_utility') 'Comparison strip must not expose raw CSS textareas.'

Write-Host 'Utility Search preset comparison contract passed.'
