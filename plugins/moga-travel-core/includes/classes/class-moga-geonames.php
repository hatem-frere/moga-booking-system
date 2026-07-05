<?php
/**
 * GeoNames API Wrapper
 *
 * Integrates with the free GeoNames web service to provide
 * worldwide city and district data for the location cascade
 * dropdowns in property and tour meta boxes and frontend search.
 *
 * How it works:
 *   1. Admin selects a country  → loadCities()  fetches from GeoNames
 *      and caches result as a WordPress transient for 30 days.
 *   2. Admin selects a city     → loadDistricts() fetches children
 *      from GeoNames using the city's geonameId and caches result.
 *   3. On post save, the selected country/city/district are
 *      auto-synced to the moga_location taxonomy via
 *      Moga_Tax_Location::sync_from_selection().
 *
 * Username resolution order (commercial-safe):
 *   1. WordPress option  'moga_geonames_username'  (buyer sets in Settings)
 *   2. PHP constant      MOGA_GEONAMES_USERNAME     (local dev only, wp-config.php)
 *   3. Empty string → API calls skipped, static fallback used instead.
 *
 * GeoNames API endpoints used:
 *   - searchJSON  : country cities ordered by population
 *   - childrenJSON: sub-divisions / districts of a city
 *
 * @package    MogaTravelCore
 * @subpackage MogaTravelCore/includes/classes
 * @author     Hatem Frere
 * @since      1.0.0
 */

// Block direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Moga_Geonames
 */
class Moga_Geonames {

    // ============================================================
    // CONSTANTS
    // ============================================================

    /**
     * GeoNames base API URL.
     *
     * @since 1.0.0
     * @var   string
     */
    const API_BASE = 'http://api.geonames.org/';

    /**
     * Transient expiry for cached city lists (30 days).
     *
     * @since 1.0.0
     * @var   int
     */
    const CACHE_CITIES_EXPIRY = 30 * DAY_IN_SECONDS;

    /**
     * Transient expiry for cached district lists (30 days).
     *
     * @since 1.0.0
     * @var   int
     */
    const CACHE_DISTRICTS_EXPIRY = 30 * DAY_IN_SECONDS;

    /**
     * Maximum cities to fetch per country.
     * GeoNames free tier allows up to 1000 rows per request.
     * 500 covers all meaningful cities worldwide per country.
     *
     * @since 1.0.0
     * @var   int
     */
    const MAX_CITIES = 500;

    /**
     * Maximum districts to fetch per city.
     *
     * @since 1.0.0
     * @var   int
     */
    const MAX_DISTRICTS = 300;


    // ============================================================
    // USERNAME RESOLUTION
    // ============================================================

    /**
     * Get the GeoNames username to use for API requests.
     *
     * Resolution order:
     *   1. WordPress option 'moga_geonames_username' (buyer sets in Moga Settings)
     *   2. PHP constant MOGA_GEONAMES_USERNAME        (local dev via wp-config.php)
     *   3. Empty string (API calls will be skipped)
     *
     * @since  1.0.0
     * @return string GeoNames username or empty string if not configured.
     */
    public static function get_username() {

        // First: check the WordPress option (production / buyer install).
        $username = get_option( 'moga_geonames_username', '' );

        if ( ! empty( $username ) ) {
            return sanitize_text_field( $username );
        }

        // Second: check PHP constant (local development via wp-config.php).
        if ( defined( 'MOGA_GEONAMES_USERNAME' ) && ! empty( MOGA_GEONAMES_USERNAME ) ) {
            return sanitize_text_field( MOGA_GEONAMES_USERNAME );
        }

        // Not configured — return empty string.
        return '';
    }

    /**
     * Check if GeoNames is configured and ready to use.
     *
     * @since  1.0.0
     * @return bool True if a username is available.
     */
    public static function is_configured() {
        return ! empty( self::get_username() );
    }


    // ============================================================
    // CITIES
    // ============================================================

    /**
     * Get cities for a given country code.
     *
     * Checks transient cache first. On cache miss, fetches from
     * GeoNames searchJSON API and stores result in transient.
     *
     * Returns array of city objects, each with:
     *   - name        (string)  City display name
     *   - geoname_id  (int)     GeoNames numeric ID (used for district lookup)
     *   - lat         (string)  GPS latitude
     *   - lng         (string)  GPS longitude
     *
     * Returns empty array if:
     *   - GeoNames username not configured
     *   - API request fails
     *   - Country code is invalid
     *
     * @since  1.0.0
     * @param  string $country_code ISO 3166-1 alpha-2 country code (e.g. 'EG', 'US').
     * @return array  Array of city data arrays, ordered by population descending.
     */
    public static function get_cities( $country_code ) {

        $country_code = strtoupper( sanitize_text_field( $country_code ) );

        if ( empty( $country_code ) ) {
            return array();
        }

        // Check transient cache first.
        $cache_key = 'moga_geo_cities_' . $country_code;
        $cached    = get_transient( $cache_key );

        if ( false !== $cached ) {
            return $cached;
        }

        // Not cached — fetch from GeoNames.
        if ( ! self::is_configured() ) {
            return array();
        }

        $cities = self::fetch_cities_from_api( $country_code );

        // Cache even if empty to avoid repeated failed API calls.
        set_transient( $cache_key, $cities, self::CACHE_CITIES_EXPIRY );

        return $cities;
    }

