<?php
/**
 * Theme Assets Class
 *
 * Handles enqueueing of all CSS and JavaScript files
 * for the frontend and WordPress admin.
 *
 * @package    MogaTravel
 * @subpackage MogaTravel/inc/classes
 * @author     Hatem Frere
 * @since      1.0.0
 */

// Block direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Moga_Assets
 */
class Moga_Assets {

    /**
     * intl-tel-input library version.
     *
     * @since 1.0.0
     * @var   string
     */
    const ITI_VERSION = '29.1.0';

    /**
     * Enqueue all frontend CSS and JS files.
     * Called on 'wp_enqueue_scripts' hook.
     *
     * @since  1.0.0
     * @return void
     */
    public static function enqueue_frontend() {

        self::register_vendor_assets();
        self::enqueue_frontend_styles();
        self::enqueue_frontend_scripts();
    }

    /**
     * Enqueue all admin CSS and JS files.
     * Called on 'admin_enqueue_scripts' hook.
     *
     * @since  1.0.0
     * @return void
     */
    public static function enqueue_admin() {

        self::register_vendor_assets();
        self::enqueue_admin_styles();
        self::enqueue_admin_scripts();
    }


    // ============================================================
    // VENDOR ASSETS — REGISTER ONLY (not enqueued yet)
    // ============================================================

    /**
     * Register all third-party vendor libraries.
     * Registered here but only enqueued when needed.
     *
     * Libraries:
     *   - intl-tel-input v29.1.0 (local files)
     *
     * @since  1.0.0
     * @return void
     */
    private static function register_vendor_assets() {

        $vendor_css = MOGA_THEME_URL . 'assets/css/vendor/';
        $vendor_js  = MOGA_THEME_URL . 'assets/js/vendor/';

        // intl-tel-input CSS.
        wp_register_style(
            'intl-tel-input',
            $vendor_css . 'intl-tel-input/intlTelInput.css',
            array(),
            self::ITI_VERSION
        );

        // intl-tel-input JS.
        wp_register_script(
            'intl-tel-input',
            $vendor_js . 'intl-tel-input/intlTelInput.min.js',
            array(),
            self::ITI_VERSION,
            true
        );
    }


    // ============================================================
    // FRONTEND STYLES
    // ============================================================

    /**
     * Register and enqueue all frontend CSS files.
     *
     * Load order:
     *   1. Google Fonts
     *   2. main.css       — variables, reset, typography, layout
     *   3. components.css — buttons, forms, cards, modals
     *   4. header.css     — header and navigation
     *   5. footer.css     — footer
     *   6. home.css       — homepage hero, search, sections
     *   7. search.css     — search results page
     *   8. booking.css    — booking-specific styles
     *   9. dashboard.css  — owner dashboard styles
     *  10. responsive.css — all media queries (always last)
     *  11. rtl.css        — RTL overrides (if RTL language)
     *
     * @since  1.0.0
     * @return void
     */
    private static function enqueue_frontend_styles() {

        $ver = MOGA_THEME_VERSION;
        $css = MOGA_THEME_URL . 'assets/css/';

        // Google Fonts — Inter + Poppins.
        wp_enqueue_style(
            'moga-google-fonts',
            'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700;800&display=swap',
            array(),
            null
        );

        // Core styles.
        wp_enqueue_style(
            'moga-main',
            $css . 'main.css',
            array( 'moga-google-fonts' ),
            $ver
        );

        // UI components.
        wp_enqueue_style(
            'moga-components',
            $css . 'components.css',
            array( 'moga-main' ),
            $ver
        );

        // Header styles.
        wp_enqueue_style(
            'moga-header',
            $css . 'header.css',
            array( 'moga-main' ),
            $ver
        );

        // Footer styles.
        wp_enqueue_style(
            'moga-footer',
            $css . 'footer.css',
            array( 'moga-main' ),
            $ver
        );

        // Home page styles — only on the homepage.
        if ( is_front_page() || is_page_template( 'page-templates/template-home.php' ) ) {
            wp_enqueue_style(
                'moga-home',
                $css . 'home.css',
                array( 'moga-main', 'moga-components' ),
                $ver
            );
        }

        // Search results page styles.
        if ( self::is_search_page() ) {
            wp_enqueue_style(
                'moga-search',
                $css . 'search.css',
                array( 'moga-main', 'moga-components' ),
                $ver
            );
        }

        // Booking styles — only on booking-related pages.
        if ( self::is_booking_page() ) {
            wp_enqueue_style(
                'moga-booking',
                $css . 'booking.css',
                array( 'moga-main', 'moga-components' ),
                $ver
            );

            // intl-tel-input CSS — phone field on booking pages.
            wp_enqueue_style( 'intl-tel-input' );
        }

        // Dashboard styles — only on dashboard pages.
        if ( self::is_dashboard_page() ) {
            wp_enqueue_style(
                'moga-dashboard',
                $css . 'dashboard.css',
                array( 'moga-main', 'moga-components' ),
                $ver
            );
        }

        // Responsive — always last so it overrides everything.
        wp_enqueue_style(
            'moga-responsive',
            $css . 'responsive.css',
            array( 'moga-main' ),
            $ver
        );

        // RTL support — only if current language is RTL (e.g. Arabic).
        if ( is_rtl() ) {
            wp_enqueue_style(
                'moga-rtl',
                $css . 'rtl.css',
                array( 'moga-main' ),
                $ver
            );
        }
    }


