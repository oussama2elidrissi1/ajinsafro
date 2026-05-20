<?php
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

    // Per-departure overrides for table mode
    $departureData = $departure ?? null;
    if ($viewMode === 'table' && $departureData) {
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
    if ($viewMode === 'table' && $departureData) {
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
?>

<?php if($viewMode === 'table'): ?>
<tr class="ws-catalog-row ws-catalog-table-row <?php echo e($rowAccent); ?> <?php echo e($hasLaravel ? '' : 'ws-catalog-row--unlinked'); ?><?php echo e($isNearFuture ? ' ws-catalog-row--near' : ''); ?><?php echo e($isSellableForRow ? '' : ' ws-catalog-row--configure'); ?>"
    data-type="<?php echo e($typeKey); ?>"
    data-row-code="<?php echo e($row['code']); ?>"
    data-code="<?php echo e($row['code']); ?>"
    data-name="<?php echo e($row['name']); ?>"
    data-search="<?php echo e(e($wsSearchBlob)); ?>"
    data-dep="<?php echo e($depRowDateIso ?? ''); ?>"
    data-ws-avail="<?php echo e(e($wsAvail)); ?>"
    data-ws-upcoming="<?php echo e($wsUpcoming ? '1' : '0'); ?>"
    data-avail-status="<?php echo e($depRowStatusKey); ?>"
    data-date-status="<?php echo e($depRowIsPast ? 'past' : ($depRowDateIso ? 'upcoming' : 'none')); ?>"
    data-stats-validee="<?php echo e($depRowSoldVal); ?>"
    data-stats-pending="<?php echo e($depRowSoldPending); ?>"
    data-stats-total="<?php echo e($depRowStatVal + $depRowStatPending + $depRowStatCancel); ?>"
    data-sort-dep="<?php echo e($depRowDateIso ? \Carbon\Carbon::parse($depRowDateIso)->timestamp : $depTs); ?>"
    data-sort-price="<?php echo e($priceSort); ?>"
    data-sort-places="<?php echo e($placesSort); ?>"
    data-commercial-priority="<?php echo e($comPriority); ?>"
    data-commercial-badge="<?php echo e($comBadge ?? ''); ?>"
    data-departure-city="<?php echo e(e($row['data_departure_city'] ?? '')); ?>"
    data-destination="<?php echo e(e($row['voyage_destination'] ?? ($modalDetail['destination'] ?? ''))); ?>"
    data-remaining="<?php echo e($depRowRemaining ?? -1); ?>"
    data-sold="<?php echo e($depRowSoldVal + $depRowSoldPending); ?>"
    data-days-until="<?php echo e($departureData ? ($departureData['days_until'] ?? 9999) : ($comDaysUntil ?? 9999)); ?>"
    data-fill-rate="<?php echo e($depRowFillRate ?? -1); ?>"
    data-price="<?php echo e($priceSort); ?>"
    data-is-sellable="<?php echo e($isSellableForRow ? '1' : '0'); ?>"
    title="<?php echo e(e($rowTitle)); ?>">
    <td class="ws-td ws-td--ref" data-label="Réf">
        <span class="ws-td__code"><?php echo e($row['code']); ?></span>
        <?php if($typeKey === 'package' && ! $hasLaravel): ?>
            <span class="ws-td__hint ws-td__hint--warn">Non lié</span>
        <?php endif; ?>
    </td>
    <td class="ws-td ws-td--offer" data-label="Voyage">
        <div class="ws-td__offer-compact">
            <span class="ws-td__type-txt"><?php echo e($typeShort); ?></span>
            <span class="ws-td__title ws-td__title--clamp" title="<?php echo e(!empty($row['price_label']) ? 'Prix : '.$row['price_label'] : 'Prix non renseigné'); ?>"><?php echo e($row['name']); ?></span>
        </div>
    </td>
    <td class="ws-td ws-td--destination" data-label="Destination">
        <?php
            $destination = $row['voyage_destination'] ?? ($modalDetail['destination'] ?? '');
        ?>
        <?php if($destination !== ''): ?>
            <span class="ws-td__destination"><?php echo e($destination); ?></span>
        <?php else: ?>
            <span class="ws-td__muted">-</span>
        <?php endif; ?>
    </td>
    <td class="ws-td ws-td--dep" data-label="Départ">
        <?php if($depRowDateLabel): ?>
            <span class="ws-td__dep-date"><?php echo e($depRowDateLabel); ?></span>
            <?php if($depRowIsPast): ?>
                <span class="ws-td__dep-badge ws-td__dep-badge--past">Passé</span>
            <?php endif; ?>
        <?php else: ?>
            <span class="ws-td__muted">-</span>
        <?php endif; ?>
    </td>
    <td class="ws-td ws-td--sold" data-label="Vendu / En attente">
        <span class="ws-td__sold" title="<?php echo e($depRowSoldVal); ?> place<?php echo e($depRowSoldVal > 1 ? 's' : ''); ?> vendue<?php echo e($depRowSoldVal > 1 ? 's' : ''); ?> confirmée<?php echo e($depRowSoldVal > 1 ? 's' : ''); ?> - <?php echo e($depRowSoldPending); ?> place<?php echo e($depRowSoldPending > 1 ? 's' : ''); ?> en attente"><?php echo e($depRowSoldVal); ?> / <?php echo e($depRowSoldPending); ?></span>
    </td>
    <td class="ws-td ws-td--remain" data-label="Restant">
        <?php if($depRowRemaining !== null): ?>
            <span class="ws-td__remain <?php echo e($depRowRemaining <= 5 ? 'ws-td__remain--danger' : ($depRowRemaining <= 10 ? 'ws-td__remain--warn' : '')); ?>"><?php echo e($depRowRemaining); ?></span>
        <?php else: ?>
            <span class="ws-td__muted">-</span>
        <?php endif; ?>
    </td>
    <td class="ws-td ws-td--cap" data-label="Capacité">
        <div class="ws-td__cap-wrap">
            <span class="ws-td__cap-text"><?php echo e($depRowCapacity !== null ? $depRowCapacity.' places' : '-'); ?></span>
            <?php if($depRowCapacity !== null && $depRowCapacity > 0): ?>
                <div class="ws-progress-bar--mini">
                    <div class="ws-progress-bar--mini__track">
                        <div class="ws-progress-bar--mini__fill <?php echo e($progressColor); ?>" style="width: <?php echo e(min(100, $depRowFillRate ?? 0)); ?>%"></div>
                    </div>
                    <span class="ws-progress-bar--mini__label"><?php echo e($depRowFillRate ?? 0); ?>%</span>
                </div>
            <?php endif; ?>
            <?php if(!empty($depRowAlerts)): ?>
                <span style="display:block;margin-top:0.25rem;font-size:0.7rem;color:#b45309;font-weight:700"><?php echo e($depRowAlerts[0]); ?></span>
            <?php endif; ?>
        </div>
    </td>
    <td class="ws-td ws-td--actions" data-label="Actions">
        <div class="ws-td__actions">
            <?php if(!empty($modalDetail)): ?>
                <button type="button"
                    class="ws-btn ws-btn--secondary ws-btn--sm ws-btn--iconish btn-ws-open-detail btn-view"
                    data-row-code="<?php echo e(e($row['code'])); ?>"
                    <?php if($departureData && $depRowTravelDateId): ?>
                        data-travel-date-id="<?php echo e($depRowTravelDateId); ?>"
                    <?php endif; ?>
                    title="Détail">
                    <i class="fas fa-eye" aria-hidden="true"></i><span>Voir</span>
                </button>
            <?php endif; ?>
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('reservations.view')): ?>
                <?php if($isSellableForRow && $hasLaravel): ?>
                    <?php if($departureData): ?>
                        <button type="button"
                            class="ws-btn ws-btn--primary ws-btn--sm ws-btn--iconish"
                            data-ws-reserve-departure="1"
                            data-row-code="<?php echo e(e($row['code'])); ?>"
                            data-travel-date-id="<?php echo e($depRowTravelDateId); ?>"
                            title="<?php echo e($reserveLabel); ?>">
                            <?php if($typeKey === 'vol'): ?>
                                <i class="fas fa-plane-departure" aria-hidden="true"></i>
                            <?php else: ?>
                                <i class="fas fa-suitcase-rolling" aria-hidden="true"></i>
                            <?php endif; ?>
                            <span><?php echo e($reserveLabel); ?></span>
                        </button>
                    <?php else: ?>
                        <button type="button"
                            class="ws-btn ws-btn--primary ws-btn--sm ws-btn--iconish btn-ws-open-reserve"
                            data-row-code="<?php echo e(e($row['code'])); ?>"
                            title="<?php echo e($reserveLabel); ?>">
                            <?php if($typeKey === 'vol'): ?>
                                <i class="fas fa-plane-departure" aria-hidden="true"></i>
                            <?php else: ?>
                                <i class="fas fa-suitcase-rolling" aria-hidden="true"></i>
                            <?php endif; ?>
                            <span><?php echo e($reserveLabel); ?></span>
                        </button>
                    <?php endif; ?>
                <?php elseif(! $isSellableForRow && $hasLaravel): ?>
                    <a href="<?php echo e($editTourUrl ?: '#'); ?>"
                        class="ws-btn ws-btn--sm ws-btn--iconish <?php echo e($editTourUrl ? 'ws-btn--configure' : 'ws-btn--disabled'); ?>"
                        <?php echo $editTourUrl ? '' : 'aria-disabled="true"'; ?>

                        title="<?php echo e($nonSellableBtnText); ?>">
                        <i class="fas fa-cog" aria-hidden="true"></i><span><?php echo e($nonSellableBtnText); ?></span>
                    </a>
                <?php else: ?>
                    <a href="<?php echo e($editTourUrl ?: '#'); ?>"
                        class="ws-btn ws-btn--sm ws-btn--iconish <?php echo e($editTourUrl ? 'ws-btn--ghost' : 'ws-btn--disabled'); ?>"
                        <?php echo $editTourUrl ? '' : 'aria-disabled="true"'; ?>

                        title="Associer la fiche Laravel">
                        <i class="fas fa-link" aria-hidden="true"></i><span>Lier</span>
                    </a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </td>
</tr>
<?php else: ?>
<article class="catalog-card ws-catalog-row ws-offer-card ws-offer-card--compact admin-sales-catalogue-card-fix <?php echo e($rowAccent); ?> <?php echo e($hasLaravel ? '' : 'ws-catalog-row--unlinked'); ?><?php echo e($isNearFuture ? ' ws-catalog-row--near' : ''); ?><?php echo e($isSellable ? '' : ' ws-catalog-row--configure'); ?>"
         style="width:100% !important;max-width:none !important;min-width:0 !important;margin:0 !important;justify-self:stretch !important;"
    data-type="<?php echo e($typeKey); ?>"
    data-row-code="<?php echo e($row['code']); ?>"
    data-code="<?php echo e($row['code']); ?>"
    data-name="<?php echo e($row['name']); ?>"
    data-search="<?php echo e(e($wsSearchBlob)); ?>"
    data-dep="<?php echo e($row['departure_date'] ? \Carbon\Carbon::parse($row['departure_date'])->format('Y-m-d') : ''); ?>"
    data-ws-avail="<?php echo e(e($wsAvail)); ?>"
    data-ws-upcoming="<?php echo e($wsUpcoming ? '1' : '0'); ?>"
    data-avail-status="<?php echo e($comAvailStatus); ?>"
    data-date-status="<?php echo e($dateStatus); ?>"
    data-stats-validee="<?php echo e($statVal); ?>"
    data-stats-pending="<?php echo e($statPending); ?>"
    data-stats-total="<?php echo e($statTotal); ?>"
    data-sort-dep="<?php echo e($depTs); ?>"
    data-sort-price="<?php echo e($priceSort); ?>"
    data-sort-places="<?php echo e($placesSort); ?>"
    data-commercial-priority="<?php echo e($comPriority); ?>"
    data-commercial-badge="<?php echo e($comBadge ?? ''); ?>"
    data-departure-city="<?php echo e(e($row['data_departure_city'] ?? '')); ?>"
    data-destination="<?php echo e(e($row['voyage_destination'] ?? ($modalDetail['destination'] ?? ''))); ?>"
    data-remaining="<?php echo e($comRemaining ?? -1); ?>"
    data-sold="<?php echo e($comSold); ?>"
    data-days-until="<?php echo e($comDaysUntil ?? 9999); ?>"
    data-fill-rate="<?php echo e($comFillRate ?? -1); ?>"
    data-price="<?php echo e($priceSort); ?>"
    data-is-sellable="<?php echo e($isSellable ? '1' : '0'); ?>"
    title="<?php echo e(e($rowTitle)); ?>">

    <?php if($comBadge): ?>
        <div class="ws-offer-card__badge-overlay <?php echo e($badgeHtmlClass); ?>"><?php echo e($comBadge); ?></div>
    <?php elseif(! $isSellable && $nonSellableBadge): ?>
        <div class="ws-offer-card__badge-overlay <?php echo e($nonSellableBadgeClass); ?>"><?php echo e($nonSellableBadge); ?></div>
    <?php endif; ?>

    <div class="ws-offer-card__media ws-offer-card__media--compact<?php echo e($imageUrl ? ' ws-offer-card__media--has-img' : ''); ?>">
        <div class="ws-offer-card__media-fill<?php echo e($imageUrl ? ' ws-offer-card__media-fill--has-img' : ''); ?>">
            <div class="ws-offer-card__placeholder" aria-hidden="true">
                <span class="ws-offer-card__placeholder-icon">
                    <?php if($typeKey === 'vol'): ?>
                        <i class="fas fa-plane"></i>
                    <?php elseif($typeKey === 'hebergement'): ?>
                        <i class="fas fa-hotel"></i>
                    <?php else: ?>
                        <i class="fas fa-map-location-dot"></i>
                    <?php endif; ?>
                </span>
                <span class="ws-offer-card__placeholder-text">Visuel indisponible</span>
            </div>
            <?php if($imageUrl): ?>
                <img src="<?php echo e(e($imageUrl)); ?>" alt="" class="ws-offer-card__img" loading="lazy" decoding="async"
                     width="320" height="180"
                     onerror="this.closest('.ws-offer-card__media-fill')?.classList.add('is-broken')">
            <?php endif; ?>
        </div>
    </div>

    <div class="ws-offer-card__body ws-offer-card__body--compact">
        <div class="ws-offer-card__compact-head">
            <span class="<?php echo e($badgeClass); ?> ws-offer-card__type--compact"><?php echo e($typeShort); ?></span>
            <span class="ws-offer-card__code ws-offer-card__code--compact"><?php echo e($row['code']); ?></span>
            <?php if($typeKey === 'package' && ! $hasLaravel): ?>
                <span class="ws-offer-card__unlink ws-offer-card__unlink--inline">Non lié</span>
            <?php endif; ?>
        </div>
        <h3 class="ws-offer-card__title ws-offer-card__title--compact"><?php echo e($row['name']); ?></h3>
        <?php if($referenceBits !== []): ?>
            <p class="ws-offer-card__refs"><?php echo e(implode(' · ', $referenceBits)); ?></p>
        <?php endif; ?>
        <?php $cardDestination = $row['voyage_destination'] ?? ($modalDetail['destination'] ?? ''); ?>
        <?php if($cardDestination !== ''): ?>
            <p class="ws-offer-card__city"><i class="fas fa-location-dot" aria-hidden="true"></i> <?php echo e($cardDestination); ?></p>
        <?php endif; ?>
        <div class="ws-offer-card__meta-list" role="group" aria-label="Tarif et capacité">
            <div class="ws-offer-card__meta-item ws-offer-card__meta-item--price">
                <span class="ws-offer-card__meta-label"><i class="fas fa-coins" aria-hidden="true"></i>Prix à partir de</span>
                <span class="ws-offer-card__meta-value ws-offer-card__meta-value--price">
                    <?php if(! empty($row['price_label'])): ?>
                        <?php echo e($row['price_label']); ?>

                    <?php else: ?>
                        <span class="ws-offer-card__meta-muted">Non renseigné</span>
                    <?php endif; ?>
                </span>
            </div>
            <div class="ws-offer-card__meta-item ws-offer-card__meta-item--cap">
                <span class="ws-offer-card__meta-label"><i class="fas fa-users" aria-hidden="true"></i>Capacité</span>
                <span class="ws-offer-card__meta-value" title="Capacité basée sur la répartition des chambres">
                    <?php if($typeKey === 'package' && $hasLaravel): ?>
                        <?php echo e(is_numeric(str_replace(' ', '', $capText)) ? $capText.' places' : $capText); ?>

                    <?php else: ?>
                        <span class="ws-offer-card__meta-muted">Non applicable</span>
                    <?php endif; ?>
                </span>
            </div>
        </div>

        <?php if($comCapacity !== null && $comCapacity > 0): ?>
            <div class="ws-offer-card__commercial-bar">
                <div class="ws-offer-card__commercial-stats">
                    <span class="ws-offer-card__commercial-stat ws-offer-card__commercial-stat--sold"><?php echo e($comSold); ?> vendues</span>
                    <span class="ws-offer-card__commercial-stat ws-offer-card__commercial-stat--remain <?php echo e($comRemaining !== null && $comRemaining <= 5 ? 'ws-offer-card__commercial-stat--danger' : ($comRemaining !== null && $comRemaining <= 10 ? 'ws-offer-card__commercial-stat--warn' : '')); ?>">
                        <?php if($comRemaining !== null): ?>
                            <?php echo e($comRemaining); ?> restantes
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </span>
                </div>
                <div class="ws-progress-bar--mini">
                    <div class="ws-progress-bar--mini__track">
                        <div class="ws-progress-bar--mini__fill <?php echo e($progressColor); ?>" style="width: <?php echo e(min(100, $comFillRate ?? 0)); ?>%"></div>
                    </div>
                    <span class="ws-progress-bar--mini__label"><?php echo e($comFillRate ?? 0); ?>%</span>
                </div>
            </div>
        <?php endif; ?>

        <?php if($typeKey === 'package' && $departures->isNotEmpty()): ?>
            <div class="ws-offer-card__departures">
                <div class="ws-offer-card__section-label">Départs disponibles</div>
                <ul class="ws-offer-card__departure-list" aria-label="Départs disponibles">
                    <?php $__currentLoopData = $visibleDepartures; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $departure): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $statusClass = match ($departure['status_key'] ?? 'unknown') {
                                'available' => 'ws-offer-card__departure-status--ok',
                                'almost_full' => 'ws-offer-card__departure-status--warn',
                                'full' => 'ws-offer-card__departure-status--full',
                                default => 'ws-offer-card__departure-status--muted',
                            };
                            $statusLabel = !empty($departure['is_past'])
                                ? 'Passé'
                                : (($departure['status_label'] ?? '') === 'Disponible' ? 'À venir' : ($departure['status_label'] ?? 'À venir'));
                        ?>
                        <li class="ws-offer-card__departure-item">
                            <span class="ws-offer-card__departure-date"><?php echo e($departure['date_label'] ?? 'Date non renseignée'); ?></span>
                            <span class="ws-offer-card__departure-status <?php echo e($statusClass); ?>">- <?php echo e($statusLabel); ?></span>
                        </li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
                <?php if($extraDepartureCount > 0): ?>
                    <button type="button" class="ws-offer-card__more btn-ws-open-detail" data-row-code="<?php echo e(e($row['code'])); ?>">
                        + <?php echo e($extraDepartureCount); ?> autre(s) départ(s)
                    </button>
                <?php endif; ?>
            </div>
        <?php elseif($hasDepDate): ?>
            <div class="ws-offer-card__departures">
                <div class="ws-offer-card__section-label">Départ</div>
                <div class="ws-offer-card__departure-item ws-offer-card__departure-item--solo">
                    <span class="ws-offer-card__departure-date"><?php echo e(\Carbon\Carbon::parse($row['departure_date'])->locale('fr')->translatedFormat('d M Y')); ?></span>
                    <?php if($pkgDepCanceled): ?>
                        <span class="ws-offer-card__departure-status ws-offer-card__departure-status--muted">Annulé</span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if($comBadge || $comAvailStatus !== 'unknown'): ?>
            <div class="ws-offer-card__avail-row">
                <?php if($comBadge): ?>
                    <span class="<?php echo e($badgeHtmlClass); ?> ws-offer-card__avail-badge"><?php echo e($comBadge); ?></span>
                <?php endif; ?>
                <span class="<?php echo e($availHtmlClass); ?> ws-offer-card__avail-badge"><?php echo e($availLabel); ?></span>
            </div>
        <?php endif; ?>

        <div class="ws-offer-card__actions ws-offer-card__actions--compact" role="group" aria-label="Actions">
            <?php if(!empty($modalDetail)): ?>
                <button type="button"
                    class="ws-btn ws-btn--secondary ws-btn--sm btn-ws-open-detail btn-view"
                    data-row-code="<?php echo e(e($row['code'])); ?>"
                    title="Détail">
                    <i class="fas fa-eye" aria-hidden="true"></i><span>Voir</span>
                </button>
            <?php endif; ?>
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('reservations.view')): ?>
                <?php if($isSellable && $hasLaravel): ?>
                    <a href="<?php echo e($reserveUrl); ?>"
                        class="ws-btn ws-btn--primary ws-btn--sm"
                        title="<?php echo e($reserveLabel); ?>">
                        <?php if($typeKey === 'vol'): ?>
                            <i class="fas fa-plane-departure" aria-hidden="true"></i>
                        <?php else: ?>
                            <i class="fas fa-suitcase-rolling" aria-hidden="true"></i>
                        <?php endif; ?>
                        <span><?php echo e($reserveLabel); ?></span>
                    </a>
                <?php elseif(! $isSellable && $hasLaravel): ?>
                    <a href="<?php echo e($editTourUrl ?: '#'); ?>"
                        class="<?php echo e($nonSellableBtnClass); ?>"
                        <?php echo $editTourUrl ? '' : 'aria-disabled="true"'; ?>

                        title="<?php echo e($nonSellableBtnText); ?>">
                        <i class="fas fa-cog" aria-hidden="true"></i><span><?php echo e($nonSellableBtnText); ?></span>
                    </a>
                <?php else: ?>
                    <a href="<?php echo e($editTourUrl ?: '#'); ?>"
                        class="ws-btn ws-btn--sm <?php echo e($editTourUrl ? 'ws-btn--ghost' : 'ws-btn--disabled'); ?>"
                        <?php echo $editTourUrl ? '' : 'aria-disabled="true"'; ?>

                        title="Associer la fiche Laravel">
                        <i class="fas fa-link" aria-hidden="true"></i><span>Lier</span>
                    </a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</article>
<?php endif; ?>

<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\reservations\workspace\partials\catalog-row.blade.php ENDPATH**/ ?>