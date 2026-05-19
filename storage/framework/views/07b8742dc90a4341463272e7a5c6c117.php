<div id="day-builder-activities-root"
     data-list-url="<?php echo e(route('admin.circuits.activities.ajax.list')); ?>"
     data-show-url-base="<?php echo e(url('/admin/circuits/activities/ajax')); ?>"
     data-store-url="<?php echo e(route('admin.circuits.activities.ajax.store')); ?>"
     data-update-url-base="<?php echo e(url('/admin/circuits/activities/ajax')); ?>"
     data-destroy-url-base="<?php echo e(url('/admin/circuits/activities/ajax')); ?>"
     data-csrf="<?php echo e(csrf_token()); ?>">

    <div id="day-builder-activities-list-view">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div class="input-group" style="max-width: 320px;">
                <span class="input-group-text"><i class="bx bx-search"></i></span>
                <input type="text" class="form-control" id="day-builder-activities-search" placeholder="Rechercher une activité...">
            </div>
            <button type="button" class="btn btn-primary" id="day-builder-activities-open-create">
                <i class="bx bx-plus me-1"></i> Créer activité
            </button>
        </div>

        <div class="alert alert-info py-2 small mb-3">
            Le catalogue est filtre automatiquement selon la region / destination du voyage.
        </div>

        <div id="day-builder-activities-loader" class="text-center py-4 d-none">
            <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
            <div class="small text-muted mt-2">Chargement des activités...</div>
        </div>

        <div id="day-builder-activities-list-alert" class="alert d-none" role="alert"></div>

        <div class="row g-3" id="day-builder-activities-cards"></div>
        <div class="text-muted d-none" id="day-builder-activities-empty-msg">Aucune activité trouvée.</div>
    </div>

    <div id="day-builder-activities-form-view" class="d-none">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h6 class="mb-0" id="day-builder-activities-form-title">Créer une activité</h6>
            <button type="button" class="btn btn-sm btn-light" id="day-builder-activities-back-to-list">
                <i class="bx bx-arrow-back me-1"></i> Retour à la liste
            </button>
        </div>

        <div id="day-builder-activities-form-alert" class="alert d-none" role="alert"></div>

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card mb-0">
                    <div class="card-body">
                        <input type="hidden" id="activity-form-id">
                        <div class="mb-3">
                            <label for="activity-form-title" class="form-label">Nom de l activite <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="activity-form-title">
                            <div class="small text-danger mt-1 d-none" data-error="title"></div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="activity-form-type" class="form-label">Type d activite <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="activity-form-type" placeholder="Ex: excursion, quad">
                                <div class="small text-danger mt-1 d-none" data-error="activity_type"></div>
                            </div>
                            <div class="col-md-6">
                                <label for="activity-form-region" class="form-label">Region / destination <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="activity-form-region" placeholder="Ex: Merzouga">
                                <div class="small text-danger mt-1 d-none" data-error="region_name"></div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="activity-form-slug" class="form-label">Slug</label>
                            <input type="text" class="form-control" id="activity-form-slug" placeholder="Généré automatiquement si vide">
                            <div class="small text-danger mt-1 d-none" data-error="slug"></div>
                        </div>
                        <div class="mb-3">
                            <label for="activity-form-description" class="form-label">Description</label>
                            <textarea class="form-control" id="activity-form-description" rows="6"></textarea>
                            <div class="small text-danger mt-1 d-none" data-error="description"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card mb-0">
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="activity-form-image" class="form-label">Image</label>
                            <input type="file" class="form-control" id="activity-form-image" accept="image/jpeg,image/png,image/webp">
                            <small class="text-muted d-block mt-1">JPEG/PNG/WebP (max 5MB)</small>
                            <div class="small text-danger mt-1 d-none" data-error="image"></div>
                            <div class="small text-danger mt-1 d-none" data-error="gallery_images"></div>
                            <div id="activity-form-image-current-wrap" class="mt-2 d-none">
                                <img id="activity-form-image-current" src="" alt="Image actuelle" class="img-fluid rounded" style="max-height: 170px; object-fit: cover; width: 100%;">
                                <small class="text-muted d-block mt-1">Image actuelle</small>
                            </div>
                            <div id="activity-form-image-preview-wrap" class="mt-2 d-none">
                                <img id="activity-form-image-preview" src="" alt="Aperçu" class="img-fluid rounded" style="max-height: 170px; object-fit: cover; width: 100%;">
                                <small class="text-muted d-block mt-1">Nouvelle image</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="activity-form-base-price" class="form-label">Prix adulte <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="activity-form-base-price" step="0.01" min="0" placeholder="0.00">
                            <div class="small text-danger mt-1 d-none" data-error="adult_price"></div>
                        </div>

                        <div class="mb-3">
                            <label for="activity-form-child-price" class="form-label">Prix enfant <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="activity-form-child-price" step="0.01" min="0" placeholder="0.00">
                            <div class="small text-danger mt-1 d-none" data-error="child_price"></div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label for="activity-form-min-age" class="form-label">Age min <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="activity-form-min-age" min="0" max="120">
                                <div class="small text-danger mt-1 d-none" data-error="min_age"></div>
                            </div>
                            <div class="col-6">
                                <label for="activity-form-max-age" class="form-label">Age max <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="activity-form-max-age" min="0" max="120">
                                <div class="small text-danger mt-1 d-none" data-error="max_age"></div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="activity-form-icon" class="form-label">Icône</label>
                            <input type="text" class="form-control" id="activity-form-icon" placeholder="Ex: bx-map">
                            <div class="small text-danger mt-1 d-none" data-error="icon"></div>
                        </div>

                        <div class="mb-3">
                            <label for="activity-form-duration" class="form-label">Durée par défaut (minutes)</label>
                            <input type="number" class="form-control" id="activity-form-duration" min="0">
                            <div class="small text-danger mt-1 d-none" data-error="default_duration_minutes"></div>
                        </div>

                        <div class="small text-muted mb-3">
                            La region selectionnee controle les activites proposees dans le voyage.
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="activity-form-is-active" checked>
                            <label class="form-check-label" for="activity-form-is-active">Actif</label>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-primary" id="activity-form-submit-btn">
                                <span class="btn-text">Enregistrer</span>
                                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                            </button>
                            <button type="button" class="btn btn-light" id="activity-form-reset-btn">Réinitialiser</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    if (window.__dayBuilderActivitiesManagerInit) return;
    window.__dayBuilderActivitiesManagerInit = true;

    var root = document.getElementById('day-builder-activities-root');
    if (!root) return;

    var urls = {
        list: root.getAttribute('data-list-url') || '',
        showBase: root.getAttribute('data-show-url-base') || '',
        store: root.getAttribute('data-store-url') || '',
        updateBase: root.getAttribute('data-update-url-base') || '',
        destroyBase: root.getAttribute('data-destroy-url-base') || '',
    };
    var csrfToken = root.getAttribute('data-csrf') || '';

    var listView = document.getElementById('day-builder-activities-list-view');
    var formView = document.getElementById('day-builder-activities-form-view');
    var searchInput = document.getElementById('day-builder-activities-search');
    var openCreateBtn = document.getElementById('day-builder-activities-open-create');
    var backToListBtn = document.getElementById('day-builder-activities-back-to-list');
    var listLoader = document.getElementById('day-builder-activities-loader');
    var listAlert = document.getElementById('day-builder-activities-list-alert');
    var cardsContainer = document.getElementById('day-builder-activities-cards');
    var emptyMsg = document.getElementById('day-builder-activities-empty-msg');

    var formTitle = document.getElementById('day-builder-activities-form-title');
    var formAlert = document.getElementById('day-builder-activities-form-alert');
    var formId = document.getElementById('activity-form-id');
    var formTitleInput = document.getElementById('activity-form-title');
    var formTypeInput = document.getElementById('activity-form-type');
    var formRegionInput = document.getElementById('activity-form-region');
    var formSlugInput = document.getElementById('activity-form-slug');
    var formDescriptionInput = document.getElementById('activity-form-description');
    var formImageInput = document.getElementById('activity-form-image');
    var currentImageWrap = document.getElementById('activity-form-image-current-wrap');
    var currentImage = document.getElementById('activity-form-image-current');
    var previewImageWrap = document.getElementById('activity-form-image-preview-wrap');
    var previewImage = document.getElementById('activity-form-image-preview');
    var formBasePriceInput = document.getElementById('activity-form-base-price');
    var formChildPriceInput = document.getElementById('activity-form-child-price');
    var formMinAgeInput = document.getElementById('activity-form-min-age');
    var formMaxAgeInput = document.getElementById('activity-form-max-age');
    var formIconInput = document.getElementById('activity-form-icon');
    var formDurationInput = document.getElementById('activity-form-duration');
    var formIsActiveInput = document.getElementById('activity-form-is-active');
    var formSubmitBtn = document.getElementById('activity-form-submit-btn');
    var formResetBtn = document.getElementById('activity-form-reset-btn');

    var state = {
        mode: 'list',
        editingId: null,
        activities: [],
    };

    function currentRegionTerms() {
        if (window.AjinsafroActivityRegionFilter && typeof window.AjinsafroActivityRegionFilter.currentTerms === 'function') {
            return window.AjinsafroActivityRegionFilter.currentTerms();
        }

        return [];
    }

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

    function showListAlert(type, message) {
        if (!listAlert) return;
        listAlert.className = 'alert alert-' + type;
        listAlert.textContent = message;
        listAlert.classList.remove('d-none');
    }

    function hideListAlert() {
        if (!listAlert) return;
        listAlert.classList.add('d-none');
    }

    function showFormAlert(type, message) {
        if (!formAlert) return;
        formAlert.className = 'alert alert-' + type;
        formAlert.textContent = message;
        formAlert.classList.remove('d-none');
    }

    function hideFormAlert() {
        if (!formAlert) return;
        formAlert.classList.add('d-none');
    }

    function setListLoading(show) {
        if (!listLoader) return;
        listLoader.classList.toggle('d-none', !show);
    }

    function setFormLoading(loading) {
        var btnText = formSubmitBtn && formSubmitBtn.querySelector('.btn-text');
        var spinner = formSubmitBtn && formSubmitBtn.querySelector('.spinner-border');
        if (formSubmitBtn) formSubmitBtn.disabled = loading;
        if (btnText) btnText.classList.toggle('d-none', loading);
        if (spinner) spinner.classList.toggle('d-none', !loading);
        if (formResetBtn) formResetBtn.disabled = loading;
    }

    function normalizeActivity(raw) {
        return {
            id: Number(raw.id),
            title: raw.title || '',
            slug: raw.slug || '',
            description: raw.description || '',
            activity_type: raw.activity_type || '',
            region_name: raw.region_name || raw.location_text || raw.place_text || '',
            location_text: raw.location_text || raw.region_name || '',
            place_text: raw.place_text || raw.region_name || '',
            adult_price: raw.adult_price || raw.base_price || raw.price || 0,
            child_price: raw.child_price || 0,
            base_price: raw.base_price || raw.adult_price || raw.price || 0,
            default_duration_minutes: raw.default_duration_minutes || 0,
            min_age: raw.min_age || 0,
            max_age: raw.max_age || 0,
            icon: raw.icon || '',
            image_url: raw.image_url || '',
            is_active: Boolean(raw.is_active),
        };
    }

    function cardHtml(activity) {
        var badge = activity.is_active
            ? '<span class="badge bg-success-subtle text-success">Active</span>'
            : '<span class="badge bg-secondary-subtle text-secondary">Inactive</span>';

        return '<div class="card h-100 programme-catalog-card">' +
            '<div class="card-body d-flex flex-column" data-activity-view>' +
                '<div class="d-flex justify-content-between align-items-start gap-2">' +
                    '<div>' +
                        '<h6 class="card-title mb-1" data-activity-title>' + esc(activity.title) + '</h6>' +
                        '<div class="small text-muted">' + esc(activity.activity_type || 'Type non renseigne') + '</div>' +
                    '</div>' +
                    '<div class="d-flex gap-1">' +
                        '<button type="button" class="btn btn-sm btn-light" data-action="edit" data-id="' + activity.id + '" title="Modifier"><i class="bx bx-pencil"></i></button>' +
                        '<button type="button" class="btn btn-sm btn-light text-danger" data-action="delete" data-id="' + activity.id + '" title="Supprimer"><i class="bx bx-trash"></i></button>' +
                    '</div>' +
                '</div>' +
                '<div class="small text-muted mb-2">' + esc(activity.region_name || 'Region non renseignee') + '</div>' +
                '<p class="card-text small text-muted flex-grow-1" data-activity-description>' + esc(limitText(activity.description, 90)) + '</p>' +
                '<div class="small mb-1">Adulte: ' + esc(activity.adult_price || '0.00') + ' MAD</div>' +
                '<div class="small text-muted mb-1">Enfant: ' + esc(activity.child_price || '0.00') + ' MAD</div>' +
                '<div class="small text-muted mb-2">Prix: ' + esc(activity.base_price || '0.00') + ' • Durée: ' + esc(activity.default_duration_minutes || '—') + ' min</div>' +
                '<div class="d-flex align-items-center justify-content-between gap-2 mt-auto">' +
                    badge +
                    '<button type="button" class="btn btn-sm btn-primary day-builder-add-activity" data-activity-id="' + activity.id + '" data-activity-title="' + esc(activity.title) + '">Ajouter au jour</button>' +
                '</div>' +
            '</div>' +
        '</div>';
    }

    function renderCards() {
        var html = state.activities.map(function(activity) {
            return '<div class="col-md-6 col-lg-6" data-activity-card-id="' + activity.id + '">' + cardHtml(activity) + '</div>';
        }).join('');
        cardsContainer.innerHTML = html;
        emptyMsg.classList.toggle('d-none', state.activities.length > 0);
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
        ['ALL_PROGRAMME_ACTIVITIES_CATALOG', 'ALL_TOUR_ACTIVITIES_CATALOG', 'PROGRAMME_ACTIVITIES_CATALOG', 'TOUR_ACTIVITIES_CATALOG'].forEach(function(key) {
            if (!Array.isArray(window[key])) window[key] = [];
            var idx = window[key].findIndex(function(item) { return Number(item.id) === Number(activity.id); });
            var payload = {
                id: Number(activity.id),
                title: activity.title,
                description: activity.description,
                activity_type: activity.activity_type || '',
                region_name: activity.region_name || activity.location_text || '',
                location_text: activity.location_text || activity.region_name || '',
                place_text: activity.place_text || activity.region_name || '',
                base_price: activity.base_price || 0,
                adult_price: activity.adult_price || activity.base_price || 0,
                child_price: activity.child_price || 0,
                default_duration_minutes: activity.default_duration_minutes || 0,
                min_age: activity.min_age || 0,
                max_age: activity.max_age || 0,
            };
            if (idx >= 0) window[key][idx] = Object.assign({}, window[key][idx], payload);
            else window[key].unshift(payload);
        });

        if (window.AjinsafroActivityRegionFilter && typeof window.AjinsafroActivityRegionFilter.apply === 'function') {
            window.AjinsafroActivityRegionFilter.apply();
        }
    }

    function removeCatalogEntry(activityId) {
        ['ALL_PROGRAMME_ACTIVITIES_CATALOG', 'ALL_TOUR_ACTIVITIES_CATALOG', 'PROGRAMME_ACTIVITIES_CATALOG', 'TOUR_ACTIVITIES_CATALOG'].forEach(function(key) {
            if (!Array.isArray(window[key])) return;
            window[key] = window[key].filter(function(item) { return Number(item.id) !== Number(activityId); });
        });

        if (window.AjinsafroActivityRegionFilter && typeof window.AjinsafroActivityRegionFilter.apply === 'function') {
            window.AjinsafroActivityRegionFilter.apply();
        }
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

    function clearFieldErrors() {
        formView.querySelectorAll('[data-error]').forEach(function(el) {
            el.classList.add('d-none');
            el.textContent = '';
        });
        [formTitleInput, formTypeInput, formRegionInput, formSlugInput, formDescriptionInput, formImageInput, formBasePriceInput, formChildPriceInput, formMinAgeInput, formMaxAgeInput, formIconInput, formDurationInput].forEach(function(input) {
            if (input) input.classList.remove('is-invalid');
        });
    }

    function applyFieldErrors(errors) {
        clearFieldErrors();
        if (!errors) return;
        Object.keys(errors).forEach(function(field) {
            var normalizedField = field.indexOf('gallery_images.') === 0 ? 'gallery_images' : field;
            var errEl = formView.querySelector('[data-error="' + normalizedField + '"]');
            if (errEl) {
                errEl.textContent = Array.isArray(errors[field]) ? errors[field][0] : String(errors[field]);
                errEl.classList.remove('d-none');
            }
            var map = {
                title: formTitleInput,
                activity_type: formTypeInput,
                region_name: formRegionInput,
                slug: formSlugInput,
                description: formDescriptionInput,
                image: formImageInput,
                gallery_images: formImageInput,
                adult_price: formBasePriceInput,
                child_price: formChildPriceInput,
                min_age: formMinAgeInput,
                max_age: formMaxAgeInput,
                icon: formIconInput,
                default_duration_minutes: formDurationInput,
            };
            if (map[normalizedField]) map[normalizedField].classList.add('is-invalid');
        });
    }

    function showListMode() {
        state.mode = 'list';
        formView.classList.add('d-none');
        listView.classList.remove('d-none');
        hideFormAlert();
        clearFieldErrors();
    }

    function showFormMode(mode) {
        state.mode = mode;
        listView.classList.add('d-none');
        formView.classList.remove('d-none');
        formTitle.textContent = mode === 'edit' ? 'Modifier une activité' : 'Créer une activité';
        hideFormAlert();
        clearFieldErrors();
    }

    function resetFormValues() {
        formId.value = '';
        formTitleInput.value = '';
        formTypeInput.value = '';
        formRegionInput.value = currentRegionTerms()[0] || '';
        formSlugInput.value = '';
        formDescriptionInput.value = '';
        formImageInput.value = '';
        formBasePriceInput.value = '';
        formChildPriceInput.value = '';
        formMinAgeInput.value = '';
        formMaxAgeInput.value = '';
        formIconInput.value = '';
        formDurationInput.value = '';
        formIsActiveInput.checked = true;
        currentImageWrap.classList.add('d-none');
        currentImage.src = '';
        previewImageWrap.classList.add('d-none');
        previewImage.src = '';
    }

    async function fetchList(search) {
        setListLoading(true);
        hideListAlert();
        try {
            var params = new URLSearchParams();
            if (search && search.trim()) {
                params.set('search', search.trim());
            }
            currentRegionTerms().forEach(function(term) {
                params.append('regions[]', term);
            });

            var url = urls.list + (params.toString() ? ('?' + params.toString()) : '');
            var response = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });
            var json = await parseJsonResponse(response);
            state.activities = (json.data || []).map(normalizeActivity);
            renderCards();
        } catch (error) {
            showListAlert('danger', error.message || 'Impossible de charger les activités.');
        } finally {
            setListLoading(false);
        }
    }

    async function fetchActivityForEdit(id) {
        hideFormAlert();
        clearFieldErrors();
        setFormLoading(true);
        try {
            var response = await fetch(urls.showBase + '/' + id, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });
            var json = await parseJsonResponse(response);
            var data = normalizeActivity(json.data || {});

            formId.value = String(data.id);
            formTitleInput.value = data.title || '';
            formTypeInput.value = data.activity_type || '';
            formRegionInput.value = data.region_name || data.location_text || '';
            formSlugInput.value = data.slug || '';
            formDescriptionInput.value = data.description || '';
            formImageInput.value = '';
            formBasePriceInput.value = data.adult_price || data.base_price || '';
            formChildPriceInput.value = data.child_price || '';
            formMinAgeInput.value = data.min_age || '';
            formMaxAgeInput.value = data.max_age || '';
            formIconInput.value = data.icon || '';
            formDurationInput.value = data.default_duration_minutes || '';
            formIsActiveInput.checked = Boolean(data.is_active);

            if (data.image_url) {
                currentImage.src = data.image_url;
                currentImageWrap.classList.remove('d-none');
            } else {
                currentImageWrap.classList.add('d-none');
                currentImage.src = '';
            }
            previewImageWrap.classList.add('d-none');
            previewImage.src = '';

            showFormMode('edit');
        } catch (error) {
            showToast(error.message || 'Impossible de charger l’activité.', 'danger');
        } finally {
            setFormLoading(false);
        }
    }

    function buildFormData() {
        var fd = new FormData();
        fd.append('_token', csrfToken);
        fd.append('activity_type', (formTypeInput.value || '').trim());
        fd.append('region_name', (formRegionInput.value || '').trim());
        fd.append('title', (formTitleInput.value || '').trim());
        fd.append('slug', (formSlugInput.value || '').trim());
        fd.append('description', formDescriptionInput.value || '');
        fd.append('adult_price', formBasePriceInput.value || '');
        fd.append('child_price', formChildPriceInput.value || '');
        fd.append('min_age', formMinAgeInput.value || '');
        fd.append('max_age', formMaxAgeInput.value || '');
        fd.append('icon', formIconInput.value || '');
        fd.append('default_duration_minutes', formDurationInput.value || '');
        fd.append('location_text', (formRegionInput.value || '').trim());
        fd.append('is_active', formIsActiveInput.checked ? '1' : '0');

        if (formImageInput.files && formImageInput.files[0]) {
            fd.append('image', formImageInput.files[0]);
        }

        return fd;
    }

    async function submitForm() {
        clearFieldErrors();
        hideFormAlert();

        var id = formId.value ? Number(formId.value) : null;
        var isEdit = Boolean(id);
        var url = isEdit ? (urls.updateBase + '/' + id + '/update') : urls.store;

        setFormLoading(true);
        try {
            var response = await fetch(url, {
                method: 'POST',
                body: buildFormData(),
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });
            var json = await parseJsonResponse(response);
            var activity = normalizeActivity(json.data || {});

            var idx = state.activities.findIndex(function(item) { return item.id === activity.id; });
            if (idx >= 0) {
                state.activities[idx] = activity;
            } else {
                state.activities.unshift(activity);
            }
            upsertCatalogEntry(activity);

            showListMode();
            fetchList(searchInput ? (searchInput.value || '') : '');
            showToast(json.message || (isEdit ? 'Activité mise à jour.' : 'Activité créée.'), 'success');
        } catch (error) {
            if (error.status === 422 && error.payload && error.payload.errors) {
                applyFieldErrors(error.payload.errors);
                showFormAlert('warning', error.payload.message || 'Veuillez corriger les erreurs.');
            } else {
                showFormAlert('danger', error.message || 'Échec de l’enregistrement.');
            }
        } finally {
            setFormLoading(false);
        }
    }

    async function deleteActivity(activityId) {
        if (!window.confirm('Supprimer cette activité ?')) return;

        try {
            var response = await fetch(urls.destroyBase + '/' + activityId, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                },
                credentials: 'same-origin'
            });

            var json = await parseJsonResponse(response);
            state.activities = state.activities.filter(function(item) {
                return item.id !== Number(activityId);
            });
            removeCatalogEntry(activityId);
            fetchList(searchInput ? (searchInput.value || '') : '');
            showToast(json.message || 'Activité supprimée.', 'success');
        } catch (error) {
            showToast(error.message || 'Suppression impossible.', 'danger');
        }
    }

    if (openCreateBtn) {
        openCreateBtn.addEventListener('click', function() {
            state.editingId = null;
            resetFormValues();
            showFormMode('create');
        });
    }

    if (backToListBtn) {
        backToListBtn.addEventListener('click', function() {
            showListMode();
        });
    }

    if (formSubmitBtn) {
        formSubmitBtn.addEventListener('click', function(e) {
            e.preventDefault();
            submitForm();
        });
    }

    if (formResetBtn) {
        formResetBtn.addEventListener('click', function() {
            if (state.mode === 'edit' && state.editingId) {
                fetchActivityForEdit(state.editingId);
            } else {
                resetFormValues();
                clearFieldErrors();
                hideFormAlert();
            }
        });
    }

    if (formImageInput) {
        formImageInput.addEventListener('change', function() {
            if (!formImageInput.files || !formImageInput.files[0]) {
                previewImageWrap.classList.add('d-none');
                previewImage.src = '';
                return;
            }
            var reader = new FileReader();
            reader.onload = function(e) {
                previewImage.src = e.target.result;
                previewImageWrap.classList.remove('d-none');
            };
            reader.readAsDataURL(formImageInput.files[0]);
        });
    }

    if (searchInput) {
        var timer = null;
        searchInput.addEventListener('input', function() {
            clearTimeout(timer);
            timer = setTimeout(function() {
                fetchList(searchInput.value || '');
            }, 250);
        });
    }

    root.addEventListener('click', function(e) {
        var editBtn = e.target.closest('[data-action="edit"]');
        if (editBtn) {
            e.preventDefault();
            var id = Number(editBtn.getAttribute('data-id'));
            if (!id) return;
            state.editingId = id;
            fetchActivityForEdit(id);
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

    document.addEventListener('voyage-activity-region-change', function() {
        if (state.mode === 'list') {
            fetchList(searchInput ? (searchInput.value || '') : '');
        } else if (!formRegionInput.value.trim()) {
            formRegionInput.value = currentRegionTerms()[0] || '';
        }
    });

    fetchList('');
})();
</script>
<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\circuits\voyages\components\ActivitiesManager.blade.php ENDPATH**/ ?>