<?php
/**
 * Email Template — Vendor Application Approved
 *
 * Variables: $vendor (WP_User), $site_name, $site_url,
 *            $dashboard_url, $subject
 *
 * @package MogaTravelCore
 * @since   1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;
$primary  = '#003580'; $green = '#28a745'; $gray_100 = '#f8f9fa'; $gray_600 = '#6c757d'; $gray_900 = '#212529';
?>
<!DOCTYPE html><html lang="<?php echo esc_attr(get_locale());?>">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?php echo esc_html($subject);?></title>
<style>body{margin:0;padding:0;background:#f0f4f8;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;}.wrap{max-width:600px;margin:32px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08);}.hdr{background:<?php echo $primary;?>;padding:32px 40px;text-align:center;}.hdr h1{margin:0 0 4px;color:#fff;font-size:24px;font-weight:700;}.hdr p{margin:0;color:rgba(255,255,255,.7);font-size:13px;}.banner{background:<?php echo $green;?>;padding:16px 40px;text-align:center;color:#fff;font-size:15px;font-weight:600;}.body{padding:36px 40px;}.greeting{font-size:17px;font-weight:600;color:<?php echo $gray_900;?>;margin-bottom:8px;}.intro{font-size:14px;color:<?php echo $gray_600;?>;margin-bottom:28px;line-height:1.6;}.tick{text-align:center;font-size:64px;margin:24px 0;}.steps{background:<?php echo $gray_100;?>;border-radius:10px;padding:24px;margin-bottom:24px;}.step{display:flex;align-items:flex-start;gap:12px;margin-bottom:14px;font-size:14px;}.step:last-child{margin-bottom:0;}.step-num{background:<?php echo $primary;?>;color:#fff;width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;flex-shrink:0;}.cta{display:block;text-align:center;background:<?php echo $primary;?>;color:#fff;text-decoration:none;padding:14px 28px;border-radius:8px;font-size:15px;font-weight:700;margin:24px 0;}.ftr{background:<?php echo $gray_100;?>;padding:24px 40px;text-align:center;font-size:12px;color:<?php echo $gray_600;?>;}.ftr a{color:<?php echo $primary;?>;text-decoration:none;}</style>
</head><body><div class="wrap">
<div class="hdr"><h1><?php echo esc_html($site_name);?></h1><p><?php esc_html_e('Travel Booking Platform','moga-travel-core');?></p></div>
<div class="banner">🎉 <?php esc_html_e('Your Vendor Application is Approved!','moga-travel-core');?></div>
<div class="body">
    <div class="tick">✅</div>
    <p class="greeting"><?php printf(esc_html__('Congratulations, %s!','moga-travel-core'),esc_html($vendor->display_name));?></p>
    <p class="intro"><?php printf(esc_html__('We are thrilled to welcome you as a vendor on %s. You can now start listing your properties and tours.','moga-travel-core'),esc_html($site_name));?></p>
    <div class="steps">
        <div class="step"><div class="step-num">1</div><div><?php esc_html_e('Log in to your dashboard and complete your profile.','moga-travel-core');?></div></div>
        <div class="step"><div class="step-num">2</div><div><?php esc_html_e('Add your first property or tour listing.','moga-travel-core');?></div></div>
        <div class="step"><div class="step-num">3</div><div><?php esc_html_e('Start receiving bookings and earning revenue.','moga-travel-core');?></div></div>
    </div>
    <a href="<?php echo esc_url($dashboard_url);?>" class="cta"><?php esc_html_e('Go to My Dashboard','moga-travel-core');?></a>
</div>
<div class="ftr"><p><?php printf(esc_html__('© %s %s','moga-travel-core'),esc_html(date('Y')),esc_html($site_name));?></p><p><a href="<?php echo esc_url($site_url);?>"><?php echo esc_html($site_url);?></a></p></div>
</div></body></html>
