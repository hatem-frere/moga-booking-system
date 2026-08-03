<?php

/**
 * Contact Form Shortcode
 *
 * Path: plugins/moga-travel-core/includes/shortcodes/class-moga-shortcode-contact.php
 *
 * Self-contained [moga_contact_form] shortcode — no third-party
 * dependency (CF7, WPForms, etc.), consistent with this project's
 * standing preference for owning its own core flows rather than
 * depending on external plugins (same reasoning as the custom
 * account/auth system replacing wp-login.php entirely, and
 * skipping WooCommerce for the booking engine).
 *
 * Because the Contact page's content is a normal WordPress page
 * (created by Moga_Activator::create_pages(), default content is
 * just this shortcode), an admin who later wants a different form
 * plugin instead can simply edit the page and replace the
 * shortcode — no code change needed here for that to work.
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
 * Class Moga_Shortcode_Contact
 */
class Moga_Shortcode_Contact
{

    /**
     * Register the shortcode.
     *
     * @since  1.0.0
     * @return void
     */
    public function register()
    {
        add_shortcode('moga_contact_form', array($this, 'render'));
    }

    /**
     * Shortcode callback.
     *
     * @since  1.0.0
     * @return string
     */
    public function render()
    {

        ob_start();

        $notice = null;

        if (
            isset($_POST['moga_contact_nonce'])
            && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['moga_contact_nonce'])), 'moga_contact_form')
        ) {
            $notice = $this->handle_submission();
        }
?>

        <?php if ($notice) : ?>
            <div class="moga-account__notice moga-account__notice--<?php echo esc_attr($notice['type']); ?>">
                <?php echo esc_html($notice['message']); ?>
            </div>
        <?php endif; ?>

        <?php if (! $notice || 'success' !== $notice['type']) : ?>
            <form method="post" class="moga-account__form moga-contact-form">
                <?php wp_nonce_field('moga_contact_form', 'moga_contact_nonce'); ?>

                <?php // Honeypot — hidden from real visitors via CSS, bots tend to fill every field. 
                ?>
                <div class="moga-contact-form__honeypot" aria-hidden="true">
                    <label for="moga_contact_website"><?php esc_html_e('Website', 'moga-travel'); ?></label>
                    <input type="text" id="moga_contact_website" name="moga_contact_website" tabindex="-1" autocomplete="off">
                </div>

                <div class="moga-account__field-row">
                    <div class="moga-account__field">
                        <label for="moga_contact_name"><?php esc_html_e('Full Name', 'moga-travel'); ?></label>
                        <input type="text" id="moga_contact_name" name="moga_contact_name" required
                            value="<?php echo isset($_POST['moga_contact_name']) ? esc_attr(sanitize_text_field(wp_unslash($_POST['moga_contact_name']))) : ''; ?>">
                    </div>
                    <div class="moga-account__field">
                        <label for="moga_contact_email"><?php esc_html_e('Email Address', 'moga-travel'); ?></label>
                        <input type="email" id="moga_contact_email" name="moga_contact_email" required
                            value="<?php echo isset($_POST['moga_contact_email']) ? esc_attr(sanitize_email(wp_unslash($_POST['moga_contact_email']))) : ''; ?>">
                    </div>
                </div>

                <div class="moga-account__field">
                    <label for="moga_contact_subject"><?php esc_html_e('Subject', 'moga-travel'); ?></label>
                    <input type="text" id="moga_contact_subject" name="moga_contact_subject" required
                        value="<?php echo isset($_POST['moga_contact_subject']) ? esc_attr(sanitize_text_field(wp_unslash($_POST['moga_contact_subject']))) : ''; ?>">
                </div>

                <div class="moga-account__field">
                    <label for="moga_contact_message"><?php esc_html_e('Message', 'moga-travel'); ?></label>
                    <textarea id="moga_contact_message" name="moga_contact_message" rows="6" required><?php
                                                                                                        echo isset($_POST['moga_contact_message']) ? esc_textarea(sanitize_textarea_field(wp_unslash($_POST['moga_contact_message']))) : '';
                                                                                                        ?></textarea>
                </div>

                <button type="submit" class="moga-btn moga-btn--primary moga-w-100">
                    <?php esc_html_e('Send Message', 'moga-travel'); ?>
                </button>
            </form>
        <?php endif; ?>

<?php
        return ob_get_clean();
    }

    /**
     * Process a contact form submission.
     *
     * @since  1.0.0
     * @return array{type:string,message:string}
     */
    private function handle_submission()
    {

        // Honeypot check — silently pretend success to a bot rather
        // than reveal the field is a trap.
        if (! empty($_POST['moga_contact_website'])) {
            return array('type' => 'success', 'message' => __('Thank you — your message has been sent.', 'moga-travel'));
        }

        $name    = isset($_POST['moga_contact_name']) ? sanitize_text_field(wp_unslash($_POST['moga_contact_name'])) : '';
        $email   = isset($_POST['moga_contact_email']) ? sanitize_email(wp_unslash($_POST['moga_contact_email'])) : '';
        $subject = isset($_POST['moga_contact_subject']) ? sanitize_text_field(wp_unslash($_POST['moga_contact_subject'])) : '';
        $message = isset($_POST['moga_contact_message']) ? sanitize_textarea_field(wp_unslash($_POST['moga_contact_message'])) : '';

        if (! $name || ! $email || ! $subject || ! $message) {
            return array('type' => 'error', 'message' => __('Please fill in all fields.', 'moga-travel'));
        }
        if (! is_email($email)) {
            return array('type' => 'error', 'message' => __('Please enter a valid email address.', 'moga-travel'));
        }

        $to = get_option('moga_admin_email') ?: get_option('admin_email');

        $body = sprintf(
            /* translators: 1: name, 2: email, 3: message */
            __("New contact form message.\n\nFrom: %1\$s <%2\$s>\n\nMessage:\n%3\$s", 'moga-travel'),
            $name,
            $email,
            $message
        );

        $headers = array(
            'Reply-To: ' . $name . ' <' . $email . '>',
        );

        $sent = wp_mail($to, '[' . get_bloginfo('name') . '] ' . $subject, $body, $headers);

        if (! $sent) {
            return array('type' => 'error', 'message' => __('Something went wrong sending your message. Please try again or email us directly.', 'moga-travel'));
        }

        return array('type' => 'success', 'message' => __('Thank you — your message has been sent. We will get back to you soon.', 'moga-travel'));
    }
}
