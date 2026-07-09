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
$adminCss = Read-ThemeFile 'assets/css/lunara-control-desk.css'

Assert-True ($controlDesk -match 'name="lunara_homepage_apply_preset"') 'Homepage Studio form must expose a hidden apply-preset field.'
Assert-True ($controlDesk -match 'lunara-control-desk-homepage-apply-button') 'Homepage preset cards must render a stable Apply Package button class.'
Assert-True ($controlDesk -match 'type="submit"[\s\S]+name="lunara_homepage_apply_preset"[\s\S]+value="<\?php echo esc_attr\( \$preset_key \); \?>"') 'Apply buttons must submit the selected preset key through the existing form.'

foreach ($preset in @(
    'trade-front-door',
    'journal-desk-day',
    'oscars-signature',
    'criticism-showcase'
)) {
    Assert-True ($controlDesk -match [regex]::Escape("'$preset'")) "Homepage apply action must preserve the $preset preset."
}

Assert-True ($controlDesk -match '\$apply_preset_key\s*=\s*isset\(\s*\$_POST\[''lunara_homepage_apply_preset''\]') 'Save handler must read the apply-preset POST value.'
Assert-True ($controlDesk -match 'sanitize_key\(\s*wp_unslash\(\s*\$_POST\[''lunara_homepage_apply_preset''\]\s*\)\s*\)') 'Apply-preset key must be unslashed and sanitized.'
Assert-True ($controlDesk -match '\$apply_preset\s*=\s*isset\(\s*\$homepage_presets\[\s*\$apply_preset_key\s*\]\s*\)') 'Save handler must validate the apply-preset key against known presets.'
Assert-True ($controlDesk -match '\$apply_values\s*=\s*isset\(\s*\$apply_preset\[''values''\]\s*\)') 'Save handler must extract validated preset values.'
Assert-True ($controlDesk -match 'isset\(\s*\$apply_values\[\s*\$key\s*\]\s*\)\s*\?\s*sanitize_key\(\s*\$apply_values\[\s*\$key\s*\]\s*\)') 'Preset values must override homepage select controls only after validation.'
Assert-True ($controlDesk -match 'isset\(\s*\$apply_values\[''lunara_home_section_order_preset''\]\s*\)') 'Preset values must override section order through the existing order preset key.'
Assert-True ($controlDesk -match 'homepage_preset_applied') 'Homepage apply action must redirect with a dedicated applied notice.'
Assert-True ($controlDesk -notmatch 'admin_post_lunara_save_homepage_preset') 'Homepage apply action must not introduce a separate save handler.'
Assert-True ($controlDesk -notmatch 'lunara-homepage-compare') 'Homepage apply action must not introduce a public comparison query variable.'
Assert-True ($controlDesk -notmatch '<textarea[^>]+(?:id|name)="[^"]*(?:css|style)[^"]*"') 'Homepage apply action must not expose raw CSS textareas.'
Assert-True ($adminCss -match '\.lunara-control-desk-homepage-apply-button') 'Control Desk CSS must style the Homepage apply button.'

Write-Host 'Homepage preset apply action contract passed.'
