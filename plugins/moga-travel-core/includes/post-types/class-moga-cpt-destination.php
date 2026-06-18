<?php
/**
 * Destination Custom Post Type
 *
 * Registers the moga_destination custom post type.
 * Each destination is a dedicated page showcasing
 * a city or region with its properties and tours.
 *
 * @package    MogaTravelCore
 * @subpackage MogaTravelCore/includes/post-types
 * @author     Hatem Frere
 * @since      1.0.0
 */

// Block direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Moga_CPT_Destination
 */
class Moga_CPT_Destination {

    /**
     * Post type key.
     *
     * @since 1.0.0
     * @var   string
     */
    const POST_TYPE = 'moga_destination';

    /**
     * Register the custom post type.
     * Called from Moga_Core::register_post_types()
     * on the 'init' hook.
     *
     * @since  1.0.0
     * @return void
     */
    public static function register() {

        $labels = array(
            'name'                  => _x( 'Destinations', 'Post type general name', 'moga-travel-core' ),
            'singular_name'         => _x( 'Destination', 'Post type singular name', 'moga-travel-core' ),
            'menu_name'             => _x( 'Destinations', 'Admin Menu text', 'moga-travel-core' ),
            'name_admin_bar'        => _x( 'Destination', 'Add New on Toolbar', 'moga-travel-core' ),
            'add_new'               => __( 'Add New', 'moga-travel-core' ),
            'add_new_item'          => __( 'Add New Destination', 'moga-travel-core' ),
            'new_item'              => __( 'New Destination', 'moga-travel-core' ),
            'edit_item'             => __( 'Edit Destination', 'moga-travel-core' ),
            'view_item'             => __( 'View Destination', 'moga-travel-core' ),
            'all_items'             => __( 'All Destinations', 'moga-travel-core' ),
            'search_items'          => __( 'Search Destinations', 'moga-travel-core' ),
            'not_found'             => __( 'No destinations found.', 'moga-travel-core' ),
            'not_found_in_trash'    => __( 'No destinations found in Trash.', 'moga-travel-core' ),
            'featured_image'        => __( 'Destination Hero Image', 'moga-travel-core' ),
            'set_featured_image'    => __( 'Set hero image', 'moga-travel-core' ),
            'remove_featured_image' => __( 'Remove hero image', 'moga-travel-core' ),
            'use_featured_image'    => __( 'Use as hero image', 'moga-travel-core' ),
            'archives'              => __( 'Destination Archives', 'moga-travel-core' ),
            'insert_into_item'      => __( 'Insert into destination', 'moga-travel-core' ),
            'uploaded_to_this_item' => __( 'Uploaded to this destination', 'moga-travel-core' ),
            'filter_items_list'     => __( 'Filter destinations list', 'moga-travel-core' ),
            'items_list_navigation' => __( 'Destinations list navigation', 'moga-travel-core' ),
            'items_list'            => __( 'Destinations list', 'moga-travel-core' ),
        );

        $args = array(
            'labels'              => $labels,
            'description'         => __( 'Travel destinations with featured properties and tours.', 'moga-travel-core' ),

            // Visibility.
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_nav_menus'   => true,
            'show_in_admin_bar'   => true,
            'show_in_rest'        => true,

            // Admin.
            'menu_position'       => 8,
            'menu_icon'           => 'dashicons-palmtree',

            // Capabilities.
            'capability_type'     => 'post',
            'map_meta_cap'        => true,

            // Supports.
            'supports'            => array(
                'title',         // Destination name.
                'editor',        // Full description.
                'thumbnail',     // Hero image.
                'excerpt',       // Short tagline.
                'revisions',
                'custom-fields',
                'page-attributes',
            ),

            // No taxonomies needed —
            // destinations ARE the location reference.
            'taxonomies'          => array(),

            // URLs.
            'rewrite'             => array(
                'slug'       => 'destinations',
                'with_front' => false,
                'feeds'      => false,
                'pages'      => true,
            ),

            // Query.
            'query_var'           => true,
            'has_archive'         => 'destinations',

            // Hierarchical — allows parent/child
            // e.g. Egypt > Hurghada > Sahl Hasheesh.
            'hierarchical'        => true,
        );

        register_post_type( self::POST_TYPE, $args );

        // Register meta fields.
        self::register_meta_fields();
    }


