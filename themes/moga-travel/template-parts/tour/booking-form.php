<?php
/**
 * Tour Booking Form
 *
 * Path: themes/moga-travel/template-parts/tour/booking-form.php
 *
 * Shows price breakdown immediately on load with 1 adult default.
 * Updates live when participant counts change.
 * Tour date is restricted to '_moga_available_days' (weekday whitelist)
 * and, if set, further restricted to the exact dates in '_moga_start_dates'.
 *
 * @package MogaTravel
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$tour_id = get_the_ID();

$display_price = function_exists( 'moga_get_tour_display_price' )
    ? moga_get_tour_display_price( $tour_id )
    : array( 'price' => 0, 'original' => 0, 'currency' => 'USD', 'discount' => 0 );

$price_per_person = $display_price['price'];
$original_price   = $display_price['original'];
$currency         = $display_price['currency'];
$group_discount   = $display_price['discount'];

$price_child   = floatval( get_post_meta( $tour_id, '_moga_price_child',  true ) );
$price_infant  = floatval( get_post_meta( $tour_id, '_moga_price_infant', true ) );

$max_participants = intval( get_post_meta( $tour_id, '_moga_max_participants', true ) ) ?: 20;
$min_participants  = intval( get_post_meta( $tour_id, '_moga_min_participants', true ) ) ?: 1;

$available_days_json = get_post_meta( $tour_id, '_moga_available_days', true );
$available_days      = $available_days_json ? json_decode( $available_days_json, true ) : array();
$available_days      = is_array( $available_days ) ? array_map( 'intval', $available_days ) : array();

$start_dates_json = get_post_meta( $tour_id, '_moga_start_dates', true );
$start_dates      = $start_dates_json ? json_decode( $start_dates_json, true ) : array();
$start_dates      = is_array( $start_dates ) ? array_values( array_filter( $start_dates ) ) : array();

$duration_days   = intval( get_post_meta( $tour_id, '_moga_duration_days',   true ) ) ?: 1;
$duration_nights = intval( get_post_meta( $tour_id, '_moga_duration_nights', true ) );

$instant = get_post_meta( $tour_id, '_moga_instant_booking', true );

$rating       = floatval( get_post_meta( $tour_id, '_moga_rating',       true ) );
$review_count = intval(   get_post_meta( $tour_id, '_moga_review_count', true ) );

$cancellation    = get_post_meta( $tour_id, '_moga_cancellation', true ) ?: 'moderate';
$cancel_policies = class_exists( 'Moga_CPT_Tour' ) ? Moga_CPT_Tour::get_cancellation_policies() : array();
$cancel_label    = isset( $cancel_policies[ $cancellation ] ) ? $cancel_policies[ $cancellation ]['label'] : '';

$date_val     = isset( $_GET['tour_date'] ) ? sanitize_text_field( wp_unslash( $_GET['tour_date'] ) ) : '';
$adults_val   = isset( $_GET['adults'] )   ? max( 1, absint( $_GET['adults'] ) )   : 1;
$children_val = isset( $_GET['children'] ) ? max( 0, absint( $_GET['children'] ) ) : 0;
$infants_val  = isset( $_GET['infants'] )  ? max( 0, absint( $_GET['infants'] ) )  : 0;

$booking_page_url = get_option( 'moga_page_booking' )
    ? get_permalink( get_option( 'moga_page_booking' ) )
    : home_url( '/booking/' );

// Default price breakdown — 1 adult, no children/infants.
$default_price = function_exists( 'moga_calculate_tour_price' )
    ? moga_calculate_tour_price( $tour_id, 1, 0, 0 )
    : array( 'subtotal' => $price_per_person, 'discount' => 0, 'total' => $price_per_person );
?>

<div class="moga-booking-form-card">

    <?php // ---- Price Header ---- ?>
    <div class="moga-booking-form-card__price">
        <?php if ( $original_price > 0 && $original_price > $price_per_person ) : ?>
            <span class="moga-booking-form-card__price-old">
                <?php echo esc_html( moga_format_price( $original_price, $currency ) ); ?>
            </span>
        <?php endif; ?>
        <span class="moga-booking-form-card__price-current">
            <?php echo esc_html( moga_format_price( $price_per_person, $currency ) ); ?>
        </span>
        <span class="moga-booking-form-card__price-label">
            <?php esc_html_e( '/ person', 'moga-travel' ); ?>
        </span>
        <?php if ( $group_discount > 0 ) : ?>
            <span class="moga-booking-form-card__discount">
                -<?php echo esc_html( intval( $group_discount ) ); ?>%
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
    <form class="moga-booking-form" id="moga-tour-booking-form" method="POST" action="<?php echo esc_url( $booking_page_url ); ?>" novalidate>
        <?php wp_nonce_field( 'moga_booking_nonce', 'moga_booking_nonce' ); ?>
        <input type="hidden" name="tour_id"          value="<?php echo esc_attr( $tour_id ); ?>">
        <input type="hidden" name="price_per_person"  value="<?php echo esc_attr( $price_per_person ); ?>">
        <input type="hidden" name="currency"          value="<?php echo esc_attr( $currency ); ?>">

        <?php // ---- Tour Date Picker ---- ?>
        <div class="moga-booking-dates moga-booking-dates--single">
            <div class="moga-booking-dates__field">
                <label for="moga-tour-date" class="moga-booking-dates__label">
                    <?php esc_html_e( 'Tour date', 'moga-travel' ); ?>
                </label>
                <input type="text" id="moga-tour-date" name="tour_date" class="moga-booking-dates__input" value="<?php echo esc_attr( $date_val ); ?>" placeholder="<?php esc_attr_e( 'Select a date', 'moga-travel' ); ?>" readonly aria-required="true" autocomplete="off">
            </div>
        </div>

        <?php // ---- Participant Counters ---- ?>
        <div class="moga-participants" id="moga-participants">

            <div class="moga-participant-row" data-type="adults">
                <div class="moga-participant-row__info">
                    <span class="moga-participant-row__label"><?php esc_html_e( 'Adults', 'moga-travel' ); ?></span>
                    <span class="moga-participant-row__price"><?php echo esc_html( moga_format_price( $price_per_person, $currency ) ); ?></span>
                </div>
                <div class="moga-participant-row__controls">
                    <button type="button" class="moga-participant-btn" id="moga-adults-minus" aria-label="<?php esc_attr_e( 'Remove one adult', 'moga-travel' ); ?>">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    </button>
                    <span class="moga-participant-count" id="moga-adults-display" aria-live="polite" aria-atomic="true"><?php echo esc_html( $adults_val ); ?></span>
                    <input type="hidden" name="adults" id="moga-adults-input" value="<?php echo esc_attr( $adults_val ); ?>">
                    <button type="button" class="moga-participant-btn" id="moga-adults-plus" aria-label="<?php esc_attr_e( 'Add one adult', 'moga-travel' ); ?>">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    </button>
                </div>
            </div>

            <?php if ( $price_child > 0 || true ) : // children counter always shown, price may be 0 (free) ?>
            <div class="moga-participant-row" data-type="children">
                <div class="moga-participant-row__info">
                    <span class="moga-participant-row__label"><?php esc_html_e( 'Children', 'moga-travel' ); ?></span>
                    <span class="moga-participant-row__price"><?php echo esc_html( moga_format_price( $price_child, $currency ) ); ?></span>
                </div>
                <div class="moga-participant-row__controls">
                    <button type="button" class="moga-participant-btn" id="moga-children-minus" aria-label="<?php esc_attr_e( 'Remove one child', 'moga-travel' ); ?>" disabled>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    </button>
                    <span class="moga-participant-count" id="moga-children-display" aria-live="polite" aria-atomic="true"><?php echo esc_html( $children_val ); ?></span>
                    <input type="hidden" name="children" id="moga-children-input" value="<?php echo esc_attr( $children_val ); ?>">
                    <button type="button" class="moga-participant-btn" id="moga-children-plus" aria-label="<?php esc_attr_e( 'Add one child', 'moga-travel' ); ?>">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    </button>
                </div>
            </div>
            <?php endif; ?>

            <div class="moga-participant-row" data-type="infants">
                <div class="moga-participant-row__info">
                    <span class="moga-participant-row__label"><?php esc_html_e( 'Infants', 'moga-travel' ); ?></span>
                    <span class="moga-participant-row__price"><?php echo $price_infant > 0 ? esc_html( moga_format_price( $price_infant, $currency ) ) : esc_html__( 'Free', 'moga-travel' ); ?></span>
                </div>
                <div class="moga-participant-row__controls">
                    <button type="button" class="moga-participant-btn" id="moga-infants-minus" aria-label="<?php esc_attr_e( 'Remove one infant', 'moga-travel' ); ?>" disabled>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    </button>
                    <span class="moga-participant-count" id="moga-infants-display" aria-live="polite" aria-atomic="true"><?php echo esc_html( $infants_val ); ?></span>
                    <input type="hidden" name="infants" id="moga-infants-input" value="<?php echo esc_attr( $infants_val ); ?>">
                    <button type="button" class="moga-participant-btn" id="moga-infants-plus" aria-label="<?php esc_attr_e( 'Add one infant', 'moga-travel' ); ?>">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    </button>
                </div>
            </div>

            <p class="moga-participants__max">
                <?php printf( esc_html__( 'Maximum %d participants', 'moga-travel' ), $max_participants ); ?>
                <?php if ( $min_participants > 1 ) : ?>
                    &middot; <?php printf( esc_html__( 'minimum %d to run', 'moga-travel' ), $min_participants ); ?>
                <?php endif; ?>
            </p>
        </div>

        <?php // ---- Price Breakdown — shown immediately with 1 adult default ---- ?>
        <div class="moga-price-breakdown" id="moga-price-breakdown">

            <div class="moga-price-breakdown__row">
                <span class="moga-price-breakdown__label" id="moga-participants-label">
                    <?php echo esc_html( moga_format_price( $price_per_person, $currency ) ); ?>
                    &times; <?php esc_html_e( '1 adult', 'moga-travel' ); ?>
                </span>
                <span class="moga-price-breakdown__value" id="moga-breakdown-subtotal">
                    <?php echo esc_html( moga_format_price( $default_price['subtotal'], $currency ) ); ?>
                </span>
            </div>

            <?php if ( $default_price['discount'] > 0 ) : ?>
                <div class="moga-price-breakdown__row moga-price-breakdown__row--discount">
                    <span class="moga-price-breakdown__label">
                        <?php printf( esc_html__( 'Group discount (%d%%)', 'moga-travel' ), intval( $group_discount ) ); ?>
                    </span>
                    <span class="moga-price-breakdown__value moga-price-breakdown__value--discount" id="moga-breakdown-discount">
                        &minus;<?php echo esc_html( moga_format_price( $default_price['discount'], $currency ) ); ?>
                    </span>
                </div>
            <?php endif; ?>

            <div class="moga-price-breakdown__row moga-price-breakdown__row--total">
                <span class="moga-price-breakdown__label moga-price-breakdown__label--total">
                    <?php esc_html_e( 'Total', 'moga-travel' ); ?>
                </span>
                <span class="moga-price-breakdown__value moga-price-breakdown__value--total" id="moga-breakdown-total">
                    <?php echo esc_html( moga_format_price( $default_price['total'], $currency ) ); ?>
                </span>
            </div>

        </div>

        <?php // ---- Reserve Button ---- ?>
        <button type="submit" class="moga-btn moga-btn--primary moga-w-100 moga-booking-form__submit" id="moga-tour-reserve-btn">
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
    <script type="application/json" id="moga-tour-booking-config">
    {
        "tourId":           <?php echo intval( $tour_id ); ?>,
        "pricePerPerson":   <?php echo floatval( $price_per_person ); ?>,
        "priceChild":       <?php echo floatval( $price_child ); ?>,
        "priceInfant":      <?php echo floatval( $price_infant ); ?>,
        "originalPrice":    <?php echo floatval( $original_price ); ?>,
        "groupDiscount":    <?php echo floatval( $group_discount ); ?>,
        "currency":         "<?php echo esc_js( $currency ); ?>",
        "minParticipants":  <?php echo intval( $min_participants ); ?>,
        "maxParticipants":  <?php echo intval( $max_participants ); ?>,
        "availableDays":    <?php echo wp_json_encode( array_values( $available_days ) ); ?>,
        "startDates":       <?php echo wp_json_encode( array_values( $start_dates ) ); ?>,
        "durationDays":     <?php echo intval( $duration_days ); ?>,
        "durationNights":   <?php echo intval( $duration_nights ); ?>,
        "instantBooking":   <?php echo '1' === $instant ? 'true' : 'false'; ?>,
        "ajaxUrl":          "<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>",
        "nonce":            "<?php echo esc_js( wp_create_nonce( 'moga_nonce' ) ); ?>"
    }
    </script>

</div>