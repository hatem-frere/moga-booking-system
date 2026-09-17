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
 * TOURS: fully supported — cancellation policy source and date
 * labels (Departure/Return vs Check-in/Check-out) branch based on
 * the real booking_type, everything else is shared with properties.
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

        $listing_id = (int) $booking['listing_id'];
        $title      = get_the_title($listing_id);
        $currency   = $booking['currency'];
        $is_tour    = 'tour' === $booking['booking_type'];

        // Cancellation policy source depends on listing type — tours
        // and properties define their own separate policy tiers.
        $cancellation_key = get_post_meta($listing_id, '_moga_cancellation', true) ?: 'moderate';
        $cancel_policies   = $is_tour
            ? (class_exists('Moga_CPT_Tour') ? Moga_CPT_Tour::get_cancellation_policies() : array())
            : (class_exists('Moga_CPT_Property') ? Moga_CPT_Property::get_cancellation_policies() : array());
        $cancel_info       = isset($cancel_policies[$cancellation_key]) ? $cancel_policies[$cancellation_key] : null;
        $policy_page_url   = get_option('moga_page_cancellation_policy')
            ? get_permalink(get_option('moga_page_cancellation_policy'))
            : home_url('/cancellation-policy/');

        $guest_email = $this->get_guest_email($booking);
        if (! $guest_email || ! is_email($guest_email)) {
            return;
        }

        $subject = sprintf(
            __('Booking Confirmed — %s', 'moga-travel'),
            $booking['booking_number']
        );

        // Booked seats (bus tours only).
        $booked_seats = array();
        if ($core->seat_map && 'tour' === $booking['booking_type']) {
            $booked_seats = $core->seat_map->get_booked_seats($booking['id']);
        }

        // Guest name.
        $guest_user = get_user_by('id', $booking['guest_id']);
        $guest_name = $guest_user ? $guest_user->display_name : '';

        // Participants.
        $adults   = (int) ($booking['guests_adults']   ?? 0);
        $children = (int) ($booking['guests_children'] ?? 0);
        $infants  = (int) ($booking['guests_infants']  ?? 0);
        $pparts   = array();
        if ($adults > 0)   $pparts[] = sprintf(_n('%d Adult',  '%d Adults',   $adults,   'moga-travel'), $adults);
        if ($children > 0) $pparts[] = sprintf(_n('%d Child',  '%d Children', $children, 'moga-travel'), $children);
        if ($infants > 0)  $pparts[] = sprintf(_n('%d Infant', '%d Infants',  $infants,  'moga-travel'), $infants);
        $participants = implode(' · ', $pparts);

        $site_name     = get_bloginfo('name');
        $site_url      = home_url('/');
        $balance_due   = (float) $booking['balance_due'];
        $is_fully_paid = $balance_due <= 0;
        $checkin_label = $is_tour ? __('Departure', 'moga-travel') : __('Check-in',  'moga-travel');
        $checkout_label= $is_tour ? __('Return',    'moga-travel') : __('Check-out', 'moga-travel');

        $primary = '#003580';
        $accent  = '#f5a623';
        $green   = '#2e7d32';
        $red     = '#dc2626';
        $bg_out  = '#f4f6f9';
        $bg_card = '#ffffff';
        $txt     = '#1a202c';
        $muted   = '#6b7280';
        $bdr     = '#e2e4e7';

        ob_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:<?php echo $bg_out;?>;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;color:<?php echo $txt;?>;">
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:<?php echo $bg_out;?>;padding:32px 16px;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;">

  <!-- HEADER -->
  <tr><td style="background:<?php echo $primary;?>;border-radius:12px 12px 0 0;padding:28px 40px;text-align:center;">
    <a href="<?php echo esc_url($site_url);?>" style="text-decoration:none;font-size:22px;font-weight:800;color:#fff;letter-spacing:-0.5px;">
      <?php echo esc_html($site_name);?>
    </a>
  </td></tr>

  <!-- SUCCESS BANNER -->
  <tr><td style="background:<?php echo $accent;?>;padding:20px 40px;text-align:center;">
    <span style="font-size:20px;font-weight:700;color:#fff;">✓ <?php esc_html_e('Booking Confirmed!','moga-travel');?></span><br>
    <span style="font-size:13px;color:rgba(255,255,255,0.9);">
      <?php printf(esc_html__('Booking ID: #%s','moga-travel'), esc_html($booking['booking_number']));?>
    </span>
  </td></tr>

  <!-- BODY -->
  <tr><td style="background:<?php echo $bg_card;?>;padding:36px 40px;">

    <?php if ($guest_name): ?>
    <p style="margin:0 0 24px;font-size:16px;">
      <?php printf(esc_html__('Hello %s,','moga-travel'),'<strong>'.esc_html($guest_name).'</strong>');?>
    </p>
    <?php endif; ?>

    <p style="margin:0 0 28px;font-size:14px;color:<?php echo $muted;?>;line-height:1.6;">
      <?php printf(
        esc_html__('Your booking at %s is confirmed. Here are your full reservation details.','moga-travel'),
        '<strong style="color:'.$txt.';">'.esc_html($title).'</strong>'
      );?>
    </p>

    <!-- RESERVATION DETAILS -->
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:<?php echo $bg_out;?>;border-radius:8px;border:1px solid <?php echo $bdr;?>;margin-bottom:24px;">
    <tr><td style="padding:20px 24px;">
      <p style="margin:0 0 12px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;color:<?php echo $muted;?>;">
        <?php esc_html_e('Reservation Details','moga-travel');?>
      </p>
      <table width="100%" cellpadding="4" cellspacing="0" border="0">
        <tr>
          <td style="font-size:13px;color:<?php echo $muted;?>;width:42%;"><?php echo esc_html($is_tour?__('Tour','moga-travel'):__('Property','moga-travel'));?></td>
          <td style="font-size:13px;font-weight:600;"><?php echo esc_html($title);?></td>
        </tr>
        <tr>
          <td style="font-size:13px;color:<?php echo $muted;?>;"><?php echo esc_html($checkin_label);?></td>
          <td style="font-size:13px;font-weight:600;"><?php echo esc_html(moga_format_date_human($booking['check_in']));?></td>
        </tr>
        <tr>
          <td style="font-size:13px;color:<?php echo $muted;?>;"><?php echo esc_html($checkout_label);?></td>
          <td style="font-size:13px;font-weight:600;"><?php echo esc_html(moga_format_date_human($booking['check_out']));?></td>
        </tr>
        <?php if ($participants): ?>
        <tr>
          <td style="font-size:13px;color:<?php echo $muted;?>;"><?php esc_html_e('Guests','moga-travel');?></td>
          <td style="font-size:13px;font-weight:600;"><?php echo esc_html($participants);?></td>
        </tr>
        <?php endif; ?>
        <?php if (!empty($booked_seats)): ?>
        <tr>
          <td style="font-size:13px;color:<?php echo $muted;?>;"><?php esc_html_e('Your Seats','moga-travel');?></td>
          <td style="font-size:13px;font-weight:700;color:<?php echo $primary;?>;"><?php echo esc_html(implode(', ',$booked_seats));?></td>
        </tr>
        <?php endif; ?>
      </table>
    </td></tr>
    </table>

    <!-- PAYMENT SUMMARY -->
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:<?php echo $bg_out;?>;border-radius:8px;border:1px solid <?php echo $bdr;?>;margin-bottom:24px;">
    <tr><td style="padding:20px 24px;">
      <p style="margin:0 0 12px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;color:<?php echo $muted;?>;">
        <?php esc_html_e('Payment Summary','moga-travel');?>
      </p>
      <table width="100%" cellpadding="5" cellspacing="0" border="0">
        <tr>
          <td style="font-size:13px;color:<?php echo $muted;?>;"><?php esc_html_e('Total Amount','moga-travel');?></td>
          <td style="font-size:13px;font-weight:700;text-align:right;"><?php echo esc_html(moga_format_price((float)$booking['total_amount'],$currency));?></td>
        </tr>
        <tr>
          <td style="font-size:13px;color:<?php echo $muted;?>;"><?php esc_html_e('Amount Committed','moga-travel');?></td>
          <td style="font-size:13px;font-weight:600;text-align:right;"><?php echo esc_html(moga_format_price((float)$booking['total_amount']-$balance_due,$currency));?></td>
        </tr>
        <tr><td colspan="2" style="border-top:1px solid <?php echo $bdr;?>;padding:4px 0;"></td></tr>
        <tr>
          <td style="font-size:13px;font-weight:700;"><?php esc_html_e('Balance Due','moga-travel');?></td>
          <td style="font-size:14px;font-weight:700;text-align:right;color:<?php echo $is_fully_paid?$green:$red;?>;">
            <?php if ($is_fully_paid): ?>
              ✓ <?php esc_html_e('Fully Paid','moga-travel');?>
            <?php else: ?>
              <?php echo esc_html(moga_format_price($balance_due,$currency));?>
            <?php endif; ?>
          </td>
        </tr>
      </table>
    </td></tr>
    </table>

    <?php if (!$is_fully_paid): ?>
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#fffbeb;border:1px solid #fcd34d;border-radius:8px;margin-bottom:24px;">
    <tr><td style="padding:14px 20px;font-size:13px;color:#92400e;line-height:1.5;">
      ⏱ <?php echo esc_html($is_tour
        ?__('Your payment is pending until the organizer confirms receipt.','moga-travel')
        :__('Your payment is pending until the owner confirms receipt.','moga-travel'));?>
    </td></tr>
    </table>
    <?php endif; ?>

    <?php if ($cancel_info): ?>
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:<?php echo $bg_out;?>;border-radius:8px;border:1px solid <?php echo $bdr;?>;margin-bottom:28px;">
    <tr><td style="padding:16px 24px;">
      <p style="margin:0 0 6px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;color:<?php echo $muted;?>;">
        <?php esc_html_e('Cancellation Policy','moga-travel');?> — <?php echo esc_html($cancel_info['label']);?>
      </p>
      <p style="margin:0 0 8px;font-size:13px;line-height:1.5;"><?php echo esc_html($cancel_info['desc']);?></p>
      <a href="<?php echo esc_url($policy_page_url);?>" style="font-size:12px;color:<?php echo $primary;?>;">
        <?php esc_html_e('Read full policy →','moga-travel');?>
      </a>
    </td></tr>
    </table>
    <?php endif; ?>

    <table width="100%" cellpadding="0" cellspacing="0" border="0">
    <tr><td align="center">
      <a href="<?php echo esc_url($site_url);?>"
        style="display:inline-block;background:<?php echo $accent;?>;color:#fff;font-size:15px;font-weight:700;text-decoration:none;padding:14px 40px;border-radius:8px;">
        <?php esc_html_e('Back to Home','moga-travel');?>
      </a>
    </td></tr>
    </table>

  </td></tr>

  <!-- FOOTER -->
  <tr><td style="background:<?php echo $primary;?>;border-radius:0 0 12px 12px;padding:20px 40px;text-align:center;">
    <p style="margin:0 0 4px;font-size:12px;color:rgba(255,255,255,0.7);">
      <?php echo esc_html($site_name);?> &mdash;
      <a href="<?php echo esc_url($site_url);?>" style="color:rgba(255,255,255,0.85);text-decoration:none;"><?php echo esc_html(home_url());?></a>
    </p>
    <p style="margin:0;font-size:11px;color:rgba(255,255,255,0.5);">
      <?php esc_html_e('This email confirms your booking. Please keep it for your records.','moga-travel');?>
    </p>
  </td></tr>

