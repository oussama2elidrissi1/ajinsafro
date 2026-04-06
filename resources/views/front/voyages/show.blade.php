@extends('layouts.front')
@section('title', html_entity_decode($voyage->name, ENT_QUOTES, 'UTF-8') . ' – AjiNsafro.ma')

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
<link rel="stylesheet" href="{{ asset('css/front-voyage-kiosk.css') }}">
@endpush

@section('content')
@php
    $voyageName = html_entity_decode($voyage->name ?? '', ENT_QUOTES, 'UTF-8');
    $heroSlides = $heroImages ?? [];
    $heroUrls = $heroImageUrls ?? [];
    $heroSrc = $heroSlides[0]['url'] ?? $heroUrls[0] ?? $voyage->featured_image_url;
    $heroThumbs = array_slice($heroSlides, 0, 5);
    $hasMultiHero = count($heroThumbs) > 1;
    $cur = $voyage->currency_symbol;
    $hasPriceFrom = isset($priceFrom) && $priceFrom > 0;
    $hasPromo = $voyage->old_price && $voyage->old_price > ($priceFrom ?? 0);
    $hasGallery = count($heroUrls) > 1 || $voyage->images->isNotEmpty();
    $galleryImages = $heroUrls;
    $hasDepartures = isset($departures) && $departures->isNotEmpty();
    $hasPlaces = isset($departurePlaces) && $departurePlaces->isNotEmpty();
    $hasExtras = $voyage->extras->isNotEmpty();
    $hasProgram = $voyage->programDays->isNotEmpty();
    $hasHighlights = isset($highlights) && count($highlights) > 0;
@endphp

{{-- ============ HERO ============ --}}
<section class="ksk-hero {{ $hasMultiHero ? 'ksk-hero--gallery' : '' }}" id="ksk-hero">
    {{-- Slider images --}}
    <div class="ksk-hero__slider" id="ksk-hero-slider">
        @forelse($heroThumbs as $idx => $img)
            @php
                $src = $img['url'] ?? null;
                $srcset = $img['srcset'] ?? null;
                $sizes = $img['sizes'] ?? '100vw';
            @endphp
            <img
                src="{{ $src }}"
                @if($srcset) srcset="{{ $srcset }}" sizes="{{ $sizes }}" @endif
                alt="{{ $voyageName }}"
                class="ksk-hero__slide {{ $idx === 0 ? 'is-active' : '' }}"
                loading="{{ $idx === 0 ? 'eager' : 'lazy' }}"
                decoding="async"
                fetchpriority="{{ $idx === 0 ? 'high' : 'auto' }}"
            >
        @empty
            <div class="ksk-hero__fallback"></div>
        @endforelse
    </div>
    <div class="ksk-hero__overlay"></div>

    {{-- Content --}}
    <div class="ksk-hero__content">
        <div class="ksk-hero__badges">
            @if($voyage->destination)
                <span class="ksk-chip"><i class="fas fa-map-marker-alt"></i> {{ e($voyage->destination) }}</span>
            @endif
            @if($voyage->duration_text)
                <span class="ksk-chip"><i class="far fa-clock"></i> {{ e($voyage->duration_text) }}</span>
            @endif
            @if($hasPromo)
                <span class="ksk-chip ksk-chip--promo"><i class="fas fa-tag"></i> -{{ $voyage->discount_percent }}%</span>
            @endif
        </div>
        <h1 class="ksk-hero__title">{{ $voyageName }}</h1>
        @if($voyage->accroche)
            <p class="ksk-hero__sub">{{ e($voyage->accroche) }}</p>
        @endif
        <div class="ksk-hero__price-line">
            @if($hasPriceFrom)
                <span class="ksk-hero__price">{{ number_format($priceFrom, 0, ',', ' ') }} {{ $cur }}</span>
                @if($hasPromo)
                    <s class="ksk-hero__old">{{ number_format($voyage->old_price, 0, ',', ' ') }} {{ $cur }}</s>
                @endif
                <span class="ksk-hero__per">/ personne</span>
            @endif
        </div>
        <button type="button" class="ksk-btn ksk-btn--hero" onclick="ksk.scrollToBuilder()">
            <i class="fas fa-bolt"></i> Commencer la réservation
        </button>
    </div>

    {{-- Thumbnail strip --}}
    @if($hasMultiHero)
    <div class="ksk-hero__thumbs" id="ksk-hero-thumbs">
        @foreach($heroThumbs as $idx => $img)
            @php
                $thumb = $img['thumb_url'] ?? $img['url'] ?? null;
            @endphp
            <button class="ksk-hero__thumb {{ $idx === 0 ? 'is-active' : '' }}" data-slide="{{ $idx }}">
                <img src="{{ $thumb }}" alt="" loading="lazy" decoding="async">
            </button>
        @endforeach
        <span class="ksk-hero__count"><i class="fas fa-images"></i> {{ count($heroThumbs) }} photos</span>
    </div>
    @endif

    {{-- Slider nav arrows --}}
    @if($hasMultiHero)
    <button class="ksk-hero__arrow ksk-hero__arrow--prev" id="ksk-hero-prev" aria-label="Précédent"><i class="fas fa-chevron-left"></i></button>
    <button class="ksk-hero__arrow ksk-hero__arrow--next" id="ksk-hero-next" aria-label="Suivant"><i class="fas fa-chevron-right"></i></button>
    @endif
