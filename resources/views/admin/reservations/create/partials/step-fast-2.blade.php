<section class="reservation-create__panel" data-create-step="2" data-reservation-step="2" hidden>
    <div class="reservation-fast-card">
        <div class="reservation-fast-card__head">
            <p class="reservation-create__eyebrow">Étape 2</p>
            <h3 class="reservation-fast-card__title">Chambres</h3>
            <span class="reservation-create__pill" id="rooming-status-pill">Rooming pending</span>
        </div>

        <div class="reservation-create__rooming-grid">
            <div class="reservation-create__rooming-panel">
                <p class="reservation-create__mini-title">Résumé voyageurs</p>
                <div class="reservation-create__traveler-stats reservation-create__traveler-stats--stacked">
                    <span>Total: <strong data-rooming-stat="total">1</strong></span>
                    <span>Adultes: <strong data-rooming-stat="adult">1</strong></span>
                    <span>Enfants: <strong data-rooming-stat="child">0</strong></span>
                    <span>Bébés: <strong data-rooming-stat="infant">0</strong></span>
                    <span>Hommes: <strong data-rooming-stat="male">0</strong></span>
                    <span>Femmes: <strong data-rooming-stat="female">0</strong></span>
                    <span>Lits à couvrir: <strong data-rooming-stat="beds">1</strong></span>
                </div>

                <p class="reservation-create__mini-title mt-3">Voyageurs à affecter</p>
                <div id="rooming-unassigned-travelers" class="reservation-create__traveler-pool"></div>
            </div>

            <div class="reservation-create__rooming-panel">
                <p class="reservation-create__mini-title">Chambres disponibles</p>
                <div id="rooming-available-rooms" class="reservation-create__available-rooms">
                    Sélectionnez un départ à l'étape Prestation.
                </div>
            </div>
        </div>

        <div class="reservation-create__rooming-actions">
            <button type="button" class="reservation-create__button reservation-create__button--primary" id="btn-auto-rooming">Répartition auto</button>
            <button type="button" class="reservation-create__button reservation-create__button--ghost" id="btn-add-room-allocation">+ Chambre</button>
            <button type="button" class="reservation-create__button reservation-create__button--secondary" id="btn-reset-rooming">Réinitialiser</button>
        </div>

        <div class="reservation-create__rooming-board" id="rooming-allocation-board"></div>

        <div class="reservation-create__alert reservation-create__alert--warn d-none" id="rooming-alerts"></div>
    </div>

    {{-- Extras --}}
    <div class="reservation-fast-card mt-3">
        <div class="reservation-fast-card__head">
            <h3 class="reservation-fast-card__title">Extras</h3>
        </div>
        <div id="reservation-create-extras-container" class="reservation-create__extras-list"></div>
        <div id="reservation-create-extras-empty" class="reservation-create__placeholder">
            <strong>Aucun extra configuré</strong>
            <p>Ce voyage ne contient pas encore d'extras actifs.</p>
        </div>
    </div>

    <div class="reservation-create__step-errors" id="step-2-errors" hidden></div>
    <div class="reservation-create__actions">
        <button type="button" class="reservation-create__button reservation-create__button--secondary" data-create-prev data-step-back="1">
            <i class="bx bx-left-arrow-alt" aria-hidden="true"></i><span>Retour</span>
        </button>
        <button type="button" class="reservation-create__button reservation-create__button--primary" data-create-next data-step-next="3">
            <span>Continuer</span><i class="bx bx-right-arrow-alt" aria-hidden="true"></i>
        </button>
    </div>

    {{-- Loader caché pour récupérer les données de chambres du départ --}}
    <div style="display:none">
        @include('admin.reservations.partials._hotel_rooms', [
            'tourHotelsWithRooms' => collect(),
            'reservation' => null,
            'hotelsRoomsUrl' => route('admin.reservations.hotels-rooms'),
            'voyageDeparturesUrl' => route('admin.reservations.voyage-departures'),
            'departureHotelsRoomsUrl' => route('admin.reservations.departure-hotels-rooms'),
            'selectedTravelDate' => $selectedTravelDate ?? null,
            'selectedDepartureId' => $selectedDepartureId ?? null,
            'selectedUnitPrice' => $selectedUnitPrice ?? null,
            'compactAvailabilityOnly' => true,
        ])
    </div>
</section>
