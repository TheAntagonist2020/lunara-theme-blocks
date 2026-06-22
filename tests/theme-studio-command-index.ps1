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
$frontend = Read-ThemeFile 'inc/frontend.php'

Assert-True ($controlDesk -match 'function\s+lunara_control_desk_theme_studio_command_index_items') 'Theme Studio must define command index items in a helper.'
Assert-True ($controlDesk -match 'function\s+lunara_control_desk_render_theme_studio_command_index') 'Theme Studio must render a command index.'
Assert-True ($controlDesk -match 'lunara_control_desk_render_theme_studio_command_index\(\)') 'Theme Studio tab must call the command index renderer.'

foreach ($label in @(
    'Theme Studio Command Index',
    'Brand Console',
    'Homepage Studio',
    'Journal Archive Studio',
    'Reviews Archive Studio',
    'Image Authority',
    'Oscar Facts'
)) {
    Assert-True ($controlDesk.Contains($label)) "Command index must include the $label label."
}

foreach ($anchor in @(
    '#lunara-theme-studio-brand-console',
    '#lunara-theme-studio-homepage-studio',
    '#lunara-theme-studio-journal-archive-studio',
    '#lunara-theme-studio-reviews-archive-studio',
    '#lunara-theme-studio-image-quality'
)) {
    Assert-True ($controlDesk.Contains($anchor)) "Command index must link to $anchor."
}

foreach ($surface in @(
    "home_url( '/' )",
    "home_url( '/journal/' )",
    "home_url( '/reviews/' )",
    "home_url( '/review/sinners-2025/' )",
    "home_url( '/oscars/' )"
)) {
    Assert-True ($controlDesk.Contains($surface)) "Command index must include preview URL $surface."
}

Assert-True ($controlDesk -match "add_query_arg\(\s*'lunara-width'\s*,\s*'390'") 'Command index must include 390px mobile preview links.'
Assert-True ($controlDesk -match 'current_user_can\(\s*''edit_theme_options''') 'Command index must remain admin capability aware.'
Assert-True ($controlDesk -notmatch 'admin_post_lunara_save_theme_studio_command_index') 'Command index must not create a save handler.'
Assert-True ($controlDesk -notmatch '<textarea[^>]+command') 'Command index must not expose raw CSS or command textareas.'
Assert-True ($frontend -notmatch 'lunara-theme-studio-command-index') 'Command index must not render through public frontend output.'

foreach ($class in @(
    '.lunara-control-desk-command-index',
    '.lunara-control-desk-command-grid',
    '.lunara-control-desk-command-card',
    '.lunara-control-desk-command-actions'
)) {
    Assert-True ($adminCss.Contains($class)) "Command index admin CSS must define $class."
}

Write-Host 'Theme Studio command index contract passed.'
