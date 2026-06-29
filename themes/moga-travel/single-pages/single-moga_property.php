<?php
/**
 * Single Property Page Template
 *
 * Path: themes/moga-travel/single-pages/single-moga_property.php
 *
 * Loaded via template_include filter in theme-hooks.php.
 *
 * @package MogaTravel
 * @since   1.0.0
 */

get_header();

if ( ! have_posts() ) {
    get_footer();
    return;
}

the_post();
$property_id = get_the_ID();


// ============================================================
// 01. DATA COLLECTION
// ============================================================

$title   = get_the_title();
$content = get_the_content();

// Location.
$city      = get_post_meta( $property_id, '_moga_city',         true );
$country   = get_post_meta( $property_id, '_moga_country_name', true );
$district  = get_post_meta( $property_id, '_moga_district',     true );
$address   = get_post_meta( $property_id, '_moga_address',      true );
$latitude  = get_post_meta( $property_id, '_moga_latitude',     true );
$longitude = get_post_meta( $property_id, '_moga_longitude',    true );

$location_parts = array_filter( array( $district, $city, $country ) );
$location_label = implode( ', ', $location_parts );

// Price.
$display_price = function_exists( 'moga_get_property_display_price' )
    ? moga_get_property_display_price( $property_id )
    : array( 'price' => 0, 'original' => 0, 'currency' => 'USD', 'discount' => 0 );

// Rating.
$rating       = floatval( get_post_meta( $property_id, '_moga_rating',       true ) );
$review_count = intval(   get_post_meta( $property_id, '_moga_review_count', true ) );
$rating_label = function_exists( 'moga_get_rating_label' ) ? moga_get_rating_label( $rating ) : '';

// Details.
$max_guests = intval(   get_post_meta( $property_id, '_moga_max_guests', true ) );
$bedrooms   = intval(   get_post_meta( $property_id, '_moga_bedrooms',   true ) );
$bathrooms  = floatval( get_post_meta( $property_id, '_moga_bathrooms',  true ) );
$area       = floatval( get_post_meta( $property_id, '_moga_area',       true ) );

// Booking rules.
$min_stay      = intval( get_post_meta( $property_id, '_moga_min_stay',      true ) ) ?: 1;
$max_stay      = intval( get_post_meta( $property_id, '_moga_max_stay',      true ) );
$checkin_time  = get_post_meta( $property_id, '_moga_checkin_time',  true ) ?: '14:00';
$checkout_time = get_post_meta( $property_id, '_moga_checkout_time', true ) ?: '11:00';
$cancellation  = get_post_meta( $property_id, '_moga_cancellation',  true ) ?: 'moderate';

$cancellation_policies = class_exists( 'Moga_CPT_Property' )
    ? Moga_CPT_Property::get_cancellation_policies() : array();
$cancellation_label = isset( $cancellation_policies[ $cancellation ] )
    ? $cancellation_policies[ $cancellation ]['label'] : '';
$cancellation_desc  = isset( $cancellation_policies[ $cancellation ] )
    ? $cancellation_policies[ $cancellation ]['desc']  : '';

// Status.
$instant_booking = get_post_meta( $property_id, '_moga_instant_booking', true );

// Amenities.
$amenities_json = get_post_meta( $property_id, '_moga_amenities', true );
$amenities      = $amenities_json ? json_decode( $amenities_json, true ) : array();
$all_amenities  = class_exists( 'Moga_CPT_Property' )
    ? Moga_CPT_Property::get_amenities() : array();

// Property type.
$property_types = wp_get_post_terms( $property_id, 'moga_property_type', array( 'fields' => 'all' ) );
$property_type  = ! is_wp_error( $property_types ) && ! empty( $property_types )
    ? $property_types[0] : null;

// Description.
$content_clean = wp_kses_post( wpautop( wp_unslash( $content ) ) );
$content_words = str_word_count( strip_tags( $content ) );

// Reviews data for gallery sidebar.
global $wpdb;
$reviews_table  = $wpdb->prefix . 'moga_reviews';
$reviews        = array();
$cat_avgs       = array();
$amenity_totals = array();
$amenity_counts = array();

