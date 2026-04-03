{{-- Modal : gestion stock chambres par date de départ (voyage Laravel requis). --}}
@php
    $raDeparturesUrl = route('admin.circuits.voyages.room-availability.departures', $voyage);
    $raSyncDeparturesUrl = route('admin.circuits.voyages.sync-departures', $voyage);
    $raPanelBase = url('/admin/circuits/voyages/'.$voyage->id.'/room-availability/departures');
    $raWpTourPostId = (int) ($wpTourPostId ?? 0);
    $raServerWpDates = (int) ($serverWpTravelDatesCount ?? 0);
    $raServerLaravelDeps = (int) ($serverLaravelDeparturesCount ?? 0);
@endphp

<div
    class="modal fade"
    id="voyageRoomAvailabilityModal"
    tabindex="-1"
    aria-labelledby="voyageRoomAvailabilityModalLabel"
    aria-hidden="true"
    data-laravel-voyage-id="{{ $voyage->id }}"
    data-wp-tour-post-id="{{ $raWpTourPostId }}"
    data-server-wp-travel-dates-count="{{ $raServerWpDates }}"
    data-server-laravel-departures-count="{{ $raServerLaravelDeps }}"
    data-departures-url="{{ $raDeparturesUrl }}"
    data-sync-departures-url="{{ $raSyncDeparturesUrl }}"
    data-panel-base="{{ $raPanelBase }}"
>
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <div>
                    <h5 class="modal-title mb-0" id="voyageRoomAvailabilityModalLabel">
                        <i class="bx bx-bed me-1"></i> Gestion du stock chambres
                    </h5>
                    <p class="text-muted small mb-0 mt-1">Choisissez un départ, puis gérez les hôtels et les types de chambre.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div id="ra-sync-hint" class="alert alert-info small mb-3 d-none" role="status">
                    <p class="mb-2"><strong>Information.</strong> Les départs sont synchronisés automatiquement depuis WordPress à l’ouverture de cette fenêtre.</p>
                    <p class="mb-0 d-none" id="ra-sync-hint-resync"></p>
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
                        Sélectionnez un départ pour afficher les hôtels et le stock.
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

<style>
    #voyageRoomAvailabilityModal .min-h-badges { min-height: 38px; align-items: center; }
    #voyageRoomAvailabilityModal .ra-departure-content .accordion-button { font-size: 0.95rem; }
</style>
