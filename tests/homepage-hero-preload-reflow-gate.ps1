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
$delivery  = Get-Content -Raw (Join-Path $root 'inc/hero-delivery.php')
$carousel  = Get-Content -Raw (Join-Path $root 'assets/js/lunara-scroll-carousel.js')
$style     = Get-Content -Raw (Join-Path $root 'style.css')

Assert-True ($functions -match "function\s+lunara_get_home_cinematic_hero_slides\s*\(") 'Homepage hero deck must have a request-scoped shared resolver.'
Assert-True ($functions -match '\$slides\s*=\s*lunara_get_home_cinematic_hero_slides\s*\(\s*\)') 'Hero rendering must consume the shared deck resolver.'
Assert-True ($delivery -match "add_action\(\s*'wp_head'\s*,\s*'lunara_preload_home_cinematic_hero_image'\s*,\s*1\s*\)") 'The canonical hero preload must be emitted at the start of wp_head.'
Assert-True ($delivery -match 'id="lunara-home-hero-preload"\s+rel="preload"\s+as="image"') 'The homepage head must contain a uniquely identifiable image preload.'
Assert-True ($delivery -match 'imagesrcset="%s"\s+imagesizes="%s"\s+fetchpriority="high"') 'The homepage head must let the browser choose the same responsive LCP candidate as the IMG.'
Assert-True ($delivery -match 'lunara_resolve_home_cinematic_hero_lcp_data') 'The preload descriptor must mirror the renderer branch, including its static fallback.'
Assert-True ($delivery -match 'shortcode_exists\(\s*\$shortcode_tag\s*\)') 'Plugin-owned hero shortcodes must remain excluded from native preload prediction.'

$syncCalls = ([regex]::Matches($carousel, 'syncDots\s*\(\s*\)\s*;')).Count
Assert-True ($syncCalls -eq 1) 'Carousel dot geometry must run only after an actual scroll, not during first paint.'
Assert-True ($carousel -match 'first dot is already marked active in server-rendered HTML') 'The first-paint carousel optimization must retain its server-state rationale.'
Assert-True ($style -match 'Version:\s*3\.2\.46') 'Homepage hero preload/reflow gate must report Theme 3.2.46.'

Write-Host 'Homepage hero preload and first-paint reflow gate passed.'
