@extends('layouts.front')

@section('title', e($voyage->name) . ' – AjiNsafro.ma')

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
<link rel="stylesheet" href="{{ asset('css/front-voyage-show.css') }}">
@endpush

@section('content')
@php
    use Carbon\Carbon;

    $heroSrc = $voyage->featured_image_url;
    $hasGallery = $voyage->images->isNotEmpty();
    $galleryImages = $hasGallery ? $voyage->images : collect();

    $hasProgram = $voyage->programDays->isNotEmpty();
    $hasDepartures = isset($departures) && $departures->isNotEmpty();
    $hasFlights = $voyage->flights->isNotEmpty() || $voyage->flightOptions->isNotEmpty();
    $hasHotels = isset($tourHotels) && $tourHotels->isNotEmpty();
    $hasTransfers = isset($transfers) && ($transfers['arrival']->isNotEmpty() || $transfers['departure']->isNotEmpty());
    $hasExtras = $voyage->extras->isNotEmpty();
    $hasHighlights = isset($highlights) && count($highlights) > 0;
    $hasIncludes = isset($includes) && count($includes) > 0;
    $hasExcludes = isset($excludes) && count($excludes) > 0;
    $hasDescription = ! empty(trim((string) $voyage->description));
    $hasAccroche = ! empty(trim((string) $voyage->accroche));
    $hasPriceFrom = $voyage->price_from !== null && $voyage->price_from > 0;
    $hasPromo = $voyage->old_price && $voyage->old_price > ($voyage->price_from ?? 0);
    $cur = $voyage->currency_symbol;

    $nextTravelDateId = (isset($nextDeparture) && $nextDeparture) ? (int) ($nextDeparture->wp_travel_date_id ?? 0) : 0;
    $reserveUrl = ($voyage && (int) $voyage->id > 0 && $nextTravelDateId > 0)
        ? url('/admin/reservations/create?voyage_id='.(int) $voyage->id.'&travel_date_id='.$nextTravelDateId)
        : null;
@endphp

