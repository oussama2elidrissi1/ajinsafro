@php
    $selectedServices = collect(old('services', $customRequest->services?->pluck('service_key')->all() ?? []))->filter()->all();
    $yesNoConfirm = ['yes' => 'Oui', 'no' => 'Non', 'to_confirm' => 'À confirmer'];
    $field = fn ($name, $default = null) => old($name, $default);
@endphp

<form method="POST" action="{{ $formAction }}" enctype="multipart/form-data" class="aj-agent-dac-form">
    @csrf

    <section class="aj-agent-dac-section">
        <div class="aj-agent-dac-section-head">
            <h2>Informations client</h2>
            <span>Profil et coordonnées</span>
        </div>
        <div class="aj-agent-dac-grid">
            <div class="aj-agent-dac-field">
                <label>Nom complet du client <span>*</span></label>
                <input name="customer_full_name" value="{{ $field('customer_full_name', $customRequest->customer_full_name) }}" required>
                @error('customer_full_name')<small>{{ $message }}</small>@enderror
            </div>
            <div class="aj-agent-dac-field">
                <label>Téléphone <span>*</span></label>
                <input name="customer_phone" value="{{ $field('customer_phone', $customRequest->customer_phone) }}" required>
                @error('customer_phone')<small>{{ $message }}</small>@enderror
            </div>
            <div class="aj-agent-dac-field">
                <label>Email</label>
                <input type="email" name="customer_email" value="{{ $field('customer_email', $customRequest->customer_email) }}">
                @error('customer_email')<small>{{ $message }}</small>@enderror
            </div>
            <div class="aj-agent-dac-field">
                <label>Ville</label>
                <input name="customer_city" value="{{ $field('customer_city', $customRequest->customer_city) }}">
            </div>
            <div class="aj-agent-dac-field">
                <label>Pays</label>
                <input name="customer_country" value="{{ $field('customer_country', $customRequest->customer_country) }}">
            </div>
            <div class="aj-agent-dac-field">
                <label>CIN / Passeport</label>
                <input name="customer_identity" value="{{ $field('customer_identity', $customRequest->customer_identity) }}">
            </div>
            <div class="aj-agent-dac-field">
                <label>Type de client</label>
                <select name="customer_type">
                    <option value="new_customer" @selected($field('customer_type', $customRequest->customer_type) === 'new_customer')>Nouveau client</option>
                    <option value="existing_customer" @selected($field('customer_type', $customRequest->customer_type) === 'existing_customer')>Client existant</option>
                </select>
            </div>
            <div class="aj-agent-dac-field aj-agent-dac-field-wide">
                <label>Remarques client</label>
                <textarea name="customer_notes">{{ $field('customer_notes', $customRequest->customer_notes) }}</textarea>
            </div>
        </div>
    </section>

    <section class="aj-agent-dac-section">
        <div class="aj-agent-dac-section-head">
            <h2>Informations voyage demandé</h2>
            <span>Destination, dates et voyageurs</span>
        </div>
        <div class="aj-agent-dac-grid">
            <div class="aj-agent-dac-field">
                <label>Destination souhaitée <span>*</span></label>
                <input name="desired_destination" value="{{ $field('desired_destination', $customRequest->desired_destination) }}" required>
                @error('desired_destination')<small>{{ $message }}</small>@enderror
            </div>
            <div class="aj-agent-dac-field">
                <label>Ville de départ <span>*</span></label>
                <input name="departure_city" value="{{ $field('departure_city', $customRequest->departure_city) }}" required>
                @error('departure_city')<small>{{ $message }}</small>@enderror
            </div>
            <div class="aj-agent-dac-field">
                <label>Date de départ souhaitée <span>*</span></label>
                <input type="date" name="desired_departure_date" value="{{ $field('desired_departure_date', optional($customRequest->desired_departure_date)->toDateString()) }}" required>
                @error('desired_departure_date')<small>{{ $message }}</small>@enderror
            </div>
            <div class="aj-agent-dac-field">
                <label>Date de retour souhaitée</label>
                <input type="date" name="desired_return_date" value="{{ $field('desired_return_date', optional($customRequest->desired_return_date)->toDateString()) }}">
            </div>
            <div class="aj-agent-dac-field">
                <label>Durée souhaitée</label>
                <input name="desired_duration" value="{{ $field('desired_duration', $customRequest->desired_duration) }}">
            </div>
            <div class="aj-agent-dac-field">
                <label>Type de voyage <span>*</span></label>
                <select name="travel_type" required>
                    <option value="">Choisir</option>
                    @foreach($travelTypeOptions as $key => $label)
                        <option value="{{ $key }}" @selected($field('travel_type', $customRequest->travel_type) === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('travel_type')<small>{{ $message }}</small>@enderror
            </div>
            <div class="aj-agent-dac-field">
                <label>Nombre total de voyageurs <span>*</span></label>
                <input type="number" min="1" name="travelers_count" value="{{ $field('travelers_count', $customRequest->travelers_count) }}" required>
                @error('travelers_count')<small>{{ $message }}</small>@enderror
            </div>
            <div class="aj-agent-dac-field">
                <label>Adultes <span>*</span></label>
                <input type="number" min="1" name="adults_count" value="{{ $field('adults_count', $customRequest->adults_count) }}" required>
                @error('adults_count')<small>{{ $message }}</small>@enderror
            </div>
            <div class="aj-agent-dac-field">
                <label>Enfants</label>
                <input type="number" min="0" name="children_count" value="{{ $field('children_count', $customRequest->children_count ?? 0) }}">
            </div>
            <div class="aj-agent-dac-field">
                <label>Bébés</label>
                <input type="number" min="0" name="babies_count" value="{{ $field('babies_count', $customRequest->babies_count ?? 0) }}">
            </div>
            <div class="aj-agent-dac-field">
                <label>Budget approximatif</label>
                <input type="number" min="0" step="0.01" name="approximate_budget" value="{{ $field('approximate_budget', $customRequest->approximate_budget) }}">
            </div>
            <div class="aj-agent-dac-field">
                <label>Devise</label>
                <select name="currency">
                    @foreach(['MAD','EUR','USD'] as $currency)
                        <option value="{{ $currency }}" @selected($field('currency', $customRequest->currency) === $currency)>{{ $currency }}</option>
                    @endforeach
                </select>
            </div>
            <div class="aj-agent-dac-field">
                <label>Niveau souhaité</label>
                <select name="desired_level">
                    <option value="">Non précisé</option>
                    @foreach(['economy'=>'Économique','standard'=>'Standard','comfort'=>'Confort','premium'=>'Premium','luxury'=>'Luxe'] as $key => $label)
                        <option value="{{ $key }}" @selected($field('desired_level', $customRequest->desired_level) === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </section>

    <section class="aj-agent-dac-section">
        <div class="aj-agent-dac-section-head">
            <h2>Hébergement</h2>
            <span>Hôtel, pension et chambres</span>
        </div>
        <div class="aj-agent-dac-grid">
            <div class="aj-agent-dac-field"><label>Hôtel souhaité</label><input name="desired_hotel" value="{{ $field('desired_hotel', $customRequest->desired_hotel) }}"></div>
            <div class="aj-agent-dac-field">
                <label>Catégorie hôtel</label>
                <select name="hotel_category">
                    <option value="">Non précisée</option>
                    @foreach(['3_stars'=>'3 étoiles','4_stars'=>'4 étoiles','5_stars'=>'5 étoiles','riad'=>'Riad','apartment'=>'Appartement','villa'=>'Villa','unspecified'=>'Indifférent'] as $key => $label)
                        <option value="{{ $key }}" @selected($field('hotel_category', $customRequest->hotel_category) === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="aj-agent-dac-field">
                <label>Type de pension</label>
                <select name="meal_plan">
                    <option value="">Non précisé</option>
                    @foreach(['room_only'=>'Sans repas','breakfast'=>'Petit déjeuner','half_board'=>'Demi-pension','full_board'=>'Pension complète','all_inclusive'=>'All inclusive'] as $key => $label)
                        <option value="{{ $key }}" @selected($field('meal_plan', $customRequest->meal_plan) === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="aj-agent-dac-field"><label>Nombre de chambres</label><input type="number" min="1" name="rooms_count" value="{{ $field('rooms_count', $customRequest->rooms_count) }}"></div>
            <div class="aj-agent-dac-field">
                <label>Type de chambres</label>
                <select name="room_type">
                    <option value="">Non précisé</option>
                    @foreach(['single'=>'Single','double'=>'Double','triple'=>'Triple','quadruple'=>'Quadruple','family'=>'Familiale'] as $key => $label)
                        <option value="{{ $key }}" @selected($field('room_type', $customRequest->room_type) === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="aj-agent-dac-field">
                <label>Chambre séparée</label>
                <select name="separate_room_needed">
                    <option value="0" @selected(! (bool) $field('separate_room_needed', $customRequest->separate_room_needed))>Non</option>
                    <option value="1" @selected((bool) $field('separate_room_needed', $customRequest->separate_room_needed))>Oui</option>
                </select>
            </div>
            <div class="aj-agent-dac-field aj-agent-dac-field-wide"><label>Remarques hébergement</label><textarea name="accommodation_notes">{{ $field('accommodation_notes', $customRequest->accommodation_notes) }}</textarea></div>
        </div>
    </section>

    <section class="aj-agent-dac-section">
        <div class="aj-agent-dac-section-head">
            <h2>Transport</h2>
            <span>Vols, bagages et transferts</span>
        </div>
        <div class="aj-agent-dac-grid">
            <div class="aj-agent-dac-field">
                <label>Vol inclus</label>
                <select name="flight_included"><option value="">Non précisé</option>@foreach($yesNoConfirm as $key => $label)<option value="{{ $key }}" @selected($field('flight_included', $customRequest->flight_included) === $key)>{{ $label }}</option>@endforeach</select>
            </div>
            <div class="aj-agent-dac-field"><label>Compagnie préférée</label><input name="preferred_airline" value="{{ $field('preferred_airline', $customRequest->preferred_airline) }}"></div>
            <div class="aj-agent-dac-field"><label>Aéroport de départ</label><input name="departure_airport" value="{{ $field('departure_airport', $customRequest->departure_airport) }}"></div>
            <div class="aj-agent-dac-field"><label>Aéroport d'arrivée</label><input name="arrival_airport" value="{{ $field('arrival_airport', $customRequest->arrival_airport) }}"></div>
            <div class="aj-agent-dac-field">
                <label>Bagages inclus</label>
                <select name="baggage_included"><option value="">Non précisé</option>@foreach($yesNoConfirm as $key => $label)<option value="{{ $key }}" @selected($field('baggage_included', $customRequest->baggage_included) === $key)>{{ $label }}</option>@endforeach</select>
            </div>
            <div class="aj-agent-dac-field">
                <label>Transfert aéroport inclus</label>
                <select name="airport_transfer_included"><option value="">Non précisé</option><option value="yes" @selected($field('airport_transfer_included', $customRequest->airport_transfer_included) === 'yes')>Oui</option><option value="no" @selected($field('airport_transfer_included', $customRequest->airport_transfer_included) === 'no')>Non</option></select>
            </div>
            <div class="aj-agent-dac-field">
                <label>Transport sur place</label>
                <select name="local_transport">
                    <option value="">Non précisé</option>
                    @foreach(['none'=>'Aucun','bus'=>'Bus','minibus'=>'Minibus','private_car'=>'Voiture privée','private_driver'=>'Chauffeur privé'] as $key => $label)
                        <option value="{{ $key }}" @selected($field('local_transport', $customRequest->local_transport) === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="aj-agent-dac-field aj-agent-dac-field-wide"><label>Remarques transport</label><textarea name="transport_notes">{{ $field('transport_notes', $customRequest->transport_notes) }}</textarea></div>
        </div>
    </section>

    <section class="aj-agent-dac-section">
        <div class="aj-agent-dac-section-head">
            <h2>Services demandés</h2>
            <span>Prestations à chiffrer</span>
        </div>
        <div class="aj-agent-dac-services">
            @foreach($serviceOptions as $key => $label)
                <label><input type="checkbox" name="services[]" value="{{ $key }}" @checked(in_array($key, $selectedServices, true))> {{ $label }}</label>
            @endforeach
        </div>
        <div class="aj-agent-dac-field aj-agent-dac-field-wide aj-agent-dac-field-after">
            <label>Détails des services demandés</label>
            <textarea name="requested_services_details">{{ $field('requested_services_details', $customRequest->requested_services_details) }}</textarea>
        </div>
    </section>

    <section class="aj-agent-dac-section">
        <div class="aj-agent-dac-section-head">
            <h2>Paiement / estimation</h2>
            <span>Montants indicatifs</span>
        </div>
        <div class="aj-agent-dac-grid">
            <div class="aj-agent-dac-field"><label>Prix estimé</label><input type="number" min="0" step="0.01" name="estimated_price" value="{{ $field('estimated_price', $customRequest->estimated_price) }}"></div>
            <div class="aj-agent-dac-field"><label>Acompte demandé</label><input type="number" min="0" step="0.01" name="requested_deposit" value="{{ $field('requested_deposit', $customRequest->requested_deposit) }}"></div>
            <div class="aj-agent-dac-field"><label>Montant payé</label><input type="number" min="0" step="0.01" name="paid_amount" value="{{ $field('paid_amount', $customRequest->paid_amount ?? 0) }}"></div>
            <div class="aj-agent-dac-field"><label>Reste à payer</label><input type="number" min="0" step="0.01" value="{{ $field('remaining_amount', $customRequest->remaining_amount) }}" readonly></div>
            <div class="aj-agent-dac-field">
                <label>Mode de paiement</label>
                <select name="payment_method"><option value="">Non précisé</option>@foreach(['cash'=>'Espèces','transfer'=>'Virement','card'=>'Carte','cheque'=>'Chèque','other'=>'Autre'] as $key => $label)<option value="{{ $key }}" @selected($field('payment_method', $customRequest->payment_method) === $key)>{{ $label }}</option>@endforeach</select>
            </div>
            <div class="aj-agent-dac-field">
                <label>Statut paiement</label>
                <select name="payment_status">@foreach($paymentStatusOptions as $key => $label)<option value="{{ $key }}" @selected($field('payment_status', $customRequest->payment_status) === $key)>{{ $label }}</option>@endforeach</select>
            </div>
        </div>
    </section>

    <section class="aj-agent-dac-section">
        <div class="aj-agent-dac-section-head">
            <h2>Suivi</h2>
            <span>Priorité et notes internes</span>
        </div>
        <div class="aj-agent-dac-grid">
            <div class="aj-agent-dac-field">
                <label>Statut</label>
                <select disabled>
                    <option>Brouillon ou nouvelle demande selon le bouton choisi</option>
                </select>
            </div>
            <div class="aj-agent-dac-field">
                <label>Priorité</label>
                <select name="priority">@foreach($priorityOptions as $key => $label)<option value="{{ $key }}" @selected($field('priority', $customRequest->priority) === $key)>{{ $label }}</option>@endforeach</select>
            </div>
            <div class="aj-agent-dac-field"><label>Date limite de réponse</label><input type="date" name="response_deadline" value="{{ $field('response_deadline', optional($customRequest->response_deadline)->toDateString()) }}"></div>
            <div class="aj-agent-dac-field"><label>Documents</label><input type="file" name="documents[]" multiple></div>
            <div class="aj-agent-dac-field aj-agent-dac-field-wide"><label>Notes internes</label><textarea name="internal_notes">{{ $field('internal_notes', $customRequest->internal_notes) }}</textarea></div>
        </div>
    </section>

    <div class="aj-agent-dac-actions">
        <a href="{{ route('agent.custom-reservations.index') }}" class="aj-agent-action-btn"><i class="bx bx-arrow-back"></i> Retour</a>
        <div>
            <button type="submit" name="submit_action" value="draft" class="aj-agent-dac-secondary"><i class="bx bx-save"></i> Enregistrer brouillon</button>
            <button type="submit" name="submit_action" value="submit" class="aj-agent-primary-btn"><i class="bx bx-send"></i> Créer la demande</button>
        </div>
    </div>
</form>
