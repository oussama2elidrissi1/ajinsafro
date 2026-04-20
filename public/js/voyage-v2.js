(function () {
    'use strict';

    var page = document.querySelector('.v2-page');
    var form = document.getElementById('edit-voyage-form');
    if (!page || !form) {
        return;
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

    function ensureHiddenInput(name) {
        var input = form.querySelector('input[type="hidden"][name="' + name + '"]');
        if (input) return input;
        input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        form.appendChild(input);
        return input;
    }

    function normalizeTargetStep(stepId, fallback) {
        if (stepId && panels[stepId]) return stepId;
        return fallback;
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
            liveStatus.textContent = raw.split('—')[0].trim() || statusInput.value;
            if (railStatus) railStatus.textContent = liveStatus.textContent;
        }

        var destinationInput = document.getElementById('destination');
        if (destinationInput && railDestination) {
            railDestination.textContent = String(destinationInput.value || '').trim() || '—';
        }
    }

    function saveStep(stepId, mode, redirectStep) {
        if (!stepId || !panels[stepId]) return false;
        if (state.saving) return false;

        syncDerivedFormState();
        clearStepErrors();
        clearPanelFieldErrors(stepId);
        refreshLiveBindings();

        var url = resolveSaveUrl(stepId);
        if (!url) {
            setSaveState('error', 'Erreur d’enregistrement', 'URL de sauvegarde introuvable.');
            return false;
        }

        var finalRedirectStep = normalizeTargetStep(redirectStep, stepId);
        var methodField = form.querySelector('input[name="_method"]');
        if (methodField) {
            methodField.disabled = true;
        }

        ensureHiddenInput('current_step').value = stepId;
        ensureHiddenInput('voyage_id').value = String(state.voyageId || 0);
        ensureHiddenInput('redirect_step').value = finalRedirectStep;
        ensureHiddenInput('v2_save_mode').value = mode || 'manual';

        state.saving = true;
        setSaveState('saving', 'Enregistrement…', 'Sauvegarde de l’étape ' + stepId + ' en cours.');
        form.setAttribute('action', url);
        form.setAttribute('method', 'POST');
        form.submit();
        return true;
    }

    function guardedNavigate(nextStepId) {
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
            saveStep(state.current, 'guard', nextStepId);
            return;
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

    window.addEventListener('beforeunload', function (event) {
        var hasDirty = stepIds.some(function (id) { return !!state.dirty[id]; });
        if (!hasDirty) return;
        event.preventDefault();
        event.returnValue = '';
    });

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
    setSaveState('idle', 'Prêt', 'Modifiez un champ pour activer la sauvegarde d’étape.');
})();
