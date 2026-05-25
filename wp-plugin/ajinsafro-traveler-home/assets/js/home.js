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
                var prepareUrl = tab.getAttribute('data-prepare-url');
                if (targetId === 'vol' && prepareUrl) {
                    window.location.href = prepareUrl;
                    return;
                }

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

    function initSearchPopovers() {
        var root = document.getElementById('aj-home');
        if (!root) return;

        function closeAll(except) {
            root.querySelectorAll('[data-aj-popover]').forEach(function (popover) {
                if (popover !== except) {
                    popover.hidden = true;
                    var owner = popover.closest('[data-aj-date-picker], [data-aj-guests]');
                    if (owner) owner.classList.remove('aj-search-field--open');
                }
            });
        }

        function formatDate(value) {
            if (!value) return '';
            var parts = String(value).split('-');
            if (parts.length !== 3) return value;
            return parts[2] + '/' + parts[1] + '/' + parts[0];
        }

        function updateDatePicker(picker) {
            var start = picker.querySelector('[data-aj-date-input="start"]');
            var end = picker.querySelector('[data-aj-date-input="end"]');
            var display = picker.querySelector('[data-aj-date-display]');
            var startHidden = picker.querySelector('[data-aj-date-start]');
            var endHidden = picker.querySelector('[data-aj-date-end]');
            var startValue = start ? start.value : '';
            var endValue = end ? end.value : '';

            if (startHidden) startHidden.value = startValue;
            if (endHidden) endHidden.value = endValue;
            if (display) {
                display.value = startValue && endValue
                    ? formatDate(startValue) + ' - ' + formatDate(endValue)
                    : (startValue ? formatDate(startValue) : '');
            }
        }

        root.querySelectorAll('[data-aj-date-picker]').forEach(function (picker) {
            var nativeDate = picker.querySelector('[data-aj-native-date]');
            if (nativeDate) {
                picker.addEventListener('click', function (event) {
                    if (event.target === nativeDate) return;
                    event.preventDefault();
                    if (typeof nativeDate.showPicker === 'function') {
                        nativeDate.showPicker();
                    } else {
                        nativeDate.focus();
                    }
                });
                return;
            }

            var display = picker.querySelector('[data-aj-date-display]');
            var popover = picker.querySelector('[data-aj-popover]');
            if (!display || !popover) return;

            display.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                var willOpen = popover.hidden;
                closeAll(popover);
                popover.hidden = !willOpen;
                picker.classList.toggle('aj-search-field--open', willOpen);
                if (willOpen) {
                    var firstInput = popover.querySelector('input[type="date"]');
                    if (firstInput) firstInput.focus();
                }
            });

            picker.addEventListener('click', function (event) {
                if (event.target.closest('[data-aj-popover]') || event.target === display) return;
                display.click();
            });

            popover.querySelectorAll('input[type="date"]').forEach(function (input) {
                input.addEventListener('change', function () {
                    updateDatePicker(picker);
                });
            });
        });

        function plural(count, singular, pluralText) {
            return count + ' ' + (count > 1 ? pluralText : singular);
        }

        function updateGuests(block) {
            var mode = block.getAttribute('data-aj-guests-mode') || 'voyage';
            var adults = parseInt((block.querySelector('[data-aj-counter="adults"] [data-aj-count]') || {}).textContent || '1', 10) || 1;
            var children = parseInt((block.querySelector('[data-aj-counter="children"] [data-aj-count]') || {}).textContent || '0', 10) || 0;
            var infantsNode = block.querySelector('[data-aj-counter="infants"] [data-aj-count]');
            var infants = infantsNode ? (parseInt(infantsNode.textContent || '0', 10) || 0) : 0;
            var adultsInput = block.querySelector('[data-aj-guests-value="adults"]');
            var childrenInput = block.querySelector('[data-aj-guests-value="children"]');
            var label = block.querySelector('[data-aj-guests-label]');
            var flightClass = block.querySelector('[data-aj-flight-class]');
            var flightClassValue = block.querySelector('[data-aj-flight-class-value]');

            if (adultsInput) adultsInput.value = String(adults);
            if (childrenInput) childrenInput.value = String(children);
            if (flightClassValue && flightClass) flightClassValue.value = flightClass.value;

            if (label) {
                if (mode === 'flight') {
                    label.textContent = plural(adults, 'Adulte', 'Adultes') + (children ? ', ' + plural(children, 'Enfant', 'Enfants') : '') + ', ' + (flightClass && flightClass.value === 'business' ? 'Business' : 'Éco');
                } else {
                    label.textContent = plural(adults, 'Adulte', 'Adultes') + ' - ' + plural(children, 'Enfant', 'Enfants') + (infants ? ' - ' + plural(infants, 'Bébé', 'Bébés') : '');
                }
            }
        }

        root.querySelectorAll('[data-aj-guests]').forEach(function (block) {
            var toggle = block.querySelector('[data-aj-guests-toggle]');
            var popover = block.querySelector('[data-aj-popover]');
            if (!toggle || !popover) return;

            toggle.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                var willOpen = popover.hidden;
                closeAll(popover);
                popover.hidden = !willOpen;
                block.classList.toggle('aj-search-field--open', willOpen);
            });

            block.addEventListener('click', function (event) {
                if (event.target.closest('[data-aj-popover]') || event.target.closest('[data-aj-guests-toggle]')) return;
                toggle.click();
            });

            popover.querySelectorAll('[data-aj-counter]').forEach(function (counter) {
                var countNode = counter.querySelector('[data-aj-count]');
                var min = counter.getAttribute('data-aj-counter') === 'adults' ? 1 : 0;
                counter.querySelectorAll('[data-aj-minus], [data-aj-plus]').forEach(function (button) {
                    button.addEventListener('click', function (event) {
                        event.preventDefault();
                        var current = parseInt(countNode.textContent || String(min), 10) || min;
                        current += button.hasAttribute('data-aj-plus') ? 1 : -1;
                        countNode.textContent = String(Math.max(min, current));
                        updateGuests(block);
                    });
                });
            });

            var classSelect = block.querySelector('[data-aj-flight-class]');
            if (classSelect) {
                classSelect.addEventListener('change', function () {
                    updateGuests(block);
                });
            }
            updateGuests(block);
        });

        root.querySelectorAll('[data-aj-popover-close]').forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();
                closeAll();
            });
        });

        root.querySelectorAll('[data-aj-flight-form]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                window.location.href = form.getAttribute('action') || '/billet-avion/';
            });
        });

        document.addEventListener('click', function (event) {
            if (!event.target.closest('[data-aj-date-picker], [data-aj-guests]')) {
                closeAll();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') closeAll();
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
        initSearchPopovers();
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
