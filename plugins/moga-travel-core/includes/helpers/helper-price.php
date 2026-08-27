<?php

/**
 * Price Helper Functions
 *
 * Price formatting, currency handling, discount calculation,
 * and total price computation used throughout the
 * Moga Booking System.
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
// PRICE FORMATTING
// ============================================================

/**
 * Format a price with currency symbol.
 *
 * Example: moga_format_price(150) → "$150.00"
 * Example: moga_format_price(150, 'EGP') → "150.00 EGP"
 *
 * @since  1.0.0
 * @param  float  $amount   Price amount.
 * @param  string $currency Currency code. Uses plugin default if empty.
 * @param  bool   $symbol   Whether to show currency symbol. Default true.
 * @return string           Formatted price string.
 */
function moga_format_price($amount, $currency = '', $symbol = true)
{

    $amount = floatval($amount);

    if (empty($currency)) {
        $currency = get_option('moga_currency', 'USD');
    }

    // Format the number with 2 decimal places.
    $formatted = number_format($amount, 2, '.', ',');

    if (! $symbol) {
        return $formatted;
    }

    $currency_symbol   = moga_get_currency_symbol($currency);
    $symbol_position   = get_option('moga_currency_position', 'before');

    if ('before' === $symbol_position) {
        return $currency_symbol . $formatted;
    }

    return $formatted . ' ' . $currency_symbol;
}

/**
 * Format a price range.
 *
 * Example: "$50.00 – $120.00"
 *
 * @since  1.0.0
 * @param  float  $min      Minimum price.
 * @param  float  $max      Maximum price.
 * @param  string $currency Currency code.
 * @return string           Formatted price range.
 */
function moga_format_price_range($min, $max, $currency = '')
{
    return moga_format_price($min, $currency)
        . ' – '
        . moga_format_price($max, $currency);
}

/**
 * Format a price per night.
 *
 * Example: "$75.00 / night"
 *
 * @since  1.0.0
 * @param  float  $price    Price per night.
 * @param  string $currency Currency code.
 * @return string           Formatted price per night.
 */
function moga_format_price_per_night($price, $currency = '')
{
    return moga_format_price($price, $currency)
        . ' / '
        . __('night', 'moga-travel-core');
}

/**
 * Format a price per person.
 *
 * Example: "$45.00 / person"
 *
 * @since  1.0.0
 * @param  float  $price    Price per person.
 * @param  string $currency Currency code.
 * @return string           Formatted price per person.
 */
function moga_format_price_per_person($price, $currency = '')
{
    return moga_format_price($price, $currency)
        . ' / '
        . __('person', 'moga-travel-core');
}


// ============================================================
// CURRENCY
// ============================================================

/**
 * Get currency symbol for a given currency code.
 *
 * @since  1.0.0
 * @param  string $currency_code ISO currency code (e.g. USD, EGP).
 * @return string                Currency symbol (e.g. $, E£).
 */
function moga_get_currency_symbol($currency_code = '')
{

    if (empty($currency_code)) {
        $currency_code = get_option('moga_currency', 'USD');
    }

    $symbols = array(
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'EGP' => 'E£',
        'SAR' => 'SR',
        'AED' => 'AED',
        'KWD' => 'KD',
        'QAR' => 'QR',
        'BHD' => 'BD',
        'OMR' => 'OMR',
        'JOD' => 'JD',
        'LBP' => 'L£',
        'MAD' => 'MAD',
        'TND' => 'DT',
        'DZD' => 'DA',
        'LYD' => 'LD',
        'SDG' => 'SDG',
        'TRY' => '₺',
        'CAD' => 'CA$',
        'AUD' => 'AU$',
        'JPY' => '¥',
        'CNY' => '¥',
        'INR' => '₹',
        'RUB' => '₽',
        'CHF' => 'CHF',
        'SEK' => 'kr',
        'NOK' => 'kr',
        'DKK' => 'kr',
    );

    return isset($symbols[$currency_code])
        ? $symbols[$currency_code]
        : $currency_code;
}

