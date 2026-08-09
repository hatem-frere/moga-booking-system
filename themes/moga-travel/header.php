<?php

/**
 * Site Header Template
 *
 * Displays the site header including logo, primary
 * navigation, language switcher, and auth buttons.
 * Called by get_header() on every page.
 *
 * @package MogaTravel
 * @since   1.0.0
 */

// NOTE: moga_account_url() is defined in the plugin
// (plugins/moga-travel-core/includes/helpers/helper-functions.php)
// and currently takes no arguments. The login/register tab is
// added here in the theme via add_query_arg( 'tab', ..., ... )
// instead of passing it into that function, so no plugin change
// is required and there's no arity mismatch.
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <?php wp_head(); ?>
</head>

<body <?php body_class('moga-body'); ?>>

    <?php wp_body_open(); ?>

    <div class="moga-wrapper">

        <!-- ======================================================
         SITE HEADER — TOP UTILITY BAR
    ====================================================== -->
        <?php get_template_part('template-parts/global/header-top'); ?>


        <!-- ======================================================
         SITE HEADER — CATEGORY BAR
         Standard WordPress dynamic menu at the 'moga-primary'
         location. moga_nav_fallback() (functions.php) supplies
         four default items — Properties, Tours, Buses,
         Destinations, mapped to the real CPTs — ONLY when no
         admin menu has been assigned yet at Appearance > Menus.
         The moment a real menu is assigned to "Primary
         Navigation" there, it replaces the fallback automatically;
         nothing here needs to change when that happens.
    ====================================================== -->
        <header id="moga-header" class="moga-header" role="banner">
            <div class="moga-container">
                <div class="moga-header__inner">

                    <nav id="moga-nav"
                        class="moga-header__nav"
                        role="navigation"
                        aria-label="<?php esc_attr_e('Browse categories', 'moga-travel'); ?>">

                        <?php
                        wp_nav_menu(array(
                            'theme_location'  => 'moga-primary',
                            'menu_id'         => 'moga-primary-menu',
                            'menu_class'      => 'moga-nav__list',
                            'container'       => false,
                            'depth'           => 2,
                            'fallback_cb'     => 'moga_nav_fallback',
                            'items_wrap'      => '<ul id="%1$s" class="%2$s" role="menubar">%3$s</ul>',
                            'item_spacing'    => 'discard',
                        ));
                        ?>
                    </nav>

                    <!-- Mobile Menu Toggle -->
                    <button class="moga-nav__toggle"
                        id="moga-nav-toggle"
                        aria-expanded="false"
                        aria-controls="moga-mobile-menu"
                        aria-label="<?php esc_attr_e('Toggle mobile menu', 'moga-travel'); ?>">
                        <span aria-hidden="true"></span>
                        <span aria-hidden="true"></span>
                        <span aria-hidden="true"></span>
                    </button>

                </div>
                <!-- / Header Inner -->
            </div>
            <!-- / Container -->
        </header>
        <!-- / Site Header -->


        <!-- ======================================================
         MOBILE MENU BACKDROP
         Dims the page behind the panel; also closes the menu on
         click, same convention as the account dropdown.
    ====================================================== -->
        <div id="moga-mobile-menu-backdrop" class="moga-mobile-menu-backdrop" aria-hidden="true"></div>

        <!-- ======================================================
         MOBILE MENU PANEL
    ====================================================== -->
        <div id="moga-mobile-menu"
            class="moga-mobile-menu"
            role="dialog"
            aria-label="<?php esc_attr_e('Mobile Navigation', 'moga-travel'); ?>"
            aria-modal="true"
            aria-hidden="true">

            <div class="moga-mobile-menu__header">
                <span class="moga-mobile-menu__title"><?php esc_html_e('Menu', 'moga-travel'); ?></span>
                <button type="button"
                    id="moga-mobile-menu-close"
                    class="moga-mobile-menu__close"
                    aria-label="<?php esc_attr_e('Close menu', 'moga-travel'); ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                </button>
            </div>

            <nav class="moga-mobile-menu__nav"
                aria-label="<?php esc_attr_e('Mobile Navigation', 'moga-travel'); ?>">
                <?php
                wp_nav_menu(array(
                    'theme_location' => 'moga-mobile',
                    'menu_id'        => 'moga-mobile-nav',
                    'menu_class'     => 'moga-mobile-menu__list',
                    'container'      => false,
                    'depth'          => 2,
                    'fallback_cb'    => 'moga_nav_fallback',
                ));
                ?>
            </nav>

            <!-- Relocated from the top bar for small screens.
                 Language stays always visible in the top bar itself
                 (see header-top.php). Contact Us and "Become a Partner"
                 move in here as text instead of staying icon/button-only,
                 both needing more room than the compact top bar has. -->
            <div class="moga-mobile-menu__secondary">

                <a href="<?php echo esc_url(get_option('moga_page_contact') ? get_permalink(get_option('moga_page_contact')) : home_url('/contact/')); ?>" class="moga-mobile-menu__secondary-link">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <circle cx="12" cy="12" r="10" />
                        <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3" />
                        <line x1="12" y1="17" x2="12.01" y2="17" />
                    </svg>
                    <?php esc_html_e('Contact Us', 'moga-travel'); ?>
                </a>

                <a href="<?php echo esc_url(add_query_arg('tab', 'register', moga_account_url())); ?>" class="moga-mobile-menu__secondary-link moga-mobile-menu__secondary-link--accent">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                        <circle cx="9" cy="7" r="4" />
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                        <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                    </svg>
                    <?php esc_html_e('Become a Partner', 'moga-travel'); ?>
                </a>

            </div>

            <?php if (! is_user_logged_in()) : ?>
                <div class="moga-mobile-menu__actions">
                    <a href="<?php echo esc_url(add_query_arg('tab', 'login', moga_account_url())); ?>"
                        class="moga-btn moga-btn--outline-white moga-btn--block">
                        <?php esc_html_e('Sign In', 'moga-travel'); ?>
                    </a>
                    <a href="<?php echo esc_url(add_query_arg('tab', 'register', moga_account_url())); ?>"
                        class="moga-btn moga-btn--primary moga-btn--block">
                        <?php esc_html_e('Register', 'moga-travel'); ?>
                    </a>
                </div>
            <?php endif; ?>

        </div>
        <!-- / Mobile Menu -->
