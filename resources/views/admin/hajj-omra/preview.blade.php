@extends('layouts.admin-v6')

@section('title', 'Aperçu de l\'offre')

@php
    $isAr = $locale === 'ar';
    require_once base_path('wp-plugin/ajinsafro-traveler-home/includes/hajj-omra-commercial-table.php');
    $commercial = app(\App\Services\HajjOmra\HajjOmraCommercialPresenter::class)->package($package);
    // Un helper unique : version arabe si demandee et saisie, sinon repli francais.
    $t = fn ($model, $field) => $model->localized($field, $locale);
    $money = fn ($value) => number_format((float) $value, 2, ',', ' ').' '.$package->currency;

    $labels = $isAr ? [
        'details' => 'التفاصيل',
        'program' => 'البرنامج',
        'prices' => 'الأسعار',
        'services' => 'الخدمات المشمولة وغير المشمولة',
        'housing' => 'الإقامة',
        'from' => 'ابتداءً من',
        'places' => 'الأماكن المتاحة',
        'duration' => 'المدة',
        'departures' => 'مواعيد الانطلاق',
        'included' => 'الخدمات المشمولة',
        'excluded' => 'الخدمات غير المشمولة',
        'conditions' => 'شروط الحجز',
        'documents' => 'الوثائق المطلوبة',
        'day' => 'اليوم',
        'save' => 'توفير',
    ] : [
        'details' => 'Détails',
        'program' => 'Programme',
        'prices' => 'Tarifs',
        'services' => 'Inclus / Non inclus',
        'housing' => 'Hébergement',
        'from' => 'À partir de',
        'places' => 'Places disponibles',
        'duration' => 'Durée',
        'departures' => 'Départs',
        'included' => 'Ce qui est inclus',
        'excluded' => 'Ce qui n\'est pas inclus',
        'conditions' => 'Conditions de réservation',
        'documents' => 'Documents nécessaires',
        'day' => 'Jour',
        'save' => 'Économie',
    ];
