<?php
/**
 * Dashboard Topbar — Horizontal Navigation
 *
 * Replaces the old sidebar with a full horizontal navigation bar.
 * Three-zone layout:
 *   LEFT  — Logo/home link + avatar + name + role
 *   CENTER — Horizontal tab links (client/vendor: flat; admin: dropdown groups)
 *   RIGHT  — Notification bell + View site link + Sign out
 *
 * Admin groups (Option C — dropdowns):
 *   Platform ▾ | My Business ▾ | Account ▾ | More ▾
 *
 * Client/Vendor (Option A — flat links):
 *   Overview | Bookings | Saved | Reviews | Wallet | Settings ▾ | More ▾
 *
 * @package MogaTravel
 * @since   1.0.0
 *
 * @var string  $args['active_tab']     Currently active tab key.
 * @var WP_User $args['user']           Current user object.
 * @var array   $args['tabs']           All allowed tabs for this user.
 * @var bool    $args['is_admin']       Whether user is administrator.
 * @var bool    $args['is_vendor']      Whether user is a vendor.
 * @var bool    $args['admin_overview'] Whether admin is on the overview page.
 */

if (! defined('ABSPATH')) exit;

$active_tab     = $args['active_tab']     ?? 'overview';
$user           = $args['user']           ?? wp_get_current_user();
$tabs           = $args['tabs']           ?? array();
$is_admin       = $args['is_admin']       ?? false;
$is_vendor      = $args['is_vendor']      ?? false;
$admin_overview = $args['admin_overview'] ?? false;

$dashboard_url = get_permalink(get_option('moga_page_dashboard'));

// Avatar initials.
$display_name    = $user->display_name ?: $user->user_login;
$first_name      = explode(' ', trim($display_name))[0];
$name_parts      = explode(' ', trim($display_name));
$avatar_initials = strtoupper(substr($name_parts[0], 0, 1) . (isset($name_parts[1]) ? substr($name_parts[1], 0, 1) : ''));
$avatar_url      = get_user_meta($user->ID, '_moga_avatar', true);

// Role label.
if ($is_admin) {
    $role_label = __('Administrator', 'moga-travel');
} elseif ($is_vendor) {
    $has_props = current_user_can('edit_moga_properties');
    $has_tours = current_user_can('edit_moga_tours');
    $role_label = ($has_props && $has_tours)
        ? __('Property Owner & Tour Organizer', 'moga-travel')
        : ($has_props ? __('Property Owner', 'moga-travel') : __('Tour Organizer', 'moga-travel'));
} else {
    $role_label = __('Guest', 'moga-travel');
}

// ----------------------------------------------------------------
// ADMIN nav — 4 dropdown groups
// ----------------------------------------------------------------
$admin_groups = array(
    'platform' => array(
        'label' => __('Platform', 'moga-travel'),
        'tabs'  => array('overview','all-bookings','all-properties','all-tours','notifications-log'),
    ),
    'finance' => array(
        'label' => __('Finance', 'moga-travel'),
        'tabs'  => array('vendors','commissions','reports'),
    ),
    'business' => array(
        'label' => __('My Business', 'moga-travel'),
        'tabs'  => array('properties','tours','buses','vendor-bookings','calendar','seat-map','earnings','payout-settings','reviews-received'),
    ),
    'account' => array(
        'label' => __('Account', 'moga-travel'),
        'tabs'  => array('personal-details','security','notifications','preferences','report-problem','delete-account'),
    ),
);

// Determine which admin group is active.
$active_admin_group = '';
if ($is_admin) {
    foreach ($admin_groups as $gk => $gdata) {
        if (in_array($active_tab, $gdata['tabs'], true)) {
            $active_admin_group = $gk;
            break;
        }
    }
}

// Build tab lookup for admin groups.
$tabs_by_key = array();
foreach ($tabs as $tab) {
    $tabs_by_key[$tab['key']] = $tab;
}

