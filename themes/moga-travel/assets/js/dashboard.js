/**
 * Dashboard JS
 *
 * Handles:
 *  1. AJAX tab switching (no full page reload on nav click)
 *  2. Mobile sidebar open/close via hamburger button
 *  3. URL update via history.pushState on tab switch
 *  4. Active nav link highlight sync
 *  5. Page title update in topbar on tab switch
 *
 * Depends on mogaDashboardData (wp_localize_script):
 *   - mogaDashboardData.ajaxUrl
 *   - mogaDashboardData.nonce
 *   - mogaDashboardData.tab    (initial active tab)
 *
 * @package MogaTravel
 * @since   1.0.0
 */

(function ($) {
    'use strict';

    if (typeof mogaDashboardData === 'undefined') return;

    var ajaxUrl    = mogaDashboardData.ajaxUrl;
    var nonce      = mogaDashboardData.nonce;
    var activeTab  = mogaDashboardData.tab || 'overview';
    var isLoading  = false;

    var $content   = $('#moga-db-content');
    var $sidebar   = $('#moga-db-sidebar');
    var $backdrop  = $('#moga-db-backdrop');
    var $hamburger = $('#moga-db-hamburger');
    var $pageTitle = $('#moga-db-page-title');

    // ----------------------------------------------------------------
    // 1. AJAX Tab Switching
    // ----------------------------------------------------------------

    $(document).on('click', '.moga-db__nav-link[data-tab]', function (e) {
        var $link = $(this);
        var tab   = $link.data('tab');

        // External links (System Management) — let browser handle.
        if ($link.hasClass('moga-db__nav-link--external')) return;

        e.preventDefault();

        if (tab === activeTab || isLoading) return;

        loadTab(tab);

        // Close mobile sidebar after nav click.
        closeSidebar();
    });

    function loadTab(tab) {
        isLoading = true;
        $content.addClass('moga-db-loading');

        $.ajax({
            url:    ajaxUrl,
            method: 'POST',
            data: {
                action: 'moga_dashboard_tab',
                nonce:  nonce,
                tab:    tab,
            },
            success: function (response) {
                if (response.success && response.data && response.data.html) {
                    $content.html(response.data.html);
                    activeTab = tab;
                    updateActiveLink(tab);
                    updatePageTitle(tab);
                    updateUrl(tab);
                    // Scroll content area to top.
                    $content.scrollTop(0);
                    window.scrollTo(0, 0);
                } else {
                    // Fallback: full page navigation.
                    window.location.href = buildTabUrl(tab);
                }
            },
            error: function () {
                // Fallback on network error.
                window.location.href = buildTabUrl(tab);
            },
            complete: function () {
                isLoading = false;
                $content.removeClass('moga-db-loading');
            }
        });
    }

    function buildTabUrl(tab) {
        var url = window.location.pathname + window.location.search;
        var params = new URLSearchParams(window.location.search);
        params.set('tab', tab);
        return window.location.pathname + '?' + params.toString();
    }

    function updateUrl(tab) {
        if (history.pushState) {
            var newUrl = buildTabUrl(tab);
            history.pushState({ tab: tab }, '', newUrl);
        }
    }

    function updateActiveLink(tab) {
        $('.moga-db__nav-link').removeClass('is-active').removeAttr('aria-current');
        $('.moga-db__nav-link[data-tab="' + tab + '"]')
            .addClass('is-active')
            .attr('aria-current', 'page');

        // If admin: switch to the pill group that contains this tab.
        var $panel = $('.moga-db__group-panel .moga-db__nav-link[data-tab="' + tab + '"]').closest('.moga-db__group-panel');
        if ($panel.length) {
            var group = $panel.data('group-panel');
            $('.moga-db__group-pill').removeClass('is-active').attr('aria-selected', 'false');
            $('.moga-db__group-pill[data-group="' + group + '"]').addClass('is-active').attr('aria-selected', 'true');
            $('.moga-db__group-panel').removeClass('is-active');
            $panel.addClass('is-active');
            try { sessionStorage.setItem('moga_db_group', group); } catch(e) {}
        }
    }

    function updatePageTitle(tab) {
        var titles = {
            'overview':         mogaData && mogaData.i18n ? mogaData.i18n.overview         : 'Overview',
            'bookings':         'My Bookings',
            'saved':            'Saved',
            'reviews':          'My Reviews',
            'wallet':           'Wallet & Credits',
            'personal-details': 'Personal Details',
            'security':         'Security',
            'notifications':    'Notifications',
            'preferences':      'Preferences',
            'become-vendor':    'Become a Vendor',
            'report-problem':   'Report a Problem',
            'delete-account':   'Delete Account',
            'properties':       'My Properties',
            'tours':            'My Tours',
            'buses':            'My Buses',
            'vendor-bookings':  'Bookings',
            'calendar':         'Calendar & Availability',
            'seat-map':         'Seat Maps',
            'earnings':         'Earnings & Commissions',
            'payout-settings':  'Payout Settings',
            'reviews-received': 'Reviews Received',
            'all-properties':   'All Properties',
            'all-tours':        'All Tours',
            'all-bookings':     'All Bookings',
            'vendors':          'Vendors',
            'commissions':      'Commissions',
            'reports':          'Reports',
            'notifications-log':'Notifications Log',
        };
        $pageTitle.text(titles[tab] || 'Dashboard');
    }

    // Handle browser back/forward.
    window.addEventListener('popstate', function (e) {
        if (e.state && e.state.tab) {
            loadTab(e.state.tab);
        }
    });

    // ----------------------------------------------------------------
    // 2. Mobile Sidebar
    // ----------------------------------------------------------------

    $hamburger.on('click', function () {
        var isOpen = $sidebar.hasClass('is-open');
        if (isOpen) {
            closeSidebar();
        } else {
            openSidebar();
        }
    });

    $backdrop.on('click', closeSidebar);

    $(document).on('keydown', function (e) {
        if (e.key === 'Escape' && $sidebar.hasClass('is-open')) {
            closeSidebar();
            $hamburger.focus();
        }
    });

    function openSidebar() {
        $sidebar.addClass('is-open');
        $backdrop.addClass('is-active');
        $hamburger.attr('aria-expanded', 'true');
        // Prevent body scroll while sidebar is open.
        $('body').css('overflow', 'hidden');
    }

    function closeSidebar() {
        $sidebar.removeClass('is-open');
        $backdrop.removeClass('is-active');
        $hamburger.attr('aria-expanded', 'false');
        $('body').css('overflow', '');
    }

    // ----------------------------------------------------------------
    // 4. Admin pill group switcher
    // ----------------------------------------------------------------

    $(document).on('click', '.moga-db__group-pill', function () {
        var $pill  = $(this);
        var group  = $pill.data('group');

        if ($pill.hasClass('is-active')) return;

        // Update pill active state.
        $('.moga-db__group-pill').removeClass('is-active').attr('aria-selected', 'false');
        $pill.addClass('is-active').attr('aria-selected', 'true');

        // Show matching group panel, hide others.
        $('.moga-db__group-panel').removeClass('is-active');
        $('.moga-db__group-panel[data-group-panel="' + group + '"]').addClass('is-active');

        // Persist active group in sessionStorage so it survives
        // AJAX tab switches within the same session.
        try { sessionStorage.setItem('moga_db_group', group); } catch(e) {}

        // Update sidebar data attribute for PHP re-renders.
        $('#moga-db-sidebar').attr('data-active-group', group);
    });

    // Restore active group from sessionStorage on page load.
    (function () {
        try {
            var savedGroup = sessionStorage.getItem('moga_db_group');
            if (savedGroup) {
                var $pill = $('.moga-db__group-pill[data-group="' + savedGroup + '"]');
                if ($pill.length && ! $pill.hasClass('is-active')) {
                    $pill.trigger('click');
                }
            }
        } catch(e) {}
    })();

    // Add a CSS loading class that dims the content area while AJAX runs.
    var style = document.createElement('style');
    style.textContent = '.moga-db-loading { opacity: 0.5; pointer-events: none; transition: opacity 0.15s ease; }';
    document.head.appendChild(style);

})(jQuery);
