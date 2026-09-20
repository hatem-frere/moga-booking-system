<?php

/**
 * Template Name: Moga Dashboard
 *
 * Smart role-detection dashboard shell. Handles all four user roles:
 * Client (subscriber), Property Owner, Tour Organizer, and Admin.
 *
 * Routing logic:
 *  - Not logged in          → redirect to login page
 *  - Logged in, any role    → render dashboard with role-appropriate tabs
 *  - Admin                  → full platform management view
 *  - Vendor (owner/org)     → vendor dashboard with their tabs
 *  - Client (subscriber)    → client dashboard
 *
 * The active tab is read from $_GET['tab'] (falls back to 'overview').
 * Tab content is included via get_template_part() — no AJAX on first
 * load, AJAX takes over for subsequent tab switches via dashboard.js.
 *
 * Gateway form POST handling preserved from the old template and moved
 * into class-moga-dashboard.php as an AJAX action.
 *
 * @package MogaTravel
 * @since   1.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

// ---- Auth gate — redirect to login if not logged in ----
if (! is_user_logged_in()) {
    wp_safe_redirect(wp_login_url(get_permalink()));
    exit;
}

$moga_user    = wp_get_current_user();
$moga_user_id = (int) $moga_user->ID;

// ---- Determine role context ----
$moga_is_admin    = current_user_can('administrator');
$moga_is_owner    = current_user_can('edit_moga_properties');
$moga_is_org      = current_user_can('edit_moga_tours');
$moga_is_vendor   = $moga_is_owner || $moga_is_org;

// ---- Determine active tab with safe fallback ----
$moga_active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'overview';

// ---- Build allowed tabs per role ----
// Each entry: [ key, label, icon (SVG path d=""), section, link_type ]
$moga_client_tabs = array(
    array( 'key' => 'overview',        'label' => __('Overview',        'moga-travel'), 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', 'section' => 'main' ),
    array( 'key' => 'bookings',        'label' => __('My Bookings',     'moga-travel'), 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', 'section' => 'main' ),
    array( 'key' => 'saved',           'label' => __('Saved',           'moga-travel'), 'icon' => 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z', 'section' => 'main' ),
    array( 'key' => 'reviews',         'label' => __('My Reviews',      'moga-travel'), 'icon' => 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z', 'section' => 'main' ),
    array( 'key' => 'wallet',          'label' => __('Wallet & Credits', 'moga-travel'), 'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z', 'section' => 'main' ),
    array( 'key' => 'personal-details','label' => __('Personal Details', 'moga-travel'), 'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z', 'section' => 'account' ),
    array( 'key' => 'security',        'label' => __('Security',        'moga-travel'), 'icon' => 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z', 'section' => 'account' ),
    array( 'key' => 'notifications',   'label' => __('Notifications',   'moga-travel'), 'icon' => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9', 'section' => 'account' ),
    array( 'key' => 'preferences',     'label' => __('Preferences',     'moga-travel'), 'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z', 'section' => 'account' ),
    array( 'key' => 'become-vendor',   'label' => __('Become a Vendor', 'moga-travel'), 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4', 'section' => 'more' ),
    array( 'key' => 'report-problem',  'label' => __('Report a Problem','moga-travel'), 'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z', 'section' => 'more' ),
    array( 'key' => 'delete-account',  'label' => __('Delete Account',  'moga-travel'), 'icon' => 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16', 'section' => 'danger', 'danger' => true ),
);

$moga_vendor_extra_tabs = array(
    array( 'key' => 'properties',      'label' => __('My Properties',   'moga-travel'), 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', 'section' => 'manage', 'cap' => 'edit_moga_properties' ),
    array( 'key' => 'tours',           'label' => __('My Tours',        'moga-travel'), 'icon' => 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7', 'section' => 'manage', 'cap' => 'edit_moga_tours' ),
    array( 'key' => 'buses',           'label' => __('My Buses',        'moga-travel'), 'icon' => 'M8 7h8m-8 5h8m-8 5h8M3 3h18v18H3V3z', 'section' => 'manage', 'cap' => 'edit_moga_tours' ),
    array( 'key' => 'vendor-bookings', 'label' => __('Bookings',        'moga-travel'), 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', 'section' => 'manage', 'cap' => '' ),
    array( 'key' => 'calendar',        'label' => __('Calendar',        'moga-travel'), 'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', 'section' => 'manage', 'cap' => 'edit_moga_properties' ),
    array( 'key' => 'seat-map',        'label' => __('Seat Maps',       'moga-travel'), 'icon' => 'M4 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM14 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zM14 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z', 'section' => 'manage', 'cap' => 'edit_moga_tours' ),
    array( 'key' => 'earnings',        'label' => __('Earnings',        'moga-travel'), 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'section' => 'manage', 'cap' => '' ),
    array( 'key' => 'payout-settings', 'label' => __('Payout Settings', 'moga-travel'), 'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z', 'section' => 'manage', 'cap' => '' ),
    array( 'key' => 'reviews-received','label' => __('Reviews Received','moga-travel'), 'icon' => 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z', 'section' => 'manage', 'cap' => '' ),
);

$moga_admin_extra_tabs = array(
    array( 'key' => 'all-properties',    'label' => __('All Properties',     'moga-travel'), 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', 'section' => 'platform' ),
    array( 'key' => 'all-tours',         'label' => __('All Tours',          'moga-travel'), 'icon' => 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7', 'section' => 'platform' ),
    array( 'key' => 'all-bookings',      'label' => __('All Bookings',       'moga-travel'), 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', 'section' => 'platform' ),
    array( 'key' => 'vendors',           'label' => __('Vendors',            'moga-travel'), 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z', 'section' => 'platform' ),
    array( 'key' => 'commissions',       'label' => __('Commissions',        'moga-travel'), 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'section' => 'platform' ),
    array( 'key' => 'reports',           'label' => __('Reports',            'moga-travel'), 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z', 'section' => 'platform' ),
    array( 'key' => 'notifications-log', 'label' => __('Notifications Log',  'moga-travel'), 'icon' => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9', 'section' => 'platform' ),
);

// ---- Build final tab list for this user ----
if ($moga_is_admin) {
    // Admin gets platform tabs + vendor tabs + client account tabs.
    // Remove 'become-vendor' from client tabs for admin.
    $moga_client_filtered = array_filter($moga_client_tabs, fn($t) => $t['key'] !== 'become-vendor');
    $moga_all_tabs = array_merge(
        $moga_admin_extra_tabs,
        $moga_vendor_extra_tabs,
        array_values($moga_client_filtered)
    );
} elseif ($moga_is_vendor) {
    // Vendor: filter vendor extra tabs by capability, merge with client tabs.
    // Remove 'become-vendor' from client tabs for vendors.
    $moga_client_filtered = array_filter($moga_client_tabs, fn($t) => $t['key'] !== 'become-vendor');
    $moga_vendor_filtered = array_filter($moga_vendor_extra_tabs, function($t) {
        return empty($t['cap']) || current_user_can($t['cap']);
    });
    $moga_all_tabs = array_merge(
        array_values($moga_vendor_filtered),
        array_values($moga_client_filtered)
    );
} else {
    // Client: all client tabs.
    $moga_all_tabs = $moga_client_tabs;
}

// ---- Validate active tab is allowed for this user ----
$moga_allowed_keys = array_column($moga_all_tabs, 'key');
if (! in_array($moga_active_tab, $moga_allowed_keys, true)) {
    $moga_active_tab = 'overview';
}

// ---- Determine template part folder per tab ----
function moga_dashboard_get_tab_template($tab, $is_admin, $is_vendor) {
    // Admin-only tabs.
    $admin_tabs = array('overview','all-properties','all-tours','all-bookings','vendors','commissions','reports','notifications-log');
    // Vendor-only tabs.
    $vendor_tabs = array('properties','tours','buses','vendor-bookings','calendar','seat-map','earnings','payout-settings','reviews-received');

    if ($is_admin && in_array($tab, $admin_tabs, true)) {
        return 'template-parts/dashboard/admin/' . $tab;
    }
    if (($is_vendor || $is_admin) && in_array($tab, $vendor_tabs, true)) {
        return 'template-parts/dashboard/vendor/' . $tab;
    }
    return 'template-parts/dashboard/client/' . $tab;
}

// ---- Admin overview mode ----
// When admin visits /dashboard/ with no ?tab param, show the card grid
// overview with no sidebar. When a tab is selected, show sidebar + content.
$moga_admin_overview = $moga_is_admin && $moga_active_tab === 'overview';

get_header();
?>

<div class="moga-dashboard-page<?php echo $moga_admin_overview ? ' moga-dashboard-page--admin-overview' : ''; ?>">
    <?php
    get_template_part('template-parts/dashboard/topbar', null, array(
        'user'           => $moga_user,
        'active_tab'     => $moga_active_tab,
        'admin_overview' => $moga_admin_overview,
    ));
    ?>

    <div class="moga-db<?php echo $moga_admin_overview ? ' moga-db--overview' : ''; ?>">

        <?php if (! $moga_admin_overview) : ?>
        <?php
        // Sidebar — only shown when a specific tab is active.
        // Determine which admin pill group to show based on active tab.
        get_template_part('template-parts/dashboard/sidebar', null, array(
            'tabs'       => $moga_all_tabs,
            'active_tab' => $moga_active_tab,
            'user'       => $moga_user,
            'is_admin'   => $moga_is_admin,
            'is_vendor'  => $moga_is_vendor,
        ));
        ?>
        <?php endif; ?>

        <div class="moga-db__content-wrap">
            <div class="moga-db__content" id="moga-db-content">
                <?php
                $moga_tab_tpl = moga_dashboard_get_tab_template(
                    $moga_active_tab,
                    $moga_is_admin,
                    $moga_is_vendor
                );

                get_template_part($moga_tab_tpl, null, array(
                    'user'           => $moga_user,
                    'user_id'        => $moga_user_id,
                    'active_tab'     => $moga_active_tab,
                    'is_admin'       => $moga_is_admin,
                    'is_vendor'      => $moga_is_vendor,
                    'is_owner'       => $moga_is_owner,
                    'is_org'         => $moga_is_org,
                    'admin_overview' => $moga_admin_overview,
                ));
                ?>
            </div>
        </div>
    </div>
</div>

<?php get_footer();
