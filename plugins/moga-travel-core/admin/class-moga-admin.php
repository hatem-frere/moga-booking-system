<?php
/**
 * Admin Main Class
 *
 * Bootstraps all admin-facing components.
 * Instantiated from Moga_Core::boot_admin() on the
 * 'init' hook when is_admin() returns true.
 *
 * Initialisation order:
 *   1. Moga_Admin_Menus   — registers the admin menu tree
 *   2. Moga_Admin_Locations — registers location AJAX + assets
 *   3. Moga_Admin_Settings  — registers settings AJAX + assets
 *
 * @package    MogaTravelCore
 * @subpackage MogaTravelCore/admin
 * @author     Hatem Frere
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Moga_Admin
 */
class Moga_Admin {

    /**
     * Boot all admin components.
     *
     * @since  1.0.0
     */
    public function __construct() {
        $this->init_components();
    }

    /**
     * Initialise each admin component class.
     *
     * Each component is responsible for hooking itself into
     * WordPress (admin_menu, admin_enqueue_scripts, wp_ajax_, etc.)
     * via its own init() static method.
     *
     * @since  1.0.0
     * @return void
     */
    private function init_components() {

        // Admin menu tree — must run before other components
        // so submenus are registered in the correct order.
        if ( class_exists( 'Moga_Admin_Menus' ) ) {
            Moga_Admin_Menus::init();
        }

        // Location Settings wizard and Location Editor.
        if ( class_exists( 'Moga_Admin_Locations' ) ) {
            Moga_Admin_Locations::init();
        }

        // Plugin settings panel (Phase 6).
        if ( class_exists( 'Moga_Admin_Settings' ) ) {
            Moga_Admin_Settings::init();
        }
    }
}