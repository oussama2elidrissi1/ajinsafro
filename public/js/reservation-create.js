(function () {
    'use strict';

    var currentStep = 1;
    var extrasMap = {};

    function parseJsonScript(id, fallback) {
        var el = document.getElementById(id);
        if (!el) return fallback;
        try {
            return JSON.parse(el.textContent || '') || fallback;
        } catch (error) {
            return fallback;
        }
    }

    function allPanels() {
        return Array.prototype.slice.call(document.querySelectorAll('.reservation-create__panel[data-create-step]'));
    }

    function currentPanel() {
        return document.querySelector('.reservation-create__panel.is-active');
    }

    function parseNumber(value) {
        var parsed = parseFloat(value || '0');
        return Number.isFinite(parsed) ? parsed : 0;
    }

    function formatMoney(value) {
        return (Math.round((Number(value) || 0) * 100) / 100).toLocaleString('fr-FR', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2
        }) + ' DH';
    }

    function getTravelerCount() {
        var count = 1;
        document.querySelectorAll('#companions-container .companion-row').forEach(function (row) {
            var first = row.querySelector('input[name*="[first_name]"]');
            var last = row.querySelector('input[name*="[last_name]"]');
            if (String(first && first.value || '').trim() !== '' || String(last && last.value || '').trim() !== '') {
                count += 1;
            }
        });
        return count;
    }

    function principalTravelerLabel() {
        var existingMode = document.getElementById('client_mode_existing');
        var select = document.getElementById('client_external_id');
        if (existingMode && existingMode.checked && select && select.selectedOptions.length && select.value) {
            return select.selectedOptions[0].textContent || 'Client principal';
        }

        var first = document.getElementById('client_first_name');
        var last = document.getElementById('client_last_name');
        var label = [first ? String(first.value || '').trim() : '', last ? String(last.value || '').trim() : '']
            .filter(Boolean)
            .join(' ');

        return label || 'Client principal';
    }

    function travelerRows() {
        var rows = [{
            id: 'principal',
            label: principalTravelerLabel(),
            type: 'adult',
            priceType: 'adult'
        }];

        document.querySelectorAll('#companions-container .companion-row').forEach(function (row, index) {
            var first = row.querySelector('input[name*="[first_name]"]');
            var last = row.querySelector('input[name*="[last_name]"]');
            var typeSelect = row.querySelector('select[name*="[type]"]');
            var firstName = String(first && first.value || '').trim();
            var lastName = String(last && last.value || '').trim();
            if (firstName === '' && lastName === '') {
                return;
            }

            var type = String(typeSelect && typeSelect.value || 'adult');
            rows.push({
                id: 'companion_' + index,
                label: [firstName, lastName].filter(Boolean).join(' ') || ('Accompagnant #' + (index + 1)),
                type: type,
                priceType: type === 'child' ? 'child' : 'adult'
            });
        });

        return rows;
    }

    function getSelectedTripLabel() {
        var select = document.getElementById('select-tour-id');
        return select && select.selectedOptions.length ? (select.selectedOptions[0].textContent || 'Aucune sélection') : 'Aucune sélection';
    }

    function getSelectedTripOption() {
        var select = document.getElementById('select-tour-id');
        return select && select.selectedOptions.length ? select.selectedOptions[0] : null;
    }

    function getSelectedTripFallbackPrice() {
        var option = getSelectedTripOption();
        return parseNumber(option && option.getAttribute('data-price-from'));
    }

    function getSelectedDepartureLabel() {
        var select = document.getElementById('reservation-departure-select');
        return select && select.selectedOptions.length && select.value ? (select.selectedOptions[0].textContent || '—') : '—';
    }

    function getSelectedDepartureOption() {
        var select = document.getElementById('reservation-departure-select');
        return select && select.selectedOptions.length ? select.selectedOptions[0] : null;
    }

    function getAvailableDepartureCapacity() {
        var option = getSelectedDepartureOption();
        return parseInt(option && option.getAttribute('data-available-capacity') || '0', 10) || 0;
    }

    function getRoomMode() {
        var container = document.getElementById('reservation-hotel-rooms-container');
        return container && container.getAttribute('data-room-mode') ? String(container.getAttribute('data-room-mode')) : 'unknown';
    }

    function setAccommodationMode(mode) {
        var hidden = document.getElementById('reservation-accommodation-mode');
        if (hidden) {
            hidden.value = mode || 'rooms';
        }
    }

    function hotelRoomSummary() {
        var selectedRoomCount = 0;
        var selectedRoomCapacity = 0;
        var roomSupplementTotal = 0;
        var stockExceeded = false;

        document.querySelectorAll('.reservation-room-count').forEach(function (input) {
            var count = parseInt(input.value || '0', 10) || 0;
            var max = parseInt(input.getAttribute('max') || '0', 10) || 0;
            var supplement = parseNumber(input.getAttribute('data-room-supplement'));
            var capacity = parseInt(input.getAttribute('data-room-capacity') || '0', 10) || 0;

            if (max > 0 && count > max) {
                stockExceeded = true;
            }

            if (count > 0) {
                selectedRoomCount += count;
                selectedRoomCapacity += count * Math.max(capacity, 1);
                roomSupplementTotal += count * supplement;
            }
        });

        return {
            selectedRoomCount: selectedRoomCount,
            selectedRoomCapacity: selectedRoomCapacity,
            roomSupplementTotal: roomSupplementTotal,
            stockExceeded: stockExceeded
        };
    }

    function getBaseUnitPrice() {
        var input = document.querySelector('input[name="base_price"]');
        var fromInput = parseNumber(input && input.value);
        if (fromInput > 0) {
            return fromInput;
        }

        return getSelectedTripFallbackPrice();
    }

    function derivePaymentStatus(totalAmount, paidAmount) {
        if (paidAmount <= 0) {
            return 'Non payé';
        }
        if (Math.abs(paidAmount - totalAmount) < 0.01) {
            return 'Payé';
        }
        if (paidAmount < totalAmount / 2) {
            return 'Acompte';
        }
        return 'Payé partiellement';
    }

    function captureExtrasSelections() {
        var snapshot = {};
        document.querySelectorAll('.reservation-create__extra-card').forEach(function (card) {
            var extraId = String(card.getAttribute('data-extra-id') || '');
            if (!extraId) return;
            snapshot[extraId] = {
                quantity: parseInt(card.querySelector('[data-extra-quantity]') && card.querySelector('[data-extra-quantity]').value || '0', 10) || 0,
                scope: String(card.querySelector('[data-extra-scope]') && card.querySelector('[data-extra-scope]').value || 'dossier'),
                travelers: Array.prototype.slice.call(card.querySelectorAll('.reservation-create-extra-cb:checked')).map(function (cb) {
                    return String(cb.getAttribute('data-traveler-id') || '');
                })
            };
        });
        return snapshot;
    }

    function renderExtras() {
        var select = document.getElementById('select-tour-id');
        var container = document.getElementById('reservation-create-extras-container');
        var emptyState = document.getElementById('reservation-create-extras-empty');
        if (!select || !container || !emptyState) return;

        var preserved = captureExtrasSelections();
        var extras = extrasMap[String(select.value || '')] || [];
        var travelers = travelerRows();
        container.innerHTML = '';

        if (!extras.length) {
            emptyState.classList.remove('d-none');
            syncFinancialSummary();
            return;
        }

        emptyState.classList.add('d-none');

        extras.forEach(function (extra) {
            var snapshot = preserved[String(extra.id)] || { quantity: 0, scope: 'dossier', travelers: [] };
            var travelerHtml = travelers.map(function (traveler) {
                var unitPrice = traveler.priceType === 'child'
                    ? parseNumber(extra.price_child)
                    : parseNumber(extra.price_adult);
                return '' +
                    '<label class="reservation-create__extra-traveler">' +
                        '<span>' +
                            '<input type="checkbox" class="reservation-create-extra-cb" data-traveler-id="' + traveler.id + '" data-traveler-type="' + traveler.type + '" data-price="' + unitPrice + '"' + (snapshot.travelers.indexOf(traveler.id) !== -1 ? ' checked' : '') + '>' +
                            '<span>' + traveler.label + '<small>' + traveler.type + '</small></span>' +
                        '</span>' +
                        '<strong class="reservation-create__extra-traveler-price">' + formatMoney(unitPrice) + '</strong>' +
                    '</label>';
            }).join('');

            var card = document.createElement('div');
            card.className = 'reservation-create__extra-card';
            card.setAttribute('data-extra-id', String(extra.id));
            card.setAttribute('data-extra-name', String(extra.name || 'Extra'));
            card.setAttribute('data-extra-description', String(extra.description || ''));
            card.setAttribute('data-extra-adult-price', String(parseNumber(extra.price_adult)));
            card.setAttribute('data-extra-child-price', String(parseNumber(extra.price_child)));
            card.innerHTML =
                '<div class="reservation-create__extra-head">' +
                    '<div>' +
                        '<h4 class="reservation-create__extra-title">' + (extra.name || 'Extra') + '</h4>' +
                        '<p class="reservation-create__extra-desc">' + (extra.description || 'Option supplémentaire pour ce dossier.') + '</p>' +
                    '</div>' +
                    '<div class="reservation-create__extra-price">' +
                        '<strong>' + formatMoney(parseNumber(extra.price_adult)) + '</strong>' +
                        '<span>prix unitaire adulte</span>' +
                    '</div>' +
                '</div>' +
                '<div class="reservation-create__grid reservation-create__grid--two reservation-create__extra-controls">' +
                    '<div class="reservation-create__field">' +
                        '<label class="reservation-create__label">Application</label>' +
                        '<select class="reservation-create__input" data-extra-scope>' +
                            '<option value="dossier"' + (snapshot.scope === 'dossier' ? ' selected' : '') + '>Tout le dossier</option>' +
                            '<option value="traveler_selection"' + (snapshot.scope === 'traveler_selection' ? ' selected' : '') + '>Voyageurs sélectionnés</option>' +
                        '</select>' +
                    '</div>' +
                    '<div class="reservation-create__field">' +
                        '<label class="reservation-create__label">Quantité</label>' +
                        '<input type="number" class="reservation-create__input" data-extra-quantity min="0" step="1" value="' + snapshot.quantity + '">' +
                    '</div>' +
                '</div>' +
                '<div class="reservation-create__extra-travelers' + (snapshot.scope === 'traveler_selection' ? '' : ' d-none') + '" data-extra-travelers>' +
                    travelerHtml +
                '</div>' +
                '<div class="reservation-create__extra-total">Total extra : <strong data-extra-total>0 DH</strong></div>';

            container.appendChild(card);
        });

        bindExtrasEvents();
        syncFinancialSummary();
    }

    function bindExtrasEvents() {
        document.querySelectorAll('.reservation-create__extra-card').forEach(function (card) {
            var scope = card.querySelector('[data-extra-scope]');
            var quantity = card.querySelector('[data-extra-quantity]');
            var travelersBlock = card.querySelector('[data-extra-travelers]');

            function refreshCard() {
                if (scope && travelersBlock) {
                    travelersBlock.classList.toggle('d-none', scope.value !== 'traveler_selection');
                }
                var totalEl = card.querySelector('[data-extra-total]');
                if (totalEl) {
                    totalEl.textContent = formatMoney(extraCardTotal(card));
                }
                syncFinancialSummary();
            }

            if (scope) scope.addEventListener('change', refreshCard);
            if (quantity) quantity.addEventListener('input', refreshCard);
            card.querySelectorAll('.reservation-create-extra-cb').forEach(function (cb) {
                cb.addEventListener('change', refreshCard);
            });

            refreshCard();
        });
    }

    function extraCardTotal(card) {
        var quantity = Math.max(0, parseInt(card.querySelector('[data-extra-quantity]') && card.querySelector('[data-extra-quantity]').value || '0', 10) || 0);
        if (quantity < 1) {
            return 0;
        }

        var scope = String(card.querySelector('[data-extra-scope]') && card.querySelector('[data-extra-scope]').value || 'dossier');
        if (scope === 'traveler_selection') {
            return Array.prototype.slice.call(card.querySelectorAll('.reservation-create-extra-cb:checked')).reduce(function (sum, cb) {
                return sum + (parseNumber(cb.getAttribute('data-price')) * quantity);
            }, 0);
        }

        return parseNumber(card.getAttribute('data-extra-adult-price')) * quantity;
    }

    function collectExtras() {
        var selected = [];
        document.querySelectorAll('.reservation-create__extra-card').forEach(function (card) {
            var quantity = Math.max(0, parseInt(card.querySelector('[data-extra-quantity]') && card.querySelector('[data-extra-quantity]').value || '0', 10) || 0);
            if (quantity < 1) {
                return;
            }

            var scope = String(card.querySelector('[data-extra-scope]') && card.querySelector('[data-extra-scope]').value || 'dossier');
            var travelerKeys = [];
            var unitPrice = parseNumber(card.getAttribute('data-extra-adult-price'));
            var totalPrice = 0;
            var name = String(card.getAttribute('data-extra-name') || 'Extra');

            if (scope === 'traveler_selection') {
                var checked = Array.prototype.slice.call(card.querySelectorAll('.reservation-create-extra-cb:checked'));
                if (!checked.length) {
                    return;
                }
                travelerKeys = checked.map(function (cb) { return String(cb.getAttribute('data-traveler-id') || ''); });
                totalPrice = checked.reduce(function (sum, cb) {
                    return sum + (parseNumber(cb.getAttribute('data-price')) * quantity);
                }, 0);
            } else {
                totalPrice = unitPrice * quantity;
            }

            selected.push({
                voyage_extra_id: parseInt(card.getAttribute('data-extra-id') || '0', 10) || null,
                name: name,
                description: String(card.getAttribute('data-extra-description') || ''),
                unit_price: unitPrice,
                quantity: quantity,
                total_price: totalPrice,
                application_scope: scope,
                traveler_keys: travelerKeys
            });
        });

        var hidden = document.getElementById('reservation-create-extras-json');
        if (hidden) {
            hidden.value = JSON.stringify(selected);
        }

        return selected;
    }

    function extrasTotal() {
        return collectExtras().reduce(function (sum, item) {
            return sum + parseNumber(item.total_price);
        }, 0);
    }

    function financialSummary() {
        var travelerCount = getTravelerCount();
        var room = hotelRoomSummary();
        var unitPrice = getBaseUnitPrice();
        var totalBase = unitPrice * travelerCount;
        var extras = extrasTotal();
        var totalAmount = totalBase + room.roomSupplementTotal + extras;
        var paidAmount = parseNumber(document.getElementById('payment_amount') && document.getElementById('payment_amount').value);
        var remainingAmount = Math.max(0, totalAmount - paidAmount);

        return {
            travelerCount: travelerCount,
            unitPrice: unitPrice,
            totalBase: totalBase,
            roomSupplementTotal: room.roomSupplementTotal,
            extrasTotal: extras,
            totalAmount: totalAmount,
            paidAmount: paidAmount,
            remainingAmount: remainingAmount,
            paymentStatus: derivePaymentStatus(totalAmount, paidAmount),
            selectedRoomCapacity: room.selectedRoomCapacity,
            selectedRoomCount: room.selectedRoomCount,
            stockExceeded: room.stockExceeded,
            availableDepartureCapacity: getAvailableDepartureCapacity(),
            priceMissing: unitPrice <= 0,
            roomMode: getRoomMode()
        };
    }

    function setText(id, value) {
        var el = document.getElementById(id);
        if (el) el.textContent = value;
    }

    function syncFinancialSummary() {
        var summary = financialSummary();

        setText('create-summary-trip', getSelectedTripLabel());
        setText('create-final-trip', getSelectedTripLabel());
        setText('create-summary-departure', getSelectedDepartureLabel());
        setText('create-final-departure', getSelectedDepartureLabel());
        setText('create-summary-travelers', String(summary.travelerCount));
        setText('create-final-travelers', String(summary.travelerCount));
        setText('create-travelers-badge', String(summary.travelerCount));
        setText('create-summary-unit-price', summary.priceMissing ? '—' : formatMoney(summary.unitPrice));
        setText('create-summary-total', formatMoney(summary.totalAmount));
        setText('create-summary-paid', formatMoney(summary.paidAmount));
        setText('create-summary-remaining', formatMoney(summary.remainingAmount));
        setText('create-final-total', formatMoney(summary.totalAmount));
        setText('create-final-extras', formatMoney(summary.extrasTotal));
        setText('create-final-remaining', formatMoney(summary.remainingAmount));
        setText('create-financial-total-base', formatMoney(summary.totalBase));
        setText('create-financial-room-supplement', formatMoney(summary.roomSupplementTotal));
        setText('create-financial-extras', formatMoney(summary.extrasTotal));
        setText('create-financial-total-amount', formatMoney(summary.totalAmount));
        setText('create-financial-paid-amount', formatMoney(summary.paidAmount));
        setText('create-financial-remaining-amount', formatMoney(summary.remainingAmount));
        setText('create-financial-payment-status', summary.paymentStatus);
        setText('create-dossier-status-preview', summary.paidAmount > 0 ? 'En attente' : 'Brouillon');

        setText('reservation-total-travelers', String(summary.travelerCount));
        setText('reservation-total-capacity', String(summary.selectedRoomCapacity));
        setText('reservation-total-supplement', formatMoney(summary.roomSupplementTotal));
        setText('reservation-grand-total', formatMoney(summary.totalAmount));

        var totalBaseInput = document.getElementById('reservation-total-base-input');
        var roomSupplementInput = document.getElementById('reservation-room-supplement-total-input');
        var extrasTotalInput = document.getElementById('reservation-extras-total-input');
        var totalAmountInput = document.getElementById('reservation-total-amount-input');

        if (totalBaseInput) totalBaseInput.value = summary.totalBase.toFixed(2);
        if (roomSupplementInput) roomSupplementInput.value = summary.roomSupplementTotal.toFixed(2);
        if (extrasTotalInput) extrasTotalInput.value = summary.extrasTotal.toFixed(2);
        if (totalAmountInput) totalAmountInput.value = summary.totalAmount.toFixed(2);

        var paymentHelp = document.getElementById('create-payment-help');
        if (paymentHelp) {
            paymentHelp.textContent = summary.paidAmount > summary.totalAmount
                ? 'Le montant payé dépasse le total du dossier.'
                : 'Le montant payé ne peut pas dépasser le total du dossier.';
            paymentHelp.classList.toggle('is-error', summary.paidAmount > summary.totalAmount);
        }

        setAccommodationMode(summary.roomMode === 'places_only' ? 'places_only' : (summary.roomMode === 'blocked' ? 'blocked' : 'rooms'));

        var capacityError = document.getElementById('reservation-capacity-error');
        if (capacityError) {
            var hasCapacityIssue = summary.selectedRoomCapacity > 0 && summary.selectedRoomCapacity < summary.travelerCount;
            capacityError.classList.toggle('d-none', !hasCapacityIssue);
        }

        return summary;
    }

    function syncClientMode() {
        var newMode = document.getElementById('client_mode_new');
        var existingMode = document.getElementById('client_mode_existing');
        var newBlock = document.getElementById('new-client-block');
        var existingBlock = document.getElementById('existing-client-block');
        var existingSelect = document.getElementById('client_external_id');

        if (!newMode || !existingMode || !newBlock || !existingBlock) return;

        var useExisting = existingMode.checked;
        existingBlock.classList.toggle('d-none', !useExisting);
        newBlock.classList.toggle('d-none', useExisting);
        if (existingSelect) {
            existingSelect.required = useExisting;
        }
    }

    function syncVisaMode() {
        var checkbox = document.getElementById('visa_ok');
        var block = document.getElementById('assistant-visa-block');
        if (!checkbox || !block) return;
        block.classList.toggle('d-none', checkbox.checked);
    }

    function inlineErrorTarget() {
        var panel = currentPanel();
        if (!panel) return null;
        var alert = panel.querySelector('.reservation-create__inline-error');
        if (!alert) {
            alert = document.createElement('div');
            alert.className = 'reservation-create__alert reservation-create__alert--error reservation-create__inline-error';
            panel.insertBefore(alert, panel.firstChild);
        }
        return alert;
    }

    function showInlineError(message) {
        var alert = inlineErrorTarget();
        if (!alert) return;
        alert.innerHTML = '<strong>Validation</strong><p>' + message + '</p>';
    }

    function clearInlineError() {
        var panel = currentPanel();
        if (!panel) return;
        var alert = panel.querySelector('.reservation-create__inline-error');
        if (alert) {
            alert.remove();
        }
    }

    function validateStep(step) {
        var summary = syncFinancialSummary();
        clearInlineError();

        if (step === 1) {
            var tripSelect = document.getElementById('select-tour-id');
            var departureSelect = document.getElementById('reservation-departure-select');

            if (!tripSelect || !tripSelect.value) {
                showInlineError('Sélectionnez un voyage avant de continuer.');
                return false;
            }
            if (!departureSelect || !departureSelect.value) {
                showInlineError('Sélectionnez un départ avant de continuer.');
                return false;
            }
            if (summary.priceMissing) {
                showInlineError('Aucun prix configuré pour ce voyage/départ.');
                return false;
            }

            if (summary.roomMode === 'blocked') {
                showInlineError('Configuration incomplète : ajoutez les chambres pour ce départ.');
                return false;
            }

            if (summary.roomMode === 'places_only') {
                if (summary.availableDepartureCapacity <= 0) {
                    showInlineError('Ce départ n’a plus de places disponibles.');
                    return false;
                }
                if (summary.travelerCount > summary.availableDepartureCapacity) {
                    showInlineError('Stock insuffisant : il reste seulement ' + summary.availableDepartureCapacity + ' places.');
                    return false;
                }
            } else {
                if (summary.selectedRoomCount < 1) {
                    showInlineError('Sélectionnez au moins une chambre pour ce dossier.');
                    return false;
                }
                if (summary.stockExceeded) {
                    showInlineError('Le nombre de chambres demandé dépasse le stock disponible.');
                    return false;
                }
                if (summary.selectedRoomCapacity < summary.travelerCount) {
                    showInlineError('La capacité des chambres sélectionnées est insuffisante pour les voyageurs du dossier.');
                    return false;
                }
                if (summary.availableDepartureCapacity > 0 && summary.travelerCount > summary.availableDepartureCapacity) {
                    showInlineError('Le nombre de voyageurs dépasse le stock disponible sur ce départ.');
                    return false;
                }
            }
        }

        if (step === 2) {
            var existingMode = document.getElementById('client_mode_existing');
            if (existingMode && existingMode.checked) {
                if (!document.getElementById('client_external_id') || !document.getElementById('client_external_id').value) {
                    showInlineError('Sélectionnez un client existant.');
                    return false;
                }
            } else {
                if (!String(document.getElementById('client_first_name') && document.getElementById('client_first_name').value || '').trim()) {
                    showInlineError('Le prénom du client principal est obligatoire.');
                    return false;
                }
                if (!String(document.getElementById('client_last_name') && document.getElementById('client_last_name').value || '').trim()) {
                    showInlineError('Le nom du client principal est obligatoire.');
                    return false;
                }
                if (!String(document.getElementById('client_phone') && document.getElementById('client_phone').value || '').trim()) {
                    showInlineError('Le téléphone du client principal est obligatoire.');
                    return false;
                }
            }
        }

        if (step === 3) {
            if (summary.selectedRoomCapacity > 0 && summary.travelerCount > summary.selectedRoomCapacity) {
                showInlineError('Le nombre de voyageurs dépasse la capacité des chambres sélectionnées.');
                return false;
            }
            if (summary.availableDepartureCapacity > 0 && summary.travelerCount > summary.availableDepartureCapacity) {
                showInlineError('Le nombre de voyageurs dépasse la capacité disponible de ce départ.');
                return false;
            }
        }

        if (step === 5) {
            if (summary.paidAmount > summary.totalAmount) {
                showInlineError('Le montant payé ne peut pas dépasser le total du dossier.');
                return false;
            }
        }

        return true;
    }

    function setStep(step) {
        var panels = allPanels();
        if (!panels.length) return;

        var max = panels.length;
        var next = Math.max(1, Math.min(Number(step) || 1, max));
        currentStep = next;

        panels.forEach(function (panel) {
            var isActive = Number(panel.getAttribute('data-create-step')) === next;
            panel.classList.toggle('is-active', isActive);
            panel.hidden = !isActive;
        });

        document.querySelectorAll('[data-create-step-nav]').forEach(function (button) {
            var stepNumber = Number(button.getAttribute('data-create-step-nav'));
            button.classList.toggle('is-active', stepNumber === next);
            button.classList.toggle('is-complete', stepNumber < next);
        });

        syncFinancialSummary();
        clearInlineError();
    }

    function addCompanion() {
        var container = document.getElementById('companions-container');
        if (!container) return;

        var index = container.querySelectorAll('.companion-row').length;
        var row = document.createElement('div');
        row.className = 'companion-row reservation-create__companion';
        row.innerHTML =
            '<div class="reservation-create__companion-head">' +
                '<h4 class="reservation-create__companion-title">Accompagnant #' + (index + 1) + '</h4>' +
                '<button type="button" class="btn-remove-companion reservation-create__remove" aria-label="Supprimer">×</button>' +
            '</div>' +
            '<div class="reservation-create__grid reservation-create__grid--two">' +
                '<div class="reservation-create__field"><label class="reservation-create__label">Prénom</label><input type="text" name="passengers[' + index + '][first_name]" class="reservation-create__input"></div>' +
                '<div class="reservation-create__field"><label class="reservation-create__label">Nom</label><input type="text" name="passengers[' + index + '][last_name]" class="reservation-create__input"></div>' +
                '<div class="reservation-create__field"><label class="reservation-create__label">Type</label><select name="passengers[' + index + '][type]" class="reservation-create__input"><option value="adult">Adulte</option><option value="child">Enfant</option><option value="infant">Bébé</option></select></div>' +
                '<div class="reservation-create__field"><label class="reservation-create__label">Date de naissance</label><input type="date" name="passengers[' + index + '][birth_date]" class="reservation-create__input"></div>' +
                '<div class="reservation-create__field"><label class="reservation-create__label">Type document</label><input type="text" name="passengers[' + index + '][document_type]" class="reservation-create__input"></div>' +
                '<div class="reservation-create__field"><label class="reservation-create__label">N° document</label><input type="text" name="passengers[' + index + '][document_number]" class="reservation-create__input"></div>' +
            '</div>';
        container.appendChild(row);
        syncTravelersEmptyState();
        renderExtras();
        syncFinancialSummary();
    }

    function syncTravelersEmptyState() {
        var empty = document.getElementById('create-no-companions');
        if (!empty) return;
        empty.classList.toggle('d-none', document.querySelectorAll('#companions-container .companion-row').length > 0);
    }

    function bindDelegatedEvents() {
        var form = document.getElementById('reservation-create-form');
        if (!form) return;

        form.addEventListener('input', function (event) {
            var target = event.target;
            if (!target) return;

            if (target.matches('#client_first_name, #client_last_name, #client_phone, #client_email, #client_external_id, #payment_amount, input[name="base_price"], .reservation-room-count')) {
                syncFinancialSummary();
            }

            if (target.closest('#companions-container')) {
                syncTravelersEmptyState();
                renderExtras();
            }
        });

        form.addEventListener('change', function (event) {
            var target = event.target;
            if (!target) return;

            if (target.matches('#client_mode_new, #client_mode_existing')) {
                syncClientMode();
                renderExtras();
            }
            if (target.matches('#visa_ok')) {
                syncVisaMode();
            }
            if (target.matches('#select-tour-id, #reservation-departure-select, #client_external_id, .reservation-room-count, #payment_type, #payment_date')) {
                syncFinancialSummary();
                if (target.id === 'select-tour-id') {
                    renderExtras();
                }
            }
            if (target.closest('#companions-container')) {
                syncTravelersEmptyState();
                renderExtras();
            }
        });

        form.addEventListener('click', function (event) {
            var target = event.target;
            if (!target) return;

            if (target.id === 'btn-add-companion') {
                event.preventDefault();
                addCompanion();
            }

            if (target.classList.contains('btn-remove-companion')) {
                event.preventDefault();
                var row = target.closest('.companion-row');
                if (row) {
                    row.remove();
                    syncTravelersEmptyState();
                    renderExtras();
                    syncFinancialSummary();
                }
            }

            if (target.hasAttribute('data-create-next')) {
                event.preventDefault();
                if (validateStep(currentStep)) {
                    setStep(currentStep + 1);
                }
            }

            if (target.hasAttribute('data-create-prev')) {
                event.preventDefault();
                setStep(currentStep - 1);
            }

            if (target.hasAttribute('data-create-step-nav')) {
                event.preventDefault();
                var requested = Number(target.getAttribute('data-create-step-nav'));
                if (requested > currentStep && !validateStep(currentStep)) {
                    return;
                }
                setStep(requested);
            }
        });

        form.addEventListener('submit', function (event) {
            if (!validateStep(currentStep)) {
                event.preventDefault();
                return;
            }
            collectExtras();
            syncFinancialSummary();
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        extrasMap = parseJsonScript('reservation-create-extras-map', {});
        bindDelegatedEvents();
        syncClientMode();
        syncVisaMode();
        syncTravelersEmptyState();
        renderExtras();
        setStep(1);

        window.reservationCreateCollectExtras = collectExtras;
        window.reservationCreateGetExtrasTotal = function () {
            return financialSummary().extrasTotal;
        };
        window.reservationCreateGetHotelSummary = hotelRoomSummary;
        window.reservationCreateRecomputeTotals = syncFinancialSummary;

        // Expose setAccommodationMode globally and flush any queued calls
        if (typeof window.setAccommodationMode !== 'function') {
            window.setAccommodationMode = setAccommodationMode;
        } else {
            var _pending = window._reservation_setAccommodationMode_pending || [];
            window.setAccommodationMode = setAccommodationMode;
            if (Array.isArray(_pending) && _pending.length) {
                _pending.forEach(function (m) { try { setAccommodationMode(m); } catch (e) { /* ignore */ } });
                delete window._reservation_setAccommodationMode_pending;
            }
        }
        // Expose helper to allow inline templates to read selected departure label safely
        if (typeof window.getSelectedDepartureLabel !== 'function') {
            window.getSelectedDepartureLabel = getSelectedDepartureLabel;
        }
        if (typeof window.getAvailableDepartureCapacity !== 'function') {
            window.getAvailableDepartureCapacity = getAvailableDepartureCapacity;
        }
        if (typeof window.getRoomMode !== 'function') {
            window.getRoomMode = getRoomMode;
        }
    });
})();
