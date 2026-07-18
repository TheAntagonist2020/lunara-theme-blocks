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
$journal = Get-Content -LiteralPath (Join-Path $root 'inc\journal-cpt.php') -Raw
$journalFamily = Get-Content -LiteralPath (Join-Path $root 'inc\journal-family.php') -Raw
$loader = Get-Content -LiteralPath (Join-Path $root 'functions-loader.php') -Raw
$single = Get-Content -LiteralPath (Join-Path $root 'single-journal.php') -Raw
$archive = Get-Content -LiteralPath (Join-Path $root 'archive-journal.php') -Raw
$controlDesk = Get-Content -LiteralPath (Join-Path $root 'inc\control-desk.php') -Raw
$related = Get-Content -LiteralPath (Join-Path $root 'inc\review-rendering.php') -Raw
$frontend = Get-Content -LiteralPath (Join-Path $root 'inc\frontend.php') -Raw
$style = Get-Content -LiteralPath (Join-Path $root 'style.css') -Raw
$fixture = Join-Path $PSScriptRoot 'fixtures\journal-foundation-integration.php'
$fixtureSource = Get-Content -LiteralPath $fixture -Raw

$json = & php $fixture
Assert-True ($LASTEXITCODE -eq 0) 'Journal Foundation PHP integration fixture failed.'
$result = $json | ConvertFrom-Json

foreach ($property in @(
    'foundation_active',
    'canonical_kicker',
    'legacy_kicker',
    'canonical_deck',
    'legacy_note',
    'canonical_section',
    'legacy_section',
    'canonical_source',
    'legacy_source',
    'canonical_featured',
    'canonical_normal_overrides',
    'legacy_featured',
    'image_source_isolated',
    'canonical_primary_terms',
    'legacy_primary_terms',
    'canonical_related_query',
    'legacy_related_query',
    'bounded_filter_terms',
    'active_filter_term_retained',
    'cpt_yielded',
    'cpt_fallback',
    'taxonomy_yielded',
    'taxonomy_fallback'
)) {
    Assert-True ([bool] $result.$property) "Journal integration contract failed: $property"
}

Assert-True ($journal -match "post_type_exists\(\s*'journal'\s*\)") 'Theme Journal CPT registration must yield to an existing owner.'
Assert-True ($journal -match "taxonomy_exists\(\s*'journal_type'\s*\)") 'Theme journal_type registration must yield when Foundation owns it.'
Assert-True ($journal -match "register_taxonomy_for_object_type\(\s*'journal_type'\s*,\s*'journal'\s*\)") 'Existing journal_type taxonomy must stay attached to Journal.'
Assert-True ($journal -match "'has_archive'\s*=>\s*'journal'") 'Fallback Journal archive must retain the /journal/ route.'
Assert-True ($journal -match "'slug'\s*=>\s*'journal-type'") 'Fallback journal_type terms must retain their public route.'
Assert-True ($archive -match "lunara_get_journal_archive_filter_terms\(\s*'journal_type',\s*8,") 'Journal archive must retain a bounded legacy taxonomy filter surface.'
Assert-True ($single -match "lunara_get_journal_field_value\([\s\S]*'journal_image_credit'") 'Journal single must consume canonical Foundation image credit.'
Assert-True ($single -match "'journal_image_alt'") 'Journal single must consume canonical Foundation image alt text.'
Assert-True ($controlDesk -match "lunara_get_journal_source_items") 'Control Desk must consume canonical Foundation source rows.'
Assert-True ($controlDesk -match "lunara_get_journal_field_value\([^\r\n]*'journal_deck'") 'Control Desk must prefer the canonical Foundation deck.'
Assert-True ($related -match "lunara_get_journal_kicker") 'Related Journal rendering must use the shared canonical-first kicker.'
Assert-True ($style -match 'Version:\s*3\.2\.16') 'Theme version must be 3.2.16 for the late Oscars CSS route scope.'
Assert-True ($fixtureSource -match "LUNARA_JOURNAL_FOUNDATION_VERSION',\s*'1\.2\.1'") 'Journal integration fixture must target stabilized Foundation 1.2.1.'
Assert-True ($loader -match 'require_once\s+\$lunara_inc\s*\.\s*''journal-family\.php''') 'Split loader must include the dedicated Journal-family adapter module.'
Assert-True ($journalFamily -match 'function\s+lunara_get_journal_related_tax_query') 'Related-query adapters belong in the dedicated Journal-family module.'
Assert-True ($journal -notmatch 'function\s+lunara_get_journal_related_tax_query') 'CPT ownership module must not absorb Journal presentation/query adapters.'
Assert-True ($journalFamily -match 'function\s+lunara_get_journal_kicker') 'Journal presentation adapters belong in the dedicated Journal-family module.'
Assert-True ($journal -notmatch 'function\s+lunara_get_journal_kicker') 'CPT ownership module must stay focused on registration and fallback controls.'

