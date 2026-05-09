@extends('layouts.master-ajinsafro')

@section('title', 'Fiche Hajj & Omra')

@section('content')
    <x-admin.page-header
        :title="$package->title"
        :subtitle="$package->short_description"
        :breadcrumbs="[
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Hajj & Omra', 'url' => route('admin.hajj-omra.index')],
            ['label' => 'Fiche offre'],
        ]"
    >
        <x-slot name="actions">
            <a href="{{ route('admin.hajj-omra.edit', $package) }}" class="aj-btn aj-btn-primary">
                <i class="bx bx-pencil"></i>
                <span>Modifier</span>
            </a>
        </x-slot>
    </x-admin.page-header>

    <div class="row g-4">
        <div class="col-lg-4">
            <x-admin.form-section title="Resume">
                <div class="d-flex flex-column gap-3">
                    <x-admin.image-thumb :src="$package->main_image_url" :alt="$package->title" size="lg" />
                    <div><strong>Type :</strong> {{ $package->type_label }}</div>
                    <div><strong>Statut :</strong> {{ $package->status_label }}</div>
                    <div><strong>Ville de depart :</strong> {{ $package->departure_city ?: '—' }}</div>
                    <div><strong>Destination :</strong> {{ $package->destination ?: '—' }}</div>
                    <div><strong>Prix a partir de :</strong> {{ $package->price_from_value !== null ? number_format($package->price_from_value, 0, ',', ' ') . ' ' . $package->currency : 'Sur demande' }}</div>
                    <div><strong>Places restantes :</strong> {{ $package->remaining_places }}</div>
                </div>
            </x-admin.form-section>
        </div>
        <div class="col-lg-8">
            <x-admin.form-section title="Description">
                <div class="mb-3"><strong>Description courte</strong><br>{{ $package->short_description ?: '—' }}</div>
                <div><strong>Description detaillee</strong><br>{!! nl2br(e($package->description ?: '—')) !!}</div>
            </x-admin.form-section>

            <x-admin.form-section title="Hotels et services">
                <div class="row g-3">
                    <div class="col-md-6"><strong>Hotel Makkah :</strong> {{ $package->makkah_hotel ?: '—' }}</div>
                    <div class="col-md-6"><strong>Distance Haram Makkah :</strong> {{ $package->makkah_haram_distance ?: '—' }}</div>
                    <div class="col-md-6"><strong>Hotel Madinah :</strong> {{ $package->madinah_hotel ?: '—' }}</div>
                    <div class="col-md-6"><strong>Distance Haram Madinah :</strong> {{ $package->madinah_haram_distance ?: '—' }}</div>
                    <div class="col-md-4"><strong>Transport inclus :</strong> {{ $package->transport_included ? 'Oui' : 'Non' }}</div>
                    <div class="col-md-4"><strong>Visa inclus :</strong> {{ $package->visa_included ? 'Oui' : 'Non' }}</div>
                    <div class="col-md-4"><strong>Encadrement inclus :</strong> {{ $package->guidance_included ? 'Oui' : 'Non' }}</div>
                </div>
            </x-admin.form-section>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <x-admin.form-section title="Prix par chambre">
                @if($package->roomPrices->isEmpty())
                    <p class="text-muted mb-0">Aucun tarif chambre renseigne.</p>
                @else
                    <div class="table-responsive">
                        <table class="table">
                            <thead><tr><th>Type</th><th>Prix</th><th>Stock</th></tr></thead>
                            <tbody>
                            @foreach($package->roomPrices as $roomPrice)
                                <tr>
                                    <td>{{ $roomPrice->room_type_label }}</td>
                                    <td>{{ number_format($roomPrice->price, 0, ',', ' ') }} {{ $package->currency }}</td>
                                    <td>{{ $roomPrice->stock }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-admin.form-section>
        </div>
        <div class="col-lg-6">
            <x-admin.form-section title="Departs">
                @if($package->departures->isEmpty())
                    <p class="text-muted mb-0">Aucun depart renseigne.</p>
                @else
                    <div class="table-responsive">
                        <table class="table">
                            <thead><tr><th>Depart</th><th>Retour</th><th>Statut</th><th>Places</th></tr></thead>
                            <tbody>
                            @foreach($package->departures as $departure)
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

    <x-admin.form-section title="Programme">
        @if($package->programDays->isEmpty())
            <p class="text-muted mb-0">Aucun jour programme.</p>
        @else
            <div class="row g-3">
                @foreach($package->programDays as $programDay)
                    <div class="col-md-6">
                        <div class="border rounded-3 p-3 h-100">
                            <div class="fw-bold mb-2">Jour {{ $programDay->day_number }} - {{ $programDay->title ?: 'Etape' }}</div>
                            <div class="text-muted small mb-2">{{ $programDay->city ?: 'Ville non renseignee' }}</div>
                            <div>{{ $programDay->description ?: '—' }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-admin.form-section>
@endsection
