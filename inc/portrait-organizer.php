<?php
/**
 * Lunara Portrait Organizer.
 *
 * One-click admin tool to file Oscar person portraits into a single media
 * folder so the Media Library stops drowning in them. Portraits are selected
 * precisely by the Academy Awards plugin's import marker
 * (_aat_person_portrait_source), so logos, posters, and backdrops are never
 * touched. The operation only ADDS a HappyFiles folder label to each portrait —
 * it never moves, renames, or deletes a file — so it is fully reversible.
 *
 * Lives in the theme (rather than a plugin) purely for a reliable deploy
 * target; the folder assignments it writes persist in the database independent
 * of the theme.
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Lunara_Portrait_Organizer' ) ) :

	final class Lunara_Portrait_Organizer {

		/** Display name of the folder portraits are filed into. */
		const FOLDER_NAME = 'Oscar Portraits';

		/** HappyFiles registers this taxonomy for attachment folders. */
		const TAXONOMY = 'happyfiles_category';

		/** How many attachments to process per AJAX batch. */
		const BATCH = 300;

		/**
		 * The portrait-source meta values written by the Academy Awards plugin
		 * on import. Selecting on these is the reliable way to grab every
		 * portrait and nothing else.
		 */
		const SOURCES = array( 'tmdb-person-profile', 'manual-batch-upload', 'existing-media-adoption' );

		/** Wire up admin hooks. */
		public static function init() {
			add_action( 'admin_menu', array( __CLASS__, 'register_page' ) );
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
			add_action( 'wp_ajax_lunara_portraits_scan', array( __CLASS__, 'ajax_scan' ) );
			add_action( 'wp_ajax_lunara_portraits_organize', array( __CLASS__, 'ajax_organize' ) );
			add_action( 'wp_ajax_lunara_portraits_unfile', array( __CLASS__, 'ajax_unfile' ) );
		}

		/** Register the tool under the Media menu. */
		public static function register_page() {
			add_media_page(
				__( 'Organize Oscar Portraits', 'lunara-film' ),
				__( 'Organize Portraits', 'lunara-film' ),
				'manage_options',
				'lunara-organize-portraits',
				array( __CLASS__, 'render_page' )
			);
		}

		/** Enqueue the tool's script only on its own screen. */
		public static function enqueue( $hook ) {
			if ( 'media_page_lunara-organize-portraits' !== $hook ) {
				return;
			}

			$rel  = 'assets/js/lunara-portrait-organizer.js';
			$path = get_stylesheet_directory() . '/' . $rel;
			$ver  = file_exists( $path ) ? (string) filemtime( $path ) : '1';

			wp_enqueue_script(
				'lunara-portrait-organizer',
				get_stylesheet_directory_uri() . '/' . $rel,
				array( 'jquery' ),
				$ver,
				true
			);

			wp_localize_script(
				'lunara-portrait-organizer',
				'LUNARA_PORTRAITS',
				array(
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'lunara_portraits' ),
					'i18n'    => array(
						'confirmUnfile' => __( 'Remove the Oscar Portraits folder label from every portrait? (The images themselves are untouched.)', 'lunara-film' ),
						'working'       => __( 'Working…', 'lunara-film' ),
						'doneOrganize'  => __( 'Done. All portraits are filed in “Oscar Portraits”.', 'lunara-film' ),
						'doneUnfile'    => __( 'Done. The folder label was removed from every portrait.', 'lunara-film' ),
						'error'         => __( 'Something went wrong — check the console and try again.', 'lunara-film' ),
					),
				)
			);
		}

		/** True when HappyFiles (the folder taxonomy) is available. */
		private static function folders_available() {
			return taxonomy_exists( self::TAXONOMY );
		}

		/**
		 * Get the "Oscar Portraits" folder term id, creating it if needed.
		 *
		 * @param bool $create Whether to create the term when missing.
		 * @return int Term id, or 0 if unavailable/not created.
		 */
		private static function folder_term_id( $create = false ) {
			if ( ! self::folders_available() ) {
				return 0;
			}

			$term = get_term_by( 'name', self::FOLDER_NAME, self::TAXONOMY );
			if ( $term instanceof WP_Term ) {
				return (int) $term->term_id;
			}

			if ( ! $create ) {
				return 0;
			}

			$created = wp_insert_term( self::FOLDER_NAME, self::TAXONOMY );
			if ( is_wp_error( $created ) ) {
				return 0;
			}

			return (int) $created['term_id'];
		}

		/**
		 * Count portrait attachments, optionally limited to those not yet in
		 * the folder.
		 *
		 * @param int $exclude_term_id When >0, exclude attachments already in this term.
		 * @return int
		 */
		private static function count_portraits( $exclude_term_id = 0 ) {
			global $wpdb;

			$placeholders = implode( ',', array_fill( 0, count( self::SOURCES ), '%s' ) );
			$params       = self::SOURCES;

			$exclude_sql = '';
			if ( $exclude_term_id > 0 ) {
				$exclude_sql = " AND p.ID NOT IN (
						SELECT tr.object_id FROM {$wpdb->term_relationships} tr
						INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
						WHERE tt.term_id = %d
					)";
				$params[]    = $exclude_term_id;
			}

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $placeholders/$exclude_sql are placeholder strings, prepared in this single call.
			$sql = $wpdb->prepare(
				"SELECT COUNT(DISTINCT p.ID)
				 FROM {$wpdb->posts} p
				 INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = '_aat_person_portrait_source'
				 WHERE p.post_type = 'attachment'
				   AND pm.meta_value IN ($placeholders)" . $exclude_sql,
				$params
			);

			return (int) $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		}

		/**
		 * Fetch a batch of portrait attachment ids not yet in the folder.
		 *
		 * @param int $term_id The folder term to exclude.
		 * @param int $limit   Batch size.
		 * @return int[]
		 */
		private static function unfiled_portrait_ids( $term_id, $limit ) {
			global $wpdb;

			$placeholders = implode( ',', array_fill( 0, count( self::SOURCES ), '%s' ) );
			$params       = array_merge( self::SOURCES, array( $term_id, (int) $limit ) );

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$sql = $wpdb->prepare(
				"SELECT DISTINCT p.ID
				 FROM {$wpdb->posts} p
				 INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = '_aat_person_portrait_source'
				 WHERE p.post_type = 'attachment'
				   AND pm.meta_value IN ($placeholders)
				   AND p.ID NOT IN (
						SELECT tr.object_id FROM {$wpdb->term_relationships} tr
						INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
						WHERE tt.term_id = %d
				   )
				 ORDER BY p.ID ASC
				 LIMIT %d",
				$params
			);

			return array_map( 'intval', (array) $wpdb->get_col( $sql ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		}

		/** Shared AJAX guard: capability + nonce. */
		private static function guard() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => __( 'Permission denied.', 'lunara-film' ) ) );
			}
			check_ajax_referer( 'lunara_portraits', 'nonce' );
		}

		/** AJAX: report counts + folder status (read-only). */
		public static function ajax_scan() {
			self::guard();

			if ( ! self::folders_available() ) {
				wp_send_json_error(
					array( 'message' => __( 'HappyFiles is not active, so there is no folder system to file into. Activate HappyFiles, then reload this page.', 'lunara-film' ) )
				);
			}

			$term_id  = self::folder_term_id( false );
			$total    = self::count_portraits( 0 );
			$unfiled  = $term_id ? self::count_portraits( $term_id ) : $total;
			$filed    = max( 0, $total - $unfiled );

			wp_send_json_success(
				array(
					'total'        => $total,
					'filed'        => $filed,
					'unfiled'      => $unfiled,
					'folderExists' => (bool) $term_id,
					'folderName'   => self::FOLDER_NAME,
				)
			);
		}

		/** AJAX: file one batch of portraits into the folder. */
		public static function ajax_organize() {
			self::guard();

			$term_id = self::folder_term_id( true );
			if ( ! $term_id ) {
				wp_send_json_error( array( 'message' => __( 'Could not find or create the Oscar Portraits folder. Is HappyFiles active?', 'lunara-film' ) ) );
			}

			$ids       = self::unfiled_portrait_ids( $term_id, self::BATCH );
			$processed = 0;
			// Defer term-count recalculation until the batch is done — avoids a
			// recount on every single assignment across thousands of rows.
			wp_defer_term_counting( true );
			foreach ( $ids as $id ) {
				// Append the folder term without clobbering any existing folders.
				$result = wp_set_object_terms( $id, array( (int) $term_id ), self::TAXONOMY, true );
				if ( ! is_wp_error( $result ) ) {
					$processed++;
				}
			}
			wp_defer_term_counting( false );

			$remaining = self::count_portraits( $term_id );

			wp_send_json_success(
				array(
					'processed' => $processed,
					'remaining' => $remaining,
					'done'      => 0 === $remaining || empty( $ids ),
				)
			);
		}

		/** AJAX: remove the folder label from one batch of portraits (reverse). */
		public static function ajax_unfile() {
			self::guard();

			$term_id = self::folder_term_id( false );
			if ( ! $term_id ) {
				wp_send_json_success( array( 'processed' => 0, 'remaining' => 0, 'done' => true ) );
			}

			global $wpdb;
			$ids = array_map(
				'intval',
				(array) $wpdb->get_col(
					$wpdb->prepare(
						"SELECT tr.object_id FROM {$wpdb->term_relationships} tr
						 INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
						 WHERE tt.term_id = %d LIMIT %d",
						$term_id,
						self::BATCH
					)
				) // phpcs:ignore WordPress.DB
			);

			$processed = 0;
			wp_defer_term_counting( true );
			foreach ( $ids as $id ) {
				$result = wp_remove_object_terms( $id, array( (int) $term_id ), self::TAXONOMY );
				if ( ! is_wp_error( $result ) ) {
					$processed++;
				}
			}
			wp_defer_term_counting( false );

			$remaining = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->term_relationships} tr
					 INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
					 WHERE tt.term_id = %d",
					$term_id
				) // phpcs:ignore WordPress.DB
			);

			wp_send_json_success(
				array(
					'processed' => $processed,
					'remaining' => $remaining,
					'done'      => 0 === $remaining || empty( $ids ),
				)
			);
		}

		/** Render the admin screen. */
		public static function render_page() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have permission to access this page.', 'lunara-film' ) );
			}
			$happyfiles = self::folders_available();
			?>
			<div class="wrap">
				<h1><?php esc_html_e( 'Organize Oscar Portraits', 'lunara-film' ); ?></h1>
				<p style="max-width:760px;">
					<?php esc_html_e( 'Files every Academy Awards person portrait into a single "Oscar Portraits" folder so your Media Library stops drowning in them. Portraits are matched by their import marker — your logos, posters, and backdrops are never touched. This only adds a folder label; it never moves, renames, or deletes a file, so it is fully reversible.', 'lunara-film' ); ?>
				</p>

				<?php if ( ! $happyfiles ) : ?>
					<div class="notice notice-warning"><p>
						<?php esc_html_e( 'HappyFiles is not active. Activate it first so there is a folder to file portraits into, then reload this page.', 'lunara-film' ); ?>
					</p></div>
				<?php else : ?>
					<div class="notice notice-info"><p>
						<strong><?php esc_html_e( 'Before the first run:', 'lunara-film' ); ?></strong>
						<?php esc_html_e( 'take an UpdraftPlus backup. Then click Scan (read-only), review the count, and Organize.', 'lunara-film' ); ?>
					</p></div>

					<div id="lunara-portraits-app" style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:20px;max-width:760px;margin-top:14px;">
						<p>
							<button type="button" class="button button-secondary" id="lunara-portraits-scan"><?php esc_html_e( 'Scan (read-only)', 'lunara-film' ); ?></button>
							<button type="button" class="button button-primary" id="lunara-portraits-organize" disabled><?php esc_html_e( 'Organize portraits', 'lunara-film' ); ?></button>
							<button type="button" class="button" id="lunara-portraits-unfile" style="float:right;"><?php esc_html_e( 'Undo (unfile all)', 'lunara-film' ); ?></button>
						</p>
						<div id="lunara-portraits-stats" style="margin:12px 0;color:#3c434a;"></div>
						<div id="lunara-portraits-progress-wrap" style="display:none;background:#f0f0f1;border-radius:6px;height:18px;overflow:hidden;margin:10px 0;">
							<div id="lunara-portraits-progress" style="height:100%;width:0;background:#c9a961;transition:width .2s ease;"></div>
						</div>
						<div id="lunara-portraits-status" style="font-weight:600;color:#1d2327;"></div>
					</div>
				<?php endif; ?>
			</div>
			<?php
		}
	}

	Lunara_Portrait_Organizer::init();

endif;
