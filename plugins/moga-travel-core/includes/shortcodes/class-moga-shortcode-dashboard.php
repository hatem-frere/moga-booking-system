<?php

/**
 * Dashboard Shortcode
 *
 * Registers the [moga_dashboard] shortcode (unused in the current
 * implementation — the dashboard runs via template-dashboard.php
 * directly). Also registers the AJAX action for JS tab switching
 * so subsequent tab loads don't require a full page reload.
 *
 * AJAX flow:
 *   1. User clicks a sidebar nav link.
 *   2. dashboard.js intercepts the click, prevents default.
 *   3. JS calls moga_dashboard_tab AJAX action with the tab key.
 *   4. This class renders the matching template part and returns HTML.
 *   5. JS injects the HTML into #moga-db-content, updates the URL
 *      via history.pushState, and updates the active nav link.
 *
 * @package    MogaTravel
 * @subpackage MogaTravel/includes/shortcodes
 * @since      1.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Class Moga_Shortcode_Dashboard
 */
class Moga_Shortcode_Dashboard
{

    /**
     * Register shortcode and AJAX hooks.
     *
     * @since  1.0.0
     * @return void
     */
    public static function init()
    {
        add_shortcode('moga_dashboard', array(__CLASS__, 'render_shortcode'));

        // AJAX tab switching — logged-in users only.
        add_action('wp_ajax_moga_dashboard_tab', array(__CLASS__, 'ajax_tab'));
    }

    /**
     * Render the [moga_dashboard] shortcode.
     * Outputs a link to the dashboard page since the full dashboard
     * is handled by template-dashboard.php.
     *
     * @since  1.0.0
     * @return string
     */
    public static function render_shortcode()
    {
        $dashboard_url = get_permalink(get_option('moga_page_dashboard'));
        if (! $dashboard_url) {
            return '';
        }
        if (! is_user_logged_in()) {
            return '<a href="' . esc_url(wp_login_url($dashboard_url)) . '" class="moga-btn moga-btn--primary">'
                . esc_html__('Sign In to Dashboard', 'moga-travel')
                . '</a>';
        }
        return '<a href="' . esc_url($dashboard_url) . '" class="moga-btn moga-btn--primary">'
            . esc_html__('Go to Dashboard', 'moga-travel')
            . '</a>';
    }

    /**
     * AJAX handler for tab switching.
     *
     * Verifies nonce, validates the requested tab against the user's
     * allowed tabs, then renders and returns the matching template part.
     *
     * @since  1.0.0
     * @return void Sends JSON response.
     */
    public static function ajax_tab()
    {
        // Verify nonce.
        if (
            ! isset($_POST['nonce'])
            || ! wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['nonce'])),
                'moga_dashboard_nonce'
            )
        ) {
            wp_send_json_error(array('message' => 'Invalid nonce.'), 403);
        }

        if (! is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Not logged in.'), 401);
        }

        $tab     = isset($_POST['tab']) ? sanitize_key($_POST['tab']) : 'overview';
        $user    = wp_get_current_user();
        $user_id = (int) $user->ID;

        $is_admin  = current_user_can('administrator');
        $is_owner  = current_user_can('edit_moga_properties');
        $is_org    = current_user_can('edit_moga_tours');
        $is_vendor = $is_owner || $is_org;

        // Map tab to template folder.
        $admin_tabs  = array('all-properties','all-tours','all-bookings','vendors','commissions','reports','notifications-log');
        $vendor_tabs = array('properties','tours','buses','vendor-bookings','calendar','seat-map','earnings','payout-settings','reviews-received');

        if (in_array($tab, $admin_tabs, true) && ! $is_admin) {
            wp_send_json_error(array('message' => 'Permission denied.'), 403);
        }

        if (in_array($tab, $vendor_tabs, true) && ! $is_vendor && ! $is_admin) {
            wp_send_json_error(array('message' => 'Permission denied.'), 403);
        }

        // Determine template path.
        if (in_array($tab, $admin_tabs, true)) {
            $tpl = 'template-parts/dashboard/admin/' . $tab;
        } elseif (in_array($tab, $vendor_tabs, true)) {
            $tpl = 'template-parts/dashboard/vendor/' . $tab;
        } else {
            $tpl = 'template-parts/dashboard/client/' . $tab;
        }

        // Buffer the template output.
        ob_start();
        get_template_part($tpl, null, array(
            'user'       => $user,
            'user_id'    => $user_id,
            'active_tab' => $tab,
            'is_admin'   => $is_admin,
            'is_vendor'  => $is_vendor,
            'is_owner'   => $is_owner,
            'is_org'     => $is_org,
        ));
        $html = ob_get_clean();

        wp_send_json_success(array(
            'html' => $html,
            'tab'  => $tab,
        ));
    }
}