{{-- ═══════════════════════════════════════════ --}}
{{-- A. HERO SECTION                            --}}
{{-- ═══════════════════════════════════════════ --}}
<section class="vp-hero" id="vp-hero">
    @if($heroSrc)
        <img src="{{ $heroSrc }}" alt="{{ e($voyage->name) }}" class="vp-hero__bg" onerror="this.style.display='none'">
    @endif
    <div class="vp-hero__overlay"></div>

    <div class="vp-hero__content">
        <nav class="vp-breadcrumb" aria-label="Fil d'Ariane">
            <a href="{{ url('/') }}">Accueil</a>
            <span>&rsaquo;</span>
            <a href="{{ url('/voyages') }}">Voyages</a>
            <span>&rsaquo;</span>
            <span>{{ e($voyage->name) }}</span>
        </nav>

        @if($hasPromo)
            <span class="vp-hero__promo-badge">
                <i class="fas fa-tag"></i> -{{ $voyage->discount_percent }}%
            </span>
        @endif

        <h1 class="vp-hero__title">{{ e($voyage->name) }}</h1>

        @if($voyage->destination)
            <p class="vp-hero__destination">
                <i class="fas fa-map-marker-alt"></i> {{ e($voyage->destination) }}
            </p>
        @endif

        <div class="vp-hero__meta">
            @if($voyage->duration_text)
                <span class="vp-hero__meta-item"><i class="far fa-clock"></i> {{ e($voyage->duration_text) }}</span>
            @endif
            @if($hasPriceFrom)
                <span class="vp-hero__meta-item vp-hero__meta-item--price">
                    <i class="fas fa-coins"></i>
                    À partir de {{ number_format($voyage->price_from, 0, ',', ' ') }} {{ $cur }}
                    @if($hasPromo)
                        <s class="vp-hero__old-price">{{ number_format($voyage->old_price, 0, ',', ' ') }} {{ $cur }}</s>
                    @endif
                </span>
            @endif
            @if($hasDepartures)
                <span class="vp-hero__meta-item"><i class="far fa-calendar-check"></i> {{ $departures->count() }} départ(s) disponible(s)</span>
            @endif
        </div>

        <div class="vp-hero__ctas">
            @if($reserveUrl)
                <a href="{{ $reserveUrl }}" class="vp-btn vp-btn--primary vp-btn--lg">
                    <i class="fas fa-bolt"></i> Réserver maintenant
                </a>
                <a href="#vp-departures" class="vp-btn vp-btn--outline-white vp-btn--lg">
                    <i class="fas fa-calendar-alt"></i> Choisir une autre date
                </a>
            @else
                <a href="#vp-departures" class="vp-btn vp-btn--primary vp-btn--lg">
                    <i class="fas fa-calendar-alt"></i> Voir les départs
                </a>
                <a href="#vp-contact" class="vp-btn vp-btn--outline-white vp-btn--lg">
                    <i class="fas fa-envelope"></i> Nous contacter
                </a>
            @endif
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════ --}}
{{-- B. QUICK OVERVIEW BAR                      --}}
{{-- ═══════════════════════════════════════════ --}}
<section class="vp-overview" id="vp-overview">
    <div class="vp-container">
        <div class="vp-overview__grid">
            @if($voyage->duration_text)
                <div class="vp-overview__item">
                    <i class="far fa-clock vp-overview__icon"></i>
                    <div><span class="vp-overview__label">Durée</span><strong>{{ e($voyage->duration_text) }}</strong></div>
                </div>
            @endif
            @if($voyage->destination)
                <div class="vp-overview__item">
                    <i class="fas fa-globe-africa vp-overview__icon"></i>
                    <div><span class="vp-overview__label">Destination</span><strong>{{ e($voyage->destination) }}</strong></div>
                </div>
            @endif
            @if($hasPriceFrom)
                <div class="vp-overview__item">
                    <i class="fas fa-tag vp-overview__icon"></i>
                    <div><span class="vp-overview__label">À partir de</span><strong>{{ number_format($voyage->price_from, 0, ',', ' ') }} {{ $cur }}</strong></div>
                </div>
            @endif
            @if(isset($nextDeparture) && $nextDeparture)
                <div class="vp-overview__item">
                    <i class="fas fa-plane-departure vp-overview__icon"></i>
                    <div><span class="vp-overview__label">Prochain départ</span><strong>{{ $nextDeparture->start_date->locale('fr')->translatedFormat('d M Y') }}</strong></div>
                </div>
            @endif
            @if($voyage->min_people)
                <div class="vp-overview__item">
                    <i class="fas fa-users vp-overview__icon"></i>
                    <div><span class="vp-overview__label">Groupe</span><strong>Min {{ $voyage->min_people }} pers.</strong></div>
                </div>
            @endif
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════ --}}
{{-- C. GALLERY                                 --}}
{{-- ═══════════════════════════════════════════ --}}
@if($hasGallery)
<section class="vp-section vp-gallery-section" id="vp-gallery">
    <div class="vp-container">
        <div class="vp-gallery" id="vp-gallery-grid">
            @foreach($galleryImages->take(6) as $idx => $img)
                <a href="{{ $img->url }}"
                   class="vp-gallery__item {{ $idx === 0 ? 'vp-gallery__item--main' : '' }}"
                   data-vp-lightbox
                   data-index="{{ $idx }}">
                    <img src="{{ $img->url }}" alt="Photo {{ $idx + 1 }}" loading="lazy">
                    @if($idx === 5 && $galleryImages->count() > 6)
                        <span class="vp-gallery__more">+{{ $galleryImages->count() - 6 }} photos</span>
                    @endif
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- MAIN LAYOUT: content + sidebar --}}
<div class="vp-main">
    <div class="vp-container">
        <div class="vp-layout">

            {{-- LEFT COLUMN --}}
            <div class="vp-content">

                {{-- ═══════════════════════════════════════════ --}}
                {{-- D. ACCROCHE / DESCRIPTION                   --}}
                {{-- ═══════════════════════════════════════════ --}}
                @if($hasAccroche || $hasDescription)
                <section class="vp-section vp-card" id="vp-about">
                    <h2 class="vp-section__title"><i class="fas fa-feather-alt"></i> À propos de ce voyage</h2>
                    @if($hasAccroche)
                        <p class="vp-accroche">{{ e($voyage->accroche) }}</p>
                    @endif
                    @if($hasDescription)
                        <div class="vp-prose">{!! $voyage->description !!}</div>
                    @endif
                </section>
                @endif

                {{-- ═══════════════════════════════════════════ --}}
                {{-- E. HIGHLIGHTS                               --}}
                {{-- ═══════════════════════════════════════════ --}}
                @if($hasHighlights)
                <section class="vp-section vp-card" id="vp-highlights">
                    <h2 class="vp-section__title"><i class="fas fa-star"></i> Points forts</h2>
                    <div class="vp-highlights">
                        @foreach($highlights as $hl)
                            <div class="vp-highlight">
                                <span class="vp-highlight__icon"><i class="fas fa-check-circle"></i></span>
                                <span>{{ e($hl) }}</span>
                            </div>
                        @endforeach
                    </div>
                </section>
                @endif

                {{-- ═══════════════════════════════════════════ --}}
                {{-- F. DEPARTURES / AVAILABILITY                --}}
                {{-- ═══════════════════════════════════════════ --}}
                @if($hasDepartures)
                <section class="vp-section vp-card" id="vp-departures">
                    <h2 class="vp-section__title"><i class="fas fa-calendar-alt"></i> Départs disponibles</h2>
                    <div class="vp-departures">
                        @foreach($departures as $dep)
                            @php
                                $depStatus = $dep->status ?? 'open';
                                $depStatusLabel = match($depStatus) {
                                    'open' => 'Disponible',
                                    'limited' => 'Dernières places',
                                    'full' => 'Complet',
                                    'closed', 'canceled', 'cancelled' => 'Fermé',
                                    default => ucfirst($depStatus),
                                };
                                $depStatusClass = match($depStatus) {
                                    'open' => 'vp-dep-badge--ok',
                                    'limited' => 'vp-dep-badge--warn',
                                    'full', 'closed', 'canceled', 'cancelled' => 'vp-dep-badge--full',
                                    default => 'vp-dep-badge--ok',
                                };
                                $depTravelDateId = (int) ($dep->wp_travel_date_id ?? 0);
                                $depReserveUrl = ((int) $voyage->id > 0 && $depTravelDateId > 0)
                                    ? url('/admin/reservations/create?voyage_id='.(int) $voyage->id.'&travel_date_id='.$depTravelDateId)
                                    : null;
                            @endphp
                            <div class="vp-departure {{ $depStatus === 'full' ? 'vp-departure--full' : '' }}">
                                <div class="vp-departure__date">
                                    <span class="vp-departure__day">{{ $dep->start_date->format('d') }}</span>
                                    <span class="vp-departure__month">{{ $dep->start_date->locale('fr')->translatedFormat('M Y') }}</span>
                                </div>
                                <div class="vp-departure__details">
                                    <span class="vp-dep-badge {{ $depStatusClass }}">{{ $depStatusLabel }}</span>
                                    @if($dep->end_date)
                                        <span class="vp-departure__range">→ {{ $dep->end_date->locale('fr')->translatedFormat('d M Y') }}</span>
                                    @endif
                                    @if($dep->available_capacity !== null && $dep->available_capacity > 0 && $depStatus !== 'full')
                                        <span class="vp-departure__places">{{ $dep->available_capacity }} place(s) restante(s)</span>
                                    @endif
                                </div>
                                @if($depStatus !== 'full' && $depStatus !== 'closed')
                                    @if($depReserveUrl)
                                        <a href="{{ $depReserveUrl }}" class="vp-btn vp-btn--sm vp-btn--primary">Réserver</a>
                                    @else
                                        <a href="#vp-contact" class="vp-btn vp-btn--sm vp-btn--primary">Réserver</a>
                                    @endif
                                @endif
                            </div>
                        @endforeach
                    </div>
                </section>
                @endif

                {{-- ═══════════════════════════════════════════ --}}
                {{-- G. PROGRAMME JOUR PAR JOUR                  --}}
                {{-- ═══════════════════════════════════════════ --}}
                @if($hasProgram)
                <section class="vp-section vp-card" id="vp-program">
                    <h2 class="vp-section__title"><i class="fas fa-route"></i> Programme détaillé</h2>
                    <div class="vp-timeline">
                        @foreach($voyage->programDays as $day)
                            @php
                                $dayNum = $day->day_number;
                                $dayTitle = trim($day->title ?? '') !== '' ? $day->title : 'Jour '.$dayNum;
                                $dayContent = $day->content_html ?: $day->description;
                                $meals = $day->meals_array;
                                $hotel = $day->hotel;
                                $dayType = $day->day_type ?? '';
                                $dayLabel = $day->day_label_badge;
                            @endphp
                            <div class="vp-timeline__item" id="vp-day-{{ $dayNum }}">
                                <div class="vp-timeline__marker">
                                    <span class="vp-timeline__num">{{ $dayNum }}</span>
                                </div>
                                <div class="vp-timeline__card">
                                    <div class="vp-timeline__header">
                                        <h3 class="vp-timeline__title">
                                            Jour {{ $dayNum }} — {{ e($dayTitle) }}
                                        </h3>
                                        <div class="vp-timeline__badges">
                                            @if($day->city)
                                                <span class="vp-badge vp-badge--location"><i class="fas fa-map-pin"></i> {{ e($day->city) }}</span>
                                            @endif
                                            @if($dayLabel)
                                                <span class="vp-badge">{{ e($dayLabel) }}</span>
                                            @endif
                                            @if($dayType === 'libre')
                                                <span class="vp-badge vp-badge--free">Jour libre</span>
                                            @endif
                                        </div>
                                    </div>

                                    @if($dayContent)
                                        <div class="vp-timeline__body vp-prose">
                                            {!! $dayContent !!}
                                        </div>
                                    @endif

                                    <div class="vp-timeline__footer">
                                        @if($meals['breakfast'] || $meals['lunch'] || $meals['dinner'])
                                            <div class="vp-meals">
                                                @if($meals['breakfast'])<span class="vp-meal"><i class="fas fa-coffee"></i> Petit-déjeuner</span>@endif
                                                @if($meals['lunch'])<span class="vp-meal"><i class="fas fa-utensils"></i> Déjeuner</span>@endif
                                                @if($meals['dinner'])<span class="vp-meal"><i class="fas fa-moon"></i> Dîner</span>@endif
                                            </div>
                                        @endif
                                        @if($day->nights && $day->nights > 0)
                                            <span class="vp-night"><i class="fas fa-bed"></i> {{ $day->nights }} nuit(s)</span>
                                        @endif
                                        @if($hotel)
                                            <span class="vp-hotel-inline">
                                                <i class="fas fa-hotel"></i> {{ e($hotel->hotel_name ?? 'Hôtel') }}
                                                @if($hotel->stars) <span class="vp-stars">{{ str_repeat('★', $hotel->stars) }}</span> @endif
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
                @endif

                {{-- ═══════════════════════════════════════════ --}}
                {{-- H. HOTELS                                   --}}
                {{-- ═══════════════════════════════════════════ --}}
                @if($hasHotels)
                <section class="vp-section vp-card" id="vp-hotels">
                    <h2 class="vp-section__title"><i class="fas fa-hotel"></i> Hébergement</h2>
                    <div class="vp-hotels">
                        @foreach($tourHotels as $th)
                            <div class="vp-hotel-card">
                                <div class="vp-hotel-card__body">
                                    <h3 class="vp-hotel-card__name">
                                        {{ e($th->hotel_name ?: 'Hôtel') }}
                                        @if($th->stars)
                                            <span class="vp-stars">{{ str_repeat('★', $th->stars) }}</span>
                                        @endif
                                    </h3>
                                    @if($th->address)
                                        <p class="vp-hotel-card__address"><i class="fas fa-map-marker-alt"></i> {{ e($th->address) }}</p>
                                    @endif
                                    @if($th->meal_plan)
                                        <span class="vp-badge"><i class="fas fa-utensils"></i> {{ e($th->meal_plan) }}</span>
                                    @endif
                                    @if($th->check_in_day || $th->day_number)
                                        <span class="vp-badge vp-badge--location">
                                            Jour {{ $th->check_in_day ?: $th->day_number }}
                                            @if($th->check_out_day) → Jour {{ $th->check_out_day }} @endif
                                        </span>
                                    @endif
                                    @if($th->notes)
                                        <p class="vp-hotel-card__notes">{{ e($th->notes) }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
                @endif

                {{-- ═══════════════════════════════════════════ --}}
                {{-- I. FLIGHTS / TRANSPORT                      --}}
                {{-- ═══════════════════════════════════════════ --}}
                @if($hasFlights)
                <section class="vp-section vp-card" id="vp-flights">
                    <h2 class="vp-section__title"><i class="fas fa-plane"></i> Vols & Transport</h2>

                    @if($voyage->flightOptions->isNotEmpty())
                        <div class="vp-flights">
                            @foreach($voyage->flightOptions as $opt)
                                <div class="vp-flight-card">
                                    <div class="vp-flight-card__type">
                                        @if($opt->type === 'outbound')
                                            <i class="fas fa-plane-departure"></i> Vol aller
                                        @elseif($opt->type === 'return')
                                            <i class="fas fa-plane-arrival"></i> Vol retour
                                        @else
                                            <i class="fas fa-plane"></i> Vol interne
                                        @endif
                                        @if($opt->day_number) <span class="vp-badge">Jour {{ $opt->day_number }}</span> @endif
                                    </div>
                                    <div class="vp-flight-card__route">
                                        <span class="vp-flight-card__city">{{ e($opt->from_label) }}</span>
                                        <span class="vp-flight-card__arrow"><i class="fas fa-long-arrow-alt-right"></i></span>
                                        <span class="vp-flight-card__city">{{ e($opt->to_label) }}</span>
                                    </div>
                                    <div class="vp-flight-card__details">
                                        @if($opt->airline)
                                            <span><i class="fas fa-building"></i> {{ e($opt->airline->name ?? '') }}</span>
                                        @endif
                                        @if($opt->flight_number)
                                            <span>N° {{ e($opt->flight_number) }}</span>
                                        @endif
                                        @if($opt->depart_at)
                                            <span><i class="far fa-clock"></i> {{ $opt->depart_at->format('H:i') }}</span>
                                        @endif
                                        @if($opt->duration_minutes)
                                            <span>{{ intdiv($opt->duration_minutes, 60) }}h{{ str_pad($opt->duration_minutes % 60, 2, '0', STR_PAD_LEFT) }}</span>
                                        @endif
                                        @if($opt->baggage_checkin_kg)
                                            <span><i class="fas fa-suitcase-rolling"></i> {{ $opt->baggage_checkin_kg }} kg</span>
                                        @endif
                                    </div>
                                    @if($opt->is_tentative)
                                        <span class="vp-badge vp-badge--warn">Horaires susceptibles de changer</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @elseif($voyage->flights->isNotEmpty())
                        <div class="vp-flights">
                            @foreach($voyage->flights as $fl)
                                <div class="vp-flight-card">
                                    <div class="vp-flight-card__type">
                                        @if($fl->direction === 'outbound')
                                            <i class="fas fa-plane-departure"></i> Vol aller
                                        @else
                                            <i class="fas fa-plane-arrival"></i> Vol retour
                                        @endif
                                    </div>
                                    <div class="vp-flight-card__route">
                                        <span class="vp-flight-card__city">{{ e($fl->from_label) }}</span>
                                        <span class="vp-flight-card__arrow"><i class="fas fa-long-arrow-alt-right"></i></span>
                                        <span class="vp-flight-card__city">{{ e($fl->to_label) }}</span>
                                    </div>
                                    <div class="vp-flight-card__details">
                                        @if($fl->airline)
                                            <span><i class="fas fa-building"></i> {{ e($fl->airline->name ?? '') }}</span>
                                        @endif
                                        @if($fl->flight_number)
                                            <span>N° {{ e($fl->flight_number) }}</span>
                                        @endif
                                        @if($fl->departure_date)
                                            <span><i class="far fa-calendar"></i> {{ $fl->departure_date->locale('fr')->translatedFormat('d M Y') }}</span>
                                        @endif
                                        @if($fl->baggage_checkin_kg)
                                            <span><i class="fas fa-suitcase-rolling"></i> {{ $fl->baggage_checkin_kg }} kg</span>
                                        @endif
                                    </div>
                                    @if($fl->is_tentative)
                                        <span class="vp-badge vp-badge--warn">Horaires susceptibles de changer</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>
                @endif

                {{-- ═══════════════════════════════════════════ --}}
                {{-- TRANSFERS                                   --}}
                {{-- ═══════════════════════════════════════════ --}}
                @if($hasTransfers)
                <section class="vp-section vp-card" id="vp-transfers">
                    <h2 class="vp-section__title"><i class="fas fa-shuttle-van"></i> Transferts</h2>
                    <div class="vp-transfers">
                        @foreach(['arrival' => 'Arrivée', 'departure' => 'Départ'] as $dir => $dirLabel)
                            @if($transfers[$dir]->isNotEmpty())
                                @foreach($transfers[$dir] as $tr)
                                    <div class="vp-transfer-card">
                                        <span class="vp-badge">{{ $dirLabel }}</span>
                                        <div class="vp-transfer-card__route">
                                            {{ e($tr->from_label ?: '—') }} → {{ e($tr->to_label ?: '—') }}
                                        </div>
                                        <div class="vp-transfer-card__meta">
                                            @if($tr->vehicle_type)
                                                <span><i class="fas fa-car"></i> {{ e($tr->vehicle_type) }}</span>
                                            @endif
                                            @if($tr->pickup_time_formatted !== '—')
                                                <span><i class="far fa-clock"></i> {{ $tr->pickup_time_formatted }}</span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        @endforeach
                    </div>
                </section>
                @endif

                {{-- ═══════════════════════════════════════════ --}}
                {{-- J. EXTRAS                                   --}}
                {{-- ═══════════════════════════════════════════ --}}
                @if($hasExtras)
                <section class="vp-section vp-card" id="vp-extras">
                    <h2 class="vp-section__title"><i class="fas fa-puzzle-piece"></i> Activités & Extras</h2>
                    <div class="vp-extras">
                        @foreach($voyage->extras as $extra)
                            <div class="vp-extra-card">
                                <div class="vp-extra-card__icon">
                                    <i class="fas {{ $extra->icon ?: 'fa-plus-circle' }}"></i>
                                </div>
                                <div class="vp-extra-card__body">
                                    <h4 class="vp-extra-card__name">{{ e($extra->name) }}</h4>
                                    @if($extra->description)
                                        <p class="vp-extra-card__desc">{{ e($extra->description) }}</p>
                                    @endif
                                </div>
                                <div class="vp-extra-card__price">
                                    @if($extra->price_adult > 0)
                                        <span>{{ number_format((float) $extra->price_adult, 0, ',', ' ') }} {{ $cur }}</span>
                                        <small>/ adulte</small>
                                    @else
                                        <span class="vp-extra-card__included">Inclus</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
                @endif

                {{-- ═══════════════════════════════════════════ --}}
                {{-- K. INCLUS / NON INCLUS                      --}}
                {{-- ═══════════════════════════════════════════ --}}
                @if($hasIncludes || $hasExcludes)
                <section class="vp-section vp-card" id="vp-includes">
                    <h2 class="vp-section__title"><i class="fas fa-clipboard-check"></i> Ce qui est inclus</h2>
                    <div class="vp-includes-grid">
                        @if($hasIncludes)
                            <div class="vp-includes-col vp-includes-col--yes">
                                <h3><i class="fas fa-check-circle"></i> Inclus</h3>
                                <ul>
                                    @foreach($includes as $item)
                                        <li>{{ e($item) }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        @if($hasExcludes)
                            <div class="vp-includes-col vp-includes-col--no">
                                <h3><i class="fas fa-times-circle"></i> Non inclus</h3>
                                <ul>
                                    @foreach($excludes as $item)
                                        <li>{{ e($item) }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                </section>
                @endif

                {{-- ═══════════════════════════════════════════ --}}
                {{-- L. TARIFS                                   --}}
                {{-- ═══════════════════════════════════════════ --}}
                @if($hasPriceFrom)
                <section class="vp-section vp-card vp-tarifs" id="vp-pricing">
                    <h2 class="vp-section__title"><i class="fas fa-coins"></i> Tarifs</h2>
                    <div class="vp-tarifs__grid">
                        <div class="vp-tarif-box">
                            <span class="vp-tarif-box__label">Adulte</span>
                            <span class="vp-tarif-box__amount">{{ number_format($voyage->price_from, 0, ',', ' ') }} {{ $cur }}</span>
                            @if($hasPromo)
                                <span class="vp-tarif-box__old"><s>{{ number_format($voyage->old_price, 0, ',', ' ') }} {{ $cur }}</s></span>
                                <span class="vp-tarif-box__save">Économisez {{ number_format($voyage->discount_amount, 0, ',', ' ') }} {{ $cur }}</span>
                            @endif
                        </div>
                    </div>
                </section>
                @endif

            </div>{{-- /vp-content --}}

            {{-- ═══════════════════════════════════════════ --}}
            {{-- RIGHT SIDEBAR (sticky)                      --}}
            {{-- ═══════════════════════════════════════════ --}}
            <aside class="vp-sidebar" id="vp-sidebar">
                <div class="vp-sidebar__sticky">

                    {{-- Price box --}}
                    <div class="vp-price-box">
                        @if($hasPriceFrom)
                            <p class="vp-price-box__from">À partir de</p>
                            <p class="vp-price-box__amount">
                                {{ number_format($voyage->price_from, 0, ',', ' ') }}
                                <span class="vp-price-box__cur">{{ $cur }}</span>
                            </p>
                            @if($hasPromo)
                                <p class="vp-price-box__promo">
                                    <s>{{ number_format($voyage->old_price, 0, ',', ' ') }} {{ $cur }}</s>
                                    <span class="vp-price-box__discount">-{{ $voyage->discount_percent }}%</span>
                                </p>
                            @endif
                        @else
                            <p class="vp-price-box__amount" style="font-size:1.2rem">Sur demande</p>
                        @endif

                        @if(isset($nextDeparture) && $nextDeparture)
                            <div class="vp-price-box__next">
                                <span>Prochain départ</span>
                                <strong>{{ $nextDeparture->start_date->locale('fr')->translatedFormat('d M Y') }}</strong>
                            </div>
                        @endif

                        @if($reserveUrl)
                            <a href="{{ $reserveUrl }}" class="vp-btn vp-btn--primary vp-btn--block">
                                <i class="fas fa-bolt"></i> Réserver maintenant
                            </a>
                        @else
                            <a href="#vp-contact" class="vp-btn vp-btn--primary vp-btn--block">
                                <i class="fas fa-envelope"></i> Nous contacter
                            </a>
                        @endif
                        <a href="https://wa.me/212660683464?text={{ rawurlencode('Bonjour, je suis intéressé(e) par le voyage : '.$voyage->name) }}" target="_blank" rel="noopener" class="vp-btn vp-btn--whatsapp vp-btn--block">
                            <i class="fab fa-whatsapp"></i> WhatsApp
                        </a>
                    </div>

                    {{-- Quick nav --}}
                    <nav class="vp-quicknav">
                        <h4 class="vp-quicknav__title">Navigation rapide</h4>
                        @if($hasProgram)<a href="#vp-program" class="vp-quicknav__link"><i class="fas fa-route"></i> Programme</a>@endif
                        @if($hasDepartures)<a href="#vp-departures" class="vp-quicknav__link"><i class="fas fa-calendar-alt"></i> Départs</a>@endif
                        @if($hasHotels)<a href="#vp-hotels" class="vp-quicknav__link"><i class="fas fa-hotel"></i> Hôtels</a>@endif
                        @if($hasFlights)<a href="#vp-flights" class="vp-quicknav__link"><i class="fas fa-plane"></i> Vols</a>@endif
                        @if($hasExtras)<a href="#vp-extras" class="vp-quicknav__link"><i class="fas fa-puzzle-piece"></i> Extras</a>@endif
                        @if($hasIncludes || $hasExcludes)<a href="#vp-includes" class="vp-quicknav__link"><i class="fas fa-clipboard-check"></i> Inclus</a>@endif
                    </nav>

                </div>
            </aside>

        </div>{{-- /vp-layout --}}
    </div>
</div>

{{-- ═══════════════════════════════════════════ --}}
{{-- M. SIMILAR VOYAGES                          --}}
{{-- ═══════════════════════════════════════════ --}}
@if(isset($similarVoyages) && $similarVoyages->isNotEmpty())
<section class="vp-section vp-similar" id="vp-similar">
    <div class="vp-container">
        <h2 class="vp-section__title vp-section__title--center"><i class="fas fa-compass"></i> Voyages similaires</h2>
        <div class="vp-similar__grid">
            @foreach($similarVoyages as $sv)
                <a href="{{ url('/voyages/'.$sv->slug) }}" class="vp-similar__card">
                    <div class="vp-similar__img">
                        @if($sv->featured_image_url)
                            <img src="{{ $sv->featured_image_url }}" alt="{{ e($sv->name) }}" loading="lazy">
                        @else
                            <div class="vp-similar__placeholder"><i class="fas fa-image"></i></div>
                        @endif
                    </div>
                    <div class="vp-similar__body">
                        <h3>{{ e($sv->name) }}</h3>
                        @if($sv->destination)
                            <p><i class="fas fa-map-marker-alt"></i> {{ e($sv->destination) }}</p>
                        @endif
                        @if($sv->price_from)
                            <span class="vp-similar__price">{{ number_format($sv->price_from, 0, ',', ' ') }} {{ $sv->currency_symbol }}</span>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ═══════════════════════════════════════════ --}}
{{-- N. CTA FINAL / CONTACT                     --}}
{{-- ═══════════════════════════════════════════ --}}
<section class="vp-cta-final" id="vp-contact">
    <div class="vp-container">
        <div class="vp-cta-final__inner">
            <h2>{{ $reserveUrl ? 'Réservez votre départ' : 'Une question ?' }}</h2>
            <p>{{ $reserveUrl ? 'Un départ est disponible : finalisez votre réservation en quelques clics.' : 'Contactez-nous sur WhatsApp ou par téléphone pour en savoir plus.' }}</p>
            <div class="vp-cta-final__actions">
                @if($reserveUrl)
                    <a href="{{ $reserveUrl }}" class="vp-btn vp-btn--primary vp-btn--lg">
                        <i class="fas fa-bolt"></i> Réserver maintenant
                    </a>
                @endif
                <a href="https://wa.me/212660683464?text={{ rawurlencode('Bonjour, je suis intéressé(e) par le voyage : '.$voyage->name) }}" target="_blank" rel="noopener" class="vp-btn vp-btn--whatsapp vp-btn--lg">
                    <i class="fab fa-whatsapp"></i> WhatsApp
                </a>
                <a href="tel:+212660683464" class="vp-btn vp-btn--outline vp-btn--lg">
                    <i class="fas fa-phone-alt"></i> Appeler
                </a>
            </div>
        </div>
    </div>
</section>

{{-- FOOTER --}}
<footer class="vp-footer">
    <div class="vp-container">
        <a href="{{ url('/voyages') }}">← Retour aux voyages</a>
        <span>&copy; {{ date('Y') }} AjiNsafro.ma — Tous droits réservés</span>
    </div>
</footer>

{{-- LIGHTBOX --}}
@if($hasGallery)
<div id="vp-lightbox" class="vp-lightbox" hidden>
    <button class="vp-lightbox__close" aria-label="Fermer">&times;</button>
    <button class="vp-lightbox__prev" aria-label="Précédent"><i class="fas fa-chevron-left"></i></button>
    <button class="vp-lightbox__next" aria-label="Suivant"><i class="fas fa-chevron-right"></i></button>
    <img class="vp-lightbox__img" src="" alt="">
    <span class="vp-lightbox__counter"></span>
</div>
@endif

{{-- MOBILE STICKY CTA --}}
<div class="vp-mobile-cta" id="vp-mobile-cta">
    @if($hasPriceFrom)
        <span class="vp-mobile-cta__price">{{ number_format($voyage->price_from, 0, ',', ' ') }} {{ $cur }}</span>
    @endif
    <a href="{{ $reserveUrl ?: '#vp-contact' }}" class="vp-btn vp-btn--primary vp-btn--sm">Réserver</a>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    /* ——— Lightbox ——— */
    var lb = document.getElementById('vp-lightbox');
    if (lb) {
        var imgs = @json($galleryImages->pluck('url')->values()->all());
        var cur = 0;
        var lbImg = lb.querySelector('.vp-lightbox__img');
        var lbCounter = lb.querySelector('.vp-lightbox__counter');
        function showLb(i) {
            cur = ((i % imgs.length) + imgs.length) % imgs.length;
            lbImg.src = imgs[cur];
            lbCounter.textContent = (cur + 1) + ' / ' + imgs.length;
        }
        document.querySelectorAll('[data-vp-lightbox]').forEach(function(el) {
            el.addEventListener('click', function(e) {
                e.preventDefault();
                showLb(parseInt(el.dataset.index || '0', 10));
                lb.hidden = false;
                document.body.style.overflow = 'hidden';
            });
        });
        lb.querySelector('.vp-lightbox__close').addEventListener('click', function() {
            lb.hidden = true;
            document.body.style.overflow = '';
        });
        lb.querySelector('.vp-lightbox__prev').addEventListener('click', function() { showLb(cur - 1); });
        lb.querySelector('.vp-lightbox__next').addEventListener('click', function() { showLb(cur + 1); });
        lb.addEventListener('click', function(e) { if (e.target === lb) { lb.hidden = true; document.body.style.overflow = ''; } });
        document.addEventListener('keydown', function(e) {
            if (lb.hidden) return;
            if (e.key === 'Escape') { lb.hidden = true; document.body.style.overflow = ''; }
            if (e.key === 'ArrowLeft') showLb(cur - 1);
            if (e.key === 'ArrowRight') showLb(cur + 1);
        });
    }

    /* ——— Mobile CTA visibility ——— */
    var mobileCta = document.getElementById('vp-mobile-cta');
    var hero = document.getElementById('vp-hero');
    if (mobileCta && hero) {
        var observer = new IntersectionObserver(function(entries) {
            mobileCta.classList.toggle('is-visible', !entries[0].isIntersecting);
        }, { threshold: 0 });
        observer.observe(hero);
    }

    /* ——— Smooth scroll for anchors ——— */
    document.querySelectorAll('a[href^="#vp-"]').forEach(function(a) {
        a.addEventListener('click', function(e) {
            var target = document.querySelector(a.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });
});
</script>
@endpush
