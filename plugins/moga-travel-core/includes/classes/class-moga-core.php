<?php
/**
 * Main Plugin Core Class
 *
 * This is the heart of the Moga Booking System.
 * It boots all components, registers hooks, and ties
 * every part of the plugin together.
 *
 * Uses the Singleton pattern to ensure only one instance
 * exists throughout the WordPress request lifecycle.
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
 * Class Moga_Core
 */
class Moga_Core {

    // ============================================================
    // SINGLETON
    // ============================================================

    /**
     * Single instance of this class.
     *
     * @since  1.0.0
     * @var    Moga_Core|null
     */
    private static $instance = null;

    /**
     * Get or create the single plugin instance.
     *
     * @since  1.0.0
     * @return Moga_Core
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor — prevents direct instantiation.
     * Use get_instance() instead.
     *
     * @since  1.0.0
     */
    private function __construct() {
        $this->load_dependencies();
        $this->register_hooks();
    }

    /**
     * Prevent cloning of the instance.
     *
     * @since  1.0.0
     */
    private function __clone() {}


    // ============================================================
    // COMPONENT PROPERTIES
    // ============================================================

    /**
     * Booking management class instance.
     *
     * @since 1.0.0
     * @var   Moga_Booking
     */
    public $booking;

    /**
     * Availability management class instance.
     *
     * @since 1.0.0
     * @var   Moga_Availability
     */
    public $availability;

    /**
     * Payment processing class instance.
     *
     * @since 1.0.0
     * @var   Moga_Payment
     */
    public $payment;

    /**
     * Commission management class instance.
     *
     * @since 1.0.0
     * @var   Moga_Commission
     */
    public $commission;

    /**
     * Notification class instance.
     *
     * @since 1.0.0
     * @var   Moga_Notification
     */
    public $notification;

    /**
     * Seat map class instance.
     *
     * @since 1.0.0
     * @var   Moga_Seat_Map
     */
    public $seat_map;

    /**
     * User roles class instance.
     *
     * @since 1.0.0
     * @var   Moga_Roles
     */
    public $roles;

    /**
     * Admin class instance.
     *
     * @since 1.0.0
     * @var   Moga_Admin
     */
    public $admin;

    /**
     * Public-facing class instance.
     *
     * @since 1.0.0
     * @var   Moga_Public
     */
    public $public;


    // ============================================================
    // LOAD DEPENDENCIES
    // ============================================================

    /**
     * Load all helper files that are not autoloaded.
     * Classes are handled by the autoloader in the main file.
     *
     * @since  1.0.0
     * @return void
     */
    private function load_dependencies() {

        // Load helper functions (plain functions, not classes).
        require_once MOGA_CORE_PATH . 'includes/helpers/helper-functions.php';
        require_once MOGA_CORE_PATH . 'includes/helpers/helper-date.php';
        require_once MOGA_CORE_PATH . 'includes/helpers/helper-price.php';
    }


    // ============================================================
    // REGISTER HOOKS
    // ============================================================

    /**
     * Register all WordPress hooks and boot all components.
     *
     * @since  1.0.0
     * @return void
     */
    private function register_hooks() {

        // Boot core components.
        add_action( 'init', array( $this, 'boot_components' ), 0 );

        // Register custom post types.
        add_action( 'init', array( $this, 'register_post_types' ), 5 );

        // Register taxonomies.
        add_action( 'init', array( $this, 'register_taxonomies' ), 5 );

        // Register shortcodes.
        add_action( 'init', array( $this, 'register_shortcodes' ), 10 );

        // Register REST API endpoints.
        add_action( 'rest_api_init', array( $this, 'register_rest_api' ) );

        // Schedule cron jobs.
        add_action( 'wp', array( $this, 'schedule_cron_jobs' ) );

        // Admin-only hooks.
        if ( is_admin() ) {
            add_action( 'init', array( $this, 'boot_admin' ), 10 );
        }

        // Public-facing hooks.
        if ( ! is_admin() ) {
            add_action( 'init', array( $this, 'boot_public' ), 10 );
        }

        // Plugin action links (adds "Settings" link on plugins page).
        add_filter(
            'plugin_action_links_' . MOGA_CORE_BASENAME,
            array( $this, 'add_action_links' )
        );
    }


    // ============================================================
    // BOOT COMPONENTS
    // ============================================================

