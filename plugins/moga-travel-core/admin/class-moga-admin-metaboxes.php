<?php
/**
 * Admin Meta Boxes
 *
 * Registers and renders all meta boxes for
 * Properties and Tours in the WordPress admin.
 *
 * Meta boxes registered:
 *   Properties:
 *     - Pricing
 *     - Location (country + city dropdowns)
 *     - Contact (intl-tel-input phone field)
 *     - Property Details
 *     - Amenities
 *     - Booking Rules
 *     - Status & Visibility
 *     - Gallery
 *
 *   Tours:
 *     - Pricing
 *     - Schedule
 *     - Location (departure + destination)
 *     - Contact (intl-tel-input phone field)
 *     - Tour Details
 *     - Includes / Excludes
 *     - Bus & Seats
 *     - Status & Visibility
 *     - Gallery
 *
 * @package    MogaTravelCore
 * @subpackage MogaTravelCore/admin
 * @author     Hatem Frere
 * @since      1.0.0
 */

// Block direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Moga_Admin_Metaboxes
 */
class Moga_Admin_Metaboxes {

    /**
     * Initialize hooks.
     *
     * @since  1.0.0
     * @return void
     */
    public static function init() {
        add_action( 'add_meta_boxes', array( __CLASS__, 'register_meta_boxes' ) );
        add_action( 'save_post',      array( __CLASS__, 'save_meta_boxes' ), 10, 2 );
        add_action( 'admin_footer',   array( __CLASS__, 'meta_box_scripts' ) );
    }


    // ============================================================
    // REGISTER META BOXES
    // ============================================================

    /**
     * Register all meta boxes.
     *
     * @since  1.0.0
     * @return void
     */
    public static function register_meta_boxes() {

        // ---- Property Meta Boxes ----
        $property_boxes = array(
            array( 'moga_property_pricing',  __( '💰 Pricing',            'moga-travel-core' ), 'render_property_pricing'  ),
            array( 'moga_property_location', __( '📍 Location',           'moga-travel-core' ), 'render_property_location' ),
            array( 'moga_property_contact',  __( '📞 Contact',            'moga-travel-core' ), 'render_property_contact'  ),
            array( 'moga_property_details',  __( '🏠 Property Details',   'moga-travel-core' ), 'render_property_details'  ),
            array( 'moga_property_amenities',__( '✨ Amenities',          'moga-travel-core' ), 'render_property_amenities'),
            array( 'moga_property_booking',  __( '📅 Booking Rules',      'moga-travel-core' ), 'render_property_booking'  ),
            array( 'moga_property_status',   __( '⚙️ Status & Visibility','moga-travel-core' ), 'render_property_status'   ),
        );

        foreach ( $property_boxes as $box ) {
            add_meta_box(
                $box[0],
                $box[1],
                array( __CLASS__, $box[2] ),
                'moga_property',
                'normal',
                'high'
            );
        }

        // ---- Tour Meta Boxes ----
        $tour_boxes = array(
            array( 'moga_tour_pricing',   __( '💰 Pricing',             'moga-travel-core' ), 'render_tour_pricing'   ),
            array( 'moga_tour_schedule',  __( '🗓️ Schedule',            'moga-travel-core' ), 'render_tour_schedule'  ),
            array( 'moga_tour_location',  __( '📍 Location & Route',    'moga-travel-core' ), 'render_tour_location'  ),
            array( 'moga_tour_contact',   __( '📞 Organizer Contact',   'moga-travel-core' ), 'render_tour_contact'   ),
            array( 'moga_tour_details',   __( '🗺️ Tour Details',        'moga-travel-core' ), 'render_tour_details'   ),
            array( 'moga_tour_includes',  __( '✅ Includes & Excludes', 'moga-travel-core' ), 'render_tour_includes'  ),
            array( 'moga_tour_bus',       __( '🚌 Bus & Seats',         'moga-travel-core' ), 'render_tour_bus'       ),
            array( 'moga_tour_status',    __( '⚙️ Status & Visibility', 'moga-travel-core' ), 'render_tour_status'    ),
        );

        foreach ( $tour_boxes as $box ) {
            add_meta_box(
                $box[0],
                $box[1],
                array( __CLASS__, $box[2] ),
                'moga_tour',
                'normal',
                'high'
            );
        }
    }


    // ============================================================
    // PROPERTY META BOXES — RENDER
    // ============================================================

