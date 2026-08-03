<?php

/**
 * Vendor Contact Form — Shared Template Part
 *
 * Path: themes/moga-travel/template-parts/global/vendor-contact-form.php
 *
 * Lets a client message the specific property owner or tour
 * organizer for the listing being viewed — separate from the
 * site-wide Contact Us page, which reaches the site admin, not
 * a vendor. Used on both single-moga_property.php and
 * single-moga_tour.php sidebars, right after each page's own
 * owner/organizer card.
 *
 * Expects these variables already set by the including template:
 *   $vendor_name     string  Display name shown in the form heading.
 *   $vendor_email    string  Resolved recipient — already run through
 *                            each page's own fallback chain (listing
 *                            override → vendor profile → nothing).
 *   $vendor_phone    string  Resolved phone, same fallback chain. May
 *                            be empty — the phone icon below the form
 *                            still renders, just faded/disabled.
 *   $vendor_whatsapp string  Resolved WhatsApp number, same pattern.
 *   $listing_title   string  Current listing's title, included in the
 *                            email subject so the vendor knows which
 *                            listing the message is about.
 *
 * Self-processing, same pattern as Moga_Shortcode_Contact — no
 * admin-post.php dependency, honeypot spam protection, no
 * third-party form plugin dependency.
 *
 * @package MogaTravel
 * @since   1.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

// This template is always included via get_template_part() from
// single-moga_property.php / single-moga_tour.php, which set these
// three variables immediately beforehand — PHP's include mechanism
// shares that calling scope, so this already works correctly at
// runtime. The lines below exist so this file also declares them
// within its own visible scope (silences an editor cross-file
// blind spot) and so it degrades gracefully if ever included
// somewhere that forgets to set one.
$vendor_name    = $vendor_name ?? '';
$vendor_email   = $vendor_email ?? '';
$vendor_phone   = $vendor_phone ?? '';
$vendor_whatsapp = $vendor_whatsapp ?? '';
$listing_title  = $listing_title ?? get_the_title();
$listing_type   = $listing_type ?? 'property';

if (empty($vendor_email)) {
    return; // Nothing to send to — no vendor email resolved anywhere in the fallback chain.
}

$notice = null;

if (
    isset($_POST['moga_vendor_contact_nonce'])
    && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['moga_vendor_contact_nonce'])), 'moga_vendor_contact_' . get_the_ID())
) {
    // Honeypot — silently pretend success to a bot.
    if (! empty($_POST['moga_vendor_contact_website'])) {
        $notice = array('type' => 'success', 'message' => __('Thank you — your message has been sent.', 'moga-travel'));
    } else {
        $name    = isset($_POST['moga_vendor_contact_name']) ? sanitize_text_field(wp_unslash($_POST['moga_vendor_contact_name'])) : '';
        $email   = isset($_POST['moga_vendor_contact_email']) ? sanitize_email(wp_unslash($_POST['moga_vendor_contact_email'])) : '';
        $message = isset($_POST['moga_vendor_contact_message']) ? sanitize_textarea_field(wp_unslash($_POST['moga_vendor_contact_message'])) : '';

        if (! $name || ! $email || ! $message) {
            $notice = array('type' => 'error', 'message' => __('Please fill in all fields.', 'moga-travel'));
        } elseif (! is_email($email)) {
            $notice = array('type' => 'error', 'message' => __('Please enter a valid email address.', 'moga-travel'));
        } else {
            // "Property Title:" or "Tour Title:" — exact wording requested,
            // driven by $listing_type set by whichever page includes this.
            $title_label = 'tour' === $listing_type
                ? __('Tour Title', 'moga-travel')
                : __('Property Title', 'moga-travel');

            $body = sprintf(
                /* translators: 1: "Property Title" or "Tour Title", 2: listing title, 3: name, 4: email, 5: message */
                __("%1\$s: %2\$s\n\nFrom: %3\$s <%4\$s>\n\nMessage:\n%5\$s", 'moga-travel'),
                $title_label,
                $listing_title,
                $name,
                $email,
                $message
            );

            $subject = sprintf( /* translators: 1: "Property Title" or "Tour Title", 2: listing title */__('New inquiry — %1$s: %2$s', 'moga-travel'), $title_label, $listing_title);

            $sent = wp_mail($vendor_email, $subject, $body, array('Reply-To: ' . $name . ' <' . $email . '>'));

            $notice = $sent
                ? array('type' => 'success', 'message' => __('Thank you — your message has been sent.', 'moga-travel'))
                : array('type' => 'error', 'message' => __('Something went wrong sending your message. Please try again.', 'moga-travel'));

            // Local dev servers (XAMPP included) usually can't actually
            // deliver mail without an SMTP plugin configured. Rather than
            // leave this untestable locally, show what was actually sent —
            // but ONLY when WP_DEBUG is on. Same pattern already used for
            // the account email-verification flow. Remove or set WP_DEBUG
            // to false before this site goes live and this block never
            // outputs anything again.
            if (defined('WP_DEBUG') && WP_DEBUG) {
                $notice['debug'] = array(
                    'to'      => $vendor_email,
                    'subject' => $subject,
                    'body'    => $body,
                );
            }
        }
    }
}
?>

