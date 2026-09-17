<?php
/**
 * Tour Booking Form
 *
 * Path: themes/moga-travel/template-parts/tour/booking-form.php
 *
 * DEPARTURE SELECTOR (Sep 2026 redesign):
 * Previously used a stacked list of buttons — one per departure group.
 * Fine for 2–3 groups, broken for 10+. Replaced with a styled
 * <select> dropdown that scales to any number of departures. The
 * Flatpickr calendar remains as a secondary date-picker that stays in
 * sync with the dropdown — both directions. Selecting from the
 * dropdown moves the calendar; selecting a date on the calendar
 * updates the dropdown. Either way, the departure summary card below
 * updates with the real group's price, seats remaining, and end date.
 *
 * @package MogaTravel
 * @since   1.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

$tour_id = get_the_ID();

$currency = get_post_meta($tour_id, '_moga_currency', true) ?: get_option('moga_currency', 'USD');

$groups_json = get_post_meta($tour_id, '_moga_tour_groups', true);
$groups_raw  = $groups_json ? json_decode($groups_json, true) : array();
$groups_raw  = is_array($groups_raw) ? $groups_raw : array();

$duration_days   = intval(get_post_meta($tour_id, '_moga_duration_days',   true)) ?: 1;
$duration_nights = intval(get_post_meta($tour_id, '_moga_duration_nights', true));

$instant = get_post_meta($tour_id, '_moga_instant_booking', true);

$rating       = floatval(get_post_meta($tour_id, '_moga_rating',       true));
$review_count = intval(get_post_meta($tour_id, '_moga_review_count', true));

$cancellation    = get_post_meta($tour_id, '_moga_cancellation', true) ?: 'moderate';
$cancel_policies = class_exists('Moga_CPT_Tour') ? Moga_CPT_Tour::get_cancellation_policies() : array();
$cancel_label    = isset($cancel_policies[$cancellation]) ? $cancel_policies[$cancellation]['label'] : '';

$booking_page_url = get_option('moga_page_booking')
    ? get_permalink(get_option('moga_page_booking'))
    : home_url('/booking/');

$bus_id = intval(get_post_meta($tour_id, '_moga_bus_id', true));

// Enrich each group with end date, seats remaining, and bus seat count.
$groups = array();
foreach ($groups_raw as $g) {
    if (empty($g['start']) || ! isset($g['capacity'])) {
        continue;
    }

    $end_date = date_create($g['start']);
    if ($end_date) {
        $end_date->modify('+' . intval($duration_nights) . ' days');
        $end_str = $end_date->format('Y-m-d');
    } else {
        $end_str = $g['start'];
    }

    $seats_taken     = function_exists('moga_get_tour_group_seats_taken')
        ? moga_get_tour_group_seats_taken($tour_id, $g['start'])
        : 0;
    $seats_remaining = max(0, intval($g['capacity']) - $seats_taken);

    $bus_seats_available = null;
    $bus_seats_total     = null;
    if ($bus_id > 0) {
        $core     = function_exists('moga_core') ? moga_core() : null;
        $seat_map = ($core && $core->seat_map) ? $core->seat_map : null;
        if ($seat_map) {
            $bus_count           = $seat_map->get_available_seat_count($bus_id, $g['start']);
            $bus_seats_available = $bus_count['available'];
            $bus_seats_total     = $bus_count['total'];
        }
    }

    $groups[] = array(
        'start'               => $g['start'],
        'end'                 => $end_str,
        'price_adult'         => isset($g['price_adult'])  ? floatval($g['price_adult'])  : 0,
        'price_child'         => isset($g['price_child'])  ? floatval($g['price_child'])  : 0,
        'price_infant'        => isset($g['price_infant']) ? floatval($g['price_infant']) : 0,
        'capacity'            => intval($g['capacity']),
        'min_participants'    => isset($g['min_participants']) ? intval($g['min_participants']) : 1,
        'seats_remaining'     => $seats_remaining,
        'bus_seats_available' => $bus_seats_available,
        'bus_seats_total'     => $bus_seats_total,
    );
}

// "Starting from" price — lowest adult price across all groups.
$badge_price_adult = 0;
if (! empty($groups)) {
    $badge_price_adult = min(array_column($groups, 'price_adult'));
}

// Pre-selected group from URL (e.g. arriving from search results).
$date_val     = isset($_GET['tour_date']) ? sanitize_text_field(wp_unslash($_GET['tour_date'])) : '';
$adults_val   = isset($_GET['adults'])   ? max(1, absint($_GET['adults']))   : 1;
$children_val = isset($_GET['children']) ? max(0, absint($_GET['children'])) : 0;
$infants_val  = isset($_GET['infants'])  ? max(0, absint($_GET['infants']))  : 0;
?>

<div class="moga-booking-form-card">

    <?php // ---- Price Header ---- ?>
    <div class="moga-booking-form-card__price">
        <span class="moga-booking-form-card__price-current" id="moga-badge-price-current">
            <?php echo esc_html(moga_format_price($badge_price_adult, $currency)); ?>
        </span>
        <span class="moga-booking-form-card__price-label">
            <?php esc_html_e('/ person', 'moga-travel'); ?>
        </span>
    </div>

    <?php // ---- Rating Summary ---- ?>
    <?php if ($rating > 0) : ?>
        <div class="moga-booking-form-card__rating">
            <span class="moga-rating-score moga-rating-score--sm">
                <?php echo esc_html(number_format($rating, 1)); ?>
            </span>
            <?php if ($review_count > 0) : ?>
                <a href="#moga-reviews" class="moga-booking-form-card__rating-link">
                    <?php printf(
                        esc_html(1 === $review_count ? __('%d review', 'moga-travel') : __('%d reviews', 'moga-travel')),
                        number_format_i18n($review_count)
                    ); ?>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php // ---- Departure Selector — dropdown + Flatpickr calendar ---- ?>
    <?php if (! empty($groups)) : ?>

        <div class="moga-form-group" id="moga-departure-group">

            <label for="moga-tour-date-select">
                <?php esc_html_e('Select Departure', 'moga-travel'); ?>
            </label>

            <?php // Dropdown — primary selector. ?>
            <select id="moga-tour-date-select" class="moga-departure-select">
                <option value=""><?php esc_html_e('— Choose a departure date —', 'moga-travel'); ?></option>
                <?php foreach ($groups as $g) : ?>
                    <?php
                    $sold_out = $g['seats_remaining'] < 1;
                    $label    = moga_format_date_human($g['start']);
                    if ($g['end'] && $g['end'] !== $g['start']) {
                        $label .= ' → ' . moga_format_date_human($g['end']);
                    }
                    $label .= '  —  ' . moga_format_price($g['price_adult'], $currency) . ' ' . __('/ person', 'moga-travel');
                    if ($sold_out) {
                        $label .= '  (' . __('Sold out', 'moga-travel') . ')';
                    } elseif ($g['seats_remaining'] <= 5) {
                        $label .= '  (' . sprintf(
                            esc_html(1 === $g['seats_remaining']
                                ? __('%d spot left', 'moga-travel')
                                : __('%d spots left', 'moga-travel')),
                            $g['seats_remaining']
                        ) . ')';
                    }
                    ?>
                    <option value="<?php echo esc_attr($g['start']); ?>"
                        <?php selected($date_val, $g['start']); ?>
                        <?php disabled($sold_out, true); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <?php // Hidden input carries the raw Y-m-d value for Flatpickr + form submit. ?>
            <input type="text" id="moga-tour-date"
                value="<?php echo esc_attr($date_val); ?>"
                class="moga-tour-date-hidden"
                readonly
                aria-hidden="true"
                tabindex="-1">

            <?php // Calendar toggle button — opens Flatpickr inline. ?>
            <button type="button" class="moga-departure-calendar-btn" id="moga-tour-calendar-btn"
                aria-label="<?php esc_attr_e('Browse by calendar', 'moga-travel'); ?>"
                title="<?php esc_attr_e('Browse by calendar', 'moga-travel'); ?>">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
                <?php esc_html_e('Calendar', 'moga-travel'); ?>
            </button>

        </div>

        <?php // ---- Selected Departure Summary Card ---- ?>
        <?php // Hidden until a departure is chosen — updated by JS when selection changes. ?>
        <div class="moga-departure-summary" id="moga-departure-summary" hidden>
            <div class="moga-departure-summary__dates" id="moga-departure-summary-dates"></div>
            <div class="moga-departure-summary__meta" id="moga-departure-summary-meta"></div>
        </div>

        <?php // ---- Bus Seat Availability Indicator ---- ?>
        <?php if ($bus_id > 0) : ?>
            <div class="moga-seat-availability" id="moga-seat-availability" hidden>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <rect x="3" y="11" width="18" height="7" rx="2"/>
                    <path d="M5 11V7a7 7 0 0 1 14 0v4"/>
                    <line x1="8" y1="11" x2="8" y2="15"/>
                    <line x1="16" y1="11" x2="16" y2="15"/>
                </svg>
                <span id="moga-seat-availability-text">
                    <?php esc_html_e('Checking seat availability...', 'moga-travel'); ?>
                </span>
            </div>
        <?php endif; ?>

    <?php endif; ?>

    <form method="GET" action="<?php echo esc_url($booking_page_url); ?>" class="moga-booking-form" id="moga-tour-booking-form">

        <input type="hidden" name="listing_type" value="tour">
        <input type="hidden" name="tour_id" value="<?php echo esc_attr($tour_id); ?>">
        <input type="hidden" name="tour_date" id="moga-tour-date-input" value="<?php echo esc_attr($date_val); ?>">

        <?php // ---- Participant Counters ---- ?>
        <div class="moga-participants" id="moga-participants">

            <div class="moga-participant-row" data-type="adults">
                <div class="moga-participant-row__info">
                    <span class="moga-participant-row__label"><?php esc_html_e('Adults', 'moga-travel'); ?></span>
                    <span class="moga-participant-row__price" id="moga-price-adult-display">
                        <?php echo esc_html(moga_format_price($badge_price_adult, $currency)); ?>
                    </span>
                </div>
                <div class="moga-participant-row__controls">
                    <button type="button" class="moga-participant-btn" id="moga-adults-minus" aria-label="<?php esc_attr_e('Remove one adult', 'moga-travel'); ?>">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    </button>
                    <span class="moga-participant-count" id="moga-adults-display" aria-live="polite"><?php echo esc_html($adults_val); ?></span>
                    <input type="hidden" name="adults" id="moga-adults-input" value="<?php echo esc_attr($adults_val); ?>">
                    <button type="button" class="moga-participant-btn" id="moga-adults-plus" aria-label="<?php esc_attr_e('Add one adult', 'moga-travel'); ?>">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    </button>
                </div>
            </div>

            <div class="moga-participant-row" data-type="children">
                <div class="moga-participant-row__info">
                    <span class="moga-participant-row__label"><?php esc_html_e('Children', 'moga-travel'); ?></span>
                    <span class="moga-participant-row__price" id="moga-price-child-display"></span>
                </div>
                <div class="moga-participant-row__controls">
                    <button type="button" class="moga-participant-btn" id="moga-children-minus" aria-label="<?php esc_attr_e('Remove one child', 'moga-travel'); ?>" disabled>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    </button>
                    <span class="moga-participant-count" id="moga-children-display" aria-live="polite"><?php echo esc_html($children_val); ?></span>
                    <input type="hidden" name="children" id="moga-children-input" value="<?php echo esc_attr($children_val); ?>">
                    <button type="button" class="moga-participant-btn" id="moga-children-plus" aria-label="<?php esc_attr_e('Add one child', 'moga-travel'); ?>">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    </button>
                </div>
            </div>

            <div class="moga-participant-row" data-type="infants">
                <div class="moga-participant-row__info">
                    <span class="moga-participant-row__label"><?php esc_html_e('Infants', 'moga-travel'); ?></span>
                    <span class="moga-participant-row__price" id="moga-price-infant-display"></span>
                </div>
                <div class="moga-participant-row__controls">
                    <button type="button" class="moga-participant-btn" id="moga-infants-minus" aria-label="<?php esc_attr_e('Remove one infant', 'moga-travel'); ?>" disabled>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    </button>
                    <span class="moga-participant-count" id="moga-infants-display" aria-live="polite"><?php echo esc_html($infants_val); ?></span>
                    <input type="hidden" name="infants" id="moga-infants-input" value="<?php echo esc_attr($infants_val); ?>">
                    <button type="button" class="moga-participant-btn" id="moga-infants-plus" aria-label="<?php esc_attr_e('Add one infant', 'moga-travel'); ?>">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    </button>
                </div>
            </div>

        </div>

        <?php // ---- Price Breakdown ---- ?>
        <div class="moga-price-breakdown" id="moga-price-breakdown" hidden>
            <div class="moga-price-breakdown__row" id="moga-breakdown-adults-row">
                <span class="moga-price-breakdown__label" id="moga-breakdown-adults-label"></span>
                <span class="moga-price-breakdown__value" id="moga-breakdown-adults-total"></span>
            </div>
            <div class="moga-price-breakdown__row" id="moga-breakdown-children-row" hidden>
                <span class="moga-price-breakdown__label" id="moga-breakdown-children-label"></span>
                <span class="moga-price-breakdown__value" id="moga-breakdown-children-total"></span>
            </div>
            <div class="moga-price-breakdown__row" id="moga-breakdown-infants-row" hidden>
                <span class="moga-price-breakdown__label" id="moga-breakdown-infants-label"></span>
                <span class="moga-price-breakdown__value" id="moga-breakdown-infants-total"></span>
            </div>
            <div class="moga-price-breakdown__row moga-price-breakdown__row--total">
                <span class="moga-price-breakdown__label moga-price-breakdown__label--total">
                    <?php esc_html_e('Total', 'moga-travel'); ?>
                </span>
                <span class="moga-price-breakdown__value moga-price-breakdown__value--total" id="moga-breakdown-total"></span>
            </div>
        </div>

        <?php // ---- Reserve Button ---- ?>
        <button type="submit" class="moga-btn moga-btn--primary moga-w-100 moga-booking-form__submit" id="moga-tour-reserve-btn">
            <?php echo '1' === $instant
                ? esc_html__('Reserve Now', 'moga-travel')
                : esc_html__('Check Availability', 'moga-travel'); ?>
        </button>

        <?php // ---- No charge notice ---- ?>
        <p class="moga-booking-form__notice">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="12"/>
                <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <?php esc_html_e("You won't be charged yet", 'moga-travel'); ?>
        </p>

    </form>

    <?php // ---- Meta Items ---- ?>
    <div class="moga-booking-form-card__meta">
        <?php if ('1' === $instant) : ?>
            <div class="moga-booking-meta-item moga-booking-meta-item--success">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                <?php esc_html_e('Instant confirmation', 'moga-travel'); ?>
            </div>
        <?php endif; ?>
        <?php if ($cancel_label) : ?>
            <div class="moga-booking-meta-item">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                <?php echo esc_html($cancel_label); ?>
            </div>
        <?php endif; ?>
        <div class="moga-booking-meta-item">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            <?php esc_html_e('Secure payment', 'moga-travel'); ?>
        </div>
    </div>

    <?php // ---- JSON Config for booking.js ---- ?>
    <script type="application/json" id="moga-tour-booking-config">
    {
        "tourId":         <?php echo intval($tour_id); ?>,
        "currency":       "<?php echo esc_js($currency); ?>",
        "durationNights": <?php echo intval($duration_nights); ?>,
        "instantBooking": <?php echo '1' === $instant ? 'true' : 'false'; ?>,
        "busId":          <?php echo intval($bus_id); ?>,
        "groups":         <?php echo wp_json_encode($groups); ?>,
        "ajaxUrl":        "<?php echo esc_js(admin_url('admin-ajax.php')); ?>",
        "nonce":          "<?php echo esc_js(wp_create_nonce('moga_nonce')); ?>"
    }
    </script>

</div>
