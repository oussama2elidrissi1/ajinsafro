/* Espace Agent — menu repliable de l'en-tête et filtres du tableau de bord. */
(function () {
    'use strict';

    function initDrawer() {
        var header = document.querySelector('[data-eag-header]');
        if (!header) return;

        var drawer = header.querySelector('[data-eag-drawer]');
        var burger = header.querySelector('[data-eag-drawer-toggle]');
        var backdrop = document.querySelector('[data-eag-backdrop]');
        if (!drawer || !burger) return;

        function setOpen(open) {
            drawer.classList.toggle('is-open', open);
            burger.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (backdrop) backdrop.classList.toggle('is-open', open);
        }

        burger.addEventListener('click', function (event) {
            event.preventDefault();
            setOpen(!drawer.classList.contains('is-open'));
        });
        if (backdrop) backdrop.addEventListener('click', function () { setOpen(false); });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') setOpen(false);
        });

        var wide = window.matchMedia('(min-width: 1120px)');
        var onChange = function () { if (wide.matches) setOpen(false); };
        if (typeof wide.addEventListener === 'function') wide.addEventListener('change', onChange);
        else if (typeof wide.addListener === 'function') wide.addListener(onChange);
    }

    /* Filtres du tableau de bord : masquage côté client, sans rechargement. */
    function initFilters() {
        var list = document.querySelector('[data-eag-rows]');
        if (!list) return;
        var buttons = Array.prototype.slice.call(document.querySelectorAll('[data-eag-filter]'));
        if (!buttons.length) return;
        var rows = Array.prototype.slice.call(list.querySelectorAll('[data-eag-status]'));
        var empty = document.querySelector('[data-eag-filter-empty]');

        function apply(key) {
            var shown = 0;
            rows.forEach(function (row) {
                var match = key === 'all' || row.getAttribute('data-eag-status') === key;
                row.classList.toggle('is-hidden', !match);
                if (match) shown++;
            });
            buttons.forEach(function (btn) {
                btn.classList.toggle('is-active', btn.getAttribute('data-eag-filter') === key);
            });
            if (empty) empty.hidden = shown > 0 || rows.length === 0;
        }

        buttons.forEach(function (btn) {
            btn.addEventListener('click', function (event) {
                event.preventDefault();
                apply(btn.getAttribute('data-eag-filter') || 'all');
            });
        });
    }

    function init() {
        initDrawer();
        initFilters();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
