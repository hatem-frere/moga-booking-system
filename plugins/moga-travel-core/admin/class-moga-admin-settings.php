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

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Class Moga_Admin_Settings
 */
class Moga_Admin_Settings
{

    /**
     * Hook into WordPress.
     *
     * @since  1.0.0
     * @return void
     */
    public static function init()
    {
        add_action('admin_init', array(__CLASS__, 'register_settings'));
    }

    /**
     * Register plugin settings with the WordPress Settings API.
     * Groups are defined here so Phase 6 can add fields without restructuring.
     *
     * @since  1.0.0
     * @return void
     */
    public static function register_settings()
    {

        // General group.
        register_setting('moga_settings_general', 'moga_currency');
        register_setting('moga_settings_general', 'moga_currency_symbol');
        register_setting('moga_settings_general', 'moga_currency_position');
        register_setting('moga_settings_general', 'moga_timezone');
        register_setting('moga_settings_general', 'moga_date_format');

        // Booking group.
        register_setting('moga_settings_booking', 'moga_commission_rate', array('sanitize_callback' => 'floatval'));
        register_setting('moga_settings_booking', 'moga_commission_type', array('sanitize_callback' => 'sanitize_key'));
        register_setting('moga_settings_booking', 'moga_booking_expiry', array('sanitize_callback' => 'absint'));
        register_setting('moga_settings_booking', 'moga_min_booking_notice', array('sanitize_callback' => 'absint'));
        register_setting('moga_settings_booking', 'moga_max_booking_days', array('sanitize_callback' => 'absint'));
        register_setting('moga_settings_booking', 'moga_seat_lock_duration', array('sanitize_callback' => 'absint'));
        register_setting('moga_settings_booking', 'moga_deposit_floor_percent', array('sanitize_callback' => array(__CLASS__, 'sanitize_deposit_floor_percent')));
        register_setting('moga_settings_booking', 'moga_cancellation_fee_percent', array('sanitize_callback' => array(__CLASS__, 'sanitize_cancellation_fee_percent')));
        register_setting('moga_settings_booking', 'moga_balance_due_days', array('sanitize_callback' => 'absint'));

        // Payment group.
        register_setting('moga_settings_payment', 'moga_payment_stripe', array('sanitize_callback' => 'rest_sanitize_boolean'));
        register_setting('moga_settings_payment', 'moga_payment_paypal', array('sanitize_callback' => 'rest_sanitize_boolean'));
        register_setting('moga_settings_payment', 'moga_payment_offline', array('sanitize_callback' => 'rest_sanitize_boolean'));

        // Notifications group.
        register_setting('moga_settings_notifications', 'moga_notify_email');
        register_setting('moga_settings_notifications', 'moga_notify_sms');
        register_setting('moga_settings_notifications', 'moga_notify_whatsapp');
        register_setting('moga_settings_notifications', 'moga_admin_email');

        // Maps group.
        register_setting('moga_settings_maps', 'moga_maps_provider');

        // Location / GeoNames username (buyer sets this in their install).
        register_setting('moga_settings_location', 'moga_geonames_username');

        // Contact group — site-wide contact info shown on the Contact
        // Us page (themes/moga-travel/page-templates/template-contact.php).
        // These were previously only editable via direct database access.
        register_setting('moga_settings_contact', 'moga_contact_phone');
        register_setting('moga_settings_contact', 'moga_contact_whatsapp');
        register_setting('moga_settings_contact', 'moga_contact_address');
        register_setting('moga_settings_contact', 'moga_contact_lat');
        register_setting('moga_settings_contact', 'moga_contact_lng');
    }

