<?php

/**
 * Admin Dashboard — All Bookings Tab
 *
 * Platform-wide bookings management table for the administrator.
 * Shows every booking across all vendors with filters, search,
 * stat cards, and quick-action links.
 *
 * Filters are URL-based (no AJAX on first load) — consistent with
 * the rest of the dashboard. dashboard.js handles tab switching.
 *
 * @package MogaTravel
 * @since   1.0.0
 *
 * @var WP_User $args['user']     Current user object.
 * @var int     $args['user_id']  Current user ID.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;

$dashboard_url = get_permalink( get_option( 'moga_page_dashboard' ) );
$current_url   = add_query_arg( 'tab', 'all-bookings', $dashboard_url );

// ── Sanitize filter inputs ────────────────────────────────────────────────────

$filter_status  = isset( $_GET['bk_status'] )   ? sanitize_key( $_GET['bk_status'] )  : '';
$filter_type    = isset( $_GET['bk_type'] )     ? sanitize_key( $_GET['bk_type'] )    : '';
$filter_search  = isset( $_GET['bk_search'] )   ? sanitize_text_field( wp_unslash( $_GET['bk_search'] ) ) : '';
$filter_date_from = isset( $_GET['bk_from'] )   ? sanitize_text_field( $_GET['bk_from'] ) : '';
$filter_date_to   = isset( $_GET['bk_to'] )     ? sanitize_text_field( $_GET['bk_to'] )   : '';
$orderby        = isset( $_GET['bk_order'] )    ? sanitize_key( $_GET['bk_order'] )   : 'created_at';
$order          = isset( $_GET['bk_dir'] ) && strtoupper( $_GET['bk_dir'] ) === 'ASC' ? 'ASC' : 'DESC';
$paged          = isset( $_GET['bk_paged'] )    ? max( 1, (int) $_GET['bk_paged'] )   : 1;
$per_page       = isset( $_GET['bk_per_page'] ) ? (int) $_GET['bk_per_page']           : 20;
$per_page       = in_array( $per_page, array( 10, 20, 25, 50, 100 ), true ) ? $per_page : 20;
$offset         = ( $paged - 1 ) * $per_page;

// Allowed orderby columns — whitelist to prevent SQL injection.
$allowed_orderby = array( 'created_at', 'check_in', 'total_amount', 'status', 'booking_number' );
if ( ! in_array( $orderby, $allowed_orderby, true ) ) {
    $orderby = 'created_at';
}

// ── Build WHERE clause ────────────────────────────────────────────────────────

$where  = array( '1=1' );
$params = array();

if ( $filter_status && in_array( $filter_status, array( 'pending', 'confirmed', 'cancelled', 'completed', 'refunded', 'no_show' ), true ) ) {
    $where[]  = 'b.status = %s';
    $params[] = $filter_status;
}

if ( $filter_type && in_array( $filter_type, array( 'property', 'tour', 'bus', 'rental' ), true ) ) {
    $where[]  = 'b.booking_type = %s';
    $params[] = $filter_type;
}

if ( $filter_date_from && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $filter_date_from ) ) {
    $where[]  = 'b.check_in >= %s';
    $params[] = $filter_date_from;
}

if ( $filter_date_to && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $filter_date_to ) ) {
    $where[]  = 'b.check_out <= %s';
    $params[] = $filter_date_to;
}

if ( $filter_search !== '' ) {
    $where[]  = '( b.booking_number LIKE %s OR u.display_name LIKE %s OR p.post_title LIKE %s )';
    $like     = '%' . $wpdb->esc_like( $filter_search ) . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$where_sql = implode( ' AND ', $where );

// ── Stat cards — always counts all bookings (no filters applied) ──────────────

$stats = $wpdb->get_row(
    "SELECT
        COUNT(*)                                          AS total,
        SUM( status = 'pending' )                        AS pending,
        SUM( status = 'confirmed' )                      AS confirmed,
        SUM( status = 'cancelled' )                      AS cancelled,
        SUM( status = 'completed' )                      AS completed,
        SUM( DATE( created_at ) = CURDATE() )            AS today,
        COALESCE( SUM( total_amount ), 0 )               AS revenue
     FROM {$wpdb->prefix}moga_bookings"
);

// ── Count filtered results (for pagination) ───────────────────────────────────

$count_sql = "
    SELECT COUNT(*)
    FROM {$wpdb->prefix}moga_bookings b
    LEFT JOIN {$wpdb->users} u  ON b.guest_id   = u.ID
    LEFT JOIN {$wpdb->posts} p  ON b.listing_id  = p.ID
    WHERE {$where_sql}
";

$total_rows = (int) ( empty( $params )
    ? $wpdb->get_var( $count_sql )
    : $wpdb->get_var( $wpdb->prepare( $count_sql, $params ) ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
);

$total_pages = max( 1, (int) ceil( $total_rows / $per_page ) );
if ( $paged > $total_pages ) {
    $paged = $total_pages;
}

// ── Fetch bookings ────────────────────────────────────────────────────────────

$bookings_sql = "
    SELECT
        b.id,
        b.booking_number,
        b.booking_type,
        b.listing_id,
        b.guest_id,
        b.owner_id,
        b.check_in,
        b.check_out,
        b.total_nights,
        b.guests_adults,
        b.guests_children,
        b.guests_infants,
        b.total_amount,
        b.deposit_amount,
        b.balance_due,
        b.currency,
        b.status,
        b.payment_status,
        b.created_at,
        u.display_name  AS guest_name,
        u.user_email    AS guest_email,
        p.post_title    AS listing_title,
        o.display_name  AS owner_name
    FROM {$wpdb->prefix}moga_bookings b
    LEFT JOIN {$wpdb->users} u  ON b.guest_id  = u.ID
    LEFT JOIN {$wpdb->posts} p  ON b.listing_id = p.ID
    LEFT JOIN {$wpdb->users} o  ON b.owner_id   = o.ID
    WHERE {$where_sql}
    ORDER BY b.{$orderby} {$order}
    LIMIT %d OFFSET %d
";

$params_with_limit   = array_merge( $params, array( $per_page, $offset ) );
$bookings            = $wpdb->get_results(
    $wpdb->prepare( $bookings_sql, $params_with_limit ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
);

// ── Helpers ───────────────────────────────────────────────────────────────────

/**
 * Return a CSS modifier class and human label for a booking status.
 *
 * @param string $status
 * @return array { class: string, label: string }
 */
