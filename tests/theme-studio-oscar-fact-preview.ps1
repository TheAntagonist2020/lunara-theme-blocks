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
$css = Read-ThemeFile 'assets/css/lunara-control-desk.css'

Assert-True ($controlDesk -match 'function\s+lunara_control_desk_render_oscar_fact_visual_preview') 'Theme Studio must have a scoped Oscar Fact preview helper.'
Assert-True ($controlDesk -match 'lunara-control-desk-oscar-fact-preview') 'Oscar Fact preview markup must use a scoped admin class.'
Assert-True ($controlDesk -match 'Wide crop') 'Oscar Fact preview must label the cropped public interpretation.'
Assert-True ($controlDesk -match 'Archival fit') 'Oscar Fact preview must label the full-frame public interpretation.'
Assert-True ($controlDesk -match 'lunara_oscar_fact_visual_focus_css') 'Oscar Fact preview must use the same focus CSS source as the public renderer.'
Assert-True ($controlDesk -match '--lunara-admin-fact-focus') 'Oscar Fact preview must expose the selected focus as a CSS custom property.'
Assert-True ($controlDesk -match '--lunara-admin-fact-image') 'Oscar Fact preview must expose the selected image as a CSS custom property.'
Assert-True ($controlDesk -match 'if\s*\(\s*!\s*\$attachment_id\s*\)') 'Oscar Fact preview must avoid rendering an empty preview chamber when no image is selected.'

Assert-True ($css -match '\.lunara-control-desk-oscar-fact-preview') 'Oscar Fact preview must have scoped admin styling.'
Assert-True ($css -match 'object-fit:\s*cover') 'Wide crop preview must use cover behavior.'
Assert-True ($css -match 'object-fit:\s*contain') 'Archival fit preview must use contain behavior.'
Assert-True ($css -match 'background-image:\s*var\(--lunara-admin-fact-image') 'Archival preview must use a backing plate from the same selected image.'
Assert-True ($css -match '@media\s*\(max-width:\s*600px\)') 'Oscar Fact preview must have a narrow admin responsive fallback.'

Write-Host 'Theme Studio Oscar Fact preview contract passed.'
