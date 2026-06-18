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

    /** @var Moga_Booking */
    public $booking;

    /** @var Moga_Availability */
    public $availability;

    /** @var Moga_Payment */
    public $payment;

    /** @var Moga_Commission */
    public $commission;

    /** @var Moga_Notification */
    public $notification;

    /** @var Moga_Seat_Map */
    public $seat_map;

    /** @var Moga_Roles */
    public $roles;

    /** @var Moga_Admin */
    public $admin;

    /** @var Moga_Public */
    public $public;


    // ============================================================
    // LOAD DEPENDENCIES
    // ============================================================

    /**
     * Load helper files.
     *
     * @since  1.0.0
     * @return void
     */
    private function load_dependencies() {
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
        add_action( 'init', array( $this, 'register_post_types' ),  5  );
        add_action( 'init', array( $this, 'register_taxonomies' ),  5  );
        add_action( 'init', array( $this, 'register_shortcodes' ),  10 );
        add_action( 'rest_api_init', array( $this, 'register_rest_api' ) );
        add_action( 'wp', array( $this, 'schedule_cron_jobs' ) );

        if ( is_admin() ) {
            add_action( 'init', array( $this, 'boot_admin' ), 10 );
        }

        if ( ! is_admin() ) {
            add_action( 'init', array( $this, 'boot_public' ), 10 );
        }

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
     * Boot admin components.
     *
     * @since  1.0.0
     * @return void
     */
    public function boot_admin() {
        if ( class_exists( 'Moga_Admin' ) ) {
            $this->admin = new Moga_Admin();
        }
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
     * @param  array $schedules
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
    // PLUGIN ACTION LINKS
    // ============================================================

    /**
     * Add links on the Plugins page.
     *
     * @since  1.0.0
     * @param  array $links
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
    public function get_path() { return MOGA_CORE_PATH; }

    /** @return string */
    public function get_url() { return MOGA_CORE_URL; }
}