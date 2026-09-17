<?php
/**
 * Tour Accommodation Widget
 *
 * Path: themes/moga-travel/template-parts/tour/accommodation-widget.php
 *
 * Displays the hotels for the current tour's departure groups in the
 * right sidebar of the single tour page. Shows hotels from the first
 * available group on page load. Updates via JS when the guest selects
 * a different departure from the booking form dropdown.
 *
 * Hotel photos and Google ratings are fetched from Google Places API
 * using the hotel name entered by the organizer. The API key is stored
 * as WordPress option 'moga_google_places_api_key'. If no key is set,
 * or if Google finds no match, the widget shows the hotel name and
 * star rating only with a placeholder image.
 *
 * @package MogaTravel
 * @since   1.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

$tour_id = $args['tour_id'] ?? get_the_ID();
$groups  = $args['groups']  ?? array();

if (empty($groups)) {
    return;
}

// Build a map of group start date → accommodation array.
// Passed to JS as JSON so the widget can update without a page reload.
$groups_accommodation = array();
foreach ($groups as $g) {
    if (! empty($g['start']) && ! empty($g['accommodation'])) {
        // Include photo_ids per hotel so the JS widget can use organizer-uploaded
        // photos as a fallback when Google Places returns no photos.
        $stays_with_photos = array();
        foreach ($g['accommodation'] as $stay) {
            $place_id_val = $stay['place_id'] ?? '';

            // Resolve attachment IDs to URLs.
            $uploaded_urls = array_values( array_filter( array_map( function($id) {
                $src = wp_get_attachment_image_src( absint($id), 'large' );
                return $src ? $src[0] : '';
            }, ! empty($stay['photo_ids']) ? (array) $stay['photo_ids'] : array() ) ) );

            // URL-based photos stored directly as strings.
            $url_photos = ! empty($stay['photo_urls']) && is_array($stay['photo_urls'])
                ? array_values( array_filter( $stay['photo_urls'] ) )
                : array();

            // Merge both sources — uploaded first, then URL-based.
            $all_organizer_photos = array_merge($uploaded_urls, $url_photos);

            $stays_with_photos[] = array(
                'hotel_name' => $stay['hotel_name'] ?? '',
                'stars'      => $stay['stars']      ?? 4,
                'night_from' => $stay['night_from'] ?? 1,
                'night_to'   => $stay['night_to']   ?? 1,
                'place_id'   => $place_id_val,
                'maps_url'   => $place_id_val
                    ? 'https://www.google.com/maps/place/?q=place_id:' . rawurlencode( $place_id_val )
                    : '',
                'photo_urls' => $all_organizer_photos,
            );
        }
        $groups_accommodation[ $g['start'] ] = $stays_with_photos;
    }
}

if (empty($groups_accommodation)) {
    return;
}

// Default: first group that has accommodation.
$default_date  = array_key_first($groups_accommodation);
$default_stays = $groups_accommodation[ $default_date ];

// Google Places API key.
$api_key = get_option('moga_google_places_api_key', '');
?>

<div class="moga-accommodation-widget" id="moga-accommodation-widget"
    data-api-key="<?php echo esc_attr($api_key ? 'set' : ''); ?>">

    <h3 class="moga-accommodation-widget__title">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
            <polyline points="9 22 9 12 15 12 15 22"/>
        </svg>
        <?php esc_html_e('Accommodation', 'moga-travel'); ?>
    </h3>

    <?php // Count valid hotels to decide whether to show arrows. ?>
    <?php $hotel_count = count(array_filter($default_stays, fn($s) => !empty($s['hotel_name']))); ?>

    <div class="moga-accommodation-widget__scroll-wrap">
        <?php if ($hotel_count > 1) : ?>
        <button type="button"
            class="moga-accommodation-widget__arrow moga-accommodation-widget__arrow--prev"
            aria-label="<?php esc_attr_e('Previous hotel', 'moga-travel'); ?>"
            id="moga-accom-arrow-prev">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                <polyline points="15 18 9 12 15 6"/>
            </svg>
        </button>
        <?php endif; ?>

        <div class="moga-accommodation-widget__list" id="moga-accommodation-list">
            <?php foreach ($default_stays as $stay) :
                if (empty($stay['hotel_name'])) continue;
                $stars     = max(1, min(5, intval($stay['stars'] ?? 4)));
                $night_from = intval($stay['night_from'] ?? 1);
                $night_to   = intval($stay['night_to']   ?? 1);
                $nights_label = $night_from === $night_to
                    ? sprintf(__('Night %d', 'moga-travel'), $night_from)
                    : sprintf(__('Nights %d–%d', 'moga-travel'), $night_from, $night_to);
            ?>
                <div class="moga-accommodation-card"
                    data-hotel="<?php echo esc_attr($stay['hotel_name']); ?>"
                    data-stars="<?php echo esc_attr($stars); ?>"
                    data-night-label="<?php echo esc_attr($nights_label); ?>">

                    <div class="moga-accommodation-card__gallery swiper" id="moga-hotel-swiper-<?php echo esc_attr(sanitize_title($stay['hotel_name'])); ?>">
                        <div class="swiper-wrapper">
                            <div class="swiper-slide moga-accommodation-card__placeholder">
                                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                                    <polyline points="9 22 9 12 15 12 15 22"/>
                                </svg>
                            </div>
                        </div>
                        <div class="swiper-pagination"></div>
                        <div class="swiper-button-prev"></div>
                        <div class="swiper-button-next"></div>
                    </div>

                    <div class="moga-accommodation-card__body">
                        <span class="moga-accommodation-card__nights"><?php echo esc_html($nights_label); ?></span>
                        <h4 class="moga-accommodation-card__name"><?php echo esc_html($stay['hotel_name']); ?></h4>
                        <div class="moga-accommodation-card__meta">
                            <span class="moga-accommodation-card__stars">
                                <?php echo esc_html(str_repeat('★', $stars) . str_repeat('☆', 5 - $stars)); ?>
                            </span>
                            <span class="moga-accommodation-card__rating" id="moga-rating-<?php echo esc_attr(sanitize_title($stay['hotel_name'])); ?>">
                            </span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($hotel_count > 1) : ?>
        <button type="button"
            class="moga-accommodation-widget__arrow moga-accommodation-widget__arrow--next"
            aria-label="<?php esc_attr_e('Next hotel', 'moga-travel'); ?>"
            id="moga-accom-arrow-next">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                <polyline points="9 18 15 12 9 6"/>
            </svg>
        </button>
        <?php endif; ?>
    </div>

</div>

<?php // Pass groups accommodation data to JS. ?>
<script type="application/json" id="moga-accommodation-config">
<?php echo wp_json_encode(array(
    'groups'     => $groups_accommodation,
    'defaultDate' => $default_date,
    'apiKey'      => $api_key,
    'ajaxUrl'     => admin_url('admin-ajax.php'),
    'nonce'       => wp_create_nonce('moga_nonce'),
)); ?>
</script>
