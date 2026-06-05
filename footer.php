<?php
/**
 * Blocksy-powered footer shell for the Lunara child theme.
 *
 * This restores Blocksy's footer builder and closing shell while keeping the
 * child theme in control of custom content templates.
 *
 * @package Lunara_Film
 */

blocksy_after_current_template();
do_action( 'blocksy:content:bottom' );

?>
    </main>

    <?php
    do_action( 'blocksy:content:after' );
    do_action( 'blocksy:footer:before' );

    blocksy_output_footer();

    do_action( 'blocksy:footer:after' );
    ?>
</div>

<?php wp_footer(); ?>

</body>
</html>
