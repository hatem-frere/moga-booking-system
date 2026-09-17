<?php

/**
 * Global Helper Functions
 *
 * General-purpose utility functions available throughout
 * the entire Moga Booking System — plugin and theme.
 *
 * @package    MogaTravelCore
 * @subpackage MogaTravelCore/includes/helpers
 * @author     Hatem Frere
 * @since      1.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}


// ============================================================
// BOOKING NUMBER
// ============================================================

/**
 * Generate a unique booking reference number.
 *
 * Format: MB-YYYYMMDD-XXXXX
 * Example: MB-20260619-A3F7K
 *
 * @since  1.0.0
 * @return string Unique booking number.
 */
function moga_generate_booking_number()
{
    $date   = gmdate('Ymd');
    $random = strtoupper(substr(base_convert(bin2hex(random_bytes(4)), 16, 36), 0, 5));
    return 'MB-' . $date . '-' . $random;
}


// ============================================================
// RATING HELPERS
// ============================================================

/**
 * Get rating label from numeric score.
 *
 * Booking.com style labels based on score out of 10.
 *
 * @since  1.0.0
 * @param  float  $score Rating score (0-10).
 * @return string        Label (Exceptional, Superb, etc.)
 */
function moga_get_rating_label($score)
{
    $score = floatval($score);

    if ($score >= 9.0) {
        return __('Exceptional', 'moga-travel-core');
    } elseif ($score >= 8.0) {
        return __('Superb', 'moga-travel-core');
    } elseif ($score >= 7.0) {
        return __('Very Good', 'moga-travel-core');
    } elseif ($score >= 6.0) {
        return __('Good', 'moga-travel-core');
    } elseif ($score >= 5.0) {
        return __('Pleasant', 'moga-travel-core');
    } elseif ($score > 0) {
        return __('Reviewed', 'moga-travel-core');
    }

    return __('No Reviews', 'moga-travel-core');
}

/**
 * Get rating color class based on score.
 *
 * @since  1.0.0
 * @param  float  $score Rating score (0-10).
 * @return string        CSS color class.
 */
function moga_get_rating_color($score)
{
    $score = floatval($score);

    if ($score >= 8.0) {
        return 'moga-rating--excellent';
    } elseif ($score >= 7.0) {
        return 'moga-rating--good';
    } elseif ($score >= 6.0) {
        return 'moga-rating--average';
    }

    return 'moga-rating--low';
}

/**
 * Render star rating HTML.
 *
 * @since  1.0.0
 * @param  int    $stars   Number of stars (1-5).
 * @param  bool   $echo    Whether to echo or return.
 * @return string|void
 */
function moga_render_stars($stars, $echo = true)
{
    $stars  = max(0, min(5, intval($stars)));
    $html   = '<span class="moga-stars" aria-label="' . sprintf(
        /* translators: %d: number of stars */
        esc_attr__('%d out of 5 stars', 'moga-travel-core'),
        $stars
    ) . '">';

    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $stars) {
            $html .= '<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>';
        } else {
            $html .= '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" class="star-empty"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>';
        }
    }

    $html .= '</span>';

    if ($echo) {
        echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        return;
    }

    return $html;
}


// ============================================================
// URL HELPERS
// ============================================================

/**
 * Get the search results page URL with optional parameters.
 *
 * @since  1.0.0
 * @param  array  $args Optional query parameters.
 * @return string       Full search URL.
 */
function moga_search_url($args = array())
{
    $base = get_option('moga_page_search_results')
        ? get_permalink(get_option('moga_page_search_results'))
        : home_url('/search-results/');

    if (empty($args)) {
        return $base;
    }

    return add_query_arg($args, $base);
}

/**
 * Get the booking page URL.
 *
 * @since  1.0.0
 * @param  array  $args Optional query parameters.
 * @return string       Full booking URL.
 */
function moga_booking_url($args = array())
{
    $base = get_option('moga_page_booking')
        ? get_permalink(get_option('moga_page_booking'))
        : home_url('/booking/');

    if (empty($args)) {
        return $base;
    }

    return add_query_arg($args, $base);
}

