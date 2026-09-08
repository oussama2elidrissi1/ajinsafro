(function () {
    'use strict';

    function init() {
        var header = document.querySelector('[data-ea-header]');
        if (!header) return;

        var toggles = Array.prototype.slice.call(header.querySelectorAll('[data-ea-menu-toggle]'));
        var panels = Array.prototype.slice.call(header.querySelectorAll('[data-ea-menu-panel]'));
        var backdrop = document.querySelector('[data-ea-backdrop]');
        var drawer = header.querySelector('[data-ea-drawer]');
        var burger = header.querySelector('[data-ea-drawer-toggle]');
        var wide = window.matchMedia('(min-width: 1040px)');

        function panelFor(key) {
            for (var i = 0; i < panels.length; i++) {
                if (panels[i].getAttribute('data-ea-menu-panel') === key) return panels[i];
            }
            return null;
        }

        function syncBackdrop() {
            if (!backdrop) return;
            var anyOpen = panels.some(function (p) { return p.classList.contains('is-open'); })
                || (drawer && drawer.classList.contains('is-open'));
            backdrop.classList.toggle('is-open', anyOpen);
        }

        function closeMenus() {
            panels.forEach(function (p) { p.classList.remove('is-open'); });
            toggles.forEach(function (t) { t.setAttribute('aria-expanded', 'false'); });
            syncBackdrop();
        }

        function openMenu(key) {
            closeMenus();
            var panel = panelFor(key);
            if (!panel) return;
            panel.classList.add('is-open');
            toggles.forEach(function (t) {
                if (t.getAttribute('data-ea-menu-toggle') === key) t.setAttribute('aria-expanded', 'true');
            });
            syncBackdrop();
        }

        function closeDrawer() {
            if (!drawer) return;
            drawer.classList.remove('is-open');
            if (burger) burger.setAttribute('aria-expanded', 'false');
            syncBackdrop();
        }

        function toggleDrawer() {
            if (!drawer) return;
            closeMenus();
            var open = !drawer.classList.contains('is-open');
            drawer.classList.toggle('is-open', open);
            if (burger) burger.setAttribute('aria-expanded', open ? 'true' : 'false');
            syncBackdrop();
        }

        toggles.forEach(function (t) {
            t.addEventListener('click', function (event) {
                event.preventDefault();
                var key = t.getAttribute('data-ea-menu-toggle');
                var panel = panelFor(key);
                if (panel && panel.classList.contains('is-open')) {
                    closeMenus();
                } else {
                    openMenu(key);
                }
            });
        });

        Array.prototype.slice.call(header.querySelectorAll('[data-ea-menu-close]')).forEach(function (btn) {
            btn.addEventListener('click', function (event) {
                event.preventDefault();
                closeMenus();
            });
        });

        if (burger) {
            burger.addEventListener('click', function (event) {
                event.preventDefault();
                toggleDrawer();
            });
        }

        if (backdrop) {
            backdrop.addEventListener('click', function () {
                closeMenus();
                closeDrawer();
            });
        }

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeMenus();
                closeDrawer();
            }
        });

        function onBreakpoint() {
            if (wide.matches) {
                closeDrawer();
            } else {
                closeMenus();
            }
        }
        if (typeof wide.addEventListener === 'function') {
            wide.addEventListener('change', onBreakpoint);
        } else if (typeof wide.addListener === 'function') {
            wide.addListener(onBreakpoint);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
