@extends('layouts.master-ajinsafro')
@section('title')
    Fiche voyage – {{ $voyage->name }}
@endsection
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">{{ $voyage->name }}</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.circuits.voyages.index') }}">Voyages</a></li>
                        <li class="breadcrumb-item active">Fiche</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-12">
            <a href="{{ route('admin.circuits.voyages.edit', $voyage) }}" class="btn btn-primary waves-effect waves-light me-2">Modifier le voyage</a>
            <a href="{{ route('admin.circuits.voyages.index') }}" class="btn btn-secondary waves-effect">Retour à la liste</a>
        </div>
    </div>

    {{-- En-tête brochure --}}
    <div class="card mb-4">
        <div class="card-body">
            <h1 class="h3 mb-2">{{ $voyage->name }}</h1>
            @if($voyage->accroche)
                <p class="text-muted lead mb-2">{{ $voyage->accroche }}</p>
            @endif
            @if($voyage->destination)
                <p class="mb-1"><strong>Destination :</strong> {{ $voyage->destination }}</p>
            @endif
            @if($voyage->duration_text)
                <p class="mb-1"><strong>Durée :</strong> {{ $voyage->duration_text }}</p>
            @endif
            @if($voyage->min_people)
                <p class="mb-0"><strong>Minimum personnes :</strong> {{ $voyage->min_people }}</p>
            @endif
        </div>
    </div>

    {{-- Bloc Prix & Promotion --}}
    @if($voyage->price_from !== null || $voyage->old_price !== null)
        <div class="card mb-4">
            <div class="card-body">
                <h4 class="card-title mb-3">Prix & Promotion</h4>
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <p class="mb-1 font-size-18 fw-medium">À partir de {{ number_format($voyage->price_from ?? 0, 0, ',', ' ') }} {{ $voyage->currency_symbol }}</p>
                        @if($voyage->old_price && $voyage->old_price > ($voyage->price_from ?? 0))
                            <p class="text-muted mb-0">Valeur : {{ number_format($voyage->old_price, 0, ',', ' ') }} {{ $voyage->currency_symbol }}</p>
                        @endif
                    </div>
                    @if($voyage->discount_percent !== null && $voyage->discount_percent > 0)
                        <div class="col-md-6 text-md-end">
                            <span class="badge bg-danger font-size-14 me-2">Remise : {{ $voyage->discount_percent }} %</span>
                            <span class="badge bg-success font-size-14">Économie : {{ number_format($voyage->discount_amount, 0, ',', ' ') }} {{ $voyage->currency_symbol }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Départs --}}
    @if($voyage->departures->isNotEmpty())
        <div class="card mb-4">
            <div class="card-body">
                <h4 class="card-title mb-3">Départs</h4>
                @if($voyage->departure_policy)
                    <p class="text-muted small mb-3">{{ $voyage->departure_policy }}</p>
                @endif
                <div class="table-responsive">
                    <table class="table table-sm table-centered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($voyage->departures as $dep)
                                <tr>
                                    <td>{{ $dep->start_date->format('d/m/Y') }}</td>
                                    <td><span class="badge bg-{{ $dep->status === 'open' ? 'success' : ($dep->status === 'full' ? 'warning' : 'secondary') }}">{{ $dep->status_label }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- Programme jour par jour (brochure) --}}
    @if($voyage->programDays->isNotEmpty())
        <div class="card mb-4">
            <div class="card-body">
                <h4 class="card-title mb-4">Programme du voyage – Jour par jour</h4>
                <div class="row">
                    @foreach($voyage->programDays as $day)
                        <div class="col-12 mb-4">
                            <div class="card border h-100">
                                <div class="card-body">
                                    <h5 class="card-title mt-0">Jour {{ $day->day_number }} – {{ $day->title }}</h5>
                                    @if($day->city)
                                        <p class="text-muted mb-2"><i class="bx bx-map-pin font-size-12 me-1"></i> {{ $day->city }}</p>
                                    @endif
                                    <p class="mb-2">
                                        @if($day->day_label)
                                            <span class="badge bg-soft-primary">{{ $day->day_label_badge }}</span>
                                        @endif
                                        @if($day->nights)
                                            <span class="badge bg-soft-secondary">{{ $day->nights }} nuit(s)</span>
                                        @endif
                                    </p>
                                    @if($day->hasMealBreakfast() || $day->hasMealLunch() || $day->hasMealDinner())
                                        <p class="text-muted small mb-2">
                                            Repas inclus : @if($day->hasMealBreakfast()) Petit-déjeuner @endif @if($day->hasMealLunch()) Déjeuner @endif @if($day->hasMealDinner()) Dîner @endif
                                        </p>
                                    @endif
                                    @php $content = $day->content_for_display; $plain = strip_tags($content); $previewLen = 200; @endphp
                                    @if($content)
                                        @if(strlen($plain) > $previewLen)
                                            <div class="day-content-preview text-muted small mb-2">{{ Str::limit($plain, $previewLen) }}</div>
                                            <button type="button" class="btn btn-sm btn-soft-primary waves-effect waves-light" data-bs-toggle="collapse" data-bs-target="#dayContent{{ $day->id }}">Voir plus</button>
                                            <div class="collapse mt-2" id="dayContent{{ $day->id }}">
                                                <div class="program-html">{!! $content !!}</div>
                                            </div>
                                        @else
                                            <div class="program-html text-muted small">{!! $content !!}</div>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    @if($voyage->description)
        <div class="card mb-4">
            <div class="card-body">
                <h4 class="card-title mb-3">Description</h4>
                <div class="text-muted">{!! nl2br(e($voyage->description)) !!}</div>
            </div>
        </div>
    @endif
@endsection
@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
@push('style')
<style>
.program-html ul { padding-left: 1.25rem; }
.program-html ol { padding-left: 1.25rem; }
.program-html li { margin-bottom: 0.25rem; }
</style>
@endpush
