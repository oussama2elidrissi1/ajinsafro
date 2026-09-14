@php
    $clientMode = old('client_mode', 'new');
    $oldPassengers = collect(old('passengers', []));
    $fastCounters = [
        ['key' => 'adult', 'label' => 'Adultes', 'hint' => '12 ans et plus', 'value' => 1, 'min' => 1],
        ['key' => 'child', 'label' => 'Enfants', 'hint' => '2 à 11 ans', 'value' => 0, 'min' => 0],
        ['key' => 'infant', 'label' => 'Bébés', 'hint' => 'moins de 2 ans', 'value' => 0, 'min' => 0],
    ];
@endphp

<section class="reservation-create__panel is-active" data-create-step="1" data-reservation-step="1">
    {{-- Hidden inputs pour l'offre (déjà verrouillée) --}}
    <input type="hidden" name="tour_id" id="tour_id_hidden" value="{{ old('tour_id', $preselectedTourId ?? '') }}">
    <input type="hidden" name="departure_id" id="input-departure-id" value="{{ old('departure_id', $selectedDepartureId ?? '') }}">
    <input type="hidden" name="travel_date_id" id="input-travel-date-id" value="{{ old('travel_date_id', $travelDateId ?? '') }}">
    <input type="hidden" name="base_price" id="reservation-base-price" value="{{ old('base_price', $selectedUnitPrice !== null ? number_format((float) $selectedUnitPrice, 2, '.', '') : '') }}">

    {{-- Composition du groupe --}}
    <div class="reservation-fast-card">
        <div class="reservation-fast-card__head">
            <div class="reservation-fast-card__heading">
                <h2 class="reservation-fast-card__title">Composition du groupe</h2>
            </div>
            <span class="reservation-fast-card__badge">Total <strong id="fast-travelers-total">1</strong> voyageur(s)</span>
        </div>

        <div class="reservation-fast-traveler-counters">
            @foreach ($fastCounters as $counter)
                <div class="reservation-fast-counter" data-counter-card="{{ $counter['key'] }}">
                    <div class="reservation-fast-counter__meta">
                        <label class="reservation-fast-counter__label" for="fast-counter-{{ $counter['key'] }}">{{ $counter['label'] }}</label>
                        <p class="reservation-fast-counter__hint">{{ $counter['hint'] }}</p>
                    </div>
                    <div class="reservation-fast-counter__control">
                        <button type="button" class="reservation-fast-counter__btn" data-counter="{{ $counter['key'] }}" data-dir="-1" aria-label="Retirer un {{ \Illuminate\Support\Str::lower($counter['label']) }}">−</button>
                        <input
                            type="number"
                            id="fast-counter-{{ $counter['key'] }}"
                            class="reservation-fast-counter__input"
                            value="{{ $counter['value'] }}"
                            min="{{ $counter['min'] }}"
                            aria-label="Nombre — {{ $counter['label'] }}"
                            readonly
                        >
                        <button type="button" class="reservation-fast-counter__btn" data-counter="{{ $counter['key'] }}" data-dir="1" aria-label="Ajouter un {{ \Illuminate\Support\Str::lower($counter['label']) }}">+</button>
                    </div>
                </div>
            @endforeach
        </div>

        <p class="reservation-fast-capacity-alert" id="fast-capacity-alert" hidden></p>
    </div>

    {{-- Client principal --}}
    <div class="reservation-fast-card">
        <div class="reservation-fast-card__head">
            <div class="reservation-fast-card__heading">
                <h2 class="reservation-fast-card__title">Client principal</h2>
                <p class="reservation-fast-card__subtitle">Titulaire du dossier, inclus dans les voyageurs.</p>
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
        </div>

        <div id="existing-client-block" class="{{ $clientMode === 'existing' ? '' : 'd-none' }}">
            <input type="hidden" name="client_external_id" id="client_external_id" value="{{ old('client_external_id') }}">
            <div class="reservation-fast-search">
                <input
                    type="search"
                    id="reservation-client-search"
                    class="reservation-create__input"
                    placeholder="Nom, téléphone ou email du client…"
                    autocomplete="off"
                    aria-label="Rechercher un client"
                    value=""
                >
                <button type="button" class="reservation-fast-search__submit" id="reservation-client-search-submit">Rechercher</button>
            </div>
            <div id="client-search-results" class="reservation-create__search-results" hidden></div>
            <div id="client-search-selected" class="reservation-create__search-selected {{ old('client_external_id') ? '' : 'd-none' }}">
                <span class="reservation-create__search-selected-label" id="client-search-selected-label">
                    @if(old('client_external_id'))
                        @php($oldClient = $clients->firstWhere('id', old('client_external_id')))
                        {{ $oldClient ? '['.$oldClient->client_code.'] '.$oldClient->full_name : 'Client sélectionné' }}
                    @endif
                </span>
                <button type="button" class="reservation-create__search-selected-clear" id="client-search-clear" aria-label="Effacer">×</button>
            </div>
            <p id="reservation-client-search-empty" class="reservation-create__helper d-none">Aucun client trouvé.</p>
        </div>

        <div id="new-client-block" class="{{ $clientMode === 'new' ? '' : 'd-none' }}">
            <div class="reservation-fast-grid">
                <label class="reservation-create__field">
                    <span class="reservation-create__label">Prénom <span class="required-star">*</span></span>
                    <input type="text" name="client_first_name" id="client_first_name" class="reservation-create__input" placeholder="Ex : Zineb" value="{{ old('client_first_name') }}" autocomplete="given-name">
                </label>
                <label class="reservation-create__field">
                    <span class="reservation-create__label">Nom <span class="required-star">*</span></span>
                    <input type="text" name="client_last_name" id="client_last_name" class="reservation-create__input" placeholder="Ex : Ben Haj Ali" value="{{ old('client_last_name') }}" autocomplete="family-name">
                </label>
                <label class="reservation-create__field reservation-create__field--compact">
                    <span class="reservation-create__label">Sexe <span class="required-star">*</span></span>
                    <select name="client_gender" id="client_gender" class="reservation-create__input">
                        <option value="">Sélectionner…</option>
                        <option value="male" {{ old('client_gender') === 'male' ? 'selected' : '' }}>Homme</option>
                        <option value="female" {{ old('client_gender') === 'female' ? 'selected' : '' }}>Femme</option>
                    </select>
                </label>
            </div>

            <div class="reservation-fast-grid">
                <label class="reservation-create__field">
                    <span class="reservation-create__label">Téléphone <span class="required-star">*</span></span>
                    <input type="tel" name="client_phone" id="client_phone" class="reservation-create__input" placeholder="+212 6 00 00 00 00" value="{{ old('client_phone') }}" autocomplete="tel">
                </label>
                <label class="reservation-create__field">
                    <span class="reservation-create__label">Email</span>
                    <input type="email" name="client_email" id="client_email" class="reservation-create__input" placeholder="client@email.com" value="{{ old('client_email') }}" autocomplete="email">
                </label>
                <label class="reservation-create__field reservation-create__field--compact">
                    <span class="reservation-create__label">Nationalité</span>
                    <input type="text" name="client_nationality" id="client_nationality" class="reservation-create__input" placeholder="Marocaine" value="{{ old('client_nationality') }}">
                </label>
            </div>

            @php($docsOpen = old('client_document_type') || old('client_document_number'))
            <button
                type="button"
                class="reservation-fast-disclosure"
                id="fast-docs-toggle"
                aria-expanded="{{ $docsOpen ? 'true' : 'false' }}"
                aria-controls="fast-docs-panel"
            >
                <span>
                    <span class="reservation-fast-disclosure__title">Pièce d'identité</span>
                    <span class="reservation-fast-disclosure__hint" data-docs-hint>
                        {{ $docsOpen ? 'Requise pour le visa et les vols internationaux.' : 'Facultative à cette étape, exigée avant confirmation.' }}
                    </span>
                </span>
                <span class="reservation-fast-disclosure__toggle" data-docs-label>{{ $docsOpen ? 'Masquer' : 'Renseigner' }}</span>
            </button>

            <div class="reservation-fast-disclosure__panel" id="fast-docs-panel" {{ $docsOpen ? '' : 'hidden' }}>
                <div class="reservation-fast-grid">
                    <label class="reservation-create__field">
                        <span class="reservation-create__label">Type de document</span>
                        <select name="client_document_type" id="client_document_type" class="reservation-create__input">
                            <option value="">Sélectionner…</option>
                            <option value="cin" {{ old('client_document_type') === 'cin' ? 'selected' : '' }}>CIN</option>
                            <option value="passport" {{ old('client_document_type') === 'passport' ? 'selected' : '' }}>Passeport</option>
                        </select>
                    </label>
                    <label class="reservation-create__field">
                        <span class="reservation-create__label">Numéro de document</span>
                        <input type="text" name="client_document_number" id="client_document_number" class="reservation-create__input reservation-create__input--mono" placeholder="Ex : K123456" value="{{ old('client_document_number') }}">
                    </label>
                </div>
            </div>
        </div>
    </div>

    {{-- Accompagnants --}}
    <div class="reservation-fast-card">
        <div class="reservation-fast-card__head">
            <div class="reservation-fast-card__heading">
                <h2 class="reservation-fast-card__title">Accompagnants</h2>
                <p class="reservation-fast-card__subtitle" id="fast-companion-hint">Le client principal voyage seul</p>
            </div>
            <button type="button" class="reservation-fast-card__action" id="btn-add-companion">+ Ajouter</button>
        </div>

        <div id="companions-container" class="reservation-fast-companions">
            @foreach($oldPassengers as $i => $passenger)
                @php($companionKey = 'companion_' . $i)
                <div class="companion-row reservation-fast-companion" data-companion-id="{{ $companionKey }}" data-traveler-key="{{ $companionKey }}">
                    <span class="reservation-fast-companion__num">{{ $loop->iteration + 1 }}</span>
                    <input type="hidden" name="passengers[{{ $companionKey }}][traveler_key]" value="{{ $companionKey }}">
                    <label class="reservation-fast-companion__field">
                        <span class="reservation-create__label">Prénom <span class="required-star">*</span></span>
                        <input type="text" name="passengers[{{ $companionKey }}][first_name]" class="reservation-create__input" placeholder="Prénom" value="{{ $passenger['first_name'] ?? '' }}">
                    </label>
                    <label class="reservation-fast-companion__field">
                        <span class="reservation-create__label">Nom <span class="required-star">*</span></span>
                        <input type="text" name="passengers[{{ $companionKey }}][last_name]" class="reservation-create__input" placeholder="Nom" value="{{ $passenger['last_name'] ?? '' }}">
                    </label>
                    <label class="reservation-fast-companion__field reservation-fast-companion__field--type">
                        <span class="reservation-create__label">Type</span>
                        <select name="passengers[{{ $companionKey }}][type]" class="reservation-create__input" data-companion-type-select="{{ $companionKey }}">
                            <option value="adult" {{ ($passenger['type'] ?? 'adult') === 'adult' ? 'selected' : '' }}>Adulte</option>
                            <option value="child" {{ ($passenger['type'] ?? '') === 'child' ? 'selected' : '' }}>Enfant</option>
                            <option value="infant" {{ ($passenger['type'] ?? '') === 'infant' ? 'selected' : '' }}>Bébé</option>
                        </select>
                    </label>
                    <label class="reservation-fast-companion__field reservation-fast-companion__field--compact">
                        <span class="reservation-create__label">Sexe</span>
                        <select name="passengers[{{ $companionKey }}][gender]" class="reservation-create__input">
                            <option value="">Sélectionner…</option>
                            <option value="male" {{ ($passenger['gender'] ?? '') === 'male' ? 'selected' : '' }}>Homme</option>
                            <option value="female" {{ ($passenger['gender'] ?? '') === 'female' ? 'selected' : '' }}>Femme</option>
                        </select>
                    </label>
                    <label class="reservation-fast-companion__field reservation-fast-companion__field--compact">
                        <span class="reservation-create__label">Naissance</span>
                        <input type="date" name="passengers[{{ $companionKey }}][birth_date]" class="reservation-create__input" value="{{ $passenger['birth_date'] ?? '' }}">
                    </label>
                    <button type="button" class="btn-remove-companion reservation-fast-companion__remove" aria-label="Supprimer l'accompagnant">−</button>
                </div>
            @endforeach
        </div>

        <div id="create-no-companions" class="reservation-fast-empty {{ $oldPassengers->isNotEmpty() ? 'd-none' : '' }}">
            <span class="reservation-fast-empty__icon" aria-hidden="true">+</span>
            <div class="reservation-fast-empty__body">
                <div class="reservation-fast-empty__title">Aucun accompagnant saisi</div>
                <p class="reservation-fast-empty__text" id="fast-companion-empty">Augmentez le nombre de voyageurs pour ajouter des accompagnants.</p>
            </div>
        </div>
    </div>

    <div class="reservation-create__step-errors" id="step-1-errors" hidden></div>
    <div class="reservation-create__actions">
        <span></span>
        <button type="button" class="reservation-create__button reservation-create__button--primary" data-create-next data-step-next="2">
            <span>Continuer</span><i class="bx bx-right-arrow-alt" aria-hidden="true"></i>
        </button>
    </div>
</section>