/**
 * Get the checkout page URL.
 *
 * @since  1.0.0
 * @param  array  $args Optional query parameters.
 * @return string       Full checkout URL.
 */
function moga_checkout_url($args = array())
{
    $base = get_option('moga_page_checkout')
        ? get_permalink(get_option('moga_page_checkout'))
        : home_url('/checkout/');

    if (empty($args)) {
        return $base;
    }

    return add_query_arg($args, $base);
}

/**
 * Get the dashboard page URL.
 *
 * @since  1.0.0
 * @return string Full dashboard URL.
 */
function moga_dashboard_url()
{
    return get_option('moga_page_dashboard')
        ? get_permalink(get_option('moga_page_dashboard'))
        : home_url('/dashboard/');
}

/**
 * Get the my account page URL.
 *
 * @since  1.0.0
 * @return string Full account URL.
 */
function moga_account_url()
{
    return get_option('moga_page_my_account')
        ? get_permalink(get_option('moga_page_my_account'))
        : home_url('/my-account/');
}


// ============================================================
// USER / ROLE HELPERS
// ============================================================

/**
 * Check if a user is specifically a Property Owner.
 *
 * BUG FIX (this session): previously only checked the legacy
 * 'moga_owner' role, which class-moga-roles.php no longer assigns
 * to anyone as of the ROLES_VERSION 2.0.0 split into distinct
 * 'moga_property_owner' / 'moga_tour_organizer' roles — meaning
 * this returned false for every real property owner account. The
 * legacy role is still checked too, for any account not yet
 * manually migrated (see Moga_Roles::maybe_show_legacy_owner_notice()).
 *
 * @since  1.0.0
 * @param  int|null $user_id Optional user ID. Defaults to current user.
 * @return bool
 */
function moga_is_owner($user_id = null)
{
    if (! $user_id) {
        $user_id = get_current_user_id();
    }

    if (! $user_id) {
        return false;
    }

    $user = get_userdata($user_id);

    return $user && (
        in_array('moga_property_owner', (array) $user->roles, true)
        || in_array('moga_owner', (array) $user->roles, true) // Legacy, pre-2.0.0.
        || in_array('administrator', (array) $user->roles, true)
    );
}

/**
 * Check if a user is specifically a Tour Organizer.
 *
 * @since  1.0.0
 * @param  int|null $user_id Optional user ID. Defaults to current user.
 * @return bool
 */
function moga_is_tour_organizer($user_id = null)
{
    if (! $user_id) {
        $user_id = get_current_user_id();
    }

    if (! $user_id) {
        return false;
    }

    $user = get_userdata($user_id);

    return $user && (
        in_array('moga_tour_organizer', (array) $user->roles, true)
        || in_array('administrator', (array) $user->roles, true)
    );
}

/**
 * Check if a user is ANY kind of Moga vendor — Property Owner OR
 * Tour Organizer (or an administrator). Use this for "can access
 * vendor-facing features" checks (e.g. the owner/organizer
 * dashboard), where it doesn't matter which specific vendor type
 * the account is — as opposed to moga_is_owner() or
 * moga_is_tour_organizer(), which are deliberately role-specific.
 *
 * Deliberately does NOT check Moga_Roles::is_vendor_approved() —
 * that gate controls whether a vendor's LISTINGS can go live, not
 * whether they can access their own dashboard. A still-pending
 * vendor should still be able to log in and see their status.
 *
 * @since  1.0.0
 * @param  int|null $user_id Optional user ID. Defaults to current user.
 * @return bool
 */
function moga_is_vendor($user_id = null)
{
    if (! $user_id) {
        $user_id = get_current_user_id();
    }

    if (! $user_id) {
        return false;
    }

    $user = get_userdata($user_id);

    return $user && (
        in_array('moga_property_owner', (array) $user->roles, true)
        || in_array('moga_tour_organizer', (array) $user->roles, true)
        || in_array('moga_owner', (array) $user->roles, true) // Legacy, pre-2.0.0.
        || in_array('administrator', (array) $user->roles, true)
    );
}

