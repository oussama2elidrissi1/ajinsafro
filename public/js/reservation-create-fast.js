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
        } else if (step >= STEP_COUNT) {
            note.textContent = 'Vérifiez le dossier avant de confirmer la réservation.';
        } else {
            note.textContent = '';
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

    function refresh() {
        scheduled = false;
        var step = activeStep();
        var state = capacityState();

        renderStepper(step);
        renderCounters();
        renderSummaryBreakdown();
        renderCapacity(state);
        renderCompanions();
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
