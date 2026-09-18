@extends('layouts.admin-v6')

@push('styles')
    <link href="{{ URL::asset('css/admin-economic-offers.css') }}" rel="stylesheet" type="text/css" />
@endpush

@section('title', 'Formule économique')

@section('content')
    @php
        $currency = 'DH';
        $money = static function ($value) use ($currency) {
            if ($value === null || $value === '' || ! is_numeric($value)) {
                return null;
            }

            return number_format((float) $value, 0, ',', ' ') . ' ' . $currency;
        };

        $firstDeparture = static function ($offer) {
            if (! empty($offer->departure_date)) {
                return $offer->departure_date;
            }

            return optional($offer->departures->firstWhere(fn ($d) => ! empty($d->departure_date)))->departure_date;
        };

        // Meme definition que le filtre SQL du controleur.
        $isIncomplete = static function ($offer) use ($firstDeparture) {
            return blank($offer->internal_reference)
                || blank($offer->destination)
                || blank($offer->departure_city)
                || $offer->price_from_value === null
                || blank($firstDeparture($offer));
        };

        $resetUrl = route('admin.economic-offers.index');
        $incompleteUrl = route('admin.economic-offers.index', ['incomplete' => 'oui']);
    @endphp

    <x-admin.page-header
        title="Formule économique"
        subtitle="Pilotez les offres petit budget Ajinsafro depuis un espace unique."
        :breadcrumbs="[
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Produits & Services'],
            ['label' => 'Formule économique'],
        ]"
    >
        <x-slot name="actions">
            <a href="{{ route('admin.economic-offers.requests.index') }}" class="aj-btn aj-btn-soft">
                <i class="bx bx-message-square-detail"></i>
                <span>Demandes clients</span>
            </a>
            <a href="{{ route('admin.economic-offers.create') }}" class="aj-btn aj-btn-primary">
                <i class="bx bx-plus"></i>
                <span>Nouvelle offre</span>
            </a>
        </x-slot>
    </x-admin.page-header>

    <div class="aeo-page">
        @if (session('success'))
            <div class="aeo-alert aeo-alert--ok" role="status">{{ session('success') }}</div>
        @endif

        <div class="aeo-stats">
            <div class="aeo-stat aeo-stat--results">
                <span class="aeo-stat__label">Résultats</span>
                <span class="aeo-stat__value">{{ number_format($totals['results'], 0, ',', ' ') }}</span>
                <span class="aeo-stat__hint">filtres courants</span>
            </div>
            <div class="aeo-stat aeo-stat--published">
                <span class="aeo-stat__label">Publiées</span>
                <span class="aeo-stat__value">{{ number_format($totals['published'], 0, ',', ' ') }}</span>
                <span class="aeo-stat__hint">visibles en front</span>
            </div>
            <div class="aeo-stat aeo-stat--drafts">
                <span class="aeo-stat__label">Brouillons</span>
                <span class="aeo-stat__value">{{ number_format($totals['drafts'], 0, ',', ' ') }}</span>
                <span class="aeo-stat__hint">non visibles</span>
            </div>
            <div class="aeo-stat aeo-stat--featured">
                <span class="aeo-stat__label">Mises en avant</span>
                <span class="aeo-stat__value">{{ number_format($totals['featured'], 0, ',', ' ') }}</span>
                <span class="aeo-stat__hint">hero et push</span>
            </div>
            <div class="aeo-stat aeo-stat--requests">
                <span class="aeo-stat__label">Demandes</span>
                <span class="aeo-stat__value">{{ number_format($totals['requests'], 0, ',', ' ') }}</span>
                <span class="aeo-stat__hint">toutes offres</span>
            </div>
        </div>

        <form class="aeo-filters" method="get" action="{{ $resetUrl }}">
            <div class="aeo-filters__grid">
                <label class="aeo-field">
                    <span class="aeo-field__label">Recherche</span>
                    <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Titre ou référence…">
                </label>

                <label class="aeo-field">
                    <span class="aeo-field__label">Type</span>
                    <select name="offer_type" data-aeo-auto>
                        <option value="">Tous les types</option>
                        @foreach ($typeOptions as $value => $label)
                            <option value="{{ $value }}" @selected($filters['offer_type'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="aeo-field">
                    <span class="aeo-field__label">Ville de départ</span>
                    <select name="departure_city" data-aeo-auto>
                        <option value="">Toutes les villes</option>
                        @foreach ($cityOptions as $city)
                            <option value="{{ $city }}" @selected($filters['departure_city'] === $city)>{{ $city }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="aeo-field">
                    <span class="aeo-field__label">Budget max (DH)</span>
                    <input type="number" name="budget" min="0" step="100" value="{{ $filters['budget'] }}" placeholder="Sans limite">
                </label>

                <label class="aeo-field">
                    <span class="aeo-field__label">Statut</span>
                    <select name="status" data-aeo-auto>
                        <option value="">Tous les statuts</option>
                        @foreach ($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="aeo-field">
                    <span class="aeo-field__label">Mise en avant</span>
                    <select name="featured" data-aeo-auto>
                        <option value="">Toutes</option>
                        <option value="oui" @selected(in_array($filters['featured'], ['1', 'oui'], true))>Mises en avant</option>
                        <option value="non" @selected($filters['featured'] === 'non')>Non mises en avant</option>
                    </select>
                </label>
            </div>

            <div class="aeo-filters__row">
                <label class="aeo-check">
                    <input type="checkbox" name="incomplete" value="oui"
                           @checked(in_array($filters['incomplete'], ['1', 'oui'], true)) data-aeo-auto>
                    <span>Uniquement les fiches incomplètes</span>
                </label>

                <div class="aeo-filters__actions">
                    <button type="submit" class="aeo-btn aeo-btn--primary">Filtrer</button>
                    <a class="aeo-btn aeo-btn--ghost" href="{{ $resetUrl }}">Réinitialiser les filtres</a>
                </div>
            </div>
        </form>

        @if ($totals['incomplete'] > 0)
            <div class="aeo-warning" role="status">
                <span class="aeo-warning__text">
                    {{ $totals['incomplete'] }}
                    {{ $totals['incomplete'] > 1 ? 'fiches sont incomplètes' : 'fiche est incomplète' }} :
                    référence, destination, tarif ou date de départ manquants.
                    Ces offres ne peuvent pas être publiées en l’état.
                </span>
                <a class="aeo-btn aeo-btn--warn" href="{{ $incompleteUrl }}">Voir ces fiches</a>
            </div>
        @endif

        <div class="aeo-card">
            <div class="aeo-scroll">
                <div class="aeo-table">
                    <div class="aeo-row aeo-row--head">
                        <span class="aeo-cell">Offre</span>
                        <span class="aeo-cell">Type</span>
                        <span class="aeo-cell">Itinéraire</span>
                        <span class="aeo-cell">Tarif</span>
                        <span class="aeo-cell">Départ</span>
                        <span class="aeo-cell">État</span>
                        <span class="aeo-cell aeo-cell--end">Actions</span>
                    </div>

                    @forelse ($offers as $offer)
                        @php
                            $incomplete = $isIncomplete($offer);
                            $price = $money($offer->price_from_value);
                            $old = $money($offer->old_price);
                            $discount = ($offer->old_price && $offer->price_from_value && $offer->old_price > 0)
                                ? (int) round((1 - $offer->price_from_value / $offer->old_price) * 100)
                                : 0;
                            $departureDate = $firstDeparture($offer);
                            $seats = (int) $offer->remaining_places;
                            $published = $offer->status === \App\Models\EconomicOffer::STATUS_PUBLISHED;
                        @endphp

                        <div class="aeo-row {{ $incomplete ? 'is-incomplete' : '' }}">
                            <span class="aeo-cell aeo-offer">
                                @if ($offer->main_image_url)
                                    <img class="aeo-offer__thumb" src="{{ $offer->main_image_url }}" alt="" loading="lazy">
                                @else
                                    <span class="aeo-offer__thumb aeo-offer__thumb--empty" aria-hidden="true">IMG</span>
                                @endif
                                <span class="aeo-offer__copy">
                                    <a class="aeo-offer__title" href="{{ route('admin.economic-offers.edit', $offer) }}">{{ $offer->title }}</a>
                                    <span class="aeo-offer__ref {{ blank($offer->internal_reference) ? 'is-missing' : '' }}">
                                        {{ $offer->internal_reference ?: 'Référence manquante' }}
                                    </span>
                                </span>
                            </span>

                            <span class="aeo-cell">
                                <span class="aeo-chip">{{ $offer->type_label }}</span>
                            </span>

                            <span class="aeo-cell">
                                <span class="aeo-strong {{ blank($offer->destination) ? 'is-missing' : '' }}">
                                    {{ $offer->destination ?: 'À compléter' }}
                                </span>
                                <span class="aeo-sub {{ blank($offer->departure_city) ? 'is-missing' : '' }}">
                                    {{ $offer->departure_city ? 'Départ de ' . $offer->departure_city : 'Ville de départ à définir' }}
                                </span>
                            </span>

                            <span class="aeo-cell">
                                <span class="aeo-price {{ $price ? '' : 'is-missing' }}">{{ $price ?: 'Sur demande' }}</span>
                                <span class="aeo-sub">
                                    @if ($old && $discount > 0)
                                        {{ $old }} &middot; &minus;{{ $discount }}%
                                    @elseif ($old)
                                        {{ $old }}
                                    @else
                                        Pas de remise
                                    @endif
                                </span>
                            </span>

                            <span class="aeo-cell">
                                <span class="aeo-strong {{ $departureDate ? '' : 'is-missing' }}">
                                    {{ $departureDate ? \Illuminate\Support\Carbon::parse($departureDate)->format('d/m/Y') : 'Date à définir' }}
                                </span>
                                <span class="aeo-sub {{ $seats > 0 ? '' : 'is-missing' }}">
                                    {{ $seats > 0 ? $seats . ($seats > 1 ? ' places' : ' place') : 'Aucune place' }}
                                </span>
                            </span>

                            <span class="aeo-cell aeo-state">
                                <form method="POST" action="{{ route('admin.economic-offers.toggle-status', $offer) }}">
                                    @csrf
                                    <button type="submit" class="aeo-status {{ $published ? 'is-published' : 'is-draft' }}"
                                            title="{{ $published ? 'Repasser en brouillon' : 'Publier cette offre' }}">
                                        {{ $offer->status_label }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.economic-offers.toggle-featured', $offer) }}">
                                    @csrf
                                    <button type="submit" class="aeo-star {{ $offer->is_featured ? 'is-on' : '' }}"
                                            title="{{ $offer->is_featured ? 'Retirer la mise en avant' : 'Mettre en avant' }}"
                                            aria-pressed="{{ $offer->is_featured ? 'true' : 'false' }}">
                                        <span aria-hidden="true">&#9733;</span>
                                        <span class="aeo-sr">Mise en avant</span>
                                    </button>
                                </form>
                            </span>

                            <span class="aeo-cell aeo-cell--end aeo-actions">
                                <a class="aeo-icon" href="{{ route('admin.economic-offers.show', $offer) }}" title="Voir">
                                    <i class="bx bx-show" aria-hidden="true"></i><span class="aeo-sr">Voir</span>
                                </a>
                                <a class="aeo-icon" href="{{ route('admin.economic-offers.edit', $offer) }}" title="Modifier">
                                    <i class="bx bx-edit-alt" aria-hidden="true"></i><span class="aeo-sr">Modifier</span>
                                </a>
                                <form method="POST" action="{{ route('admin.economic-offers.destroy', $offer) }}"
                                      onsubmit="return confirm('Supprimer cette offre ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="aeo-icon aeo-icon--danger" title="Supprimer">
                                        <i class="bx bx-trash" aria-hidden="true"></i><span class="aeo-sr">Supprimer</span>
                                    </button>
                                </form>
                            </span>
                        </div>
                    @empty
                        <div class="aeo-empty">
                            <h3>Aucune offre ne correspond à ces filtres</h3>
                            <p>Élargissez le budget, le type ou le statut pour retrouver vos offres.</p>
                            <a class="aeo-btn aeo-btn--ghost" href="{{ $resetUrl }}">Réinitialiser</a>
                        </div>
                    @endforelse
                </div>
            </div>

            @if ($offers->total() > 0)
                <div class="aeo-foot">
                    <span class="aeo-foot__label">
                        Affichage de {{ number_format($offers->count(), 0, ',', ' ') }}
                        offre{{ $offers->count() > 1 ? 's' : '' }}
                        sur {{ number_format($offers->total(), 0, ',', ' ') }}
                    </span>
                    <span class="aeo-pager">
                        @if ($offers->onFirstPage())
                            <span class="aeo-pager__btn is-disabled" aria-disabled="true">Précédent</span>
                        @else
                            <a class="aeo-pager__btn" href="{{ $offers->previousPageUrl() }}" rel="prev">Précédent</a>
                        @endif

                        @if ($offers->hasMorePages())
                            <a class="aeo-pager__btn" href="{{ $offers->nextPageUrl() }}" rel="next">Suivant</a>
                        @else
                            <span class="aeo-pager__btn is-disabled" aria-disabled="true">Suivant</span>
                        @endif
                    </span>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Les listes et la case relancent la recherche sans passer par le bouton.
        document.addEventListener('change', function (event) {
            var control = event.target.closest('[data-aeo-auto]');
            if (!control) {
                return;
            }
            var form = control.closest('form');
            if (form) {
                form.submit();
            }
        });
    </script>
@endpush
