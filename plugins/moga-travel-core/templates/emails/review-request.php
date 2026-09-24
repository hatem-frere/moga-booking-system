<?php
/**
 * Email Template — Review Request (Guest)
 *
 * Sent after booking status changes to 'completed'.
 *
 * Variables: $booking, $listing_title, $guest, $site_name,
 *            $site_url, $review_url, $subject
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
.stars{text-align:center;font-size:36px;margin:24px 0;}
.listing-name{text-align:center;font-size:20px;font-weight:700;color:<?php echo $gray_900;?>;margin-bottom:8px;}
.listing-dates{text-align:center;font-size:13px;color:<?php echo $gray_600;?>;margin-bottom:28px;}
.cta{display:block;text-align:center;background:<?php echo $accent;?>;color:#fff;text-decoration:none;padding:16px 28px;border-radius:8px;font-size:16px;font-weight:700;margin:24px 0;}
.note{font-size:12px;color:<?php echo $gray_600;?>;text-align:center;line-height:1.6;}
.ftr{background:<?php echo $gray_100;?>;padding:24px 40px;text-align:center;font-size:12px;color:<?php echo $gray_600;?>;}
.ftr a{color:<?php echo $primary;?>;text-decoration:none;}
</style>
</head>
<body>
<div class="wrap">
<div class="hdr"><h1><?php echo esc_html( $site_name ); ?></h1><p><?php esc_html_e( 'Travel Booking Platform', 'moga-travel-core' ); ?></p></div>
<div class="banner">⭐ <?php esc_html_e( 'How was your experience?', 'moga-travel-core' ); ?></div>
<div class="body">
    <p class="greeting"><?php printf( esc_html__( 'Hello, %s!', 'moga-travel-core' ), esc_html( $guest->display_name ) ); ?></p>
    <p class="intro"><?php esc_html_e( 'We hope you had a wonderful experience. Your feedback helps other travelers make informed decisions — and it only takes a minute!', 'moga-travel-core' ); ?></p>
    <div class="stars">⭐⭐⭐⭐⭐</div>
    <div class="listing-name"><?php echo esc_html( $listing_title ); ?></div>
    <div class="listing-dates">
        <?php printf(
            esc_html__( 'Stay: %s – %s · Booking %s', 'moga-travel-core' ),
            esc_html( date_i18n( get_option('date_format'), strtotime( $booking['check_in'] ) ) ),
            esc_html( date_i18n( get_option('date_format'), strtotime( $booking['check_out'] ) ) ),
            esc_html( $booking['booking_number'] )
        ); ?>
    </div>
    <a href="<?php echo esc_url( $review_url ); ?>" class="cta"><?php esc_html_e( '✍ Write a Review', 'moga-travel-core' ); ?></a>
    <p class="note"><?php esc_html_e( 'Reviews are public and help the host improve their service.', 'moga-travel-core' ); ?></p>
</div>
<div class="ftr"><p><?php printf( esc_html__( '© %s %s', 'moga-travel-core' ), esc_html( date('Y') ), esc_html( $site_name ) ); ?></p><p><a href="<?php echo esc_url( $site_url ); ?>"><?php echo esc_html( $site_url ); ?></a></p></div>
</div>
</body></html>
