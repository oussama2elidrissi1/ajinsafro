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

    // Commercial data
    $commercial = $row['commercial'] ?? [];
    $comBadge = $commercial['badge'] ?? null;
    $comPriority = $commercial['priorite_vente'] ?? 'standard';
    $comCity = $commercial['ville_depart'] ?? '';
    $comSold = $commercial['places_vendues'] ?? 0;
    $comRemaining = $commercial['places_restantes'] ?? null;
    $comCapacity = $commercial['capacity_total'] ?? null;
    $comFillRate = $commercial['taux_remplissage'] ?? null;
    $comNextDep = $commercial['prochaine_date_depart'] ?? null;
    $comDaysUntil = $commercial['jours_avant_depart'] ?? null;
    $comAvailStatus = $commercial['statut_disponibilite'] ?? 'unknown';
    $comTopDates = $commercial['top_dates'] ?? [];
    $isSellable = $commercial['is_sellable'] ?? false;

    // Per-departure overrides (table + card per-departure mode)
    $departureData = $departure ?? null;
    if ($departureData) {
        $depRowStatVal = (int) data_get($departureData, 'reservations.validee', 0);
        $depRowStatPending = (int) data_get($departureData, 'reservations.en_cours', 0);
        $depRowStatCancel = (int) data_get($departureData, 'reservations.annulee', 0);
        $depRowSoldVal = (int) data_get($departureData, 'pax.validee', 0);
        $depRowSoldPending = (int) data_get($departureData, 'pax.en_cours', 0);
        $depRowRemaining = data_get($departureData, 'remaining');
        $depRowCapacity = data_get($departureData, 'capacity');
        $depRowFillRate = data_get($departureData, 'fill_pct');
        $depRowDateLabel = data_get($departureData, 'date_label');
        $depRowDateIso = data_get($departureData, 'date_iso');
        $depRowRouteReserve = data_get($departureData, 'routes.reserve');
        $depRowIsPast = !empty($departureData['is_past']);
        $depRowStatusKey = $departureData['status_key'] ?? 'unknown';
        $depRowTravelDateId = data_get($departureData, 'travel_date_id');
        $depRowAlerts = collect(data_get($departureData, 'alerts', []))->filter()->values()->all();
    } else {
        $depRowStatVal = $statVal;
        $depRowStatPending = $statPending;
        $depRowStatCancel = $statCancel;
        $depRowSoldVal = $statVal;
        $depRowSoldPending = $statPending;
        $depRowRemaining = $comRemaining;
        $depRowCapacity = $comCapacity;
        $depRowFillRate = $comFillRate;
        $depRowDateLabel = $comNextDep ?: ($row['departure_date'] ?? null);
        $depRowDateIso = $row['departure_date'] ? \Carbon\Carbon::parse($row['departure_date'])->format('Y-m-d') : null;
        $depRowRouteReserve = $reserveUrl;
        $depRowIsPast = $isPast;
        $depRowStatusKey = $comAvailStatus;
        $depRowTravelDateId = $row['travel_date_id'] ?? null;
        $depRowAlerts = [];
    }

    $depRowSoldTitleParts = [
        $depRowSoldVal.' place'.($depRowSoldVal > 1 ? 's' : '').' confirmée'.($depRowSoldVal > 1 ? 's' : ''),
    ];
    if ($depRowStatVal > 0) {
        $depRowSoldTitleParts[] = 'dans '.$depRowStatVal.' dossier'.($depRowStatVal > 1 ? 's' : '');
    }
    $depRowSoldTitleParts[] = $depRowSoldPending.' place'.($depRowSoldPending > 1 ? 's' : '').' en attente';
    if ($depRowStatPending > 0) {
        $depRowSoldTitleParts[] = 'dans '.$depRowStatPending.' dossier'.($depRowStatPending > 1 ? 's' : '');
    }
    $depRowSoldTitle = implode(' - ', $depRowSoldTitleParts);

    $isSellableForRow = $isSellable;
    if ($departureData) {
        $isSellableForRow = $isSellable
            && ! $depRowIsPast
            && $depRowStatusKey !== 'full'
            && ($depRowRemaining === null || $depRowRemaining > 0);
    }

    $progressColor = 'bg-emerald-500';
    $progressFillRate = ($viewMode === 'table' && $departureData) ? $depRowFillRate : $comFillRate;
    if ($progressFillRate !== null) {
        if ($progressFillRate >= 90) $progressColor = 'bg-red-500';
        elseif ($progressFillRate >= 75) $progressColor = 'bg-orange-500';
        elseif ($progressFillRate >= 50) $progressColor = 'bg-amber-500';
    }

    $badgeHtmlClass = match ($comBadge) {
        'TOP VENTE' => 'ws-commercial-badge ws-commercial-badge--top',
        'À POUSSER' => 'ws-commercial-badge ws-commercial-badge--push',
        'FAIBLE STOCK' => 'ws-commercial-badge ws-commercial-badge--low',
        'DÉPART PROCHE' => 'ws-commercial-badge ws-commercial-badge--near',
        'FORT POTENTIEL' => 'ws-commercial-badge ws-commercial-badge--potential',
        'DISPONIBLE' => 'ws-commercial-badge ws-commercial-badge--avail',
        default => null,
    };

    $priorityHtmlClass = match ($comPriority) {
        'push_urgent' => 'ws-priority-badge ws-priority-badge--push',
        'almost_full' => 'ws-priority-badge ws-priority-badge--almost',
        'high_potential' => 'ws-priority-badge ws-priority-badge--potential',
        'promote' => 'ws-priority-badge ws-priority-badge--promote',
        'watch' => 'ws-priority-badge ws-priority-badge--watch',
        default => 'ws-priority-badge ws-priority-badge--standard',
    };

    $availHtmlClass = match ($comAvailStatus) {
        'full' => 'ws-avail-badge ws-avail-badge--full',
        'low' => 'ws-avail-badge ws-avail-badge--low',
        'almost_full' => 'ws-avail-badge ws-avail-badge--almost',
        'ok' => 'ws-avail-badge ws-avail-badge--ok',
        default => 'ws-avail-badge ws-avail-badge--unknown',
    };

    $availLabel = match ($comAvailStatus) {
        'full' => 'Complet',
        'low' => 'Faible stock',
        'almost_full' => 'Presque complet',
        'ok' => 'Disponible',
        default => 'Non renseigné',
    };

    $priorityLabel = match ($comPriority) {
        'push_urgent' => 'À pousser',
        'almost_full' => 'Presque complet',
        'high_potential' => 'Fort potentiel',
        'promote' => 'À promouvoir',
        'watch' => 'À surveiller',
        default => 'Standard',
    };

    $configureBadgeClass = 'ws-commercial-badge ws-commercial-badge--configure';
    $configureLabel = 'À configurer';
    $nonSellablePriorityLabel = 'Non vendable';
    $nonSellablePriorityClass = 'ws-priority-badge ws-priority-badge--configure';

    // Déterminer la raison exacte de non-vendabilité pour badge + bouton
    $nonSellableReason = null;
    $nonSellableBadge = null;
    $nonSellableBadgeClass = null;
    $nonSellableBtnText = null;
    $nonSellableBtnClass = 'ws-btn ws-btn--sm ws-btn--disabled';
    if (! $isSellable) {
        $hasFutureDep = ($commercial['has_future_departure'] ?? false);
        $cap = $commercial['capacity_total'] ?? null;
        $rem = $commercial['places_restantes'] ?? null;
        if (! $hasFutureDep) {
            $nonSellableReason = 'no_departure';
            $nonSellableBadge = 'Pas de départ';
            $nonSellableBadgeClass = 'ws-commercial-badge ws-commercial-badge--configure';
            $nonSellableBtnText = 'Pas de départ';
        } elseif ($rem !== null && $rem <= 0) {
            $nonSellableReason = 'full';
            $nonSellableBadge = 'Complet';
            $nonSellableBadgeClass = 'ws-commercial-badge ws-commercial-badge--configure';
            $nonSellableBtnText = 'Complet';
        } elseif ($cap === null || $cap <= 0) {
            $nonSellableReason = 'no_capacity';
            $nonSellableBadge = 'À configurer';
            $nonSellableBadgeClass = 'ws-commercial-badge ws-commercial-badge--configure';
            $nonSellableBtnText = 'Configurer';
            $nonSellableBtnClass = 'ws-btn ws-btn--sm ' . ($editTourUrl ? 'ws-btn--configure' : 'ws-btn--disabled');
        } else {
            $nonSellableReason = 'not_sellable';
            $nonSellableBadge = 'Indisponible';
            $nonSellableBadgeClass = 'ws-commercial-badge ws-commercial-badge--configure';
            $nonSellableBtnText = 'Indisponible';
        }
    }

    $progressColor = 'bg-emerald-500';
    if ($comFillRate !== null) {
        if ($comFillRate >= 90) $progressColor = 'bg-red-500';
        elseif ($comFillRate >= 75) $progressColor = 'bg-orange-500';
        elseif ($comFillRate >= 50) $progressColor = 'bg-amber-500';
    }
