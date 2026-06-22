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

$selectKeys = @(
    'lunara_review_pair_with_layout',
    'lunara_review_pair_with_text_depth',
    'lunara_review_pair_with_mobile_stack',
    'lunara_review_pair_with_image_focus'
)

$numberKeys = @(
    'lunara_review_pair_with_columns',
    'lunara_review_pair_with_thumb_width'
)

foreach ($key in $selectKeys) {
    Assert-True ($controlDesk -match [regex]::Escape("'$key'")) "Pair It With controls must define the $key select control."
    Assert-True ($frontend -match [regex]::Escape("'$key'")) "Pair It With public CSS must read the $key setting."
}

foreach ($key in $numberKeys) {
    Assert-True ($controlDesk -match [regex]::Escape("'$key'")) "Pair It With controls must define the $key numeric control."
    Assert-True ($frontend -match [regex]::Escape("'$key'")) "Pair It With public CSS must read the $key setting."
}

foreach ($option in @(
    'contained',
    'wide',
    'feature',
    'tight',
    'balanced',
    'full',
    'poster-led',
    'center-center',
    'center-top',
    'center-bottom',
    'left-center',
    'right-center'
)) {
    Assert-True ($controlDesk -match [regex]::Escape("'$option'")) "Pair It With controls must support the $option option."
}

Assert-True ($controlDesk -match 'function\s+lunara_control_desk_review_pair_with_select_specs') 'Pair It With controls must define select specs.'
Assert-True ($controlDesk -match 'function\s+lunara_control_desk_review_pair_with_number_specs') 'Pair It With controls must define number specs.'
Assert-True ($controlDesk -match 'function\s+lunara_control_desk_render_review_pair_with_precision_controls') 'Theme Studio must render a Pair It With precision subsection.'
Assert-True ($controlDesk -match 'lunara_control_desk_render_review_pair_with_precision_controls\(\)') 'Review Single Studio must call the Pair It With precision renderer.'
Assert-True ($controlDesk -match 'Pair It With Precision') 'Review Single Studio must label the focused Pair It With precision layer.'
Assert-True ($controlDesk -match 'lunara_review_pair_with_select') 'Pair It With select controls must save through the Review Single form.'
Assert-True ($controlDesk -match 'lunara_review_pair_with_number') 'Pair It With numeric controls must save through the Review Single form.'
Assert-True ($controlDesk -match 'lunara_control_desk_review_pair_with_clamp_number') 'Pair It With numeric controls must clamp server-side.'
Assert-True ($controlDesk -match 'lunara_control_desk_apply_review_single_values[\s\S]*lunara_control_desk_review_pair_with_select_specs') 'Review Single presets must apply Pair It With select values.'
Assert-True ($controlDesk -match 'lunara_control_desk_apply_review_single_values[\s\S]*lunara_control_desk_review_pair_with_number_specs') 'Review Single presets must apply Pair It With number values.'
Assert-True ($controlDesk -match 'lunara_control_desk_review_single_comparison_specs[\s\S]*lunara_review_pair_with_layout') 'Review Single preset comparison must include Pair It With layout.'
Assert-True ($controlDesk -match 'lunara_control_desk_review_single_comparison_specs[\s\S]*lunara_review_pair_with_thumb_width') 'Review Single preset comparison must include Pair It With thumb width.'
Assert-True ($controlDesk -match 'lunara_control_desk_review_single_key_label[\s\S]*lunara_control_desk_review_pair_with_select_specs') 'Review Single source pills must use Pair It With select labels.'
Assert-True ($controlDesk -match 'lunara_control_desk_review_single_key_label[\s\S]*lunara_control_desk_review_pair_with_number_specs') 'Review Single source pills must use Pair It With number labels.'
Assert-True ($controlDesk -match "'lunara_review_pair_with_layout'\s*=>\s*array\([\s\S]*?'default'\s*=>\s*'wide'") 'Pair It With layout must default to a wide editorial chamber.'
Assert-True ($controlDesk -match "'lunara_review_pair_with_columns'\s*=>\s*array\([\s\S]*?'default'\s*=>\s*1") 'Pair It With columns must default to one readable editorial column.'
Assert-True ($controlDesk -match "'editorial-balance'[\s\S]*?'lunara_review_pair_with_layout'\s*=>\s*'wide'[\s\S]*?'lunara_review_pair_with_columns'\s*=>\s*1") 'Editorial Balance preset must keep Pair It With wide and readable.'
Assert-True ($controlDesk -notmatch '<textarea[^>]+lunara_review_pair') 'Pair It With controls must not expose raw CSS textareas.'

foreach ($preset in @(
    'editorial-balance',
    'cinematic-feature',
    'compact-dispatch',
    'spoiler-shield'
)) {
    Assert-True ($controlDesk -match [regex]::Escape("'$preset'")) "Review Single preset $preset must remain defined."
}

foreach ($variable in @(
    '--lunara-review-pair-with-max-width',
    '--lunara-review-pair-with-columns',
    '--lunara-review-pair-with-thumb-width',
    '--lunara-review-pair-with-note-clamp',
    '--lunara-review-pair-with-image-focus'
)) {
    Assert-True ($frontend.Contains($variable)) "Pair It With public CSS must emit $variable."
}

foreach ($selector in @(
    'body.single-review .lunara-review-single-debrief--pairings',
    'body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-list--pairings',
    'body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-pairing',
    'body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-thumb',
    'body.single-review .lunara-review-single-debrief--pairings .lunara-debrief-note'
)) {
    Assert-True ($frontend.Contains($selector)) "Pair It With public CSS must stay scoped to $selector."
}

Assert-True ($frontend -match 'function\s+lunara_output_review_pair_it_with_controls_css') 'Pair It With controls must emit a named public CSS function.'
Assert-True ($frontend -match 'add_action\(\s*''wp_head''\s*,\s*''lunara_output_review_pair_it_with_controls_css''') 'Pair It With controls must hook public CSS through wp_head.'
Assert-True ($frontend -match 'is_singular\(\s*''review''\s*\)') 'Pair It With CSS must stay scoped to Review singular routes.'
Assert-True ($frontend -match 'lunara_get_review_single_preview_preset_values') 'Pair It With CSS must honor existing admin-only Review Single preview presets.'
Assert-True ($frontend -match 'object-position:\s*var\(--lunara-review-pair-with-image-focus\)') 'Pair It With image focus must map to object-position.'
Assert-True ($frontend -match 'line-clamp:\s*var\(--lunara-review-pair-with-note-clamp\)') 'Pair It With text depth must use a bounded line clamp.'
Assert-True ($frontend -match 'max-width:\s*var\(--lunara-review-pair-with-max-width\)') 'Pair It With layout must control module width.'
Assert-True ($frontend -match '@media\s*\(max-width:\s*680px\)[\s\S]*lunara-review-single-debrief--pairings') 'Pair It With mobile stacking must have a mobile-specific rule.'

Assert-True ($singleReview -match 'lunara-review-single-debrief--pairings') 'Single Review template must preserve Pair It With rendering.'
Assert-True ($singleReview -match 'lunara_split_review_debrief_block') 'Single Review template must keep Debrief split source.'

Write-Host 'Review Pair It With controls contract passed.'
