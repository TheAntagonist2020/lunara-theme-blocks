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

$style = Read-ThemeFile 'style.css'
$frontPage = Read-ThemeFile 'front-page.php'
$homeBlocks = Read-ThemeFile 'inc/home-blocks.php'
$cinematicJs = Read-ThemeFile 'assets/js/lunara-cinematic-home.js'
$publicRuntime = Read-ThemeFile 'assets/js/lunara-public-runtime.js'
$cinematicCss = Read-ThemeFile 'assets/css/lunara-cinematic-home.css'
$reviewCss = Read-ThemeFile 'assets/css/lunara-review-components.css'

Assert-True ($style -match 'Version:\s*3\.2\.51') 'Homepage progressive rendering must remain intact in Theme 3.2.51.'
Assert-True ($frontPage -match "lunara_front_door_has_canonical_hero") 'Front Page must identify when the front door already owns the canonical hero.'
Assert-True ($frontPage -match "lunara_render_home_block_composition\(\s*\`$lunara_front_door_has_canonical_hero\s*\?\s*array\(\s*'lunara/cinematic-hero'") 'Front Page must suppress the duplicate hero block only when the front door already owns the hero.'
Assert-True ($homeBlocks -match 'function\s+lunara_render_home_block_composition\(\s*\$excluded_block_names') 'Block composition must accept a render-only exclusion list.'
Assert-True ($homeBlocks -match 'parse_blocks\(\s*\$content\s*\)') 'Block composition must filter parsed blocks without mutating editable Home content.'
Assert-True ($cinematicJs -notmatch 'IntersectionObserver') 'Homepage cards must not depend on a scroll observer for basic visibility.'
Assert-True ($cinematicJs -match "classList\.remove\('is-cinematic-pending'\)") 'Homepage cards must clear stale pending state immediately.'
Assert-True ($publicRuntime -match '(?s)if\(isFrontPage\).*?classList\.add\(''is-visible''\)') 'The shared runtime must expose homepage sections immediately on every viewport.'
Assert-True ($publicRuntime -match "rootMargin:'240px 0px'") 'Non-home route reveals must still activate ahead of the viewport.'
Assert-True ($cinematicCss -match '(?s)is-cinematic-pending.*?opacity:\s*1;.*?transform:\s*none;') 'Homepage cards must remain visible even if a pending class survives.'
Assert-True ($style -match '(?s)body\.home .*?lunara-reveal \.lunara-home-section-title.*?opacity:\s*1\s*!important;.*?transform:\s*none\s*!important;') 'Homepage headings must stay visible without IntersectionObserver state.'
Assert-True ($reviewCss -match '(?s)@media\(max-width:820px\).*?lunara-pairing-desk-backdrop\{display:none!important;animation:none!important;transform:none!important\}') 'The decorative Pairing backdrop must not render or animate on mobile.'
Assert-True ((Read-ThemeFile 'assets/css/lunara-shell.css') -match '(?s)@media \(max-width: 820px\).*?body\.home \.lunara-pairing-desk-backdrop\s*\{\s*display:\s*none !important;') 'The cacheable shell must suppress the Pairing backdrop before mobile layout settles.'
Assert-True ($reviewCss -match 'lunara-pairing-desk-section\.lunara-reveal \.lunara-pair-card\{opacity:1;transform:none\}') 'Pairing cards must be useful without waiting for a scroll observer.'

Write-Host 'Homepage mobile progressive-render contract passed.'

