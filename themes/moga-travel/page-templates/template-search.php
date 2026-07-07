<?php
/**
 * Template Name: Search Results
 * Template Post Type: page
 *
 * Full search results page. Handles Properties and Tours with:
 *   - Sidebar filters (type-specific) with accordion
 *   - Sort dropdown
 *   - Grid / List view toggle
 *   - Active filter pills
 *   - Pagination
 *   - URL parameter driven (all filters via GET)
 *
 * @package MogaTravel
 * @since   1.0.0
 */

get_header();

// ============================================================
// 01. READ & SANITIZE URL PARAMETERS
// ============================================================

$search_type  = isset( $_GET['type'] ) && 'tour' === sanitize_text_field( wp_unslash( $_GET['type'] ) )
    ? 'tour' : 'property';
$current_view = isset( $_GET['view'] ) && 'list' === sanitize_text_field( wp_unslash( $_GET['view'] ) )
    ? 'list' : 'grid';
$current_sort = isset( $_GET['sort'] )
    ? sanitize_text_field( wp_unslash( $_GET['sort'] ) ) : 'recommended';
$current_page = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;

// Shared filters.
$filter_destination = isset( $_GET['destination'] )
    ? sanitize_text_field( wp_unslash( $_GET['destination'] ) ) : '';
$filter_price_min   = isset( $_GET['price_min'] ) && '' !== $_GET['price_min']
    ? absint( $_GET['price_min'] ) : '';
$filter_price_max   = isset( $_GET['price_max'] ) && '' !== $_GET['price_max']
    ? absint( $_GET['price_max'] ) : '';
$filter_rating      = isset( $_GET['rating'] ) && '' !== $_GET['rating']
    ? absint( $_GET['rating'] ) : '';

// Property filters.
$filter_property_type = isset( $_GET['property_type'] )
    ? sanitize_text_field( wp_unslash( $_GET['property_type'] ) ) : '';
$filter_location      = isset( $_GET['location'] )
    ? sanitize_text_field( wp_unslash( $_GET['location'] ) ) : '';
$filter_province      = isset( $_GET['province'] )
    ? sanitize_text_field( wp_unslash( $_GET['province'] ) ) : '';
$filter_district      = isset( $_GET['district'] )
    ? sanitize_text_field( wp_unslash( $_GET['district'] ) ) : '';
$filter_amenities     = isset( $_GET['amenities'] ) && is_array( $_GET['amenities'] )
    ? array_map( 'sanitize_text_field', wp_unslash( $_GET['amenities'] ) ) : array();

// Tour filters.
$filter_tour_category = isset( $_GET['tour_category'] )
    ? sanitize_text_field( wp_unslash( $_GET['tour_category'] ) ) : '';
$filter_difficulty    = isset( $_GET['difficulty'] )
    ? sanitize_text_field( wp_unslash( $_GET['difficulty'] ) ) : '';
$filter_duration      = isset( $_GET['duration'] )
    ? sanitize_text_field( wp_unslash( $_GET['duration'] ) ) : '';
$filter_tour_type     = isset( $_GET['tour_type'] )
    ? sanitize_text_field( wp_unslash( $_GET['tour_type'] ) ) : '';


// ============================================================
// 02. BUILD WP_QUERY
// ============================================================

$post_type      = 'tour' === $search_type ? 'moga_tour' : 'moga_property';
$price_meta_key = 'tour' === $search_type ? '_moga_price_per_person' : '_moga_price_per_night';

$args = array(
    'post_type'      => $post_type,
    'posts_per_page' => 9,
    'post_status'    => 'publish',
    'paged'          => $current_page,
);

// ---- Meta Query ----
$meta_query = array( 'relation' => 'AND' );

if ( '' !== $filter_price_min ) {
    $meta_query[] = array(
        'key'     => $price_meta_key,
        'value'   => $filter_price_min,
        'compare' => '>=',
        'type'    => 'NUMERIC',
    );
}
if ( '' !== $filter_price_max ) {
    $meta_query[] = array(
        'key'     => $price_meta_key,
        'value'   => $filter_price_max,
        'compare' => '<=',
        'type'    => 'NUMERIC',
    );
}

if ( '' !== $filter_rating ) {
    $meta_query[] = array(
        'key'     => '_moga_rating',
        'value'   => $filter_rating,
        'compare' => '>=',
        'type'    => 'NUMERIC',
    );
}

