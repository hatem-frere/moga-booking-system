<?php

/**
 * Single Tour Page Template
 *
 * Path: themes/moga-travel/single-pages/single-moga_tour.php
 *
 * Loaded via template_include filter in theme-hooks.php.
 *
 * @package MogaTravel
 * @since   1.0.0
 */

get_header();

if (! have_posts()) {
    get_footer();
    return;
}

the_post();
$tour_id = get_the_ID();


// ============================================================
// 01. DATA COLLECTION
// ============================================================

$title   = get_the_title();
$content = get_the_content();

// Category.
$categories   = wp_get_post_terms($tour_id, 'moga_tour_category', array('fields' => 'all'));
$category     = ! is_wp_error($categories) && ! empty($categories) ? $categories[0] : null;
$category_emoji = $category ? get_term_meta($category->term_id, 'moga_emoji', true) : '';

// Location — departure & destination (free-text/ISO fields, not the 4-level location DB tables).
$departure_country_code   = get_post_meta($tour_id, '_moga_departure_country',   true);
$departure_city           = get_post_meta($tour_id, '_moga_departure_city',      true);
$departure_point          = get_post_meta($tour_id, '_moga_departure_point',     true);
$destination_country_code = get_post_meta($tour_id, '_moga_destination_country', true);
$destination_city         = get_post_meta($tour_id, '_moga_destination_city',    true);
$latitude                 = get_post_meta($tour_id, '_moga_latitude',            true);
$longitude                = get_post_meta($tour_id, '_moga_longitude',           true);

$route_stops_json = get_post_meta($tour_id, '_moga_route_stops', true);
$route_stops      = $route_stops_json ? json_decode($route_stops_json, true) : array();
$route_stops      = is_array($route_stops) ? array_filter($route_stops) : array();

$destination_country = function_exists('moga_get_country') ? moga_get_country($destination_country_code) : null;
$destination_country_name = $destination_country['name'] ?? $destination_country_code;

$departure_country = function_exists('moga_get_country') ? moga_get_country($departure_country_code) : null;
$departure_country_name = $departure_country['name'] ?? $departure_country_code;

$location_parts = array_filter(array($destination_city, $destination_country_name));
$location_label = implode(', ', $location_parts);

// Price.
$display_price = function_exists('moga_get_tour_display_price')
    ? moga_get_tour_display_price($tour_id)
    : array('price' => 0, 'original' => 0, 'currency' => 'USD', 'discount' => 0);

// Rating.
$rating       = floatval(get_post_meta($tour_id, '_moga_rating',       true));
$review_count = intval(get_post_meta($tour_id, '_moga_review_count', true));
$rating_label = function_exists('moga_get_rating_label') ? moga_get_rating_label($rating) : '';

// Duration.
$duration_days   = intval(get_post_meta($tour_id, '_moga_duration_days',   true)) ?: 1;
$duration_nights = intval(get_post_meta($tour_id, '_moga_duration_nights', true));
$duration_label  = $duration_nights > 0
    ? sprintf(
        /* translators: 1: number of days, 2: number of nights */
        _n('%1$d Day', '%1$d Days', $duration_days, 'moga-travel') . ' / ' . _n('%2$d Night', '%2$d Nights', $duration_nights, 'moga-travel'),
        $duration_days,
        $duration_nights
    )
    : sprintf(_n('%d Day', '%d Days', $duration_days, 'moga-travel'), $duration_days);

// Difficulty & tour type.
$difficulty       = get_post_meta($tour_id, '_moga_difficulty', true) ?: 'easy';
$difficulty_levels = class_exists('Moga_CPT_Tour') ? Moga_CPT_Tour::get_difficulty_levels() : array();
$difficulty_data   = isset($difficulty_levels[$difficulty]) ? $difficulty_levels[$difficulty] : null;

$tour_type_key  = get_post_meta($tour_id, '_moga_tour_type', true) ?: 'group';
$tour_types     = class_exists('Moga_CPT_Tour') ? Moga_CPT_Tour::get_tour_types() : array();
$tour_type_data = isset($tour_types[$tour_type_key]) ? $tour_types[$tour_type_key] : null;

// Group size & language.
$max_participants = intval(get_post_meta($tour_id, '_moga_max_participants', true)) ?: 20;
$language         = get_post_meta($tour_id, '_moga_language', true) ?: 'Arabic';
$guide_included    = get_post_meta($tour_id, '_moga_guide_included', true);

// Schedule.
$departure_time = get_post_meta($tour_id, '_moga_departure_time', true) ?: '08:00';
$return_time     = get_post_meta($tour_id, '_moga_return_time',    true) ?: '18:00';

