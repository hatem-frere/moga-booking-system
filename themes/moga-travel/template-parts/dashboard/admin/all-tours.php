<?php

/**
 * Admin Dashboard — All Tours Tab
 *
 * Platform-wide tour management table for the administrator.
 * Shows every tour across all organizers with stat cards,
 * filters, sortable columns, per-page control, and quick-action links.
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
$current_url   = add_query_arg( 'tab', 'all-tours', $dashboard_url );

// ── Sanitize filter inputs ────────────────────────────────────────────────────

$filter_status  = isset( $_GET['tr_status'] )   ? sanitize_key( $_GET['tr_status'] )              : '';
$filter_search  = isset( $_GET['tr_search'] )   ? sanitize_text_field( wp_unslash( $_GET['tr_search'] ) ) : '';
$filter_owner   = isset( $_GET['tr_owner'] )    ? absint( $_GET['tr_owner'] )                      : 0;
$orderby        = isset( $_GET['tr_order'] )    ? sanitize_key( $_GET['tr_order'] )                : 'post_date';
$order          = isset( $_GET['tr_dir'] ) && strtoupper( $_GET['tr_dir'] ) === 'ASC' ? 'ASC' : 'DESC';
$paged          = isset( $_GET['tr_paged'] )    ? max( 1, (int) $_GET['tr_paged'] )               : 1;
$per_page       = isset( $_GET['tr_per_page'] ) ? (int) $_GET['tr_per_page']                       : 20;
$per_page       = in_array( $per_page, array( 10, 20, 25, 50, 100 ), true ) ? $per_page : 20;
$offset         = ( $paged - 1 ) * $per_page;

// Whitelist orderby columns.
$allowed_orderby = array( 'post_title', 'post_date', 'post_status', 'post_author', 'post_modified' );
if ( ! in_array( $orderby, $allowed_orderby, true ) ) {
    $orderby = 'post_date';
}

// ── Stat cards — counts across ALL tours (no filters) ────────────────────────

$stat_total     = (int) wp_count_posts( 'moga_tour' )->publish
                + (int) wp_count_posts( 'moga_tour' )->pending
                + (int) wp_count_posts( 'moga_tour' )->draft
                + (int) wp_count_posts( 'moga_tour' )->private;
$stat_published = (int) wp_count_posts( 'moga_tour' )->publish;
$stat_pending   = (int) wp_count_posts( 'moga_tour' )->pending;
$stat_draft     = (int) wp_count_posts( 'moga_tour' )->draft;

$total_tour_bookings = (int) $wpdb->get_var(
    "SELECT COUNT(*) FROM {$wpdb->prefix}moga_bookings WHERE booking_type = 'tour'"
);

// ── Tour category terms for filter dropdown ───────────────────────────────────

$tour_cats = get_terms( array(
    'taxonomy'   => 'moga_tour_category',
    'hide_empty' => false,
    'orderby'    => 'name',
    'order'      => 'ASC',
) );
if ( is_wp_error( $tour_cats ) ) {
    $tour_cats = array();
}

// ── Build WP_Query args ───────────────────────────────────────────────────────

$query_args = array(
    'post_type'      => 'moga_tour',
    'post_status'    => array( 'publish', 'pending', 'draft', 'private' ),
    'posts_per_page' => $per_page,
    'offset'         => $offset,
    'orderby'        => $orderby,
    'order'          => $order,
);

if ( $filter_status && in_array( $filter_status, array( 'publish', 'pending', 'draft', 'private', 'trash' ), true ) ) {
    $query_args['post_status'] = $filter_status;
}

if ( $filter_search !== '' ) {
    $query_args['s'] = $filter_search;
}

if ( $filter_owner > 0 ) {
    $query_args['author'] = $filter_owner;
}

// Count query for pagination.
$count_args                   = $query_args;
$count_args['fields']         = 'ids';
$count_args['offset']         = 0;
$count_args['posts_per_page'] = -1;
$count_query                  = new WP_Query( $count_args );
$total_rows                   = (int) $count_query->found_posts;
$total_pages                  = max( 1, (int) ceil( $total_rows / $per_page ) );
if ( $paged > $total_pages ) {
    $paged = $total_pages;
}
wp_reset_postdata();

// Main query.
$tours_query = new WP_Query( $query_args );
$tours       = $tours_query->posts;
wp_reset_postdata();

// ── Helpers ───────────────────────────────────────────────────────────────────

function moga_tour_status_ui( $status ) {
    $map = array(
        'publish'  => array( 'class' => 'success', 'label' => __( 'Published', 'moga-travel' ) ),
        'pending'  => array( 'class' => 'warning', 'label' => __( 'Pending',   'moga-travel' ) ),
        'draft'    => array( 'class' => 'neutral', 'label' => __( 'Draft',     'moga-travel' ) ),
        'private'  => array( 'class' => 'info',    'label' => __( 'Private',   'moga-travel' ) ),
        'trash'    => array( 'class' => 'danger',  'label' => __( 'Trash',     'moga-travel' ) ),
    );
    return $map[ $status ] ?? array( 'class' => 'neutral', 'label' => ucfirst( $status ) );
}

function moga_tour_sort_url( $col, $current_orderby, $current_order, $base_url ) {
    $dir = ( $current_orderby === $col && $current_order === 'DESC' ) ? 'ASC' : 'DESC';
    return add_query_arg( array( 'tr_order' => $col, 'tr_dir' => $dir ), $base_url );
}

function moga_tour_sort_indicator( $col, $current_orderby, $current_order ) {
    if ( $current_orderby !== $col ) {
        return '<span class="moga-db-table__sort-icon moga-db-table__sort-icon--neutral" aria-hidden="true">↕</span>';
    }
    return $current_order === 'ASC'
        ? '<span class="moga-db-table__sort-icon moga-db-table__sort-icon--asc" aria-hidden="true">↑</span>'
        : '<span class="moga-db-table__sort-icon moga-db-table__sort-icon--desc" aria-hidden="true">↓</span>';
}

// Active filter count — excludes chip-triggered params.
$tr_via_chip = ! empty( $_GET['tr_chip'] );

$active_filter_count = $tr_via_chip ? 0 :
    (int) ( $filter_status !== '' )
  + (int) ( $filter_owner > 0 )
  + (int) ( $filter_search !== '' );

// Organizers list for filter dropdown.
$organizers = $wpdb->get_results(
    "SELECT DISTINCT u.ID, u.display_name
     FROM {$wpdb->posts} p
     JOIN {$wpdb->users} u ON p.post_author = u.ID
     WHERE p.post_type = 'moga_tour' AND p.post_status != 'trash'
     ORDER BY u.display_name ASC"
);
?>

<div class="moga-db-all-tours">

    <?php // ── Stat Cards ── ?>
    <div class="moga-db-stats moga-db-stats--4col">

        <div class="moga-db-stats__card">
            <div class="moga-db-stats__value"><?php echo esc_html( number_format_i18n( $stat_total ) ); ?></div>
            <div class="moga-db-stats__label"><?php esc_html_e( 'Total Tours', 'moga-travel' ); ?></div>
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
            <div class="moga-db-stats__value"><?php echo esc_html( number_format_i18n( $total_tour_bookings ) ); ?></div>
            <div class="moga-db-stats__label"><?php esc_html_e( 'Total Bookings', 'moga-travel' ); ?></div>
        </div>

    </div>

    <?php // ── Toolbar ── ?>
    <div class="moga-db-toolbar">
        <form method="get" action="<?php echo esc_url( $current_url ); ?>"
              class="moga-db-toolbar__form" id="moga-tr-filter-form">

            <input type="hidden" name="tab" value="all-tours">
            <?php if ( $orderby !== 'post_date' ) : ?><input type="hidden" name="tr_order" value="<?php echo esc_attr( $orderby ); ?>"><?php endif; ?>
            <?php if ( $order !== 'DESC' ) : ?><input type="hidden" name="tr_dir" value="<?php echo esc_attr( $order ); ?>"><?php endif; ?>

            <div class="moga-db-toolbar__top">

                <div class="moga-db-toolbar__search">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
                    </svg>
                    <input type="text" name="tr_search"
                           value="<?php echo esc_attr( $filter_search ); ?>"
                           placeholder="<?php esc_attr_e( 'Search by tour title or organizer…', 'moga-travel' ); ?>"
                           class="moga-db-toolbar__search-input" autocomplete="off">
                    <span class="moga-db-toolbar__kbd">⌘K</span>
                </div>

                <div class="moga-db-toolbar__actions">

                    <button type="button"
                            class="moga-db-toolbar__filter-toggle"
                            id="moga-tr-filter-toggle"
                            aria-expanded="<?php echo $active_filter_count > 0 ? 'true' : 'false'; ?>"
                            aria-controls="moga-tr-filters">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none"
                             viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18M7 10h10M10 16h4"/>
                        </svg>
                        <?php esc_html_e( 'Filters', 'moga-travel' ); ?>
                        <?php if ( $active_filter_count > 0 ) : ?>
                            <span class="moga-db-toolbar__filter-badge"><?php echo esc_html( $active_filter_count ); ?></span>
                        <?php endif; ?>
                    </button>

                    <select name="tr_per_page" class="moga-db-toolbar__per-page-select"
                            onchange="this.form.submit()">
                        <?php foreach ( array( 10, 20, 25, 50, 100 ) as $n ) : ?>
                            <option value="<?php echo esc_attr( $n ); ?>" <?php selected( $per_page, $n ); ?>>
                                <?php echo esc_html( $n ); ?> / <?php esc_html_e( 'page', 'moga-travel' ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=moga_tour' ) ); ?>"
                       class="moga-db-toolbar__cta" target="_blank">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none"
                             viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                        <?php esc_html_e( 'New Tour', 'moga-travel' ); ?>
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
                $tr_chips = array(
                    array( 'label' => __( 'All Tours',      'moga-travel' ), 'dot' => '',        'active' => ( $filter_status === '' && $filter_search === '' && $filter_owner === 0 ), 'url' => add_query_arg( array( 'tab' => 'all-tours', 'tr_chip' => '1' ), $dashboard_url ) ),
                    array( 'label' => __( 'Published',      'moga-travel' ), 'dot' => '#10b981', 'active' => $filter_status === 'publish', 'url' => add_query_arg( array( 'tab' => 'all-tours', 'tr_status' => 'publish', 'tr_chip' => '1' ), $dashboard_url ) ),
                    array( 'label' => __( 'Pending Review', 'moga-travel' ), 'dot' => '#f59e0b', 'active' => $filter_status === 'pending', 'url' => add_query_arg( array( 'tab' => 'all-tours', 'tr_status' => 'pending', 'tr_chip' => '1' ), $dashboard_url ) ),
                    array( 'label' => __( 'Drafts',         'moga-travel' ), 'dot' => '#9ca3af', 'active' => $filter_status === 'draft',   'url' => add_query_arg( array( 'tab' => 'all-tours', 'tr_status' => 'draft',   'tr_chip' => '1' ), $dashboard_url ) ),
                );
                foreach ( $tr_chips as $chip ) : ?>
                    <a href="<?php echo esc_url( $chip['url'] ); ?>"
                       class="moga-db-toolbar__chip<?php echo $chip['active'] ? ' is-active' : ''; ?>">
                        <?php if ( $chip['dot'] ) : ?>
                            <span class="moga-db-toolbar__chip-dot" style="background:<?php echo esc_attr( $chip['dot'] ); ?>;"></span>
                        <?php endif; ?>
                        <?php echo esc_html( $chip['label'] ); ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <?php // ── Filter panel — all fields in ONE row ── ?>
            <div class="moga-db-toolbar__filters<?php echo $active_filter_count > 0 ? ' is-open' : ''; ?>"
                 id="moga-tr-filters">

                <div class="moga-db-toolbar__filters-row">

                    <div class="moga-db-toolbar__filter-group">
                        <label for="moga-tr-status"><?php esc_html_e( 'Status', 'moga-travel' ); ?></label>
                        <select name="tr_status" id="moga-tr-status" class="moga-db-toolbar__select">
                            <option value=""><?php esc_html_e( 'All Statuses', 'moga-travel' ); ?></option>
                            <?php foreach ( array(
                                'publish' => __( 'Published',      'moga-travel' ),
                                'pending' => __( 'Pending Review', 'moga-travel' ),
                                'draft'   => __( 'Draft',          'moga-travel' ),
                                'private' => __( 'Private',        'moga-travel' ),
                                'trash'   => __( 'Trash',          'moga-travel' ),
                            ) as $val => $label ) :
                                printf( '<option value="%s"%s>%s</option>', esc_attr( $val ), selected( $filter_status, $val, false ), esc_html( $label ) );
                            endforeach; ?>
                        </select>
                    </div>

                    <div class="moga-db-toolbar__filter-group">
                        <label for="moga-tr-owner"><?php esc_html_e( 'Organizer', 'moga-travel' ); ?></label>
                        <select name="tr_owner" id="moga-tr-owner" class="moga-db-toolbar__select">
                            <option value="0"><?php esc_html_e( 'All Organizers', 'moga-travel' ); ?></option>
                            <?php foreach ( $organizers as $org ) : ?>
                                <option value="<?php echo esc_attr( $org->ID ); ?>" <?php selected( $filter_owner, (int) $org->ID ); ?>>
                                    <?php echo esc_html( $org->display_name ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="moga-db-toolbar__filter-group">
                        <label><?php esc_html_e( 'Date Added', 'moga-travel' ); ?></label>
                        <div class="moga-db-toolbar__date-range">
                            <input type="date" name="tr_from" id="moga-tr-from"
                                   value="<?php echo esc_attr( isset( $_GET['tr_from'] ) ? sanitize_text_field( $_GET['tr_from'] ) : '' ); ?>"
                                   class="moga-db-toolbar__input">
                            <span class="moga-db-toolbar__date-sep">–</span>
                            <input type="date" name="tr_to" id="moga-tr-to"
                                   value="<?php echo esc_attr( isset( $_GET['tr_to'] ) ? sanitize_text_field( $_GET['tr_to'] ) : '' ); ?>"
                                   class="moga-db-toolbar__input">
                        </div>
                    </div>

                </div>

                <div class="moga-db-toolbar__filters-actions">
                    <a href="<?php echo esc_url( add_query_arg( 'tab', 'all-tours', $dashboard_url ) ); ?>"
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

    <?php // ── Results summary ── ?>
    <div class="moga-db-table-meta">
        <p class="moga-db-table-meta__count">
            <?php printf(
                esc_html( _n( 'Showing %s tour found', 'Showing %s tours found', $total_rows, 'moga-travel' ) ),
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

    <?php // ── Tours Table ── ?>
    <div class="moga-db-table-wrap">
        <table class="moga-db-table" id="moga-tr-table">
            <thead>
                <tr>
                    <th class="moga-db-table__th moga-db-table__th--sortable">
                        <a href="<?php echo esc_url( moga_tour_sort_url( 'post_title', $orderby, $order, $current_url ) ); ?>">
                            <?php esc_html_e( 'Tour', 'moga-travel' ); ?>
                            <?php echo moga_tour_sort_indicator( 'post_title', $orderby, $order ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </a>
                    </th>
                    <th class="moga-db-table__th moga-db-table__th--sortable">
                        <a href="<?php echo esc_url( moga_tour_sort_url( 'post_author', $orderby, $order, $current_url ) ); ?>">
                            <?php esc_html_e( 'Organizer', 'moga-travel' ); ?>
                            <?php echo moga_tour_sort_indicator( 'post_author', $orderby, $order ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </a>
                    </th>
                    <th class="moga-db-table__th"><?php esc_html_e( 'Bookings', 'moga-travel' ); ?></th>
                    <th class="moga-db-table__th moga-db-table__th--sortable">
                        <a href="<?php echo esc_url( moga_tour_sort_url( 'post_status', $orderby, $order, $current_url ) ); ?>">
                            <?php esc_html_e( 'Status', 'moga-travel' ); ?>
                            <?php echo moga_tour_sort_indicator( 'post_status', $orderby, $order ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </a>
                    </th>
                    <th class="moga-db-table__th moga-db-table__th--sortable">
                        <a href="<?php echo esc_url( moga_tour_sort_url( 'post_date', $orderby, $order, $current_url ) ); ?>">
                            <?php esc_html_e( 'Created', 'moga-travel' ); ?>
                            <?php echo moga_tour_sort_indicator( 'post_date', $orderby, $order ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </a>
                    </th>
                    <th class="moga-db-table__th moga-db-table__th--sortable">
                        <a href="<?php echo esc_url( moga_tour_sort_url( 'post_modified', $orderby, $order, $current_url ) ); ?>">
                            <?php esc_html_e( 'Modified', 'moga-travel' ); ?>
                            <?php echo moga_tour_sort_indicator( 'post_modified', $orderby, $order ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </a>
                    </th>
                    <th class="moga-db-table__th moga-db-table__th--actions">
                        <?php esc_html_e( 'Actions', 'moga-travel' ); ?>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $tours ) ) : ?>
                    <tr>
                        <td colspan="7" class="moga-db-table__empty">
                            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                 stroke-width="1.2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                            </svg>
                            <p><?php esc_html_e( 'No tours found.', 'moga-travel' ); ?></p>
                            <?php if ( $active_filter_count > 0 ) : ?>
                                <a href="<?php echo esc_url( add_query_arg( 'tab', 'all-tours', $dashboard_url ) ); ?>"
                                   class="moga-btn moga-btn--ghost moga-btn--sm">
                                    <?php esc_html_e( 'Clear filters', 'moga-travel' ); ?>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else : ?>
                    <?php foreach ( $tours as $tour ) :

                        $post_id    = $tour->ID;
                        $title      = $tour->post_title ?: __( '(no title)', 'moga-travel' );
                        $status_ui  = moga_tour_status_ui( $tour->post_status );
                        $edit_link  = get_edit_post_link( $post_id );
                        $view_link  = get_permalink( $post_id );
                        $trash_link = get_delete_post_link( $post_id );
                        $created    = date_i18n( get_option( 'date_format' ), strtotime( $tour->post_date ) );
                        $modified   = date_i18n( get_option( 'date_format' ), strtotime( $tour->post_modified ) );

                        // Organizer.
                        $organizer      = get_userdata( $tour->post_author );
                        $organizer_name = $organizer ? $organizer->display_name : __( '(unknown)', 'moga-travel' );

                        // Booking count.
                        $booking_count = (int) $wpdb->get_var( $wpdb->prepare(
                            "SELECT COUNT(*) FROM {$wpdb->prefix}moga_bookings WHERE listing_id = %d AND booking_type = 'tour'",
                            $post_id
                        ) );

                        // Thumbnail.
                        $thumb_id  = get_post_thumbnail_id( $post_id );
                        $thumb_url = $thumb_id
                            ? wp_get_attachment_image_url( $thumb_id, array( 48, 48 ) )
                            : '';
                    ?>
                    <tr class="moga-db-table__row" data-tour-id="<?php echo esc_attr( $post_id ); ?>">

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
                                                  d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
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
                            <span class="moga-db-table__owner"><?php echo esc_html( $organizer_name ); ?></span>
                        </td>

                        <td class="moga-db-table__td">
                            <?php if ( $booking_count > 0 ) : ?>
                                <a href="<?php echo esc_url( add_query_arg( array( 'tab' => 'all-bookings', 'bk_type' => 'tour' ), $dashboard_url ) ); ?>"
                                   class="moga-db-table__status-badge moga-db-table__status-badge--success">
                                    <?php echo esc_html( number_format_i18n( $booking_count ) ); ?>
                                </a>
                            <?php else : ?>
                                <span class="moga-db-table__status-badge moga-db-table__status-badge--neutral">0</span>
                            <?php endif; ?>
                        </td>

                        <td class="moga-db-table__td">
                            <span class="moga-db-table__status-badge moga-db-table__status-badge--<?php echo esc_attr( $status_ui['class'] ); ?>">
                                <?php echo esc_html( $status_ui['label'] ); ?>
                            </span>
                        </td>

                        <td class="moga-db-table__td moga-db-table__td--created">
                            <?php echo esc_html( $created ); ?>
                        </td>

                        <td class="moga-db-table__td">
                            <?php echo esc_html( $modified ); ?>
                        </td>

                        <td class="moga-db-table__td moga-db-table__td--actions">
                            <div class="moga-db-table__actions">

                                <?php if ( 'publish' === $tour->post_status && $view_link ) : ?>
                                    <a href="<?php echo esc_url( $view_link ); ?>"
                                       class="moga-db-table__action moga-db-table__action--view"
                                       title="<?php esc_attr_e( 'View Tour', 'moga-travel' ); ?>"
                                       aria-label="<?php esc_attr_e( 'View tour', 'moga-travel' ); ?>"
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

                                <?php if ( $edit_link ) : ?>
                                    <a href="<?php echo esc_url( $edit_link ); ?>"
                                       class="moga-db-table__action moga-db-table__action--edit"
                                       title="<?php esc_attr_e( 'Edit Tour', 'moga-travel' ); ?>"
                                       aria-label="<?php esc_attr_e( 'Edit tour', 'moga-travel' ); ?>"
                                       target="_blank" rel="noopener noreferrer">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15"
                                             fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                             stroke-width="2" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                <?php endif; ?>

                                <?php if ( $trash_link && 'trash' !== $tour->post_status ) : ?>
                                    <a href="<?php echo esc_url( $trash_link ); ?>"
                                       class="moga-db-table__action moga-db-table__action--cancel"
                                       title="<?php esc_attr_e( 'Move to Trash', 'moga-travel' ); ?>"
                                       aria-label="<?php esc_attr_e( 'Move tour to trash', 'moga-travel' ); ?>"
                                       onclick="return confirm('<?php esc_attr_e( 'Move this tour to trash?', 'moga-travel' ); ?>')">
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

    <?php // ── Pagination ── ?>
    <?php if ( $total_pages > 1 ) : ?>
        <nav class="moga-db-pagination"
             aria-label="<?php esc_attr_e( 'Tours pagination', 'moga-travel' ); ?>">

            <?php
            $pagination_args = array_filter( array(
                'tab'          => 'all-tours',
                'tr_status'    => $filter_status,
                'tr_search'    => $filter_search,
                'tr_owner'     => $filter_owner > 0 ? $filter_owner : '',
                'tr_order'     => $orderby !== 'post_date' ? $orderby : '',
                'tr_dir'       => $order !== 'DESC' ? $order : '',
                'tr_per_page'  => $per_page !== 20 ? $per_page : '',
            ) );
            $page_base = add_query_arg( $pagination_args, $dashboard_url );

            if ( $paged > 1 ) : ?>
                <a href="<?php echo esc_url( add_query_arg( 'tr_paged', $paged - 1, $page_base ) ); ?>"
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
                echo '<a href="' . esc_url( add_query_arg( 'tr_paged', 1, $page_base ) ) . '" class="moga-db-pagination__btn">1</a>';
                if ( $start > 2 ) { echo '<span class="moga-db-pagination__ellipsis" aria-hidden="true">…</span>'; }
            }

            for ( $i = $start; $i <= $end; $i++ ) :
                $is_current = ( $i === $paged );
                printf(
                    '<a href="%s" class="moga-db-pagination__btn%s"%s>%d</a>',
                    esc_url( add_query_arg( 'tr_paged', $i, $page_base ) ),
                    $is_current ? ' is-active' : '',
                    $is_current ? ' aria-current="page"' : '',
                    $i
                );
            endfor;

            if ( $end < $total_pages ) {
                if ( $end < $total_pages - 1 ) { echo '<span class="moga-db-pagination__ellipsis" aria-hidden="true">…</span>'; }
                echo '<a href="' . esc_url( add_query_arg( 'tr_paged', $total_pages, $page_base ) ) . '" class="moga-db-pagination__btn">' . esc_html( $total_pages ) . '</a>';
            }
            ?>

            <?php if ( $paged < $total_pages ) : ?>
                <a href="<?php echo esc_url( add_query_arg( 'tr_paged', $paged + 1, $page_base ) ); ?>"
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

</div><?php // .moga-db-all-tours ?>
