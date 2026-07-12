param(
    [Parameter(Mandatory = $true)]
    [string] $ControlUrl,

    [string] $CandidateUrl = $ControlUrl,

    [Parameter(Mandatory = $true)]
    [string] $OutputDirectory,

    [ValidateRange(1, 25)]
    [int] $Runs = 5,

    [string] $ControlLabel = 'control',

    [string] $CandidateLabel = 'candidate',

    [string] $ExpectedControlVersion = '',

    [string] $ExpectedCandidateVersion = '',

    [ValidateSet('none', 'first', 'all')]
    [string] $TracePolicy = 'first',

    [switch] $SkipWarm
)

$ErrorActionPreference = 'Stop'

$runner = Join-Path $PSScriptRoot 'lunara-performance-benchmark.js'
if (-not (Test-Path -LiteralPath $runner)) {
    throw "Missing benchmark runner: $runner"
}

$runtimeRoot = Join-Path $env:USERPROFILE '.cache\codex-runtimes\codex-primary-runtime\dependencies\node'
$bundledNode = Join-Path $runtimeRoot 'bin\node.exe'
$node = if (Test-Path -LiteralPath $bundledNode) {
    $bundledNode
} else {
    (Get-Command node -ErrorAction Stop).Source
}

$nodeModules = Join-Path $runtimeRoot 'node_modules'
$nodePathParts = @()
if (Test-Path -LiteralPath (Join-Path $nodeModules 'playwright')) {
    $nodePathParts += $nodeModules
    $playwrightCore = Get-ChildItem -LiteralPath (Join-Path $nodeModules '.pnpm') -Directory -ErrorAction SilentlyContinue |
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
if ($nodePathParts.Count -gt 0) {
    $env:NODE_PATH = $nodePathParts -join [IO.Path]::PathSeparator
}

$resolvedOutput = if ([IO.Path]::IsPathRooted($OutputDirectory)) {
    [IO.Path]::GetFullPath($OutputDirectory)
} else {
    [IO.Path]::GetFullPath((Join-Path (Get-Location) $OutputDirectory))
}

$arguments = @(
    $runner,
    '--controlUrl', $ControlUrl,
    '--candidateUrl', $CandidateUrl,
    '--outputDirectory', $resolvedOutput,
    '--runs', $Runs,
    '--controlLabel', $ControlLabel,
    '--candidateLabel', $CandidateLabel,
    '--tracePolicy', $TracePolicy
)
if ($ExpectedControlVersion) {
    $arguments += @('--expectedControlVersion', $ExpectedControlVersion)
}
if ($ExpectedCandidateVersion) {
    $arguments += @('--expectedCandidateVersion', $ExpectedCandidateVersion)
}
if ($SkipWarm) {
    $arguments += '--skipWarm'
}

& $node @arguments
exit $LASTEXITCODE