    // ============================================================
    // FRONTEND SCRIPTS
    // ============================================================

    /**
     * Register and enqueue all frontend JS files.
     *
     * @since  1.0.0
     * @return void
     */
    private static function enqueue_frontend_scripts() {

        $ver = MOGA_THEME_VERSION;
        $js  = MOGA_THEME_URL . 'assets/js/';

        // Main theme script — loaded on all pages.
        wp_enqueue_script(
            'moga-main',
            $js . 'main.js',
            array( 'jquery' ),
            $ver,
            true
        );

        // Search results page script.
        if ( self::is_search_page() ) {
            wp_enqueue_script(
                'moga-search',
                $js . 'search.js',
                array( 'jquery', 'moga-main' ),
                $ver,
                true
            );
        }

        // Booking script — only on booking pages.
        if ( self::is_booking_page() ) {
            wp_enqueue_script(
                'moga-booking',
                $js . 'booking.js',
                array( 'jquery', 'moga-main' ),
                $ver,
                true
            );

            // intl-tel-input JS — phone field on booking pages.
            wp_enqueue_script( 'intl-tel-input' );

            // Pass utils.js path to JavaScript for intl-tel-input init.
            wp_localize_script(
                'intl-tel-input',
                'mogaItiData',
                array(
                    'utilsUrl' => MOGA_THEME_URL . 'assets/js/vendor/intl-tel-input/utils.js',
                )
            );
        }

        // Seat map script — only on tour/bus booking pages.
        if ( self::is_seat_map_page() ) {
            wp_enqueue_script(
                'moga-seat-map',
                $js . 'seat-map.js',
                array( 'jquery', 'moga-main' ),
                $ver,
                true
            );
        }

        // Pass PHP data to JavaScript via wp_localize_script().
        wp_localize_script(
            'moga-main',
            'mogaData',
            array(
                'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
                'restUrl'        => rest_url( 'moga/v1/' ),
                'nonce'          => wp_create_nonce( 'moga_nonce' ),
                'siteUrl'        => home_url(),
                'currency'       => get_option( 'moga_currency', 'USD' ),
                'currencySymbol' => get_option( 'moga_currency_symbol', '$' ),
                'isLoggedIn'     => is_user_logged_in(),
                'userId'         => get_current_user_id(),
                'i18n'           => array(
                    'loading'    => __( 'Loading...', 'moga-travel' ),
                    'error'      => __( 'Something went wrong. Please try again.', 'moga-travel' ),
                    'confirm'    => __( 'Are you sure?', 'moga-travel' ),
                    'seatTaken'  => __( 'This seat is already taken.', 'moga-travel' ),
                    'seatLocked' => __( 'This seat is temporarily reserved.', 'moga-travel' ),
                ),
            )
        );
    }


    // ============================================================
    // ADMIN STYLES & SCRIPTS
    // ============================================================

    /**
     * Enqueue admin-only CSS.
     *
     * @since  1.0.0
     * @return void
     */
    private static function enqueue_admin_styles() {

        // intl-tel-input CSS — for phone fields in meta boxes.
        wp_enqueue_style( 'intl-tel-input' );
    }

    /**
     * Enqueue admin-only JS.
     *
     * @since  1.0.0
     * @return void
     */
    private static function enqueue_admin_scripts() {

        // intl-tel-input JS — for phone fields in meta boxes.
        wp_enqueue_script( 'intl-tel-input' );

        // Pass utils.js path to JavaScript for intl-tel-input init in admin.
        wp_localize_script(
            'intl-tel-input',
            'mogaItiData',
            array(
                'utilsUrl' => MOGA_THEME_URL . 'assets/js/vendor/intl-tel-input/utils.js',
            )
        );
    }


    // ============================================================
    // PAGE DETECTION HELPERS
    // ============================================================

    /**
     * Check if current page is the search results page.
     *
     * @since  1.0.0
     * @return bool
     */
    private static function is_search_page() {

        return is_page_template( 'page-templates/template-search.php' )
            || is_page( get_option( 'moga_page_search_results' ) );
    }

    /**
     * Check if current page is a booking-related page.
     *
     * @since  1.0.0
     * @return bool
     */
    private static function is_booking_page() {

        $booking_pages = array(
            get_option( 'moga_page_booking' ),
            get_option( 'moga_page_checkout' ),
            get_option( 'moga_page_booking_confirmation' ),
        );

        return is_page( $booking_pages )
            || is_singular( 'moga_property' )
            || is_singular( 'moga_tour' );
    }

    /**
     * Check if current page is the owner dashboard.
     *
     * @since  1.0.0
     * @return bool
     */
    private static function is_dashboard_page() {

        $dashboard_pages = array(
            get_option( 'moga_page_dashboard' ),
            get_option( 'moga_page_my_account' ),
        );

        return is_page( $dashboard_pages );
    }

    /**
     * Check if current page needs the seat map JS.
     *
     * @since  1.0.0
     * @return bool
     */
    private static function is_seat_map_page() {

        return is_singular( 'moga_tour' )
            || is_page( get_option( 'moga_page_booking' ) );
    }
}