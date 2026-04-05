(function () {
    'use strict';

    var currentStep = 1;
    var extrasMap = {};

    function parseExtrasMap() {
        var el = document.getElementById('reservation-create-extras-map');
        if (!el) return {};
        try {
            return JSON.parse(el.textContent || '{}') || {};
        } catch (e) {
            return {};
        }
    }

    function allSteps() {
        return Array.prototype.slice.call(document.querySelectorAll('.reservation-create__panel[data-create-step]'));
    }

    function setStep(step) {
        var panels = allSteps();
        if (!panels.length) return;
        var max = panels.length;
        var next = Number(step) || 1;
        if (next < 1) next = 1;
        if (next > max) next = max;
        currentStep = next;

        panels.forEach(function (panel) {
            var active = Number(panel.getAttribute('data-create-step')) === next;
            panel.classList.toggle('is-active', active);
            panel.hidden = !active;
        });

        document.querySelectorAll('[data-create-step-nav]').forEach(function (btn) {
            var active = Number(btn.getAttribute('data-create-step-nav')) === next;
            btn.classList.toggle('is-active', active);
            btn.setAttribute('aria-selected', active ? 'true' : 'false');
        });
    }

    function syncClientMode() {
        var modeNew = document.getElementById('client_mode_new');
        var modeExisting = document.getElementById('client_mode_existing');
        var blockNew = document.getElementById('new-client-block');
        var blockExisting = document.getElementById('existing-client-block');
        var existingSelect = document.getElementById('client_external_id');
        if (!modeNew || !modeExisting || !blockNew || !blockExisting) return;

        var useExisting = !!modeExisting.checked;
        blockExisting.classList.toggle('d-none', !useExisting);
        blockNew.classList.toggle('d-none', useExisting);
        if (existingSelect) existingSelect.required = useExisting;
    }

    function syncVisaMode() {
        var visaOk = document.getElementById('visa_ok');
        var assistantBlock = document.getElementById('assistant-visa-block');
        if (!visaOk || !assistantBlock) return;
        assistantBlock.classList.toggle('d-none', !!visaOk.checked);
    }

    function companionCount() {
        var count = 1;
        document.querySelectorAll('#companions-container .companion-row').forEach(function (row) {
            var first = row.querySelector('input[name*="[first_name]"]');
            var last = row.querySelector('input[name*="[last_name]"]');
            var hasName = (first && String(first.value || '').trim() !== '') || (last && String(last.value || '').trim() !== '');
            if (hasName) count++;
        });
        return count;
    }

    function syncSummary() {
        var tripSelect = document.getElementById('select-tour-id');
        var departureSelect = document.getElementById('reservation-departure-select');
        var grandTotal = document.getElementById('reservation-grand-total');
        var travelers = String(companionCount());
        var extrasLabel = formatMoney(extrasTotal());

        var tripLabel = 'Aucune sélection';
        if (tripSelect && tripSelect.selectedOptions && tripSelect.selectedOptions.length) {
            tripLabel = tripSelect.selectedOptions[0].textContent || tripLabel;
        }
        var depLabel = '—';
        if (departureSelect && departureSelect.selectedOptions && departureSelect.selectedOptions.length && departureSelect.value) {
            depLabel = departureSelect.selectedOptions[0].textContent || depLabel;
        }
        var totalLabel = grandTotal && grandTotal.textContent ? grandTotal.textContent.trim() : '—';

        [
            ['create-summary-trip', tripLabel],
            ['create-final-trip', tripLabel],
        ].forEach(function (pair) {
            var el = document.getElementById(pair[0]);
            if (el) el.textContent = pair[1];
        });
        [
            ['create-summary-departure', depLabel],
            ['create-final-departure', depLabel],
        ].forEach(function (pair) {
            var el = document.getElementById(pair[0]);
            if (el) el.textContent = pair[1];
        });
        [
            ['create-summary-travelers', travelers],
            ['create-final-travelers', travelers],
            ['create-travelers-badge', travelers],
        ].forEach(function (pair) {
            var el = document.getElementById(pair[0]);
            if (el) el.textContent = pair[1];
        });
        [
            ['create-summary-total', totalLabel],
            ['create-final-total', totalLabel],
        ].forEach(function (pair) {
            var el = document.getElementById(pair[0]);
            if (el) el.textContent = pair[1];
        });
        var extrasEl = document.getElementById('create-final-extras');
        if (extrasEl) extrasEl.textContent = extrasLabel;

        var selectedTripName = document.getElementById('create-selected-trip-name');
        if (selectedTripName) selectedTripName.textContent = tripLabel;
        var selectedDateName = document.getElementById('create-selected-date-name');
        if (selectedDateName) selectedDateName.textContent = depLabel;

        var empty = document.getElementById('create-no-companions');
        if (empty) empty.classList.toggle('d-none', document.querySelectorAll('#companions-container .companion-row').length > 0);
    }

    function travelerBreakdown() {
        var adult = 1;
        var child = 0;
        document.querySelectorAll('#companions-container .companion-row').forEach(function (row) {
            var type = row.querySelector('select[name*="[type]"]');
            var first = row.querySelector('input[name*="[first_name]"]');
            var last = row.querySelector('input[name*="[last_name]"]');
            var hasName = (first && String(first.value || '').trim() !== '') || (last && String(last.value || '').trim() !== '');
            if (!hasName) return;
            var value = type ? String(type.value || 'adult') : 'adult';
            if (value === 'child') child++;
            else if (value !== 'infant') adult++;
        });
        return { adult: adult, child: child };
    }

    function principalTravelerLabel() {
        var modeExisting = document.getElementById('client_mode_existing');
        var existing = document.getElementById('client_external_id');
        var first = document.getElementById('client_first_name');
        var last = document.getElementById('client_last_name');

        if (modeExisting && modeExisting.checked && existing && existing.selectedOptions && existing.selectedOptions.length && existing.value) {
            return existing.selectedOptions[0].textContent || 'Client principal';
        }

        var name = [first ? String(first.value || '').trim() : '', last ? String(last.value || '').trim() : '']
            .filter(Boolean)
            .join(' ');
        return name || 'Client principal';
    }

    function travelerRows() {
        var rows = [{
            id: 'principal',
            label: principalTravelerLabel(),
            type: 'adult',
            typeLabel: 'Adulte',
        }];

        document.querySelectorAll('#companions-container .companion-row').forEach(function (row, index) {
            var first = row.querySelector('input[name*="[first_name]"]');
            var last = row.querySelector('input[name*="[last_name]"]');
            var type = row.querySelector('select[name*="[type]"]');
            var typeValue = type ? String(type.value || 'adult') : 'adult';
            var name = [first ? String(first.value || '').trim() : '', last ? String(last.value || '').trim() : '']
                .filter(Boolean)
                .join(' ');
            rows.push({
                id: 'companion_' + index,
                label: name || ('Accompagnant #' + (index + 1)),
                type: typeValue,
                typeLabel: typeValue === 'child' ? 'Enfant' : (typeValue === 'infant' ? 'Bébé' : 'Adulte'),
            });
        });

        return rows;
    }

    function formatMoney(value) {
        return (Math.round(Number(value) || 0)).toLocaleString('fr-FR') + ' DH';
    }

    function renderExtras() {
        var tripSelect = document.getElementById('select-tour-id');
        var container = document.getElementById('reservation-create-extras-container');
        var emptyState = document.getElementById('reservation-create-extras-empty');
        if (!tripSelect || !container || !emptyState) return;
        var voyageId = String(tripSelect.value || '');
        var extras = voyageId && extrasMap[voyageId] ? extrasMap[voyageId] : [];
        var travelers = travelerRows();
        container.innerHTML = '';

        if (!extras.length) {
            emptyState.classList.remove('d-none');
            return;
        }

        emptyState.classList.add('d-none');
        extras.forEach(function (extra) {
            var icon = extra.icon || 'fa-plus-circle';
            var type = extra.extra_type ? String(extra.extra_type) : 'Extra';
            var card = document.createElement('div');
            var travelerHtml = travelers.map(function (traveler) {
                var unitPrice = traveler.type === 'child'
                    ? Number(extra.price_child || 0)
                    : (traveler.type === 'infant' ? 0 : Number(extra.price_adult || 0));
                return '' +
                    '<div class="reservation-create__extra-traveler">' +
                        '<label>' +
                            '<input type="checkbox" class="reservation-create-extra-cb" data-extra-id="' + extra.id + '" data-traveler-id="' + traveler.id + '" data-traveler-type="' + traveler.type + '" data-price="' + unitPrice + '">' +
                            '<span>' + traveler.label + '<small>' + traveler.typeLabel + '</small></span>' +
                        '</label>' +
                        '<span class="reservation-create__extra-traveler-price">' + formatMoney(unitPrice) + '</span>' +
                    '</div>';
            }).join('');
            card.className = 'reservation-create__extra-card';
            card.innerHTML =
                '<div class="reservation-create__extra-head">' +
                    '<div>' +
                        '<h4 class="reservation-create__extra-title"><i class="fas ' + icon + '"></i> ' + extra.name + '</h4>' +
                        '<p class="reservation-create__extra-desc">' + (extra.description || 'Option supplémentaire pour ce voyage.') + '</p>' +
                    '</div>' +
                    '<div class="reservation-create__extra-price">' +
                        '<strong>' + formatMoney(extra.price_adult || 0) + '</strong>' +
                        '<span>' + type + '</span>' +
                    '</div>' +
                '</div>' +
                '<div class="reservation-create__extra-travelers">' + travelerHtml + '</div>';
            container.appendChild(card);
        });

        container.querySelectorAll('.reservation-create-extra-cb').forEach(function (cb) {
            cb.addEventListener('change', function () {
                syncSummary();
                if (typeof window.reservationCreateRecomputeTotals === 'function') {
                    window.reservationCreateRecomputeTotals();
                }
            });
        });
    }

    function collectExtras() {
        var tripSelect = document.getElementById('select-tour-id');
        var hidden = document.getElementById('reservation-create-extras-json');
        var voyageId = String(tripSelect && tripSelect.value ? tripSelect.value : '');
        var extras = voyageId && extrasMap[voyageId] ? extrasMap[voyageId] : [];
        var travelers = travelerRows();
        var selected = [];

        document.querySelectorAll('.reservation-create-extra-cb:checked').forEach(function (cb) {
            var extraId = Number(cb.getAttribute('data-extra-id') || 0);
            var travelerId = String(cb.getAttribute('data-traveler-id') || '');
            var extra = extras.find(function (item) { return Number(item.id) === extraId; });
            var traveler = travelers.find(function (item) { return item.id === travelerId; });
            if (!extra) return;
            var total = Number(cb.getAttribute('data-price') || 0);
            selected.push({
                voyage_extra_id: extraId,
                name: traveler ? (extra.name + ' (' + traveler.label + ')') : extra.name,
                price: total,
                quantity: 1,
                pax: travelerId || null,
                traveler_type: traveler ? traveler.type : null,
                extra_type: extra.extra_type || '',
            });
        });

        if (hidden) hidden.value = JSON.stringify(selected);
        return selected;
    }

    function extrasTotal() {
        return collectExtras().reduce(function (sum, item) {
            return sum + Number(item.price || 0);
        }, 0);
    }

    function nextCompanionIndex() {
        return document.querySelectorAll('#companions-container .companion-row').length;
    }

    function bindCompanionRow(row) {
        var removeBtn = row.querySelector('.btn-remove-companion');
        if (removeBtn) {
            removeBtn.addEventListener('click', function () {
                row.remove();
                renderExtras();
                syncSummary();
            });
        }
        row.querySelectorAll('input, select').forEach(function (field) {
            field.addEventListener('input', function () {
                renderExtras();
                syncSummary();
            });
            field.addEventListener('change', function () {
                renderExtras();
                syncSummary();
            });
        });
    }

    function addCompanion() {
        var container = document.getElementById('companions-container');
        if (!container) return;
        var index = nextCompanionIndex();
        var row = document.createElement('div');
        row.className = 'companion-row reservation-create__companion';
        row.innerHTML =
            '<div class="reservation-create__companion-head">' +
                '<h4 class="reservation-create__companion-title">Accompagnant #' + (index + 1) + '</h4>' +
                '<button type="button" class="btn-remove-companion reservation-create__remove" aria-label="Supprimer">×</button>' +
            '</div>' +
            '<div class="reservation-create__grid reservation-create__grid--two">' +
                '<div class="reservation-create__field">' +
                    '<label class="reservation-create__label">Prénom</label>' +
                    '<input type="text" name="passengers[' + index + '][first_name]" class="reservation-create__input" autocomplete="given-name">' +
                '</div>' +
                '<div class="reservation-create__field">' +
                    '<label class="reservation-create__label">Nom</label>' +
                    '<input type="text" name="passengers[' + index + '][last_name]" class="reservation-create__input" autocomplete="family-name">' +
                '</div>' +
                '<div class="reservation-create__field">' +
                    '<label class="reservation-create__label">Type</label>' +
                    '<select name="passengers[' + index + '][type]" class="reservation-create__input">' +
                        '<option value="adult">Adulte</option>' +
                        '<option value="child">Enfant</option>' +
                        '<option value="infant">Bébé</option>' +
                    '</select>' +
                '</div>' +
                '<div class="reservation-create__field">' +
                    '<label class="reservation-create__label">Date de naissance</label>' +
                    '<input type="date" name="passengers[' + index + '][birth_date]" class="reservation-create__input">' +
                '</div>' +
                '<div class="reservation-create__field">' +
                    '<label class="reservation-create__label">Type document</label>' +
                    '<input type="text" name="passengers[' + index + '][document_type]" class="reservation-create__input" placeholder="CIN, Passeport…">' +
                '</div>' +
                '<div class="reservation-create__field">' +
                    '<label class="reservation-create__label">N° document</label>' +
                    '<input type="text" name="passengers[' + index + '][document_number]" class="reservation-create__input">' +
                '</div>' +
            '</div>';
        container.appendChild(row);
        bindCompanionRow(row);
        renderExtras();
        syncSummary();
    }

    document.addEventListener('DOMContentLoaded', function () {
        extrasMap = parseExtrasMap();
        setStep(1);

        document.querySelectorAll('[data-create-step-nav]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                setStep(btn.getAttribute('data-create-step-nav'));
            });
        });
        document.querySelectorAll('[data-create-next]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                setStep(currentStep + 1);
            });
        });
        document.querySelectorAll('[data-create-prev]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                setStep(currentStep - 1);
            });
        });

        var addBtn = document.getElementById('btn-add-companion');
        if (addBtn) addBtn.addEventListener('click', addCompanion);

        var modeNew = document.getElementById('client_mode_new');
        var modeExisting = document.getElementById('client_mode_existing');
        if (modeNew) modeNew.addEventListener('change', syncClientMode);
        if (modeExisting) modeExisting.addEventListener('change', syncClientMode);
        syncClientMode();
        if (modeNew) modeNew.addEventListener('change', renderExtras);
        if (modeExisting) modeExisting.addEventListener('change', renderExtras);

        var visaOk = document.getElementById('visa_ok');
        if (visaOk) visaOk.addEventListener('change', syncVisaMode);
        syncVisaMode();

        ['client_external_id', 'client_first_name', 'client_last_name'].forEach(function (id) {
            var el = document.getElementById(id);
            if (!el) return;
            el.addEventListener('change', renderExtras);
            el.addEventListener('input', renderExtras);
        });

        ['select-tour-id', 'reservation-departure-select'].forEach(function (id) {
            var el = document.getElementById(id);
            if (!el) return;
            el.addEventListener('change', function () {
                if (id === 'select-tour-id') {
                    renderExtras();
                }
                syncSummary();
                if (typeof window.reservationCreateRecomputeTotals === 'function') {
                    window.reservationCreateRecomputeTotals();
                }
            });
            el.addEventListener('input', syncSummary);
        });

        document.querySelectorAll('#companions-container .companion-row').forEach(bindCompanionRow);

        var observerTargets = [
            document.getElementById('reservation-grand-total'),
            document.getElementById('reservation-total-travelers'),
            document.getElementById('reservation-departure-select'),
        ].filter(Boolean);
        if (typeof MutationObserver !== 'undefined') {
            var observer = new MutationObserver(syncSummary);
            observerTargets.forEach(function (node) {
                observer.observe(node, { childList: true, subtree: true, characterData: true, attributes: true });
            });
        }

        window.reservationCreateGetExtrasTotal = extrasTotal;
        window.reservationCreateCollectExtras = collectExtras;

        var form = document.getElementById('reservation-create-form');
        if (form) {
            form.addEventListener('submit', function () {
                collectExtras();
            });
        }

        renderExtras();
        setInterval(syncSummary, 1200);
        syncSummary();
    });
})();
