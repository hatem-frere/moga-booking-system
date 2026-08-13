<?php

/**
 * Template Name: Owner Dashboard
 *
 * Owner/Organizer dashboard shell. Auth-gated to logged-in users
 * who are any kind of Moga vendor — Property Owner or Tour
 * Organizer (moga_is_vendor(), added in helper-functions.php this
 * session — the previous moga_is_owner() check only recognized the
 * legacy, no-longer-assigned 'moga_owner' role and rejected every
 * real vendor account). Handles the payment-gateway-choice form
 * submission directly (plain POST + nonce, no AJAX needed for a
 * once-in-a-while preference save), then includes the three
 * dashboard template parts in order.
 *
 * VIEW-ONLY TODAY: the bookings list below has no action buttons
 * (no cancel, no status change) — that was a deliberate scope
 * decision to keep this piece finishable and testable in one pass.
 * Actions are a well-scoped follow-up once this is confirmed
 * working end-to-end.
 *
 * @package MogaTravel
 * @since   1.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! is_user_logged_in()) {
    auth_redirect();
    exit;
}

$moga_current_user_id = get_current_user_id();

if (! moga_is_vendor($moga_current_user_id)) {
    get_header();
?>
    <div class="moga-main">
        <div class="moga-dashboard moga-dashboard--denied">
            <p><?php esc_html_e('This dashboard is only available to property owners and tour organizers.', 'moga-travel'); ?></p>
        </div>
    </div>
<?php
    get_footer();
    return;
}

// ---- Handle payment gateway choice form submission ----
$moga_gateway_saved = false;

if (
    isset($_POST['moga_save_gateway_nonce'])
    && wp_verify_nonce(
        sanitize_text_field(wp_unslash($_POST['moga_save_gateway_nonce'])),
        'moga_save_gateway'
    )
) {
    $moga_chosen_gateway  = isset($_POST['moga_vendor_gateway']) ? sanitize_key($_POST['moga_vendor_gateway']) : '';
    $moga_allowed_choices = array('', 'stripe', 'paypal', 'offline');

    if (in_array($moga_chosen_gateway, $moga_allowed_choices, true)) {
        update_user_meta($moga_current_user_id, '_moga_vendor_gateway', $moga_chosen_gateway);
        $moga_gateway_saved = true;
    }
}

get_header();
?>

<div class="moga-main">
    <div class="moga-dashboard">

        <div class="moga-dashboard__header">
            <h1><?php esc_html_e('My Dashboard', 'moga-travel'); ?></h1>
            <p>
                <?php
                printf(
                    /* translators: %s: display name */
                    esc_html__('Welcome back, %s.', 'moga-travel'),
                    esc_html(wp_get_current_user()->display_name)
                );
                ?>
            </p>
        </div>

        <?php if ($moga_gateway_saved) : ?>
            <div class="moga-dashboard__notice moga-dashboard__notice--success">
                <?php esc_html_e('Payment gateway preference saved.', 'moga-travel'); ?>
            </div>
        <?php endif; ?>

        <?php get_template_part('template-parts/dashboard/owner', 'stats', array('owner_id' => $moga_current_user_id)); ?>

        <?php get_template_part('template-parts/dashboard/owner', 'payment-settings', array('owner_id' => $moga_current_user_id)); ?>

        <?php get_template_part('template-parts/dashboard/owner', 'bookings', array('owner_id' => $moga_current_user_id)); ?>

    </div>
</div>

<?php
get_footer();
