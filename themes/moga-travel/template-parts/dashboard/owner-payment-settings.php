<?php

/**
 * Dashboard Template Part — Payment Gateway Choice
 *
 * Expects $args['owner_id']. Radio choice saved to
 * '_moga_vendor_gateway' user meta by template-dashboard.php's form
 * handler. Empty string = "use platform default", which already
 * correctly falls through in Moga_Payment::get_vendor_gateway() —
 * no changes needed there. Stripe/PayPal only appear as options if
 * the site admin has actually enabled them platform-wide
 * (Moga_Payment::is_gateway_enabled()) — no point offering a vendor
 * a gateway that isn't live site-wide.
 *
 * @package MogaTravel
 * @since   1.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

$owner_id = isset($args['owner_id']) ? absint($args['owner_id']) : get_current_user_id();

$core    = function_exists('moga_core') ? moga_core() : null;
$payment = ($core && $core->payment) ? $core->payment : null;

$current_choice = get_user_meta($owner_id, '_moga_vendor_gateway', true);

$stripe_enabled = $payment ? $payment->is_gateway_enabled('stripe') : false;
$paypal_enabled = $payment ? $payment->is_gateway_enabled('paypal') : false;
?>
<div class="moga-dashboard__section">
    <h2><?php esc_html_e('Payment Method', 'moga-travel'); ?></h2>
    <p class="moga-dashboard__hint">
        <?php esc_html_e('Choose which payment gateway guests use to pay you. Only gateways enabled platform-wide can be selected here.', 'moga-travel'); ?>
    </p>

    <form method="post">
        <?php wp_nonce_field('moga_save_gateway', 'moga_save_gateway_nonce'); ?>

        <label class="moga-dashboard__radio">
            <input type="radio" name="moga_vendor_gateway" value="" <?php checked('', $current_choice); ?>>
            <?php esc_html_e('Use platform default', 'moga-travel'); ?>
        </label>

        <?php if ($stripe_enabled) : ?>
            <label class="moga-dashboard__radio">
                <input type="radio" name="moga_vendor_gateway" value="stripe" <?php checked('stripe', $current_choice); ?>>
                <?php esc_html_e('Stripe', 'moga-travel'); ?>
            </label>
        <?php endif; ?>

        <?php if ($paypal_enabled) : ?>
            <label class="moga-dashboard__radio">
                <input type="radio" name="moga_vendor_gateway" value="paypal" <?php checked('paypal', $current_choice); ?>>
                <?php esc_html_e('PayPal', 'moga-travel'); ?>
            </label>
        <?php endif; ?>

        <label class="moga-dashboard__radio">
            <input type="radio" name="moga_vendor_gateway" value="offline" <?php checked('offline', $current_choice); ?>>
            <?php esc_html_e('Offline only (bank transfer / cash)', 'moga-travel'); ?>
        </label>

        <?php if (! $stripe_enabled && ! $paypal_enabled) : ?>
            <p class="moga-dashboard__hint moga-dashboard__hint--warning">
                <?php esc_html_e('No online gateways are currently enabled platform-wide by the site admin.', 'moga-travel'); ?>
            </p>
        <?php endif; ?>

        <button type="submit" class="moga-btn moga-btn--primary">
            <?php esc_html_e('Save Payment Preference', 'moga-travel'); ?>
        </button>
    </form>
</div>