if ( 'property' === $search_type ) {
    foreach ( $filter_amenities as $amenity ) {
        if ( $amenity ) {
            $meta_query[] = array(
                'key'     => '_moga_amenities',
                'value'   => $amenity,
                'compare' => 'LIKE',
            );
        }
    }
} elseif ( 'tour' === $search_type ) {
    if ( $filter_difficulty ) {
        $meta_query[] = array(
            'key'   => '_moga_difficulty',
            'value' => $filter_difficulty,
        );
    }
    if ( $filter_tour_type ) {
        $meta_query[] = array(
            'key'   => '_moga_tour_type',
            'value' => $filter_tour_type,
        );
    }
    if ( $filter_duration ) {
        switch ( $filter_duration ) {
            case '1':
                $meta_query[] = array( 'key' => '_moga_duration_days', 'value' => 1, 'compare' => '=', 'type' => 'NUMERIC' );
                break;
            case '2-3':
                $meta_query[] = array( 'key' => '_moga_duration_days', 'value' => array( 2, 3 ), 'compare' => 'BETWEEN', 'type' => 'NUMERIC' );
                break;
            case '4-6':
                $meta_query[] = array( 'key' => '_moga_duration_days', 'value' => array( 4, 6 ), 'compare' => 'BETWEEN', 'type' => 'NUMERIC' );
                break;
            case '7plus':
                $meta_query[] = array( 'key' => '_moga_duration_days', 'value' => 7, 'compare' => '>=', 'type' => 'NUMERIC' );
                break;
        }
    }
    if ( $filter_destination ) {
        $meta_query[] = array(
            'relation' => 'OR',
            array( 'key' => '_moga_departure_city',   'value' => $filter_destination, 'compare' => 'LIKE' ),
            array( 'key' => '_moga_destination_city', 'value' => $filter_destination, 'compare' => 'LIKE' ),
        );
    }
}

if ( count( $meta_query ) > 1 ) {
    $args['meta_query'] = $meta_query;
}

// ---- Tax Query ----
$tax_query = array( 'relation' => 'AND' );

if ( 'property' === $search_type ) {
    if ( $filter_property_type ) {
        $tax_query[] = array( 'taxonomy' => 'moga_property_type', 'field' => 'slug', 'terms' => $filter_property_type );
    }
    // District is most specific — use when set.
    // City includes all its child districts via include_children=true.
    // Province includes all its child cities and their districts.
    if ( $filter_district ) {
        $tax_query[] = array(
            'taxonomy'         => 'moga_location',
            'field'            => 'name',
            'terms'            => $filter_district,
            'include_children' => false,
        );
    } elseif ( $filter_location ) {
        $tax_query[] = array(
            'taxonomy'         => 'moga_location',
            'field'            => 'name',
            'terms'            => $filter_location,
            'include_children' => true,
        );
    } elseif ( $filter_province ) {
        $tax_query[] = array(
            'taxonomy'         => 'moga_location',
            'field'            => 'name',
            'terms'            => $filter_province,
            'include_children' => true,
        );
    } elseif ( $filter_destination ) {
        $tax_query[] = array(
            'taxonomy'         => 'moga_location',
            'field'            => 'name',
            'terms'            => $filter_destination,
            'include_children' => true,
        );
    }
} elseif ( 'tour' === $search_type ) {
    if ( $filter_tour_category ) {
        $tax_query[] = array( 'taxonomy' => 'moga_tour_category', 'field' => 'slug', 'terms' => $filter_tour_category );
    }
}

if ( count( $tax_query ) > 1 ) {
    $args['tax_query'] = $tax_query;
}

// ---- Sort ----
switch ( $current_sort ) {
    case 'price_low':
        $args['orderby']  = 'meta_value_num';
        $args['meta_key'] = $price_meta_key;
        $args['order']    = 'ASC';
        break;
    case 'price_high':
        $args['orderby']  = 'meta_value_num';
        $args['meta_key'] = $price_meta_key;
        $args['order']    = 'DESC';
        break;
    case 'rating':
        $args['orderby']  = 'meta_value_num';
        $args['meta_key'] = '_moga_rating';
        $args['order']    = 'DESC';
        break;
    case 'newest':
    default:
        $args['orderby'] = 'date';
        $args['order']   = 'DESC';
        break;
}

$search_results = new WP_Query( $args );
$total_results  = $search_results->found_posts;
$total_pages    = $search_results->max_num_pages;


// ============================================================
// 03. SIDEBAR DATA
// ============================================================

$property_types = get_terms( array( 'taxonomy' => 'moga_property_type', 'hide_empty' => false, 'orderby' => 'name', 'order' => 'ASC' ) );
$property_types = ! is_wp_error( $property_types ) ? $property_types : array();

// Province terms — all province-level terms in the location taxonomy.
// Shown in the Province / State / Governorate filter group.
$province_terms = get_terms( array(
    'taxonomy'   => 'moga_location',
    'hide_empty' => false,
    'orderby'    => 'name',
    'order'      => 'ASC',
    'meta_query' => array(
        array(
            'key'   => 'moga_level',
            'value' => 'province',
        ),
    ),
) );
$province_terms = ! is_wp_error( $province_terms ) ? $province_terms : array();

