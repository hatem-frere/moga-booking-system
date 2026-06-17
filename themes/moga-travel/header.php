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
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <?php wp_head(); ?>
</head>

<body <?php body_class( 'moga-body' ); ?>>

<?php wp_body_open(); ?>

<div class="moga-wrapper">

    <!-- ======================================================
         SITE HEADER
    ====================================================== -->
    <header id="moga-header" class="moga-header" role="banner">
        <div class="moga-container">
            <div class="moga-header__inner">

                <!-- Logo -->
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>"
                   class="moga-header__logo"
                   rel="home"
                   aria-label="<?php bloginfo( 'name' ); ?> — <?php esc_attr_e( 'Home', 'moga-travel' ); ?>">

                    <?php if ( has_custom_logo() ) : ?>
                        <?php the_custom_logo(); ?>
                    <?php else : ?>
                        <span class="moga-header__logo-text">
                            <?php
                            $site_name  = get_bloginfo( 'name' );
                            $parts      = explode( ' ', $site_name, 2 );
                            $first_word = $parts[0] ?? $site_name;
                            $rest       = $parts[1] ?? '';
                            echo esc_html( $first_word );
                            if ( $rest ) {
                                echo ' <span>' . esc_html( $rest ) . '</span>';
                            }
                            ?>
                        </span>
                    <?php endif; ?>
                </a>

                <!-- Primary Navigation -->
                <nav id="moga-nav"
                     class="moga-header__nav"
                     role="navigation"
                     aria-label="<?php esc_attr_e( 'Primary Navigation', 'moga-travel' ); ?>">

                    <?php
                    wp_nav_menu( array(
                        'theme_location'  => 'moga-primary',
                        'menu_id'         => 'moga-primary-menu',
                        'menu_class'      => 'moga-nav__list',
                        'container'       => false,
                        'depth'           => 2,
                        'fallback_cb'     => 'moga_nav_fallback',
                        'items_wrap'      => '<ul id="%1$s" class="%2$s" role="menubar">%3$s</ul>',
                        'item_spacing'    => 'discard',
                    ) );
                    ?>
                </nav>

                <!-- Header Actions -->
                <div class="moga-header__actions">

                    <!-- Currency Switcher -->
                    <button class="moga-header__currency"
                            aria-label="<?php esc_attr_e( 'Switch currency', 'moga-travel' ); ?>">
                        <?php echo esc_html( get_option( 'moga_currency', 'USD' ) ); ?>
                        <svg width="10" height="6" viewBox="0 0 10 6" fill="none" aria-hidden="true">
                            <path d="M1 1L5 5L9 1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        </svg>
                    </button>

                    <span class="moga-header__divider" aria-hidden="true"></span>

                    <!-- Auth Buttons -->
                    <div class="moga-header__user">

                        <?php if ( is_user_logged_in() ) : ?>

                            <?php
                            $current_user = wp_get_current_user();
                            $avatar       = get_avatar_url( $current_user->ID, array( 'size' => 56 ) );
                            $dashboard_url = get_option( 'moga_page_dashboard' )
                                ? get_permalink( get_option( 'moga_page_dashboard' ) )
                                : home_url( '/dashboard/' );
                            $account_url  = get_option( 'moga_page_my_account' )
                                ? get_permalink( get_option( 'moga_page_my_account' ) )
                                : home_url( '/my-account/' );
                            ?>

                            <div class="moga-header__avatar">
                                <button class="moga-header__avatar-btn"
                                        aria-expanded="false"
                                        aria-haspopup="true"
                                        aria-label="<?php esc_attr_e( 'User menu', 'moga-travel' ); ?>">
                                    <img src="<?php echo esc_url( $avatar ); ?>"
                                         alt="<?php echo esc_attr( $current_user->display_name ); ?>"
                                         class="moga-header__avatar-img"
                                         width="28"
                                         height="28">
                                    <span><?php echo esc_html( $current_user->display_name ); ?></span>
                                    <svg width="10" height="6" viewBox="0 0 10 6" fill="none" aria-hidden="true">
                                        <path d="M1 1L5 5L9 1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                    </svg>
                                </button>

                                <div class="moga-header__dropdown" role="menu">
                                    <a href="<?php echo esc_url( $account_url ); ?>" role="menuitem">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                            <circle cx="12" cy="7" r="4"/>
                                        </svg>
                                        <?php esc_html_e( 'My Account', 'moga-travel' ); ?>
                                    </a>
                                    <a href="<?php echo esc_url( $dashboard_url ); ?>" role="menuitem">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                                            <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
                                        </svg>
                                        <?php esc_html_e( 'Dashboard', 'moga-travel' ); ?>
                                    </a>
                                    <a href="<?php echo esc_url( home_url( '/my-account/?tab=bookings' ) ); ?>" role="menuitem">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                            <polyline points="14 2 14 8 20 8"/>
                                        </svg>
                                        <?php esc_html_e( 'My Bookings', 'moga-travel' ); ?>
                                    </a>
                                    <div class="moga-header__dropdown-divider" role="separator"></div>
                                    <a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>" role="menuitem">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                                            <polyline points="16 17 21 12 16 7"/>
                                            <line x1="21" y1="12" x2="9" y2="12"/>
                                        </svg>
                                        <?php esc_html_e( 'Sign Out', 'moga-travel' ); ?>
                                    </a>
                                </div>
                            </div>

                        <?php else : ?>

                            <a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>"
                               class="moga-header__login-btn">
                                <?php esc_html_e( 'Sign in', 'moga-travel' ); ?>
                            </a>

                            <a href="<?php echo esc_url( wp_registration_url() ); ?>"
                               class="moga-header__register-btn">
                                <?php esc_html_e( 'Register', 'moga-travel' ); ?>
                            </a>

                        <?php endif; ?>
                    </div>

                    <!-- Mobile Menu Toggle -->
                    <button class="moga-nav__toggle"
                            id="moga-nav-toggle"
                            aria-expanded="false"
                            aria-controls="moga-mobile-menu"
                            aria-label="<?php esc_attr_e( 'Toggle mobile menu', 'moga-travel' ); ?>">
                        <span aria-hidden="true"></span>
                        <span aria-hidden="true"></span>
                        <span aria-hidden="true"></span>
                    </button>

                </div>
                <!-- / Header Actions -->

            </div>
            <!-- / Header Inner -->
        </div>
        <!-- / Container -->
    </header>
    <!-- / Site Header -->


    <!-- ======================================================
         MOBILE MENU PANEL
    ====================================================== -->
    <div id="moga-mobile-menu"
         class="moga-mobile-menu"
         role="dialog"
         aria-label="<?php esc_attr_e( 'Mobile Navigation', 'moga-travel' ); ?>"
         aria-modal="true"
         aria-hidden="true">

        <nav class="moga-mobile-menu__nav"
             aria-label="<?php esc_attr_e( 'Mobile Navigation', 'moga-travel' ); ?>">
            <?php
            wp_nav_menu( array(
                'theme_location' => 'moga-mobile',
                'menu_id'        => 'moga-mobile-nav',
                'menu_class'     => 'moga-mobile-menu__list',
                'container'      => false,
                'depth'          => 2,
                'fallback_cb'    => 'moga_nav_fallback',
            ) );
            ?>
        </nav>

        <?php if ( ! is_user_logged_in() ) : ?>
            <div class="moga-mobile-menu__actions">
                <a href="<?php echo esc_url( wp_login_url() ); ?>"
                   class="moga-btn moga-btn--outline-white moga-btn--block">
                    <?php esc_html_e( 'Sign In', 'moga-travel' ); ?>
                </a>
                <a href="<?php echo esc_url( wp_registration_url() ); ?>"
                   class="moga-btn moga-btn--primary moga-btn--block">
                    <?php esc_html_e( 'Register', 'moga-travel' ); ?>
                </a>
            </div>
        <?php endif; ?>

    </div>
    <!-- / Mobile Menu -->