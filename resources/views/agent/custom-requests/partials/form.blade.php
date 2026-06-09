@php
    $selectedServices = collect(old('services', $customRequest->services?->pluck('service_key')->all() ?? []))->filter()->all();
    $yesNoConfirm = ['yes' => 'Oui', 'no' => 'Non', 'to_confirm' => 'À confirmer'];
    $field = fn ($name, $default = null) => old($name, $default);
@endphp

<form method="POST" action="{{ $formAction }}" enctype="multipart/form-data" class="aj-agent-dac-form">
    @csrf

    <div class="aj-dac-step-panel is-active" data-dac-step-panel="1">
        <section class="aj-agent-dac-section">
            <div class="aj-agent-dac-section-head">
                <h2>Informations générales</h2>
                <span>Fiche client, coordonnées et composition voyageurs.</span>
            </div>

            <div class="aj-agent-dac-grid">
                <div class="aj-agent-dac-field aj-agent-dac-field-wide">
                    <label>Type de client</label>
                    <div class="aj-dac-client-type">
                        <label>
                            <input type="radio" name="customer_type" value="new_customer" @checked($field('customer_type', $customRequest->customer_type) === 'new_customer')>
                            Nouveau client
                        </label>
                        <label>
                            <input type="radio" name="customer_type" value="existing_customer" @checked($field('customer_type', $customRequest->customer_type) === 'existing_customer')>
                            Client existant
                        </label>
                    </div>
                </div>

                <div class="aj-agent-dac-field aj-agent-dac-field-wide" data-existing-client-wrap @if($field('customer_type', $customRequest->customer_type) !== 'existing_customer') hidden @endif>
                    <label>Client existant</label>
                    <select name="existing_client_id" data-existing-client-select>
                        <option value="">Choisir un client de votre portefeuille</option>
                        @foreach($existingClients as $client)
                            <option
                                value="{{ $client['id'] }}"
                                data-client-full-name="{{ $client['full_name'] }}"
                                data-client-phone="{{ $client['phone'] }}"
                                data-client-email="{{ $client['email'] }}"
                                data-client-city="{{ $client['city'] }}"
                                data-client-country="{{ $client['country'] }}"
                                data-client-identity="{{ $client['identity'] }}"
                                @selected((string) $field('existing_client_id', $customRequest->client_id) === (string) $client['id'])
                            >
                                {{ $client['client_code'] }} - {{ $client['full_name'] }}
                                @if($client['phone'])
                                    · {{ $client['phone'] }}
                                @endif
                            </option>
                        @endforeach
                    </select>
                    <small>La liste est limitée aux clients qui vous sont rattachés.</small>
                    @error('existing_client_id')<small>{{ $message }}</small>@enderror
                </div>

                <div class="aj-agent-dac-field">
                    <label>Nom complet du client <span>*</span></label>
                    <input name="customer_full_name" value="{{ $field('customer_full_name', $customRequest->customer_full_name) }}" required placeholder="Ex: Mohammed El Alami" data-client-input="customer_full_name">
                    @error('customer_full_name')<small>{{ $message }}</small>@enderror
                </div>
                <div class="aj-agent-dac-field">
                    <label>Téléphone <span>*</span></label>
                    <input name="customer_phone" value="{{ $field('customer_phone', $customRequest->customer_phone) }}" required placeholder="06 00 00 00 00" data-client-input="customer_phone">
                    @error('customer_phone')<small>{{ $message }}</small>@enderror
                </div>
                <div class="aj-agent-dac-field">
                    <label>Email</label>
                    <input type="email" name="customer_email" value="{{ $field('customer_email', $customRequest->customer_email) }}" placeholder="contact@email.com" data-client-input="customer_email">
                    @error('customer_email')<small>{{ $message }}</small>@enderror
                </div>
                <div class="aj-agent-dac-field">
                    <label>Ville</label>
                    <input name="customer_city" value="{{ $field('customer_city', $customRequest->customer_city) }}" data-client-input="customer_city">
                </div>
                <div class="aj-agent-dac-field">
                    <label>Pays</label>
                    <input name="customer_country" value="{{ $field('customer_country', $customRequest->customer_country) }}" data-client-input="customer_country">
                </div>
                <div class="aj-agent-dac-field">
                    <label>CIN / Passeport</label>
                    <input name="customer_identity" value="{{ $field('customer_identity', $customRequest->customer_identity) }}" data-client-input="customer_identity">
                </div>
                <div class="aj-agent-dac-field aj-agent-dac-field-wide">
                    <label>Remarques client</label>
                    <textarea name="customer_notes">{{ $field('customer_notes', $customRequest->customer_notes) }}</textarea>
                </div>
            </div>
        </section>

        <section class="aj-agent-dac-section">
            <div class="aj-agent-dac-section-head">
                <h2>Voyage demandé</h2>
                <span>Destination, dates, niveau attendu et passagers.</span>
            </div>

            <div class="aj-agent-dac-grid">
                <div class="aj-agent-dac-field">
                    <label>Destination souhaitée <span>*</span></label>
                    <input name="desired_destination" value="{{ $field('desired_destination', $customRequest->desired_destination) }}" required placeholder="Ex: Istanbul">
                    @error('desired_destination')<small>{{ $message }}</small>@enderror
                </div>
                <div class="aj-agent-dac-field">
                    <label>Ville de départ <span>*</span></label>
                    <input name="departure_city" value="{{ $field('departure_city', $customRequest->departure_city) }}" required placeholder="Ex: Casablanca">
                    @error('departure_city')<small>{{ $message }}</small>@enderror
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
                    <input name="desired_duration" value="{{ $field('desired_duration', $customRequest->desired_duration) }}" placeholder="Ex: 7 nuits">
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
    </div>

    <div class="aj-dac-step-panel" data-dac-step-panel="2">
        <section class="aj-agent-dac-section">
            <div class="aj-agent-dac-section-head">
                <h2>Offre commerciale</h2>
                <span>Sélectionnez les briques de services, puis complétez les paramètres utiles.</span>
            </div>

            <div class="aj-agent-dac-services">
                @foreach($serviceOptions as $key => $label)
                    <label>
                        <input type="checkbox" name="services[]" value="{{ $key }}" @checked(in_array($key, $selectedServices, true))>
                        {{ $label }}
                    </label>
                @endforeach
            </div>

            <div class="aj-dac-service-configs">
                <div class="aj-dac-service-config" data-service-config="flight_ticket">
                    <div class="aj-dac-service-config-title"><i class="bx bx-plane-alt"></i> Configuration vol</div>
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
                    </div>
                </div>

                <div class="aj-dac-service-config" data-service-config="hotel">
                    <div class="aj-dac-service-config-title"><i class="bx bx-hotel"></i> Configuration hébergement</div>
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
                </div>

                <div class="aj-dac-service-config" data-service-config="transfers,transport,car_rental">
                    <div class="aj-dac-service-config-title"><i class="bx bx-car"></i> Configuration transport et transferts</div>
                    <div class="aj-agent-dac-grid">
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
                </div>

                <div class="aj-dac-service-config" data-service-config="visa,travel_insurance,tourist_guide,excursions,activities,catering,group_assistance,other">
                    <div class="aj-dac-service-config-title"><i class="bx bx-compass"></i> Détails services complémentaires</div>
                    <div class="aj-agent-dac-field aj-agent-dac-field-wide">
                        <label>Services à préciser dans le programme</label>
                        <textarea readonly rows="6" class="aj-dac-textarea-large">Complétez l’onglet “Détails de programme” pour décrire les journées, activités, excursions, assistance, visa, assurance ou autres services attendus.</textarea>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div class="aj-dac-step-panel" data-dac-step-panel="3">
        <section class="aj-agent-dac-section">
            <div class="aj-agent-dac-section-head">
                <h2>Détails de programme</h2>
                <span>Décrivez le déroulé souhaité, le rythme du voyage et les prestations à intégrer au programme.</span>
            </div>
            <div class="aj-agent-dac-grid">
                <div class="aj-agent-dac-field">
                    <label>Type de programme</label>
                    <select data-program-type>
                        <option>Programme libre à construire</option>
                        <option>Programme jour par jour souhaité</option>
                        <option>Programme mixte avec temps libre</option>
                    </select>
                </div>
                <div class="aj-agent-dac-field">
                    <label>Rythme souhaité</label>
                    <select data-program-rhythm>
                        <option>Non précisé</option>
                        <option>Souple</option>
                        <option>Équilibré</option>
                        <option>Intensif</option>
                    </select>
                </div>
                <div class="aj-agent-dac-field">
                    <label>Style d’expérience</label>
                    <select data-program-style>
                        <option>Non précisé</option>
                        <option>Famille</option>
                        <option>Culturel</option>
                        <option>Détente</option>
                        <option>Aventure</option>
                        <option>Premium</option>
                    </select>
                </div>
                <div class="aj-agent-dac-field aj-agent-dac-field-wide">
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:8px;">
                        <label style="margin:0;">Programme détaillé souhaité</label>
                        <button type="button" class="aj-dac-btn" data-generate-program>
                            <i class="bx bx-magic-wand"></i> Générer le programme avec IA
                        </button>
                    </div>
                    <textarea name="requested_services_details" rows="7" class="aj-dac-textarea-large" placeholder="Ex: Jour 1 arrivée et transfert hôtel, Jour 2 visite guidée, Jour 3 excursion, temps libre, préférences repas, contraintes horaires...">{{ $field('requested_services_details', $customRequest->requested_services_details) }}</textarea>
                </div>
                <div class="aj-agent-dac-field aj-agent-dac-field-wide">
                    <label>Contraintes / préférences à respecter</label>
                    <textarea rows="7" class="aj-dac-textarea-large" placeholder="Ex: éviter les longues marches, prévoir guide francophone, horaires adaptés aux enfants, proximité hôtel, exigences Omra, accessibilité..."></textarea>
                </div>
            </div>
        </section>
    </div>

    <div class="aj-dac-step-panel" data-dac-step-panel="4">
        <section class="aj-agent-dac-section">
            <div class="aj-agent-dac-section-head">
                <h2>Paiement / estimation</h2>
                <span>Montants indicatifs transmis avec la demande.</span>
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
                <span>Priorité, échéance, documents et notes internes.</span>
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
    </div>

    <div class="aj-dac-actionbar">
        <button type="button" class="aj-dac-btn" data-dac-prev hidden><i class="bx bx-arrow-back"></i> Précédent</button>
        <div>
            <a href="{{ route('agent.custom-reservations.index') }}" class="aj-dac-btn"><i class="bx bx-x"></i> Retour</a>
            <button type="button" class="aj-dac-btn aj-dac-btn-primary aj-dac-btn-next" data-dac-next>Étape suivante <i class="bx bx-chevron-right"></i></button>
            <button type="submit" name="submit_action" value="draft" class="aj-dac-btn" data-dac-submit hidden><i class="bx bx-save"></i> Enregistrer brouillon</button>
            <button type="submit" name="submit_action" value="submit" class="aj-dac-btn aj-dac-btn-primary" data-dac-submit hidden><i class="bx bx-send"></i> Créer la demande</button>
        </div>
    </div>
</form>
