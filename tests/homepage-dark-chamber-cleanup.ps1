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

Assert-True ($functions -match "echo\s+\`$has_card_media\s*\?\s*'has-visual'\s*:\s*'has-no-visual'") 'Homepage Review cards must expose visual/no-visual classes.'
Assert-True ($functions -match 'if\s*\(\s*\$has_card_media\s*\)\s*:\s*[\s\S]*lunara-review-grid-poster-wrap') 'Homepage Review renderer must only print poster wrappers for cards with media.'
Assert-True ($functions -match "echo\s+\`$has_visual\s*\?\s*'has-visual'\s*:\s*'has-no-visual'") 'Homepage Journal cards must expose visual/no-visual classes.'
Assert-True ($functions -match 'if\s*\(\s*\$has_visual\s*\)\s*:\s*[\s\S]*lunara-journal-home-card-media') 'Homepage Journal renderer must only print media wrappers for visual cards.'

Assert-True ($frontend -match 'function lunara_home_text_led_card_chamber_css\(\)') 'Homepage text-led card cleanup must live in a named frontend CSS emitter.'
Assert-True ($frontend -match 'lunara-home-text-led-card-chamber-css') 'Homepage text-led card cleanup must render a distinct style id.'
Assert-True ($frontend -match "add_action\(\s*'wp_footer',\s*'lunara_home_text_led_card_chamber_css',\s*141\s*\)") 'Homepage text-led card cleanup must load after the mobile card runway CSS.'
Assert-True ($frontend -match 'is_front_page\(\)') 'Homepage text-led card cleanup CSS must stay scoped to the front page.'

$match = [regex]::Match($frontend, 'function lunara_home_text_led_card_chamber_css\(\) \{(?s).*?add_action\(\s*''wp_footer'',\s*''lunara_home_text_led_card_chamber_css'',\s*141\s*\);')
Assert-True $match.Success 'Could not isolate homepage text-led card cleanup block.'
$block = $match.Value

foreach ($needle in @(
    'body.home .lunara-latest-reviews-section .lunara-review-grid-card.has-no-visual{min-height:clamp(220px,18vw,310px)!important;height:auto!important;align-self:start!important;',
    'body.home .lunara-latest-reviews-section .lunara-review-grid-card.has-no-visual .lunara-review-grid-link{min-height:0!important;height:auto!important;grid-template-rows:auto!important;',
    'body.home .lunara-latest-reviews-section .lunara-review-grid-card.has-no-visual .lunara-review-grid-copy{min-height:0!important;align-content:start!important;',
    'body.home .lunara-latest-reviews-section .lunara-review-grid-card.has-no-visual .lunara-review-grid-poster-wrap{display:none!important;',
    'body.home .lunara-dispatches-section .lunara-journal-home-card.has-no-visual{min-height:clamp(220px,18vw,320px)!important;height:auto!important;align-self:start!important;',
    'body.home .lunara-dispatches-section .lunara-journal-home-card.is-lead.has-no-visual{min-height:clamp(260px,24vw,360px)!important;',
    'body.home .lunara-dispatches-section .lunara-journal-home-card.has-no-visual .lunara-journal-home-card-link{min-height:0!important;height:auto!important;grid-template-rows:auto!important;',
    'body.home .lunara-dispatches-section .lunara-journal-home-card.has-no-visual .lunara-journal-home-card-media{display:none!important;',
    'body.home .lunara-oscar-facts-section .lunara-home-section-head.is-with-summary{min-height:0!important;padding:clamp(16px,2.2vw,26px)!important;align-items:center!important;',
    '@media(max-width:820px)',
    '@media(prefers-reduced-motion:reduce)'
)) {
    Assert-True ($block.Contains($needle)) "Homepage text-led card cleanup is missing expected CSS: $needle"
}

Assert-True ($block -notmatch 'font-family\s*:') 'Homepage text-led card cleanup must not introduce another font family.'
Assert-True ($block -notmatch 'set_theme_mod|get_option\(') 'Homepage text-led card cleanup must not mutate or add settings.'
Assert-True ($block -notmatch 'post-type-archive-review|post-type-archive-journal|single-review|single-journal') 'Homepage cleanup must not target archive or single routes.'

Write-Host 'Homepage text-led card chamber cleanup contract passed.'
