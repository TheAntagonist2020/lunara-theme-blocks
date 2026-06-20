<?php
/**
 * Journal Archive — Lunara Film
 *
 * Renders /journal/ (the journal CPT archive) as a deliberate Lunara lane:
 * type filters, lead entry, supporting grid.  Without this template, the site
 * falls through to archive.php which expects standard WordPress posts.
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

global $wp_query, $post;

$kicker      = function_exists( 'lunara_theme_mod_text' )
	? lunara_theme_mod_text( 'lunara_journal_archive_kicker', __( 'The Lunara Journal', 'lunara-film' ) )
	: __( 'The Lunara Journal', 'lunara-film' );
$title       = function_exists( 'lunara_theme_mod_text' )
	? lunara_theme_mod_text( 'lunara_journal_archive_title', __( 'Journal', 'lunara-film' ) )
	: __( 'Journal', 'lunara-film' );
$copy        = function_exists( 'lunara_theme_mod_text' )
	? lunara_theme_mod_text( 'lunara_journal_archive_copy', '' )
	: '';
$copy        = '';

if ( 'The Lunara Journal' === trim( (string) $kicker ) ) {
	$kicker = __( 'Journal', 'lunara-film' );
}

if ( 'Journal' === trim( (string) $title ) ) {
	$title = __( 'Lunara Journal', 'lunara-film' );
}

if ( '' !== $copy && 0 === strpos( trim( (string) $copy ), 'News, reactions, essays, podcasts, and dispatches' ) ) {
	$copy = '';
}

$show_hero       = function_exists( 'lunara_journal_archive_section_is_enabled' ) ? lunara_journal_archive_section_is_enabled( 'hero' ) : true;
$show_filters    = function_exists( 'lunara_journal_archive_section_is_enabled' ) ? lunara_journal_archive_section_is_enabled( 'filters' ) : true;
$show_grid       = function_exists( 'lunara_journal_archive_section_is_enabled' ) ? lunara_journal_archive_section_is_enabled( 'grid' ) : true;
$show_pagination = function_exists( 'lunara_journal_archive_section_is_enabled' ) ? lunara_journal_archive_section_is_enabled( 'pagination' ) : true;
$current_sort    = function_exists( 'lunara_get_editorial_archive_sort' ) ? lunara_get_editorial_archive_sort() : 'date_desc';
$sort_options    = function_exists( 'lunara_get_editorial_archive_sort_options' ) ? lunara_get_editorial_archive_sort_options() : array();
$sort_base_url   = remove_query_arg( array( 'sort', 'paged' ), get_pagenum_link( 1 ) );
$journal_counts  = wp_count_posts( 'journal' );
$journal_total   = isset( $journal_counts->publish ) ? (int) $journal_counts->publish : 0;
$latest_journal  = get_posts( array(
	'post_type'      => 'journal',
	'post_status'    => 'publish',
	'posts_per_page' => 1,
	'orderby'        => 'modified',
	'order'          => 'DESC',
	'fields'         => 'ids',
	'no_found_rows'  => true,
) );
$latest_label    = ! empty( $latest_journal[0] ) ? get_the_modified_date( 'F j, Y g:i A', (int) $latest_journal[0] ) : '';
$journal_archive_posts = ( $wp_query instanceof WP_Query && ! empty( $wp_query->posts ) && is_array( $wp_query->posts ) )
	? $wp_query->posts
	: array();

if ( function_exists( 'lunara_get_curated_journal_lead_id' ) && is_post_type_archive( 'journal' ) && ! is_paged() && 'date_desc' === $current_sort ) {
	$journal_lead_id = lunara_get_curated_journal_lead_id();
	$journal_lead    = $journal_lead_id ? get_post( $journal_lead_id ) : null;

	if ( $journal_lead instanceof WP_Post && 'journal' === $journal_lead->post_type && 'publish' === $journal_lead->post_status ) {
		$without_lead = array();

		foreach ( $journal_archive_posts as $journal_archive_post ) {
			if ( ! ( $journal_archive_post instanceof WP_Post ) || (int) $journal_archive_post->ID === (int) $journal_lead_id ) {
				continue;
			}

			$without_lead[] = $journal_archive_post;
		}

		array_unshift( $without_lead, $journal_lead );
		$journal_archive_posts = $without_lead;
	}
}

// Build a type-filter row from journal_type terms.
$type_terms = get_terms( array(
	'taxonomy'   => 'journal_type',
	'hide_empty' => true,
	'orderby'    => 'count',
	'order'      => 'DESC',
) );

$latest_journal_url = ! empty( $latest_journal[0] ) ? get_permalink( (int) $latest_journal[0] ) : get_post_type_archive_link( 'journal' );
$trailer_lane_url   = get_post_type_archive_link( 'journal' );

if ( $type_terms && ! is_wp_error( $type_terms ) ) {
	foreach ( $type_terms as $type_term ) {
		if ( ! $type_term instanceof WP_Term || 'trailer' !== sanitize_title( $type_term->slug ) ) {
			continue;
		}

		$term_link = get_term_link( $type_term );

		if ( ! is_wp_error( $term_link ) ) {
			$trailer_lane_url = $term_link;
		}

		break;
	}
}

$reviews_archive_url = get_post_type_archive_link( 'review' );
$journal_retention_cards = array(
	array(
		'kicker' => __( 'Latest File', 'lunara-film' ),
		'title'  => __( 'Open the newest desk entry', 'lunara-film' ),
		'copy'   => __( 'Stay with the freshest reported movement before it settles into the wider conversation.', 'lunara-film' ),
		'url'    => $latest_journal_url,
	),
	array(
		'kicker' => __( 'Trailer Lane', 'lunara-film' ),
		'title'  => __( 'Watch what just moved', 'lunara-film' ),
		'copy'   => __( 'Jump to trailer-backed files where the image, hook, and industry signal belong together.', 'lunara-film' ),
		'url'    => $trailer_lane_url,
	),
	array(
		'kicker' => __( 'Review Desk', 'lunara-film' ),
		'title'  => __( 'Move into the criticism', 'lunara-film' ),
		'copy'   => __( 'Follow the conversation from quick dispatches into full Lunara reviews and context.', 'lunara-film' ),
		'url'    => $reviews_archive_url ? $reviews_archive_url : home_url( '/reviews/' ),
	),
);
?>

<main class="lunara-archive-page lunara-journal-archive-page">

	<?php if ( $show_hero ) : ?>
	<header class="lunara-archive-hero lunara-journal-archive-hero lunara-journal-archive-slot-hero">
		<p class="lunara-archive-hero-kicker"><?php echo esc_html( $kicker ); ?></p>
		<h1 class="lunara-archive-hero-title"><?php echo esc_html( $title ); ?></h1>
		<?php if ( '' !== $copy ) : ?>
			<p class="lunara-archive-hero-copy"><?php echo esc_html( $copy ); ?></p>
		<?php endif; ?>

	</header>
	<?php endif; ?>

	<div class="lunara-journal-archive-deskbar" aria-label="<?php esc_attr_e( 'Journal desk status', 'lunara-film' ); ?>">
		<span><strong><?php esc_html_e( 'On the desk:', 'lunara-film' ); ?></strong> <?php echo esc_html( sprintf( _n( '%d file', '%d files', $journal_total, 'lunara-film' ), $journal_total ) ); ?></span>
		<?php if ( '' !== $latest_label ) : ?>
			<span><strong><?php esc_html_e( 'Latest file:', 'lunara-film' ); ?></strong> <?php echo esc_html( $latest_label ); ?></span>
		<?php endif; ?>
		<?php if ( $type_terms && ! is_wp_error( $type_terms ) ) : ?>
			<span><strong><?php esc_html_e( 'Desk mix:', 'lunara-film' ); ?></strong> <?php echo esc_html( sprintf( _n( '%d lane', '%d lanes', count( $type_terms ), 'lunara-film' ), count( $type_terms ) ) ); ?></span>
		<?php endif; ?>
	</div>

	<?php if ( $show_filters && $type_terms && ! is_wp_error( $type_terms ) ) : ?>
		<nav class="lunara-journal-archive-filters lunara-journal-archive-slot-filters" aria-label="<?php esc_attr_e( 'Filter by type', 'lunara-film' ); ?>">
			<a class="lunara-journal-filter-pill <?php echo is_post_type_archive( 'journal' ) ? 'is-active' : ''; ?>"
			   href="<?php echo esc_url( get_post_type_archive_link( 'journal' ) ); ?>">
				<?php esc_html_e( 'All', 'lunara-film' ); ?>
			</a>
			<?php
			$current_term = is_tax( 'journal_type' ) ? get_queried_object() : null;
			foreach ( $type_terms as $term ) :
				$is_active = $current_term && $current_term->term_id === $term->term_id;
				?>
				<a class="lunara-journal-filter-pill <?php echo $is_active ? 'is-active' : ''; ?>"
				   href="<?php echo esc_url( get_term_link( $term ) ); ?>">
					<?php echo esc_html( $term->name ); ?>
					<span class="lunara-journal-filter-count">(<?php echo intval( $term->count ); ?>)</span>
				</a>
			<?php endforeach; ?>
		</nav>
	<?php endif; ?>

	<?php if ( ! empty( $journal_archive_posts ) ) : ?>

		<?php if ( ! empty( $sort_options ) ) : ?>
		<div class="lunara-editorial-archive-toolbar lunara-journal-archive-toolbar">
			<div class="lunara-home-section-head lunara-editorial-archive-toolbar-head">
				<div>
					<p class="lunara-home-section-kicker"><?php esc_html_e( 'Desk Order', 'lunara-film' ); ?></p>
					<h2 class="lunara-section-title"><?php esc_html_e( 'Latest files from the desk', 'lunara-film' ); ?></h2>
				</div>
			</div>
			<div class="lunara-archive-sort" aria-label="<?php esc_attr_e( 'Sort journal archive', 'lunara-film' ); ?>">
				<?php foreach ( $sort_options as $sort_key => $sort_label ) : ?>
					<?php
					$is_active = $sort_key === $current_sort;
					$sort_url  = 'date_desc' === $sort_key ? $sort_base_url : add_query_arg( 'sort', rawurlencode( $sort_key ), $sort_base_url );
					?>
					<a class="lunara-archive-sort-link <?php echo $is_active ? 'is-active' : ''; ?>" href="<?php echo esc_url( $sort_url ); ?>"<?php echo $is_active ? ' aria-current="page"' : ''; ?>>
						<?php echo esc_html( $sort_label ); ?>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
		<?php endif; ?>

		<?php if ( $show_grid ) : ?>
		<section class="lunara-journal-archive-grid lunara-review-grid lunara-review-archive-uniform lunara-journal-archive-slot-grid">
			<?php
			$journal_card_index = 0;
			foreach ( $journal_archive_posts as $journal_archive_post ) :
				$post = $journal_archive_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
				setup_postdata( $post );
				$pid          = get_the_ID();
				$journal_card_index++;
				$entry_kicker = 1 === $journal_card_index ? __( 'Lead file', 'lunara-film' ) : __( 'From the desk', 'lunara-film' );
				$entry_type   = function_exists( 'lunara_get_dispatch_type_label' )
					? lunara_get_dispatch_type_label( $pid )
					: ( function_exists( 'lunara_get_journal_kicker' ) ? lunara_get_journal_kicker( $pid ) : __( 'Dispatch', 'lunara-film' ) );
				$entry_excerpt = has_excerpt( $pid )
					? wp_trim_words( get_the_excerpt( $pid ), 28 )
					: wp_trim_words( wp_strip_all_tags( get_post_field( 'post_content', $pid ) ), 28 );
				$updated_label = function_exists( 'lunara_get_editorial_card_updated_label' )
					? lunara_get_editorial_card_updated_label( $pid )
					: '';
				$thumb_url     = has_post_thumbnail( $pid ) ? get_the_post_thumbnail_url( $pid, 'lunara-hero-spotlight' ) : '';
				$thumb_loading = $journal_card_index <= 2 ? 'eager' : 'lazy';
				$has_media     = '' !== $thumb_url;
				?>
				<article class="lunara-review-grid-card lunara-journal-archive-card<?php echo 1 === $journal_card_index ? ' is-lead' : ''; ?><?php echo $has_media ? ' has-media' : ' is-text-brief'; ?>">
					<a class="lunara-review-grid-link" href="<?php the_permalink(); ?>">
						<?php if ( $has_media ) : ?>
							<div class="lunara-review-grid-poster-wrap">
								<?php
								$journal_thumb_attrs = array(
									'class'    => 'lunara-review-grid-poster',
									'loading'  => $thumb_loading,
									'decoding' => 'async',
									'sizes'    => '(max-width: 640px) 92vw, (max-width: 980px) 46vw, (max-width: 1280px) 31vw, 380px',
								);

								if ( 1 === $journal_card_index ) {
									$journal_thumb_attrs['fetchpriority'] = 'high';
								}

								the_post_thumbnail(
									'lunara-hero-spotlight',
									$journal_thumb_attrs
								);
								?>
							</div>
						<?php endif; ?>
						<div class="lunara-review-grid-copy">
							<p class="lunara-review-grid-kicker"><?php echo esc_html( $entry_kicker ); ?></p>
							<?php if ( '' !== trim( (string) $entry_type ) ) : ?>
								<p class="lunara-dispatch-type lunara-journal-archive-card-type"><?php echo esc_html( $entry_type ); ?></p>
							<?php endif; ?>
							<?php
							if ( function_exists( 'lunara_render_journal_card_provenance' ) ) {
								lunara_render_journal_card_provenance( $pid, 'archive' );
							}
							?>
							<?php if ( function_exists( 'lunara_render_trailer_card_badge' ) ) : ?>
								<?php echo lunara_render_trailer_card_badge( $pid, 'journal-card' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<?php endif; ?>
							<h3 class="lunara-review-grid-title"><?php the_title(); ?></h3>
							<?php if ( $entry_excerpt ) : ?>
								<p class="lunara-review-grid-excerpt"><?php echo esc_html( $entry_excerpt ); ?></p>
							<?php endif; ?>
							<div class="lunara-review-grid-footer lunara-journal-archive-card-footer">
								<span class="lunara-review-grid-meta"><?php echo esc_html( get_the_date( 'F j, Y' ) ); ?></span>
								<?php if ( '' !== $updated_label ) : ?>
									<span class="lunara-review-grid-updated"><?php echo esc_html( $updated_label ); ?></span>
								<?php endif; ?>
								<span class="lunara-journal-archive-card-cta"><?php esc_html_e( 'Read file', 'lunara-film' ); ?></span>
							</div>
						</div>
					</a>
				</article>
			<?php endforeach; ?>
			<?php wp_reset_postdata(); ?>
		</section>
		<?php endif; ?>

		<section class="lunara-journal-archive-retention" aria-label="<?php esc_attr_e( 'Continue reading the Journal', 'lunara-film' ); ?>">
			<div class="lunara-journal-archive-retention-head">
				<p class="lunara-home-section-kicker"><?php esc_html_e( 'Desk Channels', 'lunara-film' ); ?></p>
				<h2 class="lunara-section-title"><?php esc_html_e( 'Keep the file moving', 'lunara-film' ); ?></h2>
			</div>
			<div class="lunara-journal-archive-retention-grid">
				<?php foreach ( $journal_retention_cards as $retention_card ) : ?>
					<a class="lunara-journal-archive-retention-card" href="<?php echo esc_url( $retention_card['url'] ); ?>">
						<span class="lunara-journal-archive-retention-kicker"><?php echo esc_html( $retention_card['kicker'] ); ?></span>
						<strong><?php echo esc_html( $retention_card['title'] ); ?></strong>
						<span><?php echo esc_html( $retention_card['copy'] ); ?></span>
					</a>
				<?php endforeach; ?>
			</div>
		</section>

		<?php if ( $show_pagination ) : ?>
		<nav class="lunara-archive-pagination lunara-journal-archive-slot-pagination" aria-label="<?php esc_attr_e( 'Journal pagination', 'lunara-film' ); ?>">
			<?php
			the_posts_pagination( array(
				'mid_size'  => 1,
				'add_args'  => 'date_desc' === $current_sort ? false : array( 'sort' => $current_sort ),
				'prev_text' => __( '← Newer', 'lunara-film' ),
				'next_text' => __( 'Older →', 'lunara-film' ),
			) );
			?>
		</nav>
		<?php endif; ?>

	<?php else : ?>

		<div class="lunara-archive-empty lunara-journal-archive-slot-grid">
			<p><?php esc_html_e( 'No journal entries yet. Check back soon.', 'lunara-film' ); ?></p>
		</div>

	<?php endif; ?>

</main>

<?php
get_footer();
