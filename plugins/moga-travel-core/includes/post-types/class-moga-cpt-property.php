<?php
/**
 * Property Custom Post Type
 *
 * Registers the moga_property custom post type with all
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
 * Class Moga_CPT_Property
 */
class Moga_CPT_Property {

    /**
     * Post type key.
     *
     * @since 1.0.0
     * @var   string
     */
    const POST_TYPE = 'moga_property';

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
            'name'                  => _x( 'Properties', 'Post type general name', 'moga-travel-core' ),
            'singular_name'         => _x( 'Property', 'Post type singular name', 'moga-travel-core' ),
            'menu_name'             => _x( 'Properties', 'Admin Menu text', 'moga-travel-core' ),
            'name_admin_bar'        => _x( 'Property', 'Add New on Toolbar', 'moga-travel-core' ),
            'add_new'               => __( 'Add New', 'moga-travel-core' ),
            'add_new_item'          => __( 'Add New Property', 'moga-travel-core' ),
            'new_item'              => __( 'New Property', 'moga-travel-core' ),
            'edit_item'             => __( 'Edit Property', 'moga-travel-core' ),
            'view_item'             => __( 'View Property', 'moga-travel-core' ),
            'all_items'             => __( 'All Properties', 'moga-travel-core' ),
            'search_items'          => __( 'Search Properties', 'moga-travel-core' ),
            'parent_item_colon'     => __( 'Parent Property:', 'moga-travel-core' ),
            'not_found'             => __( 'No properties found.', 'moga-travel-core' ),
            'not_found_in_trash'    => __( 'No properties found in Trash.', 'moga-travel-core' ),
            'featured_image'        => __( 'Property Cover Image', 'moga-travel-core' ),
            'set_featured_image'    => __( 'Set cover image', 'moga-travel-core' ),
            'remove_featured_image' => __( 'Remove cover image', 'moga-travel-core' ),
            'use_featured_image'    => __( 'Use as cover image', 'moga-travel-core' ),
            'archives'              => __( 'Property Archives', 'moga-travel-core' ),
            'insert_into_item'      => __( 'Insert into property', 'moga-travel-core' ),
            'uploaded_to_this_item' => __( 'Uploaded to this property', 'moga-travel-core' ),
            'filter_items_list'     => __( 'Filter properties list', 'moga-travel-core' ),
            'items_list_navigation' => __( 'Properties list navigation', 'moga-travel-core' ),
            'items_list'            => __( 'Properties list', 'moga-travel-core' ),
        );

        $args = array(
            'labels'              => $labels,
            'description'         => __( 'Hotel, apartment, villa, and rental property listings.', 'moga-travel-core' ),

            // Visibility.
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_nav_menus'   => true,
            'show_in_admin_bar'   => true,
            'show_in_rest'        => true, // Enables Gutenberg and REST API.

            // Admin.
            'menu_position'       => 5,
            'menu_icon'           => 'dashicons-building',

            // Capabilities.
            'capability_type'     => 'post',
            'map_meta_cap'        => true,

            // Supports.
            'supports'            => array(
                'title',           // Property name.
                'editor',          // Description.
                'thumbnail',       // Cover image.
                'excerpt',         // Short description.
                'author',          // Property owner.
                'revisions',       // Version history.
                'custom-fields',   // Meta fields.
                'page-attributes', // Order.
            ),

            // Taxonomies.
            'taxonomies'          => array(
                'moga_property_type',
                'moga_location',
            ),

            // URLs.
            'rewrite'             => array(
                'slug'       => 'properties',
                'with_front' => false,
                'feeds'      => false,
                'pages'      => true,
            ),

            // Query.
            'query_var'           => true,
            'has_archive'         => 'properties',

            // Hierarchical.
            'hierarchical'        => false,
        );

        register_post_type( self::POST_TYPE, $args );

        // Register meta fields for REST API and Gutenberg.
        self::register_meta_fields();
    }


    /**
     * Register all property meta fields.
     * Makes them available via REST API and
     * ensures they are properly sanitized.
     *
     * @since  1.0.0
     * @return void
     */
    private static function register_meta_fields() {

        $meta_fields = array(

            // ---- Pricing ----
            '_moga_price_per_night'   => array(
                'type'        => 'number',
                'description' => __( 'Price per night in the default currency.', 'moga-travel-core' ),
                'default'     => 0,
            ),
            '_moga_price_weekend'     => array(
                'type'        => 'number',
                'description' => __( 'Weekend price per night.', 'moga-travel-core' ),
                'default'     => 0,
            ),
            '_moga_price_discount'    => array(
                'type'        => 'number',
                'description' => __( 'Discount percentage (0-100).', 'moga-travel-core' ),
                'default'     => 0,
            ),
            '_moga_currency'          => array(
                'type'        => 'string',
                'description' => __( 'Currency code (e.g. USD, EGP).', 'moga-travel-core' ),
                'default'     => 'USD',
            ),

            // ---- Location ----
            '_moga_country'           => array(
                'type'        => 'string',
                'description' => __( 'Country ISO code (e.g. EG, US).', 'moga-travel-core' ),
                'default'     => '',
            ),
            '_moga_country_name'      => array(
                'type'        => 'string',
                'description' => __( 'Country full name.', 'moga-travel-core' ),
                'default'     => '',
            ),
            '_moga_city'              => array(
                'type'        => 'string',
                'description' => __( 'City name.', 'moga-travel-core' ),
                'default'     => '',
            ),
            '_moga_district'          => array(
                'type'        => 'string',
                'description' => __( 'District or area name.', 'moga-travel-core' ),
                'default'     => '',
            ),
            '_moga_address'           => array(
                'type'        => 'string',
                'description' => __( 'Street address.', 'moga-travel-core' ),
                'default'     => '',
            ),
            '_moga_postal_code'       => array(
                'type'        => 'string',
                'description' => __( 'Postal or ZIP code.', 'moga-travel-core' ),
                'default'     => '',
            ),
            '_moga_latitude'          => array(
                'type'        => 'string',
                'description' => __( 'GPS latitude coordinate.', 'moga-travel-core' ),
                'default'     => '',
            ),
            '_moga_longitude'         => array(
                'type'        => 'string',
                'description' => __( 'GPS longitude coordinate.', 'moga-travel-core' ),
                'default'     => '',
            ),

            // ---- Contact ----
            '_moga_phone'             => array(
                'type'        => 'string',
                'description' => __( 'Property phone with country code.', 'moga-travel-core' ),
                'default'     => '',
            ),
            '_moga_whatsapp'          => array(
                'type'        => 'string',
                'description' => __( 'WhatsApp number with country code.', 'moga-travel-core' ),
                'default'     => '',
            ),
            '_moga_email'             => array(
                'type'        => 'string',
                'description' => __( 'Property contact email.', 'moga-travel-core' ),
                'default'     => '',
            ),

            // ---- Property Details ----
            '_moga_max_guests'        => array(
                'type'        => 'integer',
                'description' => __( 'Maximum number of guests allowed.', 'moga-travel-core' ),
                'default'     => 1,
            ),
            '_moga_bedrooms'          => array(
                'type'        => 'integer',
                'description' => __( 'Number of bedrooms.', 'moga-travel-core' ),
                'default'     => 1,
            ),
            '_moga_bathrooms'         => array(
                'type'        => 'number',
                'description' => __( 'Number of bathrooms.', 'moga-travel-core' ),
                'default'     => 1,
            ),
            '_moga_area'              => array(
                'type'        => 'number',
                'description' => __( 'Property area in square meters.', 'moga-travel-core' ),
                'default'     => 0,
            ),
            '_moga_floor'             => array(
                'type'        => 'integer',
                'description' => __( 'Floor number (0 = ground floor).', 'moga-travel-core' ),
                'default'     => 0,
            ),
            '_moga_building_floors'   => array(
                'type'        => 'integer',
                'description' => __( 'Total floors in the building.', 'moga-travel-core' ),
                'default'     => 1,
            ),
            '_moga_year_built'        => array(
                'type'        => 'integer',
                'description' => __( 'Year the property was built.', 'moga-travel-core' ),
                'default'     => 0,
            ),

            // ---- Amenities ----
            '_moga_amenities'         => array(
                'type'        => 'string',
                'description' => __( 'JSON array of amenity keys.', 'moga-travel-core' ),
                'default'     => '',
            ),

            // ---- Booking Rules ----
            '_moga_min_stay'          => array(
                'type'        => 'integer',
                'description' => __( 'Minimum nights required per booking.', 'moga-travel-core' ),
                'default'     => 1,
            ),
            '_moga_max_stay'          => array(
                'type'        => 'integer',
                'description' => __( 'Maximum nights allowed per booking (0 = unlimited).', 'moga-travel-core' ),
                'default'     => 0,
            ),
            '_moga_checkin_time'      => array(
                'type'        => 'string',
                'description' => __( 'Check-in time (e.g. 14:00).', 'moga-travel-core' ),
                'default'     => '14:00',
            ),
            '_moga_checkout_time'     => array(
                'type'        => 'string',
                'description' => __( 'Check-out time (e.g. 11:00).', 'moga-travel-core' ),
                'default'     => '11:00',
            ),
            '_moga_cancellation'      => array(
                'type'        => 'string',
                'description' => __( 'Cancellation policy (free, moderate, strict).', 'moga-travel-core' ),
                'default'     => 'moderate',
            ),

            // ---- Status ----
            '_moga_featured'          => array(
                'type'        => 'integer',
                'description' => __( 'Featured on homepage (1 = yes, 0 = no).', 'moga-travel-core' ),
                'default'     => 0,
            ),
            '_moga_instant_booking'   => array(
                'type'        => 'integer',
                'description' => __( 'Allow instant booking without approval (1 = yes).', 'moga-travel-core' ),
                'default'     => 1,
            ),
            '_moga_active'            => array(
                'type'        => 'integer',
                'description' => __( 'Listing is active and visible (1 = yes).', 'moga-travel-core' ),
                'default'     => 1,
            ),

            // ---- Stats (auto-updated) ----
            '_moga_rating'            => array(
                'type'        => 'number',
                'description' => __( 'Average rating (0-10).', 'moga-travel-core' ),
                'default'     => 0,
            ),
            '_moga_review_count'      => array(
                'type'        => 'integer',
                'description' => __( 'Total number of reviews.', 'moga-travel-core' ),
                'default'     => 0,
            ),
            '_moga_booking_count'     => array(
                'type'        => 'integer',
                'description' => __( 'Total number of completed bookings.', 'moga-travel-core' ),
                'default'     => 0,
            ),
            '_moga_view_count'        => array(
                'type'        => 'integer',
                'description' => __( 'Total page views.', 'moga-travel-core' ),
                'default'     => 0,
            ),

            // ---- Gallery ----
            '_moga_gallery'           => array(
                'type'        => 'string',
                'description' => __( 'JSON array of attachment IDs for the gallery.', 'moga-travel-core' ),
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
     * Get the appropriate sanitize callback for a meta field type.
     *
     * @since  1.0.0
     * @param  string $type Field type (string, number, integer).
     * @return callable     Sanitize callback function.
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
     * Get all available amenities list.
     * Used by meta boxes and frontend filters.
     *
     * @since  1.0.0
     * @return array Amenities with key, label, and icon.
     */
    public static function get_amenities() {
        return array(

            // Internet.
            'wifi'              => array(
                'label' => __( 'Free WiFi', 'moga-travel-core' ),
                'icon'  => 'dashicons-wifi',
                'group' => 'internet',
            ),

            // Climate.
            'air_conditioning'  => array(
                'label' => __( 'Air Conditioning', 'moga-travel-core' ),
                'icon'  => 'dashicons-admin-settings',
                'group' => 'climate',
            ),
            'heating'           => array(
                'label' => __( 'Heating', 'moga-travel-core' ),
                'icon'  => 'dashicons-admin-settings',
                'group' => 'climate',
            ),

            // Kitchen.
            'kitchen'           => array(
                'label' => __( 'Kitchen', 'moga-travel-core' ),
                'icon'  => 'dashicons-food',
                'group' => 'kitchen',
            ),
            'refrigerator'      => array(
                'label' => __( 'Refrigerator', 'moga-travel-core' ),
                'icon'  => 'dashicons-food',
                'group' => 'kitchen',
            ),
            'microwave'         => array(
                'label' => __( 'Microwave', 'moga-travel-core' ),
                'icon'  => 'dashicons-food',
                'group' => 'kitchen',
            ),
            'washing_machine'   => array(
                'label' => __( 'Washing Machine', 'moga-travel-core' ),
                'icon'  => 'dashicons-update',
                'group' => 'kitchen',
            ),

            // Facilities.
            'pool'              => array(
                'label' => __( 'Swimming Pool', 'moga-travel-core' ),
                'icon'  => 'dashicons-superhero',
                'group' => 'facilities',
            ),
            'gym'               => array(
                'label' => __( 'Gym / Fitness Center', 'moga-travel-core' ),
                'icon'  => 'dashicons-heart',
                'group' => 'facilities',
            ),
            'spa'               => array(
                'label' => __( 'Spa', 'moga-travel-core' ),
                'icon'  => 'dashicons-heart',
                'group' => 'facilities',
            ),
            'restaurant'        => array(
                'label' => __( 'Restaurant', 'moga-travel-core' ),
                'icon'  => 'dashicons-food',
                'group' => 'facilities',
            ),
            'room_service'      => array(
                'label' => __( 'Room Service', 'moga-travel-core' ),
                'icon'  => 'dashicons-food',
                'group' => 'facilities',
            ),

            // Transport.
            'parking'           => array(
                'label' => __( 'Free Parking', 'moga-travel-core' ),
                'icon'  => 'dashicons-car',
                'group' => 'transport',
            ),
            'airport_transfer'  => array(
                'label' => __( 'Airport Transfer', 'moga-travel-core' ),
                'icon'  => 'dashicons-airplane',
                'group' => 'transport',
            ),

            // Beach / Outdoor.
            'beach_access'      => array(
                'label' => __( 'Beach Access', 'moga-travel-core' ),
                'icon'  => 'dashicons-palmtree',
                'group' => 'outdoor',
            ),
            'sea_view'          => array(
                'label' => __( 'Sea View', 'moga-travel-core' ),
                'icon'  => 'dashicons-palmtree',
                'group' => 'outdoor',
            ),
            'balcony'           => array(
                'label' => __( 'Balcony / Terrace', 'moga-travel-core' ),
                'icon'  => 'dashicons-admin-home',
                'group' => 'outdoor',
            ),
            'garden'            => array(
                'label' => __( 'Garden', 'moga-travel-core' ),
                'icon'  => 'dashicons-palmtree',
                'group' => 'outdoor',
            ),

            // Services.
            'breakfast'         => array(
                'label' => __( 'Breakfast Included', 'moga-travel-core' ),
                'icon'  => 'dashicons-food',
                'group' => 'services',
            ),
            'daily_cleaning'    => array(
                'label' => __( 'Daily Cleaning', 'moga-travel-core' ),
                'icon'  => 'dashicons-star-filled',
                'group' => 'services',
            ),
            'luggage_storage'   => array(
                'label' => __( 'Luggage Storage', 'moga-travel-core' ),
                'icon'  => 'dashicons-archive',
                'group' => 'services',
            ),
            'reception_24h'     => array(
                'label' => __( '24H Reception', 'moga-travel-core' ),
                'icon'  => 'dashicons-clock',
                'group' => 'services',
            ),

            // Safety.
            'security'          => array(
                'label' => __( '24H Security', 'moga-travel-core' ),
                'icon'  => 'dashicons-shield',
                'group' => 'safety',
            ),
            'cctv'              => array(
                'label' => __( 'CCTV Cameras', 'moga-travel-core' ),
                'icon'  => 'dashicons-camera',
                'group' => 'safety',
            ),
            'smoke_detector'    => array(
                'label' => __( 'Smoke Detector', 'moga-travel-core' ),
                'icon'  => 'dashicons-warning',
                'group' => 'safety',
            ),
            'fire_extinguisher' => array(
                'label' => __( 'Fire Extinguisher', 'moga-travel-core' ),
                'icon'  => 'dashicons-warning',
                'group' => 'safety',
            ),

            // Family.
            'kids_pool'         => array(
                'label' => __( 'Kids Pool', 'moga-travel-core' ),
                'icon'  => 'dashicons-smiley',
                'group' => 'family',
            ),
            'playground'        => array(
                'label' => __( 'Playground', 'moga-travel-core' ),
                'icon'  => 'dashicons-smiley',
                'group' => 'family',
            ),
            'babysitting'       => array(
                'label' => __( 'Babysitting', 'moga-travel-core' ),
                'icon'  => 'dashicons-smiley',
                'group' => 'family',
            ),

            // Accessibility.
            'elevator'          => array(
                'label' => __( 'Elevator', 'moga-travel-core' ),
                'icon'  => 'dashicons-arrow-up-alt',
                'group' => 'accessibility',
            ),
            'wheelchair'        => array(
                'label' => __( 'Wheelchair Accessible', 'moga-travel-core' ),
                'icon'  => 'dashicons-universal-access',
                'group' => 'accessibility',
            ),

            // Entertainment.
            'tv'                => array(
                'label' => __( 'Smart TV', 'moga-travel-core' ),
                'icon'  => 'dashicons-desktop',
                'group' => 'entertainment',
            ),
            'netflix'           => array(
                'label' => __( 'Netflix', 'moga-travel-core' ),
                'icon'  => 'dashicons-video-alt3',
                'group' => 'entertainment',
            ),
            'game_room'         => array(
                'label' => __( 'Game Room', 'moga-travel-core' ),
                'icon'  => 'dashicons-games',
                'group' => 'entertainment',
            ),
        );
    }


    /**
     * Get amenity groups for organized display.
     *
     * @since  1.0.0
     * @return array Amenity group labels.
     */
    public static function get_amenity_groups() {
        return array(
            'internet'      => __( 'Internet', 'moga-travel-core' ),
            'climate'       => __( 'Climate Control', 'moga-travel-core' ),
            'kitchen'       => __( 'Kitchen & Laundry', 'moga-travel-core' ),
            'facilities'    => __( 'Facilities', 'moga-travel-core' ),
            'transport'     => __( 'Transport', 'moga-travel-core' ),
            'outdoor'       => __( 'Outdoor & Views', 'moga-travel-core' ),
            'services'      => __( 'Services', 'moga-travel-core' ),
            'safety'        => __( 'Safety & Security', 'moga-travel-core' ),
            'family'        => __( 'Family & Kids', 'moga-travel-core' ),
            'accessibility' => __( 'Accessibility', 'moga-travel-core' ),
            'entertainment' => __( 'Entertainment', 'moga-travel-core' ),
        );
    }


    /**
     * Get cancellation policy options.
     *
     * @since  1.0.0
     * @return array Cancellation policy options.
     */
    public static function get_cancellation_policies() {
        return array(
            'free'     => array(
                'label' => __( 'Free Cancellation', 'moga-travel-core' ),
                'desc'  => __( 'Guests can cancel for free up to 24 hours before check-in.', 'moga-travel-core' ),
            ),
            'moderate' => array(
                'label' => __( 'Moderate', 'moga-travel-core' ),
                'desc'  => __( 'Guests can cancel for free up to 5 days before check-in.', 'moga-travel-core' ),
            ),
            'strict'   => array(
                'label' => __( 'Strict', 'moga-travel-core' ),
                'desc'  => __( 'No refund if cancelled less than 14 days before check-in.', 'moga-travel-core' ),
            ),
            'no_refund' => array(
                'label' => __( 'No Refund', 'moga-travel-core' ),
                'desc'  => __( 'Non-refundable. No cancellation allowed after booking.', 'moga-travel-core' ),
            ),
        );
    }
}