<?php

/**
 * Checkout Shortcode — [moga_checkout]
 *
 * Step 3 of the booking flow:
 *   Booking page (review + guest details)
 *   -> THIS PAGE (validate guest details, choose payment, confirm)
 *   -> Confirmation page (email sent, on-screen success)
 *
 * TWO STATES ON THIS SAME PAGE:
 *   1. ARRIVAL — guest just submitted the Booking page's form. Shows
 *      the summary again, the real server-verified price, and a
 *      payment-method choice (full vs deposit, if this listing
 *      offers one). Also where guest_name/email/phone finally get
 *      REAL validation — the Booking page deliberately deferred
 *      that here.
 *   2. CONFIRM — guest clicked "Confirm Booking" on THIS page's own
 *      form (distinguished by a 'moga_action=confirm' field). This
 *      is where the real booking record actually gets created —
 *      not before. Everything is re-validated again here too, never
 *      trusting even this second round of posted data.
 *
 * GUEST ACCOUNTS: Moga_Booking::create_booking() requires a real
 * WordPress user ID (guest_id) — there's no guest-name/email/phone
 * column on the bookings table at all. Rather than force login
 * before checkout (real friction, unlike Booking.com/Airbnb's actual
 * guest-checkout experience), this quietly finds an existing account
 * by email or creates one — the guest never sees a "create an
 * account" step. Confirmed with Hatem before building this way.
 *
 * ROLE NOTE: newly-created guest accounts use WordPress's built-in
 * 'subscriber' role for now — guaranteed to exist. If
 * class-moga-roles.php already registers a dedicated 'moga_guest'
 * role, that would be the more correct choice; worth confirming and
 * switching to it, not verified as part of this file.
 *
 * PAYMENT REALITY: no live gateway exists yet (see
 * class-moga-payment.php's own docblock) — every payment recorded
 * here uses gateway 'offline', status 'pending'. The guest is told
 * this plainly rather than being shown a fake "processing payment"
 * step.
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
 * Class Moga_Shortcode_Checkout
 */
class Moga_Shortcode_Checkout
{

    /**
     * Register the shortcode.
     *
     * @since  1.0.0
     * @return void
     */
    public function register()
    {
        add_shortcode('moga_checkout', array($this, 'render'));
    }

    /**
     * Shortcode callback — dispatches to the confirm handler or the
     * review-and-payment display, based on whether this is the
     * guest's own "Confirm Booking" submission.
     *
     * @since  1.0.0
     * @param  array $atts Shortcode attributes (unused).
     * @return string
     */
    public function render($atts)
    {
        ob_start();

        $listing_type = isset($_POST['listing_type']) ? sanitize_key(wp_unslash($_POST['listing_type'])) : 'property';
        $is_confirm   = isset($_POST['moga_action']) && 'confirm' === sanitize_key(wp_unslash($_POST['moga_action']));

        if ('tour' === $listing_type) {
            if ($is_confirm) {
                $this->handle_tour_confirm();
            } else {
                $this->render_tour_review_and_payment();
            }
            return ob_get_clean();
        }

        if ($is_confirm) {
            $this->handle_confirm();
        } else {
            $this->render_review_and_payment();
        }

        return ob_get_clean();
    }

