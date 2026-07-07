<?php
/**
 * Main Plugin Core Class
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
 * Class Moga_Core
 */
class Moga_Core {

    // ============================================================
    // SINGLETON
    // ============================================================

    /** @var Moga_Core|null */
    private static $instance = null;

    /**
     * @since  1.0.0
     * @return Moga_Core
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /** @since 1.0.0 */
    private function __construct() {
        $this->load_dependencies();
        $this->register_hooks();
    }

    /** @since 1.0.0 */
    private function __clone() {}


    // ============================================================
    // COMPONENT PROPERTIES
    // ============================================================

    /** @var object|null Booking management instance. */
    public $booking;

    /** @var object|null Availability management instance. */
    public $availability;

    /** @var object|null Payment processing instance. */
    public $payment;

    /** @var object|null Commission management instance. */
    public $commission;

    /** @var object|null Notification instance. */
    public $notification;

    /** @var object|null Seat map instance. */
    public $seat_map;

    /** @var object|null User roles instance. */
    public $roles;

    /** @var object|null Admin instance. */
    public $admin;

    /** @var object|null Public instance. */
    public $public;


    // ============================================================
    // LOAD DEPENDENCIES
    // ============================================================

    /**
     * Load all helper and data files.
     * Classes are handled by the autoloader in the main file.
     *
     * @since  1.0.0
     * @return void
     */
    private function load_dependencies() {

        // Data libraries.
        require_once MOGA_CORE_PATH . 'data/countries.php';
        require_once MOGA_CORE_PATH . 'data/cities.php';

        // Helper functions.
        require_once MOGA_CORE_PATH . 'includes/helpers/helper-functions.php';
        require_once MOGA_CORE_PATH . 'includes/helpers/helper-date.php';
        require_once MOGA_CORE_PATH . 'includes/helpers/helper-price.php';
    }


    // ============================================================
    // REGISTER HOOKS
    // ============================================================

    /**
     * Register all WordPress hooks.
     *
     * @since  1.0.0
     * @return void
     */
    private function register_hooks() {

        add_action( 'init', array( $this, 'boot_components' ),     0  );
        add_action( 'init', array( $this, 'register_post_types' ), 5  );
        add_action( 'init', array( $this, 'register_taxonomies' ), 5  );
        add_action( 'init', array( $this, 'register_shortcodes' ), 10 );
        add_action( 'rest_api_init', array( $this, 'register_rest_api' ) );
        add_action( 'wp', array( $this, 'schedule_cron_jobs' ) );

        // AJAX handlers — runs for both frontend and admin.
        add_action( 'init', array( $this, 'boot_ajax' ), 0 );

        if ( is_admin() ) {
            add_action( 'init', array( $this, 'boot_admin' ), 10 );
        }

        if ( ! is_admin() ) {
            add_action( 'init', array( $this, 'boot_public' ), 10 );
        }

        // Disable Gutenberg for Moga CPTs.
        add_filter(
            'use_block_editor_for_post_type',
            array( $this, 'disable_gutenberg_for_cpts' ),
            10,
            2
        );

        // Hide auto-managed taxonomy boxes from admin UI.
        add_action(
            'add_meta_boxes',
            array( $this, 'remove_taxonomy_meta_boxes' ),
            99
        );

        add_filter(
            'plugin_action_links_' . MOGA_CORE_BASENAME,
            array( $this, 'add_action_links' )
        );
    }


    // ============================================================
    // BOOT COMPONENTS
    // ============================================================

    /**
     * Instantiate core component classes.
     *
     * @since  1.0.0
     * @return void
     */
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
     * Boot AJAX handlers.
     * Runs on init priority 0 — before everything else
     * so AJAX actions are registered early enough.
     *
     * @since  1.0.0
     * @return void
     */
    public function boot_ajax() {
        if ( class_exists( 'Moga_Ajax' ) ) {
            Moga_Ajax::init();
        }
    }

    /**
     * Boot admin components.
     *
     * @since  1.0.0
     * @return void
     */
    public function boot_admin() {

        if ( class_exists( 'Moga_Admin' ) ) {
            $this->admin = new Moga_Admin();
        }

        // Initialize meta boxes.
        if ( class_exists( 'Moga_Admin_Metaboxes' ) ) {
            Moga_Admin_Metaboxes::init();
        }

        // Enqueue admin CSS only on Moga CPT screens.
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
    }

