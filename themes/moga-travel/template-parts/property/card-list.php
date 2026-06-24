<?php
/**
 * Property Card — List View
 *
 * Displays a single property in horizontal list layout.
 * Used on: search results page (list mode).
 *
 * @package    MogaTravel
 * @since      1.0.0
 */

// Block direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get property ID from current post in loop.
$property_id = get_the_ID();

if ( ! $property_id ) {
    return;
}

// ---- Property Data ----
$title     = get_the_title( $property_id );
$permalink = get_permalink( $property_id );
$thumbnail = get_the_post_thumbnail_url( $property_id, 'moga-card' );
$excerpt   = get_the_excerpt( $property_id );

// Location.
$city     = get_post_meta( $property_id, '_moga_city',         true );
$country  = get_post_meta( $property_id, '_moga_country_name', true );
$district = get_post_meta( $property_id, '_moga_district',     true );

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

// Property type taxonomy.
$property_types = wp_get_post_terms( $property_id, 'moga_property_type', array( 'fields' => 'all' ) );
$property_type  = ! is_wp_error( $property_types ) && ! empty( $property_types )
    ? $property_types[0]
    : null;

// Badges.
$featured        = get_post_meta( $property_id, '_moga_featured',        true );
$instant_booking = get_post_meta( $property_id, '_moga_instant_booking', true );
$cancellation    = get_post_meta( $property_id, '_moga_cancellation',    true );

// Location label.
$location_parts = array_filter( array( $district, $city, $country ) );
$location_label = implode( ', ', $location_parts );

// Placeholder if no image.
if ( ! $thumbnail ) {
    $thumbnail = MOGA_THEME_URL . 'assets/images/placeholder-property.jpg';
}
?>

