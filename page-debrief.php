<?php
/**
 * Template Name: Lunara Debrief Method
 * Template Post Type: page
 *
 * The explainer for the signature that closes every review: three films in
 * conversation with the one just reviewed. Auto-applies to the page with slug
 * "debrief"; assignable to any page via the template picker. The editor's
 * block content renders in the "From the Desk" seat, so Dalton can write and
 * revise the manifesto without touching this layout. Everything else is live:
 * the specimen, the most-prescribed films, and the recent Debriefs are drawn
 * from the reviews themselves (inc/debrief-method.php).
 *
 * @package Lunara_Film
 */

get_header();

the_post();

$debrief_page_id = get_the_ID();
$debrief_title   = get_the_title();
if ( '' === trim( (string) $debrief_title ) || 'debrief' === strtolower( trim( (string) $debrief_title ) ) ) {
	$debrief_title = __( 'The Debrief Method', 'lunara-film' );
}
$debrief_content = trim( (string) apply_filters( 'the_content', get_the_content() ) );
$debrief_hero    = has_post_thumbnail( $debrief_page_id ) ? (string) get_the_post_thumbnail_url( $debrief_page_id, 'full' ) : '';

$debrief_roles = lunara_debrief_method_roles();
$debrief_index = lunara_debrief_method_index();

// Specimen: the newest review carrying the full trio, else the newest with any pairing.
$debrief_specimen_id = 0;
foreach ( $debrief_index['recent'] as $debrief_entry ) {
	if ( 3 === count( $debrief_entry['pairs'] ) ) {
		$debrief_specimen_id = (int) $debrief_entry['review_id'];
		break;
	}
}
if ( ! $debrief_specimen_id && ! empty( $debrief_index['recent'] ) ) {
	$debrief_specimen_id = (int) $debrief_index['recent'][0]['review_id'];
}
$debrief_specimen_cards = ( $debrief_specimen_id && function_exists( 'lunara_render_pair_it_with_cards' ) )
	? (string) lunara_render_pair_it_with_cards( $debrief_specimen_id )
	: '';

// The index only earns a leaderboard once a film has been prescribed more than once.
$debrief_repeat_films = array_values(
	array_filter(
		$debrief_index['films'],
		static function ( $film ) {
			return (int) $film['count'] > 1;
		}
	)
);
?>

