<?php
/** Canonical homepage carousel configuration. Reads never adopt or rewrite legacy settings. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
function lunara_home_carousel_option( $kind ) { return 'journal' === $kind ? 'lunara_home_journal_carousel' : 'lunara_hero_command'; }
function lunara_home_carousel_defaults( $kind = 'hero' ) {
 return array( 'adopted' => false, 'mode' => 'auto', 'heading' => 'journal' === $kind ? 'The Journal' : '', 'autoplay' => 1, 'interval' => 7, 'overlay' => 100, 'slides' => array() );
}
function lunara_home_carousel_sanitize( $raw, $kind = 'hero' ) {
 $clean = lunara_home_carousel_defaults( $kind ); if ( ! is_array( $raw ) ) { return $clean; }
 $clean['adopted'] = ! empty( $raw['adopted'] );
 $clean['mode'] = isset( $raw['mode'] ) && 'manual' === $raw['mode'] ? 'manual' : 'auto';
 $clean['heading'] = isset( $raw['heading'] ) && is_scalar( $raw['heading'] ) ? mb_substr( sanitize_text_field( (string) $raw['heading'] ), 0, 160 ) : $clean['heading'];
 $clean['autoplay'] = isset( $raw['autoplay'] ) ? (int) ! empty( $raw['autoplay'] ) : 1;
 foreach ( array( 'interval' => array( 3, 30, 7 ), 'overlay' => array( 20, 100, 100 ) ) as $key => $bounds ) { $clean[ $key ] = isset( $raw[ $key ] ) && is_numeric( $raw[ $key ] ) ? max( $bounds[0], min( $bounds[1], (int) $raw[ $key ] ) ) : $bounds[2]; }
 $seen = array();
 foreach ( isset( $raw['slides'] ) && is_array( $raw['slides'] ) ? $raw['slides'] : array() as $entry ) {
  if ( ! is_array( $entry ) || empty( $entry['post_id'] ) || ! is_numeric( $entry['post_id'] ) ) { continue; }
  $id = absint( $entry['post_id'] ); if ( ! $id || isset( $seen[ $id ] ) || count( $clean['slides'] ) >= 48 ) { continue; } $seen[ $id ] = true;
  // Retain unavailable source IDs: publication state is checked at delivery, never destructively at save.
  $slide = array( 'post_id' => $id, 'image_id' => isset( $entry['image_id'] ) && is_numeric( $entry['image_id'] ) ? absint( $entry['image_id'] ) : 0 );
  foreach ( array( 'headline' => 240, 'excerpt' => 600, 'kicker' => 60, 'cta' => 40 ) as $field => $max ) { $slide[ $field ] = isset( $entry[ $field ] ) && is_scalar( $entry[ $field ] ) ? mb_substr( sanitize_text_field( (string) $entry[ $field ] ), 0, $max ) : ''; }
  foreach ( array( 'overlay' => array( 0, 100, 0 ), 'focal_x' => array( 0, 100, 50 ), 'focal_y' => array( 0, 100, 30 ), 'zoom' => array( 100, 112, 100 ) ) as $field => $bounds ) { $slide[ $field ] = isset( $entry[ $field ] ) && is_numeric( $entry[ $field ] ) ? max( $bounds[0], min( $bounds[1], (int) $entry[ $field ] ) ) : $bounds[2]; }
  $slide['fit'] = isset( $entry['fit'] ) && 'full' === $entry['fit'] ? 'full' : 'cover'; $clean['slides'][] = $slide;
 }
 return $clean;
}
function lunara_home_carousel_settings( $kind = 'hero' ) {
 $kind = 'journal' === $kind ? 'journal' : 'hero';
 if ( isset( $GLOBALS['lunara_home_carousel_preview'][ $kind ] ) ) { return $GLOBALS['lunara_home_carousel_preview'][ $kind ]; }
 $raw = get_option( lunara_home_carousel_option( $kind ), array() );
 if ( 'hero' === $kind ) {
  if ( is_array( $raw ) && isset( $raw['carousel'] ) && is_array( $raw['carousel'] ) ) { $raw = $raw['carousel']; }
  else { $legacy = is_array( $raw ) ? $raw : array(); $raw = array( 'mode' => empty( $legacy['enabled'] ) ? 'auto' : 'manual', 'slides' => $legacy['slides'] ?? array(), 'overlay' => $legacy['overlay'] ?? 100 ); }
 }
 return lunara_home_carousel_sanitize( $raw, $kind );
}
