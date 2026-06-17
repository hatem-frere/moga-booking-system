<?php
/**
 * Main Index Template
 *
 * The fallback template for all pages that don't have
 * a dedicated template. WordPress always falls back to
 * this file if no other template matches.
 *
 * @package MogaTravel
 * @since   1.0.0
 */

get_header(); ?>

<main id="moga-main" class="moga-main">

    <?php if ( is_home() || is_front_page() ) : ?>

        <?php get_template_part( 'template-parts/global/breadcrumb' ); ?>

        <section class="moga-section moga-bg-light">
            <div class="moga-container">

                <?php if ( have_posts() ) : ?>

                    <div class="moga-grid moga-grid--3">
                        <?php while ( have_posts() ) : the_post(); ?>
                            <article id="post-<?php the_ID(); ?>" <?php post_class( 'moga-card' ); ?>>
                                <?php if ( has_post_thumbnail() ) : ?>
                                    <div class="moga-card__image-wrap">
                                        <a href="<?php the_permalink(); ?>">
                                            <?php the_post_thumbnail( 'moga-card', array( 'class' => 'moga-card__image' ) ); ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                                <div class="moga-card__body">
                                    <h2 class="moga-card__title">
                                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                    </h2>
                                    <div class="moga-text-muted moga-fs-sm">
                                        <?php the_excerpt(); ?>
                                    </div>
                                </div>
                            </article>
                        <?php endwhile; ?>
                    </div>

                    <?php the_posts_pagination( array(
                        'mid_size'  => 2,
                        'prev_text' => __( '&laquo; Previous', 'moga-travel' ),
                        'next_text' => __( 'Next &raquo;', 'moga-travel' ),
                    ) ); ?>

                <?php else : ?>

                    <div class="moga-text-center moga-py-3">
                        <p class="moga-text-muted">
                            <?php esc_html_e( 'No content found.', 'moga-travel' ); ?>
                        </p>
                    </div>

                <?php endif; ?>

            </div>
        </section>

    <?php else : ?>

        <section class="moga-section">
            <div class="moga-container">
                <?php if ( have_posts() ) : ?>
                    <?php while ( have_posts() ) : the_post(); ?>
                        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                            <h1 class="moga-mb-3"><?php the_title(); ?></h1>
                            <div class="moga-card moga-p-3">
                                <?php the_content(); ?>
                            </div>
                        </article>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </section>

    <?php endif; ?>

</main>

<?php get_footer(); ?>