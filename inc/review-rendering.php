<?php
/**
 * Review Rendering — archives, card builders, visual slots, and editorial shells.
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ========================================
   WHERE TO WATCH - affiliate-ready review links
   ======================================== */

if ( ! function_exists( 'lunara_get_review_where_provider_catalog' ) ) {
    function lunara_get_review_where_provider_catalog() {
        return array(
            'netflix'            => array( 'label' => 'Netflix', 'url' => 'https://www.netflix.com/search?q=%s', 'type' => 'streaming', 'eyebrow' => __( 'Streaming', 'lunara-film' ) ),
            'max'                => array( 'label' => 'Max', 'url' => 'https://play.max.com/search?q=%s', 'type' => 'streaming', 'eyebrow' => __( 'Streaming', 'lunara-film' ) ),
            'hbo max'            => array( 'label' => 'Max', 'url' => 'https://play.max.com/search?q=%s', 'type' => 'streaming', 'eyebrow' => __( 'Streaming', 'lunara-film' ) ),
            'hulu'               => array( 'label' => 'Hulu', 'url' => 'https://www.hulu.com/search?q=%s', 'type' => 'streaming', 'eyebrow' => __( 'Streaming', 'lunara-film' ) ),
            'prime video'        => array( 'label' => 'Prime Video', 'url' => 'https://www.amazon.com/s?k=%s&i=instant-video', 'type' => 'rental', 'eyebrow' => __( 'Rent or Buy', 'lunara-film' ) ),
            'amazon prime video' => array( 'label' => 'Prime Video', 'url' => 'https://www.amazon.com/s?k=%s&i=instant-video', 'type' => 'rental', 'eyebrow' => __( 'Rent or Buy', 'lunara-film' ) ),
            'amazon prime'       => array( 'label' => 'Prime Video', 'url' => 'https://www.amazon.com/s?k=%s&i=instant-video', 'type' => 'rental', 'eyebrow' => __( 'Rent or Buy', 'lunara-film' ) ),
            'amazon'             => array( 'label' => 'Amazon', 'url' => 'https://www.amazon.com/s?k=%s&i=instant-video', 'type' => 'rental', 'eyebrow' => __( 'Rent or Buy', 'lunara-film' ) ),
            'apple tv+'          => array( 'label' => 'Apple TV+', 'url' => 'https://tv.apple.com/search?term=%s', 'type' => 'streaming', 'eyebrow' => __( 'Streaming', 'lunara-film' ) ),
            'apple tv plus'      => array( 'label' => 'Apple TV+', 'url' => 'https://tv.apple.com/search?term=%s', 'type' => 'streaming', 'eyebrow' => __( 'Streaming', 'lunara-film' ) ),
            'apple tv'           => array( 'label' => 'Apple TV', 'url' => 'https://tv.apple.com/search?term=%s', 'type' => 'rental', 'eyebrow' => __( 'Rent or Buy', 'lunara-film' ) ),
            'itunes'             => array( 'label' => 'Apple TV', 'url' => 'https://tv.apple.com/search?term=%s', 'type' => 'rental', 'eyebrow' => __( 'Rent or Buy', 'lunara-film' ) ),
            'vudu'               => array( 'label' => 'Fandango at Home', 'url' => 'https://www.fandangoathome.com/search?q=%s', 'type' => 'rental', 'eyebrow' => __( 'Rent or Buy', 'lunara-film' ) ),
            'fandango at home'   => array( 'label' => 'Fandango at Home', 'url' => 'https://www.fandangoathome.com/search?q=%s', 'type' => 'rental', 'eyebrow' => __( 'Rent or Buy', 'lunara-film' ) ),
            'google play'        => array( 'label' => 'Google Play', 'url' => 'https://play.google.com/store/search?q=%s&c=movies', 'type' => 'rental', 'eyebrow' => __( 'Rent or Buy', 'lunara-film' ) ),
            'youtube'            => array( 'label' => 'YouTube', 'url' => 'https://www.youtube.com/results?search_query=%s%20movie', 'type' => 'rental', 'eyebrow' => __( 'Rent or Buy', 'lunara-film' ) ),
            'paramount+'         => array( 'label' => 'Paramount+', 'url' => 'https://www.paramountplus.com/', 'type' => 'streaming', 'eyebrow' => __( 'Streaming', 'lunara-film' ) ),
            'paramount plus'     => array( 'label' => 'Paramount+', 'url' => 'https://www.paramountplus.com/', 'type' => 'streaming', 'eyebrow' => __( 'Streaming', 'lunara-film' ) ),
            'peacock'            => array( 'label' => 'Peacock', 'url' => 'https://www.peacocktv.com/', 'type' => 'streaming', 'eyebrow' => __( 'Streaming', 'lunara-film' ) ),
            'disney+'            => array( 'label' => 'Disney+', 'url' => 'https://www.disneyplus.com/', 'type' => 'streaming', 'eyebrow' => __( 'Streaming', 'lunara-film' ) ),
            'disney plus'        => array( 'label' => 'Disney+', 'url' => 'https://www.disneyplus.com/', 'type' => 'streaming', 'eyebrow' => __( 'Streaming', 'lunara-film' ) ),
            'criterion channel'  => array( 'label' => 'Criterion Channel', 'url' => 'https://www.criterionchannel.com/', 'type' => 'streaming', 'eyebrow' => __( 'Streaming', 'lunara-film' ) ),
            'mubi'               => array( 'label' => 'Mubi', 'url' => 'https://mubi.com/', 'type' => 'streaming', 'eyebrow' => __( 'Streaming', 'lunara-film' ) ),
            'tubi'               => array( 'label' => 'Tubi', 'url' => 'https://tubitv.com/search/%s', 'type' => 'free', 'eyebrow' => __( 'Free Stream', 'lunara-film' ) ),
            'shudder'            => array( 'label' => 'Shudder', 'url' => 'https://www.shudder.com/', 'type' => 'streaming', 'eyebrow' => __( 'Streaming', 'lunara-film' ) ),
            'kanopy'             => array( 'label' => 'Kanopy', 'url' => 'https://www.kanopy.com/', 'type' => 'streaming', 'eyebrow' => __( 'Library Stream', 'lunara-film' ) ),
            'hoopla'             => array( 'label' => 'Hoopla', 'url' => 'https://www.hoopladigital.com/search?q=%s&scope=everything&type=direct', 'type' => 'streaming', 'eyebrow' => __( 'Library Stream', 'lunara-film' ) ),
            'roku channel'       => array( 'label' => 'The Roku Channel', 'url' => 'https://therokuchannel.roku.com/search/%s', 'type' => 'free', 'eyebrow' => __( 'Free Stream', 'lunara-film' ) ),
            'amc+'               => array( 'label' => 'AMC+', 'url' => 'https://www.amcplus.com/', 'type' => 'streaming', 'eyebrow' => __( 'Streaming', 'lunara-film' ) ),
            'starz'              => array( 'label' => 'Starz', 'url' => 'https://www.starz.com/us/en/search?q=%s', 'type' => 'streaming', 'eyebrow' => __( 'Streaming', 'lunara-film' ) ),
        );
    }
}

if ( ! function_exists( 'lunara_split_review_where_entries' ) ) {
    function lunara_split_review_where_entries( $where ) {
        $where = trim( (string) $where );
        if ( '' === $where ) {
            return array();
        }

        $entries = array();
        $lines   = preg_split( '/\r\n|\r|\n/', $where );
        foreach ( (array) $lines as $line ) {
            $line = trim( wp_strip_all_tags( (string) $line ) );
            if ( '' === $line ) {
                continue;
            }

            if ( preg_match( '#https?://#i', $line ) ) {
                $entries[] = $line;
                continue;
            }

            $parts = preg_split( '/\s*(?:;|\s\/\s|\s\|\s)\s*/', $line );
            if ( is_array( $parts ) && count( $parts ) > 1 ) {
                foreach ( $parts as $part ) {
                    $part = trim( $part );
                    if ( '' !== $part ) {
                        $entries[] = $part;
                    }
                }
                continue;
            }

            if ( false !== strpos( $line, ',' ) && ! preg_match( '/[\(\)]|\b(?:jan|feb|mar|apr|may|jun|jul|aug|sep|sept|oct|nov|dec|january|february|march|april|june|july|august|september|october|november|december|\d{4})\b/i', $line ) ) {
                $comma_parts = preg_split( '/\s*,\s*/', $line );
                if ( is_array( $comma_parts ) && count( $comma_parts ) > 1 ) {
                    foreach ( $comma_parts as $part ) {
                        $part = trim( $part );
                        if ( '' !== $part ) {
                            $entries[] = $part;
                        }
                    }
                    continue;
                }
            }

            $entries[] = $line;
        }

        return array_values( array_unique( $entries ) );
    }
}

if ( ! function_exists( 'lunara_infer_review_where_label_from_url' ) ) {
    function lunara_infer_review_where_label_from_url( $url ) {
        $host = wp_parse_url( $url, PHP_URL_HOST );
        if ( ! $host ) {
            return $url;
        }

        $host        = preg_replace( '#^www\.#', '', (string) $host );
        $label_guess = preg_replace( '/\.(com|co|tv|net|org|io)(\.[a-z]{2})?$/i', '', $host );

        return ucwords( str_replace( array( '-', '.' ), ' ', $label_guess ) );
    }
}

if ( ! function_exists( 'lunara_normalize_review_where_entry' ) ) {
    function lunara_normalize_review_where_entry( $entry, $title = '', $watch_url = '' ) {
        $entry = trim( wp_strip_all_tags( (string) $entry ) );
        $title = trim( (string) $title );

        $item = array(
            'label'     => $entry,
            'url'       => '',
            'eyebrow'   => __( 'Availability', 'lunara-film' ),
            'meta'      => '',
            'type'      => 'note',
            'provider'  => '',
            'affiliate' => false,
        );

        if ( '' === $entry ) {
            return $item;
        }

        $catalog      = lunara_get_review_where_provider_catalog();
        $entry_lc     = strtolower( html_entity_decode( $entry, ENT_QUOTES ) );
        $search_title = '' !== $title ? rawurlencode( $title ) : rawurlencode( $entry );

        if ( preg_match( '#https?://[^\s|]+#i', $entry, $url_match ) ) {
            $item['url']      = $url_match[0];
            $item['label']    = lunara_infer_review_where_label_from_url( $item['url'] );
            $item['eyebrow']  = __( 'Direct Link', 'lunara-film' );
            $item['type']     = 'direct';
            $item['provider'] = sanitize_title( $item['label'] );
            $item['meta']     = preg_replace( '#^www\.#', '', (string) wp_parse_url( $item['url'], PHP_URL_HOST ) );

            $parts = array_map( 'trim', explode( '|', $entry ) );
            foreach ( $parts as $part ) {
                if ( '' === $part ) {
                    continue;
                }
                if ( preg_match( '#^https?://#i', $part ) ) {
                    $item['url'] = $part;
                    continue;
                }
                if ( preg_match( '/\b(affiliate|sponsored|paid|commission)\b/i', $part ) ) {
                    $item['affiliate'] = true;
                    continue;
                }
                if ( false === stripos( $part, 'http://' ) && false === stripos( $part, 'https://' ) && $item['label'] === lunara_infer_review_where_label_from_url( $item['url'] ) ) {
                    $item['label'] = $part;
                }
            }

            return $item;
        }

        if ( preg_match( '/\b(in theaters|theaters|theatres|theatrical|showtimes)\b/i', $entry ) ) {
            $item['label']    = __( 'Find Showtimes', 'lunara-film' );
            $item['url']      = sprintf( 'https://www.fandango.com/search?q=%s', $search_title );
            $item['eyebrow']  = __( 'Theatrical', 'lunara-film' );
            $item['meta']     = $entry;
            $item['type']     = 'theatrical';
            $item['provider'] = 'fandango';

            return $item;
        }

        if ( preg_match( '/\b(festival|tiff|sundance|sxsw|beyond fest|fantastic fest)\b/i', $entry ) ) {
            $item['eyebrow'] = __( 'Festival', 'lunara-film' );
            $item['type']    = 'festival';

            return $item;
        }

        if ( preg_match( '/\b(pvod|vod|digital|rental|rent|buy|home video)\b/i', $entry ) ) {
            $item['label']    = __( 'Find Digital Options', 'lunara-film' );
            $item['url']      = '' !== $watch_url ? $watch_url : sprintf( 'https://www.justwatch.com/us/search?q=%s', $search_title );
            $item['eyebrow']  = __( 'Digital', 'lunara-film' );
            $item['meta']     = $entry;
            $item['type']     = 'digital';
            $item['provider'] = '' !== $watch_url ? 'tmdb-watch' : 'justwatch';

            return $item;
        }

        foreach ( $catalog as $provider_key => $provider ) {
            $pattern = '/(^|[^a-z0-9])' . preg_quote( $provider_key, '/' ) . '([^a-z0-9]|$)/i';
            if ( preg_match( $pattern, $entry_lc ) ) {
                $url = (string) $provider['url'];
                if ( false !== strpos( $url, '%s' ) ) {
                    $url = sprintf( $url, $search_title );
                }

                $item['label']    = (string) $provider['label'];
                $item['url']      = $url;
                $item['eyebrow']  = (string) $provider['eyebrow'];
                $item['meta']     = sprintf( __( 'Search %s', 'lunara-film' ), $provider['label'] );
                $item['type']     = (string) $provider['type'];
                $item['provider'] = sanitize_title( $provider['label'] );

                return $item;
            }
        }

        if ( '' !== $watch_url && preg_match( '/\b(stream|streaming|watch|available|availability)\b/i', $entry ) ) {
            $item['label']    = __( 'Watch Options', 'lunara-film' );
            $item['url']      = $watch_url;
            $item['eyebrow']  = __( 'Availability', 'lunara-film' );
            $item['meta']     = $entry;
            $item['type']     = 'availability';
            $item['provider'] = 'tmdb-watch';
        }

        return $item;
    }
}

if ( ! function_exists( 'lunara_render_review_where_links' ) ) {
    function lunara_render_review_where_links( $where, $title = '', $watch_url = '' ) {
        $entries = lunara_split_review_where_entries( $where );
        if ( empty( $entries ) ) {
            return '';
        }

        $card_html       = array();
        $affiliate_count = 0;

        foreach ( $entries as $entry ) {
            $item = lunara_normalize_review_where_entry( $entry, $title, $watch_url );
            if ( '' === trim( (string) $item['label'] ) ) {
                continue;
            }

            $classes = array(
                'lunara-review-single-where-chip',
                'lunara-review-watch-card',
                'is-type-' . sanitize_html_class( (string) $item['type'] ),
            );

            if ( ! empty( $item['affiliate'] ) ) {
                $classes[] = 'is-affiliate';
                $affiliate_count++;
            }

            $meta_html = '' !== trim( (string) $item['meta'] )
                ? '<span class="lunara-review-watch-meta">' . esc_html( $item['meta'] ) . '</span>'
                : '';

            if ( '' !== trim( (string) $item['url'] ) ) {
                $rel = ! empty( $item['affiliate'] ) ? 'sponsored nofollow noopener noreferrer' : 'noopener noreferrer';

                $card_html[] = sprintf(
                    '<a class="%1$s" href="%2$s" target="_blank" rel="%3$s" data-lunara-watch-link="1" data-provider="%4$s" data-watch-type="%5$s"><span class="lunara-review-watch-eyebrow">%6$s</span><span class="lunara-review-watch-label">%7$s</span>%8$s</a>',
                    esc_attr( implode( ' ', $classes ) ),
                    esc_url( $item['url'] ),
                    esc_attr( $rel ),
                    esc_attr( (string) $item['provider'] ),
                    esc_attr( (string) $item['type'] ),
                    esc_html( (string) $item['eyebrow'] ),
                    esc_html( (string) $item['label'] ),
                    $meta_html
                );
            } else {
                $classes[]   = 'is-static';
                $card_html[] = sprintf(
                    '<span class="%1$s"><span class="lunara-review-watch-eyebrow">%2$s</span><span class="lunara-review-watch-label">%3$s</span>%4$s</span>',
                    esc_attr( implode( ' ', $classes ) ),
                    esc_html( (string) $item['eyebrow'] ),
                    esc_html( (string) $item['label'] ),
                    $meta_html
                );
            }
        }

        if ( empty( $card_html ) ) {
            return '';
        }

        $html = '<div class="lunara-review-single-where-links lunara-review-watch-links" data-lunara-watch-grid="review">' . implode( '', $card_html );
        if ( $affiliate_count > 0 ) {
            $html .= '<p class="lunara-review-watch-disclosure">' . esc_html__( 'Some outbound watch links may be affiliate links. Lunara may earn a commission when you use them.', 'lunara-film' ) . '</p>';
        }
        $html .= '</div>';

        return $html;
    }
}

if ( ! function_exists( 'lunara_output_review_watch_link_css' ) ) {
    function lunara_output_review_watch_link_css() {
        if ( ! is_singular( 'review' ) ) {
            return;
        }
        ?>
        <style id="lunara-review-watch-links-css">
        body.single-review .lunara-review-watch-links {
            display: grid !important;
            grid-template-columns: repeat(auto-fit, minmax(158px, 1fr)) !important;
            gap: 10px !important;
        }
        body.single-review .lunara-review-single-where-chip.lunara-review-watch-card {
            width: 100% !important;
            min-height: 74px !important;
            display: inline-flex !important;
            align-items: flex-start !important;
            justify-content: flex-start !important;
            flex-direction: column !important;
            gap: 4px !important;
            padding: 11px 12px !important;
            border: 1px solid rgba(201,169,97,.28) !important;
            border-radius: 8px !important;
            background: linear-gradient(180deg, rgba(15,29,46,.94), rgba(10,21,32,.98)) !important;
            color: #FAFBFC !important;
            letter-spacing: 0 !important;
            line-height: 1.25 !important;
            text-align: left !important;
            text-decoration: none !important;
            white-space: normal !important;
        }
        body.single-review .lunara-review-single-where-chip.lunara-review-watch-card:hover,
        body.single-review .lunara-review-single-where-chip.lunara-review-watch-card:focus-visible {
            border-color: rgba(224,196,129,.58) !important;
            background: linear-gradient(180deg, rgba(19,39,62,.96), rgba(10,21,32,1)) !important;
            color: #FAFBFC !important;
            transform: translateY(-1px);
        }
        body.single-review .lunara-review-watch-card.is-static {
            border-color: rgba(168,168,184,.22) !important;
            background: rgba(168,168,184,.08) !important;
        }
        body.single-review .lunara-review-watch-eyebrow {
            color: #c9a961 !important;
            font-size: .66rem !important;
            font-weight: 700 !important;
            letter-spacing: .11em !important;
            line-height: 1 !important;
            text-transform: uppercase !important;
        }
        body.single-review .lunara-review-watch-label {
            color: #FAFBFC !important;
            font-size: .94rem !important;
            font-weight: 700 !important;
            line-height: 1.18 !important;
        }
        body.single-review .lunara-review-watch-meta {
            color: #A8A8B8 !important;
            font-size: .72rem !important;
            font-weight: 500 !important;
            line-height: 1.25 !important;
            overflow-wrap: anywhere !important;
        }
        body.single-review .lunara-review-watch-card.is-affiliate::after {
            content: "Affiliate";
            margin-top: 2px;
            color: #e0c481;
            font-size: .64rem;
            font-weight: 700;
            letter-spacing: .09em;
            text-transform: uppercase;
        }
        body.single-review .lunara-review-watch-disclosure {
            grid-column: 1 / -1 !important;
            margin: 0 !important;
            color: #A8A8B8 !important;
            font-size: .72rem !important;
            line-height: 1.4 !important;
        }
        @media (max-width: 900px) {
            body.single-review .lunara-review-watch-links {
                grid-template-columns: minmax(0, 1fr) !important;
            }
            body.single-review .lunara-review-single-where-chip.lunara-review-watch-card {
                min-height: 68px !important;
                padding: 10px 11px !important;
            }
        }
        </style>
        <?php
    }
    add_action( 'wp_head', 'lunara_output_review_watch_link_css', 102 );
}

