{{--
Flight Manager Component - Réutilisable pour onglet normal ET contexte compact (modal/drawer)
@param string $mode - 'full' pour onglet normal, 'modal'/'drawer' pour mode compact
@param array $flightOptionsWithIndex - Options de vol existantes
@param int $nextFlightOptionIndex - Prochain index pour nouvelle option
@param int $lastDayNumber - Dernier jour du circuit
@param Collection $airlines - Collection des compagnies aériennes
@param int|null $dayNumber - Jour courant (pour contexte compact)
@param int|null $totalDays - Nombre total de jours
--}}

@php
    $mode = $mode ?? 'full';
    $isCompact = in_array($mode, ['modal', 'drawer'], true);
    $flightOptionsWithIndex = $flightOptionsWithIndex ?? [];
    $nextFlightOptionIndex = $nextFlightOptionIndex ?? 0;
    $lastDayNumber = $lastDayNumber ?? 1;
    $airlines = $airlines ?? collect();
    $dayNumber = $dayNumber ?? 1;
    $totalDays = $totalDays ?? $lastDayNumber;
    $containerId = $isCompact ? ($mode . '-flights-container') : 'flights-container';
    $fmtDate = function($d) {
        if (!$d) return null;
        return $d instanceof \Carbon\Carbon ? $d->format('D, d M') : \Carbon\Carbon::parse($d)->format('D, d M');
    };
    $dash = '?';
@endphp