    /**
     * Register all destination meta fields.
     *
     * @since  1.0.0
     * @return void
     */
    private static function register_meta_fields() {

        $meta_fields = array(

            // ---- Location Identity ----
            '_moga_country'              => array(
                'type'        => 'string',
                'description' => __( 'Country ISO code (e.g. EG).', 'moga-travel-core' ),
                'default'     => '',
            ),
            '_moga_country_name'         => array(
                'type'        => 'string',
                'description' => __( 'Country full name.', 'moga-travel-core' ),
                'default'     => '',
            ),
            '_moga_city'                 => array(
                'type'        => 'string',
                'description' => __( 'City name.', 'moga-travel-core' ),
                'default'     => '',
            ),
            '_moga_region'               => array(
                'type'        => 'string',
                'description' => __( 'Region or governorate name.', 'moga-travel-core' ),
                'default'     => '',
            ),
            '_moga_timezone'             => array(
                'type'        => 'string',
                'description' => __( 'Local timezone (e.g. Africa/Cairo).', 'moga-travel-core' ),
                'default'     => '',
            ),
            '_moga_currency'             => array(
                'type'        => 'string',
                'description' => __( 'Local currency code (e.g. EGP).', 'moga-travel-core' ),
                'default'     => '',
            ),
            '_moga_language'             => array(
                'type'        => 'string',
                'description' => __( 'Primary local language.', 'moga-travel-core' ),
                'default'     => '',
            ),

            // ---- Map & Coordinates ----
            '_moga_latitude'             => array(
                'type'        => 'string',
                'description' => __( 'Center GPS latitude.', 'moga-travel-core' ),
                'default'     => '',
            ),
            '_moga_longitude'            => array(
                'type'        => 'string',
                'description' => __( 'Center GPS longitude.', 'moga-travel-core' ),
                'default'     => '',
            ),
            '_moga_zoom_level'           => array(
                'type'        => 'integer',
                'description' => __( 'Default map zoom level (1-20).', 'moga-travel-core' ),
                'default'     => 12,
            ),

            // ---- Stats (auto-updated) ----
            '_moga_property_count'       => array(
                'type'        => 'integer',
                'description' => __( 'Number of active properties in this destination.', 'moga-travel-core' ),
                'default'     => 0,
            ),
            '_moga_tour_count'           => array(
                'type'        => 'integer',
                'description' => __( 'Number of active tours to this destination.', 'moga-travel-core' ),
                'default'     => 0,
            ),
            '_moga_avg_price'            => array(
                'type'        => 'number',
                'description' => __( 'Average property price per night in this destination.', 'moga-travel-core' ),
                'default'     => 0,
            ),

            // ---- Travel Info ----
            '_moga_best_time'            => array(
                'type'        => 'string',
                'description' => __( 'Best time to visit (e.g. October to April).', 'moga-travel-core' ),
                'default'     => '',
            ),
            '_moga_climate'              => array(
                'type'        => 'string',
                'description' => __( 'Climate type (e.g. Desert, Mediterranean, Tropical).', 'moga-travel-core' ),
                'default'     => '',
            ),
            '_moga_avg_temperature'      => array(
                'type'        => 'string',
                'description' => __( 'Average temperature range (e.g. 15°C - 35°C).', 'moga-travel-core' ),
                'default'     => '',
            ),
            '_moga_known_for'            => array(
                'type'        => 'string',
                'description' => __( 'JSON array of things the destination is known for.', 'moga-travel-core' ),
                'default'     => '',
            ),
            '_moga_highlights'           => array(
                'type'        => 'string',
                'description' => __( 'JSON array of top highlights / attractions.', 'moga-travel-core' ),
                'default'     => '',
            ),

            // ---- Display ----
            '_moga_tagline'              => array(
                'type'        => 'string',
                'description' => __( 'Short catchy tagline (e.g. "City of a Thousand Minarets").', 'moga-travel-core' ),
                'default'     => '',
            ),
            '_moga_emoji'                => array(
                'type'        => 'string',
                'description' => __( 'Emoji representing the destination (e.g. 🏛️).', 'moga-travel-core' ),
                'default'     => '',
            ),
            '_moga_color'                => array(
                'type'        => 'string',
                'description' => __( 'Brand color for this destination card (hex).', 'moga-travel-core' ),
                'default'     => '#003580',
            ),
            '_moga_gallery'              => array(
                'type'        => 'string',
                'description' => __( 'JSON array of gallery image attachment IDs.', 'moga-travel-core' ),
                'default'     => '',
            ),

            // ---- Status ----
            '_moga_featured'             => array(
                'type'        => 'integer',
                'description' => __( 'Featured on homepage (1 = yes).', 'moga-travel-core' ),
                'default'     => 0,
            ),
            '_moga_active'               => array(
                'type'        => 'integer',
                'description' => __( 'Destination is active and visible (1 = yes).', 'moga-travel-core' ),
                'default'     => 1,
            ),
            '_moga_order'                => array(
                'type'        => 'integer',
                'description' => __( 'Display order on homepage and listings.', 'moga-travel-core' ),
                'default'     => 0,
            ),
        );

        foreach ( $meta_fields as $key => $field ) {
            register_post_meta(
                self::POST_TYPE,
                $key,
                array(
                    'type'              => $field['type'],
                    'description'       => $field['description'],
                    'single'            => true,
                    'default'           => $field['default'],
                    'show_in_rest'      => true,
                    'sanitize_callback' => self::get_sanitize_callback( $field['type'] ),
                    'auth_callback'     => function() {
                        return current_user_can( 'edit_posts' );
                    },
                )
            );
        }
    }


    /**
     * Get sanitize callback for a meta field type.
     *
     * @since  1.0.0
     * @param  string $type Field type.
     * @return callable
     */
    private static function get_sanitize_callback( $type ) {
        switch ( $type ) {
            case 'integer':
                return 'absint';
            case 'number':
                return 'floatval';
            case 'string':
            default:
                return 'sanitize_text_field';
        }
    }