    /**
     * Instantiate all core component classes.
     * Runs on 'init' hook priority 0 (very early).
     *
     * @since  1.0.0
     * @return void
     */
    // public function boot_components() {

    //     $this->roles        = new Moga_Roles();
    //     $this->booking      = new Moga_Booking();
    //     $this->availability = new Moga_Availability();
    //     $this->payment      = new Moga_Payment();
    //     $this->commission   = new Moga_Commission();
    //     $this->notification = new Moga_Notification();
    //     $this->seat_map     = new Moga_Seat_Map();
    // }
    public function boot_components() {

        if ( class_exists( 'Moga_Roles' ) ) {
            $this->roles = new Moga_Roles();
        }

        if ( class_exists( 'Moga_Booking' ) ) {
            $this->booking = new Moga_Booking();
        }

        if ( class_exists( 'Moga_Availability' ) ) {
            $this->availability = new Moga_Availability();
        }

        if ( class_exists( 'Moga_Payment' ) ) {
            $this->payment = new Moga_Payment();
        }

        if ( class_exists( 'Moga_Commission' ) ) {
            $this->commission = new Moga_Commission();
        }

        if ( class_exists( 'Moga_Notification' ) ) {
            $this->notification = new Moga_Notification();
        }

        if ( class_exists( 'Moga_Seat_Map' ) ) {
            $this->seat_map = new Moga_Seat_Map();
        }
    }

    /**
     * Boot admin-specific components.
     * Only runs in the WordPress admin dashboard.
     *
     * @since  1.0.0
     * @return void
     */
    // public function boot_admin() {
    //     $this->admin = new Moga_Admin();
    // }
    public function boot_admin() {
        if ( class_exists( 'Moga_Admin' ) ) {
            $this->admin = new Moga_Admin();
        }
    }

    /**
     * Boot public-facing components.
     * Only runs on the frontend.
     *
     * @since  1.0.0
     * @return void
     */
    // public function boot_public() {
    //     $this->public = new Moga_Public();
    // }
    public function boot_public() {
        if ( class_exists( 'Moga_Public' ) ) {
            $this->public = new Moga_Public();
        }
    }


    // ============================================================
    // CUSTOM POST TYPES
    // ============================================================

    /**
     * Register all Moga custom post types.
     *
     * @since  1.0.0
     * @return void
     */
    // public function register_post_types() {

    //     Moga_CPT_Property::register();
    //     Moga_CPT_Tour::register();
    //     Moga_CPT_Bus::register();
    //     Moga_CPT_Destination::register();
    //     Moga_CPT_Amenity::register();
    // }
    public function register_post_types() {

        if ( class_exists( 'Moga_CPT_Property' ) )    Moga_CPT_Property::register();
        if ( class_exists( 'Moga_CPT_Tour' ) )         Moga_CPT_Tour::register();
        if ( class_exists( 'Moga_CPT_Bus' ) )          Moga_CPT_Bus::register();
        if ( class_exists( 'Moga_CPT_Destination' ) )  Moga_CPT_Destination::register();
        if ( class_exists( 'Moga_CPT_Amenity' ) )      Moga_CPT_Amenity::register();
    }


    // ============================================================
    // TAXONOMIES
    // ============================================================

    /**
     * Register all Moga taxonomies.
     *
     * @since  1.0.0
     * @return void
     */
    // public function register_taxonomies() {

    //     Moga_Tax_Property_Type::register();
    //     Moga_Tax_Location::register();
    //     Moga_Tax_Tour_Category::register();
    // }
    public function register_taxonomies() {

        if ( class_exists( 'Moga_Tax_Property_Type' ) ) Moga_Tax_Property_Type::register();
        if ( class_exists( 'Moga_Tax_Location' ) )      Moga_Tax_Location::register();
        if ( class_exists( 'Moga_Tax_Tour_Category' ) ) Moga_Tax_Tour_Category::register();
    }


    // ============================================================
    // SHORTCODES
    // ============================================================

    /**
     * Register all Moga shortcodes.
     *
     * @since  1.0.0
     * @return void
     */
    // public function register_shortcodes() {

    //     $search  = new Moga_Shortcode_Search();
    //     $listing = new Moga_Shortcode_Listing();
    //     $booking = new Moga_Shortcode_Booking_Form();

