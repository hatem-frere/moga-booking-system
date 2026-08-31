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
 * bookings show dates + guest count. Tour bookings would additionally
 * show the bus seat map — NOT YET BUILT (class-moga-seat-map.php is
 * still empty), so 'tour' currently shows an honest "not available
 * yet" message rather than a broken form.
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

        if (! $property_id || ! moga_is_valid_date($check_in) || ! moga_is_valid_date($check_out)) {
            $this->render_error(
                __('We couldn\'t find your booking details.', 'moga-travel'),
                __('Please go back and select your dates again.', 'moga-travel')
            );
            return ob_get_clean();
        }

        if ('tour' === $listing_type) {
            // Tour bookings need the seat map (class-moga-seat-map.php
            // is still empty) — an honest message, not a broken form.
            $this->render_error(
                __('Tour bookings aren\'t available yet.', 'moga-travel'),
                __('We\'re still building this. Please check back soon.', 'moga-travel')
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
}
