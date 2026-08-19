$ErrorActionPreference = 'Stop'

$themeRoot = Split-Path -Parent $PSScriptRoot
$scriptPath = Join-Path $themeRoot 'assets/js/lunara-immersive-read.js'
$stylePath = Join-Path $themeRoot 'style.css'
$runtimePath = Join-Path $themeRoot 'assets/js/lunara-public-runtime.js'
$guardrailsPath = Join-Path $themeRoot 'assets/css/lunara-public-guardrails.css'

if (-not (Test-Path -LiteralPath $scriptPath)) {
    throw 'Missing Review immersive-read runtime.'
}

$script = Get-Content -Raw -LiteralPath $scriptPath
$style = Get-Content -Raw -LiteralPath $stylePath
$runtime = Get-Content -Raw -LiteralPath $runtimePath
$guardrails = Get-Content -Raw -LiteralPath $guardrailsPath

if ($script -match "bar\.id\s*=\s*'lunara-read-progress'" -or $style -match '#lunara-read-progress') {
    throw 'The retired immersive reading-progress line must not return.'
}
if ($runtime -match "line\.className\s*=\s*'lunara-reading-hairline'" -or $guardrails -match '\.lunara-reading-hairline') {
    throw 'The duplicate public reading-progress line must not return.'
}
if ($script -notmatch "body\.classList\.toggle\('is-lights-down',\s*above\)" -or $style -notmatch 'body\.is-lights-down') {
    throw 'Lights Down must remain available after the progress lines are retired.'
}
if ($script -notmatch "style\.setProperty\(\s*'--lunara-ambient'") {
    throw 'The hero-derived ambient color must remain available.'
}

if ($script -notmatch 'var ambientSampleUrl\s*=\s*function') {
    throw 'The Review ambient sampler must resolve a canvas-safe URL before loading.'
}
if ($script -notmatch "url\.hostname\.toLowerCase\(\)\s*===\s*'image\.tmdb\.org'") {
    throw 'Direct TMDB hero art must use the explicit CDN proxy path.'
}
if ($script -notmatch "new URL\('https://i0\.wp\.com/'\s*\+\s*url\.hostname\s*\+\s*url\.pathname\)") {
    throw 'TMDB ambient samples must be requested through the WordPress.com image CDN.'
}
foreach ($setting in @("'resize', '48,48'", "'quality', '60'", "'ssl', '1'")) {
    if ($script -notmatch [regex]::Escape($setting)) {
        throw "The tiny ambient sample is missing its $setting setting."
    }
}
if ($script -notmatch 'var sampleUrl\s*=\s*ambientSampleUrl\(img\)') {
    throw 'The ambient image loader must use the guarded sample URL.'
}
if ($script -notmatch 'sample\.src\s*=\s*sampleUrl') {
    throw 'The ambient image request must use the guarded sample URL.'
}
if ($script -match 'sample\.src\s*=\s*img\.(?:currentSrc|src)') {
    throw 'The ambient sampler must not re-fetch an unapproved cross-origin image directly.'
}
if ($style -notmatch 'Version:\s*3\.2\.53') {
    throw 'Theme version must be 3.2.53 for the immersive-read gate.'
}

Write-Host 'Review immersive-read CORS contract passed.'
