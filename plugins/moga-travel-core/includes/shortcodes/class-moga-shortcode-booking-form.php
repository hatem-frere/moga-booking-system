<?php

/**
 * Booking Form Shortcode — [moga_booking_form]
 *
 * Step 2 of the booking flow:
 *   Reserve (property/tour single page)
 *   -> THIS PAGE (review details + collect guest info)
 *   -> Checkout page (payment)
 *   -> Confirmation page (email sent, on-screen success)
 *
 * SHARED PAGE, PER HATEM'S EXPLICIT DESIGN: one Booking page serves
 * both property and tour bookings — the 'listing_type' field posted
 * by the Reserve button (see booking-form.php's hidden field) is the
 * marker this shortcode reads to decide what to render. Property
 * bookings show dates + guest count. Tour bookings show the seat map
 * (when the tour has a bus assigned) + guest details.
 *
 * SEAT MAP (Sep 2026 — now built): Tours with an assigned bus render
 * a full interactive seat map between the trip summary and the guest
 * details form. Seat selection is done here via AJAX (reserve_seats /
 * release_seats) before the guest proceeds to Checkout. Tours WITHOUT
 * an assigned bus skip directly to the guest details form — no seat
 * selection needed, capacity is managed via tour group limits alone.
 *
 * SECURITY: the price shown/forwarded here is ALWAYS recalculated
 * server-side via moga_calculate_property_price() — the price posted
 * by the previous page is never trusted, matching the same principle
 * already enforced in Moga_Booking::create_booking(). Availability is
 * also re-checked here, since a guest could arrive with dates that
 * became unavailable (someone else booked them) between clicking
 * Reserve and this page loading, or with tampered/stale values.
 *
 * NOT YET HANDLED HERE (deferred, by explicit agreement):
 *   - Document/ID upload — a separate, later pass
 *   - Real server-side validation of guest_name/email/phone format —
 *     belongs to the Checkout page, which actually receives this
 *     form's submission and creates the real booking record
 *
 * @package    MogaTravelCore
 * @subpackage MogaTravelCore/includes/shortcodes
 * @author     Hatem Frere
 * @since      1.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Class Moga_Shortcode_Booking_Form
 */
class Moga_Shortcode_Booking_Form
{

    /**
     * Register the shortcode.
     *
     * @since  1.0.0
     * @return void
     */
    public function register()
    {
        add_shortcode('moga_booking_form', array($this, 'render'));
    }

    /**
     * Shortcode callback.
     *
     * @since  1.0.0
     * @param  array $atts Shortcode attributes (unused).
     * @return string
     */
    public function render($atts)
    {
        // Prefer POST (arriving from the Reserve button's submit) but
        // fall back to GET, so reloading this page or navigating back
        // to it doesn't just blank it out.
        //
        // Cosmetic note: $_POST/$_GET are PHP superglobals, always
        // defined for any real web request — Intelephense's static
        // analysis can't always trace that through a ternary, hence
        // the explicit isset() checks below (purely to satisfy the
        // editor's linter; behavior is unchanged either way).
        $post_data = isset($_POST) ? $_POST : array();
        $get_data  = isset($_GET)  ? $_GET  : array();
        $source    = ! empty($post_data) ? $post_data : $get_data;

        $property_id  = isset($source['property_id'])  ? absint($source['property_id']) : 0;
        $check_in     = isset($source['check_in'])     ? sanitize_text_field(wp_unslash($source['check_in']))  : '';
        $check_out    = isset($source['check_out'])    ? sanitize_text_field(wp_unslash($source['check_out'])) : '';
        $guests       = isset($source['guests'])       ? absint($source['guests']) : 1;
        $listing_type = isset($source['listing_type']) ? sanitize_key($source['listing_type']) : 'property';

        ob_start();

        // TOUR PATH — separate from property below, since the two
        // listing types need genuinely different fields (a chosen
        // Group's date + adult/child/infant counts, not a
        // check-in/check-out range + single guest total).
        if ('tour' === $listing_type) {
            $tour_data = $this->get_validated_tour_data($source);
            if ($tour_data) {
                $this->render_tour_review_form($tour_data);
            }
            return ob_get_clean();
        }

        if (! $property_id || ! moga_is_valid_date($check_in) || ! moga_is_valid_date($check_out)) {
            $this->render_error(
                __('We couldn\'t find your booking details.', 'moga-travel'),
                __('Please go back and select your dates again.', 'moga-travel')
            );
            return ob_get_clean();
        }

        $property = get_post($property_id);
        if (! $property || 'moga_property' !== $property->post_type || 'publish' !== $property->post_status) {
            $this->render_error(
                __('This property is no longer available.', 'moga-travel'),
                __('Please go back and choose a different property.', 'moga-travel')
            );
            return ob_get_clean();
        }

        // Re-validate availability server-side — dates could have
        // become unavailable between Reserve and this page loading.
        if (! moga_is_available($property_id, $check_in, $check_out, 'property')) {
            $this->render_error(
                __('These dates are no longer available.', 'moga-travel'),
                __('Someone may have just booked them, or they\'re outside this property\'s available periods. Please go back and choose different dates.', 'moga-travel')
            );
            return ob_get_clean();
        }

        // RE-CALCULATE the price — never trust anything posted from
        // the previous page.
        $price = moga_calculate_property_price($property_id, $check_in, $check_out);

        if (empty($price['nights'])) {
            $this->render_error(
                __('We couldn\'t calculate a price for these dates.', 'moga-travel'),
                __('Please go back and try different dates.', 'moga-travel')
            );
            return ob_get_clean();
        }

        $max_guests = intval(get_post_meta($property_id, '_moga_max_guests', true)) ?: 10;
        $guests     = max(1, min($guests, $max_guests));

        $this->render_review_form($property_id, $check_in, $check_out, $guests, $price);

        return ob_get_clean();
    }

