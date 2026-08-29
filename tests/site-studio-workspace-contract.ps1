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

$studio = Read-ThemeFile 'inc/site-studio.php'
$control = Read-ThemeFile 'inc/control-desk.php'
$studioCss = Read-ThemeFile 'assets/css/lunara-site-studio.css'
$studioJs = Read-ThemeFile 'assets/js/lunara-site-studio.js'
$controlCss = Read-ThemeFile 'assets/css/lunara-control-desk.css'

Assert-True ($control -notmatch "(?s)function\s+lunara_enqueue_control_desk_assets\s*\([^)]*\)\s*\{.{0,500}lunara_page_lunara-site-studio") 'Control Desk must not enqueue its bundle on Site Studio.'
Assert-True ($studio -match "function\s+lunara_enqueue_site_studio_assets" -and $studio -match "'lunara_page_lunara-site-studio'") 'Site Studio must own an exact-hook enqueue.'
Assert-True ($studio -match "LunaraSiteStudioWorkspaceConfig" -and $studio -match "'protocol'\s*=>\s*'lunara-site-studio/v1'" -and $studio -match "'clientVersion'\s*=>\s*1") 'The workspace must localize its locked protocol.'
Assert-True ($studio -match "'global-design'\s*=>\s*array\(\s*\)" -and $studio -match "'homepage-structure'\s*=>\s*array\(\s*'hero'.*'latest-reviews'.*'pairing-desk'.*'dispatch'.*'oscar-picks'.*'oscar-facts'" -and $studio -match "'lunara-method'\s*=>\s*array\(\s*'pairing-desk'\s*\)") 'Marker compatibility lists must be explicit per pilot.'
Assert-True ($studio -match 'data-lunara-surface-card' -and $studio -match 'data-surface=' -and $studio -match 'data-search-index=') 'Surface cards must expose stable local-search attributes.'
Assert-True ($studio -match 'sandbox="allow-scripts allow-same-origin"' -and $studio -notmatch 'allow-forms|allow-popups|allow-downloads|allow-top-navigation') 'The preview must use the strict exact sandbox.'
Assert-True ($studio -notmatch 'call_user_func\(\s*\$active\[''renderer''\]' -and $studio -notmatch "boundary_guard\(\s*'renderer'") 'The new workspace must not call legacy renderers.'
Assert-True ($controlCss -notmatch '\.lunara-site-studio') 'Control Desk CSS must not retain Site Studio selectors.'
Assert-True ($studioCss -match 'min-width\s*:\s*0' -and $studioCss -match '@media\s*\(max-width:\s*782px\)' -and $studioCss -notmatch '(?s)(html|body|\.lunara-site-studio[^,{]*)[^}]*overflow-x\s*:\s*(hidden|clip)') 'Dedicated CSS must own shrink-safe responsive geometry without overflow masking.'
Assert-True ($studioCss -notmatch '(?s)\.lunara-site-studio-preview\s+iframe\s*\{[^}]*width\s*:\s*100%') 'The fixed-width iframe must not be repaired with width:100%.'
Assert-True ($studioCss -match 'min-height\s*:\s*44px' -and $studioCss -match ':focus-visible' -and $studioCss -notmatch 'outline\s*:\s*(?:0|none)') 'The workspace must lock target size and visible focus.'
Assert-True ($studioJs -match 'beforeunload' -and $studioJs -match 'confirm:\s*true' -and $studioJs -match 'AbortController|operationSequence') 'The controller must guard dirty navigation, confirmed restores, and stale operations.'
Assert-True ($studioJs.Contains('lastPreviewFingerprint=transaction.lastPreviewFingerprint;')) 'Cancelled Global edits must restore the exact pre-edit preview fingerprint.'
Assert-True ($studioJs -notmatch 'localStorage|sessionStorage|postMessage|addEventListener\(\s*[''\"]message') 'Commit 2 must remain memory-only and must not add the preview bridge.'
function Test-LunaraEs5Subset([string] $Source) {
    if ($Source.IndexOf([char]96) -ge 0) { return $false }
    $code = [regex]::Replace($Source, '(?s)/\*.*?\*/|//[^\r\n]*|"(?:\\.|[^"\\])*"|''(?:\\.|[^''\\])*''', ' ')
    $forbidden = '\b(?:const|let|class|async|await|import|export)\b|=>|\?\.|\?\?|\.\.\.|function\s*\*|for\s*\([^)]*\bof\b|\bvar\s*[\[\{]|function[^\(]*\(\s*[\[\{]|function[^\(]*\([^\)]*\b[A-Za-z_$][\w$]*\s*=|(?:^|[;,(])\s*[\[\{][^;\r\n=]*[\]\}]\s*=|\{\s*\[|(?:=|\breturn|\(|,)\s*\{\s*[A-Za-z_$][\w$]*\s*\(|\{\s*[A-Za-z_$][\w$]*\s*(?:,|\})'
    return $code -notmatch $forbidden
}
Assert-True (Test-LunaraEs5Subset $studioJs) 'The dedicated controller must preserve the promised ES5-compatible syntax subset.'
$forbiddenEs5Mutations = @(
    'const value = 1;', 'let value = 1;', 'var fn = (value) => value;', 'function fn(value = 1) {}',
    'function fn(...values) {}', 'var values = [...items];', 'var [first] = items;', 'var { first } = item;',
    'for (var item of items) {}', 'function* items() {}', 'var object = { first };', 'var object = { [first]: 1 };',
    'var object = { first() {} };', 'async function fn() { await work(); }', 'import value from ''module'';',
    'var value = item?.value ?? fallback;', 'var value = `template`;'
)
foreach ($mutation in $forbiddenEs5Mutations) { Assert-True (-not (Test-LunaraEs5Subset $mutation)) "ES5 source gate missed representative mutation: $mutation" }
Assert-True (Test-LunaraEs5Subset 'var object = { first: first }; function fn(value) { return value; }') 'ES5 source gate must allow ordinary ES5 object/function syntax.'

$node = Resolve-LunaraNode
$nodeModules = Resolve-LunaraPlaywrightModules $node
$browser = Resolve-LunaraBrowser
Assert-True ([bool]$node) 'A Node runtime is required for the real-browser contract.'
Assert-True ([bool]$nodeModules) 'Pinned Playwright or Playwright Core must be resolvable from the bundled, project, or configured module path.'
Assert-True ([bool]$browser) 'A real Chrome/Chromium executable is required.'
$priorBrowser = $env:LUNARA_BROWSER_EXECUTABLE; $priorNodePath = $env:NODE_PATH
try {
    $env:LUNARA_BROWSER_EXECUTABLE = $browser
    $env:NODE_PATH = (@($nodeModules,$priorNodePath) | Where-Object { $_ } | Select-Object -Unique) -join ([IO.Path]::PathSeparator)
    $phpOutput = & php (Join-Path $PSScriptRoot 'site-studio-runtime.php') 2>&1
    Assert-True ($LASTEXITCODE -eq 0) ("Site Studio PHP runtime failed: " + ($phpOutput -join [Environment]::NewLine))
    $nodeOutput = & $node (Join-Path $PSScriptRoot 'site-studio-workspace-runtime.js') 2>&1
    Assert-True ($LASTEXITCODE -eq 0) ("Site Studio browser runtime failed: " + ($nodeOutput -join [Environment]::NewLine))
} finally { $env:LUNARA_BROWSER_EXECUTABLE = $priorBrowser; $env:NODE_PATH = $priorNodePath }
Write-Host 'site-studio-workspace: all assertions passed.'
