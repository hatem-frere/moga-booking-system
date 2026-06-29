<?php
/**
 * Property Booking Form
 *
 * Path: themes/moga-travel/template-parts/property/booking-form.php
 *
 * Shows price breakdown immediately on page load with 1 night default.
 * Updates live when dates or guests change.
 *
 * @package MogaTravel
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$property_id = get_the_ID();

$display_price   = function_exists( 'moga_get_property_display_price' )
    ? moga_get_property_display_price( $property_id )
    : array( 'price' => 0, 'original' => 0, 'currency' => 'USD', 'discount' => 0 );

$price_per_night = $display_price['price'];
$original_price  = $display_price['original'];
$currency        = $display_price['currency'];
$discount        = $display_price['discount'];

$min_stay      = intval( get_post_meta( $property_id, '_moga_min_stay',      true ) ) ?: 1;
$max_stay      = intval( get_post_meta( $property_id, '_moga_max_stay',      true ) );
$max_guests    = intval( get_post_meta( $property_id, '_moga_max_guests',    true ) ) ?: 10;
$checkin_time  = get_post_meta( $property_id, '_moga_checkin_time',  true ) ?: '14:00';
$checkout_time = get_post_meta( $property_id, '_moga_checkout_time', true ) ?: '11:00';
$instant       = get_post_meta( $property_id, '_moga_instant_booking', true );

$rating       = floatval( get_post_meta( $property_id, '_moga_rating',       true ) );
$review_count = intval(   get_post_meta( $property_id, '_moga_review_count', true ) );

$cancellation    = get_post_meta( $property_id, '_moga_cancellation', true ) ?: 'moderate';
$cancel_policies = class_exists( 'Moga_CPT_Property' ) ? Moga_CPT_Property::get_cancellation_policies() : array();
$cancel_label    = isset( $cancel_policies[ $cancellation ] ) ? $cancel_policies[ $cancellation ]['label'] : '';

$checkin_val  = isset( $_GET['check_in'] )  ? sanitize_text_field( wp_unslash( $_GET['check_in'] ) )  : '';
$checkout_val = isset( $_GET['check_out'] ) ? sanitize_text_field( wp_unslash( $_GET['check_out'] ) ) : '';
$guests_val   = isset( $_GET['guests'] )    ? min( max( 1, absint( $_GET['guests'] ) ), $max_guests ) : 1;

$booking_page_url = get_option( 'moga_page_booking' )
    ? get_permalink( get_option( 'moga_page_booking' ) )
    : home_url( '/booking/' );

// Default price breakdown — 1 night, no dates selected yet.
$default_nights   = 1;
$default_subtotal = $price_per_night * $default_nights;
$default_discount = $discount > 0 ? $default_subtotal * ( $discount / 100 ) : 0;
$default_total    = $default_subtotal - $default_discount;
?>

<div class="moga-booking-form-card">

    <?php // ---- Price Header ---- ?>
    <div class="moga-booking-form-card__price">
        <?php if ( $original_price > 0 && $original_price > $price_per_night ) : ?>
            <span class="moga-booking-form-card__price-old">
                <?php echo esc_html( moga_format_price( $original_price, $currency ) ); ?>
            </span>
        <?php endif; ?>
        <span class="moga-booking-form-card__price-current">
            <?php echo esc_html( moga_format_price( $price_per_night, $currency ) ); ?>
        </span>
        <span class="moga-booking-form-card__price-label">
            <?php esc_html_e( '/ night', 'moga-travel' ); ?>
        </span>
        <?php if ( $discount > 0 ) : ?>
            <span class="moga-booking-form-card__discount">
                -<?php echo esc_html( intval( $discount ) ); ?>%
            </span>
        <?php endif; ?>
    </div>

    <?php // ---- Rating Summary ---- ?>
    <?php if ( $rating > 0 ) : ?>
        <div class="moga-booking-form-card__rating">
            <span class="moga-rating-score moga-rating-score--sm">
                <?php echo esc_html( number_format( $rating, 1 ) ); ?>
            </span>
            <?php if ( $review_count > 0 ) : ?>
                <a href="#moga-reviews" class="moga-booking-form-card__rating-link">
                    <?php printf(
                        esc_html( 1 === $review_count ? __( '%d review', 'moga-travel' ) : __( '%d reviews', 'moga-travel' ) ),
                        number_format_i18n( $review_count )
                    ); ?>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php // ---- Booking Form ---- ?>
    <form class="moga-booking-form" id="moga-booking-form" method="POST" action="<?php echo esc_url( $booking_page_url ); ?>" novalidate>
        <?php wp_nonce_field( 'moga_booking_nonce', 'moga_booking_nonce' ); ?>
        <input type="hidden" name="property_id"     value="<?php echo esc_attr( $property_id ); ?>">
        <input type="hidden" name="price_per_night" value="<?php echo esc_attr( $price_per_night ); ?>">
        <input type="hidden" name="currency"        value="<?php echo esc_attr( $currency ); ?>">

        <?php // ---- Date Pickers ---- ?>
        <div class="moga-booking-dates">
            <div class="moga-booking-dates__field moga-booking-dates__field--checkin">
                <label for="moga-checkin" class="moga-booking-dates__label">
                    <?php esc_html_e( 'Check-in', 'moga-travel' ); ?>
                </label>
                <input type="text" id="moga-checkin" name="check_in" class="moga-booking-dates__input" value="<?php echo esc_attr( $checkin_val ); ?>" placeholder="<?php esc_attr_e( 'Add date', 'moga-travel' ); ?>" readonly aria-required="true" autocomplete="off">
            </div>
            <div class="moga-booking-dates__arrow" aria-hidden="true">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                    <polyline points="12 5 19 12 12 19"/>
                </svg>
            </div>
            <div class="moga-booking-dates__field moga-booking-dates__field--checkout">
                <label for="moga-checkout" class="moga-booking-dates__label">
                    <?php esc_html_e( 'Check-out', 'moga-travel' ); ?>
                </label>
                <input type="text" id="moga-checkout" name="check_out" class="moga-booking-dates__input" value="<?php echo esc_attr( $checkout_val ); ?>" placeholder="<?php esc_attr_e( 'Add date', 'moga-travel' ); ?>" readonly aria-required="true" autocomplete="off">
            </div>
        </div>

        <?php // ---- Guest Counter ---- ?>
        <div class="moga-booking-guests">
            <label class="moga-booking-guests__label">
                <?php esc_html_e( 'Guests', 'moga-travel' ); ?>
            </label>
            <div class="moga-booking-guests__row">
                <button type="button" class="moga-guests-btn" id="moga-guests-minus" aria-label="<?php esc_attr_e( 'Remove one guest', 'moga-travel' ); ?>" <?php disabled( $guests_val, 1 ); ?>>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/></svg>
                </button>
                <span class="moga-guests-count" id="moga-guests-display" aria-live="polite" aria-atomic="true">
                    <?php echo esc_html( $guests_val ); ?>
                    <?php echo esc_html( 1 === $guests_val ? __( 'guest', 'moga-travel' ) : __( 'guests', 'moga-travel' ) ); ?>
                </span>
                <input type="hidden" name="guests" id="moga-guests-input" value="<?php echo esc_attr( $guests_val ); ?>">
                <button type="button" class="moga-guests-btn" id="moga-guests-plus" aria-label="<?php esc_attr_e( 'Add one guest', 'moga-travel' ); ?>" <?php disabled( $guests_val, $max_guests ); ?>>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                </button>
            </div>
            <p class="moga-booking-guests__max">
                <?php printf( esc_html__( 'Maximum %d guests', 'moga-travel' ), $max_guests ); ?>
            </p>
        </div>

        <?php // ---- Price Breakdown — shown immediately with 1 night default ---- ?>
        <div class="moga-price-breakdown" id="moga-price-breakdown">

            <div class="moga-price-breakdown__row">
                <span class="moga-price-breakdown__label" id="moga-nights-label">
                    <?php echo esc_html( moga_format_price( $price_per_night, $currency ) ); ?>
                    &times; <?php esc_html_e( '1 night', 'moga-travel' ); ?>
                </span>
                <span class="moga-price-breakdown__value" id="moga-breakdown-subtotal">
                    <?php echo esc_html( moga_format_price( $default_subtotal, $currency ) ); ?>
                </span>
            </div>

            <?php if ( $discount > 0 ) : ?>
                <div class="moga-price-breakdown__row moga-price-breakdown__row--discount">
                    <span class="moga-price-breakdown__label">
                        <?php printf( esc_html__( 'Discount (%d%%)', 'moga-travel' ), intval( $discount ) ); ?>
                    </span>
                    <span class="moga-price-breakdown__value moga-price-breakdown__value--discount" id="moga-breakdown-discount">
                        &minus;<?php echo esc_html( moga_format_price( $default_discount, $currency ) ); ?>
                    </span>
                </div>
            <?php endif; ?>

            <div class="moga-price-breakdown__row moga-price-breakdown__row--total">
                <span class="moga-price-breakdown__label moga-price-breakdown__label--total">
                    <?php esc_html_e( 'Total', 'moga-travel' ); ?>
                </span>
                <span class="moga-price-breakdown__value moga-price-breakdown__value--total" id="moga-breakdown-total">
                    <?php echo esc_html( moga_format_price( $default_total, $currency ) ); ?>
                </span>
            </div>

        </div>

        <?php // ---- Reserve Button ---- ?>
        <button type="submit" class="moga-btn moga-btn--primary moga-w-100 moga-booking-form__submit" id="moga-reserve-btn">
            <?php echo '1' === $instant
                ? esc_html__( 'Reserve Now', 'moga-travel' )
                : esc_html__( 'Check Availability', 'moga-travel' ); ?>
        </button>

        <?php // ---- No charge notice ---- ?>
        <p class="moga-booking-form__notice">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="12"/>
                <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <?php esc_html_e( "You won't be charged yet", 'moga-travel' ); ?>
        </p>

    </form>

    <?php // ---- Meta Items ---- ?>
    <div class="moga-booking-form-card__meta">
        <?php if ( '1' === $instant ) : ?>
            <div class="moga-booking-meta-item moga-booking-meta-item--success">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                <?php esc_html_e( 'Instant confirmation', 'moga-travel' ); ?>
            </div>
        <?php endif; ?>
        <?php if ( $cancel_label ) : ?>
            <div class="moga-booking-meta-item">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                <?php echo esc_html( $cancel_label ); ?>
            </div>
        <?php endif; ?>
        <div class="moga-booking-meta-item">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            <?php esc_html_e( 'Secure payment', 'moga-travel' ); ?>
        </div>
    </div>

    <?php // ---- JSON Config for booking.js ---- ?>
    <script type="application/json" id="moga-booking-config">
    {
        "propertyId":    <?php echo intval( $property_id ); ?>,
        "pricePerNight": <?php echo floatval( $price_per_night ); ?>,
        "originalPrice": <?php echo floatval( $original_price ); ?>,
        "discount":      <?php echo floatval( $discount ); ?>,
        "currency":      "<?php echo esc_js( $currency ); ?>",
        "minStay":       <?php echo intval( $min_stay ); ?>,
        "maxStay":       <?php echo intval( $max_stay ); ?>,
        "maxGuests":     <?php echo intval( $max_guests ); ?>,
        "checkinTime":   "<?php echo esc_js( $checkin_time ); ?>",
        "checkoutTime":  "<?php echo esc_js( $checkout_time ); ?>",
        "instantBooking": <?php echo '1' === $instant ? 'true' : 'false'; ?>,
        "ajaxUrl":       "<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>",
        "nonce":         "<?php echo esc_js( wp_create_nonce( 'moga_nonce' ) ); ?>"
    }
    </script>

</div>