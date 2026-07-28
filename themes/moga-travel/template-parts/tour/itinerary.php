<?php
/**
 * Tour Itinerary — Day-by-Day Timeline
 *
 * Path: themes/moga-travel/template-parts/tour/itinerary.php
 *
 * Renders a Booking.com/GetYourGuide-style vertical timeline,
 * one entry per day, reading from the '_moga_itinerary' post meta.
 *
 * JSON SCHEMA for '_moga_itinerary' (array of day objects):
 * [
 *   {
 *     "day":           1,                                   // required, int
 *     "title":         "Cairo Pyramids & Sphinx",            // required, string
 *     "location":      "Giza, Cairo",                        // optional, string
 *     "duration":      "Full day",                           // optional, string — free text (e.g. "3 hours", "Full day")
 *     "description":   "Long-form description text...",      // optional, string
 *     "meals":         ["breakfast", "lunch"],                // optional, subset of: breakfast | lunch | dinner
 *     "accommodation": "4-star hotel in Cairo (or similar)",  // optional, string — shown only for multi-day tours
 *     "activities":    ["Great Pyramid of Giza", "Sphinx"]    // optional, array of strings — shown as bullet chips
 *   },
 *   ...
 * ]
 *
 * Entries are sorted by "day" ascending regardless of JSON order.
 * Note: there is currently no admin meta box writing this JSON —
 * it must be added before real itinerary data can be entered.
 *
 * @package MogaTravel
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$tour_id = get_the_ID();

$itinerary_json = get_post_meta( $tour_id, '_moga_itinerary', true );
$itinerary       = $itinerary_json ? json_decode( $itinerary_json, true ) : array();

if ( empty( $itinerary ) || ! is_array( $itinerary ) ) {
    return; // No itinerary data — render nothing.
}

// Sort by day ascending, defensively (admin input order not guaranteed).
usort( $itinerary, function( $a, $b ) {
    return ( intval( $a['day'] ?? 0 ) ) <=> ( intval( $b['day'] ?? 0 ) );
} );

$meal_labels = array(
    'breakfast' => __( 'Breakfast', 'moga-travel' ),
    'lunch'     => __( 'Lunch',     'moga-travel' ),
    'dinner'    => __( 'Dinner',    'moga-travel' ),
);

$meal_icons = array(
    'breakfast' => '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>',
    'lunch'     => '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 2v7c0 1.1.9 2 2 2h1a2 2 0 0 0 2-2V2M7 2v20M17 2a5 5 0 0 0-5 5v6h10V7a5 5 0 0 0-5-5zM17 13v9"/></svg>',
    'dinner'    => '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 2C8 2 6 6 6 10c0 3 2 5 4 6v6h4v-6c2-1 4-3 4-6 0-4-2-8-6-8z"/></svg>',
);

$total_days = count( $itinerary );
?>

<div class="moga-itinerary" id="moga-itinerary-list">

    <?php foreach ( $itinerary as $index => $day ) :

        $day_number   = intval( $day['day'] ?? ( $index + 1 ) );
        $day_title    = isset( $day['title'] ) ? sanitize_text_field( $day['title'] ) : '';
        $day_location = isset( $day['location'] ) ? sanitize_text_field( $day['location'] ) : '';
        $day_duration = isset( $day['duration'] ) ? sanitize_text_field( $day['duration'] ) : '';
        $day_desc     = isset( $day['description'] ) ? wp_kses_post( $day['description'] ) : '';
        $day_meals    = ! empty( $day['meals'] ) && is_array( $day['meals'] ) ? $day['meals'] : array();
        $day_stay     = isset( $day['accommodation'] ) ? sanitize_text_field( $day['accommodation'] ) : '';
        $day_activities = ! empty( $day['activities'] ) && is_array( $day['activities'] ) ? $day['activities'] : array();
        $is_last      = ( $index === $total_days - 1 );

        if ( ! $day_title ) {
            continue;
        }
    ?>

        <div class="moga-itinerary-day<?php echo $is_last ? ' moga-itinerary-day--last' : ''; ?>">

            <?php // ---- Timeline marker: day number + connecting line ---- ?>
            <div class="moga-itinerary-day__marker" aria-hidden="true">
                <span class="moga-itinerary-day__number"><?php echo esc_html( $day_number ); ?></span>
                <?php if ( ! $is_last ) : ?>
                    <span class="moga-itinerary-day__line"></span>
                <?php endif; ?>
            </div>

            <?php // ---- Content card ---- ?>
            <div class="moga-itinerary-day__content">

                <div class="moga-itinerary-day__header">
                    <span class="moga-itinerary-day__label">
                        <?php printf( esc_html__( 'Day %d', 'moga-travel' ), $day_number ); ?>
                    </span>
                    <h3 class="moga-itinerary-day__title"><?php echo esc_html( $day_title ); ?></h3>
                </div>

                <?php if ( $day_location || $day_duration ) : ?>
                    <div class="moga-itinerary-day__meta">
                        <?php if ( $day_location ) : ?>
                            <span class="moga-itinerary-day__meta-item">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                <?php echo esc_html( $day_location ); ?>
                            </span>
                        <?php endif; ?>
                        <?php if ( $day_duration ) : ?>
                            <span class="moga-itinerary-day__meta-item">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                <?php echo esc_html( $day_duration ); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if ( $day_desc ) : ?>
                    <div class="moga-itinerary-day__desc"><?php echo wpautop( $day_desc ); ?></div>
                <?php endif; ?>

                <?php if ( ! empty( $day_activities ) ) : ?>
                    <ul class="moga-itinerary-day__activities">
                        <?php foreach ( $day_activities as $activity ) :
                            $activity = sanitize_text_field( $activity );
                            if ( ! $activity ) continue;
                        ?>
                            <li>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                                <?php echo esc_html( $activity ); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <?php if ( ! empty( $day_meals ) || $day_stay ) : ?>
                    <div class="moga-itinerary-day__footer">
                        <?php foreach ( $day_meals as $meal ) :
                            if ( ! isset( $meal_labels[ $meal ] ) ) continue;
                        ?>
                            <span class="moga-itinerary-day__badge">
                                <?php echo $meal_icons[ $meal ]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                <?php echo esc_html( $meal_labels[ $meal ] ); ?>
                            </span>
                        <?php endforeach; ?>
                        <?php if ( $day_stay ) : ?>
                            <span class="moga-itinerary-day__badge moga-itinerary-day__badge--stay">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 21V8a2 2 0 0 1 2-2h4V4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2h4a2 2 0 0 1 2 2v13"/><line x1="3" y1="21" x2="21" y2="21"/></svg>
                                <?php echo esc_html( $day_stay ); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            </div>
            <?php // ---- End Content card ---- ?>

        </div>

    <?php endforeach; ?>

</div>