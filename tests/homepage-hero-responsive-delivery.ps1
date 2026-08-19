$ErrorActionPreference = 'Stop'

function Assert-True {
    param(
        [bool] $Condition,
        [string] $Message
    )

    if (-not $Condition) {
        throw $Message
    }
}

$root      = Split-Path -Parent $PSScriptRoot
$module    = Join-Path $root 'inc/hero-delivery.php'
$functions = Get-Content -Raw (Join-Path $root 'functions.php')
$loader    = Get-Content -Raw (Join-Path $root 'functions-loader.php')
$style     = Get-Content -Raw (Join-Path $root 'style.css')

Assert-True (Test-Path -LiteralPath $module) 'The attachment-backed hero delivery module must exist.'
$delivery = Get-Content -Raw $module

Assert-True ($loader -match "require_once\s+\`$lunara_inc\s*\.\s*'hero-delivery\.php'") 'The split loader must load the hero delivery module.'
Assert-True ($delivery -match 'function\s+lunara_build_cinematic_hero_image_descriptor\s*\(') 'Hero delivery must expose one canonical final-image descriptor.'
Assert-True ($delivery -match 'wp_get_attachment_image\s*\(\s*\$attachment_id\s*,\s*''full''') 'Attachment-backed heroes must preserve uncropped source composition before CSS focal positioning.'
Assert-True ($delivery -notmatch 'wp_get_attachment_image\s*\(\s*\$attachment_id\s*,\s*''lunara-hero-spotlight''') 'Hero delivery must not hard-crop attachment pixels before Full Frame or focal-position controls run.'
Assert-True ($delivery -match "class_exists\s*\(\s*'WP_HTML_Tag_Processor'\s*\)") 'Final filtered image markup should use WordPress structured parsing when available.'
Assert-True ($delivery -match 'function\s+lunara_render_cinematic_hero_image\s*\(') 'Static and carousel renderers need one shared image-markup helper.'

$staticStart = $functions.IndexOf('function lunara_get_cinematic_hero_data')
$staticEnd   = $functions.IndexOf("if ( ! function_exists( 'lunara_render_cinematic_hero'", $staticStart)
Assert-True ($staticStart -ge 0 -and $staticEnd -gt $staticStart) 'Static hero data resolver boundaries are missing.'
$staticResolver = $functions.Substring($staticStart, $staticEnd - $staticStart)
Assert-True ($staticResolver -match 'lunara_hero_attachment_id_from_url\s*\(\s*\$override_image\s*\)') 'Customizer hero art must use the host-gated cached attachment resolver.'
Assert-True ($staticResolver -match 'lunara_hero_attachment_id_from_url\s*\(\s*\$candidate\s*\)') 'Static automatic candidates must use the host-gated cached attachment resolver.'
Assert-True ($staticResolver -notmatch 'attachment_url_to_postid\s*\(') 'Static hero data must not bypass host gating with direct attachment-table lookups.'

