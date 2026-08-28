$ErrorActionPreference = 'Stop'

$themeRoot = Split-Path -Parent $PSScriptRoot
$failures = [Collections.Generic.List[string]]::new()

function Read-ThemeFile([string] $RelativePath) {
    return Get-Content -LiteralPath (Join-Path $themeRoot $RelativePath) -Raw
}

function Add-ContractFailure([bool] $Condition, [string] $Message) {
    if (-not $Condition) {
        $failures.Add($Message)
    }
}

$header = Read-ThemeFile 'header.php'
$frontPage = Read-ThemeFile 'front-page.php'
$reviews = Read-ThemeFile 'inc/review-rendering.php'
$reviewsShellStart = $reviews.IndexOf('function lunara_render_review_archive_shell')
$reviewsShellEnd = $reviews.IndexOf('function lunara_render_news_archive_shell')
$reviewsShell = $reviews.Substring($reviewsShellStart, $reviewsShellEnd - $reviewsShellStart)
$journal = Read-ThemeFile 'archive-journal.php'
$oscars = Read-ThemeFile 'page-oscars.php'
$designTokens = Read-ThemeFile 'inc/design-tokens.php'
$controlDesk = Read-ThemeFile 'inc/control-desk.php'

Add-ContractFailure (([regex]::Matches($header, '<main\b')).Count -eq 1) 'header.php must remain the sole canonical main opener.'
foreach ($route in @(
    @{ Name = 'Home'; Source = $frontPage; Classes = 'site-main lunara-front-page' },
    @{ Name = 'Reviews'; Source = $reviewsShell; Classes = 'site-main lunara-archive-page lra' },
    @{ Name = 'Journal'; Source = $journal; Classes = 'lunara-archive-page lunara-journal-archive-page' },
    @{ Name = 'Oscars'; Source = $oscars; Classes = 'site-main lunara-oscars-portal' }
)) {
    Add-ContractFailure ($route.Source -notmatch '<main\s+id="primary"') "$($route.Name) must not reopen a nested main landmark."
    Add-ContractFailure ($route.Source -match '<div\s+id="primary"') "$($route.Name) must retain #primary on a neutral route wrapper."
    Add-ContractFailure ($route.Source.Contains($route.Classes)) "$($route.Name) must retain its canonical root classes."
}

Add-ContractFailure (([regex]::Matches($oscars, 'lunara_render_oscars_winner_media_link\s*\(')).Count -eq 2) 'Both Oscars winner lanes must use the shared conditional media renderer.'
Add-ContractFailure ($oscars -notmatch '<a class="lunara-ceremony-winner-media-link"') 'Oscars winner lanes must not emit unconditional media anchors.'
Add-ContractFailure ($designTokens -notmatch 'rocket_clean_domain') 'Design Tokens saves must never clear the WP Rocket domain cache.'
foreach ($staleGuidance in @('flush cache after deploys', 'flush cache, and verify public routes', 'cache flushes, and remote linting', 'after cache flush')) {
    Add-ContractFailure ($controlDesk -notmatch [regex]::Escape($staleGuidance)) "Visible Control Desk guidance must remove stale cache-flush instruction: $staleGuidance"
}
Add-ContractFailure (([regex]::Matches($controlDesk, 'Never clear caches? as a fix', 'IgnoreCase')).Count -ge 1) 'Visible Control Desk guidance must state the canonical no-cache-clearing rule.'

$phpOutput = & php (Join-Path $PSScriptRoot 'oscars-winner-map-runtime.php') 2>&1
if ($LASTEXITCODE -ne 0) {
    $failures.Add("Oscars media behavior runtime failed:`n" + ($phpOutput -join [Environment]::NewLine))
}

$runningOnWindows = [Runtime.InteropServices.RuntimeInformation]::IsOSPlatform([Runtime.InteropServices.OSPlatform]::Windows)
$node = (Get-Command node -ErrorAction Stop).Source
$nodeModules = Join-Path $themeRoot 'node_modules'
$priorNodePath = $env:NODE_PATH
$priorBrowser = $env:LUNARA_BROWSER_EXECUTABLE
try {
    $env:NODE_PATH = @($nodeModules, $priorNodePath) | Where-Object { $_ } | Select-Object -Unique | Join-String -Separator ([IO.Path]::PathSeparator)
    if (-not $env:LUNARA_BROWSER_EXECUTABLE -or -not (Test-Path -LiteralPath $env:LUNARA_BROWSER_EXECUTABLE)) {
        $browserCandidates = if ($runningOnWindows) {
            @(
                $(if ($env:ProgramFiles) { Join-Path $env:ProgramFiles 'Google\Chrome\Application\chrome.exe' }),
                $(if ($env:ProgramFiles) { Join-Path $env:ProgramFiles 'Microsoft\Edge\Application\msedge.exe' }),
                $(if (${env:ProgramFiles(x86)}) { Join-Path ${env:ProgramFiles(x86)} 'Microsoft\Edge\Application\msedge.exe' }),
                $(if ($env:LOCALAPPDATA) { Join-Path $env:LOCALAPPDATA 'Google\Chrome\Application\chrome.exe' })
            )
        } else {
            @('/usr/bin/google-chrome', '/usr/bin/google-chrome-stable', '/usr/bin/chromium', '/usr/bin/chromium-browser')
        }
        $env:LUNARA_BROWSER_EXECUTABLE = $browserCandidates | Where-Object { $_ -and (Test-Path -LiteralPath $_) } | Select-Object -First 1
    }
    if (-not $env:LUNARA_BROWSER_EXECUTABLE) {
        throw 'No compatible Chromium, Chrome, or Edge executable was found.'
    }
    $browserOutput = & $node (Join-Path $PSScriptRoot 'public-route-stabilization-runtime.js') 2>&1
    if ($LASTEXITCODE -ne 0) {
        $failures.Add("Public-route browser runtime failed:`n" + ($browserOutput -join [Environment]::NewLine))
    }
} finally {
    $env:NODE_PATH = $priorNodePath
    $env:LUNARA_BROWSER_EXECUTABLE = $priorBrowser
}

if ($failures.Count -gt 0) {
    throw ("Public route stabilization contract failed with $($failures.Count) defect group(s):`n - " + ($failures -join "`n - "))
}

Write-Host 'Theme 3.2.54 public route stabilization contract passed: 4 routes x 5 responsive baselines, one main, contained documents, local scrollers, and named conditional Oscars media.'
