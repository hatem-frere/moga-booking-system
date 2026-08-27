<?php

/**
 * Availability Management Class
 *
 * Single source of truth for the mg_moga_availability calendar —
 * property and tour date-range availability only. Bus seat
 * availability (mg_moga_seats) is a deliberately separate concern,
 * handled by class-moga-seat-map.php (not yet built).
 *
 * REFACTOR NOTE: block_dates()/release_dates() used to live as
 * private methods inside class-moga-booking.php, duplicating logic
 * that belongs here. Moga_Booking now calls into this class instead
 * of owning its own copy, so the date-blocking logic exists in
 * exactly one place.
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
 * Class Moga_Availability
 */
class Moga_Availability
{

    // ============================================================
    // BLOCK / RELEASE (moved from Moga_Booking)
    // ============================================================

    /**
     * Block a date range for a listing, linked to a booking.
     * Relies on the UNIQUE KEY idx_listing_date (listing_id, date)
     * so this correctly upserts rather than duplicating rows.
     *
     * @since  1.0.0
     * @param  int      $listing_id   Property or tour post ID.
     * @param  string   $listing_type 'property'|'tour'.
     * @param  string   $check_in     Y-m-d.
     * @param  string   $check_out    Y-m-d (exclusive, matches moga_date_range()).
     * @param  int|null $booking_id   Booking ID to link.
     * @return void
     */
    public function block_dates($listing_id, $listing_type, $check_in, $check_out, $booking_id = null)
    {
        global $wpdb;
        $prefix = $wpdb->prefix . MOGA_CORE_DB_PREFIX;

        foreach (moga_date_range($check_in, $check_out) as $date) {
            $wpdb->replace(
                "{$prefix}availability",
                array(
                    'listing_id'   => $listing_id,
                    'listing_type' => $listing_type,
                    'date'         => $date,
                    'status'       => 'booked',
                    'booking_id'   => $booking_id,
                ),
                array('%d', '%s', '%s', '%s', '%d')
            );
        }
    }

    /**
     * Release a previously blocked date range back to 'available'.
     * If $booking_id is given, only releases rows still linked to
     * that specific booking — so it can't accidentally free dates
     * another booking now holds. If $booking_id is null, releases
     * regardless of link (used for manual admin release scenarios).
     *
     * @since  1.0.0
     * @param  int      $listing_id   Property or tour post ID.
     * @param  string   $listing_type 'property'|'tour'.
     * @param  string   $check_in     Y-m-d.
     * @param  string   $check_out    Y-m-d.
     * @param  int|null $booking_id   Booking ID that held these dates, or null.
     * @return void
     */
    public function release_dates($listing_id, $listing_type, $check_in, $check_out, $booking_id = null)
    {
        global $wpdb;
        $prefix = $wpdb->prefix . MOGA_CORE_DB_PREFIX;

        if (null !== $booking_id) {
            $wpdb->query($wpdb->prepare(
                "UPDATE {$prefix}availability
                 SET status = 'available', booking_id = NULL
                 WHERE listing_id = %d AND listing_type = %s
                 AND date >= %s AND date < %s AND booking_id = %d",
                $listing_id,
                $listing_type,
                $check_in,
                $check_out,
                $booking_id
            ));
            return;
        }

        $wpdb->query($wpdb->prepare(
            "UPDATE {$prefix}availability
             SET status = 'available', booking_id = NULL
             WHERE listing_id = %d AND listing_type = %s
             AND date >= %s AND date < %s",
            $listing_id,
            $listing_type,
            $check_in,
            $check_out
        ));
    }


    // ============================================================
    // FRONTEND CALENDAR SUPPORT
    // ============================================================

    /**
     * Get a flat array of Y-m-d dates that are NOT available
     * (booked, blocked, or pending) for a listing — exactly the
     * shape a Flatpickr `disable:` array needs.
     *
     * @since  1.0.0
     * @param  int         $listing_id   Property or tour post ID.
     * @param  string      $listing_type 'property'|'tour'.
     * @param  string|null $from_date    Y-m-d. Defaults to today.
     * @param  string|null $to_date      Y-m-d. Defaults to one year out.
     * @return array Array of Y-m-d strings.
     */
    public function get_blocked_dates($listing_id, $listing_type, $from_date = null, $to_date = null)
    {
        global $wpdb;
        $prefix = $wpdb->prefix . MOGA_CORE_DB_PREFIX;

        $from_date = $from_date ?: moga_today();
        $to_date   = $to_date ?: gmdate('Y-m-d', strtotime('+1 year'));

        return $wpdb->get_col($wpdb->prepare(
            "SELECT date FROM {$prefix}availability
             WHERE listing_id = %d AND listing_type = %s
             AND status IN ('booked', 'blocked', 'pending')
             AND date >= %s AND date <= %s
             ORDER BY date ASC",
            $listing_id,
            $listing_type,
            $from_date,
            $to_date
        ));
    }


