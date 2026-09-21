(function () {
    'use strict';

    var initialRoomingCleared = false;
    var userStartedManualRooming = false;
    var observerStarted = false;

    function isFastCreate() {
        return !!document.querySelector('.reservation-create--fast');
    }

    function activeStep() {
        var activePanel = document.querySelector('.reservation-create__panel.is-active[data-create-step]');
        if (!activePanel) return 1;
        return parseInt(activePanel.getAttribute('data-create-step') || '1', 10) || 1;
    }

    function removeAutoRoomingControls() {
        document.querySelectorAll('#btn-auto-rooming, #btn-rooming-auto').forEach(function (button) {
            button.remove();
        });
    }

    function setHiddenAllocationsEmpty() {
        var hidden = document.getElementById('reservation-room-allocations-json');
        if (hidden) {
            hidden.value = '[]';
            hidden.dispatchEvent(new Event('change', { bubbles: true }));
        }

        if (window.reservationState) {
            window.reservationState.roomAllocations = [];
        }
    }

    function renderEmptyRooming() {
        var board = document.getElementById('rooming-allocation-board');
        if (board) {
            board.innerHTML = '<div class="reservation-fast-empty">' +
                '<span class="reservation-fast-empty__icon" aria-hidden="true">+</span>' +
                '<div class="reservation-fast-empty__body">' +
                    '<div class="reservation-fast-empty__title">Aucune chambre repartie</div>' +
                    '<p class="reservation-fast-empty__text">Ajoutez une chambre, puis choisissez son type et ses voyageurs.</p>' +
                '</div>' +
            '</div>';
        }

        var pill = document.getElementById('rooming-status-pill');
        if (pill) {
            pill.textContent = 'ROOMING EN ATTENTE';
            pill.classList.remove('is-complete');
        }
    }

    function hasVisibleAutoRooming() {
        var hidden = document.getElementById('reservation-room-allocations-json');
        if (hidden) {
            var value = String(hidden.value || '').trim();
            if (value && value !== '[]') return true;
        }

        var board = document.getElementById('rooming-allocation-board');
        if (!board) return false;
        return !!board.querySelector('[data-rooming-room-type], [data-rooming-bed], [data-rooming-mode], [data-rooming-remove]');
    }

    function clearInitialRooming() {
        if (!isFastCreate() || initialRoomingCleared || userStartedManualRooming || activeStep() !== 2) {
            return;
        }

        initialRoomingCleared = true;
        removeAutoRoomingControls();

        var reset = document.getElementById('btn-reset-rooming');
        if (reset) {
            reset.click();
        }

        setHiddenAllocationsEmpty();

        if (hasVisibleAutoRooming()) {
            renderEmptyRooming();
        }

        if (typeof window.reservationCreateRecomputeTotals === 'function') {
            window.reservationCreateRecomputeTotals();
        }
    }

    function refresh() {
        if (!isFastCreate()) return;
        removeAutoRoomingControls();
        clearInitialRooming();
    }

    document.addEventListener('click', function (event) {
        if (event.target && event.target.closest('#btn-add-room-allocation, #btn-rooming-add')) {
            userStartedManualRooming = true;
        }

        window.setTimeout(refresh, 0);
    }, true);

    document.addEventListener('DOMContentLoaded', function () {
        refresh();
        window.setTimeout(refresh, 50);
        window.setTimeout(refresh, 250);
        window.setTimeout(refresh, 750);

        if (!observerStarted && window.MutationObserver) {
            observerStarted = true;
            new MutationObserver(refresh).observe(document.documentElement, {
                childList: true,
                subtree: true,
                attributes: true,
                attributeFilter: ['class', 'hidden']
            });
        }
    });

    window.addEventListener('load', function () {
        refresh();
        window.setTimeout(refresh, 250);
    });
})();
