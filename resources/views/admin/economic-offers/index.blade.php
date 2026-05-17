@extends('layouts.admin-v2')

@section('title', 'Formule Économique')

@section('content')
    <x-admin.page-header
        title="Formule Économique"
        subtitle="Pilotez les offres petit budget Ajinsafro depuis un espace unique."
        :breadcrumbs="[
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Produits & Services'],
            ['label' => 'Formule Économique'],
        ]"
    >
        <x-slot name="actions">
            <a href="{{ route('admin.economic-offers.requests.index') }}" class="aj-btn aj-btn-soft">
                <i class="bx bx-message-square-detail"></i>
                <span>Demandes</span>
            </a>
            <a href="{{ route('admin.economic-offers.create') }}" class="aj-btn aj-btn-primary">
                <i class="bx bx-plus"></i>
                <span>Nouvelle offre</span>
            </a>
        </x-slot>
    </x-admin.page-header>

    <x-admin.flash-messages />

    <x-admin.kpi-cards
        :kpis="[
            ['label' => 'Offres filtrees', 'value' => number_format($totals['offers'], 0, ',', ' '), 'icon' => 'bx bx-purchase-tag-alt', 'color' => '-blue', 'note' => 'Resultat courant'],
            ['label' => 'Publiees', 'value' => number_format($totals['published'], 0, ',', ' '), 'icon' => 'bx bx-badge-check', 'color' => '-green', 'note' => 'Visibles en front'],
            ['label' => 'Mises en avant', 'value' => number_format($totals['featured'], 0, ',', ' '), 'icon' => 'bx bx-star', 'color' => '-orange', 'note' => 'Hero / push'],
            ['label' => 'Demandes clients', 'value' => number_format($totals['requests'], 0, ',', ' '), 'icon' => 'bx bx-envelope-open', 'color' => '-violet', 'note' => 'Toutes offres'],
        ]"
    />

    <x-admin.filter-panel :action="route('admin.economic-offers.index')" :reset-url="route('admin.economic-offers.index')" grid-class="row g-3 align-items-end">
        <x-slot name="fields">
            <div class="col-md-3">
                <label class="form-label">Recherche</label>
                <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control" placeholder="Titre, destination, ville, reference">
            </div>
            <div class="col-md-2">
                <label class="form-label">Type</label>
                <select name="offer_type" class="form-select">
                    <option value="">Tous</option>
                    @foreach($typeOptions as $value => $label)
                        <option value="{{ $value }}" @selected($filters['offer_type'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Destination</label>
                <input type="text" name="destination" value="{{ $filters['destination'] }}" class="form-control" placeholder="Ville / pays">
            </div>
            <div class="col-md-2">
                <label class="form-label">Ville de depart</label>
                <input type="text" name="departure_city" value="{{ $filters['departure_city'] }}" class="form-control" placeholder="Casablanca">
            </div>
            <div class="col-md-1">
                <label class="form-label">Budget</label>
                <input type="number" name="budget" value="{{ $filters['budget'] }}" class="form-control" placeholder="Max">
            </div>
            <div class="col-md-2">
                <label class="form-label">Statut</label>
                <select name="status" class="form-select">
                    <option value="">Tous</option>
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Date depart</label>
                <input type="date" name="departure_date" value="{{ $filters['departure_date'] }}" class="form-control">
            </div>
            <div class="col-md-2">
                <label class="form-label">Mise en avant</label>
                <select name="featured" class="form-select">
                    <option value="">Toutes</option>
                    <option value="1" @selected($filters['featured'] === '1')>Oui</option>
                </select>
            </div>
        </x-slot>
    </x-admin.filter-panel>

    <section class="aj-panel">
        @if($offers->isEmpty())
            <x-admin.empty-state
                title="Aucune offre économique"
                message="Créez une première offre puis ajoutez ses départs, tarifs et médias."
                :action-url="route('admin.economic-offers.create')"
                action-label="Créer une offre"
            />
        @else
            <div class="table-responsive" style="overflow-x:auto;">
                <table class="aj-table" style="width:100%;border-collapse:separate;border-spacing:0;">
                    <thead>
                    <tr>
                        <th>Image</th>
                        <th>Titre</th>
                        <th>Type</th>
                        <th>Destination</th>
                        <th>Ville de depart</th>
                        <th>Prix a partir de</th>
                        <th>Ancien prix</th>
                        <th>Date depart</th>
                        <th>Places</th>
                        <th>Statut</th>
                        <th>Feature</th>
                        <th class="text-end">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($offers as $offer)
                        @php
                            $nextDeparture = $offer->resolveUpcomingDeparture();
                        @endphp
                        <tr>
                            <td><x-admin.image-thumb :src="$offer->main_image_url ?: $offer->fallback_image_url" :alt="$offer->title" size="sm" /></td>
                            <td>
                                <div style="font-weight:800;color:#102340;">{{ $offer->title }}</div>
                                <div style="font-size:12px;font-weight:700;color:#7a879a;">{{ $offer->internal_reference ?: 'Sans reference' }}</div>
                            </td>
                            <td><x-admin.badge type="info" :label="$offer->type_label" /></td>
                            <td>{{ $offer->destination ?: '—' }}</td>
                            <td>{{ $offer->departure_city ?: '—' }}</td>
                            <td>
                                @if($offer->price_from_value !== null)
                                    <strong>{{ number_format($offer->price_from_value, 0, ',', ' ') }} {{ $offer->currency }}</strong>
                                @else
                                    <span class="text-muted">Sur demande</span>
                                @endif
                            </td>
                            <td>
                                @if($offer->old_price !== null)
                                    <span style="text-decoration:line-through;color:#7a879a;">{{ number_format((float) $offer->old_price, 0, ',', ' ') }} {{ $offer->currency }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ $nextDeparture?->departure_date?->format('d/m/Y') ?? ($offer->departure_date?->format('d/m/Y') ?: '—') }}</td>
                            <td>{{ $offer->remaining_places }}</td>
                            <td>
                                <x-admin.badge
                                    :type="match($offer->status){
                                        'published' => 'success',
                                        'full' => 'warning',
                                        'expired' => 'danger',
                                        default => 'neutral'
                                    }"
                                    :label="$offer->status_label"
                                />
                            </td>
                            <td>{!! $offer->is_featured ? '<span class="text-success fw-bold">Oui</span>' : '<span class="text-muted">Non</span>' !!}</td>
                            <td class="text-end">
                                <div class="aj-actions" style="display:flex;justify-content:flex-end;gap:8px;flex-wrap:wrap;">
                                    <a href="{{ route('admin.economic-offers.show', $offer) }}" class="aj-icon-btn" title="Voir" style="width:36px;height:36px;display:inline-flex;align-items:center;justify-content:center;border-radius:12px;border:1px solid var(--ajp-line);background:#fff;color:#31435c;text-decoration:none;">
                                        <i class="bx bx-show"></i>
                                    </a>
                                    <a href="{{ route('admin.economic-offers.edit', $offer) }}" class="aj-icon-btn" title="Modifier" style="width:36px;height:36px;display:inline-flex;align-items:center;justify-content:center;border-radius:12px;border:1px solid var(--ajp-line);background:#fff;color:#31435c;text-decoration:none;">
                                        <i class="bx bx-pencil"></i>
                                    </a>
                                    <form action="{{ route('admin.economic-offers.destroy', $offer) }}" method="POST" onsubmit="return confirm('Supprimer cette offre ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="aj-icon-btn -danger" title="Supprimer" style="width:36px;height:36px;display:inline-flex;align-items:center;justify-content:center;border-radius:12px;border:1px solid var(--ajp-line);background:#fff;color:#31435c;text-decoration:none;">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <x-admin.pagination-footer :paginator="$offers" />
        @endif
    </section>
@endsection