    // ============================================================
    // OWNER-INITIATED MANUAL BLOCKS
    // ============================================================

    /**
     * Manually block a date range for a listing — owner-initiated
     * closure (maintenance, personal use), not tied to any booking.
     *
     * @since  1.0.0
     * @param  int    $listing_id   Property or tour post ID.
     * @param  string $listing_type 'property'|'tour'.
     * @param  string $date_from    Y-m-d.
     * @param  string $date_to      Y-m-d (exclusive).
     * @param  string $note         Optional reason, stored on each row.
     * @return void
     */
    public function manual_block($listing_id, $listing_type, $date_from, $date_to, $note = '')
    {
        global $wpdb;
        $prefix = $wpdb->prefix . MOGA_CORE_DB_PREFIX;

        foreach (moga_date_range($date_from, $date_to) as $date) {
            $wpdb->replace(
                "{$prefix}availability",
                array(
                    'listing_id'   => $listing_id,
                    'listing_type' => $listing_type,
                    'date'         => $date,
                    'status'       => 'blocked',
                    'booking_id'   => null,
                    'note'         => $note ? sanitize_text_field($note) : null,
                ),
                array('%d', '%s', '%s', '%s', '%d', '%s')
            );
        }
    }

    /**
     * Manually unblock a date range. Only touches rows that are
     * currently 'blocked' AND have no booking_id, so it can never
     * accidentally unblock a date a real guest booking still holds.
     *
     * @since  1.0.0
     * @param  int    $listing_id   Property or tour post ID.
     * @param  string $listing_type 'property'|'tour'.
     * @param  string $date_from    Y-m-d.
     * @param  string $date_to      Y-m-d (exclusive).
     * @return void
     */
    public function manual_unblock($listing_id, $listing_type, $date_from, $date_to)
    {
        global $wpdb;
        $prefix = $wpdb->prefix . MOGA_CORE_DB_PREFIX;

        $wpdb->query($wpdb->prepare(
            "UPDATE {$prefix}availability
             SET status = 'available', note = NULL
             WHERE listing_id = %d AND listing_type = %s
             AND date >= %s AND date < %s
             AND status = 'blocked' AND booking_id IS NULL",
            $listing_id,
            $listing_type,
            $date_from,
            $date_to
        ));
    }


    // ============================================================
    // PER-DATE PRICE OVERRIDE
    // ============================================================