<div class="lunara-debrief-page">

	<section class="lunara-debrief-hero<?php echo '' !== $debrief_hero ? ' has-backdrop' : ''; ?>">
		<?php if ( '' !== $debrief_hero ) : ?>
			<div class="lunara-debrief-hero-backdrop" style="background-image:url('<?php echo esc_url( $debrief_hero ); ?>');" aria-hidden="true"></div>
		<?php endif; ?>
		<div class="lunara-debrief-hero-overlay" aria-hidden="true"></div>
		<div class="lunara-debrief-hero-inner">
			<p class="lunara-debrief-kicker"><?php esc_html_e( 'A Lunara Film Signature', 'lunara-film' ); ?></p>
			<h1 class="lunara-debrief-title"><?php echo esc_html( $debrief_title ); ?></h1>
			<p class="lunara-debrief-thesis"><?php echo esc_html( apply_filters( 'lunara_debrief_method_thesis', __( 'Every review on Lunara ends the same way — with three more films. Not a list of lookalikes. An argument in three moves: one that deepens the film, one that challenges it, and one that explains where it came from.', 'lunara-film' ) ) ); ?></p>

			<?php if ( $debrief_index['reviews_debrief'] > 0 ) : ?>
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

	<section class="lunara-debrief-moves" aria-labelledby="lunara-debrief-moves-title">
		<p class="lunara-debrief-kicker"><?php esc_html_e( 'The Three Moves', 'lunara-film' ); ?></p>
		<h2 id="lunara-debrief-moves-title" class="lunara-debrief-section-title"><?php esc_html_e( 'Every pairing answers a different question.', 'lunara-film' ); ?></h2>
		<ol class="lunara-debrief-moves-list">
			<?php foreach ( $debrief_roles as $debrief_slug => $debrief_role ) : ?>
				<li class="lunara-debrief-move lunara-debrief-move--<?php echo esc_attr( $debrief_slug ); ?>">
					<h3 class="lunara-debrief-move-name"><?php echo esc_html( $debrief_role['label'] ); ?></h3>
					<p class="lunara-debrief-move-question"><?php echo esc_html( $debrief_role['question'] ); ?></p>
					<p class="lunara-debrief-move-copy"><?php echo esc_html( $debrief_role['copy'] ); ?></p>
					<p class="lunara-debrief-move-not"><?php echo esc_html( $debrief_role['not'] ); ?></p>
				</li>
			<?php endforeach; ?>
		</ol>
	</section>

	<section class="lunara-debrief-why" aria-labelledby="lunara-debrief-why-title">
		<p class="lunara-debrief-kicker"><?php esc_html_e( 'Why Three', 'lunara-film' ); ?></p>
		<h2 id="lunara-debrief-why-title" class="lunara-debrief-section-title"><?php esc_html_e( 'An algorithm recommends more of the same. A critic recommends a conversation.', 'lunara-film' ); ?></h2>
		<div class="lunara-debrief-why-body">
			<p><?php esc_html_e( 'A single recommendation just agrees with you. Three chosen on purpose form a triangle around the film: the echo shows what it shares with cinema history, the counter-program tests its argument, and the career context shows how it was made. Read all three and you understand the film you just read about better.', 'lunara-film' ); ?></p>
			<p><?php esc_html_e( 'Every pairing is chosen by hand, carries a one-line reason, and links into the Lunara Oscar Ledger, so each Debrief also leads into the site\'s Academy Awards history.', 'lunara-film' ); ?></p>
		</div>
	</section>

	<?php if ( '' !== trim( $debrief_specimen_cards ) ) : ?>
		<section class="lunara-debrief-specimen" aria-labelledby="lunara-debrief-specimen-title">
			<p class="lunara-debrief-kicker"><?php esc_html_e( 'A Debrief in the Wild', 'lunara-film' ); ?></p>
			<h2 id="lunara-debrief-specimen-title" class="lunara-debrief-section-title">
				<?php esc_html_e( 'From the review of', 'lunara-film' ); ?>
				<a href="<?php echo esc_url( (string) get_permalink( $debrief_specimen_id ) ); ?>"><?php echo esc_html( get_the_title( $debrief_specimen_id ) ); ?></a>
			</h2>
			<?php echo $debrief_specimen_cards; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- renderer escapes every value. ?>
		</section>
	<?php endif; ?>

	<?php if ( '' !== $debrief_content ) : ?>
		<section class="lunara-debrief-words">
			<p class="lunara-debrief-kicker"><?php esc_html_e( 'From the Desk', 'lunara-film' ); ?></p>
			<div class="lunara-debrief-words-body">
				<?php echo $debrief_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- editor content through the_content filters. ?>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( ! empty( $debrief_repeat_films ) ) : ?>
		<section class="lunara-debrief-canon" aria-labelledby="lunara-debrief-canon-title">
			<p class="lunara-debrief-kicker"><?php esc_html_e( 'The Debrief Canon', 'lunara-film' ); ?></p>
			<h2 id="lunara-debrief-canon-title" class="lunara-debrief-section-title"><?php esc_html_e( 'The films the desk keeps returning to.', 'lunara-film' ); ?></h2>
			<ol class="lunara-debrief-canon-list">
				<?php foreach ( array_slice( $debrief_repeat_films, 0, 8 ) as $debrief_film ) : ?>
					<?php
					$debrief_poster = ( '' !== $debrief_film['tt'] && function_exists( 'lunara_get_title_poster_html' ) )
						? (string) lunara_get_title_poster_html( $debrief_film['tt'], 'medium', 'lunara-debrief-canon-poster', $debrief_film['title'], 'lazy' )
						: '';
					$debrief_role_bits = array();
					foreach ( $debrief_roles as $debrief_slug => $debrief_role ) {
						$debrief_n = (int) $debrief_film['roles'][ $debrief_slug ];
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
						</div>
					</li>
				<?php endforeach; ?>
			</ol>
		</section>
	<?php endif; ?>

	<?php if ( ! empty( $debrief_index['recent'] ) ) : ?>
		<section class="lunara-debrief-recent" aria-labelledby="lunara-debrief-recent-title">
			<p class="lunara-debrief-kicker"><?php esc_html_e( 'Recent Debriefs', 'lunara-film' ); ?></p>
			<h2 id="lunara-debrief-recent-title" class="lunara-debrief-section-title"><?php esc_html_e( 'Read the review, then follow the three.', 'lunara-film' ); ?></h2>
			<ul class="lunara-debrief-recent-list">
				<?php foreach ( $debrief_index['recent'] as $debrief_entry ) : ?>
					<li class="lunara-debrief-recent-item">
						<a class="lunara-debrief-recent-review" href="<?php echo esc_url( (string) get_permalink( $debrief_entry['review_id'] ) ); ?>"><?php echo esc_html( get_the_title( $debrief_entry['review_id'] ) ); ?></a>
						<ul class="lunara-debrief-recent-pairs">
							<?php foreach ( $debrief_entry['pairs'] as $debrief_pair ) : ?>
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

	<nav class="lunara-debrief-next" aria-label="<?php esc_attr_e( 'Keep going', 'lunara-film' ); ?>">
		<a class="lunara-debrief-next-link" href="<?php echo esc_url( home_url( '/reviews/' ) ); ?>">
			<em><?php esc_html_e( 'Every review, every Debrief', 'lunara-film' ); ?></em>
			<span><?php esc_html_e( 'Browse the Reviews', 'lunara-film' ); ?> &rarr;</span>
		</a>
		<a class="lunara-debrief-next-link" href="<?php echo esc_url( home_url( '/oscars/' ) ); ?>">
			<em><?php esc_html_e( 'Where the pairings lead', 'lunara-film' ); ?></em>
			<span><?php esc_html_e( 'The Oscar Ledger', 'lunara-film' ); ?> &rarr;</span>
		</a>
	</nav>

</div>

<?php
get_footer();
