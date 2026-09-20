<?php

/**
 * Dashboard Sidebar
 *
 * For non-admin roles: renders all tabs in a single scrollable list
 * grouped by section (manage, main, account, more, danger).
 *
 * For admin role: renders a horizontal pill switcher with 4 groups
 * (Platform, My Business, Account, More). Only the active group's
 * tabs are shown — no scrolling needed. Group is stored in
 * sessionStorage so it persists across tab switches.
 *
 * Active tab is highlighted with a primary-colour left border.
 * Sidebar flips to right on RTL languages.
 *
 * @package MogaTravel
 * @since   1.0.0
 *
 * @var array   $args['tabs']       All allowed tabs for this user.
 * @var string  $args['active_tab'] Currently active tab key.
 * @var WP_User $args['user']       Current user object.
 * @var bool    $args['is_admin']   Whether user is administrator.
 * @var bool    $args['is_vendor']  Whether user is a vendor.
 */

if (! defined('ABSPATH')) {
    exit;
}

$tabs       = $args['tabs']       ?? array();
$active_tab = $args['active_tab'] ?? 'overview';
$user       = $args['user']       ?? wp_get_current_user();
$is_admin   = $args['is_admin']   ?? false;
$is_vendor  = $args['is_vendor']  ?? false;

// Avatar initials from display name.
$display_name    = $user->display_name ?: $user->user_login;
$name_parts      = explode(' ', trim($display_name));
$avatar_initials = strtoupper(
    substr($name_parts[0], 0, 1) .
    (isset($name_parts[1]) ? substr($name_parts[1], 0, 1) : '')
);
$avatar_url = get_user_meta($user->ID, '_moga_avatar', true);

// Role label.
if ($is_admin) {
    $role_label = __('Administrator', 'moga-travel');
} elseif ($is_vendor) {
    $has_props = current_user_can('edit_moga_properties');
    $has_tours = current_user_can('edit_moga_tours');
    if ($has_props && $has_tours) {
        $role_label = __('Property Owner & Tour Organizer', 'moga-travel');
    } elseif ($has_props) {
        $role_label = __('Property Owner', 'moga-travel');
    } else {
        $role_label = __('Tour Organizer', 'moga-travel');
    }
} else {
    $role_label = __('Guest', 'moga-travel');
}

// Dashboard base URL.
$dashboard_url = get_permalink(get_option('moga_page_dashboard'));

// ----------------------------------------------------------------
// ADMIN: define 4 pill groups
// ----------------------------------------------------------------
$admin_pill_groups = array(
    'platform' => array(
        'label' => __('Platform', 'moga-travel'),
        'tabs'  => array('overview','all-properties','all-tours','all-bookings','vendors','commissions','reports','notifications-log'),
        'extra' => 'system-management', // special external link
    ),
    'business' => array(
        'label' => __('My Business', 'moga-travel'),
        'tabs'  => array('properties','tours','buses','vendor-bookings','calendar','seat-map','earnings','payout-settings','reviews-received'),
    ),
    'account' => array(
        'label' => __('Account', 'moga-travel'),
        'tabs'  => array('personal-details','security','notifications','preferences'),
    ),
    'more' => array(
        'label' => __('More', 'moga-travel'),
        'tabs'  => array('become-vendor','report-problem','delete-account'),
    ),
);

// Determine which admin pill group contains the active tab.
$active_admin_group = 'platform'; // default
if ($is_admin) {
    foreach ($admin_pill_groups as $gk => $gdata) {
        if (in_array($active_tab, $gdata['tabs'], true)) {
            $active_admin_group = $gk;
            break;
        }
    }
}

// ----------------------------------------------------------------
// NON-ADMIN: group tabs by section
// ----------------------------------------------------------------
$sections      = array();
$section_order = array('manage', 'main', 'account', 'more', 'danger');
$section_labels = array(
    'manage'  => __('My Business', 'moga-travel'),
    'main'    => __('My Account', 'moga-travel'),
    'account' => __('Settings', 'moga-travel'),
    'more'    => __('More', 'moga-travel'),
    'danger'  => '',
);

foreach ($tabs as $tab) {
    $section = $tab['section'] ?? 'main';
    $sections[$section][] = $tab;
}

// Build a lookup of tab key → tab data for admin groups.
$tabs_by_key = array();
foreach ($tabs as $tab) {
    $tabs_by_key[$tab['key']] = $tab;
}
?>

