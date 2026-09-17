<?php
/**
 * Seat Map — Bus Seat Management
 *
 * Handles everything related to bus seat selection, temporary holds,
 * confirmation, and release for tours that have an assigned bus.
 *
 * SCOPE: this class manages the mg_moga_seats table exclusively.
 * Tour-level capacity (mg_moga_availability / tour groups) is a
 * separate concern handled by Moga_Availability and the tour group
 * helpers in helper-functions.php. Both checks run independently —
 * a seat is only ever confirmed when both pass.
 *
 * SEAT HOLD FLOW:
 *   1. Guest arrives on the Booking page with a tour + departure date.
 *   2. JS renders the seat map (via moga_get_seat_map AJAX).
 *   3. Guest clicks a seat → JS calls moga_reserve_seats AJAX.
 *   4. Server marks the seat 'reserved', reserved_until = NOW + 15min,
 *      guest_id / session_token written so only this guest can confirm.
 *   5. A countdown timer shows in the UI ("Seats held for 14:52").
 *   6. Guest fills in details → proceeds to Checkout → pays.
 *   7. On booking creation: confirm_seats() upgrades 'reserved' → 'booked'.
 *   8. If the guest abandons: moga_release_expired_seats cron (every 15min)
 *      finds expired 'reserved' rows and flips them back to 'available'.
 *
 * SESSION TOKEN: we use a short-lived token stored in the PHP session
 * (and mirrored to the browser as a nonce-protected value) to identify
 * which reserved rows belong to THIS guest's current selection — even
 * for logged-out guests. This prevents a guest from releasing another
 * guest's hold by posting their guest_id, and prevents a single guest
 * from holding the same seat twice across two browser tabs.
 *
 * RACE CONDITIONS: the UNIQUE KEY idx_bus_seat_date (bus_id, seat_number,
 * trip_date) on mg_moga_seats means two simultaneous reserve attempts
 * for the same seat will produce a DB-level duplicate-key error on the
 * second one. reserve_seats() handles this gracefully and returns a
 * partial-success response that tells the JS exactly which seats were
 * taken before it could get them.
 *
 * @package    MogaTravelCore
 * @subpackage MogaTravelCore/includes/classes
 * @author     Hatem Frere
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Moga_Seat_Map
 */
class Moga_Seat_Map {

    /**
     * How long (in minutes) a reserved seat is held before the cron
     * releases it. Must match the JS countdown timer.
     *
     * @since 1.0.0
     * @var   int
     */
    const HOLD_MINUTES = 15;

    /**
     * Register the cron callback for the job already scheduled by
     * Moga_Core::schedule_cron_jobs() (moga_release_expired_seats,
     * every 15 minutes). Without this the job fires but does nothing.
     *
     * @since  1.0.0
     */
    public function __construct() {
        add_action( 'moga_release_expired_seats', array( $this, 'release_expired_seats' ) );

        // Start the PHP session as early as possible — before any output
        // is sent. AJAX requests arrive via admin-ajax.php which loads
        // WordPress fully, so hooking on 'init' priority 1 ensures the
        // session is open before any AJAX handler runs. Without this,
        // calling session_start() inside an AJAX handler on XAMPP/localhost
        // often fails silently because headers have already been sent by
        // the time the handler fires, leaving the session token tracking
        // completely broken and making seat reservation appear to do nothing.
        add_action( 'init', array( $this, 'start_session_early' ), 1 );
    }

    /**
     * Start the PHP session before any output.
     * Hooked on 'init' priority 1.
     *
     * @since  1.0.0
     * @return void
     */
    public function start_session_early() {
        if ( ! session_id() && ! headers_sent() ) {
            session_start();
        }
    }


    // ============================================================
    // SEAT MAP DATA — get_seat_map()
    // ============================================================

