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

$functions = Read-ThemeFile 'functions.php'
$controlDesk = Read-ThemeFile 'inc/control-desk.php'
$style = Read-ThemeFile 'style.css'

Assert-True ($style -match 'Version:\s*3\.2\.47') 'Seasonal Oscar forecast must remain intact in Theme 3.2.47.'
Assert-True ($functions -match "'best casting'\s*=>\s*'CASTING'") 'Oscar Picks must link the new Casting category into the Ledger.'
Assert-True ($functions -match "'Best Makeup and Hairstyling'") 'Oscar Picks must expose the full craft forecast taxonomy.'
Assert-True ($functions -match 'function\s+lunara_home_oscar_picks_default_ceremony_year') 'Oscar Picks must derive a season instead of hard-coding one ceremony.'
Assert-True ($functions -match "current_month\s*>=\s*4\s*\?\s*\`$current_year\s*\+\s*1") 'The next awards season must activate after March.'
Assert-True ($functions -match 'function\s+lunara_oscar_ceremony_ordinal_from_year') 'Oscar Picks must map ceremony years to Academy Awards ordinals.'
Assert-True ($functions -match 'Road to the %s Academy Awards') 'The public rail must frame the active upcoming ceremony.'
Assert-True ($functions -notmatch 'Predicted winners[^\r\n]+98th Academy Awards') 'The public rail must not retain the completed 98th-season heading.'
Assert-True ($functions -match "'ceremony_year'\s*=>\s*\(int\)\s*\`$args\['ceremony_year'\]") 'The public query must filter cards to the active ceremony.'
Assert-True ($functions -match 'data-lunara-oscar-ceremony-year') 'The public surface must expose its active ceremony for verification.'
Assert-True ($functions -match 'lunara-oscar-pick-card-rationale') 'Each forecast card must be able to explain the editorial reasoning behind the pick.'
Assert-True ($style -match 'var\(--lunara-font-display') 'Oscar forecast typography must use Lunara approved font tokens.'

foreach ($status in @('front_runner', 'contender', 'watchlist', 'predicted', 'won', 'lost')) {
    Assert-True ($functions -match "'$status'") "Oscar Pick status '$status' must remain supported."
}

foreach ($field in @(
    'lunara_home_oscar_picks_ceremony_year',
    'lunara_home_oscar_picks_kicker',
    'lunara_home_oscar_picks_heading',
    'lunara_home_oscar_picks_summary',
    'lunara_home_oscar_picks_cta_text'
)) {
    Assert-True ($controlDesk -match $field) "Homepage Studio must expose and save '$field'."
}

Assert-True ($controlDesk -match "'_lunara_pick_ceremony_year'\s*,\s*true\s*\)\s*===\s*\(int\)\s*\`$year") 'Stored manual picks must be constrained to the active ceremony.'
Assert-True ($controlDesk -match 'lunara_control_desk_get_homepage_oscar_pick_candidates\(\s*\$ordered_ids,\s*24,\s*\$ceremony_year\s*\)') 'Control Desk candidates must be scoped to the active ceremony.'

Write-Host 'Homepage Oscar Picks seasonal forecast contract passed.'