<article class="moga-card moga-card--list moga-property-card" data-id="<?php echo esc_attr( $property_id ); ?>">

    <?php // ---- Card Image ---- ?>
    <div class="moga-card__image-wrap">
        <a href="<?php echo esc_url( $permalink ); ?>" class="moga-card__image-link">
            <img
                src="<?php echo esc_url( $thumbnail ); ?>"
                alt="<?php echo esc_attr( $title ); ?>"
                class="moga-card__image"
                loading="lazy"
            >
        </a>

        <?php // ---- Badges ---- ?>
        <div class="moga-card__badges">
            <?php if ( '1' === $featured ) : ?>
                <span class="moga-badge moga-badge--featured">
                    ⭐ <?php esc_html_e( 'Featured', 'moga-travel' ); ?>
                </span>
            <?php endif; ?>

            <?php if ( '1' === $instant_booking ) : ?>
                <span class="moga-badge moga-badge--instant">
                    ⚡ <?php esc_html_e( 'Instant Booking', 'moga-travel' ); ?>
                </span>
            <?php endif; ?>

            <?php if ( 'free' === $cancellation ) : ?>
                <span class="moga-badge moga-badge--free-cancel">
                    ✅ <?php esc_html_e( 'Free Cancellation', 'moga-travel' ); ?>
                </span>
            <?php endif; ?>

            <?php if ( $display_price['discount'] > 0 ) : ?>
                <span class="moga-badge moga-badge--discount">
                    -<?php echo esc_html( intval( $display_price['discount'] ) ); ?>%
                </span>
            <?php endif; ?>
        </div>

        <?php // ---- Wishlist Button ---- ?>
        <button
            type="button"
            class="moga-card__wishlist"
            data-id="<?php echo esc_attr( $property_id ); ?>"
            aria-label="<?php esc_attr_e( 'Save to wishlist', 'moga-travel' ); ?>"
        >
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
            </svg>
        </button>

        <?php // ---- Property Type Tag ---- ?>
        <?php if ( $property_type ) :
            $type_emoji = get_term_meta( $property_type->term_id, 'moga_emoji', true );
        ?>
            <span class="moga-card__type">
                <?php if ( $type_emoji ) echo esc_html( $type_emoji ) . ' '; ?>
                <?php echo esc_html( $property_type->name ); ?>
            </span>
        <?php endif; ?>
    </div>
    <?php // ---- Card Content (right side) ---- ?>
    <div class="moga-card__content">

        <?php // ---- Card Body ---- ?>
        <div class="moga-card__body">

            <?php // ---- Location ---- ?>
            <?php if ( $location_label ) : ?>
                <div class="moga-card__location">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                        <circle cx="12" cy="10" r="3"/>
                    </svg>
                    <?php echo esc_html( $location_label ); ?>
                </div>
            <?php endif; ?>

            <?php // ---- Title ---- ?>
            <h3 class="moga-card__title">
                <a href="<?php echo esc_url( $permalink ); ?>">
                    <?php echo esc_html( $title ); ?>
                </a>
            </h3>

            <?php // ---- Excerpt ---- ?>
            <?php if ( $excerpt ) : ?>
                <p class="moga-card__excerpt">
                    <?php echo esc_html( wp_trim_words( $excerpt, 20, '...' ) ); ?>
                </p>
            <?php endif; ?>

            <?php // ---- Property Details (icons row) ---- ?>
            <div class="moga-card__details">

                <?php if ( $bedrooms > 0 ) : ?>
                    <span class="moga-card__detail">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                            <polyline points="9 22 9 12 15 12 15 22"/>
                        </svg>
                        <?php echo esc_html( $bedrooms ); ?>
                        <?php echo esc_html( 1 === $bedrooms ? __( 'Bedroom', 'moga-travel' ) : __( 'Bedrooms', 'moga-travel' ) ); ?>
                    </span>
                <?php endif; ?>

                <?php if ( $bathrooms > 0 ) : ?>
                    <span class="moga-card__detail">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 12h16a1 1 0 0 1 1 1v3a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4v-3a1 1 0 0 1 1-1z"/>
                            <path d="M6 12V5a2 2 0 0 1 2-2h3v2.25"/>
                        </svg>
                        <?php echo esc_html( $bathrooms ); ?>
                        <?php echo esc_html( 1 === $bathrooms ? __( 'Bath', 'moga-travel' ) : __( 'Baths', 'moga-travel' ) ); ?>
                    </span>
                <?php endif; ?>

                <?php if ( $max_guests > 0 ) : ?>
                    <span class="moga-card__detail">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                        <?php echo esc_html( $max_guests ); ?>
                        <?php echo esc_html( 1 === $max_guests ? __( 'Guest', 'moga-travel' ) : __( 'Guests', 'moga-travel' ) ); ?>
                    </span>
                <?php endif; ?>

            </div>

            <?php // ---- Rating ---- ?>
            <?php if ( $rating > 0 ) : ?>
                <div class="moga-card__rating">
                    <span class="moga-card__rating-score"><?php echo esc_html( number_format( $rating, 1 ) ); ?></span>
                    <span class="moga-card__rating-label"><?php echo esc_html( $rating_label ); ?></span>
                    <?php if ( $review_count > 0 ) : ?>
                        <span class="moga-card__rating-count">
                            (<?php echo esc_html( number_format_i18n( $review_count ) ); ?>
                            <?php echo esc_html( 1 === $review_count ? __( 'review', 'moga-travel' ) : __( 'reviews', 'moga-travel' ) ); ?>)
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </div>
        <?php // ---- Card Footer ---- ?>
        <div class="moga-card__footer">

            <?php // ---- Price ---- ?>
            <div class="moga-card__price-wrap">
                <?php if ( $display_price['original'] > 0 ) : ?>
                    <span class="moga-card__price-old">
                        <?php echo esc_html( moga_format_price( $display_price['original'], $display_price['currency'] ) ); ?>
                    </span>
                <?php endif; ?>
                <span class="moga-card__price">
                    <?php echo esc_html( moga_format_price( $display_price['price'], $display_price['currency'] ) ); ?>
                </span>
                <span class="moga-card__price-label">
                    / <?php esc_html_e( 'night', 'moga-travel' ); ?>
                </span>
            </div>

            <?php // ---- View Details Button ---- ?>
            
               <a href="<?php echo esc_url( $permalink ); ?>"
                class="moga-btn moga-btn--primary moga-btn--sm moga-card__btn"
            >
                <?php esc_html_e( 'View Details', 'moga-travel' ); ?>
            </a>

        </div>

    </div>
    <?php // ---- End Card Content ---- ?>

</article>