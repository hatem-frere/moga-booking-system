/**
 * Moga Admin — Location Settings JS
 *
 * Path: admin/assets/js/admin-locations.js
 *
 * Handles:
 *   01. Import wizard  — sequential AJAX batches with live progress bar
 *   02. Reset          — confirm and truncate all location tables
 *   03. Location Editor cascade — country → province → city → district
 *   04. Location Editor CRUD   — add, edit (inline), delete with cascade
 *
 * PHP data available via mogaLocData (wp_localize_script):
 *   ajaxUrl, nonce, batchProvinces, batchCities,
 *   totalProvinces, totalCities, i18n
 *
 * @package MogaTravelCore
 * @since   1.0.0
 */

( function ( $ ) {
    'use strict';

    var L = mogaLocData; // shorthand


    // ============================================================
    // STATE
    // ============================================================

    var state = {
        selectedCountryId:  0,
        selectedProvinceId: 0,
        selectedCityId:     0,
        editingLevel:       null,  // which level's form is open
        editingId:          0,     // ID of the row being edited (0 = adding new)
    };


    // ============================================================
    // 01. IMPORT WIZARD
    // ============================================================

    $( '#moga-loc-import-btn' ).on( 'click', function () {
        $( this ).prop( 'disabled', true );
        $( '#moga-loc-reset-btn' ).prop( 'disabled', true );
        showProgress( true );
        setProgress( 0, L.i18n.importCountries );
        importCountries();
    } );

    function importCountries() {
        $.post( L.ajaxUrl, {
            action: 'moga_loc_import_countries',
            nonce:  L.nonce,
        } )
        .done( function ( r ) {
            if ( r.success ) {
                logLine( '✅ ' + r.data.count + ' countries imported.' );
                setProgress( 5, L.i18n.importProvinces );
                importProvincesBatch( 0 );
            } else {
                importFailed( r.data.message );
            }
        } )
        .fail( function () { importFailed( 'Network error (countries).' ); } );
    }

    function importProvincesBatch( offset ) {
        $.post( L.ajaxUrl, {
            action: 'moga_loc_import_provinces',
            nonce:  L.nonce,
            offset: offset,
        } )
        .done( function ( r ) {
            if ( ! r.success ) { importFailed( r.data.message ); return; }

            var d    = r.data;
            var pct  = 5 + Math.round( ( ( offset + d.imported ) / L.totalProvinces ) * 15 );
            var step = L.i18n.importProvinces + ' (' + numberFmt( offset + d.imported ) + ' / ' + numberFmt( L.totalProvinces ) + ')';
            setProgress( pct, step );

            if ( d.done ) {
                logLine( '✅ ' + numberFmt( L.totalProvinces ) + ' provinces imported.' );
                setProgress( 20, L.i18n.importCities );
                importCitiesBatch( 0 );
            } else {
                importProvincesBatch( d.next_offset );
            }
        } )
        .fail( function () { importFailed( 'Network error (provinces).' ); } );
    }

    function importCitiesBatch( offset ) {
        $.post( L.ajaxUrl, {
            action: 'moga_loc_import_cities',
            nonce:  L.nonce,
            offset: offset,
        } )
        .done( function ( r ) {
            if ( ! r.success ) { importFailed( r.data.message ); return; }

            var d    = r.data;
            var pct  = 20 + Math.round( ( ( offset + d.imported ) / L.totalCities ) * 80 );
            var step = L.i18n.importCities + ' (' + numberFmt( offset + d.imported ) + ' / ' + numberFmt( L.totalCities ) + ')';
            setProgress( Math.min( pct, 99 ), step );

            if ( d.done ) {
                setProgress( 100, L.i18n.importDone );
                logLine( '✅ ' + numberFmt( L.totalCities ) + ' cities imported.' );
                logLine( L.i18n.importDone );
                $( '#moga-loc-import-btn' ).prop( 'disabled', false );
                $( '#moga-loc-reset-btn' ).prop( 'disabled', false );
                refreshStats();
            } else {
                importCitiesBatch( d.next_offset );
            }
        } )
        .fail( function () { importFailed( 'Network error (cities).' ); } );
    }

    function importFailed( msg ) {
        logLine( '❌ ' + msg );
        $( '#moga-loc-progress-step' ).text( L.i18n.importFailed );
        $( '#moga-loc-import-btn' ).prop( 'disabled', false );
        $( '#moga-loc-reset-btn' ).prop( 'disabled', false );
    }

    function setProgress( pct, stepText ) {
        pct = Math.min( 100, Math.max( 0, pct ) );
        $( '#moga-loc-progress-fill' )
            .css( 'width', pct + '%' )
            .attr( 'aria-valuenow', pct );
        $( '#moga-loc-progress-text' ).text( pct + '%' );
        if ( stepText ) {
            $( '#moga-loc-progress-step' ).text( stepText );
        }
    }

    function showProgress( show ) {
        $( '#moga-loc-progress' ).toggle( show );
    }

    function logLine( text ) {
        var $log = $( '#moga-loc-log' );
        $log.append( '<div class="moga-loc-log__line">' + escHtml( text ) + '</div>' );
        $log.scrollTop( $log[0].scrollHeight );
    }


    // ============================================================
    // 02. RESET
    // ============================================================

    $( '#moga-loc-reset-btn' ).on( 'click', function () {
        if ( ! window.confirm( L.i18n.resetConfirm ) ) {
            return;
        }
        var $btn = $( this );
        $btn.prop( 'disabled', true ).text( L.i18n.resetting );

        $.post( L.ajaxUrl, {
            action: 'moga_loc_reset',
            nonce:  L.nonce,
        } )
        .done( function ( r ) {
            if ( r.success ) {
                logLine( '↺ ' + L.i18n.resetDone );
                showProgress( false );
                setProgress( 0, '' );
                refreshStats();
            }
            $btn.prop( 'disabled', false ).text( '↺ Reset All Data' );
        } )
        .fail( function () {
            $btn.prop( 'disabled', false ).text( '↺ Reset All Data' );
        } );
    } );

    function refreshStats() {
        $.post( L.ajaxUrl, { action: 'moga_loc_get_stats', nonce: L.nonce } )
        .done( function ( r ) {
            if ( ! r.success ) return;
            // Reload the page to update the stat cards properly.
            window.location.reload();
        } );
    }


    // ============================================================
    // 03. LOCATION EDITOR — CASCADE
    // ============================================================

    // Country dropdown change.
    $( document ).on( 'change', '#moga-loc-country-select', function () {
        var countryId = parseInt( $( this ).val(), 10 ) || 0;
        state.selectedCountryId  = countryId;
        state.selectedProvinceId = 0;
        state.selectedCityId     = 0;

        // Enable/disable country edit + delete buttons.
        $( '.moga-loc-edit-trigger[data-level="country"]' ).prop( 'disabled', ! countryId );
        $( '.moga-loc-delete-trigger[data-level="country"]' ).prop( 'disabled', ! countryId );

        // Reset and hide lower panels.
        hidePanel( 'province' );
        hidePanel( 'city' );
        hidePanel( 'district' );

        if ( ! countryId ) return;

        showPanel( 'province' );
        setSpinner( 'province', true );

        $.post( L.ajaxUrl, {
            action:     'moga_loc_get_provinces',
            nonce:      L.nonce,
            country_id: countryId,
        } )
        .done( function ( r ) {
            setSpinner( 'province', false );
            if ( ! r.success ) return;
            renderList( 'province', r.data.provinces, L.i18n.noProvinces );
        } )
        .fail( function () { setSpinner( 'province', false ); } );
    } );

    // Province row click (event delegation).
    $( document ).on( 'click', '#moga-loc-province-list .moga-loc-list-row__name', function () {
        var $row       = $( this ).closest( '.moga-loc-list-row' );
        var provinceId = parseInt( $row.data( 'id' ), 10 ) || 0;
        if ( ! provinceId ) return;

        $( '#moga-loc-province-list .moga-loc-list-row' ).removeClass( 'is-selected' );
        $row.addClass( 'is-selected' );

        state.selectedProvinceId = provinceId;
        state.selectedCityId     = 0;

        hidePanel( 'city' );
        hidePanel( 'district' );
        showPanel( 'city' );
        setSpinner( 'city', true );

        $.post( L.ajaxUrl, {
            action:      'moga_loc_get_cities',
            nonce:       L.nonce,
            province_id: provinceId,
        } )
        .done( function ( r ) {
            setSpinner( 'city', false );
            if ( ! r.success ) return;
            renderList( 'city', r.data.cities, L.i18n.noCities );
        } )
        .fail( function () { setSpinner( 'city', false ); } );
    } );

    // City row click.
    $( document ).on( 'click', '#moga-loc-city-list .moga-loc-list-row__name', function () {
        var $row   = $( this ).closest( '.moga-loc-list-row' );
        var cityId = parseInt( $row.data( 'id' ), 10 ) || 0;
        if ( ! cityId ) return;

        $( '#moga-loc-city-list .moga-loc-list-row' ).removeClass( 'is-selected' );
        $row.addClass( 'is-selected' );

        state.selectedCityId = cityId;

        hidePanel( 'district' );
        showPanel( 'district' );
        setSpinner( 'district', true );

        $.post( L.ajaxUrl, {
            action:  'moga_loc_get_districts',
            nonce:   L.nonce,
            city_id: cityId,
        } )
        .done( function ( r ) {
            setSpinner( 'district', false );
            if ( ! r.success ) return;
            renderList( 'district', r.data.districts, L.i18n.noDistricts );
        } )
        .fail( function () { setSpinner( 'district', false ); } );
    } );


    // ============================================================
    // 04. LOCATION EDITOR — CRUD
    // ============================================================

    // Open add/edit form.
    $( document ).on( 'click', '.moga-loc-add-trigger', function () {
        var level = $( this ).data( 'level' );
        openForm( level, 0, '' );
    } );

    $( document ).on( 'click', '.moga-loc-edit-trigger', function () {
        var level = $( this ).data( 'level' );
        if ( 'country' === level ) {
            var $sel  = $( '#moga-loc-country-select' );
            var id    = parseInt( $sel.val(), 10 );
            var name  = $sel.find( ':selected' ).text().replace( /\s*\([^)]*\)$/, '' ).trim();
            var iso   = $sel.find( ':selected' ).data( 'iso' ) || '';
            if ( ! id ) return;
            openForm( level, id, name, { iso: iso } );
        }
    } );

    $( document ).on( 'click', '.moga-loc-delete-trigger', function () {
        var level = $( this ).data( 'level' );
        var id    = 0;
        if ( 'country' === level ) id = state.selectedCountryId;
        if ( ! id ) return;
        doDelete( level, id );
    } );

    // Inline list row edit/delete buttons.
    $( document ).on( 'click', '.moga-loc-row-edit', function () {
        var $row  = $( this ).closest( '.moga-loc-list-row' );
        var level = $row.data( 'level' );
        var id    = parseInt( $row.data( 'id' ), 10 );
        var name  = $row.find( '.moga-loc-list-row__name' ).text().trim();
        var extra = {};
        if ( 'city' === level ) {
            extra.lat = $row.data( 'lat' ) || '';
            extra.lng = $row.data( 'lng' ) || '';
        }
        openForm( level, id, name, extra );
    } );

    $( document ).on( 'click', '.moga-loc-row-delete', function () {
        var $row  = $( this ).closest( '.moga-loc-list-row' );
        var level = $row.data( 'level' );
        var id    = parseInt( $row.data( 'id' ), 10 );
        doDelete( level, id, $row );
    } );

    // Save form.
    $( document ).on( 'click', '.moga-loc-save-btn', function () {
        var level = $( this ).data( 'level' );
        doSave( level );
    } );

    // Cancel form.
    $( document ).on( 'click', '.moga-loc-cancel-btn', function () {
        var level = $( this ).data( 'level' );
        closeForm( level );
    } );

    /**
     * Open the inline add/edit form for a given level.
     * id = 0 means "add new"; id > 0 means "edit existing".
     */
    function openForm( level, id, currentName, extra ) {
        state.editingLevel = level;
        state.editingId    = id;
        extra              = extra || {};

        var $form = $( '#moga-loc-form-' + level );
        $form.show();

        // Pre-fill fields.
        if ( 'country' === level ) {
            $( '#moga-loc-country-name' ).val( currentName ).focus();
            $( '#moga-loc-country-iso' ).val( extra.iso || '' );
        } else if ( 'province' === level ) {
            $( '#moga-loc-province-name' ).val( currentName ).focus();
        } else if ( 'city' === level ) {
            $( '#moga-loc-city-name' ).val( currentName ).focus();
            $( '#moga-loc-city-lat' ).val( extra.lat || '' );
            $( '#moga-loc-city-lng' ).val( extra.lng || '' );
        } else if ( 'district' === level ) {
            $( '#moga-loc-district-name' ).val( currentName ).focus();
        }
    }

    function closeForm( level ) {
        $( '#moga-loc-form-' + level ).hide();
        state.editingLevel = null;
        state.editingId    = 0;
    }

    function doSave( level ) {
        var name = '';
        var data = {
            action: state.editingId ? 'moga_loc_update' : 'moga_loc_add',
            nonce:  L.nonce,
            level:  level,
            id:     state.editingId,
        };

        if ( 'country' === level ) {
            name             = $( '#moga-loc-country-name' ).val().trim();
            data.iso_code    = $( '#moga-loc-country-iso' ).val().trim().toUpperCase();
            data.name        = name;
        } else if ( 'province' === level ) {
            name          = $( '#moga-loc-province-name' ).val().trim();
            data.name     = name;
            data.parent_id = state.selectedCountryId;
        } else if ( 'city' === level ) {
            name           = $( '#moga-loc-city-name' ).val().trim();
            data.name      = name;
            data.parent_id = state.selectedProvinceId;
            data.lat       = $( '#moga-loc-city-lat' ).val().trim();
            data.lng       = $( '#moga-loc-city-lng' ).val().trim();
        } else if ( 'district' === level ) {
            name           = $( '#moga-loc-district-name' ).val().trim();
            data.name      = name;
            data.parent_id = state.selectedCityId;
        }

        if ( ! name ) {
            alert( L.i18n.enterName );
            return;
        }

        var $btn = $( '#moga-loc-form-' + level + ' .moga-loc-save-btn' );
        $btn.prop( 'disabled', true ).text( L.i18n.saving );

        $.post( L.ajaxUrl, data )
        .done( function ( r ) {
            $btn.prop( 'disabled', false ).text( L.i18n.save );
            if ( ! r.success ) {
                alert( L.i18n.errorSave + ' ' + ( r.data.message || '' ) );
                return;
            }

            closeForm( level );

            if ( 'country' === level ) {
                // Reload page to refresh country dropdown.
                window.location.reload();
            } else {
                // Refresh the list for this level.
                refreshList( level );
            }
        } )
        .fail( function () {
            $btn.prop( 'disabled', false ).text( L.i18n.save );
            alert( L.i18n.errorSave );
        } );
    }

    function doDelete( level, id, $row ) {
        if ( ! window.confirm( L.i18n.deleteConfirm ) ) return;

        $.post( L.ajaxUrl, {
            action: 'moga_loc_delete',
            nonce:  L.nonce,
            level:  level,
            id:     id,
        } )
        .done( function ( r ) {
            if ( ! r.success ) {
                alert( L.i18n.errorSave );
                return;
            }

            if ( 'country' === level ) {
                window.location.reload();
            } else if ( $row && $row.length ) {
                $row.remove();
                // If this level's list is now empty, hide lower panels.
                var $list = $( '#moga-loc-' + level + '-list' );
                if ( 0 === $list.find( '.moga-loc-list-row' ).length ) {
                    $list.html( '<p class="moga-loc-list__empty">' + escHtml( getLevelEmptyText( level ) ) + '</p>' );
                    hidePanel( getChildLevel( level ) );
                }
            } else {
                refreshList( level );
            }
        } );
    }

    /**
     * Refresh the list for a given level using the current parent ID.
     */
    function refreshList( level ) {
        var action    = '';
        var parentKey = '';
        var parentId  = 0;

        if ( 'province' === level ) {
            action    = 'moga_loc_get_provinces';
            parentKey = 'country_id';
            parentId  = state.selectedCountryId;
        } else if ( 'city' === level ) {
            action    = 'moga_loc_get_cities';
            parentKey = 'province_id';
            parentId  = state.selectedProvinceId;
        } else if ( 'district' === level ) {
            action    = 'moga_loc_get_districts';
            parentKey = 'city_id';
            parentId  = state.selectedCityId;
        }

        if ( ! parentId ) return;

        var postData = { action: action, nonce: L.nonce };
        postData[ parentKey ] = parentId;

        setSpinner( level, true );

        $.post( L.ajaxUrl, postData )
        .done( function ( r ) {
            setSpinner( level, false );
            if ( ! r.success ) return;

            var key  = level + 's'; // 'provinces', 'cities', 'districts'
            var rows = r.data[ key ] || [];
            renderList( level, rows, getLevelEmptyText( level ) );
        } )
        .fail( function () { setSpinner( level, false ); } );
    }


    // ============================================================
    // HELPERS — PANEL & LIST RENDERING
    // ============================================================

    function showPanel( level ) {
        $( '#moga-loc-panel-' + level ).show();
    }

    function hidePanel( level ) {
        $( '#moga-loc-panel-' + level ).hide();
        $( '#moga-loc-' + level + '-list' ).empty();
        closeForm( level );
    }

    function setSpinner( level, show ) {
        $( '#moga-loc-' + level + '-spinner' ).toggle( show );
    }

    /**
     * Render a list of rows into the panel list container.
     *
     * @param {string} level     — 'province', 'city', or 'district'
     * @param {Array}  rows      — [{id, name, ...}]
     * @param {string} emptyText — message when rows is empty
     */
    function renderList( level, rows, emptyText ) {
        var $list = $( '#moga-loc-' + level + '-list' );
        $list.empty();

        if ( ! rows || 0 === rows.length ) {
            $list.html( '<p class="moga-loc-list__empty">' + escHtml( emptyText ) + '</p>' );
            return;
        }

        var html = '';
        $.each( rows, function ( i, row ) {
            html += '<div class="moga-loc-list-row" data-id="' + parseInt( row.id, 10 ) + '" data-level="' + escAttr( level ) + '"'
                + ( row.lat ? ' data-lat="' + escAttr( row.lat ) + '"' : '' )
                + ( row.lng ? ' data-lng="' + escAttr( row.lng ) + '"' : '' )
                + '>'
                + '<span class="moga-loc-list-row__name">' + escHtml( row.name ) + '</span>';

            if ( 'city' === level && ( row.lat || row.lng ) ) {
                html += '<span class="moga-loc-list-row__coords">'
                    + escHtml( row.lat || '' ) + ', ' + escHtml( row.lng || '' )
                    + '</span>';
            }

            html += '<span class="moga-loc-list-row__actions">'
                + '<button type="button" class="button button-small moga-loc-row-edit">' + escHtml( L.i18n.edit ) + '</button>'
                + '<button type="button" class="button button-small moga-loc-row-delete">' + escHtml( L.i18n.delete ) + '</button>'
                + '</span>'
                + '</div>';
        } );

        $list.html( html );
    }

    function getLevelEmptyText( level ) {
        var map = {
            province: L.i18n.noProvinces,
            city:     L.i18n.noCities,
            district: L.i18n.noDistricts,
        };
        return map[ level ] || '';
    }

    function getChildLevel( level ) {
        var map = { country: 'province', province: 'city', city: 'district' };
        return map[ level ] || '';
    }

    function numberFmt( n ) {
        return n.toString().replace( /\B(?=(\d{3})+(?!\d))/g, ',' );
    }

    function escHtml( str ) {
        return $( '<div>' ).text( String( str ) ).html();
    }

    function escAttr( str ) {
        return String( str )
            .replace( /&/g, '&amp;' )
            .replace( /"/g, '&quot;' )
            .replace( /'/g, '&#039;' )
            .replace( /</g, '&lt;' )
            .replace( />/g, '&gt;' );
    }

} )( jQuery );