# Oscars Portal Dynamic Layer Implementation Plan (Theme 3.2.59)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make `/oscars/` read as live: scroll reveals and counters on the portal, a navigator scroll-spy, a season clock, board summary chips, and a date-seeded ledger pull, all inside the existing slots.

**Architecture:** One new PHP module (`inc/oscars-portal-dynamic.php`) holds pure helpers and renderers that return `''` when they have nothing; `page-oscars.php` and the board renderer consume them through `function_exists` guards. The public runtime gains a portal branch and a small portal IIFE. All CSS lands in `lunara-shell.css` because the route sheet has 880 bytes of headroom.

**Tech Stack:** PHP 8.3 (WordPress child theme), vanilla JS, CSS, PowerShell 7 contracts, PHP runtime harnesses.

**Spec:** `docs/superpowers/specs/2026-09-05-oscars-portal-dynamic-design.md`

## Global Constraints

- Branch `claude/oscars-portal-dynamic-3.2.59`; never commit to `main`; never deploy; no cache operation against production.
- Route sheet `assets/css/lunara-oscars-portal.css` stays at or under 45,000 bytes: do not edit it.
- `assets/css/lunara-shell.css` ≤ 204,800 bytes; `assets/js/lunara-public-runtime.js` ≤ 20,480 bytes; `inc/oscars-portal-critical.php` untouched.
- Six sentinel ids (`oscars-doors`, `oscars-spotlights`, `oscars-titles`, `oscars-research`, `oscars-winners`, `oscars-deep-cuts`) and the single `id="oscars-board"` stay.
- Identity literal lines in `page-oscars.php` (`lunara_theme_mod_text( 'lunara_oscars_portal_*', '...' )`) are byte-locked by the Studio runtime test: do not touch them.
- The board renderer must not call `wp_create_nonce`, `wp_nonce_field`, `setcookie`, `is_user_logged_in`, `get_current_user_id`, `current_user_can`; the same list applies to the new module.
- Back up any file before mutation-testing it with `cp`, never `git checkout --`.
- Version identity: every `3.2.58` in `tests/`, `assets/css/`, `inc/`, `style.css` moves to `3.2.59`; `docs/CHANGELOG.md` and `docs/SESSION-LOG.md` keep history. `tests/oscars-read-path-ratchet.ps1` keeps its dated `3.2.54` pin.

---

### Task 1: Dynamic module, harness, and contract (TDD)

**Files:**
- Create: `inc/oscars-portal-dynamic.php`
- Create: `tests/fixtures/oscars-portal-dynamic-harness.php`
- Create: `tests/oscars-portal-dynamic-contract.ps1`
- Modify: `functions-loader.php:30` (add the require after `oscars-family.php`)

**Interfaces:**
- Produces: `lunara_oscars_sanitize_ceremony_date( $value ): string`, `lunara_oscars_season_clock( $date_iso, $now_ts = null ): array`, `lunara_oscars_render_season_clock( $clock ): string`, `lunara_oscars_board_summary( $rows ): array`, `lunara_oscars_board_status_label( $status ): string`, `lunara_oscars_render_board_summary( $summary, $revised_label = '' ): string`, `lunara_oscars_todays_pull_offset( $day, $year, $count ): int`, `lunara_get_oscars_todays_pull(): array`, `lunara_oscars_render_todays_pull( $pull ): string`.

- [ ] **Step 1: Write the harness (fails until the module exists)**

`tests/fixtures/oscars-portal-dynamic-harness.php`:

```php
<?php
/**
 * Execute inc/oscars-portal-dynamic.php against stubs and report JSON cases.
 * The module is pure PHP behind function_exists guards, so it is required
 * directly: the code under test is the code that ships.
 */
$root = dirname( __DIR__, 2 );
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', $root . '/' ); }
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $value ) ); }
function absint( $value ) { return abs( (int) $value ); }
function esc_html( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $value ) { return esc_html( $value ); }
function esc_url( $value ) { $v = (string) $value; return preg_match( '/^(https?:\/\/|\/|#)/i', $v ) ? htmlspecialchars( $v, ENT_QUOTES, 'UTF-8' ) : ''; }
function __( $v, $d = '' ) { return $v; }
function esc_html__( $v, $d = '' ) { return esc_html( $v ); }
function esc_attr__( $v, $d = '' ) { return esc_attr( $v ); }
require $root . '/inc/oscars-portal-dynamic.php';

$report = array( 'cases' => array() );
$record = static function ( $name, $checks ) use ( &$report ) {
	$passed = true;
	foreach ( $checks as $check ) { $passed = $passed && (bool) $check; }
	$report['cases'][ $name ] = array( 'passed' => $passed, 'checks' => $checks );
};

$record( 'sanitize-date', array(
	'valid_kept'       => '2027-03-14' === lunara_oscars_sanitize_ceremony_date( ' 2027-03-14 ' ),
	'impossible_empty' => '' === lunara_oscars_sanitize_ceremony_date( '2027-02-30' ),
	'garbage_empty'    => '' === lunara_oscars_sanitize_ceremony_date( '14/03/2027' ),
	'array_empty'      => '' === lunara_oscars_sanitize_ceremony_date( array( '2027-03-14' ) ),
) );

$now = gmmktime( 12, 0, 0, 9, 5, 2026 ); // 2026-09-05
$clock = static function ( $date ) use ( $now ) { return lunara_oscars_season_clock( $date, $now ); };
$record( 'season-clock-phases', array(
	'unset_empty'     => array() === $clock( '' ),
	'countdown'       => 'countdown' === ( $clock( '2027-03-14' )['phase'] ?? '' ) && 190 === ( $clock( '2027-03-14' )['days'] ?? 0 ),
	'season'          => 'season' === ( $clock( '2026-11-14' )['phase'] ?? '' ),
	'final'           => 'final' === ( $clock( '2026-09-20' )['phase'] ?? '' ),
	'tonight'         => 'tonight' === ( $clock( '2026-09-05' )['phase'] ?? '' ) && 0 === ( $clock( '2026-09-05' )['days'] ?? 1 ),
	'settled'         => 'settled' === ( $clock( '2026-09-01' )['phase'] ?? '' ) && -4 === ( $clock( '2026-09-01' )['days'] ?? 0 ),
	'retired_empty'   => array() === $clock( '2026-08-01' ),
	'date_label'      => 'March 14, 2027' === ( $clock( '2027-03-14' )['date_label'] ?? '' ),
) );

$clock_html = lunara_oscars_render_season_clock( $clock( '2027-03-14' ) );
$record( 'season-clock-render', array(
	'empty_for_empty'   => '' === lunara_oscars_render_season_clock( array() ),
	'carries_data_iso'  => false !== strpos( $clock_html, 'data-lunara-season-clock="2027-03-14"' ),
	'carries_days'      => false !== strpos( $clock_html, '<strong class="lunara-oscars-season-days" data-lunara-season-days>190</strong>' ),
	'carries_phase'     => false !== strpos( $clock_html, 'is-phase-countdown' ),
	'tonight_copy'      => false !== strpos( lunara_oscars_render_season_clock( $clock( '2026-09-05' ) ), '>Tonight<' ),
	'settled_copy'      => false !== strpos( lunara_oscars_render_season_clock( $clock( '2026-09-01' ) ), 'days since the ceremony' ),
) );

$rows = array(
	array( 'status' => 'won' ), array( 'status' => 'front_runner' ), array( 'status' => 'front_runner' ),
	array( 'status' => 'contender' ), array( 'status' => '' ), array( 'status' => 'bogus' ), 'not-a-row',
);
$summary = lunara_oscars_board_summary( $rows );
$record( 'board-summary', array(
	'empty_for_empty' => array() === lunara_oscars_board_summary( array() ),
	'total_counts_rows' => 6 === ( $summary['total'] ?? 0 ),
	'canonical_order'   => array( 'front_runner', 'contender', 'won' ) === array_column( $summary['chips'] ?? array(), 'status' ),
	'counts'            => array( 2, 1, 1 ) === array_column( $summary['chips'] ?? array(), 'count' ),
	'label_fallback'    => 'Front Runner' === lunara_oscars_board_status_label( 'front_runner' ),
) );

$summary_html = lunara_oscars_render_board_summary( $summary, 'Revised <Sep 5>' );
$record( 'board-summary-render', array(
	'empty_for_empty'  => '' === lunara_oscars_render_board_summary( array() ),
	'total_chip'       => false !== strpos( $summary_html, 'is-total"><strong>6</strong> calls' ),
	'status_chip'      => false !== strpos( $summary_html, 'is-status-front_runner"><strong>2</strong> Front Runner' ),
	'revised_escaped'  => false !== strpos( $summary_html, 'Revised &lt;Sep 5&gt;' ),
	'no_revised_chip'  => false === strpos( lunara_oscars_render_board_summary( $summary ), 'is-revised' ),
) );

$offsets = array();
for ( $day = 0; $day < 366; $day++ ) { $offsets[] = lunara_oscars_todays_pull_offset( $day, 2026, 1234 ); }
$record( 'todays-pull-offset', array(
	'in_range'       => 0 === count( array_filter( $offsets, static function ( $o ) { return $o < 0 || $o >= 1234; } ) ),
	'deterministic'  => lunara_oscars_todays_pull_offset( 40, 2026, 1234 ) === lunara_oscars_todays_pull_offset( 40, 2026, 1234 ),
	'varies_by_day'  => count( array_unique( $offsets ) ) > 300,
	'varies_by_year' => lunara_oscars_todays_pull_offset( 40, 2026, 1234 ) !== lunara_oscars_todays_pull_offset( 40, 2027, 1234 ),
	'zero_count'     => 0 === lunara_oscars_todays_pull_offset( 40, 2026, 0 ),
) );

$pull = array(
	'ceremony_label' => '47th Academy Awards', 'year_label' => '1974', 'category' => 'Actress in a Leading Role',
	'category_url' => 'https://example.test/oscars/category/actress/', 'film' => 'Alice <Doesn\'t> Live Here Anymore',
	'film_url' => 'https://example.test/oscars/title/tt0071115/', 'name' => 'Ellen Burstyn', 'name_url' => 'https://example.test/oscars/person/nm0000994/',
	'ceremony_url' => 'https://example.test/oscars/ceremony/47/',
);
$pull_html = lunara_oscars_render_todays_pull( $pull );
$record( 'todays-pull-render', array(
	'empty_for_empty' => '' === lunara_oscars_render_todays_pull( array() ),
	'film_escaped'    => false !== strpos( $pull_html, 'Alice &lt;Doesn&#039;t&gt; Live Here Anymore' ),
	'film_link'       => false !== strpos( $pull_html, 'href="https://example.test/oscars/title/tt0071115/"' ),
	'person_link'     => false !== strpos( $pull_html, 'href="https://example.test/oscars/person/nm0000994/"' ),
	'no_name_ok'      => false === strpos( lunara_oscars_render_todays_pull( array_merge( $pull, array( 'name' => '', 'name_url' => '' ) ) ), 'lunara-oscars-todays-pull-person' ),
) );

echo wp_json_encode_fallback( $report );
function wp_json_encode_fallback( $v ) { return json_encode( $v, JSON_UNESCAPED_SLASHES ); }
```