    /**
     * Fetch cities from GeoNames searchJSON API.
     *
     * API endpoint:
     *   searchJSON?country={CC}&featureClass=P&orderby=population
     *             &maxRows=500&username={UN}
     *
     * featureClass=P returns populated places only (cities, towns, villages).
     *
     * @since  1.0.0
     * @param  string $country_code ISO country code.
     * @return array  Standardized city array.
     */
    private static function fetch_cities_from_api( $country_code ) {

        $url = add_query_arg(
            array(
                'country'      => rawurlencode( $country_code ),
                'featureClass' => 'P',
                'orderby'      => 'population',
                'maxRows'      => self::MAX_CITIES,
                'username'     => rawurlencode( self::get_username() ),
                'type'         => 'json',
            ),
            self::API_BASE . 'searchJSON'
        );

        $response = wp_remote_get( $url, array(
            'timeout'   => 10,
            'sslverify' => false,
        ) );

        // Handle request failure.
        if ( is_wp_error( $response ) ) {
            error_log( 'Moga GeoNames cities error: ' . $response->get_error_message() );
            return array();
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        // Handle API-level errors.
        if ( isset( $data['status'] ) ) {
            error_log( 'Moga GeoNames cities API error: ' . $data['status']['message'] );
            return array();
        }

        if ( empty( $data['geonames'] ) ) {
            return array();
        }

        // Normalize to our standard format.
        $cities = array();

        foreach ( $data['geonames'] as $item ) {

            // Skip entries without a name.
            if ( empty( $item['name'] ) ) {
                continue;
            }

            $cities[] = array(
                'name'       => sanitize_text_field( $item['name'] ),
                'geoname_id' => absint( $item['geonameId'] ),
                'lat'        => isset( $item['lat'] ) ? sanitize_text_field( $item['lat'] ) : '',
                'lng'        => isset( $item['lng'] ) ? sanitize_text_field( $item['lng'] ) : '',
            );
        }

        return $cities;
    }


    // ============================================================
    // DISTRICTS
    // ============================================================

    /**
     * Get districts/sub-areas for a given city GeoNames ID.
     *
     * Checks transient cache first. On cache miss, fetches from
     * GeoNames childrenJSON API and stores result in transient.
     *
     * Returns array of district objects, each with:
     *   - name        (string)  District display name
     *   - geoname_id  (int)     GeoNames numeric ID
     *   - lat         (string)  GPS latitude
     *   - lng         (string)  GPS longitude
     *
     * Returns empty array if:
     *   - GeoNames username not configured
     *   - API request fails
     *   - City has no recorded sub-divisions in GeoNames
     *
     * When empty array is returned, the caller should display
     * a manual text input fallback instead of an empty dropdown.
     *
     * @since  1.0.0
     * @param  int $geoname_id GeoNames numeric city ID (e.g. 360630 for Cairo).
     * @return array  Array of district data arrays, ordered alphabetically.
     */
    public static function get_districts( $geoname_id ) {

        $geoname_id = absint( $geoname_id );

        if ( empty( $geoname_id ) ) {
            return array();
        }

        // Check transient cache first.
        $cache_key = 'moga_geo_districts_' . $geoname_id;
        $cached    = get_transient( $cache_key );

        if ( false !== $cached ) {
            return $cached;
        }

        // Not cached — fetch from GeoNames.
        if ( ! self::is_configured() ) {
            return array();
        }

        $districts = self::fetch_districts_from_api( $geoname_id );

        // Cache even if empty to avoid repeated failed API calls.
        set_transient( $cache_key, $districts, self::CACHE_DISTRICTS_EXPIRY );

        return $districts;
    }

    /**
     * Fetch districts from GeoNames childrenJSON API.
     *
     * API endpoint:
     *   childrenJSON?geonameId={ID}&maxRows=300&username={UN}
     *
     * childrenJSON returns the direct administrative children
     * of a given place — for cities this returns neighborhoods,
     * districts, and sub-divisions recorded in GeoNames database.
     *
     * @since  1.0.0
     * @param  int $geoname_id GeoNames city ID.
     * @return array  Standardized district array, sorted by name.
     */
    private static function fetch_districts_from_api( $geoname_id ) {

        $url = add_query_arg(
            array(
                'geonameId' => $geoname_id,
                'maxRows'   => self::MAX_DISTRICTS,
                'username'  => rawurlencode( self::get_username() ),
                'type'      => 'json',
            ),
            self::API_BASE . 'childrenJSON'
        );

        $response = wp_remote_get( $url, array(
            'timeout'   => 10,
            'sslverify' => false,
        ) );

        // Handle request failure.
        if ( is_wp_error( $response ) ) {
            error_log( 'Moga GeoNames districts error: ' . $response->get_error_message() );
            return array();
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        // Handle API-level errors.
        if ( isset( $data['status'] ) ) {
            error_log( 'Moga GeoNames districts API error: ' . $data['status']['message'] );
            return array();
        }

        if ( empty( $data['geonames'] ) ) {
            return array();
        }

        // Normalize to our standard format.
        $districts = array();

        foreach ( $data['geonames'] as $item ) {

            // Skip entries without a name.
            if ( empty( $item['name'] ) ) {
                continue;
            }

            $districts[] = array(
                'name'       => sanitize_text_field( $item['name'] ),
                'geoname_id' => absint( $item['geonameId'] ),
                'lat'        => isset( $item['lat'] ) ? sanitize_text_field( $item['lat'] ) : '',
                'lng'        => isset( $item['lng'] ) ? sanitize_text_field( $item['lng'] ) : '',
            );
        }

        // Sort alphabetically by name for consistent display.
        usort( $districts, function( $a, $b ) {
            return strcmp( $a['name'], $b['name'] );
        } );

        return $districts;
    }


    // ============================================================
    // CACHE MANAGEMENT
    // ============================================================

    /**
     * Clear the cached city list for a specific country.
     * Useful if GeoNames data needs refreshing manually.
     *
     * @since  1.0.0
     * @param  string $country_code ISO country code.
     * @return void
     */
    public static function clear_cities_cache( $country_code ) {
        delete_transient( 'moga_geo_cities_' . strtoupper( $country_code ) );
    }

    /**
     * Clear the cached district list for a specific city.
     *
     * @since  1.0.0
     * @param  int $geoname_id GeoNames city ID.
     * @return void
     */
    public static function clear_districts_cache( $geoname_id ) {
        delete_transient( 'moga_geo_districts_' . absint( $geoname_id ) );
    }

    /**
     * Clear ALL GeoNames cached data (cities + districts).
     * Can be triggered from admin settings "Clear Geo Cache" button.
     *
     * @since  1.0.0
     * @return int Number of transients deleted.
     */
    public static function clear_all_cache() {

        global $wpdb;

        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options}
                 WHERE option_name LIKE %s
                    OR option_name LIKE %s",
                $wpdb->esc_like( '_transient_moga_geo_cities_' )    . '%',
                $wpdb->esc_like( '_transient_moga_geo_districts_' ) . '%'
            )
        );

