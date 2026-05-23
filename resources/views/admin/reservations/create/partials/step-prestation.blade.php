@php
    $selectedTourId = (int) ($preselectedTourId ?? old('tour_id'));
    $wpTitles = $wpTitles ?? collect();
@endphp

<section class="reservation-create__panel is-active" data-create-step="1" data-reservation-step="1">
        <div class="reservation-create__card">
            <div class="reservation-create__section-head">
                <div>
                    <p class="reservation-create__eyebrow">Étape 1</p>
                    <h3 class="reservation-create__section-title">Sélection de la prestation</h3>
                    <p class="reservation-create__section-subtitle">Choisissez le voyage et le départ avant de composer le dossier.</p>
                </div>
                <span class="reservation-create__pill">Réservation</span>
        </div>

        @if(isset($travelDateIncoherent) && $travelDateIncoherent)
            <div class="reservation-create__notice reservation-create__notice--warn">
                La date de départ fournie ne correspond pas au voyage sélectionné. Elle a été ignorée.
            </div>
        @endif

        <div class="reservation-create__grid reservation-create__grid--two">
            <div class="reservation-create__field reservation-create__field--full">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="reservation-create__label mb-0" for="select-tour-id">Voyage / circuit <span class="required-star">*</span></label>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btn-toggle-tour">Modifier</button>
                </div>
                <select class="reservation-create__input" required id="select-tour-id" disabled>
                    <option value="">Sélectionner un voyage...</option>
                    @foreach($voyages as $voyage)
                        @php
                            $label = $voyage->wp_post_id && $wpTitles->has($voyage->wp_post_id)
                                ? ($wpTitles->get($voyage->wp_post_id)->post_title ?? $voyage->name ?? $voyage->slug)
                                : ($voyage->name ?? $voyage->slug ?? 'Voyage #' . $voyage->id);
                        @endphp
                        <option value="{{ $voyage->id }}" data-price-from="{{ (float) ($voyage->price_from ?? 0) }}" {{ $selectedTourId === (int) $voyage->id ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                <input type="hidden" name="tour_id" id="tour_id_hidden" value="{{ old('tour_id', $selectedTourId) }}">
            </div>
        </div>
    </div>

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

    <p class="reservation-create__helper" style="margin-top:1rem;">
        La répartition des chambres sera effectuée à l'étape 3 après la saisie des voyageurs.
    </p>

    <div class="reservation-create__step-errors" id="step-1-errors" hidden></div>
    <div class="reservation-create__actions">
        <span></span>
        <button type="button" class="reservation-create__button reservation-create__button--primary" data-create-next data-step-next="2">
            <span>Continuer</span><i class="bx bx-right-arrow-alt" aria-hidden="true"></i>
        </button>
    </div>
</section>