$creditStart = $single.IndexOf('function lunara_get_journal_hero_credit_data')
$creditEnd = $single.IndexOf("if ( ! function_exists( 'lunara_render_journal_hero_credit' ) )", $creditStart)
Assert-True ($creditStart -ge 0 -and $creditEnd -gt $creditStart) 'Unable to isolate Journal hero credit resolver.'
$creditResolver = $single.Substring($creditStart, $creditEnd - $creditStart)
Assert-True ($creditResolver -match 'lunara_get_journal_image_source_pair') 'Hero credit must use the isolated image-provenance adapter.'
$sourceStart = $journalFamily.IndexOf('function lunara_get_journal_image_source_pair')
$sourceEnd = $journalFamily.IndexOf('function lunara_get_journal_section_label', $sourceStart)
Assert-True ($sourceStart -ge 0 -and $sourceEnd -gt $sourceStart) 'Unable to isolate Journal image-source adapter.'
$sourceResolver = $journalFamily.Substring($sourceStart, $sourceEnd - $sourceStart)
Assert-True ($sourceResolver -match "'journal_image_source_url'") 'Image provenance must prefer the canonical image-specific source URL.'
Assert-True ($sourceResolver -match 'wp_parse_url') 'Canonical image URLs must derive their own matching display source.'
Assert-True ($sourceResolver -notmatch 'journal_source_items|lunara_get_journal_source_items|_lunara_source_(?:name|url)') 'Editorial sources must never be repurposed as image provenance.'

Assert-True ($journal -match '(?s)<\?php if \( ! \$foundation_fields \) : \?>\s*<div class="lunara-meta-field">\s*<label>\s*<input\s*type="checkbox"\s*name="lunara_journal_featured"') 'Legacy Journal featured control must be hidden while Foundation owns priority.'
Assert-True ($journal -match '(?s)if \( ! lunara_journal_foundation_is_active\(\) \) \{\s*update_post_meta\(\s*\$post_id,\s*''_lunara_journal_featured''') 'Foundation saves must not overwrite the ignored legacy featured flag.'

foreach ($taxonomy in @('journal_section', 'journal_topic', 'journal_type')) {
    $templatePath = Join-Path $root ("taxonomy-$taxonomy.php")
    Assert-True (Test-Path $templatePath) "Missing Journal taxonomy template: taxonomy-$taxonomy.php"
    $template = Get-Content -LiteralPath $templatePath -Raw
    Assert-True ($template -match "archive-journal\.php") "taxonomy-$taxonomy.php must reuse the Journal visual system."
}

Assert-True ($archive -match "lunara_get_journal_archive_filter_terms\(\s*'journal_section',\s*8,") 'Journal archive must bound canonical sections to eight ranked terms.'
Assert-True ($archive -match "lunara_get_journal_archive_filter_terms\(\s*'journal_topic',\s*10,") 'Journal archive must bound canonical topics to ten ranked terms.'
Assert-True ($archive -match "lunara_get_journal_archive_filter_terms\(\s*'journal_type',\s*8,") 'Journal archive must bound legacy types to eight ranked terms.'
Assert-True ($journalFamily -match '''number''\s*=>\s*\$limit') 'Journal filter adapter must bound get_terms at query time.'
Assert-True ($journalFamily -match '\$terms\[\]\s*=\s*\$current_term') 'Journal filter adapter must retain an active term outside the ranked slice.'
Assert-True ($single -match 'lunara_get_journal_related_tax_query') 'Journal related entries must use canonical section/topic query adapters.'
Assert-True ($frontend -match "is_tax\(\s*array\(\s*'journal_section',\s*'journal_topic',\s*'journal_type'\s*\)\s*\)") 'Journal taxonomy routes must receive route-scoped archive behavior.'
Assert-True ($archive -notmatch '(?m)^\s*\$copy\s*=\s*''\s*;') 'Journal archive copy must remain editable instead of being forcibly blanked.'
Assert-True ($archive -match '\$thumb_loading\s*=\s*1\s*===\s*\$journal_card_index\s*\?\s*''eager''\s*:\s*''lazy''') 'Only the true Journal lead image may load eagerly.'

Write-Output 'Journal Foundation theme integration contract passed.'
