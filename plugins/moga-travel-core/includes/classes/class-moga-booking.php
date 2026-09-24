<?php

/**
 * Booking Management Class
 *
 * Central orchestrator for the booking lifecycle: creation,
 * status transitions, deposit calculation, cancellation, and
 * the two recurring cron jobs already scheduled in
 * Moga_Core::schedule_cron_jobs() — moga_expire_pending_bookings
 * (30-min) and moga_send_booking_reminders (daily).
 *
 * IMPORTANT: schedule_cron_jobs() only queues these events with
 * wp_schedule_event() — it does not register what runs when they
 * fire. That registration happens here, in the constructor, since
 * this class is the thing that should actually run. Without this,
 * the two cron jobs fire on schedule but do nothing at all.
 *
 * Deliberately reuses existing verified helpers rather than
 * reimplementing their logic:
 *   - moga_validate_dates()          (helper-date.php)
 *   - moga_validate_stay_length()    (helper-date.php)
 *   - moga_is_available()            (helper-functions.php)
 *   - moga_calculate_property_price() / moga_calculate_tour_price()
 *                                     (helper-price.php)
 *   - moga_generate_booking_number() (helper-functions.php)
 *
 * SCOPE NOTE: create_booking() currently supports 'property',
 * 'rental' (treated identically to property — both are date-range,
 * per-night pricing), and 'tour' booking types. 'bus' seat booking
 * is intentionally NOT handled here — it needs seat selection,
 * not date-range availability, and belongs with class-moga-seat-map.php
 * once that's built. Calling create_booking() with booking_type
 * 'bus' returns a WP_Error rather than silently doing the wrong thing.
 *
 * DEPOSIT META KEYS NOTE: calculate_deposit_amount() reads
 * '_moga_deposit_type' and '_moga_deposit_value' post meta on the
 * listing. These are NEW keys — they were not part of the 35/44
 * meta fields originally documented for the Property/Tour CPTs in
 * Phase 3, because the hybrid deposit decision was made after that
 * work. No admin meta box UI exists yet to let a vendor set them —
 * that's a follow-up task for class-moga-admin-metaboxes.php. Until
 * then, every listing simply falls back to the platform's 20% floor,
 * so the booking engine is fully functional without it.
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
 * Class Moga_Booking
 */
class Moga_Booking
{

    /**
     * Register the cron callbacks for the two jobs already
     * scheduled by Moga_Core::schedule_cron_jobs().
     *
     * @since  1.0.0
     */
    public function __construct()
    {
        add_action('moga_expire_pending_bookings', array($this, 'expire_pending_bookings'));
        add_action('moga_send_booking_reminders', array($this, 'run_daily_balance_check'));
    }


    // ============================================================
    // CREATE
    // ============================================================

