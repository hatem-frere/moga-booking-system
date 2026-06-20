<?php
/**
 * Date Helper Functions
 *
 * Date formatting, calculation, and availability helpers
 * used throughout the Moga Booking System.
 *
 * @package    MogaTravelCore
 * @subpackage MogaTravelCore/includes/helpers
 * @author     Hatem Frere
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


// ============================================================
// DATE FORMATTING
// ============================================================

/**
 * Format a date string using the plugin date format setting.
 *
 * @since  1.0.0
 * @param  string $date   Date string (Y-m-d or any strtotime-compatible).
 * @param  string $format Optional override format. Uses plugin setting if empty.
 * @return string         Formatted date string.
 */
function moga_format_date( $date, $format = '' ) {
    if ( empty( $date ) ) {
        return '';
    }

    if ( empty( $format ) ) {
        $format = get_option( 'moga_date_format', 'Y-m-d' );
    }

    $timestamp = is_numeric( $date ) ? $date : strtotime( $date );

    if ( ! $timestamp ) {
        return '';
    }

    return gmdate( $format, $timestamp );
}

/**
 * Format a date for human-readable display.
 *
 * Example: 2026-06-19 → "June 19, 2026"
 *
 * @since  1.0.0
 * @param  string $date Date string (Y-m-d).
 * @return string       Human-readable date.
 */
function moga_format_date_human( $date ) {
    return moga_format_date( $date, 'F j, Y' );
}

/**
 * Format a date range for display.
 *
 * Example: "June 19 – June 23, 2026"
 *
 * @since  1.0.0
 * @param  string $check_in  Check-in date (Y-m-d).
 * @param  string $check_out Check-out date (Y-m-d).
 * @return string            Formatted date range.
 */
function moga_format_date_range( $check_in, $check_out ) {
    if ( empty( $check_in ) || empty( $check_out ) ) {
        return '';
    }

    $in_year   = gmdate( 'Y', strtotime( $check_in ) );
    $out_year  = gmdate( 'Y', strtotime( $check_out ) );
    $in_month  = gmdate( 'n', strtotime( $check_in ) );
    $out_month = gmdate( 'n', strtotime( $check_out ) );

    // Same year and month: "June 19 – 23, 2026"
    if ( $in_year === $out_year && $in_month === $out_month ) {
        return moga_format_date( $check_in, 'F j' )
            . ' – '
            . moga_format_date( $check_out, 'j, Y' );
    }

    // Same year, different month: "June 19 – July 2, 2026"
    if ( $in_year === $out_year ) {
        return moga_format_date( $check_in, 'F j' )
            . ' – '
            . moga_format_date( $check_out, 'F j, Y' );
    }

    // Different years: "Dec 30, 2025 – Jan 3, 2026"
    return moga_format_date( $check_in, 'M j, Y' )
        . ' – '
        . moga_format_date( $check_out, 'M j, Y' );
}

/**
 * Format a datetime string for display.
 *
 * @since  1.0.0
 * @param  string $datetime Datetime string.
 * @return string           Formatted datetime.
 */
function moga_format_datetime( $datetime ) {
    if ( empty( $datetime ) ) {
        return '';
    }

    $date_format = get_option( 'moga_date_format', 'Y-m-d' );
    $time_format = get_option( 'moga_time_format', 'H:i' );

    return moga_format_date( $datetime, $date_format . ' ' . $time_format );
}

/**
 * Get a time ago string for a datetime.
 *
 * Example: "3 hours ago", "2 days ago"
 *
 * @since  1.0.0
 * @param  string $datetime Datetime string.
 * @return string           Time ago string.
 */
