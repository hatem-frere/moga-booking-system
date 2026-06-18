<?php
/**
 * Plugin Name:       Moga Travel Core
 * Plugin URI:        https://github.com/hatem-frere/moga-booking-system
 * Description:       The core engine of the Moga Booking System. Handles all booking logic, custom post types, availability calendars, payment processing, bus seat reservations, user roles, and REST API endpoints.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Hatem Frere
 * Author URI:        https://github.com/hatem-frere
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       moga-travel-core
 * Domain Path:       /languages
 *
 * @package MogaTravelCore
 */

// Block direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


// ============================================================
// CONSTANTS
// ============================================================

define( 'MOGA_CORE_VERSION',   '1.0.0' );
define( 'MOGA_CORE_MIN_WP',    '6.0' );
define( 'MOGA_CORE_MIN_PHP',   '7.4' );
define( 'MOGA_CORE_PATH',      plugin_dir_path( __FILE__ ) );
define( 'MOGA_CORE_URL',       plugin_dir_url( __FILE__ ) );
define( 'MOGA_CORE_FILE',      __FILE__ );
define( 'MOGA_CORE_BASENAME',  plugin_basename( __FILE__ ) );
define( 'MOGA_CORE_DB_PREFIX', 'moga_' );


// ============================================================
// ENVIRONMENT CHECK
// ============================================================

function moga_core_check_requirements() {

    $errors = array();

    if ( version_compare( PHP_VERSION, MOGA_CORE_MIN_PHP, '<' ) ) {
        $errors[] = sprintf(
            __( 'Moga Travel Core requires PHP %1$s or higher. Your server is running PHP %2$s.', 'moga-travel-core' ),
            MOGA_CORE_MIN_PHP,
            PHP_VERSION
        );
    }

    if ( version_compare( get_bloginfo( 'version' ), MOGA_CORE_MIN_WP, '<' ) ) {
        $errors[] = sprintf(
            __( 'Moga Travel Core requires WordPress %1$s or higher. You are running WordPress %2$s.', 'moga-travel-core' ),
            MOGA_CORE_MIN_WP,
            get_bloginfo( 'version' )
        );
    }

    if ( ! empty( $errors ) ) {
        add_action( 'admin_notices', function() use ( $errors ) {
            foreach ( $errors as $error ) {
                echo '<div class="notice notice-error"><p><strong>Moga Travel Core:</strong> '
                    . esc_html( $error ) . '</p></div>';
            }
        } );
        return false;
    }

    return true;
}


// ============================================================
// AUTOLOADER
// ============================================================

/**
 * Autoloader for all Moga plugin classes.
 *
 * Naming convention — class name to filename:
 *
 *   Moga_Core                  → class-moga-core.php
 *   Moga_Booking               → class-moga-booking.php
 *   Moga_CPT_Property          → class-moga-cpt-property.php
 *   Moga_CPT_Tour              → class-moga-cpt-tour.php
 *   Moga_CPT_Bus               → class-moga-cpt-bus.php
 *   Moga_CPT_Destination       → class-moga-cpt-destination.php
 *   Moga_CPT_Amenity           → class-moga-cpt-amenity.php
 *   Moga_Tax_Property_Type     → class-moga-tax-property-type.php
 *   Moga_Tax_Location          → class-moga-tax-location.php
 *   Moga_Tax_Tour_Category     → class-moga-tax-tour-category.php
 *   Moga_Shortcode_Search      → class-moga-shortcode-search.php
 *   Moga_Shortcode_Listing     → class-moga-shortcode-listing.php
 *   Moga_Shortcode_Booking_Form→ class-moga-shortcode-booking-form.php
 *   Moga_Rest_Api              → class-moga-rest-api.php
 *   Moga_Rest_Properties       → class-moga-rest-properties.php
 *   Moga_Rest_Bookings         → class-moga-rest-bookings.php
 *   Moga_Rest_Tours            → class-moga-rest-tours.php
 *
 * Rule: Replace ALL underscores with hyphens,
 *       lowercase everything, add class- prefix.
 *
 * @param string $class_name The class name to load.
 */
function moga_core_autoloader( $class_name ) {

    // Only autoload Moga classes.
    if ( strpos( $class_name, 'Moga_' ) !== 0 ) {
        return;
    }

    // Convert class name to filename.
    // Moga_CPT_Property → class-moga-cpt-property.php
    // Moga_Core         → class-moga-core.php
    $file_name = 'class-' . strtolower(
        str_replace( '_', '-', $class_name )
    ) . '.php';

    // Directories to search.
    $directories = array(
        MOGA_CORE_PATH . 'includes/classes/',
        MOGA_CORE_PATH . 'includes/post-types/',
        MOGA_CORE_PATH . 'includes/taxonomies/',
        MOGA_CORE_PATH . 'includes/shortcodes/',
        MOGA_CORE_PATH . 'includes/widgets/',
        MOGA_CORE_PATH . 'includes/api/',
        MOGA_CORE_PATH . 'admin/',
        MOGA_CORE_PATH . 'public/',
        MOGA_CORE_PATH . 'database/',
    );

    foreach ( $directories as $directory ) {
        $file_path = $directory . $file_name;
        if ( file_exists( $file_path ) ) {
            require_once $file_path;
            return;
        }
    }
}

spl_autoload_register( 'moga_core_autoloader' );


// ============================================================
// ACTIVATION HOOK
// ============================================================

function moga_core_activate() {

    if ( ! moga_core_check_requirements() ) {
        deactivate_plugins( MOGA_CORE_BASENAME );
        wp_die( esc_html__( 'Moga Travel Core could not be activated because your server does not meet the minimum requirements.', 'moga-travel-core' ) );
    }

    require_once MOGA_CORE_PATH . 'includes/classes/class-moga-activator.php';
    Moga_Activator::activate();
}

register_activation_hook( MOGA_CORE_FILE, 'moga_core_activate' );


// ============================================================
// DEACTIVATION HOOK
// ============================================================

function moga_core_deactivate() {
    require_once MOGA_CORE_PATH . 'includes/classes/class-moga-deactivator.php';
    Moga_Deactivator::deactivate();
}

register_deactivation_hook( MOGA_CORE_FILE, 'moga_core_deactivate' );


// ============================================================
// BOOT PLUGIN
// ============================================================

function moga_core_init() {

    if ( ! moga_core_check_requirements() ) {
        return null;
    }

    load_plugin_textdomain(
        'moga-travel-core',
        false,
        dirname( MOGA_CORE_BASENAME ) . '/languages/'
    );

    return Moga_Core::get_instance();
}

add_action( 'plugins_loaded', 'moga_core_init' );


// ============================================================
// GLOBAL HELPER
// ============================================================

function moga_core() {
    return Moga_Core::get_instance();
}