// Contact / organizer.
$organizer_name = get_post_meta($tour_id, '_moga_organizer_name', true);
$organizer_phone = get_post_meta($tour_id, '_moga_phone', true);
$organizer_whatsapp = get_post_meta($tour_id, '_moga_whatsapp', true);
$organizer_email = get_post_meta($tour_id, '_moga_email', true);

// Per-listing fields are OPTIONAL OVERRIDES — a vendor uploads their
// photo and contact info once, on their account (Moga_Shortcode_Account),
// and every listing pulls from there unless this specific listing
// explicitly overrides a field. Only fall back for fields left blank.
$author_id = (int) get_post_field('post_author', $tour_id);
if (! $organizer_name) {
    $author        = get_userdata($author_id);
    $company_name  = get_user_meta($author_id, 'moga_company_name', true);
    $organizer_name = $company_name ?: ($author ? $author->display_name : '');
}
if (! $organizer_phone) {
    $organizer_phone = get_user_meta($author_id, 'moga_contact_phone', true);
}
if (! $organizer_whatsapp) {
    $organizer_whatsapp = get_user_meta($author_id, 'moga_contact_whatsapp', true);
}
if (! $organizer_email) {
    $organizer_email = get_user_meta($author_id, 'moga_contact_email', true);
}

// Cancellation.
$cancellation    = get_post_meta($tour_id, '_moga_cancellation', true) ?: 'moderate';
$cancellation_policies = class_exists('Moga_CPT_Tour')
    ? Moga_CPT_Tour::get_cancellation_policies() : array();
$cancellation_label = isset($cancellation_policies[$cancellation])
    ? $cancellation_policies[$cancellation]['label'] : '';
$cancellation_desc  = isset($cancellation_policies[$cancellation])
    ? $cancellation_policies[$cancellation]['desc']  : '';

// Status.
$instant_booking = get_post_meta($tour_id, '_moga_instant_booking', true);

// Includes / Excludes.
$includes_json = get_post_meta($tour_id, '_moga_includes', true);
$includes_keys = $includes_json ? json_decode($includes_json, true) : array();
$includes_keys = is_array($includes_keys) ? $includes_keys : array();

$excludes_json = get_post_meta($tour_id, '_moga_excludes', true);
$excludes_keys = $excludes_json ? json_decode($excludes_json, true) : array();
$excludes_keys = is_array($excludes_keys) ? $excludes_keys : array();

$all_includes = class_exists('Moga_CPT_Tour') ? Moga_CPT_Tour::get_includes_options() : array();
$all_excludes = class_exists('Moga_CPT_Tour') ? Moga_CPT_Tour::get_excludes_options() : array();

$includes_custom_json = get_post_meta($tour_id, '_moga_includes_custom', true);
$includes_custom      = $includes_custom_json ? json_decode($includes_custom_json, true) : array();
$includes_custom      = is_array($includes_custom) ? array_filter($includes_custom) : array();

$excludes_custom_json = get_post_meta($tour_id, '_moga_excludes_custom', true);
$excludes_custom      = $excludes_custom_json ? json_decode($excludes_custom_json, true) : array();
$excludes_custom      = is_array($excludes_custom) ? array_filter($excludes_custom) : array();

// Organizer.
$organizer_photo_id  = (int) get_post_meta($tour_id, '_moga_organizer_photo', true);
if (! $organizer_photo_id) {
    $organizer_photo_id = (int) get_user_meta($author_id, 'moga_profile_photo', true);
}
$organizer_photo_url = $organizer_photo_id ? wp_get_attachment_image_url($organizer_photo_id, 'medium') : '';

// Itinerary — presence check only (rendering handled by template part).
$itinerary_json = get_post_meta($tour_id, '_moga_itinerary', true);
$has_itinerary  = ! empty($itinerary_json) && ! empty(json_decode($itinerary_json, true));

// Description.
$content_clean = wp_kses_post(wpautop(wp_unslash($content)));
$content_words = str_word_count(strip_tags($content));

// Reviews data for gallery sidebar.
global $wpdb;
$reviews_table  = $wpdb->prefix . 'moga_reviews';
$reviews        = array();
$cat_avgs       = array();
$amenity_totals = array();
$amenity_counts = array();

