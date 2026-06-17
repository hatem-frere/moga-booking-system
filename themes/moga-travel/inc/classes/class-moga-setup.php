<?php
/**
 * Theme Setup Class
 *
 * Registers all theme features, image sizes,
 * navigation menus, and WordPress supports.
 *
 * @package    MogaTravel
 * @subpackage MogaTravel/inc/classes
 * @author     Hatem Frere
 * @since      1.0.0
 */

// Block direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Moga_Setup
 */
class Moga_Setup {

    /**
     * Initialize theme setup.
     * Called on 'after_setup_theme' hook.
     *
     * @since  1.0.0
     * @return void
     */
    public static function init() {

        self::add_theme_supports();
        self::add_image_sizes();
        self::set_content_width();
        moga_register_menus();
    }


    /**
     * Register WordPress theme support features.
     *
     * @since  1.0.0
     * @return void
     */
    private static function add_theme_supports() {

        // Let WordPress manage the document title tag.
        add_theme_support( 'title-tag' );

        // Enable post thumbnails (featured images).
        add_theme_support( 'post-thumbnails' );

        // Enable HTML5 markup for core elements.
        add_theme_support( 'html5', array(
            'search-form',
            'comment-form',
            'comment-list',
            'gallery',
            'caption',
            'style',
            'script',
        ) );

        // Enable custom logo in the Customizer.
        add_theme_support( 'custom-logo', array(
            'height'      => 60,
            'width'       => 200,
            'flex-width'  => true,
            'flex-height' => true,
        ) );

        // Enable custom background in the Customizer.
        add_theme_support( 'custom-background', array(
            'default-color' => 'f8f9fa',
        ) );

        // Enable selective refresh for widgets in the Customizer.
        add_theme_support( 'customize-selective-refresh-widgets' );

        // Enable responsive embeds (YouTube, Vimeo etc).
        add_theme_support( 'responsive-embeds' );

        // Enable wide and full-width alignment for blocks.
        add_theme_support( 'align-wide' );

        // Enable editor styles.
        add_theme_support( 'editor-styles' );

        // Enable automatic feed links.
        add_theme_support( 'automatic-feed-links' );

        // Enable block editor color palette matching theme colors.
        add_theme_support( 'editor-color-palette', array(
            array(
                'name'  => __( 'Primary Blue', 'moga-travel' ),
                'slug'  => 'moga-primary',
                'color' => '#003580',
            ),
            array(
                'name'  => __( 'Secondary Blue', 'moga-travel' ),
                'slug'  => 'moga-secondary',
                'color' => '#0071c2',
            ),
            array(
                'name'  => __( 'Accent Orange', 'moga-travel' ),
                'slug'  => 'moga-accent',
                'color' => '#f5a623',
            ),
            array(
                'name'  => __( 'Dark', 'moga-travel' ),
                'slug'  => 'moga-dark',
                'color' => '#212529',
            ),
            array(
                'name'  => __( 'Light', 'moga-travel' ),
                'slug'  => 'moga-light',
                'color' => '#f8f9fa',
            ),
        ) );

        // Load theme text domain for translations.
        load_theme_textdomain(
            'moga-travel',
            MOGA_THEME_PATH . 'languages'
        );
    }


    /**
     * Register custom image sizes used throughout the theme.
     *
     * @since  1.0.0
     * @return void
     */
    private static function add_image_sizes() {

        // Property card thumbnail (grid view).
        add_image_size( 'moga-card',        400, 260, true );

        // Property card thumbnail (list view).
        add_image_size( 'moga-card-list',   360, 240, true );

        // Property hero / single page banner.
        add_image_size( 'moga-hero',        1280, 600, true );

        // Property gallery image.
        add_image_size( 'moga-gallery',     800, 560, true );

        // Tour card thumbnail.
        add_image_size( 'moga-tour-card',   400, 280, true );

        // Destination card thumbnail.
        add_image_size( 'moga-destination', 480, 320, true );

        // Owner avatar.
        add_image_size( 'moga-avatar',      120, 120, true );

        // Wide banner for homepage sections.
        add_image_size( 'moga-banner',      1600, 500, true );
    }


    /**
     * Set the global content width in pixels.
     * This limits the width of embedded media and images.
     *
     * @since  1.0.0
     * @return void
     */
    private static function set_content_width() {
        if ( ! isset( $GLOBALS['content_width'] ) ) {
            $GLOBALS['content_width'] = 1280;
        }
    }
}