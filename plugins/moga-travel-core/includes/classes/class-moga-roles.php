<?php
/**
 * User Roles & Capabilities
 *
 * Registers the Moga Tour Organizer, Property Owner, and Guest
 * roles, and keeps their capabilities in sync automatically.
 *
 * IMPORTANT — why this class exists instead of a one-time
 * activation routine: WordPress's add_role() silently does
 * nothing if the role already exists, so a plugin that only
 * defines roles on activation can never fix or extend an
 * already-active site's roles without the user deactivating
 * and reactivating the plugin. This class checks a stored
 * version number on every 'init' and re-creates the roles
 * whenever ROLES_VERSION is bumped, so role/capability changes
 * ship like any other code change — no reactivation required.
 *
 * @package    MogaTravelCore
 * @subpackage MogaTravelCore/includes/classes
 * @author     Hatem Frere
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Moga_Roles
 */
class Moga_Roles {

    /**
     * Bump this whenever role capabilities change.
     * Triggers an automatic re-sync on the next page load.
     *
     * @since 1.0.0
     */
    const ROLES_VERSION = '2.0.0';

    /**
     * The option name storing the currently-applied roles version.
     *
     * @since 1.0.0
     */
    const VERSION_OPTION = 'moga_roles_version';

    /** @since 1.0.0 */
    public function __construct() {
        $this->maybe_sync_roles();
        add_action( 'admin_notices', array( $this, 'maybe_show_legacy_owner_notice' ) );
        add_filter( 'wp_insert_post_data', array( $this, 'gate_unapproved_vendor_posts' ), 10, 2 );
        add_action( 'save_post', array( $this, 'flag_gated_post' ), 20, 2 );
    }


    // ============================================================
    // VENDOR APPROVAL GATE
    // ============================================================

    /**
     * Prevents a tour/property from a not-yet-approved vendor from
     * ever actually publishing, regardless of what button they
     * clicked in the editor. They can still save drafts and submit
     * for review — this only intercepts the 'publish' status itself
     * and quietly downgrades it to 'pending' (WordPress's built-in
     * "Pending Review" status) until Moga_Admin_Vendors approves
     * their account.
     *
     * Runs on 'wp_insert_post_data', which fires before the post
     * row is written — this is a hard gate, not a cleanup-after-save
     * race condition.
     *
     * @since  1.0.0
     * @param  array $data    Sanitized post data about to be saved.
     * @param  array $postarr Raw $_POST-derived post array.
     * @return array
     */
    public function gate_unapproved_vendor_posts( $data, $postarr ) {

        if ( ! in_array( $data['post_type'], array( 'moga_tour', 'moga_property' ), true ) ) {
            return $data;
        }
        if ( 'publish' !== $data['post_status'] && 'future' !== $data['post_status'] ) {
            return $data;
        }

        $author_id = isset( $postarr['post_author'] ) ? (int) $postarr['post_author'] : (int) $data['post_author'];

        if ( self::is_vendor_approved( $author_id ) ) {
            return $data;
        }

        $data['post_status'] = 'pending';
        return $data;
    }

    /**
     * Whether a given user is allowed to publish live listings.
     * Non-vendor accounts (e.g. an Administrator authoring a post
     * directly) are never gated — this only applies to accounts
     * holding a Moga vendor role.
     *
     * Two independent conditions must both be true: the account must
     * be admin-approved (verifies the vendor is a legitimate
     * business/individual) AND the email address must be confirmed
     * (verifies they're reachable — bookings, disputes, and payout
     * issues all depend on being able to actually contact them).
     * Either gap alone is enough to hold their listings back.
     *
     * @since  1.0.0
     * @param  int $user_id User ID to check.
     * @return bool
     */
    public static function is_vendor_approved( $user_id ) {

        $user = get_userdata( $user_id );

        if ( ! $user ) {
            return false;
        }

        $is_vendor = array_intersect( array( 'moga_tour_organizer', 'moga_property_owner' ), (array) $user->roles );

        if ( empty( $is_vendor ) ) {
            return true; // Not a vendor account — gate doesn't apply.
        }

        $admin_approved  = 'approved' === get_user_meta( $user_id, 'moga_vendor_status', true );
        $email_confirmed = '1' === get_user_meta( $user_id, 'moga_email_verified', true );

        return $admin_approved && $email_confirmed;
    }


