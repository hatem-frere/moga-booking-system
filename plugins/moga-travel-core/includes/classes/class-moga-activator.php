<?php
/**
 * Plugin Activator Class
 *
 * Fired during plugin activation. Creates all custom database
 * tables and sets default plugin options.
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
 * Class Moga_Activator
 */
class Moga_Activator {

    /**
     * Main activation method.
     *
     * @since  1.0.0
     * @return void
     */
    public static function activate() {
        self::create_tables();
        self::set_default_options();
        self::create_pages();
        self::setup_roles();
        update_option( 'moga_core_version', MOGA_CORE_VERSION );
        update_option( 'moga_core_activated_at', current_time( 'mysql' ) );
        flush_rewrite_rules();
    }


    // ============================================================
    // DATABASE TABLES
    // ============================================================

    /**
     * Create all custom database tables.
     * Uses dbDelta() — safe to run multiple times.
     * IMPORTANT: Every ENUM must be on a single line for dbDelta.
     *
     * @since  1.0.0
     * @return void
     */
    private static function create_tables() {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();
        $prefix          = $wpdb->prefix . MOGA_CORE_DB_PREFIX;

        // --------------------------------------------------------
        // Table 1: moga_bookings
        // --------------------------------------------------------
        $sql_bookings = "CREATE TABLE {$prefix}bookings (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            booking_number VARCHAR(32) NOT NULL UNIQUE,
            booking_type ENUM('property','tour','bus','rental') NOT NULL DEFAULT 'property',
            listing_id BIGINT(20) UNSIGNED NOT NULL,
            guest_id BIGINT(20) UNSIGNED NOT NULL,
            owner_id BIGINT(20) UNSIGNED NOT NULL,
            check_in DATE NOT NULL,
            check_out DATE NOT NULL,
            guests_adults TINYINT(3) UNSIGNED NOT NULL DEFAULT 1,
            guests_children TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,
            guests_infants TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,
            total_nights SMALLINT(5) UNSIGNED NOT NULL DEFAULT 1,
            price_per_night DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            discount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            taxes DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            currency VARCHAR(3) NOT NULL DEFAULT 'USD',
            status ENUM('pending','confirmed','cancelled','completed','refunded','no_show') NOT NULL DEFAULT 'pending',
            payment_status ENUM('unpaid','paid','partially_paid','refunded') NOT NULL DEFAULT 'unpaid',
            payment_method VARCHAR(50) DEFAULT NULL,
            special_requests TEXT DEFAULT NULL,
            cancellation_reason TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_booking_number (booking_number),
            KEY idx_listing_id (listing_id),
            KEY idx_guest_id (guest_id),
            KEY idx_owner_id (owner_id),
            KEY idx_status (status),
            KEY idx_check_in (check_in),
            KEY idx_check_out (check_out),
            KEY idx_created_at (created_at)
        ) $charset_collate;";