</table>
</td></tr>
</table>
</body>
</html>
        <?php
        $html_body = ob_get_clean();

        add_filter('wp_mail_content_type', function() { return 'text/html'; });
        $sent = wp_mail($guest_email, $subject, $html_body);

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
        $listing_id = (int) $booking['listing_id'];
        $title      = get_the_title($listing_id);
        $thumbnail  = get_the_post_thumbnail_url($listing_id, 'moga-card');
        $currency   = $booking['currency'];
        $is_tour    = 'tour' === $booking['booking_type'];

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

        // Fetch booked seat numbers for this booking (bus tours only).
        $booked_seats = array();
        if ($core->seat_map && 'tour' === $booking['booking_type']) {
            $booked_seats = $core->seat_map->get_booked_seats($booking['id']);
        }
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
                            <span class="moga-confirmation__date-label"><?php echo esc_html($is_tour ? __('Departure', 'moga-travel') : __('Check-in', 'moga-travel')); ?></span>
                            <span class="moga-confirmation__date-value"><?php echo esc_html(moga_format_date_human($booking['check_in'])); ?></span>
                        </div>
                        <div class="moga-confirmation__date">
                            <span class="moga-confirmation__date-label"><?php echo esc_html($is_tour ? __('Return', 'moga-travel') : __('Check-out', 'moga-travel')); ?></span>
                            <span class="moga-confirmation__date-value"><?php echo esc_html(moga_format_date_human($booking['check_out'])); ?></span>
                        </div>
                    </div>
                    <?php if (! empty($booking['guests_adults'])) : ?>
                        <div class="moga-confirmation__guests">
                            <?php
                            $parts = array();
                            if ((int)$booking['guests_adults'] > 0)
                                $parts[] = sprintf(_n('%d Adult', '%d Adults', (int)$booking['guests_adults'], 'moga-travel'), (int)$booking['guests_adults']);
                            if ((int)$booking['guests_children'] > 0)
                                $parts[] = sprintf(_n('%d Child', '%d Children', (int)$booking['guests_children'], 'moga-travel'), (int)$booking['guests_children']);
                            if ((int)$booking['guests_infants'] > 0)
                                $parts[] = sprintf(_n('%d Infant', '%d Infants', (int)$booking['guests_infants'], 'moga-travel'), (int)$booking['guests_infants']);
                            echo esc_html(implode(' · ', $parts));
                            ?>
                        </div>
                    <?php endif; ?>
                    <?php if (! empty($booked_seats)) : ?>
                        <div class="moga-confirmation__seats">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <rect x="3" y="11" width="18" height="7" rx="2"/>
                                <path d="M5 11V7a7 7 0 0 1 14 0v4"/>
                            </svg>
                            <?php printf(
                                esc_html__('Your seats: %s', 'moga-travel'),
                                '<strong>' . esc_html(implode(', ', $booked_seats)) . '</strong>'
                            ); ?>
                        </div>
                    <?php endif; ?>
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
                <?php else : ?>
                    <div class="moga-price-breakdown__row moga-price-breakdown__row--paid">
                        <span><?php esc_html_e('Balance due', 'moga-travel'); ?></span>
                        <span class="moga-confirmation__fully-paid">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                            <?php esc_html_e('Fully paid', 'moga-travel'); ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($has_pending) : ?>
                <p class="moga-confirmation__status-note">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <circle cx="12" cy="12" r="10" />
                        <polyline points="12 6 12 12 16 14" />
                    </svg>
                    <?php echo esc_html($is_tour
                        ? __('Your payment is marked as pending until the organizer confirms it was received.', 'moga-travel')
                        : __('Your payment is marked as pending until the owner confirms it was received.', 'moga-travel')
                    ); ?>
                </p>
            <?php endif; ?>

            <?php
            // Second button: links to the Search Results page,
            // explicitly on the correct tab for whatever was booked.
            //
            // BUG FIX: previously tried get_post_type_archive_link(),
            // which silently failed (Tour has no real WordPress
            // archive page enabled) and fell through to a generic
            // Search Results link with no indication of which tab to
            // show — landing on whatever tab happens to be default
            // (Properties), regardless of what was actually booked.
            // The real, confirmed mechanism the search page actually
            // uses (template-search.php) is a plain '?type=tour' URL
            // parameter — used directly here instead of guessing.
            $listing_post_type = $booking['booking_type'];

            $search_page_url = get_option('moga_page_search_results')
                ? get_permalink(get_option('moga_page_search_results'))
                : home_url('/');

            $archive_url = 'tour' === $listing_post_type
                ? add_query_arg('type', 'tour', $search_page_url)
                : add_query_arg('type', 'property', $search_page_url);

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