    /**
     * Companion to gate_unapproved_vendor_posts() — 'wp_insert_post_data'
     * can only change the data about to be saved, it can't write post
     * meta (the post doesn't have an ID yet at that point). This runs
     * after the save and marks any post that ended up 'pending' because
     * of the gate (not because the author genuinely chose "Submit for
     * Review"), so Moga_Admin_Vendors::publish_held_listings() can find
     * exactly the right posts to auto-publish on approval — and only
     * those, not every pending post an author submitted deliberately.
     *
     * @since  1.0.0
     * @param  int     $post_id Post ID.
     * @param  WP_Post $post    Post object.
     * @return void
     */
    public function flag_gated_post( $post_id, $post ) {

        if ( ! in_array( $post->post_type, array( 'moga_tour', 'moga_property' ), true ) ) {
            return;
        }
        if ( 'pending' !== $post->post_status ) {
            delete_post_meta( $post_id, '_moga_held_for_approval' );
            return;
        }
        if ( self::is_vendor_approved( $post->post_author ) ) {
            return; // Genuinely a deliberate "Submit for Review", not the gate.
        }

        update_post_meta( $post_id, '_moga_held_for_approval', '1' );
    }


    // ============================================================
    // SYNC
    // ============================================================

    /**
     * Re-create roles if the stored version doesn't match
     * ROLES_VERSION. Cheap no-op on every request once synced —
     * a single get_option() call.
     *
     * @since  1.0.0
     * @return void
     */
    private function maybe_sync_roles() {

        if ( get_option( self::VERSION_OPTION ) === self::ROLES_VERSION ) {
            return;
        }

        self::setup_roles();
        update_option( self::VERSION_OPTION, self::ROLES_VERSION );
    }


    // ============================================================
    // ROLE DEFINITIONS
    // ============================================================

    /**
     * Define and register all Moga roles and capabilities.
     * Safe to call repeatedly — removes and re-adds each Moga
     * role so capability changes always take effect, without
     * touching WordPress's own built-in roles.
     *
     * @since  1.0.0
     * @return void
     */
    public static function setup_roles() {

        // Remove and re-add so capability changes actually apply
        // to sites that already have these roles from a prior version.
        remove_role( 'moga_tour_organizer' );
        remove_role( 'moga_property_owner' );
        remove_role( 'moga_guest' );

        // ------------------------------------------------------------
        // Tour Organizer
        // Capabilities map to the Tour CPT's distinct capability_type
        // (array( 'moga_tour', 'moga_tours' ) — see class-moga-cpt-tour.php),
        // so this role can manage tours without touching properties
        // or core WordPress Posts.
        // ------------------------------------------------------------
        add_role(
            'moga_tour_organizer',
            __( 'Tour Organizer', 'moga-travel-core' ),
            array(
                'read'                       => true,
                'edit_moga_tour'             => true,
                'edit_moga_tours'            => true,
                'edit_published_moga_tours'  => true,
                'delete_moga_tour'           => true,
                'delete_moga_tours'          => true,
                'publish_moga_tours'         => true,
                'upload_files'               => true, // gallery/video/organizer photo/docs.
                'moga_manage_buses'          => true, // tours may use buses for group transport.
                'moga_view_bookings'         => true,
                'moga_manage_availability'   => true,
                'moga_view_earnings'         => true,
            )
        );

        // ------------------------------------------------------------
        // Property Owner
        // Same pattern, will map to the Property CPT's own
        // capability_type once class-moga-cpt-property.php gets the
        // matching update (see note in class-moga-roles.php header).
        // ------------------------------------------------------------
        add_role(
            'moga_property_owner',
            __( 'Property Owner', 'moga-travel-core' ),
            array(
                'read'                           => true,
                'edit_moga_property'             => true,
                'edit_moga_propertys'            => true,
                'edit_published_moga_propertys'  => true,
                'delete_moga_property'           => true,
                'delete_moga_propertys'          => true,
                'publish_moga_propertys'         => true,
                'upload_files'                   => true,
                'moga_view_bookings'             => true,
                'moga_manage_availability'       => true,
                'moga_view_earnings'             => true,
            )
        );

        // ------------------------------------------------------------
        // Guest — unchanged from the original activator definition.
        // ------------------------------------------------------------
        add_role(
            'moga_guest',
            __( 'Guest', 'moga-travel-core' ),
            array(
                'read'                 => true,
                'moga_make_booking'    => true,
                'moga_view_bookings'   => true,
                'moga_write_reviews'   => true,
                'moga_manage_wishlist' => true,
            )
        );

        self::sync_admin_capabilities();
    }

