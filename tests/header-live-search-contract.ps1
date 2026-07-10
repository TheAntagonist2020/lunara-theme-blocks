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

$header = Read-ThemeFile 'header.php'
$styles = Read-ThemeFile 'style.css'
$frontend = Read-ThemeFile 'inc/frontend.php'

Assert-True ($header.Contains("__( 'Search Lunara', 'lunara-film' )")) 'The compact site search placeholder must remain present.'
Assert-True ($header.Contains("__( 'Search the Oscar Ledger', 'lunara-film' )")) 'The compact Oscars search placeholder must remain present.'
Assert-True ($header -notmatch 'Search Oscar titles, people, ceremonies, categories') 'The Oscars header must not restore the clipped long placeholder.'
Assert-True ($styles -match '\.lunara-live-search-panel\[hidden\]\s*\{[^}]*display:\s*none\s*!important;') 'Hidden live-search panels must be removed from layout.'
Assert-True ($frontend -match 'panel\.hidden\s*=\s*true') 'The live-search script must close its suggestion panel with the hidden state.'
Assert-True ($frontend -match 'panel\.hidden\s*=\s*false') 'The live-search script must open its suggestion panel when results exist.'

Write-Host 'Header live-search contract passed.'
