<?php
/**
 * Tour Custom Post Type
 *
 * Registers the moga_tour custom post type with all
 * labels, supports, capabilities, and rewrite rules.
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
 * Class Moga_CPT_Tour
 */
class Moga_CPT_Tour {

    /**
     * Post type key.
     *
     * @since 1.0.0
     * @var   string
     */
    const POST_TYPE = 'moga_tour';

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
            'name'                  => _x( 'Tours', 'Post type general name', 'moga-travel-core' ),
            'singular_name'         => _x( 'Tour', 'Post type singular name', 'moga-travel-core' ),
            'menu_name'             => _x( 'Tours', 'Admin Menu text', 'moga-travel-core' ),
            'name_admin_bar'        => _x( 'Tour', 'Add New on Toolbar', 'moga-travel-core' ),
            'add_new'               => __( 'Add New', 'moga-travel-core' ),
            'add_new_item'          => __( 'Add New Tour', 'moga-travel-core' ),
            'new_item'              => __( 'New Tour', 'moga-travel-core' ),
            'edit_item'             => __( 'Edit Tour', 'moga-travel-core' ),
            'view_item'             => __( 'View Tour', 'moga-travel-core' ),
            'all_items'             => __( 'All Tours', 'moga-travel-core' ),
            'search_items'          => __( 'Search Tours', 'moga-travel-core' ),
            'parent_item_colon'     => __( 'Parent Tour:', 'moga-travel-core' ),
            'not_found'             => __( 'No tours found.', 'moga-travel-core' ),
            'not_found_in_trash'    => __( 'No tours found in Trash.', 'moga-travel-core' ),
            'featured_image'        => __( 'Tour Cover Image', 'moga-travel-core' ),
            'set_featured_image'    => __( 'Set tour cover image', 'moga-travel-core' ),
            'remove_featured_image' => __( 'Remove tour cover image', 'moga-travel-core' ),
            'use_featured_image'    => __( 'Use as tour cover image', 'moga-travel-core' ),
            'archives'              => __( 'Tour Archives', 'moga-travel-core' ),
            'insert_into_item'      => __( 'Insert into tour', 'moga-travel-core' ),
            'uploaded_to_this_item' => __( 'Uploaded to this tour', 'moga-travel-core' ),
            'filter_items_list'     => __( 'Filter tours list', 'moga-travel-core' ),
            'items_list_navigation' => __( 'Tours list navigation', 'moga-travel-core' ),
            'items_list'            => __( 'Tours list', 'moga-travel-core' ),
        );

        $args = array(
            'labels'              => $labels,
            'description'         => __( 'Guided tour packages with bus seat reservations.', 'moga-travel-core' ),

            // Visibility.
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_nav_menus'   => true,
            'show_in_admin_bar'   => true,
            'show_in_rest'        => true,

            // Admin.
            'menu_position'       => 6,
            'menu_icon'           => 'dashicons-location-alt',

            // Capabilities.
            'capability_type'     => 'post',
            'map_meta_cap'        => true,

            // Supports.
            'supports'            => array(
                'title',
                'editor',
                'thumbnail',
                'excerpt',
                'author',
                'revisions',
                'custom-fields',
                'page-attributes',
            ),

            // Taxonomies.
            'taxonomies'          => array(
                'moga_tour_category',
                'moga_location',
            ),

            // URLs.
            'rewrite'             => array(
                'slug'       => 'tours',
                'with_front' => false,
                'feeds'      => false,
                'pages'      => true,
            ),

            // Query.
            'query_var'           => true,
            'has_archive'         => 'tours',

            // Hierarchical.
            'hierarchical'        => false,
        );

        register_post_type( self::POST_TYPE, $args );

        // Register meta fields.
        self::register_meta_fields();
    }


    /**
     * Register all tour meta fields.
     *
     * @since  1.0.0
     * @return void
     */
    private static function register_meta_fields() {

        $meta_fields = array(

            // ---- Pricing ----
            '_moga_price_per_person'     => array(
                'type'        => 'number',
                'description' => __( 'Price per adult person.', 'moga-travel-core' ),
                'default'     => 0,
            ),
            '_moga_price_child'          => array(
                'type'        => 'number',
                'description' => __( 'Price per child (under 12).', 'moga-travel-core' ),
                'default'     => 0,
            ),
            '_moga_price_infant'         => array(
                'type'        => 'number',
                'description' => __( 'Price per infant (under 2). 0 = free.', 'moga-travel-core' ),
                'default'     => 0,
            ),
            '_moga_price_group'          => array(
                'type'        => 'number',
                'description' => __( 'Group discount percentage.', 'moga-travel-core' ),
                'default'     => 0,
            ),
            '_moga_currency'             => array(
                'type'        => 'string',
                'description' => __( 'Currency code.', 'moga-travel-core' ),
                'default'     => 'USD',
            ),

            // ---- Schedule ----
            '_moga_duration_days'        => array(
                'type'        => 'integer',
                'description' => __( 'Tour duration in days.', 'moga-travel-core' ),
                'default'     => 1,
            ),
            '_moga_duration_nights'      => array(
                'type'        => 'integer',
                'description' => __( 'Tour duration in nights.', 'moga-travel-core' ),
                'default'     => 0,
            ),
            '_moga_departure_time'       => array(
                'type'        => 'string',
                'description' => __( 'Daily departure time (e.g. 08:00).', 'moga-travel-core' ),
                'default'     => '08:00',
            ),
            '_moga_return_time'          => array(
                'type'        => 'string',
                'description' => __( 'Estimated return time (e.g. 18:00).', 'moga-travel-core' ),
                'default'     => '18:00',
            ),
            '_moga_available_days'       => array(
                'type'        => 'string',
                'description' => __( 'JSON array of available weekdays (0=Sun, 6=Sat).', 'moga-travel-core' ),
                'default'     => '',
            ),
            '_moga_start_dates'          => array(
                'type'        => 'string',
                'description' => __( 'JSON array of specific start dates (Y-m-d format).', 'moga-travel-core' ),
                'default'     => '',
            ),

            // ---- Location ----
            '_moga_departure_country'    => array(
                'type'        => 'string',
                'description' => __( 'Departure country ISO code.', 'moga-travel-core' ),
                'default'     => '',
            ),
            '_moga_departure_city'       => array(
                'type'        => 'string',
                'description' => __( 'Departure city name.', 'moga-travel-core' ),
                'default'     => '',
            ),
            '_moga_departure_point'      => array(
                'type'        => 'string',
                'description' => __( 'Exact departure point address.', 'moga-travel-core' ),
                'default'     => '',
            ),
            '_moga_destination_country'  => array(
                'type'        => 'string',
                'description' => __( 'Destination country ISO code.', 'moga-travel-core' ),
                'default'     => '',
            ),
            '_moga_destination_city'     => array(
                'type'        => 'string',
                'description' => __( 'Destination city name.', 'moga-travel-core' ),
                'default'     => '',
            ),
            '_moga_route_stops'          => array(
                'type'        => 'string',
                'description' => __( 'JSON array of route stop names.', 'moga-travel-core' ),
                'default'     => '',
            ),
            '_moga_latitude'             => array(
                'type'        => 'string',
                'description' => __( 'Main destination GPS latitude.', 'moga-travel-core' ),
                'default'     => '',
            ),
            '_moga_longitude'            => array(
                'type'        => 'string',
                'description' => __( 'Main destination GPS longitude.', 'moga-travel-core' ),
                'default'     => '',
            ),

            // ---- Contact ----
            '_moga_organizer_name'       => array(
                'type'        => 'string',
                'description' => __( 'Tour organizer name.', 'moga-travel-core' ),
                'default'     => '',
            ),
            '_moga_phone'                => array(
                'type'        => 'string',
                'description' => __( 'Organizer phone with country code.', 'moga-travel-core' ),
                'default'     => '',
            ),
            '_moga_whatsapp'             => array(
                'type'        => 'string',
                'description' => __( 'Organizer WhatsApp number.', 'moga-travel-core' ),
                'default'     => '',
            ),
            '_moga_email'                => array(
                'type'        => 'string',
                'description' => __( 'Organizer contact email.', 'moga-travel-core' ),
                'default'     => '',
            ),

            // ---- Tour Details ----
            '_moga_max_participants'     => array(
                'type'        => 'integer',
                'description' => __( 'Maximum number of participants.', 'moga-travel-core' ),
                'default'     => 20,
            ),
            '_moga_min_participants'     => array(
                'type'        => 'integer',
                'description' => __( 'Minimum participants required to run the tour.', 'moga-travel-core' ),
                'default'     => 1,
            ),
            '_moga_difficulty'           => array(
                'type'        => 'string',
                'description' => __( 'Tour difficulty level.', 'moga-travel-core' ),
                'default'     => 'easy',
            ),
            '_moga_language'             => array(
                'type'        => 'string',
                'description' => __( 'Tour language (e.g. Arabic, English).', 'moga-travel-core' ),
                'default'     => 'Arabic',
            ),
            '_moga_languages'            => array(
                'type'        => 'string',
                'description' => __( 'JSON array of all available languages.', 'moga-travel-core' ),
                'default'     => '',
            ),
            '_moga_tour_type'            => array(
                'type'        => 'string',
                'description' => __( 'Tour type (group, private, custom).', 'moga-travel-core' ),
                'default'     => 'group',
            ),
            '_moga_guide_included'       => array(
                'type'        => 'integer',
                'description' => __( 'Tour guide included (1 = yes).', 'moga-travel-core' ),
                'default'     => 1,
            ),

            // ---- Includes / Excludes ----
            '_moga_includes'             => array(
                'type'        => 'string',
                'description' => __( 'JSON array of what is included in the tour.', 'moga-travel-core' ),
                'default'     => '',
            ),
            '_moga_excludes'             => array(
                'type'        => 'string',
                'description' => __( 'JSON array of what is NOT included in the tour.', 'moga-travel-core' ),
                'default'     => '',
            ),

            // ---- Itinerary ----
            '_moga_itinerary'            => array(
                'type'        => 'string',
                'description' => __( 'JSON array of day-by-day itinerary items.', 'moga-travel-core' ),
                'default'     => '',
            ),

            // ---- Bus / Seats ----
            '_moga_bus_id'               => array(
                'type'        => 'integer',
                'description' => __( 'Assigned bus post ID.', 'moga-travel-core' ),
                'default'     => 0,
            ),
            '_moga_seats_available'      => array(
                'type'        => 'integer',
                'description' => __( 'Number of available seats.', 'moga-travel-core' ),
                'default'     => 0,
            ),
            '_moga_seats_total'          => array(
                'type'        => 'integer',
                'description' => __( 'Total number of seats on the bus.', 'moga-travel-core' ),
                'default'     => 0,
            ),

            // ---- Cancellation ----
            '_moga_cancellation'         => array(
                'type'        => 'string',
                'description' => __( 'Cancellation policy key.', 'moga-travel-core' ),
                'default'     => 'moderate',
            ),

            // ---- Status ----
            '_moga_featured'             => array(
                'type'        => 'integer',
                'description' => __( 'Featured on homepage (1 = yes).', 'moga-travel-core' ),
                'default'     => 0,
            ),
            '_moga_instant_booking'      => array(
                'type'        => 'integer',
                'description' => __( 'Allow instant booking (1 = yes).', 'moga-travel-core' ),
                'default'     => 1,
            ),
            '_moga_active'               => array(
                'type'        => 'integer',
                'description' => __( 'Tour is active and visible (1 = yes).', 'moga-travel-core' ),
                'default'     => 1,
            ),

            // ---- Stats ----
            '_moga_rating'               => array(
                'type'        => 'number',
                'description' => __( 'Average rating (0-10).', 'moga-travel-core' ),
                'default'     => 0,
            ),
            '_moga_review_count'         => array(
                'type'        => 'integer',
                'description' => __( 'Total number of reviews.', 'moga-travel-core' ),
                'default'     => 0,
            ),
            '_moga_booking_count'        => array(
                'type'        => 'integer',
                'description' => __( 'Total completed bookings.', 'moga-travel-core' ),
                'default'     => 0,
            ),
            '_moga_view_count'           => array(
                'type'        => 'integer',
                'description' => __( 'Total page views.', 'moga-travel-core' ),
                'default'     => 0,
            ),

            // ---- Gallery ----
            '_moga_gallery'              => array(
                'type'        => 'string',
                'description' => __( 'JSON array of attachment IDs for gallery.', 'moga-travel-core' ),
                'default'     => '',
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
     * Get difficulty level options.
     *
     * @since  1.0.0
     * @return array
     */
    public static function get_difficulty_levels() {
        return array(
            'easy'        => array(
                'label' => __( 'Easy', 'moga-travel-core' ),
                'desc'  => __( 'Suitable for all ages and fitness levels.', 'moga-travel-core' ),
                'color' => '#28a745',
            ),
            'moderate'    => array(
                'label' => __( 'Moderate', 'moga-travel-core' ),
                'desc'  => __( 'Requires basic fitness. Some walking involved.', 'moga-travel-core' ),
                'color' => '#f5a623',
            ),
            'challenging' => array(
                'label' => __( 'Challenging', 'moga-travel-core' ),
                'desc'  => __( 'Requires good fitness. Long walks or hikes involved.', 'moga-travel-core' ),
                'color' => '#dc3545',
            ),
            'extreme'     => array(
                'label' => __( 'Extreme', 'moga-travel-core' ),
                'desc'  => __( 'For experienced adventurers only.', 'moga-travel-core' ),
                'color' => '#6f42c1',
            ),
        );
    }


    /**
     * Get tour type options.
     *
     * @since  1.0.0
     * @return array
     */
    public static function get_tour_types() {
        return array(
            'group'   => array(
                'label' => __( 'Group Tour', 'moga-travel-core' ),
                'desc'  => __( 'Join a group of travelers. Most affordable option.', 'moga-travel-core' ),
            ),
            'private' => array(
                'label' => __( 'Private Tour', 'moga-travel-core' ),
                'desc'  => __( 'Exclusive tour for your group only.', 'moga-travel-core' ),
            ),
            'custom'  => array(
                'label' => __( 'Custom Tour', 'moga-travel-core' ),
                'desc'  => __( 'Fully customizable itinerary on request.', 'moga-travel-core' ),
            ),
        );
    }


    /**
     * Get what is typically included in tours.
     * Used as checkbox options in the meta box.
     *
     * @since  1.0.0
     * @return array
     */
    public static function get_includes_options() {
        return array(
            'transport'        => __( 'Transportation', 'moga-travel-core' ),
            'bus_seat'         => __( 'Bus Seat Reservation', 'moga-travel-core' ),
            'guide'            => __( 'Professional Guide', 'moga-travel-core' ),
            'breakfast'        => __( 'Breakfast', 'moga-travel-core' ),
            'lunch'            => __( 'Lunch', 'moga-travel-core' ),
            'dinner'           => __( 'Dinner', 'moga-travel-core' ),
            'water'            => __( 'Water & Soft Drinks', 'moga-travel-core' ),
            'hotel'            => __( 'Hotel Accommodation', 'moga-travel-core' ),
            'entrance_fees'    => __( 'Entrance Fees', 'moga-travel-core' ),
            'snorkeling_gear'  => __( 'Snorkeling Gear', 'moga-travel-core' ),
            'diving_equipment' => __( 'Diving Equipment', 'moga-travel-core' ),
            'horse_riding'     => __( 'Horse / Camel Riding', 'moga-travel-core' ),
            'insurance'        => __( 'Travel Insurance', 'moga-travel-core' ),
            'photos'           => __( 'Professional Photos', 'moga-travel-core' ),
            'airport_pickup'   => __( 'Airport Pickup', 'moga-travel-core' ),
            'hotel_pickup'     => __( 'Hotel Pickup & Drop-off', 'moga-travel-core' ),
        );
    }


    /**
     * Get what is typically NOT included in tours.
     *
     * @since  1.0.0
     * @return array
     */
    public static function get_excludes_options() {
        return array(
            'tips'             => __( 'Tips & Gratuities', 'moga-travel-core' ),
            'personal_exp'     => __( 'Personal Expenses', 'moga-travel-core' ),
            'visa'             => __( 'Visa Fees', 'moga-travel-core' ),
            'airfare'          => __( 'International Airfare', 'moga-travel-core' ),
            'travel_insurance' => __( 'Travel Insurance', 'moga-travel-core' ),
            'optional_act'     => __( 'Optional Activities', 'moga-travel-core' ),
            'drinks'           => __( 'Alcoholic Drinks', 'moga-travel-core' ),
            'laundry'          => __( 'Laundry Service', 'moga-travel-core' ),
            'room_service'     => __( 'Room Service', 'moga-travel-core' ),
            'phone_calls'      => __( 'Phone Calls', 'moga-travel-core' ),
        );
    }


    /**
     * Get available weekday options.
     *
     * @since  1.0.0
     * @return array
     */
    public static function get_weekdays() {
        return array(
            0 => __( 'Sunday', 'moga-travel-core' ),
            1 => __( 'Monday', 'moga-travel-core' ),
            2 => __( 'Tuesday', 'moga-travel-core' ),
            3 => __( 'Wednesday', 'moga-travel-core' ),
            4 => __( 'Thursday', 'moga-travel-core' ),
            5 => __( 'Friday', 'moga-travel-core' ),
            6 => __( 'Saturday', 'moga-travel-core' ),
        );
    }
}