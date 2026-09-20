<?php

/**
 * Admin Dashboard — Overview (Card Grid)
 *
 * Shown when admin visits /dashboard/ with no ?tab param.
 * Displays all dashboard sections as grouped cards — exactly like
 * Booking.com's account page. Each card has a section title and
 * a list of clickable links that go to the full tab page.
 *
 * No sidebar on this page — the card grid IS the navigation.
 *
 * @package MogaTravel
 * @since   1.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

$user    = $args['user']    ?? wp_get_current_user();
$user_id = $args['user_id'] ?? get_current_user_id();

$dashboard_url = get_permalink(get_option('moga_page_dashboard'));

// Helper to build a tab URL.
$tab_url = fn($tab) => add_query_arg('tab', $tab, $dashboard_url);

// Total pending bookings across all vendors.
global $wpdb;
$pending_bookings = (int) $wpdb->get_var(
    "SELECT COUNT(*) FROM {$wpdb->prefix}moga_bookings WHERE status = 'pending'"
);
$pending_vendors = (int) $wpdb->get_var(
    "SELECT COUNT(*) FROM {$wpdb->prefix}usermeta
     WHERE meta_key = 'moga_vendor_status' AND meta_value = 'pending'"
);

// Display name for greeting.
$display_name = $user->display_name ?: $user->user_login;
$first_name   = explode(' ', trim($display_name))[0];

// Avatar initials.
$name_parts      = explode(' ', trim($display_name));
$avatar_initials = strtoupper(
    substr($name_parts[0], 0, 1) .
    (isset($name_parts[1]) ? substr($name_parts[1], 0, 1) : '')
);
$avatar_url = get_user_meta($user_id, '_moga_avatar', true);
?>

<div class="moga-db-admin-overview">

    <?php // ---- Welcome banner ---- ?>
    <div class="moga-db-admin-overview__banner">
        <div class="moga-db-admin-overview__banner-avatar">
            <?php if ($avatar_url) : ?>
                <img src="<?php echo esc_url($avatar_url); ?>"
                     alt="<?php echo esc_attr($display_name); ?>">
            <?php else : ?>
                <?php echo esc_html($avatar_initials); ?>
            <?php endif; ?>
        </div>
        <div>
            <h1 class="moga-db-admin-overview__greeting">
                <?php printf(
                    /* translators: %s: first name */
                    esc_html__('Welcome back, %s', 'moga-travel'),
                    esc_html($first_name)
                ); ?>
            </h1>
            <p class="moga-db-admin-overview__role">
                <?php esc_html_e('Administrator · Moga Booking System', 'moga-travel'); ?>
            </p>
        </div>

        <?php // Quick alert badges ?>
        <div class="moga-db-admin-overview__alerts">
            <?php if ($pending_bookings > 0) : ?>
                <a href="<?php echo esc_url($tab_url('all-bookings')); ?>"
                    class="moga-db-admin-overview__alert moga-db-admin-overview__alert--warning">
                    <strong><?php echo esc_html($pending_bookings); ?></strong>
                    <?php esc_html_e('pending bookings', 'moga-travel'); ?>
                </a>
            <?php endif; ?>
            <?php if ($pending_vendors > 0) : ?>
                <a href="<?php echo esc_url($tab_url('vendors')); ?>"
                    class="moga-db-admin-overview__alert moga-db-admin-overview__alert--info">
                    <strong><?php echo esc_html($pending_vendors); ?></strong>
                    <?php esc_html_e('vendor applications', 'moga-travel'); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php // ---- Card grid ---- ?>
    <div class="moga-db-admin-overview__grid">

        <?php // ---- Platform Management ---- ?>
        <div class="moga-db-admin-overview__card">
            <h2 class="moga-db-admin-overview__card-title">
                <?php esc_html_e('Platform Management', 'moga-travel'); ?>
            </h2>
            <ul class="moga-db-admin-overview__card-links">
                <li>
                    <a href="<?php echo esc_url($tab_url('all-bookings')); ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        <?php esc_html_e('All Bookings', 'moga-travel'); ?>
                        <?php if ($pending_bookings > 0) : ?>
                            <span class="moga-db-admin-overview__link-badge"><?php echo esc_html($pending_bookings); ?></span>
                        <?php endif; ?>
                        <svg class="moga-db-admin-overview__arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </li>
                <li>
                    <a href="<?php echo esc_url($tab_url('all-properties')); ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        <?php esc_html_e('All Properties', 'moga-travel'); ?>
                        <svg class="moga-db-admin-overview__arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </li>
                <li>
                    <a href="<?php echo esc_url($tab_url('all-tours')); ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                        <?php esc_html_e('All Tours', 'moga-travel'); ?>
                        <svg class="moga-db-admin-overview__arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </li>
                <li>
                    <a href="<?php echo esc_url($tab_url('notifications-log')); ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        <?php esc_html_e('Notifications Log', 'moga-travel'); ?>
                        <svg class="moga-db-admin-overview__arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </li>
            </ul>
        </div>

        <?php // ---- Vendors & Finance ---- ?>
        <div class="moga-db-admin-overview__card">
            <h2 class="moga-db-admin-overview__card-title">
                <?php esc_html_e('Vendors & Finance', 'moga-travel'); ?>
            </h2>
            <ul class="moga-db-admin-overview__card-links">
                <li>
                    <a href="<?php echo esc_url($tab_url('vendors')); ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <?php esc_html_e('Vendors', 'moga-travel'); ?>
                        <?php if ($pending_vendors > 0) : ?>
                            <span class="moga-db-admin-overview__link-badge"><?php echo esc_html($pending_vendors); ?></span>
                        <?php endif; ?>
                        <svg class="moga-db-admin-overview__arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </li>
                <li>
                    <a href="<?php echo esc_url($tab_url('commissions')); ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <?php esc_html_e('Commissions', 'moga-travel'); ?>
                        <svg class="moga-db-admin-overview__arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </li>
                <li>
                    <a href="<?php echo esc_url($tab_url('reports')); ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        <?php esc_html_e('Reports & Analytics', 'moga-travel'); ?>
                        <svg class="moga-db-admin-overview__arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </li>
            </ul>
        </div>

        <?php // ---- My Business ---- ?>
        <div class="moga-db-admin-overview__card">
            <h2 class="moga-db-admin-overview__card-title">
                <?php esc_html_e('My Business', 'moga-travel'); ?>
            </h2>
            <ul class="moga-db-admin-overview__card-links">
                <li>
                    <a href="<?php echo esc_url($tab_url('properties')); ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        <?php esc_html_e('My Properties', 'moga-travel'); ?>
                        <svg class="moga-db-admin-overview__arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </li>
                <li>
                    <a href="<?php echo esc_url($tab_url('tours')); ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                        <?php esc_html_e('My Tours', 'moga-travel'); ?>
                        <svg class="moga-db-admin-overview__arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </li>
                <li>
                    <a href="<?php echo esc_url($tab_url('earnings')); ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <?php esc_html_e('Earnings & Commissions', 'moga-travel'); ?>
                        <svg class="moga-db-admin-overview__arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </li>
                <li>
                    <a href="<?php echo esc_url($tab_url('vendor-bookings')); ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        <?php esc_html_e('Bookings', 'moga-travel'); ?>
                        <svg class="moga-db-admin-overview__arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </li>
            </ul>
        </div>

        <?php // ---- Account ---- ?>
        <div class="moga-db-admin-overview__card">
            <h2 class="moga-db-admin-overview__card-title">
                <?php esc_html_e('Account', 'moga-travel'); ?>
            </h2>
            <ul class="moga-db-admin-overview__card-links">
                <li>
                    <a href="<?php echo esc_url($tab_url('personal-details')); ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        <?php esc_html_e('Personal Details', 'moga-travel'); ?>
                        <svg class="moga-db-admin-overview__arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </li>
                <li>
                    <a href="<?php echo esc_url($tab_url('security')); ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        <?php esc_html_e('Security', 'moga-travel'); ?>
                        <svg class="moga-db-admin-overview__arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </li>
                <li>
                    <a href="<?php echo esc_url($tab_url('notifications')); ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        <?php esc_html_e('Notifications', 'moga-travel'); ?>
                        <svg class="moga-db-admin-overview__arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </li>
                <li>
                    <a href="<?php echo esc_url($tab_url('preferences')); ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <?php esc_html_e('Preferences', 'moga-travel'); ?>
                        <svg class="moga-db-admin-overview__arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </li>
            </ul>
        </div>

        <?php // ---- System ---- ?>
        <div class="moga-db-admin-overview__card">
            <h2 class="moga-db-admin-overview__card-title">
                <?php esc_html_e('System', 'moga-travel'); ?>
            </h2>
            <ul class="moga-db-admin-overview__card-links">
                <li>
                    <a href="<?php echo esc_url(admin_url()); ?>"
                        target="_blank" rel="noopener noreferrer">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <?php esc_html_e('System Management (WP Admin)', 'moga-travel'); ?>
                        <svg class="moga-db-admin-overview__arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>
                </li>
                <li>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=moga-settings')); ?>"
                        target="_blank" rel="noopener noreferrer">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                        <?php esc_html_e('Moga Settings', 'moga-travel'); ?>
                        <svg class="moga-db-admin-overview__arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>
                </li>
            </ul>
        </div>

    </div><?php // .moga-db-admin-overview__grid ?>

</div><?php // .moga-db-admin-overview ?>