if ($review_count > 0) {
    $reviews = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$reviews_table}
         WHERE listing_id = %d AND listing_type = 'tour' AND status = 'approved'
         ORDER BY created_at DESC LIMIT 10",
        $tour_id
    ));

    // Category averages — same fixed columns as property reviews.
    $categories_map = array(
        'rating_cleanliness' => __('Cleanliness',     'moga-travel'),
        'rating_location'    => __('Location',        'moga-travel'),
        'rating_value'       => __('Value for money', 'moga-travel'),
        'rating_service'     => __('Guide / Staff',   'moga-travel'),
        'rating_comfort'     => __('Comfort',         'moga-travel'),
        'rating_facilities'  => __('Organization',    'moga-travel'),
    );

    $cat_totals = array_fill_keys(array_keys($categories_map), 0);
    $cat_counts = array_fill_keys(array_keys($categories_map), 0);

    foreach ($reviews as $review) {
        foreach ($categories_map as $col => $label) {
            if (! is_null($review->$col) && $review->$col > 0) {
                $cat_totals[$col] += floatval($review->$col);
                $cat_counts[$col]++;
            }
        }
        // Dynamic per-item scores — repurposed to score against tour includes
        // rather than property amenities (same 'rating_amenities' JSON column).
        if (! empty($review->rating_amenities)) {
            $scores = json_decode($review->rating_amenities, true);
            if (is_array($scores)) {
                foreach ($scores as $key => $score) {
                    $amenity_totals[$key] = ($amenity_totals[$key] ?? 0) + floatval($score);
                    $amenity_counts[$key] = ($amenity_counts[$key] ?? 0) + 1;
                }
            }
        }
    }

    foreach ($categories_map as $col => $label) {
        if ($cat_counts[$col] > 0) {
            $cat_avgs[$col] = array(
                'label' => $label,
                'avg'   => $cat_totals[$col] / $cat_counts[$col],
            );
        }
    }
}

// Featured review for sidebar quote.
$featured_review = ! empty($reviews) ? $reviews[0] : null;

// Section nav.
$section_nav = array('moga-description' => __('Overview', 'moga-travel'));
if ($has_itinerary) {
    $section_nav['moga-itinerary'] = __('Itinerary', 'moga-travel');
}
if (! empty($includes_keys) || ! empty($excludes_keys) || ! empty($includes_custom) || ! empty($excludes_custom)) {
    $section_nav['moga-includes'] = __("What's Included", 'moga-travel');
}
$section_nav['moga-logistics'] = __('Good to Know', 'moga-travel');
$section_nav['moga-reviews']   = __('Reviews', 'moga-travel');
?>

