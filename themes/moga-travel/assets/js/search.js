/**
 * Moga Travel — Search Page JS
 *
 * Handles:
 *   - Filter accordion: open/close with localStorage persistence
 *   - Sort dropdown: reloads page with new sort param on change
 *   - View toggle: saves grid/list preference to localStorage
 *   - Mobile sidebar: show/hide on toggle button click
 *
 * @package MogaTravel
 * @since   1.0.0
 */

( function() {
    'use strict';

    // ============================================================
    // CONSTANTS
    // ============================================================

    var STORAGE_KEY_ACCORDION = 'mogaFilterAccordion';
    var STORAGE_KEY_VIEW      = 'mogaViewPreference';

    // Default open/closed state per group key (matches data-group attributes).
    var ACCORDION_DEFAULTS = {
        price         : true,
        property_type : true,
        tour_category : true,
        rating        : true,
        location      : false,
        amenities     : false,
        difficulty    : false,
        duration      : false,
        tour_type     : false,
    };


    // ============================================================
    // HELPERS
    // ============================================================

    /**
     * Read accordion state from localStorage.
     * Merges with defaults so any new group always has a state.
     */
    function getAccordionState() {
        try {
            var stored = localStorage.getItem( STORAGE_KEY_ACCORDION );
            if ( stored ) {
                return Object.assign( {}, ACCORDION_DEFAULTS, JSON.parse( stored ) );
            }
        } catch ( e ) {}
        return Object.assign( {}, ACCORDION_DEFAULTS );
    }

    /**
     * Save accordion state object to localStorage.
     */
    function saveAccordionState( state ) {
        try {
            localStorage.setItem( STORAGE_KEY_ACCORDION, JSON.stringify( state ) );
        } catch ( e ) {}
    }

    /**
     * Open a single accordion group.
     */
    function openGroup( btn, content ) {
        content.removeAttribute( 'hidden' );
        btn.setAttribute( 'aria-expanded', 'true' );
    }

    /**
     * Close a single accordion group.
     */
    function closeGroup( btn, content ) {
        content.setAttribute( 'hidden', '' );
        btn.setAttribute( 'aria-expanded', 'false' );
    }


    // ============================================================
    // ACCORDION
    // ============================================================

    function initAccordion() {
        var groups = document.querySelectorAll( '.moga-filter-group[data-group]' );
        var state  = getAccordionState();

        groups.forEach( function( group ) {
            var groupKey = group.getAttribute( 'data-group' );
            var btn      = group.querySelector( '.moga-filter-group__title' );
            var content  = group.querySelector( '.moga-filter-group__content' );

            if ( ! btn || ! content ) return;

            // Apply saved or default state on page load.
            var isOpen = state.hasOwnProperty( groupKey )
                ? state[ groupKey ]
                : ( ACCORDION_DEFAULTS[ groupKey ] !== false );

            if ( isOpen ) {
                openGroup( btn, content );
            } else {
                closeGroup( btn, content );
            }

            // Toggle on click and persist.
            btn.addEventListener( 'click', function() {
                var currentlyOpen = btn.getAttribute( 'aria-expanded' ) === 'true';

                if ( currentlyOpen ) {
                    closeGroup( btn, content );
                    state[ groupKey ] = false;
                } else {
                    openGroup( btn, content );
                    state[ groupKey ] = true;
                }

                saveAccordionState( state );
            } );
        } );
    }


    // ============================================================
    // SORT DROPDOWN
    // ============================================================

    function initSort() {
        var sortSelect = document.getElementById( 'moga-sort' );
        if ( ! sortSelect ) return;

        sortSelect.addEventListener( 'change', function() {
            var url = new URL( window.location.href );
            url.searchParams.set( 'sort', this.value );
            url.searchParams.delete( 'paged' );
            window.location.href = url.toString();
        } );
    }


    // ============================================================
    // VIEW TOGGLE
    // ============================================================

    function initViewToggle() {
        var toggleBtns = document.querySelectorAll( '.moga-view-toggle__btn' );

        toggleBtns.forEach( function( btn ) {
            btn.addEventListener( 'click', function() {
                var url  = new URL( this.href );
                var view = url.searchParams.get( 'view' );
                if ( view ) {
                    try {
                        localStorage.setItem( STORAGE_KEY_VIEW, view );
                    } catch ( e ) {}
                }
            } );
        } );
    }


    // ============================================================
    // MOBILE SIDEBAR TOGGLE
    // ============================================================

    function initMobileSidebar() {
        var filterToggle  = document.getElementById( 'moga-filter-toggle' );
        var filterSidebar = document.getElementById( 'moga-search-sidebar' );

        if ( ! filterToggle || ! filterSidebar ) return;

        filterToggle.addEventListener( 'click', function() {
            var isOpen = filterSidebar.classList.contains( 'is-open' );
            if ( isOpen ) {
                filterSidebar.classList.remove( 'is-open' );
                filterToggle.setAttribute( 'aria-expanded', 'false' );
            } else {
                filterSidebar.classList.add( 'is-open' );
                filterToggle.setAttribute( 'aria-expanded', 'true' );
            }
        } );

        // Close sidebar when clicking outside on mobile.
        document.addEventListener( 'click', function( e ) {
            if (
                filterSidebar.classList.contains( 'is-open' ) &&
                ! filterSidebar.contains( e.target ) &&
                ! filterToggle.contains( e.target )
            ) {
                filterSidebar.classList.remove( 'is-open' );
                filterToggle.setAttribute( 'aria-expanded', 'false' );
            }
        } );
    }


    // ============================================================
    // BOOT
    // ============================================================

    document.addEventListener( 'DOMContentLoaded', function() {
        initAccordion();
        initSort();
        initViewToggle();
        initMobileSidebar();
    } );

} )();