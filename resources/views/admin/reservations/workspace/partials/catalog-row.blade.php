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
    $isUpcoming = $hasDepDate && ! $isPast;
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
    $wsAvail = $row['ws_avail'] ?? 'na';
    $wsUpcoming = ! empty($row['ws_has_future']);
    $imageUrl = ! empty($row['image_url']) ? (string) $row['image_url'] : null;
    $isFeatured = ! empty($row['is_featured']);
    $hasPromo = ! empty($row['has_promo']);

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

    $availBadge = match ($wsAvail) {
        'ok' => ['label' => 'Places dispo', 'class' => 'ws-offer-chip ws-offer-chip--avail-ok'],
        'low' => ['label' => 'Peu de places', 'class' => 'ws-offer-chip ws-offer-chip--avail-warn'],
        'full' => ['label' => 'Complet', 'class' => 'ws-offer-chip ws-offer-chip--avail-full'],
        default => ['label' => null, 'class' => ''],
    };

    $viewMode = $mode ?? 'card';
@endphp

@if($viewMode === 'table')
<tr class="ws-catalog-row ws-catalog-table-row {{ $rowAccent }} {{ $hasLaravel ? '' : 'ws-catalog-row--unlinked' }}"
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
    <td class="ws-td ws-td--ref">
        <span class="ws-td__code">{{ $row['code'] }}</span>
        @if($typeKey === 'package' && ! $hasLaravel)
            <span class="ws-td__hint ws-td__hint--warn">Non lié</span>
        @endif
    </td>
    <td class="ws-td ws-td--offer">
        <div class="ws-td__offer-top">
            <span class="{{ $badgeClass }} ws-td__type-pill">{{ $typeShort }}</span>
            @if($isFeatured)
                <span class="ws-offer-chip ws-offer-chip--featured ws-td__mini-chip">Coup de cœur</span>
            @endif
            @if($hasPromo)
                <span class="ws-offer-chip ws-offer-chip--promo ws-td__mini-chip">Promo</span>
            @endif
        </div>
        <div class="ws-td__title">{{ $row['name'] }}</div>
        @if($summary !== '')
            <div class="ws-td__sub line-clamp-2">{{ $summary }}</div>
        @elseif(! empty($row['subtitle']))
            <div class="ws-td__sub ws-td__sub--muted ws-line-clamp-1">{{ $row['subtitle'] }}</div>
        @endif
        @if(! empty($row['voyage_destination']))
            <div class="ws-td__dest"><i class="fas fa-location-dot" aria-hidden="true"></i> {{ $row['voyage_destination'] }}</div>
        @endif
    </td>
    <td class="ws-td ws-td--dep">
        @if($hasDepDate)
            <span class="ws-td__dep-date">{{ \Carbon\Carbon::parse($row['departure_date'])->locale('fr')->translatedFormat('d M Y') }}</span>
            @if($pkgDepCanceled)
                <span class="ws-offer-chip ws-offer-chip--danger ws-offer-chip--inline">Annulé</span>
            @endif
        @else
            <span class="ws-td__muted">À préciser</span>
        @endif
    </td>
    <td class="ws-td ws-td--price">
        @if(! empty($row['price_label']))
            <strong class="ws-td__price">{{ $row['price_label'] }}</strong>
        @else
            <span class="ws-td__muted">Sur demande</span>
        @endif
    </td>
    <td class="ws-td ws-td--cap">
        @if($typeKey === 'package' && $hasLaravel)
            @if(($placesState ?? '') === 'ok' && $placesTotal !== null)
                <span class="ws-td__cap-strong">{{ number_format((int) $placesTotal, 0, ',', ' ') }}</span>
                <span class="ws-td__cap-lbl">places</span>
            @elseif(in_array($placesState ?? '', ['no_hotels', 'no_valid_rooms'], true))
                <span class="ws-td__muted">Non renseignée</span>
            @else
                <span class="ws-td__muted">—</span>
            @endif
        @else
            <span class="ws-td__muted">—</span>
        @endif
    </td>
    <td class="ws-td ws-td--status">
        <div class="ws-td__status-stack">
            @if($pkgDepCanceled)
                <span class="ws-offer-chip ws-offer-chip--danger">Départ annulé</span>
            @elseif($hasDepDate && $isPast)
                <span class="ws-offer-chip ws-offer-chip--past">Passé</span>
            @elseif($isUpcoming)
                <span class="ws-offer-chip ws-offer-chip--ok">À venir</span>
            @elseif(! $hasDepDate)
                <span class="ws-offer-chip ws-offer-chip--muted">Sans date</span>
            @endif
            @if($typeKey === 'package' && $hasLaravel && $availBadge['label'])
                <span class="{{ $availBadge['class'] }}">{{ $availBadge['label'] }}</span>
            @endif
        </div>
        <div class="ws-td__stats-inline" aria-label="Réservations">
            <span class="ws-stat-pill ws-stat-pill--ok" title="Confirmées">{{ $statVal }}</span>
            <span class="ws-stat-pill ws-stat-pill--wait" title="En attente">{{ $statPending }}</span>
            <span class="ws-stat-pill ws-stat-pill--off" title="Annulées">{{ $statCancel }}</span>
        </div>
    </td>
    <td class="ws-td ws-td--actions">
        <div class="ws-td__actions">
            @if(!empty($modalDetail))
                <button type="button"
                    class="ws-btn ws-btn--secondary ws-btn--sm btn-ws-open-detail"
                    data-row-code="{{ e($row['code']) }}"
                    title="Voir la prestation">
                    <i class="fas fa-eye" aria-hidden="true"></i><span>Voir</span>
                </button>
            @endif
            @can('reservations.view')
                @if($hasLaravel)
                    <a href="{{ $reserveUrl }}"
                        class="ws-btn ws-btn--primary ws-btn--sm"
                        title="{{ $reserveLabel }}">
                        @if($typeKey === 'vol')
                            <i class="fas fa-plane-departure" aria-hidden="true"></i>
                        @else
                            <i class="fas fa-suitcase-rolling" aria-hidden="true"></i>
                        @endif
                        <span>{{ $reserveLabel }}</span>
                    </a>
                @else
                    <a href="{{ $editTourUrl ?: '#' }}"
                        class="ws-btn ws-btn--sm {{ $editTourUrl ? 'ws-btn--ghost' : 'ws-btn--disabled' }}"
                        {!! $editTourUrl ? '' : 'aria-disabled="true"' !!}
                        title="Associer la fiche Laravel pour réserver">
                        <i class="fas fa-link" aria-hidden="true"></i><span>Lier</span>
                    </a>
                @endif
            @endcan
        </div>
    </td>
