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

$module   = Read-ThemeFile 'inc/pairing-showcase.php'
$editor   = Read-ThemeFile 'assets/js/lunara-pairing-showcase.js'
$loader   = Read-ThemeFile 'functions-loader.php'
$frontend = Read-ThemeFile 'inc/frontend.php'
$css      = Read-ThemeFile 'style.css'

# --- Loader wiring ----------------------------------------------------------
Assert-True ($loader -match "require_once \`$lunara_inc \. 'pairing-showcase\.php';") 'functions-loader.php must load the pairing showcase module.'

# --- Server registration ----------------------------------------------------
Assert-True ($module -match "register_block_type\(\s*'lunara/pairing'") 'Pairing showcase block must be registered.'
Assert-True ($module -match "'inserter' => true") 'Pairing showcase block must be visible in the inserter.'

# --- Full customizability contract: every dial is an attribute --------------
foreach ($attribute in @('source', 'reviewId', 'pairings', 'showHeader', 'heading', 'subtitle')) {
    Assert-True ($module -match "'$attribute'") "Pairing schema must expose the '$attribute' attribute."
}

# --- Curated cards reuse the signature resolution + card chrome --------------
Assert-True ($module -match 'lunara_parse_pair_it_with_value') 'Curated pairings must resolve through the signature value parser.'
Assert-True ($module -match 'lunara_render_oscar_ledger_pill') 'Curated cards must reuse the Oscar-ledger pill.'
Assert-True ($module -match 'lunara-pair-card lunara-pair-card--') 'Curated cards must reuse the native pair-card markup.'
Assert-True ($module -match 'lunara-pair-cards-grid') 'Showcase must reuse the native pair-cards grid.'

# --- Mirror mode renders a review's automatic pairings verbatim -------------
Assert-True ($module -match 'lunara_render_pair_it_with_cards') 'Mirror mode must reuse the automatic Pair It With renderer.'
Assert-True ($module -match "'review' !== \`$post->post_type") 'Mirror mode must require a real published review.'

# --- Per-instance data must be sanitized ------------------------------------
Assert-True ($module -match 'lunara_pairing_showcase_sanitize_pairings') 'Pairings must pass through the sanitizer.'
Assert-True ($module -match 'sanitize_textarea_field') 'Pairing notes must be sanitized.'

# --- The signature stylesheet must load wherever the block is dropped -------
Assert-True ($module -match 'lunara_pairing_showcase_enqueue_styles') 'Block must ensure its stylesheet loads on demand.'
Assert-True ($module -match "wp_enqueue_style\(\s*'lunara-review-components'") 'Block must enqueue the review-components stylesheet.'
Assert-True ($frontend -match "has_block\(\s*'lunara/pairing'") 'The review-components CSS gate must detect the pairing block.'

# --- Editor: per-card curation UI -------------------------------------------
Assert-True ($editor -match "registerBlockType\(\s*'lunara/pairing'") 'Editor must register the pairing block.'
Assert-True ($editor -match 'Add pairing card') 'Editor must let you add pairing cards.'
Assert-True ($editor -match 'Feature \(move to slot 1\)') 'Editor must support featuring a card.'
Assert-True ($editor -match 'Move up') 'Editor must support reordering cards.'
Assert-True ($editor -match 'MediaUpload') 'Editor must support a poster override from the media library.'
Assert-True ($editor -match 'ServerSideRender') 'Editor must live-preview the server render.'

# --- CSS --------------------------------------------------------------------
Assert-True ($css -match 'lunara-pairing-showcase-css') 'Pairing showcase CSS section must be present.'

Write-Host 'pairing-showcase-block: all assertions passed.'