    //     $search->register();
    //     $listing->register();
    //     $booking->register();
    // }
    public function register_shortcodes() {

        if ( class_exists( 'Moga_Shortcode_Search' ) ) {
            ( new Moga_Shortcode_Search() )->register();
        }

        if ( class_exists( 'Moga_Shortcode_Listing' ) ) {
            ( new Moga_Shortcode_Listing() )->register();
        }

        if ( class_exists( 'Moga_Shortcode_Booking_Form' ) ) {
            ( new Moga_Shortcode_Booking_Form() )->register();
        }
    }


    // ============================================================
    // REST API
    // ============================================================

    /**
     * Register all Moga REST API endpoints.
     *
     * @since  1.0.0
     * @return void
     */
    // public function register_rest_api() {

    //     $api = new Moga_Rest_Api();
    //     $api->register_routes();
    // }
    public function register_rest_api() {

        if ( class_exists( 'Moga_Rest_Api' ) ) {
            ( new Moga_Rest_Api() )->register_routes();
        }
    }


    // ============================================================
    // CRON JOBS
    // ============================================================

    /**
     * Schedule recurring cron jobs if not already scheduled.
     *
     * Jobs:
     *   - Expire pending bookings (every 30 minutes)
     *   - Release expired seat locks (every 15 minutes)
     *   - Send booking reminders (daily)
     *   - Process owner payouts (weekly)
     *
     * @since  1.0.0
     * @return void
     */
    public function schedule_cron_jobs() {

        if ( ! wp_next_scheduled( 'moga_expire_pending_bookings' ) ) {
            wp_schedule_event( time(), 'moga_every_30_minutes', 'moga_expire_pending_bookings' );
        }

        if ( ! wp_next_scheduled( 'moga_release_expired_seats' ) ) {
            wp_schedule_event( time(), 'moga_every_15_minutes', 'moga_release_expired_seats' );
        }

        if ( ! wp_next_scheduled( 'moga_send_booking_reminders' ) ) {
            wp_schedule_event( time(), 'daily', 'moga_send_booking_reminders' );
        }

        if ( ! wp_next_scheduled( 'moga_process_payouts' ) ) {
            wp_schedule_event( time(), 'weekly', 'moga_process_payouts' );
        }

        // Register custom cron intervals.
        add_filter( 'cron_schedules', array( $this, 'add_cron_intervals' ) );
    }

    /**
     * Add custom cron intervals WordPress doesn't have by default.
     *
     * @since  1.0.0
     * @param  array $schedules Existing cron schedules.
     * @return array            Modified cron schedules.
     */
    public function add_cron_intervals( $schedules ) {

        $schedules['moga_every_15_minutes'] = array(
            'interval' => 15 * MINUTE_IN_SECONDS,
            'display'  => __( 'Every 15 Minutes', 'moga-travel-core' ),
        );

        $schedules['moga_every_30_minutes'] = array(
            'interval' => 30 * MINUTE_IN_SECONDS,
            'display'  => __( 'Every 30 Minutes', 'moga-travel-core' ),
        );

        return $schedules;
    }


    // ============================================================
    // PLUGIN ACTION LINKS
    // ============================================================

    /**
     * Add useful links on the Plugins page next to the plugin name.
     *
     * @since  1.0.0
     * @param  array $links Default plugin action links.
     * @return array        Modified links with Settings added.
     */
    public function add_action_links( $links ) {

        $custom_links = array(
            '<a href="' . admin_url( 'admin.php?page=moga-settings' ) . '">'
                . __( 'Settings', 'moga-travel-core' )
            . '</a>',
            '<a href="' . admin_url( 'admin.php?page=moga-dashboard' ) . '">'
                . __( 'Dashboard', 'moga-travel-core' )
            . '</a>',
        );

        return array_merge( $custom_links, $links );
    }


    // ============================================================
    // GETTERS
    // ============================================================

    /**
     * Get plugin version.
     *
     * @since  1.0.0
     * @return string
     */
    public function get_version() {
        return MOGA_CORE_VERSION;
    }

    /**
     * Get plugin path.
     *
     * @since  1.0.0
     * @return string
     */
    public function get_path() {
        return MOGA_CORE_PATH;
    }

    /**
     * Get plugin URL.
     *
     * @since  1.0.0
     * @return string
     */
    public function get_url() {
        return MOGA_CORE_URL;
    }
}