<?php
/**
 * Plugin Deactivator Class
 *
 * Fired during plugin deactivation. Clears scheduled cron jobs
 * and flushes rewrite rules. Does NOT delete any data —
 * data deletion happens only in uninstall.php.
 *
 * @package    MogaTravelCore
 * @subpackage MogaTravelCore/includes/classes
 * @author     Hatem Frere
 * @since      1.0.0
 */

// Block direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Moga_Deactivator
 */
class Moga_Deactivator {

    /**
     * Main deactivation method.
     * Called by register_deactivation_hook() in the main plugin file.
     *
     * @since  1.0.0
     * @return void
     */
    public static function deactivate() {

        // Step 1: Clear all scheduled cron jobs.
        self::clear_cron_jobs();

        // Step 2: Clear any transients we created.
        self::clear_transients();

        // Step 3: Flush rewrite rules to remove CPT URLs.
        flush_rewrite_rules();

        // Step 4: Store deactivation timestamp.
        update_option( 'moga_core_deactivated_at', current_time( 'mysql' ) );
    }


    /**
     * Remove all Moga scheduled cron jobs.
     *
     * @since  1.0.0
     * @return void
     */
    private static function clear_cron_jobs() {

        $cron_hooks = array(
            'moga_expire_pending_bookings',
            'moga_release_expired_seats',
            'moga_send_booking_reminders',
            'moga_process_payouts',
            'moga_cleanup_transients',
        );

        foreach ( $cron_hooks as $hook ) {
            $timestamp = wp_next_scheduled( $hook );
            if ( $timestamp ) {
                wp_unschedule_event( $timestamp, $hook );
            }
        }
    }


    /**
     * Delete all Moga transients from the database.
     *
     * @since  1.0.0
     * @return void
     */
    private static function clear_transients() {
        global $wpdb;

        // Delete all transients with our prefix.
        $wpdb->query(
            "DELETE FROM {$wpdb->options}
             WHERE option_name LIKE '_transient_moga_%'
             OR option_name LIKE '_transient_timeout_moga_%'"
        );
    }
}