</tr>
@else
<article class="ws-catalog-row ws-offer-card {{ $rowAccent }} {{ $hasLaravel ? '' : 'ws-catalog-row--unlinked' }}"
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

    {{-- Image pleine largeur en tête : ratio fixe, ne compresse pas le texte --}}
    <div class="ws-offer-card__media{{ $imageUrl ? ' ws-offer-card__media--has-img' : '' }}">
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
            </div>
            @if($imageUrl)
                <img src="{{ e($imageUrl) }}" alt="" class="ws-offer-card__img" loading="lazy" decoding="async"
                     width="1200" height="675"
                     onerror="this.closest('.ws-offer-card__media-fill')?.classList.add('is-broken')">
            @endif
        </div>
        <div class="ws-offer-card__media-badges">
            <span class="{{ $badgeClass }}">{{ $typeShort }}</span>
            @if($isFeatured)
                <span class="ws-offer-chip ws-offer-chip--featured">Coup de cœur</span>
            @endif
            @if($hasPromo)
                <span class="ws-offer-chip ws-offer-chip--promo">Promo</span>
            @endif
        </div>
    </div>

    <div class="ws-offer-card__body">
        <header class="ws-offer-card__head">
            <div class="ws-offer-card__ref">
                <span class="ws-offer-card__code">{{ $row['code'] }}</span>
                @if($typeKey === 'package' && ! $hasLaravel)
                    <span class="ws-offer-card__unlink">Non lié Laravel</span>
                @endif
            </div>
            <div class="ws-offer-card__title-wrap">
                <h3 class="ws-offer-card__title">{{ $row['name'] }}</h3>
            </div>
            @if($summary !== '')
                <p class="ws-offer-card__summary">{{ $summary }}</p>
            @elseif(! empty($row['subtitle']) && $typeKey !== 'package')
                <p class="ws-offer-card__summary ws-offer-card__summary--muted">{{ $row['subtitle'] }}</p>
            @elseif($typeKey === 'package' && ! $hasLaravel && ! empty($row['subtitle']))
                <p class="ws-offer-card__summary ws-offer-card__summary--warn">{{ $row['subtitle'] }}</p>
            @endif
        </header>

        {{-- Bloc métadonnées compact (label + valeur), grille responsive --}}
        <div class="ws-offer-card__meta" role="group" aria-label="Informations principales">
            <div class="ws-offer-meta-item">
                <span class="ws-offer-meta-item__label">Départ</span>
                <span class="ws-offer-meta-item__value">
                    @if($hasDepDate)
                        {{ \Carbon\Carbon::parse($row['departure_date'])->locale('fr')->translatedFormat('d M Y') }}
                        @if($pkgDepCanceled)
                            <span class="ws-offer-chip ws-offer-chip--danger ws-offer-chip--inline">Annulé</span>
                        @endif
                    @else
                        <span class="ws-offer-meta-item__muted">À préciser</span>
                    @endif
                </span>
            </div>
            @if(! empty($row['voyage_destination']))
                <div class="ws-offer-meta-item">
                    <span class="ws-offer-meta-item__label">Destination</span>
                    <span class="ws-offer-meta-item__value">{{ $row['voyage_destination'] }}</span>
                </div>
            @endif
            <div class="ws-offer-meta-item">
                <span class="ws-offer-meta-item__label">Tarif</span>
                <span class="ws-offer-meta-item__value">
                    @if(! empty($row['price_label']))
                        <strong class="ws-offer-card__price">{{ $row['price_label'] }}</strong>
                    @else
                        <span class="ws-offer-meta-item__muted">Sur demande</span>
                    @endif
                </span>
            </div>
            @if($typeKey === 'package' && $hasLaravel)
                <div class="ws-offer-meta-item">
                    <span class="ws-offer-meta-item__label">Capacité</span>
                    <span class="ws-offer-meta-item__value">
                        @if(($placesState ?? '') === 'ok' && $placesTotal !== null)
                            <strong>{{ number_format((int) $placesTotal, 0, ',', ' ') }}</strong> places
                        @elseif(in_array($placesState ?? '', ['no_hotels', 'no_valid_rooms'], true))
                            <span class="ws-offer-meta-item__muted">Non renseignée</span>
                        @else
                            <span class="ws-offer-meta-item__muted">—</span>
                        @endif
                    </span>
                </div>
            @endif
        </div>

        <div class="ws-offer-card__status-row" aria-label="Statut">
            @if($pkgDepCanceled)
                <span class="ws-offer-chip ws-offer-chip--danger">Départ annulé</span>
            @elseif($hasDepDate && $isPast)
                <span class="ws-offer-chip ws-offer-chip--past">Passé</span>
            @elseif($isUpcoming)
                <span class="ws-offer-chip ws-offer-chip--ok">À venir</span>
            @elseif(! $hasDepDate)
                <span class="ws-offer-chip ws-offer-chip--muted">Sans date</span>
            @endif
            @if($typeKey === 'package' && $hasLaravel && $availBadge['label'])
                <span class="{{ $availBadge['class'] }}">{{ $availBadge['label'] }}</span>
            @endif
        </div>

        @if($typeKey === 'package' && $placesState === 'ok' && is_array($placesLines) && count($placesLines) > 0)
            <div class="ws-offer-card__rooms">
                @foreach($placesLines as $ln)
                    @php
                        $rt = (string) ($ln['room_type'] ?? '');
                        $pr = (int) ($ln['product'] ?? 0);
                        $tip = $rt.' : '.$pr;
                    @endphp
                    <span class="ws-room-badge" title="{{ e($tip) }}">{{ $rt }} <span class="ws-room-badge__n">{{ $pr }}</span></span>
                @endforeach
            </div>
        @endif

        <div class="ws-offer-card__footer">
            <div class="ws-offer-card__stats" aria-label="Réservations">
                <span class="ws-stat-pill ws-stat-pill--ok" title="Confirmées">{{ $statVal }}</span>
                <span class="ws-stat-pill ws-stat-pill--wait" title="En attente">{{ $statPending }}</span>
                <span class="ws-stat-pill ws-stat-pill--off" title="Annulées">{{ $statCancel }}</span>
            </div>
            <div class="ws-offer-card__actions">
                @if(!empty($modalDetail))
                    <button type="button"
                        class="ws-btn ws-btn--secondary btn-ws-open-detail"
                        data-row-code="{{ e($row['code']) }}"
                        title="Voir la prestation">
                        <i class="fas fa-eye" aria-hidden="true"></i><span>Voir</span>
                    </button>
                @endif
                @can('reservations.view')
                    @if($hasLaravel)
                        <a href="{{ $reserveUrl }}"
                            class="ws-btn ws-btn--primary"
                            title="{{ $reserveLabel }}">
                            @if($typeKey === 'vol')
                                <i class="fas fa-plane-departure" aria-hidden="true"></i>
                            @else
                                <i class="fas fa-suitcase-rolling" aria-hidden="true"></i>
                            @endif
                            <span>{{ $reserveLabel }}</span>
                        </a>
                    @else
                        <a href="{{ $editTourUrl ?: '#' }}"
                            class="ws-btn {{ $editTourUrl ? 'ws-btn--ghost' : 'ws-btn--disabled' }}"
                            {!! $editTourUrl ? '' : 'aria-disabled="true"' !!}
                            title="Associer la fiche Laravel pour réserver">
                            <i class="fas fa-link" aria-hidden="true"></i><span>Lier</span>
                        </a>
                    @endif
                @endcan
            </div>
        </div>
    </div>
</article>
@endif
