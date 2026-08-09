<?php

/**
 * Language Switching
 *
 * Path: plugins/moga-travel-core/includes/classes/class-moga-i18n.php
 *
 * A one-click language switch — no WPML/Polylang dependency,
 * consistent with this project's standing preference for owning
 * core flows itself rather than depending on third-party plugins.
 *
 * Mechanism: a cookie stores the visitor's chosen language; the
 * 'locale' filter (which WordPress consults very early, before
 * translations load and before is_rtl()/dir="rtl" get decided)
 * forces that locale for every request. This works correctly with
 * zero real Arabic translation files in place yet — WordPress
 * already knows 'ar' is an RTL language from its own locale data,
 * so dir="rtl", text alignment, and every RTL CSS rule already
 * built in this project flips correctly. Only the actual word-level
 * translations (still pending, Phase 6) are unaffected by this.
 *
 * register_locale_filter() is called directly, at file-require
 * time, from Moga_Core::load_dependencies() — NOT from
 * boot_components() (which runs on the 'init' hook). That's
 * deliberate: the 'locale' filter has to be registered before
 * WordPress loads this request's translations, which happens
 * earlier than 'init' fires.
 *
 * @package    MogaTravelCore
 * @subpackage MogaTravelCore/includes/classes
 * @author     Hatem Frere
 * @since      1.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Class Moga_I18n
 */
class Moga_I18n
{

    /**
     * Cookie name storing the visitor's chosen language.
     *
     * @since 1.0.0
     */
    const COOKIE_NAME = 'moga_lang';

    /**
     * Supported languages — short code => real WordPress locale.
     * Add more here later as real translations get built; nothing
     * else in this class needs to change to support a third language.
     *
     * @since 1.0.0
     */
    const SUPPORTED = array(
        'en' => 'en_US',
        'ar' => 'ar',
    );


    // ============================================================
    // EARLY REGISTRATION — called directly from load_dependencies()
    // ============================================================

    /**
     * Register the 'locale' filter. Must run before WordPress loads
     * this request's translations — see class docblock for why this
     * is called directly rather than through the normal boot sequence.
     *
     * @since  1.0.0
     * @return void
     */
    public static function register_locale_filter()
    {
        add_filter('locale', array(__CLASS__, 'filter_locale'));
    }

    /**
     * Force the locale based on the visitor's cookie, if set to a
     * supported language. Falls through to WordPress's own default
     * (whatever Settings > General > Site Language says) otherwise.
     *
     * @since  1.0.0
     * @param  string $locale Default locale WordPress resolved.
     * @return string
     */
    public static function filter_locale($locale)
    {
        $lang = self::get_cookie_lang();
        return ($lang && isset(self::SUPPORTED[$lang])) ? self::SUPPORTED[$lang] : $locale;
    }


    // ============================================================
    // NORMAL BOOT — the switch-request handler
    // ============================================================

    /**
     * Hook the switch-request handler. Called from boot_components(),
     * same pattern as every other class in this plugin.
     *
     * @since  1.0.0
     * @return void
     */
    public static function init()
    {
        add_action('init', array(__CLASS__, 'maybe_handle_switch_request'), 1);
    }

    /**
     * If the current request is a language-switch click
     * (?moga_lang=xx), set the cookie and redirect back to the
     * same URL with that query arg stripped.
     *
     * @since  1.0.0
     * @return void
     */
    public static function maybe_handle_switch_request()
    {

        if (! isset($_GET['moga_lang'])) {
            return;
        }

        $lang = sanitize_text_field(wp_unslash($_GET['moga_lang']));

        if (! isset(self::SUPPORTED[$lang])) {
            return;
        }

        setcookie(
            self::COOKIE_NAME,
            $lang,
            time() + YEAR_IN_SECONDS,
            defined('COOKIEPATH') && COOKIEPATH ? COOKIEPATH : '/',
            defined('COOKIE_DOMAIN') ? COOKIE_DOMAIN : '',
            is_ssl(),
            true
        );

        wp_safe_redirect(remove_query_arg('moga_lang'));
        exit;
    }


    // ============================================================
    // HELPERS — for templates (header-top.php uses these)
    // ============================================================

    /**
     * Read the raw cookie value.
     *
     * @since  1.0.0
     * @return string
     */
    private static function get_cookie_lang()
    {
        $value = filter_input(INPUT_COOKIE, self::COOKIE_NAME);
        return $value ? sanitize_text_field(wp_unslash($value)) : '';
    }

    /**
     * The visitor's currently active short language code.
     * Defaults to 'en' if no cookie is set or it's invalid.
     *
     * @since  1.0.0
     * @return string 'en' or 'ar'.
     */
    public static function current_lang()
    {
        $lang = self::get_cookie_lang();
        return isset(self::SUPPORTED[$lang]) ? $lang : 'en';
    }

    /**
     * The OTHER language — i.e. what clicking the switcher would
     * change to. Only meaningful with exactly two supported
     * languages; if a third is ever added, this should become a
     * dropdown instead of a single toggle link.
     *
     * @since  1.0.0
     * @return string
     */
    public static function other_lang()
    {
        return 'en' === self::current_lang() ? 'ar' : 'en';
    }

    /**
     * Display label for the OTHER language, for the switcher link.
     *
     * @since  1.0.0
     * @return string
     */
    public static function other_lang_label()
    {
        return 'en' === self::current_lang()
            ? __('العربية', 'moga-travel-core')
            : __('English', 'moga-travel-core');
    }

    /**
     * URL that switches to the other language, preserving the
     * current page.
     *
     * @since  1.0.0
     * @return string
     */
    public static function switch_url()
    {
        return add_query_arg('moga_lang', self::other_lang());
    }
}
