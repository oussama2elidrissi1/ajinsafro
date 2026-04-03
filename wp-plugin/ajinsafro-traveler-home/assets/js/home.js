/**
 * Ajinsafro Traveler Home — home.js
 * Mobile drawer menu + accordion sub-menus + Modern search tabs + sliders
 */
(function(){
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
            if (window.innerWidth >= 1280 && document.body.classList.contains('menu-open')) closeDrawer();
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

    /* ── Modern Search Tabs ──────────────────────────────────────── */
    function initSearchTabs() {
        var tabsContainer = document.getElementById('aj-search-tabs');
        if (!tabsContainer) return;

        var tabs = tabsContainer.querySelectorAll('.aj-search-tab');
        var forms = document.querySelectorAll('.aj-search-form');

        tabs.forEach(function(tab) {
            tab.addEventListener('click', function() {
                var targetId = tab.getAttribute('data-target');

                tabs.forEach(function(t) {
                    t.classList.remove('aj-search-tab--active');
                });
                tab.classList.add('aj-search-tab--active');

                forms.forEach(function(f) {
                    f.classList.remove('aj-search-form--active');
                });

                var targetForm = document.getElementById('aj-form-' + targetId);
                if (targetForm) {
                    targetForm.classList.add('aj-search-form--active');
                }
            });
        });
    }

    /* ── Promotions accordion (horizontal expand + autoplay) ─────── */
    function initPromoAccordion() {
        var root = document.getElementById('aj-promos') ? document.querySelector('#aj-promos .aj-promo-acc') : null;
        if (!root) return;

        var strip = root.querySelector('.aj-promo-acc__strip');
        if (!strip) return;

        var panels = strip.querySelectorAll('.aj-promo-acc__panel');
        if (!panels.length) return;

        var autoplay = root.getAttribute('data-autoplay') === '1';
        var delay = parseInt(root.getAttribute('data-delay') || '5000', 10);
        if (isNaN(delay) || delay < 2000) delay = 5000;

        var defIdx = parseInt(root.getAttribute('data-default-index') || '0', 10);
        if (isNaN(defIdx)) defIdx = 0;
        if (defIdx >= panels.length) defIdx = 0;

        var timer = null;
        var active = defIdx;

        function setActive(i) {
            if (!panels.length) return;
            var n = ((i % panels.length) + panels.length) % panels.length;
            active = n;
            panels.forEach(function (p, idx) {
                var on = idx === n;
                p.classList.toggle('is-active', on);
                p.setAttribute('aria-expanded', on ? 'true' : 'false');
            });
        }

        function clearTimer() {
            if (timer) {
                clearInterval(timer);
                timer = null;
            }
        }

        function startTimer() {
            clearTimer();
            if (!autoplay || panels.length < 2) return;
            timer = setInterval(function () {
                setActive(active + 1);
            }, delay);
        }

        strip.addEventListener('click', function (e) {
            var innerBtn = e.target.closest('.aj-promo-acc__btn');
            if (innerBtn) return;

            var surf = e.target.closest('.aj-promo-acc__surface');
            if (!surf || !strip.contains(surf)) return;

            var panel = surf.closest('.aj-promo-acc__panel');
            if (!panel) return;

            var idx = parseInt(panel.getAttribute('data-index') || '0', 10);
            if (isNaN(idx)) return;

            var isLink = surf.classList.contains('aj-promo-acc__surface--link');
            if (isLink && panel.classList.contains('is-active')) {
                return;
            }
            if (isLink && !panel.classList.contains('is-active')) {
                e.preventDefault();
                setActive(idx);
                clearTimer();
                startTimer();
                return;
            }
            if (!isLink) {
                setActive(idx);
                clearTimer();
                startTimer();
            }
        });

        strip.addEventListener('keydown', function (e) {
            if (e.key !== 'Enter' && e.key !== ' ') return;
            var surf = e.target.closest('.aj-promo-acc__surface--static');
            if (!surf || !strip.contains(surf)) return;
            e.preventDefault();
            var panel = surf.closest('.aj-promo-acc__panel');
            if (!panel) return;
            var idx = parseInt(panel.getAttribute('data-index') || '0', 10);
            setActive(idx);
            clearTimer();
            startTimer();
        });

        root.addEventListener('mouseenter', clearTimer);
        root.addEventListener('mouseleave', startTimer);

        var rid = root.getAttribute('data-root') || '';
        var prevBtn = document.querySelector('.aj-promo-acc__prev[aria-controls="' + rid + '-strip"]');
        var nextBtn = document.querySelector('.aj-promo-acc__next[aria-controls="' + rid + '-strip"]');
        if (!prevBtn) prevBtn = root.parentElement ? root.parentElement.querySelector('.aj-promo-acc__prev') : null;
        if (!nextBtn) nextBtn = root.parentElement ? root.parentElement.querySelector('.aj-promo-acc__next') : null;

        if (prevBtn) {
            prevBtn.addEventListener('click', function () {
                setActive(active - 1);
                clearTimer();
                startTimer();
            });
        }
        if (nextBtn) {
            nextBtn.addEventListener('click', function () {
                setActive(active + 1);
                clearTimer();
                startTimer();
            });
        }

        setActive(defIdx);
        startTimer();
    }

    /* ── Generic Slider (prev/next arrows) ──────────────────────── */
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

        if (prevBtn) prevBtn.addEventListener('click', function() {
            track.scrollBy({ left: -scrollAmt(), behavior: 'smooth' });
        });
        if (nextBtn) nextBtn.addEventListener('click', function() {
            track.scrollBy({ left: scrollAmt(), behavior: 'smooth' });
        });
    }

    /* ── Init Everything ─────────────────────────────────────────── */
    function init() {
        initDrawer();
        initSearchTabs();

        initSlider('aj-lm-track', '.aj-arrow--prev', '.aj-arrow--next');
        initSlider('aj-accom-track', '.aj-accom-prev', '.aj-accom-next');
        initSlider('aj-theme-track', '.aj-theme-prev', '.aj-theme-next');
        initPromoAccordion();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
