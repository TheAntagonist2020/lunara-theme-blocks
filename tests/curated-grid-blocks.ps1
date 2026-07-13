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

$module = Read-ThemeFile 'inc/curated-grids.php'
$editor = Read-ThemeFile 'assets/js/lunara-curated-grids.js'
$loader = Read-ThemeFile 'functions-loader.php'
$css    = Read-ThemeFile 'style.css'

# --- Loader wiring ----------------------------------------------------------
Assert-True ($loader -match "require_once \`$lunara_inc \. 'curated-grids\.php';") 'functions-loader.php must load the curated grids module.'

# --- Server registration ----------------------------------------------------
Assert-True ($module -match "register_block_type\(\s*'lunara/reviews-grid'") 'Curated reviews grid block must be registered.'
Assert-True ($module -match "register_block_type\(\s*'lunara/journal-grid'") 'Curated journal grid block must be registered.'
Assert-True ($module -match "'inserter' => true") 'Curated grid blocks must be visible in the inserter.'

# --- Full customizability contract: every curation dial is an attribute -----
foreach ($attribute in @('mode', 'postIds', 'autoFill', 'count', 'layout', 'columns', 'showHeader', 'heading', 'kicker', 'ctaLabel', 'ctaUrl', 'showExcerpt', 'excerptWords', 'cardKicker')) {
    Assert-True ($module -match "'$attribute'") "Curated grid schema must expose the '$attribute' attribute."
}
foreach ($attribute in @('showScore', 'categoryFilter', 'showType', 'showDate', 'includePosts', 'typeFilter')) {
    Assert-True ($module -match "'$attribute'") "Per-block schema must expose the '$attribute' attribute."
}

# --- Hand-picked slots must render in picker order and stay published-only --
Assert-True ($module -match 'lunara_curated_grid_sanitize_ids') 'Curated picks must pass through the sanitizer.'
Assert-True ($module -match "'publish' !== \`$post->post_status") 'Curated picks must reject non-published posts.'
Assert-True ($module -match "'post__not_in'\s*=>\s*\`$picked") 'Auto-fill must never duplicate a hand-picked slot.'

# --- Cards must reuse the native chrome and media guards ---------------------
Assert-True ($module -match '\$has_card_media\s*=\s*\$has_thumb_html\s*\|\|\s*\$use_fallback_bg') 'Curated reviews cards must compute a real media state.'
Assert-True ($module -match "echo\s+\`$has_card_media\s*\?\s*'has-visual'\s*:\s*'has-no-visual'") 'Curated reviews cards must expose visual/no-visual classes.'
Assert-True ($module -match 'lunara-journal-home-card') 'Curated journal cards must reuse the journal card chrome.'
Assert-True ($module -match 'lunara-review-grid-card') 'Curated review cards must reuse the review card chrome.'

# --- Editor: curation UI ------------------------------------------------------
Assert-True ($editor -match "registerBlockType\(\s*config\.name") 'Editor must register the curated grid blocks.'
Assert-True ($editor -match "'lunara/reviews-grid'") 'Editor must configure the reviews grid.'
Assert-True ($editor -match "'lunara/journal-grid'") 'Editor must configure the journal grid.'
Assert-True ($editor -match '/wp/v2/search') 'Editor picker must search posts over REST.'
Assert-True ($editor -match 'Move up') 'Editor picker must support reordering slots.'
Assert-True ($editor -match 'Feature \(move to slot 1\)') 'Editor picker must support featuring a pick.'
Assert-True ($editor -match 'ServerSideRender') 'Editor must live-preview the server render.'

# --- CSS ----------------------------------------------------------------------
Assert-True ($css -match 'lunara-curated-grids-css') 'Curated grids CSS section must be present.'
Assert-True ($css -match '--lunara-cgrid-cols') 'Curated grids must honor the per-instance column dial.'

Write-Host 'curated-grid-blocks: all assertions passed.'
