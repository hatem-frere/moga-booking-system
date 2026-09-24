<?php
/**
 * Email Template — Booking Confirmation (Guest)
 *
 * Variables available:
 *   $booking        — booking row array from mg_moga_bookings
 *   $listing_title  — property or tour name
 *   $guest          — WP_User object
 *   $site_name      — get_bloginfo('name')
 *   $site_url       — home_url()
 *   $currency       — currency symbol
 *   $dashboard_url  — frontend dashboard URL
 *
 * @package MogaTravelCore
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$primary   = '#003580';
$accent    = '#f5a623';
$gray_100  = '#f8f9fa';
$gray_600  = '#6c757d';
$gray_900  = '#212529';
$white     = '#ffffff';
$green     = '#28a745';

$status_labels = array(
    'pending'   => __( 'Pending Review', 'moga-travel-core' ),
    'confirmed' => __( 'Confirmed',      'moga-travel-core' ),
    'cancelled' => __( 'Cancelled',      'moga-travel-core' ),
    'completed' => __( 'Completed',      'moga-travel-core' ),
);
$status_label = $status_labels[ $booking['status'] ] ?? ucfirst( $booking['status'] );

$type_labels = array(
    'property' => __( 'Property / Hotel', 'moga-travel-core' ),
    'tour'     => __( 'Tour',             'moga-travel-core' ),
    'bus'      => __( 'Bus Seat',         'moga-travel-core' ),
    'rental'   => __( 'Rental',           'moga-travel-core' ),
);
$type_label = $type_labels[ $booking['booking_type'] ] ?? ucfirst( $booking['booking_type'] );

$check_in  = date_i18n( get_option( 'date_format' ), strtotime( $booking['check_in'] ) );
$check_out = date_i18n( get_option( 'date_format' ), strtotime( $booking['check_out'] ) );
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
        .email-header h1 { margin: 0 0 4px; color: #fff; font-size: 24px; font-weight: 700; letter-spacing: -.5px; }
        .email-header p { margin: 0; color: rgba(255,255,255,.7); font-size: 13px; }
        .email-banner { background: <?php echo $accent; ?>; padding: 16px 40px; text-align: center; color: #fff; font-size: 15px; font-weight: 600; }
        .email-body { padding: 36px 40px; }
        .greeting { font-size: 17px; font-weight: 600; color: <?php echo $gray_900; ?>; margin-bottom: 8px; }
        .intro { font-size: 14px; color: <?php echo $gray_600; ?>; margin-bottom: 28px; line-height: 1.6; }
        .booking-box { background: <?php echo $gray_100; ?>; border-radius: 10px; padding: 24px; margin-bottom: 28px; }
        .booking-number { font-family: monospace; font-size: 18px; font-weight: 800; color: <?php echo $primary; ?>; letter-spacing: 1px; margin-bottom: 16px; display: block; }
        .detail-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e9ecef; font-size: 14px; }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { color: <?php echo $gray_600; ?>; font-weight: 500; }
        .detail-value { color: <?php echo $gray_900; ?>; font-weight: 600; text-align: right; }
        .total-row { background: <?php echo $primary; ?>; color: #fff; border-radius: 8px; padding: 14px 18px; display: flex; justify-content: space-between; font-size: 16px; font-weight: 700; margin-top: 16px; }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; background: <?php echo $green; ?>; color: #fff; }
        .cta-btn { display: block; text-align: center; background: <?php echo $primary; ?>; color: #fff; text-decoration: none; padding: 14px 28px; border-radius: 8px; font-size: 15px; font-weight: 700; margin: 24px 0; }
        .notice { background: #fff3cd; border-left: 4px solid <?php echo $accent; ?>; padding: 14px 18px; border-radius: 0 8px 8px 0; font-size: 13px; color: #856404; line-height: 1.6; margin-bottom: 24px; }
        .email-footer { background: <?php echo $gray_100; ?>; padding: 24px 40px; text-align: center; font-size: 12px; color: <?php echo $gray_600; ?>; }
        .email-footer a { color: <?php echo $primary; ?>; text-decoration: none; }
    </style>
</head>
<body>
<div class="email-wrap">

    <div class="email-header">
        <h1><?php echo esc_html( $site_name ); ?></h1>
        <p><?php esc_html_e( 'Travel Booking Platform', 'moga-travel-core' ); ?></p>
    </div>

    <div class="email-banner">
        <?php esc_html_e( '🎉 Booking Received — Pending Confirmation', 'moga-travel-core' ); ?>
    </div>

    <div class="email-body">

        <p class="greeting">
            <?php printf( esc_html__( 'Hello, %s!', 'moga-travel-core' ), esc_html( $guest->display_name ) ); ?>
        </p>
        <p class="intro">
            <?php esc_html_e( 'Thank you for your booking. Your request has been received and is now pending review by the property/tour owner. You will receive another email once it is confirmed.', 'moga-travel-core' ); ?>
        </p>

        <div class="booking-box">
            <span class="booking-number">
                <?php echo esc_html( $booking['booking_number'] ); ?>
            </span>

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
            <?php if ( $booking['guests_adults'] > 0 ) : ?>
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
            <div class="detail-row">
                <span class="detail-label"><?php esc_html_e( 'Status', 'moga-travel-core' ); ?></span>
                <span class="detail-value">
                    <span class="status-badge"><?php echo esc_html( $status_label ); ?></span>
                </span>
            </div>

            <div class="total-row">
                <span><?php esc_html_e( 'Total Amount', 'moga-travel-core' ); ?></span>
                <span><?php echo esc_html( $currency . number_format( (float) $booking['total_amount'], 2 ) ); ?></span>
            </div>

            <?php if ( (float) $booking['deposit_amount'] > 0 && (float) $booking['deposit_amount'] < (float) $booking['total_amount'] ) : ?>
            <div style="text-align:right; margin-top:8px; font-size:13px; color:<?php echo $gray_600; ?>;">
                <?php printf(
                    esc_html__( 'Deposit required: %s', 'moga-travel-core' ),
                    esc_html( $currency . number_format( (float) $booking['deposit_amount'], 2 ) )
                ); ?>
            </div>
            <?php endif; ?>
        </div>

        <?php if ( $booking['special_requests'] ) : ?>
        <div class="notice">
            <strong><?php esc_html_e( 'Your special requests:', 'moga-travel-core' ); ?></strong><br>
            <?php echo nl2br( esc_html( $booking['special_requests'] ) ); ?>
        </div>
        <?php endif; ?>

        <a href="<?php echo esc_url( $dashboard_url ); ?>" class="cta-btn">
            <?php esc_html_e( 'View My Bookings', 'moga-travel-core' ); ?>
        </a>

        <p style="font-size:13px; color:<?php echo $gray_600; ?>; line-height:1.6;">
            <?php esc_html_e( 'If you have any questions, please contact us through the website.', 'moga-travel-core' ); ?>
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
        <p>
            <a href="<?php echo esc_url( $site_url ); ?>"><?php echo esc_html( $site_url ); ?></a>
        </p>
    </div>

</div>
</body>
</html>
