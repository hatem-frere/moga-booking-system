<?php
/**
 * Tour Card — List View
 *
 * Displays a single tour in horizontal list layout.
 * Used on: search results page (list mode).
 *
 * @package    MogaTravel
 * @since      1.0.0
 */

// Block direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get tour ID from current post in loop.
$tour_id = get_the_ID();

if ( ! $tour_id ) {
    return;
}

// ---- Tour Data ----
$title     = get_the_title( $tour_id );
$permalink = get_permalink( $tour_id );
$thumbnail = get_the_post_thumbnail_url( $tour_id, 'moga-card' );
$excerpt   = get_the_excerpt( $tour_id );

// Duration.
$duration_days   = intval( get_post_meta( $tour_id, '_moga_duration_days',   true ) );
$duration_nights = intval( get_post_meta( $tour_id, '_moga_duration_nights', true ) );

// Location.
$departure_city   = get_post_meta( $tour_id, '_moga_departure_city',   true );
$destination_city = get_post_meta( $tour_id, '_moga_destination_city', true );

// Difficulty.
$difficulty        = get_post_meta( $tour_id, '_moga_difficulty', true ) ?: 'easy';
$difficulty_levels = class_exists( 'Moga_CPT_Tour' )
    ? Moga_CPT_Tour::get_difficulty_levels()
    : array();
$difficulty_label  = isset( $difficulty_levels[ $difficulty ] )
    ? $difficulty_levels[ $difficulty ]['label']
    : ucfirst( $difficulty );
$difficulty_color  = isset( $difficulty_levels[ $difficulty ] )
    ? $difficulty_levels[ $difficulty ]['color']
    : '#28a745';

// Tour type.
$tour_type       = get_post_meta( $tour_id, '_moga_tour_type', true ) ?: 'group';
$tour_types      = class_exists( 'Moga_CPT_Tour' )
    ? Moga_CPT_Tour::get_tour_types()
    : array();
$tour_type_label = isset( $tour_types[ $tour_type ] )
    ? $tour_types[ $tour_type ]['label']
    : ucfirst( $tour_type );

// Participants.
$max_participants = intval( get_post_meta( $tour_id, '_moga_max_participants', true ) );

// Price.
$price    = floatval( get_post_meta( $tour_id, '_moga_price_per_person', true ) );
$currency = get_post_meta( $tour_id, '_moga_currency', true ) ?: 'USD';
$discount = floatval( get_post_meta( $tour_id, '_moga_price_group', true ) );

$original_price = $price;
if ( $discount > 0 ) {
    $price = $price - ( $price * ( $discount / 100 ) );
}

// Rating.
$rating       = floatval( get_post_meta( $tour_id, '_moga_rating',       true ) );
$review_count = intval(   get_post_meta( $tour_id, '_moga_review_count', true ) );
$rating_label = function_exists( 'moga_get_rating_label' )
    ? moga_get_rating_label( $rating )
    : '';

// Tour category taxonomy.
$tour_categories = wp_get_post_terms( $tour_id, 'moga_tour_category', array( 'fields' => 'all' ) );
$tour_category   = ! is_wp_error( $tour_categories ) && ! empty( $tour_categories )
    ? $tour_categories[0]
    : null;

// Badges.
$featured        = get_post_meta( $tour_id, '_moga_featured',        true );
$instant_booking = get_post_meta( $tour_id, '_moga_instant_booking', true );
$guide_included  = get_post_meta( $tour_id, '_moga_guide_included',  true );

// Duration label.
$duration_label = '';
if ( $duration_days > 0 ) {
    $duration_label = $duration_days . ' ' . ( 1 === $duration_days
        ? __( 'Day', 'moga-travel' )
        : __( 'Days', 'moga-travel' ) );
}
if ( $duration_nights > 0 ) {
    $duration_label .= ' / ' . $duration_nights . ' ' . ( 1 === $duration_nights
        ? __( 'Night', 'moga-travel' )
        : __( 'Nights', 'moga-travel' ) );
}

// Placeholder if no image.
if ( ! $thumbnail ) {
    $thumbnail = MOGA_THEME_URL . 'assets/images/placeholder-tour.jpg';
}
?>

