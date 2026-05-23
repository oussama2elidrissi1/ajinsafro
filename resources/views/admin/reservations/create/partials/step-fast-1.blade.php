@php
    $clientMode = old('client_mode', 'new');
    $oldPassengers = collect(old('passengers', []));
@endphp

<section class="reservation-create__panel is-active" data-create-step="1" data-reservation-step="1">
    {{-- Hidden inputs pour l'offre (déjà verrouillée) --}}
    <input type="hidden" name="tour_id" id="tour_id_hidden" value="{{ old('tour_id', $preselectedTourId ?? '') }}">
    <input type="hidden" name="departure_id" id="input-departure-id" value="{{ old('departure_id', $selectedDepartureId ?? '') }}">
    <input type="hidden" name="travel_date_id" id="input-travel-date-id" value="{{ old('travel_date_id', $travelDateId ?? '') }}">
    <input type="hidden" name="base_price" id="reservation-base-price" value="{{ old('base_price', $selectedUnitPrice !== null ? number_format((float) $selectedUnitPrice, 2, '.', '') : '') }}">

    {{-- Client principal --}}
    <div class="reservation-fast-card">
        <div class="reservation-fast-card__head">
            <p class="reservation-create__eyebrow">Étape 1</p>
            <h3 class="reservation-fast-card__title">Client principal</h3>
        </div>

        <div class="reservation-fast-tabs">
            <label class="reservation-fast-tab {{ $clientMode === 'new' ? 'is-active' : '' }}">
                <input type="radio" name="client_mode" id="client_mode_new" value="new" {{ $clientMode === 'new' ? 'checked' : '' }}>
                <span>Nouveau client</span>
            </label>
            <label class="reservation-fast-tab {{ $clientMode === 'existing' ? 'is-active' : '' }}">
                <input type="radio" name="client_mode" id="client_mode_existing" value="existing" {{ $clientMode === 'existing' ? 'checked' : '' }}>
                <span>Client existant</span>
            </label>
        </div>

        <div id="existing-client-block" class="{{ $clientMode === 'existing' ? '' : 'd-none' }}">
            <div class="reservation-create__field" style="position:relative;z-index:20;">
                <label class="reservation-create__label" for="reservation-client-search">Rechercher un client <span class="required-star">*</span></label>
                <input type="hidden" name="client_external_id" id="client_external_id" value="{{ old('client_external_id') }}">
                <input type="search" id="reservation-client-search" class="reservation-create__input mb-1" placeholder="Nom, téléphone, email ou CIN" autocomplete="off" value="">
                <div id="client-search-results" class="reservation-create__search-results" hidden></div>
                <div id="client-search-selected" class="reservation-create__search-selected {{ old('client_external_id') ? '' : 'd-none' }}">
                    <span class="reservation-create__search-selected-label" id="client-search-selected-label">
                        @if(old('client_external_id'))
                            @php $oldClient = $clients->firstWhere('id', old('client_external_id')); @endphp
                            {{ $oldClient ? '['.$oldClient->client_code.'] '.$oldClient->full_name : 'Client sélectionné' }}
                        @endif
                    </span>
                    <button type="button" class="reservation-create__search-selected-clear" id="client-search-clear" aria-label="Effacer">×</button>
                </div>
                <p id="reservation-client-search-empty" class="reservation-create__helper d-none">Aucun client trouvé.</p>
            </div>
        </div>

        <div id="new-client-block" class="{{ $clientMode === 'new' ? '' : 'd-none' }}">
            <div class="reservation-fast-grid">
                <div class="reservation-create__field">
                    <label class="reservation-create__label" for="client_first_name">Prénom <span class="required-star">*</span></label>
                    <input type="text" name="client_first_name" id="client_first_name" class="reservation-create__input" value="{{ old('client_first_name') }}" autocomplete="given-name">
                </div>
                <div class="reservation-create__field">
                    <label class="reservation-create__label" for="client_last_name">Nom <span class="required-star">*</span></label>
                    <input type="text" name="client_last_name" id="client_last_name" class="reservation-create__input" value="{{ old('client_last_name') }}" autocomplete="family-name">
                </div>
                <div class="reservation-create__field">
                    <label class="reservation-create__label" for="client_phone">Téléphone <span class="required-star">*</span></label>
                    <input type="text" name="client_phone" id="client_phone" class="reservation-create__input" value="{{ old('client_phone') }}" autocomplete="tel">
                </div>
                <div class="reservation-create__field">
                    <label class="reservation-create__label" for="client_email">Email</label>
                    <input type="email" name="client_email" id="client_email" class="reservation-create__input" value="{{ old('client_email') }}" autocomplete="email">
                </div>
                <div class="reservation-create__field">
                    <label class="reservation-create__label" for="client_nationality">Nationalité</label>
                    <input type="text" name="client_nationality" id="client_nationality" class="reservation-create__input" value="{{ old('client_nationality') }}">
                </div>
                <div class="reservation-create__field">
                    <label class="reservation-create__label" for="client_document_type">Type document</label>
                    <select name="client_document_type" id="client_document_type" class="reservation-create__input">
                        <option value="">Sélectionner...</option>
                        <option value="cin" {{ old('client_document_type') === 'cin' ? 'selected' : '' }}>CIN</option>
                        <option value="passport" {{ old('client_document_type') === 'passport' ? 'selected' : '' }}>Passeport</option>
                    </select>
                </div>
                <div class="reservation-create__field">
                    <label class="reservation-create__label" for="client_document_number">Numéro document</label>
                    <input type="text" name="client_document_number" id="client_document_number" class="reservation-create__input" value="{{ old('client_document_number') }}">
                </div>
                <div class="reservation-create__field">
                    <label class="reservation-create__label" for="client_gender">Sexe</label>
                    <select name="client_gender" id="client_gender" class="reservation-create__input">
                        <option value="">Sélectionner...</option>
                        <option value="male" {{ old('client_gender') === 'male' ? 'selected' : '' }}>Homme</option>
                        <option value="female" {{ old('client_gender') === 'female' ? 'selected' : '' }}>Femme</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Nombre de voyageurs --}}
    <div class="reservation-fast-card mt-3">
        <div class="reservation-fast-card__head">
            <h3 class="reservation-fast-card__title">Nombre de voyageurs</h3>
            <span class="reservation-fast-card__badge">Total : <strong id="fast-travelers-total">1</strong></span>
        </div>
        <p class="reservation-create__helper mb-2">Le client principal est inclus automatiquement.</p>
        <div class="reservation-fast-traveler-counters">
            <div class="reservation-fast-counter">
                <label class="reservation-fast-counter__label">Adultes</label>
                <div class="reservation-fast-counter__control">
                    <button type="button" class="reservation-fast-counter__btn" data-counter="adult" data-dir="-1">−</button>
                    <input type="number" id="fast-counter-adult" class="reservation-fast-counter__input" value="1" min="1" readonly>
                    <button type="button" class="reservation-fast-counter__btn" data-counter="adult" data-dir="1">+</button>
                </div>
            </div>
            <div class="reservation-fast-counter">
                <label class="reservation-fast-counter__label">Enfants</label>
                <div class="reservation-fast-counter__control">
                    <button type="button" class="reservation-fast-counter__btn" data-counter="child" data-dir="-1">−</button>
                    <input type="number" id="fast-counter-child" class="reservation-fast-counter__input" value="0" min="0" readonly>
                    <button type="button" class="reservation-fast-counter__btn" data-counter="child" data-dir="1">+</button>
                </div>
            </div>
            <div class="reservation-fast-counter">
                <label class="reservation-fast-counter__label">Bébés</label>
                <div class="reservation-fast-counter__control">
                    <button type="button" class="reservation-fast-counter__btn" data-counter="infant" data-dir="-1">−</button>
                    <input type="number" id="fast-counter-infant" class="reservation-fast-counter__input" value="0" min="0" readonly>
                    <button type="button" class="reservation-fast-counter__btn" data-counter="infant" data-dir="1">+</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Accompagnants --}}
    <div class="reservation-fast-card mt-3">
        <div class="reservation-fast-card__head">
            <h3 class="reservation-fast-card__title">Accompagnants</h3>
            <button type="button" class="reservation-create__button reservation-create__button--ghost" id="btn-add-companion">+ Ajouter</button>
        </div>

        <div id="companions-container" class="reservation-fast-companions">
            @php $companionCounter = 0; @endphp
            @foreach($oldPassengers as $i => $passenger)
                @php $companionKey = 'companion_' . $i; @endphp
                <div class="companion-row reservation-fast-companion" data-companion-id="{{ $companionKey }}" data-traveler-key="{{ $companionKey }}">
                    <div class="reservation-fast-companion__head">
                        <span class="reservation-fast-companion__num">#{{ $loop->iteration }}</span>
                        <button type="button" class="btn-remove-companion reservation-fast-companion__remove" aria-label="Supprimer">×</button>
                    </div>
                    <div class="reservation-fast-grid">
                        <input type="hidden" name="passengers[{{ $companionKey }}][traveler_key]" value="{{ $companionKey }}">
                        <div class="reservation-create__field">
                            <label class="reservation-create__label">Prénom <span class="required-star">*</span></label>
                            <input type="text" name="passengers[{{ $companionKey }}][first_name]" class="reservation-create__input" value="{{ $passenger['first_name'] ?? '' }}">
                        </div>
                        <div class="reservation-create__field">
                            <label class="reservation-create__label">Nom <span class="required-star">*</span></label>
                            <input type="text" name="passengers[{{ $companionKey }}][last_name]" class="reservation-create__input" value="{{ $passenger['last_name'] ?? '' }}">
                        </div>
                        <div class="reservation-create__field">
                            <label class="reservation-create__label">Sexe</label>
                            <select name="passengers[{{ $companionKey }}][gender]" class="reservation-create__input">
                                <option value="">Sélectionner...</option>
                                <option value="male" {{ ($passenger['gender'] ?? '') === 'male' ? 'selected' : '' }}>Homme</option>
                                <option value="female" {{ ($passenger['gender'] ?? '') === 'female' ? 'selected' : '' }}>Femme</option>
                            </select>
                        </div>
                        <div class="reservation-create__field">
                            <label class="reservation-create__label">Date naissance</label>
                            <input type="date" name="passengers[{{ $companionKey }}][birth_date]" class="reservation-create__input" value="{{ $passenger['birth_date'] ?? '' }}">
                        </div>
                        <div class="reservation-create__field">
                            <label class="reservation-create__label">Type voyageur</label>
                            <select name="passengers[{{ $companionKey }}][type]" class="reservation-create__input">
                                <option value="adult" {{ ($passenger['type'] ?? '') === 'adult' ? 'selected' : '' }}>Adulte</option>
                                <option value="child" {{ ($passenger['type'] ?? '') === 'child' ? 'selected' : '' }}>Enfant</option>
                                <option value="infant" {{ ($passenger['type'] ?? '') === 'infant' ? 'selected' : '' }}>Bébé</option>
                            </select>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <p id="create-no-companions" class="reservation-create__empty-state {{ $oldPassengers->isNotEmpty() ? 'd-none' : '' }}">Aucun accompagnant pour le moment.</p>
    </div>

    <div class="reservation-create__step-errors" id="step-1-errors" hidden></div>
    <div class="reservation-create__actions">
        <span></span>
        <button type="button" class="reservation-create__button reservation-create__button--primary" data-create-next data-step-next="2">
            <span>Continuer</span><i class="bx bx-right-arrow-alt" aria-hidden="true"></i>
        </button>
    </div>
</section>