function moga_booking_status_ui( $status ) {
    $map = array(
        'pending'   => array( 'class' => 'warning',  'label' => __( 'Pending',   'moga-travel' ) ),
        'confirmed' => array( 'class' => 'success',  'label' => __( 'Confirmed', 'moga-travel' ) ),
        'cancelled' => array( 'class' => 'danger',   'label' => __( 'Cancelled', 'moga-travel' ) ),
        'completed' => array( 'class' => 'info',     'label' => __( 'Completed', 'moga-travel' ) ),
        'refunded'  => array( 'class' => 'neutral',  'label' => __( 'Refunded',  'moga-travel' ) ),
        'no_show'   => array( 'class' => 'neutral',  'label' => __( 'No Show',   'moga-travel' ) ),
    );
    return $map[ $status ] ?? array( 'class' => 'neutral', 'label' => ucfirst( $status ) );
}

/**
 * Return a CSS modifier and label for payment status.
 *
 * @param string $status
 * @return array { class: string, label: string }
 */
function moga_payment_status_ui( $status ) {
    $map = array(
        'unpaid'         => array( 'class' => 'danger',  'label' => __( 'Unpaid',         'moga-travel' ) ),
        'paid'           => array( 'class' => 'success', 'label' => __( 'Paid',           'moga-travel' ) ),
        'partially_paid' => array( 'class' => 'warning', 'label' => __( 'Partial',        'moga-travel' ) ),
        'refunded'       => array( 'class' => 'neutral', 'label' => __( 'Refunded',       'moga-travel' ) ),
    );
    return $map[ $status ] ?? array( 'class' => 'neutral', 'label' => ucfirst( $status ) );
}

/**
 * Return a human-readable booking type label.
 *
 * @param string $type
 * @return string
 */
