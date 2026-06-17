<?php
/**
 * Moga Travel — Theme Functions
 *
 * This is the theme's entry point. It loads all classes,
 * registers navigation menus, widget areas, and boots
 * the theme setup.
 *
 * @package    MogaTravel
 * @author     Hatem Frere
 * @version    1.0.0
 */

// Block direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


// ============================================================
// CONSTANTS
// ============================================================

/**
 * Theme version — bump on every release.
 */
define( 'MOGA_THEME_VERSION', '1.0.0' );

/**
 * Absolute path to the theme root directory.
 * Example: E:/xampp/htdocs/moga/wp-content/themes/moga-travel/
 */
define( 'MOGA_THEME_PATH', get_template_directory() . '/' );

/**
 * Public URL to the theme root directory.
 * Example: http://localhost/moga/wp-content/themes/moga-travel/
 */
define( 'MOGA_THEME_URL', get_template_directory_uri() . '/' );

/**
 * Path to the theme's inc/ directory.
 */
define( 'MOGA_THEME_INC', MOGA_THEME_PATH . 'inc/' );


// ============================================================
// LOAD THEME CLASSES
// ============================================================

/**
 * Load core theme class files.
 * Order matters — setup must load before assets.
 */
$moga_theme_classes = array(
    MOGA_THEME_INC . 'classes/class-moga-setup.php',
    MOGA_THEME_INC . 'classes/class-moga-assets.php',
    MOGA_THEME_INC . 'classes/class-moga-nav-walker.php',
);

foreach ( $moga_theme_classes as $class_file ) {
    if ( file_exists( $class_file ) ) {
        require_once $class_file;
    }
}

/**
 * Load helper functions and hooks.
 */
$moga_theme_includes = array(
    MOGA_THEME_INC . 'helpers/template-helpers.php',
    MOGA_THEME_INC . 'hooks/theme-hooks.php',
);

foreach ( $moga_theme_includes as $include_file ) {
    if ( file_exists( $include_file ) ) {
        require_once $include_file;
    }
}


// ============================================================
// BOOT THEME
// ============================================================

/**
 * Initialize the theme setup class.
 * Runs on 'after_setup_theme' — the correct hook for
 * add_theme_support() and register_nav_menus().
 */
add_action( 'after_setup_theme', function() {
    Moga_Setup::init();
} );

/**
 * Initialize the assets class.
 * Runs on 'wp_enqueue_scripts' for frontend assets.
 */
add_action( 'wp_enqueue_scripts', function() {
    Moga_Assets::enqueue_frontend();
} );

/**
 * Enqueue admin assets.
 * Runs on 'admin_enqueue_scripts' for backend assets.
 */
add_action( 'admin_enqueue_scripts', function() {
    Moga_Assets::enqueue_admin();
} );


// ============================================================
// REGISTER NAVIGATION MENUS
// ============================================================

/**
 * Register all theme navigation menu locations.
 * Called inside after_setup_theme via Moga_Setup::init().
 *
 * Defined here as a standalone function so it can also be
 * called directly if needed.
 */
function moga_register_menus() {
    register_nav_menus( array(
        'moga-primary'   => __( 'Primary Navigation', 'moga-travel' ),
        'moga-footer-1'  => __( 'Footer Column 1 — Company', 'moga-travel' ),
        'moga-footer-2'  => __( 'Footer Column 2 — Support', 'moga-travel' ),
        'moga-footer-3'  => __( 'Footer Column 3 — Destinations', 'moga-travel' ),
        'moga-mobile'    => __( 'Mobile Navigation', 'moga-travel' ),
        'moga-dashboard' => __( 'Dashboard Sidebar', 'moga-travel' ),
    ) );
}


// ============================================================
// REGISTER WIDGET AREAS (SIDEBARS)
// ============================================================

/**
 * Register all theme widget areas.
 */