- [ ] **Step 2: Write the contract**

`tests/oscars-portal-dynamic-contract.ps1`:

```powershell
$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

# Theme 3.2.59 — the Oscars portal reads as live.
# Proves the dynamic module's pure behavior through its harness, then pins
# the wiring: loader, template consumption behind function_exists guards,
# Customizer registration, the runtime's portal branch, the shell rules,
# the flush key, and the anonymous-cacheable forbidden list.

$root = Split-Path -Parent $PSScriptRoot
$failures = [System.Collections.Generic.List[string]]::new()
function Assert-Contract { param([bool]$Condition, [string]$Message) if (-not $Condition) { $failures.Add($Message) } }

$fixture = Join-Path $PSScriptRoot 'fixtures\oscars-portal-dynamic-harness.php'
Assert-Contract (Test-Path -LiteralPath $fixture) 'Missing runtime fixture: tests/fixtures/oscars-portal-dynamic-harness.php'
$output = & php $fixture 2>&1 | Out-String
Assert-Contract ($LASTEXITCODE -eq 0) "Dynamic harness failed:`n$output"
try { $result = $output | ConvertFrom-Json } catch { throw "Dynamic harness returned invalid JSON:`n$output" }
foreach ($caseName in @('sanitize-date','season-clock-phases','season-clock-render','board-summary','board-summary-render','todays-pull-offset','todays-pull-render')) {
    $case = $result.cases.$caseName
    Assert-Contract ($null -ne $case) "Missing harness case: $caseName"
    if ($null -ne $case) { Assert-Contract ([bool]$case.passed) ("Harness case failed: $caseName " + ($case.checks | ConvertTo-Json -Compress)) }
}

$module   = [IO.File]::ReadAllText((Join-Path $root 'inc/oscars-portal-dynamic.php'))
$loader   = [IO.File]::ReadAllText((Join-Path $root 'functions-loader.php'))
$template = [IO.File]::ReadAllText((Join-Path $root 'page-oscars.php'))
$portal   = [IO.File]::ReadAllText((Join-Path $root 'inc/oscars-portal.php'))
$custom   = [IO.File]::ReadAllText((Join-Path $root 'inc/customizer.php'))
$runtime  = [IO.File]::ReadAllText((Join-Path $root 'assets/js/lunara-public-runtime.js'))
$shell    = [IO.File]::ReadAllText((Join-Path $root 'assets/css/lunara-shell.css'))
$home     = [IO.File]::ReadAllText((Join-Path $root 'inc/home-sections.php'))

