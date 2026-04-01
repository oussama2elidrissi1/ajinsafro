@php
    $hasLaravel = ! empty($row['voyage_id']);
    $wpPostId = $row['wp_post_id'] ?? null;
    $q = $hasLaravel ? ['voyage_id' => $row['voyage_id']] : [];
    $participantsUrl = $hasLaravel ? route('admin.reservations.index', $q) : '#';
    $pdfUrl = $hasLaravel ? route('admin.reservations.workspace.prestation.pdf', $q) : '#';
    $editTourUrl = $wpPostId ? route('admin.circuits.voyages.edit', $wpPostId) : null;
    $hasDepDate = ! empty($row['departure_date']);
    $depLabel = $hasDepDate
        ? \Carbon\Carbon::parse($row['departure_date'])->locale('fr')->translatedFormat('d M Y')
        : '—';
    $typeKey = $row['type'] ?? 'package';
    $badgeClass = match ($typeKey) {
        'package' => 'ws-type-badge ws-type-badge--package',
        'vol' => 'ws-type-badge ws-type-badge--vol',
        default => 'ws-type-badge ws-type-badge--hotel',
    };
    $typeShort = match ($typeKey) {
        'package' => 'Package',
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
    if ($typeKey === 'package' && $hasLaravel) {
        if (($placesState ?? '') === 'ok' && $placesTotal !== null) {
            $placesSearchBits = ' places '.$placesTotal;
            foreach ($placesLines as $pl) {
                $placesSearchBits .= ' '.($pl['room_type'] ?? '').' '.($pl['product'] ?? '');
            }
        }
    }
    $wsSearchBlob = \Illuminate\Support\Str::lower(trim(
        ($row['name'] ?? '')
        . ' ' . ($row['code'] ?? '')
        . ' ' . ($row['subtitle'] ?? '')
        . ' ' . ($row['voyage_destination'] ?? '')
        . ' ' . ($row['price_label'] ?? '')
        . $placesSearchBits
    ));
    $pkgDepCanceled = $typeKey === 'package' && ! empty($row['departure_is_canceled']);
    $reserveLabel = $typeKey === 'vol' ? 'Réserver vol' : 'Réserver';
    $modalDetail = $row['modal_detail'] ?? null;
    $wsAvail = $row['ws_avail'] ?? 'na';
    $wsUpcoming = ! empty($row['ws_has_future']);

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
@endphp
<tr class="ws-catalog-row {{ $rowAccent }} {{ $hasLaravel ? '' : 'ws-catalog-row--unlinked' }}"
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
    {{-- Réf. & type --}}
    <td class="ws-td ws-td--ref">
        <span class="ws-ref-code">{{ $row['code'] }}</span>
        <span class="{{ $badgeClass }}">{{ $typeShort }}</span>
        @if($typeKey === 'package' && ! $hasLaravel)
            <span class="ws-ref-hint">Non lié</span>
        @endif
    </td>
    {{-- Prestation --}}
    <td class="ws-td ws-td--prestation">
        <div class="ws-prest">
            <p class="ws-prest__title">{{ $row['name'] }}</p>
            @if(!empty($row['subtitle']) && $typeKey !== 'package')
                <p class="ws-prest__sub">{{ $row['subtitle'] }}</p>
            @endif

            @if($typeKey === 'package' && $hasLaravel)
                @if(! empty($row['voyage_destination']))
                    <p class="ws-prest__line"><span class="ws-prest__ico" aria-hidden="true">📍</span><span>{{ $row['voyage_destination'] }}</span></p>
                @endif
                <div class="ws-prest__meta">
                    <span class="ws-prest__meta-item">
                        <span class="ws-prest__ico" aria-hidden="true">📅</span>
                        @if($hasDepDate)
                            <span class="ws-prest__strong">{{ \Carbon\Carbon::parse($row['departure_date'])->locale('fr')->translatedFormat('d M Y') }}</span>
                            @if($pkgDepCanceled)
                                <span class="ws-mini-tag ws-mini-tag--danger">Annulé</span>
                            @endif
                        @else
                            <span class="ws-prest__muted">Aucune date</span>
                        @endif
                    </span>
                    <span class="ws-prest__meta-item">
                        <span class="ws-prest__ico" aria-hidden="true">💰</span>
                        @if(! empty($row['price_label']))
                            <span class="ws-prest__strong">{{ $row['price_label'] }}</span>
                        @else
                            <span class="ws-prest__muted">Sur demande</span>
                        @endif
                    </span>
                    <span class="ws-prest__meta-item">
                        <span class="ws-prest__ico" aria-hidden="true">👥</span>
                        @if(($placesState ?? '') === 'ok' && $placesTotal !== null)
                            <span class="ws-prest__strong">{{ number_format((int) $placesTotal, 0, ',', ' ') }} pl.</span>
                        @elseif(in_array($placesState ?? '', ['no_hotels', 'no_valid_rooms'], true))
                            <span class="ws-prest__muted">Non renseigné</span>
                        @else
                            <span class="ws-prest__muted">—</span>
                        @endif
                    </span>
                </div>
                @if($placesState === 'ok' && is_array($placesLines) && count($placesLines) > 0)
                    <div class="ws-prest__rooms">
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
            @elseif($typeKey === 'package' && ! $hasLaravel)
                <p class="ws-prest__warn">
                    @if(! empty($row['price_label']))
                        <span class="ws-prest__strong">{{ $row['price_label'] }}</span>
                        <span class="ws-prest__muted"> · Liez la fiche Laravel pour réserver.</span>
                    @else
                        <span class="ws-prest__muted">Liez la fiche Laravel pour réserver.</span>
                    @endif
                </p>
                @if($editTourUrl)
                    <a href="{{ $editTourUrl }}" class="ws-prest__link">Ouvrir dans Circuits</a>
                @endif
            @else
                {{-- vol / hébergement : ligne compacte --}}
                <div class="ws-prest__meta ws-prest__meta--single">
                    <span class="ws-prest__meta-item">
                        <span class="ws-prest__ico" aria-hidden="true">📅</span>
                        @if($hasDepDate)
                            <span class="ws-prest__strong">{{ \Carbon\Carbon::parse($row['departure_date'])->locale('fr')->translatedFormat('d M Y') }}</span>
                        @else
                            <span class="ws-prest__muted">Aucune date</span>
                        @endif
                    </span>
                </div>
            @endif
        </div>
    </td>
    {{-- Départ --}}
    <td class="ws-td ws-td--dep">
        <span class="ws-dep-date">{{ $depLabel }}</span>
        <div class="ws-dep-badges">
            @if($pkgDepCanceled)
                <span class="ws-state-badge ws-state-badge--danger">Annulé</span>
            @elseif($hasDepDate && $isPast)
                <span class="ws-state-badge ws-state-badge--past">Passé</span>
            @elseif($isUpcoming)
                <span class="ws-state-badge ws-state-badge--ok">À venir</span>
            @elseif(! $hasDepDate)
                <span class="ws-state-badge ws-state-badge--muted">Sans date</span>
            @endif
        </div>
    </td>
    {{-- Stats --}}
    <td class="ws-td ws-td--stats">
        <div class="ws-stats-pills">
            <span class="ws-stat-pill ws-stat-pill--ok" title="Confirmées">{{ $statVal }}</span>
            <span class="ws-stat-pill ws-stat-pill--wait" title="En attente">{{ $statPending }}</span>
            <span class="ws-stat-pill ws-stat-pill--off" title="Annulées">{{ $statCancel }}</span>
        </div>
    </td>
    {{-- Actions --}}
    <td class="ws-td ws-td--actions">
        <div class="ws-actions">
            @if(!empty($modalDetail))
                <button type="button"
                    class="ws-btn ws-btn--secondary btn-ws-open-detail"
                    data-row-code="{{ e($row['code']) }}"
                    title="Détails de la prestation">
                    <i class="fas fa-info-circle" aria-hidden="true"></i><span>Détails</span>
                </button>
            @endif
            @if($hasLaravel)
                <a href="{{ $participantsUrl }}"
                   class="ws-btn ws-btn--ghost ws-action-link"
                   title="Liste des réservations et participants">
                    <i class="fas fa-list-ul" aria-hidden="true"></i><span>Réservations</span>
                </a>
                <a href="{{ $pdfUrl }}"
                   class="ws-btn ws-btn--pdf ws-action-link"
                   title="Exporter PDF">
                    <i class="fas fa-file-pdf" aria-hidden="true"></i><span>PDF</span>
                </a>
            @else
                <span class="ws-btn ws-btn--disabled" title="Liez d’abord la fiche voyage"><i class="fas fa-list-ul"></i><span>Réservations</span></span>
                <span class="ws-btn ws-btn--disabled" title="Liez d’abord la fiche voyage"><i class="fas fa-file-pdf"></i><span>PDF</span></span>
            @endif
            @can('reservations.view')
                @if($hasLaravel)
                    <button type="button"
                        class="ws-btn ws-btn--primary btn-show-add-reservation"
                        data-row-code="{{ $row['code'] }}"
                        data-type="{{ $typeKey }}"
                        data-name="{{ $row['name'] }} ({{ $row['code'] }})"
                        data-tour-id="{{ $row['voyage_id'] }}"
                        data-travel-date-id="{{ $row['travel_date_id'] ?? '' }}"
                        title="{{ $reserveLabel }}">
                        @if($typeKey === 'vol')
                            <i class="fas fa-plane-departure" aria-hidden="true"></i>
                        @else
                            <i class="fas fa-suitcase-rolling" aria-hidden="true"></i>
                        @endif
                        <span>{{ $reserveLabel }}</span>
                    </button>
                @else
                    <button type="button" disabled class="ws-btn ws-btn--disabled" title="Associez la fiche Laravel pour réserver">
                        <i class="fas fa-link" aria-hidden="true"></i><span>Lier</span>
                    </button>
                @endif
            @endcan
        </div>
    </td>
</tr>