    /**
     * Set a price override for a specific date. Creates the
     * availability row (status 'available') if none exists yet for
     * that date, or updates just the price_override column if one
     * already does — never touches status/booking_id of an existing
     * row.
     *
     * @since  1.0.0
     * @param  int    $listing_id   Property or tour post ID.
     * @param  string $listing_type 'property'|'tour'.
     * @param  string $date         Y-m-d.
     * @param  float  $price        Override price for this date.
     * @return void
     */
    public function set_price_override($listing_id, $listing_type, $date, $price)
    {
        global $wpdb;
        $prefix = $wpdb->prefix . MOGA_CORE_DB_PREFIX;

        $existing_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$prefix}availability WHERE listing_id = %d AND listing_type = %s AND date = %s",
            $listing_id,
            $listing_type,
            $date
        ));

        if ($existing_id) {
            $wpdb->update(
                "{$prefix}availability",
                array('price_override' => (float) $price),
                array('id' => $existing_id),
                array('%f'),
                array('%d')
            );
            return;
        }

        $wpdb->insert(
            "{$prefix}availability",
            array(
                'listing_id'     => $listing_id,
                'listing_type'   => $listing_type,
                'date'           => $date,
                'status'         => 'available',
                'price_override' => (float) $price,
            ),
            array('%d', '%s', '%s', '%s', '%f')
        );
    }

    /**
     * Get the price override for a specific date, if any.
     *
     * @since  1.0.0
     * @param  int    $listing_id   Property or tour post ID.
     * @param  string $listing_type 'property'|'tour'.
     * @param  string $date         Y-m-d.
     * @return float|null Override price, or null if none set.
     */
    public function get_price_override($listing_id, $listing_type, $date)
    {
        global $wpdb;
        $prefix = $wpdb->prefix . MOGA_CORE_DB_PREFIX;

        $value = $wpdb->get_var($wpdb->prepare(
            "SELECT price_override FROM {$prefix}availability WHERE listing_id = %d AND listing_type = %s AND date = %s",
            $listing_id,
            $listing_type,
            $date
        ));

        return null !== $value ? (float) $value : null;
    }


    // ============================================================
    // PER-DATE MINIMUM STAY
    // ============================================================

    /**
     * Set a minimum-stay override for a specific date. Same
     * create-or-update behaviour as set_price_override().
     *
     * @since  1.0.0
     * @param  int    $listing_id   Property post ID (min_stay is property-only).
     * @param  string $listing_type 'property'|'tour'.
     * @param  string $date         Y-m-d.
     * @param  int    $min_stay     Minimum nights required if check-in falls on this date.
     * @return void
     */
    public function set_min_stay($listing_id, $listing_type, $date, $min_stay)
    {
        global $wpdb;
        $prefix = $wpdb->prefix . MOGA_CORE_DB_PREFIX;

        $existing_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$prefix}availability WHERE listing_id = %d AND listing_type = %s AND date = %s",
            $listing_id,
            $listing_type,
            $date
        ));

        if ($existing_id) {
            $wpdb->update(
                "{$prefix}availability",
                array('min_stay' => absint($min_stay)),
                array('id' => $existing_id),
                array('%d'),
                array('%d')
            );
            return;
        }

        $wpdb->insert(
            "{$prefix}availability",
            array(
                'listing_id'   => $listing_id,
                'listing_type' => $listing_type,
                'date'         => $date,
                'status'       => 'available',
                'min_stay'     => absint($min_stay),
            ),
            array('%d', '%s', '%s', '%s', '%d')
        );
    }

    /**
     * Set a maximum-stay override for a specific date. Mirrors
     * set_min_stay() exactly.
     *
     * @since  1.0.0
     * @param  int    $listing_id   Property post ID.
     * @param  string $listing_type 'property'|'tour'.
     * @param  string $date         Y-m-d.
     * @param  int    $max_stay     Maximum nights allowed if check-in falls on this date.
     * @return void
     */
    public function set_max_stay($listing_id, $listing_type, $date, $max_stay)
    {
        global $wpdb;
        $prefix = $wpdb->prefix . MOGA_CORE_DB_PREFIX;

        $existing_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$prefix}availability WHERE listing_id = %d AND listing_type = %s AND date = %s",
            $listing_id,
            $listing_type,
            $date
        ));

        if ($existing_id) {
            $wpdb->update(
                "{$prefix}availability",
                array('max_stay' => absint($max_stay)),
                array('id' => $existing_id),
                array('%d'),
                array('%d')
            );
            return;
        }

        $wpdb->insert(
            "{$prefix}availability",
            array(
                'listing_id'   => $listing_id,
                'listing_type' => $listing_type,
                'date'         => $date,
                'status'       => 'available',
                'max_stay'     => absint($max_stay),
            ),
            array('%d', '%s', '%s', '%s', '%d')
        );
    }


    // ============================================================
    // PROPERTY PERIODS (bulk "Add Another Period" apply/clear)
    // ============================================================

    /**
     * Apply a Property Period across a whole date range in one
     * action — the actual "Add Another Period" feature. Writes
     * price_override, min_stay, and max_stay onto every date in the
     * range with a single upsert per date.
     *
     * WEEKEND LAYERING, RESOLVED HERE, ONCE: if a date in the range
     * is one of THIS PERIOD's own weekend days (passed in via
     * $weekend_days — weekend days are per-period now, not a single
     * property-wide setting) AND this period defines its own weekend
     * price, that weekend price is written for that date; otherwise
     * the period's base price is written. This is deliberately
     * resolved once, at apply (save) time, rather than as runtime
     * precedence logic — moga_calculate_property_price() never needs
     * to know "periods" exist at all; it already just reads whatever
     * ends up in price_override, exactly as it does today for any
     * other override.
     *
     * @since  1.0.0
     * @param  int         $listing_id    Property post ID.
     * @param  string      $listing_type  'property' (tours don't use this method).
     * @param  string      $date_from     Y-m-d.
     * @param  string      $date_to       Y-m-d (exclusive, matches moga_date_range()).
     * @param  float       $price         Base price/night for this period.
     * @param  float|null  $weekend_price Optional weekend rate for this period.
     * @param  int|null    $min_stay      Optional minimum nights for this period.
     * @param  int|null    $max_stay      Optional maximum nights for this period.
     * @param  array       $weekend_days  This period's own weekend day numbers (0=Sun..6=Sat).
     * @return void
     */
    public function apply_period($listing_id, $listing_type, $date_from, $date_to, $price, $weekend_price = null, $min_stay = null, $max_stay = null, $weekend_days = array())
    {
        global $wpdb;
        $prefix = $wpdb->prefix . MOGA_CORE_DB_PREFIX;

        $weekend_days = is_array($weekend_days) ? array_map('intval', $weekend_days) : array();

        foreach (moga_date_range($date_from, $date_to) as $date) {
            $day_of_week = intval(gmdate('w', strtotime($date)));
            $is_weekend  = in_array($day_of_week, $weekend_days, true);
            $date_price  = ($is_weekend && $weekend_price > 0) ? $weekend_price : $price;

            $existing_id = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$prefix}availability WHERE listing_id = %d AND listing_type = %s AND date = %s",
                $listing_id,
                $listing_type,
                $date
            ));

            $row_data   = array('price_override' => (float) $date_price);
            $row_format = array('%f');

            if (null !== $min_stay) {
                $row_data['min_stay'] = absint($min_stay);
                $row_format[]         = '%d';
            }
            if (null !== $max_stay) {
                $row_data['max_stay'] = absint($max_stay);
                $row_format[]         = '%d';
            }

            if ($existing_id) {
                $wpdb->update("{$prefix}availability", $row_data, array('id' => $existing_id), $row_format, array('%d'));
                continue;
            }

            $row_data = array_merge(
                array(
                    'listing_id'   => $listing_id,
                    'listing_type' => $listing_type,
                    'date'         => $date,
                    'status'       => 'available',
                ),
                $row_data
            );
            $row_format = array_merge(array('%d', '%s', '%s', '%s'), $row_format);

            $wpdb->insert("{$prefix}availability", $row_data, $row_format);
        }
    }

    /**
     * Clear a Property Period — resets price_override, min_stay,
     * and max_stay back to NULL (plain property default) across the
     * given date range. Called when a period is deleted or its
     * range shrinks. Deliberately touches ONLY these three columns
     * — status and booking_id are never modified, so a real guest
     * booking on one of these dates is completely unaffected.
     *
     * @since  1.0.0
     * @param  int    $listing_id   Property post ID.
     * @param  string $listing_type 'property'.
     * @param  string $date_from    Y-m-d.
     * @param  string $date_to      Y-m-d (exclusive).
     * @return void
     */
    public function clear_period($listing_id, $listing_type, $date_from, $date_to)
    {
        global $wpdb;
        $prefix = $wpdb->prefix . MOGA_CORE_DB_PREFIX;

        $wpdb->query($wpdb->prepare(
            "UPDATE {$prefix}availability
             SET price_override = NULL, min_stay = NULL, max_stay = NULL
             WHERE listing_id = %d AND listing_type = %s
             AND date >= %s AND date < %s",
            $listing_id,
            $listing_type,
            $date_from,
            $date_to
        ));
    }


    // ============================================================
    // CALENDAR VIEW
    // ============================================================

    /**
     * Get a full month's availability data for a listing — the data
     * layer for a future owner dashboard calendar widget.
     *
     * @since  1.0.0
     * @param  int    $listing_id   Property or tour post ID.
     * @param  string $listing_type 'property'|'tour'.
     * @param  int    $month        1-12.
     * @param  int    $year         e.g. 2026.
     * @return array Y-m-d => associative row (status, price_override, min_stay, max_stay, booking_id).
     */
    public function get_calendar($listing_id, $listing_type, $month, $year)
    {
        global $wpdb;
        $prefix = $wpdb->prefix . MOGA_CORE_DB_PREFIX;

        $start = sprintf('%04d-%02d-01', $year, $month);
        $end   = gmdate('Y-m-d', strtotime($start . ' +1 month'));

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT date, status, price_override, min_stay, max_stay, booking_id
             FROM {$prefix}availability
             WHERE listing_id = %d AND listing_type = %s
             AND date >= %s AND date < %s
             ORDER BY date ASC",
            $listing_id,
            $listing_type,
            $start,
            $end
        ), ARRAY_A);

        $calendar = array();
        foreach ($rows as $row) {
            $calendar[$row['date']] = $row;
        }

        return $calendar;
    }
}
