<?php
/**
 * Oscars portal dynamic layer (Theme 3.2.59).
 *
 * Pure helpers that make /oscars/ read as live: a season clock in the hero,
 * a status summary above the prediction board, and a date-seeded ledger
 * pull in Deep Cuts. Every renderer returns the exact empty string when it
 * has nothing to say, so a site with no ceremony date, no picks, or no
 * ledger keeps the 3.2.58 markup byte-for-byte. Output is anonymous-
 * cacheable: no nonce, cookie, or user-conditional branch anywhere here.
 *
 * Proven by tests/fixtures/oscars-portal-dynamic-harness.php, which
 * requires this file directly against escaping stubs, and pinned by
 * tests/oscars-portal-dynamic-contract.ps1.
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
	 * Phases by days to the ceremony: countdown (over 120), season (31 to
	 * 120), final (1 to 30), tonight (0), settled (-1 to -14). Past -14 the
	 * clock returns empty so a stale date retires itself without an edit.
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
	 * Render the hero season clock. The runtime recomputes the count at view
	 * time from data-lunara-season-clock, so cached HTML never goes stale.
	 *
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
	 * @param array<int,array<string,mixed>> $rows Board rows carrying a status key.
	 * @return array<string,mixed> total and chips, or empty when there are no rows.
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
				$chips[] = array(
					'status' => $status,
					'count'  => $counts[ $status ],
				);
			}
		}

		return array(
			'total' => $total,
			'chips' => $chips,
		);
	}
}

if ( ! function_exists( 'lunara_oscars_board_status_label' ) ) {
	/**
	 * Human label for a pick status, from the theme's status map when loaded.
	 *
	 * @param string $status Status key.
	 * @return string
	 */
	function lunara_oscars_board_status_label( $status ) {
		$status = sanitize_key( $status );
		if ( function_exists( 'lunara_oscar_pick_status_labels' ) ) {
			$labels = lunara_oscar_pick_status_labels();
			if ( is_array( $labels ) && isset( $labels[ $status ] ) ) {
				return (string) $labels[ $status ];
			}
		}

		return ucwords( str_replace( '_', ' ', $status ) );
	}
}

if ( ! function_exists( 'lunara_oscars_render_board_summary' ) ) {
	/**
	 * Render the chip row above the prediction board list.
	 *
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
	 * One winning ledger row for today, cached for the day and cleared by
	 * lunara_flush_oscars_home_transients() on import.
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
		$ordinal  = ( $aat && method_exists( $aat, 'ordinal' ) ) ? (string) $aat->ordinal( $ceremony ) : (string) $ceremony;
		$split    = static function ( $value ) {
			return array_values( array_filter( array_map( 'trim', explode( '|', (string) $value ) ) ) );
		};
		$film_ids = $split( $row['film_id'] ?? '' );
		$name_ids = $split( $row['nominee_ids'] ?? '' );
		$films    = $split( $row['film'] ?? '' );
		$canon    = (string) ( $row['canonical_category'] ?? '' );
		$entity   = static function ( $id ) use ( $aat ) {
			if ( '' === $id ) {
				return '';
			}
			if ( $aat && method_exists( $aat, 'get_entity_url' ) ) {
				return (string) $aat->get_entity_url( $id );
			}
			return home_url( '/oscars/title/' . rawurlencode( $id ) . '/' );
		};

		$pull = array(
			'ceremony_label' => $ordinal . ' ' . __( 'Academy Awards', 'lunara-film' ),
			'ceremony_url'   => ( $aat && method_exists( $aat, 'get_ceremony_url' ) ) ? (string) $aat->get_ceremony_url( $ceremony ) : home_url( '/oscars/ceremony/' . $ceremony . '/' ),
			'year_label'     => trim( (string) ( $row['year'] ?? '' ) ),
			'category'       => ( $aat && method_exists( $aat, 'format_category_display' ) ) ? (string) $aat->format_category_display( $canon ) : $canon,
			'category_url'   => ( $aat && method_exists( $aat, 'get_category_url' ) ) ? (string) $aat->get_category_url( $canon ) : '',
			'film'           => $films[0] ?? '',
			'film_url'       => $entity( $film_ids[0] ?? '' ),
			'name'           => trim( (string) ( $row['name'] ?? '' ) ),
			'name_url'       => $entity( $name_ids[0] ?? '' ),
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
	 * Render the Today's Pull card above the Deep Cuts facts grid.
	 *
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
			if ( '' !== $url ) {
				return '<a class="' . esc_attr( $class ) . '" href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
			}
			return '<span class="' . esc_attr( $class ) . '">' . esc_html( $label ) . '</span>';
		};
		$sep = '<span class="lunara-oscars-todays-pull-sep" aria-hidden="true">/</span>';

		$meta = array_filter(
			array(
				$link( $pull['ceremony_label'] ?? '', $pull['ceremony_url'] ?? '', 'lunara-oscars-todays-pull-ceremony' ),
				'' !== trim( (string) ( $pull['year_label'] ?? '' ) ) ? '<span class="lunara-oscars-todays-pull-year">' . esc_html( (string) $pull['year_label'] ) . '</span>' : '',
			)
		);
		$detail = array_filter(
			array(
				$link( $pull['category'] ?? '', $pull['category_url'] ?? '', 'lunara-oscars-todays-pull-category' ),
				$link( $pull['name'] ?? '', $pull['name_url'] ?? '', 'lunara-oscars-todays-pull-person' ),
			)
		);

		$html  = '<article class="lunara-oscars-todays-pull">';
		$html .= '<p class="lunara-oscars-todays-pull-kicker"><span class="lunara-oscars-todays-pull-badge">' . esc_html( __( "Today's Pull", 'lunara-film' ) ) . '</span>' . implode( $sep, $meta ) . '</p>';
		$html .= '<h3 class="lunara-oscars-todays-pull-title">' . $link( $pull['film'], $pull['film_url'] ?? '', 'lunara-oscars-todays-pull-film' ) . '</h3>';
		if ( ! empty( $detail ) ) {
			$html .= '<p class="lunara-oscars-todays-pull-meta">' . implode( $sep, $detail ) . '</p>';
		}

		return $html . '</article>';
	}
}
