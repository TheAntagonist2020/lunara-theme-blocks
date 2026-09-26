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
$headerCommand = Read-ThemeFile 'inc/header-command.php'
$styles = Read-ThemeFile 'style.css'
$frontend = Read-ThemeFile 'inc/frontend.php'
$liveSearch = Read-ThemeFile 'assets/js/lunara-live-search.js'

Assert-True ($header.Contains("__( 'Search Lunara', 'lunara-film' )")) 'The compact site search placeholder must remain present.'
Assert-True ($header.Contains("__( 'Search the Oscar Ledger', 'lunara-film' )")) 'The compact Oscars search placeholder must remain present.'
Assert-True ($header -notmatch 'Search Oscar titles, people, ceremonies, categories') 'The Oscars header must not restore the clipped long placeholder.'
Assert-True ($styles -match '\.lunara-live-search-panel\[hidden\]\s*\{[^}]*display:\s*none\s*!important;') 'Hidden live-search panels must be removed from layout.'
Assert-True ($frontend -match 'panel\.hidden\s*=\s*true') 'The live-search script must close its suggestion panel with the hidden state.'
Assert-True ($frontend -match 'panel\.hidden\s*=\s*false') 'The live-search script must open its suggestion panel when results exist.'
Assert-True ($headerCommand.Contains('class="lunara-header-search-icon"')) 'The header search control must use the compact search icon.'
Assert-True ($headerCommand -notmatch '<kbd[^>]*data-lunara-shortcut-key') 'The header search control must not render a browser-styled shortcut badge.'
Assert-True ($headerCommand -notmatch 'aria-keyshortcuts') 'The header search control must behave like ordinary search without command-palette shortcut metadata.'
Assert-True ($headerCommand -match '(?s)\.lunara-header-search\s*\{[^}]*width:\s*42px;[^}]*height:\s*42px;') 'The header search control must remain a stable circular 42px action.'
Assert-True ($frontend -notmatch 'data-lunara-shortcut-key|Ctrl K|⌘K') 'Homepage search must not display command-palette shortcut labels.'
Assert-True ($liveSearch -notmatch 'event\.(metaKey|ctrlKey)|event\.key\s*===\s*''/''') 'Site search must open from ordinary controls rather than global command-palette shortcuts.'

Write-Host 'Header live-search contract passed.'
