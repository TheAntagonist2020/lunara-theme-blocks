$ErrorActionPreference = 'Stop'
$themeRoot = Split-Path -Parent $PSScriptRoot
$oldNodePath = $env:NODE_PATH
try {
    $methodHome = @($env:USERPROFILE, $env:HOME, [Environment]::GetFolderPath([Environment+SpecialFolder]::UserProfile)) | Where-Object { -not [string]::IsNullOrWhiteSpace($_) } | Select-Object -First 1
    $runtimeModules = Join-Path $methodHome '.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules'
    if (Test-Path -LiteralPath $runtimeModules) { $env:NODE_PATH = $runtimeModules + [IO.Path]::PathSeparator + $oldNodePath }
    & php (Join-Path $PSScriptRoot 'site-studio-method-runtime.php')
    if ($LASTEXITCODE -ne 0) { throw 'Method server contract failed.' }
    & node (Join-Path $PSScriptRoot 'site-studio-method-browser-runtime.js')
    if ($LASTEXITCODE -ne 0) { throw 'Method browser contract failed.' }
    & node (Join-Path $PSScriptRoot 'site-studio-method-framing-runtime.js')
    if ($LASTEXITCODE -ne 0) { throw 'Method real-renderer framing parity failed.' }
    Write-Output 'Site Studio Method contract passed.'
} finally { $env:NODE_PATH = $oldNodePath }
