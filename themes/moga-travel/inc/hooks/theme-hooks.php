<?php
/**
 * Theme Hooks
 *
 * Path: themes/moga-travel/inc/hooks/theme-hooks.php
 *
 * Custom WordPress filters and actions for the Moga Travel theme.
 *
 * Hooks registered here:
 *   01. template_include — redirect CPT single pages to single-pages/ folder
 *
 * @package    MogaTravel
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


// ============================================================
// 01. SINGLE PAGE TEMPLATE FOLDER REDIRECT
// ============================================================

/**
 * Redirect WordPress CPT single page template loading
 * to the dedicated single-pages/ folder in the theme root.
 *
 * WordPress default hierarchy only looks in the theme root for
 * single-{post-type}.php. This filter tells WordPress to look
 * in themes/moga-travel/single-pages/ instead, keeping the
 * theme root clean and all single page templates organised.
 *
 * Supports:
 *   - moga_property  → single-pages/single-moga_property.php
 *   - moga_tour      → single-pages/single-moga_tour.php
 *
 * @since  1.0.0
 * @param  string $template  Current resolved template path.
 * @return string            Modified template path.
 */
add_filter( 'template_include', function( $template ) {

    $map = array(
        'moga_property' => 'single-moga_property.php',
        'moga_tour'     => 'single-moga_tour.php',
    );

    foreach ( $map as $post_type => $filename ) {
        if ( is_singular( $post_type ) ) {
            $custom = MOGA_THEME_PATH . 'single-pages/' . $filename;
            if ( file_exists( $custom ) ) {
                return $custom;
            }
        }
    }

    return $template;
} );