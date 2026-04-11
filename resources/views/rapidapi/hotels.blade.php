@extends('layouts.front')

@section('title', 'Hôtels — RapidAPI Booking')

@php
    $cssPath = public_path('css/rapidapi-hotels.css');
    $cssV = is_file($cssPath) ? filemtime($cssPath) : '1';
@endphp
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/rapidapi-hotels.css') }}?v={{ $cssV }}">
@endpush

@section('content')
    <div class="rap-page">
        <header class="rap-hero">
            <div class="rap-inner">
                <p class="rap-kicker">RapidAPI · Booking COM</p>
                <h1 class="rap-title">Rechercher un hôtel</h1>
                <p class="rap-lead">
                    Saisissez une <strong>ville</strong> : résolution de la destination puis liste d’hébergements (test d’intégration).
                </p>

                <form class="rap-form" method="get" action="{{ route('rapidapi.hotels.index', [], false) }}">
                    <input type="hidden" name="search" value="1">
                    <div class="rap-form-grid">
                        <div class="rap-field rap-field--city">
                            <label for="rap-city">Ville</label>
                            <input type="text" name="city" id="rap-city" value="{{ old('city', $city ?? '') }}"
                                class="rap-input" placeholder="ex. Paris, Marrakech, Berlin…" required autocomplete="off">
                        </div>
                        <div class="rap-field">
                            <label for="rap-checkin">Arrivée</label>
                            <input type="date" name="checkin" id="rap-checkin" value="{{ old('checkin', $checkin) }}" class="rap-input" required>
                        </div>
                        <div class="rap-field">
                            <label for="rap-checkout">Départ</label>
                            <input type="date" name="checkout" id="rap-checkout" value="{{ old('checkout', $checkout) }}" class="rap-input" required>
                        </div>
                        <div class="rap-field rap-field--narrow">
                            <label for="rap-adults">Adultes</label>
                            <input type="number" name="adults" id="rap-adults" value="{{ old('adults', $adults) }}" min="1" max="6" class="rap-input" required>
                        </div>
                        <div class="rap-field rap-field--action">
                            <button type="submit" class="rap-btn">Rechercher</button>
                        </div>
                    </div>
                </form>
            </div>
        </header>

        <main class="rap-main">
            <div class="rap-inner">
                @if ($errors->any())
                    <div class="rap-alert rap-alert--error" role="alert">
                        <ul class="mb-0 pl-4">@foreach ($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
                    </div>
                @endif

                @if (! $configured)
                    <p class="rap-neutral">Ajoutez <code>RAPIDAPI_KEY</code> et vérifiez <code>RAPIDAPI_HOST</code> / <code>RAPIDAPI_BASE_URL</code> dans <code>.env</code>.</p>
                @elseif ($error)
                    <div class="rap-alert rap-alert--error" role="alert">{{ $error }}</div>
                @elseif ($configured && empty($searchRequested))
                    <p class="rap-neutral">Indiquez une ville et cliquez sur <strong>Rechercher</strong> pour tester le flux RapidAPI.</p>
                @endif

                @if ($configured && $searchRequested && $destinationLabel && ! $error)
                    <p class="rap-meta">
                        Destination : <strong>{{ $destinationLabel }}</strong>
                        @if ($destId !== null)
                            <span class="rap-meta-id">· dest_id <code>{{ $destId }}</code></span>
                        @endif
                        @if ($destType)
                            <span class="rap-meta-id">· {{ $destType }}</span>
                        @endif
                    </p>
                @endif

                @if (config('app.debug') && ! empty($apiDebug))
                    <details class="rap-debug-wrap">
                        <summary class="rap-debug-summary">Debug API (développement)</summary>
                        <pre class="rap-debug">@json($apiDebug, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)</pre>
                    </details>
                @endif

                @if (count($hotels ?? []) > 0)
                    <div class="rap-grid">
                        @foreach ($hotels as $hotel)
                            @php
                                $hotelPk = $hotel['id'] ?? data_get($hotel, 'raw.hotel_id');
                                $hotelDetailUrl = filled($hotelPk)
                                    ? route('rapidapi.hotels.show', [
                                        'hotelId' => $hotelPk,
                                        'city' => $city ?? request('city'),
                                        'checkin' => $checkin ?? request('checkin'),
                                        'checkout' => $checkout ?? request('checkout'),
                                        'adults' => $adults ?? request('adults'),
                                        'search' => 1,
                                    ], false)
                                    : '#';
                            @endphp
                            @if (filled($hotelPk))
                                <a href="{{ $hotelDetailUrl }}" class="rap-card rap-card--link">
                            @else
                                <div class="rap-card">
                            @endif
                                <div class="rap-card__media">
                                    <img
                                        src="{{ $hotel['image'] }}"
                                        alt="{{ $hotel['name'] }}"
                                        loading="lazy"
                                        decoding="async"
                                        onerror="this.onerror=null;this.src='/images/hotel-placeholder.svg';"
                                    >
                                    @if (($hotel['rating'] ?? null) !== null)
                                        <div class="rap-score-pill" title="{{ $hotel['rating_label'] }}">
                                            <span class="rap-score-pill__value">{{ number_format($hotel['rating'], 1) }}</span>
                                            @if ($hotel['rating_label'] !== '')
                                                <span class="rap-score-pill__word">{{ $hotel['rating_label'] }}</span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                                <div class="rap-card__body">
                                    <h2 class="rap-card__title">{{ $hotel['name'] }}</h2>
                                    @if (($hotel['stars'] ?? null) !== null && (int) $hotel['stars'] > 0)
                                        <div class="rap-stars" aria-label="{{ (int) $hotel['stars'] }} étoiles sur 5">
                                            @for ($i = 0; $i < (int) $hotel['stars']; $i++)
                                                <span class="rap-stars__icon" aria-hidden="true">★</span>
                                            @endfor
                                        </div>
                                    @endif
                                    @if ($hotel['city'] !== '')
                                        <p class="rap-card__sub rap-card__city">{{ $hotel['city'] }}</p>
                                    @elseif ($hotel['address'] !== '')
                                        <p class="rap-card__sub rap-card__city">{{ $hotel['address'] }}</p>
                                    @endif
                                    @if ($hotel['rating_label'] !== '' && ($hotel['rating'] ?? null) === null)
                                        <p class="rap-card__rating-label">{{ $hotel['rating_label'] }}</p>
                                    @endif
                                    <div class="rap-card__foot">
                                        <div class="rap-card__price-block">
                                            @if ($hotel['price'] !== null)
                                                <span class="rap-price-from">à partir de</span>
                                                <span class="rap-price">{{ number_format($hotel['price'], 0, ',', ' ') }} <small>{{ $hotel['currency'] }}</small></span>
                                            @else
                                                <span class="rap-price rap-price--muted">Prix sur demande</span>
                                            @endif
                                        </div>
                                        <span class="rap-soon">Voir la fiche</span>
                                    </div>
                                </div>
                            @if (filled($hotelPk))
                                </a>
                            @else
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
        </main>
    </div>
@endsection