<aside class="moga-db__sidebar" id="moga-db-sidebar"
    role="navigation"
    aria-label="<?php esc_attr_e('Dashboard navigation', 'moga-travel'); ?>"
    <?php if ($is_admin) : ?>
        data-active-group="<?php echo esc_attr($active_admin_group); ?>"
    <?php endif; ?>>

    <?php // ---- User profile block ---- ?>
    <div class="moga-db__sidebar-profile">
        <div class="moga-db__sidebar-avatar">
            <?php if ($avatar_url) : ?>
                <img src="<?php echo esc_url($avatar_url); ?>"
                     alt="<?php echo esc_attr($display_name); ?>">
            <?php else : ?>
                <?php echo esc_html($avatar_initials); ?>
            <?php endif; ?>
        </div>
        <div>
            <div class="moga-db__sidebar-name">
                <?php echo esc_html($display_name); ?>
            </div>
            <div class="moga-db__sidebar-role">
                <?php echo esc_html($role_label); ?>
            </div>
        </div>
    </div>

    <?php // ================================================================
    // ADMIN SIDEBAR — horizontal pill group switcher
    // ================================================================ ?>
    <?php if ($is_admin) : ?>

        <?php // Pill switcher ?>
        <div class="moga-db__group-pills" role="tablist"
            aria-label="<?php esc_attr_e('Dashboard sections', 'moga-travel'); ?>">
            <?php foreach ($admin_pill_groups as $gk => $gdata) : ?>
                <button type="button"
                    class="moga-db__group-pill<?php echo $gk === $active_admin_group ? ' is-active' : ''; ?>"
                    data-group="<?php echo esc_attr($gk); ?>"
                    role="tab"
                    aria-selected="<?php echo $gk === $active_admin_group ? 'true' : 'false'; ?>">
                    <?php echo esc_html($gdata['label']); ?>
                </button>
            <?php endforeach; ?>
        </div>

        <?php // Tab lists — one per group, only active group visible ?>
        <nav class="moga-db__nav" id="moga-db-nav">
            <?php foreach ($admin_pill_groups as $gk => $gdata) : ?>
                <div class="moga-db__group-panel<?php echo $gk === $active_admin_group ? ' is-active' : ''; ?>"
                    data-group-panel="<?php echo esc_attr($gk); ?>"
                    role="tabpanel">

                    <?php foreach ($gdata['tabs'] as $tab_key) :
                        if (! isset($tabs_by_key[$tab_key])) continue;
                        $tab        = $tabs_by_key[$tab_key];
                        $is_active  = ($tab['key'] === $active_tab);
                        $is_danger  = ! empty($tab['danger']);
                        $tab_url    = add_query_arg('tab', $tab['key'], $dashboard_url);

                        $link_classes = 'moga-db__nav-link';
                        if ($is_active) $link_classes .= ' is-active';
                        if ($is_danger) $link_classes .= ' moga-db__nav-link--danger';
                    ?>
                        <a href="<?php echo esc_url($tab_url); ?>"
                            class="<?php echo esc_attr($link_classes); ?>"
                            data-tab="<?php echo esc_attr($tab['key']); ?>"
                            <?php echo $is_active ? 'aria-current="page"' : ''; ?>>

                            <svg class="moga-db__nav-icon" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="<?php echo esc_attr($tab['icon']); ?>"/>
                            </svg>

                            <?php echo esc_html($tab['label']); ?>

                            <?php if (in_array($tab['key'], array('all-bookings','all-properties','vendors'), true)) : ?>
                                <span class="moga-db__nav-badge"
                                    id="moga-badge-<?php echo esc_attr($tab['key']); ?>"
                                    style="display:none;" aria-live="polite"></span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>

                    <?php // System Management external link — only in Platform group ?>
                    <?php if ($gk === 'platform') : ?>
                        <a href="<?php echo esc_url(admin_url()); ?>"
                            class="moga-db__nav-link moga-db__nav-link--external"
                            target="_blank" rel="noopener noreferrer">
                            <svg class="moga-db__nav-icon" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <?php esc_html_e('System Management', 'moga-travel'); ?>
                            <svg class="moga-db__nav-icon-ext" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                        </a>
                    <?php endif; ?>

                </div>
            <?php endforeach; ?>
        </nav>

    <?php // ================================================================
    // NON-ADMIN SIDEBAR — single scrollable list grouped by section
    // ================================================================ ?>
    <?php else : ?>

        <nav class="moga-db__nav" id="moga-db-nav">
            <?php foreach ($section_order as $section_key) :
                if (empty($sections[$section_key])) continue; ?>

                <div class="moga-db__nav-section">
                    <?php if (! empty($section_labels[$section_key])) : ?>
                        <div class="moga-db__nav-section-label">
                            <?php echo esc_html($section_labels[$section_key]); ?>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($sections[$section_key] as $tab) :
                        $is_active   = ($tab['key'] === $active_tab);
                        $is_danger   = ! empty($tab['danger']);
                        $is_external = ! empty($tab['external']);
                        $tab_url     = $is_external
                            ? ($tab['url'] ?? admin_url())
                            : add_query_arg('tab', $tab['key'], $dashboard_url);

                        $link_classes = 'moga-db__nav-link';
                        if ($is_active)   $link_classes .= ' is-active';
                        if ($is_danger)   $link_classes .= ' moga-db__nav-link--danger';
                        if ($is_external) $link_classes .= ' moga-db__nav-link--external';
                    ?>
                        <a href="<?php echo esc_url($tab_url); ?>"
                            class="<?php echo esc_attr($link_classes); ?>"
                            <?php echo $is_external ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>
                            data-tab="<?php echo esc_attr($tab['key']); ?>"
                            <?php echo $is_active ? 'aria-current="page"' : ''; ?>>

                            <svg class="moga-db__nav-icon" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="<?php echo esc_attr($tab['icon']); ?>"/>
                            </svg>

                            <?php echo esc_html($tab['label']); ?>

                            <?php if (in_array($tab['key'], array('bookings','vendor-bookings','all-bookings','vendors'), true)) : ?>
                                <span class="moga-db__nav-badge"
                                    id="moga-badge-<?php echo esc_attr($tab['key']); ?>"
                                    style="display:none;" aria-live="polite"></span>
                            <?php endif; ?>

                        </a>
                    <?php endforeach; ?>
                </div>

            <?php endforeach; ?>
        </nav>

    <?php endif; ?>

    <?php // ---- Sidebar footer — Sign Out ---- ?>
    <div class="moga-db__sidebar-footer">
        <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>"
            class="moga-db__sidebar-signout">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15"
                fill="none" viewBox="0 0 24 24" stroke="currentColor"
                stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
            <?php esc_html_e('Sign Out', 'moga-travel'); ?>
        </a>
    </div>

</aside>

<?php // Mobile backdrop ?>
<div class="moga-db__sidebar-backdrop" id="moga-db-backdrop" aria-hidden="true"></div>
