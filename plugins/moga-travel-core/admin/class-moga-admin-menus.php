<?php
/**
 * Admin Menu Registration
 *
 * Registers the top-level Moga admin menu and all submenus.
 * Called from Moga_Admin::init_components().
 *
 * Menu structure:
 *   Moga (top-level)
 *     ├── Dashboard
 *     ├── Properties  → WP CPT list
 *     ├── Tours       → WP CPT list
 *     ├── Buses       → WP CPT list
 *     ├── Bookings
 *     ├── Users
 *     ├── Locations   ← Location Settings wizard + Editor
 *     ├── Reports
 *     └── Settings
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
 * Class Moga_Admin_Menus
 */
class Moga_Admin_Menus {

    /**
     * Hook into WordPress.
     *
     * @since  1.0.0
     * @return void
     */
    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'register_menus' ) );

        // Restrict admin list screens to own posts for vendor roles.
        add_action( 'pre_get_posts', array( __CLASS__, 'filter_own_posts_only' ) );

        // Fix count tabs on CPT list screens for vendor roles.
        add_filter( 'views_edit-moga_tour',     array( __CLASS__, 'filter_own_post_views' ) );
        add_filter( 'views_edit-moga_bus',      array( __CLASS__, 'filter_own_post_views' ) );
        add_filter( 'views_edit-moga_property', array( __CLASS__, 'filter_own_post_views' ) );

        // Add "Edit Tour" / "Edit Property" link to the frontend admin bar.
        // WordPress generates this automatically for built-in post types,
        // but custom capability_type pairs require an explicit hook because
        // the admin bar's automatic edit node checks 'edit_post' against the
        // primitive post capability — which with map_meta_cap=true should
        // resolve correctly, but only when the user also has
        // 'show_toolbar_when_viewing_site' enabled in their profile.
        // This hook adds the node regardless, so vendor accounts always
        // see the edit link on their own posts without needing to touch
        // their WordPress profile settings.
        add_action( 'admin_bar_menu', array( __CLASS__, 'add_edit_cpt_node' ), 80 );
    }

    /**
     * Register all Moga admin menus.
     * Fires on 'admin_menu'.
     *
     * @since  1.0.0
     * @return void
     */
    public static function register_menus() {

        // ================================================================
        // ARCHITECTURE NOTE (Sep 2026 fix)
        // ================================================================
        // The old Moga top-level wrapper menu is removed entirely.
        // It caused two problems:
        //
        //   1. The top-level page required 'manage_options', which blocked
        //      every Tour Organizer and Property Owner from the whole menu
        //      tree, even when each submenu used the correct lower cap.
        //
        //   2. CPTs with show_in_menu=true also generated their own sidebar
        //      entries, so Tours appeared twice in the sidebar.
        //
        // Clean ThemeForest-standard approach:
        //   - CPTs (Tours, Properties, Buses) each own their sidebar entry
        //     via show_in_menu=true in their CPT registration. WordPress
        //     handles these natively and applies the correct capability gate.
        //   - Admin-only pages (Bookings, Locations, Vendors, Reports,
        //     Settings) get their own top-level entries here.
        // ================================================================

        // ---- Moga Bookings (Phase 5) ----
        add_menu_page(
            __( 'Moga Bookings', 'moga-travel-core' ),
            __( 'Bookings', 'moga-travel-core' ),
            'manage_options',
            'moga-bookings',
            array( __CLASS__, 'render_placeholder' ),
            'dashicons-calendar-alt',
            31
        );

        // ---- Moga Locations ----
        add_menu_page(
            __( 'Location Settings', 'moga-travel-core' ),
            __( 'Locations', 'moga-travel-core' ),
            'manage_options',
            'moga-locations',
            array( 'Moga_Admin_Locations', 'render_page' ),
            'dashicons-location',
            32
        );

        // ---- Moga Vendors / User Approvals ----
        add_menu_page(
            __( 'Moga Vendors', 'moga-travel-core' ),
            __( 'Vendors', 'moga-travel-core' ),
            'moga_approve_vendors',
            'moga-users',
            class_exists( 'Moga_Admin_Vendors' )
                ? array( 'Moga_Admin_Vendors', 'render_page' )
                : array( __CLASS__, 'render_placeholder' ),
            'dashicons-groups',
            33
        );

        // ---- Moga Reports (Phase 6) ----
        add_menu_page(
            __( 'Moga Reports', 'moga-travel-core' ),
            __( 'Reports', 'moga-travel-core' ),
            'manage_options',
            'moga-reports',
            array( __CLASS__, 'render_placeholder' ),
            'dashicons-chart-bar',
            34
        );

        // ---- Moga Settings (Phase 6) ----
        add_menu_page(
            __( 'Moga Settings', 'moga-travel-core' ),
            __( 'Moga Settings', 'moga-travel-core' ),
            'manage_options',
            'moga-settings',
            array( 'Moga_Admin_Settings', 'render_page' ),
            'dashicons-admin-settings',
            35
        );
    }


    // ============================================================
    // ADMIN BAR — EDIT LINK FOR MOGA CPTs
    // ============================================================

    /**
     * Add an "Edit Tour" / "Edit Property" / "Edit Bus" node to the
     * WordPress frontend admin bar when the current user is viewing a
     * single Moga CPT post that they have permission to edit.
     *
     * WordPress adds this node automatically for built-in post types,
     * but with custom capability_type pairs and map_meta_cap=true the
     * automatic check can fail unless the user has explicitly enabled
     * "Show Toolbar when viewing site" in their profile. This hook
     * adds the node explicitly so vendor accounts always see the link
     * on their own posts — no profile setting required.
     *
     * Fires at priority 80, after WordPress's own edit-post node (20)
     * but before late additions — so we never duplicate an existing node.
     *
     * @since  1.0.0
     * @param  WP_Admin_Bar $wp_admin_bar Admin bar object.
     * @return void
     */
    public static function add_edit_cpt_node( $wp_admin_bar ) {

        // Only on the frontend — the admin already has its own edit UI.
        if ( is_admin() ) {
            return;
        }

        // Only on singular Moga CPT pages.
        $post_types = array( 'moga_tour', 'moga_property', 'moga_bus', 'moga_destination' );
        if ( ! is_singular( $post_types ) ) {
            return;
        }

        $post = get_queried_object();
        if ( ! $post || ! isset( $post->ID ) ) {
            return;
        }

        // Check the user can actually edit this specific post.
        if ( ! current_user_can( 'edit_post', $post->ID ) ) {
            return;
        }

        $edit_url = get_edit_post_link( $post->ID, 'url' );
        if ( ! $edit_url ) {
            return;
        }

        // Label varies by post type.
        $labels = array(
            'moga_tour'        => __( 'Edit Tour', 'moga-travel-core' ),
            'moga_property'    => __( 'Edit Property', 'moga-travel-core' ),
            'moga_bus'         => __( 'Edit Bus', 'moga-travel-core' ),
            'moga_destination' => __( 'Edit Destination', 'moga-travel-core' ),
        );

        $label = isset( $labels[ $post->post_type ] )
            ? $labels[ $post->post_type ]
            : __( 'Edit', 'moga-travel-core' );

        // Remove the default 'edit' node if WordPress already added one
        // (prevents duplicates for admins who have toolbar enabled).
        $wp_admin_bar->remove_node( 'edit' );

        $wp_admin_bar->add_node( array(
            'id'    => 'moga-edit-post',
            'title' => $label,
            'href'  => $edit_url,
            'meta'  => array(
                'title' => $label,
            ),
        ) );
    }


    // ============================================================
    // OWN-POSTS-ONLY FILTER
    // ============================================================

    /**
     * Restrict the admin post list to the current user's own posts
     * for all Moga CPTs when the user is a vendor role (Tour Organizer
     * or Property Owner) — not an Administrator.
     *
     * This enforces multi-vendor isolation: a Tour Organizer never sees
     * another organizer's tours or buses; a Property Owner never sees
     * another owner's properties.
     *
     * Runs on 'pre_get_posts' — fires before the DB query is built,
     * so it filters the list cleanly without any post-query processing.
     * WordPress's own pagination and counts all work correctly because
     * they're calculated after this filter applies.
     *
     * @since  1.0.0
     * @param  WP_Query $query The current query object (passed by reference).
     * @return void
     */
    public static function filter_own_posts_only( $query ) {

        // Only applies in the admin, on the main query, on list screens.
        if ( ! is_admin() || ! $query->is_main_query() ) {
            return;
        }

        // Administrators are never filtered — they see everything.
        if ( current_user_can( 'manage_options' ) ) {
            return;
        }

        $post_type = $query->get( 'post_type' );

        // Tour Organizer — restrict tours and buses to own posts.
        if (
            in_array( $post_type, array( 'moga_tour', 'moga_bus' ), true )
            && current_user_can( 'edit_moga_tours' )
            && ! current_user_can( 'edit_others_moga_tours' )
        ) {
            $query->set( 'author', get_current_user_id() );
            return;
        }

        // Property Owner — restrict properties to own posts.
        if (
            'moga_property' === $post_type
            && current_user_can( 'edit_moga_propertys' )
            && ! current_user_can( 'edit_others_moga_propertys' )
        ) {
            $query->set( 'author', get_current_user_id() );
            return;
        }
    }


    // ============================================================
    // OWN-POST COUNT TABS FILTER
    // ============================================================

    /**
     * Replace the count tab labels (All / Published / Draft / Trash)
     * on CPT list screens with counts that reflect only the current
     * vendor's own posts — matching what pre_get_posts shows in the list.
     *
     * WordPress calculates these counts via wp_count_posts() which never
     * goes through pre_get_posts, so without this filter a vendor who
     * has zero tours still sees "All (4) | Published (2) | Drafts (2)"
     * — the global totals — even though the list shows "No tours found."
     *
     * Admins are not affected — they see the real global totals.
     *
     * @since  1.0.0
     * @param  array $views Existing view links keyed by status slug.
     * @return array        Replaced view links with corrected counts.
     */
    public static function filter_own_post_views( $views ) {

        // Admins see real global totals — never filter for them.
        if ( current_user_can( 'manage_options' ) ) {
            return $views;
        }

        // Determine which post type we're on from the current screen.
        $screen    = get_current_screen();
        $post_type = $screen ? $screen->post_type : '';

        if ( ! $post_type ) {
            return $views;
        }

        $user_id  = get_current_user_id();
        $statuses = array( 'publish', 'draft', 'pending', 'trash', 'private' );

        // Count only the current user's posts per status.
        $own_counts = array();
        $total      = 0;

        foreach ( $statuses as $status ) {
            $count = (int) ( new WP_Query( array(
                'post_type'      => $post_type,
                'post_status'    => $status,
                'author'         => $user_id,
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'no_found_rows'  => false,
            ) ) )->found_posts;

            $own_counts[ $status ] = $count;
            if ( 'trash' !== $status ) {
                $total += $count;
            }
        }

        // Rebuild the view links with corrected counts.
        // We reuse the existing link markup but replace just the number
        // inside the count badge — preserving the URL and active class.
        foreach ( $views as $status => $link ) {
            $map = array(
                'all'     => $total,
                'publish' => $own_counts['publish']  ?? 0,
                'draft'   => $own_counts['draft']    ?? 0,
                'pending' => $own_counts['pending']  ?? 0,
                'trash'   => $own_counts['trash']    ?? 0,
                'private' => $own_counts['private']  ?? 0,
            );

            $count = $map[ $status ] ?? null;
            if ( null === $count ) {
                continue;
            }

            // Replace the count number in the view link.
            // WordPress renders counts in one of two formats depending on version:
            //   Format A: Post Title <span class="count">(N)</span>
            //   Format B: Post Title (<span class="count">N</span>)
            // We match both to be safe across WordPress versions.
            $new_count = (string) $count;
            $replaced  = false;

            // Format A — parentheses inside the span.
            $result = preg_replace(
                '/<span class="count">\(\d+\)<\/span>/',
                '<span class="count">(' . $new_count . ')</span>',
                $link
            );
            if ( $result && $result !== $link ) {
                $views[ $status ] = $result;
                $replaced = true;
            }

            // Format B — parentheses outside the span.
            if ( ! $replaced ) {
                $result = preg_replace(
                    '/\(<span class="count">\d+<\/span>\)/',
                    '(<span class="count">' . $new_count . '</span>)',
                    $link
                );
                if ( $result && $result !== $link ) {
                    $views[ $status ] = $result;
                }
            }
        }

        return $views;
    }


    // ============================================================
    // PAGE RENDERERS
    // ============================================================

    /**
     * Render the main Moga dashboard page.
     *
     * @since  1.0.0
     * @return void
     */
    public static function render_dashboard() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Moga Booking System', 'moga-travel-core' ); ?></h1>
            <p><?php esc_html_e( 'Welcome to the Moga Booking System dashboard.', 'moga-travel-core' ); ?></p>

            <div class="moga-dashboard-cards" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-top:24px;">

                <?php
                $cards = array(
                    array(
                        'label' => __( 'Properties', 'moga-travel-core' ),
                        'count' => wp_count_posts( 'moga_property' )->publish ?? 0,
                        'url'   => admin_url( 'edit.php?post_type=moga_property' ),
                        'color' => '#0073aa',
                    ),
                    array(
                        'label' => __( 'Tours', 'moga-travel-core' ),
                        'count' => wp_count_posts( 'moga_tour' )->publish ?? 0,
                        'url'   => admin_url( 'edit.php?post_type=moga_tour' ),
                        'color' => '#00a651',
                    ),
                    array(
                        'label' => __( 'Bookings', 'moga-travel-core' ),
                        'count' => 0,
                        'url'   => admin_url( 'admin.php?page=moga-bookings' ),
                        'color' => '#f39c12',
                    ),
                );
                foreach ( $cards as $card ) :
                ?>
                    <div style="background:#fff;border:1px solid #e0e0e0;border-radius:8px;padding:20px;text-align:center;">
                        <div style="font-size:2rem;font-weight:700;color:<?php echo esc_attr( $card['color'] ); ?>">
                            <?php echo esc_html( $card['count'] ); ?>
                        </div>
                        <div style="color:#555;margin-top:4px;">
                            <a href="<?php echo esc_url( $card['url'] ); ?>" style="color:inherit;text-decoration:none;">
                                <?php echo esc_html( $card['label'] ); ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>

            </div>
        </div>
        <?php
    }

    /**
     * Render a "coming soon" placeholder for unbuilt pages.
     *
     * @since  1.0.0
     * @return void
     */
    public static function render_placeholder() {
        $page  = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
        $title = ucwords( str_replace( array( 'moga-', '-' ), array( '', ' ' ), $page ) );
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( $title ); ?></h1>
            <div style="background:#fff;border:1px solid #e0e0e0;border-radius:8px;padding:40px;margin-top:16px;text-align:center;color:#888;">
                <p style="font-size:1.1rem;">
                    <?php esc_html_e( 'This section is under development and will be available in a future update.', 'moga-travel-core' ); ?>
                </p>
            </div>
        </div>
        <?php
    }


    // ============================================================
    // HELPER
    // ============================================================

    /**
     * Return the base64-encoded SVG for the Moga admin menu icon.
     * Using a map-pin style icon in the WordPress admin grey colour.
     *
     * @since  1.0.0
     * @return string data:image/svg+xml;base64,... string.
     */
    private static function get_menu_icon() {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#a7aaad" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'
            . '<path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/>'
            . '<circle cx="12" cy="9" r="2.5"/>'
            . '</svg>';
        return 'data:image/svg+xml;base64,' . base64_encode( $svg );
    }
}