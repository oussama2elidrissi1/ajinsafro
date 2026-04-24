/**
 * Ajinsafro Tour Bridge - Single Tour V1 interactions
 * Scope: tabs, day chips, floating CTA scroll.
 */
(function () {
    "use strict";

    function escapeHtml(str) {
        if (!str) { return ""; }
        return String(str)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;");
    }

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

    function initProgramFilters() {
        var filterButtons = Array.prototype.slice.call(
            document.querySelectorAll("[data-program-filter]"),
        );
        var dayCards = Array.prototype.slice.call(
            document.querySelectorAll("[data-program-day-card]"),
        );

        if (!filterButtons.length || !dayCards.length) {
            return;
        }

        function setActiveFilter(filterName) {
            filterButtons.forEach(function (button) {
                var isActive = button.getAttribute("data-program-filter") === filterName;
                button.classList.toggle("is-active", isActive);
                button.setAttribute("aria-pressed", isActive ? "true" : "false");
            });
        }

        function applyFilter(filterName) {
            var activeFilter = filterName || "all";
            dayCards.forEach(function (dayCard) {
                var filterableNodes = Array.prototype.slice.call(
                    dayCard.querySelectorAll("[data-program-type]"),
                );
                var visibleMatches = 0;

                filterableNodes.forEach(function (node) {
                    var types = String(node.getAttribute("data-program-type") || "")
                        .split(/\s+/)
                        .filter(Boolean);
                    var shouldShow = activeFilter === "all" || types.indexOf(activeFilter) !== -1;
                    node.hidden = !shouldShow;
                    if (shouldShow) {
                        visibleMatches += 1;
                    }
                });

                dayCard.hidden = activeFilter === "all" ? false : visibleMatches === 0;
                dayCard.classList.toggle("is-filtered", activeFilter !== "all");
            });

            setActiveFilter(activeFilter);
        }

        filterButtons.forEach(function (button) {
            button.addEventListener("click", function () {
                var filterName = button.getAttribute("data-program-filter") || "all";
                applyFilter(filterName);
            });
        });

        applyFilter("all");
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

        // Allow other components (recap finalize) to update the travellers widget
        // by changing hidden inputs + dispatching ajtb:v1:travellers-changed.
        function syncFromInputs() {
            var a = Math.max(1, parseInt(adultsInput.value || "1", 10) || 1);
            var c = Math.max(0, parseInt(childrenInput.value || "0", 10) || 0);
            if (a === state.adults && c === state.children) {
                return;
            }
            state.adults = a;
            state.children = c;
            clampTotals();
            adultsValue.textContent = String(state.adults);
            childrenValue.textContent = String(state.children);
            summary.textContent = formatSummary();
        }

        document.addEventListener("ajtb:v1:travellers-changed", syncFromInputs);

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
        var selectedActivities = [];

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

        function renderOptions(activityTotal) {
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
            }
            if (selectedActivities.length) {
                selectedActivities.forEach(function (activity) {
                    var label = activity.title || "Activite";
                    var price = isFinite(activity.price) && activity.price > 0
                        ? " +" + formatAmount(activity.price) + " " + currency
                        : "";
                    html += "<li>" + escapeHtml(label + price) + "</li>";
                });
                if (activityTotal > 0) {
                    html += '<li class="ajtb-v1-summary-chip-total">Activites ajoutees: +' + escapeHtml(formatAmount(activityTotal) + " " + currency) + "</li>";
                }
            }
            if (!html) {
                html = "<li>Aucune option supplementaire renseignee</li>";
            }
            optionsEl.innerHTML = html;
        }

        function normalizeSelectedActivity(activity) {
            activity = activity || {};
            var price = parseFloat(activity.price);
            if (!isFinite(price) || price < 0) {
                price = 0;
            }

            return {
                activity_id: parseInt(String(activity.activity_id || activity.id || "0"), 10) || 0,
                title: String(activity.title || "Activite"),
                price: price,
            };
        }

        function collectClientActivitiesFromDom() {
            selectedActivities = Array.prototype.slice.call(document.querySelectorAll(".activity-card[data-client-added='1']"))
                .map(function (row) {
                    var titleEl = row.querySelector("h4");
                    return normalizeSelectedActivity({
                        activity_id: row.getAttribute("data-activity-id") || "0",
                        title: row.getAttribute("data-activity-title") || (titleEl ? titleEl.textContent : "Activite"),
                        price: row.getAttribute("data-activity-price") || "0",
                    });
                })
                .filter(function (activity) {
                    return activity.activity_id > 0;
                });
        }

        function selectedActivitiesTotal() {
            return selectedActivities.reduce(function (sum, activity) {
                return sum + (isFinite(activity.price) ? Math.max(0, activity.price) : 0);
            }, 0);
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
            collectClientActivitiesFromDom();
            var activityTotal = selectedActivitiesTotal();
            var total = adults * adultUnit + children * childUnit;
            if (total <= 0) {
                total = adultUnit;
            }
            total += activityTotal;

            var travellerCount = adults + children;
            var baseTravellerTotal = Math.max(0, total - activityTotal);
            var pricePerPerson = travellerCount > 0 ? baseTravellerTotal / travellerCount : baseTravellerTotal;
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
                var baseActivityLabel = priceCard.getAttribute("data-activity-label") || activitiesEl.textContent;
                activitiesEl.textContent = selectedActivities.length
                    ? baseActivityLabel + " + " + selectedActivities.length + " option" + (selectedActivities.length > 1 ? "s" : "") + " (+" + formatAmount(activityTotal) + " " + currency + ")"
                    : baseActivityLabel;
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
                noteEl.textContent = activityTotal > 0
                    ? "Prix total mis a jour avec les activites ajoutees."
                    : (priceCard.getAttribute("data-default-date") ? "Prix adapte selon la selection" : noteEl.textContent);
            }
            if (actionEl) {
                actionEl.textContent = "Continuer";
            }
            renderOptions(activityTotal);
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
        document.addEventListener("ajtb:v1:activities-changed", recalculate);
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

    function initOptionalActivitiesActions() {
        var page = document.getElementById("ajtb-v1-page");
        if (!page) {
            return;
        }

        var ajaxUrl = window.ajtbData && window.ajtbData.ajaxUrl ? window.ajtbData.ajaxUrl : "";
        var nonce = window.ajtbData && window.ajtbData.activityNonce ? window.ajtbData.activityNonce : "";
        var openActivities = window.ajtbOpenActivities || [];
        var overlay = document.getElementById("ajtb-act-modal-overlay");
        var modalBody = document.getElementById("ajtb-act-modal-body");
        var currentTourId = parseInt(String(window.ajtbTourId || "0"), 10);
        // Track added activity_ids per day: { dayId: Set<activityId> }
        var addedByDay = {};
        var currentModalActivitiesById = {};

        if (!overlay || !modalBody) {
            return;
        }

        function formatPrice(price) {
            if (price === null || price === undefined) {
                return "Prix sur demande";
            }
            return new Intl.NumberFormat("fr-MA").format(price) + " MAD";
        }

        function isAdded(dayId, activityId) {
            return !!(addedByDay[dayId] && addedByDay[dayId][activityId]);
        }

        function markAdded(dayId, activityId) {
            if (!addedByDay[dayId]) {
                addedByDay[dayId] = {};
            }
            addedByDay[dayId][activityId] = true;
        }

        function unmarkAdded(dayId, activityId) {
            if (addedByDay[dayId] && addedByDay[dayId][activityId]) {
                delete addedByDay[dayId][activityId];
            }
        }

        function seedAddedFromProgram() {
            Array.prototype.slice.call(page.querySelectorAll(".activity-card[data-client-added='1']")).forEach(function (card) {
                var list = card.closest("[data-day-activities-list]");
                var dayId = list ? parseInt(list.getAttribute("data-day-id") || "0", 10) : 0;
                var activityId = parseInt(card.getAttribute("data-activity-id") || "0", 10);
                if (dayId && activityId) {
                    markAdded(dayId, activityId);
                }
            });
        }

        function buildActivityCard(act, dayId, tourId, dayNumber) {
            var added = isAdded(dayId, act.activity_id);
            var img = act.image_url
                ? '<img src="' + escHtml(act.image_url) + '" alt="" loading="lazy">'
                : '<div class="ajtb-act-card-img-placeholder"></div>';
            var btnHtml = added
                ? '<button type="button" class="ajtb-act-card-btn is-done" disabled>Ajoutee</button>'
                : '<button type="button" class="ajtb-act-card-btn" data-ajtb-v1-action="add-activity" data-tour-id="' + tourId + '" data-day-id="' + dayId + '" data-day-number="' + dayNumber + '" data-activity-id="' + act.activity_id + '">Ajouter</button>';
            return '<article class="ajtb-act-card" data-activity-id="' + act.activity_id + '">' +
                '<div class="ajtb-act-card-media">' + img + '</div>' +
                '<div class="ajtb-act-card-body">' +
                '<span class="ajtb-act-card-badge">Option client</span>' +
                '<h4 class="ajtb-act-card-title">' + escHtml(act.title) + '</h4>' +
                '<div class="ajtb-act-card-actions">' +
                '<span class="ajtb-act-card-price">' + escHtml(formatPrice(act.price)) + '</span>' +
                btnHtml +
                '</div>' +
                '<p class="ajtb-act-card-desc">' + escHtml(act.description) + '</p>' +
                '<div class="ajtb-act-card-footer">' +
                '<div class="ajtb-act-progress" data-ajtb-add-progress hidden>' +
                '<div class="ajtb-act-progress-top"><span data-ajtb-progress-state>Preparation</span><strong data-ajtb-progress-percent>0%</strong></div>' +
                '<div class="ajtb-act-progress-track"><span data-ajtb-progress-fill style="width: 0%"></span></div>' +
                '</div>' +
                '</div></div></article>';
        }

        var escHtml = escapeHtml;

        function activityMatchesDay(activity, dayNumber) {
            if (!activity) { return false; }
            if (activity.visibility === "all_days") { return true; }
            return Number(activity.day_number) === Number(dayNumber);
        }

        function normalizeActivityForProgram(activity) {
            activity = activity || {};
            var price = activity.price;
            if ((price === null || price === undefined || price === "") && activity.custom_price !== undefined) {
                price = activity.custom_price;
            }
            if ((price === null || price === undefined || price === "") && activity.base_price !== undefined) {
                price = activity.base_price;
            }

            return {
                activity_id: parseInt(String(activity.activity_id || activity.id || "0"), 10) || 0,
                title: String(activity.title || "Activity"),
                description: String(activity.description || "Activite ajoutee au programme."),
                image_url: activity.image_url || "",
                price: price === null || price === undefined || price === "" ? null : Number(price),
            };
        }

        function buildProgramActivityCard(activity) {
            var act = normalizeActivityForProgram(activity);
            var img = act.image_url
                ? '<img src="' + escHtml(act.image_url) + '" alt="Activity visual" loading="lazy">'
                : '<div class="ajtb-act-card-img-placeholder"></div>';
            var price = act.price === null || Number.isNaN(act.price)
                ? ""
                : '<span>' + escHtml(formatPrice(act.price)) + '</span>';

            return '<div class="activity-card ajtb-v1-service-card" data-activity-id="' + act.activity_id + '" data-activity-title="' + escHtml(act.title) + '" data-activity-price="' + escHtml(act.price === null || Number.isNaN(act.price) ? "" : String(act.price)) + '" data-client-added="1">' +
                '<div class="ajtb-v1-service-head"><span>Activity - Program</span>' +
                '<button type="button" class="ajtb-v1-service-remove" data-ajtb-v1-action="remove-program-activity" data-day-id="" data-day-number="" data-activity-id="' + act.activity_id + '">Retirer</button></div>' +
                '<div class="ajtb-v1-service-body ajtb-v1-media-row">' +
                img +
                '<div>' +
                '<h4>' + escHtml(act.title) + '</h4>' +
                '<p>' + escHtml(act.description) + '</p>' +
                '<div class="ajtb-v1-meta-line">' + price + '</div>' +
                '</div></div>' +
                '<div class="ajtb-v1-service-progress">' +
                '<div class="ajtb-act-progress" data-ajtb-remove-progress hidden>' +
                '<div class="ajtb-act-progress-top"><span data-ajtb-progress-state>Preparation</span><strong data-ajtb-progress-percent>0%</strong></div>' +
                '<div class="ajtb-act-progress-track"><span data-ajtb-progress-fill style="width: 0%"></span></div>' +
                '</div>' +
                '</div></div>';
        }

        function addActivityToProgram(dayId, dayNumber, activity) {
            var act = normalizeActivityForProgram(activity);
            if (!act.activity_id) { return; }

            var list = document.querySelector('[data-day-activities-list][data-day-id="' + dayId + '"]');
            if (!list) {
                list = document.querySelector('[data-day-activities-list][data-day-number="' + dayNumber + '"]');
            }
            if (!list) { return; }
            if (list.querySelector('[data-activity-id="' + act.activity_id + '"]')) { return; }

            list.insertAdjacentHTML("beforeend", buildProgramActivityCard(act));
            var row = list.querySelector('[data-client-added="1"][data-activity-id="' + act.activity_id + '"]');
            var removeBtn = row ? row.querySelector('[data-ajtb-v1-action="remove-program-activity"]') : null;
            if (removeBtn) {
                removeBtn.setAttribute("data-tour-id", String(currentTourId || ""));
                removeBtn.setAttribute("data-day-id", String(dayId || ""));
                removeBtn.setAttribute("data-day-number", String(dayNumber || ""));
            }
            document.dispatchEvent(new CustomEvent("ajtb:v1:activities-changed"));
        }

        function openModal(dayId, tourId, dayNumber, dayFixedOpts) {
            if (!overlay || !modalBody) { return; }

            var seenIds = {};
            var cards = [];
            currentModalActivitiesById = {};
            var allActivities = (dayFixedOpts || []).concat(openActivities);
            allActivities.forEach(function (act) {
                if (!activityMatchesDay(act, dayNumber)) { return; }
                var aid = act.activity_id;
                if (aid && seenIds[aid]) { return; }
                if (aid) { seenIds[aid] = true; }
                currentModalActivitiesById[aid] = act;
                cards.push(buildActivityCard(act, dayId, tourId, dayNumber));
            });

            if (cards.length === 0) {
                modalBody.innerHTML = '<p class="ajtb-act-modal-empty">Aucune activite optionnelle disponible pour ce jour.</p>';
            } else {
                modalBody.innerHTML = '<div class="ajtb-act-modal-grid">' + cards.join("") + '</div>';
            }

            overlay.removeAttribute("hidden");
            document.body.classList.add("ajtb-modal-open");
            var closeBtn = overlay.querySelector("[data-ajtb-v1-action='close-activity-modal']");
            if (closeBtn) { closeBtn.focus(); }
        }

        function closeModal() {
            if (!overlay) { return; }
            overlay.setAttribute("hidden", "");
            document.body.classList.remove("ajtb-modal-open");
        }

        function startButtonProgress(button, options) {
            options = options || {};
            var cardSelector = options.cardSelector || ".ajtb-act-card";
            var progressSelector = options.progressSelector || "[data-ajtb-add-progress]";
            var busyClass = options.busyClass || "is-adding";
            var finalState = options.finalState || "Ajoute au programme";

            var card = button.closest(cardSelector);
            var progressEl = card ? card.querySelector(progressSelector) : null;
            var fillEl = progressEl ? progressEl.querySelector("[data-ajtb-progress-fill]") : null;
            var percentEl = progressEl ? progressEl.querySelector("[data-ajtb-progress-percent]") : null;
            var stateEl = progressEl ? progressEl.querySelector("[data-ajtb-progress-state]") : null;
            var value = 0;
            var startedAt = Date.now();
            var timer = null;

            function stateFor(nextValue) {
                if (nextValue >= 92) { return "Confirmation"; }
                if (nextValue >= 64) { return "Mise a jour du programme"; }
                if (nextValue >= 35) { return "Enregistrement"; }
                return "Preparation";
            }

            function apply(nextValue, label) {
                value = Math.max(0, Math.min(100, Math.round(nextValue)));
                if (fillEl) {
                    fillEl.style.width = value + "%";
                }
                if (percentEl) {
                    percentEl.textContent = value + "%";
                }
                if (stateEl) {
                    stateEl.textContent = label || stateFor(value);
                }
                if (value < 100) {
                    button.textContent = value + "%";
                }
            }

            if (progressEl) {
                progressEl.hidden = false;
                progressEl.classList.remove("is-error", "is-complete");
            }
            if (card) {
                card.classList.add(busyClass);
            }

            apply(8, "Preparation");
            timer = window.setInterval(function () {
                if (value >= 88) {
                    return;
                }
                apply(value + Math.max(4, Math.round((90 - value) / 5)));
            }, 90);

            return {
                complete: function (callback) {
                    var wait = Math.max(0, 420 - (Date.now() - startedAt));
                    window.setTimeout(function () {
                        if (timer) {
                            window.clearInterval(timer);
                        }
                        apply(100, finalState);
                        button.textContent = "100%";
                        if (progressEl) {
                            progressEl.classList.add("is-complete");
                        }
                        window.setTimeout(function () {
                            if (card) {
                                card.classList.remove(busyClass);
                            }
                            if (typeof callback === "function") {
                                callback();
                            }
                        }, 180);
                    }, wait);
                },
                fail: function () {
                    if (timer) {
                        window.clearInterval(timer);
                    }
                    apply(value > 0 ? value : 1, "Erreur, reessayez");
                    if (progressEl) {
                        progressEl.classList.add("is-error");
                    }
                    if (card) {
                        card.classList.remove(busyClass);
                    }
                },
            };
        }

        function startAddProgress(button) {
            return startButtonProgress(button, {
                cardSelector: ".ajtb-act-card",
                progressSelector: "[data-ajtb-add-progress]",
                busyClass: "is-adding",
                finalState: "Ajoute au programme",
            });
        }

        function startRemoveProgress(button) {
            return startButtonProgress(button, {
                cardSelector: ".activity-card",
                progressSelector: "[data-ajtb-remove-progress]",
                busyClass: "is-removing",
                finalState: "Retire du programme",
            });
        }

        function addActivity(button) {
            var tourId = parseInt(button.getAttribute("data-tour-id") || "0", 10);
            var dayId = parseInt(button.getAttribute("data-day-id") || "0", 10);
            var dayNumber = parseInt(button.getAttribute("data-day-number") || "0", 10);
            var activityId = parseInt(button.getAttribute("data-activity-id") || "0", 10);
            if (!tourId || !dayId || !activityId) { return; }

            var progress = startAddProgress(button);
            button.disabled = true;
            button.classList.add("is-loading");
            button.textContent = "0%";

            function finishAdded() {
                progress.complete(function () {
                    markAdded(dayId, activityId);
                    addActivityToProgram(dayId, dayNumber, currentModalActivitiesById[activityId] || { activity_id: activityId });
                    button.classList.remove("is-loading");
                    button.classList.add("is-done");
                    button.textContent = "Ajoutee";
                    button.disabled = true;
                });
            }

            if (!ajaxUrl || !nonce) {
                finishAdded();
                return;
            }

            var formData = new FormData();
            formData.append("action", "ajtb_v1_toggle_activity");
            formData.append("nonce", nonce);
            formData.append("tour_id", String(tourId));
            formData.append("day_id", String(dayId));
            formData.append("activity_id", String(activityId));
            formData.append("activity_action", "added");

            fetch(ajaxUrl, {
                method: "POST",
                credentials: "same-origin",
                body: formData,
            })
                .then(function (r) { return r.json(); })
                .then(function (json) {
                    if (!json || !json.success) {
                        throw new Error((json && json.data && json.data.message) || "Erreur");
                    }
                    finishAdded();
                })
                .catch(function () {
                    progress.fail();
                    button.disabled = false;
                    button.classList.remove("is-loading", "is-done");
                    button.textContent = "Ajouter";
                });
        }

        function removeActivityFromProgram(button) {
            var tourId = parseInt(button.getAttribute("data-tour-id") || "0", 10) || currentTourId;
            var dayId = parseInt(button.getAttribute("data-day-id") || "0", 10);
            var activityId = parseInt(button.getAttribute("data-activity-id") || "0", 10);
            if (!tourId || !dayId || !activityId) { return; }

            var card = button.closest(".activity-card");
            var progress = startRemoveProgress(button);
            button.disabled = true;
            button.classList.add("is-loading");
            button.textContent = "0%";

            function finishRemoved() {
                progress.complete(function () {
                    unmarkAdded(dayId, activityId);
                    if (card) {
                        card.remove();
                    }
                    document.dispatchEvent(new CustomEvent("ajtb:v1:activities-changed"));
                    if (modalBody) {
                        var modalBtn = modalBody.querySelector('[data-ajtb-v1-action="add-activity"][data-day-id="' + dayId + '"][data-activity-id="' + activityId + '"]');
                        if (modalBtn) {
                            modalBtn.disabled = false;
                            modalBtn.classList.remove("is-done", "is-loading");
                            modalBtn.textContent = "Ajouter";
                        }
                    }
                });
            }

            if (!ajaxUrl || !nonce) {
                finishRemoved();
                return;
            }

            var formData = new FormData();
            formData.append("action", "ajtb_v1_toggle_activity");
            formData.append("nonce", nonce);
            formData.append("tour_id", String(tourId));
            formData.append("day_id", String(dayId));
            formData.append("activity_id", String(activityId));
            formData.append("activity_action", "removed");

            fetch(ajaxUrl, {
                method: "POST",
                credentials: "same-origin",
                body: formData,
            })
                .then(function (r) { return r.json(); })
                .then(function (json) {
                    if (!json || !json.success) {
                        throw new Error((json && json.data && json.data.message) || "Erreur");
                    }
                    finishRemoved();
                })
                .catch(function () {
                    progress.fail();
                    button.disabled = false;
                    button.classList.remove("is-loading");
                    button.textContent = "Retirer";
                });
        }

        // Close on overlay background click
        if (overlay) {
            overlay.addEventListener("click", function (e) {
                if (e.target === overlay) { closeModal(); }
            });
        }

        // Close on Escape key
        document.addEventListener("keydown", function (e) {
            if (e.key === "Escape" && overlay && !overlay.hasAttribute("hidden")) {
                closeModal();
            }
        });

        page.addEventListener("click", function (event) {
            // Open modal CTA
            var openBtn = event.target.closest("[data-ajtb-v1-action='open-activity-modal']");
            if (openBtn) {
                event.preventDefault();
                var dayId = parseInt(openBtn.getAttribute("data-day-id") || "0", 10);
                var tourId = parseInt(openBtn.getAttribute("data-tour-id") || "0", 10);
                var dayNumber = parseInt(openBtn.getAttribute("data-day-number") || "0", 10);
                var rawOpts = openBtn.getAttribute("data-day-opts") || "[]";
                var dayFixedOpts = [];
                try { dayFixedOpts = JSON.parse(rawOpts); } catch (e) {}
                openModal(dayId, tourId, dayNumber, dayFixedOpts);
                return;
            }

            // Close button
            var closeBtn = event.target.closest("[data-ajtb-v1-action='close-activity-modal']");
            if (closeBtn) {
                event.preventDefault();
                closeModal();
                return;
            }

            var removeBtn = event.target.closest("[data-ajtb-v1-action='remove-program-activity']");
            if (removeBtn && !removeBtn.disabled) {
                event.preventDefault();
                removeActivityFromProgram(removeBtn);
                return;
            }

            // Add activity inside modal
            var addBtn = event.target.closest("[data-ajtb-v1-action='add-activity']");
            if (addBtn && !addBtn.disabled) {
                event.preventDefault();
                addActivity(addBtn);
                return;
            }
        });

        seedAddedFromProgram();
    }

    function safeJsonParse(raw, fallback) {
        try {
            return JSON.parse(raw);
        } catch (e) {
            return fallback;
        }
    }

    function formatMoney(amount) {
        var num = typeof amount === "number" ? amount : parseFloat(String(amount || "0"));
        if (!isFinite(num)) {
            num = 0;
        }
        return Math.round(num)
            .toString()
            .replace(/\B(?=(\d{3})+(?!\d))/g, " ");
    }

    function escapeHtml(value) {
        return String(value || "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function collectRecapPayloadFromSingle() {
        var priceCard = document.getElementById("ajtb-v1-summary-card");
        if (!priceCard) {
            return null;
        }
        var tourId = (window.ajtbData && window.ajtbData.tourId) ? parseInt(String(window.ajtbData.tourId), 10) : 0;
        if (!tourId) {
            tourId = parseInt(priceCard.getAttribute("data-tour-id") || "0", 10) || 0;
        }

        var adultsInput = document.getElementById("ajtb-v1-guest-adults-input");
        var childrenInput = document.getElementById("ajtb-v1-guest-children-input");
        var fromSelect = document.getElementById("ajtb-v1-search-from");
        var dateSelect = document.getElementById("ajtb-v1-search-date");

        var adults = adultsInput ? parseInt(adultsInput.value || "2", 10) : 2;
        var children = childrenInput ? parseInt(childrenInput.value || "0", 10) : 0;
        if (!isFinite(adults) || adults < 1) { adults = 1; }
        if (!isFinite(children) || children < 0) { children = 0; }

        var departureLabel = priceCard.getAttribute("data-default-departure") || "—";
        var departurePlaceId = 0;
        if (fromSelect && fromSelect.options && fromSelect.selectedIndex >= 0) {
            var opt = fromSelect.options[fromSelect.selectedIndex];
            departurePlaceId = parseInt(fromSelect.value || "0", 10) || 0;
            departureLabel = (opt.getAttribute("data-place-name") || opt.textContent || departureLabel).trim();
        }

        var dateValue = "";
        var dateLabel = priceCard.getAttribute("data-default-date") || "—";
        if (dateSelect && dateSelect.options && dateSelect.selectedIndex >= 0) {
            var dateOpt = dateSelect.options[dateSelect.selectedIndex];
            dateValue = String(dateSelect.value || "");
            dateLabel = String(dateOpt.textContent || dateValue || dateLabel).trim();
        }

        var currency = priceCard.getAttribute("data-currency") || "MAD";
        var totalTextEl = document.getElementById("ajtb-v1-price-amount");
        var totalText = totalTextEl ? String(totalTextEl.textContent || "").trim() : "";
        var total = parseFloat(totalText.replace(/\s+/g, "").replace(",", ".")) || 0;

        // Selected activities are the DOM-added client cards.
        var activities = Array.prototype.slice.call(document.querySelectorAll(".activity-card[data-client-added='1']"))
            .map(function (row) {
                return {
                    activity_id: parseInt(row.getAttribute("data-activity-id") || "0", 10) || 0,
                    title: String(row.getAttribute("data-activity-title") || "").trim() || (row.querySelector("h4") ? row.querySelector("h4").textContent.trim() : "Activité"),
                    price: row.getAttribute("data-activity-price") || "",
                    assigned: [],
                };
            })
            .filter(function (a) { return a.activity_id > 0; });

        // Options shown in summary chips (best deals + activities selections).
        var options = [];
        var optionsEl = document.getElementById("ajtb-v1-summary-options");
        if (optionsEl) {
            options = Array.prototype.slice.call(optionsEl.querySelectorAll("li")).map(function (li) {
                return String(li.textContent || "").trim();
            }).filter(Boolean);
        }

        var recapUrl = priceCard.getAttribute("data-recap-url") || "";

        return {
            version: 1,
            capturedAt: Date.now(),
            tourId: tourId,
            departure: {
                id: departurePlaceId,
                label: departureLabel || "—",
            },
            date: {
                value: dateValue,
                label: dateLabel || "—",
            },
            guests: {
                adults: adults,
                children: children,
                label: (document.getElementById("ajtb-v1-summary-guests") ? document.getElementById("ajtb-v1-summary-guests").textContent : "") || "",
            },
            hotel: {
                label: (document.getElementById("ajtb-v1-summary-hotel") ? document.getElementById("ajtb-v1-summary-hotel").textContent : "") || "",
            },
            flight: {
                label: (document.getElementById("ajtb-v1-summary-flight") ? document.getElementById("ajtb-v1-summary-flight").textContent : "") || "",
            },
            transfers: {
                label: "—",
            },
            activities: activities,
            options: options,
            price: {
                total: isFinite(total) ? total : 0,
                currency: currency,
            },
            recapUrl: recapUrl,
        };
    }

    function initContinueToRecap() {
        var actionEl = document.getElementById("ajtb-v1-summary-action");
        var priceCard = document.getElementById("ajtb-v1-summary-card");
        if (!actionEl || !priceCard) {
            return;
        }

        actionEl.addEventListener("click", function () {
            var payload = collectRecapPayloadFromSingle();
            if (!payload) {
                return;
            }
            try {
                localStorage.setItem("ajtb:v1:recap:" + String(payload.tourId || "0"), JSON.stringify(payload));
            } catch (e) {}

            var recapUrl = payload.recapUrl || priceCard.getAttribute("data-recap-url") || "";
            if (!recapUrl && window.ajtbData && window.ajtbData.recapUrl) {
                recapUrl = window.ajtbData.recapUrl;
            }
            if (!recapUrl) {
                // Fallback: stay on page if recap URL missing.
                return;
            }
            window.location.href = recapUrl;
        });
    }

    function initRestoreSelectionFromRecap() {
        var priceCard = document.getElementById("ajtb-v1-summary-card");
        if (!priceCard) {
            return;
        }
        // Only when user clicks "Modifier" from recap page.
        if (!window.location.search || window.location.search.indexOf("ajtb_edit=1") === -1) {
            return;
        }

        var tourId = (window.ajtbData && window.ajtbData.tourId) ? parseInt(String(window.ajtbData.tourId), 10) : 0;
        if (!tourId) {
            tourId = parseInt(priceCard.getAttribute("data-tour-id") || "0", 10) || 0;
        }
        if (!tourId) {
            return;
        }

        var payload = null;
        try {
            payload = safeJsonParse(localStorage.getItem("ajtb:v1:recap:" + String(tourId)), null);
        } catch (e) {
            payload = null;
        }
        if (!payload || payload.tourId !== tourId) {
            return;
        }

        var fromSelect = document.getElementById("ajtb-v1-search-from");
        var dateSelect = document.getElementById("ajtb-v1-search-date");
        var adultsInput = document.getElementById("ajtb-v1-guest-adults-input");
        var childrenInput = document.getElementById("ajtb-v1-guest-children-input");

        if (fromSelect && payload.departure && payload.departure.id) {
            fromSelect.value = String(payload.departure.id);
            fromSelect.dispatchEvent(new Event("change", { bubbles: true }));
        }
        if (dateSelect && payload.date && payload.date.value) {
            dateSelect.value = String(payload.date.value);
            dateSelect.dispatchEvent(new Event("change", { bubbles: true }));
        }
        if (adultsInput && payload.guests && isFinite(payload.guests.adults)) {
            adultsInput.value = String(Math.max(1, parseInt(payload.guests.adults, 10) || 1));
        }
        if (childrenInput && payload.guests && isFinite(payload.guests.children)) {
            childrenInput.value = String(Math.max(0, parseInt(payload.guests.children, 10) || 0));
        }
        document.dispatchEvent(new CustomEvent("ajtb:v1:travellers-changed"));

        // Scroll back to selection.
        var searchBox = document.getElementById("ajtb-v1-search-box");
        if (searchBox) {
            searchBox.scrollIntoView({ behavior: "smooth", block: "start" });
        }
    }

    function initRecapPage() {
        var root = document.querySelector("[data-ajtb-recap-root]");
        if (!root) {
            return;
        }
        var stepLinks = Array.prototype.slice.call(document.querySelectorAll(".ajtb-v1-recap-step"));
        var stepTargets = stepLinks.map(function (link) {
            var href = link.getAttribute("href") || "";
            if (!href || href.charAt(0) !== "#") {
                return null;
            }
            var target = document.querySelector(href);
            if (!target) {
                return null;
            }
            return { link: link, target: target };
        }).filter(Boolean);

        function setActiveStep(targetId) {
            stepLinks.forEach(function (link) {
                var href = link.getAttribute("href") || "";
                link.classList.toggle("is-active", href === ("#" + targetId));
            });
        }

        stepLinks.forEach(function (link) {
            link.addEventListener("click", function (event) {
                var href = link.getAttribute("href") || "";
                if (!href || href.charAt(0) !== "#") {
                    return;
                }
                var target = document.querySelector(href);
                if (!target) {
                    return;
                }
                event.preventDefault();
                setActiveStep(String(target.id || ""));
                target.scrollIntoView({ behavior: "smooth", block: "start" });
            });
        });

        if (stepTargets.length) {
            var onScroll = function () {
                var viewportOffset = 170;
                var current = stepTargets[0];
                stepTargets.forEach(function (entry) {
                    var top = entry.target.getBoundingClientRect().top;
                    if (top <= viewportOffset) {
                        current = entry;
                    }
                });
                if (current && current.target && current.target.id) {
                    setActiveStep(String(current.target.id));
                }
            };
            window.addEventListener("scroll", onScroll, { passive: true });
            window.addEventListener("resize", onScroll, { passive: true });
            onScroll();
        }

        var tourId = parseInt(root.getAttribute("data-tour-id") || "0", 10) || 0;
        var hint = document.querySelector("[data-ajtb-recap-hint]");
        var payload = null;
        try {
            payload = safeJsonParse(localStorage.getItem("ajtb:v1:recap:" + String(tourId)), null);
        } catch (e) {
            payload = null;
        }
        // Allow direct open: create a minimal payload from defaults.
        if (!payload || payload.tourId !== tourId) {
            if (hint) { hint.hidden = false; }
            payload = {
                version: 1,
                capturedAt: Date.now(),
                tourId: tourId,
                departure: { id: 0, label: "—" },
                date: { value: "", label: "—" },
                guests: { adults: 2, children: 0, label: "" },
                hotel: { label: "" },
                flight: { label: "" },
                transfers: { label: "—" },
                activities: [],
                options: [],
                price: { total: 0, currency: "MAD" },
            };
        }

        // Normalize activities: default pricing mode is "all" (per traveller)
        if (payload.activities && payload.activities.length) {
            payload.activities.forEach(function (a) {
                if (!a) return;
                if (!Array.isArray(a.assigned)) a.assigned = [];
            });
        }

        function setField(name, value) {
            var el = document.querySelector("[data-ajtb-recap-field='" + name + "']");
            if (!el) { return; }
            el.textContent = (value === null || value === undefined || String(value).trim() === "") ? "—" : String(value);
        }

        function setRowVisibility(name, visible) {
            var row = document.querySelector("[data-ajtb-recap-row='" + name + "']");
            if (!row) {
                return;
            }
            if (visible) {
                row.removeAttribute("hidden");
            } else {
                row.setAttribute("hidden", "");
            }
        }

        function normalizeRoomType(value) {
            var out = String(value || "").toLowerCase();
            out = out.replace(/[\u00e9\u00e8\u00ea]/g, "e");
            return out;
        }

        function detectSourceRoomType(roomType) {
            var norm = normalizeRoomType(roomType);
            if (norm.indexOf("double") !== -1) {
                return "double";
            }
            if (norm.indexOf("single") !== -1 || norm.indexOf("solo") !== -1) {
                return "single";
            }
            if (norm.indexOf("triple") !== -1) {
                return "triple";
            }
            return "other";
        }

        function isSharedDoubleMode(room) {
            return !!(room && String(room.room_mode || "") === "shared_double");
        }

        function isHalfDoubleRoom(room) {
            if (!room) {
                return false;
            }
            if (isSharedDoubleMode(room)) {
                return true;
            }
            var norm = normalizeRoomType(room.room_type || "");
            if (norm.indexOf("demi") !== -1 || norm.indexOf("half") !== -1) {
                return true;
            }
            return room.is_half_double === true || room.is_half_double === 1 || room.is_half_double === "1";
        }

        function expandRoomModes(rooms) {
            var out = [];
            (rooms || []).forEach(function (room) {
                if (!room) {
                    return;
                }
                var sourceRoomId = parseInt(room.id || "0", 10) || 0;
                if (sourceRoomId <= 0) {
                    return;
                }
                var sourceType = String(room.source_room_type || "") || detectSourceRoomType(room.room_type || "");

                out.push(Object.assign({}, room, {
                    alloc_key: "full:" + String(sourceRoomId),
                    source_room_id: sourceRoomId,
                    source_room_type: sourceType,
                    room_mode: "full_room",
                    room_label: String(room.room_type || "Chambre"),
                }));

                if (sourceType === "double") {
                    out.push(Object.assign({}, room, {
                        alloc_key: "shared:" + String(sourceRoomId),
                        source_room_id: sourceRoomId,
                        source_room_type: "double",
                        room_mode: "shared_double",
                        room_type: "Demi-double",
                        room_label: "Demi-double",
                        capacity_per_room: 1,
                        is_half_double: true,
                        shared_room_status: "pending",
                    }));
                }
            });
            return out;
        }

        function roomAssignmentUnit(room) {
            if (isHalfDoubleRoom(room)) {
                return 1;
            }
            var cap = parseInt(room && room.capacity_per_room ? room.capacity_per_room : "1", 10) || 1;
            return Math.max(1, cap);
        }

        function getRoomAllocationSummary(state) {
            state = state || payload;
            var rooms = Array.isArray(state.availableRoomsCurrent) ? state.availableRoomsCurrent : [];
            var alloc = state.roomAllocation && typeof state.roomAllocation === "object" ? state.roomAllocation : {};
            var adults = state.guests ? parseInt(state.guests.adults || "1", 10) : 1;
            var children = state.guests ? parseInt(state.guests.children || "0", 10) : 0;
            if (!isFinite(adults) || adults < 1) { adults = 1; }
            if (!isFinite(children) || children < 0) { children = 0; }
            var need = adults + children;

            var assigned = 0;
            var hasHalfDouble = false;
            var halfDoubleSeats = 0;
            var lines = [];

            rooms.forEach(function (r) {
                if (!r) {
                    return;
                }
                var id = String(r.alloc_key || r.id || "");
                if (!id) {
                    return;
                }
                var qty = parseInt(alloc[id] || "0", 10) || 0;
                qty = Math.max(0, qty);
                if (qty <= 0) {
                    return;
                }
                var assignUnit = roomAssignmentUnit(r);
                var assignedPax = qty * assignUnit;
                var half = isHalfDoubleRoom(r);
                if (half) {
                    hasHalfDouble = true;
                    halfDoubleSeats += assignedPax;
                }
                assigned += assignedPax;
                lines.push({
                    id: id,
                    sourceRoomId: parseInt(r.source_room_id || r.id || "0", 10) || 0,
                    sourceRoomType: String(r.source_room_type || detectSourceRoomType(r.room_type || "")),
                    roomMode: String(r.room_mode || "full_room"),
                    room: r,
                    qty: qty,
                    assigned: assignedPax,
                    assignUnit: assignUnit,
                    isHalfDouble: half,
                });
            });

            return {
                need: need,
                assigned: assigned,
                remaining: Math.max(0, need - assigned),
                isComplete: assigned === need,
                isOverAssigned: assigned > need,
                hasHalfDouble: hasHalfDouble,
                halfDoubleSeats: halfDoubleSeats,
                lines: lines,
            };
        }

        function getSourcePoolUsage(state) {
            var summary = getRoomAllocationSummary(state);
            var metaBySource = {};
            var usedSeatsBySource = {};

            (state && Array.isArray(state.availableRoomsCurrent) ? state.availableRoomsCurrent : []).forEach(function (r) {
                if (!r) {
                    return;
                }
                var sourceId = parseInt(r.source_room_id || r.id || "0", 10) || 0;
                if (sourceId <= 0 || metaBySource[sourceId]) {
                    return;
                }
                var roomCount = parseInt(r.quantity || "0", 10) || 0;
                var capacityPerRoom = parseInt(r.source_capacity_per_room || r.capacity_per_room || "1", 10) || 1;
                metaBySource[sourceId] = {
                    roomCount: Math.max(0, roomCount),
                    capacityPerRoom: Math.max(1, capacityPerRoom),
                    maxSeats: Math.max(0, roomCount) * Math.max(1, capacityPerRoom),
                    sourceRoomType: String(r.source_room_type || detectSourceRoomType(r.room_type || "")),
                };
            });

            summary.lines.forEach(function (line) {
                var sourceId = parseInt(line.sourceRoomId || "0", 10) || 0;
                if (sourceId <= 0) {
                    return;
                }
                usedSeatsBySource[sourceId] = (usedSeatsBySource[sourceId] || 0) + (parseInt(line.assigned || "0", 10) || 0);
            });

            return {
                metaBySource: metaBySource,
                usedSeatsBySource: usedSeatsBySource,
                summary: summary,
            };
        }

        function computeTotalFromState(state) {
            var base = window.ajtbRecapBase || {};
            var pricing = base.pricing || {};
            var datePrices = base.datePrices || {};
            var currency = pricing.currency || (state.price ? state.price.currency : "MAD") || "MAD";
            var baseAdult = parseFloat(pricing.adult || "0");
            var baseChild = parseFloat(pricing.child || "0");
            if (!isFinite(baseAdult) || baseAdult < 0) { baseAdult = 0; }
            if (!isFinite(baseChild) || baseChild < 0) { baseChild = 0; }

            var adults = state.guests ? parseInt(state.guests.adults || "2", 10) : 2;
            var children = state.guests ? parseInt(state.guests.children || "0", 10) : 0;
            if (!isFinite(adults) || adults < 1) { adults = 1; }
            if (!isFinite(children) || children < 0) { children = 0; }

            var adultUnit = baseAdult;
            var dateValue = state.date ? String(state.date.value || "") : "";
            if (dateValue && datePrices && datePrices[dateValue] && datePrices[dateValue].specific_price !== null && datePrices[dateValue].specific_price !== undefined) {
                var dp = parseFloat(datePrices[dateValue].specific_price);
                if (isFinite(dp) && dp > 0) {
                    adultUnit = dp;
                }
            }
            if (!isFinite(adultUnit) || adultUnit < 0) { adultUnit = 0; }
            var childUnit = baseChild > 0 ? baseChild : adultUnit;

            var activitiesTotal = 0;
            if (state.activities && state.activities.length) {
                state.activities.forEach(function (a) {
                    var p = parseFloat(a.price || "0");
                    if (!isFinite(p) || p <= 0) { return; }
                    var travellers = adults + children;
                    if (!isFinite(travellers) || travellers < 1) { travellers = 1; }

                    // Per-passenger activation:
                    // a.assigned is an array of slot indexes (0 = principal client, 1.. = companions).
                    // If missing, default to all travellers.
                    var assignedCount = 0;
                    if (a && Array.isArray(a.assigned) && a.assigned.length) {
                        var uniq = {};
                        a.assigned.forEach(function (idx) {
                            var n = parseInt(idx, 10);
                            if (!isFinite(n) || n < 0) { return; }
                            uniq[String(n)] = true;
                        });
                        assignedCount = Object.keys(uniq).length;
                    } else {
                        assignedCount = travellers;
                    }
                    assignedCount = Math.max(0, Math.min(travellers, assignedCount));
                    activitiesTotal += p * assignedCount;
                });
            }

            var adultSubtotal = adults * adultUnit;
            var childSubtotal = children * childUnit;
            var total = adultSubtotal + childSubtotal + activitiesTotal;
            var roomTotal = 0;
            var halfDoublePending = false;
            (function computeRoomSupplementTotal() {
                var summary = getRoomAllocationSummary(state);
                if (!summary.lines.length) { return; }

                summary.lines.forEach(function (line) {
                    var supp = parseFloat(line.room && line.room.supplement ? line.room.supplement : "0");
                    if (!isFinite(supp) || supp < 0) {
                        supp = 0;
                    }
                    if (supp > 0 && line.assigned > 0) {
                        roomTotal += supp * line.assigned;
                    }
                    if (line.isHalfDouble) {
                        halfDoublePending = true;
                    }
                });
                if (roomTotal > 0) {
                    total += roomTotal;
                }
            })();
            var extrasTotal = 0;
            if (state.extras && state.extras.length) {
                var travellerTypes = Array.isArray(state.travellerTypes) ? state.travellerTypes : null;
                state.extras.forEach(function (ex) {
                    if (!ex) return;
                    var pa = parseFloat(ex.price_adult || "0");
                    var pc = parseFloat(ex.price_child || "0");
                    if (!isFinite(pa) || pa < 0) pa = 0;
                    if (!isFinite(pc) || pc < 0) pc = 0;
                    var travellers = adults + children;
                    if (!isFinite(travellers) || travellers < 1) travellers = 1;

                    var assigned = [];
                    if (Array.isArray(ex.assigned) && ex.assigned.length) {
                        assigned = ex.assigned.map(function (x) { return parseInt(x, 10); }).filter(function (n) {
                            return isFinite(n) && n >= 0 && n < travellers;
                        });
                    } else {
                        for (var i = 0; i < travellers; i++) assigned.push(i);
                    }

                    var adultCount = 0;
                    var childCount = 0;
                    assigned.forEach(function (slot) {
                        var t = (travellerTypes && travellerTypes[slot]) ? travellerTypes[slot] : (slot === 0 ? "adult" : "adult");
                        if (t === "child") childCount += 1;
                        else adultCount += 1;
                    });

                    if (pa > 0) extrasTotal += pa * adultCount;
                    if (pc > 0) extrasTotal += pc * childCount;
                });
                total += extrasTotal;
            }
            if (!isFinite(total) || total < 0) { total = 0; }
            return {
                total: total,
                currency: currency,
                adultUnit: adultUnit,
                childUnit: childUnit,
                adults: adults,
                children: children,
                adultSubtotal: adultSubtotal,
                childSubtotal: childSubtotal,
                activitiesTotal: activitiesTotal,
                roomTotal: roomTotal,
                extrasTotal: extrasTotal,
                halfDoublePending: halfDoublePending,
            };
        }

        function syncFormFromPayload(state) {
            var fromSelect = document.getElementById("ajtb-v1-search-from");
            var dateSelect = document.getElementById("ajtb-v1-search-date");
            var adultsInput = document.getElementById("ajtb-v1-guest-adults-input");
            var childrenInput = document.getElementById("ajtb-v1-guest-children-input");

            if (fromSelect && state.departure && state.departure.id) {
                fromSelect.value = String(state.departure.id);
            }
            if (dateSelect && state.date && state.date.value) {
                dateSelect.value = String(state.date.value);
            }
            if (adultsInput && state.guests) {
                adultsInput.value = String(Math.max(1, parseInt(state.guests.adults || "2", 10) || 2));
            }
            if (childrenInput && state.guests) {
                childrenInput.value = String(Math.max(0, parseInt(state.guests.children || "0", 10) || 0));
            }
            // Re-render guest summary using existing picker logic (already initialised on page).
            document.dispatchEvent(new CustomEvent("ajtb:v1:travellers-changed"));
        }

        function readPayloadFromForm(state) {
            var next = state || payload;
            var fromSelect = document.getElementById("ajtb-v1-search-from");
            var dateSelect = document.getElementById("ajtb-v1-search-date");
            var adultsInput = document.getElementById("ajtb-v1-guest-adults-input");
            var childrenInput = document.getElementById("ajtb-v1-guest-children-input");

            if (fromSelect && fromSelect.options && fromSelect.selectedIndex >= 0) {
                var opt = fromSelect.options[fromSelect.selectedIndex];
                next.departure = {
                    id: parseInt(fromSelect.value || "0", 10) || 0,
                    label: (opt.getAttribute("data-place-name") || opt.textContent || "—").trim(),
                };
            }
            if (dateSelect && dateSelect.options && dateSelect.selectedIndex >= 0) {
                var dopt = dateSelect.options[dateSelect.selectedIndex];
                next.date = {
                    value: String(dateSelect.value || ""),
                    label: String(dopt.textContent || dateSelect.value || "—").trim(),
                };
            }
            next.guests = next.guests || { adults: 2, children: 0, label: "" };
            next.guests.adults = adultsInput ? (parseInt(adultsInput.value || "2", 10) || 2) : 2;
            next.guests.children = childrenInput ? (parseInt(childrenInput.value || "0", 10) || 0) : 0;
            return next;
        }

        function renderRecap(state) {
            state = state || payload;
            var calc = computeTotalFromState(state);

            setField("hotel", state.hotel && state.hotel.label ? state.hotel.label : "—");
            setField("flight", state.flight && state.flight.label ? state.flight.label : "Non indiqué");
            setField("transfers", state.transfers && state.transfers.label ? state.transfers.label : "—");

            var activitiesLabel = "—";
            if (state.activities && state.activities.length) {
                activitiesLabel = state.activities.map(function (a) { return a.title; }).filter(Boolean).join(", ");
            }
            setField("activities", activitiesLabel);

            var optionsLabel = "—";
            if (state.options && state.options.length) {
                optionsLabel = state.options.join(", ");
            }
            setField("options", optionsLabel);

            var guestsLabel = (state.guests ? (state.guests.adults + " adulte(s)" + (state.guests.children > 0 ? (", " + state.guests.children + " enfant(s)") : "")) : "—");
            setField("guests", guestsLabel);
            setField("guestBreakdown", (state.guests ? (state.guests.adults + " adulte(s)" + (state.guests.children > 0 ? (" • " + state.guests.children + " enfant(s)") : "")) : "—"));
            setField("departure", state.departure && state.departure.label ? state.departure.label : "—");
            setField("date", state.date && state.date.label ? state.date.label : "—");

            setField("total", formatMoney(calc.total));
            setField("totalLine", formatMoney(calc.total));
            setField("currency", calc.currency);
            setField("currencyLine", calc.currency);
            setField("priceAdults", formatMoney(calc.adultSubtotal) + " " + calc.currency);
            setField("priceChildren", formatMoney(calc.childSubtotal) + " " + calc.currency);
            setField("priceActivities", formatMoney(calc.activitiesTotal) + " " + calc.currency);
            setField("priceExtras", formatMoney(calc.extrasTotal) + " " + calc.currency);
            setField("priceRoom", formatMoney(calc.roomTotal) + " " + calc.currency);
            setField("demiDoubleStatus", calc.halfDoublePending ? "En attente de jumelage" : "—");
            setRowVisibility("children", calc.children > 0);
            setRowVisibility("room", calc.roomTotal > 0);
            setRowVisibility("demiDouble", calc.halfDoublePending);
        }

        // Initial render from payload and hydrate controls.
        syncFormFromPayload(payload);
        payload = readPayloadFromForm(payload);
        renderRecap(payload);

        function renderRooms(rooms) {
            var box = document.getElementById("ajtb-v1-room-picker");
            if (!box) return;
            if (!rooms || !rooms.length) {
                box.innerHTML = '<p class="ajtb-v1-recap-muted">Aucune chambre disponible pour ce départ.</p>';
                return;
            }
            payload.roomAllocation = payload.roomAllocation && typeof payload.roomAllocation === "object" ? payload.roomAllocation : {};
            payload.availableRoomsCurrent = expandRoomModes(rooms);

            // Keep only currently available room ids.
            var normalized = {};
            (payload.availableRoomsCurrent || []).forEach(function (r) {
                var id = String(r.alloc_key || r.id || "");
                if (!id) return;
                var qty = parseInt(payload.roomAllocation[id] || "0", 10) || 0;
                normalized[id] = Math.max(0, qty);
            });
            payload.roomAllocation = normalized;

            function trySmartSuggestion() {
                var summary = getRoomAllocationSummary(payload);
                if (summary.assigned > 0 || summary.need <= 0) {
                    return;
                }
                var best = null;
                (payload.availableRoomsCurrent || []).forEach(function (r) {
                    var stock = parseInt(r.quantity || "0", 10) || 0;
                    if (stock <= 0) {
                        return;
                    }
                    var unit = roomAssignmentUnit(r);
                    if (unit > summary.need) {
                        return;
                    }
                    if (!best || unit > best.unit) {
                        best = { id: String(r.alloc_key || r.id || ""), unit: unit };
                    }
                });
                if (best && best.id) {
                    payload.roomAllocation[best.id] = 1;
                }
            }
            trySmartSuggestion();

            function canIncrease(room, qty) {
                var summary = getRoomAllocationSummary(payload);
                var assignUnit = roomAssignmentUnit(room);
                if ((summary.assigned + assignUnit) > summary.need) {
                    return false;
                }

                var pool = getSourcePoolUsage(payload);
                var sourceId = parseInt(room.source_room_id || room.id || "0", 10) || 0;
                if (sourceId <= 0) {
                    return false;
                }
                var meta = pool.metaBySource[sourceId];
                if (!meta) {
                    return false;
                }
                var usedSeats = pool.usedSeatsBySource[sourceId] || 0;
                var currentSeats = Math.max(0, qty) * assignUnit;
                var nextUsedSeats = (usedSeats - currentSeats) + ((Math.max(0, qty) + 1) * assignUnit);
                return nextUsedSeats <= meta.maxSeats;
            }

            function render() {
                var summary = getRoomAllocationSummary(payload);
                var need = summary.need;
                var got = summary.assigned;
                var ok = summary.isComplete;
                box.innerHTML =
                    '<div class="ajtb-v1-room-alloc-summary">' +
                    '<div><strong>Voyageurs à répartir :</strong> ' + escapeHtml(String(need)) + ' adulte(s)' +
                    '<br><strong>Voyageurs affectés :</strong> ' + escapeHtml(String(got)) + ' / ' + escapeHtml(String(need)) +
                    '<br><strong>Reste à affecter :</strong> ' + escapeHtml(String(summary.remaining)) + '</div>' +
                    '<div class="ajtb-v1-room-alloc-badge">' + (ok ? 'OK' : 'À compléter') + '</div>' +
                    '</div>' +
                    (payload.availableRoomsCurrent || []).map(function (r) {
                        var id = String(r.alloc_key || r.id || "");
                        var cap = parseInt(r.capacity_per_room || "1", 10) || 1;
                        var stock = parseInt(r.quantity || "0", 10) || 0;
                        var sourceId = parseInt(r.source_room_id || r.id || "0", 10) || 0;
                        var supp = parseFloat(r.supplement || "0");
                        if (!isFinite(supp) || supp < 0) { supp = 0; }
                        var qty = parseInt(payload.roomAllocation[id] || "0", 10) || 0;
                        qty = Math.max(0, qty);
                        var unit = roomAssignmentUnit(r);
                        var lineAssigned = qty * unit;
                        var half = isHalfDoubleRoom(r);
                        var canMinus = qty > 0;
                        var canPlus = canIncrease(r, qty);
                        var pool = getSourcePoolUsage(payload);
                        var poolMeta = pool.metaBySource[sourceId] || null;
                        var poolUsed = pool.usedSeatsBySource[sourceId] || 0;
                        var poolSeatsLeft = poolMeta ? Math.max(0, poolMeta.maxSeats - poolUsed) : 0;
                        var displayStock = half
                            ? (poolMeta ? (poolSeatsLeft + " place(s)") : (stock + " place(s)"))
                            : (poolMeta && poolMeta.sourceRoomType === "double"
                                ? (Math.floor(poolSeatsLeft / 2) + " chambre(s)")
                                : (stock + " chambre(s)"));
                        return '' +
                            '<div class="ajtb-v1-room-alloc-row ajtb-v1-room-card" data-ajtb-room-id="' + escapeHtml(id) + '">' +
                            '<div>' +
                            '<strong>' + escapeHtml(String(r.room_label || r.room_type || "Chambre")) + '</strong>' +
                            '<small>Pour ' + escapeHtml(String(unit)) + ' personne(s) · Stock disponible : ' + escapeHtml(displayStock) + '</small>' +
                            (half ? '<small>Chambre double à partager</small>' : '') +
                            '<small>' + (supp > 0 ? ('Supplément : +' + escapeHtml(formatMoney(supp)) + ' ' + escapeHtml(String(payload.currency || "MAD")) + '/personne') : 'Prix : inclus') + '</small>' +
                            (half ? '<small class="ajtb-v1-room-pending">Demi-double - en attente de jumelage</small>' : '') +
                            '<small>Voyageurs affectés via cette chambre : ' + escapeHtml(String(lineAssigned)) + '</small>' +
                            '</div>' +
                            '<div class="ajtb-v1-room-stepper">' +
                            '<button type="button" data-ajtb-room-minus ' + (canMinus ? "" : "disabled") + '>-</button>' +
                            '<span data-ajtb-room-qty>' + escapeHtml(String(qty)) + '</span>' +
                            '<button type="button" data-ajtb-room-plus ' + (canPlus ? "" : "disabled") + '>+</button>' +
                            '</div>' +
                            '</div>';
                    }).join("");
            }

            render();
            renderRecap(payload);
            try { localStorage.setItem("ajtb:v1:recap:" + String(tourId), JSON.stringify(payload)); } catch (e) {}

            // IMPORTANT: avoid binding multiple listeners (otherwise + adds twice).
            if (box.dataset.ajtbRoomHandlerBound === "1") {
                return;
            }
            box.dataset.ajtbRoomHandlerBound = "1";

            box.addEventListener("click", function (e) {
                var row = e.target && e.target.closest ? e.target.closest("[data-ajtb-room-id]") : null;
                if (!row) return;
                var id = String(row.getAttribute("data-ajtb-room-id") || "");
                if (!id) return;
                var isPlus = e.target && e.target.closest ? e.target.closest("[data-ajtb-room-plus]") : null;
                var isMinus = e.target && e.target.closest ? e.target.closest("[data-ajtb-room-minus]") : null;
                if (!isPlus && !isMinus) return;

                var curRooms = payload.availableRoomsCurrent || [];
                var r = curRooms.find(function (x) { return String(x.id) === id; });
                if (!r) {
                    r = curRooms.find(function (x) { return String(x.alloc_key || "") === id; });
                }
                if (!r) return;
                var qty = parseInt(payload.roomAllocation[id] || "0", 10) || 0;
                qty = Math.max(0, qty);
                if (isPlus && canIncrease(r, qty)) qty += 1;
                if (isMinus && qty > 0) qty -= 1;
                payload.roomAllocation[id] = qty;
                render();
                renderRecap(payload);
            }, { passive: true });
        }

        function renderExtras(extras) {
            var box = document.getElementById("ajtb-v1-extras-picker");
            if (!box) return;
            if (!extras || !extras.length) {
                box.innerHTML = '<p class="ajtb-v1-recap-muted">Aucun extra disponible.</p>';
                return;
            }
            box.innerHTML = extras.map(function (ex) {
                var priceParts = [];
                if (ex.price_adult && parseFloat(ex.price_adult) > 0) priceParts.push("Adulte " + formatMoney(ex.price_adult));
                if (ex.price_child && parseFloat(ex.price_child) > 0) priceParts.push("Enfant " + formatMoney(ex.price_child));
                var price = priceParts.length ? (priceParts.join(" / ") + " " + (window.ajtbRecapBase && window.ajtbRecapBase.pricing ? window.ajtbRecapBase.pricing.currency : "MAD")) : "—";
                return '' +
                    '<div class="ajtb-v1-choice-item">' +
                    '<span></span>' +
                    '<span><strong>' + escapeHtml(String(ex.name || "Extra")) + '</strong>' + (ex.description ? ('<small>' + escapeHtml(String(ex.description)) + '</small>') : '') + '</span>' +
                    '<span class="ajtb-v1-choice-price">' + escapeHtml(price) + '</span>' +
                    '</div>';
            }).join("");
        }

        function getTravellerTypesForExtras() {
            var list = document.getElementById("ajtb-recap-companions-list");
            if (list) {
                var types = ["adult"]; // slot 0 = client
                Array.prototype.slice.call(list.querySelectorAll("[data-companion-row]")).forEach(function (row) {
                    var sel = row.querySelector("[data-companion-type]");
                    var t = sel ? String(sel.value || "adult") : "adult";
                    types.push(t === "child" ? "child" : "adult");
                });
                return types;
            }
            // Fallback from guests picker.
            var adultsInput = document.getElementById("ajtb-v1-guest-adults-input");
            var childrenInput = document.getElementById("ajtb-v1-guest-children-input");
            var a = adultsInput ? (parseInt(adultsInput.value || "1", 10) || 1) : 1;
            var c = childrenInput ? (parseInt(childrenInput.value || "0", 10) || 0) : 0;
            a = Math.max(1, a); c = Math.max(0, c);
            var out = ["adult"];
            for (var i = 1; i < a; i++) out.push("adult");
            for (var j = 0; j < c; j++) out.push("child");
            return out;
        }

        function ensureExtraAssignments() {
            payload.extras = Array.isArray(payload.extras) ? payload.extras : [];
            var types = getTravellerTypesForExtras();
            payload.travellerTypes = types;
            var travellers = types.length;
            payload.extras.forEach(function (ex) {
                if (!ex) return;
                if (!Array.isArray(ex.assigned) || !ex.assigned.length) {
                    ex.assigned = [];
                    for (var i = 0; i < travellers; i++) ex.assigned.push(i);
                    return;
                }
                ex.assigned = ex.assigned.map(function (x) { return parseInt(x, 10); }).filter(function (n) {
                    return isFinite(n) && n >= 0 && n < travellers;
                });
                for (var k = 0; k < travellers; k++) {
                    if (ex.assigned.indexOf(k) === -1) ex.assigned.push(k);
                }
            });
        }

        function renderExtrasAssignment() {
            var box = document.getElementById("ajtb-v1-extras-assign");
            if (!box) return;
            if (!payload.extras || !payload.extras.length) {
                box.innerHTML = "";
                return;
            }
            ensureExtraAssignments();
            var types = payload.travellerTypes || getTravellerTypesForExtras();
            var travellers = types.length;
            var labels = types.map(function (t, idx) {
                if (idx === 0) return "Client";
                return (t === "child" ? ("Enfant " + idx) : ("Adulte " + idx));
            });

            box.innerHTML = labels.map(function (title, slot) {
                return '' +
                    '<div class="ajtb-v1-extras-person" data-ajtb-extra-person="' + slot + '">' +
                    '<h3>' + escapeHtml(title) + '</h3>' +
                    '<div class="ajtb-v1-extras-chips">' +
                    payload.extras.map(function (ex, exIdx) {
                        var checked = ex && Array.isArray(ex.assigned) && ex.assigned.indexOf(slot) !== -1;
                        var t = types[slot] === "child" ? "child" : "adult";
                        var p = t === "child" ? parseFloat(ex.price_child || "0") : parseFloat(ex.price_adult || "0");
                        if (!isFinite(p) || p < 0) p = 0;
                        var label = String(ex.name || "Extra") + (p > 0 ? (" · " + formatMoney(p) + " " + ((window.ajtbRecapBase && window.ajtbRecapBase.pricing) ? window.ajtbRecapBase.pricing.currency : "MAD")) : "");
                        return '' +
                            '<label class="ajtb-v1-recap-activity-toggle">' +
                            '<input type="checkbox" data-ajtb-extra-toggle data-slot="' + slot + '" data-extra-idx="' + exIdx + '"' + (checked ? ' checked' : '') + '>' +
                            '<span>' + escapeHtml(label) + '</span>' +
                            '</label>';
                    }).join("") +
                    '</div>' +
                    '</div>';
            }).join("");
        }

        function loadRoomsExtras() {
            var adultsInput = document.getElementById("ajtb-v1-guest-adults-input");
            var childrenInput = document.getElementById("ajtb-v1-guest-children-input");
            var dateSelect = document.getElementById("ajtb-v1-search-date");
            if (!dateSelect || !dateSelect.value) {
                return;
            }
            var formData = new FormData();
            formData.append("action", "ajtb_v1_get_rooms_extras");
            formData.append("nonce", (window.ajtbData && window.ajtbData.reservationNonce) ? window.ajtbData.reservationNonce : "");
            formData.append("tour_id", String(tourId));
            formData.append("departure_date", String(dateSelect.value || ""));
            formData.append("adults", String(adultsInput ? adultsInput.value : "1"));
            formData.append("children", String(childrenInput ? childrenInput.value : "0"));

            fetch((window.ajtbData && window.ajtbData.ajaxUrl) ? window.ajtbData.ajaxUrl : "/wp-admin/admin-ajax.php", {
                method: "POST",
                credentials: "same-origin",
                body: formData,
            })
                .then(function (r) { return r.json(); })
                .then(function (json) {
                    if (!json || !json.success) return;
                    payload.availableRooms = (json.data && json.data.rooms) ? json.data.rooms : [];
                    payload.availableExtras = (json.data && json.data.extras) ? json.data.extras : [];
                    // Default extras selection: all extras enabled for all travellers.
                    payload.extras = (payload.availableExtras || []).map(function (ex) {
                        return {
                            id: ex.id,
                            name: ex.name,
                            description: ex.description,
                            price_adult: ex.price_adult,
                            price_child: ex.price_child,
                            assigned: Array.isArray(ex.assigned) ? ex.assigned : [],
                        };
                    });
                    renderRooms(payload.availableRooms);
                    renderExtras(payload.availableExtras);
                    renderRecap(payload);
                    document.dispatchEvent(new CustomEvent("ajtb:v1:extras-loaded"));
                    try { localStorage.setItem("ajtb:v1:recap:" + String(tourId), JSON.stringify(payload)); } catch (e) {}
                    renderExtrasAssignment();
                })
                .catch(function () {});
        }

        // initial fetch
        loadRoomsExtras();

        // Update live when changing departure/date.
        var fromSelect = document.getElementById("ajtb-v1-search-from");
        var dateSelect = document.getElementById("ajtb-v1-search-date");
        if (fromSelect) {
            fromSelect.addEventListener("change", function () {
                payload = readPayloadFromForm(payload);
                renderRecap(payload);
                try { localStorage.setItem("ajtb:v1:recap:" + String(tourId), JSON.stringify(payload)); } catch (e) {}
                loadRoomsExtras();
            });
        }
        if (dateSelect) {
            dateSelect.addEventListener("change", function () {
                payload = readPayloadFromForm(payload);
                renderRecap(payload);
                try { localStorage.setItem("ajtb:v1:recap:" + String(tourId), JSON.stringify(payload)); } catch (e) {}
                loadRoomsExtras();
            });
        }
        document.addEventListener("ajtb:v1:travellers-changed", function () {
            payload = readPayloadFromForm(payload);
            renderRecap(payload);
            try { localStorage.setItem("ajtb:v1:recap:" + String(tourId), JSON.stringify(payload)); } catch (e) {}
            loadRoomsExtras();
            renderExtrasAssignment();
        });

        document.addEventListener("ajtb:v1:extras-loaded", function () {
            renderExtrasAssignment();
        });

        var extrasAssign = document.getElementById("ajtb-v1-extras-assign");
        if (extrasAssign) {
            extrasAssign.addEventListener("change", function (e) {
                var chk = e.target && e.target.closest ? e.target.closest("[data-ajtb-extra-toggle]") : null;
                if (!chk) return;
                var slot = parseInt(chk.getAttribute("data-slot") || "-1", 10);
                var exIdx = parseInt(chk.getAttribute("data-extra-idx") || "-1", 10);
                if (!payload.extras || slot < 0 || exIdx < 0 || exIdx >= payload.extras.length) return;
                payload.extras[exIdx].assigned = payload.extras[exIdx].assigned || [];
                var i = payload.extras[exIdx].assigned.indexOf(slot);
                if (chk.checked) {
                    if (i === -1) payload.extras[exIdx].assigned.push(slot);
                } else {
                    if (i !== -1) payload.extras[exIdx].assigned.splice(i, 1);
                }
                payload.travellerTypes = getTravellerTypesForExtras();
                renderRecap(payload);
                try { localStorage.setItem("ajtb:v1:recap:" + String(tourId), JSON.stringify(payload)); } catch (e2) {}
            });
        }

        // Rooms are managed by allocation stepper in renderRooms()

        // (Activities are managed per traveller in "Client & voyageurs")

        (function bindFinalize() {
            var finalize = document.getElementById("ajtb-v1-recap-finalize");
            if (!finalize) return;

            var list = document.getElementById("ajtb-recap-companions-list");
            var addAdultBtn = document.querySelector("[data-ajtb-recap-action='add-adult']");
            var addChildBtn = document.querySelector("[data-ajtb-recap-action='add-child']");
            var submitBtn = document.querySelector("[data-ajtb-recap-action='final-submit']");
            if (!list || !submitBtn) return;

            function companionRowHtml(idx, type) {
                type = type === "child" ? "child" : "adult";
                return '' +
                    '<div class="ajtb-v1-recap-companion-row" data-companion-row="' + idx + '">' +
                    '<select data-companion-type aria-label="Type voyageur">' +
                    '<option value="adult"' + (type === "adult" ? " selected" : "") + '>Adulte</option>' +
                    '<option value="child"' + (type === "child" ? " selected" : "") + '>Enfant</option>' +
                    '</select>' +
                    '<input type="text" placeholder="Prénom" data-companion-first>' +
                    '<input type="text" placeholder="Nom" data-companion-last>' +
                    '<button type="button" data-companion-remove>✕</button>' +
                    '<div class="ajtb-v1-recap-companion-activities" data-companion-activities></div>' +
                    '</div>';
            }

            function getTravellerTypes() {
                var types = ["adult"]; // slot 0 = client
                Array.prototype.slice.call(list.querySelectorAll("[data-companion-row]")).forEach(function (row) {
                    var sel = row.querySelector("[data-companion-type]");
                    var t = sel ? String(sel.value || "adult") : "adult";
                    types.push(t === "child" ? "child" : "adult");
                });
                return types;
            }

            function ensureCompanionsMatchCounts() {
                var adultsInput = document.getElementById("ajtb-v1-guest-adults-input");
                var childrenInput = document.getElementById("ajtb-v1-guest-children-input");
                var adults = adultsInput ? parseInt(adultsInput.value || "1", 10) : 1;
                var children = childrenInput ? parseInt(childrenInput.value || "0", 10) : 0;
                if (!isFinite(adults) || adults < 1) adults = 1;
                if (!isFinite(children) || children < 0) children = 0;

                var desiredAdultCompanions = Math.max(0, adults - 1);
                var desiredChildCompanions = Math.max(0, children);

                var rows = Array.prototype.slice.call(list.querySelectorAll("[data-companion-row]"));
                var adultRows = rows.filter(function (r) {
                    var sel = r.querySelector("[data-companion-type]");
                    return !sel || String(sel.value || "adult") === "adult";
                });
                var childRows = rows.filter(function (r) {
                    var sel = r.querySelector("[data-companion-type]");
                    return sel && String(sel.value || "") === "child";
                });

                function addRow(type) {
                    var idx = list.querySelectorAll("[data-companion-row]").length;
                    list.insertAdjacentHTML("beforeend", companionRowHtml(idx, type));
                }
                function removeLastOfType(type) {
                    var candidates = Array.prototype.slice.call(list.querySelectorAll("[data-companion-row]")).filter(function (r) {
                        var sel = r.querySelector("[data-companion-type]");
                        var t = sel ? String(sel.value || "adult") : "adult";
                        return t === type;
                    });
                    var last = candidates.length ? candidates[candidates.length - 1] : null;
                    if (last) last.remove();
                }

                while (adultRows.length < desiredAdultCompanions) {
                    addRow("adult");
                    adultRows.push(true);
                }
                while (adultRows.length > desiredAdultCompanions) {
                    removeLastOfType("adult");
                    adultRows.pop();
                }
                while (childRows.length < desiredChildCompanions) {
                    addRow("child");
                    childRows.push(true);
                }
                while (childRows.length > desiredChildCompanions) {
                    removeLastOfType("child");
                    childRows.pop();
                }
            }

            function ensureActivityAssignments() {
                // Ensure payload activities assigned array matches current travellers.
                try { payload = readPayloadFromForm(payload); } catch (e) {}
                if (!payload || !payload.activities) return;
                var adultsInput = document.getElementById("ajtb-v1-guest-adults-input");
                var childrenInput = document.getElementById("ajtb-v1-guest-children-input");
                var travellers = (adultsInput ? (parseInt(adultsInput.value || "1", 10) || 1) : 1) + (childrenInput ? (parseInt(childrenInput.value || "0", 10) || 0) : 0);
                travellers = Math.max(1, travellers);
                payload.activities.forEach(function (a) {
                    if (!a) return;
                    if (!Array.isArray(a.assigned) || !a.assigned.length) {
                        a.assigned = [];
                        for (var i = 0; i < travellers; i++) a.assigned.push(i);
                        return;
                    }
                    a.assigned = a.assigned.map(function (x) { return parseInt(x, 10); }).filter(function (n) {
                        return isFinite(n) && n >= 0 && n < travellers;
                    });
                    // New travellers default to enabled.
                    for (var j = 0; j < travellers; j++) {
                        if (a.assigned.indexOf(j) === -1) a.assigned.push(j);
                    }
                });
            }

            function renderActivityToggles() {
                if (!payload || !payload.activities) return;
                ensureActivityAssignments();

                var allRows = Array.prototype.slice.call(list.querySelectorAll("[data-companion-row]"));

                function rowForSlot(slotIdx) {
                    return allRows[slotIdx - 1] ? allRows[slotIdx - 1].querySelector("[data-companion-activities]") : null;
                }

                var adultsInput = document.getElementById("ajtb-v1-guest-adults-input");
                var childrenInput = document.getElementById("ajtb-v1-guest-children-input");
                var travellers = (adultsInput ? (parseInt(adultsInput.value || "1", 10) || 1) : 1) + (childrenInput ? (parseInt(childrenInput.value || "0", 10) || 0) : 0);
                travellers = Math.max(1, travellers);

                for (var slot = 0; slot < travellers; slot++) {
                    var host = rowForSlot(slot);
                    if (!host) continue;
                    host.innerHTML = payload.activities.map(function (a, aIdx) {
                        var checked = a && Array.isArray(a.assigned) && a.assigned.indexOf(slot) !== -1;
                        return '' +
                            '<label class="ajtb-v1-recap-activity-toggle">' +
                            '<input type="checkbox" data-ajtb-activity-toggle data-slot="' + slot + '" data-activity-idx="' + aIdx + '"' + (checked ? ' checked' : '') + '>' +
                            '<span>' + escapeHtml(String((a && a.title) ? a.title : 'Activité')) + '</span>' +
                            '</label>';
                    }).join("");
                }
            }


            function syncCountsFromCompanionRows() {
                var adultsInput = document.getElementById("ajtb-v1-guest-adults-input");
                var childrenInput = document.getElementById("ajtb-v1-guest-children-input");
                if (!adultsInput || !childrenInput) return;

                var rows = Array.prototype.slice.call(list.querySelectorAll("[data-companion-row]"));
                var adultCompanions = 0;
                var childCompanions = 0;
                rows.forEach(function (row) {
                    var sel = row.querySelector("[data-companion-type]");
                    var t = sel ? String(sel.value || "adult") : "adult";
                    if (t === "child") childCompanions += 1;
                    else adultCompanions += 1;
                });

                // Principal client is always 1 adult.
                adultsInput.value = String(Math.max(1, 1 + adultCompanions));
                childrenInput.value = String(Math.max(0, childCompanions));
                document.dispatchEvent(new CustomEvent("ajtb:v1:travellers-changed"));
            }

            function adjustCounts(deltaAdults, deltaChildren) {
                var adultsInput = document.getElementById("ajtb-v1-guest-adults-input");
                var childrenInput = document.getElementById("ajtb-v1-guest-children-input");
                if (!adultsInput || !childrenInput) return;
                var a = parseInt(adultsInput.value || "1", 10) || 1;
                var c = parseInt(childrenInput.value || "0", 10) || 0;
                a = Math.max(1, a + (deltaAdults || 0));
                c = Math.max(0, c + (deltaChildren || 0));
                adultsInput.value = String(a);
                childrenInput.value = String(c);
                document.dispatchEvent(new CustomEvent("ajtb:v1:travellers-changed"));
                ensureCompanionsMatchCounts();
            }

            if (addAdultBtn) {
                addAdultBtn.addEventListener("click", function () {
                    adjustCounts(1, 0);
                    renderActivityToggles();
                });
            }
            if (addChildBtn) {
                addChildBtn.addEventListener("click", function () {
                    adjustCounts(0, 1);
                    renderActivityToggles();
                });
            }

            list.addEventListener("click", function (e) {
                var rm = e.target && e.target.closest ? e.target.closest("[data-companion-remove]") : null;
                if (!rm) return;
                var row = rm.closest("[data-companion-row]");
                if (row) {
                    var sel = row.querySelector("[data-companion-type]");
                    var type = sel ? String(sel.value || "adult") : "adult";
                    row.remove();
                    // Keep counts consistent with UI intent: removing a row reduces counts.
                    if (type === "child") adjustCounts(0, -1);
                    else adjustCounts(-1, 0);
                    renderActivityToggles();
                }
            });

            list.addEventListener("change", function (e) {
                var typeSel = e.target && e.target.closest ? e.target.closest("[data-companion-type]") : null;
                if (typeSel) {
                    // When user changes a row type (adult/enfant), sync the travellers widget.
                    syncCountsFromCompanionRows();
                    renderActivityToggles();
                    return;
                }

                var chk = e.target && e.target.closest ? e.target.closest("[data-ajtb-activity-toggle]") : null;
                if (!chk) return;
                var slot = parseInt(chk.getAttribute("data-slot") || "-1", 10);
                var aIdx = parseInt(chk.getAttribute("data-activity-idx") || "-1", 10);
                if (!payload.activities || slot < 0 || aIdx < 0 || aIdx >= payload.activities.length) return;
                payload.activities[aIdx].assigned = payload.activities[aIdx].assigned || [];
                var i = payload.activities[aIdx].assigned.indexOf(slot);
                if (chk.checked) {
                    if (i === -1) payload.activities[aIdx].assigned.push(slot);
                } else {
                    if (i !== -1) payload.activities[aIdx].assigned.splice(i, 1);
                }
                renderRecap(payload);
                try { localStorage.setItem("ajtb:v1:recap:" + String(tourId), JSON.stringify(payload)); } catch (e) {}
            });


            function collectPassengers() {
                return Array.prototype.slice.call(list.querySelectorAll("[data-companion-row]")).map(function (row) {
                    var typeSel = row.querySelector("[data-companion-type]");
                    var first = row.querySelector("[data-companion-first]");
                    var last = row.querySelector("[data-companion-last]");
                    return {
                        first_name: first ? String(first.value || "").trim() : "",
                        last_name: last ? String(last.value || "").trim() : "",
                        type: typeSel ? String(typeSel.value || "adult") : "adult",
                    };
                }).filter(function (p) {
                    return p.first_name || p.last_name;
                });
            }

            // Keep companion rows aligned with current adults/children selections.
            ensureCompanionsMatchCounts();
            document.addEventListener("ajtb:v1:travellers-changed", ensureCompanionsMatchCounts);
            // Some themes/plugins update inputs after initial paint; re-sync on next ticks.
            setTimeout(ensureCompanionsMatchCounts, 0);
            setTimeout(ensureCompanionsMatchCounts, 250);
            // Render activity toggles per traveller
            renderActivityToggles();
            document.addEventListener("ajtb:v1:travellers-changed", function () {
                // Rows may change; rerender toggles
                renderActivityToggles();
            });

            submitBtn.addEventListener("click", function () {
                var first = document.getElementById("ajtb-client-first");
                var last = document.getElementById("ajtb-client-last");
                if (!first || !last) return;
                var fn = String(first.value || "").trim();
                var ln = String(last.value || "").trim();
                if (!fn || !ln) {
                    alert("Veuillez saisir le prénom et le nom du client.");
                    return;
                }

                payload = readPayloadFromForm(payload);
                var calc = computeTotalFromState(payload);
                payload.price = payload.price || {};
                payload.price.total = calc.total;
                payload.price.currency = calc.currency;

                var formData = new FormData();
                formData.append("action", "ajtb_v1_create_reservation");
                formData.append("nonce", (window.ajtbData && window.ajtbData.reservationNonce) ? window.ajtbData.reservationNonce : "");
                formData.append("tour_id", String(tourId));
                formData.append("departure_place_id", String(payload.departure && payload.departure.id ? payload.departure.id : 0));
                formData.append("departure_date", String(payload.date && payload.date.value ? payload.date.value : ""));
                formData.append("adults", String(payload.guests && payload.guests.adults ? payload.guests.adults : 1));
                formData.append("children", String(payload.guests && payload.guests.children ? payload.guests.children : 0));
                formData.append("client_mode", "new");
                formData.append("client_first_name", fn);
                formData.append("client_last_name", ln);
                formData.append("client_phone", document.getElementById("ajtb-client-phone") ? document.getElementById("ajtb-client-phone").value : "");
                formData.append("client_email", document.getElementById("ajtb-client-email") ? document.getElementById("ajtb-client-email").value : "");
                formData.append("client_document_type", "");
                formData.append("client_document_number", "");
                formData.append("passengers", JSON.stringify(collectPassengers()));
                formData.append("room_id", String(payload.room && payload.room.id ? payload.room.id : 0));

                var roomSummary = getRoomAllocationSummary(payload);
                if (!roomSummary.isComplete || roomSummary.isOverAssigned) {
                    alert("Veuillez affecter exactement tous les voyageurs aux chambres avant confirmation.");
                    return;
                }

                // Persist detailed allocation for backend stock/status validation.
                try {
                    formData.append("room_allocation_json", JSON.stringify(roomSummary.lines.map(function (line) {
                        return {
                            room_id: parseInt(line.sourceRoomId || line.id, 10) || 0,
                            room_mode: String(line.roomMode || "full_room"),
                            shared_room_status: line.isHalfDouble ? "pending" : null,
                            source_room_type: String(line.sourceRoomType || "other"),
                            room_count: line.qty,
                            assigned_travellers: line.assigned,
                            assignment_unit: line.assignUnit,
                            room_type: String(line.room && line.room.room_type ? line.room.room_type : ""),
                            hotel_id: line.room && line.room.hotel_id !== null && line.room.hotel_id !== undefined ? parseInt(line.room.hotel_id, 10) || null : null,
                            capacity_per_room: parseInt(line.room && line.room.capacity_per_room ? line.room.capacity_per_room : "1", 10) || 1,
                            supplement: parseFloat(line.room && line.room.supplement ? line.room.supplement : "0") || 0,
                            is_half_double: !!line.isHalfDouble,
                        };
                    })));
                } catch (eRoom) {
                    formData.append("room_allocation_json", "[]");
                }

                var extrasPayload = [];
                // Per-traveller extras -> one row per assigned slot (passenger_key = slot:N)
                if (payload.extras && payload.extras.length) {
                    var types = getTravellerTypes();
                    payload.extras.forEach(function (ex) {
                        if (!ex || !ex.name) return;
                        var assigned = Array.isArray(ex.assigned) && ex.assigned.length ? ex.assigned : [];
                        if (!assigned.length) {
                            for (var s = 0; s < types.length; s++) assigned.push(s);
                        }
                        assigned.forEach(function (slot) {
                            var t = types[slot] === "child" ? "child" : "adult";
                            var p = t === "child" ? parseFloat(ex.price_child || "0") : parseFloat(ex.price_adult || "0");
                            if (!isFinite(p) || p <= 0) return;
                            extrasPayload.push({
                                name: ex.name,
                                price: p,
                                passenger_key: "slot:" + String(slot),
                            });
                        });
                    });
                }
                roomSummary.lines.forEach(function (line) {
                    var supp = parseFloat(line.room && line.room.supplement ? line.room.supplement : "0");
                    if (!isFinite(supp) || supp <= 0) {
                        return;
                    }
                    extrasPayload.push({
                        name: "Supplément chambre (" + (line.room.room_type || "chambre") + ")",
                        price: supp * line.assigned,
                    });
                });
                formData.append("extras_json", JSON.stringify(extrasPayload));

                submitBtn.disabled = true;
                submitBtn.textContent = "En cours…";

                fetch((window.ajtbData && window.ajtbData.ajaxUrl) ? window.ajtbData.ajaxUrl : "/wp-admin/admin-ajax.php", {
                    method: "POST",
                    credentials: "same-origin",
                    body: formData,
                })
                    .then(function (r) { return r.json(); })
                    .then(function (json) {
                        if (!json || !json.success) {
                            throw new Error((json && json.data && json.data.message) ? json.data.message : "Erreur lors de la réservation.");
                        }
                        var rid = json.data && json.data.reservation_id ? json.data.reservation_id : null;
                        var created = !!(json.data && json.data.account_created);
                        var login = json.data && json.data.login ? String(json.data.login) : "";
                        var password = json.data && json.data.password ? String(json.data.password) : "";

                        var modalEl = document.getElementById("ajtb-account-modal");
                        if (modalEl && window.bootstrap && window.bootstrap.Modal) {
                            var msgEl = document.getElementById("ajtb-account-modal-message");
                            var loginEl = document.getElementById("ajtb-account-login");
                            var passEl = document.getElementById("ajtb-account-password");
                            if (msgEl) {
                                msgEl.textContent = created
                                    ? ("Votre compte client a été créé. Réservation #" + String(rid) + ".")
                                    : ("Réservation #" + String(rid) + " créée. Utilisez votre email pour vous connecter.");
                            }
                            if (loginEl) loginEl.textContent = login || "—";
                            if (passEl) passEl.textContent = password || "—";
                            try {
                                var m = window.bootstrap.Modal.getOrCreateInstance(modalEl);
                                m.show();
                            } catch (eModal) {
                                alert("Réservation créée (ID " + rid + "). Login: " + login + (password ? (" / MDP: " + password) : ""));
                            }
                        } else {
                            alert("Réservation créée (ID " + rid + "). Login: " + login + (password ? (" / MDP: " + password) : ""));
                        }
                    })
                    .catch(function (e) {
                        alert(e && e.message ? e.message : "Erreur lors de la réservation.");
                    })
                    .finally(function () {
                        submitBtn.disabled = false;
                        submitBtn.textContent = "Confirmer la réservation";
                    });
            });

            // Copy helpers for modal credentials
            document.addEventListener("click", function (e) {
                var btn = e.target && e.target.closest ? e.target.closest("[data-ajtb-copy]") : null;
                if (!btn) return;
                var sel = btn.getAttribute("data-ajtb-copy") || "";
                var el = sel ? document.querySelector(sel) : null;
                var text = el ? String(el.textContent || "").trim() : "";
                if (!text || text === "—") return;
                if (navigator && navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).catch(function () {});
                    return;
                }
                try {
                    var ta = document.createElement("textarea");
                    ta.value = text;
                    ta.style.position = "fixed";
                    ta.style.opacity = "0";
                    document.body.appendChild(ta);
                    ta.select();
                    document.execCommand("copy");
                    document.body.removeChild(ta);
                } catch (e2) {}
            });
        })();

        // Final sync pass after all handlers are bound.
        document.dispatchEvent(new CustomEvent("ajtb:v1:travellers-changed"));
    }

    document.addEventListener("DOMContentLoaded", function () {
        initTabs();
        initProgramFilters();
        initDayChips();
        initFloatingButton();
        initGuestsPicker();
        initDynamicStartingPrice();
        initStickySearchBox();
        initOptionalActivitiesActions();
        initContinueToRecap();
        initRestoreSelectionFromRecap();
        initRecapPage();
    });
})();
