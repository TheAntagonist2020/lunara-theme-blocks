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
$reviewRendering = Read-ThemeFile 'inc/review-rendering.php'
$reviewComponents = Read-ThemeFile 'assets/css/lunara-review-components.css'

foreach ($key in @(
    'lunara_review_archive_image_focus',
    'lunara_review_rail_image_focus',
    'lunara_review_related_image_focus',
    'lunara_review_feature_image_focus'
)) {
    Assert-True ($controlDesk -match [regex]::Escape("'$key'")) "Theme Studio must define the $key focus control."
    Assert-True ($frontend -match [regex]::Escape("'$key'")) "Public CSS must read the $key focus control."
}

foreach ($option in @(
    'center-center',
    'center-top',
    'center-bottom',
    'left-center',
    'right-center'
)) {
    Assert-True ($controlDesk -match [regex]::Escape("'$option'")) "Review card focus controls must support the $option option."
    Assert-True ($frontend -match [regex]::Escape("'$option'")) "Review card focus CSS must map the $option option."
}

Assert-True ($controlDesk -match 'function\s+lunara_control_desk_review_card_image_focus_specs') 'Theme Studio must define Review card image focus specs.'
Assert-True ($controlDesk -match 'function\s+lunara_control_desk_review_card_image_focus_value') 'Theme Studio must validate Review card image focus values.'
Assert-True ($controlDesk -match 'function\s+lunara_control_desk_save_review_card_image_focus_controls') 'Theme Studio must define a Review card image focus save handler.'
Assert-True ($controlDesk -match "admin_post_lunara_save_review_card_image_focus_controls") 'Theme Studio must register the admin-post save action.'
Assert-True ($controlDesk -match "check_admin_referer\(\s*'lunara_save_review_card_image_focus_controls'") 'Review card image focus save handler must verify a nonce.'
Assert-True ($controlDesk -match "current_user_can\(\s*'edit_theme_options'\s*\)") 'Review card image focus save handler must require edit_theme_options.'
Assert-True ($controlDesk -match 'lunara_review_card_image_focus_select') 'Review card image focus controls must save through a scoped POST group.'
Assert-True ($controlDesk -match 'Review Card Image Focus') 'Theme Studio must render a labeled Review Card Image Focus panel.'
Assert-True ($controlDesk -notmatch '<textarea[^>]+lunara_review_[^>]*image_focus') 'Review card image focus controls must not expose raw CSS textareas.'

foreach ($preview in @(
    "/reviews/",
    "/reviews/sinners-2025/",
    "/reviews/bugonia-the-full-spoiler/",
    "/"
)) {
    Assert-True ($controlDesk.Contains($preview)) "Review card image focus panel must include preview link for $preview."
}

Assert-True ($frontend -match 'function\s+lunara_review_card_image_focus_position') 'Frontend must map Review focus tokens to safe object-position values.'
Assert-True ($frontend -match 'function\s+lunara_output_review_card_image_focus_css') 'Frontend must emit scoped Review card image focus CSS.'
Assert-True ($frontend -match "add_action\(\s*'wp_head'\s*,\s*'lunara_output_review_card_image_focus_css'") 'Review card focus CSS must be registered on wp_head.'
Assert-True ($frontend -match '--lunara-review-archive-image-focus') 'Review archive focus CSS variable must be emitted.'
Assert-True ($frontend -match '--lunara-review-rail-image-focus') 'Review rail focus CSS variable must be emitted.'
Assert-True ($frontend -match '--lunara-review-related-image-focus') 'Review related focus CSS variable must be emitted.'
Assert-True ($frontend -match '--lunara-review-feature-image-focus') 'Review feature focus CSS variable must be emitted.'
Assert-True ($reviewComponents -match 'object-position:\s*var\(--lunara-review-archive-image-focus\)') 'Archive Review card images must use the archive focus variable.'
Assert-True ($reviewComponents -match 'object-position:\s*var\(--lunara-review-rail-image-focus\)') 'Companion rail Review card images must use the rail focus variable.'
Assert-True ($reviewComponents -match 'object-position:\s*var\(--lunara-review-related-image-focus\)') 'Related Review card images must use the related focus variable.'
Assert-True ($reviewComponents -match 'object-position:\s*var\(--lunara-review-feature-image-focus\)') 'Feature Review images must use the feature focus variable.'
Assert-True ($frontend -match 'is_post_type_archive\(\s*''review''\s*\).*is_page\(\s*''reviews''\s*\).*is_singular\(\s*''review''\s*\).*is_front_page\(\)' ) 'Review card focus CSS must stay scoped to Reviews, single Review, and homepage contexts.'

Assert-True ($reviewRendering -match '\$has_media\s*=\s*\$has_thumb_html\s*\|\|\s*\$use_fallback_bg') 'Review rendering must keep a real-media guard before card poster wrappers.'
Assert-True ($reviewRendering -match 'if\s*\(\s*\$has_media\s*\)\s*:' ) 'Review rendering must only print poster wrappers when media exists.'
Assert-True ($reviewRendering -match 'is-text-led') 'Review rendering must keep text-led state for cards without media.'
Assert-True ($reviewRendering -match 'lunara-score-badge-inline') 'Review rendering must keep inline score output for text-led cards.'

Write-Host 'Theme Studio Review card image focus controls contract passed.'
