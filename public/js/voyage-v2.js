(function () {
    'use strict';

    var page = document.querySelector('.v2-page');
    var form = document.getElementById('edit-voyage-form');
    if (!page || !form) {
        return;
    }

    var workflowToggleBtn = document.getElementById('workflowToggleBtn');
    page.classList.add('workflow-collapsed');
    if (workflowToggleBtn) {
        workflowToggleBtn.addEventListener('click', function () {
            page.classList.toggle('workflow-collapsed');
        });
    }

    var config = window.VOYAGE_V2_CONFIG || {};
    var stepIds = Array.isArray(config.sectionIds) ? config.sectionIds.slice() : [];
    var stepButtons = Array.prototype.slice.call(document.querySelectorAll('[data-v2-nav]'));
    if (!stepIds.length) {
        stepIds = stepButtons.map(function (btn) { return String(btn.getAttribute('data-v2-nav') || '').trim(); }).filter(Boolean);
    }

    var panels = {};
    stepIds.forEach(function (id) {
        var panel = document.getElementById(id);
        if (panel) panels[id] = panel;
    });

    var initialStates = (config.initialStepStates && typeof config.initialStepStates === 'object') ? config.initialStepStates : {};

    var state = {
        current: stepIds[0] || 's-general',
        voyageId: parseInt(String(page.getAttribute('data-v2-initial-id') || form.getAttribute('data-voyage-id') || '0'), 10) || 0,
        isCreate: String(page.getAttribute('data-v2-is-create') || '0') === '1',
        saving: false,
        dirty: {},
        stepStates: {},
        snapshots: {}
    };

    var saveCreateUrl = String(page.getAttribute('data-v2-save-create-url') || '');
    var saveUpdateTemplate = String(page.getAttribute('data-v2-save-update-template') || '');

    var saveButtons = Array.prototype.slice.call(document.querySelectorAll('[data-v2-save]'));
    var nextButtons = Array.prototype.slice.call(document.querySelectorAll('[data-v2-next]'));
    var prevButtons = Array.prototype.slice.call(document.querySelectorAll('[data-v2-prev]'));

    var statusTitle = document.getElementById('v2-save-state');
    var statusHelp = document.getElementById('v2-save-help');
    var saveCard = document.getElementById('v2-save-card');
    var progressText = document.getElementById('v2-progress-text');
    var progressBar = document.getElementById('v2-progress-bar');
    var stepErrorBox = document.getElementById('v2-step-errors');
    var main = document.getElementById('v2-main');
    var liveTitle = document.getElementById('v2-live-title');
    var liveStatus = document.getElementById('v2-live-status');
    var railStatus = document.getElementById('v2-rail-status');
    var railDestination = document.getElementById('v2-rail-destination');
    var railId = document.getElementById('v2-rail-id');
    var slugPreview = document.querySelector('[data-v3-slug-preview]');
    var slugCopyButton = document.querySelector('[data-v3-copy-slug]');
    var excerptCounter = document.querySelector('[data-v3-counter-for="excerpt"]');
    var publicBaseUrl = String(page.getAttribute('data-v3-public-base-url') || '');

    function clearStepErrors() {
        if (!stepErrorBox) return;
        stepErrorBox.classList.add('d-none');
        stepErrorBox.innerHTML = '';
    }

    function clearPanelFieldErrors(stepId) {
        var panel = panels[stepId];
        if (!panel) return;
        Array.prototype.slice.call(panel.querySelectorAll('.is-invalid[data-v2-error], [data-v2-error]')).forEach(function (el) {
            el.classList.remove('is-invalid');
            el.removeAttribute('data-v2-error');
        });
    }

    function keyToInputName(errorKey) {
        if (!errorKey) return '';
        if (errorKey.indexOf('.') === -1) return errorKey;

        var chunks = errorKey.split('.');
        var name = chunks.shift() || '';
        chunks.forEach(function (chunk) {
            name += '[' + chunk + ']';
        });
        return name;
    }

    function findFieldByErrorKey(stepId, errorKey) {
        var panel = panels[stepId];
        if (!panel || !errorKey) return null;

        var candidates = [];
        var asInput = keyToInputName(errorKey);
        if (asInput) candidates.push(asInput);
        if (errorKey.indexOf('.') === -1) candidates.push(errorKey + '[]');
        if (errorKey.indexOf('.') !== -1) {
            var top = errorKey.split('.')[0];
            candidates.push(top);
            candidates.push(top + '[]');
        }

        var fields = Array.prototype.slice.call(panel.querySelectorAll('[name]'));
        for (var i = 0; i < candidates.length; i++) {
            var wanted = String(candidates[i] || '');
            for (var j = 0; j < fields.length; j++) {
                if (String(fields[j].name || '') === wanted) {
                    return fields[j];
                }
            }
        }

        if (errorKey.indexOf('.') !== -1) {
            var prefix = errorKey.split('.')[0] + '[';
            for (var k = 0; k < fields.length; k++) {
                if (String(fields[k].name || '').indexOf(prefix) === 0) {
                    return fields[k];
                }
            }
        }

        return null;
    }

    function markFieldsWithErrors(stepId, errors) {
        clearPanelFieldErrors(stepId);

        var firstField = null;
        Object.keys(errors || {}).forEach(function (key) {
            var field = findFieldByErrorKey(stepId, key);
            if (!field) return;
            field.classList.add('is-invalid');
            field.setAttribute('data-v2-error', '1');
            if (!firstField) firstField = field;
        });

        return firstField;
    }

    function renderStepErrors(errors, headline) {
        if (!stepErrorBox) return;
        var items = [];
        Object.keys(errors || {}).forEach(function (key) {
            var value = errors[key];
            if (Array.isArray(value)) {
                value.forEach(function (msg) { items.push(msg); });
            }
        });

        if (!items.length) {
            clearStepErrors();
            return;
        }

        stepErrorBox.classList.remove('d-none');
        stepErrorBox.innerHTML = '<i class="bx bx-error-circle"></i><div><strong>'
            + escapeHtml(String(headline || 'Completez les champs obligatoires de cette etape avant de continuer.'))
            + '</strong><ul class="mb-0 mt-1 ps-3">'
            + items.map(function (msg) { return '<li>' + escapeHtml(String(msg || '')) + '</li>'; }).join('')
            + '</ul></div>';
    }

    function escapeHtml(text) {
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function setSaveState(kind, title, help) {
        if (saveCard) saveCard.setAttribute('data-state', kind || 'idle');
        if (statusTitle) statusTitle.textContent = title || 'Prêt';
        if (statusHelp) statusHelp.textContent = help || 'Les modifications de l\'étape en cours seront enregistrées automatiquement.';
    }

    function buildPanelSnapshot(stepId) {
        var panel = panels[stepId];
        if (!panel) return '';

        var values = [];
        Array.prototype.slice.call(panel.querySelectorAll('input[name],select[name],textarea[name]')).forEach(function (field) {
            if (!field || field.disabled) return;

            var type = String(field.type || '').toLowerCase();
            if (type === 'button' || type === 'submit' || type === 'reset' || type === 'file') return;

            if (type === 'checkbox') {
                values.push(field.name + '=' + (field.checked ? '1' : '0'));
                return;
            }

            if (type === 'radio') {
                if (field.checked) values.push(field.name + '=' + String(field.value == null ? '' : field.value));
                return;
            }

            if (field.tagName === 'SELECT' && field.multiple) {
                var selected = Array.prototype.slice.call(field.selectedOptions || []).map(function (opt) {
                    return String(opt.value == null ? '' : opt.value);
                }).join('|');
                values.push(field.name + '=' + selected);
                return;
            }

            values.push(field.name + '=' + String(field.value == null ? '' : field.value));
        });

        values.sort();
        return values.join('\u001f');
    }

    function isCurrentStepDirtyBySnapshot() {
        var stepId = state.current;
        if (!stepId || !panels[stepId]) return false;

        var snapshot = buildPanelSnapshot(stepId);
        if (typeof state.snapshots[stepId] !== 'string') {
            state.snapshots[stepId] = snapshot;
            return false;
        }

        return snapshot !== state.snapshots[stepId];
    }

    function normalizeStepState(rawState) {
        if (rawState === 'complete' || rawState === 'error') return rawState;
        return 'incomplete';
    }

    function applyStepStates(map) {
        stepIds.forEach(function (stepId) {
            var raw = (map && map[stepId]) || state.stepStates[stepId] || initialStates[stepId] || 'incomplete';
            state.stepStates[stepId] = normalizeStepState(raw);
        });

        stepButtons.forEach(function (btn) {
            var stepId = String(btn.getAttribute('data-v2-nav') || '');
            var st = state.stepStates[stepId] || 'incomplete';
            btn.setAttribute('data-v2-step-state', st);
            btn.classList.toggle('state-complete', st === 'complete');
            btn.classList.toggle('state-error', st === 'error');
            btn.classList.toggle('state-incomplete', st === 'incomplete');
            var meta = btn.querySelector('[data-v2-step-meta]');
            if (meta) {
                meta.textContent = st === 'complete' ? 'Validée' : (st === 'error' ? 'Erreur' : 'À compléter');
            }
        });

        var completed = stepIds.filter(function (id) { return state.stepStates[id] === 'complete'; }).length;
        var ratio = stepIds.length > 0 ? Math.round((completed / stepIds.length) * 100) : 0;
        if (progressText) progressText.textContent = completed + ' / ' + stepIds.length + ' étapes validées';
        if (progressBar) progressBar.style.width = ratio + '%';
    }

    function markCurrentDirty() {
        if (!state.current) return;
        state.dirty[state.current] = true;
        if (state.stepStates[state.current] !== 'error') {
            state.stepStates[state.current] = 'incomplete';
        }
        applyStepStates();
        setSaveState('dirty', 'Brouillon non enregistré', 'Des modifications ne sont pas encore enregistrées.');
    }

    function activateStep(stepId) {
        if (!stepId || !panels[stepId]) return;
        state.current = stepId;
        form.setAttribute('data-v2-current-step', stepId);
        var activePanel = panels[stepId];

        stepIds.forEach(function (id) {
            var panel = panels[id];
            if (panel) panel.classList.toggle('active', id === stepId);
        });

        stepButtons.forEach(function (btn) {
            btn.classList.toggle('active', String(btn.getAttribute('data-v2-nav') || '') === stepId);
        });

        clearStepErrors();
        clearPanelFieldErrors(stepId);
        if (main) {
            try {
                main.scrollTo({ top: 0, behavior: 'smooth' });
            } catch (e) {
                main.scrollTop = 0;
            }
        }
        if (activePanel && activePanel.getBoundingClientRect) {
            try {
                var targetTop = activePanel.getBoundingClientRect().top + window.scrollY - 92;
                window.scrollTo({ top: Math.max(0, targetTop), behavior: 'smooth' });
            } catch (e) {
                // ignore
            }
        }

        if (window.history && window.history.replaceState) {
            var url = window.location.pathname + window.location.search + '#' + stepId;
            window.history.replaceState(null, document.title, url);
        }
    }

    function resolveSaveUrl(stepId) {
        if (!stepId) return '';
        if (state.voyageId > 0 && saveUpdateTemplate) {
            return saveUpdateTemplate.replace('__ID__', String(state.voyageId)).replace('__STEP__', stepId);
        }
        if (saveCreateUrl) {
            return saveCreateUrl.replace('__STEP__', stepId);
        }
        return '';
    }

    function isV2StepSaveUrl(rawUrl) {
        if (!rawUrl) return false;
        try {
            var parsed = new URL(String(rawUrl), window.location.origin);
            return /\/admin\/circuits\/voyages(?:\/\d+)?\/v2\/steps\/[^/]+\/save\/?$/.test(parsed.pathname);
        } catch (e) {
            return false;
        }
    }

    function appendUncheckedCheckboxes(formData, panel) {
        if (!panel) return;
        Array.prototype.slice.call(panel.querySelectorAll('input[type="checkbox"][name]')).forEach(function (cb) {
            if (cb.checked) return;
            var name = cb.getAttribute('name');
            if (!name) return;
            if (formData.has(name)) return;
            formData.append(name, '0');
        });
    }

    function buildStepFormData(stepId, panel) {
        var fd = new FormData();

        var tokenField = form.querySelector('input[name="_token"]');
        if (tokenField && !tokenField.disabled) {
            fd.append('_token', tokenField.value || '');
        }

        Array.prototype.slice.call((panel || form).querySelectorAll('input[name],select[name],textarea[name]')).forEach(function (field) {
            if (!field || field.disabled) return;

            var name = String(field.name || '');
            if (!name) return;

            var tag = String(field.tagName || '').toLowerCase();
            var type = String(field.type || '').toLowerCase();

            if (type === 'button' || type === 'submit' || type === 'reset' || type === 'file') return;

            if ((type === 'checkbox' || type === 'radio') && !field.checked) {
                return;
            }

            if (tag === 'select' && field.multiple) {
                Array.prototype.slice.call(field.options || []).forEach(function (opt) {
                    if (opt.selected) fd.append(name, opt.value);
                });
                return;
            }

            fd.append(name, field.value == null ? '' : String(field.value));
        });

        // Programme payload lives at form root (outside step panel), but is required by s-programme.
        if (stepId === 's-programme') {
            var programmePayload = form.querySelector('#programme-days-payload');
            if (programmePayload && !programmePayload.disabled) {
                fd.set('programme_days_payload', programmePayload.value || '');
            }
        }

        return fd;
    }

    function syncDerivedFormState() {
        if (window.tinymce && typeof window.tinymce.triggerSave === 'function') {
            try {
                window.tinymce.triggerSave();
            } catch (e) {
                // ignore
            }
        }

        if (typeof window.syncProgrammeDaysPayload === 'function') {
            try {
                window.syncProgrammeDaysPayload(false);
            } catch (e) {
                // ignore
            }
        }
        if (typeof window.recalculateVoyageTourPlacesPreview === 'function') {
            try {
                window.recalculateVoyageTourPlacesPreview();
            } catch (e) {
                // ignore
            }
        }
    }

    function refreshLiveBindings() {
        var titleInput = document.getElementById('title');
        if (titleInput && liveTitle) {
            liveTitle.textContent = String(titleInput.value || '').trim() || 'Nouveau voyage';
        }

        var statusInput = document.getElementById('post_status');
        if (statusInput && liveStatus) {
            var raw = String(statusInput.options[statusInput.selectedIndex] ? statusInput.options[statusInput.selectedIndex].text : statusInput.value || '');
            liveStatus.textContent = raw.split(' - ')[0].trim() || statusInput.value;
            if (railStatus) railStatus.textContent = liveStatus.textContent;
        }

        var destinationInput = document.getElementById('destination');
        if (destinationInput && railDestination) {
            railDestination.textContent = String(destinationInput.value || '').trim() || '-';
        }

        var slugInput = document.getElementById('slug');
        if (slugInput && slugPreview) {
            var slugValue = String(slugInput.value || '').trim().replace(/^\/+/, '');
            slugPreview.textContent = slugValue && publicBaseUrl
                ? publicBaseUrl.replace(/\/+$/, '') + '/' + slugValue
                : (publicBaseUrl ? publicBaseUrl.replace(/\/+$/, '') + '/...' : '/voyages/...');
        }

        var excerptInput = document.getElementById('excerpt');
        if (excerptInput && excerptCounter) {
            excerptCounter.textContent = String((excerptInput.value || '').trim().length) + ' / 160';
        }
    }

    function applyVoyageIdentity(data) {
        var id = parseInt(String(data && data.voyage_id ? data.voyage_id : state.voyageId), 10) || 0;
        if (id <= 0) return;
        var idChanged = id !== state.voyageId;
        state.voyageId = id;
        form.setAttribute('data-voyage-id', String(id));
        page.setAttribute('data-v2-initial-id', String(id));

        if (idChanged) {
            state.isCreate = false;
            var redirectUrl = data && data.redirect_url ? String(data.redirect_url) : '';
            if (redirectUrl && window.history && window.history.replaceState) {
                window.history.replaceState(null, document.title, redirectUrl + '#' + state.current);
            }

            var classicLink = document.querySelector('[data-v2-classic-link]');
            if (classicLink) {
                classicLink.href = classicLink.href.replace(/\/\d+(\/edit)?$/, '/' + id + '/edit');
            }

            if (railId) railId.textContent = 'ID #' + id;
            var subtitle = document.getElementById('v2-live-subtitle');
            if (subtitle && subtitle.textContent.indexOf('Brouillon') !== -1) {
                subtitle.textContent = 'ID #' + id;
            }

            saveButtons.forEach(function (btn) {
                var span = btn.querySelector('span');
                if (span) {
                    span.textContent = 'Enregistrer l\'etape';
                }
            });
        }
    }

    function normalizeTargetStep(stepId, fallback) {
        if (stepId && panels[stepId]) return stepId;
        return fallback;
    }

    function formDataToDebugObject(formData) {
        var dump = {};
        formData.forEach(function (value, key) {
            var current = dump[key];
            if (typeof current === 'undefined') {
                dump[key] = value;
                return;
            }
            if (!Array.isArray(current)) {
                dump[key] = [current];
            }
            dump[key].push(value);
        });
        return dump;
    }

    function resolveBackendMessage(data, statusCode) {
        if (data && typeof data.message === 'string' && data.message.trim()) {
            return data.message.trim();
        }
        if (data && typeof data.error === 'string' && data.error.trim()) {
            return data.error.trim();
        }
        if (data && data.errors && typeof data.errors === 'object') {
            var firstKey = Object.keys(data.errors)[0];
            if (firstKey && Array.isArray(data.errors[firstKey]) && data.errors[firstKey][0]) {
                return String(data.errors[firstKey][0]);
            }
        }
        if (statusCode >= 500) {
            return 'Erreur serveur (' + statusCode + ').';
        }
        return 'Le serveur a renvoye une erreur.';
    }

    async function saveStep(stepId, mode, redirectStep) {
        if (!stepId || !panels[stepId]) return false;
        if (state.saving) return false;

        syncDerivedFormState();
        clearStepErrors();
        clearPanelFieldErrors(stepId);
        refreshLiveBindings();

        var url = resolveSaveUrl(stepId);
        if (!url) {
            setSaveState('error', 'Erreur d\'enregistrement', 'URL de sauvegarde introuvable.');
            return false;
        }

        var panel = panels[stepId];
        var formData = buildStepFormData(stepId, panel);
        var finalRedirectStep = normalizeTargetStep(redirectStep, stepId);
        // Step-save endpoints are POST-only; avoid Laravel method spoofing from edit form.
        formData.delete('_method');
        appendUncheckedCheckboxes(formData, panel);
        formData.set('current_step', stepId);
        formData.set('voyage_id', String(state.voyageId || 0));
        formData.set('redirect_step', finalRedirectStep);
        formData.set('v2_save_mode', mode || 'manual');
        if (stepId === 's-activities') {
            formData.set('tour_activities_present', '1');
        }

        if (!formData.get('_token')) {
            setSaveState('error', 'Erreur CSRF', 'Token CSRF manquant dans le formulaire.');
            return false;
        }

        if (window.console && typeof window.console.debug === 'function') {
            console.debug('[v2.saveStep] payload', {
                step: stepId,
                mode: mode || 'manual',
                url: url,
                payload: formDataToDebugObject(formData)
            });
        }

        state.saving = true;
        setSaveState('saving', 'Enregistrement...', 'Sauvegarde de l\'etape ' + stepId + ' en cours.');

        try {
            var response = await fetch(url, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            var data = {};
            try { data = await response.json(); } catch (e) { data = {}; }

            if (!response.ok) {
                if (response.status === 422) {
                    var firstInvalidField = markFieldsWithErrors(stepId, data.errors || {});
                    renderStepErrors(
                        data.errors || {},
                        data.message || 'Completez les champs obligatoires de cette etape avant de continuer.'
                    );
                    if (firstInvalidField && firstInvalidField.scrollIntoView) {
                        try {
                            firstInvalidField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            firstInvalidField.focus({ preventScroll: true });
                        } catch (e) {
                            // ignore
                        }
                    } else if (stepErrorBox && stepErrorBox.scrollIntoView) {
                        try {
                            stepErrorBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        } catch (e) {
                            // ignore
                        }
                    }
                    applyStepStates(data.step_states || null);
                    state.stepStates[stepId] = 'error';
                    applyStepStates();
                    setSaveState(
                        'error',
                        'Erreur de validation',
                        data.message || 'Completez les champs obligatoires de cette etape avant de continuer.'
                    );
                    return false;
                }

                if (data.errors && typeof data.errors === 'object') {
                    markFieldsWithErrors(stepId, data.errors || {});
                    renderStepErrors(data.errors || {}, resolveBackendMessage(data, response.status));
                }
                setSaveState('error', 'Erreur d\'enregistrement', resolveBackendMessage(data, response.status));
                return false;
            }

            applyVoyageIdentity(data);
            applyStepStates(data.step_states || null);
            state.dirty[stepId] = false;
            if (!data.step_states || typeof data.step_states !== 'object') {
                state.stepStates[stepId] = 'complete';
            }
            state.snapshots[stepId] = buildPanelSnapshot(stepId);
            applyStepStates();
            var ts = new Date();
            var successMessage = (data && data.message) ? String(data.message) : 'Etape enregistree.';
            setSaveState('saved', 'Enregistre', successMessage + ' Derniere sauvegarde a ' + ts.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) + '.');
            refreshLiveBindings();
            return true;
        } catch (error) {
            var reason = (error && error.message) ? String(error.message) : 'Impossible de joindre le serveur.';
            setSaveState('error', 'Erreur reseau', reason);
            return false;
        } finally {
            state.saving = false;
        }
    }

    async function guardedNavigate(nextStepId) {
        if (!nextStepId || !panels[nextStepId] || nextStepId === state.current) {
            return;
        }

        syncDerivedFormState();
        if (isCurrentStepDirtyBySnapshot()) {
            state.dirty[state.current] = true;
        }

        var currentState = state.stepStates[state.current] || 'incomplete';
        var mustValidateCurrentStep = state.dirty[state.current] || currentState !== 'complete';
        if (mustValidateCurrentStep) {
            var ok = await saveStep(state.current, 'guard', nextStepId);
            if (!ok) return;
        }

        activateStep(nextStepId);
    }

    stepButtons.forEach(function (btn) {
        btn.addEventListener('click', function (event) {
            if (event) {
                event.preventDefault();
            }
            var target = String(btn.getAttribute('data-v2-nav') || '').trim();
            guardedNavigate(target);
        });
    });

    nextButtons.forEach(function (btn) {
        btn.addEventListener('click', function (event) {
            if (event) {
                event.preventDefault();
            }
            var target = String(btn.getAttribute('data-v2-next') || '').trim();
            guardedNavigate(target);
        });
    });

    prevButtons.forEach(function (btn) {
        btn.addEventListener('click', function (event) {
            if (event) {
                event.preventDefault();
            }
            var target = String(btn.getAttribute('data-v2-prev') || '').trim();
            guardedNavigate(target);
        });
    });

    saveButtons.forEach(function (btn) {
        btn.addEventListener('click', function (event) {
            if (event) {
                event.preventDefault();
            }
            saveStep(state.current, 'manual', state.current);
        });
    });

    form.addEventListener('input', function (event) {
        var target = event.target;
        if (!target || !target.closest) return;
        var panel = target.closest('.v2-panel');
        if (!panel || !panel.id) return;
        if (panel.id !== state.current) {
            state.dirty[panel.id] = true;
            return;
        }
        markCurrentDirty();
        refreshLiveBindings();
    }, true);

    form.addEventListener('change', function (event) {
        var target = event.target;
        if (!target || !target.closest) return;
        var panel = target.closest('.v2-panel');
        if (!panel || !panel.id) return;
        if (panel.id !== state.current) {
            state.dirty[panel.id] = true;
            return;
        }
        markCurrentDirty();
        refreshLiveBindings();
    }, true);

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        if (event.stopPropagation) event.stopPropagation();
        saveStep(state.current, 'manual', state.current);
    }, true);

    if (slugCopyButton && slugPreview) {
        slugCopyButton.addEventListener('click', function () {
            var value = String(slugPreview.textContent || '').trim();
            if (!value || !navigator.clipboard || typeof navigator.clipboard.writeText !== 'function') {
                return;
            }

            navigator.clipboard.writeText(value).then(function () {
                slugCopyButton.textContent = 'Copie';
                window.setTimeout(function () {
                    slugCopyButton.textContent = 'Copier';
                }, 1200);
            }).catch(function () {});
        });
    }

    window.addEventListener('beforeunload', function (event) {
        var hasDirty = stepIds.some(function (id) { return !!state.dirty[id]; });
        if (!hasDirty) return;
        event.preventDefault();
        event.returnValue = '';
    });

    document.addEventListener('click', function (event) {
        var target = event.target;
        if (!target || !target.closest) return;
        var anchor = target.closest('a[href]');
        if (!anchor) return;
        if (!isV2StepSaveUrl(anchor.getAttribute('href'))) return;
        event.preventDefault();
        if (event.stopPropagation) event.stopPropagation();
        saveStep(state.current, 'manual', state.current);
    }, true);

    document.addEventListener('submit', function (event) {
        var submittedForm = event.target;
        if (!submittedForm || submittedForm === form || !submittedForm.getAttribute) return;
        var action = submittedForm.getAttribute('action') || '';
        if (!isV2StepSaveUrl(action)) return;
        event.preventDefault();
        if (event.stopPropagation) event.stopPropagation();
        saveStep(state.current, 'manual', state.current);
    }, true);

    window.addEventListener('hashchange', function () {
        var targetStep = String(window.location.hash || '').replace('#', '');
        if (!targetStep || !panels[targetStep] || targetStep === state.current) return;
        guardedNavigate(targetStep);
    });

    var hashStep = String(window.location.hash || '').replace('#', '');
    if (hashStep && panels[hashStep]) {
        state.current = hashStep;
    }

    stepIds.forEach(function (id) {
        state.stepStates[id] = normalizeStepState(initialStates[id] || 'incomplete');
        state.dirty[id] = false;
        state.snapshots[id] = buildPanelSnapshot(id);
    });

    applyStepStates();
    activateStep(state.current);
    refreshLiveBindings();
    setSaveState('idle', 'Pret', 'Modifiez un champ pour activer la sauvegarde d\'etape.');
})();
