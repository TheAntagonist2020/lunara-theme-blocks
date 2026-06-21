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

Assert-True ($controlDesk -match 'lcd_iq_fact_state') 'Image Quality Console must accept an Oscar Fact state query arg.'
Assert-True ($controlDesk -match "'fact_state'\s*=>") 'Image Quality Console filters must carry a fact_state value.'

foreach ($state in @('verified','unverified','needs-image')) {
    Assert-True ($controlDesk -match [regex]::Escape("'$state'")) "Oscar Fact state filters must allow $state."
}

Assert-True ($controlDesk -match '\$fact_state') 'Row filtering must evaluate the selected Oscar Fact state.'
Assert-True ($controlDesk -match '\$has_fact_image') 'Row filtering must derive fact state from whether an image exists.'
Assert-True ($controlDesk -match '\$is_fact_verified') 'Row filtering must derive verified state from visual_verified metadata.'
Assert-True ($controlDesk -match "visual_verified") 'Oscar Fact working lanes must use the existing verified metadata.'
Assert-True ($controlDesk -match "attachment_id") 'Oscar Fact working lanes must use the existing featured image field.'

Assert-True ($controlDesk -match 'Oscar Fact state') 'Theme Studio must label the Oscar Fact state filter group.'
Assert-True ($controlDesk -match 'Verified facts') 'Theme Studio must expose a verified facts priority lane.'
Assert-True ($controlDesk -match 'Unverified facts') 'Theme Studio must expose an unverified facts priority lane.'
Assert-True ($controlDesk -match 'Needs image') 'Theme Studio must expose a needs-image priority lane.'
Assert-True ($controlDesk -match 'lcd_iq_fact_state') 'Filter URLs must include the fact-state query arg when active.'

Write-Host 'Theme Studio Oscar Fact working lanes contract passed.'