    /**
     * Sanitize callback for moga_deposit_floor_percent.
     * Locked decision: 20% is the platform-wide minimum deposit
     * floor and cannot be lowered via this form. Rejects anything
     * below it, surfaces a visible error, and keeps the previous
     * saved value instead of silently accepting the bad input.
     *
     * @since  1.0.0
     * @param  mixed $value Raw submitted value.
     * @return float
     */
    public static function sanitize_deposit_floor_percent($value)
    {
        $minimum = 20;
        $value   = floatval($value);

        if ($value < $minimum) {
            add_settings_error(
                'moga_deposit_floor_percent',
                'deposit_floor_too_low',
                sprintf(
                    /* translators: %d: minimum percentage */
                    __('Minimum Deposit Floor cannot be set below the platform default of %d%%. Your change was not saved.', 'moga-travel-core'),
                    $minimum
                ),
                'error'
            );
            return get_option('moga_deposit_floor_percent', $minimum);
        }

        return $value;
    }

    /**
     * Sanitize callback for moga_cancellation_fee_percent.
     * Locked decision: 10% is the platform-wide minimum
     * cancellation fee and cannot be lowered via this form.
     *
     * @since  1.0.0
     * @param  mixed $value Raw submitted value.
     * @return float
     */
    public static function sanitize_cancellation_fee_percent($value)
    {
        $minimum = 10;
        $value   = floatval($value);

        if ($value < $minimum) {
            add_settings_error(
                'moga_cancellation_fee_percent',
                'cancellation_fee_too_low',
                sprintf(
                    /* translators: %d: minimum percentage */
                    __('Cancellation Fee cannot be set below the platform default of %d%%. Your change was not saved.', 'moga-travel-core'),
                    $minimum
                ),
                'error'
            );
            return get_option('moga_cancellation_fee_percent', $minimum);
        }

        return $value;
    }

    /**
     * Render the settings page.
     * Called by the admin menu callback.
     *
     * @since  1.0.0
     * @return void
     */
    public static function render_page()
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'moga-travel-core'));
        }

        $tab = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : 'general';

        $tabs = array(
            'general'       => __('General', 'moga-travel-core'),
            'booking'       => __('Booking', 'moga-travel-core'),
            'payment'       => __('Payments', 'moga-travel-core'),
            'notifications' => __('Notifications', 'moga-travel-core'),
            'maps'          => __('Maps', 'moga-travel-core'),
            'contact'       => __('Contact Info', 'moga-travel-core'),
        );
