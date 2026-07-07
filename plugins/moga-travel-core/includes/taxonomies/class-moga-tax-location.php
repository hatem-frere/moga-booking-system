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
 *   Level 2 — Province (e.g. Cairo Governorate)
 *   Level 3 — City     (e.g. Cairo)
 *   Level 4 — District (e.g. Zamalek)
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
            'description'        => __( 'Hierarchical location system: Country > Province > City > District.', 'moga-travel-core' ),

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

        // Location level (country, province, city, district).
        register_term_meta(
            self::TAXONOMY,
            'moga_level',
            array(
                'type'              => 'string',
                'description'       => __( 'Location level: country, province, city, or district.', 'moga-travel-core' ),
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
     * Creates a four-level seed structure for Egypt demonstrating
     * the full Country → Province → City → District hierarchy.
     * The admin can add more countries, provinces, cities and
     * districts via the Location Settings → Location Editor in
     * the Moga admin menu.
     *
     * Structure created:
     *   Egypt (country)
     *     ├── Cairo (province)
     *     │     └── Cairo (city)
     *     │           ├── Downtown Cairo (district)
     *     │           ├── Zamalek (district)
     *     │           └── Maadi (district)
     *     ├── Red Sea (province)
     *     │     └── Hurghada (city)
     *     │           └── Sahl Hasheesh (district)
     *     ├── South Sinai (province)
     *     │     └── Sharm El Sheikh (city)
     *     │           └── Naama Bay (district)
     *     ├── Luxor (province)
     *     │     └── Luxor (city)
     *     └── Aswan (province)
     *           └── Aswan (city)
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

        if ( ! $egypt_id ) {
            update_option( 'moga_locations_created', true );
            return;
        }

        // --------------------------------------------------------
        // Level 2 — Provinces + their Cities + Districts
        // Each province is a child of Egypt (country term).
        // Each city is a child of its province term.
        // Each district is a child of its city term.
        // --------------------------------------------------------
        $provinces = array(
            array(
                'name'    => __( 'Cairo Governorate', 'moga-travel-core' ),
                'slug'    => 'cairo-governorate',
                'order'   => 1,
                'popular' => 1,
                'cities'  => array(
                    array(
                        'name'      => __( 'Cairo', 'moga-travel-core' ),
                        'slug'      => 'cairo',
                        'lat'       => '30.0444',
                        'lng'       => '31.2357',
                        'order'     => 1,
                        'popular'   => 1,
                        'districts' => array(
                            array( 'name' => __( 'Downtown Cairo', 'moga-travel-core' ), 'slug' => 'downtown-cairo',  'lat' => '30.0478', 'lng' => '31.2336' ),
                            array( 'name' => __( 'Zamalek',        'moga-travel-core' ), 'slug' => 'zamalek',        'lat' => '30.0626', 'lng' => '31.2197' ),
                            array( 'name' => __( 'Maadi',          'moga-travel-core' ), 'slug' => 'maadi',          'lat' => '29.9602', 'lng' => '31.2569' ),
                            array( 'name' => __( 'Nasr City',      'moga-travel-core' ), 'slug' => 'nasr-city',      'lat' => '30.0626', 'lng' => '31.3342' ),
                            array( 'name' => __( 'Heliopolis',     'moga-travel-core' ), 'slug' => 'heliopolis',     'lat' => '30.0911', 'lng' => '31.3424' ),
                            array( 'name' => __( 'New Cairo',      'moga-travel-core' ), 'slug' => 'new-cairo',      'lat' => '30.0270', 'lng' => '31.4961' ),
                        ),
                    ),
                ),
            ),
            array(
                'name'    => __( 'Alexandria Governorate', 'moga-travel-core' ),
                'slug'    => 'alexandria-governorate',
                'order'   => 2,
                'popular' => 1,
                'cities'  => array(
                    array(
                        'name'      => __( 'Alexandria', 'moga-travel-core' ),
                        'slug'      => 'alexandria',
                        'lat'       => '31.2001',
                        'lng'       => '29.9187',
                        'order'     => 1,
                        'popular'   => 1,
                        'districts' => array(
                            array( 'name' => __( 'Corniche Alexandria', 'moga-travel-core' ), 'slug' => 'corniche-alexandria', 'lat' => '31.2156', 'lng' => '29.9553' ),
                            array( 'name' => __( 'Smouha',             'moga-travel-core' ), 'slug' => 'smouha',             'lat' => '31.1980', 'lng' => '29.9592' ),
                            array( 'name' => __( 'Montaza',            'moga-travel-core' ), 'slug' => 'montaza',            'lat' => '31.2849', 'lng' => '30.0147' ),
                        ),
                    ),
                ),
            ),
            array(
                'name'    => __( 'Red Sea Governorate', 'moga-travel-core' ),
                'slug'    => 'red-sea-governorate',
                'order'   => 3,
                'popular' => 1,
                'cities'  => array(
                    array(
                        'name'      => __( 'Hurghada', 'moga-travel-core' ),
                        'slug'      => 'hurghada',
                        'lat'       => '27.2579',
                        'lng'       => '33.8116',
                        'order'     => 1,
                        'popular'   => 1,
                        'districts' => array(
                            array( 'name' => __( 'Sahl Hasheesh', 'moga-travel-core' ), 'slug' => 'sahl-hasheesh', 'lat' => '27.1167', 'lng' => '33.8833' ),
                            array( 'name' => __( 'El Gouna',      'moga-travel-core' ), 'slug' => 'el-gouna',      'lat' => '27.3950', 'lng' => '33.6783' ),
                            array( 'name' => __( 'Makadi Bay',    'moga-travel-core' ), 'slug' => 'makadi-bay',    'lat' => '27.0333', 'lng' => '33.9167' ),
                        ),
                    ),
                ),
            ),
            array(
                'name'    => __( 'South Sinai Governorate', 'moga-travel-core' ),
                'slug'    => 'south-sinai-governorate',
                'order'   => 4,
                'popular' => 1,
                'cities'  => array(
                    array(
                        'name'      => __( 'Sharm El Sheikh', 'moga-travel-core' ),
                        'slug'      => 'sharm-el-sheikh',
                        'lat'       => '27.9158',
                        'lng'       => '34.3300',
                        'order'     => 1,
                        'popular'   => 1,
                        'districts' => array(
                            array( 'name' => __( 'Naama Bay',  'moga-travel-core' ), 'slug' => 'naama-bay',  'lat' => '27.9100', 'lng' => '34.3300' ),
                            array( 'name' => __( 'Sharks Bay', 'moga-travel-core' ), 'slug' => 'sharks-bay', 'lat' => '27.9667', 'lng' => '34.3667' ),
                        ),
                    ),
                    array(
                        'name'      => __( 'Dahab', 'moga-travel-core' ),
                        'slug'      => 'dahab',
                        'lat'       => '28.4833',
                        'lng'       => '34.5167',
                        'order'     => 2,
                        'popular'   => 0,
                        'districts' => array(),
                    ),
                ),
            ),
            array(
                'name'    => __( 'Luxor Governorate', 'moga-travel-core' ),
                'slug'    => 'luxor-governorate',
                'order'   => 5,
                'popular' => 1,
                'cities'  => array(
                    array(
                        'name'      => __( 'Luxor', 'moga-travel-core' ),
                        'slug'      => 'luxor',
                        'lat'       => '25.6872',
                        'lng'       => '32.6396',
                        'order'     => 1,
                        'popular'   => 1,
                        'districts' => array(
                            array( 'name' => __( 'Luxor East Bank', 'moga-travel-core' ), 'slug' => 'luxor-east-bank', 'lat' => '25.6872', 'lng' => '32.6396' ),
                            array( 'name' => __( 'Luxor West Bank', 'moga-travel-core' ), 'slug' => 'luxor-west-bank', 'lat' => '25.7306', 'lng' => '32.6000' ),
                        ),
                    ),
                ),
            ),
            array(
                'name'    => __( 'Aswan Governorate', 'moga-travel-core' ),
                'slug'    => 'aswan-governorate',
                'order'   => 6,
                'popular' => 1,
                'cities'  => array(
                    array(
                        'name'      => __( 'Aswan', 'moga-travel-core' ),
                        'slug'      => 'aswan',
                        'lat'       => '24.0889',
                        'lng'       => '32.8998',
                        'order'     => 1,
                        'popular'   => 1,
                        'districts' => array(
                            array( 'name' => __( 'Aswan Corniche', 'moga-travel-core' ), 'slug' => 'aswan-corniche', 'lat' => '24.0950', 'lng' => '32.9000' ),
                        ),
                    ),
                ),
            ),
        );

        foreach ( $provinces as $province_data ) {

            // ---- Insert province term ----
            $province_result = wp_insert_term(
                $province_data['name'],
                self::TAXONOMY,
                array(
                    'slug'   => $province_data['slug'],
                    'parent' => $egypt_id,
                )
            );

            if ( is_wp_error( $province_result ) ) {
                $existing_p  = get_term_by( 'slug', $province_data['slug'], self::TAXONOMY );
                $province_id = $existing_p ? $existing_p->term_id : 0;
            } else {
                $province_id = $province_result['term_id'];
            }

            if ( $province_id ) {
                update_term_meta( $province_id, 'moga_level',        'province' );
                update_term_meta( $province_id, 'moga_country_code', 'EG' );
                update_term_meta( $province_id, 'moga_flag',         '🇪🇬' );
                update_term_meta( $province_id, 'moga_order',        $province_data['order'] );
                update_term_meta( $province_id, 'moga_popular',      $province_data['popular'] );
            }

            if ( ! $province_id ) {
                continue;
            }

            // ---- Insert cities under this province ----
            foreach ( $province_data['cities'] as $city_data ) {

                $city_result = wp_insert_term(
                    $city_data['name'],
                    self::TAXONOMY,
                    array(
                        'slug'   => $city_data['slug'],
                        'parent' => $province_id,
                    )
                );

                if ( is_wp_error( $city_result ) ) {
                    $existing_c = get_term_by( 'slug', $city_data['slug'], self::TAXONOMY );
                    $city_id    = $existing_c ? $existing_c->term_id : 0;
                } else {
                    $city_id = $city_result['term_id'];
                }

                if ( $city_id ) {
                    update_term_meta( $city_id, 'moga_level',        'city' );
                    update_term_meta( $city_id, 'moga_country_code', 'EG' );
                    update_term_meta( $city_id, 'moga_flag',         '🇪🇬' );
                    update_term_meta( $city_id, 'moga_latitude',     $city_data['lat'] );
                    update_term_meta( $city_id, 'moga_longitude',    $city_data['lng'] );
                    update_term_meta( $city_id, 'moga_order',        $city_data['order'] );
                    update_term_meta( $city_id, 'moga_popular',      $city_data['popular'] );
                }

                if ( ! $city_id ) {
                    continue;
                }

                // ---- Insert districts under this city ----
                foreach ( $city_data['districts'] as $district_data ) {

                    $district_result = wp_insert_term(
                        $district_data['name'],
                        self::TAXONOMY,
                        array(
                            'slug'   => $district_data['slug'],
                            'parent' => $city_id,
                        )
                    );

                    if ( ! is_wp_error( $district_result ) ) {
                        $district_id = $district_result['term_id'];
                        update_term_meta( $district_id, 'moga_level',        'district' );
                        update_term_meta( $district_id, 'moga_country_code', 'EG' );
                        update_term_meta( $district_id, 'moga_latitude',     $district_data['lat'] );
                        update_term_meta( $district_id, 'moga_longitude',    $district_data['lng'] );
                        update_term_meta( $district_id, 'moga_order',        1 );
                    }
                }
            }
        }

        // Mark as created.
        update_option( 'moga_locations_created', true );
    }


    // ============================================================
    // SYNC FROM SELECTION — four-level hierarchy
    // ============================================================

    /**
     * Auto-create or reuse moga_location taxonomy terms when a
     * property or tour is saved with location data.
     *
     * Updated for the four-level hierarchy:
     *   Country → Province → City → District
     *
     * Term creation logic (all four levels):
     *   1. Country:  find by moga_country_code meta OR create new term.
     *   2. Province: find by name under country parent OR create new term.
     *   3. City:     find by name under province parent OR create new term.
     *   4. District: find by name under city parent OR create new term.
     *                (Only if district name is provided.)
     *
     * All matching taxonomy term IDs are assigned to the post via
     * wp_set_object_terms() so the post is queryable at all four
     * levels simultaneously.
     *
     * Usage — call from save_post in class-moga-admin-metaboxes.php:
     *
     *   Moga_Tax_Location::sync_from_selection(
     *       $post_id,
     *       array(
     *           'country_code'  => 'EG',
     *           'country_name'  => 'Egypt',
     *           'province_name' => 'Cairo Governorate',
     *           'city_name'     => 'Cairo',
     *           'district'      => 'Zamalek',
     *           'lat'           => '30.0444',
     *           'lng'           => '31.2357',
     *       )
     *   );
     *
     * @since  1.0.0
     *
     * @param  int   $post_id Post ID to assign taxonomy terms to.
     * @param  array $data {
     *     Location data array.
     *
     *     @type string $country_code  ISO 3166-1 alpha-2 code (e.g. 'EG'). Required.
     *     @type string $country_name  Country display name (e.g. 'Egypt'). Required.
     *     @type string $province_name Province/State/Governorate name. Required.
     *     @type string $city_name     City display name (e.g. 'Cairo'). Required.
     *     @type string $district      District/area name. Optional.
     *     @type string $lat           City GPS latitude. Optional.
     *     @type string $lng           City GPS longitude. Optional.
     *     @type string $district_lat  District GPS latitude. Optional.
     *     @type string $district_lng  District GPS longitude. Optional.
     * }
     * @param  bool  $append When true, uses wp_add_object_terms (additive).
     *                       When false (default), uses wp_set_object_terms (replaces).
     *                       Pass true for tour destination (second sync call) so both
     *                       departure and destination terms coexist on the same post.
     *
     * @return array {
     *     Term IDs that were created or reused.
     *
     *     @type int $country_term_id
     *     @type int $province_term_id
     *     @type int $city_term_id
     *     @type int $district_term_id  0 if no district provided.
     * }
     */
    public static function sync_from_selection( $post_id, $data, $append = false ) {

        $post_id = absint( $post_id );

        if ( ! $post_id ) {
            return array(
                'country_term_id'  => 0,
                'province_term_id' => 0,
                'city_term_id'     => 0,
                'district_term_id' => 0,
            );
        }

        // Sanitize all inputs.
        $country_code  = strtoupper( sanitize_text_field( $data['country_code']  ?? '' ) );
        $country_name  = sanitize_text_field( $data['country_name']  ?? '' );
        $province_name = sanitize_text_field( $data['province_name'] ?? '' );
        $city_name     = sanitize_text_field( $data['city_name']     ?? '' );
        $district      = sanitize_text_field( $data['district']      ?? '' );
        $lat           = sanitize_text_field( $data['lat']           ?? '' );
        $lng           = sanitize_text_field( $data['lng']           ?? '' );
        $district_lat  = sanitize_text_field( $data['district_lat']  ?? '' );
        $district_lng  = sanitize_text_field( $data['district_lng']  ?? '' );

        // Country, province, and city are all required.
        if ( empty( $country_code ) || empty( $province_name ) || empty( $city_name ) ) {
            return array(
                'country_term_id'  => 0,
                'province_term_id' => 0,
                'city_term_id'     => 0,
                'district_term_id' => 0,
            );
        }

        $term_ids = array();

        // --------------------------------------------------------
        // Step 1: Find or create Country term
        // --------------------------------------------------------
        $country_term_id = self::find_or_create_country(
            $country_name,
            $country_code
        );

        $term_ids[] = $country_term_id;

        // --------------------------------------------------------
        // Step 2: Find or create Province term
        // --------------------------------------------------------
        $province_term_id = self::find_or_create_province(
            $province_name,
            $country_term_id,
            $country_code
        );

        $term_ids[] = $province_term_id;

        // --------------------------------------------------------
        // Step 3: Find or create City term
        // --------------------------------------------------------
        $city_term_id = self::find_or_create_city(
            $city_name,
            $province_term_id,
            $country_code,
            $lat,
            $lng
        );

        $term_ids[] = $city_term_id;

        // --------------------------------------------------------
        // Step 4: Find or create District term (if provided)
        // --------------------------------------------------------
        $district_term_id = 0;

        if ( ! empty( $district ) && $city_term_id ) {
            $district_term_id = self::find_or_create_district(
                $district,
                $city_term_id,
                $country_code,
                $district_lat,
                $district_lng
            );

            if ( $district_term_id ) {
                $term_ids[] = $district_term_id;
            }
        }

        // --------------------------------------------------------
        // Step 5: Assign all term IDs to the post
        // --------------------------------------------------------
        if ( ! empty( $term_ids ) ) {
            $clean_ids = array_filter( array_map( 'absint', $term_ids ) );
            if ( $append ) {
                wp_add_object_terms(
                    $post_id,
                    $clean_ids,
                    self::TAXONOMY
                );
            } else {
                wp_set_object_terms(
                    $post_id,
                    $clean_ids,
                    self::TAXONOMY
                );
            }
        }

        return array(
            'country_term_id'  => $country_term_id,
            'province_term_id' => $province_term_id,
            'city_term_id'     => $city_term_id,
            'district_term_id' => $district_term_id,
        );
    }


    /**
     * Find an existing country term by country code,
     * or create a new one if it does not exist.
     *
     * @since  1.0.0
     * @param  string $country_name Display name (e.g. 'United States').
     * @param  string $country_code ISO code (e.g. 'US').
     * @return int Term ID, or 0 on failure.
     */
    private static function find_or_create_country( $country_name, $country_code ) {

        // Search by moga_country_code meta + level = country.
        $existing = get_terms( array(
            'taxonomy'   => self::TAXONOMY,
            'hide_empty' => false,
            'parent'     => 0,
            'meta_query' => array(
                array(
                    'key'     => 'moga_country_code',
                    'value'   => $country_code,
                    'compare' => '=',
                ),
                array(
                    'key'     => 'moga_level',
                    'value'   => 'country',
                    'compare' => '=',
                ),
            ),
        ) );

        if ( ! is_wp_error( $existing ) && ! empty( $existing ) ) {
            return absint( $existing[0]->term_id );
        }

        // Not found — create new country term.
        if ( empty( $country_name ) ) {
            // Fall back to country code as display name if name not provided.
            $country_name = $country_code;
        }

        $slug   = sanitize_title( $country_name );
        $result = wp_insert_term(
            $country_name,
            self::TAXONOMY,
            array(
                'slug'   => $slug,
                'parent' => 0,
            )
        );

        if ( is_wp_error( $result ) ) {
            // Term with this slug may already exist under a different meta config.
            $term = get_term_by( 'slug', $slug, self::TAXONOMY );
            if ( $term ) {
                $term_id = absint( $term->term_id );
            } else {
                return 0;
            }
        } else {
            $term_id = absint( $result['term_id'] );
        }

        // Set country meta.
        update_term_meta( $term_id, 'moga_level',        'country' );
        update_term_meta( $term_id, 'moga_country_code', $country_code );
        update_term_meta( $term_id, 'moga_order',        99 );
        update_term_meta( $term_id, 'moga_popular',      0 );

        return $term_id;
    }


    /**
     * Find an existing province term under a country parent,
     * or create a new one if it does not exist.
     *
     * @since  1.0.0
     * @param  string $province_name Display name (e.g. 'Cairo Governorate').
     * @param  int    $country_term_id Parent country term ID.
     * @param  string $country_code    ISO country code.
     * @return int Term ID, or 0 on failure.
     */
    private static function find_or_create_province( $province_name, $country_term_id, $country_code ) {

        if ( ! $country_term_id || empty( $province_name ) ) {
            return 0;
        }

        // Search by name under the country parent.
        $existing = get_terms( array(
            'taxonomy'   => self::TAXONOMY,
            'hide_empty' => false,
            'parent'     => $country_term_id,
            'name'       => $province_name,
            'meta_query' => array(
                array(
                    'key'     => 'moga_level',
                    'value'   => 'province',
                    'compare' => '=',
                ),
            ),
        ) );

        if ( ! is_wp_error( $existing ) && ! empty( $existing ) ) {
            return absint( $existing[0]->term_id );
        }

        // Not found — create new province term.
        $slug   = sanitize_title( $province_name . '-' . strtolower( $country_code ) );
        $result = wp_insert_term(
            $province_name,
            self::TAXONOMY,
            array(
                'slug'   => $slug,
                'parent' => $country_term_id,
            )
        );

        if ( is_wp_error( $result ) ) {
            $slug   = sanitize_title( $province_name );
            $result = wp_insert_term(
                $province_name,
                self::TAXONOMY,
                array(
                    'slug'   => $slug,
                    'parent' => $country_term_id,
                )
            );
        }

        if ( is_wp_error( $result ) ) {
            $term = get_term_by( 'slug', $slug, self::TAXONOMY );
            if ( $term ) {
                $term_id = absint( $term->term_id );
            } else {
                return 0;
            }
        } else {
            $term_id = absint( $result['term_id'] );
        }

        // Set province meta.
        update_term_meta( $term_id, 'moga_level',        'province' );
        update_term_meta( $term_id, 'moga_country_code', $country_code );
        update_term_meta( $term_id, 'moga_order',        99 );
        update_term_meta( $term_id, 'moga_popular',      0 );

        return $term_id;
    }

    /**
     * Find an existing city term under a province parent,
     * or create a new one if it does not exist.
     *
     * @since  1.0.0
     * @param  string $city_name        City display name.
     * @param  int    $province_term_id Parent province term ID.
     * @param  string $country_code     ISO country code.
     * @param  string $lat              GPS latitude.
     * @param  string $lng              GPS longitude.
     * @return int Term ID, or 0 on failure.
     */
    private static function find_or_create_city(
        $city_name,
        $province_term_id,
        $country_code,
        $lat = '',
        $lng = ''
    ) {

        if ( ! $province_term_id ) {
            return 0;
        }

        // Search by name under the province parent.
        $existing = get_terms( array(
            'taxonomy'   => self::TAXONOMY,
            'hide_empty' => false,
            'parent'     => $province_term_id,
            'name'       => $city_name,
            'meta_query' => array(
                array(
                    'key'     => 'moga_level',
                    'value'   => 'city',
                    'compare' => '=',
                ),
            ),
        ) );

        if ( ! is_wp_error( $existing ) && ! empty( $existing ) ) {
            return absint( $existing[0]->term_id );
        }

        // Not found — create new city term.
        $slug   = sanitize_title( $city_name . '-' . strtolower( $country_code ) );
        $result = wp_insert_term(
            $city_name,
            self::TAXONOMY,
            array(
                'slug'   => $slug,
                'parent' => $province_term_id,
            )
        );

        if ( is_wp_error( $result ) ) {
            $slug   = sanitize_title( $city_name );
            $result = wp_insert_term(
                $city_name,
                self::TAXONOMY,
                array(
                    'slug'   => $slug,
                    'parent' => $province_term_id,
                )
            );
        }

        if ( is_wp_error( $result ) ) {
            $term = get_term_by( 'slug', $slug, self::TAXONOMY );
            if ( $term ) {
                $term_id = absint( $term->term_id );
            } else {
                return 0;
            }
        } else {
            $term_id = absint( $result['term_id'] );
        }

        // Set city meta.
        update_term_meta( $term_id, 'moga_level',        'city' );
        update_term_meta( $term_id, 'moga_country_code', $country_code );
        update_term_meta( $term_id, 'moga_order',        99 );
        update_term_meta( $term_id, 'moga_popular',      0 );

        if ( $lat ) {
            update_term_meta( $term_id, 'moga_latitude', $lat );
        }
        if ( $lng ) {
            update_term_meta( $term_id, 'moga_longitude', $lng );
        }

        return $term_id;
    }


    /**
     * Find an existing district term under a city parent,
     * or create a new one if it does not exist.
     *
     * @since  1.0.0
     * @param  string $district_name District display name.
     * @param  int    $city_term_id  Parent city term ID.
     * @param  string $country_code  ISO country code.
     * @param  string $lat           GPS latitude.
     * @param  string $lng           GPS longitude.
     * @return int Term ID, or 0 on failure.
     */
    private static function find_or_create_district(
        $district_name,
        $city_term_id,
        $country_code,
        $lat = '',
        $lng = ''
    ) {

        if ( ! $city_term_id ) {
            return 0;
        }

        // Search by name under the city parent.
        $existing = get_terms( array(
            'taxonomy'   => self::TAXONOMY,
            'hide_empty' => false,
            'parent'     => $city_term_id,
            'name'       => $district_name,
        ) );

        if ( ! is_wp_error( $existing ) && ! empty( $existing ) ) {
            return absint( $existing[0]->term_id );
        }

        // Not found — create new district term.
        $slug   = sanitize_title( $district_name );
        $result = wp_insert_term(
            $district_name,
            self::TAXONOMY,
            array(
                'slug'   => $slug,
                'parent' => $city_term_id,
            )
        );

        if ( is_wp_error( $result ) ) {
            // Append city ID to slug to avoid conflicts.
            $slug   = sanitize_title( $district_name ) . '-' . $city_term_id;
            $result = wp_insert_term(
                $district_name,
                self::TAXONOMY,
                array(
                    'slug'   => $slug,
                    'parent' => $city_term_id,
                )
            );
        }

        if ( is_wp_error( $result ) ) {
            return 0;
        }

        $term_id = absint( $result['term_id'] );

        // Set district meta.
        update_term_meta( $term_id, 'moga_level',        'district' );
        update_term_meta( $term_id, 'moga_country_code', $country_code );
        update_term_meta( $term_id, 'moga_order',        99 );

        if ( $lat ) {
            update_term_meta( $term_id, 'moga_latitude', $lat );
        }
        if ( $lng ) {
            update_term_meta( $term_id, 'moga_longitude', $lng );
        }

        return $term_id;
    }


    // ============================================================
    // GETTERS (original — unchanged)
    // ============================================================

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
     * Get provinces for a specific country term ID.
     *
     * @since  1.0.0
     * @param  int $country_term_id Country term ID.
     * @return array
     */
    public static function get_provinces( $country_term_id ) {

        $terms = get_terms( array(
            'taxonomy'   => self::TAXONOMY,
            'hide_empty' => false,
            'parent'     => $country_term_id,
            'meta_query' => array(
                array(
                    'key'     => 'moga_level',
                    'value'   => 'province',
                    'compare' => '=',
                ),
            ),
            'orderby'  => 'name',
            'order'    => 'ASC',
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
                'link' => get_term_link( $term ),
            );
        }

        return $result;
    }


    /**
     * Get cities for a specific province term ID.
     *
     * Updated for four-level hierarchy — cities are now children
     * of provinces, not countries.
     *
     * @since  1.0.0
     * @param  int $province_term_id Province term ID.
     * @return array
     */
    public static function get_cities( $province_term_id ) {

        $terms = get_terms( array(
            'taxonomy'   => self::TAXONOMY,
            'hide_empty' => false,
            'parent'     => $province_term_id,
            'meta_query' => array(
                array(
                    'key'     => 'moga_level',
                    'value'   => 'city',
                    'compare' => '=',
                ),
            ),
            'orderby'  => 'name',
            'order'    => 'ASC',
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
     * Returns all cities across all provinces of that country.
     * Used as a convenience helper — for the primary cascade use
     * get_provinces() then get_cities() per province.
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

        // Get all provinces for this country.
        $provinces = self::get_provinces( $country_term_id );

        if ( empty( $provinces ) ) {
            return array();
        }

        $result = array(
            '' => __( '— Select City —', 'moga-travel-core' ),
        );

        // Collect cities across all provinces.
        foreach ( $provinces as $province ) {
            $cities = self::get_cities( $province['id'] );
            foreach ( $cities as $city ) {
                $result[ $city['id'] ] = $city['name'];
            }
        }

        asort( $result );

        return $result;
    }
}