    /**
     * Render a clear, honest error state with a way back — used for
     * every "this can't proceed" case above, so a guest never lands
     * on a silently broken or blank page.
     *
     * @since  1.0.0
     * @param  string $title   Short heading.
     * @param  string $message Explanation + what to do next.
     * @return void
     */
    private function render_error($title, $message)
    {
        $search_url = get_option('moga_page_search_results')
            ? get_permalink(get_option('moga_page_search_results'))
            : home_url('/');
?>
        <div class="moga-booking-error">
            <h3><?php echo esc_html($title); ?></h3>
            <p><?php echo esc_html($message); ?></p>
            <a href="<?php echo esc_url($search_url); ?>" class="moga-btn moga-btn--primary">
                <?php esc_html_e('Back to Search', 'moga-travel'); ?>
            </a>
        </div>
    <?php
    }

    /**
     * Render the real review + guest-details page — a summary of
     * what's being booked, the server-verified price breakdown, and
     * a form collecting guest info that forwards everything to the
     * Checkout page.
     *
     * @since  1.0.0
     * @param  int    $property_id Property post ID.
     * @param  string $check_in    Y-m-d.
     * @param  string $check_out   Y-m-d.
     * @param  int    $guests      Guest count.
     * @param  array  $price       Result of moga_calculate_property_price().
     * @return void
     */
    private function render_review_form($property_id, $check_in, $check_out, $guests, $price)
    {
        $checkout_page_url = get_option('moga_page_checkout')
            ? get_permalink(get_option('moga_page_checkout'))
            : '';

        $thumbnail = get_the_post_thumbnail_url($property_id, 'moga-card');
        $title     = get_the_title($property_id);
        $city      = get_post_meta($property_id, '_moga_city',         true);
        $country   = get_post_meta($property_id, '_moga_country_name', true);
        $location  = implode(', ', array_filter(array($city, $country)));

        $current_user  = wp_get_current_user();
        $prefill_name  = $current_user->exists() ? $current_user->display_name : '';
        $prefill_email = $current_user->exists() ? $current_user->user_email   : '';
    ?>
        <div class="moga-booking-review">

            <div class="moga-booking-review__summary">
                <?php if ($thumbnail) : ?>
                    <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php echo esc_attr($title); ?>" class="moga-booking-review__image">
                <?php endif; ?>
                <div class="moga-booking-review__details">
                    <h3><?php echo esc_html($title); ?></h3>
                    <?php if ($location) : ?>
                        <p class="moga-booking-review__location"><?php echo esc_html($location); ?></p>
                    <?php endif; ?>
                    <div class="moga-booking-review__dates">
                        <span><strong><?php esc_html_e('Check-in:', 'moga-travel'); ?></strong> <?php echo esc_html(moga_format_date_human($check_in)); ?></span>
                        <span><strong><?php esc_html_e('Check-out:', 'moga-travel'); ?></strong> <?php echo esc_html(moga_format_date_human($check_out)); ?></span>
                        <span><strong><?php esc_html_e('Guests:', 'moga-travel'); ?></strong> <?php echo esc_html($guests); ?></span>
                    </div>
                </div>
            </div>

            <div class="moga-booking-review__price">
                <div class="moga-price-breakdown__row">
                    <span>
                        <?php echo esc_html($price['nights']); ?>
                        <?php echo esc_html(1 === $price['nights'] ? __('night', 'moga-travel') : __('nights', 'moga-travel')); ?>
                    </span>
                    <span><?php echo esc_html(moga_format_price($price['subtotal'], $price['currency'])); ?></span>
                </div>
                <?php if ($price['discount'] > 0) : ?>
                    <div class="moga-price-breakdown__row moga-price-breakdown__row--discount">
                        <span><?php printf(esc_html__('Discount (%d%%)', 'moga-travel'), intval($price['discount_percent'])); ?></span>
                        <span>&minus;<?php echo esc_html(moga_format_price($price['discount'], $price['currency'])); ?></span>
                    </div>
                <?php endif; ?>
                <div class="moga-price-breakdown__row moga-price-breakdown__row--total">
                    <strong><?php esc_html_e('Total', 'moga-travel'); ?></strong>
                    <strong><?php echo esc_html(moga_format_price($price['total'], $price['currency'])); ?></strong>
                </div>
            </div>

            <form method="POST" action="<?php echo esc_url($checkout_page_url); ?>" class="moga-booking-review__form">
                <?php wp_nonce_field('moga_booking_review', 'moga_booking_review_nonce'); ?>

                <input type="hidden" name="property_id" value="<?php echo esc_attr($property_id); ?>">
                <input type="hidden" name="check_in" value="<?php echo esc_attr($check_in); ?>">
                <input type="hidden" name="check_out" value="<?php echo esc_attr($check_out); ?>">
                <input type="hidden" name="guests" value="<?php echo esc_attr($guests); ?>">
                <input type="hidden" name="listing_type" value="property">

                <h4><?php esc_html_e('Your Details', 'moga-travel'); ?></h4>

                <div class="moga-checkout-field">
                    <label for="moga-guest-name">
                        <?php esc_html_e('Full Name', 'moga-travel'); ?> <span class="required">*</span>
                    </label>
                    <input type="text" id="moga-guest-name" name="guest_name" value="<?php echo esc_attr($prefill_name); ?>" required>
                </div>

                <div class="moga-checkout-field">
                    <label for="moga-guest-email">
                        <?php esc_html_e('Email', 'moga-travel'); ?> <span class="required">*</span>
                    </label>
                    <input type="email" id="moga-guest-email" name="guest_email" value="<?php echo esc_attr($prefill_email); ?>" required>
                </div>

                <div class="moga-checkout-field">
                    <label for="moga-guest-phone">
                        <?php esc_html_e('Phone', 'moga-travel'); ?> <span class="required">*</span>
                    </label>
                    <input type="tel" id="moga-guest-phone" name="guest_phone" required>
                </div>

                <div class="moga-checkout-field">
                    <label for="moga-guest-notes">
                        <?php esc_html_e('Special Requests (optional)', 'moga-travel'); ?>
                    </label>
                    <textarea id="moga-guest-notes" name="guest_notes" rows="3"></textarea>
                </div>

                <button type="submit" class="moga-btn moga-btn--primary moga-w-100">
                    <?php esc_html_e('Continue to Payment', 'moga-travel'); ?>
                </button>
            </form>

        </div>
<?php
    }

