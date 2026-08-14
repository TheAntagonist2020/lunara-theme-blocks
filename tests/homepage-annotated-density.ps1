$ErrorActionPreference = 'Stop'

$themeRoot = Split-Path -Parent $PSScriptRoot

function Assert-True {
    param(
        [bool] $Condition,
        [string] $Message
    )

    if (-not $Condition) {
        throw $Message
    }
}

function Read-ThemeFile {
    param([string] $RelativePath)

    $path = Join-Path $themeRoot $RelativePath
    Assert-True (Test-Path -LiteralPath $path) "Missing expected file: $RelativePath"
    return Get-Content -LiteralPath $path -Raw
}

$style = Read-ThemeFile 'style.css'
$setup = Read-ThemeFile 'inc/setup.php'
$homeModules = Read-ThemeFile 'assets/css/lunara-home-modules.css'

Assert-True ($style -match 'Version:\s*3\.2\.36') 'Theme version must preserve annotated homepage density in Theme 3.2.36.'
Assert-True ($setup -match "assets/css/lunara-home-modules\.css") 'The annotated homepage treatment must remain in the cacheable route stylesheet.'
Assert-True ($homeModules -match 'lunara-home-annotated-density-css') 'The annotated homepage density block must retain its named asset marker.'

$match = [regex]::Match($homeModules, '(?s)/\* lunara-home-annotated-density-css \*/(?<css>.*)')
Assert-True $match.Success 'Could not isolate the annotated homepage density CSS.'
$block = $match.Groups['css'].Value

foreach ($needle in @(
    '--lunara-home-stage-max:1440px;',
    'margin:0 auto!important;',
    'grid-template-columns:repeat(12,minmax(0,1fr))!important;',
    '.lunara-journal-home-card:nth-child(1)',
    '.lunara-journal-home-card:nth-child(4)',
    '.lunara-oscar-pick-card.is-cinematic-pending',
    'margin-left:-54px!important;',
    '@keyframes lunaraHomeOrbit',
    '@media(prefers-reduced-motion:reduce)'
)) {
    Assert-True ($block.Contains($needle)) "Annotated homepage density CSS is missing: $needle"
}

Assert-True ($block -notmatch 'url\(') 'The density pass must not add another network image request.'
$fontFamilies = [regex]::Matches($block, 'font-family:\s*(?<value>[^;]+);')
foreach ($fontFamily in $fontFamilies) {
    Assert-True ($fontFamily.Groups['value'].Value.Trim().StartsWith('var(--lunara-font-')) 'The density pass must use the approved Lunara font variables.'
}
Assert-True ($block -notmatch '<script|wp_enqueue_script|three|gsap') 'The density pass must remain CSS-only.'

Write-Host 'Homepage annotated density contract passed.'
