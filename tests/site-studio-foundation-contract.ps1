$ErrorActionPreference = 'Stop'

$themeRoot = Split-Path -Parent $PSScriptRoot

function Assert-True {
    param([bool] $Condition, [string] $Message)
    if (-not $Condition) { throw $Message }
}

$runtime = & php (Join-Path $PSScriptRoot 'site-studio-foundation-runtime.php') 2>&1
Assert-True ($LASTEXITCODE -eq 0) ("Site Studio foundation runtime failed:`n" + ($runtime -join [Environment]::NewLine))

foreach ($reviewCase in @(
    'sticky-ownership',
    'adapter-only',
    'throwing-callbacks',
    'preview-capability',
    'preview-disabled',
    'state-projection',
    'revision-durability',
    'authorization-order',
    'design-token-inheritance'
)) {
    $reviewRuntime = & php (Join-Path $PSScriptRoot 'site-studio-foundation-runtime.php') $reviewCase 2>&1
    Assert-True ($LASTEXITCODE -eq 0) ("Site Studio review case $reviewCase failed:`n" + ($reviewRuntime -join [Environment]::NewLine))
}

$loader = Get-Content -LiteralPath (Join-Path $themeRoot 'functions-loader.php') -Raw
$customizer = Get-Content -LiteralPath (Join-Path $themeRoot 'inc/customizer.php') -Raw
$siteStudio = Get-Content -LiteralPath (Join-Path $themeRoot 'inc/site-studio.php') -Raw

foreach ($module in @('site-studio-registry.php', 'site-studio-adapters.php', 'site-studio-rest.php')) {
    Assert-True ($loader -match [regex]::Escape("require_once `$lunara_inc . '$module';")) "The split loader must unconditionally load $module."
}

$designPos = $loader.IndexOf("require_once `$lunara_inc . 'design-tokens.php';")
$registryPos = $loader.IndexOf("require_once `$lunara_inc . 'site-studio-registry.php';")
$routerPos = $loader.IndexOf("require_once `$lunara_inc . 'site-studio.php';")
Assert-True ($designPos -ge 0 -and $registryPos -gt $designPos -and $routerPos -gt $registryPos) 'Registry/REST must load after Design Tokens, while the admin router consumes that unconditional foundation.'
Assert-True ($loader -notmatch "if \( is_admin\(\) \) \{[\s\S]{0,180}site-studio-registry\.php") 'The registry foundation must not be admin-only.'

$functionStart = $customizer.IndexOf('function lunara_output_runtime_customizer_css')
$functionEnd = $customizer.IndexOf("add_action( 'wp_head', 'lunara_output_runtime_customizer_css'", $functionStart)
Assert-True ($functionStart -ge 0 -and $functionEnd -gt $functionStart) 'Customizer runtime emitter function must remain present.'
$runtimeSlice = $customizer.Substring($functionStart, $functionEnd - $functionStart)
foreach ($variable in @('--lunara-bg-primary:', '--lunara-bg-secondary:', '--lunara-gold:', '--lunara-gold-light:', '--lunara-text:', '--lunara-text-muted:')) {
    Assert-True (-not $runtimeSlice.Contains($variable)) "Customizer must stop competing for $variable."
}
foreach ($variable in @('--lunara-bg-deep:', '--lunara-bg-card:', '--lunara-border:', '--lunara-heading-font-stack:', '--lunara-body-font-stack:')) {
    Assert-True ($runtimeSlice.Contains($variable)) "Customizer must retain non-overlapping $variable."
}

Assert-True ($siteStudio -notmatch 'function\s+lunara_site_studio_surfaces\s*\(') 'The admin router must consume, not redefine, the unconditional registry.'
Assert-True ($siteStudio -match 'lunara_site_studio_surface_availability') 'The router must render a clear known-unavailable destination instead of a blank workspace.'

Write-Host 'site-studio-foundation: all assertions passed.'
