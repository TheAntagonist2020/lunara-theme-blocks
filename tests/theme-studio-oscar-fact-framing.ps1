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
    Assert-True (Test-Path $path) "Missing expected file: $RelativePath"
    return Get-Content -Raw $path
}

$controlDesk = Read-ThemeFile 'inc/control-desk.php'
$functions = Read-ThemeFile 'functions.php'
$css = Read-ThemeFile 'assets/css/lunara-control-desk.css'

Assert-True ($functions -match 'function\s+lunara_oscar_fact_visual_focus_options') 'Oscar Fact focus options must remain defined in functions.php.'
Assert-True ($functions -match 'function\s+lunara_sanitize_oscar_fact_visual_focus') 'Oscar Fact focus values must remain sanitized in functions.php.'

Assert-True ($controlDesk -match '\$visual_focus\s*=') 'Theme Studio save path must read an Oscar Fact visual focus value.'
Assert-True ($controlDesk -match 'lunara_image_source_visual_focus') 'Theme Studio Oscar Fact form must include a visual focus field.'
Assert-True ($controlDesk -match 'lunara_sanitize_oscar_fact_visual_focus') 'Theme Studio save path must sanitize visual focus through the existing Oscar Fact sanitizer.'
Assert-True ($controlDesk -match '_lunara_fact_visual_focus') 'Theme Studio save path must persist the existing Oscar Fact focus meta key.'
Assert-True ($controlDesk -match 'lunara_oscar_fact_visual_focus_options') 'Theme Studio must render focus options from the existing Oscar Fact option source.'
Assert-True ($controlDesk -match 'delete_post_meta\(\s*\$post_id,\s*''_lunara_fact_visual_focus''\s*\)') 'Theme Studio must clear stale focus meta when an Oscar Fact image is removed or focus resets to center.'

Assert-True ($controlDesk -match 'Public crop focus') 'Theme Studio Oscar Fact rows must label the public crop focus control clearly.'
Assert-True ($controlDesk -match 'Verified public visual') 'Theme Studio Oscar Fact rows must keep the verified public visual control.'
Assert-True ($controlDesk -match 'Public visual treatment') 'Theme Studio Oscar Fact rows must keep the public visual treatment control.'
Assert-True ($css -match 'lunara-control-desk-image-source-framing') 'Oscar Fact framing controls must have a scoped admin styling hook.'

Write-Host 'Theme Studio Oscar Fact framing contract passed.'
