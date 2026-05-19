@extends('layouts.admin-v6')
@section('title')
    Départs & Disponibilités
@endsection

@push('styles')
    <style>
        .dd-card {
            border: 0;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 8px 20px rgba(16, 24, 40, .08);
            height: 100%;
        }
        .dd-cover {
            height: 150px;
            background: linear-gradient(120deg, #0ea5e9 0%, #0284c7 35%, #0f766e 100%);
            position: relative;
        }
        .dd-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .dd-kpi {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 10px;
            background: #f8fafc;
            min-height: 66px;
        }
        .dd-kpi__label {
            color: #64748b;
            font-size: 12px;
            line-height: 1.2;
            margin-bottom: 4px;
            display: block;
        }
        .dd-kpi__value {
            font-size: 18px;
            font-weight: 700;
            line-height: 1;
        }
    </style>
@endpush

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h4 class="page-title mb-0 font-size-18">Départs, disponibilités et chambres</h4>
                    <p class="text-muted mb-0 small">Gestion centralisée: une seule logique métier pour les dates, les places et les chambres.</p>
                </div>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

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

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-6">
                    <label class="form-label mb-1">Recherche voyage</label>
                    <input type="text" name="q" class="form-control" value="{{ $search }}" placeholder="Nom du voyage ou slug">
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1">Statut départ</label>
                    <select name="status" class="form-select">
                        <option value="">Tous les statuts</option>
                        @foreach($statuses as $st)
                            <option value="{{ $st }}" @selected($status === $st)>{{ \App\Models\Departure::make(['status' => $st])->status_label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">Filtrer</button>
                    <a href="{{ route('admin.circuits.departs-dates') }}" class="btn btn-light w-100">Réinitialiser</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3">
        @forelse($voyages as $voyage)
            @php
                $summary = (array) ($voyage->departure_summary ?? []);
                $metrics = collect($voyage->departure_metrics ?? []);
                $firstDepartureId = (int) ($metrics->first()['id'] ?? 0);
                $cover = $voyage->featured_image_url ?? null;
                $destination = trim((string) ($voyage->destination ?? ''));
                $destination = $destination !== '' ? $destination : 'Destination non renseignée';
                $syncUrl = route('admin.circuits.voyages.sync-departures', $voyage);
                $departuresUrl = route('admin.circuits.voyages.room-availability.departures', $voyage);
                $panelBase = url('/admin/circuits/voyages/'.$voyage->id.'/room-availability/departures');
            @endphp
            <div class="col-xl-4 col-lg-6">
                <div class="card dd-card">
                    <div class="dd-cover">
                        @if($cover)
                            <img src="{{ $cover }}" alt="{{ $voyage->name }}" loading="lazy">
                        @endif
                    </div>
                    <div class="card-body">
                        <h5 class="mb-1">{{ $voyage->name }}</h5>
                        <p class="text-muted small mb-3">{{ $destination }}</p>

                        <div class="row g-2 mb-3">
                            <div class="col-4">
                                <div class="dd-kpi">
                                    <span class="dd-kpi__label">Départs</span>
                                    <div class="dd-kpi__value">{{ (int) ($summary['total_departures'] ?? 0) }}</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="dd-kpi">
                                    <span class="dd-kpi__label">Actifs</span>
                                    <div class="dd-kpi__value text-success">{{ (int) ($summary['active_departures'] ?? 0) }}</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="dd-kpi">
                                    <span class="dd-kpi__label">Disponibles</span>
                                    <div class="dd-kpi__value text-primary">{{ (int) ($summary['available_capacity'] ?? 0) }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button
                                type="button"
                                class="btn btn-primary btn-sm flex-grow-1"
                                data-ra-open-modal="1"
                                data-ra-voyage-id="{{ $voyage->id }}"
                                data-ra-departures-url="{{ $departuresUrl }}"
                                data-ra-sync-url="{{ $syncUrl }}"
                                data-ra-panel-base="{{ $panelBase }}"
                                data-ra-select-departure="{{ $firstDepartureId > 0 ? $firstDepartureId : '' }}"
                                data-bs-toggle="modal"
                                data-bs-target="#voyageRoomAvailabilityModal"
                            >
                                Gérer les départs
                            </button>
                            <a href="{{ route('admin.circuits.voyages.edit', $voyage->wp_post_id ?? $voyage->id) }}#availability" class="btn btn-outline-secondary btn-sm">
                                Availability
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center text-muted py-5">
                        Aucun voyage avec départ trouvé.
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    <div class="mt-3 d-flex justify-content-center">
        {{ $voyages->links() }}
    </div>

    <div
        class="modal fade"
        id="voyageRoomAvailabilityModal"
        tabindex="-1"
        aria-labelledby="voyageRoomAvailabilityModalLabel"
        aria-hidden="true"
        data-laravel-voyage-id=""
        data-wp-tour-post-id=""
        data-server-wp-travel-dates-count="0"
        data-server-laravel-departures-count="0"
        data-departures-url=""
        data-sync-departures-url=""
        data-panel-base=""
    >
        <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <div>
                        <h5 class="modal-title mb-0" id="voyageRoomAvailabilityModalLabel">
                            <i class="bx bx-bed me-1"></i> Gestion des départs et disponibilités
                        </h5>
                        <p class="text-muted small mb-0 mt-1">Section 1: départs · Section 2: paramètres · Section 3: chambres.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div id="ra-sync-hint" class="alert alert-info small mb-3 d-none" role="status">
                        <p class="mb-2"><strong>Information.</strong> Les départs sont synchronisés automatiquement à l'ouverture.</p>
                        <p class="mb-0 d-none" id="ra-sync-hint-resync"></p>
                    </div>

                    <div id="ra-departure-table-wrap" class="mb-3 d-none">
                        <div class="table-responsive border rounded">
                            <table class="table table-sm table-hover mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Départ</th>
                                        <th>Retour</th>
                                        <th>Total</th>
                                        <th>Réservées</th>
                                        <th>Disponibles</th>
                                        <th>Statut</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="ra-departure-table-body"></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label for="ra-departure-select" class="form-label small fw-semibold text-uppercase text-muted">Départ</label>
                            <select id="ra-departure-select" class="form-select" data-placeholder="Chargement…">
                                <option value="">— Sélectionnez une date de départ —</option>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div id="ra-departure-badges" class="d-flex flex-wrap gap-1 w-100 min-h-badges"></div>
                        </div>
                    </div>

                    <div id="ra-modal-alert" class="alert alert-danger d-none py-2 small" role="alert"></div>

                    <div id="ra-departure-content" class="ra-departure-content">
                        <div class="text-center text-muted py-5" id="ra-departure-placeholder">
                            <i class="bx bx-calendar-event display-6 d-block mb-2 opacity-50"></i>
                            Sélectionnez un départ pour afficher les paramètres et les chambres.
                        </div>
                    </div>

                    <div id="ra-departure-loading" class="d-none text-center py-5">
                        <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Chargement…</span></div>
                        <p class="text-muted small mt-2 mb-0">Chargement du stock…</p>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
    <script src="{{ URL::asset('js/voyage-room-availability-modal.js') }}"></script>
@endpush
