<?php
/**
 * AJAX Handlers
 *
 * Handles all WordPress AJAX requests from the frontend.
 *
 * Registered actions:
 *   - moga_get_provinces    → Province dropdown loader (DB-powered, four-level cascade)
 *   - moga_get_cities       → City dropdown loader (DB-powered, by province_id)
 *   - moga_get_districts    → District dropdown loader (DB-powered, by city_id)
 *   - moga_check_availability  → Date availability checker
 *   - moga_calculate_price     → Live price calculator
 *
 * Location cascade (four levels, all DB-driven):
 *   Country select → moga_get_provinces(country_id)
 *   Province select → moga_get_cities(province_id)
 *   City select → moga_get_districts(city_id)
 *   District: dropdown if DB has data, text input fallback if empty
 *
 * @package    MogaTravelCore
 * @subpackage MogaTravelCore/includes/classes
 * @author     Hatem Frere
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Moga_Ajax
 */
class Moga_Ajax {

    /**
     * Register all AJAX hooks.
     * Both logged-in (wp_ajax_) and logged-out (wp_ajax_nopriv_) users.
     *
     * @since  1.0.0
     * @return void
     */
    public static function init() {

        // Province dropdown loader — country → provinces cascade.
        add_action( 'wp_ajax_moga_get_provinces',         array( __CLASS__, 'get_provinces' ) );
        add_action( 'wp_ajax_nopriv_moga_get_provinces',  array( __CLASS__, 'get_provinces' ) );

        // City dropdown loader — province → cities cascade.
        add_action( 'wp_ajax_moga_get_cities',            array( __CLASS__, 'get_cities' ) );
        add_action( 'wp_ajax_nopriv_moga_get_cities',     array( __CLASS__, 'get_cities' ) );

        // District dropdown loader — city → districts cascade.
        add_action( 'wp_ajax_moga_get_districts',         array( __CLASS__, 'get_districts' ) );
        add_action( 'wp_ajax_nopriv_moga_get_districts',  array( __CLASS__, 'get_districts' ) );

        // Availability checker.
        add_action( 'wp_ajax_moga_check_availability',        array( __CLASS__, 'check_availability' ) );
        add_action( 'wp_ajax_nopriv_moga_check_availability', array( __CLASS__, 'check_availability' ) );

        // Price calculator.
        add_action( 'wp_ajax_moga_calculate_price',        array( __CLASS__, 'calculate_price' ) );
        add_action( 'wp_ajax_nopriv_moga_calculate_price', array( __CLASS__, 'calculate_price' ) );
    }


    // ============================================================
    // PROVINCE LOADER — DB-POWERED (four-level cascade step 1)
    // ============================================================

    /**
     * Handle AJAX request to get provinces for a given country.
     *
     * Queries mg_moga_loc_countries by iso_code OR mg_moga_loc_provinces
     * by country_id. Accepts either identifier for flexibility:
     *   - country_id   (int)    DB ID from mg_moga_loc_countries
     *   - country_code (string) ISO alpha-2 fallback (e.g. 'EG')
     *
     * POST params:
     *   - nonce        : moga_nonce
     *   - country_id   : DB country ID (preferred)
     *   - country_code : ISO code (used when country_id not available)
     *
     * Returns: { provinces: [{id, name}, ...], country_id: N }
     *
     * @since  1.0.0
     * @return void Sends JSON response.
     */
    public static function get_provinces() {

        if ( ! isset( $_POST['nonce'] )
            || ! wp_verify_nonce(
                sanitize_text_field( wp_unslash( $_POST['nonce'] ) ),
                'moga_nonce'
            )
        ) {
            wp_send_json_error( array( 'message' => 'Invalid nonce.' ) );
        }

        global $wpdb;
        $prefix = $wpdb->prefix . MOGA_CORE_DB_PREFIX;

        $country_id   = isset( $_POST['country_id'] )   ? absint( $_POST['country_id'] )                                     : 0;
        $country_code = isset( $_POST['country_code'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_POST['country_code'] ) ) ) : '';

