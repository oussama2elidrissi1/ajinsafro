/**
 * Ajinsafro Traveler Home - home.js
 * Mobile drawer + search tabs
 */
(function () {
    'use strict';

    function initDrawer() {
        var burger = document.getElementById('aj-burger');
        var drawer = document.getElementById('aj-drawer');
        var drawerClose = document.getElementById('aj-drawer-close');
        var navMenu = document.getElementById('aj-nav-menu');

        if (!burger || !drawer) return;

        function openDrawer() {
            if (document.body) {
                document.body.classList.add('menu-open');
                document.body.style.overflow = 'hidden';
            }
            drawer.classList.add('aj-menu-open');
            drawer.setAttribute('aria-hidden', 'false');
            burger.setAttribute('aria-expanded', 'true');
        }

        function closeDrawer() {
            if (document.body) {
                document.body.classList.remove('menu-open');
                document.body.style.overflow = '';
            }
            drawer.classList.remove('aj-menu-open');
            drawer.setAttribute('aria-hidden', 'true');
            burger.setAttribute('aria-expanded', 'false');
        }

        burger.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            if (document.body.classList.contains('menu-open')) closeDrawer();
            else openDrawer();
        });

        if (drawerClose) {
            drawerClose.addEventListener('click', function (e) {
                e.preventDefault();
                closeDrawer();
            });
        }

        window.addEventListener('resize', function () {
            if (window.innerWidth >= 1280 && document.body.classList.contains('menu-open')) {
                closeDrawer();
            }
        });

        if (navMenu) {
            navMenu.addEventListener('click', function (e) {
                var li = e.target.closest('li.aj-has-sub, li.menu-item-has-children');
                if (!li || !li.querySelector('.aj-sub-menu, .sub-menu')) return;
                var link = li.querySelector(':scope > a');
                if (link && link.contains(e.target)) {
                    e.preventDefault();
                    li.classList.toggle('aj-sub-open');
                }
            });
        }
    }

    function initSearchTabs() {
        var tabsContainer = document.getElementById('aj-search-tabs');
        if (!tabsContainer) return;

        var tabs = tabsContainer.querySelectorAll('.aj-search-tab');
        var forms = document.querySelectorAll('.aj-search-form');

        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                var targetId = tab.getAttribute('data-target');

                tabs.forEach(function (t) {
                    t.classList.remove('aj-search-tab--active');
                });
                tab.classList.add('aj-search-tab--active');

                forms.forEach(function (f) {
                    f.classList.remove('aj-search-form--active');
                });

                var targetForm = document.getElementById('aj-form-' + targetId);
                if (targetForm) {
                    targetForm.classList.add('aj-search-form--active');
                }
            });
        });
    }

    function initSlider(trackId, prevSelector, nextSelector) {
        var track = document.getElementById(trackId);
        if (!track) return;

        function scrollAmt() {
            var item = track.querySelector('.aj-slider-v2__item, .aj-card');
            if (!item) return 320;
            var gap = parseFloat(getComputedStyle(track).gap) || 16;
            return item.offsetWidth + gap;
        }

        var prevBtn = document.querySelector(prevSelector);
        var nextBtn = document.querySelector(nextSelector);

        if (prevBtn) {
            prevBtn.addEventListener('click', function () {
                track.scrollBy({ left: -scrollAmt(), behavior: 'smooth' });
            });
        }
        if (nextBtn) {
            nextBtn.addEventListener('click', function () {
                track.scrollBy({ left: scrollAmt(), behavior: 'smooth' });
            });
        }
    }

    function initCatalogFilters() {
        var toggle = document.getElementById('aj-voyages-filters-toggle');
        if (!toggle) return;

        function syncBodyLock() {
            if (!document.body) return;
            document.body.style.overflow = toggle.checked ? 'hidden' : '';
        }

        toggle.addEventListener('change', syncBodyLock);

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && toggle.checked) {
                toggle.checked = false;
                syncBodyLock();
            }
        });

        window.addEventListener('resize', function () {
            if (window.innerWidth >= 992 && toggle.checked) {
                toggle.checked = false;
                syncBodyLock();
            }
        });

        syncBodyLock();
    }

    function init() {
        initDrawer();
        initSearchTabs();
        initCatalogFilters();

        initSlider('aj-lm-track', '.aj-arrow--prev', '.aj-arrow--next');
        initSlider('aj-accom-track', '.aj-accom-prev', '.aj-accom-next');
        initSlider('aj-theme-track', '.aj-theme-prev', '.aj-theme-next');

    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