    /**
     * Get climate type options.
     *
     * @since  1.0.0
     * @return array
     */
    public static function get_climate_types() {
        return array(
            'desert'        => __( 'Desert', 'moga-travel-core' ),
            'mediterranean' => __( 'Mediterranean', 'moga-travel-core' ),
            'tropical'      => __( 'Tropical', 'moga-travel-core' ),
            'continental'   => __( 'Continental', 'moga-travel-core' ),
            'alpine'        => __( 'Alpine', 'moga-travel-core' ),
            'coastal'       => __( 'Coastal', 'moga-travel-core' ),
            'arid'          => __( 'Arid', 'moga-travel-core' ),
            'humid'         => __( 'Humid', 'moga-travel-core' ),
        );
    }


    /**
     * Get "known for" tag options.
     * Things a destination is famous for.
     *
     * @since  1.0.0
     * @return array
     */
    public static function get_known_for_options() {
        return array(
            'beaches'        => __( 'Beaches', 'moga-travel-core' ),
            'diving'         => __( 'Scuba Diving & Snorkeling', 'moga-travel-core' ),
            'history'        => __( 'History & Archaeology', 'moga-travel-core' ),
            'pyramids'       => __( 'Pyramids & Monuments', 'moga-travel-core' ),
            'temples'        => __( 'Temples & Ancient Sites', 'moga-travel-core' ),
            'shopping'       => __( 'Shopping & Souks', 'moga-travel-core' ),
            'nightlife'      => __( 'Nightlife & Entertainment', 'moga-travel-core' ),
            'cuisine'        => __( 'Local Cuisine & Food', 'moga-travel-core' ),
            'desert_safari'  => __( 'Desert Safari', 'moga-travel-core' ),
            'nile_cruise'    => __( 'Nile Cruise', 'moga-travel-core' ),
            'water_sports'   => __( 'Water Sports', 'moga-travel-core' ),
            'hiking'         => __( 'Hiking & Trekking', 'moga-travel-core' ),
            'family'         => __( 'Family Friendly', 'moga-travel-core' ),
            'honeymoon'      => __( 'Honeymoon & Romance', 'moga-travel-core' ),
            'culture'        => __( 'Culture & Arts', 'moga-travel-core' ),
            'nature'         => __( 'Nature & Wildlife', 'moga-travel-core' ),
            'luxury'         => __( 'Luxury Resorts', 'moga-travel-core' ),
            'budget'         => __( 'Budget Friendly', 'moga-travel-core' ),
            'religious'      => __( 'Religious & Spiritual Sites', 'moga-travel-core' ),
            'photography'    => __( 'Photography Spots', 'moga-travel-core' ),
        );
    }


    /**
     * Get all active destinations for use in dropdowns.
     *
     * @since  1.0.0
     * @return array Destination post ID => name pairs.
     */
    public static function get_destinations_dropdown() {

        $destinations = get_posts( array(
            'post_type'      => self::POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => array(
                array(
                    'key'     => '_moga_active',
                    'value'   => '1',
                    'compare' => '=',
                ),
            ),
            'orderby'        => 'title',
            'order'          => 'ASC',
        ) );

        $result = array(
            '' => __( '— Select Destination —', 'moga-travel-core' ),
        );

        foreach ( $destinations as $destination ) {
            $country = get_post_meta( $destination->ID, '_moga_country_name', true );
            $label   = $destination->post_title;

            if ( $country ) {
                $label .= ' (' . $country . ')';
            }

            $result[ $destination->ID ] = $label;
        }

        return $result;
    }


    /**
     * Update destination property and tour counts.
     * Called automatically when a property or tour
     * is saved, published, or deleted.
     *
     * @since  1.0.0
     * @param  int    $destination_id Destination post ID.
     * @return void
     */
    public static function update_counts( $destination_id ) {

        if ( ! $destination_id ) {
            return;
        }

        $destination_post = get_post( $destination_id );

        if ( ! $destination_post
            || self::POST_TYPE !== $destination_post->post_type
        ) {
            return;
        }

        $city = get_post_meta( $destination_id, '_moga_city', true );

        if ( ! $city ) {
            return;
        }

        // Count active properties in this city.
        $property_count = new WP_Query( array(
            'post_type'      => 'moga_property',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'     => array(
                array(
                    'key'     => '_moga_city',
                    'value'   => $city,
                    'compare' => '=',
                ),
            ),
        ) );

        // Count active tours to this city.
        $tour_count = new WP_Query( array(
            'post_type'      => 'moga_tour',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'     => array(
                array(
                    'key'     => '_moga_destination_city',
                    'value'   => $city,
                    'compare' => '=',
                ),
            ),
        ) );

        // Update the counts.
        update_post_meta(
            $destination_id,
            '_moga_property_count',
            $property_count->found_posts
        );

        update_post_meta(
            $destination_id,
            '_moga_tour_count',
            $tour_count->found_posts
        );
    }
}