    /**
     * Read, sanitize, and fully re-validate everything needed —
     * called at the start of BOTH states, since neither trusts
     * posted data blindly, even on the second (confirm) round.
     *
     * Renders an error and returns false the moment anything fails,
     * so callers can just check the return value.
     *
     * @since  1.0.0
     * @return array|false
     */
    private function get_validated_data()
    {
        $property_id = isset($_POST['property_id']) ? absint($_POST['property_id']) : 0;
        $check_in    = isset($_POST['check_in'])    ? sanitize_text_field(wp_unslash($_POST['check_in']))  : '';
        $check_out   = isset($_POST['check_out'])   ? sanitize_text_field(wp_unslash($_POST['check_out'])) : '';
        $guests      = isset($_POST['guests'])      ? absint($_POST['guests']) : 1;
        $guest_name  = isset($_POST['guest_name'])  ? sanitize_text_field(wp_unslash($_POST['guest_name'])) : '';
        $guest_email = isset($_POST['guest_email']) ? sanitize_email(wp_unslash($_POST['guest_email'])) : '';
        $guest_phone = isset($_POST['guest_phone']) ? sanitize_text_field(wp_unslash($_POST['guest_phone'])) : '';
        $guest_notes = isset($_POST['guest_notes']) ? sanitize_textarea_field(wp_unslash($_POST['guest_notes'])) : '';

        if (! $property_id || ! moga_is_valid_date($check_in) || ! moga_is_valid_date($check_out)) {
            $this->render_error(
                __('We couldn\'t find your booking details.', 'moga-travel'),
                __('Please go back and start your booking again.', 'moga-travel')
            );
            return false;
        }

        // REAL validation of guest details — deliberately deferred
        // from the Booking page to here.
        if ('' === trim($guest_name)) {
            $this->render_error(
                __('Please enter your name.', 'moga-travel'),
                __('Go back and fill in your full name.', 'moga-travel')
            );
            return false;
        }

        if (! is_email($guest_email)) {
            $this->render_error(
                __('That email address doesn\'t look right.', 'moga-travel'),
                __('Go back and double-check your email address.', 'moga-travel')
            );
            return false;
        }

        if ('' === trim($guest_phone)) {
            $this->render_error(
                __('Please enter a phone number.', 'moga-travel'),
                __('Go back and fill in a phone number we can reach you on.', 'moga-travel')
            );
            return false;
        }

        $property = get_post($property_id);
        if (! $property || 'moga_property' !== $property->post_type || 'publish' !== $property->post_status) {
            $this->render_error(
                __('This property is no longer available.', 'moga-travel'),
                __('Please go back and choose a different property.', 'moga-travel')
            );
            return false;
        }

        // Re-check availability AGAIN — time has passed since the
        // Booking page's own check.
        if (! moga_is_available($property_id, $check_in, $check_out, 'property')) {
            $this->render_error(
                __('These dates are no longer available.', 'moga-travel'),
                __('Someone may have just booked them. Please go back and choose different dates.', 'moga-travel')
            );
            return false;
        }

        // RE-CALCULATE the price again — never trust anything
        // posted, even on this second round.
        $price = moga_calculate_property_price($property_id, $check_in, $check_out);
        if (empty($price['nights'])) {
            $this->render_error(
                __('We couldn\'t calculate a price for these dates.', 'moga-travel'),
                __('Please go back and try different dates.', 'moga-travel')
            );
            return false;
        }

        $max_guests = intval(get_post_meta($property_id, '_moga_max_guests', true)) ?: 10;
        $guests     = max(1, min($guests, $max_guests));

        return array(
            'property_id' => $property_id,
            'check_in'    => $check_in,
            'check_out'   => $check_out,
            'guests'      => $guests,
            'guest_name'  => $guest_name,
            'guest_email' => $guest_email,
            'guest_phone' => $guest_phone,
            'guest_notes' => $guest_notes,
            'price'       => $price,
        );
    }

