<?php

/**
 * Account Shortcode — Registration, Login, Profile.
 *
 * Path: plugins/moga-travel-core/includes/shortcodes/class-moga-shortcode-account.php
 *
 * Handles the [moga_account] shortcode used on the "My Account" page
 * (created automatically by Moga_Activator::create_pages()).
 *
 * Logged-out visitors see a Login / Register toggle. Registration
 * offers three paths — Tour Organizer, Property Owner, Regular User —
 * with an Individual/Company toggle for the two vendor paths that
 * reveals company name + verification document upload fields.
 *
 * Logged-in users see a profile editor pre-filled from their account
 * meta, a vendor status banner if they're a pending/rejected vendor,
 * and — if they already hold exactly one vendor role — a self-service
 * "also become a ..." button that adds the second role without a
 * fresh admin review (re-uses their existing verification).
 *
 * User meta keys used throughout (also read by Moga_Roles and the
 * front-end organizer card fallback in single-moga_tour.php):
 *   moga_entity_type        'individual' | 'company'
 *   moga_company_name       string, company path only
 *   moga_profile_photo      attachment ID — personal photo or logo
 *   moga_contact_phone      string
 *   moga_contact_whatsapp   string
 *   moga_contact_email      string (separate from the WP account email
 *                            by design — a vendor may want a different
 *                            public contact address)
 *   moga_verification_docs  JSON array of attachment IDs, company only
 *   moga_vendor_status      'pending' | 'approved' | 'rejected'
 *
 * @package    MogaTravelCore
 * @subpackage MogaTravelCore/includes/shortcodes
 * @author     Hatem Frere
 * @since      1.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Class Moga_Shortcode_Account
 */
class Moga_Shortcode_Account
{

    /**
     * Roles considered "vendor" roles for status/approval purposes.
     *
     * @since 1.0.0
     */
    const VENDOR_ROLES = array('moga_tour_organizer', 'moga_property_owner');

    /**
     * Register the shortcode.
     *
     * @since  1.0.0
     * @return void
     */
    public function register()
    {
        add_shortcode('moga_account', array($this, 'render'));
    }


    // ============================================================
    // RENDER ENTRY POINT
    // ============================================================

    /**
     * Shortcode callback.
     *
     * @since  1.0.0
     * @return string
     */
    public function render()
    {

        ob_start();

        // Email verification link click — works whether the person is
        // currently logged in or not (they may click it from a
        // different browser/device than the one they registered on).
        $verify_notice = null;
        if (isset($_GET['moga_verify'], $_GET['uid'])) {
            $verify_notice = $this->handle_email_verification(
                absint($_GET['uid']),
                sanitize_text_field(wp_unslash($_GET['moga_verify']))
            );
        }

        if ($verify_notice) {
            printf(
                '<div class="moga-account__notice moga-account__notice--%s" style="max-width:520px;margin:0 auto 16px;">%s</div>',
                esc_attr($verify_notice['type']),
                esc_html($verify_notice['message'])
            );
        }

        if (is_user_logged_in()) {
            $this->render_profile();
        } else {
            $this->render_auth();
        }

        return ob_get_clean();
    }

    /**
     * Verify an email confirmation link.
     *
     * @since  1.0.0
     * @param  int    $user_id User ID from the link.
     * @param  string $token   Token from the link.
     * @return array{type:string,message:string}
     */
    private function handle_email_verification($user_id, $token)
    {

        $stored_token = get_user_meta($user_id, 'moga_email_verify_token', true);

        if (! $user_id || ! $token || ! $stored_token || ! hash_equals($stored_token, $token)) {
            return array(
                'type'    => 'error',
                'message' => __('This verification link is invalid or has already been used.', 'moga-travel'),
            );
        }

        update_user_meta($user_id, 'moga_email_verified', '1');
        delete_user_meta($user_id, 'moga_email_verify_token');

        return array(
            'type'    => 'success',
            'message' => __('Your email address has been verified. Thank you!', 'moga-travel'),
        );
    }

    /**
     * Send the "confirm your email" message after registration.
     *
     * @since  1.0.0
     * @param  int    $user_id User ID.
     * @param  string $email   Address to send to.
     * @param  string $name    Display name for the greeting.
     * @return void
     */
    private function send_verification_email($user_id, $email, $name)
    {

        $token = wp_generate_password(32, false);
        update_user_meta($user_id, 'moga_email_verify_token', $token);
        update_user_meta($user_id, 'moga_email_verified', '0');

        $verify_url = add_query_arg(array(
            'moga_verify' => $token,
            'uid'         => $user_id,
        ), moga_account_url());

        $subject = sprintf(
            /* translators: %s: site name */
            __('Confirm your email address — %s', 'moga-travel'),
            get_bloginfo('name')
        );

        $message = sprintf(
            /* translators: 1: first name, 2: verification link, 3: site name */
            __("Hi %1\$s,\n\nPlease confirm your email address by clicking the link below:\n\n%2\$s\n\nIf you didn't create this account, you can ignore this email.\n\n— %3\$s", 'moga-travel'),
            $name,
            $verify_url,
            get_bloginfo('name')
        );

        wp_mail($email, $subject, $message);
    }


    // ============================================================
    // LOGGED-OUT: LOGIN + REGISTER
    // ============================================================

