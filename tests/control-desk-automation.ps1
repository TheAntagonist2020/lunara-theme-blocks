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
$runtimeTest = Join-Path $root 'tests\control-desk-automation-runtime.php'

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

# Dispatch 3.2.5 telemetry remains private, bounded, and backward-compatible.
Assert-True ($automation -match 'function\s+lunara_control_desk_render_dispatch_telemetry') 'Automation tab must have a dedicated Dispatch telemetry renderer.'
Assert-True ($automation -match 'lunara_control_desk_render_dispatch_telemetry\(\s*\$dispatch\s*\)') 'Automation tab must render the Foundation-owned Dispatch snapshot.'
foreach ($field in @(
    'runtime',
    'provider',
    'model',
    'max_output_tokens',
    'source_budget',
    'usage_reported',
    'requested_model',
    'effective_model',
    'processed_source_items',
    'deferred_source_items',
    'fallback_used',
    'source_packet_drafts',
    'error_code',
    'input_tokens',
    'cached_input_tokens',
    'output_tokens',
    'estimated_cost_usd'
)) {
    Assert-True ($automation -match [regex]::Escape("['$field']")) "Dispatch telemetry must consume safe Foundation field: $field"
}
Assert-True ($automation -match 'Cost guard override applied') 'Telemetry must identify a requested-to-effective model override.'
Assert-True ($automation -match 'Safe source packets') 'Telemetry must identify the source-packet fallback state.'
Assert-True ($automation -match 'Not reported') 'Telemetry must degrade safely when Foundation 1.2.10 omits new fields.'
Assert-True ($automation -match "array_key_exists\(\s*'usage_reported'\s*,\s*\`$last_run\s*\)") 'Token telemetry must honor Foundation usage_reported before showing zero values.'
Assert-True ($automation -match "isset\(\s*\`$last_run\['estimated_cost_usd'\]\s*\)\s*\?\s*\`$last_run\['estimated_cost_usd'\]\s*:\s*null") 'Cost telemetry must pass null, not zero, when no estimate was reported.'
Assert-True ($automation -match "array_key_exists\(\s*'deferred_source_items'\s*,\s*\`$last_run\s*\)[^;]+null\s*!==\s*\`$last_run\['deferred_source_items'\]") 'Deferred source telemetry must preserve the distinction between zero and not reported.'
Assert-True ($automation -notmatch '\$last_run\[''response_id''\]|\$last_run\[''prompt''\]|\$last_run\[''api_key''\]') 'Control Desk must not consume response IDs, prompts, or API keys.'
foreach ($selector in @(
    '.lunara-control-desk-dispatch-telemetry',
    '.lunara-control-desk-telemetry-grid',
    '.lunara-control-desk-telemetry-card',
    '.lunara-control-desk-telemetry-message'
)) {
    Assert-True ($adminCss -match [regex]::Escape($selector)) "Control Desk CSS must style $selector."
}

$runtimeOutput = & php $runtimeTest 2>&1
Assert-True ($LASTEXITCODE -eq 0) "Dispatch telemetry runtime regression failed:`n$($runtimeOutput -join "`n")"
Assert-True (($runtimeOutput -join "`n") -match 'runtime regression passed') 'Dispatch telemetry runtime regression must complete.'

Write-Output 'Control Desk automation contract passed.'
