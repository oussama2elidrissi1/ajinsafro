@extends('layouts.partner-v2')

@section('title', 'Créer une réservation')
@section('hidePageFooter', '1')

@push('css')
    <link rel="stylesheet" href="{{ asset('css/reservation-create-v2.css') }}">
@endpush

@section('content')
<div class="reservation-create-v2">
    <header class="v2-header">
        <div class="v2-header__top">
            <nav class="v2-breadcrumb" aria-label="Breadcrumb">
                <a href="{{ route('partner.catalogue.index') }}">Catalogue</a>
                <i class="bx bx-chevron-right"></i>
                <span>Nouvelle réservation</span>
            </nav>
            <div class="v2-header__actions">
                <a href="{{ route('partner.reservations.index') }}" class="v2-btn v2-btn--ghost">
                    <i class="bx bx-arrow-back"></i> Retour
                </a>
                <button type="button" class="v2-btn v2-btn--outline" id="v2-btn-draft">
                    <i class="bx bx-save"></i> Enregistrer brouillon
                </button>
            </div>
        </div>
        <div class="v2-header__main">
            <h1 class="v2-header__title">Créer une réservation</h1>
            <p class="v2-header__subtitle">Circuit, voyageurs, hébergement et paiement — en 4 étapes.</p>
        </div>
    </header>

    @if ($errors->any())
        <div class="v2-alert v2-alert--error">
            <strong>Le dossier contient des erreurs.</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="post" action="{{ route('partner.reservations.store') }}" enctype="multipart/form-data" id="v2-reservation-form">
        @csrf
        <input type="hidden" name="extras_json" id="v2-extras-json" value="[]">
        <input type="hidden" name="travelers_json" id="v2-travelers-json" value="[]">
        <input type="hidden" name="room_allocations_json" id="v2-room-allocations-json" value="{{ old('room_allocations_json', '[]') }}">
        <input type="hidden" name="accommodation_mode" id="v2-accommodation-mode" value="rooms">
        <input type="hidden" name="total_base" id="v2-total-base" value="{{ old('total_base', 0) }}">
        <input type="hidden" name="room_supplement_total" id="v2-room-supplement-total" value="{{ old('room_supplement_total', 0) }}">
        <input type="hidden" name="extras_total" id="v2-extras-total" value="{{ old('extras_total', 0) }}">
        <input type="hidden" name="total_amount" id="v2-total-amount" value="{{ old('total_amount', 0) }}">

        <div class="v2-layout">
            <main class="v2-main">
                <div class="v2-stepper" role="tablist" aria-label="Étapes de création">
                    <button type="button" class="v2-stepper__step is-active" data-v2-step-nav="1">
                        <span class="v2-stepper__index">1</span>
                        <span class="v2-stepper__label">Circuit</span>
                    </button>
                    <button type="button" class="v2-stepper__step" data-v2-step-nav="2">
                        <span class="v2-stepper__index">2</span>
                        <span class="v2-stepper__label">Voyageurs</span>
                    </button>
                    <button type="button" class="v2-stepper__step" data-v2-step-nav="3">
                        <span class="v2-stepper__index">3</span>
                        <span class="v2-stepper__label">Chambres & extras</span>
                    </button>
                    <button type="button" class="v2-stepper__step" data-v2-step-nav="4">
                        <span class="v2-stepper__index">4</span>
                        <span class="v2-stepper__label">Paiement</span>
                    </button>
                </div>

                {{-- Étape 1 : Circuit --}}
                <section class="v2-panel is-active" data-v2-step="1">
                    <div class="v2-card">
                        <div class="v2-card__head">
                            <div>
                                <p class="v2-eyebrow">Étape 1 — Circuit</p>
                                <h2 class="v2-card__title">Choisir un voyage et un départ</h2>
                            </div>
                        </div>

                        <div class="v2-grid v2-grid--2">
                            <div class="v2-field v2-field--full">
                                <label class="v2-label" for="v2-tour-id">Voyage <span class="v2-required">*</span></label>
                                <select name="tour_id" id="v2-tour-id" class="v2-input" required>
                                    <option value="">Sélectionner un voyage…</option>
                                    @foreach($voyages as $v)
                                        @php($wpTitle = $v->wp_post_id && isset($wpTitles[$v->wp_post_id]) ? ($wpTitles[$v->wp_post_id]->post_title ?? null) : null)
                                        <option value="{{ $v->id }}"
                                            data-wp-post-id="{{ $v->wp_post_id }}"
                                            {{ (int) old('tour_id', $preselectedTourId ?? 0) === (int) $v->id ? 'selected' : '' }}>
                                            {{ $wpTitle ?: $v->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div id="v2-departure-block" class="v2-departure-block" hidden>
                            <h3 class="v2-subtitle">Départs disponibles</h3>
                            <div id="v2-departure-list" class="v2-departure-list"></div>
                            <input type="hidden" name="departure_id" id="v2-departure-id-hidden" value="{{ old('departure_id', $selectedDepartureId ?? '') }}">
                            <input type="hidden" name="travel_date_id" id="v2-travel-date-id-hidden" value="{{ old('travel_date_id', $travelDateId ?? '') }}">
                        </div>

                        <div class="v2-departure-summary">
                            <div class="v2-departure-summary__grid">
                                <div class="v2-departure-summary__item">
                                    <span class="v2-departure-summary__label">Prix unitaire</span>
                                    <strong class="v2-departure-summary__value" id="v2-summary-unit-price">—</strong>
                                </div>
                                <div class="v2-departure-summary__item">
                                    <span class="v2-departure-summary__label">Statut</span>
                                    <strong class="v2-departure-summary__value" id="v2-summary-status">—</strong>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" name="base_price" id="v2-base-price-hidden" value="{{ old('base_price', $selectedUnitPrice !== null ? number_format((float) $selectedUnitPrice, 2, '.', '') : '') }}">
                    </div>

                    <div class="v2-actions">
                        <span></span>
                        <button type="button" class="v2-btn v2-btn--primary" data-v2-next="2">Continuer <i class="bx bx-right-arrow-alt"></i></button>
                    </div>
                </section>

                {{-- Étape 2 : Voyageurs --}}
                <section class="v2-panel" data-v2-step="2" hidden>
                    <div class="v2-card">
                        <div class="v2-card__head">
                            <div>
                                <p class="v2-eyebrow">Étape 2 — Voyageurs</p>
                                <h2 class="v2-card__title">Client principal & accompagnants</h2>
                            </div>
                            <div class="v2-badge" id="v2-travelers-badge">1 voyageur</div>
                        </div>

                        <div class="v2-segmented">
                            <label class="v2-segmented__item">
                                <input type="radio" name="client_mode" value="new" id="v2-client-mode-new" {{ old('client_mode', 'new') === 'new' ? 'checked' : '' }}>
                                <span>Nouveau client</span>
                            </label>
                            <label class="v2-segmented__item">
                                <input type="radio" name="client_mode" value="existing" id="v2-client-mode-existing" {{ old('client_mode') === 'existing' ? 'checked' : '' }}>
                                <span>Client existant</span>
                            </label>
                        </div>

                        {{-- Existant --}}
                        <div id="v2-existing-client-block" {{ old('client_mode') !== 'existing' ? 'hidden' : '' }}>
                            <div class="v2-field v2-field--full" style="position:relative;z-index:20;">
                                <label class="v2-label" for="v2-client-search">Rechercher un client <span class="v2-required">*</span></label>
                                <input type="hidden" name="client_external_id" id="v2-client-external-id" value="">
                                <input type="search" id="v2-client-search" class="v2-input" placeholder="Nom, téléphone, email…"
                                       autocomplete="off">
                                <div id="v2-client-search-results" class="v2-search-results" hidden></div>
                                <div id="v2-client-search-selected" class="v2-search-selected" hidden>
                                    <span id="v2-client-search-selected-label"></span>
                                    <button type="button" class="v2-search-selected__clear" id="v2-client-search-clear">&times;</button>
                                </div>
                                <a href="{{ route('partner.clients.create') }}" target="_blank" rel="noopener" class="v2-link">Créer un nouveau client</a>
                            </div>
                        </div>

                        {{-- Nouveau --}}
                        <div id="v2-new-client-block" {{ old('client_mode') === 'existing' ? 'hidden' : '' }}>
                            <div class="v2-grid v2-grid--2">
                                <div class="v2-field">
                                    <label class="v2-label" for="v2-client-first-name">Prénom <span class="v2-required">*</span></label>
                                    <input type="text" name="client_first_name" id="v2-client-first-name" class="v2-input" value="{{ old('client_first_name') }}" autocomplete="given-name">
                                </div>
                                <div class="v2-field">
                                    <label class="v2-label" for="v2-client-last-name">Nom <span class="v2-required">*</span></label>
                                    <input type="text" name="client_last_name" id="v2-client-last-name" class="v2-input" value="{{ old('client_last_name') }}" autocomplete="family-name">
                                </div>
                                <div class="v2-field">
                                    <label class="v2-label" for="v2-client-phone">Téléphone <span class="v2-required">*</span></label>
                                    <input type="text" name="client_phone" id="v2-client-phone" class="v2-input" value="{{ old('client_phone') }}" autocomplete="tel">
                                </div>
                                <div class="v2-field">
                                    <label class="v2-label" for="v2-client-email">Email</label>
                                    <input type="email" name="client_email" id="v2-client-email" class="v2-input" value="{{ old('client_email') }}" autocomplete="email">
                                </div>
                                <div class="v2-field">
                                    <label class="v2-label" for="v2-client-gender">Sexe</label>
                                    <select name="client_gender" id="v2-client-gender" class="v2-input">
                                        <option value="">Sélectionner…</option>
                                        <option value="male" {{ old('client_gender') === 'male' ? 'selected' : '' }}>Homme</option>
                                        <option value="female" {{ old('client_gender') === 'female' ? 'selected' : '' }}>Femme</option>
                                    </select>
                                </div>
                                <div class="v2-field">
                                    <label class="v2-label" for="v2-client-traveler-type">Type voyageur</label>
                                    <select name="client_traveler_type" id="v2-client-traveler-type" class="v2-input">
                                        <option value="adult" {{ old('client_traveler_type', 'adult') === 'adult' ? 'selected' : '' }}>Adulte</option>
                                        <option value="child" {{ old('client_traveler_type') === 'child' ? 'selected' : '' }}>Enfant</option>
                                        <option value="infant" {{ old('client_traveler_type') === 'infant' ? 'selected' : '' }}>Bébé</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="v2-divider"></div>

                        <div class="v2-card__subhead">
                            <h3 class="v2-subtitle">Accompagnants</h3>
                            <button type="button" class="v2-btn v2-btn--outline" id="v2-add-companion">
                                <i class="bx bx-user-plus"></i> Ajouter
                            </button>
                        </div>

                        <div id="v2-companions-container"></div>
                        <p id="v2-no-companions" class="v2-placeholder">Aucun accompagnant ajouté.</p>
                    </div>

                    <div class="v2-actions">
                        <button type="button" class="v2-btn v2-btn--secondary" data-v2-prev="1"><i class="bx bx-left-arrow-alt"></i> Précédent</button>
                        <button type="button" class="v2-btn v2-btn--primary" data-v2-next="3">Continuer <i class="bx bx-right-arrow-alt"></i></button>
                    </div>
                </section>

                {{-- Étape 3 : Chambres & Extras --}}
                <section class="v2-panel" data-v2-step="3" hidden>
                    <div class="v2-card">
                        <div class="v2-card__head">
                            <div>
                                <p class="v2-eyebrow">Étape 3 — Chambres & extras</p>
                                <h2 class="v2-card__title">Répartition et suppléments</h2>
                            </div>
                            <div class="v2-badges">
                                <span class="v2-badge" id="v2-badge-men">Hommes : 0</span>
                                <span class="v2-badge" id="v2-badge-women">Femmes : 0</span>
                                <span class="v2-badge" id="v2-badge-beds">Lits à couvrir : 0</span>
                            </div>
                        </div>

                        <div class="v2-rooming">
                            <div class="v2-rooming__toolbar">
                                <button type="button" class="v2-btn v2-btn--primary" id="v2-rooming-auto">Répartition auto</button>
                                <button type="button" class="v2-btn v2-btn--outline" id="v2-rooming-add-room">+ Chambre</button>
                                <button type="button" class="v2-btn v2-btn--ghost" id="v2-rooming-reset">Réinitialiser</button>
                            </div>
                            <div id="v2-rooming-container"></div>
                            <div class="v2-alert v2-alert--info" id="v2-rooming-info" hidden></div>
                        </div>
                    </div>

                    <div class="v2-card">
                        <div class="v2-card__head">
                            <div>
                                <p class="v2-eyebrow">Extras</p>
                                <h2 class="v2-card__title">Suppléments disponibles</h2>
                            </div>
                        </div>
                        <div id="v2-extras-container"></div>
                        <p class="v2-placeholder" id="v2-extras-empty">Aucun extra configuré.</p>
                    </div>

                    <div class="v2-actions">
                        <button type="button" class="v2-btn v2-btn--secondary" data-v2-prev="2"><i class="bx bx-left-arrow-alt"></i> Précédent</button>
                        <button type="button" class="v2-btn v2-btn--primary" data-v2-next="4">Continuer <i class="bx bx-right-arrow-alt"></i></button>
                    </div>
                </section>

                {{-- Étape 4 : Paiement --}}
                <section class="v2-panel" data-v2-step="4" hidden>
                    <div class="v2-card">
                        <div class="v2-card__head">
                            <div>
                                <p class="v2-eyebrow">Étape 4 — Paiement</p>
                                <h2 class="v2-card__title">Finaliser et confirmer</h2>
                            </div>
                        </div>

                        <div class="v2-grid v2-grid--2">
                            <div class="v2-field">
                                <label class="v2-label" for="v2-payment-type">Type de paiement</label>
                                <select name="payment_type" id="v2-payment-type" class="v2-input">
                                    <option value="">Sélectionner…</option>
                                    <option value="CASHPLUS" {{ old('payment_type') === 'CASHPLUS' ? 'selected' : '' }}>CashPlus</option>
                                    <option value="VIREMENT" {{ old('payment_type') === 'VIREMENT' ? 'selected' : '' }}>Virement</option>
                                    <option value="ESPECE" {{ old('payment_type') === 'ESPECE' ? 'selected' : '' }}>Espèce</option>
                                </select>
                            </div>
                            <div class="v2-field">
                                <label class="v2-label" for="v2-paid-amount">Montant payé</label>
                                <input type="number" name="paid_amount" id="v2-paid-amount" class="v2-input" value="{{ old('paid_amount', 0) }}" min="0" step="0.01">
                            </div>
                            <div class="v2-field v2-field--full">
                                <label class="v2-label" for="v2-notes">Notes</label>
                                <textarea name="notes" id="v2-notes" class="v2-input v2-input--textarea" rows="3" placeholder="Informations complémentaires…">{{ old('notes') }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="v2-actions v2-actions--final">
                        <button type="button" class="v2-btn v2-btn--secondary" data-v2-prev="3"><i class="bx bx-left-arrow-alt"></i> Précédent</button>
                        <div class="v2-actions__group">
                            <a href="{{ route('partner.reservations.index') }}" class="v2-btn v2-btn--ghost">Annuler</a>
                            <button type="submit" class="v2-btn v2-btn--primary v2-btn--lg">
                                <span>Confirmer la réservation</span>
                                <i class="bx bx-check"></i>
                            </button>
                        </div>
                    </div>
                </section>
            </main>

            {{-- Sidebar sticky résumé --}}
            <aside class="v2-sidebar">
                <div class="v2-sidebar__card">
                    <h3 class="v2-sidebar__title">Résumé dossier</h3>
                    <div class="v2-sidebar__progress">
                        <div class="v2-sidebar__progress-bar" id="v2-progress-bar" style="width: 25%"></div>
                    </div>
                    <p class="v2-sidebar__progress-label" id="v2-progress-label">Étape 1 sur 4</p>

                    <div class="v2-sidebar__section">
                        <div class="v2-sidebar__item">
                            <span>Prestation</span>
                            <strong id="v2-sidebar-trip">Aucune sélection</strong>
                        </div>
                        <div class="v2-sidebar__item">
                            <span>Départ</span>
                            <strong id="v2-sidebar-departure">—</strong>
                        </div>
                        <div class="v2-sidebar__item">
                            <span>Voyageurs</span>
                            <strong id="v2-sidebar-travelers">1</strong>
                        </div>
                        <div class="v2-sidebar__item">
                            <span>Chambres</span>
                            <strong id="v2-sidebar-rooms">—</strong>
                        </div>
                    </div>

                    <div class="v2-sidebar__divider"></div>

                    <div class="v2-sidebar__section">
                        <div class="v2-sidebar__item">
                            <span>Prix unitaire</span>
                            <strong id="v2-sidebar-unit-price">—</strong>
                        </div>
                        <div class="v2-sidebar__item">
                            <span>Réduction</span>
                            <strong id="v2-sidebar-discount">Aucune</strong>
                        </div>
                        <div class="v2-sidebar__item">
                            <span>Extras</span>
                            <strong id="v2-sidebar-extras">0 DH</strong>
                        </div>
                    </div>

                    <div class="v2-sidebar__divider"></div>

                    <div class="v2-sidebar__section v2-sidebar__section--total">
                        <div class="v2-sidebar__item v2-sidebar__item--total">
                            <span>Total provisoire</span>
                            <strong id="v2-sidebar-total">—</strong>
                        </div>
                        <div class="v2-sidebar__item">
                            <span>Payé</span>
                            <strong id="v2-sidebar-paid">0 DH</strong>
                        </div>
                        <div class="v2-sidebar__item">
                            <span>Reste</span>
                            <strong id="v2-sidebar-remaining">0 DH</strong>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </form>

    <script type="application/json" id="v2-extras-map">{!! json_encode($extrasByVoyage ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) !!}</script>
    <script type="application/json" id="v2-wp-voyage-map">{!! json_encode(($voyages ?? collect())->filter(fn ($v) => (int) ($v->wp_post_id ?? 0) > 0)->mapWithKeys(fn ($v) => [(string) $v->wp_post_id => (int) $v->id])->all(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) !!}</script>
</div>
@endsection

@push('script')
    <script>
        window.RESERVATION_CREATE_ENDPOINTS = {
            voyageDepartures: @json(route('partner.reservations.voyage-departures')),
            departureHotelsRooms: @json(route('partner.reservations.departure-hotels-rooms')),
            clientSearch: @json(route('partner.clients.search')),
        };
    </script>
    <script src="{{ asset('js/reservation-create-v2.js') . '?v=' . @filemtime(public_path('js/reservation-create-v2.js')) }}"></script>
@endpush

