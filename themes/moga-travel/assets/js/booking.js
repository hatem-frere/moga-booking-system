/**
 * Moga Travel — Booking & Single Property/Tour Page JS
 *
 * Path: themes/moga-travel/assets/js/booking.js
 *
 * Handles:
 *   01. GLightbox init — gallery + thumbnail strip (property & tour)
 *   02. Flatpickr date pickers — linked check-in / check-out (property)
 *   03. Price breakdown — property — shows immediately, updates live
 *   04. Guest counter — property — +/- buttons
 *   05. Description read-more toggle
 *   06. Amenities show-all toggle
 *   07. Mobile sticky bar — show/hide on scroll
 *   08. Section nav — highlight active section on scroll
 *   09. Share button — Web Share API + clipboard fallback
 *   10. Tour date picker — single date, restricted to available days/dates
 *   11. Tour participant counters — adults / children / infants
 *   12. Tour price breakdown — shows immediately, updates live
 *
 * @package MogaTravel
 * @since   1.0.0
 */

(function () {
    "use strict";

    // ============================================================
    // READ CONFIG FROM JSON BLOCK
    // ============================================================

    function getConfig() {
        var el = document.getElementById("moga-booking-config");
        if (!el) return null;
        try {
            return JSON.parse(el.textContent);
        } catch (e) {
            return null;
        }
    }

    // Tour booking config — separate JSON block, separate ID.
    // Property and tour singles never load on the same page, but this
    // keeps the two config objects (and their shapes) fully independent.
    function getTourConfig() {
        var el = document.getElementById("moga-tour-booking-config");
        if (!el) return null;
        try {
            return JSON.parse(el.textContent);
        } catch (e) {
            return null;
        }
    }

    // ============================================================
    // 01. GLIGHTBOX
    // ============================================================

    function initGallery() {
        if (typeof GLightbox === "undefined") return;

        var first = document.querySelector("[data-gallery]");
        if (!first) return;

        var key = first.getAttribute("data-gallery");

        // ---- Image gallery ----
        GLightbox({
            selector: '[data-gallery="' + key + '"]',
            touchNavigation: true,
            loop: true,
            autoplayVideos: false,
            openEffect: "fade",
            closeEffect: "fade",
        });

        // ---- Video gallery (sidebar) ----
        // Videos use a separate gallery key (property-{id}-videos) so they
        // open in their own GLightbox sequence independent of the image gallery.
        // autoplayVideos is true here so local and embedded videos play on open.
        var videoKey = key + "-videos";
        var videoEl = document.querySelector(
            '[data-gallery="' + videoKey + '"]',
        );

        if (videoEl) {
            GLightbox({
                selector: '[data-gallery="' + videoKey + '"]',
                touchNavigation: true,
                loop: false,
                autoplayVideos: true,
                openEffect: "fade",
                closeEffect: "fade",
                videosWidth: "90vw",
            });
        }

        // "View all" button — mobile.
        var btn = document.getElementById("moga-gallery-view-all");
        if (btn) {
            btn.addEventListener("click", function () {
                var link = document.querySelector(
                    '[data-gallery="' + key + '"]',
                );
                if (link) link.click();
            });
        }
    }

    // ============================================================
    // 02. FLATPICKR DATE PICKERS
    // ============================================================

    function addDaysToDateString(dateStr, days) {
        var d = new Date(dateStr + "T00:00:00");
        d.setDate(d.getDate() + days);
        return flatpickr.formatDate(d, "Y-m-d");
    }

    // Builds Flatpickr's `enable` ranges from the property's periods —
    // switches the calendar into whitelist mode, so every date NOT
    // covered by any period is automatically greyed out and
    // unclickable. If there are no periods at all, this correctly
    // returns an empty array, meaning nothing is bookable at all —
    // matches the locked rule that a property is only bookable within
    // a defined period.
    //
    // Check-in and check-out need slightly different ranges because
    // a period's 'end' date is exclusive (checkout day, not a
    // covered night — matches moga_date_range() throughout the
    // backend): a guest can CHECK OUT on that end date, but can't
    // CHECK IN on it (their first night would be the excluded day).
    function buildEnableRanges(periods, forCheckout) {
        var ranges = [];
        for (var i = 0; i < periods.length; i++) {
            var p = periods[i];
            if (!p.start || !p.end) continue;

            if (forCheckout) {
                ranges.push({
                    from: addDaysToDateString(p.start, 1),
                    to: p.end,
                });
            } else {
                ranges.push({
                    from: p.start,
                    to: addDaysToDateString(p.end, -1),
                });
            }
        }
        return ranges;
    }

    // Inclusive check (unlike findPeriodForDate's exclusive-end
    // check-in logic) — for VISUAL highlighting purposes, a period's
    // full span including its end date should read as "available"
    // to a guest looking at the calendar, regardless of the
    // check-in/check-out boundary nuance that only matters once a
    // specific date is actually being selected.
    function isDateInAnyPeriod(periods, dateStr) {
        for (var i = 0; i < periods.length; i++) {
            var p = periods[i];
            if (p.start && p.end && dateStr >= p.start && dateStr <= p.end) {
                return true;
            }
        }
        return false;
    }

    function findEarliestPeriodStart(periods) {
        var earliest = null;
        for (var i = 0; i < periods.length; i++) {
            if (
                periods[i].start &&
                (!earliest || periods[i].start < earliest)
            ) {
                earliest = periods[i].start;
            }
        }
        return earliest;
    }

    function findPeriodForDate(periods, dateStr) {
        if (!periods || !periods.length) return null;
        for (var i = 0; i < periods.length; i++) {
            var p = periods[i];
            // Matches the exact exclusive-end convention used
            // throughout the backend (moga_date_range()) — the
            // period's own 'end' date is checkout day, not a
            // covered night, so it's excluded here too.
            if (p.start && p.end && dateStr >= p.start && dateStr < p.end) {
                return p;
            }
        }
        return null;
    }

    function initDatePickers(config) {
        if (typeof flatpickr === "undefined" || !config) return;

        var checkinEl = document.getElementById("moga-checkin");
        var checkoutEl = document.getElementById("moga-checkout");
        if (!checkinEl || !checkoutEl) return;

        var today = new Date();
        today.setHours(0, 0, 0, 0);

        var periods = config.pricingPeriods || [];

        var shared = {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "M j, Y",
            minDate: today,
            disableMobile: false,
            onDayCreate: function (dObj, dStr, fp, dayElem) {
                var dateStr = flatpickr.formatDate(dayElem.dateObj, "Y-m-d");
                if (isDateInAnyPeriod(periods, dateStr)) {
                    dayElem.classList.add("moga-flatpickr-available");
                }
            },
        };

        // BUG FIX: previously set shared.maxDate to today + config.maxStay
        // days — but maxStay is now a PER-PERIOD number (each period can
        // have its own), not a single flat property-wide limit. Using it
        // as a cap on how far into the future the ENTIRE calendar could
        // even show meant a property with periods months away (like
        // September, with a period max of 6 nights) had its whole
        // calendar capped at roughly today+6 days — hiding real,
        // bookable periods entirely, unclickable and invisible. The
        // enable whitelist below already correctly restricts which
        // dates are pickable; no separate cap is needed at all.

        var checkoutPicker = flatpickr(
            checkoutEl,
            Object.assign({}, shared, {
                enable: buildEnableRanges(periods, true),
                onClose: function () {
                    updatePriceBreakdown(config);
                },
            }),
        );

        // Extracted so the periods-list click handler (below) can
        // apply the exact same checkin-selected logic as actually
        // picking a date on the calendar — setDate() doesn't fire
        // onClose, so this can't just live inline inside it.
        function applyCheckinConstraints(dateStr) {
            var period = findPeriodForDate(periods, dateStr);

            // A period's own min/max stay (if it sets one) takes
            // precedence over the property's flat default — mirrors
            // exactly how the server (moga_validate_stay_length())
            // already resolves this, so the calendar and the
            // actually-enforced rule can never disagree.
            var minStay = (period && period.min_stay) || config.minStay || 1;
            var maxStay =
                period && period.max_stay ? period.max_stay : config.maxStay;

            var minOut = new Date(dateStr + "T00:00:00");
            minOut.setDate(minOut.getDate() + minStay);
            checkoutPicker.set("minDate", minOut);

            // BUG FIX: maxDate was computed purely from "check-in +
            // maxStay days", never checking whether that landed past
            // the period's own actual end date. A period's Max
            // Nights value can be inconsistent with its real span
            // (e.g. Max Nights = 6 on a period that only covers 5
            // real nights) — when that happens, the period's own
            // boundary must always win. Max Nights can only narrow
            // the allowed range further, never extend past the
            // period it belongs to — otherwise the calendar lets a
            // guest select a checkout date reaching into the NEXT
            // period, which is never allowed (a booking can never
            // straddle two periods, even adjacent ones).
            var periodEnd =
                period && period.end
                    ? new Date(period.end + "T00:00:00")
                    : null;

            if (maxStay > 0) {
                var maxOut = new Date(dateStr + "T00:00:00");
                maxOut.setDate(maxOut.getDate() + maxStay);
                if (periodEnd && maxOut > periodEnd) {
                    maxOut = periodEnd;
                }
                checkoutPicker.set("maxDate", maxOut);
            } else if (periodEnd) {
                checkoutPicker.set("maxDate", periodEnd);
            } else {
                checkoutPicker.set("maxDate", null);
            }

            return minOut;
        }

        var checkinPicker = flatpickr(
            checkinEl,
            Object.assign({}, shared, {
                enable: buildEnableRanges(periods, false),
                onClose: function (dates) {
                    if (!dates[0]) return;

                    var checkinStr = flatpickr.formatDate(dates[0], "Y-m-d");
                    applyCheckinConstraints(checkinStr);

                    if (!checkoutEl.value) checkoutPicker.open();
                    updatePriceBreakdown(config);
                },
            }),
        );

        // Calendar opens on whichever month actually has availability
        // instead of always showing "today"'s month — e.g. if every
        // period is in September, the calendar opens on September,
        // not a blank August. Only when no dates are already known
        // from the URL, so it doesn't override a real pre-filled
        // selection.
        if (!checkinEl.value) {
            var earliestStart = findEarliestPeriodStart(periods);
            if (earliestStart) {
                checkinPicker.jumpToDate(earliestStart);
                checkoutPicker.jumpToDate(earliestStart);
            }
        }

        // Available Periods list — clicking an item fills in both
        // date fields, applying the exact same constraint logic a
        // real calendar click would, then suggests a checkout date
        // matching that period's own minimum stay, so a guest gets a
        // ready-to-book pair of dates in one click rather than a
        // second decision to make immediately after.
        var periodsListEl = document.getElementById("moga-available-periods");
        if (periodsListEl) {
            periodsListEl.addEventListener("click", function (e) {
                var btn = e.target.closest(".moga-available-periods__item");
                if (!btn) return;

                var start = btn.getAttribute("data-start");
                if (!start) return;

                checkinPicker.setDate(start, true);
                var minOut = applyCheckinConstraints(start);
                checkoutPicker.setDate(minOut, true);

                updatePriceBreakdown(config);
            });
        }

        // If dates pre-filled from URL, update immediately.
        if (checkinEl.value && checkoutEl.value) {
            updatePriceBreakdown(config);
        }
    }

    // ============================================================
    // 03. PRICE BREAKDOWN
    // ============================================================

    function calcNights(inStr, outStr) {
        if (!inStr || !outStr) return 0;
        var diff = new Date(outStr) - new Date(inStr);
        return Math.max(0, Math.round(diff / 86400000));
    }

    function fmt(amount, currency, currencySymbol) {
        // Prefer the real resolved symbol ("E£") when available —
        // matches the top badge exactly. Falls back to the raw
        // currency code ("EGP ") if no symbol was passed, and only
        // as a last resort to the generic site-wide default.
        var sym = currencySymbol
            ? currencySymbol
            : currency
              ? currency + " "
              : window.mogaData && window.mogaData.currencySymbol
                ? window.mogaData.currencySymbol
                : "";
        return (
            sym +
            amount.toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            })
        );
    }

    function updatePriceBreakdown(config) {
        var inEl = document.getElementById("moga-checkin");
        var outEl = document.getElementById("moga-checkout");
        if (!inEl || !outEl || !config) return;

        var checkIn = inEl.value;
        var checkOut = outEl.value;

        // No real dates selected yet (or only check-in picked so
        // far, check-out still pending) — keep the breakdown box
        // hidden entirely rather than showing a guessed placeholder.
        // Per explicit request: "the price should be the only thing
        // displayed... until dates have been set" — nothing else
        // should compete for attention before there's something real
        // to show.
        if (!checkIn || !checkOut) {
            hidePriceBreakdown();
            return;
        }

        // BUG FIX (Aug 15 session): this function used to compute the
        // breakdown itself, in JS, from config.pricePerNight — which
        // is an ALREADY-DISCOUNTED, date-blind value baked into the
        // page by PHP's moga_get_property_display_price(). Multiplying
        // that by the night count and then subtracting the discount
        // percentage AGAIN compounded it, and weekend pricing was never
        // applied at all. Fixed: with real dates known, always defer to
        // the server's authoritative calculation — the same
        // moga_calculate_property_price() the AJAX handler already
        // uses correctly — rather than duplicating that logic here.
        fetchServerPrice(config, checkIn, checkOut);
    }

    /**
     * Hides the price breakdown box entirely. Sets style.display
     * directly, not just the 'hidden' attribute — booking.css may
     * set 'display' directly on '.moga-price-breakdown' (the same
     * specificity-conflict pattern already found and fixed on the
     * discount badge/row), which would otherwise silently keep the
     * box visible despite the 'hidden' attribute being present.
     */
    function hidePriceBreakdown() {
        var bd = document.getElementById("moga-price-breakdown");
        if (bd) {
            bd.setAttribute("hidden", "");
            bd.style.display = "none";
        }
    }

    function fetchServerPrice(config, checkIn, checkOut) {
        if (!config.ajaxUrl || !config.nonce || !config.propertyId) return;

        var body = new URLSearchParams({
            action: "moga_calculate_price",
            nonce: config.nonce,
            listing_id: config.propertyId,
            listing_type: "property",
            check_in: checkIn,
            check_out: checkOut,
        });

        fetch(config.ajaxUrl, {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: body.toString(),
        })
            .then(function (res) {
                return res.json();
            })
            .then(function (json) {
                if (!json.success || !json.data || !json.data.price) return;

                var p = json.data.price;
                var nights = p.nights || 1;

                // Use the server's own pre-formatted strings
                // (moga_format_price() via class-moga-ajax.php) rather
                // than reformatting raw numbers here — sidesteps
                // fmt()'s currency-symbol bug entirely for this path,
                // and guarantees this always matches what the top
                // price badge (also server-formatted, in PHP) shows.
                renderBreakdownFormatted({
                    nightsLabel: nights + (nights === 1 ? " night" : " nights"),
                    subtotal:
                        p.subtotal_formatted ||
                        fmt(p.subtotal || 0, p.currency),
                    discount:
                        p.discount_formatted ||
                        fmt(p.discount || 0, p.currency),
                    total: p.total_formatted || fmt(p.total || 0, p.currency),
                    discountPercent: p.discount_percent || 0,
                });

                // Also update the top price badges (desktop + mobile
                // sticky bar) — these were previously only set once,
                // on initial page load, and never refreshed when the
                // guest picked dates live via the date picker.
                updatePriceBadges(p);
            })
            .catch(function () {
                // Network/server error — leave the last known-good
                // display in place rather than showing broken numbers.
            });
    }

    function renderBreakdown(data) {
        var label = document.getElementById("moga-nights-label");
        if (label) label.textContent = data.nightsLabel;

        var subEl = document.getElementById("moga-breakdown-subtotal");
        if (subEl)
            subEl.textContent = fmt(
                data.subtotal,
                data.currency,
                data.currencySymbol,
            );

        var discEl = document.getElementById("moga-breakdown-discount");
        if (discEl)
            discEl.textContent =
                "\u2212" +
                fmt(data.discount, data.currency, data.currencySymbol);

        var totEl = document.getElementById("moga-breakdown-total");
        if (totEl)
            totEl.textContent = fmt(
                data.total,
                data.currency,
                data.currencySymbol,
            );

        var bd = document.getElementById("moga-price-breakdown");
        if (bd) {
            bd.removeAttribute("hidden");
            bd.style.display = "";
        }
    }

    /**
     * Same DOM targets as renderBreakdown(), but for already-formatted
     * price strings (e.g. "E£700.00") coming straight from the server
     * via moga_format_price() — no client-side currency-symbol
     * formatting involved at all, so this can't disagree with the
     * server on which symbol to use.
     */
    function renderBreakdownFormatted(data) {
        var label = document.getElementById("moga-nights-label");
        if (label) label.textContent = data.nightsLabel;

        var subEl = document.getElementById("moga-breakdown-subtotal");
        if (subEl) subEl.textContent = data.subtotal;

        var discRow = document.getElementById("moga-breakdown-discount-row");
        var discEl = document.getElementById("moga-breakdown-discount");
        var discLabel = document.getElementById(
            "moga-breakdown-discount-label",
        );
        var hasDiscount = (data.discountPercent || 0) > 0;

        // BUG FIX: the 'hidden' attribute alone wasn't enough here —
        // booking.css's ".moga-price-breakdown__row { display: flex; }"
        // rule sets display directly on this same element, and wins
        // the specificity tie against the browser's built-in
        // "[hidden] { display: none; }" rule (same specificity,
        // theme stylesheet loads later). Setting style.display
        // directly via JS always wins over any external stylesheet,
        // regardless of what it says.
        if (discRow) {
            if (hasDiscount) {
                discRow.removeAttribute("hidden");
                discRow.style.display = "";
            } else {
                discRow.setAttribute("hidden", "");
                discRow.style.display = "none";
            }
        }
        if (discEl) discEl.textContent = "\u2212" + data.discount;
        if (discLabel && hasDiscount) {
            discLabel.textContent =
                "Discount (" + Math.round(data.discountPercent) + "%)";
        }

        var totEl = document.getElementById("moga-breakdown-total");
        if (totEl) totEl.textContent = data.total;

        var bd = document.getElementById("moga-price-breakdown");
        if (bd) {
            // BUG FIX: hidePriceBreakdown() sets style.display = "none"
            // directly (needed to beat booking.css's own display rule
            // on this element). An inline style like that persists
            // independently of the 'hidden' attribute — removing just
            // the attribute was NOT enough to actually reveal the box
            // again, since the leftover inline style kept silently
            // overriding everything, even with correct data and a
            // removed attribute. Both must be cleared together.
            bd.removeAttribute("hidden");
            bd.style.display = "";
        }
    }

    /**
     * Update the top price badges (desktop card + mobile sticky bar)
     * with the real per-night average for the selected dates. Uses
     * price_per_night_avg_formatted / price_per_night_avg_original_formatted
     * from the AJAX response — server-formatted, so no client-side
     * currency-symbol guessing.
     *
     * BUG FIX: previously only ever updated TEXT content, never
     * toggled visibility — so picking a period with a different (or
     * zero) discount than whatever the page happened to load with
     * left a stale discount chip and strikethrough price visible,
     * showing the WRONG percentage next to a correctly-recalculated
     * (and correctly zero, when applicable) dollar amount. Discount
     * is a per-period value, not a fixed page-load constant — every
     * element here now explicitly shows/hides based on this specific
     * response's real discount_percent, not whatever was true when
     * the page first rendered.
     */
    function updatePriceBadges(p) {
        if (!p.price_per_night_avg_formatted) return; // Tour pricing has no per-night concept.

        var hasDiscount = (p.discount_percent || 0) > 0;

        var current = document.getElementById("moga-badge-price-current");
        if (current) current.textContent = p.price_per_night_avg_formatted;

        // BUG FIX: 'hidden' attribute alone wasn't enough — booking.css
        // sets 'display' directly on these classes (e.g.
        // ".moga-booking-form-card__discount { display: inline-flex; }"),
        // which wins the specificity tie against the browser's
        // built-in "[hidden] { display: none; }" rule. Setting
        // style.display directly via JS always wins regardless.
        var old = document.getElementById("moga-badge-price-old");
        if (old) {
            if (hasDiscount && p.price_per_night_avg_original_formatted) {
                old.textContent = p.price_per_night_avg_original_formatted;
                old.removeAttribute("hidden");
                old.style.display = "";
            } else {
                old.setAttribute("hidden", "");
                old.style.display = "none";
            }
        }

        var discountChip = document.getElementById("moga-badge-discount");
        if (discountChip) {
            if (hasDiscount) {
                discountChip.textContent =
                    "-" + Math.round(p.discount_percent) + "%";
                discountChip.removeAttribute("hidden");
                discountChip.style.display = "";
            } else {
                discountChip.setAttribute("hidden", "");
                discountChip.style.display = "none";
            }
        }

        var mobileCurrent = document.getElementById(
            "moga-mobile-badge-price-current",
        );
        if (mobileCurrent)
            mobileCurrent.textContent = p.price_per_night_avg_formatted;

        var mobileOld = document.getElementById("moga-mobile-badge-price-old");
        if (mobileOld) {
            if (hasDiscount && p.price_per_night_avg_original_formatted) {
                mobileOld.textContent =
                    p.price_per_night_avg_original_formatted;
                mobileOld.removeAttribute("hidden");
                mobileOld.style.display = "";
            } else {
                mobileOld.setAttribute("hidden", "");
                mobileOld.style.display = "none";
            }
        }
    }

    // ============================================================
    // 04. GUEST COUNTER
    // ============================================================

    function initGuestCounter(config) {
        var minus = document.getElementById("moga-guests-minus");
        var plus = document.getElementById("moga-guests-plus");
        var display = document.getElementById("moga-guests-display");
        var input = document.getElementById("moga-guests-input");
        if (!minus || !plus || !display || !input) return;

        var max = config ? config.maxGuests || 10 : 10;
        var count = parseInt(input.value, 10) || 1;

        function render() {
            display.textContent = count + (count === 1 ? " guest" : " guests");
            input.value = count;
            minus.disabled = count <= 1;
            plus.disabled = count >= max;
        }

        minus.addEventListener("click", function () {
            if (count > 1) {
                count--;
                render();
            }
        });
        plus.addEventListener("click", function () {
            if (count < max) {
                count++;
                render();
            }
        });

        render();
    }

    // ============================================================
    // 05. DESCRIPTION READ MORE
    // ============================================================

    function initDescriptionToggle() {
        var btn = document.getElementById("moga-description-toggle");
        var content = document.getElementById("moga-description-content");
        if (!btn || !content) return;

        btn.addEventListener("click", function () {
            var open = btn.getAttribute("aria-expanded") === "true";
            if (open) {
                content.classList.remove("moga-property-description--expanded");
                content.classList.add("moga-property-description--collapsed");
                btn.setAttribute("aria-expanded", "false");
                btn.querySelector("svg").style.transform = "";
            } else {
                content.classList.remove(
                    "moga-property-description--collapsed",
                );
                content.classList.add("moga-property-description--expanded");
                btn.setAttribute("aria-expanded", "true");
                btn.querySelector("svg").style.transform = "rotate(180deg)";
            }
            // Update button text node (first text node).
            var textNode = Array.from(btn.childNodes).find(function (n) {
                return n.nodeType === 3;
            });
            if (textNode)
                textNode.textContent = open ? " Show more " : " Show less ";
        });
    }

    // ============================================================
    // 06. AMENITIES SHOW ALL
    // ============================================================

    function initAmenitiesToggle() {
        var btn = document.getElementById("moga-amenities-toggle");
        var grid = document.getElementById("moga-amenities-grid");
        if (!btn || !grid) return;

        btn.addEventListener("click", function () {
            var open = btn.getAttribute("aria-expanded") === "true";
            var hidden = grid.querySelectorAll(".moga-amenity-item--hidden");
            hidden.forEach(function (el) {
                el.classList.toggle("is-visible", !open);
            });
            btn.setAttribute("aria-expanded", open ? "false" : "true");
        });
    }

    // ============================================================
    // 07. MOBILE STICKY BAR
    // ============================================================

    function initMobileStickyBar() {
        var bar = document.getElementById("moga-mobile-booking-bar");
        var sidebar = document.getElementById("moga-booking-sidebar");
        if (!bar) return;

        var threshold = 300;

        if (sidebar) {
            var rect = sidebar.getBoundingClientRect();
            threshold = rect.bottom + window.pageYOffset;
        }

        function onScroll() {
            if (window.pageYOffset > threshold) {
                bar.style.display = "flex";
                bar.removeAttribute("aria-hidden");
            } else {
                bar.style.display = "none";
                bar.setAttribute("aria-hidden", "true");
            }
        }

        window.addEventListener("scroll", onScroll, { passive: true });
        onScroll();
    }

    // ============================================================
    // 08. SECTION NAV — ACTIVE ON SCROLL
    // ============================================================

    function initSectionNav() {
        var links = document.querySelectorAll(".moga-section-nav__link");
        var sections = [];

        links.forEach(function (link) {
            var href = link.getAttribute("href");
            if (href && href.startsWith("#")) {
                var el = document.getElementById(href.slice(1));
                if (el) sections.push({ link: link, el: el });
            }
        });

        if (sections.length === 0) return;

        function onScroll() {
            var scrollY = window.pageYOffset + 120; // offset for sticky header + nav
            var active = null;

            sections.forEach(function (item) {
                if (item.el.offsetTop <= scrollY) {
                    active = item;
                }
            });

            links.forEach(function (l) {
                l.classList.remove("is-active");
            });
            if (active) active.link.classList.add("is-active");
        }

        window.addEventListener("scroll", onScroll, { passive: true });
        onScroll();

        // Smooth scroll on click.
        links.forEach(function (link) {
            link.addEventListener("click", function (e) {
                var href = link.getAttribute("href");
                if (href && href.startsWith("#")) {
                    e.preventDefault();
                    var target = document.getElementById(href.slice(1));
                    if (target) {
                        var top = target.offsetTop - 120;
                        window.scrollTo({ top: top, behavior: "smooth" });
                    }
                }
            });
        });
    }

    // ============================================================
    // 09. SHARE BUTTON
    // ============================================================

    function initShareButton() {
        var btn = document.getElementById("moga-share-btn");
        if (!btn) return;

        btn.addEventListener("click", function () {
            var url = window.location.href;
            var title = document.title;

            if (navigator.share) {
                navigator
                    .share({ title: title, url: url })
                    .catch(function () {});
            } else if (navigator.clipboard) {
                navigator.clipboard
                    .writeText(url)
                    .then(function () {
                        var original = btn.innerHTML;
                        btn.textContent = "Link copied!";
                        setTimeout(function () {
                            btn.innerHTML = original;
                        }, 2000);
                    })
                    .catch(function () {});
            }
        });
    }

    // ============================================================
    // 10. TOUR DATE PICKER
    // ============================================================

    // Restricts the tour date field to bookable days only:
    //   - If startDates is non-empty, ONLY those exact dates are enabled
    //     (specific scheduled departures take priority).
    //   - Otherwise, dates are enabled by weekday according to availableDays
    //     (0=Sun .. 6=Sat). An empty availableDays array means "no
    //     restriction" — every day from today onward is enabled.
    function initTourDatePicker(config) {
        if (typeof flatpickr === "undefined" || !config) return;

        var dateEl = document.getElementById("moga-tour-date");
        if (!dateEl) return;

        var today = new Date();
        today.setHours(0, 0, 0, 0);

        var opts = {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "D, M j, Y",
            minDate: today,
            disableMobile: false,
        };

        var startDates = (config.startDates || []).filter(Boolean);

        if (startDates.length > 0) {
            // Specific scheduled departure dates only.
            opts.enable = startDates;
        } else if (config.availableDays && config.availableDays.length > 0) {
            // Weekday whitelist.
            var allowedDays = config.availableDays;
            opts.disable = [
                function (date) {
                    return allowedDays.indexOf(date.getDay()) === -1;
                },
            ];
        }
        // If neither is set, every future date is bookable — no restriction.

        flatpickr(dateEl, opts);
    }

    // ============================================================
    // 11. TOUR PARTICIPANT COUNTERS
    // ============================================================

    function initParticipantCounters(config) {
        var maxTotal = config ? config.maxParticipants || 20 : 20;

        var groups = [
            { key: "adults", min: 1 },
            { key: "children", min: 0 },
            { key: "infants", min: 0 },
        ];

        function getCount(key) {
            var input = document.getElementById("moga-" + key + "-input");
            return input ? parseInt(input.value, 10) || 0 : 0;
        }

        function totalCount() {
            return groups.reduce(function (sum, g) {
                return sum + getCount(g.key);
            }, 0);
        }

        function render(key, min) {
            var display = document.getElementById("moga-" + key + "-display");
            var input = document.getElementById("moga-" + key + "-input");
            var minus = document.getElementById("moga-" + key + "-minus");
            var plus = document.getElementById("moga-" + key + "-plus");
            if (!display || !input || !minus || !plus) return;

            var count = getCount(key);
            display.textContent = count;
            minus.disabled = count <= min;
            plus.disabled = totalCount() >= maxTotal;
        }

        groups.forEach(function (g) {
            var input = document.getElementById("moga-" + g.key + "-input");
            var minus = document.getElementById("moga-" + g.key + "-minus");
            var plus = document.getElementById("moga-" + g.key + "-plus");
            if (!input || !minus || !plus) return;

            minus.addEventListener("click", function () {
                var count = getCount(g.key);
                if (count > g.min) {
                    input.value = count - 1;
                    groups.forEach(function (gg) {
                        render(gg.key, gg.min);
                    });
                    updateTourPriceBreakdown(config);
                }
            });

            plus.addEventListener("click", function () {
                if (totalCount() < maxTotal) {
                    input.value = getCount(g.key) + 1;
                    groups.forEach(function (gg) {
                        render(gg.key, gg.min);
                    });
                    updateTourPriceBreakdown(config);
                }
            });

            render(g.key, g.min);
        });
    }

    // ============================================================
    // 12. TOUR PRICE BREAKDOWN
    // ============================================================

    function updateTourPriceBreakdown(config) {
        if (!config) return;

        var adultsEl = document.getElementById("moga-adults-input");
        var childrenEl = document.getElementById("moga-children-input");
        var infantsEl = document.getElementById("moga-infants-input");

        var adults = adultsEl ? parseInt(adultsEl.value, 10) || 1 : 1;
        var children = childrenEl ? parseInt(childrenEl.value, 10) || 0 : 0;
        var infants = infantsEl ? parseInt(infantsEl.value, 10) || 0 : 0;

        var priceAdult = config.pricePerPerson || 0;
        var priceChild = config.priceChild || 0;
        var priceInfant = config.priceInfant || 0;
        var groupDiscount = config.groupDiscount || 0;
        var currency = config.currency || "";

        var subtotal =
            priceAdult * adults + priceChild * children + priceInfant * infants;
        var disc = groupDiscount > 0 ? subtotal * (groupDiscount / 100) : 0;
        var total = subtotal - disc;

        var totalParticipants = adults + children + infants;

        var label = document.getElementById("moga-participants-label");
        if (label) {
            var parts = [];
            if (adults > 0)
                parts.push(adults + (adults === 1 ? " adult" : " adults"));
            if (children > 0)
                parts.push(
                    children + (children === 1 ? " child" : " children"),
                );
            if (infants > 0)
                parts.push(infants + (infants === 1 ? " infant" : " infants"));
            label.textContent =
                fmt(priceAdult, currency) +
                " \u00d7 " +
                (parts.join(", ") || totalParticipants + " participants");
        }

        var subEl = document.getElementById("moga-breakdown-subtotal");
        if (subEl) subEl.textContent = fmt(subtotal, currency);

        var discEl = document.getElementById("moga-breakdown-discount");
        if (discEl) discEl.textContent = "\u2212" + fmt(disc, currency);

        var totEl = document.getElementById("moga-breakdown-total");
        if (totEl) totEl.textContent = fmt(total, currency);

        var bd = document.getElementById("moga-price-breakdown");
        if (bd) {
            bd.removeAttribute("hidden");
            bd.style.display = "";
        }
    }

    // ============================================================
    // BOOT
    // ============================================================

    document.addEventListener("DOMContentLoaded", function () {
        var config = getConfig();

        initGallery();
        initDatePickers(config);
        initGuestCounter(config);
        updatePriceBreakdown(config); // Show default 1-night price on load.
        initDescriptionToggle();
        initAmenitiesToggle();
        initMobileStickyBar();
        initSectionNav();
        initShareButton();

        // Tour page — elements are absent on property pages, so each
        // function below early-returns harmlessly if its DOM isn't found.
        var tourConfig = getTourConfig();
        initTourDatePicker(tourConfig);
        initParticipantCounters(tourConfig);
        updateTourPriceBreakdown(tourConfig); // Show default 1-adult price on load.
    });
})();
