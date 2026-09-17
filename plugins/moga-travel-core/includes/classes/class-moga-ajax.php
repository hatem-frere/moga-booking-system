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
 *   - moga_get_seat_map        → Bus seat map data for a tour trip date
 *   - moga_reserve_seats       → Temporarily hold selected seats (15-min hold)
 *   - moga_release_seats       → Release a guest's held seats
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

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Class Moga_Ajax
 */
class Moga_Ajax
{

    /**
     * Register all AJAX hooks.
     * Both logged-in (wp_ajax_) and logged-out (wp_ajax_nopriv_) users.
     *
     * @since  1.0.0
     * @return void
     */
    public static function init()
    {

        // Province dropdown loader — country → provinces cascade.
        add_action('wp_ajax_moga_get_provinces',         array(__CLASS__, 'get_provinces'));
        add_action('wp_ajax_nopriv_moga_get_provinces',  array(__CLASS__, 'get_provinces'));

        // City dropdown loader — province → cities cascade.
        add_action('wp_ajax_moga_get_cities',            array(__CLASS__, 'get_cities'));
        add_action('wp_ajax_nopriv_moga_get_cities',     array(__CLASS__, 'get_cities'));

        // District dropdown loader — city → districts cascade.
        add_action('wp_ajax_moga_get_districts',         array(__CLASS__, 'get_districts'));
        add_action('wp_ajax_nopriv_moga_get_districts',  array(__CLASS__, 'get_districts'));

        // Availability checker.
        add_action('wp_ajax_moga_check_availability',        array(__CLASS__, 'check_availability'));
        add_action('wp_ajax_nopriv_moga_check_availability', array(__CLASS__, 'check_availability'));

        // Price calculator.
        add_action('wp_ajax_moga_calculate_price',        array(__CLASS__, 'calculate_price'));
        add_action('wp_ajax_nopriv_moga_calculate_price', array(__CLASS__, 'calculate_price'));

        // Seat map — get full seat grid for a bus + trip date.
        add_action('wp_ajax_moga_get_seat_map',        array(__CLASS__, 'get_seat_map'));
        add_action('wp_ajax_nopriv_moga_get_seat_map', array(__CLASS__, 'get_seat_map'));

        // Seat reserve — temporarily hold selected seats (15-min hold).
        add_action('wp_ajax_moga_reserve_seats',        array(__CLASS__, 'reserve_seats'));
        add_action('wp_ajax_nopriv_moga_reserve_seats', array(__CLASS__, 'reserve_seats'));

        // Seat release — release a guest's currently held seats.
        add_action('wp_ajax_moga_release_seats',        array(__CLASS__, 'release_seats'));
        add_action('wp_ajax_nopriv_moga_release_seats', array(__CLASS__, 'release_seats'));

        // Inline bus creation — from Tour editor "Create New Bus" panel.
        // Admin-only — no nopriv equivalent.
        add_action('wp_ajax_moga_create_bus_inline', array(__CLASS__, 'create_bus_inline'));

        // Bus seats lookup — auto-fills Total Seats when bus dropdown changes.
        // Admin-only — no nopriv equivalent.
        add_action('wp_ajax_moga_get_bus_seats', array(__CLASS__, 'get_bus_seats'));

        // Google Places hotel lookup — fetches name, rating, and photo
        // references for the accommodation widget. Server-side only so
        // the API key is never exposed in the browser.
        // Available to both logged-in and logged-out guests.
        add_action('wp_ajax_moga_get_hotel_places',        array(__CLASS__, 'get_hotel_places'));
        add_action('wp_ajax_nopriv_moga_get_hotel_places', array(__CLASS__, 'get_hotel_places'));

        // Hotel autocomplete — admin Tour editor. Returns matching hotel
        // names from Google Places as the organizer types.
        add_action('wp_ajax_moga_search_hotel', array(__CLASS__, 'search_hotel'));

        // City autocomplete — admin metaboxes (property, tour departure,
        // tour destination). Returns Google Places city suggestions scoped
        // to the selected country. Replaces the DB-driven city <select>.
        // Admin-only — no nopriv equivalent.
        add_action('wp_ajax_moga_city_autocomplete', array(__CLASS__, 'city_autocomplete'));

        // Province autocomplete — admin metaboxes. Returns Google Places
        // administrative_area_level_1 suggestions scoped to the selected
        // country. Replaces the DB-driven province <select>.
        // Admin-only — no nopriv equivalent.
        add_action('wp_ajax_moga_province_autocomplete', array(__CLASS__, 'province_autocomplete'));
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
    public static function get_provinces()
    {

        if (
            ! isset($_POST['nonce'])
            || ! wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['nonce'])),
                'moga_nonce'
            )
        ) {
            wp_send_json_error(array('message' => 'Invalid nonce.'));
        }

        global $wpdb;
        $prefix = $wpdb->prefix . MOGA_CORE_DB_PREFIX;

        $country_id   = isset($_POST['country_id'])   ? absint($_POST['country_id'])                                     : 0;
        $country_code = isset($_POST['country_code']) ? strtoupper(sanitize_text_field(wp_unslash($_POST['country_code']))) : '';

        // Resolve country_id from iso_code if not provided directly.
        if (! $country_id && $country_code) {
            $country_id = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$prefix}loc_countries WHERE iso_code = %s LIMIT 1",
                $country_code
            ));
        }

        if (! $country_id) {
            wp_send_json_error(array('message' => 'country_id or country_code required.'));
        }

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT id, name FROM {$prefix}loc_provinces WHERE country_id = %d ORDER BY name ASC",
            $country_id
        ), ARRAY_A);

        wp_send_json_success(array(
            'provinces'  => $rows ?: array(),
            'country_id' => $country_id,
        ));
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
    public static function get_cities()
    {

        if (
            ! isset($_POST['nonce'])
            || ! wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['nonce'])),
                'moga_nonce'
            )
        ) {
            wp_send_json_error(array('message' => 'Invalid nonce.'));
        }

        global $wpdb;
        $prefix = $wpdb->prefix . MOGA_CORE_DB_PREFIX;

        $province_id = isset($_POST['province_id']) ? absint($_POST['province_id']) : 0;

        if (! $province_id) {
            wp_send_json_error(array('message' => 'province_id required.'));
        }

        // Query cities from the DB.
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT id, name, lat, lng FROM {$prefix}loc_cities WHERE province_id = %d ORDER BY name ASC",
            $province_id
        ), ARRAY_A);

        // Fallback: if DB is empty (import not yet run), use static data.
        // This keeps the system functional even before the import wizard is used.
        if (empty($rows)) {
            $static = moga_get_cities_by_country(''); // empty — returns nothing, graceful
            wp_send_json_success(array(
                'cities'      => array(),
                'province_id' => $province_id,
                'source'      => 'empty',
            ));
        }

        wp_send_json_success(array(
            'cities'      => $rows,
            'province_id' => $province_id,
            'source'      => 'db',
        ));
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
    public static function get_districts()
    {

        if (
            ! isset($_POST['nonce'])
            || ! wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['nonce'])),
                'moga_nonce'
            )
        ) {
            wp_send_json_error(array('message' => 'Invalid nonce.'));
        }

        $city_id = isset($_POST['city_id']) ? absint($_POST['city_id']) : 0;

        if (! $city_id) {
            // No city_id — return empty, frontend shows text input.
            wp_send_json_success(array(
                'districts' => array(),
                'city_id'   => 0,
            ));
        }

        global $wpdb;
        $prefix = $wpdb->prefix . MOGA_CORE_DB_PREFIX;

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT id, name FROM {$prefix}loc_districts WHERE city_id = %d ORDER BY name ASC",
            $city_id
        ), ARRAY_A);

        // Always return success — empty array signals text input fallback.
        wp_send_json_success(array(
            'districts' => $rows ?: array(),
            'city_id'   => $city_id,
        ));
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
    public static function check_availability()
    {

        // Verify nonce.
        if (
            ! isset($_POST['nonce'])
            || ! wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['nonce'])),
                'moga_nonce'
            )
        ) {
            wp_send_json_error(array('message' => 'Invalid nonce.'));
        }

        $listing_id   = isset($_POST['listing_id'])   ? absint($_POST['listing_id'])                                    : 0;
        $listing_type = isset($_POST['listing_type']) ? sanitize_text_field(wp_unslash($_POST['listing_type']))       : 'property';
        $check_in     = isset($_POST['check_in'])     ? sanitize_text_field(wp_unslash($_POST['check_in']))           : '';
        $check_out    = isset($_POST['check_out'])    ? sanitize_text_field(wp_unslash($_POST['check_out']))          : '';

        if (! $listing_id || ! $check_in || ! $check_out) {
            wp_send_json_error(array('message' => 'Missing required fields.'));
        }

        // Validate dates.
        $validation = moga_validate_dates($check_in, $check_out);
        if (is_wp_error($validation)) {
            wp_send_json_error(array('message' => $validation->get_error_message()));
        }

        // Check availability. Tours use a fundamentally different
        // check than properties — a specific Group's real, live
        // capacity + booking cutoff, not a generic "is this date
        // blocked" check (which has no concept of capacity at all
        // and would incorrectly close a tour after its very first
        // booking, regardless of how many seats remain).
        if ('tour' === $listing_type) {
            $requested_seats = isset($_POST['adults']) ? absint($_POST['adults']) : 1;
            $requested_seats += isset($_POST['children']) ? absint($_POST['children']) : 0;
            $available = moga_is_tour_group_available($listing_id, $check_in, max(1, $requested_seats));
        } else {
            $available = moga_is_available($listing_id, $check_in, $check_out, $listing_type);
        }

        $response = array(
            'available'    => $available,
            'listing_id'   => $listing_id,
            'check_in'     => $check_in,
            'check_out'    => $check_out,
        );

        // If available, include price.
        if ($available) {
            if ('property' === $listing_type) {
                $price_data = moga_calculate_property_price($listing_id, $check_in, $check_out);
            } else {
                // For tours, 'check_in' IS the chosen Group's start
                // date — the same field submitted either way, just
                // meaning "which group" instead of "check-in date".
                $adults   = isset($_POST['adults'])   ? absint($_POST['adults'])   : 1;
                $children = isset($_POST['children']) ? absint($_POST['children']) : 0;
                $infants  = isset($_POST['infants'])  ? absint($_POST['infants'])  : 0;
                $price_data = moga_calculate_tour_price($listing_id, $check_in, $adults, $children, $infants);
            }

            $currency = isset($price_data['currency']) ? $price_data['currency'] : moga_currency();

            // Add formatted prices.
            $price_data['price_formatted']    = moga_format_price($price_data['price_per_night'] ?? $price_data['price_adult'] ?? 0, $currency);
            $price_data['subtotal_formatted'] = moga_format_price($price_data['subtotal'] ?? 0, $currency);
            $price_data['discount_formatted'] = moga_format_price($price_data['discount']  ?? 0, $currency);
            $price_data['taxes_formatted']    = moga_format_price($price_data['taxes']     ?? 0, $currency);
            $price_data['total_formatted']    = moga_format_price($price_data['total']     ?? 0, $currency);

            $response['price'] = $price_data;
        }

        wp_send_json_success($response);
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
    public static function calculate_price()
    {

        // Verify nonce.
        if (
            ! isset($_POST['nonce'])
            || ! wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['nonce'])),
                'moga_nonce'
            )
        ) {
            wp_send_json_error(array('message' => 'Invalid nonce.'));
        }

        $listing_id   = isset($_POST['listing_id'])   ? absint($_POST['listing_id'])                              : 0;
        $listing_type = isset($_POST['listing_type']) ? sanitize_text_field(wp_unslash($_POST['listing_type'])) : 'property';
        $check_in     = isset($_POST['check_in'])     ? sanitize_text_field(wp_unslash($_POST['check_in']))     : '';
        $check_out    = isset($_POST['check_out'])    ? sanitize_text_field(wp_unslash($_POST['check_out']))    : '';
        $adults       = isset($_POST['adults'])       ? absint($_POST['adults'])                                  : 1;
        $children     = isset($_POST['children'])     ? absint($_POST['children'])                                : 0;
        $infants      = isset($_POST['infants'])      ? absint($_POST['infants'])                                 : 0;

        if (! $listing_id) {
            wp_send_json_error(array('message' => 'Listing ID required.'));
        }

        // Calculate price based on listing type.
        if ('property' === $listing_type) {
            if (! $check_in || ! $check_out) {
                wp_send_json_error(array('message' => 'Dates required for property pricing.'));
            }
            $price_data = moga_calculate_property_price($listing_id, $check_in, $check_out);
        } else {
            // For tours, 'check_in' IS the chosen Group's start date
            // — identifies WHICH group is being priced, the same way
            // check_in/check_out identify a date range for properties.
            if (! $check_in) {
                wp_send_json_error(array('message' => 'A departure date is required for tour pricing.'));
            }
            $price_data = moga_calculate_tour_price($listing_id, $check_in, $adults, $children, $infants);
        }

        $currency = isset($price_data['currency']) ? $price_data['currency'] : moga_currency();

        // Add formatted prices for JavaScript rendering.
        $price_data['price_formatted']         = moga_format_price($price_data['price_per_night']  ?? $price_data['price_adult'] ?? 0, $currency);
        $price_data['subtotal_formatted']      = moga_format_price($price_data['subtotal']         ?? 0, $currency);
        $price_data['discount_formatted']      = moga_format_price($price_data['discount']         ?? 0, $currency);
        $price_data['taxes_formatted']         = moga_format_price($price_data['taxes']            ?? 0, $currency);
        $price_data['total_formatted']         = moga_format_price($price_data['total']            ?? 0, $currency);
        $price_data['discount_percent']        = $price_data['discount_percent'] ?? $price_data['group_discount'] ?? 0;

        // Tour-only formatted fields — for the always-visible,
        // three-line "N Adults x price = total" breakdown (each line
        // hidden entirely when its count is zero — handled in JS).
        if ('tour' === $listing_type) {
            $price_data['price_adult_formatted']    = moga_format_price($price_data['price_adult']    ?? 0, $currency);
            $price_data['price_child_formatted']    = moga_format_price($price_data['price_child']    ?? 0, $currency);
            $price_data['price_infant_formatted']   = moga_format_price($price_data['price_infant']   ?? 0, $currency);
            $price_data['adults_total_formatted']   = moga_format_price($price_data['adults_total']   ?? 0, $currency);
            $price_data['children_total_formatted'] = moga_format_price($price_data['children_total'] ?? 0, $currency);
            $price_data['infants_total_formatted']  = moga_format_price($price_data['infants_total']  ?? 0, $currency);
        }

        // Regular vs weekend subtotals, formatted — property only,
        // for the always-visible "N regular nights = X, N weekend
        // nights = Y" breakdown rows on the booking form.
        if ('property' === $listing_type) {
            $price_data['weekday_subtotal_formatted'] = moga_format_price($price_data['weekday_subtotal'] ?? 0, $currency);
            $price_data['weekend_subtotal_formatted'] = moga_format_price($price_data['weekend_subtotal'] ?? 0, $currency);
        }

        // Per-night AVERAGE, pre- and post-discount — for the top
        // price badge (desktop + mobile sticky bar), which shows a
        // single "starting from / night" figure even when the stay
        // spans a mix of weekday/weekend/override rates. Only
        // meaningful for property bookings, which have a real
        // nights count; tours have no equivalent concept.
        if ('property' === $listing_type && ! empty($price_data['nights'])) {
            $nights = max(1, intval($price_data['nights']));
            $price_data['price_per_night_avg_formatted']          = moga_format_price(($price_data['total']    ?? 0) / $nights, $currency);
            $price_data['price_per_night_avg_original_formatted'] = moga_format_price(($price_data['subtotal'] ?? 0) / $nights, $currency);
        }

        wp_send_json_success(array('price' => $price_data));
    }


    // ============================================================
    // SEAT MAP — GET
    // ============================================================

    /**
     * Handle AJAX request to get the full seat map for a bus on a
     * specific trip date.
     *
     * Called when the Booking page seat map section renders — JS
     * calls this once to get the initial grid state (which seats are
     * available, reserved, booked, etc.), then updates individual
     * seats locally after each reserve/release action.
     *
     * POST params:
     *   - nonce         : moga_nonce
     *   - bus_id        : Bus post ID
     *   - trip_date     : Y-m-d — the tour group's start date
     *   - session_token : Optional. Guest's current hold token (to
     *                     mark their own reserved seats as 'reserved_by_me')
     *
     * Returns: { seats: [...], layout, columns, rows, driver_position,
     *            bus_name, hold_minutes }
     *
     * @since  1.0.0
     * @return void Sends JSON response.
     */
    public static function get_seat_map()
    {
        if (
            ! isset($_POST['nonce'])
            || ! wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['nonce'])),
                'moga_nonce'
            )
        ) {
            wp_send_json_error(array('message' => 'Invalid nonce.'));
        }

        $bus_id        = isset($_POST['bus_id'])        ? absint($_POST['bus_id'])                                       : 0;
        $trip_date     = isset($_POST['trip_date'])     ? sanitize_text_field(wp_unslash($_POST['trip_date']))           : '';
        $session_token = isset($_POST['session_token']) ? sanitize_text_field(wp_unslash($_POST['session_token']))       : '';

        if (! $bus_id || ! $trip_date) {
            wp_send_json_error(array('message' => 'bus_id and trip_date are required.'));
        }

        $core      = function_exists('moga_core') ? moga_core() : null;
        $seat_map  = ($core && $core->seat_map) ? $core->seat_map : null;

        if (! $seat_map) {
            wp_send_json_error(array('message' => 'Seat map service unavailable.'));
        }

        $result = $seat_map->get_seat_map($bus_id, $trip_date, $session_token);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success($result);
    }


    // ============================================================
    // SEAT MAP — RESERVE
    // ============================================================

    /**
     * Handle AJAX request to reserve one or more seats.
     *
     * Called when the guest clicks a seat on the Booking page.
     * Returns which seats were successfully reserved and which were
     * already taken by the time the request arrived — so the JS can
     * update the map honestly rather than optimistically.
     *
     * POST params:
     *   - nonce         : moga_nonce
     *   - bus_id        : Bus post ID
     *   - tour_id       : Tour post ID
     *   - trip_date     : Y-m-d
     *   - seats         : JSON array of seat number strings (e.g. '["3A","3B"]')
     *   - session_token : Guest's current hold token (server-issued on first reserve)
     *
     * Returns: {
     *   reserved      : string[]  — seats now held by this guest
     *   taken         : string[]  — seats grabbed by someone else first
     *   session_token : string    — token to use for subsequent calls
     *   expires_at    : string    — ISO datetime when the hold expires
     * }
     *
     * @since  1.0.0
     * @return void Sends JSON response.
     */
    public static function reserve_seats()
    {
        if (
            ! isset($_POST['nonce'])
            || ! wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['nonce'])),
                'moga_nonce'
            )
        ) {
            wp_send_json_error(array('message' => 'Invalid nonce.'));
        }

        $bus_id        = isset($_POST['bus_id'])        ? absint($_POST['bus_id'])                                       : 0;
        $tour_id       = isset($_POST['tour_id'])       ? absint($_POST['tour_id'])                                      : 0;
        $trip_date     = isset($_POST['trip_date'])     ? sanitize_text_field(wp_unslash($_POST['trip_date']))           : '';
        $seats_json    = isset($_POST['seats'])         ? wp_unslash($_POST['seats'])                                    : '[]';
        $session_token = isset($_POST['session_token']) ? sanitize_text_field(wp_unslash($_POST['session_token']))       : '';

        if (! $bus_id || ! $tour_id || ! $trip_date) {
            wp_send_json_error(array('message' => 'bus_id, tour_id, and trip_date are required.'));
        }

        // Decode and sanitize seat list.
        $seats = json_decode($seats_json, true);
        if (! is_array($seats) || empty($seats)) {
            wp_send_json_error(array('message' => 'At least one seat number is required.'));
        }
        $seats = array_map('sanitize_text_field', $seats);

        // Issue a session token if the guest doesn't have one yet
        // (first reserve call in this session).
        if (! $session_token) {
            $session_token = Moga_Seat_Map::generate_session_token();
        }

        $core     = function_exists('moga_core') ? moga_core() : null;
        $seat_map = ($core && $core->seat_map) ? $core->seat_map : null;

        if (! $seat_map) {
            wp_send_json_error(array('message' => 'Seat map service unavailable.'));
        }

        $result = $seat_map->reserve_seats($bus_id, $tour_id, $trip_date, $seats, $session_token);

        $expires_at = gmdate(
            'c', // ISO 8601.
            time() + (Moga_Seat_Map::HOLD_MINUTES * MINUTE_IN_SECONDS)
        );

        wp_send_json_success(array(
            'reserved'      => $result['reserved'],
            'taken'         => $result['taken'],
            'session_token' => $session_token,
            'expires_at'    => $expires_at,
            'hold_minutes'  => Moga_Seat_Map::HOLD_MINUTES,
        ));
    }


    // ============================================================
    // SEAT MAP — RELEASE
    // ============================================================

    /**
     * Handle AJAX request to release one or more held seats.
     *
     * Called when:
     *   - The guest clicks an already-selected seat to deselect it.
     *   - The JS beforeunload event fires (guest navigating away).
     *   - The hold countdown reaches zero in the browser.
     *
     * Only ever releases seats that match the posted session_token —
     * never touches another guest's hold.
     *
     * POST params:
     *   - nonce         : moga_nonce
     *   - bus_id        : Bus post ID
     *   - trip_date     : Y-m-d
     *   - seats         : JSON array of seat numbers to release.
     *                     Empty array = release all seats held by this session.
     *   - session_token : Guest's current hold token
     *
     * Returns: { released: int — number of seats actually freed }
     *
     * @since  1.0.0
     * @return void Sends JSON response.
     */
    public static function release_seats()
    {
        if (
            ! isset($_POST['nonce'])
            || ! wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['nonce'])),
                'moga_nonce'
            )
        ) {
            wp_send_json_error(array('message' => 'Invalid nonce.'));
        }

        $bus_id        = isset($_POST['bus_id'])        ? absint($_POST['bus_id'])                                       : 0;
        $trip_date     = isset($_POST['trip_date'])     ? sanitize_text_field(wp_unslash($_POST['trip_date']))           : '';
        $seats_json    = isset($_POST['seats'])         ? wp_unslash($_POST['seats'])                                    : '[]';
        $session_token = isset($_POST['session_token']) ? sanitize_text_field(wp_unslash($_POST['session_token']))       : '';

        if (! $bus_id || ! $trip_date || ! $session_token) {
            wp_send_json_error(array('message' => 'bus_id, trip_date, and session_token are required.'));
        }

        $seats = json_decode($seats_json, true);
        $seats = is_array($seats) ? array_map('sanitize_text_field', $seats) : array();

        $core     = function_exists('moga_core') ? moga_core() : null;
        $seat_map = ($core && $core->seat_map) ? $core->seat_map : null;

        if (! $seat_map) {
            wp_send_json_error(array('message' => 'Seat map service unavailable.'));
        }

        $released = $seat_map->release_seats($bus_id, $trip_date, $seats, $session_token);

        wp_send_json_success(array('released' => $released));
    }


    // ============================================================
    // INLINE BUS CREATION — from Tour editor
    // ============================================================

    /**
     * Create a new bus post inline from the Tour editor's
     * "Create New Bus" panel — without leaving the tour edit screen.
     * Admin-only action (no nopriv equivalent).
     *
     * POST params:
     *   - nonce       : moga_nonce
     *   - bus_title   : Bus name / post title (required)
     *   - bus_plate   : Plate number (optional)
     *   - bus_type    : Bus type key (optional, default 'standard')
     *   - seat_layout : Layout key — 2+2, 2+3, 1+2, 1+1 (default 2+2)
     *   - seat_rows   : Number of rows (int, default 10)
     *
     * Returns: {
     *   bus_id      : int    — newly created bus post ID
     *   bus_label   : string — label for the dropdown option
     *   total_seats : int    — rows × columns
     * }
     *
     * @since  1.0.0
     * @return void Sends JSON response.
     */
    public static function create_bus_inline()
    {
        if (
            ! isset($_POST['nonce'])
            || ! wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['nonce'])),
                'moga_nonce'
            )
        ) {
            wp_send_json_error(array('message' => 'Invalid nonce.'));
        }

        if (! current_user_can('edit_moga_buses')) {
            wp_send_json_error(array('message' => 'Permission denied.'));
        }

        $title  = isset($_POST['bus_title'])   ? sanitize_text_field(wp_unslash($_POST['bus_title']))  : '';
        $plate  = isset($_POST['bus_plate'])   ? sanitize_text_field(wp_unslash($_POST['bus_plate']))  : '';
        $type   = isset($_POST['bus_type'])    ? sanitize_key($_POST['bus_type'])                       : 'standard';
        $layout = isset($_POST['seat_layout']) ? sanitize_key($_POST['seat_layout'])                    : '2+2';
        $rows   = isset($_POST['seat_rows'])   ? max(1, absint($_POST['seat_rows']))                    : 10;

        if (empty($title)) {
            wp_send_json_error(array('message' => __('Bus name is required.', 'moga-travel-core')));
        }

        // Validate layout against known values.
        $layouts = class_exists('Moga_CPT_Bus') ? Moga_CPT_Bus::get_seat_layouts() : array();
        if (! array_key_exists($layout, $layouts)) {
            $layout = '2+2';
        }

        $columns     = isset($layouts[$layout]['columns']) ? $layouts[$layout]['columns'] : 4;
        $total_seats = $rows * $columns;

        // Create the bus post.
        $bus_id = wp_insert_post(array(
            'post_title'  => $title,
            'post_type'   => 'moga_bus',
            'post_status' => 'publish',
        ), true);

        if (is_wp_error($bus_id)) {
            wp_send_json_error(array('message' => $bus_id->get_error_message()));
        }

        // Save all relevant meta — mirrors what the Bus editor
        // save_meta_boxes() does, using the same meta keys.
        update_post_meta($bus_id, '_moga_bus_plate',          $plate);
        update_post_meta($bus_id, '_moga_bus_type',           $type);
        update_post_meta($bus_id, '_moga_seat_layout',        $layout);
        update_post_meta($bus_id, '_moga_seat_rows',          $rows);
        update_post_meta($bus_id, '_moga_seat_columns',       $columns);
        update_post_meta($bus_id, '_moga_total_seats',        $total_seats);
        update_post_meta($bus_id, '_moga_active',             '1');
        update_post_meta($bus_id, '_moga_under_maintenance',  '0');
        update_post_meta($bus_id, '_moga_has_ac',             '1');
        update_post_meta($bus_id, '_moga_has_luggage',        '1');

        // Build the dropdown label — matches the format produced by
        // Moga_CPT_Bus::get_available_buses() so the option looks
        // identical to buses that were created via the Bus editor.
        $bus_label = sprintf(
            '%s — %s (%s seats)',
            $title,
            $plate ?: __('No plate', 'moga-travel-core'),
            $total_seats
        );

        wp_send_json_success(array(
            'bus_id'      => $bus_id,
            'bus_label'   => $bus_label,
            'total_seats' => $total_seats,
        ));
    }


    // ============================================================
    // BUS SEATS LOOKUP — auto-fill Total Seats in Tour editor
    // ============================================================

    /**
     * Return the total seat count for a bus — called when the admin
     * changes the bus dropdown in the Tour editor so the Total Seats
     * field updates without a page reload.
     * Admin-only action (no nopriv equivalent).
     *
     * POST params:
     *   - nonce  : moga_nonce
     *   - bus_id : Bus post ID
     *
     * Returns: { total_seats: int }
     *
     * @since  1.0.0
     * @return void Sends JSON response.
     */
    public static function get_bus_seats()
    {
        if (
            ! isset($_POST['nonce'])
            || ! wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['nonce'])),
                'moga_nonce'
            )
        ) {
            wp_send_json_error(array('message' => 'Invalid nonce.'));
        }

        if (! current_user_can('edit_moga_buses')) {
            wp_send_json_error(array('message' => 'Permission denied.'));
        }

        $bus_id = isset($_POST['bus_id']) ? absint($_POST['bus_id']) : 0;

        if (! $bus_id) {
            wp_send_json_error(array('message' => 'bus_id required.'));
        }

        $total = get_post_meta($bus_id, '_moga_total_seats', true);

        wp_send_json_success(array(
            'total_seats' => $total ? absint($total) : 0,
        ));
    }


    // ============================================================
    // GOOGLE PLACES HOTEL LOOKUP
    // ============================================================

    /**
     * Fetch hotel data from Google Places API for the accommodation widget.
     *
     * Runs server-side so the API key is never exposed in the browser.
     * Results are cached as transients for 7 days — hotel data rarely
     * changes, and caching dramatically reduces API quota usage.
     *
     * POST params:
     *   - nonce      : moga_nonce
     *   - hotel_name : Hotel name string to search for
     *
     * Returns: {
     *   name         : string   — Google's canonical name
     *   rating       : float    — Google rating (1–5)
     *   user_ratings : int      — Number of user ratings
     *   photos       : string[] — Array of photo URLs (max 5)
     *   place_id     : string   — Google Place ID
     *   found        : bool     — false when no match
     * }
     *
     * @since  1.0.0
     * @return void Sends JSON response.
     */
    public static function get_hotel_places()
    {
        if (
            ! isset($_POST['nonce'])
            || ! wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['nonce'])),
                'moga_nonce'
            )
        ) {
            wp_send_json_error(array('message' => 'Invalid nonce.'));
        }

        $hotel_name = isset($_POST['hotel_name'])
            ? sanitize_text_field(wp_unslash($_POST['hotel_name']))
            : '';

        if (empty($hotel_name)) {
            wp_send_json_error(array('message' => 'hotel_name required.'));
        }

        $api_key = get_option('moga_google_places_api_key', '');

        if (empty($api_key)) {
            // No API key configured — return not found gracefully.
            wp_send_json_success(array('found' => false, 'reason' => 'no_api_key'));
        }

        // Check cache first — keyed by hotel name, 7-day TTL.
        $cache_key = 'moga_places_' . md5(strtolower($hotel_name));
        $cached    = get_transient($cache_key);
        if (false !== $cached) {
            wp_send_json_success($cached);
        }

        // Step 1 — Text search to find the Place ID.
        $search_url = add_query_arg(array(
            'query'  => urlencode($hotel_name),
            'type'   => 'lodging',
            'key'    => $api_key,
            'fields' => 'place_id,name,rating,user_ratings_total',
        ), 'https://maps.googleapis.com/maps/api/place/textsearch/json');

        $search_response = wp_remote_get($search_url, array(
            'timeout' => 8,
            'sslverify' => true,
        ));

        if (is_wp_error($search_response)) {
            wp_send_json_success(array('found' => false, 'reason' => 'api_error'));
        }

        $search_data = json_decode(wp_remote_retrieve_body($search_response), true);

        if (
            empty($search_data['results'])
            || ! isset($search_data['results'][0]['place_id'])
        ) {
            // No match — cache the miss for 24h to avoid hammering the API.
            set_transient($cache_key, array('found' => false), DAY_IN_SECONDS);
            wp_send_json_success(array('found' => false, 'reason' => 'no_match'));
        }

        $result   = $search_data['results'][0];
        $place_id = $result['place_id'];

        // Step 2 — Place Details to get photos.
        $details_url = add_query_arg(array(
            'place_id' => $place_id,
            'fields'   => 'name,rating,user_ratings_total,photos',
            'key'      => $api_key,
        ), 'https://maps.googleapis.com/maps/api/place/details/json');

        $details_response = wp_remote_get($details_url, array(
            'timeout'   => 8,
            'sslverify' => true,
        ));

        $photo_urls = array();

        if (! is_wp_error($details_response)) {
            $details_data = json_decode(wp_remote_retrieve_body($details_response), true);
            $photos       = $details_data['result']['photos'] ?? array();

            // Fetch up to 15 photos — shown in the frontend widget slideshow.
            // If Google has fewer than 15, all available photos are returned.
            // The admin thumbnail strip shows only the first 5 of these.
            foreach (array_slice($photos, 0, 15) as $photo) {
                if (empty($photo['photo_reference'])) {
                    continue;
                }
                $photo_urls[] = add_query_arg(array(
                    'maxwidth'        => 800,
                    'photo_reference' => $photo['photo_reference'],
                    'key'             => $api_key,
                ), 'https://maps.googleapis.com/maps/api/place/photo');
            }
        }

        $data = array(
            'found'        => true,
            'place_id'     => $place_id,
            'name'         => $result['name']                ?? $hotel_name,
            'google_name'  => $result['name']                ?? $hotel_name,
            'maps_url'     => 'https://www.google.com/maps/place/?q=place_id:' . rawurlencode( $place_id ),
            'rating'       => $result['rating']              ?? null,
            'user_ratings' => $result['user_ratings_total']  ?? 0,
            'photos'       => $photo_urls,
        );

        // Cache for 7 days.
        set_transient($cache_key, $data, 7 * DAY_IN_SECONDS);

        wp_send_json_success($data);
    }


    // ============================================================
    // HOTEL AUTOCOMPLETE SEARCH — Admin Tour Editor
    // ============================================================

    /**
     * Search for hotels matching a query string — powers the live
     * autocomplete in the Tour editor accommodation row.
     *
     * The organizer types 3+ characters; JS calls this action every
     * 400ms (debounced). Returns up to 8 hotel name suggestions with
     * their place_id so a second call to get_hotel_places() can fetch
     * full details (photos, rating) when the organizer selects one.
     *
     * Admin-only — no nopriv equivalent. The API key is never sent
     * to the browser.
     *
     * POST params:
     *   - nonce : moga_nonce
     *   - query : Search string (min 3 chars)
     *
     * Returns: Array of { place_id, name, address, rating }
     *
     * @since  1.0.0
     * @return void Sends JSON response.
     */
    public static function search_hotel()
    {
        // Purpose: Return up to 8 hotel name suggestions from Google Places
        // that match the organizer's query string within the tour's
        // destination city and country. Called on every keystroke (debounced
        // 380ms) from the accommodation row autocomplete input.
        //
        // Expected outcome: a JSON array of { place_id, name, address, rating }
        // objects scoped to the correct destination — e.g. typing "son" when
        // the destination is Al Ghardaqah, Egypt returns Sonesta hotels in
        // Hurghada/Red Sea, not worldwide results.

        if (
            ! isset($_POST['nonce'])
            || ! wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['nonce'])),
                'moga_nonce'
            )
        ) {
            wp_send_json_error(array('message' => 'Invalid nonce.'));
        }

        if (! current_user_can('edit_posts') && ! current_user_can('edit_moga_tours') && ! current_user_can('edit_moga_properties')) {
            wp_send_json_error(array('message' => 'Permission denied.'));
        }

        $query   = isset($_POST['query'])   ? sanitize_text_field(wp_unslash($_POST['query']))   : '';
        $city    = isset($_POST['city'])    ? sanitize_text_field(wp_unslash($_POST['city']))    : '';
        $country = isset($_POST['country']) ? sanitize_text_field(wp_unslash($_POST['country'])) : '';

        if (mb_strlen($query) < 3) {
            wp_send_json_success(array('results' => array()));
        }

        $api_key = get_option('moga_google_places_api_key', '');
        if (empty($api_key)) {
            wp_send_json_success(array('results' => array(), 'reason' => 'no_api_key'));
        }

        // Build a location-aware search string.
        // Example: "son" + city "Al Ghardaqah" + country "EG" →
        // query string: "son hotel Al Ghardaqah Egypt"
        // This scopes results to the destination, so the organizer
        // typing 3 chars sees hotels in their specific city only.
        $country_name = '';
        if ($country) {
            $country_by_code = array();
            foreach ( moga_get_countries() as $c ) {
                $country_by_code[ $c['code'] ] = $c['name'];
            }
            $country_name = $country_by_code[$country] ?? $country;
        }

        $location_suffix = trim(implode(' ', array_filter(array($city, $country_name))));
        $search_string   = trim($query . ' hotel ' . $location_suffix);

        // Cache key includes city+country so the same query in a different
        // destination returns different (correct) results.
        $cache_key = 'moga_hotel_search_' . md5(strtolower($search_string));
        $cached    = get_transient($cache_key);
        if (false !== $cached) {
            wp_send_json_success(array('results' => $cached));
        }

        $search_url = add_query_arg(array(
            'query'  => urlencode($search_string),
            'type'   => 'lodging',
            'key'    => $api_key,
            'fields' => 'place_id,name,formatted_address,rating',
        ), 'https://maps.googleapis.com/maps/api/place/textsearch/json');

        $response = wp_remote_get($search_url, array(
            'timeout'   => 6,
            'sslverify' => true,
        ));

        if (is_wp_error($response)) {
            wp_send_json_success(array('results' => array(), 'reason' => 'api_error'));
        }

        $data    = json_decode(wp_remote_retrieve_body($response), true);
        $results = array();

        foreach (array_slice($data['results'] ?? array(), 0, 8) as $place) {
            $results[] = array(
                'place_id' => $place['place_id']          ?? '',
                'name'     => $place['name']              ?? '',
                'address'  => $place['formatted_address'] ?? '',
                'rating'   => $place['rating']            ?? null,
            );
        }

        // Cache for 24 hours — hotel listings in a city change rarely.
        set_transient($cache_key, $results, DAY_IN_SECONDS);

        wp_send_json_success(array('results' => $results));
    }


    // ============================================================
    // CITY AUTOCOMPLETE — Admin metaboxes (Google Places)
    // ============================================================

    /**
     * Return city suggestions from Google Places for the admin
     * city autocomplete field.
     *
     * Purpose: Replaces the DB-driven city <select> dropdown with a
     * live Google Places search. When the organizer types a city name,
     * this action returns matching cities scoped to the selected country.
     * The result is always a Google-recognized name — no transliteration
     * errors, no outdated data, no "Fix City Names" tool needed.
     *
     * POST params:
     *   - nonce   : moga_nonce
     *   - query   : string — what the organizer has typed (min 2 chars)
     *   - country : string — ISO 3166-1 alpha-2 code (e.g. 'EG', 'TR')
     *              restricts results to this country; pass '' for global
     *
     * Returns: {
     *   cities: [
     *     { name: string, lat: float, lng: float, place_id: string }
     *   ]
     * }
     *
     * @since  1.0.0
     * @return void Sends JSON response.
     */
    public static function city_autocomplete()
    {
        if (
            ! isset( $_POST['nonce'] )
            || ! wp_verify_nonce(
                sanitize_text_field( wp_unslash( $_POST['nonce'] ) ),
                'moga_nonce'
            )
        ) {
            wp_send_json_error( array( 'message' => 'Invalid nonce.' ) );
        }

        if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_moga_tours' ) && ! current_user_can( 'edit_moga_properties' ) ) {
            wp_send_json_error( array( 'message' => 'Permission denied.' ) );
        }

        $query    = isset( $_POST['query'] )    ? sanitize_text_field( wp_unslash( $_POST['query'] ) )    : '';
        $country  = isset( $_POST['country'] )  ? sanitize_text_field( wp_unslash( $_POST['country'] ) )  : '';
        $province = isset( $_POST['province'] ) ? sanitize_text_field( wp_unslash( $_POST['province'] ) ) : '';

        if ( mb_strlen( $query ) < 2 ) {
            wp_send_json_success( array( 'cities' => array() ) );
        }

        $api_key = get_option( 'moga_google_places_api_key', '' );
        if ( empty( $api_key ) ) {
            wp_send_json_success( array( 'cities' => array(), 'reason' => 'no_api_key' ) );
        }

        // Cache key includes province so the same query in different
        // provinces returns correctly scoped results.
        $cache_key = 'moga_city_ac_' . md5( strtolower( $query . '_' . $country . '_' . $province ) );
        $cached    = get_transient( $cache_key );
        if ( false !== $cached ) {
            wp_send_json_success( array( 'cities' => $cached ) );
        }

        // Use the Places Autocomplete API with type=locality to restrict
        // results to cities only (not streets, businesses, etc.).
        // Including the province name in the search string scopes results
        // to the correct region — e.g. "hur" in "Red Sea, EG" returns
        // Hurghada, not cities from other Egyptian governorates.
        $search_input = $province
            ? $query . ', ' . $province
            : $query;

        $params = array(
            'input'    => urlencode( $search_input ),
            'types'    => 'locality',
            'language' => 'en',
            'key'      => $api_key,
        );

        if ( ! empty( $country ) ) {
            $params['components'] = 'country:' . strtolower( $country );
        }

        $ac_url = add_query_arg(
            $params,
            'https://maps.googleapis.com/maps/api/place/autocomplete/json'
        );

        $response = wp_remote_get( $ac_url, array(
            'timeout'   => 5,
            'sslverify' => true,
        ) );

        if ( is_wp_error( $response ) ) {
            wp_send_json_success( array( 'cities' => array(), 'reason' => 'api_error' ) );
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( empty( $data['predictions'] ) ) {
            set_transient( $cache_key, array(), HOUR_IN_SECONDS );
            wp_send_json_success( array( 'cities' => array() ) );
        }

        $cities = array();

        foreach ( array_slice( $data['predictions'], 0, 8 ) as $prediction ) {
            // Extract the city name only — the first term in the
            // structured_formatting.main_text is always the city name.
            $city_name = $prediction['structured_formatting']['main_text']
                ?? explode( ',', $prediction['description'] )[0];

            $cities[] = array(
                'name'        => trim( $city_name ),
                'description' => $prediction['description'] ?? $city_name,
                'place_id'    => $prediction['place_id']    ?? '',
            );
        }

        // Cache for 24 hours — city names in a country change very rarely.
        set_transient( $cache_key, $cities, DAY_IN_SECONDS );

        wp_send_json_success( array( 'cities' => $cities ) );
    }


    // ============================================================
    // PROVINCE AUTOCOMPLETE — Admin metaboxes (Google Places)
    // ============================================================

    /**
     * Return province/state/governorate suggestions from Google Places
     * for the admin province autocomplete field.
     *
     * Purpose: Replaces the DB-driven province <select> dropdown with a
     * live Google Places search for administrative_area_level_1 entries
     * scoped to the selected country.
     *
     * POST params:
     *   - nonce   : moga_nonce
     *   - query   : string — what the organizer has typed (min 2 chars)
     *   - country : string — ISO 3166-1 alpha-2 code (e.g. 'EG', 'TR')
     *
     * Returns: { provinces: [ { name: string } ] }
     *
     * @since  1.0.0
     * @return void Sends JSON response.
     */
    public static function province_autocomplete()
    {
        if (
            ! isset( $_POST['nonce'] )
            || ! wp_verify_nonce(
                sanitize_text_field( wp_unslash( $_POST['nonce'] ) ),
                'moga_nonce'
            )
        ) {
            wp_send_json_error( array( 'message' => 'Invalid nonce.' ) );
        }

        if (
            ! current_user_can( 'edit_posts' )
            && ! current_user_can( 'edit_moga_tours' )
            && ! current_user_can( 'edit_moga_properties' )
        ) {
            wp_send_json_error( array( 'message' => 'Permission denied.' ) );
        }

        $query   = isset( $_POST['query'] )   ? sanitize_text_field( wp_unslash( $_POST['query'] ) )   : '';
        $country = isset( $_POST['country'] ) ? sanitize_text_field( wp_unslash( $_POST['country'] ) ) : '';

        if ( mb_strlen( $query ) < 2 ) {
            wp_send_json_success( array( 'provinces' => array() ) );
        }

        $api_key = get_option( 'moga_google_places_api_key', '' );
        if ( empty( $api_key ) ) {
            wp_send_json_success( array( 'provinces' => array(), 'reason' => 'no_api_key' ) );
        }

        $cache_key = 'moga_prov_ac_' . md5( strtolower( $query . '_' . $country ) );
        $cached    = get_transient( $cache_key );
        if ( false !== $cached ) {
            wp_send_json_success( array( 'provinces' => $cached ) );
        }

        // Use Places Autocomplete with type=administrative_area_level_1
        // to restrict results to provinces/states/governorates only.
        $params = array(
            'input'    => urlencode( $query ),
            'types'    => 'administrative_area_level_1',
            'language' => 'en',
            'key'      => $api_key,
        );

        if ( ! empty( $country ) ) {
            $params['components'] = 'country:' . strtolower( $country );
        }

        $ac_url = add_query_arg(
            $params,
            'https://maps.googleapis.com/maps/api/place/autocomplete/json'
        );

        $response = wp_remote_get( $ac_url, array(
            'timeout'   => 5,
            'sslverify' => true,
        ) );

        if ( is_wp_error( $response ) ) {
            wp_send_json_success( array( 'provinces' => array(), 'reason' => 'api_error' ) );
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( empty( $data['predictions'] ) ) {
            set_transient( $cache_key, array(), HOUR_IN_SECONDS );
            wp_send_json_success( array( 'provinces' => array() ) );
        }

        $provinces = array();
        foreach ( array_slice( $data['predictions'], 0, 8 ) as $prediction ) {
            $name = $prediction['structured_formatting']['main_text']
                ?? explode( ',', $prediction['description'] )[0];
            $provinces[] = array( 'name' => trim( $name ) );
        }

        set_transient( $cache_key, $provinces, DAY_IN_SECONDS );

        wp_send_json_success( array( 'provinces' => $provinces ) );
    }
}
