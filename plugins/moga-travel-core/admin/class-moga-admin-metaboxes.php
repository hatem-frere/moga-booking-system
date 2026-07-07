<?php
/**
 * Admin Meta Boxes
 *
 * Registers and renders all meta boxes for
 * Properties, Tours and Destinations in the WordPress admin.
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
 * Class Moga_Admin_Metaboxes
 */
class Moga_Admin_Metaboxes {

    /**
     * Maximum gallery images allowed.
     *
     * @since 1.0.0
     * @var   int
     */
    const MAX_GALLERY_IMAGES = 20;

    /**
     * Maximum video URLs allowed.
     *
     * @since 1.0.0
     * @var   int
     */
    const MAX_VIDEO_URLS = 3;

    /**
     * Maximum local video uploads allowed.
     *
     * @since 1.0.0
     * @var   int
     */
    const MAX_VIDEO_UPLOADS = 2;

    /**
     * Initialize hooks.
     *
     * @since  1.0.0
     * @return void
     */
    public static function init() {
        add_action( 'add_meta_boxes', array( __CLASS__, 'register_meta_boxes' ) );
        add_action( 'save_post',      array( __CLASS__, 'save_meta_boxes' ), 10, 2 );
        add_action( 'admin_footer',   array( __CLASS__, 'meta_box_scripts' ) );
    }


    // ============================================================
    // REGISTER META BOXES
    // ============================================================

    /**
     * Register all meta boxes.
     *
     * @since  1.0.0
     * @return void
     */
    public static function register_meta_boxes() {

        // ---- Property Meta Boxes ----
        $property_boxes = array(
            array( 'moga_property_pricing',   __( '💰 Pricing',             'moga-travel-core' ), 'render_property_pricing'   ),
            array( 'moga_property_location',  __( '📍 Location',            'moga-travel-core' ), 'render_property_location'  ),
            array( 'moga_property_contact',   __( '📞 Contact',             'moga-travel-core' ), 'render_property_contact'   ),
            array( 'moga_property_details',   __( '🏠 Property Details',    'moga-travel-core' ), 'render_property_details'   ),
            array( 'moga_property_amenities', __( '✨ Amenities',           'moga-travel-core' ), 'render_property_amenities' ),
            array( 'moga_property_booking',   __( '📅 Booking Rules',       'moga-travel-core' ), 'render_property_booking'   ),
            array( 'moga_property_status',    __( '⚙️ Status & Visibility', 'moga-travel-core' ), 'render_property_status'    ),
        );

        foreach ( $property_boxes as $box ) {
            add_meta_box(
                $box[0],
                $box[1],
                array( __CLASS__, $box[2] ),
                'moga_property',
                'normal',
                'high'
            );
        }

        // Property sidebar boxes.
        add_meta_box(
            'moga_property_gallery',
            __( '📸 Photo Gallery', 'moga-travel-core' ),
            array( __CLASS__, 'render_gallery_box' ),
            'moga_property',
            'side',
            'default'
        );

        add_meta_box(
            'moga_property_videos',
            __( '🎬 Videos', 'moga-travel-core' ),
            array( __CLASS__, 'render_videos_box' ),
            'moga_property',
            'side',
            'default'
        );

        // ---- Tour Meta Boxes ----
        $tour_boxes = array(
            array( 'moga_tour_pricing',  __( '💰 Pricing',             'moga-travel-core' ), 'render_tour_pricing'  ),
            array( 'moga_tour_schedule', __( '🗓️ Schedule',            'moga-travel-core' ), 'render_tour_schedule' ),
            array( 'moga_tour_location', __( '📍 Location & Route',    'moga-travel-core' ), 'render_tour_location' ),
            array( 'moga_tour_contact',  __( '📞 Organizer Contact',   'moga-travel-core' ), 'render_tour_contact'  ),
            array( 'moga_tour_details',  __( '🗺️ Tour Details',        'moga-travel-core' ), 'render_tour_details'  ),
            array( 'moga_tour_includes', __( '✅ Includes & Excludes', 'moga-travel-core' ), 'render_tour_includes' ),
            array( 'moga_tour_bus',      __( '🚌 Bus & Seats',         'moga-travel-core' ), 'render_tour_bus'      ),
            array( 'moga_tour_status',   __( '⚙️ Status & Visibility', 'moga-travel-core' ), 'render_tour_status'   ),
        );

        foreach ( $tour_boxes as $box ) {
            add_meta_box(
                $box[0],
                $box[1],
                array( __CLASS__, $box[2] ),
                'moga_tour',
                'normal',
                'high'
            );
        }

        // Tour sidebar boxes.
        add_meta_box(
            'moga_tour_gallery',
            __( '📸 Photo Gallery', 'moga-travel-core' ),
            array( __CLASS__, 'render_gallery_box' ),
            'moga_tour',
            'side',
            'default'
        );

        add_meta_box(
            'moga_tour_videos',
            __( '🎬 Videos', 'moga-travel-core' ),
            array( __CLASS__, 'render_videos_box' ),
            'moga_tour',
            'side',
            'default'
        );

        // ---- Destination sidebar boxes ----
        add_meta_box(
            'moga_destination_gallery',
            __( '📸 Photo Gallery', 'moga-travel-core' ),
            array( __CLASS__, 'render_gallery_box' ),
            'moga_destination',
            'side',
            'default'
        );

        add_meta_box(
            'moga_destination_videos',
            __( '🎬 Videos', 'moga-travel-core' ),
            array( __CLASS__, 'render_videos_box' ),
            'moga_destination',
            'side',
            'default'
        );
    }


    // ============================================================
    // SHARED SIDEBAR META BOXES
    // ============================================================

    /**
     * Render the Photo Gallery meta box.
     * Shared between Property, Tour, and Destination.
     *
     * @since  1.0.0
     * @param  WP_Post $post Current post object.
     * @return void
     */
    public static function render_gallery_box( $post ) {
        wp_nonce_field( 'moga_gallery_nonce', 'moga_gallery_nonce' );

        $gallery     = get_post_meta( $post->ID, '_moga_gallery', true );
        $gallery_ids = $gallery ? json_decode( $gallery, true ) : array();
        $max         = self::MAX_GALLERY_IMAGES;
        $count       = count( $gallery_ids );
        ?>
        <div class="moga-gallery-box">

            <div class="moga-gallery-box__header">
                <span class="moga-gallery-box__count">
                    <span id="moga-gallery-count"><?php echo esc_html( $count ); ?></span>
                    /<?php echo esc_html( $max ); ?>
                    <?php esc_html_e( 'photos', 'moga-travel-core' ); ?>
                </span>
            </div>

            <ul
                id="moga-gallery-list"
                class="moga-gallery-box__list"
                data-max="<?php echo esc_attr( $max ); ?>"
            >
                <?php foreach ( $gallery_ids as $attachment_id ) : ?>
                    <?php
                    $thumb = wp_get_attachment_image_url( $attachment_id, 'thumbnail' );
                    if ( ! $thumb ) {
                        continue;
                    }
                    ?>
                    <li class="moga-gallery-box__item" data-id="<?php echo esc_attr( $attachment_id ); ?>">
                        <img src="<?php echo esc_url( $thumb ); ?>" alt="">
                        <button
                            type="button"
                            class="moga-gallery-box__remove"
                            title="<?php esc_attr_e( 'Remove', 'moga-travel-core' ); ?>"
                        >✕</button>
                        <input
                            type="hidden"
                            name="moga_gallery_ids[]"
                            value="<?php echo esc_attr( $attachment_id ); ?>"
                        >
                    </li>
                <?php endforeach; ?>
            </ul>

            <button
                type="button"
                id="moga-gallery-add"
                class="moga-gallery-box__btn button"
                <?php echo $count >= $max ? 'disabled' : ''; ?>
            >
                + <?php esc_html_e( 'Add Photos', 'moga-travel-core' ); ?>
            </button>

            <?php if ( $count >= $max ) : ?>
                <p class="moga-metabox__hint moga-metabox__hint--warning">
                    <?php
                    printf(
                        /* translators: %d: max images */
                        esc_html__( 'Maximum %d photos reached.', 'moga-travel-core' ),
                        $max
                    );
                    ?>
                </p>
            <?php endif; ?>

        </div>
        <?php
    }

    /**
     * Render the Videos meta box.
     * Shared between Property, Tour, and Destination.
     * Supports: YouTube URLs, Vimeo URLs, local video uploads.
     *
     * @since  1.0.0
     * @param  WP_Post $post Current post object.
     * @return void
     */
    public static function render_videos_box( $post ) {
        wp_nonce_field( 'moga_videos_nonce', 'moga_videos_nonce' );

        $videos_meta    = get_post_meta( $post->ID, '_moga_videos', true );
        $videos         = $videos_meta ? json_decode( $videos_meta, true ) : array();
        $max_urls       = self::MAX_VIDEO_URLS;
        $max_uploads    = self::MAX_VIDEO_UPLOADS;

        // Separate URL videos and uploaded videos.
        $url_videos    = array_filter( $videos, fn( $v ) => isset( $v['type'] ) && 'url' === $v['type'] );
        $upload_videos = array_filter( $videos, fn( $v ) => isset( $v['type'] ) && 'upload' === $v['type'] );

        // Reset array keys.
        $url_videos    = array_values( $url_videos );
        $upload_videos = array_values( $upload_videos );
        ?>
        <div class="moga-videos-box">

            <?php // ---- Section 1: YouTube / Vimeo URLs ---- ?>
            <div class="moga-videos-box__section">
                <h4 class="moga-videos-box__section-title">
                    🔗 <?php esc_html_e( 'YouTube / Vimeo URLs', 'moga-travel-core' ); ?>
                    <span class="moga-videos-box__max">
                        <?php
                        printf(
                            /* translators: %d: max videos */
                            esc_html__( '(max %d)', 'moga-travel-core' ),
                            $max_urls
                        );
                        ?>
                    </span>
                </h4>

                <?php for ( $i = 0; $i < $max_urls; $i++ ) : ?>
                    <div class="moga-videos-box__url-row">
                        <input
                            type="url"
                            name="moga_video_urls[]"
                            value="<?php echo esc_attr( isset( $url_videos[ $i ]['url'] ) ? $url_videos[ $i ]['url'] : '' ); ?>"
                            placeholder="https://youtube.com/watch?v=... or https://vimeo.com/..."
                            class="moga-videos-box__url-input"
                        >
                    </div>
                <?php endfor; ?>
            </div>

            <?php // ---- Section 2: Local Video Uploads ---- ?>
            <div class="moga-videos-box__section">
                <h4 class="moga-videos-box__section-title">
                    📁 <?php esc_html_e( 'Upload Local Videos', 'moga-travel-core' ); ?>
                    <span class="moga-videos-box__max">
                        <?php
                        printf(
                            /* translators: %d: max uploads */
                            esc_html__( '(max %d)', 'moga-travel-core' ),
                            $max_uploads
                        );
                        ?>
                    </span>
                </h4>

                <ul
                    id="moga-upload-video-list"
                    class="moga-videos-box__upload-list"
                    data-max="<?php echo esc_attr( $max_uploads ); ?>"
                >
                    <?php foreach ( $upload_videos as $video ) : ?>
                        <?php
                        $attachment_id  = isset( $video['id'] ) ? intval( $video['id'] ) : 0;
                        $attachment_url = $attachment_id ? wp_get_attachment_url( $attachment_id ) : '';
                        $filename       = $attachment_id ? basename( $attachment_url ) : '';
                        if ( ! $attachment_url ) {
                            continue;
                        }
                        ?>
                        <li
                            class="moga-videos-box__upload-item"
                            data-id="<?php echo esc_attr( $attachment_id ); ?>"
                        >
                            <span class="moga-videos-box__upload-icon">🎬</span>
                            <span class="moga-videos-box__upload-name">
                                <?php echo esc_html( $filename ); ?>
                            </span>
                            <button
                                type="button"
                                class="moga-videos-box__remove-upload"
                                title="<?php esc_attr_e( 'Remove', 'moga-travel-core' ); ?>"
                            >✕</button>
                            <input
                                type="hidden"
                                name="moga_video_upload_ids[]"
                                value="<?php echo esc_attr( $attachment_id ); ?>"
                            >
                        </li>
                    <?php endforeach; ?>
                </ul>

                <?php if ( count( $upload_videos ) < $max_uploads ) : ?>
                    <button
                        type="button"
                        id="moga-upload-video-add"
                        class="moga-videos-box__btn button"
                    >
                        + <?php esc_html_e( 'Upload Video', 'moga-travel-core' ); ?>
                    </button>
                    <p class="moga-metabox__hint">
                        <?php esc_html_e( 'Supported: MP4, WebM, OGV. Max 100MB each.', 'moga-travel-core' ); ?>
                    </p>
                <?php else : ?>
                    <p class="moga-metabox__hint moga-metabox__hint--warning">
                        <?php
                        printf(
                            /* translators: %d: max uploads */
                            esc_html__( 'Maximum %d video uploads reached.', 'moga-travel-core' ),
                            $max_uploads
                        );
                        ?>
                    </p>
                <?php endif; ?>

            </div>

        </div>
        <?php
    }


    // ============================================================
    // PROPERTY META BOXES — RENDER
    // ============================================================

