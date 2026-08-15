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

$controlCss = Read-ThemeFile 'assets/css/lunara-control-desk.css'
$blocks     = Read-ThemeFile 'inc/blocks.php'
$style      = Read-ThemeFile 'style.css'

# The two-column archive shell must not force its nested numeric controls beyond
# the Site Studio workspace at WordPress desktop or mobile widths.
Assert-True ($controlCss -match '(?s)\.lunara-site-studio\s+\.lunara-control-desk-homepage-number-grid\s*\{[^}]*grid-template-columns:\s*repeat\(auto-fit,\s*minmax\(min\(100%,\s*26rem\),\s*1fr\)\)[^}]*min-width:\s*0') 'Site Studio archive number grids must collapse by available container width instead of overflowing at 1268px.'
Assert-True ($controlCss -match '(?s)\.lunara-site-studio\s+\.lunara-control-desk-homepage-number[^\{]*,[^\{]*\.lunara-site-studio\s+\.lunara-control-desk-brand-number-value[^\{]*,[^\{]*\.lunara-site-studio\s+\.lunara-control-desk-brand-reset\s*\{[^}]*min-width:\s*0') 'Every nested numeric-control item must be allowed to shrink inside Site Studio.'
Assert-True ($controlCss -match '(?s)\.lunara-site-studio\s+\.lunara-control-desk-brand-number-value\s*\{[^}]*flex-wrap:\s*wrap') 'The numeric value and unit must wrap rather than increase document width.'
Assert-True ($controlCss -match '(?s)\.lunara-site-studio\s+\.lunara-control-desk-brand-number-value\s+input\s*\{[^}]*box-sizing:\s*border-box[^}]*max-width:\s*100%') 'Numeric inputs must stay bounded by their available Site Studio track.'
Assert-True ($controlCss -match '(?s)\.lunara-site-studio\s+\.lunara-control-desk-brand-reset\s+label\s*\{[^}]*white-space:\s*normal') 'Reset labels must wrap on narrow Site Studio archive cards.'
Assert-True ($controlCss -match '(?s)@media\s*\(max-width:\s*782px\)[^{]*\{[\s\S]*?\.lunara-site-studio\s+\.lunara-control-desk-homepage-number\s*\{[^}]*grid-template-columns:\s*1fr') 'Site Studio numeric controls must become a single column at WordPress mobile widths.'

# WordPress must discover iframe styles through the block-asset path. The
# is_admin guard keeps this editor stylesheet entirely off anonymous requests.
Assert-True ($blocks -match 'function\s+lunara_enqueue_homepage_editor_card_style\s*\(') 'Homepage cards need a dedicated editor-style enqueue function.'
Assert-True ($blocks -match "add_action\(\s*'enqueue_block_assets'\s*,\s*'lunara_enqueue_homepage_editor_card_style'\s*,\s*20\s*\)") 'Homepage card CSS must use enqueue_block_assets so WordPress loads it into the editor iframe correctly.'
$styleFunctionStart = $blocks.IndexOf('function lunara_enqueue_homepage_editor_card_style')
$styleFunctionEnd   = $blocks.IndexOf("add_action( 'enqueue_block_assets'", $styleFunctionStart)
Assert-True ($styleFunctionStart -ge 0 -and $styleFunctionEnd -gt $styleFunctionStart) 'Homepage editor style function boundaries are missing.'
$styleFunction = $blocks.Substring($styleFunctionStart, $styleFunctionEnd - $styleFunctionStart)
Assert-True ($styleFunction -match "!\s*is_admin\(\)") 'The block-asset enqueue must return outside WordPress admin.'
Assert-True ($styleFunction -match "wp_enqueue_style\(\s*'lunara-homepage-editor'") 'The iframe-safe hook must enqueue the existing compact-card stylesheet.'
$configFunctionStart = $blocks.IndexOf('function lunara_enqueue_homepage_editor_card_assets')
$configFunctionEnd   = $blocks.IndexOf("add_action( 'enqueue_block_editor_assets'", $configFunctionStart)
Assert-True ($configFunctionStart -ge 0 -and $configFunctionEnd -gt $configFunctionStart) 'Homepage editor configuration function boundaries are missing.'
$configFunction = $blocks.Substring($configFunctionStart, $configFunctionEnd - $configFunctionStart)
Assert-True ($configFunction -notmatch 'wp_enqueue_style') 'The block-editor configuration hook must not inject the stylesheet into the iframe incorrectly.'
Assert-True ($blocks -notmatch "add_action\(\s*'wp_enqueue_scripts'[^\r\n]*lunara_enqueue_homepage_editor_card_style") 'Homepage editor-card CSS must never be attached to the public enqueue hook.'

Assert-True ($style -match '(?m)^Version:\s*3\.2\.40\s*$') 'Theme version must preserve the Site Studio overflow and iframe follow-up in 3.2.40.'

Write-Host 'site-studio-responsive-assets: all assertions passed.'