// City terms — only loaded when a province is already selected.
// Queries children of the selected province term.
$city_terms = array();
if ( $filter_province ) {
    $selected_province_term = get_term_by( 'name', $filter_province, 'moga_location' );
    if ( $selected_province_term && ! is_wp_error( $selected_province_term ) ) {
        $city_terms = get_terms( array(
            'taxonomy'   => 'moga_location',
            'hide_empty' => false,
            'parent'     => $selected_province_term->term_id,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ) );
        $city_terms = ! is_wp_error( $city_terms ) ? $city_terms : array();
    }
}

// District terms — only loaded when a city is already selected.
// Queries children of the selected city term.
$district_terms = array();
if ( $filter_location ) {
    $selected_city_term = get_term_by( 'name', $filter_location, 'moga_location' );
    if ( $selected_city_term && ! is_wp_error( $selected_city_term ) ) {
        $district_terms = get_terms( array(
            'taxonomy'   => 'moga_location',
            'hide_empty' => false,
            'parent'     => $selected_city_term->term_id,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ) );
        $district_terms = ! is_wp_error( $district_terms ) ? $district_terms : array();
    }
}

$tour_categories = get_terms( array( 'taxonomy' => 'moga_tour_category', 'hide_empty' => false, 'orderby' => 'name', 'order' => 'ASC' ) );
$tour_categories = ! is_wp_error( $tour_categories ) ? $tour_categories : array();

$difficulty_levels = class_exists( 'Moga_CPT_Tour' ) ? Moga_CPT_Tour::get_difficulty_levels() : array();
$tour_types_data   = class_exists( 'Moga_CPT_Tour' ) ? Moga_CPT_Tour::get_tour_types() : array();

$sidebar_amenities = array(
    'wifi'             => __( 'Free WiFi', 'moga-travel' ),
    'air_conditioning' => __( 'Air Conditioning', 'moga-travel' ),
    'pool'             => __( 'Swimming Pool', 'moga-travel' ),
    'parking'          => __( 'Free Parking', 'moga-travel' ),
    'kitchen'          => __( 'Kitchen', 'moga-travel' ),
    'breakfast'        => __( 'Breakfast Included', 'moga-travel' ),
    'beach_access'     => __( 'Beach Access', 'moga-travel' ),
    'sea_view'         => __( 'Sea View', 'moga-travel' ),
    'gym'              => __( 'Gym', 'moga-travel' ),
    'airport_transfer' => __( 'Airport Transfer', 'moga-travel' ),
);

$duration_options = array(
    '1'     => __( '1 Day', 'moga-travel' ),
    '2-3'   => __( '2–3 Days', 'moga-travel' ),
    '4-6'   => __( '4–6 Days', 'moga-travel' ),
    '7plus' => __( '7+ Days', 'moga-travel' ),
);

$sort_options = array(
    'recommended' => __( 'Recommended', 'moga-travel' ),
    'price_low'   => __( 'Price: Low to High', 'moga-travel' ),
    'price_high'  => __( 'Price: High to Low', 'moga-travel' ),
    'newest'      => __( 'Newest First', 'moga-travel' ),
    'rating'      => __( 'Top Rated', 'moga-travel' ),
);


// ============================================================
// 04. ACTIVE FILTER PILLS
// ============================================================

$active_filters = array();

