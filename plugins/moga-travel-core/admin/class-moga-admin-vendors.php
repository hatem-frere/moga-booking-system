<?php
/**
 * Vendor Approval Admin Screen
 *
 * Path: plugins/moga-travel-core/admin/class-moga-admin-vendors.php
 *
 * Replaces the "Users" placeholder in Moga_Admin_Menus with a real
 * screen: lists every Tour Organizer / Property Owner account,
 * shows their entity type, uploaded verification documents, and
 * current vendor_status, with Approve / Reject actions plus manual
 * add/remove of either vendor role (the admin-side override for
 * the self-service "also become a ..." flow in the account
 * shortcode).
 *
 * @package    MogaTravelCore
 * @subpackage MogaTravelCore/admin
 * @author     Hatem Frere
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Moga_Admin_Vendors
 */
class Moga_Admin_Vendors {

    /**
     * Hook into WordPress. Called from Moga_Core::boot_admin().
     *
     * The "Users" submenu itself is registered in Moga_Admin_Menus,
     * pointed directly at self::render_page() (see that file) —
     * nothing to register here for the menu itself. This init()
     * only wires up the form-submission handler.
     *
     * @since  1.0.0
     * @return void
     */
    public static function init() {
        add_action( 'admin_post_moga_vendor_action', array( __CLASS__, 'handle_action' ) );

        // Surface vendor status directly on the native Users list table
        // (wp-admin/users.php) — the first place an admin naturally
        // looks — in addition to the dedicated Moga > Users screen.
        add_filter( 'manage_users_columns', array( __CLASS__, 'add_status_column' ) );
        add_filter( 'manage_users_custom_column', array( __CLASS__, 'render_status_column' ), 10, 3 );
        add_filter( 'user_row_actions', array( __CLASS__, 'add_row_actions' ), 10, 2 );
    }

    /**
     * Add a "Vendor Status" column to wp-admin/users.php.
     *
     * @since  1.0.0
     * @param  array $columns Existing columns.
     * @return array
     */
    public static function add_status_column( $columns ) {
        $columns['moga_vendor_status'] = __( 'Vendor Status', 'moga-travel-core' );
        return $columns;
    }

    /**
     * Render the "Vendor Status" column content for one user row.
     *
     * @since  1.0.0
     * @param  string $output      Existing column output (empty for custom columns).
     * @param  string $column_name Column being rendered.
     * @param  int    $user_id     User ID for this row.
     * @return string
     */
    public static function render_status_column( $output, $column_name, $user_id ) {

        if ( 'moga_vendor_status' !== $column_name ) {
            return $output;
        }

        $user = get_userdata( $user_id );
        $is_vendor = $user && array_intersect( array( 'moga_tour_organizer', 'moga_property_owner' ), (array) $user->roles );

        if ( ! $is_vendor ) {
            return '—';
        }

        $status = get_user_meta( $user_id, 'moga_vendor_status', true ) ?: 'approved';
        $colors = array( 'pending' => '#dba617', 'approved' => '#00a651', 'rejected' => '#d63638' );
        $color  = isset( $colors[ $status ] ) ? $colors[ $status ] : '#555';

        return '<span style="color:' . esc_attr( $color ) . ';font-weight:600;text-transform:capitalize;">'
            . esc_html( $status ) . '</span>';
    }

    /**
     * Add quick Approve/Reject row actions on wp-admin/users.php,
     * so an admin doesn't have to leave the screen they're already on.
     * Links straight to the same admin-post.php handler used by the
     * full Moga > Users screen.
     *
     * @since  1.0.0
     * @param  array   $actions Existing row actions.
     * @param  WP_User $user    User for this row.
     * @return array
     */
    public static function add_row_actions( $actions, $user ) {

        if ( ! current_user_can( 'moga_approve_vendors' ) ) {
            return $actions;
        }

        $is_vendor = array_intersect( array( 'moga_tour_organizer', 'moga_property_owner' ), (array) $user->roles );

        if ( ! $is_vendor ) {
            return $actions;
        }

        $status = get_user_meta( $user->ID, 'moga_vendor_status', true ) ?: 'approved';

        if ( 'approved' !== $status ) {
            $actions['moga_approve'] = self::row_action_link( $user->ID, 'approve', __( 'Approve', 'moga-travel-core' ) );
        }
        if ( 'rejected' !== $status ) {
            $actions['moga_reject'] = self::row_action_link( $user->ID, 'reject', __( 'Reject', 'moga-travel-core' ) );
        }

        return $actions;
    }

    /**
     * Build a nonce-protected admin-post.php link for a row action.
     *
     * @since  1.0.0
     * @param  int    $user_id Vendor user ID.
     * @param  string $action  Action key.
     * @param  string $label   Link label.
     * @return string HTML <a> tag.
     */
    private static function row_action_link( $user_id, $action, $label ) {

        $url = wp_nonce_url(
            add_query_arg( array(
                'action'        => 'moga_vendor_action',
                'user_id'       => $user_id,
                'vendor_action' => $action,
            ), admin_url( 'admin-post.php' ) ),
            'moga_vendor_action_' . $user_id,
            'moga_vendor_nonce'
        );

        return '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
    }