<article class="moga-card moga-card--list moga-tour-card" data-id="<?php echo esc_attr( $tour_id ); ?>">

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

            <?php if ( '1' === $guide_included ) : ?>
                <span class="moga-badge moga-badge--guide">
                    🎤 <?php esc_html_e( 'Guide Included', 'moga-travel' ); ?>
                </span>
            <?php endif; ?>

            <?php if ( $discount > 0 ) : ?>
                <span class="moga-badge moga-badge--discount">
                    -<?php echo esc_html( intval( $discount ) ); ?>%
                </span>
            <?php endif; ?>
        </div>

        <?php // ---- Wishlist Button ---- ?>
        <button
            type="button"
            class="moga-card__wishlist"
            data-id="<?php echo esc_attr( $tour_id ); ?>"
            aria-label="<?php esc_attr_e( 'Save to wishlist', 'moga-travel' ); ?>"
        >
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
            </svg>
        </button>

        <?php // ---- Tour Category Tag ---- ?>
        <?php if ( $tour_category ) : ?>
            <span class="moga-card__type">
                <?php echo esc_html( $tour_category->name ); ?>
            </span>
        <?php endif; ?>

        <?php // ---- Duration Tag ---- ?>
        <?php if ( $duration_label ) : ?>
            <span class="moga-card__duration">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
                </svg>
                <?php echo esc_html( $duration_label ); ?>
            </span>
        <?php endif; ?>

    </div>

    <?php // ---- Card Content (right side) ---- ?>
    <div class="moga-card__content">

        <?php // ---- Card Body ---- ?>
        <div class="moga-card__body">

            <?php // ---- Location ---- ?>
            <?php if ( $departure_city ) : ?>
                <div class="moga-card__location">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                        <circle cx="12" cy="10" r="3"/>
                    </svg>
                    <?php
                    $location_display = $departure_city;
                    if ( $destination_city && $destination_city !== $departure_city ) {
                        $location_display .= ' → ' . $destination_city;
                    }
                    echo esc_html( $location_display );
                    ?>
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

            <?php // ---- Tour Details Row ---- ?>
            <div class="moga-card__details">

                <?php // Duration. ?>
                <?php if ( $duration_label ) : ?>
                    <span class="moga-card__detail">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                        <?php echo esc_html( $duration_label ); ?>
                    </span>
                <?php endif; ?>

                <?php // Difficulty. ?>
                <span
                    class="moga-card__detail moga-card__detail--difficulty"
                    style="color: <?php echo esc_attr( $difficulty_color ); ?>;"
                >
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                    </svg>
                    <?php echo esc_html( $difficulty_label ); ?>
                </span>

                <?php // Tour type. ?>
                <span class="moga-card__detail">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                    <?php echo esc_html( $tour_type_label ); ?>
                </span>

                <?php // Max participants. ?>
                <?php if ( $max_participants > 0 ) : ?>
                    <span class="moga-card__detail">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                        <?php echo esc_html( $max_participants ); ?>
                        <?php esc_html_e( 'Max', 'moga-travel' ); ?>
                    </span>
                <?php endif; ?>

            </div>

            <?php // ---- Rating ---- ?>
            <?php if ( $rating > 0 ) : ?>
                <div class="moga-card__rating">
                    <span class="moga-card__rating-score">
                        <?php echo esc_html( number_format( $rating, 1 ) ); ?>
                    </span>
                    <span class="moga-card__rating-label">
                        <?php echo esc_html( $rating_label ); ?>
                    </span>
                    <?php if ( $review_count > 0 ) : ?>
                        <span class="moga-card__rating-count">
                            (<?php echo esc_html( number_format_i18n( $review_count ) ); ?>
                            <?php echo esc_html( 1 === $review_count
                                ? __( 'review', 'moga-travel' )
                                : __( 'reviews', 'moga-travel' ) ); ?>)
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </div>

        <?php // ---- Card Footer ---- ?>
        <div class="moga-card__footer">

            <?php // ---- Price ---- ?>
            <div class="moga-card__price-wrap">
                <?php if ( $discount > 0 ) : ?>
                    <span class="moga-card__price-old">
                        <?php echo esc_html( moga_format_price( $original_price, $currency ) ); ?>
                    </span>
                <?php endif; ?>
                <span class="moga-card__price">
                    <?php echo esc_html( moga_format_price( $price, $currency ) ); ?>
                </span>
                <span class="moga-card__price-label">
                    / <?php esc_html_e( 'person', 'moga-travel' ); ?>
                </span>
            </div>

            <?php // ---- View Tour Button ---- ?>
            
               <a href="<?php echo esc_url( $permalink ); ?>"
                class="moga-btn moga-btn--primary moga-btn--sm moga-card__btn"
            >
                <?php esc_html_e( 'View Tour', 'moga-travel' ); ?>
            </a>

        </div>

    </div>
    <?php // ---- End Card Content ---- ?>

</article>