?>
        <div class="wrap">
            <h1><?php esc_html_e('Moga Settings', 'moga-travel-core'); ?></h1>

            <?php settings_errors(); ?>

            <nav class="nav-tab-wrapper wp-clearfix" style="margin-bottom:0;">
                <?php foreach ($tabs as $slug => $label) : ?>
                    <a
                        href="<?php echo esc_url(admin_url('admin.php?page=moga-settings&tab=' . $slug)); ?>"
                        class="nav-tab<?php echo $tab === $slug ? ' nav-tab-active' : ''; ?>"><?php echo esc_html($label); ?></a>
                <?php endforeach; ?>
            </nav>

            <div style="background:#fff;border:1px solid #c3c4c7;border-top:none;padding:30px;border-radius:0 0 4px 4px;">

                <?php if ('contact' === $tab) : ?>

                    <form method="post" action="options.php">
                        <?php settings_fields('moga_settings_contact'); ?>

                        <table class="form-table" role="presentation">
                            <tr>
                                <th scope="row"><label for="moga_contact_phone"><?php esc_html_e('Phone Number', 'moga-travel-core'); ?></label></th>
                                <td><input type="text" id="moga_contact_phone" name="moga_contact_phone" class="regular-text" value="<?php echo esc_attr(get_option('moga_contact_phone')); ?>"></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="moga_contact_whatsapp"><?php esc_html_e('WhatsApp Number', 'moga-travel-core'); ?></label></th>
                                <td><input type="text" id="moga_contact_whatsapp" name="moga_contact_whatsapp" class="regular-text" value="<?php echo esc_attr(get_option('moga_contact_whatsapp')); ?>"></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="moga_contact_address"><?php esc_html_e('Address', 'moga-travel-core'); ?></label></th>
                                <td><input type="text" id="moga_contact_address" name="moga_contact_address" class="regular-text" value="<?php echo esc_attr(get_option('moga_contact_address')); ?>"></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="moga_contact_lat"><?php esc_html_e('Map Latitude', 'moga-travel-core'); ?></label></th>
                                <td>
                                    <input type="text" id="moga_contact_lat" name="moga_contact_lat" class="regular-text" value="<?php echo esc_attr(get_option('moga_contact_lat')); ?>">
                                    <p class="description"><?php esc_html_e('Used for the map on the Contact Us page. Right-click your location on Google Maps to copy the coordinates.', 'moga-travel-core'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="moga_contact_lng"><?php esc_html_e('Map Longitude', 'moga-travel-core'); ?></label></th>
                                <td><input type="text" id="moga_contact_lng" name="moga_contact_lng" class="regular-text" value="<?php echo esc_attr(get_option('moga_contact_lng')); ?>"></td>
                            </tr>
                        </table>

                        <?php submit_button(); ?>
                    </form>

                <?php elseif ('booking' === $tab) : ?>

                    <form method="post" action="options.php">
                        <?php settings_fields('moga_settings_booking'); ?>

                        <table class="form-table" role="presentation">
                            <tr>
                                <th scope="row"><label for="moga_commission_rate"><?php esc_html_e('Commission Rate (%)', 'moga-travel-core'); ?></label></th>
                                <td>
                                    <input type="number" step="0.01" min="0" max="100" id="moga_commission_rate" name="moga_commission_rate" class="small-text" value="<?php echo esc_attr(get_option('moga_commission_rate', 10)); ?>">
                                    <p class="description"><?php esc_html_e('Platform commission taken from each booking. Used by moga_calculate_commission().', 'moga-travel-core'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="moga_commission_type"><?php esc_html_e('Commission Type', 'moga-travel-core'); ?></label></th>
                                <td>
                                    <select id="moga_commission_type" name="moga_commission_type">
                                        <option value="percentage" <?php selected(get_option('moga_commission_type', 'percentage'), 'percentage'); ?>><?php esc_html_e('Percentage', 'moga-travel-core'); ?></option>
                                        <option value="fixed" <?php selected(get_option('moga_commission_type', 'percentage'), 'fixed'); ?>><?php esc_html_e('Fixed Amount', 'moga-travel-core'); ?></option>
                                    </select>
                                    <p class="description" style="color:#b32d2e;">
                                        <?php esc_html_e('Note: only "Percentage" is currently implemented in the commission calculation logic. Selecting "Fixed Amount" has no effect yet.', 'moga-travel-core'); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="moga_deposit_floor_percent"><?php esc_html_e('Minimum Deposit Floor (%)', 'moga-travel-core'); ?></label></th>
                                <td>
                                    <input type="number" step="0.01" min="20" max="100" id="moga_deposit_floor_percent" name="moga_deposit_floor_percent" class="small-text" value="<?php echo esc_attr(get_option('moga_deposit_floor_percent', 20)); ?>">
                                    <p class="description"><?php esc_html_e('Platform-wide minimum deposit. Cannot be set below 20% — vendors may require more, never less.', 'moga-travel-core'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="moga_cancellation_fee_percent"><?php esc_html_e('Cancellation Fee (%)', 'moga-travel-core'); ?></label></th>
                                <td>
                                    <input type="number" step="0.01" min="10" max="100" id="moga_cancellation_fee_percent" name="moga_cancellation_fee_percent" class="small-text" value="<?php echo esc_attr(get_option('moga_cancellation_fee_percent', 10)); ?>">
                                    <p class="description"><?php esc_html_e('Site-wide default fee kept on a voluntary cancellation refund. Cannot be set below 10%. Vendors will be able to override this once the owner/organizer dashboard exists.', 'moga-travel-core'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="moga_balance_due_days"><?php esc_html_e('Balance Due (days before check-in)', 'moga-travel-core'); ?></label></th>
                                <td>
                                    <input type="number" min="0" id="moga_balance_due_days" name="moga_balance_due_days" class="small-text" value="<?php echo esc_attr(get_option('moga_balance_due_days', 7)); ?>">
                                    <p class="description"><?php esc_html_e('When a guest pays a deposit instead of the full amount, this is how many days before check-in the remaining balance is due. Shown to guests at checkout.', 'moga-travel-core'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="moga_booking_expiry"><?php esc_html_e('Pending Booking Expiry (minutes)', 'moga-travel-core'); ?></label></th>
                                <td>
                                    <input type="number" min="1" id="moga_booking_expiry" name="moga_booking_expiry" class="small-text" value="<?php echo esc_attr(get_option('moga_booking_expiry', 30)); ?>">
                                    <p class="description"><?php esc_html_e('How long an unpaid pending booking holds its dates before auto-cancelling.', 'moga-travel-core'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="moga_min_booking_notice"><?php esc_html_e('Minimum Booking Notice (days)', 'moga-travel-core'); ?></label></th>
                                <td><input type="number" min="0" id="moga_min_booking_notice" name="moga_min_booking_notice" class="small-text" value="<?php echo esc_attr(get_option('moga_min_booking_notice', 1)); ?>"></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="moga_max_booking_days"><?php esc_html_e('Maximum Advance Booking (days)', 'moga-travel-core'); ?></label></th>
                                <td><input type="number" min="1" id="moga_max_booking_days" name="moga_max_booking_days" class="small-text" value="<?php echo esc_attr(get_option('moga_max_booking_days', 365)); ?>"></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="moga_seat_lock_duration"><?php esc_html_e('Seat Hold Duration (minutes)', 'moga-travel-core'); ?></label></th>
                                <td>
                                    <input type="number" min="1" id="moga_seat_lock_duration" name="moga_seat_lock_duration" class="small-text" value="<?php echo esc_attr(get_option('moga_seat_lock_duration', 15)); ?>">
                                    <p class="description" style="color:#b32d2e;">
                                        <?php esc_html_e('Note: not yet consumed by any code — class-moga-seat-map.php has not been built.', 'moga-travel-core'); ?>
                                    </p>
                                </td>
                            </tr>
                        </table>

                        <?php submit_button(); ?>
                    </form>

                <?php elseif ('payment' === $tab) : ?>

                    <p class="description" style="margin-bottom:20px;">
                        <?php esc_html_e('These toggles only control which gateways are considered "enabled" for site-wide fallback purposes. No live gateway integration (API keys, checkout flow) exists yet for Stripe or PayPal — that is separate, not-yet-scoped work.', 'moga-travel-core'); ?>
                    </p>

                    <form method="post" action="options.php">
                        <?php settings_fields('moga_settings_payment'); ?>

                        <table class="form-table" role="presentation">
                            <tr>
                                <th scope="row"><?php esc_html_e('Stripe', 'moga-travel-core'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="moga_payment_stripe" value="1" <?php checked(get_option('moga_payment_stripe'), 1); ?>>
                                        <?php esc_html_e('Enable Stripe as a site-wide gateway option', 'moga-travel-core'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('PayPal', 'moga-travel-core'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="moga_payment_paypal" value="1" <?php checked(get_option('moga_payment_paypal'), 1); ?>>
                                        <?php esc_html_e('Enable PayPal as a site-wide gateway option', 'moga-travel-core'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Offline Payment', 'moga-travel-core'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="moga_payment_offline" value="1" <?php checked(get_option('moga_payment_offline'), 1); ?>>
                                        <?php esc_html_e('Allow bank transfer / cash payments, confirmed manually by an admin or vendor', 'moga-travel-core'); ?>
                                    </label>
                                </td>
                            </tr>
                        </table>

                        <?php submit_button(); ?>
                    </form>

                <?php else : ?>

                    <div style="text-align:center;padding:40px 0;color:#888;">
                        <p style="font-size:1.1rem;margin:0;">
                            <?php
                            printf(
                                /* translators: %s: tab name */
                                esc_html__('%s settings are coming in Phase 6.', 'moga-travel-core'),
                                '<strong>' . esc_html($tabs[$tab] ?? '') . '</strong>'
                            );
                            ?>
                        </p>
                        <p style="margin:8px 0 0;">
                            <?php esc_html_e('Default values are already active and the plugin is fully functional.', 'moga-travel-core'); ?>
                        </p>
                    </div>

                <?php endif; ?>

            </div>
        </div>
<?php
    }
}
