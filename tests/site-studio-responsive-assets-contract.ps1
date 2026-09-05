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

$controlCss = Read-ThemeFile 'assets/css/lunara-site-studio.css'
$blocks     = Read-ThemeFile 'inc/blocks.php'
$style      = Read-ThemeFile 'style.css'

# The dedicated workspace owns its own responsive, shrink-safe geometry.
Assert-True ($controlCss -match '(?s)\.lunara-site-studio-workspace\s*\{[^}]*display:\s*grid[^}]*grid-template-columns:[^}]*minmax\(') 'Site Studio must use a shrink-safe grid workspace.'
Assert-True ($controlCss -match '(?s)\.lunara-site-studio\s*>\s*\*[^}]*min-width:\s*0') 'Every top-level workspace child must be allowed to shrink.'
Assert-True ($controlCss -match '(?s)\.lunara-site-studio-preview\s+iframe\s*\{[^}]*max-width:\s*none[^}]*width:\s*1440px') 'The preview iframe must retain an exact fixed desktop width for scaling.'
Assert-True ($controlCss -match '(?s)@media\s*\(max-width:\s*1280px\)[\s\S]*?\.lunara-site-studio-inspector\s*\{[^}]*grid-column:\s*1\s*/\s*-1') 'The inspector must span the safer intermediate two-column layout under wp-admin chrome.'
Assert-True ($controlCss -match '(?s)@media\s*\(max-width:\s*782px\)[\s\S]*?\.lunara-site-studio-workspace\s*\{[^}]*grid-template-columns:\s*minmax\(0,\s*1fr\)') 'Site Studio must become one column at the WordPress mobile breakpoint.'
Assert-True ($controlCss -notmatch '(?s)(html|body|\.lunara-site-studio[^,{]*)[^}]*overflow-x:\s*(hidden|clip)') 'Responsive geometry must not mask horizontal overflow.'

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

Assert-True ($style -match '(?m)^Version:\s*3\.2\.58\s*$') 'Theme version must preserve the Site Studio overflow and iframe follow-up in 3.2.58.'

Write-Host 'site-studio-responsive-assets: all assertions passed.'
