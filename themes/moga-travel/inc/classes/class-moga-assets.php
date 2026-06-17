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
     * Enqueue all frontend CSS and JS files.
     * Called on 'wp_enqueue_scripts' hook.
     *
     * @since  1.0.0
     * @return void
     */
    public static function enqueue_frontend() {

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

        self::enqueue_admin_styles();
        self::enqueue_admin_scripts();
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
     *   6. booking.css    — booking-specific styles
     *   7. dashboard.css  — owner dashboard styles
     *   8. responsive.css — all media queries (always last)
     *   9. rtl.css        — RTL overrides (if RTL language)
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

        // Booking styles — only on booking-related pages.
        if ( self::is_booking_page() ) {
            wp_enqueue_style(
                'moga-booking',
                $css . 'booking.css',
                array( 'moga-main', 'moga-components' ),
                $ver
            );
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
            true // Load in footer.
        );

        // Booking script — only on booking pages.
        if ( self::is_booking_page() ) {
            wp_enqueue_script(
                'moga-booking',
                $js . 'booking.js',
                array( 'jquery', 'moga-main' ),
                $ver,
                true
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
        // This makes WordPress data available as a JS object.
        wp_localize_script(
            'moga-main',
            'mogaData',
            array(
                'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
                'restUrl'     => rest_url( 'moga/v1/' ),
                'nonce'       => wp_create_nonce( 'moga_nonce' ),
                'siteUrl'     => home_url(),
                'currency'    => get_option( 'moga_currency', 'USD' ),
                'currencySymbol' => get_option( 'moga_currency_symbol', '$' ),
                'isLoggedIn'  => is_user_logged_in(),
                'userId'      => get_current_user_id(),
                'i18n'        => array(
                    'loading'     => __( 'Loading...', 'moga-travel' ),
                    'error'       => __( 'Something went wrong. Please try again.', 'moga-travel' ),
                    'confirm'     => __( 'Are you sure?', 'moga-travel' ),
                    'seatTaken'   => __( 'This seat is already taken.', 'moga-travel' ),
                    'seatLocked'  => __( 'This seat is temporarily reserved.', 'moga-travel' ),
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
        // Admin styles will be added in Phase 6 (Admin Panel).
        // Placeholder for now.
    }

    /**
     * Enqueue admin-only JS.
     *
     * @since  1.0.0
     * @return void
     */
    private static function enqueue_admin_scripts() {
        // Admin scripts will be added in Phase 6 (Admin Panel).
        // Placeholder for now.
    }


    // ============================================================
    // PAGE DETECTION HELPERS
    // ============================================================

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