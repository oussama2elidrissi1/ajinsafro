@extends('layouts.front')

@section('title', 'Hôtels RateHawk')

@php
    $cssPath = public_path('css/ratehawk-hotels.css');
    $cssV = is_file($cssPath) ? filemtime($cssPath) : '1';
@endphp
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/ratehawk-hotels.css') }}?v={{ $cssV }}">
@endpush

@section('content')
    <div class="rh-page">
        <header class="rh-hero">
            <div class="rh-inner">
                <p class="rh-kicker">RateHawk · ETG API v3</p>
                <h1 class="rh-title">Trouver un hôtel</h1>
                <p class="rh-lead">
                    Indiquez une <strong>ville</strong> : résolution automatique du <code>region_id</code> (multicomplete), puis tarifs (SERP).
                    Vous pouvez aussi forcer un <strong>ID région</strong> connu.
                </p>

                {{-- URLs relatives : évite APP_URL sans port (ex. :8000) qui faisait perdre les query params --}}
                <form class="rh-form" method="get" action="{{ route('ratehawk.hotels.index', [], false) }}">
                    <input type="hidden" name="search" value="1">

                    <div class="rh-form-grid">
                        <div class="rh-field rh-field--city">
                            <label for="city">Destination (ville ou lieu)</label>
                            <div class="rh-ac-wrap">
                                <input
                                    type="text"
                                    name="city"
                                    id="city"
                                    value="{{ old('city', $city ?? '') }}"
                                    placeholder="ex. Marrakech, Paris, Berlin…"
                                    class="rh-input"
                                    autocomplete="off"
                                    data-autocomplete-url="{{ $autocompleteUrl }}"
                                >
                                <div id="rh-ac-dropdown" class="rh-ac-dropdown rh-hidden" role="listbox" aria-label="Suggestions"></div>
                            </div>
                        </div>
                        <div class="rh-field">
                            <label for="region_id">ID région (optionnel)</label>
                            <input
                                type="number"
                                name="region_id"
                                id="region_id"
                                value="{{ old('region_id', $regionId) }}"
                                min="1"
                                placeholder="Prioritaire sur la ville"
                                class="rh-input"
                                title="Si renseigné, la recherche utilise cet ID sans multicomplete"
                            >
                        </div>
                        <div class="rh-field">
                            <label for="checkin">Arrivée</label>
                            <input type="date" name="checkin" id="checkin" value="{{ old('checkin', $checkin) }}" class="rh-input" required>
                        </div>
                        <div class="rh-field">
                            <label for="checkout">Départ</label>
                            <input type="date" name="checkout" id="checkout" value="{{ old('checkout', $checkout) }}" class="rh-input" required>
                        </div>
                        <div class="rh-field rh-field--narrow">
                            <label for="adults">Adultes</label>
                            <input type="number" name="adults" id="adults" value="{{ old('adults', $adults) }}" min="1" max="6" class="rh-input" required>
                        </div>
                        <div class="rh-field rh-field--action">
                            <button type="submit" class="rh-btn">Rechercher</button>
                        </div>
                    </div>
                </form>
            </div>
        </header>

        <main class="rh-main">
            <div class="rh-inner">
                @if ($errors->any())
                    <div class="rh-alert rh-alert--error" role="alert">
                        <ul class="rh-alert-list">
                            @foreach ($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($error)
                    <div class="rh-alert {{ ! empty($ratehawkIpAccessDenied) ? 'rh-alert--warning' : 'rh-alert--error' }}" role="alert">
                        <strong class="rh-alert-title">{{ ! empty($ratehawkIpAccessDenied) ? 'Accès refusé' : 'Erreur' }}</strong>
                        <p class="rh-alert-text mb-0">{{ $error }}</p>
                    </div>
                @endif

                @if (config('app.debug'))
                    <div class="rh-debug-panel">
                        <strong class="rh-debug-title">Debug (APP_DEBUG=true)</strong>
                        <ul class="rh-debug-list">
                            <li><span>Ville</span> <code>{{ $city !== '' ? $city : '—' }}</code></li>
                            <li><span>region_id</span> <code>{{ $regionId !== null ? $regionId : '—' }}</code></li>
                            <li><span>Hôtels affichés</span> <code>{{ count($hotels ?? []) }}</code></li>
                            <li><span>total_hotels (API)</span> <code>{{ $totalHotels !== null ? $totalHotels : '—' }}</code></li>
                            <li><span>Recherche lancée</span> <code>{{ $searchRequested ? 'oui' : 'non' }}</code></li>
                            <li><span>Query string</span> <code>{{ request()->getQueryString() ?: '—' }}</code></li>
                        </ul>
                        @if (! empty($ratehawkAccessDeniedDebug))
                            <div class="rh-debug-access-denied" role="region" aria-label="Détail refus IP RateHawk">
                                <strong class="rh-debug-access-denied__title">Refus d’accès RateHawk (<code>not_allowed_host</code>)</strong>
                                <ul class="rh-debug-list rh-debug-list--tight mt-2">
                                    <li><span>error</span> <code>{{ $ratehawkAccessDeniedDebug['error'] ?? $ratehawkAccessDeniedDebug['api_error'] ?? '—' }}</code></li>
                                    <li><span>validation_error</span> <code>{{ $ratehawkAccessDeniedDebug['validation_error'] ?? '—' }}</code></li>
                                    <li><span>http_status</span> <code>{{ isset($ratehawkAccessDeniedDebug['http_status']) ? $ratehawkAccessDeniedDebug['http_status'] : '—' }}</code></li>
                                    <li><span>IP (extraite)</span> <code>{{ $ratehawkAccessDeniedDebug['ip'] ?? '—' }}</code></li>
                                </ul>
                            </div>
                        @endif
                        @if (! empty($apiDebug))
                            <pre class="rh-debug-pre">@json($apiDebug, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)</pre>
                        @endif
                    </div>
                @endif

                <section class="rh-results" aria-label="Résultats">
                    @if ($configured && $searchRequested && ! $error && $totalHotels !== null && count($hotels) > 0)
                        <div class="rh-results-head">
                            <h2 class="rh-results-title">Résultats</h2>
                            <p class="rh-results-sub">
                                <span class="font-semibold text-slate-900">{{ $totalHotels }}</span> proposition(s)
                                @if (! empty($resolvedLabel ?? null))
                                    · {{ $resolvedLabel }}
                                    @if (! empty($regionId))
                                        <span class="font-mono text-slate-600">#{{ $regionId }}</span>
                                    @endif
                                @elseif (! empty($regionId))
                                    · région <span class="font-mono">#{{ $regionId }}</span>
                                @endif
                            </p>
                        </div>
                    @endif

                    @if ($configured && $searchRequested && ! $error && $totalHotels !== null && count($hotels) === 0)
                        <div class="rh-alert rh-alert--empty">
                            <p class="rh-empty-title mb-1">Aucun hôtel trouvé pour cette recherche.</p>
                            <p class="text-slate-500 mb-0 text-sm">Modifiez les dates ou la destination.</p>
                        </div>
                    @endif

                    @if (count($hotels ?? []) > 0)
                        <div class="rh-grid">
                            @foreach ($hotels as $hotel)
                                <article class="rh-card">
                                    <div class="rh-card__media">
                                        <img
                                            src="{{ $hotel['image'] ?? $placeholderImage }}"
                                            alt="{{ $hotel['name'] }}"
                                            loading="lazy"
                                            width="640"
                                            height="400"
                                            onerror="this.onerror=null;this.src='{{ $placeholderImage }}';"
                                        >
                                        @if ($hotel['stars'] !== null)
                                            <span class="rh-badge">{{ number_format($hotel['stars'], 1) }} ★</span>
                                        @elseif ($hotel['rating'] !== null)
                                            <span class="rh-badge">{{ number_format($hotel['rating'], 1) }}</span>
                                        @endif
                                    </div>
                                    <div class="rh-card__body">
                                        <h3 class="rh-card__title">{{ $hotel['name'] }}</h3>
                                        @if (! empty($hotel['address']))
                                            <p class="rh-card__line">{{ $hotel['address'] }}</p>
                                        @endif
                                        @if (! empty($hotel['region_name']))
                                            <p class="rh-card__line rh-card__line--muted">{{ $hotel['region_name'] }}</p>
                                        @endif
                                        @if (! empty($hotel['meal']))
                                            <p class="rh-card__meta">{{ $hotel['meal'] }}</p>
                                        @endif
                                        <div class="rh-card__footer">
                                            <div class="rh-price">
                                                @if ($hotel['price'] !== null)
                                                    {{ number_format($hotel['price'], 0, ',', ' ') }}
                                                    <small>{{ $hotel['currency'] }}</small>
                                                @else
                                                    <small class="text-slate-500">Tarif sur demande</small>
                                                @endif
                                            </div>
                                            <div class="rh-card__actions">
                                                <a href="#" class="rh-link" onclick="return false;">Voir détails</a>
                                                <a href="#" class="rh-btn rh-btn--outline" onclick="return false;">Réserver</a>
                                            </div>
                                        </div>
                                        @if (($hotel['hid'] ?? 0) > 0)
                                            <p class="rh-card__hid">Hôtel #{{ $hotel['hid'] }}</p>
                                        @endif
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </section>

                @if (! $configured)
                    <p class="rh-neutral text-center">
                        Configuration API RateHawk manquante : renseignez
                        <code>RATEHAWK_KEY_ID</code>, <code>RATEHAWK_API_KEY</code> et <code>RATEHAWK_API_BASE_URL</code> dans votre fichier <code>.env</code>.
                    </p>
                @elseif (! $searchRequested)
                    <p class="rh-neutral text-center">Renseignez une destination et cliquez sur <strong>Rechercher</strong>.</p>
                @endif

                <p class="rh-footnote">ETG : multicomplete → region_id → SERP région.</p>
            </div>
        </main>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    var input = document.getElementById('city');
    var dd = document.getElementById('rh-ac-dropdown');
    var url = input && input.getAttribute('data-autocomplete-url');
    if (!input || !dd || !url) return;

    var t = null;

    function hide() {
        dd.classList.add('rh-hidden');
        dd.innerHTML = '';
    }

    function render(list) {
        dd.innerHTML = '';
        if (!list.length) { hide(); return; }
        list.forEach(function (s) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'rh-ac-item';
            btn.setAttribute('role', 'option');
            var meta = [s.type, s.country_code].filter(Boolean).join(' · ');
            btn.innerHTML = '<span class="rh-ac-item__title">' + escapeHtml(s.label) + '</span>' +
                (meta ? '<span class="rh-ac-item__meta">' + escapeHtml(meta) + '</span>' : '') +
                '<span class="rh-ac-item__id">#' + s.region_id + '</span>';
            btn.addEventListener('click', function () {
                input.value = s.label.split(' — ')[0].trim();
                var rid = document.getElementById('region_id');
                if (rid) rid.value = String(s.region_id);
                hide();
            });
            dd.appendChild(btn);
        });
        dd.classList.remove('rh-hidden');
    }

    function escapeHtml(str) {
        var d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    input.addEventListener('input', function () {
        var q = input.value.trim();
        clearTimeout(t);
        if (q.length < 2) { hide(); return; }
        t = setTimeout(function () {
            fetch(url + '?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    render(data.suggestions || []);
                })
                .catch(function () { hide(); });
        }, 280);
    });

    document.addEventListener('click', function (e) {
        if (!input.contains(e.target) && !dd.contains(e.target)) hide();
    });
})();
</script>
@endpush