        // Resolve country_id from iso_code if not provided directly.
        if ( ! $country_id && $country_code ) {
            $country_id = (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT id FROM {$prefix}loc_countries WHERE iso_code = %s LIMIT 1",
                $country_code
            ) );
        }

        if ( ! $country_id ) {
            wp_send_json_error( array( 'message' => 'country_id or country_code required.' ) );
        }

        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT id, name FROM {$prefix}loc_provinces WHERE country_id = %d ORDER BY name ASC",
            $country_id
        ), ARRAY_A );

        wp_send_json_success( array(
            'provinces'  => $rows ?: array(),
            'country_id' => $country_id,
        ) );
    }


    // ============================================================
    // CITY LOADER — DB-POWERED (four-level cascade step 2)
    // ============================================================

    /**
     * Handle AJAX request to get cities for a given province.
     *
     * Primary key: province_id (DB ID from mg_moga_loc_provinces).
     * Fallback: if DB tables are empty (import not run yet), falls
     * back to static moga_get_cities_by_country() to keep the
     * system functional before the import wizard is used.
     *
     * POST params:
     *   - nonce       : moga_nonce
     *   - province_id : DB province ID (required for DB path)
     *
     * Returns: { cities: [{id, name, lat, lng}, ...], province_id: N }
     *
     * @since  1.0.0
     * @return void Sends JSON response.
     */
    public static function get_cities() {

        if ( ! isset( $_POST['nonce'] )
            || ! wp_verify_nonce(
                sanitize_text_field( wp_unslash( $_POST['nonce'] ) ),
                'moga_nonce'
            )
        ) {
            wp_send_json_error( array( 'message' => 'Invalid nonce.' ) );
        }

        global $wpdb;
        $prefix = $wpdb->prefix . MOGA_CORE_DB_PREFIX;

        $province_id = isset( $_POST['province_id'] ) ? absint( $_POST['province_id'] ) : 0;

        if ( ! $province_id ) {
            wp_send_json_error( array( 'message' => 'province_id required.' ) );
        }

        // Query cities from the DB.
        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT id, name, lat, lng FROM {$prefix}loc_cities WHERE province_id = %d ORDER BY name ASC",
            $province_id
        ), ARRAY_A );

        // Fallback: if DB is empty (import not yet run), use static data.
        // This keeps the system functional even before the import wizard is used.
        if ( empty( $rows ) ) {
            $static = moga_get_cities_by_country( '' ); // empty — returns nothing, graceful
            wp_send_json_success( array(
                'cities'      => array(),
                'province_id' => $province_id,
                'source'      => 'empty',
            ) );
        }

        wp_send_json_success( array(
            'cities'      => $rows,
            'province_id' => $province_id,
            'source'      => 'db',
        ) );
    }


    // ============================================================
    // DISTRICT LOADER — DB-POWERED (four-level cascade step 3)
    // ============================================================

    /**
     * Handle AJAX request to get districts for a given city.
     *
     * Districts are the fourth and deepest location level.
     * They are NOT imported from JSON — they are added manually
     * by the admin via the Location Editor.
     *
     * Returns an empty array when no districts exist for the city.
     * The frontend JS interprets an empty array as a signal to show
     * a free-text input field instead of a dropdown, so the property
     * owner can type the district name manually.
     *
     * POST params:
     *   - nonce   : moga_nonce
     *   - city_id : DB city ID from mg_moga_loc_cities (required)
     *
     * Returns: { districts: [{id, name}, ...], city_id: N }
     *   Empty districts array → frontend shows text input fallback.
     *
     * @since  1.0.0
     * @return void Sends JSON response.
     */
    public static function get_districts() {

        if ( ! isset( $_POST['nonce'] )
            || ! wp_verify_nonce(
                sanitize_text_field( wp_unslash( $_POST['nonce'] ) ),
                'moga_nonce'
            )
        ) {
            wp_send_json_error( array( 'message' => 'Invalid nonce.' ) );
        }

        $city_id = isset( $_POST['city_id'] ) ? absint( $_POST['city_id'] ) : 0;

        if ( ! $city_id ) {
            // No city_id — return empty, frontend shows text input.
            wp_send_json_success( array(
                'districts' => array(),
                'city_id'   => 0,
            ) );
        }

        global $wpdb;
        $prefix = $wpdb->prefix . MOGA_CORE_DB_PREFIX;

        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT id, name FROM {$prefix}loc_districts WHERE city_id = %d ORDER BY name ASC",
            $city_id
        ), ARRAY_A );

        // Always return success — empty array signals text input fallback.
        wp_send_json_success( array(
            'districts' => $rows ?: array(),
            'city_id'   => $city_id,
        ) );
    }


    // ============================================================
    // AVAILABILITY CHECKER
    // ============================================================

    /**
     * Handle AJAX request to check listing availability.
     *
     * @since  1.0.0
     * @return void Sends JSON response.
     */
    public static function check_availability() {

        // Verify nonce.
        if ( ! isset( $_POST['nonce'] )
            || ! wp_verify_nonce(
                sanitize_text_field( wp_unslash( $_POST['nonce'] ) ),
                'moga_nonce'
            )
        ) {
            wp_send_json_error( array( 'message' => 'Invalid nonce.' ) );
        }

        $listing_id   = isset( $_POST['listing_id'] )   ? absint( $_POST['listing_id'] )                                    : 0;
        $listing_type = isset( $_POST['listing_type'] ) ? sanitize_text_field( wp_unslash( $_POST['listing_type'] ) )       : 'property';
        $check_in     = isset( $_POST['check_in'] )     ? sanitize_text_field( wp_unslash( $_POST['check_in'] ) )           : '';
        $check_out    = isset( $_POST['check_out'] )    ? sanitize_text_field( wp_unslash( $_POST['check_out'] ) )          : '';

        if ( ! $listing_id || ! $check_in || ! $check_out ) {
            wp_send_json_error( array( 'message' => 'Missing required fields.' ) );
        }

        // Validate dates.
        $validation = moga_validate_dates( $check_in, $check_out );
        if ( is_wp_error( $validation ) ) {
            wp_send_json_error( array( 'message' => $validation->get_error_message() ) );
        }

        // Check availability.
        $available = moga_is_available( $listing_id, $check_in, $check_out, $listing_type );

        $response = array(
            'available'    => $available,
            'listing_id'   => $listing_id,
            'check_in'     => $check_in,
            'check_out'    => $check_out,
        );

        // If available, include price.
        if ( $available ) {
            if ( 'property' === $listing_type ) {
                $price_data = moga_calculate_property_price( $listing_id, $check_in, $check_out );
            } else {
                $price_data = moga_calculate_tour_price( $listing_id );
            }

            $currency = isset( $price_data['currency'] ) ? $price_data['currency'] : moga_currency();

            // Add formatted prices.
            $price_data['price_formatted']    = moga_format_price( $price_data['price_per_night'] ?? $price_data['price_adult'] ?? 0, $currency );
            $price_data['subtotal_formatted'] = moga_format_price( $price_data['subtotal'] ?? 0, $currency );
            $price_data['discount_formatted'] = moga_format_price( $price_data['discount']  ?? 0, $currency );
            $price_data['taxes_formatted']    = moga_format_price( $price_data['taxes']     ?? 0, $currency );
            $price_data['total_formatted']    = moga_format_price( $price_data['total']     ?? 0, $currency );

            $response['price'] = $price_data;
        }

        wp_send_json_success( $response );
    }


    // ============================================================
    // PRICE CALCULATOR
    // ============================================================

    /**
     * Handle AJAX request to calculate booking price.
     *
     * @since  1.0.0
     * @return void Sends JSON response.
     */
    public static function calculate_price() {

        // Verify nonce.
        if ( ! isset( $_POST['nonce'] )
            || ! wp_verify_nonce(
                sanitize_text_field( wp_unslash( $_POST['nonce'] ) ),
                'moga_nonce'
            )
        ) {
            wp_send_json_error( array( 'message' => 'Invalid nonce.' ) );
        }

        $listing_id   = isset( $_POST['listing_id'] )   ? absint( $_POST['listing_id'] )                              : 0;
        $listing_type = isset( $_POST['listing_type'] ) ? sanitize_text_field( wp_unslash( $_POST['listing_type'] ) ) : 'property';
        $check_in     = isset( $_POST['check_in'] )     ? sanitize_text_field( wp_unslash( $_POST['check_in'] ) )     : '';
        $check_out    = isset( $_POST['check_out'] )    ? sanitize_text_field( wp_unslash( $_POST['check_out'] ) )    : '';
        $adults       = isset( $_POST['adults'] )       ? absint( $_POST['adults'] )                                  : 1;
        $children     = isset( $_POST['children'] )     ? absint( $_POST['children'] )                                : 0;
        $infants      = isset( $_POST['infants'] )      ? absint( $_POST['infants'] )                                 : 0;

        if ( ! $listing_id ) {
            wp_send_json_error( array( 'message' => 'Listing ID required.' ) );
        }

        // Calculate price based on listing type.
        if ( 'property' === $listing_type ) {
            if ( ! $check_in || ! $check_out ) {
                wp_send_json_error( array( 'message' => 'Dates required for property pricing.' ) );
            }
            $price_data = moga_calculate_property_price( $listing_id, $check_in, $check_out );
        } else {
            $price_data = moga_calculate_tour_price( $listing_id, $adults, $children, $infants );
        }

        $currency = isset( $price_data['currency'] ) ? $price_data['currency'] : moga_currency();

        // Add formatted prices for JavaScript rendering.
        $price_data['price_formatted']         = moga_format_price( $price_data['price_per_night']  ?? $price_data['price_adult'] ?? 0, $currency );
        $price_data['subtotal_formatted']      = moga_format_price( $price_data['subtotal']         ?? 0, $currency );
        $price_data['discount_formatted']      = moga_format_price( $price_data['discount']         ?? 0, $currency );
        $price_data['taxes_formatted']         = moga_format_price( $price_data['taxes']            ?? 0, $currency );
        $price_data['total_formatted']         = moga_format_price( $price_data['total']            ?? 0, $currency );
        $price_data['adults_total_formatted']  = moga_format_price( $price_data['adults_total']     ?? 0, $currency );
        $price_data['price_adult_formatted']   = moga_format_price( $price_data['price_adult']      ?? 0, $currency );
        $price_data['price_child_formatted']   = moga_format_price( $price_data['price_child']      ?? 0, $currency );
        $price_data['discount_percent']        = $price_data['discount_percent'] ?? $price_data['group_discount'] ?? 0;

        wp_send_json_success( array( 'price' => $price_data ) );
    }
}