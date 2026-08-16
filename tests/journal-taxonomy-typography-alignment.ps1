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
$route = Get-Content -LiteralPath (Join-Path $root 'assets\css\lunara-journal-archive.css') -Raw
$tokens = Get-Content -LiteralPath (Join-Path $root 'inc\design-tokens.php') -Raw

Assert-True ($style -match 'Version:\s*3\.2\.49') 'Journal typography contract must remain intact in Theme 3.2.49.'
Assert-True ($style -match '--lunara-font-body:\s*"Tiempos Text"') 'The shipped body token must remain Tiempos Text.'
Assert-True ($tokens -match "'body'\s*=>\s*array\([\s\S]{0,300}?'default'\s*=>\s*'tiempos-text'") 'The editable body role must default to Tiempos Text.'
Assert-True ($route -match '#primary\.lunara-journal-archive-page\s*\{[\s\S]{0,420}?font-family:\s*var\(--lunara-font-body') 'The route-owned Journal page must retain the editable body token.'
Assert-True ($route -match '#primary\.lunara-journal-archive-page \.lunara-archive-hero-title\s*\{[\s\S]{0,180}?font-family:\s*var\(--lunara-font-glamour') 'Journal route titles must use the editable Canela glamour token.'
Assert-True ($route -match '#primary\.lunara-journal-archive-page \.lunara-journal-filter-label\s*\{[\s\S]{0,260}?font-family:\s*var\(--lunara-font-label') 'Journal filter framing must retain the editable label token.'
Assert-True ($route -match '#primary\.lunara-journal-archive-page \.lunara-journal-filter-pill,[\s\S]{0,520}?font-family:\s*var\(--lunara-font-label') 'Journal filter controls must retain the editable label token.'
Assert-True ($route -match '#primary\.lunara-journal-archive-page \.lunara-journal-filter-count\s*\{[\s\S]{0,180}?font-family:\s*inherit') 'Journal filter counts must inherit the tokenized pill typography.'
Assert-True ($route -match '#primary\.lunara-journal-archive-page \.lunara-archive-empty p\s*\{[\s\S]{0,180}?font-family:\s*var\(--lunara-font-display') 'Journal empty-state emphasis must use the editable display token.'
Assert-True ($route -match '#primary\.lunara-journal-archive-page \.lunara-archive-empty p\s*\{[\s\S]{0,260}?font-weight:\s*700') 'Journal display emphasis must reuse the already-loaded Tiempos Headline bold face.'
Assert-True ($route -match '#primary\.lunara-journal-archive-page > \.lunara-journal-archive-slot-deskbar span\s*\{[\s\S]{0,420}?font-family:\s*var\(--lunara-font-body') 'Journal status values must use the editable body token.'
Assert-True ($shell -match 'body:not\(\.wp-admin\) \.lunara-site-footer \.lunara-footer-nav-col a,[\s\S]*?font-family:\s*var\(--lunara-font-label') 'Footer navigation must retain the Tiempos label token.'
Assert-True ($route -notmatch 'font-family:\s*"(?:Tiempos|Canela)') 'Journal route typography must remain editable through shared font-role tokens, never hard-coded.'
Assert-True ($route -notmatch '--lunara-font-signature') 'Journal archive must not load GT Sectra for minor or repeated UI text.'
Assert-True ($shell -match 'body\.single-journal \.lunara-review-single-title[\s\S]*?font-family:\s*var\(--lunara-font-glamour') 'Journal single titles must share the Canela route-family voice.'
Assert-True ($shell -match 'body\.single-journal \.lunara-review-single-kicker[\s\S]*?font-family:\s*var\(--lunara-font-label') 'Journal single kickers must use the Tiempos label token.'
Assert-True ($shell -match 'body\.single-journal \.lunara-review-single-content,[\s\S]*?font-family:\s*var\(--lunara-font-body') 'Journal single reading copy must use Tiempos Text.'
Assert-True ($shell -match 'body\.single-journal \.lunara-review-single-content h2,[\s\S]*?font-family:\s*var\(--lunara-font-display') 'Journal single subheads must use Tiempos Headline.'
Assert-True ($shell -match 'body\.single-journal \.lunara-review-single-rail-actions \.lunara-btn[\s\S]*?font-family:\s*var\(--lunara-font-label') 'Journal single rail controls must use the Tiempos label token.'
Assert-True ($route -match '#primary\.lunara-journal-archive-page \.lunara-journal-archive-retention-card-link > strong\s*\{[\s\S]{0,240}?font-family:\s*var\(--lunara-font-display[\s\S]{0,180}?font-weight:\s*700') 'Journal retention titles must reuse the editable display token and loaded bold face.'
Assert-True ($guardrails -notmatch 'post-type-archive-journal|lunara-journal-archive') 'Late public guardrails must no longer own Journal route typography or geometry.'
Assert-True ($route -match '@media \(max-width:\s*620px\)[\s\S]*?#primary\.lunara-journal-archive-page \.lunara-journal-archive-filters,[\s\S]*?flex-wrap:\s*nowrap\s*!important;[\s\S]*?overflow-x:\s*auto\s*!important;') 'Mobile Journal filters must remain an intentional bounded scroller.'
Assert-True ($route -notmatch 'translateX\(-32px\)') 'The obsolete mobile Journal translation must not return.'

Write-Output 'Journal taxonomy typography and alignment contract passed.'