/**
 * Check if current user is a guest (registered traveler).
 *
 * @since  1.0.0
 * @param  int|null $user_id Optional user ID.
 * @return bool
 */
function moga_is_guest($user_id = null)
{
    if (! $user_id) {
        $user_id = get_current_user_id();
    }

    if (! $user_id) {
        return false;
    }

    $user = get_userdata($user_id);

    return $user && in_array('moga_guest', (array) $user->roles, true);
}

/**
 * Check if current user can manage a specific property.
 *
 * @since  1.0.0
 * @param  int $property_id Property post ID.
 * @return bool
 */
function moga_can_manage_property($property_id)
{
    if (! is_user_logged_in()) {
        return false;
    }

    if (current_user_can('manage_options')) {
        return true;
    }

    return (int) get_post_field('post_author', $property_id) === get_current_user_id();
}


// ============================================================
// AMENITY HELPERS
// ============================================================

/**
 * Get amenities for a property as an array.
 *
 * @since  1.0.0
 * @param  int   $property_id Property post ID.
 * @return array Array of amenity keys.
 */
function moga_get_property_amenities($property_id)
{
    $amenities = get_post_meta($property_id, '_moga_amenities', true);

    if (empty($amenities)) {
        return array();
    }

    $decoded = json_decode($amenities, true);

    return is_array($decoded) ? $decoded : array();
}

/**
 * Check if a property has a specific amenity.
 *
 * @since  1.0.0
 * @param  int    $property_id Property post ID.
 * @param  string $amenity_key Amenity key (e.g. 'wifi', 'pool').
 * @return bool
 */
function moga_has_amenity($property_id, $amenity_key)
{
    return in_array($amenity_key, moga_get_property_amenities($property_id), true);
}


// ============================================================
// AVAILABILITY HELPERS
// ============================================================

/**
 * Check if a listing is available for given dates.
 *
 * PROPERTY RULE (locked decision): a property is bookable ONLY
 * within a single Pricing Period — never straddling two, even two
 * periods sitting directly adjacent with no gap between them. So
 * this doesn't just check "is every night covered by some period or
 * other" (which would wrongly allow spanning two adjacent periods)
 * — it finds the ONE period covering check-in, then requires
 * check-out to fall within that SAME period's own end date.
 *
 * Tours/buses/rentals are unaffected — this single-period rule is
 * property-specific, gated by $listing_type below.
 *
 * @since  1.0.0
 * @param  int    $listing_id   Property or tour post ID.
 * @param  string $check_in     Check-in date (Y-m-d).
 * @param  string $check_out    Check-out date (Y-m-d).
 * @param  string $listing_type Listing type (property, tour).
 * @return bool
 */
function moga_is_available($listing_id, $check_in, $check_out, $listing_type = 'property', $requested_seats = 1)
{
    // Tours use a fundamentally different availability model than
    // properties — a specific Group's real, live seat capacity plus
    // its own booking cutoff, not "is this date blocked at all".
    // The generic blocked/status check below is correct for
    // properties (a night can only be sold once) but was WRONG for
    // tours: it would mark a date fully unavailable after a single
    // booking, with zero concept of remaining seats, regardless of
    // how large that group's real capacity was. Routed entirely to
    // moga_is_tour_group_available() instead — see helper-price.php.
    if ('tour' === $listing_type) {
        return function_exists('moga_is_tour_group_available')
            ? moga_is_tour_group_available($listing_id, $check_in, max(1, intval($requested_seats)))
            : false;
    }

    global $wpdb;

    $prefix = $wpdb->prefix . MOGA_CORE_DB_PREFIX;

    $blocked = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$prefix}availability
         WHERE listing_id = %d
         AND listing_type = %s
         AND date >= %s
         AND date < %s
         AND status IN ('booked', 'blocked')",
        $listing_id,
        $listing_type,
        $check_in,
        $check_out
    ));

    if (intval($blocked) > 0) {
        return false;
    }

    if ('property' === $listing_type) {
        $periods_json = get_post_meta($listing_id, '_moga_pricing_periods', true);
        $periods      = $periods_json ? json_decode($periods_json, true) : array();
        $periods      = is_array($periods) ? $periods : array();

        $covering_period = null;
        foreach ($periods as $period) {
            if (
                ! empty($period['start']) && ! empty($period['end'])
                && $check_in >= $period['start'] && $check_in < $period['end']
            ) {
                $covering_period = $period;
                break;
            }
        }

        // No period covers the check-in date at all — the property
        // simply has no rate/rules defined for this date, so it's
        // not bookable, regardless of the raw 'available' status.
        if (! $covering_period) {
            return false;
        }

        // Check-out must fall within THAT SAME period — never
        // extend into the next period, even an adjacent one.
        if ($check_out > $covering_period['end']) {
            return false;
        }
    }

    return true;
}


