<?php
/**
 * Template Name: Search Results
 * Template Post Type: page
 *
 * Minimal search results page template.
 * Displays grid/list toggle and property cards.
 * Sidebar filters and full query logic added in Step 19.
 *
 * @package MogaTravel
 * @since   1.0.0
 */

get_header();

// Determine current view — grid or list. Defaults to grid.
$current_view = isset( $_GET['view'] ) && 'list' === $_GET['view'] ? 'list' : 'grid';
?>

<main id="moga-main" class="moga-main">
    <div class="moga-container">
        <div class="moga-search-results">

            <?php // ---- Results Toolbar ---- ?>
            <div class="moga-results-toolbar">

                <p class="moga-results-toolbar__count">
                    <?php esc_html_e( 'Available Properties', 'moga-travel' ); ?>
                </p>

                <?php // ---- Grid / List Toggle ---- ?>
                <div class="moga-view-toggle" role="group" aria-label="<?php esc_attr_e( 'View mode', 'moga-travel' ); ?>">

                    
                       <a href="<?php echo esc_url( add_query_arg( 'view', 'grid' ) ); ?>"
                        class="moga-view-toggle__btn <?php echo 'grid' === $current_view ? 'is-active' : ''; ?>"
                        aria-label="<?php esc_attr_e( 'Grid view', 'moga-travel' ); ?>"
                        aria-pressed="<?php echo 'grid' === $current_view ? 'true' : 'false'; ?>"
                    >
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="7" height="7"/>
                            <rect x="14" y="3" width="7" height="7"/>
                            <rect x="3" y="14" width="7" height="7"/>
                            <rect x="14" y="14" width="7" height="7"/>
                        </svg>
                    </a>

                    
                       <a href="<?php echo esc_url( add_query_arg( 'view', 'list' ) ); ?>"
                        class="moga-view-toggle__btn <?php echo 'list' === $current_view ? 'is-active' : ''; ?>"
                        aria-label="<?php esc_attr_e( 'List view', 'moga-travel' ); ?>"
                        aria-pressed="<?php echo 'list' === $current_view ? 'true' : 'false'; ?>"
                    >
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="8" y1="6" x2="21" y2="6"/>
                            <line x1="8" y1="12" x2="21" y2="12"/>
                            <line x1="8" y1="18" x2="21" y2="18"/>
                            <line x1="3" y1="6" x2="3.01" y2="6"/>
                            <line x1="3" y1="12" x2="3.01" y2="12"/>
                            <line x1="3" y1="18" x2="3.01" y2="18"/>
                        </svg>
                    </a>

                </div>
                <?php // ---- End Toggle ---- ?>

            </div>
            <?php // ---- End Toolbar ---- ?>

            <?php // ---- Results ---- ?>
            <?php
            $args = array(
                'post_type'      => 'moga_property',
                'posts_per_page' => 9,
                'post_status'    => 'publish',
                'orderby'        => 'date',
                'order'          => 'DESC',
            );

            $properties = new WP_Query( $args );
            ?>

            <?php if ( $properties->have_posts() ) : ?>

                <div class="moga-results moga-results--<?php echo esc_attr( $current_view ); ?>">
                    <?php while ( $properties->have_posts() ) : $properties->the_post(); ?>
                        <?php
                        if ( 'list' === $current_view ) {
                            get_template_part( 'template-parts/property/card-list' );
                        } else {
                            get_template_part( 'template-parts/property/card-grid' );
                        }
                        ?>
                    <?php endwhile; ?>
                    <?php wp_reset_postdata(); ?>
                </div>

            <?php else : ?>

                <div class="moga-no-results">
                    <p><?php esc_html_e( 'No properties found.', 'moga-travel' ); ?></p>
                </div>

            <?php endif; ?>
            <?php // ---- End Results ---- ?>

        </div>
    </div>
</main>

<?php get_footer(); ?>