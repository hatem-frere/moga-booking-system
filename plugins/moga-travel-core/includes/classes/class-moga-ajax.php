<?php
/**
 * AJAX Handlers
 *
 * Handles all WordPress AJAX requests from the frontend.
 *
 * Registered actions:
 *   - moga_get_cities          → City dropdown loader
 *   - moga_check_availability  → Date availability checker
 *   - moga_calculate_price     → Live price calculator
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

        // City dropdown loader.
        add_action( 'wp_ajax_moga_get_cities',         array( __CLASS__, 'get_cities' ) );
        add_action( 'wp_ajax_nopriv_moga_get_cities',  array( __CLASS__, 'get_cities' ) );

        // Availability checker.
        add_action( 'wp_ajax_moga_check_availability',        array( __CLASS__, 'check_availability' ) );
        add_action( 'wp_ajax_nopriv_moga_check_availability', array( __CLASS__, 'check_availability' ) );

        // Price calculator.
        add_action( 'wp_ajax_moga_calculate_price',        array( __CLASS__, 'calculate_price' ) );
        add_action( 'wp_ajax_nopriv_moga_calculate_price', array( __CLASS__, 'calculate_price' ) );
    }


    // ============================================================
    // CITY LOADER
    // ============================================================

    /**
     * Handle AJAX request to get cities for a country.
     *
     * @since  1.0.0
     * @return void Sends JSON response.
     */
    public static function get_cities() {

        // Verify nonce.
        if ( ! isset( $_POST['nonce'] )
            || ! wp_verify_nonce(
                sanitize_text_field( wp_unslash( $_POST['nonce'] ) ),
                'moga_nonce'
            )
        ) {
            wp_send_json_error( array( 'message' => 'Invalid nonce.' ) );
        }

        $country_code = isset( $_POST['country_code'] )
            ? strtoupper( sanitize_text_field( wp_unslash( $_POST['country_code'] ) ) )
            : '';

        if ( empty( $country_code ) ) {
            wp_send_json_error( array( 'message' => 'Country code required.' ) );
        }

        $cities = moga_get_cities_by_country( $country_code );

        if ( empty( $cities ) ) {
            wp_send_json_success( array(
                'cities'  => array(),
                'message' => 'No cities found for this country.',
            ) );
        }

        wp_send_json_success( array(
            'cities'  => $cities,
            'country' => $country_code,
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