@php
    $clientMode = old('client_mode', 'new');
    $oldPassengers = collect(old('passengers', []));
@endphp

<section class="reservation-create__panel" data-create-step="2" hidden>
    <div class="reservation-create__card">
        <div class="reservation-create__section-head">
            <div>
                <p class="reservation-create__eyebrow">Étape 2</p>
                <h3 class="reservation-create__section-title">Voyageurs</h3>
                <p class="reservation-create__section-subtitle">Le client principal est le voyageur principal. Ajoutez les accompagnants avant la repartition des chambres.</p>
            </div>
            <div class="reservation-create__metric">
                <span>Total</span>
                <strong id="create-travelers-badge">1</strong>
            </div>
        </div>

        <div class="reservation-create__choice-row">
            <label class="reservation-create__choice">
                <input type="radio" name="client_mode" id="client_mode_new" value="new" {{ $clientMode === 'new' ? 'checked' : '' }}>
                <span>Nouveau client</span>
            </label>
            <label class="reservation-create__choice">
                <input type="radio" name="client_mode" id="client_mode_existing" value="existing" {{ $clientMode === 'existing' ? 'checked' : '' }}>
                <span>Client existant</span>
            </label>
        </div>

        <div id="existing-client-block" class="{{ $clientMode === 'existing' ? '' : 'd-none' }}">
            <div class="reservation-create__field">
                <label class="reservation-create__label" for="client_external_id">Recherche client <span>*</span></label>
                <select name="client_external_id" id="client_external_id" class="reservation-create__input">
                    <option value="">Choisir un client…</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}" {{ old('client_external_id') == $client->id ? 'selected' : '' }}>
                            [{{ $client->client_code }}] {{ $client->full_name }}
                            @if($client->phone) — {{ $client->phone }} @endif
                            @if($client->email) — {{ $client->email }} @endif
                        </option>
                    @endforeach
                </select>
                <p class="reservation-create__helper">Recherche par nom, téléphone, email ou CIN depuis la liste existante.</p>
            </div>
        </div>

        <div id="new-client-block" class="{{ $clientMode === 'new' ? '' : 'd-none' }}">
            <div class="reservation-create__grid reservation-create__grid--two">
                <div class="reservation-create__field">
                    <label class="reservation-create__label" for="client_first_name">Prénom <span>*</span></label>
                    <input type="text" name="client_first_name" id="client_first_name" class="reservation-create__input" value="{{ old('client_first_name') }}" autocomplete="given-name">
                </div>
                <div class="reservation-create__field">
                    <label class="reservation-create__label" for="client_last_name">Nom <span>*</span></label>
                    <input type="text" name="client_last_name" id="client_last_name" class="reservation-create__input" value="{{ old('client_last_name') }}" autocomplete="family-name">
                </div>
                <div class="reservation-create__field">
                    <label class="reservation-create__label" for="client_phone">Téléphone <span>*</span></label>
                    <input type="text" name="client_phone" id="client_phone" class="reservation-create__input" value="{{ old('client_phone') }}" autocomplete="tel">
                </div>
                <div class="reservation-create__field">
                    <label class="reservation-create__label" for="client_email">Email</label>
                    <input type="email" name="client_email" id="client_email" class="reservation-create__input" value="{{ old('client_email') }}" autocomplete="email">
                </div>
                <div class="reservation-create__field">
                    <label class="reservation-create__label" for="client_document_type">Type de document</label>
                    <select name="client_document_type" id="client_document_type" class="reservation-create__input">
                        <option value="">Sélectionner…</option>
                        <option value="cin" {{ old('client_document_type') === 'cin' ? 'selected' : '' }}>CIN</option>
                        <option value="passport" {{ old('client_document_type') === 'passport' ? 'selected' : '' }}>Passeport</option>
                    </select>
                </div>
                <div class="reservation-create__field">
                    <label class="reservation-create__label" for="client_document_number">Numéro document</label>
                    <input type="text" name="client_document_number" id="client_document_number" class="reservation-create__input" value="{{ old('client_document_number') }}">
                </div>
                <div class="reservation-create__field">
                    <label class="reservation-create__label" for="client_nationality">Nationalité</label>
                    <input type="text" name="client_nationality" id="client_nationality" class="reservation-create__input" value="{{ old('client_nationality') }}">
                </div>
                <div class="reservation-create__field">
                    <label class="reservation-create__label" for="client_birth_date">Date naissance</label>
                    <input type="date" name="client_birth_date" id="client_birth_date" class="reservation-create__input" value="{{ old('client_birth_date') }}">
                </div>
                <div class="reservation-create__field">
                    <label class="reservation-create__label" for="client_traveler_type">Type voyageur</label>
                    <select name="client_traveler_type" id="client_traveler_type" class="reservation-create__input">
                        <option value="adult" {{ old('client_traveler_type', 'adult') === 'adult' ? 'selected' : '' }}>Adulte</option>
                        <option value="child" {{ old('client_traveler_type') === 'child' ? 'selected' : '' }}>Enfant</option>
                        <option value="infant" {{ old('client_traveler_type') === 'infant' ? 'selected' : '' }}>Bebe</option>
                    </select>
                </div>
                <div class="reservation-create__field">
                    <label class="reservation-create__label" for="client_gender">Sexe</label>
                    <select name="client_gender" id="client_gender" class="reservation-create__input">
                        <option value="">Selectionner...</option>
                        <option value="male" {{ old('client_gender') === 'male' ? 'selected' : '' }}>Homme</option>
                        <option value="female" {{ old('client_gender') === 'female' ? 'selected' : '' }}>Femme</option>
                    </select>
                </div>
                <div class="reservation-create__field">
                    <label class="reservation-create__label" for="client_consumes_bed">Lit</label>
                    <select name="client_consumes_bed" id="client_consumes_bed" class="reservation-create__input">
                        <option value="1" {{ old('client_consumes_bed', '1') === '1' ? 'selected' : '' }}>Consomme un lit</option>
                        <option value="0" {{ old('client_consumes_bed') === '0' ? 'selected' : '' }}>Sans lit</option>
                    </select>
                </div>
                <div class="reservation-create__field reservation-create__field--full">
                    <label class="reservation-create__label" for="client_address">Adresse</label>
                    <textarea name="client_address" id="client_address" class="reservation-create__input reservation-create__input--textarea" rows="3">{{ old('client_address') }}</textarea>
                </div>
            </div>
        </div>

        <div class="reservation-create__traveler-stats" id="reservation-traveler-stats">
            <span>Total: <strong data-traveler-stat="total">1</strong></span>
            <span>Adultes: <strong data-traveler-stat="adult">1</strong></span>
            <span>Enfants: <strong data-traveler-stat="child">0</strong></span>
            <span>Bebes: <strong data-traveler-stat="infant">0</strong></span>
            <span>Hommes: <strong data-traveler-stat="male">0</strong></span>
            <span>Femmes: <strong data-traveler-stat="female">0</strong></span>
            <span>Sexe non renseigne: <strong data-traveler-stat="gender_unknown">1</strong></span>
        </div>

        <div class="reservation-create__toolbar">
            <p>Les accompagnants seront utilises pour la repartition automatique des chambres.</p>
            <button type="button" class="reservation-create__button reservation-create__button--ghost" id="btn-add-companion">Ajouter un accompagnant</button>
        </div>

        <div id="companions-container" class="reservation-create__companions">
            @foreach($oldPassengers as $i => $passenger)
                <div class="companion-row reservation-create__companion" data-traveler-key="companion_{{ $i }}">
                    <div class="reservation-create__companion-head">
                        <h4 class="reservation-create__companion-title">Accompagnant #{{ $loop->iteration }}</h4>
                        <button type="button" class="btn-remove-companion reservation-create__remove" aria-label="Supprimer">x</button>
                    </div>
                    <div class="reservation-create__grid reservation-create__grid--two">
                        <div class="reservation-create__field"><label class="reservation-create__label">Prenom</label><input type="text" name="passengers[{{ $i }}][first_name]" class="reservation-create__input" value="{{ $passenger['first_name'] ?? '' }}"></div>
                        <div class="reservation-create__field"><label class="reservation-create__label">Nom</label><input type="text" name="passengers[{{ $i }}][last_name]" class="reservation-create__input" value="{{ $passenger['last_name'] ?? '' }}"></div>
                        <div class="reservation-create__field"><label class="reservation-create__label">Type voyageur</label><select name="passengers[{{ $i }}][type]" class="reservation-create__input"><option value="adult" {{ ($passenger['type'] ?? '') === 'adult' ? 'selected' : '' }}>Adulte</option><option value="child" {{ ($passenger['type'] ?? '') === 'child' ? 'selected' : '' }}>Enfant</option><option value="infant" {{ ($passenger['type'] ?? '') === 'infant' ? 'selected' : '' }}>Bebe</option></select></div>
                        <div class="reservation-create__field"><label class="reservation-create__label">Sexe</label><select name="passengers[{{ $i }}][gender]" class="reservation-create__input"><option value="">Selectionner...</option><option value="male" {{ ($passenger['gender'] ?? '') === 'male' ? 'selected' : '' }}>Homme</option><option value="female" {{ ($passenger['gender'] ?? '') === 'female' ? 'selected' : '' }}>Femme</option></select></div>
                        <div class="reservation-create__field"><label class="reservation-create__label">Relation</label><select name="passengers[{{ $i }}][relationship_to_main]" class="reservation-create__input">@foreach(['spouse' => 'Conjoint / conjointe', 'child' => 'Enfant', 'parent' => 'Parent', 'friend' => 'Ami', 'group' => 'Groupe', 'solo' => 'Seul'] as $value => $label)<option value="{{ $value }}" {{ ($passenger['relationship_to_main'] ?? 'group') === $value ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
                        <div class="reservation-create__field"><label class="reservation-create__label">Date naissance</label><input type="date" name="passengers[{{ $i }}][birth_date]" class="reservation-create__input" value="{{ $passenger['birth_date'] ?? '' }}"></div>
                        <div class="reservation-create__field"><label class="reservation-create__label">Type document</label><input type="text" name="passengers[{{ $i }}][document_type]" class="reservation-create__input" value="{{ $passenger['document_type'] ?? '' }}"></div>
                        <div class="reservation-create__field"><label class="reservation-create__label">No document</label><input type="text" name="passengers[{{ $i }}][document_number]" class="reservation-create__input" value="{{ $passenger['document_number'] ?? '' }}"></div>
                        <div class="reservation-create__field"><label class="reservation-create__label">Lit</label><select name="passengers[{{ $i }}][consumes_bed]" class="reservation-create__input"><option value="1" {{ ($passenger['consumes_bed'] ?? '1') === '1' ? 'selected' : '' }}>Consomme un lit</option><option value="0" {{ ($passenger['consumes_bed'] ?? '') === '0' ? 'selected' : '' }}>Sans lit</option></select></div>
                    </div>
                </div>
            @endforeach
        </div>
        <p id="create-no-companions" class="reservation-create__empty-state {{ $oldPassengers->isNotEmpty() ? 'd-none' : '' }}">Aucun accompagnant pour le moment.</p>
    </div>

    <div class="reservation-create__actions">
        <button type="button" class="reservation-create__button reservation-create__button--secondary" data-create-prev>Retour</button>
        <button type="button" class="reservation-create__button reservation-create__button--primary" data-create-next>Continuer</button>
    </div>
</section>
