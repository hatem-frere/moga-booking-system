/**
 * Moga Travel — Booking & Single Property Page JS
 *
 * Path: themes/moga-travel/assets/js/booking.js
 *
 * Handles:
 *   01. GLightbox init — property gallery + thumbnail strip
 *   02. Flatpickr date pickers — linked check-in / check-out
 *   03. Price breakdown — shows immediately on load, updates live
 *   04. Guest counter — +/- buttons
 *   05. Description read-more toggle
 *   06. Amenities show-all toggle
 *   07. Mobile sticky bar — show/hide on scroll
 *   08. Section nav — highlight active section on scroll
 *   09. Share button — Web Share API + clipboard fallback
 *
 * @package MogaTravel
 * @since   1.0.0
 */

( function () {
    'use strict';


    // ============================================================
    // READ CONFIG FROM JSON BLOCK
    // ============================================================

    function getConfig() {
        var el = document.getElementById( 'moga-booking-config' );
        if ( ! el ) return null;
        try { return JSON.parse( el.textContent ); } catch ( e ) { return null; }
    }


    // ============================================================
    // 01. GLIGHTBOX
    // ============================================================

    function initGallery() {
        if ( typeof GLightbox === 'undefined' ) return;

        var first = document.querySelector( '[data-gallery]' );
        if ( ! first ) return;

        var key = first.getAttribute( 'data-gallery' );

        GLightbox( {
            selector:        '[data-gallery="' + key + '"]',
            touchNavigation: true,
            loop:            true,
            autoplayVideos:  false,
            openEffect:      'fade',
            closeEffect:     'fade',
        } );

        // "View all" button — mobile.
        var btn = document.getElementById( 'moga-gallery-view-all' );
        if ( btn ) {
            btn.addEventListener( 'click', function () {
                var link = document.querySelector( '[data-gallery="' + key + '"]' );
                if ( link ) link.click();
            } );
        }
    }


    // ============================================================
    // 02. FLATPICKR DATE PICKERS
    // ============================================================

    function initDatePickers( config ) {
        if ( typeof flatpickr === 'undefined' || ! config ) return;

        var checkinEl  = document.getElementById( 'moga-checkin' );
        var checkoutEl = document.getElementById( 'moga-checkout' );
        if ( ! checkinEl || ! checkoutEl ) return;

        var today = new Date();
        today.setHours( 0, 0, 0, 0 );

        var shared = {
            dateFormat:    'Y-m-d',
            altInput:      true,
            altFormat:     'M j, Y',
            minDate:       today,
            disableMobile: false,
        };

        if ( config.maxStay > 0 ) {
            var maxD = new Date( today.getTime() + config.maxStay * 86400000 );
            shared.maxDate = maxD;
        }

        var checkoutPicker = flatpickr( checkoutEl, Object.assign( {}, shared, {
            onClose: function () {
                updatePriceBreakdown( config );
            },
        } ) );

        flatpickr( checkinEl, Object.assign( {}, shared, {
            onClose: function ( dates ) {
                if ( ! dates[0] ) return;
                var minOut = new Date( dates[0].getTime() );
                minOut.setDate( minOut.getDate() + ( config.minStay || 1 ) );
                checkoutPicker.set( 'minDate', minOut );
                if ( ! checkoutEl.value ) checkoutPicker.open();
                updatePriceBreakdown( config );
            },
        } ) );

        // If dates pre-filled from URL, update immediately.
        if ( checkinEl.value && checkoutEl.value ) {
            updatePriceBreakdown( config );
        }
    }


    // ============================================================
    // 03. PRICE BREAKDOWN
    // ============================================================

    function calcNights( inStr, outStr ) {
        if ( ! inStr || ! outStr ) return 0;
        var diff = new Date( outStr ) - new Date( inStr );
        return Math.max( 0, Math.round( diff / 86400000 ) );
    }

    function fmt( amount, currency ) {
        var sym = ( window.mogaData && window.mogaData.currencySymbol )
            ? window.mogaData.currencySymbol
            : currency + ' ';
        return sym + amount.toLocaleString( undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 } );
    }

    function updatePriceBreakdown( config ) {
        var inEl  = document.getElementById( 'moga-checkin' );
        var outEl = document.getElementById( 'moga-checkout' );
        if ( ! inEl || ! outEl ) return;

        var nights = calcNights( inEl.value, outEl.value );
        // Fall back to 1 night default when no dates selected.
        if ( nights <= 0 ) nights = 1;

        var ppn      = config.pricePerNight || 0;
        var discount = config.discount      || 0;
        var currency = config.currency      || '';
        var subtotal = ppn * nights;
        var disc     = discount > 0 ? subtotal * ( discount / 100 ) : 0;
        var total    = subtotal - disc;

        var label = document.getElementById( 'moga-nights-label' );
        if ( label ) {
            label.textContent = fmt( ppn, currency ) + ' \u00d7 '
                + nights + ( nights === 1 ? ' night' : ' nights' );
        }

        var subEl = document.getElementById( 'moga-breakdown-subtotal' );
        if ( subEl ) subEl.textContent = fmt( subtotal, currency );

        var discEl = document.getElementById( 'moga-breakdown-discount' );
        if ( discEl ) discEl.textContent = '\u2212' + fmt( disc, currency );

        var totEl = document.getElementById( 'moga-breakdown-total' );
        if ( totEl ) totEl.textContent = fmt( total, currency );

        // Always visible — remove hidden if present.
        var bd = document.getElementById( 'moga-price-breakdown' );
        if ( bd ) bd.removeAttribute( 'hidden' );
    }


    // ============================================================
    // 04. GUEST COUNTER
    // ============================================================

    function initGuestCounter( config ) {
        var minus   = document.getElementById( 'moga-guests-minus' );
        var plus    = document.getElementById( 'moga-guests-plus' );
        var display = document.getElementById( 'moga-guests-display' );
        var input   = document.getElementById( 'moga-guests-input' );
        if ( ! minus || ! plus || ! display || ! input ) return;

        var max   = config ? ( config.maxGuests || 10 ) : 10;
        var count = parseInt( input.value, 10 ) || 1;

        function render() {
            display.textContent = count + ( count === 1 ? ' guest' : ' guests' );
            input.value         = count;
            minus.disabled      = ( count <= 1 );
            plus.disabled       = ( count >= max );
        }

        minus.addEventListener( 'click', function () { if ( count > 1 )   { count--; render(); } } );
        plus.addEventListener(  'click', function () { if ( count < max ) { count++; render(); } } );

        render();
    }


    // ============================================================
    // 05. DESCRIPTION READ MORE
    // ============================================================

    function initDescriptionToggle() {
        var btn     = document.getElementById( 'moga-description-toggle' );
        var content = document.getElementById( 'moga-description-content' );
        if ( ! btn || ! content ) return;

        btn.addEventListener( 'click', function () {
            var open = btn.getAttribute( 'aria-expanded' ) === 'true';
            if ( open ) {
                content.classList.remove( 'moga-property-description--expanded' );
                content.classList.add( 'moga-property-description--collapsed' );
                btn.setAttribute( 'aria-expanded', 'false' );
                btn.querySelector( 'svg' ).style.transform = '';
            } else {
                content.classList.remove( 'moga-property-description--collapsed' );
                content.classList.add( 'moga-property-description--expanded' );
                btn.setAttribute( 'aria-expanded', 'true' );
                btn.querySelector( 'svg' ).style.transform = 'rotate(180deg)';
            }
            // Update button text node (first text node).
            var textNode = Array.from( btn.childNodes ).find( function( n ) { return n.nodeType === 3; } );
            if ( textNode ) textNode.textContent = open ? ' Show more ' : ' Show less ';
        } );
    }


    // ============================================================
    // 06. AMENITIES SHOW ALL
    // ============================================================

    function initAmenitiesToggle() {
        var btn  = document.getElementById( 'moga-amenities-toggle' );
        var grid = document.getElementById( 'moga-amenities-grid' );
        if ( ! btn || ! grid ) return;

        btn.addEventListener( 'click', function () {
            var open   = btn.getAttribute( 'aria-expanded' ) === 'true';
            var hidden = grid.querySelectorAll( '.moga-amenity-item--hidden' );
            hidden.forEach( function( el ) {
                el.classList.toggle( 'is-visible', ! open );
            } );
            btn.setAttribute( 'aria-expanded', open ? 'false' : 'true' );
        } );
    }


    // ============================================================
    // 07. MOBILE STICKY BAR
    // ============================================================

    function initMobileStickyBar() {
        var bar     = document.getElementById( 'moga-mobile-booking-bar' );
        var sidebar = document.getElementById( 'moga-booking-sidebar' );
        if ( ! bar ) return;

        var threshold = 300;

        if ( sidebar ) {
            var rect = sidebar.getBoundingClientRect();
            threshold = rect.bottom + window.pageYOffset;
        }

        function onScroll() {
            if ( window.pageYOffset > threshold ) {
                bar.style.display = 'flex';
                bar.removeAttribute( 'aria-hidden' );
            } else {
                bar.style.display = 'none';
                bar.setAttribute( 'aria-hidden', 'true' );
            }
        }

        window.addEventListener( 'scroll', onScroll, { passive: true } );
        onScroll();
    }


    // ============================================================
    // 08. SECTION NAV — ACTIVE ON SCROLL
    // ============================================================

    function initSectionNav() {
        var links    = document.querySelectorAll( '.moga-section-nav__link' );
        var sections = [];

        links.forEach( function ( link ) {
            var href = link.getAttribute( 'href' );
            if ( href && href.startsWith( '#' ) ) {
                var el = document.getElementById( href.slice(1) );
                if ( el ) sections.push( { link: link, el: el } );
            }
        } );

        if ( sections.length === 0 ) return;

        function onScroll() {
            var scrollY = window.pageYOffset + 120; // offset for sticky header + nav
            var active  = null;

            sections.forEach( function ( item ) {
                if ( item.el.offsetTop <= scrollY ) {
                    active = item;
                }
            } );

            links.forEach( function ( l ) { l.classList.remove( 'is-active' ); } );
            if ( active ) active.link.classList.add( 'is-active' );
        }

        window.addEventListener( 'scroll', onScroll, { passive: true } );
        onScroll();

        // Smooth scroll on click.
        links.forEach( function ( link ) {
            link.addEventListener( 'click', function ( e ) {
                var href = link.getAttribute( 'href' );
                if ( href && href.startsWith( '#' ) ) {
                    e.preventDefault();
                    var target = document.getElementById( href.slice(1) );
                    if ( target ) {
                        var top = target.offsetTop - 120;
                        window.scrollTo( { top: top, behavior: 'smooth' } );
                    }
                }
            } );
        } );
    }


    // ============================================================
    // 09. SHARE BUTTON
    // ============================================================

    function initShareButton() {
        var btn = document.getElementById( 'moga-share-btn' );
        if ( ! btn ) return;

        btn.addEventListener( 'click', function () {
            var url   = window.location.href;
            var title = document.title;

            if ( navigator.share ) {
                navigator.share( { title: title, url: url } ).catch( function () {} );
            } else if ( navigator.clipboard ) {
                navigator.clipboard.writeText( url ).then( function () {
                    var original = btn.innerHTML;
                    btn.textContent = 'Link copied!';
                    setTimeout( function () { btn.innerHTML = original; }, 2000 );
                } ).catch( function () {} );
            }
        } );
    }


    // ============================================================
    // BOOT
    // ============================================================

    document.addEventListener( 'DOMContentLoaded', function () {
        var config = getConfig();

        initGallery();
        initDatePickers( config );
        initGuestCounter( config );
        updatePriceBreakdown( config ); // Show default 1-night price on load.
        initDescriptionToggle();
        initAmenitiesToggle();
        initMobileStickyBar();
        initSectionNav();
        initShareButton();
    } );

} )();