if ( $review_count > 0 ) {
    $reviews = $wpdb->get_results( $wpdb->prepare(
        "SELECT * FROM {$reviews_table}
         WHERE listing_id = %d AND listing_type = 'property' AND status = 'approved'
         ORDER BY created_at DESC LIMIT 10",
        $property_id
    ) );

    // Calculate category averages for sidebar.
    $categories = array(
        'rating_cleanliness' => __( 'Cleanliness',     'moga-travel' ),
        'rating_location'    => __( 'Location',        'moga-travel' ),
        'rating_value'       => __( 'Value for money', 'moga-travel' ),
        'rating_service'     => __( 'Staff',           'moga-travel' ),
        'rating_comfort'     => __( 'Comfort',         'moga-travel' ),
        'rating_facilities'  => __( 'Facilities',      'moga-travel' ),
    );

    $cat_totals = array_fill_keys( array_keys( $categories ), 0 );
    $cat_counts = array_fill_keys( array_keys( $categories ), 0 );
    $amenity_totals = array();
    $amenity_counts = array();

    foreach ( $reviews as $review ) {
        foreach ( $categories as $col => $label ) {
            if ( ! is_null( $review->$col ) && $review->$col > 0 ) {
                $cat_totals[ $col ] += floatval( $review->$col );
                $cat_counts[ $col ]++;
            }
        }
        if ( ! empty( $review->rating_amenities ) ) {
            $scores = json_decode( $review->rating_amenities, true );
            if ( is_array( $scores ) ) {
                foreach ( $scores as $key => $score ) {
                    $amenity_totals[ $key ] = ( $amenity_totals[ $key ] ?? 0 ) + floatval( $score );
                    $amenity_counts[ $key ] = ( $amenity_counts[ $key ] ?? 0 ) + 1;
                }
            }
        }
    }

    foreach ( $categories as $col => $label ) {
        if ( $cat_counts[ $col ] > 0 ) {
            $cat_avgs[ $col ] = array(
                'label' => $label,
                'avg'   => $cat_totals[ $col ] / $cat_counts[ $col ],
            );
        }
    }
}

// Featured review for sidebar quote.
$featured_review = ! empty( $reviews ) ? $reviews[0] : null;

// Section nav.
$section_nav = array( 'moga-description' => __( 'Overview', 'moga-travel' ) );
if ( ! empty( $amenities ) ) {
    $section_nav['moga-amenities'] = __( 'Facilities', 'moga-travel' );
}
$section_nav['moga-house-rules'] = __( 'House Rules', 'moga-travel' );
$section_nav['moga-reviews']     = __( 'Reviews', 'moga-travel' );
?>

