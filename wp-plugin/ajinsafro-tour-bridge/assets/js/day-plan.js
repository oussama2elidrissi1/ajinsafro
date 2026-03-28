/**
 * Day Plan – Layout MakeMyTrip: tous les jours affichés; clic sur un jour = scroll vers ce jour.
 * Top Itinerary Bar: Jour actif + INCLUS + onglets (Programme / Vols / Transferts / Hôtels / Activités).
 * Left: sticky day list. Center: tous les panneaux visibles. Right: sidebar prix (page level).
 *
 * @package AjinsafroTourBridge
 */
(function ($) {
    "use strict";

    var dayIncludesData = {};
    var activeDay = "1";
    var activeTab = "programme";
    var scrollSpyLock = false;
    var currentMode = "global"; // 'global' or 'day'
    var globalActiveTab = "programme";

    function setActive(dayNum) {
        dayNum = String(dayNum);
        // Update left sidebar nav
        var $nav = $(".aj-day-plan-nav");
        if ($nav.length) {
            $nav.find("[data-aj-nav-day]")
                .removeClass("active is-active")
                .attr("aria-selected", "false");
            $nav.find('[data-aj-nav-day="' + dayNum + '"]')
                .addClass("active is-active")
                .attr("aria-selected", "true");
        }
    }

    function updateDayDetailsBar(dayNum) {
        dayNum = String(dayNum);
        var dayLabel = document.getElementById("ajtb-day-details-day-label");
        if (dayLabel) dayLabel.textContent = "Jour " + dayNum;

        // Un seul set d'onglets à droite : mettre à jour les labels avec les nombres du jour
        var includes =
            dayIncludesData[dayNum] ||
            dayIncludesData[parseInt(dayNum, 10)] ||
            {};
        var flightsCount = parseInt(includes.flights || 0, 10);
        var transfersCount = parseInt(includes.transfers || 0, 10);
        var hotelsCount = parseInt(includes.hotels || 0, 10);
        var activitiesCount = parseInt(includes.activities || 0, 10);

        var tabLabels = {
            programme: "Programme",
            flights:
                flightsCount > 0
                    ? flightsCount + " " + (flightsCount > 1 ? "Vols" : "Vol")
                    : "Vols",
            transfers:
                transfersCount > 0
                    ? transfersCount +
                      " " +
                      (transfersCount > 1 ? "Transferts" : "Transfert")
                    : "Transferts",
            hotels:
                hotelsCount > 0
                    ? hotelsCount + " " + (hotelsCount > 1 ? "Hôtels" : "Hôtel")
                    : "Hôtels",
            activities:
                activitiesCount > 0
                    ? activitiesCount +
                      " " +
                      (activitiesCount > 1 ? "Activités" : "Activité")
                    : "Activités",
        };
        $(".ajtb-day-details-bar__tabs .ajtb-tab-pill").each(function () {
            var tab = $(this).attr("data-ajtb-tab");
            if (tab && tabLabels[tab] !== undefined) {
                $(this).text(tabLabels[tab]);
            }
        });

        // Mettre à jour la ligne INCLUDED (style capture : lieu + INCLUDED: + icône + nombre + libellé)
        var inclusEl = document.getElementById("ajtb-day-details-inclus-line");
        if (inclusEl) {
            var panel = document.querySelector(
                '#itinerary .ajtb-day-content-panel[data-day="' + dayNum + '"]',
            );
            var dayTitle =
                (panel &&
                    panel.querySelector(".ajtb-day-title-mmt") &&
                    panel
                        .querySelector(".ajtb-day-title-mmt")
                        .textContent.trim()) ||
                inclusEl.getAttribute("data-day-title") ||
                "";
            var parts = [];
            if (dayTitle)
                parts.push(
                    '<span class="ajtb-inclus-location">' +
                        escapeHtml(dayTitle) +
                        "</span> ",
                );
            parts.push('<strong class="ajtb-inclus-label">INCLUDED:</strong> ');
            var items = [
                {
                    type: "transfer",
                    n: parseInt(includes.transfers || 0, 10),
                    label: "Transfert",
                    labelPlural: "Transferts",
                },
                {
                    type: "activity",
                    n: parseInt(includes.activities || 0, 10),
                    label: "Activité",
                    labelPlural: "Activités",
                },
                {
                    type: "hotel",
                    n: parseInt(includes.hotels || 0, 10),
                    label: "Hôtel",
                    labelPlural: "Hôtels",
                },
                {
                    type: "meal",
                    n: parseInt(includes.meals || 0, 10),
                    label: "Repas",
                    labelPlural: "Repas",
                },
                {
                    type: "flight",
                    n: parseInt(includes.flights || 0, 10),
                    label: "Vol",
                    labelPlural: "Vols",
                },
            ];
            items.forEach(function (it) {
                var text =
                    it.n > 0
                        ? it.n + " " + (it.n > 1 ? it.labelPlural : it.label)
                        : it.label;
                parts.push(
                    '<span class="ajtb-inclus-item ajtb-inclus-item--' +
                        it.type +
                        '"><span class="ajtb-inclus-icon" aria-hidden="true"></span><span class="ajtb-inclus-text">' +
                        escapeHtml(text) +
                        "</span></span>",
                );
            });
            inclusEl.innerHTML = parts.join("");
        }
    }

    function escapeHtml(text) {
        var div = document.createElement("div");
        div.textContent = text;
        return div.innerHTML;
    }

    function setActiveDay(dayNum) {
        dayNum = String(dayNum);
        if (activeDay === dayNum && currentMode === "day") return;
        activeDay = dayNum;
        currentMode = "day";
        setActive(dayNum);
        updateDayDetailsBar(dayNum);
        applyFilterToPanel(dayNum);
    }

    function setGlobalMode(tab, opts) {
        opts = opts || {};
        currentMode = "global";
        globalActiveTab = tab || "programme";
        // Day Details bar stays visible, just update global bar pills
        var $globalBar = $("#ajtb-global-summary-bar");
        if ($globalBar.length) {
            $globalBar.find(".ajtb-global-pill").removeClass("active");
            $globalBar
                .find('[data-ajtb-global-tab="' + globalActiveTab + '"]')
                .addClass("active");
        }
        applyGlobalFilter(globalActiveTab, opts);
    }

    function scrollToDay(dayNum) {
        dayNum = String(dayNum);
        scrollSpyLock = true;
        setActiveDay(dayNum);
        var el =
            document.getElementById("aj-day-" + dayNum) ||
            document.getElementById("aj-day-panel-" + dayNum);
        if (el) el.scrollIntoView({ behavior: "smooth", block: "start" });
        setTimeout(function () {
            scrollSpyLock = false;
        }, 800);
    }

    // formatInclus n'est plus utilisé (remplacé par les pills), mais gardé pour compatibilité si nécessaire
    function formatInclus(includes) {
        if (!includes) return "INCLUS : —";
        var parts = [];
        if (includes.flights)
            parts.push(
                includes.flights + " Vol" + (includes.flights > 1 ? "s" : ""),
            );
        if (includes.transfers)
            parts.push(
                includes.transfers +
                    " Transfert" +
                    (includes.transfers > 1 ? "s" : ""),
            );
        if (includes.hotels)
            parts.push(
                includes.hotels + " Hôtel" + (includes.hotels > 1 ? "s" : ""),
            );
        if (includes.activities)
            parts.push(
                "(+ " +
                    includes.activities +
                    " Activité" +
                    (includes.activities > 1 ? "s" : "") +
                    ")",
            );
        return parts.length ? "INCLUS : " + parts.join(" + ") : "INCLUS : —";
    }

    // Mettre à jour l'état actif des pills inclus quand l'onglet change
    function initScrollSpy() {
        var panels = document.querySelectorAll(
            "#itinerary .ajtb-day-content-panel[data-day]",
        );
        if (!panels.length) return;

        function getVisibleDayLocal() {
            var headerH =
                parseInt(
                    getComputedStyle(document.documentElement).getPropertyValue(
                        "--header-h",
                    ) || "100",
                    10,
                ) || 100;
            var globalBarH =
                parseInt(
                    getComputedStyle(document.documentElement).getPropertyValue(
                        "--aj-global-bar-height",
                    ) || "60",
                    10,
                ) || 60;
            var barH =
                parseInt(
                    getComputedStyle(document.documentElement).getPropertyValue(
                        "--aj-day-details-bar-height",
                    ) || "52",
                    10,
                ) || 52;
            var topOffset = headerH + globalBarH + barH;

            var best = null;
            var bestTop = Infinity;
            for (var i = 0; i < panels.length; i++) {
                var rect = panels[i].getBoundingClientRect();
                if (rect.bottom < topOffset + 30) continue;
                if (rect.top > topOffset + 200) continue;
                if (rect.top < bestTop) {
                    bestTop = rect.top;
                    best = panels[i].getAttribute("data-day");
                }
            }
            if (best) return best;
            for (var j = panels.length - 1; j >= 0; j--) {
                var r = panels[j].getBoundingClientRect();
                if (r.top <= topOffset + 150)
                    return panels[j].getAttribute("data-day");
            }
            return activeDay;
        }

        var headerH =
            parseInt(
                getComputedStyle(document.documentElement).getPropertyValue(
                    "--header-h",
                ) || "100",
                10,
            ) || 100;
        var globalBarH =
            parseInt(
                getComputedStyle(document.documentElement).getPropertyValue(
                    "--aj-global-bar-height",
                ) || "60",
                10,
            ) || 60;
        var barH =
            parseInt(
                getComputedStyle(document.documentElement).getPropertyValue(
                    "--aj-day-details-bar-height",
                ) || "52",
                10,
            ) || 52;
        var topOffset = headerH + globalBarH + barH;
        var rootMargin = "-" + topOffset + "px 0px -50% 0px";

        function onScroll() {
            if (scrollSpyLock) return;
            var day = getVisibleDayLocal();
            if (day) {
                // Update active day in sidebar nav
                setActive(day);
                // Always update Day Details bar with current visible day
                updateDayDetailsBar(day);
                // Update active day tracking
                activeDay = day;
                // If in day mode, also apply filter
                if (currentMode === "day") {
                    applyFilterToPanel(day);
                }
            }
        }

        var ticking = false;
        window.addEventListener(
            "scroll",
            function () {
                if (ticking) return;
                ticking = true;
                requestAnimationFrame(function () {
                    onScroll();
                    ticking = false;
                });
            },
            { passive: true },
        );

        var observer = new IntersectionObserver(
            function (entries) {
                if (scrollSpyLock) return;
                requestAnimationFrame(function () {
                    var day = getVisibleDayLocal();
                    if (day) {
                        // Update active day in Days bar (works in both global and day mode)
                        setActive(day);
                        // Always update Day Details bar with current visible day
                        updateDayDetailsBar(day);
                        // Update active day tracking
                        activeDay = day;
                        // If in day mode, also apply filter
                        if (currentMode === "day") {
                            applyFilterToPanel(day);
                        }
                    }
                });
            },
            { root: null, rootMargin: rootMargin, threshold: [0, 0.1, 0.5] },
        );
        for (var k = 0; k < panels.length; k++) observer.observe(panels[k]);
        onScroll();
    }

    function setTabActive(tab) {
        activeTab = tab;
        // Un seul set d'onglets : mettre à jour l'état actif
        $(
            ".ajtb-day-details-bar__tabs .ajtb-tab-pill, .ajtb-top-bar__tabs .ajtb-tab-pill",
        ).each(function () {
            var $btn = $(this);
            var t = $btn.attr("data-ajtb-tab");
            if (t === tab) {
                $btn.addClass("active").attr("aria-pressed", "true");
            } else {
                $btn.removeClass("active").attr("aria-pressed", "false");
            }
        });
    }

    function applyFilterToPanel(dayNum) {
        var panel = document.getElementById("aj-day-panel-" + dayNum);
        if (!panel) return;
        var blocks = panel.querySelectorAll(".ajtb-tab-block");
        blocks.forEach(function (block) {
            var tab = block.getAttribute("data-ajtb-tab");
            var show = activeTab === "programme" || tab === activeTab;
            block.style.display = show ? "" : "none";
        });
    }

    function applyGlobalFilter(tab, opts) {
        opts = opts || {};
        // Show all days, filter by tab type across all days
        var panels = document.querySelectorAll(
            "#itinerary .ajtb-day-content-panel",
        );
        panels.forEach(function (panel) {
            var blocks = panel.querySelectorAll(".ajtb-tab-block");
            blocks.forEach(function (block) {
                var blockTab = block.getAttribute("data-ajtb-tab");
                var show = false;

                if (tab === "programme") {
                    show = true; // Show all
                } else if (tab === "flights-transfers") {
                    show = blockTab === "flights" || blockTab === "transfers";
                } else {
                    show = blockTab === tab;
                }

                block.style.display = show ? "" : "none";
            });
        });

        // Scroll to first visible content only when user changes tab (not on initial page load)
        if (opts.scroll !== false) {
            var firstVisible = document.querySelector(
                '#itinerary .ajtb-day-content-panel:not([style*="display: none"])',
            );
            if (firstVisible) {
                firstVisible.scrollIntoView({
                    behavior: "smooth",
                    block: "start",
                });
            }
        }
    }

    function initTopItineraryBar() {
        var section = document.getElementById("itinerary");
        if (!section) return;
        var raw = section.getAttribute("data-day-includes");
        if (raw) {
            try {
                dayIncludesData = JSON.parse(raw);
            } catch (e) {
                console.error("Error parsing day-includes data:", e);
                dayIncludesData = {};
            }
        } else {
            console.warn("data-day-includes attribute not found on #itinerary");
        }
        // Initialize Day Details bar height
        setDayDetailsBarHeight();
        updateDayDetailsBar(activeDay);
        setTabActive(activeTab);

        $(document).on(
            "click",
            ".ajtb-day-details-bar__tabs .ajtb-tab-pill, .ajtb-top-bar__tabs .ajtb-tab-pill",
            function (e) {
                e.preventDefault();
                var tab = $(this).attr("data-ajtb-tab");
                if (!tab) return;
                setTabActive(tab);
                if (currentMode === "day") {
                    applyFilterToPanel(activeDay);
                } else {
                    // In global mode, apply filter to all days
                    applyGlobalFilter(globalActiveTab);
                }
            },
        );

        // Global bar clicks
        $(document).on("click", ".ajtb-global-pill", function (e) {
            e.preventDefault();
            var tab = $(this).attr("data-ajtb-global-tab");
            if (!tab) return;
            setGlobalMode(tab);
        });
    }

    function initDayPlanNav() {
        var $panels = $("#itinerary .ajtb-day-content-panel");
        var $tabs = $("#itinerary .aj-day-plan-nav [data-aj-nav-day]");
        if ($panels.length && $tabs.length) {
            $panels.attr("role", "tabpanel");
            $tabs.attr("role", "tab");
            // Click: scroll smooth vers le jour, mise à jour immédiate de la barre + liste, reset onglet Programme
            $(document).on(
                "click",
                "#itinerary [data-aj-nav-day]",
                function (e) {
                    e.preventDefault();
                    var day = $(this).attr("data-aj-nav-day");
                    if (day) {
                        setTabActive("programme");
                        setActive(day);
                        updateDayDetailsBar(day);
                        activeDay = day;
                        scrollToDay(day);
                    }
                },
            );

            return;
        }

        // Fallback: legacy timeline (scroll to day)
        var $legacyPanels = $("#itinerary .aj-day-panel");
        if ($legacyPanels.length) {
            $legacyPanels.attr("role", "tabpanel");
            $("#itinerary")
                .find(".aj-day-plan-nav [data-aj-nav-day]")
                .attr("role", "tab");
            $(document).on("click", "[data-aj-nav-day]", function (e) {
                e.preventDefault();
                var day = $(this).attr("data-aj-nav-day");
                if (day) {
                    setActive(day);
                    var el = document.getElementById("aj-day-panel-" + day);
                    if (el)
                        el.scrollIntoView({
                            behavior: "smooth",
                            block: "start",
                        });
                }
            });
        }
    }

    function initReadMore() {
        $(document).on(
            "click",
            "#itinerary .aj-day-notes-read-more",
            function () {
                var $btn = $(this);
                var $wrap = $btn.closest(".aj-day-notes-wrap");
                var expanded = $btn.attr("aria-expanded") === "true";
                $wrap.toggleClass("aj-day-notes-collapsed", expanded);
                $btn.attr("aria-expanded", !expanded).text(
                    expanded ? "Voir plus" : "Voir moins",
                );
            },
        );
    }

    function initFlightRemove() {
        $(document).on(
            "click",
            "#itinerary [data-aj-flight-remove]",
            function () {
                var $card = $(this).closest(".aj-flight-card");
                var $block = $card.closest(".ajtb-day-flight-block");
                if ($block.length) {
                    $block.addClass("aj-flight-card--removed").slideUp(200);
                } else {
                    $card.addClass("aj-flight-card--removed").slideUp(200);
                }
            },
        );
    }

    /**
     * Quand la section Galerie Photos (#gallery) est bien visible (≥50%), masquer le bloc sticky et le sidebar.
     * Seuil élevé pour ne pas cacher le Plan de séjour tant qu'on est encore dans l'itinéraire.
     */
    function initGalleryStickyHide() {
        var galleryEl = document.getElementById("gallery");
        var pageEl = document.querySelector(".ajtb-tour-page");
        if (!galleryEl || !pageEl) return;

        var observer = new IntersectionObserver(
            function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        pageEl.classList.add("ajtb-gallery-in-view");
                    } else {
                        pageEl.classList.remove("ajtb-gallery-in-view");
                    }
                });
            },
            {
                root: null,
                rootMargin: "0px",
                threshold: 0.5,
            },
        );
        observer.observe(galleryEl);
    }

    /**
     * Calcule --header-h (hauteur du header) pour que la barre sticky colle JUSTE SOUS le header.
     * Détecte les headers fixed/sticky en haut de la page et calcule leur hauteur totale.
     * Recalcul au chargement + resize + ResizeObserver sur le header.
     */
    function setHeaderHeight() {
        var MAX = 350;
        var FALLBACK = 100;

        // Vérifier d'abord si une valeur personnalisée est définie
        var custom =
            document.documentElement.getAttribute("data-aj-header-offset") ||
            document.body.getAttribute("data-aj-header-offset");
        if (custom) {
            var num = parseInt(custom, 10);
            if (!isNaN(num) && num >= 0) {
                var val = Math.min(num, MAX);
                document.documentElement.style.setProperty(
                    "--header-h",
                    val + "px",
                );
                return;
            }
        }

        // Stratégie 1: Chercher les éléments fixed/sticky en haut de la page (topbar + header)
        var totalHeight = 0;
        var fixedElements = document.querySelectorAll(
            'header, [role="banner"], #header, .header, .site-header, .main-header, .navbar, .navbar-header, #masthead, .st-header, .header-wrapper, .site-header-wrap, .header-area, #site-header, #page-topbar, [class*="topbar"], [class*="top-bar"]',
        );

        for (var i = 0; i < fixedElements.length; i++) {
            var el = fixedElements[i];
            var style = window.getComputedStyle(el);
            var rect = el.getBoundingClientRect();

            // Vérifier si l'élément est fixed ou sticky et en haut de la page
            var isFixed =
                style.position === "fixed" || style.position === "sticky";
            var isAtTop = rect.top <= 10; // Tolérance de 10px pour les headers qui peuvent avoir un petit décalage

            if (isFixed && isAtTop) {
                var h = Math.round(el.offsetHeight || rect.height || 0);
                if (h > 0 && h <= MAX) {
                    // Si plusieurs éléments sont empilés (ex: topbar + header), additionner leurs hauteurs
                    // On vérifie si l'élément est au-dessus du précédent
                    if (rect.top < totalHeight) {
                        // Nouvel élément au-dessus, réinitialiser
                        totalHeight = h;
                    } else if (rect.top <= totalHeight + 5) {
                        // Élément adjacent, additionner
                        totalHeight = Math.max(totalHeight, rect.top + h);
                    } else {
                        // Prendre le maximum si les éléments ne sont pas empilés
                        totalHeight = Math.max(totalHeight, h);
                    }
                }
            }
        }

        // Stratégie 2: Si rien trouvé avec fixed/sticky, chercher le premier header visible
        if (totalHeight <= 0) {
            var selectors = [
                "header",
                'header[role="banner"]',
                "#header",
                ".header",
                ".site-header",
                ".main-header",
                ".navbar",
                ".navbar-header",
                "#masthead",
                ".st-header",
                ".header-wrapper",
                ".site-header-wrap",
                ".header-area",
                "#site-header",
                "#page-topbar",
            ];

            for (var j = 0; j < selectors.length; j++) {
                var el = document.querySelector(selectors[j]);
                if (el) {
                    var rect = el.getBoundingClientRect();
                    var h = Math.round(el.offsetHeight || rect.height);
                    // Accepter si l'élément est en haut ou proche du haut
                    if (rect.top <= 50 && h > 0 && h <= MAX) {
                        totalHeight = Math.max(totalHeight, h);
                    }
                }
            }
        }

        // Stratégie 3: Fallback - chercher n'importe quel header
        if (totalHeight <= 0) {
            var firstHeader = document.querySelector(
                "header, #header, .header, .site-header, #page-topbar",
            );
            if (firstHeader) {
                var h = Math.round(
                    firstHeader.offsetHeight ||
                        firstHeader.getBoundingClientRect().height ||
                        0,
                );
                if (h > 0 && h <= MAX) totalHeight = h;
            }
        }

        // Calculer la valeur finale avec limites raisonnables
        var val = Math.min(
            Math.max(totalHeight > 0 ? totalHeight : FALLBACK, 60),
            MAX,
        );
        document.documentElement.style.setProperty("--header-h", val + "px");

        // Calculer aussi les hauteurs des barres
        setGlobalBarHeight();

        // Debug (optionnel, à retirer en production)
        // console.log('[AJTB] Header height calculated:', val + 'px');
    }

    /**
     * Calcule --aj-global-bar-height (hauteur de la barre globale) pour positionner la barre du jour.
     * IMPORTANT : Cette fonction doit être appelée AVANT que dayBar ne devienne sticky.
     */
    function setGlobalBarHeight() {
        var globalBar = document.getElementById("ajtb-global-summary-bar");
        if (globalBar) {
            // Forcer un reflow pour obtenir la hauteur réelle
            var height = Math.round(
                globalBar.offsetHeight ||
                    globalBar.getBoundingClientRect().height ||
                    0,
            );
            if (height > 0) {
                document.documentElement.style.setProperty(
                    "--aj-global-bar-height",
                    height + "px",
                );
            } else {
                // Fallback si la hauteur n'est pas encore disponible
                document.documentElement.style.setProperty(
                    "--aj-global-bar-height",
                    "60px",
                );
            }
        } else {
            document.documentElement.style.setProperty(
                "--aj-global-bar-height",
                "0px",
            );
        }
    }

    /**
     * Calcule --aj-day-details-bar-height (hauteur de la barre Day Details) pour le scroll-margin-top.
     */
    function setDayDetailsBarHeight() {
        var dayDetailsBar = document.getElementById("ajtb-day-details-bar");
        if (dayDetailsBar) {
            var height = Math.round(dayDetailsBar.offsetHeight || 0);
            document.documentElement.style.setProperty(
                "--aj-day-details-bar-height",
                height + "px",
            );
            document.documentElement.style.setProperty(
                "--aj-sticky-bar-height",
                height + "px",
            ); // Legacy compatibility
        } else {
            document.documentElement.style.setProperty(
                "--aj-day-details-bar-height",
                "52px",
            );
            document.documentElement.style.setProperty(
                "--aj-sticky-bar-height",
                "52px",
            );
        }
    }

    /**
     * Calcule --ajtb-sticky-group-height (titre + onglets + plan de séjour + barre jour).
     */
    function setStickyGroupHeight() {
        var container = document.getElementById("sticky-itinerary-container");
        var fallback = "60px";
        if (!container) {
            document.documentElement.style.setProperty(
                "--ajtb-sticky-group-height",
                fallback,
            );
            return;
        }
        var tabs = container.querySelector(".sticky-itinerary-container__tabs");
        var total = tabs ? Math.round(tabs.offsetHeight || 0) : 0;
        /* Marge de sécurité pour que le plan de séjour ne soit jamais masqué sous la barre au scroll */
        if (total > 0) {
            var safeHeight = total + 8;
            document.documentElement.style.setProperty(
                "--ajtb-sticky-group-height",
                safeHeight + "px",
            );
        } else {
            document.documentElement.style.setProperty(
                "--ajtb-sticky-group-height",
                fallback,
            );
        }
    }

    /**
     * Barre sticky titre du tour : affichée au scroll tant que la barre du titre (au-dessus des onglets)
     * n’est pas visible. Dès que le bloc titre+onglets entre en vue ou se colle, on masque la barre
     * fixe pour éviter toute duplication visuelle ; titre et onglets restent un seul bloc sticky.
     */
    function initStickyTourTitleBar() {
        var stickyTitleBar = document.getElementById(
            "ajtb-sticky-tour-title-bar",
        );
        var heroTitle = document.querySelector(".ajtb-hero-title");
        var tabsBlock = document.querySelector(
            ".ajtb-tabs-block.sticky-itinerary-container__tabs, .sticky-itinerary-container__tabs",
        );
        var itineraryHeader = document.querySelector(
            ".sticky-itinerary-container__header.ajtb-sticky-manual-title-bar",
        );

        if (!stickyTitleBar) return;

        function updateStickyTitleBar() {
            var heroBottom = heroTitle
                ? heroTitle.getBoundingClientRect().bottom
                : 0;
            var tabsTop = tabsBlock
                ? tabsBlock.getBoundingClientRect().top
                : 9999;
            var titleBarHeight = stickyTitleBar.offsetHeight;
            var viewportHeight = window.innerHeight;

            /* Dès que la barre du titre (au-dessus des onglets) est visible dans le viewport,
               on masque la barre fixe pour ne jamais afficher deux titres en même temps. */
            var headerInView = false;
            if (itineraryHeader) {
                var headerRect = itineraryHeader.getBoundingClientRect();
                headerInView =
                    headerRect.bottom > 0 && headerRect.top < viewportHeight;
            }
            var tabsReachedTop = tabsTop <= titleBarHeight;

            if (headerInView || tabsReachedTop) {
                stickyTitleBar.classList.add("is-hidden");
                stickyTitleBar.classList.remove("is-visible");
                stickyTitleBar.setAttribute("aria-hidden", "true");
            } else if (heroBottom < 0) {
                stickyTitleBar.classList.add("is-visible");
                stickyTitleBar.classList.remove("is-hidden");
                stickyTitleBar.setAttribute("aria-hidden", "false");
            } else {
                stickyTitleBar.classList.remove("is-visible");
                stickyTitleBar.classList.remove("is-hidden");
                stickyTitleBar.setAttribute("aria-hidden", "true");
            }
        }

        var scrollTicking = false;
        window.addEventListener(
            "scroll",
            function () {
                if (!scrollTicking) {
                    window.requestAnimationFrame(function () {
                        updateStickyTitleBar();
                        scrollTicking = false;
                    });
                    scrollTicking = true;
                }
            },
            { passive: true },
        );

        updateStickyTitleBar();
    }

    $(function () {
        // Calcul initial avec délai pour s'assurer que le DOM est complètement chargé
        setHeaderHeight();

        // Recalcul après un court délai pour capturer les headers chargés dynamiquement
        setTimeout(setHeaderHeight, 100);
        setTimeout(setHeaderHeight, 500);

        // Observer tous les éléments de header possibles
        var headerSelectors =
            'header, [role="banner"], #header, .header, .site-header, .main-header, .navbar, .navbar-header, #masthead, .st-header, .header-wrapper, .site-header-wrap, .header-area, #site-header, #page-topbar, [class*="topbar"], [class*="top-bar"]';
        var headers = document.querySelectorAll(headerSelectors);

        if (window.ResizeObserver && headers.length) {
            headers.forEach(function (header) {
                new ResizeObserver(function () {
                    // Délai pour éviter les calculs multiples rapides
                    clearTimeout(window._ajtbHeaderResizeTimeout);
                    window._ajtbHeaderResizeTimeout = setTimeout(
                        setHeaderHeight,
                        50,
                    );
                }).observe(header);
            });
        }

        // Recalcul au resize de la fenêtre
        var resizeTimeout;
        window.addEventListener(
            "resize",
            function () {
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(function () {
                    setHeaderHeight();
                    setGlobalBarHeight();
                    setDayDetailsBarHeight();
                }, 150);
            },
            { passive: true },
        );

        // Observer les changements de style sur le body/html pour détecter les changements de layout
        if (window.MutationObserver) {
            var observer = new MutationObserver(function () {
                clearTimeout(window._ajtbHeaderMutationTimeout);
                window._ajtbHeaderMutationTimeout = setTimeout(function () {
                    setHeaderHeight();
                    setGlobalBarHeight();
                    setDayDetailsBarHeight();
                    setStickyGroupHeight();
                }, 100);
            });
            observer.observe(document.body, {
                childList: true,
                subtree: true,
                attributes: true,
                attributeFilter: ["class", "style"],
            });

            // Observer aussi la barre globale pour détecter les changements de hauteur
            var globalBar = document.getElementById("ajtb-global-summary-bar");
            if (globalBar && window.ResizeObserver) {
                new ResizeObserver(function () {
                    clearTimeout(window._ajtbGlobalBarResizeTimeout);
                    window._ajtbGlobalBarResizeTimeout = setTimeout(
                        function () {
                            setGlobalBarHeight();
                            // Recalculer aussi la hauteur de dayBar après globalBar pour éviter le chevauchement
                            setDayDetailsBarHeight();
                        },
                        50,
                    );
                }).observe(globalBar);
            }

            // Observer aussi la barre Day Details pour détecter les changements de hauteur
            var dayDetailsBar = document.getElementById("ajtb-day-details-bar");
            if (dayDetailsBar && window.ResizeObserver) {
                new ResizeObserver(function () {
                    clearTimeout(window._ajtbDayDetailsBarResizeTimeout);
                    window._ajtbDayDetailsBarResizeTimeout = setTimeout(
                        setDayDetailsBarHeight,
                        50,
                    );
                }).observe(dayDetailsBar);
            }

            // Observer le bloc sticky (titre + tabs) pour le top du sidebar Plan de séjour
            var stickyContainer = document.getElementById(
                "sticky-itinerary-container",
            );
            if (stickyContainer && window.ResizeObserver) {
                new ResizeObserver(function () {
                    clearTimeout(window._ajtbStickyGroupResizeTimeout);
                    window._ajtbStickyGroupResizeTimeout = setTimeout(
                        setStickyGroupHeight,
                        50,
                    );
                }).observe(stickyContainer);
            }
        }

        // IMPORTANT : Calculer les hauteurs AVANT d'initialiser les autres fonctions
        setHeaderHeight();
        setGlobalBarHeight();
        setDayDetailsBarHeight();
        setStickyGroupHeight();

        initTopItineraryBar();
        initDayPlanNav();
        initScrollSpy();
        initReadMore();
        initFlightRemove();
        initGalleryStickyHide();

        // Recalcul après un court délai pour capturer les changements de layout
        setTimeout(function () {
            setHeaderHeight();
            setGlobalBarHeight();
            setDayDetailsBarHeight();
            setStickyGroupHeight();
        }, 100);
        setTimeout(function () {
            setHeaderHeight();
            setGlobalBarHeight();
            setDayDetailsBarHeight();
            setStickyGroupHeight();
        }, 500);

        // Initialize: start in global mode (sans scroll pour garder la page en haut)
        setGlobalMode("programme", { scroll: false });

        // Initialize active day in sidebar nav and Day Details bar
        if (activeDay) {
            setActive(activeDay);
            updateDayDetailsBar(activeDay);
        }

        // Initial scroll check to set correct day
        setTimeout(function () {
            var panels = document.querySelectorAll(
                "#itinerary .ajtb-day-content-panel[data-day]",
            );
            if (panels.length) {
                var headerH =
                    parseInt(
                        getComputedStyle(
                            document.documentElement,
                        ).getPropertyValue("--header-h") || "100",
                        10,
                    ) || 100;
                var globalBarH =
                    parseInt(
                        getComputedStyle(
                            document.documentElement,
                        ).getPropertyValue("--aj-global-bar-height") || "60",
                        10,
                    ) || 60;
                var barH =
                    parseInt(
                        getComputedStyle(
                            document.documentElement,
                        ).getPropertyValue("--aj-day-details-bar-height") ||
                            "52",
                        10,
                    ) || 52;
                var topOffset = headerH + globalBarH + barH;

                var best = null;
                var bestTop = Infinity;
                for (var i = 0; i < panels.length; i++) {
                    var rect = panels[i].getBoundingClientRect();
                    if (rect.bottom < topOffset + 30) continue;
                    if (rect.top > topOffset + 200) continue;
                    if (rect.top < bestTop) {
                        bestTop = rect.top;
                        best = panels[i].getAttribute("data-day");
                    }
                }
                if (best) {
                    setActive(best);
                    updateDayDetailsBar(best);
                    activeDay = best;
                }
            }
        }, 300);
    });
})(jQuery);
