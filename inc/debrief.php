<?php
/**
 * Lunara Debrief - Review Metadata
 *
 * Defines the Debrief meta box, star renderer, auto-fill parser,
 * Oscar ledger utilities, IMDb title map helpers, and the
 * [lunara_debrief] shortcode.
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ========================================
   LUNARA DEBRIEF - REVIEW METADATA
   ======================================== */

/**
 * Render star rating
 */
if ( ! function_exists( 'lunara_render_stars' ) ) {
    function lunara_render_stars( $score ) {
        if ( empty( $score ) ) {
            return '';
        }

        $score = floatval( $score );
        $full_stars = floor( $score );
        $half_star = ( $score - $full_stars ) >= 0.5;

        $output = '<span class="lunara-stars">';
        for ( $i = 0; $i < $full_stars; $i++ ) {
            $output .= '★';
        }
        if ( $half_star ) {
            $output .= '½';
        }
        $output .= '</span>';

        return $output;
    }
}

/**
 * Add Lunara Debrief meta box to Reviews
 */
if ( ! function_exists( 'lunara_get_career_context_meta' ) ) {
    /**
     * Read the current Career Context field with legacy Craft Mirror fallback.
     *
     * @param int $post_id Review post ID.
     * @return string
     */
    function lunara_get_career_context_meta( $post_id ) {
        $career_context = get_post_meta( $post_id, '_lunara_career_context', true );
        if ( '' !== trim( (string) $career_context ) ) {
            return $career_context;
        }

        return get_post_meta( $post_id, '_lunara_craft_mirror', true );
    }
}

