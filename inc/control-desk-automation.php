<?php
/**
 * Control Desk presentation for Foundation-owned Journal Automation.
 *
 * The theme renders this operating surface; Journal Foundation retains all
 * permissions, credentials, REST behavior, private data, and audit history.
 *
 * @package Lunara_Film
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'lunara_control_desk_render_automation_notice' ) ) {
    function lunara_control_desk_render_automation_notice() {
        $notice = isset( $_GET['lunara_automation_notice'] ) ? sanitize_key( wp_unslash( $_GET['lunara_automation_notice'] ) ) : '';
        $messages = array(
            'morning_queued'        => array( 'success', __( 'Morning Desk was queued for IFTTT delivery.', 'lunara-film' ) ),
            'morning_not_configured'=> array( 'warning', __( 'Morning Desk was built, but outbound IFTTT delivery is not configured yet.', 'lunara-film' ) ),
            'test_queued'           => array( 'success', __( 'The IFTTT connection test was queued.', 'lunara-film' ) ),
            'test_not_configured'   => array( 'warning', __( 'The connection test could not be sent because outbound IFTTT delivery is not configured.', 'lunara-film' ) ),
            'signal_updated'        => array( 'success', __( 'Automation Inbox status updated.', 'lunara-film' ) ),
            'signal_invalid'        => array( 'error', __( 'That Automation Inbox update was refused.', 'lunara-film' ) ),
        );

        if ( ! isset( $messages[ $notice ] ) ) {
            return;
        }

        printf(
            '<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
            esc_attr( $messages[ $notice ][0] ),
            esc_html( $messages[ $notice ][1] )
        );
    }
}

if ( ! function_exists( 'lunara_control_desk_automation_status_class' ) ) {
    function lunara_control_desk_automation_status_class( $ready ) {
        return $ready ? 'is-ready' : 'is-needs';
    }
}

if ( ! function_exists( 'lunara_control_desk_automation_format_cost' ) ) {
    /**
     * Format a bounded cost estimate without implying unreported data is free.
     *
     * @param mixed $cost Estimated run cost in US dollars.
     * @return string
     */
    function lunara_control_desk_automation_format_cost( $cost ) {
        if ( null === $cost || '' === $cost || ! is_numeric( $cost ) ) {
            return __( 'Not reported', 'lunara-film' );
        }

        return '$' . number_format_i18n( max( 0, (float) $cost ), 4 );
    }
}

