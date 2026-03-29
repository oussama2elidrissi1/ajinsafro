@php
    $hasLaravel = ! empty($row['voyage_id']);
    $wpPostId = $row['wp_post_id'] ?? null;
    $q = $hasLaravel ? ['voyage_id' => $row['voyage_id']] : [];
    if ($hasLaravel && ! empty($row['travel_date_id'])) {
        $q['travel_date_id'] = $row['travel_date_id'];
    }
    $participantsUrl = $hasLaravel ? route('admin.reservations.workspace.prestation.participants', $q) : '#';
    $pdfUrl = $hasLaravel ? route('admin.reservations.workspace.prestation.pdf', $q) : '#';
    $editTourUrl = $wpPostId ? route('admin.circuits.voyages.edit', $wpPostId) : null;
    $hasDepDate = ! empty($row['departure_date']);
    $depLabel = $hasDepDate
        ? \Carbon\Carbon::parse($row['departure_date'])->locale('fr')->translatedFormat('d M Y')
        : '—';
    $typeKey = $row['type'] ?? 'package';
    $badgeClass = match ($typeKey) {
        'package' => 'bg-orange-50 text-brand-orange border-orange-200/80',
        'vol' => 'bg-blue-50 text-brand-blue border-blue-200/80',
        default => 'bg-amber-50 text-amber-800 border-amber-200/80',
    };
    $typeShort = match ($typeKey) {
        'package' => 'Package',
        'vol' => 'Vol',
        default => 'Hébergement',
    };
    $rowAccent = match ($typeKey) {
        'package' => 'border-l-[3px] border-l-orange-400',
        'vol' => 'border-l-[3px] border-l-brand-blue',
        default => 'border-l-[3px] border-l-amber-500',
    };
    $stats = $row['stats'] ?? ['validee' => 0, 'en_cours' => 0, 'annulee' => 0];
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
@endphp
<tr class="ws-catalog-row group border-b border-gray-100/90 last:border-0 hover:bg-slate-50/80 transition-colors {{ $rowAccent }} {{ $hasLaravel ? '' : 'bg-amber-50/25' }}"
    data-type="{{ $typeKey }}"
    data-row-code="{{ $row['code'] }}"
    data-code="{{ $row['code'] }}"
    data-name="{{ $row['name'] }}"
    data-search="{{ e($wsSearchBlob) }}"
    data-dep="{{ $row['departure_date'] ? \Carbon\Carbon::parse($row['departure_date'])->format('Y-m-d') : '' }}"
    data-ws-avail="{{ e($wsAvail) }}"
    data-ws-upcoming="{{ $wsUpcoming ? '1' : '0' }}">
    {{-- Réf. & type --}}
    <td class="py-3.5 px-4 sm:px-5 align-top w-[120px] sm:w-[132px]">
        <span class="text-xs font-extrabold text-brand-dark block font-mono tracking-tight leading-tight">{{ $row['code'] }}</span>
        @if($typeKey === 'package' && $hasLaravel && !empty($row['voyage_id']))
            <span class="block text-[9px] text-slate-400 font-mono mt-1">Laravel #{{ $row['voyage_id'] }}</span>
        @endif
        <span class="inline-flex mt-2 px-2 py-0.5 {{ $badgeClass }} text-[9px] font-extrabold rounded-md uppercase tracking-wide border">{{ $typeShort }}</span>
        @if($typeKey === 'package' && ! $hasLaravel)
            <span class="block mt-2 text-[9px] font-bold text-amber-800 uppercase tracking-wide leading-snug">Non lié Laravel</span>
        @endif
    </td>
    {{-- Prestation --}}
    <td class="py-3.5 px-4 sm:px-5 align-top min-w-0 max-w-[min(100vw,420px)]">
        <p class="font-bold text-brand-dark text-sm leading-snug line-clamp-3 sm:line-clamp-2" title="{{ e($row['name']) }}">{{ $row['name'] }}</p>
        @if(!empty($row['subtitle']))
            <p class="text-[11px] text-slate-500 mt-1.5 line-clamp-2 leading-relaxed">{{ $row['subtitle'] }}</p>
        @endif
        @if($typeKey === 'package' && $hasLaravel)
            @php
                $ps = $placesState ?? '';
            @endphp
            <div class="mt-2 space-y-2">
                @if(! empty($row['voyage_destination']))
                    <p class="text-[11px] text-slate-600 leading-snug flex items-center gap-1.5">
                        <span class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-md bg-brand-blue/10 text-brand-blue" aria-hidden="true"><i class="fas fa-map-marker-alt text-[10px]"></i></span>
                        <span class="font-medium text-slate-700">{{ $row['voyage_destination'] }}</span>
                    </p>
                @endif

                {{-- Zone 1 : date + prix + places sur une ligne (desktop) --}}
                <div class="flex flex-col gap-1.5 sm:flex-row sm:flex-wrap sm:items-baseline sm:gap-x-2 sm:gap-y-1 text-[11px] leading-snug">
                    <span class="inline-flex items-center gap-1.5 text-slate-700 min-w-0">
                        <i class="far fa-calendar-alt text-brand-blue/80 shrink-0 text-[12px]" aria-hidden="true"></i>
                        @if($hasDepDate)
                            <span class="font-semibold text-brand-dark tabular-nums">{{ \Carbon\Carbon::parse($row['departure_date'])->locale('fr')->translatedFormat('d M Y') }}</span>
                            @if($pkgDepCanceled)
                                <span class="ml-0.5 inline-flex text-[9px] font-bold uppercase tracking-wide text-red-700 bg-red-50 border border-red-100/90 px-1.5 py-0.5 rounded-full">Annulé</span>
                            @endif
                        @else
                            <span class="text-slate-400 font-medium">Aucune date</span>
                        @endif
                    </span>

                    <span class="hidden sm:inline text-slate-300 font-light select-none" aria-hidden="true">·</span>

                    <span class="inline-flex items-center gap-1.5 text-slate-700 min-w-0">
                        <i class="fas fa-coins text-emerald-600/85 shrink-0 text-[11px]" aria-hidden="true"></i>
                        @if(! empty($row['price_label']))
                            <span class="font-semibold text-brand-dark">{{ $row['price_label'] }}</span>
                        @else
                            <span class="text-slate-400 font-medium">Sur demande</span>
                        @endif
                    </span>

                    <span class="hidden sm:inline text-slate-300 font-light select-none" aria-hidden="true">·</span>

                    <span class="inline-flex items-center gap-1.5 text-slate-700 min-w-0">
                        <i class="fas fa-users text-slate-500 shrink-0 text-[11px]" aria-hidden="true"></i>
                        @if($ps === 'ok' && $placesTotal !== null)
                            <span class="font-semibold text-brand-dark tabular-nums">{{ number_format((int) $placesTotal, 0, ',', ' ') }} places</span>
                        @elseif(in_array($ps, ['no_hotels', 'no_valid_rooms'], true))
                            <span class="text-slate-400 font-medium">Places non renseignées</span>
                        @else
                            <span class="text-slate-400 font-medium">—</span>
                        @endif
                    </span>
                </div>

                {{-- Zone 2 : badges chambres + tooltip détail --}}
                @if($ps === 'ok' && is_array($placesLines) && count($placesLines) > 0)
                    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:gap-2 pt-0.5">
                        <span class="inline-flex items-center gap-1 text-[10px] font-semibold uppercase tracking-wide text-slate-400 shrink-0">
                            <i class="fas fa-bed text-slate-400 text-[10px]" aria-hidden="true"></i>
                            Chambres
                        </span>
                        <div class="flex flex-wrap items-center gap-1.5">
                            @foreach($placesLines as $ln)
                                @php
                                    $rt = (string) ($ln['room_type'] ?? '');
                                    $rc = (int) ($ln['room_count'] ?? 0);
                                    $cu = (int) ($ln['capacity_used'] ?? 0);
                                    $pr = (int) ($ln['product'] ?? 0);
                                    $tip = $rt.' : '.$rc.' × '.$cu.' = '.$pr;
                                @endphp
                                <span
                                    class="ws-room-badge inline-flex items-center rounded-full border border-slate-200/90 bg-gradient-to-b from-white to-slate-50/90 px-2.5 py-0.5 text-[10px] font-semibold text-slate-700 shadow-sm tabular-nums transition-colors hover:border-brand-blue/30 hover:bg-brand-light/40 hover:text-brand-dark cursor-default"
                                    title="{{ e($tip) }}"
                                    aria-label="{{ e($tip) }}"
                                >{{ $rt }} <span class="text-slate-400 font-medium mx-0.5">·</span> {{ $pr }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @elseif($typeKey === 'package' && ! $hasLaravel)
            <div class="mt-2 space-y-2 text-[11px]">
                @if(! empty($row['price_label']))
                    <div class="flex flex-wrap items-center gap-2 text-slate-700">
                        <i class="fas fa-coins text-emerald-600/80 text-[11px]" aria-hidden="true"></i>
                        <span class="font-semibold text-brand-dark">{{ $row['price_label'] }}</span>
                        <span class="text-[10px] text-slate-400">(WordPress — liez Laravel pour départs & réservation)</span>
                    </div>
                @endif
                <p class="flex items-start gap-1.5 text-amber-800/95">
                    <i class="fas fa-link mt-0.5 text-[10px] opacity-80" aria-hidden="true"></i>
                    <span>Liez <span class="font-mono text-[10px] bg-amber-100/80 px-1 rounded">voyages.wp_post_id</span> pour départs et réservation.</span>
                </p>
            </div>
        @endif
        @if($editTourUrl && $typeKey === 'package' && ! $hasLaravel)
            <a href="{{ $editTourUrl }}" class="inline-flex items-center gap-1.5 mt-2 text-[11px] font-bold text-brand-blue hover:underline">
                <i class="fas fa-external-link-alt text-[10px]"></i> Circuits / voyages
            </a>
        @endif
    </td>
    {{-- Départ --}}
    <td class="py-3.5 px-4 sm:px-5 align-top whitespace-nowrap w-[130px]">
        <p class="text-xs font-semibold text-slate-800">{{ $depLabel }}</p>
        <div class="flex flex-wrap gap-1 mt-1.5">
            @if($pkgDepCanceled)
                <span class="inline-flex items-center text-[9px] font-bold uppercase tracking-wide text-red-800 bg-red-50 border border-red-100 px-1.5 py-0.5 rounded-md">Départ annulé</span>
            @endif
            @if($hasDepDate && $isPast && ! $pkgDepCanceled)
                <span class="inline-flex items-center text-[9px] font-bold uppercase tracking-wide text-amber-800 bg-amber-50 border border-amber-200 px-1.5 py-0.5 rounded-md">Passé</span>
            @elseif($isUpcoming && ! $pkgDepCanceled)
                <span class="inline-flex items-center text-[9px] font-bold uppercase tracking-wide text-emerald-800 bg-emerald-50 border border-emerald-200 px-1.5 py-0.5 rounded-md">À venir</span>
            @elseif(! $hasDepDate && $typeKey === 'package' && $hasLaravel)
                <span class="text-[10px] text-slate-400 font-medium">—</span>
            @elseif(! $hasDepDate && $typeKey !== 'package')
                <span class="text-[10px] text-slate-400 font-medium">Aucune date</span>
            @endif
        </div>
    </td>
    {{-- Stats --}}
    <td class="py-3.5 px-3 align-middle text-center w-[150px] sm:w-[168px]">
        <div class="inline-flex flex-col sm:flex-row flex-wrap items-center justify-center gap-1.5">
            <span class="inline-flex items-center gap-1 min-w-[3.25rem] justify-center bg-emerald-50 text-emerald-800 px-2 py-1 rounded-lg text-[10px] font-bold border border-emerald-200/90 shadow-sm" title="Confirmées">
                <i class="fas fa-check-circle text-[9px] opacity-90"></i>{{ $stats['validee'] }}
            </span>
            <span class="inline-flex items-center gap-1 min-w-[3.25rem] justify-center bg-amber-50 text-amber-900 px-2 py-1 rounded-lg text-[10px] font-bold border border-amber-200/90 shadow-sm" title="En attente">
                <i class="fas fa-hourglass-half text-[9px] opacity-90"></i>{{ $stats['en_cours'] }}
            </span>
            <span class="inline-flex items-center gap-1 min-w-[3.25rem] justify-center bg-red-50 text-red-700 px-2 py-1 rounded-lg text-[10px] font-bold border border-red-200/90 shadow-sm" title="Annulées">
                <i class="fas fa-times-circle text-[9px] opacity-90"></i>{{ $stats['annulee'] }}
            </span>
        </div>
    </td>
    {{-- Actions --}}
    <td class="py-3.5 px-3 sm:px-4 align-middle text-right">
        <div class="flex flex-col sm:flex-row flex-wrap items-stretch sm:items-center justify-end gap-2 min-w-0 sm:min-w-[320px]">
            @if(!empty($modalDetail))
                <button type="button"
                    class="btn-ws-open-detail inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-300 bg-white px-2.5 py-2 text-[11px] font-bold text-slate-700 shadow-sm hover:border-brand-blue hover:text-brand-blue hover:bg-brand-light/40 transition-all order-0"
                    data-row-code="{{ e($row['code']) }}"
                    title="Détails du voyage">
                    <i class="fas fa-chart-line text-xs" aria-hidden="true"></i>
                    <span class="hidden sm:inline">Détails</span>
                </button>
            @endif
            @if($hasLaravel)
                <a href="{{ $participantsUrl }}"
                   class="ws-action-link inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-2.5 py-2 text-[11px] font-bold text-slate-600 shadow-sm hover:border-brand-blue hover:bg-brand-blue hover:text-white transition-all order-1 sm:order-none">
                    <i class="fas fa-eye text-xs"></i>
                    <span class="hidden sm:inline">Participants</span>
                </a>
                <a href="{{ $pdfUrl }}"
                   class="ws-action-link inline-flex items-center justify-center gap-1.5 rounded-xl border border-red-100 bg-red-50 px-2.5 py-2 text-[11px] font-bold text-red-700 shadow-sm hover:bg-red-600 hover:text-white hover:border-red-600 transition-all order-2 sm:order-none">
                    <i class="fas fa-file-pdf text-xs"></i>
                    <span class="hidden sm:inline">PDF</span>
                </a>
            @else
                <span class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-dashed border-slate-200 px-2.5 py-2 text-[10px] font-semibold text-slate-300 cursor-not-allowed order-1" title="Liez voyages.wp_post_id">
                    <i class="fas fa-eye"></i><span class="hidden sm:inline">Participants</span>
                </span>
                <span class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-dashed border-slate-200 px-2.5 py-2 text-[10px] font-semibold text-slate-300 cursor-not-allowed order-2" title="Liez voyages.wp_post_id">
                    <i class="fas fa-file-pdf"></i><span class="hidden sm:inline">PDF</span>
                </span>
            @endif
            @can('reservations.view')
                @if($hasLaravel)
                    <button type="button"
                        class="btn-show-add-reservation inline-flex items-center justify-center gap-1.5 rounded-xl bg-gradient-to-b from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 text-white px-3 py-2.5 text-[11px] sm:text-xs font-extrabold shadow-md shadow-emerald-500/25 border border-emerald-400/40 transition-all order-3 sm:order-none sm:ml-0"
                        data-row-code="{{ $row['code'] }}"
                        data-type="{{ $typeKey }}"
                        data-name="{{ $row['name'] }} ({{ $row['code'] }})"
                        data-tour-id="{{ $row['voyage_id'] }}"
                        data-travel-date-id="{{ $row['travel_date_id'] ?? '' }}">
                        @if($typeKey === 'vol')
                            <i class="fas fa-plane-departure text-xs"></i>
                        @else
                            <i class="fas fa-suitcase-rolling text-xs"></i>
                        @endif
                        <span>{{ $reserveLabel }}</span>
                    </button>
                @else
                    <button type="button" disabled
                        class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-slate-200 text-slate-500 px-3 py-2.5 text-[11px] font-bold cursor-not-allowed border border-slate-200 order-3"
                        title="Créez voyages.wp_post_id = {{ $wpPostId }}">
                        <i class="fas fa-link"></i><span class="hidden sm:inline">Lier d’abord</span>
                    </button>
                @endif
            @endcan
        </div>
    </td>
</tr>
