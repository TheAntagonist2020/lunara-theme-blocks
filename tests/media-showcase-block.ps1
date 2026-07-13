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

$module  = Read-ThemeFile 'inc/curated-media.php'
$editor  = Read-ThemeFile 'assets/js/lunara-curated-media.js'
$runtime = Read-ThemeFile 'assets/js/lunara-media-showcase.js'
$loader  = Read-ThemeFile 'functions-loader.php'
$css     = Read-ThemeFile 'style.css'

# --- Loader wiring ----------------------------------------------------------
Assert-True ($loader -match "require_once \`$lunara_inc \. 'curated-media\.php';") 'functions-loader.php must load the curated media module.'

# --- Server registration ----------------------------------------------------
Assert-True ($module -match "register_block_type\(\s*'lunara/media-showcase'") 'Media showcase block must be registered.'
Assert-True ($module -match "'inserter' => true") 'Media showcase block must be visible in the inserter.'

# --- Full customizability contract: every dial is an attribute --------------
foreach ($attribute in @('ids', 'display', 'columns', 'featureFirst', 'aspectRatio', 'gap', 'rounded', 'linkTo', 'showCaptions', 'autoplay', 'autoplaySpeed', 'showArrows', 'showDots', 'showHeader', 'heading', 'kicker', 'ctaLabel', 'ctaUrl')) {
    Assert-True ($module -match "'$attribute'") "Media showcase schema must expose the '$attribute' attribute."
}

# --- Three displays over one picked set -------------------------------------
Assert-True ($module -match 'lunara_media_showcase_render_carousel') 'Media showcase must have a carousel display.'
Assert-True ($module -match 'lunara_media_showcase_render_rail_or_grid') 'Media showcase must have gallery + slider displays.'
Assert-True ($module -match "in_array\(\s*\`$display,\s*array\(\s*'gallery',\s*'slider',\s*'carousel'") 'Display dial must accept gallery, slider, and carousel.'

# --- Picks must be sanitized to real image attachments, in slot order -------
Assert-True ($module -match 'lunara_media_showcase_sanitize_ids') 'Picked images must pass through the sanitizer.'
Assert-True ($module -match "'attachment' !== \`$post->post_type") 'Sanitizer must reject non-attachment IDs.'
Assert-True ($module -match "strpos\(.*get_post_mime_type") 'Sanitizer must reject non-image attachments.'

# --- Carousel display must reuse the theme carousel contract + runtime -------
Assert-True ($module -match 'lunara-carousel lunara-media-carousel') 'Carousel display must emit the .lunara-carousel contract.'
Assert-True ($module -match "wp_enqueue_script\(\s*'lunara-carousel'") 'Carousel display must enqueue the shared carousel runtime.'
Assert-True ($module -match 'data-autoplay=') 'Carousel display must honor the autoplay dial.'

# --- Editor: media-library curation UI --------------------------------------
Assert-True ($editor -match "registerBlockType\(\s*'lunara/media-showcase'") 'Editor must register the media showcase block.'
Assert-True ($editor -match 'MediaUpload') 'Editor must pick images from the media library.'
Assert-True ($editor -match 'Feature \(move to slot 1\)') 'Editor must support featuring an image.'
Assert-True ($editor -match 'Move up') 'Editor must support reordering slots.'
Assert-True ($editor -match 'ServerSideRender') 'Editor must live-preview the server render.'

# --- Slider arrow runtime ----------------------------------------------------
Assert-True ($runtime -match 'lunara-media-slider-track') 'Slider runtime must drive the scroll-snap rail.'
Assert-True ($runtime -match 'scrollBy') 'Slider arrows must scroll the rail.'

# --- CSS ---------------------------------------------------------------------
Assert-True ($css -match 'lunara-media-showcase-css') 'Media showcase CSS section must be present.'
Assert-True ($css -match '--lunara-media-cols') 'Gallery must honor the per-instance column dial.'
Assert-True ($css -match '--lunara-media-ratio') 'Displays must honor the per-instance aspect-ratio dial.'

Write-Host 'media-showcase-block: all assertions passed.'
