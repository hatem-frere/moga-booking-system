<?php
/**
 * Notification System
 *
 * Handles all outbound email notifications sent by the Moga Booking System.
 * Every sent/failed attempt is logged to mg_moga_notifications for the
 * admin Notifications Log dashboard tab.
 *
 * Hooked events (registered in __construct):
 *   moga_booking_created       → guest confirmation + owner alert
 *   moga_booking_confirmed     → guest approval email
 *   moga_booking_cancelled     → guest + owner cancellation email
 *   moga_booking_completed     → guest review-request email
 *   moga_send_booking_reminders (daily cron) → check-in reminder emails
 *
 * All emails use HTML templates from:
 *   plugins/moga-travel-core/templates/emails/
 *
 * @package    MogaTravelCore
 * @subpackage MogaTravelCore/includes/classes
 * @author     Hatem Frere
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Moga_Notification
 */
class Moga_Notification {

    // ============================================================
    // CONSTANTS
    // ============================================================

    /** Days before check-in to send reminder email. */
    const REMINDER_DAYS_BEFORE = 2;

    /** Template directory path. */
    const TEMPLATE_DIR = MOGA_CORE_PATH . 'templates/emails/';


    // ============================================================
    // CONSTRUCTOR — REGISTER HOOKS
    // ============================================================

    /**
     * @since 1.0.0
     */
    public function __construct() {
        // Booking lifecycle hooks.
        add_action( 'moga_booking_created',   array( $this, 'on_booking_created'   ), 10, 2 );
        add_action( 'moga_booking_confirmed', array( $this, 'on_booking_confirmed' ), 10, 2 );
        add_action( 'moga_booking_cancelled', array( $this, 'on_booking_cancelled' ), 10, 2 );
        add_action( 'moga_booking_completed', array( $this, 'on_booking_completed' ), 10, 2 );

        // Daily cron — reminder emails.
        add_action( 'moga_send_booking_reminders', array( $this, 'send_reminders' ) );

        // AJAX resend action (admin only).
        add_action( 'wp_ajax_moga_resend_notification', array( $this, 'ajax_resend_notification' ) );

        // Vendor application hooks.
        add_action( 'moga_vendor_application_received', array( $this, 'on_vendor_application_received' ), 10, 1 );
        add_action( 'moga_vendor_approved',             array( $this, 'on_vendor_approved'             ), 10, 1 );
        add_action( 'moga_vendor_rejected',             array( $this, 'on_vendor_rejected'             ), 10, 1 );
    }


    // ============================================================
    // DB TABLE — CREATE ON DEMAND
    // ============================================================

