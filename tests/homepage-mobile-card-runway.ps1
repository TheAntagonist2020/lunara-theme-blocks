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

$frontend = Read-ThemeFile 'inc/frontend.php'
$controlDesk = Read-ThemeFile 'inc/control-desk.php'

Assert-True ($frontend -match 'function lunara_home_mobile_card_runway_css\(\)') 'Homepage mobile card runway must live in a named frontend CSS emitter.'
Assert-True ($frontend -match 'lunara-home-mobile-card-runway-css') 'Homepage mobile card runway must render a distinct style id.'
Assert-True ($frontend -match "add_action\(\s*'wp_footer',\s*'lunara_home_mobile_card_runway_css',\s*137\s*\)") 'Homepage mobile card runway must load after first-viewport polish.'
Assert-True ($frontend -match 'is_front_page\(\)') 'Homepage mobile card runway CSS must stay scoped to the front page.'

$match = [regex]::Match($frontend, 'function lunara_home_mobile_card_runway_css\(\) \{(?s).*?add_action\(\s*''wp_footer'',\s*''lunara_home_mobile_card_runway_css'',\s*137\s*\);')
Assert-True $match.Success 'Could not isolate homepage mobile card runway block.'
$block = $match.Value

foreach ($needle in @(
    '@media(max-width:820px)',
    'body.home .lunara-latest-reviews-section .lunara-review-grid{grid-template-columns:minmax(0,1fr)!important;',
    'body.home .lunara-latest-reviews-section .lunara-review-grid-card.has-visual .lunara-review-grid-link{display:grid!important;grid-template-columns:minmax(92px,34vw) minmax(0,1fr)!important;',
    'body.home .lunara-latest-reviews-section .lunara-review-grid-card.has-no-visual .lunara-review-grid-link{grid-template-columns:minmax(0,1fr)!important;',
    'body.home .lunara-latest-reviews-section .lunara-review-grid-poster-wrap{aspect-ratio:3/4!important;',
    'body.home .lunara-latest-reviews-section .lunara-review-grid-title{font-size:clamp(1rem,4.6vw,1.16rem)!important;',
    'body.home .lunara-latest-reviews-section .lunara-review-grid-quote{-webkit-line-clamp:2!important;',
    '@media(max-width:520px)',
    '@media(prefers-reduced-motion:reduce)'
)) {
    Assert-True ($block.Contains($needle)) "Homepage mobile card runway is missing expected CSS: $needle"
}

Assert-True ($block -notmatch 'font-family\s*:') 'Homepage mobile card runway must not introduce another font family.'
Assert-True ($block -notmatch 'set_theme_mod|get_option\(') 'Homepage mobile card runway must not mutate or add settings.'
Assert-True ($controlDesk -match "'mobile_order'\s*=>\s*array\(\s*'hero',\s*'dispatch',\s*'latest-reviews'") 'Homepage mobile order presets must still put Journal before Latest Reviews.'
Assert-True ($controlDesk -match "'desktop_order'\s*=>\s*array\(\s*'hero',\s*'latest-reviews',\s*'dispatch'") 'Homepage desktop order presets must still put Latest Reviews before Journal.'

Write-Host 'Homepage mobile card runway contract passed.'
