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
    Assert-True (Test-Path $path) "Missing expected file: $RelativePath"
    return Get-Content -Raw $path
}

$controlDesk = Read-ThemeFile 'inc/control-desk.php'
$frontend = Read-ThemeFile 'inc/frontend.php'
$singleReview = Read-ThemeFile 'single-review.php'

foreach ($key in @(
    'lunara_review_single_density',
    'lunara_review_single_hero_scale',
    'lunara_review_single_rail_mode',
    'lunara_review_single_debrief_prominence',
    'lunara_review_single_pairing_density',
    'lunara_review_single_spoiler_treatment',
    'lunara_review_single_trailer_prominence'
)) {
    Assert-True ($controlDesk -match [regex]::Escape("'$key'")) "Review Single Studio must define the $key select control."
    Assert-True ($frontend -match [regex]::Escape("'$key'")) "Review Single public CSS must read the $key setting."
}

foreach ($key in @(
    'lunara_review_single_section_gap',
    'lunara_review_single_debrief_poster_width',
    'lunara_review_related_count'
)) {
    Assert-True ($controlDesk -match [regex]::Escape("'$key'")) "Review Single Studio must define the $key numeric control."
    Assert-True ($frontend -match [regex]::Escape("'$key'")) "Review Single public CSS must read the $key setting."
}

foreach ($option in @(
    'compact',
    'editorial',
    'feature',
    'standard',
    'poster-forward',
    'wide-forward',
    'balanced',
    'minimal',
    'metadata-forward',
    'signature-forward',
    'showcase',
    'shield-forward',
    'high-contrast',
    'centered'
)) {
    Assert-True ($controlDesk -match [regex]::Escape("'$option'")) "Review Single Studio must support the $option option."
}

Assert-True ($controlDesk -match 'function\s+lunara_control_desk_render_review_single_studio') 'Theme Studio must render a Review Single Studio panel.'
Assert-True ($controlDesk -match 'lunara_control_desk_render_review_single_studio\(\)') 'Theme Studio tab must call the Review Single Studio renderer.'
Assert-True ($controlDesk -match 'admin_post_lunara_save_review_single_studio') 'Review Single Studio must save through a nonce-protected admin-post handler.'
Assert-True ($controlDesk -match 'check_admin_referer\(\s*''lunara_save_review_single_studio''') 'Review Single Studio save handler must verify a nonce.'
Assert-True ($controlDesk -match 'current_user_can\(\s*''edit_theme_options''') 'Review Single Studio must remain capability protected.'
Assert-True ($controlDesk -notmatch '<textarea[^>]+lunara_review_single') 'Review Single Studio must not expose raw CSS textareas.'

foreach ($preview in @(
    "home_url( '/reviews/sinners-2025/' )",
    "home_url( '/reviews/bugonia-the-full-spoiler/' )",
    "home_url( '/reviews/' )"
)) {
    Assert-True ($controlDesk.Contains($preview)) "Review Single Studio must include preview URL $preview."
}
Assert-True ($controlDesk -match "add_query_arg\(\s*'lunara-width'\s*,\s*'390'") 'Review Single Studio must include 390px mobile preview links.'

foreach ($variable in @(
    '--lunara-review-single-section-gap',
    '--lunara-review-single-body-gap',
    '--lunara-review-single-hero-max',
    '--lunara-review-single-rail-width',
    '--lunara-review-single-debrief-poster-width',
    '--lunara-review-single-pairing-gap',
    '--lunara-review-single-related-card-min',
    '--lunara-review-single-related-excerpt-clamp'
)) {
    Assert-True ($frontend.Contains($variable)) "Review Single public CSS must emit $variable."
}

foreach ($selector in @(
    'body.single-review .lunara-review-single-page',
    'body.single-review .lunara-review-single-body-grid',
    'body.single-review .lunara-review-single-rail',
    'body.single-review .lunara-review-single-debrief-section',
    'body.single-review .lunara-review-single-debrief--pairings',
    'body.single-review .lunara-review-related'
)) {
    Assert-True ($frontend.Contains($selector)) "Review Single public CSS must stay scoped to $selector."
}

Assert-True ($frontend -match 'is_singular\(\s*''review''\s*\)') 'Review Single CSS must stay scoped to Review singular routes.'
Assert-True ($frontend -match 'line-clamp') 'Review Single density controls must tune text depth, not only spacing.'
Assert-True ($frontend -match 'function\s+lunara_output_review_single_studio_css') 'Review Single Studio must emit a named public CSS function.'
Assert-True ($frontend -match 'add_action\(\s*''wp_head''\s*,\s*''lunara_output_review_single_studio_css''') 'Review Single Studio public CSS must be hooked through wp_head.'
Assert-True ($frontend -match 'JS fixed-follow can overlap the Debrief section') 'Review Single pass must preserve the fixed-follow overlap warning.'
Assert-True ($frontend -match 'function\s+lunara_output_sidebar_scroll_follow_js[\s\S]*?return;\s*\?>') 'Review Single pass must not re-enable fixed-follow sidebar JavaScript.'

Assert-True ($singleReview -match 'lunara_render_full_spoiler_review_warning') 'Single Review template must preserve the spoiler warning renderer.'
Assert-True ($singleReview -match 'lunara_render_trailer_module') 'Single Review template must preserve the trailer module renderer.'
Assert-True ($singleReview -match 'lunara_split_review_debrief_block') 'Single Review template must preserve Debrief split rendering.'
Assert-True ($singleReview -match 'lunara-review-single-debrief--pairings') 'Single Review template must preserve Pair It With rendering.'

Write-Host 'Review Single Studio controls contract passed.'