if ( ! function_exists( 'lunara_control_desk_render_dispatch_telemetry' ) ) {
    /**
     * Render the private, non-secret Dispatch 3.2.5 operating snapshot.
     *
     * Foundation owns collection and sanitization. This theme renderer stays
     * backward-compatible when Foundation 1.2.10 omits the additive fields.
     *
     * @param array $dispatch Foundation-owned Dispatch snapshot.
     * @return void
     */
    function lunara_control_desk_render_dispatch_telemetry( $dispatch ) {
        $runtime  = isset( $dispatch['runtime'] ) && is_array( $dispatch['runtime'] ) ? $dispatch['runtime'] : array();
        $last_run = isset( $dispatch['last_run'] ) && is_array( $dispatch['last_run'] ) ? $dispatch['last_run'] : array();

        $has_output_cap    = ! empty( $runtime['max_output_tokens'] );
        $has_source_budget = ! empty( $runtime['source_budget'] );
        $provider          = ! empty( $runtime['provider'] ) ? strtoupper( (string) $runtime['provider'] ) : __( 'Not reported', 'lunara-film' );
        $configured_model  = ! empty( $runtime['model'] ) ? $runtime['model'] : __( 'Not reported', 'lunara-film' );
        $output_cap        = $has_output_cap ? number_format_i18n( absint( $runtime['max_output_tokens'] ) ) : __( 'Not reported', 'lunara-film' );
        $source_budget     = $has_source_budget ? number_format_i18n( absint( $runtime['source_budget'] ) ) : __( 'Not reported', 'lunara-film' );

        $requested_model = ! empty( $last_run['requested_model'] ) ? $last_run['requested_model'] : '';
        $effective_model = ! empty( $last_run['effective_model'] ) ? $last_run['effective_model'] : '';
        $model_route     = $effective_model ? $effective_model : ( $requested_model ? $requested_model : __( 'Awaiting run telemetry', 'lunara-film' ) );
        $model_note      = __( 'No model override reported.', 'lunara-film' );
        if ( $requested_model && $effective_model && $requested_model !== $effective_model ) {
            $model_route = sprintf( '%1$s → %2$s', $requested_model, $effective_model );
            $model_note  = __( 'Cost guard override applied.', 'lunara-film' );
        }

        $has_usage = array_key_exists( 'usage_reported', $last_run )
            ? ! empty( $last_run['usage_reported'] )
            : ( ( array_key_exists( 'input_tokens', $last_run ) && null !== $last_run['input_tokens'] ) || ( array_key_exists( 'output_tokens', $last_run ) && null !== $last_run['output_tokens'] ) );

        $input_reported  = $has_usage && array_key_exists( 'input_tokens', $last_run ) && null !== $last_run['input_tokens'];
        $cached_reported = $has_usage && array_key_exists( 'cached_input_tokens', $last_run ) && null !== $last_run['cached_input_tokens'];
        $output_reported = $has_usage && array_key_exists( 'output_tokens', $last_run ) && null !== $last_run['output_tokens'];
        $input_label     = $input_reported ? number_format_i18n( absint( $last_run['input_tokens'] ) ) : __( 'Not reported', 'lunara-film' );
        $cached_label    = $cached_reported ? number_format_i18n( absint( $last_run['cached_input_tokens'] ) ) : __( 'Not reported', 'lunara-film' );
        $output_label    = $output_reported ? number_format_i18n( absint( $last_run['output_tokens'] ) ) : __( 'Not reported', 'lunara-film' );
        $usage_label     = $has_usage
            ? sprintf(
                /* translators: 1: input tokens, 2: cached input tokens, 3: output tokens */
                __( '%1$s in · %2$s cached · %3$s out', 'lunara-film' ),
                $input_label,
                $cached_label,
                $output_label
            )
            : __( 'Not reported', 'lunara-film' );

        $processed_reported = ( array_key_exists( 'processed_source_items', $last_run ) && null !== $last_run['processed_source_items'] ) || ( array_key_exists( 'imported', $last_run ) && null !== $last_run['imported'] );
        $deferred_reported  = array_key_exists( 'deferred_source_items', $last_run ) && null !== $last_run['deferred_source_items'];
        $processed       = isset( $last_run['processed_source_items'] ) ? absint( $last_run['processed_source_items'] ) : ( isset( $last_run['imported'] ) ? absint( $last_run['imported'] ) : 0 );
        $deferred        = isset( $last_run['deferred_source_items'] ) ? absint( $last_run['deferred_source_items'] ) : 0;
        $processed_label = $processed_reported ? number_format_i18n( $processed ) : __( 'Not reported', 'lunara-film' );
        $deferred_label  = $deferred_reported ? number_format_i18n( $deferred ) : __( 'Not reported', 'lunara-film' );
        $fallback         = ! empty( $last_run['fallback_used'] );
        $packet_drafts    = isset( $last_run['source_packet_drafts'] ) ? absint( $last_run['source_packet_drafts'] ) : 0;
        $actual_provider  = ! empty( $last_run['provider'] ) ? strtoupper( (string) $last_run['provider'] ) : __( 'Not reported', 'lunara-film' );
        $draft_mode       = $fallback
            ? __( 'Safe source packets', 'lunara-film' )
            : ( $effective_model ? __( 'AI-assisted drafts', 'lunara-film' ) : __( 'Not reported', 'lunara-film' ) );
        $run_state = isset( $last_run['success'] ) ? $last_run['success'] : null;
        $run_label = null === $run_state
            ? __( 'Waiting for first report', 'lunara-film' )
            : ( $run_state ? __( 'Completed safely', 'lunara-film' ) : __( 'Needs attention', 'lunara-film' ) );
        $run_time   = ! empty( $last_run['timestamp_gmt'] ) ? $last_run['timestamp_gmt'] . ' GMT' : __( 'No run recorded', 'lunara-film' );
        $error_code = ! empty( $last_run['error_code'] ) ? $last_run['error_code'] : '';
        ?>
        <section class="lunara-control-desk-panel lunara-control-desk-dispatch-telemetry">
            <div class="lunara-control-desk-panel-header">
                <div>
                    <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Dispatch Runway', 'lunara-film' ); ?></p>
                    <h3><?php esc_html_e( 'Cost-safe drafting status', 'lunara-film' ); ?></h3>
                    <p class="lunara-control-desk-subtle"><?php esc_html_e( 'Configured limits and the latest actual run, reported by Journal Foundation without prompts, credentials, response IDs, or story bodies.', 'lunara-film' ); ?></p>
                </div>
                <div class="lunara-control-desk-status-pill <?php echo esc_attr( null === $run_state ? '' : lunara_control_desk_automation_status_class( (bool) $run_state ) ); ?>">
                    <strong><?php echo esc_html( $run_label ); ?></strong>
                    <span><?php echo esc_html( $run_time ); ?></span>
                </div>
            </div>

            <div class="lunara-control-desk-telemetry-grid">
                <article class="lunara-control-desk-telemetry-card">
                    <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Configured Guardrails', 'lunara-film' ); ?></p>
                    <dl>
                        <div><dt><?php esc_html_e( 'Provider', 'lunara-film' ); ?></dt><dd><?php echo esc_html( $provider ); ?></dd></div>
                        <div><dt><?php esc_html_e( 'Configured model', 'lunara-film' ); ?></dt><dd><?php echo esc_html( $configured_model ); ?></dd></div>
                        <div><dt><?php esc_html_e( 'Output cap', 'lunara-film' ); ?></dt><dd><?php echo esc_html( $output_cap ); ?><?php echo $has_output_cap ? esc_html__( ' tokens', 'lunara-film' ) : ''; ?></dd></div>
                        <div><dt><?php esc_html_e( 'Source budget', 'lunara-film' ); ?></dt><dd><?php echo esc_html( $source_budget ); ?><?php echo $has_source_budget ? esc_html__( ' per run', 'lunara-film' ) : ''; ?></dd></div>
                    </dl>
                </article>

                <article class="lunara-control-desk-telemetry-card <?php echo esc_attr( $fallback ? 'is-fallback' : '' ); ?>">
                    <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Latest Run', 'lunara-film' ); ?></p>
                    <dl>
                        <div><dt><?php esc_html_e( 'Model route', 'lunara-film' ); ?></dt><dd><?php echo esc_html( $model_route ); ?><small><?php echo esc_html( $model_note ); ?></small></dd></div>
                        <div><dt><?php esc_html_e( 'Actual provider', 'lunara-film' ); ?></dt><dd><?php echo esc_html( $actual_provider ); ?></dd></div>
                        <div><dt><?php esc_html_e( 'Draft mode', 'lunara-film' ); ?></dt><dd><?php echo esc_html( $draft_mode ); ?><?php if ( $fallback ) : ?><small><?php echo esc_html( sprintf( __( '%1$d source-packet draft(s)%2$s', 'lunara-film' ), $packet_drafts, $error_code ? ' · ' . $error_code : '' ) ); ?></small><?php endif; ?></dd></div>
                        <div><dt><?php esc_html_e( 'Source queue', 'lunara-film' ); ?></dt><dd><?php echo esc_html( sprintf( __( '%1$s processed · %2$s deferred', 'lunara-film' ), $processed_label, $deferred_label ) ); ?></dd></div>
                        <div><dt><?php esc_html_e( 'Token use', 'lunara-film' ); ?></dt><dd><?php echo esc_html( $usage_label ); ?></dd></div>
                        <div><dt><?php esc_html_e( 'Estimated cost', 'lunara-film' ); ?></dt><dd><?php echo esc_html( lunara_control_desk_automation_format_cost( isset( $last_run['estimated_cost_usd'] ) ? $last_run['estimated_cost_usd'] : null ) ); ?></dd></div>
                    </dl>
                </article>
            </div>

            <?php if ( ! empty( $last_run['message'] ) ) : ?>
                <p class="lunara-control-desk-telemetry-message"><strong><?php esc_html_e( 'Latest result:', 'lunara-film' ); ?></strong> <?php echo esc_html( $last_run['message'] ); ?></p>
            <?php elseif ( empty( $runtime ) ) : ?>
                <p class="lunara-control-desk-telemetry-message"><?php esc_html_e( 'Detailed telemetry will appear after Journal Foundation 1.2.11 is active and Dispatch completes its next run.', 'lunara-film' ); ?></p>
            <?php endif; ?>
        </section>
        <?php
    }
}

