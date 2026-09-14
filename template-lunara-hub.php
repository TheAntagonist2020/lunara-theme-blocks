<?php
/**
 * Template Name: Lunara Hub
 * Description: Full-width canvas for composing pages with Lunara blocks
 * (cinematic-hero, journal-lane, oscar-picks, oscar-facts) and any other
 * Gutenberg core blocks. Same architecture as the homepage front-page.php
 * but available for any Page — pick "Lunara Hub" in the editor's Template
 * dropdown (or assign via MCP with template="template-lunara-hub.php").
 *
 * Use cases: landing pages, awards-season campaign pages, editorial
 * spotlights, special-event microsites, ceremony reveal pages.
 *
 * @package Lunara_Film
 * @since 2026-05-10
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main id="primary" class="site-main lunara-front-page lunara-hub-page">
	<?php
	while ( have_posts() ) :
		the_post();
		the_content();
	endwhile;
	?>
</main>

<?php
get_footer();
