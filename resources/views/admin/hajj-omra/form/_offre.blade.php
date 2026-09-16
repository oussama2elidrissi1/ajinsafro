{{-- Etape 1 : l'essentiel commercial de l'offre. --}}
@php
    $available = (int) old('available_places', $package->available_places ?? 0);
    $reserved = (int) old('reserved_places', $package->reserved_places ?? 0);
    $fillRatio = $available > 0 ? min(100, (int) round($reserved / $available * 100)) : 0;
@endphp

<div class="ho-panel" data-panel="offre">
    <div class="row g-3">
        <div class="col-xl-8">

            <section class="ho-card">
                <div class="ho-eyebrow">Identité de l'offre</div>

                @include('admin.hajj-omra.form._field', [
                    'name' => 'title_fr',
                    'nameAr' => 'title_ar',
                    'label' => 'Titre de l\'offre',
                    'labelAr' => 'عنوان العرض',
                    'value' => $package->title,
                    'valueAr' => $package->title_ar,
                    'required' => false,
                    'maxlength' => 255,
                    'hint' => 'Exemple : Omra Ramadan 1448 — 14 jours',
                ])

                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label" for="type">Type d'offre <span class="text-danger">*</span></label>
                        <select id="type" name="type" class="form-select @error('type') is-invalid @enderror" required>
                            @foreach ($typeOptions as $key => $label)
                                <option value="{{ $key }}" @selected(old('type', $package->type) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        {{-- id distinct de « status » : le theme reserve #status a son preloader. --}}
                        <label class="form-label" for="offer_status">Statut <span class="text-danger">*</span></label>
                        <select id="offer_status" name="status" class="form-select @error('status') is-invalid @enderror" required>
                            @foreach ($statusOptions as $key => $label)
                                <option value="{{ $key }}" @selected(old('status', $package->status) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="departure_city">Ville de départ</label>
                        <input type="text" id="departure_city" name="departure_city" class="form-control"
                               value="{{ old('departure_city', $package->departure_city) }}" placeholder="Casablanca">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="destination">Destination</label>
                        <input type="text" id="destination" name="destination" class="form-control"
                               value="{{ old('destination', $package->destination) }}" placeholder="Makkah / Madinah">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="whatsapp_phone">Numéro WhatsApp de l'offre</label>
                        <input type="text" id="whatsapp_phone" name="whatsapp_phone" inputmode="tel"
                               class="form-control ho-mono @error('whatsapp_phone') is-invalid @enderror"
                               value="{{ old('whatsapp_phone', $package->whatsapp_phone) }}" placeholder="+212 6 00 00 00 00">
                        <div class="form-text">
                            Reçoit les demandes WhatsApp de cette offre, sur la fiche publique et le catalogue.
                            Laissez vide pour utiliser le numéro général de l'agence.
                        </div>
                        @error('whatsapp_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label" for="duration_days">Nombre de jours <span class="text-danger">*</span></label>
                        <input type="number" min="1" max="365" id="duration_days" name="duration_days"
                               class="form-control @error('duration_days') is-invalid @enderror"
                               value="{{ old('duration_days', $package->duration_days) }}" required
                               data-role="duration-days">
                        <div class="form-text">Pilote la génération automatique du programme.</div>
                        @error('duration_days')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="duration_nights">Nombre de nuits</label>
                        <input type="number" min="0" max="365" id="duration_nights" name="duration_nights" class="form-control"
                               value="{{ old('duration_nights', $package->duration_nights) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="start_date">Départ principal</label>
                        <input type="date" id="start_date" name="start_date" class="form-control"
                               value="{{ old('start_date', optional($package->start_date)->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="return_date">Retour principal</label>
                        <input type="date" id="return_date" name="return_date"
                               class="form-control @error('return_date') is-invalid @enderror"
                               value="{{ old('return_date', optional($package->return_date)->format('Y-m-d')) }}">
                        @error('return_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </section>

            <section class="ho-card">
                <div class="d-flex align-items-baseline gap-3 flex-wrap mb-3">
                    <div class="ho-eyebrow mb-0">Prix principal</div>
                    <button type="button" class="btn btn-link btn-sm p-0 fw-semibold" data-goto-step="tarifs">Modifier les tarifs →</button>
                </div>

                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label" for="adult_price">Prix à partir de</label>
                        <div class="input-group">
                            <input type="number" step="0.01" min="0" id="adult_price" name="adult_price" class="form-control"
                                   value="{{ old('adult_price', $package->adult_price) }}" data-role="price-current">
                            <span class="input-group-text" data-role="currency-label">{{ old('currency', $package->currency ?: 'DH') }}</span>
                        </div>
                        <div data-from-price-note hidden>
                            <p class="form-text mb-1" data-ho-fr="Laissez vide pour appliquer automatiquement le tarif actif le plus bas. Une valeur saisie ici s’affiche telle quelle côté client." data-ho-ar="اتركه فارغاً لاستخدام أقل سعر مفعّل تلقائياً. أي قيمة تُدخل هنا تظهر كما هي للعميل.">Laissez vide pour appliquer automatiquement le tarif actif le plus bas. Une valeur saisie ici s’affiche telle quelle côté client.</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="old_price">Ancien prix / valeur</label>
                        <input type="number" step="0.01" min="0" id="old_price" name="old_price" class="form-control"
                               value="{{ old('old_price', $package->old_price) }}" data-role="price-old">
                        <div class="form-text">Affiché barré sur le site public.</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="discount_amount">Économie / remise</label>
                        <input type="number" step="0.01" min="0" id="discount_amount" name="discount_amount" class="form-control"
                               value="{{ old('discount_amount', $package->discount_amount) }}" data-role="price-discount">
                        <div class="form-text" data-role="discount-hint">Calculée automatiquement si laissée vide.</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="currency">Devise <span class="text-danger">*</span></label>
                        <select id="currency" name="currency" class="form-select" required data-role="currency">
                            @foreach (['DH' => 'DH', 'MAD' => 'MAD', 'EUR' => 'EUR', 'USD' => 'USD'] as $key => $label)
                                <option value="{{ $key }}" @selected(old('currency', $package->currency ?: 'DH') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label" for="child_price">Prix enfant</label>
                        <input type="number" step="0.01" min="0" id="child_price" name="child_price" class="form-control"
                               value="{{ old('child_price', $package->child_price) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="baby_price">Prix bébé</label>
                        <input type="number" step="0.01" min="0" id="baby_price" name="baby_price" class="form-control"
                               value="{{ old('baby_price', $package->baby_price) }}">
                    </div>
                    <div class="col-md-6">
                        <div class="ho-row mb-0" style="padding:.8rem .95rem;">
                            <div class="row g-2 align-items-end">
                                <div class="col-6">
                                    <label class="form-label" for="available_places">Places disponibles</label>
                                    <input type="number" min="0" id="available_places" name="available_places"
                                           class="form-control form-control-sm @error('available_places') is-invalid @enderror"
                                           value="{{ $available }}">
                                    @error('available_places')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-6">
                                    <label class="form-label" for="reserved_places">Dont réservées</label>
                                    <input type="number" min="0" id="reserved_places" name="reserved_places"
                                           class="form-control form-control-sm @error('reserved_places') is-invalid @enderror"
                                           value="{{ $reserved }}">
                                    @error('reserved_places')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-12">
                                    <div class="progress" style="height:5px;"><div class="progress-bar" style="width: {{ $fillRatio }}%; background: var(--ho-accent);"></div></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="ho-row mb-0 d-flex align-items-center gap-3" style="cursor:pointer;background:#fff;">
                            <input type="hidden" name="is_featured" value="0">
                            <input class="form-check-input mt-0" type="checkbox" id="is_featured" name="is_featured" value="1"
                                   @checked(old('is_featured', $package->is_featured))>
                            <span>
                                <span class="d-block fw-semibold" style="font-size:.86rem;">Mettre cette offre en avant</span>
                                <span class="d-block form-text">Épinglée en tête du catalogue public.</span>
                            </span>
                        </label>
                    </div>
                </div>
            </section>

            <section class="ho-card mb-0">
                <div class="ho-eyebrow">Description</div>

                @include('admin.hajj-omra.form._field', [
                    'name' => 'short_description_fr',
                    'nameAr' => 'short_description_ar',
                    'label' => 'Description courte',
                    'labelAr' => 'وصف موجز',
                    'value' => $package->short_description,
                    'valueAr' => $package->short_description_ar,
                    'type' => 'textarea',
                    'rows' => 3,
                    'maxlength' => 1200,
                    'hint' => 'Reprise dans les listes et les cartes du site public.',
                ])

                @include('admin.hajj-omra.form._field', [
                    'name' => 'description_fr',
                    'nameAr' => 'description_ar',
                    'label' => 'Description détaillée',
                    'labelAr' => 'الوصف التفصيلي',
                    'value' => $package->description,
                    'valueAr' => $package->description_ar,
                    'type' => 'editor',
                    'rows' => 8,
                ])
            </section>
        </div>

        {{-- Rappel permanent de ce que verra le client et de ce qu'il reste a faire. --}}
        <div class="col-xl-4">
            <div class="ho-preview-card mb-3">
                <div class="ho-eyebrow">Aperçu carte publique</div>
                <div class="ho-preview-card__inner">
                    <div class="ho-preview-card__media">
                        @if ($package->main_image_url)
                            <img src="{{ $package->main_image_url }}" alt="">
                        @else
                            Image principale manquante
                        @endif
                    </div>
                    <div class="mt-2 fw-semibold" style="font-size:.9rem;line-height:1.3;">{{ $package->title ?: 'Titre à renseigner' }}</div>
                    <div class="mt-1" style="font-size:.76rem;color:#b9d4ea;line-height:1.5;">{{ \Illuminate\Support\Str::limit($package->short_description, 90) ?: 'Description courte à renseigner.' }}</div>
                    <div class="mt-2 d-flex align-items-baseline gap-2">
                        <b class="ho-preview-card__price">{{ $package->exists && $package->price_from_value !== null ? number_format($package->price_from_value, 0, ',', ' ') : '—' }}</b>
                        <span style="font-size:.76rem;color:#b9d4ea;">{{ $package->currency ?: 'DH' }}{{ $package->duration_label ? ' · '.$package->duration_label : '' }}</span>
                    </div>
                </div>
            </div>

            @if (! empty($editorTodos))
                <div class="ho-card mb-0">
                    <div class="ho-eyebrow">À finaliser</div>
                    @foreach ($editorTodos as $todo)
                        <div @class(['ho-todo', 'is-done' => $todo['done']])>
                            <span class="ho-todo__mark">{{ $todo['done'] ? '✓' : '!' }}</span>
                            <span>{{ $todo['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