if ( ! function_exists( 'lunara_control_desk_render_automation_tab' ) ) {
    function lunara_control_desk_render_automation_tab() {
        lunara_control_desk_render_automation_notice();

        if ( ! current_user_can( 'manage_options' ) ) {
            ?>
            <section class="lunara-control-desk-panel lunara-control-desk-automation">
                <div class="lunara-control-desk-panel-header">
                    <div>
                        <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Journal Automation', 'lunara-film' ); ?></p>
                        <h2><?php esc_html_e( 'Administrator access required', 'lunara-film' ); ?></h2>
                        <p class="lunara-control-desk-subtle"><?php esc_html_e( 'The private intake queue, connection state, and automation audit are restricted to site administrators.', 'lunara-film' ); ?></p>
                    </div>
                </div>
            </section>
            <?php
            return;
        }

        if ( ! class_exists( 'Lunara_Journal_Automation' ) || ! method_exists( 'Lunara_Journal_Automation', 'admin_snapshot' ) ) {
            ?>
            <section class="lunara-control-desk-panel lunara-control-desk-automation">
                <div class="lunara-control-desk-panel-header">
                    <div>
                        <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Journal Automation', 'lunara-film' ); ?></p>
                        <h2><?php esc_html_e( 'Foundation upgrade required', 'lunara-film' ); ?></h2>
                        <p class="lunara-control-desk-subtle"><?php esc_html_e( 'Deploy the Journal Foundation automation release to activate IFTTT status, private capture, Dispatch controls, and alerts here.', 'lunara-film' ); ?></p>
                    </div>
                </div>
            </section>
            <?php
            return;
        }

        $snapshot  = Lunara_Journal_Automation::admin_snapshot();
        $profile   = isset( $snapshot['ifttt_profile'] ) && is_array( $snapshot['ifttt_profile'] ) ? $snapshot['ifttt_profile'] : array();
        $dispatch  = isset( $snapshot['dispatch'] ) && is_array( $snapshot['dispatch'] ) ? $snapshot['dispatch'] : array();
        $counts    = isset( $snapshot['inbox_counts'] ) && is_array( $snapshot['inbox_counts'] ) ? $snapshot['inbox_counts'] : array();
        $workflows = isset( $snapshot['workflows'] ) && is_array( $snapshot['workflows'] ) ? $snapshot['workflows'] : array();
        $inbox     = isset( $snapshot['inbox'] ) && is_array( $snapshot['inbox'] ) ? $snapshot['inbox'] : array();
        $history   = isset( $snapshot['history'] ) && is_array( $snapshot['history'] ) ? $snapshot['history'] : array();
        $endpoints = isset( $snapshot['endpoints'] ) && is_array( $snapshot['endpoints'] ) ? $snapshot['endpoints'] : array();
        $last_run  = isset( $dispatch['last_run'] ) && is_array( $dispatch['last_run'] ) ? $dispatch['last_run'] : array();
        $workflow_descriptions = array(
            'morning_desk'       => __( 'Morning Desk sends one concise operational briefing instead of making you inspect several screens.', 'lunara-film' ),
            'run_lunara'         => __( 'Run Lunara queues the existing asynchronous Dispatch collector from your phone or watch.', 'lunara-film' ),
            'capture_idea'       => __( 'Capture Idea sends a dictated or typed thought into the private Automation Inbox.', 'lunara-film' ),
            'source_radar'       => __( 'Source Radar saves the exact story URL as a private research candidate, never as an automatic post.', 'lunara-film' ),
            'screening_followup' => __( 'Screening Follow-Up preserves your first reaction, craft note, and possible Debrief pairings.', 'lunara-film' ),
            'needs_attention'    => __( 'Needs Attention stays quiet unless Dispatch or Journal validation genuinely fails.', 'lunara-film' ),
        );
        $profile_ready  = ! empty( $profile['configured'] ) && ! empty( $profile['active'] );
        $outbound_ready = ! empty( $snapshot['outbound_configured'] );
        $last_run_state = isset( $last_run['success'] ) ? $last_run['success'] : null;
        $last_run_label = null === $last_run_state
            ? __( 'Waiting', 'lunara-film' )
            : ( $last_run_state ? __( 'Healthy', 'lunara-film' ) : __( 'Attention', 'lunara-film' ) );
        ?>
        <section class="lunara-control-desk-panel lunara-control-desk-automation">
            <div class="lunara-control-desk-panel-header">
                <div>
                    <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Journal Automation', 'lunara-film' ); ?></p>
                    <h2><?php esc_html_e( 'IFTTT Pro+ Operator', 'lunara-film' ); ?></h2>
                    <p class="lunara-control-desk-subtle"><?php esc_html_e( 'A secure transport layer around Lunara: capture privately, run the tested Dispatch queue, surface real problems, and keep every publishing decision in WordPress.', 'lunara-film' ); ?></p>
                </div>
                <div class="lunara-control-desk-status-pill">
                    <strong><?php echo esc_html( ! empty( $snapshot['enabled'] ) ? __( 'Enabled', 'lunara-film' ) : __( 'Paused', 'lunara-film' ) ); ?></strong>
                    <span><?php echo esc_html( 'Foundation ' . ( isset( $snapshot['foundation_version'] ) ? $snapshot['foundation_version'] : '' ) ); ?></span>
                </div>
            </div>

            <div class="lunara-control-desk-status-grid">
                <div class="lunara-control-desk-status-card <?php echo esc_attr( lunara_control_desk_automation_status_class( $profile_ready ) ); ?>">
                    <strong><?php echo esc_html( $profile_ready ? __( 'Ready', 'lunara-film' ) : __( 'Connect', 'lunara-film' ) ); ?></strong>
                    <span><?php esc_html_e( 'Restricted inbound profile', 'lunara-film' ); ?></span>
                </div>
                <div class="lunara-control-desk-status-card <?php echo esc_attr( lunara_control_desk_automation_status_class( $outbound_ready ) ); ?>">
                    <strong><?php echo esc_html( $outbound_ready ? __( 'Ready', 'lunara-film' ) : __( 'Connect', 'lunara-film' ) ); ?></strong>
                    <span><?php esc_html_e( 'Outbound notifications', 'lunara-film' ); ?></span>
                </div>
                <div class="lunara-control-desk-status-card <?php echo esc_attr( lunara_control_desk_automation_status_class( ! empty( $dispatch['active'] ) ) ); ?>">
                    <strong><?php echo esc_html( ! empty( $dispatch['active'] ) ? __( 'Active', 'lunara-film' ) : __( 'Unavailable', 'lunara-film' ) ); ?></strong>
                    <span><?php echo esc_html( sprintf( __( 'Dispatch %s', 'lunara-film' ), isset( $dispatch['version'] ) ? $dispatch['version'] : '' ) ); ?></span>
                </div>
                <div class="lunara-control-desk-status-card <?php echo esc_attr( lunara_control_desk_automation_status_class( false !== $last_run_state ) ); ?>">
                    <strong><?php echo esc_html( $last_run_label ); ?></strong>
                    <span><?php esc_html_e( 'Latest Dispatch report', 'lunara-film' ); ?></span>
                </div>
            </div>
        </section>

        <?php lunara_control_desk_render_dispatch_telemetry( $dispatch ); ?>

        <div class="lunara-control-desk-automation-grid">
            <section class="lunara-control-desk-panel">
                <div class="lunara-control-desk-panel-header">
                    <div>
                        <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Connection Gate', 'lunara-film' ); ?></p>
                        <h3><?php esc_html_e( 'Two credentials, two narrow jobs', 'lunara-film' ); ?></h3>
                    </div>
                </div>
                <ol class="lunara-control-desk-automation-steps">
                    <li>
                        <strong><?php esc_html_e( 'Generate the IFTTT Operator token', 'lunara-film' ); ?></strong>
                        <span><?php esc_html_e( 'This token can capture private signals, queue Dispatch, and request allowlisted notices. It cannot read the inbox and has no publish scope.', 'lunara-film' ); ?></span>
                    </li>
                    <li>
                        <strong><?php esc_html_e( 'Add the outbound Webhooks key to deployment configuration', 'lunara-film' ); ?></strong>
                        <span><?php esc_html_e( 'The secret remains outside the database and is never displayed here.', 'lunara-film' ); ?></span>
                    </li>
                    <li>
                        <strong><?php esc_html_e( 'Connect the six workflows', 'lunara-film' ); ?></strong>
                        <span><?php esc_html_e( 'Use the endpoint recipes below after both status cards read Ready.', 'lunara-film' ); ?></span>
                    </li>
                </ol>
                <?php if ( current_user_can( 'manage_options' ) ) : ?>
                    <p><a class="button button-primary" href="<?php echo esc_url( admin_url( 'edit.php?post_type=journal&page=lunara-journal-bridge' ) ); ?>"><?php esc_html_e( 'Open Journal Bridge Keys', 'lunara-film' ); ?></a></p>
                <?php endif; ?>
            </section>

            <section class="lunara-control-desk-panel">
                <div class="lunara-control-desk-panel-header">
                    <div>
                        <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Live Controls', 'lunara-film' ); ?></p>
                        <h3><?php esc_html_e( 'Verify without publishing', 'lunara-film' ); ?></h3>
                    </div>
                </div>
                <p class="lunara-control-desk-subtle"><?php esc_html_e( 'Both controls only queue background work. They cannot publish, schedule, delete, or alter the public theme.', 'lunara-film' ); ?></p>
                <?php if ( current_user_can( 'manage_options' ) ) : ?>
                    <div class="lunara-control-desk-actions">
                        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                            <?php wp_nonce_field( 'lunara_journal_automation_test' ); ?>
                            <input type="hidden" name="action" value="lunara_journal_automation_test" />
                            <button class="button" type="submit" <?php disabled( ! $outbound_ready ); ?>><?php esc_html_e( 'Send Connection Test', 'lunara-film' ); ?></button>
                        </form>
                        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                            <?php wp_nonce_field( 'lunara_journal_automation_morning_desk' ); ?>
                            <input type="hidden" name="action" value="lunara_journal_automation_morning_desk" />
                            <button class="button button-primary" type="submit" <?php disabled( ! $outbound_ready ); ?>><?php esc_html_e( 'Send Morning Desk Now', 'lunara-film' ); ?></button>
                        </form>
                    </div>
                <?php else : ?>
                    <p><?php esc_html_e( 'Administrator access is required to run connection tests.', 'lunara-film' ); ?></p>
                <?php endif; ?>
            </section>
        </div>

        <section class="lunara-control-desk-panel">
            <div class="lunara-control-desk-panel-header">
                <div>
                    <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Workflow Deck', 'lunara-film' ); ?></p>
                    <h3><?php esc_html_e( 'The six first-release automations', 'lunara-film' ); ?></h3>
                </div>
            </div>
            <div class="lunara-control-desk-automation-workflows">
                <?php foreach ( $workflows as $workflow_id => $workflow ) : ?>
                    <article class="lunara-control-desk-automation-workflow <?php echo ! empty( $workflow['ready'] ) ? 'is-ready' : 'is-pending'; ?>">
                        <span><?php echo esc_html( isset( $workflow['direction'] ) ? strtoupper( $workflow['direction'] ) : '' ); ?></span>
                        <h4><?php echo esc_html( isset( $workflow['label'] ) ? $workflow['label'] : '' ); ?></h4>
                        <p><?php echo esc_html( isset( $workflow_descriptions[ $workflow_id ] ) ? $workflow_descriptions[ $workflow_id ] : '' ); ?></p>
                        <strong><?php echo esc_html( ! empty( $workflow['ready'] ) ? __( 'Connection ready', 'lunara-film' ) : __( 'Awaiting connection', 'lunara-film' ) ); ?></strong>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="lunara-control-desk-panel">
            <div class="lunara-control-desk-panel-header">
                <div>
                    <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Applet Recipes', 'lunara-film' ); ?></p>
                    <h3><?php esc_html_e( 'Copy-safe IFTTT endpoint map', 'lunara-film' ); ?></h3>
                    <p class="lunara-control-desk-subtle"><?php esc_html_e( 'Send the restricted operator token as an Authorization Bearer header. Never place it in a URL.', 'lunara-film' ); ?></p>
                </div>
            </div>
            <div class="lunara-control-desk-automation-endpoints">
                <?php foreach ( $endpoints as $name => $url ) : ?>
                    <?php if ( ! in_array( $name, array( 'capture', 'run_dispatch', 'morning_desk' ), true ) ) : continue; endif; ?>
                    <label>
                        <span><?php echo esc_html( ucwords( str_replace( '_', ' ', $name ) ) ); ?></span>
                        <input type="text" readonly value="<?php echo esc_attr( $url ); ?>" onclick="this.select();" />
                    </label>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="lunara-control-desk-panel">
            <div class="lunara-control-desk-panel-header">
                <div>
                    <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Private Intake', 'lunara-film' ); ?></p>
                    <h3><?php esc_html_e( 'Automation Inbox', 'lunara-film' ); ?></h3>
                    <p class="lunara-control-desk-subtle"><?php echo esc_html( sprintf( __( '%1$d new · %2$d triaged · %3$d archived', 'lunara-film' ), isset( $counts['new'] ) ? $counts['new'] : 0, isset( $counts['triaged'] ) ? $counts['triaged'] : 0, isset( $counts['archived'] ) ? $counts['archived'] : 0 ) ); ?></p>
                </div>
            </div>
            <?php if ( empty( $inbox ) ) : ?>
                <div class="lunara-control-desk-empty"><p><?php esc_html_e( 'No private automation signals have arrived yet.', 'lunara-film' ); ?></p></div>
            <?php else : ?>
                <div class="lunara-control-desk-table-wrap">
                    <table class="widefat striped lunara-control-desk-table">
                        <thead><tr><th><?php esc_html_e( 'Signal', 'lunara-film' ); ?></th><th><?php esc_html_e( 'Context', 'lunara-film' ); ?></th><th><?php esc_html_e( 'Status', 'lunara-film' ); ?></th><th><?php esc_html_e( 'Action', 'lunara-film' ); ?></th></tr></thead>
                        <tbody>
                        <?php foreach ( $inbox as $item ) : ?>
                            <tr>
                                <td><strong><?php echo esc_html( isset( $item['title'] ) ? $item['title'] : '' ); ?></strong><br><span class="lunara-control-desk-subtle"><?php echo esc_html( isset( $item['type'] ) ? strtoupper( $item['type'] ) : '' ); ?></span></td>
                                <td>
                                    <?php if ( ! empty( $item['note'] ) ) : ?><p><?php echo esc_html( $item['note'] ); ?></p><?php endif; ?>
                                    <?php if ( ! empty( $item['source_url'] ) ) : ?><a href="<?php echo esc_url( $item['source_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open exact source', 'lunara-film' ); ?></a><?php endif; ?>
                                </td>
                                <td><?php echo esc_html( isset( $item['status'] ) ? $item['status'] : 'new' ); ?></td>
                                <td>
                                    <?php if ( current_user_can( 'manage_options' ) && ! empty( $item['id'] ) ) : ?>
                                        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                                            <?php wp_nonce_field( 'lunara_journal_automation_update_signal' ); ?>
                                            <input type="hidden" name="action" value="lunara_journal_automation_update_signal" />
                                            <input type="hidden" name="signal_id" value="<?php echo esc_attr( $item['id'] ); ?>" />
                                            <select name="signal_status" aria-label="<?php esc_attr_e( 'Inbox status', 'lunara-film' ); ?>">
                                                <?php foreach ( array( 'new' => __( 'New', 'lunara-film' ), 'triaged' => __( 'Triaged', 'lunara-film' ), 'archived' => __( 'Archived', 'lunara-film' ) ) as $status => $label ) : ?>
                                                    <option value="<?php echo esc_attr( $status ); ?>" <?php selected( isset( $item['status'] ) ? $item['status'] : 'new', $status ); ?>><?php echo esc_html( $label ); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button class="button button-small" type="submit"><?php esc_html_e( 'Save', 'lunara-film' ); ?></button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section class="lunara-control-desk-panel">
            <div class="lunara-control-desk-panel-header">
                <div>
                    <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Audit', 'lunara-film' ); ?></p>
                    <h3><?php esc_html_e( 'Recent automation events', 'lunara-film' ); ?></h3>
                </div>
            </div>
            <?php if ( empty( $history ) ) : ?>
                <div class="lunara-control-desk-empty"><p><?php esc_html_e( 'No automation events have been recorded.', 'lunara-film' ); ?></p></div>
            <?php else : ?>
                <div class="lunara-control-desk-table-wrap">
                    <table class="widefat striped lunara-control-desk-table">
                        <thead><tr><th><?php esc_html_e( 'Time', 'lunara-film' ); ?></th><th><?php esc_html_e( 'Action', 'lunara-film' ); ?></th><th><?php esc_html_e( 'Outcome', 'lunara-film' ); ?></th><th><?php esc_html_e( 'Actor', 'lunara-film' ); ?></th></tr></thead>
                        <tbody>
                        <?php foreach ( $history as $event ) : ?>
                            <tr>
                                <td><?php echo esc_html( isset( $event['created_at'] ) ? $event['created_at'] : '' ); ?></td>
                                <td><code><?php echo esc_html( isset( $event['action'] ) ? $event['action'] : '' ); ?></code></td>
                                <td><?php echo esc_html( isset( $event['outcome'] ) ? $event['outcome'] : '' ); ?></td>
                                <td><?php echo esc_html( isset( $event['profile_id'] ) ? $event['profile_id'] : '' ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section class="lunara-control-desk-panel lunara-control-desk-automation-guardrail">
            <p class="lunara-control-desk-kicker"><?php esc_html_e( 'Permanent Boundary', 'lunara-film' ); ?></p>
            <h3><?php esc_html_e( 'IFTTT transports intent; Lunara decides what is allowed.', 'lunara-film' ); ?></h3>
            <p><?php esc_html_e( 'This bridge cannot publish, schedule, trash, delete, change a theme or plugin, alter cache/CDN settings, or replace an exact source image. Captures remain private until you deliberately act on them.', 'lunara-film' ); ?></p>
        </section>
        <?php
    }
}