// ----------------------------------------------------------------
// NON-ADMIN nav — flat + grouped dropdowns
// Primary (always visible): overview, bookings/vendor-bookings, saved, reviews
// Settings dropdown: personal-details, security, notifications, preferences
// More dropdown: become-vendor, wallet, report-problem, delete-account
// ----------------------------------------------------------------
$primary_tabs    = array('overview', 'bookings', 'vendor-bookings', 'saved', 'reviews-received');
$settings_tabs   = array('personal-details', 'security', 'notifications', 'preferences');
$more_tabs       = array('wallet', 'become-vendor', 'report-problem', 'delete-account');

// Check if active tab is in a dropdown group.
$settings_active = in_array($active_tab, $settings_tabs, true);
$more_active     = in_array($active_tab, $more_tabs, true);
?>

<header class="moga-db__topbar" id="moga-db-topbar">

    <?php // ---- LEFT: avatar + name ---- ?>
    <div class="moga-db__topbar-identity">
        <a href="<?php echo esc_url($dashboard_url); ?>"
            class="moga-db__topbar-avatar-link"
            aria-label="<?php esc_attr_e('Dashboard home', 'moga-travel'); ?>">
            <div class="moga-db__topbar-avatar">
                <?php if ($avatar_url) : ?>
                    <img src="<?php echo esc_url($avatar_url); ?>"
                         alt="<?php echo esc_attr($display_name); ?>">
                <?php else : ?>
                    <?php echo esc_html($avatar_initials); ?>
                <?php endif; ?>
            </div>
        </a>
        <div class="moga-db__topbar-user">
            <span class="moga-db__topbar-name"><?php echo esc_html($first_name); ?></span>
            <span class="moga-db__topbar-role"><?php echo esc_html($role_label); ?></span>
        </div>
    </div>

    <?php // ---- CENTER: navigation ---- ?>
    <nav class="moga-db__topbar-nav" aria-label="<?php esc_attr_e('Dashboard navigation', 'moga-travel'); ?>">

        <?php if ($is_admin) : ?>
            <?php // ADMIN — dropdown groups ?>

            <?php // Overview is always a direct link ?>
            <?php if (isset($tabs_by_key['overview'])) :
                $is_active = ($active_tab === 'overview'); ?>
                <a href="<?php echo esc_url(add_query_arg('tab', 'overview', $dashboard_url)); ?>"
                    class="moga-db__nav-item<?php echo $is_active ? ' is-active' : ''; ?>"
                    data-tab="overview">
                    <?php esc_html_e('Overview', 'moga-travel'); ?>
                </a>
            <?php endif; ?>

            <?php foreach ($admin_groups as $gk => $gdata) :
                if ($gk === 'platform') continue; // overview handled above, rest in dropdown
                $group_active = ($active_admin_group === $gk);
                $group_tabs   = array_filter($gdata['tabs'], fn($t) => $t !== 'overview' && isset($tabs_by_key[$t]));
                if (empty($group_tabs)) continue;
            ?>
                <div class="moga-db__nav-dropdown<?php echo $group_active ? ' is-active' : ''; ?>">
                    <button type="button"
                        class="moga-db__nav-item moga-db__nav-item--dropdown<?php echo $group_active ? ' is-active' : ''; ?>"
                        aria-expanded="false"
                        aria-haspopup="true">
                        <?php echo esc_html($gdata['label']); ?>
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <polyline points="6 9 12 15 18 9"/>
                        </svg>
                    </button>
                    <div class="moga-db__nav-dropdown-menu" role="menu">
                        <?php foreach ($group_tabs as $tab_key) :
                            if (! isset($tabs_by_key[$tab_key])) continue;
                            $tab = $tabs_by_key[$tab_key];
                            $is_tab_active = ($active_tab === $tab_key);
                        ?>
                            <a href="<?php echo esc_url(add_query_arg('tab', $tab_key, $dashboard_url)); ?>"
                                class="moga-db__nav-dropdown-item<?php echo $is_tab_active ? ' is-active' : ''; ?>"
                                data-tab="<?php echo esc_attr($tab_key); ?>"
                                role="menuitem">
                                <?php echo esc_html($tab['label']); ?>
                            </a>
                        <?php endforeach; ?>

                        <?php // System Management in Platform group ?>
                        <?php if ($gk === 'platform') : ?>
                            <a href="<?php echo esc_url(admin_url()); ?>"
                                class="moga-db__nav-dropdown-item moga-db__nav-dropdown-item--external"
                                target="_blank" rel="noopener noreferrer" role="menuitem">
                                <?php esc_html_e('System Management', 'moga-travel'); ?>
                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                </svg>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php // Platform dropdown (all-bookings, all-properties, all-tours, etc.) ?>
            <?php
            $platform_tabs = array_filter(
                $admin_groups['platform']['tabs'],
                fn($t) => $t !== 'overview' && isset($tabs_by_key[$t])
            );
            $platform_active = ($active_admin_group === 'platform' && $active_tab !== 'overview');
            if (! empty($platform_tabs)) : ?>
                <div class="moga-db__nav-dropdown<?php echo $platform_active ? ' is-active' : ''; ?>">
                    <button type="button"
                        class="moga-db__nav-item moga-db__nav-item--dropdown<?php echo $platform_active ? ' is-active' : ''; ?>"
                        aria-expanded="false" aria-haspopup="true">
                        <?php esc_html_e('Platform', 'moga-travel'); ?>
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <polyline points="6 9 12 15 18 9"/>
                        </svg>
                    </button>
                    <div class="moga-db__nav-dropdown-menu" role="menu">
                        <?php foreach ($platform_tabs as $tab_key) :
                            if (! isset($tabs_by_key[$tab_key])) continue;
                            $tab = $tabs_by_key[$tab_key];
                        ?>
                            <a href="<?php echo esc_url(add_query_arg('tab', $tab_key, $dashboard_url)); ?>"
                                class="moga-db__nav-dropdown-item<?php echo $active_tab === $tab_key ? ' is-active' : ''; ?>"
                                data-tab="<?php echo esc_attr($tab_key); ?>" role="menuitem">
                                <?php echo esc_html($tab['label']); ?>
                            </a>
                        <?php endforeach; ?>
                        <div class="moga-db__nav-dropdown-divider"></div>
                        <a href="<?php echo esc_url(admin_url()); ?>"
                            class="moga-db__nav-dropdown-item moga-db__nav-dropdown-item--external"
                            target="_blank" rel="noopener noreferrer" role="menuitem">
                            <?php esc_html_e('System Management ↗', 'moga-travel'); ?>
                        </a>
                    </div>
                </div>
            <?php endif; ?>

        <?php else : ?>
            <?php // CLIENT / VENDOR — flat primary tabs + Settings + More dropdowns ?>

            <?php foreach ($primary_tabs as $tab_key) :
                if (! isset($tabs_by_key[$tab_key])) continue;
                $tab = $tabs_by_key[$tab_key];
                $is_active = ($active_tab === $tab_key);
            ?>
                <a href="<?php echo esc_url(add_query_arg('tab', $tab_key, $dashboard_url)); ?>"
                    class="moga-db__nav-item<?php echo $is_active ? ' is-active' : ''; ?>"
                    data-tab="<?php echo esc_attr($tab_key); ?>">
                    <?php echo esc_html($tab['label']); ?>
                </a>
            <?php endforeach; ?>

            <?php // Settings dropdown ?>
            <?php $visible_settings = array_filter($settings_tabs, fn($t) => isset($tabs_by_key[$t]));
            if (! empty($visible_settings)) : ?>
                <div class="moga-db__nav-dropdown<?php echo $settings_active ? ' is-active' : ''; ?>">
                    <button type="button"
                        class="moga-db__nav-item moga-db__nav-item--dropdown<?php echo $settings_active ? ' is-active' : ''; ?>"
                        aria-expanded="false" aria-haspopup="true">
                        <?php esc_html_e('Settings', 'moga-travel'); ?>
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <polyline points="6 9 12 15 18 9"/>
                        </svg>
                    </button>
                    <div class="moga-db__nav-dropdown-menu" role="menu">
                        <?php foreach ($visible_settings as $tab_key) :
                            $tab = $tabs_by_key[$tab_key]; ?>
                            <a href="<?php echo esc_url(add_query_arg('tab', $tab_key, $dashboard_url)); ?>"
                                class="moga-db__nav-dropdown-item<?php echo $active_tab === $tab_key ? ' is-active' : ''; ?>"
                                data-tab="<?php echo esc_attr($tab_key); ?>" role="menuitem">
                                <?php echo esc_html($tab['label']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php // More dropdown ?>
            <?php $visible_more = array_filter($more_tabs, fn($t) => isset($tabs_by_key[$t]));
            if (! empty($visible_more)) : ?>
                <div class="moga-db__nav-dropdown<?php echo $more_active ? ' is-active' : ''; ?>">
                    <button type="button"
                        class="moga-db__nav-item moga-db__nav-item--dropdown<?php echo $more_active ? ' is-active' : ''; ?>"
                        aria-expanded="false" aria-haspopup="true">
                        <?php esc_html_e('More', 'moga-travel'); ?>
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <polyline points="6 9 12 15 18 9"/>
                        </svg>
                    </button>
                    <div class="moga-db__nav-dropdown-menu" role="menu">
                        <?php foreach ($visible_more as $tab_key) :
                            $tab = $tabs_by_key[$tab_key];
                            $is_danger = ! empty($tab['danger']); ?>
                            <a href="<?php echo esc_url(add_query_arg('tab', $tab_key, $dashboard_url)); ?>"
                                class="moga-db__nav-dropdown-item<?php echo $active_tab === $tab_key ? ' is-active' : ''; ?><?php echo $is_danger ? ' moga-db__nav-dropdown-item--danger' : ''; ?>"
                                data-tab="<?php echo esc_attr($tab_key); ?>" role="menuitem">
                                <?php echo esc_html($tab['label']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

        <?php endif; ?>

    </nav>

    <?php // ---- RIGHT: actions ---- ?>
    <div class="moga-db__topbar-actions">

        <?php // Notification bell ?>
        <a href="<?php echo esc_url(add_query_arg('tab', 'notifications', $dashboard_url)); ?>"
            class="moga-db__topbar-bell"
            aria-label="<?php esc_attr_e('Notifications', 'moga-travel'); ?>"
            data-tab="notifications">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
            <span class="moga-db__topbar-bell-dot" id="moga-db-bell-dot"
                style="display:none;" aria-hidden="true"></span>
        </a>

        <?php // Sign out ?>
        <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>"
            class="moga-db__topbar-signout"
            aria-label="<?php esc_attr_e('Sign out', 'moga-travel'); ?>">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
            <span class="moga-db__topbar-signout-label">
                <?php esc_html_e('Sign out', 'moga-travel'); ?>
            </span>
        </a>

    </div>

    <?php // Mobile hamburger — toggles nav on small screens ?>
    <button type="button" class="moga-db__topbar-hamburger"
        id="moga-db-hamburger"
        aria-label="<?php esc_attr_e('Toggle navigation', 'moga-travel'); ?>"
        aria-expanded="false">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
    </button>

</header>

<?php // Mobile nav drawer — shown when hamburger is clicked ?>
<div class="moga-db__mobile-nav" id="moga-db-mobile-nav" aria-hidden="true">
    <div class="moga-db__mobile-nav-inner">
        <?php foreach ($tabs as $tab) :
            if (empty($tab['key'])) continue;
            $is_active = ($active_tab === $tab['key']);
            $is_danger = ! empty($tab['danger']);
            $tab_url   = add_query_arg('tab', $tab['key'], $dashboard_url);
        ?>
            <a href="<?php echo esc_url($tab_url); ?>"
                class="moga-db__mobile-nav-link<?php echo $is_active ? ' is-active' : ''; ?><?php echo $is_danger ? ' is-danger' : ''; ?>"
                data-tab="<?php echo esc_attr($tab['key']); ?>">
                <?php echo esc_html($tab['label']); ?>
            </a>
        <?php endforeach; ?>
        <?php if ($is_admin) : ?>
            <a href="<?php echo esc_url(admin_url()); ?>"
                class="moga-db__mobile-nav-link"
                target="_blank" rel="noopener noreferrer">
                <?php esc_html_e('System Management ↗', 'moga-travel'); ?>
            </a>
        <?php endif; ?>
    </div>
</div>
<div class="moga-db__mobile-backdrop" id="moga-db-backdrop" aria-hidden="true"></div>
