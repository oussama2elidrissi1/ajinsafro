@extends('layouts.master-ajinsafro')
@section('title')
    Créer un tour WordPress
@endsection
@push('styles')
    <link href="{{ URL::asset('css/voyage-edit.css?v=' . md5_file(public_path('css/voyage-edit.css'))) }}" rel="stylesheet" type="text/css" />
@endpush
@section('content')
@php
    $isCreate = true;
    $voyageHeader = (object) [
        'ID' => 0,
        'post_title' => '',
        'post_status' => old('post_status', 'draft'),
        'post_modified' => '',
        'name' => '',
    ];
    $veDestinationCreate = old('destination') ? trim((string) old('destination')) : null;
@endphp
<div class="voyage-edit-page">
    <div class="ve-shell">
        @include('admin.circuits.voyages.partials._voyage_page_header', [
            'isCreate' => true,
            'voyage' => $voyageHeader,
            'veWpId' => 0,
            'laravelV' => null,
            'vePriceLabel' => null,
            'veDestination' => $veDestinationCreate,
            'formId' => 'create-voyage-form',
        ])
    </div>

    <form id="create-voyage-form" action="{{ route('admin.circuits.voyages.store') }}" method="POST">
        @csrf
        <div class="ve-shell">
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

        <div class="ve-page-layout">
        <div class="ve-main-col">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Informations principales</h4>
                        
                        <div class="mb-3">
                            <label for="title" class="form-label">Titre du tour <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" value="{{ old('title') }}" required placeholder="Ex : Séjour Dubaï 7 jours (6 nuits)">
                        </div>

                        <div class="mb-3">
                            <label for="slug" class="form-label">Slug (URL)</label>
                            <input type="text" class="form-control" id="slug" name="slug" value="{{ old('slug') }}" placeholder="laissez vide pour générer automatiquement">
                        </div>

                        <div class="mb-3">
                            <label for="content" class="form-label">Description complète</label>
                            <textarea class="form-control" id="content" name="content" rows="10" placeholder="Description détaillée du tour">{{ old('content') }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label for="excerpt" class="form-label">Extrait / Accroche</label>
                            <textarea class="form-control" id="excerpt" name="excerpt" rows="3" placeholder="Texte court pour l'aperçu">{{ old('excerpt') }}</textarea>
                        </div>
                    </div>
                </div>
        {{-- Vols — Flight cards (même style qu’édition) --}}
        @php
            $airlines = $airlines ?? collect();
            $hasSecondFlightCreate = old('flights.1.airline_id') || old('flights.1.cabin_class');
            $flightDash = '—';
        @endphp
                <div id="create-flights-content" class="create-flights-crud">
                <style>
                .flight-card-admin { background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,.08); border: 1px solid #e9ecef; overflow: hidden; }
                .flight-card-admin .flight-card-header { display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; background: #f8f9fa; border-bottom: 1px solid #e9ecef; }
                .flight-card-admin .flight-card-title { font-size: 13px; font-weight: 600; color: #495057; }
                .flight-card-admin .flight-remove-btn { background: none; border: none; color: #dc3545; font-size: 12px; font-weight: 600; cursor: pointer; padding: 0 4px; }
                .flight-card-admin .flight-card-body { display: flex; align-items: stretch; padding: 16px; gap: 16px; }
                .flight-card-admin .flight-card-col { display: flex; flex-direction: column; justify-content: center; }
                .flight-card-admin .flight-icon-circle { width: 48px; height: 48px; border-radius: 50%; background: #e7f1ff; color: #0d6efd; display: flex; align-items: center; justify-content: center; font-size: 20px; }
                .flight-card-admin .flight-card-center { flex: 1; display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
                .flight-card-admin .flight-dep, .flight-card-admin .flight-arr { text-align: center; }
                .flight-card-admin .flight-date { font-size: 12px; color: #6c757d; margin-bottom: 2px; }
                .flight-card-admin .flight-place { font-size: 14px; font-weight: 500; color: #212529; }
                .flight-card-admin .flight-arrow { color: #adb5bd; font-size: 18px; }
                .flight-card-admin .flight-card-baggage { font-size: 12px; color: #6c757d; }
                .flight-card-admin .flight-card-badge-wrap { padding: 0 16px 12px; }
                .flight-card-admin .flight-badge-tentative { display: inline-block; padding: 4px 10px; border-radius: 20px; background: #f5e6d3; color: #856404; font-size: 11px; font-weight: 600; }
                .flight-block { margin-bottom: 20px; }
                .flight-block .flight-card-view { display: flex; align-items: flex-start; gap: 12px; flex-wrap: wrap; }
                .flight-block .flight-card-edit { padding: 16px; background: #f8f9fa; border-radius: 8px; border: 1px solid #e9ecef; }
                </style>

                {{-- Vol 1 --}}
                <div class="flight-block" data-flight-index="0">
                    <div class="flight-card-view" id="create-flight-0-card-view">
                        <div class="flight-card-admin" style="min-width: 320px;">
                            <div class="flight-card-header">
                                <span class="flight-card-title">✈ FLIGHT • <span id="create-flight-0-dep-label">{{ old('flights.0.depart_airport') ?: old('flights.0.depart_city') ?: $flightDash }}</span> to <span id="create-flight-0-arr-label">{{ old('flights.0.arrive_airport') ?: old('flights.0.arrive_city') ?: $flightDash }}</span></span>
                                <button type="button" class="flight-remove-btn create-flight-reset-btn" data-index="0">REMOVE</button>
                            </div>
                            <div class="flight-card-body">
                                <div class="flight-card-col"><div class="flight-icon-circle"><i class="bx bx-trip"></i></div></div>
                                <div class="flight-card-col flight-card-center">
                                    <div class="flight-dep"><div class="flight-date" id="create-flight-0-dep-date">{{ $flightDash }}</div><div class="flight-place" id="create-flight-0-dep-place">{{ old('flights.0.depart_airport') ?: old('flights.0.depart_city') ?: $flightDash }}</div></div>
                                    <div class="flight-arrow">→</div>
                                    <div class="flight-arr"><div class="flight-date" id="create-flight-0-arr-date">{{ $flightDash }}</div><div class="flight-place" id="create-flight-0-arr-place">{{ old('flights.0.arrive_airport') ?: old('flights.0.arrive_city') ?: $flightDash }}</div></div>
                                </div>
                                <div class="flight-card-col flight-card-baggage">
                                    <div>Cabin: <span id="create-flight-0-cabin-bag">{{ old('flights.0.cabin_baggage') ?: $flightDash }}</span></div>
                                    <div>Check-in: <span id="create-flight-0-checkin-bag">{{ old('flights.0.checkin_baggage') ?: $flightDash }}</span></div>
                                </div>
                            </div>
                            <div class="flight-card-badge-wrap">
                                <span class="flight-badge-tentative" id="create-flight-0-tentative-badge" style="display:{{ old('flights.0.is_tentative') ? 'inline-block' : 'none' }}">Tentative Flight</span>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary create-flight-edit-btn" data-index="0">Edit</button>
                    </div>
                    <div class="flight-card-edit" id="create-flight-0-edit" style="display:none;">
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label">Compagnie aérienne</label>
                                <select class="form-select" name="flights[0][airline_id]"><option value="">— Choisir —</option>
                                    @foreach($airlines as $airline)
                                        <option value="{{ $airline->id }}" {{ old('flights.0.airline_id') == $airline->id ? 'selected' : '' }}>{{ $airline->name }} @if($airline->code_iata)({{ $airline->code_iata }})@endif</option>
                                    @endforeach
                                </select></div>
                            <div class="col-md-6"><label class="form-label">Type de cabine</label>
                                <select class="form-select" name="flights[0][cabin_class]">
                                    @foreach(\App\Models\TourFlight::cabinOptions() as $value => $label)
                                        <option value="{{ $value }}" {{ old('flights.0.cabin_class', 'economy') == $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select></div>
                            <div class="col-md-4"><label class="form-label">Numéro de vol</label><input type="text" class="form-control" name="flights[0][flight_number]" value="{{ old('flights.0.flight_number') }}" placeholder="ex. AF1234"></div>
                            <div class="col-md-4"><label class="form-label">Ville départ</label><input type="text" class="form-control" name="flights[0][depart_city]" value="{{ old('flights.0.depart_city') }}" placeholder="ex. Casablanca"></div>
                            <div class="col-md-4"><label class="form-label">Aéroport départ</label><input type="text" class="form-control" name="flights[0][depart_airport]" value="{{ old('flights.0.depart_airport') }}" placeholder="ex. CMN"></div>
                            <div class="col-md-4"><label class="form-label">Ville arrivée</label><input type="text" class="form-control" name="flights[0][arrive_city]" value="{{ old('flights.0.arrive_city') }}" placeholder="ex. Paris"></div>
                            <div class="col-md-4"><label class="form-label">Aéroport arrivée</label><input type="text" class="form-control" name="flights[0][arrive_airport]" value="{{ old('flights.0.arrive_airport') }}" placeholder="ex. CDG"></div>
                            <div class="col-md-6"><label class="form-label">Date départ</label><input type="date" class="form-control" name="flights[0][depart_date]" value="{{ old('flights.0.depart_date') }}"></div>
                            <div class="col-md-6"><label class="form-label">Date arrivée</label><input type="date" class="form-control" name="flights[0][arrive_date]" value="{{ old('flights.0.arrive_date') }}"></div>
                            <div class="col-md-4"><label class="form-label">Cabin (ex. 7 KGS)</label><input type="text" class="form-control" name="flights[0][cabin_baggage]" value="{{ old('flights.0.cabin_baggage') }}"></div>
                            <div class="col-md-4"><label class="form-label">Check-in (ex. 20 KGS)</label><input type="text" class="form-control" name="flights[0][checkin_baggage]" value="{{ old('flights.0.checkin_baggage') }}"></div>
                            <div class="col-md-4"><label class="form-label">&nbsp;</label><div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="flights[0][is_tentative]" value="1" {{ old('flights.0.is_tentative') ? 'checked' : '' }}><label class="form-check-label">Vol tentative</label></div></div>
                            <div class="col-12"><input type="hidden" name="flights[0][is_default]" id="create_flights_0_is_default" value="{{ $hasSecondFlightCreate ? (old('flights.0.is_default', true) ? '1' : '0') : '1' }}"></div>
                            <div class="col-12"><button type="button" class="btn btn-sm btn-primary create-flight-save-btn me-2" data-index="0">Save flight</button><button type="button" class="btn btn-sm btn-secondary create-flight-cancel-btn" data-index="0">Cancel</button></div>
                        </div>
                    </div>
                </div>

                {{-- Vol 2 (optionnel) --}}
                <div class="flight-block" id="create-flight-2-block" style="{{ $hasSecondFlightCreate ? '' : 'display:none;' }}">
                    <div class="flight-card-view" id="create-flight-1-card-view">
                        <div class="flight-card-admin" style="min-width: 320px;">
                            <div class="flight-card-header">
                                <span class="flight-card-title">✈ FLIGHT • <span id="create-flight-1-dep-label">{{ old('flights.1.departure_airport') ?: $flightDash }}</span> to <span id="create-flight-1-arr-label">{{ old('flights.1.arrival_airport') ?: $flightDash }}</span></span>
                                <button type="button" class="flight-remove-btn create-flight-remove-vol2-btn">REMOVE</button>
                            </div>
                            <div class="flight-card-body">
                                <div class="flight-card-col"><div class="flight-icon-circle"><i class="bx bx-trip"></i></div></div>
                                <div class="flight-card-col flight-card-center">
                                    <div class="flight-dep"><div class="flight-date" id="create-flight-1-dep-date">{{ $flightDash }}</div><div class="flight-place" id="create-flight-1-dep-place">{{ old('flights.1.depart_airport') ?: old('flights.1.depart_city') ?: $flightDash }}</div></div>
                                    <div class="flight-arrow">→</div>
                                    <div class="flight-arr"><div class="flight-date" id="create-flight-1-arr-date">{{ $flightDash }}</div><div class="flight-place" id="create-flight-1-arr-place">{{ old('flights.1.arrive_airport') ?: old('flights.1.arrive_city') ?: $flightDash }}</div></div>
                                </div>
                                <div class="flight-card-col flight-card-baggage">
                                    <div>Cabin: <span id="create-flight-1-cabin-bag">{{ old('flights.1.cabin_baggage') ?: $flightDash }}</span></div>
                                    <div>Check-in: <span id="create-flight-1-checkin-bag">{{ old('flights.1.checkin_baggage') ?: $flightDash }}</span></div>
                                </div>
                            </div>
                            <div class="flight-card-badge-wrap">
                                <span class="flight-badge-tentative" id="create-flight-1-tentative-badge" style="display:{{ old('flights.1.is_tentative') ? 'inline-block' : 'none' }}">Tentative Flight</span>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary create-flight-edit-btn" data-index="1">Edit</button>
                    </div>
                    <div class="flight-card-edit" id="create-flight-1-edit" style="display:none;">
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label">Compagnie aérienne</label>
                                <select class="form-select" name="flights[1][airline_id]"><option value="">— Choisir —</option>
                                    @foreach($airlines as $airline)
                                        <option value="{{ $airline->id }}" {{ old('flights.1.airline_id') == $airline->id ? 'selected' : '' }}>{{ $airline->name }} @if($airline->code_iata)({{ $airline->code_iata }})@endif</option>
                                    @endforeach
                                </select></div>
                            <div class="col-md-6"><label class="form-label">Type de cabine</label>
                                <select class="form-select" name="flights[1][cabin_class]">
                                    @foreach(\App\Models\TourFlight::cabinOptions() as $value => $label)
                                        <option value="{{ $value }}" {{ old('flights.1.cabin_class', 'economy') == $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select></div>
                            <div class="col-md-4"><label class="form-label">Numéro de vol</label><input type="text" class="form-control" name="flights[1][flight_number]" value="{{ old('flights.1.flight_number') }}"></div>
                            <div class="col-md-4"><label class="form-label">Ville départ</label><input type="text" class="form-control" name="flights[1][depart_city]" value="{{ old('flights.1.depart_city') }}"></div>
                            <div class="col-md-4"><label class="form-label">Aéroport départ</label><input type="text" class="form-control" name="flights[1][depart_airport]" value="{{ old('flights.1.depart_airport') }}"></div>
                            <div class="col-md-4"><label class="form-label">Ville arrivée</label><input type="text" class="form-control" name="flights[1][arrive_city]" value="{{ old('flights.1.arrive_city') }}"></div>
                            <div class="col-md-4"><label class="form-label">Aéroport arrivée</label><input type="text" class="form-control" name="flights[1][arrive_airport]" value="{{ old('flights.1.arrive_airport') }}"></div>
                            <div class="col-md-6"><label class="form-label">Date départ</label><input type="date" class="form-control" name="flights[1][depart_date]" value="{{ old('flights.1.depart_date') }}"></div>
                            <div class="col-md-6"><label class="form-label">Date arrivée</label><input type="date" class="form-control" name="flights[1][arrive_date]" value="{{ old('flights.1.arrive_date') }}"></div>
                            <div class="col-md-4"><label class="form-label">Cabin (ex. 7 KGS)</label><input type="text" class="form-control" name="flights[1][cabin_baggage]" value="{{ old('flights.1.cabin_baggage') }}"></div>
                            <div class="col-md-4"><label class="form-label">Check-in (ex. 20 KGS)</label><input type="text" class="form-control" name="flights[1][checkin_baggage]" value="{{ old('flights.1.checkin_baggage') }}"></div>
                            <div class="col-md-4"><label class="form-label">&nbsp;</label><div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="flights[1][is_tentative]" value="1" {{ old('flights.1.is_tentative') ? 'checked' : '' }}><label class="form-check-label">Vol tentative</label></div></div>
                            <div class="col-12"><div class="form-check"><input class="form-check-input create-flight-default-radio" type="radio" name="create_flights_default_radio" id="create_flights_default_0" value="0" {{ $hasSecondFlightCreate && old('flights.0.is_default', true) ? 'checked' : '' }}><label class="form-check-label" for="create_flights_default_0">Vol par défaut</label></div>
                                <div class="form-check"><input class="form-check-input create-flight-default-radio" type="radio" name="create_flights_default_radio" id="create_flights_default_1" value="1" {{ $hasSecondFlightCreate && old('flights.1.is_default') ? 'checked' : '' }}><label class="form-check-label" for="create_flights_default_1">Vol par défaut</label></div>
                                <input type="hidden" name="flights[1][is_default]" id="create_flights_1_is_default" value="{{ $hasSecondFlightCreate ? (old('flights.1.is_default') ? '1' : '0') : '0' }}"></div>
                            <div class="col-12"><button type="button" class="btn btn-sm btn-primary create-flight-save-btn me-2" data-index="1">Save flight</button><button type="button" class="btn btn-sm btn-secondary create-flight-cancel-btn" data-index="1">Cancel</button></div>
                        </div>
                    </div>
                </div>

                <div class="mb-3" id="create-flight2-add-wrap" style="{{ $hasSecondFlightCreate ? 'display:none;' : '' }}">
                    <button type="button" class="btn btn-outline-primary" id="create-flight2-add-btn"><i class="bx bx-plus"></i> Ajouter un 2ème vol</button>
                </div>
                </div>
        {{-- Tour Program Section --}}
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Tour Program</h4>
                        <p class="text-muted">Définissez le programme jour par jour de votre tour (identique à WordPress Traveler)</p>
                        
                        {{-- Program Style --}}
                        <div class="mb-4">
                            <label for="tours_program_style" class="form-label">Program Style</label>
                            <select name="tours_program_style" id="tours_program_style" class="form-select">
                                <option value="style1" {{ old('tours_program_style', 'style1') === 'style1' ? 'selected' : '' }}>Style 1</option>
                                <option value="style2" {{ old('tours_program_style') === 'style2' ? 'selected' : '' }}>Style 2</option>
                                <option value="style3" {{ old('tours_program_style') === 'style3' ? 'selected' : '' }}>Style 3</option>
                            </select>
                            <small class="text-muted">Choisissez le style d'affichage du programme sur le front-end</small>
                        </div>
                        
                        {{-- Program Items --}}
                        <div id="programItemsContainer">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0">Programme items</h5>
                                <button type="button" class="btn btn-success btn-sm" id="addProgramItemCreate">
                                    <i class="bx bx-plus"></i> Add new
                                </button>
                            </div>
                            
                            <div id="programItemsListCreate">
                                {{-- Items will be added dynamically --}}
                            </div>
                            
                            <div class="alert alert-info" id="emptyProgramAlert">
                                <i class="bx bx-info-circle"></i> Aucun item de programme. Cliquez sur "Add new" pour ajouter un élément.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card" id="activities">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Activités</h4>
                        @include('admin.circuits.voyages.partials._under_construction_notice', [
                            'title' => '⚠️ Section en cours de construction — ne pas modifier',
                            'tabName' => 'Activités',
                        ])
                    </div>
                </div>

                <div class="card" id="availability">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Disponibilité & Réservation</h4>
                        @include('admin.circuits.voyages.partials._availability_notice')
                    </div>
                </div>
        </div>{{-- /.ve-main-col --}}

        <aside class="ve-sidebar-col">
            <div class="ve-sticky-sidebar">

                {{-- ── ACTIONS (unique zone : créer / annuler) ── --}}
                <div class="card ve-sidebar-card ve-actions-card">
                    <div class="card-body">
                        <button type="submit" form="create-voyage-form" class="btn btn-primary">
                            <i class="bx bx-plus-circle"></i> Créer le tour
                        </button>
                        <a href="{{ route('admin.circuits.voyages.index') }}" class="btn btn-outline-secondary">
                            <i class="bx bx-x"></i> Annuler
                        </a>
                    </div>
                </div>

                {{-- ── PARAMÈTRES & PRIX ── --}}
                <div class="card ve-sidebar-card">
                    <div class="card-body">
                        <h5 class="ve-sidebar-title mb-3 fw-bold"><i class="bx bx-cog text-primary"></i> Paramètres & Prix</h5>

                        <div class="mb-3">
                            <label for="post_status" class="form-label">Statut</label>
                            <select class="form-select" id="post_status" name="post_status">
                                <option value="publish" {{ old('post_status') === 'publish' ? 'selected' : '' }}>Publié</option>
                                <option value="draft" {{ old('post_status') === 'draft' ? 'selected' : '' }}>Brouillon</option>
                                <option value="pending" {{ old('post_status') === 'pending' ? 'selected' : '' }}>En attente</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="destination" class="form-label">Destination</label>
                            <input type="text" class="form-control" id="destination" name="destination" value="{{ old('destination') }}" placeholder="Ex : Dubaï, EAU">
                        </div>
                        
                        <hr class="my-4">
                        
                        <h5 class="mb-2" style="font-size: 16px; font-weight: 600; color: #23282d;">Tour location</h5>
                        <p class="text-muted mb-3" style="font-size: 13px;">Select one or more location for your tour</p>
                        
                        <div class="mb-3">
                            <input 
                                type="text" 
                                id="locationSearchCreate" 
                                class="form-control" 
                                placeholder="Type to search"
                                style="font-size: 14px; padding: 6px 12px; border: 1px solid #ddd; border-radius: 3px;"
                            >
                        </div>
                        
                        <div class="wp-location-box" id="locationTreeContainer" style="border: 1px solid #ccd0d4; background: #fff; padding: 12px; max-height: 300px; overflow-y: auto; border-radius: 3px;">
                            @if(!empty($locationsTree))
                                @include('admin.circuits.voyages.partials.location-tree', [
                                    'locations' => $locationsTree, 
                                    'selectedIds' => $selectedLocationIds ?? []
                                ])
                            @else
                                <p class="text-muted mb-0" style="font-size: 13px; color: #646970;">Aucune location disponible</p>
                            @endif
                        </div>

                        <div class="mb-3">
                            <label for="duration_text" class="form-label">Durée</label>
                            <input type="text" class="form-control" id="duration_text" name="duration_text" value="{{ old('duration_text') }}" placeholder="Ex : 7 jours / 6 nuits">
                        </div>

                        <div class="mb-3">
                            <label for="adult_price" class="form-label">Prix Adulte (MAD)</label>
                            <input type="number" class="form-control" id="adult_price" name="adult_price" value="{{ old('adult_price') }}" step="0.01" min="0" placeholder="5000">
                        </div>

                        <div class="mb-3">
                            <label for="child_price" class="form-label">Prix Enfant (MAD)</label>
                            <input type="number" class="form-control" id="child_price" name="child_price" value="{{ old('child_price') }}" step="0.01" min="0" placeholder="3000">
                        </div>

                        <div class="mb-3">
                            <label for="min_price" class="form-label">Prix Minimum (MAD)</label>
                            <input type="number" class="form-control" id="min_price" name="min_price" value="{{ old('min_price') }}" step="0.01" min="0">
                        </div>

                        <div class="mb-3">
                            <label for="min_people" class="form-label">Nombre min. de personnes</label>
                            <input type="number" class="form-control" id="min_people" name="min_people" value="{{ old('min_people') }}" min="1" placeholder="2">
                        </div>
                        
                        <div class="mb-3">
                            <label for="max_people" class="form-label">Nombre max. de personnes</label>
                            <input type="number" class="form-control" id="max_people" name="max_people" value="{{ old('max_people') }}" min="1" placeholder="15">
                        </div>

                        <div class="mb-3">
                            <label for="hero_image_id" class="form-label">Image principale du voyage (Hero / Cover)</label>
                            <input type="number" class="form-control" id="hero_image_id" name="hero_image_id" value="{{ old('hero_image_id') }}" placeholder="Ex. 14434" min="0">
                        </div>

                        {{-- Galerie Hero (5 images) --}}
                        <div class="mb-4 p-3 border rounded bg-light">
                            <h5 class="mb-2" style="font-size: 14px; font-weight: 600;">Galerie Hero (5 images)</h5>
                            @php
                                $hero_gallery_ids = old('hero_gallery_ids', []);
                                if (!is_array($hero_gallery_ids)) {
                                    $hero_gallery_ids = is_string($hero_gallery_ids) ? explode(',', $hero_gallery_ids) : [];
                                }
                                $hero_gallery_ids = array_filter(array_map('trim', $hero_gallery_ids));
                                $hero_gallery_ids = array_slice($hero_gallery_ids, 0, 5);
                                while (count($hero_gallery_ids) < 5) {
                                    $hero_gallery_ids[] = '';
                                }
                            @endphp
                            <input type="hidden" name="hero_gallery_ids" id="hero_gallery_ids" value="{{ implode(',', array_filter($hero_gallery_ids)) }}">
                            <div id="hero-gallery-container" class="row g-2">
                                @for($i = 0; $i < 5; $i++)
                                    @php
                                        $img_id = $hero_gallery_ids[$i] ?? '';
                                        $img_url = $img_id ? \App\Services\Wp\WpHeroImageService::getAttachmentUrl((int) $img_id) : '';
                                    @endphp
                                    <div class="col-6 col-md-4">
                                        <div class="hero-gallery-item border rounded p-2 bg-white" data-index="{{ $i }}" style="font-size: 12px;">
                                            <label class="form-label small mb-1 d-block">
                                                Image {{ $i === 0 ? 'Principale' : ($i + 1) }}
                                            </label>
                                            <div class="hero-gallery-preview-wrap mb-2" style="width: 100%; height: 100px; border: 1px solid #ddd; border-radius: 4px; overflow: hidden; background: #f8f9fa; display: {{ $img_url ? 'block' : 'none' }};">
                                                <img src="{{ $img_url }}" alt="Preview {{ $i + 1 }}" class="hero-gallery-preview" style="width: 100%; height: 100%; object-fit: cover;">
                                            </div>
                                            <div class="hero-gallery-placeholder mb-2" style="width: 100%; height: 100px; border: 2px dashed #ddd; border-radius: 4px; display: flex; align-items: center; justify-content: center; background: #f8f9fa; {{ $img_url ? 'display: none;' : '' }}">
                                                <span class="text-muted" style="font-size: 11px;">Aucune image</span>
                                            </div>
                                            <div class="d-flex gap-1 flex-wrap">
                                                <button type="button" class="btn btn-outline-primary btn-sm hero-gallery-upload-btn" data-index="{{ $i }}" style="font-size: 10px; padding: 2px 6px;">
                                                    <i class="bx bx-upload"></i> Upload
                                                </button>
                                                <button type="button" class="btn btn-outline-secondary btn-sm hero-gallery-choose-btn" data-index="{{ $i }}" style="font-size: 10px; padding: 2px 6px;">
                                                    <i class="bx bx-images"></i> Choisir
                                                </button>
                                                <button type="button" class="btn btn-outline-danger btn-sm hero-gallery-remove-btn" data-index="{{ $i }}" style="font-size: 10px; padding: 2px 6px;" {{ !$img_id ? 'disabled' : '' }}>
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            </div>
                                            <input type="hidden" class="hero-gallery-id-input" data-index="{{ $i }}" value="{{ $img_id }}">
                                        </div>
                                    </div>
                                @endfor
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="thumbnail_id" class="form-label">Image à la une (ID WP)</label>
                            <input type="number" class="form-control" id="thumbnail_id" name="thumbnail_id" value="{{ old('thumbnail_id') }}" placeholder="14434">
                        </div>

                        <div class="mb-3">
                            <label for="gallery_ids" class="form-label">Galerie générale (images supplémentaires)</label>
                            <input type="text" class="form-control" id="gallery_ids" name="gallery_ids" value="{{ old('gallery_ids') }}" placeholder="14435,14436,14437">
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" value="1" {{ old('is_featured') ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_featured">
                                Tour à la une (Featured)
                            </label>
                        </div>
                        
                        <hr>
                    </div>
                    </div>
                </div>

            </div>
        </aside>

        </div>{{-- /.ve-page-layout --}}
        </div>{{-- /.ve-shell --}}
    </form>
</div>{{-- /.voyage-edit-page --}}
@endsection
@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
    <script>
        // Location search filter for create form (WordPress Traveler behavior)
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('locationSearchCreate');
            const locationItems = document.querySelectorAll('.wp-location-item');
            
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase().trim();
                    
                    if (searchTerm === '') {
                        locationItems.forEach(function(item) {
                            item.style.display = '';
                        });
                    } else {
                        locationItems.forEach(function(item) {
                            const title = item.getAttribute('data-title');
                            const selfMatches = title.includes(searchTerm);
                            const childMatches = Array.from(item.querySelectorAll('.wp-location-item')).some(function(child) {
                                return child.getAttribute('data-title').includes(searchTerm);
                            });
                            
                            if (selfMatches || childMatches) {
                                item.style.display = '';
                            } else {
                                item.style.display = 'none';
                            }
                        });
                    }
                });
            }
            
            // Update count
            const checkboxes = document.querySelectorAll('.location-checkbox');
            const updateCount = function() {
                const checked = document.querySelectorAll('.location-checkbox:checked').length;
                const countText = document.getElementById('locationCountTextCreate');
                if (countText) {
                    countText.textContent = checked + ' location(s) sélectionnée(s)';
                }
            };
            
            checkboxes.forEach(function(checkbox) {
                checkbox.addEventListener('change', updateCount);
            });
        });
        
        // Vols: flight cards + edit mode (create form)
        document.addEventListener('DOMContentLoaded', function() {
            var dash = '—';
            function parseDateTimeLocal(val) {
                if (!val) return null;
                var d = new Date(val);
                return isNaN(d.getTime()) ? null : d;
            }
            function formatCardDate(d) {
                if (!d) return dash;
                var days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
                var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                return days[d.getDay()] + ', ' + (d.getDate() < 10 ? '0' : '') + d.getDate() + ' ' + months[d.getMonth()];
            }
            function updateCreateCardFromForm(index) {
                var block = document.querySelector('.flight-block[data-flight-index="' + index + '"]');
                if (!block) return;
                var depCity = (block.querySelector('input[name="flights[' + index + '][depart_city]"]') || {}).value;
                var depAir = (block.querySelector('input[name="flights[' + index + '][depart_airport]"]') || {}).value;
                var arrCity = (block.querySelector('input[name="flights[' + index + '][arrive_city]"]') || {}).value;
                var arrAir = (block.querySelector('input[name="flights[' + index + '][arrive_airport]"]') || {}).value;
                var depDate = (block.querySelector('input[name="flights[' + index + '][depart_date]"]') || {}).value;
                var arrDate = (block.querySelector('input[name="flights[' + index + '][arrive_date]"]') || {}).value;
                var cabinBag = (block.querySelector('input[name="flights[' + index + '][cabin_baggage]"]') || {}).value;
                var checkinBag = (block.querySelector('input[name="flights[' + index + '][checkin_baggage]"]') || {}).value;
                var tentative = (block.querySelector('input[name="flights[' + index + '][is_tentative]"]') || {}).checked;
                var depLabel = depCity || depAir || dash;
                var arrLabel = arrCity || arrAir || dash;
                document.getElementById('create-flight-' + index + '-dep-label').textContent = depLabel;
                document.getElementById('create-flight-' + index + '-arr-label').textContent = arrLabel;
                document.getElementById('create-flight-' + index + '-dep-date').textContent = depDate ? formatCardDate(new Date(depDate + 'T12:00:00')) : dash;
                document.getElementById('create-flight-' + index + '-arr-date').textContent = arrDate ? formatCardDate(new Date(arrDate + 'T12:00:00')) : dash;
                document.getElementById('create-flight-' + index + '-dep-place').textContent = depLabel;
                document.getElementById('create-flight-' + index + '-arr-place').textContent = arrLabel;
                document.getElementById('create-flight-' + index + '-cabin-bag').textContent = cabinBag || dash;
                document.getElementById('create-flight-' + index + '-checkin-bag').textContent = checkinBag || dash;
                document.getElementById('create-flight-' + index + '-tentative-badge').style.display = tentative ? 'inline-block' : 'none';
            }
            function showCreateEdit(index) {
                document.getElementById('create-flight-' + index + '-card-view').style.display = 'none';
                document.getElementById('create-flight-' + index + '-edit').style.display = 'block';
            }
            function showCreateCard(index) {
                document.getElementById('create-flight-' + index + '-edit').style.display = 'none';
                document.getElementById('create-flight-' + index + '-card-view').style.display = 'flex';
                updateCreateCardFromForm(index);
            }
            document.querySelectorAll('.create-flight-edit-btn').forEach(function(btn) {
                btn.addEventListener('click', function() { showCreateEdit(parseInt(this.getAttribute('data-index'), 10)); });
            });
            document.querySelectorAll('.create-flight-save-btn, .create-flight-cancel-btn').forEach(function(btn) {
                btn.addEventListener('click', function() { showCreateCard(parseInt(this.getAttribute('data-index'), 10)); });
            });
            document.querySelectorAll('.create-flight-reset-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var block = document.querySelector('.flight-block[data-flight-index="0"]');
                    block.querySelectorAll('input, select').forEach(function(el) {
                        if (el.name && el.name.indexOf('flights[0]') !== -1) {
                            if (el.type === 'checkbox') el.checked = false; else el.value = '';
                        }
                    });
                    document.getElementById('create_flights_0_is_default').value = '1';
                    var cur = block.querySelector('input[name="flights[0][currency]"]');
                    if (cur) cur.value = 'MAD';
                    updateCreateCardFromForm(0);
                });
            });
            var removeVol2Btn = document.querySelector('.create-flight-remove-vol2-btn');
            var flight2Block = document.getElementById('create-flight-2-block');
            var addWrap = document.getElementById('create-flight2-add-wrap');
            if (removeVol2Btn && flight2Block) {
                removeVol2Btn.addEventListener('click', function() {
                    flight2Block.style.display = 'none';
                    addWrap.style.display = '';
                    flight2Block.querySelectorAll('input, select').forEach(function(el) {
                        if (el.name && el.name.indexOf('flights[1]') !== -1) { if (el.type === 'checkbox') el.checked = false; else el.value = ''; }
                    });
                    document.getElementById('create_flights_1_is_default').value = '0';
                    document.getElementById('create_flights_default_0').checked = true;
                    document.getElementById('create_flights_0_is_default').value = '1';
                });
            }
            var addBtn = document.getElementById('create-flight2-add-btn');
            if (addBtn && flight2Block && addWrap) {
                addBtn.addEventListener('click', function() {
                    flight2Block.style.display = 'block';
                    addWrap.style.display = 'none';
                    showCreateEdit(1);
                });
            }
            document.querySelectorAll('.create-flight-default-radio').forEach(function(radio) {
                radio.addEventListener('change', function() {
                    document.getElementById('create_flights_0_is_default').value = this.value === '0' ? '1' : '0';
                    document.getElementById('create_flights_1_is_default').value = this.value === '1' ? '1' : '0';
                });
            });
        });

        // Tour Program: Add/Remove items (create form)
        document.addEventListener('DOMContentLoaded', function() {
            const oldProgramItems = @json(old('tours_program', []));
            let programItemIndex = 0;

            function appendProgramItem(title, desc) {
                const container = document.getElementById('programItemsListCreate');
                const newItem = document.createElement('div');
                newItem.className = 'program-item card mb-3';
                newItem.setAttribute('data-index', programItemIndex);
                newItem.innerHTML = `
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6 class="mb-0">Item ${programItemIndex + 1}</h6>
                            <button type="button" class="btn btn-danger btn-sm remove-program-item-create">
                                <i class="bx bx-trash"></i> Remove
                            </button>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input type="text"
                                   name="tours_program[${programItemIndex}][title]"
                                   class="form-control"
                                   placeholder="Ex: 08:00 - Départ de l'hôtel"
                                   value="${String(title || '').replace(/"/g, '&quot;')}">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Description</label>
                            <textarea name="tours_program[${programItemIndex}][desc]"
                                      class="form-control"
                                      rows="3"
                                      placeholder="Description détaillée de cette étape du programme">${String(desc || '')}</textarea>
                        </div>
                    </div>
                `;
                container.appendChild(newItem);
                programItemIndex++;
                const emptyAlert = document.getElementById('emptyProgramAlert');
                if (emptyAlert) {
                    emptyAlert.style.display = 'none';
                }
            }

            if (Array.isArray(oldProgramItems) && oldProgramItems.length > 0) {
                oldProgramItems.forEach(function(item) {
                    if (!item) return;
                    appendProgramItem(item.title || '', item.desc || '');
                });
            }
            
            // Add new program item
            document.getElementById('addProgramItemCreate').addEventListener('click', function() {
                appendProgramItem('', '');
                
                // Hide empty message
                const emptyAlert = document.getElementById('emptyProgramAlert');
                if (emptyAlert) {
                    emptyAlert.style.display = 'none';
                }
            });
            
            // Remove program item (delegated event)
            document.getElementById('programItemsListCreate').addEventListener('click', function(e) {
                if (e.target.closest('.remove-program-item-create')) {
                    const item = e.target.closest('.program-item');
                    if (confirm('Supprimer cet item du programme ?')) {
                        item.remove();
                        
                        // Update item numbers
                        const items = document.querySelectorAll('#programItemsListCreate .program-item');
                        items.forEach(function(item, index) {
                            const title = item.querySelector('h6');
                            if (title) {
                                title.textContent = 'Item ' + (index + 1);
                            }
                        });
                        
                        // Show empty message if no items left
                        if (items.length === 0) {
                            const emptyAlert = document.getElementById('emptyProgramAlert');
                            if (emptyAlert) {
                                emptyAlert.style.display = 'block';
                            }
                        }
                    }
                }
            });
        });

        // Hero Gallery (5 images) management for create form
        var heroGalleryCurrentIndex = null;
        var heroGalleryUploadUrl = "{{ route('admin.circuits.voyages.hero-image.upload', ['id' => 0]) }}"; // Will be updated after tour creation

        function updateHeroGalleryHidden() {
            var ids = [];
            document.querySelectorAll('.hero-gallery-id-input').forEach(function(input) {
                var val = input.value.trim();
                if (val) ids.push(val);
            });
            var hiddenInput = document.getElementById('hero_gallery_ids');
            if (hiddenInput) hiddenInput.value = ids.join(',');
        }

        function setHeroGalleryPreview(index, url, id) {
            var item = document.querySelector('.hero-gallery-item[data-index="' + index + '"]');
            if (!item) return;
            var input = item.querySelector('.hero-gallery-id-input');
            var preview = item.querySelector('.hero-gallery-preview');
            var previewWrap = item.querySelector('.hero-gallery-preview-wrap');
            var placeholder = item.querySelector('.hero-gallery-placeholder');
            var removeBtn = item.querySelector('.hero-gallery-remove-btn');
            if (input) input.value = id || '';
            if (preview && url) preview.src = url;
            if (previewWrap) previewWrap.style.display = (url ? 'block' : 'none');
            if (placeholder) placeholder.style.display = (url ? 'none' : 'flex');
            if (removeBtn) removeBtn.disabled = !id;
            updateHeroGalleryHidden();
        }

        // Upload buttons (simple file input for create form)
        document.querySelectorAll('.hero-gallery-upload-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var index = this.getAttribute('data-index');
                heroGalleryCurrentIndex = index;
                var fileInput = document.createElement('input');
                fileInput.type = 'file';
                fileInput.accept = 'image/jpeg,image/png,image/webp';
                fileInput.addEventListener('change', function() {
                    if (!this.files || !this.files[0]) return;
                    alert('Pour le formulaire de création, veuillez d\'abord créer le tour, puis éditer pour uploader les images via la médiathèque WordPress.');
                    this.value = '';
                });
                fileInput.click();
            });
        });

        // Choose buttons (manual ID input for create form)
        document.querySelectorAll('.hero-gallery-choose-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var index = this.getAttribute('data-index');
                var id = prompt('Entrez l\'ID de l\'image WordPress (ex: 14434):');
                if (id && !isNaN(id) && parseInt(id) > 0) {
                    // Try to get image URL
                    fetch('{{ url("admin/wp-media/get") }}/' + id, {
                        credentials: 'same-origin',
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    }).then(function(res) {
                        return res.json();
                    }).then(function(data) {
                        if (data && data.url) {
                            setHeroGalleryPreview(index, data.url, id);
                        } else {
                            // Fallback: set ID anyway, URL will be loaded on edit
                            setHeroGalleryPreview(index, '', id);
                        }
                    }).catch(function() {
                        // Fallback: set ID anyway
                        setHeroGalleryPreview(index, '', id);
                    });
                }
            });
        });

        // Remove buttons
        document.querySelectorAll('.hero-gallery-remove-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var index = this.getAttribute('data-index');
                if (confirm('Retirer cette image de la galerie hero ?')) {
                    setHeroGalleryPreview(index, '', '');
                }
            });
        });

        // Initialize hidden input
        updateHeroGalleryHidden();

        // Éviter "An invalid form control with name='...' is not focusable" : retirer required des champs dans modals/onglets cachés avant submit
        (function() {
            function stripRequiredFromHiddenInForm(form) {
                var list = [];
                if (!form) return list;
                form.querySelectorAll('input[required], select[required], textarea[required]').forEach(function(el) {
                    var inHiddenModal = el.closest('.modal') && !el.closest('.modal').classList.contains('show');
                    var inHiddenOffcanvas = el.closest('.offcanvas') && !el.closest('.offcanvas').classList.contains('show');
                    var inInactiveTab = el.closest('.tab-pane') && !el.closest('.tab-pane').classList.contains('active');
                    var inHidden = el.closest('[hidden]');
                    if (inHiddenModal || inHiddenOffcanvas || inInactiveTab || inHidden) {
                        list.push(el);
                        el.removeAttribute('required');
                        el.setAttribute('data-required-restore', '1');
                    }
                });
                return list;
            }
            function restoreRequired(list) {
                list.forEach(function(el) {
                    if (el.getAttribute('data-required-restore') === '1') {
                        el.setAttribute('required', 'required');
                        el.removeAttribute('data-required-restore');
                    }
                });
            }
            document.addEventListener('DOMContentLoaded', function() {
                var form = document.getElementById('create-voyage-form');
                var submitBtn = form && form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.addEventListener('click', function(e) {
                        var toRestore = stripRequiredFromHiddenInForm(form);
                        if (toRestore.length > 0) {
                            e.preventDefault();
                            e.stopImmediatePropagation();
                            form.requestSubmit();
                            setTimeout(function() { restoreRequired(toRestore); }, 100);
                            return false;
                        }
                    }, true);
                }
            });
        })();
    </script>
@endpush