/* ========================================
   WHERE TO WATCH — chip renderer for review singles
   ======================================== */

if ( ! function_exists( 'lunara_render_review_where_links' ) ) {
    function lunara_render_review_where_links( $where, $title = '', $watch_url = '' ) {
        $where = trim( (string) $where );
        if ( '' === $where ) {
            return '';
        }

        $title = trim( (string) $title );
        $tokens = preg_split( '/\s*(?:,|\/|\||;)\s*/', $where );
        if ( ! is_array( $tokens ) ) {
            $tokens = array( $where );
        }
        $tokens = array_values(
            array_filter(
                array_map( 'trim', $tokens ),
                static function ( $value ) {
                    return '' !== $value;
                }
            )
        );

        if ( empty( $tokens ) ) {
            $tokens = array( $where );
        }

        $provider_map = array(
            'netflix'                => 'https://www.netflix.com/search?q=%s',
            'max'                    => 'https://play.max.com/search?q=%s',
            'hbo max'                => 'https://play.max.com/search?q=%s',
            'hulu'                   => 'https://www.hulu.com/search?q=%s',
            'prime video'            => 'https://www.amazon.com/s?k=%s&i=instant-video',
            'amazon prime video'     => 'https://www.amazon.com/s?k=%s&i=instant-video',
            'amazon'                 => 'https://www.amazon.com/s?k=%s&i=instant-video',
            'apple tv+'              => 'https://tv.apple.com/search?term=%s',
            'apple tv plus'          => 'https://tv.apple.com/search?term=%s',
            'apple tv'               => 'https://tv.apple.com/search?term=%s',
            'paramount+'             => 'https://www.paramountplus.com/',
            'paramount plus'         => 'https://www.paramountplus.com/',
            'peacock'                => 'https://www.peacocktv.com/',
            'disney+'                => 'https://www.disneyplus.com/',
            'disney plus'            => 'https://www.disneyplus.com/',
            'criterion channel'      => 'https://www.criterionchannel.com/',
            'mubi'                   => 'https://mubi.com/',
            'fandango at home'       => 'https://www.fandangoathome.com/search?q=%s',
            'fandango'               => 'https://www.fandangoathome.com/search?q=%s',
            'vudu'                   => 'https://www.fandangoathome.com/search?q=%s',
            'google play'            => 'https://play.google.com/store/search?q=%s&c=movies',
            'itunes'                 => 'https://tv.apple.com/search?term=%s',
            'apple itunes'           => 'https://tv.apple.com/search?term=%s',
            'amazon prime'           => 'https://www.amazon.com/s?k=%s&i=instant-video',
            'tubi'                   => 'https://tubitv.com/search/%s',
            'shudder'                => 'https://www.shudder.com/',
            'kanopy'                 => 'https://www.kanopy.com/',
            'theaters'               => '',
            'theatrical'             => '',
            'in theaters'            => '',
            'pvod'                   => '',
            'vod'                    => '',
            'digital'                => '',
        );

        $chip_html = array();
        foreach ( $tokens as $token ) {
            $label = $token;
            $token_lc = strtolower( $token );
            $url = '';

            if ( filter_var( $token, FILTER_VALIDATE_URL ) ) {
                $url = $token;
                $label = wp_parse_url( $token, PHP_URL_HOST );
                $label = $label ? preg_replace( '#^www\.#', '', (string) $label ) : $token;
            } elseif ( isset( $provider_map[ $token_lc ] ) ) {
                $mapped = $provider_map[ $token_lc ];
                if ( '' !== $mapped ) {
                    $url = false !== strpos( $mapped, '%s' ) ? sprintf( $mapped, rawurlencode( $title ) ) : $mapped;
                } elseif ( '' !== $watch_url ) {
                    $url = $watch_url;
                }
            } elseif ( '' !== $watch_url ) {
                $url = $watch_url;
            }

            if ( '' !== $url ) {
                $chip_html[] = sprintf(
                    '<a class="lunara-review-single-where-chip" href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
                    esc_url( $url ),
                    esc_html( $label )
                );
            } else {
                $chip_html[] = sprintf(
                    '<span class="lunara-review-single-where-chip is-static">%s</span>',
                    esc_html( $label )
                );
            }
        }

        return '<div class="lunara-review-single-where-links">' . implode( '', $chip_html ) . '</div>';
    }
}

/* ========================================
   LUNARA 2.0 - REVIEW ARCHIVES / LEDGER HIGHLIGHTS / METADATA
   ======================================== */

/**
 * Register archive taxonomies for director and review year.
 */