function moga_booking_type_label( $type ) {
    $map = array(
        'property' => __( 'Property', 'moga-travel' ),
        'tour'     => __( 'Tour',     'moga-travel' ),
        'bus'      => __( 'Bus',      'moga-travel' ),
        'rental'   => __( 'Rental',   'moga-travel' ),
    );
    return $map[ $type ] ?? ucfirst( $type );
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
function moga_sort_url( $col, $current_orderby, $current_order, $base_url ) {
    $dir = ( $current_orderby === $col && $current_order === 'DESC' ) ? 'ASC' : 'DESC';
    return add_query_arg( array( 'bk_order' => $col, 'bk_dir' => $dir ), $base_url );
}

/**
 * Return a sort indicator arrow for the column header.
 *
 * @param string $col
 * @param string $current_orderby
 * @param string $current_order
 * @return string
 */
function moga_sort_indicator( $col, $current_orderby, $current_order ) {
    if ( $current_orderby !== $col ) {
        return '<span class="moga-db-table__sort-icon moga-db-table__sort-icon--neutral" aria-hidden="true">↕</span>';
    }
    return $current_order === 'ASC'
        ? '<span class="moga-db-table__sort-icon moga-db-table__sort-icon--asc" aria-hidden="true">↑</span>'
        : '<span class="moga-db-table__sort-icon moga-db-table__sort-icon--desc" aria-hidden="true">↓</span>';
}

// Currency symbol.
$currency_symbol = get_option( 'moga_currency_symbol', '$' );

// Active filter count — only counts params set via the Filters panel,
// NOT via quick chips (chip=1 means it was set by a chip click).
$bk_via_chip = ! empty( $_GET['bk_chip'] );

$active_filter_count = $bk_via_chip ? 0 :
    (int) ( $filter_status !== '' )
  + (int) ( $filter_type !== '' )
  + (int) ( $filter_search !== '' )
  + (int) ( $filter_date_from !== '' )
  + (int) ( $filter_date_to !== '' );
?>

<div class="moga-db-all-bookings">

    <?php // ── Stat Cards ────────────────────────────────────────────────────── ?>
    <div class="moga-db-stats">

        <div class="moga-db-stats__card">
            <div class="moga-db-stats__value"><?php echo esc_html( number_format_i18n( (int) $stats->total ) ); ?></div>
            <div class="moga-db-stats__label"><?php esc_html_e( 'Total Bookings', 'moga-travel' ); ?></div>
        </div>

        <div class="moga-db-stats__card moga-db-stats__card--warning">
            <div class="moga-db-stats__value"><?php echo esc_html( number_format_i18n( (int) $stats->pending ) ); ?></div>
            <div class="moga-db-stats__label"><?php esc_html_e( 'Pending', 'moga-travel' ); ?></div>
        </div>

        <div class="moga-db-stats__card moga-db-stats__card--success">
            <div class="moga-db-stats__value"><?php echo esc_html( number_format_i18n( (int) $stats->confirmed ) ); ?></div>
            <div class="moga-db-stats__label"><?php esc_html_e( 'Confirmed', 'moga-travel' ); ?></div>
        </div>

        <div class="moga-db-stats__card moga-db-stats__card--info">
            <div class="moga-db-stats__value"><?php echo esc_html( number_format_i18n( (int) $stats->completed ) ); ?></div>
            <div class="moga-db-stats__label"><?php esc_html_e( 'Completed', 'moga-travel' ); ?></div>
        </div>

        <div class="moga-db-stats__card moga-db-stats__card--danger">
            <div class="moga-db-stats__value"><?php echo esc_html( number_format_i18n( (int) $stats->cancelled ) ); ?></div>
            <div class="moga-db-stats__label"><?php esc_html_e( 'Cancelled', 'moga-travel' ); ?></div>
        </div>

        <div class="moga-db-stats__card moga-db-stats__card--revenue">
            <div class="moga-db-stats__value">
                <?php echo esc_html( $currency_symbol . number_format_i18n( (float) $stats->revenue, 0 ) ); ?>
            </div>
            <div class="moga-db-stats__label"><?php esc_html_e( 'Total Revenue', 'moga-travel' ); ?></div>
        </div>

    </div>

    <?php // ── Toolbar ───────────────────────────────────────────────────────── ?>
    <div class="moga-db-toolbar">
        <form method="get" action="<?php echo esc_url( $current_url ); ?>"
              class="moga-db-toolbar__form" id="moga-bk-filter-form">

            <input type="hidden" name="tab" value="all-bookings">
            <?php if ( $orderby !== 'created_at' ) : ?><input type="hidden" name="bk_order" value="<?php echo esc_attr( $orderby ); ?>"><?php endif; ?>
            <?php if ( $order !== 'DESC' ) : ?><input type="hidden" name="bk_dir" value="<?php echo esc_attr( $order ); ?>"><?php endif; ?>

            <?php // ── Top row: search LEFT | actions RIGHT ── ?>
            <div class="moga-db-toolbar__top">

                <div class="moga-db-toolbar__search">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
                    </svg>
                    <input type="text" name="bk_search"
                           value="<?php echo esc_attr( $filter_search ); ?>"
                           placeholder="<?php esc_attr_e( 'Search by booking #, guest, or listing…', 'moga-travel' ); ?>"
                           class="moga-db-toolbar__search-input" autocomplete="off">
                    <span class="moga-db-toolbar__kbd">⌘K</span>
                </div>

                <div class="moga-db-toolbar__actions">

                    <?php // Filters button ?>
                    <button type="button"
                            class="moga-db-toolbar__filter-toggle"
                            id="moga-bk-filter-toggle"
                            aria-expanded="<?php echo $active_filter_count > 0 ? 'true' : 'false'; ?>"
                            aria-controls="moga-bk-filters">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none"
                             viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18M7 10h10M10 16h4"/>
                        </svg>
                        <?php esc_html_e( 'Filters', 'moga-travel' ); ?>
                        <?php if ( $active_filter_count > 0 ) : ?>
                            <span class="moga-db-toolbar__filter-badge"><?php echo esc_html( $active_filter_count ); ?></span>
                        <?php endif; ?>
                    </button>

                    <?php // Per-page select ?>
                    <select name="bk_per_page" class="moga-db-toolbar__per-page-select"
                            onchange="this.form.submit()">
                        <?php foreach ( array( 10, 20, 25, 50, 100 ) as $n ) : ?>
                            <option value="<?php echo esc_attr( $n ); ?>" <?php selected( $per_page, $n ); ?>>
                                <?php echo esc_html( $n ); ?> / <?php esc_html_e( 'page', 'moga-travel' ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <?php // New Booking CTA ?>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=moga-bookings' ) ); ?>"
                       class="moga-db-toolbar__cta" target="_blank">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none"
                             viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                        <?php esc_html_e( 'New Booking', 'moga-travel' ); ?>
                    </a>

                </div>
            </div>

            <?php // ── Quick filter chips — full page navigation, no AJAX ── ?>
            <div class="moga-db-toolbar__quick">
                <span class="moga-db-toolbar__quick-label">
                    <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    <?php esc_html_e( 'Quick:', 'moga-travel' ); ?>
                </span>

                <?php
                $bk_chips = array(
                    array(
                        'label'  => __( 'All Bookings', 'moga-travel' ),
                        'dot'    => '',
                        'active' => ( $filter_status === '' && $filter_type === '' && $filter_search === '' && empty( $_GET['bk_pay'] ) ),
                        'url'    => add_query_arg( array( 'tab' => 'all-bookings', 'bk_chip' => '1' ), $dashboard_url ),
                    ),
                    array(
                        'label'  => __( 'Pending Approval', 'moga-travel' ),
                        'dot'    => '#f59e0b',
                        'active' => ( $filter_status === 'pending' ),
                        'url'    => add_query_arg( array( 'tab' => 'all-bookings', 'bk_status' => 'pending',   'bk_chip' => '1' ), $dashboard_url ),
                    ),
                    array(
                        'label'  => __( 'Confirmed', 'moga-travel' ),
                        'dot'    => '#10b981',
                        'active' => ( $filter_status === 'confirmed' ),
                        'url'    => add_query_arg( array( 'tab' => 'all-bookings', 'bk_status' => 'confirmed', 'bk_chip' => '1' ), $dashboard_url ),
                    ),
                    array(
                        'label'  => __( 'Unpaid', 'moga-travel' ),
                        'dot'    => '#ef4444',
                        'active' => ( isset( $_GET['bk_pay'] ) && sanitize_key( $_GET['bk_pay'] ) === 'unpaid' ),
                        'url'    => add_query_arg( array( 'tab' => 'all-bookings', 'bk_pay' => 'unpaid',       'bk_chip' => '1' ), $dashboard_url ),
                    ),
                    array(
                        'label'  => __( 'Tours Only', 'moga-travel' ),
                        'dot'    => '#8b5cf6',
                        'active' => ( $filter_type === 'tour' ),
                        'url'    => add_query_arg( array( 'tab' => 'all-bookings', 'bk_type' => 'tour',        'bk_chip' => '1' ), $dashboard_url ),
                    ),
                );
                foreach ( $bk_chips as $chip ) :
                ?>
                    <a href="<?php echo esc_url( $chip['url'] ); ?>"
                       class="moga-db-toolbar__chip<?php echo $chip['active'] ? ' is-active' : ''; ?>">
                        <?php if ( $chip['dot'] ) : ?>
                            <span class="moga-db-toolbar__chip-dot"
                                  style="background:<?php echo esc_attr( $chip['dot'] ); ?>;"></span>
                        <?php endif; ?>
                        <?php echo esc_html( $chip['label'] ); ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <?php // ── Advanced filter panel — all fields in ONE row ── ?>
            <div class="moga-db-toolbar__filters<?php echo $active_filter_count > 0 ? ' is-open' : ''; ?>"
                 id="moga-bk-filters">

                <div class="moga-db-toolbar__filters-row">

                    <div class="moga-db-toolbar__filter-group">
                        <label for="moga-bk-status"><?php esc_html_e( 'Booking Status', 'moga-travel' ); ?></label>
                        <select name="bk_status" id="moga-bk-status" class="moga-db-toolbar__select">
                            <option value=""><?php esc_html_e( 'All Statuses', 'moga-travel' ); ?></option>
                            <?php foreach ( array(
                                'pending'   => __( 'Pending',   'moga-travel' ),
                                'confirmed' => __( 'Confirmed', 'moga-travel' ),
                                'completed' => __( 'Completed', 'moga-travel' ),
                                'cancelled' => __( 'Cancelled', 'moga-travel' ),
                                'refunded'  => __( 'Refunded',  'moga-travel' ),
                                'no_show'   => __( 'No Show',   'moga-travel' ),
                            ) as $val => $label ) :
                                printf( '<option value="%s"%s>%s</option>', esc_attr( $val ), selected( $filter_status, $val, false ), esc_html( $label ) );
                            endforeach; ?>
                        </select>
                    </div>

                    <div class="moga-db-toolbar__filter-group">
                        <label for="moga-bk-pay"><?php esc_html_e( 'Payment Status', 'moga-travel' ); ?></label>
                        <select name="bk_pay" id="moga-bk-pay" class="moga-db-toolbar__select">
                            <option value=""><?php esc_html_e( 'All Payments', 'moga-travel' ); ?></option>
                            <?php
                            $selected_pay = isset( $_GET['bk_pay'] ) ? sanitize_key( $_GET['bk_pay'] ) : '';
                            foreach ( array(
                                'unpaid'         => __( 'Unpaid',         'moga-travel' ),
                                'paid'           => __( 'Paid',           'moga-travel' ),
                                'partially_paid' => __( 'Partially Paid', 'moga-travel' ),
                                'refunded'       => __( 'Refunded',       'moga-travel' ),
                            ) as $val => $label ) :
                                printf( '<option value="%s"%s>%s</option>', esc_attr( $val ), selected( $selected_pay, $val, false ), esc_html( $label ) );
                            endforeach; ?>
                        </select>
                    </div>

                    <div class="moga-db-toolbar__filter-group">
                        <label for="moga-bk-type"><?php esc_html_e( 'Type', 'moga-travel' ); ?></label>
                        <select name="bk_type" id="moga-bk-type" class="moga-db-toolbar__select">
                            <option value=""><?php esc_html_e( 'All Types', 'moga-travel' ); ?></option>
                            <?php foreach ( array(
                                'property' => __( 'Property', 'moga-travel' ),
                                'tour'     => __( 'Tour',     'moga-travel' ),
                                'bus'      => __( 'Bus',      'moga-travel' ),
                                'rental'   => __( 'Rental',   'moga-travel' ),
                            ) as $val => $label ) :
                                printf( '<option value="%s"%s>%s</option>', esc_attr( $val ), selected( $filter_type, $val, false ), esc_html( $label ) );
                            endforeach; ?>
                        </select>
                    </div>

                    <div class="moga-db-toolbar__filter-group">
                        <label><?php esc_html_e( 'Date Range', 'moga-travel' ); ?></label>
                        <div class="moga-db-toolbar__date-range">
                            <input type="date" name="bk_from" id="moga-bk-from"
                                   value="<?php echo esc_attr( $filter_date_from ); ?>"
                                   class="moga-db-toolbar__input">
                            <span class="moga-db-toolbar__date-sep">–</span>
                            <input type="date" name="bk_to" id="moga-bk-to"
                                   value="<?php echo esc_attr( $filter_date_to ); ?>"
                                   class="moga-db-toolbar__input">
                        </div>
                    </div>

                </div>

                <div class="moga-db-toolbar__filters-actions">
                    <a href="<?php echo esc_url( add_query_arg( 'tab', 'all-bookings', $dashboard_url ) ); ?>"
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
                esc_html( _n( 'Showing %s booking found', 'Showing %s bookings found', $total_rows, 'moga-travel' ) ),
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

    <?php // ── Bookings Table ────────────────────────────────────────────────── ?>
    <div class="moga-db-table-wrap">
        <table class="moga-db-table" id="moga-bk-table">
            <thead>
                <tr>
                    <th class="moga-db-table__th moga-db-table__th--sortable">
                        <a href="<?php echo esc_url( moga_sort_url( 'booking_number', $orderby, $order, $current_url ) ); ?>">
                            <?php esc_html_e( 'Booking #', 'moga-travel' ); ?>
                            <?php echo moga_sort_indicator( 'booking_number', $orderby, $order ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </a>
                    </th>
                    <th class="moga-db-table__th"><?php esc_html_e( 'Guest', 'moga-travel' ); ?></th>
                    <th class="moga-db-table__th"><?php esc_html_e( 'Listing', 'moga-travel' ); ?></th>
                    <th class="moga-db-table__th"><?php esc_html_e( 'Vendor', 'moga-travel' ); ?></th>
                    <th class="moga-db-table__th"><?php esc_html_e( 'Type', 'moga-travel' ); ?></th>
                    <th class="moga-db-table__th moga-db-table__th--sortable">
                        <a href="<?php echo esc_url( moga_sort_url( 'check_in', $orderby, $order, $current_url ) ); ?>">
                            <?php esc_html_e( 'Dates', 'moga-travel' ); ?>
                            <?php echo moga_sort_indicator( 'check_in', $orderby, $order ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </a>
                    </th>
                    <th class="moga-db-table__th moga-db-table__th--sortable">
                        <a href="<?php echo esc_url( moga_sort_url( 'total_amount', $orderby, $order, $current_url ) ); ?>">
                            <?php esc_html_e( 'Amount', 'moga-travel' ); ?>
                            <?php echo moga_sort_indicator( 'total_amount', $orderby, $order ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </a>
                    </th>
                    <th class="moga-db-table__th moga-db-table__th--sortable">
                        <a href="<?php echo esc_url( moga_sort_url( 'status', $orderby, $order, $current_url ) ); ?>">
                            <?php esc_html_e( 'Status', 'moga-travel' ); ?>
                            <?php echo moga_sort_indicator( 'status', $orderby, $order ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </a>
                    </th>
                    <th class="moga-db-table__th moga-db-table__th--sortable">
                        <a href="<?php echo esc_url( moga_sort_url( 'created_at', $orderby, $order, $current_url ) ); ?>">
                            <?php esc_html_e( 'Created', 'moga-travel' ); ?>
                            <?php echo moga_sort_indicator( 'created_at', $orderby, $order ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </a>
                    </th>
                    <th class="moga-db-table__th moga-db-table__th--actions">
                        <?php esc_html_e( 'Actions', 'moga-travel' ); ?>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $bookings ) ) : ?>
                    <tr>
                        <td colspan="10" class="moga-db-table__empty">
                            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                 stroke-width="1.2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            <p><?php esc_html_e( 'No bookings found.', 'moga-travel' ); ?></p>
                            <?php if ( $active_filter_count > 0 ) : ?>
                                <a href="<?php echo esc_url( add_query_arg( 'tab', 'all-bookings', $dashboard_url ) ); ?>"
                                   class="moga-btn moga-btn--ghost moga-btn--sm">
                                    <?php esc_html_e( 'Clear filters', 'moga-travel' ); ?>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else : ?>
                    <?php foreach ( $bookings as $booking ) :
                        $status_ui  = moga_booking_status_ui( $booking->status );
                        $payment_ui = moga_payment_status_ui( $booking->payment_status );
                        $type_label = moga_booking_type_label( $booking->booking_type );

                        // Dates display.
                        $check_in  = date_i18n( get_option( 'date_format' ), strtotime( $booking->check_in ) );
                        $check_out = date_i18n( get_option( 'date_format' ), strtotime( $booking->check_out ) );

                        // Created at.
                        $created = date_i18n( get_option( 'date_format' ), strtotime( $booking->created_at ) );

                        // Listing title fallback.
                        $listing_title = $booking->listing_title ?: __( '(deleted listing)', 'moga-travel' );
                        $listing_link  = $booking->listing_id ? get_edit_post_link( $booking->listing_id ) : '';

                        // Guest display.
                        $guest_name  = $booking->guest_name  ?: __( '(unknown)', 'moga-travel' );
                        $guest_email = $booking->guest_email ?: '';

                        // Owner display.
                        $owner_name = $booking->owner_name ?: __( '(unknown)', 'moga-travel' );

                        // Amount display.
                        $amount = $currency_symbol . number_format_i18n( (float) $booking->total_amount, 2 );
                    ?>
                    <tr class="moga-db-table__row" data-booking-id="<?php echo esc_attr( $booking->id ); ?>">

                        <td class="moga-db-table__td moga-db-table__td--booking-no">
                            <span class="moga-db-table__booking-number">
                                <?php echo esc_html( $booking->booking_number ); ?>
                            </span>
                        </td>

                        <td class="moga-db-table__td">
                            <div class="moga-db-table__guest">
                                <span class="moga-db-table__guest-name"><?php echo esc_html( $guest_name ); ?></span>
                                <?php if ( $guest_email ) : ?>
                                    <span class="moga-db-table__guest-email"><?php echo esc_html( $guest_email ); ?></span>
                                <?php endif; ?>
                            </div>
                        </td>

                        <td class="moga-db-table__td">
                            <?php if ( $listing_link ) : ?>
                                <a href="<?php echo esc_url( $listing_link ); ?>"
                                   class="moga-db-table__listing-link"
                                   target="_blank" rel="noopener noreferrer">
                                    <?php echo esc_html( $listing_title ); ?>
                                </a>
                            <?php else : ?>
                                <span class="moga-db-table__listing-deleted"><?php echo esc_html( $listing_title ); ?></span>
                            <?php endif; ?>
                        </td>

                        <td class="moga-db-table__td">
                            <span class="moga-db-table__owner"><?php echo esc_html( $owner_name ); ?></span>
                        </td>

                        <td class="moga-db-table__td">
                            <span class="moga-db-table__type-badge moga-db-table__type-badge--<?php echo esc_attr( $booking->booking_type ); ?>">
                                <?php echo esc_html( $type_label ); ?>
                            </span>
                        </td>

                        <td class="moga-db-table__td moga-db-table__td--dates">
                            <span class="moga-db-table__date"><?php echo esc_html( $check_in ); ?></span>
                            <span class="moga-db-table__date-sep" aria-hidden="true">→</span>
                            <span class="moga-db-table__date"><?php echo esc_html( $check_out ); ?></span>
                            <span class="moga-db-table__nights">
                                <?php
                                printf(
                                    /* translators: %d: number of nights */
                                    esc_html( _n( '%d night', '%d nights', (int) $booking->total_nights, 'moga-travel' ) ),
                                    (int) $booking->total_nights
                                );
                                ?>
                            </span>
                        </td>

                        <td class="moga-db-table__td moga-db-table__td--amount">
                            <span class="moga-db-table__amount"><?php echo esc_html( $amount ); ?></span>
                            <span class="moga-db-table__payment-badge moga-db-table__payment-badge--<?php echo esc_attr( $payment_ui['class'] ); ?>">
                                <?php echo esc_html( $payment_ui['label'] ); ?>
                            </span>
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

                                <?php // View details — goes to booking detail tab (future). ?>
                                <a href="<?php echo esc_url( add_query_arg( array( 'tab' => 'all-bookings', 'bk_id' => $booking->id ), $dashboard_url ) ); ?>"
                                   class="moga-db-table__action moga-db-table__action--view"
                                   title="<?php esc_attr_e( 'View Details', 'moga-travel' ); ?>"
                                   aria-label="<?php esc_attr_e( 'View booking details', 'moga-travel' ); ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15"
                                         fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                         stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>

                                <?php // Confirm — only if pending. ?>
                                <?php if ( $booking->status === 'pending' ) : ?>
                                    <button type="button"
                                            class="moga-db-table__action moga-db-table__action--confirm"
                                            title="<?php esc_attr_e( 'Confirm Booking', 'moga-travel' ); ?>"
                                            aria-label="<?php esc_attr_e( 'Confirm booking', 'moga-travel' ); ?>"
                                            data-action="confirm"
                                            data-booking-id="<?php echo esc_attr( $booking->id ); ?>"
                                            data-nonce="<?php echo esc_attr( wp_create_nonce( 'moga_booking_action_' . $booking->id ) ); ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15"
                                             fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                             stroke-width="2" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </button>
                                <?php endif; ?>

                                <?php // Cancel — only if pending or confirmed. ?>
                                <?php if ( in_array( $booking->status, array( 'pending', 'confirmed' ), true ) ) : ?>
                                    <button type="button"
                                            class="moga-db-table__action moga-db-table__action--cancel"
                                            title="<?php esc_attr_e( 'Cancel Booking', 'moga-travel' ); ?>"
                                            aria-label="<?php esc_attr_e( 'Cancel booking', 'moga-travel' ); ?>"
                                            data-action="cancel"
                                            data-booking-id="<?php echo esc_attr( $booking->id ); ?>"
                                            data-nonce="<?php echo esc_attr( wp_create_nonce( 'moga_booking_action_' . $booking->id ) ); ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15"
                                             fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                             stroke-width="2" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
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
             aria-label="<?php esc_attr_e( 'Bookings pagination', 'moga-travel' ); ?>">

            <?php
            // Build base URL preserving all active filters.
            $pagination_args = array_filter( array(
                'tab'          => 'all-bookings',
                'bk_status'    => $filter_status,
                'bk_type'      => $filter_type,
                'bk_search'    => $filter_search,
                'bk_from'      => $filter_date_from,
                'bk_to'        => $filter_date_to,
                'bk_order'     => $orderby !== 'created_at' ? $orderby : '',
                'bk_dir'       => $order !== 'DESC' ? $order : '',
                'bk_per_page'  => $per_page !== 20 ? $per_page : '',
            ) );
            $page_base = add_query_arg( $pagination_args, $dashboard_url );

            // Previous.
            if ( $paged > 1 ) : ?>
                <a href="<?php echo esc_url( add_query_arg( 'bk_paged', $paged - 1, $page_base ) ); ?>"
                   class="moga-db-pagination__btn"
                   aria-label="<?php esc_attr_e( 'Previous page', 'moga-travel' ); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor"
                         stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
            <?php endif; ?>

            <?php
            // Page numbers — show up to 7 links with ellipsis.
            $start = max( 1, $paged - 3 );
            $end   = min( $total_pages, $paged + 3 );

            if ( $start > 1 ) :
                echo '<a href="' . esc_url( add_query_arg( 'bk_paged', 1, $page_base ) ) . '" class="moga-db-pagination__btn">1</a>';
                if ( $start > 2 ) {
                    echo '<span class="moga-db-pagination__ellipsis" aria-hidden="true">…</span>';
                }
            endif;

            for ( $i = $start; $i <= $end; $i++ ) :
                $is_current = ( $i === $paged );
                printf(
                    '<a href="%s" class="moga-db-pagination__btn%s"%s>%d</a>',
                    esc_url( add_query_arg( 'bk_paged', $i, $page_base ) ),
                    $is_current ? ' is-active' : '',
                    $is_current ? ' aria-current="page"' : '',
                    $i
                );
            endfor;

            if ( $end < $total_pages ) :
                if ( $end < $total_pages - 1 ) {
                    echo '<span class="moga-db-pagination__ellipsis" aria-hidden="true">…</span>';
                }
                echo '<a href="' . esc_url( add_query_arg( 'bk_paged', $total_pages, $page_base ) ) . '" class="moga-db-pagination__btn">' . esc_html( $total_pages ) . '</a>';
            endif;
            ?>

            <?php // Next. ?>
            <?php if ( $paged < $total_pages ) : ?>
                <a href="<?php echo esc_url( add_query_arg( 'bk_paged', $paged + 1, $page_base ) ); ?>"
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
                    /* translators: 1: current page, 2: total pages */
                    esc_html__( 'Page %1$s of %2$s', 'moga-travel' ),
                    '<strong>' . esc_html( $paged ) . '</strong>',
                    '<strong>' . esc_html( $total_pages ) . '</strong>'
                );
                ?>
            </span>

        </nav>
    <?php endif; ?>

</div><?php // .moga-db-all-bookings ?>