    /**
     * Ensure Administrator always has every Moga capability,
     * across both vendor roles plus the shared/system ones.
     *
     * @since  1.0.0
     * @return void
     */
    private static function sync_admin_capabilities() {

        $admin = get_role( 'administrator' );

        if ( ! $admin ) {
            return;
        }

        $caps = array(
            // Tours.
            'edit_moga_tour', 'edit_moga_tours', 'edit_others_moga_tours',
            'edit_published_moga_tours', 'publish_moga_tours',
            'delete_moga_tour', 'delete_moga_tours', 'delete_others_moga_tours',
            'delete_published_moga_tours', 'delete_private_moga_tours',
            'edit_private_moga_tours', 'read_private_moga_tours',
            // Properties.
            'edit_moga_property', 'edit_moga_propertys', 'edit_others_moga_propertys',
            'edit_published_moga_propertys', 'publish_moga_propertys',
            'delete_moga_property', 'delete_moga_propertys', 'delete_others_moga_propertys',
            'delete_published_moga_propertys', 'delete_private_moga_propertys',
            'edit_private_moga_propertys', 'read_private_moga_propertys',
            // Shared / system.
            'moga_manage_buses', 'moga_view_bookings', 'moga_manage_bookings',
            'moga_manage_availability', 'moga_view_earnings', 'moga_manage_commissions',
            'moga_manage_settings', 'moga_make_booking', 'moga_write_reviews',
            'moga_manage_wishlist', 'moga_approve_vendors',
        );

        foreach ( $caps as $cap ) {
            $admin->add_cap( $cap );
        }
    }


    // ============================================================
    // LEGACY ROLE MIGRATION NOTICE
    // ============================================================

    /**
     * The old bundled 'moga_owner' role (properties + tours + buses
     * all in one) is superseded by the two-role split. We can't
     * safely auto-migrate existing 'moga_owner' users — we don't
     * know whether each one should become a Tour Organizer, a
     * Property Owner, or both — so surface an admin notice instead
     * of silently leaving them stuck with a role that has no
     * working capabilities against either CPT going forward.
     *
     * @since  1.0.0
     * @return void
     */
    public function maybe_show_legacy_owner_notice() {

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $legacy_users = get_users( array(
            'role'   => 'moga_owner',
            'fields' => 'ID',
        ) );

        if ( empty( $legacy_users ) ) {
            return;
        }

        printf(
            '<div class="notice notice-warning"><p>%s</p></div>',
            sprintf(
                /* translators: %d: number of users, %s: Users admin URL */
                esc_html( _n(
                    'Moga Booking System: %1$d user still has the old "Property Owner" (moga_owner) role, which has been replaced by separate Tour Organizer and Property Owner roles. Please reassign this user manually in %2$s.',
                    'Moga Booking System: %1$d users still have the old "Property Owner" (moga_owner) role, which has been replaced by separate Tour Organizer and Property Owner roles. Please reassign these users manually in %2$s.',
                    count( $legacy_users ),
                    'moga-travel-core'
                ) ),
                count( $legacy_users ),
                '<a href="' . esc_url( admin_url( 'users.php' ) ) . '">' . esc_html__( 'Users', 'moga-travel-core' ) . '</a>'
            )
        );
    }
}