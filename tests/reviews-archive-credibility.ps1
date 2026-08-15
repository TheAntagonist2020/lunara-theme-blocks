$ErrorActionPreference = 'Stop'

$root = Split-Path -Parent $PSScriptRoot
$reviewRendering = Join-Path $root 'inc\review-rendering.php'
$frontend = Join-Path $root 'inc\frontend.php'
$dynamicRail = Join-Path $root 'assets\js\lunara-dynamic-rails.js'

function Assert-Contains {
    param(
        [string] $Path,
        [string] $Pattern,
        [string] $Message
    )

    if (-not (Test-Path -LiteralPath $Path)) {
        throw "Missing file: $Path"
    }

    $content = Get-Content -LiteralPath $Path -Raw
    if ($content -notmatch $Pattern) {
        throw $Message
    }
}

Assert-Contains $reviewRendering 'data-lunara-review-dynamic-rail' 'Review archive support rail must expose the Reviews dynamic rail data hook.'
Assert-Contains $reviewRendering 'data-lunara-dynamic-rail-track' 'Review archive support rail must expose a dynamic rail track.'
Assert-Contains $reviewRendering 'data-lunara-dynamic-rail-dot' 'Review archive support rail must render navigation dots.'
Assert-Contains $reviewRendering 'data-lunara-dynamic-rail-prev' 'Review archive support rail must render a previous control.'
Assert-Contains $reviewRendering 'data-lunara-dynamic-rail-next' 'Review archive support rail must render a next control.'
Assert-Contains $reviewRendering 'lunara_render_review_grid_card' 'Review archive must continue using the existing Review card renderer.'

Assert-Contains $frontend 'lunara_enqueue_review_archive_dynamic_rails' 'Frontend must enqueue the Reviews dynamic rail asset through a theme-owned function.'
Assert-Contains $frontend 'lunara-dynamic-rails\.js' 'Frontend must reference the theme-owned dynamic rail asset.'
Assert-Contains $frontend 'lunara-review-archive-dynamic-rail' 'Frontend CSS must scope rail polish to the Reviews archive.'
Assert-Contains $frontend 'body\.post-type-archive-review\s+\.lunara-review-archive-dynamic-rail[\s\S]*grid-template-columns:\s*minmax\(0,\s*1fr\)' 'Dynamic rail CSS must use body-scoped specificity to override the old static rail grid and keep the track full-width.'
Assert-Contains $frontend 'prefers-reduced-motion' 'Reviews archive rail CSS must include reduced-motion handling.'

Assert-Contains $dynamicRail 'prefers-reduced-motion: reduce' 'Dynamic rail JS must respect reduced-motion preferences.'
Assert-Contains $dynamicRail 'pointerenter' 'Dynamic rail JS must pause on pointer hover.'
Assert-Contains $dynamicRail 'focusin' 'Dynamic rail JS must pause on focus.'
Assert-Contains $dynamicRail 'keydown' 'Dynamic rail JS must support keyboard navigation.'
Assert-Contains $dynamicRail 'touchstart' 'Dynamic rail JS must support touch navigation.'
Assert-Contains $dynamicRail 'data-lunara-dynamic-rail' 'Dynamic rail JS must target theme-owned rail hooks.'

$dynamicContent = Get-Content -LiteralPath $dynamicRail -Raw
foreach ($forbidden in @('prettyPhoto', 'all_in_one', 'magic_carousel', 'multimedia_carousel', 'lbg_zoominoutslider')) {
    if ($dynamicContent -match [regex]::Escape($forbidden)) {
        throw "Dynamic rail JS must not import or reference paid archive plugin code: $forbidden"
    }
}

Write-Output 'Reviews archive credibility contract passed.'
