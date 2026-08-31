<?php

/**
 * Cancellation Policy Shortcode — [moga_cancellation_policy]
 *
 * A general, standalone reference page explaining all four
 * cancellation policy tiers a property owner can choose from —
 * matches real Booking.com/Airbnb practice of one general policy
 * explainer page, with the SPECIFIC policy that applies to a given
 * booking shown inline at checkout instead (see
 * class-moga-shortcode-checkout.php's render_cancellation_summary()).
 *
 * Uses the real, authoritative policy text already defined in
 * Moga_CPT_Property::get_cancellation_policies() — never invents its
 * own wording, so this page can never drift out of sync with what an
 * owner actually selects in the property editor.
 *
 * PAGE SETUP: there's no existing settings UI for registering a new
 * page-to-shortcode mapping (the three flow pages — Booking,
 * Checkout, Confirmation — were auto-created by the plugin activator,
 * not through a settings screen). Rather than build new
 * infrastructure for one page, this shortcode expects to live on a
 * WordPress Page at the predictable slug /cancellation-policy/ —
 * create one with this shortcode in it.
 *
 * @package    MogaTravelCore
 * @subpackage MogaTravelCore/includes/shortcodes
 * @author     Hatem Frere
 * @since      1.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Class Moga_Shortcode_Cancellation_Policy
 */
class Moga_Shortcode_Cancellation_Policy
{

    /**
     * Register the shortcode.
     *
     * @since  1.0.0
     * @return void
     */
    public function register()
    {
        add_shortcode('moga_cancellation_policy', array($this, 'render'));
    }

    /**
     * Shortcode callback.
     *
     * @since  1.0.0
     * @param  array $atts Shortcode attributes (unused).
     * @return string
     */
    public function render($atts)
    {
        $policies = class_exists('Moga_CPT_Property')
            ? Moga_CPT_Property::get_cancellation_policies()
            : array();

        ob_start();
?>
        <div class="moga-cancellation-policy">

            <p class="moga-cancellation-policy__intro">
                <?php esc_html_e('Each property or tour on Moga Booking System sets its own cancellation policy, chosen by the property owner or tour organizer from one of the tiers below. The specific policy for a listing is always shown on its page and at checkout before you confirm a booking.', 'moga-travel'); ?>
            </p>

            <?php foreach ($policies as $key => $policy) : ?>
                <div class="moga-cancellation-policy__tier moga-cancellation-policy__tier--<?php echo esc_attr($key); ?>">
                    <h3><?php echo esc_html($policy['label']); ?></h3>
                    <p><?php echo esc_html($policy['desc']); ?></p>
                </div>
            <?php endforeach; ?>

            <?php if (empty($policies)) : ?>
                <p><?php esc_html_e('Cancellation policy information isn\'t available right now.', 'moga-travel'); ?></p>
            <?php endif; ?>

        </div>
<?php
        return ob_get_clean();
    }
}
