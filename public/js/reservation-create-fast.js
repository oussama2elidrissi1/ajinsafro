/**
 * Réservation rapide — comportements propres à l'habillage « fast create ».
 *
 * Ce fichier ne recalcule aucun prix : il lit le prix unitaire du dossier
 * (#reservation-base-price, résolu côté Laravel par ReservationPricingService)
 * et se contente de le ventiler par catégorie de voyageur, exactement comme le
 * fait le serveur (prix unitaire × nombre de voyageurs). Les totaux affichés
 * restent ceux écrits par reservation-create.js.
 */
(function () {
    'use strict';

    var root = document.querySelector('.reservation-create--fast');
    if (!root) return;

    var STEP_COUNT = 4;
    var CATEGORIES = ['adult', 'child', 'infant'];

    /* ---------------------------------------------------------------- utils */

    function $(selector, scope) {
        return (scope || document).querySelector(selector);
    }

    function $$(selector, scope) {
        return Array.prototype.slice.call((scope || document).querySelectorAll(selector));
    }

    function formatMoney(value) {
        return (Math.round((Number(value) || 0) * 100) / 100).toLocaleString('fr-FR', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2
        }) + ' DH';
    }

    function parseNumber(value) {
        var parsed = parseFloat(String(value === null || value === undefined ? '' : value).replace(',', '.'));
        return isNaN(parsed) ? 0 : parsed;
    }

    function counterValue(category) {
        var input = document.getElementById('fast-counter-' + category);
        if (!input) return 0;
        var value = parseInt(input.value, 10);
        return isNaN(value) ? 0 : value;
    }

    function unitPrice() {
        return parseNumber(($('#reservation-base-price') || {}).value);
    }

    function availableCapacity() {
        var chip = document.getElementById('fast-offer-seats');
        if (!chip) return 0;
        var value = parseInt(chip.getAttribute('data-available-capacity'), 10);
        return isNaN(value) ? 0 : value;
    }

    /**
     * Voyageurs réellement facturés. reservation-create.js publie la liste dans
     * window.reservationState.travelers ; c'est elle qui alimente le total, donc
     * la ventilation ci-dessous en découle au lieu d'être recalculée à part.
     * Un accompagnant sans nom n'est pas un voyageur (même règle que
     * ReservationPricingService::countTravelers côté serveur).
     */
    function billableTravelers() {
        var state = window.reservationState;
        if (state && Array.isArray(state.travelers) && state.travelers.length) {
            return state.travelers.map(function (traveler) {
                return traveler && traveler.type ? String(traveler.type) : 'adult';
            });
        }

        var types = ['adult'];
        $$('#companions-container .companion-row').forEach(function (row) {
            var first = row.querySelector('input[name*="[first_name]"]');
            var last = row.querySelector('input[name*="[last_name]"]');
            var named = String((first && first.value) || '').trim() !== ''
                || String((last && last.value) || '').trim() !== '';
            if (!named) return;
            var select = row.querySelector('select[name*="[type]"]');
            types.push(select && select.value ? String(select.value) : 'adult');
        });
        return types;
    }

    function billableCounts() {
        return billableTravelers().reduce(function (counts, type) {
            var key = CATEGORIES.indexOf(type) === -1 ? 'adult' : type;
            counts[key] += 1;
            return counts;
        }, { adult: 0, child: 0, infant: 0 });
    }

    function declaredTotal() {
        return CATEGORIES.reduce(function (sum, category) {
            return sum + counterValue(category);
        }, 0);
    }

    function activeStep() {
        var panel = $('.reservation-create__panel.is-active[data-create-step]');
        var step = panel ? parseInt(panel.getAttribute('data-create-step'), 10) : 1;
        return isNaN(step) ? 1 : step;
    }

    /* ------------------------------------------- hauteur du header de coque */

    function measureHeader() {
        var header = document.querySelector('[data-ea-header], .ea-header, .agent-portal-header');
        if (!header) return;
        var height = Math.round(header.getBoundingClientRect().height);
        if (height > 0) {
            root.style.setProperty('--rcf-header-h', height + 'px');
        }
    }

    /* ------------------------------------------------------------- stepper  */

    function renderStepper(step) {
        $$('.reservation-create__steps--chevrons .reservation-create__step').forEach(function (button) {
            var number = parseInt(button.getAttribute('data-create-step-nav'), 10);
            if (isNaN(number)) return;

            var isCurrent = number === step;
            var isDone = number < step;

            button.classList.toggle('is-active', isCurrent);
            button.classList.toggle('is-complete', isDone);
            button.setAttribute('aria-selected', isCurrent ? 'true' : 'false');

            var index = button.querySelector('[data-step-index]');
            if (index) index.textContent = isDone ? '✓' : String(number);

            var kicker = button.querySelector('[data-step-kicker]');
            if (kicker) kicker.textContent = isDone ? 'Terminé' : (isCurrent ? 'En cours' : 'Étape ' + number);
        });
    }

    /* ----------------------------------------------- ventilation du résumé  */

    function renderSummaryBreakdown() {
        var price = unitPrice();
        var hasPrice = price > 0;
        var counts = billableCounts();
        var billable = counts.adult + counts.child + counts.infant;

        CATEGORIES.forEach(function (category) {
            var line = $('[data-summary-line="' + category + '"]');
            if (!line) return;

            var count = counts[category];
            // Les adultes restent visibles même à 1 ; enfants et bébés n'apparaissent
            // que lorsqu'ils sont présents, comme dans la maquette.
            line.hidden = category !== 'adult' && count <= 0;

            var countEl = line.querySelector('[data-summary-count="' + category + '"]');
            if (countEl) countEl.textContent = String(count);

            var amountEl = line.querySelector('[data-summary-amount="' + category + '"]');
            if (amountEl) amountEl.textContent = hasPrice ? formatMoney(price * count) : '—';
        });

        // Un accompagnant annoncé mais pas encore nommé n'entre pas dans le total :
        // on l'explique plutôt que de laisser un écart inexpliqué entre les compteurs
        // et le résumé.
        var pending = Math.max(0, declaredTotal() - billable);
        var pendingLine = $('[data-summary-line="pending"]');
        if (pendingLine) {
            pendingLine.hidden = pending <= 0;
            var pendingCount = pendingLine.querySelector('[data-summary-count="pending"]');
            if (pendingCount) pendingCount.textContent = String(pending);
        }
    }

    function renderCounters() {
        CATEGORIES.forEach(function (category) {
            var card = $('[data-counter-card="' + category + '"]');
            if (!card) return;
            card.classList.toggle('is-filled', category !== 'adult' && counterValue(category) > 0);
        });
    }

    /* --------------------------------------------------- capacité du départ */

    function capacityState() {
        var capacity = availableCapacity();
        var travelers = declaredTotal();

        return {
            capacity: capacity,
            travelers: travelers,
            // Une capacité à 0 signifie « non renseignée » sur ce départ : on
            // n'invente pas de blocage dans ce cas (même règle que le tunnel classique).
            isOver: capacity > 0 && travelers > capacity,
            remaining: capacity - travelers
        };
    }

    function capacityMessage(state) {
        return state.travelers + ' voyageur(s) pour ' + state.capacity + ' place(s) restante(s). '
            + 'Réduisez le groupe ou passez sur un autre départ.';
    }

    function renderCapacity(state) {
        var alert = document.getElementById('fast-capacity-alert');
        if (alert) {
            alert.hidden = !state.isOver;
            alert.textContent = state.isOver ? capacityMessage(state) : '';
        }

        if (!state.isOver) clearOwnedError();

        var chip = document.getElementById('fast-offer-seats');
        if (!chip) return;

        chip.classList.toggle('is-over', state.isOver);
        chip.classList.toggle('is-tight', !state.isOver && state.capacity > 0 && state.remaining <= 1);

        if (state.isOver) {
            chip.textContent = (state.travelers - state.capacity) + ' place(s) de trop';
        } else if (state.capacity > 0 && state.remaining === 0) {
            chip.textContent = 'Départ complet avec ce groupe';
        } else {
            chip.textContent = state.capacity + ' place(s) restante(s)';
        }
    }

    /* -------------------------------------------------------- accompagnants */

    function renderCompanions() {
        var travelers = declaredTotal();
        var rows = $$('#companions-container .companion-row').length;
        var missing = Math.max(0, travelers - 1 - rows);

        var hint = document.getElementById('fast-companion-hint');
        if (hint) {
            hint.textContent = travelers > 1
                ? (travelers - 1) + ' voyageur(s) à nommer en plus du client principal'
                : 'Le client principal voyage seul';
        }

        var empty = document.getElementById('fast-companion-empty');
        if (empty) {
            empty.textContent = missing > 0
                ? 'Le groupe compte ' + travelers + ' voyageurs : ajoutez les ' + missing + ' fiche(s) manquante(s).'
                : 'Augmentez le nombre de voyageurs pour ajouter des accompagnants.';
        }
    }

    /* ------------------------------------------------------ remise éventuelle */

    function renderDiscountLine() {
        var line = document.getElementById('create-summary-discount-line');
        var value = document.getElementById('create-summary-discount');
        if (!line || !value) return;

        var label = String(value.textContent || '').trim();
        line.hidden = label === '' || label === 'Aucune' || label === '—';
    }

    /* -------------------------------------------------------- CTA du résumé */

    function panelButton(step, selector) {
        var panel = $('.reservation-create__panel[data-create-step="' + step + '"]');
        return panel ? panel.querySelector(selector) : null;
    }

    function renderCta(step, state) {
        var primary = $('[data-fast-cta-primary]');
        var label = $('[data-fast-cta-label]');
        var secondary = $('[data-fast-cta-secondary]');
        var cancel = $('[data-fast-cta-cancel]');
        var note = $('[data-fast-cta-note]');

        if (label) label.textContent = step >= STEP_COUNT ? 'Confirmer la réservation' : 'Continuer';
        if (primary) primary.classList.toggle('is-blocked', state.isOver);
        if (secondary) secondary.hidden = step <= 1;
        if (cancel) cancel.hidden = step > 1;

        if (!note) return;
        note.classList.toggle('is-blocking', state.isOver);
        if (state.isOver) {
            note.textContent = 'Capacité dépassée : ajustez le groupe pour continuer.';
        } else if (step === 1) {
            note.textContent = 'Champs obligatoires : prénom, nom, téléphone, sexe.';
        } else if (step === 2) {
            note.textContent = 'Les chambres partielles partent en attente de jumelage.';
        } else if (step === 3) {
            note.textContent = 'Un acompte n’est pas obligatoire pour continuer.';
        } else {
            note.textContent = 'La confirmation génère le numéro de dossier et décompte les places.';
        }
    }

    // On ne nettoie que les messages que l'on a nous-mêmes posés, pour ne pas
    // effacer ceux de reservation-create.js.
    var ownedError = null;

    function showStepError(step, message) {
        var box = document.getElementById('step-' + step + '-errors');
        if (!box) return;
        box.textContent = message;
        box.hidden = false;
        ownedError = box;
        box.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function clearOwnedError() {
        if (!ownedError) return;
        ownedError.textContent = '';
        ownedError.hidden = true;
        ownedError = null;
    }

    function bindCta() {
        var primary = $('[data-fast-cta-primary]');
        var secondary = $('[data-fast-cta-secondary]');
        if (!primary) return;

        primary.addEventListener('click', function () {
            var step = activeStep();
            var state = capacityState();

            if (state.isOver) {
                showStepError(step, capacityMessage(state));
                return;
            }

            if (step >= STEP_COUNT) {
                var submit = panelButton(step, 'button[type="submit"]');
                if (submit) {
                    submit.click();
                } else {
                    var form = document.getElementById('reservation-create-form');
                    if (form) form.requestSubmit ? form.requestSubmit() : form.submit();
                }
                return;
            }

            var next = panelButton(step, '[data-create-next]');
            if (next) next.click();
        });

        if (secondary) {
            secondary.addEventListener('click', function () {
                var prev = panelButton(activeStep(), '[data-create-prev]');
                if (prev) prev.click();
            });
        }

        // Le relais est branché : les boutons d'étape d'origine peuvent disparaître.
        root.classList.add('is-cta-proxied');
    }

    /* ------------------------------------------------------ pièce d'identité */

    function bindDocsDisclosure() {
        var toggle = document.getElementById('fast-docs-toggle');
        var panel = document.getElementById('fast-docs-panel');
        if (!toggle || !panel) return;

        toggle.addEventListener('click', function () {
            var willOpen = panel.hidden;
            panel.hidden = !willOpen;
            toggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');

            var label = toggle.querySelector('[data-docs-label]');
            if (label) label.textContent = willOpen ? 'Masquer' : 'Renseigner';

            var hint = toggle.querySelector('[data-docs-hint]');
            if (hint) {
                hint.textContent = willOpen
                    ? 'Requise pour le visa et les vols internationaux.'
                    : 'Facultative à cette étape, exigée avant confirmation.';
            }
        });
    }

    /* ------------------------------------------------- extras (étape 2) ---- */

    function extraCards() {
        return $$('.reservation-fast-extra');
    }

    function scopeInput(card) {
        return card.querySelector('[data-extra-scope]');
    }

    /** Notifie reservation-create.js, seul responsable des totaux. */
    function commitScope(card, value, checkAll) {
        var input = scopeInput(card);
        if (!input) return;
        input.value = value;
        if (checkAll) {
            card.querySelectorAll('.reservation-create-extra-cb').forEach(function (cb) {
                cb.checked = true;
            });
        }
        input.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function bindExtras() {
        document.addEventListener('click', function (event) {
            var card = event.target.closest && event.target.closest('.reservation-fast-extra');
            if (!card) return;

            var toggle = event.target.closest('[data-extra-enabled]');
            if (toggle) {
                event.preventDefault();
                var body = card.querySelector('.reservation-fast-extra__body');
                if (!body) return;
                var opening = body.hidden;
                body.hidden = !opening;
                if (opening) {
                    // À l'activation, l'extra s'applique à tout le dossier.
                    commitScope(card, 'per_traveler', true);
                } else {
                    // Désactivé : plus aucun voyageur, donc ignoré par collectExtras.
                    card.querySelectorAll('.reservation-create-extra-cb').forEach(function (cb) {
                        cb.checked = false;
                    });
                    commitScope(card, 'traveler_selection', false);
                }
                return;
            }

            var scopeButton = event.target.closest('[data-extra-scope-set]');
            if (scopeButton) {
                event.preventDefault();
                var next = scopeButton.getAttribute('data-extra-scope-set');
                commitScope(card, next, next === 'per_traveler');
                return;
            }

            var more = event.target.closest('[data-extra-more]');
            if (more) {
                event.preventDefault();
                var desc = card.querySelector('[data-extra-desc]');
                if (!desc) return;
                var clamped = desc.classList.toggle('is-clamped');
                more.textContent = clamped ? 'Lire la suite' : 'Réduire';
            }
        });

        // Décocher un voyageur alors que l'extra vise tout le dossier bascule
        // naturellement en « Au choix ».
        document.addEventListener('change', function (event) {
            var cb = event.target;
            if (!cb || !cb.classList || !cb.classList.contains('reservation-create-extra-cb')) return;
            var card = cb.closest('.reservation-fast-extra');
            if (!card) return;
            var input = scopeInput(card);
            if (input && input.value === 'per_traveler') {
                commitScope(card, 'traveler_selection', false);
            }
        });
    }

    function renderExtrasCount() {
        var count = String(extraCards().filter(function (card) {
            return card.classList.contains('is-on');
        }).length);
        ['fast-extras-count', 'fast-extras-count-summary'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) el.textContent = count;
        });
    }

    /* --------------------------------------------- paiement (étape 3) ------ */

    function hiddenAmount(id) {
        return parseNumber((document.getElementById(id) || {}).value);
    }

    /**
     * Le tunnel de création n'enregistre qu'un règlement (payment_amount) ;
     * « déjà encaissé » vaut donc 0 sur un dossier neuf et la barre distingue
     * ce qui est en cours de saisie de ce qui restera dû.
     */
    function paymentState() {
        var total = hiddenAmount('reservation-total-amount-input');
        var paid = 0;
        var typed = Math.max(0, parseNumber((document.getElementById('payment_amount') || {}).value));
        var pending = total > 0 ? Math.min(typed, total) : typed;
        return {
            total: total,
            paid: paid,
            pending: pending,
            due: Math.max(0, total - paid - pending),
            over: total > 0 && typed > total
        };
    }

    function renderPayment() {
        var state = paymentState();
        var pct = function (value) {
            return state.total > 0 ? Math.min(100, (value / state.total) * 100) + '%' : '0%';
        };

        var bar = document.getElementById('fast-pay-bar-paid');
        if (bar) bar.style.width = pct(state.paid);
        var barPending = document.getElementById('fast-pay-bar-pending');
        if (barPending) barPending.style.width = pct(state.pending);

        var set = function (id, value) {
            var el = document.getElementById(id);
            if (el) el.textContent = value;
        };
        set('fast-pay-paid', formatMoney(state.paid));
        set('fast-pay-pending', formatMoney(state.pending));
        set('fast-pay-due', formatMoney(state.due));
        set('fast-pay-after', formatMoney(state.due));
        set('fast-pay-subtitle', 'Total ' + formatMoney(state.total) + ' · ' + formatMoney(state.pending) + ' en saisie');

        var pill = document.getElementById('fast-pay-state');
        if (pill) {
            var label = 'AUCUN RÈGLEMENT';
            var tone = 'is-none';
            if (state.total > 0 && state.pending >= state.total) {
                label = 'SOLDÉ À LA CONFIRMATION';
                tone = 'is-settled';
            } else if (state.pending > 0) {
                label = 'ACOMPTE EN SAISIE';
                tone = 'is-partial';
            }
            pill.textContent = label;
            pill.className = 'reservation-fast-pay-state ' + tone;
        }

        $$('[data-pay-preset]').forEach(function (button) {
            var ratio = button.getAttribute('data-pay-preset');
            var amountEl = button.querySelector('.reservation-fast-pay-preset__amount');
            if (ratio === 'free') {
                var isPreset = ['0.3', '0.5', '1'].some(function (r) {
                    return Math.round(state.total * parseFloat(r)) === Math.round(state.pending);
                });
                button.classList.toggle('is-active', state.pending > 0 && !isPreset);
                return;
            }
            var value = Math.round(state.total * parseFloat(ratio));
            if (amountEl) amountEl.textContent = state.total > 0 ? formatMoney(value) : '—';
            button.classList.toggle('is-active', state.total > 0 && Math.round(state.pending) === value);
        });

        var capHint = document.getElementById('fast-pay-cap-hint');
        if (capHint) {
            capHint.textContent = state.over
                ? 'Le montant saisi dépasse le total du dossier (' + formatMoney(state.total) + ').'
                : 'Le montant ne peut pas dépasser le total du dossier (' + formatMoney(state.total) + ').';
            capHint.classList.toggle('is-error', state.over);
        }
    }

    function bindPayment() {
        document.addEventListener('click', function (event) {
            var preset = event.target.closest && event.target.closest('[data-pay-preset]');
            if (!preset) return;
            event.preventDefault();
            var input = document.getElementById('payment_amount');
            if (!input) return;
            var ratio = preset.getAttribute('data-pay-preset');
            if (ratio === 'free') {
                input.focus();
                input.select();
                return;
            }
            var state = paymentState();
            input.value = String(Math.round(state.total * parseFloat(ratio)));
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
        });

        // Nom du fichier choisi, dans la zone de dépôt.
        document.addEventListener('change', function (event) {
            var input = event.target;
            if (!input || !input.classList || !input.classList.contains('reservation-fast-dropzone__input')) return;
            var zone = input.closest('.reservation-fast-dropzone');
            var hint = zone && zone.querySelector('[data-dropzone-hint]');
            if (!hint) return;
            if (!hint.dataset.defaultHint) hint.dataset.defaultHint = hint.textContent;
            var files = input.files ? Array.prototype.slice.call(input.files) : [];
            zone.classList.toggle('is-filled', files.length > 0);
            hint.textContent = files.length
                ? files.map(function (file) { return file.name; }).join(' · ')
                : hint.dataset.defaultHint;
        });
    }

    /* ------------------------------------------ vérification (étape 4) ----- */

    function principalName() {
        var selected = document.getElementById('client-search-selected');
        var existing = document.getElementById('client_mode_existing');
        if (existing && existing.checked && selected && !selected.classList.contains('d-none')) {
            var label = document.getElementById('client-search-selected-label');
            return (label && label.textContent.trim()) || 'Client existant';
        }
        var first = (document.getElementById('client_first_name') || {}).value || '';
        var last = (document.getElementById('client_last_name') || {}).value || '';
        return [first, last].map(function (part) { return String(part).trim(); }).filter(Boolean).join(' ');
    }

    function namedCompanions() {
        return $$('#companions-container .companion-row').filter(function (row) {
            var first = row.querySelector('input[name*="[first_name]"]');
            var last = row.querySelector('input[name*="[last_name]"]');
            return String((first && first.value) || '').trim() !== ''
                || String((last && last.value) || '').trim() !== '';
        }).length;
    }

    function setReview(key, value, tone) {
        var el = $('[data-review-line="' + key + '"]');
        if (!el) return;
        el.textContent = value;
        el.classList.remove('is-warn', 'is-ok', 'is-danger');
        if (tone) el.classList.add(tone);
    }

    function renderReview() {
        if (!$('#fast-review')) return;

        var counts = billableCounts();
        var declared = declaredTotal();
        var named = namedCompanions();
        var expected = Math.max(0, declared - 1);

        setReview('client-name', principalName() || 'À renseigner', principalName() ? null : 'is-warn');
        setReview('travelers', declared + ' (' + counts.adult + 'A / ' + counts.child + 'E / ' + counts.infant + 'B)');
        setReview('companions', named + ' / ' + expected, named < expected ? 'is-warn' : 'is-ok');

        var allocations = (window.reservationState && window.reservationState.roomAllocations) || [];
        var beds = allocations.reduce(function (sum, a) { return sum + (parseInt(a.capacity, 10) || 0); }, 0);
        var occupied = allocations.reduce(function (sum, a) { return sum + ((a.traveler_keys || []).length); }, 0);
        setReview('rooms-count', allocations.length + ' chambre(s)');
        setReview('beds', occupied + ' / ' + beds);

        var pill = document.getElementById('rooming-status-pill');
        var roomingLabel = pill ? pill.textContent.replace(/^ROOMING\s*/i, '').toLowerCase() : '—';
        var roomingWarn = pill ? !pill.className.includes('is-complete') : true;
        setReview('rooming', roomingLabel || '—', roomingWarn ? 'is-warn' : 'is-ok');

        var extrasOn = extraCards().filter(function (card) { return card.classList.contains('is-on'); }).length;
        setReview('extras-count', extrasOn + ' extra(s)');
        setReview('extras-total', formatMoney(hiddenAmount('reservation-extras-total-input')));
        setReview('room-supplement', formatMoney(hiddenAmount('reservation-room-supplement-total-input')));

        var pay = paymentState();
        setReview('total', formatMoney(pay.total));
        setReview('paid', formatMoney(pay.pending), pay.pending > 0 ? 'is-ok' : null);
        setReview('due', formatMoney(pay.due), pay.due > 0 ? 'is-danger' : 'is-ok');
    }

    /**
     * Contrôles déduits : ils ne créent aucune donnée, ils rendent visible ce qui
     * manque encore dans ce qui a déjà été saisi.
     */
    function derivedChecks() {
        var docType = (document.getElementById('client_document_type') || {}).value || '';
        var docNumber = String((document.getElementById('client_document_number') || {}).value || '').trim();
        var phone = String((document.getElementById('client_phone') || {}).value || '').trim();
        var email = String((document.getElementById('client_email') || {}).value || '').trim();
        var usingExisting = (document.getElementById('client_mode_existing') || {}).checked;
        var declared = declaredTotal();
        var named = namedCompanions() + 1;

        return {
            identity: usingExisting
                ? { ok: true, tag: 'Fiche client', hint: 'Reprise depuis la fiche du client existant.' }
                : {
                    ok: !!(docType && docNumber),
                    tag: docType && docNumber ? 'OK' : 'Manquant',
                    hint: docType && docNumber
                        ? 'Document renseigné à l’étape 1.'
                        : 'Renseignez le type et le numéro à l’étape 1.'
                },
            contact: usingExisting
                ? { ok: true, tag: 'Fiche client', hint: 'Reprise depuis la fiche du client existant.' }
                : {
                    // L'email est facultatif côté serveur : seul le téléphone bloque.
                    ok: !!phone,
                    tag: phone ? (email ? 'OK' : 'OK · sans email') : 'Manquant',
                    hint: phone
                        ? (email ? 'Téléphone et email renseignés.' : 'Téléphone renseigné ; email facultatif, absent.')
                        : 'Le téléphone du titulaire est obligatoire.'
                },
            travelers: {
                ok: named >= declared,
                tag: named >= declared ? 'OK' : (declared - named) + ' à nommer',
                hint: named >= declared
                    ? declared + ' voyageur(s) nommé(s) sur ' + declared + '.'
                    : 'Le groupe annonce ' + declared + ' place(s) pour ' + named + ' fiche(s) nommée(s).'
            }
        };
    }

    function renderChecks() {
        var container = $('.reservation-fast-checks');
        if (!container) return;

        var results = derivedChecks();
        var done = 0;
        var total = 0;

        Object.keys(results).forEach(function (key) {
            var row = $('[data-derived-check="' + key + '"]');
            if (!row) return;
            var result = results[key];
            total += 1;
            if (result.ok) done += 1;
            row.classList.toggle('is-ok', result.ok);
            var tag = row.querySelector('[data-check-tag]');
            if (tag) tag.textContent = result.tag;
            var hint = row.querySelector('[data-check-hint]');
            if (hint) hint.textContent = result.hint;
        });

        var visa = document.getElementById('visa_ok');
        var visaRow = $('[data-check="visa"]');
        if (visaRow && visa) visaRow.classList.toggle('is-ok', visa.checked);

        var hintEl = document.getElementById('fast-checks-hint');
        if (hintEl) {
            hintEl.textContent = done === total
                ? 'Tous les contrôles déduits du dossier sont au vert.'
                : done + ' / ' + total + ' contrôles au vert — les points restants n’empêchent pas la confirmation.';
        }
    }

    /* ---------------------------------------------------- recherche client  */

    function bindClientSearchButton() {
        var button = document.getElementById('reservation-client-search-submit');
        var input = document.getElementById('reservation-client-search');
        if (!button || !input) return;

        button.addEventListener('click', function () {
            // reservation-create.js écoute l'événement « input » délégué au formulaire.
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.focus();
        });
    }

    /* ------------------------------------------------------------- refresh  */

    var scheduled = false;
    var firstRoomingEntryCleared = false;

    function disableFastAutoRooming() {
        document.querySelectorAll('#btn-auto-rooming, #btn-rooming-auto').forEach(function (button) {
            button.remove();
        });
    }

    function clearAutoRoomingOnFirstEntry() {
        if (firstRoomingEntryCleared || activeStep() !== 2) return;
        firstRoomingEntryCleared = true;
        disableFastAutoRooming();
        if (typeof window.resetReservationDownstream === 'function') {
            window.resetReservationDownstream({});
            return;
        }
        if (window.reservationState) {
            window.reservationState.roomAllocations = [];
        }
        var hidden = document.getElementById('reservation-room-allocations-json');
        if (hidden) hidden.value = '[]';
    }

    function refresh() {
        scheduled = false;
        disableFastAutoRooming();
        clearAutoRoomingOnFirstEntry();
        var step = activeStep();
        var state = capacityState();

        renderStepper(step);
        renderCounters();
        renderSummaryBreakdown();
        renderCapacity(state);
        renderCompanions();
        renderExtrasCount();
        renderPayment();
        renderReview();
        renderChecks();
        renderDiscountLine();
        renderCta(step, state);
    }

    function schedule() {
        if (scheduled) return;
        scheduled = true;
        window.requestAnimationFrame(refresh);
    }

    function boot() {
        measureHeader();
        bindCta();
        bindDocsDisclosure();
        bindClientSearchButton();
        bindExtras();
        bindPayment();

        // Les handlers de reservation-create.js s'exécutent d'abord (phase de
        // bouillonnement) : on se resynchronise juste après.
        ['input', 'change', 'click'].forEach(function (type) {
            document.addEventListener(type, schedule);
        });

        window.addEventListener('resize', function () {
            measureHeader();
            schedule();
        });

        if (typeof ResizeObserver !== 'undefined') {
            var header = document.querySelector('[data-ea-header], .ea-header, .agent-portal-header');
            if (header) new ResizeObserver(measureHeader).observe(header);
        }

        // Le total du résumé est écrit par reservation-create.js : on suit ses
        // mutations pour rafraîchir la ventilation sans dupliquer sa logique.
        var total = document.getElementById('create-summary-total');
        if (total && typeof MutationObserver !== 'undefined') {
            new MutationObserver(schedule).observe(total, { childList: true, characterData: true, subtree: true });
        }

        refresh();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