    /**
     * Read, sanitize, and fully re-validate a tour booking request —
     * the tour equivalent of the property validation above. Renders
     * an error and returns false the moment anything fails.
     *
     * BUS PATH (Sep 2026): tours with an assigned bus now pass through
     * normally — bus_id is included in the returned data so
     * render_tour_review_form() can render the seat map section.
     * The old "seat selection isn't available yet" gate is removed.
     *
     * @since  1.0.0
     * @param  array $source $_POST or $_GET, whichever arrived.
     * @return array|false
     */
    private function get_validated_tour_data($source)
    {
        $tour_id   = isset($source['tour_id'])   ? absint($source['tour_id']) : 0;
        $tour_date = isset($source['tour_date']) ? sanitize_text_field(wp_unslash($source['tour_date'])) : '';
        $adults    = isset($source['adults'])    ? max(1, absint($source['adults'])) : 1;
        $children  = isset($source['children'])  ? absint($source['children']) : 0;
        $infants   = isset($source['infants'])   ? absint($source['infants']) : 0;

        if (! $tour_id || ! moga_is_valid_date($tour_date)) {
            $this->render_error(
                __('We couldn\'t find your booking details.', 'moga-travel'),
                __('Please go back and select a departure again.', 'moga-travel')
            );
            return false;
        }

        $tour = get_post($tour_id);
        if (! $tour || 'moga_tour' !== $tour->post_type || 'publish' !== $tour->post_status) {
            $this->render_error(
                __('This tour is no longer available.', 'moga-travel'),
                __('Please go back and choose a different tour.', 'moga-travel')
            );
            return false;
        }

        // Resolve bus assignment for this tour — included in the
        // returned data so render_tour_review_form() can show the
        // seat map when a bus is assigned.
        $bus_id = intval(get_post_meta($tour_id, '_moga_bus_id', true));

        // Re-check real, live capacity — dates could have filled up
        // between Reserve and this page loading.
        if (! function_exists('moga_is_tour_group_available') || ! moga_is_tour_group_available($tour_id, $tour_date, $adults + $children)) {
            $this->render_error(
                __('This departure is no longer available.', 'moga-travel'),
                __('It may have sold out or closed for booking. Please go back and choose a different departure.', 'moga-travel')
            );
            return false;
        }

        // RE-CALCULATE the price — never trust anything posted from
        // the previous page.
        $price = function_exists('moga_calculate_tour_price')
            ? moga_calculate_tour_price($tour_id, $tour_date, $adults, $children, $infants)
            : array();

        if (empty($price['group_found']) || empty($price['total'])) {
            $this->render_error(
                __('We couldn\'t calculate a price for this departure.', 'moga-travel'),
                __('Please go back and try again.', 'moga-travel')
            );
            return false;
        }

        return array(
            'tour_id'   => $tour_id,
            'tour_date' => $tour_date,
            'adults'    => $adults,
            'children'  => $children,
            'infants'   => $infants,
            'price'     => $price,
            'bus_id'    => $bus_id, // 0 when no bus assigned — seat map section skipped.
        );
    }