function moga_time_ago( $datetime ) {
    if ( empty( $datetime ) ) {
        return '';
    }

    $timestamp = strtotime( $datetime );
    $now       = current_time( 'timestamp' ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
    $diff      = $now - $timestamp;

    if ( $diff < 60 ) {
        return __( 'Just now', 'moga-travel-core' );
    } elseif ( $diff < 3600 ) {
        $minutes = floor( $diff / 60 );
        return sprintf(
            /* translators: %d: number of minutes */
            _n( '%d minute ago', '%d minutes ago', $minutes, 'moga-travel-core' ),
            $minutes
        );
    } elseif ( $diff < 86400 ) {
        $hours = floor( $diff / 3600 );
        return sprintf(
            /* translators: %d: number of hours */
            _n( '%d hour ago', '%d hours ago', $hours, 'moga-travel-core' ),
            $hours
        );
    } elseif ( $diff < 604800 ) {
        $days = floor( $diff / 86400 );
        return sprintf(
            /* translators: %d: number of days */
            _n( '%d day ago', '%d days ago', $days, 'moga-travel-core' ),
            $days
        );
    } elseif ( $diff < 2592000 ) {
        $weeks = floor( $diff / 604800 );
        return sprintf(
            /* translators: %d: number of weeks */
            _n( '%d week ago', '%d weeks ago', $weeks, 'moga-travel-core' ),
            $weeks
        );
    }

    return moga_format_date_human( $datetime );
}


// ============================================================
// NIGHT / DURATION CALCULATION
// ============================================================

/**
 * Calculate number of nights between two dates.
 *
 * @since  1.0.0
 * @param  string $check_in  Check-in date (Y-m-d).
 * @param  string $check_out Check-out date (Y-m-d).
 * @return int               Number of nights. 0 if invalid.
 */
function moga_calculate_nights( $check_in, $check_out ) {
    if ( empty( $check_in ) || empty( $check_out ) ) {
        return 0;
    }

    $in  = new DateTime( $check_in );
    $out = new DateTime( $check_out );

    if ( $out <= $in ) {
        return 0;
    }

    return (int) $in->diff( $out )->days;
}

/**
 * Get a human-readable nights label.
 *
 * Example: "3 nights"
 *
 * @since  1.0.0
 * @param  int $nights Number of nights.
 * @return string
 */
function moga_nights_label( $nights ) {
    return sprintf(
        /* translators: %d: number of nights */
        _n( '%d night', '%d nights', intval( $nights ), 'moga-travel-core' ),
        intval( $nights )
    );
}

/**
 * Get a human-readable days label.
 *
 * Example: "5 days"
 *
 * @since  1.0.0
 * @param  int $days Number of days.
 * @return string
 */
function moga_days_label( $days ) {
    return sprintf(
        /* translators: %d: number of days */
        _n( '%d day', '%d days', intval( $days ), 'moga-travel-core' ),
        intval( $days )
    );
}

/**
 * Get tour duration label.
 *
 * Example: "3 days / 2 nights"
 *
 * @since  1.0.0
 * @param  int $days   Number of days.
 * @param  int $nights Number of nights.
 * @return string
 */
function moga_duration_label( $days, $nights ) {
    $days   = intval( $days );
    $nights = intval( $nights );

    if ( $days > 0 && $nights > 0 ) {
        return moga_days_label( $days ) . ' / ' . moga_nights_label( $nights );
    } elseif ( $days > 0 ) {
        return moga_days_label( $days );
    } elseif ( $nights > 0 ) {
        return moga_nights_label( $nights );
    }

    return __( 'Day trip', 'moga-travel-core' );
}


// ============================================================
// DATE VALIDATION
// ============================================================

/**
 * Check if a date string is valid.
 *
 * @since  1.0.0
 * @param  string $date   Date string.
 * @param  string $format Expected format (default Y-m-d).
 * @return bool
 */
function moga_is_valid_date( $date, $format = 'Y-m-d' ) {
    if ( empty( $date ) ) {
        return false;
    }

    $d = DateTime::createFromFormat( $format, $date );

    return $d && $d->format( $format ) === $date;
}

/**
 * Check if check-in and check-out dates are valid for booking.
 *
 * @since  1.0.0
 * @param  string $check_in  Check-in date (Y-m-d).
 * @param  string $check_out Check-out date (Y-m-d).
 * @return true|WP_Error     True if valid, WP_Error if not.
 */
function moga_validate_dates( $check_in, $check_out ) {

    if ( ! moga_is_valid_date( $check_in ) ) {
        return new WP_Error(
            'invalid_checkin',
            __( 'Invalid check-in date.', 'moga-travel-core' )
        );
    }

    if ( ! moga_is_valid_date( $check_out ) ) {
        return new WP_Error(
            'invalid_checkout',
            __( 'Invalid check-out date.', 'moga-travel-core' )
        );
    }

    $today = new DateTime( 'today' );
    $in    = new DateTime( $check_in );
    $out   = new DateTime( $check_out );

    if ( $in < $today ) {
        return new WP_Error(
            'past_checkin',
            __( 'Check-in date cannot be in the past.', 'moga-travel-core' )
        );
    }

    if ( $out <= $in ) {
        return new WP_Error(
            'invalid_range',
            __( 'Check-out date must be after check-in date.', 'moga-travel-core' )
        );
    }

    $min_notice = intval( get_option( 'moga_min_booking_notice', 1 ) );
    $earliest   = new DateTime( '+' . $min_notice . ' days' );

    if ( $in < $earliest ) {
        return new WP_Error(
            'too_soon',
            sprintf(
                /* translators: %d: number of days */
                _n(
                    'Bookings must be made at least %d day in advance.',
                    'Bookings must be made at least %d days in advance.',
                    $min_notice,
                    'moga-travel-core'
                ),
                $min_notice
            )
        );
    }

    $max_days = intval( get_option( 'moga_max_booking_days', 365 ) );
    $latest   = new DateTime( '+' . $max_days . ' days' );

    if ( $in > $latest ) {
        return new WP_Error(
            'too_far',
            sprintf(
                /* translators: %d: number of days */
                _n(
                    'Bookings cannot be made more than %d day in advance.',
                    'Bookings cannot be made more than %d days in advance.',
                    $max_days,
                    'moga-travel-core'
                ),
                $max_days
            )
        );
    }

    return true;
}


// ============================================================
// DATE RANGE GENERATION
// ============================================================

/**
 * Get all dates between two dates as an array.
 *
 * Example: moga_date_range('2026-06-19', '2026-06-22')
 * Returns: ['2026-06-19', '2026-06-20', '2026-06-21']
 * Note: check-out date is NOT included (same as hotel logic).
 *
 * @since  1.0.0
 * @param  string $start_date Start date (Y-m-d).
 * @param  string $end_date   End date (Y-m-d) — excluded.
 * @return array              Array of Y-m-d date strings.
 */
function moga_date_range( $start_date, $end_date ) {
    $dates   = array();
    $current = new DateTime( $start_date );
    $end     = new DateTime( $end_date );

    while ( $current < $end ) {
        $dates[] = $current->format( 'Y-m-d' );
        $current->modify( '+1 day' );
    }

    return $dates;
}

/**
 * Check if two date ranges overlap.
 *
 * @since  1.0.0
 * @param  string $start1 Start of range 1 (Y-m-d).
 * @param  string $end1   End of range 1 (Y-m-d).
 * @param  string $start2 Start of range 2 (Y-m-d).
 * @param  string $end2   End of range 2 (Y-m-d).
 * @return bool           True if ranges overlap.
 */
function moga_dates_overlap( $start1, $end1, $start2, $end2 ) {
    return $start1 < $end2 && $end1 > $start2;
}

/**
 * Get today's date in Y-m-d format.
 *
 * @since  1.0.0
 * @return string
 */
function moga_today() {
    return gmdate( 'Y-m-d' );
}

/**
 * Get tomorrow's date in Y-m-d format.
 *
 * @since  1.0.0
 * @return string
 */
function moga_tomorrow() {
    return gmdate( 'Y-m-d', strtotime( '+1 day' ) );
}

/**
 * Get the minimum allowed check-in date.
 *
 * @since  1.0.0
 * @return string Y-m-d date string.
 */
function moga_min_checkin_date() {
    $min_notice = intval( get_option( 'moga_min_booking_notice', 1 ) );
    return gmdate( 'Y-m-d', strtotime( '+' . $min_notice . ' days' ) );
}

/**
 * Get the maximum allowed check-in date.
 *
 * @since  1.0.0
 * @return string Y-m-d date string.
 */
function moga_max_checkin_date() {
    $max_days = intval( get_option( 'moga_max_booking_days', 365 ) );
    return gmdate( 'Y-m-d', strtotime( '+' . $max_days . ' days' ) );
}


// ============================================================
// SEASON HELPERS
// ============================================================

/**
 * Get the season for a given date (Egypt climate).
 *
 * @since  1.0.0
 * @param  string $date Date string (Y-m-d). Defaults to today.
 * @return string       Season name.
 */
function moga_get_season( $date = '' ) {
    if ( empty( $date ) ) {
        $date = moga_today();
    }

    $month = intval( gmdate( 'n', strtotime( $date ) ) );

    if ( in_array( $month, array( 12, 1, 2 ), true ) ) {
        return __( 'Winter', 'moga-travel-core' );
    } elseif ( in_array( $month, array( 3, 4, 5 ), true ) ) {
        return __( 'Spring', 'moga-travel-core' );
    } elseif ( in_array( $month, array( 6, 7, 8 ), true ) ) {
        return __( 'Summer', 'moga-travel-core' );
    }

    return __( 'Autumn', 'moga-travel-core' );
}

/**
 * Check if a date falls in peak season (October–April for Egypt).
 *
 * @since  1.0.0
 * @param  string $date Date string (Y-m-d).
 * @return bool
 */
function moga_is_peak_season( $date ) {
    $month = intval( gmdate( 'n', strtotime( $date ) ) );
    return in_array( $month, array( 10, 11, 12, 1, 2, 3, 4 ), true );
}