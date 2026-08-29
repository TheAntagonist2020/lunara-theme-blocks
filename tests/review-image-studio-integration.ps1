$ErrorActionPreference = 'Stop'

$root = Split-Path -Parent $PSScriptRoot
$style = Get-Content -LiteralPath (Join-Path $root 'style.css') -Raw
$single = Get-Content -LiteralPath (Join-Path $root 'single-review.php') -Raw
$rendering = Get-Content -LiteralPath (Join-Path $root 'inc\review-rendering.php') -Raw
$debrief = Get-Content -LiteralPath (Join-Path $root 'inc\debrief.php') -Raw
$functions = Get-Content -LiteralPath (Join-Path $root 'functions.php') -Raw

function Assert-True {
    param([bool]$Condition, [string]$Message)
    if (-not $Condition) {
        throw $Message
    }
}

Assert-True ($style -match 'Version:\s*3\.2\.56') 'Theme must preserve the Reviews Archive composition release.'
Assert-True ($single -match "get_mode\(\s*\`$post_id,\s*'hero_banner'\s*\)") 'Single Review must read the explicit hero mode.'
Assert-True ($single -match "'off'\s*!==\s*\`$hero_image_mode") 'Off mode must suppress the Featured Image hero fallback.'
Assert-True ($rendering -match "resolve_slot\(\s*\`$post_id,\s*'card'\s*\)") 'Review cards must resolve the shared Core image contract.'
Assert-True ($rendering -match "resolve_slot\(\s*\`$post_id,\s*\`$slot\s*\)") 'Review visual slots must resolve the shared Core image contract.'
Assert-True ($rendering -match 'wp_get_attachment_image') 'Local Review visuals must retain WordPress responsive image markup.'
Assert-True ($rendering -match "fetchpriority'\]\s*=\s*'high'") 'The Review hero must remain high priority for LCP.'
Assert-True ($debrief -match "!\s*class_exists\(\s*'Lunara_Review_Image_Studio'\s*\)") 'The legacy URL controls must remain only as a Core-absent fallback.'
Assert-True ($functions -match "resolve_slot\(\s*\`$post_id,\s*'card'\s*\)") 'The active monolith card renderer must use the shared Core image contract.'
Assert-True ($functions -match "resolve_slot\(\s*\`$post_id,\s*\`$slot\s*\)") 'The active monolith visual renderer must use the shared Core image contract.'
Assert-True ($functions -match 'wp_get_attachment_image') 'The active monolith must preserve responsive attachment rendering.'
Assert-True ($functions -match "!\s*class_exists\(\s*'Lunara_Review_Image_Studio'\s*\)") 'The active monolith must hide its legacy URL controls when Core owns the Image Studio.'

Write-Output 'Review Image Studio theme integration checks passed.'
