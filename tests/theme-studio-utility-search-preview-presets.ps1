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
$search = Read-ThemeFile 'search.php'
$notFound = Read-ThemeFile '404.php'

Assert-True ($controlDesk -match 'function\s+lunara_control_desk_utility_search_preset_specs') 'Utility Search Studio must define preset specs.'
Assert-True ($controlDesk -match 'function\s+lunara_control_desk_utility_search_active_preset_key') 'Utility Search Studio must detect the active preset.'
Assert-True ($controlDesk -match 'function\s+lunara_control_desk_apply_utility_search_values') 'Utility Search Studio must apply preset values through bounded helpers.'
Assert-True ($controlDesk -match 'function\s+lunara_control_desk_utility_search_preset_preview_url') 'Utility Search Studio must generate preset preview URLs.'
Assert-True ($controlDesk -match 'function\s+lunara_control_desk_render_utility_search_preset_card') 'Utility Search Studio must render preset cards.'

foreach ($preset in @(
    'balanced-desk',
    'ledger-signal',
    'criticism-run',
    'journal-desk',
    'navigation-clean'
)) {
    Assert-True ($controlDesk -match [regex]::Escape("'$preset'")) "Utility Search preset specs must include $preset."
}

foreach ($key in @(
    'lunara_utility_search_preset',
    'lunara_utility_search_density',
    'lunara_utility_result_treatment',
    'lunara_utility_result_media',
    'lunara_utility_recovery_prominence',
    'lunara_utility_search_lead_focus',
    'lunara_utility_search_spotlight_type',
    'lunara_utility_reentry_primary',
    'lunara_utility_section_gap',
    'lunara_utility_result_min_height',
    'lunara_utility_card_grid_min'
)) {
    Assert-True ($controlDesk -match [regex]::Escape("'$key'")) "Utility Search presets must cover $key."
}

Assert-True ($controlDesk -match 'name="lunara_utility_search_preset"') 'Preset cards must post the selected preset key.'
Assert-True ($controlDesk -match 'utility_search_preset_applied') 'Preset apply action must return a dedicated admin notice.'
Assert-True ($controlDesk -match 'lunara-utility-preset') 'Preset preview links must use the request-only preview key.'
Assert-True ($controlDesk -match 'request-only') 'Preset UI must explain previews are request-only.'
Assert-True ($controlDesk -notmatch '<textarea[^>]+lunara_utility') 'Utility Search preset controls must not expose raw CSS textareas.'

Assert-True ($frontend -match 'function\s+lunara_get_utility_search_preview_preset_values') 'Frontend must define Utility Search preview preset reader.'
Assert-True ($frontend -match "current_user_can\(\s*'edit_theme_options'\s*\)") 'Preview preset reader must require theme editing permission.'
Assert-True ($frontend.Contains("`$_GET['lunara-utility-preset']")) 'Preview preset reader must read the request-only preset key.'
Assert-True ($frontend -match 'lunara_get_utility_search_studio_select_value') 'Utility Search CSS must read select values through a preview-aware helper.'
Assert-True ($frontend -match 'lunara_get_utility_search_studio_number_value') 'Utility Search CSS must read number values through a preview-aware helper.'
Assert-True ($frontend -match 'is_search\(\)\s*\|\|\s*is_404\(\)') 'Utility Search CSS must remain scoped to Search and 404 routes.'

Assert-True ($search -match 'lunara_get_utility_search_preview_preset_values') 'Search template must read Utility Search preset previews.'
Assert-True ($search -match 'lunara_utility_search_lead_focus') 'Search template must keep reading lead focus.'
Assert-True ($search -match 'lunara_utility_search_spotlight_type') 'Search template must keep reading spotlight type.'

Assert-True ($notFound -match 'lunara_get_utility_search_preview_preset_values') '404 template must read Utility Search preset previews.'
Assert-True ($notFound -match 'lunara_utility_reentry_primary') '404 template must keep reading primary re-entry.'

Write-Host 'Utility Search preview presets contract passed.'
