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

$module = Read-ThemeFile 'inc/blocks-hub.php'
$editor = Read-ThemeFile 'assets/js/lunara-blocks-hub.js'
$loader = Read-ThemeFile 'functions-loader.php'

# Loader and editor asset wiring.
Assert-True ($loader -match "require_once \`$lunara_inc \. 'blocks-hub\.php';") 'functions-loader.php must load the Lunara blocks hub module.'
Assert-True ($module -match "add_action\(\s*'enqueue_block_editor_assets'\s*,\s*'lunara_blocks_hub_enqueue_editor'\s*\)") 'Blocks hub must hook editor asset enqueue.'
Assert-True ($module -match "wp_enqueue_script\(\s*'lunara-blocks-hub'") 'Blocks hub must enqueue its editor script.'
foreach ($dependency in @('wp-plugins', 'wp-edit-post', 'wp-editor', 'wp-components', 'wp-element', 'wp-blocks', 'wp-data', 'wp-i18n')) {
    Assert-True ($module -match [regex]::Escape("'$dependency'")) "Blocks hub editor script must depend on $dependency."
}

# Defensive pattern category and all starter patterns.
Assert-True ($module -match "register_block_pattern_category\(\s*'lunara'") 'Blocks hub must register the Lunara pattern category defensively.'
foreach ($pattern in @(
    'lunara/starter-reviews-grid',
    'lunara/starter-journal-grid',
    'lunara/starter-media-gallery',
    'lunara/starter-pair-it-with',
    'lunara/section-cinematic-hero',
    'lunara/section-journal-lane',
    'lunara/section-oscar-picks',
    'lunara/section-oscar-facts',
    'lunara/section-pairing-desk'
)) {
    Assert-True ($module -match ("'" + [regex]::Escape($pattern) + "'\s*=>")) "Missing starter pattern: $pattern"
}
Assert-True ($module -match "register_block_pattern\(\s*\`$name\s*,\s*\`$pattern\s*\)") 'Blocks hub must register each starter pattern through the WordPress pattern API.'
Assert-True ($module -match "\`$pattern\['categories'\]\s*=\s*array\(\s*'lunara'\s*\)") 'Starter patterns must use the Lunara category.'
Assert-True ($module.Contains('wp:lunara/pairing')) 'Pair It With starter must use the current lunara/pairing block.'

# Editor panel contract.
foreach ($needle in @('registerPlugin', 'PluginSidebar', 'PluginSidebarMoreMenuItem', 'createBlock', 'insertBlocks', 'Lunara Studio')) {
    Assert-True ($editor -match [regex]::Escape($needle)) "Editor hub must reference $needle."
}
foreach ($block in @(
    'lunara/reviews-grid',
    'lunara/journal-grid',
    'lunara/media-showcase',
    'lunara/pairing',
    'lunara/cinematic-hero',
    'lunara/journal-lane',
    'lunara/oscar-picks',
    'lunara/oscar-facts',
    'lunara/pairing-desk'
)) {
    Assert-True ($editor -match [regex]::Escape($block)) "Editor hub must expose $block."
}
Assert-True ($editor -match 'editPost\.(?:PluginSidebar|PluginSidebarMoreMenuItem)\s*\|\|\s*editor\.') 'Editor hub must support the @wordpress/editor component fallback.'

Write-Host 'blocks-hub: all assertions passed.'
