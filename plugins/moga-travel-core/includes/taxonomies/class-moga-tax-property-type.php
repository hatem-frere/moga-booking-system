<?php
/**
 * Property Type Taxonomy
 *
 * Registers the moga_property_type taxonomy.
 * Used to categorize properties into types such as
 * Hotel, Apartment, Villa, Chalet, Resort, etc.
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
 * Class Moga_Tax_Property_Type
 */
class Moga_Tax_Property_Type {

    /**
     * Taxonomy key.
     *
     * @since 1.0.0
     * @var   string
     */
    const TAXONOMY = 'moga_property_type';

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
            'name'                       => _x( 'Property Types', 'Taxonomy general name', 'moga-travel-core' ),
            'singular_name'              => _x( 'Property Type', 'Taxonomy singular name', 'moga-travel-core' ),
            'search_items'               => __( 'Search Property Types', 'moga-travel-core' ),
            'popular_items'              => __( 'Popular Property Types', 'moga-travel-core' ),
            'all_items'                  => __( 'All Property Types', 'moga-travel-core' ),
            'parent_item'                => __( 'Parent Property Type', 'moga-travel-core' ),
            'parent_item_colon'          => __( 'Parent Property Type:', 'moga-travel-core' ),
            'edit_item'                  => __( 'Edit Property Type', 'moga-travel-core' ),
            'view_item'                  => __( 'View Property Type', 'moga-travel-core' ),
            'update_item'                => __( 'Update Property Type', 'moga-travel-core' ),
            'add_new_item'               => __( 'Add New Property Type', 'moga-travel-core' ),
            'new_item_name'              => __( 'New Property Type Name', 'moga-travel-core' ),
            'separate_items_with_commas' => __( 'Separate property types with commas', 'moga-travel-core' ),
            'add_or_remove_items'        => __( 'Add or remove property types', 'moga-travel-core' ),
            'choose_from_most_used'      => __( 'Choose from the most used property types', 'moga-travel-core' ),
            'not_found'                  => __( 'No property types found.', 'moga-travel-core' ),
            'no_terms'                   => __( 'No property types', 'moga-travel-core' ),
            'menu_name'                  => __( 'Property Types', 'moga-travel-core' ),
            'items_list_navigation'      => __( 'Property types list navigation', 'moga-travel-core' ),
            'items_list'                 => __( 'Property types list', 'moga-travel-core' ),
            'back_to_items'              => __( '← Back to Property Types', 'moga-travel-core' ),
        );

        $args = array(
            'labels'            => $labels,
            'description'       => __( 'Categorize properties by type (Hotel, Apartment, Villa, etc.)', 'moga-travel-core' ),

            // Hierarchical = true means it works like
            // categories (with parent/child) rather than tags.
            // e.g. Accommodation > Hotel > Boutique Hotel
            'hierarchical'      => true,

            // Visibility.
            'public'            => true,
            'publicly_queryable' => true,
            'show_ui'           => true,
            'show_in_menu'      => true,
            'show_in_nav_menus' => true,
            'show_in_rest'      => true,
            'show_tagcloud'     => false,
            'show_in_quick_edit' => true,
            'show_admin_column' => true,

            // URLs.
            'rewrite'           => array(
                'slug'         => 'property-type',
                'with_front'   => false,
                'hierarchical' => true,
            ),

            // Query.
            'query_var'         => true,
        );

        register_taxonomy(
            self::TAXONOMY,
            array( 'moga_property' ),
            $args
        );

        // Register taxonomy meta fields.
        self::register_meta_fields();

        // Create default terms on first run.
        self::create_default_terms();
    }


    /**
     * Register taxonomy meta fields.
     * Adds extra data to each property type term.
     *
     * @since  1.0.0
     * @return void
     */
    private static function register_meta_fields() {

        // Icon for the property type (dashicon or SVG key).
        register_term_meta(
            self::TAXONOMY,
            'moga_icon',
            array(
                'type'              => 'string',
                'description'       => __( 'Icon key for this property type.', 'moga-travel-core' ),
                'single'            => true,
                'default'           => 'dashicons-building',
                'show_in_rest'      => true,
                'sanitize_callback' => 'sanitize_text_field',
            )
        );

        // Emoji for the property type.
        register_term_meta(
            self::TAXONOMY,
            'moga_emoji',
            array(
                'type'              => 'string',
                'description'       => __( 'Emoji for this property type.', 'moga-travel-core' ),
                'single'            => true,
                'default'           => '🏨',
                'show_in_rest'      => true,
                'sanitize_callback' => 'sanitize_text_field',
            )
        );

        // Color for the property type badge.
        register_term_meta(
            self::TAXONOMY,
            'moga_color',
            array(
                'type'              => 'string',
                'description'       => __( 'Brand color for this property type (hex).', 'moga-travel-core' ),
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
    }


    /**
     * Create default property type terms on first activation.
     * Uses wp_insert_term() which safely skips
     * existing terms — safe to run multiple times.
     *
     * @since  1.0.0
     * @return void
     */
    private static function create_default_terms() {

        // Only run once — check if terms already exist.
        if ( get_option( 'moga_property_types_created' ) ) {
            return;
        }

        $default_terms = array(

            // ---- Accommodation ----
            array(
                'name'        => __( 'Hotel', 'moga-travel-core' ),
                'slug'        => 'hotel',
                'description' => __( 'Traditional hotels with full services and amenities.', 'moga-travel-core' ),
                'emoji'       => '🏨',
                'color'       => '#003580',
                'order'       => 1,
            ),
            array(
                'name'        => __( 'Apartment', 'moga-travel-core' ),
                'slug'        => 'apartment',
                'description' => __( 'Self-contained apartments with kitchen facilities.', 'moga-travel-core' ),
                'emoji'       => '🏢',
                'color'       => '#0071c2',
                'order'       => 2,
            ),
            array(
                'name'        => __( 'Villa', 'moga-travel-core' ),
                'slug'        => 'villa',
                'description' => __( 'Luxury villas with private pool and garden.', 'moga-travel-core' ),
                'emoji'       => '🏡',
                'color'       => '#28a745',
                'order'       => 3,
            ),
            array(
                'name'        => __( 'Chalet', 'moga-travel-core' ),
                'slug'        => 'chalet',
                'description' => __( 'Cozy chalets ideal for families and groups.', 'moga-travel-core' ),
                'emoji'       => '🏠',
                'color'       => '#f5a623',
                'order'       => 4,
            ),
            array(
                'name'        => __( 'Resort', 'moga-travel-core' ),
                'slug'        => 'resort',
                'description' => __( 'Full-service resorts with beach and pool access.', 'moga-travel-core' ),
                'emoji'       => '🏖️',
                'color'       => '#17a2b8',
                'order'       => 5,
            ),
            array(
                'name'        => __( 'Hostel', 'moga-travel-core' ),
                'slug'        => 'hostel',
                'description' => __( 'Budget-friendly shared accommodation.', 'moga-travel-core' ),
                'emoji'       => '🛏️',
                'color'       => '#6c757d',
                'order'       => 6,
            ),
            array(
                'name'        => __( 'Guest House', 'moga-travel-core' ),
                'slug'        => 'guest-house',
                'description' => __( 'Small privately-owned accommodation with personal service.', 'moga-travel-core' ),
                'emoji'       => '🏘️',
                'color'       => '#6f42c1',
                'order'       => 7,
            ),
            array(
                'name'        => __( 'Boutique Hotel', 'moga-travel-core' ),
                'slug'        => 'boutique-hotel',
                'description' => __( 'Small stylish hotels with unique character and design.', 'moga-travel-core' ),
                'emoji'       => '✨',
                'color'       => '#e83e8c',
                'order'       => 8,
            ),
            array(
                'name'        => __( 'Studio', 'moga-travel-core' ),
                'slug'        => 'studio',
                'description' => __( 'Compact studio apartments for solo travelers or couples.', 'moga-travel-core' ),
                'emoji'       => '🛋️',
                'color'       => '#20c997',
                'order'       => 9,
            ),
            array(
                'name'        => __( 'Penthouse', 'moga-travel-core' ),
                'slug'        => 'penthouse',
                'description' => __( 'Luxury top-floor apartments with panoramic views.', 'moga-travel-core' ),
                'emoji'       => '🌆',
                'color'       => '#fd7e14',
                'order'       => 10,
            ),
            array(
                'name'        => __( 'Camping', 'moga-travel-core' ),
                'slug'        => 'camping',
                'description' => __( 'Outdoor camping sites and glamping experiences.', 'moga-travel-core' ),
                'emoji'       => '⛺',
                'color'       => '#28a745',
                'order'       => 11,
            ),
            array(
                'name'        => __( 'Floating Hotel', 'moga-travel-core' ),
                'slug'        => 'floating-hotel',
                'description' => __( 'Nile cruise ships and floating hotels.', 'moga-travel-core' ),
                'emoji'       => '🚢',
                'color'       => '#003580',
                'order'       => 12,
            ),
        );

        foreach ( $default_terms as $term ) {

            // Insert term if it does not exist.
            $result = wp_insert_term(
                $term['name'],
                self::TAXONOMY,
                array(
                    'slug'        => $term['slug'],
                    'description' => $term['description'],
                )
            );

            // If term was created successfully, add its meta.
            if ( ! is_wp_error( $result ) ) {
                $term_id = $result['term_id'];
                update_term_meta( $term_id, 'moga_emoji', $term['emoji'] );
                update_term_meta( $term_id, 'moga_color', $term['color'] );
                update_term_meta( $term_id, 'moga_order', $term['order'] );
            }
        }

        // Mark as created so this never runs again.
        update_option( 'moga_property_types_created', true );
    }


    /**
     * Get all property types for use in dropdowns and filters.
     *
     * @since  1.0.0
     * @param  bool $include_count Whether to include property count.
     * @return array
     */
    public static function get_all( $include_count = false ) {

        $terms = get_terms( array(
            'taxonomy'   => self::TAXONOMY,
            'hide_empty' => false,
            'orderby'    => 'meta_value_num',
            'meta_key'   => 'moga_order',
            'order'      => 'ASC',
        ) );

        if ( is_wp_error( $terms ) || empty( $terms ) ) {
            return array();
        }

        $result = array();

        foreach ( $terms as $term ) {
            $result[] = array(
                'id'    => $term->term_id,
                'name'  => $term->name,
                'slug'  => $term->slug,
                'count' => $include_count ? $term->count : null,
                'emoji' => get_term_meta( $term->term_id, 'moga_emoji', true ),
                'color' => get_term_meta( $term->term_id, 'moga_color', true ),
                'order' => get_term_meta( $term->term_id, 'moga_order', true ),
                'link'  => get_term_link( $term ),
            );
        }

        return $result;
    }


    /**
     * Get property types formatted for a select dropdown.
     *
     * @since  1.0.0
     * @return array term_id => name pairs.
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
            '' => __( '— Select Property Type —', 'moga-travel-core' ),
        );

        if ( is_wp_error( $terms ) || empty( $terms ) ) {
            return $result;
        }

        foreach ( $terms as $term ) {
            $emoji          = get_term_meta( $term->term_id, 'moga_emoji', true );
            $label          = $emoji ? $emoji . ' ' . $term->name : $term->name;
            $result[ $term->term_id ] = $label;
        }

        return $result;
    }
}