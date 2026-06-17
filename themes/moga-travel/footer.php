<?php
/**
 * Site Footer Template
 *
 * Displays the site footer including newsletter section,
 * widget columns, social links, copyright bar,
 * and closes all open HTML tags from header.php.
 * Called by get_footer() on every page.
 *
 * @package MogaTravel
 * @since   1.0.0
 */
?>

    <!-- ======================================================
         SITE FOOTER
    ====================================================== -->
    <footer id="moga-footer" class="moga-footer" role="contentinfo">

        <!-- Newsletter Section -->
        <div class="moga-footer__newsletter">
            <div class="moga-container">
                <div class="moga-footer__newsletter-inner">
                    <div class="moga-footer__newsletter-text">
                        <h3><?php esc_html_e( 'Get the best travel deals', 'moga-travel' ); ?></h3>
                        <p><?php esc_html_e( 'Subscribe and save up to 30% on your next booking.', 'moga-travel' ); ?></p>
                    </div>
                    <form class="moga-footer__newsletter-form"
                          aria-label="<?php esc_attr_e( 'Newsletter signup', 'moga-travel' ); ?>">
                        <?php wp_nonce_field( 'moga_newsletter', 'moga_newsletter_nonce' ); ?>
                        <input type="email"
                               name="moga_newsletter_email"
                               class="moga-footer__newsletter-input"
                               placeholder="<?php esc_attr_e( 'Enter your email address', 'moga-travel' ); ?>"
                               required
                               aria-label="<?php esc_attr_e( 'Email address', 'moga-travel' ); ?>">
                        <button type="submit" class="moga-btn moga-btn--primary">
                            <?php esc_html_e( 'Subscribe', 'moga-travel' ); ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Footer Top — Widget Columns -->
        <div class="moga-footer__top">
            <div class="moga-container">
                <div class="moga-footer__widgets">

                    <!-- Column 1 — About -->
                    <div class="moga-footer__widget moga-footer__about">
                        <a href="<?php echo esc_url( home_url( '/' ) ); ?>"
                           class="moga-footer__about-logo"
                           aria-label="<?php bloginfo( 'name' ); ?>">
                            <?php if ( has_custom_logo() ) : ?>
                                <?php the_custom_logo(); ?>
                            <?php else : ?>
                                <span class="moga-footer__about-logo-text">
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

                        <p class="moga-footer__about-desc">
                            <?php echo esc_html( get_bloginfo( 'description' ) ?: __( 'Your trusted travel booking platform. Find and book hotels, tours, properties, and bus seats across Egypt and beyond.', 'moga-travel' ) ); ?>
                        </p>

                        <!-- Social Links -->
                        <div class="moga-footer__social" aria-label="<?php esc_attr_e( 'Social media links', 'moga-travel' ); ?>">
                            <a href="#" class="moga-footer__social-link" aria-label="Facebook" rel="noopener noreferrer" target="_blank">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>
                                </svg>
                            </a>
                            <a href="#" class="moga-footer__social-link" aria-label="Instagram" rel="noopener noreferrer" target="_blank">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <rect x="2" y="2" width="20" height="20" rx="5" ry="5"/>
                                    <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/>
                                    <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/>
                                </svg>
                            </a>
                            <a href="#" class="moga-footer__social-link" aria-label="Twitter / X" rel="noopener noreferrer" target="_blank">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                                </svg>
                            </a>
                            <a href="#" class="moga-footer__social-link" aria-label="WhatsApp" rel="noopener noreferrer" target="_blank">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/>
                                </svg>
                            </a>
                        </div>
                    </div>

                    <!-- Column 2 — Company Links -->
                    <div class="moga-footer__widget">
                        <?php if ( is_active_sidebar( 'moga-footer-2' ) ) : ?>
                            <?php dynamic_sidebar( 'moga-footer-2' ); ?>
                        <?php else : ?>
                            <h4 class="moga-footer__widget-title">
                                <?php esc_html_e( 'Company', 'moga-travel' ); ?>
                            </h4>
                            <nav class="moga-footer__widget-links"
                                 aria-label="<?php esc_attr_e( 'Company links', 'moga-travel' ); ?>">
                                <a href="#"><?php esc_html_e( 'About Us', 'moga-travel' ); ?></a>
                                <a href="#"><?php esc_html_e( 'How It Works', 'moga-travel' ); ?></a>
                                <a href="#"><?php esc_html_e( 'Careers', 'moga-travel' ); ?></a>
                                <a href="#"><?php esc_html_e( 'Press', 'moga-travel' ); ?></a>
                                <a href="#"><?php esc_html_e( 'Contact Us', 'moga-travel' ); ?></a>
                            </nav>
                        <?php endif; ?>
                    </div>

                    <!-- Column 3 — Support Links -->
                    <div class="moga-footer__widget">
                        <?php if ( is_active_sidebar( 'moga-footer-3' ) ) : ?>
                            <?php dynamic_sidebar( 'moga-footer-3' ); ?>
                        <?php else : ?>
                            <h4 class="moga-footer__widget-title">
                                <?php esc_html_e( 'Support', 'moga-travel' ); ?>
                            </h4>
                            <nav class="moga-footer__widget-links"
                                 aria-label="<?php esc_attr_e( 'Support links', 'moga-travel' ); ?>">
                                <a href="#"><?php esc_html_e( 'Help Center', 'moga-travel' ); ?></a>
                                <a href="#"><?php esc_html_e( 'Safety Information', 'moga-travel' ); ?></a>
                                <a href="#"><?php esc_html_e( 'Cancellation Options', 'moga-travel' ); ?></a>
                                <a href="#"><?php esc_html_e( 'Report a Problem', 'moga-travel' ); ?></a>
                                <a href="#"><?php esc_html_e( 'Terms & Conditions', 'moga-travel' ); ?></a>
                            </nav>
                        <?php endif; ?>
                    </div>

                    <!-- Column 4 — Destinations Links -->
                    <div class="moga-footer__widget">
                        <?php if ( is_active_sidebar( 'moga-footer-4' ) ) : ?>
                            <?php dynamic_sidebar( 'moga-footer-4' ); ?>
                        <?php else : ?>
                            <h4 class="moga-footer__widget-title">
                                <?php esc_html_e( 'Destinations', 'moga-travel' ); ?>
                            </h4>
                            <nav class="moga-footer__widget-links"
                                 aria-label="<?php esc_attr_e( 'Destinations links', 'moga-travel' ); ?>">
                                <a href="#"><?php esc_html_e( 'Cairo', 'moga-travel' ); ?></a>
                                <a href="#"><?php esc_html_e( 'Alexandria', 'moga-travel' ); ?></a>
                                <a href="#"><?php esc_html_e( 'Hurghada', 'moga-travel' ); ?></a>
                                <a href="#"><?php esc_html_e( 'Sharm El Sheikh', 'moga-travel' ); ?></a>
                                <a href="#"><?php esc_html_e( 'Luxor & Aswan', 'moga-travel' ); ?></a>
                            </nav>
                        <?php endif; ?>
                    </div>

                </div>
                <!-- / Footer Widgets -->
            </div>
        </div>
        <!-- / Footer Top -->

        <!-- Footer Bottom Bar -->
        <div class="moga-footer__bottom">
            <div class="moga-container">
                <div class="moga-footer__bottom-inner">

                    <p class="moga-footer__copyright">
                        &copy; <?php echo esc_html( gmdate( 'Y' ) ); ?>
                        <a href="<?php echo esc_url( home_url( '/' ) ); ?>">
                            <?php bloginfo( 'name' ); ?>
                        </a>
                        &mdash;
                        <?php esc_html_e( 'All rights reserved.', 'moga-travel' ); ?>
                        <?php esc_html_e( 'Developed by', 'moga-travel' ); ?>
                        <a href="https://github.com/hatem-frere"
                           target="_blank"
                           rel="noopener noreferrer">
                            Hatem Frere
                        </a>
                    </p>

                    <nav class="moga-footer__bottom-links"
                         aria-label="<?php esc_attr_e( 'Legal links', 'moga-travel' ); ?>">
                        <a href="#"><?php esc_html_e( 'Privacy Policy', 'moga-travel' ); ?></a>
                        <a href="#"><?php esc_html_e( 'Terms of Service', 'moga-travel' ); ?></a>
                        <a href="#"><?php esc_html_e( 'Cookie Policy', 'moga-travel' ); ?></a>
                    </nav>

                </div>
            </div>
        </div>
        <!-- / Footer Bottom -->

    </footer>
    <!-- / Site Footer -->

</div>
<!-- / .moga-wrapper -->

<?php wp_footer(); ?>

</body>
</html>