function moga_register_sidebars() {

    // Search results sidebar.
    register_sidebar( array(
        'name'          => __( 'Search Filters Sidebar', 'moga-travel' ),
        'id'            => 'moga-search-sidebar',
        'description'   => __( 'Filters shown on property and tour search results pages.', 'moga-travel' ),
        'before_widget' => '<div id="%1$s" class="moga-widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="moga-widget__title">',
        'after_title'   => '</h3>',
    ) );

    // Property single sidebar.
    register_sidebar( array(
        'name'          => __( 'Property Sidebar', 'moga-travel' ),
        'id'            => 'moga-property-sidebar',
        'description'   => __( 'Shown on individual property pages.', 'moga-travel' ),
        'before_widget' => '<div id="%1$s" class="moga-widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="moga-widget__title">',
        'after_title'   => '</h3>',
    ) );

    // Tour single sidebar.
    register_sidebar( array(
        'name'          => __( 'Tour Sidebar', 'moga-travel' ),
        'id'            => 'moga-tour-sidebar',
        'description'   => __( 'Shown on individual tour pages.', 'moga-travel' ),
        'before_widget' => '<div id="%1$s" class="moga-widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="moga-widget__title">',
        'after_title'   => '</h3>',
    ) );

    // Footer widget areas.
    for ( $i = 1; $i <= 4; $i++ ) {
        register_sidebar( array(
            'name'          => sprintf( __( 'Footer Column %d', 'moga-travel' ), $i ),
            'id'            => 'moga-footer-' . $i,
            'description'   => sprintf( __( 'Footer widget area column %d.', 'moga-travel' ), $i ),
            'before_widget' => '<div id="%1$s" class="moga-widget %2$s">',
            'after_widget'  => '</div>',
            'before_title'  => '<h4 class="moga-footer__widget-title">',
            'after_title'   => '</h4>',
        ) );
    }
}

add_action( 'widgets_init', 'moga_register_sidebars' );


// ============================================================
// THEME UTILITY FUNCTIONS
// ============================================================

/**
 * Get the theme version string.
 * Used for cache-busting CSS and JS files.
 *
 * @since  1.0.0
 * @return string
 */
function moga_version() {
    return MOGA_THEME_VERSION;
}

/**
 * Get the theme assets URL.
 *
 * @since  1.0.0
 * @param  string $path Optional path relative to assets folder.
 * @return string       Full URL to the asset.
 */
function moga_asset( $path = '' ) {
    return MOGA_THEME_URL . 'assets/' . ltrim( $path, '/' );
}

/**
 * Get the theme image URL.
 *
 * @since  1.0.0
 * @param  string $filename Image filename.
 * @return string           Full URL to the image.
 */
function moga_image( $filename ) {
    return moga_asset( 'images/' . $filename );
}

/**
 * Check if Moga Travel Core plugin is active.
 * The theme can work standalone but full features
 * require the plugin.
 *
 * @since  1.0.0
 * @return bool
 */
function moga_plugin_active() {
    return defined( 'MOGA_CORE_VERSION' );
}

/**
 * Output theme template part with data.
 * Wrapper around get_template_part() with variable passing.
 *
 * Usage:
 *   moga_get_part( 'template-parts/property/card-grid', array(
 *       'property_id' => 123,
 *   ) );
 *
 * @since  1.0.0
 * @param  string $slug Template part slug (without .php).
 * @param  array  $data Variables to pass to the template.
 * @return void
 */
function moga_get_part( $slug, $data = array() ) {
    // Make data available inside the template via $args.
    set_query_var( 'moga_data', $data );
    get_template_part( $slug );
}

/**
 * Retrieve data passed to a template part via moga_get_part().
 *
 * Usage inside a template part:
 *   $data = moga_part_data();
 *   $id   = $data['property_id'] ?? null;
 *
 * @since  1.0.0
 * @return array
 */
function moga_part_data() {
    return get_query_var( 'moga_data', array() );
}

/**
 * Navigation fallback — shown when no menu is assigned.
 * Displays a helpful message in the nav area.
 *
 * @since  1.0.0
 * @return void
 */
function moga_nav_fallback() {
    if ( current_user_can( 'manage_options' ) ) {
        echo '<ul class="moga-nav__list">'
            . '<li class="moga-nav__item">'
            . '<a href="' . esc_url( admin_url( 'nav-menus.php' ) ) . '">'
            . esc_html__( '+ Assign a menu', 'moga-travel' )
            . '</a>'
            . '</li>'
            . '</ul>';
    }
}