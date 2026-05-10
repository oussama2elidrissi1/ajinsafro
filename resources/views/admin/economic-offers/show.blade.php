@extends('layouts.admin-v2')

@section('title', 'Fiche Formule Économique')

@section('content')
    <x-admin.page-header
        :title="$offer->title"
        :subtitle="$offer->short_description ?: 'Offre économique Ajinsafro'"
        :breadcrumbs="[
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Formule Économique', 'url' => route('admin.economic-offers.index')],
            ['label' => 'Fiche offre'],
        ]"
    >
        <x-slot name="actions">
            <a href="{{ route('admin.economic-offers.edit', $offer) }}" class="aj-btn aj-btn-primary">
                <i class="bx bx-pencil"></i>
                <span>Modifier</span>
            </a>
        </x-slot>
    </x-admin.page-header>

    <div class="row g-4">
        <div class="col-lg-4">
            <x-admin.form-section title="Résumé">
                <div class="d-flex flex-column gap-3">
                    <x-admin.image-thumb :src="$offer->main_image_url ?: $offer->fallback_image_url" :alt="$offer->title" size="lg" />
                    <div><strong>Type :</strong> {{ $offer->type_label }}</div>
                    <div><strong>Catégorie :</strong> {{ $offer->category_label }}</div>
                    <div><strong>Statut :</strong> {{ $offer->status_label }}</div>
                    <div><strong>Disponibilité :</strong> {{ $offer->availability_label }}</div>
                    <div><strong>Ville de départ :</strong> {{ $offer->departure_city ?: '—' }}</div>
                    <div><strong>Destination :</strong> {{ $offer->destination ?: '—' }}</div>
                    <div><strong>Prix à partir de :</strong> {{ $offer->price_from_value !== null ? number_format($offer->price_from_value, 0, ',', ' ') . ' ' . $offer->currency : 'Sur demande' }}</div>
                    <div><strong>Places restantes :</strong> {{ $offer->remaining_places }}</div>
                </div>
            </x-admin.form-section>
        </div>
        <div class="col-lg-8">
            <x-admin.form-section title="Présentation">
                <div class="mb-3"><strong>Description courte</strong><br>{{ $offer->short_description ?: '—' }}</div>
                <div><strong>Description détaillée</strong><br>{!! nl2br(e($offer->description ?: '—')) !!}</div>
            </x-admin.form-section>

            <x-admin.form-section title="Prix et conditions">
                <div class="row g-3">
                    <div class="col-md-4"><strong>Ancien prix :</strong> {{ $offer->old_price !== null ? number_format((float) $offer->old_price, 0, ',', ' ') . ' ' . $offer->currency : '—' }}</div>
                    <div class="col-md-4"><strong>Type de prix :</strong> {{ \App\Models\EconomicOffer::priceTypeOptions()[$offer->price_type] ?? '—' }}</div>
                    <div class="col-md-4"><strong>Acompte :</strong> {{ $offer->deposit_amount !== null ? number_format((float) $offer->deposit_amount, 0, ',', ' ') . ' ' . $offer->currency : '—' }}</div>
                    <div class="col-md-6"><strong>Ville d arrivée :</strong> {{ $offer->arrival_city ?: '—' }}</div>
                    <div class="col-md-6"><strong>Zone / adresse :</strong> {{ $offer->address_zone ?: '—' }}</div>
                </div>
            </x-admin.form-section>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <x-admin.form-section title="Prix variables">
                @if($offer->prices->isEmpty())
                    <p class="text-muted mb-0">Aucune ligne de prix renseignée.</p>
                @else
                    <div class="table-responsive">
                        <table class="table">
                            <thead><tr><th>Libellé</th><th>Type</th><th>Prix</th><th>Stock</th></tr></thead>
                            <tbody>
                            @foreach($offer->prices as $price)
                                <tr>
                                    <td>{{ $price->label }}</td>
                                    <td>{{ $price->type ?: '—' }}</td>
                                    <td>{{ number_format((float) $price->price, 0, ',', ' ') }} {{ $offer->currency }}</td>
                                    <td>{{ $price->stock }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-admin.form-section>
        </div>
        <div class="col-lg-6">
            <x-admin.form-section title="Départs">
                @if($offer->departures->isEmpty())
                    <p class="text-muted mb-0">Aucun départ renseigné.</p>
                @else
                    <div class="table-responsive">
                        <table class="table">
                            <thead><tr><th>Départ</th><th>Retour</th><th>Statut</th><th>Places</th></tr></thead>
                            <tbody>
                            @foreach($offer->departures as $departure)
                                <tr>
                                    <td>{{ $departure->departure_date?->format('d/m/Y') }}</td>
                                    <td>{{ $departure->return_date?->format('d/m/Y') ?: '—' }}</td>
                                    <td>{{ $departure->status_label }}</td>
                                    <td>{{ $departure->remaining_places }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-admin.form-section>
        </div>
    </div>

    <x-admin.form-section title="Demandes reçues">
        @if($offer->requests->isEmpty())
            <p class="text-muted mb-0">Aucune demande client liée à cette offre.</p>
        @else
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Date</th><th>Client</th><th>Statut</th><th></th></tr></thead>
                    <tbody>
                    @foreach($offer->requests->take(10) as $requestItem)
                        <tr>
                            <td>{{ $requestItem->created_at?->format('d/m/Y H:i') }}</td>
                            <td>{{ $requestItem->full_name }}<br><small class="text-muted">{{ $requestItem->phone }}</small></td>
                            <td>{{ $requestItem->status_label }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.economic-offers.requests.show', $requestItem) }}" class="aj-btn aj-btn-soft btn-sm">Voir</a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-admin.form-section>
@endsection
