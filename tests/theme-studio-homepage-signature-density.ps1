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
$homeModules = Read-ThemeFile 'assets/css/lunara-home-modules.css'

foreach ($key in @(
    'lunara_home_latest_reviews_density',
    'lunara_home_journal_lane_density',
    'lunara_home_oscar_facts_density'
)) {
    Assert-True ($controlDesk -match [regex]::Escape("'$key'")) "Homepage Studio must define the $key select control."
    Assert-True ($frontend -match [regex]::Escape("'$key'")) "Homepage public CSS must read the $key setting."
}

foreach ($key in @(
    'lunara_home_latest_reviews_card_min_height',
    'lunara_home_journal_card_min_height',
    'lunara_home_oscar_facts_card_min_height'
)) {
    Assert-True ($controlDesk -match [regex]::Escape("'$key'")) "Homepage Studio must define the $key numeric control."
    Assert-True ($frontend -match [regex]::Escape("'$key'")) "Homepage public CSS must read the $key setting."
}

foreach ($option in @('compact','editorial','showcase')) {
    Assert-True ($controlDesk -match [regex]::Escape("'$option'")) "Homepage signature density controls must support $option."
}

Assert-True ($controlDesk -match 'foreach\s*\(\s*lunara_control_desk_homepage_select_specs\(\)') 'Homepage Studio save handler must persist select controls generically.'
Assert-True ($controlDesk -match 'foreach\s*\(\s*lunara_control_desk_homepage_number_specs\(\)') 'Homepage Studio save handler must persist numeric controls generically.'
Assert-True ($controlDesk -notmatch '<textarea[^>]+lunara_home_.*density') 'Homepage Studio must not expose raw CSS or density textareas.'

foreach ($variable in @(
    '--lunara-home-latest-reviews-card-min',
    '--lunara-home-journal-card-min',
    '--lunara-home-oscar-facts-card-min',
    '--lunara-home-latest-reviews-gap',
    '--lunara-home-journal-gap',
    '--lunara-home-oscar-facts-body-clamp'
)) {
    Assert-True ($frontend.Contains($variable)) "Homepage public CSS must emit $variable."
}

foreach ($selector in @(
    '.lunara-latest-reviews-section',
    '.lunara-review-grid-card',
    '.lunara-journal-home-grid',
    '.lunara-journal-home-card',
    '.lunara-oscar-facts-section',
    '.lunara-oscar-fact-card-link'
)) {
    Assert-True ($homeModules.Contains($selector)) "Homepage public CSS must scope signature density to $selector."
}

Assert-True ($frontend -match 'is_front_page\(\)') 'Homepage signature density CSS must stay scoped to the front page.'
Assert-True ($homeModules -match 'line-clamp') 'Homepage signature density CSS must tune text depth, not only spacing.'

Write-Host 'Homepage signature density controls contract passed.'
