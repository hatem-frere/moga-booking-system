<?php

/**
 * Confirmation Shortcode — [moga_booking_confirmation]
 *
 * Step 4 of the booking flow — the final page:
 *   Reserve -> Booking page -> Checkout page -> THIS PAGE
 *
 * Reads 'booking_id' from the URL (Checkout's handle_confirm()
 * already redirects here with it). Looks up the REAL booking record
 * — nothing here is re-derived from posted data, since by this point
 * the booking genuinely exists in the database.
 *
 * SECURITY: verifies the currently logged-in user actually owns this
 * booking (guest_id matches) before showing anything. A mismatch
 * shows the same generic "not found" message as a genuinely invalid
 * ID — never a distinct "access denied" message, which would leak
 * that a given booking_id is valid to someone who doesn't own it.
 *
 * EMAIL: sends a real confirmation email via wp_mail() — genuinely
 * functional, not a placeholder. class-moga-notification.php (the
 * intended full notification system) is still empty; this is a
 * small, self-contained, real implementation rather than routing
 * through infrastructure that doesn't exist yet. Guarded with a
 * booking-meta flag so reloading this page never sends a duplicate
 * email.
 *
 * TOURS: not yet supported (matches Booking/Checkout) — a tour
 * booking_type shows an honest message rather than guessing at
 * unbuilt tour-specific confirmation content.
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
 * Class Moga_Shortcode_Confirmation
 */
class Moga_Shortcode_Confirmation
{

    /**
     * Register the shortcode.
     *
     * @since  1.0.0
     * @return void
     */
    public function register()
    {
        add_shortcode('moga_booking_confirmation', array($this, 'render'));
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
        ob_start();

        $booking_id = isset($_GET['booking_id']) ? absint($_GET['booking_id']) : 0;

        $core = function_exists('moga_core') ? moga_core() : null;
        if (! $booking_id || ! $core || ! $core->booking) {
            $this->render_error();
            return ob_get_clean();
        }

        $booking = $core->booking->get_booking($booking_id);

        // Same generic message whether the ID is genuinely invalid,
        // or valid but belongs to someone else — never confirm to an
        // unauthorized visitor that a given booking_id even exists.
        if (! $booking || (int) $booking['guest_id'] !== get_current_user_id()) {
            $this->render_error();
            return ob_get_clean();
        }

        if ('property' !== $booking['booking_type']) {
            $this->render_error(
                __('Tour booking confirmations aren\'t available yet.', 'moga-travel'),
                __('We\'re still building this. Please contact the organizer directly for now.', 'moga-travel')
            );
            return ob_get_clean();
        }

        $this->maybe_send_confirmation_email($booking, $core);
        $this->render_success($booking, $core);

        return ob_get_clean();
    }

    /**
     * The guest's email address, straight from their real WordPress
     * account — this is the reliable source, not booking meta, since
     * guest_email was only ever used to find/create that account in
     * the first place (Checkout page), never separately stored.
     *
     * @since  1.0.0
     * @param  array $booking Full booking row.
     * @return string
     */
    private function get_guest_email($booking)
    {
        $user = get_userdata((int) $booking['guest_id']);
        return $user ? $user->user_email : '';
    }

    /**
     * Render a clear, honest error state with a way back.
     *
     * @since  1.0.0
     * @param  string|null $title   Optional override title.
     * @param  string|null $message Optional override message.
     * @return void
     */
    private function render_error($title = null, $message = null)
    {
        $title   = $title   ?? __('We couldn\'t find that booking.', 'moga-travel');
        $message = $message ?? __('The link may have expired, or this booking doesn\'t belong to your account.', 'moga-travel');

        $home_url = home_url('/');
?>
        <div class="moga-booking-error">
            <h3><?php echo esc_html($title); ?></h3>
            <p><?php echo esc_html($message); ?></p>
            <a href="<?php echo esc_url($home_url); ?>" class="moga-btn moga-btn--primary">
                <?php esc_html_e('Back to Home', 'moga-travel'); ?>
            </a>
        </div>
    <?php
    }

