@php($agentReservationMode = (bool) request()->attributes->get('agent_reservation_mode', false))

<section class="reservation-create__panel" data-create-step="2" data-reservation-step="2" hidden>
    {{-- Répartition des chambres --}}
    <div class="reservation-fast-card">
        <div class="reservation-fast-card__head">
            <div class="reservation-fast-card__heading">
                <h2 class="reservation-fast-card__title">Répartition des chambres</h2>
                <p class="reservation-fast-card__subtitle" id="rooming-hint">Ajoutez une chambre, puis choisissez le type et les voyageurs.</p>
            </div>
            <span class="reservation-create__pill reservation-fast-room-status" id="rooming-status-pill">ROOMING EN ATTENTE</span>
        </div>

        <div class="reservation-fast-room-actions">
            <button type="button" class="reservation-fast-room-actions__add" id="btn-add-room-allocation">+ Chambre</button>
            <button type="button" class="reservation-fast-room-actions__reset" id="btn-reset-rooming">Réinitialiser</button>
            @if (! $agentReservationMode && auth()->user()?->can('circuits.voyages.view'))
                <button type="button" class="reservation-fast-room-actions__reset" id="btn-manage-departure-rooms" aria-haspopup="dialog" aria-controls="departure-rooms-modal">Gérer les chambres</button>
            @endif
            <span class="reservation-fast-room-actions__inventory">
                Inventaire départ :
                <span id="rooming-available-rooms" class="reservation-create__available-rooms">—</span>
            </span>
        </div>

        <div id="rooming-unassigned-travelers" class="reservation-fast-room-pool"></div>

        <div class="reservation-fast-room-board" id="rooming-allocation-board"></div>

        <div class="reservation-create__alert reservation-create__alert--warn d-none" id="rooming-alerts"></div>
    </div>

    {{-- Extras & options --}}
    <div class="reservation-fast-card">
        <div class="reservation-fast-card__head">
            <div class="reservation-fast-card__heading">
                <h2 class="reservation-fast-card__title">Extras &amp; options</h2>
                <p class="reservation-fast-card__subtitle">Prix par voyageur sélectionné. Le total se met à jour en direct.</p>
            </div>
            <span class="reservation-fast-card__badge">Sélectionnés <strong id="fast-extras-count">0</strong></span>
        </div>

        <div id="reservation-create-extras-container" class="reservation-fast-extras"></div>
        <div id="reservation-create-extras-empty" class="reservation-fast-empty">
            <span class="reservation-fast-empty__icon" aria-hidden="true">+</span>
            <div class="reservation-fast-empty__body">
                <div class="reservation-fast-empty__title">Aucun extra configuré</div>
                <p class="reservation-fast-empty__text">Ce voyage ne contient pas encore d'extras actifs.</p>
            </div>
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
            'hotelsRoomsUrl' => $agentReservationMode ? route('agent.reservations.hotels-rooms') : route('admin.reservations.hotels-rooms'),
            'voyageDeparturesUrl' => $agentReservationMode ? route('agent.reservations.voyage-departures') : route('admin.reservations.voyage-departures'),
            'departureHotelsRoomsUrl' => $agentReservationMode ? route('agent.reservations.departure-hotels-rooms') : route('admin.reservations.departure-hotels-rooms'),
            'selectedTravelDate' => $selectedTravelDate ?? null,
            'selectedDepartureId' => $selectedDepartureId ?? null,
            'selectedUnitPrice' => $selectedUnitPrice ?? null,
            'compactAvailabilityOnly' => true,
        ])
    </div>
</section>