    /**
     * Create a new booking.
     *
     * Expected $data keys:
     *   booking_type     (string) property|tour|rental. Required.
     *   listing_id       (int)    Property or tour post ID. Required.
     *   guest_id         (int)    Defaults to current user if omitted.
     *   check_in         (string) Y-m-d. Required.
     *   check_out        (string) Y-m-d. Required.
     *   guests_adults    (int)    Defaults to 1.
     *   guests_children  (int)    Defaults to 0.
     *   guests_infants   (int)    Defaults to 0.
     *   special_requests (string) Optional free text.
     *
     * @since  1.0.0
     * @param  array $data Booking input, see above.
     * @return int|WP_Error Booking ID on success, WP_Error on failure.
     */
    public function create_booking(array $data)
    {
        $booking_type = isset($data['booking_type']) ? sanitize_key($data['booking_type']) : 'property';
        $valid_types  = array('property', 'tour', 'rental');

        if (! in_array($booking_type, $valid_types, true)) {
            return new WP_Error(
                'invalid_booking_type',
                __('This booking type is not supported yet, or is invalid.', 'moga-travel-core')
            );
        }

        $listing_id = isset($data['listing_id']) ? absint($data['listing_id']) : 0;
        $guest_id   = isset($data['guest_id']) ? absint($data['guest_id']) : get_current_user_id();

        if (! $listing_id) {
            return new WP_Error('missing_listing', __('Listing ID is required.', 'moga-travel-core'));
        }
        if (! $guest_id) {
            return new WP_Error('missing_guest', __('You must be logged in to book.', 'moga-travel-core'));
        }

        $owner_id = (int) get_post_field('post_author', $listing_id);
        if (! $owner_id) {
            return new WP_Error('invalid_listing', __('Listing not found.', 'moga-travel-core'));
        }

        $check_in  = isset($data['check_in']) ? sanitize_text_field($data['check_in']) : '';
        $check_out = isset($data['check_out']) ? sanitize_text_field($data['check_out']) : '';

        // Reuse the existing date validator.
        $date_check = moga_validate_dates($check_in, $check_out);
        if (is_wp_error($date_check)) {
            return $date_check;
        }

        $adults   = isset($data['guests_adults']) ? max(1, absint($data['guests_adults'])) : 1;
        $children = isset($data['guests_children']) ? absint($data['guests_children']) : 0;
        $infants  = isset($data['guests_infants']) ? absint($data['guests_infants']) : 0;

        // Reuse the existing availability checker rather than
        // writing new SQL against mg_moga_availability. For tours,
        // 'check_in' IS the chosen Group's start date, and the real
        // requested seat count (adults + children — infants don't
        // occupy a seat, matching moga_get_tour_group_seats_taken()'s
        // own convention) must be passed through, since a tour's
        // availability is a genuine capacity check, not a simple
        // date-blocked check.
        $availability_type = ('tour' === $booking_type) ? 'tour' : 'property';
        $requested_seats    = $adults + $children;

        if (! moga_is_available($listing_id, $check_in, $check_out, $availability_type, $requested_seats)) {
            return new WP_Error(
                'not_available',
                'tour' === $availability_type
                    ? __('This departure no longer has enough seats available.', 'moga-travel-core')
                    : __('These dates are no longer available for this listing.', 'moga-travel-core')
            );
        }

        // SECURITY FIX (Aug 19 session): min/max stay was previously
        // enforced only client-side (booking.js/Flatpickr) — a real
        // gap, since a guest could bypass the calendar entirely by
        // submitting a booking request directly. Property/rental
        // only — tours have no min/max-stay concept.
        if ('property' === $availability_type) {
            $stay_check = moga_validate_stay_length($listing_id, $check_in, $check_out);
            if (is_wp_error($stay_check)) {
                return $stay_check;
            }
        }

        // Reuse the existing price calculators.
        if ('tour' === $booking_type) {
            // BUG FIX: this was calling moga_calculate_tour_price()
            // with its OLD signature (no group identifier at all) —
            // left over from before Tour Groups existed. 'check_in'
            // is the chosen Group's start date, required now to know
            // WHICH group's real price/capacity to use.
            $price = moga_calculate_tour_price($listing_id, $check_in, $adults, $children, $infants);
        } else {
            $price = moga_calculate_property_price($listing_id, $check_in, $check_out);
        }

        if (empty($price['total']) || $price['total'] <= 0) {
            return new WP_Error(
                'invalid_price',
                __('Unable to calculate a valid price for this listing. Please check its pricing settings.', 'moga-travel-core')
            );
        }

        $deposit_amount = $this->calculate_deposit_amount((float) $price['total'], $listing_id);
        $balance_due    = round((float) $price['total'] - $deposit_amount, 2);

        $booking_number = moga_generate_booking_number();

        $nights          = isset($price['nights']) ? intval($price['nights']) : moga_calculate_nights($check_in, $check_out);
        $price_per_night = isset($price['price_per_night']) ? (float) $price['price_per_night'] : 0.00;

        global $wpdb;
        $prefix = $wpdb->prefix . MOGA_CORE_DB_PREFIX;

        $inserted = $wpdb->insert(
            "{$prefix}bookings",
            array(
                'booking_number'   => $booking_number,
                'booking_type'     => $booking_type,
                'listing_id'       => $listing_id,
                'guest_id'         => $guest_id,
                'owner_id'         => $owner_id,
                'check_in'         => $check_in,
                'check_out'        => $check_out,
                'guests_adults'    => $adults,
                'guests_children'  => $children,
                'guests_infants'   => $infants,
                'total_nights'     => max(1, $nights),
                'price_per_night'  => $price_per_night,
                'subtotal'         => (float) $price['subtotal'],
                'discount'         => (float) $price['discount'],
                'taxes'            => (float) $price['taxes'],
                'total_amount'     => (float) $price['total'],
                'deposit_amount'   => $deposit_amount,
                'balance_due'      => $balance_due,
                'currency'         => $price['currency'],
                'status'           => 'pending',
                'payment_status'   => 'unpaid',
                'special_requests' => isset($data['special_requests'])
                    ? sanitize_textarea_field($data['special_requests'])
                    : null,
            ),
            array(
                '%s',
                '%s',
                '%d',
                '%d',
                '%d',
                '%s',
                '%s',
                '%d',
                '%d',
                '%d',
                '%d',
                '%f',
                '%f',
                '%f',
                '%f',
                '%f',
                '%f',
                '%f',
                '%s',
                '%s',
                '%s',
                '%s',
            )
        );

        if (false === $inserted) {
            return new WP_Error(
                'db_error',
                __('Could not create the booking due to a database error. Please try again.', 'moga-travel-core')
            );
        }

        $booking_id = $wpdb->insert_id;

        // Note for tours: this marks the date's row status in
        // mg_moga_availability, same as properties — but tour
        // capacity checking (moga_is_tour_group_available()) counts
        // real bookings directly from mg_moga_bookings, never reads
        // this status at all. Left in place since other, unrelated
        // consumers of this table (e.g. an admin availability
        // calendar) may still expect it — but it no longer has any
        // effect on whether a tour group is considered full.
        $availability_manager = $this->get_availability_manager();
        if ($availability_manager) {
            $availability_manager->block_dates($listing_id, $availability_type, $check_in, $check_out, $booking_id);
        }

        // Fire notification hook — triggers guest confirmation + owner alert.
        $new_booking = $this->get_booking( $booking_id );
        if ( $new_booking ) {
            do_action( 'moga_booking_created', $booking_id, $new_booking );
        }

        return $booking_id;
    }


