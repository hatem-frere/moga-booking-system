<?php
/**
 * Admin Settings Page
 *
 * Handles the Moga plugin settings panel.
 * Full implementation is Phase 6.
 * This shell registers the page and renders a structured
 * placeholder so the Settings menu item is functional.
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
 * Class Moga_Admin_Settings
 */
class Moga_Admin_Settings {

    /**
     * Hook into WordPress.
     *
     * @since  1.0.0
     * @return void
     */
    public static function init() {
        add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
    }

    /**
     * Register plugin settings with the WordPress Settings API.
     * Groups are defined here so Phase 6 can add fields without restructuring.
     *
     * @since  1.0.0
     * @return void
     */
    public static function register_settings() {

        // General group.
        register_setting( 'moga_settings_general', 'moga_currency' );
        register_setting( 'moga_settings_general', 'moga_currency_symbol' );
        register_setting( 'moga_settings_general', 'moga_currency_position' );
        register_setting( 'moga_settings_general', 'moga_timezone' );
        register_setting( 'moga_settings_general', 'moga_date_format' );

        // Booking group.
        register_setting( 'moga_settings_booking', 'moga_commission_rate' );
        register_setting( 'moga_settings_booking', 'moga_commission_type' );
        register_setting( 'moga_settings_booking', 'moga_booking_expiry' );
        register_setting( 'moga_settings_booking', 'moga_min_booking_notice' );
        register_setting( 'moga_settings_booking', 'moga_max_booking_days' );
        register_setting( 'moga_settings_booking', 'moga_seat_lock_duration' );

        // Payment group.
        register_setting( 'moga_settings_payment', 'moga_payment_stripe' );
        register_setting( 'moga_settings_payment', 'moga_payment_paypal' );
        register_setting( 'moga_settings_payment', 'moga_payment_offline' );

        // Notifications group.
        register_setting( 'moga_settings_notifications', 'moga_notify_email' );
        register_setting( 'moga_settings_notifications', 'moga_notify_sms' );
        register_setting( 'moga_settings_notifications', 'moga_notify_whatsapp' );
        register_setting( 'moga_settings_notifications', 'moga_admin_email' );

        // Maps group.
        register_setting( 'moga_settings_maps', 'moga_maps_provider' );

        // Location / GeoNames username (buyer sets this in their install).
        register_setting( 'moga_settings_location', 'moga_geonames_username' );
    }

    /**
     * Render the settings page.
     * Called by the admin menu callback.
     *
     * @since  1.0.0
     * @return void
     */
    public static function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'moga-travel-core' ) );
        }

        $tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'general';

        $tabs = array(
            'general'       => __( 'General', 'moga-travel-core' ),
            'booking'       => __( 'Booking', 'moga-travel-core' ),
            'payment'       => __( 'Payments', 'moga-travel-core' ),
            'notifications' => __( 'Notifications', 'moga-travel-core' ),
            'maps'          => __( 'Maps', 'moga-travel-core' ),
        );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Moga Settings', 'moga-travel-core' ); ?></h1>

            <nav class="nav-tab-wrapper wp-clearfix" style="margin-bottom:0;">
                <?php foreach ( $tabs as $slug => $label ) : ?>
                    <a
                        href="<?php echo esc_url( admin_url( 'admin.php?page=moga-settings&tab=' . $slug ) ); ?>"
                        class="nav-tab<?php echo $tab === $slug ? ' nav-tab-active' : ''; ?>"
                    ><?php echo esc_html( $label ); ?></a>
                <?php endforeach; ?>
            </nav>

            <div style="background:#fff;border:1px solid #c3c4c7;border-top:none;padding:30px;border-radius:0 0 4px 4px;">
                <div style="text-align:center;padding:40px 0;color:#888;">
                    <p style="font-size:1.1rem;margin:0;">
                        <?php
                        printf(
                            /* translators: %s: tab name */
                            esc_html__( '%s settings are coming in Phase 6.', 'moga-travel-core' ),
                            '<strong>' . esc_html( $tabs[ $tab ] ?? '' ) . '</strong>'
                        );
                        ?>
                    </p>
                    <p style="margin:8px 0 0;">
                        <?php esc_html_e( 'Default values are already active and the plugin is fully functional.', 'moga-travel-core' ); ?>
                    </p>
                </div>
            </div>
        </div>
        <?php
    }
}