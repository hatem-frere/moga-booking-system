<?php

/**
 * Property Booking Form
 *
 * Path: themes/moga-travel/template-parts/property/booking-form.php
 *
 * Top price badge always shows a "starting from" figure. The price
 * breakdown box stays hidden until the guest actually picks real
 * dates (or arrives with dates already in the URL) — nothing but
 * the badge is shown before that.
 *
 * @package MogaTravel
 * @since   1.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

$property_id = get_the_ID();

$display_price   = function_exists('moga_get_property_display_price')
    ? moga_get_property_display_price($property_id)
    : array('price' => 0, 'original' => 0, 'currency' => 'USD', 'discount' => 0);

$price_per_night = $display_price['price'];
$original_price  = $display_price['original'];
$currency        = $display_price['currency'];
$discount        = $display_price['discount'];

// Resolved currency SYMBOL ("E£"), not just the code ("EGP") — for
// booking.js's fmt(), which previously only had the code available
// and had to fall back to printing "EGP 4,500.00" instead of a
// proper "E£4,500.00" to match the top badge. Reuses
// moga_format_price() (the same function already proven correct
// for the badge) rather than guessing at the currency data
// structure — strips all digits/punctuation/whitespace from a
// zero-amount format, leaving just the symbol regardless of
// whether it's a prefix or suffix currency.
$currency_symbol = trim(preg_replace('/[\d.,\s]/', '', moga_format_price(0, $currency)));

// Booking rules — pulled from the SAME period used for the
// displayed price above (see helper-price.php's
// moga_get_property_display_price() docblock), not the old flat
// fields, which can hold stale leftover data from before the
// period-only pricing model.
$reference_period = $display_price['period'];
$min_stay          = $reference_period && isset($reference_period['min_stay']) ? intval($reference_period['min_stay']) : 1;
$max_stay          = $reference_period && isset($reference_period['max_stay']) ? intval($reference_period['max_stay']) : 0;
$max_guests    = intval(get_post_meta($property_id, '_moga_max_guests',    true)) ?: 10;
$checkin_time      = $reference_period && ! empty($reference_period['checkin_time'])  ? $reference_period['checkin_time']  : '14:00';
$checkout_time     = $reference_period && ! empty($reference_period['checkout_time']) ? $reference_period['checkout_time'] : '11:00';
$instant       = get_post_meta($property_id, '_moga_instant_booking', true);

// Pricing Periods — only the fields booking.js needs for date-picker
// min/max-stay enforcement (Gap 1: the calendar previously only knew
// the property's flat min/max, so it could visually allow (or block)
// a stay length the server would then disagree with once a period's
// own, different min/max applied). Price fields aren't included here
// yet — that's a separate, later piece (in-calendar price display).
$pricing_periods_json   = get_post_meta($property_id, '_moga_pricing_periods', true);
$pricing_periods_raw    = $pricing_periods_json ? json_decode($pricing_periods_json, true) : array();
$pricing_periods_raw    = is_array($pricing_periods_raw) ? $pricing_periods_raw : array();
$pricing_periods_for_js = array_map(function ($period) {
    return array(
        'start'         => isset($period['start']) ? $period['start'] : '',
        'end'           => isset($period['end']) ? $period['end'] : '',
        'min_stay'      => isset($period['min_stay']) ? intval($period['min_stay']) : null,
        'max_stay'      => isset($period['max_stay']) ? intval($period['max_stay']) : null,
        'checkin_time'  => isset($period['checkin_time'])  ? $period['checkin_time']  : '',
        'checkout_time' => isset($period['checkout_time']) ? $period['checkout_time'] : '',
    );
}, $pricing_periods_raw);

$rating       = floatval(get_post_meta($property_id, '_moga_rating',       true));
$review_count = intval(get_post_meta($property_id, '_moga_review_count', true));

$cancellation    = get_post_meta($property_id, '_moga_cancellation', true) ?: 'moderate';
$cancel_policies = class_exists('Moga_CPT_Property') ? Moga_CPT_Property::get_cancellation_policies() : array();
$cancel_label    = isset($cancel_policies[$cancellation]) ? $cancel_policies[$cancellation]['label'] : '';

$checkin_val  = isset($_GET['check_in'])  ? sanitize_text_field(wp_unslash($_GET['check_in']))  : '';
$checkout_val = isset($_GET['check_out']) ? sanitize_text_field(wp_unslash($_GET['check_out'])) : '';
$guests_val   = isset($_GET['guests'])    ? min(max(1, absint($_GET['guests'])), $max_guests) : 1;

$booking_page_url = get_option('moga_page_booking')
    ? get_permalink(get_option('moga_page_booking'))
    : home_url('/booking/');

// Default price breakdown.
//
// BUG FIX (Aug 15 session): this used to always build the breakdown
// from moga_get_property_display_price(), which (a) already returns
// an ALREADY-DISCOUNTED per-night price, and (b) has no concept of
// dates at all. Multiplying that by "1 night" and then subtracting
// the discount percentage AGAIN compounded the discount, and never
// applied weekend pricing even when real check-in/check-out dates
// were already known (e.g. arriving from search results with dates
// in the URL). Fixed: when real dates are known, defer entirely to
// moga_calculate_property_price() — the same weekend-aware,
// override-aware function the AJAX price calculator already uses
// correctly — rather than duplicating (and getting wrong) that math
// here. Only fall back to the simple marketing-price placeholder
// when no dates are selected yet, which is a legitimate, different
// use case (nothing meaningful to calculate without real dates).
if ($checkin_val && $checkout_val) {
    $server_price      = moga_calculate_property_price($property_id, $checkin_val, $checkout_val);
    $default_nights    = max(1, intval($server_price['nights']));
    $default_subtotal  = (float) $server_price['subtotal'];
    $default_discount  = (float) $server_price['discount'];
    $default_total     = (float) $server_price['total'];
    $currency          = $server_price['currency'];

    // Top badge — average per-night rate actually used for these
    // specific dates (a standard, widely-used convention, same as
    // Airbnb/Booking.com's own listing price display), rather than
    // the generic, date-blind marketing price. Pre-discount average
    // only shown (struck through) when a discount actually applies.
    $badge_price_per_night = $default_total / $default_nights;
    $badge_original_price  = $default_discount > 0 ? ($default_subtotal / $default_nights) : 0;
} else {
    $default_nights   = 1;
    $default_subtotal = $price_per_night * $default_nights;
    $default_discount = $discount > 0 ? $default_subtotal * ($discount / 100) : 0;
    $default_total    = $default_subtotal - $default_discount;

    $badge_price_per_night = $price_per_night;
    $badge_original_price  = $original_price;
}
?>

<div class="moga-booking-form-card">

    <?php // ---- Price Header ----
    ?>
    <div class="moga-booking-form-card__price">
        <span class="moga-booking-form-card__price-old" id="moga-badge-price-old" <?php echo ($badge_original_price > 0 && $badge_original_price > $badge_price_per_night) ? '' : 'hidden'; ?>>
            <?php echo esc_html(moga_format_price($badge_original_price, $currency)); ?>
        </span>
        <span class="moga-booking-form-card__price-current" id="moga-badge-price-current">
            <?php echo esc_html(moga_format_price($badge_price_per_night, $currency)); ?>
        </span>
        <span class="moga-booking-form-card__price-label">
            <?php esc_html_e('/ night', 'moga-travel'); ?>
        </span>
        <span class="moga-booking-form-card__discount" id="moga-badge-discount" <?php echo $discount > 0 ? '' : 'hidden'; ?>>
            -<?php echo esc_html(intval($discount)); ?>%
        </span>
    </div>

    <?php // ---- Rating Summary ----
    ?>
    <?php if ($rating > 0) : ?>
        <div class="moga-booking-form-card__rating">
            <span class="moga-rating-score moga-rating-score--sm">
                <?php echo esc_html(number_format($rating, 1)); ?>
            </span>
            <?php if ($review_count > 0) : ?>
                <a href="#moga-reviews" class="moga-booking-form-card__rating-link">
                    <?php printf(
                        esc_html(1 === $review_count ? __('%d review', 'moga-travel') : __('%d reviews', 'moga-travel')),
                        number_format_i18n($review_count)
                    ); ?>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php // ---- Available Periods — so a guest browsing a month with
    // zero availability (e.g. August, when everything is in
    // September) knows real dates exist elsewhere, instead of
    // silently wondering why the calendar looks empty. Clicking an
    // item fills in both date fields via event delegation in
    // booking.js.
    ?>
    <?php if (! empty($pricing_periods_raw)) : ?>
        <div class="moga-available-periods" id="moga-available-periods">
            <p class="moga-available-periods__label">
                <?php esc_html_e('Available dates', 'moga-travel'); ?>
            </p>
            <?php foreach ($pricing_periods_raw as $period) :
                if (empty($period['start']) || empty($period['end']) || empty($period['price'])) {
                    continue;
                }
            ?>
                <button type="button" class="moga-available-periods__item"
                    data-start="<?php echo esc_attr($period['start']); ?>"
                    data-end="<?php echo esc_attr($period['end']); ?>">
                    <span class="moga-available-periods__dates">
                        <?php echo esc_html(moga_format_date_range($period['start'], $period['end'])); ?>
                    </span>
                    <span class="moga-available-periods__price">
                        <?php echo esc_html(moga_format_price($period['price'], $currency)); ?>
                        <?php esc_html_e('/ night', 'moga-travel'); ?>
                    </span>
                </button>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php // ---- Booking Form ----
    ?>
    <form class="moga-booking-form" id="moga-booking-form" method="POST" action="<?php echo esc_url($booking_page_url); ?>" novalidate>
        <?php wp_nonce_field('moga_booking_nonce', 'moga_booking_nonce'); ?>
        <input type="hidden" name="property_id" value="<?php echo esc_attr($property_id); ?>">
        <input type="hidden" name="listing_type" value="property">
        <input type="hidden" name="price_per_night" value="<?php echo esc_attr($price_per_night); ?>">
        <input type="hidden" name="currency" value="<?php echo esc_attr($currency); ?>">

        <?php // ---- Date Pickers ----
        ?>
        <div class="moga-booking-dates">
            <div class="moga-booking-dates__field moga-booking-dates__field--checkin">
                <label for="moga-checkin" class="moga-booking-dates__label">
                    <?php esc_html_e('Check-in', 'moga-travel'); ?>
                </label>
                <input type="text" id="moga-checkin" name="check_in" class="moga-booking-dates__input" value="<?php echo esc_attr($checkin_val); ?>" placeholder="<?php esc_attr_e('Add date', 'moga-travel'); ?>" readonly aria-required="true" autocomplete="off">
            </div>
            <div class="moga-booking-dates__arrow" aria-hidden="true">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="5" y1="12" x2="19" y2="12" />
                    <polyline points="12 5 19 12 12 19" />
                </svg>
            </div>
            <div class="moga-booking-dates__field moga-booking-dates__field--checkout">
                <label for="moga-checkout" class="moga-booking-dates__label">
                    <?php esc_html_e('Check-out', 'moga-travel'); ?>
                </label>
                <input type="text" id="moga-checkout" name="check_out" class="moga-booking-dates__input" value="<?php echo esc_attr($checkout_val); ?>" placeholder="<?php esc_attr_e('Add date', 'moga-travel'); ?>" readonly aria-required="true" autocomplete="off">
            </div>
        </div>

        <?php // ---- Dynamic Period Info — empty until a real check-in
        // date is picked, then filled in with THAT specific period's
        // check-in/out time and min/max nights by booking.js. Never a
        // static claim about the whole property — see the removed
        // House Rules lines in single-moga_property.php for why.
        ?>
        <p class="moga-booking-period-info" id="moga-booking-period-info" hidden></p>

        <?php // ---- Guest Counter ----
        ?>
        <div class="moga-booking-guests">
            <label class="moga-booking-guests__label">
                <?php esc_html_e('Guests', 'moga-travel'); ?>
            </label>
            <div class="moga-booking-guests__row">
                <button type="button" class="moga-guests-btn" id="moga-guests-minus" aria-label="<?php esc_attr_e('Remove one guest', 'moga-travel'); ?>" <?php disabled($guests_val, 1); ?>>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <line x1="5" y1="12" x2="19" y2="12" />
                    </svg>
                </button>
                <span class="moga-guests-count" id="moga-guests-display" aria-live="polite" aria-atomic="true">
                    <?php echo esc_html($guests_val); ?>
                    <?php echo esc_html(1 === $guests_val ? __('guest', 'moga-travel') : __('guests', 'moga-travel')); ?>
                </span>
                <input type="hidden" name="guests" id="moga-guests-input" value="<?php echo esc_attr($guests_val); ?>">
                <button type="button" class="moga-guests-btn" id="moga-guests-plus" aria-label="<?php esc_attr_e('Add one guest', 'moga-travel'); ?>" <?php disabled($guests_val, $max_guests); ?>>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <line x1="12" y1="5" x2="12" y2="19" />
                        <line x1="5" y1="12" x2="19" y2="12" />
                    </svg>
                </button>
            </div>
            <p class="moga-booking-guests__max">
                <?php printf(esc_html__('Maximum %d guests', 'moga-travel'), $max_guests); ?>
            </p>
        </div>

        <?php // ---- Price Breakdown — hidden until the guest actually
        // picks real dates (per explicit request: "the price should
        // be the only thing displayed... until dates have been set").
        // Only shown immediately when real dates already arrived via
        // the URL (e.g. from search results), matching how the top
        // badge and this box's own numbers are already computed above.
        ?>
        <div class="moga-price-breakdown" id="moga-price-breakdown" <?php echo ($checkin_val && $checkout_val) ? '' : 'hidden'; ?>>

            <div class="moga-price-breakdown__row">
                <span class="moga-price-breakdown__label" id="moga-nights-label">
                    <?php
                    printf(
                        /* translators: %d: number of nights */
                        esc_html(1 === $default_nights ? __('%d night', 'moga-travel') : __('%d nights', 'moga-travel')),
                        $default_nights
                    );
                    ?>
                </span>
                <span class="moga-price-breakdown__value" id="moga-breakdown-subtotal">
                    <?php echo esc_html(moga_format_price($default_subtotal, $currency)); ?>
                </span>
            </div>

            <div class="moga-price-breakdown__row moga-price-breakdown__row--discount" id="moga-breakdown-discount-row" <?php echo $discount > 0 ? '' : 'hidden'; ?>>
                <span class="moga-price-breakdown__label" id="moga-breakdown-discount-label">
                    <?php printf(esc_html__('Discount (%d%%)', 'moga-travel'), intval($discount)); ?>
                </span>
                <span class="moga-price-breakdown__value moga-price-breakdown__value--discount" id="moga-breakdown-discount">
                    &minus;<?php echo esc_html(moga_format_price($default_discount, $currency)); ?>
                </span>
            </div>

            <div class="moga-price-breakdown__row moga-price-breakdown__row--total">
                <span class="moga-price-breakdown__label moga-price-breakdown__label--total">
                    <?php esc_html_e('Total', 'moga-travel'); ?>
                </span>
                <span class="moga-price-breakdown__value moga-price-breakdown__value--total" id="moga-breakdown-total">
                    <?php echo esc_html(moga_format_price($default_total, $currency)); ?>
                </span>
            </div>

        </div>

        <?php // ---- Reserve Button ----
        ?>
        <button type="submit" class="moga-btn moga-btn--primary moga-w-100 moga-booking-form__submit" id="moga-reserve-btn">
            <?php echo '1' === $instant
                ? esc_html__('Reserve Now', 'moga-travel')
                : esc_html__('Check Availability', 'moga-travel'); ?>
        </button>

        <?php // ---- No charge notice ----
        ?>
        <p class="moga-booking-form__notice">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <circle cx="12" cy="12" r="10" />
                <line x1="12" y1="8" x2="12" y2="12" />
                <line x1="12" y1="16" x2="12.01" y2="16" />
            </svg>
            <?php esc_html_e("You won't be charged yet", 'moga-travel'); ?>
        </p>

    </form>

    <?php // ---- Meta Items ----
    ?>
    <div class="moga-booking-form-card__meta">
        <?php if ('1' === $instant) : ?>
            <div class="moga-booking-meta-item moga-booking-meta-item--success">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                    <polyline points="20 6 9 17 4 12" />
                </svg>
                <?php esc_html_e('Instant confirmation', 'moga-travel'); ?>
            </div>
        <?php endif; ?>
        <?php if ($cancel_label) : ?>
            <div class="moga-booking-meta-item">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                </svg>
                <?php echo esc_html($cancel_label); ?>
            </div>
        <?php endif; ?>
        <div class="moga-booking-meta-item">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                <path d="M7 11V7a5 5 0 0 1 10 0v4" />
            </svg>
            <?php esc_html_e('Secure payment', 'moga-travel'); ?>
        </div>
    </div>

    <?php // ---- JSON Config for booking.js ----
    ?>
    <script type="application/json" id="moga-booking-config">
        {
            "propertyId": <?php echo intval($property_id); ?>,
            "pricePerNight": <?php echo floatval($price_per_night); ?>,
            "originalPrice": <?php echo floatval($original_price); ?>,
            "discount": <?php echo floatval($discount); ?>,
            "currency": "<?php echo esc_js($currency); ?>",
            "currencySymbol": "<?php echo esc_js($currency_symbol); ?>",
            "minStay": <?php echo intval($min_stay); ?>,
            "maxStay": <?php echo intval($max_stay); ?>,
            "pricingPeriods": <?php echo wp_json_encode($pricing_periods_for_js); ?>,
            "maxGuests": <?php echo intval($max_guests); ?>,
            "checkinTime": "<?php echo esc_js($checkin_time); ?>",
            "checkoutTime": "<?php echo esc_js($checkout_time); ?>",
            "instantBooking": <?php echo '1' === $instant ? 'true' : 'false'; ?>,
            "ajaxUrl": "<?php echo esc_js(admin_url('admin-ajax.php')); ?>",
            "nonce": "<?php echo esc_js(wp_create_nonce('moga_nonce')); ?>"
        }
    </script>

</div>
