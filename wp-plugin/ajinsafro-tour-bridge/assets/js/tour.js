/**
 * Ajinsafro Tour Bridge - Main JavaScript
 * Handles interactions on single tour pages
 *
 * @version 1.0.0
 */

(function ($) {
    "use strict";

    console.log("ajinsafro tour js loaded");

    // Wait for DOM ready
    $(document).ready(function () {
        if (typeof ajtbData !== "undefined") {
            console.log("ajtbData:", {
                ajax_url: ajtbData.ajax_url || ajtbData.ajaxUrl,
                nonce: ajtbData.nonce ? "set" : "missing",
                postId: ajtbData.postId,
                tour_id: ajtbData.tour_id,
            });
        }
        AJTB.init();
    });

    /**
     * Main Tour Module
     */
    var AJTB = {
        // Config
        config: {
            prices: {
                adult: 0,
                child: 0,
                infant: 0,
            },
            currency: "DH",
        },

        /**
         * Initialize all modules
         */
        init: function () {
            this.loadConfig();
            this.initTabs();
            this.initQuantityControls();
            this.initPriceCalculation();
            this.initItineraryAccordion();
            this.initActivityToggle();
            this.initActivityModal();
            this.initActivityEditModal();
            this.initFlightToggle();
            this.initFAQAccordion();
            this.initGallery();
            this.initHeroGallerySlider();
            this.initShareButton();
            this.initSaveButton();
            this.initSmoothScroll();
            this.initStickyNav();
            this.initExpandableContent();
            this.initSearchbar();
            this.initFlightsByDepartureFilter();
        },

        /**
         * Normalize "Starting from" from the selected <option> of #aj-search-from.
         * Priorité: option sélectionnée → out.id = opt.dataset.id (si numérique), out.code = opt.dataset.code, out.name = opt.textContent.trim().
         * Ne dépend pas du JSON data-departure-places.
         */
        normalizeStartingFrom: function () {
            var out = { id: "", name: "", code: "" };
            var select = document.getElementById("aj-search-from");
            if (!select || select.selectedIndex < 0) return out;
            var opt = select.options[select.selectedIndex];
            if (!opt || opt.value === "") return out;
            var dataId =
                opt.dataset && opt.dataset.id != null
                    ? String(opt.dataset.id).trim()
                    : "";
            var dataCode =
                opt.dataset && opt.dataset.code != null
                    ? String(opt.dataset.code).trim()
                    : "";
            var labelText = (opt.textContent || opt.innerText || "")
                .replace(/\s+/g, " ")
                .trim();
            if (/^\d+$/.test(dataId)) out.id = dataId;
            out.code = dataCode;
            out.name =
                labelText !== ""
                    ? labelText
                    : opt.value != null && opt.value !== ""
                      ? String(opt.value).trim()
                      : "";
            return out;
        },

        /**
         * Filter by "Starting from" only:
         * - #aj-flight-details .aj-flight-card
         * - Programme Vol Aller: .ajtb-day-flight-block.ajtb-day-flight-outbound[data-aj-day-number="1"] .aj-flight-card
         * Match by priority: id == data-departure-place-id OR code == data-departure-place-code OR name == data-departure-place-name (case-insensitive).
         * No filter on #flights section.
         */
        applyFlightsByDeparture: function (selectedValueOverride) {
            var $from = $("#aj-search-from");
            var rawVal =
                selectedValueOverride !== undefined && selectedValueOverride !== null
                    ? String(selectedValueOverride).trim()
                    : ($from.length && $from.val() ? String($from.val()).trim() : "");
            var normalized = this.normalizeStartingFrom();
            var selectedLabel = "";
            if ($from.length && $from[0].selectedIndex >= 0) {
                selectedLabel = String(
                    $from[0].options[$from[0].selectedIndex].textContent || "",
                )
                    .replace(/\s+/g, " ")
                    .trim();
            }
            var hasNorm =
                normalized.id !== "" ||
                normalized.name !== "" ||
                normalized.code !== "";
            var nameOnlyNumeric = /^\d+$/.test(normalized.name);
            var showAll =
                rawVal === "" ||
                !hasNorm ||
                (normalized.id === "" &&
                    normalized.code === "" &&
                    nameOnlyNumeric);

            function normStr(s) {
                if (s == null || s === "") return "";
                return String(s).replace(/\s+/g, " ").trim().toLowerCase();
            }
            function nameMatch(a, b) {
                if (a === "" || b === "") return false;
                var na = normStr(a)
                    .replace(/\s*\([^)]*\)\s*$/, "")
                    .trim();
                var nb = normStr(b)
                    .replace(/\s*\([^)]*\)\s*$/, "")
                    .trim();
                return na === nb || normStr(a) === normStr(b);
            }

            function getSelectedFlightId() {
                var searchbar = document.getElementById("aj-searchbar");
                if (!searchbar) return "";
                var rawPlaces =
                    searchbar.getAttribute("data-departure-places") || "[]";
                var places = [];
                try {
                    places = JSON.parse(rawPlaces);
                } catch (e) {
                    places = [];
                }
                if (!Array.isArray(places) || !places.length) return "";

                var selectedPlace = null;
                places.some(function (place) {
                    if (!place || typeof place !== "object") return false;
                    var placeId =
                        place.id != null ? String(place.id).trim() : "";
                    var placeName =
                        place.name != null ? String(place.name).trim() : "";
                    var placeCode =
                        place.code != null ? String(place.code).trim() : "";
                    if (rawVal !== "" && placeId !== "" && placeId === rawVal) {
                        selectedPlace = place;
                        return true;
                    }
                    if (
                        normalized.code !== "" &&
                        placeCode !== "" &&
                        normalized.code === placeCode
                    ) {
                        selectedPlace = place;
                        return true;
                    }
                    if (
                        normalized.name !== "" &&
                        placeName !== "" &&
                        nameMatch(normalized.name, placeName)
                    ) {
                        selectedPlace = place;
                        return true;
                    }
                    return false;
                });

                if (
                    !selectedPlace ||
                    !Array.isArray(selectedPlace.flights) ||
                    !selectedPlace.flights.length
                ) {
                    return "";
                }

                function flightOriginMatches(flight) {
                    if (!flight || typeof flight !== "object") return false;
                    var fromCity =
                        flight.from_city != null
                            ? String(flight.from_city).trim()
                            : "";
                    var fromAirport =
                        flight.from_airport != null
                            ? String(flight.from_airport).trim()
                            : "";
                    return (
                        (normalized.name !== "" &&
                            nameMatch(normalized.name, fromCity)) ||
                        (normalized.name !== "" &&
                            nameMatch(normalized.name, fromAirport))
                    );
                }

                var selectedFlight = null;
                selectedPlace.flights.some(function (flight) {
                    if (!flightOriginMatches(flight)) return false;
                    var id = flight.id != null ? String(flight.id).trim() : "";
                    if (id === "") return false;
                    selectedFlight = flight;
                    return true;
                });

                if (selectedFlight) {
                    return String(selectedFlight.id).trim();
                }

                selectedPlace.flights.some(function (flight) {
                    if (!flight || typeof flight !== "object") return false;
                    var id = flight.id != null ? String(flight.id).trim() : "";
                    if (id === "") return false;
                    selectedFlight = flight;
                    return true;
                });

                return selectedFlight && selectedFlight.id != null
                    ? String(selectedFlight.id).trim()
                    : "";
            }

            function getSelectedPlaceAndFlight() {
                var searchbar = document.getElementById("aj-searchbar");
                if (!searchbar) return null;
                var rawPlaces =
                    searchbar.getAttribute("data-departure-places") || "[]";
                var places = [];
                try {
                    places = JSON.parse(rawPlaces);
                } catch (e) {
                    places = [];
                }
                if (!Array.isArray(places) || !places.length) return null;
                var selectedPlace = null;
                places.some(function (place) {
                    if (!place || typeof place !== "object") return false;
                    var placeId =
                        place.id != null ? String(place.id).trim() : "";
                    var placeName =
                        place.name != null ? String(place.name).trim() : "";
                    var placeCode =
                        place.code != null ? String(place.code).trim() : "";
                    if (rawVal !== "" && placeId !== "" && placeId === rawVal) {
                        selectedPlace = place;
                        return true;
                    }
                    if (
                        normalized.code !== "" &&
                        placeCode !== "" &&
                        normalized.code === placeCode
                    ) {
                        selectedPlace = place;
                        return true;
                    }
                    if (
                        normalized.name !== "" &&
                        placeName !== "" &&
                        nameMatch(normalized.name, placeName)
                    ) {
                        selectedPlace = place;
                        return true;
                    }
                    return false;
                });
                if (
                    !selectedPlace ||
                    !Array.isArray(selectedPlace.flights) ||
                    !selectedPlace.flights.length
                )
                    return null;
                var flight = selectedPlace.flights[0];
                return { place: selectedPlace, flight: flight };
            }

            function formatFlightDate(dateStr) {
                if (!dateStr || String(dateStr).trim() === "") return "—";
                var s = String(dateStr).trim();
                var m = s.match(/^(\d{4})-(\d{2})-(\d{2})/);
                if (m) {
                    var d = new Date(parseInt(m[1], 10), parseInt(m[2], 10) - 1, parseInt(m[3], 10));
                    var days = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
                    var months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
                    return days[d.getDay()] + ", " + ("0" + d.getDate()).slice(-2) + " " + months[d.getMonth()];
                }
                return s;
            }

            function formatFlightTime(t) {
                if (!t || String(t).trim() === "") return "—";
                var s = String(t).trim();
                var m = s.match(/(\d{1,2}):(\d{2})/);
                if (m) return m[1].padStart(2, "0") + ":" + m[2];
                if (s.match(/^\d{4}-\d{2}-\d{2}/)) {
                    var d = new Date(s);
                    return ("0" + d.getHours()).slice(-2) + ":" + ("0" + d.getMinutes()).slice(-2);
                }
                return s;
            }

            function updateCardContentWithFlight($card, placeName, flight) {
                if (!$card || !$card.length || !flight) return;
                var $title = $card.find(".aj-flight-header__title").first();
                var $sub = $card.find(".aj-flight-header__subtitle").first();
                var $depLine = $card.find(".aj-flight-header__departure-place").first();
                var $origTime = $card.find(".aj-flight-timeline__col--origin .aj-flight-time").first();
                var $origPlace = $card.find(".aj-flight-timeline__col--origin .aj-flight-place").first();
                var $destTime = $card.find(".aj-flight-timeline__col--dest .aj-flight-time").first();
                var $destPlace = $card.find(".aj-flight-timeline__col--dest .aj-flight-place").first();
                if (!$card.attr("data-ajtb-original-title") && $title.length)
                    $card.attr("data-ajtb-original-title", $title.text());
                if (!$card.attr("data-ajtb-original-subtitle") && $sub.length)
                    $card.attr("data-ajtb-original-subtitle", $sub.text());
                if (!$card.attr("data-ajtb-original-depline") && $depLine.length)
                    $card.attr("data-ajtb-original-depline", $depLine.text());
                if (!$card.attr("data-ajtb-original-origtime") && $origTime.length)
                    $card.attr("data-ajtb-original-origtime", $origTime.text());
                if (!$card.attr("data-ajtb-original-origplace") && $origPlace.length)
                    $card.attr("data-ajtb-original-origplace", $origPlace.text());
                if (!$card.attr("data-ajtb-original-desttime") && $destTime.length)
                    $card.attr("data-ajtb-original-desttime", $destTime.text());
                if (!$card.attr("data-ajtb-original-destplace") && $destPlace.length)
                    $card.attr("data-ajtb-original-destplace", $destPlace.text());
                var from = (flight.from_city != null ? String(flight.from_city).trim() : "") || "—";
                var to = (flight.to_city != null ? String(flight.to_city).trim() : "") || "—";
                var depTime = formatFlightTime(flight.depart_time);
                var arrTime = formatFlightTime(flight.arrive_time);
                var depDate = formatFlightDate(flight.depart_date);
                var originCode = from !== "—" ? from.substring(0, 3) : "—";
                var destCode = to !== "—" ? to.substring(0, 3) : "—";
                $title.text(from + " → " + to);
                if ($sub.length) $sub.text(depDate).show();
                if (!$depLine.length) {
                    $depLine = $("<p class=\"aj-flight-header__departure-place\"></p>");
                    $card.find(".aj-flight-header__main").first().append($depLine);
                }
                $depLine.text("Départ depuis : " + (placeName || from)).show();
                if ($origTime.length) $origTime.text(depTime);
                if ($origPlace.length) $origPlace.text(originCode + " • " + from);
                if ($destTime.length) $destTime.text(arrTime);
                if ($destPlace.length) $destPlace.text(destCode + " • " + to);
            }

            function restoreCardOriginalContent($card) {
                if (!$card || !$card.length) return;
                var orig = $card.attr("data-ajtb-original-title");
                if (orig) $card.find(".aj-flight-header__title").first().text(orig);
                orig = $card.attr("data-ajtb-original-subtitle");
                if (orig !== undefined) $card.find(".aj-flight-header__subtitle").first().text(orig);
                orig = $card.attr("data-ajtb-original-depline");
                if (orig !== undefined) {
                    var $dl = $card.find(".aj-flight-header__departure-place").first();
                    if ($dl.length) $dl.text(orig);
                }
                orig = $card.attr("data-ajtb-original-origtime");
                if (orig !== undefined) $card.find(".aj-flight-timeline__col--origin .aj-flight-time").first().text(orig);
                orig = $card.attr("data-ajtb-original-origplace");
                if (orig !== undefined) $card.find(".aj-flight-timeline__col--origin .aj-flight-place").first().text(orig);
                orig = $card.attr("data-ajtb-original-desttime");
                if (orig !== undefined) $card.find(".aj-flight-timeline__col--dest .aj-flight-time").first().text(orig);
                orig = $card.attr("data-ajtb-original-destplace");
                if (orig !== undefined) $card.find(".aj-flight-timeline__col--dest .aj-flight-place").first().text(orig);
            }

            var selectedFlightId = getSelectedFlightId();
            var selectedPlaceAndFlight = getSelectedPlaceAndFlight();

            function cardMatches($card, norm) {
                if (showAll) return true;
                var flightId =
                    $card.attr("data-flight-id") != null
                        ? String($card.attr("data-flight-id")).trim()
                        : "";
                if (
                    selectedFlightId !== "" &&
                    flightId !== "" &&
                    flightId === selectedFlightId
                ) {
                    return true;
                }
                var id =
                    $card.attr("data-departure-place-id") != null
                        ? String($card.attr("data-departure-place-id")).trim()
                        : "";
                var code =
                    $card.attr("data-departure-place-code") != null
                        ? String($card.attr("data-departure-place-code")).trim()
                        : "";
                var name =
                    $card.attr("data-departure-place-name") != null
                        ? String($card.attr("data-departure-place-name")).trim()
                        : "";
                if (/^\d+$/.test(norm.id) && id === norm.id) return true;
                if (norm.code !== "" && code === norm.code) return true;
                if (norm.name !== "" && nameMatch(norm.name, name)) return true;
                return false;
            }

            function updateDeparturePlaceLabel($card, labelText) {
                var $main = $card.find(".aj-flight-header__main").first();
                if (!$main.length) return;
                var $line = $main.find(".aj-flight-header__departure-place").first();
                if (!$line.length && labelText) {
                    $line = $('<p class="aj-flight-header__departure-place"></p>');
                    var $subtitle = $main.find(".aj-flight-header__subtitle").first();
                    if ($subtitle.length) {
                        $subtitle.after($line);
                    } else {
                        $main.append($line);
                    }
                }
                if (!$line.length) return;
                if ($line.attr("data-ajtb-original-text") === undefined) {
                    $line.attr("data-ajtb-original-text", $line.text());
                }
                if (labelText) {
                    $line.text("Depart depuis : " + labelText);
                    $line.show();
                } else {
                    var originalText = $line.attr("data-ajtb-original-text") || "";
                    if (originalText) {
                        $line.text(originalText).show();
                    } else {
                        $line.remove();
                    }
                }
            }

            function updateFlightTitle($card, labelText) {
                var $title = $card.find(".aj-flight-header__title").first();
                if (!$title.length) return;
                if ($title.attr("data-ajtb-original-text") === undefined) {
                    $title.attr("data-ajtb-original-text", $title.text());
                }
                var originalText = $title.attr("data-ajtb-original-text") || "";
                var parts = originalText.split(/\s*[→\u2192]\s*/);
                if (labelText && parts.length >= 2) {
                    $title.text(labelText + " → " + parts.slice(1).join(" → "));
                } else {
                    $title.text(originalText);
                }
            }

            function updateOriginContent($card, labelText) {
                var $originPlace = $card
                    .find(".aj-flight-timeline__col--origin .aj-flight-place")
                    .first();
                if (!$originPlace.length) return;
                if ($originPlace.attr("data-ajtb-original-text") === undefined) {
                    $originPlace.attr(
                        "data-ajtb-original-text",
                        $originPlace.text(),
                    );
                }
                var originalText =
                    $originPlace.attr("data-ajtb-original-text") || "";
                if (labelText) {
                    $originPlace.text(labelText);
                } else {
                    $originPlace.text(originalText);
                }
            }

            function keepOnlyFirstVisible($cards) {
                var firstShown = false;
                $cards.each(function () {
                    var $card = $(this);
                    if (!$card.is(":visible")) return;
                    if (!firstShown) {
                        firstShown = true;
                        $card.removeClass("ajtb-flight-card-hidden");
                        return;
                    }
                    $card.addClass("ajtb-flight-card-hidden").hide();
                });
            }

            function showOnlyFirstCard($cards) {
                var firstShown = false;
                $cards.each(function () {
                    var $card = $(this);
                    if (!firstShown) {
                        $card.removeClass("ajtb-flight-card-hidden").show();
                        firstShown = true;
                    } else {
                        $card.addClass("ajtb-flight-card-hidden").hide();
                    }
                });
            }

            var selectorOutbound =
                '.ajtb-day-flight-block.ajtb-day-flight-outbound[data-aj-day-number="1"] .aj-flight-card';
            var $cardsDetails = $("#aj-flight-details .aj-flight-card");
            var $cardsOutbound = $(selectorOutbound);

            var allCards = $cardsDetails.add($cardsOutbound);

            // Une seule carte: on garde la même carte et on change son contenu avec les infos du vol de la ville choisie
            var $outboundBlocks = $('.ajtb-day-flight-block.ajtb-day-flight-outbound[data-aj-day-number="1"]');
            if (!showAll && selectedPlaceAndFlight) {
                $outboundBlocks.removeClass("ajtb-flight-show-all");
                var placeName = selectedPlaceAndFlight.place.name != null ? String(selectedPlaceAndFlight.place.name).trim() : "";
                var flight = selectedPlaceAndFlight.flight;
                if ($cardsDetails.length) {
                    var $firstDetail = $cardsDetails.first();
                    updateCardContentWithFlight($firstDetail, placeName, flight);
                    $firstDetail.removeClass("ajtb-flight-card-hidden").show();
                    $cardsDetails.slice(1).addClass("ajtb-flight-card-hidden").hide();
                }
                if ($cardsOutbound.length) {
                    var $firstOutbound = $cardsOutbound.first();
                    updateCardContentWithFlight($firstOutbound, placeName, flight);
                    $firstOutbound.removeClass("ajtb-flight-card-hidden").show();
                    $cardsOutbound.slice(1).addClass("ajtb-flight-card-hidden").hide();
                }
            } else {
                $outboundBlocks.addClass("ajtb-flight-show-all");
                allCards.removeClass("ajtb-flight-card-hidden").show();
                $cardsOutbound.each(function () {
                    restoreCardOriginalContent($(this));
                });
                if ($cardsDetails.length) restoreCardOriginalContent($cardsDetails.first());
            }

            var $outboundLabels = $(
                '.ajtb-day-flight-block.ajtb-day-flight-outbound[data-aj-day-number="1"]'
            )
                .closest(".ajtb-block-flight, .ajtb-day-flight-block")
                .find(".ajtb-block-title, .ajtb-day-flight-label")
                .first();
            $outboundLabels.each(function () {
                var $label = $(this);
                if ($label.attr("data-ajtb-original-text") === undefined) {
                    $label.attr("data-ajtb-original-text", $label.text());
                }
                var originalText = $label.attr("data-ajtb-original-text") || "";
                if (!showAll && selectedLabel) {
                    $label.text(originalText + " • " + selectedLabel);
                } else {
                    $label.text(originalText);
                }
            });

            if (typeof console !== "undefined" && console.log) {
                console.log(
                    "[AJTB Starting from] raw:",
                    rawVal,
                    "| normalized:",
                    normalized,
                );
                console.log("[AJTB Starting from] cards.length", {
                    details: $cardsDetails.length,
                    outbound: $cardsOutbound.length,
                    total: allCards.length,
                });
            }
        },

        initFlightsByDepartureFilter: function () {
            // Filter is triggered on change in initSearchbar and after localStorage restore below
        },

        /**
         * Load configuration from page
         */
        loadConfig: function () {
            if (typeof ajtbData !== "undefined") {
                this.config.currency = ajtbData.currencySymbol || "DH";
            }

            var $box = $(".ajtb-booking-box");
            if (
                $box.length &&
                $box.data("adult-price") != null &&
                $box.data("adult-price") !== ""
            ) {
                this.config.prices.adult =
                    parseFloat($box.data("adult-price")) || 0;
                this.config.prices.child =
                    parseFloat($box.data("child-price")) || 0;
                if ($box.data("currency"))
                    this.config.currency = $box.data("currency");
            } else {
                var $priceBreakdown = $(".booking-price-breakdown");
                if ($priceBreakdown.length) {
                    var $rows = $priceBreakdown.find(".price-row");
                    var adultPriceText = $rows.first().find(".value").text();
                    this.config.prices.adult = this.parsePrice(adultPriceText);
                    if ($rows.length > 1) {
                        var childPriceText = $rows.eq(1).find(".value").text();
                        this.config.prices.child =
                            this.parsePrice(childPriceText);
                    }
                } else {
                    var headerPrice = $(".price-current").first().text();
                    this.config.prices.adult = this.parsePrice(headerPrice);
                }
            }
        },

        /**
         * Parse price from formatted string
         */
        parsePrice: function (priceStr) {
            if (!priceStr) return 0;
            return parseFloat(priceStr.replace(/[^\d.]/g, "")) || 0;
        },

        /**
         * Format price with thousand separators
         */
        formatPrice: function (price) {
            return Math.round(price)
                .toString()
                .replace(/\B(?=(\d{3})+(?!\d))/g, " ");
        },

        /**
         * Tab navigation.
         * One visible tab panel at a time for top tabbed sections.
         * Other non-tab sections still scroll normally.
         */
        initTabs: function () {
            var $tabLinks = $(".ajtb-tabs-nav .tab-link");
            if (!$tabLinks.length) return;

            var topPanelSelectors = [
                "#overview",
                "#categories",
                "#destinations",
                "#itinerary",
                "#summary",
            ];
            var $exclusivePanels = $(topPanelSelectors.join(",")).filter(
                ".ajtb-tab-panel",
            );

            var $tabPanelLinks = $();
            var $tabPanels = $();

            $tabLinks.each(function () {
                var $link = $(this);
                var target = $link.attr("href");
                if (!target || target.charAt(0) !== "#") return;
                var $target = $(target);
                if ($target.length && $target.hasClass("ajtb-tab-panel")) {
                    $tabPanelLinks = $tabPanelLinks.add($link);
                    $tabPanels = $tabPanels.add($target);
                }
            });

            function hideAllTabPanels() {
                $exclusivePanels
                    .addClass("ajtb-tab-panel-hidden")
                    .attr("aria-hidden", "true")
                    .css("display", "none");
            }

            function showTabPanel(target) {
                if (!target || target.charAt(0) !== "#") return false;
                var $target = $(target);
                if (
                    !$target.length ||
                    !$target.hasClass("ajtb-tab-panel") ||
                    !$exclusivePanels.filter(target).length
                ) {
                    return false;
                }
                $tabPanelLinks.removeClass("active");
                hideAllTabPanels();
                $('.ajtb-tabs-nav .tab-link[href="' + target + '"]').addClass(
                    "active",
                );
                $target
                    .removeClass("ajtb-tab-panel-hidden")
                    .attr("aria-hidden", "false")
                    .css("display", "");
                return true;
            }

            if ($exclusivePanels && $exclusivePanels.length) {
                var defaultTarget = null;
                if ($("#itinerary.ajtb-tab-panel").length) {
                    defaultTarget = "#itinerary";
                } else {
                    var $activePanelLink = $tabPanelLinks.filter(".active").first();
                    defaultTarget = $activePanelLink.length
                        ? $activePanelLink.attr("href")
                        : $tabPanelLinks.first().attr("href");
                }
                showTabPanel(defaultTarget);
            }

            $(document).on("click", ".ajtb-tabs-nav .tab-link", function (e) {
                e.preventDefault();
                var $link = $(this);
                var target = $link.attr("href");

                if ($exclusivePanels && $exclusivePanels.length) {
                    hideAllTabPanels();
                }

                // Tabbed sections: only one visible at a time
                if (showTabPanel(target)) {
                    return;
                }

                // Other tabs: update active and scroll to section
                $(".ajtb-tabs-nav .tab-link").removeClass("active");
                $link.addClass("active");
                if ($(target).length) {
                    $("html, body").animate(
                        { scrollTop: $(target).offset().top - 80 },
                        500
                    );
                }
            });
        },

        /**
         * Quantity +/- controls
         */
        initQuantityControls: function () {
            var self = this;

            $(document).on("click", ".qty-btn", function () {
                var $btn = $(this);
                var target = $btn.data("target");
                var $input = $("#" + target);
                var current = parseInt($input.val(), 10) || 0;
                var min = parseInt($input.attr("min"), 10) || 0;
                var max = parseInt($input.attr("max"), 10) || 99;

                if ($btn.hasClass("plus")) {
                    if (current < max) {
                        $input.val(current + 1);
                    }
                } else if ($btn.hasClass("minus")) {
                    if (current > min) {
                        $input.val(current - 1);
                    }
                }

                // Recalculate price
                self.calculateTotal();
            });
        },

        /**
         * Calculate and display total price
         */
        initPriceCalculation: function () {
            // Calculate total (which also updates cart)
            this.calculateTotal();
        },

        /**
         * Get activities added by client (client_added=true AND price > 0)
         * Returns array of {title, price, day_id, activity_id}
         */
        getAddedActivities: function () {
            var activities = [];
            // Find all activities marked as client_added (added by user)
            $('.day-activity-item[data-client-added="true"]').each(function () {
                var $item = $(this);
                var title = $item.find(".activity-title").text().trim() || "";
                var priceText = $item.find(".activity-price").text().trim();
                var price = 0;
                if (priceText) {
                    price = parseFloat(priceText.replace(/[^\d.]/g, "")) || 0;
                }
                // Only include if price > 0 (activities with price)
                // Activities without price or price=0 are considered "included" and don't affect total
                if (price > 0) {
                    activities.push({
                        title: title,
                        price: price,
                        day_id: $item.data("day-id") || 0,
                        activity_id: $item.data("activity-id") || 0,
                    });
                }
            });
            return activities;
        },

        /**
         * Update cart display with base + activities
         */
        updateCart: function () {
            var adults = parseInt($("#adults").val(), 10) || 0;
            var children = parseInt($("#children").val(), 10) || 0;
            var adultPrice = this.config.prices.adult || 0;
            var childPrice =
                this.config.prices.child !== undefined &&
                this.config.prices.child !== null
                    ? this.config.prices.child
                    : 0;

            // Update base labels
            $("#cart-adults-count").text(adults);
            if ($("#cart-children-count").length) {
                $("#cart-children-count").text(children);
            }

            // Update base value: (adultes × prix adulte) + (enfants × prix enfant)
            var baseTotal = adults * adultPrice + children * childPrice;
            $("#cart-base-value").text(
                this.formatPrice(baseTotal) + " " + this.config.currency,
            );
            if ($("#cart-child-value").length) {
                var baseChildTotal = children * childPrice;
                $("#cart-child-value").text(
                    this.formatPrice(baseChildTotal) +
                        " " +
                        this.config.currency,
                );
            }

            // Get and display activities
            var activities = this.getAddedActivities();
            var $activitiesList = $("#cart-activities-list");
            var $activitiesWrapper = $("#cart-activities-wrapper");
            $activitiesList.empty();

            if (activities.length > 0) {
                $activitiesWrapper.show();
                var totalActivities = 0;
                activities.forEach(
                    function (act) {
                        totalActivities += act.price;
                        var $item = $('<li class="ajtb-activity-row"></li>');
                        $item.append(
                            '<span class="ajtb-activity-name">' +
                                this.escapeHtml(act.title) +
                                "</span>",
                        );
                        $item.append(
                            '<span class="ajtb-activity-price">' +
                                this.formatPrice(act.price) +
                                " " +
                                this.config.currency +
                                "</span>",
                        );
                        $activitiesList.append($item);
                    }.bind(this),
                );
            } else {
                $activitiesWrapper.hide();
            }
        },

        /**
         * Escape HTML to prevent XSS
         */
        escapeHtml: function (text) {
            if (text == null) return "";
            var s = String(text);
            var map = {
                "&": "&amp;",
                "<": "&lt;",
                ">": "&gt;",
                '"': "&quot;",
                "'": "&#039;",
            };
            return s.replace(/[&<>"']/g, function (m) {
                return map[m];
            });
        },

        /**
         * Calculate total from quantities + activities
         */
        calculateTotal: function () {
            var adults = parseInt($("#adults").val(), 10) || 0;
            var children = parseInt($("#children").val(), 10) || 0;

            var adultPrice = this.config.prices.adult || 0;
            var childPrice =
                this.config.prices.child !== undefined &&
                this.config.prices.child !== null
                    ? this.config.prices.child
                    : 0;

            var baseTotal = adults * adultPrice + children * childPrice;

            // Add activities prices (only non-included, client-added)
            var activities = this.getAddedActivities();
            var activitiesTotal = 0;
            activities.forEach(function (act) {
                activitiesTotal += act.price;
            });

            var total = baseTotal + activitiesTotal;

            // Update cart display
            this.updateCart();

            // Update total
            $("#booking-total").text(
                this.formatPrice(total) + " " + this.config.currency,
            );
        },

        /**
         * Itinerary accordion
         */
        initItineraryAccordion: function () {
            // Day toggle
            $(document).on("click", ".day-header", function () {
                var $dayCard = $(this).closest(".day-card");
                var $dayBody = $dayCard.find(".day-body");
                var $toggle = $(this).find(".day-toggle");
                var isExpanded = $toggle.attr("aria-expanded") === "true";

                if (isExpanded) {
                    $dayBody.slideUp(200);
                    $toggle.attr("aria-expanded", "false");
                } else {
                    $dayBody.slideDown(200);
                    $toggle.attr("aria-expanded", "true");
                }
            });

            // Expand all button
            $(document).on("click", "#expand-all-days", function () {
                var $btn = $(this);
                var allExpanded =
                    $('.day-toggle[aria-expanded="false"]').length === 0;

                if (allExpanded) {
                    // Collapse all
                    $(".day-body").slideUp(200);
                    $(".day-toggle").attr("aria-expanded", "false");
                    $btn.html(
                        '<svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="2"><polyline points="15,3 21,3 21,9"></polyline><polyline points="9,21 3,21 3,15"></polyline><line x1="21" y1="3" x2="14" y2="10"></line><line x1="3" y1="21" x2="10" y2="14"></line></svg> Tout déplier',
                    );
                } else {
                    // Expand all
                    $(".day-body").slideDown(200);
                    $(".day-toggle").attr("aria-expanded", "true");
                    $btn.html(
                        '<svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="2"><polyline points="4,14 10,14 10,20"></polyline><polyline points="20,10 14,10 14,4"></polyline><line x1="14" y1="10" x2="21" y2="3"></line><line x1="3" y1="21" x2="10" y2="14"></line></svg> Tout réduire',
                    );
                }
            });

            // Lire plus: expand long notes
            $(document).on("click", ".aj-day-notes-read-more", function () {
                var $wrap = $(this).closest(".aj-day-notes-wrap");
                $wrap
                    .addClass("aj-day-notes-expanded")
                    .removeClass("aj-day-notes-collapsed");
                $(this).attr("aria-expanded", "true");
            });
        },

        /**
         * Activity toggle (add/remove) — event delegation + replace container HTML from server
         */
        initActivityToggle: function () {
            var self = this;
            var ajtbData =
                typeof window.ajtbData !== "undefined" ? window.ajtbData : {};
            var ajaxUrl = ajtbData.ajax_url || ajtbData.ajaxUrl || "";
            var nonce = ajtbData.nonce || "";
            var $section = $("#itinerary");
            var tourId = $section.length
                ? $section.data("tour-id") ||
                  ajtbData.tour_id ||
                  ajtbData.postId
                : ajtbData.tour_id || ajtbData.postId;
            var sessionToken = $section.length
                ? $section.data("session-token")
                : "";

            if (!ajaxUrl || !nonce) {
                console.warn(
                    "AJTB initActivityToggle: missing ajax_url or nonce",
                    { ajaxUrl: !!ajaxUrl, nonce: !!nonce },
                );
                return;
            }

            // Event delegation: one listener for all [data-aj-action] buttons (including dynamically inserted)
            document.addEventListener("click", function (e) {
                var btn =
                    e.target && e.target.closest
                        ? e.target.closest("[data-aj-action]")
                        : null;
                if (!btn) return;
                var action = btn.getAttribute("data-aj-action");
                if (action !== "remove" && action !== "add") return;

                var tourIdVal =
                    parseInt(btn.getAttribute("data-tour-id"), 10) || tourId;
                var dayIdVal = parseInt(btn.getAttribute("data-day-id"), 10);
                if (!dayIdVal) return;

                var activityIdVal = 0;
                var $activityItem = null;

                if (action === "remove") {
                    activityIdVal = parseInt(
                        btn.getAttribute("data-activity-id"),
                        10,
                    );
                    // Find the parent activity item for instant removal
                    $activityItem = $(btn).closest(".day-activity-item");
                } else {
                    var selectId = btn.getAttribute("data-select-id");
                    var selectEl = selectId
                        ? document.getElementById(selectId)
                        : null;
                    if (!selectEl) return;
                    activityIdVal = parseInt(selectEl.value, 10);
                }
                if (!activityIdVal) {
                    if (action === "add")
                        AJTB.showToast("Choisissez une activité");
                    return;
                }

                if (btn.disabled) return;
                btn.disabled = true;

                // INSTANT FEEDBACK: Remove activity item immediately (for "remove" action)
                var itemHtml = null;
                var container = document.getElementById(
                    "aj-day-activities-" + dayIdVal,
                );
                var $list = container
                    ? $(container).find(".day-activities-list")
                    : null;

                if (
                    action === "remove" &&
                    $activityItem &&
                    $activityItem.length
                ) {
                    itemHtml = $activityItem[0].outerHTML; // Store for potential restore
                    $activityItem.fadeOut(150, function () {
                        $(this).remove();
                    });
                }

                var payload = {
                    action: "aj_toggle_activity",
                    nonce: nonce,
                    tour_id: tourIdVal,
                    day_id: dayIdVal,
                    activity_id: activityIdVal,
                    toggle_action: action === "remove" ? "removed" : "added",
                    session_token: sessionToken,
                };
                console.log("AJ TB payload", payload);

                // AJAX call in background
                $.post(ajaxUrl, payload)
                    .done(function (resp) {
                        console.log("AJ TB response", resp);
                        if (
                            resp.success &&
                            resp.data &&
                            resp.data.html !== undefined
                        ) {
                            if (container) {
                                container.innerHTML = resp.data.html;
                            }
                            AJTB.showToast(
                                resp.data.message ||
                                    (action === "remove"
                                        ? "Activité retirée"
                                        : "Activité ajoutée"),
                            );
                            // Recalculate total with activities
                            if (typeof AJTB.calculateTotal === "function") {
                                AJTB.calculateTotal();
                            }
                        } else {
                            // On error: restore item if it was removed
                            if (
                                action === "remove" &&
                                itemHtml &&
                                $list &&
                                $list.length
                            ) {
                                $list.prepend(itemHtml);
                                $list
                                    .find(".day-activity-item")
                                    .first()
                                    .hide()
                                    .fadeIn(150);
                            }
                            var msg =
                                resp.data && resp.data.message
                                    ? resp.data.message
                                    : "Erreur";
                            AJTB.showToast(msg);
                            btn.disabled = false;
                        }
                    })
                    .fail(function (xhr, status, err) {
                        console.warn("AJ TB request failed", status, err);
                        // On error: restore item if it was removed
                        if (
                            action === "remove" &&
                            itemHtml &&
                            $list &&
                            $list.length
                        ) {
                            $list.prepend(itemHtml);
                            $list
                                .find(".day-activity-item")
                                .first()
                                .hide()
                                .fadeIn(150);
                        }
                        AJTB.showToast("Erreur réseau");
                        btn.disabled = false;
                    });
            });
        },

        /**
         * Activity Modal (reconstruit): Choisir une activité
         */
        initActivityModal: function () {
            var self = this;
            var $modal = $("#ajtb-activity-modal");
            if (!$modal.length) return;

            var currentTourId = 0;
            var currentDayId = 0;
            var currentPage = 1;
            var currentSearch = "";
            var searchTimeout = null;
            var isLoading = false;

            $(document).on("click", ".ajtb-btn-open-activity-modal", function (e) {
                e.preventDefault();
                currentTourId = parseInt($(this).attr("data-tour-id"), 10) || 0;
                currentDayId = parseInt($(this).attr("data-day-id"), 10) || 0;
                if (!currentTourId || !currentDayId) {
                    self.showToast("Paramètres manquants");
                    return;
                }
                $modal.attr("aria-hidden", "false").addClass("is-open");
                currentPage = 1;
                currentSearch = "";
                $("#ajtb-actmod-search").val("");
                self.loadActivitiesModal();
            });

            $(document).on("click", "[data-ajtb-modal-close]", function (e) {
                e.preventDefault();
                if ($(this).closest("#ajtb-activity-modal").length) {
                    $modal.attr("aria-hidden", "true").removeClass("is-open");
                }
            });

            $("#ajtb-actmod-search").on("input", function () {
                var search = $(this).val().trim();
                if (searchTimeout) clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function () {
                    currentSearch = search;
                    currentPage = 1;
                    self.loadActivitiesModal();
                }, 300);
            });

            this.loadActivitiesModal = function () {
                if (isLoading || !currentTourId || !currentDayId) return;
                isLoading = true;
                var $list = $("#ajtb-actmod-list");
                var $loading = $("#ajtb-actmod-loading");
                var $empty = $("#ajtb-actmod-empty");
                var $pagination = $("#ajtb-actmod-pagination");

                $list.hide().empty();
                $empty.attr("hidden", true);
                $pagination.attr("hidden", true);
                $loading.attr("hidden", false);

                var ajaxUrl = (typeof ajtbData !== "undefined" && ajtbData.ajax_url) ? ajtbData.ajax_url : ajaxurl;
                $.post(ajaxUrl, {
                    action: "aj_get_activities_modal",
                    tour_id: currentTourId,
                    day_id: currentDayId,
                    search: currentSearch,
                    page: currentPage,
                    per_page: 12,
                })
                    .done(function (resp) {
                        $loading.attr("hidden", true);
                        if (resp.success && resp.data && resp.data.items && resp.data.items.length > 0) {
                            $empty.attr("hidden", true);
                            var items = resp.data.items;
                            var tourId = currentTourId;
                            var dayId = currentDayId;
                            items.forEach(function (item) {
                                var $row = $("<div class=\"ajtb-actmod-row\"></div>").attr("data-activity-id", item.id);
                                var $media = $("<div class=\"ajtb-actmod-row-media\"></div>");
                                if (item.image_url) {
                                    $media.append(
                                        $("<img class=\"ajtb-actmod-row-img\">").attr({
                                            src: item.image_url,
                                            alt: item.title || "",
                                            loading: "lazy",
                                        }),
                                    );
                                } else {
                                    $media.append("<div class=\"ajtb-actmod-row-placeholder\">—</div>");
                                }

                                var $content = $("<div class=\"ajtb-actmod-row-content\"></div>");
                                $content.append(
                                    $("<h3 class=\"ajtb-actmod-row-title\"></h3>").text(item.title || ""),
                                );

                                var metaParts = [];
                                if (item.duration_minutes) metaParts.push(item.duration_minutes + " min");
                                if (item.location_text) metaParts.push(item.location_text || "");
                                if (item.base_price != null && item.base_price !== undefined) {
                                    metaParts.push(self.formatPrice(item.base_price) + " " + (self.config.currency || "DH"));
                                }
                                if (metaParts.length) {
                                    $content.append(
                                        $("<div class=\"ajtb-actmod-row-meta\"></div>").text(metaParts.join(" · ")),
                                    );
                                }

                                if (item.description) {
                                    var d =
                                        (item.description || "").length > 120
                                            ? (item.description || "").substring(0, 120) + "…"
                                            : (item.description || "");
                                    $content.append(
                                        $("<p class=\"ajtb-actmod-row-desc\"></p>").text(d),
                                    );
                                }

                                var $actions = $("<div class=\"ajtb-actmod-row-actions\"></div>");
                                var $btn = $("<button type=\"button\" class=\"ajtb-actmod-btn-add\">Ajouter</button>");
                                $btn.attr({
                                    "data-activity-id": item.id,
                                    "data-tour-id": tourId,
                                    "data-day-id": dayId,
                                });
                                $actions.append($btn);

                                $row.append($media).append($content).append($actions);
                                $list.append($row);
                            });
                            $list.show();
                            if (resp.data.total_pages > 1) {
                                var pg = resp.data.page;
                                var tot = resp.data.total_pages;
                                var h = "<span>Page " + pg + " / " + tot + "</span>";
                                if (pg > 1) h = "<button type=\"button\" class=\"ajtb-actmod-pag-prev\" data-page=\"" + (pg - 1) + "\">←</button>" + h;
                                if (pg < tot) h = h + "<button type=\"button\" class=\"ajtb-actmod-pag-next\" data-page=\"" + (pg + 1) + "\">→</button>";
                                $pagination.html(h).attr("hidden", false);
                            }
                        } else {
                            $empty.attr("hidden", false);
                            $list.hide();
                        }
                    })
                    .fail(function () {
                        $loading.attr("hidden", true);
                        $empty.attr("hidden", false);
                        $list.hide();
                        self.showToast("Erreur lors du chargement");
                    })
                    .always(function () {
                        isLoading = false;
                    });
            };

            $(document).on("click", ".ajtb-actmod-pag-prev, .ajtb-actmod-pag-next", function () {
                var p = parseInt($(this).attr("data-page"), 10);
                if (p > 0) {
                    currentPage = p;
                    self.loadActivitiesModal();
                    $modal.find(".ajtb-actmod-body").scrollTop(0);
                }
            });

            $(document).on("click", ".ajtb-actmod-btn-add", function (e) {
                e.preventDefault();
                var $btn = $(this);
                var activityId = parseInt($btn.attr("data-activity-id"), 10);
                var tourId = parseInt($btn.attr("data-tour-id"), 10);
                var dayId = parseInt($btn.attr("data-day-id"), 10);
                if (!activityId || !tourId || !dayId) return;
                if ($btn.prop("disabled")) return;

                var $card = $btn.closest(".ajtb-actmod-row");
                $btn.prop("disabled", true).text("Ajout…");
                $card.fadeOut(180, function () {
                    $(this).remove();
                    if ($("#ajtb-actmod-list").children().length === 0) {
                        $("#ajtb-actmod-empty").attr("hidden", false);
                        $("#ajtb-actmod-list").hide();
                    }
                });

                var ajaxUrl = (typeof ajtbData !== "undefined" && ajtbData.ajax_url) ? ajtbData.ajax_url : ajaxurl;
                $.post(ajaxUrl, {
                    action: "aj_toggle_activity",
                    nonce: (typeof ajtbData !== "undefined" && ajtbData.nonce) ? ajtbData.nonce : "",
                    tour_id: tourId,
                    day_id: dayId,
                    activity_id: activityId,
                    toggle_action: "added",
                    session_token: (typeof ajtbData !== "undefined" && ajtbData.session_token) ? ajtbData.session_token : "",
                })
                    .done(function (resp) {
                        if (resp.success && resp.data && resp.data.html !== undefined) {
                            var el = document.getElementById("aj-day-activities-" + dayId);
                            if (el) el.innerHTML = resp.data.html;
                            self.showToast(resp.data.message || "Activité ajoutée");
                            if (typeof self.calculateTotal === "function") self.calculateTotal();
                            setTimeout(function () {
                                $modal.attr("aria-hidden", "true").removeClass("is-open");
                            }, 280);
                        } else {
                            self.showToast((resp.data && resp.data.message) ? resp.data.message : "Erreur");
                            $btn.prop("disabled", false).text("Ajouter");
                        }
                    })
                    .fail(function () {
                        self.showToast("Erreur réseau");
                        $btn.prop("disabled", false).text("Ajouter");
                    });
            });
        },

        /**
         * Activity Edit Modal: open edit modal, populate form, update activity
         */
        initActivityEditModal: function () {
            var self = this;
            var $modal = $("#ajtb-activity-edit-modal");
            if (!$modal.length) return;

            // Open edit modal
            $(document).on("click", ".ajtb-btn-edit-activity", function (e) {
                e.preventDefault();
                var $btn = $(this);
                var dayActivityId = parseInt(
                    $btn.attr("data-day-activity-id"),
                    10,
                );
                var tourId = parseInt($btn.attr("data-tour-id"), 10);
                var dayId = parseInt($btn.attr("data-day-id"), 10);
                var activityId = parseInt($btn.attr("data-activity-id"), 10);

                if (!dayActivityId || !tourId || !dayId || !activityId) return;

                // Get current activity data from DOM
                var $item = $btn.closest(".day-activity-item");
                var currentTitle = $item.find(".activity-title").text().trim();
                var currentDesc =
                    $item.find(".activity-description").html() || "";
                var currentPrice =
                    $item
                        .find(".activity-price")
                        .text()
                        .trim()
                        .replace(/[^\d.]/g, "") || "";
                var startTime =
                    $item.find(".activity-time span").first().text().trim() ||
                    "";
                var endTime =
                    $item.find(".activity-time span").last().text().trim() ||
                    "";

                // Populate form
                $("#ajtb-edit-day-activity-id").val(dayActivityId);
                $("#ajtb-edit-tour-id").val(tourId);
                $("#ajtb-edit-day-id").val(dayId);
                $("#ajtb-edit-activity-id").val(activityId);
                $("#ajtb-edit-custom-title").val(currentTitle);
                $("#ajtb-edit-custom-description").val(
                    currentDesc.replace(/<[^>]*>/g, ""),
                );
                $("#ajtb-edit-custom-price").val(currentPrice);
                $("#ajtb-edit-start-time").val(startTime);
                $("#ajtb-edit-end-time").val(endTime);

                // Open modal
                $modal.attr("aria-hidden", "false").addClass("is-open");
            });

            // Close modal
            $(document).on(
                "click",
                "#ajtb-activity-edit-modal [data-ajtb-modal-close]",
                function (e) {
                    e.preventDefault();
                    $modal.attr("aria-hidden", "true").removeClass("is-open");
                },
            );

            // Submit form
            $("#ajtb-activity-edit-form").on("submit", function (e) {
                e.preventDefault();
                var $form = $(this);
                var $submitBtn = $form.find('button[type="submit"]');

                if ($submitBtn.prop("disabled")) return;
                $submitBtn.prop("disabled", true).text("Enregistrement...");

                var ajaxUrl =
                    typeof ajtbData !== "undefined" && ajtbData.ajax_url
                        ? ajtbData.ajax_url
                        : ajaxurl;
                var sessionToken =
                    typeof ajtbData !== "undefined" && ajtbData.session_token
                        ? ajtbData.session_token
                        : "";

                $.post(ajaxUrl, {
                    action: "aj_update_activity",
                    day_activity_id: $("#ajtb-edit-day-activity-id").val(),
                    tour_id: $("#ajtb-edit-tour-id").val(),
                    day_id: $("#ajtb-edit-day-id").val(),
                    activity_id: $("#ajtb-edit-activity-id").val(),
                    custom_title: $("#ajtb-edit-custom-title").val(),
                    custom_description: $(
                        "#ajtb-edit-custom-description",
                    ).val(),
                    custom_price: $("#ajtb-edit-custom-price").val(),
                    start_time: $("#ajtb-edit-start-time").val(),
                    end_time: $("#ajtb-edit-end-time").val(),
                    session_token: sessionToken,
                })
                    .done(function (resp) {
                        if (
                            resp.success &&
                            resp.data &&
                            resp.data.html !== undefined
                        ) {
                            var container = document.getElementById(
                                "aj-day-activities-" + resp.data.day_id,
                            );
                            if (container) {
                                container.innerHTML = resp.data.html;
                            }
                            self.showToast(
                                resp.data.message || "Activité mise à jour",
                            );
                            $modal
                                .attr("aria-hidden", "true")
                                .removeClass("is-open");
                        } else {
                            var msg =
                                resp.data && resp.data.message
                                    ? resp.data.message
                                    : "Erreur";
                            self.showToast(msg);
                            $submitBtn
                                .prop("disabled", false)
                                .text("Enregistrer");
                        }
                    })
                    .fail(function () {
                        self.showToast("Erreur réseau");
                        $submitBtn.prop("disabled", false).text("Enregistrer");
                    });
            });
        },

        /**
         * Flight toggle (add/remove) — event delegation, replace #ajtb-flights-container with response HTML
         */
        initFlightToggle: function () {
            var ajtbData =
                typeof window.ajtbData !== "undefined" ? window.ajtbData : {};
            var ajaxUrl = ajtbData.ajax_url || ajtbData.ajaxUrl || "";
            var flightNonce = ajtbData.flight_nonce || "";
            var sessionToken = ajtbData.session_token || "";
            var tourId = ajtbData.tour_id || ajtbData.postId || 0;

            if (!ajaxUrl || !flightNonce) return;

            document.addEventListener("click", function (e) {
                var btn =
                    e.target && e.target.closest
                        ? e.target.closest(".ajtb-btn-remove-flight") ||
                          e.target.closest(".ajtb-btn-add-flight")
                        : null;
                if (!btn) return;

                var tourIdVal =
                    parseInt(btn.getAttribute("data-tour-id"), 10) || tourId;
                var flightIdVal = parseInt(
                    btn.getAttribute("data-flight-id"),
                    10,
                );
                var toggleAction = btn.getAttribute("data-toggle-action");
                if (!flightIdVal || !toggleAction) return;

                if (btn.disabled) return;
                btn.disabled = true;

                $.post(ajaxUrl, {
                    action: "ajtb_toggle_flight",
                    nonce: flightNonce,
                    tour_id: tourIdVal,
                    flight_id: flightIdVal,
                    toggle_action: toggleAction,
                    session_token: sessionToken,
                })
                    .done(function (resp) {
                        if (
                            resp.success &&
                            resp.data &&
                            resp.data.html !== undefined
                        ) {
                            var container = document.getElementById(
                                "ajtb-flights-container",
                            );
                            if (container) {
                                container.innerHTML = resp.data.html;
                            }
                            AJTB.showToast(
                                toggleAction === "removed"
                                    ? "Vol retiré"
                                    : "Vol ajouté",
                            );
                        } else {
                            AJTB.showToast(
                                resp.data && resp.data.message
                                    ? resp.data.message
                                    : "Erreur",
                            );
                        }
                    })
                    .fail(function () {
                        AJTB.showToast("Erreur réseau");
                    })
                    .always(function () {
                        btn.disabled = false;
                    });
            });
        },

        /**
         * FAQ accordion
         */
        initFAQAccordion: function () {
            $(document).on("click", ".faq-question", function () {
                var $item = $(this).closest(".faq-item");
                var $answer = $item.find(".faq-answer");
                var isActive = $item.hasClass("active");

                // Close all others
                $(".faq-item").removeClass("active");
                $(".faq-answer").css("max-height", "0");
                $(".faq-question").attr("aria-expanded", "false");

                // Toggle current
                if (!isActive) {
                    $item.addClass("active");
                    $answer.css(
                        "max-height",
                        $answer.get(0).scrollHeight + "px",
                    );
                    $(this).attr("aria-expanded", "true");
                }
            });
        },

        /**
         * Gallery lightbox
         */
        initGallery: function () {
            $(document).on("click", "[data-lightbox]", function (e) {
                e.preventDefault();

                var $this = $(this);
                var group = $this.data("lightbox");
                var $items = $('[data-lightbox="' + group + '"]');
                var index = $items.index($this);

                AJTB.openLightbox($items, index);
            });
        },

        /**
         * Open lightbox
         */
        openLightbox: function ($items, startIndex) {
            var currentIndex = startIndex;
            var images = [];

            $items.each(function () {
                images.push({
                    src: $(this).attr("href"),
                    alt: $(this).find("img").attr("alt") || "",
                });
            });

            // Create lightbox
            var $lightbox = $(
                '<div class="ajtb-lightbox">' +
                    '<div class="lightbox-backdrop"></div>' +
                    '<div class="lightbox-container">' +
                    '<button class="lightbox-close" aria-label="Fermer">&times;</button>' +
                    '<button class="lightbox-nav prev" aria-label="Précédent">&lsaquo;</button>' +
                    '<div class="lightbox-content"><img src="" alt=""></div>' +
                    '<button class="lightbox-nav next" aria-label="Suivant">&rsaquo;</button>' +
                    '<div class="lightbox-counter"></div>' +
                    "</div>" +
                    "</div>",
            );

            // Styles
            $lightbox.css({
                position: "fixed",
                inset: 0,
                zIndex: 99999,
                display: "flex",
                alignItems: "center",
                justifyContent: "center",
            });

            $lightbox.find(".lightbox-backdrop").css({
                position: "absolute",
                inset: 0,
                background: "rgba(0,0,0,0.92)",
            });

            $lightbox.find(".lightbox-container").css({
                position: "relative",
                maxWidth: "90%",
                maxHeight: "90%",
                display: "flex",
                alignItems: "center",
            });

            $lightbox.find(".lightbox-content img").css({
                maxWidth: "100%",
                maxHeight: "85vh",
                display: "block",
                borderRadius: "8px",
            });

            var btnStyle = {
                position: "absolute",
                background: "rgba(255,255,255,0.15)",
                color: "#fff",
                border: "none",
                cursor: "pointer",
                transition: "background 0.2s",
            };

            $lightbox.find(".lightbox-close").css(
                $.extend({}, btnStyle, {
                    top: "20px",
                    right: "20px",
                    width: "40px",
                    height: "40px",
                    borderRadius: "50%",
                    fontSize: "24px",
                    zIndex: 10,
                }),
            );

            $lightbox.find(".lightbox-nav").css(
                $.extend({}, btnStyle, {
                    top: "50%",
                    transform: "translateY(-50%)",
                    width: "50px",
                    height: "50px",
                    borderRadius: "50%",
                    fontSize: "28px",
                }),
            );

            $lightbox.find(".lightbox-nav.prev").css("left", "20px");
            $lightbox.find(".lightbox-nav.next").css("right", "20px");

            $lightbox.find(".lightbox-counter").css({
                position: "absolute",
                bottom: "20px",
                left: "50%",
                transform: "translateX(-50%)",
                color: "#fff",
                fontSize: "14px",
            });

            // Show image
            function showImage(index) {
                currentIndex = index;
                if (currentIndex < 0) currentIndex = images.length - 1;
                if (currentIndex >= images.length) currentIndex = 0;

                $lightbox
                    .find(".lightbox-content img")
                    .attr("src", images[currentIndex].src)
                    .attr("alt", images[currentIndex].alt);

                $lightbox
                    .find(".lightbox-counter")
                    .text(currentIndex + 1 + " / " + images.length);
            }

            // Events
            $lightbox
                .find(".lightbox-backdrop, .lightbox-close")
                .on("click", function () {
                    $lightbox.remove();
                    $("body").css("overflow", "");
                });

            $lightbox.find(".lightbox-nav.prev").on("click", function () {
                showImage(currentIndex - 1);
            });

            $lightbox.find(".lightbox-nav.next").on("click", function () {
                showImage(currentIndex + 1);
            });

            // Keyboard
            $(document).on("keydown.lightbox", function (e) {
                if (e.key === "Escape") {
                    $lightbox.remove();
                    $("body").css("overflow", "");
                    $(document).off("keydown.lightbox");
                } else if (e.key === "ArrowLeft") {
                    showImage(currentIndex - 1);
                } else if (e.key === "ArrowRight") {
                    showImage(currentIndex + 1);
                }
            });

            // Append and show
            $("body").append($lightbox).css("overflow", "hidden");
            showImage(startIndex);
        },

        /**
         * Hero gallery slider (mobile)
         */
        initHeroGallerySlider: function () {
            var $slider = $(".ajtb-hero-gallery-slider");
            if (!$slider.length) return;

            var $track = $slider.find(".ajtb-hero-gallery-slider-track");
            var $slides = $track.find(".ajtb-hero-gallery-slide");
            var total = $slides.length;
            if (total === 0) return;

            var $dotsContainer = $slider.find(".ajtb-hero-gallery-slider-dots");
            var i, $dot;
            for (i = 0; i < total; i++) {
                $dot = $(
                    '<button type="button" class="ajtb-hero-gallery-slider-dot" aria-label="' +
                        (i + 1) +
                        '"></button>',
                );
                $dotsContainer.append($dot);
            }
            var $dots = $dotsContainer.find(".ajtb-hero-gallery-slider-dot");
            $dots.eq(0).addClass("is-active");

            var current = 0;

            function goTo(index) {
                if (index < 0) index = total - 1;
                if (index >= total) index = 0;
                current = index;
                $track.css("transform", "translateX(-" + current * 100 + "%)");
                $dots
                    .removeClass("is-active")
                    .eq(current)
                    .addClass("is-active");
            }

            $slider
                .find(".ajtb-hero-gallery-slider-prev")
                .on("click", function () {
                    goTo(current - 1);
                });

            $slider
                .find(".ajtb-hero-gallery-slider-next")
                .on("click", function () {
                    goTo(current + 1);
                });

            $dots.on("click", function () {
                var idx = $dots.index(this);
                goTo(idx);
            });

            // Swipe
            var startX = 0,
                startY = 0;
            $slider.on("touchstart", function (e) {
                startX = e.originalEvent.touches[0].clientX;
                startY = e.originalEvent.touches[0].clientY;
            });
            $slider.on("touchend", function (e) {
                var endX = e.originalEvent.changedTouches[0].clientX;
                var endY = e.originalEvent.changedTouches[0].clientY;
                var dx = endX - startX;
                var dy = endY - startY;
                if (Math.abs(dx) > Math.abs(dy) && Math.abs(dx) > 40) {
                    if (dx > 0) goTo(current - 1);
                    else goTo(current + 1);
                }
            });
        },

        /**
         * Share button
         */
        initShareButton: function () {
            $(document).on("click", "#share-tour", function () {
                var url = $(this).data("url");
                var title = document.title;

                if (navigator.share) {
                    navigator
                        .share({
                            title: title,
                            url: url,
                        })
                        .catch(function () {});
                } else {
                    // Copy to clipboard
                    AJTB.copyToClipboard(url);
                    AJTB.showToast("Lien copié !");
                }
            });
        },

        /**
         * Save/wishlist button
         */
        initSaveButton: function () {
            var savedTours = this.getSavedTours();

            // Check if current tour is saved
            var currentTourId = $("#save-tour").data("tour-id");
            if (
                currentTourId &&
                savedTours.indexOf(currentTourId.toString()) !== -1
            ) {
                $("#save-tour").addClass("active");
            }

            $(document).on("click", "#save-tour", function () {
                var $btn = $(this);
                var tourId = $btn.data("tour-id").toString();
                var savedTours = AJTB.getSavedTours();

                if ($btn.hasClass("active")) {
                    // Remove
                    $btn.removeClass("active");
                    savedTours = savedTours.filter(function (id) {
                        return id !== tourId;
                    });
                    AJTB.showToast("Retiré des favoris");
                } else {
                    // Add
                    $btn.addClass("active");
                    if (savedTours.indexOf(tourId) === -1) {
                        savedTours.push(tourId);
                    }
                    AJTB.showToast("Ajouté aux favoris !");
                }

                AJTB.saveTours(savedTours);
            });
        },

        /**
         * Get saved tours from localStorage
         */
        getSavedTours: function () {
            try {
                return (
                    JSON.parse(localStorage.getItem("ajtb_saved_tours")) || []
                );
            } catch (e) {
                return [];
            }
        },

        /**
         * Save tours to localStorage
         */
        saveTours: function (tours) {
            try {
                localStorage.setItem("ajtb_saved_tours", JSON.stringify(tours));
            } catch (e) {}
        },

        /**
         * Copy to clipboard
         */
        copyToClipboard: function (text) {
            var $temp = $("<input>");
            $("body").append($temp);
            $temp.val(text).select();
            document.execCommand("copy");
            $temp.remove();
        },

        /**
         * Show toast notification
         */
        showToast: function (message) {
            var $toast = $('<div class="ajtb-toast">' + message + "</div>");
            $toast.css({
                position: "fixed",
                bottom: "30px",
                left: "50%",
                transform: "translateX(-50%) translateY(20px)",
                padding: "12px 24px",
                background: "#1a1a1a",
                color: "#fff",
                borderRadius: "8px",
                fontSize: "14px",
                fontWeight: "500",
                zIndex: 99999,
                opacity: 0,
                transition: "all 0.3s ease",
            });

            $("body").append($toast);

            setTimeout(function () {
                $toast.css({
                    opacity: 1,
                    transform: "translateX(-50%) translateY(0)",
                });
            }, 10);

            setTimeout(function () {
                $toast.css({
                    opacity: 0,
                    transform: "translateX(-50%) translateY(20px)",
                });
                setTimeout(function () {
                    $toast.remove();
                }, 300);
            }, 2500);
        },

        /**
         * Smooth scroll
         */
        initSmoothScroll: function () {
            $(document).on(
                "click",
                'a[href^="#"]:not([data-lightbox])',
                function (e) {
                    var target = $(this.getAttribute("href"));
                    if (target.length) {
                        e.preventDefault();
                        $("html, body").animate(
                            {
                                scrollTop: target.offset().top - 80,
                            },
                            500,
                        );
                    }
                },
            );

            // Scroll down from hero
            $(document).on("click", ".ajtb-hero-scroll", function () {
                var $content = $(".ajtb-tour-layout");
                if ($content.length) {
                    $("html, body").animate(
                        {
                            scrollTop: $content.offset().top - 20,
                        },
                        500,
                    );
                }
            });
        },

        /**
         * Sticky nav highlight on scroll
         */
        initStickyNav: function () {
            var $tabs = $(".ajtb-tabs-nav");
            if (!$tabs.length) return;

            var sections = [];
            $tabs.find(".tab-link").each(function () {
                var href = $(this).attr("href");
                if ($(href).length) {
                    sections.push({
                        link: $(this),
                        section: $(href),
                    });
                }
            });

            $(window).on("scroll", function () {
                var scrollPos = $(window).scrollTop() + 100;

                sections.forEach(function (item) {
                    var $section = item.section;
                    if ($section.hasClass("ajtb-tab-panel-hidden") || !$section.is(":visible")) return;
                    var sectionTop = $section.offset().top;
                    var sectionBottom = sectionTop + $section.outerHeight();

                    if (scrollPos >= sectionTop && scrollPos < sectionBottom) {
                        $tabs.find(".tab-link").removeClass("active");
                        item.link.addClass("active");
                    }
                });
            });
        },

        /**
         * Long content blocks: collapse and reveal with "Voir plus".
         */
        initExpandableContent: function () {
            var selectors = [
                ".ajtb-overview-content",
                ".description-content",
                ".ajtb-policy-content",
            ];
            var threshold = 260;

            $(selectors.join(", ")).each(function () {
                var $block = $(this);
                if ($block.data("ajtbExpandableReady")) return;

                var el = $block.get(0);
                if (!el) return;

                var fullHeight = el.scrollHeight || 0;
                if (fullHeight <= threshold) return;

                $block
                    .addClass("ajtb-expandable-content is-collapsed")
                    .css("--ajtb-expandable-max-height", threshold + "px")
                    .css("--ajtb-expandable-full-height", fullHeight + "px")
                    .attr("data-ajtb-expanded", "false")
                    .data("ajtbExpandableReady", true);

                var $button = $(
                    '<button type="button" class="ajtb-expandable-toggle" aria-expanded="false">Voir plus</button>',
                );

                $button.on("click", function () {
                    var expanded =
                        $block.attr("data-ajtb-expanded") === "true";

                    if (expanded) {
                        $block
                            .removeClass("is-expanded")
                            .addClass("is-collapsed")
                            .attr("data-ajtb-expanded", "false");
                        $(this)
                            .text("Voir plus")
                            .attr("aria-expanded", "false");
                    } else {
                        $block
                            .removeClass("is-collapsed")
                            .addClass("is-expanded")
                            .attr("data-ajtb-expanded", "true");
                        $(this)
                            .text("Voir moins")
                            .attr("aria-expanded", "true");
                    }
                });

                $block.after($button);
            });
        },

        /**
         * Search bar: 3 blocks, localStorage (start_from, travel_date, adults, children), guests panel with Apply, sync total
         */
        initSearchbar: function () {
            var self = this;
            var storageKey = "aj_tb_search";
            var cookieName = "aj_tb_search";
            var cookieDays = 30;

            function getStored() {
                try {
                    var tourId = $("#aj-searchbar").data("tour-id");
                    var key = tourId ? storageKey + "_" + tourId : storageKey;
                    var raw = localStorage.getItem(key);
                    if (raw) {
                        var parsed = JSON.parse(raw);
                        if (parsed && typeof parsed === "object") {
                            return {
                                start_from:
                                    parsed.start_from !== undefined
                                        ? parsed.start_from
                                        : parsed.starting_from,
                                travel_date:
                                    parsed.travel_date !== undefined
                                        ? parsed.travel_date
                                        : parsed.travelling_on,
                                adults: parsed.adults,
                                children: parsed.children,
                            };
                        }
                    }
                } catch (e) {}
                var match = document.cookie.match(
                    new RegExp("(^| )" + cookieName + "=([^;]+)"),
                );
                if (match) {
                    try {
                        var parsed = JSON.parse(decodeURIComponent(match[2]));
                        if (parsed && typeof parsed === "object") {
                            return {
                                start_from:
                                    parsed.start_from !== undefined
                                        ? parsed.start_from
                                        : parsed.starting_from,
                                travel_date:
                                    parsed.travel_date !== undefined
                                        ? parsed.travel_date
                                        : parsed.travelling_on,
                                adults: parsed.adults,
                                children: parsed.children,
                            };
                        }
                    } catch (e) {}
                }
                return {};
            }

            function setStored(data) {
                var payload = {
                    start_from:
                        data.start_from !== undefined ? data.start_from : "",
                    travel_date:
                        data.travel_date !== undefined ? data.travel_date : "",
                    adults: typeof data.adults === "number" ? data.adults : 2,
                    children:
                        typeof data.children === "number" ? data.children : 0,
                };
                try {
                    var tourId = $("#aj-searchbar").data("tour-id");
                    var key = tourId ? storageKey + "_" + tourId : storageKey;
                    localStorage.setItem(key, JSON.stringify(payload));
                } catch (e) {}
                var d = new Date();
                d.setTime(d.getTime() + cookieDays * 24 * 60 * 60 * 1000);
                document.cookie =
                    cookieName +
                    "=" +
                    encodeURIComponent(JSON.stringify(payload)) +
                    ";path=/;expires=" +
                    d.toUTCString() +
                    ";SameSite=Lax";
            }

            function getSearchbarState() {
                var $bar = $("#aj-searchbar");
                if (!$bar.length) return {};
                var dateVal = ($bar.find("#aj-search-date").val() || "").trim();
                var adults =
                    parseInt($bar.find("#aj-panel-adults").text(), 10) || 0;
                var children =
                    parseInt($bar.find("#aj-panel-children").text(), 10) || 0;
                var from = ($bar.find("#aj-search-from").val() || "").trim();
                return {
                    start_from: from,
                    travel_date: dateVal,
                    adults: adults,
                    children: children,
                };
            }

            function getValidStartFromOptions() {
                var $from = $("#aj-searchbar #aj-search-from");
                if (!$from.length) return [];
                var list = [];
                $from.find("option").each(function () {
                    var v = ($(this).val() || "").toString().trim();
                    if (v !== "") list.push(v);
                });
                return list;
            }

            function setSearchbarDisplay(state) {
                var $bar = $("#aj-searchbar");
                if (!$bar.length) return;
                var $from = $bar.find("#aj-search-from");
                var $dateInput = $bar.find("#aj-search-date");
                var $dateDisplay = $bar.find("#aj-search-date-display");
                var $panelAdults = $bar.find("#aj-panel-adults");
                var $panelChildren = $bar.find("#aj-panel-children");
                if (state.start_from !== undefined && $from.length) {
                    var wantedFrom = String(state.start_from).trim();
                    $from.val(wantedFrom);
                    if (
                        ($from.val() || "").toString().trim() !== wantedFrom &&
                        wantedFrom !== ""
                    ) {
                        var lowerWanted = wantedFrom.toLowerCase();
                        var mappedValue = "";
                        $from.find("option").each(function () {
                            if (mappedValue !== "") return;
                            var $opt = $(this);
                            var optionValue = ($opt.val() || "")
                                .toString()
                                .trim();
                            var optionId = (
                                ($opt.data("id") || "") + ""
                            ).trim();
                            var optionCode = (
                                ($opt.data("code") || "") + ""
                            ).trim();
                            var optionLabel = ($opt.text() || "")
                                .replace(/\s+/g, " ")
                                .trim();
                            var optionName = optionLabel
                                .replace(/\s*\([^)]*\)\s*$/, "")
                                .trim();
                            if (
                                optionValue === wantedFrom ||
                                optionId === wantedFrom ||
                                optionCode.toLowerCase() === lowerWanted ||
                                optionName.toLowerCase() === lowerWanted ||
                                optionLabel.toLowerCase() === lowerWanted
                            ) {
                                mappedValue = optionValue;
                            }
                        });
                        if (mappedValue !== "") $from.val(mappedValue);
                    }
                }
                if (state.travel_date) {
                    $dateInput.val(state.travel_date);
                    var parts = state.travel_date.split("-");
                    if (parts.length === 3)
                        $dateDisplay.text(
                            parts[2] + "/" + parts[1] + "/" + parts[0],
                        );
                    else $dateDisplay.text(state.travel_date);
                } else {
                    $dateInput.val("");
                    $dateDisplay.text(
                        $dateDisplay.attr("data-placeholder") || "",
                    );
                }
                if (typeof state.adults === "number") {
                    $panelAdults.text(state.adults);
                }
                if (typeof state.children === "number") {
                    $panelChildren.text(state.children);
                }
                updateGuestsSummary();
            }

            function updateGuestsSummary() {
                var a = parseInt($("#aj-panel-adults").text(), 10) || 0;
                var c = parseInt($("#aj-panel-children").text(), 10) || 0;
                var $s = $("#aj-guest-summary");
                if (!$s.length) return;
                var adultLabel = a === 1 ? "Adulte" : "Adultes";
                var text = a + " " + adultLabel;
                if (c > 0)
                    text += ", " + c + " " + (c === 1 ? "Enfant" : "Enfants");
                $s.text(text);
            }

            function syncToBookingForm(state) {
                var $date = $("#booking-date");
                var $adults = $("#adults");
                var $children = $("#children");
                if ($date.length && state.travel_date)
                    $date.val(state.travel_date);
                if ($adults.length && typeof state.adults === "number")
                    $adults.val(state.adults);
                if ($children.length && typeof state.children === "number")
                    $children.val(state.children);
                if (typeof self.calculateTotal === "function")
                    self.calculateTotal();
            }

            function closeGuestsPanel() {
                var trigger = document.getElementById("aj-guest-trigger");
                var panel = document.getElementById("aj-guests-panel");
                if (trigger) trigger.setAttribute("aria-expanded", "false");
                if (panel) panel.setAttribute("hidden", "");
            }

            // Guests trigger: open/close (data-aj-search="guests-trigger")
            $(document).on(
                "click",
                '#aj-searchbar [data-aj-search="guests-trigger"]',
                function (e) {
                    e.stopPropagation();
                    var trigger = this;
                    var panel = document.getElementById("aj-guests-panel");
                    var open = trigger.getAttribute("aria-expanded") === "true";
                    trigger.setAttribute(
                        "aria-expanded",
                        open ? "false" : "true",
                    );
                    if (panel) {
                        if (open) panel.setAttribute("hidden", "");
                        else panel.removeAttribute("hidden");
                    }
                },
            );

            $(document).on("click", function (e) {
                if (!$(e.target).closest("#aj-searchbar").length)
                    closeGuestsPanel();
            });

            // Starting from (data-aj-search="from") — mise à jour immédiate du vol affiché (real time)
            function applyStartingFromFilter() {
                var $from = $("#aj-searchbar #aj-search-from");
                if (!$from.length) return;
                var currentVal = ($from.val() || "").toString().trim();
                var state = getSearchbarState();
                setStored(state);
                if (typeof self.applyFlightsByDeparture === "function")
                    self.applyFlightsByDeparture(currentVal);
            }
            $(document).on(
                "change",
                '#aj-searchbar [data-aj-search="from"]',
                function () {
                    applyStartingFromFilter();
                },
            );
            $(document).on(
                "input",
                '#aj-searchbar [data-aj-search="from"]',
                function () {
                    applyStartingFromFilter();
                },
            );

            // Date (data-aj-search="date")
            $(document).on(
                "change",
                '#aj-searchbar [data-aj-search="date"]',
                function () {
                    var val = $(this).val() || "";
                    var state = getSearchbarState();
                    state.travel_date = val;
                    setStored(state);
                    var $display = $("#aj-search-date-display");
                    if (val) {
                        var parts = val.split("-");
                        if (parts.length === 3)
                            $display.text(
                                parts[2] + "/" + parts[1] + "/" + parts[0],
                            );
                        else $display.text(val);
                    } else {
                        $display.text($display.attr("data-placeholder") || "");
                    }
                    syncToBookingForm(state);
                },
            );

            // Panel +/- (data-aj-search="counter" / "plus" / "minus")
            $(document).on(
                "click",
                '#aj-searchbar [data-aj-search="plus"], #aj-searchbar [data-aj-search="minus"]',
                function (e) {
                    e.stopPropagation();
                    var $counter = $(this).closest(
                        '[data-aj-search="counter"]',
                    );
                    if (!$counter.length) return;
                    var target = $counter.data("target");
                    if (target !== "adults" && target !== "children") return;
                    var $num = $counter.find(".aj-counter-num");
                    var max = parseInt($counter.data("max"), 10) || 99;
                    var min = parseInt($counter.data("min"), 10);
                    if (target === "children") min = 0;
                    else if (target === "adults") min = 1;
                    var current = parseInt($num.text(), 10) || 0;
                    if ($(this).data("aj-search") === "plus") {
                        if (current < max) current++;
                    } else {
                        if (current > min) current--;
                    }
                    $num.text(current);
                },
            );

            // Apply: copy panel to state, close, persist, sync total (data-aj-search="guests-apply")
            $(document).on(
                "click",
                '#aj-searchbar [data-aj-search="guests-apply"]',
                function (e) {
                    e.stopPropagation();
                    var state = getSearchbarState();
                    setSearchbarDisplay(state);
                    setStored(state);
                    syncToBookingForm(state);
                    closeGuestsPanel();
                },
            );

            // On load: restore from storage only if stored value is still valid; otherwise keep backend selection (e.g. Fes)
            if ($("#aj-searchbar").length) {
                var saved = getStored();
                var state = getSearchbarState();
                var validStartFrom = getValidStartFromOptions();
                var currentFrom = (state.start_from || "").toString().trim();
                var savedFrom = saved.start_from !== undefined ? String(saved.start_from).trim() : "";
                if (validStartFrom.length) {
                    var currentValid = validStartFrom.indexOf(currentFrom) !== -1;
                    var savedValid = savedFrom !== "" && validStartFrom.indexOf(savedFrom) !== -1;
                    if (currentValid) {
                        // keep backend / current selection (e.g. Fes)
                    } else if (savedValid) {
                        state.start_from = savedFrom;
                    } else {
                        state.start_from = validStartFrom[0];
                    }
                }
                if (
                    (state.travel_date || "").toString().trim() === "" &&
                    saved.travel_date
                )
                    state.travel_date = saved.travel_date;
                if (typeof saved.adults === "number")
                    state.adults = saved.adults;
                if (typeof saved.children === "number")
                    state.children = saved.children;
                setSearchbarDisplay(state);
                state = getSearchbarState();
                setStored(state);
                syncToBookingForm(state);
                var $from = $("#aj-searchbar #aj-search-from");
                var selectedVal = $from.length ? ($from.val() || "").toString().trim() : "";
                if (selectedVal !== "" && typeof self.applyFlightsByDeparture === "function") {
                    self.applyFlightsByDeparture(selectedVal);
                } else if (typeof self.applyFlightsByDeparture === "function") {
                    self.applyFlightsByDeparture();
                }
            }
        },
    };

    // Expose globally
    window.AJTB = AJTB;
})(jQuery);
