@extends('layouts.front')

@section('title', data_get($detail, 'name', 'Hôtel').' — RapidAPI')

@php
    $cssPath = public_path('css/rapidapi-hotels.css');
    $cssV = is_file($cssPath) ? filemtime($cssPath) : '1';
@endphp
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/rapidapi-hotels.css') }}?v={{ $cssV }}">
@endpush

@section('content')
    <div class="rap-page rap-page--detail">
        <header class="rap-detail-hero">
            <div class="rap-inner">
                <p class="rap-kicker"><a href="{{ $backUrl }}" class="rap-back-link">← Retour aux résultats</a></p>
                @if ($detail)
                    <h1 class="rap-title">{{ $detail['name'] }}</h1>
                    @if (($detail['stars'] ?? null) !== null && (int) $detail['stars'] > 0)
                        <div class="rap-stars rap-stars--large" aria-label="{{ (int) $detail['stars'] }} étoiles">
                            @for ($i = 0; $i < (int) $detail['stars']; $i++)
                                <span class="rap-stars__icon" aria-hidden="true">★</span>
                            @endfor
                        </div>
                    @endif
                    <p class="rap-detail-meta">
                        @if ($detail['city'] !== '')
                            <span>{{ $detail['city'] }}</span>
                        @endif
                        @if ($detail['address'] !== '')
                            <span class="rap-detail-meta__sep">·</span>
                            <span>{{ $detail['address'] }}</span>
                        @endif
                    </p>
                    @if (($detail['rating'] ?? null) !== null)
                        <div class="rap-score-pill rap-score-pill--inline" title="{{ $detail['rating_label'] }}">
                            <span class="rap-score-pill__value">{{ number_format($detail['rating'], 1) }}</span>
                            @if ($detail['rating_label'] !== '')
                                <span class="rap-score-pill__word">{{ $detail['rating_label'] }}</span>
                            @endif
                        </div>
                    @endif
                @else
                    <h1 class="rap-title">Hôtel</h1>
                @endif
            </div>
        </header>

        <main class="rap-main rap-main--detail">
            <div class="rap-inner">
                @if (! $configured)
                    <p class="rap-neutral">{{ $error }}</p>
                @elseif ($error)
                    <div class="rap-alert rap-alert--error" role="alert">{{ $error }}</div>
                @elseif ($detail)
                    @php
                        $heroSrc = ! empty($detail['hero_image']) ? $detail['hero_image'] : '/images/hotel-placeholder.svg';
                    @endphp
                    <div class="rap-detail-cover">
                        <img
                            src="{{ $heroSrc }}"
                            alt=""
                            loading="eager"
                            decoding="async"
                            class="rap-detail-cover__img"
                            onerror="this.onerror=null;this.src='/images/hotel-placeholder.svg';"
                        >
                    </div>

                    @if (! empty($detail['photos']) && count($detail['photos']) > 1)
                        <div class="rap-detail-gallery">
                            @foreach (array_slice($detail['photos'], 0, 12) as $src)
                                <button type="button" class="rap-detail-thumb" tabindex="-1">
                                    <img src="{{ $src }}" alt="" loading="lazy" onerror="this.onerror=null;this.src='/images/hotel-placeholder.svg';">
                                </button>
                            @endforeach
                        </div>
                    @endif

                    @if (($detail['price'] ?? null) !== null)
                        <section class="rap-detail-section rap-detail-section--price">
                            <p class="rap-detail-price">
                                <span class="rap-detail-price-from">à partir de</span>
                                <span class="rap-detail-price-value">{{ number_format($detail['price'], 0, ',', ' ') }} <small>{{ $detail['currency'] ?? 'EUR' }}</small></span>
                            </p>
                        </section>
                    @endif

                    @if (! empty($detail['description']))
                        <section class="rap-detail-section">
                            <h2 class="rap-detail-h2">Présentation</h2>
                            <div class="rap-detail-desc">{!! nl2br(e($detail['description'])) !!}</div>
                        </section>
                    @endif

                    @if (! empty($detail['facilities']))
                        <section class="rap-detail-section">
                            <h2 class="rap-detail-h2">Équipements</h2>
                            <ul class="rap-facilities">
                                @foreach (array_slice($detail['facilities'], 0, 40) as $f)
                                    <li>{{ $f }}</li>
                                @endforeach
                            </ul>
                        </section>
                    @endif

                    @if (! empty($detail['booking_url']))
                        <p class="rap-detail-cta">
                            <a href="{{ $detail['booking_url'] }}" class="rap-btn rap-btn--outline" target="_blank" rel="noopener noreferrer">Voir sur Booking.com</a>
                        </p>
                    @endif
                @endif

                @if (! empty($apiDebug))
                    <details class="rap-debug-wrap" @if (! empty($error)) open @endif>
                        <summary class="rap-debug-summary">{{ config('app.debug') ? 'Debug API (développement)' : 'Détails technique (réponse API)' }}</summary>
                        <pre class="rap-debug">@json($apiDebug, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)</pre>
                    </details>
                @endif
            </div>
        </main>
    </div>
@endsection
