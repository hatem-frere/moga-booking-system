/**
 * Moga Public JavaScript
 *
 * Handles all frontend plugin interactions:
 *   - AJAX province loader (country → province dropdown) — DB-powered
 *   - AJAX city loader (province → city dropdown) — DB-powered
 *   - AJAX district loader (city → district dropdown) — DB-powered
 *   - Availability checker
 *   - Search form interactions
 *   - Guest counter
 *   - Date validation
 *   - Price calculation display
 *
 * @package    MogaTravelCore
 * @since      1.0.0
 */

( function ( $ ) {
    'use strict';

    /**
     * Main Moga Public object.
     * All methods are namespaced here to avoid conflicts.
     */
    var MogaPublic = {

        /**
         * Cache of loaded provinces per country ISO code.
         * Avoids duplicate AJAX calls for the same country.
         *
         * @type {Object}
         */
        provinceCache: {},

        /**
         * Cache of loaded cities per province DB id.
         * Avoids duplicate AJAX calls for the same province.
         *
         * @type {Object}
         */
        cityCache: {},

        /**
         * Cache of loaded districts per city DB id.
         * Avoids duplicate AJAX calls for the same city.
         *
         * @type {Object}
         */
        districtCache: {},

        /**
         * Currently active availability check request.
         * Cancelled if a new request is made before it completes.
         *
         * @type {jqXHR|null}
         */
        availabilityXhr: null,

        /**
         * Initialize all modules.
         * Called on DOM ready.
         *
         * @return {void}
         */
        init: function () {
            this.initProvinceLoader();
            this.initCityLoader();
            this.initDistrictLoader();
            this.initGuestCounter();
            this.initDateValidation();
            this.initSearchTabs();
            this.initSearchForm();
            this.initAvailabilityChecker();
            this.initPriceCalculator();
        },


        // ============================================================
        // AJAX PROVINCE LOADER — country → province (DB-powered)
        // ============================================================

        /**
         * Initialize country → province cascade dropdown.
         * Fires on .moga-country-select change.
         * Uses moga_get_provinces AJAX action (queries mg_moga_loc_provinces).
         *
         * Province select is found via:
         *   1. data-province-target="[id]" attribute on the country select
         *   2. Proximity: .moga-province-select within the same form/group
         *
         * @return {void}
         */
        initProvinceLoader: function () {
            var self = this;

            $( document ).on(
                'change',
                '.moga-country-select, [data-moga="country-select"]',
                function () {
                    var $country        = $( this );
                    var countryCode     = $country.val();
                    var $provinceSelect = self.findProvinceSelect( $country );
                    var $citySelect     = self.findCitySelectFromCountry( $country );
                    var $districtField  = $citySelect.length
                        ? self.findDistrictField( $citySelect )
                        : $();

                    // Reset province, city, and district.
                    self.resetProvinceDropdown( $provinceSelect );
                    if ( $citySelect.length ) self.resetCityDropdown( $citySelect );
                    if ( $districtField.length ) self.resetDistrictField( $districtField );

                    if ( ! countryCode ) return;

                    // Use cache if available.
                    if ( self.provinceCache[ countryCode ] ) {
                        self.populateProvinceDropdown( $provinceSelect, self.provinceCache[ countryCode ] );
                        return;
                    }

                    // Show loading.
                    $provinceSelect.empty().append(
                        $( '<option>' ).val( '' ).text( mogaCoreData.i18n.loadingProvinces || 'Loading…' )
                    ).prop( 'disabled', true );

                    $.ajax( {
                        url:  mogaCoreData.ajaxUrl,
                        type: 'POST',
                        data: {
                            action:       'moga_get_provinces',
                            country_code: countryCode,
                            nonce:        mogaCoreData.nonce,
                        },
                        success: function ( r ) {
                            if ( r.success && r.data && r.data.provinces && r.data.provinces.length ) {
                                self.provinceCache[ countryCode ] = r.data.provinces;
                                self.populateProvinceDropdown( $provinceSelect, r.data.provinces );
                            } else {
                                self.resetProvinceDropdown( $provinceSelect );
                            }
                        },
                        error: function () {
                            self.resetProvinceDropdown( $provinceSelect );
                        },
                    } );
                }
            );
        },

        /**
         * Find the province select associated with a country select.
         *
         * @param  {jQuery} $countrySelect Country <select>.
         * @return {jQuery}
         */
        findProvinceSelect: function ( $countrySelect ) {
            var targetId = $countrySelect.data( 'province-target' );
            if ( targetId ) {
                var $t = $( '#' + targetId );
                if ( $t.length ) return $t;
            }
            var $group = $countrySelect.closest(
                '.moga-search-group, .moga-filter-row, .moga-filter-group, .moga-search-form'
            );
            if ( $group.length ) {
                var $s = $group.find( '.moga-province-select' );
                if ( $s.length ) return $s;
            }
            return $();
        },

        /**
         * Find the city select reachable from a country select.
         * Used to reset it when country changes.
         *
         * @param  {jQuery} $countrySelect Country <select>.
         * @return {jQuery}
         */
        findCitySelectFromCountry: function ( $countrySelect ) {
            var cityTargetId = $countrySelect.data( 'city-target' );
            if ( cityTargetId ) {
                var $t = $( '#' + cityTargetId );
                if ( $t.length ) return $t;
            }
            var $group = $countrySelect.closest(
                '.moga-search-group, .moga-filter-row, .moga-filter-group, .moga-search-form'
            );
            if ( $group.length ) {
                var $s = $group.find( '.moga-city-select' );
                if ( $s.length ) return $s;
            }
            return $();
        },

        /**
         * Reset province dropdown to default empty state.
         *
         * @param  {jQuery} $select Province select.
         * @return {void}
         */
        resetProvinceDropdown: function ( $select ) {
            if ( ! $select || ! $select.length ) return;
            $select.empty().append(
                $( '<option>' ).val( '' ).text( mogaCoreData.i18n.selectCountryFirst || '— Select Country First —' )
            ).prop( 'disabled', false );
        },

        /**
         * Populate province dropdown.
         *
         * @param  {jQuery} $select   Province select element.
         * @param  {Array}  provinces Array of {id, name} objects.
         * @return {void}
         */
        populateProvinceDropdown: function ( $select, provinces ) {
            if ( ! $select || ! $select.length ) return;
            $select.empty().append(
                $( '<option>' ).val( '' ).text( mogaCoreData.i18n.selectProvince || '— Select Province —' )
            );
            $.each( provinces, function ( i, p ) {
                $select.append( $( '<option>' ).val( p.id ).text( p.name ) );
            } );
            $select.prop( 'disabled', false );
        },


        // ============================================================
        // AJAX CITY LOADER — province → city (DB-powered)
        // ============================================================

        /**
         * Initialize province → city cascade dropdown.
         * Fires on .moga-province-select change.
         * Uses moga_get_cities AJAX action (queries mg_moga_loc_cities).
         *
         * @return {void}
         */
        initCityLoader: function () {
            var self = this;

            $( document ).on(
                'change',
                '.moga-province-select, [data-moga="province-select"]',
                function () {
                    var $province   = $( this );
                    var provinceId  = parseInt( $province.val(), 10 ) || 0;
                    var cityTargetId = $province.data( 'city-target' );
                    var $citySelect = cityTargetId
                        ? $( '#' + cityTargetId )
                        : $province.closest(
                            '.moga-search-group, .moga-filter-row, .moga-filter-group, .moga-search-form'
                          ).find( '.moga-city-select' );

                    if ( ! $citySelect.length ) return;

                    // Reset city and district.
                    self.resetCityDropdown( $citySelect );
                    var $districtField = self.findDistrictField( $citySelect );
                    if ( $districtField.length ) self.resetDistrictField( $districtField );

                    if ( ! provinceId ) return;

                    // Use cache if available.
                    if ( self.cityCache[ provinceId ] ) {
                        self.populateCityDropdown( $citySelect, self.cityCache[ provinceId ] );
                        return;
                    }

                    // Show loading.
                    $citySelect.empty().append(
                        $( '<option>' ).val( '' ).text( mogaCoreData.i18n.loading || 'Loading…' )
                    ).prop( 'disabled', true );

                    $.ajax( {
                        url:  mogaCoreData.ajaxUrl,
                        type: 'POST',
                        data: {
                            action:      'moga_get_cities',
                            province_id: provinceId,
                            nonce:       mogaCoreData.nonce,
                        },
                        success: function ( r ) {
                            if ( r.success && r.data && r.data.cities && r.data.cities.length ) {
                                self.cityCache[ provinceId ] = r.data.cities;
                                self.populateCityDropdown( $citySelect, r.data.cities );
                            } else {
                                self.resetCityDropdown( $citySelect );
                            }
                        },
                        error: function () {
                            self.resetCityDropdown( $citySelect );
                        },
                    } );
                }
            );
        },

        /**
         * Reset city dropdown to default empty state.
         *
         * @param  {jQuery} $select City select element.
         * @return {void}
         */
        resetCityDropdown: function ( $select ) {
            if ( ! $select || ! $select.length ) return;
            $select.empty().append(
                $( '<option>' ).val( '' ).text( mogaCoreData.i18n.selectProvinceFirst || '— Select Province First —' )
            ).prop( 'disabled', false );
        },

        /**
         * Populate city dropdown.
         * City option value = city DB id (used for district lookup).
         *
         * @param  {jQuery} $select City select element.
         * @param  {Array}  cities  Array of {id, name} objects from mg_moga_loc_cities.
         * @return {void}
         */
        populateCityDropdown: function ( $select, cities ) {
            $select.empty().append(
                $( '<option>' ).val( '' ).text( mogaCoreData.i18n.selectCity || '— Select City —' )
            );
            $.each( cities, function ( i, city ) {
                $select.append( $( '<option>' ).val( city.id ).text( city.name ) );
            } );
            $select.prop( 'disabled', false );
        },


        // ============================================================
        // AJAX DISTRICT LOADER — city → district (DB-powered)
        // ============================================================

        /**
         * Initialize city → district cascade dropdown.
         * Fires on .moga-city-select change.
         * Uses moga_get_districts AJAX action (queries mg_moga_loc_districts).
         *
         * City option value = city DB id — used directly as city_id parameter.
         *
         * Behavior:
         *   - Districts found  → populate and show .moga-district-select
         *   - No districts     → show .moga-district-text for manual entry
         *   - No city selected → reset district field
         *
         * @return {void}
         */
        initDistrictLoader: function () {
            var self = this;

            $( document ).on(
                'change',
                '.moga-city-select, [data-moga="city-select"]',
                function () {
                    var $citySelect    = $( this );
                    var cityId         = parseInt( $citySelect.val(), 10 ) || 0;
                    var $districtField = self.findDistrictField( $citySelect );

                    if ( ! $districtField.length ) return;

                    self.resetDistrictField( $districtField );

                    if ( ! cityId ) return;

                    // Use cache if available.
                    if ( self.districtCache[ cityId ] !== undefined ) {
                        self.renderDistrictField( $districtField, self.districtCache[ cityId ], '' );
                        return;
                    }

                    self.setDistrictLoading( $districtField, true );

                    $.ajax( {
                        url:  mogaCoreData.ajaxUrl,
                        type: 'POST',
                        data: {
                            action:  'moga_get_districts',
                            city_id: cityId,
                            nonce:   mogaCoreData.nonce,
                        },
                        success: function ( r ) {
                            self.setDistrictLoading( $districtField, false );
                            var districts = ( r.success && r.data && r.data.districts )
                                ? r.data.districts : [];
                            self.districtCache[ cityId ] = districts;
                            self.renderDistrictField( $districtField, districts, '' );
                        },
                        error: function () {
                            self.setDistrictLoading( $districtField, false );
                            self.districtCache[ cityId ] = [];
                            self.showDistrictTextInput( $districtField );
                        },
                    } );
                }
            );
        },

        /**
         * Find the district field associated with a city select.
         *
         * @param  {jQuery} $citySelect The city <select> element.
         * @return {jQuery} The district field, or empty jQuery object if not found.
         */
        findDistrictField: function ( $citySelect ) {
            var targetId = $citySelect.data( 'district-target' );
            if ( targetId ) {
                var $target = $( '#' + targetId );
                if ( $target.length ) return $target;
            }
            var $group = $citySelect.closest(
                '.moga-search-group, .moga-filter-row, .moga-filter-group, ' +
                '.moga-search-form, .moga-district-wrapper'
            );
            if ( $group.length ) {
                var $sel = $group.find( '.moga-district-select' );
                if ( $sel.length ) return $sel;
                var $txt = $group.find( '.moga-district-text' );
                if ( $txt.length ) return $txt;
            }
            return $();
        },

        /**
         * Render the district field with available data.
         * Districts found → populate select. Empty → show text input.
         *
         * @param  {jQuery} $field        District field element.
         * @param  {Array}  districts     Array of {id, name} objects.
         * @param  {string} savedDistrict Previously saved value to pre-select.
         * @return {void}
         */
        renderDistrictField: function ( $field, districts, savedDistrict ) {
            var $wrapper = $field.closest( '.moga-district-wrapper' );

            if ( districts.length ) {
                var $select = $field.is( 'select' )
                    ? $field
                    : ( $wrapper.length ? $wrapper.find( '.moga-district-select' ) : $() );
                var $text = $field.is( 'input' )
                    ? $field
                    : ( $wrapper.length ? $wrapper.find( '.moga-district-text' ) : $() );

                if ( $select.length ) {
                    $select.empty().append(
                        $( '<option>' ).val( '' ).text( mogaCoreData.i18n.selectDistrict || '— Select District —' )
                    );
                    $.each( districts, function ( i, d ) {
                        var $opt = $( '<option>' ).val( d.name ).text( d.name );
                        if ( savedDistrict && d.name === savedDistrict ) $opt.prop( 'selected', true );
                        $select.append( $opt );
                    } );
                    if ( $text.length ) {
                        $select.off( 'change.district' ).on( 'change.district', function () {
                            $text.val( $( this ).val() );
                        } );
                        if ( savedDistrict ) $text.val( savedDistrict );
                    }
                    $select.prop( 'disabled', false ).show();
                    if ( $wrapper.length ) $wrapper.show();
                } else {
                    this.showDistrictTextInput( $field );
                }
            } else {
                this.showDistrictTextInput( $field );
            }
        },

        /**
         * Show the district text input as manual entry fallback.
         *
         * @param  {jQuery} $field District field element.
         * @return {void}
         */
        showDistrictTextInput: function ( $field ) {
            var $wrapper = $field.closest( '.moga-district-wrapper' );
            var $select  = $field.is( 'select' ) ? $field : ( $wrapper.length ? $wrapper.find( '.moga-district-select' ) : $() );
            if ( $select.length ) $select.hide();
            var $text = $field.is( 'input' ) ? $field : ( $wrapper.length ? $wrapper.find( '.moga-district-text' ) : $() );
            if ( $text.length ) {
                $text.show();
                if ( $wrapper.length ) $wrapper.show();
            } else if ( $wrapper.length ) {
                $wrapper.hide();
            }
        },

        /**
         * Reset district field to default empty/hidden state.
         *
         * @param  {jQuery} $field District select or text input.
         * @return {void}
         */
        resetDistrictField: function ( $field ) {
            if ( ! $field || ! $field.length ) return;
            var $wrapper = $field.closest( '.moga-district-wrapper' );
            if ( $field.is( 'select' ) ) {
                $field.empty().append( $( '<option>' ).val( '' ).text( mogaCoreData.i18n.selectDistrict || '— Select District —' ) ).hide();
            } else if ( $field.is( 'input' ) ) {
                $field.val( '' );
            }
            if ( $wrapper.length ) {
                $wrapper.find( '.moga-district-select' )
                    .empty().append( $( '<option>' ).val( '' ).text( mogaCoreData.i18n.selectDistrict || '— Select District —' ) ).hide();
                $wrapper.find( '.moga-district-text' ).val( '' );
            }
        },

        /**
         * Set district field loading state.
         *
         * @param  {jQuery}  $field   District field element.
         * @param  {boolean} loading  Whether loading is active.
         * @return {void}
         */
        setDistrictLoading: function ( $field, loading ) {
            var $wrapper = $field.closest( '.moga-district-wrapper' );
            var $loading = $wrapper.length ? $wrapper.find( '.moga-district-loading' ) : $();
            if ( loading ) {
                $field.prop( 'disabled', true );
                if ( $loading.length ) $loading.show();
            } else {
                $field.prop( 'disabled', false );
                if ( $loading.length ) $loading.hide();
            }
        },


        // GUEST COUNTER
        // ============================================================

        /**
         * Initialize guest counter +/- buttons.
         * Used in search forms and booking forms.
         *
         * @return {void}
         */
        initGuestCounter: function () {

            $( document ).on( 'click', '.moga-guest-btn', function ( e ) {
                e.preventDefault();

                var $btn    = $( this );
                var action  = $btn.data( 'action' ); // 'increase' or 'decrease'
                var target  = $btn.data( 'target' );
                var $input  = $( '#' + target );

                if ( ! $input.length ) return;

                var current = parseInt( $input.val(), 10 ) || 0;
                var min     = parseInt( $input.attr( 'min' ), 10 ) || 0;
                var max     = parseInt( $input.attr( 'max' ), 10 ) || 99;

                if ( 'increase' === action && current < max ) {
                    $input.val( current + 1 ).trigger( 'change' );
                } else if ( 'decrease' === action && current > min ) {
                    $input.val( current - 1 ).trigger( 'change' );
                }

                // Update button states.
                var newVal = parseInt( $input.val(), 10 );
                $btn.closest( '.moga-guest-control' )
                    .find( '[data-action="decrease"]' )
                    .prop( 'disabled', newVal <= min );
                $btn.closest( '.moga-guest-control' )
                    .find( '[data-action="increase"]' )
                    .prop( 'disabled', newVal >= max );
            } );

            // Update guest summary label on change.
            $( document ).on(
                'change',
                '.moga-guest-input',
                function () {
                    MogaPublic.updateGuestSummary(
                        $( this ).closest( '.moga-guests-dropdown' )
                    );
                }
            );
        },

        /**
         * Update the guest summary display text.
         *
         * @param  {jQuery} $wrapper Guest dropdown wrapper.
         * @return {void}
         */
        updateGuestSummary: function ( $wrapper ) {
            var adults   = parseInt( $wrapper.find( '[data-guest="adults"]' ).val(), 10 )   || 0;
            var children = parseInt( $wrapper.find( '[data-guest="children"]' ).val(), 10 ) || 0;
            var infants  = parseInt( $wrapper.find( '[data-guest="infants"]' ).val(), 10 )  || 0;

            var parts = [];

            if ( adults > 0 ) {
                parts.push(
                    adults + ' ' + ( 1 === adults
                        ? ( mogaCoreData.i18n.adult   || 'adult' )
                        : ( mogaCoreData.i18n.adults  || 'adults' ) )
                );
            }

            if ( children > 0 ) {
                parts.push(
                    children + ' ' + ( 1 === children
                        ? ( mogaCoreData.i18n.child    || 'child' )
                        : ( mogaCoreData.i18n.children || 'children' ) )
                );
            }

            if ( infants > 0 ) {
                parts.push(
                    infants + ' ' + ( 1 === infants
                        ? ( mogaCoreData.i18n.infant  || 'infant' )
                        : ( mogaCoreData.i18n.infants || 'infants' ) )
                );
            }

            var summary = parts.length
                ? parts.join( ', ' )
                : ( mogaCoreData.i18n.addGuests || 'Add guests' );

            $wrapper.closest( '.moga-search-group' )
                .find( '.moga-guests-trigger' )
                .text( summary );
        },


        // ============================================================
        // DATE VALIDATION
        // ============================================================

        /**
         * Initialize date validation.
         * Ensures check-out is always after check-in.
         * Sets minimum dates based on today.
         *
         * @return {void}
         */
        initDateValidation: function () {
            var today    = new Date().toISOString().split( 'T' )[ 0 ];
            var tomorrow = new Date(
                new Date().setDate( new Date().getDate() + 1 )
            ).toISOString().split( 'T' )[ 0 ];

            // Set minimum dates.
            $( '.moga-checkin-input' ).attr( 'min', today );
            $( '.moga-checkout-input' ).attr( 'min', tomorrow );

            // When check-in changes, update check-out minimum.
            $( document ).on( 'change', '.moga-checkin-input', function () {
                var checkinVal = $( this ).val();
                var $checkout  = $( this )
                    .closest( '.moga-search-form, .moga-booking-form' )
                    .find( '.moga-checkout-input' );

                if ( ! checkinVal || ! $checkout.length ) return;

                // Check-out must be at least 1 day after check-in.
                var checkinDate    = new Date( checkinVal );
                var minCheckout    = new Date( checkinDate );
                minCheckout.setDate( minCheckout.getDate() + 1 );
                var minCheckoutStr = minCheckout.toISOString().split( 'T' )[ 0 ];

                $checkout.attr( 'min', minCheckoutStr );

                // If current checkout is before new minimum, reset it.
                if ( $checkout.val() && $checkout.val() <= checkinVal ) {
                    $checkout.val( minCheckoutStr );
                }

                // Update nights display.
                MogaPublic.updateNightsDisplay(
                    $( this ).closest( '.moga-search-form, .moga-booking-form' )
                );
            } );

            // When check-out changes, update nights display.
            $( document ).on( 'change', '.moga-checkout-input', function () {
                MogaPublic.updateNightsDisplay(
                    $( this ).closest( '.moga-search-form, .moga-booking-form' )
                );
            } );
        },

        /**
         * Update the nights count display between two dates.
         *
         * @param  {jQuery} $form Form wrapper.
         * @return {void}
         */
        updateNightsDisplay: function ( $form ) {
            var checkin  = $form.find( '.moga-checkin-input' ).val();
            var checkout = $form.find( '.moga-checkout-input' ).val();
            var $display = $form.find( '.moga-nights-display' );

            if ( ! $display.length ) return;

            if ( ! checkin || ! checkout ) {
                $display.text( '' );
                return;
            }

            var inDate  = new Date( checkin );
            var outDate = new Date( checkout );
            var nights  = Math.round(
                ( outDate - inDate ) / ( 1000 * 60 * 60 * 24 )
            );

            if ( nights > 0 ) {
                $display.text(
                    nights + ' ' + ( 1 === nights
                        ? ( mogaCoreData.i18n.night  || 'night' )
                        : ( mogaCoreData.i18n.nights || 'nights' ) )
                );
            } else {
                $display.text( '' );
            }
        },


        // ============================================================
        // SEARCH FORM TABS
        // ============================================================

        /**
         * Initialize search form tab switching.
         * Switches between Properties / Tours / Bus Seats / Rentals.
         *
         * @return {void}
         */
        initSearchTabs: function () {

            $( document ).on( 'click', '.moga-search-tab', function ( e ) {
                e.preventDefault();

                var $tab    = $( this );
                var tabType = $tab.data( 'tab' );
                var $tabs   = $tab.closest( '.moga-search-tabs' );
                var $form   = $tab.closest( '.moga-search-box' ).find( '.moga-search-form' );

                // Update active tab.
                $tabs.find( '.moga-search-tab' ).removeClass( 'moga-search-tab--active' );
                $tab.addClass( 'moga-search-tab--active' );

                // Update form type hidden field.
                $form.find( '[name="type"]' ).val( tabType );

                // Show/hide tab-specific fields.
                $form.find( '[data-tab-field]' ).hide();
                $form.find( '[data-tab-field="' + tabType + '"]' ).show();
                $form.find( '[data-tab-field="all"]' ).show();
            } );
        },


        // ============================================================
        // SEARCH FORM SUBMISSION
        // ============================================================

        /**
         * Initialize search form submission.
         * Builds URL with search parameters and redirects.
         *
         * @return {void}
         */
        initSearchForm: function () {

            $( document ).on( 'submit', '.moga-search-form', function ( e ) {
                e.preventDefault();

                var $form  = $( this );
                var params = {};

                // Collect all form values.
                $form.find( 'input, select' ).each( function () {
                    var name  = $( this ).attr( 'name' );
                    var value = $( this ).val();

                    if ( name && value ) {
                        params[ name ] = value;
                    }
                } );

                // Build search URL.
                var baseUrl   = mogaCoreData.searchUrl || mogaCoreData.siteUrl + '/search-results/';
                var queryStr  = $.param( params );
                var searchUrl = baseUrl + ( baseUrl.indexOf( '?' ) > -1 ? '&' : '?' ) + queryStr;

                window.location.href = searchUrl;
            } );
        },


        // ============================================================
        // AVAILABILITY CHECKER
        // ============================================================

        /**
         * Initialize availability checker.
         * Fires when dates change on a booking form.
         *
         * @return {void}
         */
        initAvailabilityChecker: function () {
            var self = this;

            $( document ).on(
                'change',
                '.moga-booking-form .moga-checkin-input, ' +
                '.moga-booking-form .moga-checkout-input',
                function () {
                    var $form = $( this ).closest( '.moga-booking-form' );
                    self.checkAvailability( $form );
                }
            );
        },

        /**
         * Check availability for current date selection.
         *
         * @param  {jQuery} $form Booking form wrapper.
         * @return {void}
         */
        checkAvailability: function ( $form ) {
            var self            = this;
            var checkin         = $form.find( '.moga-checkin-input' ).val();
            var checkout        = $form.find( '.moga-checkout-input' ).val();
            var listingId       = $form.data( 'listing-id' );
            var listingType     = $form.data( 'listing-type' ) || 'property';
            var $statusEl       = $form.find( '.moga-availability-status' );
            var $bookingBtn     = $form.find( '.moga-book-now-btn' );
            var $priceBreakdown = $form.find( '.moga-price-breakdown' );

            if ( ! checkin || ! checkout || ! listingId ) return;

            // Cancel any pending request.
            if ( self.availabilityXhr ) {
                self.availabilityXhr.abort();
            }

            // Show checking state.
            $statusEl
                .removeClass( 'moga-availability--available moga-availability--unavailable' )
                .addClass( 'moga-availability--checking' )
                .text( mogaCoreData.i18n.checkingAvailability || 'Checking availability...' );

            $bookingBtn.prop( 'disabled', true );

            // AJAX availability check.
            self.availabilityXhr = $.ajax( {
                url:     mogaCoreData.ajaxUrl,
                type:    'POST',
                data:    {
                    action:       'moga_check_availability',
                    listing_id:   listingId,
                    listing_type: listingType,
                    check_in:     checkin,
                    check_out:    checkout,
                    nonce:        mogaCoreData.nonce,
                },
                success: function ( response ) {
                    $statusEl.removeClass( 'moga-availability--checking' );

                    if ( response.success && response.data ) {
                        var data = response.data;

                        if ( data.available ) {
                            $statusEl
                                .addClass( 'moga-availability--available' )
                                .text( mogaCoreData.i18n.available || '✅ Available for your dates!' );

                            $bookingBtn.prop( 'disabled', false );

                            if ( data.price ) {
                                self.renderPriceBreakdown( $priceBreakdown, data.price );
                            }
                        } else {
                            $statusEl
                                .addClass( 'moga-availability--unavailable' )
                                .text( mogaCoreData.i18n.unavailable || '❌ Not available for selected dates.' );

                            $bookingBtn.prop( 'disabled', true );
                        }
                    }
                },
                error: function ( xhr ) {
                    if ( 'abort' === xhr.statusText ) return;

                    $statusEl
                        .removeClass( 'moga-availability--checking' )
                        .text( mogaCoreData.i18n.error || 'Something went wrong. Please try again.' );
                },
            } );
        },


        // ============================================================
        // PRICE CALCULATOR
        // ============================================================

        /**
         * Initialize live price calculator.
         * Updates price display when dates or guests change.
         *
         * @return {void}
         */
        initPriceCalculator: function () {
            var self = this;

            $( document ).on(
                'change',
                '.moga-booking-form .moga-checkin-input, ' +
                '.moga-booking-form .moga-checkout-input, ' +
                '.moga-booking-form .moga-guest-input',
                function () {
                    var $form = $( this ).closest( '.moga-booking-form' );
                    self.calculatePrice( $form );
                }
            );
        },

        /**
         * Calculate and display price for current selections.
         *
         * @param  {jQuery} $form Booking form wrapper.
         * @return {void}
         */
        calculatePrice: function ( $form ) {
            var checkin     = $form.find( '.moga-checkin-input' ).val();
            var checkout    = $form.find( '.moga-checkout-input' ).val();
            var listingId   = $form.data( 'listing-id' );
            var listingType = $form.data( 'listing-type' ) || 'property';
            var adults      = parseInt( $form.find( '[data-guest="adults"]' ).val(), 10 )   || 1;
            var children    = parseInt( $form.find( '[data-guest="children"]' ).val(), 10 ) || 0;
            var infants     = parseInt( $form.find( '[data-guest="infants"]' ).val(), 10 )  || 0;

            if ( ! checkin || ! checkout || ! listingId ) return;

            var $priceBreakdown = $form.find( '.moga-price-breakdown' );

            $.ajax( {
                url:     mogaCoreData.ajaxUrl,
                type:    'POST',
                data:    {
                    action:       'moga_calculate_price',
                    listing_id:   listingId,
                    listing_type: listingType,
                    check_in:     checkin,
                    check_out:    checkout,
                    adults:       adults,
                    children:     children,
                    infants:      infants,
                    nonce:        mogaCoreData.nonce,
                },
                success: function ( response ) {
                    if ( response.success && response.data && response.data.price ) {
                        MogaPublic.renderPriceBreakdown( $priceBreakdown, response.data.price );
                    }
                },
            } );
        },

        /**
         * Render price breakdown HTML into the price breakdown container.
         *
         * @param  {jQuery} $container Price breakdown container.
         * @param  {Object} price      Price data object from PHP.
         * @return {void}
         */
        renderPriceBreakdown: function ( $container, price ) {
            if ( ! $container.length ) return;

            var html = '<div class="moga-price-breakdown__inner">';

            // Nights row (for properties).
            if ( price.nights ) {
                html += '<div class="moga-price-breakdown__row">' +
                    '<span>' + price.nights + ' ' +
                    ( 1 === price.nights
                        ? ( mogaCoreData.i18n.night  || 'night' )
                        : ( mogaCoreData.i18n.nights || 'nights' ) ) +
                    ' × ' + price.price_formatted + '</span>' +
                    '<span>' + price.subtotal_formatted + '</span>' +
                    '</div>';
            }

            // Adults row (for tours).
            if ( price.adults && price.adults > 0 && price.adults_total ) {
                html += '<div class="moga-price-breakdown__row">' +
                    '<span>' + price.adults + ' ' +
                    ( mogaCoreData.i18n.adults || 'adults' ) +
                    ' × ' + price.price_adult_formatted + '</span>' +
                    '<span>' + price.adults_total_formatted + '</span>' +
                    '</div>';
            }

            // Discount row.
            if ( price.discount && price.discount > 0 ) {
                html += '<div class="moga-price-breakdown__row moga-price-breakdown__row--discount">' +
                    '<span>' + ( mogaCoreData.i18n.discount || 'Discount' ) +
                    ' (' + price.discount_percent + '%)</span>' +
                    '<span>-' + price.discount_formatted + '</span>' +
                    '</div>';
            }

            // Taxes row.
            if ( price.taxes && price.taxes > 0 ) {
                html += '<div class="moga-price-breakdown__row">' +
                    '<span>' + ( mogaCoreData.i18n.taxes || 'Taxes & fees' ) + '</span>' +
                    '<span>' + price.taxes_formatted + '</span>' +
                    '</div>';
            }

            // Total row.
            html += '<div class="moga-price-breakdown__row moga-price-breakdown__row--total">' +
                '<span>' + ( mogaCoreData.i18n.total || 'Total' ) + '</span>' +
                '<span class="moga-price-breakdown__total">' + price.total_formatted + '</span>' +
                '</div>';

            html += '</div>';

            $container.html( html ).show();
        },

    }; // end MogaPublic


    // ============================================================
    // INITIALIZE ON DOM READY
    // ============================================================

    $( document ).ready( function () {
        MogaPublic.init();
    } );


    // ============================================================
    // EXPOSE TO GLOBAL SCOPE
    // ============================================================

    /**
     * Make MogaPublic accessible globally so other scripts
     * can call its methods if needed.
     * Example: window.MogaPublic.checkAvailability(...)
     */
    window.MogaPublic = MogaPublic;

} )( jQuery );