if ( $filter_property_type ) {
    $pt = get_term_by( 'slug', $filter_property_type, 'moga_property_type' );
    if ( $pt ) {
        $active_filters['property_type'] = array( 'label' => $pt->name, 'remove' => esc_url( remove_query_arg( 'property_type' ) ) );
    }
}
if ( $filter_province ) {
    $active_filters['province'] = array(
        'label'  => $filter_province,
        // Removing province also clears city and district since they depend on it.
        'remove' => esc_url( remove_query_arg( array( 'province', 'location', 'district' ) ) ),
    );
}
if ( $filter_location ) {
    $active_filters['location'] = array(
        'label'  => $filter_location,
        // Removing city also clears district.
        'remove' => esc_url( remove_query_arg( array( 'location', 'district' ) ) ),
    );
}
if ( $filter_district ) {
    $active_filters['district'] = array(
        'label'  => $filter_district,
        'remove' => esc_url( remove_query_arg( 'district' ) ),
    );
}
if ( '' !== $filter_price_min ) {
    $active_filters['price_min'] = array( 'label' => sprintf( __( 'From %s', 'moga-travel' ), number_format_i18n( $filter_price_min ) ), 'remove' => esc_url( remove_query_arg( 'price_min' ) ) );
}
if ( '' !== $filter_price_max ) {
    $active_filters['price_max'] = array( 'label' => sprintf( __( 'To %s', 'moga-travel' ), number_format_i18n( $filter_price_max ) ), 'remove' => esc_url( remove_query_arg( 'price_max' ) ) );
}
if ( '' !== $filter_rating ) {
    $active_filters['rating'] = array( 'label' => $filter_rating . '+ ★', 'remove' => esc_url( remove_query_arg( 'rating' ) ) );
}
if ( $filter_tour_category ) {
    $tc = get_term_by( 'slug', $filter_tour_category, 'moga_tour_category' );
    if ( $tc ) {
        $active_filters['tour_category'] = array( 'label' => $tc->name, 'remove' => esc_url( remove_query_arg( 'tour_category' ) ) );
    }
}
if ( $filter_difficulty && isset( $difficulty_levels[ $filter_difficulty ] ) ) {
    $active_filters['difficulty'] = array( 'label' => $difficulty_levels[ $filter_difficulty ]['label'], 'remove' => esc_url( remove_query_arg( 'difficulty' ) ) );
}
if ( $filter_duration && isset( $duration_options[ $filter_duration ] ) ) {
    $active_filters['duration'] = array( 'label' => $duration_options[ $filter_duration ], 'remove' => esc_url( remove_query_arg( 'duration' ) ) );
}
if ( $filter_tour_type && isset( $tour_types_data[ $filter_tour_type ] ) ) {
    $active_filters['tour_type'] = array( 'label' => $tour_types_data[ $filter_tour_type ]['label'], 'remove' => esc_url( remove_query_arg( 'tour_type' ) ) );
}
foreach ( $filter_amenities as $amenity ) {
    if ( isset( $sidebar_amenities[ $amenity ] ) ) {
        $remaining  = array_values( array_diff( $filter_amenities, array( $amenity ) ) );
        $remove_url = esc_url( remove_query_arg( 'amenities' ) );
        if ( ! empty( $remaining ) ) {
            $remove_url = esc_url( add_query_arg( 'amenities', $remaining, remove_query_arg( 'amenities' ) ) );
        }
        $active_filters[ 'amenity_' . $amenity ] = array( 'label' => $sidebar_amenities[ $amenity ], 'remove' => $remove_url );
    }
}

$has_active_filters = ! empty( $active_filters );

$reset_url = esc_url( add_query_arg(
    array_filter( array(
        'type'        => $search_type,
        'view'        => $current_view,
        'destination' => $filter_destination ?: false,
    ) ),
    get_permalink()
) );

// ============================================================
// 05. ACCORDION CHEVRON HELPER
// ============================================================

/**
 * Output the chevron SVG used in every accordion title.
 */
function moga_accordion_chevron() {
    echo '<svg class="moga-filter-group__chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <polyline points="6 9 12 15 18 9"/>
    </svg>';
}
?>

