<?php

/**
 * Admin Dashboard — Notifications Log Tab
 *
 * Displays a log of all system-sent notifications (booking confirmations,
 * approvals, reminders, cancellations, etc.).
 *
 * Works defensively — checks whether the notifications table exists before
 * querying. Shows a clear empty state if the table is not yet created,
 * so the page never crashes during development.
 *
 * Filters: notification type, recipient email, date range, status.
 * Sortable: sent_at (default), recipient, type, status.
 * Per-page: inline select between Filter and CTA buttons.
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
$current_url   = add_query_arg( 'tab', 'notifications-log', $dashboard_url );
$table_name    = $wpdb->prefix . 'moga_notifications';

// ── Check if notifications table exists ──────────────────────────────────────

$table_exists = $wpdb->get_var(
    $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name )
) === $table_name;

// ── Sanitize filter inputs ────────────────────────────────────────────────────

$filter_type    = isset( $_GET['nl_type'] )   ? sanitize_key( $_GET['nl_type'] )              : '';
$filter_status  = isset( $_GET['nl_status'] ) ? sanitize_key( $_GET['nl_status'] )            : '';
$filter_search  = isset( $_GET['nl_search'] ) ? sanitize_text_field( wp_unslash( $_GET['nl_search'] ) ) : '';
$filter_from    = isset( $_GET['nl_from'] )   ? sanitize_text_field( $_GET['nl_from'] )       : '';
$filter_to      = isset( $_GET['nl_to'] )     ? sanitize_text_field( $_GET['nl_to'] )         : '';
$orderby        = isset( $_GET['nl_order'] )  ? sanitize_key( $_GET['nl_order'] )             : 'sent_at';
$order          = isset( $_GET['nl_dir'] ) && strtoupper( $_GET['nl_dir'] ) === 'ASC' ? 'ASC' : 'DESC';
$paged          = isset( $_GET['nl_paged'] )  ? max( 1, (int) $_GET['nl_paged'] )             : 1;
$per_page       = isset( $_GET['nl_per_page'] ) ? (int) $_GET['nl_per_page']                  : 20;
$per_page       = in_array( $per_page, array( 10, 20, 25, 50, 100 ), true ) ? $per_page : 20;
$offset         = ( $paged - 1 ) * $per_page;

// Whitelist orderby columns.
$allowed_orderby = array( 'sent_at', 'recipient_email', 'type', 'status' );
if ( ! in_array( $orderby, $allowed_orderby, true ) ) {
    $orderby = 'sent_at';
}

// ── Chip flag — chip clicks don't open filter panel ───────────────────────────

$nl_via_chip = ! empty( $_GET['nl_chip'] );

$active_filter_count = $nl_via_chip ? 0 :
    (int) ( $filter_type !== '' )
  + (int) ( $filter_status !== '' )
  + (int) ( $filter_search !== '' )
  + (int) ( $filter_from !== '' )
  + (int) ( $filter_to !== '' );

// ── Notification type labels ──────────────────────────────────────────────────

$notification_types = array(
    'booking_confirmation'  => __( 'Booking Confirmation',  'moga-travel' ),
    'booking_approved'      => __( 'Booking Approved',      'moga-travel' ),
    'booking_rejected'      => __( 'Booking Rejected',      'moga-travel' ),
    'booking_cancelled'     => __( 'Booking Cancelled',     'moga-travel' ),
    'booking_reminder'      => __( 'Booking Reminder',      'moga-travel' ),
    'payment_received'      => __( 'Payment Received',      'moga-travel' ),
    'vendor_application'    => __( 'Vendor Application',    'moga-travel' ),
    'vendor_approved'       => __( 'Vendor Approved',       'moga-travel' ),
    'vendor_rejected'       => __( 'Vendor Rejected',       'moga-travel' ),
    'review_request'        => __( 'Review Request',        'moga-travel' ),
    'password_reset'        => __( 'Password Reset',        'moga-travel' ),
);

$notification_statuses = array(
    'sent'    => __( 'Sent',    'moga-travel' ),
    'failed'  => __( 'Failed',  'moga-travel' ),
    'pending' => __( 'Pending', 'moga-travel' ),
);

// ── Sort helpers ──────────────────────────────────────────────────────────────

function moga_nl_sort_url( $col, $current_orderby, $current_order, $base_url ) {
    $dir = ( $current_orderby === $col && $current_order === 'DESC' ) ? 'ASC' : 'DESC';
    return add_query_arg( array( 'nl_order' => $col, 'nl_dir' => $dir ), $base_url );
}

function moga_nl_sort_indicator( $col, $current_orderby, $current_order ) {
    if ( $current_orderby !== $col ) {
        return '<span class="moga-db-table__sort-icon moga-db-table__sort-icon--neutral" aria-hidden="true">↕</span>';
    }
    return $current_order === 'ASC'
        ? '<span class="moga-db-table__sort-icon moga-db-table__sort-icon--asc" aria-hidden="true">↑</span>'
        : '<span class="moga-db-table__sort-icon moga-db-table__sort-icon--desc" aria-hidden="true">↓</span>';
}

// ── Stats + data (only if table exists) ──────────────────────────────────────

$stats       = null;
$logs        = array();
$total_rows  = 0;
$total_pages = 1;

if ( $table_exists ) {

    // Stat counts.
    $stats = $wpdb->get_row(
        "SELECT
            COUNT(*)                          AS total,
            SUM( status = 'sent' )            AS sent,
            SUM( status = 'failed' )          AS failed,
            SUM( status = 'pending' )         AS pending,
            SUM( DATE(sent_at) = CURDATE() )  AS today
         FROM {$table_name}"
    );

    // ── Build WHERE clause ─────────────────────────────────────────────────────

    $where  = array( '1=1' );
    $params = array();

    if ( $filter_type !== '' ) {
        $where[]  = 'type = %s';
        $params[] = $filter_type;
    }

    if ( $filter_status !== '' ) {
        $where[]  = 'status = %s';
        $params[] = $filter_status;
    }

    if ( $filter_search !== '' ) {
        $where[]  = '( recipient_email LIKE %s OR subject LIKE %s OR booking_number LIKE %s )';
        $like     = '%' . $wpdb->esc_like( $filter_search ) . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    if ( $filter_from !== '' ) {
        $where[]  = 'DATE(sent_at) >= %s';
        $params[] = $filter_from;
    }

    if ( $filter_to !== '' ) {
        $where[]  = 'DATE(sent_at) <= %s';
        $params[] = $filter_to;
    }

    $where_sql = implode( ' AND ', $where );

    // Count.
    $count_sql = "SELECT COUNT(*) FROM {$table_name} WHERE {$where_sql}";
    $total_rows = (int) ( empty( $params )
        ? $wpdb->get_var( $count_sql )
        : $wpdb->get_var( $wpdb->prepare( $count_sql, $params ) ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    );

    $total_pages = max( 1, (int) ceil( $total_rows / $per_page ) );
    if ( $paged > $total_pages ) {
        $paged = $total_pages;
    }

    // Fetch rows.
    $data_sql = "
        SELECT
            id,
            type,
            recipient_email,
            recipient_name,
            subject,
            booking_number,
            status,
            error_message,
            sent_at
        FROM {$table_name}
        WHERE {$where_sql}
        ORDER BY {$orderby} {$order}
        LIMIT %d OFFSET %d
    ";

    $params_with_limit = array_merge( $params, array( $per_page, $offset ) );
    $logs = $wpdb->get_results(
        $wpdb->prepare( $data_sql, $params_with_limit ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    );
}

// ── Status badge helper ───────────────────────────────────────────────────────

function moga_nl_status_ui( $status ) {
    $map = array(
        'sent'    => array( 'class' => 'success', 'label' => __( 'Sent',    'moga-travel' ) ),
        'failed'  => array( 'class' => 'danger',  'label' => __( 'Failed',  'moga-travel' ) ),
        'pending' => array( 'class' => 'warning', 'label' => __( 'Pending', 'moga-travel' ) ),
    );
    return $map[ $status ] ?? array( 'class' => 'neutral', 'label' => ucfirst( $status ) );
}
?>

<div class="moga-db-notifications-log">

    <?php // ── Stat Cards ── ?>
    <div class="moga-db-stats moga-db-stats--4col">

        <div class="moga-db-stats__card">
            <div class="moga-db-stats__value">
                <?php echo esc_html( $table_exists ? number_format_i18n( (int) $stats->total ) : '—' ); ?>
            </div>
            <div class="moga-db-stats__label"><?php esc_html_e( 'Total Sent', 'moga-travel' ); ?></div>
        </div>

        <div class="moga-db-stats__card moga-db-stats__card--success">
            <div class="moga-db-stats__value">
                <?php echo esc_html( $table_exists ? number_format_i18n( (int) $stats->sent ) : '—' ); ?>
            </div>
            <div class="moga-db-stats__label"><?php esc_html_e( 'Delivered', 'moga-travel' ); ?></div>
        </div>

        <div class="moga-db-stats__card moga-db-stats__card--danger">
            <div class="moga-db-stats__value">
                <?php echo esc_html( $table_exists ? number_format_i18n( (int) $stats->failed ) : '—' ); ?>
            </div>
            <div class="moga-db-stats__label"><?php esc_html_e( 'Failed', 'moga-travel' ); ?></div>
        </div>

        <div class="moga-db-stats__card moga-db-stats__card--info">
            <div class="moga-db-stats__value">
                <?php echo esc_html( $table_exists ? number_format_i18n( (int) $stats->today ) : '—' ); ?>
            </div>
            <div class="moga-db-stats__label"><?php esc_html_e( 'Sent Today', 'moga-travel' ); ?></div>
        </div>

    </div>

    <?php // ── Toolbar ── ?>
    <div class="moga-db-toolbar">
        <form method="get" action="<?php echo esc_url( $current_url ); ?>"
              class="moga-db-toolbar__form" id="moga-nl-filter-form">

            <input type="hidden" name="tab" value="notifications-log">
            <?php if ( $orderby !== 'sent_at' ) : ?><input type="hidden" name="nl_order" value="<?php echo esc_attr( $orderby ); ?>"><?php endif; ?>
            <?php if ( $order !== 'DESC' ) : ?><input type="hidden" name="nl_dir" value="<?php echo esc_attr( $order ); ?>"><?php endif; ?>

            <?php // ── Top row ── ?>
            <div class="moga-db-toolbar__top">

                <div class="moga-db-toolbar__search">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
                    </svg>
                    <input type="text" name="nl_search"
                           value="<?php echo esc_attr( $filter_search ); ?>"
                           placeholder="<?php esc_attr_e( 'Search by email, subject, or booking #…', 'moga-travel' ); ?>"
                           class="moga-db-toolbar__search-input" autocomplete="off">
                    <span class="moga-db-toolbar__kbd">⌘K</span>
                </div>

                <div class="moga-db-toolbar__actions">

                    <button type="button"
                            class="moga-db-toolbar__filter-toggle"
                            id="moga-nl-filter-toggle"
                            aria-expanded="<?php echo $active_filter_count > 0 ? 'true' : 'false'; ?>"
                            aria-controls="moga-nl-filters">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none"
                             viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18M7 10h10M10 16h4"/>
                        </svg>
                        <?php esc_html_e( 'Filters', 'moga-travel' ); ?>
                        <?php if ( $active_filter_count > 0 ) : ?>
                            <span class="moga-db-toolbar__filter-badge"><?php echo esc_html( $active_filter_count ); ?></span>
                        <?php endif; ?>
                    </button>

                    <select name="nl_per_page" class="moga-db-toolbar__per-page-select"
                            onchange="this.form.submit()">
                        <?php foreach ( array( 10, 20, 25, 50, 100 ) as $n ) : ?>
                            <option value="<?php echo esc_attr( $n ); ?>" <?php selected( $per_page, $n ); ?>>
                                <?php echo esc_html( $n ); ?> / <?php esc_html_e( 'page', 'moga-travel' ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                </div>
            </div>

            <?php // ── Quick chips ── ?>
            <div class="moga-db-toolbar__quick">
                <span class="moga-db-toolbar__quick-label">
                    <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    <?php esc_html_e( 'Quick:', 'moga-travel' ); ?>
                </span>
                <?php
                $nl_chips = array(
                    array( 'label' => __( 'All',              'moga-travel' ), 'dot' => '',        'active' => ( $filter_type === '' && $filter_status === '' && $filter_search === '' ), 'url' => add_query_arg( array( 'tab' => 'notifications-log', 'nl_chip' => '1' ), $dashboard_url ) ),
                    array( 'label' => __( 'Delivered',         'moga-travel' ), 'dot' => '#10b981', 'active' => $filter_status === 'sent',    'url' => add_query_arg( array( 'tab' => 'notifications-log', 'nl_status' => 'sent',   'nl_chip' => '1' ), $dashboard_url ) ),
                    array( 'label' => __( 'Failed',            'moga-travel' ), 'dot' => '#ef4444', 'active' => $filter_status === 'failed',  'url' => add_query_arg( array( 'tab' => 'notifications-log', 'nl_status' => 'failed', 'nl_chip' => '1' ), $dashboard_url ) ),
                    array( 'label' => __( 'Booking Confirmations', 'moga-travel' ), 'dot' => '#3b82f6', 'active' => $filter_type === 'booking_confirmation', 'url' => add_query_arg( array( 'tab' => 'notifications-log', 'nl_type' => 'booking_confirmation', 'nl_chip' => '1' ), $dashboard_url ) ),
                    array( 'label' => __( 'Reminders',         'moga-travel' ), 'dot' => '#f59e0b', 'active' => $filter_type === 'booking_reminder', 'url' => add_query_arg( array( 'tab' => 'notifications-log', 'nl_type' => 'booking_reminder', 'nl_chip' => '1' ), $dashboard_url ) ),
                );
                foreach ( $nl_chips as $chip ) : ?>
                    <a href="<?php echo esc_url( $chip['url'] ); ?>"
                       class="moga-db-toolbar__chip<?php echo $chip['active'] ? ' is-active' : ''; ?>">
                        <?php if ( $chip['dot'] ) : ?>
                            <span class="moga-db-toolbar__chip-dot" style="background:<?php echo esc_attr( $chip['dot'] ); ?>;"></span>
                        <?php endif; ?>
                        <?php echo esc_html( $chip['label'] ); ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <?php // ── Filter panel — all fields in one row ── ?>
            <div class="moga-db-toolbar__filters<?php echo $active_filter_count > 0 ? ' is-open' : ''; ?>"
                 id="moga-nl-filters">

                <div class="moga-db-toolbar__filters-row">

                    <div class="moga-db-toolbar__filter-group">
                        <label for="moga-nl-type"><?php esc_html_e( 'Notification Type', 'moga-travel' ); ?></label>
                        <select name="nl_type" id="moga-nl-type" class="moga-db-toolbar__select">
                            <option value=""><?php esc_html_e( 'All Types', 'moga-travel' ); ?></option>
                            <?php foreach ( $notification_types as $val => $label ) :
                                printf( '<option value="%s"%s>%s</option>', esc_attr( $val ), selected( $filter_type, $val, false ), esc_html( $label ) );
                            endforeach; ?>
                        </select>
                    </div>

                    <div class="moga-db-toolbar__filter-group">
                        <label for="moga-nl-status"><?php esc_html_e( 'Status', 'moga-travel' ); ?></label>
                        <select name="nl_status" id="moga-nl-status" class="moga-db-toolbar__select">
                            <option value=""><?php esc_html_e( 'All Statuses', 'moga-travel' ); ?></option>
                            <?php foreach ( $notification_statuses as $val => $label ) :
                                printf( '<option value="%s"%s>%s</option>', esc_attr( $val ), selected( $filter_status, $val, false ), esc_html( $label ) );
                            endforeach; ?>
                        </select>
                    </div>

                    <div class="moga-db-toolbar__filter-group">
                        <label><?php esc_html_e( 'Date Range', 'moga-travel' ); ?></label>
                        <div class="moga-db-toolbar__date-range">
                            <input type="date" name="nl_from" id="moga-nl-from"
                                   value="<?php echo esc_attr( $filter_from ); ?>"
                                   class="moga-db-toolbar__input">
                            <span class="moga-db-toolbar__date-sep">–</span>
                            <input type="date" name="nl_to" id="moga-nl-to"
                                   value="<?php echo esc_attr( $filter_to ); ?>"
                                   class="moga-db-toolbar__input">
                        </div>
                    </div>

                </div>

                <div class="moga-db-toolbar__filters-actions">
                    <a href="<?php echo esc_url( add_query_arg( 'tab', 'notifications-log', $dashboard_url ) ); ?>"
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
            <?php if ( ! $table_exists ) : ?>
                <span><?php esc_html_e( 'Notifications table not yet created.', 'moga-travel' ); ?></span>
            <?php else : ?>
                <?php printf(
                    esc_html( _n( 'Showing %s notification', 'Showing %s notifications', $total_rows, 'moga-travel' ) ),
                    '<strong>' . esc_html( number_format_i18n( $total_rows ) ) . '</strong>'
                ); ?>
            <?php endif; ?>
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

    <?php // ── Table ── ?>
    <div class="moga-db-table-wrap">
        <table class="moga-db-table" id="moga-nl-table">
            <thead>
                <tr>
                    <th class="moga-db-table__th moga-db-table__th--sortable">
                        <a href="<?php echo esc_url( moga_nl_sort_url( 'type', $orderby, $order, $current_url ) ); ?>">
                            <?php esc_html_e( 'Type', 'moga-travel' ); ?>
                            <?php echo moga_nl_sort_indicator( 'type', $orderby, $order ); // phpcs:ignore ?>
                        </a>
                    </th>
                    <th class="moga-db-table__th moga-db-table__th--sortable">
                        <a href="<?php echo esc_url( moga_nl_sort_url( 'recipient_email', $orderby, $order, $current_url ) ); ?>">
                            <?php esc_html_e( 'Recipient', 'moga-travel' ); ?>
                            <?php echo moga_nl_sort_indicator( 'recipient_email', $orderby, $order ); // phpcs:ignore ?>
                        </a>
                    </th>
                    <th class="moga-db-table__th"><?php esc_html_e( 'Subject', 'moga-travel' ); ?></th>
                    <th class="moga-db-table__th"><?php esc_html_e( 'Booking #', 'moga-travel' ); ?></th>
                    <th class="moga-db-table__th moga-db-table__th--sortable">
                        <a href="<?php echo esc_url( moga_nl_sort_url( 'status', $orderby, $order, $current_url ) ); ?>">
                            <?php esc_html_e( 'Status', 'moga-travel' ); ?>
                            <?php echo moga_nl_sort_indicator( 'status', $orderby, $order ); // phpcs:ignore ?>
                        </a>
                    </th>
                    <th class="moga-db-table__th moga-db-table__th--sortable">
                        <a href="<?php echo esc_url( moga_nl_sort_url( 'sent_at', $orderby, $order, $current_url ) ); ?>">
                            <?php esc_html_e( 'Sent At', 'moga-travel' ); ?>
                            <?php echo moga_nl_sort_indicator( 'sent_at', $orderby, $order ); // phpcs:ignore ?>
                        </a>
                    </th>
                    <th class="moga-db-table__th moga-db-table__th--actions">
                        <?php esc_html_e( 'Actions', 'moga-travel' ); ?>
                    </th>
                </tr>
            </thead>
            <tbody>

                <?php if ( ! $table_exists ) : ?>
                    <tr>
                        <td colspan="7" class="moga-db-table__empty">
                            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                 stroke-width="1.2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            <p><?php esc_html_e( 'The notifications log table has not been created yet.', 'moga-travel' ); ?></p>
                            <p class="moga-db-table__empty-hint">
                                <?php esc_html_e( 'It will be created automatically when the notification system is activated.', 'moga-travel' ); ?>
                            </p>
                        </td>
                    </tr>

                <?php elseif ( empty( $logs ) ) : ?>
                    <tr>
                        <td colspan="7" class="moga-db-table__empty">
                            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                 stroke-width="1.2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            <p><?php esc_html_e( 'No notifications found.', 'moga-travel' ); ?></p>
                            <?php if ( $active_filter_count > 0 || $nl_via_chip ) : ?>
                                <a href="<?php echo esc_url( add_query_arg( 'tab', 'notifications-log', $dashboard_url ) ); ?>"
                                   class="moga-btn moga-btn--ghost moga-btn--sm">
                                    <?php esc_html_e( 'Clear filters', 'moga-travel' ); ?>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>

                <?php else : ?>
                    <?php foreach ( $logs as $log ) :
                        $status_ui   = moga_nl_status_ui( $log->status );
                        $type_label  = $notification_types[ $log->type ] ?? ucwords( str_replace( '_', ' ', $log->type ) );
                        $sent_at     = $log->sent_at
                            ? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $log->sent_at ) )
                            : '—';
                    ?>
                        <tr class="moga-db-table__row">

                            <td class="moga-db-table__td">
                                <span class="moga-db-nl__type-label"><?php echo esc_html( $type_label ); ?></span>
                            </td>

                            <td class="moga-db-table__td">
                                <div class="moga-db-nl__recipient">
                                    <?php if ( $log->recipient_name ) : ?>
                                        <span class="moga-db-nl__recipient-name">
                                            <?php echo esc_html( $log->recipient_name ); ?>
                                        </span>
                                    <?php endif; ?>
                                    <span class="moga-db-nl__recipient-email">
                                        <?php echo esc_html( $log->recipient_email ); ?>
                                    </span>
                                </div>
                            </td>

                            <td class="moga-db-table__td">
                                <span class="moga-db-nl__subject" title="<?php echo esc_attr( $log->subject ); ?>">
                                    <?php echo esc_html( wp_trim_words( $log->subject, 8, '…' ) ); ?>
                                </span>
                            </td>

                            <td class="moga-db-table__td">
                                <?php if ( $log->booking_number ) : ?>
                                    <a href="<?php echo esc_url( add_query_arg( array( 'tab' => 'all-bookings', 'bk_search' => $log->booking_number ), $dashboard_url ) ); ?>"
                                       class="moga-db-nl__booking-link">
                                        <?php echo esc_html( $log->booking_number ); ?>
                                    </a>
                                <?php else : ?>
                                    <span class="moga-db-table__td--muted">—</span>
                                <?php endif; ?>
                            </td>

                            <td class="moga-db-table__td">
                                <span class="moga-db-table__status-badge moga-db-table__status-badge--<?php echo esc_attr( $status_ui['class'] ); ?>">
                                    <?php echo esc_html( $status_ui['label'] ); ?>
                                </span>
                                <?php if ( $log->status === 'failed' && $log->error_message ) : ?>
                                    <span class="moga-db-nl__error-hint"
                                          title="<?php echo esc_attr( $log->error_message ); ?>">
                                        <?php esc_html_e( '(hover for error)', 'moga-travel' ); ?>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td class="moga-db-table__td moga-db-table__td--created">
                                <?php echo esc_html( $sent_at ); ?>
                            </td>

                            <td class="moga-db-table__td moga-db-table__td--actions">
                                <div class="moga-db-table__actions">
                                    <?php // Resend action (only for failed/pending) ?>
                                    <?php if ( in_array( $log->status, array( 'failed', 'pending' ), true ) ) : ?>
                                        <button type="button"
                                           class="moga-db-table__action moga-db-table__action--edit moga-nl-resend-btn"
                                           data-log-id="<?php echo esc_attr( $log->id ); ?>"
                                           data-nonce="<?php echo esc_attr( wp_create_nonce( 'moga_dashboard_nonce' ) ); ?>"
                                           title="<?php esc_attr_e( 'Resend notification', 'moga-travel' ); ?>"
                                           aria-label="<?php esc_attr_e( 'Resend notification', 'moga-travel' ); ?>">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                                                 fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                 stroke-width="2" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                      d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
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

    <?php // ── Pagination ── ?>
    <?php if ( $total_pages > 1 ) : ?>
        <nav class="moga-db-pagination"
             aria-label="<?php esc_attr_e( 'Notifications pagination', 'moga-travel' ); ?>">
            <?php
            $pagination_args = array_filter( array(
                'tab'          => 'notifications-log',
                'nl_type'      => $filter_type,
                'nl_status'    => $filter_status,
                'nl_search'    => $filter_search,
                'nl_from'      => $filter_from,
                'nl_to'        => $filter_to,
                'nl_order'     => $orderby !== 'sent_at' ? $orderby : '',
                'nl_dir'       => $order !== 'DESC' ? $order : '',
                'nl_per_page'  => $per_page !== 20 ? $per_page : '',
            ) );
            $page_base = add_query_arg( $pagination_args, $dashboard_url );

            if ( $paged > 1 ) :
                echo '<a href="' . esc_url( add_query_arg( 'nl_paged', $paged - 1, $page_base ) ) . '" class="moga-db-pagination__btn" aria-label="' . esc_attr__( 'Previous', 'moga-travel' ) . '"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg></a>';
            endif;

            $start = max( 1, $paged - 3 );
            $end   = min( $total_pages, $paged + 3 );

            if ( $start > 1 ) {
                echo '<a href="' . esc_url( add_query_arg( 'nl_paged', 1, $page_base ) ) . '" class="moga-db-pagination__btn">1</a>';
                if ( $start > 2 ) { echo '<span class="moga-db-pagination__ellipsis">…</span>'; }
            }
            for ( $i = $start; $i <= $end; $i++ ) :
                printf(
                    '<a href="%s" class="moga-db-pagination__btn%s"%s>%d</a>',
                    esc_url( add_query_arg( 'nl_paged', $i, $page_base ) ),
                    $i === $paged ? ' is-active' : '',
                    $i === $paged ? ' aria-current="page"' : '',
                    $i
                );
            endfor;
            if ( $end < $total_pages ) {
                if ( $end < $total_pages - 1 ) { echo '<span class="moga-db-pagination__ellipsis">…</span>'; }
                echo '<a href="' . esc_url( add_query_arg( 'nl_paged', $total_pages, $page_base ) ) . '" class="moga-db-pagination__btn">' . esc_html( $total_pages ) . '</a>';
            }
            if ( $paged < $total_pages ) :
                echo '<a href="' . esc_url( add_query_arg( 'nl_paged', $paged + 1, $page_base ) ) . '" class="moga-db-pagination__btn" aria-label="' . esc_attr__( 'Next', 'moga-travel' ) . '"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></a>';
            endif;
            ?>
            <span class="moga-db-pagination__info">
                <?php printf(
                    esc_html__( 'Page %1$s of %2$s', 'moga-travel' ),
                    '<strong>' . esc_html( $paged ) . '</strong>',
                    '<strong>' . esc_html( $total_pages ) . '</strong>'
                ); ?>
            </span>
        </nav>
    <?php endif; ?>

</div><?php // .moga-db-notifications-log ?>
