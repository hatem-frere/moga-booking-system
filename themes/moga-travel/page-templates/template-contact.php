<?php

/**
 * Template Name: Contact Us
 *
 * Path: themes/moga-travel/page-templates/template-contact.php
 *
 * Rich chrome (hero, contact info cards, map) wrapped around the
 * page's actual content — which defaults to [moga_contact_form],
 * same thin-wrapper pattern as template-account.php. Site-wide
 * contact details (phone/WhatsApp/address/map coordinates) come
 * from options set in Moga_Activator::set_default_options() —
 * update these directly via the database until a Settings page
 * exists to manage them visually.
 *
 * @package MogaTravel
 * @since   1.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

get_header();

$admin_email = get_option('moga_admin_email') ?: get_option('admin_email');
$phone       = get_option('moga_contact_phone');
$whatsapp    = get_option('moga_contact_whatsapp');
$address     = get_option('moga_contact_address');
$lat         = get_option('moga_contact_lat');
$lng         = get_option('moga_contact_lng');
?>

<main id="moga-main" class="moga-main moga-contact-page">

    <!-- ==================== HERO ==================== -->
    <section class="moga-contact-hero">
        <div class="moga-container">
            <h1 class="moga-contact-hero__title">
                <?php echo esc_html(get_the_title() ?: __('Get in Touch', 'moga-travel')); ?>
            </h1>
            <p class="moga-contact-hero__subtitle">
                <?php esc_html_e("We'd love to hear from you — whether it's a question about a booking, becoming a partner, or anything else.", 'moga-travel'); ?>
            </p>
        </div>
    </section>

    <div class="moga-container">
        <div class="moga-contact-layout">

            <!-- ==================== INFO CARDS ==================== -->
            <div class="moga-contact-info">

                <?php if ($admin_email) : ?>
                    <a href="mailto:<?php echo esc_attr($admin_email); ?>" class="moga-contact-card">
                        <span class="moga-contact-card__icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M4 4h16v16H4z" opacity="0" />
                                <path d="M22 6c0-1.1-.9-2-2-2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6z" />
                                <polyline points="22 6 12 13 2 6" />
                            </svg>
                        </span>
                        <span class="moga-contact-card__label"><?php esc_html_e('Email', 'moga-travel'); ?></span>
                        <span class="moga-contact-card__value"><?php echo esc_html($admin_email); ?></span>
                    </a>
                <?php endif; ?>

                <?php if ($phone) : ?>
                    <a href="tel:<?php echo esc_attr(preg_replace('/\s+/', '', $phone)); ?>" class="moga-contact-card">
                        <span class="moga-contact-card__icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.362 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0 1 22 16.92z" />
                            </svg>
                        </span>
                        <span class="moga-contact-card__label"><?php esc_html_e('Phone', 'moga-travel'); ?></span>
                        <span class="moga-contact-card__value"><?php echo esc_html($phone); ?></span>
                    </a>
                <?php endif; ?>

                <?php if ($whatsapp) : ?>
                    <a href="<?php echo esc_url('https://wa.me/' . preg_replace('/\D/', '', $whatsapp)); ?>"
                        class="moga-contact-card"
                        target="_blank"
                        rel="noopener noreferrer">
                        <span class="moga-contact-card__icon moga-contact-card__icon--whatsapp">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M17.6 6.3a8.9 8.9 0 0 0-14.2 10.7L2 22l5.2-1.4A8.9 8.9 0 0 0 17.6 6.3zM12 20.2a7.3 7.3 0 0 1-3.7-1l-.3-.2-2.7.7.7-2.6-.2-.3a7.3 7.3 0 1 1 13.6-3.7 7.3 7.3 0 0 1-7.4 7.1zm4-5.5c-.2-.1-1.3-.6-1.5-.7-.2-.1-.3-.1-.5.1s-.6.7-.7.8-.3.2-.5.1a6 6 0 0 1-1.8-1.1 6.7 6.7 0 0 1-1.2-1.5c-.1-.2 0-.3.1-.5l.4-.4c.1-.1.1-.2.2-.4s0-.3 0-.4l-.7-1.6c-.2-.4-.4-.4-.5-.4h-.5a.9.9 0 0 0-.6.3 2.7 2.7 0 0 0-.8 2 4.7 4.7 0 0 0 1 2.5 10.7 10.7 0 0 0 4.1 3.6c.6.2 1 .4 1.4.5a3.3 3.3 0 0 0 1.5.1 2.5 2.5 0 0 0 1.6-1.1 1.9 1.9 0 0 0 .1-1.1c-.1-.1-.2-.2-.4-.3z" />
                            </svg>
                        </span>
                        <span class="moga-contact-card__label"><?php esc_html_e('WhatsApp', 'moga-travel'); ?></span>
                        <span class="moga-contact-card__value"><?php echo esc_html($whatsapp); ?></span>
                    </a>
                <?php endif; ?>

                <?php if ($address) : ?>
                    <div class="moga-contact-card moga-contact-card--static">
                        <span class="moga-contact-card__icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" />
                                <circle cx="12" cy="10" r="3" />
                            </svg>
                        </span>
                        <span class="moga-contact-card__label"><?php esc_html_e('Address', 'moga-travel'); ?></span>
                        <span class="moga-contact-card__value"><?php echo esc_html($address); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($lat && $lng) :
                    $bbox = ($lng - 0.01) . ',' . ($lat - 0.01) . ',' . ($lng + 0.01) . ',' . ($lat + 0.01);
                    $map_url = add_query_arg(array(
                        'bbox'   => $bbox,
                        'layer'  => 'mapnik',
                        'marker' => $lat . ',' . $lng,
                    ), 'https://www.openstreetmap.org/export/embed.html');
                ?>
                    <div class="moga-contact-map">
                        <iframe
                            src="<?php echo esc_url($map_url); ?>"
                            class="moga-contact-map__iframe"
                            title="<?php esc_attr_e('Our location', 'moga-travel'); ?>"
                            loading="lazy"
                            referrerpolicy="no-referrer"></iframe>
                    </div>
                <?php endif; ?>

            </div>
            <!-- / Info Cards -->

            <!-- ==================== FORM ==================== -->
            <div class="moga-contact-form-wrap">
                <div class="moga-account">
                    <?php
                    if (have_posts()) {
                        while (have_posts()) {
                            the_post();
                            $content = get_the_content();
                            if (has_shortcode($content, 'moga_contact_form')) {
                                the_content();
                            } else {
                                the_content();
                                echo do_shortcode('[moga_contact_form]');
                            }
                        }
                    } else {
                        echo do_shortcode('[moga_contact_form]');
                    }
                    ?>
                </div>
            </div>

        </div>
    </div>

</main>

<?php get_footer(); ?>
