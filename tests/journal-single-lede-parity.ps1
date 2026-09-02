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

# Theme 3.2.57 — Journal single lede parity.
#
# On a journal entry the first body paragraph is the hero deck repeated, so the
# enlarged opening-paragraph treatment that reviews keep produced a 28% size
# step between paragraph one and paragraph two on a three-paragraph dispatch.
# Readers saw it as a second typeface. Reviews retain the lede; journal entries
# take the single-journal guardrail body clamp for every paragraph.

$root = Split-Path -Parent $PSScriptRoot
$style = Get-Content -LiteralPath (Join-Path $root 'style.css') -Raw
$shell = Get-Content -LiteralPath (Join-Path $root 'assets\css\lunara-shell.css') -Raw
$frontend = Get-Content -LiteralPath (Join-Path $root 'inc\frontend.php') -Raw

Assert-True ($style -match 'Version:\s*3\.2\.57') 'Journal lede parity must ship in Theme 3.2.57.'

# The review lede survives, on its own, with the size it always had.
$reviewLede = [regex]::Matches($style, '(?m)^body\.single-review \.lunara-review-single-content > p:first-of-type \{[^}]*?font-size:\s*clamp\(1\.1rem, 0\.98rem \+ 0\.4vw, 1\.28rem\) !important;[^}]*\}')
Assert-True ($reviewLede.Count -eq 1) 'Reviews must keep exactly one enlarged opening-paragraph rule in style.css.'
Assert-True ($reviewLede[0].Value -notmatch 'single-journal') 'The review lede rule must not carry a journal selector.'

# No journal selector may size, colour, or lead the first paragraph with !important anywhere the theme ships CSS.
$journalLedePattern = 'body\.single-journal \.lunara-review-single-content\s*>\s*p:first-of-type'
Assert-True ($style -notmatch $journalLedePattern) 'style.css must not single out the first journal paragraph.'
Assert-True ($shell -notmatch $journalLedePattern) 'lunara-shell.css must not single out the first journal paragraph.'
Assert-True ($frontend -notmatch $journalLedePattern) 'inc/frontend.php inline guardrails must not single out the first journal paragraph.'

# The rule every journal paragraph now shares must still exist in the inline guardrail, with its !important clamp.
Assert-True ($frontend -match 'body\.single-journal \.lunara-review-single-content p\{[^}]*?font-size:clamp\(1rem,1\.05vw,1\.12rem\)!important;') 'The single-journal guardrail must keep the shared body paragraph clamp.'

# The only remaining generic first-paragraph rule is not !important, so the guardrail clamp wins it for journal entries.
$genericLede = [regex]::Match($style, '(?m)^\.lunara-review-single-content > p:first-of-type \{[^}]*\}')
Assert-True ($genericLede.Success) 'The shared non-important opening-paragraph rule must remain for reviews.'
Assert-True ($genericLede.Value -notmatch '!important') 'The shared opening-paragraph rule must stay non-important so the journal guardrail clamp outranks it.'

Write-Host 'Theme 3.2.57 journal single lede parity contract passed: reviews keep the lede, journal paragraphs share one body clamp.'
