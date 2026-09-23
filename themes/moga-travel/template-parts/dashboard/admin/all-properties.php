<?php

/**
 * Admin Dashboard — All Properties Tab
 *
 * Platform-wide property management table for the administrator.
 * Shows every property listing across all vendors with stat cards,
 * filters, sortable table, and quick-action links.
 *
 * Filters are URL-based — consistent with the rest of the dashboard.
 *
 * @package MogaTravel
 * @since   1.0.0
 *
 * @var WP_User $args['user']    Current user object.
 * @var int     $args['user_id'] Current user ID.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;

$dashboard_url = get_permalink( get_option( 'moga_page_dashboard' ) );
$current_url   = add_query_arg( 'tab', 'all-properties', $dashboard_url );

// ── Sanitize filter inputs ────────────────────────────────────────────────────

$filter_status   = isset( $_GET['pp_status'] )  ? sanitize_key( $_GET['pp_status'] )              : '';
$filter_type     = isset( $_GET['pp_type'] )    ? absint( $_GET['pp_type'] )                       : 0;
$filter_search   = isset( $_GET['pp_search'] )  ? sanitize_text_field( wp_unslash( $_GET['pp_search'] ) ) : '';
$filter_owner    = isset( $_GET['pp_owner'] )   ? absint( $_GET['pp_owner'] )                      : 0;
$orderby         = isset( $_GET['pp_order'] )   ? sanitize_key( $_GET['pp_order'] )                : 'post_date';
$order           = isset( $_GET['pp_dir'] ) && strtoupper( $_GET['pp_dir'] ) === 'ASC' ? 'ASC' : 'DESC';
$paged           = isset( $_GET['pp_paged'] )   ? max( 1, (int) $_GET['pp_paged'] )               : 1;
$per_page        = isset( $_GET['pp_per_page'] ) ? (int) $_GET['pp_per_page'] : 20;
$per_page        = in_array( $per_page, array( 10, 20, 25, 50, 100 ), true ) ? $per_page : 20;
$offset          = ( $paged - 1 ) * $per_page;

// Whitelist orderby columns.
$allowed_orderby = array( 'post_title', 'post_date', 'post_status', 'post_author' );
if ( ! in_array( $orderby, $allowed_orderby, true ) ) {
    $orderby = 'post_date';
}

// ── Stat cards — counts across ALL properties (no filters) ───────────────────

$stat_total     = (int) wp_count_posts( 'moga_property' )->publish
                + (int) wp_count_posts( 'moga_property' )->pending
                + (int) wp_count_posts( 'moga_property' )->draft
                + (int) wp_count_posts( 'moga_property' )->private;
$stat_published = (int) wp_count_posts( 'moga_property' )->publish;
$stat_pending   = (int) wp_count_posts( 'moga_property' )->pending;
$stat_draft     = (int) wp_count_posts( 'moga_property' )->draft;

// ── Property type terms for filter dropdown ───────────────────────────────────

$property_types = get_terms( array(
    'taxonomy'   => 'moga_property_type',
    'hide_empty' => false,
    'orderby'    => 'name',
    'order'      => 'ASC',
) );
if ( is_wp_error( $property_types ) ) {
    $property_types = array();
}

// ── Build WP_Query args ───────────────────────────────────────────────────────

$query_args = array(
    'post_type'      => 'moga_property',
    'post_status'    => array( 'publish', 'pending', 'draft', 'private' ),
    'posts_per_page' => $per_page,
    'offset'         => $offset,
    'orderby'        => $orderby,
    'order'          => $order,
);

if ( $filter_status && in_array( $filter_status, array( 'publish', 'pending', 'draft', 'private' ), true ) ) {
    $query_args['post_status'] = $filter_status;
}

if ( $filter_search !== '' ) {
    $query_args['s'] = $filter_search;
}

if ( $filter_owner > 0 ) {
    $query_args['author'] = $filter_owner;
}

if ( $filter_type > 0 ) {
    $query_args['tax_query'] = array(
        array(
            'taxonomy' => 'moga_property_type',
            'field'    => 'term_id',
            'terms'    => $filter_type,
        ),
    );
}

// Count query for pagination.
$count_args              = $query_args;
$count_args['fields']    = 'ids';
$count_args['offset']    = 0;
$count_args['posts_per_page'] = -1;
$count_query             = new WP_Query( $count_args );
$total_rows              = (int) $count_query->found_posts;
$total_pages             = max( 1, (int) ceil( $total_rows / $per_page ) );
if ( $paged > $total_pages ) {
    $paged = $total_pages;
}
wp_reset_postdata();

// Main query.
$properties_query = new WP_Query( $query_args );
$properties       = $properties_query->posts;
wp_reset_postdata();

// ── Helpers ───────────────────────────────────────────────────────────────────

/**
 * Return CSS modifier class and human label for a post status.
 *
 * @param string $status
 * @return array { class: string, label: string }
 */
