/**
 * Moga Public JavaScript
 *
 * Handles all frontend plugin interactions:
 *   - AJAX city loader (country → city dropdown)
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
         * Cache of loaded cities per country code.
         * Avoids duplicate AJAX calls for the same country.
         *
         * @type {Object}
         */
        cityCache: {},

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
            this.initCityLoader();
            this.initGuestCounter();
            this.initDateValidation();
            this.initSearchTabs();
            this.initSearchForm();
            this.initAvailabilityChecker();
            this.initPriceCalculator();
        },


        // ============================================================
        // AJAX CITY LOADER
        // ============================================================

        /**
         * Initialize country → city dynamic dropdown.
         * When admin or user selects a country, the city
         * dropdown is populated via AJAX.
         *
         * @return {void}
         */
        initCityLoader: function () {
            var self = this;

            $( document ).on(
                'change',
                '.moga-country-select, [data-moga="country-select"]',
                function () {
                    var $select     = $( this );
                    var countryCode = $select.val();
                    var targetId    = $select.data( 'target' ) || $select.data( 'city-target' );
                    var $citySelect = targetId
                        ? $( '#' + targetId )
                        : $select.closest( '.moga-search-group, .moga-filter-row' ).find( '.moga-city-select' );

                    if ( ! $citySelect.length ) return;

                    // Reset city dropdown.
                    self.resetCityDropdown( $citySelect );

                    if ( ! countryCode ) return;

                    // Use cache if available.
                    if ( self.cityCache[ countryCode ] ) {
                        self.populateCityDropdown( $citySelect, self.cityCache[ countryCode ] );
                        return;
                    }

                    // Show loading state.
                    self.setCityLoading( $citySelect, true );

                    // AJAX call to get cities.
                    $.ajax( {
                        url:      mogaCoreData.ajaxUrl,
                        type:     'POST',
                        data:     {
                            action:       'moga_get_cities',
                            country_code: countryCode,
                            nonce:        mogaCoreData.nonce,
                        },
                        success:  function ( response ) {
                            self.setCityLoading( $citySelect, false );

                            if ( response.success && response.data && response.data.cities ) {
                                // Store in cache.
                                self.cityCache[ countryCode ] = response.data.cities;
                                self.populateCityDropdown( $citySelect, response.data.cities );
                            } else {
                                self.resetCityDropdown( $citySelect );
                            }
                        },
                        error:    function () {
                            self.setCityLoading( $citySelect, false );
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
            $select.empty().append(
                $( '<option>' )
                    .val( '' )
                    .text( mogaCoreData.i18n.selectCity || '— Select City —' )
            );
        },

        /**
         * Set city dropdown loading state.
         *
         * @param  {jQuery}  $select  City select element.
         * @param  {boolean} loading  Whether loading is active.
         * @return {void}
         */
        setCityLoading: function ( $select, loading ) {
            if ( loading ) {
                $select.empty().append(
                    $( '<option>' )
                        .val( '' )
                        .text( mogaCoreData.i18n.loading || 'Loading...' )
                );
                $select.prop( 'disabled', true );
            } else {
                $select.prop( 'disabled', false );
            }
        },

        /**
         * Populate city dropdown with cities array.
         *
         * @param  {jQuery} $select City select element.
         * @param  {Array}  cities  Array of city objects.
         * @return {void}
         */
        populateCityDropdown: function ( $select, cities ) {
            $select.empty().append(
                $( '<option>' )
                    .val( '' )
                    .text( mogaCoreData.i18n.selectCity || '— Select City —' )
            );

            $.each( cities, function ( i, city ) {
                $select.append(
                    $( '<option>' )
                        .val( city.name )
                        .text( city.name )
                );
            } );

            $select.prop( 'disabled', false );
        },


        // ============================================================
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
            var total    = adults + children;

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
                var checkinVal  = $( this ).val();
                var $checkout   = $( this )
                    .closest( '.moga-search-form, .moga-booking-form' )
                    .find( '.moga-checkout-input' );

                if ( ! checkinVal || ! $checkout.length ) return;

                // Check-out must be at least 1 day after check-in.
                var checkinDate  = new Date( checkinVal );
                var minCheckout  = new Date( checkinDate );
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
                var baseUrl    = mogaCoreData.searchUrl || mogaCoreData.siteUrl + '/search-results/';
                var queryStr   = $.param( params );
                var searchUrl  = baseUrl + ( baseUrl.indexOf( '?' ) > -1 ? '&' : '?' ) + queryStr;

                // Redirect to search results.
                window.location.href = searchUrl;
            } );

            // Also handle the search button click directly.
            $( document ).on( 'click', '.moga-search-btn', function ( e ) {
                var $form = $( this ).closest( '.moga-search-form' );
                if ( $form.length ) {
                    $form.trigger( 'submit' );
                }
            } );
        },


        // ============================================================
        // AVAILABILITY CHECKER
        // ============================================================

        /**
         * Initialize availability checker on property pages.
         * Called when user selects dates on a property single page.
         *
         * @return {void}
         */
        initAvailabilityChecker: function () {
            var self = this;

            $( document ).on(
                'change',
                '.moga-booking-form .moga-checkin-input, .moga-booking-form .moga-checkout-input',
                function () {
                    var $form    = $( this ).closest( '.moga-booking-form' );
                    var checkin  = $form.find( '.moga-checkin-input' ).val();
                    var checkout = $form.find( '.moga-checkout-input' ).val();
                    var listingId = $form.data( 'listing-id' );
                    var listingType = $form.data( 'listing-type' ) || 'property';

                    if ( ! checkin || ! checkout || ! listingId ) return;

                    self.checkAvailability( $form, listingId, listingType, checkin, checkout );
                }
            );
        },

        /**
         * Check availability for given dates via AJAX.
         *
         * @param  {jQuery} $form       Booking form wrapper.
         * @param  {int}    listingId   Property or tour post ID.
         * @param  {string} listingType 'property' or 'tour'.
         * @param  {string} checkin     Check-in date (Y-m-d).
         * @param  {string} checkout    Check-out date (Y-m-d).
         * @return {void}
         */
        checkAvailability: function ( $form, listingId, listingType, checkin, checkout ) {
            var self            = this;
            var $statusEl       = $form.find( '.moga-availability-status' );
            var $bookingBtn     = $form.find( '.moga-book-btn' );
            var $priceBreakdown = $form.find( '.moga-price-breakdown' );

            // Cancel previous request.
            if ( self.availabilityXhr ) {
                self.availabilityXhr.abort();
            }

            // Show checking state.
            $statusEl.removeClass( 'moga-availability--available moga-availability--unavailable' )
                .addClass( 'moga-availability--checking' )
                .text( mogaCoreData.i18n.checkingAvailability || 'Checking availability...' )
                .show();

            $bookingBtn.prop( 'disabled', true );
            $priceBreakdown.hide();

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

                            // Show price breakdown.
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
            var checkin    = $form.find( '.moga-checkin-input' ).val();
            var checkout   = $form.find( '.moga-checkout-input' ).val();
            var listingId  = $form.data( 'listing-id' );
            var listingType = $form.data( 'listing-type' ) || 'property';
            var adults     = parseInt( $form.find( '[data-guest="adults"]' ).val(), 10 )   || 1;
            var children   = parseInt( $form.find( '[data-guest="children"]' ).val(), 10 ) || 0;
            var infants    = parseInt( $form.find( '[data-guest="infants"]' ).val(), 10 )  || 0;

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