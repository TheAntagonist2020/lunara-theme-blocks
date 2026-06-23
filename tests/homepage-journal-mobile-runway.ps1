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

$frontend = Read-ThemeFile 'inc/frontend.php'
$functions = Read-ThemeFile 'functions.php'
$controlDesk = Read-ThemeFile 'inc/control-desk.php'

Assert-True ($functions -match 'function\s+lunara_render_homepage_journal_lane\(\)') 'Homepage Journal lane renderer must remain the source of truth.'
Assert-True ($functions -match "echo\s+\`$has_visual\s*\?\s*'has-visual'\s*:\s*'has-no-visual'") 'Homepage Journal cards must expose visual/no-visual classes.'
Assert-True ($functions -match 'if\s*\(\s*\$has_visual\s*\)\s*:\s*[\s\S]*lunara-journal-home-card-media') 'Homepage Journal renderer must only print media wrappers for visual cards.'

Assert-True ($frontend -match 'function lunara_home_journal_mobile_runway_css\(\)') 'Homepage Journal mobile runway must live in a named frontend CSS emitter.'
Assert-True ($frontend -match 'lunara-home-journal-mobile-runway-css') 'Homepage Journal mobile runway must render a distinct style id.'
Assert-True ($frontend -match "add_action\(\s*'wp_footer',\s*'lunara_home_journal_mobile_runway_css',\s*139\s*\)") 'Homepage Journal mobile runway must load after the Reviews mobile runway.'
Assert-True ($frontend -match 'is_front_page\(\)') 'Homepage Journal mobile runway CSS must stay scoped to the front page.'

$match = [regex]::Match($frontend, 'function lunara_home_journal_mobile_runway_css\(\) \{(?s).*?add_action\(\s*''wp_footer'',\s*''lunara_home_journal_mobile_runway_css'',\s*139\s*\);')
Assert-True $match.Success 'Could not isolate homepage Journal mobile runway block.'
$block = $match.Value

foreach ($needle in @(
    '@media(max-width:820px)',
    'body.home .lunara-dispatches-section .lunara-journal-home-grid{grid-template-columns:minmax(0,1fr)!important;',
    'body.home .lunara-dispatches-section .lunara-journal-home-card{min-height:0!important;height:auto!important;overflow:hidden;',
    'body.home .lunara-dispatches-section .lunara-journal-home-card.has-visual:not(.is-lead) .lunara-journal-home-card-link{display:grid!important;grid-template-columns:minmax(112px,38vw) minmax(0,1fr)!important;',
    'body.home .lunara-dispatches-section .lunara-journal-home-card.is-lead.has-visual .lunara-journal-home-card-media{max-height:clamp(180px,50vw,260px)!important;',
    'body.home .lunara-dispatches-section .lunara-journal-home-card.has-no-visual .lunara-journal-home-card-link{grid-template-columns:minmax(0,1fr)!important;',
    'body.home .lunara-dispatches-section .lunara-journal-home-card.has-no-visual .lunara-journal-home-card-copy{min-height:0!important;',
    'body.home .lunara-dispatches-section .lunara-journal-home-card-media{aspect-ratio:16/10!important;',
    'body.home .lunara-dispatches-section .lunara-journal-home-card-excerpt{-webkit-line-clamp:2!important;',
    '@media(max-width:520px)',
    '@media(prefers-reduced-motion:reduce)'
)) {
    Assert-True ($block.Contains($needle)) "Homepage Journal mobile runway is missing expected CSS: $needle"
}

Assert-True ($block -notmatch 'font-family\s*:') 'Homepage Journal mobile runway must not introduce another font family.'
Assert-True ($block -notmatch 'set_theme_mod|get_option\(') 'Homepage Journal mobile runway must not mutate or add settings.'
Assert-True ($controlDesk -match "'mobile_order'\s*=>\s*array\(\s*'hero',\s*'dispatch',\s*'latest-reviews'") 'Homepage mobile order presets must still put Journal before Latest Reviews.'
Assert-True ($controlDesk -match "'desktop_order'\s*=>\s*array\(\s*'hero',\s*'latest-reviews',\s*'dispatch'") 'Homepage desktop order presets must still put Latest Reviews before Journal.'

Write-Host 'Homepage Journal mobile runway contract passed.'
