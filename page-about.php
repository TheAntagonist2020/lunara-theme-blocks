<?php
/**
 * Template Name: Lunara About
 * Template Post Type: page
 *
 * The desk's own dossier — composed from the house instruments so the
 * one page that introduces the critic stops being the one page that
 * looks like WordPress. Auto-applies to the page with slug "about";
 * assignable to any page via the template picker. The editor's block
 * content renders inside the composition (the "In their words" seat),
 * so copy stays editable without touching this layout.
 *
 * @package Lunara_Film
 */

get_header();

the_post();

$about_id      = get_the_ID();
$about_title   = get_the_title();
$about_content = trim( (string) apply_filters( 'the_content', get_the_content() ) );
$about_hero    = has_post_thumbnail( $about_id ) ? (string) get_the_post_thumbnail_url( $about_id, 'full' ) : '';

$latest_review_id  = 0;
$latest_review_ids = get_posts(
	array(
		'post_type'      => 'review',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
	)
);
if ( $latest_review_ids ) {
	$latest_review_id = (int) $latest_review_ids[0];
}

$review_total  = wp_count_posts( 'review' );
$review_total  = isset( $review_total->publish ) ? (int) $review_total->publish : 0;
$journal_total = wp_count_posts( 'journal' );
$journal_total = isset( $journal_total->publish ) ? (int) $journal_total->publish : 0;
?>

<div class="lunara-about-page">

	<section class="lunara-about-hero<?php echo '' !== $about_hero ? ' has-backdrop' : ''; ?>">
		<?php if ( '' !== $about_hero ) : ?>
			<div class="lunara-about-hero-backdrop" style="background-image:url('<?php echo esc_url( $about_hero ); ?>');" aria-hidden="true"></div>
		<?php endif; ?>
		<div class="lunara-about-hero-overlay" aria-hidden="true"></div>
		<div class="lunara-about-hero-inner">
			<p class="lunara-about-kicker"><?php esc_html_e( 'The Desk', 'lunara-film' ); ?></p>
			<h1 class="lunara-about-title"><?php echo esc_html( $about_title ); ?></h1>
			<p class="lunara-about-thesis"><?php echo esc_html( apply_filters( 'lunara_about_thesis', __( 'Film criticism and the living record of the Oscars — one publication, argued by a critic, never served by an algorithm.', 'lunara-film' ) ) ); ?></p>
		</div>
	</section>

	<section class="lunara-about-pillars" aria-label="<?php esc_attr_e( 'What the desk publishes', 'lunara-film' ); ?>">
		<a class="lunara-about-pillar" href="<?php echo esc_url( home_url( '/reviews/' ) ); ?>">
			<span class="lunara-about-pillar-label"><?php esc_html_e( 'Criticism', 'lunara-film' ); ?></span>
			<strong><?php echo esc_html( sprintf( /* translators: %d: published review count */ _n( '%d review on file', '%d reviews on file', $review_total, 'lunara-film' ), $review_total ) ); ?></strong>
			<span class="lunara-about-pillar-copy"><?php esc_html_e( 'Every review ends with three more films — a Theme Echo, a Counter-Program, a Career Context.', 'lunara-film' ); ?></span>
		</a>
		<a class="lunara-about-pillar" href="<?php echo esc_url( home_url( '/journal/' ) ); ?>">
			<span class="lunara-about-pillar-label"><?php esc_html_e( 'The Journal', 'lunara-film' ); ?></span>
			<strong><?php echo esc_html( sprintf( /* translators: %d: published journal count */ _n( '%d file from the desk', '%d files from the desk', $journal_total, 'lunara-film' ), $journal_total ) ); ?></strong>
			<span class="lunara-about-pillar-copy"><?php esc_html_e( 'News, trailers, and industry movement — opinion arrives fast, sources stay named.', 'lunara-film' ); ?></span>
		</a>
		<a class="lunara-about-pillar" href="<?php echo esc_url( home_url( '/oscars/' ) ); ?>">
			<span class="lunara-about-pillar-label"><?php esc_html_e( 'The Oscar Ledger', 'lunara-film' ); ?></span>
			<strong><?php esc_html_e( 'Academy Awards history, complete', 'lunara-film' ); ?></strong>
			<span class="lunara-about-pillar-copy"><?php esc_html_e( 'Every ceremony, category, film, and name — treated like a living editorial system.', 'lunara-film' ); ?></span>
		</a>
	</section>

	<?php if ( '' !== $about_content ) : ?>
		<section class="lunara-about-words">
			<p class="lunara-about-kicker"><?php esc_html_e( 'In Their Words', 'lunara-film' ); ?></p>
			<div class="lunara-about-words-body">
				<?php echo $about_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- editor content through the_content filters. ?>
			</div>
		</section>
	<?php endif; ?>

	<section class="lunara-about-method" aria-label="<?php esc_attr_e( 'How the desk works', 'lunara-film' ); ?>">
		<p class="lunara-about-kicker"><?php esc_html_e( 'How the Desk Works', 'lunara-film' ); ?></p>
		<ol class="lunara-about-method-list">
			<li><strong><?php esc_html_e( 'Watch', 'lunara-film' ); ?></strong><span><?php esc_html_e( 'Every film gets the encounter — the room, the screen, the honest limit.', 'lunara-film' ); ?></span></li>
			<li><strong><?php esc_html_e( 'Argue', 'lunara-film' ); ?></strong><span><?php esc_html_e( 'The review owes the reader a verdict, a reason, and the next three moves.', 'lunara-film' ); ?></span></li>
			<li><strong><?php esc_html_e( 'Record', 'lunara-film' ); ?></strong><span><?php esc_html_e( 'Everything joins the graph — films, talent, and the ledger, cross-linked for keeps.', 'lunara-film' ); ?></span></li>
		</ol>
		<?php if ( $latest_review_id ) : ?>
			<a class="lunara-about-latest" href="<?php echo esc_url( (string) get_permalink( $latest_review_id ) ); ?>">
				<em><?php esc_html_e( 'Freshest from the desk', 'lunara-film' ); ?></em>
				<span><?php echo esc_html( get_the_title( $latest_review_id ) ); ?> &rarr;</span>
			</a>
		<?php endif; ?>
	</section>

</div>

<?php
get_footer();
