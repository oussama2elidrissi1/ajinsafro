<div class="row g-3" id="day-builder-activities">
    <div class="col-12 mb-2">
        <div class="card border-primary">
            <div class="card-header bg-light py-2">
                <strong><i class="bx bx-plus-circle"></i> Créer une nouvelle activité</strong>
            </div>
            <div id="day-builder-new-activity-form" class="card-body">
                <div id="day-builder-new-activity-form-el"
                     data-action="{{ route('admin.circuits.activities.store') }}"
                     data-base-url="{{ url('/admin/circuits/activities') }}">
                    @csrf
                    <div class="row g-2">
                        <div class="col-md-5">
                            <label for="day-builder-activity-title" class="form-label small">Titre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="day-builder-activity-title" placeholder="Ex. Visite du château">
                            <div id="day-builder-activity-title-error" class="small text-danger mt-1 d-none"></div>
                        </div>
                        <div class="col-md-5">
                            <label for="day-builder-activity-description" class="form-label small">Description</label>
                            <input type="text" class="form-control form-control-sm" id="day-builder-activity-description" placeholder="Optionnel">
                            <div id="day-builder-activity-description-error" class="small text-danger mt-1 d-none"></div>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" class="btn btn-sm btn-primary w-100" id="day-builder-new-activity-submit">
                                <span class="btn-text">Créer</span>
                                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                            </button>
                        </div>
                    </div>
                    <div id="day-builder-new-activity-error" class="small text-danger mt-2 d-none"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="row g-3" id="day-builder-activities-cards">
            @foreach($activitiesCatalog as $act)
                <div class="col-md-6 col-lg-6" data-activity-card-id="{{ $act->id }}">
                    <div class="card h-100 programme-catalog-card">
                        <div class="card-body d-flex flex-column" data-activity-view>
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <h6 class="card-title mb-1" data-activity-title>{{ $act->title }}</h6>
                                <div class="d-flex gap-1">
                                    <button type="button" class="btn btn-sm btn-light" data-action="edit" data-id="{{ $act->id }}" title="Modifier"><i class="bx bx-pencil"></i></button>
                                    <button type="button" class="btn btn-sm btn-light text-danger" data-action="delete" data-id="{{ $act->id }}" title="Supprimer"><i class="bx bx-trash"></i></button>
                                </div>
                            </div>
                            <p class="card-text small text-muted flex-grow-1" data-activity-description>{{ \Illuminate\Support\Str::limit($act->description ?? '', 90) }}</p>
                            <button
                                type="button"
                                class="btn btn-sm btn-primary day-builder-add-activity"
                                data-activity-id="{{ $act->id }}"
                                data-activity-title="{{ e($act->title) }}"
                            >
                                Ajouter au jour
                            </button>
                        </div>
                        <div class="card-body d-none" data-activity-edit>
                            <div class="mb-2">
                                <label class="form-label small">Titre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" data-edit-title value="{{ $act->title }}">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">Description</label>
                                <input type="text" class="form-control form-control-sm" data-edit-description value="{{ $act->description ?? '' }}">
                            </div>
                            <div class="small text-danger d-none mb-2" data-edit-error></div>
                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-sm btn-light" data-action="cancel-edit" data-id="{{ $act->id }}">Annuler</button>
                                <button type="button" class="btn btn-sm btn-primary" data-action="save-edit" data-id="{{ $act->id }}">Enregistrer</button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            <div class="col-12 text-muted {{ $activitiesCatalog->isEmpty() ? '' : 'd-none' }}" id="day-builder-activities-empty-msg">Aucune activité dans le catalogue. Créez-en une ci-dessus.</div>
        </div>
    </div>
</div>