    /**
     * Render a clear, honest error state with a way back.
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
     * STATE 1 — Review + payment method choice. The guest's own
     * "Confirm Booking" form posts back to this same page with
     * 'moga_action=confirm'.
     *
     * @since  1.0.0
     * @return void
     */
    private function render_review_and_payment()
    {
        $data = $this->get_validated_data();
        if (! $data) {
            return;
        }

        $price = $data['price'];

        $core           = function_exists('moga_core') ? moga_core() : null;
        $deposit_amount = ($core && $core->booking)
            ? $core->booking->calculate_deposit_amount((float) $price['total'], $data['property_id'])
            : (float) $price['total'];
        $deposit_available = $deposit_amount < (float) $price['total'];
        $balance_due_days  = absint(get_option('moga_balance_due_days', 7));

        $title     = get_the_title($data['property_id']);
        $thumbnail = get_the_post_thumbnail_url($data['property_id'], 'moga-card');

        // Real Cancellation Policy for THIS property, using the same
        // authoritative label/description text as the general policy
        // reference page — never invented separately, so the two can
        // never disagree with each other.
        $cancellation_key = get_post_meta($data['property_id'], '_moga_cancellation', true) ?: 'moderate';
        $cancel_policies  = class_exists('Moga_CPT_Property') ? Moga_CPT_Property::get_cancellation_policies() : array();
        $cancel_info      = isset($cancel_policies[$cancellation_key]) ? $cancel_policies[$cancellation_key] : null;
        $policy_page_url  = get_option('moga_page_cancellation_policy')
            ? get_permalink(get_option('moga_page_cancellation_policy'))
            : home_url('/Cancellation-policy/');
    ?>
        <div class="moga-checkout">

            <div class="moga-checkout__steps">
                <span class="moga-checkout__step moga-checkout__step--done"><?php esc_html_e('1. Your Details', 'moga-travel'); ?></span>
                <span class="moga-checkout__step moga-checkout__step--current"><?php esc_html_e('2. Payment', 'moga-travel'); ?></span>
                <span class="moga-checkout__step"><?php esc_html_e('3. Confirmation', 'moga-travel'); ?></span>
            </div>

            <div class="moga-booking-review__summary">
                <?php if ($thumbnail) : ?>
                    <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php echo esc_attr($title); ?>" class="moga-booking-review__image">
                <?php endif; ?>
                <div class="moga-booking-review__details">
                    <h3><?php echo esc_html($title); ?></h3>
                    <div class="moga-booking-review__dates">
                        <span><strong><?php esc_html_e('Check-in:', 'moga-travel'); ?></strong> <?php echo esc_html(moga_format_date_human($data['check_in'])); ?></span>
                        <span><strong><?php esc_html_e('Check-out:', 'moga-travel'); ?></strong> <?php echo esc_html(moga_format_date_human($data['check_out'])); ?></span>
                        <span><strong><?php esc_html_e('Guests:', 'moga-travel'); ?></strong> <?php echo esc_html($data['guests']); ?></span>
                    </div>
                </div>
            </div>

            <?php // Guest details — actually SHOWN now, not just carried
            // forward as invisible hidden fields. A guest should see
            // exactly what they're confirming before paying.
            ?>
            <div class="moga-checkout-guest-summary">
                <h4><?php esc_html_e('Your Details', 'moga-travel'); ?></h4>
                <div class="moga-checkout-guest-summary__row">
                    <span class="moga-checkout-guest-summary__label"><?php esc_html_e('Name', 'moga-travel'); ?></span>
                    <span><?php echo esc_html($data['guest_name']); ?></span>
                </div>
                <div class="moga-checkout-guest-summary__row">
                    <span class="moga-checkout-guest-summary__label"><?php esc_html_e('Email', 'moga-travel'); ?></span>
                    <span><?php echo esc_html($data['guest_email']); ?></span>
                </div>
                <div class="moga-checkout-guest-summary__row">
                    <span class="moga-checkout-guest-summary__label"><?php esc_html_e('Phone', 'moga-travel'); ?></span>
                    <span><?php echo esc_html($data['guest_phone']); ?></span>
                </div>
                <?php if ($data['guest_notes']) : ?>
                    <div class="moga-checkout-guest-summary__row">
                        <span class="moga-checkout-guest-summary__label"><?php esc_html_e('Notes', 'moga-travel'); ?></span>
                        <span><?php echo esc_html($data['guest_notes']); ?></span>
                    </div>
                <?php endif; ?>
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

            <?php if ($cancel_info) : ?>
                <div class="moga-checkout-cancellation">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                    </svg>
                    <p>
                        <strong><?php echo esc_html($cancel_info['label']); ?>:</strong>
                        <?php echo esc_html($cancel_info['desc']); ?>
                        <a href="<?php echo esc_url($policy_page_url); ?>" target="_blank" rel="noopener">
                            <?php esc_html_e('Read the full Cancellation Policy', 'moga-travel'); ?>
                        </a>
                    </p>
                </div>
            <?php endif; ?>

            <form method="POST" class="moga-checkout__form">
                <?php wp_nonce_field('moga_checkout_confirm', 'moga_checkout_confirm_nonce'); ?>
                <input type="hidden" name="moga_action" value="confirm">
                <input type="hidden" name="property_id" value="<?php echo esc_attr($data['property_id']); ?>">
                <input type="hidden" name="check_in" value="<?php echo esc_attr($data['check_in']); ?>">
                <input type="hidden" name="check_out" value="<?php echo esc_attr($data['check_out']); ?>">
                <input type="hidden" name="guests" value="<?php echo esc_attr($data['guests']); ?>">
                <input type="hidden" name="guest_name" value="<?php echo esc_attr($data['guest_name']); ?>">
                <input type="hidden" name="guest_email" value="<?php echo esc_attr($data['guest_email']); ?>">
                <input type="hidden" name="guest_phone" value="<?php echo esc_attr($data['guest_phone']); ?>">
                <input type="hidden" name="guest_notes" value="<?php echo esc_attr($data['guest_notes']); ?>">

                <h4><?php esc_html_e('Payment', 'moga-travel'); ?></h4>

                <?php if ($deposit_available) : ?>
                    <label class="moga-checkout-payment-option moga-checkout-payment-option--selected">
                        <input type="radio" name="payment_type" value="full" checked>
                        <span class="moga-checkout-payment-option__body">
                            <span class="moga-checkout-payment-option__title">
                                <?php printf(
                                    /* translators: %s: full price, formatted */
                                    esc_html__('Pay in full — %s', 'moga-travel'),
                                    esc_html(moga_format_price($price['total'], $price['currency']))
                                ); ?>
                            </span>
                        </span>
                    </label>
                    <label class="moga-checkout-payment-option">
                        <input type="radio" name="payment_type" value="deposit">
                        <span class="moga-checkout-payment-option__body">
                            <span class="moga-checkout-payment-option__title">
                                <?php printf(
                                    /* translators: %s: deposit amount, formatted */
                                    esc_html__('Pay a deposit now — %s', 'moga-travel'),
                                    esc_html(moga_format_price($deposit_amount, $price['currency']))
                                ); ?>
                            </span>
                            <span class="moga-checkout-payment-option__hint">
                                <?php printf(
                                    /* translators: 1: remaining balance, formatted; 2: number of days */
                                    esc_html(_n(
                                        'Remaining balance of %1$s due %2$d day before check-in.',
                                        'Remaining balance of %1$s due %2$d days before check-in.',
                                        $balance_due_days,
                                        'moga-travel'
                                    )),
                                    esc_html(moga_format_price($price['total'] - $deposit_amount, $price['currency'])),
                                    $balance_due_days
                                ); ?>
                            </span>
                        </span>
                    </label>
                <?php else : ?>
                    <input type="hidden" name="payment_type" value="full">
                    <p><?php esc_html_e('Full payment is required for this listing.', 'moga-travel'); ?></p>
                <?php endif; ?>

                <p class="moga-checkout__offline-note">
                    <?php esc_html_e('Online card payment isn\'t available yet — your booking will be marked as pending until the owner confirms your payment was received.', 'moga-travel'); ?>
                </p>

                <button type="submit" class="moga-btn moga-btn--primary moga-w-100">
                    <?php esc_html_e('Confirm Booking', 'moga-travel'); ?>
                </button>

                <p class="moga-checkout__trust-note">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                        <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                    </svg>
                    <?php esc_html_e('Your details are only shared with this property\'s owner to arrange your stay.', 'moga-travel'); ?>
                </p>
            </form>

            <script>
                (function() {
                    var options = document.querySelectorAll('.moga-checkout-payment-option');
                    options.forEach(function(opt) {
                        opt.addEventListener('click', function() {
                            options.forEach(function(o) {
                                o.classList.remove('moga-checkout-payment-option--selected');
                            });
                            opt.classList.add('moga-checkout-payment-option--selected');
                        });
                    });
                })();
            </script>

        </div>
<?php
    }