        // --------------------------------------------------------
        // Table 2: moga_booking_meta
        // --------------------------------------------------------
        $sql_booking_meta = "CREATE TABLE {$prefix}booking_meta (
            meta_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            booking_id BIGINT(20) UNSIGNED NOT NULL,
            meta_key VARCHAR(255) NOT NULL,
            meta_value LONGTEXT DEFAULT NULL,
            PRIMARY KEY (meta_id),
            KEY idx_booking_id (booking_id),
            KEY idx_meta_key (meta_key)
        ) $charset_collate;";

        // --------------------------------------------------------
        // Table 3: moga_availability
        // --------------------------------------------------------
        $sql_availability = "CREATE TABLE {$prefix}availability (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            listing_id BIGINT(20) UNSIGNED NOT NULL,
            listing_type ENUM('property','tour','bus','rental') NOT NULL DEFAULT 'property',
            date DATE NOT NULL,
            status ENUM('available','booked','blocked','pending') NOT NULL DEFAULT 'available',
            price_override DECIMAL(10,2) DEFAULT NULL,
            min_stay TINYINT(3) UNSIGNED DEFAULT NULL,
            booking_id BIGINT(20) UNSIGNED DEFAULT NULL,
            note VARCHAR(255) DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY idx_listing_date (listing_id, date),
            KEY idx_listing_id (listing_id),
            KEY idx_date (date),
            KEY idx_status (status)
        ) $charset_collate;";

        // --------------------------------------------------------
        // Table 4: moga_seats
        // --------------------------------------------------------
        $sql_seats = "CREATE TABLE {$prefix}seats (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            bus_id BIGINT(20) UNSIGNED NOT NULL,
            tour_id BIGINT(20) UNSIGNED DEFAULT NULL,
            trip_date DATE NOT NULL,
            seat_number VARCHAR(10) NOT NULL,
            seat_row TINYINT(3) UNSIGNED NOT NULL,
            seat_column TINYINT(3) UNSIGNED NOT NULL,
            seat_type ENUM('standard','vip','disabled') NOT NULL DEFAULT 'standard',
            status ENUM('available','reserved','booked','unavailable') NOT NULL DEFAULT 'available',
            booking_id BIGINT(20) UNSIGNED DEFAULT NULL,
            guest_id BIGINT(20) UNSIGNED DEFAULT NULL,
            reserved_at DATETIME DEFAULT NULL,
            reserved_until DATETIME DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY idx_bus_seat_date (bus_id, seat_number, trip_date),
            KEY idx_bus_id (bus_id),
            KEY idx_tour_id (tour_id),
            KEY idx_trip_date (trip_date),
            KEY idx_status (status),
            KEY idx_booking_id (booking_id)
        ) $charset_collate;";

        // --------------------------------------------------------
        // Table 5: moga_payments
        // --------------------------------------------------------
        $sql_payments = "CREATE TABLE {$prefix}payments (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            booking_id BIGINT(20) UNSIGNED NOT NULL,
            transaction_id VARCHAR(100) DEFAULT NULL,
            payment_method VARCHAR(50) NOT NULL,
            payment_gateway VARCHAR(50) NOT NULL,
            amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            currency VARCHAR(3) NOT NULL DEFAULT 'USD',
            status ENUM('pending','completed','failed','refunded','cancelled') NOT NULL DEFAULT 'pending',
            gateway_response LONGTEXT DEFAULT NULL,
            refund_amount DECIMAL(10,2) DEFAULT NULL,
            refund_reason TEXT DEFAULT NULL,
            refunded_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_booking_id (booking_id),
            KEY idx_transaction_id (transaction_id),
            KEY idx_status (status),
            KEY idx_created_at (created_at)
        ) $charset_collate;";

        // --------------------------------------------------------
        // Table 6: moga_reviews
        // --------------------------------------------------------
        $sql_reviews = "CREATE TABLE {$prefix}reviews (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            booking_id BIGINT(20) UNSIGNED NOT NULL,
            listing_id BIGINT(20) UNSIGNED NOT NULL,
            listing_type ENUM('property','tour','bus','rental') NOT NULL DEFAULT 'property',
            guest_id BIGINT(20) UNSIGNED NOT NULL,
            rating_overall TINYINT(1) UNSIGNED NOT NULL DEFAULT 5,
            rating_cleanliness TINYINT(1) UNSIGNED DEFAULT NULL,
            rating_location TINYINT(1) UNSIGNED DEFAULT NULL,
            rating_value TINYINT(1) UNSIGNED DEFAULT NULL,
            rating_service TINYINT(1) UNSIGNED DEFAULT NULL,
            title VARCHAR(255) DEFAULT NULL,
            content TEXT DEFAULT NULL,
            owner_reply TEXT DEFAULT NULL,
            owner_replied_at DATETIME DEFAULT NULL,
            status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_listing_id (listing_id),
            KEY idx_guest_id (guest_id),
            KEY idx_booking_id (booking_id),
            KEY idx_status (status)
        ) $charset_collate;";

        // --------------------------------------------------------
        // Table 7: moga_commissions
        // --------------------------------------------------------
        $sql_commissions = "CREATE TABLE {$prefix}commissions (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            booking_id BIGINT(20) UNSIGNED NOT NULL,
            owner_id BIGINT(20) UNSIGNED NOT NULL,
            booking_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            commission_rate DECIMAL(5,2) NOT NULL DEFAULT 10.00,
            commission_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            owner_earnings DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            status ENUM('pending','paid','cancelled') NOT NULL DEFAULT 'pending',
            paid_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_booking_id (booking_id),
            KEY idx_owner_id (owner_id),
            KEY idx_status (status)
        ) $charset_collate;";

        dbDelta( $sql_bookings );
        dbDelta( $sql_booking_meta );
        dbDelta( $sql_availability );
        dbDelta( $sql_seats );
        dbDelta( $sql_payments );
        dbDelta( $sql_reviews );
        dbDelta( $sql_commissions );
    }


    // ============================================================
    // DEFAULT OPTIONS
    // ============================================================

    /**
     * Set default plugin options on first activation.
     *
     * @since  1.0.0
     * @return void
     */
    private static function set_default_options() {

        $defaults = array(
            'moga_currency'           => 'USD',
            'moga_currency_symbol'    => '$',
            'moga_currency_position'  => 'before',
            'moga_date_format'        => 'Y-m-d',
            'moga_time_format'        => 'H:i',
            'moga_timezone'           => 'Africa/Cairo',
            'moga_commission_rate'    => '10',
            'moga_commission_type'    => 'percentage',
            'moga_booking_expiry'     => '30',
            'moga_min_booking_notice' => '1',
            'moga_max_booking_days'   => '365',
            'moga_seat_lock_duration' => '15',
            'moga_payment_stripe'     => '0',
            'moga_payment_paypal'     => '0',
            'moga_payment_offline'    => '1',
            'moga_notify_email'       => '1',
            'moga_notify_sms'         => '0',
            'moga_notify_whatsapp'    => '0',
            'moga_admin_email'        => get_option( 'admin_email' ),
            'moga_maps_provider'      => 'google',
            'moga_google_maps_key'    => '',
            'moga_default_language'   => 'en',
            'moga_rtl_support'        => '1',
        );

        foreach ( $defaults as $key => $value ) {
            add_option( $key, $value );
        }
    }


    // ============================================================
    // CREATE PAGES
    // ============================================================

    /**
     * Create required WordPress pages on activation.
     *
     * @since  1.0.0
     * @return void
     */
    private static function create_pages() {

        $pages = array(
            array(
                'title'   => __( 'Search Results', 'moga-travel-core' ),
                'slug'    => 'search-results',
                'content' => '[moga_search_results]',
            ),
            array(
                'title'   => __( 'Booking', 'moga-travel-core' ),
                'slug'    => 'booking',
                'content' => '[moga_booking_form]',
            ),
            array(
                'title'   => __( 'My Account', 'moga-travel-core' ),
                'slug'    => 'my-account',
                'content' => '[moga_account]',
            ),
            array(
                'title'   => __( 'Owner Dashboard', 'moga-travel-core' ),
                'slug'    => 'dashboard',
                'content' => '[moga_dashboard]',
            ),
            array(
                'title'   => __( 'Checkout', 'moga-travel-core' ),
                'slug'    => 'checkout',
                'content' => '[moga_checkout]',
            ),
            array(
                'title'   => __( 'Booking Confirmation', 'moga-travel-core' ),
                'slug'    => 'booking-confirmation',
                'content' => '[moga_booking_confirmation]',
            ),
        );

        foreach ( $pages as $page ) {
            self::create_page_if_not_exists(
                $page['title'],
                $page['slug'],
                $page['content']
            );
        }
    }

    /**
     * Create a single page if it does not already exist.
     *
     * @since  1.0.0
     * @param  string $title   Page title.
     * @param  string $slug    Page slug.
     * @param  string $content Page content.
     * @return int|WP_Error
     */
    private static function create_page_if_not_exists( $title, $slug, $content ) {

        $existing = get_page_by_path( $slug );

        if ( $existing ) {
            update_option(
                'moga_page_' . str_replace( '-', '_', $slug ),
                $existing->ID
            );
            return $existing->ID;
        }

        $page_id = wp_insert_post( array(
            'post_title'     => $title,
            'post_name'      => $slug,
            'post_content'   => $content,
            'post_status'    => 'publish',
            'post_type'      => 'page',
            'post_author'    => 1,
            'comment_status' => 'closed',
        ) );

        if ( ! is_wp_error( $page_id ) ) {
            update_option(
                'moga_page_' . str_replace( '-', '_', $slug ),
                $page_id
            );
        }

        return $page_id;
    }


    // ============================================================
    // USER ROLES
    // ============================================================

    /**
     * Add custom user roles and capabilities.
     *
     * @since  1.0.0
     * @return void
     */
    private static function setup_roles() {

        add_role(
            'moga_owner',
            __( 'Property Owner', 'moga-travel-core' ),
            array(
                'read'                     => true,
                'moga_manage_properties'   => true,
                'moga_manage_tours'        => true,
                'moga_manage_buses'        => true,
                'moga_view_bookings'       => true,
                'moga_manage_availability' => true,
                'moga_view_earnings'       => true,
            )
        );

        add_role(
            'moga_guest',
            __( 'Guest', 'moga-travel-core' ),
            array(
                'read'                 => true,
                'moga_make_booking'    => true,
                'moga_view_bookings'   => true,
                'moga_write_reviews'   => true,
                'moga_manage_wishlist' => true,
            )
        );

        $admin = get_role( 'administrator' );
        if ( $admin ) {
            $caps = array(
                'moga_manage_properties',
                'moga_manage_tours',
                'moga_manage_buses',
                'moga_view_bookings',
                'moga_manage_bookings',
                'moga_manage_availability',
                'moga_view_earnings',
                'moga_manage_commissions',
                'moga_manage_settings',
                'moga_make_booking',
                'moga_write_reviews',
                'moga_manage_wishlist',
            );
            foreach ( $caps as $cap ) {
                $admin->add_cap( $cap );
            }
        }
    }
}