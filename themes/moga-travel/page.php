<?php
/**
 * Page Template
 *
 * Default template for all WordPress pages.
 * Loads the assigned page template if one exists,
 * otherwise renders the page content.
 *
 * @package MogaTravel
 * @since   1.0.0
 */

get_header(); ?>

<main id="moga-main" class="moga-main">
    <?php
    while ( have_posts() ) :
        the_post();

        // Load custom page template if assigned.
        $template = get_page_template_slug();
        if ( $template && locate_template( $template ) ) {
            include locate_template( $template );
        } else {
            // Default: just show the content.
            ?>
            <section class="moga-section">
                <div class="moga-container">
                    <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                        <h1 class="moga-mb-3"><?php the_title(); ?></h1>
                        <div class="moga-card moga-p-3">
                            <?php the_content(); ?>
                        </div>
                    </article>
                </div>
            </section>
            <?php
        }

    endwhile;
    ?>
</main>

<?php get_footer(); ?>