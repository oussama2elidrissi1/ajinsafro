@extends('layouts.admin-v6')

@push('styles')
    <link href="{{ URL::asset('css/admin-tour-transfers.css') }}" rel="stylesheet" type="text/css" />
@endpush

@section('title')
    Transferts des circuits
@endsection

@section('content')
    @php
        $currentFilters = ['q' => $search, 'etat' => $etat, 'page' => request('page')];
        $listUrl = route('admin.circuits.tour-transfers.index', array_filter($currentFilters));
    @endphp

    <div class="att-page">
        <div class="att-head">
            <div class="att-head__copy">
                <nav class="att-breadcrumb" aria-label="Fil d’Ariane">
                    <a href="{{ route('admin.dashboard') }}">Admin</a>
                    <span aria-hidden="true">/</span>
                    <a href="{{ route('admin.circuits.index') }}">Circuits</a>
                    <span aria-hidden="true">/</span>
                    <span class="is-current">Transferts</span>
                </nav>
                <h1>Transferts des circuits</h1>
                <p>Transfert aller (jour 1) : aéroport &rarr; hôtel. Transfert retour (dernier jour) : hôtel &rarr; aéroport.</p>
            </div>

            <div class="att-stats">
                <div class="att-stat">
                    <span class="att-stat__label">Circuits</span>
                    <span class="att-stat__value">{{ number_format($stats['total'], 0, ',', ' ') }}</span>
                </div>
                <div class="att-stat">
                    <span class="att-stat__label">Définis</span>
                    <span class="att-stat__value att-stat__value--ok">{{ number_format($stats['complet'], 0, ',', ' ') }}</span>
                </div>
                <div class="att-stat">
                    <span class="att-stat__label">À définir</span>
                    <span class="att-stat__value att-stat__value--todo">{{ number_format($stats['a_definir'], 0, ',', ' ') }}</span>
                </div>
            </div>
        </div>

        @if (session('success'))
            <div class="att-alert att-alert--ok" role="status">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="att-alert att-alert--error" role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (!empty($wpConnectionFailed))
            <div class="att-alert att-alert--warn" role="alert">
                <strong>Connexion WordPress indisponible.</strong> Vérifiez la configuration de la base WP.
            </div>
        @endif

        <div class="att-card">
            <form class="att-toolbar" method="get" action="{{ route('admin.circuits.tour-transfers.index') }}">
                <label class="att-search">
                    <span class="att-search__icon" aria-hidden="true"></span>
                    <input type="search" name="q" value="{{ $search }}" placeholder="Rechercher un circuit ou un ID…" aria-label="Rechercher un circuit ou un ID">
                </label>

                <label class="att-filter">
                    <span class="att-filter__label">État</span>
                    <select name="etat" data-att-auto>
                        <option value="tous" @selected($etat === 'tous')>Tous les circuits</option>
                        <option value="manquant" @selected($etat === 'manquant')>Aucun transfert défini</option>
                        <option value="partiel" @selected($etat === 'partiel')>Partiellement défini</option>
                        <option value="complet" @selected($etat === 'complet')>Aller et retour définis</option>
                    </select>
                </label>

                <button type="submit" class="att-btn att-btn--primary">Rechercher</button>

                @if ($search !== '' || $etat !== 'tous')
                    <a class="att-btn att-btn--ghost" href="{{ route('admin.circuits.tour-transfers.index') }}">Réinitialiser</a>
                @endif

                <span class="att-count">
                    @if ($search !== '' || $etat !== 'tous')
                        {{ number_format($tours->total(), 0, ',', ' ') }} sur {{ number_format($stats['total'], 0, ',', ' ') }} circuits
                    @else
                        {{ number_format($stats['total'], 0, ',', ' ') }} circuits
                    @endif
                </span>
            </form>

            {{-- Les cases des lignes pointent ici via l'attribut form : pas de
                 formulaire imbrique dans le tableau. --}}
            <form id="att-bulk-form" class="att-bulk" method="POST" action="{{ route('admin.circuits.tour-transfers.bulk') }}" hidden data-att-bulk>
                @csrf
                <div class="att-bulk__bar">
                    <span class="att-bulk__count" data-att-selection-label>0 circuit sélectionné</span>
                    <button type="button" class="att-btn att-btn--primary" data-att-bulk-open>Définir les transferts en lot</button>
                    <button type="button" class="att-btn att-btn--ghost" data-att-bulk-clear>Annuler la sélection</button>
                </div>

                <div class="att-bulk__panel" hidden data-att-bulk-panel>
                    <div class="att-fields">
                        <label class="att-field">
                            <span class="att-field__label">Aller &middot; point de prise en charge <b aria-hidden="true">*</b></span>
                            <input type="text" name="arrival_from_label" required placeholder="Aéroport Mohammed V">
                        </label>
                        <label class="att-field">
                            <span class="att-field__label">Aller &middot; destination <b aria-hidden="true">*</b></span>
                            <input type="text" name="arrival_to_label" required placeholder="Hôtel du circuit">
                        </label>
                        <label class="att-field">
                            <span class="att-field__label">Retour &middot; point de prise en charge</span>
                            <input type="text" name="departure_from_label" placeholder="Hôtel du circuit">
                        </label>
                        <label class="att-field">
                            <span class="att-field__label">Retour &middot; destination</span>
                            <input type="text" name="departure_to_label" placeholder="Aéroport de départ">
                        </label>
                    </div>
                    <div class="att-actions">
                        <button type="submit" class="att-btn att-btn--primary">Appliquer aux circuits sélectionnés</button>
                        <button type="button" class="att-btn att-btn--ghost" data-att-bulk-close>Annuler</button>
                        <span class="att-hint">Le retour reprend l’aller inversé si vous le laissez vide.</span>
                    </div>
                </div>
            </form>

            <div class="att-scroll">
                <div class="att-table">
                    <div class="att-row att-row--head">
                        <span class="att-cell att-cell--check">
                            <input type="checkbox" data-att-toggle-all aria-label="Tout sélectionner">
                        </span>
                        <span class="att-cell">ID</span>
                        <span class="att-cell">Titre du circuit</span>
                        <span class="att-cell">Transfert aller</span>
                        <span class="att-cell">Transfert retour</span>
                        <span class="att-cell att-cell--end">Actions</span>
                    </div>

                    @forelse($tours as $tour)
                        @php
                            $tr = $transfersByTour[$tour->ID] ?? ['arrival' => null, 'departure' => null, 'total' => 0, 'coverage' => 'manquant'];
                            $arrival = $tr['arrival'];
                            $departure = $tr['departure'];
                            $coverage = $tr['coverage'];
                            $isOpen = (int) $editId === (int) $tour->ID;
                            $leg = static function ($row) {
                                if (! $row) {
                                    return null;
                                }
                                $from = trim((string) $row->from_label);
                                $to = trim((string) $row->to_label);
                                if ('' === $from && '' === $to) {
                                    return null;
                                }

                                return ($from !== '' ? $from : 'Départ à préciser')
                                    . ' → '
                                    . ($to !== '' ? $to : 'Arrivée à préciser');
                            };
                            $allerLabel = $leg($arrival);
                            $retourLabel = $leg($departure);
                        @endphp

                        <div class="att-group" id="circuit-{{ $tour->ID }}">
                            <div class="att-row att-row--{{ $coverage }}">
                                <span class="att-cell att-cell--check">
                                    <input type="checkbox" form="att-bulk-form" name="tour_ids[]" value="{{ $tour->ID }}"
                                           data-att-row-check aria-label="Sélectionner le circuit {{ $tour->ID }}">
                                </span>

                                <span class="att-cell att-cell--id">{{ $tour->ID }}</span>

                                <span class="att-cell att-cell--title">
                                    <a class="att-title" href="{{ route('admin.circuits.voyages.edit', $tour->ID) }}">{{ $tour->post_title }}</a>
                                    @if (($tr['total'] ?? 0) > 2)
                                        {{-- Cette page n'edite que le premier transfert de chaque sens. --}}
                                        <span class="att-meta">{{ $tr['total'] }} transferts enregistrés &middot;
                                            <a href="{{ route('admin.circuits.voyages.edit', $tour->ID) }}?tab=flights">tout gérer dans le circuit</a>
                                        </span>
                                    @endif
                                </span>

                                <span class="att-cell att-leg {{ $allerLabel ? '' : 'is-missing' }}">{{ $allerLabel ?: 'Non défini' }}</span>
                                <span class="att-cell att-leg {{ $retourLabel ? '' : 'is-missing' }}">{{ $retourLabel ?: 'Non défini' }}</span>

                                <span class="att-cell att-cell--end">
                                    @if ($isOpen)
                                        <a class="att-btn att-btn--ghost" href="{{ $listUrl }}#circuit-{{ $tour->ID }}">Fermer</a>
                                    @else
                                        <a class="att-btn {{ 'manquant' === $coverage ? 'att-btn--primary' : 'att-btn--ghost' }}"
                                           href="{{ route('admin.circuits.tour-transfers.index', array_filter($currentFilters) + ['edit' => $tour->ID]) }}#circuit-{{ $tour->ID }}">
                                            {{ 'manquant' === $coverage ? 'Définir' : 'Modifier' }}
                                        </a>
                                    @endif
                                </span>
                            </div>

                            @if ($isOpen)
                                <form class="att-edit" method="POST" action="{{ route('admin.circuits.tour-transfers.update', $tour->ID) }}">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="q" value="{{ $search }}">
                                    <input type="hidden" name="etat" value="{{ $etat }}">
                                    <input type="hidden" name="page" value="{{ request('page') }}">

                                    <div class="att-fields">
                                        <label class="att-field">
                                            <span class="att-field__label">Aller &middot; point de prise en charge <b aria-hidden="true">*</b></span>
                                            <input type="text" name="arrival[from_label]" required
                                                   value="{{ old('arrival.from_label', $arrival?->from_label ?? '') }}"
                                                   placeholder="Aéroport Mohammed V">
                                        </label>
                                        <label class="att-field">
                                            <span class="att-field__label">Aller &middot; destination <b aria-hidden="true">*</b></span>
                                            <input type="text" name="arrival[to_label]" required
                                                   value="{{ old('arrival.to_label', $arrival?->to_label ?? '') }}"
                                                   placeholder="Hôtel du circuit">
                                        </label>
                                        <label class="att-field">
                                            <span class="att-field__label">Retour &middot; point de prise en charge</span>
                                            <input type="text" name="departure[from_label]"
                                                   value="{{ old('departure.from_label', $departure?->from_label ?? '') }}"
                                                   placeholder="Hôtel du circuit">
                                        </label>
                                        <label class="att-field">
                                            <span class="att-field__label">Retour &middot; destination</span>
                                            <input type="text" name="departure[to_label]"
                                                   value="{{ old('departure.to_label', $departure?->to_label ?? '') }}"
                                                   placeholder="Aéroport de départ">
                                        </label>
                                    </div>

                                    <div class="att-actions">
                                        <button type="submit" class="att-btn att-btn--primary">Enregistrer les transferts</button>
                                        <a class="att-btn att-btn--ghost" href="{{ $listUrl }}#circuit-{{ $tour->ID }}">Annuler</a>
                                        <span class="att-hint">Le retour reprend l’aller inversé si vous le laissez vide.</span>
                                    </div>
                                </form>
                            @endif
                        </div>
                    @empty
                        <div class="att-empty">
                            <h3>Aucun circuit ne correspond à cette recherche</h3>
                            <p>Vérifiez l’ID saisi ou changez le filtre d’état.</p>
                            <a class="att-btn att-btn--ghost" href="{{ route('admin.circuits.tour-transfers.index') }}">Réinitialiser</a>
                        </div>
                    @endforelse
                </div>
            </div>

            @if ($tours->total() > 0)
                <div class="att-foot">
                    <span class="att-foot__label">
                        Affichage de {{ number_format($tours->count(), 0, ',', ' ') }} circuit{{ $tours->count() > 1 ? 's' : '' }}
                        sur {{ number_format($tours->total(), 0, ',', ' ') }}
                        &middot; {{ number_format($stats['complet'], 0, ',', ' ') }} avec transferts,
                        {{ number_format($stats['a_definir'], 0, ',', ' ') }} à compléter
                    </span>
                    <span class="att-pager">
                        @if ($tours->onFirstPage())
                            <span class="att-pager__btn is-disabled" aria-disabled="true">Précédent</span>
                        @else
                            <a class="att-pager__btn" href="{{ $tours->previousPageUrl() }}" rel="prev">Précédent</a>
                        @endif

                        @if ($tours->hasMorePages())
                            <a class="att-pager__btn" href="{{ $tours->nextPageUrl() }}" rel="next">Suivant</a>
                        @else
                            <span class="att-pager__btn is-disabled" aria-disabled="true">Suivant</span>
                        @endif
                    </span>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ URL::asset('js/admin-tour-transfers.js') }}"></script>
@endpush
