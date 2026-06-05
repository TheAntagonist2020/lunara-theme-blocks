<?php
/**
 * Front Page — PATH B (Gutenberg blocks driven)
 *
 * Replaced 2026-05-10. The legacy ~1,150-line template that hard-coded every
 * homepage section (hero, latest reviews, journal, picks, facts, etc.) has been
 * snapshotted to front-page.php.bak-pre-pathB-20260510. The current template
 * just renders the Home Page's content — and the Home Page now contains
 * Gutenberg blocks (lunara/cinematic-hero, lunara/journal-lane, lunara/oscar-picks,
 * lunara/oscar-facts) plus any other blocks Dalton or the MCP drops in.
 *
 * To revert: restore from front-page.php.bak-pre-pathB-20260510 and remove
 * the block registrations in functions.php (search for "PATH B — HOMEPAGE AS
 * GUTENBERG BLOCKS").
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main id="primary" class="site-main lunara-front-page">
	<h1 class="screen-reader-text lunara-screen-reader-text"><?php echo esc_html( get_bloginfo( 'name' ) ? get_bloginfo( 'name' ) : __( 'Lunara Film', 'lunara-film' ) ); ?></h1>
	<?php
	while ( have_posts() ) :
		the_post();
		the_content();
	endwhile;
	?>
</main>

<?php
get_footer();
