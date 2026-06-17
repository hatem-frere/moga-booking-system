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

// Block direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Moga_Activator
 *
 * Handles everything that must happen on plugin activation.
 */
class Moga_Activator {

    /**
     * Main activation method.
     * Called by register_activation_hook() in the main plugin file.
     *
     * @since  1.0.0
     * @return void
     */
    public static function activate() {

        // Step 1: Create all custom database tables.
        self::create_tables();

        // Step 2: Set default plugin options if not already set.
        self::set_default_options();

        // Step 3: Create required WordPress pages.
        self::create_pages();

        // Step 4: Set user roles and capabilities.
        self::setup_roles();

        // Step 5: Store the plugin version in DB.
        update_option( 'moga_core_version', MOGA_CORE_VERSION );

        // Step 6: Store activation timestamp.
        update_option( 'moga_core_activated_at', current_time( 'mysql' ) );

        // Step 7: Flush rewrite rules so CPT URLs work immediately.
        flush_rewrite_rules();
    }


    // ============================================================
    // DATABASE TABLES
    // ============================================================

    /**
     * Create all Moga custom database tables.
     *
     * Uses dbDelta() which is the WordPress-safe way to create
     * or upgrade tables — it only makes changes when needed.
     *
     * Tables created:
     *   1. moga_bookings       — All bookings (property, tour, bus)
     *   2. moga_booking_meta   — Extra data per booking
     *   3. moga_availability   — Availability calendar per listing
     *   4. moga_seats          — Bus seat layout and status
     *   5. moga_payments       — Payment records
     *   6. moga_reviews        — Guest reviews
     *   7. moga_commissions    — Platform commission records
     *
     * @since  1.0.0
     * @return void
     */
    private static function create_tables() {
        global $wpdb;

        // Required for dbDelta().
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Get WordPress charset and collation (e.g. utf8mb4_unicode_ci).
        $charset_collate = $wpdb->get_charset_collate();

        // Table name prefix: wp_ + moga_ = wp_moga_
        $prefix = $wpdb->prefix . MOGA_CORE_DB_PREFIX;

        // --------------------------------------------------------
        // Table 1: moga_bookings
        // Stores every booking made on the platform.
        // --------------------------------------------------------
        $sql_bookings = "CREATE TABLE {$prefix}bookings (
            id              BIGINT(20)      UNSIGNED NOT NULL AUTO_INCREMENT,
            booking_number  VARCHAR(32)     NOT NULL UNIQUE,
            booking_type    ENUM('property','tour','bus','rental')
                                            NOT NULL DEFAULT 'property',
            listing_id      BIGINT(20)      UNSIGNED NOT NULL,
            guest_id        BIGINT(20)      UNSIGNED NOT NULL,
            owner_id        BIGINT(20)      UNSIGNED NOT NULL,
            check_in        DATE            NOT NULL,
            check_out       DATE            NOT NULL,
            guests_adults   TINYINT(3)      UNSIGNED NOT NULL DEFAULT 1,
            guests_children TINYINT(3)      UNSIGNED NOT NULL DEFAULT 0,
            guests_infants  TINYINT(3)      UNSIGNED NOT NULL DEFAULT 0,
            total_nights    SMALLINT(5)     UNSIGNED NOT NULL DEFAULT 1,
            price_per_night DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
            subtotal        DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
            discount        DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
            taxes           DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
            total_amount    DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
            currency        VARCHAR(3)      NOT NULL DEFAULT 'USD',
            status          ENUM('pending','confirmed','cancelled',
                                'completed','refunded','no_show')
                                            NOT NULL DEFAULT 'pending',
            payment_status  ENUM('unpaid','paid','partially_paid',
                                'refunded')  NOT NULL DEFAULT 'unpaid',
            payment_method  VARCHAR(50)     DEFAULT NULL,
            special_requests TEXT           DEFAULT NULL,
            cancellation_reason TEXT        DEFAULT NULL,
            created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
                                            ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY     (id),
            KEY idx_booking_number  (booking_number),
            KEY idx_listing_id      (listing_id),
            KEY idx_guest_id        (guest_id),
            KEY idx_owner_id        (owner_id),
            KEY idx_status          (status),
            KEY idx_check_in        (check_in),
            KEY idx_check_out       (check_out),
            KEY idx_created_at      (created_at)
        ) $charset_collate;";

