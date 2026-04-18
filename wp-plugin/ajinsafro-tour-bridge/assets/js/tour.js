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
            var target = document.getElementById("ajtb-v1-price-card");
            if (!target) {
                return;
            }

            target.scrollIntoView({ behavior: "smooth", block: "start" });
        });
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
        initStickySearchBox();
    });
})();
