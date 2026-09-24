/**
 * Dashboard JS — Moga Booking System
 *
 * 1. Horizontal nav dropdown open/close
 * 2. Mobile drawer open/close
 * 3. AJAX tab content loading
 * 4. URL update via history.pushState
 * 5. Filter panel expand/collapse (all admin tabs)
 *
 * @package MogaTravel
 * @since   1.0.0
 */

/* global mogaDashboardData */
(function () {
    'use strict';

    if (typeof mogaDashboardData === 'undefined') { return; }

    var cfg       = mogaDashboardData;
    var activeTab = cfg.tab || 'overview';
    var busy      = false;
    var content   = document.getElementById('moga-db-content');

    // ── 1. Dropdown menus ────────────────────────────────────────────
    // Dropdown menus are moved to <body> when opened so they escape
    // the sticky topbar stacking context and render above all content.

    var activeDropdown = null; // { trigger, menu, original_parent }

    function openDropdown(btn) {
        closeAll();

        var dropdown = btn.closest('.moga-db__nav-dropdown');
        var menu     = dropdown.querySelector('.moga-db__nav-dropdown-menu');
        if (!menu) { return; }

        // Store original parent for restore on close.
        var originalParent = menu.parentNode;

        // Get trigger position.
        var rect = btn.getBoundingClientRect();

        // Move menu to body.
        document.body.appendChild(menu);

        // Position it below the trigger button.
        menu.style.position   = 'fixed';
        menu.style.top        = (rect.bottom + 6) + 'px';
        menu.style.left       = (rect.left + rect.width / 2) + 'px';
        menu.style.transform  = 'translateX(-50%)';
        menu.style.zIndex     = '99999';
        menu.style.display    = 'block';

        dropdown.classList.add('is-open');
        btn.setAttribute('aria-expanded', 'true');

        activeDropdown = { btn: btn, menu: menu, parent: originalParent, dropdown: dropdown };
    }

    function closeAll() {
        if (activeDropdown) {
            // Restore menu to original parent.
            activeDropdown.parent.appendChild(activeDropdown.menu);
            activeDropdown.menu.style.cssText = '';
            activeDropdown.dropdown.classList.remove('is-open');
            activeDropdown.btn.setAttribute('aria-expanded', 'false');
            activeDropdown = null;
        }
    }

    document.querySelectorAll('.moga-db__nav-item--dropdown').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var dropdown = btn.closest('.moga-db__nav-dropdown');
            var wasOpen  = dropdown.classList.contains('is-open');
            closeAll();
            if (!wasOpen) { openDropdown(btn); }
        });
    });

    // Stop clicks inside a detached menu from bubbling to document.
    document.addEventListener('click', function (e) {
        if (activeDropdown && activeDropdown.menu.contains(e.target)) { return; }
        closeAll();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { closeAll(); closeMobile(); }
    });

    // ── 2. Mobile drawer ─────────────────────────────────────────────

    var mobileNav  = document.getElementById('moga-db-mobile-nav');
    var backdrop   = document.getElementById('moga-db-backdrop');
    var hamburger  = document.getElementById('moga-db-hamburger');

    function openMobile() {
        if (!mobileNav) { return; }
        mobileNav.classList.add('is-open');
        mobileNav.setAttribute('aria-hidden', 'false');
        if (backdrop)  { backdrop.classList.add('is-active'); }
        if (hamburger) { hamburger.setAttribute('aria-expanded', 'true'); }
        document.body.style.overflow = 'hidden';
    }

    function closeMobile() {
        if (!mobileNav) { return; }
        mobileNav.classList.remove('is-open');
        mobileNav.setAttribute('aria-hidden', 'true');
        if (backdrop)  { backdrop.classList.remove('is-active'); }
        if (hamburger) { hamburger.setAttribute('aria-expanded', 'false'); }
        document.body.style.overflow = '';
    }

    if (hamburger) {
        hamburger.addEventListener('click', function () {
            if (mobileNav && mobileNav.classList.contains('is-open')) {
                closeMobile();
            } else {
                openMobile();
            }
        });
    }

    if (backdrop) { backdrop.addEventListener('click', closeMobile); }

    // ── 3. AJAX tab loading ───────────────────────────────────────────

    function buildUrl(tab) {
        var params = new URLSearchParams(window.location.search);
        params.set('tab', tab);
        return window.location.pathname + '?' + params.toString();
    }

    function setActive(tab) {
        document.querySelectorAll('.moga-db__nav-item[data-tab]').forEach(function (el) {
            el.classList.toggle('is-active', el.getAttribute('data-tab') === tab);
        });
        document.querySelectorAll('.moga-db__nav-dropdown-item[data-tab]').forEach(function (el) {
            el.classList.toggle('is-active', el.getAttribute('data-tab') === tab);
        });
        document.querySelectorAll('.moga-db__mobile-nav-link[data-tab]').forEach(function (el) {
            el.classList.toggle('is-active', el.getAttribute('data-tab') === tab);
        });
        // Mark parent dropdown active when a child is active.
        document.querySelectorAll('.moga-db__nav-dropdown').forEach(function (dd) {
            var hasActive = dd.querySelector('.moga-db__nav-dropdown-item.is-active');
            dd.classList.toggle('is-active', !!hasActive);
            var trigger = dd.querySelector('.moga-db__nav-item--dropdown');
            if (trigger) { trigger.classList.toggle('is-active', !!hasActive); }
        });
    }

    function goToTab(tab) {
        if (tab === activeTab || busy || !content) { return; }
        closeAll();
        busy = true;
        content.style.opacity = '0.5';

        var body = new FormData();
        body.append('action', 'moga_dashboard_tab');
        body.append('nonce',  cfg.nonce);
        body.append('tab',    tab);

        fetch(cfg.ajaxUrl, { method: 'POST', body: body })
            .then(function (r) { return r.json(); })
            .then(function (json) {
                if (json.success && json.data && json.data.html) {
                    content.innerHTML = json.data.html;
                    activeTab = tab;
                    setActive(tab);
                    if (history.pushState) {
                        history.pushState({ tab: tab }, '', buildUrl(tab));
                    }
                    window.scrollTo(0, 0);
                } else {
                    window.location.href = buildUrl(tab);
                }
            })
            .catch(function () {
                window.location.href = buildUrl(tab);
            })
            .finally(function () {
                busy = false;
                content.style.opacity = '';
            });
    }

    // Intercept tab link clicks.
    document.addEventListener('click', function (e) {
        var el = e.target.closest('[data-tab]');
        if (!el) { return; }
        // Let external links navigate normally.
        if (el.getAttribute('target') === '_blank') { return; }
        // Let card-grid links navigate normally.
        if (el.closest('.moga-db-admin-overview__card-links')) { return; }
        // Let logout links navigate normally.
        if (el.tagName === 'A' && el.href && el.href.indexOf('logout') !== -1) { return; }
        // Let quick filter chips do a FULL page reload — no AJAX.
        // Chips carry query-string params that the PHP needs to process server-side.
        if (el.closest('.moga-db-toolbar__quick')) { return; }
        // Let toolbar Clear/Apply actions do full page reloads.
        if (el.closest('.moga-db-toolbar')) { return; }

        e.preventDefault();
        closeAll();
        closeMobile();
        goToTab(el.getAttribute('data-tab'));
    });

    // Browser back/forward.
    window.addEventListener('popstate', function (e) {
        if (e.state && e.state.tab) { goToTab(e.state.tab); }
    });

    // ── 5. Filter panel toggle ────────────────────────────────────────
    // Expands/collapses the advanced filter panel on all admin tabs.

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.moga-db-toolbar__filter-toggle');
        if (!btn) { return; }
        var targetId = btn.getAttribute('aria-controls');
        var panel    = targetId ? document.getElementById(targetId) : null;
        if (!panel)  { return; }
        var isOpen = panel.classList.contains('is-open');
        panel.classList.toggle('is-open', !isOpen);
        btn.setAttribute('aria-expanded', String(!isOpen));
    });

    // ── 6. Search input keyboard shortcut ────────────────────────────
    // Focus search input when ⌘K or Ctrl+K is pressed.

    document.addEventListener('keydown', function (e) {
        if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
            var input = document.querySelector('.moga-db-toolbar__search-input');
            if (input) {
                e.preventDefault();
                input.focus();
                input.select();
            }
        }
    });

    // ── 7. Resend notification (notifications log) ────────────────
    // Handles click on .moga-nl-resend-btn buttons in the log table.

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.moga-nl-resend-btn');
        if (!btn) { return; }

        var logId = btn.getAttribute('data-log-id');
        var nonce = btn.getAttribute('data-nonce');
        if (!logId || !nonce) { return; }

        if (!confirm(mogaDashboardData.i18n && mogaDashboardData.i18n.resendConfirm
            ? mogaDashboardData.i18n.resendConfirm
            : 'Resend this notification?')) { return; }

        btn.disabled = true;
        btn.style.opacity = '0.5';

        var body = new FormData();
        body.append('action', 'moga_resend_notification');
        body.append('nonce',  nonce);
        body.append('log_id', logId);

        fetch(cfg.ajaxUrl, { method: 'POST', body: body })
            .then(function (r) { return r.json(); })
            .then(function (json) {
                if (json.success) {
                    btn.style.opacity = '1';
                    btn.title = json.data && json.data.message ? json.data.message : 'Resent!';
                    btn.style.color = '#10b981';
                } else {
                    alert(json.data && json.data.message ? json.data.message : 'Failed to resend.');
                    btn.disabled = false;
                    btn.style.opacity = '1';
                }
            })
            .catch(function () {
                alert('Request failed. Please try again.');
                btn.disabled = false;
                btn.style.opacity = '1';
            });
    });

}());
