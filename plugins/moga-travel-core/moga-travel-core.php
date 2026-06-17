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

// ============================================================
// SECURITY: Block direct file access
// ============================================================
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ============================================================
// CONSTANTS
// ============================================================

/**
 * Plugin version.
 * Bump this number on every release.
 */
define( 'MOGA_CORE_VERSION', '1.0.0' );

/**
 * Minimum WordPress version required.
 */
define( 'MOGA_CORE_MIN_WP', '6.0' );

/**
 * Minimum PHP version required.
 */
define( 'MOGA_CORE_MIN_PHP', '7.4' );

/**
 * Absolute path to the plugin root directory.
 * Example: E:/xampp/htdocs/moga/wp-content/plugins/moga-travel-core/
 */
define( 'MOGA_CORE_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Public URL to the plugin root directory.
 * Example: http://localhost/moga/wp-content/plugins/moga-travel-core/
 */
define( 'MOGA_CORE_URL', plugin_dir_url( __FILE__ ) );

/**
 * Absolute path to the plugin main file.
 */
define( 'MOGA_CORE_FILE', __FILE__ );

/**
 * Plugin basename.
 * Example: moga-travel-core/moga-travel-core.php
 */
define( 'MOGA_CORE_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Database table prefix for all Moga custom tables.
 * All custom tables will be named: mg_moga_bookings, mg_moga_seats, etc.
 */
define( 'MOGA_CORE_DB_PREFIX', 'moga_' );


// ============================================================
// ENVIRONMENT CHECK
// ============================================================

/**
 * Check if server meets minimum requirements.
 * If not, show admin notice and stop loading the plugin.
 *
 * @return bool True if requirements are met.
 */
function moga_core_check_requirements() {

    $errors = array();

    // Check PHP version.
    if ( version_compare( PHP_VERSION, MOGA_CORE_MIN_PHP, '<' ) ) {
        $errors[] = sprintf(
            /* translators: 1: Required PHP version, 2: Current PHP version */
            __( 'Moga Travel Core requires PHP %1$s or higher. Your server is running PHP %2$s.', 'moga-travel-core' ),
            MOGA_CORE_MIN_PHP,
            PHP_VERSION
        );
    }

    // Check WordPress version.
    if ( version_compare( get_bloginfo( 'version' ), MOGA_CORE_MIN_WP, '<' ) ) {
        $errors[] = sprintf(
            /* translators: 1: Required WP version, 2: Current WP version */
            __( 'Moga Travel Core requires WordPress %1$s or higher. You are running WordPress %2$s.', 'moga-travel-core' ),
            MOGA_CORE_MIN_WP,
            get_bloginfo( 'version' )
        );
    }

    // If there are errors, show them and return false.
    if ( ! empty( $errors ) ) {
        add_action(
            'admin_notices',
            function() use ( $errors ) {
                foreach ( $errors as $error ) {
                    echo '<div class="notice notice-error"><p><strong>Moga Travel Core:</strong> '
                        . esc_html( $error )
                        . '</p></div>';
                }
            }
        );
        return false;
    }

    return true;
}


// ============================================================
// AUTOLOADER
// ============================================================

/**
 * PSR-4 inspired autoloader for all Moga plugin classes.
 *
 * Naming convention:
 *   Class name:  Moga_Core        → File: class-moga-core.php
 *   Class name:  Moga_Booking     → File: class-moga-booking.php
 *   Class name:  Moga_CPT_Property → File: class-cpt-property.php
 *
 * @param string $class_name The fully-qualified class name.
 */
function moga_core_autoloader( $class_name ) {

    // Only autoload Moga classes.
    if ( strpos( $class_name, 'Moga_' ) !== 0 ) {
        return;
    }

    // Convert class name to file name.
    // Moga_Core            → class-moga-core.php
    // Moga_CPT_Property    → class-cpt-property.php
    $file_name = 'class-' . strtolower(
        str_replace( '_', '-', $class_name )
    ) . '.php';

    // Map of directories to search for class files.
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

    // Search each directory for the file.
    foreach ( $directories as $directory ) {
        $file_path = $directory . $file_name;
        if ( file_exists( $file_path ) ) {
            require_once $file_path;
            return;
        }
    }
}

// Register autoloader.
spl_autoload_register( 'moga_core_autoloader' );


// ============================================================
// ACTIVATION HOOK
// ============================================================

/**
 * Fires when the plugin is activated.
 *
 * - Creates custom database tables.
 * - Sets default plugin options.
 * - Creates required pages (Booking, Search, Dashboard).
 * - Flushes rewrite rules for custom post types.
 */
function moga_core_activate() {
    // Requirements must pass before activating.
    if ( ! moga_core_check_requirements() ) {
        // Deactivate the plugin immediately.
        deactivate_plugins( MOGA_CORE_BASENAME );
        wp_die(
            esc_html__(
                'Moga Travel Core could not be activated because your server does not meet the minimum requirements. Please check the admin notices for details.',
                'moga-travel-core'
            )
        );
    }

    require_once MOGA_CORE_PATH . 'includes/classes/class-moga-activator.php';
    Moga_Activator::activate();
}

register_activation_hook( MOGA_CORE_FILE, 'moga_core_activate' );


// ============================================================
// DEACTIVATION HOOK
// ============================================================

/**
 * Fires when the plugin is deactivated.
 *
 * - Flushes rewrite rules.
 * - Clears scheduled cron jobs.
 * Does NOT delete data — that happens only on uninstall.
 */
function moga_core_deactivate() {
    require_once MOGA_CORE_PATH . 'includes/classes/class-moga-deactivator.php';
    Moga_Deactivator::deactivate();
}

register_deactivation_hook( MOGA_CORE_FILE, 'moga_core_deactivate' );


// ============================================================
// BOOT PLUGIN
// ============================================================

/**
 * Initialize and return the main plugin instance.
 * Runs on the 'plugins_loaded' hook to ensure WordPress
 * and all other plugins are fully loaded first.
 *
 * @return Moga_Core|null Plugin instance or null if requirements fail.
 */
function moga_core_init() {

    // Run requirements check.
    if ( ! moga_core_check_requirements() ) {
        return null;
    }

    // Load plugin text domain for translations.
    load_plugin_textdomain(
        'moga-travel-core',
        false,
        dirname( MOGA_CORE_BASENAME ) . '/languages/'
    );

    // Boot the main plugin class (singleton).
    return Moga_Core::get_instance();
}

add_action( 'plugins_loaded', 'moga_core_init' );


// ============================================================
// GLOBAL HELPER FUNCTION
// ============================================================

/**
 * Global helper to access the Moga_Core instance from anywhere.
 *
 * Usage:
 *   moga_core()->booking   → Access booking class
 *   moga_core()->payment   → Access payment class
 *
 * @return Moga_Core|null
 */
function moga_core() {
    return Moga_Core::get_instance();
}