<div class="flight-manager"
     data-mode="{{ $mode }}"
     data-day-number="{{ $dayNumber }}"
     data-total-days="{{ $totalDays }}"
     id="{{ $containerId }}">

    @if($isCompact)
        <div class="alert alert-warning border-warning mb-3" role="alert">
            <h6 class="mb-2"><i class="bx bx-error me-1"></i>Section en cours de construction ? ne pas modifier</h6>
            <p class="mb-1">Cette section n?Test pas encore finalisée et ses champs ne sont pas pris en charge par la logique actuelle (enregistrement, validation, affichage).</p>
            <p class="mb-0">Merci de ne rien modifier ici pour le moment afin d?Téviter incohérences, erreurs de sauvegarde ou comportements inattendus. Cette partie sera activée dès qu?Telle sera prête.</p>
        </div>
    @endif

    @if($isCompact)
        <style>
        .flight-manager[data-mode="modal"] .flight-opt-card,
        .flight-manager[data-mode="drawer"] .flight-opt-card { margin-bottom: 8px; }
        .flight-manager[data-mode="modal"] .flight-opt-header,
        .flight-manager[data-mode="drawer"] .flight-opt-header { padding: 8px 12px; font-size: 12px; }
        .flight-manager[data-mode="modal"] .flight-opt-body,
        .flight-manager[data-mode="drawer"] .flight-opt-body { padding: 12px; gap: 12px; }
        .flight-manager[data-mode="modal"] .flight-opt-icon,
        .flight-manager[data-mode="drawer"] .flight-opt-icon { width: 36px; height: 36px; font-size: 16px; }
        .flight-manager[data-mode="modal"] .flight-section-focused,
        .flight-manager[data-mode="drawer"] .flight-section-focused { margin-bottom: 16px; }
        .flight-manager[data-mode="modal"] .flight-section-focused h6,
        .flight-manager[data-mode="drawer"] .flight-section-focused h6 { font-size: 14px; margin-bottom: 8px; }
        .flight-manager[data-mode="modal"] .btn,
        .flight-manager[data-mode="drawer"] .btn { font-size: 12px; }
        .flight-manager[data-mode="modal"] .compact-flight-context,
        .flight-manager[data-mode="drawer"] .compact-flight-context {
            background: #e7f1ff;
            border: 1px solid #b6d7ff;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 16px;
        }
        .flight-manager .compact-flight-guidance { border-style: dashed; }

        .flight-manager[data-mode="drawer"] .flight-section-header h6 {
            font-size: 15px;
            font-weight: 700;
        }
        .flight-manager[data-mode="drawer"] .flight-opt-view .px-3.pb-2 {
            display: flex;
            justify-content: flex-end;
        }
        .flight-manager[data-mode="drawer"] .flight-opt-edit .col-12 {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }
        .flight-manager[data-mode="drawer"] .flight-opt-header .flight-opt-remove,
        .flight-manager[data-mode="drawer"] .flight-opt-view .flight-opt-edit-btn {
            font-weight: 600;
        }
        .flight-manager[data-mode="drawer"] .flight-cards-container {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
        }
        .flight-manager[data-mode="drawer"] .flight-cards-container .flight-opt-card {
            margin-bottom: 0;
        }
        .flight-manager[data-mode="drawer"] .flight-opt-edit .row.g-2 {
            display: flex;
            flex-wrap: wrap;
            row-gap: 8px;
        }
        .flight-manager[data-mode="drawer"] .flight-opt-edit .col-md-6 {
            flex: 0 0 50%;
            max-width: 50%;
        }
        .flight-manager[data-mode="drawer"] .flight-opt-edit .col-md-4 {
            flex: 0 0 33.3333%;
            max-width: 33.3333%;
        }
        .flight-manager[data-mode="drawer"] .flight-opt-edit .col-12 {
            flex: 0 0 100%;
            max-width: 100%;
        }
        @media (min-width: 600px) {
            .flight-manager[data-mode="drawer"] .flight-cards-container {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        @media (max-width: 599px) {
            .flight-manager[data-mode="drawer"] .flight-opt-edit .col-md-6,
            .flight-manager[data-mode="drawer"] .flight-opt-edit .col-md-4 {
                flex: 0 0 100%;
                max-width: 100%;
            }
        }
        </style>

        <div class="compact-flight-context">
            <div class="d-flex align-items-start gap-2">
                <i class="bx bx-calendar text-primary mt-1"></i>
                <div class="flex-grow-1">
                    <div class="fw-semibold text-primary" data-flight-context-title></div>
                    <div class="small text-muted" data-flight-context-description></div>
                </div>
            </div>
        </div>
    @else
        <p class="alert alert-info py-2 mb-3 small">
            <i class="bx bx-info-circle"></i>
            <strong>Vols Aller / Retour / Segments</strong> (plusieurs options possibles).
            Les hôtels et transferts sont dans leurs propres onglets.
        </p>
    @endif

    <div class="flights-content">

        @if(!$isCompact)
            <div class="mb-3">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modal-airlines-manage" id="btn-open-airlines-modal">
                    <i class="bx bx-list-ul me-1"></i> Gérer les compagnies aériennes
                </button>
                @if($airlines->isEmpty())
                    <span class="text-muted ms-2">? Aucune compagnie. Cliquez pour en ajouter.</span>
                @endif
            </div>
        @endif

        @if($isCompact)
            <div data-compact-flight-section="outbound">
                @include('admin.circuits.voyages.partials._flight_section_focused', [
                    'type' => 'outbound',
                    'title' => 'Vol Aller - Jour 1',
                    'flightOptionsWithIndex' => $flightOptionsWithIndex,
                    'airlines' => $airlines,
                    'fmtDate' => $fmtDate,
                    'dash' => $dash,
                    'dayNumber' => 1,
                    'isModal' => true
                ])
            </div>

            <div data-compact-flight-section="segment">
                @include('admin.circuits.voyages.partials._flight_section_focused', [
                    'type' => 'segment',
                    'title' => "Vols pendant le circuit - Jour {$dayNumber}",
                    'flightOptionsWithIndex' => $flightOptionsWithIndex,
                    'airlines' => $airlines,
                    'fmtDate' => $fmtDate,
                    'dash' => $dash,
                    'dayNumber' => $dayNumber,
                    'isModal' => true
                ])
            </div>

            <div data-compact-flight-section="return">
                @include('admin.circuits.voyages.partials._flight_section_focused', [
                    'type' => 'return',
                    'title' => "Vol Retour - Jour {$totalDays}",
                    'flightOptionsWithIndex' => $flightOptionsWithIndex,
                    'airlines' => $airlines,
                    'fmtDate' => $fmtDate,
                    'dash' => $dash,
                    'dayNumber' => $totalDays,
                    'isModal' => true
                ])
            </div>

            <div class="alert alert-info compact-flight-guidance" data-compact-flight-section="mid">
                <p class="mb-2">Le Vol Aller se configure sur Jour 1, le Vol Retour sur Jour {{ $totalDays }}, et les vols pendant le circuit sur le jour courant.</p>
                <div class="d-flex gap-2 flex-wrap">
                    <button type="button" class="btn btn-sm btn-outline-primary" data-flight-jump-day="1">
                        Configurer Vol Aller (Jour 1)
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-flight-jump-day="{{ $totalDays }}">
                        Configurer Vol Retour (Jour {{ $totalDays }})
                    </button>
                </div>
            </div>

            <div class="modal-flight-validation mt-3">
                <div class="alert alert-warning alert-sm py-2 d-none" id="flight-validation-error">
                    <i class="bx bx-error-circle me-1"></i>
                    <span class="validation-message"></span>
                </div>
            </div>
        @else
            @include('admin.circuits.voyages.partials._flight_options_sections', [
                'flightOptionsWithIndex' => $flightOptionsWithIndex,
                'nextFlightOptionIndex' => $nextFlightOptionIndex,
                'lastDayNumber' => $lastDayNumber,
                'airlines' => $airlines,
                'departurePlaces' => $departurePlaces ?? collect()
            ])
        @endif
    </div>

    @if(!$isCompact)
        <input type="hidden" id="flight-opt-next-index" value="{{ $nextFlightOptionIndex }}">
        <p class="text-muted small mt-2">Enregistrez le voyage pour sauvegarder les vols.</p>
    @endif
</div>

@if(!$isCompact)
    <div class="modal fade" id="modal-airlines-manage" tabindex="-1" aria-labelledby="modal-airlines-manage-label" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-airlines-manage-label">Gestion des compagnies aériennes</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div id="airline-modal-alert" class="alert d-none" role="alert"></div>

                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <div class="input-group" style="max-width: 360px;">
                            <span class="input-group-text"><i class="bx bx-search"></i></span>
                            <input type="text" class="form-control" id="airline-search-input" placeholder="Rechercher Nom / IATA">
                        </div>
                        <button type="button" class="btn btn-primary" id="btn-airline-show-create">
                            <i class="bx bx-plus me-1"></i> Ajouter une compagnie
                        </button>
                    </div>

                    <div class="card border mb-3 d-none" id="airline-editor-card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <strong id="airline-editor-title">Ajouter une compagnie</strong>
                            <button type="button" class="btn btn-sm btn-light" id="btn-airline-cancel-edit">Fermer</button>
                        </div>
                        <div class="card-body">
                            <div id="airline-editor-form" novalidate>
                                <input type="hidden" id="airline-editor-id">
                                <div class="row g-3">
                                    <div class="col-md-5">
                                        <label class="form-label" for="airline-editor-name">Nom <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="airline-editor-name">
                                        <div class="invalid-feedback d-none" data-field-error="name"></div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label" for="airline-editor-iata">Code IATA</label>
                                        <input type="text" class="form-control" id="airline-editor-iata" maxlength="10">
                                        <div class="invalid-feedback d-none" data-field-error="iata_code"></div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label" for="airline-editor-logo">Logo URL</label>
                                        <input type="text" class="form-control" id="airline-editor-logo">
                                        <div class="invalid-feedback d-none" data-field-error="logo_url"></div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="airline-editor-active" checked>
                                            <label class="form-check-label" for="airline-editor-active">Actif</label>
                                        </div>
                                    </div>
                                    <div class="col-12 d-flex gap-2 justify-content-end">
                                        <button type="button" class="btn btn-light" id="btn-airline-reset-form">Réinitialiser</button>
                                        <button type="button" class="btn btn-primary" id="btn-airline-save">
                                            <span class="spinner-border spinner-border-sm me-1 d-none" id="airline-save-spinner"></span>
                                            Enregistrer
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="airline-table-loader" class="text-center py-4 d-none">
                        <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
                        <div class="small text-muted mt-2">Chargement des compagnies...</div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nom</th>
                                    <th>Code IATA</th>
                                    <th>Logo URL</th>
                                    <th>Statut</th>
                                    <th class="text-end" style="width: 180px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="airline-table-body">
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">Aucune compagnie</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="airline-toast-host" class="position-fixed" style="top:16px;right:16px;z-index:1080;"></div>
@endif

@if($isCompact)
    <script>
    (function() {
        const container = document.getElementById('{{ $containerId }}');
        if (!container || container.dataset.flightManagerInitialized) return;
        container.dataset.flightManagerInitialized = 'true';

        const flightsContent = container.querySelector('.flights-content');
        const validationAlert = container.querySelector('#flight-validation-error');
        const titleEl = container.querySelector('[data-flight-context-title]');
        const descEl = container.querySelector('[data-flight-context-description]');
        const outboundSection = container.querySelector('[data-compact-flight-section="outbound"]');
        const segmentSection = container.querySelector('[data-compact-flight-section="segment"]');
        const returnSection = container.querySelector('[data-compact-flight-section="return"]');
        const midSection = container.querySelector('[data-compact-flight-section="mid"]');
        const totalDays = parseInt(container.getAttribute('data-total-days') || '1', 10) || 1;

        function hideValidationError() {
            if (validationAlert) validationAlert.classList.add('d-none');
        }

        function setContextByDay(dayNumber) {
            const day = parseInt(dayNumber || '1', 10) || 1;
            container.setAttribute('data-day-number', String(day));

            if (titleEl && descEl) {
                if (day === 1) {
                    titleEl.textContent = 'Jour 1 ? Vol Aller';
                    descEl.textContent = 'Configuration du vol aller du circuit.';
                } else if (day === totalDays) {
                    titleEl.textContent = 'Jour ' + totalDays + ' ? Vol Retour';
                    descEl.textContent = 'Configuration du vol retour du circuit.';
                } else {
                    titleEl.textContent = 'Jour ' + day + ' ? Vols pendant le circuit';
                    descEl.textContent = 'Ajoutez ici les vols pendant le circuit pour ce jour. Aller/Retour restent sur Jour 1 et Jour ' + totalDays + '.';
                }
            }

            if (outboundSection) outboundSection.style.display = day === 1 ? '' : 'none';
            if (segmentSection) segmentSection.style.display = (day !== 1 && day !== totalDays) ? '' : 'none';
            if (returnSection) returnSection.style.display = day === totalDays ? '' : 'none';
            if (midSection) midSection.style.display = (day !== 1 && day !== totalDays) ? '' : 'none';

            if (segmentSection) {
                var segmentHeader = segmentSection.querySelector('.flight-section-header h6');
                if (segmentHeader) {
                    segmentHeader.innerHTML = '<i class="bx bx-trip me-1"></i>Vols pendant le circuit - Jour ' + day;
                }
                segmentSection.querySelectorAll('.btn-add-flight-opt[data-type="segment"]').forEach(function(btn) {
                    btn.setAttribute('data-day', String(day));
                });
                segmentSection.querySelectorAll('.flight-section-focused[data-type="segment"]').forEach(function(section) {
                    section.setAttribute('data-day', String(day));
                });
            }
            hideValidationError();
        }

        container.addEventListener('click', function(e) {
            const jumpBtn = e.target.closest('[data-flight-jump-day]');
            if (!jumpBtn) return;
            const targetDay = parseInt(jumpBtn.getAttribute('data-flight-jump-day') || '1', 10) || 1;
            setContextByDay(targetDay);
            document.dispatchEvent(new CustomEvent('day-builder:set-day', {
                detail: { dayNumber: targetDay, tab: 'flights' }
            }));
        });

        document.addEventListener('day-builder:context-changed', function(e) {
            const detail = (e && e.detail) ? e.detail : {};
            if (!detail.dayNumber) return;
            setContextByDay(detail.dayNumber);
        });

        setContextByDay(container.getAttribute('data-day-number') || 1);
    })();
    </script>
@else
    {{-- Full mode (onglet Vols Create/Edit) --}}
    <script>
    (function() {
        var container = document.getElementById('{{ $containerId }}');
        if (!container || container.dataset.fullFlightToggleInit) return;
        container.dataset.fullFlightToggleInit = 'true';

        if (window.__airlineManagerInit) {
            return;
        }
        window.__airlineManagerInit = true;

        var modalEl = document.getElementById('modal-airlines-manage');
        if (!modalEl) return;

        var airlineState = {
            list: [],
            lastInteractedSelect: null,
            selectedOnCreate: null,
        };

        var els = {
            modal: modalEl,
            searchInput: document.getElementById('airline-search-input'),
            tableBody: document.getElementById('airline-table-body'),
            tableLoader: document.getElementById('airline-table-loader'),
            alert: document.getElementById('airline-modal-alert'),
            editorCard: document.getElementById('airline-editor-card'),
            editorTitle: document.getElementById('airline-editor-title'),
            editorForm: document.getElementById('airline-editor-form'),
            editorId: document.getElementById('airline-editor-id'),
            editorName: document.getElementById('airline-editor-name'),
            editorIata: document.getElementById('airline-editor-iata'),
            editorLogo: document.getElementById('airline-editor-logo'),
            editorActive: document.getElementById('airline-editor-active'),
            saveBtn: document.getElementById('btn-airline-save'),
            saveSpinner: document.getElementById('airline-save-spinner'),
            showCreateBtn: document.getElementById('btn-airline-show-create'),
            cancelEditBtn: document.getElementById('btn-airline-cancel-edit'),
            resetFormBtn: document.getElementById('btn-airline-reset-form'),
            toastHost: document.getElementById('airline-toast-host'),
        };

        var endpoints = {
            list: '{{ route('admin.circuits.airlines.ajax.list') }}',
            store: '{{ route('admin.circuits.airlines.ajax.store') }}',
            updateBase: '{{ url('/admin/circuits/airlines/ajax') }}',
            destroyBase: '{{ url('/admin/circuits/airlines/ajax') }}',
        };

        function getCsrfToken() {
            var meta = document.querySelector('meta[name="csrf-token"]');
            if (meta && meta.content) return meta.content;
            return '{{ csrf_token() }}';
        }

        function showAlert(type, message) {
            if (!els.alert) return;
            els.alert.className = 'alert alert-' + type;
            els.alert.textContent = message;
            els.alert.classList.remove('d-none');
        }

        function hideAlert() {
            if (!els.alert) return;
            els.alert.classList.add('d-none');
        }

        function showToast(message, type) {
            if (!els.toastHost) return;
            var toast = document.createElement('div');
            toast.className = 'alert alert-' + (type || 'success') + ' alert-dismissible fade show mb-2';
            toast.innerHTML = '<span>' + (message || '') + '</span><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
            els.toastHost.appendChild(toast);
            setTimeout(function() {
                toast.remove();
            }, 3000);
        }

        function toggleTableLoader(show) {
            if (!els.tableLoader) return;
            els.tableLoader.classList.toggle('d-none', !show);
        }

        function setSaveLoading(loading) {
            if (els.saveBtn) els.saveBtn.disabled = loading;
            if (els.saveSpinner) els.saveSpinner.classList.toggle('d-none', !loading);
        }

        function clearValidationErrors() {
            if (!els.editorForm) return;
            els.editorForm.querySelectorAll('[data-field-error]').forEach(function(el) {
                el.classList.add('d-none');
                el.textContent = '';
            });
            [els.editorName, els.editorIata, els.editorLogo].forEach(function(input) {
                if (input) input.classList.remove('is-invalid');
            });
        }

        function applyValidationErrors(errors) {
            clearValidationErrors();
            if (!errors) return;

            Object.keys(errors).forEach(function(field) {
                var messages = Array.isArray(errors[field]) ? errors[field] : [errors[field]];
                var errorEl = els.editorForm.querySelector('[data-field-error="' + field + '"]');
                if (errorEl) {
                    errorEl.textContent = messages.join(' ');
                    errorEl.classList.remove('d-none');
                }

                if (field === 'name' && els.editorName) els.editorName.classList.add('is-invalid');
                if (field === 'iata_code' && els.editorIata) els.editorIata.classList.add('is-invalid');
                if (field === 'logo_url' && els.editorLogo) els.editorLogo.classList.add('is-invalid');
            });
        }

        function normalizeAirline(a) {
            return {
                id: Number(a.id),
                name: a.name || '',
                code_iata: a.code_iata || '',
                logo_url: a.logo_url || '',
                is_active: Boolean(a.is_active),
            };
        }

        function optionLabel(airline) {
            return airline.code_iata ? (airline.name + ' (' + airline.code_iata + ')') : airline.name;
        }

        function escapeHtml(value) {
            return String(value == null ? '' : value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function getAllAirlineSelects() {
            return Array.from(document.querySelectorAll('select[name$="[airline_id]"]'));
        }

        function syncAllAirlineSelects(preferredSelectedId) {
            var selects = getAllAirlineSelects();

            selects.forEach(function(selectEl) {
                var previousValue = selectEl.value;
                var placeholder = selectEl.querySelector('option[value=""]');
                var placeholderText = placeholder ? placeholder.textContent : '?';

                selectEl.innerHTML = '';
                var emptyOpt = document.createElement('option');
                emptyOpt.value = '';
                emptyOpt.textContent = placeholderText || '?';
                selectEl.appendChild(emptyOpt);

                airlineState.list.forEach(function(airline) {
                    var opt = document.createElement('option');
                    opt.value = String(airline.id);
                    opt.textContent = optionLabel(airline);
                    selectEl.appendChild(opt);
                });

                if (preferredSelectedId) {
                    selectEl.value = String(preferredSelectedId);
                    if (selectEl.value !== String(preferredSelectedId)) {
                        selectEl.value = previousValue;
                    }
                } else {
                    selectEl.value = previousValue;
                }

                if (selectEl.value && !selectEl.querySelector('option[value="' + selectEl.value + '"]')) {
                    selectEl.value = '';
                }
            });

            if (preferredSelectedId) {
                var target = airlineState.lastInteractedSelect;
                if (!target || !document.contains(target) || target.disabled) {
                    target = selects.find(function(s) {
                        return !s.disabled && s.offsetParent !== null;
                    }) || selects.find(function(s) { return !s.disabled; }) || null;
                }
                if (target) {
                    target.value = String(preferredSelectedId);
                    target.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }
        }

        function renderAirlinesTable() {
            if (!els.tableBody) return;

            var term = ((els.searchInput && els.searchInput.value) || '').trim().toLowerCase();
            var filtered = airlineState.list.filter(function(airline) {
                if (!term) return true;
                return airline.name.toLowerCase().includes(term)
                    || (airline.code_iata || '').toLowerCase().includes(term);
            });

            if (!filtered.length) {
                els.tableBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">Aucune compagnie trouvée</td></tr>';
                return;
            }

            els.tableBody.innerHTML = filtered.map(function(airline) {
                return '<tr data-airline-id="' + airline.id + '">' +
                    '<td>' + escapeHtml(airline.name) + '</td>' +
                    '<td>' + escapeHtml(airline.code_iata || '?') + '</td>' +
                    '<td>' + escapeHtml(airline.logo_url || '?') + '</td>' +
                    '<td>' + (airline.is_active
                        ? '<span class="badge bg-success-subtle text-success">Actif</span>'
                        : '<span class="badge bg-secondary-subtle text-secondary">Inactif</span>') + '</td>' +
                    '<td class="text-end">' +
                        '<button type="button" class="btn btn-sm btn-soft-primary me-1" data-action="edit" data-id="' + airline.id + '">Modifier</button>' +
                        '<button type="button" class="btn btn-sm btn-soft-danger" data-action="delete" data-id="' + airline.id + '">Supprimer</button>' +
                    '</td>' +
                '</tr>';
            }).join('');
        }

        function openEditorForCreate() {
            if (!els.editorCard) return;
            hideAlert();
            clearValidationErrors();
            els.editorTitle.textContent = 'Ajouter une compagnie';
            els.editorId.value = '';
            els.editorName.value = '';
            els.editorIata.value = '';
            els.editorLogo.value = '';
            els.editorActive.checked = true;
            els.editorCard.classList.remove('d-none');
            els.editorName.focus();
        }

        function openEditorForEdit(airlineId) {
            var airline = airlineState.list.find(function(item) {
                return item.id === Number(airlineId);
            });
            if (!airline || !els.editorCard) return;

            hideAlert();
            clearValidationErrors();
            els.editorTitle.textContent = 'Modifier la compagnie';
            els.editorId.value = String(airline.id);
            els.editorName.value = airline.name || '';
            els.editorIata.value = airline.code_iata || '';
            els.editorLogo.value = airline.logo_url || '';
            els.editorActive.checked = Boolean(airline.is_active);
            els.editorCard.classList.remove('d-none');
            els.editorName.focus();
        }

        function closeEditor() {
            if (!els.editorCard) return;
            els.editorCard.classList.add('d-none');
            clearValidationErrors();
            hideAlert();
        }

        async function parseResponse(response) {
            var json;
            try {
                json = await response.json();
            } catch (e) {
                json = null;
            }

            if (!response.ok || !json || json.success === false) {
                var err = new Error((json && json.message) || 'Une erreur est survenue.');
                err.status = response.status;
                err.payload = json;
                throw err;
            }

            return json;
        }

        async function loadAirlines() {
            toggleTableLoader(true);
            hideAlert();
            try {
                var query = ((els.searchInput && els.searchInput.value) || '').trim();
                var url = endpoints.list + (query ? ('?search=' + encodeURIComponent(query)) : '');
                var response = await fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                });
                var json = await parseResponse(response);
                airlineState.list = (json.data || []).map(normalizeAirline);
                renderAirlinesTable();
                syncAllAirlineSelects();
            } catch (error) {
                showAlert('danger', error.message || 'Impossible de charger les compagnies.');
            } finally {
                toggleTableLoader(false);
            }
        }

        async function submitEditorForm() {
            clearValidationErrors();
            hideAlert();

            var id = els.editorId.value ? Number(els.editorId.value) : null;
            var payload = {
                name: (els.editorName.value || '').trim(),
                iata_code: (els.editorIata.value || '').trim(),
                logo_url: (els.editorLogo.value || '').trim(),
                is_active: els.editorActive.checked ? 1 : 0,
            };

            setSaveLoading(true);
            try {
                var isUpdate = Boolean(id);
                var url = isUpdate
                    ? (endpoints.updateBase + '/' + id + '/update')
                    : endpoints.store;

                var response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': getCsrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(payload),
                });

                var json = await parseResponse(response);
                var savedAirline = normalizeAirline(json.data || {});

                if (isUpdate) {
                    airlineState.list = airlineState.list.map(function(item) {
                        return item.id === savedAirline.id ? savedAirline : item;
                    });
                    showToast(json.message || 'Compagnie mise à jour.', 'success');
                } else {
                    airlineState.list.push(savedAirline);
                    airlineState.selectedOnCreate = savedAirline.id;
                    showToast(json.message || 'Compagnie créée.', 'success');
                }

                airlineState.list.sort(function(a, b) {
                    return a.name.localeCompare(b.name);
                });

                renderAirlinesTable();
                syncAllAirlineSelects(airlineState.selectedOnCreate || null);
                airlineState.selectedOnCreate = null;
                closeEditor();
            } catch (error) {
                if (error.status === 422 && error.payload && error.payload.errors) {
                    applyValidationErrors(error.payload.errors);
                    showAlert('warning', error.payload.message || 'Veuillez corriger les erreurs de validation.');
                } else {
                    showAlert('danger', error.message || '?chec lors de l?Tenregistrement.');
                }
            } finally {
                setSaveLoading(false);
            }
        }

        async function deleteAirline(airlineId) {
            var airline = airlineState.list.find(function(item) {
                return item.id === Number(airlineId);
            });
            if (!airline) return;

            var confirmed = window.confirm('Supprimer la compagnie "' + airline.name + '" ?');
            if (!confirmed) return;

            hideAlert();
            try {
                var response = await fetch(endpoints.destroyBase + '/' + airline.id, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': getCsrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                });
                var json = await parseResponse(response);

                airlineState.list = airlineState.list.filter(function(item) {
                    return item.id !== airline.id;
                });

                renderAirlinesTable();
                syncAllAirlineSelects();
                showToast(json.message || 'Compagnie supprimée.', 'success');

                if (Number(els.editorId.value) === airline.id) {
                    closeEditor();
                }
            } catch (error) {
                showAlert('danger', error.message || 'Suppression impossible.');
            }
        }

        document.addEventListener('focusin', function(e) {
            var selectEl = e.target && e.target.closest ? e.target.closest('select[name$="[airline_id]"]') : null;
            if (selectEl) airlineState.lastInteractedSelect = selectEl;
        });

        document.addEventListener('change', function(e) {
            var selectEl = e.target && e.target.closest ? e.target.closest('select[name$="[airline_id]"]') : null;
            if (selectEl) airlineState.lastInteractedSelect = selectEl;
        });

        if (els.showCreateBtn) {
            els.showCreateBtn.addEventListener('click', function() {
                openEditorForCreate();
            });
        }

        if (els.cancelEditBtn) {
            els.cancelEditBtn.addEventListener('click', function() {
                closeEditor();
            });
        }

        if (els.resetFormBtn) {
            els.resetFormBtn.addEventListener('click', function() {
                if (els.editorId.value) {
                    openEditorForEdit(els.editorId.value);
                } else {
                    openEditorForCreate();
                }
            });
        }

        if (els.saveBtn) {
            els.saveBtn.addEventListener('click', function(e) {
                e.preventDefault();
                submitEditorForm();
            });
        }

        if (els.editorForm) {
            els.editorForm.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && e.target && e.target.tagName !== 'TEXTAREA') {
                    e.preventDefault();
                    submitEditorForm();
                }
            });
        }

        if (els.searchInput) {
            var debounceTimer = null;
            els.searchInput.addEventListener('input', function() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function() {
                    loadAirlines();
                }, 250);
            });
        }

        if (els.tableBody) {
            els.tableBody.addEventListener('click', function(e) {
                var editBtn = e.target.closest('[data-action="edit"]');
                if (editBtn) {
                    openEditorForEdit(editBtn.getAttribute('data-id'));
                    return;
                }

                var deleteBtn = e.target.closest('[data-action="delete"]');
                if (deleteBtn) {
                    deleteAirline(deleteBtn.getAttribute('data-id'));
                }
            });
        }

        modalEl.addEventListener('show.bs.modal', function() {
            hideAlert();
            loadAirlines();
        });
    })();
    </script>
@endif
