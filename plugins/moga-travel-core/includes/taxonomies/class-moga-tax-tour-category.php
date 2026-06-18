<?php
/**
 * Tour Category Taxonomy
 *
 * Registers the moga_tour_category taxonomy.
 * Used to categorize tours into types such as
 * Adventure, Cultural, Beach, Historical, Family, etc.
 *
 * @package    MogaTravelCore
 * @subpackage MogaTravelCore/includes/taxonomies
 * @author     Hatem Frere
 * @since      1.0.0
 */

// Block direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Moga_Tax_Tour_Category
 */
class Moga_Tax_Tour_Category {

    /**
     * Taxonomy key.
     *
     * @since 1.0.0
     * @var   string
     */
    const TAXONOMY = 'moga_tour_category';

    /**
     * Register the taxonomy.
     * Called from Moga_Core::register_taxonomies()
     * on the 'init' hook.
     *
     * @since  1.0.0
     * @return void
     */
    public static function register() {

        $labels = array(
            'name'                       => _x( 'Tour Categories', 'Taxonomy general name', 'moga-travel-core' ),
            'singular_name'              => _x( 'Tour Category', 'Taxonomy singular name', 'moga-travel-core' ),
            'search_items'               => __( 'Search Tour Categories', 'moga-travel-core' ),
            'popular_items'              => __( 'Popular Tour Categories', 'moga-travel-core' ),
            'all_items'                  => __( 'All Tour Categories', 'moga-travel-core' ),
            'parent_item'                => __( 'Parent Tour Category', 'moga-travel-core' ),
            'parent_item_colon'          => __( 'Parent Tour Category:', 'moga-travel-core' ),
            'edit_item'                  => __( 'Edit Tour Category', 'moga-travel-core' ),
            'view_item'                  => __( 'View Tour Category', 'moga-travel-core' ),
            'update_item'                => __( 'Update Tour Category', 'moga-travel-core' ),
            'add_new_item'               => __( 'Add New Tour Category', 'moga-travel-core' ),
            'new_item_name'              => __( 'New Tour Category Name', 'moga-travel-core' ),
            'separate_items_with_commas' => __( 'Separate categories with commas', 'moga-travel-core' ),
            'add_or_remove_items'        => __( 'Add or remove tour categories', 'moga-travel-core' ),
            'choose_from_most_used'      => __( 'Choose from the most used categories', 'moga-travel-core' ),
            'not_found'                  => __( 'No tour categories found.', 'moga-travel-core' ),
            'no_terms'                   => __( 'No tour categories', 'moga-travel-core' ),
            'menu_name'                  => __( 'Tour Categories', 'moga-travel-core' ),
            'items_list_navigation'      => __( 'Tour categories list navigation', 'moga-travel-core' ),
            'items_list'                 => __( 'Tour categories list', 'moga-travel-core' ),
            'back_to_items'              => __( '← Back to Tour Categories', 'moga-travel-core' ),
        );

        $args = array(
            'labels'             => $labels,
            'description'        => __( 'Categorize tours by type (Adventure, Cultural, Beach, etc.)', 'moga-travel-core' ),

            // Hierarchical = true means it works like
            // categories with parent/child relationships.
            'hierarchical'       => true,

            // Visibility.
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'show_in_nav_menus'  => true,
            'show_in_rest'       => true,
            'show_tagcloud'      => false,
            'show_in_quick_edit' => true,
            'show_admin_column'  => true,

            // URLs.
            'rewrite'            => array(
                'slug'         => 'tour-category',
                'with_front'   => false,
                'hierarchical' => true,
            ),

            // Query.
            'query_var'          => true,
        );

        register_taxonomy(
            self::TAXONOMY,
            array( 'moga_tour' ),
            $args
        );

        // Register taxonomy meta fields.
        self::register_meta_fields();

        // Create default terms on first run.
        self::create_default_terms();
    }


