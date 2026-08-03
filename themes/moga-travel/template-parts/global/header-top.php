<?php

/**
 * Site Header — Top Utility Bar
 *
 * Path: themes/moga-travel/template-parts/global/header-top.php
 *
 * Booking.com-style top bar: logo, currency/language switchers,
 * Contact Us, "Become a Partner" CTA, and auth (Sign in/Register
 * or the logged-in avatar dropdown). This is the single source of
 * truth for these elements — header.php includes this via
 * get_template_part() and does not duplicate any of this markup.
 *
 * Currency switcher and the Sign in/Register/avatar-dropdown block
 * are moved here unchanged from their previous location directly
 * inside header.php. Language switcher is a visual shell only —
 * per project decision, actual language switching is deferred to
 * a later phase; this just reserves its place in the bar.
 *
 * @package MogaTravel
 * @since   1.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

$contact_url = get_option('moga_page_contact')
    ? get_permalink(get_option('moga_page_contact'))
    : home_url('/contact/');
?>
<div id="moga-header-top" class="moga-header-top">
    <div class="moga-container">
        <div class="moga-header-top__inner">

            <!-- Logo -->
            <a href="<?php echo esc_url(home_url('/')); ?>"
                class="moga-header__logo"
                rel="home"
                aria-label="<?php bloginfo('name'); ?> — <?php esc_attr_e('Home', 'moga-travel'); ?>">

                <?php if (has_custom_logo()) : ?>
                    <?php the_custom_logo(); ?>
                <?php else : ?>
                    <span class="moga-header__logo-text">
                        <?php
                        $site_name  = get_bloginfo('name');
                        $parts      = explode(' ', $site_name, 2);
                        $first_word = $parts[0] ?? $site_name;
                        $rest       = $parts[1] ?? '';
                        echo esc_html($first_word);
                        if ($rest) {
                            echo ' <span>' . esc_html($rest) . '</span>';
                        }
                        ?>
                    </span>
                <?php endif; ?>
            </a>

            <!-- Right-side utility group -->
            <div class="moga-header-top__actions">

                <!-- Currency Switcher -->
                <button class="moga-header__currency"
                    aria-label="<?php esc_attr_e('Switch currency', 'moga-travel'); ?>">
                    <?php echo esc_html(get_option('moga_currency', 'USD')); ?>
                    <svg width="10" height="6" viewBox="0 0 10 6" fill="none" aria-hidden="true">
                        <path d="M1 1L5 5L9 1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                    </svg>
                </button>

                <!-- Language Switcher — visual only, wiring deferred -->
                <button class="moga-header__lang"
                    aria-label="<?php esc_attr_e('Switch language', 'moga-travel'); ?>"
                    title="<?php esc_attr_e('Language switching coming soon', 'moga-travel'); ?>">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <circle cx="12" cy="12" r="10" />
                        <line x1="2" y1="12" x2="22" y2="12" />
                        <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
                    </svg>
                    <?php echo esc_html(strtoupper(substr(get_locale(), 0, 2))); ?>
                    <svg width="10" height="6" viewBox="0 0 10 6" fill="none" aria-hidden="true">
                        <path d="M1 1L5 5L9 1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                    </svg>
                </button>

                <span class="moga-header__divider" aria-hidden="true"></span>

                <!-- Contact Us -->
                <a href="<?php echo esc_url($contact_url); ?>"
                    class="moga-header-top__icon-btn"
                    aria-label="<?php esc_attr_e('Contact us', 'moga-travel'); ?>"
                    title="<?php esc_attr_e('Contact us', 'moga-travel'); ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <circle cx="12" cy="12" r="10" />
                        <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3" />
                        <line x1="12" y1="17" x2="12.01" y2="17" />
                    </svg>
                </a>

                <!-- Become a Partner -->
                <a href="<?php echo esc_url(add_query_arg('tab', 'register', moga_account_url())); ?>"
                    class="moga-header-top__partner-btn">
                    <?php esc_html_e('Become a Partner', 'moga-travel'); ?>
                </a>

                <span class="moga-header__divider" aria-hidden="true"></span>

                <!-- Auth Buttons -->
                <div class="moga-header__user">

                    <?php if (is_user_logged_in()) : ?>

                        <?php
                        $current_user  = wp_get_current_user();
                        $avatar        = get_avatar_url($current_user->ID, array('size' => 56));
                        $dashboard_url = get_option('moga_page_dashboard')
                            ? get_permalink(get_option('moga_page_dashboard'))
                            : home_url('/dashboard/');
                        $account_url   = get_option('moga_page_my_account')
                            ? get_permalink(get_option('moga_page_my_account'))
                            : home_url('/my-account/');
                        ?>

                        <div class="moga-header__avatar">
                            <button class="moga-header__avatar-btn"
                                aria-expanded="false"
                                aria-haspopup="true"
                                aria-label="<?php esc_attr_e('User menu', 'moga-travel'); ?>">
                                <img src="<?php echo esc_url($avatar); ?>"
                                    alt="<?php echo esc_attr($current_user->display_name); ?>"
                                    class="moga-header__avatar-img"
                                    width="28"
                                    height="28">
                                <span><?php echo esc_html($current_user->display_name); ?></span>
                                <svg width="10" height="6" viewBox="0 0 10 6" fill="none" aria-hidden="true">
                                    <path d="M1 1L5 5L9 1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                                </svg>
                            </button>

                            <div class="moga-header__dropdown" role="menu">
                                <a href="<?php echo esc_url($account_url); ?>" role="menuitem">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                        <circle cx="12" cy="7" r="4" />
                                    </svg>
                                    <?php esc_html_e('My Account', 'moga-travel'); ?>
                                </a>
                                <a href="<?php echo esc_url($dashboard_url); ?>" role="menuitem">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <rect x="3" y="3" width="7" height="7" />
                                        <rect x="14" y="3" width="7" height="7" />
                                        <rect x="14" y="14" width="7" height="7" />
                                        <rect x="3" y="14" width="7" height="7" />
                                    </svg>
                                    <?php esc_html_e('Dashboard', 'moga-travel'); ?>
                                </a>
                                <a href="<?php echo esc_url(home_url('/my-account/?tab=bookings')); ?>" role="menuitem">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                        <polyline points="14 2 14 8 20 8" />
                                    </svg>
                                    <?php esc_html_e('My Bookings', 'moga-travel'); ?>
                                </a>
                                <div class="moga-header__dropdown-divider" role="separator"></div>
                                <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" role="menuitem">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                                        <polyline points="16 17 21 12 16 7" />
                                        <line x1="21" y1="12" x2="9" y2="12" />
                                    </svg>
                                    <?php esc_html_e('Sign Out', 'moga-travel'); ?>
                                </a>
                            </div>
                        </div>

                    <?php else : ?>

                        <a href="<?php echo esc_url(add_query_arg('tab', 'login', moga_account_url())); ?>"
                            class="moga-header__login-btn">
                            <?php esc_html_e('Sign in', 'moga-travel'); ?>
                        </a>

                        <a href="<?php echo esc_url(add_query_arg('tab', 'register', moga_account_url())); ?>"
                            class="moga-header__register-btn">
                            <?php esc_html_e('Register', 'moga-travel'); ?>
                        </a>

                    <?php endif; ?>
                </div>

            </div>
            <!-- / Right-side utility group -->

        </div>
    </div>
</div>
