<?php
/**
 * Email Template — New Booking Notification (Owner / Organizer)
 *
 * Variables available:
 *   $booking        — booking row array from mg_moga_bookings
 *   $listing_title  — property or tour name
 *   $guest          — WP_User object (the guest who booked)
 *   $owner          — WP_User object (the property/tour owner)
 *   $site_name      — get_bloginfo('name')
 *   $site_url       — home_url()
 *   $currency       — currency symbol
 *   $dashboard_url  — frontend dashboard URL
 *   $subject        — email subject line
 *
 * @package MogaTravelCore
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$primary  = '#003580';
$accent   = '#f5a623';
$gray_100 = '#f8f9fa';
$gray_600 = '#6c757d';
$gray_900 = '#212529';
$white    = '#ffffff';

$check_in  = date_i18n( get_option( 'date_format' ), strtotime( $booking['check_in'] ) );
$check_out = date_i18n( get_option( 'date_format' ), strtotime( $booking['check_out'] ) );

$type_labels = array(
    'property' => __( 'Property / Hotel', 'moga-travel-core' ),
    'tour'     => __( 'Tour',             'moga-travel-core' ),
    'bus'      => __( 'Bus Seat',         'moga-travel-core' ),
    'rental'   => __( 'Rental',           'moga-travel-core' ),
);
$type_label = $type_labels[ $booking['booking_type'] ] ?? ucfirst( $booking['booking_type'] );
?>
<!DOCTYPE html>
<html lang="<?php echo esc_attr( get_locale() ); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html( $subject ); ?></title>
    <style>
        body { margin: 0; padding: 0; background: #f0f4f8; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
        .email-wrap { max-width: 600px; margin: 32px auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,.08); }
        .email-header { background: <?php echo $primary; ?>; padding: 32px 40px; text-align: center; }
        .email-header h1 { margin: 0 0 4px; color: #fff; font-size: 24px; font-weight: 700; }
        .email-header p { margin: 0; color: rgba(255,255,255,.7); font-size: 13px; }
        .email-banner { background: <?php echo $accent; ?>; padding: 16px 40px; text-align: center; color: #fff; font-size: 15px; font-weight: 600; }
        .email-body { padding: 36px 40px; }
        .greeting { font-size: 17px; font-weight: 600; color: <?php echo $gray_900; ?>; margin-bottom: 8px; }
        .intro { font-size: 14px; color: <?php echo $gray_600; ?>; margin-bottom: 28px; line-height: 1.6; }
        .section-title { font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: <?php echo $gray_600; ?>; margin-bottom: 12px; margin-top: 24px; }
        .booking-box { background: <?php echo $gray_100; ?>; border-radius: 10px; padding: 24px; margin-bottom: 24px; }
        .booking-number { font-family: monospace; font-size: 18px; font-weight: 800; color: <?php echo $primary; ?>; letter-spacing: 1px; margin-bottom: 16px; display: block; }
        .detail-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e9ecef; font-size: 14px; }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { color: <?php echo $gray_600; ?>; font-weight: 500; }
        .detail-value { color: <?php echo $gray_900; ?>; font-weight: 600; text-align: right; }
        .total-row { background: <?php echo $primary; ?>; color: #fff; border-radius: 8px; padding: 14px 18px; display: flex; justify-content: space-between; font-size: 16px; font-weight: 700; margin-top: 16px; }
        .guest-box { border: 1px solid #dee2e6; border-radius: 10px; padding: 18px 24px; margin-bottom: 24px; }
        .guest-name { font-size: 16px; font-weight: 700; color: <?php echo $gray_900; ?>; }
        .guest-email { font-size: 13px; color: <?php echo $gray_600; ?>; margin-top: 2px; }
        .action-btns { display: flex; gap: 12px; margin: 24px 0; }
        .btn-approve { flex: 1; display: block; text-align: center; background: #28a745; color: #fff; text-decoration: none; padding: 12px; border-radius: 8px; font-size: 14px; font-weight: 700; }
        .btn-view { flex: 1; display: block; text-align: center; background: <?php echo $primary; ?>; color: #fff; text-decoration: none; padding: 12px; border-radius: 8px; font-size: 14px; font-weight: 700; }
        .email-footer { background: <?php echo $gray_100; ?>; padding: 24px 40px; text-align: center; font-size: 12px; color: <?php echo $gray_600; ?>; }
        .email-footer a { color: <?php echo $primary; ?>; text-decoration: none; }
    </style>
</head>
<body>
<div class="email-wrap">

    <div class="email-header">
        <h1><?php echo esc_html( $site_name ); ?></h1>
        <p><?php esc_html_e( 'Vendor Dashboard Notification', 'moga-travel-core' ); ?></p>
    </div>

    <div class="email-banner">
        <?php esc_html_e( '🔔 New Booking Request — Action Required', 'moga-travel-core' ); ?>
    </div>

    <div class="email-body">

        <p class="greeting">
            <?php printf( esc_html__( 'Hello, %s!', 'moga-travel-core' ), esc_html( $owner->display_name ) ); ?>
        </p>
        <p class="intro">
            <?php printf(
                esc_html__( 'You have received a new booking request for "%s". Please review and respond as soon as possible.', 'moga-travel-core' ),
                esc_html( $listing_title )
            ); ?>
        </p>

        <div class="section-title"><?php esc_html_e( 'Booking Details', 'moga-travel-core' ); ?></div>
        <div class="booking-box">
            <span class="booking-number"><?php echo esc_html( $booking['booking_number'] ); ?></span>

            <div class="detail-row">
                <span class="detail-label"><?php esc_html_e( 'Listing', 'moga-travel-core' ); ?></span>
                <span class="detail-value"><?php echo esc_html( $listing_title ); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label"><?php esc_html_e( 'Type', 'moga-travel-core' ); ?></span>
                <span class="detail-value"><?php echo esc_html( $type_label ); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label"><?php esc_html_e( 'Check-in', 'moga-travel-core' ); ?></span>
                <span class="detail-value"><?php echo esc_html( $check_in ); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label"><?php esc_html_e( 'Check-out', 'moga-travel-core' ); ?></span>
                <span class="detail-value"><?php echo esc_html( $check_out ); ?></span>
            </div>
            <?php if ( $booking['guests_adults'] ) : ?>
            <div class="detail-row">
                <span class="detail-label"><?php esc_html_e( 'Guests', 'moga-travel-core' ); ?></span>
                <span class="detail-value">
                    <?php
                    $parts = array();
                    if ( $booking['guests_adults'] )   $parts[] = $booking['guests_adults']   . ' ' . _n( 'Adult',  'Adults',   $booking['guests_adults'],   'moga-travel-core' );
                    if ( $booking['guests_children'] ) $parts[] = $booking['guests_children'] . ' ' . _n( 'Child',  'Children', $booking['guests_children'], 'moga-travel-core' );
                    if ( $booking['guests_infants'] )  $parts[] = $booking['guests_infants']  . ' ' . _n( 'Infant', 'Infants',  $booking['guests_infants'],  'moga-travel-core' );
                    echo esc_html( implode( ', ', $parts ) );
                    ?>
                </span>
            </div>
            <?php endif; ?>

            <div class="total-row">
                <span><?php esc_html_e( 'Total Amount', 'moga-travel-core' ); ?></span>
                <span><?php echo esc_html( $currency . number_format( (float) $booking['total_amount'], 2 ) ); ?></span>
            </div>
        </div>

        <div class="section-title"><?php esc_html_e( 'Guest Information', 'moga-travel-core' ); ?></div>
        <div class="guest-box">
            <div class="guest-name"><?php echo esc_html( $guest->display_name ); ?></div>
            <div class="guest-email"><?php echo esc_html( $guest->user_email ); ?></div>
        </div>

        <?php if ( $booking['special_requests'] ) : ?>
        <div class="section-title"><?php esc_html_e( 'Special Requests', 'moga-travel-core' ); ?></div>
        <p style="font-size:14px; color:<?php echo $gray_600; ?>; background:<?php echo $gray_100; ?>; padding:14px 18px; border-radius:8px; line-height:1.6;">
            <?php echo nl2br( esc_html( $booking['special_requests'] ) ); ?>
        </p>
        <?php endif; ?>

        <div class="action-btns">
            <a href="<?php echo esc_url( add_query_arg( 'tab', 'bookings', $dashboard_url ) ); ?>"
               class="btn-approve">
                <?php esc_html_e( '✓ Review & Approve', 'moga-travel-core' ); ?>
            </a>
            <a href="<?php echo esc_url( $dashboard_url ); ?>" class="btn-view">
                <?php esc_html_e( 'Go to Dashboard', 'moga-travel-core' ); ?>
            </a>
        </div>

        <p style="font-size:12px; color:<?php echo $gray_600; ?>; line-height:1.6;">
            <?php esc_html_e( 'Please respond to this booking request promptly. Delayed responses affect your listing ranking.', 'moga-travel-core' ); ?>
        </p>

    </div>

    <div class="email-footer">
        <p>
            <?php printf(
                esc_html__( '© %s %s · All rights reserved', 'moga-travel-core' ),
                esc_html( date( 'Y' ) ),
                esc_html( $site_name )
            ); ?>
        </p>
        <p><a href="<?php echo esc_url( $site_url ); ?>"><?php echo esc_html( $site_url ); ?></a></p>
    </div>

</div>
</body>
</html>