<div class="moga-vendor-contact">
    <h3 class="moga-vendor-contact__title">
        <?php
        printf(
            /* translators: %s: vendor's display name */
            esc_html__('Message %s', 'moga-travel'),
            esc_html($vendor_name ?: __('the host', 'moga-travel'))
        );
        ?>
    </h3>

    <?php if ($notice) : ?>
        <div class="moga-account__notice moga-account__notice--<?php echo esc_attr($notice['type']); ?>">
            <?php echo esc_html($notice['message']); ?>

            <?php if (! empty($notice['debug'])) : ?>
                <div style="margin-top:8px;padding-top:8px;border-top:1px dashed currentColor;font-size:0.75rem;">
                    <strong><?php esc_html_e('DEBUG (WP_DEBUG only) — email content:', 'moga-travel'); ?></strong><br>
                    <strong><?php esc_html_e('To:', 'moga-travel'); ?></strong> <?php echo esc_html($notice['debug']['to']); ?><br>
                    <strong><?php esc_html_e('Subject:', 'moga-travel'); ?></strong> <?php echo esc_html($notice['debug']['subject']); ?><br>
                    <strong><?php esc_html_e('Body:', 'moga-travel'); ?></strong>
                    <pre style="white-space:pre-wrap;margin:4px 0 0;font-family:inherit;"><?php echo esc_html($notice['debug']['body']); ?></pre>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (! $notice || 'success' !== $notice['type']) : ?>
        <form method="post" class="moga-account__form moga-vendor-contact__form">
            <?php wp_nonce_field('moga_vendor_contact_' . get_the_ID(), 'moga_vendor_contact_nonce'); ?>

            <div class="moga-contact-form__honeypot" aria-hidden="true">
                <label for="moga_vendor_contact_website"><?php esc_html_e('Website', 'moga-travel'); ?></label>
                <input type="text" id="moga_vendor_contact_website" name="moga_vendor_contact_website" tabindex="-1" autocomplete="off">
            </div>

            <div class="moga-account__field">
                <label for="moga_vendor_contact_name"><?php esc_html_e('Your Name', 'moga-travel'); ?></label>
                <input type="text" id="moga_vendor_contact_name" name="moga_vendor_contact_name" required>
            </div>

            <div class="moga-account__field">
                <label for="moga_vendor_contact_email"><?php esc_html_e('Your Email', 'moga-travel'); ?></label>
                <input type="email" id="moga_vendor_contact_email" name="moga_vendor_contact_email" required>
            </div>

            <div class="moga-account__field">
                <label for="moga_vendor_contact_message"><?php esc_html_e('Message', 'moga-travel'); ?></label>
                <textarea id="moga_vendor_contact_message" name="moga_vendor_contact_message" rows="4" required></textarea>
            </div>

            <button type="submit" class="moga-btn moga-btn--secondary moga-w-100">
                <?php esc_html_e('Send Message', 'moga-travel'); ?>
            </button>
        </form>
    <?php endif; ?>

    <?php // ---- Direct contact icons — always shown; faded + click-tip when the vendor hasn't set that field ----
    ?>
    <div class="moga-vendor-contact__icons">

        <button type="button"
            class="moga-vendor-contact__icon-btn<?php echo $vendor_phone ? '' : ' is-disabled'; ?>"
            data-href="<?php echo $vendor_phone ? esc_url('tel:' . preg_replace('/\s+/', '', $vendor_phone)) : ''; ?>"
            data-empty-message="<?php esc_attr_e('The partner does not have a phone number.', 'moga-travel'); ?>"
            aria-label="<?php esc_attr_e('Call', 'moga-travel'); ?>">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.362 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0 1 22 16.92z" />
            </svg>
        </button>

        <button type="button"
            class="moga-vendor-contact__icon-btn moga-vendor-contact__icon-btn--whatsapp<?php echo $vendor_whatsapp ? '' : ' is-disabled'; ?>"
            data-href="<?php echo $vendor_whatsapp ? esc_url('https://wa.me/' . preg_replace('/\D/', '', $vendor_whatsapp)) : ''; ?>"
            data-empty-message="<?php esc_attr_e('The partner does not have a WhatsApp number.', 'moga-travel'); ?>"
            aria-label="<?php esc_attr_e('WhatsApp', 'moga-travel'); ?>">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M17.6 6.3a8.9 8.9 0 0 0-14.2 10.7L2 22l5.2-1.4A8.9 8.9 0 0 0 17.6 6.3zM12 20.2a7.3 7.3 0 0 1-3.7-1l-.3-.2-2.7.7.7-2.6-.2-.3a7.3 7.3 0 1 1 13.6-3.7 7.3 7.3 0 0 1-7.4 7.1zm4-5.5c-.2-.1-1.3-.6-1.5-.7-.2-.1-.3-.1-.5.1s-.6.7-.7.8-.3.2-.5.1a6 6 0 0 1-1.8-1.1 6.7 6.7 0 0 1-1.2-1.5c-.1-.2 0-.3.1-.5l.4-.4c.1-.1.1-.2.2-.4s0-.3 0-.4l-.7-1.6c-.2-.4-.4-.4-.5-.4h-.5a.9.9 0 0 0-.6.3 2.7 2.7 0 0 0-.8 2 4.7 4.7 0 0 0 1 2.5 10.7 10.7 0 0 0 4.1 3.6c.6.2 1 .4 1.4.5a3.3 3.3 0 0 0 1.5.1 2.5 2.5 0 0 0 1.6-1.1 1.9 1.9 0 0 0 .1-1.1c-.1-.1-.2-.2-.4-.3z" />
            </svg>
        </button>

    </div>

    <script>
        (function() {
            var container = document.currentScript.closest('.moga-vendor-contact') ||
                document.currentScript.previousElementSibling;
            var buttons = document.querySelectorAll('.moga-vendor-contact__icon-btn');

            buttons.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var href = btn.dataset.href;

                    if (!href) {
                        var existing = btn.querySelector('.moga-vendor-contact__tip');
                        if (existing) {
                            existing.remove();
                            return;
                        }

                        var tip = document.createElement('span');
                        tip.className = 'moga-vendor-contact__tip';
                        tip.textContent = btn.dataset.emptyMessage;
                        btn.appendChild(tip);

                        setTimeout(function() {
                            if (tip.parentNode) {
                                tip.remove();
                            }
                        }, 3000);
                        return;
                    }

                    var isWhatsapp = href.indexOf('wa.me') !== -1;
                    if (isWhatsapp) {
                        window.open(href, '_blank', 'noopener,noreferrer');
                    } else {
                        window.location.href = href;
                    }
                });
            });
        })();
    </script>
</div>
