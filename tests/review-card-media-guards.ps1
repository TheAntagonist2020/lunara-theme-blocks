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

$homeSections = Read-ThemeFile 'inc/home-sections.php'
$functions = Read-ThemeFile 'functions.php'
$shortcodes = Read-ThemeFile 'inc/shortcodes-home.php'
$frontend = Read-ThemeFile 'inc/frontend.php'

Assert-True ($homeSections -match '\$has_card_media\s*=\s*\$has_thumb_html\s*\|\|\s*\$use_fallback_bg') 'Active homepage renderer must compute a real media state.'
Assert-True ($homeSections -match "echo\s+\`$has_card_media\s*\?\s*'has-visual'\s*:\s*'has-no-visual'") 'Active homepage cards must expose visual/no-visual classes.'
Assert-True ($homeSections -match 'if\s*\(\s*\$has_card_media\s*\)\s*:\s*[\s\S]*lunara-review-grid-poster-wrap') 'Active homepage renderer must only print poster wrappers for media cards.'
Assert-True ($homeSections -match '! \$has_card_media && \$score[\s\S]*is-inline-score') 'Active homepage no-media scores must render inline.'

foreach ($entry in @(
    @{ Name = 'monolithic fallback'; Content = $functions },
    @{ Name = 'legacy shortcode'; Content = $shortcodes }
)) {
    $name = $entry.Name
    $content = $entry.Content

    Assert-True ($content -match '\$has_card_media\s*=\s*\$has_thumb_html\s*\|\|\s*\$use_fallback_bg') "$name renderer must compute a real media state."
    Assert-True ($content -match "echo\s+\`$has_card_media\s*\?\s*'has-visual'\s*:\s*'has-no-visual'") "$name renderer must expose visual/no-visual classes."
    Assert-True ($content -match 'if\s*\(\s*\$has_card_media\s*\)\s*:\s*[\s\S]*lunara-review-grid-poster-wrap') "$name renderer must gate poster wrappers behind media."
    Assert-True ($content -match '! \$has_card_media && \$score[\s\S]*is-inline-score') "$name renderer must render no-media scores inline."
}

Assert-True ($frontend -match 'lunara_home_card_media_hygiene_css') 'Homepage card hygiene CSS must stay active.'
Assert-True ($frontend -match '\.lunara-review-grid-card\.has-no-visual\s+\.lunara-review-grid-link') 'Homepage CSS must collapse no-visual review cards.'
Assert-True ($frontend -match 'function\s+hydrateLazySource') 'Active image fade-in script must hydrate lazy image sources.'
Assert-True ($frontend -match 'data-lazy-src') 'Active image fade-in script must handle Jetpack-style lazy sources.'
Assert-True ($frontend -match 'window\.setTimeout[\s\S]*lunara-img-loaded') 'Active image fade-in script must fail open instead of leaving blank image chambers.'

foreach ($selector in @(
    '.lunara-review-grid-poster',
    '.lunara-review-feature-image',
    '.lunara-poster-card-image',
    '.lunara-journal-home-card-image',
    '.lunara-dispatch-archive-thumb',
    '.lunara-dispatch-lead-image',
    '.lunara-oscar-pick-card-image',
    '.lunara-oscar-fact-card-poster-image',
    '.lunara-home-pulse-poster',
    '.aat-filmography-poster',
    '.aat-entity-poster'
)) {
    Assert-True ($frontend.Contains($selector)) "Active image fade-in script must include $selector."
}

Write-Host 'Review card media guard contract passed.'
