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
    $depLabel = $row['departure_date']
        ? \Carbon\Carbon::parse($row['departure_date'])->locale('fr')->translatedFormat('d M Y')
        : '—';
    $typeKey = $row['type'] ?? 'package';
    $badgeClass = match ($typeKey) {
        'package' => 'bg-orange-50 text-brand-orange border-orange-100',
        'vol' => 'bg-blue-50 text-brand-blue border-blue-100',
        default => 'bg-amber-50 text-amber-700 border-amber-100',
    };
    $typeShort = match ($typeKey) {
        'package' => 'Package',
        'vol' => 'Vol',
        default => 'Hébergement',
    };
    $stats = $row['stats'] ?? ['validee' => 0, 'en_cours' => 0, 'annulee' => 0];
    $isPast = ! empty($row['departure_is_past']);
    $wsSearchBlob = \Illuminate\Support\Str::lower(trim(
        ($row['name'] ?? '')
        . ' ' . ($row['code'] ?? '')
        . ' ' . ($row['subtitle'] ?? '')
    ));
@endphp
<tr class="ws-catalog-row group border-b border-gray-100/80 last:border-0 hover:bg-gradient-to-r hover:from-[#e6f3fa]/40 hover:to-transparent transition-colors {{ $hasLaravel ? '' : 'bg-amber-50/20' }}"
    data-type="{{ $typeKey }}"
    data-row-code="{{ $row['code'] }}"
    data-code="{{ $row['code'] }}"
    data-name="{{ $row['name'] }}"
    data-search="{{ e($wsSearchBlob) }}"
    data-dep="{{ $row['departure_date'] ? \Carbon\Carbon::parse($row['departure_date'])->format('Y-m-d') : '' }}">
    <td class="py-4 px-5 sm:px-6 align-middle">
        <span class="text-xs font-bold text-gray-500 block mb-1.5 font-mono tracking-tight">{{ $row['code'] }}</span>
        <span class="inline-flex px-2 py-0.5 {{ $badgeClass }} text-[9px] font-bold rounded-md uppercase tracking-wide border">{{ $typeShort }}</span>
        @if($typeKey === 'package' && ! $hasLaravel)
            <span class="block mt-1.5 text-[9px] font-bold text-amber-800 uppercase tracking-wide">Non lié Laravel</span>
        @endif
    </td>
    <td class="py-4 px-5 sm:px-6 align-middle min-w-[200px]">
        <p class="font-bold text-brand-dark text-sm leading-snug">{{ $row['name'] }}</p>
        @if(!empty($row['subtitle']))
            <p class="text-[11px] text-gray-500 mt-1">{{ $row['subtitle'] }}</p>
        @endif
        @if($editTourUrl && $typeKey === 'package' && ! $hasLaravel)
            <a href="{{ $editTourUrl }}" class="inline-flex items-center gap-1 mt-2 text-[11px] font-bold text-brand-blue hover:underline">
                <i class="fas fa-external-link-alt text-[10px]"></i> Ouvrir dans Circuits / voyages
            </a>
        @endif
    </td>
    <td class="py-4 px-5 sm:px-6 align-middle whitespace-nowrap">
        <p class="text-xs font-semibold text-gray-800">{{ $depLabel }}</p>
        @if($isPast && $row['departure_date'])
            <span class="inline-block mt-1 text-[9px] font-bold uppercase tracking-wide text-amber-700 bg-amber-50 border border-amber-100 px-1.5 py-0.5 rounded">Passé</span>
        @endif
    </td>
    <td class="py-4 px-4 align-middle text-center">
        <div class="inline-flex flex-wrap items-center justify-center gap-1.5 sm:gap-2">
            <span class="inline-flex items-center gap-1 bg-emerald-50 text-emerald-700 px-2 py-1 rounded-lg text-[10px] sm:text-xs font-bold border border-emerald-100/80" title="Confirmées">
                <i class="fas fa-check-circle text-[10px] opacity-80"></i>{{ $stats['validee'] }}
            </span>
            <span class="inline-flex items-center gap-1 bg-amber-50 text-amber-800 px-2 py-1 rounded-lg text-[10px] sm:text-xs font-bold border border-amber-100/80" title="En attente">
                <i class="fas fa-hourglass-half text-[10px] opacity-80"></i>{{ $stats['en_cours'] }}
            </span>
            <span class="inline-flex items-center gap-1 bg-red-50 text-red-600 px-2 py-1 rounded-lg text-[10px] sm:text-xs font-bold border border-red-100/80" title="Annulées">
                <i class="fas fa-times-circle text-[10px] opacity-80"></i>{{ $stats['annulee'] }}
            </span>
        </div>
    </td>
    <td class="py-4 px-5 sm:px-6 align-middle text-right">
        <div class="inline-flex items-center justify-end gap-1.5 sm:gap-2 flex-wrap">
            @if($hasLaravel)
                <a href="{{ $participantsUrl }}" class="ws-action-btn w-9 h-9 rounded-xl bg-gray-50 text-gray-500 hover:bg-brand-blue hover:text-white hover:border-brand-blue border border-gray-200/80 shadow-sm flex items-center justify-center transition-all" title="Voir les participants">
                    <i class="fas fa-eye text-sm"></i>
                </a>
                <a href="{{ $pdfUrl }}" class="ws-action-btn w-9 h-9 rounded-xl bg-gray-50 text-gray-500 hover:bg-red-500 hover:text-white hover:border-red-500 border border-gray-200/80 shadow-sm flex items-center justify-center transition-all" title="Télécharger le PDF">
                    <i class="fas fa-file-pdf text-sm"></i>
                </a>
            @else
                <span class="w-9 h-9 rounded-xl border border-dashed border-gray-200 flex items-center justify-center text-gray-300 cursor-not-allowed" title="Liez une fiche Laravel (voyages.wp_post_id)"><i class="fas fa-eye text-sm"></i></span>
                <span class="w-9 h-9 rounded-xl border border-dashed border-gray-200 flex items-center justify-center text-gray-300 cursor-not-allowed" title="Liez une fiche Laravel"><i class="fas fa-file-pdf text-sm"></i></span>
            @endif
            @can('reservations.view')
                @if($hasLaravel)
                    <button type="button"
                        class="btn-show-add-reservation inline-flex items-center gap-1.5 bg-gradient-to-b from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 text-white px-3 py-2 rounded-xl text-[11px] sm:text-xs font-bold shadow-md shadow-emerald-500/20 border border-emerald-400/30 transition-all"
                        data-row-code="{{ $row['code'] }}"
                        data-type="{{ $typeKey }}"
                        data-name="{{ $row['name'] }} ({{ $row['code'] }})"
                        data-tour-id="{{ $row['voyage_id'] }}"
                        data-travel-date-id="{{ $row['travel_date_id'] ?? '' }}">
                        @if($typeKey === 'vol')
                            <i class="fas fa-user-plus"></i><span class="hidden sm:inline">Ajouter</span><span class="sm:hidden">+</span>
                        @else
                            <i class="fas fa-plus-circle"></i><span class="hidden sm:inline">Réserver</span><span class="sm:hidden">+</span>
                        @endif
                    </button>
                @else
                    <button type="button" disabled class="inline-flex items-center gap-1.5 bg-gray-200 text-gray-500 px-3 py-2 rounded-xl text-[11px] sm:text-xs font-bold cursor-not-allowed border border-gray-200" title="Créez une ligne dans la table voyages avec wp_post_id = {{ $wpPostId }}">
                        <i class="fas fa-link"></i><span class="hidden sm:inline">Lier d’abord</span>
                    </button>
                @endif
            @endcan
        </div>
    </td>
</tr>