    /**
     * STATE 2 — the guest clicked "Confirm Booking". Re-validates
     * everything again, finds or creates the guest's account,
     * creates the real booking, records the payment as
     * offline/pending, then redirects to Confirmation.
     *
     * @since  1.0.0
     * @return void
     */
    private function handle_confirm()
    {
        if (
            ! isset($_POST['moga_checkout_confirm_nonce'])
            || ! wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['moga_checkout_confirm_nonce'])),
                'moga_checkout_confirm'
            )
        ) {
            $this->render_error(
                __('This booking form has expired.', 'moga-travel'),
                __('Please go back and try again.', 'moga-travel')
            );
            return;
        }

        $data = $this->get_validated_data();
        if (! $data) {
            return;
        }

        $core = function_exists('moga_core') ? moga_core() : null;
        if (! $core || ! $core->booking || ! $core->payment) {
            $this->render_error(
                __('Something went wrong on our end.', 'moga-travel'),
                __('Please try again in a moment.', 'moga-travel')
            );
            return;
        }

        $price          = $data['price'];
        $deposit_amount = $core->booking->calculate_deposit_amount((float) $price['total'], $data['property_id']);
        $deposit_available = $deposit_amount < (float) $price['total'];

        $payment_type = isset($_POST['payment_type']) ? sanitize_key(wp_unslash($_POST['payment_type'])) : 'full';
        if (! in_array($payment_type, array('full', 'deposit'), true) || (! $deposit_available && 'deposit' === $payment_type)) {
            $payment_type = 'full';
        }

        $amount_to_charge = 'deposit' === $payment_type ? $deposit_amount : (float) $price['total'];

        $guest_id = $this->find_or_create_guest_user($data['guest_name'], $data['guest_email'], $data['guest_phone']);
        if (is_wp_error($guest_id)) {
            $this->render_error(
                __('We couldn\'t set up your booking account.', 'moga-travel'),
                $guest_id->get_error_message()
            );
            return;
        }

        // Log them in — so they land on Confirmation (and can browse
        // their dashboard afterward) already recognized, without
        // ever having seen a login/registration step.
        wp_set_current_user($guest_id);
        wp_set_auth_cookie($guest_id);

        $booking_id = $core->booking->create_booking(array(
            'booking_type'     => 'property',
            'listing_id'       => $data['property_id'],
            'guest_id'         => $guest_id,
            'check_in'         => $data['check_in'],
            'check_out'        => $data['check_out'],
            'guests_adults'    => $data['guests'],
            'guests_children'  => 0,
            'guests_infants'   => 0,
            'special_requests' => $data['guest_notes'],
        ));

        if (is_wp_error($booking_id)) {
            $this->render_error(
                __('We couldn\'t complete your booking.', 'moga-travel'),
                $booking_id->get_error_message()
            );
            return;
        }

        // Offline only — no live gateway exists yet (see
        // class-moga-payment.php's own docblock). Marked 'pending'
        // until the owner manually confirms payment was received.
        $core->payment->record_payment(
            $booking_id,
            $amount_to_charge,
            $payment_type,
            'offline',
            'offline',
            array(),
            '',
            'pending'
        );

        $confirmation_url = get_option('moga_page_booking_confirmation')
            ? get_permalink(get_option('moga_page_booking_confirmation'))
            : home_url('/');

        wp_safe_redirect(add_query_arg('booking_id', $booking_id, $confirmation_url));
        exit;
    }

    /**
     * Find an existing WordPress account by email, or silently
     * create a new one — the guest never sees an explicit "create an
     * account" step. See this file's own docblock for the full
     * reasoning (Moga_Booking::create_booking() requires a real
     * guest_id; there's no separate guest-contact-info storage).
     *
     * @since  1.0.0
     * @param  string $name  Guest's full name.
     * @param  string $email Guest's email — used to find/create the account.
     * @param  string $phone Guest's phone — stored as user meta.
     * @return int|WP_Error User ID, or WP_Error on failure.
     */
    private function find_or_create_guest_user($name, $email, $phone)
    {
        $existing = get_user_by('email', $email);
        if ($existing) {
            update_user_meta($existing->ID, '_moga_phone', $phone);
            return $existing->ID;
        }

        $username_base = sanitize_user(current(explode('@', $email)), true);
        if ('' === $username_base) {
            $username_base = 'guest';
        }

        $username = $username_base;
        $suffix   = 1;
        while (username_exists($username)) {
            $username = $username_base . $suffix;
            $suffix++;
        }

        $user_id = wp_insert_user(array(
            'user_login'   => $username,
            'user_email'   => $email,
            'user_pass'    => wp_generate_password(20, true),
            'display_name' => $name,
            // TODO: switch to a dedicated 'moga_guest' role once
            // confirmed against class-moga-roles.php — 'subscriber'
            // used here since it's guaranteed to exist.
            'role'         => 'subscriber',
        ));

        if (is_wp_error($user_id)) {
            return $user_id;
        }

        update_user_meta($user_id, '_moga_phone', $phone);

        return $user_id;
    }

    /**
     * Read, sanitize, and fully re-validate a tour checkout request —
     * the tour equivalent of get_validated_data() above. Called at
     * the start of both tour states, same discipline as the property
     * path: never trust posted data blindly, even on the confirm round.
     *
     * @since  1.0.0
     * @return array|false
     */
    private function get_validated_tour_data()
    {
        $tour_id           = isset($_POST['tour_id'])           ? absint($_POST['tour_id']) : 0;
        $tour_date         = isset($_POST['tour_date'])         ? sanitize_text_field(wp_unslash($_POST['tour_date'])) : '';
        $adults            = isset($_POST['adults'])            ? max(1, absint($_POST['adults'])) : 1;
        $children          = isset($_POST['children'])          ? absint($_POST['children']) : 0;
        $infants           = isset($_POST['infants'])           ? absint($_POST['infants']) : 0;
        $guest_name        = isset($_POST['guest_name'])        ? sanitize_text_field(wp_unslash($_POST['guest_name'])) : '';
        $guest_email       = isset($_POST['guest_email'])       ? sanitize_email(wp_unslash($_POST['guest_email'])) : '';
        $guest_phone       = isset($_POST['guest_phone'])       ? sanitize_text_field(wp_unslash($_POST['guest_phone'])) : '';
        $guest_notes       = isset($_POST['guest_notes'])       ? sanitize_textarea_field(wp_unslash($_POST['guest_notes'])) : '';
        $bus_id            = isset($_POST['bus_id'])            ? absint($_POST['bus_id']) : 0;
        $selected_seats    = isset($_POST['selected_seats'])    ? sanitize_text_field(wp_unslash($_POST['selected_seats'])) : '';
        $seat_session_token = isset($_POST['seat_session_token']) ? sanitize_text_field(wp_unslash($_POST['seat_session_token'])) : '';

        if (! $tour_id || ! moga_is_valid_date($tour_date)) {
            $this->render_error(
                __('We couldn\'t find your booking details.', 'moga-travel'),
                __('Please go back and start your booking again.', 'moga-travel')
            );
            return false;
        }

        if ('' === trim($guest_name)) {
            $this->render_error(
                __('Please enter your name.', 'moga-travel'),
                __('Go back and fill in your full name.', 'moga-travel')
            );
            return false;
        }

        if (! is_email($guest_email)) {
            $this->render_error(
                __('That email address doesn\'t look right.', 'moga-travel'),
                __('Go back and double-check your email address.', 'moga-travel')
            );
            return false;
        }

        if ('' === trim($guest_phone)) {
            $this->render_error(
                __('Please enter a phone number.', 'moga-travel'),
                __('Go back and fill in a phone number we can reach you on.', 'moga-travel')
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

        // Re-check real, live capacity AGAIN — time has passed since
        // the Booking page's own check.
        if (! function_exists('moga_is_tour_group_available') || ! moga_is_tour_group_available($tour_id, $tour_date, $adults + $children)) {
            $this->render_error(
                __('This departure is no longer available.', 'moga-travel'),
                __('It may have sold out or closed for booking. Please go back and choose a different departure.', 'moga-travel')
            );
            return false;
        }

        // RE-CALCULATE the price again — never trust anything
        // posted, even on this second round.
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
            'tour_id'            => $tour_id,
            'tour_date'          => $tour_date,
            'adults'             => $adults,
            'children'           => $children,
            'infants'            => $infants,
            'guest_name'         => $guest_name,
            'guest_email'        => $guest_email,
            'guest_phone'        => $guest_phone,
            'guest_notes'        => $guest_notes,
            'price'              => $price,
            'bus_id'             => $bus_id,
            'selected_seats'     => $selected_seats,
            'seat_session_token' => $seat_session_token,
        );
    }

    /**
     * STATE 1 for tours — review + payment method choice. Mirrors
     * render_review_and_payment()'s exact structure, adapted for a
     * departure date and adult/child/infant counts.
     *
     * @since  1.0.0
     * @return void
     */
    private function render_tour_review_and_payment()
    {
        $data = $this->get_validated_tour_data();
        if (! $data) {
            return;
        }

        $price = $data['price'];

        $core           = function_exists('moga_core') ? moga_core() : null;
        $deposit_amount = ($core && $core->booking)
            ? $core->booking->calculate_deposit_amount((float) $price['total'], $data['tour_id'])
            : (float) $price['total'];
        $deposit_available = $deposit_amount < (float) $price['total'];
        $balance_due_days  = absint(get_option('moga_balance_due_days', 7));

        $title     = get_the_title($data['tour_id']);
        $thumbnail = get_the_post_thumbnail_url($data['tour_id'], 'moga-card');

        $cancellation_key = get_post_meta($data['tour_id'], '_moga_cancellation', true) ?: 'moderate';
        $cancel_policies  = class_exists('Moga_CPT_Tour') ? Moga_CPT_Tour::get_cancellation_policies() : array();
        $cancel_info      = isset($cancel_policies[$cancellation_key]) ? $cancel_policies[$cancellation_key] : null;
        $policy_page_url  = get_option('moga_page_cancellation_policy')
            ? get_permalink(get_option('moga_page_cancellation_policy'))
            : home_url('/cancellation-policy/');
    ?>
        <div class="moga-checkout">

            <div class="moga-checkout__steps">
                <span class="moga-checkout__step moga-checkout__step--done"><?php esc_html_e('1. Your Details', 'moga-travel'); ?></span>
                <span class="moga-checkout__step moga-checkout__step--current"><?php esc_html_e('2. Payment', 'moga-travel'); ?></span>
                <span class="moga-checkout__step"><?php esc_html_e('3. Confirmation', 'moga-travel'); ?></span>
            </div>

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

            <div class="moga-checkout-guest-summary">
                <h4><?php esc_html_e('Your Details', 'moga-travel'); ?></h4>
                <div class="moga-checkout-guest-summary__row">
                    <span class="moga-checkout-guest-summary__label"><?php esc_html_e('Name', 'moga-travel'); ?></span>
                    <span><?php echo esc_html($data['guest_name']); ?></span>
                </div>
                <div class="moga-checkout-guest-summary__row">
                    <span class="moga-checkout-guest-summary__label"><?php esc_html_e('Email', 'moga-travel'); ?></span>
                    <span><?php echo esc_html($data['guest_email']); ?></span>
                </div>
                <div class="moga-checkout-guest-summary__row">
                    <span class="moga-checkout-guest-summary__label"><?php esc_html_e('Phone', 'moga-travel'); ?></span>
                    <span><?php echo esc_html($data['guest_phone']); ?></span>
                </div>
                <?php if ($data['guest_notes']) : ?>
                    <div class="moga-checkout-guest-summary__row">
                        <span class="moga-checkout-guest-summary__label"><?php esc_html_e('Notes', 'moga-travel'); ?></span>
                        <span><?php echo esc_html($data['guest_notes']); ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <?php // Three-line breakdown — each line only shown when
            // its count is real, matching the same rule already
            // built into the tour booking widget itself.
            ?>
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

            <?php if ($cancel_info) : ?>
                <div class="moga-checkout-cancellation">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                    </svg>
                    <p>
                        <strong><?php echo esc_html($cancel_info['label']); ?>:</strong>
                        <?php echo esc_html($cancel_info['desc']); ?>
                        <a href="<?php echo esc_url($policy_page_url); ?>" target="_blank" rel="noopener">
                            <?php esc_html_e('Read the full Cancellation Policy', 'moga-travel'); ?>
                        </a>
                    </p>
                </div>
            <?php endif; ?>

            <form method="POST" class="moga-checkout__form">
                <?php wp_nonce_field('moga_checkout_confirm', 'moga_checkout_confirm_nonce'); ?>
                <input type="hidden" name="moga_action"          value="confirm">
                <input type="hidden" name="listing_type"         value="tour">
                <input type="hidden" name="tour_id"              value="<?php echo esc_attr($data['tour_id']); ?>">
                <input type="hidden" name="tour_date"            value="<?php echo esc_attr($data['tour_date']); ?>">
                <input type="hidden" name="adults"               value="<?php echo esc_attr($data['adults']); ?>">
                <input type="hidden" name="children"             value="<?php echo esc_attr($data['children']); ?>">
                <input type="hidden" name="infants"              value="<?php echo esc_attr($data['infants']); ?>">
                <input type="hidden" name="guest_name"           value="<?php echo esc_attr($data['guest_name']); ?>">
                <input type="hidden" name="guest_email"          value="<?php echo esc_attr($data['guest_email']); ?>">
                <input type="hidden" name="guest_phone"          value="<?php echo esc_attr($data['guest_phone']); ?>">
                <input type="hidden" name="guest_notes"          value="<?php echo esc_attr($data['guest_notes']); ?>">
                <?php if (! empty($data['bus_id'])) : ?>
                <input type="hidden" name="bus_id"               value="<?php echo esc_attr($data['bus_id']); ?>">
                <input type="hidden" name="selected_seats"       value="<?php echo esc_attr($data['selected_seats']); ?>">
                <input type="hidden" name="seat_session_token"   value="<?php echo esc_attr($data['seat_session_token']); ?>">
                <?php endif; ?>

                <h4><?php esc_html_e('Payment', 'moga-travel'); ?></h4>

                <?php if ($deposit_available) : ?>
                    <label class="moga-checkout-payment-option moga-checkout-payment-option--selected">
                        <input type="radio" name="payment_type" value="full" checked>
                        <span class="moga-checkout-payment-option__body">
                            <span class="moga-checkout-payment-option__title">
                                <?php printf(
                                    /* translators: %s: full price, formatted */
                                    esc_html__('Pay in full — %s', 'moga-travel'),
                                    esc_html(moga_format_price($price['total'], $price['currency']))
                                ); ?>
                            </span>
                        </span>
                    </label>
                    <label class="moga-checkout-payment-option">
                        <input type="radio" name="payment_type" value="deposit">
                        <span class="moga-checkout-payment-option__body">
                            <span class="moga-checkout-payment-option__title">
                                <?php printf(
                                    /* translators: %s: deposit amount, formatted */
                                    esc_html__('Pay a deposit now — %s', 'moga-travel'),
                                    esc_html(moga_format_price($deposit_amount, $price['currency']))
                                ); ?>
                            </span>
                            <span class="moga-checkout-payment-option__hint">
                                <?php printf(
                                    /* translators: 1: remaining balance, formatted; 2: number of days */
                                    esc_html(_n(
                                        'Remaining balance of %1$s due %2$d day before departure.',
                                        'Remaining balance of %1$s due %2$d days before departure.',
                                        $balance_due_days,
                                        'moga-travel'
                                    )),
                                    esc_html(moga_format_price($price['total'] - $deposit_amount, $price['currency'])),
                                    $balance_due_days
                                ); ?>
                            </span>
                        </span>
                    </label>
                <?php else : ?>
                    <input type="hidden" name="payment_type" value="full">
                    <p><?php esc_html_e('Full payment is required for this listing.', 'moga-travel'); ?></p>
                <?php endif; ?>

                <p class="moga-checkout__offline-note">
                    <?php esc_html_e('Online card payment isn\'t available yet — your booking will be marked as pending until the organizer confirms your payment was received.', 'moga-travel'); ?>
                </p>

                <button type="submit" class="moga-btn moga-btn--primary moga-w-100">
                    <?php esc_html_e('Confirm Booking', 'moga-travel'); ?>
                </button>

                <p class="moga-checkout__trust-note">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                        <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                    </svg>
                    <?php esc_html_e('Your details are only shared with this tour\'s organizer to arrange your trip.', 'moga-travel'); ?>
                </p>
            </form>

            <script>
                (function() {
                    var options = document.querySelectorAll('.moga-checkout-payment-option');
                    options.forEach(function(opt) {
                        opt.addEventListener('click', function() {
                            options.forEach(function(o) {
                                o.classList.remove('moga-checkout-payment-option--selected');
                            });
                            opt.classList.add('moga-checkout-payment-option--selected');
                        });
                    });
                })();
            </script>

        </div>
<?php
    }

    /**
     * STATE 2 for tours — the guest clicked "Confirm Booking".
     * Mirrors handle_confirm()'s exact structure and defensive
     * re-validation discipline.
     *
     * @since  1.0.0
     * @return void
     */
    private function handle_tour_confirm()
    {
        if (
            ! isset($_POST['moga_checkout_confirm_nonce'])
            || ! wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['moga_checkout_confirm_nonce'])),
                'moga_checkout_confirm'
            )
        ) {
            $this->render_error(
                __('This booking form has expired.', 'moga-travel'),
                __('Please go back and try again.', 'moga-travel')
            );
            return;
        }

        $data = $this->get_validated_tour_data();
        if (! $data) {
            return;
        }

        $core = function_exists('moga_core') ? moga_core() : null;
        if (! $core || ! $core->booking || ! $core->payment) {
            $this->render_error(
                __('Something went wrong on our end.', 'moga-travel'),
                __('Please try again in a moment.', 'moga-travel')
            );
            return;
        }

        $price              = $data['price'];
        $deposit_amount     = $core->booking->calculate_deposit_amount((float) $price['total'], $data['tour_id']);
        $deposit_available  = $deposit_amount < (float) $price['total'];

        $payment_type = isset($_POST['payment_type']) ? sanitize_key(wp_unslash($_POST['payment_type'])) : 'full';
        if (! in_array($payment_type, array('full', 'deposit'), true) || (! $deposit_available && 'deposit' === $payment_type)) {
            $payment_type = 'full';
        }

        $amount_to_charge = 'deposit' === $payment_type ? $deposit_amount : (float) $price['total'];

        $guest_id = $this->find_or_create_guest_user($data['guest_name'], $data['guest_email'], $data['guest_phone']);
        if (is_wp_error($guest_id)) {
            $this->render_error(
                __('We couldn\'t set up your booking account.', 'moga-travel'),
                $guest_id->get_error_message()
            );
            return;
        }

        wp_set_current_user($guest_id);
        wp_set_auth_cookie($guest_id);

        // The real booking record still needs a check_out — a Tour
        // Group's own end date, computed the same way the booking
        // widget itself already computes it (start + this tour's
        // Duration in nights), so the stored date always matches
        // what the guest actually saw.
        $check_out = $this->compute_group_end_date($data['tour_id'], $data['tour_date']);

        $booking_id = $core->booking->create_booking(array(
            'booking_type'     => 'tour',
            'listing_id'       => $data['tour_id'],
            'guest_id'         => $guest_id,
            'check_in'         => $data['tour_date'],
            'check_out'        => $check_out,
            'guests_adults'    => $data['adults'],
            'guests_children'  => $data['children'],
            'guests_infants'   => $data['infants'],
            'special_requests' => $data['guest_notes'],
        ));

        if (is_wp_error($booking_id)) {
            $this->render_error(
                __('We couldn\'t complete your booking.', 'moga-travel'),
                $booking_id->get_error_message()
            );
            return;
        }

        // Confirm the seat reservations — upgrade status from
        // 'reserved' → 'booked' and write the booking_id.
        // Uses INSERT ON DUPLICATE KEY UPDATE so this works even if
        // the hold expired and the reserved row was deleted before
        // the guest completed checkout.
        if (! empty($data['bus_id']) && ! empty($data['selected_seats']) && $core->seat_map) {
            $seat_numbers = array_values(
                array_filter(
                    array_map('trim', explode(',', $data['selected_seats']))
                )
            );
            if (! empty($seat_numbers)) {
                $core->seat_map->confirm_seats(
                    $data['bus_id'],
                    $data['tour_date'],
                    $seat_numbers,
                    $booking_id,
                    $data['seat_session_token']
                );
            }
        }

        // Offline only — no live gateway exists yet. Marked 'pending'
        // until the organizer manually confirms payment was received.
        $core->payment->record_payment(
            $booking_id,
            $amount_to_charge,
            $payment_type,
            'offline',
            'offline',
            array(),
            '',
            'pending'
        );

        // Fix balance_due for full payments.
        // create_booking() always stores balance_due = total - deposit.
        // For a full payment this is wrong — zero it out now.
        // For a deposit payment, the stored balance_due is already correct
        // (it shows what's still owed) — do NOT subtract again.
        if ('full' === $payment_type) {
            $core->booking->record_payment_received($booking_id, $amount_to_charge, 'full');
        }

        $confirmation_url = get_option('moga_page_booking_confirmation')
            ? get_permalink(get_option('moga_page_booking_confirmation'))
            : home_url('/');

        wp_safe_redirect(add_query_arg('booking_id', $booking_id, $confirmation_url));
        exit;
    }

    /**
     * Computes a Tour Group's own end date — start date plus the
     * tour's own fixed Duration in nights. Mirrors exactly the same
     * calculation already used in the tour booking widget itself
     * (template-parts/tour/booking-form.php), so the date stored on
     * the real booking record always matches what the guest saw.
     *
     * @since  1.0.0
     * @param  int    $tour_id     Tour post ID.
     * @param  string $group_start The chosen group's start date (Y-m-d).
     * @return string Y-m-d.
     */
    private function compute_group_end_date($tour_id, $group_start)
    {
        $duration_nights = intval(get_post_meta($tour_id, '_moga_duration_nights', true));
        $end             = date_create($group_start);

        if (! $end) {
            return $group_start;
        }

        $end->modify('+' . $duration_nights . ' days');
        return $end->format('Y-m-d');
    }
}