@endphp

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h4 class="mb-1">Aperçu de l'offre</h4>
            <p class="text-muted small mb-0">Rendu commercial tel qu'il sera présenté au client.</p>
        </div>
        <div class="d-flex gap-2">
            <div class="btn-group btn-group-sm">
                <a href="{{ route('admin.hajj-omra.preview', [$package, 'locale' => 'fr']) }}"
                   class="btn {{ $isAr ? 'btn-outline-secondary' : 'btn-primary' }}">Français</a>
                <a href="{{ route('admin.hajj-omra.preview', [$package, 'locale' => 'ar']) }}"
                   class="btn {{ $isAr ? 'btn-primary' : 'btn-outline-secondary' }}" lang="ar">العربية</a>
            </div>
            <a href="{{ route('admin.hajj-omra.edit', $package) }}" class="btn btn-sm btn-outline-secondary">Retour à l'édition</a>
        </div>
    </div>

    {{--
        Le bloc d'apercu porte dir="rtl" quand la langue est l'arabe, mais les valeurs
        numeriques (prix, dates, distances) sont enfermees dans des spans dir="ltr" :
        elles restent lisibles et ne sont jamais reordonnees par le moteur bidirectionnel.
    --}}
    <div class="ho-preview" @if ($isAr) dir="rtl" lang="ar" @else dir="ltr" lang="fr" @endif>
        <style>{!! file_get_contents(base_path('wp-plugin/ajinsafro-traveler-home/assets/css/hajj-omra-formulas.css')) !!}</style>
        @if ($commercial['has_formulas'])
            <section id="formules">
                <h2 class="h5">{{ $isAr ? 'الباقات والإقامة' : 'Formules & hébergements' }}</h2>
                {!! \Ajinsafro\HajjOmra\CommercialTable::render($commercial['formulas'], $locale, $package->currency) !!}
                @if (!$commercial['formulas'])<p>{{ $isAr ? 'لا توجد باقة مكتملة ومفعّلة.' : 'Aucune formule active et complète.' }}</p>@endif
            </section>
        @endif

        <div class="card border-0 shadow-sm mb-3">
            <div class="row g-0">
                <div class="col-lg-6">
                    @if ($package->main_image_url)
                        <img src="{{ $package->main_image_url }}" alt="" class="img-fluid w-100" style="height:100%;max-height:340px;object-fit:cover;">
                    @else
                        <div class="d-flex align-items-center justify-content-center bg-light text-muted" style="height:280px;">Aucune image</div>
                    @endif

                    @if ($package->images->isNotEmpty())
                        <div class="d-flex gap-2 p-2 overflow-auto">
                            @foreach ($package->images as $image)
                                <img src="{{ $image->image_url }}" alt="{{ $image->alt_text }}"
                                     class="rounded" style="width:84px;height:64px;object-fit:cover;">
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="col-lg-6">
                    <div class="card-body">
                        <h3 class="mb-1">{{ $t($package, 'title') }}</h3>
                        <div class="text-muted mb-3">
                            <span class="badge bg-secondary-subtle text-secondary">{{ $package->type_label }}</span>
                            @if ($package->duration_label)
                                <span class="ms-2">{{ $labels['duration'] }} :
                                    <span dir="ltr">{{ $package->duration_days }}</span>
                                    {{ $isAr ? 'أيام' : 'jours' }}
                                    @if ($package->duration_nights)
                                        / <span dir="ltr">{{ $package->duration_nights }}</span> {{ $isAr ? 'ليالٍ' : 'nuits' }}
                                    @endif
                                </span>
                            @endif
                        </div>

                        @if ($package->start_date)
                            <div class="mb-3 small text-muted">
                                <span dir="ltr">{{ $package->start_date->format('d/m/Y') }}</span>
                                @if ($package->return_date)
                                    &rarr; <span dir="ltr">{{ $package->return_date->format('d/m/Y') }}</span>
                                @endif
                            </div>
                        @endif

                        <div class="mb-3">
                            <div class="text-muted small">{{ $labels['from'] }}</div>
                            <div class="d-flex align-items-baseline gap-2 flex-wrap">
                                <span class="fs-3 fw-semibold" dir="ltr">
                                    {{ $package->price_from_value !== null ? $money($package->price_from_value) : '—' }}
                                </span>
                                @if ($package->old_price)
                                    <span class="text-muted text-decoration-line-through" dir="ltr">{{ $money($package->old_price) }}</span>
                                @endif
                                @if ($package->savings_value)
                                    <span class="badge bg-success-subtle text-success">
                                        {{ $labels['save'] }} <span dir="ltr">{{ $money($package->savings_value) }}</span>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="small text-muted">
                            {{ $labels['places'] }} : <span dir="ltr">{{ $package->remaining_places }}</span>
                        </div>

                        @if ($t($package, 'short_description'))
                            <p class="mt-3 mb-0">{{ $t($package, 'short_description') }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <ul class="nav nav-tabs mb-0" role="tablist">
            @foreach ([
                'details' => $labels['details'],
                'program' => $labels['program'],
                'prices' => $labels['prices'],
                'services' => $labels['services'],
                'housing' => $labels['housing'],
            ] as $key => $label)
                <li class="nav-item">
                    <button class="nav-link @if ($loop->first) active @endif" data-bs-toggle="tab"
                            data-bs-target="#pv-{{ $key }}" type="button">{{ $label }}</button>
                </li>
            @endforeach
        </ul>

        <div class="card border-0 shadow-sm rounded-top-0">
            <div class="card-body tab-content">

                <div class="tab-pane fade show active" id="pv-details">
                    @if ($t($package, 'description'))
                        <div>{!! nl2br(e($t($package, 'description'))) !!}</div>
                    @else
                        <p class="text-muted mb-0">—</p>
                    @endif

                    @if ($package->departures->isNotEmpty())
                        <h6 class="mt-4 mb-2">{{ $labels['departures'] }}</h6>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach ($package->departures as $departure)
                                <span class="badge bg-light text-body">
                                    <span dir="ltr">{{ optional($departure->departure_date)->format('d/m/Y') }}</span>
                                    @if ($departure->departure_city) — {{ $departure->departure_city }} @endif
                                </span>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="tab-pane fade" id="pv-program">
                    @forelse ($package->programDays as $day)
                        <div class="mb-3 pb-3 border-bottom">
                            <div class="fw-semibold">
                                {{ $labels['day'] }} <span dir="ltr">{{ $day->day_number }}</span>
                                @if ($day->city) — {{ $day->city }} @endif
                            </div>
                            @if ($t($day, 'title'))<div class="text-muted">{{ $t($day, 'title') }}</div>@endif
                            @if ($t($day, 'description'))<p class="mb-0 mt-1">{{ $t($day, 'description') }}</p>@endif
                        </div>
                    @empty
                        <p class="text-muted mb-0">—</p>
                    @endforelse
                </div>

                <div class="tab-pane fade" id="pv-prices">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ $isAr ? 'نوع الغرفة' : 'Type de chambre' }}</th>
                                    <th class="text-end">{{ $isAr ? 'السعر' : 'Prix' }}</th>
                                    <th class="text-end">{{ $isAr ? 'الأماكن' : 'Places' }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($package->roomPrices as $room)
                                    <tr>
                                        <td>{{ $room->room_type_label }}</td>
                                        <td class="text-end" dir="ltr">
                                            {{ $money($room->price) }}
                                            @if ($room->old_price)
                                                <span class="text-muted text-decoration-line-through ms-1">{{ $money($room->old_price) }}</span>
                                            @endif
                                        </td>
                                        <td class="text-end" dir="ltr">{{ $room->stock }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted py-3">—</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="pv-services">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <h6 class="mb-2">{{ $labels['included'] }}</h6>
                            <ul class="list-unstyled mb-0">
                                @forelse ($package->serviceItems->where('kind', 'included') as $item)
                                    <li class="mb-1"><span class="text-success">✓</span> {{ $t($item, 'label') }}</li>
                                @empty
                                    <li class="text-muted">—</li>
                                @endforelse
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6 class="mb-2">{{ $labels['excluded'] }}</h6>
                            <ul class="list-unstyled mb-0">
                                @forelse ($package->serviceItems->where('kind', 'excluded') as $item)
                                    <li class="mb-1"><span class="text-danger">✕</span> {{ $t($item, 'label') }}</li>
                                @empty
                                    <li class="text-muted">—</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>

                    @if ($t($package, 'booking_conditions'))
                        <h6 class="mt-4 mb-2">{{ $labels['conditions'] }}</h6>
                        <p class="mb-0">{{ $t($package, 'booking_conditions') }}</p>
                    @endif

                    @if ($t($package, 'required_documents'))
                        <h6 class="mt-4 mb-2">{{ $labels['documents'] }}</h6>
                        <p class="mb-0">{{ $t($package, 'required_documents') }}</p>
                    @endif
                </div>

                <div class="tab-pane fade" id="pv-housing">
                    <div class="row g-3">
                        @forelse ($package->hotels as $hotel)
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <div class="text-muted small">
                                                {{ $isAr ? (\App\Models\HajjOmraPackageHotel::CITY_LABELS_AR[$hotel->city] ?? $hotel->city_label) : $hotel->city_label }}
                                            </div>
                                            <div class="fw-semibold">{{ $hotel->name ?: '—' }}</div>
                                        </div>
                                        @if ($hotel->stars)
                                            <span class="badge bg-light text-body" dir="ltr">{{ $hotel->stars }} ★</span>
                                        @endif
                                    </div>
                                    <ul class="list-unstyled small text-muted mt-2 mb-0">
                                        @if ($hotel->haram_distance)
                                            <li>{{ $isAr ? 'المسافة إلى الحرم' : 'Distance du Haram' }} :
                                                <span dir="ltr">{{ $hotel->haram_distance }}</span></li>
                                        @endif
                                        @if ($hotel->nights)
                                            <li>{{ $isAr ? 'عدد الليالي' : 'Nuits' }} : <span dir="ltr">{{ $hotel->nights }}</span></li>
                                        @endif
                                        @if ($hotel->location)<li>{{ $hotel->location }}</li>@endif
                                    </ul>
                                    @if ($t($hotel, 'description'))
                                        <p class="small mt-2 mb-0">{{ $t($hotel, 'description') }}</p>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="col-12"><p class="text-muted mb-0">—</p></div>
                        @endforelse
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection
