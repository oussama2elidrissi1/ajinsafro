@php
    $hasLaravel = ! empty($row['voyage_id']);
    $wpPostId = $row['wp_post_id'] ?? null;
    $q = $hasLaravel ? array_filter([
        'voyage_id' => $row['voyage_id'],
        'travel_date_id' => $row['travel_date_id'] ?? null,
    ], fn ($value) => $value !== null && $value !== '') : [];
    $reserveUrl = $hasLaravel ? route('admin.reservations.create', $q) : '#';
    $editTourUrl = $wpPostId ? route('admin.circuits.voyages.edit', $wpPostId) : null;
    $hasDepDate = ! empty($row['departure_date']);
    $typeKey = $row['type'] ?? 'package';
    $badgeClass = match ($typeKey) {
        'package' => 'ws-offer-card__type ws-offer-card__type--package',
        'vol' => 'ws-offer-card__type ws-offer-card__type--vol',
        default => 'ws-offer-card__type ws-offer-card__type--hotel',
    };
    $typeShort = match ($typeKey) {
        'package' => 'Circuit',
        'vol' => 'Vol',
        default => 'Hébergement',
    };
    $rowAccent = match ($typeKey) {
        'package' => 'ws-catalog-row--package',
        'vol' => 'ws-catalog-row--vol',
        default => 'ws-catalog-row--hotel',
    };
    $stats = $row['stats'] ?? ['validee' => 0, 'en_cours' => 0, 'annulee' => 0];
    $statVal = (int) ($stats['validee'] ?? 0);
    $statPending = (int) ($stats['en_cours'] ?? 0);
    $statCancel = (int) ($stats['annulee'] ?? 0);
    $statTotal = $statVal + $statPending + $statCancel;
    $isPast = ! empty($row['departure_is_past']);
    $placesState = $row['places_state'] ?? null;
    $placesTotal = $row['places_total'] ?? null;
    $placesLines = $row['places_lines'] ?? [];
    $placesSearchBits = '';
    if ($typeKey === 'package' && $hasLaravel && ($placesState ?? '') === 'ok' && $placesTotal !== null) {
        $placesSearchBits = ' places '.$placesTotal;
        foreach ($placesLines as $pl) {
            $placesSearchBits .= ' '.($pl['room_type'] ?? '').' '.($pl['product'] ?? '');
        }
    }
    $summary = trim((string) ($row['summary'] ?? ''));
    $wsSearchBlob = \Illuminate\Support\Str::lower(trim(
        ($row['name'] ?? '')
        . ' ' . ($row['code'] ?? '')
        . ' ' . ($row['subtitle'] ?? '')
        . ' ' . ($row['voyage_destination'] ?? '')
        . ' ' . ($row['price_label'] ?? '')
        . ' ' . $summary
        . $placesSearchBits
    ));
    $pkgDepCanceled = $typeKey === 'package' && ! empty($row['departure_is_canceled']);
    $reserveLabel = $typeKey === 'vol' ? 'Réserver vol' : 'Réserver';
    $modalDetail = $row['modal_detail'] ?? null;
    $departures = collect($modalDetail['departures'] ?? [])->values();
    $visibleDepartures = $departures->take(3);
    $extraDepartureCount = max(0, $departures->count() - $visibleDepartures->count());
    $singleDepartureReserveUrl = $departures->count() === 1 ? data_get($departures->first(), 'routes.reserve') : null;
    $wsAvail = $row['ws_avail'] ?? 'na';
    $wsUpcoming = ! empty($row['ws_has_future']);
    $imageUrl = ! empty($row['image_url']) ? (string) $row['image_url'] : null;

    if (! $hasDepDate) {
        $dateStatus = 'none';
    } elseif ($isPast) {
        $dateStatus = 'past';
    } else {
        $dateStatus = 'upcoming';
    }

    $depTs = $hasDepDate ? \Carbon\Carbon::parse($row['departure_date'])->timestamp : 0;
    $priceLabel = $row['price_label'] ?? '';
    $priceSort = 0;
    if ($priceLabel !== '') {
        $digits = preg_replace('/[^\d]/', '', $priceLabel);
        $priceSort = $digits !== '' ? (int) $digits : 0;
    }
    $placesSort = ($placesState === 'ok' && $placesTotal !== null) ? (int) $placesTotal : -1;

    $rowTitle = trim(($row['name'] ?? '').($hasLaravel && $typeKey === 'package' ? ' · #'.$row['voyage_id'] : ''));

    $capText = 'À configurer';
    if ($typeKey === 'package' && $hasLaravel && ($placesState ?? '') === 'ok' && $placesTotal !== null) {
        $capText = number_format((int) $placesTotal, 0, ',', ' ');
    }

    if ($singleDepartureReserveUrl) {
        $reserveUrl = $singleDepartureReserveUrl;
    }

    $referenceBits = array_values(array_filter([
        $wpPostId ? 'WP #'.$wpPostId : null,
        $hasLaravel ? 'Laravel #'.$row['voyage_id'] : null,
    ]));

    $isNearFuture = false;
    if ($hasDepDate) {
        $depDay = \Carbon\Carbon::parse($row['departure_date'])->startOfDay();
        $today = \Carbon\Carbon::today();
        if ($depDay->gte($today) && $depDay->lte($today->copy()->addDays(30))) {
            $isNearFuture = true;
        }
    }

    $viewMode = $mode ?? 'card';
@endphp

@if($viewMode === 'table')
<tr class="ws-catalog-row ws-catalog-table-row {{ $rowAccent }} {{ $hasLaravel ? '' : 'ws-catalog-row--unlinked' }}{{ $isNearFuture ? ' ws-catalog-row--near' : '' }}"
    data-type="{{ $typeKey }}"
    data-row-code="{{ $row['code'] }}"
    data-code="{{ $row['code'] }}"
    data-name="{{ $row['name'] }}"
    data-search="{{ e($wsSearchBlob) }}"
    data-dep="{{ $row['departure_date'] ? \Carbon\Carbon::parse($row['departure_date'])->format('Y-m-d') : '' }}"
    data-ws-avail="{{ e($wsAvail) }}"
    data-ws-upcoming="{{ $wsUpcoming ? '1' : '0' }}"
    data-date-status="{{ $dateStatus }}"
    data-stats-validee="{{ $statVal }}"
    data-stats-pending="{{ $statPending }}"
    data-stats-total="{{ $statTotal }}"
    data-sort-dep="{{ $depTs }}"
    data-sort-price="{{ $priceSort }}"
    data-sort-places="{{ $placesSort }}"
    title="{{ e($rowTitle) }}">
    <td class="ws-td ws-td--ref" data-label="Réf">
        <span class="ws-td__code">{{ $row['code'] }}</span>
        @if($typeKey === 'package' && ! $hasLaravel)
            <span class="ws-td__hint ws-td__hint--warn">Non lié</span>
        @endif
    </td>
    <td class="ws-td ws-td--offer" data-label="Voyage">
        <div class="ws-td__offer-compact">
            <span class="ws-td__type-txt">{{ $typeShort }}</span>
            <span class="ws-td__title ws-td__title--clamp">{{ $row['name'] }}</span>
        </div>
    </td>
    <td class="ws-td ws-td--dep" data-label="Départ">
        @if($hasDepDate)
            <span class="ws-td__dep-date">{{ \Carbon\Carbon::parse($row['departure_date'])->locale('fr')->translatedFormat('d M Y') }}</span>
            @if($pkgDepCanceled)
                <span class="ws-td__dep-note">Annulé</span>
            @endif
        @else
            <span class="ws-td__muted">—</span>
        @endif
    </td>
    <td class="ws-td ws-td--price" data-label="Prix">
        @if(! empty($row['price_label']))
            <strong class="ws-td__price">{{ $row['price_label'] }}</strong>
        @else
            <span class="ws-td__muted">—</span>
        @endif
    </td>
    <td class="ws-td ws-td--cap" data-label="Capacité">
        <span class="ws-td__cap-cell" title="Capacité basée sur la répartition des chambres">
            {{ is_numeric(str_replace(' ', '', $capText)) ? $capText.' places' : $capText }}
        </span>
    </td>
    <td class="ws-td ws-td--actions" data-label="Actions">
        <div class="ws-td__actions">
            @if(!empty($modalDetail))
                    <button type="button"
                        class="ws-btn ws-btn--secondary ws-btn--sm ws-btn--iconish btn-ws-open-detail btn-view"
                    data-row-code="{{ e($row['code']) }}"
                    title="Détail">
                    <i class="fas fa-eye" aria-hidden="true"></i><span>Voir</span>
                </button>
            @endif
            @can('reservations.view')
                @if($hasLaravel)
                    <button type="button"
                        class="ws-btn ws-btn--primary ws-btn--sm ws-btn--iconish btn-ws-open-reserve"
                        data-row-code="{{ e($row['code']) }}"
                        title="{{ $reserveLabel }}">
                        @if($typeKey === 'vol')
                            <i class="fas fa-plane-departure" aria-hidden="true"></i>
                        @else
                            <i class="fas fa-suitcase-rolling" aria-hidden="true"></i>
                        @endif
                        <span>{{ $reserveLabel }}</span>
                    </button>
                @else
                    <a href="{{ $editTourUrl ?: '#' }}"
                        class="ws-btn ws-btn--sm ws-btn--iconish {{ $editTourUrl ? 'ws-btn--ghost' : 'ws-btn--disabled' }}"
                        {!! $editTourUrl ? '' : 'aria-disabled="true"' !!}
                        title="Associer la fiche Laravel">
                        <i class="fas fa-link" aria-hidden="true"></i><span>Lier</span>
                    </a>
                @endif
            @endcan
        </div>
    </td>
