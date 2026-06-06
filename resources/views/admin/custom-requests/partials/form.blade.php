@php
    $selectedServices = collect(old('services', $customRequest->services?->pluck('service_key')->all() ?? []))->filter()->all();
    $yesNoConfirm = ['yes' => 'Oui', 'no' => 'Non', 'to_confirm' => 'À confirmer'];
@endphp

@push('styles')
<style>
    .dac-form { display: grid; gap: 16px; }
    .dac-head { display:flex; justify-content:space-between; gap:14px; align-items:center; background:#fff; border:1px solid #dde7f0; border-radius:8px; padding:18px; }
    .dac-head h2 { margin:0; font-size:20px; font-weight:600; color:#10233f; }
    .dac-head p { margin:4px 0 0; color:#6b7c8f; }
    .dac-panel { background:#fff; border:1px solid #dde7f0; border-radius:8px; padding:18px; }
    .dac-panel h3 { margin:0 0 14px; font-size:15px; font-weight:600; color:#10233f; }
    .dac-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:14px; }
    .dac-field label { display:block; font-size:12px; font-weight:600; color:#46566a; margin-bottom:6px; }
    .dac-required { color:#dc3545; }
    .dac-field input,.dac-field select,.dac-field textarea { width:100%; border:1px solid #d8e2ec; border-radius:6px; padding:9px 10px; color:#10233f; }
    .dac-field textarea { min-height:92px; resize:vertical; }
    .dac-field.full { grid-column:1 / -1; }
    .dac-services { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:10px; }
    .dac-check { display:flex; align-items:center; gap:8px; padding:10px; border:1px solid #d8e2ec; border-radius:6px; background:#f8fafc; font-weight:500; color:#10233f; }
    .dac-actions { display:flex; justify-content:space-between; flex-wrap:wrap; gap:10px; position:sticky; bottom:0; background:#f6f8fb; border:1px solid #dde7f0; border-radius:8px; padding:12px; }
    .dac-btn { border:0; border-radius:6px; padding:9px 13px; display:inline-flex; align-items:center; gap:7px; font-weight:600; text-decoration:none; }
    .dac-btn-primary { background:#1f6feb; color:#fff; }
    .dac-btn-soft { background:#eef3f8; color:#20324d; border:1px solid #d8e2ec; }
    .dac-btn-warning { background:#f59e0b; color:#fff; }
    @media (max-width:1100px){ .dac-grid{grid-template-columns:repeat(2,1fr)} .dac-services{grid-template-columns:repeat(2,1fr)} }
    @media (max-width:720px){ .dac-head,.dac-actions{display:grid} .dac-grid,.dac-services{grid-template-columns:1fr} }
</style>
@endpush

<form method="POST" action="{{ $formAction }}" enctype="multipart/form-data" class="dac-form">
    @csrf
    @if($formMethod !== 'POST') @method($formMethod) @endif

    <div class="dac-head">
        <div>
            <h2>{{ $customRequest->exists ? $customRequest->request_number : 'Création demande à la carte' }}</h2>
            <p>Les champs marqués d’un <span class="dac-required">*</span> sont obligatoires. Email facultatif.</p>
        </div>
        <a href="{{ route('admin.custom-requests.index') }}" class="dac-btn dac-btn-soft"><i class="bx bx-arrow-back"></i> Annuler</a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger mb-0">Vérifiez les champs du formulaire.</div>
    @endif

    <section class="dac-panel">
        <h3>Informations client</h3>
        <div class="dac-grid">
            <div class="dac-field"><label>Nom complet <span class="dac-required">*</span></label><input name="customer_full_name" value="{{ old('customer_full_name', $customRequest->customer_full_name) }}" required>@error('customer_full_name')<small class="text-danger">{{ $message }}</small>@enderror</div>
            <div class="dac-field"><label>Téléphone <span class="dac-required">*</span></label><input name="customer_phone" value="{{ old('customer_phone', $customRequest->customer_phone) }}" required>@error('customer_phone')<small class="text-danger">{{ $message }}</small>@enderror</div>
            <div class="dac-field"><label>Email</label><input type="email" name="customer_email" value="{{ old('customer_email', $customRequest->customer_email) }}">@error('customer_email')<small class="text-danger">{{ $message }}</small>@enderror</div>
            <div class="dac-field"><label>Ville</label><input name="customer_city" value="{{ old('customer_city', $customRequest->customer_city) }}"></div>
            <div class="dac-field"><label>Pays</label><input name="customer_country" value="{{ old('customer_country', $customRequest->customer_country) }}"></div>
            <div class="dac-field"><label>CIN / Passeport</label><input name="customer_identity" value="{{ old('customer_identity', $customRequest->customer_identity) }}"></div>
            <div class="dac-field"><label>Type client</label><select name="customer_type"><option value="new_customer" @selected(old('customer_type', $customRequest->customer_type) === 'new_customer')>Nouveau client</option><option value="existing_customer" @selected(old('customer_type', $customRequest->customer_type) === 'existing_customer')>Client existant</option></select></div>
            <div class="dac-field full"><label>Notes client</label><textarea name="customer_notes">{{ old('customer_notes', $customRequest->customer_notes) }}</textarea></div>
        </div>
    </section>

    <section class="dac-panel">
        <h3>Informations voyage</h3>
        <div class="dac-grid">
            <div class="dac-field"><label>Destination <span class="dac-required">*</span></label><input name="desired_destination" value="{{ old('desired_destination', $customRequest->desired_destination) }}" required></div>
            <div class="dac-field"><label>Ville de départ <span class="dac-required">*</span></label><input name="departure_city" value="{{ old('departure_city', $customRequest->departure_city) }}" required></div>
            <div class="dac-field"><label>Date départ <span class="dac-required">*</span></label><input type="date" name="desired_departure_date" value="{{ old('desired_departure_date', optional($customRequest->desired_departure_date)->toDateString()) }}" required></div>
            <div class="dac-field"><label>Date retour</label><input type="date" name="desired_return_date" value="{{ old('desired_return_date', optional($customRequest->desired_return_date)->toDateString()) }}"></div>
            <div class="dac-field"><label>Durée</label><input name="desired_duration" value="{{ old('desired_duration', $customRequest->desired_duration) }}"></div>
            <div class="dac-field"><label>Type de voyage <span class="dac-required">*</span></label><select name="travel_type" required><option value="">Choisir</option>@foreach($travelTypeOptions as $key => $label)<option value="{{ $key }}" @selected(old('travel_type', $customRequest->travel_type) === $key)>{{ $label }}</option>@endforeach</select></div>
            <div class="dac-field"><label>Voyageurs <span class="dac-required">*</span></label><input type="number" min="1" name="travelers_count" value="{{ old('travelers_count', $customRequest->travelers_count) }}" required></div>
            <div class="dac-field"><label>Adultes <span class="dac-required">*</span></label><input type="number" min="1" name="adults_count" value="{{ old('adults_count', $customRequest->adults_count) }}" required></div>
            <div class="dac-field"><label>Enfants</label><input type="number" min="0" name="children_count" value="{{ old('children_count', $customRequest->children_count ?? 0) }}"></div>
            <div class="dac-field"><label>Bébés</label><input type="number" min="0" name="babies_count" value="{{ old('babies_count', $customRequest->babies_count ?? 0) }}"></div>
            <div class="dac-field"><label>Budget approx.</label><input type="number" min="0" step="0.01" name="approximate_budget" value="{{ old('approximate_budget', $customRequest->approximate_budget) }}"></div>
            <div class="dac-field"><label>Devise</label><select name="currency">@foreach(['MAD','EUR','USD'] as $currency)<option value="{{ $currency }}" @selected(old('currency', $customRequest->currency) === $currency)>{{ $currency }}</option>@endforeach</select></div>
            <div class="dac-field"><label>Niveau souhaité</label><select name="desired_level"><option value="">Non précisé</option>@foreach(['economy'=>'Économique','standard'=>'Standard','comfort'=>'Confort','premium'=>'Premium','luxury'=>'Luxe'] as $key=>$label)<option value="{{ $key }}" @selected(old('desired_level', $customRequest->desired_level) === $key)>{{ $label }}</option>@endforeach</select></div>
        </div>
    </section>

    <section class="dac-panel">
        <h3>Hébergement</h3>
        <div class="dac-grid">
            <div class="dac-field"><label>Hôtel souhaité</label><input name="desired_hotel" value="{{ old('desired_hotel', $customRequest->desired_hotel) }}"></div>
            <div class="dac-field"><label>Catégorie</label><select name="hotel_category"><option value="">Non précisée</option>@foreach(['3_stars'=>'3 étoiles','4_stars'=>'4 étoiles','5_stars'=>'5 étoiles','riad'=>'Riad','apartment'=>'Appartement','villa'=>'Villa','unspecified'=>'Indifférent'] as $key=>$label)<option value="{{ $key }}" @selected(old('hotel_category', $customRequest->hotel_category) === $key)>{{ $label }}</option>@endforeach</select></div>
            <div class="dac-field"><label>Repas</label><select name="meal_plan"><option value="">Non précisé</option>@foreach(['room_only'=>'Sans repas','breakfast'=>'Petit déjeuner','half_board'=>'Demi-pension','full_board'=>'Pension complète','all_inclusive'=>'All inclusive'] as $key=>$label)<option value="{{ $key }}" @selected(old('meal_plan', $customRequest->meal_plan) === $key)>{{ $label }}</option>@endforeach</select></div>
            <div class="dac-field"><label>Nombre chambres</label><input type="number" min="1" name="rooms_count" value="{{ old('rooms_count', $customRequest->rooms_count) }}"></div>
            <div class="dac-field"><label>Type chambre</label><select name="room_type"><option value="">Non précisé</option>@foreach(['single'=>'Single','double'=>'Double','triple'=>'Triple','quadruple'=>'Quadruple','family'=>'Familiale'] as $key=>$label)<option value="{{ $key }}" @selected(old('room_type', $customRequest->room_type) === $key)>{{ $label }}</option>@endforeach</select></div>
            <div class="dac-field"><label>Chambre séparée</label><select name="separate_room_needed"><option value="0" @selected(!old('separate_room_needed', $customRequest->separate_room_needed))>Non</option><option value="1" @selected(old('separate_room_needed', $customRequest->separate_room_needed))>Oui</option></select></div>
            <div class="dac-field full"><label>Notes hébergement</label><textarea name="accommodation_notes">{{ old('accommodation_notes', $customRequest->accommodation_notes) }}</textarea></div>
        </div>
    </section>

    <section class="dac-panel">
        <h3>Transport</h3>
        <div class="dac-grid">
            <div class="dac-field"><label>Vol inclus</label><select name="flight_included"><option value="">Non précisé</option>@foreach($yesNoConfirm as $key=>$label)<option value="{{ $key }}" @selected(old('flight_included', $customRequest->flight_included) === $key)>{{ $label }}</option>@endforeach</select></div>
            <div class="dac-field"><label>Compagnie préférée</label><input name="preferred_airline" value="{{ old('preferred_airline', $customRequest->preferred_airline) }}"></div>
            <div class="dac-field"><label>Aéroport départ</label><input name="departure_airport" value="{{ old('departure_airport', $customRequest->departure_airport) }}"></div>
            <div class="dac-field"><label>Aéroport arrivée</label><input name="arrival_airport" value="{{ old('arrival_airport', $customRequest->arrival_airport) }}"></div>
            <div class="dac-field"><label>Bagage inclus</label><select name="baggage_included"><option value="">Non précisé</option>@foreach($yesNoConfirm as $key=>$label)<option value="{{ $key }}" @selected(old('baggage_included', $customRequest->baggage_included) === $key)>{{ $label }}</option>@endforeach</select></div>
            <div class="dac-field"><label>Transfert aéroport</label><select name="airport_transfer_included"><option value="">Non précisé</option><option value="yes" @selected(old('airport_transfer_included', $customRequest->airport_transfer_included) === 'yes')>Oui</option><option value="no" @selected(old('airport_transfer_included', $customRequest->airport_transfer_included) === 'no')>Non</option></select></div>
            <div class="dac-field"><label>Transport local</label><select name="local_transport"><option value="">Non précisé</option>@foreach(['none'=>'Aucun','bus'=>'Bus','minibus'=>'Minibus','private_car'=>'Voiture privée','private_driver'=>'Chauffeur privé'] as $key=>$label)<option value="{{ $key }}" @selected(old('local_transport', $customRequest->local_transport) === $key)>{{ $label }}</option>@endforeach</select></div>
            <div class="dac-field full"><label>Notes transport</label><textarea name="transport_notes">{{ old('transport_notes', $customRequest->transport_notes) }}</textarea></div>
        </div>
    </section>

    <section class="dac-panel">
        <h3>Services demandés</h3>
        <div class="dac-services">
            @foreach($serviceOptions as $key => $label)
                <label class="dac-check"><input type="checkbox" name="services[]" value="{{ $key }}" @checked(in_array($key, $selectedServices, true))> {{ $label }}</label>
            @endforeach
        </div>
        <div class="dac-field mt-3"><label>Détails services</label><textarea name="requested_services_details">{{ old('requested_services_details', $customRequest->requested_services_details) }}</textarea></div>
    </section>

    <section class="dac-panel">
        <h3>Paiement, documents et suivi</h3>
        <div class="dac-grid">
            <div class="dac-field"><label>Prix estimé</label><input type="number" min="0" step="0.01" name="estimated_price" value="{{ old('estimated_price', $customRequest->estimated_price) }}"></div>
            <div class="dac-field"><label>Acompte demandé</label><input type="number" min="0" step="0.01" name="requested_deposit" value="{{ old('requested_deposit', $customRequest->requested_deposit) }}"></div>
            <div class="dac-field"><label>Montant payé</label><input type="number" min="0" step="0.01" name="paid_amount" value="{{ old('paid_amount', $customRequest->paid_amount ?? 0) }}"></div>
            <div class="dac-field"><label>Méthode paiement</label><select name="payment_method"><option value="">Non précisée</option>@foreach(['cash'=>'Espèces','transfer'=>'Virement','card'=>'Carte','cheque'=>'Chèque','other'=>'Autre'] as $key=>$label)<option value="{{ $key }}" @selected(old('payment_method', $customRequest->payment_method) === $key)>{{ $label }}</option>@endforeach</select></div>
            <div class="dac-field"><label>Statut paiement</label><select name="payment_status">@foreach($paymentStatusOptions as $key=>$label)<option value="{{ $key }}" @selected(old('payment_status', $customRequest->payment_status) === $key)>{{ $label }}</option>@endforeach</select></div>
            <div class="dac-field"><label>Priorité</label><select name="priority">@foreach($priorityOptions as $key=>$label)<option value="{{ $key }}" @selected(old('priority', $customRequest->priority) === $key)>{{ $label }}</option>@endforeach</select></div>
            <div class="dac-field"><label>Date limite réponse</label><input type="date" name="response_deadline" value="{{ old('response_deadline', optional($customRequest->response_deadline)->toDateString()) }}"></div>
            <div class="dac-field"><label>Documents</label><input type="file" name="documents[]" multiple></div>
            <div class="dac-field full"><label>Notes internes</label><textarea name="internal_notes">{{ old('internal_notes', $customRequest->internal_notes) }}</textarea></div>
        </div>
    </section>

    <div class="dac-actions">
        <a href="{{ route('admin.custom-requests.index') }}" class="dac-btn dac-btn-soft"><i class="bx bx-x"></i> Annuler</a>
        <div class="d-flex gap-2 flex-wrap">
            <button type="submit" name="submit_action" value="draft" class="dac-btn dac-btn-soft"><i class="bx bx-save"></i> Enregistrer brouillon</button>
            <button type="submit" name="submit_action" value="submit" class="dac-btn dac-btn-primary"><i class="bx bx-send"></i> {{ $submitLabel }}</button>
        </div>
    </div>
</form>
