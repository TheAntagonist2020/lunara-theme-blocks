$ErrorActionPreference = 'Stop'

$themeRoot = Split-Path -Parent $PSScriptRoot
$routeCssPath = Join-Path $themeRoot 'assets/css/lunara-review-archive.css'
$componentCssPath = Join-Path $themeRoot 'assets/css/lunara-review-components.css'
$frontendPath = Join-Path $themeRoot 'inc/frontend.php'
$criticalRenderer = Join-Path $PSScriptRoot 'reviews-archive-critical-render.php'
$runtimePath = Join-Path $PSScriptRoot 'reviews-archive-text-led-cards-runtime.js'
$stylePath = Join-Path $themeRoot 'style.css'

$routeCss = Get-Content -Raw $routeCssPath
$componentCss = Get-Content -Raw $componentCssPath
$frontend = Get-Content -Raw $frontendPath
$style = Get-Content -Raw $stylePath
$runtime = Get-Content -Raw $runtimePath
$payload = @{
    orders = @{
        hero = 1
        utility = 2
        grid = 2
        pagination = 3
        'pairing-desk' = 4
    }
    visibility = @{
        hero = $true
        grid = $true
        'pairing-desk' = $true
    }
} | ConvertTo-Json -Compress -Depth 5
$payload64 = [Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes($payload))
$criticalCss = & php $criticalRenderer $payload64
if ($LASTEXITCODE -ne 0) {
    throw 'Unable to render the real Reviews archive critical seed.'
}

$routeRule = [regex]::Match(
    $routeCss,
    '(?s)#primary\.lra\s+\.lunara-review-archive-uniform\s+\.lunara-review-grid-card\.is-text-led\s+\.lunara-review-grid-link\s*\{(?<body>[^}]*)\}'
)
if (-not $routeRule.Success) {
    throw 'The route asset must own an archive-scoped, high-specificity text-led mobile card rule.'
}
if ($routeRule.Groups['body'].Value -notmatch 'grid-template-columns\s*:\s*minmax\(0\s*,\s*1fr\)\s*!important') {
    throw 'The route asset must collapse image-less Review cards to one mobile column.'
}
if ($routeRule.Groups['body'].Value -notmatch 'min-height\s*:\s*0\s*!important') {
    throw 'The route asset must release the media-backed mobile minimum height for image-less cards.'
}
$copyRule = [regex]::Match(
    $routeCss,
    '(?s)#primary\.lra\s+\.lunara-review-archive-uniform\s+\.lunara-review-grid-card\.is-text-led\s+\.lunara-review-grid-copy\s*\{(?<body>[^}]*)\}'
)
if (-not $copyRule.Success -or $copyRule.Groups['body'].Value -notmatch 'grid-column\s*:\s*1\s*/\s*-1\s*!important') {
    throw 'The image-less Review copy must explicitly span every mobile archive grid column.'
}

$pairingRule = [regex]::Match(
    $componentCss,
    '(?s)@media\s*\(max-width:\s*680px\)\s*\{.*?\.lunara-pairing-desk-section\s+\.lunara-pair-card::after\s*\{(?<body>[^}]*)\}'
)
if (-not $pairingRule.Success) {
    throw 'The shared Review components asset must own the compact Pairing numeral treatment below 680px.'
}
$pairingCounterBody = $pairingRule.Groups['body'].Value
foreach ($required in @('display\s*:\s*none\s*!important')) {
    if ($pairingCounterBody -notmatch $required) {
        throw "The collision-safe mobile Pairing numeral rule is missing: $required"
    }
}
if ($routeCss -match 'lunara-pair-card::after[\s\S]{0,180}font-size\s*:\s*2rem') {
    throw 'The shared Pairing collision repair must not be stranded in the Reviews-only route asset.'
}
if ($componentCss -match '(?s)\.lunara-pairing-desk-section\s+\.lunara-pair-card-role\s*,\s*\.lunara-pairing-desk-section\s+\.lunara-pair-card-title\s*\{[^}]*padding-right') {
    throw 'The absolute Pairing numeral repair must not reflow role/title copy.'
}
if ($frontend -match 'lunara-pairing-mobile-geometry' -or $frontend -match "lunara_rocket_preserve_review_archive_inline_css[\s\S]{0,320}'lunara-pair-it-with-vars'") {
    throw 'The card-only Pairing repair must not add an inline or optimizer-delivery workaround.'
}

if ([Text.Encoding]::UTF8.GetByteCount($routeCss) -gt 46080) {
    throw 'The Reviews archive route stylesheet exceeded its 45 KB raw budget.'
}
if ([Text.Encoding]::UTF8.GetByteCount($componentCss) -gt 20480) {
    throw 'The shared Review component stylesheet exceeded its canonical 20 KB raw budget.'
}
$criticalBytes = [Text.Encoding]::UTF8.GetBytes([string] $criticalCss)
$sha256 = [Security.Cryptography.SHA256]::Create()
try {
    $criticalHashBytes = $sha256.ComputeHash($criticalBytes)
} finally {
    $sha256.Dispose()
}
$criticalHash = -join ($criticalHashBytes | ForEach-Object { $_.ToString('x2') })
if ($criticalBytes.Count -ne 11602 -or $criticalHash -ne '24999682414bab62f5a05fda4af3682d30459ff83c99e461188f01154fe37567') {
    throw "The 3.2.43 card repair must not alter the independently approved CLS seed ($($criticalBytes.Count)B $criticalHash)."
}
if ($style -notmatch '(?m)^Version:\s*3\.2\.52\s*$') {
    throw 'Theme version must preserve the mobile text-led card repair in 3.2.52.'
}
foreach ($viewport in @('375', '390', '782', '1440')) {
    if ($runtime -notmatch "(?m)for \(const viewportWidth of \[[^\]]*\b$viewport\b") {
        throw "The actual-browser regression must exercise the $viewport pixel viewport."
    }
}
foreach ($runtimeGate in @(
    'assertTextLedFullWidth',
    'assertMediaBackedPreserved',
    'assertPairingNumerals',
    'homepageMarkup',
    'body class="home"',
    'roleIntersection',
    'titleIntersection',
    'scrollWidth > after.viewport'
)) {
    if (-not $runtime.Contains($runtimeGate)) {
        throw "The actual-browser regression is missing gate: $runtimeGate"
    }
}

Write-Host 'Theme 3.2.52 Reviews mobile card legibility contract passed.'
