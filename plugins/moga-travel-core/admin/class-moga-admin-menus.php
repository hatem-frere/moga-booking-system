<?php
/**
 * Admin Menu Registration
 *
 * Registers the top-level Moga admin menu and all submenus.
 * Called from Moga_Admin::init_components().
 *
 * Menu structure:
 *   Moga (top-level)
 *     ├── Dashboard
 *     ├── Properties  → WP CPT list
 *     ├── Tours       → WP CPT list
 *     ├── Buses       → WP CPT list
 *     ├── Bookings
 *     ├── Users
 *     ├── Locations   ← Location Settings wizard + Editor
 *     ├── Reports
 *     └── Settings
 *
 * @package    MogaTravelCore
 * @subpackage MogaTravelCore/admin
 * @author     Hatem Frere
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Moga_Admin_Menus
 */
class Moga_Admin_Menus {

    /**
     * Hook into WordPress.
     *
     * @since  1.0.0
     * @return void
     */
    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'register_menus' ) );
    }

    /**
     * Register all Moga admin menus.
     * Fires on 'admin_menu'.
     *
     * @since  1.0.0
     * @return void
     */
    public static function register_menus() {

        // ---- Top-level Moga menu ----
        add_menu_page(
            __( 'Moga Booking System', 'moga-travel-core' ),
            __( 'Moga', 'moga-travel-core' ),
            'manage_options',
            'moga-dashboard',
            array( __CLASS__, 'render_dashboard' ),
            self::get_menu_icon(),
            30
        );

        // ---- Dashboard (same slug as top-level to rename the auto-generated item) ----
        add_submenu_page(
            'moga-dashboard',
            __( 'Moga Dashboard', 'moga-travel-core' ),
            __( 'Dashboard', 'moga-travel-core' ),
            'manage_options',
            'moga-dashboard',
            array( __CLASS__, 'render_dashboard' )
        );

        // ---- Properties ----
        add_submenu_page(
            'moga-dashboard',
            __( 'Properties', 'moga-travel-core' ),
            __( 'Properties', 'moga-travel-core' ),
            'manage_options',
            'edit.php?post_type=moga_property'
        );

        // ---- Tours ----
        add_submenu_page(
            'moga-dashboard',
            __( 'Tours', 'moga-travel-core' ),
            __( 'Tours', 'moga-travel-core' ),
            'manage_options',
            'edit.php?post_type=moga_tour'
        );

        // ---- Buses ----
        add_submenu_page(
            'moga-dashboard',
            __( 'Buses', 'moga-travel-core' ),
            __( 'Buses', 'moga-travel-core' ),
            'manage_options',
            'edit.php?post_type=moga_bus'
        );

        // ---- Bookings (Phase 5) ----
        add_submenu_page(
            'moga-dashboard',
            __( 'Bookings', 'moga-travel-core' ),
            __( 'Bookings', 'moga-travel-core' ),
            'manage_options',
            'moga-bookings',
            array( __CLASS__, 'render_placeholder' )
        );

        // ---- Users (Phase 5) ----
        add_submenu_page(
            'moga-dashboard',
            __( 'Users', 'moga-travel-core' ),
            __( 'Users', 'moga-travel-core' ),
            'manage_options',
            'moga-users',
            array( __CLASS__, 'render_placeholder' )
        );

        // ---- Location Settings + Editor ----
        // Callback is handled entirely by Moga_Admin_Locations.
        add_submenu_page(
            'moga-dashboard',
            __( 'Location Settings', 'moga-travel-core' ),
            __( 'Locations', 'moga-travel-core' ),
            'manage_options',
            'moga-locations',
            array( 'Moga_Admin_Locations', 'render_page' )
        );

        // ---- Reports (Phase 6) ----
        add_submenu_page(
            'moga-dashboard',
            __( 'Reports', 'moga-travel-core' ),
            __( 'Reports', 'moga-travel-core' ),
            'manage_options',
            'moga-reports',
            array( __CLASS__, 'render_placeholder' )
        );

        // ---- Settings (Phase 6) ----
        add_submenu_page(
            'moga-dashboard',
            __( 'Moga Settings', 'moga-travel-core' ),
            __( 'Settings', 'moga-travel-core' ),
            'manage_options',
            'moga-settings',
            array( 'Moga_Admin_Settings', 'render_page' )
        );
    }


    // ============================================================
    // PAGE RENDERERS
    // ============================================================

    /**
     * Render the main Moga dashboard page.
     *
     * @since  1.0.0
     * @return void
     */
    public static function render_dashboard() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Moga Booking System', 'moga-travel-core' ); ?></h1>
            <p><?php esc_html_e( 'Welcome to the Moga Booking System dashboard.', 'moga-travel-core' ); ?></p>

            <div class="moga-dashboard-cards" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-top:24px;">

                <?php
                $cards = array(
                    array(
                        'label' => __( 'Properties', 'moga-travel-core' ),
                        'count' => wp_count_posts( 'moga_property' )->publish ?? 0,
                        'url'   => admin_url( 'edit.php?post_type=moga_property' ),
                        'color' => '#0073aa',
                    ),
                    array(
                        'label' => __( 'Tours', 'moga-travel-core' ),
                        'count' => wp_count_posts( 'moga_tour' )->publish ?? 0,
                        'url'   => admin_url( 'edit.php?post_type=moga_tour' ),
                        'color' => '#00a651',
                    ),
                    array(
                        'label' => __( 'Bookings', 'moga-travel-core' ),
                        'count' => 0,
                        'url'   => admin_url( 'admin.php?page=moga-bookings' ),
                        'color' => '#f39c12',
                    ),
                );
                foreach ( $cards as $card ) :
                ?>
                    <div style="background:#fff;border:1px solid #e0e0e0;border-radius:8px;padding:20px;text-align:center;">
                        <div style="font-size:2rem;font-weight:700;color:<?php echo esc_attr( $card['color'] ); ?>">
                            <?php echo esc_html( $card['count'] ); ?>
                        </div>
                        <div style="color:#555;margin-top:4px;">
                            <a href="<?php echo esc_url( $card['url'] ); ?>" style="color:inherit;text-decoration:none;">
                                <?php echo esc_html( $card['label'] ); ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>

            </div>
        </div>
        <?php
    }

    /**
     * Render a "coming soon" placeholder for unbuilt pages.
     *
     * @since  1.0.0
     * @return void
     */
    public static function render_placeholder() {
        $page  = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
        $title = ucwords( str_replace( array( 'moga-', '-' ), array( '', ' ' ), $page ) );
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( $title ); ?></h1>
            <div style="background:#fff;border:1px solid #e0e0e0;border-radius:8px;padding:40px;margin-top:16px;text-align:center;color:#888;">
                <p style="font-size:1.1rem;">
                    <?php esc_html_e( 'This section is under development and will be available in a future update.', 'moga-travel-core' ); ?>
                </p>
            </div>
        </div>
        <?php
    }


    // ============================================================
    // HELPER
    // ============================================================

    /**
     * Return the base64-encoded SVG for the Moga admin menu icon.
     * Using a map-pin style icon in the WordPress admin grey colour.
     *
     * @since  1.0.0
     * @return string data:image/svg+xml;base64,... string.
     */
    private static function get_menu_icon() {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#a7aaad" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'
            . '<path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/>'
            . '<circle cx="12" cy="9" r="2.5"/>'
            . '</svg>';
        return 'data:image/svg+xml;base64,' . base64_encode( $svg );
    }
}