<?php

/**
 * Dashboard Template Part — Bookings List (view-only)
 *
 * Expects $args['owner_id']. VIEW-ONLY by deliberate scope decision
 * — no cancel/action buttons yet. Status filter via query string
 * (?moga_status=confirmed etc.). No pagination controls yet — capped
 * at 50 most recent bookings; a known simplification, not a bug.
 *
 * @package MogaTravel
 * @since   1.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

$owner_id = isset($args['owner_id']) ? absint($args['owner_id']) : get_current_user_id();

$status_filter = isset($_GET['moga_status']) ? sanitize_key(wp_unslash($_GET['moga_status'])) : '';

$query_args = array(
    'owner_id' => $owner_id,
    'per_page' => 50,
    'orderby'  => 'check_in',
    'order'    => 'DESC',
);
if ($status_filter) {
    $query_args['status'] = $status_filter;
}

$core     = function_exists('moga_core') ? moga_core() : null;
$bookings = ($core && $core->booking) ? $core->booking->get_bookings($query_args) : array();

$status_labels = array(
    'pending'   => __('Pending', 'moga-travel'),
    'confirmed' => __('Confirmed', 'moga-travel'),
    'cancelled' => __('Cancelled', 'moga-travel'),
    'completed' => __('Completed', 'moga-travel'),
    'refunded'  => __('Refunded', 'moga-travel'),
    'no_show'   => __('No Show', 'moga-travel'),
);
?>
<div class="moga-dashboard__section">
    <h2><?php esc_html_e('Bookings', 'moga-travel'); ?></h2>

    <div class="moga-dashboard__filters">
        <a href="<?php echo esc_url(remove_query_arg('moga_status')); ?>"
            class="<?php echo '' === $status_filter ? 'is-active' : ''; ?>">
            <?php esc_html_e('All', 'moga-travel'); ?>
        </a>
        <?php foreach ($status_labels as $status_key => $status_label) : ?>
            <a href="<?php echo esc_url(add_query_arg('moga_status', $status_key)); ?>"
                class="<?php echo $status_filter === $status_key ? 'is-active' : ''; ?>">
                <?php echo esc_html($status_label); ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (empty($bookings)) : ?>

        <p class="moga-dashboard__empty"><?php esc_html_e('No bookings found.', 'moga-travel'); ?></p>

    <?php else : ?>

        <table class="moga-dashboard__table">
            <thead>
                <tr>
                    <th><?php esc_html_e('Booking #', 'moga-travel'); ?></th>
                    <th><?php esc_html_e('Listing', 'moga-travel'); ?></th>
                    <th><?php esc_html_e('Dates', 'moga-travel'); ?></th>
                    <th><?php esc_html_e('Status', 'moga-travel'); ?></th>
                    <th><?php esc_html_e('Payment', 'moga-travel'); ?></th>
                    <th><?php esc_html_e('Total', 'moga-travel'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bookings as $booking) : ?>
                    <tr>
                        <td><?php echo esc_html($booking['booking_number']); ?></td>
                        <td><?php echo esc_html(get_the_title($booking['listing_id']) ?: '#' . $booking['listing_id']); ?></td>
                        <td><?php echo esc_html(moga_format_date_range($booking['check_in'], $booking['check_out'])); ?></td>
                        <td>
                            <span class="moga-status-badge moga-status-badge--<?php echo esc_attr($booking['status']); ?>">
                                <?php echo esc_html($status_labels[$booking['status']] ?? $booking['status']); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html(ucfirst(str_replace('_', ' ', $booking['payment_status']))); ?></td>
                        <td><?php echo esc_html(moga_format_price($booking['total_amount'], $booking['currency'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    <?php endif; ?>
</div>
