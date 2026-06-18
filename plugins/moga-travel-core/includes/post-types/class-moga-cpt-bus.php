<?php
/**
 * Bus Custom Post Type
 *
 * Registers the moga_bus custom post type.
 * Each bus has its own seat layout, capacity,
 * and is assigned to one or more tours.
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
 * Class Moga_CPT_Bus
 */
class Moga_CPT_Bus {

    /**
     * Post type key.
     *
     * @since 1.0.0
     * @var   string
     */
    const POST_TYPE = 'moga_bus';

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
            'name'                  => _x( 'Buses', 'Post type general name', 'moga-travel-core' ),
            'singular_name'         => _x( 'Bus', 'Post type singular name', 'moga-travel-core' ),
            'menu_name'             => _x( 'Buses', 'Admin Menu text', 'moga-travel-core' ),
            'name_admin_bar'        => _x( 'Bus', 'Add New on Toolbar', 'moga-travel-core' ),
            'add_new'               => __( 'Add New', 'moga-travel-core' ),
            'add_new_item'          => __( 'Add New Bus', 'moga-travel-core' ),
            'new_item'              => __( 'New Bus', 'moga-travel-core' ),
            'edit_item'             => __( 'Edit Bus', 'moga-travel-core' ),
            'view_item'             => __( 'View Bus', 'moga-travel-core' ),
            'all_items'             => __( 'All Buses', 'moga-travel-core' ),
            'search_items'          => __( 'Search Buses', 'moga-travel-core' ),
            'not_found'             => __( 'No buses found.', 'moga-travel-core' ),
            'not_found_in_trash'    => __( 'No buses found in Trash.', 'moga-travel-core' ),
            'featured_image'        => __( 'Bus Photo', 'moga-travel-core' ),
            'set_featured_image'    => __( 'Set bus photo', 'moga-travel-core' ),
            'remove_featured_image' => __( 'Remove bus photo', 'moga-travel-core' ),
            'use_featured_image'    => __( 'Use as bus photo', 'moga-travel-core' ),
            'archives'              => __( 'Bus Archives', 'moga-travel-core' ),
            'filter_items_list'     => __( 'Filter buses list', 'moga-travel-core' ),
            'items_list_navigation' => __( 'Buses list navigation', 'moga-travel-core' ),
            'items_list'            => __( 'Buses list', 'moga-travel-core' ),
        );

        $args = array(
            'labels'              => $labels,
            'description'         => __( 'Bus fleet with seat layouts for tour reservations.', 'moga-travel-core' ),

            // Visibility.
            // Buses are admin-only — not shown on frontend directly.
            'public'              => false,
            'publicly_queryable'  => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_nav_menus'   => false,
            'show_in_admin_bar'   => true,
            'show_in_rest'        => true,

            // Admin.
            'menu_position'       => 7,
            'menu_icon'           => 'dashicons-car',

            // Capabilities.
            'capability_type'     => 'post',
            'map_meta_cap'        => true,

            // Supports.
            'supports'            => array(
                'title',         // Bus name / plate number.
                'thumbnail',     // Bus photo.
                'custom-fields', // Meta fields.
                'revisions',
            ),

            // No taxonomies needed for buses.
            'taxonomies'          => array(),

            // No frontend URLs needed.
            'rewrite'             => false,
            'query_var'           => false,
            'has_archive'         => false,
            'hierarchical'        => false,
        );

        register_post_type( self::POST_TYPE, $args );

        // Register meta fields.
        self::register_meta_fields();
    }


    /**
     * Register all bus meta fields.
     *
     * @since  1.0.0
     * @return void
     */
    private static function register_meta_fields() {

        $meta_fields = array(

            // ---- Bus Identity ----
            '_moga_bus_plate'         => array(
                'type'        => 'string',
                'description' => __( 'Bus plate number.', 'moga-travel-core' ),
                'default'     => '',
            ),
            '_moga_bus_model'         => array(
                'type'        => 'string',
                'description' => __( 'Bus make and model (e.g. Mercedes Sprinter).', 'moga-travel-core' ),
                'default'     => '',
            ),
            '_moga_bus_year'          => array(
                'type'        => 'integer',
                'description' => __( 'Bus manufacturing year.', 'moga-travel-core' ),
                'default'     => 0,
            ),
            '_moga_bus_color'         => array(
                'type'        => 'string',
                'description' => __( 'Bus color.', 'moga-travel-core' ),
                'default'     => '',
            ),
            '_moga_bus_type'          => array(
                'type'        => 'string',
                'description' => __( 'Bus type (minibus, standard, luxury, double_decker).', 'moga-travel-core' ),
                'default'     => 'standard',
            ),

            // ---- Seat Layout ----
            '_moga_total_seats'       => array(
                'type'        => 'integer',
                'description' => __( 'Total number of seats on the bus.', 'moga-travel-core' ),
                'default'     => 40,
            ),
            '_moga_seat_rows'         => array(
                'type'        => 'integer',
                'description' => __( 'Number of seat rows.', 'moga-travel-core' ),
                'default'     => 10,
            ),
            '_moga_seat_columns'      => array(
                'type'        => 'integer',
                'description' => __( 'Number of seat columns (e.g. 4 for 2+2 layout).', 'moga-travel-core' ),
                'default'     => 4,
            ),
            '_moga_seat_layout'       => array(
                'type'        => 'string',
                'description' => __( 'Seat layout type (2+2, 2+3, 1+2, etc.).', 'moga-travel-core' ),
                'default'     => '2+2',
            ),
            '_moga_vip_seats'         => array(
                'type'        => 'string',
                'description' => __( 'JSON array of VIP seat numbers.', 'moga-travel-core' ),
                'default'     => '',
            ),
            '_moga_disabled_seats'    => array(
                'type'        => 'string',
                'description' => __( 'JSON array of disabled/unavailable seat numbers.', 'moga-travel-core' ),
                'default'     => '',
            ),
            '_moga_driver_seat'       => array(
                'type'        => 'string',
                'description' => __( 'Driver seat position (front-left, front-right).', 'moga-travel-core' ),
                'default'     => 'front-left',
            ),
            '_moga_has_aisle'         => array(
                'type'        => 'integer',
                'description' => __( 'Has center aisle (1 = yes).', 'moga-travel-core' ),
                'default'     => 1,
            ),

            // ---- Amenities ----
            '_moga_has_ac'            => array(
                'type'        => 'integer',
                'description' => __( 'Has air conditioning (1 = yes).', 'moga-travel-core' ),
                'default'     => 1,
            ),
            '_moga_has_wifi'          => array(
                'type'        => 'integer',
                'description' => __( 'Has onboard WiFi (1 = yes).', 'moga-travel-core' ),
                'default'     => 0,
            ),
            '_moga_has_tv'            => array(
                'type'        => 'integer',
                'description' => __( 'Has onboard TV screens (1 = yes).', 'moga-travel-core' ),
                'default'     => 0,
            ),
            '_moga_has_usb'           => array(
                'type'        => 'integer',
                'description' => __( 'Has USB charging ports (1 = yes).', 'moga-travel-core' ),
                'default'     => 0,
            ),
            '_moga_has_toilet'        => array(
                'type'        => 'integer',
                'description' => __( 'Has onboard toilet (1 = yes).', 'moga-travel-core' ),
                'default'     => 0,
            ),
            '_moga_has_reclining'     => array(
                'type'        => 'integer',
                'description' => __( 'Has reclining seats (1 = yes).', 'moga-travel-core' ),
                'default'     => 0,
            ),
            '_moga_has_luggage'       => array(
                'type'        => 'integer',
                'description' => __( 'Has luggage compartment (1 = yes).', 'moga-travel-core' ),
                'default'     => 1,
            ),
            '_moga_has_wheelchair'    => array(
                'type'        => 'integer',
                'description' => __( 'Wheelchair accessible (1 = yes).', 'moga-travel-core' ),
                'default'     => 0,
            ),

            // ---- Driver Info ----
            '_moga_driver_name'       => array(
                'type'        => 'string',
                'description' => __( 'Driver full name.', 'moga-travel-core' ),
                'default'     => '',
            ),
            '_moga_driver_phone'      => array(
                'type'        => 'string',
                'description' => __( 'Driver phone number with country code.', 'moga-travel-core' ),
                'default'     => '',
            ),
            '_moga_driver_license'    => array(
                'type'        => 'string',
                'description' => __( 'Driver license number.', 'moga-travel-core' ),
                'default'     => '',
            ),

            // ---- Status ----
            '_moga_active'            => array(
                'type'        => 'integer',
                'description' => __( 'Bus is active and available (1 = yes).', 'moga-travel-core' ),
                'default'     => 1,
            ),
            '_moga_under_maintenance' => array(
                'type'        => 'integer',
                'description' => __( 'Bus is under maintenance (1 = yes).', 'moga-travel-core' ),
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
     * Get bus type options.
     *
     * @since  1.0.0
     * @return array
     */
    public static function get_bus_types() {
        return array(
            'minibus'       => array(
                'label'    => __( 'Minibus', 'moga-travel-core' ),
                'capacity' => __( '8-20 seats', 'moga-travel-core' ),
            ),
            'standard'      => array(
                'label'    => __( 'Standard Bus', 'moga-travel-core' ),
                'capacity' => __( '21-45 seats', 'moga-travel-core' ),
            ),
            'luxury'        => array(
                'label'    => __( 'Luxury Coach', 'moga-travel-core' ),
                'capacity' => __( '21-45 seats — premium class', 'moga-travel-core' ),
            ),
            'double_decker' => array(
                'label'    => __( 'Double Decker', 'moga-travel-core' ),
                'capacity' => __( '60-80 seats', 'moga-travel-core' ),
            ),
            'vip'           => array(
                'label'    => __( 'VIP Van', 'moga-travel-core' ),
                'capacity' => __( '4-8 seats — VIP class', 'moga-travel-core' ),
            ),
        );
    }


    /**
     * Get seat layout options.
     *
     * @since  1.0.0
     * @return array
     */
    public static function get_seat_layouts() {
        return array(
            '2+2' => array(
                'label'   => __( '2+2 (Standard)', 'moga-travel-core' ),
                'columns' => 4,
                'desc'    => __( '2 seats on each side of the aisle. Most common layout.', 'moga-travel-core' ),
            ),
            '2+3' => array(
                'label'   => __( '2+3 (High Capacity)', 'moga-travel-core' ),
                'columns' => 5,
                'desc'    => __( '2 seats on left, 3 seats on right. Maximum capacity.', 'moga-travel-core' ),
            ),
            '1+2' => array(
                'label'   => __( '1+2 (VIP)', 'moga-travel-core' ),
                'columns' => 3,
                'desc'    => __( '1 seat on left, 2 seats on right. VIP / business class.', 'moga-travel-core' ),
            ),
            '1+1' => array(
                'label'   => __( '1+1 (Luxury)', 'moga-travel-core' ),
                'columns' => 2,
                'desc'    => __( '1 seat on each side. Full luxury class.', 'moga-travel-core' ),
            ),
        );
    }


    /**
     * Get driver seat position options.
     *
     * @since  1.0.0
     * @return array
     */
    public static function get_driver_positions() {
        return array(
            'front-left'  => __( 'Front Left (Most countries)', 'moga-travel-core' ),
            'front-right' => __( 'Front Right (UK, Egypt, etc.)', 'moga-travel-core' ),
        );
    }


    /**
     * Generate seat numbers for a bus based on its layout.
     * Returns an array of seat numbers in order.
     *
     * Example for 2+2 layout with 10 rows:
     * Row 1: 1A, 1B, 1C, 1D
     * Row 2: 2A, 2B, 2C, 2D
     * etc.
     *
     * @since  1.0.0
     * @param  int    $rows    Number of rows.
     * @param  string $layout  Seat layout (2+2, 2+3, 1+2, 1+1).
     * @return array           Array of seat numbers.
     */
    public static function generate_seat_numbers( $rows, $layout = '2+2' ) {

        $layouts = self::get_seat_layouts();
        $columns = isset( $layouts[ $layout ]['columns'] )
            ? $layouts[ $layout ]['columns']
            : 4;

        // Column letters based on number of columns.
        $column_letters = array( 'A', 'B', 'C', 'D', 'E' );
        $letters        = array_slice( $column_letters, 0, $columns );

        $seats = array();

        for ( $row = 1; $row <= $rows; $row++ ) {
            foreach ( $letters as $letter ) {
                $seats[] = $row . $letter;
            }
        }

        return $seats;
    }


    /**
     * Get all buses available for assignment to tours.
     * Returns an array of bus IDs and names.
     *
     * @since  1.0.0
     * @return array
     */
    public static function get_available_buses() {

        $buses = get_posts( array(
            'post_type'      => self::POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => array(
                array(
                    'key'     => '_moga_active',
                    'value'   => '1',
                    'compare' => '=',
                ),
                array(
                    'key'     => '_moga_under_maintenance',
                    'value'   => '0',
                    'compare' => '=',
                ),
            ),
            'orderby'        => 'title',
            'order'          => 'ASC',
        ) );

        $result = array();

        foreach ( $buses as $bus ) {
            $total_seats = get_post_meta( $bus->ID, '_moga_total_seats', true );
            $bus_type    = get_post_meta( $bus->ID, '_moga_bus_type', true );
            $plate       = get_post_meta( $bus->ID, '_moga_bus_plate', true );

            $result[ $bus->ID ] = sprintf(
                '%s — %s (%s seats)',
                $bus->post_title,
                $plate ? $plate : __( 'No plate', 'moga-travel-core' ),
                $total_seats ? $total_seats : '?'
            );
        }

        return $result;
    }
}