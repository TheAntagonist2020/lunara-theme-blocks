<?php
/**
 * Template Name: Lunara Debrief Method
 * Template Post Type: page
 *
 * The explainer for the signature that closes every review: three films in
 * conversation with the one just reviewed. Auto-applies to the page with slug
 * "debrief"; assignable to any page via the template picker.
 *
 * Every word, section and count is a setting (inc/debrief-method.php), edited
 * in Site Studio → Reviews → Debrief page with a private preview. The page's
 * own block content renders in the "From the Desk" seat. Everything else is
 * live: the totals, the specimen, the most-prescribed films, and the recent
 * Debriefs are drawn from the reviews themselves.
 *
 * @package Lunara_Film
 */

get_header();

the_post();

$debrief_page_id  = get_the_ID();
$debrief_settings = lunara_debrief_method_settings();
$debrief_roles    = lunara_debrief_method_display_roles( $debrief_settings );
$debrief_index    = lunara_debrief_method_index();

$debrief_title = trim( (string) $debrief_settings['hero']['title'] );
if ( '' === $debrief_title ) {
	$debrief_title = trim( wp_strip_all_tags( (string) get_the_title() ) );
}
if ( '' === $debrief_title || 'debrief' === strtolower( $debrief_title ) ) {
	$debrief_title = __( 'The Debrief Method', 'lunara-film' );
}
$debrief_content = trim( (string) apply_filters( 'the_content', get_the_content() ) );
$debrief_hero    = has_post_thumbnail( $debrief_page_id ) ? (string) get_the_post_thumbnail_url( $debrief_page_id, 'full' ) : '';
$debrief_thesis  = (string) apply_filters( 'lunara_debrief_method_thesis', $debrief_settings['hero']['thesis'] );

$debrief_specimen_id    = 0;
$debrief_specimen_cards = '';
if ( $debrief_settings['specimen']['show'] ) {
	$debrief_specimen_id = lunara_debrief_method_specimen_id( $debrief_index, $debrief_settings['specimen']['review_id'] );
	if ( $debrief_specimen_id && function_exists( 'lunara_render_pair_it_with_cards' ) ) {
		$debrief_specimen_cards = trim( (string) lunara_render_pair_it_with_cards( $debrief_specimen_id ) );
	}
}

$debrief_canon = $debrief_settings['canon']['show']
	? lunara_debrief_method_canon( $debrief_index, $debrief_settings['canon']['count'], $debrief_settings['canon']['min_count'] )
	: array();

$debrief_recent = $debrief_settings['recent']['show']
	? array_slice( (array) $debrief_index['recent'], 0, $debrief_settings['recent']['count'] )
	: array();

// Warm the post cache for every review the canon and recent lists link to.
$debrief_linked_reviews = wp_list_pluck( $debrief_recent, 'review_id' );
if ( $debrief_settings['canon']['show_reviews'] ) {
	foreach ( $debrief_canon as $debrief_film ) {
		$debrief_linked_reviews = array_merge( $debrief_linked_reviews, (array) $debrief_film['reviews'] );
	}
}
if ( $debrief_linked_reviews && function_exists( '_prime_post_caches' ) ) {
	_prime_post_caches( array_unique( array_map( 'intval', $debrief_linked_reviews ) ), false, false );
}
?>

