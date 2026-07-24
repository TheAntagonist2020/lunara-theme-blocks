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
$loader      = Get-Content -LiteralPath (Join-Path $root 'functions-loader.php') -Raw
$controlDesk = Get-Content -LiteralPath (Join-Path $root 'inc\control-desk.php') -Raw
$automation  = Get-Content -LiteralPath (Join-Path $root 'inc\control-desk-automation.php') -Raw
$adminCss    = Get-Content -LiteralPath (Join-Path $root 'assets\css\lunara-control-desk.css') -Raw

Assert-True ($loader -match "is_admin\(\)[\s\S]*require_once\s+\`$lunara_inc\s*\.\s*'control-desk-automation\.php'") 'Automation UI must load only in WordPress admin.'
Assert-True ($controlDesk -match "'automation'\s*=>\s*__\(\s*'Automation'") 'Control Desk must expose a first-class Automation tab.'
Assert-True ($controlDesk -match "case\s+'automation':[\s\S]*lunara_control_desk_render_automation_tab") 'Automation tab must render its dedicated module.'
Assert-True ($automation -match 'function\s+lunara_control_desk_render_automation_tab') 'Automation renderer must exist.'
Assert-True ($automation -match 'Lunara_Journal_Automation::admin_snapshot') 'Automation tab must read Foundation-owned state.'
Assert-True ($automation -match "current_user_can\(\s*'manage_options'\s*\)") 'Private automation data must be restricted to administrators.'
Assert-True ($automation -match 'Morning Desk') 'Automation tab must expose Morning Desk.'
Assert-True ($automation -match 'Run Lunara') 'Automation tab must expose the controlled Dispatch workflow.'
Assert-True ($automation -match 'Capture Idea') 'Automation tab must describe the private idea-capture workflow.'
Assert-True ($automation -match 'Source Radar') 'Automation tab must describe the private research-intake workflow.'
Assert-True ($automation -match 'Screening Follow-Up') 'Automation tab must describe screening follow-up.'
Assert-True ($automation -match 'Needs Attention') 'Automation tab must describe actionable alerts.'
Assert-True ($automation -notmatch 'token_hash|LUNARA_IFTTT_WEBHOOK_KEY') 'Theme UI must never read or render credential material.'
Assert-True ($adminCss -match '\.lunara-control-desk-automation-grid') 'Automation tab must have admin-only responsive layout styling.'

Write-Output 'Control Desk automation contract passed.'