if ( ! defined( 'LUNARA_CORE_VERSION' ) ) {
    function lunara_add_debrief_meta_box() {
        add_meta_box(
            'lunara_debrief_meta',
            'Lunara Debrief',
            'lunara_debrief_meta_callback',
            'review',
            'normal',
            'high'
        );
    }
    add_action( 'add_meta_boxes', 'lunara_add_debrief_meta_box' );

    /**
     * Debrief meta box callback
     */
    function lunara_debrief_meta_callback( $post ) {
        wp_nonce_field( 'lunara_debrief_nonce', 'lunara_debrief_nonce' );

        $score = get_post_meta( $post->ID, '_lunara_score', true );
        $year = get_post_meta( $post->ID, '_lunara_year', true );
        $imdb_review_id = get_post_meta( $post->ID, '_lunara_imdb_title_id', true );
        $where = get_post_meta( $post->ID, '_lunara_where', true );
        $theme_echo = get_post_meta( $post->ID, '_lunara_theme_echo', true );
        $counter = get_post_meta( $post->ID, '_lunara_counter_program', true );
        $craft = lunara_get_career_context_meta( $post->ID );
        ?>
        <style>
            .lunara-meta-field { margin-bottom: 15px; }
            .lunara-meta-field label { display: block; font-weight: 600; margin-bottom: 5px; }
            .lunara-meta-field input, .lunara-meta-field select, .lunara-meta-field textarea { width: 100%; }
            .lunara-meta-field .description { font-style: italic; color: #666; font-size: 12px; margin-top: 4px; }
            .lunara-meta-section { margin-top: 20px; padding-top: 15px; border-top: 1px solid #ddd; }
            .lunara-meta-section h4 { margin: 0 0 15px; color: #c9a961; }
            .lunara-meta-row { display: flex; gap: 20px; }
            .lunara-meta-row .lunara-meta-field { flex: 1; }
            .lunara-pair-preview { margin-top: 18px; border: 1px solid #d8c38a; border-radius: 10px; background: #071523; overflow: hidden; color: #f7f1dd; }
            .lunara-pair-preview-head { display: flex; justify-content: space-between; gap: 12px; padding: 12px 14px; border-bottom: 1px solid rgba(216, 195, 138, 0.28); background: rgba(216, 195, 138, 0.08); }
            .lunara-pair-preview-head strong { color: #e4c875; text-transform: uppercase; letter-spacing: .08em; }
            .lunara-pair-preview-head span { color: #b8c3cf; font-size: 12px; }
            .lunara-pair-preview-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; padding: 14px; }
            .lunara-pair-preview-card { display: grid; grid-template-columns: 74px minmax(0, 1fr); gap: 12px; min-height: 122px; padding: 10px; border: 1px solid rgba(255,255,255,.12); border-radius: 8px; background: rgba(255,255,255,.045); }
            .lunara-pair-preview-card.is-warning { border-color: rgba(214, 126, 70, .85); }
            .lunara-pair-preview-card.is-empty { opacity: .72; }
            .lunara-pair-preview-media { width: 74px; aspect-ratio: 2 / 3; border-radius: 6px; overflow: hidden; background: rgba(255,255,255,.07); display: flex; align-items: center; justify-content: center; color: #8f9aa7; font-size: 11px; text-align: center; }
            .lunara-pair-preview-thumb { display: block; width: 100%; height: 100%; object-fit: cover; }
            .lunara-pair-preview-role { margin: 0 0 4px; color: #e4c875; font-size: 11px; font-weight: 700; letter-spacing: .09em; text-transform: uppercase; }
            .lunara-pair-preview-title { margin: 0 0 5px; color: #fff; font-size: 14px; line-height: 1.25; }
            .lunara-pair-preview-note { margin: 0 0 8px; color: #c8d0d8; font-size: 12px; line-height: 1.35; }
            .lunara-pair-preview-chips { display: flex; flex-wrap: wrap; gap: 5px; margin-top: 7px; }
            .lunara-pair-preview-chip { display: inline-flex; align-items: center; min-height: 20px; padding: 2px 7px; border: 1px solid rgba(228, 200, 117, .42); border-radius: 999px; color: #f2d986; font-size: 11px; line-height: 1.2; text-decoration: none; }
            .lunara-pair-preview-chip.is-muted { border-color: rgba(255,255,255,.18); color: #aeb8c4; }
            .lunara-pair-preview-warnings { margin: 8px 0 0; padding-left: 16px; color: #ffb07b; font-size: 12px; }
            .lunara-pair-preview-warnings li { margin: 0 0 4px; }
            @media (max-width: 1100px) { .lunara-pair-preview-grid { grid-template-columns: 1fr; } }
        </style>

        <div class="lunara-meta-row">
            <div class="lunara-meta-field">
                <label for="lunara_score">Score (0-5, use .5 for half stars)</label>
                <input type="text" id="lunara_score" name="lunara_score" value="<?php echo esc_attr( $score ); ?>" placeholder="4.5">
                <p class="description">Examples: 4, 4.5, 5 → ★★★★, ★★★★½, ★★★★★</p>
            </div>

            <div class="lunara-meta-field">
                <label for="lunara_year">Year Released</label>
                <select id="lunara_year" name="lunara_year">
                    <option value="">— Select Year —</option>
                    <?php
                    $current_year = (int) date('Y') + 2; // Allow 2 years ahead for upcoming films
                    for ( $y = $current_year; $y >= 1920; $y-- ) :
                    ?>
                        <option value="<?php echo $y; ?>" <?php selected( $year, $y ); ?>><?php echo $y; ?></option>
                    <?php endfor; ?>
                </select>
            </div>

    </div>

        <div class="lunara-meta-field">
            <label for="lunara_imdb_title_id">IMDb Title ID (for this review)</label>
            <input type="text" id="lunara_imdb_title_id" name="lunara_imdb_title_id" value="<?php echo esc_attr( $imdb_review_id ); ?>" placeholder="tt1234567">
            <p class="description">Connects this review to the Oscars database film page (shows a "Lunara Review" module on /oscars/title/tt…/).</p>
        </div>

        <div class="lunara-meta-field">
            <label for="lunara_where">Where to Watch</label>
            <textarea id="lunara_where" name="lunara_where" rows="5" placeholder="One per line. Examples:&#10;In Theaters&#10;Netflix&#10;Max | https://play.max.com/video/watch/123&#10;Amazon | https://amazon.com/example | affiliate"><?php echo esc_textarea( $where ); ?></textarea>
            <p class="description">Use service names, theater status, URL alone, Label | URL, or Label | URL | affiliate for future sponsored links.</p>
        </div>

        <div class="lunara-meta-section">
            <h4>CINEMATIC IMAGE STRUCTURE</h4>

            <div class="lunara-meta-field">
                <label for="lunara_review_hero_banner">Hero Banner Image URL</label>
                <input type="url" id="lunara_review_hero_banner" name="lunara_review_hero_banner" value="<?php echo esc_attr( get_post_meta( $post->ID, '_lunara_review_hero_banner', true ) ); ?>" placeholder="https://...">
                <p class="description">Wide, textless still that sits under the title and rating before the review begins.</p>
            </div>

            <div class="lunara-meta-field">
                <label for="lunara_review_hero_banner_caption">Hero Banner Caption (Optional)</label>
                <input type="text" id="lunara_review_hero_banner_caption" name="lunara_review_hero_banner_caption" value="<?php echo esc_attr( get_post_meta( $post->ID, '_lunara_review_hero_banner_caption', true ) ); ?>" placeholder="Optional context or source note">
            </div>

            <div class="lunara-meta-field">
                <label for="lunara_review_context_shot">Context Shot Image URL</label>
                <input type="url" id="lunara_review_context_shot" name="lunara_review_context_shot" value="<?php echo esc_attr( get_post_meta( $post->ID, '_lunara_review_context_shot', true ) ); ?>" placeholder="https://...">
                <p class="description">Usually lands after the introductory movement, near the first major subheading.</p>
            </div>

            <div class="lunara-meta-field">
                <label for="lunara_review_context_shot_caption">Context Shot Caption (Optional)</label>
                <input type="text" id="lunara_review_context_shot_caption" name="lunara_review_context_shot_caption" value="<?php echo esc_attr( get_post_meta( $post->ID, '_lunara_review_context_shot_caption', true ) ); ?>" placeholder="Optional context or source note">
            </div>

            <div class="lunara-meta-field">
                <label for="lunara_review_visual_evidence">Visual Evidence Image URL</label>
                <input type="url" id="lunara_review_visual_evidence" name="lunara_review_visual_evidence" value="<?php echo esc_attr( get_post_meta( $post->ID, '_lunara_review_visual_evidence', true ) ); ?>" placeholder="https://...">
                <p class="description">Use for the frame that proves a point about craft, performance, lighting, or composition.</p>
            </div>

            <div class="lunara-meta-field">
                <label for="lunara_review_visual_evidence_caption">Visual Evidence Caption (Optional)</label>
                <input type="text" id="lunara_review_visual_evidence_caption" name="lunara_review_visual_evidence_caption" value="<?php echo esc_attr( get_post_meta( $post->ID, '_lunara_review_visual_evidence_caption', true ) ); ?>" placeholder="Optional context or source note">
            </div>

            <div class="lunara-meta-field">
                <label for="lunara_review_thematic_echo">Thematic Echo Image URL (Optional)</label>
                <input type="url" id="lunara_review_thematic_echo" name="lunara_review_thematic_echo" value="<?php echo esc_attr( get_post_meta( $post->ID, '_lunara_review_thematic_echo', true ) ); ?>" placeholder="https://...">
                <p class="description">An evocative late still for longer essays, usually placed before the closing movement.</p>
            </div>

            <div class="lunara-meta-field">
                <label for="lunara_review_thematic_echo_caption">Thematic Echo Caption (Optional)</label>
                <input type="text" id="lunara_review_thematic_echo_caption" name="lunara_review_thematic_echo_caption" value="<?php echo esc_attr( get_post_meta( $post->ID, '_lunara_review_thematic_echo_caption', true ) ); ?>" placeholder="Optional context or source note">
            </div>
        </div>

        <div class="lunara-meta-section">
            <h4>PAIR IT WITH</h4>

            <div class="lunara-meta-field">
                <label for="lunara_theme_echo">Theme Echo</label>
                <input type="text" id="lunara_theme_echo" name="lunara_theme_echo" value="<?php echo esc_attr( $theme_echo ); ?>" placeholder="Film that shares thematic DNA">
                <p class="description">Tip: for clickable internal + IMDb links, you can append <code>| tt1234567</code> or paste a full IMDb URL anywhere in the line. No punctuation is required after the IMDb ID before your note.</p>
            </div>

            <div class="lunara-meta-field">
                <label for="lunara_counter_program">Counter-Program</label>
                <input type="text" id="lunara_counter_program" name="lunara_counter_program" value="<?php echo esc_attr( $counter ); ?>" placeholder="Film that offers opposing perspective">
                <p class="description">Tip: optionally add <code>| tt1234567</code> (or an IMDb URL) to enable direct links. No punctuation is required after the IMDb ID before your note.</p>
            </div>

            <div class="lunara-meta-field">
                <label for="lunara_career_context">Career Context (Optional)</label>
                <input type="text" id="lunara_career_context" name="lunara_career_context" value="<?php echo esc_attr( $craft ); ?>" placeholder="Film that clarifies this artist's career or creative trajectory">
                <p class="description">Tip: optionally add <code>| tt1234567</code> (or an IMDb URL) to enable direct links. No punctuation is required after the IMDb ID before your note.</p>
            </div>

            <?php
            if ( function_exists( 'lunara_render_pair_it_with_admin_preview' ) ) {
                echo lunara_render_pair_it_with_admin_preview(
                    $post->ID,
                    array(
                        'Theme Echo'      => $theme_echo,
                        'Counter-Program' => $counter,
                        'Career Context'  => $craft,
                    )
                );
            }
            ?>
        </div>
        <?php
    }

    /**
     * Save Debrief meta
     */
    function lunara_save_debrief_meta( $post_id ) {
        if ( ! isset( $_POST['lunara_debrief_nonce'] ) ) return;
        if ( ! wp_verify_nonce( $_POST['lunara_debrief_nonce'], 'lunara_debrief_nonce' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        $text_fields = array( 'lunara_score', 'lunara_year', 'lunara_imdb_title_id', 'lunara_theme_echo', 'lunara_counter_program', 'lunara_career_context' );
        $textarea_fields = array( 'lunara_where' );
        $url_fields  = array( 'lunara_review_hero_banner', 'lunara_review_context_shot', 'lunara_review_visual_evidence', 'lunara_review_thematic_echo' );
        $caption_fields = array( 'lunara_review_hero_banner_caption', 'lunara_review_context_shot_caption', 'lunara_review_visual_evidence_caption', 'lunara_review_thematic_echo_caption' );

        foreach ( $text_fields as $field ) {
            if ( isset( $_POST[ $field ] ) ) {
                update_post_meta( $post_id, '_' . $field, sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) );
            }
        }
        foreach ( $textarea_fields as $field ) {
            if ( isset( $_POST[ $field ] ) ) {
                update_post_meta( $post_id, '_' . $field, sanitize_textarea_field( wp_unslash( $_POST[ $field ] ) ) );
            }
        }
        foreach ( $url_fields as $field ) {
            if ( isset( $_POST[ $field ] ) ) {
                update_post_meta( $post_id, '_' . $field, esc_url_raw( wp_unslash( $_POST[ $field ] ) ) );
            }
        }

        foreach ( $caption_fields as $field ) {
            if ( isset( $_POST[ $field ] ) ) {
                update_post_meta( $post_id, '_' . $field, sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) );
            }
        }
    }
    add_action( 'save_post_review', 'lunara_save_debrief_meta' );
}

/**
 * Auto-fill review meta from pasted HTML content.
 *
 * Dalton writes reviews externally and pastes HTML into the Code editor.
 * This parser reads the content on save and auto-populates any EMPTY meta fields
 * from patterns already present in the HTML:
 *
 *   <!-- "Title" (2026) — tt12345678 -->          → IMDb ID, Year
 *   Score: ⭐⭐⭐                                   → Score
 *   Where to Watch: Theatrical / Digital           → Where to Watch
 *   Theme Echo: <em>Title</em> (YYYY) tt... — ...  → Theme Echo pairing
 *   Counter-Program: <em>Title</em> ...            → Counter-Program pairing
 *   Career Context: <em>Title</em> ...             → Career Context pairing
 *   <!-- Director: Name / Runtime: 135 min / Studio: Name -->  → Detail fields
 *
 * Only fills EMPTY fields — never overwrites manually entered data.
 */
function lunara_autofill_review_meta_from_content( $post_id ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( 'review' !== get_post_type( $post_id ) ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    $content = (string) get_post_field( 'post_content', $post_id );
    if ( '' === trim( $content ) ) {
        return;
    }

    // Helper: only set if the user didn't manually type something in the meta box.
    // We check $_POST to see if the form field was submitted empty — if so, the user
    // didn't fill it, and we should auto-fill from the content.
    $fill = static function( $post_id, $meta_key, $value ) {
        $value = trim( (string) $value );
        if ( '' === $value ) {
            return;
        }

        // Map meta keys to their form field names
        $form_field_map = array(
            '_lunara_score'            => 'lunara_score',
            '_lunara_year'             => 'lunara_year',
            '_lunara_imdb_title_id'    => 'lunara_imdb_title_id',
            '_lunara_where'            => 'lunara_where',
            '_lunara_theme_echo'       => 'lunara_theme_echo',
            '_lunara_counter_program'  => 'lunara_counter_program',
            '_lunara_career_context'   => 'lunara_career_context',
            '_lunara_craft_mirror'     => 'lunara_craft_mirror',
            '_lunara_director'         => 'lunara_director',
            '_lunara_runtime'          => 'lunara_runtime',
            '_lunara_studio'           => 'lunara_studio',
        );

        // If the form field was submitted with a value, the user typed something — don't overwrite.
        $form_field = isset( $form_field_map[ $meta_key ] ) ? $form_field_map[ $meta_key ] : '';
        if ( '' !== $form_field && isset( $_POST[ $form_field ] ) && '' !== trim( (string) $_POST[ $form_field ] ) ) {
            return;
        }

        update_post_meta( $post_id, $meta_key, sanitize_text_field( $value ) );
    };

    // 1) IMDb title ID from header comment: <!-- "Title" (2026) — tt12345678 -->
    if ( preg_match( '/<!--.*?(tt\d{7,8}).*?-->/', $content, $m ) ) {
        $fill( $post_id, '_lunara_imdb_title_id', $m[1] );
    }

    // 2) Year from header comment: <!-- "Title" (2026) -->
    if ( preg_match( '/<!--.*?\((\d{4})\).*?-->/', $content, $m ) ) {
        $fill( $post_id, '_lunara_year', $m[1] );
    }

    // 3) Score — supports multiple formats:
    //    Score: ⭐⭐⭐        (star emojis)
    //    Score: ⭐⭐⭐½       (star emojis + half)
    //    Score: 3 out of 5    (text)
    //    Score: 3/5           (fraction)
    //    Score: 3.5           (decimal)
    //    Score: 3             (bare number)
    $score_found = '';
    // Try star emojis first
    if ( preg_match( '/Score:<\/strong>\s*([\x{2B50}\x{2605}\x{00BD}]+)/u', $content, $m ) ) {
        $stars_str = $m[1];
        $full = preg_match_all( '/[\x{2B50}\x{2605}]/u', $stars_str );
        $half = preg_match( '/\x{00BD}/u', $stars_str ) ? 0.5 : 0;
        $score_found = (string) ( $full + $half );
    }
    // Try "X out of 5" or "X/5"
    if ( '' === $score_found && preg_match( '/Score:<\/strong>\s*(\d+(?:\.\d+)?)\s*(?:out\s+of\s+5|\/\s*5)/i', $content, $m ) ) {
        $score_found = $m[1];
    }
    // Try bare number after Score:
    if ( '' === $score_found && preg_match( '/Score:<\/strong>\s*(\d+(?:\.\d+)?)\b/', $content, $m ) ) {
        $val = floatval( $m[1] );
        if ( $val > 0 && $val <= 5 ) {
            $score_found = $m[1];
        }
    }
    // Also try without <strong> tags (plain text)
    if ( '' === $score_found && preg_match( '/Score:\s*(\d+(?:\.\d+)?)\s*(?:out\s+of\s+5|\/\s*5)/i', $content, $m ) ) {
        $score_found = $m[1];
    }
    if ( '' === $score_found && preg_match( '/Score:\s*(\d+(?:\.\d+)?)\b/', $content, $m ) ) {
        $val = floatval( $m[1] );
        if ( $val > 0 && $val <= 5 ) {
            $score_found = $m[1];
        }
    }
    if ( '' !== $score_found ) {
        $fill( $post_id, '_lunara_score', $score_found );
    }

    // 4) Where to Watch
    if ( preg_match( '/Where\s+to\s+Watch:\s*(.+)/i', $content, $m ) ) {
        $value = wp_strip_all_tags( $m[1] );
        $value = preg_replace( '/\s*<.*$/', '', $value );
        $fill( $post_id, '_lunara_where', $value );
    }

    // 5) Pair It With — Theme Echo
    if ( preg_match( '/Theme\s+Echo:\s*(.+)/i', $content, $m ) ) {
        $fill( $post_id, '_lunara_theme_echo', wp_strip_all_tags( html_entity_decode( $m[1] ) ) );
    }

    // 6) Pair It With — Counter-Program
    if ( preg_match( '/Counter[\-\s]Program:\s*(.+)/i', $content, $m ) ) {
        $fill( $post_id, '_lunara_counter_program', wp_strip_all_tags( html_entity_decode( $m[1] ) ) );
    }

    // 7) Pair It With — Career Context (or Craft Mirror)
    if ( preg_match( '/Career\s+Context:\s*(.+)/i', $content, $m ) ) {
        $fill( $post_id, '_lunara_career_context', wp_strip_all_tags( html_entity_decode( $m[1] ) ) );
    } elseif ( preg_match( '/Craft\s+Mirror:\s*(.+)/i', $content, $m ) ) {
        $fill( $post_id, '_lunara_career_context', wp_strip_all_tags( html_entity_decode( $m[1] ) ) );
    }

    // 8) Director from comment: <!-- Director: Name -->
    if ( preg_match( '/Director:\s*([^\/\n<]+)/i', $content, $m ) ) {
        $director = trim( $m[1] );
        // Skip if it's the meta box label or a URL
        if ( strlen( $director ) > 2 && strlen( $director ) < 100 && ! preg_match( '/^(Director|http)/i', $director ) ) {
            $fill( $post_id, '_lunara_director', $director );
        }
    }

    // 9) Runtime from comment: <!-- Runtime: 135 min -->
    if ( preg_match( '/Runtime:\s*(\d+\s*min[^\/\n<]*)/i', $content, $m ) ) {
        $fill( $post_id, '_lunara_runtime', trim( $m[1] ) );
    }

    // 10) Studio from comment: <!-- Studio: Name --> or <!-- Studio / Distributor: Name -->
    if ( preg_match( '/Studio(?:\s*\/\s*Distributor)?:\s*([^\/\n<]+)/i', $content, $m ) ) {
        $studio = trim( $m[1] );
        if ( strlen( $studio ) > 1 && strlen( $studio ) < 100 ) {
            $fill( $post_id, '_lunara_studio', $studio );
        }
    }
}
add_action( 'save_post_review', 'lunara_autofill_review_meta_from_content', 50 );

/**
 * Load the bundled IMDb title map (title|year -> ttID).
 * This lets Debrief lines link directly to IMDb (and Lunara Oscars film pages)
 * without requiring you to paste a tt-id every time.
 *
 * File: /assets/data/imdb-title-map.json
 *
 * Key format: "<normalized_title>|<year>" => "tt1234567"
 */

/**
 * Resolve the active Academy Awards DB table name (supports multiple plugin variants).
 */
function lunara_awards_table_name() {
    static $resolved_table = null;

    if ( $resolved_table !== null ) {
        return $resolved_table;
    }

    $cached_table = get_transient( 'lunara_awards_table_name_v1' );
    if ( is_string( $cached_table ) ) {
        $resolved_table = $cached_table;
        return $resolved_table;
    }

    global $wpdb;
    $candidates = array(
        $wpdb->prefix . 'academy_awards',
        $wpdb->prefix . 'academy_awards_table',
        $wpdb->prefix . 'aat_awards',
        $wpdb->prefix . 'lunara_academy_awards',
        $wpdb->prefix . 'lunara_awards',
    );
    foreach ( $candidates as $t ) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $found = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $t ) );
        if ( $found ) {
            $resolved_table = $t;
            set_transient( 'lunara_awards_table_name_v1', $resolved_table, 12 * HOUR_IN_SECONDS );
            return $resolved_table;
        }
    }

    $resolved_table = '';
    set_transient( 'lunara_awards_table_name_v1', $resolved_table, HOUR_IN_SECONDS );
    return $resolved_table;
}

/**
 * Get Oscar nominations/wins counts for a film by IMDb title id (tt...).
 * Returns array( 'noms' => int, 'wins' => int ).
 */
function lunara_get_oscar_ledger_counts( $tt ) {
    $tt = strtolower( trim( (string) $tt ) );
    if ( $tt === '' || ! preg_match( '/^tt\d{7,8}$/', $tt ) ) {
        return array( 'noms' => 0, 'wins' => 0 );
    }

    $cache_key = 'lunara_oscar_ledger_' . $tt;
    $cached = get_transient( $cache_key );
    if ( is_array( $cached ) && isset( $cached['noms'], $cached['wins'] ) ) {
        return $cached;
    }

    $table = lunara_awards_table_name();
    if ( $table === '' ) {
        return array( 'noms' => 0, 'wins' => 0 );
    }

    global $wpdb;

    // Fetch both totals in one pass so single-review pages do less database work.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $row = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT COUNT(*) AS noms, SUM(CASE WHEN winner = 1 THEN 1 ELSE 0 END) AS wins FROM {$table} WHERE film_id = %s",
            $tt
        ),
        ARRAY_A
    );

    $out = array(
        'noms' => isset( $row['noms'] ) ? (int) $row['noms'] : 0,
        'wins' => isset( $row['wins'] ) ? (int) $row['wins'] : 0,
    );
    set_transient( $cache_key, $out, 6 * HOUR_IN_SECONDS );
    return $out;
}

/**
 * Render the Oscar Ledger pill (clicks into Lunara's Oscars film page).
 */
function lunara_render_oscar_ledger_pill( $tt, $counts = null ) {
    $tt = strtolower( trim( (string) $tt ) );
    if ( $tt === '' || ! preg_match( '/^tt\d{7,8}$/', $tt ) ) {
        return '';
    }

    if ( ! is_array( $counts ) ) {
        $counts = lunara_get_oscar_ledger_counts( $tt );
    }

    $noms = (int) ( $counts['noms'] ?? 0 );
    $wins = (int) ( $counts['wins'] ?? 0 );

    // Keep it classy: only show when the film actually has Oscar presence.
    if ( $noms <= 0 ) {
        return '';
    }

    $href = home_url( '/oscars/title/' . $tt . '/' );
    $label = sprintf( '%d nominations • %d wins', $noms, $wins );

    return '<a class="lunara-oscar-ledger" href="' . esc_url( $href ) . '">'
        . '<span class="lunara-oscar-ledger-pill">Oscar Ledger</span>'
        . '<span class="lunara-oscar-ledger-counts">' . esc_html( $label ) . '</span>'
        . '</a>';
}


function lunara_imdb_title_map() {
    static $map = null;
    if ( $map !== null ) {
        return $map;
    }

    $map = array();
    $asset = lunara_resolve_theme_asset(
        'assets/data/imdb-title-map.json',
        array( 'imdb-title-map.json' )
    );
    $file = $asset['path'];

    if ( $file && file_exists( $file ) ) {
        $json = file_get_contents( $file );
        $data = json_decode( $json, true );
        if ( is_array( $data ) ) {
            $map = $data;
        }
    }

    return $map;
}

/**
 * Normalize a title to a stable lookup key.
 */
function lunara_normalize_title_key( $title ) {
    $t = strtolower( remove_accents( (string) $title ) );
    $t = str_replace( '&', 'and', $t );
    $t = preg_replace( '/[^a-z0-9]+/', ' ', $t );
    $t = trim( preg_replace( '/\s+/', ' ', $t ) );
    return $t;
}

if ( ! function_exists( 'lunara_get_review_permalink_by_imdb_title_id' ) ) {
    function lunara_get_review_permalink_by_imdb_title_id( $tt, $exclude_post_id = 0 ) {
        global $wpdb;

        $tt = strtolower( trim( (string) $tt ) );
        if ( ! preg_match( '/^tt\d{7,8}$/', $tt ) ) {
            return '';
        }

        $exclude_post_id = absint( $exclude_post_id );
        $sql             = "
            SELECT p.ID
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm
                ON pm.post_id = p.ID
            WHERE p.post_type = 'review'
              AND p.post_status = 'publish'
              AND pm.meta_key = '_lunara_imdb_title_id'
              AND LOWER(pm.meta_value) LIKE %s
        ";
        $params          = array( '%' . $wpdb->esc_like( $tt ) . '%' );

        if ( $exclude_post_id > 0 ) {
            $sql      .= ' AND p.ID != %d';
            $params[] = $exclude_post_id;
        }

        $sql .= ' ORDER BY p.post_date_gmt DESC, p.ID DESC LIMIT 1';

        $review_id = (int) $wpdb->get_var( $wpdb->prepare( $sql, $params ) );

        return $review_id > 0 ? (string) get_permalink( $review_id ) : '';
    }
}

if ( ! function_exists( 'lunara_get_internal_title_reference_url' ) ) {
    function lunara_get_internal_title_reference_url( $tt, $exclude_post_id = 0 ) {
        $tt = strtolower( trim( (string) $tt ) );
        if ( ! preg_match( '/^tt\d{7,8}$/', $tt ) ) {
            return '';
        }

        $review_url = lunara_get_review_permalink_by_imdb_title_id( $tt, $exclude_post_id );
        if ( '' !== $review_url ) {
            return $review_url;
        }

        if ( function_exists( 'lunara_get_oscar_ledger_counts' ) ) {
            $counts = lunara_get_oscar_ledger_counts( $tt );
            if ( intval( $counts['noms'] ?? 0 ) <= 0 ) {
                return '';
            }
        }

        if ( function_exists( 'lunara_get_oscars_title_url' ) ) {
            return (string) lunara_get_oscars_title_url( $tt );
        }

        return '';
    }
}

if ( ! function_exists( 'lunara_get_title_poster_html' ) ) {
    function lunara_get_title_poster_html( $tt, $size = 'medium', $class = 'lunara-debrief-thumb', $title = '', $loading = 'lazy' ) {
        $tt      = strtolower( trim( (string) $tt ) );
        $size    = trim( (string) $size );
        $class   = trim( (string) $class );
        $title   = trim( (string) $title );
        $loading = 'eager' === trim( (string) $loading ) ? 'eager' : 'lazy';

        if ( ! preg_match( '/^tt\d{7,8}$/', $tt ) ) {
            return '';
        }

        if ( '' === $size ) {
            $size = 'medium';
        }

        if ( '' === $class ) {
            $class = 'lunara-debrief-thumb';
        }

        $build_img = static function( $url, $resolved_title ) use ( $class, $loading ) {
            $url = trim( (string) $url );
            if ( '' === $url ) {
                return '';
            }

            $alt = '' !== $resolved_title ? $resolved_title . ' poster' : __( 'Film poster', 'lunara-film' );

            return sprintf(
                '<img class="%1$s" src="%2$s" alt="%3$s" loading="%4$s" decoding="async" data-no-lazy="1" data-skip-lazy="1">',
                esc_attr( $class ),
                esc_url( $url ),
                esc_attr( $alt ),
                esc_attr( $loading )
            );
        };

        if ( class_exists( 'Academy_Awards_Table' ) ) {
            $aat = Academy_Awards_Table::get_instance();

            if ( $aat && method_exists( $aat, 'get_title_visual_package' ) ) {
                $visual = $aat->get_title_visual_package( $tt, $size );

                if ( is_array( $visual ) ) {
                    $resolved_title = '' !== $title && ! preg_match( '/^tt\d{7,8}$/i', $title )
                        ? $title
                        : trim( (string) ( $visual['title'] ?? '' ) );

                    if ( ! empty( $visual['poster_url'] ) ) {
                        return $build_img( $visual['poster_url'], $resolved_title );
                    }

                    if ( ! empty( $visual['poster_html'] ) && is_string( $visual['poster_html'] ) ) {
                        $poster_html = $visual['poster_html'];
                        $poster_html = preg_replace( '/\sclass="[^"]*"/i', ' class="' . esc_attr( $class ) . '"', $poster_html, 1 );
                        $poster_html = preg_replace( '/\sloading="[^"]*"/i', ' loading="' . esc_attr( $loading ) . '"', $poster_html, 1 );
                        if ( false === stripos( $poster_html, ' loading=' ) ) {
                            $poster_html = preg_replace( '/<img\b/i', '<img loading="' . esc_attr( $loading ) . '"', $poster_html, 1 );
                        }
                        if ( false === stripos( $poster_html, ' data-no-lazy=' ) ) {
                            $poster_html = preg_replace( '/<img\b/i', '<img data-no-lazy="1" data-skip-lazy="1"', $poster_html, 1 );
                        }

                        if ( '' !== $resolved_title ) {
                            $poster_html = preg_replace(
                                '/\salt="[^"]*"/i',
                                ' alt="' . esc_attr( $resolved_title . ' poster' ) . '"',
                                $poster_html,
                                1
                            );
                        }

                        return $poster_html;
                    }
                }
            }

            if ( $aat && method_exists( $aat, 'get_poster_img_html_for_title' ) ) {
                $poster_html = (string) $aat->get_poster_img_html_for_title(
                    $tt,
                    $size,
                    array(
                        'class'    => $class,
                        'loading'  => $loading,
                        'decoding' => 'async',
                        'data-no-lazy'   => '1',
                        'data-skip-lazy' => '1',
                    )
                );

                if ( '' !== trim( $poster_html ) ) {
                    return $poster_html;
                }
            }
        }

        if ( defined( 'AAT_TMDB_API_KEY' ) ) {
            $tmdb_cache_key = 'lunara_title_poster_' . md5( $tt . '|' . $size );
            $cached_url     = get_transient( $tmdb_cache_key );

            if ( false === $cached_url ) {
                $cached_url    = '';
                $tmdb_response = wp_remote_get(
                    'https://api.themoviedb.org/3/find/' . rawurlencode( $tt ) . '?api_key=' . rawurlencode( AAT_TMDB_API_KEY ) . '&external_source=imdb_id',
                    array( 'timeout' => 5 )
                );

                if ( ! is_wp_error( $tmdb_response ) ) {
                    $tmdb_body = json_decode( wp_remote_retrieve_body( $tmdb_response ), true );
                    $results   = ! empty( $tmdb_body['movie_results'] ) ? $tmdb_body['movie_results'] : ( ! empty( $tmdb_body['tv_results'] ) ? $tmdb_body['tv_results'] : array() );

                    if ( ! empty( $results[0]['poster_path'] ) ) {
                        $cached_url = 'https://image.tmdb.org/t/p/w500' . $results[0]['poster_path'];

                        if ( '' === $title && ! empty( $results[0]['title'] ) ) {
                            $title = (string) $results[0]['title'];
                        } elseif ( '' === $title && ! empty( $results[0]['name'] ) ) {
                            $title = (string) $results[0]['name'];
                        }
                    }
                }

                set_transient( $tmdb_cache_key, $cached_url, 7 * DAY_IN_SECONDS );
            }

            if ( '' !== $cached_url ) {
                return $build_img( $cached_url, $title );
            }
        }

        return '';
    }
}

if ( ! function_exists( 'lunara_parse_pair_it_with_value' ) ) {
    /**
     * Parse a Pair It With field into the parts needed for preview and QA.
     *
     * @param string $value Raw meta value.
     * @param int    $post_id        Current review post ID.
     * @param bool   $resolve_poster Whether to resolve poster HTML.
     * @return array<string,mixed>
     */
    function lunara_parse_pair_it_with_value( $value, $post_id = 0, $resolve_poster = true ) {
        $raw      = trim( (string) $value );
        $warnings = array();

        $out = array(
            'raw'             => $raw,
            'clean'           => '',
            'tt'              => '',
            'title'           => '',
            'title_base'      => '',
            'year'            => '',
            'note'            => '',
            'internal_href'   => '',
            'imdb_href'       => '',
            'title_href'      => '',
            'title_href_type' => 'none',
            'counts'          => array( 'noms' => 0, 'wins' => 0 ),
            'poster_html'     => '',
            'resolved_title'  => '',
            'warnings'        => array(),
        );

        if ( '' === $raw ) {
            return $out;
        }

        $tt = '';
        if ( preg_match( '/\btt\d{7,8}\b/i', $raw, $m ) ) {
            $tt = strtolower( $m[0] );
        } elseif ( preg_match( '#imdb\.com/title/(tt\d{7,8})#i', $raw, $m ) ) {
            $tt = strtolower( $m[1] );
        }

        $lb = '';
        if ( preg_match( '#letterboxd\.com/film/[^\s\|\)\]]+/?#i', $raw, $m ) ) {
            $lb = $m[0];
            if ( stripos( $lb, 'http' ) !== 0 ) {
                $lb = 'https://' . ltrim( $lb, '/' );
            }
        }

        $clean = $raw;
        if ( '' !== $tt ) {
            $clean = preg_replace( '/\[\s*' . preg_quote( $tt, '/' ) . '\s*\]/i', '', $clean );
            $clean = preg_replace( '/\(\s*' . preg_quote( $tt, '/' ) . '\s*\)/i', '', $clean );
            $clean = preg_replace( '/\s*\|\s*\b' . preg_quote( $tt, '/' ) . '\b\s*$/i', '', $clean );
            $clean = preg_replace( '#\s*\|\s*https?://(www\.)?imdb\.com/title/' . preg_quote( $tt, '#' ) . '/?\s*$#i', '', $clean );
            $clean = preg_replace( '#\s*https?://(www\.)?imdb\.com/title/' . preg_quote( $tt, '#' ) . '/?\s*#i', ' ', $clean );
            $clean = preg_replace( '/\s*\b' . preg_quote( $tt, '/' ) . '\b\s*/i', ' ', $clean );
            $clean = preg_replace( '/\s*\|\s*(?:imdb|imdb id|imdb title id)\s*:?\s*$/i', '', $clean );
            $clean = preg_replace( '/\s*(?:imdb|imdb id|imdb title id)\s*:?\s*$/i', '', $clean );
            $clean = preg_replace( '/\s*\|\s*$/', '', $clean );
        }

        if ( '' !== $lb ) {
            $clean = preg_replace( '#\s*\|\s*lb:\s*' . preg_quote( $lb, '#' ) . '\s*$#i', '', $clean );
            $clean = preg_replace( '#\s*\|\s*' . preg_quote( $lb, '#' ) . '\s*$#i', '', $clean );
            $clean = preg_replace( '#\s*' . preg_quote( $lb, '#' ) . '\s*#i', ' ', $clean );
        }

        $clean = preg_replace( '/\s+([,.;:!?])/', '$1', (string) $clean );
        $clean = trim( preg_replace( '/\s{2,}/', ' ', (string) $clean ) );

        $parts = preg_split( '/\s+(?:-|\x{2013}|\x{2014}|â€”)\s+/u', $clean, 2 );
        $title = trim( $parts[0] ?? '' );
        $note  = trim( $parts[1] ?? '' );

        if ( '' === $note && '' !== $tt ) {
            $tail_pattern = '/^(.*?)\b' . preg_quote( $tt, '/' ) . '\b(?:\s*[.:;\-\x{2013}\x{2014}]\s*|\s+)(.+)$/iu';
            if ( preg_match( $tail_pattern, $raw, $m3 ) ) {
                $raw_title = trim( wp_strip_all_tags( $m3[1] ) );
                $raw_title = preg_replace( '/\s*(?:\||:|-|\x{2013}|\x{2014})\s*$/u', '', $raw_title );
                $raw_title = preg_replace( '/\s{2,}/', ' ', (string) $raw_title );
                $title     = trim( (string) $raw_title );
                $note      = trim( wp_strip_all_tags( $m3[2] ) );
            }
        }

        if ( '' === $note && preg_match( '/^(.*?\(\d{4}\))\s*[.:;\-\x{2013}\x{2014}]+\s*(.+)$/u', $title, $m4 ) ) {
            $title = trim( $m4[1] );
            $note  = trim( $m4[2] );
        }

        $title_base = $title;
        $year       = '';
        if ( preg_match( '/^(.*?)(?:\s*\((\d{4})\))\s*$/', $title, $m2 ) ) {
            $title_base = trim( $m2[1] );
            $year       = trim( $m2[2] );
        }

        if ( '' === $tt && '' !== $title_base ) {
            $map = lunara_imdb_title_map();
            if ( '' !== $year ) {
                $key = lunara_normalize_title_key( $title_base ) . '|' . $year;
                if ( isset( $map[ $key ] ) ) {
                    $tt = strtolower( $map[ $key ] );
                }
            } else {
                $prefix  = lunara_normalize_title_key( $title_base ) . '|';
                $matches = array();
                foreach ( $map as $k => $val ) {
                    if ( strpos( $k, $prefix ) === 0 ) {
                        $matches[] = $val;
                    }
                }
                $matches = array_values( array_unique( $matches ) );
                if ( count( $matches ) === 1 ) {
                    $tt = strtolower( $matches[0] );
                }
            }
        }

        $internal_href = '';
        $imdb_href     = '';
        if ( '' !== $tt ) {
            $imdb_href = 'https://www.imdb.com/title/' . $tt . '/';
            if ( function_exists( 'lunara_get_internal_title_reference_url' ) ) {
                $internal_href = lunara_get_internal_title_reference_url( $tt, $post_id );
            }
        } elseif ( '' !== $title_base ) {
            $q = $title_base;
            if ( '' !== $year ) {
                $q .= ' ' . $year;
            }
            $imdb_href = 'https://www.imdb.com/find/?q=' . rawurlencode( $q ) . '&s=tt';
            $warnings[] = __( 'Missing IMDb title ID. Append | tt1234567 to lock poster and link accuracy.', 'lunara-film' );
        }

        $title_href      = '' !== $internal_href ? $internal_href : $imdb_href;
        $title_href_type = 'none';
        if ( '' !== $internal_href ) {
            $title_href_type = false !== strpos( $internal_href, '/reviews/' ) ? 'review' : 'oscar';
        } elseif ( '' !== $imdb_href ) {
            $title_href_type = 'imdb';
        }

        $counts = array( 'noms' => 0, 'wins' => 0 );
        if ( '' !== $tt && function_exists( 'lunara_get_oscar_ledger_counts' ) ) {
            $counts = lunara_get_oscar_ledger_counts( $tt );
        }

        $poster_html = '';
        if ( $resolve_poster && '' !== $tt && function_exists( 'lunara_get_title_poster_html' ) ) {
            $poster_html = lunara_get_title_poster_html( $tt, 'medium', 'lunara-pair-preview-thumb', $title );
            if ( '' === trim( $poster_html ) ) {
                $warnings[] = __( 'No poster resolved for this IMDb ID yet.', 'lunara-film' );
            }
        }

        $resolved_title = '';
        if ( '' !== $tt && class_exists( 'Academy_Awards_Table' ) ) {
            $aat = Academy_Awards_Table::get_instance();
            if ( $aat && method_exists( $aat, 'get_title_visual_package' ) ) {
                $visual = $aat->get_title_visual_package( $tt, 'thumbnail' );
                if ( is_array( $visual ) && ! empty( $visual['title'] ) ) {
                    $resolved_title = trim( (string) $visual['title'] );
                    if ( '' !== $title_base && lunara_normalize_title_key( $resolved_title ) !== lunara_normalize_title_key( $title_base ) ) {
                        $warnings[] = sprintf(
                            /* translators: %s: resolved film title */
                            __( 'IMDb ID resolves as "%s"; check this if the poster looks wrong.', 'lunara-film' ),
                            $resolved_title
                        );
                    }
                }
            }
        }

        $out['clean']           = $clean;
        $out['tt']              = $tt;
        $out['title']           = $title;
        $out['title_base']      = $title_base;
        $out['year']            = $year;
        $out['note']            = $note;
        $out['internal_href']   = $internal_href;
        $out['imdb_href']       = $imdb_href;
        $out['title_href']      = $title_href;
        $out['title_href_type'] = $title_href_type;
        $out['counts']          = $counts;
        $out['poster_html']     = $poster_html;
        $out['resolved_title']  = $resolved_title;
        $out['warnings']        = array_values( array_unique( array_filter( $warnings ) ) );

        return $out;
    }
}

if ( ! function_exists( 'lunara_render_pair_it_with_admin_preview' ) ) {
    /**
     * Render a read-only Pair It With QA preview in the Review editor.
     *
     * @param int                  $post_id Review post ID.
     * @param array<string,string> $pairings Label => raw value.
     * @return string
     */
    function lunara_render_pair_it_with_admin_preview( $post_id, $pairings ) {
        if ( ! is_array( $pairings ) ) {
            return '';
        }

        ob_start();
        ?>
        <div class="lunara-pair-preview">
            <div class="lunara-pair-preview-head">
                <strong><?php esc_html_e( 'Pair It With Preview', 'lunara-film' ); ?></strong>
                <span><?php esc_html_e( 'Read-only check for title, poster, link, and Oscar Ledger status.', 'lunara-film' ); ?></span>
            </div>
            <div class="lunara-pair-preview-grid">
                <?php foreach ( $pairings as $label => $value ) : ?>
                    <?php
                    $preview  = lunara_parse_pair_it_with_value( $value, $post_id );
                    $is_empty = '' === $preview['raw'];
                    $warnings = is_array( $preview['warnings'] ) ? $preview['warnings'] : array();
                    $classes  = array( 'lunara-pair-preview-card' );
                    if ( $is_empty ) {
                        $classes[] = 'is-empty';
                    } elseif ( ! empty( $warnings ) ) {
                        $classes[] = 'is-warning';
                    } else {
                        $classes[] = 'is-ready';
                    }

                    $title = '' !== $preview['title'] ? $preview['title'] : __( 'Not filled yet', 'lunara-film' );
                    $noms  = (int) ( $preview['counts']['noms'] ?? 0 );
                    $wins  = (int) ( $preview['counts']['wins'] ?? 0 );
                    ?>
                    <article class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
                        <div class="lunara-pair-preview-media">
                            <?php
                            if ( ! empty( $preview['poster_html'] ) ) {
                                echo wp_kses_post( $preview['poster_html'] );
                            } else {
                                esc_html_e( 'No poster', 'lunara-film' );
                            }
                            ?>
                        </div>
                        <div>
                            <p class="lunara-pair-preview-role"><?php echo esc_html( (string) $label ); ?></p>
                            <p class="lunara-pair-preview-title">
                                <?php if ( ! $is_empty && '' !== $preview['title_href'] ) : ?>
                                    <a href="<?php echo esc_url( $preview['title_href'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $title ); ?></a>
                                <?php else : ?>
                                    <?php echo esc_html( $title ); ?>
                                <?php endif; ?>
                            </p>
                            <?php if ( ! empty( $preview['note'] ) ) : ?>
                                <p class="lunara-pair-preview-note"><?php echo esc_html( $preview['note'] ); ?></p>
                            <?php endif; ?>
                            <div class="lunara-pair-preview-chips">
                                <?php if ( ! empty( $preview['tt'] ) ) : ?>
                                    <span class="lunara-pair-preview-chip"><?php echo esc_html( strtoupper( $preview['tt'] ) ); ?></span>
                                <?php endif; ?>
                                <?php if ( ! empty( $preview['poster_html'] ) ) : ?>
                                    <span class="lunara-pair-preview-chip"><?php esc_html_e( 'Poster ready', 'lunara-film' ); ?></span>
                                <?php endif; ?>
                                <?php if ( $noms > 0 ) : ?>
                                    <span class="lunara-pair-preview-chip"><?php echo esc_html( sprintf( __( 'Oscar Ledger: %1$d noms / %2$d wins', 'lunara-film' ), $noms, $wins ) ); ?></span>
                                <?php elseif ( ! empty( $preview['tt'] ) ) : ?>
                                    <span class="lunara-pair-preview-chip is-muted"><?php esc_html_e( 'No Oscar Ledger data', 'lunara-film' ); ?></span>
                                <?php endif; ?>
                                <?php if ( 'review' === $preview['title_href_type'] ) : ?>
                                    <span class="lunara-pair-preview-chip"><?php esc_html_e( 'Links to Lunara review', 'lunara-film' ); ?></span>
                                <?php elseif ( 'oscar' === $preview['title_href_type'] ) : ?>
                                    <span class="lunara-pair-preview-chip"><?php esc_html_e( 'Links to Oscar page', 'lunara-film' ); ?></span>
                                <?php elseif ( 'imdb' === $preview['title_href_type'] ) : ?>
                                    <span class="lunara-pair-preview-chip is-muted"><?php esc_html_e( 'IMDb fallback', 'lunara-film' ); ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if ( ! empty( $warnings ) ) : ?>
                                <ul class="lunara-pair-preview-warnings">
                                    <?php foreach ( $warnings as $warning ) : ?>
                                        <li><?php echo esc_html( $warning ); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

if ( ! function_exists( 'lunara_pair_aperture_mark_symbol' ) ) {
    /**
     * Inline SVG <symbol> for the Lunara crescent-moon aperture mark.
     *
     * Referenced by the no-poster "title plate" via <use href="#lunara-aperture-mark">.
     * This is a placeholder interpretation of Dalton's mark; the entire symbol is
     * filterable so the refined production SVG can be dropped in without touching
     * the renderer or its CSS.
     *
     * @return string
     */
    function lunara_pair_aperture_mark_symbol() {
        $svg = '<svg class="lunara-pair-mark-defs" width="0" height="0" aria-hidden="true" focusable="false">'
            . '<symbol id="lunara-aperture-mark" viewBox="0 0 120 120">'
            . '<defs>'
            . '<linearGradient id="lunara-aperture-grad" x1="0" y1="0" x2="1" y2="1">'
            . '<stop offset="0" stop-color="#f0dca6"></stop>'
            . '<stop offset="0.45" stop-color="#cdac68"></stop>'
            . '<stop offset="1" stop-color="#997a43"></stop>'
            . '</linearGradient>'
            . '<mask id="lunara-aperture-mask">'
            . '<rect width="120" height="120" fill="#000"></rect>'
            . '<circle cx="55" cy="63" r="45" fill="#fff"></circle>'
            . '<circle cx="78" cy="47" r="39" fill="#000"></circle>'
            . '</mask>'
            . '</defs>'
            . '<circle cx="55" cy="63" r="45" fill="url(#lunara-aperture-grad)" mask="url(#lunara-aperture-mask)"></circle>'
            . '<g fill="url(#lunara-aperture-grad)">'
            . '<path id="lunara-aperture-blade" d="M66 56 L98 47 L91 67 Z"></path>'
            . '<use href="#lunara-aperture-blade" transform="rotate(126 66 56)"></use>'
            . '<use href="#lunara-aperture-blade" transform="rotate(252 66 56)"></use>'
            . '</g>'
            . '</symbol></svg>';

        /**
         * Filter the inline crescent-moon aperture mark.
         *
         * Return a complete <svg>…<symbol id="lunara-aperture-mark">…</symbol></svg>
         * string to replace the placeholder with the finished production mark.
         *
         * @param string $svg Default placeholder symbol markup.
         */
        return (string) apply_filters( 'lunara_pair_aperture_mark_svg', $svg );
    }
}

if ( ! function_exists( 'lunara_pair_render_where_to_watch' ) ) {
    /**
     * "Where to watch" slot for a paired film — JustWatch integration point.
     *
     * Returns an empty string by default so the card never shows a half-built
     * streaming row. When JustWatch's API surfaces land, hook this filter to
     * return a row of provider chips for the given IMDb title ID. Each card also
     * carries a data-pair-tt attribute for optional client-side hydration.
     *
     * @param string $tt      IMDb title ID (lowercased) or ''.
     * @param int    $post_id Current review post ID.
     * @return string
     */
    function lunara_pair_render_where_to_watch( $tt, $post_id = 0 ) {
        $tt = strtolower( trim( (string) $tt ) );

        /**
         * Filter the paired-film "Where to watch" markup (JustWatch stub).
         *
         * @param string $html    Default markup (empty).
         * @param string $tt      IMDb title ID (lowercased) or ''.
         * @param int    $post_id Current review post ID.
         */
        return (string) apply_filters( 'lunara_pair_where_to_watch_html', '', $tt, (int) $post_id );
    }
}

if ( ! function_exists( 'lunara_render_pair_it_with_cards' ) ) {
    /**
     * Render the "Pair It With" trio as uniform, self-contained cinematic cards.
     *
     * Reuses lunara_parse_pair_it_with_value() for data resolution, then renders
     * each pairing into an identical card shape: poster (or Fallback B title
     * plate with the crescent-moon colophon when no poster resolves), role label,
     * linked title + year, note, an always-on IMDb chip, an Oscar Ledger pill
     * when the film has Oscar history, and a reserved JustWatch "Where to watch"
     * slot. Uniformity is enforced structurally — every card carries every slot.
     *
     * @param int $post_id Review post ID (defaults to the current post).
     * @return string Card markup, or '' when no pairings are filled.
     */
    function lunara_render_pair_it_with_cards( $post_id = 0 ) {
        $post_id = $post_id ? (int) $post_id : (int) get_the_ID();
        if ( $post_id <= 0 ) {
            return '';
        }

        $rows = array(
            array(
                'slug'  => 'theme',
                'label' => __( 'Theme Echo', 'lunara-film' ),
                'value' => get_post_meta( $post_id, '_lunara_theme_echo', true ),
            ),
            array(
                'slug'  => 'counter',
                'label' => __( 'Counter-Program', 'lunara-film' ),
                'value' => get_post_meta( $post_id, '_lunara_counter_program', true ),
            ),
            array(
                'slug'  => 'career',
                'label' => __( 'Career Context', 'lunara-film' ),
                'value' => lunara_get_career_context_meta( $post_id ),
            ),
        );

        $cards     = array();
        $need_mark = false;

        foreach ( $rows as $row ) {
            $value = trim( (string) $row['value'] );
            if ( '' === $value ) {
                continue;
            }

            $data       = lunara_parse_pair_it_with_value( $value, $post_id );
            $title_base = '' !== $data['title_base'] ? $data['title_base'] : $data['title'];
            if ( '' === trim( (string) $title_base ) ) {
                continue;
            }

            $tt     = (string) $data['tt'];
            $year   = (string) $data['year'];
            $note   = (string) $data['note'];
            $counts = is_array( $data['counts'] ) ? $data['counts'] : array( 'noms' => 0, 'wins' => 0 );

            // -- Poster, or Fallback B title plate with the aperture colophon. --
            $poster_html = trim( (string) $data['poster_html'] );
            if ( '' !== $poster_html ) {
                $media = '<div class="lunara-pair-card-poster">' . $poster_html . '</div>';
            } else {
                $need_mark   = true;
                $plate_title = '' !== trim( (string) $title_base ) ? $title_base : (string) $data['title'];
                $media       = '<div class="lunara-pair-card-poster lunara-pair-card-poster--plate">'
                    . '<div class="lunara-pair-card-plate">'
                    . '<svg class="lunara-pair-card-mark" viewBox="0 0 120 120" aria-hidden="true" focusable="false"><use href="#lunara-aperture-mark"></use></svg>'
                    . '<span class="lunara-pair-card-plate-title">' . esc_html( $plate_title ) . '</span>'
                    . '<span class="lunara-pair-card-plate-rule"></span>'
                    . '</div></div>';
            }

            // -- Title (+ year). Internal Lunara destination preferred; IMDb is the chip. --
            $title_inner = '<span class="lunara-pair-card-title-text">' . esc_html( $title_base ) . '</span>';
            if ( '' !== $year ) {
                $title_inner .= ' <span class="lunara-pair-card-year">(' . esc_html( $year ) . ')</span>';
            }

            $title_href = (string) $data['title_href'];
            $href_type  = (string) $data['title_href_type'];
            if ( '' !== $title_href && in_array( $href_type, array( 'review', 'oscar' ), true ) ) {
                $title_html = '<a class="lunara-pair-card-title-link" href="' . esc_url( $title_href ) . '">' . $title_inner . '</a>';
            } elseif ( '' !== $title_href ) {
                $title_html = '<a class="lunara-pair-card-title-link" href="' . esc_url( $title_href ) . '" target="_blank" rel="noopener noreferrer nofollow">' . $title_inner . '</a>';
            } else {
                $title_html = '<span class="lunara-pair-card-title-link">' . $title_inner . '</span>';
            }

            // -- Chips: IMDb (always) + Oscar Ledger (only when Oscar history exists). --
            $chips     = '';
            $imdb_href = (string) $data['imdb_href'];
            if ( '' !== $imdb_href ) {
                $chips .= '<a class="lunara-pair-card-chip lunara-pair-card-chip--imdb" href="' . esc_url( $imdb_href ) . '" target="_blank" rel="noopener noreferrer nofollow">IMDb</a>';
            }
            $chips .= lunara_render_oscar_ledger_pill( $tt, $counts );

            // -- JustWatch "Where to watch" slot (stubbed until the API lands). --
            $watch = lunara_pair_render_where_to_watch( $tt, $post_id );

            // -- Assemble the uniform card. --
            $card  = '<article class="lunara-pair-card lunara-pair-card--' . esc_attr( $row['slug'] ) . '"';
            $card .= '' !== $tt ? ' data-pair-tt="' . esc_attr( $tt ) . '"' : '';
            $card .= '>';
            $card .= $media;
            $card .= '<div class="lunara-pair-card-body">';
            $card .= '<p class="lunara-pair-card-role">' . esc_html( $row['label'] ) . '</p>';
            $card .= '<h4 class="lunara-pair-card-title">' . $title_html . '</h4>';
            if ( '' !== trim( $note ) ) {
                $card .= '<p class="lunara-pair-card-note">' . esc_html( $note ) . '</p>';
            }
            if ( '' !== trim( $chips ) ) {
                $card .= '<div class="lunara-pair-card-chips">' . $chips . '</div>';
            }
            if ( '' !== trim( (string) $watch ) ) {
                $card .= '<div class="lunara-pair-card-watch">' . $watch . '</div>';
            }
            $card .= '</div></article>';

            $cards[] = $card;
        }

        if ( empty( $cards ) ) {
            return '';
        }

        // Emit the aperture mark symbol once per request, only if a plate needs it.
        static $mark_emitted = false;
        $symbol = '';
        if ( $need_mark && ! $mark_emitted ) {
            $symbol       = lunara_pair_aperture_mark_symbol();
            $mark_emitted = true;
        }

        $subtitle = (string) apply_filters(
            'lunara_pair_cards_subtitle',
            __( 'Three films in conversation with this one.', 'lunara-film' ),
            $post_id
        );

        $html  = $symbol;
        $html .= '<section class="lunara-pair-cards" aria-label="' . esc_attr__( 'Pair It With', 'lunara-film' ) . '">';
        $html .= '<div class="lunara-pair-cards-head">';
        $html .= '<h3 class="lunara-pair-cards-title">' . esc_html__( 'Pair It With', 'lunara-film' ) . '</h3>';
        if ( '' !== trim( $subtitle ) ) {
            $html .= '<p class="lunara-pair-cards-sub">' . esc_html( $subtitle ) . '</p>';
        }
        $html .= '</div>';
        $html .= '<div class="lunara-pair-cards-grid" data-count="' . count( $cards ) . '">' . implode( '', $cards ) . '</div>';
        $html .= '</section>';

        return $html;
    }
}



/**
 * Shortcode: The Lunara Debrief Block
 */
function lunara_debrief_shortcode( $atts ) {
    $post_id = get_the_ID();

    $score = get_post_meta( $post_id, '_lunara_score', true );
    $year = get_post_meta( $post_id, '_lunara_year', true );
    $where = get_post_meta( $post_id, '_lunara_where', true );
    $theme_echo = get_post_meta( $post_id, '_lunara_theme_echo', true );
    $counter = get_post_meta( $post_id, '_lunara_counter_program', true );
    $craft = lunara_get_career_context_meta( $post_id );
    $review_tt = get_post_meta( $post_id, '_lunara_imdb_title_id', true );
    if ( is_string( $review_tt ) && preg_match( '#imdb\.com/title/(tt\d{7,8})#i', $review_tt, $mtt ) ) { $review_tt = $mtt[1]; }
    $review_tt = strtolower( trim( (string) $review_tt ) );

    if ( empty( $score ) && empty( $where ) && empty( $theme_echo ) && empty( $year ) ) {
        return '';
    }

    // Local helper: render a "Pair It With" line.
    // Supports optional IMDb title ID / URL embedded anywhere in the field.
    // Examples you can paste into the meta field:
    //   "There Will Be Blood (2007) — ... | tt0469494"
    //   "There Will Be Blood (2007) — ... https://www.imdb.com/title/tt0469494/"
    // If a tt-id is present, the title links to the internal Oscars film page (/oscars/title/tt.../)
    // and an "IMDb" reference chip is shown.
    $format_pairing = function( $value ) use ( $post_id ) {
    $raw = trim( (string) $value );
    if ( $raw === '' ) {
        return '';
    }

    // 1) Extract a tt-id if present anywhere (either bare tt123... or full IMDb URL).
    //    Older entries may also contain a Letterboxd URL; strip it from display without using it.
    $tt = '';
    $lb = '';
    if ( preg_match( '/\btt\d{7,8}\b/i', $raw, $m ) ) {
        $tt = strtolower( $m[0] );
    } elseif ( preg_match( '#imdb\.com/title/(tt\d{7,8})#i', $raw, $m ) ) {
        $tt = strtolower( $m[1] );
    }

    // Letterboxd film URL (optional). Supports:
    //   - https://letterboxd.com/film/<slug>/
    //   - | lb:https://letterboxd.com/film/<slug>/
    //   - | https://letterboxd.com/film/<slug>/
    if ( preg_match( '#letterboxd\.com/film/[^\s\|\)\]]+/?#i', $raw, $m ) ) {
        $lb = $m[0];
        // Ensure scheme.
        if ( stripos( $lb, 'http' ) !== 0 ) {
            $lb = 'https://' . ltrim( $lb, '/' );
        }
    }

    // 2) Remove the tt-id / IMDb URL / Letterboxd URL from the display string so the line stays clean.
    $clean = $raw;
    if ( $tt !== '' ) {
        $clean = preg_replace( '/\[\s*' . preg_quote( $tt, '/' ) . '\s*\]/i', '', $clean );
        $clean = preg_replace( '/\(\s*' . preg_quote( $tt, '/' ) . '\s*\)/i', '', $clean );
        $clean = preg_replace( '/\s*\|\s*\b' . preg_quote( $tt, '/' ) . '\b\s*$/i', '', $clean );
        $clean = preg_replace( '#\s*\|\s*https?://(www\.)?imdb\.com/title/' . preg_quote( $tt, '#' ) . '/?\s*$#i', '', $clean );
        $clean = preg_replace( '#\s*https?://(www\.)?imdb\.com/title/' . preg_quote( $tt, '#' ) . '/?\s*#i', ' ', $clean );
        $clean = preg_replace( '/\s*\b' . preg_quote( $tt, '/' ) . '\b\s*/i', ' ', $clean );
        $clean = preg_replace( '/\s*\|\s*(?:imdb|imdb id|imdb title id)\s*:?\s*$/i', '', $clean );
        $clean = preg_replace( '/\s*(?:imdb|imdb id|imdb title id)\s*:?\s*$/i', '', $clean );
        $clean = preg_replace( '/\s*\|\s*$/', '', $clean );
        $clean = trim( preg_replace( '/\s{2,}/', ' ', $clean ) );
    }
    if ( $lb !== '' ) {
        $clean = preg_replace( '#\s*\|\s*lb:\s*' . preg_quote( $lb, '#' ) . '\s*$#i', '', $clean );
        $clean = preg_replace( '#\s*\|\s*' . preg_quote( $lb, '#' ) . '\s*$#i', '', $clean );
        $clean = preg_replace( '#\s*' . preg_quote( $lb, '#' ) . '\s*#i', ' ', $clean );
        $clean = trim( preg_replace( '/\s{2,}/', ' ', $clean ) );
    }

    // Removing embedded IDs can leave awkward spaces before punctuation.
    $clean = preg_replace( '/\s+([,.;:!?])/', '$1', $clean );
    $clean = trim( preg_replace( '/\s{2,}/', ' ', $clean ) );

    // 3) Split into title + note (prefer em dash).
    $parts = preg_split( '/\s+—\s+/u', $clean, 2 );
    if ( count( $parts ) < 2 ) {
        $parts = preg_split( '/\s+-\s+/', $clean, 2 );
    }

    $title = trim( $parts[0] ?? '' );
    $note  = trim( $parts[1] ?? '' );

    // If the note starts immediately after the IMDb ID, don't require punctuation.
    if ( '' === $note && $tt !== '' ) {
        $tail_pattern = '/^(.*?)\b' . preg_quote( $tt, '/' ) . '\b(?:\s*[.:;\-\x{2013}\x{2014}]\s*|\s+)(.+)$/iu';
        if ( preg_match( $tail_pattern, $raw, $m3 ) ) {
            $raw_title = trim( wp_strip_all_tags( $m3[1] ) );
            $raw_title = preg_replace( '/\s*(?:\||:|-|\x{2013}|\x{2014})\s*$/u', '', $raw_title );
            $raw_title = preg_replace( '/\s{2,}/', ' ', (string) $raw_title );
            $title     = trim( (string) $raw_title );
            $note      = trim( wp_strip_all_tags( $m3[2] ) );
        }
    }

    // Some entries are stored as "Title (Year) tt1234567. Note..." and lose their
    // separator after the tt-id is stripped. Fall back to splitting after the year.
    if ( '' === $note && preg_match( '/^(.*?\(\d{4}\))\s*[.:;\-\x{2013}\x{2014}]+\s*(.+)$/u', $title, $m4 ) ) {
        $title = trim( $m4[1] );
        $note  = trim( $m4[2] );
    }

    // 4) Pull year out of "Title (YYYY)" for smarter lookups & cleaner IMDb search queries.
    $title_base = $title;
    $year = '';
    if ( preg_match( '/^(.*?)(?:\s*\((\d{4})\))\s*$/', $title, $m2 ) ) {
        $title_base = trim( $m2[1] );
        $year = trim( $m2[2] );
    }

    // 5) If no explicit tt-id was provided, try to resolve via the bundled IMDb title map.
    if ( $tt === '' && $title_base !== '' ) {
        $map = lunara_imdb_title_map();
        if ( $year !== '' ) {
            $key = lunara_normalize_title_key( $title_base ) . '|' . $year;
            if ( isset( $map[ $key ] ) ) {
                $tt = strtolower( $map[ $key ] );
            }
        } else {
            // Only use a title-only lookup if it's unambiguous.
            $prefix = lunara_normalize_title_key( $title_base ) . '|';
            $matches = array();
            foreach ( $map as $k => $val ) {
                if ( strpos( $k, $prefix ) === 0 ) {
                    $matches[] = $val;
                }
            }
            $matches = array_values( array_unique( $matches ) );
            if ( count( $matches ) === 1 ) {
                $tt = strtolower( $matches[0] );
            }
        }
    }

    // 6) Build title + links.
    //    Internal Lunara destinations take the title click; IMDb remains the external reference chip.
    $internal_href = '';
    $fallback_href = '';
    if ( $tt !== '' && function_exists( 'lunara_get_internal_title_reference_url' ) ) {
        $internal_href = lunara_get_internal_title_reference_url( $tt, $post_id );
    }

    if ( $tt !== '' ) {
        $fallback_href = 'https://www.imdb.com/title/' . $tt . '/';
    }

    if ( '' !== $internal_href ) {
        $title_html = '<a class="lunara-pair-title" href="' . esc_url( $internal_href ) . '"><em>' . esc_html( $title ) . '</em></a>';
    } elseif ( '' !== $fallback_href ) {
        $title_html = '<a class="lunara-pair-title" href="' . esc_url( $fallback_href ) . '" target="_blank" rel="noopener noreferrer nofollow"><em>' . esc_html( $title ) . '</em></a>';
    } else {
        $title_html = '<span class="lunara-pair-title"><em>' . esc_html( $title ) . '</em></span>';
    }
    $chips_html = '';

    if ( $tt !== '' ) {
            $imdb = $fallback_href;

            // IMDb is the external reference. Oscar Ledger pill drives visitors into Lunara's database.
            $chips_html = ' <a class="lunara-debrief-chip lunara-debrief-chip-imdb" href="' . esc_url( $imdb ) . '" target="_blank" rel="noopener noreferrer nofollow">IMDb</a>';
            $chips_html .= ' ' . lunara_render_oscar_ledger_pill( $tt );
        } else {
        // Fallback: IMDb search. Use "Title YYYY" (no parentheses) for better results.
        $q = $title_base !== '' ? $title_base : $title;
        if ( $year !== '' ) {
            $q .= ' ' . $year;
        }
        $imdb_search = 'https://www.imdb.com/find/?q=' . rawurlencode( $q ) . '&s=tt';
        $chips_html  = ' <a class="lunara-debrief-chip lunara-debrief-chip-imdb" href="' . esc_url( $imdb_search ) . '" target="_blank" rel="noopener noreferrer nofollow">IMDb</a>';
    }

    // Optional poster thumbnail.
    // Priority: 1) Oscar database poster library, 2) TMDB API lookup via IMDb ID.
    // Every paired film should show a poster regardless of Oscar history.
    $poster_html = '';
    if ( $tt !== '' && function_exists( 'lunara_get_title_poster_html' ) ) {
        $poster_html = lunara_get_title_poster_html( $tt, 'medium', 'lunara-debrief-thumb', $title, 'eager' );
    }

    // IMPORTANT UX FIX:
    // The descriptive sentence (note) must not trail AFTER the IMDb chip.
    // Render a clean first line (title + chips), then the note on its own line below.
    $line1 = '<span class="lunara-debrief-line1">' . $title_html . $chips_html . '</span>';
    $line2 = '';
    if ( $note !== '' ) {
        $line2 = '<span class="lunara-debrief-note">' . esc_html( $note ) . '</span>';
    }

    $text_html = '<span class="lunara-debrief-pairing-text">' . $line1 . $line2 . '</span>';

    if ( $poster_html === '' ) {
        return $text_html;
    }

    return '<span class="lunara-debrief-pairing">'
        . '<span class="lunara-debrief-thumb-wrap">' . $poster_html . '</span>'
        . $text_html
        . '</span>';
};

    ob_start();
    ?>
    <section class="lunara-debrief-block">
        <h3 class="lunara-debrief-heading">LUNARA DEBRIEF</h3>
        <?php $kicker = trim( (string) get_theme_mod( 'lunara_debrief_kicker_text', 'A LUNARA FILM SIGNATURE' ) ); ?>
        <?php if ( $kicker !== '' ) : ?>
            <div class="lunara-debrief-kicker"><?php echo esc_html( $kicker ); ?></div>
        <?php endif; ?>
<ul class="lunara-debrief-list">
            <?php if ( $score ) : ?>
                <li><strong>Score:</strong><span class="lunara-debrief-value"><?php echo lunara_render_stars( $score ); ?></span></li>
            <?php endif; ?>

            <?php if ( $review_tt ) : ?>
                <?php $ledger = lunara_render_oscar_ledger_pill( $review_tt ); ?>
                <?php if ( $ledger !== '' ) : ?>
                    <li class="lunara-debrief-ledger-row"><strong>&nbsp;</strong><span class="lunara-debrief-value"><?php echo $ledger; ?></span></li>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ( $year ) : ?>
                <li><strong>Year:</strong><span class="lunara-debrief-value"><?php echo esc_html( $year ); ?></span></li>
            <?php endif; ?>

            <?php if ( $where ) : ?>
                <?php $where_markup = function_exists( 'lunara_render_review_where_links' ) ? lunara_render_review_where_links( $where, get_the_title( $post_id ) ) : ''; ?>
                <li class="lunara-debrief-where-row"><strong>Where to Watch:</strong><span class="lunara-debrief-value"><?php echo '' !== $where_markup ? wp_kses_post( $where_markup ) : esc_html( $where ); ?></span></li>
            <?php endif; ?>

            <?php if ( $theme_echo || $counter || $craft ) : ?>
                <li class="lunara-debrief-pair-header">Pair It With</li>

                <?php if ( $theme_echo ) : ?>
                    <li class="lunara-debrief-pair-row lunara-debrief-pair-row--theme"><strong class="lunara-debrief-pair-type">Theme Echo</strong><span class="lunara-debrief-value"><?php echo $format_pairing( $theme_echo ); ?></span></li>
                <?php endif; ?>

                <?php if ( $counter ) : ?>
                    <li class="lunara-debrief-pair-row lunara-debrief-pair-row--counter"><strong class="lunara-debrief-pair-type">Counter-Program</strong><span class="lunara-debrief-value"><?php echo $format_pairing( $counter ); ?></span></li>
                <?php endif; ?>

                <?php if ( $craft ) : ?>
                    <li class="lunara-debrief-pair-row lunara-debrief-pair-row--career"><strong class="lunara-debrief-pair-type">Career Context</strong><span class="lunara-debrief-value"><?php echo $format_pairing( $craft ); ?></span></li>
                <?php endif; ?>
            <?php endif; ?>
        </ul>
    </section>
    <?php
    return ob_get_clean();
}
add_shortcode( 'lunara_debrief', 'lunara_debrief_shortcode' );

/* ========================================
   DEBRIEF BLOCK SPLITTER — separates signature from pairings for review singles
   ======================================== */

if ( ! function_exists( 'lunara_get_dom_node_outer_html' ) ) {
    function lunara_get_dom_node_outer_html( DOMNode $node ) {
        $document = $node->ownerDocument;
        return $document instanceof DOMDocument ? (string) $document->saveHTML( $node ) : '';
    }
}

if ( ! function_exists( 'lunara_split_review_debrief_block' ) ) {
    function lunara_split_review_debrief_block( $html ) {
        $html = trim( (string) $html );

        $fallback = array(
            'signature' => $html,
            'pairings'  => '',
        );

        if ( '' === $html || ! class_exists( 'DOMDocument' ) ) {
            return $fallback;
        }

        $previous = libxml_use_internal_errors( true );
        $document = new DOMDocument( '1.0', 'UTF-8' );
        $wrapper  = '<!DOCTYPE html><html><body><div id="lunara-debrief-root">' . $html . '</div></body></html>';

        if ( ! $document->loadHTML( mb_convert_encoding( $wrapper, 'HTML-ENTITIES', 'UTF-8' ) ) ) {
            libxml_clear_errors();
            libxml_use_internal_errors( $previous );
            return $fallback;
        }

        $root  = $document->getElementById( 'lunara-debrief-root' );
        $xpath = new DOMXPath( $document );

        if ( ! ( $root instanceof DOMElement ) ) {
            libxml_clear_errors();
            libxml_use_internal_errors( $previous );
            return $fallback;
        }

        $block = $xpath->query( './/*[contains(concat(" ", normalize-space(@class), " "), " lunara-debrief-block ")]', $root )->item( 0 );
        $list  = $xpath->query( './/*[contains(concat(" ", normalize-space(@class), " "), " lunara-debrief-list ")]', $root )->item( 0 );

        if ( ! ( $block instanceof DOMElement ) || ! ( $list instanceof DOMElement ) ) {
            libxml_clear_errors();
            libxml_use_internal_errors( $previous );
            return $fallback;
        }

        $heading_html = '';
        $kicker_html  = '';
        foreach ( $block->childNodes as $child ) {
            if ( ! ( $child instanceof DOMElement ) ) {
                continue;
            }

            $class_name = ' ' . trim( (string) $child->getAttribute( 'class' ) ) . ' ';
            if ( false !== strpos( $class_name, ' lunara-debrief-heading ' ) ) {
                $heading_html = lunara_get_dom_node_outer_html( $child );
            } elseif ( false !== strpos( $class_name, ' lunara-debrief-kicker ' ) ) {
                $kicker_html = lunara_get_dom_node_outer_html( $child );
            }
        }

        $signature_items = array();
        $pairing_items   = array();
        $pairing_title   = __( 'Pair It With', 'lunara-film' );
        $in_pairings     = false;

        foreach ( $list->childNodes as $child ) {
            if ( ! ( $child instanceof DOMElement ) || 'li' !== strtolower( $child->tagName ) ) {
                continue;
            }

            $class_name = ' ' . trim( (string) $child->getAttribute( 'class' ) ) . ' ';
            if ( false !== strpos( $class_name, ' lunara-debrief-pair-header ' ) ) {
                $in_pairings   = true;
                $pairing_title = trim( wp_strip_all_tags( $child->textContent ) ) ?: $pairing_title;
                continue;
            }

            if ( $in_pairings ) {
                $pairing_items[] = lunara_get_dom_node_outer_html( $child );
            } else {
                $signature_items[] = lunara_get_dom_node_outer_html( $child );
            }
        }

        $signature_html = '';
        if ( ! empty( $heading_html ) || ! empty( $kicker_html ) || ! empty( $signature_items ) ) {
            $signature_html .= '<section class="lunara-debrief-block lunara-debrief-block--signature">';
            $signature_html .= $heading_html;
            $signature_html .= $kicker_html;
            if ( ! empty( $signature_items ) ) {
                $signature_html .= '<ul class="lunara-debrief-list lunara-debrief-list--signature">' . implode( '', $signature_items ) . '</ul>';
            }
            $signature_html .= '</section>';
        }

        $pairings_html = '';
        if ( ! empty( $pairing_items ) ) {
            $pairings_html .= '<section class="lunara-debrief-block lunara-debrief-block--pairings">';
            $pairings_html .= '<div class="lunara-debrief-pairings-head">';
            $pairings_html .= '<p class="lunara-debrief-kicker lunara-debrief-kicker--pairings">' . esc_html( $pairing_title ) . '</p>';
            $pairings_html .= '</div>';
            $pairings_html .= '<ul class="lunara-debrief-list lunara-debrief-list--pairings">' . implode( '', $pairing_items ) . '</ul>';
            $pairings_html .= '</section>';
        }

        libxml_clear_errors();
        libxml_use_internal_errors( $previous );

        return array(
            'signature' => '' !== $signature_html ? $signature_html : $fallback['signature'],
            'pairings'  => $pairings_html,
        );
    }
}

if ( ! function_exists( 'lunara_pair_it_with_shortcode' ) ) {
    /**
     * Shortcode: render only the Pair It With portion of the Lunara Debrief module.
     *
     * Usage:
     * [lunara_pair_it_with]
     * [lunara_pair_it_with id="123"]
     */
    function lunara_pair_it_with_shortcode( $atts ) {
        $atts = shortcode_atts(
            array(
                'id' => 0,
            ),
            $atts,
            'lunara_pair_it_with'
        );

        $post_id = absint( $atts['id'] );
        if ( $post_id <= 0 ) {
            $post_id = get_the_ID();
        }

        if ( $post_id <= 0 || ! function_exists( 'lunara_debrief_shortcode' ) ) {
            return '';
        }

        // Prefer the self-contained uniform card renderer.
        if ( function_exists( 'lunara_render_pair_it_with_cards' ) ) {
            $cards = lunara_render_pair_it_with_cards( $post_id );
            if ( '' !== trim( (string) $cards ) ) {
                return $cards;
            }
        }

        $target_post = get_post( $post_id );
        if ( ! ( $target_post instanceof WP_Post ) ) {
            return '';
        }

        $previous_post = $GLOBALS['post'] ?? null;
        $GLOBALS['post'] = $target_post;
        setup_postdata( $target_post );

        $debrief_html = trim( (string) lunara_debrief_shortcode( array() ) );

        if ( $previous_post instanceof WP_Post ) {
            $GLOBALS['post'] = $previous_post;
            setup_postdata( $previous_post );
        } else {
            unset( $GLOBALS['post'] );
            wp_reset_postdata();
        }

        if ( '' === $debrief_html ) {
            return '';
        }

        $split = lunara_split_review_debrief_block( $debrief_html );
        return trim( (string) ( $split['pairings'] ?? '' ) );
    }
}
add_shortcode( 'lunara_pair_it_with', 'lunara_pair_it_with_shortcode' );
