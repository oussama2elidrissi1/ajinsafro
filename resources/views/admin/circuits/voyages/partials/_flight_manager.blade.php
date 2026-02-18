{{-- 
Flight Manager Component - Réutilisable pour onglet normal ET modal 
@param string $mode - 'full' pour onglet normal, 'modal' pour modal compact
@param array $flightOptionsWithIndex - Options de vol existantes
@param int $nextFlightOptionIndex - Prochain index pour nouvelle option
@param int $lastDayNumber - Dernier jour du circuit
@param Collection $airlines - Collection des compagnies aériennes
@param int|null $dayNumber - Jour courant (pour contexte modal)
@param int|null $totalDays - Nombre total de jours
--}}

@php
    $mode = $mode ?? 'full';
    $isModal = $mode === 'modal';
    $flightOptionsWithIndex = $flightOptionsWithIndex ?? [];
    $nextFlightOptionIndex = $nextFlightOptionIndex ?? 0;
    $lastDayNumber = $lastDayNumber ?? 1;
    $airlines = $airlines ?? collect();
    $dayNumber = $dayNumber ?? null;
    $totalDays = $totalDays ?? $lastDayNumber;
    $containerId = $isModal ? 'modal-flights-container' : 'flights-container';
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
    
    @if($isModal)
        {{-- CSS compact pour modal --}}
        <style>
        .flight-manager[data-mode="modal"] .flight-opt-card { margin-bottom: 8px; }
        .flight-manager[data-mode="modal"] .flight-opt-header { padding: 8px 12px; font-size: 12px; }
        .flight-manager[data-mode="modal"] .flight-opt-body { padding: 12px; gap: 12px; }
        .flight-manager[data-mode="modal"] .flight-opt-icon { width: 36px; height: 36px; font-size: 16px; }
        .flight-manager[data-mode="modal"] .flight-opt-section h6 { font-size: 14px; margin-bottom: 8px; }
        .flight-manager[data-mode="modal"] .btn { font-size: 12px; }
        .flight-manager[data-mode="modal"] .modal-flight-context { 
            background: #e7f1ff; 
            border: 1px solid #b6d7ff; 
            border-radius: 6px; 
            padding: 12px; 
            margin-bottom: 16px; 
        }
        .flight-manager[data-mode="modal"] .modal-flight-context .btn { margin-top: 8px; }
        </style>
        
        {{-- Contexte jour courant pour modal --}}
        @if($dayNumber)
            <div class="modal-flight-context">
                <div class="d-flex align-items-center">
                    <i class="bx bx-calendar me-2 text-primary"></i>
                    <div class="flex-grow-1">
                        @if($dayNumber == 1)
                            <strong class="text-primary">Jour {{ $dayNumber }}</strong> - Configuration du vol aller
                            <div class="small text-muted">Ce jour correspond au vol aller du circuit</div>
                        @elseif($dayNumber == $totalDays)
                            <strong class="text-primary">Jour {{ $dayNumber }}</strong> - Configuration du vol retour
                            <div class="small text-muted">Ce jour correspond au vol retour du circuit</div>
                        @else
                            <strong class="text-primary">Jour {{ $dayNumber }}</strong> - Vols internes
                            <div class="small text-muted">Vols aller (Jour 1) et retour (Jour {{ $totalDays }}) à configurer séparément</div>
                        @endif
                    </div>
                </div>
                @if($dayNumber != 1 && $dayNumber != $totalDays)
                    <button type="button" class="btn btn-sm btn-outline-primary quick-access-flights" data-target="main-flights">
                        <i class="bx bx-external-link me-1"></i>Accéder aux vols aller/retour
                    </button>
                @endif
            </div>
        @endif
    @else
        {{-- Info pour onglet normal --}}
        <p class="alert alert-info py-2 mb-3 small">
            <i class="bx bx-info-circle"></i> 
            <strong>Vols Aller / Retour / Segments</strong> (plusieurs options possibles). 
            Les hôtels et transferts sont dans leurs propres onglets.
        </p>
    @endif

    {{-- Option "Sans vol" --}}
    <div class="flight-option-toggle mb-3">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="no-flights-toggle-{{ $containerId }}" 
                   {{ empty($flightOptionsWithIndex) ? 'checked' : '' }}>
            <label class="form-check-label fw-bold text-muted" for="no-flights-toggle-{{ $containerId }}">
                Sans vol (circuit terrestre uniquement)
            </label>
        </div>
    </div>

    {{-- Contenu principal des vols --}}
    <div class="flights-content" style="{{ empty($flightOptionsWithIndex) ? 'display: none;' : '' }}">
        
        @if(!$isModal && Route::has('admin.circuits.airlines.index'))
            <div class="mb-3">
                <a href="{{ route('admin.circuits.airlines.index') }}" class="btn btn-sm btn-outline-secondary" target="_blank">
                    <i class="bx bx-list-ul me-1"></i> Gérer les compagnies aériennes
                </a>
                @if($airlines->isEmpty())
                    <span class="text-muted ms-2">— Aucune compagnie. <a href="{{ route('admin.circuits.airlines.create') }}">Créer une compagnie</a></span>
                @endif
            </div>
        @endif

        {{-- Sections des vols selon contexte --}}
        @if($isModal && $dayNumber)
            @if($dayNumber == 1)
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
            @elseif($dayNumber == $totalDays) 
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
            @else
                @include('admin.circuits.voyages.partials._flight_section_focused', [
                    'type' => 'segment', 
                    'title' => "Vols internes - Jour {$dayNumber}",
                    'flightOptionsWithIndex' => $flightOptionsWithIndex,
                    'airlines' => $airlines,
                    'fmtDate' => $fmtDate,
                    'dash' => $dash,
                    'dayNumber' => $dayNumber,
                    'isModal' => true
                ])
            @endif
        @else
            {{-- Mode complet : toutes les sections --}}
            @include('admin.circuits.voyages.partials._flight_options_sections', [
                'flightOptionsWithIndex' => $flightOptionsWithIndex,
                'nextFlightOptionIndex' => $nextFlightOptionIndex,
                'lastDayNumber' => $lastDayNumber,
                'airlines' => $airlines
            ])
        @endif

        @if($isModal)
            <div class="modal-flight-validation mt-3">
                <div class="alert alert-warning alert-sm py-2 d-none" id="flight-validation-error">
                    <i class="bx bx-error-circle me-1"></i>
                    <span class="validation-message"></span>
                </div>
            </div>
        @endif
    </div>

    @if(!$isModal)
        <input type="hidden" id="flight-opt-next-index" value="{{ $nextFlightOptionIndex }}">
        <p class="text-muted small mt-2">Enregistrez le voyage pour sauvegarder les vols.</p>
    @endif
