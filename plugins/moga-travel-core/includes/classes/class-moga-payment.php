<?php

/**
 * Payment Processing Class
 *
 * Handles the payment ledger (mg_moga_payments), offline payment
 * confirmation, refunds, and gateway resolution. Deliberately does
 * NOT contain any live Stripe/PayPal API calls yet — no gateway
 * decision has been finalised for site-level defaults beyond "PayPal
 * and similar", so this class builds the gateway-agnostic ledger and
 * resolution logic today, with handle_gateway_webhook() as the clear
 * seam where real gateway SDKs plug in once chosen.
 *
 * GATEWAY ARCHITECTURE (locked decision):
 *   - Site-level default gateway(s), admin-configured.
 *   - Each vendor (owner/tour organizer) may override with their own
 *     regional gateway, or disable online payment and rely on
 *     offline only.
 *   - Resolution order: vendor's own choice -> site default -> offline.
 *   - Settings surface in admin, owner, and tour-organizer dashboards
 *     — none of those UIs exist yet; this class only needs the data
 *     to already be there when they're built, via get_vendor_gateway().
 *
 * VENDOR GATEWAY META NOTE: get_vendor_gateway() reads user meta key
 * '_moga_vendor_gateway' on the owner/organizer's user account. This
 * is a NEW key — no admin UI sets it yet, same situation as
 * yesterday's '_moga_deposit_type' listing meta. Falls back cleanly
 * to the site default when unset, so nothing breaks in the meantime.
 *
 * REFUND ENUM NOTE: mg_moga_payments.status and
 * mg_moga_bookings.payment_status both only have a 'refunded' value
 * — no 'partially_refunded'. Cancellation refunds here keep a fixed
 * fee (moga_cancellation_fee_percent, locked minimum 10%, cannot be
 * set lower via the settings page) and refund the rest, but are
 * still marked 'refunded' throughout, matching how most booking
 * platforms display any settled refund regardless of a small kept
 * fee. Documented here so it's not a silent assumption.
 *
 * @package    MogaTravelCore
 * @subpackage MogaTravelCore/includes/classes
 * @author     Hatem Frere
 * @since      1.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Class Moga_Payment
 */
class Moga_Payment
{

    // ============================================================
    // CORE RECORDING / LEDGER
    // ============================================================

    /**
     * Record a payment transaction and, if it completed successfully,
     * update the booking's balance via Moga_Booking::record_payment_received().
     *
     * @since  1.0.0
     * @param  int    $booking_id      Booking ID.
     * @param  float  $amount          Amount paid.
     * @param  string $payment_type    deposit|remainder|full.
     * @param  string $method          Free-text payment method label (e.g. 'card', 'bank_transfer').
     * @param  string $gateway         Gateway slug (e.g. 'paypal', 'stripe', 'offline').
     * @param  array  $gateway_response Raw gateway response data, stored as-is for audit/dispute purposes.
     * @param  string $transaction_id  Gateway's own transaction reference, if any.
     * @param  string $status          pending|completed|failed|refunded|cancelled. Default 'completed'.
     * @return int|WP_Error Payment ID on success, WP_Error on failure.
     */
    public function record_payment(
        $booking_id,
        $amount,
        $payment_type,
        $method,
        $gateway,
        array $gateway_response = array(),
        $transaction_id = '',
        $status = 'completed'
    ) {
        $valid_types = array('deposit', 'remainder', 'full');
        if (! in_array($payment_type, $valid_types, true)) {
            return new WP_Error('invalid_payment_type', __('Invalid payment type.', 'moga-travel-core'));
        }

        $valid_statuses = array('pending', 'completed', 'failed', 'refunded', 'cancelled');
        if (! in_array($status, $valid_statuses, true)) {
            return new WP_Error('invalid_payment_status', __('Invalid payment status.', 'moga-travel-core'));
        }

        global $wpdb;
        $prefix = $wpdb->prefix . MOGA_CORE_DB_PREFIX;

        $inserted = $wpdb->insert(
            "{$prefix}payments",
            array(
                'booking_id'       => absint($booking_id),
                'transaction_id'   => $transaction_id ? sanitize_text_field($transaction_id) : null,
                'payment_method'   => sanitize_text_field($method),
                'payment_gateway'  => sanitize_key($gateway),
                'payment_type'     => $payment_type,
                'amount'           => (float) $amount,
                'currency'         => get_option('moga_currency', 'USD'),
                'status'           => $status,
                'gateway_response' => ! empty($gateway_response) ? wp_json_encode($gateway_response) : null,
            ),
            array('%d', '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%s')
        );

        if (false === $inserted) {
            return new WP_Error('db_error', __('Could not record the payment.', 'moga-travel-core'));
        }

        $payment_id = $wpdb->insert_id;

        // Only a completed payment actually moves the booking's balance.
        if ('completed' === $status) {
            $booking = moga_core();
            if ($booking && $booking->booking) {
                $booking->booking->record_payment_received($booking_id, (float) $amount, $payment_type);
            }
        }

        return $payment_id;
    }

