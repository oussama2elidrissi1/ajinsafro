@extends('layouts.admin-v6')

@section('title', 'Fiche Formule Ã‰conomique')

@section('content')
    <x-admin.page-header
        :title="$offer->title"
        :subtitle="$offer->short_description ?: 'Offre Ã©conomique Ajinsafro'"
        :breadcrumbs="[
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Formule Ã‰conomique', 'url' => route('admin.economic-offers.index')],
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
            <x-admin.form-section title="RÃ©sumÃ©">
                <div class="d-flex flex-column gap-3">
                    <x-admin.image-thumb :src="$offer->main_image_url ?: $offer->fallback_image_url" :alt="$offer->title" size="lg" />
                    <div><strong>Type :</strong> {{ $offer->type_label }}</div>
                    <div><strong>CatÃ©gorie :</strong> {{ $offer->category_label }}</div>
                    <div><strong>Statut :</strong> {{ $offer->status_label }}</div>
                    <div><strong>DisponibilitÃ© :</strong> {{ $offer->availability_label }}</div>
                    <div><strong>Ville de dÃ©part :</strong> {{ $offer->departure_city ?: 'â€”' }}</div>
                    <div><strong>Destination :</strong> {{ $offer->destination ?: 'â€”' }}</div>
                    <div><strong>Prix Ã  partir de :</strong> {{ $offer->price_from_value !== null ? number_format($offer->price_from_value, 0, ',', ' ') . ' ' . $offer->currency : 'Sur demande' }}</div>
                    <div><strong>Places restantes :</strong> {{ $offer->remaining_places }}</div>
                </div>
            </x-admin.form-section>
        </div>
        <div class="col-lg-8">
            <x-admin.form-section title="PrÃ©sentation">
                <div class="mb-3"><strong>Description courte</strong><br>{{ $offer->short_description ?: 'â€”' }}</div>
                <div><strong>Description dÃ©taillÃ©e</strong><br>{!! nl2br(e($offer->description ?: 'â€”')) !!}</div>
            </x-admin.form-section>

            <x-admin.form-section title="Prix et conditions">
                <div class="row g-3">
                    <div class="col-md-4"><strong>Ancien prix :</strong> {{ $offer->old_price !== null ? number_format((float) $offer->old_price, 0, ',', ' ') . ' ' . $offer->currency : 'â€”' }}</div>
                    <div class="col-md-4"><strong>Type de prix :</strong> {{ \App\Models\EconomicOffer::priceTypeOptions()[$offer->price_type] ?? 'â€”' }}</div>
                    <div class="col-md-4"><strong>Acompte :</strong> {{ $offer->deposit_amount !== null ? number_format((float) $offer->deposit_amount, 0, ',', ' ') . ' ' . $offer->currency : 'â€”' }}</div>
                    <div class="col-md-6"><strong>Ville d arrivÃ©e :</strong> {{ $offer->arrival_city ?: 'â€”' }}</div>
                    <div class="col-md-6"><strong>Zone / adresse :</strong> {{ $offer->address_zone ?: 'â€”' }}</div>
                </div>
            </x-admin.form-section>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <x-admin.form-section title="Prix variables">
                @if($offer->prices->isEmpty())
                    <p class="text-muted mb-0">Aucune ligne de prix renseignÃ©e.</p>
                @else
                    <div class="table-responsive">
                        <table class="table">
                            <thead><tr><th>LibellÃ©</th><th>Type</th><th>Prix</th><th>Stock</th></tr></thead>
                            <tbody>
                            @foreach($offer->prices as $price)
                                <tr>
                                    <td>{{ $price->label }}</td>
                                    <td>{{ $price->type ?: 'â€”' }}</td>
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
            <x-admin.form-section title="DÃ©parts">
                @if($offer->departures->isEmpty())
                    <p class="text-muted mb-0">Aucun dÃ©part renseignÃ©.</p>
                @else
                    <div class="table-responsive">
                        <table class="table">
                            <thead><tr><th>DÃ©part</th><th>Retour</th><th>Statut</th><th>Places</th></tr></thead>
                            <tbody>
                            @foreach($offer->departures as $departure)
                                <tr>
                                    <td>{{ $departure->departure_date?->format('d/m/Y') }}</td>
                                    <td>{{ $departure->return_date?->format('d/m/Y') ?: 'â€”' }}</td>
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

    <x-admin.form-section title="Demandes reÃ§ues">
        @if($offer->requests->isEmpty())
            <p class="text-muted mb-0">Aucune demande client liÃ©e Ã  cette offre.</p>
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