function moga_property_status_ui( $status ) {
    $map = array(
        'publish' => array( 'class' => 'success', 'label' => __( 'Published', 'moga-travel' ) ),
        'pending' => array( 'class' => 'warning', 'label' => __( 'Pending',   'moga-travel' ) ),
        'draft'   => array( 'class' => 'neutral', 'label' => __( 'Draft',     'moga-travel' ) ),
        'private' => array( 'class' => 'info',    'label' => __( 'Private',   'moga-travel' ) ),
        'trash'   => array( 'class' => 'danger',  'label' => __( 'Trash',     'moga-travel' ) ),
    );
    return $map[ $status ] ?? array( 'class' => 'neutral', 'label' => ucfirst( $status ) );
}

/**
 * Build a sort URL, toggling direction if already sorted by this column.
 *
 * @param string $col
 * @param string $current_orderby
 * @param string $current_order
 * @param string $base_url
 * @return string
 */
function moga_prop_sort_url( $col, $current_orderby, $current_order, $base_url ) {
    $dir = ( $current_orderby === $col && $current_order === 'DESC' ) ? 'ASC' : 'DESC';
    return add_query_arg( array( 'pp_order' => $col, 'pp_dir' => $dir ), $base_url );
}

/**
 * Return a sort indicator arrow for the column header.
 *
 * @param string $col
 * @param string $current_orderby
 * @param string $current_order
 * @return string
 */
function moga_prop_sort_indicator( $col, $current_orderby, $current_order ) {
    if ( $current_orderby !== $col ) {
        return '<span class="moga-db-table__sort-icon moga-db-table__sort-icon--neutral" aria-hidden="true">↕</span>';
    }
    return $current_order === 'ASC'
        ? '<span class="moga-db-table__sort-icon moga-db-table__sort-icon--asc" aria-hidden="true">↑</span>'
        : '<span class="moga-db-table__sort-icon moga-db-table__sort-icon--desc" aria-hidden="true">↓</span>';
}

// Active filter count — excludes chip-triggered params.
$pp_via_chip = ! empty( $_GET['pp_chip'] );

$active_filter_count = $pp_via_chip ? 0 :
    (int) ( $filter_status !== '' )
  + (int) ( $filter_type > 0 )
  + (int) ( $filter_owner > 0 )
  + (int) ( $filter_search !== '' );

$currency_symbol = get_option( 'moga_currency_symbol', '$' );

// Property owners list for filter dropdown.
$property_owners = $wpdb->get_results(
    "SELECT DISTINCT u.ID, u.display_name
     FROM {$wpdb->posts} p
     JOIN {$wpdb->users} u ON p.post_author = u.ID
     WHERE p.post_type = 'moga_property' AND p.post_status != 'trash'
     ORDER BY u.display_name ASC"
);
?>

