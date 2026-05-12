@php $clientMode = old('client_mode', 'new'); @endphp

<section class="reservation-create__panel" data-create-step="2" hidden>
    <div class="reservation-create__card">
        <div class="reservation-create__section-head">
            <div>
                <p class="reservation-create__eyebrow">Étape 2</p>
                <h3 class="reservation-create__section-title">Informations client</h3>
                <p class="reservation-create__section-subtitle">Créez ou rattachez le client principal du dossier. Le prénom, le nom et le téléphone restent obligatoires.</p>
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
                <div class="reservation-create__field reservation-create__field--full">
                    <label class="reservation-create__label" for="client_address">Adresse</label>
                    <textarea name="client_address" id="client_address" class="reservation-create__input reservation-create__input--textarea" rows="3">{{ old('client_address') }}</textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="reservation-create__actions">
        <button type="button" class="reservation-create__button reservation-create__button--secondary" data-create-prev>Retour</button>
        <button type="button" class="reservation-create__button reservation-create__button--primary" data-create-next>Continuer</button>
    </div>
</section>