    /**
     * Register taxonomy meta fields.
     * Adds extra data to each tour category term.
     *
     * @since  1.0.0
     * @return void
     */
    private static function register_meta_fields() {

        // Emoji for the tour category.
        register_term_meta(
            self::TAXONOMY,
            'moga_emoji',
            array(
                'type'              => 'string',
                'description'       => __( 'Emoji for this tour category.', 'moga-travel-core' ),
                'single'            => true,
                'default'           => '🗺️',
                'show_in_rest'      => true,
                'sanitize_callback' => 'sanitize_text_field',
            )
        );

        // Color for the tour category badge.
        register_term_meta(
            self::TAXONOMY,
            'moga_color',
            array(
                'type'              => 'string',
                'description'       => __( 'Brand color for this tour category (hex).', 'moga-travel-core' ),
                'single'            => true,
                'default'           => '#003580',
                'show_in_rest'      => true,
                'sanitize_callback' => 'sanitize_hex_color',
            )
        );

        // Display order.
        register_term_meta(
            self::TAXONOMY,
            'moga_order',
            array(
                'type'              => 'integer',
                'description'       => __( 'Display order in filters and dropdowns.', 'moga-travel-core' ),
                'single'            => true,
                'default'           => 0,
                'show_in_rest'      => true,
                'sanitize_callback' => 'absint',
            )
        );

        // Popular flag.
        register_term_meta(
            self::TAXONOMY,
            'moga_popular',
            array(
                'type'              => 'integer',
                'description'       => __( 'Mark as popular category (1 = yes).', 'moga-travel-core' ),
                'single'            => true,
                'default'           => 0,
                'show_in_rest'      => true,
                'sanitize_callback' => 'absint',
            )
        );

        // Icon class for the category.
        register_term_meta(
            self::TAXONOMY,
            'moga_icon',
            array(
                'type'              => 'string',
                'description'       => __( 'Dashicon or icon class for this category.', 'moga-travel-core' ),
                'single'            => true,
                'default'           => 'dashicons-location-alt',
                'show_in_rest'      => true,
                'sanitize_callback' => 'sanitize_text_field',
            )
        );
    }