    /**
     * Boot public components.
     *
     * @since  1.0.0
     * @return void
     */
    public function boot_public() {

        if ( class_exists( 'Moga_Public' ) ) {
            $this->public = new Moga_Public();
        }

        // Enqueue plugin public JS with PHP data.
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_assets' ) );
    }


    // ============================================================
    // CUSTOM POST TYPES
    // ============================================================

    /**
     * Register all custom post types.
     *
     * @since  1.0.0
     * @return void
     */
    public function register_post_types() {

        if ( class_exists( 'Moga_CPT_Property' ) )   Moga_CPT_Property::register();
        if ( class_exists( 'Moga_CPT_Tour' ) )        Moga_CPT_Tour::register();
        if ( class_exists( 'Moga_CPT_Bus' ) )         Moga_CPT_Bus::register();
        if ( class_exists( 'Moga_CPT_Destination' ) ) Moga_CPT_Destination::register();
        if ( class_exists( 'Moga_CPT_Amenity' ) )     Moga_CPT_Amenity::register();
    }


    // ============================================================
    // TAXONOMIES
    // ============================================================

    /**
     * Register all taxonomies.
     *
     * @since  1.0.0
     * @return void
     */
    public function register_taxonomies() {

        if ( class_exists( 'Moga_Tax_Property_Type' ) ) Moga_Tax_Property_Type::register();
        if ( class_exists( 'Moga_Tax_Location' ) )      Moga_Tax_Location::register();
        if ( class_exists( 'Moga_Tax_Tour_Category' ) ) Moga_Tax_Tour_Category::register();
    }


    // ============================================================
    // SHORTCODES
    // ============================================================

    /**
     * Register all shortcodes.
     *
     * @since  1.0.0
     * @return void
     */
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
     * Register REST API endpoints.
     *
     * @since  1.0.0
     * @return void
     */
    public function register_rest_api() {

        if ( class_exists( 'Moga_Rest_Api' ) ) {
            ( new Moga_Rest_Api() )->register_routes();
        }
    }


    // ============================================================
    // CRON JOBS
    // ============================================================

    /**
     * Schedule recurring cron jobs.
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

        add_filter( 'cron_schedules', array( $this, 'add_cron_intervals' ) );
    }

    /**
     * Add custom cron intervals.
     *
     * @since  1.0.0
     * @param  array $schedules Existing cron schedules.
     * @return array
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
    // GUTENBERG — DISABLE FOR MOGA CPTs
    // ============================================================

    /**
     * Disable Gutenberg block editor for all Moga custom post types.
     * Forces Classic Editor for better meta box compatibility.
     *
     * @since  1.0.0
     * @param  bool   $use_block_editor Whether to use block editor.
     * @param  string $post_type        Post type name.
     * @return bool
     */
    public function disable_gutenberg_for_cpts( $use_block_editor, $post_type ) {

        $moga_post_types = array(
            'moga_property',
            'moga_tour',
            'moga_bus',
            'moga_destination',
        );

        if ( in_array( $post_type, $moga_post_types, true ) ) {
            return false;
        }

        return $use_block_editor;
    }


    // ============================================================
    // TAXONOMY META BOXES — HIDE AUTO-MANAGED ONES
    // ============================================================

    /**
     * Remove taxonomy meta boxes that are auto-managed
     * by our sync functions. Admins should never touch
     * the Location taxonomy box manually — it is
     * populated automatically when a property or tour
     * is saved via moga_sync_city_to_taxonomy().
     *
     * Property Type and Tour Category boxes remain visible
     * because admins assign these manually.
     *
     * @since  1.0.0
     * @return void
     */
    public function remove_taxonomy_meta_boxes() {

        // Hide Location taxonomy box from Properties.
        remove_meta_box( 'moga_locationdiv', 'moga_property', 'side' );

        // Hide Location taxonomy box from Tours.
        remove_meta_box( 'moga_locationdiv', 'moga_tour', 'side' );
    }


    // ============================================================
    // PUBLIC ASSETS
    // ============================================================

