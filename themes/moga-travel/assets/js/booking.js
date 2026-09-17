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
 *   13. Tour seat map — seat grid, reserve/release AJAX, hold countdown
 *   14. Tour seat availability indicator — "N of M seats available" on tour page
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

    // Seat map config — only present on the Booking page when the
    // tour has an assigned bus. Separate from the tour booking config
    // (which lives on the single tour page) so the two pages stay
    // completely independent.
    function getSeatMapConfig() {
        var el = document.getElementById("moga-seat-map-config");
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

        // Matches the same 12-hour conversion PHP's date_i18n('g:i A', ...)
        // already does elsewhere — "14:00" -> "2:00 PM".
        function formatTime12h(time24) {
            if (!time24) return "";
            var parts = time24.split(":");
            var hour = parseInt(parts[0], 10);
            var minute = parts[1] || "00";
            var ampm = hour >= 12 ? "PM" : "AM";
            var hour12 = hour % 12;
            if (hour12 === 0) hour12 = 12;
            return hour12 + ":" + minute + " " + ampm;
        }

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

            // Dynamic period-info line — the replacement for the
            // static, per-period-but-shown-as-property-wide House
            // Rules lines removed from single-moga_property.php.
            // Empty and hidden until a real period actually matches;
            // one compact line, not several separate blocks.
            var infoEl = document.getElementById("moga-booking-period-info");
            if (infoEl) {
                if (period) {
                    var infoParts = [];
                    if (period.checkin_time) {
                        infoParts.push(
                            formatTime12h(period.checkin_time) + " check-in",
                        );
                    }
                    if (period.checkout_time) {
                        infoParts.push(
                            formatTime12h(period.checkout_time) + " check-out",
                        );
                    }
                    infoParts.push(
                        maxStay > 0
                            ? minStay + "\u2013" + maxStay + " nights"
                            : minStay + "+ nights",
                    );
                    infoEl.textContent =
                        "This period: " + infoParts.join(" \u00b7 ");
                    infoEl.removeAttribute("hidden");
                    infoEl.style.display = "";
                } else {
                    infoEl.setAttribute("hidden", "");
                    infoEl.style.display = "none";
                }
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
        // date fields. Uses setDate() without the trigger flag so
        // Flatpickr updates the visible altInput without firing
        // onClose (which would open the checkout picker prematurely).
        // applyCheckinConstraints is called manually here to set
        // the checkout minDate before setDate on the checkout picker.
        var periodsListEl = document.getElementById("moga-available-periods");
        if (periodsListEl) {
            periodsListEl.addEventListener("click", function (e) {
                var btn = e.target.closest(".moga-available-periods__item");
                if (!btn) return;

                var start = btn.getAttribute("data-start");
                if (!start) return;

                // setDate(value, false) — updates the input and altInput
                // display without firing onClose or onChange events.
                // This prevents the checkout picker from auto-opening
                // and avoids double-calling applyCheckinConstraints.
                checkinPicker.setDate(start, false);

                // Manually apply constraints so checkout picker knows
                // the correct minDate and maxDate for this period.
                var minOut = applyCheckinConstraints(start);

                // Set checkout to the minimum allowed date for this period.
                checkoutPicker.setDate(minOut, false);

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
        if (!checkIn || !checkOut) {
            hidePriceBreakdown();
            return;
        }

        fetchServerPrice(config, checkIn, checkOut);
    }

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

                renderBreakdownFormatted({
                    weekdayNights: p.weekday_nights || 0,
                    weekdaySubtotal:
                        p.weekday_subtotal_formatted ||
                        fmt(p.weekday_subtotal || 0, p.currency),
                    weekendNights: p.weekend_nights || 0,
                    weekendSubtotal:
                        p.weekend_subtotal_formatted ||
                        fmt(p.weekend_subtotal || 0, p.currency),
                    discount:
                        p.discount_formatted ||
                        fmt(p.discount || 0, p.currency),
                    total: p.total_formatted || fmt(p.total || 0, p.currency),
                    discountPercent: p.discount_percent || 0,
                });

                updatePriceBadges(p);
            })
            .catch(function () {});
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

    function renderBreakdownFormatted(data) {
        var weekdayLabel = document.getElementById(
            "moga-breakdown-weekday-label",
        );
        if (weekdayLabel) {
            weekdayLabel.textContent =
                data.weekdayNights +
                (data.weekdayNights === 1
                    ? " regular night"
                    : " regular nights");
        }
        var weekdaySubEl = document.getElementById(
            "moga-breakdown-weekday-subtotal",
        );
        if (weekdaySubEl) weekdaySubEl.textContent = data.weekdaySubtotal;

        var weekendLabel = document.getElementById(
            "moga-breakdown-weekend-label",
        );
        if (weekendLabel) {
            weekendLabel.textContent =
                data.weekendNights +
                (data.weekendNights === 1
                    ? " weekend night"
                    : " weekend nights");
        }
        var weekendSubEl = document.getElementById(
            "moga-breakdown-weekend-subtotal",
        );
        if (weekendSubEl) weekendSubEl.textContent = data.weekendSubtotal;

        var discEl = document.getElementById("moga-breakdown-discount");
        var discLabel = document.getElementById(
            "moga-breakdown-discount-label",
        );
        if (discEl) discEl.textContent = "\u2212" + data.discount;
        if (discLabel) {
            discLabel.textContent =
                "Discount (" + Math.round(data.discountPercent || 0) + "%)";
        }

        var totEl = document.getElementById("moga-breakdown-total");
        if (totEl) totEl.textContent = data.total;

        var bd = document.getElementById("moga-price-breakdown");
        if (bd) {
            bd.removeAttribute("hidden");
            bd.style.display = "";
        }
    }

    function updatePriceBadges(p) {
        if (!p.price_per_night_avg_formatted) return;

        var hasDiscount = (p.discount_percent || 0) > 0;

        var current = document.getElementById("moga-badge-price-current");
        if (current) current.textContent = p.price_per_night_avg_formatted;

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
            var scrollY = window.pageYOffset + 120;
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
    //     REBUILT (Tour Groups): restricted to real Group start
    //     dates only — same whitelist technique already used for
    //     property periods — instead of the old flat
    //     availableDays/startDates fields, which had no real
    //     capacity or pricing behind them at all.
    // ============================================================

    function initTourDatePicker(config) {
        if (typeof flatpickr === "undefined" || !config) return;

        var dateEl = document.getElementById("moga-tour-date");
        if (!dateEl) return;

        var today = new Date();
        today.setHours(0, 0, 0, 0);

        var groups = config.groups || [];
        var startDates = groups
            .filter(function (g) {
                return g.seats_remaining > 0;
            })
            .map(function (g) {
                return g.start;
            });

        var opts = {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "D, M j, Y",
            minDate: today,
            disableMobile: false,
            enable: startDates,
        };

        var picker = flatpickr(dateEl, opts);

        // Jump to the month of the earliest available group — so
        // a guest doesn't land on September when all departures
        // are in October or later. Only when no date is pre-filled.
        if (!dateEl.value && startDates.length > 0) {
            var earliest = startDates.slice().sort()[0];
            picker.jumpToDate(earliest);
        }

        function findGroup(dateStr) {
            for (var i = 0; i < groups.length; i++) {
                if (groups[i].start === dateStr) return groups[i];
            }
            return null;
        }

        function selectGroup(dateStr) {
            var group = findGroup(dateStr);
            updateTourGroupInfo(group);
            updateTourPriceDisplays(group);
            updateSeatAvailabilityIndicator(group, config);
            fetchTourServerPrice(config, dateStr);
            updateDepartureSummary(group);

            // Sync the hidden input that the form submits — the visible
            // <select> carries the display value but the hidden input
            // carries the Y-m-d that the Booking page reads from $_GET.
            var hiddenInput = document.getElementById("moga-tour-date-input");
            if (hiddenInput) hiddenInput.value = dateStr;

            // Sync the departure dropdown if it exists.
            var dropdownEl = document.getElementById("moga-tour-date-select");
            if (dropdownEl && dropdownEl.value !== dateStr) {
                dropdownEl.value = dateStr;
            }
        }

        // Sync from Flatpickr calendar → dropdown.
        dateEl.addEventListener("change", function () {
            if (dateEl.value) selectGroup(dateEl.value);
        });

        // Sync from dropdown → Flatpickr calendar.
        var dropdownEl = document.getElementById("moga-tour-date-select");
        if (dropdownEl) {
            dropdownEl.addEventListener("change", function () {
                var val = dropdownEl.value;
                if (!val) return;
                picker.setDate(val, true);
                selectGroup(val);
            });
        }

        // Legacy: stacked list buttons (kept for graceful degradation).
        var list = document.getElementById("moga-available-departures");
        if (list) {
            list.addEventListener("click", function (e) {
                var btn = e.target.closest(".moga-available-periods__item");
                if (!btn || btn.disabled) return;
                var start = btn.getAttribute("data-start");
                if (!start) return;
                picker.setDate(start, true);
                selectGroup(start);
            });
        }

        if (dateEl.value) selectGroup(dateEl.value);
    }

    function updateTourGroupInfo(group) {
        var infoEl = document.getElementById("moga-tour-group-info");
        if (!infoEl) return;

        if (!group) {
            infoEl.setAttribute("hidden", "");
            infoEl.style.display = "none";
            return;
        }

        var parts = [];
        if (group.end && group.end !== group.start) {
            parts.push(
                formatDateHuman(group.start) + " \u2013 " + formatDateHuman(group.end),
            );
        } else {
            parts.push(formatDateHuman(group.start));
        }
        parts.push(
            group.seats_remaining > 0
                ? group.seats_remaining + " seats left"
                : "Sold out",
        );

        infoEl.textContent = "This departure: " + parts.join(" \u00b7 ");
        infoEl.removeAttribute("hidden");
        infoEl.style.display = "";
    }

    function formatDateHuman(dateStr) {
        if (!dateStr) return "";
        var d = new Date(dateStr + "T00:00:00");
        return d.toLocaleDateString(undefined, {
            month: "short",
            day: "numeric",
            year: "numeric",
        });
    }

    function updateTourPriceDisplays(group) {
        var currency = (getTourConfig() || {}).currency || "";

        var adultEl = document.getElementById("moga-price-adult-display");
        var childEl = document.getElementById("moga-price-child-display");
        var infantEl = document.getElementById("moga-price-infant-display");

        if (!group) return;

        if (adultEl) adultEl.textContent = fmt(group.price_adult || 0, currency);
        if (childEl) childEl.textContent = fmt(group.price_child || 0, currency);
        if (infantEl) {
            infantEl.textContent =
                group.price_infant > 0 ? fmt(group.price_infant, currency) : "Free";
        }
    }

    // ============================================================
    // 14. TOUR SEAT AVAILABILITY INDICATOR
    //     Updates the "N of M seats available" line on the single
    //     tour page sidebar when a departure is selected.
    //     Only shown when the tour has a bus (config.busId > 0).
    // ============================================================

    function updateSeatAvailabilityIndicator(group, config) {
        var el = document.getElementById("moga-seat-availability");
        var textEl = document.getElementById("moga-seat-availability-text");
        if (!el || !textEl) return;

        // No bus assigned to this tour — indicator never shows.
        if (!config || !config.busId || config.busId < 1) return;

        if (!group) {
            el.setAttribute("hidden", "");
            el.style.display = "none";
            return;
        }

        // bus_seats_available and bus_seats_total are pre-computed
        // by PHP (get_available_seat_count) and embedded in the
        // groups config — no extra AJAX call needed here.
        var available = group.bus_seats_available;
        var total     = group.bus_seats_total;

        if (available === null || available === undefined || total === null) {
            el.setAttribute("hidden", "");
            el.style.display = "none";
            return;
        }

        var text;
        if (available < 1) {
            text = "No seats available for this departure";
        } else if (available <= 5) {
            text = "Only " + available + " of " + total + " seats available!";
        } else {
            text = available + " of " + total + " seats available";
        }

        textEl.textContent = text;
        el.removeAttribute("hidden");
        el.style.display = "";
    }

    // ============================================================
    // 14b. DEPARTURE SUMMARY CARD
    //      Shows date range, price per person, and seats remaining
    //      below the dropdown after a departure is selected.
    // ============================================================

    function updateDepartureSummary(group) {
        var summaryEl = document.getElementById("moga-departure-summary");
        var datesEl   = document.getElementById("moga-departure-summary-dates");
        var metaEl    = document.getElementById("moga-departure-summary-meta");
        if (!summaryEl) return;

        if (!group) {
            summaryEl.setAttribute("hidden", "");
            summaryEl.style.display = "none";
            return;
        }

        var config = getTourConfig();
        var currency = config ? config.currency : "";

        // Date range.
        var dateStr = formatDateHuman(group.start);
        if (group.end && group.end !== group.start) {
            dateStr += " \u2192 " + formatDateHuman(group.end);
        }
        if (datesEl) datesEl.textContent = dateStr;

        // Meta: price + seats.
        if (metaEl) {
            var metaHtml = "";

            var priceStr = fmt(group.price_adult || 0, currency);
            metaHtml += '<span class="moga-departure-summary__meta-item">' + priceStr + " / person</span>";

            if (group.seats_remaining < 1) {
                metaHtml += '<span class="moga-departure-summary__meta-item moga-departure-summary__meta-item--urgency">Sold out</span>';
            } else if (group.seats_remaining <= 5) {
                metaHtml += '<span class="moga-departure-summary__meta-item moga-departure-summary__meta-item--urgency">Only ' + group.seats_remaining + ' spot' + (group.seats_remaining === 1 ? "" : "s") + ' left!</span>';
            } else {
                metaHtml += '<span class="moga-departure-summary__meta-item">' + group.seats_remaining + " spots available</span>";
            }

            metaEl.innerHTML = metaHtml;
        }

        summaryEl.removeAttribute("hidden");
        summaryEl.style.display = "";
    }

    // ============================================================
    // 11. TOUR PARTICIPANT COUNTERS
    //     REBUILT: max total now comes from the SELECTED group's
    //     own real capacity/seats remaining, not a stale flat
    //     tour-wide number.
    // ============================================================

    function getSelectedTourGroup(config) {
        var dateEl = document.getElementById("moga-tour-date");
        if (!dateEl || !dateEl.value || !config) return null;
        var groups = config.groups || [];
        for (var i = 0; i < groups.length; i++) {
            if (groups[i].start === dateEl.value) return groups[i];
        }
        return null;
    }

    function initParticipantCounters(config) {
        var groups = [
            { key: "adults", min: 1 },
            { key: "children", min: 0 },
            { key: "infants", min: 0 },
        ];

        function getCount(key) {
            var input = document.getElementById("moga-" + key + "-input");
            return input ? parseInt(input.value, 10) || 0 : 0;
        }

        // Only adults + children count against real seat capacity —
        // matches moga_get_tour_group_seats_taken()'s server-side
        // convention exactly (infants travel on an adult's lap).
        function seatCount() {
            return getCount("adults") + getCount("children");
        }

        function maxSeats() {
            var group = getSelectedTourGroup(config);
            return group ? group.seats_remaining : 999;
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

            plus.disabled = key === "infants" ? false : seatCount() >= maxSeats();
        }

        function renderAll() {
            groups.forEach(function (g) {
                render(g.key, g.min);
            });
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
                    renderAll();
                    var dateEl = document.getElementById("moga-tour-date");
                    if (dateEl && dateEl.value)
                        fetchTourServerPrice(config, dateEl.value);
                }
            });

            plus.addEventListener("click", function () {
                var withinSeatCap =
                    g.key === "infants" || seatCount() < maxSeats();
                if (withinSeatCap) {
                    input.value = getCount(g.key) + 1;
                    renderAll();
                    var dateEl = document.getElementById("moga-tour-date");
                    if (dateEl && dateEl.value)
                        fetchTourServerPrice(config, dateEl.value);
                }
            });
        });

        renderAll();
    }

    // ============================================================
    // 12. TOUR PRICE BREAKDOWN
    //     REBUILT: now a real, server-verified AJAX call — matching
    //     exactly how the property side already works — instead of
    //     pure client-side math with no capacity awareness at all.
    // ============================================================

    function fetchTourServerPrice(config, groupStart) {
        if (!config || !config.ajaxUrl || !config.nonce || !config.tourId) return;
        if (!groupStart) return;

        var adults = parseInt(
            (document.getElementById("moga-adults-input") || {}).value,
            10,
        ) || 1;
        var children = parseInt(
            (document.getElementById("moga-children-input") || {}).value,
            10,
        ) || 0;
        var infants = parseInt(
            (document.getElementById("moga-infants-input") || {}).value,
            10,
        ) || 0;

        var body = new URLSearchParams({
            action: "moga_calculate_price",
            nonce: config.nonce,
            listing_id: config.tourId,
            listing_type: "tour",
            check_in: groupStart,
            adults: adults,
            children: children,
            infants: infants,
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
                renderTourBreakdown(json.data.price);
            });
    }

    // Three separate lines (Adults/Children/Infants) — each entirely
    // hidden (not just zeroed) when its count is zero.
    function renderTourBreakdown(p) {
        var rows = [
            { key: "adults", count: p.adults, price: p.price_adult_formatted, total: p.adults_total_formatted, singular: "Adult", plural: "Adults" },
            { key: "children", count: p.children, price: p.price_child_formatted, total: p.children_total_formatted, singular: "Child", plural: "Children" },
            { key: "infants", count: p.infants, price: p.price_infant_formatted, total: p.infants_total_formatted, singular: "Infant", plural: "Infants" },
        ];

        rows.forEach(function (row) {
            var rowEl = document.getElementById("moga-breakdown-" + row.key + "-row");
            var labelEl = document.getElementById("moga-breakdown-" + row.key + "-label");
            var totalEl = document.getElementById("moga-breakdown-" + row.key + "-total");
            if (!rowEl) return;

            if (!row.count || row.count < 1) {
                rowEl.setAttribute("hidden", "");
                rowEl.style.display = "none";
                return;
            }

            rowEl.removeAttribute("hidden");
            rowEl.style.display = "";
            if (labelEl) {
                labelEl.textContent =
                    row.count +
                    " " +
                    (row.count === 1 ? row.singular : row.plural) +
                    " \u00d7 " +
                    row.price;
            }
            if (totalEl) totalEl.textContent = row.total;
        });

        var totEl = document.getElementById("moga-breakdown-total");
        if (totEl) totEl.textContent = p.total_formatted;

        var bd = document.getElementById("moga-price-breakdown");
        if (bd) {
            bd.removeAttribute("hidden");
            bd.style.display = "";
        }
    }

    // ============================================================
    // 13. TOUR SEAT MAP
    //     Only runs on the Booking page when:
    //       - #moga-seat-map-config JSON block is present
    //       - #moga-seat-map container exists in the DOM
    //
    //     Flow:
    //       1. Fetch full seat grid via moga_get_seat_map AJAX
    //       2. Render color-coded seat buttons in the grid
    //       3. Guest clicks a seat:
    //            - If available → reserve via AJAX → mark selected
    //            - If selected  → release via AJAX → mark available
    //            - If taken/unavailable → ignore
    //       4. Start/refresh 15-min countdown on first reservation
    //       5. Update submit button state (disabled until N seats selected)
    //       6. On beforeunload → release all held seats
    //       7. On countdown expiry → release all seats + show expired msg
    // ============================================================

    function initSeatMap(seatConfig) {
        if (!seatConfig) return;

        var mapEl      = document.getElementById("moga-seat-map");
        var loadingEl  = document.getElementById("moga-seat-map-loading");
        var submitBtn  = document.getElementById("moga-tour-review-submit");
        var noticeEl   = document.getElementById("moga-seat-required-notice");
        var countEl    = document.getElementById("moga-seats-selected-count");
        var labelsEl   = document.getElementById("moga-selected-seat-labels");
        var timerEl    = document.getElementById("moga-seat-hold-timer");
        var countdownEl = document.getElementById("moga-seat-hold-countdown");
        var seatsField = document.getElementById("moga-selected-seats-field");
        var sessionField = document.getElementById("moga-seat-session-field");

        if (!mapEl) return;

        // State.
        var selectedSeats  = [];   // seat numbers currently selected by this guest
        var sessionToken   = "";   // issued by server on first reserve call
        var holdExpiresAt  = null; // Date object — when the hold expires
        var countdownTimer = null; // setInterval handle
        var seatData       = {};   // seat_number → seat object from last server response

        var busId        = seatConfig.busId;
        var tourId       = seatConfig.tourId;
        var tripDate     = seatConfig.tripDate;
        var required     = seatConfig.requiredSeats;
        var ajaxUrl      = seatConfig.ajaxUrl;
        var nonce        = seatConfig.nonce;

        // ---- Fetch and render the seat grid ----
        function loadSeatMap() {
            showLoading(true);

            var body = new URLSearchParams({
                action:        "moga_get_seat_map",
                nonce:         nonce,
                bus_id:        busId,
                trip_date:     tripDate,
                session_token: sessionToken,
            });

            fetch(ajaxUrl, {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: body.toString(),
            })
                .then(function (res) { return res.json(); })
                .then(function (json) {
                    showLoading(false);
                    if (!json.success || !json.data) return;
                    renderSeatGrid(json.data);
                })
                .catch(function () {
                    showLoading(false);
                });
        }

        function showLoading(on) {
            if (loadingEl) {
                if (on) {
                    loadingEl.removeAttribute("hidden");
                    loadingEl.style.display = "";
                } else {
                    loadingEl.setAttribute("hidden", "");
                    loadingEl.style.display = "none";
                }
            }
            if (mapEl) {
                if (on) {
                    mapEl.setAttribute("hidden", "");
                    mapEl.style.display = "none";
                } else {
                    mapEl.removeAttribute("hidden");
                    mapEl.style.display = "";
                }
            }
        }

        // ---- Build the seat grid from server data ----
        function renderSeatGrid(data) {
            var seats   = data.seats   || [];
            var columns = data.columns || 4;
            var layout  = data.layout  || "2+2";
            var driver  = data.driver_position || "front-left";

            // Index seat data for quick lookup.
            seatData = {};
            seats.forEach(function (s) { seatData[s.seat_number] = s; });

            mapEl.innerHTML = "";

            // ---- Driver row ----
            var driverRow = document.createElement("div");
            driverRow.className = "moga-seat-row moga-seat-row--driver";
            driverRow.setAttribute("aria-hidden", "true");

            var driverCell = document.createElement("div");
            driverCell.className = "moga-seat moga-seat--driver " +
                ("front-left" === driver ? "moga-seat--driver-left" : "moga-seat--driver-right");
            driverCell.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>';
            driverCell.title = "Driver";

            if ("front-left" === driver) {
                driverRow.appendChild(driverCell);
            } else {
                // Push driver to the right.
                var spacer = document.createElement("div");
                spacer.className = "moga-seat moga-seat--spacer";
                for (var s = 0; s < columns - 1; s++) {
                    driverRow.appendChild(spacer.cloneNode(false));
                }
                driverRow.appendChild(driverCell);
            }
            mapEl.appendChild(driverRow);

            // ---- Seat rows ----
            // Group seats into rows by seat_row number.
            var rowMap = {};
            seats.forEach(function (seat) {
                var r = seat.seat_row;
                if (!rowMap[r]) rowMap[r] = [];
                rowMap[r].push(seat);
            });

            var rowNums = Object.keys(rowMap).map(Number).sort(function (a, b) { return a - b; });

            rowNums.forEach(function (rowNum) {
                var rowSeats = rowMap[rowNum];
                var rowEl = document.createElement("div");
                rowEl.className = "moga-seat-row";
                rowEl.setAttribute("data-row", rowNum);

                // Determine aisle position from layout.
                // 2+2 → aisle after column 2
                // 2+3 → aisle after column 2
                // 1+2 → aisle after column 1
                // 1+1 → aisle after column 1
                var aisleAfter = Math.floor(columns / 2);
                if (layout === "1+2") aisleAfter = 1;
                if (layout === "1+1") aisleAfter = 1;

                rowSeats.forEach(function (seat, idx) {
                    // Insert aisle gap.
                    if (idx === aisleAfter) {
                        var aisle = document.createElement("div");
                        aisle.className = "moga-seat-aisle";
                        rowEl.appendChild(aisle);
                    }

                    var btn = buildSeatButton(seat);
                    rowEl.appendChild(btn);
                });

                mapEl.appendChild(rowEl);
            });
        }

        // ---- Build a single seat button element ----
        function buildSeatButton(seat) {
            var btn = document.createElement("button");
            btn.type = "button";
            btn.className = "moga-seat";
            btn.setAttribute("data-seat", seat.seat_number);

            // Normalise: a seat marked disabled by type is always
            // unavailable regardless of its DB status — must be
            // resolved BEFORE applyStatusClasses so the color class
            // reflects the real state (not "available green" + disabled).
            var effectiveStatus = seat.status;
            if (seat.seat_type === "disabled") {
                effectiveStatus = "unavailable";
            }

            // Tooltip + aria label — status + type in plain English.
            var typeLabel   = seat.seat_type === "vip"     ? " · VIP"
                            : seat.seat_type === "disabled" ? " · Unavailable"
                            : "";
            var statusLabel = effectiveStatus === "available"      ? "Available"
                            : effectiveStatus === "reserved_by_me" ? "Your selection"
                            : effectiveStatus === "reserved"        ? "Held by another guest"
                            : effectiveStatus === "booked"          ? "Taken"
                            : "Not available";

            var tooltipText = "Seat " + seat.seat_number + typeLabel + " — " + statusLabel;
            btn.setAttribute("data-status-label", tooltipText);
            btn.setAttribute("aria-label", tooltipText);

            applyStatusClasses(btn, effectiveStatus, seat.seat_type);

            // Disable non-selectable seats.
            if (effectiveStatus !== "available") {
                btn.disabled = true;
            }

            btn.addEventListener("click", function () {
                handleSeatClick(seat.seat_number);
            });

            // Seat label inside.
            var label = document.createElement("span");
            label.className = "moga-seat__label";
            label.textContent = seat.seat_number;
            btn.appendChild(label);

            return btn;
        }

        function applyStatusClasses(btn, status, type) {
            btn.className = "moga-seat";

            if (type === "vip")      btn.classList.add("moga-seat--vip");
            if (type === "disabled") btn.classList.add("moga-seat--disabled");

            switch (status) {
                case "available":
                    btn.classList.add("moga-seat--available");
                    break;
                case "reserved_by_me":
                case "selected":
                    btn.classList.add("moga-seat--selected");
                    break;
                case "reserved":
                case "booked":
                case "taken":
                    btn.classList.add("moga-seat--taken");
                    break;
                case "unavailable":
                default:
                    btn.classList.add("moga-seat--unavailable");
                    break;
            }

            // Refresh tooltip to reflect current state.
            var seatNumber  = btn.getAttribute("data-seat") || "";
            var typeLabel   = type === "vip"     ? " · VIP"
                            : type === "disabled" ? " · Unavailable"
                            : "";
            var statusLabel = status === "available"      ? "Available"
                            : status === "reserved_by_me" ? "Your selection"
                            : status === "selected"        ? "Your selection"
                            : status === "reserved"        ? "Held by another guest"
                            : status === "booked"          ? "Taken"
                            : status === "taken"           ? "Taken"
                            : "Not available";
            var tip = "Seat " + seatNumber + typeLabel + " — " + statusLabel;
            btn.setAttribute("data-status-label", tip);
            btn.setAttribute("aria-label", tip);
        }

        // ---- Handle a seat click ----
        function handleSeatClick(seatNumber) {
            var seat = seatData[seatNumber];
            if (!seat) return;

            var isSelected = selectedSeats.indexOf(seatNumber) !== -1;

            if (isSelected) {
                // Deselect — release this seat.
                releaseSeat(seatNumber);
            } else {
                // Can the guest select another seat?
                if (selectedSeats.length >= required) {
                    // Already at limit — flash a message, don't block.
                    flashSelectionStatus("You need " + required + " seat" + (required === 1 ? "" : "s") + ". Deselect one first.");
                    return;
                }
                reserveSeat(seatNumber);
            }
        }

        // ---- Reserve a single seat via AJAX ----
        function reserveSeat(seatNumber) {
            var seatsToReserve = [seatNumber];

            var body = new URLSearchParams({
                action:        "moga_reserve_seats",
                nonce:         nonce,
                bus_id:        busId,
                tour_id:       tourId,
                trip_date:     tripDate,
                seats:         JSON.stringify(seatsToReserve),
                session_token: sessionToken,
            });

            fetch(ajaxUrl, {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: body.toString(),
            })
                .then(function (res) { return res.json(); })
                .then(function (json) {
                    if (!json.success || !json.data) return;

                    var data = json.data;

                    // Store session token issued by server.
                    if (data.session_token && !sessionToken) {
                        sessionToken = data.session_token;
                        if (sessionField) sessionField.value = sessionToken;
                    }

                    // Mark successfully reserved seats.
                    data.reserved.forEach(function (sn) {
                        if (selectedSeats.indexOf(sn) === -1) {
                            selectedSeats.push(sn);
                        }
                        updateSeatButtonStatus(sn, "selected");
                    });

                    // Mark seats taken by someone else.
                    data.taken.forEach(function (sn) {
                        updateSeatButtonStatus(sn, "taken");
                        if (seatData[sn]) seatData[sn].status = "booked";
                    });

                    // Start or refresh the hold countdown.
                    if (data.expires_at && data.reserved.length > 0) {
                        startCountdown(new Date(data.expires_at));
                    }

                    if (data.taken.length > 0) {
                        flashSelectionStatus("Seat " + data.taken.join(", ") + " was just taken. Please choose another.");
                    }

                    updateSelectionUI();
                })
                .catch(function () {});
        }

        // ---- Release a single seat via AJAX ----
        function releaseSeat(seatNumber) {
            if (!sessionToken) return;

            var body = new URLSearchParams({
                action:        "moga_release_seats",
                nonce:         nonce,
                bus_id:        busId,
                trip_date:     tripDate,
                seats:         JSON.stringify([seatNumber]),
                session_token: sessionToken,
            });

            fetch(ajaxUrl, {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: body.toString(),
            })
                .then(function (res) { return res.json(); })
                .then(function () {
                    var idx = selectedSeats.indexOf(seatNumber);
                    if (idx !== -1) selectedSeats.splice(idx, 1);
                    updateSeatButtonStatus(seatNumber, "available");
                    updateSelectionUI();
                })
                .catch(function () {});
        }

        // ---- Release ALL held seats (beforeunload / timeout) ----
        function releaseAllSeats(sync) {
            if (!sessionToken || selectedSeats.length === 0) return;

            var body = new URLSearchParams({
                action:        "moga_release_seats",
                nonce:         nonce,
                bus_id:        busId,
                trip_date:     tripDate,
                seats:         JSON.stringify([]),   // empty = release all held by session
                session_token: sessionToken,
            });

            // Use sendBeacon for beforeunload — fetch is not reliable there.
            if (sync && navigator.sendBeacon) {
                navigator.sendBeacon(ajaxUrl, body);
            } else {
                fetch(ajaxUrl, {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: body.toString(),
                }).catch(function () {});
            }
        }

        // ---- Update a single seat button's visual state ----
        function updateSeatButtonStatus(seatNumber, status) {
            var btn = mapEl.querySelector('[data-seat="' + seatNumber + '"]');
            if (!btn) return;

            var seat = seatData[seatNumber] || { seat_type: "standard" };
            applyStatusClasses(btn, status, seat.seat_type);

            btn.disabled = (status === "taken" || status === "unavailable" || status === "booked");
        }

        // ---- Update the selection count, labels, submit button ----
        function updateSelectionUI() {
            var count = selectedSeats.length;

            if (countEl) countEl.textContent = count;

            if (labelsEl) {
                labelsEl.textContent = count > 0
                    ? "(" + selectedSeats.slice().sort().join(", ") + ")"
                    : "";
            }

            if (seatsField) seatsField.value = selectedSeats.join(",");

            var complete = count >= required;

            if (submitBtn) {
                submitBtn.disabled = !complete;
            }
            if (noticeEl) {
                if (complete) {
                    noticeEl.setAttribute("hidden", "");
                    noticeEl.style.display = "none";
                } else {
                    noticeEl.removeAttribute("hidden");
                    noticeEl.style.display = "";
                }
            }
        }

        // ---- Flash a temporary status message ----
        function flashSelectionStatus(msg) {
            var statusEl = document.getElementById("moga-seat-selection-status");
            if (!statusEl) return;
            var original = statusEl.textContent;
            statusEl.textContent = msg;
            statusEl.classList.add("moga-seat-selection-status--flash");
            setTimeout(function () {
                statusEl.classList.remove("moga-seat-selection-status--flash");
                updateSelectionUI(); // restore real count
            }, 2500);
        }

        // ---- Hold countdown timer ----
        function startCountdown(expiresAt) {
            holdExpiresAt = expiresAt;

            if (timerEl) {
                timerEl.removeAttribute("hidden");
                timerEl.style.display = "";
            }

            if (countdownTimer) clearInterval(countdownTimer);

            countdownTimer = setInterval(function () {
                var remaining = Math.max(0, holdExpiresAt - Date.now());
                var mins = Math.floor(remaining / 60000);
                var secs = Math.floor((remaining % 60000) / 1000);

                if (countdownEl) {
                    countdownEl.textContent =
                        mins + ":" + (secs < 10 ? "0" : "") + secs;
                }

                if (remaining <= 0) {
                    clearInterval(countdownTimer);
                    onHoldExpired();
                }
            }, 1000);
        }

        function onHoldExpired() {
            selectedSeats = [];
            sessionToken  = "";
            if (seatsField)  seatsField.value  = "";
            if (sessionField) sessionField.value = "";

            if (timerEl) {
                timerEl.setAttribute("hidden", "");
                timerEl.style.display = "none";
            }

            updateSelectionUI();
            // Reload the seat map so released seats show as available again.
            loadSeatMap();

            flashSelectionStatus("Your seat hold expired. Please reselect your seats.");
        }

        // ---- Release seats when the guest leaves the page ----
        window.addEventListener("beforeunload", function () {
            releaseAllSeats(true); // sync via sendBeacon
        });

        // ---- Init ----
        loadSeatMap();
        updateSelectionUI();
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

        // Property booking form — collapsible Available Dates toggle.
        var periodsToggle = document.getElementById("moga-periods-toggle");
        var periodsList   = document.getElementById("moga-periods-list");
        if (periodsToggle && periodsList) {
            periodsToggle.addEventListener("click", function () {
                var expanded = periodsToggle.getAttribute("aria-expanded") === "true";
                periodsToggle.setAttribute("aria-expanded", expanded ? "false" : "true");
                if (expanded) {
                    periodsList.setAttribute("hidden", "");
                    periodsList.style.display = "none";
                } else {
                    periodsList.removeAttribute("hidden");
                    periodsList.style.display = "";
                }
            });
        }

        // Tour single page — elements are absent on property pages,
        // so each function below early-returns harmlessly if its DOM
        // isn't found.
        var tourConfig = getTourConfig();
        initTourDatePicker(tourConfig);
        initParticipantCounters(tourConfig);

        // Calendar browse button — opens Flatpickr when clicked.
        // The real Flatpickr input is hidden; this button triggers it.
        var calBtn = document.getElementById("moga-tour-calendar-btn");
        if (calBtn) {
            calBtn.addEventListener("click", function () {
                var hiddenInput = document.getElementById("moga-tour-date");
                if (hiddenInput && hiddenInput._flatpickr) {
                    hiddenInput._flatpickr.open();
                }
            });
        }

        // If a departure is pre-selected from URL, show summary immediately.
        if (tourConfig) {
            var preDate = document.getElementById("moga-tour-date");
            if (preDate && preDate.value) {
                var preGroups = tourConfig.groups || [];
                var preGroup  = null;
                for (var pi = 0; pi < preGroups.length; pi++) {
                    if (preGroups[pi].start === preDate.value) { preGroup = preGroups[pi]; break; }
                }
                if (preGroup) updateDepartureSummary(preGroup);
            }
        }

        // Booking page — seat map. Only runs when the seat map config
        // block is present (i.e. this tour has an assigned bus).
        var seatConfig = getSeatMapConfig();
        if (seatConfig) {
            initSeatMap(seatConfig);
        }

        // Single tour page — Accommodation widget.
        initAccommodationWidget();
    });

    // ============================================================
    // ACCOMMODATION WIDGET
    // ============================================================

    var placesCache = {};

    function initAccommodationWidget() {
        var configEl = document.getElementById("moga-accommodation-config");
        var widgetEl = document.getElementById("moga-accommodation-widget");
        if (!configEl || !widgetEl) return;

        var config;
        try { config = JSON.parse(configEl.textContent); } catch(e) { return; }
        if (!config || !config.groups) return;

        // Load default (first) group on page load.
        fetchAndRenderAccomGroup(config.defaultDate, config);

        // Update when guest selects a departure.
        var selectEl = document.getElementById("moga-tour-date-select");
        if (selectEl) {
            selectEl.addEventListener("change", function() {
                var date = selectEl.value;
                if (date && config.groups[date]) {
                    fetchAndRenderAccomGroup(date, config);
                }
            });
        }

        var dateInput = document.getElementById("moga-tour-date");
        if (dateInput) {
            dateInput.addEventListener("change", function() {
                var date = dateInput.value;
                if (date && config.groups[date]) {
                    fetchAndRenderAccomGroup(date, config);
                }
            });
        }

        // Wire scroll arrow buttons.
        // Purpose: let the guest navigate between hotel cards by clicking
        // the ‹ and › buttons that overlay the left/right edges of the widget.
        // Each click scrolls exactly one card width, with smooth animation.
        var prevBtn = document.getElementById("moga-accom-arrow-prev");
        var nextBtn = document.getElementById("moga-accom-arrow-next");
        var listEl  = document.getElementById("moga-accommodation-list");

        if (prevBtn && nextBtn && listEl) {

            function getCardWidth() {
                var card = listEl.querySelector(".moga-accommodation-card");
                // card width + 10px gap
                return card ? card.offsetWidth + 10 : 240;
            }

            function updateArrowState() {
                var atStart = listEl.scrollLeft <= 2;
                var atEnd   = listEl.scrollLeft + listEl.offsetWidth >= listEl.scrollWidth - 2;
                prevBtn.style.opacity      = atStart ? "0.3" : "1";
                prevBtn.style.pointerEvents = atStart ? "none" : "";
                nextBtn.style.opacity      = atEnd   ? "0.3" : "1";
                nextBtn.style.pointerEvents = atEnd   ? "none" : "";
            }

            prevBtn.addEventListener("click", function() {
                listEl.scrollBy({ left: -getCardWidth(), behavior: "smooth" });
            });

            nextBtn.addEventListener("click", function() {
                listEl.scrollBy({ left: getCardWidth(), behavior: "smooth" });
            });

            listEl.addEventListener("scroll", updateArrowState);

            // Set initial arrow state after cards are rendered.
            setTimeout(updateArrowState, 100);
        }
    }

    function fetchAndRenderAccomGroup(dateStr, config) {
        var stays = config.groups[dateStr];
        if (!stays || !stays.length) return;

        var listEl = document.getElementById("moga-accommodation-list");
        if (!listEl) return;

        listEl.innerHTML = "";

        // Filter valid stays first so we know the total for layout decisions.
        var validStays = stays.filter(function(s) { return !!s.hotel_name; });

        validStays.forEach(function(stay) {
            var card = buildHotelCard(stay);
            listEl.appendChild(card);
            fetchHotelPlaces(stay.hotel_name, config, function(data) {
                enrichHotelCard(card, data, stay);
            });
        });

        // CSS :has() handles single vs multiple card widths automatically.
        // JS fallback for browsers without :has() support:
        if (validStays.length > 1) {
            listEl.querySelectorAll(".moga-accommodation-card").forEach(function(c) {
                c.style.flex = "0 0 85%";
            });
        }
    }

    function buildHotelCard(stay) {
        var stars      = Math.max(1, Math.min(5, parseInt(stay.stars, 10) || 4));
        var nightFrom  = parseInt(stay.night_from, 10) || 1;
        var nightTo    = parseInt(stay.night_to,   10) || 1;
        var nightLabel = nightFrom === nightTo
            ? "Night " + nightFrom
            : "Nights " + nightFrom + "\u2013" + nightTo;

        var card = document.createElement("div");
        card.className = "moga-accommodation-card";

        card.innerHTML =
            '<div class="moga-accommodation-card__gallery swiper js-hotel-swiper">' +
                '<div class="swiper-wrapper">' +
                    '<div class="swiper-slide moga-accommodation-card__placeholder">' +
                        '<svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">' +
                            '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>' +
                            '<polyline points="9 22 9 12 15 12 15 22"/>' +
                        '</svg>' +
                    '</div>' +
                '</div>' +
                '<div class="swiper-pagination"></div>' +
                '<div class="swiper-button-prev"></div>' +
                '<div class="swiper-button-next"></div>' +
            '</div>' +
            '<div class="moga-accommodation-card__body">' +
                '<span class="moga-accommodation-card__nights">' + escHtmlStr(nightLabel) + '</span>' +
                '<h4 class="moga-accommodation-card__name">' + escHtmlStr(stay.hotel_name) + '</h4>' +
                '<div class="moga-accommodation-card__meta">' +
                    '<span class="moga-accommodation-card__stars">' +
                        '\u2605'.repeat(stars) + '\u2606'.repeat(5 - stars) +
                    '</span>' +
                    '<span class="moga-accommodation-card__rating js-hotel-rating"></span>' +
                '</div>' +
            '</div>';

        return card;
    }

    function enrichHotelCard(card, data, stay) {
        // ---- Determine photos to show ----
        // Strategy: merge Google Places photos + organizer-added photos
        // (uploaded via Media Library OR added by URL) into one slideshow.
        // Google photos come first; organizer photos are appended after.
        // If Google has no match, organizer photos fill the slideshow alone.
        // If neither source has photos, the placeholder icon remains.
        var googlePhotos    = (data && data.found && data.photos && data.photos.length) ? data.photos : [];
        var organizerPhotos = (stay.photo_urls && stay.photo_urls.length) ? stay.photo_urls : [];
        var isGoogleMatch   = data && data.found;

        var photosToShow = googlePhotos.concat(organizerPhotos);

        // ---- Build Swiper slides ----
        var swiperWrapper = card.querySelector(".swiper-wrapper");
        if (swiperWrapper && photosToShow.length) {
            swiperWrapper.innerHTML = "";
            var galleryId = "hotel-" + escHtmlStr(stay.hotel_name).replace(/\s+/g, "-").toLowerCase();

            photosToShow.forEach(function(url, idx) {
                var slide = document.createElement("div");
                slide.className = "swiper-slide";

                var mediaType = detectMediaType(url); // 'image' | 'video' | 'youtube' | 'vimeo'

                if (mediaType === "image") {
                    // Standard image slide with GLightbox popup.
                    var a = document.createElement("a");
                    a.href      = url;
                    a.className = "moga-hotel-photo-link glightbox";
                    a.setAttribute("data-gallery",   galleryId);
                    a.setAttribute("data-type",      "image");
                    a.setAttribute("data-title",     stay.hotel_name || "");

                    var img = document.createElement("img");
                    img.src       = url;
                    img.alt       = stay.hotel_name || "";
                    img.className = "moga-accommodation-card__photo";
                    img.loading   = idx === 0 ? "eager" : "lazy";

                    a.appendChild(img);
                    slide.appendChild(a);

                } else if (mediaType === "video") {
                    // Direct video file — inline <video> with play overlay.
                    // Clicking opens GLightbox video popup.
                    var va = document.createElement("a");
                    va.href      = url;
                    va.className = "moga-hotel-photo-link moga-hotel-video-link glightbox";
                    va.setAttribute("data-gallery", galleryId);
                    va.setAttribute("data-type",    "video");
                    va.setAttribute("data-title",   stay.hotel_name || "");

                    var vWrap = document.createElement("div");
                    vWrap.className = "moga-accommodation-card__video-thumb";

                    var vid = document.createElement("video");
                    vid.src      = url;
                    vid.muted    = true;
                    vid.preload  = "metadata";
                    vid.className = "moga-accommodation-card__photo";

                    var playIcon = document.createElement("div");
                    playIcon.className = "moga-hotel-play-icon";
                    playIcon.innerHTML =
                        '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">' +
                            '<path d="M8 5v14l11-7z"/>' +
                        '</svg>';

                    vWrap.appendChild(vid);
                    vWrap.appendChild(playIcon);
                    va.appendChild(vWrap);
                    slide.appendChild(va);

                } else {
                    // YouTube or Vimeo — show thumbnail poster with play overlay.
                    // Clicking opens GLightbox iframe popup.
                    var embedUrl = getEmbedUrl(url, mediaType);
                    var thumbUrl = getVideoThumbnail(url, mediaType);

                    var ia = document.createElement("a");
                    ia.href      = url;
                    ia.className = "moga-hotel-photo-link moga-hotel-video-link glightbox";
                    ia.setAttribute("data-gallery", galleryId);
                    ia.setAttribute("data-type",    "video");
                    ia.setAttribute("data-title",   stay.hotel_name || "");

                    var iWrap = document.createElement("div");
                    iWrap.className = "moga-accommodation-card__video-thumb";

                    if (thumbUrl) {
                        var tImg = document.createElement("img");
                        tImg.src       = thumbUrl;
                        tImg.alt       = stay.hotel_name || "";
                        tImg.className = "moga-accommodation-card__photo";
                        tImg.loading   = "lazy";
                        iWrap.appendChild(tImg);
                    }

                    var iPlayIcon = document.createElement("div");
                    iPlayIcon.className = "moga-hotel-play-icon";
                    iPlayIcon.innerHTML =
                        '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">' +
                            '<path d="M8 5v14l11-7z"/>' +
                        '</svg>';

                    iWrap.appendChild(iPlayIcon);
                    ia.appendChild(iWrap);
                    slide.appendChild(ia);
                }

                swiperWrapper.appendChild(slide);
            });

            // Photo count badge on first slide.
            if (photosToShow.length > 1) {
                var badge = document.createElement("div");
                badge.className = "moga-hotel-photo-count";
                badge.innerHTML =
                    '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">' +
                        '<rect x="3" y="3" width="18" height="18" rx="2"/>' +
                        '<circle cx="8.5" cy="8.5" r="1.5"/>' +
                        '<polyline points="21 15 16 10 5 21"/>' +
                    '</svg> ' +
                    photosToShow.length;
                card.querySelector(".moga-accommodation-card__gallery").appendChild(badge);
            }

            // Initialise Swiper.
            if (typeof Swiper !== "undefined") {
                var swiperEl = card.querySelector(".js-hotel-swiper");
                if (swiperEl) {
                    new Swiper(swiperEl, {
                        loop: photosToShow.length > 1,
                        pagination: {
                            el:        swiperEl.querySelector(".swiper-pagination"),
                            clickable: true,
                        },
                        navigation: {
                            prevEl: swiperEl.querySelector(".swiper-button-prev"),
                            nextEl: swiperEl.querySelector(".swiper-button-next"),
                        },
                    });
                }
            }

            // Initialise GLightbox for this card's gallery.
            if (typeof GLightbox !== "undefined") {
                GLightbox({
                    selector: "[data-gallery='" + galleryId + "']",
                    loop:     true,
                    touchNavigation: true,
                });
            }
        }

        // ---- Hotel name: replace with Google-confirmed name + Maps link ----
        var nameEl = card.querySelector(".moga-accommodation-card__name");
        if (nameEl) {
            if (isGoogleMatch && data.maps_url) {
                // Replace plain name with a clickable link to Google Maps.
                var googleName = data.google_name || data.name || stay.hotel_name;
                nameEl.innerHTML =
                    '<a href="' + escHtmlStr(data.maps_url) + '" target="_blank" rel="noopener noreferrer" ' +
                    'class="moga-hotel-maps-link" title="View on Google Maps">' +
                    escHtmlStr(googleName) +
                    '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-left:4px;vertical-align:middle;" aria-hidden="true">' +
                        '<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>' +
                        '<polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/>' +
                    '</svg>' +
                    '</a>';
            }
        }

        // ---- Stars: hide organizer stars, show Google rating ----
        var starsEl  = card.querySelector(".moga-accommodation-card__stars");
        var ratingEl = card.querySelector(".js-hotel-rating");

        if (isGoogleMatch && data.rating) {
            // Hide organizer-entered stars — Google rating is authoritative.
            if (starsEl) starsEl.style.display = "none";

            // Show Google rating with star icon.
            if (ratingEl) {
                ratingEl.innerHTML =
                    '<svg width="12" height="12" viewBox="0 0 24 24" fill="#f59e0b" aria-hidden="true">' +
                        '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>' +
                    '</svg> <strong>' + parseFloat(data.rating).toFixed(1) + '</strong>' +
                    (data.user_ratings
                        ? ' <span class="moga-accommodation-card__rating-count">(' +
                          Number(data.user_ratings).toLocaleString() + ')</span>'
                        : '');
            }
        } else if (!isGoogleMatch) {
            // No Google match — keep organizer stars visible, clear rating.
            if (starsEl) starsEl.style.display = "";
            if (ratingEl) ratingEl.innerHTML = "";
        }
    }

    // ---- Media type helpers ----

    function detectMediaType(url) {
        var u = url.toLowerCase().split("?")[0];
        if (/\.(jpg|jpeg|png|gif|webp|avif|svg)$/.test(u))  return "image";
        if (/\.(mp4|webm|mov|avi|ogv|m4v)$/.test(u))         return "video";
        if (/youtube\.com\/watch|youtu\.be\//.test(url))      return "youtube";
        if (/vimeo\.com\/\d/.test(url))                       return "vimeo";
        // Fallback: treat as image (Google Places photo URLs have no extension).
        return "image";
    }

    function getEmbedUrl(url, type) {
        if (type === "youtube") {
            var m = url.match(/(?:v=|youtu\.be\/)([A-Za-z0-9_-]{11})/);
            return m ? "https://www.youtube.com/embed/" + m[1] + "?autoplay=1" : url;
        }
        if (type === "vimeo") {
            var vm = url.match(/vimeo\.com\/(\d+)/);
            return vm ? "https://player.vimeo.com/video/" + vm[1] + "?autoplay=1" : url;
        }
        return url;
    }

    function getVideoThumbnail(url, type) {
        if (type === "youtube") {
            var m = url.match(/(?:v=|youtu\.be\/)([A-Za-z0-9_-]{11})/);
            return m ? "https://img.youtube.com/vi/" + m[1] + "/hqdefault.jpg" : "";
        }
        // Vimeo thumbnails require an API call — skip for now, show play icon only.
        return "";
    }

    function fetchHotelPlaces(hotelName, config, callback) {
        if (!config.apiKey) {
            callback({ found: false });
            return;
        }

        if (placesCache[hotelName] !== undefined) {
            callback(placesCache[hotelName]);
            return;
        }

        var formData = new FormData();
        formData.append("action",     "moga_get_hotel_places");
        formData.append("nonce",      config.nonce);
        formData.append("hotel_name", hotelName);

        fetch(config.ajaxUrl, { method: "POST", body: formData })
            .then(function(r) { return r.json(); })
            .then(function(json) {
                var data = (json.success && json.data) ? json.data : { found: false };
                placesCache[hotelName] = data;
                callback(data);
            })
            .catch(function() {
                placesCache[hotelName] = { found: false };
                callback({ found: false });
            });
    }

    function escHtmlStr(str) {
        return String(str)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;");
    }

})();