    /**
     * Render the logged-out login/register experience.
     *
     * @since  1.0.0
     * @return void
     */
    private function render_auth()
    {

        $login_error    = null;
        $register_error = null;
        $active_tab     = isset($_GET['tab']) && 'register' === $_GET['tab'] ? 'register' : 'login';

        // 'register_as' drives which registration form is rendered.
        // This is the mechanism that makes vendor-only fields genuinely
        // absent from the DOM for Client registration — not present at
        // all, not just CSS/JS-hidden — because PHP simply never
        // outputs that branch's markup unless this matches it.
        $allowed_types = array('client', 'tour_organizer', 'property_owner');
        $register_as   = isset($_GET['register_as']) ? sanitize_text_field(wp_unslash($_GET['register_as'])) : '';
        if (! in_array($register_as, $allowed_types, true)) {
            $register_as = '';
        }

        // ---- Handle login submission ----
        if (isset($_POST['moga_login_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['moga_login_nonce'])), 'moga_login')) {
            $login_error = $this->handle_login();
            $active_tab  = 'login';
        }

        // ---- Handle registration submission ----
        if (isset($_POST['moga_register_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['moga_register_nonce'])), 'moga_register')) {
            $register_error = $this->handle_registration();
            $active_tab     = 'register';
            $register_as    = isset($_POST['moga_account_type']) ? sanitize_text_field(wp_unslash($_POST['moga_account_type'])) : $register_as;
        }
?>
        <div class="moga-account">

            <div class="moga-account__tabs" role="tablist">
                <a href="?tab=login" class="moga-account__tab<?php echo 'login' === $active_tab ? ' is-active' : ''; ?>">
                    <?php esc_html_e('Log In', 'moga-travel'); ?>
                </a>
                <a href="?tab=register" class="moga-account__tab<?php echo 'register' === $active_tab ? ' is-active' : ''; ?>">
                    <?php esc_html_e('Register', 'moga-travel'); ?>
                </a>
            </div>

            <?php // ==================== LOGIN PANEL ====================
            ?>
            <div class="moga-account__panel" <?php echo 'login' === $active_tab ? '' : 'hidden'; ?>>
                <?php $this->render_login_panel($login_error); ?>
            </div>

            <?php // ==================== REGISTER PANEL ====================
            ?>
            <div class="moga-account__panel" <?php echo 'register' === $active_tab ? '' : 'hidden'; ?>>
                <?php if ($register_as) : ?>
                    <?php $this->render_register_form($register_as, $register_error); ?>
                <?php else : ?>
                    <?php $this->render_register_type_picker(); ?>
                <?php endif; ?>
            </div>

        </div>

        <script>
            (function() {
                document.querySelectorAll('.moga-password-toggle').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        var input = document.getElementById(btn.dataset.target);
                        if (!input) return;

                        var showing = 'text' === input.type;
                        input.type = showing ? 'password' : 'text';
                        btn.setAttribute('aria-pressed', showing ? 'false' : 'true');
                        btn.setAttribute('aria-label', showing ?
                            '<?php echo esc_js(__('Show password', 'moga-travel')); ?>' :
                            '<?php echo esc_js(__('Hide password', 'moga-travel')); ?>');

                        btn.querySelector('.moga-password-toggle__eye').hidden = !showing;
                        btn.querySelector('.moga-password-toggle__eye-off').hidden = showing;
                    });
                });
            })();
        </script>
    <?php
    }

    /**
     * Render Day/Month/Year dropdowns for Date of Birth.
     *
     * Deliberately NOT a native <input type="date"> — that field's
     * calendar always opens on today's month, and with a max="18
     * years ago" restriction every visible day is disabled, making
     * it look completely broken since reaching a valid date requires
     * clicking "previous month" roughly 200+ times. Plain dropdowns
     * let you jump straight to any year.
     *
     * @since  1.0.0
     * @return void
     */
    private function render_dob_selects()
    {

        $posted_day   = isset($_POST['moga_reg_dob_day']) ? absint($_POST['moga_reg_dob_day']) : 0;
        $posted_month = isset($_POST['moga_reg_dob_month']) ? absint($_POST['moga_reg_dob_month']) : 0;
        $posted_year  = isset($_POST['moga_reg_dob_year']) ? absint($_POST['moga_reg_dob_year']) : 0;

        $months = array(
            1 => __('January', 'moga-travel'),
            2 => __('February', 'moga-travel'),
            3 => __('March', 'moga-travel'),
            4 => __('April', 'moga-travel'),
            5 => __('May', 'moga-travel'),
            6 => __('June', 'moga-travel'),
            7 => __('July', 'moga-travel'),
            8 => __('August', 'moga-travel'),
            9 => __('September', 'moga-travel'),
            10 => __('October', 'moga-travel'),
            11 => __('November', 'moga-travel'),
            12 => __('December', 'moga-travel'),
        );

        $current_year = (int) gmdate('Y');
        $max_year     = $current_year - 18; // Youngest allowed — 18 years old.
        $min_year     = $current_year - 100; // Oldest reasonable — 100 years old.
    ?>
        <div class="moga-dob-row">
            <select name="moga_reg_dob_day" required aria-label="<?php esc_attr_e('Day', 'moga-travel'); ?>">
                <option value=""><?php esc_html_e('Day', 'moga-travel'); ?></option>
                <?php for ($d = 1; $d <= 31; $d++) : ?>
                    <option value="<?php echo esc_attr($d); ?>" <?php selected($posted_day, $d); ?>><?php echo esc_html($d); ?></option>
                <?php endfor; ?>
            </select>

            <select name="moga_reg_dob_month" required aria-label="<?php esc_attr_e('Month', 'moga-travel'); ?>">
                <option value=""><?php esc_html_e('Month', 'moga-travel'); ?></option>
                <?php foreach ($months as $num => $label) : ?>
                    <option value="<?php echo esc_attr($num); ?>" <?php selected($posted_month, $num); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>

            <select name="moga_reg_dob_year" required aria-label="<?php esc_attr_e('Year', 'moga-travel'); ?>">
                <option value=""><?php esc_html_e('Year', 'moga-travel'); ?></option>
                <?php for ($y = $max_year; $y >= $min_year; $y--) : ?>
                    <option value="<?php echo esc_attr($y); ?>" <?php selected($posted_year, $y); ?>><?php echo esc_html($y); ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <p class="moga-account__hint"><?php esc_html_e('You must be 18 or older to register as a partner.', 'moga-travel'); ?></p>
    <?php
    }

    /**
     * Render a show/hide toggle button for a password field.
     * Icon-only, paired via data-target with the field's id — the
     * shared script in render_auth() wires up every instance of
     * this button on the page in one pass.
     *
     * @since  1.0.0
     * @param  string $target_id The id of the <input> this button controls.
     * @return void
     */
    private function render_password_toggle_btn($target_id)
    {
    ?>
        <button type="button" class="moga-password-toggle" data-target="<?php echo esc_attr($target_id); ?>" aria-label="<?php esc_attr_e('Show password', 'moga-travel'); ?>" aria-pressed="false">
            <svg class="moga-password-toggle__eye" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                <circle cx="12" cy="12" r="3" />
            </svg>
            <svg class="moga-password-toggle__eye-off" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" hidden>
                <path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a18.4 18.4 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24" />
                <line x1="1" y1="1" x2="23" y2="23" />
            </svg>
        </button>
    <?php
    }

