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

foreach ($key in @(
    'lunara_utility_search_lead_focus',
    'lunara_utility_search_spotlight_type',
    'lunara_utility_reentry_primary'
)) {
    Assert-True ($controlDesk -match [regex]::Escape("'$key'")) "Utility Search Studio must define the $key focus control."
    Assert-True ($frontend -match [regex]::Escape("'$key'")) "Utility Search public CSS must read the $key focus value."
}

foreach ($option in @(
    'balanced',
    'ledger',
    'reviews',
    'journal',
    'automatic',
    'review',
    'page',
    'home',
    'oscars',
    'search'
)) {
    Assert-True ($controlDesk -match [regex]::Escape("'$option'")) "Utility Search focus controls must support the $option option."
}

Assert-True ($controlDesk -match 'function\s+lunara_control_desk_utility_search_focus_select_specs') 'Utility Search Studio must define focus select specs.'
Assert-True ($controlDesk -match 'lunara_control_desk_utility_search_focus_select_specs\(\)') 'Utility Search save/render paths must use focus select specs.'
Assert-True ($controlDesk -match 'lunara_utility_search_focus_select') 'Utility Search focus controls must save through the existing form.'
Assert-True ($controlDesk -match 'Search Focus') 'Utility Search Studio must render a Search Focus control group.'
Assert-True ($controlDesk -notmatch '<textarea[^>]+lunara_utility') 'Utility Search focus controls must not expose raw CSS textareas.'

Assert-True ($search -match 'lunara_utility_search_lead_focus') 'Search template must read the lead focus control.'
Assert-True ($search -match 'lunara_utility_search_spotlight_type') 'Search template must read the spotlight type control.'
Assert-True ($search -match 'lunara_search_focus_order_posts') 'Search template must order the returned display posts through a focus helper.'
Assert-True ($search -match 'lunara_search_render_oscar_matches') 'Search template must render Oscar matches through a reusable section helper.'
Assert-True ($search -match 'lunara-search-page--focus-') 'Search template must expose a stable focus class.'
Assert-True ($search -match 'lunara-search-page--spotlight-') 'Search template must expose a stable spotlight class.'

Assert-True ($notFound -match 'lunara_utility_reentry_primary') '404 template must read the primary re-entry control.'
Assert-True ($notFound -match 'lunara_404_order_reentry_actions') '404 template must order actions through a re-entry helper.'
Assert-True ($notFound -match 'lunara-404-page--primary-') '404 template must expose a stable primary-route class.'

foreach ($selector in @(
    'body.search .lunara-search-page--focus-ledger',
    'body.search .lunara-search-page--focus-reviews',
    'body.search .lunara-search-page--focus-journal',
    'body.search .lunara-search-page--spotlight-review',
    'body.error404 .lunara-404-page--primary-reviews',
    'body.error404 .lunara-404-page--primary-oscars'
)) {
    Assert-True ($frontend.Contains($selector)) "Utility Search focus CSS must stay scoped to $selector."
}

Assert-True ($frontend -match 'is_search\(\)\s*\|\|\s*is_404\(\)') 'Utility Search focus CSS must remain scoped to Search and 404 routes.'

Write-Host 'Utility Search focus controls contract passed.'
