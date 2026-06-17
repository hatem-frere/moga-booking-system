<?php
/**
 * Template Name: Home Template
 * Template Post Type: page
 *
 * The main homepage template for Moga Booking System.
 * Displays hero search, featured properties, destinations,
 * and featured tours sections.
 *
 * @package MogaTravel
 * @since   1.0.0
 */

get_header(); ?>

<main id="moga-main" class="moga-main moga-home">

    <?php get_template_part( 'template-parts/global/breadcrumb' ); ?>

    <!-- ======================================================
         SECTION 1 — HERO SEARCH
    ====================================================== -->
    <section class="moga-hero" aria-label="<?php esc_attr_e( 'Search for travel deals', 'moga-travel' ); ?>">

        <!-- Hero Background -->
        <div class="moga-hero__bg" aria-hidden="true">
            <div class="moga-hero__overlay"></div>
        </div>

        <div class="moga-container">
            <div class="moga-hero__inner">

                <!-- Hero Headline -->
                <div class="moga-hero__text">
                    <h1 class="moga-hero__title">
                        <?php esc_html_e( 'Find your perfect escape', 'moga-travel' ); ?>
                    </h1>
                    <p class="moga-hero__subtitle">
                        <?php esc_html_e( 'Hotels, tours, rentals and bus seats — all in one place.', 'moga-travel' ); ?>
                    </p>
                </div>

                <!-- Search Tabs -->
                <div class="moga-search-box">

                    <div class="moga-search-box__tabs" role="tablist">
                        <button class="moga-search-box__tab is-active"
                                role="tab"
                                aria-selected="true"
                                data-tab="property"
                                aria-controls="moga-search-property">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                                <polyline points="9 22 9 12 15 12 15 22"/>
                            </svg>
                            <?php esc_html_e( 'Properties', 'moga-travel' ); ?>
                        </button>
                        <button class="moga-search-box__tab"
                                role="tab"
                                aria-selected="false"
                                data-tab="tour"
                                aria-controls="moga-search-tour">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <circle cx="12" cy="12" r="10"/>
                                <polygon points="10 8 16 12 10 16 10 8"/>
                            </svg>
                            <?php esc_html_e( 'Tours', 'moga-travel' ); ?>
                        </button>
                        <button class="moga-search-box__tab"
                                role="tab"
                                aria-selected="false"
                                data-tab="bus"
                                aria-controls="moga-search-bus">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <rect x="1" y="3" width="15" height="13" rx="2"/>
                                <path d="M16 8h4l3 3v5h-7V8z"/>
                                <circle cx="5.5" cy="18.5" r="2.5"/>
                                <circle cx="18.5" cy="18.5" r="2.5"/>
                            </svg>
                            <?php esc_html_e( 'Bus Seats', 'moga-travel' ); ?>
                        </button>
                        <button class="moga-search-box__tab"
                                role="tab"
                                aria-selected="false"
                                data-tab="rental"
                                aria-controls="moga-search-rental">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                            </svg>
                            <?php esc_html_e( 'Rentals', 'moga-travel' ); ?>
                        </button>
                    </div>

                    <!-- Tab Panel: Properties -->
                    <div id="moga-search-property"
                         class="moga-search-box__panel is-active"
                         role="tabpanel"
                         data-panel="property">
                        <form class="moga-search-form"
                              method="GET"
                              action="<?php echo esc_url( home_url( '/search-results/' ) ); ?>"
                              aria-label="<?php esc_attr_e( 'Property search', 'moga-travel' ); ?>">

                            <input type="hidden" name="type" value="property">

                            <div class="moga-search-form__fields">

                                <!-- Destination -->
                                <div class="moga-search-form__field moga-search-form__field--destination">
                                    <label for="property-destination" class="moga-search-form__label">
                                        <?php esc_html_e( 'Destination', 'moga-travel' ); ?>
                                    </label>
                                    <div class="moga-search-form__input-wrap">
                                        <svg class="moga-search-form__icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                            <circle cx="12" cy="10" r="3"/>
                                        </svg>
                                        <input type="text"
                                               id="property-destination"
                                               name="destination"
                                               class="moga-search-form__input"
                                               placeholder="<?php esc_attr_e( 'Where are you going?', 'moga-travel' ); ?>"
                                               autocomplete="off"
                                               aria-required="true">
                                    </div>
                                </div>

                                <!-- Check In -->
                                <div class="moga-search-form__field moga-search-form__field--date">
                                    <label for="property-checkin" class="moga-search-form__label">
                                        <?php esc_html_e( 'Check In', 'moga-travel' ); ?>
                                    </label>
                                    <div class="moga-search-form__input-wrap">
                                        <svg class="moga-search-form__icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                            <line x1="16" y1="2" x2="16" y2="6"/>
                                            <line x1="8" y1="2" x2="8" y2="6"/>
                                            <line x1="3" y1="10" x2="21" y2="10"/>
                                        </svg>
                                        <input type="date"
                                               id="property-checkin"
                                               name="check_in"
                                               class="moga-search-form__input"
                                               aria-required="true">
                                    </div>
                                </div>

                                <!-- Check Out -->
                                <div class="moga-search-form__field moga-search-form__field--date">
                                    <label for="property-checkout" class="moga-search-form__label">
                                        <?php esc_html_e( 'Check Out', 'moga-travel' ); ?>
                                    </label>
                                    <div class="moga-search-form__input-wrap">
                                        <svg class="moga-search-form__icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                            <line x1="16" y1="2" x2="16" y2="6"/>
                                            <line x1="8" y1="2" x2="8" y2="6"/>
                                            <line x1="3" y1="10" x2="21" y2="10"/>
                                        </svg>
                                        <input type="date"
                                               id="property-checkout"
                                               name="check_out"
                                               class="moga-search-form__input"
                                               aria-required="true">
                                    </div>
                                </div>

                                <!-- Guests -->
                                <div class="moga-search-form__field moga-search-form__field--guests">
                                    <label for="property-guests" class="moga-search-form__label">
                                        <?php esc_html_e( 'Guests', 'moga-travel' ); ?>
                                    </label>
                                    <div class="moga-search-form__input-wrap">
                                        <svg class="moga-search-form__icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                            <circle cx="9" cy="7" r="4"/>
                                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                        </svg>
                                        <input type="number"
                                               id="property-guests"
                                               name="guests"
                                               class="moga-search-form__input"
                                               min="1"
                                               max="20"
                                               value="2"
                                               placeholder="<?php esc_attr_e( '2 guests', 'moga-travel' ); ?>">
                                    </div>
                                </div>

                                <!-- Search Button -->
                                <div class="moga-search-form__field moga-search-form__field--submit">
                                    <button type="submit" class="moga-btn moga-btn--primary moga-btn--lg moga-search-form__btn">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                            <circle cx="11" cy="11" r="8"/>
                                            <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                                        </svg>
                                        <?php esc_html_e( 'Search', 'moga-travel' ); ?>
                                    </button>
                                </div>

                            </div>
                            <!-- / Search Fields -->

                        </form>
                    </div>
                    <!-- / Property Panel -->

                    <!-- Tab Panel: Tours -->
                    <div id="moga-search-tour"
                         class="moga-search-box__panel"
                         role="tabpanel"
                         data-panel="tour">
                        <form class="moga-search-form"
                              method="GET"
                              action="<?php echo esc_url( home_url( '/search-results/' ) ); ?>">

                            <input type="hidden" name="type" value="tour">

                            <div class="moga-search-form__fields">

                                <div class="moga-search-form__field moga-search-form__field--destination">
                                    <label for="tour-destination" class="moga-search-form__label">
                                        <?php esc_html_e( 'Destination', 'moga-travel' ); ?>
                                    </label>
                                    <div class="moga-search-form__input-wrap">
                                        <svg class="moga-search-form__icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                            <circle cx="12" cy="10" r="3"/>
                                        </svg>
                                        <input type="text"
                                               id="tour-destination"
                                               name="destination"
                                               class="moga-search-form__input"
                                               placeholder="<?php esc_attr_e( 'Where do you want to go?', 'moga-travel' ); ?>">
                                    </div>
                                </div>

                                <div class="moga-search-form__field moga-search-form__field--date">
                                    <label for="tour-date" class="moga-search-form__label">
                                        <?php esc_html_e( 'Tour Date', 'moga-travel' ); ?>
                                    </label>
                                    <div class="moga-search-form__input-wrap">
                                        <svg class="moga-search-form__icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                            <line x1="16" y1="2" x2="16" y2="6"/>
                                            <line x1="8" y1="2" x2="8" y2="6"/>
                                            <line x1="3" y1="10" x2="21" y2="10"/>
                                        </svg>
                                        <input type="date"
                                               id="tour-date"
                                               name="tour_date"
                                               class="moga-search-form__input">
                                    </div>
                                </div>

                                <div class="moga-search-form__field moga-search-form__field--guests">
                                    <label for="tour-guests" class="moga-search-form__label">
                                        <?php esc_html_e( 'People', 'moga-travel' ); ?>
                                    </label>
                                    <div class="moga-search-form__input-wrap">
                                        <svg class="moga-search-form__icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                            <circle cx="9" cy="7" r="4"/>
                                        </svg>
                                        <input type="number"
                                               id="tour-guests"
                                               name="guests"
                                               class="moga-search-form__input"
                                               min="1"
                                               max="50"
                                               value="1">
                                    </div>
                                </div>

                                <div class="moga-search-form__field moga-search-form__field--submit">
                                    <button type="submit" class="moga-btn moga-btn--primary moga-btn--lg moga-search-form__btn">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                            <circle cx="11" cy="11" r="8"/>
                                            <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                                        </svg>
                                        <?php esc_html_e( 'Search Tours', 'moga-travel' ); ?>
                                    </button>
                                </div>

                            </div>
                        </form>
                    </div>
                    <!-- / Tour Panel -->

                    <!-- Tab Panel: Bus Seats -->
                    <div id="moga-search-bus"
                         class="moga-search-box__panel"
                         role="tabpanel"
                         data-panel="bus">
                        <form class="moga-search-form"
                              method="GET"
                              action="<?php echo esc_url( home_url( '/search-results/' ) ); ?>">

                            <input type="hidden" name="type" value="bus">

                            <div class="moga-search-form__fields">

                                <div class="moga-search-form__field moga-search-form__field--destination">
                                    <label for="bus-from" class="moga-search-form__label">
                                        <?php esc_html_e( 'From', 'moga-travel' ); ?>
                                    </label>
                                    <div class="moga-search-form__input-wrap">
                                        <svg class="moga-search-form__icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                            <circle cx="12" cy="10" r="3"/>
                                        </svg>
                                        <input type="text"
                                               id="bus-from"
                                               name="from"
                                               class="moga-search-form__input"
                                               placeholder="<?php esc_attr_e( 'Departure city', 'moga-travel' ); ?>">
                                    </div>
                                </div>

                                <!-- Swap Button -->
                                <div class="moga-search-form__swap" aria-label="<?php esc_attr_e( 'Swap cities', 'moga-travel' ); ?>">
                                    <button type="button" class="moga-search-form__swap-btn" id="moga-swap-cities">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <polyline points="17 1 21 5 17 9"/>
                                            <path d="M3 11V9a4 4 0 0 1 4-4h14"/>
                                            <polyline points="7 23 3 19 7 15"/>
                                            <path d="M21 13v2a4 4 0 0 1-4 4H3"/>
                                        </svg>
                                    </button>
                                </div>

                                <div class="moga-search-form__field moga-search-form__field--destination">
                                    <label for="bus-to" class="moga-search-form__label">
                                        <?php esc_html_e( 'To', 'moga-travel' ); ?>
                                    </label>
                                    <div class="moga-search-form__input-wrap">
                                        <svg class="moga-search-form__icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                            <circle cx="12" cy="10" r="3"/>
                                        </svg>
                                        <input type="text"
                                               id="bus-to"
                                               name="to"
                                               class="moga-search-form__input"
                                               placeholder="<?php esc_attr_e( 'Arrival city', 'moga-travel' ); ?>">
                                    </div>
                                </div>

                                <div class="moga-search-form__field moga-search-form__field--date">
                                    <label for="bus-date" class="moga-search-form__label">
                                        <?php esc_html_e( 'Travel Date', 'moga-travel' ); ?>
                                    </label>
                                    <div class="moga-search-form__input-wrap">
                                        <svg class="moga-search-form__icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                            <line x1="16" y1="2" x2="16" y2="6"/>
                                            <line x1="8" y1="2" x2="8" y2="6"/>
                                            <line x1="3" y1="10" x2="21" y2="10"/>
                                        </svg>
                                        <input type="date"
                                               id="bus-date"
                                               name="travel_date"
                                               class="moga-search-form__input">
                                    </div>
                                </div>

                                <div class="moga-search-form__field moga-search-form__field--submit">
                                    <button type="submit" class="moga-btn moga-btn--primary moga-btn--lg moga-search-form__btn">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                            <circle cx="11" cy="11" r="8"/>
                                            <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                                        </svg>
                                        <?php esc_html_e( 'Find Seats', 'moga-travel' ); ?>
                                    </button>
                                </div>

                            </div>
                        </form>
                    </div>
                    <!-- / Bus Panel -->

                    <!-- Tab Panel: Rentals -->
                    <div id="moga-search-rental"
                         class="moga-search-box__panel"
                         role="tabpanel"
                         data-panel="rental">
                        <form class="moga-search-form"
                              method="GET"
                              action="<?php echo esc_url( home_url( '/search-results/' ) ); ?>">

                            <input type="hidden" name="type" value="rental">

                            <div class="moga-search-form__fields">

                                <div class="moga-search-form__field moga-search-form__field--destination">
                                    <label for="rental-destination" class="moga-search-form__label">
                                        <?php esc_html_e( 'Location', 'moga-travel' ); ?>
                                    </label>
                                    <div class="moga-search-form__input-wrap">
                                        <svg class="moga-search-form__icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                            <circle cx="12" cy="10" r="3"/>
                                        </svg>
                                        <input type="text"
                                               id="rental-destination"
                                               name="destination"
                                               class="moga-search-form__input"
                                               placeholder="<?php esc_attr_e( 'City or area', 'moga-travel' ); ?>">
                                    </div>
                                </div>

                                <div class="moga-search-form__field moga-search-form__field--date">
                                    <label for="rental-checkin" class="moga-search-form__label">
                                        <?php esc_html_e( 'From Date', 'moga-travel' ); ?>
                                    </label>
                                    <div class="moga-search-form__input-wrap">
                                        <svg class="moga-search-form__icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                            <line x1="16" y1="2" x2="16" y2="6"/>
                                            <line x1="8" y1="2" x2="8" y2="6"/>
                                            <line x1="3" y1="10" x2="21" y2="10"/>
                                        </svg>
                                        <input type="date"
                                               id="rental-checkin"
                                               name="check_in"
                                               class="moga-search-form__input">
                                    </div>
                                </div>

                                <div class="moga-search-form__field moga-search-form__field--date">
                                    <label for="rental-checkout" class="moga-search-form__label">
                                        <?php esc_html_e( 'To Date', 'moga-travel' ); ?>
                                    </label>
                                    <div class="moga-search-form__input-wrap">
                                        <svg class="moga-search-form__icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                            <line x1="16" y1="2" x2="16" y2="6"/>
                                            <line x1="8" y1="2" x2="8" y2="6"/>
                                            <line x1="3" y1="10" x2="21" y2="10"/>
                                        </svg>
                                        <input type="date"
                                               id="rental-checkout"
                                               name="check_out"
                                               class="moga-search-form__input">
                                    </div>
                                </div>

                                <div class="moga-search-form__field moga-search-form__field--submit">
                                    <button type="submit" class="moga-btn moga-btn--primary moga-btn--lg moga-search-form__btn">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                            <circle cx="11" cy="11" r="8"/>
                                            <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                                        </svg>
                                        <?php esc_html_e( 'Search Rentals', 'moga-travel' ); ?>
                                    </button>
                                </div>

                            </div>
                        </form>
                    </div>
                    <!-- / Rental Panel -->

                </div>
                <!-- / Search Box -->

            </div>
            <!-- / Hero Inner -->
        </div>
        <!-- / Container -->

    </section>
    <!-- / Hero Section -->


    <!-- ======================================================
         SECTION 2 — POPULAR DESTINATIONS
    ====================================================== -->
    <section class="moga-section moga-destinations" aria-labelledby="moga-destinations-title">
        <div class="moga-container">

            <div class="moga-section__header">
                <h2 id="moga-destinations-title" class="moga-section__title">
                    <?php esc_html_e( 'Popular Destinations', 'moga-travel' ); ?>
                </h2>
                <p class="moga-section__subtitle">
                    <?php esc_html_e( 'Explore Egypt\'s most beloved travel destinations', 'moga-travel' ); ?>
                </p>
            </div>

            <div class="moga-destinations__grid">

                <?php
                $destinations = array(
                    array(
                        'name'  => __( 'Cairo', 'moga-travel' ),
                        'count' => '120+',
                        'label' => __( 'properties', 'moga-travel' ),
                        'color' => '#003580',
                        'emoji' => '🏛️',
                    ),
                    array(
                        'name'  => __( 'Alexandria', 'moga-travel' ),
                        'count' => '85+',
                        'label' => __( 'properties', 'moga-travel' ),
                        'color' => '#0071c2',
                        'emoji' => '🌊',
                    ),
                    array(
                        'name'  => __( 'Hurghada', 'moga-travel' ),
                        'count' => '200+',
                        'label' => __( 'properties', 'moga-travel' ),
                        'color' => '#f5a623',
                        'emoji' => '🏖️',
                    ),
                    array(
                        'name'  => __( 'Sharm El Sheikh', 'moga-travel' ),
                        'count' => '150+',
                        'label' => __( 'properties', 'moga-travel' ),
                        'color' => '#28a745',
                        'emoji' => '🤿',
                    ),
                    array(
                        'name'  => __( 'Luxor', 'moga-travel' ),
                        'count' => '60+',
                        'label' => __( 'properties', 'moga-travel' ),
                        'color' => '#dc3545',
                        'emoji' => '🏺',
                    ),
                    array(
                        'name'  => __( 'Aswan', 'moga-travel' ),
                        'count' => '45+',
                        'label' => __( 'properties', 'moga-travel' ),
                        'color' => '#6f42c1',
                        'emoji' => '⛵',
                    ),
                );

                foreach ( $destinations as $destination ) : ?>
                    <a href="<?php echo esc_url( add_query_arg( array(
                        'type'        => 'property',
                        'destination' => sanitize_title( $destination['name'] ),
                    ), home_url( '/search-results/' ) ) ); ?>"
                       class="moga-destination-card"
                       aria-label="<?php echo esc_attr( sprintf(
                           /* translators: %s: destination name */
                           __( 'Explore %s', 'moga-travel' ),
                           $destination['name']
                       ) ); ?>">

                        <div class="moga-destination-card__icon"
                             style="background-color: <?php echo esc_attr( $destination['color'] ); ?>15; color: <?php echo esc_attr( $destination['color'] ); ?>;">
                            <span aria-hidden="true"><?php echo esc_html( $destination['emoji'] ); ?></span>
                        </div>

                        <div class="moga-destination-card__body">
                            <h3 class="moga-destination-card__name">
                                <?php echo esc_html( $destination['name'] ); ?>
                            </h3>
                            <p class="moga-destination-card__count">
                                <strong><?php echo esc_html( $destination['count'] ); ?></strong>
                                <?php echo esc_html( $destination['label'] ); ?>
                            </p>
                        </div>

                        <svg class="moga-destination-card__arrow" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <polyline points="9 18 15 12 9 6"/>
                        </svg>

                    </a>
                <?php endforeach; ?>

            </div>
            <!-- / Destinations Grid -->

            <!-- View All Destinations Button -->
            <div class="moga-text-center moga-mt-4">
                <a href="<?php echo esc_url( add_query_arg( 'type', 'property', home_url( '/search-results/' ) ) ); ?>"
                   class="moga-btn moga-btn--outline moga-btn--lg">
                    <?php esc_html_e( 'View All Destinations', 'moga-travel' ); ?>
                </a>
            </div>

        </div>
    </section>
    <!-- / Destinations Section -->


    <!-- ======================================================
         SECTION 3 — FEATURED PROPERTIES
    ====================================================== -->
    <section class="moga-section moga-bg-white moga-featured-properties" aria-labelledby="moga-properties-title">
        <div class="moga-container">

            <div class="moga-section__header">
                <h2 id="moga-properties-title" class="moga-section__title">
                    <?php esc_html_e( 'Featured Properties', 'moga-travel' ); ?>
                </h2>
                <p class="moga-section__subtitle">
                    <?php esc_html_e( 'Hand-picked hotels and apartments for your perfect stay', 'moga-travel' ); ?>
                </p>
            </div>

            <?php
            $properties_args = array(
                'post_type'      => 'moga_property',
                'posts_per_page' => 8,
                'post_status'    => 'publish',
                'meta_query'     => array(
                    array(
                        'key'     => '_moga_featured',
                        'value'   => '1',
                        'compare' => '=',
                    ),
                ),
                'orderby'        => 'date',
                'order'          => 'DESC',
            );

            $properties = new WP_Query( $properties_args );
            ?>

            <?php if ( $properties->have_posts() ) : ?>

                <div class="moga-grid moga-grid--4">
                    <?php while ( $properties->have_posts() ) : $properties->the_post(); ?>
                        <?php get_template_part( 'template-parts/property/card-grid' ); ?>
                    <?php endwhile; ?>
                    <?php wp_reset_postdata(); ?>
                </div>

                <div class="moga-text-center moga-mt-4">
                    <a href="<?php echo esc_url( add_query_arg( 'type', 'property', home_url( '/search-results/' ) ) ); ?>"
                       class="moga-btn moga-btn--outline moga-btn--lg">
                        <?php esc_html_e( 'View All Properties', 'moga-travel' ); ?>
                    </a>
                </div>

            <?php else : ?>

                <div class="moga-grid moga-grid--4">
                    <?php for ( $i = 1; $i <= 8; $i++ ) : ?>
                        <div class="moga-card moga-card--placeholder" aria-hidden="true">
                            <div class="moga-card__image-wrap moga-placeholder-shimmer"></div>
                            <div class="moga-card__body">
                                <div class="moga-placeholder-shimmer moga-placeholder-line"></div>
                                <div class="moga-placeholder-shimmer moga-placeholder-line moga-placeholder-line--short"></div>
                            </div>
                            <div class="moga-card__footer">
                                <div class="moga-placeholder-shimmer moga-placeholder-price"></div>
                                <div class="moga-placeholder-shimmer moga-placeholder-btn"></div>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>

                <?php if ( current_user_can( 'manage_options' ) ) : ?>
                    <div class="moga-alert moga-alert--info moga-mt-3">
                        <strong><?php esc_html_e( 'Admin Notice:', 'moga-travel' ); ?></strong>
                        <?php esc_html_e( 'No featured properties found. Add properties and mark them as featured to display them here.', 'moga-travel' ); ?>
                        <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=moga_property' ) ); ?>">
                            <?php esc_html_e( 'Add Property →', 'moga-travel' ); ?>
                        </a>
                    </div>
                <?php endif; ?>

                <!-- View All Properties Button — always visible even with no properties -->
                <div class="moga-text-center moga-mt-4">
                    <a href="<?php echo esc_url( add_query_arg( 'type', 'property', home_url( '/search-results/' ) ) ); ?>"
                       class="moga-btn moga-btn--outline moga-btn--lg">
                        <?php esc_html_e( 'View All Properties', 'moga-travel' ); ?>
                    </a>
                </div>

            <?php endif; ?>

        </div>
    </section>
    <!-- / Featured Properties Section -->


    <!-- ======================================================
         SECTION 4 — WHY CHOOSE US
    ====================================================== -->
    <section class="moga-section moga-why-us" aria-labelledby="moga-why-title">
        <div class="moga-container">

            <div class="moga-section__header">
                <h2 id="moga-why-title" class="moga-section__title">
                    <?php esc_html_e( 'Why Choose Moga Booking?', 'moga-travel' ); ?>
                </h2>
                <p class="moga-section__subtitle">
                    <?php esc_html_e( 'Everything you need for a perfect trip, all in one place', 'moga-travel' ); ?>
                </p>
            </div>

            <div class="moga-grid moga-grid--4">

                <?php
                $features = array(
                    array(
                        'icon'  => '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
                        'title' => __( 'Secure Booking', 'moga-travel' ),
                        'desc'  => __( 'Your payments are protected with bank-level encryption and secure gateways.', 'moga-travel' ),
                        'color' => '#003580',
                    ),
                    array(
                        'icon'  => '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
                        'title' => __( 'Instant Confirmation', 'moga-travel' ),
                        'desc'  => __( 'Get your booking confirmed instantly with email and SMS notification.', 'moga-travel' ),
                        'color' => '#0071c2',
                    ),
                    array(
                        'icon'  => '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>',
                        'title' => __( 'Best Price Guarantee', 'moga-travel' ),
                        'desc'  => __( 'Find a lower price? We\'ll match it. Always get the best deal with us.', 'moga-travel' ),
                        'color' => '#f5a623',
                    ),
                    array(
                        'icon'  => '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.4 2 2 0 0 1 3.6 1.22h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.78a16 16 0 0 0 6 6l.93-.93a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21.73 16z"/></svg>',
                        'title' => __( '24/7 Support', 'moga-travel' ),
                        'desc'  => __( 'Our support team is available around the clock to help you with any issue.', 'moga-travel' ),
                        'color' => '#28a745',
                    ),
                );

                foreach ( $features as $feature ) : ?>
                    <div class="moga-feature-card">
                        <div class="moga-feature-card__icon"
                             style="color: <?php echo esc_attr( $feature['color'] ); ?>; background-color: <?php echo esc_attr( $feature['color'] ); ?>15;">
                            <?php echo $feature['icon']; ?>
                        </div>
                        <h3 class="moga-feature-card__title">
                            <?php echo esc_html( $feature['title'] ); ?>
                        </h3>
                        <p class="moga-feature-card__desc">
                            <?php echo esc_html( $feature['desc'] ); ?>
                        </p>
                    </div>
                <?php endforeach; ?>

            </div>

        </div>
    </section>
    <!-- / Why Choose Us Section -->


    <!-- ======================================================
         SECTION 5 — FEATURED TOURS
    ====================================================== -->
    <section class="moga-section moga-bg-white moga-featured-tours" aria-labelledby="moga-tours-title">
        <div class="moga-container">

            <div class="moga-section__header">
                <h2 id="moga-tours-title" class="moga-section__title">
                    <?php esc_html_e( 'Featured Tours', 'moga-travel' ); ?>
                </h2>
                <p class="moga-section__subtitle">
                    <?php esc_html_e( 'Guided tours with bus seat reservations included', 'moga-travel' ); ?>
                </p>
            </div>

            <?php
            $tours_args = array(
                'post_type'      => 'moga_tour',
                'posts_per_page' => 4,
                'post_status'    => 'publish',
                'meta_query'     => array(
                    array(
                        'key'     => '_moga_featured',
                        'value'   => '1',
                        'compare' => '=',
                    ),
                ),
            );

            $tours = new WP_Query( $tours_args );
            ?>

            <?php if ( $tours->have_posts() ) : ?>

                <div class="moga-grid moga-grid--4">
                    <?php while ( $tours->have_posts() ) : $tours->the_post(); ?>
                        <?php get_template_part( 'template-parts/tour/card-grid' ); ?>
                    <?php endwhile; ?>
                    <?php wp_reset_postdata(); ?>
                </div>

            <?php else : ?>

                <div class="moga-grid moga-grid--4">
                    <?php for ( $i = 1; $i <= 4; $i++ ) : ?>
                        <div class="moga-card moga-card--placeholder" aria-hidden="true">
                            <div class="moga-card__image-wrap moga-placeholder-shimmer"></div>
                            <div class="moga-card__body">
                                <div class="moga-placeholder-shimmer moga-placeholder-line"></div>
                                <div class="moga-placeholder-shimmer moga-placeholder-line moga-placeholder-line--short"></div>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>

                <?php if ( current_user_can( 'manage_options' ) ) : ?>
                    <div class="moga-alert moga-alert--info moga-mt-3">
                        <strong><?php esc_html_e( 'Admin Notice:', 'moga-travel' ); ?></strong>
                        <?php esc_html_e( 'No featured tours found. Add tours and mark them as featured to display them here.', 'moga-travel' ); ?>
                        <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=moga_tour' ) ); ?>">
                            <?php esc_html_e( 'Add Tour →', 'moga-travel' ); ?>
                        </a>
                    </div>
                <?php endif; ?>

            <?php endif; ?>

            <div class="moga-text-center moga-mt-4">
                <a href="<?php echo esc_url( add_query_arg( 'type', 'tour', home_url( '/search-results/' ) ) ); ?>"
                   class="moga-btn moga-btn--outline moga-btn--lg">
                    <?php esc_html_e( 'View All Tours', 'moga-travel' ); ?>
                </a>
            </div>

        </div>
    </section>
    <!-- / Featured Tours Section -->


    <!-- ======================================================
         SECTION 6 — CALL TO ACTION
    ====================================================== -->
    <section class="moga-section moga-cta" aria-labelledby="moga-cta-title">
        <div class="moga-container">
            <div class="moga-cta__inner">

                <div class="moga-cta__text">
                    <h2 id="moga-cta-title" class="moga-cta__title">
                        <?php esc_html_e( 'List your property or tour', 'moga-travel' ); ?>
                    </h2>
                    <p class="moga-cta__desc">
                        <?php esc_html_e( 'Join thousands of owners earning extra income. List your property or tour on Moga Booking today — it\'s free to get started.', 'moga-travel' ); ?>
                    </p>
                </div>

                <div class="moga-cta__actions">
                    <?php if ( is_user_logged_in() ) : ?>
                        <a href="<?php echo esc_url( home_url( '/dashboard/' ) ); ?>"
                           class="moga-btn moga-btn--white moga-btn--lg">
                            <?php esc_html_e( 'Go to Dashboard', 'moga-travel' ); ?>
                        </a>
                    <?php else : ?>
                        <a href="<?php echo esc_url( wp_registration_url() ); ?>"
                           class="moga-btn moga-btn--white moga-btn--lg">
                            <?php esc_html_e( 'Get Started Free', 'moga-travel' ); ?>
                        </a>
                        <a href="<?php echo esc_url( wp_login_url() ); ?>"
                           class="moga-btn moga-btn--outline-white moga-btn--lg">
                            <?php esc_html_e( 'Sign In', 'moga-travel' ); ?>
                        </a>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </section>
    <!-- / CTA Section -->

</main>

<?php get_footer(); ?>