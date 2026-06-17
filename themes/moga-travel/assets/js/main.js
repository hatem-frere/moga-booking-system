/**
 * Moga Travel — Main JavaScript
 *
 * Core frontend interactions:
 *   - Mobile menu toggle
 *   - Header dropdown menus
 *   - Sticky header behavior
 *   - Alert dismissal
 *   - Smooth scroll
 *   - Back to top button
 *
 * @package MogaTravel
 * @since   1.0.0
 */

( function( $ ) {
    'use strict';

    // ============================================================
    // DOCUMENT READY
    // ============================================================
    $( document ).ready( function() {

        Moga.mobileMenu.init();
        Moga.dropdown.init();
        Moga.stickyHeader.init();
        Moga.alerts.init();
        Moga.backToTop.init();
        Moga.smoothScroll.init();

    } );


    // ============================================================
    // MOGA NAMESPACE
    // ============================================================
    window.Moga = window.Moga || {};


    // ============================================================
    // 01. MOBILE MENU
    // ============================================================
    Moga.mobileMenu = {

        toggle:   $( '#moga-nav-toggle' ),
        menu:     $( '#moga-mobile-menu' ),
        isOpen:   false,

        init: function() {
            if ( ! this.toggle.length ) return;
            this.bindEvents();
        },

        bindEvents: function() {
            var self = this;

            // Toggle button click.
            this.toggle.on( 'click', function() {
                self.isOpen ? self.close() : self.open();
            } );

            // Close on outside click.
            $( document ).on( 'click', function( e ) {
                if ( self.isOpen
                    && ! $( e.target ).closest( '#moga-mobile-menu, #moga-nav-toggle' ).length
                ) {
                    self.close();
                }
            } );

            // Close on ESC key.
            $( document ).on( 'keydown', function( e ) {
                if ( e.key === 'Escape' && self.isOpen ) {
                    self.close();
                    self.toggle.focus();
                }
            } );
        },

        open: function() {
            this.isOpen = true;
            this.toggle.addClass( 'is-open' ).attr( 'aria-expanded', 'true' );
            this.menu.addClass( 'is-open' ).attr( 'aria-hidden', 'false' );
            $( 'body' ).addClass( 'moga-menu-open' );
        },

        close: function() {
            this.isOpen = false;
            this.toggle.removeClass( 'is-open' ).attr( 'aria-expanded', 'false' );
            this.menu.removeClass( 'is-open' ).attr( 'aria-hidden', 'true' );
            $( 'body' ).removeClass( 'moga-menu-open' );
        },
    };


    // ============================================================
    // 02. DROPDOWN MENUS
    // ============================================================
    Moga.dropdown = {

        init: function() {
            this.bindAvatarDropdown();
        },

        bindAvatarDropdown: function() {
            var btn      = $( '.moga-header__avatar-btn' );
            var dropdown = $( '.moga-header__dropdown' );

            if ( ! btn.length ) return;

            btn.on( 'click', function( e ) {
                e.stopPropagation();
                var isOpen = dropdown.hasClass( 'is-open' );
                dropdown.toggleClass( 'is-open' );
                btn.attr( 'aria-expanded', ! isOpen );
            } );

            // Close on outside click.
            $( document ).on( 'click', function() {
                dropdown.removeClass( 'is-open' );
                btn.attr( 'aria-expanded', 'false' );
            } );

            // Close on ESC.
            $( document ).on( 'keydown', function( e ) {
                if ( e.key === 'Escape' ) {
                    dropdown.removeClass( 'is-open' );
                    btn.attr( 'aria-expanded', 'false' );
                }
            } );
        },
    };


    // ============================================================
    // 03. STICKY HEADER
    // ============================================================
    Moga.stickyHeader = {

        header:      $( '#moga-header' ),
        scrolled:    false,
        threshold:   50,

        init: function() {
            if ( ! this.header.length ) return;
            this.bindEvents();
            this.check(); // Run on load.
        },

        bindEvents: function() {
            var self = this;
            $( window ).on( 'scroll.mogaHeader', function() {
                self.check();
            } );
        },

        check: function() {
            var scrollTop = $( window ).scrollTop();
            if ( scrollTop > this.threshold ) {
                this.header.addClass( 'is-scrolled' );
            } else {
                this.header.removeClass( 'is-scrolled' );
            }
        },
    };


    // ============================================================
    // 04. ALERT DISMISSAL
    // ============================================================
    Moga.alerts = {

        init: function() {
            $( document ).on( 'click', '.moga-alert__close', function() {
                $( this ).closest( '.moga-alert' ).fadeOut( 300, function() {
                    $( this ).remove();
                } );
            } );
        },
    };


    // ============================================================
    // 05. BACK TO TOP
    // ============================================================
    Moga.backToTop = {

        btn:       null,
        threshold: 400,

        init: function() {
            this.createButton();
            this.bindEvents();
        },

        createButton: function() {
            if ( $( '#moga-back-to-top' ).length ) return;

            this.btn = $( '<button>', {
                id:             'moga-back-to-top',
                class:          'moga-back-to-top',
                'aria-label':   mogaData.i18n.loading || 'Back to top',
                html: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="18 15 12 9 6 15"/></svg>',
            } );

            $( 'body' ).append( this.btn );
        },

        bindEvents: function() {
            var self = this;

            $( window ).on( 'scroll.mogaBackToTop', function() {
                if ( $( window ).scrollTop() > self.threshold ) {
                    self.btn.addClass( 'is-visible' );
                } else {
                    self.btn.removeClass( 'is-visible' );
                }
            } );

            $( document ).on( 'click', '#moga-back-to-top', function() {
                $( 'html, body' ).animate( { scrollTop: 0 }, 400 );
            } );
        },
    };


    // ============================================================
    // 06. SMOOTH SCROLL
    // ============================================================
    Moga.smoothScroll = {

        init: function() {
            $( document ).on( 'click', 'a[href^="#"]', function( e ) {
                var target = $( $( this ).attr( 'href' ) );
                if ( ! target.length ) return;

                e.preventDefault();
                var headerHeight = $( '#moga-header' ).outerHeight() || 70;

                $( 'html, body' ).animate( {
                    scrollTop: target.offset().top - headerHeight - 16,
                }, 500 );
            } );
        },
    };


    // ============================================================
    // 07. TABS
    // ============================================================
    Moga.tabs = {

        init: function( context ) {
            var scope = context || document;

            $( scope ).on( 'click', '.moga-tabs__nav-item', function() {
                var tab     = $( this );
                var target  = tab.data( 'tab' );
                var wrapper = tab.closest( '.moga-tabs' );

                // Update nav items.
                wrapper.find( '.moga-tabs__nav-item' ).removeClass( 'is-active' );
                tab.addClass( 'is-active' );

                // Update panels.
                wrapper.find( '.moga-tabs__panel' ).removeClass( 'is-active' );
                wrapper.find( '[data-panel="' + target + '"]' ).addClass( 'is-active' );
            } );
        },
    };


    // ============================================================
    // 08. UTILITY FUNCTIONS
    // ============================================================

    /**
     * Format a price with currency symbol.
     *
     * @param  {number} amount
     * @return {string}
     */
    Moga.formatPrice = function( amount ) {
        var symbol   = mogaData.currencySymbol || '$';
        var position = mogaData.currencyPosition || 'before';
        var formatted = parseFloat( amount ).toFixed( 2 );

        return position === 'before'
            ? symbol + formatted
            : formatted + symbol;
    };

    /**
     * Show a temporary notice on the page.
     *
     * @param {string} message
     * @param {string} type     success | danger | warning | info
     * @param {number} duration Milliseconds before auto-dismiss.
     */
    Moga.notify = function( message, type, duration ) {
        type     = type     || 'info';
        duration = duration || 4000;

        var notice = $( '<div>', {
            class: 'moga-alert moga-alert--' + type,
            html:  message
                + '<button class="moga-alert__close" aria-label="Dismiss">&times;</button>',
        } );

        $( '.moga-wrapper' ).prepend( notice );
        notice.hide().slideDown( 200 );

        if ( duration > 0 ) {
            setTimeout( function() {
                notice.slideUp( 200, function() {
                    $( this ).remove();
                } );
            }, duration );
        }
    };

    /**
     * Simple AJAX helper.
     *
     * @param  {string}   action   WordPress AJAX action name.
     * @param  {object}   data     Data to send.
     * @param  {function} callback Success callback.
     */
    Moga.ajax = function( action, data, callback ) {
        $.post(
            mogaData.ajaxUrl,
            $.extend( { action: action, nonce: mogaData.nonce }, data ),
            callback
        );
    };

} )( jQuery );