</div>

@if($isModal)
    {{-- JavaScript spécifique modal --}}
    <script>
    (function() {
        const container = document.getElementById('{{ $containerId }}');
        if (!container || container.dataset.flightManagerInitialized) return;
        container.dataset.flightManagerInitialized = 'true';

        const noFlightsToggle = container.querySelector('#no-flights-toggle-{{ $containerId }}');
        const flightsContent = container.querySelector('.flights-content');
        const validationAlert = container.querySelector('#flight-validation-error');
        const currentDay = {{ $dayNumber ?? 0 }};
        const totalDays = {{ $totalDays ?? 1 }};

        // Toggle sans vol
        if (noFlightsToggle && flightsContent) {
            noFlightsToggle.addEventListener('change', function() {
                flightsContent.style.display = this.checked ? 'none' : '';
                if (validationAlert) validationAlert.classList.add('d-none');
            });
        }

        // Validation en temps réel
        function validateFlights() {
            if (noFlightsToggle && noFlightsToggle.checked) return true;
            
            const outboundDate = container.querySelector('input[name*="[departure_date]"][name*="outbound"]');
            const returnDate = container.querySelector('input[name*="[departure_date]"][name*="return"]');
            
            if (outboundDate && returnDate && outboundDate.value && returnDate.value) {
                if (new Date(returnDate.value) <= new Date(outboundDate.value)) {
                    showValidationError("La date de retour doit être postérieure à la date d'aller");
                    return false;
                }
            }
            
            hideValidationError();
            return true;
        }

        function showValidationError(message) {
            if (validationAlert) {
                validationAlert.querySelector('.validation-message').textContent = message;
                validationAlert.classList.remove('d-none');
            }
        }

        function hideValidationError() {
            if (validationAlert) {
                validationAlert.classList.add('d-none');
            }
        }

        // Écouter les changements de dates
        container.addEventListener('change', function(e) {
            if (e.target.matches('input[type="date"]')) {
                validateFlights();
            }
        });

        // Focus contextuel
        @if($dayNumber == 1)
            // Auto-focus sur vol aller si jour 1
            setTimeout(() => {
                const addOutboundBtn = container.querySelector('.btn-add-flight-opt[data-type="outbound"]');
                if (addOutboundBtn) addOutboundBtn.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 300);
        @elseif($dayNumber == $totalDays)
            // Auto-focus sur vol retour si dernier jour
            setTimeout(() => {
                const addReturnBtn = container.querySelector('.btn-add-flight-opt[data-type="return"]');
                if (addReturnBtn) addReturnBtn.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 300);
        @endif

        // Bouton accès rapide
        const quickAccessBtn = container.querySelector('.quick-access-flights');
        if (quickAccessBtn) {
            quickAccessBtn.addEventListener('click', function() {
                const modal = container.closest('.modal');
                if (modal) {
                    const bsModal = bootstrap.Modal.getInstance(modal);
                    if (bsModal) bsModal.hide();
                }
                
                // Ouvrir onglet Vols principal
                setTimeout(() => {
                    const flightsTab = document.querySelector('a[href="#flights"]');
                    if (flightsTab) {
                        flightsTab.click();
                        flightsTab.scrollIntoView({ behavior: 'smooth' });
                    }
                }, 100);
            });
        }
    })();
    </script>
@endif