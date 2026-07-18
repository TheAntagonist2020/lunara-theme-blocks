$ErrorActionPreference = 'Stop'

function Assert-True {
    param(
        [bool] $Condition,
        [string] $Message
    )

    if (-not $Condition) {
        throw $Message
    }
}

$root = Split-Path -Parent $PSScriptRoot
$style = Get-Content -LiteralPath (Join-Path $root 'style.css') -Raw
$shell = Get-Content -LiteralPath (Join-Path $root 'assets\css\lunara-shell.css') -Raw
$guardrails = Get-Content -LiteralPath (Join-Path $root 'assets\css\lunara-public-guardrails.css') -Raw

Assert-True ($style -match 'Version:\s*3\.2\.15') 'Journal typography contract must remain intact in Theme 3.2.15.'
Assert-True ($shell -match 'body\.post-type-archive-journal \.lunara-archive-page,[\s\S]*?font-family:\s*var\(--lunara-font-body') 'Journal pages must retain Tiempos Text through the body token.'
Assert-True ($shell -match 'body\.post-type-archive-journal \.lunara-archive-hero-title[\s\S]*?font-family:\s*var\(--lunara-font-glamour') 'Journal route titles must use the Canela glamour token.'
Assert-True ($shell -match 'body\.post-type-archive-journal \.lunara-journal-filter-label,[\s\S]*?body\.post-type-archive-journal \.lunara-journal-filter-count,[\s\S]*?font-family:\s*var\(--lunara-font-label') 'Journal lane labels and counts must retain the Tiempos label token.'
Assert-True ($shell -match 'body\.post-type-archive-journal \.lunara-archive-empty p[\s\S]*?font-family:\s*var\(--lunara-font-display') 'Journal empty-state emphasis must use the Tiempos Headline display token.'
Assert-True ($shell -match 'body\.post-type-archive-journal \.lunara-archive-empty p[\s\S]*?font-weight:\s*700') 'Journal display emphasis must reuse the already-loaded Tiempos Headline bold face.'
Assert-True ($shell -match 'body\.post-type-archive-journal \.lunara-journal-archive-deskbar span,[\s\S]*?font-family:\s*var\(--lunara-font-body') 'Journal status values must not fall through to runtime Georgia stacks.'
Assert-True ($shell -match 'body:not\(\.wp-admin\) \.lunara-site-footer \.lunara-footer-nav-col a,[\s\S]*?font-family:\s*var\(--lunara-font-label') 'Footer navigation must retain the Tiempos label token.'
Assert-True ($shell -notmatch 'body\.post-type-archive-journal \.lunara-archive-page,[\s\S]{0,160}?font-family:\s*Georgia') 'Journal route family must not override its house body token with raw Georgia.'
Assert-True ($shell -notmatch 'body\.post-type-archive-journal[^\{]*\{[^\}]*--lunara-font-signature') 'Journal archive must not load GT Sectra for minor or repeated UI text.'
Assert-True ($shell -match 'body\.single-journal \.lunara-review-single-title[\s\S]*?font-family:\s*var\(--lunara-font-glamour') 'Journal single titles must share the Canela route-family voice.'
Assert-True ($shell -match 'body\.single-journal \.lunara-review-single-kicker[\s\S]*?font-family:\s*var\(--lunara-font-label') 'Journal single kickers must use the Tiempos label token.'
Assert-True ($shell -match 'body\.single-journal \.lunara-review-single-content,[\s\S]*?font-family:\s*var\(--lunara-font-body') 'Journal single reading copy must use Tiempos Text.'
Assert-True ($shell -match 'body\.single-journal \.lunara-review-single-content h2,[\s\S]*?font-family:\s*var\(--lunara-font-display') 'Journal single subheads must use Tiempos Headline.'
Assert-True ($shell -match 'body\.single-journal \.lunara-review-single-rail-actions \.lunara-btn[\s\S]*?font-family:\s*var\(--lunara-font-label') 'Journal single rail controls must use the Tiempos label token.'
Assert-True ($guardrails -match 'body\.post-type-archive-journal \.lunara-journal-archive-retention-card strong[\s\S]*?font-family:\s*var\(--lunara-font-display[\s\S]*?font-weight:\s*700') 'Journal retention titles must reuse the loaded Tiempos Headline bold face.'
Assert-True ($guardrails -notmatch 'body\.post-type-archive-journal \.lunara-journal-archive-retention-card strong[\s\S]{0,220}?--theme-font-family') 'Late public guardrails must not restore Georgia on Journal retention titles.'
Assert-True ($shell -match 'body\.post-type-archive-journal \.lunara-journal-archive-deskbar,[\s\S]*?body\.post-type-archive-journal \.lunara-journal-archive-grid \{\s*transform:\s*none\s*!important;') 'Mobile Journal lanes must remain centered without a horizontal translation.'
Assert-True ($shell -match 'body\.post-type-archive-journal \.lunara-journal-archive-filters \{\s*justify-self:\s*center\s*!important;') 'Mobile Journal filter groups must center within the archive lane.'
Assert-True ($shell -notmatch 'translateX\(-32px\)') 'The obsolete mobile Journal translation must not return.'

Write-Output 'Journal taxonomy typography and alignment contract passed.'
