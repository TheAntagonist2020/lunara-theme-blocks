$ErrorActionPreference = 'Stop'

$themeRoot = Split-Path -Parent $PSScriptRoot

function Assert-True {
    param([bool] $Condition, [string] $Message)
    if (-not $Condition) { throw $Message }
}

$runtime = & php (Join-Path $PSScriptRoot 'site-studio-pilot-runtime.php') 2>&1
Assert-True ($LASTEXITCODE -eq 0) ("Site Studio pilot runtime failed:`n" + ($runtime -join [Environment]::NewLine))
Assert-True (($runtime -join "`n") -match 'site-studio pilot runtime: all assertions passed') 'Pilot runtime did not reach its completion marker.'

$homeRuntime = & php (Join-Path $PSScriptRoot 'home-block-composition-runtime.php') 2>&1
Assert-True ($LASTEXITCODE -eq 0) ("Home block composition runtime failed:`n" + ($homeRuntime -join [Environment]::NewLine))

$registry = Get-Content -Raw (Join-Path $themeRoot 'inc/site-studio-registry.php')
$adapters = Get-Content -Raw (Join-Path $themeRoot 'inc/site-studio-adapters.php')
$homeSource = Get-Content -Raw (Join-Path $themeRoot 'inc/home-blocks.php')

foreach ($factory in @(
    'lunara_site_studio_global_design_adapter',
    'lunara_site_studio_homepage_structure_adapter',
    'lunara_site_studio_lunara_method_adapter'
)) {
    Assert-True ($adapters -match [regex]::Escape($factory)) "Missing pilot factory $factory."
}
Assert-True ($homeSource -match 'function\s+lunara_compose_home_section_blocks\s*\(') 'The pure Homepage composer must exist.'
Assert-True ($homeSource -match 'wp_update_post\([\s\S]*?,\s*true\s*\)') 'The Homepage writer must request WP_Error propagation; runtime Core-unslash coverage verifies byte-exact slashing behavior.'
foreach ($queryArg in @('lunara_global_design_preview', 'lunara_homepage_preview', 'lunara_method_preview')) {
    Assert-True ($registry -match [regex]::Escape($queryArg)) "Registry is missing $queryArg."
}

Write-Host 'site-studio-pilot: all assertions passed.'