</section>

{{-- ============ MAIN LAYOUT ============ --}}
<div class="ksk-main" id="ksk-main">
    <div class="ksk-container">
        <div class="ksk-layout">

            {{-- LEFT: BOOKING BUILDER --}}
            <div class="ksk-builder" id="ksk-builder">

                {{-- STEP INDICATOR --}}
                <nav class="ksk-steps" id="ksk-steps-nav">
                    <button class="ksk-step is-active" data-step="1"><span class="ksk-step__num">1</span><span class="ksk-step__label">Date</span></button>
                    @if($hasPlaces)
                        <button class="ksk-step" data-step="2"><span class="ksk-step__num">2</span><span class="ksk-step__label">Ville</span></button>
                    @endif
                    <button class="ksk-step" data-step="3"><span class="ksk-step__num">{{ $hasPlaces ? '3' : '2' }}</span><span class="ksk-step__label">Chambre</span></button>
                    @if($hasExtras)
                        <button class="ksk-step" data-step="4"><span class="ksk-step__num">{{ $hasPlaces ? '4' : '3' }}</span><span class="ksk-step__label">Extras</span></button>
                    @endif
                    <button class="ksk-step" data-step="5"><span class="ksk-step__num"><i class="fas fa-check"></i></span><span class="ksk-step__label">Résumé</span></button>
                </nav>

                {{-- ═══ STEP 1: DEPARTURE DATE ═══ --}}
                <section class="ksk-panel is-active" data-panel="1" id="ksk-panel-date">
                    <div class="ksk-panel__head">
                        <h2><i class="fas fa-calendar-alt"></i> Choisissez votre date de départ</h2>
                        <p>Sélectionnez la date qui vous convient parmi les départs disponibles.</p>
                    </div>
                    <div class="ksk-dates" id="ksk-dates-grid">
                        {{-- Rendered by JS from departuresJson --}}
                    </div>
                    <div class="ksk-panel__foot">
                        <button type="button" class="ksk-btn ksk-btn--next" id="ksk-next-1" disabled>
                            Continuer <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </section>

                {{-- ═══ STEP 2: DEPARTURE CITY ═══ --}}
                @if($hasPlaces)
                <section class="ksk-panel" data-panel="2" id="ksk-panel-city">
                    <div class="ksk-panel__head">
                        <h2><i class="fas fa-plane-departure"></i> Ville de départ</h2>
                        <p>Choisissez votre point de départ.</p>
                    </div>
                    <div class="ksk-cities" id="ksk-cities-grid">
                        {{-- Rendered by JS --}}
                    </div>
                    <div id="ksk-flight-info" class="ksk-flight-info" hidden></div>
                    <div class="ksk-panel__foot">
                        <button type="button" class="ksk-btn ksk-btn--back" onclick="ksk.goStep(1)"><i class="fas fa-arrow-left"></i> Retour</button>
                        <button type="button" class="ksk-btn ksk-btn--next" id="ksk-next-2" disabled>Continuer <i class="fas fa-arrow-right"></i></button>
                    </div>
                </section>
                @endif

                {{-- ═══ STEP 3: ROOM ═══ --}}
                <section class="ksk-panel" data-panel="3" id="ksk-panel-room">
                    <div class="ksk-panel__head">
                        <h2><i class="fas fa-bed"></i> Choisissez votre chambre</h2>
                        <p>Sélectionnez le type de chambre et le nombre de voyageurs.</p>
                    </div>
                    <div class="ksk-pax-row" id="ksk-pax">
                        <div class="ksk-pax-item">
                            <label>Adultes</label>
                            <div class="ksk-counter">
                                <button type="button" data-pax="adults" data-dir="-1">−</button>
                                <span id="ksk-pax-adults">2</span>
                                <button type="button" data-pax="adults" data-dir="1">+</button>
                            </div>
                        </div>
                        <div class="ksk-pax-item">
                            <label>Enfants</label>
                            <div class="ksk-counter">
                                <button type="button" data-pax="children" data-dir="-1">−</button>
                                <span id="ksk-pax-children">0</span>
                                <button type="button" data-pax="children" data-dir="1">+</button>
                            </div>
                        </div>
                    </div>
                    <div class="ksk-rooms" id="ksk-rooms-grid">
                        {{-- Rendered by JS based on selected departure --}}
                    </div>
                    <div class="ksk-panel__foot">
                        <button type="button" class="ksk-btn ksk-btn--back" onclick="ksk.goStep({{ $hasPlaces ? 2 : 1 }})"><i class="fas fa-arrow-left"></i> Retour</button>
                        <button type="button" class="ksk-btn ksk-btn--next" id="ksk-next-3" disabled>Continuer <i class="fas fa-arrow-right"></i></button>
                    </div>
                </section>

                {{-- ═══ STEP 4: EXTRAS ═══ --}}
                @if($hasExtras)
                <section class="ksk-panel" data-panel="4" id="ksk-panel-extras">
                    <div class="ksk-panel__head">
                        <h2><i class="fas fa-star"></i> Personnalisez votre séjour</h2>
                        <p>Ajoutez des options pour enrichir votre voyage.</p>
                    </div>
                    <div class="ksk-extras" id="ksk-extras-grid">
                        {{-- Rendered by JS --}}
                    </div>
                    <div class="ksk-panel__foot">
                        <button type="button" class="ksk-btn ksk-btn--back" onclick="ksk.goStep(3)"><i class="fas fa-arrow-left"></i> Retour</button>
                        <button type="button" class="ksk-btn ksk-btn--next" id="ksk-next-4">Continuer <i class="fas fa-arrow-right"></i></button>
                    </div>
                </section>
                @endif

                {{-- ═══ STEP 5: SUMMARY ═══ --}}
                <section class="ksk-panel" data-panel="5" id="ksk-panel-summary">
                    <div class="ksk-panel__head">
                        <h2><i class="fas fa-clipboard-check"></i> Récapitulatif de votre réservation</h2>
                        <p>Vérifiez vos choix avant de confirmer.</p>
                    </div>
                    <div class="ksk-summary-detail" id="ksk-summary-detail">
                        {{-- Rendered by JS --}}
                    </div>
                    <div class="ksk-panel__foot ksk-panel__foot--final">
                        <button type="button" class="ksk-btn ksk-btn--back" onclick="ksk.goStep({{ $hasExtras ? 4 : 3 }})"><i class="fas fa-arrow-left"></i> Modifier</button>
                        <a href="#" class="ksk-btn ksk-btn--reserve" id="ksk-reserve-btn">
                            <i class="fas fa-bolt"></i> Réserver maintenant
                        </a>
                    </div>
                </section>

            </div>{{-- /ksk-builder --}}

            {{-- RIGHT: STICKY SUMMARY --}}
            <aside class="ksk-sidebar" id="ksk-sidebar">
                <div class="ksk-sidebar__sticky">
                    <div class="ksk-cart">
                        <div class="ksk-cart__header">
                            <h3><i class="fas fa-shopping-bag"></i> Votre sélection</h3>
                        </div>
                        <div class="ksk-cart__body" id="ksk-cart-body">
                            <p class="ksk-cart__empty">Commencez par choisir une date de départ.</p>
                        </div>
                        <div class="ksk-cart__total">
                            <span>Total estimé</span>
                            <strong id="ksk-cart-total">— {{ $cur }}</strong>
                        </div>
                        <a href="#" class="ksk-btn ksk-btn--reserve ksk-btn--block" id="ksk-cart-reserve" style="display:none">
                            <i class="fas fa-bolt"></i> Réserver
                        </a>
                    </div>
                    {{-- Trip card --}}
                    <div class="ksk-trip-card">
                        @if($heroSrc)
                            <img src="{{ $heroSrc }}" alt="" class="ksk-trip-card__img">
                        @endif
                        <div class="ksk-trip-card__body">
                            <h4>{{ $voyageName }}</h4>
                            @if($voyage->destination)<p><i class="fas fa-map-marker-alt"></i> {{ e($voyage->destination) }}</p>@endif
                            @if($voyage->duration_text)<p><i class="far fa-clock"></i> {{ e($voyage->duration_text) }}</p>@endif
                        </div>
                    </div>
                    <a href="https://wa.me/212660683464?text={{ rawurlencode('Bonjour, je suis intéressé(e) par : '.$voyageName) }}" target="_blank" rel="noopener" class="ksk-btn ksk-btn--whatsapp ksk-btn--block">
                        <i class="fab fa-whatsapp"></i> Besoin d'aide ?
                    </a>
                </div>
            </aside>

        </div>
        {{-- /ksk-layout --}}
    </div>
