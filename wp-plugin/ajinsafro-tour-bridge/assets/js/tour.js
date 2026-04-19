/**
 * Ajinsafro Tour Bridge - Single Tour V1 interactions
 * Scope: tabs, day chips, floating CTA scroll.
 */
(function () {
    "use strict";

    function initTabs() {
        var tabButtons = Array.prototype.slice.call(
            document.querySelectorAll(".ajtb-v1-tab-btn"),
        );
        var panels = Array.prototype.slice.call(
            document.querySelectorAll(".ajtb-v1-tab-panel"),
        );

        if (!tabButtons.length || !panels.length) {
            return;
        }

        function activateTab(tabName) {
            tabButtons.forEach(function (button) {
                var isActive = button.getAttribute("data-ajtb-tab") === tabName;
                button.classList.toggle("is-active", isActive);
                button.setAttribute("aria-selected", isActive ? "true" : "false");
            });

            panels.forEach(function (panel) {
                var shouldShow = panel.id === "ajtb-v1-panel-" + tabName;
                panel.classList.toggle("is-active", shouldShow);
                if (shouldShow) {
                    panel.removeAttribute("hidden");
                } else {
                    panel.setAttribute("hidden", "");
                }
            });
        }

        tabButtons.forEach(function (button) {
            button.addEventListener("click", function () {
                var tabName = button.getAttribute("data-ajtb-tab");
                if (!tabName) {
                    return;
                }
                activateTab(tabName);
            });
        });
    }

    function initDayChips() {
        var chips = Array.prototype.slice.call(
            document.querySelectorAll(".ajtb-v1-day-chip"),
        );

        if (!chips.length) {
            return;
        }

        function activateChipByTarget(targetId) {
            chips.forEach(function (chip) {
                var isActive = chip.getAttribute("data-ajtb-day-target") === targetId;
                chip.classList.toggle("is-active", isActive);
            });
        }

        chips.forEach(function (chip) {
            chip.addEventListener("click", function () {
                var targetId = chip.getAttribute("data-ajtb-day-target");
                if (!targetId) {
                    return;
                }

                var target = document.getElementById(targetId);
                if (!target) {
                    return;
                }

                activateChipByTarget(targetId);
                var root = document.getElementById("ajtb-v1-page");
                var offset = 172;
                if (root) {
                    var parsed = parseInt(
                        getComputedStyle(root).getPropertyValue("--ajtb-v1-day-scroll-offset"),
                        10,
                    );
                    if (!isNaN(parsed) && parsed > 0) {
                        offset = parsed;
                    }
                }
                var y =
                    window.pageYOffset +
                    target.getBoundingClientRect().top -
                    offset;
                window.scrollTo({ top: Math.max(0, y), behavior: "smooth" });
            });
        });

        var observer = new IntersectionObserver(
            function (entries) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting) {
                        return;
                    }
                    activateChipByTarget(entry.target.id);
                });
            },
            {
                root: null,
                rootMargin: "-35% 0px -45% 0px",
                threshold: 0.05,
            },
        );

        chips.forEach(function (chip) {
            var targetId = chip.getAttribute("data-ajtb-day-target");
            var target = targetId ? document.getElementById(targetId) : null;
            if (target) {
                observer.observe(target);
            }
        });
    }

    function initFloatingButton() {
        var button = document.querySelector("[data-ajtb-action='scroll-price']");
        if (!button) {
            return;
        }

        button.addEventListener("click", function () {
            var target = document.getElementById("ajtb-v1-summary-card");
            if (!target) {
                return;
            }

            target.scrollIntoView({ behavior: "smooth", block: "start" });
        });
    }

    function initGuestsPicker() {
        var picker = document.querySelector(".ajtb-v1-guests-picker");
        if (!picker) {
            return;
        }

        var trigger = document.getElementById("ajtb-v1-guest-trigger");
        var popover = document.getElementById("ajtb-v1-guest-popover");
        var applyBtn = document.getElementById("ajtb-v1-guest-apply");
        var summary = document.getElementById("ajtb-v1-guest-summary");
        var adultsValue = document.getElementById("ajtb-v1-guest-adults-value");
        var childrenValue = document.getElementById("ajtb-v1-guest-children-value");
        var adultsInput = document.getElementById("ajtb-v1-guest-adults-input");
        var childrenInput = document.getElementById("ajtb-v1-guest-children-input");

        if (
            !trigger ||
            !popover ||
            !summary ||
            !adultsValue ||
            !childrenValue ||
            !adultsInput ||
            !childrenInput
        ) {
            return;
        }

        var maxAdults = parseInt(picker.getAttribute("data-max-adults"), 10);
        var maxChildren = parseInt(picker.getAttribute("data-max-children"), 10);
        var maxTotal = parseInt(picker.getAttribute("data-max-total"), 10);

        if (isNaN(maxAdults) || maxAdults < 1) {
            maxAdults = 20;
        }
        if (isNaN(maxChildren) || maxChildren < 0) {
            maxChildren = 8;
        }
        if (isNaN(maxTotal) || maxTotal < 1) {
            maxTotal = maxAdults + maxChildren;
        }

        var state = {
            adults: Math.max(1, parseInt(adultsInput.value || "2", 10) || 2),
            children: Math.max(0, parseInt(childrenInput.value || "0", 10) || 0),
        };

        function formatSummary() {
            var txt =
                state.adults +
                " " +
                (state.adults > 1 ? "adultes" : "adulte");
            if (state.children > 0) {
                txt +=
                    ", " +
                    state.children +
                    " " +
                    (state.children > 1 ? "enfants" : "enfant");
            }
            return txt;
        }

        function render() {
            adultsValue.textContent = String(state.adults);
            childrenValue.textContent = String(state.children);
            adultsInput.value = String(state.adults);
            childrenInput.value = String(state.children);
            summary.textContent = formatSummary();
            document.dispatchEvent(
                new CustomEvent("ajtb:v1:travellers-changed", {
                    detail: {
                        adults: state.adults,
                        children: state.children,
                    },
                }),
            );
        }

        function clampTotals() {
            if (state.adults > maxAdults) {
                state.adults = maxAdults;
            }
            if (state.children > maxChildren) {
                state.children = maxChildren;
            }
            if (state.adults < 1) {
                state.adults = 1;
            }
            if (state.children < 0) {
                state.children = 0;
            }

            while (state.adults + state.children > maxTotal) {
                if (state.children > 0) {
                    state.children -= 1;
                } else if (state.adults > 1) {
                    state.adults -= 1;
                } else {
                    break;
                }
            }
        }

        function setOpen(open) {
            if (open) {
                popover.removeAttribute("hidden");
                trigger.setAttribute("aria-expanded", "true");
                picker.classList.add("is-open");
            } else {
                popover.setAttribute("hidden", "");
                trigger.setAttribute("aria-expanded", "false");
                picker.classList.remove("is-open");
            }
        }

        picker.addEventListener("click", function (event) {
            var control = event.target.closest("[data-ajtb-guest-action]");
            if (!control) {
                return;
            }
            event.preventDefault();

            var action = control.getAttribute("data-ajtb-guest-action");
            var target = control.getAttribute("data-ajtb-guest-target");

            if (target === "adults") {
                if (action === "plus") {
                    state.adults += 1;
                } else if (action === "minus") {
                    state.adults -= 1;
                }
            } else if (target === "children") {
                if (action === "plus") {
                    state.children += 1;
                } else if (action === "minus") {
                    state.children -= 1;
                }
            }

            clampTotals();
            render();
        });

        trigger.addEventListener("click", function () {
            var isOpen = !popover.hasAttribute("hidden");
            setOpen(!isOpen);
        });

        if (applyBtn) {
            applyBtn.addEventListener("click", function () {
                setOpen(false);
            });
        }

        document.addEventListener("click", function (event) {
            if (!picker.contains(event.target)) {
                setOpen(false);
            }
        });

        clampTotals();
        render();
    }

    function initDynamicStartingPrice() {
        var priceCard = document.getElementById("ajtb-v1-summary-card");
        var amountEl = document.getElementById("ajtb-v1-price-amount");
        if (!priceCard || !amountEl) {
            return;
        }

        var currencyEl = document.getElementById("ajtb-v1-price-currency");
        var suffixEl = document.getElementById("ajtb-v1-price-suffix");
        var perPersonEl = document.getElementById("ajtb-v1-price-per-person");
        var departureEl = document.getElementById("ajtb-v1-summary-departure");
        var dateEl = document.getElementById("ajtb-v1-summary-date");
        var guestsEl = document.getElementById("ajtb-v1-summary-guests");
        var durationEl = document.getElementById("ajtb-v1-summary-duration");
        var hotelEl = document.getElementById("ajtb-v1-summary-hotel");
        var activitiesEl = document.getElementById("ajtb-v1-summary-activities");
        var flightEl = document.getElementById("ajtb-v1-summary-flight");
        var availabilityEl = document.getElementById("ajtb-v1-summary-availability");
        var optionsEl = document.getElementById("ajtb-v1-summary-options");
        var noteEl = document.getElementById("ajtb-v1-summary-note");
        var actionEl = document.getElementById("ajtb-v1-summary-action");
        var availabilityBadgeEl = document.getElementById("ajtb-v1-availability-badge");
        var adultsInput = document.getElementById("ajtb-v1-guest-adults-input");
        var childrenInput = document.getElementById("ajtb-v1-guest-children-input");
        var dateSelect = document.getElementById("ajtb-v1-search-date");
        var fromSelect = document.getElementById("ajtb-v1-search-from");
        var dateDisplayMap = {};
        var searchBar = document.getElementById("ajtb-v1-search-box");

        var baseAdultPrice = parseFloat(
            priceCard.getAttribute("data-base-adult-price") || "0",
        );
        var baseChildPrice = parseFloat(
            priceCard.getAttribute("data-base-child-price") || "0",
        );
        var currency = priceCard.getAttribute("data-currency") || "MAD";
        var datePricesRaw = priceCard.getAttribute("data-date-prices") || "{}";
        var datePrices = {};

        if (!isFinite(baseAdultPrice) || baseAdultPrice < 0) {
            baseAdultPrice = 0;
        }
        if (!isFinite(baseChildPrice) || baseChildPrice < 0) {
            baseChildPrice = 0;
        }

        try {
            datePrices = JSON.parse(datePricesRaw);
        } catch (err) {
            datePrices = {};
        }

        if (dateSelect) {
            Array.prototype.slice.call(dateSelect.options || []).forEach(function (option) {
                dateDisplayMap[option.value] = option.textContent || option.value;
            });
        }

        function getSelectedDepartureLabel() {
            if (!fromSelect) {
                return priceCard.getAttribute("data-default-departure") || "—";
            }
            var selectedOption = fromSelect.options[fromSelect.selectedIndex];
            if (!selectedOption) {
                return priceCard.getAttribute("data-default-departure") || "—";
            }
            var label = (selectedOption.getAttribute("data-place-name") || selectedOption.textContent || "").trim();
            return label || priceCard.getAttribute("data-default-departure") || "—";
        }

        function getSelectedDateLabel() {
            if (!dateSelect) {
                return priceCard.getAttribute("data-default-date") || "—";
            }
            var selectedOption = dateSelect.options[dateSelect.selectedIndex];
            var selectedLabel = selectedOption ? (selectedOption.textContent || "") : "";
            return dateDisplayMap[dateSelect.value] || selectedLabel || priceCard.getAttribute("data-default-date") || "—";
        }

        function getGuestsLabel(adults, children) {
            var text = adults + " " + (adults > 1 ? "adultes" : "adulte");
            if (children > 0) {
                text += ", " + children + " " + (children > 1 ? "enfants" : "enfant");
            }
            return text;
        }

        function getAvailabilityLabel(total, dateAdultPrice) {
            var baseLabel = priceCard.getAttribute("data-availability-label") || "Sous réserve de disponibilité";
            if (dateAdultPrice !== null && total > 0) {
                return "Disponible";
            }
            return baseLabel;
        }

        function renderOptions() {
            if (!optionsEl) {
                return;
            }
            var rawOptions = [];
            try {
                rawOptions = JSON.parse(priceCard.getAttribute("data-options") || "[]");
            } catch (err) {
                rawOptions = [];
            }
            var html = "";
            if (rawOptions && rawOptions.length) {
                rawOptions.forEach(function (item) {
                    html += "<li>" + escapeHtml(String(item)) + "</li>";
                });
            } else {
                html = "<li>Aucune option supplémentaire renseignée</li>";
            }
            optionsEl.innerHTML = html;
        }

        function getTravellerValue(input, fallback) {
            if (!input) {
                return fallback;
            }
            var parsed = parseInt(input.value || String(fallback), 10);
            if (!isFinite(parsed)) {
                return fallback;
            }
            return parsed;
        }

        function getDateSpecificAdultPrice() {
            if (!dateSelect) {
                return null;
            }
            var selectedDate = dateSelect.value || "";
            if (!selectedDate || !datePrices || !datePrices[selectedDate]) {
                return null;
            }

            var info = datePrices[selectedDate];
            var selectedPlaceId = fromSelect ? parseInt(fromSelect.value || "0", 10) : 0;
            var datePlaceId =
                info && info.departure_place_id !== null && info.departure_place_id !== undefined
                    ? parseInt(info.departure_place_id, 10)
                    : 0;

            if (
                isFinite(datePlaceId) &&
                datePlaceId > 0 &&
                isFinite(selectedPlaceId) &&
                selectedPlaceId > 0 &&
                datePlaceId !== selectedPlaceId
            ) {
                return null;
            }

            if (
                info &&
                info.specific_price !== null &&
                info.specific_price !== undefined
            ) {
                var parsed = parseFloat(info.specific_price);
                if (isFinite(parsed) && parsed > 0) {
                    return parsed;
                }
            }
            return null;
        }

        function formatAmount(value) {
            return Math.round(value)
                .toString()
                .replace(/\B(?=(\d{3})+(?!\d))/g, " ");
        }

        function recalculate() {
            var adults = Math.max(1, getTravellerValue(adultsInput, 2));
            var children = Math.max(0, getTravellerValue(childrenInput, 0));

            var adultUnit = baseAdultPrice;
            var dateAdult = getDateSpecificAdultPrice();
            if (dateAdult !== null) {
                adultUnit = dateAdult;
            }
            if (!isFinite(adultUnit) || adultUnit < 0) {
                adultUnit = 0;
            }

            var childUnit = baseChildPrice > 0 ? baseChildPrice : adultUnit;
            var total = adults * adultUnit + children * childUnit;
            if (total <= 0) {
                total = adultUnit;
            }

            var travellerCount = adults + children;
            var pricePerPerson = travellerCount > 0 ? total / travellerCount : total;
            var departureLabel = getSelectedDepartureLabel();
            var dateLabel = getSelectedDateLabel();
            var guestsLabel = getGuestsLabel(adults, children);
            var availabilityLabel = getAvailabilityLabel(total, dateAdult);

            amountEl.textContent = formatAmount(total);
            if (currencyEl) {
                currencyEl.textContent = currency;
            }
            if (suffixEl) {
                suffixEl.textContent = "/ total";
            }
            if (perPersonEl) {
                perPersonEl.textContent = formatAmount(pricePerPerson) + " " + currency + " / pers.";
            }
            if (departureEl) {
                departureEl.textContent = departureLabel;
            }
            if (dateEl) {
                dateEl.textContent = dateLabel;
            }
            if (guestsEl) {
                guestsEl.textContent = guestsLabel;
            }
            if (durationEl) {
                durationEl.textContent = priceCard.getAttribute("data-duration-label") || durationEl.textContent;
            }
            if (hotelEl) {
                hotelEl.textContent = priceCard.getAttribute("data-hotel-label") || hotelEl.textContent;
            }
            if (activitiesEl) {
                activitiesEl.textContent = priceCard.getAttribute("data-activity-label") || activitiesEl.textContent;
            }
            if (flightEl) {
                flightEl.textContent = priceCard.getAttribute("data-has-flight") === "1" ? "Inclus" : "Non indiqué";
            }
            if (availabilityEl) {
                availabilityEl.textContent = availabilityLabel;
            }
            if (availabilityBadgeEl) {
                availabilityBadgeEl.textContent = availabilityLabel === "Disponible" ? "Disponible" : "À confirmer";
            }
            if (noteEl) {
                noteEl.textContent = priceCard.getAttribute("data-default-date") ? "Prix adapté selon la sélection" : noteEl.textContent;
            }
            if (actionEl) {
                actionEl.textContent = "Continuer";
            }
            renderOptions();
        }

        if (dateSelect) {
            dateSelect.addEventListener("change", function () {
                recalculate();
                document.dispatchEvent(new CustomEvent("ajtb:v1:date-changed"));
            });
        }

        if (fromSelect) {
            fromSelect.addEventListener("change", recalculate);
        }

        document.addEventListener("ajtb:v1:travellers-changed", recalculate);
        document.addEventListener("ajtb:v1:date-changed", recalculate);
        recalculate();
    }

    function initStickySearchBox() {
        var searchBox = document.getElementById("ajtb-v1-search-box");
        var pageRoot = document.getElementById("ajtb-v1-page");
        var headerRoot = document.getElementById("ajtb-v1-site-header");
        if (!searchBox || !pageRoot || !headerRoot) {
            return;
        }

        function getAdminOffset() {
            var adminBar = document.getElementById("wpadminbar");
            if (!adminBar) {
                return 0;
            }
            return Math.ceil(adminBar.getBoundingClientRect().height || 0);
        }

        function isVisible(el) {
            if (!el) {
                return false;
            }
            var rect = el.getBoundingClientRect();
            if (rect.height <= 0 || rect.width <= 0) {
                return false;
            }
            var style = window.getComputedStyle(el);
            return style.display !== "none" && style.visibility !== "hidden";
        }

        function getHeaderHeight() {
            var measured = Math.ceil(headerRoot.getBoundingClientRect().height || 0);
            if (measured >= 56) {
                return measured;
            }

            var candidates = [];
            var selectors = [
                "#aj-navbar",
                ".aj-navbar",
                ".topbar",
                ".aj-topbar",
                ".ajtb-v1-fallback-header",
            ];
            selectors.forEach(function (selector) {
                var found = headerRoot.querySelector(selector);
                if (found && candidates.indexOf(found) === -1) {
                    candidates.push(found);
                }
            });

            if (!candidates.length && headerRoot.children.length) {
                candidates = Array.prototype.slice.call(headerRoot.children);
            }

            var total = 0;
            candidates.forEach(function (el) {
                if (isVisible(el)) {
                    total += Math.ceil(el.getBoundingClientRect().height || 0);
                }
            });

            return Math.max(measured, total, 56);
        }

        function isMobileSearchLayout() {
            return window.matchMedia("(max-width: 767px)").matches;
        }

        function computeOffset() {
            var adminOffset = getAdminOffset();
            var headerHeight = getHeaderHeight();
            var searchHeight = Math.ceil(searchBox.getBoundingClientRect().height || 0);

            var stickyTop = adminOffset + headerHeight + 10;
            var railTop = stickyTop + searchHeight + 14;
            var dayOffset = railTop + 16;

            if (isMobileSearchLayout()) {
                stickyTop = adminOffset + headerHeight + 8;
                railTop = stickyTop + 10;
                dayOffset = adminOffset + headerHeight + 20;
            }

            pageRoot.style.setProperty("--ajtb-v1-admin-top", adminOffset + "px");
            pageRoot.style.setProperty("--ajtb-v1-sticky-top", stickyTop + "px");
            pageRoot.style.setProperty("--ajtb-v1-rail-top", railTop + "px");
            pageRoot.style.setProperty("--ajtb-v1-day-scroll-offset", dayOffset + "px");
        }

        function updateStuckState() {
            var rect = searchBox.getBoundingClientRect();
            var adminTop = parseInt(
                getComputedStyle(pageRoot).getPropertyValue("--ajtb-v1-admin-top"),
                10,
            );
            var stickyTop = parseInt(
                getComputedStyle(pageRoot).getPropertyValue("--ajtb-v1-sticky-top"),
                10,
            );
            var isHeaderStuck = window.pageYOffset > 2;
            if (isNaN(adminTop)) {
                adminTop = 0;
            }
            if (isNaN(stickyTop)) {
                stickyTop = 88;
            }
            var mobileLayout = isMobileSearchLayout();
            var searchIsStuck = !mobileLayout && rect.top <= stickyTop + 1;
            searchBox.classList.toggle("is-stuck", searchIsStuck);
            headerRoot.classList.toggle("is-stuck", isHeaderStuck);
            document.body.classList.toggle("ajtb-v1-header-is-sticky", isHeaderStuck);
            headerRoot.style.top = adminTop + "px";
        }

        computeOffset();
        updateStuckState();

        window.addEventListener("resize", computeOffset, { passive: true });
        window.addEventListener("resize", updateStuckState, { passive: true });
        window.addEventListener("scroll", updateStuckState, { passive: true });
        window.addEventListener("load", computeOffset);
        window.addEventListener("load", updateStuckState);
    }

    document.addEventListener("DOMContentLoaded", function () {
        initTabs();
        initDayChips();
        initFloatingButton();
        initGuestsPicker();
        initDynamicStartingPrice();
        initStickySearchBox();
    });
})();