        // Also clean up the timeout rows.
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options}
                 WHERE option_name LIKE %s
                    OR option_name LIKE %s",
                $wpdb->esc_like( '_transient_timeout_moga_geo_cities_' )    . '%',
                $wpdb->esc_like( '_transient_timeout_moga_geo_districts_' ) . '%'
            )
        );

        return absint( $deleted );
    }


    // ============================================================
    // ADMIN NOTICE
    // ============================================================

    /**
     * Display admin notice if GeoNames username is not configured.
     * Hooked to 'admin_notices' from Moga_Core if needed.
     *
     * @since  1.0.0
     * @return void
     */
    public static function maybe_show_config_notice() {

        if ( self::is_configured() ) {
            return;
        }

        $screen = get_current_screen();

        // Only show on Moga CPT screens and settings page.
        $show_on = array( 'moga_property', 'moga_tour', 'moga_bus', 'moga_destination' );

        if ( ! $screen
            || ( ! in_array( $screen->post_type, $show_on, true )
                && 'moga_page_moga-settings' !== $screen->id )
        ) {
            return;
        }

        echo '<div class="notice notice-warning is-dismissible">'
            . '<p><strong>' . esc_html__( 'Moga Booking:', 'moga-travel-core' ) . '</strong> '
            . esc_html__( 'GeoNames is not configured. Worldwide city and district dropdowns will use static data only. ', 'moga-travel-core' )
            . '<a href="' . esc_url( admin_url( 'admin.php?page=moga-settings' ) ) . '">'
            . esc_html__( 'Enter your GeoNames username in Moga Settings.', 'moga-travel-core' )
            . '</a></p></div>';
    }
}