<main id="moga-main" class="moga-main moga-property-single">
    <div class="moga-container">


        <?php // ---- Breadcrumb ---- ?>
        <nav class="moga-breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'moga-travel' ); ?>">
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'moga-travel' ); ?></a>
            <span class="moga-breadcrumb__sep" aria-hidden="true">›</span>
            <a href="<?php echo esc_url( add_query_arg( 'type', 'property', home_url( '/search-results/' ) ) ); ?>"><?php esc_html_e( 'Properties', 'moga-travel' ); ?></a>
            <?php if ( $city ) : ?>
                <span class="moga-breadcrumb__sep" aria-hidden="true">›</span>
                <a href="<?php echo esc_url( add_query_arg( array( 'type' => 'property', 'location' => $city ), home_url( '/search-results/' ) ) ); ?>"><?php echo esc_html( $city ); ?></a>
            <?php endif; ?>
            <span class="moga-breadcrumb__sep" aria-hidden="true">›</span>
            <span aria-current="page"><?php echo esc_html( $title ); ?></span>
        </nav>


        <?php // ---- Section Navigation ---- ?>
        <nav class="moga-section-nav" aria-label="<?php esc_attr_e( 'Page sections', 'moga-travel' ); ?>">
            <div class="moga-section-nav__inner">
                <?php foreach ( $section_nav as $anchor => $label ) : ?>
                    <a href="#<?php echo esc_attr( $anchor ); ?>" class="moga-section-nav__link">
                        <?php echo esc_html( $label ); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </nav>


        <?php // ---- Property Header ---- ?>
        <div class="moga-property-header">
            <div class="moga-property-header__main">
                <?php if ( $property_type ) : ?>
                    <span class="moga-property-header__type"><?php echo esc_html( $property_type->name ); ?></span>
                <?php endif; ?>
                <h1 class="moga-property-header__title"><?php echo esc_html( $title ); ?></h1>
                <div class="moga-property-header__meta">
                    <?php if ( $rating > 0 ) : ?>
                        <div class="moga-property-header__rating">
                            <span class="moga-rating-score"><?php echo esc_html( number_format( $rating, 1 ) ); ?></span>
                            <span class="moga-rating-label"><?php echo esc_html( $rating_label ); ?></span>
                            <?php if ( $review_count > 0 ) : ?>
                                <a href="#moga-reviews" class="moga-rating-count">
                                    <?php echo esc_html( number_format_i18n( $review_count ) ); ?>
                                    <?php echo esc_html( 1 === $review_count ? __( 'review', 'moga-travel' ) : __( 'reviews', 'moga-travel' ) ); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <?php if ( $location_label ) : ?>
                        <div class="moga-property-header__location">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                <circle cx="12" cy="10" r="3"/>
                            </svg>
                            <span><?php echo esc_html( $location_label ); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="moga-property-header__actions">
                <?php if ( '1' === $instant_booking ) : ?>
                    <span class="moga-badge moga-badge--instant">⚡ <?php esc_html_e( 'Instant Booking', 'moga-travel' ); ?></span>
                <?php endif; ?>
                <a href="#moga-booking-sidebar" class="moga-btn moga-btn--primary moga-property-header__reserve">
                    <?php esc_html_e( 'Reserve', 'moga-travel' ); ?>
                </a>
                <button type="button" class="moga-property-header__action-btn" id="moga-share-btn" aria-label="<?php esc_attr_e( 'Share property', 'moga-travel' ); ?>">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/>
                        <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/>
                        <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/>
                    </svg>
                    <?php esc_html_e( 'Share', 'moga-travel' ); ?>
                </button>
                <button type="button" class="moga-property-header__action-btn moga-wishlist-btn" data-id="<?php echo esc_attr( $property_id ); ?>" aria-label="<?php esc_attr_e( 'Save to wishlist', 'moga-travel' ); ?>">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                    </svg>
                    <?php esc_html_e( 'Save', 'moga-travel' ); ?>
                </button>
            </div>
        </div>
        <?php // ---- End Property Header ---- ?>


        <?php // ---- Gallery + Rating Sidebar ---- ?>
        <div class="moga-gallery-with-sidebar">

            <?php // Left: Gallery mosaic + thumbnail strip ?>
            <div class="moga-gallery-main">
                <?php get_template_part( 'template-parts/property/single-gallery' ); ?>
            </div>

            <?php // Right: Rating box + Map ?>
            <div class="moga-gallery-sidebar">

                <?php // Rating Box ?>
                <div class="moga-gallery-rating-box">
                    <div class="moga-gallery-rating-box__score">
                        <?php if ( $rating > 0 ) : ?>
                            <span class="moga-gallery-rating-box__badge">
                                <?php echo esc_html( number_format( $rating, 1 ) ); ?>
                            </span>
                            <div class="moga-gallery-rating-box__labels">
                                <span class="moga-gallery-rating-box__label"><?php echo esc_html( $rating_label ); ?></span>
                                <?php if ( $review_count > 0 ) : ?>
                                    <a href="#moga-reviews" class="moga-gallery-rating-box__count">
                                        <?php printf(
                                            esc_html( 1 === $review_count ? __( '%d review', 'moga-travel' ) : __( '%d reviews', 'moga-travel' ) ),
                                            number_format_i18n( $review_count )
                                        ); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php else : ?>
                            <span class="moga-gallery-rating-box__no-rating">
                                <?php esc_html_e( 'No reviews yet', 'moga-travel' ); ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <?php // Featured review quote ?>
                    <?php if ( $featured_review && $featured_review->content ) : ?>
                        <div class="moga-gallery-rating-box__quote">
                            <p class="moga-gallery-rating-box__quote-text">
                                "<?php echo esc_html( mb_substr( $featured_review->content, 0, 100 ) ); ?>…"
                            </p>
                            <?php
                            $guest = get_userdata( $featured_review->guest_id );
                            if ( $guest ) :
                            ?>
                                <span class="moga-gallery-rating-box__quote-author">
                                    <?php echo esc_html( $guest->display_name ); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php // Location score from category averages ?>
                    <?php if ( isset( $cat_avgs['rating_location'] ) ) : ?>
                        <div class="moga-gallery-rating-box__location">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                <circle cx="12" cy="10" r="3"/>
                            </svg>
                            <span class="moga-gallery-rating-box__location-label">
                                <?php
                                $loc_avg = $cat_avgs['rating_location']['avg'];
                                $loc_lbl = function_exists( 'moga_get_rating_label' ) ? moga_get_rating_label( $loc_avg ) : '';
                                echo esc_html( $loc_lbl . ' ' . __( 'location', 'moga-travel' ) );
                                ?>
                            </span>
                            <span class="moga-gallery-rating-box__location-score">
                                <?php echo esc_html( number_format( $loc_avg, 1 ) ); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
                <?php // ---- End Rating Box ---- ?>

                <?php // Map — OpenStreetMap iframe ?>
                <div class="moga-gallery-map">
                    <?php if ( $latitude && $longitude ) :
                        $lat  = floatval( $latitude );
                        $lng  = floatval( $longitude );
                        $bbox = ( $lng - 0.01 ) . ',' . ( $lat - 0.01 ) . ',' . ( $lng + 0.01 ) . ',' . ( $lat + 0.01 );
                        $map_url = add_query_arg( array(
                            'bbox'   => $bbox,
                            'layer'  => 'mapnik',
                            'marker' => $lat . ',' . $lng,
                        ), 'https://www.openstreetmap.org/export/embed.html' );
                    ?>
                        <iframe
                            src="<?php echo esc_url( $map_url ); ?>"
                            class="moga-gallery-map__iframe"
                            title="<?php printf( esc_attr__( 'Map showing location of %s', 'moga-travel' ), $title ); ?>"
                            loading="lazy"
                            referrerpolicy="no-referrer"
                        ></iframe>
                        <a
                            href="<?php echo esc_url( 'https://www.openstreetmap.org/?mlat=' . $lat . '&mlon=' . $lng . '#map=15/' . $lat . '/' . $lng ); ?>"
                            class="moga-gallery-map__link"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <?php esc_html_e( 'View larger map', 'moga-travel' ); ?>
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                                <polyline points="15 3 21 3 21 9"/>
                                <line x1="10" y1="14" x2="21" y2="3"/>
                            </svg>
                        </a>
                    <?php else : ?>
                        <div class="moga-gallery-map__placeholder">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                <circle cx="12" cy="10" r="3"/>
                            </svg>
                            <p><?php echo esc_html( $location_label ?: __( 'Location not set', 'moga-travel' ) ); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
                <?php // ---- End Map ---- ?>

            </div>
            <?php // ---- End Gallery Sidebar ---- ?>

        </div>
        <?php // ---- End Gallery + Rating Sidebar ---- ?>


        <?php // ---- Two-Column Layout ---- ?>
        <div class="moga-single-layout">

            <?php // ---- LEFT: Main Content ---- ?>
            <div class="moga-single-content">

                <?php // ---- Highlights Bar ---- ?>
                <div class="moga-property-highlights">
                    <?php if ( $bedrooms > 0 ) : ?>
                        <div class="moga-property-highlights__item">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                            <span class="moga-property-highlights__value"><?php echo esc_html( $bedrooms ); ?></span>
                            <span class="moga-property-highlights__label"><?php echo esc_html( 1 === $bedrooms ? __( 'Bedroom', 'moga-travel' ) : __( 'Bedrooms', 'moga-travel' ) ); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ( $bathrooms > 0 ) : ?>
                        <div class="moga-property-highlights__item">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M4 12h16a1 1 0 0 1 1 1v3a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4v-3a1 1 0 0 1 1-1z"/><path d="M6 12V5a2 2 0 0 1 2-2h3v2.25"/></svg>
                            <span class="moga-property-highlights__value"><?php echo esc_html( $bathrooms ); ?></span>
                            <span class="moga-property-highlights__label"><?php echo esc_html( 1 === $bathrooms ? __( 'Bathroom', 'moga-travel' ) : __( 'Bathrooms', 'moga-travel' ) ); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ( $max_guests > 0 ) : ?>
                        <div class="moga-property-highlights__item">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            <span class="moga-property-highlights__value"><?php echo esc_html( $max_guests ); ?></span>
                            <span class="moga-property-highlights__label"><?php echo esc_html( 1 === $max_guests ? __( 'Guest', 'moga-travel' ) : __( 'Guests', 'moga-travel' ) ); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ( $area > 0 ) : ?>
                        <div class="moga-property-highlights__item">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
                            <span class="moga-property-highlights__value"><?php echo esc_html( $area ); ?></span>
                            <span class="moga-property-highlights__label">m²</span>
                        </div>
                    <?php endif; ?>
                    <?php if ( $min_stay > 1 ) : ?>
                        <div class="moga-property-highlights__item">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            <span class="moga-property-highlights__value"><?php echo esc_html( $min_stay ); ?></span>
                            <span class="moga-property-highlights__label"><?php esc_html_e( 'Min. nights', 'moga-travel' ); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ( $cancellation_label ) : ?>
                        <div class="moga-property-highlights__item">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                            <span class="moga-property-highlights__value moga-property-highlights__value--sm"><?php echo esc_html( $cancellation_label ); ?></span>
                            <span class="moga-property-highlights__label"><?php esc_html_e( 'Cancellation', 'moga-travel' ); ?></span>
                        </div>
                    <?php endif; ?>
                </div>


                <?php // ---- Description ---- ?>
                <?php if ( $content_clean ) : ?>
                    <div class="moga-single-section" id="moga-description">
                        <h2 class="moga-single-section__title"><?php esc_html_e( 'About this property', 'moga-travel' ); ?></h2>
                        <div class="moga-property-description<?php echo $content_words > 60 ? ' moga-property-description--collapsed' : ''; ?>" id="moga-description-content">
                            <?php echo $content_clean; ?>
                        </div>
                        <?php if ( $content_words > 60 ) : ?>
                            <button type="button" class="moga-read-more-btn" id="moga-description-toggle" aria-expanded="false" aria-controls="moga-description-content">
                                <?php esc_html_e( 'Show more', 'moga-travel' ); ?>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>


                <?php // ---- Amenities ---- ?>
                <?php if ( ! empty( $amenities ) && ! empty( $all_amenities ) ) : ?>
                    <div class="moga-single-section" id="moga-amenities">
                        <h2 class="moga-single-section__title"><?php esc_html_e( 'Amenities', 'moga-travel' ); ?></h2>
                        <div class="moga-amenities-grid" id="moga-amenities-grid">
                            <?php
                            $shown = 0;
                            foreach ( $amenities as $key ) :
                                if ( ! isset( $all_amenities[ $key ] ) ) continue;
                                $amenity    = $all_amenities[ $key ];
                                $shown++;
                                $hidden_cls = $shown > 10 ? ' moga-amenity-item--hidden' : '';
                            ?>
                                <div class="moga-amenity-item<?php echo esc_attr( $hidden_cls ); ?>">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                                    <span><?php echo esc_html( $amenity['label'] ); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if ( count( $amenities ) > 10 ) : ?>
                            <button type="button" class="moga-read-more-btn" id="moga-amenities-toggle" aria-expanded="false" aria-controls="moga-amenities-grid">
                                <?php printf( esc_html__( 'Show all %d amenities', 'moga-travel' ), count( $amenities ) ); ?>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>


                <?php // ---- House Rules ---- ?>
                <div class="moga-single-section" id="moga-house-rules">
                    <h2 class="moga-single-section__title"><?php esc_html_e( 'House Rules', 'moga-travel' ); ?></h2>
                    <div class="moga-house-rules">
                        <div class="moga-house-rule">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            <div class="moga-house-rule__content"><span class="moga-house-rule__label"><?php esc_html_e( 'Check-in', 'moga-travel' ); ?></span><span class="moga-house-rule__value"><?php echo esc_html( $checkin_time ); ?></span></div>
                        </div>
                        <div class="moga-house-rule">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            <div class="moga-house-rule__content"><span class="moga-house-rule__label"><?php esc_html_e( 'Check-out', 'moga-travel' ); ?></span><span class="moga-house-rule__value"><?php echo esc_html( $checkout_time ); ?></span></div>
                        </div>
                        <?php if ( $min_stay > 0 ) : ?>
                            <div class="moga-house-rule">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                <div class="moga-house-rule__content"><span class="moga-house-rule__label"><?php esc_html_e( 'Minimum stay', 'moga-travel' ); ?></span><span class="moga-house-rule__value"><?php printf( esc_html( 1 === $min_stay ? __( '%d night', 'moga-travel' ) : __( '%d nights', 'moga-travel' ) ), $min_stay ); ?></span></div>
                            </div>
                        <?php endif; ?>
                        <?php if ( $max_guests > 0 ) : ?>
                            <div class="moga-house-rule">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                <div class="moga-house-rule__content"><span class="moga-house-rule__label"><?php esc_html_e( 'Max guests', 'moga-travel' ); ?></span><span class="moga-house-rule__value"><?php printf( esc_html( 1 === $max_guests ? __( '%d guest', 'moga-travel' ) : __( '%d guests', 'moga-travel' ) ), $max_guests ); ?></span></div>
                            </div>
                        <?php endif; ?>
                        <?php if ( $cancellation_label ) : ?>
                            <div class="moga-house-rule">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                <div class="moga-house-rule__content">
                                    <span class="moga-house-rule__label"><?php esc_html_e( 'Cancellation policy', 'moga-travel' ); ?></span>
                                    <span class="moga-house-rule__value"><?php echo esc_html( $cancellation_label ); ?></span>
                                    <?php if ( $cancellation_desc ) : ?><p class="moga-house-rule__desc"><?php echo esc_html( $cancellation_desc ); ?></p><?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>


                <?php // ---- Reviews ---- ?>
                <div class="moga-single-section" id="moga-reviews">
                    <h2 class="moga-single-section__title">
                        <?php esc_html_e( 'Guest Reviews', 'moga-travel' ); ?>
                        <?php if ( $rating > 0 ) : ?>
                            <span class="moga-single-section__rating">
                                <span class="moga-rating-score"><?php echo esc_html( number_format( $rating, 1 ) ); ?></span>
                                <span class="moga-rating-label"><?php echo esc_html( $rating_label ); ?></span>
                            </span>
                        <?php endif; ?>
                    </h2>

                    <?php if ( $review_count > 0 ) : ?>
                        <p class="moga-reviews-count">
                            <?php printf( esc_html( 1 === $review_count ? __( 'Based on %d review', 'moga-travel' ) : __( 'Based on %d reviews', 'moga-travel' ) ), number_format_i18n( $review_count ) ); ?>
                        </p>

                        <?php // Category score bars ?>
                        <?php if ( ! empty( $cat_avgs ) ) : ?>
                            <div class="moga-review-categories">
                                <?php foreach ( $cat_avgs as $col => $data ) :
                                    $pct = ( $data['avg'] / 10 ) * 100;
                                ?>
                                    <div class="moga-review-category">
                                        <div class="moga-review-category__header">
                                            <span class="moga-review-category__label"><?php echo esc_html( $data['label'] ); ?></span>
                                            <span class="moga-review-category__score"><?php echo esc_html( number_format( $data['avg'], 1 ) ); ?></span>
                                        </div>
                                        <div class="moga-review-category__bar" role="progressbar" aria-valuenow="<?php echo esc_attr( round( $data['avg'], 1 ) ); ?>" aria-valuemin="0" aria-valuemax="10">
                                            <div class="moga-review-category__fill" style="width:<?php echo esc_attr( round( $pct, 1 ) ); ?>%"></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php // Amenity score bars ?>
                        <?php if ( ! empty( $amenity_totals ) && ! empty( $all_amenities ) ) : ?>
                            <div class="moga-review-amenities">
                                <h3 class="moga-review-amenities__title"><?php esc_html_e( 'Amenity Ratings', 'moga-travel' ); ?></h3>
                                <div class="moga-review-categories">
                                    <?php foreach ( $amenity_totals as $key => $total ) :
                                        if ( ! isset( $all_amenities[ $key ] ) ) continue;
                                        $avg = $total / $amenity_counts[ $key ];
                                        $pct = ( $avg / 10 ) * 100;
                                    ?>
                                        <div class="moga-review-category">
                                            <div class="moga-review-category__header">
                                                <span class="moga-review-category__label"><?php echo esc_html( $all_amenities[ $key ]['label'] ); ?></span>
                                                <span class="moga-review-category__score"><?php echo esc_html( number_format( $avg, 1 ) ); ?></span>
                                            </div>
                                            <div class="moga-review-category__bar" role="progressbar" aria-valuenow="<?php echo esc_attr( round( $avg, 1 ) ); ?>" aria-valuemin="0" aria-valuemax="10">
                                                <div class="moga-review-category__fill" style="width:<?php echo esc_attr( round( $pct, 1 ) ); ?>%"></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php // Review cards ?>
                        <?php if ( ! empty( $reviews ) ) : ?>
                            <div class="moga-review-cards">
                                <?php foreach ( $reviews as $review ) :
                                    $guest      = get_userdata( $review->guest_id );
                                    $guest_name = $guest ? $guest->display_name : __( 'Anonymous', 'moga-travel' );
                                    $avatar_url = $guest ? get_avatar_url( $review->guest_id, array( 'size' => 48 ) ) : '';
                                ?>
                                    <div class="moga-review-card">
                                        <div class="moga-review-card__header">
                                            <?php if ( $avatar_url ) : ?>
                                                <img src="<?php echo esc_url( $avatar_url ); ?>" alt="<?php echo esc_attr( $guest_name ); ?>" class="moga-review-card__avatar" width="48" height="48">
                                            <?php else : ?>
                                                <div class="moga-review-card__avatar moga-review-card__avatar--placeholder"><?php echo esc_html( mb_substr( $guest_name, 0, 1 ) ); ?></div>
                                            <?php endif; ?>
                                            <div class="moga-review-card__meta">
                                                <span class="moga-review-card__name"><?php echo esc_html( $guest_name ); ?></span>
                                                <span class="moga-review-card__date"><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $review->created_at ) ) ); ?></span>
                                            </div>
                                            <span class="moga-review-card__score"><?php echo esc_html( number_format( $review->rating_overall, 1 ) ); ?></span>
                                        </div>
                                        <?php if ( $review->title ) : ?><h4 class="moga-review-card__title"><?php echo esc_html( $review->title ); ?></h4><?php endif; ?>
                                        <?php if ( $review->content ) : ?><p class="moga-review-card__content"><?php echo esc_html( $review->content ); ?></p><?php endif; ?>
                                        <?php if ( $review->owner_reply ) : ?>
                                            <div class="moga-review-card__reply">
                                                <strong><?php esc_html_e( 'Property response:', 'moga-travel' ); ?></strong>
                                                <p><?php echo esc_html( $review->owner_reply ); ?></p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                    <?php else : ?>
                        <p class="moga-no-reviews"><?php esc_html_e( 'No reviews yet. Be the first to review this property!', 'moga-travel' ); ?></p>
                    <?php endif; ?>
                </div>

            </div>
            <?php // ---- End LEFT Column ---- ?>


            <?php // ---- RIGHT: Booking Form Sidebar ---- ?>
            <div class="moga-single-sidebar">
                <div class="moga-booking-sidebar" id="moga-booking-sidebar">
                    <?php get_template_part( 'template-parts/property/booking-form' ); ?>
                </div>
            </div>

        </div>
        <?php // ---- End Two-Column Layout ---- ?>

    </div>
</main>


<?php // ---- Mobile Sticky Bottom Bar ---- ?>
<div class="moga-mobile-booking-bar" id="moga-mobile-booking-bar" aria-hidden="true">
    <div class="moga-mobile-booking-bar__price">
        <?php if ( $display_price['original'] > 0 && $display_price['original'] > $display_price['price'] ) : ?>
            <span class="moga-mobile-booking-bar__price-old"><?php echo esc_html( moga_format_price( $display_price['original'], $display_price['currency'] ) ); ?></span>
        <?php endif; ?>
        <span class="moga-mobile-booking-bar__price-current"><?php echo esc_html( moga_format_price( $display_price['price'], $display_price['currency'] ) ); ?></span>
        <span class="moga-mobile-booking-bar__price-label">/ <?php esc_html_e( 'night', 'moga-travel' ); ?></span>
    </div>
    <a href="#moga-booking-sidebar" class="moga-btn moga-btn--primary moga-mobile-booking-bar__btn">
        <?php esc_html_e( 'Reserve', 'moga-travel' ); ?>
    </a>
</div>

<?php get_footer(); ?>