    /**
     * Sends the real confirmation email exactly once per booking,
     * guarded by a booking-meta flag so reloading this page never
     * sends a duplicate.
     *
     * @since  1.0.0
     * @param  array       $booking Full booking row (from get_booking()).
     * @param  Moga_Core   $core    Plugin core instance.
     * @return void
     */
    private function maybe_send_confirmation_email($booking, $core)
    {
        $already_sent = $core->booking->get_booking_meta($booking['id'], '_moga_confirmation_email_sent');
        if ($already_sent) {
            return;
        }

        $property_id = (int) $booking['listing_id'];
        $title       = get_the_title($property_id);
        $currency    = $booking['currency'];

        $cancellation_key = get_post_meta($property_id, '_moga_cancellation', true) ?: 'moderate';
        $cancel_policies   = class_exists('Moga_CPT_Property') ? Moga_CPT_Property::get_cancellation_policies() : array();
        $cancel_info       = isset($cancel_policies[$cancellation_key]) ? $cancel_policies[$cancellation_key] : null;
        $policy_page_url   = get_option('moga_page_cancellation_policy')
            ? get_permalink(get_option('moga_page_cancellation_policy'))
            : home_url('/cancellation-policy/');

        $guest_email = $this->get_guest_email($booking);
        if (! $guest_email || ! is_email($guest_email)) {
            return; // Nothing to send to — don't mark as sent.
        }

        $subject = sprintf(
            /* translators: %s: booking number */
            __('Your booking is confirmed — %s', 'moga-travel'),
            $booking['booking_number']
        );

        $lines   = array();
        $lines[] = sprintf(__('Your booking at %s is confirmed.', 'moga-travel'), $title);
        $lines[] = '';
        $lines[] = sprintf(__('Booking number: %s', 'moga-travel'), $booking['booking_number']);
        $lines[] = sprintf(__('Check-in: %s', 'moga-travel'), moga_format_date_human($booking['check_in']));
        $lines[] = sprintf(__('Check-out: %s', 'moga-travel'), moga_format_date_human($booking['check_out']));
        $lines[] = sprintf(__('Total: %s', 'moga-travel'), moga_format_price((float) $booking['total_amount'], $currency));

        if ((float) $booking['balance_due'] > 0) {
            $lines[] = sprintf(__('Remaining balance: %s', 'moga-travel'), moga_format_price((float) $booking['balance_due'], $currency));
        }

        if ($cancel_info) {
            $lines[] = '';
            $lines[] = sprintf(
                /* translators: 1: policy label, 2: policy description */
                __('Cancellation policy (%1$s): %2$s', 'moga-travel'),
                $cancel_info['label'],
                $cancel_info['desc']
            );
            $lines[] = sprintf(__('Full policy: %s', 'moga-travel'), $policy_page_url);
        }

        $sent = wp_mail($guest_email, $subject, implode("\n", $lines));

        if ($sent) {
            $core->booking->update_booking_meta($booking['id'], '_moga_confirmation_email_sent', current_time('mysql'));
        }
    }

