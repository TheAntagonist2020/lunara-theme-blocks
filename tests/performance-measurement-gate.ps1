$ErrorActionPreference = 'Stop'

$themeRoot = Split-Path -Parent $PSScriptRoot
$runnerPath = Join-Path $PSScriptRoot 'tools\lunara-performance-benchmark.js'
$launcherPath = Join-Path $PSScriptRoot 'tools\lunara-performance-benchmark.ps1'

function Assert-True {
    param(
        [bool] $Condition,
        [string] $Message
    )

    if (-not $Condition) {
        throw $Message
    }
}

Assert-True (Test-Path -LiteralPath $runnerPath) 'Missing the browser performance benchmark runner.'
Assert-True (Test-Path -LiteralPath $launcherPath) 'Missing the PowerShell performance benchmark launcher.'

$runner = Get-Content -LiteralPath $runnerPath -Raw
$launcher = Get-Content -LiteralPath $launcherPath -Raw
$deployIgnore = Get-Content -LiteralPath (Join-Path $themeRoot '.deployignore') -Raw

Assert-True ($runner -match "viewportWidth', 390") 'Benchmark must default to the 390px mobile viewport.'
Assert-True ($runner -match "viewportHeight', 844") 'Benchmark must default to the 844px mobile viewport.'
Assert-True ($runner -match "cpuSlowdown', 4") 'Benchmark must retain 4x CPU slowdown.'
Assert-True ($runner -match 'Network\.emulateNetworkConditions') 'Benchmark must emulate the fixed mobile network profile.'
Assert-True ($runner -match 'Network\.clearBrowserCache') 'Cold runs must clear the browser cache.'
Assert-True ($runner -match 'prepareStagingAccess') 'Benchmark must satisfy the WordPress.com staging access challenge before timing.'
Assert-True ($runner -match "page\.goto\('about:blank'") 'Benchmark must leave the access preflight before clearing cache and timing.'
Assert-True ($runner -match 'Emulation\.setCPUThrottlingRate') 'Benchmark must apply CPU throttling through CDP.'
Assert-True ($runner -match "pair % 2 === 1") 'Benchmark must alternate AB and BA pair order.'
Assert-True ($runner -match "runs', 5") 'Benchmark must default to five paired runs.'
Assert-True ($runner -match 'coefficientOfVariation') 'Benchmark must report measurement variance.'
Assert-True ($runner -match 'medianAbsolutePairedDeltaPct') 'A/A runs must report paired noise.'
Assert-True ($runner -match 'Tracing\.start') 'Benchmark must capture diagnostic Chrome traces.'
Assert-True ($runner -match 'lcpResource') 'Benchmark must record LCP request timing.'
Assert-True ($runner -match 'inlineStyleBytes') 'Benchmark must record live inline CSS bytes.'
Assert-True ($runner -match 'htmlDecodedBytes') 'Benchmark must record live HTML payload bytes.'
Assert-True ($runner -match 'probeThemeIdentity') 'Benchmark must probe the live theme stylesheet identity.'
Assert-True ($runner -match "css\.match\(\/\^\\s\*Version:") 'Benchmark must read the authoritative style.css Version header.'
Assert-True ($runner -match "searchParams\.set\('lunara_identity'") 'Theme identity probes must bypass stale CDN objects.'
Assert-True ($launcher -match '\[Parameter\(Mandatory = \$true\)\][\s\S]*\[string\] \$OutputDirectory') 'Launcher must require an explicit evidence directory.'
Assert-True ($launcher -match 'NODE_PATH') 'Launcher must wire the bundled Playwright dependency path.'
Assert-True ($deployIgnore -match '(?m)^tests$') 'Benchmark tooling must remain excluded from WordPress.com deployments.'

Write-Host 'Performance measurement gate contract passed.'
