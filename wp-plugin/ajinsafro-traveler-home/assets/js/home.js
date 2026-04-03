/**
 * Ajinsafro Traveler Home - home.js
 * Mobile drawer + search tabs + promotions interactions
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

    /* Accordéon prototype (onglets verticaux + flex-1, autoplay ping-pong, pause au survol) */
    function initProtoAccordionSlider() {
        var root = document.getElementById('aj-accordion-slider');
        if (!root) return;

        var slides = root.querySelectorAll('.aj-accordion-slide');
        var prevBtn = root.querySelector('[data-accordion-prev="1"]');
        var nextBtn = root.querySelector('[data-accordion-next="1"]');
        if (!slides.length) return;

        var autoplay = root.getAttribute('data-autoplay') === '1';
        var delay = parseInt(root.getAttribute('data-delay') || '5000', 10);
        if (isNaN(delay) || delay < 2000) delay = 5000;

        var defIdx = parseInt(root.getAttribute('data-default-index') || '0', 10);
        if (isNaN(defIdx)) defIdx = 0;
        if (defIdx >= slides.length) defIdx = 0;

        var timer = null;
        var currentIndex = defIdx;
        var direction = 1;

        function setActive(rawIndex) {
            var n = ((rawIndex % slides.length) + slides.length) % slides.length;
            currentIndex = n;

            slides.forEach(function (slide, i) {
                var content = slide.querySelector('.aj-accordion-slide__content');
                if (i === n) {
                    slide.classList.add('is-active');
                    slide.setAttribute('aria-expanded', 'true');
                    if (content) {
                        content.classList.remove('is-obscured');
                        window.setTimeout(function () {
                            content.classList.add('is-visible');
                        }, 100);
                    }
                } else {
                    slide.classList.remove('is-active');
                    slide.setAttribute('aria-expanded', 'false');
                    if (content) {
                        content.classList.remove('is-visible');
                        content.classList.add('is-obscured');
                    }
                }
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
            if (!autoplay || slides.length < 2) return;
            timer = setInterval(function () {
                if (currentIndex >= slides.length - 1) {
                    direction = -1;
                } else if (currentIndex <= 0) {
                    direction = 1;
                }
                setActive(currentIndex + direction);
            }, delay);
        }

        slides.forEach(function (slide) {
            slide.addEventListener('click', function () {
                var idx = parseInt(slide.getAttribute('data-index') || '0', 10);
                if (isNaN(idx)) return;
                setActive(idx);
                clearTimer();
                startTimer();
            });

            slide.addEventListener('keydown', function (e) {
                if (e.key !== 'Enter' && e.key !== ' ') return;
                e.preventDefault();
                var idx = parseInt(slide.getAttribute('data-index') || '0', 10);
                if (isNaN(idx)) return;
                setActive(idx);
                clearTimer();
                startTimer();
            });
        });

        if (prevBtn) {
            prevBtn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                setActive(currentIndex - 1);
                clearTimer();
                startTimer();
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                setActive(currentIndex + 1);
                clearTimer();
                startTimer();
            });
        }

        root.addEventListener('mouseenter', clearTimer);
        root.addEventListener('mouseleave', startTimer);

        setActive(defIdx);
        startTimer();
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

    function init() {
        initDrawer();
        initSearchTabs();

        initSlider('aj-lm-track', '.aj-arrow--prev', '.aj-arrow--next');
        initSlider('aj-accom-track', '.aj-accom-prev', '.aj-accom-next');
        initSlider('aj-theme-track', '.aj-theme-prev', '.aj-theme-next');

        initProtoAccordionSlider();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
