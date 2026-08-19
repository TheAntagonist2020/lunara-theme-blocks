$ErrorActionPreference = 'Stop'

$themeRoot = Split-Path -Parent $PSScriptRoot
$controlDesk = Get-Content -Raw (Join-Path $themeRoot 'inc/control-desk.php')
$adminCss = Get-Content -Raw (Join-Path $themeRoot 'assets/css/lunara-control-desk.css')

if ($controlDesk -notmatch 'class="lunara-control-desk-homepage-field lunara-control-desk-reviews-lead-field"[\s\S]{0,300}<select name="lunara_reviews_archive_lead_id">') {
    throw 'The production Reviews lead label must carry the scoped shrink-safe class.'
}

$labelSelector = '.lunara-site-studio #lunara-theme-studio-reviews-archive-studio .lunara-control-desk-reviews-lead-field'
$selectSelector = $labelSelector + ' select[name="lunara_reviews_archive_lead_id"]'

function Get-CssRuleBody {
    param([string] $Selector)

    $match = [regex]::Match($adminCss, [regex]::Escape($Selector) + '\s*\{(?<body>[^}]*)\}')
    if (-not $match.Success) {
        throw "Missing scoped CSS rule: $Selector"
    }

    return $match.Groups['body'].Value
}

function Assert-CssDeclaration {
    param(
        [string] $RuleBody,
        [string] $Property,
        [string] $Value,
        [string] $Subject
    )

    $pattern = '(?m)^\s*' + [regex]::Escape($Property) + '\s*:\s*' + [regex]::Escape($Value) + '\s*;'
    if ($RuleBody -notmatch $pattern) {
        throw "$Subject must declare ${Property}: $Value for the 390px container contract."
    }
}

$labelRule = Get-CssRuleBody $labelSelector
$selectRule = Get-CssRuleBody $selectSelector

foreach ($required in @(
    @('box-sizing', 'border-box'),
    @('max-width', '100%'),
    @('min-width', '0'),
    @('width', '100%')
)) {
    Assert-CssDeclaration $labelRule $required[0] $required[1] 'Reviews lead label'
    Assert-CssDeclaration $selectRule $required[0] $required[1] 'Reviews lead selector'
}

if (($labelRule + $selectRule) -match 'overflow(?:-x)?\s*:\s*hidden') {
    throw 'The Reviews lead mobile repair must not hide overflow.'
}

$style = Get-Content -Raw (Join-Path $themeRoot 'style.css')
if ($style -notmatch '(?m)^Version:\s*3\.2\.53\s*$') {
    throw 'Theme version must preserve the Site Studio Reviews lead mobile repair in 3.2.53.'
}

Write-Host 'Site Studio Reviews lead 390px container contract passed.'