    /**
     * Render property pricing meta box.
     *
     * @since  1.0.0
     * @param  WP_Post $post Current post object.
     * @return void
     */
    public static function render_property_pricing( $post ) {
        wp_nonce_field( 'moga_property_pricing_nonce', 'moga_property_pricing_nonce' );

        $price         = get_post_meta( $post->ID, '_moga_price_per_night', true );
        $weekend_price = get_post_meta( $post->ID, '_moga_price_weekend',   true );
        $discount      = get_post_meta( $post->ID, '_moga_price_discount',  true );
        $currency      = get_post_meta( $post->ID, '_moga_currency',        true ) ?: 'USD';
        $currencies    = moga_get_currencies();
        ?>
        <div class="moga-metabox">
            <div class="moga-metabox__row">

                <div class="moga-metabox__field">
                    <label for="moga_price_per_night">
                        <?php esc_html_e( 'Price Per Night', 'moga-travel-core' ); ?>
                        <span class="required">*</span>
                    </label>
                    <input
                        type="number"
                        id="moga_price_per_night"
                        name="moga_price_per_night"
                        value="<?php echo esc_attr( $price ); ?>"
                        min="0" step="0.01" placeholder="0.00"
                    >
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_price_weekend">
                        <?php esc_html_e( 'Weekend Price (Fri-Sat)', 'moga-travel-core' ); ?>
                    </label>
                    <input
                        type="number"
                        id="moga_price_weekend"
                        name="moga_price_weekend"
                        value="<?php echo esc_attr( $weekend_price ); ?>"
                        min="0" step="0.01" placeholder="0.00"
                    >
                    <p class="moga-metabox__hint">
                        <?php esc_html_e( 'Leave empty to use the same price for weekends.', 'moga-travel-core' ); ?>
                    </p>
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_price_discount">
                        <?php esc_html_e( 'Discount %', 'moga-travel-core' ); ?>
                    </label>
                    <input
                        type="number"
                        id="moga_price_discount"
                        name="moga_price_discount"
                        value="<?php echo esc_attr( $discount ); ?>"
                        min="0" max="100" step="1" placeholder="0"
                    >
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_currency">
                        <?php esc_html_e( 'Currency', 'moga-travel-core' ); ?>
                    </label>
                    <select id="moga_currency" name="moga_currency">
                        <?php foreach ( $currencies as $code => $label ) : ?>
                            <option value="<?php echo esc_attr( $code ); ?>" <?php selected( $currency, $code ); ?>>
                                <?php echo esc_html( $label ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

            </div>
        </div>
        <?php
    }

    /**
    /**
     * Render property location meta box.
     *
     * Four-level DB-powered cascade:
     *   Country → Province/State/Governorate → City → District
     * All cascade dropdowns loaded via AJAX from location DB tables.
     *
     * @since  1.0.0
     * @param  WP_Post $post Current post object.
     * @return void
     */
    public static function render_property_location( $post ) {
        wp_nonce_field( 'moga_property_location_nonce', 'moga_property_location_nonce' );

        $country     = get_post_meta( $post->ID, '_moga_country',     true );
        $province    = get_post_meta( $post->ID, '_moga_province',    true );
        $province_id = (int) get_post_meta( $post->ID, '_moga_province_id', true );
        $city        = get_post_meta( $post->ID, '_moga_city',        true );
        $city_id     = (int) get_post_meta( $post->ID, '_moga_city_id',    true );
        $district    = get_post_meta( $post->ID, '_moga_district',    true );
        $address     = get_post_meta( $post->ID, '_moga_address',     true );
        $postal_code = get_post_meta( $post->ID, '_moga_postal_code', true );
        $latitude    = get_post_meta( $post->ID, '_moga_latitude',    true );
        $longitude   = get_post_meta( $post->ID, '_moga_longitude',   true );

        $countries     = moga_get_countries_dropdown();
        $province_opts = self::get_provinces_for_render( $country );
        $city_opts     = self::get_cities_for_province_render( $province_id );
        ?>
        <div class="moga-metabox">

            <div class="moga-metabox__row">
                <div class="moga-metabox__field">
                    <label for="moga_country">
                        <?php esc_html_e( 'Country', 'moga-travel-core' ); ?>
                        <span class="required">*</span>
                    </label>
                    <select
                        id="moga_country"
                        name="moga_country"
                        class="moga-country-select"
                        data-province-target="moga_province_id"
                        data-city-target="moga_city_id"
                        data-district-wrapper="moga-property-district-wrapper"
                    >
                        <?php foreach ( $countries as $code => $label ) : ?>
                            <option value="<?php echo esc_attr( $code ); ?>" <?php selected( $country, $code ); ?>>
                                <?php echo esc_html( $label ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_province_id">
                        <?php esc_html_e( 'State / Province / Governorate', 'moga-travel-core' ); ?>
                        <span class="required">*</span>
                    </label>
                    <select
                        id="moga_province_id"
                        name="moga_province_id"
                        class="moga-province-select"
                        data-city-target="moga_city_id"
                        data-district-wrapper="moga-property-district-wrapper"
                        data-name-field="moga_province"
                    >
                        <?php if ( empty( $province_opts ) ) : ?>
                            <option value=""><?php esc_html_e( '— Select Country First —', 'moga-travel-core' ); ?></option>
                        <?php else : ?>
                            <option value=""><?php esc_html_e( '— Select Province —', 'moga-travel-core' ); ?></option>
                            <?php foreach ( $province_opts as $p ) : ?>
                                <option value="<?php echo esc_attr( $p['id'] ); ?>" <?php selected( $province_id, (int) $p['id'] ); ?>>
                                    <?php echo esc_html( $p['name'] ); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <input type="hidden" id="moga_province" name="moga_province" value="<?php echo esc_attr( $province ); ?>">
                </div>
            </div>

            <div class="moga-metabox__row">
                <div class="moga-metabox__field">
                    <label for="moga_city_id">
                        <?php esc_html_e( 'City', 'moga-travel-core' ); ?>
                        <span class="required">*</span>
                    </label>
                    <select
                        id="moga_city_id"
                        name="moga_city_id"
                        class="moga-city-select"
                        data-district-wrapper="moga-property-district-wrapper"
                        data-name-field="moga_city"
                    >
                        <?php if ( empty( $city_opts ) ) : ?>
                            <option value=""><?php esc_html_e( '— Select Province First —', 'moga-travel-core' ); ?></option>
                        <?php else : ?>
                            <option value=""><?php esc_html_e( '— Select City —', 'moga-travel-core' ); ?></option>
                            <?php foreach ( $city_opts as $c ) : ?>
                                <option value="<?php echo esc_attr( $c['id'] ); ?>" <?php selected( $city_id, (int) $c['id'] ); ?>>
                                    <?php echo esc_html( $c['name'] ); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <input type="hidden" id="moga_city" name="moga_city" value="<?php echo esc_attr( $city ); ?>">
                </div>

                <?php // District wrapper — dropdown when DB has districts, text fallback otherwise ?>
                <div class="moga-metabox__field moga-district-wrapper" id="moga-property-district-wrapper">
                    <div class="moga-district-dropdown-field" style="display:none;">
                        <label for="moga_district_select">
                            <?php esc_html_e( 'District / Area', 'moga-travel-core' ); ?>
                        </label>
                        <select id="moga_district_select" class="moga-district-select">
                            <option value=""><?php esc_html_e( '— Select District —', 'moga-travel-core' ); ?></option>
                        </select>
                        <span class="moga-district-loading" style="display:none;">
                            <?php esc_html_e( 'Loading districts…', 'moga-travel-core' ); ?>
                        </span>
                    </div>
                    <div class="moga-district-text-field">
                        <label for="moga_district" class="moga-district-text-label">
                            <?php esc_html_e( 'District / Area', 'moga-travel-core' ); ?>
                        </label>
                        <input
                            type="text"
                            id="moga_district"
                            name="moga_district"
                            class="moga-district-text"
                            value="<?php echo esc_attr( $district ); ?>"
                            placeholder="<?php esc_attr_e( 'e.g. Downtown, Zamalek', 'moga-travel-core' ); ?>"
                        >
                    </div>
                </div>
            </div>

            <div class="moga-metabox__row">
                <div class="moga-metabox__field">
                    <label for="moga_postal_code"><?php esc_html_e( 'Postal Code', 'moga-travel-core' ); ?></label>
                    <input type="text" id="moga_postal_code" name="moga_postal_code"
                        value="<?php echo esc_attr( $postal_code ); ?>"
                        placeholder="<?php esc_attr_e( 'e.g. 12345', 'moga-travel-core' ); ?>">
                </div>
            </div>

            <div class="moga-metabox__row moga-metabox__row--full">
                <div class="moga-metabox__field">
                    <label for="moga_address"><?php esc_html_e( 'Street Address', 'moga-travel-core' ); ?></label>
                    <input type="text" id="moga_address" name="moga_address"
                        value="<?php echo esc_attr( $address ); ?>"
                        placeholder="<?php esc_attr_e( 'Full street address', 'moga-travel-core' ); ?>">
                </div>
            </div>

            <div class="moga-metabox__row">
                <div class="moga-metabox__field">
                    <label for="moga_latitude"><?php esc_html_e( 'Latitude', 'moga-travel-core' ); ?></label>
                    <input type="text" id="moga_latitude" name="moga_latitude"
                        value="<?php echo esc_attr( $latitude ); ?>"
                        placeholder="<?php esc_attr_e( 'e.g. 30.0444', 'moga-travel-core' ); ?>">
                </div>
                <div class="moga-metabox__field">
                    <label for="moga_longitude"><?php esc_html_e( 'Longitude', 'moga-travel-core' ); ?></label>
                    <input type="text" id="moga_longitude" name="moga_longitude"
                        value="<?php echo esc_attr( $longitude ); ?>"
                        placeholder="<?php esc_attr_e( 'e.g. 31.2357', 'moga-travel-core' ); ?>">
                </div>
            </div>

        </div>
        <?php
    }


    /**
     * Render property contact meta box.
     *
     * @since  1.0.0
     * @param  WP_Post $post Current post object.
     * @return void
     */
    public static function render_property_contact( $post ) {
        wp_nonce_field( 'moga_property_contact_nonce', 'moga_property_contact_nonce' );

        $phone    = get_post_meta( $post->ID, '_moga_phone',    true );
        $whatsapp = get_post_meta( $post->ID, '_moga_whatsapp', true );
        $email    = get_post_meta( $post->ID, '_moga_email',    true );
        ?>
        <div class="moga-metabox">
            <div class="moga-metabox__row">

                <div class="moga-metabox__field">
                    <label for="moga_phone">
                        <?php esc_html_e( 'Phone Number', 'moga-travel-core' ); ?>
                    </label>
                    <input
                        type="tel"
                        id="moga_phone"
                        name="moga_phone"
                        value="<?php echo esc_attr( $phone ); ?>"
                        class="moga-phone-field"
                        placeholder="<?php esc_attr_e( 'Phone number', 'moga-travel-core' ); ?>"
                    >
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_whatsapp">
                        <?php esc_html_e( 'WhatsApp Number', 'moga-travel-core' ); ?>
                    </label>
                    <input
                        type="tel"
                        id="moga_whatsapp"
                        name="moga_whatsapp"
                        value="<?php echo esc_attr( $whatsapp ); ?>"
                        class="moga-phone-field"
                        placeholder="<?php esc_attr_e( 'WhatsApp number', 'moga-travel-core' ); ?>"
                    >
                </div>

            </div>

            <div class="moga-metabox__row">
                <div class="moga-metabox__field">
                    <label for="moga_email">
                        <?php esc_html_e( 'Email Address', 'moga-travel-core' ); ?>
                    </label>
                    <input
                        type="email"
                        id="moga_email"
                        name="moga_email"
                        value="<?php echo esc_attr( $email ); ?>"
                        placeholder="<?php esc_attr_e( 'contact@example.com', 'moga-travel-core' ); ?>"
                    >
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render property details meta box.
     *
     * @since  1.0.0
     * @param  WP_Post $post Current post object.
     * @return void
     */
    public static function render_property_details( $post ) {
        wp_nonce_field( 'moga_property_details_nonce', 'moga_property_details_nonce' );

        $max_guests      = get_post_meta( $post->ID, '_moga_max_guests',      true ) ?: 1;
        $bedrooms        = get_post_meta( $post->ID, '_moga_bedrooms',        true ) ?: 1;
        $bathrooms       = get_post_meta( $post->ID, '_moga_bathrooms',       true ) ?: 1;
        $area            = get_post_meta( $post->ID, '_moga_area',            true );
        $floor           = get_post_meta( $post->ID, '_moga_floor',           true );
        $building_floors = get_post_meta( $post->ID, '_moga_building_floors', true );
        $year_built      = get_post_meta( $post->ID, '_moga_year_built',      true );
        $cancellation    = get_post_meta( $post->ID, '_moga_cancellation',    true ) ?: 'moderate';

        $cancellation_policies = Moga_CPT_Property::get_cancellation_policies();
        ?>
        <div class="moga-metabox">

            <div class="moga-metabox__row">
                <div class="moga-metabox__field">
                    <label for="moga_max_guests">
                        <?php esc_html_e( 'Max Guests', 'moga-travel-core' ); ?>
                    </label>
                    <input type="number" id="moga_max_guests" name="moga_max_guests"
                        value="<?php echo esc_attr( $max_guests ); ?>" min="1" step="1">
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_bedrooms">
                        <?php esc_html_e( 'Bedrooms', 'moga-travel-core' ); ?>
                    </label>
                    <input type="number" id="moga_bedrooms" name="moga_bedrooms"
                        value="<?php echo esc_attr( $bedrooms ); ?>" min="0" step="1">
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_bathrooms">
                        <?php esc_html_e( 'Bathrooms', 'moga-travel-core' ); ?>
                    </label>
                    <input type="number" id="moga_bathrooms" name="moga_bathrooms"
                        value="<?php echo esc_attr( $bathrooms ); ?>" min="0" step="0.5">
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_area">
                        <?php esc_html_e( 'Area (m²)', 'moga-travel-core' ); ?>
                    </label>
                    <input type="number" id="moga_area" name="moga_area"
                        value="<?php echo esc_attr( $area ); ?>" min="0" step="1" placeholder="0">
                </div>
            </div>

            <div class="moga-metabox__row">
                <div class="moga-metabox__field">
                    <label for="moga_floor">
                        <?php esc_html_e( 'Floor Number', 'moga-travel-core' ); ?>
                    </label>
                    <input type="number" id="moga_floor" name="moga_floor"
                        value="<?php echo esc_attr( $floor ); ?>" min="0" step="1" placeholder="0">
                    <p class="moga-metabox__hint"><?php esc_html_e( '0 = Ground floor', 'moga-travel-core' ); ?></p>
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_building_floors">
                        <?php esc_html_e( 'Total Building Floors', 'moga-travel-core' ); ?>
                    </label>
                    <input type="number" id="moga_building_floors" name="moga_building_floors"
                        value="<?php echo esc_attr( $building_floors ); ?>" min="1" step="1" placeholder="1">
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_year_built">
                        <?php esc_html_e( 'Year Built', 'moga-travel-core' ); ?>
                    </label>
                    <input type="number" id="moga_year_built" name="moga_year_built"
                        value="<?php echo esc_attr( $year_built ); ?>"
                        min="1900" max="<?php echo esc_attr( gmdate( 'Y' ) ); ?>"
                        step="1" placeholder="<?php echo esc_attr( gmdate( 'Y' ) ); ?>">
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_cancellation">
                        <?php esc_html_e( 'Cancellation Policy', 'moga-travel-core' ); ?>
                    </label>
                    <select id="moga_cancellation" name="moga_cancellation">
                        <?php foreach ( $cancellation_policies as $key => $policy ) : ?>
                            <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $cancellation, $key ); ?>>
                                <?php echo esc_html( $policy['label'] ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ( isset( $cancellation_policies[ $cancellation ] ) ) : ?>
                        <p class="moga-metabox__hint">
                            <?php echo esc_html( $cancellation_policies[ $cancellation ]['desc'] ); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

        </div>
        <?php
    }

    /**
     * Render property amenities meta box.
     *
     * @since  1.0.0
     * @param  WP_Post $post Current post object.
     * @return void
     */
    public static function render_property_amenities( $post ) {
        wp_nonce_field( 'moga_property_amenities_nonce', 'moga_property_amenities_nonce' );

        $saved_amenities = moga_get_property_amenities( $post->ID );
        $all_amenities   = Moga_CPT_Property::get_amenities();
        $groups          = Moga_CPT_Property::get_amenity_groups();
        ?>
        <div class="moga-metabox moga-metabox--amenities">
            <?php foreach ( $groups as $group_key => $group_label ) : ?>
                <?php
                $group_amenities = array_filter(
                    $all_amenities,
                    function( $amenity ) use ( $group_key ) {
                        return $amenity['group'] === $group_key;
                    }
                );
                if ( empty( $group_amenities ) ) continue;
                ?>
                <div class="moga-amenity-group">
                    <h4 class="moga-amenity-group__title"><?php echo esc_html( $group_label ); ?></h4>
                    <div class="moga-amenity-group__items">
                        <?php foreach ( $group_amenities as $key => $amenity ) : ?>
                            <label class="moga-amenity-item">
                                <input
                                    type="checkbox"
                                    name="moga_amenities[]"
                                    value="<?php echo esc_attr( $key ); ?>"
                                    <?php checked( in_array( $key, $saved_amenities, true ) ); ?>
                                >
                                <span class="moga-amenity-item__label">
                                    <?php echo esc_html( $amenity['label'] ); ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Render property booking rules meta box.
     *
     * @since  1.0.0
     * @param  WP_Post $post Current post object.
     * @return void
     */
    public static function render_property_booking( $post ) {
        wp_nonce_field( 'moga_property_booking_nonce', 'moga_property_booking_nonce' );

        $min_stay      = get_post_meta( $post->ID, '_moga_min_stay',      true ) ?: 1;
        $max_stay      = get_post_meta( $post->ID, '_moga_max_stay',      true ) ?: 0;
        $checkin_time  = get_post_meta( $post->ID, '_moga_checkin_time',  true ) ?: '14:00';
        $checkout_time = get_post_meta( $post->ID, '_moga_checkout_time', true ) ?: '11:00';
        ?>
        <div class="moga-metabox">
            <div class="moga-metabox__row">

                <div class="moga-metabox__field">
                    <label for="moga_min_stay">
                        <?php esc_html_e( 'Minimum Stay (nights)', 'moga-travel-core' ); ?>
                    </label>
                    <input type="number" id="moga_min_stay" name="moga_min_stay"
                        value="<?php echo esc_attr( $min_stay ); ?>" min="1" step="1">
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_max_stay">
                        <?php esc_html_e( 'Maximum Stay (nights)', 'moga-travel-core' ); ?>
                    </label>
                    <input type="number" id="moga_max_stay" name="moga_max_stay"
                        value="<?php echo esc_attr( $max_stay ); ?>" min="0" step="1">
                    <p class="moga-metabox__hint">
                        <?php esc_html_e( '0 = No maximum limit', 'moga-travel-core' ); ?>
                    </p>
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_checkin_time">
                        <?php esc_html_e( 'Check-in Time', 'moga-travel-core' ); ?>
                    </label>
                    <input type="time" id="moga_checkin_time" name="moga_checkin_time"
                        value="<?php echo esc_attr( $checkin_time ); ?>">
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_checkout_time">
                        <?php esc_html_e( 'Check-out Time', 'moga-travel-core' ); ?>
                    </label>
                    <input type="time" id="moga_checkout_time" name="moga_checkout_time"
                        value="<?php echo esc_attr( $checkout_time ); ?>">
                </div>

            </div>
        </div>
        <?php
    }

    /**
     * Render property status meta box.
     *
     * @since  1.0.0
     * @param  WP_Post $post Current post object.
     * @return void
     */
    public static function render_property_status( $post ) {
        wp_nonce_field( 'moga_property_status_nonce', 'moga_property_status_nonce' );

        $featured        = get_post_meta( $post->ID, '_moga_featured',        true );
        $instant_booking = get_post_meta( $post->ID, '_moga_instant_booking', true );
        $active          = get_post_meta( $post->ID, '_moga_active',          true );

        if ( '' === $active ) {
            $active = '1';
        }
        ?>
        <div class="moga-metabox">
            <div class="moga-metabox__switches">

                <label class="moga-switch">
                    <input type="checkbox" name="moga_active" value="1" <?php checked( '1', $active ); ?>>
                    <span class="moga-switch__slider"></span>
                    <span class="moga-switch__label">
                        <?php esc_html_e( 'Active — visible to guests', 'moga-travel-core' ); ?>
                    </span>
                </label>

                <label class="moga-switch">
                    <input type="checkbox" name="moga_featured" value="1" <?php checked( '1', $featured ); ?>>
                    <span class="moga-switch__slider"></span>
                    <span class="moga-switch__label">
                        <?php esc_html_e( 'Featured — shown on homepage', 'moga-travel-core' ); ?>
                    </span>
                </label>

                <label class="moga-switch">
                    <input type="checkbox" name="moga_instant_booking" value="1" <?php checked( '1', $instant_booking ); ?>>
                    <span class="moga-switch__slider"></span>
                    <span class="moga-switch__label">
                        <?php esc_html_e( 'Instant Booking — no approval needed', 'moga-travel-core' ); ?>
                    </span>
                </label>

            </div>
        </div>
        <?php
    }


    // ============================================================
    // TOUR META BOXES — RENDER
    // ============================================================

    /**
     * Render tour pricing meta box.
     *
     * @since  1.0.0
     * @param  WP_Post $post Current post object.
     * @return void
     */
    public static function render_tour_pricing( $post ) {
        wp_nonce_field( 'moga_tour_pricing_nonce', 'moga_tour_pricing_nonce' );

        $price_adult  = get_post_meta( $post->ID, '_moga_price_per_person', true );
        $price_child  = get_post_meta( $post->ID, '_moga_price_child',      true );
        $price_infant = get_post_meta( $post->ID, '_moga_price_infant',     true );
        $group_disc   = get_post_meta( $post->ID, '_moga_price_group',      true );
        $currency     = get_post_meta( $post->ID, '_moga_currency',         true ) ?: 'USD';
        $currencies   = moga_get_currencies();
        ?>
        <div class="moga-metabox">
            <div class="moga-metabox__row">

                <div class="moga-metabox__field">
                    <label for="moga_price_per_person">
                        <?php esc_html_e( 'Price Per Adult', 'moga-travel-core' ); ?>
                        <span class="required">*</span>
                    </label>
                    <input type="number" id="moga_price_per_person" name="moga_price_per_person"
                        value="<?php echo esc_attr( $price_adult ); ?>" min="0" step="0.01" placeholder="0.00">
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_price_child">
                        <?php esc_html_e( 'Price Per Child (under 12)', 'moga-travel-core' ); ?>
                    </label>
                    <input type="number" id="moga_price_child" name="moga_price_child"
                        value="<?php echo esc_attr( $price_child ); ?>" min="0" step="0.01" placeholder="0.00">
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_price_infant">
                        <?php esc_html_e( 'Price Per Infant (under 2)', 'moga-travel-core' ); ?>
                    </label>
                    <input type="number" id="moga_price_infant" name="moga_price_infant"
                        value="<?php echo esc_attr( $price_infant ); ?>" min="0" step="0.01" placeholder="0.00">
                    <p class="moga-metabox__hint">
                        <?php esc_html_e( '0 = Infants travel free', 'moga-travel-core' ); ?>
                    </p>
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_price_group">
                        <?php esc_html_e( 'Group Discount %', 'moga-travel-core' ); ?>
                    </label>
                    <input type="number" id="moga_price_group" name="moga_price_group"
                        value="<?php echo esc_attr( $group_disc ); ?>" min="0" max="100" step="1" placeholder="0">
                </div>

            </div>

            <div class="moga-metabox__row">
                <div class="moga-metabox__field">
                    <label for="moga_tour_currency">
                        <?php esc_html_e( 'Currency', 'moga-travel-core' ); ?>
                    </label>
                    <select id="moga_tour_currency" name="moga_currency">
                        <?php foreach ( $currencies as $code => $label ) : ?>
                            <option value="<?php echo esc_attr( $code ); ?>" <?php selected( $currency, $code ); ?>>
                                <?php echo esc_html( $label ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render tour schedule meta box.
     *
     * @since  1.0.0
     * @param  WP_Post $post Current post object.
     * @return void
     */
    public static function render_tour_schedule( $post ) {
        wp_nonce_field( 'moga_tour_schedule_nonce', 'moga_tour_schedule_nonce' );

        $duration_days   = get_post_meta( $post->ID, '_moga_duration_days',   true ) ?: 1;
        $duration_nights = get_post_meta( $post->ID, '_moga_duration_nights', true ) ?: 0;
        $departure_time  = get_post_meta( $post->ID, '_moga_departure_time',  true ) ?: '08:00';
        $return_time     = get_post_meta( $post->ID, '_moga_return_time',     true ) ?: '18:00';
        $available_days  = get_post_meta( $post->ID, '_moga_available_days',  true );
        $available_days  = $available_days ? json_decode( $available_days, true ) : array();
        $weekdays        = Moga_CPT_Tour::get_weekdays();
        ?>
        <div class="moga-metabox">

            <div class="moga-metabox__row">
                <div class="moga-metabox__field">
                    <label for="moga_duration_days">
                        <?php esc_html_e( 'Duration (Days)', 'moga-travel-core' ); ?>
                    </label>
                    <input type="number" id="moga_duration_days" name="moga_duration_days"
                        value="<?php echo esc_attr( $duration_days ); ?>" min="1" step="1">
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_duration_nights">
                        <?php esc_html_e( 'Duration (Nights)', 'moga-travel-core' ); ?>
                    </label>
                    <input type="number" id="moga_duration_nights" name="moga_duration_nights"
                        value="<?php echo esc_attr( $duration_nights ); ?>" min="0" step="1">
                    <p class="moga-metabox__hint">
                        <?php esc_html_e( '0 = Day trip (no overnight)', 'moga-travel-core' ); ?>
                    </p>
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_departure_time">
                        <?php esc_html_e( 'Departure Time', 'moga-travel-core' ); ?>
                    </label>
                    <input type="time" id="moga_departure_time" name="moga_departure_time"
                        value="<?php echo esc_attr( $departure_time ); ?>">
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_return_time">
                        <?php esc_html_e( 'Return Time', 'moga-travel-core' ); ?>
                    </label>
                    <input type="time" id="moga_return_time" name="moga_return_time"
                        value="<?php echo esc_attr( $return_time ); ?>">
                </div>
            </div>

            <div class="moga-metabox__row moga-metabox__row--full">
                <div class="moga-metabox__field">
                    <label><?php esc_html_e( 'Available Days', 'moga-travel-core' ); ?></label>
                    <div class="moga-weekdays">
                        <?php foreach ( $weekdays as $day_num => $day_label ) : ?>
                            <label class="moga-weekday">
                                <input
                                    type="checkbox"
                                    name="moga_available_days[]"
                                    value="<?php echo esc_attr( $day_num ); ?>"
                                    <?php checked( in_array( (string) $day_num, array_map( 'strval', $available_days ), true ) ); ?>
                                >
                                <span><?php echo esc_html( substr( $day_label, 0, 3 ) ); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <p class="moga-metabox__hint">
                        <?php esc_html_e( 'Days this tour departs. Leave all unchecked for custom dates only.', 'moga-travel-core' ); ?>
                    </p>
                </div>
            </div>

        </div>
        <?php
    }

    /**
     * Render tour location meta box.
     *
     * Four-level DB-powered cascade for both departure and destination:
     *   Country → Province/State/Governorate → City → District
     *
     * @since  1.0.0
     * @param  WP_Post $post Current post object.
     * @return void
     */
    public static function render_tour_location( $post ) {
        wp_nonce_field( 'moga_tour_location_nonce', 'moga_tour_location_nonce' );

        $dep_country     = get_post_meta( $post->ID, '_moga_departure_country',     true );
        $dep_province    = get_post_meta( $post->ID, '_moga_departure_province',    true );
        $dep_province_id = (int) get_post_meta( $post->ID, '_moga_departure_province_id', true );
        $dep_city        = get_post_meta( $post->ID, '_moga_departure_city',        true );
        $dep_city_id     = (int) get_post_meta( $post->ID, '_moga_departure_city_id',    true );
        $dep_district    = get_post_meta( $post->ID, '_moga_departure_district',    true );
        $dep_point       = get_post_meta( $post->ID, '_moga_departure_point',       true );

        $dest_country     = get_post_meta( $post->ID, '_moga_destination_country',     true );
        $dest_province    = get_post_meta( $post->ID, '_moga_destination_province',    true );
        $dest_province_id = (int) get_post_meta( $post->ID, '_moga_destination_province_id', true );
        $dest_city        = get_post_meta( $post->ID, '_moga_destination_city',        true );
        $dest_city_id     = (int) get_post_meta( $post->ID, '_moga_destination_city_id',    true );
        $dest_district    = get_post_meta( $post->ID, '_moga_destination_district',    true );

        $countries        = moga_get_countries_dropdown();
        $dep_prov_opts    = self::get_provinces_for_render( $dep_country );
        $dep_city_opts    = self::get_cities_for_province_render( $dep_province_id );
        $dest_prov_opts   = self::get_provinces_for_render( $dest_country );
        $dest_city_opts   = self::get_cities_for_province_render( $dest_province_id );
        ?>
        <div class="moga-metabox">

            <?php // ---- DEPARTURE SECTION ---- ?>
            <h4 class="moga-metabox__section-title"><?php esc_html_e( 'Departure', 'moga-travel-core' ); ?></h4>
            <div class="moga-metabox__row">
                <div class="moga-metabox__field">
                    <label for="moga_departure_country"><?php esc_html_e( 'Departure Country', 'moga-travel-core' ); ?></label>
                    <select id="moga_departure_country" name="moga_departure_country"
                        class="moga-country-select"
                        data-province-target="moga_departure_province_id"
                        data-city-target="moga_departure_city_id"
                        data-district-wrapper="moga-departure-district-wrapper">
                        <?php foreach ( $countries as $code => $label ) : ?>
                            <option value="<?php echo esc_attr( $code ); ?>" <?php selected( $dep_country, $code ); ?>><?php echo esc_html( $label ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_departure_province_id"><?php esc_html_e( 'Departure Province / State', 'moga-travel-core' ); ?></label>
                    <select id="moga_departure_province_id" name="moga_departure_province_id"
                        class="moga-province-select"
                        data-city-target="moga_departure_city_id"
                        data-district-wrapper="moga-departure-district-wrapper"
                        data-name-field="moga_departure_province">
                        <?php if ( empty( $dep_prov_opts ) ) : ?>
                            <option value=""><?php esc_html_e( '— Select Country First —', 'moga-travel-core' ); ?></option>
                        <?php else : ?>
                            <option value=""><?php esc_html_e( '— Select Province —', 'moga-travel-core' ); ?></option>
                            <?php foreach ( $dep_prov_opts as $p ) : ?>
                                <option value="<?php echo esc_attr( $p['id'] ); ?>" <?php selected( $dep_province_id, (int) $p['id'] ); ?>><?php echo esc_html( $p['name'] ); ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <input type="hidden" id="moga_departure_province" name="moga_departure_province" value="<?php echo esc_attr( $dep_province ); ?>">
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_departure_city_id"><?php esc_html_e( 'Departure City', 'moga-travel-core' ); ?></label>
                    <select id="moga_departure_city_id" name="moga_departure_city_id"
                        class="moga-city-select"
                        data-district-wrapper="moga-departure-district-wrapper"
                        data-name-field="moga_departure_city">
                        <?php if ( empty( $dep_city_opts ) ) : ?>
                            <option value=""><?php esc_html_e( '— Select Province First —', 'moga-travel-core' ); ?></option>
                        <?php else : ?>
                            <option value=""><?php esc_html_e( '— Select City —', 'moga-travel-core' ); ?></option>
                            <?php foreach ( $dep_city_opts as $c ) : ?>
                                <option value="<?php echo esc_attr( $c['id'] ); ?>" <?php selected( $dep_city_id, (int) $c['id'] ); ?>><?php echo esc_html( $c['name'] ); ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <input type="hidden" id="moga_departure_city" name="moga_departure_city" value="<?php echo esc_attr( $dep_city ); ?>">
                </div>

                <div class="moga-metabox__field" id="moga-departure-district-wrapper">
                    <div class="moga-district-dropdown-field" style="display:none;">
                        <label for="moga_departure_district_select"><?php esc_html_e( 'Departure District', 'moga-travel-core' ); ?></label>
                        <select id="moga_departure_district_select" class="moga-district-select">
                            <option value=""><?php esc_html_e( '— Select District —', 'moga-travel-core' ); ?></option>
                        </select>
                        <span class="moga-district-loading" style="display:none;"><?php esc_html_e( 'Loading districts…', 'moga-travel-core' ); ?></span>
                    </div>
                    <div class="moga-district-text-field">
                        <label for="moga_departure_district" class="moga-district-text-label"><?php esc_html_e( 'Departure District / Area', 'moga-travel-core' ); ?></label>
                        <input type="text" id="moga_departure_district" name="moga_departure_district"
                            class="moga-district-text" value="<?php echo esc_attr( $dep_district ); ?>"
                            placeholder="<?php esc_attr_e( 'e.g. City Centre', 'moga-travel-core' ); ?>">
                    </div>
                </div>

                <div class="moga-metabox__field moga-metabox__field--wide">
                    <label for="moga_departure_point"><?php esc_html_e( 'Exact Departure Point', 'moga-travel-core' ); ?></label>
                    <input type="text" id="moga_departure_point" name="moga_departure_point"
                        value="<?php echo esc_attr( $dep_point ); ?>"
                        placeholder="<?php esc_attr_e( 'e.g. Cairo International Airport, Terminal 2', 'moga-travel-core' ); ?>">
                </div>
            </div>

            <?php // ---- DESTINATION SECTION ---- ?>
            <h4 class="moga-metabox__section-title"><?php esc_html_e( 'Destination', 'moga-travel-core' ); ?></h4>
            <div class="moga-metabox__row">
                <div class="moga-metabox__field">
                    <label for="moga_destination_country"><?php esc_html_e( 'Destination Country', 'moga-travel-core' ); ?></label>
                    <select id="moga_destination_country" name="moga_destination_country"
                        class="moga-country-select"
                        data-province-target="moga_destination_province_id"
                        data-city-target="moga_destination_city_id"
                        data-district-wrapper="moga-destination-district-wrapper">
                        <?php foreach ( $countries as $code => $label ) : ?>
                            <option value="<?php echo esc_attr( $code ); ?>" <?php selected( $dest_country, $code ); ?>><?php echo esc_html( $label ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_destination_province_id"><?php esc_html_e( 'Destination Province / State', 'moga-travel-core' ); ?></label>
                    <select id="moga_destination_province_id" name="moga_destination_province_id"
                        class="moga-province-select"
                        data-city-target="moga_destination_city_id"
                        data-district-wrapper="moga-destination-district-wrapper"
                        data-name-field="moga_destination_province">
                        <?php if ( empty( $dest_prov_opts ) ) : ?>
                            <option value=""><?php esc_html_e( '— Select Country First —', 'moga-travel-core' ); ?></option>
                        <?php else : ?>
                            <option value=""><?php esc_html_e( '— Select Province —', 'moga-travel-core' ); ?></option>
                            <?php foreach ( $dest_prov_opts as $p ) : ?>
                                <option value="<?php echo esc_attr( $p['id'] ); ?>" <?php selected( $dest_province_id, (int) $p['id'] ); ?>><?php echo esc_html( $p['name'] ); ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <input type="hidden" id="moga_destination_province" name="moga_destination_province" value="<?php echo esc_attr( $dest_province ); ?>">
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_destination_city_id"><?php esc_html_e( 'Destination City', 'moga-travel-core' ); ?></label>
                    <select id="moga_destination_city_id" name="moga_destination_city_id"
                        class="moga-city-select"
                        data-district-wrapper="moga-destination-district-wrapper"
                        data-name-field="moga_destination_city">
                        <?php if ( empty( $dest_city_opts ) ) : ?>
                            <option value=""><?php esc_html_e( '— Select Province First —', 'moga-travel-core' ); ?></option>
                        <?php else : ?>
                            <option value=""><?php esc_html_e( '— Select City —', 'moga-travel-core' ); ?></option>
                            <?php foreach ( $dest_city_opts as $c ) : ?>
                                <option value="<?php echo esc_attr( $c['id'] ); ?>" <?php selected( $dest_city_id, (int) $c['id'] ); ?>><?php echo esc_html( $c['name'] ); ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <input type="hidden" id="moga_destination_city" name="moga_destination_city" value="<?php echo esc_attr( $dest_city ); ?>">
                </div>

                <div class="moga-metabox__field" id="moga-destination-district-wrapper">
                    <div class="moga-district-dropdown-field" style="display:none;">
                        <label for="moga_destination_district_select"><?php esc_html_e( 'Destination District', 'moga-travel-core' ); ?></label>
                        <select id="moga_destination_district_select" class="moga-district-select">
                            <option value=""><?php esc_html_e( '— Select District —', 'moga-travel-core' ); ?></option>
                        </select>
                        <span class="moga-district-loading" style="display:none;"><?php esc_html_e( 'Loading districts…', 'moga-travel-core' ); ?></span>
                    </div>
                    <div class="moga-district-text-field">
                        <label for="moga_destination_district" class="moga-district-text-label"><?php esc_html_e( 'Destination District / Area', 'moga-travel-core' ); ?></label>
                        <input type="text" id="moga_destination_district" name="moga_destination_district"
                            class="moga-district-text" value="<?php echo esc_attr( $dest_district ); ?>"
                            placeholder="<?php esc_attr_e( 'e.g. Old Town', 'moga-travel-core' ); ?>">
                    </div>
                </div>
            </div>

        </div>
        <?php
    }


    /**
     * Render tour contact meta box.
     *
     * @since  1.0.0
     * @param  WP_Post $post Current post object.
     * @return void
     */
    public static function render_tour_contact( $post ) {
        wp_nonce_field( 'moga_tour_contact_nonce', 'moga_tour_contact_nonce' );

        $organizer = get_post_meta( $post->ID, '_moga_organizer_name', true );
        $phone     = get_post_meta( $post->ID, '_moga_phone',          true );
        $whatsapp  = get_post_meta( $post->ID, '_moga_whatsapp',       true );
        $email     = get_post_meta( $post->ID, '_moga_email',          true );
        ?>
        <div class="moga-metabox">
            <div class="moga-metabox__row">

                <div class="moga-metabox__field">
                    <label for="moga_organizer_name">
                        <?php esc_html_e( 'Organizer Name', 'moga-travel-core' ); ?>
                    </label>
                    <input type="text" id="moga_organizer_name" name="moga_organizer_name"
                        value="<?php echo esc_attr( $organizer ); ?>"
                        placeholder="<?php esc_attr_e( 'Tour organizer or company name', 'moga-travel-core' ); ?>">
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_tour_phone">
                        <?php esc_html_e( 'Phone Number', 'moga-travel-core' ); ?>
                    </label>
                    <input type="tel" id="moga_tour_phone" name="moga_phone"
                        value="<?php echo esc_attr( $phone ); ?>"
                        class="moga-phone-field"
                        placeholder="<?php esc_attr_e( 'Phone number', 'moga-travel-core' ); ?>">
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_tour_whatsapp">
                        <?php esc_html_e( 'WhatsApp Number', 'moga-travel-core' ); ?>
                    </label>
                    <input type="tel" id="moga_tour_whatsapp" name="moga_whatsapp"
                        value="<?php echo esc_attr( $whatsapp ); ?>"
                        class="moga-phone-field"
                        placeholder="<?php esc_attr_e( 'WhatsApp number', 'moga-travel-core' ); ?>">
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_tour_email">
                        <?php esc_html_e( 'Email Address', 'moga-travel-core' ); ?>
                    </label>
                    <input type="email" id="moga_tour_email" name="moga_email"
                        value="<?php echo esc_attr( $email ); ?>"
                        placeholder="<?php esc_attr_e( 'contact@example.com', 'moga-travel-core' ); ?>">
                </div>

            </div>
        </div>
        <?php
    }

    /**
     * Render tour details meta box.
     *
     * @since  1.0.0
     * @param  WP_Post $post Current post object.
     * @return void
     */
    public static function render_tour_details( $post ) {
        wp_nonce_field( 'moga_tour_details_nonce', 'moga_tour_details_nonce' );

        $max_participants = get_post_meta( $post->ID, '_moga_max_participants', true ) ?: 20;
        $min_participants = get_post_meta( $post->ID, '_moga_min_participants', true ) ?: 1;
        $difficulty       = get_post_meta( $post->ID, '_moga_difficulty',       true ) ?: 'easy';
        $tour_type        = get_post_meta( $post->ID, '_moga_tour_type',        true ) ?: 'group';
        $language         = get_post_meta( $post->ID, '_moga_language',         true ) ?: 'Arabic';
        $guide_included   = get_post_meta( $post->ID, '_moga_guide_included',   true );

        $difficulty_levels = Moga_CPT_Tour::get_difficulty_levels();
        $tour_types        = Moga_CPT_Tour::get_tour_types();
        ?>
        <div class="moga-metabox">
            <div class="moga-metabox__row">

                <div class="moga-metabox__field">
                    <label for="moga_max_participants">
                        <?php esc_html_e( 'Max Participants', 'moga-travel-core' ); ?>
                    </label>
                    <input type="number" id="moga_max_participants" name="moga_max_participants"
                        value="<?php echo esc_attr( $max_participants ); ?>" min="1" step="1">
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_min_participants">
                        <?php esc_html_e( 'Min Participants', 'moga-travel-core' ); ?>
                    </label>
                    <input type="number" id="moga_min_participants" name="moga_min_participants"
                        value="<?php echo esc_attr( $min_participants ); ?>" min="1" step="1">
                    <p class="moga-metabox__hint">
                        <?php esc_html_e( 'Tour runs only if this number is reached.', 'moga-travel-core' ); ?>
                    </p>
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_difficulty">
                        <?php esc_html_e( 'Difficulty Level', 'moga-travel-core' ); ?>
                    </label>
                    <select id="moga_difficulty" name="moga_difficulty">
                        <?php foreach ( $difficulty_levels as $key => $level ) : ?>
                            <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $difficulty, $key ); ?>>
                                <?php echo esc_html( $level['label'] ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_tour_type">
                        <?php esc_html_e( 'Tour Type', 'moga-travel-core' ); ?>
                    </label>
                    <select id="moga_tour_type" name="moga_tour_type">
                        <?php foreach ( $tour_types as $key => $type ) : ?>
                            <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $tour_type, $key ); ?>>
                                <?php echo esc_html( $type['label'] ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

            </div>

            <div class="moga-metabox__row">
                <div class="moga-metabox__field">
                    <label for="moga_language">
                        <?php esc_html_e( 'Tour Language', 'moga-travel-core' ); ?>
                    </label>
                    <input type="text" id="moga_language" name="moga_language"
                        value="<?php echo esc_attr( $language ); ?>"
                        placeholder="<?php esc_attr_e( 'e.g. Arabic, English', 'moga-travel-core' ); ?>">
                </div>

                <div class="moga-metabox__field">
                    <label class="moga-switch" style="margin-top:24px;">
                        <input type="checkbox" name="moga_guide_included" value="1"
                            <?php checked( '1', $guide_included ); ?>>
                        <span class="moga-switch__slider"></span>
                        <span class="moga-switch__label">
                            <?php esc_html_e( 'Guide Included', 'moga-travel-core' ); ?>
                        </span>
                    </label>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render tour includes / excludes meta box.
     *
     * @since  1.0.0
     * @param  WP_Post $post Current post object.
     * @return void
     */
    public static function render_tour_includes( $post ) {
        wp_nonce_field( 'moga_tour_includes_nonce', 'moga_tour_includes_nonce' );

        $saved_includes = get_post_meta( $post->ID, '_moga_includes', true );
        $saved_excludes = get_post_meta( $post->ID, '_moga_excludes', true );
        $saved_includes = $saved_includes ? json_decode( $saved_includes, true ) : array();
        $saved_excludes = $saved_excludes ? json_decode( $saved_excludes, true ) : array();

        $includes_options = Moga_CPT_Tour::get_includes_options();
        $excludes_options = Moga_CPT_Tour::get_excludes_options();
        ?>
        <div class="moga-metabox moga-metabox--two-col">

            <div class="moga-metabox__col">
                <h4 class="moga-metabox__section-title moga-metabox__section-title--green">
                    ✅ <?php esc_html_e( 'Included', 'moga-travel-core' ); ?>
                </h4>
                <div class="moga-checklist">
                    <?php foreach ( $includes_options as $key => $label ) : ?>
                        <label class="moga-checklist__item">
                            <input type="checkbox" name="moga_includes[]" value="<?php echo esc_attr( $key ); ?>"
                                <?php checked( in_array( $key, $saved_includes, true ) ); ?>>
                            <?php echo esc_html( $label ); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="moga-metabox__col">
                <h4 class="moga-metabox__section-title moga-metabox__section-title--red">
                    ❌ <?php esc_html_e( 'Not Included', 'moga-travel-core' ); ?>
                </h4>
                <div class="moga-checklist">
                    <?php foreach ( $excludes_options as $key => $label ) : ?>
                        <label class="moga-checklist__item">
                            <input type="checkbox" name="moga_excludes[]" value="<?php echo esc_attr( $key ); ?>"
                                <?php checked( in_array( $key, $saved_excludes, true ) ); ?>>
                            <?php echo esc_html( $label ); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
        <?php
    }

    /**
     * Render tour bus & seats meta box.
     *
     * @since  1.0.0
     * @param  WP_Post $post Current post object.
     * @return void
     */
    public static function render_tour_bus( $post ) {
        wp_nonce_field( 'moga_tour_bus_nonce', 'moga_tour_bus_nonce' );

        $bus_id          = get_post_meta( $post->ID, '_moga_bus_id',          true );
        $seats_total     = get_post_meta( $post->ID, '_moga_seats_total',     true );
        $seats_available = get_post_meta( $post->ID, '_moga_seats_available', true );
        $buses           = Moga_CPT_Bus::get_available_buses();
        ?>
        <div class="moga-metabox">
            <div class="moga-metabox__row">

                <div class="moga-metabox__field">
                    <label for="moga_bus_id">
                        <?php esc_html_e( 'Assign Bus', 'moga-travel-core' ); ?>
                    </label>
                    <select id="moga_bus_id" name="moga_bus_id">
                        <option value="">
                            <?php esc_html_e( '— No Bus Assigned —', 'moga-travel-core' ); ?>
                        </option>
                        <?php foreach ( $buses as $id => $label ) : ?>
                            <option value="<?php echo esc_attr( $id ); ?>" <?php selected( $bus_id, $id ); ?>>
                                <?php echo esc_html( $label ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ( empty( $buses ) ) : ?>
                        <p class="moga-metabox__hint moga-metabox__hint--warning">
                            <?php
                            printf(
                                /* translators: %s: link to add new bus */
                                esc_html__( 'No buses available. %s first.', 'moga-travel-core' ),
                                '<a href="' . esc_url( admin_url( 'post-new.php?post_type=moga_bus' ) ) . '">'
                                    . esc_html__( 'Add a Bus', 'moga-travel-core' )
                                . '</a>'
                            );
                            ?>
                        </p>
                    <?php endif; ?>
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_seats_total">
                        <?php esc_html_e( 'Total Seats', 'moga-travel-core' ); ?>
                    </label>
                    <input type="number" id="moga_seats_total" name="moga_seats_total"
                        value="<?php echo esc_attr( $seats_total ); ?>"
                        min="0" step="1" placeholder="0" readonly>
                    <p class="moga-metabox__hint">
                        <?php esc_html_e( 'Auto-populated from bus.', 'moga-travel-core' ); ?>
                    </p>
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_seats_available">
                        <?php esc_html_e( 'Available Seats', 'moga-travel-core' ); ?>
                    </label>
                    <input type="number" id="moga_seats_available" name="moga_seats_available"
                        value="<?php echo esc_attr( $seats_available ); ?>"
                        min="0" step="1" placeholder="0">
                </div>

            </div>
        </div>
        <?php
    }

    /**
     * Render tour status meta box.
     *
     * @since  1.0.0
     * @param  WP_Post $post Current post object.
     * @return void
     */
    public static function render_tour_status( $post ) {
        wp_nonce_field( 'moga_tour_status_nonce', 'moga_tour_status_nonce' );

        $featured        = get_post_meta( $post->ID, '_moga_featured',        true );
        $instant_booking = get_post_meta( $post->ID, '_moga_instant_booking', true );
        $active          = get_post_meta( $post->ID, '_moga_active',          true );

        if ( '' === $active ) {
            $active = '1';
        }
        ?>
        <div class="moga-metabox">
            <div class="moga-metabox__switches">

                <label class="moga-switch">
                    <input type="checkbox" name="moga_active" value="1" <?php checked( '1', $active ); ?>>
                    <span class="moga-switch__slider"></span>
                    <span class="moga-switch__label">
                        <?php esc_html_e( 'Active — visible to guests', 'moga-travel-core' ); ?>
                    </span>
                </label>

                <label class="moga-switch">
                    <input type="checkbox" name="moga_featured" value="1" <?php checked( '1', $featured ); ?>>
                    <span class="moga-switch__slider"></span>
                    <span class="moga-switch__label">
                        <?php esc_html_e( 'Featured — shown on homepage', 'moga-travel-core' ); ?>
                    </span>
                </label>

                <label class="moga-switch">
                    <input type="checkbox" name="moga_instant_booking" value="1" <?php checked( '1', $instant_booking ); ?>>
                    <span class="moga-switch__slider"></span>
                    <span class="moga-switch__label">
                        <?php esc_html_e( 'Instant Booking — no approval needed', 'moga-travel-core' ); ?>
                    </span>
                </label>

            </div>
        </div>
        <?php
    }


    // ============================================================
    // SAVE META BOXES
    // ============================================================

    /**
     * Save all meta box data when a post is saved.
     *
     * @since  1.0.0
     * @param  int     $post_id Post ID being saved.
     * @param  WP_Post $post    Post object being saved.
     * @return void
     */
    public static function save_meta_boxes( $post_id, $post ) {

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }

        // Save gallery and videos for all supported CPTs.
        $gallery_cpts = array( 'moga_property', 'moga_tour', 'moga_destination' );
        if ( in_array( $post->post_type, $gallery_cpts, true ) ) {
            self::save_gallery_meta( $post_id );
            self::save_videos_meta( $post_id );
        }

        if ( 'moga_property' === $post->post_type ) {
            self::save_property_meta( $post_id );
        } elseif ( 'moga_tour' === $post->post_type ) {
            self::save_tour_meta( $post_id );
        }
    }

    /**
     * Save gallery meta.
     *
     * @since  1.0.0
     * @param  int $post_id Post ID.
     * @return void
     */
    private static function save_gallery_meta( $post_id ) {

        if ( ! isset( $_POST['moga_gallery_nonce'] )
            || ! wp_verify_nonce(
                sanitize_text_field( wp_unslash( $_POST['moga_gallery_nonce'] ) ),
                'moga_gallery_nonce'
            )
        ) {
            return;
        }

        $gallery_ids = isset( $_POST['moga_gallery_ids'] ) && is_array( $_POST['moga_gallery_ids'] )
            ? array_map( 'absint', $_POST['moga_gallery_ids'] )
            : array();

        // Enforce maximum.
        $gallery_ids = array_slice( $gallery_ids, 0, self::MAX_GALLERY_IMAGES );

        update_post_meta( $post_id, '_moga_gallery', wp_json_encode( $gallery_ids ) );
    }

    /**
     * Save videos meta.
     *
     * @since  1.0.0
     * @param  int $post_id Post ID.
     * @return void
     */
    private static function save_videos_meta( $post_id ) {

        if ( ! isset( $_POST['moga_videos_nonce'] )
            || ! wp_verify_nonce(
                sanitize_text_field( wp_unslash( $_POST['moga_videos_nonce'] ) ),
                'moga_videos_nonce'
            )
        ) {
            return;
        }

        $videos = array();

        // Save URL videos.
        if ( isset( $_POST['moga_video_urls'] ) && is_array( $_POST['moga_video_urls'] ) ) {
            $urls = array_map( 'esc_url_raw', wp_unslash( $_POST['moga_video_urls'] ) );
            foreach ( $urls as $url ) {
                if ( ! empty( $url ) ) {
                    $videos[] = array(
                        'type' => 'url',
                        'url'  => $url,
                    );
                }
            }
        }

        // Enforce max URL videos.
        $url_videos = array_filter( $videos, fn( $v ) => 'url' === $v['type'] );
        if ( count( $url_videos ) > self::MAX_VIDEO_URLS ) {
            $videos = array_slice( array_values( $url_videos ), 0, self::MAX_VIDEO_URLS );
        }

        // Save uploaded videos.
        if ( isset( $_POST['moga_video_upload_ids'] ) && is_array( $_POST['moga_video_upload_ids'] ) ) {
            $upload_ids = array_map( 'absint', $_POST['moga_video_upload_ids'] );
            $upload_ids = array_slice( $upload_ids, 0, self::MAX_VIDEO_UPLOADS );
            foreach ( $upload_ids as $id ) {
                if ( $id > 0 ) {
                    $videos[] = array(
                        'type' => 'upload',
                        'id'   => $id,
                        'url'  => wp_get_attachment_url( $id ),
                    );
                }
            }
        }

        update_post_meta( $post_id, '_moga_videos', wp_json_encode( $videos ) );
    }

    /**
     * Save property meta fields.
     *
     * CHANGED in location system update:
     * - Now saves province, province_id, city_id fields
     * - sync_from_selection() updated for four-level hierarchy
     *
     * @since  1.0.0
     * @param  int $post_id Property post ID.
     * @return void
     */
    private static function save_property_meta( $post_id ) {

        // ---- Pricing ----
        if ( isset( $_POST['moga_property_pricing_nonce'] )
            && wp_verify_nonce(
                sanitize_text_field( wp_unslash( $_POST['moga_property_pricing_nonce'] ) ),
                'moga_property_pricing_nonce'
            )
        ) {
            update_post_meta( $post_id, '_moga_price_per_night',
                isset( $_POST['moga_price_per_night'] ) ? floatval( $_POST['moga_price_per_night'] ) : 0 );
            update_post_meta( $post_id, '_moga_price_weekend',
                isset( $_POST['moga_price_weekend'] ) ? floatval( $_POST['moga_price_weekend'] ) : 0 );
            update_post_meta( $post_id, '_moga_price_discount',
                isset( $_POST['moga_price_discount'] ) ? floatval( $_POST['moga_price_discount'] ) : 0 );
            update_post_meta( $post_id, '_moga_currency',
                isset( $_POST['moga_currency'] ) ? sanitize_text_field( wp_unslash( $_POST['moga_currency'] ) ) : 'USD' );
        }

        // ---- Location ----
        if ( isset( $_POST['moga_property_location_nonce'] )
            && wp_verify_nonce(
                sanitize_text_field( wp_unslash( $_POST['moga_property_location_nonce'] ) ),
                'moga_property_location_nonce'
            )
        ) {
            $country     = isset( $_POST['moga_country'] )     ? sanitize_text_field( wp_unslash( $_POST['moga_country'] ) ) : '';
            $province    = isset( $_POST['moga_province'] )    ? sanitize_text_field( wp_unslash( $_POST['moga_province'] ) ) : '';
            $province_id = isset( $_POST['moga_province_id'] ) ? absint( $_POST['moga_province_id'] ) : 0;
            $city        = isset( $_POST['moga_city'] )        ? sanitize_text_field( wp_unslash( $_POST['moga_city'] ) ) : '';
            $city_id     = isset( $_POST['moga_city_id'] )     ? absint( $_POST['moga_city_id'] ) : 0;
            $district    = isset( $_POST['moga_district'] )    ? sanitize_text_field( wp_unslash( $_POST['moga_district'] ) ) : '';
            $latitude    = isset( $_POST['moga_latitude'] )    ? sanitize_text_field( wp_unslash( $_POST['moga_latitude'] ) ) : '';
            $longitude   = isset( $_POST['moga_longitude'] )   ? sanitize_text_field( wp_unslash( $_POST['moga_longitude'] ) ) : '';

            update_post_meta( $post_id, '_moga_country',     $country );
            update_post_meta( $post_id, '_moga_province',    $province );
            update_post_meta( $post_id, '_moga_province_id', $province_id );
            update_post_meta( $post_id, '_moga_city',        $city );
            update_post_meta( $post_id, '_moga_city_id',     $city_id );
            update_post_meta( $post_id, '_moga_district',    $district );
            update_post_meta( $post_id, '_moga_address',
                isset( $_POST['moga_address'] ) ? sanitize_text_field( wp_unslash( $_POST['moga_address'] ) ) : '' );
            update_post_meta( $post_id, '_moga_postal_code',
                isset( $_POST['moga_postal_code'] ) ? sanitize_text_field( wp_unslash( $_POST['moga_postal_code'] ) ) : '' );
            update_post_meta( $post_id, '_moga_latitude',  $latitude );
            update_post_meta( $post_id, '_moga_longitude', $longitude );

            // Resolve country display name.
            $country_name = '';
            if ( $country ) {
                $country_data = moga_get_country( $country );
                if ( $country_data ) {
                    $country_name = $country_data['name'];
                    update_post_meta( $post_id, '_moga_country_name', $country_name );
                }
            }

            // Auto-sync to moga_location taxonomy (four levels).
            if ( $country && $province && $city ) {
                Moga_Tax_Location::sync_from_selection(
                    $post_id,
                    array(
                        'country_code'  => $country,
                        'country_name'  => $country_name,
                        'province_name' => $province,
                        'city_name'     => $city,
                        'district'      => $district,
                        'lat'           => $latitude,
                        'lng'           => $longitude,
                    )
                );
            }
        }

        // ---- Contact ----
        if ( isset( $_POST['moga_property_contact_nonce'] )
            && wp_verify_nonce(
                sanitize_text_field( wp_unslash( $_POST['moga_property_contact_nonce'] ) ),
                'moga_property_contact_nonce'
            )
        ) {
            update_post_meta( $post_id, '_moga_phone',
                isset( $_POST['moga_phone'] ) ? moga_sanitize_phone( wp_unslash( $_POST['moga_phone'] ) ) : '' );
            update_post_meta( $post_id, '_moga_whatsapp',
                isset( $_POST['moga_whatsapp'] ) ? moga_sanitize_phone( wp_unslash( $_POST['moga_whatsapp'] ) ) : '' );
            update_post_meta( $post_id, '_moga_email',
                isset( $_POST['moga_email'] ) ? sanitize_email( wp_unslash( $_POST['moga_email'] ) ) : '' );
        }

        // ---- Details ----
        if ( isset( $_POST['moga_property_details_nonce'] )
            && wp_verify_nonce(
                sanitize_text_field( wp_unslash( $_POST['moga_property_details_nonce'] ) ),
                'moga_property_details_nonce'
            )
        ) {
            update_post_meta( $post_id, '_moga_max_guests',
                isset( $_POST['moga_max_guests'] ) ? absint( $_POST['moga_max_guests'] ) : 1 );
            update_post_meta( $post_id, '_moga_bedrooms',
                isset( $_POST['moga_bedrooms'] ) ? absint( $_POST['moga_bedrooms'] ) : 1 );
            update_post_meta( $post_id, '_moga_bathrooms',
                isset( $_POST['moga_bathrooms'] ) ? floatval( $_POST['moga_bathrooms'] ) : 1 );
            update_post_meta( $post_id, '_moga_area',
                isset( $_POST['moga_area'] ) ? floatval( $_POST['moga_area'] ) : 0 );
            update_post_meta( $post_id, '_moga_floor',
                isset( $_POST['moga_floor'] ) ? absint( $_POST['moga_floor'] ) : 0 );
            update_post_meta( $post_id, '_moga_building_floors',
                isset( $_POST['moga_building_floors'] ) ? absint( $_POST['moga_building_floors'] ) : 1 );
            update_post_meta( $post_id, '_moga_year_built',
                isset( $_POST['moga_year_built'] ) ? absint( $_POST['moga_year_built'] ) : 0 );
            update_post_meta( $post_id, '_moga_cancellation',
                isset( $_POST['moga_cancellation'] ) ? sanitize_text_field( wp_unslash( $_POST['moga_cancellation'] ) ) : 'moderate' );
        }

        // ---- Amenities ----
        if ( isset( $_POST['moga_property_amenities_nonce'] )
            && wp_verify_nonce(
                sanitize_text_field( wp_unslash( $_POST['moga_property_amenities_nonce'] ) ),
                'moga_property_amenities_nonce'
            )
        ) {
            $amenities = isset( $_POST['moga_amenities'] ) && is_array( $_POST['moga_amenities'] )
                ? array_map( 'sanitize_text_field', wp_unslash( $_POST['moga_amenities'] ) )
                : array();
            update_post_meta( $post_id, '_moga_amenities', wp_json_encode( $amenities ) );
        }

        // ---- Booking Rules ----
        if ( isset( $_POST['moga_property_booking_nonce'] )
            && wp_verify_nonce(
                sanitize_text_field( wp_unslash( $_POST['moga_property_booking_nonce'] ) ),
                'moga_property_booking_nonce'
            )
        ) {
            update_post_meta( $post_id, '_moga_min_stay',
                isset( $_POST['moga_min_stay'] ) ? absint( $_POST['moga_min_stay'] ) : 1 );
            update_post_meta( $post_id, '_moga_max_stay',
                isset( $_POST['moga_max_stay'] ) ? absint( $_POST['moga_max_stay'] ) : 0 );
            update_post_meta( $post_id, '_moga_checkin_time',
                isset( $_POST['moga_checkin_time'] ) ? sanitize_text_field( wp_unslash( $_POST['moga_checkin_time'] ) ) : '14:00' );
            update_post_meta( $post_id, '_moga_checkout_time',
                isset( $_POST['moga_checkout_time'] ) ? sanitize_text_field( wp_unslash( $_POST['moga_checkout_time'] ) ) : '11:00' );
        }

        // ---- Status ----
        if ( isset( $_POST['moga_property_status_nonce'] )
            && wp_verify_nonce(
                sanitize_text_field( wp_unslash( $_POST['moga_property_status_nonce'] ) ),
                'moga_property_status_nonce'
            )
        ) {
            update_post_meta( $post_id, '_moga_active',
                isset( $_POST['moga_active'] ) ? '1' : '0' );
            update_post_meta( $post_id, '_moga_featured',
                isset( $_POST['moga_featured'] ) ? '1' : '0' );
            update_post_meta( $post_id, '_moga_instant_booking',
                isset( $_POST['moga_instant_booking'] ) ? '1' : '0' );
        }
    }

    /**
     * Save tour meta fields.
     *
     * CHANGED in location system update:
     * - Now saves province, province_id, city_id for both departure and destination
     * - sync_from_selection() updated for four-level hierarchy
     * - Tours have TWO locations (departure + destination). Departure syncs
     *   with append=false, destination with append=true so both coexist.
     *
     * @since  1.0.0
     * @param  int $post_id Tour post ID.
     * @return void
     */
    private static function save_tour_meta( $post_id ) {

        // ---- Pricing ----
        if ( isset( $_POST['moga_tour_pricing_nonce'] )
            && wp_verify_nonce(
                sanitize_text_field( wp_unslash( $_POST['moga_tour_pricing_nonce'] ) ),
                'moga_tour_pricing_nonce'
            )
        ) {
            update_post_meta( $post_id, '_moga_price_per_person',
                isset( $_POST['moga_price_per_person'] ) ? floatval( $_POST['moga_price_per_person'] ) : 0 );
            update_post_meta( $post_id, '_moga_price_child',
                isset( $_POST['moga_price_child'] ) ? floatval( $_POST['moga_price_child'] ) : 0 );
            update_post_meta( $post_id, '_moga_price_infant',
                isset( $_POST['moga_price_infant'] ) ? floatval( $_POST['moga_price_infant'] ) : 0 );
            update_post_meta( $post_id, '_moga_price_group',
                isset( $_POST['moga_price_group'] ) ? floatval( $_POST['moga_price_group'] ) : 0 );
            update_post_meta( $post_id, '_moga_currency',
                isset( $_POST['moga_currency'] ) ? sanitize_text_field( wp_unslash( $_POST['moga_currency'] ) ) : 'USD' );
        }

        // ---- Schedule ----
        if ( isset( $_POST['moga_tour_schedule_nonce'] )
            && wp_verify_nonce(
                sanitize_text_field( wp_unslash( $_POST['moga_tour_schedule_nonce'] ) ),
                'moga_tour_schedule_nonce'
            )
        ) {
            update_post_meta( $post_id, '_moga_duration_days',
                isset( $_POST['moga_duration_days'] ) ? absint( $_POST['moga_duration_days'] ) : 1 );
            update_post_meta( $post_id, '_moga_duration_nights',
                isset( $_POST['moga_duration_nights'] ) ? absint( $_POST['moga_duration_nights'] ) : 0 );
            update_post_meta( $post_id, '_moga_departure_time',
                isset( $_POST['moga_departure_time'] ) ? sanitize_text_field( wp_unslash( $_POST['moga_departure_time'] ) ) : '08:00' );
            update_post_meta( $post_id, '_moga_return_time',
                isset( $_POST['moga_return_time'] ) ? sanitize_text_field( wp_unslash( $_POST['moga_return_time'] ) ) : '18:00' );

            $available_days = isset( $_POST['moga_available_days'] ) && is_array( $_POST['moga_available_days'] )
                ? array_map( 'absint', $_POST['moga_available_days'] )
                : array();
            update_post_meta( $post_id, '_moga_available_days', wp_json_encode( $available_days ) );
        }

        // ---- Location ----
        if ( isset( $_POST['moga_tour_location_nonce'] )
            && wp_verify_nonce(
                sanitize_text_field( wp_unslash( $_POST['moga_tour_location_nonce'] ) ),
                'moga_tour_location_nonce'
            )
        ) {
            $dep_country     = isset( $_POST['moga_departure_country'] )     ? sanitize_text_field( wp_unslash( $_POST['moga_departure_country'] ) ) : '';
            $dep_province    = isset( $_POST['moga_departure_province'] )    ? sanitize_text_field( wp_unslash( $_POST['moga_departure_province'] ) ) : '';
            $dep_province_id = isset( $_POST['moga_departure_province_id'] ) ? absint( $_POST['moga_departure_province_id'] ) : 0;
            $dep_city        = isset( $_POST['moga_departure_city'] )        ? sanitize_text_field( wp_unslash( $_POST['moga_departure_city'] ) ) : '';
            $dep_city_id     = isset( $_POST['moga_departure_city_id'] )     ? absint( $_POST['moga_departure_city_id'] ) : 0;
            $dep_district    = isset( $_POST['moga_departure_district'] )    ? sanitize_text_field( wp_unslash( $_POST['moga_departure_district'] ) ) : '';

            $dest_country     = isset( $_POST['moga_destination_country'] )     ? sanitize_text_field( wp_unslash( $_POST['moga_destination_country'] ) ) : '';
            $dest_province    = isset( $_POST['moga_destination_province'] )    ? sanitize_text_field( wp_unslash( $_POST['moga_destination_province'] ) ) : '';
            $dest_province_id = isset( $_POST['moga_destination_province_id'] ) ? absint( $_POST['moga_destination_province_id'] ) : 0;
            $dest_city        = isset( $_POST['moga_destination_city'] )        ? sanitize_text_field( wp_unslash( $_POST['moga_destination_city'] ) ) : '';
            $dest_city_id     = isset( $_POST['moga_destination_city_id'] )     ? absint( $_POST['moga_destination_city_id'] ) : 0;
            $dest_district    = isset( $_POST['moga_destination_district'] )    ? sanitize_text_field( wp_unslash( $_POST['moga_destination_district'] ) ) : '';

            update_post_meta( $post_id, '_moga_departure_country',      $dep_country );
            update_post_meta( $post_id, '_moga_departure_province',     $dep_province );
            update_post_meta( $post_id, '_moga_departure_province_id',  $dep_province_id );
            update_post_meta( $post_id, '_moga_departure_city',         $dep_city );
            update_post_meta( $post_id, '_moga_departure_city_id',      $dep_city_id );
            update_post_meta( $post_id, '_moga_departure_district',     $dep_district );
            update_post_meta( $post_id, '_moga_destination_country',    $dest_country );
            update_post_meta( $post_id, '_moga_destination_province',   $dest_province );
            update_post_meta( $post_id, '_moga_destination_province_id',$dest_province_id );
            update_post_meta( $post_id, '_moga_destination_city',       $dest_city );
            update_post_meta( $post_id, '_moga_destination_city_id',    $dest_city_id );
            update_post_meta( $post_id, '_moga_destination_district',   $dest_district );
            update_post_meta( $post_id, '_moga_departure_point',
                isset( $_POST['moga_departure_point'] ) ? sanitize_text_field( wp_unslash( $_POST['moga_departure_point'] ) ) : '' );

            // Sync departure location to taxonomy (replaces existing terms).
            if ( $dep_country && $dep_province && $dep_city ) {
                $dep_country_data = moga_get_country( $dep_country );
                Moga_Tax_Location::sync_from_selection(
                    $post_id,
                    array(
                        'country_code'  => $dep_country,
                        'country_name'  => $dep_country_data ? $dep_country_data['name'] : '',
                        'province_name' => $dep_province,
                        'city_name'     => $dep_city,
                        'district'      => $dep_district,
                    ),
                    false
                );
            }

            // Sync destination location to taxonomy (appends to departure terms).
            if ( $dest_country && $dest_province && $dest_city ) {
                $dest_country_data = moga_get_country( $dest_country );
                Moga_Tax_Location::sync_from_selection(
                    $post_id,
                    array(
                        'country_code'  => $dest_country,
                        'country_name'  => $dest_country_data ? $dest_country_data['name'] : '',
                        'province_name' => $dest_province,
                        'city_name'     => $dest_city,
                        'district'      => $dest_district,
                    ),
                    true
                );
            }
        }

        // ---- Contact ----
        if ( isset( $_POST['moga_tour_contact_nonce'] )
            && wp_verify_nonce(
                sanitize_text_field( wp_unslash( $_POST['moga_tour_contact_nonce'] ) ),
                'moga_tour_contact_nonce'
            )
        ) {
            update_post_meta( $post_id, '_moga_organizer_name',
                isset( $_POST['moga_organizer_name'] ) ? sanitize_text_field( wp_unslash( $_POST['moga_organizer_name'] ) ) : '' );
            update_post_meta( $post_id, '_moga_phone',
                isset( $_POST['moga_phone'] ) ? moga_sanitize_phone( wp_unslash( $_POST['moga_phone'] ) ) : '' );
            update_post_meta( $post_id, '_moga_whatsapp',
                isset( $_POST['moga_whatsapp'] ) ? moga_sanitize_phone( wp_unslash( $_POST['moga_whatsapp'] ) ) : '' );
            update_post_meta( $post_id, '_moga_email',
                isset( $_POST['moga_email'] ) ? sanitize_email( wp_unslash( $_POST['moga_email'] ) ) : '' );
        }

        // ---- Details ----
        if ( isset( $_POST['moga_tour_details_nonce'] )
            && wp_verify_nonce(
                sanitize_text_field( wp_unslash( $_POST['moga_tour_details_nonce'] ) ),
                'moga_tour_details_nonce'
            )
        ) {
            update_post_meta( $post_id, '_moga_max_participants',
                isset( $_POST['moga_max_participants'] ) ? absint( $_POST['moga_max_participants'] ) : 20 );
            update_post_meta( $post_id, '_moga_min_participants',
                isset( $_POST['moga_min_participants'] ) ? absint( $_POST['moga_min_participants'] ) : 1 );
            update_post_meta( $post_id, '_moga_difficulty',
                isset( $_POST['moga_difficulty'] ) ? sanitize_text_field( wp_unslash( $_POST['moga_difficulty'] ) ) : 'easy' );
            update_post_meta( $post_id, '_moga_tour_type',
                isset( $_POST['moga_tour_type'] ) ? sanitize_text_field( wp_unslash( $_POST['moga_tour_type'] ) ) : 'group' );
            update_post_meta( $post_id, '_moga_language',
                isset( $_POST['moga_language'] ) ? sanitize_text_field( wp_unslash( $_POST['moga_language'] ) ) : 'Arabic' );
            update_post_meta( $post_id, '_moga_guide_included',
                isset( $_POST['moga_guide_included'] ) ? '1' : '0' );
        }

        // ---- Includes / Excludes ----
        if ( isset( $_POST['moga_tour_includes_nonce'] )
            && wp_verify_nonce(
                sanitize_text_field( wp_unslash( $_POST['moga_tour_includes_nonce'] ) ),
                'moga_tour_includes_nonce'
            )
        ) {
            $includes = isset( $_POST['moga_includes'] ) && is_array( $_POST['moga_includes'] )
                ? array_map( 'sanitize_text_field', wp_unslash( $_POST['moga_includes'] ) )
                : array();
            $excludes = isset( $_POST['moga_excludes'] ) && is_array( $_POST['moga_excludes'] )
                ? array_map( 'sanitize_text_field', wp_unslash( $_POST['moga_excludes'] ) )
                : array();
            update_post_meta( $post_id, '_moga_includes', wp_json_encode( $includes ) );
            update_post_meta( $post_id, '_moga_excludes', wp_json_encode( $excludes ) );
        }

        // ---- Bus & Seats ----
        if ( isset( $_POST['moga_tour_bus_nonce'] )
            && wp_verify_nonce(
                sanitize_text_field( wp_unslash( $_POST['moga_tour_bus_nonce'] ) ),
                'moga_tour_bus_nonce'
            )
        ) {
            $bus_id = isset( $_POST['moga_bus_id'] ) ? absint( $_POST['moga_bus_id'] ) : 0;
            update_post_meta( $post_id, '_moga_bus_id', $bus_id );
            update_post_meta( $post_id, '_moga_seats_available',
                isset( $_POST['moga_seats_available'] ) ? absint( $_POST['moga_seats_available'] ) : 0 );

            if ( $bus_id ) {
                $total = get_post_meta( $bus_id, '_moga_total_seats', true );
                if ( $total ) {
                    update_post_meta( $post_id, '_moga_seats_total', absint( $total ) );
                }
            }
        }

        // ---- Status ----
        if ( isset( $_POST['moga_tour_status_nonce'] )
            && wp_verify_nonce(
                sanitize_text_field( wp_unslash( $_POST['moga_tour_status_nonce'] ) ),
                'moga_tour_status_nonce'
            )
        ) {
            update_post_meta( $post_id, '_moga_active',
                isset( $_POST['moga_active'] ) ? '1' : '0' );
            update_post_meta( $post_id, '_moga_featured',
                isset( $_POST['moga_featured'] ) ? '1' : '0' );
            update_post_meta( $post_id, '_moga_instant_booking',
                isset( $_POST['moga_instant_booking'] ) ? '1' : '0' );
        }
    }



    // ============================================================
    // HELPERS — DB-powered location loaders (replaces GeoNames)
    // ============================================================

    /**
     * Get provinces for initial meta box render.
     * Queries mg_moga_loc_provinces by country ISO code.
     *
     * @since  1.0.0
     * @param  string $country_code ISO country code (e.g. 'EG').
     * @return array [{id, name}] or empty array.
     */
    private static function get_provinces_for_render( $country_code ) {
        if ( empty( $country_code ) ) {
            return array();
        }
        global $wpdb;
        $prefix     = $wpdb->prefix . MOGA_CORE_DB_PREFIX;
        $country_id = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$prefix}loc_countries WHERE iso_code = %s LIMIT 1",
            strtoupper( $country_code )
        ) );
        if ( ! $country_id ) {
            return array();
        }
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT id, name FROM {$prefix}loc_provinces WHERE country_id = %d ORDER BY name ASC",
            $country_id
        ), ARRAY_A ) ?: array();
    }

    /**
     * Get cities for initial meta box render.
     * Queries mg_moga_loc_cities by province DB id.
     *
     * @since  1.0.0
     * @param  int $province_id Province DB id.
     * @return array [{id, name}] or empty array.
     */
    private static function get_cities_for_province_render( $province_id ) {
        if ( ! $province_id ) {
            return array();
        }
        global $wpdb;
        $prefix = $wpdb->prefix . MOGA_CORE_DB_PREFIX;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT id, name FROM {$prefix}loc_cities WHERE province_id = %d ORDER BY name ASC",
            (int) $province_id
        ), ARRAY_A ) ?: array();
    }


    // ============================================================
    // ADMIN SCRIPTS
    // ============================================================

    /**
     * Output inline JavaScript for meta box interactions.
     *
     * Location cascade (four levels, DB-powered):
     *   Country → Province → City → District
     * All AJAX actions hit the location DB tables directly.
     * No GeoNames or external API calls.
     *
     * @since  1.0.0
     * @return void
     */
    public static function meta_box_scripts() {

        $screen = get_current_screen();

        if ( ! $screen || ! in_array(
            $screen->post_type,
            array( 'moga_property', 'moga_tour', 'moga_destination' ),
            true
        ) ) {
            return;
        }

        $max_gallery = self::MAX_GALLERY_IMAGES;
        $max_uploads = self::MAX_VIDEO_UPLOADS;

        $admin_data = array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'moga_nonce' ),
            'i18n'    => array(
                'selectCountryFirst'  => __( '— Select Country First —',   'moga-travel-core' ),
                'selectProvinceFirst' => __( '— Select Province First —',  'moga-travel-core' ),
                'selectProvince'      => __( '— Select Province —',        'moga-travel-core' ),
                'selectCity'          => __( '— Select City —',            'moga-travel-core' ),
                'selectDistrict'      => __( '— Select District —',        'moga-travel-core' ),
                'loadingProvinces'    => __( 'Loading provinces…',         'moga-travel-core' ),
                'loadingCities'       => __( 'Loading cities…',            'moga-travel-core' ),
                'loadingDistricts'    => __( 'Loading districts…',         'moga-travel-core' ),
                'districtLabel'       => __( 'District / Area',            'moga-travel-core' ),
                'orTypeManually'      => __( 'Or type manually:',          'moga-travel-core' ),
                'typeDistrict'        => __( 'e.g. Downtown, Zamalek',     'moga-travel-core' ),
            ),
        );
        ?>
        <script type="text/javascript">
        ( function( $ ) {
            'use strict';

            var mogaAdmin  = <?php echo wp_json_encode( $admin_data ); ?>;
            var maxGallery = <?php echo intval( $max_gallery ); ?>;
            var maxUploads = <?php echo intval( $max_uploads ); ?>;

            // In-memory caches — one entry per parent ID.
            var provinceCache = {};
            var cityCache     = {};
            var districtCache = {};


            // ================================================================
            // COUNTRY → PROVINCE (DB AJAX)
            // ================================================================

            $( document ).on( 'change', '.moga-country-select', function() {
                var $c             = $( this );
                var countryCode    = $c.val();
                var provTargetId   = $c.data( 'province-target' );
                var cityTargetId   = $c.data( 'city-target' );
                var distWrapper    = $c.data( 'district-wrapper' );
                var $provSelect    = $( '#' + provTargetId );
                var $citySelect    = $( '#' + cityTargetId );
                var $distWrapper   = distWrapper ? $( '#' + distWrapper ) : $();

                // Reset downstream selects and district.
                resetProvince( $provSelect );
                resetCity( $citySelect );
                if ( $distWrapper.length ) resetDistrict( $distWrapper );

                if ( ! countryCode ) return;

                if ( provinceCache[ countryCode ] ) {
                    populateProvinces( $provSelect, provinceCache[ countryCode ] );
                    return;
                }

                $provSelect.empty().append( $( '<option>' ).val( '' ).text( mogaAdmin.i18n.loadingProvinces ) ).prop( 'disabled', true );

                $.ajax( {
                    url:  mogaAdmin.ajaxUrl,
                    type: 'POST',
                    data: { action: 'moga_get_provinces', nonce: mogaAdmin.nonce, country_code: countryCode },
                    success: function( r ) {
                        if ( r.success && r.data.provinces && r.data.provinces.length ) {
                            provinceCache[ countryCode ] = r.data.provinces;
                            populateProvinces( $provSelect, r.data.provinces );
                        } else {
                            resetProvince( $provSelect );
                        }
                    },
                    error: function() { resetProvince( $provSelect ); }
                } );
            } );


            // ================================================================
            // PROVINCE → CITY (DB AJAX)
            // ================================================================

            $( document ).on( 'change', '.moga-province-select', function() {
                var $p           = $( this );
                var provinceId   = parseInt( $p.val(), 10 ) || 0;
                var cityTargetId = $p.data( 'city-target' );
                var distWrapper  = $p.data( 'district-wrapper' );
                var nameField    = $p.data( 'name-field' );
                var $citySelect  = $( '#' + cityTargetId );
                var $distWrapper = distWrapper ? $( '#' + distWrapper ) : $();

                // Sync province name hidden field.
                if ( nameField ) {
                    var pName = provinceId ? $p.find( ':selected' ).text().trim() : '';
                    $( '#' + nameField ).val( pName );
                }

                resetCity( $citySelect );
                if ( $distWrapper.length ) resetDistrict( $distWrapper );

                if ( ! provinceId ) return;

                if ( cityCache[ provinceId ] ) {
                    populateCities( $citySelect, cityCache[ provinceId ] );
                    return;
                }

                $citySelect.empty().append( $( '<option>' ).val( '' ).text( mogaAdmin.i18n.loadingCities ) ).prop( 'disabled', true );

                $.ajax( {
                    url:  mogaAdmin.ajaxUrl,
                    type: 'POST',
                    data: { action: 'moga_get_cities', nonce: mogaAdmin.nonce, province_id: provinceId },
                    success: function( r ) {
                        if ( r.success && r.data.cities && r.data.cities.length ) {
                            cityCache[ provinceId ] = r.data.cities;
                            populateCities( $citySelect, r.data.cities );
                        } else {
                            resetCity( $citySelect );
                        }
                    },
                    error: function() { resetCity( $citySelect ); }
                } );
            } );


            // ================================================================
            // CITY → DISTRICT (DB AJAX)
            // ================================================================

            $( document ).on( 'change', '.moga-city-select', function() {
                var $city       = $( this );
                var cityId      = parseInt( $city.val(), 10 ) || 0;
                var wrapperSel  = $city.data( 'district-wrapper' );
                var nameField   = $city.data( 'name-field' );
                var $wrapper    = wrapperSel ? $( '#' + wrapperSel ) : $();

                // Sync city name hidden field.
                if ( nameField ) {
                    var cName = cityId ? $city.find( ':selected' ).text().trim() : '';
                    $( '#' + nameField ).val( cName );
                }

                if ( $wrapper.length ) resetDistrict( $wrapper );
                if ( ! cityId || ! $wrapper.length ) return;

                if ( districtCache[ cityId ] !== undefined ) {
                    renderDistricts( $wrapper, districtCache[ cityId ], '' );
                    return;
                }

                $wrapper.find( '.moga-district-loading' ).show();

                $.ajax( {
                    url:  mogaAdmin.ajaxUrl,
                    type: 'POST',
                    data: { action: 'moga_get_districts', nonce: mogaAdmin.nonce, city_id: cityId },
                    success: function( r ) {
                        var districts = ( r.success && r.data.districts ) ? r.data.districts : [];
                        districtCache[ cityId ] = districts;
                        renderDistricts( $wrapper, districts, '' );
                    },
                    error: function() {
                        $wrapper.find( '.moga-district-loading' ).hide();
                        districtCache[ cityId ] = [];
                    }
                } );
            } );


            // ================================================================
            // POPULATE HELPERS
            // ================================================================

            function populateProvinces( $select, provinces ) {
                $select.empty().append( $( '<option>' ).val( '' ).text( mogaAdmin.i18n.selectProvince ) );
                $.each( provinces, function( i, p ) {
                    $select.append( $( '<option>' ).val( p.id ).text( p.name ) );
                } );
                $select.prop( 'disabled', false );
            }

            function populateCities( $select, cities ) {
                $select.empty().append( $( '<option>' ).val( '' ).text( mogaAdmin.i18n.selectCity ) );
                $.each( cities, function( i, c ) {
                    $select.append( $( '<option>' ).val( c.id ).text( c.name ) );
                } );
                $select.prop( 'disabled', false );
            }

            function resetProvince( $select ) {
                $select.empty().append( $( '<option>' ).val( '' ).text( mogaAdmin.i18n.selectCountryFirst ) ).prop( 'disabled', false );
                var nf = $select.data( 'name-field' );
                if ( nf ) $( '#' + nf ).val( '' );
            }

            function resetCity( $select ) {
                $select.empty().append( $( '<option>' ).val( '' ).text( mogaAdmin.i18n.selectProvinceFirst ) ).prop( 'disabled', false );
                var nf = $select.data( 'name-field' );
                if ( nf ) $( '#' + nf ).val( '' );
            }

            function renderDistricts( $wrapper, districts, savedDistrict ) {
                var $dropdownField = $wrapper.find( '.moga-district-dropdown-field' );
                var $select  = $wrapper.find( '.moga-district-select' );
                var $text    = $wrapper.find( '.moga-district-text' );
                var $label   = $wrapper.find( '.moga-district-text-label' );
                var $loading = $wrapper.find( '.moga-district-loading' );

                $loading.hide();

                if ( districts.length ) {
                    $select.empty().append( $( '<option>' ).val( '' ).text( mogaAdmin.i18n.selectDistrict ) );
                    $.each( districts, function( i, d ) {
                        var $opt = $( '<option>' ).val( d.name ).text( d.name );
                        if ( savedDistrict && d.name === savedDistrict ) $opt.prop( 'selected', true );
                        $select.append( $opt );
                    } );
                    $select.off( 'change.district' ).on( 'change.district', function() {
                        $text.val( $( this ).val() );
                    } );
                    if ( savedDistrict ) $text.val( savedDistrict );
                    $label.text( mogaAdmin.i18n.orTypeManually );
                    $dropdownField.show();
                } else {
                    $label.text( mogaAdmin.i18n.districtLabel );
                    $dropdownField.hide();
                }
            }

            function resetDistrict( $wrapper ) {
                $wrapper.find( '.moga-district-dropdown-field' ).hide();
                $wrapper.find( '.moga-district-select' ).empty();
                $wrapper.find( '.moga-district-text-label' ).text( mogaAdmin.i18n.districtLabel );
                $wrapper.find( '.moga-district-loading' ).hide();
                $wrapper.find( '.moga-district-text' ).val( '' );
            }


            // ================================================================
            // ON PAGE LOAD — auto-trigger district for already-selected cities
            // ================================================================

            $( document ).ready( function() {
                $( '.moga-city-select' ).each( function() {
                    var $sel         = $( this );
                    var cityId       = parseInt( $sel.val(), 10 ) || 0;
                    var wrapperSel   = $sel.data( 'district-wrapper' );
                    if ( ! cityId || ! wrapperSel ) return;
                    var $wrapper     = $( '#' + wrapperSel );
                    if ( ! $wrapper.length ) return;
                    var savedDistrict = $wrapper.find( '.moga-district-text' ).val();

                    if ( districtCache[ cityId ] !== undefined ) {
                        renderDistricts( $wrapper, districtCache[ cityId ], savedDistrict );
                        return;
                    }

                    $wrapper.find( '.moga-district-loading' ).show();

                    $.ajax( {
                        url:  mogaAdmin.ajaxUrl,
                        type: 'POST',
                        data: { action: 'moga_get_districts', nonce: mogaAdmin.nonce, city_id: cityId },
                        success: function( r ) {
                            var districts = ( r.success && r.data.districts ) ? r.data.districts : [];
                            districtCache[ cityId ] = districts;
                            renderDistricts( $wrapper, districts, savedDistrict );
                        },
                        error: function() { $wrapper.find( '.moga-district-loading' ).hide(); }
                    } );
                } );
            } );


            // ================================================================
            // PHOTO GALLERY
            // ================================================================

            var galleryFrame;

            function updateGalleryCount() {
                var count = $( '#moga-gallery-list .moga-gallery-box__item' ).length;
                $( '#moga-gallery-count' ).text( count );
                $( '#moga-gallery-add' ).prop( 'disabled', count >= maxGallery );
            }

            $( '#moga-gallery-add' ).on( 'click', function() {
                if ( $( '#moga-gallery-list .moga-gallery-box__item' ).length >= maxGallery ) return;

                if ( galleryFrame ) { galleryFrame.open(); return; }

                galleryFrame = wp.media( {
                    title:    '<?php echo esc_js( __( 'Select Gallery Photos', 'moga-travel-core' ) ); ?>',
                    button:   { text: '<?php echo esc_js( __( 'Add to Gallery', 'moga-travel-core' ) ); ?>' },
                    multiple: true,
                    library:  { type: 'image' },
                } );

                galleryFrame.on( 'select', function() {
                    var selection    = galleryFrame.state().get( 'selection' );
                    var currentCount = $( '#moga-gallery-list .moga-gallery-box__item' ).length;
                    var remaining    = maxGallery - currentCount;

                    selection.each( function( attachment, index ) {
                        if ( index >= remaining ) return;
                        var id    = attachment.get( 'id' );
                        var thumb = attachment.get( 'sizes' ) && attachment.get( 'sizes' ).thumbnail
                            ? attachment.get( 'sizes' ).thumbnail.url : attachment.get( 'url' );
                        if ( $( '#moga-gallery-list [data-id="' + id + '"]' ).length ) return;
                        $( '#moga-gallery-list' ).append(
                            '<li class="moga-gallery-box__item" data-id="' + id + '">' +
                            '<img src="' + thumb + '" alt="">' +
                            '<button type="button" class="moga-gallery-box__remove" title="Remove">✕</button>' +
                            '<input type="hidden" name="moga_gallery_ids[]" value="' + id + '">' +
                            '</li>'
                        );
                    } );
                    updateGalleryCount();
                } );

                galleryFrame.open();
            } );

            $( '#moga-gallery-list' ).on( 'click', '.moga-gallery-box__remove', function() {
                $( this ).closest( '.moga-gallery-box__item' ).remove();
                updateGalleryCount();
            } );

            if ( $.fn.sortable ) {
                $( '#moga-gallery-list' ).sortable( {
                    items:       '.moga-gallery-box__item',
                    cursor:      'grab',
                    opacity:     0.7,
                    placeholder: 'moga-gallery-box__placeholder',
                    tolerance:   'pointer',
                } );
            }


            // ================================================================
            // LOCAL VIDEO UPLOAD
            // ================================================================

            var videoFrame;

            $( '#moga-upload-video-add' ).on( 'click', function() {
                if ( $( '#moga-upload-video-list .moga-videos-box__upload-item' ).length >= maxUploads ) return;

                if ( videoFrame ) { videoFrame.open(); return; }

                videoFrame = wp.media( {
                    title:    '<?php echo esc_js( __( 'Upload or Select Video', 'moga-travel-core' ) ); ?>',
                    button:   { text: '<?php echo esc_js( __( 'Use this video', 'moga-travel-core' ) ); ?>' },
                    multiple: false,
                    library:  { type: 'video' },
                } );

                videoFrame.on( 'select', function() {
                    var attachment = videoFrame.state().get( 'selection' ).first().toJSON();
                    var id         = attachment.id;
                    var filename   = attachment.filename || attachment.url.split( '/' ).pop();
                    $( '#moga-upload-video-list' ).append(
                        '<li class="moga-videos-box__upload-item" data-id="' + id + '">' +
                        '<span class="moga-videos-box__upload-icon">🎬</span>' +
                        '<span class="moga-videos-box__upload-name">' + filename + '</span>' +
                        '<button type="button" class="moga-videos-box__remove-upload" title="Remove">✕</button>' +
                        '<input type="hidden" name="moga_video_upload_ids[]" value="' + id + '">' +
                        '</li>'
                    );
                    if ( $( '#moga-upload-video-list .moga-videos-box__upload-item' ).length >= maxUploads ) {
                        $( '#moga-upload-video-add' ).prop( 'disabled', true );
                    }
                } );

                videoFrame.open();
            } );

            $( '#moga-upload-video-list' ).on( 'click', '.moga-videos-box__remove-upload', function() {
                $( this ).closest( '.moga-videos-box__upload-item' ).remove();
                $( '#moga-upload-video-add' ).prop( 'disabled', false );
            } );


            // ================================================================
            // INTL-TEL-INPUT PHONE FIELD
            // ================================================================

            $( document ).ready( function() {
                var itiInputs = document.querySelectorAll( '.moga-phone-field' );
                if ( itiInputs.length && typeof window.intlTelInput === 'function' ) {
                    itiInputs.forEach( function( input ) {
                        window.intlTelInput( input, {
                            initialCountry:   'eg',
                            loadUtils:        function() {
                                return import( '<?php echo esc_js( defined( 'MOGA_THEME_URL' ) ? MOGA_THEME_URL . 'assets/js/vendor/intl-tel-input/utils.js' : '' ); ?>' );
                            },
                            separateDialCode: true,
                        } );
                    } );
                }
            } );

        } )( jQuery );
        </script>
        <?php
    }
}