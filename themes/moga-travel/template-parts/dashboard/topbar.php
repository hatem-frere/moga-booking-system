<?php

/**
 * Dashboard Top Bar
 *
 * Sticky top bar showing the current tab title, notification bell,
 * "View site" link, and mobile hamburger button.
 *
 * @package MogaTravel
 * @since   1.0.0
 *
 * @var string  $args['active_tab'] Currently active tab key.
 * @var WP_User $args['user']       Current user object.
 */

if (! defined('ABSPATH')) {
    exit;
}

$active_tab      = $args['active_tab']     ?? 'overview';
$user            = $args['user']           ?? wp_get_current_user();
$admin_overview  = $args['admin_overview'] ?? false;
$is_admin        = current_user_can('administrator');

// Tab titles map.
$tab_titles = array(
    'overview'          => __('Overview',            'moga-travel'),
    'bookings'          => __('My Bookings',         'moga-travel'),
    'saved'             => __('Saved',               'moga-travel'),
    'reviews'           => __('My Reviews',          'moga-travel'),
    'wallet'            => __('Wallet & Credits',    'moga-travel'),
    'personal-details'  => __('Personal Details',    'moga-travel'),
    'security'          => __('Security',            'moga-travel'),
    'notifications'     => __('Notifications',       'moga-travel'),
    'preferences'       => __('Preferences',         'moga-travel'),
    'become-vendor'     => __('Become a Vendor',     'moga-travel'),
    'report-problem'    => __('Report a Problem',    'moga-travel'),
    'delete-account'    => __('Delete Account',      'moga-travel'),
    'properties'        => __('My Properties',       'moga-travel'),
    'tours'             => __('My Tours',            'moga-travel'),
    'buses'             => __('My Buses',            'moga-travel'),
    'vendor-bookings'   => __('Bookings',            'moga-travel'),
    'calendar'          => __('Calendar & Availability', 'moga-travel'),
    'seat-map'          => __('Seat Maps',           'moga-travel'),
    'earnings'          => __('Earnings & Commissions', 'moga-travel'),
    'payout-settings'   => __('Payout Settings',    'moga-travel'),
    'reviews-received'  => __('Reviews Received',   'moga-travel'),
    'all-properties'    => __('All Properties',      'moga-travel'),
    'all-tours'         => __('All Tours',           'moga-travel'),
    'all-bookings'      => __('All Bookings',        'moga-travel'),
    'vendors'           => __('Vendors',             'moga-travel'),
    'commissions'       => __('Commissions',         'moga-travel'),
    'reports'           => __('Reports',             'moga-travel'),
    'notifications-log' => __('Notifications Log',   'moga-travel'),
);

$page_title = $tab_titles[$active_tab] ?? __('Dashboard', 'moga-travel');
?>

<header class="moga-db__topbar" id="moga-db-topbar">

    <?php // Mobile hamburger — hidden on admin overview (no sidebar) ?>
    <?php if (! $admin_overview) : ?>
    <button type="button"
        class="moga-db__topbar-hamburger"
        id="moga-db-hamburger"
        aria-label="<?php esc_attr_e('Open menu', 'moga-travel'); ?>"
        aria-expanded="false"
        aria-controls="moga-db-sidebar">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
            fill="none" viewBox="0 0 24 24" stroke="currentColor"
            stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
    </button>
    <?php endif; ?>

    <?php // Back to overview link — admin only, when inside a tab ?>
    <?php if ($is_admin && ! $admin_overview) : ?>
    <a href="<?php echo esc_url(get_permalink(get_option('moga_page_dashboard'))); ?>"
        class="moga-db__topbar-back"
        aria-label="<?php esc_attr_e('Back to overview', 'moga-travel'); ?>">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
            fill="none" viewBox="0 0 24 24" stroke="currentColor"
            stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        <?php esc_html_e('Overview', 'moga-travel'); ?>
    </a>
    <?php endif; ?>

    <?php // Page title — updates via JS on tab switch ?>
    <h1 class="moga-db__topbar-title" id="moga-db-page-title">
        <?php echo esc_html($page_title); ?>
    </h1>

    <div class="moga-db__topbar-actions">

        <?php // Notification bell ?>
        <a href="<?php echo esc_url(add_query_arg('tab', 'notifications', get_permalink(get_option('moga_page_dashboard')))); ?>"
            class="moga-db__topbar-bell"
            aria-label="<?php esc_attr_e('Notifications', 'moga-travel'); ?>"
            id="moga-db-bell">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                fill="none" viewBox="0 0 24 24" stroke="currentColor"
                stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
            <?php // Red dot — shown by JS when there are unread notifications ?>
            <span class="moga-db__topbar-bell-dot"
                id="moga-db-bell-dot"
                style="display:none;"
                aria-hidden="true">
            </span>
        </a>

        <?php // View site link ?>
        <a href="<?php echo esc_url(home_url('/')); ?>"
            class="moga-db__topbar-site-link"
            target="_blank" rel="noopener noreferrer">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                fill="none" viewBox="0 0 24 24" stroke="currentColor"
                stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
            </svg>
            <span><?php esc_html_e('View site', 'moga-travel'); ?></span>
        </a>

    </div>

</header>
