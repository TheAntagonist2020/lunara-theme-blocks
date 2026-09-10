param([switch] $ResolveHomeOnly)
$ErrorActionPreference = 'Stop'
function Resolve-LunaraBrowserHome { foreach ($candidate in @($env:USERPROFILE,$env:HOME,[Environment]::GetFolderPath([Environment+SpecialFolder]::UserProfile))) { if (-not [string]::IsNullOrWhiteSpace("$candidate")) { return "$candidate" } } throw 'Site Studio gate could not resolve a user home directory.' }
$browserHome = Resolve-LunaraBrowserHome
if ($ResolveHomeOnly) { Write-Output $browserHome; exit 0 }
$themeRoot = Split-Path -Parent $PSScriptRoot
$runningOnWindows = [Runtime.InteropServices.RuntimeInformation]::IsOSPlatform([Runtime.InteropServices.OSPlatform]::Windows)
function Assert-True([bool]$Condition, [string]$Message) { if (-not $Condition) { throw $Message } }
function Read-ThemeFile([string]$RelativePath) { $path = Join-Path $themeRoot $RelativePath; Assert-True (Test-Path -LiteralPath $path) "Missing expected file: $RelativePath"; Get-Content -LiteralPath $path -Raw }
function Resolve-LunaraNode {
    $runtimeRoot = Join-Path (Join-Path (Join-Path (Join-Path (Join-Path $browserHome '.cache') 'codex-runtimes') 'codex-primary-runtime') 'dependencies') 'node'
    $bundledNode = Join-Path (Join-Path $runtimeRoot 'bin') $(if ($runningOnWindows) { 'node.exe' } else { 'node' })
    if (Test-Path -LiteralPath $bundledNode) { return $bundledNode }
    $command = Get-Command node -ErrorAction SilentlyContinue
    if ($command) { return $command.Source }
    return $null
}
function Resolve-LunaraPlaywrightModules([string] $Node) {
    $runtimeModules = Join-Path (Join-Path (Join-Path (Join-Path (Join-Path (Join-Path $browserHome '.cache') 'codex-runtimes') 'codex-primary-runtime') 'dependencies') 'node') 'node_modules'
    $candidates = @($runtimeModules, (Join-Path $themeRoot 'node_modules'))
    if ($env:NODE_PATH) { $candidates += ($env:NODE_PATH -split [IO.Path]::PathSeparator) }
    if ($Node) { $candidates += [IO.Path]::GetFullPath((Join-Path (Split-Path -Parent $Node) '../node_modules')) }
    $npm = Get-Command npm -ErrorAction SilentlyContinue
    if ($npm) { $npmRoot = @(& $npm.Source root -g 2>$null) | Select-Object -First 1; if ($npmRoot) { $candidates += "$npmRoot" } }
    return $candidates | Where-Object { $_ -and ((Test-Path -LiteralPath (Join-Path $_ 'playwright')) -or (Test-Path -LiteralPath (Join-Path $_ 'playwright-core'))) } | Select-Object -Unique | Select-Object -First 1
}
function Resolve-LunaraBrowser {
    $candidates = @($env:LUNARA_BROWSER_EXECUTABLE)
    $playwrightCaches = @()
    if ($env:PLAYWRIGHT_BROWSERS_PATH) { $playwrightCaches += $env:PLAYWRIGHT_BROWSERS_PATH }
    if ($runningOnWindows -and $env:LOCALAPPDATA) { $playwrightCaches += (Join-Path $env:LOCALAPPDATA 'ms-playwright') }
    elseif ($browserHome) { $playwrightCaches += (Join-Path (Join-Path $browserHome '.cache') 'ms-playwright') }
    foreach ($cache in $playwrightCaches) { if (Test-Path -LiteralPath $cache) { $candidates += Get-ChildItem -LiteralPath $cache -Directory -Filter 'chromium-*' -ErrorAction SilentlyContinue | Sort-Object Name -Descending | ForEach-Object { @((Join-Path (Join-Path $_.FullName 'chrome-win64') 'chrome.exe'),(Join-Path (Join-Path $_.FullName 'chrome-linux') 'chrome'),(Join-Path (Join-Path $_.FullName 'chrome-linux64') 'chrome')) } } }
    if ($runningOnWindows) {
        if ($env:ProgramFiles) { $candidates += (Join-Path $env:ProgramFiles 'Google\Chrome\Application\chrome.exe'); $candidates += (Join-Path $env:ProgramFiles 'Microsoft\Edge\Application\msedge.exe') }
        if (${env:ProgramFiles(x86)}) { $candidates += (Join-Path ${env:ProgramFiles(x86)} 'Microsoft\Edge\Application\msedge.exe') }
        if ($env:LOCALAPPDATA) { $candidates += (Join-Path $env:LOCALAPPDATA 'Google\Chrome\Application\chrome.exe') }
    } else { $candidates += @('/usr/bin/google-chrome','/usr/bin/google-chrome-stable','/usr/bin/chromium','/usr/bin/chromium-browser') }
    return $candidates | Where-Object { $_ -and (Test-Path -LiteralPath $_) } | Select-Object -First 1
}


$node = Resolve-LunaraNode
$nodeModules = Resolve-LunaraPlaywrightModules $node
$browser = Resolve-LunaraBrowser
Assert-True ([bool]$node -and [bool]$nodeModules -and [bool]$browser) 'Portal checks require PHP, Node, Playwright and Chromium.'
$priorBrowser = $env:LUNARA_BROWSER_EXECUTABLE; $priorNodePath = $env:NODE_PATH
try {
    $env:LUNARA_BROWSER_EXECUTABLE = $browser
    $env:NODE_PATH = (@($nodeModules,$priorNodePath) | Where-Object { $_ } | Select-Object -Unique) -join ([IO.Path]::PathSeparator)
    & php (Join-Path $PSScriptRoot 'site-studio-oscars-runtime.php')
    Assert-True ($LASTEXITCODE -eq 0) 'Portal provider integration failed.'
    & $node (Join-Path $PSScriptRoot 'site-studio-oscars-browser-runtime.js')
    Assert-True ($LASTEXITCODE -eq 0) 'Portal browser integration failed.'
} finally { $env:LUNARA_BROWSER_EXECUTABLE = $priorBrowser; $env:NODE_PATH = $priorNodePath }
Write-Host 'site-studio-oscars: all assertions passed.'
