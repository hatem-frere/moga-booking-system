<?php
/**
 * Template Name: My Account
 *
 * Path: themes/moga-travel/page-templates/template-account.php
 *
 * Thin wrapper — all real logic lives in the [moga_account]
 * shortcode (Moga_Shortcode_Account), so this template just
 * provides the page chrome and outputs the shortcode.
 *
 * Note: the "My Account" WordPress page itself is created
 * automatically on plugin activation with content
 * '[moga_account]' (see Moga_Activator::create_pages()), so this
 * template only needs to be selected as that page's template —
 * it does not need to output the shortcode itself unless you
 * want to guarantee it renders even if the page's saved content
 * is ever edited. It's included below as a safety net.
 *
 * @package MogaTravel
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>

<main id="moga-main" class="moga-main moga-account-page">
    <div class="moga-container moga-container--narrow">

        <h1 class="moga-account-page__title">
            <?php echo esc_html( get_the_title() ?: __( 'My Account', 'moga-travel' ) ); ?>
        </h1>

        <?php
        // Prefer the actual page content (so admins can add intro text
        // around the shortcode in the editor), but always guarantee the
        // shortcode itself renders even if it was accidentally removed.
        if ( have_posts() ) {
            while ( have_posts() ) {
                the_post();
                $content = get_the_content();
                if ( has_shortcode( $content, 'moga_account' ) ) {
                    the_content();
                } else {
                    the_content();
                    echo do_shortcode( '[moga_account]' );
                }
            }
        } else {
            echo do_shortcode( '[moga_account]' );
        }
        ?>

    </div>
</main>

<?php get_footer(); ?>