<div class="moga-db-all-properties">

    <?php // ── Stat Cards ────────────────────────────────────────────────────── ?>
    <div class="moga-db-stats moga-db-stats--4col">

        <div class="moga-db-stats__card">
            <div class="moga-db-stats__value"><?php echo esc_html( number_format_i18n( $stat_total ) ); ?></div>
            <div class="moga-db-stats__label"><?php esc_html_e( 'Total Properties', 'moga-travel' ); ?></div>
        </div>

        <div class="moga-db-stats__card moga-db-stats__card--success">
            <div class="moga-db-stats__value"><?php echo esc_html( number_format_i18n( $stat_published ) ); ?></div>
            <div class="moga-db-stats__label"><?php esc_html_e( 'Published', 'moga-travel' ); ?></div>
        </div>

        <div class="moga-db-stats__card moga-db-stats__card--warning">
            <div class="moga-db-stats__value"><?php echo esc_html( number_format_i18n( $stat_pending ) ); ?></div>
            <div class="moga-db-stats__label"><?php esc_html_e( 'Pending Review', 'moga-travel' ); ?></div>
        </div>

        <div class="moga-db-stats__card moga-db-stats__card--neutral">
            <div class="moga-db-stats__value"><?php echo esc_html( number_format_i18n( $stat_draft ) ); ?></div>
            <div class="moga-db-stats__label"><?php esc_html_e( 'Drafts', 'moga-travel' ); ?></div>
        </div>

    </div>

    <?php // ── Toolbar ───────────────────────────────────────────────────────── ?>
    <div class="moga-db-toolbar">
        <form method="get" action="<?php echo esc_url( $current_url ); ?>"
              class="moga-db-toolbar__form" id="moga-pp-filter-form">

            <input type="hidden" name="tab" value="all-properties">
            <?php if ( $orderby !== 'post_date' ) : ?><input type="hidden" name="pp_order" value="<?php echo esc_attr( $orderby ); ?>"><?php endif; ?>
            <?php if ( $order !== 'DESC' ) : ?><input type="hidden" name="pp_dir" value="<?php echo esc_attr( $order ); ?>"><?php endif; ?>

            <div class="moga-db-toolbar__top">

                <div class="moga-db-toolbar__search">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
                    </svg>
                    <input type="text" name="pp_search"
                           value="<?php echo esc_attr( $filter_search ); ?>"
                           placeholder="<?php esc_attr_e( 'Search by property title or owner…', 'moga-travel' ); ?>"
                           class="moga-db-toolbar__search-input" autocomplete="off">
                    <span class="moga-db-toolbar__kbd">⌘K</span>
                </div>

                <div class="moga-db-toolbar__actions">

                    <button type="button"
                            class="moga-db-toolbar__filter-toggle"
                            id="moga-pp-filter-toggle"
                            aria-expanded="<?php echo $active_filter_count > 0 ? 'true' : 'false'; ?>"
                            aria-controls="moga-pp-filters">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none"
                             viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18M7 10h10M10 16h4"/>
                        </svg>
                        <?php esc_html_e( 'Filters', 'moga-travel' ); ?>
                        <?php if ( $active_filter_count > 0 ) : ?>
                            <span class="moga-db-toolbar__filter-badge"><?php echo esc_html( $active_filter_count ); ?></span>
                        <?php endif; ?>
                    </button>

                    <select name="pp_per_page" class="moga-db-toolbar__per-page-select"
                            onchange="this.form.submit()">
                        <?php foreach ( array( 10, 20, 25, 50, 100 ) as $n ) : ?>
                            <option value="<?php echo esc_attr( $n ); ?>" <?php selected( $per_page, $n ); ?>>
                                <?php echo esc_html( $n ); ?> / <?php esc_html_e( 'page', 'moga-travel' ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=moga_property' ) ); ?>"
                       class="moga-db-toolbar__cta" target="_blank">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none"
                             viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                        <?php esc_html_e( 'New Property', 'moga-travel' ); ?>
                    </a>

                </div>
            </div>

            <?php // ── Quick chips — full page navigation ── ?>
            <div class="moga-db-toolbar__quick">
                <span class="moga-db-toolbar__quick-label">
                    <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    <?php esc_html_e( 'Quick:', 'moga-travel' ); ?>
                </span>
                <?php
                $pp_chips = array(
                    array( 'label' => __( 'All Properties', 'moga-travel' ), 'dot' => '',        'active' => ( $filter_status === '' && $filter_search === '' && $filter_owner === 0 ), 'url' => add_query_arg( array( 'tab' => 'all-properties', 'pp_chip' => '1' ), $dashboard_url ) ),
                    array( 'label' => __( 'Published',      'moga-travel' ), 'dot' => '#10b981', 'active' => $filter_status === 'publish',  'url' => add_query_arg( array( 'tab' => 'all-properties', 'pp_status' => 'publish',  'pp_chip' => '1' ), $dashboard_url ) ),
                    array( 'label' => __( 'Pending Review', 'moga-travel' ), 'dot' => '#f59e0b', 'active' => $filter_status === 'pending',  'url' => add_query_arg( array( 'tab' => 'all-properties', 'pp_status' => 'pending',  'pp_chip' => '1' ), $dashboard_url ) ),
                    array( 'label' => __( 'Drafts',         'moga-travel' ), 'dot' => '#9ca3af', 'active' => $filter_status === 'draft',    'url' => add_query_arg( array( 'tab' => 'all-properties', 'pp_status' => 'draft',    'pp_chip' => '1' ), $dashboard_url ) ),
                );
                foreach ( $pp_chips as $chip ) : ?>
                    <a href="<?php echo esc_url( $chip['url'] ); ?>"
                       class="moga-db-toolbar__chip<?php echo $chip['active'] ? ' is-active' : ''; ?>">
                        <?php if ( $chip['dot'] ) : ?>
                            <span class="moga-db-toolbar__chip-dot" style="background:<?php echo esc_attr( $chip['dot'] ); ?>;"></span>
                        <?php endif; ?>
                        <?php echo esc_html( $chip['label'] ); ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <?php // ── Filter panel ── ?>
            <div class="moga-db-toolbar__filters<?php echo $active_filter_count > 0 ? ' is-open' : ''; ?>"
                 id="moga-pp-filters">

                <div class="moga-db-toolbar__filters-row">

                    <div class="moga-db-toolbar__filter-group">
                        <label for="moga-pp-status"><?php esc_html_e( 'Status', 'moga-travel' ); ?></label>
                        <select name="pp_status" id="moga-pp-status" class="moga-db-toolbar__select">
                            <option value=""><?php esc_html_e( 'All Statuses', 'moga-travel' ); ?></option>
                            <?php foreach ( array(
                                'publish' => __( 'Published',      'moga-travel' ),
                                'pending' => __( 'Pending Review', 'moga-travel' ),
                                'draft'   => __( 'Draft',          'moga-travel' ),
                                'private' => __( 'Private',        'moga-travel' ),
                            ) as $val => $label ) :
                                printf( '<option value="%s"%s>%s</option>', esc_attr( $val ), selected( $filter_status, $val, false ), esc_html( $label ) );
                            endforeach; ?>
                        </select>
                    </div>

                    <div class="moga-db-toolbar__filter-group">
                        <label for="moga-pp-type"><?php esc_html_e( 'Property Type', 'moga-travel' ); ?></label>
                        <select name="pp_type" id="moga-pp-type" class="moga-db-toolbar__select">
                            <option value="0"><?php esc_html_e( 'All Types', 'moga-travel' ); ?></option>
                            <?php foreach ( $property_types as $term ) : ?>
                                <option value="<?php echo esc_attr( $term->term_id ); ?>" <?php selected( $filter_type, $term->term_id ); ?>>
                                    <?php echo esc_html( $term->name ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="moga-db-toolbar__filter-group">
                        <label for="moga-pp-owner"><?php esc_html_e( 'Owner', 'moga-travel' ); ?></label>
                        <select name="pp_owner" id="moga-pp-owner" class="moga-db-toolbar__select">
                            <option value="0"><?php esc_html_e( 'All Owners', 'moga-travel' ); ?></option>
                            <?php foreach ( $property_owners as $owner ) : ?>
                                <option value="<?php echo esc_attr( $owner->ID ); ?>" <?php selected( $filter_owner, (int) $owner->ID ); ?>>
                                    <?php echo esc_html( $owner->display_name ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="moga-db-toolbar__filter-group">
                        <label><?php esc_html_e( 'Date Added', 'moga-travel' ); ?></label>
                        <div class="moga-db-toolbar__date-range">
                            <input type="date" name="pp_from" id="moga-pp-from"
                                   value="<?php echo esc_attr( isset( $_GET['pp_from'] ) ? sanitize_text_field( $_GET['pp_from'] ) : '' ); ?>"
                                   class="moga-db-toolbar__input">
                            <span class="moga-db-toolbar__date-sep">–</span>
                            <input type="date" name="pp_to" id="moga-pp-to"
                                   value="<?php echo esc_attr( isset( $_GET['pp_to'] ) ? sanitize_text_field( $_GET['pp_to'] ) : '' ); ?>"
                                   class="moga-db-toolbar__input">
                        </div>
                    </div>

                </div>

                <div class="moga-db-toolbar__filters-actions">
                    <a href="<?php echo esc_url( add_query_arg( 'tab', 'all-properties', $dashboard_url ) ); ?>"
                       class="moga-db-toolbar__btn-clear">
                        <?php esc_html_e( 'Clear Filters', 'moga-travel' ); ?>
                    </a>
                    <button type="submit" class="moga-db-toolbar__btn-apply">
                        <?php esc_html_e( 'Apply Filters', 'moga-travel' ); ?>
                    </button>
                </div>

            </div>

        </form>
    </div>

    <?php // ── Results summary ───────────────────────────────────────────────── ?>
    <div class="moga-db-table-meta">
        <p class="moga-db-table-meta__count">
            <?php printf(
                esc_html( _n( 'Showing %s property found', 'Showing %s properties found', $total_rows, 'moga-travel' ) ),
                '<strong>' . esc_html( number_format_i18n( $total_rows ) ) . '</strong>'
            ); ?>
        </p>
        <span class="moga-db-table-meta__updated">
            <?php esc_html_e( 'Updated just now', 'moga-travel' ); ?>
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none"
                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
        </span>
    </div>

    <?php // ── Properties Table ──────────────────────────────────────────────── ?>
    <div class="moga-db-table-wrap">
        <table class="moga-db-table" id="moga-pp-table">
            <thead>
                <tr>
                    <th class="moga-db-table__th moga-db-table__th--sortable">
                        <a href="<?php echo esc_url( moga_prop_sort_url( 'post_title', $orderby, $order, $current_url ) ); ?>">
                            <?php esc_html_e( 'Property', 'moga-travel' ); ?>
                            <?php echo moga_prop_sort_indicator( 'post_title', $orderby, $order ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </a>
                    </th>
                    <th class="moga-db-table__th moga-db-table__th--sortable">
                        <a href="<?php echo esc_url( moga_prop_sort_url( 'post_author', $orderby, $order, $current_url ) ); ?>">
                            <?php esc_html_e( 'Owner', 'moga-travel' ); ?>
                            <?php echo moga_prop_sort_indicator( 'post_author', $orderby, $order ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </a>
                    </th>
                    <th class="moga-db-table__th"><?php esc_html_e( 'Type', 'moga-travel' ); ?></th>
                    <th class="moga-db-table__th"><?php esc_html_e( 'Location', 'moga-travel' ); ?></th>
                    <th class="moga-db-table__th"><?php esc_html_e( 'Price', 'moga-travel' ); ?></th>
                    <th class="moga-db-table__th moga-db-table__th--sortable">
                        <a href="<?php echo esc_url( moga_prop_sort_url( 'post_status', $orderby, $order, $current_url ) ); ?>">
                            <?php esc_html_e( 'Status', 'moga-travel' ); ?>
                            <?php echo moga_prop_sort_indicator( 'post_status', $orderby, $order ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </a>
                    </th>
                    <th class="moga-db-table__th moga-db-table__th--sortable">
                        <a href="<?php echo esc_url( moga_prop_sort_url( 'post_date', $orderby, $order, $current_url ) ); ?>">
                            <?php esc_html_e( 'Created', 'moga-travel' ); ?>
                            <?php echo moga_prop_sort_indicator( 'post_date', $orderby, $order ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </a>
                    </th>
                    <th class="moga-db-table__th moga-db-table__th--actions">
                        <?php esc_html_e( 'Actions', 'moga-travel' ); ?>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $properties ) ) : ?>
                    <tr>
                        <td colspan="8" class="moga-db-table__empty">
                            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                 stroke-width="1.2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                            <p><?php esc_html_e( 'No properties found.', 'moga-travel' ); ?></p>
                            <?php if ( $active_filter_count > 0 ) : ?>
                                <a href="<?php echo esc_url( add_query_arg( 'tab', 'all-properties', $dashboard_url ) ); ?>"
                                   class="moga-btn moga-btn--ghost moga-btn--sm">
                                    <?php esc_html_e( 'Clear filters', 'moga-travel' ); ?>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else : ?>
                    <?php foreach ( $properties as $property ) :

                        $post_id     = $property->ID;
                        $title       = $property->post_title ?: __( '(no title)', 'moga-travel' );
                        $status_ui   = moga_property_status_ui( $property->post_status );
                        $edit_link   = get_edit_post_link( $post_id );
                        $view_link   = get_permalink( $post_id );
                        $trash_link  = get_delete_post_link( $post_id );
                        $created     = date_i18n( get_option( 'date_format' ), strtotime( $property->post_date ) );

                        // Owner.
                        $owner      = get_userdata( $property->post_author );
                        $owner_name = $owner ? $owner->display_name : __( '(unknown)', 'moga-travel' );

                        // Property type taxonomy term.
                        $type_terms = get_the_terms( $post_id, 'moga_property_type' );
                        $type_label = ( ! is_wp_error( $type_terms ) && ! empty( $type_terms ) )
                            ? $type_terms[0]->name
                            : '—';

                        // Location — from moga_location taxonomy.
                        $loc_terms   = get_the_terms( $post_id, 'moga_location' );
                        $loc_label   = '—';
                        if ( ! is_wp_error( $loc_terms ) && ! empty( $loc_terms ) ) {
                            // Use the deepest (last) term which is the city/district.
                            $loc_label = end( $loc_terms )->name;
                        }

                        // Display price — use the helper from helper-price.php.
                        $display_price = '—';
                        if ( function_exists( 'moga_get_property_display_price' ) ) {
                            $price_data = moga_get_property_display_price( $post_id );
                            if ( $price_data && isset( $price_data['price'] ) && $price_data['price'] > 0 ) {
                                $display_price = $currency_symbol . number_format_i18n( (float) $price_data['price'], 0 )
                                    . ' <span class="moga-db-table__price-unit">/' . esc_html__( 'night', 'moga-travel' ) . '</span>';
                            }
                        }

                        // Thumbnail.
                        $thumb_id  = get_post_thumbnail_id( $post_id );
                        $thumb_url = $thumb_id
                            ? wp_get_attachment_image_url( $thumb_id, array( 48, 48 ) )
                            : '';
                    ?>
                    <tr class="moga-db-table__row" data-property-id="<?php echo esc_attr( $post_id ); ?>">

                        <td class="moga-db-table__td moga-db-table__td--title">
                            <div class="moga-db-table__listing">
                                <?php if ( $thumb_url ) : ?>
                                    <img class="moga-db-table__listing-thumb"
                                         src="<?php echo esc_url( $thumb_url ); ?>"
                                         alt="" aria-hidden="true" loading="lazy"
                                         width="48" height="48">
                                <?php else : ?>
                                    <div class="moga-db-table__listing-thumb moga-db-table__listing-thumb--placeholder" aria-hidden="true">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                             fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                             stroke-width="1.5" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                        </svg>
                                    </div>
                                <?php endif; ?>
                                <div class="moga-db-table__listing-info">
                                    <span class="moga-db-table__listing-title">
                                        <?php echo esc_html( $title ); ?>
                                    </span>
                                    <span class="moga-db-table__listing-id">
                                        #<?php echo esc_html( $post_id ); ?>
                                    </span>
                                </div>
                            </div>
                        </td>

                        <td class="moga-db-table__td">
                            <span class="moga-db-table__owner"><?php echo esc_html( $owner_name ); ?></span>
                        </td>

                        <td class="moga-db-table__td">
                            <span class="moga-db-table__type-badge moga-db-table__type-badge--property">
                                <?php echo esc_html( $type_label ); ?>
                            </span>
                        </td>

                        <td class="moga-db-table__td">
                            <span class="moga-db-table__location"><?php echo esc_html( $loc_label ); ?></span>
                        </td>

                        <td class="moga-db-table__td moga-db-table__td--price">
                            <?php echo wp_kses( $display_price, array( 'span' => array( 'class' => array() ) ) ); ?>
                        </td>

                        <td class="moga-db-table__td">
                            <span class="moga-db-table__status-badge moga-db-table__status-badge--<?php echo esc_attr( $status_ui['class'] ); ?>">
                                <?php echo esc_html( $status_ui['label'] ); ?>
                            </span>
                        </td>

                        <td class="moga-db-table__td moga-db-table__td--created">
                            <?php echo esc_html( $created ); ?>
                        </td>

                        <td class="moga-db-table__td moga-db-table__td--actions">
                            <div class="moga-db-table__actions">

                                <?php // View — only when published. ?>
                                <?php if ( 'publish' === $property->post_status && $view_link ) : ?>
                                    <a href="<?php echo esc_url( $view_link ); ?>"
                                       class="moga-db-table__action moga-db-table__action--view"
                                       title="<?php esc_attr_e( 'View Property', 'moga-travel' ); ?>"
                                       aria-label="<?php esc_attr_e( 'View property', 'moga-travel' ); ?>"
                                       target="_blank" rel="noopener noreferrer">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15"
                                             fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                             stroke-width="2" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>
                                <?php endif; ?>

                                <?php // Edit — always. ?>
                                <?php if ( $edit_link ) : ?>
                                    <a href="<?php echo esc_url( $edit_link ); ?>"
                                       class="moga-db-table__action moga-db-table__action--edit"
                                       title="<?php esc_attr_e( 'Edit Property', 'moga-travel' ); ?>"
                                       aria-label="<?php esc_attr_e( 'Edit property', 'moga-travel' ); ?>"
                                       target="_blank" rel="noopener noreferrer">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15"
                                             fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                             stroke-width="2" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                <?php endif; ?>

                                <?php // Trash — only if not already trashed. ?>
                                <?php if ( $trash_link && 'trash' !== $property->post_status ) : ?>
                                    <a href="<?php echo esc_url( $trash_link ); ?>"
                                       class="moga-db-table__action moga-db-table__action--cancel"
                                       title="<?php esc_attr_e( 'Move to Trash', 'moga-travel' ); ?>"
                                       aria-label="<?php esc_attr_e( 'Move property to trash', 'moga-travel' ); ?>"
                                       onclick="return confirm('<?php esc_attr_e( 'Move this property to trash?', 'moga-travel' ); ?>')">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15"
                                             fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                             stroke-width="2" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </a>
                                <?php endif; ?>

                            </div>
                        </td>

                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php // ── Pagination ────────────────────────────────────────────────────── ?>
    <?php if ( $total_pages > 1 ) : ?>
        <nav class="moga-db-pagination"
             aria-label="<?php esc_attr_e( 'Properties pagination', 'moga-travel' ); ?>">

            <?php
            $pagination_args = array_filter( array(
                'tab'          => 'all-properties',
                'pp_status'    => $filter_status,
                'pp_type'      => $filter_type > 0 ? $filter_type : '',
                'pp_search'    => $filter_search,
                'pp_owner'     => $filter_owner > 0 ? $filter_owner : '',
                'pp_order'     => $orderby !== 'post_date' ? $orderby : '',
                'pp_dir'       => $order !== 'DESC' ? $order : '',
                'pp_per_page'  => $per_page !== 20 ? $per_page : '',
            ) );
            $page_base = add_query_arg( $pagination_args, $dashboard_url );

            if ( $paged > 1 ) : ?>
                <a href="<?php echo esc_url( add_query_arg( 'pp_paged', $paged - 1, $page_base ) ); ?>"
                   class="moga-db-pagination__btn"
                   aria-label="<?php esc_attr_e( 'Previous page', 'moga-travel' ); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor"
                         stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
            <?php endif;

            $start = max( 1, $paged - 3 );
            $end   = min( $total_pages, $paged + 3 );

            if ( $start > 1 ) {
                echo '<a href="' . esc_url( add_query_arg( 'pp_paged', 1, $page_base ) ) . '" class="moga-db-pagination__btn">1</a>';
                if ( $start > 2 ) echo '<span class="moga-db-pagination__ellipsis" aria-hidden="true">…</span>';
            }

            for ( $i = $start; $i <= $end; $i++ ) :
                $is_current = ( $i === $paged );
                printf(
                    '<a href="%s" class="moga-db-pagination__btn%s"%s>%d</a>',
                    esc_url( add_query_arg( 'pp_paged', $i, $page_base ) ),
                    $is_current ? ' is-active' : '',
                    $is_current ? ' aria-current="page"' : '',
                    $i
                );
            endfor;

            if ( $end < $total_pages ) {
                if ( $end < $total_pages - 1 ) echo '<span class="moga-db-pagination__ellipsis" aria-hidden="true">…</span>';
                echo '<a href="' . esc_url( add_query_arg( 'pp_paged', $total_pages, $page_base ) ) . '" class="moga-db-pagination__btn">' . esc_html( $total_pages ) . '</a>';
            }
            ?>

            <?php if ( $paged < $total_pages ) : ?>
                <a href="<?php echo esc_url( add_query_arg( 'pp_paged', $paged + 1, $page_base ) ); ?>"
                   class="moga-db-pagination__btn"
                   aria-label="<?php esc_attr_e( 'Next page', 'moga-travel' ); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor"
                         stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            <?php endif; ?>

            <span class="moga-db-pagination__info">
                <?php
                printf(
                    esc_html__( 'Page %1$s of %2$s', 'moga-travel' ),
                    '<strong>' . esc_html( $paged ) . '</strong>',
                    '<strong>' . esc_html( $total_pages ) . '</strong>'
                );
                ?>
            </span>

        </nav>
    <?php endif; ?>

</div><?php // .moga-db-all-properties ?>
