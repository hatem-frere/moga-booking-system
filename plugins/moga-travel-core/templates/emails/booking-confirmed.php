<?php
/**
 * Email Template — Booking Confirmed (Guest)
 *
 * Sent when owner/admin changes booking status to 'confirmed'.
 *
 * Variables: $booking, $listing_title, $guest, $site_name,
 *            $site_url, $currency, $dashboard_url, $subject
 *
 * @package MogaTravelCore
 * @since   1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$primary  = '#003580';
$accent   = '#28a745';
$gray_100 = '#f8f9fa';
$gray_600 = '#6c757d';
$gray_900 = '#212529';

$check_in  = date_i18n( get_option( 'date_format' ), strtotime( $booking['check_in'] ) );
$check_out = date_i18n( get_option( 'date_format' ), strtotime( $booking['check_out'] ) );
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
.banner{background:<?php echo $accent;?>;padding:16px 40px;text-align:center;color:#fff;font-size:15px;font-weight:600;}
.body{padding:36px 40px;}
.greeting{font-size:17px;font-weight:600;color:<?php echo $gray_900;?>;margin-bottom:8px;}
.intro{font-size:14px;color:<?php echo $gray_600;?>;margin-bottom:28px;line-height:1.6;}
.box{background:<?php echo $gray_100;?>;border-radius:10px;padding:24px;margin-bottom:24px;}
.bnum{font-family:monospace;font-size:18px;font-weight:800;color:<?php echo $primary;?>;display:block;margin-bottom:16px;}
.row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #e9ecef;font-size:14px;}
.row:last-child{border-bottom:none;}
.lbl{color:<?php echo $gray_600;?>;font-weight:500;}
.val{color:<?php echo $gray_900;?>;font-weight:600;text-align:right;}
.total{background:<?php echo $primary;?>;color:#fff;border-radius:8px;padding:14px 18px;display:flex;justify-content:space-between;font-size:16px;font-weight:700;margin-top:16px;}
.badge{display:inline-block;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700;background:<?php echo $accent;?>;color:#fff;}
.cta{display:block;text-align:center;background:<?php echo $primary;?>;color:#fff;text-decoration:none;padding:14px 28px;border-radius:8px;font-size:15px;font-weight:700;margin:24px 0;}
.ftr{background:<?php echo $gray_100;?>;padding:24px 40px;text-align:center;font-size:12px;color:<?php echo $gray_600;?>;}
.ftr a{color:<?php echo $primary;?>;text-decoration:none;}
</style>
</head>
<body>
<div class="wrap">
<div class="hdr"><h1><?php echo esc_html( $site_name ); ?></h1><p><?php esc_html_e( 'Travel Booking Platform', 'moga-travel-core' ); ?></p></div>
<div class="banner">✅ <?php esc_html_e( 'Your Booking is Confirmed!', 'moga-travel-core' ); ?></div>
<div class="body">
    <p class="greeting"><?php printf( esc_html__( 'Hello, %s!', 'moga-travel-core' ), esc_html( $guest->display_name ) ); ?></p>
    <p class="intro"><?php printf( esc_html__( 'Great news! Your booking for "%s" has been confirmed by the host. Everything is all set for your trip.', 'moga-travel-core' ), esc_html( $listing_title ) ); ?></p>
    <div class="box">
        <span class="bnum"><?php echo esc_html( $booking['booking_number'] ); ?></span>
        <div class="row"><span class="lbl"><?php esc_html_e( 'Listing', 'moga-travel-core' ); ?></span><span class="val"><?php echo esc_html( $listing_title ); ?></span></div>
        <div class="row"><span class="lbl"><?php esc_html_e( 'Check-in', 'moga-travel-core' ); ?></span><span class="val"><?php echo esc_html( $check_in ); ?></span></div>
        <div class="row"><span class="lbl"><?php esc_html_e( 'Check-out', 'moga-travel-core' ); ?></span><span class="val"><?php echo esc_html( $check_out ); ?></span></div>
        <div class="row"><span class="lbl"><?php esc_html_e( 'Status', 'moga-travel-core' ); ?></span><span class="val"><span class="badge"><?php esc_html_e( 'Confirmed', 'moga-travel-core' ); ?></span></span></div>
        <div class="total"><span><?php esc_html_e( 'Total Amount', 'moga-travel-core' ); ?></span><span><?php echo esc_html( $currency . number_format( (float) $booking['total_amount'], 2 ) ); ?></span></div>
    </div>
    <a href="<?php echo esc_url( $dashboard_url ); ?>" class="cta"><?php esc_html_e( 'View My Bookings', 'moga-travel-core' ); ?></a>
</div>
<div class="ftr"><p><?php printf( esc_html__( '© %s %s', 'moga-travel-core' ), esc_html( date('Y') ), esc_html( $site_name ) ); ?></p><p><a href="<?php echo esc_url( $site_url ); ?>"><?php echo esc_html( $site_url ); ?></a></p></div>
</div>
</body></html>