    /**
     * Enqueue plugin public JS and pass PHP data to it.
     * Loads moga-public.js on all frontend pages.
     *
     * @since  1.0.0
     * @return void
     */
    public function enqueue_public_assets() {

        wp_enqueue_script(
            'moga-public',
            MOGA_CORE_URL . 'public/assets/js/moga-public.js',
            array( 'jquery' ),
            MOGA_CORE_VERSION,
            true
        );

        // Pass PHP data to moga-public.js.
        wp_localize_script(
            'moga-public',
            'mogaCoreData',
            array(
                'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
                'nonce'     => wp_create_nonce( 'moga_nonce' ),
                'siteUrl'   => home_url(),
                'searchUrl' => get_option( 'moga_page_search_results' )
                    ? get_permalink( get_option( 'moga_page_search_results' ) )
                    : home_url( '/search-results/' ),
                'currency'  => get_option( 'moga_currency', 'USD' ),
                'i18n'      => array(
                    'loading'              => __( 'Loading...', 'moga-travel-core' ),
                    'error'                => __( 'Something went wrong.', 'moga-travel-core' ),
                    'selectCountryFirst'   => __( '— Select Country First —', 'moga-travel-core' ),
                    'selectProvince'       => __( '— Select Province —', 'moga-travel-core' ),
                    'selectProvinceFirst'  => __( '— Select Province First —', 'moga-travel-core' ),
                    'loadingProvinces'     => __( 'Loading provinces…', 'moga-travel-core' ),
                    'selectCity'           => __( '— Select City —', 'moga-travel-core' ),
                    'selectDistrict'       => __( '— Select District —', 'moga-travel-core' ),
                    'checkingAvailability' => __( 'Checking availability...', 'moga-travel-core' ),
                    'available'            => __( '✅ Available for your dates!', 'moga-travel-core' ),
                    'unavailable'          => __( '❌ Not available for selected dates.', 'moga-travel-core' ),
                    'night'                => __( 'night', 'moga-travel-core' ),
                    'nights'               => __( 'nights', 'moga-travel-core' ),
                    'adult'                => __( 'adult', 'moga-travel-core' ),
                    'adults'               => __( 'adults', 'moga-travel-core' ),
                    'child'                => __( 'child', 'moga-travel-core' ),
                    'children'             => __( 'children', 'moga-travel-core' ),
                    'infant'               => __( 'infant', 'moga-travel-core' ),
                    'infants'              => __( 'infants', 'moga-travel-core' ),
                    'discount'             => __( 'Discount', 'moga-travel-core' ),
                    'taxes'                => __( 'Taxes & fees', 'moga-travel-core' ),
                    'total'                => __( 'Total', 'moga-travel-core' ),
                    'addGuests'            => __( 'Add guests', 'moga-travel-core' ),
                ),
            )
        );
    }


    // ============================================================
    // ADMIN ASSETS
    // ============================================================

    /**
     * Enqueue admin CSS for Moga CPT screens only.
     *
     * @since  1.0.0
     * @return void
     */
    public function enqueue_admin_assets() {

        $screen = get_current_screen();

        if ( ! $screen ) {
            return;
        }

        $moga_post_types = array(
            'moga_property',
            'moga_tour',
            'moga_bus',
            'moga_destination',
        );

        if ( ! in_array( $screen->post_type, $moga_post_types, true ) ) {
            return;
        }

        wp_enqueue_style(
            'moga-admin-style',
            MOGA_CORE_URL . 'admin/assets/css/admin-style.css',
            array(),
            MOGA_CORE_VERSION
        );
    }


    // ============================================================
    // PLUGIN ACTION LINKS
    // ============================================================

    /**
     * Add links on the Plugins page.
     *
     * @since  1.0.0
     * @param  array $links Default plugin action links.
     * @return array
     */
    public function add_action_links( $links ) {

        $custom_links = array(
            '<a href="' . admin_url( 'admin.php?page=moga-settings' ) . '">'
                . __( 'Settings', 'moga-travel-core' ) . '</a>',
            '<a href="' . admin_url( 'admin.php?page=moga-dashboard' ) . '">'
                . __( 'Dashboard', 'moga-travel-core' ) . '</a>',
        );

        return array_merge( $custom_links, $links );
    }


    // ============================================================
    // GETTERS
    // ============================================================

    /** @return string */
    public function get_version() { return MOGA_CORE_VERSION; }

    /** @return string */
    public function get_path()    { return MOGA_CORE_PATH; }

    /** @return string */
    public function get_url()     { return MOGA_CORE_URL; }
}