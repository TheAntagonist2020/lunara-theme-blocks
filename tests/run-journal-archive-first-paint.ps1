param(
    [switch] $ResolveHomeOnly
)

$ErrorActionPreference = 'Stop'

function Resolve-LunaraBrowserHome {
    $candidates = @(
        $env:USERPROFILE,
        $env:HOME,
        [Environment]::GetFolderPath([Environment+SpecialFolder]::UserProfile)
    )
    foreach ($candidate in $candidates) {
        if (-not [string]::IsNullOrWhiteSpace("$candidate")) {
            return "$candidate"
        }
    }
    throw 'Journal first-paint gate could not resolve a user home directory.'
}

$homeRoot = Resolve-LunaraBrowserHome
if ($ResolveHomeOnly) {
    Write-Output $homeRoot
    exit 0
}

$themeRoot = Split-Path -Parent $PSScriptRoot
$runner = Join-Path $PSScriptRoot 'journal-archive-first-paint-runtime.js'
if (-not (Test-Path -LiteralPath $runner)) {
    throw "Missing Journal first-paint runner: $runner"
}

$runningOnWindows = [Runtime.InteropServices.RuntimeInformation]::IsOSPlatform([Runtime.InteropServices.OSPlatform]::Windows)
$runtimeRoot = Join-Path $homeRoot '.cache'
$runtimeRoot = Join-Path $runtimeRoot 'codex-runtimes'
$runtimeRoot = Join-Path $runtimeRoot 'codex-primary-runtime'
$runtimeRoot = Join-Path $runtimeRoot 'dependencies'
$runtimeRoot = Join-Path $runtimeRoot 'node'
$bundledNode = Join-Path (Join-Path $runtimeRoot 'bin') $(if ($runningOnWindows) { 'node.exe' } else { 'node' })
$bundledModules = Join-Path $runtimeRoot 'node_modules'
$node = $null
$nodeModules = $null

if (Test-Path -LiteralPath $bundledNode) {
    $node = $bundledNode
} else {
    $nodeCommand = Get-Command node -ErrorAction SilentlyContinue
    if ($nodeCommand) {
        $node = $nodeCommand.Source
    }
}

if (-not $node) {
    throw 'Journal first-paint gate requires Node 20 or newer.'
}

$candidateModules = @($bundledModules, (Join-Path $themeRoot 'node_modules'))
if ($env:NODE_PATH) {
    $candidateModules += ($env:NODE_PATH -split [IO.Path]::PathSeparator)
}
$nodeParent = Split-Path -Parent $node
$candidateModules += [IO.Path]::GetFullPath((Join-Path $nodeParent '../node_modules'))
$npmCommand = Get-Command npm -ErrorAction SilentlyContinue
if ($npmCommand) {
    $npmRoot = @(& $npmCommand.Source root -g 2>$null) | Select-Object -First 1
    if ($npmRoot) {
        $candidateModules += "$npmRoot"
    }
}
$nodeModules = $candidateModules |
    Where-Object {
        $_ -and (
            (Test-Path -LiteralPath (Join-Path $_ 'playwright')) -or
            (Test-Path -LiteralPath (Join-Path $_ 'playwright-core'))
        )
    } |
    Select-Object -Unique |
    Select-Object -First 1
if (-not $nodeModules) {
    throw 'Journal first-paint gate requires pinned Playwright. Run npm ci --ignore-scripts or provide NODE_PATH with playwright/playwright-core.'
}

$browserExecutable = $env:LUNARA_BROWSER_EXECUTABLE
if (-not $browserExecutable -or -not (Test-Path -LiteralPath $browserExecutable)) {
    $playwrightCaches = @()
    if ($env:PLAYWRIGHT_BROWSERS_PATH) {
        $playwrightCaches += $env:PLAYWRIGHT_BROWSERS_PATH
    }
    if ($runningOnWindows -and $env:LOCALAPPDATA) {
        $playwrightCaches += (Join-Path $env:LOCALAPPDATA 'ms-playwright')
    } elseif ($env:HOME) {
        $playwrightCaches += (Join-Path (Join-Path $env:HOME '.cache') 'ms-playwright')
    }
    foreach ($playwrightCache in $playwrightCaches) {
        if (-not (Test-Path -LiteralPath $playwrightCache)) {
            continue
        }
        $browserExecutable = Get-ChildItem -LiteralPath $playwrightCache -Directory -Filter 'chromium-*' -ErrorAction SilentlyContinue |
            Sort-Object Name -Descending |
            ForEach-Object {
                @(
                    (Join-Path (Join-Path $_.FullName 'chrome-win64') 'chrome.exe'),
                    (Join-Path (Join-Path $_.FullName 'chrome-linux') 'chrome'),
                    (Join-Path (Join-Path $_.FullName 'chrome-linux64') 'chrome')
                )
            } |
            Where-Object { Test-Path -LiteralPath $_ } |
            Select-Object -First 1
        if ($browserExecutable) {
            break
        }
    }
}
if (-not $browserExecutable -or -not (Test-Path -LiteralPath $browserExecutable)) {
    if ($runningOnWindows) {
        $browserCandidates = @()
        if ($env:ProgramFiles) {
            $browserCandidates += (Join-Path $env:ProgramFiles 'Google\Chrome\Application\chrome.exe')
            $browserCandidates += (Join-Path $env:ProgramFiles 'Microsoft\Edge\Application\msedge.exe')
        }
        if (${env:ProgramFiles(x86)}) {
            $browserCandidates += (Join-Path ${env:ProgramFiles(x86)} 'Microsoft\Edge\Application\msedge.exe')
        }
        if ($env:LOCALAPPDATA) {
            $browserCandidates += (Join-Path $env:LOCALAPPDATA 'Google\Chrome\Application\chrome.exe')
        }
    } else {
        $browserCandidates = @(
            '/usr/bin/google-chrome',
            '/usr/bin/google-chrome-stable',
            '/usr/bin/chromium',
            '/usr/bin/chromium-browser'
        )
    }
    $browserExecutable = $browserCandidates | Where-Object { $_ -and (Test-Path -LiteralPath $_) } | Select-Object -First 1
}
if (-not $browserExecutable) {
    throw 'Journal first-paint gate resolved Playwright but no compatible Chromium, Chrome, or Edge executable. Install a system browser or set LUNARA_BROWSER_EXECUTABLE.'
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
    $nodePathParts += ($env:NODE_PATH -split [IO.Path]::PathSeparator)
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
