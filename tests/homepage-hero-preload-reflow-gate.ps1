$ErrorActionPreference = 'Stop'

function Assert-True {
    param(
        [bool] $Condition,
        [string] $Message
    )

    if ( -not $Condition ) {
        throw $Message
    }
}

$root      = Split-Path -Parent $PSScriptRoot
$functions = Get-Content -Raw (Join-Path $root 'functions.php')
$carousel  = Get-Content -Raw (Join-Path $root 'assets/js/lunara-scroll-carousel.js')
$style     = Get-Content -Raw (Join-Path $root 'style.css')

Assert-True ($functions -match "function\s+lunara_get_home_cinematic_hero_slides\s*\(") 'Homepage hero deck must have a request-scoped shared resolver.'
Assert-True ($functions -match '\$slides\s*=\s*lunara_get_home_cinematic_hero_slides\s*\(\s*\)') 'Hero rendering must consume the shared deck resolver.'
Assert-True ($functions -match "add_action\(\s*'wp_head'\s*,\s*'lunara_preload_home_cinematic_hero_image'\s*,\s*1\s*\)") 'The canonical hero preload must be emitted at the start of wp_head.'
Assert-True ($functions -match 'id="lunara-home-hero-preload"\s+rel="preload"\s+as="image"') 'The homepage head must contain a uniquely identifiable image preload.'
Assert-True ($functions -match 'fetchpriority="high"') 'The homepage hero preload must keep high fetch priority.'
Assert-True ($functions -match 'isset\(\s*\$slides\[0\]\[''image''\]\s*\)') 'The preload URL must come from the first canonical hero slide.'
Assert-True ($functions -match 'shortcode_exists\(\s*\$shortcode_tag\s*\)') 'Plugin-owned hero shortcodes must remain excluded from native preload prediction.'

$syncCalls = ([regex]::Matches($carousel, 'syncDots\s*\(\s*\)\s*;')).Count
Assert-True ($syncCalls -eq 1) 'Carousel dot geometry must run only after an actual scroll, not during first paint.'
Assert-True ($carousel -match 'first dot is already marked active in server-rendered HTML') 'The first-paint carousel optimization must retain its server-state rationale.'
Assert-True ($style -match 'Version:\s*3\.2\.37') 'Homepage hero preload/reflow gate must report Theme 3.2.37.'

Write-Host 'Homepage hero preload and first-paint reflow gate passed.'