    /**
     * Render the login form.
     *
     * @since  1.0.0
     * @param  string|null $error Error message, if a login attempt just failed.
     * @return void
     */
    private function render_login_panel($error)
    {
    ?>
        <?php if ($error) : ?>
            <div class="moga-account__notice moga-account__notice--error"><?php echo esc_html($error); ?></div>
        <?php endif; ?>

        <form method="post" class="moga-account__form">
            <?php wp_nonce_field('moga_login', 'moga_login_nonce'); ?>

            <div class="moga-account__field">
                <label for="moga_login_user"><?php esc_html_e('Email or Username', 'moga-travel'); ?></label>
                <input type="text" id="moga_login_user" name="moga_login_user" required>
            </div>

            <div class="moga-account__field">
                <label for="moga_login_pass"><?php esc_html_e('Password', 'moga-travel'); ?></label>
                <div class="moga-password-field">
                    <input type="password" id="moga_login_pass" name="moga_login_pass" required>
                    <?php $this->render_password_toggle_btn('moga_login_pass'); ?>
                </div>
            </div>

            <div class="moga-account__field moga-account__field--checkbox">
                <label><input type="checkbox" name="moga_login_remember" value="1"> <?php esc_html_e('Remember me', 'moga-travel'); ?></label>
            </div>

            <button type="submit" name="moga_login_submit" class="moga-btn moga-btn--primary moga-w-100">
                <?php esc_html_e('Log In', 'moga-travel'); ?>
            </button>

            <p class="moga-account__lost-password">
                <a href="<?php echo esc_url(wp_lostpassword_url()); ?>"><?php esc_html_e('Forgot your password?', 'moga-travel'); ?></a>
            </p>
        </form>
    <?php
    }

    /**
     * STEP 1 of registration — choose account type. Each card links to
     * the same page with ?register_as=... added, which is what causes
     * render_register_form() to output the correct, and ONLY the
     * correct, set of fields for that type.
     *
     * @since  1.0.0
     * @return void
     */
    private function render_register_type_picker()
    {

        $base_url = add_query_arg('tab', 'register', remove_query_arg('register_as'));

        $types = array(
            'client'         => array(
                'label' => __('Client', 'moga-travel'),
                'desc'  => __('Book properties and tours.', 'moga-travel'),
                'icon'  => '🧳',
            ),
            'tour_organizer' => array(
                'label' => __('Tour Organizer', 'moga-travel'),
                'desc'  => __('List and manage tours.', 'moga-travel'),
                'icon'  => '🧭',
            ),
            'property_owner' => array(
                'label' => __('Property Owner', 'moga-travel'),
                'desc'  => __('List and manage properties.', 'moga-travel'),
                'icon'  => '🏠',
            ),
        );
    ?>
        <p class="moga-account__step-intro"><?php esc_html_e('How would you like to register?', 'moga-travel'); ?></p>

        <div class="moga-account__type-picker">
            <?php foreach ($types as $key => $type) : ?>
                <a href="<?php echo esc_url(add_query_arg('register_as', $key, $base_url)); ?>" class="moga-account__type-card">
                    <span class="moga-account__type-card-icon" aria-hidden="true"><?php echo esc_html($type['icon']); ?></span>
                    <span class="moga-account__type-card-label"><?php echo esc_html($type['label']); ?></span>
                    <span class="moga-account__type-card-desc"><?php echo esc_html($type['desc']); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php
    }

