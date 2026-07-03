(function (window, document) {
    'use strict';

    if (window.VoyageEditorRuntime) {
        return;
    }

    var runtime = {
        ownsTabs: true,
        ownsProgrammeBuilder: true,
        init: initVoyageEditor,
        initVoyageTabs: initVoyageTabs,
        openVoyageTab: openVoyageTab,
        initProgrammeBuilder: initProgrammeBuilder,
        initActivitiesModal: initActivitiesModal,
        initVolsForm: initVolsForm,
        initHotelsForm: initHotelsForm,
        initTransfertsForm: initTransfertsForm
    };

    var state = {
        initialized: false,
        tabsBound: false,
        programmeBound: false,
        programmeObserver: null,
        programmeIntegrityScheduled: false,
        flightsBound: false,
        flightsObserver: null,
        programmeManagerDiagnosticsBound: false,
        draggedProgrammeCard: null
    };

    window.VoyageEditorRuntime = runtime;
    window.openVoyageTab = openVoyageTab;
    window.initVoyageTabs = initVoyageTabs;
    window.initProgrammeBuilder = initProgrammeBuilder;
    window.initActivitiesModal = initActivitiesModal;
    window.initVolsForm = initVolsForm;
    window.initHotelsForm = initHotelsForm;
    window.initTransfertsForm = initTransfertsForm;

    onReady(initVoyageEditor);

    function onReady(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback, { once: true });
        } else {
            callback();
        }
    }

    function initVoyageEditor() {
        if (state.initialized) {
            refreshActiveModule();
            return runtime;
        }

        state.initialized = true;
        initVoyageTabs();
        initProgrammeBuilder();
        initActivitiesModal();
        initVolsForm();
        initHotelsForm();
        initTransfertsForm();

        document.addEventListener('voyage:tab-opened', function (event) {
            var tabId = event && event.detail ? event.detail.tabId : '';
            refreshModule(tabId);
        });

        return runtime;
    }

    function initVoyageTabs() {
        var context = getTabsContext();
        if (!context) {
            return null;
        }

        bindTabEvents(context);
        syncTabState(resolveInitialTab(context), { updateHash: false, fromBootstrapEvent: false });

        return context;
    }

    function bindTabEvents(context) {
        if (state.tabsBound) {
            return;
        }

        state.tabsBound = true;

        context.links.forEach(function (link) {
            link.addEventListener('click', function (event) {
                event.preventDefault();
                openVoyageTab(link.getAttribute('href'), { updateHash: true });
            });

            link.addEventListener('shown.bs.tab', function (event) {
                syncTabState(event.target.getAttribute('href'), {
                    updateHash: true,
                    fromBootstrapEvent: true
                });
            });
        });

        window.addEventListener('hashchange', function () {
            var tabId = normalizeTabId(window.location.hash);
            if (tabId) {
                openVoyageTab(tabId, { updateHash: false });
            }
        });
    }

    function openVoyageTab(tabId, options) {
        var context = getTabsContext();
        var normalizedTabId = normalizeTabId(tabId);

        if (!context || !normalizedTabId || !context.linksById[normalizedTabId]) {
            return false;
        }

        if (window.bootstrap && window.bootstrap.Tab && !optionsFromBootstrap(options)) {
            window.bootstrap.Tab.getOrCreateInstance(context.linksById[normalizedTabId]).show();
            return true;
        }

        syncTabState(normalizedTabId, options || {});
        return true;
    }

    function syncTabState(tabId, options) {
        var context = getTabsContext();
        var normalizedTabId = normalizeTabId(tabId);

        if (!context || !normalizedTabId || !context.panesById[normalizedTabId]) {
            return;
        }

        context.links.forEach(function (link) {
            var isActive = normalizeTabId(link.getAttribute('href')) === normalizedTabId;
            link.classList.toggle('active', isActive);
            link.setAttribute('aria-selected', isActive ? 'true' : 'false');
            link.tabIndex = isActive ? 0 : -1;
        });

        context.panes.forEach(function (pane) {
            var isActive = pane.id === normalizedTabId;
            pane.classList.toggle('active', isActive);
            pane.classList.toggle('show', isActive);
            pane.hidden = !isActive;

            if (isActive) {
                pane.style.display = 'block';
                pane.removeAttribute('aria-hidden');
            } else {
                pane.style.display = 'none';
                pane.setAttribute('aria-hidden', 'true');
            }
        });

        if (options && options.updateHash) {
            updateHash(normalizedTabId);
        }

        ensureActiveTabVisible(context, normalizedTabId);
        dispatch('voyage:tab-opened', { tabId: normalizedTabId });
    }

    function getTabsContext() {
        var links = Array.prototype.slice.call(document.querySelectorAll('.ve-nav-tabs [data-bs-toggle="tab"][href^="#"]'));
        var panes = Array.prototype.slice.call(document.querySelectorAll('.ve-tab-content > .tab-pane[id]'));

        if (!links.length || !panes.length) {
            return null;
        }

        var linksById = {};
        var panesById = {};

        links.forEach(function (link) {
            linksById[normalizeTabId(link.getAttribute('href'))] = link;
        });

        panes.forEach(function (pane) {
            panesById[pane.id] = pane;
        });

        return {
            links: links,
            panes: panes,
            linksById: linksById,
            panesById: panesById
        };
    }

    function ensureActiveTabVisible(context, tabId) {
        var activeLink = context && context.linksById ? context.linksById[tabId] : null;
        var scroller = activeLink ? activeLink.closest('.ve-tab-scroll') : null;

        if (!activeLink || !scroller) {
            return;
        }

        window.requestAnimationFrame(function () {
            var padding = 12;
            var linkStart = activeLink.offsetLeft;
            var linkEnd = linkStart + activeLink.offsetWidth;
            var viewStart = scroller.scrollLeft;
            var viewEnd = viewStart + scroller.clientWidth;

            if (linkStart - viewStart < padding) {
                scroller.scrollLeft = Math.max(0, linkStart - padding);
                return;
            }

            if (viewEnd - linkEnd < padding) {
                scroller.scrollLeft = Math.max(0, linkEnd - scroller.clientWidth + padding);
            }
        });
    }

    function resolveInitialTab(context) {
        var params = new URLSearchParams(window.location.search);
        var candidates = [
            normalizeTabId(params.get('tab')),
            normalizeTabId(window.location.hash),
            findTabIdFromErrors(),
            findActiveTabId(context),
            context.links.length ? normalizeTabId(context.links[0].getAttribute('href')) : ''
        ];

        for (var i = 0; i < candidates.length; i++) {
            if (candidates[i] && context.panesById[candidates[i]]) {
                return candidates[i];
            }
        }

        return '';
    }

    function findTabIdFromErrors() {
        var errorItems = Array.prototype.slice.call(document.querySelectorAll('.alert.alert-danger li'));
        var joinedErrors = errorItems.map(function (item) {
            return (item.textContent || '').toLowerCase();
        }).join(' ');

        if (!joinedErrors) {
            return '';
        }

        if (joinedErrors.indexOf('programme_days') !== -1) return 'program-days';
        if (joinedErrors.indexOf('tour_activities') !== -1) return 'activities';
        if (joinedErrors.indexOf('flights') !== -1) return 'flights';
        if (joinedErrors.indexOf('travel_dates') !== -1) return 'availability';
        if (joinedErrors.indexOf('departure_allocations') !== -1) return 'hotels';
        if (joinedErrors.indexOf('tour_hotels') !== -1) return 'hotels';
        if (joinedErrors.indexOf('tour_transfer') !== -1) return 'transfers';

        return '';
    }

    function findActiveTabId(context) {
        var activeLink = context.links.find(function (link) {
            return link.classList.contains('active');
        });

        return activeLink ? normalizeTabId(activeLink.getAttribute('href')) : '';
    }

    function updateHash(tabId) {
        if (!tabId) {
            return;
        }

        if (window.location.hash === '#' + tabId) {
            return;
        }

        if (window.history && window.history.replaceState) {
            window.history.replaceState(null, document.title, window.location.pathname + window.location.search + '#' + tabId);
        } else {
            window.location.hash = tabId;
        }
    }

    function normalizeTabId(value) {
        if (!value) {
            return '';
        }

        return String(value).replace(/^#/, '').trim();
    }

    function optionsFromBootstrap(options) {
        return !!(options && options.fromBootstrapEvent);
    }

    function refreshActiveModule() {
        var context = getTabsContext();
        if (!context) {
            return;
        }

        refreshModule(resolveInitialTab(context));
    }

    function refreshModule(tabId) {
        switch (tabId) {
            case 'program-days':
                initProgrammeBuilder();
                ensureFirstProgrammeDayVisible();
                break;
            case 'activities':
                initActivitiesModal();
                break;
            case 'flights':
                initVolsForm();
                break;
            case 'hotels':
                initHotelsForm();
                break;
            case 'transfers':
                initTransfertsForm();
                break;
        }
    }

    function initActivitiesModal() {
        var modal = document.getElementById('activitiesCatalogModal');
        if (modal && window.bootstrap && window.bootstrap.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(modal);
        }

        return {
            trigger: document.getElementById('btn-open-activities-modal'),
            modal: modal
        };
    }

    function initVolsForm() {
        var pane = document.getElementById('flights');
        var form = document.getElementById('edit-voyage-form');

        if (!pane || !form) {
            return pane;
        }

        enableFlightOptionsForSubmit();

        if (!state.flightsBound) {
            state.flightsBound = true;

            form.addEventListener('submit', function () {
                disableDrawerFlightOptionsForSubmit();
                enableFlightOptionsForSubmit();
            }, true);

            if (window.MutationObserver) {
                state.flightsObserver = new window.MutationObserver(function () {
                    enableFlightOptionsForSubmit();
                });
                state.flightsObserver.observe(form, { childList: true, subtree: true });
            }
        }

        return pane;
    }

    function initHotelsForm() {
        return document.getElementById('hotels');
    }

    function initTransfertsForm() {
        return document.getElementById('transfers');
    }

    function initProgrammeBuilder() {
        var accordion = document.getElementById('accordionProgrammeDays');
        if (!accordion) {
            return null;
        }

        normalizeProgrammeCards();
        bindProgrammeEvents(accordion);
        bindProgrammeManagerDiagnostics(accordion);
        bindProgrammeIntegrityObserver(accordion);
        ensureFirstProgrammeDayVisible();

        return accordion;
    }

    function bindProgrammeEvents(accordion) {
        if (state.programmeBound) {
            return;
        }

        state.programmeBound = true;

        var addButton = document.getElementById('btn-add-program-day');
        var addButtonEmpty = document.getElementById('btn-add-program-day-empty');
        var form = document.getElementById('edit-voyage-form');

        if (addButton) {
            addButton.addEventListener('click', function () {
                addProgrammeDay();
            });
        }

        if (addButtonEmpty) {
            addButtonEmpty.addEventListener('click', function () {
                addProgrammeDay();
            });
        }

        accordion.addEventListener('click', function (event) {
            var removeButton = event.target.closest('.btn-remove-program-day');
            if (removeButton) {
                event.preventDefault();
                removeProgrammeDay(removeButton.closest('.programme-day-card'));
            }
        });

        accordion.addEventListener('input', function (event) {
            if (!event.target.matches('input[name$="[day_title]"]')) {
                return;
            }

            var card = event.target.closest('.programme-day-card');
            if (!card) {
                return;
            }

            var cardIndex = parseInt(card.getAttribute('data-day-index') || '0', 10);
            updateProgrammeCardHeader(card, cardIndex);
        });

        accordion.addEventListener('dragstart', function (event) {
            var card = event.target.closest('.programme-day-card');
            if (!card || !event.target.closest('.drag-handle')) {
                event.preventDefault();
                return;
            }

            state.draggedProgrammeCard = card;
            card.classList.add('programme-day-card--dragging');
            card.classList.add('opacity-50');
            // Keep the dragged card in normal flow (no fixed pixel widths).
            // Some browsers can leave stale inline styles if drag is cancelled/interrupted.
            card.style.width = '100%';
            card.style.maxWidth = '100%';
            card.style.transform = 'none';
            card.style.position = '';
            card.style.left = '';
            card.style.right = '';
            card.style.top = '';
            card.style.marginLeft = '';
            card.style.marginRight = '';
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', card.getAttribute('data-day-index') || '');
        });

        accordion.addEventListener('dragover', function (event) {
            var card = event.target.closest('.programme-day-card');
            if (!card || !state.draggedProgrammeCard || card === state.draggedProgrammeCard) {
                return;
            }

            event.preventDefault();
            card.classList.add('border-primary');
            event.dataTransfer.dropEffect = 'move';
        });

        accordion.addEventListener('dragleave', function (event) {
            var card = event.target.closest('.programme-day-card');
            if (card) {
                card.classList.remove('border-primary');
            }
        });

        accordion.addEventListener('drop', function (event) {
            var card = event.target.closest('.programme-day-card');
            if (!card || !state.draggedProgrammeCard || card === state.draggedProgrammeCard) {
                return;
            }

            event.preventDefault();
            card.classList.remove('border-primary');

            var nextSibling = card.nextElementSibling;
            accordion.insertBefore(state.draggedProgrammeCard, nextSibling);
            cleanupDraggedProgrammeCard();
            normalizeProgrammeCards();
        });

        accordion.addEventListener('dragend', cleanupDraggedProgrammeCard);
        // Defensive cleanup for interrupted drags (ESC, drop outside, blur, etc.)
        document.addEventListener('drop', cleanupDraggedProgrammeCard, true);
        document.addEventListener('dragend', cleanupDraggedProgrammeCard, true);
        window.addEventListener('blur', cleanupDraggedProgrammeCard, true);
        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState !== 'visible') {
                cleanupDraggedProgrammeCard();
            }
        });
        document.addEventListener('keydown', function (e) {
            if (e && (e.key === 'Escape' || e.key === 'Esc')) {
                cleanupDraggedProgrammeCard();
            }
        }, true);

        if (form) {
            // Ensure programme days payload is always submitted as JSON.
            // This avoids PHP input limits (max_input_vars) truncating programme_days[*] fields,
            // which can lead to only the first few days being received server-side.
            form.addEventListener('submit', function () {
                try {
                    syncProgrammeDuration();
                    syncProgrammeDaysPayloadForSubmit(form);
                } catch (e) {
                    // Never block submit; server-side can still rely on legacy fields if needed.
                }
            }, true);
        }

    }

    function syncProgrammeDaysPayloadForSubmit(form) {
        if (!form) {
            return [];
        }

        var payloadField = document.getElementById('programme-days-payload');
        var programmeDays = buildProgrammeDaysPayloadFromDom();
        var isV2Form = !!(form.closest('.v2-page') || form.hasAttribute('data-v2-current-step'));

        if (payloadField) {
            payloadField.value = JSON.stringify(programmeDays);
        }

        if (isV2Form) {
            Array.prototype.slice.call(form.querySelectorAll('[name^="programme_days["]')).forEach(function (field) {
                field.removeAttribute('disabled');
                field.removeAttribute('data-programme-submit-disabled');
            });

            return programmeDays;
        }

        // Disable the large nested programme_days[*] inputs to avoid hitting PHP max_input_vars.
        Array.prototype.slice.call(form.querySelectorAll('[name^="programme_days["]')).forEach(function (field) {
            field.setAttribute('disabled', 'disabled');
            field.setAttribute('data-programme-submit-disabled', '1');
        });

        return programmeDays;
    }

    function buildProgrammeDaysPayloadFromDom() {
        var accordion = document.getElementById('accordionProgrammeDays');
        if (!accordion) {
            return [];
        }

        var cards = getProgrammeCards();
        var programmeDays = [];

        function fieldValue(scope, selector) {
            var field = scope.querySelector(selector);
            return field ? field.value : '';
        }

        function checkboxValue(scope, selector, fallback) {
            var field = scope.querySelector(selector);
            if (!field) {
                return fallback;
            }
            return field.checked ? 1 : 0;
        }

        cards.forEach(function (card) {
            var dayId = (fieldValue(card, 'input[name$="[id]"]') || card.getAttribute('data-day-id') || '').trim();
            var dayTitle = (fieldValue(card, 'input[name$="[day_title]"]') || '').trim();
            var title = (fieldValue(card, 'input[name$="[title]"]') || '').trim();
            var notes = (fieldValue(card, 'textarea[name$="[notes]"]') || '').trim();
            var mode = fieldValue(card, 'select[name$="[mode]"]') === 'free' ? 'free' : 'program';
            var activities = [];

            Array.prototype.slice.call(card.querySelectorAll('.programme-activity-row')).forEach(function (row, k) {
                var activityId = parseInt(fieldValue(row, 'input[name$="[activity_id]"]') || '0', 10);
                if (!activityId || activityId <= 0) {
                    return;
                }

                activities.push({
                    day_activity_id: fieldValue(row, 'input[name$="[day_activity_id]"]'),
                    activity_id: activityId,
                    sort_order: k,
                    is_included: checkboxValue(row, 'input[type="checkbox"][name$="[is_included]"]', 1),
                    is_mandatory: checkboxValue(row, 'input[type="checkbox"][name$="[is_mandatory]"]', 0),
                    custom_title: fieldValue(row, '[name$="[custom_title]"]'),
                    custom_description: fieldValue(row, '[name$="[custom_description]"]')
                });
            });

            programmeDays.push({
                id: dayId,
                day_id: (fieldValue(card, 'input[name$="[day_id]"]') || dayId).trim(),
                mode: mode,
                day_title: dayTitle,
                city: fieldValue(card, 'input[name$="[city]"]'),
                day_type: fieldValue(card, 'select[name$="[day_type]"]') || 'visite',
                content_html: fieldValue(card, 'textarea[name$="[content_html]"]'),
                notes: notes,
                title: title || dayTitle,
                description: fieldValue(card, 'textarea[name$="[description]"]'),
                hotel_id: fieldValue(card, 'input[name$="[hotel_id]"]'),
                transfer_ids: fieldValue(card, 'input[name$="[transfer_ids]"]'),
                flights: fieldValue(card, 'input[name$="[flights]"]'),
                activities: activities
            });
        });

        return programmeDays;
    }

    function cleanupDraggedProgrammeCard() {
        if (state.draggedProgrammeCard) {
            state.draggedProgrammeCard.classList.remove('opacity-50');
            state.draggedProgrammeCard.classList.remove('programme-day-card--dragging');
            state.draggedProgrammeCard.style.width = '';
            state.draggedProgrammeCard.style.maxWidth = '';
            state.draggedProgrammeCard.style.transform = '';
            state.draggedProgrammeCard.style.position = '';
            state.draggedProgrammeCard.style.left = '';
            state.draggedProgrammeCard.style.right = '';
            state.draggedProgrammeCard.style.top = '';
            state.draggedProgrammeCard.style.marginLeft = '';
            state.draggedProgrammeCard.style.marginRight = '';
            state.draggedProgrammeCard.style.zIndex = '';
        }

        Array.prototype.slice.call(document.querySelectorAll('.programme-day-card.border-primary')).forEach(function (card) {
            card.classList.remove('border-primary');
        });

        scheduleProgrammeIntegritySync();
        state.draggedProgrammeCard = null;
    }

    function addProgrammeDay() {
        var accordion = document.getElementById('accordionProgrammeDays');
        var sourceCard = accordion && accordion.querySelector('.programme-day-card');
        if (!accordion || !sourceCard) {
            return;
        }

        var cardCount = getProgrammeCards().length;
        var newCard = sourceCard.cloneNode(true);
        var collapseId = createProgrammeCollapseId(cardCount);
        var collapseElement = newCard.querySelector('.accordion-collapse');
        var toggleButton = newCard.querySelector('.accordion-button');
        var extras = newCard.querySelector('.programme-day-extras');
        var inclus = newCard.querySelector('.programme-day-inclus');
        var activitiesList = newCard.querySelector('.programme-activities-list');

        newCard.setAttribute('data-day-id', '');
        newCard.classList.remove('border-primary', 'opacity-50');

        if (toggleButton) {
            toggleButton.classList.add('collapsed');
            toggleButton.setAttribute('aria-expanded', 'false');
            toggleButton.setAttribute('data-bs-target', '#' + collapseId);
            toggleButton.setAttribute('aria-controls', collapseId);
        }

        if (collapseElement) {
            collapseElement.id = collapseId;
            collapseElement.classList.remove('show');
        }

        newCard.querySelectorAll('input, textarea, select').forEach(function (field) {
            var fieldName = field.name || '';

            if (!fieldName) {
                return;
            }

            if (field.tagName === 'SELECT') {
                if (fieldName.indexOf('[mode]') !== -1) field.value = 'program';
                if (fieldName.indexOf('[day_type]') !== -1) field.value = 'visite';
                return;
            }

            if (field.type === 'hidden') {
                if (fieldName.indexOf('[title]') !== -1) field.value = '';
                if (fieldName.indexOf('[id]') !== -1) field.value = '';
                if (fieldName.indexOf('[day_id]') !== -1) field.value = '';
                if (fieldName.indexOf('[flights]') !== -1) field.value = '';
                if (fieldName.indexOf('[hotel_id]') !== -1) field.value = '';
                if (fieldName.indexOf('[transfer_ids]') !== -1) field.value = '';
                return;
            }

            if (field.type === 'checkbox' || field.type === 'radio') {
                field.checked = false;
                return;
            }

            field.value = '';
        });

        if (activitiesList) {
            activitiesList.innerHTML = '';
        }

        if (extras) {
            extras.innerHTML = '';
        }

        if (inclus) {
            inclus.textContent = 'INCLUS : 0 Activite';
        }

        accordion.appendChild(newCard);
        normalizeProgrammeCards();
        ensureProgrammeDayVisible(newCard);

        if (window.updateProgrammeDayExtras) {
            window.updateProgrammeDayExtras(String(getProgrammeCards().length - 1));
        }
    }

    function removeProgrammeDay(card) {
        if (!card) {
            return;
        }

        if (getProgrammeCards().length <= 1) {
            window.alert('Il doit rester au moins un jour.');
            return;
        }

        if (!window.confirm('Supprimer ce jour ? Les activites du jour seront supprimees a la sauvegarde.')) {
            return;
        }

        card.remove();
        normalizeProgrammeCards();
        ensureFirstProgrammeDayVisible();
    }

    function normalizeProgrammeCards() {
        normalizeProgrammeCardPlacement();

        getProgrammeCards().forEach(function (card, index) {
            card.setAttribute('data-day-index', index);
            card.draggable = true;
            card.style.width = '';
            card.style.maxWidth = '';
            card.style.position = '';
            card.style.left = '';
            card.style.right = '';
            card.style.top = '';
            card.style.transform = '';
            card.style.marginLeft = '';
            card.style.marginRight = '';
            card.style.zIndex = '';
            card.classList.remove('programme-day-card--dragging');
            card.classList.remove('opacity-50');
            card.classList.remove('border-primary');

            card.querySelectorAll('[name^="programme_days["]').forEach(function (field) {
                field.name = field.name.replace(/^programme_days\[\d+\]/, 'programme_days[' + index + ']');
            });

            card.querySelectorAll('[data-day-index]').forEach(function (node) {
                node.setAttribute('data-day-index', index);
            });

            card.querySelectorAll('.add-activity-select, .add-activity-to-day').forEach(function (node) {
                node.setAttribute('data-day-index', index);
            });


            card.querySelectorAll('.programme-activity-row').forEach(function (row, activityIndex) {
                row.querySelectorAll('[name*="[activities]"]').forEach(function (field) {
                    field.name = field.name.replace(/\[activities\]\[\d+\]/, '[activities][' + activityIndex + ']');
                });

                var sortOrderInput = row.querySelector('input[name$="[sort_order]"]');
                if (sortOrderInput) {
                    sortOrderInput.value = activityIndex;
                }
            });

            updateProgrammeCardHeader(card, index);
            updateProgrammeInclus(card);

            if (window.updateProgrammeDayExtras) {
                window.updateProgrammeDayExtras(String(index));
            }
        });

        syncProgrammeBadge();
        syncProgrammeDuration();
    }

    function updateProgrammeCardHeader(card, index) {
        var titleInput = card.querySelector('input[name$="[day_title]"]');
        var hiddenTitleInput = card.querySelector('input[name$="[title]"]');
        var label = card.querySelector('.programme-day-label');
        var title = titleInput && titleInput.value.trim() ? titleInput.value.trim() : 'Jour ' + (index + 1);

        if (hiddenTitleInput) {
            hiddenTitleInput.value = title;
        }

        if (label) {
            label.textContent = 'JOUR ' + (index + 1) + ' - ' + title;
        }
    }

    function updateProgrammeInclus(card) {
        var inclus = card.querySelector('.programme-day-inclus');
        var activities = card.querySelectorAll('.programme-activity-row').length;

        if (inclus) {
            inclus.textContent = 'INCLUS : ' + activities + (activities > 1 ? ' Activites' : ' Activite');
        }
    }

    function syncProgrammeBadge() {
        var badge = document.getElementById('program-days-badge');
        var count = getProgrammeCards().length;

        if (badge) {
            badge.textContent = count === 1 ? '1 jour' : count + ' jours';
        }
    }

    function syncProgrammeDuration() {
        var durationInput = document.getElementById('duration_day');
        var count = Math.max(1, getProgrammeCards().length);
        if (durationInput) {
            durationInput.value = count;
        }

        document.dispatchEvent(new CustomEvent('voyage:program-days-changed', {
            detail: { days: count }
        }));
        if (window.VoyageHotelDays && typeof window.VoyageHotelDays.refresh === 'function') {
            window.VoyageHotelDays.refresh(count);
        }
    }

    function ensureFirstProgrammeDayVisible() {
        var firstCard = getProgrammeCards()[0];
        if (firstCard) {
            ensureProgrammeDayVisible(firstCard);
        }
    }

    function ensureProgrammeDayVisible(card) {
        var collapseElement = card.querySelector('.accordion-collapse');
        var toggleButton = card.querySelector('.accordion-button');

        if (!collapseElement || !toggleButton) {
            return;
        }

        if (window.bootstrap && window.bootstrap.Collapse) {
            window.bootstrap.Collapse.getOrCreateInstance(collapseElement, { toggle: false }).show();
        } else {
            collapseElement.classList.add('show');
        }

        toggleButton.classList.remove('collapsed');
        toggleButton.setAttribute('aria-expanded', 'true');
    }

    function getProgrammeCards() {
        var accordion = document.getElementById('accordionProgrammeDays');

        if (!accordion) {
            return [];
        }

        return Array.prototype.slice.call(accordion.children).filter(function (node) {
            return !!(node && node.classList && node.classList.contains('programme-day-card'));
        });
    }

    function normalizeProgrammeCardPlacement() {
        var accordion = document.getElementById('accordionProgrammeDays');
        var programRoot = document.getElementById('program-days');

        if (!accordion || !programRoot) {
            return;
        }

        // Scope to programme tab only — never remove .programme-day-card nodes elsewhere on the page.
        Array.prototype.slice.call(programRoot.querySelectorAll('.programme-day-card')).forEach(function (card) {
            if (card === state.draggedProgrammeCard) {
                return;
            }
            if (card.parentElement !== accordion) {
                accordion.appendChild(card);
            }
        });

        Array.prototype.slice.call(document.querySelectorAll('.sortable-ghost, .sortable-chosen, .sortable-drag, .ui-sortable-helper, .ui-sortable-placeholder')).forEach(function (node) {
            if (!accordion.contains(node) && node !== state.draggedProgrammeCard && node.parentElement) {
                node.parentElement.removeChild(node);
            }
        });
    }

    function bindProgrammeIntegrityObserver(accordion) {
        if (state.programmeObserver || !window.MutationObserver) {
            return;
        }

        var root = document.body || document.getElementById('program-days') || accordion;
        state.programmeObserver = new window.MutationObserver(function (mutations) {
            var shouldSync = mutations.some(function (mutation) {
                return containsProgrammeCardNodes(mutation.addedNodes)
                    || containsProgrammeCardNodes(mutation.removedNodes);
            });

            if (shouldSync) {
                scheduleProgrammeIntegritySync();
            }
        });

        state.programmeObserver.observe(root, { childList: true, subtree: true });
    }

    function scheduleProgrammeIntegritySync() {
        if (state.programmeIntegrityScheduled) {
            return;
        }

        state.programmeIntegrityScheduled = true;

        window.requestAnimationFrame(function () {
            state.programmeIntegrityScheduled = false;
            normalizeProgrammeCards();
        });
    }

    function containsProgrammeCardNodes(nodes) {
        return Array.prototype.slice.call(nodes || []).some(function (node) {
            if (!node || node.nodeType !== 1) {
                return false;
            }

            return (node.classList && node.classList.contains('programme-day-card'))
                || (node.classList && (
                    node.classList.contains('sortable-ghost')
                    || node.classList.contains('sortable-chosen')
                    || node.classList.contains('sortable-drag')
                    || node.classList.contains('ui-sortable-helper')
                    || node.classList.contains('ui-sortable-placeholder')
                ))
                || !!(node.querySelector && node.querySelector('.sortable-ghost, .sortable-chosen, .sortable-drag, .ui-sortable-helper, .ui-sortable-placeholder'))
                || !!(node.querySelector && node.querySelector('.programme-day-card'));
        });
    }

    function createProgrammeCollapseId(index) {
        return 'programme-day-collapse-' + (index + 1) + '-' + Date.now();
    }

    function bindProgrammeManagerDiagnostics(accordion) {
        if (state.programmeManagerDiagnosticsBound || !isManagerPortalShell()) {
            return;
        }

        state.programmeManagerDiagnosticsBound = true;

        accordion.addEventListener('click', function (event) {
            var toggleButton = event.target.closest('.accordion-button');
            if (!toggleButton) {
                return;
            }

            window.setTimeout(function () {
                logProgrammeManagerState(toggleButton.closest('.programme-day-card'), 'click');
            }, 50);

            window.setTimeout(function () {
                logProgrammeManagerState(toggleButton.closest('.programme-day-card'), 'click+400ms');
            }, 400);
        });

        accordion.addEventListener('shown.bs.collapse', function (event) {
            logProgrammeManagerState(event.target.closest('.programme-day-card'), 'shown.bs.collapse');
        });

        accordion.addEventListener('hidden.bs.collapse', function (event) {
            logProgrammeManagerState(event.target.closest('.programme-day-card'), 'hidden.bs.collapse');
        });
    }

    function logProgrammeManagerState(card, phase) {
        if (!card || !window.console) {
            return;
        }

        var collapse = card.querySelector('.accordion-collapse');
        var body = card.querySelector('.accordion-body');
        var computedCollapse = collapse ? window.getComputedStyle(collapse) : null;
        var computedBody = body ? window.getComputedStyle(body) : null;

        console.log('[Programme Manager Diagnostic]', {
            phase: phase,
            dayIndex: card.getAttribute('data-day-index'),
            collapseFound: !!collapse,
            bodyFound: !!body,
            bodyHtmlLength: body ? (body.innerHTML || '').length : 0,
            collapseClasses: collapse ? collapse.className : null,
            bodyClasses: body ? body.className : null,
            collapseDisplay: computedCollapse ? computedCollapse.display : null,
            collapseVisibility: computedCollapse ? computedCollapse.visibility : null,
            collapseHeight: collapse ? collapse.getBoundingClientRect().height : null,
            collapseMaxHeight: computedCollapse ? computedCollapse.maxHeight : null,
            collapseOverflow: computedCollapse ? computedCollapse.overflow : null,
            bodyDisplay: computedBody ? computedBody.display : null,
            bodyVisibility: computedBody ? computedBody.visibility : null,
            bodyOpacity: computedBody ? computedBody.opacity : null,
            bodyHeight: body ? body.getBoundingClientRect().height : null,
            bodyOverflow: computedBody ? computedBody.overflow : null
        });
    }

    function isManagerPortalShell() {
        return document.body.classList.contains('partner-v2') && !!document.querySelector('.agent-portal-main');
    }

    function enableFlightOptionsForSubmit() {
        Array.prototype.slice.call(document.querySelectorAll('[name^="flight_options"]')).forEach(function (field) {
            if (isFlightOptionTemplateField(field) || isFlightOptionDrawerField(field)) {
                return;
            }

            if (field.hasAttribute('disabled')) {
                field.removeAttribute('disabled');
            }
        });
    }

    function disableDrawerFlightOptionsForSubmit() {
        var drawer = document.getElementById('day-builder-root');
        if (!drawer) {
            return;
        }

        Array.prototype.slice.call(drawer.querySelectorAll('[name^="flight_options"]')).forEach(function (field) {
            if (!field.hasAttribute('disabled')) {
                field.setAttribute('disabled', 'disabled');
                field.setAttribute('data-runtime-disabled', '1');
            }
        });
    }

    function isFlightOptionTemplateField(field) {
        var templates = document.getElementById('flight-opt-templates');
        if (templates && templates.contains(field)) {
            return true;
        }

        return !!((field.name || '').indexOf('[-1]') !== -1);
    }

    function isFlightOptionDrawerField(field) {
        var drawer = document.getElementById('day-builder-root');
        return !!(drawer && drawer.contains(field));
    }

    function dispatch(name, detail) {
        document.dispatchEvent(new window.CustomEvent(name, {
            detail: detail || {}
        }));
    }
})(window, document);