<main id="moga-main" class="moga-main moga-tour-single">
    <div class="moga-container">


        <?php // ---- Breadcrumb ----
        ?>
        <nav class="moga-breadcrumb" aria-label="<?php esc_attr_e('Breadcrumb', 'moga-travel'); ?>">
            <a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Home', 'moga-travel'); ?></a>
            <span class="moga-breadcrumb__sep" aria-hidden="true">›</span>
            <a href="<?php echo esc_url(add_query_arg('type', 'tour', home_url('/search-results/'))); ?>"><?php esc_html_e('Tours', 'moga-travel'); ?></a>
            <?php if ($destination_city) : ?>
                <span class="moga-breadcrumb__sep" aria-hidden="true">›</span>
                <a href="<?php echo esc_url(add_query_arg(array('type' => 'tour', 'location' => $destination_city), home_url('/search-results/'))); ?>"><?php echo esc_html($destination_city); ?></a>
            <?php endif; ?>
            <span class="moga-breadcrumb__sep" aria-hidden="true">›</span>
            <span aria-current="page"><?php echo esc_html($title); ?></span>
        </nav>


        <?php // ---- Section Navigation ----
        ?>
        <nav class="moga-section-nav" aria-label="<?php esc_attr_e('Page sections', 'moga-travel'); ?>">
            <div class="moga-section-nav__inner">
                <?php foreach ($section_nav as $anchor => $label) : ?>
                    <a href="#<?php echo esc_attr($anchor); ?>" class="moga-section-nav__link">
                        <?php echo esc_html($label); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </nav>


        <?php // ---- Tour Header ----
        ?>
        <div class="moga-property-header">
            <div class="moga-property-header__main">
                <?php if ($category) : ?>
                    <span class="moga-property-header__type">
                        <?php echo esc_html(trim($category_emoji . ' ' . $category->name)); ?>
                    </span>
                <?php endif; ?>
                <h1 class="moga-property-header__title"><?php echo esc_html($title); ?></h1>
                <div class="moga-property-header__meta">
                    <?php if ($rating > 0) : ?>
                        <div class="moga-property-header__rating">
                            <span class="moga-rating-score"><?php echo esc_html(number_format($rating, 1)); ?></span>
                            <span class="moga-rating-label"><?php echo esc_html($rating_label); ?></span>
                            <?php if ($review_count > 0) : ?>
                                <a href="#moga-reviews" class="moga-rating-count">
                                    <?php echo esc_html(number_format_i18n($review_count)); ?>
                                    <?php echo esc_html(1 === $review_count ? __('review', 'moga-travel') : __('reviews', 'moga-travel')); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($location_label) : ?>
                        <div class="moga-property-header__location">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" />
                                <circle cx="12" cy="10" r="3" />
                            </svg>
                            <span><?php echo esc_html($location_label); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="moga-property-header__actions">
                <?php if ('1' === $instant_booking) : ?>
                    <span class="moga-badge moga-badge--instant">⚡ <?php esc_html_e('Instant Booking', 'moga-travel'); ?></span>
                <?php endif; ?>
                <a href="#moga-booking-sidebar" class="moga-btn moga-btn--primary moga-property-header__reserve">
                    <?php esc_html_e('Reserve', 'moga-travel'); ?>
                </a>
                <button type="button" class="moga-property-header__action-btn" id="moga-share-btn" aria-label="<?php esc_attr_e('Share tour', 'moga-travel'); ?>">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <circle cx="18" cy="5" r="3" />
                        <circle cx="6" cy="12" r="3" />
                        <circle cx="18" cy="19" r="3" />
                        <line x1="8.59" y1="13.51" x2="15.42" y2="17.49" />
                        <line x1="15.41" y1="6.51" x2="8.59" y2="10.49" />
                    </svg>
                    <?php esc_html_e('Share', 'moga-travel'); ?>
                </button>
                <button type="button" class="moga-property-header__action-btn moga-wishlist-btn" data-id="<?php echo esc_attr($tour_id); ?>" aria-label="<?php esc_attr_e('Save to wishlist', 'moga-travel'); ?>">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
                    </svg>
                    <?php esc_html_e('Save', 'moga-travel'); ?>
                </button>
            </div>
        </div>
        <?php // ---- End Tour Header ----
        ?>


        <?php // ---- Gallery + Rating Sidebar ----
        ?>
        <div class="moga-gallery-with-sidebar">

            <?php // Left: Gallery mosaic + thumbnail strip
            ?>
            <div class="moga-gallery-main">
                <?php get_template_part('template-parts/tour/single-gallery'); ?>
            </div>

            <?php // Right: Rating box + Map + Videos
            ?>
            <div class="moga-gallery-sidebar">

                <?php // Rating Box
                ?>
                <div class="moga-gallery-rating-box">
                    <div class="moga-gallery-rating-box__score">
                        <?php if ($rating > 0) : ?>
                            <span class="moga-gallery-rating-box__badge">
                                <?php echo esc_html(number_format($rating, 1)); ?>
                            </span>
                            <div class="moga-gallery-rating-box__labels">
                                <span class="moga-gallery-rating-box__label"><?php echo esc_html($rating_label); ?></span>
                                <?php if ($review_count > 0) : ?>
                                    <a href="#moga-reviews" class="moga-gallery-rating-box__count">
                                        <?php printf(
                                            esc_html(1 === $review_count ? __('%d review', 'moga-travel') : __('%d reviews', 'moga-travel')),
                                            number_format_i18n($review_count)
                                        ); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php else : ?>
                            <span class="moga-gallery-rating-box__no-rating">
                                <?php esc_html_e('No reviews yet', 'moga-travel'); ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <?php // Featured review quote
                    ?>
                    <?php if ($featured_review && $featured_review->content) : ?>
                        <div class="moga-gallery-rating-box__quote">
                            <p class="moga-gallery-rating-box__quote-text">
                                "<?php echo esc_html(mb_substr($featured_review->content, 0, 100)); ?>…"
                            </p>
                            <?php
                            $guest = get_userdata($featured_review->guest_id);
                            if ($guest) :
                            ?>
                                <span class="moga-gallery-rating-box__quote-author">
                                    <?php echo esc_html($guest->display_name); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php // ---- End Rating Box ----
                ?>

                <?php // Map — OpenStreetMap iframe
                ?>
                <div class="moga-gallery-map">
                    <?php if ($latitude && $longitude) :
                        $lat  = floatval($latitude);
                        $lng  = floatval($longitude);
                        $bbox = ($lng - 0.01) . ',' . ($lat - 0.01) . ',' . ($lng + 0.01) . ',' . ($lat + 0.01);
                        $map_url = add_query_arg(array(
                            'bbox'   => $bbox,
                            'layer'  => 'mapnik',
                            'marker' => $lat . ',' . $lng,
                        ), 'https://www.openstreetmap.org/export/embed.html');
                    ?>
                        <iframe
                            src="<?php echo esc_url($map_url); ?>"
                            class="moga-gallery-map__iframe"
                            title="<?php printf(esc_attr__('Map showing destination of %s', 'moga-travel'), $title); ?>"
                            loading="lazy"
                            referrerpolicy="no-referrer"></iframe>
                        <a
                            href="<?php echo esc_url('https://www.openstreetmap.org/?mlat=' . $lat . '&mlon=' . $lng . '#map=15/' . $lat . '/' . $lng); ?>"
                            class="moga-gallery-map__link"
                            target="_blank"
                            rel="noopener noreferrer">
                            <?php esc_html_e('View larger map', 'moga-travel'); ?>
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6" />
                                <polyline points="15 3 21 3 21 9" />
                                <line x1="10" y1="14" x2="21" y2="3" />
                            </svg>
                        </a>
                    <?php else : ?>
                        <div class="moga-gallery-map__placeholder">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" />
                                <circle cx="12" cy="10" r="3" />
                            </svg>
                            <p><?php echo esc_html($location_label ?: __('Destination not set', 'moga-travel')); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
                <?php // ---- End Map ----
                ?>

                <?php // ---- Videos — sidebar widget below map ----
                ?>
                <?php get_template_part('template-parts/tour/single-videos'); ?>

            </div>
            <?php // ---- End Gallery Sidebar ----
            ?>

        </div>
        <?php // ---- End Gallery + Rating Sidebar ----
        ?>


        <?php // ---- Two-Column Layout ----
        ?>
        <div class="moga-single-layout">

            <?php // ---- LEFT: Main Content ----
            ?>
            <div class="moga-single-content">

                <?php // ---- Highlights Bar ----
                ?>
                <div class="moga-property-highlights">
                    <div class="moga-property-highlights__item">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <circle cx="12" cy="12" r="10" />
                            <polyline points="12 6 12 12 16 14" />
                        </svg>
                        <span class="moga-property-highlights__value moga-property-highlights__value--sm"><?php echo esc_html($duration_label); ?></span>
                        <span class="moga-property-highlights__label"><?php esc_html_e('Duration', 'moga-travel'); ?></span>
                    </div>
                    <?php if ($max_participants > 0) : ?>
                        <div class="moga-property-highlights__item">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                <circle cx="9" cy="7" r="4" />
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                                <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                            </svg>
                            <span class="moga-property-highlights__value"><?php echo esc_html($max_participants); ?></span>
                            <span class="moga-property-highlights__label"><?php esc_html_e('Max group size', 'moga-travel'); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($difficulty_data) : ?>
                        <div class="moga-property-highlights__item">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="<?php echo esc_attr($difficulty_data['color']); ?>" stroke-width="1.5" aria-hidden="true">
                                <path d="M13 2L3 14h7l-1 8 11-14h-7l1-6z" />
                            </svg>
                            <span class="moga-property-highlights__value moga-property-highlights__value--sm"><?php echo esc_html($difficulty_data['label']); ?></span>
                            <span class="moga-property-highlights__label"><?php esc_html_e('Difficulty', 'moga-travel'); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($language) : ?>
                        <div class="moga-property-highlights__item">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <circle cx="12" cy="12" r="10" />
                                <line x1="2" y1="12" x2="22" y2="12" />
                                <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
                            </svg>
                            <span class="moga-property-highlights__value moga-property-highlights__value--sm"><?php echo esc_html($language); ?></span>
                            <span class="moga-property-highlights__label"><?php esc_html_e('Language', 'moga-travel'); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($tour_type_data) : ?>
                        <div class="moga-property-highlights__item">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                <circle cx="9" cy="7" r="4" />
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                                <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                            </svg>
                            <span class="moga-property-highlights__value moga-property-highlights__value--sm"><?php echo esc_html($tour_type_data['label']); ?></span>
                            <span class="moga-property-highlights__label"><?php esc_html_e('Tour type', 'moga-travel'); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($cancellation_label) : ?>
                        <div class="moga-property-highlights__item">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                            </svg>
                            <span class="moga-property-highlights__value moga-property-highlights__value--sm"><?php echo esc_html($cancellation_label); ?></span>
                            <span class="moga-property-highlights__label"><?php esc_html_e('Cancellation', 'moga-travel'); ?></span>
                        </div>
                    <?php endif; ?>
                </div>


                <?php // ---- Description ----
                ?>
                <?php if ($content_clean) : ?>
                    <div class="moga-single-section" id="moga-description">
                        <h2 class="moga-single-section__title"><?php esc_html_e('About this tour', 'moga-travel'); ?></h2>
                        <div class="moga-property-description<?php echo $content_words > 60 ? ' moga-property-description--collapsed' : ''; ?>" id="moga-description-content">
                            <?php echo $content_clean; ?>
                        </div>
                        <?php if ($content_words > 60) : ?>
                            <button type="button" class="moga-read-more-btn" id="moga-description-toggle" aria-expanded="false" aria-controls="moga-description-content">
                                <?php esc_html_e('Show more', 'moga-travel'); ?>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <polyline points="6 9 12 15 18 9" />
                                </svg>
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>


                <?php // ---- Itinerary ----
                ?>
                <?php if ($has_itinerary) : ?>
                    <div class="moga-single-section" id="moga-itinerary">
                        <h2 class="moga-single-section__title"><?php esc_html_e('Itinerary', 'moga-travel'); ?></h2>
                        <?php get_template_part('template-parts/tour/itinerary'); ?>
                    </div>
                <?php endif; ?>


                <?php // ---- What's Included / Not Included ----
                ?>
                <?php if (! empty($includes_keys) || ! empty($excludes_keys) || ! empty($includes_custom) || ! empty($excludes_custom)) : ?>
                    <div class="moga-single-section" id="moga-includes">
                        <h2 class="moga-single-section__title"><?php esc_html_e("What's Included", 'moga-travel'); ?></h2>
                        <div class="moga-includes-excludes">
                            <?php if (! empty($includes_keys) || ! empty($includes_custom)) : ?>
                                <div class="moga-includes-excludes__col moga-includes-excludes__col--included">
                                    <h3 class="moga-includes-excludes__title"><?php esc_html_e('Included', 'moga-travel'); ?></h3>
                                    <ul class="moga-includes-excludes__list">
                                        <?php foreach ($includes_keys as $key) :
                                            if (! isset($all_includes[$key])) continue;
                                        ?>
                                            <li>
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                                    <polyline points="20 6 9 17 4 12" />
                                                </svg>
                                                <?php echo esc_html($all_includes[$key]); ?>
                                            </li>
                                        <?php endforeach; ?>
                                        <?php foreach ($includes_custom as $item) : ?>
                                            <li>
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                                    <polyline points="20 6 9 17 4 12" />
                                                </svg>
                                                <?php echo esc_html($item); ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            <?php if (! empty($excludes_keys) || ! empty($excludes_custom)) : ?>
                                <div class="moga-includes-excludes__col moga-includes-excludes__col--excluded">
                                    <h3 class="moga-includes-excludes__title"><?php esc_html_e('Not Included', 'moga-travel'); ?></h3>
                                    <ul class="moga-includes-excludes__list">
                                        <?php foreach ($excludes_keys as $key) :
                                            if (! isset($all_excludes[$key])) continue;
                                        ?>
                                            <li>
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                                    <line x1="18" y1="6" x2="6" y2="18" />
                                                    <line x1="6" y1="6" x2="18" y2="18" />
                                                </svg>
                                                <?php echo esc_html($all_excludes[$key]); ?>
                                            </li>
                                        <?php endforeach; ?>
                                        <?php foreach ($excludes_custom as $item) : ?>
                                            <li>
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                                    <line x1="18" y1="6" x2="6" y2="18" />
                                                    <line x1="6" y1="6" x2="18" y2="18" />
                                                </svg>
                                                <?php echo esc_html($item); ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>


                <?php // ---- Good to Know / Logistics ----
                ?>
                <div class="moga-single-section" id="moga-logistics">
                    <h2 class="moga-single-section__title"><?php esc_html_e('Good to Know', 'moga-travel'); ?></h2>
                    <div class="moga-house-rules">
                        <div class="moga-house-rule">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <circle cx="12" cy="12" r="10" />
                                <polyline points="12 6 12 12 16 14" />
                            </svg>
                            <div class="moga-house-rule__content"><span class="moga-house-rule__label"><?php esc_html_e('Departure time', 'moga-travel'); ?></span><span class="moga-house-rule__value"><?php echo esc_html($departure_time); ?></span></div>
                        </div>
                        <div class="moga-house-rule">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <circle cx="12" cy="12" r="10" />
                                <polyline points="12 6 12 12 16 14" />
                            </svg>
                            <div class="moga-house-rule__content"><span class="moga-house-rule__label"><?php esc_html_e('Estimated return', 'moga-travel'); ?></span><span class="moga-house-rule__value"><?php echo esc_html($return_time); ?></span></div>
                        </div>
                        <?php if ($departure_point) : ?>
                            <div class="moga-house-rule">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" />
                                    <circle cx="12" cy="10" r="3" />
                                </svg>
                                <div class="moga-house-rule__content"><span class="moga-house-rule__label"><?php esc_html_e('Meeting point', 'moga-travel'); ?></span><span class="moga-house-rule__value"><?php echo esc_html($departure_point); ?></span></div>
                            </div>
                        <?php endif; ?>
                        <?php if ('1' === $guide_included) : ?>
                            <div class="moga-house-rule">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                    <circle cx="9" cy="7" r="4" />
                                </svg>
                                <div class="moga-house-rule__content"><span class="moga-house-rule__label"><?php esc_html_e('Tour guide', 'moga-travel'); ?></span><span class="moga-house-rule__value"><?php esc_html_e('Included', 'moga-travel'); ?></span></div>
                            </div>
                        <?php endif; ?>
                        <?php if ($cancellation_label) : ?>
                            <div class="moga-house-rule">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                                </svg>
                                <div class="moga-house-rule__content">
                                    <span class="moga-house-rule__label"><?php esc_html_e('Cancellation policy', 'moga-travel'); ?></span>
                                    <span class="moga-house-rule__value"><?php echo esc_html($cancellation_label); ?></span>
                                    <?php if ($cancellation_desc) : ?><p class="moga-house-rule__desc"><?php echo esc_html($cancellation_desc); ?></p><?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>


                <?php // ---- Reviews ----
                ?>
                <div class="moga-single-section" id="moga-reviews">
                    <h2 class="moga-single-section__title">
                        <?php esc_html_e('Guest Reviews', 'moga-travel'); ?>
                        <?php if ($rating > 0) : ?>
                            <span class="moga-single-section__rating">
                                <span class="moga-rating-score"><?php echo esc_html(number_format($rating, 1)); ?></span>
                                <span class="moga-rating-label"><?php echo esc_html($rating_label); ?></span>
                            </span>
                        <?php endif; ?>
                    </h2>

                    <?php if ($review_count > 0) : ?>
                        <p class="moga-reviews-count">
                            <?php printf(esc_html(1 === $review_count ? __('Based on %d review', 'moga-travel') : __('Based on %d reviews', 'moga-travel')), number_format_i18n($review_count)); ?>
                        </p>

                        <?php if (! empty($cat_avgs)) : ?>
                            <div class="moga-review-categories">
                                <?php foreach ($cat_avgs as $col => $data) :
                                    $pct = ($data['avg'] / 10) * 100;
                                ?>
                                    <div class="moga-review-category">
                                        <div class="moga-review-category__header">
                                            <span class="moga-review-category__label"><?php echo esc_html($data['label']); ?></span>
                                            <span class="moga-review-category__score"><?php echo esc_html(number_format($data['avg'], 1)); ?></span>
                                        </div>
                                        <div class="moga-review-category__bar" role="progressbar" aria-valuenow="<?php echo esc_attr(round($data['avg'], 1)); ?>" aria-valuemin="0" aria-valuemax="10">
                                            <div class="moga-review-category__fill" style="width:<?php echo esc_attr(round($pct, 1)); ?>%"></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (! empty($amenity_totals) && ! empty($all_includes)) : ?>
                            <div class="moga-review-amenities">
                                <h3 class="moga-review-amenities__title"><?php esc_html_e('What Guests Rated', 'moga-travel'); ?></h3>
                                <div class="moga-review-categories">
                                    <?php foreach ($amenity_totals as $key => $total) :
                                        if (! isset($all_includes[$key])) continue;
                                        $avg = $total / $amenity_counts[$key];
                                        $pct = ($avg / 10) * 100;
                                    ?>
                                        <div class="moga-review-category">
                                            <div class="moga-review-category__header">
                                                <span class="moga-review-category__label"><?php echo esc_html($all_includes[$key]); ?></span>
                                                <span class="moga-review-category__score"><?php echo esc_html(number_format($avg, 1)); ?></span>
                                            </div>
                                            <div class="moga-review-category__bar" role="progressbar" aria-valuenow="<?php echo esc_attr(round($avg, 1)); ?>" aria-valuemin="0" aria-valuemax="10">
                                                <div class="moga-review-category__fill" style="width:<?php echo esc_attr(round($pct, 1)); ?>%"></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (! empty($reviews)) : ?>
                            <div class="moga-review-cards">
                                <?php foreach ($reviews as $review) :
                                    $guest      = get_userdata($review->guest_id);
                                    $guest_name = $guest ? $guest->display_name : __('Anonymous', 'moga-travel');
                                    $avatar_url = $guest ? get_avatar_url($review->guest_id, array('size' => 48)) : '';
                                ?>
                                    <div class="moga-review-card">
                                        <div class="moga-review-card__header">
                                            <?php if ($avatar_url) : ?>
                                                <img src="<?php echo esc_url($avatar_url); ?>" alt="<?php echo esc_attr($guest_name); ?>" class="moga-review-card__avatar" width="48" height="48">
                                            <?php else : ?>
                                                <div class="moga-review-card__avatar moga-review-card__avatar--placeholder"><?php echo esc_html(mb_substr($guest_name, 0, 1)); ?></div>
                                            <?php endif; ?>
                                            <div class="moga-review-card__meta">
                                                <span class="moga-review-card__name"><?php echo esc_html($guest_name); ?></span>
                                                <span class="moga-review-card__date"><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($review->created_at))); ?></span>
                                            </div>
                                            <span class="moga-review-card__score"><?php echo esc_html(number_format($review->rating_overall, 1)); ?></span>
                                        </div>
                                        <?php if ($review->title) : ?><h4 class="moga-review-card__title"><?php echo esc_html($review->title); ?></h4><?php endif; ?>
                                        <?php if ($review->content) : ?><p class="moga-review-card__content"><?php echo esc_html($review->content); ?></p><?php endif; ?>
                                        <?php if ($review->owner_reply) : ?>
                                            <div class="moga-review-card__reply">
                                                <strong><?php esc_html_e('Organizer response:', 'moga-travel'); ?></strong>
                                                <p><?php echo esc_html($review->owner_reply); ?></p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                    <?php else : ?>
                        <p class="moga-no-reviews"><?php esc_html_e('No reviews yet. Be the first to review this tour!', 'moga-travel'); ?></p>
                    <?php endif; ?>
                </div>

            </div>
            <?php // ---- End LEFT Column ----
            ?>


            <?php // ---- RIGHT: Booking Form Sidebar ----
            ?>
            <div class="moga-single-sidebar">
                <div class="moga-booking-sidebar" id="moga-booking-sidebar">

                    <?php // ---- Organizer Card ----
                    ?>
                    <?php if ($organizer_name) : ?>
                        <div class="moga-organizer-card">
                            <?php if ($organizer_photo_url) : ?>
                                <img src="<?php echo esc_url($organizer_photo_url); ?>" alt="<?php echo esc_attr($organizer_name); ?>" class="moga-organizer-card__photo" width="56" height="56">
                            <?php else : ?>
                                <div class="moga-organizer-card__photo moga-organizer-card__photo--placeholder">
                                    <?php echo esc_html(mb_substr($organizer_name, 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                            <div class="moga-organizer-card__info">
                                <span class="moga-organizer-card__label"><?php esc_html_e('Organized by', 'moga-travel'); ?></span>
                                <span class="moga-organizer-card__name"><?php echo esc_html($organizer_name); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php // ---- Message the Organizer ----
                    ?>
                    <?php
                    get_template_part('template-parts/global/vendor-contact-form', null, array(
                        'vendor_name'     => $organizer_name,
                        'vendor_email'    => $organizer_email,
                        'vendor_phone'    => $organizer_phone,
                        'vendor_whatsapp' => $organizer_whatsapp,
                        'listing_title'   => $title,
                        'listing_type'    => 'tour',
                    ));
                    ?>

                    <?php get_template_part('template-parts/tour/booking-form'); ?>
                </div>
            </div>

        </div>
        <?php // ---- End Two-Column Layout ----
        ?>

    </div>
</main>


<?php // ---- Mobile Sticky Bottom Bar ----
?>
<div class="moga-mobile-booking-bar" id="moga-mobile-booking-bar" aria-hidden="true">
    <div class="moga-mobile-booking-bar__price">
        <?php if ($display_price['original'] > 0 && $display_price['original'] > $display_price['price']) : ?>
            <span class="moga-mobile-booking-bar__price-old"><?php echo esc_html(moga_format_price($display_price['original'], $display_price['currency'])); ?></span>
        <?php endif; ?>
        <span class="moga-mobile-booking-bar__price-current"><?php echo esc_html(moga_format_price($display_price['price'], $display_price['currency'])); ?></span>
        <span class="moga-mobile-booking-bar__price-label">/ <?php esc_html_e('person', 'moga-travel'); ?></span>
    </div>
    <a href="#moga-booking-sidebar" class="moga-btn moga-btn--primary moga-mobile-booking-bar__btn">
        <?php esc_html_e('Reserve', 'moga-travel'); ?>
    </a>
</div>

<?php get_footer(); ?>