    /**
     * STEP 2 of registration — the actual form for one specific type.
     * This is the only place vendor-only and company-only fields are
     * ever echoed, and only for the two vendor types — a Client never
     * receives this markup at all, server-side, regardless of JS.
     *
     * @since  1.0.0
     * @param  string      $type  'client' | 'tour_organizer' | 'property_owner'.
     * @param  string|null $error Error message, if a submission just failed.
     * @return void
     */
    private function render_register_form($type, $error)
    {

        $is_vendor = in_array($type, array('tour_organizer', 'property_owner'), true);
        $back_url  = add_query_arg('tab', 'register', remove_query_arg('register_as'));
        $selected_entity = isset($_POST['moga_entity_type']) ? sanitize_text_field(wp_unslash($_POST['moga_entity_type'])) : 'individual';

        $val = function ($key) {
            return isset($_POST[$key]) ? sanitize_text_field(wp_unslash($_POST[$key])) : '';
        };
    ?>
        <a href="<?php echo esc_url($back_url); ?>" class="moga-account__back-btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <line x1="19" y1="12" x2="5" y2="12" />
                <polyline points="12 19 5 12 12 5" />
            </svg>
            <?php esc_html_e('Choose a different account type', 'moga-travel'); ?>
        </a>

        <?php if ($error) : ?>
            <div class="moga-account__notice moga-account__notice--error"><?php echo esc_html($error); ?></div>
        <?php endif; ?>

        <form method="post" class="moga-account__form" enctype="multipart/form-data" id="moga-register-form">
            <?php wp_nonce_field('moga_register', 'moga_register_nonce'); ?>
            <input type="hidden" name="moga_account_type" value="<?php echo esc_attr($type); ?>">

            <?php // ---- Core fields — every account type ----
            ?>
            <div class="moga-account__field-row">
                <div class="moga-account__field">
                    <label for="moga_reg_first_name"><?php esc_html_e('First Name', 'moga-travel'); ?></label>
                    <input type="text" id="moga_reg_first_name" name="moga_reg_first_name" required value="<?php echo esc_attr($val('moga_reg_first_name')); ?>">
                </div>
                <div class="moga-account__field">
                    <label for="moga_reg_last_name"><?php esc_html_e('Last Name', 'moga-travel'); ?></label>
                    <input type="text" id="moga_reg_last_name" name="moga_reg_last_name" required value="<?php echo esc_attr($val('moga_reg_last_name')); ?>">
                </div>
            </div>

            <div class="moga-account__field">
                <label for="moga_reg_username"><?php esc_html_e('Username', 'moga-travel'); ?></label>
                <input type="text" id="moga_reg_username" name="moga_reg_username" required value="<?php echo esc_attr($val('moga_reg_username')); ?>">
            </div>

            <div class="moga-account__field">
                <label for="moga_reg_email"><?php esc_html_e('Email Address', 'moga-travel'); ?></label>
                <input type="email" id="moga_reg_email" name="moga_reg_email" required value="<?php echo esc_attr(sanitize_email($val('moga_reg_email'))); ?>">
            </div>

            <?php // ---- Vendor-only fields — server-rendered ONLY inside this branch ----
            ?>
            <?php if ($is_vendor) : ?>

                <div class="moga-account__field-row">
                    <div class="moga-account__field">
                        <label for="moga_reg_phone"><?php esc_html_e('Mobile Number', 'moga-travel'); ?></label>
                        <input type="text" id="moga_reg_phone" name="moga_reg_phone" required value="<?php echo esc_attr($val('moga_reg_phone')); ?>">
                    </div>
                    <div class="moga-account__field">
                        <label for="moga_reg_whatsapp"><?php esc_html_e('WhatsApp Number', 'moga-travel'); ?></label>
                        <input type="text" id="moga_reg_whatsapp" name="moga_reg_whatsapp" required value="<?php echo esc_attr($val('moga_reg_whatsapp')); ?>">
                    </div>
                </div>

                <div class="moga-account__field-row">
                    <div class="moga-account__field">
                        <label><?php esc_html_e('Date of Birth', 'moga-travel'); ?></label>
                        <?php $this->render_dob_selects(); ?>
                    </div>
                    <div class="moga-account__field">
                        <label for="moga_reg_address"><?php esc_html_e('Address', 'moga-travel'); ?></label>
                        <input type="text" id="moga_reg_address" name="moga_reg_address" required value="<?php echo esc_attr($val('moga_reg_address')); ?>">
                    </div>
                </div>

                <div class="moga-account__field">
                    <label><?php esc_html_e('Individual or Company?', 'moga-travel'); ?></label>
                    <div class="moga-account__type-choices">
                        <label class="moga-account__type-choice">
                            <input type="radio" name="moga_entity_type" value="individual" <?php checked($selected_entity, 'individual'); ?>>
                            <?php esc_html_e('Individual', 'moga-travel'); ?>
                        </label>
                        <label class="moga-account__type-choice">
                            <input type="radio" name="moga_entity_type" value="company" <?php checked($selected_entity, 'company'); ?>>
                            <?php esc_html_e('Company', 'moga-travel'); ?>
                        </label>
                    </div>
                </div>

                <?php
                // Individual vs. Company is a nested choice WITHIN an
                // already-vendor-only branch (both options here are
                // vendor concepts, unlike the outer Client/vendor split
                // that caused the original bug) — JS toggling this pair
                // is safe, and handle_registration() re-validates
                // server-side regardless of what the browser submits.
                ?>
                <div class="moga-account__field" id="moga-company-name-field" <?php echo 'company' === $selected_entity ? '' : 'hidden'; ?>>
                    <label for="moga_company_name"><?php esc_html_e('Company Name', 'moga-travel'); ?></label>
                    <input type="text" id="moga_company_name" name="moga_company_name" value="<?php echo esc_attr($val('moga_company_name')); ?>">
                </div>

                <div class="moga-account__field" id="moga-company-docs-field" <?php echo 'company' === $selected_entity ? '' : 'hidden'; ?>>
                    <label for="moga_reg_docs"><?php esc_html_e('Verification Documents (company registration, license, etc.)', 'moga-travel'); ?></label>
                    <input type="file" id="moga_reg_docs" name="moga_reg_docs[]" accept="image/*,.pdf" multiple>
                    <p class="moga-account__hint"><?php esc_html_e('Images or PDF files. Your account will be reviewed before your listings go live.', 'moga-travel'); ?></p>
                </div>

                <div class="moga-account__field">
                    <label for="moga_reg_photo" id="moga-photo-label">
                        <?php echo 'company' === $selected_entity
                            ? esc_html__('Company Logo', 'moga-travel')
                            : esc_html__('Profile Photo', 'moga-travel'); ?>
                    </label>
                    <input type="file" id="moga_reg_photo" name="moga_reg_photo" accept="image/*">
                </div>

            <?php endif; ?>

            <?php // ---- Password — every account type ----
            ?>
            <div class="moga-account__field">
                <label for="moga_reg_pass"><?php esc_html_e('Password', 'moga-travel'); ?></label>
                <div class="moga-password-field">
                    <input type="password" id="moga_reg_pass" name="moga_reg_pass" required autocomplete="new-password">
                    <?php $this->render_password_toggle_btn('moga_reg_pass'); ?>
                </div>
                <ul class="moga-password-meter" id="moga-password-meter">
                    <li data-rule="length"><?php esc_html_e('At least 8 characters', 'moga-travel'); ?></li>
                    <li data-rule="upper"><?php esc_html_e('One uppercase letter', 'moga-travel'); ?></li>
                    <li data-rule="lower"><?php esc_html_e('One lowercase letter', 'moga-travel'); ?></li>
                    <li data-rule="number"><?php esc_html_e('One number', 'moga-travel'); ?></li>
                    <li data-rule="symbol"><?php esc_html_e('One symbol (e.g. ! @ # $)', 'moga-travel'); ?></li>
                </ul>
            </div>

            <div class="moga-account__field">
                <label for="moga_reg_pass_confirm"><?php esc_html_e('Confirm Password', 'moga-travel'); ?></label>
                <div class="moga-password-field">
                    <input type="password" id="moga_reg_pass_confirm" name="moga_reg_pass_confirm" required autocomplete="new-password">
                    <?php $this->render_password_toggle_btn('moga_reg_pass_confirm'); ?>
                </div>
                <p class="moga-account__hint" id="moga-password-match-hint" hidden></p>
            </div>

            <button type="submit" name="moga_register_submit" class="moga-btn moga-btn--primary moga-w-100">
                <?php esc_html_e('Create Account', 'moga-travel'); ?>
            </button>

        </form>

        <?php if ($is_vendor) : ?>
            <script>
                (function() {
                    var entityInputs = document.querySelectorAll('input[name="moga_entity_type"]');
                    var companyName = document.getElementById('moga-company-name-field');
                    var companyDocs = document.getElementById('moga-company-docs-field');
                    var photoLabel = document.getElementById('moga-photo-label');

                    function sync() {
                        var entity = document.querySelector('input[name="moga_entity_type"]:checked');
                        var isCompany = entity && 'company' === entity.value;
                        companyName.hidden = !isCompany;
                        companyDocs.hidden = !isCompany;
                        if (photoLabel) {
                            photoLabel.textContent = isCompany ?
                                '<?php echo esc_js(__('Company Logo', 'moga-travel')); ?>' :
                                '<?php echo esc_js(__('Profile Photo', 'moga-travel')); ?>';
                        }
                    }

                    entityInputs.forEach(function(el) {
                        el.addEventListener('change', sync);
                    });
                    sync();
                })();
            </script>
        <?php endif; ?>

        <script>
            (function() {
                var passField = document.getElementById('moga_reg_pass');
                var confirmField = document.getElementById('moga_reg_pass_confirm');
                var meter = document.getElementById('moga-password-meter');
                var matchHint = document.getElementById('moga-password-match-hint');
                if (!passField || !meter) return;

                var rules = {
                    length: function(v) {
                        return v.length >= 8;
                    },
                    upper: function(v) {
                        return /[A-Z]/.test(v);
                    },
                    lower: function(v) {
                        return /[a-z]/.test(v);
                    },
                    number: function(v) {
                        return /[0-9]/.test(v);
                    },
                    symbol: function(v) {
                        return /[^A-Za-z0-9]/.test(v);
                    },
                };

                function checkPassword() {
                    var value = passField.value;
                    Object.keys(rules).forEach(function(key) {
                        var li = meter.querySelector('[data-rule="' + key + '"]');
                        var met = rules[key](value);
                        li.classList.toggle('is-met', met);
                        li.classList.toggle('is-unmet', !met && value.length > 0);
                    });
                }

                function checkMatch() {
                    if (!confirmField.value) {
                        matchHint.hidden = true;
                        return;
                    }
                    matchHint.hidden = false;
                    if (passField.value === confirmField.value) {
                        matchHint.textContent = '<?php echo esc_js(__('Passwords match', 'moga-travel')); ?>';
                        matchHint.className = 'moga-account__hint moga-account__hint--success';
                    } else {
                        matchHint.textContent = '<?php echo esc_js(__('Passwords do not match', 'moga-travel')); ?>';
                        matchHint.className = 'moga-account__hint moga-account__hint--error';
                    }
                }

                passField.addEventListener('input', function() {
                    checkPassword();
                    checkMatch();
                });
                confirmField.addEventListener('input', checkMatch);
            })();
        </script>
    <?php
    }

