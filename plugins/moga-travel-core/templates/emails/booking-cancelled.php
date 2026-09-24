<?php
/**
 * Email Template — Booking Cancelled
 *
 * Sent to both guest and owner when a booking is cancelled.
 *
 * Variables: $booking, $listing_title, $recipient (WP_User),
 *            $cancelled_by ('guest'|'owner'|'system'),
 *            $site_name, $site_url, $dashboard_url, $subject
 *
 * @package MogaTravelCore
 * @since   1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$primary  = '#003580';
$danger   = '#dc2626';
$gray_100 = '#f8f9fa';
$gray_600 = '#6c757d';
$gray_900 = '#212529';

$check_in  = date_i18n( get_option( 'date_format' ), strtotime( $booking['check_in'] ) );
$check_out = date_i18n( get_option( 'date_format' ), strtotime( $booking['check_out'] ) );
$reason    = ! empty( $booking['cancellation_reason'] ) ? $booking['cancellation_reason'] : __( 'No reason provided.', 'moga-travel-core' );
?>
<!DOCTYPE html>
<html lang="<?php echo esc_attr( get_locale() ); ?>">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo esc_html( $subject ); ?></title>
<style>
body{margin:0;padding:0;background:#f0f4f8;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;}
.wrap{max-width:600px;margin:32px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08);}
.hdr{background:<?php echo $primary;?>;padding:32px 40px;text-align:center;}
.hdr h1{margin:0 0 4px;color:#fff;font-size:24px;font-weight:700;}
.hdr p{margin:0;color:rgba(255,255,255,.7);font-size:13px;}
.banner{background:<?php echo $danger;?>;padding:16px 40px;text-align:center;color:#fff;font-size:15px;font-weight:600;}
.body{padding:36px 40px;}
.greeting{font-size:17px;font-weight:600;color:<?php echo $gray_900;?>;margin-bottom:8px;}
.intro{font-size:14px;color:<?php echo $gray_600;?>;margin-bottom:28px;line-height:1.6;}
.box{background:<?php echo $gray_100;?>;border-radius:10px;padding:24px;margin-bottom:24px;}
.bnum{font-family:monospace;font-size:18px;font-weight:800;color:<?php echo $danger;?>;display:block;margin-bottom:16px;}
.row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #e9ecef;font-size:14px;}
.row:last-child{border-bottom:none;}
.lbl{color:<?php echo $gray_600;?>;font-weight:500;}
.val{color:<?php echo $gray_900;?>;font-weight:600;text-align:right;}
.reason-box{border-left:4px solid <?php echo $danger;?>;background:#fef2f2;padding:14px 18px;border-radius:0 8px 8px 0;font-size:13px;color:#7f1d1d;line-height:1.6;margin-bottom:24px;}
.cta{display:block;text-align:center;background:<?php echo $primary;?>;color:#fff;text-decoration:none;padding:14px 28px;border-radius:8px;font-size:15px;font-weight:700;margin:24px 0;}
.ftr{background:<?php echo $gray_100;?>;padding:24px 40px;text-align:center;font-size:12px;color:<?php echo $gray_600;?>;}
.ftr a{color:<?php echo $primary;?>;text-decoration:none;}
</style>
</head>
<body>
<div class="wrap">
<div class="hdr"><h1><?php echo esc_html( $site_name ); ?></h1><p><?php esc_html_e( 'Travel Booking Platform', 'moga-travel-core' ); ?></p></div>
<div class="banner">❌ <?php esc_html_e( 'Booking Cancelled', 'moga-travel-core' ); ?></div>
<div class="body">
    <p class="greeting"><?php printf( esc_html__( 'Hello, %s!', 'moga-travel-core' ), esc_html( $recipient->display_name ) ); ?></p>
    <p class="intro"><?php printf( esc_html__( 'We regret to inform you that booking %s for "%s" has been cancelled.', 'moga-travel-core' ), '<strong>' . esc_html( $booking['booking_number'] ) . '</strong>', esc_html( $listing_title ) ); ?></p>
    <div class="box">
        <span class="bnum"><?php echo esc_html( $booking['booking_number'] ); ?></span>
        <div class="row"><span class="lbl"><?php esc_html_e( 'Listing', 'moga-travel-core' ); ?></span><span class="val"><?php echo esc_html( $listing_title ); ?></span></div>
        <div class="row"><span class="lbl"><?php esc_html_e( 'Check-in', 'moga-travel-core' ); ?></span><span class="val"><?php echo esc_html( $check_in ); ?></span></div>
        <div class="row"><span class="lbl"><?php esc_html_e( 'Check-out', 'moga-travel-core' ); ?></span><span class="val"><?php echo esc_html( $check_out ); ?></span></div>
    </div>
    <div class="reason-box"><strong><?php esc_html_e( 'Cancellation reason:', 'moga-travel-core' ); ?></strong><br><?php echo nl2br( esc_html( $reason ) ); ?></div>
    <a href="<?php echo esc_url( $dashboard_url ); ?>" class="cta"><?php esc_html_e( 'Go to My Dashboard', 'moga-travel-core' ); ?></a>
    <p style="font-size:13px;color:<?php echo $gray_600;?>;line-height:1.6;"><?php esc_html_e( 'If you believe this is an error or have questions, please contact us.', 'moga-travel-core' ); ?></p>
</div>
<div class="ftr"><p><?php printf( esc_html__( '© %s %s', 'moga-travel-core' ), esc_html( date('Y') ), esc_html( $site_name ) ); ?></p><p><a href="<?php echo esc_url( $site_url ); ?>"><?php echo esc_html( $site_url ); ?></a></p></div>
</div>
</body></html>
