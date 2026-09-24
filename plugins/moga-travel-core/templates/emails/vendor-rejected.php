<?php
/**
 * Email Template — Vendor Application Rejected
 *
 * Variables: $vendor (WP_User), $site_name, $site_url, $subject
 *
 * @package MogaTravelCore
 * @since   1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;
$primary = '#003580'; $danger = '#dc2626'; $gray_100 = '#f8f9fa'; $gray_600 = '#6c757d'; $gray_900 = '#212529';
?>
<!DOCTYPE html><html lang="<?php echo esc_attr(get_locale());?>">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?php echo esc_html($subject);?></title>
<style>body{margin:0;padding:0;background:#f0f4f8;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;}.wrap{max-width:600px;margin:32px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08);}.hdr{background:<?php echo $primary;?>;padding:32px 40px;text-align:center;}.hdr h1{margin:0 0 4px;color:#fff;font-size:24px;font-weight:700;}.hdr p{margin:0;color:rgba(255,255,255,.7);font-size:13px;}.banner{background:<?php echo $danger;?>;padding:16px 40px;text-align:center;color:#fff;font-size:15px;font-weight:600;}.body{padding:36px 40px;}.greeting{font-size:17px;font-weight:600;color:<?php echo $gray_900;?>;margin-bottom:8px;}.intro{font-size:14px;color:<?php echo $gray_600;?>;margin-bottom:24px;line-height:1.6;}.notice{background:#fef2f2;border-left:4px solid <?php echo $danger;?>;padding:14px 18px;border-radius:0 8px 8px 0;font-size:13px;color:#7f1d1d;line-height:1.6;margin-bottom:24px;}.cta{display:block;text-align:center;background:<?php echo $primary;?>;color:#fff;text-decoration:none;padding:14px 28px;border-radius:8px;font-size:15px;font-weight:700;margin:24px 0;}.ftr{background:<?php echo $gray_100;?>;padding:24px 40px;text-align:center;font-size:12px;color:<?php echo $gray_600;?>;}.ftr a{color:<?php echo $primary;?>;text-decoration:none;}</style>
</head><body><div class="wrap">
<div class="hdr"><h1><?php echo esc_html($site_name);?></h1><p><?php esc_html_e('Travel Booking Platform','moga-travel-core');?></p></div>
<div class="banner"><?php esc_html_e('Update on Your Vendor Application','moga-travel-core');?></div>
<div class="body">
    <p class="greeting"><?php printf(esc_html__('Hello, %s.','moga-travel-core'),esc_html($vendor->display_name));?></p>
    <p class="intro"><?php printf(esc_html__('Thank you for your interest in becoming a vendor on %s. After reviewing your application, we are unfortunately unable to approve it at this time.','moga-travel-core'),esc_html($site_name));?></p>
    <div class="notice"><?php esc_html_e('This decision may be due to incomplete documentation, or our current vendor capacity. You are welcome to reapply in the future with updated information.','moga-travel-core');?></div>
    <p style="font-size:14px;color:<?php echo $gray_600;?>;line-height:1.6;"><?php esc_html_e('If you have questions or would like to appeal this decision, please contact us through the website.','moga-travel-core');?></p>
    <a href="<?php echo esc_url($site_url.'/contact');?>" class="cta"><?php esc_html_e('Contact Us','moga-travel-core');?></a>
</div>
<div class="ftr"><p><?php printf(esc_html__('© %s %s','moga-travel-core'),esc_html(date('Y')),esc_html($site_name));?></p><p><a href="<?php echo esc_url($site_url);?>"><?php echo esc_html($site_url);?></a></p></div>
</div></body></html>