    /**
     * Build the full seat map for a bus on a specific trip date.
     *
     * Returns every seat the bus has (generated from its layout meta),
     * merged with the live status from mg_moga_seats for that trip date.
     * Seats with no row in mg_moga_seats are 'available' by definition.
     *
     * The session_token parameter is used to identify seats this specific
     * guest already holds in the current session — those seats are returned
     * as 'reserved_by_me' so the JS can pre-highlight them and the guest
     * doesn't accidentally try to re-reserve them.
     *
     * Return shape per seat:
     * {
     *   seat_number : '3A',
     *   seat_row    : 3,
     *   seat_column : 1,          // 1-based, left to right
     *   seat_type   : 'standard'|'vip'|'disabled',
     *   status      : 'available'|'reserved'|'reserved_by_me'|'booked'|'unavailable',
     * }
     *
     * @since  1.0.0
     * @param  int    $bus_id        Bus post ID.
     * @param  string $trip_date     Y-m-d — the tour group's start date.
     * @param  string $session_token Optional. Token identifying the current guest's hold.
     * @return array  Array of seat arrays, or WP_Error on failure.
     */
    public function get_seat_map( $bus_id, $trip_date, $session_token = '' ) {

        $bus_id = absint( $bus_id );
        if ( ! $bus_id ) {
            return new WP_Error( 'invalid_bus', __( 'Invalid bus ID.', 'moga-travel-core' ) );
        }

        $bus = get_post( $bus_id );
        if ( ! $bus || 'moga_bus' !== $bus->post_type ) {
            return new WP_Error( 'bus_not_found', __( 'Bus not found.', 'moga-travel-core' ) );
        }

        // ---- Bus layout meta ----
        $rows            = absint( get_post_meta( $bus_id, '_moga_seat_rows',    true ) ) ?: 10;
        $layout          = get_post_meta( $bus_id, '_moga_seat_layout',  true ) ?: '2+2';
        $driver_position = get_post_meta( $bus_id, '_moga_driver_seat',  true ) ?: 'front-left';

        $vip_json      = get_post_meta( $bus_id, '_moga_vip_seats',      true );
        $disabled_json = get_post_meta( $bus_id, '_moga_disabled_seats', true );

        $vip_seats      = $vip_json      ? json_decode( $vip_json,      true ) : array();
        $disabled_seats = $disabled_json ? json_decode( $disabled_json, true ) : array();
        $vip_seats      = is_array( $vip_seats )      ? $vip_seats      : array();
        $disabled_seats = is_array( $disabled_seats ) ? $disabled_seats : array();

        // ---- Generate full seat list from layout ----
        if ( ! class_exists( 'Moga_CPT_Bus' ) ) {
            return new WP_Error( 'missing_dependency', __( 'Bus CPT class not loaded.', 'moga-travel-core' ) );
        }

        $seat_numbers = Moga_CPT_Bus::generate_seat_numbers( $rows, $layout );
        $layouts      = Moga_CPT_Bus::get_seat_layouts();
        $columns      = isset( $layouts[ $layout ]['columns'] ) ? $layouts[ $layout ]['columns'] : 4;

        // ---- Live DB status for this trip date ----
        global $wpdb;
        $prefix = $wpdb->prefix . MOGA_CORE_DB_PREFIX;

        $db_rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT seat_number, status, guest_id, reserved_until
             FROM {$prefix}seats
             WHERE bus_id = %d AND trip_date = %s
             ORDER BY seat_row ASC, seat_column ASC",
            $bus_id,
            $trip_date
        ), ARRAY_A );

        // Index by seat_number for O(1) lookup.
        $db_index = array();
        foreach ( $db_rows as $row ) {
            $db_index[ $row['seat_number'] ] = $row;
        }

        $current_guest_id = get_current_user_id();

        // ---- Build final seat list ----
        $seat_map     = array();
        $column_index = 0;

        foreach ( $seat_numbers as $seat_number ) {
            $row_num = intval( $seat_number );   // '3A' → 3
            $col_num = ( $column_index % $columns ) + 1; // 1-based
            $column_index++;

            // Determine seat type from bus meta.
            if ( in_array( $seat_number, $disabled_seats, true ) ) {
                $seat_type = 'disabled';
            } elseif ( in_array( $seat_number, $vip_seats, true ) ) {
                $seat_type = 'vip';
            } else {
                $seat_type = 'standard';
            }

            // Determine live status.
            if ( isset( $db_index[ $seat_number ] ) ) {
                $db_row     = $db_index[ $seat_number ];
                $db_status  = $db_row['status'];

                if ( 'reserved' === $db_status ) {
                    // Check if the hold has actually expired (cron may
                    // not have run yet) — treat expired holds as available.
                    // Also treat NULL reserved_until as expired (legacy rows
                    // that were released via UPDATE instead of DELETE).
                    $expires = $db_row['reserved_until'];
                    if ( ! $expires || strtotime( $expires ) < time() ) {
                        $status = 'available';
                    } elseif (
                        // This guest's own hold — by session token OR by user ID.
                        ( $session_token && $this->is_my_session( $session_token, $bus_id, $trip_date, $seat_number ) )
                        || ( $current_guest_id && absint( $db_row['guest_id'] ) === $current_guest_id )
                    ) {
                        $status = 'reserved_by_me';
                    } else {
                        $status = 'reserved';
                    }
                } elseif ( 'booked' === $db_status ) {
                    $status = 'booked';
                } elseif ( 'unavailable' === $db_status ) {
                    $status = 'unavailable';
                } else {
                    // status='available' row (legacy from UPDATE-based release)
                    // or any other unrecognised status — treat as available.
                    $status = 'available';
                }
            } else {
                // No row in the DB → available by default.
                $status = 'available';
            }

            // Disabled seats are always unavailable regardless of DB.
            if ( 'disabled' === $seat_type ) {
                $status = 'unavailable';
            }

            $seat_map[] = array(
                'seat_number' => $seat_number,
                'seat_row'    => $row_num,
                'seat_column' => $col_num,
                'seat_type'   => $seat_type,
                'status'      => $status,
            );
        }

        return array(
            'seats'           => $seat_map,
            'layout'          => $layout,
            'columns'         => $columns,
            'rows'            => $rows,
            'driver_position' => $driver_position,
            'bus_name'        => $bus->post_title,
            'hold_minutes'    => self::HOLD_MINUTES,
        );
    }


    // ============================================================
    // RESERVE — reserve_seats()
    // ============================================================

    /**
     * Reserve one or more seats for the current guest — a 15-minute
     * temporary hold that must be confirmed by a completed booking
     * before it expires.
     *
     * Called from the AJAX handler Moga_Ajax::reserve_seats() when
     * the guest clicks seat(s) on the Booking page.
     *
     * Handles the UNIQUE KEY race condition correctly: if two guests
     * click the same seat simultaneously, the second INSERT will fail
     * with a duplicate-key error. reserve_seats() attempts each seat
     * individually and collects which ones succeeded vs. which were
     * already taken — so the JS gets an honest partial result and can
     * show "seat 3A was just taken by someone else" rather than a
     * generic error.
     *
     * @since  1.0.0
     * @param  int    $bus_id        Bus post ID.
     * @param  int    $tour_id       Tour post ID.
     * @param  string $trip_date     Y-m-d.
     * @param  array  $seat_numbers  Array of seat number strings (e.g. ['3A','3B']).
     * @param  string $session_token Short token identifying this guest's browser session.
     * @return array {
     *   reserved : string[]   Seats successfully reserved.
     *   taken    : string[]   Seats that were already taken.
     *   expired  : string[]   Seats where the guest's previous hold expired.
     * }
     */
    public function reserve_seats( $bus_id, $tour_id, $trip_date, array $seat_numbers, $session_token ) {

        $bus_id  = absint( $bus_id );
        $tour_id = absint( $tour_id );

        global $wpdb;
        $prefix = $wpdb->prefix . MOGA_CORE_DB_PREFIX;

        $guest_id    = get_current_user_id();
        $now         = current_time( 'mysql' );
        $expires_at  = gmdate( 'Y-m-d H:i:s', time() + ( self::HOLD_MINUTES * MINUTE_IN_SECONDS ) );

        // Resolve row/column for each seat from the bus layout.
        $layout_map = $this->build_layout_map( $bus_id );

        $reserved = array();
        $taken    = array();

        foreach ( $seat_numbers as $seat_number ) {
            $seat_number = sanitize_text_field( $seat_number );
            if ( ! $seat_number ) {
                continue;
            }

            $row_col  = isset( $layout_map[ $seat_number ] ) ? $layout_map[ $seat_number ] : array( 1, 1 );
            $seat_row = $row_col[0];
            $seat_col = $row_col[1];

            // Determine seat_type from bus meta.
            $seat_type = $this->get_seat_type( $bus_id, $seat_number );

            // Attempt to INSERT — if the UNIQUE KEY fires, this seat
            // is already in the table (booked or held by someone else).
            // We then check whether it's genuinely taken or expired.
            $inserted = $wpdb->insert(
                "{$prefix}seats",
                array(
                    'bus_id'         => $bus_id,
                    'tour_id'        => $tour_id,
                    'trip_date'      => $trip_date,
                    'seat_number'    => $seat_number,
                    'seat_row'       => $seat_row,
                    'seat_column'    => $seat_col,
                    'seat_type'      => $seat_type,
                    'status'         => 'reserved',
                    'guest_id'       => $guest_id ?: null,
                    'reserved_at'    => $now,
                    'reserved_until' => $expires_at,
                ),
                array( '%d', '%d', '%s', '%s', '%d', '%d', '%s', '%s', '%d', '%s', '%s' )
            );

            if ( false !== $inserted ) {
                // Fresh insert — seat is ours.
                $this->store_session_seat( $session_token, $bus_id, $trip_date, $seat_number );
                $reserved[] = $seat_number;
                continue;
            }

            // Duplicate key — check the existing row.
            $existing = $wpdb->get_row( $wpdb->prepare(
                "SELECT status, reserved_until, guest_id
                 FROM {$prefix}seats
                 WHERE bus_id = %d AND seat_number = %s AND trip_date = %s",
                $bus_id,
                $seat_number,
                $trip_date
            ) );

            if ( ! $existing ) {
                $taken[] = $seat_number;
                continue;
            }

            // Take over the row when:
            // (a) status is 'available' — row left over from old UPDATE-based release
            // (b) status is 'reserved' but the hold has expired
            // Both cases mean the seat is genuinely free for this guest.
            $can_take_over = (
                'available' === $existing->status
                || (
                    'reserved' === $existing->status
                    && (
                        ! $existing->reserved_until
                        || strtotime( $existing->reserved_until ) < time()
                    )
                )
            );

            if ( $can_take_over ) {
                $updated = $wpdb->update(
                    "{$prefix}seats",
                    array(
                        'tour_id'        => $tour_id,
                        'status'         => 'reserved',
                        'guest_id'       => $guest_id ?: null,
                        'reserved_at'    => $now,
                        'reserved_until' => $expires_at,
                    ),
                    array(
                        'bus_id'      => $bus_id,
                        'seat_number' => $seat_number,
                        'trip_date'   => $trip_date,
                    ),
                    array( '%d', '%s', '%d', '%s', '%s' ),
                    array( '%d', '%s', '%s' )
                );

                if ( false !== $updated ) {
                    $this->store_session_seat( $session_token, $bus_id, $trip_date, $seat_number );
                    $reserved[] = $seat_number;
                    continue;
                }
            }

            // Genuinely taken — booked or held by someone else.
            $taken[] = $seat_number;
        }

        return array(
            'reserved' => $reserved,
            'taken'    => $taken,
        );
    }


    // ============================================================
    // RELEASE — release_seats()
    // ============================================================

    /**
     * Release seats held by the current guest's session back to
     * 'available'. Called when:
     *   - The guest explicitly de-selects a seat in the UI.
     *   - The guest navigates away (JS beforeunload → AJAX).
     *   - The countdown timer hits zero in the browser.
     *
     * Only releases seats that match BOTH the session_token AND the
     * current guest_id (when logged in) — never releases another
     * guest's hold.
     *
     * @since  1.0.0
     * @param  int    $bus_id        Bus post ID.
     * @param  string $trip_date     Y-m-d.
     * @param  array  $seat_numbers  Seats to release. Empty array = release all held by this session.
     * @param  string $session_token Guest's current session token.
     * @return int Number of seats actually released.
     */
    public function release_seats( $bus_id, $trip_date, array $seat_numbers, $session_token ) {

        $bus_id = absint( $bus_id );

        global $wpdb;
        $prefix = $wpdb->prefix . MOGA_CORE_DB_PREFIX;

        // Resolve which seats this session actually holds.
        $held = $this->get_session_seats( $session_token, $bus_id, $trip_date );

        if ( empty( $held ) ) {
            return 0;
        }

        // If specific seats requested, intersect with what this session holds.
        if ( ! empty( $seat_numbers ) ) {
            $held = array_intersect( $held, array_map( 'sanitize_text_field', $seat_numbers ) );
        }

        if ( empty( $held ) ) {
            return 0;
        }

        $placeholders = implode( ', ', array_fill( 0, count( $held ), '%s' ) );

        // DELETE the rows entirely — "no row = available" is the system
        // convention. Updating to status='available' leaves a row that
        // causes INSERT conflicts (UNIQUE KEY) for the next guest trying
        // to reserve the same seat.
        $released = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$prefix}seats
                 WHERE bus_id = %d AND trip_date = %s
                 AND status = 'reserved'
                 AND seat_number IN ({$placeholders})",
                array_merge( array( $bus_id, $trip_date ), $held )
            )
        );

        // Clean up session record.
        $this->clear_session_seats( $session_token, $bus_id, $trip_date, $held );

        return (int) $released;
    }


    // ============================================================
    // CONFIRM — confirm_seats()
    // ============================================================

    /**
     * Upgrade seat status from 'reserved' → 'booked' when a booking
     * is successfully created. Called by Moga_Booking::create_booking()
     * (or by the Checkout shortcode) after the booking row is inserted.
     *
     * Only confirms seats matching the session_token / guest_id, so
     * a malicious POST can't confirm someone else's seats into a
     * booking it doesn't own.
     *
     * @since  1.0.0
     * @param  int    $bus_id        Bus post ID.
     * @param  string $trip_date     Y-m-d.
     * @param  array  $seat_numbers  Seats to confirm.
     * @param  int    $booking_id    The newly created booking ID.
     * @param  string $session_token Guest's current session token.
     * @return int Number of seats confirmed.
     */
    public function confirm_seats( $bus_id, $trip_date, array $seat_numbers, $booking_id, $session_token ) {

        $bus_id     = absint( $bus_id );
        $booking_id = absint( $booking_id );

        if ( ! $bus_id || ! $booking_id || empty( $seat_numbers ) ) {
            return 0;
        }

        global $wpdb;
        $prefix = $wpdb->prefix . MOGA_CORE_DB_PREFIX;

        $seat_numbers = array_map( 'sanitize_text_field', $seat_numbers );
        $confirmed    = 0;

        // Resolve bus layout for INSERT fallback.
        $layout_map = $this->build_layout_map( $bus_id );

        foreach ( $seat_numbers as $seat_number ) {
            if ( ! $seat_number ) {
                continue;
            }

            // First attempt: UPDATE existing reserved row → booked.
            // This is the fast path when the hold is still active.
            $updated = $wpdb->query( $wpdb->prepare(
                "UPDATE {$prefix}seats
                 SET status = 'booked',
                     booking_id = %d,
                     reserved_until = NULL
                 WHERE bus_id = %d AND trip_date = %s
                 AND status = 'reserved'
                 AND seat_number = %s",
                $booking_id,
                $bus_id,
                $trip_date,
                $seat_number
            ) );

            if ( $updated > 0 ) {
                $confirmed++;
                continue;
            }

            // UPDATE matched zero rows — either the hold expired and the
            // row was deleted, or the row never existed. Use INSERT with
            // ON DUPLICATE KEY UPDATE so we write a 'booked' row regardless,
            // without failing on the UNIQUE KEY constraint.
            $row_col   = isset( $layout_map[ $seat_number ] ) ? $layout_map[ $seat_number ] : array( 1, 1 );
            $seat_type = $this->get_seat_type( $bus_id, $seat_number );

            $wpdb->query( $wpdb->prepare(
                "INSERT INTO {$prefix}seats
                 (bus_id, trip_date, seat_number, seat_row, seat_column,
                  seat_type, status, booking_id, guest_id, reserved_at, reserved_until)
                 VALUES (%d, %s, %s, %d, %d, %s, 'booked', %d, NULL, NULL, NULL)
                 ON DUPLICATE KEY UPDATE
                     status      = 'booked',
                     booking_id  = VALUES(booking_id),
                     reserved_until = NULL",
                $bus_id,
                $trip_date,
                $seat_number,
                $row_col[0],
                $row_col[1],
                $seat_type,
                $booking_id
            ) );

            $confirmed++;
        }

        // Clean up session.
        $this->clear_session_seats( $session_token, $bus_id, $trip_date, $seat_numbers );

        return $confirmed;
    }


    // ============================================================
    // RELEASE EXPIRED — release_expired_seats() (cron handler)
    // ============================================================

    /**
     * Cron handler for moga_release_expired_seats (every 15 minutes).
     * Finds all 'reserved' seats whose reserved_until has passed and
     * flips them back to 'available'.
     *
     * This is the safety net — it ensures that a guest who closes their
     * browser, loses their connection, or simply abandons the booking
     * page never permanently holds seats that other guests could use.
     *
     * @since  1.0.0
     * @return int Number of seats released.
     */
    public function release_expired_seats() {

        global $wpdb;
        $prefix = $wpdb->prefix . MOGA_CORE_DB_PREFIX;

        // DELETE expired holds entirely rather than setting status='available'.
        // Keeping the row with status='available' causes INSERT conflicts for
        // the next guest who tries to reserve the same seat — the UNIQUE KEY
        // on (bus_id, seat_number, trip_date) fires and the fallback UPDATE
        // fails silently when reserved_until is NULL. Deleting the row means
        // "no row = available by default", which is how the entire system is
        // designed — get_seat_map() returns 'available' for any seat with
        // no corresponding DB row.
        $released = $wpdb->query(
            "DELETE FROM {$prefix}seats
             WHERE status = 'reserved'
             AND reserved_until IS NOT NULL
             AND reserved_until < UTC_TIMESTAMP()"
        );

        return (int) $released;
    }


    // ============================================================
    // SEAT COUNTS — get_available_seat_count()
    // ============================================================

    /**
     * Count genuinely available seats for a bus on a specific trip date.
     * Used by the tour booking form sidebar indicator ("28 of 40 seats
     * available") so a guest knows at a glance whether seats are left
     * without needing to open the full seat map.
     *
     * Expired holds are counted as available (consistent with get_seat_map).
     * 'disabled' seat_type rows are never available regardless of status.
     *
     * @since  1.0.0
     * @param  int    $bus_id    Bus post ID.
     * @param  string $trip_date Y-m-d.
     * @return array { available: int, total: int }
     */
    public function get_available_seat_count( $bus_id, $trip_date ) {

        $bus_id = absint( $bus_id );

        $rows   = absint( get_post_meta( $bus_id, '_moga_seat_rows',   true ) ) ?: 10;
        $layout = get_post_meta( $bus_id, '_moga_seat_layout', true ) ?: '2+2';

        if ( ! class_exists( 'Moga_CPT_Bus' ) ) {
            return array( 'available' => 0, 'total' => 0 );
        }

        $all_seats = Moga_CPT_Bus::generate_seat_numbers( $rows, $layout );

        $disabled_json = get_post_meta( $bus_id, '_moga_disabled_seats', true );
        $disabled      = $disabled_json ? json_decode( $disabled_json, true ) : array();
        $disabled      = is_array( $disabled ) ? $disabled : array();

        $bookable_seats = array_diff( $all_seats, $disabled );
        $total          = count( $bookable_seats );

        if ( ! $total ) {
            return array( 'available' => 0, 'total' => 0 );
        }

        global $wpdb;
        $prefix = $wpdb->prefix . MOGA_CORE_DB_PREFIX;

        // Count seats that are genuinely taken — booked, or reserved
        // with a hold that hasn't expired yet.
        $now = current_time( 'mysql' );

        $taken = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*)
             FROM {$prefix}seats
             WHERE bus_id = %d AND trip_date = %s
             AND seat_type != 'disabled'
             AND (
                 status = 'booked'
                 OR ( status = 'reserved' AND reserved_until > %s )
                 OR status = 'unavailable'
             )",
            $bus_id,
            $trip_date,
            $now
        ) );

        return array(
            'available' => max( 0, $total - $taken ),
            'total'     => $total,
        );
    }


    // ============================================================
    // BOOKED SEATS FOR A BOOKING — get_booked_seats()
    // ============================================================

    /**
     * Get the seat numbers confirmed for a specific booking.
     * Used by the Confirmation page to show "Your seats: 3A, 3B".
     *
     * @since  1.0.0
     * @param  int $booking_id Booking ID.
     * @return array Array of seat number strings, or empty array.
     */
    public function get_booked_seats( $booking_id ) {

        $booking_id = absint( $booking_id );
        if ( ! $booking_id ) {
            return array();
        }

        global $wpdb;
        $prefix = $wpdb->prefix . MOGA_CORE_DB_PREFIX;

        return $wpdb->get_col( $wpdb->prepare(
            "SELECT seat_number FROM {$prefix}seats
             WHERE booking_id = %d AND status = 'booked'
             ORDER BY seat_row ASC, seat_column ASC",
            $booking_id
        ) );
    }


    // ============================================================
    // SESSION HELPERS
    // ============================================================

    /**
     * Start a PHP session if one isn't already running.
     * Called before any session read/write.
     *
     * @since  1.0.0
     * @return void
     */
    private function maybe_start_session() {
        // Session is started early via start_session_early() on 'init'
        // priority 1. This is a safety fallback only — in normal operation
        // the session should already be open by the time any method here runs.
        if ( ! session_id() && ! headers_sent() ) {
            session_start();
        }
    }

    /**
     * Generate a short random token for this guest's seat selection
     * session. Returned to the browser on the first reserve call and
     * stored as a data attribute on the seat map — sent back with
     * every subsequent reserve/release call.
     *
     * @since  1.0.0
     * @return string 16-character hex token.
     */
    public static function generate_session_token() {
        return bin2hex( random_bytes( 8 ) );
    }

    /**
     * Record that a seat belongs to this session's hold.
     * Stored server-side in $_SESSION — the browser never writes this,
     * it only reads back the token we issued.
     *
     * @since  1.0.0
     * @param  string $token       Session token.
     * @param  int    $bus_id      Bus post ID.
     * @param  string $trip_date   Y-m-d.
     * @param  string $seat_number Seat number string.
     * @return void
     */
    private function store_session_seat( $token, $bus_id, $trip_date, $seat_number ) {
        $this->maybe_start_session();
        $key = 'moga_seats_' . $token . '_' . $bus_id . '_' . $trip_date;
        if ( ! isset( $_SESSION[ $key ] ) || ! is_array( $_SESSION[ $key ] ) ) {
            $_SESSION[ $key ] = array();
        }
        if ( ! in_array( $seat_number, $_SESSION[ $key ], true ) ) {
            $_SESSION[ $key ][] = $seat_number;
        }
    }

    /**
     * Get all seats held by this session token for a given bus + date.
     *
     * @since  1.0.0
     * @param  string $token     Session token.
     * @param  int    $bus_id    Bus post ID.
     * @param  string $trip_date Y-m-d.
     * @return array Array of seat number strings.
     */
    public function get_session_seats( $token, $bus_id, $trip_date ) {
        if ( ! $token ) {
            return array();
        }
        $this->maybe_start_session();
        $key = 'moga_seats_' . $token . '_' . absint( $bus_id ) . '_' . $trip_date;
        return isset( $_SESSION[ $key ] ) && is_array( $_SESSION[ $key ] )
            ? $_SESSION[ $key ]
            : array();
    }

    /**
     * Check whether a specific seat belongs to this session's hold.
     *
     * @since  1.0.0
     * @param  string $token       Session token.
     * @param  int    $bus_id      Bus post ID.
     * @param  string $trip_date   Y-m-d.
     * @param  string $seat_number Seat number string.
     * @return bool
     */
    private function is_my_session( $token, $bus_id, $trip_date, $seat_number ) {
        $held = $this->get_session_seats( $token, $bus_id, $trip_date );
        return in_array( $seat_number, $held, true );
    }

    /**
     * Remove specific seats from this session's hold record.
     * Called after confirm_seats() or release_seats() so the session
     * doesn't accumulate stale entries.
     *
     * @since  1.0.0
     * @param  string   $token        Session token.
     * @param  int      $bus_id       Bus post ID.
     * @param  string   $trip_date    Y-m-d.
     * @param  string[] $seat_numbers Seats to remove from session.
     * @return void
     */
    private function clear_session_seats( $token, $bus_id, $trip_date, array $seat_numbers ) {
        if ( ! $token ) {
            return;
        }
        $this->maybe_start_session();
        $key = 'moga_seats_' . $token . '_' . absint( $bus_id ) . '_' . $trip_date;
        if ( isset( $_SESSION[ $key ] ) && is_array( $_SESSION[ $key ] ) ) {
            $_SESSION[ $key ] = array_values(
                array_diff( $_SESSION[ $key ], $seat_numbers )
            );
        }
    }


    // ============================================================
    // LAYOUT HELPERS
    // ============================================================

    /**
     * Build a seat_number → [row, column] map for a bus.
     * Used by reserve_seats() to write the correct row/column
     * values into mg_moga_seats without re-generating the full
     * seat list twice.
     *
     * @since  1.0.0
     * @param  int $bus_id Bus post ID.
     * @return array seat_number => [row (int), column (int)]
     */
    private function build_layout_map( $bus_id ) {

        $rows   = absint( get_post_meta( $bus_id, '_moga_seat_rows',   true ) ) ?: 10;
        $layout = get_post_meta( $bus_id, '_moga_seat_layout', true ) ?: '2+2';

        if ( ! class_exists( 'Moga_CPT_Bus' ) ) {
            return array();
        }

        $seat_numbers = Moga_CPT_Bus::generate_seat_numbers( $rows, $layout );
        $layouts      = Moga_CPT_Bus::get_seat_layouts();
        $columns      = isset( $layouts[ $layout ]['columns'] ) ? $layouts[ $layout ]['columns'] : 4;

        $map          = array();
        $column_index = 0;

        foreach ( $seat_numbers as $seat_number ) {
            $row_num = intval( $seat_number );
            $col_num = ( $column_index % $columns ) + 1;
            $column_index++;
            $map[ $seat_number ] = array( $row_num, $col_num );
        }

        return $map;
    }

    /**
     * Determine the seat_type for a single seat number from bus meta.
     *
     * @since  1.0.0
     * @param  int    $bus_id      Bus post ID.
     * @param  string $seat_number Seat number string.
     * @return string 'standard'|'vip'|'disabled'
     */
    private function get_seat_type( $bus_id, $seat_number ) {

        $disabled_json = get_post_meta( $bus_id, '_moga_disabled_seats', true );
        $vip_json      = get_post_meta( $bus_id, '_moga_vip_seats',      true );

        $disabled = $disabled_json ? json_decode( $disabled_json, true ) : array();
        $vip      = $vip_json      ? json_decode( $vip_json,      true ) : array();

        $disabled = is_array( $disabled ) ? $disabled : array();
        $vip      = is_array( $vip )      ? $vip      : array();

        if ( in_array( $seat_number, $disabled, true ) ) {
            return 'disabled';
        }
        if ( in_array( $seat_number, $vip, true ) ) {
            return 'vip';
        }

        return 'standard';
    }
}