    /**
     * Render the tour review + guest-details page — mirrors
     * render_review_form()'s structure, adapted for a departure date
     * and adult/child/infant counts instead of a date range and a
     * single guest total.
     *
     * When $data['bus_id'] > 0, a full seat map section is rendered
     * between the trip summary and the guest details form. The guest
     * must select the required number of seats before the form
     * submits — enforced in JS by disabling the submit button until
     * seat selection is complete.
     *
     * @since  1.0.0
     * @param  array $data Result of get_validated_tour_data().
     * @return void
     */
    private function render_tour_review_form($data)
    {
        $checkout_page_url = get_option('moga_page_checkout')
            ? get_permalink(get_option('moga_page_checkout'))
            : '';

        $thumbnail = get_the_post_thumbnail_url($data['tour_id'], 'moga-card');
        $title     = get_the_title($data['tour_id']);
        $price     = $data['price'];
        $bus_id    = $data['bus_id'];

        // Required seat count = adults + children (infants don't
        // occupy a seat — matches moga_get_tour_group_seats_taken()).
        $required_seats = $data['adults'] + $data['children'];

        $current_user  = wp_get_current_user();
        $prefill_name  = $current_user->exists() ? $current_user->display_name : '';
        $prefill_email = $current_user->exists() ? $current_user->user_email   : '';

        $nonce     = wp_create_nonce('moga_nonce');
        $ajax_url  = admin_url('admin-ajax.php');
    ?>
        <div class="moga-booking-review">

            <?php // ---- Step indicator ---- ?>
            <div class="moga-booking-steps">
                <div class="moga-booking-step moga-booking-step--done">
                    <span class="moga-booking-step__num">1</span>
                    <span class="moga-booking-step__label"><?php esc_html_e('Tour Selected', 'moga-travel'); ?></span>
                </div>
                <div class="moga-booking-step moga-booking-step--active">
                    <span class="moga-booking-step__num">2</span>
                    <span class="moga-booking-step__label"><?php esc_html_e('Review &amp; Details', 'moga-travel'); ?></span>
                </div>
                <div class="moga-booking-step">
                    <span class="moga-booking-step__num">3</span>
                    <span class="moga-booking-step__label"><?php esc_html_e('Payment', 'moga-travel'); ?></span>
                </div>
            </div>

            <?php // ---- Trip Summary ---- ?>
            <div class="moga-booking-review__summary">
                <?php if ($thumbnail) : ?>
                    <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php echo esc_attr($title); ?>" class="moga-booking-review__image">
                <?php endif; ?>
                <div class="moga-booking-review__details">
                    <h3><?php echo esc_html($title); ?></h3>
                    <div class="moga-booking-review__dates">
                        <span><strong><?php esc_html_e('Departure:', 'moga-travel'); ?></strong> <?php echo esc_html(moga_format_date_human($data['tour_date'])); ?></span>
                        <span><strong><?php esc_html_e('Adults:', 'moga-travel'); ?></strong> <?php echo esc_html($data['adults']); ?></span>
                        <?php if ($data['children'] > 0) : ?>
                            <span><strong><?php esc_html_e('Children:', 'moga-travel'); ?></strong> <?php echo esc_html($data['children']); ?></span>
                        <?php endif; ?>
                        <?php if ($data['infants'] > 0) : ?>
                            <span><strong><?php esc_html_e('Infants:', 'moga-travel'); ?></strong> <?php echo esc_html($data['infants']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php // ---- Price Breakdown ---- ?>
            <div class="moga-booking-review__price">
                <?php if ($data['adults'] > 0) : ?>
                    <div class="moga-price-breakdown__row">
                        <span><?php printf(esc_html(1 === $data['adults'] ? __('%d Adult', 'moga-travel') : __('%d Adults', 'moga-travel')), $data['adults']); ?></span>
                        <span><?php echo esc_html(moga_format_price($price['adults_total'], $price['currency'])); ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($data['children'] > 0) : ?>
                    <div class="moga-price-breakdown__row">
                        <span><?php printf(esc_html(1 === $data['children'] ? __('%d Child', 'moga-travel') : __('%d Children', 'moga-travel')), $data['children']); ?></span>
                        <span><?php echo esc_html(moga_format_price($price['children_total'], $price['currency'])); ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($data['infants'] > 0) : ?>
                    <div class="moga-price-breakdown__row">
                        <span><?php printf(esc_html(1 === $data['infants'] ? __('%d Infant', 'moga-travel') : __('%d Infants', 'moga-travel')), $data['infants']); ?></span>
                        <span><?php echo esc_html(moga_format_price($price['infants_total'], $price['currency'])); ?></span>
                    </div>
                <?php endif; ?>
                <div class="moga-price-breakdown__row moga-price-breakdown__row--total">
                    <strong><?php esc_html_e('Total', 'moga-travel'); ?></strong>
                    <strong><?php echo esc_html(moga_format_price($price['total'], $price['currency'])); ?></strong>
                </div>
            </div>

            <?php // ---- Seat Map Section (bus tours only) ----
            // Rendered when this tour has a bus assigned. The seat map
            // is fetched via AJAX on page load — this block is just the
            // container and loading state. JS (initSeatMap in booking.js)
            // populates the real grid once the AJAX call returns.
            // ---- ?>
            <?php if ($bus_id > 0) : ?>
                <?php $this->render_seat_map_section($bus_id, $data['tour_id'], $data['tour_date'], $required_seats, $nonce, $ajax_url); ?>
            <?php endif; ?>

            <?php // ---- Guest Details Form ---- ?>
            <form method="POST" action="<?php echo esc_url($checkout_page_url); ?>" class="moga-booking-review__form" id="moga-tour-review-form">
                <?php wp_nonce_field('moga_booking_review', 'moga_booking_review_nonce'); ?>

                <input type="hidden" name="tour_id"       value="<?php echo esc_attr($data['tour_id']); ?>">
                <input type="hidden" name="tour_date"     value="<?php echo esc_attr($data['tour_date']); ?>">
                <input type="hidden" name="adults"        value="<?php echo esc_attr($data['adults']); ?>">
                <input type="hidden" name="children"      value="<?php echo esc_attr($data['children']); ?>">
                <input type="hidden" name="infants"       value="<?php echo esc_attr($data['infants']); ?>">
                <input type="hidden" name="listing_type"  value="tour">

                <?php if ($bus_id > 0) : ?>
                    <?php // Selected seat numbers are written here by JS before submit. ?>
                    <input type="hidden" name="bus_id"           id="moga-seat-bus-id-field"      value="<?php echo esc_attr($bus_id); ?>">
                    <input type="hidden" name="selected_seats"   id="moga-selected-seats-field"   value="">
                    <input type="hidden" name="seat_session_token" id="moga-seat-session-field"   value="">
                <?php endif; ?>

                <h4><?php esc_html_e('Your Details', 'moga-travel'); ?></h4>

                <div class="moga-checkout-field">
                    <label for="moga-guest-name">
                        <?php esc_html_e('Full Name', 'moga-travel'); ?> <span class="required">*</span>
                    </label>
                    <input type="text" id="moga-guest-name" name="guest_name" value="<?php echo esc_attr($prefill_name); ?>" required>
                </div>

                <div class="moga-checkout-field">
                    <label for="moga-guest-email">
                        <?php esc_html_e('Email', 'moga-travel'); ?> <span class="required">*</span>
                    </label>
                    <input type="email" id="moga-guest-email" name="guest_email" value="<?php echo esc_attr($prefill_email); ?>" required>
                </div>

                <div class="moga-checkout-field">
                    <label for="moga-guest-phone">
                        <?php esc_html_e('Phone', 'moga-travel'); ?> <span class="required">*</span>
                    </label>
                    <input type="tel" id="moga-guest-phone" name="guest_phone" required>
                </div>

                <div class="moga-checkout-field">
                    <label for="moga-guest-notes">
                        <?php esc_html_e('Special Requests (optional)', 'moga-travel'); ?>
                    </label>
                    <textarea id="moga-guest-notes" name="guest_notes" rows="3"></textarea>
                </div>

                <button type="submit" class="moga-btn moga-btn--primary moga-w-100" id="moga-tour-review-submit"
                    <?php echo ($bus_id > 0) ? 'disabled' : ''; ?>>
                    <?php esc_html_e('Continue to Payment', 'moga-travel'); ?>
                </button>

                <?php if ($bus_id > 0) : ?>
                    <p class="moga-booking-form__notice" id="moga-seat-required-notice">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="12" y1="8" x2="12" y2="12" />
                            <line x1="12" y1="16" x2="12.01" y2="16" />
                        </svg>
                        <?php printf(
                            esc_html(
                                1 === $required_seats
                                    ? __('Please select %d seat to continue.', 'moga-travel')
                                    : __('Please select %d seats to continue.', 'moga-travel')
                            ),
                            $required_seats
                        ); ?>
                    </p>
                <?php endif; ?>

            </form>

        </div>

        <?php // ---- Seat map config for booking.js ---- ?>
        <?php if ($bus_id > 0) : ?>
        <script type="application/json" id="moga-seat-map-config">
        {
            "busId":         <?php echo intval($bus_id); ?>,
            "tourId":        <?php echo intval($data['tour_id']); ?>,
            "tripDate":      "<?php echo esc_js($data['tour_date']); ?>",
            "requiredSeats": <?php echo intval($required_seats); ?>,
            "ajaxUrl":       "<?php echo esc_js($ajax_url); ?>",
            "nonce":         "<?php echo esc_js($nonce); ?>"
        }
        </script>
        <?php endif; ?>
<?php
    }

    /**
     * Render the seat map section container.
     * JS (initSeatMap in booking.js) fetches the real seat data via
     * AJAX and populates this container. The PHP side only renders the
     * skeleton, the legend, the hold countdown, and the loading state —
     * never the actual seat grid, which changes in real time.
     *
     * @since  1.0.0
     * @param  int    $bus_id         Bus post ID.
     * @param  int    $tour_id        Tour post ID.
     * @param  string $trip_date      Y-m-d.
     * @param  int    $required_seats Number of seats the guest must select.
     * @param  string $nonce          moga_nonce value.
     * @param  string $ajax_url       admin-ajax.php URL.
     * @return void
     */
    private function render_seat_map_section($bus_id, $tour_id, $trip_date, $required_seats, $nonce, $ajax_url)
    {
        $bus      = get_post($bus_id);
        $bus_name = $bus ? $bus->post_title : __('Bus', 'moga-travel');
        $bus_type = get_post_meta($bus_id, '_moga_bus_type', true) ?: 'standard';
        $bus_types = class_exists('Moga_CPT_Bus') ? Moga_CPT_Bus::get_bus_types() : array();
        $bus_type_label = isset($bus_types[$bus_type]['label']) ? $bus_types[$bus_type]['label'] : '';
    ?>
        <div class="moga-seat-map-section" id="moga-seat-map-section">

            <div class="moga-seat-map-section__header">
                <h4>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <rect x="3" y="11" width="18" height="7" rx="2"/>
                        <path d="M5 11V7a7 7 0 0 1 14 0v4"/>
                        <line x1="8" y1="11" x2="8" y2="15"/>
                        <line x1="16" y1="11" x2="16" y2="15"/>
                    </svg>
                    <?php esc_html_e('Choose Your Seats', 'moga-travel'); ?>
                </h4>
                <p class="moga-seat-map-section__bus-name">
                    <?php echo esc_html($bus_name); ?>
                    <?php if ($bus_type_label) : ?>
                        <span class="moga-seat-map-section__bus-type">&mdash; <?php echo esc_html($bus_type_label); ?></span>
                    <?php endif; ?>
                </p>
            </div>

            <?php // ---- Hold countdown — hidden until seats are reserved ---- ?>
            <div class="moga-seat-hold-timer" id="moga-seat-hold-timer" hidden>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
                </svg>
                <?php esc_html_e('Seats held for', 'moga-travel'); ?>
                <strong id="moga-seat-hold-countdown">15:00</strong>
            </div>

            <?php // ---- Selection progress ---- ?>
            <div class="moga-seat-selection-status" id="moga-seat-selection-status">
                <span id="moga-seats-selected-count">0</span>
                <?php printf(
                    esc_html__('of %d seats selected', 'moga-travel'),
                    $required_seats
                ); ?>
                <span id="moga-selected-seat-labels" class="moga-seat-labels"></span>
            </div>

            <?php // ---- Seat map grid — populated by JS ---- ?>
            <div class="moga-seat-map-loading" id="moga-seat-map-loading">
                <span class="moga-spinner" aria-hidden="true"></span>
                <?php esc_html_e('Loading seat map...', 'moga-travel'); ?>
            </div>

            <div class="moga-seat-map" id="moga-seat-map" hidden aria-label="<?php esc_attr_e('Bus seat map', 'moga-travel'); ?>">
                <?php // JS renders rows here. ?>
            </div>

            <?php // ---- Seat legend ---- ?>
            <div class="moga-seat-legend">
                <span class="moga-seat-legend__item">
                    <span class="moga-seat moga-seat--available moga-seat--legend" aria-hidden="true"></span>
                    <?php esc_html_e('Available', 'moga-travel'); ?>
                </span>
                <span class="moga-seat-legend__item">
                    <span class="moga-seat moga-seat--selected moga-seat--legend" aria-hidden="true"></span>
                    <?php esc_html_e('Selected', 'moga-travel'); ?>
                </span>
                <span class="moga-seat-legend__item">
                    <span class="moga-seat moga-seat--taken moga-seat--legend" aria-hidden="true"></span>
                    <?php esc_html_e('Taken', 'moga-travel'); ?>
                </span>
                <span class="moga-seat-legend__item">
                    <span class="moga-seat moga-seat--vip moga-seat--legend" aria-hidden="true"></span>
                    <?php esc_html_e('VIP', 'moga-travel'); ?>
                </span>
            </div>

        </div>
<?php
    }
}