<div class="lunara-debrief-page">

	<section class="lunara-debrief-hero<?php echo '' !== $debrief_hero ? ' has-backdrop' : ''; ?>" data-lunara-site-studio-section="hero">
		<?php if ( '' !== $debrief_hero ) : ?>
			<div class="lunara-debrief-hero-backdrop" style="background-image:url('<?php echo esc_url( $debrief_hero ); ?>');" aria-hidden="true"></div>
		<?php endif; ?>
		<div class="lunara-debrief-hero-overlay" aria-hidden="true"></div>
		<div class="lunara-debrief-hero-inner">
			<?php if ( '' !== $debrief_settings['hero']['kicker'] ) : ?>
				<p class="lunara-debrief-kicker"><?php echo esc_html( $debrief_settings['hero']['kicker'] ); ?></p>
			<?php endif; ?>
			<h1 class="lunara-debrief-title"><?php echo esc_html( $debrief_title ); ?></h1>
			<?php if ( '' !== trim( $debrief_thesis ) ) : ?>
				<p class="lunara-debrief-thesis"><?php echo esc_html( $debrief_thesis ); ?></p>
			<?php endif; ?>

			<?php if ( $debrief_settings['hero']['show_stats'] && $debrief_index['reviews_debrief'] > 0 ) : ?>
				<dl class="lunara-debrief-stats">
					<div>
						<dt><?php esc_html_e( 'Reviews debriefed', 'lunara-film' ); ?></dt>
						<dd><?php echo esc_html( number_format_i18n( $debrief_index['reviews_debrief'] ) ); ?></dd>
					</div>
					<div>
						<dt><?php esc_html_e( 'Films prescribed', 'lunara-film' ); ?></dt>
						<dd><?php echo esc_html( number_format_i18n( $debrief_index['pairings_total'] ) ); ?></dd>
					</div>
					<div>
						<dt><?php esc_html_e( 'Distinct titles', 'lunara-film' ); ?></dt>
						<dd><?php echo esc_html( number_format_i18n( $debrief_index['unique_films'] ) ); ?></dd>
					</div>
				</dl>
			<?php endif; ?>
		</div>
	</section>

	<section class="lunara-debrief-moves" aria-labelledby="lunara-debrief-moves-title" data-lunara-site-studio-section="moves">
		<?php if ( '' !== $debrief_settings['moves']['kicker'] ) : ?>
			<p class="lunara-debrief-kicker"><?php echo esc_html( $debrief_settings['moves']['kicker'] ); ?></p>
		<?php endif; ?>
		<h2 id="lunara-debrief-moves-title" class="lunara-debrief-section-title"><?php echo esc_html( '' !== $debrief_settings['moves']['title'] ? $debrief_settings['moves']['title'] : __( 'The Three Moves', 'lunara-film' ) ); ?></h2>
		<ol class="lunara-debrief-moves-list">
			<?php foreach ( $debrief_roles as $debrief_slug => $debrief_role ) : ?>
				<li class="lunara-debrief-move lunara-debrief-move--<?php echo esc_attr( $debrief_slug ); ?>">
					<h3 class="lunara-debrief-move-name"><?php echo esc_html( $debrief_role['label'] ); ?></h3>
					<?php foreach ( array( 'question', 'copy', 'not' ) as $debrief_part ) : ?>
						<?php if ( '' !== trim( $debrief_role[ $debrief_part ] ) ) : ?>
							<p class="lunara-debrief-move-<?php echo esc_attr( $debrief_part ); ?>"><?php echo esc_html( $debrief_role[ $debrief_part ] ); ?></p>
						<?php endif; ?>
					<?php endforeach; ?>
				</li>
			<?php endforeach; ?>
		</ol>
	</section>

	<?php $debrief_why_paragraphs = lunara_debrief_method_paragraphs( $debrief_settings['why']['body'] ); ?>
	<?php if ( $debrief_settings['why']['show'] && ( $debrief_why_paragraphs || '' !== $debrief_settings['why']['title'] ) ) : ?>
		<section class="lunara-debrief-why"<?php echo '' !== $debrief_settings['why']['title'] ? ' aria-labelledby="lunara-debrief-why-title"' : ''; ?> data-lunara-site-studio-section="why">
			<?php if ( '' !== $debrief_settings['why']['kicker'] ) : ?>
				<p class="lunara-debrief-kicker"><?php echo esc_html( $debrief_settings['why']['kicker'] ); ?></p>
			<?php endif; ?>
			<?php if ( '' !== $debrief_settings['why']['title'] ) : ?>
				<h2 id="lunara-debrief-why-title" class="lunara-debrief-section-title"><?php echo esc_html( $debrief_settings['why']['title'] ); ?></h2>
			<?php endif; ?>
			<?php if ( $debrief_why_paragraphs ) : ?>
				<div class="lunara-debrief-why-body">
					<?php foreach ( $debrief_why_paragraphs as $debrief_paragraph ) : ?>
						<p><?php echo esc_html( $debrief_paragraph ); ?></p>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</section>
	<?php endif; ?>

	<?php if ( '' !== $debrief_specimen_cards ) : ?>
		<section class="lunara-debrief-specimen" aria-labelledby="lunara-debrief-specimen-title" data-lunara-site-studio-section="specimen">
			<?php if ( '' !== $debrief_settings['specimen']['kicker'] ) : ?>
				<p class="lunara-debrief-kicker"><?php echo esc_html( $debrief_settings['specimen']['kicker'] ); ?></p>
			<?php endif; ?>
			<h2 id="lunara-debrief-specimen-title" class="lunara-debrief-section-title">
				<?php if ( '' !== $debrief_settings['specimen']['lead'] ) : ?>
					<?php echo esc_html( $debrief_settings['specimen']['lead'] ); ?>
				<?php endif; ?>
				<a href="<?php echo esc_url( (string) get_permalink( $debrief_specimen_id ) ); ?>"><?php echo esc_html( wp_strip_all_tags( (string) get_the_title( $debrief_specimen_id ) ) ); ?></a>
			</h2>
			<?php echo $debrief_specimen_cards; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- renderer escapes every value. ?>
		</section>
	<?php endif; ?>

	<?php if ( '' !== $debrief_content ) : ?>
		<section class="lunara-debrief-words" data-lunara-site-studio-section="desk">
			<?php if ( '' !== $debrief_settings['desk']['kicker'] ) : ?>
				<p class="lunara-debrief-kicker"><?php echo esc_html( $debrief_settings['desk']['kicker'] ); ?></p>
			<?php endif; ?>
			<div class="lunara-debrief-words-body">
				<?php echo $debrief_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- editor content through the_content filters. ?>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( ! empty( $debrief_canon ) ) : ?>
		<section class="lunara-debrief-canon" aria-labelledby="lunara-debrief-canon-title" data-lunara-site-studio-section="canon">
			<?php if ( '' !== $debrief_settings['canon']['kicker'] ) : ?>
				<p class="lunara-debrief-kicker"><?php echo esc_html( $debrief_settings['canon']['kicker'] ); ?></p>
			<?php endif; ?>
			<h2 id="lunara-debrief-canon-title" class="lunara-debrief-section-title"><?php echo esc_html( '' !== $debrief_settings['canon']['title'] ? $debrief_settings['canon']['title'] : __( 'The Debrief Canon', 'lunara-film' ) ); ?></h2>
			<ol class="lunara-debrief-canon-list">
				<?php foreach ( $debrief_canon as $debrief_film ) : ?>
					<?php
					$debrief_poster = ( '' !== $debrief_film['tt'] && function_exists( 'lunara_get_title_poster_html' ) )
						? (string) lunara_get_title_poster_html( $debrief_film['tt'], 'medium', 'lunara-debrief-canon-poster', $debrief_film['title'], 'lazy', '(min-width: 1000px) 240px, 76px' )
						: '';
					$debrief_role_bits = array();
					foreach ( $debrief_roles as $debrief_slug => $debrief_role ) {
						$debrief_n = isset( $debrief_film['roles'][ $debrief_slug ] ) ? (int) $debrief_film['roles'][ $debrief_slug ] : 0;
						if ( $debrief_n > 0 ) {
							$debrief_role_bits[] = sprintf( '%s ×%d', $debrief_role['label'], $debrief_n );
						}
					}
					?>
					<li class="lunara-debrief-canon-item">
						<div class="lunara-debrief-canon-media">
							<?php if ( '' !== $debrief_poster ) : ?>
								<?php echo $debrief_poster; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- poster helper escapes. ?>
							<?php else : ?>
								<span class="lunara-debrief-canon-plate"><?php echo esc_html( $debrief_film['title'] ); ?></span>
							<?php endif; ?>
						</div>
						<div class="lunara-debrief-canon-body">
							<h3 class="lunara-debrief-canon-title">
								<?php if ( '' !== $debrief_film['href'] ) : ?>
									<a href="<?php echo esc_url( $debrief_film['href'] ); ?>"><?php echo esc_html( $debrief_film['title'] ); ?></a>
								<?php else : ?>
									<?php echo esc_html( $debrief_film['title'] ); ?>
								<?php endif; ?>
								<?php if ( '' !== $debrief_film['year'] ) : ?>
									<span class="lunara-debrief-canon-year">(<?php echo esc_html( $debrief_film['year'] ); ?>)</span>
								<?php endif; ?>
							</h3>
							<p class="lunara-debrief-canon-count">
								<?php echo esc_html( sprintf( /* translators: %d: number of reviews */ _n( 'Prescribed in %d review', 'Prescribed in %d reviews', (int) $debrief_film['count'], 'lunara-film' ), (int) $debrief_film['count'] ) ); ?>
							</p>
							<?php if ( $debrief_role_bits ) : ?>
								<p class="lunara-debrief-canon-roles"><?php echo esc_html( implode( ' · ', $debrief_role_bits ) ); ?></p>
							<?php endif; ?>
							<?php if ( $debrief_settings['canon']['show_reviews'] && ! empty( $debrief_film['reviews'] ) ) : ?>
								<?php
								$debrief_review_links = array();
								foreach ( array_slice( (array) $debrief_film['reviews'], 0, 3 ) as $debrief_review_id ) {
									$debrief_review_url = (string) get_permalink( (int) $debrief_review_id );
									if ( '' !== $debrief_review_url ) {
										$debrief_review_links[] = '<a href="' . esc_url( $debrief_review_url ) . '">' . esc_html( lunara_debrief_method_review_label( (int) $debrief_review_id ) ) . '</a>';
									}
								}
								$debrief_review_list = implode( ', ', $debrief_review_links );
								$debrief_more        = (int) $debrief_film['count'] - count( $debrief_review_links );
								if ( $debrief_review_list && $debrief_more > 0 ) {
									/* translators: %d: number of further reviews */
									$debrief_review_list .= ' ' . esc_html( sprintf( _n( 'and %d more', 'and %d more', $debrief_more, 'lunara-film' ), $debrief_more ) );
								}
								?>
								<?php if ( $debrief_review_list ) : ?>
									<p class="lunara-debrief-canon-reviews">
										<span class="lunara-debrief-canon-reviews-label"><?php esc_html_e( 'In the Debriefs for', 'lunara-film' ); ?></span>
										<?php echo $debrief_review_list; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- links and count escaped above. ?>
									</p>
								<?php endif; ?>
							<?php endif; ?>
						</div>
					</li>
				<?php endforeach; ?>
			</ol>
		</section>
	<?php endif; ?>

	<?php if ( ! empty( $debrief_recent ) ) : ?>
		<section class="lunara-debrief-recent" aria-labelledby="lunara-debrief-recent-title" data-lunara-site-studio-section="recent">
			<?php if ( '' !== $debrief_settings['recent']['kicker'] ) : ?>
				<p class="lunara-debrief-kicker"><?php echo esc_html( $debrief_settings['recent']['kicker'] ); ?></p>
			<?php endif; ?>
			<h2 id="lunara-debrief-recent-title" class="lunara-debrief-section-title"><?php echo esc_html( '' !== $debrief_settings['recent']['title'] ? $debrief_settings['recent']['title'] : __( 'Recent Debriefs', 'lunara-film' ) ); ?></h2>
			<ul class="lunara-debrief-recent-list">
				<?php foreach ( $debrief_recent as $debrief_entry ) : ?>
					<li class="lunara-debrief-recent-item">
						<a class="lunara-debrief-recent-review" href="<?php echo esc_url( (string) get_permalink( $debrief_entry['review_id'] ) ); ?>"><?php echo esc_html( wp_strip_all_tags( (string) get_the_title( $debrief_entry['review_id'] ) ) ); ?></a>
						<ul class="lunara-debrief-recent-pairs">
							<?php foreach ( $debrief_entry['pairs'] as $debrief_pair ) : ?>
								<?php if ( ! isset( $debrief_roles[ $debrief_pair['role'] ] ) ) { continue; } ?>
								<li class="lunara-debrief-recent-pair lunara-debrief-recent-pair--<?php echo esc_attr( $debrief_pair['role'] ); ?>">
									<span class="lunara-debrief-recent-role"><?php echo esc_html( $debrief_roles[ $debrief_pair['role'] ]['label'] ); ?></span>
									<?php if ( '' !== $debrief_pair['href'] ) : ?>
										<a href="<?php echo esc_url( $debrief_pair['href'] ); ?>"><em><?php echo esc_html( $debrief_pair['title'] ); ?></em></a>
									<?php else : ?>
										<em><?php echo esc_html( $debrief_pair['title'] ); ?></em>
									<?php endif; ?>
								</li>
							<?php endforeach; ?>
						</ul>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
	<?php endif; ?>

	<?php if ( $debrief_settings['next']['show'] ) : ?>
		<nav class="lunara-debrief-next" aria-label="<?php esc_attr_e( 'Keep going', 'lunara-film' ); ?>" data-lunara-site-studio-section="next">
			<?php
			$debrief_next_links = array(
				array( home_url( '/reviews/' ), $debrief_settings['next']['reviews_kicker'], $debrief_settings['next']['reviews_label'] ),
				array( home_url( '/oscars/' ), $debrief_settings['next']['oscars_kicker'], $debrief_settings['next']['oscars_label'] ),
			);
			?>
			<?php foreach ( $debrief_next_links as $debrief_next ) : ?>
				<?php if ( '' === $debrief_next[2] ) { continue; } ?>
				<a class="lunara-debrief-next-link" href="<?php echo esc_url( $debrief_next[0] ); ?>">
					<?php if ( '' !== $debrief_next[1] ) : ?>
						<em><?php echo esc_html( $debrief_next[1] ); ?></em>
					<?php endif; ?>
					<span><?php echo esc_html( $debrief_next[2] ); ?> &rarr;</span>
				</a>
			<?php endforeach; ?>
		</nav>
	<?php endif; ?>

</div>

<?php
get_footer();