@endphp

@if($viewMode === 'table')
<tr class="ws-catalog-row ws-catalog-table-row {{ $rowAccent }} {{ $hasLaravel ? '' : 'ws-catalog-row--unlinked' }}{{ $isNearFuture ? ' ws-catalog-row--near' : '' }}{{ $isSellableForRow ? '' : ' ws-catalog-row--configure' }}"
    data-type="{{ $typeKey }}"
    data-row-code="{{ $row['code'] }}"
    data-code="{{ $row['code'] }}"
    data-name="{{ $row['name'] }}"
    data-search="{{ e($wsSearchBlob) }}"
    data-dep="{{ $depRowDateIso ?? '' }}"
    data-ws-avail="{{ e($wsAvail) }}"
    data-ws-upcoming="{{ $wsUpcoming ? '1' : '0' }}"
    data-avail-status="{{ $depRowStatusKey }}"
    data-date-status="{{ $depRowIsPast ? 'past' : ($depRowDateIso ? 'upcoming' : 'none') }}"
    data-stats-validee="{{ $depRowSoldVal }}"
    data-stats-pending="{{ $depRowSoldPending }}"
    data-stats-total="{{ $depRowStatVal + $depRowStatPending + $depRowStatCancel }}"
    data-sort-dep="{{ $depRowDateIso ? \Carbon\Carbon::parse($depRowDateIso)->timestamp : $depTs }}"
    data-sort-price="{{ $priceSort }}"
    data-sort-places="{{ $placesSort }}"
    data-commercial-priority="{{ $comPriority }}"
    data-commercial-badge="{{ $comBadge ?? '' }}"
    data-departure-city="{{ e($row['data_departure_city'] ?? '') }}"
    data-destination="{{ e($row['voyage_destination'] ?? ($modalDetail['destination'] ?? '')) }}"
    data-remaining="{{ $depRowRemaining ?? -1 }}"
    data-sold="{{ $depRowSoldVal + $depRowSoldPending }}"
    data-days-until="{{ $departureData ? ($departureData['days_until'] ?? 9999) : ($comDaysUntil ?? 9999) }}"
    data-fill-rate="{{ $depRowFillRate ?? -1 }}"
    data-price="{{ $priceSort }}"
    data-is-sellable="{{ $isSellableForRow ? '1' : '0' }}"
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
            <span class="ws-td__title ws-td__title--clamp" title="{{ !empty($row['price_label']) ? 'Prix : '.$row['price_label'] : 'Prix non renseigné' }}">{{ $row['name'] }}</span>
        </div>
    </td>
    <td class="ws-td ws-td--destination" data-label="Destination">
        @php
            $destination = $row['voyage_destination'] ?? ($modalDetail['destination'] ?? '');
        @endphp
        @if($destination !== '')
            <span class="ws-td__destination">{{ $destination }}</span>
        @else
            <span class="ws-td__muted">-</span>
        @endif
    </td>
    <td class="ws-td ws-td--dep" data-label="Départ">
        @if($depRowDateLabel)
            <span class="ws-td__dep-date">{{ $depRowDateLabel }}</span>
            @if($depRowIsPast)
                <span class="ws-td__dep-badge ws-td__dep-badge--past">Passé</span>
            @endif
        @else
            <span class="ws-td__muted">-</span>
        @endif
    </td>
    <td class="ws-td ws-td--sold" data-label="Vendu / En attente">
        <span class="ws-td__sold" title="{{ $depRowSoldVal }} place{{ $depRowSoldVal > 1 ? 's' : '' }} vendue{{ $depRowSoldVal > 1 ? 's' : '' }} confirmée{{ $depRowSoldVal > 1 ? 's' : '' }} - {{ $depRowSoldPending }} place{{ $depRowSoldPending > 1 ? 's' : '' }} en attente">{{ $depRowSoldVal }} / {{ $depRowSoldPending }}</span>
    </td>
    <td class="ws-td ws-td--remain" data-label="Restant">
        @if($depRowRemaining !== null)
            <span class="ws-td__remain {{ $depRowRemaining <= 5 ? 'ws-td__remain--danger' : ($depRowRemaining <= 10 ? 'ws-td__remain--warn' : '') }}">{{ $depRowRemaining }}</span>
        @else
            <span class="ws-td__muted">-</span>
        @endif
    </td>
    <td class="ws-td ws-td--cap" data-label="Capacité">
        <div class="ws-td__cap-wrap">
            <span class="ws-td__cap-text">{{ $depRowCapacity !== null ? $depRowCapacity.' places' : '-' }}</span>
            @if($depRowCapacity !== null && $depRowCapacity > 0)
                <div class="ws-progress-bar--mini">
                    <div class="ws-progress-bar--mini__track">
                        <div class="ws-progress-bar--mini__fill {{ $progressColor }}" style="width: {{ min(100, $depRowFillRate ?? 0) }}%"></div>
                    </div>
                    <span class="ws-progress-bar--mini__label">{{ $depRowFillRate ?? 0 }}%</span>
                </div>
            @endif
            @if(!empty($depRowAlerts))
                <span style="display:block;margin-top:0.25rem;font-size:0.7rem;color:#b45309;font-weight:700">{{ $depRowAlerts[0] }}</span>
            @endif
        </div>
    </td>
    <td class="ws-td ws-td--actions" data-label="Actions">
        <div class="ws-td__actions">
            @if(!empty($modalDetail))
                <button type="button"
                    class="ws-btn ws-btn--secondary ws-btn--sm ws-btn--iconish btn-ws-open-detail btn-view"
                    data-row-code="{{ e($row['code']) }}"
                    @if($departureData && $depRowTravelDateId)
                        data-travel-date-id="{{ $depRowTravelDateId }}"
                    @endif
                    title="Détail">
                    <i class="fas fa-eye" aria-hidden="true"></i><span>Voir</span>
                </button>
            @endif
            @can('reservations.view')
                @if($isSellableForRow && $hasLaravel)
                    @if($departureData)
                        <button type="button"
                            class="ws-btn ws-btn--primary ws-btn--sm ws-btn--iconish"
                            data-ws-reserve-departure="1"
                            data-row-code="{{ e($row['code']) }}"
                            data-travel-date-id="{{ $depRowTravelDateId }}"
                            title="{{ $reserveLabel }}">
                            @if($typeKey === 'vol')
                                <i class="fas fa-plane-departure" aria-hidden="true"></i>
                            @else
                                <i class="fas fa-suitcase-rolling" aria-hidden="true"></i>
                            @endif
                            <span>{{ $reserveLabel }}</span>
                        </button>
                    @else
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
                    @endif
                @elseif(! $isSellableForRow && $hasLaravel)
                    <a href="{{ $editTourUrl ?: '#' }}"
                        class="ws-btn ws-btn--sm ws-btn--iconish {{ $editTourUrl ? 'ws-btn--configure' : 'ws-btn--disabled' }}"
                        {!! $editTourUrl ? '' : 'aria-disabled="true"' !!}
                        title="{{ $nonSellableBtnText }}">
                        <i class="fas fa-cog" aria-hidden="true"></i><span>{{ $nonSellableBtnText }}</span>
                    </a>
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
<article class="catalog-card ws-catalog-row ws-offer-card ws-offer-card--compact admin-sales-catalogue-card-fix {{ $rowAccent }} {{ $hasLaravel ? '' : 'ws-catalog-row--unlinked' }}{{ $isNearFuture ? ' ws-catalog-row--near' : '' }}{{ $isSellable ? '' : ' ws-catalog-row--configure' }}"
         style="width:100% !important;max-width:none !important;min-width:0 !important;margin:0 !important;justify-self:stretch !important;grid-column:auto !important;grid-column-start:auto !important;grid-column-end:auto !important;"
    data-type="{{ $typeKey }}"
    data-row-code="{{ $row['code'] }}"
    data-code="{{ $row['code'] }}"
    data-name="{{ $row['name'] }}"
    data-search="{{ e($wsSearchBlob) }}"
    data-dep="{{ $row['departure_date'] ? \Carbon\Carbon::parse($row['departure_date'])->format('Y-m-d') : '' }}"
    data-ws-avail="{{ e($wsAvail) }}"
    data-ws-upcoming="{{ $wsUpcoming ? '1' : '0' }}"
    data-avail-status="{{ $comAvailStatus }}"
    data-date-status="{{ $dateStatus }}"
    data-stats-validee="{{ $statVal }}"
    data-stats-pending="{{ $statPending }}"
    data-stats-total="{{ $statTotal }}"
    data-sort-dep="{{ $depTs }}"
    data-sort-price="{{ $priceSort }}"
    data-sort-places="{{ $placesSort }}"
    data-commercial-priority="{{ $comPriority }}"
    data-commercial-badge="{{ $comBadge ?? '' }}"
    data-departure-city="{{ e($row['data_departure_city'] ?? '') }}"
    data-destination="{{ e($row['voyage_destination'] ?? ($modalDetail['destination'] ?? '')) }}"
    data-remaining="{{ $comRemaining ?? -1 }}"
    data-sold="{{ $comSold }}"
    data-days-until="{{ $comDaysUntil ?? 9999 }}"
    data-fill-rate="{{ $comFillRate ?? -1 }}"
    data-price="{{ $priceSort }}"
    data-is-sellable="{{ $isSellable ? '1' : '0' }}"
    title="{{ e($rowTitle) }}">

    @if($comBadge)
        <div class="ws-offer-card__badge-overlay {{ $badgeHtmlClass }}">{{ $comBadge }}</div>
    @elseif(! $isSellable && $nonSellableBadge)
        <div class="ws-offer-card__badge-overlay {{ $nonSellableBadgeClass }}">{{ $nonSellableBadge }}</div>
    @endif

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
        @php $cardDestination = $row['voyage_destination'] ?? ($modalDetail['destination'] ?? ''); @endphp
        @if($cardDestination !== '')
            <p class="ws-offer-card__city"><i class="fas fa-location-dot" aria-hidden="true"></i> {{ $cardDestination }}</p>
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

        @if($comCapacity !== null && $comCapacity > 0)
            <div class="ws-offer-card__commercial-bar">
                <div class="ws-offer-card__commercial-stats">
                    <span class="ws-offer-card__commercial-stat ws-offer-card__commercial-stat--sold">{{ $comSold }} vendues</span>
                    <span class="ws-offer-card__commercial-stat ws-offer-card__commercial-stat--remain {{ $comRemaining !== null && $comRemaining <= 5 ? 'ws-offer-card__commercial-stat--danger' : ($comRemaining !== null && $comRemaining <= 10 ? 'ws-offer-card__commercial-stat--warn' : '') }}">
                        @if($comRemaining !== null)
                            {{ $comRemaining }} restantes
                        @else
                            -
                        @endif
                    </span>
                </div>
                <div class="ws-progress-bar--mini">
                    <div class="ws-progress-bar--mini__track">
                        <div class="ws-progress-bar--mini__fill {{ $progressColor }}" style="width: {{ min(100, $comFillRate ?? 0) }}%"></div>
                    </div>
                    <span class="ws-progress-bar--mini__label">{{ $comFillRate ?? 0 }}%</span>
                </div>
            </div>
        @endif

        @if($departureData)
            <div class="ws-offer-card__departures">
                <div class="ws-offer-card__section-label">Départ</div>
                <div class="ws-offer-card__departure-item ws-offer-card__departure-item--solo">
                    <span class="ws-offer-card__departure-date">{{ $depRowDateLabel ?: 'Date non renseignée' }}</span>
                    @if($depRowIsPast)
                        <span class="ws-offer-card__departure-status ws-offer-card__departure-status--muted">Passé</span>
                    @elseif($depRowStatusKey === 'full')
                        <span class="ws-offer-card__departure-status ws-offer-card__departure-status--full">Complet</span>
                    @elseif($depRowStatusKey === 'almost_full')
                        <span class="ws-offer-card__departure-status ws-offer-card__departure-status--warn">Presque complet</span>
                    @else
                        <span class="ws-offer-card__departure-status ws-offer-card__departure-status--ok">Disponible</span>
                    @endif
                </div>
            </div>
        @elseif($typeKey === 'package' && $departures->isNotEmpty())
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
                            <span class="ws-offer-card__departure-status {{ $statusClass }}">- {{ $statusLabel }}</span>
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

        @if($comBadge || $comAvailStatus !== 'unknown')
            <div class="ws-offer-card__avail-row">
                @if($comBadge)
                    <span class="{{ $badgeHtmlClass }} ws-offer-card__avail-badge">{{ $comBadge }}</span>
                @endif
                <span class="{{ $availHtmlClass }} ws-offer-card__avail-badge">{{ $availLabel }}</span>
            </div>
        @endif

        <div class="ws-offer-card__actions ws-offer-card__actions--compact" role="group" aria-label="Actions">
            @if(!empty($modalDetail))
                <button type="button"
                    class="ws-btn ws-btn--secondary ws-btn--sm btn-ws-open-detail btn-view"
                    data-row-code="{{ e($row['code']) }}"
                    @if($departureData && $depRowTravelDateId)
                        data-travel-date-id="{{ $depRowTravelDateId }}"
                    @endif
                    title="Détail">
                    <i class="fas fa-eye" aria-hidden="true"></i><span>Voir</span>
                </button>
            @endif
            @can('reservations.view')
                @php
                    $cardReserveUrl = $departureData && $depRowRouteReserve ? $depRowRouteReserve : $reserveUrl;
                @endphp
                @if($isSellableForRow && $hasLaravel)
                    <a href="{{ $cardReserveUrl }}"
                        class="ws-btn ws-btn--primary ws-btn--sm"
                        title="{{ $reserveLabel }}">
                        @if($typeKey === 'vol')
                            <i class="fas fa-plane-departure" aria-hidden="true"></i>
                        @else
                            <i class="fas fa-suitcase-rolling" aria-hidden="true"></i>
                        @endif
                        <span>{{ $reserveLabel }}</span>
                    </a>
                @elseif(! $isSellableForRow && $hasLaravel)
                    <a href="{{ $editTourUrl ?: '#' }}"
                        class="{{ $nonSellableBtnClass }}"
                        {!! $editTourUrl ? '' : 'aria-disabled="true"' !!}
                        title="{{ $nonSellableBtnText }}">
                        <i class="fas fa-cog" aria-hidden="true"></i><span>{{ $nonSellableBtnText }}</span>
                    </a>
                @else
                    <a href="{{ $editTourUrl ?: '#' }}"
                        class="ws-btn ws-btn--sm {{ $editTourUrl ? 'ws-btn--ghost' : 'ws-btn--disabled' }}"
                        {!! $editTourUrl ? '' : 'aria-disabled="true"' !!}
                        title="Associer la fiche Laravel">
                        <i class="fas fa-link" aria-hidden="true"></i><span>Lier</span>
                    </a>
                @endif
            @endcan
        </div>
    </div>
</article>
@endif