    /**
     * Create default tour category terms on first activation.
     *
     * @since  1.0.0
     * @return void
     */
    private static function create_default_terms() {

        // Only run once.
        if ( get_option( 'moga_tour_categories_created' ) ) {
            return;
        }

        $default_terms = array(

            // ---- Main Categories ----
            array(
                'name'    => __( 'Adventure', 'moga-travel-core' ),
                'slug'    => 'adventure',
                'desc'    => __( 'Thrilling outdoor adventures — hiking, diving, desert safaris.', 'moga-travel-core' ),
                'emoji'   => '🧗',
                'color'   => '#dc3545',
                'icon'    => 'dashicons-awards',
                'popular' => 1,
                'order'   => 1,
            ),
            array(
                'name'    => __( 'Cultural', 'moga-travel-core' ),
                'slug'    => 'cultural',
                'desc'    => __( 'Explore local culture, traditions, arts and crafts.', 'moga-travel-core' ),
                'emoji'   => '🎭',
                'color'   => '#6f42c1',
                'icon'    => 'dashicons-art',
                'popular' => 1,
                'order'   => 2,
            ),
            array(
                'name'    => __( 'Historical', 'moga-travel-core' ),
                'slug'    => 'historical',
                'desc'    => __( 'Visit ancient monuments, temples, and archaeological sites.', 'moga-travel-core' ),
                'emoji'   => '🏛️',
                'color'   => '#f5a623',
                'icon'    => 'dashicons-store',
                'popular' => 1,
                'order'   => 3,
            ),
            array(
                'name'    => __( 'Beach & Sea', 'moga-travel-core' ),
                'slug'    => 'beach-sea',
                'desc'    => __( 'Sun, sand, and sea — beach tours and water activities.', 'moga-travel-core' ),
                'emoji'   => '🏖️',
                'color'   => '#17a2b8',
                'icon'    => 'dashicons-palmtree',
                'popular' => 1,
                'order'   => 4,
            ),
            array(
                'name'    => __( 'Family', 'moga-travel-core' ),
                'slug'    => 'family',
                'desc'    => __( 'Fun-filled tours suitable for the whole family.', 'moga-travel-core' ),
                'emoji'   => '👨‍👩‍👧‍👦',
                'color'   => '#28a745',
                'icon'    => 'dashicons-groups',
                'popular' => 1,
                'order'   => 5,
            ),
            array(
                'name'    => __( 'Luxury', 'moga-travel-core' ),
                'slug'    => 'luxury',
                'desc'    => __( 'Premium tours with exclusive access and VIP experiences.', 'moga-travel-core' ),
                'emoji'   => '✨',
                'color'   => '#003580',
                'icon'    => 'dashicons-star-filled',
                'popular' => 1,
                'order'   => 6,
            ),
            array(
                'name'    => __( 'Desert Safari', 'moga-travel-core' ),
                'slug'    => 'desert-safari',
                'desc'    => __( 'Explore Egypt\'s vast deserts by jeep, camel, or on foot.', 'moga-travel-core' ),
                'emoji'   => '🐪',
                'color'   => '#fd7e14',
                'icon'    => 'dashicons-superhero',
                'popular' => 1,
                'order'   => 7,
            ),
            array(
                'name'    => __( 'Nile Cruise', 'moga-travel-core' ),
                'slug'    => 'nile-cruise',
                'desc'    => __( 'Sail the Nile discovering ancient temples and monuments.', 'moga-travel-core' ),
                'emoji'   => '⛵',
                'color'   => '#0071c2',
                'icon'    => 'dashicons-location',
                'popular' => 1,
                'order'   => 8,
            ),
            array(
                'name'    => __( 'Diving & Snorkeling', 'moga-travel-core' ),
                'slug'    => 'diving-snorkeling',
                'desc'    => __( 'Explore the Red Sea\'s stunning underwater world.', 'moga-travel-core' ),
                'emoji'   => '🤿',
                'color'   => '#20c997',
                'icon'    => 'dashicons-visibility',
                'popular' => 1,
                'order'   => 9,
            ),
            array(
                'name'    => __( 'Day Trip', 'moga-travel-core' ),
                'slug'    => 'day-trip',
                'desc'    => __( 'Single-day excursions from your base location.', 'moga-travel-core' ),
                'emoji'   => '☀️',
                'color'   => '#ffc107',
                'icon'    => 'dashicons-clock',
                'popular' => 0,
                'order'   => 10,
            ),
            array(
                'name'    => __( 'Multi-Day Tour', 'moga-travel-core' ),
                'slug'    => 'multi-day-tour',
                'desc'    => __( 'Extended tours covering multiple destinations over several days.', 'moga-travel-core' ),
                'emoji'   => '📅',
                'color'   => '#003580',
                'icon'    => 'dashicons-calendar-alt',
                'popular' => 0,
                'order'   => 11,
            ),
            array(
                'name'    => __( 'Honeymoon', 'moga-travel-core' ),
                'slug'    => 'honeymoon',
                'desc'    => __( 'Romantic tours for couples and newlyweds.', 'moga-travel-core' ),
                'emoji'   => '💑',
                'color'   => '#e83e8c',
                'icon'    => 'dashicons-heart',
                'popular' => 0,
                'order'   => 12,
            ),
            array(
                'name'    => __( 'Photography', 'moga-travel-core' ),
                'slug'    => 'photography',
                'desc'    => __( 'Tours designed for photography enthusiasts.', 'moga-travel-core' ),
                'emoji'   => '📸',
                'color'   => '#6c757d',
                'icon'    => 'dashicons-camera',
                'popular' => 0,
                'order'   => 13,
            ),
            array(
                'name'    => __( 'Religious', 'moga-travel-core' ),
                'slug'    => 'religious',
                'desc'    => __( 'Spiritual and religious site visits and pilgrimages.', 'moga-travel-core' ),
                'emoji'   => '🕌',
                'color'   => '#28a745',
                'icon'    => 'dashicons-admin-site',
                'popular' => 0,
                'order'   => 14,
            ),
            array(
                'name'    => __( 'Food & Cuisine', 'moga-travel-core' ),
                'slug'    => 'food-cuisine',
                'desc'    => __( 'Culinary tours exploring local food markets and restaurants.', 'moga-travel-core' ),
                'emoji'   => '🍽️',
                'color'   => '#fd7e14',
                'icon'    => 'dashicons-food',
                'popular' => 0,
                'order'   => 15,
            ),
            array(
                'name'    => __( 'Bird Watching', 'moga-travel-core' ),
                'slug'    => 'bird-watching',
                'desc'    => __( 'Nature tours focused on Egypt\'s diverse bird species.', 'moga-travel-core' ),
                'emoji'   => '🦅',
                'color'   => '#20c997',
                'icon'    => 'dashicons-visibility',
                'popular' => 0,
                'order'   => 16,
            ),
        );

        foreach ( $default_terms as $term ) {

            $result = wp_insert_term(
                $term['name'],
                self::TAXONOMY,
                array(
                    'slug'        => $term['slug'],
                    'description' => $term['desc'],
                )
            );

            if ( ! is_wp_error( $result ) ) {
                $term_id = $result['term_id'];
                update_term_meta( $term_id, 'moga_emoji',   $term['emoji'] );
                update_term_meta( $term_id, 'moga_color',   $term['color'] );
                update_term_meta( $term_id, 'moga_icon',    $term['icon'] );
                update_term_meta( $term_id, 'moga_popular', $term['popular'] );
                update_term_meta( $term_id, 'moga_order',   $term['order'] );
            }
        }

        // Mark as created.
        update_option( 'moga_tour_categories_created', true );
    }


