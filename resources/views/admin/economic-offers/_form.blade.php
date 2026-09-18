@php
    $priceRows = old('prices', $offer->prices->map(fn($item) => [
        'label' => $item->label,
        'type' => $item->type,
        'price' => $item->price,
        'old_price' => $item->old_price,
        'stock' => $item->stock,
        'condition' => $item->condition,
    ])->all());

    $departureRows = old('departures', $offer->departures->map(fn($item) => [
        'departure_date' => optional($item->departure_date)->format('Y-m-d'),
        'return_date' => optional($item->return_date)->format('Y-m-d'),
        'price_from' => $item->price_from,
        'total_places' => $item->total_places,
        'available_places' => $item->available_places,
        'reserved_places' => $item->reserved_places,
        'status' => $item->status,
        'internal_notes' => $item->internal_notes,
    ])->all());

    $includedItems = collect(old('included_items_text') !== null
        ? preg_split('/\r\n|\r|\n/', (string) old('included_items_text'))
        : ($offer->included_items ?? []))->map(fn ($v) => trim((string) $v))->filter()->values();

    $excludedItems = collect(old('excluded_items_text') !== null
        ? preg_split('/\r\n|\r|\n/', (string) old('excluded_items_text'))
        : ($offer->excluded_items ?? []))->map(fn ($v) => trim((string) $v))->filter()->values();

    $keywords = old('seo_keywords_text', collect($offer->seo_keywords ?? [])->implode(', '));

    $issues = $report['issues'];
    $completion = $report['completion'];

    $rempli = static fn ($value) => ! blank($value);

    $sections = [
        ['id' => 'sec-1', 'label' => '1. Informations générales', 'ok' => $rempli($offer->title) && $rempli($offer->offer_type)],
        ['id' => 'sec-2', 'label' => '2. Prix & services', 'ok' => $rempli($offer->price_from) && $rempli($offer->cancellation_conditions)],
        ['id' => 'sec-3', 'label' => '3. Départs', 'hint' => count($departureRows) . ' ' . (count($departureRows) > 1 ? 'dates' : 'date')],
        ['id' => 'sec-4', 'label' => '4. Médias', 'hint' => $offer->images->count() . ' ' . ($offer->images->count() > 1 ? 'visuels' : 'visuel')],
        ['id' => 'sec-5', 'label' => '5. SEO & documents', 'ok' => $rempli($offer->meta_title)],
    ];
@endphp

