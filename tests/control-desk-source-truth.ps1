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

$root        = Split-Path -Parent $PSScriptRoot
$controlDesk = Get-Content -LiteralPath (Join-Path $root 'inc\control-desk.php') -Raw

Assert-True ($controlDesk -notmatch [regex]::Escape('G:\lunara-backups\work')) 'Control Desk must not present retired local G: work trees as live source truth.'
Assert-True ($controlDesk -notmatch [regex]::Escape('/home/151589083/htdocs/wp-content/themes/lunara-theme-blocks-20260513-2300')) 'Control Desk must not hard-code a production theme path as deploy identity.'
Assert-True ($controlDesk -notmatch 'main\s*@\s*(?:0c93adb|d88777b|2c2dbc7|75aae10|1b041f3)') 'Control Desk must not present stale hard-coded commit anchors.'

foreach ($repo in @(
    'TheAntagonist2020/lunara-theme-blocks',
    'TheAntagonist2020/lunara-plugin-core',
    'TheAntagonist2020/lunara-plugin-oscars-ledger',
    'TheAntagonist2020/lunara-plugin-dispatch',
    'TheAntagonist2020/lunara-plugin-imdb-guard',
    'TheAntagonist2020/lunara-plugin-ai-assistant-classic'
)) {
    Assert-True ($controlDesk -match [regex]::Escape($repo)) "Control Desk source map must identify canonical repository: $repo"
}

Assert-True ($controlDesk -match 'function\s+lunara_control_desk_deployed_file_hash') 'Control Desk must calculate a live deployed file fingerprint.'
Assert-True ($controlDesk -match "hash_file\(\s*'sha256'") 'Live fingerprints must use SHA256.'
Assert-True ($controlDesk -match 'function\s+lunara_control_desk_deployed_commit') 'Control Desk must read optional deployment commit metadata without hard-coding it.'
Assert-True ($controlDesk -match 'function\s+lunara_control_desk_source_control_card') 'Source-control cards must share the live version/commit/hash formatter.'
Assert-True ($controlDesk -match 'lunara_control_desk_plugin_file_version') 'Plugin source truth must read deployed plugin versions.'
Assert-True ($controlDesk -match 'Live fingerprint') 'Deploy Truth must display the served theme hash.'
Assert-True ($controlDesk -match 'deployed commit not recorded') 'Control Desk must state honestly when deployment commit metadata is unavailable.'

Write-Output 'Control Desk source truth contract passed.'
