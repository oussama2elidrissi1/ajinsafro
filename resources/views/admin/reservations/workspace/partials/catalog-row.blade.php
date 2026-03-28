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
    $wsSearchBlob = \Illuminate\Support\Str::lower(trim(
        ($row['name'] ?? '')
        . ' ' . ($row['code'] ?? '')
        . ' ' . ($row['subtitle'] ?? '')
        . ' ' . ($row['voyage_destination'] ?? '')
        . ' ' . ($row['price_label'] ?? '')
    ));
    $pkgDepCanceled = $typeKey === 'package' && ! empty($row['departure_is_canceled']);
    $reserveLabel = $typeKey === 'vol' ? 'Réserver vol' : 'Réserver';
@endphp
<tr class="ws-catalog-row group border-b border-gray-100/90 last:border-0 hover:bg-slate-50/80 transition-colors {{ $rowAccent }} {{ $hasLaravel ? '' : 'bg-amber-50/25' }}"
    data-type="{{ $typeKey }}"
    data-row-code="{{ $row['code'] }}"
    data-code="{{ $row['code'] }}"
    data-name="{{ $row['name'] }}"
    data-search="{{ e($wsSearchBlob) }}"
    data-dep="{{ $row['departure_date'] ? \Carbon\Carbon::parse($row['departure_date'])->format('Y-m-d') : '' }}">
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
            <div class="mt-2.5 space-y-1.5 rounded-xl border border-slate-100 bg-slate-50/70 px-3 py-2.5 shadow-sm/30">
                @if(!empty($row['voyage_destination']))
                    <p class="text-[11px] text-slate-600 leading-snug flex items-start gap-2">
                        <i class="fas fa-map-marker-alt text-brand-blue mt-0.5 text-[10px] shrink-0 opacity-90"></i>
                        <span>{{ $row['voyage_destination'] }}</span>
                    </p>
                @endif
                <p class="text-[11px] leading-snug">
                    <span class="text-[9px] font-extrabold uppercase tracking-wider text-slate-500">Départ</span>
                    @if($hasDepDate)
                        <span class="font-semibold text-brand-dark"> {{ \Carbon\Carbon::parse($row['departure_date'])->locale('fr')->translatedFormat('d M Y') }}</span>
                        @if($pkgDepCanceled)
                            <span class="ml-1.5 align-middle inline-flex text-[8px] font-bold uppercase text-red-700 bg-red-50 border border-red-100 px-1.5 py-0.5 rounded-md">Annulé</span>
                        @endif
                    @else
                        <span class="text-slate-400 font-medium"> Aucune date</span>
                    @endif
                </p>
                <p class="text-[11px] leading-snug border-t border-slate-200/80 pt-1.5 mt-0.5">
                    <span class="text-[9px] font-extrabold uppercase tracking-wider text-slate-500">Prix adulte</span>
                    @if(!empty($row['price_label']))
                        <span class="font-semibold text-brand-dark"> {{ $row['price_label'] }}</span>
                    @else
                        <span class="text-slate-400 font-medium"> Sur demande</span>
                    @endif
                </p>
            </div>
        @elseif($typeKey === 'package' && ! $hasLaravel)
            <div class="mt-2.5 space-y-2">
                @if(!empty($row['price_label']))
                    <div class="rounded-xl border border-slate-100 bg-white px-3 py-2 text-[11px] shadow-sm">
                        <span class="text-[9px] font-extrabold uppercase tracking-wider text-slate-500">Prix adulte</span>
                        <span class="font-semibold text-brand-dark"> {{ $row['price_label'] }}</span>
                        <span class="text-[9px] text-slate-400 block mt-0.5">Source WordPress · liez Laravel pour départs et réservation.</span>
                    </div>
                @endif
                <div class="rounded-xl border border-dashed border-amber-200 bg-amber-50/50 px-3 py-2 text-[11px] text-amber-900/90">
                    <span class="text-[9px] font-extrabold uppercase tracking-wide text-amber-800">Départ</span>
                    <span class="text-slate-600"> — liez <span class="font-mono text-[10px]">voyages.wp_post_id</span> pour afficher les départs Laravel.</span>
                </div>
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
        <div class="flex flex-col sm:flex-row flex-wrap items-stretch sm:items-center justify-end gap-2 min-w-0 sm:min-w-[280px]">
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