    /**
     * Render property pricing meta box.
     *
     * @since  1.0.0
     * @param  WP_Post $post Current post object.
     * @return void
     */
    public static function render_property_pricing( $post ) {
        wp_nonce_field( 'moga_property_pricing_nonce', 'moga_property_pricing_nonce' );

        $price          = get_post_meta( $post->ID, '_moga_price_per_night', true );
        $weekend_price  = get_post_meta( $post->ID, '_moga_price_weekend',   true );
        $discount       = get_post_meta( $post->ID, '_moga_price_discount',  true );
        $currency       = get_post_meta( $post->ID, '_moga_currency',        true ) ?: 'USD';

        $currencies = moga_get_currencies();
        ?>
        <div class="moga-metabox">
            <div class="moga-metabox__row">

                <div class="moga-metabox__field">
                    <label for="moga_price_per_night">
                        <?php esc_html_e( 'Price Per Night', 'moga-travel-core' ); ?>
                        <span class="required">*</span>
                    </label>
                    <input
                        type="number"
                        id="moga_price_per_night"
                        name="moga_price_per_night"
                        value="<?php echo esc_attr( $price ); ?>"
                        min="0"
                        step="0.01"
                        placeholder="0.00"
                    >
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_price_weekend">
                        <?php esc_html_e( 'Weekend Price (Fri-Sat)', 'moga-travel-core' ); ?>
                    </label>
                    <input
                        type="number"
                        id="moga_price_weekend"
                        name="moga_price_weekend"
                        value="<?php echo esc_attr( $weekend_price ); ?>"
                        min="0"
                        step="0.01"
                        placeholder="0.00"
                    >
                    <p class="moga-metabox__hint">
                        <?php esc_html_e( 'Leave empty to use the same price for weekends.', 'moga-travel-core' ); ?>
                    </p>
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_price_discount">
                        <?php esc_html_e( 'Discount %', 'moga-travel-core' ); ?>
                    </label>
                    <input
                        type="number"
                        id="moga_price_discount"
                        name="moga_price_discount"
                        value="<?php echo esc_attr( $discount ); ?>"
                        min="0"
                        max="100"
                        step="1"
                        placeholder="0"
                    >
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_currency">
                        <?php esc_html_e( 'Currency', 'moga-travel-core' ); ?>
                    </label>
                    <select id="moga_currency" name="moga_currency">
                        <?php foreach ( $currencies as $code => $label ) : ?>
                            <option
                                value="<?php echo esc_attr( $code ); ?>"
                                <?php selected( $currency, $code ); ?>
                            >
                                <?php echo esc_html( $label ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

            </div>
        </div>
        <?php
    }

    /**
     * Render property location meta box.
     *
     * @since  1.0.0
     * @param  WP_Post $post Current post object.
     * @return void
     */
    public static function render_property_location( $post ) {
        wp_nonce_field( 'moga_property_location_nonce', 'moga_property_location_nonce' );

        $country      = get_post_meta( $post->ID, '_moga_country',      true );
        $city         = get_post_meta( $post->ID, '_moga_city',         true );
        $district     = get_post_meta( $post->ID, '_moga_district',     true );
        $address      = get_post_meta( $post->ID, '_moga_address',      true );
        $postal_code  = get_post_meta( $post->ID, '_moga_postal_code',  true );
        $latitude     = get_post_meta( $post->ID, '_moga_latitude',     true );
        $longitude    = get_post_meta( $post->ID, '_moga_longitude',    true );

        $countries = moga_get_countries_dropdown();
        $cities    = $country ? moga_get_cities_dropdown( $country ) : array( '' => __( '— Select Country First —', 'moga-travel-core' ) );
        ?>
        <div class="moga-metabox">

            <div class="moga-metabox__row">

                <div class="moga-metabox__field">
                    <label for="moga_country">
                        <?php esc_html_e( 'Country', 'moga-travel-core' ); ?>
                        <span class="required">*</span>
                    </label>
                    <select
                        id="moga_country"
                        name="moga_country"
                        class="moga-country-select"
                        data-target="moga_city"
                    >
                        <?php foreach ( $countries as $code => $label ) : ?>
                            <option
                                value="<?php echo esc_attr( $code ); ?>"
                                <?php selected( $country, $code ); ?>
                            >
                                <?php echo esc_html( $label ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_city">
                        <?php esc_html_e( 'City', 'moga-travel-core' ); ?>
                        <span class="required">*</span>
                    </label>
                    <select
                        id="moga_city"
                        name="moga_city"
                        class="moga-city-select"
                    >
                        <?php foreach ( $cities as $value => $label ) : ?>
                            <option
                                value="<?php echo esc_attr( $value ); ?>"
                                <?php selected( $city, $value ); ?>
                            >
                                <?php echo esc_html( $label ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

            </div>

            <div class="moga-metabox__row">

                <div class="moga-metabox__field">
                    <label for="moga_district">
                        <?php esc_html_e( 'District / Area', 'moga-travel-core' ); ?>
                    </label>
                    <input
                        type="text"
                        id="moga_district"
                        name="moga_district"
                        value="<?php echo esc_attr( $district ); ?>"
                        placeholder="<?php esc_attr_e( 'e.g. Downtown, Zamalek', 'moga-travel-core' ); ?>"
                    >
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_postal_code">
                        <?php esc_html_e( 'Postal Code', 'moga-travel-core' ); ?>
                    </label>
                    <input
                        type="text"
                        id="moga_postal_code"
                        name="moga_postal_code"
                        value="<?php echo esc_attr( $postal_code ); ?>"
                        placeholder="<?php esc_attr_e( 'e.g. 12345', 'moga-travel-core' ); ?>"
                    >
                </div>

            </div>

            <div class="moga-metabox__row moga-metabox__row--full">
                <div class="moga-metabox__field">
                    <label for="moga_address">
                        <?php esc_html_e( 'Street Address', 'moga-travel-core' ); ?>
                    </label>
                    <input
                        type="text"
                        id="moga_address"
                        name="moga_address"
                        value="<?php echo esc_attr( $address ); ?>"
                        placeholder="<?php esc_attr_e( 'Full street address', 'moga-travel-core' ); ?>"
                    >
                </div>
            </div>

            <div class="moga-metabox__row">
                <div class="moga-metabox__field">
                    <label for="moga_latitude">
                        <?php esc_html_e( 'Latitude', 'moga-travel-core' ); ?>
                    </label>
                    <input
                        type="text"
                        id="moga_latitude"
                        name="moga_latitude"
                        value="<?php echo esc_attr( $latitude ); ?>"
                        placeholder="<?php esc_attr_e( 'e.g. 30.0444', 'moga-travel-core' ); ?>"
                    >
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_longitude">
                        <?php esc_html_e( 'Longitude', 'moga-travel-core' ); ?>
                    </label>
                    <input
                        type="text"
                        id="moga_longitude"
                        name="moga_longitude"
                        value="<?php echo esc_attr( $longitude ); ?>"
                        placeholder="<?php esc_attr_e( 'e.g. 31.2357', 'moga-travel-core' ); ?>"
                    >
                </div>
            </div>

        </div>
        <?php
    }

    /**
     * Render property contact meta box.
     *
     * @since  1.0.0
     * @param  WP_Post $post Current post object.
     * @return void
     */
    public static function render_property_contact( $post ) {
        wp_nonce_field( 'moga_property_contact_nonce', 'moga_property_contact_nonce' );

        $phone    = get_post_meta( $post->ID, '_moga_phone',    true );
        $whatsapp = get_post_meta( $post->ID, '_moga_whatsapp', true );
        $email    = get_post_meta( $post->ID, '_moga_email',    true );
        ?>
        <div class="moga-metabox">

            <div class="moga-metabox__row">

                <div class="moga-metabox__field">
                    <label for="moga_phone">
                        <?php esc_html_e( 'Phone Number', 'moga-travel-core' ); ?>
                    </label>
                    <input
                        type="tel"
                        id="moga_phone"
                        name="moga_phone"
                        value="<?php echo esc_attr( $phone ); ?>"
                        class="moga-phone-field"
                        placeholder="<?php esc_attr_e( 'Phone number', 'moga-travel-core' ); ?>"
                    >
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_whatsapp">
                        <?php esc_html_e( 'WhatsApp Number', 'moga-travel-core' ); ?>
                    </label>
                    <input
                        type="tel"
                        id="moga_whatsapp"
                        name="moga_whatsapp"
                        value="<?php echo esc_attr( $whatsapp ); ?>"
                        class="moga-phone-field"
                        placeholder="<?php esc_attr_e( 'WhatsApp number', 'moga-travel-core' ); ?>"
                    >
                </div>

            </div>

            <div class="moga-metabox__row">
                <div class="moga-metabox__field">
                    <label for="moga_email">
                        <?php esc_html_e( 'Email Address', 'moga-travel-core' ); ?>
                    </label>
                    <input
                        type="email"
                        id="moga_email"
                        name="moga_email"
                        value="<?php echo esc_attr( $email ); ?>"
                        placeholder="<?php esc_attr_e( 'contact@example.com', 'moga-travel-core' ); ?>"
                    >
                </div>
            </div>

        </div>
        <?php
    }

    /**
     * Render property details meta box.
     *
     * @since  1.0.0
     * @param  WP_Post $post Current post object.
     * @return void
     */
    public static function render_property_details( $post ) {
        wp_nonce_field( 'moga_property_details_nonce', 'moga_property_details_nonce' );

        $max_guests      = get_post_meta( $post->ID, '_moga_max_guests',      true ) ?: 1;
        $bedrooms        = get_post_meta( $post->ID, '_moga_bedrooms',        true ) ?: 1;
        $bathrooms       = get_post_meta( $post->ID, '_moga_bathrooms',       true ) ?: 1;
        $area            = get_post_meta( $post->ID, '_moga_area',            true );
        $floor           = get_post_meta( $post->ID, '_moga_floor',           true );
        $building_floors = get_post_meta( $post->ID, '_moga_building_floors', true );
        $year_built      = get_post_meta( $post->ID, '_moga_year_built',      true );
        $cancellation    = get_post_meta( $post->ID, '_moga_cancellation',    true ) ?: 'moderate';

        $cancellation_policies = Moga_CPT_Property::get_cancellation_policies();
        ?>
        <div class="moga-metabox">

            <div class="moga-metabox__row">

                <div class="moga-metabox__field">
                    <label for="moga_max_guests">
                        <?php esc_html_e( 'Max Guests', 'moga-travel-core' ); ?>
                    </label>
                    <input
                        type="number"
                        id="moga_max_guests"
                        name="moga_max_guests"
                        value="<?php echo esc_attr( $max_guests ); ?>"
                        min="1"
                        step="1"
                    >
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_bedrooms">
                        <?php esc_html_e( 'Bedrooms', 'moga-travel-core' ); ?>
                    </label>
                    <input
                        type="number"
                        id="moga_bedrooms"
                        name="moga_bedrooms"
                        value="<?php echo esc_attr( $bedrooms ); ?>"
                        min="0"
                        step="1"
                    >
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_bathrooms">
                        <?php esc_html_e( 'Bathrooms', 'moga-travel-core' ); ?>
                    </label>
                    <input
                        type="number"
                        id="moga_bathrooms"
                        name="moga_bathrooms"
                        value="<?php echo esc_attr( $bathrooms ); ?>"
                        min="0"
                        step="0.5"
                    >
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_area">
                        <?php esc_html_e( 'Area (m²)', 'moga-travel-core' ); ?>
                    </label>
                    <input
                        type="number"
                        id="moga_area"
                        name="moga_area"
                        value="<?php echo esc_attr( $area ); ?>"
                        min="0"
                        step="1"
                        placeholder="0"
                    >
                </div>

            </div>

            <div class="moga-metabox__row">

                <div class="moga-metabox__field">
                    <label for="moga_floor">
                        <?php esc_html_e( 'Floor Number', 'moga-travel-core' ); ?>
                    </label>
                    <input
                        type="number"
                        id="moga_floor"
                        name="moga_floor"
                        value="<?php echo esc_attr( $floor ); ?>"
                        min="0"
                        step="1"
                        placeholder="0"
                    >
                    <p class="moga-metabox__hint">
                        <?php esc_html_e( '0 = Ground floor', 'moga-travel-core' ); ?>
                    </p>
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_building_floors">
                        <?php esc_html_e( 'Total Building Floors', 'moga-travel-core' ); ?>
                    </label>
                    <input
                        type="number"
                        id="moga_building_floors"
                        name="moga_building_floors"
                        value="<?php echo esc_attr( $building_floors ); ?>"
                        min="1"
                        step="1"
                        placeholder="1"
                    >
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_year_built">
                        <?php esc_html_e( 'Year Built', 'moga-travel-core' ); ?>
                    </label>
                    <input
                        type="number"
                        id="moga_year_built"
                        name="moga_year_built"
                        value="<?php echo esc_attr( $year_built ); ?>"
                        min="1900"
                        max="<?php echo esc_attr( gmdate( 'Y' ) ); ?>"
                        step="1"
                        placeholder="<?php echo esc_attr( gmdate( 'Y' ) ); ?>"
                    >
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_cancellation">
                        <?php esc_html_e( 'Cancellation Policy', 'moga-travel-core' ); ?>
                    </label>
                    <select id="moga_cancellation" name="moga_cancellation">
                        <?php foreach ( $cancellation_policies as $key => $policy ) : ?>
                            <option
                                value="<?php echo esc_attr( $key ); ?>"
                                <?php selected( $cancellation, $key ); ?>
                            >
                                <?php echo esc_html( $policy['label'] ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ( isset( $cancellation_policies[ $cancellation ] ) ) : ?>
                        <p class="moga-metabox__hint">
                            <?php echo esc_html( $cancellation_policies[ $cancellation ]['desc'] ); ?>
                        </p>
                    <?php endif; ?>
                </div>

            </div>

        </div>
        <?php
    }

    /**
     * Render property amenities meta box.
     *
     * @since  1.0.0
     * @param  WP_Post $post Current post object.
     * @return void
     */
    public static function render_property_amenities( $post ) {
        wp_nonce_field( 'moga_property_amenities_nonce', 'moga_property_amenities_nonce' );

        $saved_amenities = moga_get_property_amenities( $post->ID );
        $all_amenities   = Moga_CPT_Property::get_amenities();
        $groups          = Moga_CPT_Property::get_amenity_groups();
        ?>
        <div class="moga-metabox moga-metabox--amenities">
            <?php foreach ( $groups as $group_key => $group_label ) : ?>

                <?php
                // Get amenities for this group.
                $group_amenities = array_filter(
                    $all_amenities,
                    function( $amenity ) use ( $group_key ) {
                        return $amenity['group'] === $group_key;
                    }
                );

                if ( empty( $group_amenities ) ) {
                    continue;
                }
                ?>

                <div class="moga-amenity-group">
                    <h4 class="moga-amenity-group__title">
                        <?php echo esc_html( $group_label ); ?>
                    </h4>
                    <div class="moga-amenity-group__items">
                        <?php foreach ( $group_amenities as $key => $amenity ) : ?>
                            <label class="moga-amenity-item">
                                <input
                                    type="checkbox"
                                    name="moga_amenities[]"
                                    value="<?php echo esc_attr( $key ); ?>"
                                    <?php checked( in_array( $key, $saved_amenities, true ) ); ?>
                                >
                                <span class="moga-amenity-item__label">
                                    <?php echo esc_html( $amenity['label'] ); ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Render property booking rules meta box.
     *
     * @since  1.0.0
     * @param  WP_Post $post Current post object.
     * @return void
     */
    public static function render_property_booking( $post ) {
        wp_nonce_field( 'moga_property_booking_nonce', 'moga_property_booking_nonce' );

        $min_stay      = get_post_meta( $post->ID, '_moga_min_stay',      true ) ?: 1;
        $max_stay      = get_post_meta( $post->ID, '_moga_max_stay',      true ) ?: 0;
        $checkin_time  = get_post_meta( $post->ID, '_moga_checkin_time',  true ) ?: '14:00';
        $checkout_time = get_post_meta( $post->ID, '_moga_checkout_time', true ) ?: '11:00';
        ?>
        <div class="moga-metabox">
            <div class="moga-metabox__row">

                <div class="moga-metabox__field">
                    <label for="moga_min_stay">
                        <?php esc_html_e( 'Minimum Stay (nights)', 'moga-travel-core' ); ?>
                    </label>
                    <input
                        type="number"
                        id="moga_min_stay"
                        name="moga_min_stay"
                        value="<?php echo esc_attr( $min_stay ); ?>"
                        min="1"
                        step="1"
                    >
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_max_stay">
                        <?php esc_html_e( 'Maximum Stay (nights)', 'moga-travel-core' ); ?>
                    </label>
                    <input
                        type="number"
                        id="moga_max_stay"
                        name="moga_max_stay"
                        value="<?php echo esc_attr( $max_stay ); ?>"
                        min="0"
                        step="1"
                    >
                    <p class="moga-metabox__hint">
                        <?php esc_html_e( '0 = No maximum limit', 'moga-travel-core' ); ?>
                    </p>
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_checkin_time">
                        <?php esc_html_e( 'Check-in Time', 'moga-travel-core' ); ?>
                    </label>
                    <input
                        type="time"
                        id="moga_checkin_time"
                        name="moga_checkin_time"
                        value="<?php echo esc_attr( $checkin_time ); ?>"
                    >
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_checkout_time">
                        <?php esc_html_e( 'Check-out Time', 'moga-travel-core' ); ?>
                    </label>
                    <input
                        type="time"
                        id="moga_checkout_time"
                        name="moga_checkout_time"
                        value="<?php echo esc_attr( $checkout_time ); ?>"
                    >
                </div>

            </div>
        </div>
        <?php
    }

    /**
     * Render property status meta box.
     *
     * @since  1.0.0
     * @param  WP_Post $post Current post object.
     * @return void
     */
    public static function render_property_status( $post ) {
        wp_nonce_field( 'moga_property_status_nonce', 'moga_property_status_nonce' );

        $featured        = get_post_meta( $post->ID, '_moga_featured',        true );
        $instant_booking = get_post_meta( $post->ID, '_moga_instant_booking', true );
        $active          = get_post_meta( $post->ID, '_moga_active',          true );

        // Default active to 1 on new posts.
        if ( '' === $active ) {
            $active = '1';
        }
        ?>
        <div class="moga-metabox">
            <div class="moga-metabox__switches">

                <label class="moga-switch">
                    <input
                        type="checkbox"
                        name="moga_active"
                        value="1"
                        <?php checked( '1', $active ); ?>
                    >
                    <span class="moga-switch__slider"></span>
                    <span class="moga-switch__label">
                        <?php esc_html_e( 'Active — visible to guests', 'moga-travel-core' ); ?>
                    </span>
                </label>

                <label class="moga-switch">
                    <input
                        type="checkbox"
                        name="moga_featured"
                        value="1"
                        <?php checked( '1', $featured ); ?>
                    >
                    <span class="moga-switch__slider"></span>
                    <span class="moga-switch__label">
                        <?php esc_html_e( 'Featured — shown on homepage', 'moga-travel-core' ); ?>
                    </span>
                </label>

                <label class="moga-switch">
                    <input
                        type="checkbox"
                        name="moga_instant_booking"
                        value="1"
                        <?php checked( '1', $instant_booking ); ?>
                    >
                    <span class="moga-switch__slider"></span>
                    <span class="moga-switch__label">
                        <?php esc_html_e( 'Instant Booking — no approval needed', 'moga-travel-core' ); ?>
                    </span>
                </label>

            </div>
        </div>
        <?php
    }


    // ============================================================
    // TOUR META BOXES — RENDER
    // ============================================================

    /**
     * Render tour pricing meta box.
     *
     * @since  1.0.0
     * @param  WP_Post $post Current post object.
     * @return void
     */
    public static function render_tour_pricing( $post ) {
        wp_nonce_field( 'moga_tour_pricing_nonce', 'moga_tour_pricing_nonce' );

        $price_adult  = get_post_meta( $post->ID, '_moga_price_per_person', true );
        $price_child  = get_post_meta( $post->ID, '_moga_price_child',      true );
        $price_infant = get_post_meta( $post->ID, '_moga_price_infant',     true );
        $group_disc   = get_post_meta( $post->ID, '_moga_price_group',      true );
        $currency     = get_post_meta( $post->ID, '_moga_currency',         true ) ?: 'USD';

        $currencies = moga_get_currencies();
        ?>
        <div class="moga-metabox">
            <div class="moga-metabox__row">

                <div class="moga-metabox__field">
                    <label for="moga_price_per_person">
                        <?php esc_html_e( 'Price Per Adult', 'moga-travel-core' ); ?>
                        <span class="required">*</span>
                    </label>
                    <input
                        type="number"
                        id="moga_price_per_person"
                        name="moga_price_per_person"
                        value="<?php echo esc_attr( $price_adult ); ?>"
                        min="0"
                        step="0.01"
                        placeholder="0.00"
                    >
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_price_child">
                        <?php esc_html_e( 'Price Per Child (under 12)', 'moga-travel-core' ); ?>
                    </label>
                    <input
                        type="number"
                        id="moga_price_child"
                        name="moga_price_child"
                        value="<?php echo esc_attr( $price_child ); ?>"
                        min="0"
                        step="0.01"
                        placeholder="0.00"
                    >
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_price_infant">
                        <?php esc_html_e( 'Price Per Infant (under 2)', 'moga-travel-core' ); ?>
                    </label>
                    <input
                        type="number"
                        id="moga_price_infant"
                        name="moga_price_infant"
                        value="<?php echo esc_attr( $price_infant ); ?>"
                        min="0"
                        step="0.01"
                        placeholder="0.00"
                    >
                    <p class="moga-metabox__hint">
                        <?php esc_html_e( '0 = Infants travel free', 'moga-travel-core' ); ?>
                    </p>
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_price_group">
                        <?php esc_html_e( 'Group Discount %', 'moga-travel-core' ); ?>
                    </label>
                    <input
                        type="number"
                        id="moga_price_group"
                        name="moga_price_group"
                        value="<?php echo esc_attr( $group_disc ); ?>"
                        min="0"
                        max="100"
                        step="1"
                        placeholder="0"
                    >
                </div>

            </div>

            <div class="moga-metabox__row">
                <div class="moga-metabox__field">
                    <label for="moga_tour_currency">
                        <?php esc_html_e( 'Currency', 'moga-travel-core' ); ?>
                    </label>
                    <select id="moga_tour_currency" name="moga_currency">
                        <?php foreach ( $currencies as $code => $label ) : ?>
                            <option
                                value="<?php echo esc_attr( $code ); ?>"
                                <?php selected( $currency, $code ); ?>
                            >
                                <?php echo esc_html( $label ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

        </div>
        <?php
    }

    /**
     * Render tour schedule meta box.
     *
     * @since  1.0.0
     * @param  WP_Post $post Current post object.
     * @return void
     */
    public static function render_tour_schedule( $post ) {
        wp_nonce_field( 'moga_tour_schedule_nonce', 'moga_tour_schedule_nonce' );

        $duration_days    = get_post_meta( $post->ID, '_moga_duration_days',    true ) ?: 1;
        $duration_nights  = get_post_meta( $post->ID, '_moga_duration_nights',  true ) ?: 0;
        $departure_time   = get_post_meta( $post->ID, '_moga_departure_time',   true ) ?: '08:00';
        $return_time      = get_post_meta( $post->ID, '_moga_return_time',      true ) ?: '18:00';
        $available_days   = get_post_meta( $post->ID, '_moga_available_days',   true );
        $available_days   = $available_days ? json_decode( $available_days, true ) : array();

        $weekdays = Moga_CPT_Tour::get_weekdays();
        ?>
        <div class="moga-metabox">

            <div class="moga-metabox__row">

                <div class="moga-metabox__field">
                    <label for="moga_duration_days">
                        <?php esc_html_e( 'Duration (Days)', 'moga-travel-core' ); ?>
                    </label>
                    <input
                        type="number"
                        id="moga_duration_days"
                        name="moga_duration_days"
                        value="<?php echo esc_attr( $duration_days ); ?>"
                        min="1"
                        step="1"
                    >
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_duration_nights">
                        <?php esc_html_e( 'Duration (Nights)', 'moga-travel-core' ); ?>
                    </label>
                    <input
                        type="number"
                        id="moga_duration_nights"
                        name="moga_duration_nights"
                        value="<?php echo esc_attr( $duration_nights ); ?>"
                        min="0"
                        step="1"
                    >
                    <p class="moga-metabox__hint">
                        <?php esc_html_e( '0 = Day trip (no overnight)', 'moga-travel-core' ); ?>
                    </p>
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_departure_time">
                        <?php esc_html_e( 'Departure Time', 'moga-travel-core' ); ?>
                    </label>
                    <input
                        type="time"
                        id="moga_departure_time"
                        name="moga_departure_time"
                        value="<?php echo esc_attr( $departure_time ); ?>"
                    >
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_return_time">
                        <?php esc_html_e( 'Return Time', 'moga-travel-core' ); ?>
                    </label>
                    <input
                        type="time"
                        id="moga_return_time"
                        name="moga_return_time"
                        value="<?php echo esc_attr( $return_time ); ?>"
                    >
                </div>

            </div>

            <div class="moga-metabox__row moga-metabox__row--full">
                <div class="moga-metabox__field">
                    <label><?php esc_html_e( 'Available Days', 'moga-travel-core' ); ?></label>
                    <div class="moga-weekdays">
                        <?php foreach ( $weekdays as $day_num => $day_label ) : ?>
                            <label class="moga-weekday">
                                <input
                                    type="checkbox"
                                    name="moga_available_days[]"
                                    value="<?php echo esc_attr( $day_num ); ?>"
                                    <?php checked( in_array( (string) $day_num, array_map( 'strval', $available_days ), true ) ); ?>
                                >
                                <span><?php echo esc_html( substr( $day_label, 0, 3 ) ); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <p class="moga-metabox__hint">
                        <?php esc_html_e( 'Days this tour departs. Leave all unchecked for custom dates only.', 'moga-travel-core' ); ?>
                    </p>
                </div>
            </div>

        </div>
        <?php
    }

    /**
     * Render tour location meta box.
     *
     * @since  1.0.0
     * @param  WP_Post $post Current post object.
     * @return void
     */
    public static function render_tour_location( $post ) {
        wp_nonce_field( 'moga_tour_location_nonce', 'moga_tour_location_nonce' );

        $dep_country  = get_post_meta( $post->ID, '_moga_departure_country',   true );
        $dep_city     = get_post_meta( $post->ID, '_moga_departure_city',      true );
        $dep_point    = get_post_meta( $post->ID, '_moga_departure_point',     true );
        $dest_country = get_post_meta( $post->ID, '_moga_destination_country', true );
        $dest_city    = get_post_meta( $post->ID, '_moga_destination_city',    true );

        $countries  = moga_get_countries_dropdown();
        $dep_cities = $dep_country
            ? moga_get_cities_dropdown( $dep_country )
            : array( '' => __( '— Select Country First —', 'moga-travel-core' ) );
        $dest_cities = $dest_country
            ? moga_get_cities_dropdown( $dest_country )
            : array( '' => __( '— Select Country First —', 'moga-travel-core' ) );
        ?>
        <div class="moga-metabox">

            <h4 class="moga-metabox__section-title">
                <?php esc_html_e( 'Departure', 'moga-travel-core' ); ?>
            </h4>
            <div class="moga-metabox__row">

                <div class="moga-metabox__field">
                    <label for="moga_departure_country">
                        <?php esc_html_e( 'Departure Country', 'moga-travel-core' ); ?>
                    </label>
                    <select
                        id="moga_departure_country"
                        name="moga_departure_country"
                        class="moga-country-select"
                        data-target="moga_departure_city"
                    >
                        <?php foreach ( $countries as $code => $label ) : ?>
                            <option
                                value="<?php echo esc_attr( $code ); ?>"
                                <?php selected( $dep_country, $code ); ?>
                            >
                                <?php echo esc_html( $label ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_departure_city">
                        <?php esc_html_e( 'Departure City', 'moga-travel-core' ); ?>
                    </label>
                    <select
                        id="moga_departure_city"
                        name="moga_departure_city"
                        class="moga-city-select"
                    >
                        <?php foreach ( $dep_cities as $value => $label ) : ?>
                            <option
                                value="<?php echo esc_attr( $value ); ?>"
                                <?php selected( $dep_city, $value ); ?>
                            >
                                <?php echo esc_html( $label ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="moga-metabox__field moga-metabox__field--wide">
                    <label for="moga_departure_point">
                        <?php esc_html_e( 'Exact Departure Point', 'moga-travel-core' ); ?>
                    </label>
                    <input
                        type="text"
                        id="moga_departure_point"
                        name="moga_departure_point"
                        value="<?php echo esc_attr( $dep_point ); ?>"
                        placeholder="<?php esc_attr_e( 'e.g. Cairo International Airport, Terminal 2', 'moga-travel-core' ); ?>"
                    >
                </div>

            </div>

            <h4 class="moga-metabox__section-title">
                <?php esc_html_e( 'Destination', 'moga-travel-core' ); ?>
            </h4>
            <div class="moga-metabox__row">

                <div class="moga-metabox__field">
                    <label for="moga_destination_country">
                        <?php esc_html_e( 'Destination Country', 'moga-travel-core' ); ?>
                    </label>
                    <select
                        id="moga_destination_country"
                        name="moga_destination_country"
                        class="moga-country-select"
                        data-target="moga_destination_city"
                    >
                        <?php foreach ( $countries as $code => $label ) : ?>
                            <option
                                value="<?php echo esc_attr( $code ); ?>"
                                <?php selected( $dest_country, $code ); ?>
                            >
                                <?php echo esc_html( $label ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_destination_city">
                        <?php esc_html_e( 'Destination City', 'moga-travel-core' ); ?>
                    </label>
                    <select
                        id="moga_destination_city"
                        name="moga_destination_city"
                        class="moga-city-select"
                    >
                        <?php foreach ( $dest_cities as $value => $label ) : ?>
                            <option
                                value="<?php echo esc_attr( $value ); ?>"
                                <?php selected( $dest_city, $value ); ?>
                            >
                                <?php echo esc_html( $label ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

            </div>

        </div>
        <?php
    }

    /**
     * Render tour contact meta box.
     *
     * @since  1.0.0
     * @param  WP_Post $post Current post object.
     * @return void
     */
    public static function render_tour_contact( $post ) {
        wp_nonce_field( 'moga_tour_contact_nonce', 'moga_tour_contact_nonce' );

        $organizer = get_post_meta( $post->ID, '_moga_organizer_name', true );
        $phone     = get_post_meta( $post->ID, '_moga_phone',          true );
        $whatsapp  = get_post_meta( $post->ID, '_moga_whatsapp',       true );
        $email     = get_post_meta( $post->ID, '_moga_email',          true );
        ?>
        <div class="moga-metabox">
            <div class="moga-metabox__row">

                <div class="moga-metabox__field">
                    <label for="moga_organizer_name">
                        <?php esc_html_e( 'Organizer Name', 'moga-travel-core' ); ?>
                    </label>
                    <input
                        type="text"
                        id="moga_organizer_name"
                        name="moga_organizer_name"
                        value="<?php echo esc_attr( $organizer ); ?>"
                        placeholder="<?php esc_attr_e( 'Tour organizer or company name', 'moga-travel-core' ); ?>"
                    >
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_tour_phone">
                        <?php esc_html_e( 'Phone Number', 'moga-travel-core' ); ?>
                    </label>
                    <input
                        type="tel"
                        id="moga_tour_phone"
                        name="moga_phone"
                        value="<?php echo esc_attr( $phone ); ?>"
                        class="moga-phone-field"
                        placeholder="<?php esc_attr_e( 'Phone number', 'moga-travel-core' ); ?>"
                    >
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_tour_whatsapp">
                        <?php esc_html_e( 'WhatsApp Number', 'moga-travel-core' ); ?>
                    </label>
                    <input
                        type="tel"
                        id="moga_tour_whatsapp"
                        name="moga_whatsapp"
                        value="<?php echo esc_attr( $whatsapp ); ?>"
                        class="moga-phone-field"
                        placeholder="<?php esc_attr_e( 'WhatsApp number', 'moga-travel-core' ); ?>"
                    >
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_tour_email">
                        <?php esc_html_e( 'Email Address', 'moga-travel-core' ); ?>
                    </label>
                    <input
                        type="email"
                        id="moga_tour_email"
                        name="moga_email"
                        value="<?php echo esc_attr( $email ); ?>"
                        placeholder="<?php esc_attr_e( 'contact@example.com', 'moga-travel-core' ); ?>"
                    >
                </div>

            </div>
        </div>
        <?php
    }

    /**
     * Render tour details meta box.
     *
     * @since  1.0.0
     * @param  WP_Post $post Current post object.
     * @return void
     */
    public static function render_tour_details( $post ) {
        wp_nonce_field( 'moga_tour_details_nonce', 'moga_tour_details_nonce' );

        $max_participants = get_post_meta( $post->ID, '_moga_max_participants', true ) ?: 20;
        $min_participants = get_post_meta( $post->ID, '_moga_min_participants', true ) ?: 1;
        $difficulty       = get_post_meta( $post->ID, '_moga_difficulty',       true ) ?: 'easy';
        $tour_type        = get_post_meta( $post->ID, '_moga_tour_type',        true ) ?: 'group';
        $language         = get_post_meta( $post->ID, '_moga_language',         true ) ?: 'Arabic';
        $guide_included   = get_post_meta( $post->ID, '_moga_guide_included',   true );

        $difficulty_levels = Moga_CPT_Tour::get_difficulty_levels();
        $tour_types        = Moga_CPT_Tour::get_tour_types();
        ?>
        <div class="moga-metabox">
            <div class="moga-metabox__row">

                <div class="moga-metabox__field">
                    <label for="moga_max_participants">
                        <?php esc_html_e( 'Max Participants', 'moga-travel-core' ); ?>
                    </label>
                    <input
                        type="number"
                        id="moga_max_participants"
                        name="moga_max_participants"
                        value="<?php echo esc_attr( $max_participants ); ?>"
                        min="1"
                        step="1"
                    >
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_min_participants">
                        <?php esc_html_e( 'Min Participants', 'moga-travel-core' ); ?>
                    </label>
                    <input
                        type="number"
                        id="moga_min_participants"
                        name="moga_min_participants"
                        value="<?php echo esc_attr( $min_participants ); ?>"
                        min="1"
                        step="1"
                    >
                    <p class="moga-metabox__hint">
                        <?php esc_html_e( 'Tour runs only if this number is reached.', 'moga-travel-core' ); ?>
                    </p>
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_difficulty">
                        <?php esc_html_e( 'Difficulty Level', 'moga-travel-core' ); ?>
                    </label>
                    <select id="moga_difficulty" name="moga_difficulty">
                        <?php foreach ( $difficulty_levels as $key => $level ) : ?>
                            <option
                                value="<?php echo esc_attr( $key ); ?>"
                                <?php selected( $difficulty, $key ); ?>
                            >
                                <?php echo esc_html( $level['label'] ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_tour_type">
                        <?php esc_html_e( 'Tour Type', 'moga-travel-core' ); ?>
                    </label>
                    <select id="moga_tour_type" name="moga_tour_type">
                        <?php foreach ( $tour_types as $key => $type ) : ?>
                            <option
                                value="<?php echo esc_attr( $key ); ?>"
                                <?php selected( $tour_type, $key ); ?>
                            >
                                <?php echo esc_html( $type['label'] ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

            </div>

            <div class="moga-metabox__row">

                <div class="moga-metabox__field">
                    <label for="moga_language">
                        <?php esc_html_e( 'Tour Language', 'moga-travel-core' ); ?>
                    </label>
                    <input
                        type="text"
                        id="moga_language"
                        name="moga_language"
                        value="<?php echo esc_attr( $language ); ?>"
                        placeholder="<?php esc_attr_e( 'e.g. Arabic, English', 'moga-travel-core' ); ?>"
                    >
                </div>

                <div class="moga-metabox__field">
                    <label class="moga-switch">
                        <input
                            type="checkbox"
                            name="moga_guide_included"
                            value="1"
                            <?php checked( '1', $guide_included ); ?>
                        >
                        <span class="moga-switch__slider"></span>
                        <span class="moga-switch__label">
                            <?php esc_html_e( 'Guide Included', 'moga-travel-core' ); ?>
                        </span>
                    </label>
                </div>

            </div>
        </div>
        <?php
    }

    /**
     * Render tour includes / excludes meta box.
     *
     * @since  1.0.0
     * @param  WP_Post $post Current post object.
     * @return void
     */
    public static function render_tour_includes( $post ) {
        wp_nonce_field( 'moga_tour_includes_nonce', 'moga_tour_includes_nonce' );

        $saved_includes = get_post_meta( $post->ID, '_moga_includes', true );
        $saved_excludes = get_post_meta( $post->ID, '_moga_excludes', true );
        $saved_includes = $saved_includes ? json_decode( $saved_includes, true ) : array();
        $saved_excludes = $saved_excludes ? json_decode( $saved_excludes, true ) : array();

        $includes_options = Moga_CPT_Tour::get_includes_options();
        $excludes_options = Moga_CPT_Tour::get_excludes_options();
        ?>
        <div class="moga-metabox moga-metabox--two-col">

            <div class="moga-metabox__col">
                <h4 class="moga-metabox__section-title moga-metabox__section-title--green">
                    ✅ <?php esc_html_e( 'Included', 'moga-travel-core' ); ?>
                </h4>
                <div class="moga-checklist">
                    <?php foreach ( $includes_options as $key => $label ) : ?>
                        <label class="moga-checklist__item">
                            <input
                                type="checkbox"
                                name="moga_includes[]"
                                value="<?php echo esc_attr( $key ); ?>"
                                <?php checked( in_array( $key, $saved_includes, true ) ); ?>
                            >
                            <?php echo esc_html( $label ); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="moga-metabox__col">
                <h4 class="moga-metabox__section-title moga-metabox__section-title--red">
                    ❌ <?php esc_html_e( 'Not Included', 'moga-travel-core' ); ?>
                </h4>
                <div class="moga-checklist">
                    <?php foreach ( $excludes_options as $key => $label ) : ?>
                        <label class="moga-checklist__item">
                            <input
                                type="checkbox"
                                name="moga_excludes[]"
                                value="<?php echo esc_attr( $key ); ?>"
                                <?php checked( in_array( $key, $saved_excludes, true ) ); ?>
                            >
                            <?php echo esc_html( $label ); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
        <?php
    }

    /**
     * Render tour bus & seats meta box.
     *
     * @since  1.0.0
     * @param  WP_Post $post Current post object.
     * @return void
     */
    public static function render_tour_bus( $post ) {
        wp_nonce_field( 'moga_tour_bus_nonce', 'moga_tour_bus_nonce' );

        $bus_id          = get_post_meta( $post->ID, '_moga_bus_id',          true );
        $seats_total     = get_post_meta( $post->ID, '_moga_seats_total',     true );
        $seats_available = get_post_meta( $post->ID, '_moga_seats_available', true );

        $buses = Moga_CPT_Bus::get_available_buses();
        ?>
        <div class="moga-metabox">
            <div class="moga-metabox__row">

                <div class="moga-metabox__field">
                    <label for="moga_bus_id">
                        <?php esc_html_e( 'Assign Bus', 'moga-travel-core' ); ?>
                    </label>
                    <select id="moga_bus_id" name="moga_bus_id">
                        <option value="">
                            <?php esc_html_e( '— No Bus Assigned —', 'moga-travel-core' ); ?>
                        </option>
                        <?php foreach ( $buses as $id => $label ) : ?>
                            <option
                                value="<?php echo esc_attr( $id ); ?>"
                                <?php selected( $bus_id, $id ); ?>
                            >
                                <?php echo esc_html( $label ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ( empty( $buses ) ) : ?>
                        <p class="moga-metabox__hint moga-metabox__hint--warning">
                            <?php
                            printf(
                                /* translators: %s: link to add new bus */
                                esc_html__( 'No buses available. %s first.', 'moga-travel-core' ),
                                '<a href="' . esc_url( admin_url( 'post-new.php?post_type=moga_bus' ) ) . '">'
                                . esc_html__( 'Add a bus', 'moga-travel-core' )
                                . '</a>'
                            );
                            ?>
                        </p>
                    <?php endif; ?>
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_seats_total">
                        <?php esc_html_e( 'Total Seats', 'moga-travel-core' ); ?>
                    </label>
                    <input
                        type="number"
                        id="moga_seats_total"
                        name="moga_seats_total"
                        value="<?php echo esc_attr( $seats_total ); ?>"
                        min="0"
                        step="1"
                        placeholder="0"
                        readonly
                    >
                    <p class="moga-metabox__hint">
                        <?php esc_html_e( 'Auto-filled from bus configuration.', 'moga-travel-core' ); ?>
                    </p>
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_seats_available">
                        <?php esc_html_e( 'Available Seats', 'moga-travel-core' ); ?>
                    </label>
                    <input
                        type="number"
                        id="moga_seats_available"
                        name="moga_seats_available"
                        value="<?php echo esc_attr( $seats_available ); ?>"
                        min="0"
                        step="1"
                        placeholder="0"
                    >
                </div>

            </div>
        </div>
        <?php
    }

    /**
     * Render tour status meta box.
     *
     * @since  1.0.0
     * @param  WP_Post $post Current post object.
     * @return void
     */
    public static function render_tour_status( $post ) {
        wp_nonce_field( 'moga_tour_status_nonce', 'moga_tour_status_nonce' );

        $featured        = get_post_meta( $post->ID, '_moga_featured',        true );
        $instant_booking = get_post_meta( $post->ID, '_moga_instant_booking', true );
        $active          = get_post_meta( $post->ID, '_moga_active',          true );

        if ( '' === $active ) {
            $active = '1';
        }
        ?>
        <div class="moga-metabox">
            <div class="moga-metabox__switches">

                <label class="moga-switch">
                    <input
                        type="checkbox"
                        name="moga_active"
                        value="1"
                        <?php checked( '1', $active ); ?>
                    >
                    <span class="moga-switch__slider"></span>
                    <span class="moga-switch__label">
                        <?php esc_html_e( 'Active — visible to guests', 'moga-travel-core' ); ?>
                    </span>
                </label>

                <label class="moga-switch">
                    <input
                        type="checkbox"
                        name="moga_featured"
                        value="1"
                        <?php checked( '1', $featured ); ?>
                    >
                    <span class="moga-switch__slider"></span>
                    <span class="moga-switch__label">
                        <?php esc_html_e( 'Featured — shown on homepage', 'moga-travel-core' ); ?>
                    </span>
                </label>

                <label class="moga-switch">
                    <input
                        type="checkbox"
                        name="moga_instant_booking"
                        value="1"
                        <?php checked( '1', $instant_booking ); ?>
                    >
                    <span class="moga-switch__slider"></span>
                    <span class="moga-switch__label">
                        <?php esc_html_e( 'Instant Booking — no approval needed', 'moga-travel-core' ); ?>
                    </span>
                </label>

            </div>
        </div>
        <?php
    }


    // ============================================================
    // SAVE META BOXES
    // ============================================================

    /**
     * Save all meta box data when a post is saved.
     *
     * @since  1.0.0
     * @param  int     $post_id Post ID being saved.
     * @param  WP_Post $post    Post object being saved.
     * @return void
     */
    public static function save_meta_boxes( $post_id, $post ) {

        // Skip autosaves and revisions.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }

        // Save based on post type.
        if ( 'moga_property' === $post->post_type ) {
            self::save_property_meta( $post_id );
        } elseif ( 'moga_tour' === $post->post_type ) {
            self::save_tour_meta( $post_id );
        }
    }

    /**
     * Save property meta fields.
     *
     * @since  1.0.0
     * @param  int $post_id Property post ID.
     * @return void
     */
    private static function save_property_meta( $post_id ) {

        // ---- Pricing ----
        if ( isset( $_POST['moga_property_pricing_nonce'] )
            && wp_verify_nonce(
                sanitize_text_field( wp_unslash( $_POST['moga_property_pricing_nonce'] ) ),
                'moga_property_pricing_nonce'
            )
        ) {
            update_post_meta( $post_id, '_moga_price_per_night',
                isset( $_POST['moga_price_per_night'] ) ? floatval( $_POST['moga_price_per_night'] ) : 0 );
            update_post_meta( $post_id, '_moga_price_weekend',
                isset( $_POST['moga_price_weekend'] ) ? floatval( $_POST['moga_price_weekend'] ) : 0 );
            update_post_meta( $post_id, '_moga_price_discount',
                isset( $_POST['moga_price_discount'] ) ? floatval( $_POST['moga_price_discount'] ) : 0 );
            update_post_meta( $post_id, '_moga_currency',
                isset( $_POST['moga_currency'] ) ? sanitize_text_field( wp_unslash( $_POST['moga_currency'] ) ) : 'USD' );
        }

        // ---- Location ----
        if ( isset( $_POST['moga_property_location_nonce'] )
            && wp_verify_nonce(
                sanitize_text_field( wp_unslash( $_POST['moga_property_location_nonce'] ) ),
                'moga_property_location_nonce'
            )
        ) {
            $country = isset( $_POST['moga_country'] )
                ? sanitize_text_field( wp_unslash( $_POST['moga_country'] ) ) : '';
            $city    = isset( $_POST['moga_city'] )
                ? sanitize_text_field( wp_unslash( $_POST['moga_city'] ) ) : '';

            update_post_meta( $post_id, '_moga_country',     $country );
            update_post_meta( $post_id, '_moga_city',        $city );
            update_post_meta( $post_id, '_moga_district',
                isset( $_POST['moga_district'] ) ? sanitize_text_field( wp_unslash( $_POST['moga_district'] ) ) : '' );
            update_post_meta( $post_id, '_moga_address',
                isset( $_POST['moga_address'] ) ? sanitize_text_field( wp_unslash( $_POST['moga_address'] ) ) : '' );
            update_post_meta( $post_id, '_moga_postal_code',
                isset( $_POST['moga_postal_code'] ) ? sanitize_text_field( wp_unslash( $_POST['moga_postal_code'] ) ) : '' );
            update_post_meta( $post_id, '_moga_latitude',
                isset( $_POST['moga_latitude'] ) ? sanitize_text_field( wp_unslash( $_POST['moga_latitude'] ) ) : '' );
            update_post_meta( $post_id, '_moga_longitude',
                isset( $_POST['moga_longitude'] ) ? sanitize_text_field( wp_unslash( $_POST['moga_longitude'] ) ) : '' );

            // Auto-sync country name.
            if ( $country ) {
                $country_data = moga_get_country( $country );
                if ( $country_data ) {
                    update_post_meta( $post_id, '_moga_country_name', $country_data['name'] );
                }
            }

            // Auto-sync city to taxonomy in background.
            if ( $country && $city ) {
                moga_sync_city_to_taxonomy( $country, $city );
            }
        }

        // ---- Contact ----
        if ( isset( $_POST['moga_property_contact_nonce'] )
            && wp_verify_nonce(
                sanitize_text_field( wp_unslash( $_POST['moga_property_contact_nonce'] ) ),
                'moga_property_contact_nonce'
            )
        ) {
            update_post_meta( $post_id, '_moga_phone',
                isset( $_POST['moga_phone'] ) ? moga_sanitize_phone( wp_unslash( $_POST['moga_phone'] ) ) : '' );
            update_post_meta( $post_id, '_moga_whatsapp',
                isset( $_POST['moga_whatsapp'] ) ? moga_sanitize_phone( wp_unslash( $_POST['moga_whatsapp'] ) ) : '' );
            update_post_meta( $post_id, '_moga_email',
                isset( $_POST['moga_email'] ) ? sanitize_email( wp_unslash( $_POST['moga_email'] ) ) : '' );
        }

        // ---- Details ----
        if ( isset( $_POST['moga_property_details_nonce'] )
            && wp_verify_nonce(
                sanitize_text_field( wp_unslash( $_POST['moga_property_details_nonce'] ) ),
                'moga_property_details_nonce'
            )
        ) {
            update_post_meta( $post_id, '_moga_max_guests',
                isset( $_POST['moga_max_guests'] ) ? absint( $_POST['moga_max_guests'] ) : 1 );
            update_post_meta( $post_id, '_moga_bedrooms',
                isset( $_POST['moga_bedrooms'] ) ? absint( $_POST['moga_bedrooms'] ) : 1 );
            update_post_meta( $post_id, '_moga_bathrooms',
                isset( $_POST['moga_bathrooms'] ) ? floatval( $_POST['moga_bathrooms'] ) : 1 );
            update_post_meta( $post_id, '_moga_area',
                isset( $_POST['moga_area'] ) ? floatval( $_POST['moga_area'] ) : 0 );
            update_post_meta( $post_id, '_moga_floor',
                isset( $_POST['moga_floor'] ) ? absint( $_POST['moga_floor'] ) : 0 );
            update_post_meta( $post_id, '_moga_building_floors',
                isset( $_POST['moga_building_floors'] ) ? absint( $_POST['moga_building_floors'] ) : 1 );
            update_post_meta( $post_id, '_moga_year_built',
                isset( $_POST['moga_year_built'] ) ? absint( $_POST['moga_year_built'] ) : 0 );
            update_post_meta( $post_id, '_moga_cancellation',
                isset( $_POST['moga_cancellation'] ) ? sanitize_text_field( wp_unslash( $_POST['moga_cancellation'] ) ) : 'moderate' );
        }

        // ---- Amenities ----
        if ( isset( $_POST['moga_property_amenities_nonce'] )
            && wp_verify_nonce(
                sanitize_text_field( wp_unslash( $_POST['moga_property_amenities_nonce'] ) ),
                'moga_property_amenities_nonce'
            )
        ) {
            $amenities = isset( $_POST['moga_amenities'] ) && is_array( $_POST['moga_amenities'] )
                ? array_map( 'sanitize_text_field', wp_unslash( $_POST['moga_amenities'] ) )
                : array();
            update_post_meta( $post_id, '_moga_amenities', wp_json_encode( $amenities ) );
        }

        // ---- Booking Rules ----
        if ( isset( $_POST['moga_property_booking_nonce'] )
            && wp_verify_nonce(
                sanitize_text_field( wp_unslash( $_POST['moga_property_booking_nonce'] ) ),
                'moga_property_booking_nonce'
            )
        ) {
            update_post_meta( $post_id, '_moga_min_stay',
                isset( $_POST['moga_min_stay'] ) ? absint( $_POST['moga_min_stay'] ) : 1 );
            update_post_meta( $post_id, '_moga_max_stay',
                isset( $_POST['moga_max_stay'] ) ? absint( $_POST['moga_max_stay'] ) : 0 );
            update_post_meta( $post_id, '_moga_checkin_time',
                isset( $_POST['moga_checkin_time'] ) ? sanitize_text_field( wp_unslash( $_POST['moga_checkin_time'] ) ) : '14:00' );
            update_post_meta( $post_id, '_moga_checkout_time',
                isset( $_POST['moga_checkout_time'] ) ? sanitize_text_field( wp_unslash( $_POST['moga_checkout_time'] ) ) : '11:00' );
        }

        // ---- Status ----
        if ( isset( $_POST['moga_property_status_nonce'] )
            && wp_verify_nonce(
                sanitize_text_field( wp_unslash( $_POST['moga_property_status_nonce'] ) ),
                'moga_property_status_nonce'
            )
        ) {
            update_post_meta( $post_id, '_moga_active',
                isset( $_POST['moga_active'] ) ? '1' : '0' );
            update_post_meta( $post_id, '_moga_featured',
                isset( $_POST['moga_featured'] ) ? '1' : '0' );
            update_post_meta( $post_id, '_moga_instant_booking',
                isset( $_POST['moga_instant_booking'] ) ? '1' : '0' );
        }
    }

    /**
     * Save tour meta fields.
     *
     * @since  1.0.0
     * @param  int $post_id Tour post ID.
     * @return void
     */
    private static function save_tour_meta( $post_id ) {

        // ---- Pricing ----
        if ( isset( $_POST['moga_tour_pricing_nonce'] )
            && wp_verify_nonce(
                sanitize_text_field( wp_unslash( $_POST['moga_tour_pricing_nonce'] ) ),
                'moga_tour_pricing_nonce'
            )
        ) {
            update_post_meta( $post_id, '_moga_price_per_person',
                isset( $_POST['moga_price_per_person'] ) ? floatval( $_POST['moga_price_per_person'] ) : 0 );
            update_post_meta( $post_id, '_moga_price_child',
                isset( $_POST['moga_price_child'] ) ? floatval( $_POST['moga_price_child'] ) : 0 );
            update_post_meta( $post_id, '_moga_price_infant',
                isset( $_POST['moga_price_infant'] ) ? floatval( $_POST['moga_price_infant'] ) : 0 );
            update_post_meta( $post_id, '_moga_price_group',
                isset( $_POST['moga_price_group'] ) ? floatval( $_POST['moga_price_group'] ) : 0 );
            update_post_meta( $post_id, '_moga_currency',
                isset( $_POST['moga_currency'] ) ? sanitize_text_field( wp_unslash( $_POST['moga_currency'] ) ) : 'USD' );
        }

        // ---- Schedule ----
        if ( isset( $_POST['moga_tour_schedule_nonce'] )
            && wp_verify_nonce(
                sanitize_text_field( wp_unslash( $_POST['moga_tour_schedule_nonce'] ) ),
                'moga_tour_schedule_nonce'
            )
        ) {
            update_post_meta( $post_id, '_moga_duration_days',
                isset( $_POST['moga_duration_days'] ) ? absint( $_POST['moga_duration_days'] ) : 1 );
            update_post_meta( $post_id, '_moga_duration_nights',
                isset( $_POST['moga_duration_nights'] ) ? absint( $_POST['moga_duration_nights'] ) : 0 );
            update_post_meta( $post_id, '_moga_departure_time',
                isset( $_POST['moga_departure_time'] ) ? sanitize_text_field( wp_unslash( $_POST['moga_departure_time'] ) ) : '08:00' );
            update_post_meta( $post_id, '_moga_return_time',
                isset( $_POST['moga_return_time'] ) ? sanitize_text_field( wp_unslash( $_POST['moga_return_time'] ) ) : '18:00' );

            $available_days = isset( $_POST['moga_available_days'] ) && is_array( $_POST['moga_available_days'] )
                ? array_map( 'absint', $_POST['moga_available_days'] )
                : array();
            update_post_meta( $post_id, '_moga_available_days', wp_json_encode( $available_days ) );
        }

        // ---- Location ----
        if ( isset( $_POST['moga_tour_location_nonce'] )
            && wp_verify_nonce(
                sanitize_text_field( wp_unslash( $_POST['moga_tour_location_nonce'] ) ),
                'moga_tour_location_nonce'
            )
        ) {
            $dep_country  = isset( $_POST['moga_departure_country'] )
                ? sanitize_text_field( wp_unslash( $_POST['moga_departure_country'] ) ) : '';
            $dep_city     = isset( $_POST['moga_departure_city'] )
                ? sanitize_text_field( wp_unslash( $_POST['moga_departure_city'] ) ) : '';
            $dest_country = isset( $_POST['moga_destination_country'] )
                ? sanitize_text_field( wp_unslash( $_POST['moga_destination_country'] ) ) : '';
            $dest_city    = isset( $_POST['moga_destination_city'] )
                ? sanitize_text_field( wp_unslash( $_POST['moga_destination_city'] ) ) : '';

            update_post_meta( $post_id, '_moga_departure_country',   $dep_country );
            update_post_meta( $post_id, '_moga_departure_city',      $dep_city );
            update_post_meta( $post_id, '_moga_destination_country', $dest_country );
            update_post_meta( $post_id, '_moga_destination_city',    $dest_city );
            update_post_meta( $post_id, '_moga_departure_point',
                isset( $_POST['moga_departure_point'] ) ? sanitize_text_field( wp_unslash( $_POST['moga_departure_point'] ) ) : '' );

            // Auto-sync both departure and destination cities to taxonomy.
            if ( $dep_country && $dep_city ) {
                moga_sync_city_to_taxonomy( $dep_country, $dep_city );
            }
            if ( $dest_country && $dest_city ) {
                moga_sync_city_to_taxonomy( $dest_country, $dest_city );
            }
        }

        // ---- Contact ----
        if ( isset( $_POST['moga_tour_contact_nonce'] )
            && wp_verify_nonce(
                sanitize_text_field( wp_unslash( $_POST['moga_tour_contact_nonce'] ) ),
                'moga_tour_contact_nonce'
            )
        ) {
            update_post_meta( $post_id, '_moga_organizer_name',
                isset( $_POST['moga_organizer_name'] ) ? sanitize_text_field( wp_unslash( $_POST['moga_organizer_name'] ) ) : '' );
            update_post_meta( $post_id, '_moga_phone',
                isset( $_POST['moga_phone'] ) ? moga_sanitize_phone( wp_unslash( $_POST['moga_phone'] ) ) : '' );
            update_post_meta( $post_id, '_moga_whatsapp',
                isset( $_POST['moga_whatsapp'] ) ? moga_sanitize_phone( wp_unslash( $_POST['moga_whatsapp'] ) ) : '' );
            update_post_meta( $post_id, '_moga_email',
                isset( $_POST['moga_email'] ) ? sanitize_email( wp_unslash( $_POST['moga_email'] ) ) : '' );
        }

        // ---- Details ----
        if ( isset( $_POST['moga_tour_details_nonce'] )
            && wp_verify_nonce(
                sanitize_text_field( wp_unslash( $_POST['moga_tour_details_nonce'] ) ),
                'moga_tour_details_nonce'
            )
        ) {
            update_post_meta( $post_id, '_moga_max_participants',
                isset( $_POST['moga_max_participants'] ) ? absint( $_POST['moga_max_participants'] ) : 20 );
            update_post_meta( $post_id, '_moga_min_participants',
                isset( $_POST['moga_min_participants'] ) ? absint( $_POST['moga_min_participants'] ) : 1 );
            update_post_meta( $post_id, '_moga_difficulty',
                isset( $_POST['moga_difficulty'] ) ? sanitize_text_field( wp_unslash( $_POST['moga_difficulty'] ) ) : 'easy' );
            update_post_meta( $post_id, '_moga_tour_type',
                isset( $_POST['moga_tour_type'] ) ? sanitize_text_field( wp_unslash( $_POST['moga_tour_type'] ) ) : 'group' );
            update_post_meta( $post_id, '_moga_language',
                isset( $_POST['moga_language'] ) ? sanitize_text_field( wp_unslash( $_POST['moga_language'] ) ) : 'Arabic' );
            update_post_meta( $post_id, '_moga_guide_included',
                isset( $_POST['moga_guide_included'] ) ? '1' : '0' );
        }

        // ---- Includes / Excludes ----
        if ( isset( $_POST['moga_tour_includes_nonce'] )
            && wp_verify_nonce(
                sanitize_text_field( wp_unslash( $_POST['moga_tour_includes_nonce'] ) ),
                'moga_tour_includes_nonce'
            )
        ) {
            $includes = isset( $_POST['moga_includes'] ) && is_array( $_POST['moga_includes'] )
                ? array_map( 'sanitize_text_field', wp_unslash( $_POST['moga_includes'] ) )
                : array();
            $excludes = isset( $_POST['moga_excludes'] ) && is_array( $_POST['moga_excludes'] )
                ? array_map( 'sanitize_text_field', wp_unslash( $_POST['moga_excludes'] ) )
                : array();
            update_post_meta( $post_id, '_moga_includes', wp_json_encode( $includes ) );
            update_post_meta( $post_id, '_moga_excludes', wp_json_encode( $excludes ) );
        }

        // ---- Bus & Seats ----
        if ( isset( $_POST['moga_tour_bus_nonce'] )
            && wp_verify_nonce(
                sanitize_text_field( wp_unslash( $_POST['moga_tour_bus_nonce'] ) ),
                'moga_tour_bus_nonce'
            )
        ) {
            $bus_id = isset( $_POST['moga_bus_id'] ) ? absint( $_POST['moga_bus_id'] ) : 0;
            update_post_meta( $post_id, '_moga_bus_id', $bus_id );
            update_post_meta( $post_id, '_moga_seats_available',
                isset( $_POST['moga_seats_available'] ) ? absint( $_POST['moga_seats_available'] ) : 0 );

            // Auto-fill total seats from bus if assigned.
            if ( $bus_id ) {
                $total = get_post_meta( $bus_id, '_moga_total_seats', true );
                if ( $total ) {
                    update_post_meta( $post_id, '_moga_seats_total', absint( $total ) );
                }
            }
        }

        // ---- Status ----
        if ( isset( $_POST['moga_tour_status_nonce'] )
            && wp_verify_nonce(
                sanitize_text_field( wp_unslash( $_POST['moga_tour_status_nonce'] ) ),
                'moga_tour_status_nonce'
            )
        ) {
            update_post_meta( $post_id, '_moga_active',
                isset( $_POST['moga_active'] ) ? '1' : '0' );
            update_post_meta( $post_id, '_moga_featured',
                isset( $_POST['moga_featured'] ) ? '1' : '0' );
            update_post_meta( $post_id, '_moga_instant_booking',
                isset( $_POST['moga_instant_booking'] ) ? '1' : '0' );
        }
    }


    // ============================================================
    // ADMIN SCRIPTS — COUNTRY/CITY AJAX + PHONE FIELD
    // ============================================================

    /**
     * Output inline JavaScript for meta box interactions.
     * Handles:
     *   - Country → city dropdown dynamic loading
     *   - intl-tel-input phone field initialization
     *
     * @since  1.0.0
     * @return void
     */
    public static function meta_box_scripts() {

        $screen = get_current_screen();

        if ( ! $screen || ! in_array( $screen->post_type, array( 'moga_property', 'moga_tour' ), true ) ) {
            return;
        }

        // Pass cities data to JavaScript.
        $cities_data = moga_get_all_cities();
        ?>
        <script type="text/javascript">
        ( function( $ ) {
            'use strict';

            // ---- Cities data from PHP ----
            var mogaCities = <?php echo wp_json_encode( $cities_data ); ?>;

            // ---- Country → City dynamic dropdown ----
            $( document ).on( 'change', '.moga-country-select', function() {

                var countryCode = $( this ).val();
                var targetId    = $( this ).data( 'target' );
                var $citySelect = $( '#' + targetId );

                // Clear current options.
                $citySelect.empty();

                if ( ! countryCode || ! mogaCities[ countryCode ] ) {
                    $citySelect.append(
                        $( '<option>' ).val( '' ).text(
                            '— <?php echo esc_js( __( 'Select Country First', 'moga-travel-core' ) ); ?> —'
                        )
                    );
                    return;
                }

                // Add placeholder.
                $citySelect.append(
                    $( '<option>' ).val( '' ).text(
                        '— <?php echo esc_js( __( 'Select City', 'moga-travel-core' ) ); ?> —'
                    )
                );

                // Add cities for selected country.
                $.each( mogaCities[ countryCode ], function( i, city ) {
                    $citySelect.append(
                        $( '<option>' ).val( city.name ).text( city.name )
                    );
                } );
            } );

            // ---- intl-tel-input phone field initialization ----
            $( document ).ready( function() {

                var itiInputs = document.querySelectorAll( '.moga-phone-field' );

                if ( itiInputs.length && typeof window.intlTelInput === 'function' ) {
                    itiInputs.forEach( function( input ) {
                        window.intlTelInput( input, {
                            initialCountry:   'eg',
                            loadUtils:        function() {
                                return import( '<?php echo esc_js( defined( "MOGA_THEME_URL" ) ? MOGA_THEME_URL . "assets/js/vendor/intl-tel-input/utils.js" : "" ); ?>' );
                            },
                            separateDialCode: true,
                        } );
                    } );
                }

            } );

        } )( jQuery );
        </script>

        <style>
        /* ---- Meta Box Styles ---- */
        .moga-metabox { padding: 12px 0; }
        .moga-metabox__row { display: flex; flex-wrap: wrap; gap: 16px; margin-bottom: 16px; }
        .moga-metabox__row--full .moga-metabox__field { flex: 1 1 100%; }
        .moga-metabox__field { flex: 1 1 180px; min-width: 150px; }
        .moga-metabox__field label { display: block; font-weight: 600; margin-bottom: 5px; color: #1d2327; }
        .moga-metabox__field input[type="text"],
        .moga-metabox__field input[type="number"],
        .moga-metabox__field input[type="email"],
        .moga-metabox__field input[type="tel"],
        .moga-metabox__field input[type="time"],
        .moga-metabox__field select { width: 100%; padding: 6px 8px; border: 1px solid #8c8f94; border-radius: 4px; }
        .moga-metabox__hint { font-size: 11px; color: #646970; margin: 4px 0 0; }
        .moga-metabox__hint--warning { color: #d63638; }
        .moga-metabox__section-title { font-size: 13px; font-weight: 600; color: #1d2327; border-bottom: 1px solid #dcdcde; padding-bottom: 6px; margin: 16px 0 12px; }
        .moga-metabox__section-title--green { color: #1a7f37; }
        .moga-metabox__section-title--red { color: #d63638; }
        .required { color: #d63638; margin-left: 2px; }

        /* ---- Amenities ---- */
        .moga-amenity-group { margin-bottom: 16px; }
        .moga-amenity-group__title { font-size: 12px; font-weight: 600; text-transform: uppercase; color: #646970; margin-bottom: 8px; }
        .moga-amenity-group__items { display: flex; flex-wrap: wrap; gap: 8px; }
        .moga-amenity-item { display: flex; align-items: center; gap: 5px; background: #f6f7f7; padding: 4px 8px; border-radius: 3px; cursor: pointer; font-size: 12px; }
        .moga-amenity-item input { margin: 0; }

        /* ---- Two column ---- */
        .moga-metabox--two-col { display: flex; gap: 24px; }
        .moga-metabox__col { flex: 1; }
        .moga-checklist { display: flex; flex-direction: column; gap: 6px; }
        .moga-checklist__item { display: flex; align-items: center; gap: 8px; font-size: 13px; cursor: pointer; }

        /* ---- Weekdays ---- */
        .moga-weekdays { display: flex; gap: 8px; flex-wrap: wrap; }
        .moga-weekday { display: flex; flex-direction: column; align-items: center; gap: 4px; background: #f6f7f7; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: 600; }

        /* ---- Toggle Switches ---- */
        .moga-metabox__switches { display: flex; flex-direction: column; gap: 12px; }
        .moga-switch { display: flex; align-items: center; gap: 10px; cursor: pointer; }
        .moga-switch input { display: none; }
        .moga-switch__slider { position: relative; width: 40px; height: 22px; background: #ccc; border-radius: 22px; transition: background 0.2s; flex-shrink: 0; }
        .moga-switch__slider::after { content: ''; position: absolute; width: 18px; height: 18px; background: #fff; border-radius: 50%; top: 2px; left: 2px; transition: left 0.2s; }
        .moga-switch input:checked + .moga-switch__slider { background: #2271b1; }
        .moga-switch input:checked + .moga-switch__slider::after { left: 20px; }
        .moga-switch__label { font-size: 13px; color: #1d2327; }
        </style>
        <?php
    }
}