</div>

{{-- ============ TRIP DETAILS (collapsible below builder) ============ --}}
<section class="ksk-details" id="ksk-details">
    <div class="ksk-container">
        <h2 class="ksk-details__title"><i class="fas fa-info-circle"></i> Détails du voyage</h2>

        @if($hasHighlights)
        <div class="ksk-highlights">
            @foreach($highlights as $hl)
                <span class="ksk-highlight"><i class="fas fa-check-circle"></i> {{ e($hl) }}</span>
            @endforeach
        </div>
        @endif

        @if(!empty(trim((string)$voyage->description)))
        <div class="ksk-prose">{!! $voyage->description !!}</div>
        @endif

        @if($hasProgram)
        <h3 class="ksk-details__sub"><i class="fas fa-route"></i> Programme jour par jour</h3>
        <div class="ksk-program">
            @foreach($voyage->programDays as $day)
                <details class="ksk-day" {{ $loop->first ? 'open' : '' }}>
                    <summary>
                        <span class="ksk-day__num">J{{ $day->day_number }}</span>
                        <span class="ksk-day__title">{{ e($day->title ?: 'Jour '.$day->day_number) }}</span>
                        @if($day->city)<span class="ksk-day__city"><i class="fas fa-map-pin"></i> {{ e($day->city) }}</span>@endif
                    </summary>
                    <div class="ksk-day__body">
                        @if($day->content_html){!! $day->content_html !!}@elseif($day->description){!! nl2br(e($day->description)) !!}@endif
                        @php $meals = $day->meals_array ?? ['breakfast' => false, 'lunch' => false, 'dinner' => false]; @endphp
                        @if(($meals['breakfast'] ?? false) || ($meals['lunch'] ?? false) || ($meals['dinner'] ?? false))
                            <div class="ksk-day__meals">
                                @if(($meals['breakfast'] ?? false))<span><i class="fas fa-coffee"></i> Petit-déj</span>@endif
                                @if(($meals['lunch'] ?? false))<span><i class="fas fa-utensils"></i> Déjeuner</span>@endif
                                @if(($meals['dinner'] ?? false))<span><i class="fas fa-moon"></i> Dîner</span>@endif
                            </div>
                        @endif
                    </div>
                </details>
            @endforeach
        </div>
        @endif
    </div>