    /**
     * Process a login attempt.
     *
     * @since  1.0.0
     * @return string|null Error message, or null on success (redirects away).
     */
    private function handle_login()
    {

        $creds = array(
            'user_login'    => isset($_POST['moga_login_user']) ? sanitize_text_field(wp_unslash($_POST['moga_login_user'])) : '',
            'user_password' => isset($_POST['moga_login_pass']) ? $_POST['moga_login_pass'] : '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- raw password, not sanitized by design.
            'remember'      => ! empty($_POST['moga_login_remember']),
        );

        $user = wp_signon($creds, is_ssl());

        if (is_wp_error($user)) {
            return $user->get_error_message();
        }

        wp_safe_redirect(remove_query_arg(array('tab')));
        exit;
    }

    /**
     * Process a registration submission.
     *
     * @since  1.0.0
     * @return string|null Error message, or null on success (redirects away).
     */
    private function handle_registration()
    {

        $account_type = isset($_POST['moga_account_type']) ? sanitize_text_field(wp_unslash($_POST['moga_account_type'])) : 'client';
        $is_vendor    = in_array($account_type, array('tour_organizer', 'property_owner'), true);
        $entity_type  = isset($_POST['moga_entity_type']) ? sanitize_text_field(wp_unslash($_POST['moga_entity_type'])) : 'individual';

        $first_name = isset($_POST['moga_reg_first_name']) ? sanitize_text_field(wp_unslash($_POST['moga_reg_first_name'])) : '';
        $last_name  = isset($_POST['moga_reg_last_name']) ? sanitize_text_field(wp_unslash($_POST['moga_reg_last_name'])) : '';
        $username   = isset($_POST['moga_reg_username']) ? sanitize_user(wp_unslash($_POST['moga_reg_username']), true) : '';
        $email      = isset($_POST['moga_reg_email']) ? sanitize_email(wp_unslash($_POST['moga_reg_email'])) : '';
        $password   = isset($_POST['moga_reg_pass']) ? $_POST['moga_reg_pass'] : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
        $password2  = isset($_POST['moga_reg_pass_confirm']) ? $_POST['moga_reg_pass_confirm'] : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

        // Vendor-only fields — only read/validated when this IS a vendor
        // registration. A Client submission never has these keys at all
        // since render_register_form() never output them for 'client'.
        $phone        = $is_vendor && isset($_POST['moga_reg_phone']) ? sanitize_text_field(wp_unslash($_POST['moga_reg_phone'])) : '';
        $whatsapp     = $is_vendor && isset($_POST['moga_reg_whatsapp']) ? sanitize_text_field(wp_unslash($_POST['moga_reg_whatsapp'])) : '';
        $address      = $is_vendor && isset($_POST['moga_reg_address']) ? sanitize_text_field(wp_unslash($_POST['moga_reg_address'])) : '';
        $company_name = $is_vendor && isset($_POST['moga_company_name']) ? sanitize_text_field(wp_unslash($_POST['moga_company_name'])) : '';

        // ---- Core validation — every account type ----
        if (! $first_name || ! $last_name || ! $username || ! $email || ! $password) {
            return __('Please fill in all required fields.', 'moga-travel');
        }
        if (! is_email($email)) {
            return __('Please enter a valid email address.', 'moga-travel');
        }
        if (email_exists($email)) {
            return __('An account already exists with that email address.', 'moga-travel');
        }
        if (username_exists($username) || ! validate_username($username)) {
            return __('That username is invalid or already taken.', 'moga-travel');
        }
        if ($password !== $password2) {
            return __('Passwords do not match.', 'moga-travel');
        }

        $password_error = $this->validate_password($password);
        if ($password_error) {
            return $password_error;
        }

        // ---- Vendor-only validation ----
        $dob = '';
        if ($is_vendor) {
            if (! $phone || ! $whatsapp || ! $address) {
                return __('Please fill in all required fields.', 'moga-travel');
            }

            $dob = $this->combine_dob_fields();
            if (! $dob) {
                return __('Please enter a valid date of birth.', 'moga-travel');
            }
            if (strtotime($dob) > strtotime('-18 years')) {
                return __('You must be 18 or older to register as a partner.', 'moga-travel');
            }

            if ('company' === $entity_type && ! $company_name) {
                return __('Please enter your company name.', 'moga-travel');
            }
        }

        $user_id = wp_insert_user(array(
            'user_login'   => $username,
            'user_email'   => $email,
            'user_pass'    => $password,
            'display_name' => trim($first_name . ' ' . $last_name),
            'first_name'   => $first_name,
            'last_name'    => $last_name,
            'role'         => $this->role_for_account_type($account_type),
        ));

        if (is_wp_error($user_id)) {
            return $user_id->get_error_message();
        }

        update_user_meta($user_id, 'moga_contact_email', $email);

        $this->send_verification_email($user_id, $email, $first_name);

        if ($is_vendor) {
            update_user_meta($user_id, 'moga_contact_phone', $phone);
            update_user_meta($user_id, 'moga_contact_whatsapp', $whatsapp);
            update_user_meta($user_id, 'moga_date_of_birth', $dob);
            update_user_meta($user_id, 'moga_address', $address);
            update_user_meta($user_id, 'moga_entity_type', $entity_type);

            if ('company' === $entity_type) {
                update_user_meta($user_id, 'moga_company_name', $company_name);
            }

            // Every new vendor account starts pending — see the
            // 'wp_insert_post_data' gate in Moga_Roles that keeps
            // their listings unpublished until you approve them.
            update_user_meta($user_id, 'moga_vendor_status', 'pending');

            if ('company' === $entity_type && ! empty($_FILES['moga_reg_docs'])) {
                $doc_ids = $this->handle_multi_upload('moga_reg_docs', $user_id);
                if (! empty($doc_ids)) {
                    update_user_meta($user_id, 'moga_verification_docs', wp_json_encode($doc_ids));
                }
            }

            if (! empty($_FILES['moga_reg_photo']['name'])) {
                $photo_id = $this->handle_single_upload('moga_reg_photo', $user_id);
                if ($photo_id) {
                    update_user_meta($user_id, 'moga_profile_photo', $photo_id);
                }
            }
        }

        // Log the new user in immediately.
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);