Assert-Contract ($loader -match "require_once \`$lunara_inc \. 'oscars-portal-dynamic\.php'") 'The loader must require inc/oscars-portal-dynamic.php.'
foreach ($forbidden in @('wp_create_nonce','wp_nonce_field','setcookie','is_user_logged_in','get_current_user_id','current_user_can')) {
    Assert-Contract (-not $module.Contains($forbidden)) "The dynamic module must not call $forbidden on the anonymous-cacheable portal."
}
Assert-Contract ($template -match "function_exists\( 'lunara_oscars_render_season_clock' \)") 'page-oscars.php must consume the season clock behind a function_exists guard.'
Assert-Contract ($template -match "get_theme_mod\( 'lunara_oscars_next_ceremony_date', '' \)") 'The hero must read the ceremony date mod with an empty default.'
Assert-Contract ($template -match "function_exists\( 'lunara_oscars_render_todays_pull' \)") 'page-oscars.php must consume Today''s Pull behind a function_exists guard.'
Assert-Contract ($template.IndexOf('lunara_oscars_render_todays_pull') -lt $template.IndexOf('<div class="lunara-oscars-portal-facts-grid">')) 'Today''s Pull must render above the facts grid.'
Assert-Contract ($portal -match "function_exists\( 'lunara_oscars_board_summary' \)") 'The board renderer must build its summary behind a function_exists guard.'
Assert-Contract ($portal.IndexOf('lunara_oscars_render_board_summary') -lt $portal.IndexOf('<ol class="lunara-oscars-board-list">')) 'The board summary must render above the list.'
Assert-Contract ($custom -match "'setting'\s*=>\s*'lunara_oscars_next_ceremony_date'") 'The Customizer must register lunara_oscars_next_ceremony_date.'
Assert-Contract ($custom -match "'sanitize'\s*=>\s*'lunara_oscars_sanitize_ceremony_date'") 'The ceremony date must sanitize through lunara_oscars_sanitize_ceremony_date.'
Assert-Contract ($runtime -match "var isPortal=document\.body\.classList\.contains\('lunara-oscars-portal-page'\)") 'The reveal runtime must detect the portal.'
Assert-Contract ($runtime.IndexOf('if(isPortal){') -lt $runtime.IndexOf('if(isPluginPage){')) 'The portal branch must win over the plugin-page branch.'
Assert-Contract ($runtime -match "':not\(\.lunara-oscars-portal-slot-hero\)'") 'The hero must never be a reveal target.'
Assert-Contract ($runtime -match "window\.setTimeout\(function\(\)\{document\.querySelectorAll\('\.lunara-reveal:not\(\.is-visible\)'\)") 'The reveal safety timer must exist.'
Assert-Contract ($runtime -match "\.lunara-oscars-portal-stat-value,\.lunara-oscars-portal-fact-value,\.lunara-oscars-season-days") 'Counters must cover the portal stat, fact, and season-day values.'
Assert-Contract ($runtime -match "/\^\\d\{4\}\$/\.test\(text\)") 'Counters must skip four-digit years.'
Assert-Contract ($runtime -match "setAttribute\('aria-current','location'\)") 'The navigator scroll-spy must mark the current link.'
Assert-Contract ($runtime -match "data-lunara-season-clock") 'The runtime must recompute the season clock at view time.'
foreach ($rule in @('.lunara-oscars-season-clock', '.lunara-oscars-board-summary', '.lunara-oscars-todays-pull', '.lunara-oscars-navigator a[aria-current]', '@media (hover: hover) and (pointer: fine)')) {
    Assert-Contract ($shell.Contains($rule)) "Shell must carry $rule."
}
Assert-Contract ($home -match "delete_transient\( 'lunara_oscars_todays_pull_v1_' \. \`$day \)") 'The import flush must clear the Today''s Pull key.'
$shellBytes = (Get-Item (Join-Path $root 'assets/css/lunara-shell.css')).Length
$runtimeBytes = (Get-Item (Join-Path $root 'assets/js/lunara-public-runtime.js')).Length
Assert-Contract ($shellBytes -le 204800) "Shell exceeds 204,800 bytes: $shellBytes."
Assert-Contract ($runtimeBytes -le 20480) "Public runtime exceeds 20,480 bytes: $runtimeBytes."