    /**
     * Get all tour categories.
     *
     * @since  1.0.0
     * @param  bool $popular_only Return only popular categories.
     * @return array
     */
    public static function get_all( $popular_only = false ) {

        $meta_query = array();

        if ( $popular_only ) {
            $meta_query[] = array(
                'key'     => 'moga_popular',
                'value'   => '1',
                'compare' => '=',
            );
        }

        $terms = get_terms( array(
            'taxonomy'   => self::TAXONOMY,
            'hide_empty' => false,
            'orderby'    => 'meta_value_num',
            'meta_key'   => 'moga_order',
            'order'      => 'ASC',
            'meta_query' => $meta_query,
        ) );

        if ( is_wp_error( $terms ) || empty( $terms ) ) {
            return array();
        }

        $result = array();

        foreach ( $terms as $term ) {
            $result[] = array(
                'id'      => $term->term_id,
                'name'    => $term->name,
                'slug'    => $term->slug,
                'count'   => $term->count,
                'emoji'   => get_term_meta( $term->term_id, 'moga_emoji', true ),
                'color'   => get_term_meta( $term->term_id, 'moga_color', true ),
                'icon'    => get_term_meta( $term->term_id, 'moga_icon', true ),
                'popular' => get_term_meta( $term->term_id, 'moga_popular', true ),
                'order'   => get_term_meta( $term->term_id, 'moga_order', true ),
                'link'    => get_term_link( $term ),
            );
        }

        return $result;
    }


    /**
     * Get tour categories formatted for a select dropdown.
     *
     * @since  1.0.0
     * @return array term_id => "emoji name" pairs.
     */
    public static function get_dropdown() {

        $terms = get_terms( array(
            'taxonomy'   => self::TAXONOMY,
            'hide_empty' => false,
            'orderby'    => 'meta_value_num',
            'meta_key'   => 'moga_order',
            'order'      => 'ASC',
        ) );

        $result = array(
            '' => __( '— Select Tour Category —', 'moga-travel-core' ),
        );

        if ( is_wp_error( $terms ) || empty( $terms ) ) {
            return $result;
        }

        foreach ( $terms as $term ) {
            $emoji              = get_term_meta( $term->term_id, 'moga_emoji', true );
            $label              = $emoji ? $emoji . ' ' . $term->name : $term->name;
            $result[ $term->term_id ] = $label;
        }

        return $result;
    }
}