<div class="oef-layout" data-oef-form>
    <aside class="oef-aside">
        <nav class="oef-nav" aria-label="Sections du formulaire">
            @foreach ($sections as $section)
                <a class="oef-nav__item" href="#{{ $section['id'] }}">
                    <span class="oef-nav__label">{{ $section['label'] }}</span>
                    @if (array_key_exists('hint', $section))
                        <span class="oef-nav__hint">{{ $section['hint'] }}</span>
                    @else
                        <span class="oef-nav__hint {{ $section['ok'] ? 'is-ok' : 'is-todo' }}">{{ $section['ok'] ? 'OK' : 'à compléter' }}</span>
                    @endif
                </a>
            @endforeach
        </nav>

        @if ($issues !== [])
            <div class="oef-issues">
                <span class="oef-issues__title">À compléter avant publication</span>
                <ul>
                    @foreach ($issues as $issue)
                        <li>{{ $issue }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

    </aside>

    <div class="oef-main">

        {{-- ─────────────── 1. Informations générales ─────────────── --}}
        <section class="oef-section" id="sec-1">
            <header class="oef-section__head">
                <span class="oef-section__title">1. Informations générales</span>
                <span class="oef-section__sub">Structure principale de l’offre économique.</span>
            </header>
            <div class="oef-section__body oef-grid">
                <label class="oef-field oef-field--full">
                    <span class="oef-label">Titre <b aria-hidden="true">*</b></span>
                    <input type="text" name="title" value="{{ old('title', $offer->title) }}" required>
                    @error('title') <span class="oef-error">{{ $message }}</span> @enderror
                </label>

                <label class="oef-field">
                    <span class="oef-label">Slug</span>
                    <input type="text" name="slug" value="{{ old('slug', $offer->slug) }}">
                    @error('slug') <span class="oef-error">{{ $message }}</span> @enderror
                </label>

                <label class="oef-field">
                    <span class="oef-label">Référence interne</span>
                    <input type="text" class="oef-mono" name="internal_reference" value="{{ old('internal_reference', $offer->internal_reference) }}">
                    @error('internal_reference') <span class="oef-error">{{ $message }}</span> @enderror
                </label>

                <label class="oef-field">
                    <span class="oef-label">Type d’offre <b aria-hidden="true">*</b></span>
                    <select name="offer_type" required>
                        @foreach ($typeOptions as $value => $label)
                            <option value="{{ $value }}" @selected(old('offer_type', $offer->offer_type) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="oef-field">
                    <span class="oef-label">Catégorie <b aria-hidden="true">*</b></span>
                    <select name="category" required>
                        @foreach ($categoryOptions as $value => $label)
                            <option value="{{ $value }}" @selected(old('category', $offer->category) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="oef-field">
                    <span class="oef-label">Statut <b aria-hidden="true">*</b></span>
                    <select name="status" required>
                        @foreach ($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $offer->status) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="oef-field">
                    <span class="oef-label">Ordre d’affichage</span>
                    <input type="number" name="sort_order" value="{{ old('sort_order', $offer->sort_order ?? 0) }}">
                </label>

                <div class="oef-field">
                    <span class="oef-label">Mise en avant</span>
                    <label class="oef-switch">
                        <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $offer->is_featured))>
                        <span class="oef-switch__track" aria-hidden="true"><span class="oef-switch__knob"></span></span>
                        <span class="oef-switch__label" data-on="Activée" data-off="Désactivée">
                            {{ old('is_featured', $offer->is_featured) ? 'Activée' : 'Désactivée' }}
                        </span>
                    </label>
                </div>

                <label class="oef-field oef-field--full">
                    <span class="oef-label">Description courte</span>
                    <textarea name="short_description" rows="2" data-oef-count="short-count">{{ old('short_description', $offer->short_description) }}</textarea>
                    <span class="oef-hint" id="short-count">{{ mb_strlen((string) old('short_description', $offer->short_description)) }} caractères · 160 conseillés</span>
                </label>

                <label class="oef-field oef-field--full">
                    <span class="oef-label">Description détaillée</span>
                    <textarea name="description" rows="5">{{ old('description', $offer->description) }}</textarea>
                </label>
            </div>
        </section>

        {{-- ─────────────── 2. Prix & services ─────────────── --}}
        <section class="oef-section" id="sec-2">
            <header class="oef-section__head">
                <span class="oef-section__title">2. Prix &amp; services</span>
                <span class="oef-section__sub">Tarification principale, options et services inclus.</span>
            </header>
            <div class="oef-section__body">
                <div class="oef-grid oef-grid--narrow">
                    <label class="oef-field">
                        <span class="oef-label">Prix à partir de <b aria-hidden="true">*</b></span>
                        <input type="number" step="0.01" min="0" name="price_from" value="{{ old('price_from', $offer->price_from) }}" data-oef-price>
                        @error('price_from') <span class="oef-error">{{ $message }}</span> @enderror
                    </label>

                    <label class="oef-field">
                        <span class="oef-label">Ancien prix</span>
                        <input type="number" step="0.01" min="0" name="old_price" value="{{ old('old_price', $offer->old_price) }}" data-oef-old-price>
                        <span class="oef-hint" data-oef-discount>
                            @php $p = (float) old('price_from', $offer->price_from); $o = (float) old('old_price', $offer->old_price); @endphp
                            @if ($o > 0 && $p > 0 && $o > $p)
                                Remise affichée : &minus;{{ (int) round((1 - $p / $o) * 100) }} %
                            @else
                                Aucune remise affichée
                            @endif
                        </span>
                    </label>

                    <label class="oef-field">
                        <span class="oef-label">Devise</span>
                        <input type="text" name="currency" value="{{ old('currency', $offer->currency ?: 'DH') }}" list="oef-currencies" maxlength="8">
                        <datalist id="oef-currencies"><option value="DH"></option><option value="EUR"></option><option value="USD"></option></datalist>
                    </label>

                    <label class="oef-field">
                        <span class="oef-label">Type de prix</span>
                        <select name="price_type">
                            <option value="">Non précisé</option>
                            @foreach ($priceTypeOptions as $value => $label)
                                <option value="{{ $value }}" @selected(old('price_type', $offer->price_type) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="oef-field">
                        <span class="oef-label">Acompte</span>
                        <input type="number" step="0.01" min="0" name="deposit_amount" value="{{ old('deposit_amount', $offer->deposit_amount) }}">
                    </label>
                </div>

                <div class="oef-block oef-grid oef-grid--narrow">
                    <label class="oef-field">
                        <span class="oef-label">Places totales</span>
                        <input type="number" min="0" name="total_places" value="{{ old('total_places', $offer->total_places) }}" data-oef-total>
                    </label>
                    <label class="oef-field">
                        <span class="oef-label">Places disponibles</span>
                        <input type="number" min="0" name="available_places" value="{{ old('available_places', $offer->available_places) }}" data-oef-available>
                    </label>
                    <label class="oef-field">
                        <span class="oef-label">Places réservées</span>
                        <input type="number" min="0" name="reserved_places" class="oef-readonly"
                               value="{{ old('reserved_places', $offer->reserved_places) }}" readonly data-oef-reserved>
                        <span class="oef-hint">Calculé : totales &minus; disponibles</span>
                    </label>
                </div>

                <div class="oef-block">
                    <span class="oef-label">Services inclus</span>
                    <div class="oef-services">
                        @foreach ([
                            'transport_included' => 'Transport inclus',
                            'flight_included' => 'Vol inclus',
                            'hotel_included' => 'Hôtel inclus',
                            'meals_included' => 'Repas inclus',
                            'guide_included' => 'Guide inclus',
                            'insurance_included' => 'Assurance incluse',
                            'transfer_included' => 'Transfert inclus',
                        ] as $name => $label)
                            <label class="oef-service {{ old($name, $offer->$name) ? 'is-on' : '' }}">
                                <input type="checkbox" name="{{ $name }}" value="1"
                                       @checked(old($name, $offer->$name))
                                       @if ($name === 'meals_included') data-oef-meals @endif>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>

                    <label class="oef-field oef-field--half" data-oef-meal-plan @if (! old('meals_included', $offer->meals_included)) hidden @endif>
                        <span class="oef-label">Formule de repas</span>
                        <select name="meal_plan">
                            @foreach ($mealPlanOptions as $value => $label)
                                <option value="{{ $value }}" @selected(old('meal_plan', $offer->meal_plan) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>

                <div class="oef-block oef-grid oef-grid--narrow">
                    <label class="oef-field">
                        <span class="oef-label">Type d’hébergement</span>
                        <input type="text" name="accommodation_type" value="{{ old('accommodation_type', $offer->accommodation_type) }}" placeholder="Hôtel, résidence…">
                    </label>
                    <label class="oef-field">
                        <span class="oef-label">Nom de l’hôtel</span>
                        <input type="text" name="hotel_name" value="{{ old('hotel_name', $offer->hotel_name) }}" placeholder="À renseigner">
                    </label>
                    <label class="oef-field">
                        <span class="oef-label">Catégorie hôtel</span>
                        <input type="text" name="hotel_category" value="{{ old('hotel_category', $offer->hotel_category) }}" list="oef-hotel-categories" placeholder="Non renseignée">
                        <datalist id="oef-hotel-categories">
                            <option value="3 étoiles"></option><option value="4 étoiles"></option><option value="5 étoiles"></option>
                        </datalist>
                    </label>
                    <label class="oef-field">
                        <span class="oef-label">Type de chambre</span>
                        <input type="text" name="room_type" value="{{ old('room_type', $offer->room_type) }}">
                    </label>
                </div>

                <label class="oef-field oef-block">
                    <span class="oef-label">Résumé du programme</span>
                    <input type="text" name="program_summary" value="{{ old('program_summary', $offer->program_summary) }}">
                </label>

                <div class="oef-block oef-grid oef-grid--wide">
                    @foreach ([
                        ['included_items_text', 'Inclus dans le prix', $includedItems, 'ok', 'Ajouter une prestation puis Entrée'],
                        ['excluded_items_text', 'Non inclus', $excludedItems, 'no', 'Ajouter une exclusion puis Entrée'],
                    ] as [$name, $legend, $items, $tone, $placeholder])
                        <div class="oef-field" data-oef-tags>
                            <span class="oef-label">{{ $legend }}</span>
                            <div class="oef-chips oef-chips--{{ $tone }}" data-oef-chip-list>
                                @foreach ($items as $item)
                                    <span class="oef-chip" data-oef-chip>
                                        <span>{{ $item }}</span>
                                        <button type="button" title="Retirer" data-oef-chip-remove>&times;<span class="oef-sr">Retirer</span></button>
                                    </span>
                                @endforeach
                            </div>
                            <input type="text" placeholder="{{ $placeholder }}" data-oef-chip-input>
                            {{-- Source de verite envoyee au serveur, tenue a jour par le script. --}}
                            <textarea name="{{ $name }}" hidden data-oef-chip-store>{{ $items->implode("\n") }}</textarea>
                        </div>
                    @endforeach
                </div>

                <div class="oef-block oef-grid oef-grid--wide">
                    <label class="oef-field">
                        <span class="oef-label">Conditions de paiement</span>
                        <textarea name="payment_conditions" rows="3">{{ old('payment_conditions', $offer->payment_conditions) }}</textarea>
                    </label>
                    <label class="oef-field">
                        <span class="oef-label">Conditions d’annulation</span>
                        <textarea name="cancellation_conditions" rows="3"
                                  class="{{ blank(old('cancellation_conditions', $offer->cancellation_conditions)) ? 'is-todo' : '' }}"
                                  placeholder="À renseigner avant publication">{{ old('cancellation_conditions', $offer->cancellation_conditions) }}</textarea>
                    </label>
                </div>

                <div class="oef-block oef-repeater" data-oef-repeater="prices">
                    <div class="oef-repeater__head">
                        <span class="oef-repeater__title">Prix variables</span>
                        <button type="button" class="oef-btn oef-btn--ghost" data-oef-add>+ Ajouter une ligne</button>
                    </div>
                    <div class="oef-repeater__rows" data-oef-rows>
                        @foreach ($priceRows as $index => $row)
                            <div class="oef-row" data-oef-row>
                                <div class="oef-grid oef-grid--tight">
                                    <label class="oef-field"><span class="oef-label">Libellé</span>
                                        <input type="text" name="prices[{{ $index }}][label]" value="{{ $row['label'] ?? '' }}"></label>
                                    <label class="oef-field"><span class="oef-label">Type</span>
                                        <input type="text" name="prices[{{ $index }}][type]" value="{{ $row['type'] ?? '' }}" list="oef-price-types">
                                    </label>
                                    <label class="oef-field"><span class="oef-label">Prix</span>
                                        <input type="number" step="0.01" min="0" name="prices[{{ $index }}][price]" value="{{ $row['price'] ?? '' }}"></label>
                                    <label class="oef-field"><span class="oef-label">Ancien prix</span>
                                        <input type="number" step="0.01" min="0" name="prices[{{ $index }}][old_price]" value="{{ $row['old_price'] ?? '' }}"></label>
                                    <label class="oef-field"><span class="oef-label">Stock</span>
                                        <input type="number" min="0" name="prices[{{ $index }}][stock]" value="{{ $row['stock'] ?? '' }}"></label>
                                    <label class="oef-field"><span class="oef-label">Condition</span>
                                        <input type="text" name="prices[{{ $index }}][condition]" value="{{ $row['condition'] ?? '' }}"></label>
                                </div>
                                <div class="oef-row__actions">
                                    <button type="button" class="oef-btn oef-btn--danger" data-oef-remove>Supprimer cette ligne</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <datalist id="oef-price-types"><option value="adulte"></option><option value="enfant"></option><option value="bébé"></option></datalist>
                </div>
            </div>
        </section>

        {{-- ─────────────── 3. Départs ─────────────── --}}
        <section class="oef-section" id="sec-3">
            <header class="oef-section__head">
                <span class="oef-section__title">3. Départs</span>
                <span class="oef-section__sub">Une offre économique peut avoir plusieurs dates et prix.</span>
            </header>
            <div class="oef-section__body">
                <div class="oef-grid oef-grid--narrow">
                    <label class="oef-field">
                        <span class="oef-label">Date de départ <b aria-hidden="true">*</b></span>
                        <input type="date" name="departure_date" value="{{ old('departure_date', optional($offer->departure_date)->format('Y-m-d')) }}">
                        @error('departure_date') <span class="oef-error">{{ $message }}</span> @enderror
                    </label>
                    <label class="oef-field">
                        <span class="oef-label">Date de retour</span>
                        <input type="date" name="return_date" value="{{ old('return_date', optional($offer->return_date)->format('Y-m-d')) }}">
                    </label>
                    <label class="oef-field">
                        <span class="oef-label">Jours</span>
                        <input type="number" min="0" name="duration_days" value="{{ old('duration_days', $offer->duration_days) }}">
                    </label>
                    <label class="oef-field">
                        <span class="oef-label">Nuits</span>
                        <input type="number" min="0" name="duration_nights" value="{{ old('duration_nights', $offer->duration_nights) }}">
                    </label>
                    <label class="oef-field">
                        <span class="oef-label">Disponibilité</span>
                        <select name="availability_status">
                            @foreach ($availabilityOptions as $value => $label)
                                <option value="{{ $value }}" @selected(old('availability_status', $offer->availability_status) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>

                <div class="oef-block oef-grid oef-grid--narrow">
                    <label class="oef-field">
                        <span class="oef-label">Ville de départ</span>
                        <input type="text" name="departure_city" value="{{ old('departure_city', $offer->departure_city) }}">
                    </label>
                    <label class="oef-field">
                        <span class="oef-label">Destination</span>
                        <input type="text" name="destination" value="{{ old('destination', $offer->destination) }}">
                    </label>
                    <label class="oef-field">
                        <span class="oef-label">Pays</span>
                        <input type="text" name="country" value="{{ old('country', $offer->country) }}">
                    </label>
                    <label class="oef-field">
                        <span class="oef-label">Ville d’arrivée</span>
                        <input type="text" name="arrival_city" value="{{ old('arrival_city', $offer->arrival_city) }}">
                    </label>
                    <label class="oef-field">
                        <span class="oef-label">Distance clé</span>
                        <input type="text" name="key_distance" value="{{ old('key_distance', $offer->key_distance) }}" placeholder="ex. 4 km de la mosquée">
                    </label>
                    <label class="oef-field">
                        <span class="oef-label">Adresse / zone</span>
                        <input type="text" name="address_zone" value="{{ old('address_zone', $offer->address_zone) }}">
                    </label>
                </div>

                <div class="oef-block oef-repeater" data-oef-repeater="departures">
                    <div class="oef-repeater__head">
                        <span class="oef-repeater__title">Départs multiples</span>
                        <button type="button" class="oef-btn oef-btn--ghost" data-oef-add>+ Ajouter un départ</button>
                    </div>
                    <div class="oef-repeater__rows" data-oef-rows>
                        @foreach ($departureRows as $index => $row)
                            @php
                                $total = (int) ($row['total_places'] ?? 0);
                                $avail = (int) ($row['available_places'] ?? 0);
                                $taken = max(0, $total - $avail);
                            @endphp
                            <div class="oef-row" data-oef-row>
                                <div class="oef-row__head">
                                    <span class="oef-row__title">Départ {{ $index + 1 }} &middot; {{ $row['internal_notes'] ?: 'sans note' }}</span>
                                    <span class="oef-row__fill {{ $total > 0 && $taken >= $total ? 'is-full' : '' }}">{{ $taken }} réservées sur {{ $total }}</span>
                                </div>
                                <div class="oef-grid oef-grid--tight">
                                    <label class="oef-field"><span class="oef-label">Départ</span>
                                        <input type="date" name="departures[{{ $index }}][departure_date]" value="{{ $row['departure_date'] ?? '' }}"></label>
                                    <label class="oef-field"><span class="oef-label">Retour</span>
                                        <input type="date" name="departures[{{ $index }}][return_date]" value="{{ $row['return_date'] ?? '' }}"></label>
                                    <label class="oef-field"><span class="oef-label">Prix</span>
                                        <input type="number" step="0.01" min="0" name="departures[{{ $index }}][price_from]" value="{{ $row['price_from'] ?? '' }}"></label>
                                    <label class="oef-field"><span class="oef-label">Places totales</span>
                                        <input type="number" min="0" name="departures[{{ $index }}][total_places]" value="{{ $row['total_places'] ?? '' }}"></label>
                                    <label class="oef-field"><span class="oef-label">Places dispo</span>
                                        <input type="number" min="0" name="departures[{{ $index }}][available_places]" value="{{ $row['available_places'] ?? '' }}"></label>
                                    <label class="oef-field"><span class="oef-label">Réservées</span>
                                        <input type="number" min="0" name="departures[{{ $index }}][reserved_places]" value="{{ $row['reserved_places'] ?? '' }}"></label>
                                    <label class="oef-field"><span class="oef-label">Statut</span>
                                        <select name="departures[{{ $index }}][status]">
                                            @foreach ($departureStatusOptions as $value => $label)
                                                <option value="{{ $value }}" @selected(($row['status'] ?? '') === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                    <label class="oef-field"><span class="oef-label">Note interne</span>
                                        <input type="text" name="departures[{{ $index }}][internal_notes]" value="{{ $row['internal_notes'] ?? '' }}"></label>
                                </div>
                                <div class="oef-row__actions">
                                    <button type="button" class="oef-btn oef-btn--danger" data-oef-remove>Supprimer ce départ</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        {{-- ─────────────── 4. Médias ─────────────── --}}
        <section class="oef-section" id="sec-4">
            <header class="oef-section__head">
                <span class="oef-section__title">4. Médias</span>
                <span class="oef-section__sub">Image principale, galerie et vidéo.</span>
            </header>
            <div class="oef-section__body">
                <div class="oef-grid oef-grid--media">
                    <div class="oef-field">
                        <span class="oef-label">Image principale</span>
                        <label class="oef-drop">
                            @if ($offer->main_image_url)
                                <img src="{{ $offer->main_image_url }}" alt="">
                            @else
                                <span class="oef-drop__title">Déposer une image</span>
                                <span class="oef-drop__hint">JPG ou PNG &middot; 1600 &times; 1000 recommandé</span>
                            @endif
                            <input type="file" name="main_image_file" accept=".jpg,.jpeg,.png,.webp">
                        </label>
                        @if ($offer->main_image)
                            <label class="oef-inline-check">
                                <input type="checkbox" name="remove_main_image" value="1"><span>Supprimer l’image principale</span>
                            </label>
                        @endif
                    </div>

                    <div class="oef-field">
                        <span class="oef-label">Image de secours</span>
                        <label class="oef-drop">
                            <span class="oef-drop__title">Déposer une image</span>
                            <span class="oef-drop__hint">Utilisée si l’image principale manque</span>
                            <input type="file" name="fallback_image_file" accept=".jpg,.jpeg,.png,.webp">
                        </label>
                        @if ($offer->fallback_image)
                            <label class="oef-inline-check">
                                <input type="checkbox" name="remove_fallback_image" value="1"><span>Supprimer l’image de secours</span>
                            </label>
                        @endif
                    </div>

                    <label class="oef-field">
                        <span class="oef-label">Vidéo (URL)</span>
                        <input type="url" name="video_url" value="{{ old('video_url', $offer->video_url) }}" placeholder="https://">
                        <span class="oef-hint">YouTube ou Vimeo. Laissez vide si aucune vidéo.</span>
                    </label>
                </div>

                <div class="oef-block">
                    <div class="oef-repeater__head">
                        <span class="oef-label">Galerie images</span>
                        <span class="oef-hint">{{ $offer->images->count() }} image{{ $offer->images->count() > 1 ? 's' : '' }} dans la galerie</span>
                    </div>
                    <div class="oef-gallery">
                        @foreach ($offer->images as $image)
                            <figure class="oef-gallery__item">
                                @if ($image->image_url)
                                    <img src="{{ $image->image_url }}" alt="" loading="lazy">
                                @else
                                    <span class="oef-gallery__ph">IMG</span>
                                @endif
                            </figure>
                        @endforeach
                        <label class="oef-gallery__add">
                            <span class="oef-gallery__plus" aria-hidden="true">+</span>
                            <span class="oef-hint">Ajouter</span>
                            <input type="file" name="gallery_images[]" accept=".jpg,.jpeg,.png,.webp" multiple>
                        </label>
                    </div>
                    @if ($offer->images->count())
                        <label class="oef-inline-check">
                            <input type="checkbox" name="replace_gallery" value="1"><span>Remplacer toute la galerie par les fichiers ajoutés</span>
                        </label>
                    @endif
                </div>
            </div>
        </section>

        {{-- ─────────────── 5. SEO & documents ─────────────── --}}
        <section class="oef-section" id="sec-5">
            <header class="oef-section__head">
                <span class="oef-section__title">5. SEO &amp; documents</span>
                <span class="oef-section__sub">Balises de référencement et pièces demandées au client.</span>
            </header>
            <div class="oef-section__body oef-grid oef-grid--wide">
                <label class="oef-field">
                    <span class="oef-label">Meta title</span>
                    <input type="text" name="meta_title" value="{{ old('meta_title', $offer->meta_title) }}"
                           placeholder="{{ $offer->title ? $offer->title . ' — Ajinsafro' : 'Titre affiché dans Google' }}"
                           data-oef-count="meta-title-count" data-oef-limit="60">
                    <span class="oef-hint" id="meta-title-count">{{ mb_strlen((string) old('meta_title', $offer->meta_title)) }} / 60 caractères</span>
                </label>

                <label class="oef-field">
                    <span class="oef-label">Mots-clés</span>
                    <input type="text" name="seo_keywords_text" value="{{ $keywords }}" placeholder="omra, économique, casablanca">
                    <span class="oef-hint">Séparés par des virgules ou des retours à la ligne.</span>
                </label>

                <label class="oef-field oef-field--full">
                    <span class="oef-label">Meta description</span>
                    <textarea name="meta_description" rows="3" placeholder="Résumé affiché dans les résultats de recherche"
                              data-oef-count="meta-desc-count" data-oef-limit="160">{{ old('meta_description', $offer->meta_description) }}</textarea>
                    <span class="oef-hint" id="meta-desc-count">{{ mb_strlen((string) old('meta_description', $offer->meta_description)) }} / 160 caractères</span>
                </label>

                <label class="oef-field">
                    <span class="oef-label">Image SEO</span>
                    <input type="file" name="seo_image_file" accept=".jpg,.jpeg,.png,.webp">
                </label>

                <label class="oef-field oef-field--full">
                    <span class="oef-label">Documents nécessaires</span>
                    <textarea name="required_documents" rows="2">{{ old('required_documents', $offer->required_documents) }}</textarea>
                </label>
            </div>
        </section>

        <div class="oef-savebar">
            <span class="oef-savebar__state" data-oef-dirty>Aucune modification en attente.</span>
            <div class="oef-savebar__actions">
                {{ $secondaryAction ?? '' }}
                <button type="submit" class="oef-btn oef-btn--primary">{{ $submitLabel ?? 'Enregistrer' }}</button>
            </div>
        </div>
    </div>
</div>

{{-- Modeles des lignes ajoutees dynamiquement. --}}
<template data-oef-template="prices">
    <div class="oef-row" data-oef-row>
        <div class="oef-grid oef-grid--tight">
            <label class="oef-field"><span class="oef-label">Libellé</span><input type="text" name="prices[__INDEX__][label]"></label>
            <label class="oef-field"><span class="oef-label">Type</span><input type="text" name="prices[__INDEX__][type]" list="oef-price-types"></label>
            <label class="oef-field"><span class="oef-label">Prix</span><input type="number" step="0.01" min="0" name="prices[__INDEX__][price]"></label>
            <label class="oef-field"><span class="oef-label">Ancien prix</span><input type="number" step="0.01" min="0" name="prices[__INDEX__][old_price]"></label>
            <label class="oef-field"><span class="oef-label">Stock</span><input type="number" min="0" name="prices[__INDEX__][stock]"></label>
            <label class="oef-field"><span class="oef-label">Condition</span><input type="text" name="prices[__INDEX__][condition]"></label>
        </div>
        <div class="oef-row__actions">
            <button type="button" class="oef-btn oef-btn--danger" data-oef-remove>Supprimer cette ligne</button>
        </div>
    </div>
</template>

<template data-oef-template="departures">
    <div class="oef-row" data-oef-row>
        <div class="oef-row__head">
            <span class="oef-row__title">Nouveau départ</span>
        </div>
        <div class="oef-grid oef-grid--tight">
            <label class="oef-field"><span class="oef-label">Départ</span><input type="date" name="departures[__INDEX__][departure_date]"></label>
            <label class="oef-field"><span class="oef-label">Retour</span><input type="date" name="departures[__INDEX__][return_date]"></label>
            <label class="oef-field"><span class="oef-label">Prix</span><input type="number" step="0.01" min="0" name="departures[__INDEX__][price_from]"></label>
            <label class="oef-field"><span class="oef-label">Places totales</span><input type="number" min="0" name="departures[__INDEX__][total_places]"></label>
            <label class="oef-field"><span class="oef-label">Places dispo</span><input type="number" min="0" name="departures[__INDEX__][available_places]"></label>
            <label class="oef-field"><span class="oef-label">Réservées</span><input type="number" min="0" name="departures[__INDEX__][reserved_places]"></label>
            <label class="oef-field"><span class="oef-label">Statut</span>
                <select name="departures[__INDEX__][status]">
                    @foreach ($departureStatusOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="oef-field"><span class="oef-label">Note interne</span><input type="text" name="departures[__INDEX__][internal_notes]"></label>
        </div>
        <div class="oef-row__actions">
            <button type="button" class="oef-btn oef-btn--danger" data-oef-remove>Supprimer ce départ</button>
        </div>
    </div>
</template>