</tr>
@else
<article class="ws-catalog-row ws-offer-card ws-offer-card--compact {{ $rowAccent }} {{ $hasLaravel ? '' : 'ws-catalog-row--unlinked' }}{{ $isNearFuture ? ' ws-catalog-row--near' : '' }}"
    data-type="{{ $typeKey }}"
    data-row-code="{{ $row['code'] }}"
    data-code="{{ $row['code'] }}"
    data-name="{{ $row['name'] }}"
    data-search="{{ e($wsSearchBlob) }}"
    data-dep="{{ $row['departure_date'] ? \Carbon\Carbon::parse($row['departure_date'])->format('Y-m-d') : '' }}"
    data-ws-avail="{{ e($wsAvail) }}"
    data-ws-upcoming="{{ $wsUpcoming ? '1' : '0' }}"
    data-date-status="{{ $dateStatus }}"
    data-stats-validee="{{ $statVal }}"
    data-stats-pending="{{ $statPending }}"
    data-stats-total="{{ $statTotal }}"
    data-sort-dep="{{ $depTs }}"
    data-sort-price="{{ $priceSort }}"
    data-sort-places="{{ $placesSort }}"
    title="{{ e($rowTitle) }}">

    <div class="ws-offer-card__media ws-offer-card__media--compact{{ $imageUrl ? ' ws-offer-card__media--has-img' : '' }}">
        <div class="ws-offer-card__media-fill{{ $imageUrl ? ' ws-offer-card__media-fill--has-img' : '' }}">
            <div class="ws-offer-card__placeholder" aria-hidden="true">
                <span class="ws-offer-card__placeholder-icon">
                    @if($typeKey === 'vol')
                        <i class="fas fa-plane"></i>
                    @elseif($typeKey === 'hebergement')
                        <i class="fas fa-hotel"></i>
                    @else
                        <i class="fas fa-map-location-dot"></i>
                    @endif
                </span>
                <span class="ws-offer-card__placeholder-text">Visuel indisponible</span>
            </div>
            @if($imageUrl)
                <img src="{{ e($imageUrl) }}" alt="" class="ws-offer-card__img" loading="lazy" decoding="async"
                     width="320" height="180"
                     onerror="this.closest('.ws-offer-card__media-fill')?.classList.add('is-broken')">
            @endif
        </div>
    </div>

    <div class="ws-offer-card__body ws-offer-card__body--compact">
        <div class="ws-offer-card__compact-head">
            <span class="{{ $badgeClass }} ws-offer-card__type--compact">{{ $typeShort }}</span>
            <span class="ws-offer-card__code ws-offer-card__code--compact">{{ $row['code'] }}</span>
            @if($typeKey === 'package' && ! $hasLaravel)
                <span class="ws-offer-card__unlink ws-offer-card__unlink--inline">Non lié</span>
            @endif
        </div>
        <h3 class="ws-offer-card__title ws-offer-card__title--compact">{{ $row['name'] }}</h3>
        @if($referenceBits !== [])
            <p class="ws-offer-card__refs">{{ implode(' · ', $referenceBits) }}</p>
        @endif
        <div class="ws-offer-card__meta-list" role="group" aria-label="Tarif et capacité">
            <div class="ws-offer-card__meta-item ws-offer-card__meta-item--price">
                <span class="ws-offer-card__meta-label"><i class="fas fa-coins" aria-hidden="true"></i>Prix à partir de</span>
                <span class="ws-offer-card__meta-value ws-offer-card__meta-value--price">
                    @if(! empty($row['price_label']))
                        {{ $row['price_label'] }}
                    @else
                        <span class="ws-offer-card__meta-muted">Non renseigné</span>
                    @endif
                </span>
            </div>
            <div class="ws-offer-card__meta-item ws-offer-card__meta-item--cap">
                <span class="ws-offer-card__meta-label"><i class="fas fa-users" aria-hidden="true"></i>Capacité</span>
                <span class="ws-offer-card__meta-value" title="Capacité basée sur la répartition des chambres">
                    @if($typeKey === 'package' && $hasLaravel)
                        {{ is_numeric(str_replace(' ', '', $capText)) ? $capText.' places' : $capText }}
                    @else
                        <span class="ws-offer-card__meta-muted">Non applicable</span>
                    @endif
                </span>
            </div>
        </div>
        @if($typeKey === 'package' && $departures->isNotEmpty())
            <div class="ws-offer-card__departures">
                <div class="ws-offer-card__section-label">Départs disponibles</div>
                <ul class="ws-offer-card__departure-list" aria-label="Départs disponibles">
                    @foreach($visibleDepartures as $departure)
                        @php
                            $statusClass = match ($departure['status_key'] ?? 'unknown') {
                                'available' => 'ws-offer-card__departure-status--ok',
                                'almost_full' => 'ws-offer-card__departure-status--warn',
                                'full' => 'ws-offer-card__departure-status--full',
                                default => 'ws-offer-card__departure-status--muted',
                            };
                            $statusLabel = !empty($departure['is_past'])
                                ? 'Passé'
                                : (($departure['status_label'] ?? '') === 'Disponible' ? 'À venir' : ($departure['status_label'] ?? 'À venir'));
                        @endphp
                        <li class="ws-offer-card__departure-item">
                            <span class="ws-offer-card__departure-date">{{ $departure['date_label'] ?? 'Date non renseignée' }}</span>
                            <span class="ws-offer-card__departure-status {{ $statusClass }}">— {{ $statusLabel }}</span>
                        </li>
                    @endforeach
                </ul>
                @if($extraDepartureCount > 0)
                    <button type="button" class="ws-offer-card__more btn-ws-open-detail" data-row-code="{{ e($row['code']) }}">
                        + {{ $extraDepartureCount }} autre(s) départ(s)
                    </button>
                @endif
            </div>
        @elseif($hasDepDate)
            <div class="ws-offer-card__departures">
                <div class="ws-offer-card__section-label">Départ</div>
                <div class="ws-offer-card__departure-item ws-offer-card__departure-item--solo">
                    <span class="ws-offer-card__departure-date">{{ \Carbon\Carbon::parse($row['departure_date'])->locale('fr')->translatedFormat('d M Y') }}</span>
                    @if($pkgDepCanceled)
                        <span class="ws-offer-card__departure-status ws-offer-card__departure-status--muted">Annulé</span>
                    @endif
                </div>
            </div>
        @endif
        <div class="ws-offer-card__actions ws-offer-card__actions--compact" role="group" aria-label="Actions">
            @if(!empty($modalDetail))
                <button type="button"
                    class="ws-btn ws-btn--secondary ws-btn--sm btn-ws-open-detail btn-view"
                    data-row-code="{{ e($row['code']) }}"
                    title="Détail">
                    <i class="fas fa-eye" aria-hidden="true"></i><span>Voir</span>
                </button>
            @endif
        </div>
    </div>
</article>
@endif
