/**
 * Moga Travel — Search Page JS
 *
 * Handles grid/list view toggle.
 * Full filter and sort logic added in Step 19/21.
 *
 * @package MogaTravel
 * @since   1.0.0
 */

( function() {
    'use strict';

    /**
     * Save view preference to localStorage
     * so it persists across page loads.
     */
    document.addEventListener( 'DOMContentLoaded', function() {

        var toggleBtns = document.querySelectorAll( '.moga-view-toggle__btn' );

        toggleBtns.forEach( function( btn ) {
            btn.addEventListener( 'click', function() {
                var url   = new URL( this.href );
                var view  = url.searchParams.get( 'view' );
                if ( view ) {
                    localStorage.setItem( 'mogaViewPreference', view );
                }
            } );
        } );

    } );

} )();