</section>

{{-- ============ GALLERY ============ --}}
@if($hasGallery && count($galleryImages) > 1)
<section class="ksk-gallery-section">
    <div class="ksk-container">
        <h2 class="ksk-details__title"><i class="fas fa-images"></i> Galerie photos</h2>
        <div class="ksk-gallery">
            @foreach(array_slice($galleryImages, 0, 8) as $idx => $imgUrl)
                <a href="{{ $imgUrl }}" class="ksk-gallery__item" data-ksk-lb data-index="{{ $idx }}">
                    <img src="{{ $imgUrl }}" alt="Photo {{ $idx+1 }}" loading="lazy">
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ============ MOBILE STICKY BAR ============ --}}
<div class="ksk-mobile-bar" id="ksk-mobile-bar">
    <div class="ksk-mobile-bar__price">
        <span id="ksk-mobile-total">—</span> {{ $cur }}
    </div>
    <button type="button" class="ksk-btn ksk-btn--reserve ksk-btn--sm" onclick="ksk.scrollToBuilder()">
        <i class="fas fa-bolt"></i> Réserver
    </button>
</div>

{{-- ============ LIGHTBOX ============ --}}
@if($hasGallery && count($galleryImages) > 1)
<div id="ksk-lightbox" class="ksk-lightbox" hidden>
    <button class="ksk-lightbox__close" aria-label="Fermer">&times;</button>
    <button class="ksk-lightbox__prev"><i class="fas fa-chevron-left"></i></button>
    <button class="ksk-lightbox__next"><i class="fas fa-chevron-right"></i></button>
    <img class="ksk-lightbox__img" src="" alt="">
    <span class="ksk-lightbox__counter"></span>
</div>
@endif

@endsection

