<?php
/**
 * Journal Archive — server-rendered from the last-valid Studio configuration.
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

global $wp_query, $post;

$journal_config = function_exists( 'lunara_journal_archive_studio_get_public_config' )
	? lunara_journal_archive_studio_get_public_config()
	: array();
$journal_defaults = function_exists( 'lunara_journal_archive_studio_defaults' )
	? lunara_journal_archive_studio_defaults()
	: array();
$journal_config   = array_replace_recursive( $journal_defaults, $journal_config );
$journal_labels   = $journal_config['labels'];
$kicker           = $journal_config['kicker'];
$title            = $journal_config['title'];
$copy             = $journal_config['deck'];

$journal_taxonomies = array( 'journal_section', 'journal_topic', 'journal_type' );
$current_term        = is_tax( $journal_taxonomies ) ? get_queried_object() : null;

if ( $current_term instanceof WP_Term ) {
	$taxonomy_kickers = array(
		'journal_section' => $journal_labels['taxonomy_section_kicker'],
		'journal_topic'   => $journal_labels['taxonomy_topic_kicker'],
		'journal_type'    => $journal_labels['taxonomy_type_kicker'],
	);
	$kicker = isset( $taxonomy_kickers[ $current_term->taxonomy ] )
		? $taxonomy_kickers[ $current_term->taxonomy ]
		: __( 'Journal File', 'lunara-film' );
	$title = $current_term->name;
	$copy  = trim( wp_strip_all_tags( term_description( $current_term ) ) );
}

$current_sort  = function_exists( 'lunara_get_editorial_archive_sort' ) ? lunara_get_editorial_archive_sort() : 'date_desc';
$sort_options  = function_exists( 'lunara_get_editorial_archive_sort_options' ) ? lunara_get_editorial_archive_sort_options() : array();
$sort_options  = array_replace(
	$sort_options,
	array(
		'date_desc'     => $journal_labels['sort_newest'],
		'date_asc'      => $journal_labels['sort_oldest'],
		'modified_desc' => $journal_labels['sort_updated'],
	)
);
$sort_base_url = remove_query_arg( array( 'sort', 'paged' ), get_pagenum_link( 1 ) );
$journal_counts = wp_count_posts( 'journal' );
$journal_total  = $current_term instanceof WP_Term && $wp_query instanceof WP_Query
	? (int) $wp_query->found_posts
	: ( isset( $journal_counts->publish ) ? (int) $journal_counts->publish : 0 );
$latest_journal = get_posts(
	array(
		'post_type'      => 'journal',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'orderby'        => 'modified',
		'order'          => 'DESC',
		'fields'         => 'ids',
		'no_found_rows'  => true,
	)
);
$latest_label = ! empty( $latest_journal[0] ) ? get_the_modified_date( 'F j, Y g:i A', (int) $latest_journal[0] ) : '';
$journal_archive_posts = ( $wp_query instanceof WP_Query && ! empty( $wp_query->posts ) && is_array( $wp_query->posts ) )
	? $wp_query->posts
	: array();

// Foundation taxonomy terms remain canonical; Studio owns only bounded caps
// and the archive framing labels around those WordPress-owned terms.
$section_terms = lunara_get_journal_archive_filter_terms( 'journal_section', absint( $journal_config['filter_caps']['journal_section'] ), $current_term );
$topic_terms = lunara_get_journal_archive_filter_terms( 'journal_topic', absint( $journal_config['filter_caps']['journal_topic'] ), $current_term );
$legacy_type_terms = lunara_get_journal_archive_filter_terms( 'journal_type', absint( $journal_config['filter_caps']['journal_type'] ), $current_term );

$has_section_terms = is_array( $section_terms ) && ! empty( $section_terms );
$primary_terms     = $has_section_terms ? $section_terms : $legacy_type_terms;
$primary_label     = $has_section_terms ? $journal_labels['filter_sections'] : $journal_labels['filter_types'];
$journal_filter_groups = array();

if ( is_array( $primary_terms ) && ! empty( $primary_terms ) ) {
	$journal_filter_groups[] = array( 'label' => $primary_label, 'terms' => $primary_terms );
}
if ( is_array( $topic_terms ) && ! empty( $topic_terms ) ) {
	$journal_filter_groups[] = array( 'label' => $journal_labels['filter_topics'], 'terms' => $topic_terms );
}
if ( $has_section_terms && is_array( $legacy_type_terms ) && ! empty( $legacy_type_terms ) ) {
	$journal_filter_groups[] = array( 'label' => $journal_labels['filter_archive_types'], 'terms' => $legacy_type_terms );
}

$journal_lane_count = 0;
foreach ( $journal_filter_groups as $filter_group ) {
	$journal_lane_count += count( $filter_group['terms'] );
}

$latest_journal_url = ! empty( $latest_journal[0] ) ? get_permalink( (int) $latest_journal[0] ) : get_post_type_archive_link( 'journal' );
$trailer_lane_url   = get_post_type_archive_link( 'journal' );
foreach ( $journal_filter_groups as $filter_group ) {
	foreach ( $filter_group['terms'] as $type_term ) {
		if ( ! $type_term instanceof WP_Term || 'trailer' !== sanitize_title( $type_term->slug ) ) {
			continue;
		}
		$term_link = get_term_link( $type_term );
		if ( ! is_wp_error( $term_link ) ) {
			$trailer_lane_url = $term_link;
		}
		break 2;
	}
}
$reviews_archive_url = get_post_type_archive_link( 'review' );

$journal_retention_cards = $journal_config['retention'];
usort( $journal_retention_cards, static function ( $left, $right ) { return absint( $left['order'] ) <=> absint( $right['order'] ); } );
$journal_retention_cards = array_values( array_filter( $journal_retention_cards, static function ( $card ) { return ! empty( $card['visible'] ); } ) );
foreach ( $journal_retention_cards as $retention_index => $retention_card ) {
	$journal_retention_cards[ $retention_index ]['media_markup'] = function_exists( 'lunara_journal_archive_studio_retention_media_markup' )
		? lunara_journal_archive_studio_retention_media_markup( $retention_card )
		: '';
}
$journal_gallery_markup = function_exists( 'lunara_journal_archive_studio_render_gallery' )
	&& function_exists( 'lunara_journal_archive_studio_is_gallery_request' )
	&& lunara_journal_archive_studio_is_gallery_request()
	? lunara_journal_archive_studio_render_gallery( $journal_config['gallery'] )
	: '';

$resolve_retention_url = static function ( $card ) use ( $latest_journal_url, $trailer_lane_url, $reviews_archive_url ) {
	switch ( $card['destination'] ) {
		case 'latest': return $latest_journal_url;
		case 'trailer': return $trailer_lane_url;
		case 'reviews': return $reviews_archive_url ? $reviews_archive_url : home_url( '/reviews/' );
		case 'journal': return get_post_type_archive_link( 'journal' );
		case 'custom': return $card['url'];
		default: return get_post_type_archive_link( 'journal' );
	}
};

$journal_section_markup = array();

ob_start();
?>
<header class="lunara-archive-hero lunara-journal-archive-hero lunara-journal-archive-slot-hero" data-lunara-site-studio-section="hero">
	<p class="lunara-archive-hero-kicker"><?php echo esc_html( $kicker ); ?></p>
	<h1 class="lunara-archive-hero-title"><?php echo esc_html( $title ); ?></h1>
	<?php if ( '' !== trim( (string) $copy ) ) : ?><p class="lunara-archive-hero-copy"><?php echo esc_html( $copy ); ?></p><?php endif; ?>
</header>
<?php
$journal_section_markup['hero'] = ob_get_clean();
$journal_section_markup['fallback-h1'] = '<h1 class="screen-reader-text">' . esc_html( $title ) . '</h1>';

ob_start();
?>
<div class="lunara-journal-archive-deskbar lunara-journal-archive-slot-deskbar" data-lunara-site-studio-section="deskbar" aria-label="<?php esc_attr_e( 'Journal desk status', 'lunara-film' ); ?>">
	<span><strong><?php echo esc_html( $journal_labels['desk_count'] ); ?></strong> <?php echo esc_html( $journal_total . ' ' . ( 1 === $journal_total ? $journal_labels['file_singular'] : $journal_labels['file_plural'] ) ); ?></span>
	<?php if ( '' !== $latest_label ) : ?><span><strong><?php echo esc_html( $journal_labels['desk_latest'] ); ?></strong> <?php echo esc_html( $latest_label ); ?></span><?php endif; ?>
	<?php if ( $journal_lane_count > 0 ) : ?><span><strong><?php echo esc_html( $journal_labels['desk_mix'] ); ?></strong> <?php echo esc_html( $journal_lane_count . ' ' . ( 1 === $journal_lane_count ? $journal_labels['lane_singular'] : $journal_labels['lane_plural'] ) ); ?></span><?php endif; ?>
</div>
<?php
$journal_section_markup['deskbar'] = ob_get_clean();

ob_start();
if ( ! empty( $journal_filter_groups ) ) :
	?>
	<div class="lunara-journal-filter-groups lunara-journal-archive-slot-filters" data-lunara-site-studio-section="filters">
		<?php foreach ( $journal_filter_groups as $group_index => $filter_group ) : ?>
			<nav class="lunara-journal-archive-filters" aria-label="<?php echo esc_attr( sprintf( __( 'Filter Journal by %s', 'lunara-film' ), $filter_group['label'] ) ); ?>">
				<span class="lunara-journal-filter-label"><?php echo esc_html( $filter_group['label'] ); ?></span>
				<?php if ( 0 === $group_index ) : ?><a class="lunara-journal-filter-pill <?php echo is_post_type_archive( 'journal' ) ? 'is-active' : ''; ?>" href="<?php echo esc_url( get_post_type_archive_link( 'journal' ) ); ?>"><?php echo esc_html( $journal_labels['filter_all'] ); ?></a><?php endif; ?>
				<?php foreach ( $filter_group['terms'] as $term ) : ?><?php $is_active = $current_term instanceof WP_Term && $current_term->taxonomy === $term->taxonomy && $current_term->term_id === $term->term_id; ?><a class="lunara-journal-filter-pill <?php echo $is_active ? 'is-active' : ''; ?>" href="<?php echo esc_url( get_term_link( $term ) ); ?>"><?php echo esc_html( $term->name ); ?> <span class="lunara-journal-filter-count">(<?php echo intval( $term->count ); ?>)</span></a><?php endforeach; ?>
			</nav>
		<?php endforeach; ?>
	</div>
	<?php
endif;
$journal_section_markup['filters'] = ob_get_clean();

ob_start();
if ( ! empty( $journal_archive_posts ) && ! empty( $sort_options ) ) :
	?>
	<div class="lunara-editorial-archive-toolbar lunara-journal-archive-toolbar lunara-journal-archive-slot-toolbar" data-lunara-site-studio-section="toolbar">
		<div class="lunara-home-section-head lunara-editorial-archive-toolbar-head"><div><p class="lunara-home-section-kicker"><?php echo esc_html( $journal_labels['toolbar_kicker'] ); ?></p><h2 class="lunara-section-title"><?php echo esc_html( $journal_labels['toolbar_title'] ); ?></h2><?php if ( '' !== trim( (string) $journal_config['supporting_copy'] ) ) : ?><p class="lunara-home-section-summary"><?php echo esc_html( $journal_config['supporting_copy'] ); ?></p><?php endif; ?></div></div>
		<div class="lunara-archive-sort" aria-label="<?php esc_attr_e( 'Sort journal archive', 'lunara-film' ); ?>"><?php foreach ( $sort_options as $sort_key => $sort_label ) : ?><?php $is_active = $sort_key === $current_sort; $sort_url = 'date_desc' === $sort_key ? $sort_base_url : add_query_arg( 'sort', rawurlencode( $sort_key ), $sort_base_url ); ?><a class="lunara-archive-sort-link <?php echo $is_active ? 'is-active' : ''; ?>" href="<?php echo esc_url( $sort_url ); ?>"<?php echo $is_active ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $sort_label ); ?></a><?php endforeach; ?></div>
	</div>
	<?php
endif;
$journal_section_markup['toolbar'] = ob_get_clean();

ob_start();
if ( ! empty( $journal_archive_posts ) ) :
	?>
	<section class="lunara-journal-archive-grid lunara-review-grid lunara-review-archive-uniform lunara-journal-archive-slot-grid" data-lunara-site-studio-section="grid">
		<?php
		$journal_card_index = 0;
		foreach ( $journal_archive_posts as $journal_archive_post ) :
			$post = $journal_archive_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			setup_postdata( $post );
			$pid = get_the_ID();
			$journal_card_index++;
			$is_visual_lead = lunara_journal_archive_card_is_visual_lead( $journal_card_index );
			$entry_kicker = $is_visual_lead ? $journal_labels['lead_kicker'] : $journal_labels['card_kicker'];
			$entry_type = function_exists( 'lunara_get_dispatch_type_label' ) ? lunara_get_dispatch_type_label( $pid ) : ( function_exists( 'lunara_get_journal_kicker' ) ? lunara_get_journal_kicker( $pid ) : __( 'Dispatch', 'lunara-film' ) );
			$entry_excerpt = has_excerpt( $pid ) ? wp_trim_words( get_the_excerpt( $pid ), 28 ) : wp_trim_words( wp_strip_all_tags( get_post_field( 'post_content', $pid ) ), 28 );
			$updated_label = function_exists( 'lunara_get_editorial_card_updated_label' ) ? lunara_get_editorial_card_updated_label( $pid ) : '';
			$featured_id = get_post_thumbnail_id( $pid );
			$media_markup = '';
			if ( $featured_id ) {
				$media_alt = trim( (string) get_post_meta( $featured_id, '_wp_attachment_image_alt', true ) );
				if ( '' === $media_alt ) {
					$media_alt = get_the_title( $pid );
				}
				$media_attributes = lunara_journal_archive_card_image_attributes( $is_visual_lead, $media_alt );
				$media_markup     = lunara_journal_archive_card_image_markup( $featured_id, $media_attributes );
			}
			$has_media = '' !== trim( (string) $media_markup );
			?>
			<article class="lunara-review-grid-card lunara-journal-archive-card<?php echo $is_visual_lead ? ' is-lead' : ''; ?><?php echo $has_media ? ' has-media' : ' is-text-brief'; ?>">
				<a class="lunara-review-grid-link" href="<?php the_permalink(); ?>">
					<?php if ( $has_media ) : ?>
						<div class="lunara-review-grid-poster-wrap">
							<?php
							echo $media_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							?>
						</div>
					<?php endif; ?>
					<div class="lunara-review-grid-copy"><p class="lunara-review-grid-kicker"><?php echo esc_html( $entry_kicker ); ?></p><?php if ( '' !== trim( (string) $entry_type ) ) : ?><p class="lunara-dispatch-type lunara-journal-archive-card-type"><?php echo esc_html( $entry_type ); ?></p><?php endif; ?><?php if ( function_exists( 'lunara_render_journal_card_provenance' ) ) { lunara_render_journal_card_provenance( $pid, 'archive' ); } ?><?php if ( function_exists( 'lunara_render_trailer_card_badge' ) ) { echo lunara_render_trailer_card_badge( $pid, 'journal-card' ); } ?><h3 class="lunara-review-grid-title"><?php the_title(); ?></h3><?php if ( $entry_excerpt ) : ?><p class="lunara-review-grid-excerpt"><?php echo esc_html( $entry_excerpt ); ?></p><?php endif; ?><div class="lunara-review-grid-footer lunara-journal-archive-card-footer"><span class="lunara-review-grid-meta"><?php echo esc_html( get_the_date( 'F j, Y' ) ); ?></span><?php if ( '' !== $updated_label ) : ?><span class="lunara-review-grid-updated"><?php echo esc_html( $updated_label ); ?></span><?php endif; ?><span class="lunara-journal-archive-card-cta"><?php echo esc_html( $journal_labels['card_cta'] ); ?></span></div></div>
				</a>
			</article>
		<?php endforeach; wp_reset_postdata(); ?>
	</section>
	<?php
else :
	?><div class="lunara-archive-empty lunara-journal-archive-slot-grid" data-lunara-site-studio-section="grid"><p><?php echo esc_html( $journal_labels['empty_copy'] ); ?></p></div><?php
endif;
$journal_section_markup['grid'] = ob_get_clean();

ob_start();
if ( ! empty( $journal_retention_cards ) ) :
	?>
	<div class="lunara-journal-archive-retention-head"><p class="lunara-home-section-kicker"><?php echo esc_html( $journal_labels['retention_kicker'] ); ?></p><h2 class="lunara-section-title"><?php echo esc_html( $journal_labels['retention_title'] ); ?></h2></div>
	<div class="lunara-journal-archive-retention-grid">
				<?php foreach ( $journal_retention_cards as $retention_card ) : $retention_url = $resolve_retention_url( $retention_card ); $retention_has_media = '' !== $retention_card['media_markup']; ?>
					<article class="lunara-journal-archive-retention-card<?php echo $retention_has_media ? ' has-media' : ''; ?>">
						<a class="lunara-journal-archive-retention-card-link" href="<?php echo esc_url( $retention_url ); ?>">
							<?php if ( $retention_has_media ) : ?><span class="lunara-journal-archive-retention-media" style="--lunara-retention-focus-x:<?php echo esc_attr( $retention_card['focal_x'] ); ?>%;--lunara-retention-focus-y:<?php echo esc_attr( $retention_card['focal_y'] ); ?>%"><?php echo $retention_card['media_markup']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Resolved native attachment markup. ?></span><?php endif; ?>
							<span class="lunara-journal-archive-retention-kicker"><?php echo esc_html( $retention_card['label'] ); ?></span><strong><?php echo esc_html( $retention_card['title'] ); ?></strong><span><?php echo esc_html( $retention_card['copy'] ); ?></span>
						</a>
						<?php if ( $retention_has_media && ( $retention_card['image_credit'] || $retention_card['image_source'] ) ) : ?><small class="lunara-journal-archive-retention-provenance"><span><?php echo esc_html( $retention_card['image_credit'] ); ?></span><?php if ( $retention_card['image_credit'] && $retention_card['image_source'] ) : ?><span aria-hidden="true"> · </span><?php endif; ?><?php if ( $retention_card['image_source'] ) : ?><a href="<?php echo esc_url( $retention_card['image_source_url'] ); ?>" rel="noopener noreferrer"><?php echo esc_html( $retention_card['image_source'] ); ?></a><?php endif; ?></small><?php endif; ?>
					</article>
				<?php endforeach; ?>
	</div>
	<?php
endif;
$journal_retention_cards_markup = ob_get_clean();
$journal_section_markup['retention'] = lunara_journal_archive_studio_compose_retention_lane(
	$journal_retention_cards_markup,
	$journal_gallery_markup,
	! empty( $journal_archive_posts )
);

ob_start();
if ( ! empty( $journal_archive_posts ) ) :
	?><nav class="lunara-archive-pagination lunara-journal-archive-slot-pagination" data-lunara-site-studio-section="pagination" aria-label="<?php esc_attr_e( 'Journal pagination', 'lunara-film' ); ?>"><?php the_posts_pagination( array( 'mid_size' => 1, 'add_args' => 'date_desc' === $current_sort ? false : array( 'sort' => $current_sort ), 'prev_text' => $journal_labels['pagination_prev'], 'next_text' => $journal_labels['pagination_next'] ) ); ?></nav><?php
endif;
$journal_section_markup['pagination'] = ob_get_clean();

$journal_section_order      = $journal_config['section_order'];
$journal_section_visibility = $journal_config['section_visibility'];
$journal_label_font_class   = function_exists( 'lunara_journal_archive_uses_tiempos_label_face' ) && lunara_journal_archive_uses_tiempos_label_face()
	? ' is-label-font-tiempos'
	: '';
?>
<div id="primary" class="lunara-archive-page lunara-journal-archive-page<?php echo esc_attr( $journal_label_font_class ); ?>" data-lunara-theme-version="<?php echo esc_attr( (string) wp_get_theme()->get( 'Version' ) ); ?>">
	<?php echo lunara_journal_archive_studio_render_sections( $journal_section_markup, $journal_section_order, $journal_section_visibility ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
<?php
get_footer();