/**
 * Get all supported currencies for dropdown.
 *
 * @since  1.0.0
 * @return array code => name pairs.
 */
function moga_get_currencies()
{
    return array(
        'USD' => __('US Dollar ($)', 'moga-travel-core'),
        'EUR' => __('Euro (€)', 'moga-travel-core'),
        'GBP' => __('British Pound (£)', 'moga-travel-core'),
        'EGP' => __('Egyptian Pound (E£)', 'moga-travel-core'),
        'SAR' => __('Saudi Riyal (SR)', 'moga-travel-core'),
        'AED' => __('UAE Dirham (AED)', 'moga-travel-core'),
        'KWD' => __('Kuwaiti Dinar (KD)', 'moga-travel-core'),
        'QAR' => __('Qatari Riyal (QR)', 'moga-travel-core'),
        'BHD' => __('Bahraini Dinar (BD)', 'moga-travel-core'),
        'OMR' => __('Omani Rial (OMR)', 'moga-travel-core'),
        'JOD' => __('Jordanian Dinar (JD)', 'moga-travel-core'),
        'MAD' => __('Moroccan Dirham (MAD)', 'moga-travel-core'),
        'TND' => __('Tunisian Dinar (DT)', 'moga-travel-core'),
        'TRY' => __('Turkish Lira (₺)', 'moga-travel-core'),
        'CAD' => __('Canadian Dollar (CA$)', 'moga-travel-core'),
        'AUD' => __('Australian Dollar (AU$)', 'moga-travel-core'),
        'CHF' => __('Swiss Franc (CHF)', 'moga-travel-core'),
    );
}


// ============================================================
// DISCOUNT CALCULATION
// ============================================================

/**
 * Calculate discounted price.
 *
 * @since  1.0.0
 * @param  float  $original_price  Original price.
 * @param  float  $discount_percent Discount percentage (0-100).
 * @return float                   Discounted price.
 */
function moga_apply_discount($original_price, $discount_percent)
{
    $original_price   = floatval($original_price);
    $discount_percent = floatval($discount_percent);

    if ($discount_percent <= 0 || $discount_percent > 100) {
        return $original_price;
    }

    $discount_amount = $original_price * ($discount_percent / 100);

    return round($original_price - $discount_amount, 2);
}

/**
 * Calculate discount amount from original price and percentage.
 *
 * @since  1.0.0
 * @param  float $original_price   Original price.
 * @param  float $discount_percent Discount percentage.
 * @return float                   Discount amount.
 */
function moga_calculate_discount_amount($original_price, $discount_percent)
{
    $original_price   = floatval($original_price);
    $discount_percent = floatval($discount_percent);

    if ($discount_percent <= 0) {
        return 0.00;
    }

    return round($original_price * ($discount_percent / 100), 2);
}

/**
 * Calculate percentage saved between original and sale price.
 *
 * @since  1.0.0
 * @param  float $original_price Original price.
 * @param  float $sale_price     Sale price.
 * @return int                   Percentage saved (0-100).
 */
function moga_calculate_savings_percent($original_price, $sale_price)
{
    $original_price = floatval($original_price);
    $sale_price     = floatval($sale_price);

    if ($original_price <= 0 || $sale_price >= $original_price) {
        return 0;
    }

    return intval(round((($original_price - $sale_price) / $original_price) * 100));
}


// ============================================================
// PROPERTY PRICE CALCULATION
// ============================================================

/**
 * Calculate total price for a property booking.
 *
 * @since  1.0.0
 * @param  int    $property_id Property post ID.
 * @param  string $check_in    Check-in date (Y-m-d).
 * @param  string $check_out   Check-out date (Y-m-d).
 * @return array               Price breakdown array.
 */
