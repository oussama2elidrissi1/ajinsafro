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
    $dash = '—';
@endphp

<div class="flight-manager"
     data-mode="{{ $mode }}"
     data-day-number="{{ $dayNumber }}"
     data-total-days="{{ $totalDays }}"
     id="{{ $containerId }}">

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

    <div class="flight-option-toggle mb-3">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="no-flights-toggle-{{ $containerId }}"
                   {{ empty($flightOptionsWithIndex) ? 'checked' : '' }}>
            <label class="form-check-label fw-bold text-muted" for="no-flights-toggle-{{ $containerId }}">
                Sans vol (circuit terrestre uniquement)
            </label>
        </div>
    </div>

    <div class="flights-content" style="{{ empty($flightOptionsWithIndex) ? 'display: none;' : '' }}">

        @if(!$isCompact && Route::has('admin.circuits.airlines.index'))
            <div class="mb-3">
                <a href="{{ route('admin.circuits.airlines.index') }}" class="btn btn-sm btn-outline-secondary" target="_blank">
                    <i class="bx bx-list-ul me-1"></i> Gérer les compagnies aériennes
                </a>
                @if($airlines->isEmpty())
                    <span class="text-muted ms-2">— Aucune compagnie. <a href="{{ route('admin.circuits.airlines.create') }}">Créer une compagnie</a></span>
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
                'airlines' => $airlines
            ])
        @endif
    </div>

    @if(!$isCompact)
        <input type="hidden" id="flight-opt-next-index" value="{{ $nextFlightOptionIndex }}">
        <p class="text-muted small mt-2">Enregistrez le voyage pour sauvegarder les vols.</p>
    @endif
</div>

@if($isCompact)
    <script>
    (function() {
        const container = document.getElementById('{{ $containerId }}');
        if (!container || container.dataset.flightManagerInitialized) return;
        container.dataset.flightManagerInitialized = 'true';

        const noFlightsToggle = container.querySelector('#no-flights-toggle-{{ $containerId }}');
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
                    titleEl.textContent = 'Jour 1 — Vol Aller';
                    descEl.textContent = 'Configuration du vol aller du circuit.';
                } else if (day === totalDays) {
                    titleEl.textContent = 'Jour ' + totalDays + ' — Vol Retour';
                    descEl.textContent = 'Configuration du vol retour du circuit.';
                } else {
                    titleEl.textContent = 'Jour ' + day + ' — Vols pendant le circuit';
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

        if (noFlightsToggle && flightsContent) {
            noFlightsToggle.addEventListener('change', function() {
                flightsContent.style.display = this.checked ? 'none' : '';
                hideValidationError();
            });
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
@endif