if ($failures.Count -gt 0) { throw "Oscars portal dynamic contract failed:`n$(($failures | ForEach-Object { " - $_" }) -join "`n")" }
Write-Host 'Oscars portal dynamic contract passed: harness cases, wiring, runtime branches, shell rules, flush key, budgets.'
```

- [ ] **Step 3: Run the contract, expect failure on the missing module**

Run: `pwsh tests/oscars-portal-dynamic-contract.ps1`
Expected: throws, first failure "Dynamic harness failed" (require of a missing file).

- [ ] **Step 4: Write the module**

`inc/oscars-portal-dynamic.php`:

```php
<?php
/**
 * Oscars portal dynamic layer (Theme 3.2.59).
 *
 * Pure helpers that make /oscars/ read as live: a season clock in the hero,
 * a status summary above the prediction board, and a date-seeded ledger
 * pull in Deep Cuts. Every renderer returns the exact empty string when it
 * has nothing to say, so a site with no ceremony date, no picks, or no
 * ledger keeps its 3.2.58 markup byte-for-byte. Output is anonymous-
 * cacheable: no nonce, cookie, or user-conditional branch anywhere here.
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'lunara_oscars_sanitize_ceremony_date' ) ) {
	/**
	 * Bound the Customizer's ceremony date to a real Y-m-d or nothing.
	 *
	 * @param mixed $value Raw mod value.
	 * @return string
	 */
	function lunara_oscars_sanitize_ceremony_date( $value ) {
		$value = is_scalar( $value ) ? trim( (string) $value ) : '';
		if ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m ) ) {
			return '';
		}
		return checkdate( (int) $m[2], (int) $m[3], (int) $m[1] ) ? $value : '';
	}
}

if ( ! function_exists( 'lunara_oscars_season_clock' ) ) {
	/**
	 * Resolve the season phase for a ceremony date.
	 *
	 * @param string   $date_iso Y-m-d.
	 * @param int|null $now_ts   "Today" as a timestamp; null reads the site clock.
	 * @return array<string,mixed> Empty when unset, invalid, or more than 14 days past.
	 */
	function lunara_oscars_season_clock( $date_iso, $now_ts = null ) {
		$date_iso = lunara_oscars_sanitize_ceremony_date( $date_iso );
		if ( '' === $date_iso ) {
			return array();
		}
		$today_iso = null === $now_ts
			? ( function_exists( 'wp_date' ) ? wp_date( 'Y-m-d' ) : gmdate( 'Y-m-d' ) )
			: gmdate( 'Y-m-d', (int) $now_ts );
		$utc    = new DateTimeZone( 'UTC' );
		$target = DateTime::createFromFormat( '!Y-m-d', $date_iso, $utc );
		$today  = DateTime::createFromFormat( '!Y-m-d', (string) $today_iso, $utc );
		if ( ! $target || ! $today ) {
			return array();
		}
		$days = (int) $today->diff( $target )->format( '%r%a' );
		if ( $days < -14 ) {
			return array();
		}
		if ( $days < 0 ) {
			$phase = 'settled';
			$label = __( 'Ceremony settled', 'lunara-film' );
		} elseif ( 0 === $days ) {
			$phase = 'tonight';
			$label = __( 'Ceremony night', 'lunara-film' );
		} elseif ( $days <= 30 ) {
			$phase = 'final';
			$label = __( 'Final stretch', 'lunara-film' );
		} elseif ( $days <= 120 ) {
			$phase = 'season';
			$label = __( 'Awards season', 'lunara-film' );
		} else {
			$phase = 'countdown';
			$label = __( 'Ceremony countdown', 'lunara-film' );
		}
		return array(
			'date_iso'    => $date_iso,
			'date_label'  => $target->format( 'F j, Y' ),
			'days'        => $days,
			'phase'       => $phase,
			'phase_label' => $label,
		);
	}
}

if ( ! function_exists( 'lunara_oscars_render_season_clock' ) ) {
	/**
	 * @param array<string,mixed> $clock Output of lunara_oscars_season_clock().
	 * @return string
	 */
	function lunara_oscars_render_season_clock( $clock ) {
		if ( ! is_array( $clock ) || empty( $clock['date_iso'] ) ) {
			return '';
		}
		$days = (int) ( $clock['days'] ?? 0 );
		if ( $days > 0 ) {
			$number = (string) $days;
			$unit   = 1 === $days ? __( 'day to the ceremony', 'lunara-film' ) : __( 'days to the ceremony', 'lunara-film' );
		} elseif ( 0 === $days ) {
			$number = __( 'Tonight', 'lunara-film' );
			$unit   = __( 'the envelopes open', 'lunara-film' );
		} else {
			$number = (string) abs( $days );
			$unit   = -1 === $days ? __( 'day since the ceremony', 'lunara-film' ) : __( 'days since the ceremony', 'lunara-film' );
		}
		return '<div class="lunara-oscars-season-clock is-phase-' . esc_attr( (string) ( $clock['phase'] ?? '' ) ) . '" data-lunara-season-clock="' . esc_attr( (string) $clock['date_iso'] ) . '" role="group" aria-label="' . esc_attr__( 'Season clock', 'lunara-film' ) . '">'
			. '<span class="lunara-oscars-season-phase">' . esc_html( (string) ( $clock['phase_label'] ?? '' ) ) . '</span>'
			. '<strong class="lunara-oscars-season-days" data-lunara-season-days>' . esc_html( $number ) . '</strong>'
			. '<span class="lunara-oscars-season-unit" data-lunara-season-unit>' . esc_html( $unit ) . '</span>'
			. '<span class="lunara-oscars-season-date">' . esc_html( (string) ( $clock['date_label'] ?? '' ) ) . '</span>'
			. '</div>';
	}
}

if ( ! function_exists( 'lunara_oscars_board_summary' ) ) {
	/**
	 * Count board rows by status in canonical order.
	 *
	 * @param array<int,array<string,mixed>> $rows Board rows (status key).
	 * @return array{total:int,chips:array<int,array{status:string,count:int}>}|array{}
	 */
	function lunara_oscars_board_summary( $rows ) {
		$order  = array( 'front_runner', 'contender', 'predicted', 'watchlist', 'won', 'lost' );
		$counts = array_fill_keys( $order, 0 );
		$total  = 0;
		foreach ( (array) $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$total++;
			$status = isset( $row['status'] ) && is_scalar( $row['status'] ) ? (string) $row['status'] : '';
			if ( isset( $counts[ $status ] ) ) {
				$counts[ $status ]++;
			}
		}
		if ( 0 === $total ) {
			return array();
		}
		$chips = array();
		foreach ( $order as $status ) {
			if ( $counts[ $status ] > 0 ) {
				$chips[] = array( 'status' => $status, 'count' => $counts[ $status ] );
			}
		}
		return array( 'total' => $total, 'chips' => $chips );
	}
}

if ( ! function_exists( 'lunara_oscars_board_status_label' ) ) {
	/**
	 * @param string $status Sanitized status key.
	 * @return string
	 */
	function lunara_oscars_board_status_label( $status ) {
		$status = sanitize_key( $status );
		if ( function_exists( 'lunara_oscar_pick_status_labels' ) ) {
			$labels = lunara_oscar_pick_status_labels();
			if ( isset( $labels[ $status ] ) ) {
				return (string) $labels[ $status ];
			}
		}
		return ucwords( str_replace( '_', ' ', $status ) );
	}
}

if ( ! function_exists( 'lunara_oscars_render_board_summary' ) ) {
	/**
	 * @param array<string,mixed> $summary       Output of lunara_oscars_board_summary().
	 * @param string              $revised_label Optional "Revised Sep 5" chip.
	 * @return string
	 */
	function lunara_oscars_render_board_summary( $summary, $revised_label = '' ) {
		if ( ! is_array( $summary ) || empty( $summary['chips'] ) ) {
			return '';
		}
		$total = (int) ( $summary['total'] ?? 0 );
		$html  = '<ul class="lunara-oscars-board-summary" aria-label="' . esc_attr__( 'Board status summary', 'lunara-film' ) . '">';
		$html .= '<li class="lunara-oscars-board-chip is-total"><strong>' . esc_html( (string) $total ) . '</strong> ' . esc_html( 1 === $total ? __( 'call', 'lunara-film' ) : __( 'calls', 'lunara-film' ) ) . '</li>';
		foreach ( (array) $summary['chips'] as $chip ) {
			$status = sanitize_key( (string) ( $chip['status'] ?? '' ) );
			if ( '' === $status ) {
				continue;
			}
			$html .= '<li class="lunara-oscars-board-chip is-status-' . esc_attr( $status ) . '"><strong>' . esc_html( (string) (int) ( $chip['count'] ?? 0 ) ) . '</strong> ' . esc_html( lunara_oscars_board_status_label( $status ) ) . '</li>';
		}
		$revised_label = is_scalar( $revised_label ) ? trim( (string) $revised_label ) : '';
		if ( '' !== $revised_label ) {
			$html .= '<li class="lunara-oscars-board-chip is-revised">' . esc_html( $revised_label ) . '</li>';
		}
		return $html . '</ul>';
	}
}

if ( ! function_exists( 'lunara_oscars_todays_pull_offset' ) ) {
	/**
	 * Deterministic row offset for a (year, day) pair. A prime stride spreads
	 * consecutive days across the table instead of walking it in order.
	 *
	 * @param int $day   Day of year (0-365).
	 * @param int $year  Four-digit year.
	 * @param int $count Row count.
	 * @return int
	 */
	function lunara_oscars_todays_pull_offset( $day, $year, $count ) {
		$count = (int) $count;
		if ( $count <= 0 ) {
			return 0;
		}
		$seed = ( (int) $year * 367 + (int) $day ) * 7919;
		return (int) ( $seed % $count );
	}
}

if ( ! function_exists( 'lunara_get_oscars_todays_pull' ) ) {
	/**
	 * One winning ledger row for today, cached for the day.
	 *
	 * @return array<string,string>
	 */
	function lunara_get_oscars_todays_pull() {
		if ( ! function_exists( 'lunara_awards_table_name' ) || ! function_exists( 'get_transient' ) ) {
			return array();
		}
		$day       = function_exists( 'wp_date' ) ? (int) wp_date( 'z' ) : (int) gmdate( 'z' );
		$year      = function_exists( 'wp_date' ) ? (int) wp_date( 'Y' ) : (int) gmdate( 'Y' );
		$cache_key = 'lunara_oscars_todays_pull_v1_' . $day;
		$cached    = get_transient( $cache_key );
		if ( is_array( $cached ) && ! empty( $cached ) ) {
			return $cached;
		}
		$table = lunara_awards_table_name();
		if ( '' === $table ) {
			return array();
		}
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE winner = 1 AND film != ''" );
		if ( $count <= 0 ) {
			return array();
		}
		$offset = lunara_oscars_todays_pull_offset( $day, $year, $count );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT ceremony, year, canonical_category, film, film_id, name, nominee_ids FROM {$table} WHERE winner = 1 AND film != '' ORDER BY id ASC LIMIT 1 OFFSET %d", $offset ), ARRAY_A );
		if ( empty( $row ) || ! is_array( $row ) ) {
			return array();
		}
		$aat      = function_exists( 'lunara_get_oscars_plugin' ) ? lunara_get_oscars_plugin() : null;
		$ceremony = (int) ( $row['ceremony'] ?? 0 );
		$ordinal  = ( $aat && method_exists( $aat, 'ordinal' ) ) ? $aat->ordinal( $ceremony ) : (string) $ceremony;
		$film_id  = trim( (string) ( $row['film_id'] ?? '' ) );
		$film_ids = array_values( array_filter( array_map( 'trim', explode( '|', $film_id ) ) ) );
		$film_id  = $film_ids[0] ?? '';
		$name_ids = array_values( array_filter( array_map( 'trim', explode( '|', (string) ( $row['nominee_ids'] ?? '' ) ) ) ) );
		$name_id  = $name_ids[0] ?? '';
		$films    = array_values( array_filter( array_map( 'trim', explode( '|', (string) ( $row['film'] ?? '' ) ) ) ) );
		$canon    = (string) ( $row['canonical_category'] ?? '' );
		$entity   = static function ( $id ) use ( $aat ) {
			if ( '' === $id ) {
				return '';
			}
			return ( $aat && method_exists( $aat, 'get_entity_url' ) ) ? (string) $aat->get_entity_url( $id ) : home_url( '/oscars/title/' . rawurlencode( $id ) . '/' );
		};
		$pull = array(
			'ceremony_label' => $ordinal . ' ' . __( 'Academy Awards', 'lunara-film' ),
			'ceremony_url'   => ( $aat && method_exists( $aat, 'get_ceremony_url' ) ) ? (string) $aat->get_ceremony_url( $ceremony ) : home_url( '/oscars/ceremony/' . $ceremony . '/' ),
			'year_label'     => trim( (string) ( $row['year'] ?? '' ) ),
			'category'       => ( $aat && method_exists( $aat, 'format_category_display' ) ) ? (string) $aat->format_category_display( $canon ) : $canon,
			'category_url'   => ( $aat && method_exists( $aat, 'get_category_url' ) ) ? (string) $aat->get_category_url( $canon ) : '',
			'film'           => $films[0] ?? '',
			'film_url'       => $entity( $film_id ),
			'name'           => trim( (string) ( $row['name'] ?? '' ) ),
			'name_url'       => $entity( $name_id ),
		);
		if ( '' === $pull['film'] ) {
			return array();
		}
		set_transient( $cache_key, $pull, DAY_IN_SECONDS );
		return $pull;
	}
}

if ( ! function_exists( 'lunara_oscars_render_todays_pull' ) ) {
	/**
	 * @param array<string,string> $pull Output of lunara_get_oscars_todays_pull().
	 * @return string
	 */
	function lunara_oscars_render_todays_pull( $pull ) {
		if ( ! is_array( $pull ) || '' === trim( (string) ( $pull['film'] ?? '' ) ) ) {
			return '';
		}
		$link = static function ( $label, $url, $class ) {
			$label = trim( (string) $label );
			$url   = trim( (string) $url );
			if ( '' === $label ) {
				return '';
			}
			return '' !== $url
				? '<a class="' . esc_attr( $class ) . '" href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>'
				: '<span class="' . esc_attr( $class ) . '">' . esc_html( $label ) . '</span>';
		};
		$kicker = __( "Today's Pull", 'lunara-film' );
		$meta   = array_filter( array(
			$link( $pull['ceremony_label'] ?? '', $pull['ceremony_url'] ?? '', 'lunara-oscars-todays-pull-ceremony' ),
			'' !== trim( (string) ( $pull['year_label'] ?? '' ) ) ? '<span class="lunara-oscars-todays-pull-year">' . esc_html( (string) $pull['year_label'] ) . '</span>' : '',
		) );
		$detail = array_filter( array(
			$link( $pull['category'] ?? '', $pull['category_url'] ?? '', 'lunara-oscars-todays-pull-category' ),
			$link( $pull['name'] ?? '', $pull['name_url'] ?? '', 'lunara-oscars-todays-pull-person' ),
		) );
		return '<article class="lunara-oscars-todays-pull">'
			. '<p class="lunara-oscars-todays-pull-kicker"><span class="lunara-oscars-todays-pull-badge">' . esc_html( $kicker ) . '</span>' . implode( '<span class="lunara-oscars-todays-pull-sep" aria-hidden="true">/</span>', $meta ) . '</p>'
			. '<h3 class="lunara-oscars-todays-pull-title">' . $link( $pull['film'], $pull['film_url'] ?? '', 'lunara-oscars-todays-pull-film' ) . '</h3>'
			. ( ! empty( $detail ) ? '<p class="lunara-oscars-todays-pull-meta">' . implode( '<span class="lunara-oscars-todays-pull-sep" aria-hidden="true">/</span>', $detail ) . '</p>' : '' )
			. '</article>';
	}
}
```

- [ ] **Step 5: Wire the loader**

In `functions-loader.php`, after line 30 (`oscars-family.php`), add:

```php
require_once $lunara_inc . 'oscars-portal-dynamic.php';  // Season clock, board summary, Today's Pull (pure renderers).
```

- [ ] **Step 6: Run the harness alone, then the contract**

Run: `php tests/fixtures/oscars-portal-dynamic-harness.php` — expect JSON with every `passed: true`.
Run: `pwsh tests/oscars-portal-dynamic-contract.ps1` — expect failures only on template/portal/customizer/runtime/shell/flush wiring (Tasks 2–5).

- [ ] **Step 7: Commit**

```bash
git add inc/oscars-portal-dynamic.php functions-loader.php tests/fixtures/oscars-portal-dynamic-harness.php tests/oscars-portal-dynamic-contract.ps1 docs/superpowers
git commit -m "Theme 3.2.59: Oscars portal dynamic module, harness, contract"
```

### Task 2: Board summary in the prediction board renderer

**Files:**
- Modify: `inc/oscars-portal.php` (inside `lunara_render_oscars_prediction_board`, after `$rows` is built and before `ob_start()`; and the markup between the header `</div>` and `<ol class="lunara-oscars-board-list">`)

- [ ] **Step 1: Add the summary computation after the `empty( $rows )` return**

```php
		$summary_html = '';
		if ( function_exists( 'lunara_oscars_board_summary' ) && function_exists( 'lunara_oscars_render_board_summary' ) ) {
			$revised_label = '';
			if ( $revised_ts > 0 && function_exists( 'wp_date' ) ) {
				$revised_label = sprintf( /* translators: %s: month and day */ __( 'Revised %s', 'lunara-film' ), wp_date( 'M j', $revised_ts ) );
			}
			$summary_html = lunara_oscars_render_board_summary( lunara_oscars_board_summary( $rows ), $revised_label );
		}
```

and, inside the `foreach ( $posts as $pick )` loop, track the newest modified time (declare `$revised_ts = 0;` beside `$ceremony_year = 0;`):

```php
			if ( function_exists( 'get_post_modified_time' ) ) {
				$modified = (int) get_post_modified_time( 'U', true, $pick_id );
				if ( $modified > $revised_ts ) {
					$revised_ts = $modified;
				}
			}
```

- [ ] **Step 2: Emit it above the list**

Between the header's closing `</div>` and `<ol class="lunara-oscars-board-list">`:

```php
			<?php echo $summary_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- renderer escapes internally. ?>
```

- [ ] **Step 3: Run the board contract and the dynamic contract**

Run: `pwsh tests/oscars-portal-board-contract.ps1` — expect pass (the harness lacks the summary functions, so the guard skips them; empty-board still returns `''`).
Run: `pwsh tests/oscars-portal-dynamic-contract.ps1` — the two board assertions now pass.

- [ ] **Step 4: Commit**

```bash
git add inc/oscars-portal.php
git commit -m "Theme 3.2.59: board summary chips above the prediction board"
```

### Task 3: Template, Customizer, and flush wiring

**Files:**
- Modify: `page-oscars.php` (hero stat grid, Deep Cuts header)
- Modify: `inc/customizer.php` (`$oscars_portal_controls`)
- Modify: `inc/home-sections.php` (`lunara_flush_oscars_home_transients`)

- [ ] **Step 1: Season clock under the hero stat grid**

After the `</div>` that closes `.lunara-oscars-portal-stat-grid` in `page-oscars.php`:

```php
                    <?php if ( function_exists( 'lunara_oscars_render_season_clock' ) && function_exists( 'lunara_oscars_season_clock' ) ) { echo lunara_oscars_render_season_clock( lunara_oscars_season_clock( get_theme_mod( 'lunara_oscars_next_ceremony_date', '' ) ) ); } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- renderer escapes internally. ?>
```

- [ ] **Step 2: Today's Pull above the facts grid**

Between the Deep Cuts `.lunara-home-section-header` block and `<div class="lunara-oscars-portal-facts-grid">`:

```php
                <?php if ( function_exists( 'lunara_oscars_render_todays_pull' ) && function_exists( 'lunara_get_oscars_todays_pull' ) ) { echo lunara_oscars_render_todays_pull( lunara_get_oscars_todays_pull() ); } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- renderer escapes internally. ?>
```

- [ ] **Step 3: Customizer control**

Append to `$oscars_portal_controls` in `inc/customizer.php`:

```php
        array(
            'setting'     => 'lunara_oscars_next_ceremony_date',
            'default'     => '',
            'label'       => __( 'Next Ceremony Date', 'lunara-film' ),
            'type'        => 'date',
            'sanitize'    => 'lunara_oscars_sanitize_ceremony_date',
            'description' => __( 'Drives the season clock in the portal hero. Leave empty to hide it; the clock retires itself two weeks after the date.', 'lunara-film' ),
        ),
```

- [ ] **Step 4: Flush key**

Inside the `foreach ( array( $today, ( $today + 1 ) % 366 ) as $day )` loop in `lunara_flush_oscars_home_transients()`:

```php
        delete_transient( 'lunara_oscars_todays_pull_v1_' . $day );
```

- [ ] **Step 5: Lint and run contracts**

Run: `php -l page-oscars.php && php -l inc/customizer.php && php -l inc/home-sections.php`
Run: `pwsh tests/oscars-portal-dynamic-contract.ps1` — remaining failures are runtime and shell only.
Run: `php tests/oscars-portal-studio-runtime.php` — expect pass (identity literals untouched).

- [ ] **Step 6: Commit**

```bash
git add page-oscars.php inc/customizer.php inc/home-sections.php
git commit -m "Theme 3.2.59: season clock, Today's Pull, ceremony date mod, flush key"
```

### Task 4: Public runtime portal branch

**Files:**
- Modify: `assets/js/lunara-public-runtime.js` (reveal IIFE, counter IIFE, new portal IIFE)

- [ ] **Step 1: Reveal IIFE**

Replace the block from `var isFrontPage=` through `document.querySelectorAll('.lunara-reveal').forEach(function(el){obs.observe(el);});` with:

```js
        var isFrontPage=document.body.classList.contains('home')||document.querySelector('.lunara-front-page');
        var isPortal=document.body.classList.contains('lunara-oscars-portal-page');
        var isPluginPage=document.querySelector('.aat-hub-page,.aat-entity-page');
        var revealSels=[];
        var staggerSels=[];
        if(isFrontPage){
            revealSels=[
                '.lunara-front-page>.lunara-home-section','.lunara-review-grid-card','.lunara-review-feature-card',
                '.lunara-poster-card','.lunara-ledger-card','.lunara-dispatch-archive-card'
            ];
            staggerSels=[
                '.lunara-review-grid','.lunara-review-related-grid'
            ];
        }
        /* Oscars portal (3.2.59): every section below the hero, and the cards inside its grids. Checked before the plugin-page branch because the portal embeds the plugin hub. */
        if(isPortal){
            revealSels=[
                '#primary.lunara-oscars-portal>.lunara-home-section'+':not(.lunara-oscars-portal-slot-hero)',
                '.lunara-oscars-portal-link-card','.lunara-oscars-portal-spotlight-card','.lunara-oscars-portal-title-card',
                '.lunara-oscars-research-card','.lunara-oscars-portal-fact-card','.lunara-oscars-board-row',
                '.lunara-ceremony-winners-grid>.lunara-ceremony-winner-card'
            ];
            staggerSels=[
                '.lunara-oscars-portal-link-grid','.lunara-oscars-portal-spotlight-grid','.lunara-oscars-portal-title-grid',
                '.lunara-oscars-research-card-grid','.lunara-oscars-portal-facts-grid','.lunara-oscars-board-list','.lunara-ceremony-winners-grid'
            ];
        }else if(isPluginPage){
            revealSels=['.aat-entity-status-banner','.aat-stat','.aat-timeline-card'];
            staggerSels=['.aat-stats-bar','.aat-timeline-list'];
        }
        if(!revealSels.length)return;
        revealSels.forEach(function(s){
            document.querySelectorAll(s).forEach(function(el){el.classList.add('lunara-reveal');});
        });
        staggerSels.forEach(function(s){
            document.querySelectorAll(s).forEach(function(el){el.classList.add('lunara-reveal-stagger');});
        });
		if(isFrontPage){
			document.querySelectorAll('.lunara-reveal').forEach(function(el){el.classList.add('is-visible');});
			return;
		}
        var obs=new IntersectionObserver(function(entries){
            entries.forEach(function(entry){
                if(entry.isIntersecting){
                    entry.target.classList.add('is-visible');
                    obs.unobserve(entry.target);
                }
            });
		},{threshold:0.01,rootMargin:'240px 0px'});
        /* Anything already on screen shows at once; a safety timer makes sure no observer can strand a section. */
        var vh=window.innerHeight||0;
        document.querySelectorAll('.lunara-reveal').forEach(function(el){
            if(el.getBoundingClientRect().top<vh*0.9){el.classList.add('is-visible');}else{obs.observe(el);}
        });
        window.setTimeout(function(){document.querySelectorAll('.lunara-reveal:not(.is-visible)').forEach(function(el){el.classList.add('is-visible');});},6000);
```

- [ ] **Step 2: Counters**

Replace `var stats=document.querySelectorAll('.aat-stat-number');` with:

```js
        var stats=document.querySelectorAll('.aat-stat-number,.lunara-oscars-portal-stat-value,.lunara-oscars-portal-fact-value,.lunara-oscars-season-days');
```

and after `var el=entry.target,text=el.textContent.trim();` add:

```js
                if(/^\d{4}$/.test(text))return;
```

- [ ] **Step 3: Portal IIFE (append before the view-transition IIFE)**

```js
(function(){
        var body=document.body;if(!body||!body.classList.contains('lunara-oscars-portal-page'))return;
        /* Season clock: recompute at view time so anonymous cached HTML never shows yesterday's count. */
        var labels={settled:'Ceremony settled',tonight:'Ceremony night',final:'Final stretch',season:'Awards season',countdown:'Ceremony countdown'};
        document.querySelectorAll('[data-lunara-season-clock]').forEach(function(el){
            var m=/^(\d{4})-(\d{2})-(\d{2})$/.exec(el.getAttribute('data-lunara-season-clock')||'');if(!m)return;
            var now=new Date();
            var days=Math.round((Date.UTC(+m[1],+m[2]-1,+m[3])-Date.UTC(now.getFullYear(),now.getMonth(),now.getDate()))/86400000);
            if(days<-14){el.hidden=true;return;}
            var num=el.querySelector('[data-lunara-season-days]'),unit=el.querySelector('[data-lunara-season-unit]'),ph=el.querySelector('.lunara-oscars-season-phase');
            if(!num||!unit)return;
            var phase=days<0?'settled':days===0?'tonight':days<=30?'final':days<=120?'season':'countdown';
            if(ph)ph.textContent=labels[phase];
            el.className=el.className.replace(/\bis-phase-\S+/,'is-phase-'+phase);
            if(days>0){num.textContent=String(days);unit.textContent=days===1?'day to the ceremony':'days to the ceremony';}
            else if(days===0){num.textContent='Tonight';unit.textContent='the envelopes open';}
            else{num.textContent=String(-days);unit.textContent=days===-1?'day since the ceremony':'days since the ceremony';}
        });
        /* Navigator scroll-spy: the pill whose section owns the middle of the viewport is current. */
        var nav=document.querySelector('.lunara-oscars-navigator');
        if(!nav||!('IntersectionObserver' in window))return;
        var links=Array.prototype.slice.call(nav.querySelectorAll('a[href^="#"]')),map={},targets=[];
        links.forEach(function(a){var id=a.getAttribute('href').slice(1);var t=id?document.getElementById(id):null;if(t){map[id]=a;targets.push(t);}});
        if(!targets.length)return;
        var current='';
        var spy=new IntersectionObserver(function(entries){
            entries.forEach(function(en){
                if(!en.isIntersecting||current===en.target.id)return;
                current=en.target.id;
                links.forEach(function(a){a.removeAttribute('aria-current');});
                map[current].setAttribute('aria-current','location');
            });
        },{rootMargin:'-35% 0px -55% 0px',threshold:0});
        targets.forEach(function(t){spy.observe(t);});
    })();
```

- [ ] **Step 4: Syntax and contract**

Run: `node --check assets/js/lunara-public-runtime.js`
Run: `pwsh tests/oscars-portal-dynamic-contract.ps1` — remaining failures are shell rules only.
Run: `pwsh tests/performance-payload-budget.ps1` — expect pass.

- [ ] **Step 5: Commit**

```bash
git add assets/js/lunara-public-runtime.js
git commit -m "Theme 3.2.59: portal reveals, counters, scroll-spy, live season clock"
```

### Task 5: Shell CSS

**Files:**
- Modify: `assets/css/lunara-shell.css` (append a final block)

- [ ] **Step 1: Append**

```css

/* ═══════════════════════════════════════════════════════════════════
   Oscars portal dynamic layer (Theme 3.2.59)
   Season clock, board summary, Today's Pull, navigator scroll-spy,
   and fine-pointer hover depth. New classes only; nothing here
   re-fights an earlier authority.
   ═══════════════════════════════════════════════════════════════════ */
body.lunara-oscars-portal-page .lunara-oscars-season-clock {
    align-items: baseline;
    border: 1px solid rgba(201, 169, 97, .34);
    border-radius: 16px;
    background: linear-gradient(135deg, rgba(201, 169, 97, .12), rgba(9, 20, 32, .78));
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, .05);
    color: rgba(248, 244, 234, .9);
    display: flex;
    flex-wrap: wrap;
    gap: 4px 12px;
    margin-top: clamp(12px, 1.4vw, 18px);
    padding: clamp(12px, 1.2vw, 16px) clamp(14px, 1.6vw, 20px);
}
body.lunara-oscars-portal-page .lunara-oscars-season-phase {
    border: 1px solid rgba(201, 169, 97, .5);
    border-radius: 999px;
    color: var(--lunara-gold-light, #e0c481);
    font-family: var(--lunara-font-label, sans-serif);
    font-size: clamp(.62rem, .22vw + .5rem, .78rem);
    letter-spacing: .18em;
    padding: 4px 10px;
    text-transform: uppercase;
    white-space: nowrap;
}
body.lunara-oscars-portal-page .lunara-oscars-season-days {
    color: #ffe28b;
    font-size: clamp(1.7rem, 1.4vw + 1rem, 2.9rem);
    font-weight: 700;
    letter-spacing: -.01em;
    line-height: 1;
}
body.lunara-oscars-portal-page .lunara-oscars-season-unit {
    font-size: clamp(.9rem, .3vw + .7rem, 1.1rem);
}
body.lunara-oscars-portal-page .lunara-oscars-season-date {
    color: rgba(244, 239, 227, .62);
    font-size: clamp(.76rem, .22vw + .62rem, .92rem);
    margin-left: auto;
    white-space: nowrap;
}
body.lunara-oscars-portal-page .lunara-oscars-season-clock.is-phase-tonight {
    border-color: rgba(255, 226, 139, .8);
    box-shadow: 0 0 0 1px rgba(255, 226, 139, .25), 0 12px 40px rgba(201, 169, 97, .22);
}
body.lunara-oscars-portal-page .lunara-oscars-season-clock.is-phase-settled {
    opacity: .82;
}
@media (prefers-reduced-motion: no-preference) {
    body.lunara-oscars-portal-page .lunara-oscars-season-clock.is-phase-tonight .lunara-oscars-season-days {
        animation: lunara-oscars-envelope 2.4s ease-in-out infinite;
    }
    @keyframes lunara-oscars-envelope {
        0%, 100% { text-shadow: 0 0 0 rgba(255, 226, 139, 0); }
        50% { text-shadow: 0 0 18px rgba(255, 226, 139, .55); }
    }
}
body.lunara-oscars-portal-page .lunara-oscars-board-summary {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    list-style: none;
    margin: clamp(14px, 1.6vw, 22px) 0 0;
    padding: 0;
}
body.lunara-oscars-portal-page .lunara-oscars-board-chip {
    border: 1px solid rgba(201, 169, 97, .28);
    border-radius: 999px;
    color: rgba(244, 239, 227, .8);
    font-family: var(--lunara-font-label, sans-serif);
    font-size: clamp(.68rem, .24vw + .54rem, .84rem);
    letter-spacing: .1em;
    padding: 6px 12px;
    text-transform: uppercase;
    white-space: nowrap;
}
body.lunara-oscars-portal-page .lunara-oscars-board-chip strong {
    color: #ffe28b;
    font-weight: 700;
    margin-right: 2px;
}
body.lunara-oscars-portal-page .lunara-oscars-board-chip.is-total {
    background: rgba(201, 169, 97, .14);
    border-color: rgba(201, 169, 97, .5);
}
body.lunara-oscars-portal-page .lunara-oscars-board-chip.is-status-won {
    border-color: rgba(120, 220, 160, .5);
}
body.lunara-oscars-portal-page .lunara-oscars-board-chip.is-status-won strong {
    color: #9ff0c0;
}
body.lunara-oscars-portal-page .lunara-oscars-board-chip.is-status-lost {
    border-color: rgba(244, 239, 227, .18);
    color: rgba(244, 239, 227, .55);
}
body.lunara-oscars-portal-page .lunara-oscars-board-chip.is-status-lost strong {
    color: rgba(244, 239, 227, .7);
}
body.lunara-oscars-portal-page .lunara-oscars-board-chip.is-revised {
    border-style: dashed;
    margin-left: auto;
    text-transform: none;
    letter-spacing: .04em;
}
body.lunara-oscars-portal-page .lunara-oscars-todays-pull {
    border: 1px solid rgba(201, 169, 97, .34);
    border-radius: 18px;
    background:
        radial-gradient(circle at top right, rgba(241, 204, 110, .16), transparent 46%),
        linear-gradient(160deg, rgba(18, 32, 49, .96), rgba(7, 15, 26, .96));
    display: grid;
    gap: 8px;
    margin: 0 0 clamp(14px, 1.6vw, 20px);
    padding: clamp(16px, 1.8vw, 24px) clamp(18px, 2vw, 28px);
}
body.lunara-oscars-portal-page .lunara-oscars-todays-pull-kicker,
body.lunara-oscars-portal-page .lunara-oscars-todays-pull-meta {
    align-items: center;
    color: rgba(244, 239, 227, .72);
    display: flex;
    flex-wrap: wrap;
    font-family: var(--lunara-font-label, sans-serif);
    font-size: clamp(.7rem, .24vw + .56rem, .86rem);
    gap: 6px 10px;
    letter-spacing: .12em;
    margin: 0;
    text-transform: uppercase;
}
body.lunara-oscars-portal-page .lunara-oscars-todays-pull-badge {
    background: rgba(201, 169, 97, .18);
    border: 1px solid rgba(201, 169, 97, .5);
    border-radius: 999px;
    color: var(--lunara-gold-light, #e0c481);
    padding: 3px 10px;
}
body.lunara-oscars-portal-page .lunara-oscars-todays-pull-sep {
    color: rgba(201, 169, 97, .5);
}
body.lunara-oscars-portal-page .lunara-oscars-todays-pull-title {
    font-size: clamp(1.3rem, 1.1vw + .8rem, 2.3rem);
    line-height: 1.08;
    margin: 0;
    text-wrap: balance;
}
body.lunara-oscars-portal-page .lunara-oscars-todays-pull a {
    color: inherit;
    text-decoration: none;
    transition: color .16s ease;
}
body.lunara-oscars-portal-page .lunara-oscars-todays-pull-title a {
    color: #ffe28b;
}
body.lunara-oscars-portal-page .lunara-oscars-todays-pull a:hover,
body.lunara-oscars-portal-page .lunara-oscars-todays-pull a:focus-visible {
    color: #fff;
}
body.lunara-oscars-portal-page .lunara-oscars-navigator a[aria-current] {
    background: rgba(201, 169, 97, .2);
    box-shadow: inset 0 0 0 1px rgba(201, 169, 97, .55);
    color: #ffe28b;
}
@media (hover: hover) and (pointer: fine) {
    body.lunara-oscars-portal-page .lunara-oscars-portal-title-media,
    body.lunara-oscars-portal-page .lunara-oscars-spotlight-poster,
    body.lunara-oscars-portal-page .lunara-ceremony-winner-poster {
        transition: transform .45s cubic-bezier(.2, .7, .2, 1), filter .45s ease;
        will-change: transform;
    }
    body.lunara-oscars-portal-page .lunara-oscars-portal-title-card:hover .lunara-oscars-portal-title-media,
    body.lunara-oscars-portal-page .lunara-oscars-portal-spotlight-card:hover .lunara-oscars-spotlight-poster,
    body.lunara-oscars-portal-page .lunara-ceremony-winner-card:hover .lunara-ceremony-winner-poster {
        filter: saturate(1.08) brightness(1.06);
        transform: translateY(-4px) scale(1.025);
    }
    body.lunara-oscars-portal-page .lunara-oscars-board-row {
        transition: border-color .2s ease, transform .2s ease;
    }
    body.lunara-oscars-portal-page .lunara-oscars-board-row:hover {
        border-color: rgba(201, 169, 97, .55);
        transform: translateY(-2px);
    }
}
@media (max-width: 620px) {
    body.lunara-oscars-portal-page .lunara-oscars-season-date,
    body.lunara-oscars-portal-page .lunara-oscars-board-chip.is-revised {
        margin-left: 0;
    }
}
@media (prefers-reduced-motion: reduce) {
    body.lunara-oscars-portal-page .lunara-oscars-portal-title-media,
    body.lunara-oscars-portal-page .lunara-oscars-spotlight-poster,
    body.lunara-oscars-portal-page .lunara-ceremony-winner-poster,
    body.lunara-oscars-portal-page .lunara-oscars-board-row {
        transition: none;
    }
}
```

- [ ] **Step 2: Brace balance and contracts**

Run: `pwsh tests/oscars-portal-dynamic-contract.ps1` — expect pass.
Run: `pwsh tests/performance-payload-budget.ps1` — expect pass.

- [ ] **Step 3: Commit**

```bash
git add assets/css/lunara-shell.css
git commit -m "Theme 3.2.59: shell rules for the portal dynamic layer"
```

### Task 6: Offline render and the rotating winners carousel

**Files:**
- Create (scratch only, not committed): a rebuilt copy of the live page with local assets under the session scratchpad.
- Modify (only if the render proves a CSS cause): `assets/css/lunara-shell.css`.

- [ ] **Step 1: Rebuild the live page offline**

Fetch `https://lunarafilm.com/oscars/?jb-disable-modules=all`, rewrite every `wp-content/themes/lunara-theme-blocks-20260513-2300/` URL to the working tree, serve the scratch directory with `python -m http.server`, open in the Browser pane at 1440 and 390.

- [ ] **Step 2: Diagnose the carousel**

Inspect `.lunara-oscars-winner-carousel-track` children: measure the first card's box, its `has-poster` state, and the track's `scroll-padding` / `padding`. Record the cause in the session log. Fix in the shell block from Task 5 only if the cause is CSS; if it is data (a first card with no visual), suppress in `lunara_get_rotating_oscars_ceremony_showcase()` by preferring posters and log it.

- [ ] **Step 3: Verify the dynamic layer renders**

Confirm: sections reveal on scroll, navigator pill lights, hero stats count up, no horizontal overflow at 390 or 2560. Screenshot before and after.

- [ ] **Step 4: Commit any fix**

```bash
git add assets/css/lunara-shell.css inc/home-sections.php
git commit -m "Theme 3.2.59: rotating winners carousel fix from offline render"
```

### Task 7: Release identity, version sweep, changelog, session log

**Files:**
- Rename: `tests/release-identity-3-2-58.ps1` → `tests/release-identity-3-2-59.ps1` (`git mv`), update headings, prior version `3.2.58`, coverage patterns.
- Modify: `style.css:7` (`Version: 3.2.59`), every `3.2.58` in `tests/`, `assets/css/`, `inc/`.
- Modify: `docs/CHANGELOG.md` (new `## 2026-09-05 — Theme 3.2.59 Oscars Portal Dynamic Layer` entry above the 3.2.58 entry), `docs/SESSION-LOG.md` (new top entry).

- [ ] **Step 1: Sweep**

```bash
grep -rl '3\.2\.58' tests assets/css inc style.css | xargs sed -i 's/3\.2\.58/3.2.59/g'
git mv tests/release-identity-3-2-58.ps1 tests/release-identity-3-2-59.ps1
sed -i "s/@('3', '2', '57')/@('3', '2', '58')/" tests/release-identity-3-2-59.ps1
grep -rn '3\.2\.58' tests assets/css inc style.css   # must print nothing
```

- [ ] **Step 2: Update the identity contract's headings and coverage patterns** to the 3.2.59 changelog heading `## 2026-09-05 — Theme 3.2.59 Oscars Portal Dynamic Layer` and session heading `## 2026-09-05 — Theme 3.2.59 Oscars portal dynamic layer and local candidate close`, with coverage patterns: `season clock`, `board summary`, `Today's Pull`, `scroll-spy`, `safety timer`, `rendered offline`, `Not changed`.

- [ ] **Step 3: Write the changelog and session-log entries** in the shape of the 3.2.58 entries (headline, verified live state, what shipped and why, commit ledger, gate ledger, corrections, logged not fixed, punch-list, whose move).

- [ ] **Step 4: Full gates**

```bash
for f in $(git ls-files '*.php'); do php -l "$f" >/dev/null || echo "FAIL $f"; done
for f in tests/*.ps1; do pwsh "$f" >/dev/null 2>&1 || echo "FAIL $f"; done
for f in tests/*.php; do php "$f" >/dev/null 2>&1 || echo "FAIL $f"; done
```

- [ ] **Step 5: Mutation tests on the new contract** (each from a `cp` backup, restored and `cmp`-verified): remove the loader require; remove the hero guard; drop the portal reveal branch; remove the shell season-clock block. Each must go RED.

- [ ] **Step 6: Commit**

```bash
git add -A
git commit -m "Theme 3.2.59: release identity, version sweep, changelog, session log"
```