function moga_calculate_property_price($property_id, $check_in, $check_out)
{

    $nights = moga_calculate_nights($check_in, $check_out);

    $currency = get_post_meta($property_id, '_moga_currency', true)
        ?: get_option('moga_currency', 'USD');

    $empty_result = array(
        'nights'           => 0,
        'weekend_nights'   => 0,
        'weekday_nights'   => 0,
        'price_per_night'  => 0,
        'subtotal'         => 0,
        'discount_percent' => 0,
        'discount'         => 0,
        'tax_rate'         => 0,
        'taxes'            => 0,
        'total'            => 0,
        'currency'         => $currency,
    );

    if ($nights <= 0) {
        return $empty_result;
    }

    // REBUILT (Aug 2026 session — Period-only pricing model): a
    // property has no flat rate of its own anymore. Previously this
    // function bailed out immediately whenever '_moga_price_per_night'
    // was 0 — which is now ALWAYS true, since nothing saves that
    // field anymore (moved entirely into Pricing Periods). That gate
    // was silently returning all-zeros for every single request,
    // discarding the correctly-calculated $nights value above and
    // never even reaching the (already-correct) per-date pricing
    // logic below.
    //
    // Every night's real price now comes from price_override,
    // written by Moga_Availability::apply_period() for every date
    // inside a defined Pricing Period — including that period's own
    // weekend-day resolution, already baked in at save time. Neither
    // '_moga_price_per_night', '_moga_price_weekend', nor
    // '_moga_weekend_days' (property-wide) are read here anymore.

    // Find the single period covering check-in — a valid request
    // must have its ENTIRE range inside one period
    // (moga_is_available() already enforces this before a real
    // booking can be created). Used here for that period's own
    // discount % and weekend_days, for the nights breakdown.
    $periods_json = get_post_meta($property_id, '_moga_pricing_periods', true);
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

    $discount_percent    = ($covering_period && isset($covering_period['discount'])) ? floatval($covering_period['discount']) : 0;
    $period_weekend_days = ($covering_period && is_array($covering_period['weekend_days'] ?? null))
        ? array_map('intval', $covering_period['weekend_days'])
        : array();

    global $wpdb;
    $prefix = $wpdb->prefix . MOGA_CORE_DB_PREFIX;

    $overrides = $wpdb->get_results($wpdb->prepare(
        "SELECT date, price_override FROM {$prefix}availability
         WHERE listing_id = %d AND date >= %s AND date < %s AND price_override IS NOT NULL",
        $property_id,
        $check_in,
        $check_out
    ), OBJECT_K);

    $dates          = moga_date_range($check_in, $check_out);
    $subtotal       = 0;
    $weekend_nights = 0;
    $weekday_nights = 0;

    foreach ($dates as $date) {
        // A valid request should have 100% period coverage — every
        // date in a real booking belongs to the SAME single period.
        // A date with no override here means no period covers it —
        // an invalid or incomplete request, not something to
        // silently price at zero for just that one night.
        if (! isset($overrides[$date]) || null === $overrides[$date]->price_override) {
            return $empty_result;
        }

        $subtotal += (float) $overrides[$date]->price_override;

        $day_of_week = intval(gmdate('w', strtotime($date)));
        if (in_array($day_of_week, $period_weekend_days, true)) {
            $weekend_nights++;
        } else {
            $weekday_nights++;
        }
    }

    $discount = 0;
    if ($discount_percent > 0) {
        $discount = moga_calculate_discount_amount($subtotal, $discount_percent);
    }
    $subtotal_after_discount = $subtotal - $discount;

    $tax_rate = floatval(get_option('moga_tax_rate', 0));
    $taxes    = $tax_rate > 0
        ? round($subtotal_after_discount * ($tax_rate / 100), 2)
        : 0;

    $total = round($subtotal_after_discount + $taxes, 2);

    return array(
        'nights'           => $nights,
        'weekend_nights'   => $weekend_nights,
        'weekday_nights'   => $weekday_nights,
        'price_per_night'  => round($subtotal / $nights, 2),
        'subtotal'         => round($subtotal, 2),
        'discount_percent' => $discount_percent,
        'discount'         => round($discount, 2),
        'tax_rate'         => $tax_rate,
        'taxes'            => $taxes,
        'total'            => $total,
        'currency'         => $currency,
    );
}


// ============================================================
// TOUR PRICE CALCULATION
// ============================================================

