$ErrorActionPreference = 'Stop'

$themeRoot = Split-Path -Parent $PSScriptRoot
$runner = Join-Path $PSScriptRoot 'journal-archive-first-paint-runtime.js'
if (-not (Test-Path -LiteralPath $runner)) {
    throw "Missing Journal first-paint runner: $runner"
}

$runtimeRoot = Join-Path $env:USERPROFILE '.cache\codex-runtimes\codex-primary-runtime\dependencies\node'
$bundledNode = Join-Path $runtimeRoot 'bin\node.exe'
$bundledModules = Join-Path $runtimeRoot 'node_modules'
$node = $null
$nodeModules = $null

if ((Test-Path -LiteralPath $bundledNode) -and (Test-Path -LiteralPath (Join-Path $bundledModules 'playwright'))) {
    $node = $bundledNode
    $nodeModules = $bundledModules
} else {
    $nodeCommand = Get-Command node -ErrorAction SilentlyContinue
    if ($nodeCommand) {
        $candidateModules = @()
        if ($env:NODE_PATH) {
            $candidateModules += ($env:NODE_PATH -split [IO.Path]::PathSeparator)
        }
        $candidateModules += (Join-Path (Split-Path -Parent $nodeCommand.Source) '..\node_modules')
        $candidateModules += (Join-Path $themeRoot 'node_modules')
        $nodeModules = $candidateModules |
            Where-Object { $_ -and (Test-Path -LiteralPath (Join-Path $_ 'playwright')) } |
            Select-Object -First 1
        if ($nodeModules) {
            $node = $nodeCommand.Source
        }
    }
}

if (-not $node -or -not $nodeModules) {
    throw 'Journal first-paint gate requires a Node runtime with Playwright. Install the workspace dependency bundle or provide a NODE_PATH containing playwright.'
}

$browserExecutable = $env:LUNARA_BROWSER_EXECUTABLE
if (-not $browserExecutable -or -not (Test-Path -LiteralPath $browserExecutable)) {
    $playwrightCache = Join-Path $env:LOCALAPPDATA 'ms-playwright'
    if (Test-Path -LiteralPath $playwrightCache) {
        $browserExecutable = Get-ChildItem -LiteralPath $playwrightCache -Directory -Filter 'chromium-*' -ErrorAction SilentlyContinue |
            Sort-Object Name -Descending |
            ForEach-Object { Join-Path $_.FullName 'chrome-win64\chrome.exe' } |
            Where-Object { Test-Path -LiteralPath $_ } |
            Select-Object -First 1
    }
}
if (-not $browserExecutable -or -not (Test-Path -LiteralPath $browserExecutable)) {
    $browserExecutable = @(
        (Join-Path $env:ProgramFiles 'Google\Chrome\Application\chrome.exe'),
        (Join-Path ${env:ProgramFiles(x86)} 'Microsoft\Edge\Application\msedge.exe'),
        (Join-Path $env:ProgramFiles 'Microsoft\Edge\Application\msedge.exe'),
        (Join-Path $env:LOCALAPPDATA 'Google\Chrome\Application\chrome.exe')
    ) | Where-Object { $_ -and (Test-Path -LiteralPath $_) } | Select-Object -First 1
}
if (-not $browserExecutable) {
    throw 'Journal first-paint gate resolved Playwright but no compatible Chromium, Chrome, or Edge executable. Install the workspace browser runtime or set LUNARA_BROWSER_EXECUTABLE.'
}

$nodePathParts = @($nodeModules)
$pnpmRoot = Join-Path $nodeModules '.pnpm'
if (Test-Path -LiteralPath $pnpmRoot) {
    $playwrightCore = Get-ChildItem -LiteralPath $pnpmRoot -Directory -ErrorAction SilentlyContinue |
        Where-Object { $_.Name -like 'playwright-core@*' } |
        Sort-Object Name -Descending |
        Select-Object -First 1
    if ($playwrightCore) {
        $nodePathParts += (Join-Path $playwrightCore.FullName 'node_modules')
    }
}
if ($env:NODE_PATH) {
    $nodePathParts += $env:NODE_PATH
}

$priorNodePath = $env:NODE_PATH
$priorBrowserExecutable = $env:LUNARA_BROWSER_EXECUTABLE
try {
    $env:NODE_PATH = $nodePathParts -join [IO.Path]::PathSeparator
    $env:LUNARA_BROWSER_EXECUTABLE = $browserExecutable
    & $node $runner
    if ($LASTEXITCODE -ne 0) {
        exit $LASTEXITCODE
    }
} finally {
    $env:NODE_PATH = $priorNodePath
    $env:LUNARA_BROWSER_EXECUTABLE = $priorBrowserExecutable
}
