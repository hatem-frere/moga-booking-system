<?php

/**
 * Dashboard Template Part — Owner Stats Cards
 *
 * Expects $args['owner_id']. Aggregates from Moga_Booking::get_bookings()
 * rather than a dedicated SQL aggregate query — acceptable at
 * current scale, but a known simplification worth revisiting if a
 * vendor accumulates a very large number of bookings (per_page here
 * is set high enough to capture "all of them" rather than paging).
 *
 * HONESTY NOTE: the "Total Collected" figure is gross amount
 * actually paid to date (total_amount - balance_due), NOT a
 * commission-adjusted net payout — class-moga-commission.php has
 * not been built yet (still 0 bytes), so there is no real per-booking
 * commission record to subtract. Labelled "Collected" rather than
 * "Earnings" deliberately, to avoid implying a net figure that
 * doesn't exist yet.
 *
 * @package MogaTravel
 * @since   1.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

$owner_id = isset($args['owner_id']) ? absint($args['owner_id']) : get_current_user_id();

$core     = function_exists('moga_core') ? moga_core() : null;
$bookings = ($core && $core->booking)
    ? $core->booking->get_bookings(array('owner_id' => $owner_id, 'per_page' => 500))
    : array();

$total_bookings = count($bookings);
$upcoming        = 0;
$pending_action  = 0;
$total_collected = 0.0;
$today           = current_time('Y-m-d');

foreach ($bookings as $booking) {
    if ('confirmed' === $booking['status'] && $booking['check_in'] >= $today) {
        $upcoming++;
    }
    if ('pending' === $booking['status']) {
        $pending_action++;
    }
    if (in_array($booking['status'], array('confirmed', 'completed'), true)) {
        $total_collected += (float) $booking['total_amount'] - (float) $booking['balance_due'];
    }
}
?>
<div class="moga-dashboard__stats">

    <div class="moga-dashboard__stat-card">
        <div class="moga-dashboard__stat-value"><?php echo esc_html($total_bookings); ?></div>
        <div class="moga-dashboard__stat-label"><?php esc_html_e('Total Bookings', 'moga-travel'); ?></div>
    </div>

    <div class="moga-dashboard__stat-card">
        <div class="moga-dashboard__stat-value"><?php echo esc_html($upcoming); ?></div>
        <div class="moga-dashboard__stat-label"><?php esc_html_e('Upcoming', 'moga-travel'); ?></div>
    </div>

    <div class="moga-dashboard__stat-card">
        <div class="moga-dashboard__stat-value"><?php echo esc_html($pending_action); ?></div>
        <div class="moga-dashboard__stat-label"><?php esc_html_e('Pending Action', 'moga-travel'); ?></div>
    </div>

    <div class="moga-dashboard__stat-card">
        <div class="moga-dashboard__stat-value"><?php echo esc_html(moga_format_price($total_collected)); ?></div>
        <div class="moga-dashboard__stat-label"><?php esc_html_e('Total Collected', 'moga-travel'); ?></div>
    </div>

</div>