    /**
     * Render the actual success page.
     *
     * @since  1.0.0
     * @param  array     $booking Full booking row.
     * @param  Moga_Core $core    Plugin core instance.
     * @return void
     */
    private function render_success($booking, $core)
    {
        $property_id = (int) $booking['listing_id'];
        $title       = get_the_title($property_id);
        $thumbnail   = get_the_post_thumbnail_url($property_id, 'moga-card');
        $currency    = $booking['currency'];

        $payments = $core->payment ? $core->payment->get_payments_for_booking($booking['id']) : array();

        // Every payment right now is genuinely status 'pending' —
        // nothing auto-confirms it, since there's no live gateway
        // (see class-moga-payment.php's own docblock). Showing a
        // confident "Paid: X" figure would misrepresent money that
        // hasn't actually been confirmed received by anyone yet.
        // What's shown instead: what the guest committed to pay,
        // clearly labeled as pending — honest about the real state.
        $committed_total = 0;
        $has_pending     = false;
        foreach ($payments as $payment) {
            $committed_total += (float) $payment['amount'];
            if ('completed' !== $payment['status']) {
                $has_pending = true;
            }
        }

        $guest_email = $this->get_guest_email($booking);
    ?>
        <div class="moga-confirmation">

            <div class="moga-checkout__steps">
                <span class="moga-checkout__step moga-checkout__step--done"><?php esc_html_e('1. Your Details', 'moga-travel'); ?></span>
                <span class="moga-checkout__step moga-checkout__step--done"><?php esc_html_e('2. Payment', 'moga-travel'); ?></span>
                <span class="moga-checkout__step moga-checkout__step--current"><?php esc_html_e('3. Confirmation', 'moga-travel'); ?></span>
            </div>

            <div class="moga-confirmation__badge">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <circle cx="12" cy="12" r="10" />
                    <polyline points="8 12 11 15 16 9" />
                </svg>
            </div>

            <h2 class="moga-confirmation__title"><?php esc_html_e('Booking Confirmed!', 'moga-travel'); ?></h2>
            <p class="moga-confirmation__booking-number">
                <?php printf(esc_html__('Booking ID: #%s', 'moga-travel'), esc_html($booking['booking_number'])); ?>
            </p>

            <?php if ($guest_email) : ?>
                <p class="moga-confirmation__email-note">
                    <?php printf(
                        /* translators: %s: guest's email address */
                        esc_html__('An email with your booking details has been sent to %s.', 'moga-travel'),
                        '<strong>' . esc_html($guest_email) . '</strong>'
                    ); ?>
                </p>
            <?php endif; ?>

            <div class="moga-booking-review__summary">
                <?php if ($thumbnail) : ?>
                    <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php echo esc_attr($title); ?>" class="moga-booking-review__image">
                <?php endif; ?>
                <div class="moga-booking-review__details">
                    <h3><?php echo esc_html($title); ?></h3>
                    <div class="moga-confirmation__dates">
                        <div class="moga-confirmation__date">
                            <span class="moga-confirmation__date-label"><?php esc_html_e('Check-in', 'moga-travel'); ?></span>
                            <span class="moga-confirmation__date-value"><?php echo esc_html(moga_format_date_human($booking['check_in'])); ?></span>
                        </div>
                        <div class="moga-confirmation__date">
                            <span class="moga-confirmation__date-label"><?php esc_html_e('Check-out', 'moga-travel'); ?></span>
                            <span class="moga-confirmation__date-value"><?php echo esc_html(moga_format_date_human($booking['check_out'])); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="moga-booking-review__price">
                <div class="moga-price-breakdown__row moga-price-breakdown__row--total">
                    <strong><?php esc_html_e('Total', 'moga-travel'); ?></strong>
                    <strong><?php echo esc_html(moga_format_price((float) $booking['total_amount'], $currency)); ?></strong>
                </div>
                <div class="moga-price-breakdown__row">
                    <span><?php esc_html_e('Amount committed', 'moga-travel'); ?></span>
                    <span><?php echo esc_html(moga_format_price($committed_total, $currency)); ?></span>
                </div>
                <?php if ((float) $booking['balance_due'] > 0) : ?>
                    <div class="moga-price-breakdown__row">
                        <span><?php esc_html_e('Balance due', 'moga-travel'); ?></span>
                        <span><?php echo esc_html(moga_format_price((float) $booking['balance_due'], $currency)); ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($has_pending) : ?>
                <p class="moga-confirmation__status-note">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <circle cx="12" cy="12" r="10" />
                        <polyline points="12 6 12 12 16 14" />
                    </svg>
                    <?php esc_html_e('Your payment is marked as pending until the owner confirms it was received.', 'moga-travel'); ?>
                </p>
            <?php endif; ?>

            <?php
            // Second button: links to the ARCHIVE page for whatever
            // was booked (All Properties / All Tours), not the
            // specific listing itself — label and link both change
            // together, based on the real booking_type. Falls back
            // to the Search Results page if this post type's archive
            // isn't enabled/registered, so the button never points
            // nowhere.
            $listing_post_type = $booking['booking_type'];
            $archive_url        = get_post_type_archive_link($listing_post_type);
            if (! $archive_url) {
                $archive_url = get_option('moga_page_search_results')
                    ? get_permalink(get_option('moga_page_search_results'))
                    : home_url('/');
            }
            $archive_label = 'tour' === $listing_post_type
                ? __('Back to All Tours', 'moga-travel')
                : __('Back to All Properties', 'moga-travel');

            // Arrow flips direction for RTL languages — "back" points
            // right, not left, when reading right-to-left.
            $arrow_points = is_rtl()
                ? 'M5 12h14M13 6l6 6-6 6'
                : 'M19 12H5M11 18l-6-6 6-6';
            ?>

            <div class="moga-confirmation__actions">
                <a href="<?php echo esc_url($archive_url); ?>" class="moga-btn moga-btn--secondary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="<?php echo esc_attr($arrow_points); ?>" />
                    </svg>
                    <?php echo esc_html($archive_label); ?>
                </a>
                <a href="<?php echo esc_url(home_url('/')); ?>" class="moga-btn moga-btn--primary">
                    <?php esc_html_e('Back to Home', 'moga-travel'); ?>
                </a>
            </div>

        </div>
<?php
    }
}