<script>
(function() {
    if (window.__dayBuilderActivitiesManagerInit) return;
    window.__dayBuilderActivitiesManagerInit = true;

    var root = document.getElementById('day-builder-activities');
    var form = document.getElementById('day-builder-new-activity-form-el');
    var submitBtn = document.getElementById('day-builder-new-activity-submit');
    var titleInp = document.getElementById('day-builder-activity-title');
    var descInp = document.getElementById('day-builder-activity-description');
    var titleErr = document.getElementById('day-builder-activity-title-error');
    var descErr = document.getElementById('day-builder-activity-description-error');
    var formErr = document.getElementById('day-builder-new-activity-error');
    var cardsContainer = document.getElementById('day-builder-activities-cards');
    var emptyMsg = document.getElementById('day-builder-activities-empty-msg');
    if (!root || !form || !cardsContainer) return;

    var tokenEl = form.querySelector('input[name="_token"]');
    var csrfToken = tokenEl ? tokenEl.value : '';
    var storeUrl = form.getAttribute('data-action') || '';
    var baseUrl = form.getAttribute('data-base-url') || '';

    function esc(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function limitText(text, max) {
        var s = String(text || '');
        return s.length > max ? s.substring(0, max) + '…' : s;
    }

    function toastHost() {
        var host = document.getElementById('day-builder-activity-toast-host');
        if (host) return host;
        host = document.createElement('div');
        host.id = 'day-builder-activity-toast-host';
        host.className = 'position-fixed';
        host.style.cssText = 'top:16px;right:16px;z-index:2100;';
        document.body.appendChild(host);
        return host;
    }

    function showToast(message, type) {
        var host = toastHost();
        var toast = document.createElement('div');
        toast.className = 'alert alert-' + (type || 'success') + ' alert-dismissible fade show mb-2';
        toast.innerHTML = '<span>' + esc(message || '') + '</span><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        host.appendChild(toast);
        setTimeout(function() { toast.remove(); }, 3000);
    }

    function clearCreateErrors() {
        if (formErr) { formErr.classList.add('d-none'); formErr.textContent = ''; }
        if (titleErr) { titleErr.classList.add('d-none'); titleErr.textContent = ''; }
        if (descErr) { descErr.classList.add('d-none'); descErr.textContent = ''; }
        [titleInp, descInp].forEach(function(input) {
            if (input) input.classList.remove('is-invalid');
        });
    }

    function applyCreateErrors(errors, fallbackMessage) {
        clearCreateErrors();
        if (errors && errors.title && titleErr) {
            titleErr.textContent = errors.title[0];
            titleErr.classList.remove('d-none');
            if (titleInp) titleInp.classList.add('is-invalid');
        }
        if (errors && errors.description && descErr) {
            descErr.textContent = errors.description[0];
            descErr.classList.remove('d-none');
            if (descInp) descInp.classList.add('is-invalid');
        }
        if (formErr) {
            formErr.textContent = fallbackMessage || 'Veuillez corriger les erreurs.';
            formErr.classList.remove('d-none');
        }
    }

    function setCreateLoading(loading) {
        var btnText = submitBtn && submitBtn.querySelector('.btn-text');
        var spinner = submitBtn && submitBtn.querySelector('.spinner-border');
        if (submitBtn) submitBtn.disabled = loading;
        if (btnText) btnText.classList.toggle('d-none', loading);
        if (spinner) spinner.classList.toggle('d-none', !loading);
    }

    function normalizeActivity(raw) {
        return {
            id: Number(raw.id),
            title: raw.title || '',
            description: raw.description || '',
            base_price: raw.base_price || raw.price || 0,
        };
    }

    function cardHtml(activity) {
        return '<div class="card h-100 programme-catalog-card">' +
            '<div class="card-body d-flex flex-column" data-activity-view>' +
                '<div class="d-flex justify-content-between align-items-start gap-2">' +
                    '<h6 class="card-title mb-1" data-activity-title>' + esc(activity.title) + '</h6>' +
                    '<div class="d-flex gap-1">' +
                        '<button type="button" class="btn btn-sm btn-light" data-action="edit" data-id="' + activity.id + '" title="Modifier"><i class="bx bx-pencil"></i></button>' +
                        '<button type="button" class="btn btn-sm btn-light text-danger" data-action="delete" data-id="' + activity.id + '" title="Supprimer"><i class="bx bx-trash"></i></button>' +
                    '</div>' +
                '</div>' +
                '<p class="card-text small text-muted flex-grow-1" data-activity-description>' + esc(limitText(activity.description, 90)) + '</p>' +
                '<button type="button" class="btn btn-sm btn-primary day-builder-add-activity" data-activity-id="' + activity.id + '" data-activity-title="' + esc(activity.title) + '">Ajouter au jour</button>' +
            '</div>' +
            '<div class="card-body d-none" data-activity-edit>' +
                '<div class="mb-2"><label class="form-label small">Titre <span class="text-danger">*</span></label><input type="text" class="form-control form-control-sm" data-edit-title value="' + esc(activity.title) + '"></div>' +
                '<div class="mb-2"><label class="form-label small">Description</label><input type="text" class="form-control form-control-sm" data-edit-description value="' + esc(activity.description) + '"></div>' +
                '<div class="small text-danger d-none mb-2" data-edit-error></div>' +
                '<div class="d-flex justify-content-end gap-2"><button type="button" class="btn btn-sm btn-light" data-action="cancel-edit" data-id="' + activity.id + '">Annuler</button><button type="button" class="btn btn-sm btn-primary" data-action="save-edit" data-id="' + activity.id + '">Enregistrer</button></div>' +
            '</div>' +
        '</div>';
    }

    function upsertCard(activity, prepend) {
        var selector = '[data-activity-card-id="' + activity.id + '"]';
        var col = cardsContainer.querySelector(selector);
        if (!col) {
            col = document.createElement('div');
            col.className = 'col-md-6 col-lg-6';
            col.setAttribute('data-activity-card-id', String(activity.id));
            if (prepend && cardsContainer.firstChild) {
                cardsContainer.insertBefore(col, cardsContainer.firstChild);
            } else {
                cardsContainer.appendChild(col);
            }
        }
        col.innerHTML = cardHtml(activity);
        if (emptyMsg) emptyMsg.classList.add('d-none');
    }

    function removeCard(activityId) {
        var col = cardsContainer.querySelector('[data-activity-card-id="' + activityId + '"]');
        if (col) col.remove();

        var cardsCount = cardsContainer.querySelectorAll('[data-activity-card-id]').length;
        if (!cardsCount && emptyMsg) emptyMsg.classList.remove('d-none');
    }

    function upsertCatalogEntry(activity) {
        ['PROGRAMME_ACTIVITIES_CATALOG', 'TOUR_ACTIVITIES_CATALOG'].forEach(function(key) {
            if (!Array.isArray(window[key])) window[key] = [];
            var idx = window[key].findIndex(function(item) { return Number(item.id) === Number(activity.id); });
            var payload = {
                id: Number(activity.id),
                title: activity.title,
                description: activity.description,
                base_price: activity.base_price || 0,
            };
            if (idx >= 0) window[key][idx] = Object.assign({}, window[key][idx], payload);
            else window[key].unshift(payload);
        });

        document.querySelectorAll('.add-activity-select').forEach(function(select) {
            var val = String(activity.id);
            var option = select.querySelector('option[value="' + val + '"]');
            if (!option) {
                option = document.createElement('option');
                option.value = val;
                select.appendChild(option);
            }
            option.textContent = activity.title;
        });
    }

    function removeCatalogEntry(activityId) {
        ['PROGRAMME_ACTIVITIES_CATALOG', 'TOUR_ACTIVITIES_CATALOG'].forEach(function(key) {
            if (!Array.isArray(window[key])) return;
            window[key] = window[key].filter(function(item) { return Number(item.id) !== Number(activityId); });
        });

        document.querySelectorAll('.add-activity-select option[value="' + activityId + '"]').forEach(function(opt) {
            opt.remove();
        });
    }

    async function parseJsonResponse(response) {
        var json = await response.json().catch(function() { return null; });
        if (!response.ok || !json || json.success === false) {
            var err = new Error((json && json.message) || 'Une erreur est survenue.');
            err.status = response.status;
            err.payload = json;
            throw err;
        }
        return json;
    }

    async function createActivity() {
        clearCreateErrors();
        var title = (titleInp && titleInp.value || '').trim();
        var description = (descInp && descInp.value || '').trim();

        if (!title) {
            applyCreateErrors({ title: ['Le titre est obligatoire.'] }, 'Le titre est obligatoire.');
            if (titleInp) titleInp.focus();
            return;
        }

        setCreateLoading(true);
        try {
            var formData = new FormData();
            formData.append('_token', csrfToken);
            formData.append('title', title);
            formData.append('description', description);

            var response = await fetch(storeUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });

            var json = await parseJsonResponse(response);
            var activity = normalizeActivity(json.data || json.activity || {});
            if (!activity.id) throw new Error('Réponse invalide du serveur.');

            upsertCard(activity, true);
            upsertCatalogEntry(activity);
            if (titleInp) titleInp.value = '';
            if (descInp) descInp.value = '';
            showToast(json.message || 'Activité créée.', 'success');
        } catch (error) {
            if (error.status === 422 && error.payload && error.payload.errors) {
                applyCreateErrors(error.payload.errors, error.payload.message || 'Erreur de validation.');
            } else {
                applyCreateErrors(null, error.message || 'Erreur lors de la création.');
            }
        } finally {
            setCreateLoading(false);
        }
    }

    async function updateActivity(cardCol, activityId) {
        var editWrap = cardCol.querySelector('[data-activity-edit]');
        var viewWrap = cardCol.querySelector('[data-activity-view]');
        if (!editWrap || !viewWrap) return;

        var titleInput = editWrap.querySelector('[data-edit-title]');
        var descInput = editWrap.querySelector('[data-edit-description]');
        var errorEl = editWrap.querySelector('[data-edit-error]');
        var saveBtn = editWrap.querySelector('[data-action="save-edit"]');
        if (errorEl) { errorEl.classList.add('d-none'); errorEl.textContent = ''; }

        var title = (titleInput && titleInput.value || '').trim();
        var description = (descInput && descInput.value || '').trim();

        if (!title) {
            if (errorEl) {
                errorEl.textContent = 'Le titre est obligatoire.';
                errorEl.classList.remove('d-none');
            }
            if (titleInput) titleInput.focus();
            return;
        }

        if (saveBtn) saveBtn.disabled = true;
        try {
            var formData = new FormData();
            formData.append('_token', csrfToken);
            formData.append('_method', 'PATCH');
            formData.append('title', title);
            formData.append('description', description);

            var response = await fetch(baseUrl + '/' + activityId, {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });

            var json = await parseJsonResponse(response);
            var activity = normalizeActivity(json.data || {});
            upsertCard(activity, false);
            upsertCatalogEntry(activity);
            showToast(json.message || 'Activité mise à jour.', 'success');
        } catch (error) {
            var msg = error.message || 'Impossible de modifier l’activité.';
            if (error.status === 422 && error.payload && error.payload.errors && error.payload.errors.title) {
                msg = error.payload.errors.title[0];
            }
            if (errorEl) {
                errorEl.textContent = msg;
                errorEl.classList.remove('d-none');
            }
        } finally {
            if (saveBtn) saveBtn.disabled = false;
        }
    }

    async function deleteActivity(activityId) {
        if (!window.confirm('Supprimer cette activité ?')) return;

        try {
            var formData = new FormData();
            formData.append('_token', csrfToken);
            formData.append('_method', 'DELETE');

            var response = await fetch(baseUrl + '/' + activityId, {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });

            var json = await parseJsonResponse(response);
            removeCard(activityId);
            removeCatalogEntry(activityId);
            showToast(json.message || 'Activité supprimée.', 'success');
        } catch (error) {
            showToast(error.message || 'Suppression impossible.', 'danger');
        }
    }

    if (submitBtn) {
        submitBtn.addEventListener('click', function(e) {
            e.preventDefault();
            createActivity();
        });
    }

    if (titleInp) {
        titleInp.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                createActivity();
            }
        });
    }

    root.addEventListener('click', function(e) {
        var editBtn = e.target.closest('[data-action="edit"]');
        if (editBtn) {
            e.preventDefault();
            var cardCol = editBtn.closest('[data-activity-card-id]');
            if (!cardCol) return;
            var viewWrap = cardCol.querySelector('[data-activity-view]');
            var editWrap = cardCol.querySelector('[data-activity-edit]');
            if (viewWrap) viewWrap.classList.add('d-none');
            if (editWrap) editWrap.classList.remove('d-none');
            return;
        }

        var cancelBtn = e.target.closest('[data-action="cancel-edit"]');
        if (cancelBtn) {
            e.preventDefault();
            var cancelCard = cancelBtn.closest('[data-activity-card-id]');
            if (!cancelCard) return;
            var cancelView = cancelCard.querySelector('[data-activity-view]');
            var cancelEdit = cancelCard.querySelector('[data-activity-edit]');
            if (cancelEdit) cancelEdit.classList.add('d-none');
            if (cancelView) cancelView.classList.remove('d-none');
            return;
        }

        var saveBtn = e.target.closest('[data-action="save-edit"]');
        if (saveBtn) {
            e.preventDefault();
            var saveCard = saveBtn.closest('[data-activity-card-id]');
            if (!saveCard) return;
            var saveId = saveBtn.getAttribute('data-id');
            if (!saveId) return;
            updateActivity(saveCard, saveId);
            return;
        }

        var deleteBtn = e.target.closest('[data-action="delete"]');
        if (deleteBtn) {
            e.preventDefault();
            var deleteId = deleteBtn.getAttribute('data-id');
            if (!deleteId) return;
            deleteActivity(deleteId);
        }
    });
})();
</script>