/**
 * Calculate total price for a tour booking.
 *
 * @since  1.0.0
 * @param  int $tour_id       Tour post ID.
 * @param  int $adults        Number of adult participants.
 * @param  int $children      Number of child participants.
 * @param  int $infants       Number of infant participants.
 * @return array              Price breakdown array.
 */
function moga_calculate_tour_price($tour_id, $adults = 1, $children = 0, $infants = 0)
{

    $price_adult    = floatval(get_post_meta($tour_id, '_moga_price_per_person', true));
    $price_child    = floatval(get_post_meta($tour_id, '_moga_price_child', true));
    $price_infant   = floatval(get_post_meta($tour_id, '_moga_price_infant', true));
    $group_discount = floatval(get_post_meta($tour_id, '_moga_price_group', true));

    $adults   = max(0, intval($adults));
    $children = max(0, intval($children));
    $infants  = max(0, intval($infants));

    $adults_total   = $price_adult * $adults;
    $children_total = $price_child * $children;
    $infants_total  = $price_infant * $infants;
    $subtotal       = $adults_total + $children_total + $infants_total;

    // Apply group discount.
    $discount = 0;
    if ($group_discount > 0) {
        $discount = moga_calculate_discount_amount($subtotal, $group_discount);
    }

    $subtotal_after_discount = $subtotal - $discount;

    // Calculate taxes.
    $tax_rate = floatval(get_option('moga_tax_rate', 0));
    $taxes    = $tax_rate > 0
        ? round($subtotal_after_discount * ($tax_rate / 100), 2)
        : 0;

    $total = round($subtotal_after_discount + $taxes, 2);

    return array(
        'adults'           => $adults,
        'children'         => $children,
        'infants'          => $infants,
        'price_adult'      => $price_adult,
        'price_child'      => $price_child,
        'price_infant'     => $price_infant,
        'adults_total'     => round($adults_total, 2),
        'children_total'   => round($children_total, 2),
        'infants_total'    => round($infants_total, 2),
        'subtotal'         => round($subtotal, 2),
        'group_discount'   => $group_discount,
        'discount'         => round($discount, 2),
        'tax_rate'         => $tax_rate,
        'taxes'            => $taxes,
        'total'            => $total,
        'currency'         => get_post_meta($tour_id, '_moga_currency', true)
            ?: get_option('moga_currency', 'USD'),
    );
}


// ============================================================
// COMMISSION CALCULATION
// ============================================================

/**
 * Calculate platform commission for a booking.
 *
 * @since  1.0.0
 * @param  float  $booking_total   Total booking amount.
 * @param  float  $commission_rate Commission rate percentage.
 * @return array                   Commission breakdown.
 */
function moga_calculate_commission($booking_total, $commission_rate = null)
{

    $booking_total = floatval($booking_total);

    if (null === $commission_rate) {
        $commission_rate = floatval(get_option('moga_commission_rate', 10));
    }

    $commission_amount = round($booking_total * ($commission_rate / 100), 2);
    $owner_earnings    = round($booking_total - $commission_amount, 2);

    return array(
        'booking_total'     => $booking_total,
        'commission_rate'   => $commission_rate,
        'commission_amount' => $commission_amount,
        'owner_earnings'    => $owner_earnings,
    );
}


// ============================================================
// PRICE DISPLAY HELPERS
// ============================================================

/**
 * Render a price with optional original price (for discounts).
 *
 * Example output:
 *   <span class="moga-price">$75.00</span>
 *   <span class="moga-price__original">$100.00</span>
 *   <span class="moga-price__badge">-25%</span>
 *
 * @since  1.0.0
 * @param  float  $price          Current price.
 * @param  float  $original_price Original price (0 if no discount).
 * @param  string $currency       Currency code.
 * @param  string $suffix         Optional suffix (e.g. "/ night").
 * @param  bool   $echo           Whether to echo or return.
 * @return string|void
 */
