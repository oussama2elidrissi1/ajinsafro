/*
 * Transferts des circuits : selection multiple et panneau de definition en lot.
 * Tout le reste de la page fonctionne sans JavaScript (liens et formulaires).
 */
(function () {
    'use strict';

    var bulk = document.querySelector('[data-att-bulk]');
    if (!bulk) {
        return;
    }

    var panel = bulk.querySelector('[data-att-bulk-panel]');
    var label = bulk.querySelector('[data-att-selection-label]');
    var openButton = bulk.querySelector('[data-att-bulk-open]');
    var closeButton = bulk.querySelector('[data-att-bulk-close]');
    var clearButton = bulk.querySelector('[data-att-bulk-clear]');
    var toggleAll = document.querySelector('[data-att-toggle-all]');

    function rowChecks() {
        return Array.prototype.slice.call(document.querySelectorAll('[data-att-row-check]'));
    }

    function selected() {
        return rowChecks().filter(function (box) { return box.checked; });
    }

    function refresh() {
        var count = selected().length;

        bulk.hidden = count === 0;
        if (count === 0 && panel) {
            panel.hidden = true;
        }

        if (label) {
            label.textContent = count + (count > 1 ? ' circuits sélectionnés' : ' circuit sélectionné');
        }

        if (toggleAll) {
            var boxes = rowChecks();
            toggleAll.checked = boxes.length > 0 && count === boxes.length;
            toggleAll.indeterminate = count > 0 && count < boxes.length;
        }
    }

    document.addEventListener('change', function (event) {
        if (event.target.matches('[data-att-row-check]')) {
            refresh();
            return;
        }

        if (event.target.matches('[data-att-toggle-all]')) {
            var on = event.target.checked;
            rowChecks().forEach(function (box) { box.checked = on; });
            refresh();
            return;
        }

        // Le filtre d'etat rejoue la recherche sans passer par le bouton.
        if (event.target.matches('[data-att-auto]')) {
            var form = event.target.closest('form');
            if (form) {
                form.submit();
            }
        }
    });

    if (openButton && panel) {
        openButton.addEventListener('click', function () {
            panel.hidden = false;
            var first = panel.querySelector('input');
            if (first) {
                first.focus();
            }
        });
    }

    if (closeButton && panel) {
        closeButton.addEventListener('click', function () {
            panel.hidden = true;
        });
    }

    if (clearButton) {
        clearButton.addEventListener('click', function () {
            rowChecks().forEach(function (box) { box.checked = false; });
            refresh();
        });
    }

    refresh();
})();