@push('scripts')
<script>
(function(){
    'use strict';

    var VOYAGE_ID = {{ (int)$voyage->id }};
    var CURRENCY = @json($cur);
    var DEPARTURES = @json($departuresJson);
    var PLACES = @json($placesJson);
    var EXTRAS = @json($extrasJson);
    var FLIGHTS_BY_PLACE = @json($flightsByPlace ?? (object)[]);
    var HAS_PLACES = PLACES.length > 0;
    var HAS_EXTRAS = EXTRAS.length > 0;
    var BASE_PRICE = {{ (float)($priceFrom ?? 0) }};

    var state = {
        step: 1,
        departureIdx: null,
        placeIdx: null,
        roomIdx: null,
        paxAdults: 2,
        paxChildren: 0,
        extras: {}
    };

    function esc(s) {
        var d = document.createElement('div'); d.textContent = s; return d.innerHTML;
    }
    function fmt(n) {
        return n.toLocaleString('fr-FR', {maximumFractionDigits:0});
    }

    /* ═══ STEP NAVIGATION ═══ */
    function resolveStep(n) {
        if (!HAS_PLACES && n >= 2) n = n === 2 ? 3 : n;
        if (!HAS_EXTRAS && n === 4) n = 5;
        return n;
    }
    function goStep(n) {
        n = resolveStep(n);
        state.step = n;
        document.querySelectorAll('.ksk-panel').forEach(function(p){
            p.classList.toggle('is-active', parseInt(p.dataset.panel) === n);
        });
        document.querySelectorAll('.ksk-step').forEach(function(s){
            var sn = parseInt(s.dataset.step);
            s.classList.toggle('is-active', sn === n);
            s.classList.toggle('is-done', sn < n);
        });
        document.getElementById('ksk-builder').scrollIntoView({behavior:'smooth', block:'start'});
        if (n === 3) renderRooms();
        if (n === 4) renderExtras();
        if (n === 5) renderSummary();
        updateCart();
    }

    /* ═══ STEP 1: DATES ═══ */
    function renderDates() {
        var g = document.getElementById('ksk-dates-grid'); if(!g) return;
        var h = '';
        DEPARTURES.forEach(function(d, i){
            var disabled = d.status === 'full' || d.status === 'closed' || d.status === 'canceled' || d.status === 'cancelled';
            var statusLabel = {open:'Disponible', limited:'Dernières places', full:'Complet', closed:'Fermé', canceled:'Fermé', cancelled:'Fermé', draft:'Bientôt'}[d.status] || d.status;
            var statusClass = {open:'ok', limited:'warn', full:'full', closed:'full', canceled:'full', cancelled:'full'}[d.status] || 'ok';
            var price = d.sale_price > 0 ? d.sale_price : (d.base_price > 0 ? d.base_price : BASE_PRICE);
            h += '<button type="button" class="ksk-date-card' + (disabled ? ' is-disabled' : '') + '" data-dep="'+i+'"' + (disabled ? ' disabled' : '') + '>';
            h += '<div class="ksk-date-card__date">' + esc(d.start_label) + '</div>';
            if(d.end_label) h += '<div class="ksk-date-card__range">→ ' + esc(d.end_label) + '</div>';
            h += '<span class="ksk-status ksk-status--'+statusClass+'">' + esc(statusLabel) + '</span>';
            if(!disabled && d.available_capacity > 0) h += '<div class="ksk-date-card__seats">' + d.available_capacity + ' place(s)</div>';
            if(price > 0) h += '<div class="ksk-date-card__price">' + fmt(price) + ' ' + CURRENCY + '</div>';
            h += '</button>';
        });
        if(h === '') h = '<p class="ksk-empty">Aucun départ disponible actuellement.</p>';
        g.innerHTML = h;
        g.querySelectorAll('.ksk-date-card:not(.is-disabled)').forEach(function(c){
            c.addEventListener('click', function(){
                state.departureIdx = parseInt(c.dataset.dep);
                state.roomIdx = null;
                g.querySelectorAll('.ksk-date-card').forEach(function(x){ x.classList.remove('is-selected'); });
                c.classList.add('is-selected');
                var btn = document.getElementById('ksk-next-1'); if(btn) btn.disabled = false;
                updateCart();
            });
        });
    }

    /* ═══ STEP 2: CITIES ═══ */
    function renderCities() {
        var g = document.getElementById('ksk-cities-grid'); if(!g) return;
        var h = '';
        PLACES.forEach(function(p, i){
            h += '<button type="button" class="ksk-city-card" data-place="'+i+'">';
            h += '<i class="fas fa-plane-departure ksk-city-card__icon"></i>';
            h += '<div class="ksk-city-card__name">' + esc(p.name) + '</div>';
            if(p.code) h += '<div class="ksk-city-card__code">' + esc(p.code) + '</div>';
            if(p.price > 0) h += '<div class="ksk-city-card__sup">+' + fmt(p.price) + ' ' + CURRENCY + '</div>';
            h += '</button>';
        });
        g.innerHTML = h;
        g.querySelectorAll('.ksk-city-card').forEach(function(c){
            c.addEventListener('click', function(){
                state.placeIdx = parseInt(c.dataset.place);
                g.querySelectorAll('.ksk-city-card').forEach(function(x){ x.classList.remove('is-selected'); });
                c.classList.add('is-selected');
                var btn = document.getElementById('ksk-next-2'); if(btn) btn.disabled = false;
                showFlightInfo();
                updateCart();
            });
        });
    }
    function showFlightInfo() {
        var el = document.getElementById('ksk-flight-info'); if(!el) return;
        var place = PLACES[state.placeIdx]; if(!place) { el.hidden = true; return; }
        var flights = FLIGHTS_BY_PLACE[String(place.id)] || [];
        if(flights.length === 0) { el.hidden = true; return; }
        var h = '<div class="ksk-flight-cards">';
        flights.forEach(function(f){
            var typeLabel = f.type === 'outbound' ? 'Aller' : (f.type === 'return' ? 'Retour' : 'Interne');
            h += '<div class="ksk-flight-mini">';
            h += '<span class="ksk-flight-mini__type">' + typeLabel + '</span>';
            h += '<span>' + esc(f.from_city||'—') + ' → ' + esc(f.to_city||'—') + '</span>';
            if(f.depart_at) h += '<span><i class="far fa-clock"></i> ' + f.depart_at + '</span>';
            if(f.airline) h += '<span><i class="fas fa-building"></i> ' + esc(f.airline) + '</span>';
            h += '</div>';
        });
        h += '</div>';
        el.innerHTML = h; el.hidden = false;
    }

    /* ═══ STEP 3: ROOMS ═══ */
    function renderRooms() {
        var g = document.getElementById('ksk-rooms-grid'); if(!g) return;
        var dep = DEPARTURES[state.departureIdx];
        var rooms = dep ? dep.rooms : [];
        var h = '';
        if(rooms.length === 0) {
            h = '<div class="ksk-room-card is-selected" data-room="-1">';
            h += '<div class="ksk-room-card__icon"><i class="fas fa-bed"></i></div>';
            h += '<div class="ksk-room-card__name">Chambre standard</div>';
            h += '<div class="ksk-room-card__meta">Selon disponibilité</div>';
            h += '<div class="ksk-room-card__price">Inclus</div>';
            h += '</div>';
            state.roomIdx = -1;
            var btn = document.getElementById('ksk-next-3'); if(btn) btn.disabled = false;
        } else {
            rooms.forEach(function(r, i){
                var disabled = r.status === 'full' || r.status === 'closed' || r.status === 'inactive' || r.available_rooms <= 0;
                var statusLabel = disabled ? 'Complet' : (r.status === 'limited' ? 'Dernières dispo' : 'Disponible');
                h += '<button type="button" class="ksk-room-card' + (disabled ? ' is-disabled' : '') + '" data-room="'+i+'"' + (disabled?' disabled':'') + '>';
                h += '<div class="ksk-room-card__icon"><i class="fas fa-bed"></i></div>';
                h += '<div class="ksk-room-card__name">' + esc(r.room_type) + '</div>';
                h += '<div class="ksk-room-card__meta">' + esc(r.hotel_name) + ' · ' + r.capacity_per_room + ' pers. max</div>';
                h += '<span class="ksk-status ksk-status--' + (disabled?'full':'ok') + '">' + statusLabel + '</span>';
                h += '<div class="ksk-room-card__price">' + (r.supplement > 0 ? '+'+fmt(r.supplement)+' '+CURRENCY : 'Inclus') + '</div>';
                h += '</button>';
            });
        }
        g.innerHTML = h;
        g.querySelectorAll('.ksk-room-card:not(.is-disabled)').forEach(function(c){
            c.addEventListener('click', function(){
                state.roomIdx = parseInt(c.dataset.room);
                g.querySelectorAll('.ksk-room-card').forEach(function(x){ x.classList.remove('is-selected'); });
                c.classList.add('is-selected');
                var btn = document.getElementById('ksk-next-3'); if(btn) btn.disabled = false;
                updateCart();
            });
        });
    }

    /* ═══ STEP 4: EXTRAS ═══ */
    function renderExtras() {
        var g = document.getElementById('ksk-extras-grid'); if(!g) return;
        var h = '';
        EXTRAS.forEach(function(e, i){
            var checked = state.extras[e.id] ? true : false;
            h += '<button type="button" class="ksk-extra-card' + (checked?' is-selected':'') + '" data-extra="'+e.id+'">';
            h += '<div class="ksk-extra-card__icon"><i class="fas ' + esc(e.icon) + '"></i></div>';
            h += '<div class="ksk-extra-card__body">';
            h += '<div class="ksk-extra-card__name">' + esc(e.name) + '</div>';
            if(e.description) h += '<div class="ksk-extra-card__desc">' + esc(e.description) + '</div>';
            h += '</div>';
            h += '<div class="ksk-extra-card__price">' + (e.price_adult > 0 ? fmt(e.price_adult)+' '+CURRENCY : 'Gratuit') + '</div>';
            h += '<div class="ksk-extra-card__check"><i class="fas ' + (checked?'fa-check-circle':'fa-plus-circle') + '"></i></div>';
            h += '</button>';
        });
        g.innerHTML = h;
        g.querySelectorAll('.ksk-extra-card').forEach(function(c){
            c.addEventListener('click', function(){
                var eid = parseInt(c.dataset.extra);
                if(state.extras[eid]) { delete state.extras[eid]; } else { state.extras[eid] = true; }
                renderExtras();
                updateCart();
            });
        });
    }

    /* ═══ PRICING ═══ */
    function calcTotal() {
        var dep = DEPARTURES[state.departureIdx];
        if(!dep) return 0;
        var perPerson = dep.sale_price > 0 ? dep.sale_price : (dep.base_price > 0 ? dep.base_price : BASE_PRICE);
        if(state.roomIdx !== null && state.roomIdx >= 0 && dep.rooms[state.roomIdx]) {
            perPerson += dep.rooms[state.roomIdx].supplement || 0;
        }
        var place = PLACES[state.placeIdx];
        if(place) perPerson += place.price || 0;
        var totalPax = state.paxAdults + state.paxChildren;
        var total = perPerson * (totalPax > 0 ? totalPax : 1);
        Object.keys(state.extras).forEach(function(eid){
            var ex = EXTRAS.find(function(e){ return e.id === parseInt(eid); });
            if(ex) total += (ex.price_adult * state.paxAdults) + (ex.price_child * state.paxChildren);
        });
        return total;
    }

    /* ═══ CART / SIDEBAR ═══ */
    function updateCart() {
        var body = document.getElementById('ksk-cart-body');
        var totalEl = document.getElementById('ksk-cart-total');
        var mobileTotal = document.getElementById('ksk-mobile-total');
        var reserveBtn = document.getElementById('ksk-cart-reserve');
        var dep = DEPARTURES[state.departureIdx];
        if(!dep) {
            body.innerHTML = '<p class="ksk-cart__empty">Commencez par choisir une date de départ.</p>';
            totalEl.textContent = '— ' + CURRENCY;
            if(mobileTotal) mobileTotal.textContent = '—';
            if(reserveBtn) reserveBtn.style.display = 'none';
            return;
        }
        var h = '';
        h += '<div class="ksk-cart__line"><span><i class="fas fa-calendar"></i> Départ</span><strong>' + esc(dep.start_label) + '</strong></div>';
        var place = PLACES[state.placeIdx];
        if(place) h += '<div class="ksk-cart__line"><span><i class="fas fa-plane"></i> Ville</span><strong>' + esc(place.name) + '</strong></div>';
        if(state.roomIdx !== null) {
            var roomLabel = 'Standard';
            if(state.roomIdx >= 0 && dep.rooms[state.roomIdx]) roomLabel = dep.rooms[state.roomIdx].room_type;
            h += '<div class="ksk-cart__line"><span><i class="fas fa-bed"></i> Chambre</span><strong>' + esc(roomLabel) + '</strong></div>';
        }
        h += '<div class="ksk-cart__line"><span><i class="fas fa-users"></i> Voyageurs</span><strong>' + state.paxAdults + ' ad.' + (state.paxChildren > 0 ? ' + '+state.paxChildren+' enf.' : '') + '</strong></div>';
        var extraNames = [];
        Object.keys(state.extras).forEach(function(eid){
            var ex = EXTRAS.find(function(e){ return e.id === parseInt(eid); });
            if(ex) extraNames.push(ex.name);
        });
        if(extraNames.length) h += '<div class="ksk-cart__line"><span><i class="fas fa-star"></i> Extras</span><strong>' + esc(extraNames.join(', ')) + '</strong></div>';
        body.innerHTML = h;
        var total = calcTotal();
        totalEl.textContent = fmt(total) + ' ' + CURRENCY;
        if(mobileTotal) mobileTotal.textContent = fmt(total);
        if(reserveBtn) {
            reserveBtn.style.display = state.step >= 3 ? '' : 'none';
            reserveBtn.href = buildReserveUrl();
        }
    }

    /* ═══ SUMMARY ═══ */
    function renderSummary() {
        var el = document.getElementById('ksk-summary-detail'); if(!el) return;
        var dep = DEPARTURES[state.departureIdx] || {};
        var place = PLACES[state.placeIdx];
        var h = '<div class="ksk-summary-grid">';
        h += sumRow('Date de départ', dep.start_label || '—', 'fa-calendar');
        if(dep.end_label) h += sumRow('Retour', dep.end_label, 'fa-calendar-check');
        if(place) h += sumRow('Ville de départ', place.name, 'fa-plane-departure');
        var roomLabel = 'Standard';
        if(state.roomIdx >= 0 && dep.rooms && dep.rooms[state.roomIdx]) roomLabel = dep.rooms[state.roomIdx].room_type;
        h += sumRow('Chambre', roomLabel, 'fa-bed');
        h += sumRow('Voyageurs', state.paxAdults + ' adulte(s)' + (state.paxChildren > 0 ? ', '+state.paxChildren+' enfant(s)' : ''), 'fa-users');
        var extraNames = [];
        Object.keys(state.extras).forEach(function(eid){
            var ex = EXTRAS.find(function(e){ return e.id === parseInt(eid); }); if(ex) extraNames.push(ex.name);
        });
        if(extraNames.length) h += sumRow('Extras', extraNames.join(', '), 'fa-star');
        h += '</div>';
        h += '<div class="ksk-summary-total"><span>Total estimé</span><strong>' + fmt(calcTotal()) + ' ' + CURRENCY + '</strong></div>';
        el.innerHTML = h;
        var btn = document.getElementById('ksk-reserve-btn');
        if(btn) btn.href = buildReserveUrl();
    }
    function sumRow(label, value, icon) {
        return '<div class="ksk-sum-row"><span class="ksk-sum-row__label"><i class="fas '+icon+'"></i> '+esc(label)+'</span><span class="ksk-sum-row__value">'+esc(value)+'</span></div>';
    }

    /* ═══ RESERVE URL ═══ */
    function buildReserveUrl() {
        var dep = DEPARTURES[state.departureIdx];
        if(!dep) return '#';
        var params = 'voyage_id=' + VOYAGE_ID + '&travel_date_id=' + dep.wp_travel_date_id;
        if(state.paxAdults) params += '&adults=' + state.paxAdults;
        if(state.paxChildren) params += '&children=' + state.paxChildren;
        return '/admin/reservations/create?' + params;
    }

    /* ═══ PAX COUNTERS ═══ */
    document.querySelectorAll('[data-pax]').forEach(function(btn){
        btn.addEventListener('click', function(){
            var key = btn.dataset.pax;
            var dir = parseInt(btn.dataset.dir);
            if(key === 'adults') state.paxAdults = Math.max(1, Math.min(9, state.paxAdults + dir));
            if(key === 'children') state.paxChildren = Math.max(0, Math.min(6, state.paxChildren + dir));
            document.getElementById('ksk-pax-adults').textContent = state.paxAdults;
            document.getElementById('ksk-pax-children').textContent = state.paxChildren;
            updateCart();
        });
    });

    /* ═══ NEXT BUTTONS ═══ */
    document.querySelectorAll('.ksk-btn--next').forEach(function(btn){
        btn.addEventListener('click', function(){
            var panel = btn.closest('.ksk-panel');
            var cur = parseInt(panel.dataset.panel);
            var next = cur + 1;
            if(!HAS_PLACES && next === 2) next = 3;
            if(!HAS_EXTRAS && next === 4) next = 5;
            goStep(next);
        });
    });
    document.querySelectorAll('.ksk-step').forEach(function(s){
        s.addEventListener('click', function(){
            var n = parseInt(s.dataset.step);
            if(n <= state.step) goStep(n);
        });
    });

    /* ═══ MOBILE BAR ═══ */
    var mobileBar = document.getElementById('ksk-mobile-bar');
    var heroEl = document.getElementById('ksk-hero');
    if(mobileBar && heroEl) {
        var obs = new IntersectionObserver(function(entries){ mobileBar.classList.toggle('is-visible', !entries[0].isIntersecting); }, {threshold:0});
        obs.observe(heroEl);
    }

    /* ═══ LIGHTBOX ═══ */
    var lb = document.getElementById('ksk-lightbox');
    if(lb) {
        var imgs = @json(array_values($galleryImages));
        var cur_lb = 0;
        var lbImg = lb.querySelector('.ksk-lightbox__img');
        var lbCnt = lb.querySelector('.ksk-lightbox__counter');
        function showLb(i){ cur_lb=((i%imgs.length)+imgs.length)%imgs.length; lbImg.src=imgs[cur_lb]; lbCnt.textContent=(cur_lb+1)+'/'+imgs.length; }
        document.querySelectorAll('[data-ksk-lb]').forEach(function(a){ a.addEventListener('click',function(e){ e.preventDefault(); showLb(parseInt(a.dataset.index||'0')); lb.hidden=false; document.body.style.overflow='hidden'; }); });
        lb.querySelector('.ksk-lightbox__close').addEventListener('click',function(){ lb.hidden=true; document.body.style.overflow=''; });
        lb.querySelector('.ksk-lightbox__prev').addEventListener('click',function(){ showLb(cur_lb-1); });
        lb.querySelector('.ksk-lightbox__next').addEventListener('click',function(){ showLb(cur_lb+1); });
        lb.addEventListener('click',function(e){ if(e.target===lb){lb.hidden=true;document.body.style.overflow='';} });
        document.addEventListener('keydown',function(e){ if(lb.hidden)return; if(e.key==='Escape'){lb.hidden=true;document.body.style.overflow='';} if(e.key==='ArrowLeft')showLb(cur_lb-1); if(e.key==='ArrowRight')showLb(cur_lb+1); });
    }

    /* ═══ PUBLIC API ═══ */
    window.ksk = {
        goStep: goStep,
        scrollToBuilder: function(){ document.getElementById('ksk-builder').scrollIntoView({behavior:'smooth',block:'start'}); }
    };

    /* ═══ HERO SLIDER ═══ */
    (function(){
        var slides = document.querySelectorAll('.ksk-hero__slide');
        var thumbs = document.querySelectorAll('.ksk-hero__thumb');
        if(slides.length <= 1) return;
        var cur = 0;
        var total = slides.length;
        var timer;

        function show(n) {
            cur = ((n % total) + total) % total;
            slides.forEach(function(s, i){ s.classList.toggle('is-active', i === cur); });
            thumbs.forEach(function(t, i){ t.classList.toggle('is-active', i === cur); });
        }
        function next(){ show(cur + 1); }
        function prev(){ show(cur - 1); }
        function startAuto(){ timer = setInterval(next, 5000); }
        function stopAuto(){ clearInterval(timer); }

        var prevBtn = document.getElementById('ksk-hero-prev');
        var nextBtn = document.getElementById('ksk-hero-next');
        if(prevBtn) prevBtn.addEventListener('click', function(){ stopAuto(); prev(); startAuto(); });
        if(nextBtn) nextBtn.addEventListener('click', function(){ stopAuto(); next(); startAuto(); });
        thumbs.forEach(function(t){
            t.addEventListener('click', function(){ stopAuto(); show(parseInt(t.dataset.slide)); startAuto(); });
        });
        startAuto();
    })();

    /* ═══ INIT ═══ */
    renderDates();
    if(HAS_PLACES) renderCities();
    updateCart();

})();
</script>
@endpush