    // ============================================================
    // RENDER
    // ============================================================

    /**
     * Render the vendor list/approval screen.
     *
     * @since  1.0.0
     * @return void
     */
    public static function render_page() {

        if ( ! current_user_can( 'moga_approve_vendors' ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'moga-travel-core' ) );
        }

        $vendors = get_users( array(
            'role__in' => array( 'moga_tour_organizer', 'moga_property_owner' ),
            'orderby'  => 'registered',
            'order'    => 'DESC',
        ) );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Vendor Accounts', 'moga-travel-core' ); ?></h1>

            <?php if ( isset( $_GET['moga_notice'] ) ) : ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Vendor updated.', 'moga-travel-core' ); ?></p></div>
            <?php endif; ?>

            <table class="wp-list-table widefat fixed striped" style="margin-top:16px;">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Vendor', 'moga-travel-core' ); ?></th>
                        <th><?php esc_html_e( 'Roles', 'moga-travel-core' ); ?></th>
                        <th><?php esc_html_e( 'Type', 'moga-travel-core' ); ?></th>
                        <th><?php esc_html_e( 'Documents', 'moga-travel-core' ); ?></th>
                        <th><?php esc_html_e( 'Email', 'moga-travel-core' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'moga-travel-core' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'moga-travel-core' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $vendors ) ) : ?>
                        <tr><td colspan="7"><?php esc_html_e( 'No vendor accounts yet.', 'moga-travel-core' ); ?></td></tr>
                    <?php endif; ?>

                    <?php foreach ( $vendors as $vendor ) :
                        $status       = get_user_meta( $vendor->ID, 'moga_vendor_status', true ) ?: 'approved'; // legacy accounts with no status = treat as approved.
                        $entity_type  = get_user_meta( $vendor->ID, 'moga_entity_type', true ) ?: 'individual';
                        $company_name = get_user_meta( $vendor->ID, 'moga_company_name', true );
                        $docs_json    = get_user_meta( $vendor->ID, 'moga_verification_docs', true );
                        $doc_ids      = $docs_json ? json_decode( $docs_json, true ) : array();
                        $doc_ids      = is_array( $doc_ids ) ? $doc_ids : array();
                        $has_organizer = in_array( 'moga_tour_organizer', $vendor->roles, true );
                        $has_owner     = in_array( 'moga_property_owner', $vendor->roles, true );

                        $status_colors = array(
                            'pending'  => '#dba617',
                            'approved' => '#00a651',
                            'rejected' => '#d63638',
                        );
                    ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html( $vendor->display_name ); ?></strong><br>
                                <a href="mailto:<?php echo esc_attr( $vendor->user_email ); ?>"><?php echo esc_html( $vendor->user_email ); ?></a><br>
                                <?php echo esc_html( get_user_meta( $vendor->ID, 'moga_contact_phone', true ) ); ?>
                            </td>
                            <td>
                                <?php if ( $has_organizer ) : ?><span class="moga-vendor-role-badge">🧭 <?php esc_html_e( 'Tour Organizer', 'moga-travel-core' ); ?></span><br><?php endif; ?>
                                <?php if ( $has_owner ) : ?><span class="moga-vendor-role-badge">🏠 <?php esc_html_e( 'Property Owner', 'moga-travel-core' ); ?></span><?php endif; ?>
                            </td>
                            <td>
                                <?php if ( 'company' === $entity_type ) : ?>
                                    🏢 <?php echo esc_html( $company_name ?: __( '(company)', 'moga-travel-core' ) ); ?>
                                <?php else : ?>
                                    👤 <?php esc_html_e( 'Individual', 'moga-travel-core' ); ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ( empty( $doc_ids ) ) : ?>
                                    <em><?php esc_html_e( 'None', 'moga-travel-core' ); ?></em>
                                <?php else : ?>
                                    <?php foreach ( $doc_ids as $doc_id ) :
                                        $url = wp_get_attachment_url( $doc_id );
                                        if ( ! $url ) continue;
                                    ?>
                                        <a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer">📄 <?php echo esc_html( basename( $url ) ); ?></a><br>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ( '1' === get_user_meta( $vendor->ID, 'moga_email_verified', true ) ) : ?>
                                    <span style="color:#00a651;">✓ <?php esc_html_e( 'Verified', 'moga-travel-core' ); ?></span>
                                <?php else : ?>
                                    <span style="color:#dba617;">⚠ <?php esc_html_e( 'Unverified', 'moga-travel-core' ); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="color:<?php echo esc_attr( $status_colors[ $status ] ?? '#555' ); ?>;font-weight:600;text-transform:capitalize;">
                                    <?php echo esc_html( $status ); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ( 'approved' !== $status ) : ?>
                                    <?php self::render_action_button( $vendor->ID, 'approve', __( 'Approve', 'moga-travel-core' ) ); ?>
                                <?php endif; ?>
                                <?php if ( 'rejected' !== $status ) : ?>
                                    <?php self::render_action_button( $vendor->ID, 'reject', __( 'Reject', 'moga-travel-core' ) ); ?>
                                <?php endif; ?>
                                <br>
                                <?php if ( ! $has_organizer ) : ?>
                                    <?php self::render_action_button( $vendor->ID, 'add_organizer', __( '+ Tour Organizer role', 'moga-travel-core' ) ); ?>
                                <?php else : ?>
                                    <?php self::render_action_button( $vendor->ID, 'remove_organizer', __( '− Tour Organizer role', 'moga-travel-core' ) ); ?>
                                <?php endif; ?>
                                <?php if ( ! $has_owner ) : ?>
                                    <?php self::render_action_button( $vendor->ID, 'add_owner', __( '+ Property Owner role', 'moga-travel-core' ) ); ?>
                                <?php else : ?>
                                    <?php self::render_action_button( $vendor->ID, 'remove_owner', __( '− Property Owner role', 'moga-travel-core' ) ); ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Render a single inline form/button for one row action.
     *
     * @since  1.0.0
     * @param  int    $user_id Vendor user ID.
     * @param  string $action  Action key — see handle_action().
     * @param  string $label   Button label.
     * @return void
     */
    private static function render_action_button( $user_id, $action, $label ) {
        ?>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;margin:2px 0;">
            <?php wp_nonce_field( 'moga_vendor_action_' . $user_id, 'moga_vendor_nonce' ); ?>
            <input type="hidden" name="action" value="moga_vendor_action">
            <input type="hidden" name="user_id" value="<?php echo esc_attr( $user_id ); ?>">
            <input type="hidden" name="vendor_action" value="<?php echo esc_attr( $action ); ?>">
            <button type="submit" class="button button-small"><?php echo esc_html( $label ); ?></button>
        </form>
        <?php
    }


    // ============================================================
    // ACTIONS
    // ============================================================

    /**
     * Handle Approve / Reject / add-role / remove-role form posts.
     *
     * @since  1.0.0
     * @return void
     */
    public static function handle_action() {

        if ( ! current_user_can( 'moga_approve_vendors' ) ) {
            wp_die( esc_html__( 'You do not have permission to do that.', 'moga-travel-core' ) );
        }

        $user_id = isset( $_REQUEST['user_id'] ) ? absint( $_REQUEST['user_id'] ) : 0;

        if ( ! $user_id || ! isset( $_REQUEST['moga_vendor_nonce'] )
            || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['moga_vendor_nonce'] ) ), 'moga_vendor_action_' . $user_id )
        ) {
            wp_die( esc_html__( 'Security check failed.', 'moga-travel-core' ) );
        }

        $action = isset( $_REQUEST['vendor_action'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['vendor_action'] ) ) : '';
        $user   = get_userdata( $user_id );

        if ( ! $user ) {
            wp_die( esc_html__( 'Vendor not found.', 'moga-travel-core' ) );
        }

        switch ( $action ) {

            case 'approve':
                update_user_meta( $user_id, 'moga_vendor_status', 'approved' );
                // Publish anything that was held back by the approval
                // gate while this vendor was pending — see Moga_Roles.
                self::publish_held_listings( $user_id );
                break;

            case 'reject':
                update_user_meta( $user_id, 'moga_vendor_status', 'rejected' );
                break;

            case 'add_organizer':
                $user->add_role( 'moga_tour_organizer' );
                break;

            case 'remove_organizer':
                $user->remove_role( 'moga_tour_organizer' );
                break;

            case 'add_owner':
                $user->add_role( 'moga_property_owner' );
                break;

            case 'remove_owner':
                $user->remove_role( 'moga_property_owner' );
                break;
        }

        wp_safe_redirect( add_query_arg( 'moga_notice', '1', admin_url( 'admin.php?page=moga-users' ) ) );
        exit;
    }

    /**
     * When a vendor is approved, publish any of their tours/properties
     * that the approval gate in Moga_Roles had forced to 'pending'
     * status while they were unapproved.
     *
     * @since  1.0.0
     * @param  int $user_id Vendor user ID.
     * @return void
     */
    private static function publish_held_listings( $user_id ) {

        $held = get_posts( array(
            'post_type'      => array( 'moga_tour', 'moga_property' ),
            'post_status'    => 'pending',
            'author'         => $user_id,
            'posts_per_page' => -1,
            'meta_key'       => '_moga_held_for_approval',
            'meta_value'     => '1',
        ) );

        foreach ( $held as $post ) {
            wp_update_post( array(
                'ID'          => $post->ID,
                'post_status' => 'publish',
            ) );
            delete_post_meta( $post->ID, '_moga_held_for_approval' );
        }
    }
}