<main id="moga-main" class="moga-main">
    <div class="moga-container">

        <?php // ---- Page Header ---- ?>
        <div class="moga-search-page-header">
            <h1 class="moga-search-page-header__title">
                <?php echo 'tour' === $search_type
                    ? esc_html__( 'Search Tours', 'moga-travel' )
                    : esc_html__( 'Search Properties', 'moga-travel' ); ?>
            </h1>
            <?php if ( $filter_destination ) : ?>
                <p class="moga-search-page-header__sub">
                    <?php printf(
                        /* translators: %s: destination name */
                        esc_html__( 'Results for "%s"', 'moga-travel' ),
                        esc_html( $filter_destination )
                    ); ?>
                </p>
            <?php endif; ?>
        </div>

        <?php // ---- Mobile Filter Toggle ---- ?>
        <button
            type="button"
            class="moga-filter-toggle"
            id="moga-filter-toggle"
            aria-expanded="false"
            aria-controls="moga-search-sidebar"
        >
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="4" y1="6" x2="20" y2="6"/>
                <line x1="8" y1="12" x2="20" y2="12"/>
                <line x1="12" y1="18" x2="20" y2="18"/>
            </svg>
            <?php esc_html_e( 'Filters', 'moga-travel' ); ?>
            <?php if ( $has_active_filters ) : ?>
                <span class="moga-filter-toggle__count"><?php echo count( $active_filters ); ?></span>
            <?php endif; ?>
        </button>

        <?php // ---- Two Column Layout ---- ?>
        <div class="moga-search-layout">

            <?php // ============================================================
                  // SIDEBAR
                  // ============================================================ ?>
            <aside
                class="moga-search-sidebar"
                id="moga-search-sidebar"
                aria-label="<?php esc_attr_e( 'Search filters', 'moga-travel' ); ?>"
            >
                <form
                    method="GET"
                    action="<?php echo esc_url( get_permalink() ); ?>"
                    class="moga-filter-form"
                    id="moga-filter-form"
                >
                    <input type="hidden" name="type" value="<?php echo esc_attr( $search_type ); ?>">
                    <input type="hidden" name="view" value="<?php echo esc_attr( $current_view ); ?>">
                    <input type="hidden" name="sort" value="<?php echo esc_attr( $current_sort ); ?>">
                    <?php if ( $filter_destination ) : ?>
                        <input type="hidden" name="destination" value="<?php echo esc_attr( $filter_destination ); ?>">
                    <?php endif; ?>
                    <?php if ( $filter_province ) : ?>
                        <input type="hidden" name="province" value="<?php echo esc_attr( $filter_province ); ?>">
                    <?php endif; ?>
                    <?php if ( $filter_district && ! empty( $district_terms ) ) : ?>
                        <?php // Keep district param while user adjusts other filters. ?>
                        <input type="hidden" name="district" value="<?php echo esc_attr( $filter_district ); ?>">
                    <?php endif; ?>

                    <?php // ---- Search Type Tabs ---- ?>
                    <div class="moga-sidebar-type-tabs">
                        <a
                            href="<?php echo esc_url( add_query_arg( array( 'type' => 'property', 'paged' => false ) ) ); ?>"
                            class="moga-sidebar-type-tab <?php echo 'property' === $search_type ? 'is-active' : ''; ?>"
                        >
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                                <polyline points="9 22 9 12 15 12 15 22"/>
                            </svg>
                            <?php esc_html_e( 'Properties', 'moga-travel' ); ?>
                        </a>
                        <a
                            href="<?php echo esc_url( add_query_arg( array( 'type' => 'tour', 'paged' => false ) ) ); ?>"
                            class="moga-sidebar-type-tab <?php echo 'tour' === $search_type ? 'is-active' : ''; ?>"
                        >
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <polygon points="10 8 16 12 10 16 10 8"/>
                            </svg>
                            <?php esc_html_e( 'Tours', 'moga-travel' ); ?>
                        </a>
                    </div>

                    <?php // ---- Price Range (open by default) ---- ?>
                    <div class="moga-filter-group" data-group="price">
                        <button type="button" class="moga-filter-group__title" aria-expanded="true">
                            <?php esc_html_e( 'Price Range', 'moga-travel' ); ?>
                            <?php moga_accordion_chevron(); ?>
                        </button>
                        <div class="moga-filter-group__content">
                            <div class="moga-price-range">
                                <div class="moga-price-range__field">
                                    <label for="price_min"><?php esc_html_e( 'Min', 'moga-travel' ); ?></label>
                                    <input type="number" id="price_min" name="price_min" value="<?php echo esc_attr( $filter_price_min ); ?>" min="0" step="50" placeholder="0" class="moga-price-range__input">
                                </div>
                                <span class="moga-price-range__sep">—</span>
                                <div class="moga-price-range__field">
                                    <label for="price_max"><?php esc_html_e( 'Max', 'moga-travel' ); ?></label>
                                    <input type="number" id="price_max" name="price_max" value="<?php echo esc_attr( $filter_price_max ); ?>" min="0" step="50" placeholder="<?php esc_attr_e( 'Any', 'moga-travel' ); ?>" class="moga-price-range__input">
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ( 'property' === $search_type ) : ?>

                        <?php // ---- Property Type (open by default) ---- ?>
                        <?php if ( ! empty( $property_types ) ) : ?>
                            <div class="moga-filter-group" data-group="property_type">
                                <button type="button" class="moga-filter-group__title" aria-expanded="true">
                                    <?php esc_html_e( 'Property Type', 'moga-travel' ); ?>
                                    <?php moga_accordion_chevron(); ?>
                                </button>
                                <div class="moga-filter-group__content">
                                    <div class="moga-filter-options">
                                        <?php foreach ( $property_types as $pt ) : ?>
                                            <label class="moga-filter-option">
                                                <input type="radio" name="property_type" value="<?php echo esc_attr( $pt->slug ); ?>" <?php checked( $filter_property_type, $pt->slug ); ?>>
                                                <span class="moga-filter-option__label"><?php echo esc_html( $pt->name ); ?></span>
                                                <span class="moga-filter-option__count"><?php echo esc_html( $pt->count ); ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php // ---- Province / State / Governorate (closed by default) ---- ?>
                        <?php if ( ! empty( $province_terms ) ) : ?>
                            <div class="moga-filter-group" data-group="province">
                                <button
                                    type="button"
                                    class="moga-filter-group__title"
                                    aria-expanded="<?php echo $filter_province ? 'true' : 'false'; ?>"
                                >
                                    <?php esc_html_e( 'Province / State', 'moga-travel' ); ?>
                                    <?php moga_accordion_chevron(); ?>
                                </button>
                                <div class="moga-filter-group__content" <?php echo $filter_province ? '' : 'hidden'; ?>>
                                    <div class="moga-filter-options moga-filter-options--scrollable">
                                        <?php foreach ( $province_terms as $province ) : ?>
                                            <label class="moga-filter-option">
                                                <input type="radio" name="province" value="<?php echo esc_attr( $province->name ); ?>" <?php checked( $filter_province, $province->name ); ?>>
                                                <span class="moga-filter-option__label"><?php echo esc_html( $province->name ); ?></span>
                                                <span class="moga-filter-option__count"><?php echo esc_html( $province->count ); ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php // ---- City (shown only when a province is selected and has cities) ---- ?>
                        <?php if ( ! empty( $city_terms ) ) : ?>
                            <div class="moga-filter-group" data-group="location">
                                <button
                                    type="button"
                                    class="moga-filter-group__title"
                                    aria-expanded="<?php echo $filter_location ? 'true' : 'false'; ?>"
                                >
                                    <?php esc_html_e( 'City', 'moga-travel' ); ?>
                                    <?php moga_accordion_chevron(); ?>
                                </button>
                                <div class="moga-filter-group__content" <?php echo $filter_location ? '' : 'hidden'; ?>>
                                    <div class="moga-filter-options moga-filter-options--scrollable">
                                        <?php foreach ( $city_terms as $city ) : ?>
                                            <label class="moga-filter-option">
                                                <input type="radio" name="location" value="<?php echo esc_attr( $city->name ); ?>" <?php checked( $filter_location, $city->name ); ?>>
                                                <span class="moga-filter-option__label"><?php echo esc_html( $city->name ); ?></span>
                                                <span class="moga-filter-option__count"><?php echo esc_html( $city->count ); ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php // ---- District / Area (shown only when a city is selected and has districts) ---- ?>
                        <?php if ( ! empty( $district_terms ) ) : ?>
                            <div class="moga-filter-group" data-group="district">
                                <button
                                    type="button"
                                    class="moga-filter-group__title"
                                    aria-expanded="<?php echo $filter_district ? 'true' : 'false'; ?>"
                                >
                                    <?php esc_html_e( 'District / Area', 'moga-travel' ); ?>
                                    <?php moga_accordion_chevron(); ?>
                                </button>
                                <div
                                    class="moga-filter-group__content"
                                    <?php echo $filter_district ? '' : 'hidden'; ?>
                                >
                                    <div class="moga-filter-options moga-filter-options--scrollable">
                                        <?php foreach ( $district_terms as $district ) : ?>
                                            <label class="moga-filter-option">
                                                <input
                                                    type="radio"
                                                    name="district"
                                                    value="<?php echo esc_attr( $district->name ); ?>"
                                                    <?php checked( $filter_district, $district->name ); ?>
                                                >
                                                <span class="moga-filter-option__label">
                                                    <?php echo esc_html( $district->name ); ?>
                                                </span>
                                                <span class="moga-filter-option__count">
                                                    <?php echo esc_html( $district->count ); ?>
                                                </span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php // ---- Amenities (closed by default) ---- ?>
                        <div class="moga-filter-group" data-group="amenities">
                            <button type="button" class="moga-filter-group__title" aria-expanded="false">
                                <?php esc_html_e( 'Amenities', 'moga-travel' ); ?>
                                <?php moga_accordion_chevron(); ?>
                            </button>
                            <div class="moga-filter-group__content" hidden>
                                <div class="moga-filter-options">
                                    <?php foreach ( $sidebar_amenities as $key => $label ) : ?>
                                        <label class="moga-filter-option">
                                            <input type="checkbox" name="amenities[]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $filter_amenities, true ) ); ?>>
                                            <span class="moga-filter-option__label"><?php echo esc_html( $label ); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                    <?php elseif ( 'tour' === $search_type ) : ?>

                        <?php // ---- Tour Category (open by default) ---- ?>
                        <?php if ( ! empty( $tour_categories ) ) : ?>
                            <div class="moga-filter-group" data-group="tour_category">
                                <button type="button" class="moga-filter-group__title" aria-expanded="true">
                                    <?php esc_html_e( 'Tour Category', 'moga-travel' ); ?>
                                    <?php moga_accordion_chevron(); ?>
                                </button>
                                <div class="moga-filter-group__content">
                                    <div class="moga-filter-options">
                                        <?php foreach ( $tour_categories as $tc ) : ?>
                                            <label class="moga-filter-option">
                                                <input type="radio" name="tour_category" value="<?php echo esc_attr( $tc->slug ); ?>" <?php checked( $filter_tour_category, $tc->slug ); ?>>
                                                <span class="moga-filter-option__label"><?php echo esc_html( $tc->name ); ?></span>
                                                <span class="moga-filter-option__count"><?php echo esc_html( $tc->count ); ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php // ---- Difficulty (closed by default) ---- ?>
                        <?php if ( ! empty( $difficulty_levels ) ) : ?>
                            <div class="moga-filter-group" data-group="difficulty">
                                <button type="button" class="moga-filter-group__title" aria-expanded="false">
                                    <?php esc_html_e( 'Difficulty', 'moga-travel' ); ?>
                                    <?php moga_accordion_chevron(); ?>
                                </button>
                                <div class="moga-filter-group__content" hidden>
                                    <div class="moga-filter-options">
                                        <?php foreach ( $difficulty_levels as $key => $level ) : ?>
                                            <label class="moga-filter-option">
                                                <input type="radio" name="difficulty" value="<?php echo esc_attr( $key ); ?>" <?php checked( $filter_difficulty, $key ); ?>>
                                                <span class="moga-filter-option__label" style="color: <?php echo esc_attr( $level['color'] ); ?>; font-weight: 600;">
                                                    <?php echo esc_html( $level['label'] ); ?>
                                                </span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php // ---- Duration (closed by default) ---- ?>
                        <div class="moga-filter-group" data-group="duration">
                            <button type="button" class="moga-filter-group__title" aria-expanded="false">
                                <?php esc_html_e( 'Duration', 'moga-travel' ); ?>
                                <?php moga_accordion_chevron(); ?>
                            </button>
                            <div class="moga-filter-group__content" hidden>
                                <div class="moga-filter-options">
                                    <?php foreach ( $duration_options as $key => $label ) : ?>
                                        <label class="moga-filter-option">
                                            <input type="radio" name="duration" value="<?php echo esc_attr( $key ); ?>" <?php checked( $filter_duration, $key ); ?>>
                                            <span class="moga-filter-option__label"><?php echo esc_html( $label ); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <?php // ---- Tour Type (closed by default) ---- ?>
                        <?php if ( ! empty( $tour_types_data ) ) : ?>
                            <div class="moga-filter-group" data-group="tour_type">
                                <button type="button" class="moga-filter-group__title" aria-expanded="false">
                                    <?php esc_html_e( 'Tour Type', 'moga-travel' ); ?>
                                    <?php moga_accordion_chevron(); ?>
                                </button>
                                <div class="moga-filter-group__content" hidden>
                                    <div class="moga-filter-options">
                                        <?php foreach ( $tour_types_data as $key => $type ) : ?>
                                            <label class="moga-filter-option">
                                                <input type="radio" name="tour_type" value="<?php echo esc_attr( $key ); ?>" <?php checked( $filter_tour_type, $key ); ?>>
                                                <span class="moga-filter-option__label"><?php echo esc_html( $type['label'] ); ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                    <?php endif; ?>

                    <?php // ---- Guest Rating (open by default) ---- ?>
                    <div class="moga-filter-group" data-group="rating">
                        <button type="button" class="moga-filter-group__title" aria-expanded="true">
                            <?php esc_html_e( 'Guest Rating', 'moga-travel' ); ?>
                            <?php moga_accordion_chevron(); ?>
                        </button>
                        <div class="moga-filter-group__content">
                            <div class="moga-filter-options">
                                <?php
                                $rating_options = array(
                                    9 => __( 'Superb 9+', 'moga-travel' ),
                                    8 => __( 'Very Good 8+', 'moga-travel' ),
                                    7 => __( 'Good 7+', 'moga-travel' ),
                                    6 => __( 'Pleasant 6+', 'moga-travel' ),
                                );
                                foreach ( $rating_options as $value => $label ) : ?>
                                    <label class="moga-filter-option">
                                        <input type="radio" name="rating" value="<?php echo esc_attr( $value ); ?>" <?php checked( (string) $filter_rating, (string) $value ); ?>>
                                        <span class="moga-filter-option__label"><?php echo esc_html( $label ); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <?php // ---- Apply & Reset ---- ?>
                    <div class="moga-filter-actions">
                        <button type="submit" class="moga-btn moga-btn--primary moga-w-100">
                            <?php esc_html_e( 'Apply Filters', 'moga-travel' ); ?>
                        </button>
                        <?php if ( $has_active_filters ) : ?>
                            <a href="<?php echo $reset_url; ?>" class="moga-filter-reset">
                                <?php esc_html_e( 'Reset All Filters', 'moga-travel' ); ?>
                            </a>
                        <?php endif; ?>
                    </div>

                </form>
            </aside>
            <?php // ---- End Sidebar ---- ?>


            <?php // ============================================================
                  // MAIN CONTENT
                  // ============================================================ ?>
            <div class="moga-search-main">

                <?php // ---- Results Toolbar ---- ?>
                <div class="moga-results-toolbar">
                    <p class="moga-results-toolbar__count">
                        <?php if ( $total_results > 0 ) :
                            printf(
                                esc_html( _n( '%d result found', '%d results found', $total_results, 'moga-travel' ) ),
                                esc_html( number_format_i18n( $total_results ) )
                            );
                        else :
                            esc_html_e( 'No results found', 'moga-travel' );
                        endif; ?>
                    </p>
                    <div class="moga-results-toolbar__controls">
                        <div class="moga-sort-wrap">
                            <label for="moga-sort" class="moga-sort-label"><?php esc_html_e( 'Sort:', 'moga-travel' ); ?></label>
                            <select id="moga-sort" class="moga-sort-select">
                                <?php foreach ( $sort_options as $value => $label ) : ?>
                                    <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current_sort, $value ); ?>>
                                        <?php echo esc_html( $label ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="moga-view-toggle" role="group" aria-label="<?php esc_attr_e( 'View mode', 'moga-travel' ); ?>">
                            <a href="<?php echo esc_url( add_query_arg( 'view', 'grid' ) ); ?>" class="moga-view-toggle__btn <?php echo 'grid' === $current_view ? 'is-active' : ''; ?>" aria-label="<?php esc_attr_e( 'Grid view', 'moga-travel' ); ?>" aria-pressed="<?php echo 'grid' === $current_view ? 'true' : 'false'; ?>">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/>
                                </svg>
                            </a>
                            <a href="<?php echo esc_url( add_query_arg( 'view', 'list' ) ); ?>" class="moga-view-toggle__btn <?php echo 'list' === $current_view ? 'is-active' : ''; ?>" aria-label="<?php esc_attr_e( 'List view', 'moga-travel' ); ?>" aria-pressed="<?php echo 'list' === $current_view ? 'true' : 'false'; ?>">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
                <?php // ---- End Toolbar ---- ?>

                <?php // ---- Active Filter Pills ---- ?>
                <?php if ( $has_active_filters ) : ?>
                    <div class="moga-active-filters" role="list" aria-label="<?php esc_attr_e( 'Active filters', 'moga-travel' ); ?>">
                        <?php foreach ( $active_filters as $filter ) : ?>
                            <a href="<?php echo esc_url( $filter['remove'] ); ?>" class="moga-active-filter" role="listitem">
                                <?php echo esc_html( $filter['label'] ); ?>
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                                </svg>
                            </a>
                        <?php endforeach; ?>
                        <a href="<?php echo $reset_url; ?>" class="moga-active-filter moga-active-filter--clear">
                            <?php esc_html_e( 'Clear all', 'moga-travel' ); ?>
                        </a>
                    </div>
                <?php endif; ?>

                <?php // ---- Results ---- ?>
                <?php if ( $search_results->have_posts() ) : ?>
                    <div class="moga-results moga-results--<?php echo esc_attr( $current_view ); ?>">
                        <?php while ( $search_results->have_posts() ) : $search_results->the_post(); ?>
                            <?php
                            if ( 'tour' === $search_type ) {
                                get_template_part( 'list' === $current_view ? 'template-parts/tour/card-list' : 'template-parts/tour/card-grid' );
                            } else {
                                get_template_part( 'list' === $current_view ? 'template-parts/property/card-list' : 'template-parts/property/card-grid' );
                            }
                            ?>
                        <?php endwhile; ?>
                        <?php wp_reset_postdata(); ?>
                    </div>

                    <?php // ---- Pagination ---- ?>
                    <?php if ( $total_pages > 1 ) : ?>
                        <nav class="moga-pagination" aria-label="<?php esc_attr_e( 'Results pages', 'moga-travel' ); ?>">
                            <?php
                            $base_url_no_paged = esc_url( remove_query_arg( 'paged' ) );
                            $pages = paginate_links( array(
                                'base'      => add_query_arg( 'paged', '%#%', $base_url_no_paged ),
                                'format'    => '',
                                'current'   => $current_page,
                                'total'     => $total_pages,
                                'prev_text' => '&laquo;',
                                'next_text' => '&raquo;',
                                'type'      => 'array',
                                'mid_size'  => 2,
                            ) );
                            if ( $pages ) : ?>
                                <ul class="moga-pagination__list">
                                    <?php foreach ( $pages as $page ) : ?>
                                        <li class="moga-pagination__item"><?php echo $page; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </nav>
                    <?php endif; ?>

                <?php else : ?>
                    <div class="moga-no-results">
                        <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" class="moga-no-results__icon">
                            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        </svg>
                        <h3 class="moga-no-results__title"><?php esc_html_e( 'No results found', 'moga-travel' ); ?></h3>
                        <p class="moga-no-results__text"><?php esc_html_e( 'Try adjusting your filters or search for a different destination.', 'moga-travel' ); ?></p>
                        <?php if ( $has_active_filters ) : ?>
                            <a href="<?php echo $reset_url; ?>" class="moga-btn moga-btn--outline moga-mt-2">
                                <?php esc_html_e( 'Clear All Filters', 'moga-travel' ); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            </div>
            <?php // ---- End Main Content ---- ?>

        </div>
        <?php // ---- End Two Column Layout ---- ?>

    </div>
</main>

<?php get_footer(); ?>