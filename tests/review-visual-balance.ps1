$ErrorActionPreference = 'Stop'

function Assert-True {
    param([bool]$Condition, [string]$Message)
    if (-not $Condition) { throw $Message }
}

$root = Split-Path -Parent $PSScriptRoot
$style = Get-Content (Join-Path $root 'style.css') -Raw
$front = Get-Content (Join-Path $root 'inc/frontend.php') -Raw
$desk  = Get-Content (Join-Path $root 'inc/control-desk.php') -Raw
$reviewCss = Get-Content (Join-Path $root 'assets/css/lunara-review-single.css') -Raw

Assert-True ($style -match 'Version:\s*3\.2\.35') 'Theme must identify the mobile CLS gate.'
Assert-True ($reviewCss -match 'width:\s*min\(1060px,\s*calc\(100vw\s*-\s*88px\)\)') 'The desktop Debrief must use the calmer 1060px editorial measure.'
Assert-True ($reviewCss -match 'rgba\(224,\s*196,\s*129,\s*0\.09\)') 'The Debrief gradient must retain only the subtle warm-gold bloom.'
Assert-True ($reviewCss -notmatch 'rgba\(112,\s*148,\s*185,\s*0\.12\)') 'The former steel-blue bloom must not return.'
Assert-True ($reviewCss -match '0 18px 44px rgba\(0,\s*0,\s*0,\s*0\.22\)') 'The Debrief shadow must remain restrained.'
Assert-True ($reviewCss -match 'linear-gradient\(135deg,\s*rgba\(255,\s*255,\s*255,\s*0\.022\),\s*transparent 38%\)') 'The signature surface must use a quiet sheen instead of the former heavy striped emboss.'
Assert-True ($front -match '''signature-forward''\s*===\s*\$debrief_prominence[\s\S]*?min\(\s*300,\s*\$debrief_poster_width\s*\)') 'Signature-forward must prioritize editorial data instead of enlarging the poster.'
Assert-True ($desk -match "'lunara_review_single_debrief_poster_width'[\s\S]*?'default'\s*=>\s*300[\s\S]*?'max'\s*=>\s*360") 'Review Studio must expose the revised practical poster-width range.'

Write-Host 'Review visual-balance contract passed.'
