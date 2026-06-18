<?php
/**
 * Location Taxonomy
 *
 * Registers the moga_location taxonomy.
 * Hierarchical location system shared between
 * properties and tours.
 *
 * Structure:
 *   Level 1 — Country  (e.g. Egypt)
 *   Level 2 — City     (e.g. Cairo)
 *   Level 3 — District (e.g. Downtown Cairo)
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
 * Class Moga_Tax_Location
 */
class Moga_Tax_Location {

    /**
     * Taxonomy key.
     *
     * @since 1.0.0
     * @var   string
     */
    const TAXONOMY = 'moga_location';

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
            'name'                       => _x( 'Locations', 'Taxonomy general name', 'moga-travel-core' ),
            'singular_name'              => _x( 'Location', 'Taxonomy singular name', 'moga-travel-core' ),
            'search_items'               => __( 'Search Locations', 'moga-travel-core' ),
            'popular_items'              => __( 'Popular Locations', 'moga-travel-core' ),
            'all_items'                  => __( 'All Locations', 'moga-travel-core' ),
            'parent_item'                => __( 'Parent Location', 'moga-travel-core' ),
            'parent_item_colon'          => __( 'Parent Location:', 'moga-travel-core' ),
            'edit_item'                  => __( 'Edit Location', 'moga-travel-core' ),
            'view_item'                  => __( 'View Location', 'moga-travel-core' ),
            'update_item'                => __( 'Update Location', 'moga-travel-core' ),
            'add_new_item'               => __( 'Add New Location', 'moga-travel-core' ),
            'new_item_name'              => __( 'New Location Name', 'moga-travel-core' ),
            'separate_items_with_commas' => __( 'Separate locations with commas', 'moga-travel-core' ),
            'add_or_remove_items'        => __( 'Add or remove locations', 'moga-travel-core' ),
            'choose_from_most_used'      => __( 'Choose from the most used locations', 'moga-travel-core' ),
            'not_found'                  => __( 'No locations found.', 'moga-travel-core' ),
            'no_terms'                   => __( 'No locations', 'moga-travel-core' ),
            'menu_name'                  => __( 'Locations', 'moga-travel-core' ),
            'items_list_navigation'      => __( 'Locations list navigation', 'moga-travel-core' ),
            'items_list'                 => __( 'Locations list', 'moga-travel-core' ),
            'back_to_items'              => __( '← Back to Locations', 'moga-travel-core' ),
        );

        $args = array(
            'labels'             => $labels,
            'description'        => __( 'Hierarchical location system: Country > City > District.', 'moga-travel-core' ),

            // Hierarchical = true means it works
            // like categories with parent/child levels.
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
                'slug'         => 'location',
                'with_front'   => false,
                'hierarchical' => true,
            ),

            // Query.
            'query_var'          => true,
        );

        // Attach to both properties and tours.
        register_taxonomy(
            self::TAXONOMY,
            array( 'moga_property', 'moga_tour' ),
            $args
        );

        // Register taxonomy meta fields.
        self::register_meta_fields();

        // Create default location terms.
        self::create_default_terms();
    }


    /**
     * Register taxonomy meta fields.
     * Adds extra data to each location term.
     *
     * @since  1.0.0
     * @return void
     */
    private static function register_meta_fields() {

        // Location level (country, city, district).
        register_term_meta(
            self::TAXONOMY,
            'moga_level',
            array(
                'type'              => 'string',
                'description'       => __( 'Location level: country, city, or district.', 'moga-travel-core' ),
                'single'            => true,
                'default'           => 'city',
                'show_in_rest'      => true,
                'sanitize_callback' => 'sanitize_text_field',
            )
        );

        // ISO country code.
        register_term_meta(
            self::TAXONOMY,
            'moga_country_code',
            array(
                'type'              => 'string',
                'description'       => __( 'ISO 3166-1 alpha-2 country code (e.g. EG, US).', 'moga-travel-core' ),
                'single'            => true,
                'default'           => '',
                'show_in_rest'      => true,
                'sanitize_callback' => 'sanitize_text_field',
            )
        );

        // Country flag emoji.
        register_term_meta(
            self::TAXONOMY,
            'moga_flag',
            array(
                'type'              => 'string',
                'description'       => __( 'Country flag emoji (e.g. 🇪🇬).', 'moga-travel-core' ),
                'single'            => true,
                'default'           => '',
                'show_in_rest'      => true,
                'sanitize_callback' => 'sanitize_text_field',
            )
        );

        // GPS latitude.
        register_term_meta(
            self::TAXONOMY,
            'moga_latitude',
            array(
                'type'              => 'string',
                'description'       => __( 'Center GPS latitude coordinate.', 'moga-travel-core' ),
                'single'            => true,
                'default'           => '',
                'show_in_rest'      => true,
                'sanitize_callback' => 'sanitize_text_field',
            )
        );

        // GPS longitude.
        register_term_meta(
            self::TAXONOMY,
            'moga_longitude',
            array(
                'type'              => 'string',
                'description'       => __( 'Center GPS longitude coordinate.', 'moga-travel-core' ),
                'single'            => true,
                'default'           => '',
                'show_in_rest'      => true,
                'sanitize_callback' => 'sanitize_text_field',
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
                'description'       => __( 'Mark as popular destination (1 = yes).', 'moga-travel-core' ),
                'single'            => true,
                'default'           => 0,
                'show_in_rest'      => true,
                'sanitize_callback' => 'absint',
            )
        );
    }


    /**
     * Create default location terms on first activation.
     *
     * Structure created:
     *   Egypt (country)
     *     ├── Cairo (city)
     *     │     ├── Downtown Cairo (district)
     *     │     ├── New Cairo (district)
     *     │     └── Zamalek (district)
     *     ├── Alexandria (city)
     *     ├── Hurghada (city)
     *     │     └── Sahl Hasheesh (district)
     *     ├── Sharm El Sheikh (city)
     *     │     └── Naama Bay (district)
     *     ├── Luxor (city)
     *     ├── Aswan (city)
     *     ├── Dahab (city)
     *     ├── Marsa Matrouh (city)
     *     └── Siwa Oasis (city)
     *
     * @since  1.0.0
     * @return void
     */
    private static function create_default_terms() {

        // Only run once.
        if ( get_option( 'moga_locations_created' ) ) {
            return;
        }

        // --------------------------------------------------------
        // Level 1 — Country: Egypt
        // --------------------------------------------------------
        $egypt = wp_insert_term(
            __( 'Egypt', 'moga-travel-core' ),
            self::TAXONOMY,
            array(
                'slug'        => 'egypt',
                'description' => __( 'Arab Republic of Egypt', 'moga-travel-core' ),
            )
        );

        if ( is_wp_error( $egypt ) ) {
            // Term might already exist — get its ID.
            $existing = get_term_by( 'slug', 'egypt', self::TAXONOMY );
            $egypt_id = $existing ? $existing->term_id : 0;
        } else {
            $egypt_id = $egypt['term_id'];
        }

        if ( $egypt_id ) {
            update_term_meta( $egypt_id, 'moga_level',        'country' );
            update_term_meta( $egypt_id, 'moga_country_code', 'EG' );
            update_term_meta( $egypt_id, 'moga_flag',         '🇪🇬' );
            update_term_meta( $egypt_id, 'moga_latitude',     '26.8206' );
            update_term_meta( $egypt_id, 'moga_longitude',    '30.8025' );
            update_term_meta( $egypt_id, 'moga_order',        1 );
            update_term_meta( $egypt_id, 'moga_popular',      1 );
        }

        // --------------------------------------------------------
        // Level 2 — Egyptian Cities
        // --------------------------------------------------------
        $cities = array(
            array(
                'name'    => __( 'Cairo', 'moga-travel-core' ),
                'slug'    => 'cairo',
                'desc'    => __( 'Capital city of Egypt — the city of a thousand minarets.', 'moga-travel-core' ),
                'lat'     => '30.0444',
                'lng'     => '31.2357',
                'order'   => 1,
                'popular' => 1,
                'districts' => array(
                    array(
                        'name'  => __( 'Downtown Cairo', 'moga-travel-core' ),
                        'slug'  => 'downtown-cairo',
                        'lat'   => '30.0478',
                        'lng'   => '31.2336',
                        'order' => 1,
                    ),
                    array(
                        'name'  => __( 'New Cairo', 'moga-travel-core' ),
                        'slug'  => 'new-cairo',
                        'lat'   => '30.0270',
                        'lng'   => '31.4961',
                        'order' => 2,
                    ),
                    array(
                        'name'  => __( 'Zamalek', 'moga-travel-core' ),
                        'slug'  => 'zamalek',
                        'lat'   => '30.0626',
                        'lng'   => '31.2197',
                        'order' => 3,
                    ),
                    array(
                        'name'  => __( 'Maadi', 'moga-travel-core' ),
                        'slug'  => 'maadi',
                        'lat'   => '29.9602',
                        'lng'   => '31.2569',
                        'order' => 4,
                    ),
                    array(
                        'name'  => __( 'Heliopolis', 'moga-travel-core' ),
                        'slug'  => 'heliopolis',
                        'lat'   => '30.0911',
                        'lng'   => '31.3424',
                        'order' => 5,
                    ),
                    array(
                        'name'  => __( 'Nasr City', 'moga-travel-core' ),
                        'slug'  => 'nasr-city',
                        'lat'   => '30.0626',
                        'lng'   => '31.3342',
                        'order' => 6,
                    ),
                    array(
                        'name'  => __( 'Giza', 'moga-travel-core' ),
                        'slug'  => 'giza',
                        'lat'   => '30.0131',
                        'lng'   => '31.2089',
                        'order' => 7,
                    ),
                ),
            ),
            array(
                'name'    => __( 'Alexandria', 'moga-travel-core' ),
                'slug'    => 'alexandria',
                'desc'    => __( 'Egypt\'s Mediterranean coastal city — the pearl of the Mediterranean.', 'moga-travel-core' ),
                'lat'     => '31.2001',
                'lng'     => '29.9187',
                'order'   => 2,
                'popular' => 1,
                'districts' => array(
                    array(
                        'name'  => __( 'Corniche Alexandria', 'moga-travel-core' ),
                        'slug'  => 'corniche-alexandria',
                        'lat'   => '31.2156',
                        'lng'   => '29.9553',
                        'order' => 1,
                    ),
                    array(
                        'name'  => __( 'Smouha', 'moga-travel-core' ),
                        'slug'  => 'smouha',
                        'lat'   => '31.1980',
                        'lng'   => '29.9592',
                        'order' => 2,
                    ),
                    array(
                        'name'  => __( 'Montaza', 'moga-travel-core' ),
                        'slug'  => 'montaza',
                        'lat'   => '31.2849',
                        'lng'   => '30.0147',
                        'order' => 3,
                    ),
                ),
            ),
            array(
                'name'    => __( 'Hurghada', 'moga-travel-core' ),
                'slug'    => 'hurghada',
                'desc'    => __( 'Red Sea resort city famous for beaches and diving.', 'moga-travel-core' ),
                'lat'     => '27.2579',
                'lng'     => '33.8116',
                'order'   => 3,
                'popular' => 1,
                'districts' => array(
                    array(
                        'name'  => __( 'Sahl Hasheesh', 'moga-travel-core' ),
                        'slug'  => 'sahl-hasheesh',
                        'lat'   => '27.1167',
                        'lng'   => '33.8833',
                        'order' => 1,
                    ),
                    array(
                        'name'  => __( 'El Gouna', 'moga-travel-core' ),
                        'slug'  => 'el-gouna',
                        'lat'   => '27.3950',
                        'lng'   => '33.6783',
                        'order' => 2,
                    ),
                    array(
                        'name'  => __( 'Makadi Bay', 'moga-travel-core' ),
                        'slug'  => 'makadi-bay',
                        'lat'   => '27.0333',
                        'lng'   => '33.9167',
                        'order' => 3,
                    ),
                ),
            ),
            array(
                'name'    => __( 'Sharm El Sheikh', 'moga-travel-core' ),
                'slug'    => 'sharm-el-sheikh',
                'desc'    => __( 'World-famous Red Sea resort with coral reefs and water sports.', 'moga-travel-core' ),
                'lat'     => '27.9158',
                'lng'     => '34.3300',
                'order'   => 4,
                'popular' => 1,
                'districts' => array(
                    array(
                        'name'  => __( 'Naama Bay', 'moga-travel-core' ),
                        'slug'  => 'naama-bay',
                        'lat'   => '27.9100',
                        'lng'   => '34.3300',
                        'order' => 1,
                    ),
                    array(
                        'name'  => __( 'Nabq Bay', 'moga-travel-core' ),
                        'slug'  => 'nabq-bay',
                        'lat'   => '27.9833',
                        'lng'   => '34.4000',
                        'order' => 2,
                    ),
                    array(
                        'name'  => __( 'Shark\'s Bay', 'moga-travel-core' ),
                        'slug'  => 'sharks-bay',
                        'lat'   => '27.9483',
                        'lng'   => '34.3583',
                        'order' => 3,
                    ),
                ),
            ),
            array(
                'name'    => __( 'Luxor', 'moga-travel-core' ),
                'slug'    => 'luxor',
                'desc'    => __( 'Ancient city of Thebes — open-air museum of ancient Egypt.', 'moga-travel-core' ),
                'lat'     => '25.6872',
                'lng'     => '32.6396',
                'order'   => 5,
                'popular' => 1,
                'districts' => array(
                    array(
                        'name'  => __( 'Luxor East Bank', 'moga-travel-core' ),
                        'slug'  => 'luxor-east-bank',
                        'lat'   => '25.6872',
                        'lng'   => '32.6396',
                        'order' => 1,
                    ),
                    array(
                        'name'  => __( 'Luxor West Bank', 'moga-travel-core' ),
                        'slug'  => 'luxor-west-bank',
                        'lat'   => '25.7306',
                        'lng'   => '32.6000',
                        'order' => 2,
                    ),
                ),
            ),
            array(
                'name'    => __( 'Aswan', 'moga-travel-core' ),
                'slug'    => 'aswan',
                'desc'    => __( 'Nubian city on the Nile — gateway to Abu Simbel.', 'moga-travel-core' ),
                'lat'     => '24.0889',
                'lng'     => '32.8998',
                'order'   => 6,
                'popular' => 1,
                'districts' => array(
                    array(
                        'name'  => __( 'Aswan Corniche', 'moga-travel-core' ),
                        'slug'  => 'aswan-corniche',
                        'lat'   => '24.0950',
                        'lng'   => '32.9000',
                        'order' => 1,
                    ),
                    array(
                        'name'  => __( 'Elephantine Island', 'moga-travel-core' ),
                        'slug'  => 'elephantine-island',
                        'lat'   => '24.0883',
                        'lng'   => '32.8883',
                        'order' => 2,
                    ),
                ),
            ),
            array(
                'name'    => __( 'Dahab', 'moga-travel-core' ),
                'slug'    => 'dahab',
                'desc'    => __( 'Laid-back Sinai beach town famous for diving and snorkeling.', 'moga-travel-core' ),
                'lat'     => '28.4833',
                'lng'     => '34.5167',
                'order'   => 7,
                'popular' => 0,
                'districts' => array(),
            ),
            array(
                'name'    => __( 'Marsa Matrouh', 'moga-travel-core' ),
                'slug'    => 'marsa-matrouh',
                'desc'    => __( 'Mediterranean coastal city with crystal-clear turquoise waters.', 'moga-travel-core' ),
                'lat'     => '31.3543',
                'lng'     => '27.2373',
                'order'   => 8,
                'popular' => 0,
                'districts' => array(),
            ),
            array(
                'name'    => __( 'Siwa Oasis', 'moga-travel-core' ),
                'slug'    => 'siwa-oasis',
                'desc'    => __( 'Remote desert oasis with salt lakes and ancient ruins.', 'moga-travel-core' ),
                'lat'     => '29.2031',
                'lng'     => '25.5196',
                'order'   => 9,
                'popular' => 0,
                'districts' => array(),
            ),
            array(
                'name'    => __( 'Port Said', 'moga-travel-core' ),
                'slug'    => 'port-said',
                'desc'    => __( 'Historic Suez Canal city at the Mediterranean coast.', 'moga-travel-core' ),
                'lat'     => '31.2653',
                'lng'     => '32.3019',
                'order'   => 10,
                'popular' => 0,
                'districts' => array(),
            ),
            array(
                'name'    => __( 'Ain Sokhna', 'moga-travel-core' ),
                'slug'    => 'ain-sokhna',
                'desc'    => __( 'Popular Red Sea resort close to Cairo.', 'moga-travel-core' ),
                'lat'     => '29.5934',
                'lng'     => '32.3455',
                'order'   => 11,
                'popular' => 0,
                'districts' => array(),
            ),
            array(
                'name'    => __( 'North Coast', 'moga-travel-core' ),
                'slug'    => 'north-coast',
                'desc'    => __( 'Egypt\'s Mediterranean north coast with beach resorts.', 'moga-travel-core' ),
                'lat'     => '30.9197',
                'lng'     => '29.5516',
                'order'   => 12,
                'popular' => 0,
                'districts' => array(),
            ),
        );

        // Insert each city and its districts.
        foreach ( $cities as $city ) {

            $city_result = wp_insert_term(
                $city['name'],
                self::TAXONOMY,
                array(
                    'slug'        => $city['slug'],
                    'description' => $city['desc'],
                    'parent'      => $egypt_id,
                )
            );

            if ( is_wp_error( $city_result ) ) {
                $existing = get_term_by( 'slug', $city['slug'], self::TAXONOMY );
                $city_id  = $existing ? $existing->term_id : 0;
            } else {
                $city_id = $city_result['term_id'];
            }

            if ( $city_id ) {
                update_term_meta( $city_id, 'moga_level',        'city' );
                update_term_meta( $city_id, 'moga_country_code', 'EG' );
                update_term_meta( $city_id, 'moga_flag',         '🇪🇬' );
                update_term_meta( $city_id, 'moga_latitude',     $city['lat'] );
                update_term_meta( $city_id, 'moga_longitude',    $city['lng'] );
                update_term_meta( $city_id, 'moga_order',        $city['order'] );
                update_term_meta( $city_id, 'moga_popular',      $city['popular'] );

                // Insert districts for this city.
                foreach ( $city['districts'] as $district ) {

                    $district_result = wp_insert_term(
                        $district['name'],
                        self::TAXONOMY,
                        array(
                            'slug'   => $district['slug'],
                            'parent' => $city_id,
                        )
                    );

                    if ( ! is_wp_error( $district_result ) ) {
                        $district_id = $district_result['term_id'];
                        update_term_meta( $district_id, 'moga_level',        'district' );
                        update_term_meta( $district_id, 'moga_country_code', 'EG' );
                        update_term_meta( $district_id, 'moga_flag',         '🇪🇬' );
                        update_term_meta( $district_id, 'moga_latitude',     $district['lat'] );
                        update_term_meta( $district_id, 'moga_longitude',    $district['lng'] );
                        update_term_meta( $district_id, 'moga_order',        $district['order'] );
                    }
                }
            }
        }

        // Mark as created.
        update_option( 'moga_locations_created', true );
    }


    /**
     * Get all countries from the location taxonomy.
     *
     * @since  1.0.0
     * @return array
     */
    public static function get_countries() {

        $terms = get_terms( array(
            'taxonomy'   => self::TAXONOMY,
            'hide_empty' => false,
            'parent'     => 0,
            'meta_query' => array(
                array(
                    'key'     => 'moga_level',
                    'value'   => 'country',
                    'compare' => '=',
                ),
            ),
        ) );

        if ( is_wp_error( $terms ) || empty( $terms ) ) {
            return array();
        }

        $result = array();

        foreach ( $terms as $term ) {
            $result[] = array(
                'id'   => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
                'code' => get_term_meta( $term->term_id, 'moga_country_code', true ),
                'flag' => get_term_meta( $term->term_id, 'moga_flag', true ),
                'lat'  => get_term_meta( $term->term_id, 'moga_latitude', true ),
                'lng'  => get_term_meta( $term->term_id, 'moga_longitude', true ),
            );
        }

        return $result;
    }


    /**
     * Get cities for a specific country term ID.
     *
     * @since  1.0.0
     * @param  int $country_term_id Country term ID.
     * @return array
     */
    public static function get_cities( $country_term_id ) {

        $terms = get_terms( array(
            'taxonomy'   => self::TAXONOMY,
            'hide_empty' => false,
            'parent'     => $country_term_id,
            'meta_query' => array(
                array(
                    'key'     => 'moga_level',
                    'value'   => 'city',
                    'compare' => '=',
                ),
            ),
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
                'id'      => $term->term_id,
                'name'    => $term->name,
                'slug'    => $term->slug,
                'popular' => get_term_meta( $term->term_id, 'moga_popular', true ),
                'lat'     => get_term_meta( $term->term_id, 'moga_latitude', true ),
                'lng'     => get_term_meta( $term->term_id, 'moga_longitude', true ),
                'link'    => get_term_link( $term ),
            );
        }

        return $result;
    }


    /**
     * Get districts for a specific city term ID.
     *
     * @since  1.0.0
     * @param  int $city_term_id City term ID.
     * @return array
     */
    public static function get_districts( $city_term_id ) {

        $terms = get_terms( array(
            'taxonomy'   => self::TAXONOMY,
            'hide_empty' => false,
            'parent'     => $city_term_id,
            'meta_query' => array(
                array(
                    'key'     => 'moga_level',
                    'value'   => 'district',
                    'compare' => '=',
                ),
            ),
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
                'id'   => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
                'lat'  => get_term_meta( $term->term_id, 'moga_latitude', true ),
                'lng'  => get_term_meta( $term->term_id, 'moga_longitude', true ),
            );
        }

        return $result;
    }


    /**
     * Get popular cities across all countries.
     * Used for the homepage destinations section.
     *
     * @since  1.0.0
     * @return array
     */
    public static function get_popular_cities() {

        $terms = get_terms( array(
            'taxonomy'   => self::TAXONOMY,
            'hide_empty' => false,
            'meta_query' => array(
                array(
                    'key'     => 'moga_level',
                    'value'   => 'city',
                    'compare' => '=',
                ),
                array(
                    'key'     => 'moga_popular',
                    'value'   => '1',
                    'compare' => '=',
                ),
            ),
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
                'count' => $term->count,
                'flag'  => get_term_meta( $term->term_id, 'moga_flag', true ),
                'lat'   => get_term_meta( $term->term_id, 'moga_latitude', true ),
                'lng'   => get_term_meta( $term->term_id, 'moga_longitude', true ),
                'link'  => get_term_link( $term ),
            );
        }

        return $result;
    }


    /**
     * Get full location hierarchy for a given term ID.
     * Returns breadcrumb-style array from district to country.
     *
     * Example: [ 'Downtown Cairo', 'Cairo', 'Egypt' ]
     *
     * @since  1.0.0
     * @param  int $term_id Location term ID.
     * @return array
     */
    public static function get_breadcrumb( $term_id ) {

        $breadcrumb = array();
        $term       = get_term( $term_id, self::TAXONOMY );

        while ( $term && ! is_wp_error( $term ) ) {
            array_unshift( $breadcrumb, array(
                'id'   => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
                'link' => get_term_link( $term ),
            ) );

            if ( $term->parent ) {
                $term = get_term( $term->parent, self::TAXONOMY );
            } else {
                break;
            }
        }

        return $breadcrumb;
    }


    /**
     * Get cities dropdown for a given country code.
     * Used in AJAX city loader in meta boxes and forms.
     *
     * @since  1.0.0
     * @param  string $country_code ISO country code (e.g. EG).
     * @return array  city term_id => city name pairs.
     */
    public static function get_cities_by_country_code( $country_code ) {

        // Find the country term first.
        $country_terms = get_terms( array(
            'taxonomy'   => self::TAXONOMY,
            'hide_empty' => false,
            'meta_query' => array(
                array(
                    'key'     => 'moga_country_code',
                    'value'   => strtoupper( $country_code ),
                    'compare' => '=',
                ),
                array(
                    'key'     => 'moga_level',
                    'value'   => 'country',
                    'compare' => '=',
                ),
            ),
        ) );

        if ( is_wp_error( $country_terms ) || empty( $country_terms ) ) {
            return array();
        }

        $country_term_id = $country_terms[0]->term_id;
        $cities          = self::get_cities( $country_term_id );

        $result = array(
            '' => __( '— Select City —', 'moga-travel-core' ),
        );

        foreach ( $cities as $city ) {
            $result[ $city['id'] ] = $city['name'];
        }

        return $result;
    }
}