    /**
     * Fetch a single payment row.
     *
     * @since  1.0.0
     * @param  int $payment_id Payment ID.
     * @return array|null
     */
    public function get_payment($payment_id)
    {
        global $wpdb;
        $prefix = $wpdb->prefix . MOGA_CORE_DB_PREFIX;

        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$prefix}payments WHERE id = %d", absint($payment_id)),
            ARRAY_A
        );

        return $row ?: null;
    }

    /**
     * Fetch the full payment history for a booking, oldest first.
     *
     * @since  1.0.0
     * @param  int $booking_id Booking ID.
     * @return array
     */
    public function get_payments_for_booking($booking_id)
    {
        global $wpdb;
        $prefix = $wpdb->prefix . MOGA_CORE_DB_PREFIX;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$prefix}payments WHERE booking_id = %d ORDER BY created_at ASC",
            absint($booking_id)
        ), ARRAY_A);
    }

    /**
     * Determine what the NEXT payment against a booking should be
     * classified as, based on its current deposit_amount/balance_due
     * — so callers never have to guess or hardcode 'deposit' vs
     * 'remainder'.
     *
     * @since  1.0.0
     * @param  int $booking_id Booking ID.
     * @return string|WP_Error 'deposit'|'remainder'|'full'.
     */
    public function determine_next_payment_type($booking_id)
    {
        $core = function_exists('moga_core') ? moga_core() : null;
        if (! $core || ! $core->booking) {
            return new WP_Error('booking_unavailable', __('Booking engine is not available.', 'moga-travel-core'));
        }

        $booking = $core->booking->get_booking($booking_id);
        if (! $booking) {
            return new WP_Error('not_found', __('Booking not found.', 'moga-travel-core'));
        }

        $total_amount   = (float) $booking['total_amount'];
        $deposit_amount = (float) $booking['deposit_amount'];
        $balance_due    = (float) $booking['balance_due'];
        $amount_paid    = round($total_amount - $balance_due, 2);

        if ($amount_paid <= 0) {
            // Nothing paid yet — is this listing full-payment-only or deposit-based?
            return $deposit_amount >= $total_amount ? 'full' : 'deposit';
        }

        return 'remainder';
    }


    // ============================================================
    // OFFLINE PAYMENTS (bank transfer / cash — no gateway required)
    // ============================================================

    /**
     * Record an offline payment claim as 'pending' — the guest says
     * they've paid, but nobody has verified it yet. Does NOT touch
     * the booking's balance; that only happens on confirmation.
     *
     * @since  1.0.0
     * @param  int    $booking_id   Booking ID.
     * @param  float  $amount       Claimed amount.
     * @param  string $payment_type deposit|remainder|full.
     * @return int|WP_Error Payment ID (status 'pending') or WP_Error.
     */
    public function create_offline_payment($booking_id, $amount, $payment_type)
    {
        return $this->record_payment(
            $booking_id,
            $amount,
            $payment_type,
            'offline',
            'offline',
            array(),
            '',
            'pending'
        );
    }

    /**
     * Admin/owner confirms an offline payment actually arrived —
     * flips it to 'completed' and updates the booking's balance.
     * This is the action a vendor clicks after checking their bank
     * account or receiving cash.
     *
     * @since  1.0.0
     * @param  int $payment_id Payment ID (must currently be 'pending').
     * @return true|WP_Error
     */
    public function confirm_offline_payment($payment_id)
    {
        $payment = $this->get_payment($payment_id);
        if (! $payment) {
            return new WP_Error('not_found', __('Payment record not found.', 'moga-travel-core'));
        }

        if ('pending' !== $payment['status']) {
            return new WP_Error(
                'invalid_state',
                __('Only a pending offline payment can be confirmed.', 'moga-travel-core')
            );
        }

        global $wpdb;
        $prefix = $wpdb->prefix . MOGA_CORE_DB_PREFIX;

        $updated = $wpdb->update(
            "{$prefix}payments",
            array('status' => 'completed'),
            array('id' => absint($payment_id)),
            array('%s'),
            array('%d')
        );

        if (false === $updated) {
            return new WP_Error('db_error', __('Could not confirm the payment.', 'moga-travel-core'));
        }

        $core = function_exists('moga_core') ? moga_core() : null;
        if ($core && $core->booking) {
            $core->booking->record_payment_received(
                $payment['booking_id'],
                (float) $payment['amount'],
                $payment['payment_type']
            );
        }

        return true;
    }


    // ============================================================
    // REFUNDS
    // ============================================================

    /**
     * Resolve the cancellation fee percentage to apply, following
     * the same three-level pattern as get_vendor_gateway():
     *   1. Vendor's own percentage (user meta, if set)
     *   2. Site-wide default (moga_cancellation_fee_percent option)
     *   3. 5% hardcoded fallback, if even the site option is unset
     *
     * VENDOR FEE META NOTE: reads '_moga_vendor_cancellation_fee_percent'
     * user meta on the owner/organizer's account. Same situation as
     * '_moga_vendor_gateway' — no dashboard UI sets this yet for any
     * of the three roles (admin/owner/organizer), so every vendor
     * currently falls through to the site default until that UI
     * exists. Functional in the meantime via the fallback chain.
     *
     * @since  1.0.0
     * @param  int $owner_id Listing owner's user ID. 0 to skip
     *                       straight to the site default (e.g. when
     *                       the owner isn't known/relevant).
     * @return float Fee percentage to apply.
     */
    public function get_cancellation_fee_percent($owner_id = 0)
    {
        $minimum = 10;

        if ($owner_id) {
            $vendor_fee = get_user_meta(absint($owner_id), '_moga_vendor_cancellation_fee_percent', true);

            if ('' !== $vendor_fee && null !== $vendor_fee) {
                return max((float) $vendor_fee, $minimum);
            }
        }

        return max((float) get_option('moga_cancellation_fee_percent', $minimum), $minimum);
    }

    /**
     * Calculate a cancellation refund: total paid minus the
     * cancellation fee percentage, resolved per get_cancellation_fee_percent()
     * (vendor override -> site default -> 5% fallback).
     *
     * @since  1.0.0
     * @param  float $amount_paid Total amount paid to date on the booking.
     * @param  int   $owner_id    Listing owner's user ID, for fee resolution.
     * @return array {
     *     @type float $fee_amount    Amount kept as the cancellation fee.
     *     @type float $refund_amount Amount to actually refund the guest.
     *     @type float $fee_percent   The percentage actually used.
     * }
     */
    public function calculate_cancellation_refund($amount_paid, $owner_id = 0)
    {
        $amount_paid = (float) $amount_paid;
        $fee_percent = $this->get_cancellation_fee_percent($owner_id);

        $fee_amount    = round($amount_paid * ($fee_percent / 100), 2);
        $refund_amount = round($amount_paid - $fee_amount, 2);

        return array(
            'fee_amount'    => $fee_amount,
            'refund_amount' => $refund_amount,
            'fee_percent'   => $fee_percent,
        );
    }

    /**
     * Process a voluntary cancellation refund for a booking: works
     * out the total already paid, applies the fixed cancellation
     * fee, marks the relevant payment row(s) as refunded, and hands
     * off to Moga_Booking::cancel_booking() for status + availability.
     *
     * This is NOT used for the auto-cancel-for-nonpayment cron path
     * (Moga_Booking::auto_cancel_unpaid_balances()) — that keeps the
     * full deposit per yesterday's locked decision and never calls
     * this method, since there's nothing to refund in that case.
     *
     * @since  1.0.0
     * @param  int    $booking_id Booking ID.
     * @param  string $reason     Cancellation reason.
     * @return array|WP_Error {
     *     @type float $fee_amount    Amount kept.
     *     @type float $refund_amount Amount refunded.
     * } or WP_Error on failure.
     */
    public function process_cancellation_refund($booking_id, $reason = '')
    {
        $core = function_exists('moga_core') ? moga_core() : null;
        if (! $core || ! $core->booking) {
            return new WP_Error('booking_unavailable', __('Booking engine is not available.', 'moga-travel-core'));
        }

        $booking = $core->booking->get_booking($booking_id);
        if (! $booking) {
            return new WP_Error('not_found', __('Booking not found.', 'moga-travel-core'));
        }

        $amount_paid = round((float) $booking['total_amount'] - (float) $booking['balance_due'], 2);

        if ($amount_paid <= 0) {
            // Nothing was ever paid — just cancel, no refund math needed.
            $cancel_result = $core->booking->cancel_booking($booking_id, $reason);
            if (is_wp_error($cancel_result)) {
                return $cancel_result;
            }
            return array('fee_amount' => 0.00, 'refund_amount' => 0.00);
        }

        $breakdown = $this->calculate_cancellation_refund($amount_paid, (int) $booking['owner_id']);

        // Apply the refund against completed payments, oldest first,
        // until the refundable amount is fully allocated. A single
        // payment row is marked 'refunded' as soon as any refund
        // amount is applied to it — see the REFUND ENUM NOTE at the
        // top of this file for why partial-per-row isn't tracked
        // separately.
        global $wpdb;
        $prefix = $wpdb->prefix . MOGA_CORE_DB_PREFIX;

        $completed_payments = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$prefix}payments
             WHERE booking_id = %d AND status = 'completed'
             ORDER BY created_at ASC",
            absint($booking_id)
        ), ARRAY_A);

        $remaining_to_refund = $breakdown['refund_amount'];

        foreach ($completed_payments as $payment) {
            if ($remaining_to_refund <= 0) {
                break;
            }

            $refund_for_this_row = min($remaining_to_refund, (float) $payment['amount']);

            $wpdb->update(
                "{$prefix}payments",
                array(
                    'status'        => 'refunded',
                    'refund_amount' => $refund_for_this_row,
                    'refund_reason' => sanitize_textarea_field($reason),
                    'refunded_at'   => current_time('mysql'),
                ),
                array('id' => $payment['id']),
                array('%s', '%f', '%s', '%s'),
                array('%d')
            );

            $remaining_to_refund = round($remaining_to_refund - $refund_for_this_row, 2);
        }

        $cancel_result = $core->booking->cancel_booking($booking_id, $reason);
        if (is_wp_error($cancel_result)) {
            return $cancel_result;
        }

        // Booking-level payment_status has no partial-refund value —
        // mark it 'refunded' to close out the payment lifecycle, per
        // the REFUND ENUM NOTE.
        $wpdb->update(
            "{$prefix}bookings",
            array('payment_status' => 'refunded'),
            array('id' => absint($booking_id)),
            array('%s'),
            array('%d')
        );

        return $breakdown;
    }


    // ============================================================
    // GATEWAY RESOLUTION
    // ============================================================

    /**
     * Resolve which gateway should be used for a given listing's
     * owner, following the locked resolution order:
     *   1. Vendor's own gateway choice (user meta, if set)
     *   2. Site-level default gateway
     *   3. 'offline' as the universal fallback
     *
     * @since  1.0.0
     * @param  int $owner_id The listing owner's user ID.
     * @return string Gateway slug.
     */
    public function get_vendor_gateway($owner_id)
    {
        $vendor_choice = get_user_meta(absint($owner_id), '_moga_vendor_gateway', true);

        if ($vendor_choice && $this->is_gateway_enabled($vendor_choice)) {
            return $vendor_choice;
        }

        $site_default = $this->get_site_default_gateway();
        if ($site_default) {
            return $site_default;
        }

        return 'offline';
    }

    /**
     * Get the site-level default gateway — the first enabled one
     * among the registered payment settings, in a fixed priority
     * order. Both moga_payment_stripe and moga_payment_paypal are
     * already registered in class-moga-admin-settings.php, but with
     * no admin UI yet, so this returns null until an admin actually
     * configures one.
     *
     * @since  1.0.0
     * @return string|null Gateway slug, or null if none configured.
     */
    public function get_site_default_gateway()
    {
        $priority = array('stripe', 'paypal');

        foreach ($priority as $gateway) {
            if ($this->is_gateway_enabled($gateway)) {
                return $gateway;
            }
        }

        return null;
    }

    /**
     * Whether a given gateway is enabled at the site level.
     *
     * @since  1.0.0
     * @param  string $gateway 'stripe'|'paypal'|'offline'.
     * @return bool
     */
    public function is_gateway_enabled($gateway)
    {
        $option_map = array(
            'stripe'  => 'moga_payment_stripe',
            'paypal'  => 'moga_payment_paypal',
            'offline' => 'moga_payment_offline',
        );

        if (! isset($option_map[$gateway])) {
            return false;
        }

        return (bool) get_option($option_map[$gateway], false);
    }


    // ============================================================
    // GATEWAY WEBHOOK (STRUCTURAL PLACEHOLDER)
    // ============================================================

    /**
     * Entry point for gateway webhooks (Stripe/PayPal payment
     * confirmations arriving asynchronously). Not yet wired to any
     * real endpoint — class-moga-rest-api.php doesn't have a route
     * for this yet. Returns a clear error for anything but 'offline'
     * so it's honest about being incomplete rather than pretending
     * to handle gateways that were never actually integrated.
     *
     * @since  1.0.0
     * @param  string $gateway 'stripe'|'paypal'.
     * @param  array  $payload Raw webhook payload.
     * @return WP_Error Always, until a real gateway is integrated.
     */
    public function handle_gateway_webhook($gateway, array $payload)
    {
        return new WP_Error(
            'gateway_not_configured',
            sprintf(
                /* translators: %s: gateway name */
                __('The %s gateway is not yet configured. No specific payment provider has been selected for live integration.', 'moga-travel-core'),
                $gateway
            )
        );
    }
}