    /**
     * Create the notifications log table if it doesn't exist.
     * Called from Moga_Activator::run_tables() and also defensively
     * here so the log never fails if the table is missing at runtime.
     *
     * @since  1.0.0
     * @return void
     */
    public static function create_table() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $table          = $wpdb->prefix . 'moga_notifications';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            type VARCHAR(60) NOT NULL,
            recipient_email VARCHAR(100) NOT NULL,
            recipient_name VARCHAR(100) DEFAULT NULL,
            subject VARCHAR(255) NOT NULL,
            booking_id BIGINT(20) UNSIGNED DEFAULT NULL,
            booking_number VARCHAR(32) DEFAULT NULL,
            status ENUM('sent','failed','pending') NOT NULL DEFAULT 'pending',
            error_message TEXT DEFAULT NULL,
            sent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_type (type),
            KEY idx_recipient (recipient_email),
            KEY idx_booking_id (booking_id),
            KEY idx_status (status),
            KEY idx_sent_at (sent_at)
        ) {$charset_collate};";

        dbDelta( $sql );
    }


    // ============================================================
    // BOOKING LIFECYCLE HANDLERS
    // ============================================================

    /**
     * Fires when a new booking is created (status = pending).
     * Sends:
     *  1. Confirmation email to the guest.
     *  2. New booking alert to the property/tour owner.
     *
     * @since  1.0.0
     * @param  int   $booking_id Booking ID.
     * @param  array $booking    Booking row array.
     * @return void
     */
    public function on_booking_created( $booking_id, $booking ) {
        $this->send_booking_confirmation( $booking );
        $this->send_owner_new_booking_alert( $booking );
    }

    /**
     * Fires when a booking status changes to 'confirmed'.
     *
     * @since  1.0.0
     * @param  int   $booking_id Booking ID.
     * @param  array $booking    Booking row array.
     * @return void
     */
    public function on_booking_confirmed( $booking_id, $booking ) {
        $guest = get_userdata( $booking['guest_id'] );
        if ( ! $guest ) return;

        $listing_title = get_the_title( $booking['listing_id'] );
        $subject       = sprintf(
            /* translators: %s: booking number */
            __( '✅ Booking Confirmed — %s', 'moga-travel-core' ),
            $booking['booking_number']
        );

        $message = $this->render_template( 'booking-confirmed', array(
            'booking'       => $booking,
            'guest'         => $guest,
            'listing_title' => $listing_title,
            'site_name'     => get_bloginfo( 'name' ),
            'site_url'      => home_url(),
            'currency'      => get_option( 'moga_currency_symbol', '$' ),
            'dashboard_url' => add_query_arg( 'tab', 'bookings', get_permalink( get_option( 'moga_page_dashboard' ) ) ),
            'subject'       => $subject,
        ) );

        $this->send_email(
            $guest->user_email,
            $guest->display_name,
            $subject,
            $message,
            'booking_approved',
            $booking['id'],
            $booking['booking_number']
        );
    }

    /**
     * Fires when a booking is cancelled.
     *
     * @since  1.0.0
     * @param  int   $booking_id Booking ID.
     * @param  array $booking    Booking row array.
     * @return void
     */
    public function on_booking_cancelled( $booking_id, $booking ) {
        $guest = get_userdata( $booking['guest_id'] );
        $owner = get_userdata( $booking['owner_id'] );

        $listing_title = get_the_title( $booking['listing_id'] );
        $site_name     = get_bloginfo( 'name' );

        $cancel_tpl_data = array(
            'booking'       => $booking,
            'listing_title' => $listing_title,
            'site_name'     => get_bloginfo( 'name' ),
            'site_url'      => home_url(),
            'dashboard_url' => add_query_arg( 'tab', 'bookings', get_permalink( get_option( 'moga_page_dashboard' ) ) ),
        );

        // Email to guest.
        if ( $guest ) {
            $subject = sprintf(
                /* translators: %s: booking number */
                __( '❌ Booking Cancelled — %s', 'moga-travel-core' ),
                $booking['booking_number']
            );
            $message = $this->render_template( 'booking-cancelled', array_merge( $cancel_tpl_data, array(
                'recipient' => $guest,
                'subject'   => $subject,
            ) ) );
            $this->send_email( $guest->user_email, $guest->display_name, $subject, $message, 'booking_cancelled', $booking['id'], $booking['booking_number'] );
        }

        // Email to owner.
        if ( $owner ) {
            $subject = sprintf(
                /* translators: %s: booking number */
                __( 'Booking Cancelled — %s', 'moga-travel-core' ),
                $booking['booking_number']
            );
            $message = $this->render_template( 'booking-cancelled', array_merge( $cancel_tpl_data, array(
                'recipient' => $owner,
                'subject'   => $subject,
            ) ) );
            $this->send_email( $owner->user_email, $owner->display_name, $subject, $message, 'booking_cancelled', $booking['id'], $booking['booking_number'] );
        }
    }

    /**
     * Fires when a booking is marked completed.
     * Sends a review request to the guest.
     *
     * @since  1.0.0
     * @param  int   $booking_id Booking ID.
     * @param  array $booking    Booking row array.
     * @return void
     */
    public function on_booking_completed( $booking_id, $booking ) {
        $guest = get_userdata( $booking['guest_id'] );
        if ( ! $guest ) return;

        $listing_title = get_the_title( $booking['listing_id'] );
        $subject       = sprintf(
            /* translators: %s: listing name */
            __( 'How was your stay at %s? Leave a review!', 'moga-travel-core' ),
            $listing_title
        );
        $review_url = add_query_arg( 'tab', 'reviews', get_permalink( get_option( 'moga_page_dashboard' ) ) );
        $message = $this->render_template( 'review-request', array(
            'booking'       => $booking,
            'guest'         => $guest,
            'listing_title' => $listing_title,
            'site_name'     => get_bloginfo( 'name' ),
            'site_url'      => home_url(),
            'review_url'    => $review_url,
            'subject'       => $subject,
        ) );

        $this->send_email(
            $guest->user_email,
            $guest->display_name,
            $subject,
            $message,
            'review_request',
            $booking['id'],
            $booking['booking_number']
        );
    }


    // ============================================================
    // DEDICATED EMAIL METHODS
    // ============================================================

    /**
     * Send booking confirmation email to the guest.
     *
     * @since  1.0.0
     * @param  array $booking Booking row array.
     * @return void
     */
    public function send_booking_confirmation( $booking ) {
        $guest = get_userdata( $booking['guest_id'] );
        if ( ! $guest ) return;

        $listing_title = get_the_title( $booking['listing_id'] );
        $site_name     = get_bloginfo( 'name' );
        $dashboard_url = get_permalink( get_option( 'moga_page_dashboard' ) );
        $currency      = get_option( 'moga_currency_symbol', '$' );

        $subject = sprintf(
            /* translators: %s: booking number */
            __( 'Booking Request Received — %s', 'moga-travel-core' ),
            $booking['booking_number']
        );

        $message = $this->render_template( 'booking-confirmation', array(
            'booking'       => $booking,
            'guest'         => $guest,
            'listing_title' => $listing_title,
            'site_name'     => $site_name,
            'site_url'      => home_url(),
            'currency'      => $currency,
            'dashboard_url' => add_query_arg( 'tab', 'bookings', $dashboard_url ),
            'subject'       => $subject,
        ) );

        $this->send_email(
            $guest->user_email,
            $guest->display_name,
            $subject,
            $message,
            'booking_confirmation',
            $booking['id'],
            $booking['booking_number']
        );
    }

    /**
     * Send new booking alert to the property/tour owner.
     *
     * @since  1.0.0
     * @param  array $booking Booking row array.
     * @return void
     */
    public function send_owner_new_booking_alert( $booking ) {
        $owner = get_userdata( $booking['owner_id'] );
        $guest = get_userdata( $booking['guest_id'] );
        if ( ! $owner || ! $guest ) return;

        $listing_title = get_the_title( $booking['listing_id'] );
        $dashboard_url = get_permalink( get_option( 'moga_page_dashboard' ) );
        $currency      = get_option( 'moga_currency_symbol', '$' );

        $subject = sprintf(
            /* translators: 1: guest name, 2: listing title */
            __( '🔔 New Booking from %1$s for "%2$s"', 'moga-travel-core' ),
            $guest->display_name,
            $listing_title
        );

        $message = $this->render_template( 'owner-notification', array(
            'booking'       => $booking,
            'owner'         => $owner,
            'guest'         => $guest,
            'listing_title' => $listing_title,
            'site_name'     => get_bloginfo( 'name' ),
            'site_url'      => home_url(),
            'currency'      => $currency,
            'dashboard_url' => add_query_arg( 'tab', 'bookings', $dashboard_url ),
            'subject'       => $subject,
        ) );

        $this->send_email(
            $owner->user_email,
            $owner->display_name,
            $subject,
            $message,
            'booking_confirmation',
            $booking['id'],
            $booking['booking_number']
        );
    }

    /**
     * Send check-in reminder emails.
     * Runs daily via the moga_send_booking_reminders cron job.
     * Sends to guests with confirmed bookings checking in in
     * REMINDER_DAYS_BEFORE days.
     *
     * @since  1.0.0
     * @return void
     */
    public function send_reminders() {
        global $wpdb;

        $prefix      = $wpdb->prefix . 'moga_notifications';
        $bookings_t  = $wpdb->prefix . 'moga_bookings';
        $target_date = gmdate( 'Y-m-d', strtotime( '+' . self::REMINDER_DAYS_BEFORE . ' days' ) );

        $bookings = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$bookings_t}
             WHERE status = 'confirmed'
               AND check_in = %s",
            $target_date
        ), ARRAY_A );

        if ( empty( $bookings ) ) return;

        foreach ( $bookings as $booking ) {
            // Avoid sending duplicate reminders.
            $already_sent = $wpdb->get_var( $wpdb->prepare(
                "SELECT id FROM {$prefix}
                 WHERE type = 'booking_reminder'
                   AND booking_id = %d",
                $booking['id']
            ) );
            if ( $already_sent ) continue;

            $guest = get_userdata( $booking['guest_id'] );
            if ( ! $guest ) continue;

            $listing_title = get_the_title( $booking['listing_id'] );
            $check_in      = date_i18n( get_option( 'date_format' ), strtotime( $booking['check_in'] ) );

            $subject = sprintf(
                /* translators: %s: listing name */
                __( '⏰ Reminder: Your check-in at "%s" is in %d days', 'moga-travel-core' ),
                $listing_title,
                self::REMINDER_DAYS_BEFORE
            );
            $message = sprintf(
                __( "Hello %s,\n\nThis is a friendly reminder that your check-in at \"%s\" is on %s.\n\nBooking reference: %s\n\nWe look forward to welcoming you!\n\n%s", 'moga-travel-core' ),
                $guest->display_name,
                $listing_title,
                $check_in,
                $booking['booking_number'],
                get_bloginfo( 'name' )
            );

            $this->send_email(
                $guest->user_email,
                $guest->display_name,
                $subject,
                $message,
                'booking_reminder',
                $booking['id'],
                $booking['booking_number']
            );
        }
    }


    // ============================================================
    // VENDOR APPLICATION HANDLERS
    // ============================================================

    /**
     * Admin alert when a new vendor application is submitted.
     *
     * @since  1.0.0
     * @param  int $user_id Applicant user ID.
     * @return void
     */
    public function on_vendor_application_received( $user_id ) {
        $applicant  = get_userdata( $user_id );
        $admin_email = get_option( 'admin_email' );
        if ( ! $applicant ) return;

        $subject = sprintf(
            /* translators: %s: applicant name */
            __( '📋 New Vendor Application from %s', 'moga-travel-core' ),
            $applicant->display_name
        );
        $message = sprintf(
            __( "Hello,\n\nA new vendor application has been submitted by:\n\nName: %s\nEmail: %s\n\nPlease review it in your dashboard.\n\n%s", 'moga-travel-core' ),
            $applicant->display_name,
            $applicant->user_email,
            admin_url( 'admin.php?page=moga-vendors' )
        );

        $this->send_email(
            $admin_email,
            get_bloginfo( 'name' ),
            $subject,
            $message,
            'vendor_application',
            null,
            null
        );
    }

    /**
     * Email to vendor when their application is approved.
     *
     * @since  1.0.0
     * @param  int $user_id Vendor user ID.
     * @return void
     */
    public function on_vendor_approved( $user_id ) {
        $vendor = get_userdata( $user_id );
        if ( ! $vendor ) return;

        $subject = sprintf(
            /* translators: %s: site name */
            __( '🎉 Your vendor application on %s has been approved!', 'moga-travel-core' ),
            get_bloginfo( 'name' )
        );
        $message = $this->render_template( 'vendor-approved', array(
            'vendor'        => $vendor,
            'site_name'     => get_bloginfo( 'name' ),
            'site_url'      => home_url(),
            'dashboard_url' => get_permalink( get_option( 'moga_page_dashboard' ) ),
            'subject'       => $subject,
        ) );

        $this->send_email(
            $vendor->user_email,
            $vendor->display_name,
            $subject,
            $message,
            'vendor_approved',
            null,
            null
        );
    }

    /**
     * Email to vendor when their application is rejected.
     *
     * @since  1.0.0
     * @param  int $user_id Vendor user ID.
     * @return void
     */
    public function on_vendor_rejected( $user_id ) {
        $vendor = get_userdata( $user_id );
        if ( ! $vendor ) return;

        $subject = sprintf(
            /* translators: %s: site name */
            __( 'Update on your vendor application — %s', 'moga-travel-core' ),
            get_bloginfo( 'name' )
        );
        $message = $this->render_template( 'vendor-rejected', array(
            'vendor'    => $vendor,
            'site_name' => get_bloginfo( 'name' ),
            'site_url'  => home_url(),
            'subject'   => $subject,
        ) );

        $this->send_email(
            $vendor->user_email,
            $vendor->display_name,
            $subject,
            $message,
            'vendor_rejected',
            null,
            null
        );
    }


    // ============================================================
    // CORE EMAIL SENDER
    // ============================================================

    /**
     * Send an HTML email via wp_mail() and log the result.
     *
     * @since  1.0.0
     * @param  string      $to_email      Recipient email address.
     * @param  string      $to_name       Recipient display name.
     * @param  string      $subject       Email subject.
     * @param  string      $message       HTML message body.
     * @param  string      $type          Notification type key.
     * @param  int|null    $booking_id    Booking ID or null.
     * @param  string|null $booking_number Booking reference or null.
     * @return bool True on success, false on failure.
     */
    public function send_email(
        $to_email,
        $to_name,
        $subject,
        $message,
        $type,
        $booking_id = null,
        $booking_number = null
    ) {
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            sprintf( 'From: %s <%s>', get_bloginfo( 'name' ), get_option( 'admin_email' ) ),
        );

        $sent  = wp_mail( $to_email, $subject, $message, $headers );
        $error = '';

        if ( ! $sent ) {
            global $phpmailer;
            if ( isset( $phpmailer ) && is_object( $phpmailer ) ) {
                $error = $phpmailer->ErrorInfo;
            }
        }

        $this->log(
            $type,
            $to_email,
            $to_name,
            $subject,
            $booking_id,
            $booking_number,
            $sent ? 'sent' : 'failed',
            $error
        );

        return $sent;
    }


    // ============================================================
    // LOGGING
    // ============================================================

    /**
     * Insert a record into mg_moga_notifications.
     * Creates the table first if it doesn't exist.
     *
     * @since  1.0.0
     * @param  string      $type            Notification type key.
     * @param  string      $recipient_email Recipient email.
     * @param  string      $recipient_name  Recipient display name.
     * @param  string      $subject         Email subject.
     * @param  int|null    $booking_id      Booking ID.
     * @param  string|null $booking_number  Booking reference.
     * @param  string      $status          'sent' | 'failed' | 'pending'.
     * @param  string      $error_message   Error string if failed.
     * @return int|false   Inserted row ID or false on failure.
     */
    public function log(
        $type,
        $recipient_email,
        $recipient_name,
        $subject,
        $booking_id,
        $booking_number,
        $status = 'sent',
        $error_message = ''
    ) {
        global $wpdb;

        $table = $wpdb->prefix . 'moga_notifications';

        // Ensure the table exists.
        if ( ! $this->table_exists() ) {
            self::create_table();
        }

        $inserted = $wpdb->insert(
            $table,
            array(
                'type'            => sanitize_key( $type ),
                'recipient_email' => sanitize_email( $recipient_email ),
                'recipient_name'  => sanitize_text_field( $recipient_name ),
                'subject'         => sanitize_text_field( $subject ),
                'booking_id'      => $booking_id ? absint( $booking_id ) : null,
                'booking_number'  => $booking_number ? sanitize_text_field( $booking_number ) : null,
                'status'          => in_array( $status, array( 'sent', 'failed', 'pending' ), true ) ? $status : 'pending',
                'error_message'   => sanitize_textarea_field( $error_message ),
                'sent_at'         => current_time( 'mysql' ),
            ),
            array( '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s' )
        );

        return $inserted ? $wpdb->insert_id : false;
    }

    /**
     * Check if the notifications table exists.
     *
     * @since  1.0.0
     * @return bool
     */
    private function table_exists() {
        global $wpdb;
        $table = $wpdb->prefix . 'moga_notifications';
        return $wpdb->get_var(
            $wpdb->prepare( 'SHOW TABLES LIKE %s', $table )
        ) === $table;
    }


    // ============================================================
    // TEMPLATE RENDERER
    // ============================================================

    /**
     * Render an HTML email template file.
     *
     * Templates live in:
     *   plugins/moga-travel-core/templates/emails/{$template_name}.php
     *
     * Variables in $data are extracted into the template scope.
     * Falls back to null if the template file does not exist.
     *
     * @since  1.0.0
     * @param  string $template_name Template file name without .php.
     * @param  array  $data          Variables to pass to the template.
     * @return string|null           Rendered HTML or null.
     */
    private function render_template( $template_name, $data = array() ) {
        $file = self::TEMPLATE_DIR . sanitize_file_name( $template_name ) . '.php';

        if ( ! file_exists( $file ) ) {
            return null;
        }

        // Extract variables into local scope for the template.
        // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
        extract( $data, EXTR_SKIP );

        ob_start();
        include $file;
        return ob_get_clean();
    }


    // ============================================================
    // PUBLIC UTILITY
    // ============================================================

    /**
     * Fire the moga_booking_created action from outside this class.
     * Called by class-moga-booking.php after a booking is persisted.
     *
     * Usage:
     *   do_action( 'moga_booking_created', $booking_id, $booking_row );
     *
     * @since  1.0.0
     */
    // (No code needed here — hooks are registered in __construct.)

    /**
     * Manually trigger a booking notification by type.
     * Useful for admin resend action in the notifications log.
     *
     * @since  1.0.0
     * @param  string $type       Notification type key.
     * @param  int    $booking_id Booking ID.
     * @return bool|null True on success, false on failure, null if type unsupported.
     */
    public function resend( $type, $booking_id ) {
        global $wpdb;

        $booking = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}moga_bookings WHERE id = %d",
            absint( $booking_id )
        ), ARRAY_A );

        if ( ! $booking ) return false;

        switch ( $type ) {
            case 'booking_confirmation':
                $this->send_booking_confirmation( $booking );
                return true;

            case 'booking_approved':
                $this->on_booking_confirmed( $booking_id, $booking );
                return true;

            case 'booking_cancelled':
                $this->on_booking_cancelled( $booking_id, $booking );
                return true;

            case 'booking_reminder':
                $guest         = get_userdata( $booking['guest_id'] );
                $listing_title = get_the_title( $booking['listing_id'] );
                if ( ! $guest ) return false;
                $subject = sprintf( __( '⏰ Reminder: Your booking at "%s"', 'moga-travel-core' ), $listing_title );
                $message = sprintf(
                    __( "Hello %s,\n\nThis is a reminder for your booking %s at \"%s\".\n\nCheck-in: %s\n\n%s", 'moga-travel-core' ),
                    $guest->display_name,
                    $booking['booking_number'],
                    $listing_title,
                    $booking['check_in'],
                    get_bloginfo( 'name' )
                );
                return $this->send_email(
                    $guest->user_email,
                    $guest->display_name,
                    $subject,
                    $message,
                    'booking_reminder',
                    $booking['id'],
                    $booking['booking_number']
                );

            default:
                return null;
        }
    }

    // ============================================================
    // AJAX RESEND HANDLER
    // ============================================================

    /**
     * AJAX handler — resend a notification from the log.
     * Admin-only. Verifies nonce, finds the log row, calls resend().
     *
     * POST params: nonce, log_id
     *
     * @since  1.0.0
     * @return void (sends JSON)
     */
    public function ajax_resend_notification() {
        if ( ! check_ajax_referer( 'moga_dashboard_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'moga-travel-core' ) ) );
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'moga-travel-core' ) ) );
        }

        $log_id = isset( $_POST['log_id'] ) ? absint( $_POST['log_id'] ) : 0;
        if ( ! $log_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid log ID.', 'moga-travel-core' ) ) );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'moga_notifications';

        $log = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $log_id
        ), ARRAY_A );

        if ( ! $log ) {
            wp_send_json_error( array( 'message' => __( 'Notification log entry not found.', 'moga-travel-core' ) ) );
        }

        $result = null;
        if ( $log['booking_id'] ) {
            $result = $this->resend( $log['type'], (int) $log['booking_id'] );
        }

        if ( $result === false ) {
            wp_send_json_error( array( 'message' => __( 'Failed to resend notification.', 'moga-travel-core' ) ) );
        }

        if ( $result === null ) {
            wp_send_json_error( array( 'message' => __( 'Unsupported notification type for resend.', 'moga-travel-core' ) ) );
        }

        wp_send_json_success( array( 'message' => __( 'Notification resent successfully.', 'moga-travel-core' ) ) );
    }

}