function moga_render_price($price, $original_price = 0, $currency = '', $suffix = '', $echo = true)
{

    $html = '<div class="moga-price-wrap">';

    // Current price.
    $html .= '<span class="moga-card__price">'
        . esc_html(moga_format_price($price, $currency));

    if ($suffix) {
        $html .= '<span class="moga-card__price-label">'
            . esc_html($suffix) . '</span>';
    }

    $html .= '</span>';

    // Original price and discount badge (if discounted).
    if ($original_price > 0 && $original_price > $price) {
        $savings = moga_calculate_savings_percent($original_price, $price);

        $html .= '<span class="moga-card__price-old">'
            . esc_html(moga_format_price($original_price, $currency))
            . '</span>';

        if ($savings > 0) {
            $html .= '<span class="moga-badge moga-badge--danger">-'
                . esc_html($savings) . '%</span>';
        }
    }

    $html .= '</div>';

    if ($echo) {
        echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        return;
    }

    return $html;
}

/**
 * Get property price with discount applied for display — the
 * "starting from" figure shown on search cards and the property
 * page before any dates are picked.
 *
 * REBUILT (Period-only pricing model): a property has no flat
 * default rate of its own anymore — every price lives inside a
 * Pricing Period. This finds the period with the LOWEST effective
 * (post-discount) nightly rate and uses it, matching standard
 * Booking.com/Airbnb "from" pricing on search cards.
 *
 * Returns the full winning period under 'period' too, so callers
 * (single-moga_property.php, booking-form.php) can pull that SAME
 * period's check-in/out time and min/max stay for display —
 * otherwise those would show mismatched information from a
 * different period than the one whose price is actually shown.
 *
 * @since  1.0.0
 * @param  int $property_id Property post ID.
 * @return array Array with 'price', 'original', 'currency', 'discount', 'period' (full period array, or null if no periods exist).
 */
function moga_get_property_display_price($property_id)
{
    $periods_json = get_post_meta($property_id, '_moga_pricing_periods', true);
    $periods      = $periods_json ? json_decode($periods_json, true) : array();
    $periods      = is_array($periods) ? $periods : array();

    $currency = get_post_meta($property_id, '_moga_currency', true)
        ?: get_option('moga_currency', 'USD');

    $empty_result = array(
        'price'    => 0,
        'original' => 0,
        'currency' => $currency,
        'discount' => 0,
        'period'   => null,
    );

    if (empty($periods)) {
        return $empty_result;
    }

    $lowest = null;

    foreach ($periods as $period) {
        if (empty($period['price']) || $period['price'] <= 0) {
            continue;
        }

        $discount  = isset($period['discount']) ? floatval($period['discount']) : 0;
        $effective = $discount > 0 ? moga_apply_discount($period['price'], $discount) : (float) $period['price'];

        if (null === $lowest || $effective < $lowest['effective']) {
            $lowest = array(
                'effective' => $effective,
                'original'  => (float) $period['price'],
                'discount'  => $discount,
                'data'      => $period,
            );
        }
    }

    if (! $lowest) {
        return $empty_result;
    }

    return array(
        'price'    => round($lowest['effective'], 2),
        'original' => $lowest['discount'] > 0 ? $lowest['original'] : 0,
        'currency' => $currency,
        'discount' => $lowest['discount'],
        'period'   => $lowest['data'],
    );
}

/**
 * Get tour price with discount applied for display.
 *
 * @since  1.0.0
 * @param  int $tour_id Tour post ID.
 * @return array        Array with 'price', 'original', 'currency'.
 */
function moga_get_tour_display_price($tour_id)
{

    $price_per_person = floatval(get_post_meta($tour_id, '_moga_price_per_person', true));
    $group_discount   = floatval(get_post_meta($tour_id, '_moga_price_group', true));
    $currency         = get_post_meta($tour_id, '_moga_currency', true)
        ?: get_option('moga_currency', 'USD');

    $display_price = $group_discount > 0
        ? moga_apply_discount($price_per_person, $group_discount)
        : $price_per_person;

    return array(
        'price'    => $display_price,
        'original' => $group_discount > 0 ? $price_per_person : 0,
        'currency' => $currency,
        'discount' => $group_discount,
    );
}