    // ============================================================
    // READ
    // ============================================================

    /**
     * Fetch a single booking, including its booking_meta rows.
     *
     * @since  1.0.0
     * @param  int $booking_id Booking ID.
     * @return array|null Associative array of the booking row plus
     *                     a 'meta' key, or null if not found.
     */
    public function get_booking($booking_id)
    {
        global $wpdb;
        $prefix = $wpdb->prefix . MOGA_CORE_DB_PREFIX;

        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$prefix}bookings WHERE id = %d", absint($booking_id)),
            ARRAY_A
        );

        if (! $row) {
            return null;
        }

        $row['meta'] = $this->get_all_booking_meta($booking_id);

        return $row;
    }

    /**
     * Fetch a filtered, paginated list of bookings.
     *
     * Accepted $args keys (all optional):
     *   guest_id, owner_id, listing_id, booking_type,
     *   status, payment_status,
     *   check_in_after, check_in_before (Y-m-d),
     *   orderby (column name, default 'created_at'),
     *   order ('ASC'|'DESC', default 'DESC'),
     *   per_page (default 20), paged (default 1).
     *
     * @since  1.0.0
     * @param  array $args Filter/pagination args.
     * @return array List of associative booking rows (no meta — call
     *               get_booking() for a single row's meta).
     */
    public function get_bookings(array $args = array())
    {
        $defaults = array(
            'guest_id'        => 0,
            'owner_id'        => 0,
            'listing_id'      => 0,
            'booking_type'    => '',
            'status'          => '',
            'payment_status'  => '',
            'check_in_after'  => '',
            'check_in_before' => '',
            'orderby'         => 'created_at',
            'order'           => 'DESC',
            'per_page'        => 20,
            'paged'           => 1,
        );
        $args = wp_parse_args($args, $defaults);

        global $wpdb;
        $prefix = $wpdb->prefix . MOGA_CORE_DB_PREFIX;

        $where  = array('1=1');
        $params = array();

        if ($args['guest_id']) {
            $where[]  = 'guest_id = %d';
            $params[] = absint($args['guest_id']);
        }
        if ($args['owner_id']) {
            $where[]  = 'owner_id = %d';
            $params[] = absint($args['owner_id']);
        }
        if ($args['listing_id']) {
            $where[]  = 'listing_id = %d';
            $params[] = absint($args['listing_id']);
        }
        if ($args['booking_type']) {
            $where[]  = 'booking_type = %s';
            $params[] = sanitize_key($args['booking_type']);
        }
        if ($args['status']) {
            $where[]  = 'status = %s';
            $params[] = sanitize_key($args['status']);
        }
        if ($args['payment_status']) {
            $where[]  = 'payment_status = %s';
            $params[] = sanitize_key($args['payment_status']);
        }
        if ($args['check_in_after']) {
            $where[]  = 'check_in >= %s';
            $params[] = sanitize_text_field($args['check_in_after']);
        }
        if ($args['check_in_before']) {
            $where[]  = 'check_in <= %s';
            $params[] = sanitize_text_field($args['check_in_before']);
        }

        // Whitelist orderby/order to avoid building raw SQL from user input.
        $allowed_orderby = array('created_at', 'check_in', 'check_out', 'total_amount', 'status');
        $orderby = in_array($args['orderby'], $allowed_orderby, true) ? $args['orderby'] : 'created_at';
        $order   = 'ASC' === strtoupper($args['order']) ? 'ASC' : 'DESC';

        $per_page = max(1, intval($args['per_page']));
        $paged    = max(1, intval($args['paged']));
        $offset   = ($paged - 1) * $per_page;

        $sql = "SELECT * FROM {$prefix}bookings WHERE " . implode(' AND ', $where)
            . " ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";

        $params[] = $per_page;
        $params[] = $offset;

        return $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);
    }


    // ============================================================
    // STATUS LIFECYCLE
    // ============================================================

    /**
     * Map of allowed status transitions.
     *
     * @since  1.0.0
     * @return array current_status => array of allowed next statuses.
     */
    private function get_valid_transitions()
    {
        return array(
            'pending'   => array('confirmed', 'cancelled'),
            'confirmed' => array('completed', 'cancelled', 'no_show'),
            'completed' => array('refunded'),
            'cancelled' => array(),
            'refunded'  => array(),
            'no_show'   => array(),
        );
    }

    /**
     * Move a booking to a new status, enforcing valid transitions.
     *
     * @since  1.0.0
     * @param  int    $booking_id Booking ID.
     * @param  string $new_status One of the mg_moga_bookings status enum values.
     * @return true|WP_Error
     */
    public function update_status($booking_id, $new_status)
    {
        $valid_statuses = array('pending', 'confirmed', 'cancelled', 'completed', 'refunded', 'no_show');

        if (! in_array($new_status, $valid_statuses, true)) {
            return new WP_Error('invalid_status', __('Invalid booking status.', 'moga-travel-core'));
        }

        $booking = $this->get_booking($booking_id);
        if (! $booking) {
            return new WP_Error('not_found', __('Booking not found.', 'moga-travel-core'));
        }

        $current = $booking['status'];
        if ($current === $new_status) {
            return true;
        }

        $transitions = $this->get_valid_transitions();
        if (! in_array($new_status, $transitions[$current] ?? array(), true)) {
            return new WP_Error(
                'invalid_transition',
                sprintf(
                    /* translators: 1: current status, 2: attempted new status */
                    __('Cannot change booking status from %1$s to %2$s.', 'moga-travel-core'),
                    $current,
                    $new_status
                )
            );
        }

        global $wpdb;
        $prefix = $wpdb->prefix . MOGA_CORE_DB_PREFIX;

        $updated = $wpdb->update(
            "{$prefix}bookings",
            array('status' => $new_status),
            array('id' => absint($booking_id)),
            array('%s'),
            array('%d')
        );

        if ( false !== $updated ) {
            // Fire notification hooks for relevant status transitions.
            $booking = $this->get_booking( $booking_id );
            if ( $booking ) {
                switch ( $new_status ) {
                    case 'confirmed':
                        do_action( 'moga_booking_confirmed', $booking_id, $booking );
                        break;
                    case 'cancelled':
                        do_action( 'moga_booking_cancelled', $booking_id, $booking );
                        break;
                    case 'completed':
                        do_action( 'moga_booking_completed', $booking_id, $booking );
                        break;
                }
            }
            return true;
        }

        return new WP_Error('db_error', __('Could not update booking status.', 'moga-travel-core'));
    }

    /**
     * Cancel a booking and release its blocked dates.
     * Does NOT process any refund — that is class-moga-payment.php's
     * responsibility once it exists; this only updates status,
     * records the reason, and frees the availability calendar.
     *
     * @since  1.0.0
     * @param  int    $booking_id Booking ID.
     * @param  string $reason     Cancellation reason.
     * @param  bool   $by_guest   Whether the guest (vs. system/owner) cancelled.
     * @return true|WP_Error
     */
    public function cancel_booking($booking_id, $reason = '', $by_guest = true)
    {
        $booking = $this->get_booking($booking_id);
        if (! $booking) {
            return new WP_Error('not_found', __('Booking not found.', 'moga-travel-core'));
        }

        $result = $this->update_status($booking_id, 'cancelled');
        if (is_wp_error($result)) {
            return $result;
        }

        global $wpdb;
        $prefix = $wpdb->prefix . MOGA_CORE_DB_PREFIX;

        $wpdb->update(
            "{$prefix}bookings",
            array('cancellation_reason' => sanitize_textarea_field($reason)),
            array('id' => absint($booking_id)),
            array('%s'),
            array('%d')
        );

        $availability_type = ('tour' === $booking['booking_type']) ? 'tour' : 'property';

        $availability_manager = $this->get_availability_manager();
        if ($availability_manager) {
            $availability_manager->release_dates(
                $booking['listing_id'],
                $availability_type,
                $booking['check_in'],
                $booking['check_out'],
                $booking_id
            );
        }

        return true;
    }


    // ============================================================
    // DEPOSIT / BALANCE
    // ============================================================

    /**
     * Calculate the deposit required for a booking, applying the
     * vendor's chosen deposit type/value with the platform-wide
     * floor enforced (locked decision: 20% minimum).
     *
     * See the DEPOSIT META KEYS NOTE in this file's header docblock
     * regarding '_moga_deposit_type' / '_moga_deposit_value'.
     *
     * @since  1.0.0
     * @param  float $total_amount Booking total.
     * @param  int   $listing_id   Property or tour post ID.
     * @return float Deposit amount, rounded to 2 decimals.
     */
    public function calculate_deposit_amount($total_amount, $listing_id)
    {
        $total_amount = (float) $total_amount;
        $floor_percent = (float) get_option('moga_deposit_floor_percent', 20);

        $deposit_type  = get_post_meta($listing_id, '_moga_deposit_type', true);
        $deposit_value = get_post_meta($listing_id, '_moga_deposit_value', true);

        if (! $deposit_type) {
            $deposit_type = 'percentage';
        }
        if ('' === $deposit_value || null === $deposit_value) {
            $deposit_value = $floor_percent;
        } else {
            $deposit_value = (float) $deposit_value;
        }

        switch ($deposit_type) {
            case 'full':
                return round($total_amount, 2);

            case 'fixed':
                $floor_amount = round($total_amount * ($floor_percent / 100), 2);
                return round(max($deposit_value, $floor_amount), 2);

            case 'percentage':
            default:
                $percent = max($deposit_value, $floor_percent);
                return round($total_amount * ($percent / 100), 2);
        }
    }

    /**
     * Record a payment against a booking's balance. Called by
     * class-moga-payment.php after a transaction completes — this
     * method does not process payment itself, only the bookkeeping.
     *
     * A booking moves from 'pending' to 'confirmed' automatically
     * once the amount paid to date meets or exceeds the required
     * deposit — not necessarily full payment.
     *
     * @since  1.0.0
     * @param  int    $booking_id   Booking ID.
     * @param  float  $amount       Amount just paid.
     * @param  string $payment_type deposit|remainder|full — for the
     *                               caller's own mg_moga_payments row;
     *                               not used in this method's math.
     * @return true|WP_Error
     */
    public function record_payment_received($booking_id, $amount, $payment_type = 'full')
    {
        $booking = $this->get_booking($booking_id);
        if (! $booking) {
            return new WP_Error('not_found', __('Booking not found.', 'moga-travel-core'));
        }

        $amount      = (float) $amount;
        $new_balance = max(0, round((float) $booking['balance_due'] - $amount, 2));
        $new_status  = $new_balance <= 0 ? 'paid' : 'partially_paid';

        global $wpdb;
        $prefix = $wpdb->prefix . MOGA_CORE_DB_PREFIX;

        $updated = $wpdb->update(
            "{$prefix}bookings",
            array(
                'balance_due'    => $new_balance,
                'payment_status' => $new_status,
            ),
            array('id' => absint($booking_id)),
            array('%f', '%s'),
            array('%d')
        );

        if (false === $updated) {
            return new WP_Error('db_error', __('Could not record the payment.', 'moga-travel-core'));
        }

        $amount_paid_so_far = round((float) $booking['total_amount'] - $new_balance, 2);

        if ('pending' === $booking['status'] && $amount_paid_so_far >= (float) $booking['deposit_amount']) {
            $this->update_status($booking_id, 'confirmed');
        }

        return true;
    }

    /**
     * Whether a booking's balance is fully settled.
     *
     * @since  1.0.0
     * @param  int $booking_id Booking ID.
     * @return bool
     */
    public function is_fully_paid($booking_id)
    {
        $booking = $this->get_booking($booking_id);
        return $booking && (float) $booking['balance_due'] <= 0;
    }


    // ============================================================
    // CRON: PENDING BOOKING EXPIRY (moga_expire_pending_bookings)
    // ============================================================

    /**
     * Cancel bookings that were never paid at all (still 'pending'
     * and 'unpaid') past the configured expiry window, and release
     * their held dates. Reads moga_booking_expiry (minutes),
     * registered by Moga_Admin_Settings, default 30 — matching the
     * 30-minute cron interval this runs on.
     *
     * @since  1.0.0
     * @return void
     */
    public function expire_pending_bookings()
    {
        global $wpdb;
        $prefix = $wpdb->prefix . MOGA_CORE_DB_PREFIX;

        $expiry_minutes = intval(get_option('moga_booking_expiry', 30));
        $cutoff = gmdate('Y-m-d H:i:s', strtotime("-{$expiry_minutes} minutes"));

        $expired = $wpdb->get_results($wpdb->prepare(
            "SELECT id, listing_id, booking_type, check_in, check_out FROM {$prefix}bookings
             WHERE status = 'pending' AND payment_status = 'unpaid' AND created_at < %s",
            $cutoff
        ));

        $availability_manager = $this->get_availability_manager();

        foreach ($expired as $booking) {
            $this->update_status($booking->id, 'cancelled');

            $wpdb->update(
                "{$prefix}bookings",
                array('cancellation_reason' => __('Expired — no payment received in time.', 'moga-travel-core')),
                array('id' => $booking->id),
                array('%s'),
                array('%d')
            );

            $availability_type = ('tour' === $booking->booking_type) ? 'tour' : 'property';

            if ($availability_manager) {
                $availability_manager->release_dates(
                    $booking->listing_id,
                    $availability_type,
                    $booking->check_in,
                    $booking->check_out,
                    $booking->id
                );
            }
        }
    }


    // ============================================================
    // CRON: BALANCE REMINDERS + AUTO-CANCEL (moga_send_booking_reminders)
    // ============================================================

    /**
     * Single entry point for the daily cron — runs the escalating
     * reminder schedule, then checks for bookings that ran out the
     * clock and auto-cancels them.
     *
     * @since  1.0.0
     * @return void
     */
    public function run_daily_balance_check()
    {
        $this->send_balance_reminders();
        $this->auto_cancel_unpaid_balances();
    }

    /**
     * Send escalating reminders for confirmed bookings with an
     * outstanding balance, per the locked schedule:
     *   > 14 days before check-in  → every 3 days
     *   7–14 days before check-in  → every 2 days
     *   2–7 days before check-in   → daily
     *   1 day before check-in      → one final warning (sent once)
     *
     * Delegates the actual sending to Moga_Notification, which does
     * not exist yet — guarded with class_exists() so this runs
     * safely (tracking reminder timestamps correctly) even before
     * that class is written; it just won't send anything yet.
     *
     * @since  1.0.0
     * @return void
     */
    public function send_balance_reminders()
    {
        global $wpdb;
        $prefix = $wpdb->prefix . MOGA_CORE_DB_PREFIX;

        $bookings = $wpdb->get_results(
            "SELECT id, check_in, guest_id, owner_id, balance_due FROM {$prefix}bookings
             WHERE status = 'confirmed' AND balance_due > 0 AND check_in >= CURDATE()"
        );

        $today = current_time('timestamp'); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested

        foreach ($bookings as $booking) {
            $days_until = (int) ceil((strtotime($booking->check_in) - $today) / DAY_IN_SECONDS);

            if ($days_until < 0) {
                continue; // Past check-in — auto_cancel_unpaid_balances() handles this.
            }

            // Final warning, exactly one day out, sent only once.
            if (1 === $days_until) {
                if (! $this->get_booking_meta($booking->id, 'final_warning_sent')) {
                    $this->trigger_reminder($booking->id, 'final');
                    $this->update_booking_meta($booking->id, 'final_warning_sent', current_time('mysql'));
                }
                continue;
            }

            if ($days_until > 14) {
                $required_gap = 3;
            } elseif ($days_until >= 7) {
                $required_gap = 2;
            } else {
                $required_gap = 1; // Covers the 2–6 day window ("daily").
            }

            $last_sent = $this->get_booking_meta($booking->id, 'last_reminder_sent_at');
            $should_send = true;

            if ($last_sent) {
                $days_since_last = ($today - strtotime($last_sent)) / DAY_IN_SECONDS;
                $should_send = $days_since_last >= $required_gap;
            }

            if ($should_send) {
                $this->trigger_reminder($booking->id, 'standard');
                $this->update_booking_meta($booking->id, 'last_reminder_sent_at', current_time('mysql'));
            }
        }
    }

    /**
     * Cancel confirmed bookings whose check-in date has arrived (or
     * passed) with a balance still due — the final-warning deadline
     * has run out. Per the locked decision, the guest forfeits the
     * entire deposit already paid; no refund is issued here.
     *
     * @since  1.0.0
     * @return void
     */
    public function auto_cancel_unpaid_balances()
    {
        global $wpdb;
        $prefix = $wpdb->prefix . MOGA_CORE_DB_PREFIX;

        $overdue = $wpdb->get_results(
            "SELECT id, listing_id, booking_type, check_in, check_out, guest_id, owner_id
             FROM {$prefix}bookings
             WHERE status = 'confirmed' AND balance_due > 0 AND check_in <= CURDATE()"
        );

        $availability_manager = $this->get_availability_manager();

        foreach ($overdue as $booking) {
            $this->update_status($booking->id, 'cancelled');

            $wpdb->update(
                "{$prefix}bookings",
                array(
                    'cancellation_reason' => __('Auto-cancelled — remaining balance was not paid in time. Deposit forfeited.', 'moga-travel-core'),
                ),
                array('id' => $booking->id),
                array('%s'),
                array('%d')
            );

            $availability_type = ('tour' === $booking->booking_type) ? 'tour' : 'property';

            if ($availability_manager) {
                $availability_manager->release_dates(
                    $booking->listing_id,
                    $availability_type,
                    $booking->check_in,
                    $booking->check_out,
                    $booking->id
                );
            }

            $this->trigger_reminder($booking->id, 'cancelled');
        }
    }

    /**
     * Hand off a reminder/notice event to Moga_Notification, if it
     * exists yet. Kept as a single choke point so wiring in the
     * real notification class later is a one-line change here, not
     * a rewrite of the cron methods above.
     *
     * @since  1.0.0
     * @param  int    $booking_id Booking ID.
     * @param  string $type       'standard'|'final'|'cancelled'.
     * @return void
     */
    private function trigger_reminder($booking_id, $type)
    {
        $core = function_exists('moga_core') ? moga_core() : null;

        if ($core && $core->notification && method_exists($core->notification, 'send_balance_reminder')) {
            $core->notification->send_balance_reminder($booking_id, $type);
        }
    }


    // ============================================================
    // BOOKING META HELPERS
    // ============================================================

    /**
     * Get a single booking meta value.
     *
     * @since  1.0.0
     * @param  int    $booking_id Booking ID.
     * @param  string $key        Meta key.
     * @return string|null
     */
    public function get_booking_meta($booking_id, $key)
    {
        global $wpdb;
        $prefix = $wpdb->prefix . MOGA_CORE_DB_PREFIX;

        return $wpdb->get_var($wpdb->prepare(
            "SELECT meta_value FROM {$prefix}booking_meta WHERE booking_id = %d AND meta_key = %s LIMIT 1",
            absint($booking_id),
            $key
        ));
    }

    /**
     * Get all meta for a booking as a flat key => value array.
     *
     * @since  1.0.0
     * @param  int $booking_id Booking ID.
     * @return array
     */
    public function get_all_booking_meta($booking_id)
    {
        global $wpdb;
        $prefix = $wpdb->prefix . MOGA_CORE_DB_PREFIX;

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT meta_key, meta_value FROM {$prefix}booking_meta WHERE booking_id = %d",
            absint($booking_id)
        ), ARRAY_A);

        $meta = array();
        foreach ($rows as $row) {
            $meta[$row['meta_key']] = $row['meta_value'];
        }

        return $meta;
    }

    /**
     * Set (replacing any existing) a single booking meta value.
     * mg_moga_booking_meta has no unique key on (booking_id, meta_key),
     * so "update" here means delete-then-insert to keep single-value
     * semantics per key, which is what every current caller needs.
     *
     * @since  1.0.0
     * @param  int    $booking_id Booking ID.
     * @param  string $key        Meta key.
     * @param  mixed  $value      Meta value (stored as-is via longtext).
     * @return void
     */
    public function update_booking_meta($booking_id, $key, $value)
    {
        global $wpdb;
        $prefix = $wpdb->prefix . MOGA_CORE_DB_PREFIX;

        $wpdb->delete(
            "{$prefix}booking_meta",
            array('booking_id' => absint($booking_id), 'meta_key' => $key),
            array('%d', '%s')
        );

        $wpdb->insert(
            "{$prefix}booking_meta",
            array(
                'booking_id' => absint($booking_id),
                'meta_key'   => $key,
                'meta_value' => maybe_serialize($value),
            ),
            array('%d', '%s', '%s')
        );
    }


    // ============================================================
    // AVAILABILITY ACCESS (delegates to Moga_Availability)
    // ============================================================

    /**
     * Resolve the shared Moga_Availability instance, if available.
     * Guarded with function_exists()/null-checks the same way
     * trigger_reminder() resolves Moga_Notification, so this class
     * degrades safely rather than fataling if boot order ever changes.
     *
     * @since  1.0.0
     * @return Moga_Availability|null
     */
    private function get_availability_manager()
    {
        $core = function_exists('moga_core') ? moga_core() : null;
        return ($core && $core->availability) ? $core->availability : null;
    }
}