        // --------------------------------------------------------
        // Table 2: moga_booking_meta
        // Stores extra key-value data per booking (flexible).
        // --------------------------------------------------------
        $sql_booking_meta = "CREATE TABLE {$prefix}booking_meta (
            meta_id         BIGINT(20)      UNSIGNED NOT NULL AUTO_INCREMENT,
            booking_id      BIGINT(20)      UNSIGNED NOT NULL,
            meta_key        VARCHAR(255)    NOT NULL,
            meta_value      LONGTEXT        DEFAULT NULL,
            PRIMARY KEY     (meta_id),
            KEY idx_booking_id  (booking_id),
            KEY idx_meta_key    (meta_key)
        ) $charset_collate;";

        // --------------------------------------------------------
        // Table 3: moga_availability
        // Tracks which dates are available, blocked, or booked
        // for each listing (property, tour, bus).
        // --------------------------------------------------------
        $sql_availability = "CREATE TABLE {$prefix}availability (
            id              BIGINT(20)      UNSIGNED NOT NULL AUTO_INCREMENT,
            listing_id      BIGINT(20)      UNSIGNED NOT NULL,
            listing_type    ENUM('property','tour','bus','rental')
                                            NOT NULL DEFAULT 'property',
            date            DATE            NOT NULL,
            status          ENUM('available','booked','blocked','pending')
                                            NOT NULL DEFAULT 'available',
            price_override  DECIMAL(10,2)   DEFAULT NULL,
            min_stay        TINYINT(3)      UNSIGNED DEFAULT NULL,
            booking_id      BIGINT(20)      UNSIGNED DEFAULT NULL,
            note            VARCHAR(255)    DEFAULT NULL,
            PRIMARY KEY     (id),
            UNIQUE KEY idx_listing_date (listing_id, date),
            KEY idx_listing_id      (listing_id),
            KEY idx_date            (date),
            KEY idx_status          (status)
        ) $charset_collate;";

        // --------------------------------------------------------
        // Table 4: moga_seats
        // Stores bus seat layout and reservation status per tour.
        // --------------------------------------------------------
        $sql_seats = "CREATE TABLE {$prefix}seats (
            id              BIGINT(20)      UNSIGNED NOT NULL AUTO_INCREMENT,
            bus_id          BIGINT(20)      UNSIGNED NOT NULL,
            tour_id         BIGINT(20)      UNSIGNED DEFAULT NULL,
            trip_date       DATE            NOT NULL,
            seat_number     VARCHAR(10)     NOT NULL,
            seat_row        TINYINT(3)      UNSIGNED NOT NULL,
            seat_column     TINYINT(3)      UNSIGNED NOT NULL,
            seat_type       ENUM('standard','vip','disabled')
                                            NOT NULL DEFAULT 'standard',
            status          ENUM('available','reserved','booked',
                                'unavailable') NOT NULL DEFAULT 'available',
            booking_id      BIGINT(20)      UNSIGNED DEFAULT NULL,
            guest_id        BIGINT(20)      UNSIGNED DEFAULT NULL,
            reserved_at     DATETIME        DEFAULT NULL,
            reserved_until  DATETIME        DEFAULT NULL,
            PRIMARY KEY     (id),
            UNIQUE KEY idx_bus_seat_date (bus_id, seat_number, trip_date),
            KEY idx_bus_id      (bus_id),
            KEY idx_tour_id     (tour_id),
            KEY idx_trip_date   (trip_date),
            KEY idx_status      (status),
            KEY idx_booking_id  (booking_id)
        ) $charset_collate;";

        // --------------------------------------------------------
        // Table 5: moga_payments
        // Stores all payment transactions.
        // --------------------------------------------------------
        $sql_payments = "CREATE TABLE {$prefix}payments (
            id                  BIGINT(20)      UNSIGNED NOT NULL AUTO_INCREMENT,
            booking_id          BIGINT(20)      UNSIGNED NOT NULL,
            transaction_id      VARCHAR(100)    DEFAULT NULL,
            payment_method      VARCHAR(50)     NOT NULL,
            payment_gateway     VARCHAR(50)     NOT NULL,
            amount              DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
            currency            VARCHAR(3)      NOT NULL DEFAULT 'USD',
            status              ENUM('pending','completed','failed',
                                    'refunded','cancelled')
                                                NOT NULL DEFAULT 'pending',
            gateway_response    LONGTEXT        DEFAULT NULL,
            refund_amount       DECIMAL(10,2)   DEFAULT NULL,
            refund_reason       TEXT            DEFAULT NULL,
            refunded_at         DATETIME        DEFAULT NULL,
            created_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
                                                ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY         (id),
            KEY idx_booking_id      (booking_id),
            KEY idx_transaction_id  (transaction_id),
            KEY idx_status          (status),
            KEY idx_created_at      (created_at)
        ) $charset_collate;";

        // --------------------------------------------------------
        // Table 6: moga_reviews
        // Stores guest reviews for properties and tours.
        // --------------------------------------------------------
        $sql_reviews = "CREATE TABLE {$prefix}reviews (
            id              BIGINT(20)      UNSIGNED NOT NULL AUTO_INCREMENT,
            booking_id      BIGINT(20)      UNSIGNED NOT NULL,
            listing_id      BIGINT(20)      UNSIGNED NOT NULL,
            listing_type    ENUM('property','tour','bus','rental')
                                            NOT NULL DEFAULT 'property',
            guest_id        BIGINT(20)      UNSIGNED NOT NULL,
            rating_overall  TINYINT(1)      UNSIGNED NOT NULL DEFAULT 5,
            rating_cleanliness TINYINT(1)   UNSIGNED DEFAULT NULL,
            rating_location TINYINT(1)      UNSIGNED DEFAULT NULL,
            rating_value    TINYINT(1)      UNSIGNED DEFAULT NULL,
            rating_service  TINYINT(1)      UNSIGNED DEFAULT NULL,
            title           VARCHAR(255)    DEFAULT NULL,
            content         TEXT            DEFAULT NULL,
            owner_reply     TEXT            DEFAULT NULL,
            owner_replied_at DATETIME       DEFAULT NULL,
            status          ENUM('pending','approved','rejected')
                                            NOT NULL DEFAULT 'pending',
            created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY     (id),
            KEY idx_listing_id  (listing_id),
            KEY idx_guest_id    (guest_id),
            KEY idx_booking_id  (booking_id),
            KEY idx_status      (status)
        ) $charset_collate;";

        // --------------------------------------------------------
        // Table 7: moga_commissions
        // Tracks platform commission per booking.
        // --------------------------------------------------------
        $sql_commissions = "CREATE TABLE {$prefix}commissions (
            id                  BIGINT(20)      UNSIGNED NOT NULL AUTO_INCREMENT,
            booking_id          BIGINT(20)      UNSIGNED NOT NULL,
            owner_id            BIGINT(20)      UNSIGNED NOT NULL,
            booking_total       DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
            commission_rate     DECIMAL(5,2)    NOT NULL DEFAULT 10.00,
            commission_amount   DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
            owner_earnings      DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
            status              ENUM('pending','paid','cancelled')
                                                NOT NULL DEFAULT 'pending',
            paid_at             DATETIME        DEFAULT NULL,
            created_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY         (id),
            KEY idx_booking_id  (booking_id),
            KEY idx_owner_id    (owner_id),
            KEY idx_status      (status)
        ) $charset_collate;";

        // Run all table creation SQL through dbDelta().
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
     * Uses add_option() which only inserts if the option
     * does not already exist — safe to run multiple times.
     *
     * @since  1.0.0
     * @return void
     */
    private static function set_default_options() {

        $defaults = array(

            // General settings.
            'moga_currency'             => 'USD',
            'moga_currency_symbol'      => '$',
            'moga_currency_position'    => 'before', // before | after
            'moga_date_format'          => 'Y-m-d',
            'moga_time_format'          => 'H:i',
            'moga_timezone'             => 'Africa/Cairo',

            // Commission settings.
            'moga_commission_rate'      => '10', // percentage
            'moga_commission_type'      => 'percentage', // percentage | fixed

            // Booking settings.
            'moga_booking_expiry'       => '30', // minutes before pending expires
            'moga_min_booking_notice'   => '1',  // days in advance required
            'moga_max_booking_days'     => '365', // max days ahead a guest can book
            'moga_seat_lock_duration'   => '15', // minutes a seat is locked during checkout

            // Payment settings.
            'moga_payment_stripe'       => '0', // enabled/disabled
            'moga_payment_paypal'       => '0',
            'moga_payment_offline'      => '1', // cash on arrival

            // Notification settings.
            'moga_notify_email'         => '1',
            'moga_notify_sms'           => '0',
            'moga_notify_whatsapp'      => '0',
            'moga_admin_email'          => get_option( 'admin_email' ),

            // Map settings.
            'moga_maps_provider'        => 'google', // google | openstreetmap
            'moga_google_maps_key'      => '',

            // Language settings.
            'moga_default_language'     => 'en',
            'moga_rtl_support'          => '1',

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
     * Checks if page already exists before creating.
     *
     * Pages created:
     *   - Search Results  (/search-properties/)
     *   - Booking         (/booking/)
     *   - My Account      (/my-account/)
     *   - Dashboard       (/dashboard/)
     *   - Checkout        (/checkout/)
     *   - Booking Confirm (/booking-confirmation/)
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
     * Create a single WordPress page if it doesn't already exist.
     *
     * @since  1.0.0
     * @param  string $title   Page title.
     * @param  string $slug    Page slug.
     * @param  string $content Page content (shortcode).
     * @return int|WP_Error    Post ID on success, WP_Error on failure.
     */
    private static function create_page_if_not_exists( $title, $slug, $content ) {

        // Check if page with this slug already exists.
        $existing = get_page_by_path( $slug );

        if ( $existing ) {
            // Store the page ID in options for later reference.
            update_option( 'moga_page_' . str_replace( '-', '_', $slug ), $existing->ID );
            return $existing->ID;
        }

        // Create the page.
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
            // Store the page ID for reference throughout the plugin.
            update_option( 'moga_page_' . str_replace( '-', '_', $slug ), $page_id );
        }

        return $page_id;
    }


    // ============================================================
    // USER ROLES
    // ============================================================

    /**
     * Add custom user roles and capabilities.
     *
     * Roles added:
     *   - moga_owner  : Property/tour owner
     *   - moga_guest  : Registered guest (extended subscriber)
     *
     * @since  1.0.0
     * @return void
     */
    private static function setup_roles() {

        // Property / Tour Owner role.
        add_role(
            'moga_owner',
            __( 'Property Owner', 'moga-travel-core' ),
            array(
                'read'                      => true,
                'moga_manage_properties'    => true,
                'moga_manage_tours'         => true,
                'moga_manage_buses'         => true,
                'moga_view_bookings'        => true,
                'moga_manage_availability'  => true,
                'moga_view_earnings'        => true,
            )
        );

        // Guest role (registered traveler).
        add_role(
            'moga_guest',
            __( 'Guest', 'moga-travel-core' ),
            array(
                'read'                  => true,
                'moga_make_booking'     => true,
                'moga_view_bookings'    => true,
                'moga_write_reviews'    => true,
                'moga_manage_wishlist'  => true,
            )
        );

        // Give administrators all Moga capabilities.
        $admin = get_role( 'administrator' );
        if ( $admin ) {
            $admin->add_cap( 'moga_manage_properties' );
            $admin->add_cap( 'moga_manage_tours' );
            $admin->add_cap( 'moga_manage_buses' );
            $admin->add_cap( 'moga_view_bookings' );
            $admin->add_cap( 'moga_manage_bookings' );
            $admin->add_cap( 'moga_manage_availability' );
            $admin->add_cap( 'moga_view_earnings' );
            $admin->add_cap( 'moga_manage_commissions' );
            $admin->add_cap( 'moga_manage_settings' );
            $admin->add_cap( 'moga_make_booking' );
            $admin->add_cap( 'moga_write_reviews' );
            $admin->add_cap( 'moga_manage_wishlist' );
        }
    }
}