if ( ! defined( 'LUNARA_CORE_VERSION' ) ) {
    function lunara_register_review_taxonomies() {
        register_taxonomy( 'lunara_director', array( 'review' ), array(
            'labels' => array(
                'name'          => __( 'Directors', 'lunara-film' ),
                'singular_name' => __( 'Director', 'lunara-film' ),
            ),
            'public'       => true,
            'hierarchical' => false,
            'show_in_rest' => true,
            'rewrite'      => array( 'slug' => 'director' ),
        ) );

        register_taxonomy( 'lunara_review_year', array( 'review' ), array(
            'labels' => array(
                'name'          => __( 'Review Years', 'lunara-film' ),
                'singular_name' => __( 'Review Year', 'lunara-film' ),
            ),
            'public'       => true,
            'hierarchical' => false,
            'show_in_rest' => true,
            'rewrite'      => array( 'slug' => 'review-year' ),
        ) );
    }
    add_action( 'init', 'lunara_register_review_taxonomies', 20 );

    /**
     * Review detail meta box.
     */
    function lunara_add_review_details_meta_box() {
        add_meta_box(
            'lunara_review_details_meta',
            'Review Details',
            'lunara_review_details_meta_callback',
            'review',
            'side',
            'default'
        );
    }
    add_action( 'add_meta_boxes', 'lunara_add_review_details_meta_box' );

    function lunara_review_details_meta_callback( $post ) {
        wp_nonce_field( 'lunara_review_details_nonce', 'lunara_review_details_nonce' );
        $director = get_post_meta( $post->ID, '_lunara_director', true );
        $runtime  = get_post_meta( $post->ID, '_lunara_runtime', true );
        $studio   = get_post_meta( $post->ID, '_lunara_studio', true );
        ?>
        <p><label for="lunara_director"><strong>Director</strong></label><br>
        <input type="text" name="lunara_director" id="lunara_director" value="<?php echo esc_attr( $director ); ?>" style="width:100%;"></p>

        <p><label for="lunara_runtime"><strong>Runtime</strong></label><br>
        <input type="text" name="lunara_runtime" id="lunara_runtime" value="<?php echo esc_attr( $runtime ); ?>" placeholder="142 min" style="width:100%;"></p>

        <p><label for="lunara_studio"><strong>Studio / Distributor</strong></label><br>
        <input type="text" name="lunara_studio" id="lunara_studio" value="<?php echo esc_attr( $studio ); ?>" style="width:100%;"></p>
        <?php
    }

    function lunara_save_review_details_meta( $post_id ) {
        if ( ! isset( $_POST['lunara_review_details_nonce'] ) ) return;
        if ( ! wp_verify_nonce( $_POST['lunara_review_details_nonce'], 'lunara_review_details_nonce' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        foreach ( array( 'lunara_director', 'lunara_runtime', 'lunara_studio' ) as $field ) {
            if ( isset( $_POST[ $field ] ) ) {
                update_post_meta( $post_id, '_' . $field, sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) );
            }
        }
    }
    add_action( 'save_post_review', 'lunara_save_review_details_meta' );

    /**
     * Keep archive taxonomies synchronized with review meta.
     */
    function lunara_sync_review_archive_terms( $post_id ) {
        if ( wp_is_post_revision( $post_id ) || 'review' !== get_post_type( $post_id ) ) {
            return;
        }

        $director = trim( (string) get_post_meta( $post_id, '_lunara_director', true ) );
        $year     = trim( (string) get_post_meta( $post_id, '_lunara_year', true ) );

        if ( $director !== '' ) {
            wp_set_object_terms( $post_id, array( $director ), 'lunara_director', false );
        }

        if ( $year !== '' ) {
            wp_set_object_terms( $post_id, array( $year ), 'lunara_review_year', false );
        }
    }
    add_action( 'save_post_review', 'lunara_sync_review_archive_terms', 30 );
}

/**
 * Visual Image Toolkit — helper panel in the review editor sidebar.
 * Placed outside LUNARA_CORE_VERSION guard so it always registers.
 */
if ( ! function_exists( 'lunara_add_image_toolkit_meta_box' ) ) {
    function lunara_add_image_toolkit_meta_box() {
        add_meta_box(
            'lunara_image_toolkit',
            'Lunara Image Toolkit',
            'lunara_image_toolkit_callback',
            'review',
            'side',
            'high'
        );
    }
    add_action( 'add_meta_boxes', 'lunara_add_image_toolkit_meta_box' );

    function lunara_image_toolkit_callback( $post ) {
        ?>
        <style>
            .lunara-toolkit-section { margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid #2c3e50; }
            .lunara-toolkit-section:last-child { border-bottom: none; margin-bottom: 0; }
            .lunara-toolkit-section h4 { margin: 0 0 6px; color: #c9a961; font-size: 12px; text-transform: uppercase; letter-spacing: .08em; }
            .lunara-toolkit-section p { margin: 0 0 6px; font-size: 12px; color: #8899aa; line-height: 1.5; }
            .lunara-toolkit-code { display: block; margin: 6px 0; padding: 8px 10px; background: #0d1923; border: 1px solid #1e3045; border-radius: 6px; font-family: monospace; font-size: 11px; color: #e0c481; white-space: pre-wrap; word-break: break-all; cursor: pointer; position: relative; }
            .lunara-toolkit-code:hover { border-color: #c9a961; }
            .lunara-toolkit-code::after { content: 'click to copy'; position: absolute; top: 4px; right: 6px; font-size: 9px; color: #556677; font-family: sans-serif; }
            .lunara-toolkit-styles { display: grid; grid-template-columns: 1fr 1fr; gap: 4px; margin-top: 6px; }
            .lunara-toolkit-styles span { display: block; padding: 4px 6px; background: #0d1923; border: 1px solid #1e3045; border-radius: 4px; font-size: 10px; color: #8899aa; text-align: center; }
            .lunara-toolkit-styles span strong { color: #e0c481; display: block; font-size: 11px; }
        </style>

        <div class="lunara-toolkit-section">
            <h4>Quick Method — Media Insert</h4>
            <p>Just drop an image into the review body using the <strong>+</strong> button or <code>/image</code>. It auto-gets the Lunara cinematic frame treatment. Use <strong>Full Width</strong> alignment for edge-to-edge stills.</p>
        </div>

        <div class="lunara-toolkit-section">
            <h4>Power Method — Shortcode</h4>
            <p>Paste this in the review body wherever you want it:</p>
            <code class="lunara-toolkit-code" onclick="navigator.clipboard.writeText(this.innerText.replace('click to copy','').trim())">[lunara_still url="" caption="" kicker="" style="default"]</code>
        </div>

        <div class="lunara-toolkit-section">
            <h4>Available Styles</h4>
            <div class="lunara-toolkit-styles">
                <span><strong>default</strong>Inline frame</span>
                <span><strong>full</strong>Viewport-wide</span>
                <span><strong>hero</strong>16:9 crop</span>
                <span><strong>inset</strong>Centered narrow</span>
                <span><strong>left</strong>Float left</span>
                <span><strong>right</strong>Float right</span>
                <span><strong>pair</strong>Half-width</span>
            </div>
        </div>

        <div class="lunara-toolkit-section">
            <h4>Examples</h4>
            <code class="lunara-toolkit-code" onclick="navigator.clipboard.writeText(this.innerText.replace('click to copy','').trim())">[lunara_still url="https://..." style="full" caption="The frame that proves the point" kicker="Visual Evidence"]</code>
            <code class="lunara-toolkit-code" onclick="navigator.clipboard.writeText(this.innerText.replace('click to copy','').trim())">[lunara_still url="https://..." style="inset" caption="A quieter moment"]</code>
        </div>

        <div class="lunara-toolkit-section">
            <h4>Attributes</h4>
            <p><strong>url</strong> — image URL (required)<br>
            <strong>caption</strong> — italic text below<br>
            <strong>kicker</strong> — gold uppercase label<br>
            <strong>style</strong> — layout style<br>
            <strong>alt</strong> — accessibility text<br>
            <strong>loading</strong> — eager or lazy</p>
        </div>

        <script>
        document.querySelectorAll('.lunara-toolkit-code').forEach(function(el) {
            el.addEventListener('click', function() {
                var text = el.innerText.replace('click to copy', '').trim();
                navigator.clipboard.writeText(text).then(function() {
                    var orig = el.style.borderColor;
                    el.style.borderColor = '#c9a961';
                    setTimeout(function() { el.style.borderColor = orig; }, 800);
                });
            });
        });
        </script>
        <?php
    }
}

/**
 * Helper for card excerpt.
 */
function lunara_card_excerpt( $post_id, $words = 22 ) {
    if ( has_excerpt( $post_id ) ) {
        return wp_trim_words( get_the_excerpt( $post_id ), $words );
    }
    return wp_trim_words( wp_strip_all_tags( get_post_field( 'post_content', $post_id ) ), $words );
}

/**
 * Cached home section review IDs.
 */
function lunara_cached_review_ids( $cache_group, $count, $query_args ) {
    $count = max( 1, (int) $count );
    $cache_key = sprintf( 'lunara_%s_%d_v1', sanitize_key( $cache_group ), $count );
    $post_ids = get_transient( $cache_key );

    if ( ! is_array( $post_ids ) ) {
        $query_args = wp_parse_args(
            $query_args,
            array(
                'post_type'              => 'review',
                'posts_per_page'         => $count,
                'post_status'            => 'publish',
                'ignore_sticky_posts'    => true,
                'no_found_rows'          => true,
                'fields'                 => 'ids',
                'orderby'                => 'date',
                'order'                  => 'DESC',
                'update_post_meta_cache' => false,
                'update_post_term_cache' => false,
            )
        );

        $query_args['posts_per_page'] = $count;
        $post_ids = get_posts( $query_args );
        $post_ids = array_values( array_map( 'intval', is_array( $post_ids ) ? $post_ids : array() ) );

        set_transient( $cache_key, $post_ids, 15 * MINUTE_IN_SECONDS );
    }

    return $post_ids;
}

/**
 * Prime post caches used by front-page cards before rendering.
 */
function lunara_prime_review_card_caches( $post_ids ) {
    $post_ids = array_values( array_filter( array_map( 'intval', (array) $post_ids ) ) );
    if ( empty( $post_ids ) ) {
        return;
    }

    update_meta_cache( 'post', $post_ids );
    update_object_term_cache( $post_ids, 'post' );
}

/**
 * Build a review query from cached IDs.
 */
function lunara_reviews_query_from_ids( $post_ids ) {
    $post_ids = array_values( array_filter( array_map( 'intval', (array) $post_ids ) ) );

    if ( empty( $post_ids ) ) {
        return new WP_Query(
            array(
                'post_type'      => 'review',
                'post__in'       => array( 0 ),
                'posts_per_page' => 0,
                'no_found_rows'  => true,
            )
        );
    }

    lunara_prime_review_card_caches( $post_ids );

    return new WP_Query(
        array(
            'post_type'              => 'review',
            'post__in'               => $post_ids,
            'posts_per_page'         => count( $post_ids ),
            'orderby'                => 'post__in',
            'post_status'            => 'publish',
            'ignore_sticky_posts'    => true,
            'no_found_rows'          => true,
            'update_post_meta_cache' => true,
            'update_post_term_cache' => true,
        )
    );
}

/**
 * Build a standard post query from a curated list of IDs.
 */
function lunara_posts_query_from_ids( $post_ids ) {
    $post_ids = array_values( array_filter( array_map( 'intval', (array) $post_ids ) ) );
    $post_types = array( 'post' );

    if ( post_type_exists( 'journal' ) ) {
        $post_types[] = 'journal';
    }

    if ( empty( $post_ids ) ) {
        return new WP_Query(
            array(
                'post_type'      => array_values( array_unique( $post_types ) ),
                'post__in'       => array( 0 ),
                'posts_per_page' => 0,
                'no_found_rows'  => true,
            )
        );
    }

    lunara_prime_review_card_caches( $post_ids );

    return new WP_Query(
        array(
            'post_type'              => array_values( array_unique( $post_types ) ),
            'post__in'               => $post_ids,
            'posts_per_page'         => count( $post_ids ),
            'orderby'                => 'post__in',
            'post_status'            => 'publish',
            'ignore_sticky_posts'    => true,
            'no_found_rows'          => true,
            'update_post_meta_cache' => true,
            'update_post_term_cache' => true,
        )
    );
}

/**
 * Resolve the editorial label for a standard post card.
 */
function lunara_get_dispatch_type_label( $post_id ) {
    $priority_labels = array(
        'podcast'      => __( 'Podcast', 'lunara-film' ),
        'audio'        => __( 'Podcast', 'lunara-film' ),
        'news'         => __( 'News', 'lunara-film' ),
        'reaction'     => __( 'Reaction', 'lunara-film' ),
        'reactions'    => __( 'Reaction', 'lunara-film' ),
        'think-piece'  => __( 'Think Piece', 'lunara-film' ),
        'think-pieces' => __( 'Think Piece', 'lunara-film' ),
        'essay'        => __( 'Essay', 'lunara-film' ),
        'essays'       => __( 'Essay', 'lunara-film' ),
        'ink'          => __( 'Ink', 'lunara-film' ),
        'interview'    => __( 'Interview', 'lunara-film' ),
    );

    if ( 'journal' === get_post_type( $post_id ) ) {
        $override = trim( (string) get_post_meta( $post_id, '_lunara_journal_kicker', true ) );
        if ( '' !== $override ) {
            return $override;
        }

        $journal_terms = get_the_terms( $post_id, 'journal_type' );
        if ( is_array( $journal_terms ) ) {
            foreach ( $priority_labels as $slug => $label ) {
                foreach ( $journal_terms as $term ) {
                    if ( $term instanceof WP_Term && $term->slug === $slug ) {
                        return $label;
                    }
                }
            }

            foreach ( $journal_terms as $term ) {
                if ( $term instanceof WP_Term && '' !== trim( (string) $term->name ) ) {
                    return (string) $term->name;
                }
            }
        }

        return __( 'Journal', 'lunara-film' );
    }

    $terms = get_the_terms( $post_id, 'category' );
    if ( ! is_array( $terms ) ) {
        return __( 'Dispatch', 'lunara-film' );
    }

    foreach ( $priority_labels as $slug => $label ) {
        foreach ( $terms as $term ) {
            if ( $term instanceof WP_Term && $term->slug === $slug ) {
                return $label;
            }
        }
    }

    foreach ( $terms as $term ) {
        if ( ! ( $term instanceof WP_Term ) ) {
            continue;
        }

        if ( 'uncategorized' === $term->slug ) {
            continue;
        }

        if ( '' !== trim( (string) $term->name ) ) {
            return (string) $term->name;
        }
    }

    return __( 'Dispatch', 'lunara-film' );
}

/**
 * Resolve a stable editorial type slug for styling and layout accents.
 */
function lunara_get_dispatch_type_slug( $post_id ) {
    $priority_slugs = array(
        'podcast'      => 'podcast',
        'audio'        => 'podcast',
        'news'         => 'news',
        'reaction'     => 'reaction',
        'reactions'    => 'reaction',
        'think-piece'  => 'essay',
        'think-pieces' => 'essay',
        'essay'        => 'essay',
        'essays'       => 'essay',
        'ink'          => 'ink',
        'interview'    => 'interview',
    );

    if ( 'journal' === get_post_type( $post_id ) ) {
        $journal_terms = get_the_terms( $post_id, 'journal_type' );
        if ( is_array( $journal_terms ) ) {
            foreach ( $priority_slugs as $slug => $resolved_slug ) {
                foreach ( $journal_terms as $term ) {
                    if ( $term instanceof WP_Term && $term->slug === $slug ) {
                        return $resolved_slug;
                    }
                }
            }

            foreach ( $journal_terms as $term ) {
                if ( ! ( $term instanceof WP_Term ) ) {
                    continue;
                }

                $fallback_slug = sanitize_title( (string) $term->slug );
                if ( '' !== $fallback_slug ) {
                    return $fallback_slug;
                }
            }
        }

        return 'journal';
    }

    $terms = get_the_terms( $post_id, 'category' );
    if ( ! is_array( $terms ) ) {
        return 'dispatch';
    }

    foreach ( $priority_slugs as $slug => $resolved_slug ) {
        foreach ( $terms as $term ) {
            if ( $term instanceof WP_Term && $term->slug === $slug ) {
                return $resolved_slug;
            }
        }
    }

    foreach ( $terms as $term ) {
        if ( ! ( $term instanceof WP_Term ) || 'uncategorized' === $term->slug ) {
            continue;
        }

        $fallback_slug = sanitize_title( (string) $term->slug );
        if ( '' !== $fallback_slug ) {
            return $fallback_slug;
        }
    }

    return 'dispatch';
}

/**
 * Return the editorial category slugs configured for the journal lane.
 */
function lunara_get_dispatch_category_slugs() {
    $raw_slugs = lunara_theme_mod_text( 'lunara_home_dispatch_category_slugs', 'news,think-pieces,reactions,podcast' );
    return array_values( array_filter( array_map( 'sanitize_title', array_map( 'trim', explode( ',', $raw_slugs ) ) ) ) );
}

/**
 * Determine whether a category term belongs to the editorial dispatch lane.
 */
function lunara_is_editorial_category_term( $term ) {
    if ( ! ( $term instanceof WP_Term ) || 'category' !== $term->taxonomy ) {
        return false;
    }

    return in_array( $term->slug, lunara_get_dispatch_category_slugs(), true );
}

/**
 * Resolve the fallback archive URL for the homepage dispatches section.
 */
function lunara_home_dispatch_archive_url() {
    $custom_url = lunara_theme_mod_url( 'lunara_home_dispatch_button_url', '' );
    if ( '' !== $custom_url ) {
        return $custom_url;
    }

    // The Journal CPT archive is the canonical destination for the homepage lane.
    if ( post_type_exists( 'journal' ) ) {
        $journal_archive = get_post_type_archive_link( 'journal' );
        if ( $journal_archive ) {
            return $journal_archive;
        }
    }

    $slugs = lunara_get_dispatch_category_slugs();

    foreach ( $slugs as $slug ) {
        $term = get_category_by_slug( $slug );
        if ( $term instanceof WP_Term && intval( $term->count ) > 0 ) {
            $term_link = get_term_link( $term );
            if ( ! is_wp_error( $term_link ) ) {
                return $term_link;
            }
        }
    }

    $posts_page_id = absint( get_option( 'page_for_posts' ) );
    if ( $posts_page_id > 0 ) {
        $posts_page_url = get_permalink( $posts_page_id );
        if ( is_string( $posts_page_url ) && '' !== $posts_page_url ) {
            return $posts_page_url;
        }
    }

    foreach ( array( 'news', 'journal', 'blog' ) as $path ) {
        $page = get_page_by_path( $path );
        if ( $page instanceof WP_Post ) {
            $page_url = get_permalink( $page );
            if ( is_string( $page_url ) && '' !== $page_url ) {
                return $page_url;
            }
        }
    }

    $latest_post = get_posts(
        array(
            'post_type'              => 'post',
            'post_status'            => 'publish',
            'posts_per_page'         => 1,
            'orderby'                => 'date',
            'order'                  => 'DESC',
            'ignore_sticky_posts'    => true,
            'fields'                 => 'ids',
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        )
    );

    if ( ! empty( $latest_post[0] ) ) {
        return get_permalink( intval( $latest_post[0] ) );
    }

    return home_url( '/news/' );
}

/**
 * Build the year/director line used on review cards.
 */
function lunara_get_review_card_meta( $post_id ) {
    $post_id  = intval( $post_id );
    $year     = trim( (string) get_post_meta( $post_id, '_lunara_year', true ) );
    $director = trim( (string) get_post_meta( $post_id, '_lunara_director', true ) );
    $parts    = array();

    if ( '' !== $year ) {
        $parts[] = $year;
    }

    if ( '' !== $director ) {
        $parts[] = $director;
    }

    return implode( ' / ', $parts );
}

if ( ! function_exists( 'lunara_get_review_archive_sort_options' ) ) {
    /**
     * Available public review-archive sort modes.
     */
    function lunara_get_review_archive_sort_options() {
        return array(
            'release_desc'  => __( 'Newest Release', 'lunara-film' ),
            'release_asc'   => __( 'Oldest Release', 'lunara-film' ),
            'modified_desc' => __( 'Recently Updated', 'lunara-film' ),
        );
    }
}

if ( ! function_exists( 'lunara_get_review_archive_sort' ) ) {
    /**
     * Resolve the current review-archive sort from the query string.
     */
    function lunara_get_review_archive_sort() {
        $sort    = isset( $_GET['sort'] ) ? sanitize_key( wp_unslash( $_GET['sort'] ) ) : '';
        $options = lunara_get_review_archive_sort_options();

        return isset( $options[ $sort ] ) ? $sort : 'release_desc';
    }
}

if ( ! function_exists( 'lunara_get_review_archive_sort_label' ) ) {
    /**
     * Human label for the current review-archive sort.
     */
    function lunara_get_review_archive_sort_label( $sort = '' ) {
        $sort    = $sort ? sanitize_key( (string) $sort ) : lunara_get_review_archive_sort();
        $options = lunara_get_review_archive_sort_options();

        return isset( $options[ $sort ] ) ? (string) $options[ $sort ] : (string) $options['release_desc'];
    }
}

if ( ! function_exists( 'lunara_apply_review_archive_sort_args' ) ) {
    /**
     * Apply the public review-archive sort mode to a query args array.
     */
    function lunara_apply_review_archive_sort_args( $query_args, $sort = '' ) {
        $sort = $sort ? sanitize_key( (string) $sort ) : lunara_get_review_archive_sort();

        switch ( $sort ) {
            case 'release_asc':
                $query_args['orderby'] = 'date';
                $query_args['order']   = 'ASC';
                break;

            case 'modified_desc':
                $query_args['orderby'] = 'modified';
                $query_args['order']   = 'DESC';
                break;

            case 'release_desc':
            default:
                $query_args['orderby'] = 'date';
                $query_args['order']   = 'DESC';
                break;
        }

        return $query_args;
    }
}

if ( ! function_exists( 'lunara_get_review_card_modified_label' ) ) {
    /**
     * Return a compact updated label when modified date is meaningfully newer
     * than the published/release-facing date.
     */
    function lunara_get_review_card_modified_label( $post_id ) {
        $post_id       = intval( $post_id );
        $published_ts  = (int) get_post_timestamp( $post_id, 'date' );
        $modified_ts   = (int) get_post_timestamp( $post_id, 'modified' );

        if ( $post_id <= 0 || $modified_ts <= 0 ) {
            return '';
        }

        if ( $published_ts > 0 && gmdate( 'Y-m-d', $published_ts ) === gmdate( 'Y-m-d', $modified_ts ) ) {
            return '';
        }

        return sprintf(
            /* translators: %s: modified date */
            __( 'Updated %s', 'lunara-film' ),
            get_the_modified_date( 'F j, Y', $post_id )
        );
    }
}

/**
 * Provide one uniform teaser line for review cards.
 */
function lunara_get_review_card_teaser() {
    return __( 'Open the review and enter the full argument.', 'lunara-film' );
}

/**
 * Pull the reader-facing quote/excerpt for review cards.
 */
if ( ! function_exists( 'lunara_get_review_card_pull_quote' ) ) {
    function lunara_get_review_card_pull_quote( $post_id, $words = 46, $allow_fallback = false ) {
        $post_id = intval( $post_id );
        $words   = max( 12, intval( $words ) );

        if ( $post_id <= 0 ) {
            return '';
        }

        $clean_exact = static function( $value ) {
            $value = strip_shortcodes( (string) $value );
            $value = html_entity_decode( $value, ENT_QUOTES, get_bloginfo( 'charset' ) ?: 'UTF-8' );
            $value = wp_strip_all_tags( $value );
            $value = preg_replace( '/\s+/', ' ', $value );

            return trim( (string) $value );
        };

        $clean_fallback = static function( $value ) use ( $words, $clean_exact ) {
            $value = $clean_exact( $value );

            return '' === $value ? '' : wp_trim_words( $value, $words, '...' );
        };

        $meta_keys = array(
            '_lunara_pull_quote',
            '_lunara_review_pull_quote',
            'lunara_pull_quote',
            'lunara_review_pull_quote',
        );

        foreach ( $meta_keys as $meta_key ) {
            $quote = $clean_exact( get_post_meta( $post_id, $meta_key, true ) );
            if ( '' !== $quote ) {
                return $quote;
            }
        }

        if ( ! $allow_fallback ) {
            return '';
        }

        if ( has_excerpt( $post_id ) ) {
            $quote = $clean_fallback( get_the_excerpt( $post_id ) );
            if ( '' !== $quote ) {
                return $quote;
            }
        }

        if ( function_exists( 'lunara_card_excerpt' ) ) {
            $quote = $clean_fallback( lunara_card_excerpt( $post_id, $words ) );
            if ( '' !== $quote ) {
                return $quote;
            }
        }

        return $clean_fallback( get_post_field( 'post_content', $post_id ) );
    }
}

/**
 * Keep review artwork on stable source dimensions.
 */
if ( ! function_exists( 'lunara_get_review_image_profile' ) ) {
    function lunara_get_review_image_profile( $class_string = '' ) {
        $class_string = (string) $class_string;
        $profile      = array(
            'size'   => 'lunara-review-card-retina',
            'width'  => 1500,
            'height' => 2000,
            'sizes'  => '(max-width: 520px) 46vw, (max-width: 900px) 42vw, (max-width: 1180px) 30vw, 340px',
        );

        if ( false !== stripos( $class_string, 'lunara-review-feature-image' ) ) {
            $profile = array(
                'size'   => 'lunara-review-feature',
                'width'  => 1500,
                'height' => 2000,
                'sizes'  => '(max-width: 900px) 88vw, 520px',
            );
        } elseif ( false !== stripos( $class_string, 'lunara-review-single-debrief-poster' ) ) {
            $profile = array(
                'size'   => 'lunara-poster-library',
                'width'  => 2000,
                'height' => 3000,
                'sizes'  => '(max-width: 900px) 42vw, 320px',
            );
        } elseif ( false !== stripos( $class_string, 'lunara-review-visual--poster-hero' ) ) {
            $profile = array(
                'size'   => 'lunara-poster-library',
                'width'  => 2000,
                'height' => 3000,
                'sizes'  => '(max-width: 900px) min(78vw, 420px), 460px',
            );
        } elseif ( false !== stripos( $class_string, 'lunara-review-visual-image' ) ) {
            $profile = array(
                'size'   => 'lunara-hero-spotlight',
                'width'  => 960,
                'height' => 540,
                'sizes'  => '(max-width: 900px) 100vw, 960px',
            );
        }

        return $profile;
    }
}

if ( ! function_exists( 'lunara_review_image_can_use_wpcom_resize' ) ) {
    function lunara_review_image_can_use_wpcom_resize( $url ) {
        $url  = html_entity_decode( (string) $url, ENT_QUOTES, 'UTF-8' );
        $host = (string) wp_parse_url( $url, PHP_URL_HOST );

        if ( '' === $host ) {
            return false;
        }

        $host = strtolower( $host );

        return (
            'lunarafilm.com' === $host ||
            'www.lunarafilm.com' === $host ||
            false !== strpos( $host, '.wp.com' ) ||
            false !== strpos( $host, '.wordpress.com' ) ||
            false !== strpos( $url, '/wp-content/uploads/' )
        );
    }
}

if ( ! function_exists( 'lunara_lock_review_image_url' ) ) {
    function lunara_lock_review_image_url( $source_url, $profile ) {
        $source_url = html_entity_decode( (string) $source_url, ENT_QUOTES, 'UTF-8' );

        if ( '' === trim( $source_url ) ) {
            return '';
        }

        if (
            is_array( $profile ) &&
            function_exists( 'lunara_resize_wpcom_image_url' ) &&
            lunara_review_image_can_use_wpcom_resize( $source_url )
        ) {
            $locked_url = lunara_resize_wpcom_image_url(
                $source_url,
                isset( $profile['width'] ) ? absint( $profile['width'] ) : 0,
                isset( $profile['height'] ) ? absint( $profile['height'] ) : 0
            );

            if ( '' !== $locked_url ) {
                return $locked_url;
            }
        }

        return esc_url( $source_url );
    }
}

if ( ! function_exists( 'lunara_replace_img_attribute' ) ) {
    function lunara_replace_img_attribute( $html, $attribute, $value ) {
        $attribute = preg_replace( '/[^a-zA-Z0-9:_-]/', '', (string) $attribute );

        if ( '' === $attribute || ! is_string( $html ) || '' === $html ) {
            return $html;
        }

        $replacement = ' ' . $attribute . '="' . esc_attr( (string) $value ) . '"';

        if ( preg_match( '/\s' . preg_quote( $attribute, '/' ) . '=("|\')[^"\']*\1/i', $html, $match ) ) {
            return str_replace( $match[0], $replacement, $html );
        }

        return preg_replace( '/<img\b/i', '<img' . $replacement, $html, 1 );
    }
}

if ( ! function_exists( 'lunara_remove_img_attribute' ) ) {
    function lunara_remove_img_attribute( $html, $attribute ) {
        $attribute = preg_replace( '/[^a-zA-Z0-9:_-]/', '', (string) $attribute );

        if ( '' === $attribute || ! is_string( $html ) || '' === $html ) {
            return $html;
        }

        return preg_replace( '/\s' . preg_quote( $attribute, '/' ) . '=("|\')[^"\']*\1/i', '', $html, 1 );
    }
}

if ( ! function_exists( 'lunara_lock_review_image_markup' ) ) {
    function lunara_lock_review_image_markup( $html, $source_url, $profile ) {
        if ( ! is_string( $html ) || '' === trim( $html ) || ! is_array( $profile ) ) {
            return $html;
        }

        $source_url = (string) $source_url;

        if ( '' === trim( $source_url ) && preg_match( '/\ssrc=("|\')([^"\']+)\1/i', $html, $src_match ) ) {
            $source_url = $src_match[2];
        }

        $width  = isset( $profile['width'] ) ? absint( $profile['width'] ) : 0;
        $height = isset( $profile['height'] ) ? absint( $profile['height'] ) : 0;
        $src    = lunara_lock_review_image_url( $source_url, $profile );

        if ( '' === $src || 0 === $width || 0 === $height ) {
            return $html;
        }

        $html = lunara_replace_img_attribute( $html, 'src', $src );
        $html = lunara_replace_img_attribute( $html, 'data-src', $src );
        $html = lunara_replace_img_attribute( $html, 'data-no-lazy', '1' );
        $html = lunara_replace_img_attribute( $html, 'data-skip-lazy', '1' );
        $html = lunara_replace_img_attribute( $html, 'width', $width );
        $html = lunara_replace_img_attribute( $html, 'height', $height );

        if ( ! empty( $profile['sizes'] ) ) {
            $html = lunara_replace_img_attribute( $html, 'sizes', $profile['sizes'] );
        }

        $retina_profile           = $profile;
        $retina_profile['width']  = $width * 2;
        $retina_profile['height'] = $height * 2;
        $retina_src               = lunara_lock_review_image_url( $source_url, $retina_profile );

        if ( '' !== $retina_src && $retina_src !== $src ) {
            $srcset = $src . ' ' . $width . 'w, ' . $retina_src . ' ' . ( $width * 2 ) . 'w';
            $html   = lunara_replace_img_attribute( $html, 'srcset', $srcset );
            $html   = lunara_replace_img_attribute( $html, 'data-srcset', $srcset );
        } else {
            $html = lunara_remove_img_attribute( $html, 'srcset' );
            $html = lunara_remove_img_attribute( $html, 'data-srcset' );
        }

        return $html;
    }
}

/**
 * Resolve the preferred image for review cards and feature placements.
 */
if ( ! function_exists( 'lunara_get_review_card_image_data' ) ) {
    function lunara_get_review_card_image_data( $post_id, $size = 'medium_large', $attrs = array() ) {
        $post_id = intval( $post_id );

        if ( $post_id <= 0 ) {
            return array(
                'url'       => '',
                'html'      => '',
                'has_image' => false,
            );
        }

        $title = trim( wp_strip_all_tags( get_the_title( $post_id ) ) );
        $attrs = wp_parse_args(
            is_array( $attrs ) ? $attrs : array(),
            array(
                'class'    => 'lunara-review-grid-poster',
                'loading'  => 'lazy',
                'decoding' => 'async',
                'sizes'    => '(max-width: 520px) 46vw, (max-width: 900px) 42vw, (max-width: 1180px) 30vw, 340px',
                'alt'      => '' !== $title ? sprintf( __( '%s artwork', 'lunara-film' ), $title ) : __( 'Review artwork', 'lunara-film' ),
            )
        );

        $profile = lunara_get_review_image_profile( isset( $attrs['class'] ) ? (string) $attrs['class'] : '' );
        $size    = isset( $profile['size'] ) ? (string) $profile['size'] : (string) $size;

        if ( ! empty( $profile['width'] ) && ! empty( $profile['height'] ) ) {
            $attrs['width']  = absint( $profile['width'] );
            $attrs['height'] = absint( $profile['height'] );
        }

        if ( ! empty( $profile['sizes'] ) ) {
            $attrs['sizes'] = (string) $profile['sizes'];
        }

        $attrs['data-no-lazy']   = '1';
        $attrs['data-skip-lazy'] = '1';

        $url       = '';
        $html      = '';
        $card_url  = trim( (string) get_post_meta( $post_id, '_lunara_review_card_image', true ) );

        if ( '' !== $card_url ) {
            $attachment_id = attachment_url_to_postid( $card_url );

            if ( $attachment_id > 0 ) {
                $source_url = (string) wp_get_attachment_image_url( $attachment_id, 'full' );

                if ( '' === $source_url ) {
                    $source_url = (string) wp_get_attachment_image_url( $attachment_id, $size );
                }

                if ( '' === $source_url ) {
                    $source_url = esc_url_raw( $card_url );
                }

                $url  = lunara_lock_review_image_url( $source_url, $profile );
                $html = (string) wp_get_attachment_image( $attachment_id, 'full', false, $attrs );

                if ( '' === $url ) {
                    $url = esc_url_raw( $card_url );
                }

                if ( '' !== $html ) {
                    $html = lunara_lock_review_image_markup( $html, $source_url, $profile );
                }
            } else {
                $url = lunara_lock_review_image_url( $card_url, $profile );

                if ( '' !== $url ) {
                    $attr_html = '';

                    foreach ( $attrs as $attr_name => $attr_value ) {
                        $attr_name = preg_replace( '/[^a-zA-Z0-9:_-]/', '', (string) $attr_name );

                        if ( '' === $attr_name || false === $attr_value || null === $attr_value ) {
                            continue;
                        }

                        if ( true === $attr_value ) {
                            $attr_html .= ' ' . esc_attr( $attr_name );
                            continue;
                        }

                        if ( is_scalar( $attr_value ) && '' !== (string) $attr_value ) {
                            $attr_html .= ' ' . esc_attr( $attr_name ) . '="' . esc_attr( (string) $attr_value ) . '"';
                        }
                    }

                    $html = sprintf( '<img src="%1$s"%2$s>', esc_url( $url ), $attr_html );
                }
            }
        }

        if ( '' === $html && has_post_thumbnail( $post_id ) ) {
            $thumbnail_id = get_post_thumbnail_id( $post_id );
            $source_url   = $thumbnail_id ? (string) wp_get_attachment_image_url( $thumbnail_id, 'full' ) : '';

            if ( '' === $source_url ) {
                $source_url = $thumbnail_id ? (string) wp_get_attachment_image_url( $thumbnail_id, $size ) : '';
            }

            $url  = lunara_lock_review_image_url( $source_url, $profile );
            $html = (string) get_the_post_thumbnail( $post_id, 'full', $attrs );

            if ( '' === $url ) {
                $url = (string) get_the_post_thumbnail_url( $post_id, $size );
            }

            if ( '' !== $html ) {
                $html = lunara_lock_review_image_markup( $html, $source_url, $profile );
            }
        }

        return array(
            'url'       => (string) $url,
            'html'      => (string) $html,
            'has_image' => '' !== $html || '' !== $url,
        );
    }
}

/**
 * Build a stable Oscars title URL from an IMDb title id.
 */
if ( ! function_exists( 'lunara_get_oscars_title_url' ) ) {
    function lunara_get_oscars_title_url( $imdb_title_id ) {
        $imdb_title_id = strtolower( trim( (string) $imdb_title_id ) );
        if ( ! preg_match( '/^tt\d{7,8}$/', $imdb_title_id ) ) {
            return '';
        }

        return home_url( '/oscars/title/' . rawurlencode( $imdb_title_id ) . '/' );
    }
}

/**
 * Render the filtered review content so it can be re-sectioned inside the template.
 */
if ( ! function_exists( 'lunara_get_review_rendered_content' ) ) {
    function lunara_get_review_rendered_content( $post_id ) {
        $post_id = intval( $post_id );
        if ( $post_id <= 0 ) {
            return '';
        }

        $content = get_post_field( 'post_content', $post_id );
        if ( ! is_string( $content ) || '' === trim( $content ) ) {
            return '';
        }

        return (string) apply_filters( 'the_content', $content );
    }
}

/**
 * Add Lunara-owned links and Oscar pills to paired-film lines inside the debrief.
 */
if ( ! function_exists( 'lunara_enhance_review_debrief_html' ) ) {
    function lunara_enhance_review_debrief_html( $html ) {
        $html = trim( (string) $html );
        if ( '' === $html ) {
            return '';
        }

        return preg_replace_callback(
            '~(<strong>[^<]+:</strong>\s*)([^<]+?)\s*\|\s*IMDB:\s*(tt\d{7,8})(?:\s*(?:—|-)\s*([^<]+)|\s*<em>\s*(?:—|-)\s*([^<]+)\s*</em>)?~iu',
            static function( $matches ) {
                $label = $matches[1];
                $title = trim( wp_strip_all_tags( $matches[2] ) );
                $tt_id = strtolower( trim( $matches[3] ) );
                $note  = isset( $matches[4] ) && '' !== trim( (string) $matches[4] )
                    ? trim( wp_strip_all_tags( $matches[4] ) )
                    : ( isset( $matches[5] ) ? trim( wp_strip_all_tags( $matches[5] ) ) : '' );

                if ( '' !== $note ) {
                    $note = preg_replace( '/^\s*[—-]\s*/u', '', $note );
                }

                $internal_url = function_exists( 'lunara_get_internal_title_reference_url' )
                    ? lunara_get_internal_title_reference_url( $tt_id )
                    : '';

                $imdb_url = 'https://www.imdb.com/title/' . rawurlencode( $tt_id ) . '/';

                if ( '' !== $internal_url ) {
                    $title_html = sprintf(
                        '<a class="lunara-pair-title-link" href="%s"><em>%s</em></a>',
                        esc_url( $internal_url ),
                        esc_html( $title )
                    );
                } else {
                    $title_html = sprintf(
                        '<a class="lunara-pair-title-link" href="%s" target="_blank" rel="noopener noreferrer nofollow"><em>%s</em></a>',
                        esc_url( $imdb_url ),
                        esc_html( $title )
                    );
                }

                $imdb_chip = sprintf(
                    '<a class="lunara-debrief-chip lunara-debrief-chip-imdb" href="%s" target="_blank" rel="noopener noreferrer nofollow">IMDb</a>',
                    esc_url( $imdb_url )
                );

                $pill = '';
                if ( function_exists( 'lunara_get_oscar_ledger_counts' ) && function_exists( 'lunara_render_oscar_ledger_pill' ) ) {
                    $pill = lunara_render_oscar_ledger_pill( $tt_id, lunara_get_oscar_ledger_counts( $tt_id ) );
                }

                $poster_html = function_exists( 'lunara_get_title_poster_html' )
                    ? lunara_get_title_poster_html( $tt_id, 'medium', 'lunara-debrief-thumb', $title )
                    : '';

                $line_1    = '<span class="lunara-debrief-line1">' . $title_html . ' ' . $imdb_chip . ( '' !== $pill ? ' ' . $pill : '' ) . '</span>';
                $line_2    = '' !== $note ? '<span class="lunara-debrief-note">' . esc_html( $note ) . '</span>' : '';
                $text_html = '<span class="lunara-debrief-pairing-text">' . $line_1 . $line_2 . '</span>';

                if ( '' === $poster_html ) {
                    return $label . $text_html;
                }

                return $label
                    . '<span class="lunara-debrief-pairing">'
                    . '<span class="lunara-debrief-thumb-wrap">' . $poster_html . '</span>'
                    . $text_html
                    . '</span>';
            },
            $html
        );
    }
}

/**
 * Render the reviewed film poster for the debrief signature area.
 */
if ( ! function_exists( 'lunara_get_review_debrief_signature_media_html' ) ) {
    function lunara_get_review_debrief_signature_media_html( $post_id ) {
        $post_id = intval( $post_id );
        if ( $post_id <= 0 ) {
            return '';
        }

        $poster_html = '';
        $title_id    = function_exists( 'lunara_get_review_imdb_title_id' )
            ? lunara_get_review_imdb_title_id( $post_id )
            : '';
        $poster_profile = function_exists( 'lunara_get_review_image_profile' )
            ? lunara_get_review_image_profile( 'lunara-review-single-debrief-poster' )
            : array(
                'size'   => 'lunara-poster-library',
                'width'  => 1000,
                'height' => 1500,
                'sizes'  => '(max-width: 900px) 42vw, 320px',
            );

        if ( '' !== $title_id && function_exists( 'lunara_get_title_poster_html' ) ) {
            $poster_html = lunara_get_title_poster_html(
                $title_id,
                'large',
                'lunara-review-single-debrief-poster',
                get_the_title( $post_id ),
                'eager'
            );
        }

        if ( '' === trim( $poster_html ) && has_post_thumbnail( $post_id ) ) {
            $poster_source  = (string) get_the_post_thumbnail_url( $post_id, 'full' );
            $poster_attrs   = array(
                'class'    => 'lunara-review-single-debrief-poster',
                'loading'  => 'eager',
                'decoding' => 'async',
                'width'    => isset( $poster_profile['width'] ) ? absint( $poster_profile['width'] ) : 1000,
                'height'   => isset( $poster_profile['height'] ) ? absint( $poster_profile['height'] ) : 1500,
                'sizes'    => isset( $poster_profile['sizes'] ) ? (string) $poster_profile['sizes'] : '(max-width: 900px) 42vw, 320px',
                'data-no-lazy'   => '1',
                'data-skip-lazy' => '1',
            );
            $poster_html = (string) get_the_post_thumbnail(
                $post_id,
                'full',
                $poster_attrs
            );

            if ( function_exists( 'lunara_lock_review_image_markup' ) ) {
                $poster_html = lunara_lock_review_image_markup( $poster_html, $poster_source, $poster_profile );
            }
        }

        if ( '' !== trim( $poster_html ) && function_exists( 'lunara_lock_review_image_markup' ) ) {
            $poster_html = lunara_lock_review_image_markup( $poster_html, '', $poster_profile );
        }

        $title = trim( wp_strip_all_tags( get_the_title( $post_id ) ) );
        $year  = trim( (string) get_post_meta( $post_id, '_lunara_year', true ) );
        $meta  = '' !== $year ? $year : __( 'Review anchor', 'lunara-film' );

        if ( '' === trim( $poster_html ) ) {
            return '';
        }

        $poster_label = '' !== $title ? $title . ' poster' : __( 'Reviewed film poster', 'lunara-film' );
        $poster_html  = preg_replace(
            '/\salt="[^"]*"/i',
            ' alt="' . esc_attr( $poster_label ) . '"',
            $poster_html,
            1
        );
        $poster_html  = preg_replace(
            '/\sdata-image-title="[^"]*"/i',
            ' data-image-title="' . esc_attr( $title ) . '"',
            $poster_html,
            1
        );

        return sprintf(
            '<aside class="lunara-review-single-debrief-media" aria-label="%1$s"><p class="lunara-home-section-kicker">%2$s</p><div class="lunara-review-single-debrief-poster-shell">%3$s</div><div class="lunara-review-single-debrief-media-copy"><p class="lunara-review-single-debrief-media-title">%4$s</p><p class="lunara-review-single-debrief-media-meta">%5$s</p></div></aside>',
            esc_attr__( 'Reviewed film poster', 'lunara-film' ),
            esc_html__( 'Reviewed Film', 'lunara-film' ),
            $poster_html,
            esc_html( $title ),
            esc_html( $meta )
        );
    }
}

/**
 * Return configured cinematic still slot data for a review.
 */
if ( ! function_exists( 'lunara_get_review_visual_slot_data' ) ) {
    function lunara_get_review_visual_slot_presets( $post_id ) {
        $post = get_post( $post_id );
        if ( ! ( $post instanceof WP_Post ) ) {
            return array();
        }

        $slug = sanitize_title( (string) $post->post_name );

        return array();
    }

    function lunara_get_review_visual_slot_data( $post_id, $slot ) {
        $configs = array(
            'hero_banner' => array(
                'url_key'     => '_lunara_review_hero_banner',
                'caption_key' => '_lunara_review_hero_banner_caption',
                'label'       => __( 'Hero Banner', 'lunara-film' ),
            ),
            'context_shot' => array(
                'url_key'     => '_lunara_review_context_shot',
                'caption_key' => '_lunara_review_context_shot_caption',
                'label'       => __( 'Context Shot', 'lunara-film' ),
            ),
            'visual_evidence' => array(
                'url_key'     => '_lunara_review_visual_evidence',
                'caption_key' => '_lunara_review_visual_evidence_caption',
                'label'       => __( 'Visual Evidence', 'lunara-film' ),
            ),
            'thematic_echo' => array(
                'url_key'     => '_lunara_review_thematic_echo',
                'caption_key' => '_lunara_review_thematic_echo_caption',
                'label'       => __( 'Thematic Echo', 'lunara-film' ),
            ),
        );

        if ( ! isset( $configs[ $slot ] ) ) {
            return array();
        }

        $config  = $configs[ $slot ];
        $url     = trim( (string) get_post_meta( $post_id, $config['url_key'], true ) );
        $caption = trim( (string) get_post_meta( $post_id, $config['caption_key'], true ) );

        if ( '' === $url ) {
            $presets = lunara_get_review_visual_slot_presets( $post_id );
            if ( isset( $presets[ $slot ] ) && ! empty( $presets[ $slot ]['url'] ) ) {
                $url     = trim( (string) $presets[ $slot ]['url'] );
                $caption = '' !== $caption ? $caption : trim( (string) ( $presets[ $slot ]['caption'] ?? '' ) );
            }
        }

        if ( '' === $url ) {
            return array();
        }

        $title = trim( wp_strip_all_tags( get_the_title( $post_id ) ) );
        $alt   = '' !== $caption ? wp_strip_all_tags( $caption ) : trim( $title . ' ' . $config['label'] );

        return array(
            'url'     => esc_url( $url ),
            'caption' => $caption,
            'alt'     => $alt,
            'label'   => $config['label'],
            'slot'    => $slot,
        );
    }
}

/**
 * Render a cinematic still slot for the review single.
 */
if ( ! function_exists( 'lunara_render_review_visual_slot' ) ) {
    function lunara_render_review_visual_slot( $post_id, $slot, $args = array() ) {
        $data = lunara_get_review_visual_slot_data( $post_id, $slot );
        if ( empty( $data ) ) {
            return '';
        }

        $args = wp_parse_args(
            $args,
            array(
                'loading' => 'lazy',
                'context' => 'body',
            )
        );

        $classes = array(
            'lunara-review-visual',
            'lunara-review-visual--' . sanitize_html_class( str_replace( '_', '-', $slot ) ),
            'lunara-review-visual--' . sanitize_html_class( $args['context'] ),
        );
        $is_poster_hero = (
            'hero_banner' === $slot &&
            preg_match( '/poster|one-sheet|key[-_ ]?art/i', $data['url'] . ' ' . $data['alt'] )
        );
        if ( $is_poster_hero ) {
            $classes[] = 'lunara-review-visual--poster-hero';
        }

        $caption_html = '';
        if ( '' !== $data['caption'] ) {
            $caption_html = sprintf(
                '<figcaption class="lunara-review-visual-caption"><span class="lunara-home-section-kicker">%1$s</span><p>%2$s</p></figcaption>',
                esc_html( $data['label'] ),
                esc_html( $data['caption'] )
            );
        }

        $profile = function_exists( 'lunara_get_review_image_profile' )
            ? lunara_get_review_image_profile( 'lunara-review-visual-image ' . implode( ' ', $classes ) )
            : array(
                'width'  => 960,
                'height' => 540,
                'sizes'  => '(max-width: 900px) 100vw, 960px',
            );
        $width   = isset( $profile['width'] ) ? absint( $profile['width'] ) : 960;
        $height  = isset( $profile['height'] ) ? absint( $profile['height'] ) : 540;
        $src     = function_exists( 'lunara_lock_review_image_url' )
            ? lunara_lock_review_image_url( $data['url'], $profile )
            : esc_url( $data['url'] );

        return sprintf(
            '<figure class="%1$s"><div class="lunara-review-visual-frame"><img class="lunara-review-visual-image" src="%2$s" alt="%3$s" loading="%4$s" decoding="async" width="%5$d" height="%6$d" sizes="%7$s"></div>%8$s</figure>',
            esc_attr( implode( ' ', $classes ) ),
            esc_url( $src ),
            esc_attr( $data['alt'] ),
            esc_attr( $args['loading'] ),
            $width,
            $height,
            esc_attr( isset( $profile['sizes'] ) ? (string) $profile['sizes'] : '(max-width: 900px) 100vw, 960px' ),
            $caption_html
        );
    }
}

/**
 * Inject optional cinematic stills into the body of a review.
 */
if ( ! function_exists( 'lunara_insert_review_visuals_into_body_html' ) ) {
    function lunara_insert_review_visuals_into_body_html( $body_html, $post_id ) {
        $body_html = trim( (string) $body_html );
        if ( '' === $body_html || ! class_exists( 'DOMDocument' ) ) {
            return $body_html;
        }

        $slot_html = array(
            'context_shot'    => lunara_render_review_visual_slot( $post_id, 'context_shot' ),
            'visual_evidence' => lunara_render_review_visual_slot( $post_id, 'visual_evidence' ),
            'thematic_echo'   => lunara_render_review_visual_slot( $post_id, 'thematic_echo' ),
        );

        if ( ! array_filter( $slot_html ) ) {
            return $body_html;
        }

        $previous_state = libxml_use_internal_errors( true );
        $dom            = new DOMDocument( '1.0', 'UTF-8' );
        $loaded         = $dom->loadHTML(
            '<?xml encoding="utf-8" ?><div id="lunara-review-body-root">' . $body_html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        if ( ! $loaded ) {
            libxml_clear_errors();
            libxml_use_internal_errors( $previous_state );
            return $body_html;
        }

        $root = $dom->getElementById( 'lunara-review-body-root' );
        if ( ! $root ) {
            libxml_clear_errors();
            libxml_use_internal_errors( $previous_state );
            return $body_html;
        }

        $get_children = static function( $container ) {
            $children = array();
            foreach ( $container->childNodes as $child ) {
                if ( XML_ELEMENT_NODE === $child->nodeType ) {
                    $children[] = $child;
                }
            }
            return $children;
        };

        $append_fragment_after = static function( $dom, $root, $target, $html ) {
            if ( '' === trim( (string) $html ) ) {
                return;
            }

            $fragment_doc = new DOMDocument( '1.0', 'UTF-8' );
            $loaded       = $fragment_doc->loadHTML(
                '<?xml encoding="utf-8" ?><div>' . $html . '</div>',
                LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
            );

            if ( ! $loaded ) {
                return;
            }

            $wrapper = $fragment_doc->getElementsByTagName( 'div' )->item( 0 );
            if ( ! $wrapper ) {
                return;
            }

            $reference = $target ? $target->nextSibling : $root->firstChild;
            foreach ( iterator_to_array( $wrapper->childNodes ) as $child ) {
                $imported = $dom->importNode( $child, true );
                if ( $reference ) {
                    $root->insertBefore( $imported, $reference );
                } else {
                    $root->appendChild( $imported );
                }
            }
        };

        $children = $get_children( $root );

        if ( '' !== $slot_html['context_shot'] && ! empty( $children ) ) {
            $target = null;
            foreach ( $children as $child ) {
                $tag = strtolower( $child->nodeName );
                if ( in_array( $tag, array( 'h2', 'h3' ), true ) ) {
                    $target = $child;
                    break;
                }
            }

            if ( ! $target ) {
                $paragraphs = array_values(
                    array_filter(
                        $children,
                        static function( $child ) {
                            return 'p' === strtolower( $child->nodeName );
                        }
                    )
                );
                $target = $paragraphs[ min( 1, max( 0, count( $paragraphs ) - 1 ) ) ] ?? $children[0];
            }

            $append_fragment_after( $dom, $root, $target, $slot_html['context_shot'] );
            $children = $get_children( $root );
        }

        if ( '' !== $slot_html['visual_evidence'] && ! empty( $children ) ) {
            $index  = max( 1, min( count( $children ) - 2, (int) floor( count( $children ) * 0.58 ) ) );
            $target = $children[ $index - 1 ] ?? end( $children );
            $append_fragment_after( $dom, $root, $target, $slot_html['visual_evidence'] );
            $children = $get_children( $root );
        }

        if ( '' !== $slot_html['thematic_echo'] && count( $children ) >= 2 ) {
            $index  = max( 1, min( count( $children ) - 2, (int) floor( count( $children ) * 0.82 ) ) );
            $target = $children[ $index - 1 ] ?? end( $children );
            $append_fragment_after( $dom, $root, $target, $slot_html['thematic_echo'] );
        }

        $output = '';
        foreach ( $root->childNodes as $child ) {
            $output .= $dom->saveHTML( $child );
        }

        libxml_clear_errors();
        libxml_use_internal_errors( $previous_state );

        return trim( $output );
    }
}

/**
 * Split a rendered review into the main essay, the Lunara Debrief, and any postscript/share blocks.
 */
if ( ! function_exists( 'lunara_extract_review_content_sections' ) ) {
    function lunara_extract_review_content_sections( $content_html ) {
        $content_html = trim( (string) $content_html );

        $sections = array(
            'body'     => $content_html,
            'debrief'  => '',
            'postscript' => '',
        );

        if ( '' === $content_html ) {
            return $sections;
        }

        $marker_pattern = '~<p>\s*(?:<strong>)?\s*LUNARA\s+DEBRIEF\s*(?:</strong>)?\s*</p>~i';

        if ( ! preg_match( $marker_pattern, $content_html, $marker_match, PREG_OFFSET_CAPTURE ) ) {
            return $sections;
        }

        $start_offset = intval( $marker_match[0][1] );
        $before       = trim( substr( $content_html, 0, $start_offset ) );
        $tail         = substr( $content_html, $start_offset );
        $debrief_end  = null;

        if ( preg_match( '~<p>\s*<strong>\s*Pair\s+It\s+With\s*</strong>\s*</p>.*?(</ul>)~is', $tail, $pair_match, PREG_OFFSET_CAPTURE ) ) {
            $debrief_end = intval( $pair_match[1][1] ) + strlen( $pair_match[1][0] );
        } elseif ( preg_match( '~</ul>~i', $tail, $list_end_match, PREG_OFFSET_CAPTURE ) ) {
            $debrief_end = intval( $list_end_match[0][1] ) + strlen( $list_end_match[0][0] );
        }

        if ( null === $debrief_end ) {
            return $sections;
        }

        $debrief_html   = trim( substr( $tail, 0, $debrief_end ) );
        $postscript_html = trim( substr( $tail, $debrief_end ) );

        $debrief_html = preg_replace( $marker_pattern, '', $debrief_html, 1 );
        $debrief_html = lunara_enhance_review_debrief_html( $debrief_html );

        $sections['body']       = '' !== $before ? $before : $content_html;
        $sections['debrief']    = trim( (string) $debrief_html );
        $sections['postscript'] = trim( (string) $postscript_html );

        return $sections;
    }
}

/**
 * Build a longer excerpt for hero/feature review cards.
 */
if ( ! function_exists( 'lunara_get_review_archive_excerpt' ) ) {
function lunara_get_review_archive_excerpt( $post_id, $words = 28 ) {
    $post_id = intval( $post_id );
    $words   = max( 12, intval( $words ) );

    if ( $post_id <= 0 ) {
        return '';
    }

    if ( function_exists( 'lunara_get_review_card_pull_quote' ) ) {
        return lunara_get_review_card_pull_quote( $post_id, $words );
    }

    return function_exists( 'lunara_card_excerpt' )
        ? lunara_card_excerpt( $post_id, $words )
        : wp_trim_words( wp_strip_all_tags( get_post_field( 'post_content', $post_id ) ), $words, '...' );
}
}

/**
 * Render a lead or supporting review card for the archive shell.
 */
if ( ! function_exists( 'lunara_render_review_feature_card' ) ) {
    function lunara_render_review_feature_card( $post_id, $args = array() ) {
        $post_id = intval( $post_id );
        if ( $post_id <= 0 ) {
            return '';
        }

        $args = wp_parse_args(
            $args,
            array(
                'variant' => 'lead',
                'excerpt_words' => 30,
            )
        );

        $variant      = 'compact' === $args['variant'] ? 'compact' : 'lead';
        $score        = trim( (string) get_post_meta( $post_id, '_lunara_score', true ) );
        $excerpt      = lunara_get_review_archive_excerpt( $post_id, intval( $args['excerpt_words'] ) );
        $review_tt    = function_exists( 'lunara_get_review_imdb_title_id' ) ? lunara_get_review_imdb_title_id( $post_id ) : '';
        $image_data   = lunara_get_review_card_image_data(
            $post_id,
            'large',
            array(
                'class'    => 'lunara-review-feature-image',
                'loading'  => 'lazy',
                'decoding' => 'async',
                'sizes'    => '(max-width: 900px) 100vw, 48vw',
            )
        );
        $ledger_pill  = '';
        $has_media    = ! empty( $image_data['html'] );
        $has_excerpt  = '' !== trim( (string) $excerpt );
        $card_classes = array(
            'lunara-review-feature-card',
            'is-' . $variant,
            $has_media ? 'has-review-media' : 'is-text-led',
            $has_excerpt ? 'has-review-quote' : 'has-no-review-quote',
        );

        if ( '' !== $review_tt && function_exists( 'lunara_get_oscar_ledger_counts' ) && function_exists( 'lunara_render_oscar_ledger_pill' ) ) {
            $ledger_pill = lunara_render_oscar_ledger_pill( $review_tt, lunara_get_oscar_ledger_counts( $review_tt ) );
        }

        ob_start();
        ?>
        <article class="<?php echo esc_attr( implode( ' ', array_filter( $card_classes ) ) ); ?>">
            <a class="lunara-review-feature-link" href="<?php echo esc_url( get_permalink( $post_id ) ); ?>">
                <?php if ( $has_media ) : ?>
                    <div class="lunara-review-feature-media">
                        <?php echo $image_data['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        <?php if ( '' !== $score ) : ?>
                            <span class="lunara-score-badge"><?php echo wp_kses_post( lunara_render_stars( $score ) ); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <div class="lunara-review-feature-copy">
                    <p class="lunara-home-section-kicker"><?php esc_html_e( 'Lunara Review', 'lunara-film' ); ?></p>
                    <?php if ( ! $has_media && '' !== $score ) : ?>
                        <span class="lunara-score-badge lunara-score-badge-inline"><?php echo wp_kses_post( lunara_render_stars( $score ) ); ?></span>
                    <?php endif; ?>
                    <h2 class="lunara-review-feature-title"><?php echo esc_html( get_the_title( $post_id ) ); ?></h2>
                    <?php if ( $has_excerpt ) : ?>
                        <p class="lunara-review-feature-excerpt lunara-review-feature-quote"><?php echo esc_html( $excerpt ); ?></p>
                    <?php endif; ?>
                    <div class="lunara-review-feature-footer">
                        <?php if ( '' !== $ledger_pill ) : ?>
                            <div class="lunara-review-feature-ledger"><?php echo wp_kses_post( $ledger_pill ); ?></div>
                        <?php endif; ?>
                        <span class="lunara-section-link"><?php esc_html_e( 'Read Review', 'lunara-film' ); ?></span>
                    </div>
                </div>
            </a>
        </article>
        <?php

        return ob_get_clean();
    }
}

/**
 * Normalize the IMDb title id attached to a review.
 */
if ( ! function_exists( 'lunara_get_review_imdb_title_id' ) ) {
    function lunara_get_review_imdb_title_id( $post_id ) {
        $post_id = intval( $post_id );
        if ( $post_id <= 0 ) {
            return '';
        }

        $raw = trim( (string) get_post_meta( $post_id, '_lunara_imdb_title_id', true ) );
        if ( '' === $raw ) {
            return '';
        }

        if ( preg_match( '/\btt\d{7,8}\b/i', $raw, $matches ) ) {
            return strtolower( $matches[0] );
        }

        if ( preg_match( '#imdb\.com/title/(tt\d{7,8})#i', $raw, $matches ) ) {
            return strtolower( $matches[1] );
        }

        return '';
    }
}

/**
 * Find Review categories intended for full spoiler-review companions.
 */
if ( ! function_exists( 'lunara_get_spoiler_review_category_ids' ) ) {
    function lunara_get_spoiler_review_category_ids() {
        $terms = get_terms(
            array(
                'taxonomy'   => 'category',
                'hide_empty' => false,
                'fields'     => 'all',
            )
        );

        if ( is_wp_error( $terms ) || empty( $terms ) ) {
            return array();
        }

        $ids = array();
        foreach ( $terms as $term ) {
            if ( ! $term instanceof WP_Term ) {
                continue;
            }

            $haystack = strtolower( $term->slug . ' ' . $term->name );
            if ( false !== strpos( $haystack, 'spoiler' ) ) {
                $ids[] = (int) $term->term_id;
            }
        }

        return array_values( array_unique( array_filter( $ids ) ) );
    }
}

/**
 * Determine whether a Review is explicitly marked as a full spoiler companion.
 */
if ( ! function_exists( 'lunara_is_full_spoiler_review' ) ) {
    function lunara_is_full_spoiler_review( $post_id ) {
        $post_id = intval( $post_id );
        if ( $post_id <= 0 ) {
            return false;
        }

        return 'full_spoiler' === get_post_meta( $post_id, '_lunara_review_spoiler_mode', true );
    }
}

/**
 * Resolve the full spoiler review link for a spoiler-free Review.
 */
if ( ! function_exists( 'lunara_get_linked_spoiler_review' ) ) {
    function lunara_get_linked_spoiler_review( $post_id ) {
        $post_id = intval( $post_id );
        if ( $post_id <= 0 ) {
            return array();
        }

        if ( function_exists( 'lunara_is_full_spoiler_review' ) && lunara_is_full_spoiler_review( $post_id ) ) {
            return array();
        }

        $manual_url   = trim( (string) get_post_meta( $post_id, '_lunara_spoiler_review_url', true ) );
        $manual_label = trim( (string) get_post_meta( $post_id, '_lunara_spoiler_review_label', true ) );

        if ( '' !== $manual_url ) {
            return array(
                'url'     => esc_url_raw( $manual_url ),
                'label'   => '' !== $manual_label ? $manual_label : __( 'Read the full spoiler review', 'lunara-film' ),
                'title'   => '',
                'source'  => 'manual',
                'post_id' => 0,
            );
        }

        $review_tt = function_exists( 'lunara_get_review_imdb_title_id' ) ? lunara_get_review_imdb_title_id( $post_id ) : '';
        if ( '' === $review_tt ) {
            return array();
        }

        $candidate_ids = get_posts(
            array(
                'post_type'              => 'review',
                'post_status'            => 'publish',
                'posts_per_page'         => 6,
                'post__not_in'           => array( $post_id ),
                'fields'                 => 'ids',
                'orderby'                => 'date',
                'order'                  => 'DESC',
                'ignore_sticky_posts'    => true,
                'no_found_rows'          => true,
                'update_post_meta_cache' => false,
                'update_post_term_cache' => false,
                'meta_query'             => array(
                    array(
                        'key'     => '_lunara_imdb_title_id',
                        'value'   => $review_tt,
                        'compare' => '=',
                    ),
                    array(
                        'key'     => '_lunara_review_spoiler_mode',
                        'value'   => 'full_spoiler',
                        'compare' => '=',
                    ),
                ),
            )
        );

        $spoiler_category_ids = lunara_get_spoiler_review_category_ids();
        if ( empty( $candidate_ids ) && ! empty( $spoiler_category_ids ) ) {
            $candidate_ids = get_posts(
                array(
                    'post_type'              => 'review',
                    'post_status'            => 'publish',
                    'posts_per_page'         => 12,
                    'post__not_in'           => array( $post_id ),
                    'category__in'           => $spoiler_category_ids,
                    'fields'                 => 'ids',
                    'orderby'                => 'date',
                    'order'                  => 'DESC',
                    'ignore_sticky_posts'    => true,
                    'no_found_rows'          => true,
                    'update_post_meta_cache' => false,
                    'update_post_term_cache' => false,
                )
            );
        }

        foreach ( array_map( 'intval', is_array( $candidate_ids ) ? $candidate_ids : array() ) as $candidate_id ) {
            if ( $candidate_id <= 0 || $candidate_id === $post_id ) {
                continue;
            }

            if ( $review_tt !== lunara_get_review_imdb_title_id( $candidate_id ) ) {
                continue;
            }

            return array(
                'url'     => get_permalink( $candidate_id ),
                'label'   => '' !== $manual_label ? $manual_label : __( 'Read the full spoiler review', 'lunara-film' ),
                'title'   => get_the_title( $candidate_id ),
                'source'  => 'auto',
                'post_id' => $candidate_id,
            );
        }

        return array();
    }
}

/**
 * Render the active top-of-review spoiler shield for full spoiler companion files.
 */
if ( ! function_exists( 'lunara_render_full_spoiler_review_warning' ) ) {
    function lunara_render_full_spoiler_review_warning( $post_id ) {
        if ( ! function_exists( 'lunara_is_full_spoiler_review' ) || ! lunara_is_full_spoiler_review( $post_id ) ) {
            return '';
        }

        $review_tt      = function_exists( 'lunara_get_review_imdb_title_id' ) ? lunara_get_review_imdb_title_id( $post_id ) : '';
        $related_link   = '';
        $spoiler_free   = null;
        $candidate_args = array(
            'post_type'              => 'review',
            'post_status'            => 'publish',
            'posts_per_page'         => 8,
            'post__not_in'           => array( intval( $post_id ) ),
            'fields'                 => 'ids',
            'orderby'                => 'date',
            'order'                  => 'DESC',
            'ignore_sticky_posts'    => true,
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'meta_query'             => array(
                array(
                    'key'     => '_lunara_review_spoiler_mode',
                    'value'   => 'full_spoiler',
                    'compare' => '!=',
                ),
            ),
        );

        if ( '' !== $review_tt ) {
            $candidate_args['meta_query'] = array(
                'relation' => 'AND',
                array(
                    'key'     => '_lunara_imdb_title_id',
                    'value'   => $review_tt,
                    'compare' => '=',
                ),
                array(
                    'relation' => 'OR',
                    array(
                        'key'     => '_lunara_review_spoiler_mode',
                        'value'   => 'full_spoiler',
                        'compare' => '!=',
                    ),
                    array(
                        'key'     => '_lunara_review_spoiler_mode',
                        'compare' => 'NOT EXISTS',
                    ),
                ),
            );
        }

        $candidate_ids = get_posts( $candidate_args );
        foreach ( array_map( 'intval', is_array( $candidate_ids ) ? $candidate_ids : array() ) as $candidate_id ) {
            if ( $candidate_id <= 0 || ( function_exists( 'lunara_is_full_spoiler_review' ) && lunara_is_full_spoiler_review( $candidate_id ) ) ) {
                continue;
            }

            $spoiler_free = $candidate_id;
            break;
        }

        if ( $spoiler_free ) {
            $related_link = sprintf(
                '<a class="lunara-full-spoiler-warning-link" href="%s">%s</a>',
                esc_url( get_permalink( $spoiler_free ) ),
                esc_html__( 'Read the spoiler-free review instead', 'lunara-film' )
            );
        }

        ob_start();
        ?>
        <aside
            id="lunara-spoiler-shield-<?php echo esc_attr( intval( $post_id ) ); ?>"
            class="lunara-full-spoiler-warning lunara-full-spoiler-shield"
            role="note"
            aria-label="<?php esc_attr_e( 'Full spoiler warning', 'lunara-film' ); ?>"
            data-lunara-spoiler-shield
            data-lunara-spoiler-post="<?php echo esc_attr( intval( $post_id ) ); ?>"
        >
            <div class="lunara-full-spoiler-warning-copy lunara-full-spoiler-shield-copy">
                <p class="lunara-full-spoiler-warning-kicker lunara-full-spoiler-shield-kicker"><?php esc_html_e( 'Full Spoiler Review', 'lunara-film' ); ?></p>
                <h2 class="lunara-full-spoiler-warning-title lunara-full-spoiler-shield-title"><?php esc_html_e( 'This file discusses the ending openly.', 'lunara-film' ); ?></h2>
                <p class="lunara-full-spoiler-warning-text lunara-full-spoiler-shield-text"><?php esc_html_e( 'Continue only if you are ready for plot revelations, final images, deaths, reversals, and the complete critical argument.', 'lunara-film' ); ?></p>
            </div>
            <div class="lunara-full-spoiler-shield-actions">
                <button
                    class="lunara-full-spoiler-shield-button"
                    type="button"
                    data-lunara-spoiler-reveal
                    aria-controls="lunara-spoiler-protected-<?php echo esc_attr( intval( $post_id ) ); ?>"
                >
                    <?php esc_html_e( 'I understand. Reveal the spoiler review.', 'lunara-film' ); ?>
                </button>
                <?php echo $related_link; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>
        </aside>
        <?php

        return trim( ob_get_clean() );
    }
}

/**
 * Render a compact retention bridge from a spoiler-free Review to its full spoiler companion.
 */
if ( ! function_exists( 'lunara_render_spoiler_review_bridge' ) ) {
    function lunara_render_spoiler_review_bridge( $post_id ) {
        $link = lunara_get_linked_spoiler_review( $post_id );
        if ( empty( $link['url'] ) ) {
            return '';
        }

        $title_line = '';
        if ( ! empty( $link['title'] ) ) {
            $title_line = sprintf(
                '<p class="lunara-spoiler-review-bridge-source">%s</p>',
                esc_html( sprintf( __( 'Companion file: %s', 'lunara-film' ), $link['title'] ) )
            );
        }

        ob_start();
        ?>
        <aside class="lunara-spoiler-review-bridge" aria-label="<?php esc_attr_e( 'Full spoiler review', 'lunara-film' ); ?>">
            <div class="lunara-spoiler-review-bridge-copy">
                <p class="lunara-spoiler-review-bridge-kicker"><?php esc_html_e( 'Spoiler File', 'lunara-film' ); ?></p>
                <h2 class="lunara-spoiler-review-bridge-title"><?php esc_html_e( 'Ready for the full breakdown?', 'lunara-film' ); ?></h2>
                <p class="lunara-spoiler-review-bridge-text"><?php esc_html_e( 'For readers who have seen the film, continue into the ending, reversals, and full argument without pulling the spoiler-free review out of shape.', 'lunara-film' ); ?></p>
                <?php echo $title_line; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>
            <a class="lunara-spoiler-review-bridge-link" href="<?php echo esc_url( $link['url'] ); ?>">
                <?php echo esc_html( ! empty( $link['label'] ) ? $link['label'] : __( 'Read the full spoiler review', 'lunara-film' ) ); ?>
            </a>
        </aside>
        <?php

        return trim( ob_get_clean() );
    }
}

/**
 * Render Lunara-owned share controls for single Reviews.
 */
if ( ! function_exists( 'lunara_render_review_share_strip' ) ) {
    function lunara_render_review_share_strip( $post_id ) {
        $post_id = intval( $post_id );
        if ( $post_id <= 0 ) {
            return '';
        }

        $url   = get_permalink( $post_id );
        $title = trim( html_entity_decode( wp_strip_all_tags( get_the_title( $post_id ) ), ENT_QUOTES, get_bloginfo( 'charset' ) ) );
        if ( empty( $url ) || '' === $title ) {
            return '';
        }

        $share_text      = sprintf( __( '%s - Lunara Film', 'lunara-film' ), $title );
        $share_text_url  = rawurlencode( $share_text );
        $share_url       = rawurlencode( $url );
        $share_body      = rawurlencode( $share_text . "\n\n" . $url );
        $share_bluesky   = rawurlencode( $share_text . ' ' . $url );
        $share_platforms = array(
            array(
                'label' => __( 'X', 'lunara-film' ),
                'url'   => 'https://twitter.com/intent/tweet?text=' . $share_text_url . '&url=' . $share_url,
            ),
            array(
                'label' => __( 'Bluesky', 'lunara-film' ),
                'url'   => 'https://bsky.app/intent/compose?text=' . $share_bluesky,
            ),
            array(
                'label' => __( 'Facebook', 'lunara-film' ),
                'url'   => 'https://www.facebook.com/sharer/sharer.php?u=' . $share_url,
            ),
            array(
                'label' => __( 'Email', 'lunara-film' ),
                'url'   => 'mailto:?subject=' . $share_text_url . '&body=' . $share_body,
            ),
        );

        ob_start();
        ?>
        <aside class="lunara-review-share-strip" aria-label="<?php esc_attr_e( 'Share this review', 'lunara-film' ); ?>">
            <div class="lunara-review-share-strip-copy">
                <p class="lunara-review-share-strip-kicker"><?php esc_html_e( 'Share File', 'lunara-film' ); ?></p>
                <p class="lunara-review-share-strip-title"><?php esc_html_e( 'Put this review in circulation', 'lunara-film' ); ?></p>
            </div>
            <div class="lunara-review-share-strip-actions">
                <button class="lunara-review-share-link lunara-review-share-copy" type="button" data-lunara-copy-share data-share-url="<?php echo esc_url( $url ); ?>">
                    <?php esc_html_e( 'Copy Link', 'lunara-film' ); ?>
                </button>
                <?php foreach ( $share_platforms as $platform ) : ?>
                    <a class="lunara-review-share-link" href="<?php echo esc_url( $platform['url'] ); ?>" target="_blank" rel="noopener noreferrer nofollow">
                        <?php echo esc_html( $platform['label'] ); ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <p class="lunara-review-share-status" role="status" aria-live="polite"></p>
        </aside>
        <?php

        return trim( ob_get_clean() );
    }
}

/**
 * Query related reviews using director/year affinity first, then recent fallback.
 */
if ( ! function_exists( 'lunara_get_related_review_posts' ) ) {
    function lunara_get_related_review_posts( $post_id, $count = 4 ) {
        $post_id = intval( $post_id );
        $count   = max( 1, intval( $count ) );

        if ( $post_id <= 0 ) {
            return lunara_reviews_query_from_ids( array() );
        }

        $director = trim( (string) get_post_meta( $post_id, '_lunara_director', true ) );
        $year     = trim( (string) get_post_meta( $post_id, '_lunara_year', true ) );
        $ids      = array();

        $collect_ids = static function( $query_args ) use ( &$ids, $count, $post_id ) {
            if ( count( $ids ) >= $count ) {
                return;
            }

            $query_args = wp_parse_args(
                $query_args,
                array(
                    'post_type'              => 'review',
                    'post_status'            => 'publish',
                    'posts_per_page'         => max( 1, $count - count( $ids ) ),
                    'post__not_in'           => array_merge( array( $post_id ), $ids ),
                    'ignore_sticky_posts'    => true,
                    'fields'                 => 'ids',
                    'orderby'                => 'date',
                    'order'                  => 'DESC',
                    'no_found_rows'          => true,
                    'update_post_meta_cache' => false,
                    'update_post_term_cache' => false,
                )
            );

            $found_ids = get_posts( $query_args );
            foreach ( array_map( 'intval', is_array( $found_ids ) ? $found_ids : array() ) as $found_id ) {
                if ( $found_id > 0 && ! in_array( $found_id, $ids, true ) && $found_id !== $post_id ) {
                    $ids[] = $found_id;
                    if ( count( $ids ) >= $count ) {
                        break;
                    }
                }
            }
        };

        if ( '' !== $director ) {
            $collect_ids(
                array(
                    'meta_query' => array(
                        array(
                            'key'     => '_lunara_director',
                            'value'   => $director,
                            'compare' => '=',
                        ),
                    ),
                )
            );
        }

        if ( count( $ids ) < $count && '' !== $year ) {
            $collect_ids(
                array(
                    'meta_query' => array(
                        array(
                            'key'     => '_lunara_year',
                            'value'   => $year,
                            'compare' => '=',
                        ),
                    ),
                )
            );
        }

        if ( count( $ids ) < $count ) {
            $collect_ids( array() );
        }

        return lunara_reviews_query_from_ids( array_slice( $ids, 0, $count ) );
    }
}

/**
 * Render a review archive card.
 */
function lunara_render_review_grid_card( $post_id, $card_index = null ) {
    $post_id = intval( $post_id );
    if ( $post_id <= 0 ) {
        return '';
    }

    $has_position = null !== $card_index;
    $card_index   = max( 1, intval( $card_index ) );
    $score        = get_post_meta( $post_id, '_lunara_score', true );
    $quote        = lunara_get_review_card_pull_quote( $post_id, 26 );
    $updated      = lunara_get_review_card_modified_label( $post_id );
    $is_spoiler   = function_exists( 'lunara_is_full_spoiler_review' ) && lunara_is_full_spoiler_review( $post_id );
    $review_tt    = function_exists( 'lunara_get_review_imdb_title_id' ) ? lunara_get_review_imdb_title_id( $post_id ) : '';
    $thumb_attrs  = array(
        'class'    => 'lunara-review-grid-poster',
        'loading'  => $has_position && $card_index <= 2 ? 'eager' : 'lazy',
        'decoding' => 'async',
        'sizes'    => '(max-width: 520px) 46vw, (max-width: 900px) 42vw, (max-width: 1180px) 30vw, 340px',
    );
    $ledger_pill  = '';

    if ( $has_position && 1 === $card_index ) {
        $thumb_attrs['fetchpriority'] = 'high';
    }

    $image_data      = lunara_get_review_card_image_data( $post_id, 'lunara-review-card', $thumb_attrs );
    $thumb_url       = isset( $image_data['url'] ) ? (string) $image_data['url'] : '';
    $has_thumb_html  = ! empty( $image_data['html'] );
    $use_fallback_bg = '' !== $thumb_url && ! $has_thumb_html;
    $has_media       = $has_thumb_html || $use_fallback_bg;
    $has_quote       = '' !== trim( (string) $quote );
    $card_classes    = array(
        'lunara-review-grid-card',
        'lunara-review-archive-card',
        $has_media ? 'has-review-media' : 'is-text-led',
        $has_quote ? 'has-review-quote' : 'has-no-review-quote',
        $is_spoiler ? 'is-full-spoiler-review' : '',
    );

    if ( '' !== $review_tt && function_exists( 'lunara_get_oscar_ledger_counts' ) && function_exists( 'lunara_render_oscar_ledger_pill' ) ) {
        $ledger_pill = lunara_render_oscar_ledger_pill( $review_tt, lunara_get_oscar_ledger_counts( $review_tt ) );
    }

    ob_start();
    ?>
    <article class="<?php echo esc_attr( implode( ' ', array_filter( $card_classes ) ) ); ?>">
        <a class="lunara-review-grid-link" href="<?php echo esc_url( get_permalink( $post_id ) ); ?>">
            <?php if ( $has_media ) : ?>
                <div class="lunara-review-grid-poster-wrap<?php echo $use_fallback_bg ? ' has-poster-bg has-fallback-bg' : ''; ?>"<?php if ( $use_fallback_bg ) : ?> style="background-image: url('<?php echo esc_url( $thumb_url ); ?>');"<?php endif; ?>>
                    <?php if ( $has_thumb_html ) : ?>
                        <?php echo $image_data['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    <?php endif; ?>
                    <?php if ( $score ) : ?>
                        <span class="lunara-score-badge"><?php echo wp_kses_post( lunara_render_stars( $score ) ); ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <div class="lunara-review-grid-copy">
                <p class="lunara-review-grid-kicker"><?php echo esc_html( $is_spoiler ? __( 'Full Spoiler Review', 'lunara-film' ) : __( 'Lunara Review', 'lunara-film' ) ); ?></p>
                <?php if ( ! $has_media && $score ) : ?>
                    <span class="lunara-score-badge lunara-score-badge-inline"><?php echo wp_kses_post( lunara_render_stars( $score ) ); ?></span>
                <?php endif; ?>
                <?php if ( function_exists( 'lunara_render_trailer_card_badge' ) ) : ?>
                    <?php echo lunara_render_trailer_card_badge( $post_id, 'review-card' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <?php endif; ?>
                <h3 class="lunara-review-grid-title"><?php echo esc_html( get_the_title( $post_id ) ); ?></h3>
                <?php if ( $has_quote ) : ?>
                    <p class="lunara-review-grid-excerpt lunara-review-grid-quote"><?php echo esc_html( $quote ); ?></p>
                <?php endif; ?>
                <?php if ( '' !== $updated ) : ?>
                    <p class="lunara-review-grid-updated"><?php echo esc_html( $updated ); ?></p>
                <?php endif; ?>
            </div>
        </a>
        <?php if ( '' !== $ledger_pill ) : ?>
            <div class="lunara-review-grid-footer">
                <div class="lunara-review-grid-ledger"><?php echo wp_kses_post( $ledger_pill ); ?></div>
            </div>
        <?php endif; ?>
    </article>
    <?php

    return ob_get_clean();
}

/**
 * Build a short taxonomy line for standard post cards.
 */
function lunara_get_dispatch_category_line( $post_id ) {
    $terms = get_the_terms( $post_id, 'category' );
    if ( ! is_array( $terms ) ) {
        return '';
    }

    $labels = array();

    foreach ( $terms as $term ) {
        if ( ! ( $term instanceof WP_Term ) || 'uncategorized' === $term->slug ) {
            continue;
        }

        $labels[] = trim( (string) $term->name );
    }

    $labels = array_values( array_filter( array_unique( $labels ) ) );

    return implode( ' / ', array_slice( $labels, 0, 2 ) );
}

/**
 * Estimate reading time for a standard editorial post.
 */
function lunara_get_post_reading_time( $post_id ) {
    $post_id = intval( $post_id );
    if ( $post_id <= 0 ) {
        return '';
    }

    $content    = wp_strip_all_tags( (string) get_post_field( 'post_content', $post_id ) );
    $word_count = str_word_count( $content );
    if ( $word_count <= 0 ) {
        return '';
    }

    $minutes = max( 1, (int) ceil( $word_count / 225 ) );

    /* translators: %d: Reading time in minutes. */
    return sprintf( _n( '%d min read', '%d mins read', $minutes, 'lunara-film' ), $minutes );
}

/**
 * Build a compact label list for a post's tags.
 */
function lunara_get_post_tag_line( $post_id, $limit = 4 ) {
    $terms = get_the_terms( $post_id, 'post_tag' );
    if ( ! is_array( $terms ) ) {
        return '';
    }

    $labels = array();

    foreach ( $terms as $term ) {
        if ( ! ( $term instanceof WP_Term ) ) {
            continue;
        }

        $labels[] = trim( (string) $term->name );
    }

    $labels = array_values( array_filter( array_unique( $labels ) ) );

    return implode( ' / ', array_slice( $labels, 0, max( 1, intval( $limit ) ) ) );
}

if ( ! function_exists( 'lunara_get_editorial_archive_sort_options' ) ) {
    /**
     * Available public sort modes for journal/editorial archives.
     */
    function lunara_get_editorial_archive_sort_options() {
        return array(
            'date_desc'     => __( 'Newest Filed', 'lunara-film' ),
            'date_asc'      => __( 'Oldest Filed', 'lunara-film' ),
            'modified_desc' => __( 'Recently Updated', 'lunara-film' ),
        );
    }
}

if ( ! function_exists( 'lunara_get_editorial_archive_sort' ) ) {
    /**
     * Resolve the current journal/editorial archive sort from the query string.
     */
    function lunara_get_editorial_archive_sort() {
        $sort    = isset( $_GET['sort'] ) ? sanitize_key( wp_unslash( $_GET['sort'] ) ) : '';
        $options = lunara_get_editorial_archive_sort_options();

        return isset( $options[ $sort ] ) ? $sort : 'date_desc';
    }
}

if ( ! function_exists( 'lunara_get_editorial_archive_sort_label' ) ) {
    /**
     * Human label for the current journal/editorial archive sort.
     */
    function lunara_get_editorial_archive_sort_label( $sort = '' ) {
        $sort    = $sort ? sanitize_key( (string) $sort ) : lunara_get_editorial_archive_sort();
        $options = lunara_get_editorial_archive_sort_options();

        return isset( $options[ $sort ] ) ? (string) $options[ $sort ] : (string) $options['date_desc'];
    }
}

if ( ! function_exists( 'lunara_apply_editorial_archive_sort_args' ) ) {
    /**
     * Apply journal/editorial archive sort mode to a query args array.
     */
    function lunara_apply_editorial_archive_sort_args( $query_args, $sort = '' ) {
        $sort = $sort ? sanitize_key( (string) $sort ) : lunara_get_editorial_archive_sort();

        switch ( $sort ) {
            case 'date_asc':
                $query_args['orderby'] = 'date';
                $query_args['order']   = 'ASC';
                break;

            case 'modified_desc':
                $query_args['orderby'] = 'modified';
                $query_args['order']   = 'DESC';
                break;

            case 'date_desc':
            default:
                $query_args['orderby'] = 'date';
                $query_args['order']   = 'DESC';
                break;
        }

        return $query_args;
    }
}

if ( ! function_exists( 'lunara_get_editorial_card_updated_label' ) ) {
    /**
     * Return a compact updated label when an editorial post was meaningfully
     * modified after its original publish date.
     */
    function lunara_get_editorial_card_updated_label( $post_id ) {
        $post_id      = intval( $post_id );
        $published_ts = (int) get_post_timestamp( $post_id, 'date' );
        $modified_ts  = (int) get_post_timestamp( $post_id, 'modified' );

        if ( $post_id <= 0 || $modified_ts <= 0 ) {
            return '';
        }

        if ( $published_ts > 0 && gmdate( 'Y-m-d', $published_ts ) === gmdate( 'Y-m-d', $modified_ts ) ) {
            return '';
        }

        return sprintf(
            /* translators: %s: modified date */
            __( 'Updated %s', 'lunara-film' ),
            get_the_modified_date( 'F j, Y', $post_id )
        );
    }
}

/**
 * Query related editorial posts by shared categories.
 */
function lunara_get_related_dispatch_posts( $post_id, $count = 3 ) {
    $post_id = intval( $post_id );
    $count   = max( 1, intval( $count ) );
    $cat_ids = wp_get_post_categories( $post_id, array( 'fields' => 'ids' ) );

    $query_args = array(
        'post_type'              => 'post',
        'post_status'            => 'publish',
        'posts_per_page'         => $count,
        'post__not_in'           => array( $post_id ),
        'ignore_sticky_posts'    => true,
        'no_found_rows'          => true,
        'update_post_meta_cache' => true,
        'update_post_term_cache' => true,
        'orderby'                => 'date',
        'order'                  => 'DESC',
    );

    if ( ! empty( $cat_ids ) ) {
        $query_args['category__in'] = array_map( 'intval', $cat_ids );
    } else {
        $dispatch_slugs = lunara_get_dispatch_category_slugs();
        if ( ! empty( $dispatch_slugs ) ) {
            $query_args['tax_query'] = array(
                array(
                    'taxonomy' => 'category',
                    'field'    => 'slug',
                    'terms'    => $dispatch_slugs,
                    'operator' => 'IN',
                ),
            );
        }
    }

    return new WP_Query( $query_args );
}

/**
 * Render a standard post card for the editorial archive.
 */
function lunara_render_dispatch_archive_card( $post_id, $featured = false ) {
    $post_id        = intval( $post_id );
    $featured       = (bool) $featured;
    $type_label     = lunara_get_dispatch_type_label( $post_id );
    $type_slug      = lunara_get_dispatch_type_slug( $post_id );
    $category_line  = lunara_get_dispatch_category_line( $post_id );
    $updated_label  = lunara_get_editorial_card_updated_label( $post_id );
    $excerpt_length = $featured ? 40 : 22;
    $excerpt        = function_exists( 'lunara_card_excerpt' )
        ? lunara_card_excerpt( $post_id, $excerpt_length )
        : wp_trim_words( get_the_excerpt( $post_id ), $excerpt_length );

    ob_start();

    if ( $featured ) :
        ?>
        <article class="<?php echo esc_attr( 'lunara-dispatch-lead lunara-archive-lead-card lunara-dispatch-type-card is-' . sanitize_html_class( $type_slug ) ); ?>">
            <a class="lunara-dispatch-lead-link" href="<?php echo esc_url( get_permalink( $post_id ) ); ?>">
                <div class="lunara-dispatch-lead-media">
                    <?php if ( has_post_thumbnail( $post_id ) ) : ?>
                        <?php echo get_the_post_thumbnail( $post_id, 'large', array( 'class' => 'lunara-dispatch-lead-image', 'loading' => 'lazy' ) ); ?>
                    <?php else : ?>
                        <div class="lunara-dispatch-lead-placeholder"><?php echo esc_html( $type_label ); ?></div>
                    <?php endif; ?>
                </div>
                <div class="lunara-dispatch-lead-copy">
                    <p class="lunara-dispatch-type"><?php echo esc_html( $type_label ); ?></p>
                    <h2 class="lunara-dispatch-lead-title"><?php echo esc_html( get_the_title( $post_id ) ); ?></h2>
                    <?php if ( '' !== $excerpt ) : ?>
                        <p class="lunara-dispatch-lead-excerpt"><?php echo esc_html( $excerpt ); ?></p>
                    <?php endif; ?>
                    <div class="lunara-dispatch-lead-meta">
                        <span><?php echo esc_html( get_the_date( 'F j, Y', $post_id ) ); ?></span>
                        <?php if ( '' !== $category_line ) : ?>
                            <span><?php echo esc_html( $category_line ); ?></span>
                        <?php endif; ?>
                        <?php if ( '' !== $updated_label ) : ?>
                            <span class="lunara-dispatch-archive-updated"><?php echo esc_html( $updated_label ); ?></span>
                        <?php endif; ?>
                        <span class="lunara-dispatch-meta-link"><?php esc_html_e( 'Read or listen', 'lunara-film' ); ?></span>
                    </div>
                </div>
            </a>
        </article>
        <?php
    else :
        ?>
        <article class="<?php echo esc_attr( 'lunara-dispatch-archive-card lunara-dispatch-type-card is-' . sanitize_html_class( $type_slug ) ); ?>">
            <a class="lunara-dispatch-archive-link" href="<?php echo esc_url( get_permalink( $post_id ) ); ?>">
                <div class="lunara-dispatch-archive-thumb-wrap">
                    <?php if ( has_post_thumbnail( $post_id ) ) : ?>
                        <?php echo get_the_post_thumbnail( $post_id, 'medium_large', array( 'class' => 'lunara-dispatch-archive-thumb', 'loading' => 'lazy' ) ); ?>
                    <?php else : ?>
                        <div class="lunara-dispatch-rail-thumb-placeholder"><?php echo esc_html( $type_label ); ?></div>
                    <?php endif; ?>
                </div>
                <div class="lunara-dispatch-archive-copy">
                    <p class="lunara-dispatch-type"><?php echo esc_html( $type_label ); ?></p>
                    <h3 class="lunara-dispatch-archive-title"><?php echo esc_html( get_the_title( $post_id ) ); ?></h3>
                    <?php if ( '' !== $excerpt ) : ?>
                        <p class="lunara-dispatch-archive-excerpt"><?php echo esc_html( $excerpt ); ?></p>
                    <?php endif; ?>
                    <p class="lunara-dispatch-archive-meta">
                        <span><?php echo esc_html( get_the_date( 'F j, Y', $post_id ) ); ?></span>
                        <?php if ( '' !== $category_line ) : ?>
                            <span><?php echo esc_html( $category_line ); ?></span>
                        <?php endif; ?>
                        <?php if ( '' !== $updated_label ) : ?>
                            <span class="lunara-dispatch-archive-updated"><?php echo esc_html( $updated_label ); ?></span>
                        <?php endif; ?>
                    </p>
                </div>
            </a>
        </article>
        <?php
    endif;

    return ob_get_clean();
}

/**
 * Normalize posts pulled from either the global loop or an explicit query.
 */
if ( ! function_exists( 'lunara_get_loop_posts' ) ) {
    function lunara_get_loop_posts( $query = null ) {
        if ( $query instanceof WP_Query ) {
            return is_array( $query->posts ) ? $query->posts : array();
        }

        global $wp_query;

        return ( isset( $wp_query ) && $wp_query instanceof WP_Query && is_array( $wp_query->posts ) )
            ? $wp_query->posts
            : array();
    }
}

/**
 * Shared editorial archive shell used by the posts index and editorial tax archives.
 */
if ( ! function_exists( 'lunara_render_editorial_archive_shell' ) ) {
    function lunara_render_editorial_archive_shell( $args = array() ) {
        $defaults = array(
            'classes'           => 'lunara-editorial-archive-page',
            'kicker'            => __( 'Archive', 'lunara-film' ),
            'title'             => __( 'Archive', 'lunara-film' ),
            'copy'              => '',
            'posts'             => array(),
            'empty_title'       => __( 'Nothing has been filed in this archive yet.', 'lunara-film' ),
            'empty_copy'        => '',
            'copy_words'        => 42,
            'pagination'        => paginate_links(),
            'overview_kicker'   => __( 'At A Glance', 'lunara-film' ),
            'overview_label'    => __( 'Coverage Focus', 'lunara-film' ),
            'source_label'      => __( 'Editorial lane', 'lunara-film' ),
            'overview_lines'    => array(),
            'lead_rail_kicker'  => __( 'In Rotation', 'lunara-film' ),
            'lead_rail_title'   => __( 'What The Archive Is Holding Beside The Lead', 'lunara-film' ),
            'lead_rail_copy'    => '',
            'run_kicker'        => __( 'Archive Run', 'lunara-film' ),
            'run_title'         => __( 'More From The Archive', 'lunara-film' ),
            'run_copy'          => '',
            'empty_note_kicker' => __( 'What Lives Here', 'lunara-film' ),
            'empty_note_title'  => __( 'Dispatches, reactions, essays, and signal worth following.', 'lunara-film' ),
            'empty_note_copy'   => '',
            'current_sort'      => lunara_get_editorial_archive_sort(),
            'sort_options'      => lunara_get_editorial_archive_sort_options(),
        );
        $args = wp_parse_args( $args, $defaults );

        $posts          = array_values( array_filter( (array) $args['posts'], static function ( $post_item ) {
            return $post_item instanceof WP_Post;
        } ) );
        $copy            = '';
        $classes         = trim( 'site-main lunara-archive-page ' . (string) $args['classes'] );
        $lead_post       = ! empty( $posts ) ? array_shift( $posts ) : null;
        $support_posts   = array_slice( $posts, 0, 2 );
        $remaining_posts = array_slice( $posts, 2 );
        $visible_count   = count( $posts ) + ( $lead_post instanceof WP_Post ? 1 : 0 );
        $current_sort    = sanitize_key( (string) $args['current_sort'] );
        $sort_options    = is_array( $args['sort_options'] ) ? $args['sort_options'] : lunara_get_editorial_archive_sort_options();
        $has_posts       = $lead_post instanceof WP_Post;
        $classes        .= $has_posts ? ' lunara-editorial-archive-has-posts' : ' lunara-editorial-archive-is-empty';
        $archive_mode    = $lead_post instanceof WP_Post
            ? ( ! empty( $remaining_posts ) ? __( 'Spotlight / Supporting / Archive Run', 'lunara-film' ) : __( 'Spotlight / Supporting', 'lunara-film' ) )
            : __( 'Standby', 'lunara-film' );
        $total_count     = 0;
        $show_hero       = function_exists( 'lunara_news_archive_live_section_is_enabled' ) ? lunara_news_archive_live_section_is_enabled( 'hero' ) : true;
        $show_spotlight  = function_exists( 'lunara_news_archive_live_section_is_enabled' ) ? lunara_news_archive_live_section_is_enabled( 'spotlight' ) : true;
        $show_run        = function_exists( 'lunara_news_archive_live_section_is_enabled' ) ? lunara_news_archive_live_section_is_enabled( 'run' ) : true;
        $show_pagination = function_exists( 'lunara_news_archive_live_section_is_enabled' ) ? lunara_news_archive_live_section_is_enabled( 'pagination' ) : true;
        $show_intro      = function_exists( 'lunara_news_archive_empty_section_is_enabled' ) ? lunara_news_archive_empty_section_is_enabled( 'intro' ) : true;
        $show_standby    = function_exists( 'lunara_news_archive_empty_section_is_enabled' ) ? lunara_news_archive_empty_section_is_enabled( 'standby' ) : true;
        $standby_card_order = function_exists( 'lunara_get_news_archive_standby_card_order_map' ) ? lunara_get_news_archive_standby_card_order_map() : array();

        global $wp_query;

        if ( isset( $wp_query ) && $wp_query instanceof WP_Query ) {
            $total_count = intval( $wp_query->found_posts );
        }

        if ( $total_count <= 0 ) {
            $total_count = $visible_count;
        }

        $sort_base_url = remove_query_arg( array( 'sort', 'paged' ), get_pagenum_link( 1 ) );
        $sort_label    = lunara_get_editorial_archive_sort_label( $current_sort );

        $overview_lines = array_values( array_filter( (array) $args['overview_lines'], static function ( $line ) {
            return is_array( $line ) && ! empty( $line['label'] ) && isset( $line['value'] );
        } ) );

        if ( empty( $overview_lines ) ) {
            $overview_lines = array(
                array(
                    'label' => __( 'Total Filed', 'lunara-film' ),
                    'value' => number_format_i18n( $total_count ),
                ),
                array(
                    'label' => __( 'Visible Now', 'lunara-film' ),
                    'value' => number_format_i18n( $visible_count ),
                ),
                array(
                    'label' => __( 'Sorted By', 'lunara-film' ),
                    'value' => $sort_label,
                ),
                array(
                    'label' => __( 'Page Shape', 'lunara-film' ),
                    'value' => $archive_mode,
                ),
                array(
                    'label' => (string) $args['overview_label'],
                    'value' => (string) $args['source_label'],
                ),
            );
        }

        ob_start();
        ?>
        <main id="primary" class="<?php echo esc_attr( $classes ); ?>">
            <?php if ( $show_hero ) : ?>
            <section class="lunara-home-section lunara-archive-hero lunara-editorial-archive-slot-hero" data-lunara-section="hero">
                <div class="lunara-editorial-archive-hero-shell">
                    <div class="lunara-editorial-archive-hero-copy-wrap">
                        <p class="lunara-archive-hero-kicker"><?php echo esc_html( $args['kicker'] ); ?></p>
                        <h1 class="lunara-archive-hero-title"><?php echo esc_html( $args['title'] ); ?></h1>
                        <?php if ( '' !== $copy ) : ?>
                            <p class="lunara-archive-hero-copy"><?php echo esc_html( wp_trim_words( $copy, max( 12, intval( $args['copy_words'] ) ) ) ); ?></p>
                        <?php endif; ?>
                    </div>
                    <aside class="lunara-editorial-archive-debrief" aria-label="<?php esc_attr_e( 'Editorial archive summary', 'lunara-film' ); ?>">
                        <p class="lunara-editorial-archive-debrief-kicker"><?php echo esc_html( $args['overview_kicker'] ); ?></p>
                        <ul class="lunara-editorial-archive-debrief-list">
                            <?php foreach ( $overview_lines as $line ) : ?>
                                <li>
                                    <strong><?php echo esc_html( (string) $line['label'] ); ?></strong>
                                    <span><?php echo esc_html( (string) $line['value'] ); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </aside>
                </div>
            </section>
            <?php endif; ?>

            <section class="lunara-home-section lunara-editorial-archive-shell">
                <?php if ( ! empty( $sort_options ) ) : ?>
                    <div class="lunara-editorial-archive-toolbar">
                        <div class="lunara-home-section-head lunara-editorial-archive-toolbar-head">
                            <div>
                                <p class="lunara-home-section-kicker"><?php esc_html_e( 'Archive Order', 'lunara-film' ); ?></p>
                                <h2 class="lunara-section-title"><?php esc_html_e( 'Filed Chronology Or Real Editing Activity', 'lunara-film' ); ?></h2>
                            </div>
                        </div>
                        <div class="lunara-archive-sort" aria-label="<?php esc_attr_e( 'Sort archive', 'lunara-film' ); ?>">
                            <?php foreach ( $sort_options as $sort_key => $sort_option_label ) : ?>
                                <?php
                                $is_active = $sort_key === $current_sort;
                                $sort_url  = 'date_desc' === $sort_key ? $sort_base_url : add_query_arg( 'sort', rawurlencode( $sort_key ), $sort_base_url );
                                ?>
                                <a class="lunara-archive-sort-link <?php echo $is_active ? 'is-active' : ''; ?>" href="<?php echo esc_url( $sort_url ); ?>"<?php echo $is_active ? ' aria-current="page"' : ''; ?>>
                                    <?php echo esc_html( $sort_option_label ); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if ( $lead_post instanceof WP_Post ) : ?>
                    <?php if ( $show_spotlight ) : ?>
                    <div class="lunara-editorial-archive-spotlight lunara-editorial-archive-slot-spotlight" data-lunara-section="spotlight">
                        <?php echo lunara_render_dispatch_archive_card( $lead_post->ID, true ); ?>

                        <?php if ( ! empty( $support_posts ) ) : ?>
                            <div class="lunara-editorial-archive-rail">
                                <div class="lunara-editorial-archive-rail-shell">
                                    <p class="lunara-home-section-kicker"><?php echo esc_html( $args['lead_rail_kicker'] ); ?></p>
                                    <h2 class="lunara-section-title"><?php echo esc_html( $args['lead_rail_title'] ); ?></h2>
                                </div>
                                <?php foreach ( $support_posts as $post_item ) : ?>
                                    <?php echo lunara_render_dispatch_archive_card( $post_item->ID ); ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <?php if ( ! empty( $remaining_posts ) && $show_run ) : ?>
                        <div class="lunara-editorial-archive-slot-run" data-lunara-section="run">
                        <div class="lunara-home-section-head lunara-editorial-archive-run-head">
                            <div>
                                <p class="lunara-home-section-kicker"><?php echo esc_html( $args['run_kicker'] ); ?></p>
                                <h2 class="lunara-section-title"><?php echo esc_html( $args['run_title'] ); ?></h2>
                            </div>
                        </div>

                        <div class="lunara-dispatch-archive-grid lunara-editorial-archive-grid">
                            <?php foreach ( $remaining_posts as $post_item ) : ?>
                                <?php echo lunara_render_dispatch_archive_card( $post_item->ID ); ?>
                            <?php endforeach; ?>
                        </div>
                        </div>
                    <?php endif; ?>

                    <?php if ( ! empty( $args['pagination'] ) && $show_pagination ) : ?>
                        <div class="lunara-archive-pagination lunara-editorial-archive-slot-pagination" data-lunara-section="pagination">
                            <?php echo wp_kses_post( $args['pagination'] ); ?>
                        </div>
                    <?php endif; ?>
                <?php else : ?>
                    <?php if ( $show_intro ) : ?>
                    <div class="lunara-editorial-archive-empty-shell lunara-editorial-archive-slot-intro" data-lunara-section="intro">
                        <div class="lunara-archive-empty lunara-editorial-archive-empty">
                            <h2><?php echo esc_html( $args['empty_title'] ); ?></h2>
                            <?php if ( '' !== trim( (string) $args['empty_copy'] ) ) : ?>
                                <p><?php echo esc_html( $args['empty_copy'] ); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="lunara-editorial-archive-empty-note">
                            <p class="lunara-home-section-kicker"><?php echo esc_html( $args['empty_note_kicker'] ); ?></p>
                            <h2 class="lunara-section-title"><?php echo esc_html( $args['empty_note_title'] ); ?></h2>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ( $show_standby ) : ?>
                    <div class="lunara-news-archive-standby-shell lunara-editorial-archive-slot-standby" data-lunara-section="standby">
                        <div class="lunara-home-section-head lunara-news-archive-standby-head">
                            <div>
                                <p class="lunara-home-section-kicker"><?php esc_html_e( 'Stay On Signal', 'lunara-film' ); ?></p>
                                <h2 class="lunara-section-title"><?php esc_html_e( 'The publication is still alive around the dispatch desk.', 'lunara-film' ); ?></h2>
                            </div>
                        </div>
                        <div class="lunara-news-archive-standby-grid">
                            <a class="lunara-news-archive-standby-card" style="order:<?php echo esc_attr( intval( $standby_card_order['reviews'] ?? 1 ) ); ?>;" href="<?php echo esc_url( get_post_type_archive_link( 'review' ) ?: home_url( '/reviews/' ) ); ?>">
                                <p class="lunara-home-section-kicker"><?php esc_html_e( 'Criticism', 'lunara-film' ); ?></p>
                                <h3><?php esc_html_e( 'Browse The Review Archive', 'lunara-film' ); ?></h3>
                                <span class="lunara-section-link"><?php esc_html_e( 'Enter The Reviews', 'lunara-film' ); ?></span>
                            </a>
                            <a class="lunara-news-archive-standby-card" style="order:<?php echo esc_attr( intval( $standby_card_order['ledger'] ?? 2 ) ); ?>;" href="<?php echo esc_url( home_url( '/oscars/' ) ); ?>">
                                <p class="lunara-home-section-kicker"><?php esc_html_e( 'Ledger', 'lunara-film' ); ?></p>
                                <h3><?php esc_html_e( 'Step Into The Oscar Ledger', 'lunara-film' ); ?></h3>
                                <span class="lunara-section-link"><?php esc_html_e( 'Open The Ledger', 'lunara-film' ); ?></span>
                            </a>
                            <a class="lunara-news-archive-standby-card" style="order:<?php echo esc_attr( intval( $standby_card_order['home'] ?? 3 ) ); ?>;" href="<?php echo esc_url( home_url( '/' ) ); ?>">
                                <p class="lunara-home-section-kicker"><?php esc_html_e( 'Front Door', 'lunara-film' ); ?></p>
                                <h3><?php esc_html_e( 'Return To The Live Homepage', 'lunara-film' ); ?></h3>
                                <span class="lunara-section-link"><?php esc_html_e( 'Go To Lunara', 'lunara-film' ); ?></span>
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </section>
        </main>
        <?php

        return ob_get_clean();
    }
}

/**
 * Shared review archive shell used by review and director archives.
 */
if ( ! function_exists( 'lunara_render_review_archive_shell' ) ) {
    function lunara_render_review_archive_shell( $args = array() ) {
        $defaults = array(
            'classes'      => 'lunara-review-archive-page',
            'kicker'       => __( 'Criticism Desk', 'lunara-film' ),
            'title'        => __( 'Lunara Reviews', 'lunara-film' ),
            'copy'         => __( 'Spoiler-free criticism, full-spoiler companion files, festival finds, and the films that deserve a longer argument after the credits roll.', 'lunara-film' ),
            'posts'        => array(),
            'empty_title'  => __( 'No reviews yet.', 'lunara-film' ),
            'empty_copy'   => __( 'When new criticism is published, it will appear here automatically.', 'lunara-film' ),
            'copy_words'   => 42,
            'pagination'   => paginate_links(),
        );
        $args = wp_parse_args( $args, $defaults );

        $posts = array_values( array_filter( (array) $args['posts'], static function ( $post_item ) {
            return $post_item instanceof WP_Post;
        } ) );
        $base_classes        = trim( (string) $args['classes'] );
        $is_director_archive = false !== strpos( $base_classes, 'lunara-director-archive-page' );
        $copy            = trim( (string) $args['copy'] );
        if ( '' === $copy && ! $is_director_archive ) {
            $copy = (string) $defaults['copy'];
        }
        $classes         = trim( 'site-main lunara-archive-page ' . $base_classes );
        $total_reviews   = wp_count_posts( 'review' );
        $total_reviews   = isset( $total_reviews->publish ) ? intval( $total_reviews->publish ) : 0;
        $visible_count   = count( $posts );
        $latest_ts       = 0;
        foreach ( $posts as $post_item ) {
            $latest_ts = max( $latest_ts, (int) get_post_modified_time( 'U', true, $post_item ) );
        }
        $lead_post       = ! empty( $posts ) ? array_shift( $posts ) : null;
        $support_posts   = array_slice( $posts, 0, 2 );
        $remaining_posts = array_slice( $posts, 2 );
        $has_posts       = $lead_post instanceof WP_Post;
        $classes        .= $has_posts ? ' lunara-review-archive-has-posts' : ' lunara-review-archive-is-empty';
        $archive_mode    = $has_posts
            ? ( ! empty( $remaining_posts ) ? __( 'Lead / Support / Archive Run', 'lunara-film' ) : __( 'Lead / Support', 'lunara-film' ) )
            : __( 'Standby', 'lunara-film' );
        $current_sort    = isset( $args['current_sort'] ) ? sanitize_key( (string) $args['current_sort'] ) : lunara_get_review_archive_sort();
        $sort_options    = isset( $args['sort_options'] ) && is_array( $args['sort_options'] ) ? $args['sort_options'] : lunara_get_review_archive_sort_options();
        $section_order = function_exists( 'lunara_get_reviews_archive_section_order_map' )
            ? lunara_get_reviews_archive_section_order_map()
            : array();
        $show_hero      = function_exists( 'lunara_reviews_archive_section_is_enabled' )
            ? lunara_reviews_archive_section_is_enabled( 'hero' )
            : true;
        $show_grid      = function_exists( 'lunara_reviews_archive_section_is_enabled' )
            ? lunara_reviews_archive_section_is_enabled( 'grid' )
            : true;
        $show_pagination = function_exists( 'lunara_reviews_archive_section_is_enabled' )
            ? lunara_reviews_archive_section_is_enabled( 'pagination' )
            : true;
        $sort_base_url  = remove_query_arg( array( 'sort', 'paged' ), get_pagenum_link( 1 ) );
        $sort_label     = lunara_get_review_archive_sort_label( $current_sort );
        $latest_label   = $latest_ts > 0 ? wp_date( 'M j, Y', $latest_ts ) : __( 'Standby', 'lunara-film' );

        ob_start();
        ?>
        <main id="primary" class="<?php echo esc_attr( $classes ); ?>">
            <?php if ( $show_hero ) : ?>
            <section class="lunara-home-section lunara-archive-hero lunara-review-archive-hero lunara-review-archive-slot-hero" data-lunara-section="hero">
                <div class="lunara-review-archive-hero-shell">
                    <div class="lunara-review-archive-hero-copy-wrap">
                        <p class="lunara-archive-hero-kicker"><?php echo esc_html( $args['kicker'] ); ?></p>
                        <h1 class="lunara-archive-hero-title"><?php echo esc_html( $args['title'] ); ?></h1>
                        <?php if ( '' !== $copy ) : ?>
                            <p class="lunara-archive-hero-copy"><?php echo esc_html( wp_trim_words( $copy, max( 12, intval( $args['copy_words'] ) ) ) ); ?></p>
                        <?php endif; ?>
                    </div>
                    <aside class="lunara-review-archive-debrief" aria-label="<?php esc_attr_e( 'Review archive summary', 'lunara-film' ); ?>">
                        <p class="lunara-review-archive-debrief-kicker"><?php esc_html_e( 'Reviews Command', 'lunara-film' ); ?></p>
                        <ul class="lunara-review-archive-debrief-list">
                            <li>
                                <strong><?php esc_html_e( 'Archive Depth', 'lunara-film' ); ?></strong>
                                <span><?php echo esc_html( number_format_i18n( $total_reviews ) ); ?></span>
                            </li>
                            <li>
                                <strong><?php esc_html_e( 'Visible File', 'lunara-film' ); ?></strong>
                                <span><?php echo esc_html( number_format_i18n( $visible_count ) ); ?></span>
                            </li>
                            <li>
                                <strong><?php esc_html_e( 'Latest Update', 'lunara-film' ); ?></strong>
                                <span><?php echo esc_html( $latest_label ); ?></span>
                            </li>
                            <li>
                                <strong><?php esc_html_e( 'Current Order', 'lunara-film' ); ?></strong>
                                <span><?php echo esc_html( $sort_label ); ?></span>
                            </li>
                        </ul>
                        <?php if ( ! $is_director_archive ) : ?>
                            <div class="lunara-review-archive-hero-actions">
                                <a href="#lunara-review-archive-run"><?php esc_html_e( 'Browse The Run', 'lunara-film' ); ?></a>
                                <a href="<?php echo esc_url( home_url( '/oscars/' ) ); ?>"><?php esc_html_e( 'Oscar Ledger', 'lunara-film' ); ?></a>
                                <a href="<?php echo esc_url( home_url( '/journal/' ) ); ?>"><?php esc_html_e( 'Journal Desk', 'lunara-film' ); ?></a>
                            </div>
                        <?php endif; ?>
                    </aside>
                </div>
            </section>
            <?php endif; ?>

            <?php if ( $show_grid && ! empty( $sort_options ) ) : ?>
            <section class="lunara-home-section lunara-review-archive-utility lunara-review-archive-slot-utility" data-lunara-section="utility">
                <div class="lunara-review-archive-toolbar">
                    <div class="lunara-home-section-head lunara-review-archive-toolbar-head">
                        <div>
                            <p class="lunara-home-section-kicker"><?php esc_html_e( 'Review Order', 'lunara-film' ); ?></p>
                            <h2 class="lunara-section-title"><?php esc_html_e( 'Release timeline or real editing activity', 'lunara-film' ); ?></h2>
                        </div>
                    </div>
                    <div class="lunara-review-archive-sort" aria-label="<?php esc_attr_e( 'Sort reviews', 'lunara-film' ); ?>">
                        <?php foreach ( $sort_options as $sort_key => $sort_option_label ) : ?>
                            <?php
                            $is_active = $sort_key === $current_sort;
                            $sort_url  = 'release_desc' === $sort_key ? $sort_base_url : add_query_arg( 'sort', rawurlencode( $sort_key ), $sort_base_url );
                            ?>
                            <a class="lunara-review-archive-sort-link <?php echo $is_active ? 'is-active' : ''; ?>" href="<?php echo esc_url( $sort_url ); ?>"<?php echo $is_active ? ' aria-current="page"' : ''; ?>>
                                <?php echo esc_html( $sort_option_label ); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
            <?php endif; ?>

            <?php if ( $show_grid ) : ?>
            <section class="lunara-home-section lunara-review-archive-shell lunara-review-archive-slot-grid" data-lunara-section="grid">
                <?php if ( $lead_post instanceof WP_Post ) : ?>
                    <div class="lunara-review-archive-spotlight" data-lunara-section="spotlight">
                        <?php echo lunara_render_review_feature_card( $lead_post->ID, array( 'variant' => 'lead', 'excerpt_words' => 44 ) ); ?>

                        <?php if ( ! empty( $support_posts ) ) : ?>
                            <div class="lunara-review-archive-support-head">
                                <p class="lunara-home-section-kicker"><?php esc_html_e( 'Filed Beside The Lead', 'lunara-film' ); ?></p>
                                <h2 class="lunara-section-title"><?php esc_html_e( 'Two more reviews carrying the current desk rhythm.', 'lunara-film' ); ?></h2>
                            </div>
                            <div class="lunara-review-archive-rail">
                                <?php foreach ( $support_posts as $support_index => $support_post ) : ?>
                                    <?php echo lunara_render_review_grid_card( $support_post->ID, $support_index + 2 ); ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ( ! empty( $remaining_posts ) ) : ?>
                        <div id="lunara-review-archive-run" class="lunara-review-archive-run" data-lunara-section="run">
                            <div class="lunara-home-section-head lunara-review-archive-run-head">
                                <div>
                                    <p class="lunara-home-section-kicker"><?php esc_html_e( 'Criticism Run', 'lunara-film' ); ?></p>
                                    <h2 class="lunara-section-title"><?php esc_html_e( 'The archive keeps moving.', 'lunara-film' ); ?></h2>
                                </div>
                            </div>
                            <div class="lunara-review-grid lunara-review-archive-grid lunara-review-archive-uniform lunara-review-archive-run-grid">
                                <?php foreach ( $remaining_posts as $review_index => $review_post ) : ?>
                                    <?php echo lunara_render_review_grid_card( $review_post->ID, $review_index + 4 ); ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php else : ?>
                    <div class="lunara-archive-empty">
                        <h2><?php echo esc_html( $args['empty_title'] ); ?></h2>
                        <?php if ( '' !== trim( (string) $args['empty_copy'] ) ) : ?>
                            <p><?php echo esc_html( $args['empty_copy'] ); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </section>
            <?php endif; ?>

            <?php if ( $show_grid && $show_pagination && ! empty( $posts ) && ! empty( $args['pagination'] ) ) : ?>
                    <div class="lunara-archive-pagination lunara-review-archive-slot-pagination" data-lunara-section="pagination">
                        <?php echo wp_kses_post( $args['pagination'] ); ?>
                    </div>
            <?php elseif ( ! $show_grid && $show_pagination && ! empty( $args['pagination'] ) ) : ?>
                    <div class="lunara-archive-pagination lunara-review-archive-slot-pagination" data-lunara-section="pagination">
                        <div class="lunara-archive-pagination">
                            <?php echo wp_kses_post( $args['pagination'] ); ?>
                        </div>
                    </div>
            <?php endif; ?>
        </main>
        <?php

        return ob_get_clean();
    }
}

/**
 * Dedicated news archive shell so the /news/ route feels authored, not generic.
 */
if ( ! function_exists( 'lunara_render_news_archive_shell' ) ) {
    function lunara_render_news_archive_shell( $args = array() ) {
        $defaults = array(
            'classes'      => 'lunara-editorial-archive-page lunara-news-archive-page',
            'kicker'       => __( 'Lunara Journal', 'lunara-film' ),
            'title'        => __( 'News', 'lunara-film' ),
            'copy'         => '',
            'posts'        => array(),
            'empty_title'  => __( 'No news posts have been filed yet.', 'lunara-film' ),
            'empty_copy'   => __( 'Published news coverage will appear here automatically.', 'lunara-film' ),
            'copy_words'   => 42,
            'pagination'   => paginate_links(),
            'source_label' => __( 'Editorial lane', 'lunara-film' ),
        );
        $args = wp_parse_args( $args, $defaults );

        $posts = array_values( array_filter( (array) $args['posts'], static function ( $post_item ) {
            return $post_item instanceof WP_Post;
        } ) );

        $copy            = '';
        $classes         = trim( 'site-main lunara-archive-page ' . (string) $args['classes'] );
        $lead_post       = ! empty( $posts ) ? array_shift( $posts ) : null;
        $support_posts   = array_slice( $posts, 0, 2 );
        $remaining_posts = array_slice( $posts, 2 );
        $visible_count   = count( $posts ) + ( $lead_post instanceof WP_Post ? 1 : 0 );
        $has_posts       = $lead_post instanceof WP_Post;
        $classes        .= $has_posts ? ' lunara-news-archive-has-posts' : ' lunara-news-archive-is-empty';
        $archive_mode    = $lead_post instanceof WP_Post
            ? ( ! empty( $remaining_posts ) ? __( 'Spotlight / Supporting / News Run', 'lunara-film' ) : __( 'Spotlight / Supporting', 'lunara-film' ) )
            : __( 'Standby', 'lunara-film' );
        $live_order_map  = function_exists( 'lunara_get_news_archive_live_section_order_map' )
            ? lunara_get_news_archive_live_section_order_map()
            : array();
        $empty_order_map = function_exists( 'lunara_get_news_archive_empty_section_order_map' )
            ? lunara_get_news_archive_empty_section_order_map()
            : array();
        $standby_card_order = function_exists( 'lunara_get_news_archive_standby_card_order_map' )
            ? lunara_get_news_archive_standby_card_order_map()
            : array();

        $news_total = 0;
        $news_term  = get_category_by_slug( 'news' );
        if ( $news_term instanceof WP_Term ) {
            $news_total = intval( $news_term->count );
        }

        ob_start();
        ?>
        <main id="primary" class="<?php echo esc_attr( $classes ); ?>">
            <?php if ( function_exists( 'lunara_news_archive_live_section_is_enabled' ) ? lunara_news_archive_live_section_is_enabled( 'hero' ) : true ) : ?>
            <section class="lunara-home-section lunara-archive-hero lunara-news-archive-hero lunara-news-archive-slot-hero" data-lunara-section="hero">
                <div class="lunara-news-archive-hero-shell">
                    <div class="lunara-news-archive-hero-copy-wrap">
                        <p class="lunara-archive-hero-kicker"><?php echo esc_html( $args['kicker'] ); ?></p>
                        <h1 class="lunara-archive-hero-title"><?php echo esc_html( $args['title'] ); ?></h1>
                        <?php if ( '' !== $copy ) : ?>
                            <p class="lunara-archive-hero-copy"><?php echo esc_html( wp_trim_words( $copy, max( 12, intval( $args['copy_words'] ) ) ) ); ?></p>
                        <?php endif; ?>
                    </div>
                    <aside class="lunara-news-archive-debrief" aria-label="<?php esc_attr_e( 'News archive summary', 'lunara-film' ); ?>">
                        <p class="lunara-news-archive-debrief-kicker"><?php esc_html_e( 'At A Glance', 'lunara-film' ); ?></p>
                        <ul class="lunara-news-archive-debrief-list">
                            <li>
                                <strong><?php esc_html_e( 'Total Filed', 'lunara-film' ); ?></strong>
                                <span><?php echo esc_html( number_format_i18n( $news_total ) ); ?></span>
                            </li>
                            <li>
                                <strong><?php esc_html_e( 'Visible Now', 'lunara-film' ); ?></strong>
                                <span><?php echo esc_html( number_format_i18n( $visible_count ) ); ?></span>
                            </li>
                            <li>
                                <strong><?php esc_html_e( 'Desk State', 'lunara-film' ); ?></strong>
                                <span><?php echo esc_html( $archive_mode ); ?></span>
                            </li>
                            <li>
                                <strong><?php esc_html_e( 'Coverage Focus', 'lunara-film' ); ?></strong>
                                <span><?php echo esc_html( $args['source_label'] ); ?></span>
                            </li>
                        </ul>
                    </aside>
                </div>
            </section>
            <?php endif; ?>

            <section class="lunara-home-section lunara-editorial-archive-shell lunara-news-archive-shell">
                <?php if ( $lead_post instanceof WP_Post ) : ?>
                    <?php if ( function_exists( 'lunara_news_archive_live_section_is_enabled' ) ? lunara_news_archive_live_section_is_enabled( 'spotlight' ) : true ) : ?>
                    <div class="lunara-news-archive-spotlight lunara-news-archive-slot-spotlight" data-lunara-section="spotlight">
                        <?php echo lunara_render_dispatch_archive_card( $lead_post->ID, true ); ?>

                        <?php if ( ! empty( $support_posts ) ) : ?>
                            <div class="lunara-news-archive-rail">
                                <div class="lunara-news-archive-rail-shell">
                                    <p class="lunara-home-section-kicker"><?php esc_html_e( 'In Rotation', 'lunara-film' ); ?></p>
                                    <h2 class="lunara-section-title"><?php esc_html_e( 'What The Signal Is Holding Beside The Lead', 'lunara-film' ); ?></h2>
                                </div>
                                <?php foreach ( $support_posts as $post_item ) : ?>
                                    <?php echo lunara_render_dispatch_archive_card( $post_item->ID ); ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <?php if ( ! empty( $remaining_posts ) && ( function_exists( 'lunara_news_archive_live_section_is_enabled' ) ? lunara_news_archive_live_section_is_enabled( 'run' ) : true ) ) : ?>
                        <div class="lunara-news-archive-slot-run" data-lunara-section="run">
                        <div class="lunara-home-section-head lunara-news-archive-run-head">
                            <div>
                                <p class="lunara-home-section-kicker"><?php esc_html_e( 'Archive Run', 'lunara-film' ); ?></p>
                                <h2 class="lunara-section-title"><?php esc_html_e( 'More Lunara Dispatches', 'lunara-film' ); ?></h2>
                            </div>
                        </div>

                        <div class="lunara-dispatch-archive-grid lunara-news-archive-grid">
                            <?php foreach ( $remaining_posts as $post_item ) : ?>
                                <?php echo lunara_render_dispatch_archive_card( $post_item->ID ); ?>
                            <?php endforeach; ?>
                        </div>
                        </div>
                    <?php endif; ?>

                    <?php if ( ! empty( $args['pagination'] ) && ( function_exists( 'lunara_news_archive_live_section_is_enabled' ) ? lunara_news_archive_live_section_is_enabled( 'pagination' ) : true ) ) : ?>
                        <div class="lunara-archive-pagination lunara-news-archive-slot-pagination" data-lunara-section="pagination">
                            <?php echo wp_kses_post( $args['pagination'] ); ?>
                        </div>
                    <?php endif; ?>
                <?php else : ?>
                    <?php if ( function_exists( 'lunara_news_archive_empty_section_is_enabled' ) ? lunara_news_archive_empty_section_is_enabled( 'intro' ) : true ) : ?>
                    <div class="lunara-news-archive-empty-shell lunara-news-archive-slot-intro" data-lunara-section="intro">
                        <div class="lunara-archive-empty lunara-news-archive-empty">
                            <p class="lunara-home-section-kicker"><?php esc_html_e( 'Desk Standby', 'lunara-film' ); ?></p>
                            <h2><?php echo esc_html( $args['empty_title'] ); ?></h2>
                            <?php if ( '' !== trim( (string) $args['empty_copy'] ) ) : ?>
                                <p><?php echo esc_html( $args['empty_copy'] ); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="lunara-news-archive-empty-note">
                            <p class="lunara-home-section-kicker"><?php esc_html_e( 'What Lives Here', 'lunara-film' ); ?></p>
                            <h2 class="lunara-section-title"><?php esc_html_e( 'Breaking items, industry shifts, and the stories worth moving on quickly.', 'lunara-film' ); ?></h2>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ( function_exists( 'lunara_news_archive_empty_section_is_enabled' ) ? lunara_news_archive_empty_section_is_enabled( 'standby' ) : true ) : ?>
                    <div class="lunara-news-archive-standby-shell lunara-news-archive-slot-standby" data-lunara-section="standby">
                        <div class="lunara-home-section-head lunara-news-archive-standby-head">
                            <div>
                                <p class="lunara-home-section-kicker"><?php esc_html_e( 'Stay On Signal', 'lunara-film' ); ?></p>
                                <h2 class="lunara-section-title"><?php esc_html_e( 'The publication is still alive around the dispatch desk.', 'lunara-film' ); ?></h2>
                            </div>
                        </div>
                        <div class="lunara-news-archive-standby-grid">
                            <a class="lunara-news-archive-standby-card" style="order:<?php echo esc_attr( intval( $standby_card_order['reviews'] ?? 1 ) ); ?>;" href="<?php echo esc_url( get_post_type_archive_link( 'review' ) ?: home_url( '/reviews/' ) ); ?>">
                                <p class="lunara-home-section-kicker"><?php esc_html_e( 'Criticism', 'lunara-film' ); ?></p>
                                <h3><?php esc_html_e( 'Browse The Review Archive', 'lunara-film' ); ?></h3>
                                <span class="lunara-section-link"><?php esc_html_e( 'Enter The Reviews', 'lunara-film' ); ?></span>
                            </a>
                            <a class="lunara-news-archive-standby-card" style="order:<?php echo esc_attr( intval( $standby_card_order['ledger'] ?? 2 ) ); ?>;" href="<?php echo esc_url( home_url( '/oscars/' ) ); ?>">
                                <p class="lunara-home-section-kicker"><?php esc_html_e( 'Ledger', 'lunara-film' ); ?></p>
                                <h3><?php esc_html_e( 'Step Into The Oscar Ledger', 'lunara-film' ); ?></h3>
                                <span class="lunara-section-link"><?php esc_html_e( 'Open The Ledger', 'lunara-film' ); ?></span>
                            </a>
                            <a class="lunara-news-archive-standby-card" style="order:<?php echo esc_attr( intval( $standby_card_order['home'] ?? 3 ) ); ?>;" href="<?php echo esc_url( home_url( '/' ) ); ?>">
                                <p class="lunara-home-section-kicker"><?php esc_html_e( 'Front Door', 'lunara-film' ); ?></p>
                                <h3><?php esc_html_e( 'Return To The Live Homepage', 'lunara-film' ); ?></h3>
                                <span class="lunara-section-link"><?php esc_html_e( 'Go To Lunara', 'lunara-film' ); ?></span>
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </section>
        </main>
        <?php

        return ob_get_clean();
    }
}

/* lunara_where_to_watch_shortcode lives in inc/shortcodes-home.php on the server */