// ============================================================
// SANITIZATION HELPERS
// ============================================================

/**
 * Sanitize a phone number — keep only digits and + sign.
 *
 * @since  1.0.0
 * @param  string $phone Raw phone number.
 * @return string        Sanitized phone number.
 */
function moga_sanitize_phone($phone)
{
    return preg_replace('/[^\d+\-\s()]/', '', $phone);
}

/**
 * Sanitize a coordinate value (latitude or longitude).
 *
 * @since  1.0.0
 * @param  mixed $value Raw coordinate.
 * @return float        Sanitized coordinate.
 */
function moga_sanitize_coordinate($value)
{
    return floatval(preg_replace('/[^\d.\-]/', '', $value));
}

/**
 * Sanitize a hex color value.
 *
 * @since  1.0.0
 * @param  string $color Raw color string.
 * @return string        Sanitized hex color or empty string.
 */
function moga_sanitize_hex_color($color)
{
    if (preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $color)) {
        return $color;
    }
    return '';
}


// ============================================================
// MISCELLANEOUS
// ============================================================

/**
 * Get the plugin option with a fallback default.
 *
 * @since  1.0.0
 * @param  string $key     Option key (without moga_ prefix).
 * @param  mixed  $default Default value if option not set.
 * @return mixed
 */
function moga_option($key, $default = '')
{
    return get_option('moga_' . $key, $default);
}

/**
 * Check if the Moga Travel Core plugin is active.
 *
 * @since  1.0.0
 * @return bool
 */
function moga_plugin_active()
{
    return defined('MOGA_CORE_VERSION');
}

/**
 * Get the default currency symbol.
 *
 * @since  1.0.0
 * @return string
 */
function moga_currency_symbol()
{
    return get_option('moga_currency_symbol', '$');
}

/**
 * Get the default currency code.
 *
 * @since  1.0.0
 * @return string
 */
function moga_currency()
{
    return get_option('moga_currency', 'USD');
}

/**
 * Truncate a string to a given length with ellipsis.
 *
 * @since  1.0.0
 * @param  string $text   Input text.
 * @param  int    $length Max length.
 * @param  string $more   Suffix when truncated.
 * @return string
 */
function moga_truncate($text, $length = 120, $more = '...')
{
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . $more;
}

/**
 * Get guest count label.
 *
 * @since  1.0.0
 * @param  int $adults   Number of adults.
 * @param  int $children Number of children.
 * @param  int $infants  Number of infants.
 * @return string
 */
function moga_guest_label($adults = 1, $children = 0, $infants = 0)
{
    $parts = array();

    if ($adults > 0) {
        $parts[] = sprintf(
            /* translators: %d: number of adults */
            _n('%d adult', '%d adults', $adults, 'moga-travel-core'),
            $adults
        );
    }

    if ($children > 0) {
        $parts[] = sprintf(
            /* translators: %d: number of children */
            _n('%d child', '%d children', $children, 'moga-travel-core'),
            $children
        );
    }

    if ($infants > 0) {
        $parts[] = sprintf(
            /* translators: %d: number of infants */
            _n('%d infant', '%d infants', $infants, 'moga-travel-core'),
            $infants
        );
    }

    return implode(', ', $parts);
}