        wp_safe_redirect(remove_query_arg(array('tab', 'register_as')));
        exit;
    }

    /**
     * Combine the three Day/Month/Year <select> values into a real,
     * calendar-valid 'Y-m-d' date string.
     *
     * @since  1.0.0
     * @return string 'Y-m-d' on success, empty string if invalid
     *                (e.g. Feb 30 — checkdate() catches this).
     */
    private function combine_dob_fields()
    {

        $day   = isset($_POST['moga_reg_dob_day']) ? absint($_POST['moga_reg_dob_day']) : 0;
        $month = isset($_POST['moga_reg_dob_month']) ? absint($_POST['moga_reg_dob_month']) : 0;
        $year  = isset($_POST['moga_reg_dob_year']) ? absint($_POST['moga_reg_dob_year']) : 0;

        if (! $day || ! $month || ! $year || ! checkdate($month, $day, $year)) {
            return '';
        }

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    /**
     * Server-side password strength check — the JS meter in
     * render_register_form() is a UX aid, not the enforcement. This
     * is, since client-side validation can always be bypassed.
     *
     * @since  1.0.0
     * @param  string $password Raw password.
     * @return string|null Error message, or null if the password passes.
     */
    private function validate_password($password)
    {

        if (strlen($password) < 8) {
            return __('Password must be at least 8 characters long.', 'moga-travel');
        }
        if (! preg_match('/[A-Z]/', $password)) {
            return __('Password must include at least one uppercase letter.', 'moga-travel');
        }
        if (! preg_match('/[a-z]/', $password)) {
            return __('Password must include at least one lowercase letter.', 'moga-travel');
        }
        if (! preg_match('/[0-9]/', $password)) {
            return __('Password must include at least one number.', 'moga-travel');
        }
        if (! preg_match('/[^A-Za-z0-9]/', $password)) {
            return __('Password must include at least one symbol (e.g. ! @ # $).', 'moga-travel');
        }

        return null;
    }

    /**
     * Map the registration form's account_type value to a WP role.
     *
     * @since  1.0.0
     * @param  string $account_type 'tour_organizer' | 'property_owner' | 'client'.
     * @return string WP role slug.
     */
    private function role_for_account_type($account_type)
    {
        $map = array(
            'tour_organizer' => 'moga_tour_organizer',
            'property_owner' => 'moga_property_owner',
            'client'         => 'moga_guest', // Role slug unchanged — display label is "Client" now, see render_register_type_picker().
        );
        return isset($map[$account_type]) ? $map[$account_type] : 'moga_guest';
    }


    // ============================================================
    // LOGGED-IN: PROFILE
    // ============================================================

    /**
     * Render the logged-in profile editor.
     *
     * @since  1.0.0
     * @return void
     */
    private function render_profile()
    {

        $user    = wp_get_current_user();
        $user_id = $user->ID;
        $notice  = null;

        if (isset($_POST['moga_profile_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['moga_profile_nonce'])), 'moga_profile')) {
            $notice = $this->handle_profile_update($user_id);
        }

        if (isset($_POST['moga_add_role_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['moga_add_role_nonce'])), 'moga_add_role')) {
            $notice = $this->handle_add_role($user_id);
        }

        if (isset($_POST['moga_resend_verify_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['moga_resend_verify_nonce'])), 'moga_resend_verify')) {
            $this->send_verification_email($user_id, $user->user_email, $user->first_name ?: $user->display_name);
            $notice = __('Verification email sent — please check your inbox.', 'moga-travel');
        }

        $is_verified = '1' === get_user_meta($user_id, 'moga_email_verified', true);

        $is_vendor      = (bool) array_intersect(self::VENDOR_ROLES, $user->roles);
        $vendor_status  = get_user_meta($user_id, 'moga_vendor_status', true);
        $entity_type    = get_user_meta($user_id, 'moga_entity_type', true) ?: 'individual';
        $company_name   = get_user_meta($user_id, 'moga_company_name', true);
        $photo_id       = (int) get_user_meta($user_id, 'moga_profile_photo', true);
        $photo_url      = $photo_id ? wp_get_attachment_image_url($photo_id, 'thumbnail') : '';
        $phone          = get_user_meta($user_id, 'moga_contact_phone', true);
        $whatsapp       = get_user_meta($user_id, 'moga_contact_whatsapp', true);
        $contact_email  = get_user_meta($user_id, 'moga_contact_email', true) ?: $user->user_email;

        $has_organizer = in_array('moga_tour_organizer', $user->roles, true);
        $has_owner     = in_array('moga_property_owner', $user->roles, true);
    ?>
        <div class="moga-account moga-account--profile">

            <?php if ($notice) : ?>
                <div class="moga-account__notice moga-account__notice--success"><?php echo esc_html($notice); ?></div>
            <?php endif; ?>

            <?php // ---- Email verification banner ----
            ?>
            <?php if (! $is_verified) : ?>
                <div class="moga-account__notice moga-account__notice--warning">
                    <?php esc_html_e('Please confirm your email address — check your inbox for the verification link.', 'moga-travel'); ?>
                    <form method="post" style="display:inline;margin-left:8px;">
                        <?php wp_nonce_field('moga_resend_verify', 'moga_resend_verify_nonce'); ?>
                        <button type="submit" class="moga-account__inline-link"><?php esc_html_e('Resend email', 'moga-travel'); ?></button>
                    </form>

                    <?php
                    // Local dev servers (XAMPP included) usually can't actually
                    // deliver mail without an SMTP plugin configured. Rather
                    // than leave you unable to test this flow at all, show the
                    // real link directly — but ONLY when WP_DEBUG is on, and
                    // ONLY to the account it belongs to (this is the current
                    // user's own token, never anyone else's). Remove or set
                    // WP_DEBUG to false before this site goes live.
                    if (defined('WP_DEBUG') && WP_DEBUG) :
                        $debug_token = get_user_meta($user_id, 'moga_email_verify_token', true);
                        if ($debug_token) :
                            $debug_link = add_query_arg(array('moga_verify' => $debug_token, 'uid' => $user_id), moga_account_url());
                    ?>
                            <p style="margin:6px 0 0;font-size:0.75rem;">
                                <strong>DEBUG (WP_DEBUG only):</strong>
                                <a href="<?php echo esc_url($debug_link); ?>"><?php echo esc_html($debug_link); ?></a>
                            </p>
                    <?php
                        endif;
                    endif;
                    ?>
                </div>
            <?php endif; ?>

            <?php // ---- Vendor status banner ----
            ?>
            <?php if ($is_vendor && 'approved' !== $vendor_status) : ?>
                <div class="moga-account__notice moga-account__notice--<?php echo 'rejected' === $vendor_status ? 'error' : 'warning'; ?>">
                    <?php if ('rejected' === $vendor_status) : ?>
                        <?php esc_html_e('Your vendor account was not approved. Please update your details and contact support.', 'moga-travel'); ?>
                    <?php else : ?>
                        <?php esc_html_e('Your vendor account is pending review. You can complete your profile and draft listings now, but nothing will be published until an admin approves your account.', 'moga-travel'); ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php // ---- Self-service: add the other vendor role ----
            ?>
            <?php if ($is_vendor && ! ($has_organizer && $has_owner)) : ?>
                <div class="moga-account__upsell">
                    <form method="post">
                        <?php wp_nonce_field('moga_add_role', 'moga_add_role_nonce'); ?>
                        <?php if (! $has_organizer) : ?>
                            <input type="hidden" name="moga_add_role_target" value="moga_tour_organizer">
                            <p><?php esc_html_e('Also want to organize tours on this account?', 'moga-travel'); ?></p>
                            <button type="submit" class="moga-btn moga-btn--secondary"><?php esc_html_e('Also Become a Tour Organizer', 'moga-travel'); ?></button>
                        <?php elseif (! $has_owner) : ?>
                            <input type="hidden" name="moga_add_role_target" value="moga_property_owner">
                            <p><?php esc_html_e('Also want to list properties on this account?', 'moga-travel'); ?></p>
                            <button type="submit" class="moga-btn moga-btn--secondary"><?php esc_html_e('Also Become a Property Owner', 'moga-travel'); ?></button>
                        <?php endif; ?>
                    </form>
                </div>
            <?php endif; ?>

            <?php // ---- Profile form ----
            ?>
            <form method="post" class="moga-account__form" enctype="multipart/form-data">
                <?php wp_nonce_field('moga_profile', 'moga_profile_nonce'); ?>

                <div class="moga-account__field">
                    <label id="moga-profile-photo-label">
                        <?php echo 'company' === $entity_type
                            ? esc_html__('Company Logo', 'moga-travel')
                            : esc_html__('Profile Photo', 'moga-travel'); ?>
                    </label>
                    <?php if ($photo_url) : ?>
                        <img src="<?php echo esc_url($photo_url); ?>" alt="" class="moga-account__photo-preview">
                    <?php endif; ?>
                    <input type="file" name="moga_profile_photo" accept="image/*">
                </div>

                <?php if ($is_vendor) : ?>
                    <div class="moga-account__field">
                        <label><?php esc_html_e('Individual or Company?', 'moga-travel'); ?></label>
                        <div class="moga-account__type-choices">
                            <label class="moga-account__type-choice">
                                <input type="radio" name="moga_entity_type" value="individual" <?php checked($entity_type, 'individual'); ?>>
                                <?php esc_html_e('Individual', 'moga-travel'); ?>
                            </label>
                            <label class="moga-account__type-choice">
                                <input type="radio" name="moga_entity_type" value="company" <?php checked($entity_type, 'company'); ?>>
                                <?php esc_html_e('Company', 'moga-travel'); ?>
                            </label>
                        </div>
                    </div>

                    <div class="moga-account__field" id="moga-profile-company-name-field" <?php echo 'company' === $entity_type ? '' : 'hidden'; ?>>
                        <label for="moga_company_name"><?php esc_html_e('Company Name', 'moga-travel'); ?></label>
                        <input type="text" id="moga_company_name" name="moga_company_name" value="<?php echo esc_attr($company_name); ?>">
                    </div>

                    <div class="moga-account__field" id="moga-profile-company-docs-field" <?php echo 'company' === $entity_type ? '' : 'hidden'; ?>>
                        <label for="moga_profile_docs"><?php esc_html_e('Verification Documents', 'moga-travel'); ?></label>
                        <input type="file" id="moga_profile_docs" name="moga_profile_docs[]" accept="image/*,.pdf" multiple>
                        <p class="moga-account__hint"><?php esc_html_e('Uploading new documents will re-open your account for review.', 'moga-travel'); ?></p>
                    </div>

                    <script>
                        (function() {
                            var entityInputs = document.querySelectorAll('input[name="moga_entity_type"]');
                            var companyName = document.getElementById('moga-profile-company-name-field');
                            var companyDocs = document.getElementById('moga-profile-company-docs-field');
                            var photoLabel = document.getElementById('moga-profile-photo-label');

                            function sync() {
                                var entity = document.querySelector('input[name="moga_entity_type"]:checked');
                                var isCompany = entity && 'company' === entity.value;
                                companyName.hidden = !isCompany;
                                companyDocs.hidden = !isCompany;
                                if (photoLabel) {
                                    photoLabel.textContent = isCompany ?
                                        '<?php echo esc_js(__('Company Logo', 'moga-travel')); ?>' :
                                        '<?php echo esc_js(__('Profile Photo', 'moga-travel')); ?>';
                                }
                            }

                            entityInputs.forEach(function(el) {
                                el.addEventListener('change', sync);
                            });
                            sync();
                        })();
                    </script>
                <?php endif; ?>

                <div class="moga-account__field">
                    <label for="moga_contact_phone"><?php esc_html_e('Mobile Number', 'moga-travel'); ?></label>
                    <input type="text" id="moga_contact_phone" name="moga_contact_phone" value="<?php echo esc_attr($phone); ?>">
                </div>

                <div class="moga-account__field">
                    <label for="moga_contact_whatsapp"><?php esc_html_e('WhatsApp Number', 'moga-travel'); ?></label>
                    <input type="text" id="moga_contact_whatsapp" name="moga_contact_whatsapp" value="<?php echo esc_attr($whatsapp); ?>">
                </div>

                <div class="moga-account__field">
                    <label for="moga_contact_email"><?php esc_html_e('Public Contact Email', 'moga-travel'); ?></label>
                    <input type="email" id="moga_contact_email" name="moga_contact_email" value="<?php echo esc_attr($contact_email); ?>">
                </div>

                <button type="submit" class="moga-btn moga-btn--primary"><?php esc_html_e('Save Profile', 'moga-travel'); ?></button>
            </form>

            <p class="moga-account__logout">
                <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>"><?php esc_html_e('Log Out', 'moga-travel'); ?></a>
            </p>

        </div>
<?php
    }

    /**
     * Save profile updates for a logged-in user.
     *
     * @since  1.0.0
     * @param  int $user_id Current user ID.
     * @return string Notice message.
     */
    private function handle_profile_update($user_id)
    {

        update_user_meta(
            $user_id,
            'moga_contact_phone',
            isset($_POST['moga_contact_phone']) ? sanitize_text_field(wp_unslash($_POST['moga_contact_phone'])) : ''
        );
        update_user_meta(
            $user_id,
            'moga_contact_whatsapp',
            isset($_POST['moga_contact_whatsapp']) ? sanitize_text_field(wp_unslash($_POST['moga_contact_whatsapp'])) : ''
        );
        update_user_meta(
            $user_id,
            'moga_contact_email',
            isset($_POST['moga_contact_email']) ? sanitize_email(wp_unslash($_POST['moga_contact_email'])) : ''
        );

        $user      = get_userdata($user_id);
        $is_vendor = (bool) array_intersect(self::VENDOR_ROLES, $user->roles);

        if ($is_vendor) {
            $entity_type = isset($_POST['moga_entity_type']) ? sanitize_text_field(wp_unslash($_POST['moga_entity_type'])) : 'individual';
            update_user_meta($user_id, 'moga_entity_type', $entity_type);
            update_user_meta(
                $user_id,
                'moga_company_name',
                isset($_POST['moga_company_name']) ? sanitize_text_field(wp_unslash($_POST['moga_company_name'])) : ''
            );

            // New documents re-open the account for review — the vendor
            // may have changed entities or is fixing a rejected submission.
            if (! empty($_FILES['moga_profile_docs']['name'][0])) {
                $doc_ids = $this->handle_multi_upload('moga_profile_docs', $user_id);
                if (! empty($doc_ids)) {
                    $existing = get_user_meta($user_id, 'moga_verification_docs', true);
                    $existing = $existing ? json_decode($existing, true) : array();
                    $existing = is_array($existing) ? $existing : array();
                    update_user_meta($user_id, 'moga_verification_docs', wp_json_encode(array_merge($existing, $doc_ids)));
                    update_user_meta($user_id, 'moga_vendor_status', 'pending');
                }
            }
        }

        if (! empty($_FILES['moga_profile_photo']['name'])) {
            $photo_id = $this->handle_single_upload('moga_profile_photo', $user_id);
            if ($photo_id) {
                update_user_meta($user_id, 'moga_profile_photo', $photo_id);
            }
        }

        return __('Profile updated.', 'moga-travel');
    }

    /**
     * Self-service: add the other vendor role to an existing vendor's
     * account. Does NOT reset vendor_status — an already-approved
     * vendor stays approved; documents already on file cover both
     * roles since it's the same legal entity/individual.
     *
     * @since  1.0.0
     * @param  int $user_id Current user ID.
     * @return string Notice message.
     */
    private function handle_add_role($user_id)
    {

        $target = isset($_POST['moga_add_role_target']) ? sanitize_text_field(wp_unslash($_POST['moga_add_role_target'])) : '';

        if (! in_array($target, self::VENDOR_ROLES, true)) {
            return __('Invalid role requested.', 'moga-travel');
        }

        $user = get_userdata($user_id);
        $user->add_role($target);

        return __('Role added to your account.', 'moga-travel');
    }


    // ============================================================
    // FRONT-END FILE UPLOAD HELPERS
    // ============================================================

    /**
     * Ensure the WP media-handling functions are loaded.
     * They are admin-only includes by default, needed here for
     * front-end front-of-site uploads via media_handle_upload().
     *
     * @since  1.0.0
     * @return void
     */
    private function ensure_upload_includes()
    {
        if (! function_exists('media_handle_upload')) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
        }
    }

    /**
     * Handle a single-file upload field.
     *
     * @since  1.0.0
     * @param  string $field   $_FILES key.
     * @param  int    $post_id Post ID to attach the upload to (0 = unattached).
     * @return int Attachment ID, or 0 on failure.
     */
    private function handle_single_upload($field, $post_id = 0)
    {

        if (empty($_FILES[$field]['name'])) {
            return 0;
        }

        $this->ensure_upload_includes();

        $attachment_id = media_handle_upload($field, $post_id);

        return is_wp_error($attachment_id) ? 0 : $attachment_id;
    }

    /**
     * Handle a multi-file upload field (name="field[]").
     *
     * @since  1.0.0
     * @param  string $field   $_FILES key.
     * @param  int    $post_id Post ID to attach uploads to (0 = unattached).
     * @return array Array of attachment IDs.
     */
    private function handle_multi_upload($field, $post_id = 0)
    {

        if (empty($_FILES[$field]['name']) || ! is_array($_FILES[$field]['name'])) {
            return array();
        }

        $this->ensure_upload_includes();

        $ids   = array();
        $count = count($_FILES[$field]['name']);

        for ($i = 0; $i < $count; $i++) {

            if (empty($_FILES[$field]['name'][$i])) {
                continue;
            }

            $single_file = array(
                'name'     => $_FILES[$field]['name'][$i],
                'type'     => $_FILES[$field]['type'][$i],
                'tmp_name' => $_FILES[$field]['tmp_name'][$i],
                'error'    => $_FILES[$field]['error'][$i],
                'size'     => $_FILES[$field]['size'][$i],
            );

            // media_handle_upload() reads from the global $_FILES by key,
            // so temporarily substitute the single-file array for this key.
            $_FILES['__moga_tmp_' . $i] = $single_file;
            $attachment_id = media_handle_upload('__moga_tmp_' . $i, $post_id);
            unset($_FILES['__moga_tmp_' . $i]);

            if (! is_wp_error($attachment_id)) {
                $ids[] = $attachment_id;
            }
        }

        return $ids;
    }
}
