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

Assert-True ($style -match 'Version:\s*3\.2\.30') 'Mobile progressive rendering release must report Theme 3.2.30.'
Assert-True ($frontPage -match "lunara_front_door_has_canonical_hero") 'Front Page must identify when the front door already owns the canonical hero.'
Assert-True ($frontPage -match "lunara_render_home_block_composition\(\s*\`$lunara_front_door_has_canonical_hero\s*\?\s*array\(\s*'lunara/cinematic-hero'") 'Front Page must suppress the duplicate hero block only when the front door already owns the hero.'
Assert-True ($homeBlocks -match 'function\s+lunara_render_home_block_composition\(\s*\$excluded_block_names') 'Block composition must accept a render-only exclusion list.'
Assert-True ($homeBlocks -match 'parse_blocks\(\s*\$content\s*\)') 'Block composition must filter parsed blocks without mutating editable Home content.'
Assert-True ($cinematicJs -match "matchMedia\('\(max-width: 820px\)'\)") 'Cinematic cards must recognize the mobile/tablet viewport.'
Assert-True ($cinematicJs -match 'reduceMotion\s*\|\|\s*compactViewport') 'Compact viewports must bypass delayed card hiding.'
Assert-True ($cinematicJs -match "rootMargin:\s*'240px 0px'") 'Desktop cinematic cards must reveal before they enter the viewport.'
Assert-True ($publicRuntime -match 'compactHome') 'The shared runtime must expose homepage content immediately on compact viewports.'
Assert-True ($publicRuntime -match "rootMargin:'240px 0px'") 'Shared homepage reveals must activate ahead of the viewport.'
Assert-True ($cinematicCss -match '(?s)@media\s*\(max-width:\s*820px\).*?is-cinematic-pending.*?opacity:\s*1;.*?transform:\s*none;') 'Mobile cards must remain visible even if a pending class survives.'
Assert-True ($reviewCss -match '(?s)@media\(max-width:820px\).*?lunara-pairing-desk-backdrop\{animation:none!important;transform:none!important\}') 'The oversized Pairing backdrop must not animate on mobile.'
Assert-True ($reviewCss -match '(?s)@media\(max-width:820px\).*?lunara-reveal:not\(\.is-visible\) \.lunara-pair-card\{opacity:1;transform:none\}') 'Pairing cards must be useful without waiting for a scroll observer on mobile.'

Write-Host 'Homepage mobile progressive-render contract passed.'

