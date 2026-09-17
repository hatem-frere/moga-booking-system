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

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Class Moga_Admin_Metaboxes
 */
class Moga_Admin_Metaboxes
{

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
    public static function init()
    {
        add_action('add_meta_boxes', array(__CLASS__, 'register_meta_boxes'));
        add_action('save_post',      array(__CLASS__, 'save_meta_boxes'), 10, 2);
        add_action('admin_footer',   array(__CLASS__, 'meta_box_scripts'));

        // Required-email enforcement — see require_contact_email() below.
        add_filter('wp_insert_post_data', array(__CLASS__, 'require_contact_email'), 10, 2);
        add_filter('redirect_post_location', array(__CLASS__, 'flag_email_required_redirect'), 10, 2);
        add_action('admin_notices', array(__CLASS__, 'show_email_required_notice'));
        add_action('admin_notices', array(__CLASS__, 'show_rejected_periods_notice'));
        add_action('admin_notices', array(__CLASS__, 'show_skipped_tour_groups_notice'));
    }


    /**
     * Days-of-week list for the Weekend Days checkboxes (Property
     * Pricing and Tour Schedule boxes), keyed to match PHP's
     * date('w')/gmdate('w') convention (0=Sunday..6=Saturday) —
     * this must match exactly what moga_calculate_property_price()
     * checks against in helper-price.php. Deliberately NOT reusing
     * Moga_CPT_Tour::get_weekdays() (used for "Available Days" /
     * tour departure days), since that method's day-numbering
     * convention serves a different concept and hasn't been
     * verified to use the same 0-indexed-Sunday scheme.
     *
     * @since  1.0.0
     * @return array day_num (0-6) => translated day name.
     */
    private static function get_days_of_week()
    {
        return array(
            0 => __('Sunday', 'moga-travel-core'),
            1 => __('Monday', 'moga-travel-core'),
            2 => __('Tuesday', 'moga-travel-core'),
            3 => __('Wednesday', 'moga-travel-core'),
            4 => __('Thursday', 'moga-travel-core'),
            5 => __('Friday', 'moga-travel-core'),
            6 => __('Saturday', 'moga-travel-core'),
        );
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
    public static function register_meta_boxes()
    {

        // ---- Property Meta Boxes ----
        // Ordered to match a natural "describe the listing" flow:
        // what it is -> where -> what's included -> how much it costs
        // -> the booking rules around it (kept next to Pricing, since
        // Pricing Periods also set their own stay-length rules) ->
        // how to reach the owner -> publish status.
        $property_boxes = array(
            array('moga_property_details',   __('🏠 Property Details',    'moga-travel-core'), 'render_property_details'),
            array('moga_property_location',  __('📍 Location',            'moga-travel-core'), 'render_property_location'),
            array('moga_property_amenities', __('✨ Amenities',           'moga-travel-core'), 'render_property_amenities'),
            array('moga_property_pricing',   __('💰 Rules and Prices',    'moga-travel-core'), 'render_property_pricing'),
            array('moga_property_contact',   __('📞 Contact',             'moga-travel-core'), 'render_property_contact'),
            array('moga_property_status',    __('⚙️ Status & Visibility', 'moga-travel-core'), 'render_property_status'),
        );

        foreach ($property_boxes as $box) {
            add_meta_box(
                $box[0],
                $box[1],
                array(__CLASS__, $box[2]),
                'moga_property',
                'normal',
                'high'
            );
        }

        // Property sidebar boxes.
        add_meta_box(
            'moga_property_gallery',
            __('📸 Photo Gallery', 'moga-travel-core'),
            array(__CLASS__, 'render_gallery_box'),
            'moga_property',
            'side',
            'default'
        );

        add_meta_box(
            'moga_property_videos',
            __('🎬 Videos', 'moga-travel-core'),
            array(__CLASS__, 'render_videos_box'),
            'moga_property',
            'side',
            'default'
        );

        // ---- Tour Meta Boxes ----
        $tour_boxes = array(
            array('moga_tour_details',   __('🗺️ Tour Details',        'moga-travel-core'), 'render_tour_details'),
            array('moga_tour_schedule', __('🗓️ Schedule',            'moga-travel-core'), 'render_tour_schedule'),
            array('moga_tour_location', __('📍 Location & Route',    'moga-travel-core'), 'render_tour_location'),
            array('moga_tour_itinerary', __('🧭 Itinerary',           'moga-travel-core'), 'render_tour_itinerary'),
            array('moga_tour_includes',  __('✅ Includes & Excludes', 'moga-travel-core'), 'render_tour_includes'),
            array('moga_tour_pricing',  __('🎟️ Tour Groups',        'moga-travel-core'), 'render_tour_groups'),
            array('moga_tour_bus',      __('🚌 Bus & Seats',         'moga-travel-core'), 'render_tour_bus'),
            array('moga_tour_contact',  __('📞 Organizer Contact',   'moga-travel-core'), 'render_tour_contact'),
            array('moga_tour_status',   __('⚙️ Status & Visibility', 'moga-travel-core'), 'render_tour_status'),
        );

        foreach ($tour_boxes as $box) {
            add_meta_box(
                $box[0],
                $box[1],
                array(__CLASS__, $box[2]),
                'moga_tour',
                'normal',
                'high'
            );
        }

        // Tour sidebar boxes.
        add_meta_box(
            'moga_tour_gallery',
            __('📸 Photo Gallery', 'moga-travel-core'),
            array(__CLASS__, 'render_gallery_box'),
            'moga_tour',
            'side',
            'default'
        );

        add_meta_box(
            'moga_tour_videos',
            __('🎬 Videos', 'moga-travel-core'),
            array(__CLASS__, 'render_videos_box'),
            'moga_tour',
            'side',
            'default'
        );

        // ---- Destination sidebar boxes ----
        add_meta_box(
            'moga_destination_gallery',
            __('📸 Photo Gallery', 'moga-travel-core'),
            array(__CLASS__, 'render_gallery_box'),
            'moga_destination',
            'side',
            'default'
        );

        add_meta_box(
            'moga_destination_videos',
            __('🎬 Videos', 'moga-travel-core'),
            array(__CLASS__, 'render_videos_box'),
            'moga_destination',
            'side',
            'default'
        );

        // ---- Bus Meta Boxes ----
        // Two boxes: identity/amenities (main column) + seat layout (main column).
        // The Bus editor is admin/organizer-only — no gallery or video boxes needed.
        $bus_boxes = array(
            array('moga_bus_details', __('🚌 Bus Details',    'moga-travel-core'), 'render_bus_details'),
            array('moga_bus_seats',   __('💺 Seat Layout',    'moga-travel-core'), 'render_bus_seats'),
        );

        foreach ($bus_boxes as $box) {
            add_meta_box(
                $box[0],
                $box[1],
                array(__CLASS__, $box[2]),
                'moga_bus',
                'normal',
                'high'
            );
        }
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
    public static function render_gallery_box($post)
    {
        wp_nonce_field('moga_gallery_nonce', 'moga_gallery_nonce');

        $gallery     = get_post_meta($post->ID, '_moga_gallery', true);
        $gallery_ids = $gallery ? json_decode($gallery, true) : array();
        $max         = self::MAX_GALLERY_IMAGES;
        $count       = count($gallery_ids);
?>
        <div class="moga-gallery-box">

            <div class="moga-gallery-box__header">
                <span class="moga-gallery-box__count">
                    <span id="moga-gallery-count"><?php echo esc_html($count); ?></span>
                    /<?php echo esc_html($max); ?>
                    <?php esc_html_e('photos', 'moga-travel-core'); ?>
                </span>
            </div>

            <ul
                id="moga-gallery-list"
                class="moga-gallery-box__list"
                data-max="<?php echo esc_attr($max); ?>">
                <?php foreach ($gallery_ids as $attachment_id) : ?>
                    <?php
                    $thumb = wp_get_attachment_image_url($attachment_id, 'thumbnail');
                    if (! $thumb) {
                        continue;
                    }
                    ?>
                    <li class="moga-gallery-box__item" data-id="<?php echo esc_attr($attachment_id); ?>">
                        <img src="<?php echo esc_url($thumb); ?>" alt="">
                        <button
                            type="button"
                            class="moga-gallery-box__remove"
                            title="<?php esc_attr_e('Remove', 'moga-travel-core'); ?>">✕</button>
                        <input
                            type="hidden"
                            name="moga_gallery_ids[]"
                            value="<?php echo esc_attr($attachment_id); ?>">
                    </li>
                <?php endforeach; ?>
            </ul>

            <button
                type="button"
                id="moga-gallery-add"
                class="moga-gallery-box__btn button"
                <?php echo $count >= $max ? 'disabled' : ''; ?>>
                + <?php esc_html_e('Add Photos', 'moga-travel-core'); ?>
            </button>

            <?php if ($count >= $max) : ?>
                <p class="moga-metabox__hint moga-metabox__hint--warning">
                    <?php
                    printf(
                        /* translators: %d: max images */
                        esc_html__('Maximum %d photos reached.', 'moga-travel-core'),
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
    public static function render_videos_box($post)
    {
        wp_nonce_field('moga_videos_nonce', 'moga_videos_nonce');

        $videos_meta    = get_post_meta($post->ID, '_moga_videos', true);
        $videos         = $videos_meta ? json_decode($videos_meta, true) : array();
        $max_urls       = self::MAX_VIDEO_URLS;
        $max_uploads    = self::MAX_VIDEO_UPLOADS;

        // Separate URL videos and uploaded videos.
        $url_videos    = array_filter($videos, fn($v) => isset($v['type']) && 'url' === $v['type']);
        $upload_videos = array_filter($videos, fn($v) => isset($v['type']) && 'upload' === $v['type']);

        // Reset array keys.
        $url_videos    = array_values($url_videos);
        $upload_videos = array_values($upload_videos);
    ?>
        <div class="moga-videos-box">

            <?php // ---- Section 1: YouTube / Vimeo URLs ----
            ?>
            <div class="moga-videos-box__section">
                <h4 class="moga-videos-box__section-title">
                    🔗 <?php esc_html_e('YouTube / Vimeo URLs', 'moga-travel-core'); ?>
                    <span class="moga-videos-box__max">
                        <?php
                        printf(
                            /* translators: %d: max videos */
                            esc_html__('(max %d)', 'moga-travel-core'),
                            $max_urls
                        );
                        ?>
                    </span>
                </h4>

                <?php for ($i = 0; $i < $max_urls; $i++) : ?>
                    <div class="moga-videos-box__url-row">
                        <input
                            type="url"
                            name="moga_video_urls[]"
                            value="<?php echo esc_attr(isset($url_videos[$i]['url']) ? $url_videos[$i]['url'] : ''); ?>"
                            placeholder="https://youtube.com/watch?v=... or https://vimeo.com/..."
                            class="moga-videos-box__url-input">
                    </div>
                <?php endfor; ?>
            </div>

            <?php // ---- Section 2: Local Video Uploads ----
            ?>
            <div class="moga-videos-box__section">
                <h4 class="moga-videos-box__section-title">
                    📁 <?php esc_html_e('Upload Local Videos', 'moga-travel-core'); ?>
                    <span class="moga-videos-box__max">
                        <?php
                        printf(
                            /* translators: %d: max uploads */
                            esc_html__('(max %d)', 'moga-travel-core'),
                            $max_uploads
                        );
                        ?>
                    </span>
                </h4>

                <ul
                    id="moga-upload-video-list"
                    class="moga-videos-box__upload-list"
                    data-max="<?php echo esc_attr($max_uploads); ?>">
                    <?php foreach ($upload_videos as $video) : ?>
                        <?php
                        $attachment_id  = isset($video['id']) ? intval($video['id']) : 0;
                        $attachment_url = $attachment_id ? wp_get_attachment_url($attachment_id) : '';
                        $filename       = $attachment_id ? basename($attachment_url) : '';
                        if (! $attachment_url) {
                            continue;
                        }
                        ?>
                        <li
                            class="moga-videos-box__upload-item"
                            data-id="<?php echo esc_attr($attachment_id); ?>">
                            <span class="moga-videos-box__upload-icon">🎬</span>
                            <span class="moga-videos-box__upload-name">
                                <?php echo esc_html($filename); ?>
                            </span>
                            <button
                                type="button"
                                class="moga-videos-box__remove-upload"
                                title="<?php esc_attr_e('Remove', 'moga-travel-core'); ?>">✕</button>
                            <input
                                type="hidden"
                                name="moga_video_upload_ids[]"
                                value="<?php echo esc_attr($attachment_id); ?>">
                        </li>
                    <?php endforeach; ?>
                </ul>

                <?php if (count($upload_videos) < $max_uploads) : ?>
                    <button
                        type="button"
                        id="moga-upload-video-add"
                        class="moga-videos-box__btn button">
                        + <?php esc_html_e('Upload Video', 'moga-travel-core'); ?>
                    </button>
                    <p class="moga-metabox__hint">
                        <?php esc_html_e('Supported: MP4, WebM, OGV. Max 100MB each.', 'moga-travel-core'); ?>
                    </p>
                <?php else : ?>
                    <p class="moga-metabox__hint moga-metabox__hint--warning">
                        <?php
                        printf(
                            /* translators: %d: max uploads */
                            esc_html__('Maximum %d video uploads reached.', 'moga-travel-core'),
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
    public static function render_property_pricing($post)
    {
        wp_nonce_field('moga_property_pricing_nonce', 'moga_property_pricing_nonce');

        $currency   = get_post_meta($post->ID, '_moga_currency', true) ?: 'USD';
        $currencies = moga_get_currencies();

        $periods_json = get_post_meta($post->ID, '_moga_pricing_periods', true);
        $periods      = $periods_json ? json_decode($periods_json, true) : array();
        $periods      = is_array($periods) ? $periods : array();
    ?>
        <div class="moga-metabox">

            <div class="moga-metabox__row">
                <div class="moga-metabox__field" style="max-width:260px;">
                    <label for="moga_currency">
                        <?php esc_html_e('Currency', 'moga-travel-core'); ?>
                    </label>
                    <select id="moga_currency" name="moga_currency">
                        <?php foreach ($currencies as $code => $label) : ?>
                            <option value="<?php echo esc_attr($code); ?>" <?php selected($currency, $code); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="moga-metabox__hint">
                        <?php esc_html_e('Applies to every period below.', 'moga-travel-core'); ?>
                    </p>
                </div>
            </div>

            <div class="moga-metabox__row moga-metabox__row--full" style="margin-top:20px;border-top:1px solid #e2e4e7;padding-top:20px;">
                <div class="moga-metabox__field">
                    <p class="moga-metabox__hint">
                        <?php esc_html_e('Every price and booking rule lives inside a period — start and end dates, nightly rate, weekend rate, discount, stay length, and check-in/out times. Guests can book any sub-range within a period, subject to that period\'s own rules. Periods cannot overlap each other — you\'ll see an error naming the conflicting dates if you try.', 'moga-travel-core'); ?>
                    </p>

                    <div id="moga-periods-list">
                        <?php foreach ($periods as $index => $period) : ?>
                            <?php echo self::render_pricing_period_row($index, $period); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            ?>
                        <?php endforeach; ?>
                    </div>

                    <button type="button" id="moga-periods-add" class="moga-metabox__btn button">
                        + <span id="moga-periods-add-label"><?php echo empty($periods)
                                                                ? esc_html__('Add Period', 'moga-travel-core')
                                                                : esc_html__('Add Another Period', 'moga-travel-core'); ?></span>
                    </button>

                    <?php // Hidden template for new rows — JS replaces __INDEX__ on insert.
                    ?>
                    <script type="text/template" id="moga-periods-row-template"><?php echo self::render_pricing_period_row('__INDEX__', array()); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                                                ?></script>
                </div>
            </div>

        </div>
    <?php
    }

    /**
     * Render a single Pricing Period card (used for both initial
     * render and as the markup JS clones for new rows — see
     * moga-periods-row-template in meta_box_scripts()).
     *
     * Every booking rule that used to be a flat, property-wide
     * setting (price, weekend price, weekend days, discount, min/max
     * stay, check-in/out times) now lives HERE, per period — a
     * property has no default rate of its own anymore; every price
     * comes from whichever period covers the guest's chosen dates.
     *
     * @since  1.0.0
     * @param  int|string $index  Row index (numeric on render, '__INDEX__' placeholder for the JS template).
     * @param  array      $period Period data — empty array for a blank card.
     * @return string HTML for the card.
     */
    private static function render_pricing_period_row($index, $period)
    {
        $start         = isset($period['start'])         ? $period['start']         : '';
        $end           = isset($period['end'])           ? $period['end']           : '';
        $price         = isset($period['price'])         ? $period['price']         : '';
        $weekend_price = isset($period['weekend_price']) ? $period['weekend_price'] : '';
        $discount      = isset($period['discount'])      ? $period['discount']      : '';
        $checkin_time  = isset($period['checkin_time'])  ? $period['checkin_time']  : '14:00';
        $checkout_time = isset($period['checkout_time']) ? $period['checkout_time'] : '11:00';
        // Min Nights gets a REAL default value of 1, not just a
        // placeholder — a placeholder LOOKS like a value but isn't
        // submitted unless actually typed (this caused a real bug
        // earlier — an owner saw grey "1" text, assumed it was
        // already set, and it saved as empty instead).
        $min_stay      = isset($period['min_stay']) && '' !== $period['min_stay'] ? $period['min_stay'] : '1';
        $max_stay      = isset($period['max_stay'])      ? $period['max_stay']      : '';

        $weekend_days     = isset($period['weekend_days']) && is_array($period['weekend_days']) ? $period['weekend_days'] : array();
        $has_weekend_days = ! empty($weekend_days);

        // Header title shows the actual date range once both dates
        // are set, reusing the same smart date-range formatter used
        // everywhere else in the codebase — falls back to a generic
        // label for a brand-new, still-empty card.
        $title_text = ($start && $end)
            ? moga_format_date_range($start, $end)
            : __('New Period', 'moga-travel-core');

        ob_start();
    ?>
        <div class="moga-period-card">
            <div class="moga-period-card__header">
                <button type="button" class="moga-period-card__toggle" aria-label="<?php esc_attr_e('Expand or collapse', 'moga-travel-core'); ?>">▾</button>
                <span class="moga-period-card__title" data-default-label="<?php esc_attr_e('New Period', 'moga-travel-core'); ?>"><?php echo esc_html($title_text); ?></span>
                <button type="button" class="moga-period-card__remove" title="<?php esc_attr_e('Remove', 'moga-travel-core'); ?>">✕</button>
            </div>

            <div class="moga-period-card__body">

                <div class="moga-metabox__field" style="margin-bottom:16px;">
                    <label><?php esc_html_e('Weekend Days', 'moga-travel-core'); ?></label>
                    <div class="moga-weekdays">
                        <?php foreach (self::get_days_of_week() as $day_num => $day_label) : ?>
                            <label class="moga-weekday">
                                <input
                                    type="checkbox"
                                    class="moga-period-weekday-checkbox"
                                    name="moga_pricing_periods[<?php echo esc_attr($index); ?>][weekend_days][]"
                                    value="<?php echo esc_attr($day_num); ?>"
                                    <?php checked(in_array((string) $day_num, array_map('strval', $weekend_days), true)); ?>>
                                <span><?php echo esc_html(substr($day_label, 0, 3)); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="moga-period-card__grid">
                    <div class="moga-metabox__field">
                        <label><?php esc_html_e('Start Date', 'moga-travel-core'); ?></label>
                        <input type="date" class="moga-period-start" name="moga_pricing_periods[<?php echo esc_attr($index); ?>][start]"
                            value="<?php echo esc_attr($start); ?>">
                    </div>
                    <div class="moga-metabox__field">
                        <label><?php esc_html_e('End Date', 'moga-travel-core'); ?></label>
                        <input type="date" class="moga-period-end" name="moga_pricing_periods[<?php echo esc_attr($index); ?>][end]"
                            value="<?php echo esc_attr($end); ?>">
                    </div>
                    <div class="moga-metabox__field">
                        <label><?php esc_html_e('Price/Night', 'moga-travel-core'); ?></label>
                        <input type="number" min="0" step="0.01" placeholder="0.00"
                            name="moga_pricing_periods[<?php echo esc_attr($index); ?>][price]"
                            value="<?php echo esc_attr($price); ?>">
                    </div>
                    <div class="moga-metabox__field">
                        <label><?php esc_html_e('Weekend Price', 'moga-travel-core'); ?></label>
                        <input type="number" min="0" step="0.01" class="moga-period-weekend-price"
                            placeholder="<?php esc_attr_e('optional', 'moga-travel-core'); ?>"
                            name="moga_pricing_periods[<?php echo esc_attr($index); ?>][weekend_price]"
                            value="<?php echo esc_attr($weekend_price); ?>"
                            <?php disabled(! $has_weekend_days); ?>>
                        <p class="moga-metabox__hint">
                            <?php esc_html_e('Check at least one Weekend Day above first.', 'moga-travel-core'); ?>
                        </p>
                    </div>
                    <div class="moga-metabox__field">
                        <label><?php esc_html_e('Discount %', 'moga-travel-core'); ?></label>
                        <input type="number" min="0" max="100" step="1" placeholder="0"
                            name="moga_pricing_periods[<?php echo esc_attr($index); ?>][discount]"
                            value="<?php echo esc_attr($discount); ?>">
                    </div>
                    <div class="moga-metabox__field">
                        <label><?php esc_html_e('Min Nights', 'moga-travel-core'); ?></label>
                        <input type="number" min="1" step="1"
                            name="moga_pricing_periods[<?php echo esc_attr($index); ?>][min_stay]"
                            value="<?php echo esc_attr($min_stay); ?>">
                    </div>
                    <div class="moga-metabox__field">
                        <label><?php esc_html_e('Max Nights', 'moga-travel-core'); ?></label>
                        <input type="number" min="1" step="1" placeholder="<?php esc_attr_e('optional', 'moga-travel-core'); ?>"
                            name="moga_pricing_periods[<?php echo esc_attr($index); ?>][max_stay]"
                            value="<?php echo esc_attr($max_stay); ?>">
                        <p class="moga-metabox__hint">
                            <?php esc_html_e('Leave empty for no maximum.', 'moga-travel-core'); ?>
                        </p>
                    </div>
                    <div class="moga-metabox__field">
                        <label><?php esc_html_e('Check-in Time', 'moga-travel-core'); ?></label>
                        <input type="time" name="moga_pricing_periods[<?php echo esc_attr($index); ?>][checkin_time]"
                            value="<?php echo esc_attr($checkin_time); ?>">
                    </div>
                    <div class="moga-metabox__field">
                        <label><?php esc_html_e('Check-out Time', 'moga-travel-core'); ?></label>
                        <input type="time" name="moga_pricing_periods[<?php echo esc_attr($index); ?>][checkout_time]"
                            value="<?php echo esc_attr($checkout_time); ?>">
                    </div>
                </div>

            </div>
        </div>
    <?php
        return ob_get_clean();
    }

    /**
     * Save the Pricing Periods repeater.
     *
     * Validates each submitted row, REJECTS (does not save) any
     * period that overlaps an already-accepted one in the same
     * submission — deliberately never guesses a winner for
     * overlapping dates. An automatic "last one wins" rule was
     * considered and rejected: it would ask the owner to trust an
     * invisible save-order rule with no on-screen indication, and a
     * bug in that logic could silently apply the wrong (often
     * cheaper) rate to every future booking touching those dates —
     * a real, ongoing revenue loss rather than one visible mistake.
     * Rejected periods are surfaced as a clear one-time admin
     * notice naming the exact conflicting dates.
     *
     * Clears the OLD periods' own date ranges (via
     * Moga_Availability::clear_period(), which never touches
     * status/booking_id) before applying the newly-accepted set —
     * so editing or deleting a period cleanly resets its old dates
     * rather than leaving stale price/stay-length data behind.
     *
     * @since  1.0.0
     * @param  int $post_id Property post ID.
     * @return void
     */
    private static function save_pricing_periods($post_id)
    {
        $core = function_exists('moga_core') ? moga_core() : null;
        if (! $core || ! $core->availability) {
            return;
        }

        // ---- Clear whatever the OLD periods used to cover ----
        $old_periods_json = get_post_meta($post_id, '_moga_pricing_periods', true);
        $old_periods       = $old_periods_json ? json_decode($old_periods_json, true) : array();
        if (is_array($old_periods)) {
            foreach ($old_periods as $old_period) {
                if (! empty($old_period['start']) && ! empty($old_period['end'])) {
                    $core->availability->clear_period($post_id, 'property', $old_period['start'], $old_period['end']);
                }
            }
        }

        // ---- Parse and sanitize submitted rows ----
        $input = isset($_POST['moga_pricing_periods']) && is_array($_POST['moga_pricing_periods'])
            ? wp_unslash($_POST['moga_pricing_periods'])
            : array();

        $candidates    = array();
        $incomplete    = array();

        foreach ($input as $row_number => $row) {
            $start = isset($row['start']) ? sanitize_text_field($row['start']) : '';
            $end   = isset($row['end'])   ? sanitize_text_field($row['end'])   : '';
            $price = isset($row['price']) ? floatval($row['price']) : 0;

            // A genuinely blank row — added, then left completely
            // untouched. Silently ignored — there's nothing to
            // report, since nothing was ever attempted.
            if ('' === $start && '' === $end && $price <= 0) {
                continue;
            }

            // Someone started filling this row in, but it's missing
            // something required — reported by name, not silently
            // dropped, so it can never look like data just vanished.
            $missing = array();
            if (! moga_is_valid_date($start)) {
                $missing[] = __('Start Date', 'moga-travel-core');
            }
            if (! moga_is_valid_date($end)) {
                $missing[] = __('End Date', 'moga-travel-core');
            }
            if (moga_is_valid_date($start) && moga_is_valid_date($end) && strtotime($end) <= strtotime($start)) {
                $missing[] = __('End Date must be after Start Date', 'moga-travel-core');
            }
            if ($price <= 0) {
                $missing[] = __('Price/Night', 'moga-travel-core');
            }

            if (! empty($missing)) {
                $incomplete[] = sprintf(
                    /* translators: 1: row number, 2: comma-separated list of missing/invalid fields */
                    __('Period row %1$d was not saved — missing or invalid: %2$s.', 'moga-travel-core'),
                    $row_number + 1,
                    implode(', ', $missing)
                );
                continue;
            }

            $weekend_days_input = isset($row['weekend_days']) && is_array($row['weekend_days'])
                ? array_map('absint', $row['weekend_days'])
                : array();

            $candidates[] = array(
                'start'         => $start,
                'end'           => $end,
                'price'         => $price,
                'weekend_price' => (isset($row['weekend_price']) && '' !== $row['weekend_price']) ? floatval($row['weekend_price']) : null,
                'min_stay'      => (isset($row['min_stay']) && '' !== $row['min_stay']) ? absint($row['min_stay']) : null,
                'max_stay'      => (isset($row['max_stay']) && '' !== $row['max_stay']) ? absint($row['max_stay']) : null,
                'weekend_days'  => $weekend_days_input,
                'discount'      => (isset($row['discount']) && '' !== $row['discount']) ? floatval($row['discount']) : 0,
                'checkin_time'  => isset($row['checkin_time'])  ? sanitize_text_field($row['checkin_time'])  : '14:00',
                'checkout_time' => isset($row['checkout_time']) ? sanitize_text_field($row['checkout_time']) : '11:00',
            );
        }

        // ---- Reject overlaps against already-accepted periods ----
        $accepted = array();
        $rejected = array();

        foreach ($candidates as $candidate) {
            $conflict = null;

            foreach ($accepted as $existing) {
                if (moga_dates_overlap($candidate['start'], $candidate['end'], $existing['start'], $existing['end'])) {
                    $conflict = $existing;
                    break;
                }
            }

            if ($conflict) {
                $rejected[] = array(
                    'period'   => $candidate,
                    'conflict' => $conflict,
                );
                continue;
            }

            $accepted[] = $candidate;
        }

        // ---- Apply accepted periods ----
        foreach ($accepted as $period) {
            $core->availability->apply_period(
                $post_id,
                'property',
                $period['start'],
                $period['end'],
                $period['price'],
                $period['weekend_price'],
                $period['min_stay'],
                $period['max_stay'],
                $period['weekend_days']
            );
        }

        update_post_meta($post_id, '_moga_pricing_periods', wp_json_encode($accepted));

        // ---- Surface incomplete rows + overlap rejections as one combined admin notice ----
        $messages = $incomplete;

        foreach ($rejected as $r) {
            $messages[] = sprintf(
                /* translators: 1: new period's dates, 2: conflicting existing period's dates */
                __('%1$s overlaps with an existing period (%2$s) and was NOT saved. Adjust the dates so periods don\'t overlap, then try again.', 'moga-travel-core'),
                moga_format_date($r['period']['start']) . ' – ' . moga_format_date($r['period']['end']),
                moga_format_date($r['conflict']['start']) . ' – ' . moga_format_date($r['conflict']['end'])
            );
        }

        if (! empty($messages)) {
            set_transient('moga_periods_rejected_' . $post_id . '_' . get_current_user_id(), $messages, 60);
        }
    }

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
    public static function render_property_location($post)
    {
        wp_nonce_field('moga_property_location_nonce', 'moga_property_location_nonce');

        $country     = get_post_meta($post->ID, '_moga_country',     true);
        $province    = get_post_meta($post->ID, '_moga_province',    true);
        $province_id = (int) get_post_meta($post->ID, '_moga_province_id', true);
        $city        = get_post_meta($post->ID, '_moga_city',        true);
        $city_id     = (int) get_post_meta($post->ID, '_moga_city_id',    true);
        $district    = get_post_meta($post->ID, '_moga_district',    true);
        $address     = get_post_meta($post->ID, '_moga_address',     true);
        $postal_code = get_post_meta($post->ID, '_moga_postal_code', true);
        $latitude    = get_post_meta($post->ID, '_moga_latitude',    true);
        $longitude   = get_post_meta($post->ID, '_moga_longitude',   true);

        $countries     = moga_get_countries_dropdown();
        $province_opts = self::get_provinces_for_render($country);
        $city_opts     = self::get_cities_for_province_render($province_id);

        // Build a code-indexed lookup for displaying country names.
        // moga_get_countries() returns a numeric array; we need code → name.
        $country_by_code = array();
        foreach ( moga_get_countries() as $c ) {
            $country_by_code[ $c['code'] ] = $c['name'];
        }
    ?>
        <div class="moga-metabox">

            <div class="moga-metabox__row">
                <div class="moga-metabox__field">
                    <label for="moga_country_search">
                        <?php esc_html_e('Country', 'moga-travel-core'); ?>
                        <span class="required">*</span>
                    </label>
                    <div class="moga-country-autocomplete-wrap" style="position:relative;">
                        <input type="text"
                            id="moga_country_search"
                            class="moga-country-ac-input"
                            value="<?php echo esc_attr($country ? ($country_by_code[$country] ?? $country) : ''); ?>"
                            placeholder="<?php esc_attr_e('Type country name…', 'moga-travel-core'); ?>"
                            autocomplete="off">
                        <div class="moga-country-ac-dropdown" style="display:none;"></div>
                    </div>
                    <input type="hidden" id="moga_country" name="moga_country"
                        value="<?php echo esc_attr($country); ?>"
                        class="moga-country-ac-hidden"
                        data-province-target="moga_province_id"
                        data-city-wrap="moga_city_search_wrap">
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_province_search">
                        <?php esc_html_e('State / Province / Governorate', 'moga-travel-core'); ?>
                        <span class="required">*</span>
                    </label>
                    <div class="moga-province-autocomplete-wrap" style="position:relative;"
                        data-country-source="moga_country"
                        data-name-target="moga_province"
                        data-id-target="moga_province_id"
                        data-city-wrap-country="moga_country">
                        <input type="text"
                            id="moga_province_search"
                            class="moga-province-ac-input"
                            value="<?php echo esc_attr($province); ?>"
                            placeholder="<?php esc_attr_e('Type province or state…', 'moga-travel-core'); ?>"
                            autocomplete="off">
                        <div class="moga-province-ac-dropdown" style="display:none;"></div>
                    </div>
                    <input type="hidden" id="moga_province"    name="moga_province"    value="<?php echo esc_attr($province); ?>">
                    <input type="hidden" id="moga_province_id" name="moga_province_id" value="<?php echo esc_attr($province_id); ?>">
                </div>
            </div>

            <div class="moga-metabox__row">
                <div class="moga-metabox__field">
                    <label for="moga_city_search">
                        <?php esc_html_e('City', 'moga-travel-core'); ?>
                        <span class="required">*</span>
                    </label>

                    <?php // Google Places Autocomplete text input.
                    // The organizer types a city name and selects from
                    // the dropdown. The hidden moga_city field carries
                    // the confirmed Google-recognized name on save. ?>
                    <div class="moga-city-autocomplete-wrap" style="position:relative;"
                        data-country-source="moga_country"
                        data-name-target="moga_city"
                        data-id-target="moga_city_id">
                        <input type="text"
                            id="moga_city_search"
                            class="moga-city-ac-input"
                            value="<?php echo esc_attr($city); ?>"
                            placeholder="<?php esc_attr_e('Type city name…', 'moga-travel-core'); ?>"
                            autocomplete="off">
                        <div class="moga-city-ac-dropdown" style="display:none;"></div>
                    </div>

                    <?php // Hidden fields — carry the saved values on form submit. ?>
                    <input type="hidden" id="moga_city"    name="moga_city"    value="<?php echo esc_attr($city); ?>">
                    <input type="hidden" id="moga_city_id" name="moga_city_id" value="<?php echo esc_attr($city_id); ?>">

                    <p class="moga-metabox__hint">
                        <?php esc_html_e('Type 2+ characters to search. Results are scoped to the selected country.', 'moga-travel-core'); ?>
                    </p>
                </div>

                <?php // District wrapper — dropdown when DB has districts, text fallback otherwise
                ?>
                <div class="moga-metabox__field moga-district-wrapper" id="moga-property-district-wrapper">
                    <div class="moga-district-dropdown-field" style="display:none;">
                        <label for="moga_district_select">
                            <?php esc_html_e('District / Area', 'moga-travel-core'); ?>
                        </label>
                        <select id="moga_district_select" class="moga-district-select">
                            <option value=""><?php esc_html_e('— Select District —', 'moga-travel-core'); ?></option>
                        </select>
                        <span class="moga-district-loading" style="display:none;">
                            <?php esc_html_e('Loading districts…', 'moga-travel-core'); ?>
                        </span>
                    </div>
                    <div class="moga-district-text-field">
                        <label for="moga_district" class="moga-district-text-label">
                            <?php esc_html_e('District / Area', 'moga-travel-core'); ?>
                        </label>
                        <input
                            type="text"
                            id="moga_district"
                            name="moga_district"
                            class="moga-district-text"
                            value="<?php echo esc_attr($district); ?>"
                            placeholder="<?php esc_attr_e('e.g. Downtown, Zamalek', 'moga-travel-core'); ?>">
                    </div>
                </div>
            </div>

            <div class="moga-metabox__row">
                <div class="moga-metabox__field">
                    <label for="moga_postal_code"><?php esc_html_e('Postal Code', 'moga-travel-core'); ?></label>
                    <input type="text" id="moga_postal_code" name="moga_postal_code"
                        value="<?php echo esc_attr($postal_code); ?>"
                        placeholder="<?php esc_attr_e('e.g. 12345', 'moga-travel-core'); ?>">
                </div>
            </div>

            <div class="moga-metabox__row moga-metabox__row--full">
                <div class="moga-metabox__field">
                    <label for="moga_address"><?php esc_html_e('Street Address', 'moga-travel-core'); ?></label>
                    <input type="text" id="moga_address" name="moga_address"
                        value="<?php echo esc_attr($address); ?>"
                        placeholder="<?php esc_attr_e('Full street address', 'moga-travel-core'); ?>">
                </div>
            </div>

            <div class="moga-metabox__row">
                <div class="moga-metabox__field">
                    <label for="moga_latitude"><?php esc_html_e('Latitude', 'moga-travel-core'); ?></label>
                    <input type="text" id="moga_latitude" name="moga_latitude"
                        value="<?php echo esc_attr($latitude); ?>"
                        placeholder="<?php esc_attr_e('e.g. 30.0444', 'moga-travel-core'); ?>">
                </div>
                <div class="moga-metabox__field">
                    <label for="moga_longitude"><?php esc_html_e('Longitude', 'moga-travel-core'); ?></label>
                    <input type="text" id="moga_longitude" name="moga_longitude"
                        value="<?php echo esc_attr($longitude); ?>"
                        placeholder="<?php esc_attr_e('e.g. 31.2357', 'moga-travel-core'); ?>">
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
    public static function render_property_contact($post)
    {
        wp_nonce_field('moga_property_contact_nonce', 'moga_property_contact_nonce');

        $phone    = get_post_meta($post->ID, '_moga_phone',    true);
        $whatsapp = get_post_meta($post->ID, '_moga_whatsapp', true);
        $email    = get_post_meta($post->ID, '_moga_email',    true);
    ?>
        <div class="moga-metabox">
            <div class="moga-metabox__row">

                <div class="moga-metabox__field">
                    <label for="moga_phone">
                        <?php esc_html_e('Phone Number (optional override)', 'moga-travel-core'); ?>
                    </label>
                    <input
                        type="tel"
                        id="moga_phone"
                        name="moga_phone"
                        value="<?php echo esc_attr($phone); ?>"
                        class="moga-phone-field"
                        placeholder="<?php esc_attr_e('Leave blank to use your account number', 'moga-travel-core'); ?>">
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_whatsapp">
                        <?php esc_html_e('WhatsApp Number (optional override)', 'moga-travel-core'); ?>
                    </label>
                    <input
                        type="tel"
                        id="moga_whatsapp"
                        name="moga_whatsapp"
                        value="<?php echo esc_attr($whatsapp); ?>"
                        class="moga-phone-field"
                        placeholder="<?php esc_attr_e('Leave blank to use your account number', 'moga-travel-core'); ?>">
                </div>

            </div>

            <div class="moga-metabox__row">
                <div class="moga-metabox__field">
                    <label for="moga_email">
                        <?php esc_html_e('Email Address', 'moga-travel-core'); ?> <span class="required">*</span>
                    </label>
                    <input
                        type="email"
                        id="moga_email"
                        name="moga_email"
                        value="<?php echo esc_attr($email); ?>"
                        placeholder="<?php esc_attr_e('contact@example.com', 'moga-travel-core'); ?>"
                        required>
                    <p class="moga-metabox__hint">
                        <?php esc_html_e('Required — clients use this to contact you about this listing. The listing cannot be published without it.', 'moga-travel-core'); ?>
                    </p>
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
    public static function render_property_details($post)
    {
        wp_nonce_field('moga_property_details_nonce', 'moga_property_details_nonce');

        $max_guests      = get_post_meta($post->ID, '_moga_max_guests',      true) ?: 1;
        $bedrooms        = get_post_meta($post->ID, '_moga_bedrooms',        true) ?: 1;
        $bathrooms       = get_post_meta($post->ID, '_moga_bathrooms',       true) ?: 1;
        $area            = get_post_meta($post->ID, '_moga_area',            true);
        $floor           = get_post_meta($post->ID, '_moga_floor',           true);
        $building_floors = get_post_meta($post->ID, '_moga_building_floors', true);
        $year_built      = get_post_meta($post->ID, '_moga_year_built',      true);
        $cancellation    = get_post_meta($post->ID, '_moga_cancellation',    true) ?: 'moderate';

        $cancellation_policies = Moga_CPT_Property::get_cancellation_policies();
    ?>
        <div class="moga-metabox">

            <div class="moga-metabox__row">
                <div class="moga-metabox__field">
                    <label for="moga_max_guests">
                        <?php esc_html_e('Max Guests', 'moga-travel-core'); ?>
                    </label>
                    <input type="number" id="moga_max_guests" name="moga_max_guests"
                        value="<?php echo esc_attr($max_guests); ?>" min="1" step="1">
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_bedrooms">
                        <?php esc_html_e('Bedrooms', 'moga-travel-core'); ?>
                    </label>
                    <input type="number" id="moga_bedrooms" name="moga_bedrooms"
                        value="<?php echo esc_attr($bedrooms); ?>" min="0" step="1">
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_bathrooms">
                        <?php esc_html_e('Bathrooms', 'moga-travel-core'); ?>
                    </label>
                    <input type="number" id="moga_bathrooms" name="moga_bathrooms"
                        value="<?php echo esc_attr($bathrooms); ?>" min="0" step="0.5">
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_area">
                        <?php esc_html_e('Area (m²)', 'moga-travel-core'); ?>
                    </label>
                    <input type="number" id="moga_area" name="moga_area"
                        value="<?php echo esc_attr($area); ?>" min="0" step="1" placeholder="0">
                </div>
            </div>

            <div class="moga-metabox__row">
                <div class="moga-metabox__field">
                    <label for="moga_floor">
                        <?php esc_html_e('Floor Number', 'moga-travel-core'); ?>
                    </label>
                    <input type="number" id="moga_floor" name="moga_floor"
                        value="<?php echo esc_attr($floor); ?>" min="0" step="1" placeholder="0">
                    <p class="moga-metabox__hint"><?php esc_html_e('0 = Ground floor', 'moga-travel-core'); ?></p>
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_building_floors">
                        <?php esc_html_e('Total Building Floors', 'moga-travel-core'); ?>
                    </label>
                    <input type="number" id="moga_building_floors" name="moga_building_floors"
                        value="<?php echo esc_attr($building_floors); ?>" min="1" step="1" placeholder="1">
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_year_built">
                        <?php esc_html_e('Year Built', 'moga-travel-core'); ?>
                    </label>
                    <input type="number" id="moga_year_built" name="moga_year_built"
                        value="<?php echo esc_attr($year_built); ?>"
                        min="1900" max="<?php echo esc_attr(gmdate('Y')); ?>"
                        step="1" placeholder="<?php echo esc_attr(gmdate('Y')); ?>">
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_cancellation">
                        <?php esc_html_e('Cancellation Policy', 'moga-travel-core'); ?>
                    </label>
                    <select id="moga_cancellation" name="moga_cancellation">
                        <?php foreach ($cancellation_policies as $key => $policy) : ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($cancellation, $key); ?>>
                                <?php echo esc_html($policy['label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($cancellation_policies[$cancellation])) : ?>
                        <p class="moga-metabox__hint">
                            <?php echo esc_html($cancellation_policies[$cancellation]['desc']); ?>
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
    public static function render_property_amenities($post)
    {
        wp_nonce_field('moga_property_amenities_nonce', 'moga_property_amenities_nonce');

        $saved_amenities = moga_get_property_amenities($post->ID);
        $all_amenities   = Moga_CPT_Property::get_amenities();
        $groups          = Moga_CPT_Property::get_amenity_groups();
    ?>
        <div class="moga-metabox moga-metabox--amenities">
            <?php foreach ($groups as $group_key => $group_label) : ?>
                <?php
                $group_amenities = array_filter(
                    $all_amenities,
                    function ($amenity) use ($group_key) {
                        return $amenity['group'] === $group_key;
                    }
                );
                if (empty($group_amenities)) continue;
                ?>
                <div class="moga-amenity-group">
                    <h4 class="moga-amenity-group__title"><?php echo esc_html($group_label); ?></h4>
                    <div class="moga-amenity-group__items">
                        <?php foreach ($group_amenities as $key => $amenity) : ?>
                            <label class="moga-amenity-item">
                                <input
                                    type="checkbox"
                                    name="moga_amenities[]"
                                    value="<?php echo esc_attr($key); ?>"
                                    <?php checked(in_array($key, $saved_amenities, true)); ?>>
                                <span class="moga-amenity-item__label">
                                    <?php echo esc_html($amenity['label']); ?>
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
     * Render property status meta box.
     *
     * @since  1.0.0
     * @param  WP_Post $post Current post object.
     * @return void
     */
    public static function render_property_status($post)
    {
        wp_nonce_field('moga_property_status_nonce', 'moga_property_status_nonce');

        $featured        = get_post_meta($post->ID, '_moga_featured',        true);
        $instant_booking = get_post_meta($post->ID, '_moga_instant_booking', true);
        $active          = get_post_meta($post->ID, '_moga_active',          true);

        if ('' === $active) {
            $active = '1';
        }
    ?>
        <div class="moga-metabox">
            <div class="moga-metabox__switches">

                <label class="moga-switch">
                    <input type="checkbox" name="moga_active" value="1" <?php checked('1', $active); ?>>
                    <span class="moga-switch__slider"></span>
                    <span class="moga-switch__label">
                        <?php esc_html_e('Active — visible to guests', 'moga-travel-core'); ?>
                    </span>
                </label>

                <label class="moga-switch">
                    <input type="checkbox" name="moga_featured" value="1" <?php checked('1', $featured); ?>>
                    <span class="moga-switch__slider"></span>
                    <span class="moga-switch__label">
                        <?php esc_html_e('Featured — shown on homepage', 'moga-travel-core'); ?>
                    </span>
                </label>

                <label class="moga-switch">
                    <input type="checkbox" name="moga_instant_booking" value="1" <?php checked('1', $instant_booking); ?>>
                    <span class="moga-switch__slider"></span>
                    <span class="moga-switch__label">
                        <?php esc_html_e('Instant Booking — no approval needed', 'moga-travel-core'); ?>
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
    public static function render_tour_groups($post)
    {
        wp_nonce_field('moga_tour_pricing_nonce', 'moga_tour_pricing_nonce');

        $currency        = get_post_meta($post->ID, '_moga_currency', true) ?: 'USD';
        $currencies      = moga_get_currencies();
        $cutoff_hours    = get_post_meta($post->ID, '_moga_booking_cutoff_hours', true);
        $cutoff_hours    = ('' === $cutoff_hours) ? 24 : intval($cutoff_hours);
        $duration_nights = intval(get_post_meta($post->ID, '_moga_duration_nights', true));

        $groups_json = get_post_meta($post->ID, '_moga_tour_groups', true);
        $groups      = $groups_json ? json_decode($groups_json, true) : array();
        $groups      = is_array($groups) ? $groups : array();
    ?>
        <div class="moga-metabox">

            <div class="moga-metabox__row">
                <div class="moga-metabox__field">
                    <label for="moga_tour_currency">
                        <?php esc_html_e('Currency', 'moga-travel-core'); ?>
                    </label>
                    <select id="moga_tour_currency" name="moga_currency">
                        <?php foreach ($currencies as $code => $label) : ?>
                            <option value="<?php echo esc_attr($code); ?>" <?php selected($currency, $code); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="moga-metabox__hint">
                        <?php esc_html_e('Applies to every group below.', 'moga-travel-core'); ?>
                    </p>
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_booking_cutoff_hours">
                        <?php esc_html_e('Booking Cutoff (hours before departure)', 'moga-travel-core'); ?>
                    </label>
                    <input type="number" id="moga_booking_cutoff_hours" name="moga_booking_cutoff_hours"
                        value="<?php echo esc_attr($cutoff_hours); ?>" min="0" step="1">
                    <p class="moga-metabox__hint">
                        <?php esc_html_e('Booking closes this many hours before a group\'s start date, even with seats remaining. Applies across every group below.', 'moga-travel-core'); ?>
                    </p>
                </div>
            </div>

            <p class="moga-metabox__hint" style="margin: 12px 0;">
                <?php esc_html_e('Every price and capacity lives inside a Group — one row per departure date. A group\'s end date is calculated automatically from this tour\'s own Duration (set in the Schedule box below) — it can never drift out of sync with how long the tour actually runs.', 'moga-travel-core'); ?>
            </p>

            <div id="moga-tour-groups-list">
                <?php if (empty($groups)) : ?>
                    <?php self::render_tour_group_row(0, array(), $duration_nights); ?>
                <?php else : ?>
                    <?php foreach ($groups as $index => $group) : ?>
                        <?php self::render_tour_group_row($index, $group, $duration_nights); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <button type="button" id="moga-tour-groups-add" class="button">
                <?php esc_html_e('+ Add Group', 'moga-travel-core'); ?>
            </button>

            <template id="moga-tour-group-template">
                <?php self::render_tour_group_row('__INDEX__', array(), $duration_nights); ?>
            </template>

        </div>

        <script>
            (function() {
                var list = document.getElementById('moga-tour-groups-list');
                var addBtn = document.getElementById('moga-tour-groups-add');
                var template = document.getElementById('moga-tour-group-template');

                // BUG FIX: previously read a fixed data-duration-nights
                // attribute captured once, from the DATABASE, at the
                // exact moment the page loaded. For a brand-new,
                // never-yet-saved tour, that value is always 0/empty —
                // whatever the organizer just typed into Duration
                // (Nights) lives only in the browser at that point,
                // not the database yet. Reading it live, on demand,
                // from the actual field every time means this always
                // reflects whatever's really typed, saved or not.
                function getDurationNights() {
                    var field = document.getElementById('moga_duration_nights');
                    return field ? (parseInt(field.value, 10) || 0) : 0;
                }

                function formatDate(d) {
                    var mm = String(d.getMonth() + 1).padStart(2, '0');
                    var dd = String(d.getDate()).padStart(2, '0');
                    return d.getFullYear() + '-' + mm + '-' + dd;
                }

                function updateEndDate(card) {
                    var startInput = card.querySelector('.moga-tour-group__start');
                    var endDisplay = card.querySelector('.moga-tour-group__end-display');
                    var titleEl = card.querySelector('.moga-tour-group-card__title');
                    if (!startInput || !startInput.value) {
                        if (endDisplay) endDisplay.textContent = '—';
                        return;
                    }
                    var start = new Date(startInput.value + 'T00:00:00');
                    var end = new Date(start.getTime());
                    end.setDate(end.getDate() + getDurationNights());
                    var endStr = formatDate(end);
                    if (endDisplay) endDisplay.textContent = endStr;
                    if (titleEl) {
                        titleEl.textContent = startInput.value === endStr ?
                            startInput.value :
                            startInput.value + ' \u2013 ' + endStr;
                    }
                }

                function wireCard(card) {
                    var startInput = card.querySelector('.moga-tour-group__start');
                    if (startInput) {
                        startInput.addEventListener('change', function() {
                            updateEndDate(card);
                        });
                    }
                    var toggle = card.querySelector('.moga-tour-group-card__toggle');
                    if (toggle) {
                        toggle.addEventListener('click', function() {
                            card.classList.toggle('moga-tour-group-card--collapsed');
                        });
                    }
                    var remove = card.querySelector('.moga-tour-group-card__remove');
                    if (remove) {
                        remove.addEventListener('click', function() {
                            card.remove();
                        });
                    }
                    updateEndDate(card);

                    // ---- Accommodation sub-repeater ----
                    wireAccommodation(card);
                }

                function wireAccommodation(card) {
                    var accomList = card.querySelector('.moga-tour-group-accommodation__list');
                    var accomAdd  = card.querySelector('.moga-tour-group-accommodation__add');
                    var accomTpl  = card.querySelector('.moga-tour-group-accommodation__template');
                    if (!accomList || !accomAdd || !accomTpl) return;

                    // Nonce shared by wireAccomRow (hotel search) and
                    // fetchGoogleThumbsForRow (photo fetch) — must be at
                    // wireAccommodation scope so both inner functions can access it.
                    var nonce = <?php echo wp_json_encode( wp_create_nonce( 'moga_nonce' ) ); ?>;

                    // Get the group index from any existing input name inside the card.
                    function getGroupIndex() {
                        var inp = card.querySelector('input[name^="moga_tour_groups["]');
                        if (!inp) return '__INDEX__';
                        var m = inp.name.match(/moga_tour_groups\[([^\]]+)\]/);
                        return m ? m[1] : '__INDEX__';
                    }

                    function wireAccomRow(row) {
                        var searchInput   = row.querySelector('.moga-accommodation-row__search-input');
                        var hiddenInput   = row.querySelector('.moga-accommodation-row__name-input');
                        var suggestionsEl = row.querySelector('.moga-hotel-suggestions');
                        var titleEl       = row.querySelector('.moga-accommodation-row__title');
                        var starsSelect   = row.querySelector('.moga-accommodation-row__stars-select');
                        var searchTimer   = null;

                        if (searchInput && hiddenInput && suggestionsEl) {

                            function getDestinationContext() {
                                // Read destination city and country from the Location & Route
                                // metabox hidden fields. These are set by the AJAX city
                                // dropdown and always reflect the current tour's destination.
                                var city    = document.getElementById('moga_destination_city')
                                    ? document.getElementById('moga_destination_city').value : '';
                                var country = document.getElementById('moga_destination_country')
                                    ? document.getElementById('moga_destination_country').value : '';
                                return { city: city, country: country };
                            }

                            function showSuggestions(results) {
                                suggestionsEl.innerHTML = '';
                                if (!results || results.length === 0) {
                                    suggestionsEl.style.display = 'none';
                                    return;
                                }
                                results.forEach(function(place) {
                                    var item = document.createElement('div');
                                    item.className = 'moga-hotel-suggestion-item';
                                    item.innerHTML =
                                        '<strong>' + escHtmlAdmin(place.name) + '</strong>' +
                                        '<span>'   + escHtmlAdmin(place.address || '') + '</span>' +
                                        (place.rating ? '<em>⭐ ' + parseFloat(place.rating).toFixed(1) + '</em>' : '');

                                    item.addEventListener('mousedown', function(e) {
                                        e.preventDefault();
                                        searchInput.value = place.name;
                                        hiddenInput.value = place.name;
                                        if (titleEl) titleEl.textContent = place.name;
                                        suggestionsEl.style.display = 'none';

                                        // Store Google Place ID and name.
                                        var placeIdInput = row.querySelector('.moga-accommodation-row__place-id');
                                        var googleNameInput = row.querySelector('.moga-accommodation-row__google-name');
                                        var googleRatingInput = row.querySelector('.moga-accommodation-row__google-rating');
                                        if (placeIdInput) placeIdInput.value = place.place_id || '';
                                        if (googleNameInput) googleNameInput.value = place.name;
                                        if (googleRatingInput) googleRatingInput.value = place.rating || '';

                                        // Mark row as Google-confirmed.
                                        row.setAttribute('data-google-selected', '1');

                                        // Show Google badge in header.
                                        var existingBadge = row.querySelector('.moga-accommodation-row__google-badge');
                                        if (!existingBadge) {
                                            var badge = document.createElement('span');
                                            badge.className = 'moga-accommodation-row__google-badge';
                                            badge.textContent = '✓ Google';
                                            titleEl.parentNode.insertBefore(badge, titleEl.nextSibling);
                                        }

                                        // Auto-set stars from Google rating.
                                        if (starsSelect && place.rating) {
                                            starsSelect.value = Math.max(1, Math.min(5, Math.round(place.rating)));
                                        }

                                        // Hide Stars select, show Google rating display.
                                        if (starsSelect) starsSelect.style.display = 'none';
                                        var ratingDisplay = row.querySelector('.moga-accommodation-row__google-rating-display');
                                        if (ratingDisplay) {
                                            ratingDisplay.style.display = '';
                                            if (place.rating) {
                                                ratingDisplay.innerHTML =
                                                    '<span style="color:#f59e0b;">★</span>' +
                                                    '<strong>' + parseFloat(place.rating).toFixed(1) + '</strong>' +
                                                    '<small style="color:#8c8f94;margin-left:4px;">' +
                                                    <?php echo wp_json_encode(__('Google rating', 'moga-travel-core')); ?> +
                                                    '</small>';
                                            }
                                        }

                                        // Hide upload section, show Google preview strip.
                                        // URL section stays visible — organizer can add extra photos.
                                        var photoSection = row.querySelector('.moga-accommodation-row__photo-section');
                                        if (photoSection) photoSection.style.display = 'none';
                                        var googlePreviewStrip = row.querySelector('.moga-accommodation-row__google-preview-strip');
                                        if (googlePreviewStrip) {
                                            googlePreviewStrip.style.display = '';
                                            fetchGoogleThumbsForRow(row, place.name);
                                        }
                                        // Update URL section label to reflect Google context.
                                        var urlSection = row.querySelector('.moga-accommodation-row__url-section');
                                        if (urlSection) {
                                            var urlLabel = urlSection.querySelector('label');
                                            if (urlLabel) urlLabel.firstChild.textContent = <?php echo wp_json_encode(__('Add Extra Photos by URL', 'moga-travel-core')); ?>;
                                            var urlHint = urlSection.querySelector('.moga-metabox__hint');
                                            if (urlHint) urlHint.textContent = <?php echo wp_json_encode(__('Google Places provides up to 10 photos. Paste additional image URLs here to supplement them in the widget slideshow. No storage used.', 'moga-travel-core')); ?>;
                                        }
                                    });
                                    suggestionsEl.appendChild(item);
                                });
                                suggestionsEl.style.display = 'block';
                            }

                            searchInput.addEventListener('input', function() {
                                var query = searchInput.value.trim();
                                // Keep hidden input in sync as a manual fallback.
                                hiddenInput.value = query;

                                // If organizer clears or changes the name, reset Google state.
                                if (!query) {
                                    row.setAttribute('data-google-selected', '0');
                                    var placeIdInput = row.querySelector('.moga-accommodation-row__place-id');
                                    if (placeIdInput) placeIdInput.value = '';
                                    var badge = row.querySelector('.moga-accommodation-row__google-badge');
                                    if (badge) badge.remove();
                                    if (starsSelect) starsSelect.style.display = '';
                                    var ratingDisplay = row.querySelector('.moga-accommodation-row__google-rating-display');
                                    if (ratingDisplay) ratingDisplay.style.display = 'none';
                                    // Restore manual photo upload, hide Google preview strip.
                                    // URL section stays visible — just update its label back.
                                    var photoSection = row.querySelector('.moga-accommodation-row__photo-section');
                                    if (photoSection) photoSection.style.display = '';
                                    var googlePreviewStrip = row.querySelector('.moga-accommodation-row__google-preview-strip');
                                    if (googlePreviewStrip) googlePreviewStrip.style.display = 'none';
                                    var urlSection = row.querySelector('.moga-accommodation-row__url-section');
                                    if (urlSection) {
                                        var urlLabel = urlSection.querySelector('label');
                                        if (urlLabel) urlLabel.firstChild.textContent = <?php echo wp_json_encode(__('Or Add Photos by URL', 'moga-travel-core')); ?>;
                                        var urlHint = urlSection.querySelector('.moga-metabox__hint');
                                        if (urlHint) urlHint.textContent = <?php echo wp_json_encode(__('Paste direct image URLs from the hotel website or any public source. One URL per line. Shown in the widget alongside uploaded photos — no storage used.', 'moga-travel-core')); ?>;
                                    }
                                }

                                if (query.length < 3) {
                                    clearTimeout(searchTimer);
                                    suggestionsEl.style.display = 'none';
                                    return;
                                }

                                clearTimeout(searchTimer);
                                searchTimer = setTimeout(function() {
                                    var ctx      = getDestinationContext();
                                    var formData = new FormData();
                                    formData.append('action',  'moga_search_hotel');
                                    formData.append('nonce',   nonce);
                                    formData.append('query',   query);
                                    formData.append('city',    ctx.city);
                                    formData.append('country', ctx.country);

                                    fetch(ajaxurl, { method: 'POST', body: formData })
                                        .then(function(r) { return r.json(); })
                                        .then(function(json) {
                                            if (json.success && json.data && json.data.results) {
                                                showSuggestions(json.data.results);
                                            }
                                        })
                                        .catch(function() {});
                                }, 380);
                            });

                            searchInput.addEventListener('blur', function() {
                                setTimeout(function() {
                                    suggestionsEl.style.display = 'none';
                                }, 220);
                            });

                            searchInput.addEventListener('focus', function() {
                                if (suggestionsEl.children.length > 0) {
                                    suggestionsEl.style.display = 'block';
                                }
                            });

                            searchInput.addEventListener('keydown', function(e) {
                                if (e.key === 'Escape') {
                                    suggestionsEl.style.display = 'none';
                                    return;
                                }
                                var items = Array.from(
                                    suggestionsEl.querySelectorAll('.moga-hotel-suggestion-item')
                                );
                                if (!items.length) return;

                                if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                                    e.preventDefault();
                                    var activeEl  = suggestionsEl.querySelector('.moga-hotel-suggestion-item--active');
                                    var activeIdx = items.indexOf(activeEl);
                                    if (activeEl) activeEl.classList.remove('moga-hotel-suggestion-item--active');
                                    if (e.key === 'ArrowDown') activeIdx = Math.min(activeIdx + 1, items.length - 1);
                                    else                        activeIdx = Math.max(activeIdx - 1, 0);
                                    items[activeIdx].classList.add('moga-hotel-suggestion-item--active');
                                    items[activeIdx].scrollIntoView({ block: 'nearest' });
                                }

                                if (e.key === 'Enter') {
                                    var activeItem = suggestionsEl.querySelector('.moga-hotel-suggestion-item--active');
                                    if (activeItem) {
                                        e.preventDefault();
                                        activeItem.dispatchEvent(new MouseEvent('mousedown'));
                                    }
                                }
                            });
                        }

                        // Remove button.
                        var removeBtn = row.querySelector('.moga-accommodation-row__remove');
                        if (removeBtn) {
                            removeBtn.addEventListener('click', function() { row.remove(); });
                        }

                        // Hotel photo uploader — WordPress media library.
                        var photoAddBtn  = row.querySelector('.moga-accommodation-row__photo-add');
                        var photoIdsInput = row.querySelector('.moga-accommodation-row__photo-ids');
                        var photoPreview  = row.querySelector('.moga-accommodation-row__photo-preview');

                        if (photoAddBtn && photoIdsInput && photoPreview) {
                            photoAddBtn.addEventListener('click', function() {
                                var frame = wp.media({
                                    title:    <?php echo wp_json_encode(__('Select Hotel Photos', 'moga-travel-core')); ?>,
                                    button:   { text: <?php echo wp_json_encode(__('Add Photos', 'moga-travel-core')); ?> },
                                    multiple: true,
                                });

                                frame.on('select', function() {
                                    var selection = frame.state().get('selection');
                                    var ids = photoIdsInput.value
                                        ? photoIdsInput.value.split(',').map(Number).filter(Boolean)
                                        : [];

                                    selection.each(function(attachment) {
                                        var id  = attachment.get('id');
                                        var url = attachment.get('url');
                                        if (ids.indexOf(id) !== -1) return; // skip duplicates

                                        ids.push(id);

                                        var thumb = document.createElement('div');
                                        thumb.className = 'moga-accommodation-row__photo-thumb';
                                        thumb.setAttribute('data-id', id);
                                        thumb.innerHTML =
                                            '<img src="' + url + '" alt="" width="60" height="60">' +
                                            '<button type="button" class="moga-accommodation-row__photo-remove" aria-label="Remove">✕</button>';

                                        thumb.querySelector('.moga-accommodation-row__photo-remove')
                                            .addEventListener('click', function() {
                                                var removeId = parseInt(thumb.getAttribute('data-id'), 10);
                                                ids = ids.filter(function(i) { return i !== removeId; });
                                                photoIdsInput.value = ids.join(',');
                                                thumb.remove();
                                            });

                                        photoPreview.appendChild(thumb);
                                    });

                                    photoIdsInput.value = ids.join(',');
                                });

                                frame.open();
                            });

                            // Wire remove buttons on existing thumbs (on page load).
                            photoPreview.querySelectorAll('.moga-accommodation-row__photo-thumb').forEach(function(thumb) {
                                var removeBtn = thumb.querySelector('.moga-accommodation-row__photo-remove');
                                if (removeBtn) {
                                    removeBtn.addEventListener('click', function() {
                                        var removeId = parseInt(thumb.getAttribute('data-id'), 10);
                                        var ids = photoIdsInput.value.split(',').map(Number).filter(Boolean);
                                        ids = ids.filter(function(i) { return i !== removeId; });
                                        photoIdsInput.value = ids.join(',');
                                        thumb.remove();
                                    });
                                }
                            });
                        }

                        // If this row already has a Google hotel (on page load),
                        // fetch and display its thumbnail photos in the admin strip.
                        if (row.getAttribute('data-google-selected') === '1') {
                            var placeIdEl = row.querySelector('.moga-accommodation-row__place-id');
                            var hotelNameEl = row.querySelector('.moga-accommodation-row__name-input');
                            if (placeIdEl && placeIdEl.value && hotelNameEl) {
                                fetchGoogleThumbsForRow(row, hotelNameEl.value);
                            }
                        }
                    }

                    // Fetch Google Places photos and display as thumbnails in the admin row.
                    function fetchGoogleThumbsForRow(row, hotelName) {
                        var thumbsWrap = row.querySelector('.moga-accommodation-row__google-thumbs');
                        if (!thumbsWrap) return;

                        var formData = new FormData();
                        formData.append('action',     'moga_get_hotel_places');
                        formData.append('nonce',      nonce);
                        formData.append('hotel_name', hotelName);

                        fetch(ajaxurl, { method: 'POST', body: formData })
                            .then(function(r) { return r.json(); })
                            .then(function(json) {
                                // Always clear the spinner first.
                                thumbsWrap.innerHTML = '';

                                if (!json.success || !json.data || !json.data.found) {
                                    thumbsWrap.innerHTML =
                                        '<span class="moga-google-thumbs__empty">' +
                                        <?php echo wp_json_encode(__('No photos found on Google.', 'moga-travel-core')); ?> +
                                        '</span>';
                                    return;
                                }

                                var photos = json.data.photos || [];

                                if (!photos.length) {
                                    thumbsWrap.innerHTML =
                                        '<span class="moga-google-thumbs__empty">' +
                                        <?php echo wp_json_encode(__('No photos available.', 'moga-travel-core')); ?> +
                                        '</span>';
                                    return;
                                }

                                // Show first 5 photos as styled thumbnails.
                                photos.slice(0, 5).forEach(function(url, idx) {
                                    var wrap = document.createElement('div');
                                    wrap.className = 'moga-google-thumbs__item';

                                    var img = document.createElement('img');
                                    img.src     = url;
                                    img.alt     = hotelName;
                                    img.loading = 'lazy';

                                    // Badge on first thumb showing total count.
                                    if (idx === 0 && photos.length > 5) {
                                        var badge = document.createElement('span');
                                        badge.className   = 'moga-google-thumbs__count';
                                        badge.textContent = '+' + (photos.length - 5);
                                        wrap.appendChild(badge);
                                    }

                                    wrap.appendChild(img);
                                    thumbsWrap.appendChild(wrap);
                                });
                            })
                            .catch(function() {
                                thumbsWrap.innerHTML =
                                    '<span class="moga-google-thumbs__empty">' +
                                    <?php echo wp_json_encode(__('Could not load photos.', 'moga-travel-core')); ?> +
                                    '</span>';
                            });
                    }

                    function escHtmlAdmin(str) {
                        return String(str)
                            .replace(/&/g, '&amp;')
                            .replace(/</g, '&lt;')
                            .replace(/>/g, '&gt;')
                            .replace(/"/g, '&quot;');
                    }

                    // Wire existing rows.
                    accomList.querySelectorAll('.moga-accommodation-row').forEach(wireAccomRow);

                    accomAdd.addEventListener('click', function() {
                        var groupIdx = getGroupIndex();
                        var accomIdx = accomList.querySelectorAll('.moga-accommodation-row').length;

                        // Replace both placeholders: group index and accommodation index.
                        var html = accomTpl.innerHTML
                            .replace(/\[__INDEX__\]/g, '[' + groupIdx + ']')
                            .replace(/\[__AIDX__\]/g, '[' + accomIdx + ']');

                        var wrapper = document.createElement('div');
                        wrapper.innerHTML = html.trim();
                        var newRow = wrapper.firstElementChild;
                        accomList.appendChild(newRow);
                        wireAccomRow(newRow);
                    });
                }

                list.querySelectorAll('.moga-tour-group-card').forEach(wireCard);

                // Changing Duration (Nights) after groups already exist
                // recalculates every existing group's end date too, not
                // just newly-typed start dates.
                var durationField = document.getElementById('moga_duration_nights');
                if (durationField) {
                    durationField.addEventListener('input', function() {
                        list.querySelectorAll('.moga-tour-group-card').forEach(updateEndDate);
                    });
                }

                addBtn.addEventListener('click', function() {
                    var index = list.querySelectorAll('.moga-tour-group-card').length;
                    var html = template.innerHTML.replace(/__INDEX__/g, index);
                    var wrapper = document.createElement('div');
                    wrapper.innerHTML = html.trim();
                    var newCard = wrapper.firstElementChild;
                    list.appendChild(newCard);
                    wireCard(newCard);
                    addBtn.textContent = <?php echo wp_json_encode(__('+ Add Another Group', 'moga-travel-core')); ?>;
                });

                if (list.querySelectorAll('.moga-tour-group-card').length > 1) {
                    addBtn.textContent = <?php echo wp_json_encode(__('+ Add Another Group', 'moga-travel-core')); ?>;
                }
            })();
        </script>
    <?php
    }

    /**
     * Render a single Tour Group card — start date, auto-computed end
     * date (display-only), price adult/child/infant, capacity, and
     * min participants to run.
     *
     * @since  1.0.0
     * @param  int|string $index           Repeater index (int for real rows, '__INDEX__' for the JS template).
     * @param  array      $group           Existing group data, or empty for a new row.
     * @param  int        $duration_nights Tour's own duration, for the initial end-date display.
     * @return void
     */
    private static function render_tour_group_row($index, $group, $duration_nights)
    {
        $start            = $group['start']            ?? '';
        $price_adult      = $group['price_adult']      ?? '';
        $price_child      = $group['price_child']      ?? '';
        $price_infant     = $group['price_infant']     ?? '';
        $capacity         = $group['capacity']         ?? '';
        $min_participants = $group['min_participants'] ?? '';
        $accommodation    = isset($group['accommodation']) && is_array($group['accommodation'])
            ? $group['accommodation']
            : array();

        $end_preview = '—';
        if ($start) {
            $end_date    = date_create($start);
            if ($end_date) {
                $end_date->modify('+' . intval($duration_nights) . ' days');
                $end_preview = $end_date->format('Y-m-d');
            }
        }

        $title = $start ? ($start === $end_preview ? $start : $start . ' – ' . $end_preview) : __('New Group', 'moga-travel-core');
    ?>
        <div class="moga-tour-group-card">
            <div class="moga-tour-group-card__header">
                <span class="moga-tour-group-card__title"><?php echo esc_html($title); ?></span>
                <button type="button" class="moga-tour-group-card__toggle" aria-label="<?php esc_attr_e('Collapse', 'moga-travel-core'); ?>">▾</button>
                <button type="button" class="moga-tour-group-card__remove" aria-label="<?php esc_attr_e('Remove group', 'moga-travel-core'); ?>">✕</button>
            </div>
            <div class="moga-tour-group-card__body">
                <div class="moga-metabox__row">
                    <div class="moga-metabox__field">
                        <label><?php esc_html_e('Start Date', 'moga-travel-core'); ?></label>
                        <input type="date" class="moga-tour-group__start"
                            name="moga_tour_groups[<?php echo esc_attr($index); ?>][start]"
                            value="<?php echo esc_attr($start); ?>">
                    </div>
                    <div class="moga-metabox__field">
                        <label><?php esc_html_e('End Date (auto)', 'moga-travel-core'); ?></label>
                        <input type="text" class="moga-tour-group__end-display" value="<?php echo esc_attr($end_preview); ?>" readonly disabled>
                        <p class="moga-metabox__hint">
                            <?php esc_html_e('Calculated from this tour\'s Duration — not editable here.', 'moga-travel-core'); ?>
                        </p>
                    </div>
                </div>
                <div class="moga-metabox__row">
                    <div class="moga-metabox__field">
                        <label><?php esc_html_e('Price / Adult', 'moga-travel-core'); ?></label>
                        <input type="number" name="moga_tour_groups[<?php echo esc_attr($index); ?>][price_adult]"
                            value="<?php echo esc_attr($price_adult); ?>" min="0" step="0.01" placeholder="0.00">
                    </div>
                    <div class="moga-metabox__field">
                        <label><?php esc_html_e('Price / Child', 'moga-travel-core'); ?></label>
                        <input type="number" name="moga_tour_groups[<?php echo esc_attr($index); ?>][price_child]"
                            value="<?php echo esc_attr($price_child); ?>" min="0" step="0.01" placeholder="0.00">
                    </div>
                    <div class="moga-metabox__field">
                        <label><?php esc_html_e('Price / Infant', 'moga-travel-core'); ?></label>
                        <input type="number" name="moga_tour_groups[<?php echo esc_attr($index); ?>][price_infant]"
                            value="<?php echo esc_attr($price_infant); ?>" min="0" step="0.01" placeholder="0.00">
                    </div>
                </div>
                <div class="moga-metabox__row">
                    <div class="moga-metabox__field">
                        <label><?php esc_html_e('Capacity (seats)', 'moga-travel-core'); ?></label>
                        <input type="number" name="moga_tour_groups[<?php echo esc_attr($index); ?>][capacity]"
                            value="<?php echo esc_attr($capacity); ?>" min="1" step="1" placeholder="25">
                    </div>
                    <div class="moga-metabox__field">
                        <label><?php esc_html_e('Min Participants to Run', 'moga-travel-core'); ?></label>
                        <input type="number" name="moga_tour_groups[<?php echo esc_attr($index); ?>][min_participants]"
                            value="<?php echo esc_attr($min_participants); ?>" min="1" step="1" placeholder="1">
                    </div>
                </div>

                <?php // ---- Accommodation sub-repeater ---- ?>
                <div class="moga-tour-group-accommodation" style="margin-top:16px;border-top:1px solid #e2e4e7;padding-top:14px;">
                    <div class="moga-tour-group-accommodation__header">
                        <span class="moga-metabox__section-title" style="border-bottom:none;margin-bottom:6px;">
                            🏨 <?php esc_html_e('Accommodation', 'moga-travel-core'); ?>
                            <span style="font-weight:400;text-transform:none;letter-spacing:0;color:#8c8f94;font-size:11px;">
                                — <?php esc_html_e('optional', 'moga-travel-core'); ?>
                            </span>
                        </span>
                        <p class="moga-metabox__hint" style="margin-bottom:10px;">
                            <?php esc_html_e('Add hotels night by night. Guests see this on the tour page.', 'moga-travel-core'); ?>
                        </p>
                    </div>

                    <div class="moga-tour-group-accommodation__list">
                        <?php if (! empty($accommodation)) : ?>
                            <?php foreach ($accommodation as $ai => $stay) : ?>
                                <?php self::render_accommodation_row($index, $ai, $stay); ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <button type="button" class="button moga-tour-group-accommodation__add" style="margin-top:6px;">
                        + <?php esc_html_e('Add Hotel', 'moga-travel-core'); ?>
                    </button>

                    <template class="moga-tour-group-accommodation__template">
                        <?php self::render_accommodation_row($index, '__AIDX__', array()); ?>
                    </template>
                </div>

            </div>
        </div>
    <?php
    }

    /**
     * Render a single accommodation row inside a tour group card.
     *
     * @since  1.0.0
     * @param  int|string $group_index Group repeater index.
     * @param  int|string $accom_index Accommodation repeater index.
     * @param  array      $stay        Existing stay data or empty array.
     * @return void
     */
    private static function render_accommodation_row($group_index, $accom_index, $stay)
    {
        $hotel_name  = $stay['hotel_name']  ?? '';
        $stars       = $stay['stars']       ?? '4';
        $night_from  = $stay['night_from']  ?? '1';
        $night_to    = $stay['night_to']    ?? '1';
        $board       = $stay['board']       ?? 'breakfast';
        $notes       = $stay['notes']       ?? '';
        $photo_ids   = isset($stay['photo_ids']) && is_array($stay['photo_ids'])
            ? $stay['photo_ids'] : array();
        // URL-based photos — stored as array, displayed as one-per-line textarea.
        $photo_urls_arr = isset($stay['photo_urls']) && is_array($stay['photo_urls'])
            ? $stay['photo_urls'] : array();
        $photo_urls_text = implode("\n", $photo_urls_arr);
        $place_id    = $stay['place_id']    ?? '';
        $google_name = $stay['google_name'] ?? $hotel_name;
        $google_rating = $stay['google_rating'] ?? '';

        // Is this hotel already confirmed via Google Places?
        $is_google = ! empty( $place_id );

        $board_options = array(
            'room_only'     => __('Room Only',          'moga-travel-core'),
            'breakfast'     => __('Breakfast Included', 'moga-travel-core'),
            'half_board'    => __('Half Board',         'moga-travel-core'),
            'full_board'    => __('Full Board',         'moga-travel-core'),
            'all_inclusive' => __('All Inclusive',      'moga-travel-core'),
        );

        $name_prefix = "moga_tour_groups[{$group_index}][accommodation][{$accom_index}]";

        // Unique ID suffix for JS targeting within this row.
        $row_id = 'accom-' . $group_index . '-' . $accom_index;
    ?>
        <div class="moga-accommodation-row" id="<?php echo esc_attr($row_id); ?>"
            data-google-selected="<?php echo $is_google ? '1' : '0'; ?>">
            <div class="moga-accommodation-row__header">
                <strong class="moga-accommodation-row__title">
                    <?php echo $hotel_name
                        ? esc_html($hotel_name)
                        : esc_html__('New Hotel', 'moga-travel-core'); ?>
                </strong>
                <?php if ($is_google) : ?>
                    <span class="moga-accommodation-row__google-badge">✓ Google</span>
                <?php endif; ?>
                <button type="button"
                    class="moga-accommodation-row__remove"
                    aria-label="<?php esc_attr_e('Remove hotel', 'moga-travel-core'); ?>">✕</button>
            </div>

            <input type="hidden" name="<?php echo esc_attr($name_prefix); ?>[place_id]"
                class="moga-accommodation-row__place-id" value="<?php echo esc_attr($place_id); ?>">
            <input type="hidden" name="<?php echo esc_attr($name_prefix); ?>[google_name]"
                class="moga-accommodation-row__google-name" value="<?php echo esc_attr($google_name); ?>">
            <input type="hidden" name="<?php echo esc_attr($name_prefix); ?>[google_rating]"
                class="moga-accommodation-row__google-rating" value="<?php echo esc_attr($google_rating); ?>">

            <div class="moga-accommodation-row__body">

                <?php // ---- Hotel Name autocomplete ---- ?>
                <div class="moga-metabox__row">
                    <div class="moga-metabox__field" style="flex:2;position:relative;">
                        <label>
                            <?php esc_html_e('Hotel Name', 'moga-travel-core'); ?>
                            <span class="required">*</span>
                        </label>

                        <?php // Visible search input — organizer types here. ?>
                        <input type="text"
                            class="moga-accommodation-row__search-input"
                            value="<?php echo esc_attr($hotel_name); ?>"
                            placeholder="<?php esc_attr_e('Type 3+ characters to search hotels…', 'moga-travel-core'); ?>"
                            autocomplete="off">

                        <?php // Hidden input — carries the confirmed hotel name for save. ?>
                        <input type="hidden"
                            name="<?php echo esc_attr($name_prefix); ?>[hotel_name]"
                            class="moga-accommodation-row__name-input"
                            value="<?php echo esc_attr($hotel_name); ?>">

                        <?php // Suggestions dropdown — populated by JS. ?>
                        <div class="moga-hotel-suggestions" style="display:none;"></div>

                        <p class="moga-metabox__hint">
                            <?php esc_html_e(
                                'Results are filtered to the tour\'s Destination City and Country. '
                                . 'If the hotel is not found, enter the name manually and upload photos below.',
                                'moga-travel-core'
                            ); ?>
                        </p>
                    </div>

                    <div class="moga-metabox__field">
                        <label><?php esc_html_e('Stars', 'moga-travel-core'); ?></label>
                        <?php // Stars select — hidden when Google match confirmed. ?>
                        <select name="<?php echo esc_attr($name_prefix); ?>[stars]"
                            class="moga-accommodation-row__stars-select"
                            <?php echo $is_google ? 'style="display:none;"' : ''; ?>>
                            <?php for ($s = 1; $s <= 5; $s++) : ?>
                                <option value="<?php echo esc_attr($s); ?>"
                                    <?php selected((int)$stars, $s); ?>>
                                    <?php echo esc_html(str_repeat('★', $s)); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                        <?php // Google rating display — shown when Google match confirmed. ?>
                        <div class="moga-accommodation-row__google-rating-display"
                            <?php echo ! $is_google ? 'style="display:none;"' : ''; ?>>
                            <?php if ($is_google && $google_rating) : ?>
                                <span style="color:#f59e0b;">★</span>
                                <strong><?php echo esc_html(number_format((float)$google_rating, 1)); ?></strong>
                                <small style="color:#8c8f94;"><?php esc_html_e('Google rating', 'moga-travel-core'); ?></small>
                            <?php elseif ($is_google) : ?>
                                <small style="color:#8c8f94;"><?php esc_html_e('Rating fetched when page loads', 'moga-travel-core'); ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php // ---- Night range + board ---- ?>
                <div class="moga-metabox__row">
                    <div class="moga-metabox__field">
                        <label><?php esc_html_e('From Night', 'moga-travel-core'); ?></label>
                        <input type="number"
                            name="<?php echo esc_attr($name_prefix); ?>[night_from]"
                            value="<?php echo esc_attr($night_from); ?>"
                            min="1" step="1" placeholder="1">
                    </div>
                    <div class="moga-metabox__field">
                        <label><?php esc_html_e('To Night', 'moga-travel-core'); ?></label>
                        <input type="number"
                            name="<?php echo esc_attr($name_prefix); ?>[night_to]"
                            value="<?php echo esc_attr($night_to); ?>"
                            min="1" step="1" placeholder="1">
                    </div>
                    <div class="moga-metabox__field">
                        <label><?php esc_html_e('Board', 'moga-travel-core'); ?></label>
                        <select name="<?php echo esc_attr($name_prefix); ?>[board]">
                            <?php foreach ($board_options as $key => $label) : ?>
                                <option value="<?php echo esc_attr($key); ?>"
                                    <?php selected($board, $key); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <?php // ---- Notes ---- ?>
                <div class="moga-metabox__row moga-metabox__row--full">
                    <div class="moga-metabox__field">
                        <label>
                            <?php esc_html_e('Notes', 'moga-travel-core'); ?>
                            <span class="moga-metabox__label-hint">
                                — <?php esc_html_e('optional', 'moga-travel-core'); ?>
                            </span>
                        </label>
                        <input type="text"
                            name="<?php echo esc_attr($name_prefix); ?>[notes]"
                            value="<?php echo esc_attr($notes); ?>"
                            placeholder="<?php esc_attr_e('e.g. Pool view rooms, floor 3+', 'moga-travel-core'); ?>">
                    </div>
                </div>

                <?php // ---- Hotel Photos section ---- ?>
                <?php // When a Google hotel is selected: hidden — Google photos show in widget automatically.
                // When hotel is unknown: visible — organizer uploads photos manually. ?>

                <?php // Google photo preview strip — shown in admin when Google hotel selected. ?>
                <div class="moga-metabox__row moga-metabox__row--full moga-accommodation-row__google-preview-strip"
                    <?php echo ! $is_google ? 'style="display:none;"' : ''; ?>>
                    <div class="moga-metabox__field">
                        <label><?php esc_html_e('Hotel Photos', 'moga-travel-core'); ?></label>

                        <?php // Thumbnail strip — populated by JS when page loads or hotel is selected. ?>
                        <div class="moga-accommodation-row__google-thumbs">
                            <?php if ($is_google) : ?>
                                <span class="moga-accommodation-row__google-thumbs-loading">
                                    <span class="spinner is-active" style="float:none;margin:0 6px 0 0;"></span>
                                    <?php esc_html_e('Loading Google photos…', 'moga-travel-core'); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <p class="moga-metabox__hint" style="margin-top:6px;">
                            <strong style="color:#4285f4;">✓ Google</strong>
                            <?php esc_html_e('— up to 10 real photos shown automatically in the widget slideshow.', 'moga-travel-core'); ?>
                            <?php if ($is_google && $place_id) : ?>
                                &nbsp;
                                <a href="https://www.google.com/maps/place/?q=place_id:<?php echo esc_attr($place_id); ?>"
                                    target="_blank" rel="noopener noreferrer"
                                    style="color:#2271b1;">
                                    <?php esc_html_e('View on Google Maps ↗', 'moga-travel-core'); ?>
                                </a>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>


                <?php // ---- Upload section — hidden for Google hotels (they have their own photos) ---- ?>
                <div class="moga-metabox__row moga-metabox__row--full moga-accommodation-row__photo-section"
                    <?php echo $is_google ? 'style="display:none;"' : ''; ?>>
                    <div class="moga-metabox__field">
                        <label>
                            <?php esc_html_e('Hotel Photos', 'moga-travel-core'); ?>
                            <span class="moga-metabox__label-hint">
                                — <?php esc_html_e('optional, used when hotel is not on Google', 'moga-travel-core'); ?>
                            </span>
                        </label>

                        <input type="hidden"
                            name="<?php echo esc_attr($name_prefix); ?>[photo_ids]"
                            class="moga-accommodation-row__photo-ids"
                            value="<?php echo esc_attr(implode(',', $photo_ids)); ?>">

                        <div class="moga-accommodation-row__photo-preview">
                            <?php foreach ($photo_ids as $pid) :
                                $thumb = wp_get_attachment_image_src($pid, 'thumbnail');
                                if (!$thumb) continue;
                            ?>
                                <div class="moga-accommodation-row__photo-thumb"
                                    data-id="<?php echo esc_attr($pid); ?>">
                                    <img src="<?php echo esc_url($thumb[0]); ?>"
                                        alt="" width="60" height="60">
                                    <button type="button"
                                        class="moga-accommodation-row__photo-remove"
                                        aria-label="<?php esc_attr_e('Remove photo', 'moga-travel-core'); ?>">✕</button>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <button type="button"
                            class="button moga-accommodation-row__photo-add">
                            📁 <?php esc_html_e('Add Hotel Photos', 'moga-travel-core'); ?>
                        </button>
                    </div>
                </div>

                <?php // ---- URL section — always visible for both Google and unknown hotels ----
                // For Google hotels: adds extra photos beyond Google's 10-photo API limit.
                // For unknown hotels: main photo source alongside uploaded files. ?>
                <div class="moga-metabox__row moga-metabox__row--full moga-accommodation-row__url-section">
                    <div class="moga-metabox__field">
                        <label>
                            <?php echo $is_google
                                ? esc_html__('Add Extra Photos by URL', 'moga-travel-core')
                                : esc_html__('Or Add Photos by URL', 'moga-travel-core'); ?>
                            <span class="moga-metabox__label-hint">— <?php esc_html_e('one URL per line', 'moga-travel-core'); ?></span>
                        </label>
                        <textarea
                            name="<?php echo esc_attr($name_prefix); ?>[photo_urls]"
                            class="moga-accommodation-row__photo-urls"
                            rows="4"
                            placeholder="https://hotel.com/pool.jpg&#10;https://hotel.com/room-tour.mp4&#10;https://www.youtube.com/watch?v=ABC123&#10;https://vimeo.com/123456789"
                            style="width:100%;font-size:12px;font-family:monospace;resize:vertical;"><?php echo esc_textarea($photo_urls_text); ?></textarea>
                        <p class="moga-metabox__hint" style="margin-top:4px;">
                            <?php if ($is_google) : ?>
                                <?php esc_html_e('Google Places provides up to 10 photos. Add extra image or video URLs here to supplement them in the widget slideshow. Supports: images (.jpg .png .webp), direct videos (.mp4 .webm .mov), YouTube and Vimeo links. One URL per line. No storage used.', 'moga-travel-core'); ?>
                            <?php else : ?>
                                <?php esc_html_e('Paste image or video URLs — one per line. Supports images (.jpg .png .webp), direct video files (.mp4 .webm .mov), YouTube and Vimeo links. These appear in the widget slideshow. No storage used.', 'moga-travel-core'); ?>
                            <?php endif; ?>
                        </p>
                    </div>
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
    public static function render_tour_schedule($post)
    {
        wp_nonce_field('moga_tour_schedule_nonce', 'moga_tour_schedule_nonce');

        $duration_days   = get_post_meta($post->ID, '_moga_duration_days',   true) ?: 1;
        $duration_nights = get_post_meta($post->ID, '_moga_duration_nights', true) ?: 0;
        $departure_time  = get_post_meta($post->ID, '_moga_departure_time',  true) ?: '08:00';
        $return_time     = get_post_meta($post->ID, '_moga_return_time',     true) ?: '18:00';

        $weekend_days = get_post_meta($post->ID, '_moga_weekend_days', true);
        $weekend_days = $weekend_days ? json_decode($weekend_days, true) : array();
        if (! is_array($weekend_days)) {
            $weekend_days = array();
        }
    ?>
        <div class="moga-metabox">

            <div class="moga-metabox__row">
                <div class="moga-metabox__field">
                    <label for="moga_duration_days">
                        <?php esc_html_e('Duration (Days)', 'moga-travel-core'); ?>
                    </label>
                    <input type="number" id="moga_duration_days" name="moga_duration_days"
                        value="<?php echo esc_attr($duration_days); ?>" min="1" step="1">
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_duration_nights">
                        <?php esc_html_e('Duration (Nights)', 'moga-travel-core'); ?>
                    </label>
                    <input type="number" id="moga_duration_nights" name="moga_duration_nights"
                        value="<?php echo esc_attr($duration_nights); ?>" min="0" step="1">
                    <p class="moga-metabox__hint">
                        <?php esc_html_e('0 = Day trip (no overnight). Every Group\'s end date below is calculated from this number — changing it after groups exist changes when they all end.', 'moga-travel-core'); ?>
                    </p>
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_departure_time">
                        <?php esc_html_e('Departure Time', 'moga-travel-core'); ?>
                    </label>
                    <input type="time" id="moga_departure_time" name="moga_departure_time"
                        value="<?php echo esc_attr($departure_time); ?>">
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_return_time">
                        <?php esc_html_e('Return Time', 'moga-travel-core'); ?>
                    </label>
                    <input type="time" id="moga_return_time" name="moga_return_time"
                        value="<?php echo esc_attr($return_time); ?>">
                </div>
            </div>

            <div class="moga-metabox__row moga-metabox__row--full">
                <div class="moga-metabox__field">
                    <label><?php esc_html_e('Weekend Days', 'moga-travel-core'); ?></label>
                    <div class="moga-weekdays">
                        <?php foreach (self::get_days_of_week() as $day_num => $day_label) : ?>
                            <label class="moga-weekday">
                                <input
                                    type="checkbox"
                                    name="moga_weekend_days[]"
                                    value="<?php echo esc_attr($day_num); ?>"
                                    <?php checked(in_array((string) $day_num, array_map('strval', $weekend_days), true)); ?>>
                                <span><?php echo esc_html(substr($day_label, 0, 3)); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <p class="moga-metabox__hint">
                        <?php esc_html_e('Reserved for future date-based tour pricing — not yet used in price calculations.', 'moga-travel-core'); ?>
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
    public static function render_tour_location($post)
    {
        wp_nonce_field('moga_tour_location_nonce', 'moga_tour_location_nonce');

        $dep_country     = get_post_meta($post->ID, '_moga_departure_country',     true);
        $dep_province    = get_post_meta($post->ID, '_moga_departure_province',    true);
        $dep_province_id = (int) get_post_meta($post->ID, '_moga_departure_province_id', true);
        $dep_city        = get_post_meta($post->ID, '_moga_departure_city',        true);
        $dep_city_id     = (int) get_post_meta($post->ID, '_moga_departure_city_id',    true);
        $dep_district    = get_post_meta($post->ID, '_moga_departure_district',    true);
        $dep_point       = get_post_meta($post->ID, '_moga_departure_point',       true);

        $dest_country     = get_post_meta($post->ID, '_moga_destination_country',     true);
        $dest_province    = get_post_meta($post->ID, '_moga_destination_province',    true);
        $dest_province_id = (int) get_post_meta($post->ID, '_moga_destination_province_id', true);
        $dest_city        = get_post_meta($post->ID, '_moga_destination_city',        true);
        $dest_city_id     = (int) get_post_meta($post->ID, '_moga_destination_city_id',    true);
        $dest_district    = get_post_meta($post->ID, '_moga_destination_district',    true);

        $countries        = moga_get_countries_dropdown();
        $dep_prov_opts    = self::get_provinces_for_render($dep_country);
        $dep_city_opts    = self::get_cities_for_province_render($dep_province_id);
        $dest_prov_opts   = self::get_provinces_for_render($dest_country);
        $dest_city_opts   = self::get_cities_for_province_render($dest_province_id);

        // Code-indexed country name lookup for display.
        $country_by_code = array();
        foreach ( moga_get_countries() as $c ) {
            $country_by_code[ $c['code'] ] = $c['name'];
        }
    ?>
        <div class="moga-metabox">

            <?php // ---- DEPARTURE SECTION ----
            ?>
            <h4 class="moga-metabox__section-title"><?php esc_html_e('Departure', 'moga-travel-core'); ?></h4>
            <div class="moga-metabox__row">
                <div class="moga-metabox__field">
                    <label for="moga_departure_country_search"><?php esc_html_e('Departure Country', 'moga-travel-core'); ?></label>
                    <div class="moga-country-autocomplete-wrap" style="position:relative;">
                        <input type="text"
                            id="moga_departure_country_search"
                            class="moga-country-ac-input"
                            value="<?php echo esc_attr($dep_country ? ($country_by_code[$dep_country] ?? $dep_country) : ''); ?>"
                            placeholder="<?php esc_attr_e('Type country name…', 'moga-travel-core'); ?>"
                            autocomplete="off">
                        <div class="moga-country-ac-dropdown" style="display:none;"></div>
                    </div>
                    <input type="hidden" id="moga_departure_country" name="moga_departure_country"
                        value="<?php echo esc_attr($dep_country); ?>"
                        class="moga-country-ac-hidden">
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_departure_province_search"><?php esc_html_e('Departure Province / State', 'moga-travel-core'); ?></label>
                    <div class="moga-province-autocomplete-wrap" style="position:relative;"
                        data-country-source="moga_departure_country"
                        data-name-target="moga_departure_province"
                        data-id-target="moga_departure_province_id">
                        <input type="text"
                            id="moga_departure_province_search"
                            class="moga-province-ac-input"
                            value="<?php echo esc_attr($dep_province); ?>"
                            placeholder="<?php esc_attr_e('Type province or state…', 'moga-travel-core'); ?>"
                            autocomplete="off">
                        <div class="moga-province-ac-dropdown" style="display:none;"></div>
                    </div>
                    <input type="hidden" id="moga_departure_province"    name="moga_departure_province"    value="<?php echo esc_attr($dep_province); ?>">
                    <input type="hidden" id="moga_departure_province_id" name="moga_departure_province_id" value="<?php echo esc_attr($dep_province_id); ?>">
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_departure_city_search"><?php esc_html_e('Departure City', 'moga-travel-core'); ?></label>

                    <div class="moga-city-autocomplete-wrap" style="position:relative;"
                        data-country-source="moga_departure_country"
                        data-name-target="moga_departure_city"
                        data-id-target="moga_departure_city_id">
                        <input type="text"
                            id="moga_departure_city_search"
                            class="moga-city-ac-input"
                            value="<?php echo esc_attr($dep_city); ?>"
                            placeholder="<?php esc_attr_e('Type city name…', 'moga-travel-core'); ?>"
                            autocomplete="off">
                        <div class="moga-city-ac-dropdown" style="display:none;"></div>
                    </div>

                    <input type="hidden" id="moga_departure_city"    name="moga_departure_city"    value="<?php echo esc_attr($dep_city); ?>">
                    <input type="hidden" id="moga_departure_city_id" name="moga_departure_city_id" value="<?php echo esc_attr($dep_city_id); ?>">
                </div>

                <div class="moga-metabox__field" id="moga-departure-district-wrapper">
                    <div class="moga-district-dropdown-field" style="display:none;">
                        <label for="moga_departure_district_select"><?php esc_html_e('Departure District', 'moga-travel-core'); ?></label>
                        <select id="moga_departure_district_select" class="moga-district-select">
                            <option value=""><?php esc_html_e('— Select District —', 'moga-travel-core'); ?></option>
                        </select>
                        <span class="moga-district-loading" style="display:none;"><?php esc_html_e('Loading districts…', 'moga-travel-core'); ?></span>
                    </div>
                    <div class="moga-district-text-field">
                        <label for="moga_departure_district" class="moga-district-text-label"><?php esc_html_e('Departure District / Area', 'moga-travel-core'); ?></label>
                        <input type="text" id="moga_departure_district" name="moga_departure_district"
                            class="moga-district-text" value="<?php echo esc_attr($dep_district); ?>"
                            placeholder="<?php esc_attr_e('e.g. City Centre', 'moga-travel-core'); ?>">
                    </div>
                </div>

                <div class="moga-metabox__field moga-metabox__field--wide">
                    <label for="moga_departure_point"><?php esc_html_e('Exact Departure Point', 'moga-travel-core'); ?></label>
                    <input type="text" id="moga_departure_point" name="moga_departure_point"
                        value="<?php echo esc_attr($dep_point); ?>"
                        placeholder="<?php esc_attr_e('e.g. Cairo International Airport, Terminal 2', 'moga-travel-core'); ?>">
                </div>
            </div>

            <?php // ---- DESTINATION SECTION ----
            ?>
            <h4 class="moga-metabox__section-title"><?php esc_html_e('Destination', 'moga-travel-core'); ?></h4>
            <div class="moga-metabox__row">
                <div class="moga-metabox__field">
                    <label for="moga_destination_country_search"><?php esc_html_e('Destination Country', 'moga-travel-core'); ?></label>
                    <div class="moga-country-autocomplete-wrap" style="position:relative;">
                        <input type="text"
                            id="moga_destination_country_search"
                            class="moga-country-ac-input"
                            value="<?php echo esc_attr($dest_country ? ($country_by_code[$dest_country] ?? $dest_country) : ''); ?>"
                            placeholder="<?php esc_attr_e('Type country name…', 'moga-travel-core'); ?>"
                            autocomplete="off">
                        <div class="moga-country-ac-dropdown" style="display:none;"></div>
                    </div>
                    <input type="hidden" id="moga_destination_country" name="moga_destination_country"
                        value="<?php echo esc_attr($dest_country); ?>"
                        class="moga-country-ac-hidden">
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_destination_province_search"><?php esc_html_e('Destination Province / State', 'moga-travel-core'); ?></label>
                    <div class="moga-province-autocomplete-wrap" style="position:relative;"
                        data-country-source="moga_destination_country"
                        data-name-target="moga_destination_province"
                        data-id-target="moga_destination_province_id">
                        <input type="text"
                            id="moga_destination_province_search"
                            class="moga-province-ac-input"
                            value="<?php echo esc_attr($dest_province); ?>"
                            placeholder="<?php esc_attr_e('Type province or state…', 'moga-travel-core'); ?>"
                            autocomplete="off">
                        <div class="moga-province-ac-dropdown" style="display:none;"></div>
                    </div>
                    <input type="hidden" id="moga_destination_province"    name="moga_destination_province"    value="<?php echo esc_attr($dest_province); ?>">
                    <input type="hidden" id="moga_destination_province_id" name="moga_destination_province_id" value="<?php echo esc_attr($dest_province_id); ?>">
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_destination_city_search"><?php esc_html_e('Destination City', 'moga-travel-core'); ?></label>

                    <div class="moga-city-autocomplete-wrap" style="position:relative;"
                        data-country-source="moga_destination_country"
                        data-name-target="moga_destination_city"
                        data-id-target="moga_destination_city_id">
                        <input type="text"
                            id="moga_destination_city_search"
                            class="moga-city-ac-input"
                            value="<?php echo esc_attr($dest_city); ?>"
                            placeholder="<?php esc_attr_e('Type city name…', 'moga-travel-core'); ?>"
                            autocomplete="off">
                        <div class="moga-city-ac-dropdown" style="display:none;"></div>
                    </div>

                    <input type="hidden" id="moga_destination_city"    name="moga_destination_city"    value="<?php echo esc_attr($dest_city); ?>">
                    <input type="hidden" id="moga_destination_city_id" name="moga_destination_city_id" value="<?php echo esc_attr($dest_city_id); ?>">
                </div>

                <div class="moga-metabox__field" id="moga-destination-district-wrapper">
                    <div class="moga-district-dropdown-field" style="display:none;">
                        <label for="moga_destination_district_select"><?php esc_html_e('Destination District', 'moga-travel-core'); ?></label>
                        <select id="moga_destination_district_select" class="moga-district-select">
                            <option value=""><?php esc_html_e('— Select District —', 'moga-travel-core'); ?></option>
                        </select>
                        <span class="moga-district-loading" style="display:none;"><?php esc_html_e('Loading districts…', 'moga-travel-core'); ?></span>
                    </div>
                    <div class="moga-district-text-field">
                        <label for="moga_destination_district" class="moga-district-text-label"><?php esc_html_e('Destination District / Area', 'moga-travel-core'); ?></label>
                        <input type="text" id="moga_destination_district" name="moga_destination_district"
                            class="moga-district-text" value="<?php echo esc_attr($dest_district); ?>"
                            placeholder="<?php esc_attr_e('e.g. Old Town', 'moga-travel-core'); ?>">
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
    public static function render_tour_contact($post)
    {
        wp_nonce_field('moga_tour_contact_nonce', 'moga_tour_contact_nonce');

        $organizer = get_post_meta($post->ID, '_moga_organizer_name', true);
        $photo_id  = (int) get_post_meta($post->ID, '_moga_organizer_photo', true);
        $photo_url = $photo_id ? wp_get_attachment_image_url($photo_id, 'thumbnail') : '';
        $phone     = get_post_meta($post->ID, '_moga_phone',          true);
        $whatsapp  = get_post_meta($post->ID, '_moga_whatsapp',       true);
        $email     = get_post_meta($post->ID, '_moga_email',          true);
    ?>
        <div class="moga-metabox">

            <?php // ---- Organizer Photo / Logo ----
            ?>
            <div class="moga-metabox__row">
                <div class="moga-metabox__field">
                    <label><?php esc_html_e('Organizer Photo / Logo (optional override)', 'moga-travel-core'); ?></label>
                    <div class="moga-organizer-photo" id="moga-organizer-photo">
                        <div class="moga-organizer-photo__preview" id="moga-organizer-photo-preview" <?php echo $photo_url ? '' : 'style="display:none;"'; ?>>
                            <img src="<?php echo esc_url($photo_url); ?>" alt="">
                            <button type="button" class="moga-organizer-photo__remove" title="<?php esc_attr_e('Remove', 'moga-travel-core'); ?>">✕</button>
                        </div>
                        <button type="button" class="button" id="moga-organizer-photo-select" <?php echo $photo_url ? 'style="display:none;"' : ''; ?>>
                            <?php esc_html_e('Select Photo or Logo', 'moga-travel-core'); ?>
                        </button>
                        <input type="hidden" id="moga_organizer_photo" name="moga_organizer_photo" value="<?php echo esc_attr($photo_id); ?>">
                    </div>
                    <p class="moga-metabox__hint">
                        <?php esc_html_e('Leave blank to use the photo/logo from your account profile. Only set this if this specific listing needs a different one.', 'moga-travel-core'); ?>
                    </p>
                </div>
            </div>

            <div class="moga-metabox__row">

                <div class="moga-metabox__field">
                    <label for="moga_organizer_name">
                        <?php esc_html_e('Organizer Name (optional override)', 'moga-travel-core'); ?>
                    </label>
                    <input type="text" id="moga_organizer_name" name="moga_organizer_name"
                        value="<?php echo esc_attr($organizer); ?>"
                        placeholder="<?php esc_attr_e('Leave blank to use your account name / company name', 'moga-travel-core'); ?>">
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_tour_phone">
                        <?php esc_html_e('Phone Number (optional override)', 'moga-travel-core'); ?>
                    </label>
                    <input type="tel" id="moga_tour_phone" name="moga_phone"
                        value="<?php echo esc_attr($phone); ?>"
                        class="moga-phone-field"
                        placeholder="<?php esc_attr_e('Leave blank to use your account phone number', 'moga-travel-core'); ?>">
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_tour_whatsapp">
                        <?php esc_html_e('WhatsApp Number (optional override)', 'moga-travel-core'); ?>
                    </label>
                    <input type="tel" id="moga_tour_whatsapp" name="moga_whatsapp"
                        value="<?php echo esc_attr($whatsapp); ?>"
                        class="moga-phone-field"
                        placeholder="<?php esc_attr_e('Leave blank to use your account WhatsApp number', 'moga-travel-core'); ?>">
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_tour_email">
                        <?php esc_html_e('Email Address', 'moga-travel-core'); ?> <span class="required">*</span>
                    </label>
                    <input type="email" id="moga_tour_email" name="moga_email"
                        value="<?php echo esc_attr($email); ?>"
                        placeholder="<?php esc_attr_e('contact@example.com', 'moga-travel-core'); ?>"
                        required>
                    <p class="moga-metabox__hint">
                        <?php esc_html_e('Required — clients use this to contact you about this tour. The listing cannot be published without it.', 'moga-travel-core'); ?>
                    </p>
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
    public static function render_tour_details($post)
    {
        wp_nonce_field('moga_tour_details_nonce', 'moga_tour_details_nonce');

        $difficulty       = get_post_meta($post->ID, '_moga_difficulty',       true) ?: 'easy';
        $tour_type        = get_post_meta($post->ID, '_moga_tour_type',        true) ?: 'group';
        $language         = get_post_meta($post->ID, '_moga_language',         true) ?: 'Arabic';
        $guide_included   = get_post_meta($post->ID, '_moga_guide_included',   true);

        $difficulty_levels = Moga_CPT_Tour::get_difficulty_levels();
        $tour_types        = Moga_CPT_Tour::get_tour_types();
    ?>
        <div class="moga-metabox">
            <div class="moga-metabox__row">

                <div class="moga-metabox__field">
                    <label for="moga_difficulty">
                        <?php esc_html_e('Difficulty Level', 'moga-travel-core'); ?>
                    </label>
                    <select id="moga_difficulty" name="moga_difficulty">
                        <?php foreach ($difficulty_levels as $key => $level) : ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($difficulty, $key); ?>>
                                <?php echo esc_html($level['label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_tour_type">
                        <?php esc_html_e('Tour Type', 'moga-travel-core'); ?>
                    </label>
                    <select id="moga_tour_type" name="moga_tour_type">
                        <?php foreach ($tour_types as $key => $type) : ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($tour_type, $key); ?>>
                                <?php echo esc_html($type['label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

            </div>

            <div class="moga-metabox__row">
                <div class="moga-metabox__field">
                    <label for="moga_language">
                        <?php esc_html_e('Tour Language', 'moga-travel-core'); ?>
                    </label>
                    <input type="text" id="moga_language" name="moga_language"
                        value="<?php echo esc_attr($language); ?>"
                        placeholder="<?php esc_attr_e('e.g. Arabic, English', 'moga-travel-core'); ?>">
                </div>

                <div class="moga-metabox__field">
                    <label class="moga-switch" style="margin-top:24px;">
                        <input type="checkbox" name="moga_guide_included" value="1"
                            <?php checked('1', $guide_included); ?>>
                        <span class="moga-switch__slider"></span>
                        <span class="moga-switch__label">
                            <?php esc_html_e('Guide Included', 'moga-travel-core'); ?>
                        </span>
                    </label>
                </div>
            </div>
        </div>
    <?php
    }

    /**
     * Render tour itinerary meta box.
     *
     * Day-by-day repeater. Day numbers are NOT stored per-row —
     * they are derived from row position on save, so reordering
     * rows (drag handle) automatically renumbers the days and
     * there is no way for the admin to create duplicate/gapped
     * day numbers by hand.
     *
     * JSON schema written to '_moga_itinerary' (matches the schema
     * documented in template-parts/tour/itinerary.php):
     * [
     *   {
     *     "day":           1,
     *     "title":         "Cairo Pyramids & Sphinx",
     *     "location":      "Giza, Cairo",
     *     "duration":      "Full day",
     *     "description":   "...",
     *     "meals":         ["breakfast","lunch"],
     *     "accommodation": "4-star hotel in Cairo (or similar)",
     *     "activities":    ["Great Pyramid of Giza","Sphinx"]
     *   }
     * ]
     *
     * @since  1.0.0
     * @param  WP_Post $post Current post object.
     * @return void
     */
    public static function render_tour_itinerary($post)
    {
        wp_nonce_field('moga_tour_itinerary_nonce', 'moga_tour_itinerary_nonce');

        $itinerary_json = get_post_meta($post->ID, '_moga_itinerary', true);
        $days           = $itinerary_json ? json_decode($itinerary_json, true) : array();
        $days           = is_array($days) ? $days : array();

        $meal_options = array(
            'breakfast' => __('Breakfast', 'moga-travel-core'),
            'lunch'     => __('Lunch',     'moga-travel-core'),
            'dinner'    => __('Dinner',    'moga-travel-core'),
        );
    ?>
        <div class="moga-itinerary-builder">

            <ul id="moga-itinerary-days" class="moga-itinerary-builder__list">
                <?php foreach ($days as $index => $day) : ?>
                    <?php echo self::render_itinerary_day_row($index, $day, $meal_options); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    ?>
                <?php endforeach; ?>
            </ul>

            <button type="button" id="moga-itinerary-add-day" class="moga-itinerary-builder__btn button">
                + <?php esc_html_e('Add Day', 'moga-travel-core'); ?>
            </button>

            <p class="moga-metabox__hint">
                <?php esc_html_e('Drag the ⠿ handle to reorder days — day numbers on the front end follow this order automatically.', 'moga-travel-core'); ?>
            </p>

            <?php // Hidden template for new rows — JS replaces __INDEX__ with the real index on insert.
            ?>
            <script type="text/template" id="moga-itinerary-day-template"><?php echo self::render_itinerary_day_row('__INDEX__', array(), $meal_options); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                                            ?></script>

        </div>
    <?php
    }

    /**
     * Render a single itinerary day row (used for both initial
     * render and as the markup the JS clones for new rows —
     * see moga-itinerary-day-template in meta_box_scripts()).
     *
     * @since  1.0.0
     * @param  int|string $index        Row index (numeric on render, '__INDEX__' placeholder for the JS template).
     * @param  array      $day          Day data — empty array for a blank row.
     * @param  array      $meal_options Meal key => label pairs.
     * @return string HTML for the row.
     */
    private static function render_itinerary_day_row($index, $day, $meal_options)
    {

        $title         = isset($day['title'])         ? $day['title']         : '';
        $location      = isset($day['location'])      ? $day['location']      : '';
        $duration      = isset($day['duration'])       ? $day['duration']       : '';
        $description   = isset($day['description'])   ? $day['description']   : '';
        $accommodation = isset($day['accommodation'])  ? $day['accommodation'] : '';
        $meals         = ! empty($day['meals']) && is_array($day['meals']) ? $day['meals'] : array();
        $activities    = ! empty($day['activities']) && is_array($day['activities']) ? implode("\n", $day['activities']) : '';

        ob_start();
    ?>
        <li class="moga-itinerary-builder__row" data-index="<?php echo esc_attr($index); ?>">

            <div class="moga-itinerary-builder__row-header">
                <span class="moga-itinerary-builder__handle" title="<?php esc_attr_e('Drag to reorder', 'moga-travel-core'); ?>">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <circle cx="9" cy="6" r="1.6" />
                        <circle cx="15" cy="6" r="1.6" />
                        <circle cx="9" cy="12" r="1.6" />
                        <circle cx="15" cy="12" r="1.6" />
                        <circle cx="9" cy="18" r="1.6" />
                        <circle cx="15" cy="18" r="1.6" />
                    </svg>
                </span>
                <span class="moga-itinerary-builder__day-badge">
                    <?php esc_html_e('Day', 'moga-travel-core'); ?>
                    <span class="moga-itinerary-builder__day-number"><?php echo esc_html(is_int($index) ? $index + 1 : ''); ?></span>
                </span>
                <button type="button" class="moga-itinerary-builder__toggle" title="<?php esc_attr_e('Collapse', 'moga-travel-core'); ?>">▾</button>
                <button type="button" class="moga-itinerary-builder__remove" title="<?php esc_attr_e('Remove day', 'moga-travel-core'); ?>">✕</button>
            </div>

            <div class="moga-itinerary-builder__row-body">

                <div class="moga-metabox__row">
                    <div class="moga-metabox__field moga-metabox__field--wide">
                        <label><?php esc_html_e('Title', 'moga-travel-core'); ?> <span class="required">*</span></label>
                        <input type="text" name="moga_itinerary[<?php echo esc_attr($index); ?>][title]"
                            value="<?php echo esc_attr($title); ?>"
                            placeholder="<?php esc_attr_e('e.g. Cairo Pyramids & Sphinx', 'moga-travel-core'); ?>">
                    </div>
                    <div class="moga-metabox__field">
                        <label><?php esc_html_e('Location', 'moga-travel-core'); ?></label>
                        <input type="text" name="moga_itinerary[<?php echo esc_attr($index); ?>][location]"
                            value="<?php echo esc_attr($location); ?>"
                            placeholder="<?php esc_attr_e('e.g. Giza, Cairo', 'moga-travel-core'); ?>">
                    </div>
                    <div class="moga-metabox__field">
                        <label><?php esc_html_e('Duration', 'moga-travel-core'); ?></label>
                        <input type="text" name="moga_itinerary[<?php echo esc_attr($index); ?>][duration]"
                            value="<?php echo esc_attr($duration); ?>"
                            placeholder="<?php esc_attr_e('e.g. Full day, 3 hours', 'moga-travel-core'); ?>">
                    </div>
                </div>

                <div class="moga-metabox__row moga-metabox__row--full">
                    <div class="moga-metabox__field">
                        <label><?php esc_html_e('Description', 'moga-travel-core'); ?></label>
                        <textarea name="moga_itinerary[<?php echo esc_attr($index); ?>][description]" rows="3"
                            placeholder="<?php esc_attr_e('What happens this day…', 'moga-travel-core'); ?>"><?php echo esc_textarea($description); ?></textarea>
                    </div>
                </div>

                <div class="moga-metabox__row">
                    <div class="moga-metabox__field">
                        <label><?php esc_html_e('Meals Included', 'moga-travel-core'); ?></label>
                        <div class="moga-itinerary-builder__meals">
                            <?php foreach ($meal_options as $key => $label) : ?>
                                <label class="moga-checklist__item moga-checklist__item--inline">
                                    <input type="checkbox" name="moga_itinerary[<?php echo esc_attr($index); ?>][meals][]"
                                        value="<?php echo esc_attr($key); ?>"
                                        <?php checked(in_array($key, $meals, true)); ?>>
                                    <?php echo esc_html($label); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="moga-metabox__field moga-metabox__field--wide">
                        <label><?php esc_html_e('Accommodation', 'moga-travel-core'); ?></label>
                        <input type="text" name="moga_itinerary[<?php echo esc_attr($index); ?>][accommodation]"
                            value="<?php echo esc_attr($accommodation); ?>"
                            placeholder="<?php esc_attr_e('e.g. 4-star hotel in Cairo (or similar) — leave blank for day trips', 'moga-travel-core'); ?>">
                    </div>
                </div>

                <div class="moga-metabox__row moga-metabox__row--full">
                    <div class="moga-metabox__field">
                        <label><?php esc_html_e('Activities / Highlights', 'moga-travel-core'); ?></label>
                        <textarea name="moga_itinerary[<?php echo esc_attr($index); ?>][activities]" rows="3"
                            placeholder="<?php esc_attr_e("One per line, e.g.\nGreat Pyramid of Giza\nSphinx\nEgyptian Museum", 'moga-travel-core'); ?>"><?php echo esc_textarea($activities); ?></textarea>
                        <p class="moga-metabox__hint"><?php esc_html_e('One activity per line.', 'moga-travel-core'); ?></p>
                    </div>
                </div>

            </div>
            <?php // ---- End .moga-itinerary-builder__row-body ----
            ?>

        </li>
    <?php
        return ob_get_clean();
    }

    /**
     * Render tour includes / excludes meta box.
     *
     * @since  1.0.0
     * @param  WP_Post $post Current post object.
     * @return void
     */
    public static function render_tour_includes($post)
    {
        wp_nonce_field('moga_tour_includes_nonce', 'moga_tour_includes_nonce');

        $saved_includes = get_post_meta($post->ID, '_moga_includes', true);
        $saved_excludes = get_post_meta($post->ID, '_moga_excludes', true);
        $saved_includes = $saved_includes ? json_decode($saved_includes, true) : array();
        $saved_excludes = $saved_excludes ? json_decode($saved_excludes, true) : array();

        $custom_includes = get_post_meta($post->ID, '_moga_includes_custom', true);
        $custom_excludes = get_post_meta($post->ID, '_moga_excludes_custom', true);
        $custom_includes = $custom_includes ? json_decode($custom_includes, true) : array();
        $custom_excludes = $custom_excludes ? json_decode($custom_excludes, true) : array();
        $custom_includes = is_array($custom_includes) ? $custom_includes : array();
        $custom_excludes = is_array($custom_excludes) ? $custom_excludes : array();

        $includes_options = Moga_CPT_Tour::get_includes_options();
        $excludes_options = Moga_CPT_Tour::get_excludes_options();
    ?>
        <div class="moga-metabox moga-metabox--two-col">

            <div class="moga-metabox__col">
                <h4 class="moga-metabox__section-title moga-metabox__section-title--green">
                    ✅ <?php esc_html_e('Included', 'moga-travel-core'); ?>
                </h4>
                <div class="moga-checklist">
                    <?php foreach ($includes_options as $key => $label) : ?>
                        <label class="moga-checklist__item">
                            <input type="checkbox" name="moga_includes[]" value="<?php echo esc_attr($key); ?>"
                                <?php checked(in_array($key, $saved_includes, true)); ?>>
                            <?php echo esc_html($label); ?>
                        </label>
                    <?php endforeach; ?>
                </div>

                <?php // ---- Custom included items ----
                ?>
                <ul class="moga-custom-items" id="moga-includes-custom-list">
                    <?php foreach ($custom_includes as $i => $item) : ?>
                        <li class="moga-custom-items__row">
                            <input type="text" name="moga_includes_custom[]" value="<?php echo esc_attr($item); ?>">
                            <button type="button" class="moga-custom-items__remove" title="<?php esc_attr_e('Remove', 'moga-travel-core'); ?>">✕</button>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="moga-custom-items__add button" data-target="moga-includes-custom-list" data-name="moga_includes_custom[]">
                    + <?php esc_html_e('Add Custom Item', 'moga-travel-core'); ?>
                </button>
            </div>

            <div class="moga-metabox__col">
                <h4 class="moga-metabox__section-title moga-metabox__section-title--red">
                    ❌ <?php esc_html_e('Not Included', 'moga-travel-core'); ?>
                </h4>
                <div class="moga-checklist">
                    <?php foreach ($excludes_options as $key => $label) : ?>
                        <label class="moga-checklist__item">
                            <input type="checkbox" name="moga_excludes[]" value="<?php echo esc_attr($key); ?>"
                                <?php checked(in_array($key, $saved_excludes, true)); ?>>
                            <?php echo esc_html($label); ?>
                        </label>
                    <?php endforeach; ?>
                </div>

                <?php // ---- Custom excluded items ----
                ?>
                <ul class="moga-custom-items" id="moga-excludes-custom-list">
                    <?php foreach ($custom_excludes as $i => $item) : ?>
                        <li class="moga-custom-items__row">
                            <input type="text" name="moga_excludes_custom[]" value="<?php echo esc_attr($item); ?>">
                            <button type="button" class="moga-custom-items__remove" title="<?php esc_attr_e('Remove', 'moga-travel-core'); ?>">✕</button>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="moga-custom-items__add button" data-target="moga-excludes-custom-list" data-name="moga_excludes_custom[]">
                    + <?php esc_html_e('Add Custom Item', 'moga-travel-core'); ?>
                </button>
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
    public static function render_tour_bus($post)
    {
        wp_nonce_field('moga_tour_bus_nonce', 'moga_tour_bus_nonce');

        // Load existing bus data if one is already linked to this tour.
        $bus_id = get_post_meta($post->ID, '_moga_bus_id', true);
        $bus    = $bus_id ? get_post($bus_id) : null;

        // Bus fields — read from the linked bus post if one exists,
        // otherwise empty defaults for a new bus.
        $plate    = $bus_id ? get_post_meta($bus_id, '_moga_bus_plate',   true) : '';
        $model    = $bus_id ? get_post_meta($bus_id, '_moga_bus_model',   true) : '';
        $year     = $bus_id ? get_post_meta($bus_id, '_moga_bus_year',    true) : '';
        $color    = $bus_id ? get_post_meta($bus_id, '_moga_bus_color',   true) : '';
        $bus_type = $bus_id ? (get_post_meta($bus_id, '_moga_bus_type',   true) ?: 'standard') : 'standard';

        $layout      = $bus_id ? (get_post_meta($bus_id, '_moga_seat_layout',  true) ?: '2+2') : '2+2';
        $rows        = $bus_id ? (get_post_meta($bus_id, '_moga_seat_rows',    true) ?: 10)    : 10;
        $driver_seat = $bus_id ? (get_post_meta($bus_id, '_moga_driver_seat',  true) ?: 'front-left') : 'front-left';
        $total_seats = $bus_id ? get_post_meta($bus_id, '_moga_total_seats',   true) : 0;

        $vip_json      = $bus_id ? get_post_meta($bus_id, '_moga_vip_seats',      true) : '';
        $disabled_json = $bus_id ? get_post_meta($bus_id, '_moga_disabled_seats', true) : '';
        $vip_seats     = $vip_json      ? implode(', ', json_decode($vip_json,      true) ?: array()) : '';
        $disabled_seats = $disabled_json ? implode(', ', json_decode($disabled_json, true) ?: array()) : '';

        $driver_name    = $bus_id ? get_post_meta($bus_id, '_moga_driver_name',    true) : '';
        $driver_phone   = $bus_id ? get_post_meta($bus_id, '_moga_driver_phone',   true) : '';
        $driver_license = $bus_id ? get_post_meta($bus_id, '_moga_driver_license', true) : '';

        $has_ac         = $bus_id ? get_post_meta($bus_id, '_moga_has_ac',         true) : '1';
        $has_wifi       = $bus_id ? get_post_meta($bus_id, '_moga_has_wifi',       true) : '0';
        $has_tv         = $bus_id ? get_post_meta($bus_id, '_moga_has_tv',         true) : '0';
        $has_usb        = $bus_id ? get_post_meta($bus_id, '_moga_has_usb',        true) : '0';
        $has_toilet     = $bus_id ? get_post_meta($bus_id, '_moga_has_toilet',     true) : '0';
        $has_reclining  = $bus_id ? get_post_meta($bus_id, '_moga_has_reclining',  true) : '0';
        $has_luggage    = $bus_id ? get_post_meta($bus_id, '_moga_has_luggage',    true) : '1';
        $has_wheelchair = $bus_id ? get_post_meta($bus_id, '_moga_has_wheelchair', true) : '0';

        $bus_types        = class_exists('Moga_CPT_Bus') ? Moga_CPT_Bus::get_bus_types()       : array();
        $layouts          = class_exists('Moga_CPT_Bus') ? Moga_CPT_Bus::get_seat_layouts()    : array();
        $driver_positions = class_exists('Moga_CPT_Bus') ? Moga_CPT_Bus::get_driver_positions() : array();

        // Hidden field carries the linked bus ID — written on save
        // by the save handler which creates/updates the bus post.
        // Value of 0 = no bus yet (new tour or tour without a bus).
    ?>
        <div class="moga-metabox">

            <?php // If a bus is already linked, show its name as context. ?>
            <?php if ($bus) : ?>
                <div class="moga-metabox__row">
                    <div class="moga-metabox__field">
                        <p class="moga-metabox__hint">
                            <?php
                            printf(
                                /* translators: %s: bus post title */
                                esc_html__('Currently linked bus: %s', 'moga-travel-core'),
                                '<strong>' . esc_html($bus->post_title) . '</strong>'
                            );
                            ?>
                            &nbsp;
                            <a href="<?php echo esc_url(get_edit_post_link($bus_id)); ?>" target="_blank">
                                <?php esc_html_e('View full bus editor →', 'moga-travel-core'); ?>
                            </a>
                        </p>
                    </div>
                </div>
            <?php else : ?>
                <div class="moga-metabox__row">
                    <div class="moga-metabox__field">
                        <p class="moga-metabox__hint">
                            <?php esc_html_e('Fill in the bus details below. A dedicated bus post will be created and linked to this tour automatically when you save.', 'moga-travel-core'); ?>
                        </p>
                    </div>
                </div>
            <?php endif; ?>

            <input type="hidden" name="moga_bus_id" id="moga_bus_id" value="<?php echo esc_attr($bus_id ?: 0); ?>">

            <?php // ---- Bus Identity ---- ?>
            <div class="moga-metabox__section-title"><?php esc_html_e('Bus Identity', 'moga-travel-core'); ?></div>

            <div class="moga-metabox__row">
                <div class="moga-metabox__field">
                    <label for="moga_tour_bus_title"><?php esc_html_e('Bus Name / Title', 'moga-travel-core'); ?> <span class="required">*</span></label>
                    <input type="text" id="moga_tour_bus_title" name="moga_tour_bus_title"
                        value="<?php echo esc_attr($bus ? $bus->post_title : ''); ?>"
                        placeholder="<?php esc_attr_e('e.g. Mercedes Sprinter — Cairo Fleet', 'moga-travel-core'); ?>">
                </div>
                <div class="moga-metabox__field">
                    <label for="moga_tour_bus_plate"><?php esc_html_e('Plate Number', 'moga-travel-core'); ?></label>
                    <input type="text" id="moga_tour_bus_plate" name="moga_tour_bus_plate"
                        value="<?php echo esc_attr($plate); ?>"
                        placeholder="<?php esc_attr_e('e.g. ABC-1234', 'moga-travel-core'); ?>">
                </div>
            </div>

            <div class="moga-metabox__row">
                <div class="moga-metabox__field">
                    <label for="moga_tour_bus_model"><?php esc_html_e('Make & Model', 'moga-travel-core'); ?></label>
                    <input type="text" id="moga_tour_bus_model" name="moga_tour_bus_model"
                        value="<?php echo esc_attr($model); ?>"
                        placeholder="<?php esc_attr_e('e.g. Mercedes Sprinter', 'moga-travel-core'); ?>">
                </div>
                <div class="moga-metabox__field">
                    <label for="moga_tour_bus_year"><?php esc_html_e('Year', 'moga-travel-core'); ?></label>
                    <input type="number" id="moga_tour_bus_year" name="moga_tour_bus_year"
                        value="<?php echo esc_attr($year); ?>"
                        min="1990" max="<?php echo esc_attr(date('Y') + 1); ?>"
                        placeholder="<?php echo esc_attr(date('Y')); ?>">
                </div>
                <div class="moga-metabox__field">
                    <label for="moga_tour_bus_color"><?php esc_html_e('Color', 'moga-travel-core'); ?></label>
                    <input type="text" id="moga_tour_bus_color" name="moga_tour_bus_color"
                        value="<?php echo esc_attr($color); ?>"
                        placeholder="<?php esc_attr_e('e.g. White', 'moga-travel-core'); ?>">
                </div>
            </div>

            <div class="moga-metabox__row">
                <div class="moga-metabox__field">
                    <label for="moga_tour_bus_type"><?php esc_html_e('Bus Type', 'moga-travel-core'); ?></label>
                    <select id="moga_tour_bus_type" name="moga_tour_bus_type">
                        <?php foreach ($bus_types as $key => $type) : ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($bus_type, $key); ?>>
                                <?php echo esc_html($type['label']); ?>
                                <?php if (! empty($type['capacity'])) : ?>
                                    — <?php echo esc_html($type['capacity']); ?>
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <?php // ---- Seat Layout ---- ?>
            <div class="moga-metabox__section-title" style="margin-top:20px;">
                <?php esc_html_e('Seat Layout', 'moga-travel-core'); ?>
            </div>

            <div class="moga-metabox__row">
                <div class="moga-metabox__field">
                    <label for="moga_tour_seat_layout"><?php esc_html_e('Layout', 'moga-travel-core'); ?></label>
                    <select id="moga_tour_seat_layout" name="moga_tour_seat_layout">
                        <?php foreach ($layouts as $key => $ldata) : ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($layout, $key); ?>>
                                <?php echo esc_html($ldata['label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="moga-metabox__hint" id="moga-tour-layout-desc">
                        <?php echo isset($layouts[$layout]['desc']) ? esc_html($layouts[$layout]['desc']) : ''; ?>
                    </p>
                </div>
                <div class="moga-metabox__field">
                    <label for="moga_tour_seat_rows"><?php esc_html_e('Number of Rows', 'moga-travel-core'); ?></label>
                    <input type="number" id="moga_tour_seat_rows" name="moga_tour_seat_rows"
                        value="<?php echo esc_attr($rows); ?>"
                        min="1" max="30">
                    <p class="moga-metabox__hint">
                        <?php esc_html_e('Total seats:', 'moga-travel-core'); ?>
                        <strong id="moga-tour-total-seats-display">
                            <?php echo esc_html($total_seats ?: ($rows * (isset($layouts[$layout]['columns']) ? $layouts[$layout]['columns'] : 4))); ?>
                        </strong>
                    </p>
                </div>
                <div class="moga-metabox__field">
                    <label for="moga_tour_driver_seat"><?php esc_html_e('Driver Position', 'moga-travel-core'); ?></label>
                    <select id="moga_tour_driver_seat" name="moga_tour_driver_seat">
                        <?php foreach ($driver_positions as $key => $label) : ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($driver_seat, $key); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="moga-metabox__row moga-metabox__row--full" style="margin-top:4px;">
                <div class="moga-metabox__field">
                    <label for="moga_tour_vip_seats">
                        <?php esc_html_e('VIP Seats', 'moga-travel-core'); ?>
                        <span class="moga-metabox__label-hint">— <?php esc_html_e('optional', 'moga-travel-core'); ?></span>
                    </label>
                    <input type="text" id="moga_tour_vip_seats" name="moga_tour_vip_seats"
                        value="<?php echo esc_attr($vip_seats); ?>"
                        placeholder="<?php esc_attr_e('e.g. 1A, 1B, 1C, 1D', 'moga-travel-core'); ?>">
                    <p class="moga-metabox__hint">
                        <?php esc_html_e('Comma-separated seat numbers. Highlighted on the seat map but bookable by any guest.', 'moga-travel-core'); ?>
                    </p>
                </div>
                <div class="moga-metabox__field">
                    <label for="moga_tour_disabled_seats">
                        <?php esc_html_e('Unavailable Seats', 'moga-travel-core'); ?>
                        <span class="moga-metabox__label-hint">— <?php esc_html_e('optional', 'moga-travel-core'); ?></span>
                    </label>
                    <input type="text" id="moga_tour_disabled_seats" name="moga_tour_disabled_seats"
                        value="<?php echo esc_attr($disabled_seats); ?>"
                        placeholder="<?php esc_attr_e('e.g. 5C, 5D', 'moga-travel-core'); ?>">
                    <p class="moga-metabox__hint">
                        <?php esc_html_e('Comma-separated. Never offered for booking — guide seat, broken seats, etc.', 'moga-travel-core'); ?>
                    </p>
                </div>
            </div>

            <?php // ---- Seat Map Preview ---- ?>
            <div class="moga-metabox__row moga-metabox__row--full" style="margin-top:8px;">
                <div class="moga-metabox__field">
                    <label><?php esc_html_e('Seat Map Preview', 'moga-travel-core'); ?></label>
                    <div id="moga-tour-bus-seat-preview" class="moga-bus-seat-preview"></div>
                </div>
            </div>

            <?php // ---- Onboard Amenities ---- ?>
            <div class="moga-metabox__section-title" style="margin-top:20px;">
                <?php esc_html_e('Onboard Amenities', 'moga-travel-core'); ?>
            </div>

            <div class="moga-metabox__row moga-metabox__row--full">
                <div class="moga-amenity-pills">
                    <?php
                    $amenities = array(
                        'moga_tour_has_ac'         => array('label' => __('Air Conditioning', 'moga-travel-core'), 'val' => $has_ac,         'default' => '1'),
                        'moga_tour_has_wifi'       => array('label' => __('WiFi',             'moga-travel-core'), 'val' => $has_wifi,       'default' => '0'),
                        'moga_tour_has_tv'         => array('label' => __('TV Screens',       'moga-travel-core'), 'val' => $has_tv,         'default' => '0'),
                        'moga_tour_has_usb'        => array('label' => __('USB Charging',     'moga-travel-core'), 'val' => $has_usb,        'default' => '0'),
                        'moga_tour_has_toilet'     => array('label' => __('Onboard Toilet',   'moga-travel-core'), 'val' => $has_toilet,     'default' => '0'),
                        'moga_tour_has_reclining'  => array('label' => __('Reclining Seats',  'moga-travel-core'), 'val' => $has_reclining,  'default' => '0'),
                        'moga_tour_has_luggage'    => array('label' => __('Luggage Space',    'moga-travel-core'), 'val' => $has_luggage,    'default' => '1'),
                        'moga_tour_has_wheelchair' => array('label' => __('Wheelchair Access','moga-travel-core'), 'val' => $has_wheelchair, 'default' => '0'),
                    );
                    foreach ($amenities as $key => $amenity) :
                        $checked = ('' === $amenity['val']) ? $amenity['default'] : $amenity['val'];
                    ?>
                        <label class="moga-amenity-pill <?php echo '1' === $checked ? 'moga-amenity-pill--checked' : ''; ?>">
                            <input type="checkbox" name="<?php echo esc_attr($key); ?>" value="1"
                                <?php checked('1', $checked); ?>>
                            <?php echo esc_html($amenity['label']); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php // ---- Driver Information ---- ?>
            <div class="moga-metabox__section-title" style="margin-top:20px;">
                <?php esc_html_e('Driver Information', 'moga-travel-core'); ?>
            </div>

            <div class="moga-metabox__row">
                <div class="moga-metabox__field">
                    <label for="moga_tour_driver_name"><?php esc_html_e('Driver Name', 'moga-travel-core'); ?></label>
                    <input type="text" id="moga_tour_driver_name" name="moga_tour_driver_name"
                        value="<?php echo esc_attr($driver_name); ?>"
                        placeholder="<?php esc_attr_e('Full name', 'moga-travel-core'); ?>">
                </div>
                <div class="moga-metabox__field">
                    <label for="moga_tour_driver_license"><?php esc_html_e('License Number', 'moga-travel-core'); ?></label>
                    <input type="text" id="moga_tour_driver_license" name="moga_tour_driver_license"
                        value="<?php echo esc_attr($driver_license); ?>"
                        placeholder="<?php esc_attr_e('e.g. DL-123456', 'moga-travel-core'); ?>">
                </div>
            </div>

            <div class="moga-metabox__row">
                <div class="moga-metabox__field">
                    <label for="moga_tour_driver_phone"><?php esc_html_e('Driver Phone', 'moga-travel-core'); ?></label>
                    <input type="tel" id="moga_tour_driver_phone" name="moga_tour_driver_phone"
                        value="<?php echo esc_attr($driver_phone); ?>"
                        placeholder="+20 10 XXXX XXXX"
                        class="moga-phone-field">
                    <p class="moga-metabox__hint">
                        <?php esc_html_e('Internal use only — not shown to guests.', 'moga-travel-core'); ?>
                    </p>
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
    public static function render_tour_status($post)
    {
        wp_nonce_field('moga_tour_status_nonce', 'moga_tour_status_nonce');

        $featured        = get_post_meta($post->ID, '_moga_featured',        true);
        $instant_booking = get_post_meta($post->ID, '_moga_instant_booking', true);
        $active          = get_post_meta($post->ID, '_moga_active',          true);

        if ('' === $active) {
            $active = '1';
        }
    ?>
        <div class="moga-metabox">
            <div class="moga-metabox__switches">

                <label class="moga-switch">
                    <input type="checkbox" name="moga_active" value="1" <?php checked('1', $active); ?>>
                    <span class="moga-switch__slider"></span>
                    <span class="moga-switch__label">
                        <?php esc_html_e('Active — visible to guests', 'moga-travel-core'); ?>
                    </span>
                </label>

                <label class="moga-switch">
                    <input type="checkbox" name="moga_featured" value="1" <?php checked('1', $featured); ?>>
                    <span class="moga-switch__slider"></span>
                    <span class="moga-switch__label">
                        <?php esc_html_e('Featured — shown on homepage', 'moga-travel-core'); ?>
                    </span>
                </label>

                <label class="moga-switch">
                    <input type="checkbox" name="moga_instant_booking" value="1" <?php checked('1', $instant_booking); ?>>
                    <span class="moga-switch__slider"></span>
                    <span class="moga-switch__label">
                        <?php esc_html_e('Instant Booking — no approval needed', 'moga-travel-core'); ?>
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
    public static function save_meta_boxes($post_id, $post)
    {

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (wp_is_post_revision($post_id)) {
            return;
        }

        // Save gallery and videos for all supported CPTs.
        $gallery_cpts = array('moga_property', 'moga_tour', 'moga_destination');
        if (in_array($post->post_type, $gallery_cpts, true)) {
            self::save_gallery_meta($post_id);
            self::save_videos_meta($post_id);
        }

        if ('moga_property' === $post->post_type) {
            self::save_property_meta($post_id);
        } elseif ('moga_tour' === $post->post_type) {
            self::save_tour_meta($post_id);
        }
    }

    /**
     * Block Property/Tour from publishing without a contact email.
     *
     * This is a real requirement, not a UI nicety — the vendor
     * contact form on the single page (vendor-contact-form.php)
     * silently doesn't render at all if no email resolves, so an
     * empty email here would make the whole listing unreachable by
     * clients with no explanation. Runs on 'wp_insert_post_data',
     * BEFORE the database write — unlike save_post (which fires
     * after), this can actually change what status gets saved.
     * HTML5 `required` on the field is a UX nicety only; this is
     * the real enforcement, since client-side validation can
     * always be bypassed.
     *
     * @since  1.0.0
     * @param  array $data    Post data about to be saved.
     * @param  array $postarr Raw $_POST-derived data, including post ID.
     * @return array
     */
    public static function require_contact_email($data, $postarr)
    {

        if (! in_array($data['post_type'], array('moga_property', 'moga_tour'), true)) {
            return $data;
        }
        if ('publish' !== $data['post_status']) {
            return $data;
        }

        $email = isset($_POST['moga_email']) ? sanitize_email(wp_unslash($_POST['moga_email'])) : '';

        if (! $email || ! is_email($email)) {
            $data['post_status'] = 'draft';
            update_option('moga_email_required_flag_' . get_current_user_id(), '1', false);
        }

        return $data;
    }

    /**
     * Append a query flag to the post-save redirect URL when
     * require_contact_email() just forced a listing back to draft,
     * so show_email_required_notice() knows to display the warning
     * on the very next page load.
     *
     * @since  1.0.0
     * @param  string $location Redirect URL.
     * @param  int    $post_id  Post ID.
     * @return string
     */
    public static function flag_email_required_redirect($location, $post_id)
    {

        if (get_option('moga_email_required_flag_' . get_current_user_id())) {
            delete_option('moga_email_required_flag_' . get_current_user_id());
            $location = add_query_arg('moga_email_required', '1', $location);
        }

        return $location;
    }

    /**
     * Show the "email is required to publish" admin notice.
     *
     * @since  1.0.0
     * @return void
     */
    public static function show_email_required_notice()
    {

        if (empty($_GET['moga_email_required'])) {
            return;
        }
    ?>
        <div class="notice notice-error is-dismissible">
            <p>
                <?php esc_html_e('This listing was saved as a draft because the Contact Email field is required before publishing. Clients need a working email to reach you — please add one in the Contact box below and publish again.', 'moga-travel-core'); ?>
            </p>
        </div>
    <?php
    }

    /**
     * Show a one-time admin notice for any Pricing Period rejected
     * during save_pricing_periods() for overlapping an already-
     * accepted period. Deletes the transient immediately so it only
     * ever shows once, on the page load right after saving.
     *
     * @since  1.0.0
     * @return void
     */
    public static function show_rejected_periods_notice()
    {
        global $post;

        if (! $post || ! current_user_can('edit_post', $post->ID)) {
            return;
        }

        $key      = 'moga_periods_rejected_' . $post->ID . '_' . get_current_user_id();
        $messages = get_transient($key);

        if (empty($messages)) {
            return;
        }

        delete_transient($key);
    ?>
        <div class="notice notice-error is-dismissible">
            <p><strong><?php esc_html_e('Some Pricing Periods could not be saved:', 'moga-travel-core'); ?></strong></p>
            <ul style="list-style:disc;margin-left:20px;">
                <?php foreach ($messages as $message) : ?>
                    <li><?php echo esc_html($message); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php
    }

    /**
     * Notice for Tour Groups that were entered but couldn't be saved —
     * a group had a real start date but no valid capacity, so it
     * would have been unbookable if kept. Mirrors
     * show_rejected_periods_notice()'s exact same pattern.
     *
     * @since  1.0.0
     * @return void
     */
    public static function show_skipped_tour_groups_notice()
    {
        global $post;

        if (! $post || ! current_user_can('edit_post', $post->ID)) {
            return;
        }

        $key           = 'moga_tour_groups_skipped_' . $post->ID . '_' . get_current_user_id();
        $skipped_count = get_transient($key);

        if (empty($skipped_count)) {
            return;
        }

        delete_transient($key);
    ?>
        <div class="notice notice-error is-dismissible">
            <p>
                <?php
                printf(
                    /* translators: %d: number of skipped groups */
                    esc_html(_n(
                        '%d Tour Group had a start date but no capacity set, so it was not saved — add a capacity and try again.',
                        '%d Tour Groups had a start date but no capacity set, so they were not saved — add a capacity and try again.',
                        $skipped_count,
                        'moga-travel-core'
                    )),
                    $skipped_count
                );
                ?>
            </p>
        </div>
    <?php
    }

    /**
     * Save gallery meta.
     *
     * @since  1.0.0
     * @param  int $post_id Post ID.
     * @return void
     */
    private static function save_gallery_meta($post_id)
    {

        if (
            ! isset($_POST['moga_gallery_nonce'])
            || ! wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['moga_gallery_nonce'])),
                'moga_gallery_nonce'
            )
        ) {
            return;
        }

        $gallery_ids = isset($_POST['moga_gallery_ids']) && is_array($_POST['moga_gallery_ids'])
            ? array_map('absint', $_POST['moga_gallery_ids'])
            : array();

        // Enforce maximum.
        $gallery_ids = array_slice($gallery_ids, 0, self::MAX_GALLERY_IMAGES);

        update_post_meta($post_id, '_moga_gallery', wp_json_encode($gallery_ids));
    }

    /**
     * Save videos meta.
     *
     * @since  1.0.0
     * @param  int $post_id Post ID.
     * @return void
     */
    private static function save_videos_meta($post_id)
    {

        if (
            ! isset($_POST['moga_videos_nonce'])
            || ! wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['moga_videos_nonce'])),
                'moga_videos_nonce'
            )
        ) {
            return;
        }

        $videos = array();

        // Save URL videos.
        if (isset($_POST['moga_video_urls']) && is_array($_POST['moga_video_urls'])) {
            $urls = array_map('esc_url_raw', wp_unslash($_POST['moga_video_urls']));
            foreach ($urls as $url) {
                if (! empty($url)) {
                    $videos[] = array(
                        'type' => 'url',
                        'url'  => $url,
                    );
                }
            }
        }

        // Enforce max URL videos.
        $url_videos = array_filter($videos, fn($v) => 'url' === $v['type']);
        if (count($url_videos) > self::MAX_VIDEO_URLS) {
            $videos = array_slice(array_values($url_videos), 0, self::MAX_VIDEO_URLS);
        }

        // Save uploaded videos.
        if (isset($_POST['moga_video_upload_ids']) && is_array($_POST['moga_video_upload_ids'])) {
            $upload_ids = array_map('absint', $_POST['moga_video_upload_ids']);
            $upload_ids = array_slice($upload_ids, 0, self::MAX_VIDEO_UPLOADS);
            foreach ($upload_ids as $id) {
                if ($id > 0) {
                    $videos[] = array(
                        'type' => 'upload',
                        'id'   => $id,
                        'url'  => wp_get_attachment_url($id),
                    );
                }
            }
        }

        update_post_meta($post_id, '_moga_videos', wp_json_encode($videos));
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
    private static function save_property_meta($post_id)
    {

        // ---- Pricing ----
        if (
            isset($_POST['moga_property_pricing_nonce'])
            && wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['moga_property_pricing_nonce'])),
                'moga_property_pricing_nonce'
            )
        ) {
            update_post_meta(
                $post_id,
                '_moga_currency',
                isset($_POST['moga_currency']) ? sanitize_text_field(wp_unslash($_POST['moga_currency'])) : 'USD'
            );

            // ---- Pricing Periods ----
            // Everything that used to be a flat, property-wide field
            // here (price, weekend price, weekend days, discount)
            // moved into save_pricing_periods() — a property has no
            // default rate of its own anymore, only periods.
            self::save_pricing_periods($post_id);
        }

        // ---- Location ----
        if (
            isset($_POST['moga_property_location_nonce'])
            && wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['moga_property_location_nonce'])),
                'moga_property_location_nonce'
            )
        ) {
            $country     = isset($_POST['moga_country'])     ? sanitize_text_field(wp_unslash($_POST['moga_country'])) : '';
            $province    = isset($_POST['moga_province'])    ? sanitize_text_field(wp_unslash($_POST['moga_province'])) : '';
            $province_id = isset($_POST['moga_province_id']) ? absint($_POST['moga_province_id']) : 0;
            $city        = isset($_POST['moga_city'])        ? sanitize_text_field(wp_unslash($_POST['moga_city'])) : '';
            $city_id     = isset($_POST['moga_city_id'])     ? absint($_POST['moga_city_id']) : 0;
            $district    = isset($_POST['moga_district'])    ? sanitize_text_field(wp_unslash($_POST['moga_district'])) : '';
            $latitude    = isset($_POST['moga_latitude'])    ? sanitize_text_field(wp_unslash($_POST['moga_latitude'])) : '';
            $longitude   = isset($_POST['moga_longitude'])   ? sanitize_text_field(wp_unslash($_POST['moga_longitude'])) : '';

            update_post_meta($post_id, '_moga_country',     $country);
            update_post_meta($post_id, '_moga_province',    $province);
            update_post_meta($post_id, '_moga_province_id', $province_id);
            update_post_meta($post_id, '_moga_city',        $city);
            update_post_meta($post_id, '_moga_city_id',     $city_id);
            update_post_meta($post_id, '_moga_district',    $district);
            update_post_meta(
                $post_id,
                '_moga_address',
                isset($_POST['moga_address']) ? sanitize_text_field(wp_unslash($_POST['moga_address'])) : ''
            );
            update_post_meta(
                $post_id,
                '_moga_postal_code',
                isset($_POST['moga_postal_code']) ? sanitize_text_field(wp_unslash($_POST['moga_postal_code'])) : ''
            );
            update_post_meta($post_id, '_moga_latitude',  $latitude);
            update_post_meta($post_id, '_moga_longitude', $longitude);

            // Resolve country display name.
            $country_name = '';
            if ($country) {
                $country_data = moga_get_country($country);
                if ($country_data) {
                    $country_name = $country_data['name'];
                    update_post_meta($post_id, '_moga_country_name', $country_name);
                }
            }

            // Auto-sync to moga_location taxonomy (four levels).
            if ($country && $province && $city) {
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
        if (
            isset($_POST['moga_property_contact_nonce'])
            && wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['moga_property_contact_nonce'])),
                'moga_property_contact_nonce'
            )
        ) {
            update_post_meta(
                $post_id,
                '_moga_phone',
                isset($_POST['moga_phone']) ? moga_sanitize_phone(wp_unslash($_POST['moga_phone'])) : ''
            );
            update_post_meta(
                $post_id,
                '_moga_whatsapp',
                isset($_POST['moga_whatsapp']) ? moga_sanitize_phone(wp_unslash($_POST['moga_whatsapp'])) : ''
            );
            update_post_meta(
                $post_id,
                '_moga_email',
                isset($_POST['moga_email']) ? sanitize_email(wp_unslash($_POST['moga_email'])) : ''
            );
        }

        // ---- Details ----
        if (
            isset($_POST['moga_property_details_nonce'])
            && wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['moga_property_details_nonce'])),
                'moga_property_details_nonce'
            )
        ) {
            update_post_meta(
                $post_id,
                '_moga_max_guests',
                isset($_POST['moga_max_guests']) ? absint($_POST['moga_max_guests']) : 1
            );
            update_post_meta(
                $post_id,
                '_moga_bedrooms',
                isset($_POST['moga_bedrooms']) ? absint($_POST['moga_bedrooms']) : 1
            );
            update_post_meta(
                $post_id,
                '_moga_bathrooms',
                isset($_POST['moga_bathrooms']) ? floatval($_POST['moga_bathrooms']) : 1
            );
            update_post_meta(
                $post_id,
                '_moga_area',
                isset($_POST['moga_area']) ? floatval($_POST['moga_area']) : 0
            );
            update_post_meta(
                $post_id,
                '_moga_floor',
                isset($_POST['moga_floor']) ? absint($_POST['moga_floor']) : 0
            );
            update_post_meta(
                $post_id,
                '_moga_building_floors',
                isset($_POST['moga_building_floors']) ? absint($_POST['moga_building_floors']) : 1
            );
            update_post_meta(
                $post_id,
                '_moga_year_built',
                isset($_POST['moga_year_built']) ? absint($_POST['moga_year_built']) : 0
            );
            update_post_meta(
                $post_id,
                '_moga_cancellation',
                isset($_POST['moga_cancellation']) ? sanitize_text_field(wp_unslash($_POST['moga_cancellation'])) : 'moderate'
            );
        }

        // ---- Amenities ----
        if (
            isset($_POST['moga_property_amenities_nonce'])
            && wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['moga_property_amenities_nonce'])),
                'moga_property_amenities_nonce'
            )
        ) {
            $amenities = isset($_POST['moga_amenities']) && is_array($_POST['moga_amenities'])
                ? array_map('sanitize_text_field', wp_unslash($_POST['moga_amenities']))
                : array();
            update_post_meta($post_id, '_moga_amenities', wp_json_encode($amenities));
        }

        // ---- Status ----
        if (
            isset($_POST['moga_property_status_nonce'])
            && wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['moga_property_status_nonce'])),
                'moga_property_status_nonce'
            )
        ) {
            update_post_meta(
                $post_id,
                '_moga_active',
                isset($_POST['moga_active']) ? '1' : '0'
            );
            update_post_meta(
                $post_id,
                '_moga_featured',
                isset($_POST['moga_featured']) ? '1' : '0'
            );
            update_post_meta(
                $post_id,
                '_moga_instant_booking',
                isset($_POST['moga_instant_booking']) ? '1' : '0'
            );
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
    private static function save_tour_meta($post_id)
    {

        // ---- Tour Groups ----
        if (
            isset($_POST['moga_tour_pricing_nonce'])
            && wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['moga_tour_pricing_nonce'])),
                'moga_tour_pricing_nonce'
            )
        ) {
            update_post_meta(
                $post_id,
                '_moga_currency',
                isset($_POST['moga_currency']) ? sanitize_text_field(wp_unslash($_POST['moga_currency'])) : 'USD'
            );
            update_post_meta(
                $post_id,
                '_moga_booking_cutoff_hours',
                isset($_POST['moga_booking_cutoff_hours']) ? absint($_POST['moga_booking_cutoff_hours']) : 24
            );

            $groups_input = isset($_POST['moga_tour_groups']) && is_array($_POST['moga_tour_groups'])
                ? wp_unslash($_POST['moga_tour_groups'])
                : array();

            $groups         = array();
            $skipped_count  = 0;

            foreach ($groups_input as $row) {
                $start = isset($row['start']) ? sanitize_text_field($row['start']) : '';

                // A row with no start date at all is an empty/unused
                // row (e.g. "Add Group" clicked but never filled in) —
                // skip it silently, nothing meaningful was entered.
                if (! $start) {
                    continue;
                }

                $capacity = isset($row['capacity']) ? absint($row['capacity']) : 0;

                // A start date WAS entered but capacity is missing or
                // zero — this group can never actually be booked, so
                // it's dropped rather than silently saved as a
                // trap a guest could click into and find nothing
                // bookable. Counted so the organizer is told, not left
                // wondering why a group they filled in disappeared.
                if ($capacity < 1) {
                    $skipped_count++;
                    continue;
                }

                $groups[] = array(
                    'start'             => $start,
                    'price_adult'       => isset($row['price_adult']) ? floatval($row['price_adult']) : 0,
                    'price_child'       => isset($row['price_child']) ? floatval($row['price_child']) : 0,
                    'price_infant'      => isset($row['price_infant']) ? floatval($row['price_infant']) : 0,
                    'capacity'          => $capacity,
                    'min_participants'  => isset($row['min_participants']) ? max(1, absint($row['min_participants'])) : 1,
                    'accommodation'     => self::sanitize_accommodation($row['accommodation'] ?? array()),
                );
            }

            // Sort by start date ascending — matches the natural
            // "soonest departure first" order a guest would expect on
            // the frontend, regardless of the order rows were entered
            // or reordered in the admin.
            usort($groups, function ($a, $b) {
                return strcmp($a['start'], $b['start']);
            });

            update_post_meta($post_id, '_moga_tour_groups', wp_json_encode($groups));

            if ($skipped_count > 0) {
                set_transient(
                    'moga_tour_groups_skipped_' . $post_id . '_' . get_current_user_id(),
                    $skipped_count,
                    45
                );
            }
        }

        // ---- Schedule ----
        if (
            isset($_POST['moga_tour_schedule_nonce'])
            && wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['moga_tour_schedule_nonce'])),
                'moga_tour_schedule_nonce'
            )
        ) {
            update_post_meta(
                $post_id,
                '_moga_duration_days',
                isset($_POST['moga_duration_days']) ? absint($_POST['moga_duration_days']) : 1
            );
            update_post_meta(
                $post_id,
                '_moga_duration_nights',
                isset($_POST['moga_duration_nights']) ? absint($_POST['moga_duration_nights']) : 0
            );
            update_post_meta(
                $post_id,
                '_moga_departure_time',
                isset($_POST['moga_departure_time']) ? sanitize_text_field(wp_unslash($_POST['moga_departure_time'])) : '08:00'
            );
            update_post_meta(
                $post_id,
                '_moga_return_time',
                isset($_POST['moga_return_time']) ? sanitize_text_field(wp_unslash($_POST['moga_return_time'])) : '18:00'
            );

            $weekend_days = isset($_POST['moga_weekend_days']) && is_array($_POST['moga_weekend_days'])
                ? array_map('absint', $_POST['moga_weekend_days'])
                : array();
            update_post_meta($post_id, '_moga_weekend_days', wp_json_encode($weekend_days));
        }

        // ---- Location ----
        if (
            isset($_POST['moga_tour_location_nonce'])
            && wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['moga_tour_location_nonce'])),
                'moga_tour_location_nonce'
            )
        ) {
            $dep_country     = isset($_POST['moga_departure_country'])     ? sanitize_text_field(wp_unslash($_POST['moga_departure_country'])) : '';
            $dep_province    = isset($_POST['moga_departure_province'])    ? sanitize_text_field(wp_unslash($_POST['moga_departure_province'])) : '';
            $dep_province_id = isset($_POST['moga_departure_province_id']) ? absint($_POST['moga_departure_province_id']) : 0;
            $dep_city        = isset($_POST['moga_departure_city'])        ? sanitize_text_field(wp_unslash($_POST['moga_departure_city'])) : '';
            $dep_city_id     = isset($_POST['moga_departure_city_id'])     ? absint($_POST['moga_departure_city_id']) : 0;
            $dep_district    = isset($_POST['moga_departure_district'])    ? sanitize_text_field(wp_unslash($_POST['moga_departure_district'])) : '';

            $dest_country     = isset($_POST['moga_destination_country'])     ? sanitize_text_field(wp_unslash($_POST['moga_destination_country'])) : '';
            $dest_province    = isset($_POST['moga_destination_province'])    ? sanitize_text_field(wp_unslash($_POST['moga_destination_province'])) : '';
            $dest_province_id = isset($_POST['moga_destination_province_id']) ? absint($_POST['moga_destination_province_id']) : 0;
            $dest_city        = isset($_POST['moga_destination_city'])        ? sanitize_text_field(wp_unslash($_POST['moga_destination_city'])) : '';
            $dest_city_id     = isset($_POST['moga_destination_city_id'])     ? absint($_POST['moga_destination_city_id']) : 0;
            $dest_district    = isset($_POST['moga_destination_district'])    ? sanitize_text_field(wp_unslash($_POST['moga_destination_district'])) : '';

            update_post_meta($post_id, '_moga_departure_country',      $dep_country);
            update_post_meta($post_id, '_moga_departure_province',     $dep_province);
            update_post_meta($post_id, '_moga_departure_province_id',  $dep_province_id);
            update_post_meta($post_id, '_moga_departure_city',         $dep_city);
            update_post_meta($post_id, '_moga_departure_city_id',      $dep_city_id);
            update_post_meta($post_id, '_moga_departure_district',     $dep_district);
            update_post_meta($post_id, '_moga_destination_country',    $dest_country);
            update_post_meta($post_id, '_moga_destination_province',   $dest_province);
            update_post_meta($post_id, '_moga_destination_province_id', $dest_province_id);
            update_post_meta($post_id, '_moga_destination_city',       $dest_city);
            update_post_meta($post_id, '_moga_destination_city_id',    $dest_city_id);
            update_post_meta($post_id, '_moga_destination_district',   $dest_district);
            update_post_meta(
                $post_id,
                '_moga_departure_point',
                isset($_POST['moga_departure_point']) ? sanitize_text_field(wp_unslash($_POST['moga_departure_point'])) : ''
            );

            // Sync departure location to taxonomy (replaces existing terms).
            if ($dep_country && $dep_province && $dep_city) {
                $dep_country_data = moga_get_country($dep_country);
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
            if ($dest_country && $dest_province && $dest_city) {
                $dest_country_data = moga_get_country($dest_country);
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
        if (
            isset($_POST['moga_tour_contact_nonce'])
            && wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['moga_tour_contact_nonce'])),
                'moga_tour_contact_nonce'
            )
        ) {
            update_post_meta(
                $post_id,
                '_moga_organizer_name',
                isset($_POST['moga_organizer_name']) ? sanitize_text_field(wp_unslash($_POST['moga_organizer_name'])) : ''
            );
            update_post_meta(
                $post_id,
                '_moga_organizer_photo',
                isset($_POST['moga_organizer_photo']) ? absint($_POST['moga_organizer_photo']) : 0
            );
            update_post_meta(
                $post_id,
                '_moga_phone',
                isset($_POST['moga_phone']) ? moga_sanitize_phone(wp_unslash($_POST['moga_phone'])) : ''
            );
            update_post_meta(
                $post_id,
                '_moga_whatsapp',
                isset($_POST['moga_whatsapp']) ? moga_sanitize_phone(wp_unslash($_POST['moga_whatsapp'])) : ''
            );
            update_post_meta(
                $post_id,
                '_moga_email',
                isset($_POST['moga_email']) ? sanitize_email(wp_unslash($_POST['moga_email'])) : ''
            );
        }

        // ---- Details ----
        if (
            isset($_POST['moga_tour_details_nonce'])
            && wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['moga_tour_details_nonce'])),
                'moga_tour_details_nonce'
            )
        ) {
            update_post_meta(
                $post_id,
                '_moga_difficulty',
                isset($_POST['moga_difficulty']) ? sanitize_text_field(wp_unslash($_POST['moga_difficulty'])) : 'easy'
            );
            update_post_meta(
                $post_id,
                '_moga_tour_type',
                isset($_POST['moga_tour_type']) ? sanitize_text_field(wp_unslash($_POST['moga_tour_type'])) : 'group'
            );
            update_post_meta(
                $post_id,
                '_moga_language',
                isset($_POST['moga_language']) ? sanitize_text_field(wp_unslash($_POST['moga_language'])) : 'Arabic'
            );
            update_post_meta(
                $post_id,
                '_moga_guide_included',
                isset($_POST['moga_guide_included']) ? '1' : '0'
            );
        }

        // ---- Itinerary ----
        // Day numbers are derived from array position, not stored per-row,
        // so re-ordering rows in the admin always produces clean 1..N days
        // with no possibility of duplicate or gapped day numbers.
        if (
            isset($_POST['moga_tour_itinerary_nonce'])
            && wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['moga_tour_itinerary_nonce'])),
                'moga_tour_itinerary_nonce'
            )
        ) {
            $itinerary_input = isset($_POST['moga_itinerary']) && is_array($_POST['moga_itinerary'])
                ? wp_unslash($_POST['moga_itinerary'])
                : array();

            $valid_meals = array('breakfast', 'lunch', 'dinner');
            $itinerary   = array();
            $day_number  = 0;

            foreach ($itinerary_input as $row) {

                $title = isset($row['title']) ? sanitize_text_field($row['title']) : '';

                // Skip rows with no title — an empty row added and left blank.
                if ('' === $title) {
                    continue;
                }

                $day_number++;

                $meals = array();
                if (! empty($row['meals']) && is_array($row['meals'])) {
                    $meals = array_values(array_intersect(array_map('sanitize_text_field', $row['meals']), $valid_meals));
                }

                $activities = array();
                if (! empty($row['activities'])) {
                    $lines      = preg_split('/\r\n|\r|\n/', $row['activities']);
                    $activities = array_values(array_filter(array_map(function ($line) {
                        return sanitize_text_field(trim($line));
                    }, $lines)));
                }

                $itinerary[] = array(
                    'day'           => $day_number,
                    'title'         => $title,
                    'location'      => isset($row['location'])      ? sanitize_text_field($row['location'])      : '',
                    'duration'      => isset($row['duration'])      ? sanitize_text_field($row['duration'])      : '',
                    'description'   => isset($row['description'])   ? sanitize_textarea_field($row['description']) : '',
                    'meals'         => $meals,
                    'accommodation' => isset($row['accommodation']) ? sanitize_text_field($row['accommodation']) : '',
                    'activities'    => $activities,
                );
            }

            update_post_meta($post_id, '_moga_itinerary', wp_json_encode($itinerary));
        }

        // ---- Includes / Excludes ----
        if (
            isset($_POST['moga_tour_includes_nonce'])
            && wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['moga_tour_includes_nonce'])),
                'moga_tour_includes_nonce'
            )
        ) {
            $includes = isset($_POST['moga_includes']) && is_array($_POST['moga_includes'])
                ? array_map('sanitize_text_field', wp_unslash($_POST['moga_includes']))
                : array();
            $excludes = isset($_POST['moga_excludes']) && is_array($_POST['moga_excludes'])
                ? array_map('sanitize_text_field', wp_unslash($_POST['moga_excludes']))
                : array();
            update_post_meta($post_id, '_moga_includes', wp_json_encode($includes));
            update_post_meta($post_id, '_moga_excludes', wp_json_encode($excludes));

            // Custom (free-text) items — beyond the predefined checkbox list.
            $includes_custom = isset($_POST['moga_includes_custom']) && is_array($_POST['moga_includes_custom'])
                ? array_map('sanitize_text_field', wp_unslash($_POST['moga_includes_custom']))
                : array();
            $excludes_custom = isset($_POST['moga_excludes_custom']) && is_array($_POST['moga_excludes_custom'])
                ? array_map('sanitize_text_field', wp_unslash($_POST['moga_excludes_custom']))
                : array();
            $includes_custom = array_values(array_filter($includes_custom));
            $excludes_custom = array_values(array_filter($excludes_custom));
            update_post_meta($post_id, '_moga_includes_custom', wp_json_encode($includes_custom));
            update_post_meta($post_id, '_moga_excludes_custom', wp_json_encode($excludes_custom));
        }

        // ---- Bus & Seats (embedded bus builder) ----
        // Creates or updates a dedicated bus post linked to this tour.
        // The organizer fills in all bus details directly in the Tour
        // editor — no separate Buses screen needed.
        if (
            isset($_POST['moga_tour_bus_nonce'])
            && wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['moga_tour_bus_nonce'])),
                'moga_tour_bus_nonce'
            )
        ) {
            $existing_bus_id = isset($_POST['moga_bus_id']) ? absint($_POST['moga_bus_id']) : 0;
            $bus_title       = isset($_POST['moga_tour_bus_title']) ? sanitize_text_field(wp_unslash($_POST['moga_tour_bus_title'])) : '';

            // Only create/update when a bus name has been entered.
            if (! empty($bus_title)) {

                $layout  = isset($_POST['moga_tour_seat_layout']) ? sanitize_key($_POST['moga_tour_seat_layout']) : '2+2';
                $rows    = isset($_POST['moga_tour_seat_rows'])   ? max(1, absint($_POST['moga_tour_seat_rows'])) : 10;
                $layouts = class_exists('Moga_CPT_Bus') ? Moga_CPT_Bus::get_seat_layouts() : array();

                if (! array_key_exists($layout, $layouts)) {
                    $layout = '2+2';
                }

                $columns     = isset($layouts[$layout]['columns']) ? $layouts[$layout]['columns'] : 4;
                $total_seats = $rows * $columns;

                if ($existing_bus_id && get_post_type($existing_bus_id) === 'moga_bus') {
                    // Update the existing linked bus post.
                    wp_update_post(array(
                        'ID'         => $existing_bus_id,
                        'post_title' => $bus_title,
                    ));
                    $bus_id = $existing_bus_id;
                } else {
                    // Create a new bus post owned by this tour's author.
                    $bus_id = wp_insert_post(array(
                        'post_title'  => $bus_title,
                        'post_type'   => 'moga_bus',
                        'post_status' => 'publish',
                        'post_author' => $post->post_author,
                    ));
                }

                if ($bus_id && ! is_wp_error($bus_id)) {
                    // Save all bus meta from the embedded form.
                    $bus_text_fields = array(
                        'moga_tour_bus_plate'   => '_moga_bus_plate',
                        'moga_tour_bus_model'   => '_moga_bus_model',
                        'moga_tour_bus_color'   => '_moga_bus_color',
                        'moga_tour_bus_type'    => '_moga_bus_type',
                        'moga_tour_driver_name'    => '_moga_driver_name',
                        'moga_tour_driver_phone'   => '_moga_driver_phone',
                        'moga_tour_driver_license' => '_moga_driver_license',
                        'moga_tour_driver_seat'    => '_moga_driver_seat',
                    );
                    foreach ($bus_text_fields as $post_key => $meta_key) {
                        update_post_meta(
                            $bus_id,
                            $meta_key,
                            isset($_POST[$post_key]) ? sanitize_text_field(wp_unslash($_POST[$post_key])) : ''
                        );
                    }

                    if (isset($_POST['moga_tour_bus_year'])) {
                        update_post_meta($bus_id, '_moga_bus_year', absint($_POST['moga_tour_bus_year']));
                    }

                    update_post_meta($bus_id, '_moga_seat_layout',  $layout);
                    update_post_meta($bus_id, '_moga_seat_rows',    $rows);
                    update_post_meta($bus_id, '_moga_seat_columns', $columns);
                    update_post_meta($bus_id, '_moga_total_seats',  $total_seats);

                    $vip_raw = isset($_POST['moga_tour_vip_seats'])      ? sanitize_text_field(wp_unslash($_POST['moga_tour_vip_seats']))      : '';
                    $dis_raw = isset($_POST['moga_tour_disabled_seats'])  ? sanitize_text_field(wp_unslash($_POST['moga_tour_disabled_seats'])) : '';

                    update_post_meta($bus_id, '_moga_vip_seats',      wp_json_encode(array_values(array_filter(array_map('trim', explode(',', $vip_raw))))));
                    update_post_meta($bus_id, '_moga_disabled_seats', wp_json_encode(array_values(array_filter(array_map('trim', explode(',', $dis_raw))))));

                    $amenity_fields = array(
                        'moga_tour_has_ac'         => '_moga_has_ac',
                        'moga_tour_has_wifi'       => '_moga_has_wifi',
                        'moga_tour_has_tv'         => '_moga_has_tv',
                        'moga_tour_has_usb'        => '_moga_has_usb',
                        'moga_tour_has_toilet'     => '_moga_has_toilet',
                        'moga_tour_has_reclining'  => '_moga_has_reclining',
                        'moga_tour_has_luggage'    => '_moga_has_luggage',
                        'moga_tour_has_wheelchair' => '_moga_has_wheelchair',
                    );
                    foreach ($amenity_fields as $post_key => $meta_key) {
                        update_post_meta($bus_id, $meta_key, isset($_POST[$post_key]) ? '1' : '0');
                    }

                    update_post_meta($bus_id, '_moga_active',            '1');
                    update_post_meta($bus_id, '_moga_under_maintenance',  '0');

                    // Link the bus to this tour.
                    update_post_meta($post_id, '_moga_bus_id',    $bus_id);
                    update_post_meta($post_id, '_moga_seats_total', $total_seats);
                }
            } elseif (empty($bus_title) && $existing_bus_id) {
                // Bus name cleared — unlink (do not delete the bus post,
                // it may have booking history).
                update_post_meta($post_id, '_moga_bus_id', 0);
                update_post_meta($post_id, '_moga_seats_total', 0);
            }
        }

        // ---- Status ----
        if (
            isset($_POST['moga_tour_status_nonce'])
            && wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['moga_tour_status_nonce'])),
                'moga_tour_status_nonce'
            )
        ) {
            update_post_meta(
                $post_id,
                '_moga_active',
                isset($_POST['moga_active']) ? '1' : '0'
            );
            update_post_meta(
                $post_id,
                '_moga_featured',
                isset($_POST['moga_featured']) ? '1' : '0'
            );
            update_post_meta(
                $post_id,
                '_moga_instant_booking',
                isset($_POST['moga_instant_booking']) ? '1' : '0'
            );
        }

        // ---- Bus Details ----
        if (
            isset($_POST['moga_bus_details_nonce'])
            && wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['moga_bus_details_nonce'])),
                'moga_bus_details_nonce'
            )
            && 'moga_bus' === get_post_type($post_id)
        ) {
            $bus_text_fields = array(
                'moga_bus_plate'   => '_moga_bus_plate',
                'moga_bus_model'   => '_moga_bus_model',
                'moga_bus_color'   => '_moga_bus_color',
                'moga_bus_type'    => '_moga_bus_type',
                'moga_driver_name' => '_moga_driver_name',
                'moga_driver_phone'   => '_moga_driver_phone',
                'moga_driver_license' => '_moga_driver_license',
            );
            foreach ($bus_text_fields as $post_key => $meta_key) {
                update_post_meta(
                    $post_id,
                    $meta_key,
                    isset($_POST[$post_key]) ? sanitize_text_field(wp_unslash($_POST[$post_key])) : ''
                );
            }

            $bus_int_fields = array(
                'moga_bus_year' => '_moga_bus_year',
            );
            foreach ($bus_int_fields as $post_key => $meta_key) {
                update_post_meta(
                    $post_id,
                    $meta_key,
                    isset($_POST[$post_key]) ? absint($_POST[$post_key]) : 0
                );
            }

            $bus_toggle_fields = array(
                'moga_has_ac'          => '_moga_has_ac',
                'moga_has_wifi'        => '_moga_has_wifi',
                'moga_has_tv'          => '_moga_has_tv',
                'moga_has_usb'         => '_moga_has_usb',
                'moga_has_toilet'      => '_moga_has_toilet',
                'moga_has_reclining'   => '_moga_has_reclining',
                'moga_has_luggage'     => '_moga_has_luggage',
                'moga_has_wheelchair'  => '_moga_has_wheelchair',
                'moga_active'          => '_moga_active',
                'moga_under_maintenance' => '_moga_under_maintenance',
            );
            foreach ($bus_toggle_fields as $post_key => $meta_key) {
                update_post_meta(
                    $post_id,
                    $meta_key,
                    isset($_POST[$post_key]) ? '1' : '0'
                );
            }
        }

        // ---- Bus Seat Layout ----
        if (
            isset($_POST['moga_bus_seats_nonce'])
            && wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['moga_bus_seats_nonce'])),
                'moga_bus_seats_nonce'
            )
            && 'moga_bus' === get_post_type($post_id)
        ) {
            $layout = isset($_POST['moga_seat_layout'])
                ? sanitize_key($_POST['moga_seat_layout'])
                : '2+2';

            $layouts        = class_exists('Moga_CPT_Bus') ? Moga_CPT_Bus::get_seat_layouts() : array();
            $valid_layouts  = array_keys($layouts);
            if (! in_array($layout, $valid_layouts, true)) {
                $layout = '2+2';
            }

            $rows = isset($_POST['moga_seat_rows']) ? max(1, absint($_POST['moga_seat_rows'])) : 10;

            // Auto-calculate total seats from rows × columns.
            $columns     = isset($layouts[$layout]['columns']) ? $layouts[$layout]['columns'] : 4;
            $total_seats = $rows * $columns;

            update_post_meta($post_id, '_moga_seat_layout',   $layout);
            update_post_meta($post_id, '_moga_seat_rows',     $rows);
            update_post_meta($post_id, '_moga_seat_columns',  $columns);
            update_post_meta($post_id, '_moga_total_seats',   $total_seats);

            $driver_seat = isset($_POST['moga_driver_seat'])
                ? sanitize_key($_POST['moga_driver_seat'])
                : 'front-left';
            update_post_meta($post_id, '_moga_driver_seat', $driver_seat);

            // VIP and disabled seats — comma-separated list from JS,
            // stored as a JSON array for clean retrieval elsewhere.
            $vip_raw  = isset($_POST['moga_vip_seats'])      ? sanitize_text_field(wp_unslash($_POST['moga_vip_seats']))      : '';
            $dis_raw  = isset($_POST['moga_disabled_seats'])  ? sanitize_text_field(wp_unslash($_POST['moga_disabled_seats'])) : '';

            $vip_seats = array_values(array_filter(array_map('trim', explode(',', $vip_raw))));
            $dis_seats = array_values(array_filter(array_map('trim', explode(',', $dis_raw))));

            update_post_meta($post_id, '_moga_vip_seats',      wp_json_encode($vip_seats));
            update_post_meta($post_id, '_moga_disabled_seats', wp_json_encode($dis_seats));
        }
    }



    // ============================================================
    // BUS META BOXES — RENDER
    // ============================================================

    /**
     * Render the Bus Details meta box.
     * Covers identity, amenities, driver info, and active status.
     *
     * @since  1.0.0
     * @param  WP_Post $post Current post object.
     * @return void
     */
    public static function render_bus_details($post)
    {
        wp_nonce_field('moga_bus_details_nonce', 'moga_bus_details_nonce');

        $plate    = get_post_meta($post->ID, '_moga_bus_plate',   true);
        $model    = get_post_meta($post->ID, '_moga_bus_model',   true);
        $year     = get_post_meta($post->ID, '_moga_bus_year',    true);
        $color    = get_post_meta($post->ID, '_moga_bus_color',   true);
        $bus_type = get_post_meta($post->ID, '_moga_bus_type',    true) ?: 'standard';

        $driver_name    = get_post_meta($post->ID, '_moga_driver_name',    true);
        $driver_phone   = get_post_meta($post->ID, '_moga_driver_phone',   true);
        $driver_license = get_post_meta($post->ID, '_moga_driver_license', true);

        $has_ac         = get_post_meta($post->ID, '_moga_has_ac',         true);
        $has_wifi       = get_post_meta($post->ID, '_moga_has_wifi',       true);
        $has_tv         = get_post_meta($post->ID, '_moga_has_tv',         true);
        $has_usb        = get_post_meta($post->ID, '_moga_has_usb',        true);
        $has_toilet     = get_post_meta($post->ID, '_moga_has_toilet',     true);
        $has_reclining  = get_post_meta($post->ID, '_moga_has_reclining',  true);
        $has_luggage    = get_post_meta($post->ID, '_moga_has_luggage',    true);
        $has_wheelchair = get_post_meta($post->ID, '_moga_has_wheelchair', true);
        $active         = get_post_meta($post->ID, '_moga_active',         true);
        $maintenance    = get_post_meta($post->ID, '_moga_under_maintenance', true);

        $bus_types = class_exists('Moga_CPT_Bus') ? Moga_CPT_Bus::get_bus_types() : array();

        // Default active to 1 for new buses.
        if ('' === $active && ! $post->ID) {
            $active = '1';
        }
    ?>
        <div class="moga-metabox">

            <?php // ---- Identity ---- ?>
            <div class="moga-metabox__section-title"><?php esc_html_e('Bus Identity', 'moga-travel-core'); ?></div>

            <div class="moga-metabox__row">
                <div class="moga-metabox__field">
                    <label for="moga_bus_plate"><?php esc_html_e('Plate Number', 'moga-travel-core'); ?></label>
                    <input type="text" id="moga_bus_plate" name="moga_bus_plate"
                        value="<?php echo esc_attr($plate); ?>"
                        placeholder="<?php esc_attr_e('e.g. ABC-1234', 'moga-travel-core'); ?>">
                </div>
                <div class="moga-metabox__field">
                    <label for="moga_bus_model"><?php esc_html_e('Make & Model', 'moga-travel-core'); ?></label>
                    <input type="text" id="moga_bus_model" name="moga_bus_model"
                        value="<?php echo esc_attr($model); ?>"
                        placeholder="<?php esc_attr_e('e.g. Mercedes Sprinter', 'moga-travel-core'); ?>">
                </div>
            </div>

            <div class="moga-metabox__row">
                <div class="moga-metabox__field">
                    <label for="moga_bus_year"><?php esc_html_e('Year', 'moga-travel-core'); ?></label>
                    <input type="number" id="moga_bus_year" name="moga_bus_year"
                        value="<?php echo esc_attr($year); ?>"
                        min="1990" max="<?php echo esc_attr(date('Y') + 1); ?>"
                        placeholder="<?php echo esc_attr(date('Y')); ?>">
                </div>
                <div class="moga-metabox__field">
                    <label for="moga_bus_color"><?php esc_html_e('Color', 'moga-travel-core'); ?></label>
                    <input type="text" id="moga_bus_color" name="moga_bus_color"
                        value="<?php echo esc_attr($color); ?>"
                        placeholder="<?php esc_attr_e('e.g. White', 'moga-travel-core'); ?>">
                </div>
            </div>

            <div class="moga-metabox__row">
                <div class="moga-metabox__field">
                    <label for="moga_bus_type"><?php esc_html_e('Bus Type', 'moga-travel-core'); ?></label>
                    <select id="moga_bus_type" name="moga_bus_type">
                        <?php foreach ($bus_types as $key => $type) : ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($bus_type, $key); ?>>
                                <?php echo esc_html($type['label']); ?>
                                <?php if (! empty($type['capacity'])) : ?>
                                    — <?php echo esc_html($type['capacity']); ?>
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <?php // ---- Amenities ---- ?>
            <div class="moga-metabox__section-title" style="margin-top:20px;">
                <?php esc_html_e('Onboard Amenities', 'moga-travel-core'); ?>
            </div>

            <div class="moga-metabox__row moga-metabox__row--full">
                <div class="moga-amenity-pills">
                    <?php
                    $amenities = array(
                        'moga_has_ac'         => array('label' => __('Air Conditioning', 'moga-travel-core'), 'default' => '1', 'val' => $has_ac),
                        'moga_has_wifi'       => array('label' => __('WiFi',             'moga-travel-core'), 'default' => '0', 'val' => $has_wifi),
                        'moga_has_tv'         => array('label' => __('TV Screens',       'moga-travel-core'), 'default' => '0', 'val' => $has_tv),
                        'moga_has_usb'        => array('label' => __('USB Charging',     'moga-travel-core'), 'default' => '0', 'val' => $has_usb),
                        'moga_has_toilet'     => array('label' => __('Onboard Toilet',   'moga-travel-core'), 'default' => '0', 'val' => $has_toilet),
                        'moga_has_reclining'  => array('label' => __('Reclining Seats',  'moga-travel-core'), 'default' => '0', 'val' => $has_reclining),
                        'moga_has_luggage'    => array('label' => __('Luggage Space',    'moga-travel-core'), 'default' => '1', 'val' => $has_luggage),
                        'moga_has_wheelchair' => array('label' => __('Wheelchair Access','moga-travel-core'), 'default' => '0', 'val' => $has_wheelchair),
                    );
                    foreach ($amenities as $key => $amenity) :
                        $checked = ('' === $amenity['val']) ? $amenity['default'] : $amenity['val'];
                    ?>
                        <label class="moga-amenity-pill <?php echo '1' === $checked ? 'moga-amenity-pill--checked' : ''; ?>">
                            <input type="checkbox" name="<?php echo esc_attr($key); ?>" value="1"
                                <?php checked('1', $checked); ?>>
                            <?php echo esc_html($amenity['label']); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php // ---- Driver Info ---- ?>
            <div class="moga-metabox__section-title" style="margin-top:20px;">
                <?php esc_html_e('Driver Information', 'moga-travel-core'); ?>
            </div>

            <div class="moga-metabox__row">
                <div class="moga-metabox__field">
                    <label for="moga_driver_name"><?php esc_html_e('Driver Name', 'moga-travel-core'); ?></label>
                    <input type="text" id="moga_driver_name" name="moga_driver_name"
                        value="<?php echo esc_attr($driver_name); ?>"
                        placeholder="<?php esc_attr_e('Full name', 'moga-travel-core'); ?>">
                </div>
                <div class="moga-metabox__field">
                    <label for="moga_driver_license"><?php esc_html_e('License Number', 'moga-travel-core'); ?></label>
                    <input type="text" id="moga_driver_license" name="moga_driver_license"
                        value="<?php echo esc_attr($driver_license); ?>"
                        placeholder="<?php esc_attr_e('e.g. DL-123456', 'moga-travel-core'); ?>">
                </div>
            </div>

            <div class="moga-metabox__row">
                <div class="moga-metabox__field">
                    <label for="moga_driver_phone"><?php esc_html_e('Driver Phone', 'moga-travel-core'); ?></label>
                    <input type="tel" id="moga_driver_phone" name="moga_driver_phone"
                        value="<?php echo esc_attr($driver_phone); ?>"
                        placeholder="+20 10 XXXX XXXX"
                        class="moga-phone-field">
                    <p class="moga-metabox__hint">
                        <?php esc_html_e('For internal use — not shown to guests.', 'moga-travel-core'); ?>
                    </p>
                </div>
            </div>

            <?php // ---- Status ---- ?>
            <div class="moga-metabox__section-title" style="margin-top:20px;">
                <?php esc_html_e('Status', 'moga-travel-core'); ?>
            </div>

            <div class="moga-metabox__row">
                <div class="moga-metabox__field">
                    <label class="moga-toggle">
                        <input type="checkbox" name="moga_active" value="1" <?php checked('1', $active); ?>>
                        <span class="moga-toggle__switch"></span>
                        <span class="moga-toggle__label"><?php esc_html_e('Bus is Active', 'moga-travel-core'); ?></span>
                    </label>
                    <p class="moga-metabox__hint">
                        <?php esc_html_e('Inactive buses cannot be assigned to tours.', 'moga-travel-core'); ?>
                    </p>
                </div>
                <div class="moga-metabox__field">
                    <label class="moga-toggle">
                        <input type="checkbox" name="moga_under_maintenance" value="1" <?php checked('1', $maintenance); ?>>
                        <span class="moga-toggle__switch"></span>
                        <span class="moga-toggle__label"><?php esc_html_e('Under Maintenance', 'moga-travel-core'); ?></span>
                    </label>
                    <p class="moga-metabox__hint">
                        <?php esc_html_e('Temporarily removes this bus from the available fleet.', 'moga-travel-core'); ?>
                    </p>
                </div>
            </div>

        </div>
    <?php
    }

    /**
     * Render the Bus Seat Layout meta box.
     * Covers layout type, row count, driver position,
     * VIP seats, and disabled/unavailable seats.
     *
     * @since  1.0.0
     * @param  WP_Post $post Current post object.
     * @return void
     */
    public static function render_bus_seats($post)
    {
        wp_nonce_field('moga_bus_seats_nonce', 'moga_bus_seats_nonce');

        $layout       = get_post_meta($post->ID, '_moga_seat_layout',  true) ?: '2+2';
        $rows         = get_post_meta($post->ID, '_moga_seat_rows',    true) ?: 10;
        $total_seats  = get_post_meta($post->ID, '_moga_total_seats',  true) ?: 0;
        $driver_seat  = get_post_meta($post->ID, '_moga_driver_seat',  true) ?: 'front-left';

        $vip_json      = get_post_meta($post->ID, '_moga_vip_seats',      true);
        $disabled_json = get_post_meta($post->ID, '_moga_disabled_seats', true);
        $vip_seats     = $vip_json      ? implode(', ', json_decode($vip_json, true) ?: array()) : '';
        $disabled_seats = $disabled_json ? implode(', ', json_decode($disabled_json, true) ?: array()) : '';

        $layouts          = class_exists('Moga_CPT_Bus') ? Moga_CPT_Bus::get_seat_layouts() : array();
        $driver_positions = class_exists('Moga_CPT_Bus') ? Moga_CPT_Bus::get_driver_positions() : array();
    ?>
        <div class="moga-metabox">

            <div class="moga-metabox__row">
                <div class="moga-metabox__field">
                    <label for="moga_seat_layout"><?php esc_html_e('Seat Layout', 'moga-travel-core'); ?></label>
                    <select id="moga_seat_layout" name="moga_seat_layout">
                        <?php foreach ($layouts as $key => $ldata) : ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($layout, $key); ?>>
                                <?php echo esc_html($ldata['label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="moga-metabox__hint" id="moga-layout-desc">
                        <?php echo isset($layouts[$layout]['desc']) ? esc_html($layouts[$layout]['desc']) : ''; ?>
                    </p>
                </div>
                <div class="moga-metabox__field">
                    <label for="moga_seat_rows"><?php esc_html_e('Number of Rows', 'moga-travel-core'); ?></label>
                    <input type="number" id="moga_seat_rows" name="moga_seat_rows"
                        value="<?php echo esc_attr($rows); ?>"
                        min="1" max="30">
                    <p class="moga-metabox__hint">
                        <?php esc_html_e('Total seats:', 'moga-travel-core'); ?>
                        <strong id="moga-total-seats-display">
                            <?php echo esc_html($total_seats ?: ($rows * (isset($layouts[$layout]['columns']) ? $layouts[$layout]['columns'] : 4))); ?>
                        </strong>
                    </p>
                </div>
            </div>

            <div class="moga-metabox__row">
                <div class="moga-metabox__field">
                    <label for="moga_driver_seat"><?php esc_html_e('Driver Position', 'moga-travel-core'); ?></label>
                    <select id="moga_driver_seat" name="moga_driver_seat">
                        <?php foreach ($driver_positions as $key => $label) : ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($driver_seat, $key); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="moga-metabox__row moga-metabox__row--full" style="margin-top:16px;border-top:1px solid #e2e4e7;padding-top:16px;">

                <div class="moga-metabox__field">
                    <label for="moga_vip_seats">
                        <?php esc_html_e('VIP Seats', 'moga-travel-core'); ?>
                        <span class="moga-metabox__label-hint">
                            — <?php esc_html_e('optional', 'moga-travel-core'); ?>
                        </span>
                    </label>
                    <input type="text" id="moga_vip_seats" name="moga_vip_seats"
                        value="<?php echo esc_attr($vip_seats); ?>"
                        placeholder="<?php esc_attr_e('e.g. 1A, 1B, 1C, 1D', 'moga-travel-core'); ?>">
                    <p class="moga-metabox__hint">
                        <?php esc_html_e('Comma-separated seat numbers. VIP seats are highlighted on the seat map but can still be booked by any guest.', 'moga-travel-core'); ?>
                    </p>
                </div>

                <div class="moga-metabox__field">
                    <label for="moga_disabled_seats">
                        <?php esc_html_e('Unavailable Seats', 'moga-travel-core'); ?>
                        <span class="moga-metabox__label-hint">
                            — <?php esc_html_e('optional', 'moga-travel-core'); ?>
                        </span>
                    </label>
                    <input type="text" id="moga_disabled_seats" name="moga_disabled_seats"
                        value="<?php echo esc_attr($disabled_seats); ?>"
                        placeholder="<?php esc_attr_e('e.g. 5C, 5D', 'moga-travel-core'); ?>">
                    <p class="moga-metabox__hint">
                        <?php esc_html_e('Comma-separated. These seats will never be offered for booking — use for broken seats, guide seat, etc.', 'moga-travel-core'); ?>
                    </p>
                </div>

            </div>

            <?php // ---- Live seat map preview ---- ?>
            <div class="moga-metabox__row moga-metabox__row--full" style="margin-top:16px;">
                <div class="moga-metabox__field">
                    <label><?php esc_html_e('Seat Map Preview', 'moga-travel-core'); ?></label>
                    <p class="moga-metabox__hint">
                        <?php esc_html_e('Updates automatically when you change the layout or row count above. Green = available, gold = VIP, grey = unavailable.', 'moga-travel-core'); ?>
                    </p>
                    <div id="moga-bus-seat-preview" class="moga-bus-seat-preview">
                        <?php // Rendered by JS on page load and on layout/row change. ?>
                    </div>
                </div>
            </div>

        </div>
    <?php
    }


    /**
     * Sanitize the accommodation sub-repeater array from $_POST.
     * Called from the tour groups save handler.
     *
     * @since  1.0.0
     * @param  array $raw Raw accommodation rows from $_POST (may be empty).
     * @return array      Sanitized accommodation rows, empty rows dropped.
     */
    private static function sanitize_accommodation( $raw ) {
        if ( ! is_array( $raw ) || empty( $raw ) ) {
            return array();
        }

        $valid_board = array( 'room_only', 'breakfast', 'half_board', 'full_board', 'all_inclusive' );
        $result      = array();

        foreach ( $raw as $stay ) {
            $hotel_name = isset( $stay['hotel_name'] )
                ? sanitize_text_field( $stay['hotel_name'] )
                : '';

            // Drop rows with no hotel name.
            if ( '' === $hotel_name ) {
                continue;
            }

            $board = isset( $stay['board'] ) && in_array( $stay['board'], $valid_board, true )
                ? $stay['board']
                : 'breakfast';

            $result[] = array(
                'hotel_name'    => $hotel_name,
                'stars'         => isset( $stay['stars'] ) ? max( 1, min( 5, absint( $stay['stars'] ) ) ) : 4,
                'night_from'    => isset( $stay['night_from'] ) ? max( 1, absint( $stay['night_from'] ) ) : 1,
                'night_to'      => isset( $stay['night_to'] )   ? max( 1, absint( $stay['night_to'] ) )   : 1,
                'board'         => $board,
                'notes'         => isset( $stay['notes'] ) ? sanitize_text_field( $stay['notes'] ) : '',
                'place_id'      => isset( $stay['place_id'] ) ? sanitize_text_field( $stay['place_id'] ) : '',
                'google_name'   => isset( $stay['google_name'] ) ? sanitize_text_field( $stay['google_name'] ) : '',
                'google_rating' => isset( $stay['google_rating'] ) ? sanitize_text_field( $stay['google_rating'] ) : '',
                'photo_ids'     => isset( $stay['photo_ids'] ) && $stay['photo_ids'] !== ''
                    ? array_values( array_filter( array_map( 'absint', explode( ',', $stay['photo_ids'] ) ) ) )
                    : array(),
                // URL-based photos — one per line, sanitized individually.
                'photo_urls'    => isset( $stay['photo_urls'] ) && $stay['photo_urls'] !== ''
                    ? array_values( array_filter( array_map( function( $url ) {
                        $clean = esc_url_raw( trim( $url ) );
                        return ( strlen( $clean ) > 10 ) ? $clean : '';
                    }, preg_split( '/[\r\n]+/', $stay['photo_urls'] ) ) ) )
                    : array(),
            );
        }

        return $result;
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
    private static function get_provinces_for_render($country_code)
    {
        if (empty($country_code)) {
            return array();
        }
        global $wpdb;
        $prefix     = $wpdb->prefix . MOGA_CORE_DB_PREFIX;
        $country_id = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$prefix}loc_countries WHERE iso_code = %s LIMIT 1",
            strtoupper($country_code)
        ));
        if (! $country_id) {
            return array();
        }
        return $wpdb->get_results($wpdb->prepare(
            "SELECT id, name FROM {$prefix}loc_provinces WHERE country_id = %d ORDER BY name ASC",
            $country_id
        ), ARRAY_A) ?: array();
    }

    /**
     * Get cities for initial meta box render.
     * Queries mg_moga_loc_cities by province DB id.
     *
     * @since  1.0.0
     * @param  int $province_id Province DB id.
     * @return array [{id, name}] or empty array.
     */
    private static function get_cities_for_province_render($province_id)
    {
        if (! $province_id) {
            return array();
        }
        global $wpdb;
        $prefix = $wpdb->prefix . MOGA_CORE_DB_PREFIX;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT id, name FROM {$prefix}loc_cities WHERE province_id = %d ORDER BY name ASC",
            (int) $province_id
        ), ARRAY_A) ?: array();
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
    public static function meta_box_scripts()
    {

        $screen = get_current_screen();

        if (! $screen || ! in_array(
            $screen->post_type,
            array('moga_property', 'moga_tour', 'moga_destination', 'moga_bus'),
            true
        )) {
            return;
        }

        $max_gallery = self::MAX_GALLERY_IMAGES;
        $max_uploads = self::MAX_VIDEO_UPLOADS;

        $admin_data = array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('moga_nonce'),
            'i18n'    => array(
                'selectCountryFirst'  => __('— Select Country First —',   'moga-travel-core'),
                'selectProvinceFirst' => __('— Select Province First —',  'moga-travel-core'),
                'selectProvince'      => __('— Select Province —',        'moga-travel-core'),
                'selectCity'          => __('— Select City —',            'moga-travel-core'),
                'selectDistrict'      => __('— Select District —',        'moga-travel-core'),
                'loadingProvinces'    => __('Loading provinces…',         'moga-travel-core'),
                'loadingCities'       => __('Loading cities…',            'moga-travel-core'),
                'loadingDistricts'    => __('Loading districts…',         'moga-travel-core'),
                'districtLabel'       => __('District / Area',            'moga-travel-core'),
                'orTypeManually'      => __('Or type manually:',          'moga-travel-core'),
                'typeDistrict'        => __('e.g. Downtown, Zamalek',     'moga-travel-core'),
            ),
        );
    ?>
        <style>
        /* Google Places badge in accommodation row header */
        .moga-accommodation-row__google-badge {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            font-size: 10px;
            font-weight: 700;
            color: #fff;
            background: #4285f4;
            padding: 2px 6px;
            border-radius: 3px;
            margin-left: 6px;
            vertical-align: middle;
        }
        .moga-accommodation-row__google-rating-display {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-top: 4px;
            font-size: 13px;
            color: #3c434a;
            min-height: 32px;
        }

        /* Hotel autocomplete dropdown — Tour editor accommodation row */
        .moga-hotel-suggestions {
            position: absolute;
            top: calc(100% + 2px);
            left: 0;
            right: 0;
            background: #fff;
            border: 1.5px solid #2271b1;
            border-radius: 4px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.13);
            z-index: 9999;
            max-height: 280px;
            overflow-y: auto;
        }
        .moga-hotel-suggestion-item {
            padding: 9px 12px;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .moga-hotel-suggestion-item:last-child {
            border-bottom: none;
        }
        .moga-hotel-suggestion-item strong {
            font-size: 13px;
            color: #1d2327;
            font-weight: 600;
        }
        .moga-hotel-suggestion-item span {
            font-size: 11px;
            color: #8c8f94;
        }
        .moga-hotel-suggestion-item em {
            font-size: 11px;
            color: #f59e0b;
            font-style: normal;
        }
        .moga-hotel-suggestion-item:hover,
        .moga-hotel-suggestion-item--active {
            background: #f0f6fc;
        }
        .moga-accommodation-row__search-input {
            width: 100%;
            box-sizing: border-box;
        }
        .moga-accommodation-row__photo-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 8px;
        }
        .moga-accommodation-row__photo-thumb {
            position: relative;
            width: 60px;
            height: 60px;
            border-radius: 4px;
            overflow: hidden;
            border: 1px solid #ddd;
        }
        .moga-accommodation-row__photo-thumb img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            display: block;
        }
        .moga-accommodation-row__photo-remove {
            position: absolute;
            top: 2px;
            right: 2px;
            width: 16px;
            height: 16px;
            background: rgba(0,0,0,0.6);
            color: #fff;
            border: none;
            border-radius: 50%;
            font-size: 10px;
            line-height: 16px;
            text-align: center;
            cursor: pointer;
            padding: 0;
        }

        /* City autocomplete dropdown */
        .moga-city-ac-input {
            width: 100%;
            box-sizing: border-box;
        }
        .moga-city-ac-dropdown {
            position: absolute;
            top: calc(100% + 2px);
            left: 0;
            right: 0;
            background: #fff;
            border: 1.5px solid #2271b1;
            border-radius: 4px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.12);
            z-index: 9999;
            max-height: 260px;
            overflow-y: auto;
        }
        .moga-city-ac-item {
            padding: 8px 12px;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
            font-size: 13px;
            color: #1d2327;
        }
        .moga-city-ac-item:last-child { border-bottom: none; }
        .moga-city-ac-item small {
            display: block;
            font-size: 11px;
            color: #8c8f94;
            margin-top: 1px;
        }
        .moga-city-ac-item:hover,
        .moga-city-ac-item--active { background: #f0f6fc; }

        /* Country autocomplete — same visual style as city */
        .moga-country-ac-input,
        .moga-province-ac-input { width: 100%; box-sizing: border-box; }
        .moga-country-ac-dropdown,
        .moga-province-ac-dropdown {
            position: absolute;
            top: calc(100% + 2px);
            left: 0; right: 0;
            background: #fff;
            border: 1.5px solid #2271b1;
            border-radius: 4px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.12);
            z-index: 9999;
            max-height: 220px;
            overflow-y: auto;
        }
        .moga-country-ac-item,
        .moga-province-ac-item {
            padding: 8px 12px;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
            font-size: 13px;
            color: #1d2327;
        }
        .moga-country-ac-item:last-child,
        .moga-province-ac-item:last-child { border-bottom: none; }
        .moga-country-ac-item:hover,
        .moga-country-ac-item--active,
        .moga-province-ac-item:hover,
        .moga-province-ac-item--active { background: #f0f6fc; }
        </style>
        <script type="text/javascript">
            (function($) {
                'use strict';

                var mogaAdmin = <?php echo wp_json_encode($admin_data); ?>;
                var maxGallery = <?php echo intval($max_gallery); ?>;
                var maxUploads = <?php echo intval($max_uploads); ?>;

                // In-memory caches — one entry per parent ID.
                var provinceCache = {};
                var cityCache = {};
                var districtCache = {};


                // ================================================================
                // COUNTRY → PROVINCE (DB AJAX)
                // ================================================================

                $(document).on('change', '.moga-country-select', function() {
                    var $c = $(this);
                    var countryCode = $c.val();
                    var provTargetId = $c.data('province-target');
                    var cityTargetId = $c.data('city-target');
                    var distWrapper = $c.data('district-wrapper');
                    var $provSelect = $('#' + provTargetId);
                    var $citySelect = $('#' + cityTargetId);
                    var $distWrapper = distWrapper ? $('#' + distWrapper) : $();

                    // Reset downstream selects and district.
                    resetProvince($provSelect);
                    resetCity($citySelect);
                    if ($distWrapper.length) resetDistrict($distWrapper);

                    // Also clear the Google Places city autocomplete input
                    // and its hidden fields — the old city name is no longer
                    // valid for the newly selected country.
                    var $wrap = $provSelect.closest('.moga-metabox__row')
                        .next('.moga-metabox__row')
                        .find('.moga-city-autocomplete-wrap');
                    if (!$wrap.length) {
                        // Property metabox: city is in the next row.
                        $wrap = $(document).find(
                            '[data-country-source="' + $c.attr('id') + '"]'
                        );
                    }
                    $wrap.find('.moga-city-ac-input').val('');
                    var nameTarget = $wrap.data('name-target');
                    var idTarget   = $wrap.data('id-target');
                    if (nameTarget) $('#' + nameTarget).val('');
                    if (idTarget)   $('#' + idTarget).val('');

                    if (!countryCode) return;

                    if (provinceCache[countryCode]) {
                        populateProvinces($provSelect, provinceCache[countryCode]);
                        return;
                    }

                    $provSelect.empty().append($('<option>').val('').text(mogaAdmin.i18n.loadingProvinces)).prop('disabled', true);

                    $.ajax({
                        url: mogaAdmin.ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'moga_get_provinces',
                            nonce: mogaAdmin.nonce,
                            country_code: countryCode
                        },
                        success: function(r) {
                            if (r.success && r.data.provinces && r.data.provinces.length) {
                                provinceCache[countryCode] = r.data.provinces;
                                populateProvinces($provSelect, r.data.provinces);
                            } else {
                                resetProvince($provSelect);
                            }
                        },
                        error: function() {
                            resetProvince($provSelect);
                        }
                    });
                });


                // ================================================================
                // PROVINCE → CITY (DB AJAX)
                // ================================================================

                $(document).on('change', '.moga-province-select', function() {
                    var $p = $(this);
                    var provinceId = parseInt($p.val(), 10) || 0;
                    var cityTargetId = $p.data('city-target');
                    var distWrapper = $p.data('district-wrapper');
                    var nameField = $p.data('name-field');
                    var $citySelect = $('#' + cityTargetId);
                    var $distWrapper = distWrapper ? $('#' + distWrapper) : $();

                    // Sync province name hidden field.
                    if (nameField) {
                        var pName = provinceId ? $p.find(':selected').text().trim() : '';
                        $('#' + nameField).val(pName);
                    }

                    resetCity($citySelect);
                    if ($distWrapper.length) resetDistrict($distWrapper);

                    if (!provinceId) return;

                    if (cityCache[provinceId]) {
                        populateCities($citySelect, cityCache[provinceId]);
                        return;
                    }

                    $citySelect.empty().append($('<option>').val('').text(mogaAdmin.i18n.loadingCities)).prop('disabled', true);

                    $.ajax({
                        url: mogaAdmin.ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'moga_get_cities',
                            nonce: mogaAdmin.nonce,
                            province_id: provinceId
                        },
                        success: function(r) {
                            if (r.success && r.data.cities && r.data.cities.length) {
                                cityCache[provinceId] = r.data.cities;
                                populateCities($citySelect, r.data.cities);
                            } else {
                                resetCity($citySelect);
                            }
                        },
                        error: function() {
                            resetCity($citySelect);
                        }
                    });
                });


                // ================================================================
                // CITY → DISTRICT (DB AJAX)
                // ================================================================

                $(document).on('change', '.moga-city-select', function() {
                    var $city = $(this);
                    var cityId = parseInt($city.val(), 10) || 0;
                    var wrapperSel = $city.data('district-wrapper');
                    var nameField = $city.data('name-field');
                    var $wrapper = wrapperSel ? $('#' + wrapperSel) : $();

                    // Sync city name hidden field.
                    if (nameField) {
                        var cName = cityId ? $city.find(':selected').text().trim() : '';
                        $('#' + nameField).val(cName);
                    }

                    if ($wrapper.length) resetDistrict($wrapper);
                    if (!cityId || !$wrapper.length) return;

                    if (districtCache[cityId] !== undefined) {
                        renderDistricts($wrapper, districtCache[cityId], '');
                        return;
                    }

                    $wrapper.find('.moga-district-loading').show();

                    $.ajax({
                        url: mogaAdmin.ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'moga_get_districts',
                            nonce: mogaAdmin.nonce,
                            city_id: cityId
                        },
                        success: function(r) {
                            var districts = (r.success && r.data.districts) ? r.data.districts : [];
                            districtCache[cityId] = districts;
                            renderDistricts($wrapper, districts, '');
                        },
                        error: function() {
                            $wrapper.find('.moga-district-loading').hide();
                            districtCache[cityId] = [];
                        }
                    });
                });


                // ================================================================
                // POPULATE HELPERS
                // ================================================================

                function populateProvinces($select, provinces) {
                    $select.empty().append($('<option>').val('').text(mogaAdmin.i18n.selectProvince));
                    $.each(provinces, function(i, p) {
                        $select.append($('<option>').val(p.id).text(p.name));
                    });
                    $select.prop('disabled', false);
                }

                function populateCities($select, cities) {
                    $select.empty().append($('<option>').val('').text(mogaAdmin.i18n.selectCity));
                    $.each(cities, function(i, c) {
                        $select.append($('<option>').val(c.id).text(c.name));
                    });
                    $select.prop('disabled', false);
                }

                function resetProvince($select) {
                    $select.empty().append($('<option>').val('').text(mogaAdmin.i18n.selectCountryFirst)).prop('disabled', false);
                    var nf = $select.data('name-field');
                    if (nf) $('#' + nf).val('');
                }

                function resetCity($select) {
                    $select.empty().append($('<option>').val('').text(mogaAdmin.i18n.selectProvinceFirst)).prop('disabled', false);
                    var nf = $select.data('name-field');
                    if (nf) $('#' + nf).val('');
                }

                function renderDistricts($wrapper, districts, savedDistrict) {
                    var $dropdownField = $wrapper.find('.moga-district-dropdown-field');
                    var $select = $wrapper.find('.moga-district-select');
                    var $text = $wrapper.find('.moga-district-text');
                    var $label = $wrapper.find('.moga-district-text-label');
                    var $loading = $wrapper.find('.moga-district-loading');

                    $loading.hide();

                    if (districts.length) {
                        $select.empty().append($('<option>').val('').text(mogaAdmin.i18n.selectDistrict));
                        $.each(districts, function(i, d) {
                            var $opt = $('<option>').val(d.name).text(d.name);
                            if (savedDistrict && d.name === savedDistrict) $opt.prop('selected', true);
                            $select.append($opt);
                        });
                        $select.off('change.district').on('change.district', function() {
                            $text.val($(this).val());
                        });
                        if (savedDistrict) $text.val(savedDistrict);
                        $label.text(mogaAdmin.i18n.orTypeManually);
                        $dropdownField.show();
                    } else {
                        $label.text(mogaAdmin.i18n.districtLabel);
                        $dropdownField.hide();
                    }
                }

                function resetDistrict($wrapper) {
                    $wrapper.find('.moga-district-dropdown-field').hide();
                    $wrapper.find('.moga-district-select').empty();
                    $wrapper.find('.moga-district-text-label').text(mogaAdmin.i18n.districtLabel);
                    $wrapper.find('.moga-district-loading').hide();
                    $wrapper.find('.moga-district-text').val('');
                }


                // ================================================================
                // ON PAGE LOAD — auto-trigger district for already-selected cities
                // ================================================================

                $(document).ready(function() {
                    $('.moga-city-select').each(function() {
                        var $sel = $(this);
                        var cityId = parseInt($sel.val(), 10) || 0;
                        var wrapperSel = $sel.data('district-wrapper');
                        if (!cityId || !wrapperSel) return;
                        var $wrapper = $('#' + wrapperSel);
                        if (!$wrapper.length) return;
                        var savedDistrict = $wrapper.find('.moga-district-text').val();

                        if (districtCache[cityId] !== undefined) {
                            renderDistricts($wrapper, districtCache[cityId], savedDistrict);
                            return;
                        }

                        $wrapper.find('.moga-district-loading').show();

                        $.ajax({
                            url: mogaAdmin.ajaxUrl,
                            type: 'POST',
                            data: {
                                action: 'moga_get_districts',
                                nonce: mogaAdmin.nonce,
                                city_id: cityId
                            },
                            success: function(r) {
                                var districts = (r.success && r.data.districts) ? r.data.districts : [];
                                districtCache[cityId] = districts;
                                renderDistricts($wrapper, districts, savedDistrict);
                            },
                            error: function() {
                                $wrapper.find('.moga-district-loading').hide();
                            }
                        });
                    });
                });


                // ================================================================
                // PHOTO GALLERY
                // ================================================================

                var galleryFrame;

                function updateGalleryCount() {
                    var count = $('#moga-gallery-list .moga-gallery-box__item').length;
                    $('#moga-gallery-count').text(count);
                    $('#moga-gallery-add').prop('disabled', count >= maxGallery);
                }

                $('#moga-gallery-add').on('click', function() {
                    if ($('#moga-gallery-list .moga-gallery-box__item').length >= maxGallery) return;

                    if (galleryFrame) {
                        galleryFrame.open();
                        return;
                    }

                    galleryFrame = wp.media({
                        title: '<?php echo esc_js(__('Select Gallery Photos', 'moga-travel-core')); ?>',
                        button: {
                            text: '<?php echo esc_js(__('Add to Gallery', 'moga-travel-core')); ?>'
                        },
                        multiple: true,
                        library: {
                            type: 'image'
                        },
                    });

                    galleryFrame.on('select', function() {
                        var selection = galleryFrame.state().get('selection');
                        var currentCount = $('#moga-gallery-list .moga-gallery-box__item').length;
                        var remaining = maxGallery - currentCount;

                        selection.each(function(attachment, index) {
                            if (index >= remaining) return;
                            var id = attachment.get('id');
                            var thumb = attachment.get('sizes') && attachment.get('sizes').thumbnail ?
                                attachment.get('sizes').thumbnail.url : attachment.get('url');
                            if ($('#moga-gallery-list [data-id="' + id + '"]').length) return;
                            $('#moga-gallery-list').append(
                                '<li class="moga-gallery-box__item" data-id="' + id + '">' +
                                '<img src="' + thumb + '" alt="">' +
                                '<button type="button" class="moga-gallery-box__remove" title="Remove">✕</button>' +
                                '<input type="hidden" name="moga_gallery_ids[]" value="' + id + '">' +
                                '</li>'
                            );
                        });
                        updateGalleryCount();
                    });

                    galleryFrame.open();
                });

                $('#moga-gallery-list').on('click', '.moga-gallery-box__remove', function() {
                    $(this).closest('.moga-gallery-box__item').remove();
                    updateGalleryCount();
                });

                if ($.fn.sortable) {
                    $('#moga-gallery-list').sortable({
                        items: '.moga-gallery-box__item',
                        cursor: 'grab',
                        opacity: 0.7,
                        placeholder: 'moga-gallery-box__placeholder',
                        tolerance: 'pointer',
                    });
                }


                // ================================================================
                // PRICING PERIODS
                // ================================================================

                var periodsTemplate = $('#moga-periods-row-template').length ?
                    $('#moga-periods-row-template').html() :
                    '';

                function updateAddPeriodLabel() {
                    var hasPeriods = $('#moga-periods-list').children('.moga-period-card').length > 0;
                    $('#moga-periods-add-label').text(hasPeriods ? 'Add Another Period' : 'Add Period');
                }

                $('#moga-periods-add').on('click', function() {
                    if (!periodsTemplate) return;

                    var index = Date.now(); // Unique placeholder index — server re-numbers on save anyway.
                    var rowHtml = periodsTemplate.split('__INDEX__').join(index);
                    $('#moga-periods-list').append(rowHtml);
                    updateAddPeriodLabel();
                });

                $('#moga-periods-list').on('click', '.moga-period-card__remove', function() {
                    $(this).closest('.moga-period-card').remove();
                    updateAddPeriodLabel();
                });

                // Weekend Price is disabled until at least one Weekend
                // Day is checked WITHIN THAT SAME PERIOD CARD — each
                // period has its own independent weekend days now,
                // not one property-wide setting.
                $('#moga-periods-list').on('change', '.moga-period-weekday-checkbox', function() {
                    var $card = $(this).closest('.moga-period-card');
                    var anyChecked = $card.find('.moga-period-weekday-checkbox:checked').length > 0;
                    $card.find('.moga-period-weekend-price').prop('disabled', !anyChecked);
                });

                // Card header title live-updates to show the actual
                // date range as soon as both Start and End are set —
                // simple, consistent format here (not the fancier
                // same-month-shortening PHP does), since this is just
                // a live preview and the real formatted version comes
                // back from the server on next page load anyway.
                $('#moga-periods-list').on('change', '.moga-period-start, .moga-period-end', function() {
                    var $card = $(this).closest('.moga-period-card');
                    var start = $card.find('.moga-period-start').val();
                    var end = $card.find('.moga-period-end').val();
                    var $title = $card.find('.moga-period-card__title');

                    if (start && end) {
                        var startLabel = new Date(start + 'T00:00:00').toLocaleDateString(undefined, {
                            month: 'short',
                            day: 'numeric',
                            year: 'numeric'
                        });
                        var endLabel = new Date(end + 'T00:00:00').toLocaleDateString(undefined, {
                            month: 'short',
                            day: 'numeric',
                            year: 'numeric'
                        });
                        $title.text(startLabel + ' \u2013 ' + endLabel);
                    } else {
                        $title.text($title.data('default-label'));
                    }
                });

                // Chevron expands/collapses just that card's body —
                // purely visual, never clears or touches field values.
                $('#moga-periods-list').on('click', '.moga-period-card__toggle', function() {
                    var $card = $(this).closest('.moga-period-card');
                    $card.toggleClass('moga-period-card--collapsed');
                    $(this).text($card.hasClass('moga-period-card--collapsed') ? '\u25B8' : '\u25BE');
                });


                // ================================================================
                // TOUR ITINERARY BUILDER
                // ================================================================

                var itineraryTemplate = $('#moga-itinerary-day-template').length ?
                    $('#moga-itinerary-day-template').html() :
                    '';

                function renumberItineraryDays() {
                    $('#moga-itinerary-days > .moga-itinerary-builder__row').each(function(i) {
                        $(this).find('.moga-itinerary-builder__day-number').text(i + 1);
                    });
                }

                $('#moga-itinerary-add-day').on('click', function() {
                    if (!itineraryTemplate) return;

                    var index = Date.now(); // Unique placeholder index — server re-numbers on save anyway.
                    var rowHtml = itineraryTemplate.split('__INDEX__').join(index);
                    $('#moga-itinerary-days').append(rowHtml);
                    renumberItineraryDays();

                    // Newly appended rows are picked up automatically by the
                    // 'items' selector, but refresh() forces jQuery UI to
                    // re-scan immediately rather than at the next drag start.
                    if ($.fn.sortable && $('#moga-itinerary-days').data('ui-sortable')) {
                        $('#moga-itinerary-days').sortable('refresh');
                    }
                });

                $('#moga-itinerary-days').on('click', '.moga-itinerary-builder__remove', function() {
                    $(this).closest('.moga-itinerary-builder__row').remove();
                    renumberItineraryDays();
                });

                $('#moga-itinerary-days').on('click', '.moga-itinerary-builder__toggle', function() {
                    var $row = $(this).closest('.moga-itinerary-builder__row');
                    $row.toggleClass('moga-itinerary-builder__row--collapsed');
                    $(this).text($row.hasClass('moga-itinerary-builder__row--collapsed') ? '\u25B8' : '\u25BE');
                });

                if ($.fn.sortable) {
                    $('#moga-itinerary-days').sortable({
                        items: '.moga-itinerary-builder__row',
                        handle: '.moga-itinerary-builder__handle',
                        cursor: 'grab',
                        opacity: 0.7,
                        placeholder: 'moga-itinerary-builder__placeholder',
                        tolerance: 'pointer',
                        update: renumberItineraryDays,
                    });
                }


                // ================================================================
                // ORGANIZER PHOTO / LOGO
                // ================================================================

                var organizerPhotoFrame;

                $('#moga-organizer-photo-select').on('click', function() {
                    if (organizerPhotoFrame) {
                        organizerPhotoFrame.open();
                        return;
                    }

                    organizerPhotoFrame = wp.media({
                        title: '<?php echo esc_js(__('Select Organizer Photo or Logo', 'moga-travel-core')); ?>',
                        button: {
                            text: '<?php echo esc_js(__('Use this image', 'moga-travel-core')); ?>'
                        },
                        multiple: false,
                        library: {
                            type: 'image'
                        },
                    });

                    organizerPhotoFrame.on('select', function() {
                        var attachment = organizerPhotoFrame.state().get('selection').first().toJSON();
                        var url = attachment.sizes && attachment.sizes.thumbnail ?
                            attachment.sizes.thumbnail.url :
                            attachment.url;
                        $('#moga_organizer_photo').val(attachment.id);
                        $('#moga-organizer-photo-preview img').attr('src', url);
                        $('#moga-organizer-photo-preview').show();
                        $('#moga-organizer-photo-select').hide();
                    });

                    organizerPhotoFrame.open();
                });

                $('#moga-organizer-photo').on('click', '.moga-organizer-photo__remove', function() {
                    $('#moga_organizer_photo').val('');
                    $('#moga-organizer-photo-preview').hide();
                    $('#moga-organizer-photo-select').show();
                });


                // ================================================================
                // CUSTOM INCLUDES / EXCLUDES ITEMS
                // ================================================================

                $('.moga-custom-items__add').on('click', function() {
                    var targetId = $(this).data('target');
                    var name = $(this).data('name');
                    $('#' + targetId).append(
                        '<li class="moga-custom-items__row">' +
                        '<input type="text" name="' + name + '" value="" placeholder="<?php echo esc_js(__('e.g. Airport pickup', 'moga-travel-core')); ?>">' +
                        '<button type="button" class="moga-custom-items__remove" title="<?php echo esc_js(__('Remove', 'moga-travel-core')); ?>">✕</button>' +
                        '</li>'
                    );
                    $('#' + targetId + ' .moga-custom-items__row:last-child input').trigger('focus');
                });

                $('.moga-custom-items').on('click', '.moga-custom-items__remove', function() {
                    $(this).closest('.moga-custom-items__row').remove();
                });


                // ================================================================
                // LOCAL VIDEO UPLOAD
                // ================================================================

                var videoFrame;

                $('#moga-upload-video-add').on('click', function() {
                    if ($('#moga-upload-video-list .moga-videos-box__upload-item').length >= maxUploads) return;

                    if (videoFrame) {
                        videoFrame.open();
                        return;
                    }

                    videoFrame = wp.media({
                        title: '<?php echo esc_js(__('Upload or Select Video', 'moga-travel-core')); ?>',
                        button: {
                            text: '<?php echo esc_js(__('Use this video', 'moga-travel-core')); ?>'
                        },
                        multiple: false,
                        library: {
                            type: 'video'
                        },
                    });

                    videoFrame.on('select', function() {
                        var attachment = videoFrame.state().get('selection').first().toJSON();
                        var id = attachment.id;
                        var filename = attachment.filename || attachment.url.split('/').pop();
                        $('#moga-upload-video-list').append(
                            '<li class="moga-videos-box__upload-item" data-id="' + id + '">' +
                            '<span class="moga-videos-box__upload-icon">🎬</span>' +
                            '<span class="moga-videos-box__upload-name">' + filename + '</span>' +
                            '<button type="button" class="moga-videos-box__remove-upload" title="Remove">✕</button>' +
                            '<input type="hidden" name="moga_video_upload_ids[]" value="' + id + '">' +
                            '</li>'
                        );
                        if ($('#moga-upload-video-list .moga-videos-box__upload-item').length >= maxUploads) {
                            $('#moga-upload-video-add').prop('disabled', true);
                        }
                    });

                    videoFrame.open();
                });

                $('#moga-upload-video-list').on('click', '.moga-videos-box__remove-upload', function() {
                    $(this).closest('.moga-videos-box__upload-item').remove();
                    $('#moga-upload-video-add').prop('disabled', false);
                });


                // ================================================================
                // INTL-TEL-INPUT PHONE FIELD
                // ================================================================

                $(document).ready(function() {
                    var itiInputs = document.querySelectorAll('.moga-phone-field');
                    if (itiInputs.length && typeof window.intlTelInput === 'function') {
                        itiInputs.forEach(function(input) {
                            window.intlTelInput(input, {
                                initialCountry: 'eg',
                                loadUtils: function() {
                                    return import('<?php echo esc_js(defined('MOGA_THEME_URL') ? MOGA_THEME_URL . 'assets/js/vendor/intl-tel-input/utils.js' : ''); ?>');
                                },
                                separateDialCode: true,
                            });
                        });
                    }
                });


                // ================================================================
                // BUS SEAT LAYOUT — live total seats + preview
                // ================================================================

                var busLayouts = <?php echo wp_json_encode(
                    class_exists('Moga_CPT_Bus')
                        ? Moga_CPT_Bus::get_seat_layouts()
                        : array()
                ); ?>;

                function getBusColumns(layout) {
                    return busLayouts[layout] && busLayouts[layout].columns
                        ? busLayouts[layout].columns
                        : 4;
                }

                function updateBusTotalSeats() {
                    var layout  = $('#moga_seat_layout').val();
                    var rows    = parseInt($('#moga_seat_rows').val(), 10) || 0;
                    var columns = getBusColumns(layout);
                    var total   = rows * columns;
                    $('#moga-total-seats-display').text(total);

                    // Layout description.
                    var desc = busLayouts[layout] ? busLayouts[layout].desc : '';
                    $('#moga-layout-desc').text(desc || '');
                }

                function renderBusSeatPreview() {
                    var $preview = $('#moga-bus-seat-preview');
                    if (!$preview.length) return;

                    var layout   = $('#moga_seat_layout').val();
                    var rows     = parseInt($('#moga_seat_rows').val(), 10) || 0;
                    var columns  = getBusColumns(layout);
                    var driver   = $('#moga_driver_seat').val();

                    var vipRaw  = $('#moga_vip_seats').val();
                    var disRaw  = $('#moga_disabled_seats').val();
                    var vipList = vipRaw  ? vipRaw.split(',').map(function(s){ return s.trim(); }) : [];
                    var disList = disRaw  ? disRaw.split(',').map(function(s){ return s.trim(); }) : [];

                    var letters = ['A','B','C','D','E'].slice(0, columns);

                    // Aisle position.
                    var aisleAfter = Math.floor(columns / 2);
                    if (layout === '1+2' || layout === '1+1') aisleAfter = 1;

                    var html = '<div class="moga-seat-preview-grid">';

                    // Driver row — only the driver cell is visible,
                    // empty spacers are hidden via CSS (visibility:hidden).
                    html += '<div class="moga-seat-preview-row moga-seat-preview-row--driver">';
                    for (var d = 0; d < columns; d++) {
                        if (d === aisleAfter) html += '<div class="moga-seat-preview-aisle"></div>';
                        var isDriver = (driver === 'front-left' && d === 0) ||
                                       (driver === 'front-right' && d === columns - 1);
                        html += '<div class="moga-seat-preview-cell ' +
                            (isDriver ? 'moga-seat-preview-cell--driver' : 'moga-seat-preview-cell--empty') + '">' +
                            (isDriver ? '👤' : '') + '</div>';
                    }
                    html += '</div>';

                    // Seat rows — cap preview at 15 rows to avoid overwhelming the admin.
                    var previewRows = Math.min(rows, 15);
                    for (var r = 1; r <= previewRows; r++) {
                        html += '<div class="moga-seat-preview-row">';
                        for (var c = 0; c < columns; c++) {
                            if (c === aisleAfter) html += '<div class="moga-seat-preview-aisle"></div>';
                            var seatNum = r + letters[c];
                            var cls = 'moga-seat-preview-cell';
                            if (disList.indexOf(seatNum) !== -1) {
                                cls += ' moga-seat-preview-cell--disabled';
                            } else if (vipList.indexOf(seatNum) !== -1) {
                                cls += ' moga-seat-preview-cell--vip';
                            } else {
                                cls += ' moga-seat-preview-cell--available';
                            }
                            html += '<div class="' + cls + '" title="' + seatNum + '">' + seatNum + '</div>';
                        }
                        html += '</div>';
                    }

                    if (rows > 15) {
                        html += '<div class="moga-seat-preview-more">… ' + (rows - 15) + '<?php echo esc_js( __( ' more rows (not shown in preview)', 'moga-travel-core' ) ); ?></div>';
                    }

                    html += '</div>';

                    // Colored swatch legend — replaces the old plain-text hint.
                    html += '<div class="moga-seat-preview-legend">' +
                        '<div class="moga-seat-preview-legend__item">' +
                            '<span class="moga-seat-preview-legend__swatch moga-seat-preview-legend__swatch--available"></span>' +
                            '<?php echo esc_js( __( 'Available', 'moga-travel-core' ) ); ?>' +
                        '</div>' +
                        '<div class="moga-seat-preview-legend__item">' +
                            '<span class="moga-seat-preview-legend__swatch moga-seat-preview-legend__swatch--vip"></span>' +
                            '<?php echo esc_js( __( 'VIP', 'moga-travel-core' ) ); ?>' +
                        '</div>' +
                        '<div class="moga-seat-preview-legend__item">' +
                            '<span class="moga-seat-preview-legend__swatch moga-seat-preview-legend__swatch--disabled"></span>' +
                            '<?php echo esc_js( __( 'Unavailable', 'moga-travel-core' ) ); ?>' +
                        '</div>' +
                        '<div class="moga-seat-preview-legend__item">' +
                            '<span class="moga-seat-preview-legend__swatch moga-seat-preview-legend__swatch--driver"></span>' +
                            '<?php echo esc_js( __( 'Driver', 'moga-travel-core' ) ); ?>' +
                        '</div>' +
                    '</div>';

                    $preview.html(html);
                }

                function refreshBusPreview() {
                    updateBusTotalSeats();
                    renderBusSeatPreview();
                }

                if ($('#moga_seat_layout').length) {
                    // Init on page load.
                    refreshBusPreview();

                    // Update on any change.
                    $('#moga_seat_layout, #moga_seat_rows, #moga_driver_seat').on('change input', refreshBusPreview);
                    $('#moga_vip_seats, #moga_disabled_seats').on('input', function() {
                        // Slight delay so the user can finish typing.
                        clearTimeout(window._mogaBusPreviewTimer);
                        window._mogaBusPreviewTimer = setTimeout(renderBusSeatPreview, 400);
                    });
                }


                // ================================================================
                // TOUR EDITOR — Embedded bus builder preview
                // Targets the tour editor's own IDs (moga_tour_seat_layout etc.)
                // — separate from the Bus editor preview above which uses
                // moga_seat_layout. Both can coexist on the same page safely.
                // ================================================================

                function updateTourBusTotal() {
                    var layout  = $('#moga_tour_seat_layout').val();
                    var rows    = parseInt($('#moga_tour_seat_rows').val(), 10) || 0;
                    var columns = busLayouts[layout] && busLayouts[layout].columns ? busLayouts[layout].columns : 4;
                    $('#moga-tour-total-seats-display').text(rows * columns);
                    $('#moga-tour-layout-desc').text(busLayouts[layout] ? busLayouts[layout].desc || '' : '');
                }

                function renderTourBusSeatPreview() {
                    var $preview = $('#moga-tour-bus-seat-preview');
                    if (!$preview.length) return;

                    var layout  = $('#moga_tour_seat_layout').val();
                    var rows    = parseInt($('#moga_tour_seat_rows').val(), 10) || 0;
                    var columns = busLayouts[layout] && busLayouts[layout].columns ? busLayouts[layout].columns : 4;
                    var driver  = $('#moga_tour_driver_seat').val();
                    var letters = ['A','B','C','D','E'].slice(0, columns);
                    var vipList = ($('#moga_tour_vip_seats').val() || '').split(',').map(function(s){ return s.trim(); }).filter(Boolean);
                    var disList = ($('#moga_tour_disabled_seats').val() || '').split(',').map(function(s){ return s.trim(); }).filter(Boolean);

                    var aisleAfter = Math.floor(columns / 2);
                    if (layout === '1+2' || layout === '1+1') aisleAfter = 1;

                    var html = '<div class="moga-seat-preview-grid">';

                    // Driver row.
                    html += '<div class="moga-seat-preview-row moga-seat-preview-row--driver">';
                    for (var d = 0; d < columns; d++) {
                        if (d === aisleAfter) html += '<div class="moga-seat-preview-aisle"></div>';
                        var isDriver = (driver === 'front-left' && d === 0) || (driver === 'front-right' && d === columns - 1);
                        html += '<div class="moga-seat-preview-cell ' + (isDriver ? 'moga-seat-preview-cell--driver' : 'moga-seat-preview-cell--empty') + '">' + (isDriver ? '👤' : '') + '</div>';
                    }
                    html += '</div>';

                    var previewRows = Math.min(rows, 15);
                    for (var r = 1; r <= previewRows; r++) {
                        html += '<div class="moga-seat-preview-row">';
                        for (var c = 0; c < columns; c++) {
                            if (c === aisleAfter) html += '<div class="moga-seat-preview-aisle"></div>';
                            var seatNum = r + letters[c];
                            var cls = 'moga-seat-preview-cell';
                            if (disList.indexOf(seatNum) !== -1) cls += ' moga-seat-preview-cell--disabled';
                            else if (vipList.indexOf(seatNum) !== -1) cls += ' moga-seat-preview-cell--vip';
                            else cls += ' moga-seat-preview-cell--available';
                            html += '<div class="' + cls + '" title="' + seatNum + '">' + seatNum + '</div>';
                        }
                        html += '</div>';
                    }

                    if (rows > 15) {
                        html += '<div class="moga-seat-preview-more">… ' + (rows - 15) + '<?php echo esc_js(__(' more rows', 'moga-travel-core')); ?></div>';
                    }

                    html += '</div><div class="moga-seat-preview-legend">' +
                        '<div class="moga-seat-preview-legend__item"><span class="moga-seat-preview-legend__swatch moga-seat-preview-legend__swatch--available"></span><?php echo esc_js(__('Available', 'moga-travel-core')); ?></div>' +
                        '<div class="moga-seat-preview-legend__item"><span class="moga-seat-preview-legend__swatch moga-seat-preview-legend__swatch--vip"></span><?php echo esc_js(__('VIP', 'moga-travel-core')); ?></div>' +
                        '<div class="moga-seat-preview-legend__item"><span class="moga-seat-preview-legend__swatch moga-seat-preview-legend__swatch--disabled"></span><?php echo esc_js(__('Unavailable', 'moga-travel-core')); ?></div>' +
                        '<div class="moga-seat-preview-legend__item"><span class="moga-seat-preview-legend__swatch moga-seat-preview-legend__swatch--driver"></span><?php echo esc_js(__('Driver', 'moga-travel-core')); ?></div>' +
                    '</div>';

                    $preview.html(html);
                }

                if ($('#moga_tour_seat_layout').length) {
                    updateTourBusTotal();
                    renderTourBusSeatPreview();
                    $('#moga_tour_seat_layout, #moga_tour_seat_rows, #moga_tour_driver_seat').on('change input', function() {
                        updateTourBusTotal();
                        renderTourBusSeatPreview();
                    });
                    $('#moga_tour_vip_seats, #moga_tour_disabled_seats').on('input', function() {
                        clearTimeout(window._mogaTourBusTimer);
                        window._mogaTourBusTimer = setTimeout(renderTourBusSeatPreview, 400);
                    });
                }

            })(jQuery);
        </script>

        <?php // ================================================================ ?>
        <?php // COUNTRY + PROVINCE + CITY AUTOCOMPLETE — Google Places ?>
        <?php // ================================================================ ?>
        <script>
        (function() {
            'use strict';

            var nonce   = <?php echo wp_json_encode( wp_create_nonce( 'moga_nonce' ) ); ?>;
            var ajaxUrl = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;

            // ---- Country list (client-side filter — no API call) ----
            // Build sorted, code-free list from PHP.
            var allCountries = <?php
                $countries_raw = moga_get_countries();
                $country_list  = array();
                foreach ( $countries_raw as $c ) {
                    $country_list[] = array( 'code' => $c['code'], 'name' => $c['name'] );
                }
                usort( $country_list, fn( $a, $b ) => strcmp( $a['name'], $b['name'] ) );
                echo wp_json_encode( $country_list );
            ?>;

            // ---- Wire country autocomplete inputs ----
            document.querySelectorAll('.moga-country-autocomplete-wrap').forEach(function(wrap) {
                initCountryAc(wrap);
            });

            function initCountryAc(wrap) {
                var input    = wrap.querySelector('.moga-country-ac-input');
                var dropdown = wrap.querySelector('.moga-country-ac-dropdown');
                // The hidden ISO code field is the next sibling of wrap.
                var hidden   = wrap.parentElement
                    ? wrap.parentElement.querySelector('.moga-country-ac-hidden')
                    : null;

                if (!input || !dropdown || !hidden) return;

                var timer = null;

                function showCountries(matches) {
                    dropdown.innerHTML = '';
                    if (!matches.length) { dropdown.style.display = 'none'; return; }
                    matches.slice(0, 10).forEach(function(c) {
                        var item = document.createElement('div');
                        item.className = 'moga-country-ac-item';
                        item.textContent = c.name;
                        item.addEventListener('mousedown', function(e) {
                            e.preventDefault();
                            input.value  = c.name;
                            hidden.value = c.code;
                            dropdown.style.display = 'none';

                            // Clear province and city when country changes.
                            clearProvinceAndCity(hidden.id);

                            // Fire a custom event so province autocomplete
                            // reacts to the new country selection.
                            hidden.dispatchEvent(new Event('country-selected', { bubbles: true }));
                        });
                        dropdown.appendChild(item);
                    });
                    dropdown.style.display = 'block';
                }

                input.addEventListener('input', function() {
                    var q = input.value.trim().toLowerCase();
                    // Update hidden code — clear it until a match is picked.
                    hidden.value = '';
                    clearProvinceAndCity(hidden.id);

                    if (q.length < 1) { dropdown.style.display = 'none'; return; }
                    clearTimeout(timer);
                    timer = setTimeout(function() {
                        var matches = allCountries.filter(function(c) {
                            return c.name.toLowerCase().indexOf(q) === 0 ||
                                   c.name.toLowerCase().indexOf(' ' + q) !== -1;
                        });
                        showCountries(matches);
                    }, 150);
                });

                input.addEventListener('blur', function() {
                    setTimeout(function() { dropdown.style.display = 'none'; }, 220);
                });
                input.addEventListener('focus', function() {
                    if (dropdown.children.length) dropdown.style.display = 'block';
                });
                input.addEventListener('keydown', function(e) {
                    handleArrowKeys(e, dropdown, 'moga-country-ac-item--active');
                });
            }

            function clearProvinceAndCity(countryHiddenId) {
                // Find province and city inputs linked to this country hidden field.
                document.querySelectorAll('.moga-province-autocomplete-wrap').forEach(function(pw) {
                    if (pw.getAttribute('data-country-source') === countryHiddenId) {
                        var pi = pw.querySelector('.moga-province-ac-input');
                        var pnt = pw.getAttribute('data-name-target');
                        var pit = pw.getAttribute('data-id-target');
                        if (pi)  pi.value = '';
                        if (pnt) { var pnf = document.getElementById(pnt); if (pnf) pnf.value = ''; }
                        if (pit) { var pif = document.getElementById(pit); if (pif) pif.value = ''; }
                    }
                });
                document.querySelectorAll('.moga-city-autocomplete-wrap').forEach(function(cw) {
                    if (cw.getAttribute('data-country-source') === countryHiddenId) {
                        var ci = cw.querySelector('.moga-city-ac-input');
                        var cnt = cw.getAttribute('data-name-target');
                        var cit = cw.getAttribute('data-id-target');
                        if (ci)  ci.value = '';
                        if (cnt) { var cnf = document.getElementById(cnt); if (cnf) cnf.value = ''; }
                        if (cit) { var cif = document.getElementById(cit); if (cif) cif.value = ''; }
                    }
                });
            }

            // ---- Wire province autocomplete inputs ----
            document.querySelectorAll('.moga-province-autocomplete-wrap').forEach(function(wrap) {
                initProvinceAc(wrap);
            });

            var provTimers = {};

            function initProvinceAc(wrap) {
                var input      = wrap.querySelector('.moga-province-ac-input');
                var dropdown   = wrap.querySelector('.moga-province-ac-dropdown');
                var countryId  = wrap.getAttribute('data-country-source');
                var nameTarget = wrap.getAttribute('data-name-target');
                var idTarget   = wrap.getAttribute('data-id-target');

                if (!input || !dropdown) return;

                function getCountryCode() {
                    var el = document.getElementById(countryId);
                    return el ? el.value : '';
                }

                function setProvince(name) {
                    input.value = name;
                    if (nameTarget) { var nf = document.getElementById(nameTarget); if (nf) nf.value = name; }
                    if (idTarget)   { var idf = document.getElementById(idTarget);  if (idf) idf.value = ''; }
                    dropdown.style.display = 'none';
                    dropdown.innerHTML = '';
                    // Clear city when province changes.
                    clearCityForCountry(countryId);
                }

                function clearCityForCountry(cid) {
                    document.querySelectorAll('.moga-city-autocomplete-wrap').forEach(function(cw) {
                        if (cw.getAttribute('data-country-source') === cid) {
                            var ci = cw.querySelector('.moga-city-ac-input');
                            var cnt = cw.getAttribute('data-name-target');
                            var cit = cw.getAttribute('data-id-target');
                            if (ci)  ci.value = '';
                            if (cnt) { var cnf = document.getElementById(cnt); if (cnf) cnf.value = ''; }
                            if (cit) { var cif = document.getElementById(cit); if (cif) cif.value = ''; }
                        }
                    });
                }

                input.addEventListener('input', function() {
                    var query = input.value.trim();
                    if (nameTarget) { var nf = document.getElementById(nameTarget); if (nf) nf.value = query; }

                    if (query.length < 2) { dropdown.style.display = 'none'; return; }

                    clearTimeout(provTimers[nameTarget || 'prov']);
                    provTimers[nameTarget || 'prov'] = setTimeout(function() {
                        var country  = getCountryCode();
                        var formData = new FormData();
                        formData.append('action',  'moga_province_autocomplete');
                        formData.append('nonce',   nonce);
                        formData.append('query',   query);
                        formData.append('country', country);

                        fetch(ajaxUrl, { method: 'POST', body: formData })
                            .then(function(r) { return r.json(); })
                            .then(function(json) {
                                if (!json.success || !json.data || !json.data.provinces) return;
                                var provs = json.data.provinces;
                                dropdown.innerHTML = '';
                                if (!provs.length) { dropdown.style.display = 'none'; return; }
                                provs.forEach(function(p) {
                                    var item = document.createElement('div');
                                    item.className = 'moga-province-ac-item';
                                    item.textContent = p.name;
                                    item.addEventListener('mousedown', function(e) {
                                        e.preventDefault();
                                        setProvince(p.name);
                                    });
                                    dropdown.appendChild(item);
                                });
                                dropdown.style.display = 'block';
                            })
                            .catch(function() {});
                    }, 300);
                });

                input.addEventListener('blur', function() {
                    setTimeout(function() { dropdown.style.display = 'none'; }, 220);
                });
                input.addEventListener('focus', function() {
                    if (dropdown.children.length) dropdown.style.display = 'block';
                });
                input.addEventListener('keydown', function(e) {
                    handleArrowKeys(e, dropdown, 'moga-province-ac-item--active');
                });
            }

            // ---- Wire city autocomplete inputs ----
            var timers = {};

            document.querySelectorAll('.moga-city-autocomplete-wrap').forEach(function(wrap) {
                initCityAc(wrap);
            });

            function initCityAc(wrap) {
                var input      = wrap.querySelector('.moga-city-ac-input');
                var dropdown   = wrap.querySelector('.moga-city-ac-dropdown');
                var countryId  = wrap.getAttribute('data-country-source');
                var nameTarget = wrap.getAttribute('data-name-target');
                var idTarget   = wrap.getAttribute('data-id-target');

                if (!input || !dropdown) return;

                function getCountry() {
                    var el = document.getElementById(countryId);
                    return el ? el.value : '';
                }

                function setCity(name, placeId) {
                    input.value = name;
                    if (nameTarget) { var nf = document.getElementById(nameTarget); if (nf) nf.value = name; }
                    if (idTarget)   { var idf = document.getElementById(idTarget);  if (idf) idf.value = placeId || ''; }
                    dropdown.style.display = 'none';
                    dropdown.innerHTML = '';
                }

                function showDropdown(cities) {
                    dropdown.innerHTML = '';
                    if (!cities || !cities.length) { dropdown.style.display = 'none'; return; }
                    cities.forEach(function(city) {
                        var item = document.createElement('div');
                        item.className = 'moga-city-ac-item';
                        item.innerHTML =
                            escH(city.name) +
                            (city.description && city.description !== city.name
                                ? '<small>' + escH(city.description) + '</small>'
                                : '');
                        item.addEventListener('mousedown', function(e) {
                            e.preventDefault();
                            setCity(city.name, city.place_id);
                        });
                        dropdown.appendChild(item);
                    });
                    dropdown.style.display = 'block';
                }

                input.addEventListener('input', function() {
                    var query = input.value.trim();
                    if (nameTarget) { var nf = document.getElementById(nameTarget); if (nf) nf.value = query; }

                    if (query.length < 2) { dropdown.style.display = 'none'; return; }

                    clearTimeout(timers[nameTarget]);
                    timers[nameTarget] = setTimeout(function() {
                        var country  = getCountry();
                        // Also read province to scope city results correctly.
                        // Province hidden field ID follows the pattern:
                        // country source = "moga_destination_country"
                        // → province hidden = "moga_destination_province"
                        var provinceId = countryId.replace('_country', '_province');
                        var provEl     = document.getElementById(provinceId);
                        var province   = provEl ? provEl.value : '';

                        var formData = new FormData();
                        formData.append('action',   'moga_city_autocomplete');
                        formData.append('nonce',    nonce);
                        formData.append('query',    query);
                        formData.append('country',  country);
                        formData.append('province', province);

                        fetch(ajaxUrl, { method: 'POST', body: formData })
                            .then(function(r) { return r.json(); })
                            .then(function(json) {
                                if (json.success && json.data && json.data.cities) {
                                    showDropdown(json.data.cities);
                                }
                            })
                            .catch(function() {});
                    }, 300);
                });

                input.addEventListener('blur', function() {
                    setTimeout(function() { dropdown.style.display = 'none'; }, 220);
                });
                input.addEventListener('focus', function() {
                    if (dropdown.children.length) dropdown.style.display = 'block';
                });
                input.addEventListener('keydown', function(e) {
                    handleArrowKeys(e, dropdown, 'moga-city-ac-item--active');
                });
            }

            // ---- Shared arrow key navigation ----
            function handleArrowKeys(e, dropdown, activeClass) {
                var items = Array.from(dropdown.querySelectorAll('[class*="-ac-item"]'));
                if (!items.length) return;
                if (e.key === 'Escape') { dropdown.style.display = 'none'; return; }
                if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                    e.preventDefault();
                    var active = dropdown.querySelector('.' + activeClass);
                    var idx    = items.indexOf(active);
                    if (active) active.classList.remove(activeClass);
                    idx = e.key === 'ArrowDown'
                        ? Math.min(idx + 1, items.length - 1)
                        : Math.max(idx - 1, 0);
                    items[idx].classList.add(activeClass);
                    items[idx].scrollIntoView({ block: 'nearest' });
                }
                if (e.key === 'Enter') {
                    var activeItem = dropdown.querySelector('.' + activeClass);
                    if (activeItem) { e.preventDefault(); activeItem.dispatchEvent(new MouseEvent('mousedown')); }
                }
            }

            function escH(str) {
                return String(str)
                    .replace(/&/g, '&amp;').replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
            }

        })();
        </script>

<?php
    }
}