Assert-True ($functions -match '''attachment_id''\s*=>\s*lunara_hero_attachment_id_from_url\s*\(\s*\$image\s*\)') 'Automatic hero feed items must preserve attachment identity before URL right-sizing.'
$qualifierStart = $functions.IndexOf('function lunara_hero_image_qualifies')
$qualifierEnd   = $functions.IndexOf("if ( ! function_exists( 'lunara_hero_qualify_or_blank'", $qualifierStart)
Assert-True ($qualifierStart -ge 0 -and $qualifierEnd -gt $qualifierStart) 'Hero image qualifier boundaries are missing.'
$qualifier = $functions.Substring($qualifierStart, $qualifierEnd - $qualifierStart)
Assert-True ($qualifier -match 'lunara_hero_attachment_id_from_url\s*\(\s*\$url\s*\)') 'Image qualification and slide construction must share the cached attachment resolver.'
$builderStart = $functions.IndexOf('function lunara_build_hero_slide_for_post')
$builderEnd   = $functions.IndexOf("if ( ! function_exists( 'lunara_build_spotlight_hero_slide'", $builderStart)
Assert-True ($builderStart -ge 0 -and $builderEnd -gt $builderStart) 'Hero slide builder boundaries are missing.'
$builder = $functions.Substring($builderStart, $builderEnd - $builderStart)
Assert-True ($builder -match '''attachment_id''\s*=>\s*\$attachment_id') 'Curated/Hero Command slides must preserve attachment identity.'

$slideStart = $functions.IndexOf('function lunara_render_cinematic_hero_slide')
$slideEnd   = $functions.IndexOf("if ( ! function_exists( 'lunara_get_home_cinematic_hero_slides'", $slideStart)
Assert-True ($slideStart -ge 0 -and $slideEnd -gt $slideStart) 'Hero slide renderer boundaries are missing.'
$slideRenderer = $functions.Substring($slideStart, $slideEnd - $slideStart)
Assert-True ($slideRenderer -match 'lunara_render_cinematic_hero_image\s*\(\s*\$data\s*,\s*\$is_priority_image\s*\)') 'Carousel slides must render the canonical descriptor markup.'
Assert-True ($slideRenderer -notmatch '<img\s+src=".*\$data\[''image''\]') 'Carousel renderer must not hand-build the old URL-only image.'

Assert-True ($delivery -match 'function\s+lunara_resolve_home_cinematic_hero_lcp_data\s*\(') 'Preload resolution must mirror the exact renderer branch.'
Assert-True ($delivery -match 'count\s*\(\s*\$slides\s*\)\s*<\s*2[\s\S]*?lunara_get_cinematic_hero_data') 'One automatic slide must resolve the static fallback used by the renderer.'
Assert-True ($delivery -match 'function\s+lunara_get_home_cinematic_hero_preload_descriptor\s*\(') 'Homepage head hints must consume the canonical descriptor.'
Assert-True ($delivery -match 'function\s+lunara_get_home_cinematic_hero_http_link_value\s*\(') 'URL-only HTTP Link parity must be independently testable.'
$httpStart = $delivery.IndexOf('function lunara_get_home_cinematic_hero_http_link_value')
$httpEnd   = $delivery.IndexOf("if ( ! function_exists( 'lunara_send_home_cinematic_hero_preload_header'", $httpStart)
Assert-True ($httpStart -ge 0 -and $httpEnd -gt $httpStart) 'HTTP Link resolver boundaries are missing.'
$httpResolver = $delivery.Substring($httpStart, $httpEnd - $httpStart)
$guardAt      = $httpResolver.IndexOf('lunara_home_cinematic_hero_preload_is_allowed')
$deckAt       = $httpResolver.IndexOf('lunara_resolve_home_cinematic_hero_lcp_data')
Assert-True ($guardAt -ge 0 -and $deckAt -gt $guardAt) 'HTTP routing must stop non-home/plugin-owned requests before resolving the hero deck.'
Assert-True ($delivery -match 'imagesrcset="%s"') 'Responsive preload must emit imagesrcset.'
Assert-True ($delivery -match 'imagesizes="%s"') 'Responsive preload must emit imagesizes.'
Assert-True ($delivery -match 'if\s*\(\s*''''\s*!==\s*\$descriptor\[''srcset''\]\s*\)\s*\{\s*return\s+'''';') 'Responsive images must skip the unreliable fixed HTTP Link header.'
Assert-True ($delivery -notmatch 'rel="preload"[^>]+href="%s"[^>]+imagesrcset=') 'Responsive preload must not pin a mismatched fixed href candidate.'
Assert-True ($delivery -match "add_action\(\s*'wp_head'\s*,\s*'lunara_preload_home_cinematic_hero_image'\s*,\s*1\s*\)") 'Responsive HTML preload must remain early in wp_head.'

$runtime = Join-Path $PSScriptRoot 'homepage-hero-responsive-runtime.php'
$phpOutput = & php $runtime 2>&1
if ($LASTEXITCODE -ne 0) {
    throw "Homepage hero responsive runtime failed:`n$($phpOutput -join [Environment]::NewLine)"
}
Assert-True (($phpOutput -join "`n") -match 'all assertions passed') 'Homepage hero responsive runtime did not report success.'

Assert-True ($style -match '(?m)^Version:\s*3\.2\.53\s*$') 'Theme version must preserve responsive hero delivery in 3.2.53.'

Write-Host 'homepage-hero-responsive-delivery: all assertions passed.'
