@php
    use App\Services\View\AgentPortalLayout;
    use Carbon\Carbon;
    $usePortalTailwind = AgentPortalLayout::shouldUse(auth()->user());

    $workspaceCalendarEvents = $catalogRows->map(function ($r) {
        if (empty($r['departure_date']) || empty($r['voyage_id'])) {
            return null;
        }
        $name = (string) ($r['name'] ?? '');
        if (function_exists('mb_strlen') && mb_strlen($name) > 36) {
            $name = mb_substr($name, 0, 34).'…';
        }
        return [
            'title' => ($r['code'] ?? '').' — '.$name,
            'start' => Carbon::parse($r['departure_date'])->format('Y-m-d'),
            'type' => $r['type'] ?? 'package',
            'code' => $r['code'] ?? '',
            'voyage_id' => (int) $r['voyage_id'],
            'travel_date_id' => $r['travel_date_id'] ?? '',
            'prestation_type' => $r['type'] ?? 'package',
            'label' => ($r['name'] ?? '').' ('.($r['code'] ?? '').')',
        ];
    })->filter()->values()->all();
@endphp
@extends('layouts.master-ajinsafro')

@section('title', 'Espace réservation — Catalogue')

@push('styles')
@if(!$usePortalTailwind)
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Poppins', 'sans-serif'] },
                    colors: {
                        brand: {
                            dark: '#0e3a5a',
                            blue: '#0083c4',
                            light: '#e6f3fa',
                            orange: '#f37a1f',
                            yellow: '#ffb300',
                            gray: '#f7f9fc',
                        }
                    },
                    boxShadow: {
                        custom: '0 4px 24px rgba(14,58,90,0.06)',
                        'ws-bar': '0 8px 32px rgba(0,131,196,0.08)',
                    },
                }
            }
        };
    </script>
@endif
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
    .ws-ring-pulse { animation: wsPulse 1.6s ease-out 1; }
    @keyframes wsPulse {
        0% { box-shadow: 0 0 0 0 rgba(0, 131, 196, 0.45); }
        100% { box-shadow: 0 0 0 12px rgba(0, 131, 196, 0); }
    }
    #ws-catalog-table thead th { position: sticky; top: 0; z-index: 1; background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%); }
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .line-clamp-3 {
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    @media (min-width: 640px) {
        .sm\:line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    }
</style>
@endpush

@section('content')
<div class="fade-in max-w-[1680px] mx-auto pb-10">
    {{-- En-tête --}}
    <div class="relative overflow-hidden rounded-2xl border border-gray-200/80 bg-gradient-to-br from-white via-[#f8fcfe] to-[#e6f3fa]/50 shadow-custom mb-8 px-5 sm:px-8 py-6 sm:py-8">
        <div class="absolute top-0 right-0 w-64 h-64 bg-brand-blue/5 rounded-full blur-3xl -translate-y-1/2 translate-x-1/3 pointer-events-none"></div>
        <div class="relative flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
            <div class="min-w-0">
                <p class="text-[10px] sm:text-xs font-bold uppercase tracking-[0.2em] text-brand-blue/80 mb-2">Catalogue réservable</p>
                <h1 class="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-brand-dark tracking-tight">Espace réservation</h1>
                <p class="text-sm text-gray-600 mt-2 max-w-2xl leading-relaxed">
                    Même catalogue que <strong>Circuits / voyages</strong> (tours WordPress <span class="font-mono text-xs bg-white/80 px-1 rounded">st_tours</span>), enrichi par la fiche Laravel quand <span class="font-mono text-xs bg-white/80 px-1 rounded">voyages.wp_post_id</span> correspond. Vols et hébergements en lignes supplémentaires pour les voyages liés.
                </p>
                <div class="flex flex-wrap gap-2 sm:gap-3 mt-5">
                    <span class="inline-flex items-center gap-2 rounded-xl bg-white/90 border border-brand-blue/15 px-3 py-2 text-xs font-semibold text-brand-dark shadow-sm">
                        <i class="fas fa-suitcase-rolling text-brand-blue"></i>
                        {{ $catalogPackageCount ?? $catalogRows->where('type', 'package')->count() }} voyage(s)
                    </span>
                    <span class="inline-flex items-center gap-2 rounded-xl bg-white/90 border border-gray-200/80 px-3 py-2 text-xs font-semibold text-gray-700 shadow-sm">
                        <i class="fas fa-layer-group text-brand-orange"></i>
                        {{ $catalogTotalCount ?? $catalogRows->count() }} ligne(s) prestation
                    </span>
                </div>
            </div>
            <div class="flex flex-col sm:flex-row gap-3 shrink-0">
                <a href="{{ route('admin.circuits.voyages.index') }}" class="inline-flex items-center justify-center gap-2 text-sm font-semibold text-gray-700 bg-white border border-gray-200 rounded-xl px-5 py-3 shadow-sm hover:border-brand-blue/40 hover:text-brand-blue transition-all">
                    <i class="fas fa-route"></i> Circuits / voyages
                </a>
                <a href="{{ route('admin.reservations.toutes') }}" class="inline-flex items-center justify-center gap-2 text-sm font-bold text-white bg-brand-dark hover:bg-brand-blue rounded-xl px-5 py-3 shadow-ws-bar transition-colors">
                    <i class="fas fa-list-ul"></i> Toutes les réservations
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 text-emerald-900 px-4 py-3 text-sm font-medium shadow-sm">{{ session('success') }}</div>
    @endif

    @if(config('app.debug') && isset($catalogMeta))
        <div class="mb-6 rounded-2xl border border-amber-200/90 bg-amber-50/95 px-4 py-3 text-xs text-amber-950 shadow-sm">
            <p class="font-bold uppercase tracking-wide text-amber-800 mb-1">Debug catalogue (APP_DEBUG)</p>
            @if(!empty($catalogMeta['wp_connection_failed']))
                <p class="text-red-800 font-semibold">Connexion WordPress indisponible — aucune ligne catalogue.</p>
            @else
                <p class="text-[11px] text-amber-900/90 mb-2 leading-relaxed">
                    <strong>wp_tours</strong> = nombre de tours WordPress (comme Circuits / voyages). <strong>total_rows</strong> = packages + vols + hébergements dans le tableau.
                </p>
                <p class="font-mono leading-relaxed">
                    wp_tours={{ (int) ($catalogMeta['wp_tour_count'] ?? 0) }},
                    laravel_matched={{ (int) ($catalogMeta['laravel_voyage_matched'] ?? 0) }},
                    packages={{ (int) ($catalogMeta['package_rows'] ?? 0) }},
                    vols={{ (int) ($catalogMeta['vol_rows'] ?? 0) }},
                    hébergements={{ (int) ($catalogMeta['hebergement_rows'] ?? 0) }},
                    total_rows={{ (int) ($catalogMeta['total_rows'] ?? ($catalogTotalCount ?? $catalogRows->count())) }}
                </p>
                @php
                    $p = (int) ($catalogMeta['package_rows'] ?? 0);
                    $v = (int) ($catalogMeta['vol_rows'] ?? 0);
                    $h = (int) ($catalogMeta['hebergement_rows'] ?? 0);
                    $chk = $p + $v + $h;
                    $tot = (int) ($catalogMeta['total_rows'] ?? $catalogRows->count());
                @endphp
                @if($chk === $tot && $p > 0)
                    <p class="text-[11px] text-emerald-800 mt-2 font-medium">Vérification : {{ $p }} + {{ $v }} + {{ $h }} = {{ $tot }} (cohérent).</p>
                @endif
                @if(!empty($catalogMeta['wp_tour_ids']))
                    <p class="text-[10px] font-mono mt-2 break-all text-amber-950/90"><span class="font-sans font-bold text-amber-800">IDs WP (ordre Circuits / voyages, desc ID)</span> : {{ implode(', ', $catalogMeta['wp_tour_ids']) }}</p>
                @endif
                @if(!empty($catalogMeta['laravel_wp_post_ids_matched']))
                    <p class="text-[10px] font-mono mt-1 break-all text-amber-950/90"><span class="font-sans font-bold text-amber-800">wp_post_id Laravel liés</span> : {{ implode(', ', $catalogMeta['laravel_wp_post_ids_matched']) }}</p>
                @endif
                @if(!empty($catalogMeta['wp_tour_ids_without_laravel']))
                    <p class="text-[10px] font-mono mt-1 break-all text-amber-900"><span class="font-sans font-bold">Tours WP sans fiche Laravel</span> : {{ implode(', ', $catalogMeta['wp_tour_ids_without_laravel']) }}</p>
                @endif
                @if(!empty($catalogMeta['laravel_duplicates_wp_post_id']))
                    <p class="text-[10px] mt-2 text-red-800 font-semibold">Doublons voyages.wp_post_id (plusieurs lignes Laravel) :</p>
                    <pre class="text-[10px] font-mono mt-1 overflow-x-auto bg-white/60 rounded-lg p-2 border border-red-100">{{ json_encode($catalogMeta['laravel_duplicates_wp_post_id'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                @endif
                @if(!empty($catalogMeta['package_price_debug']))
                    <p class="text-[10px] font-bold text-amber-900 mt-3 mb-1">Prix packages (meta WP <code class="font-mono">adult_price</code> = colonne Prix Adulte Circuits / voyages)</p>
                    <div class="overflow-x-auto max-h-48 overflow-y-auto border border-amber-200/80 rounded-lg bg-white/70">
                        <table class="w-full text-[9px] font-mono">
                            <thead class="bg-amber-100/80 text-left">
                                <tr>
                                    <th class="p-1.5">WP ID</th>
                                    <th class="p-1.5">meta brut</th>
                                    <th class="p-1.5">parsé</th>
                                    <th class="p-1.5">Laravel price_from</th>
                                    <th class="p-1.5">Affiché</th>
                                    <th class="p-1.5">Source</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($catalogMeta['package_price_debug'] as $d)
                                    <tr class="border-t border-amber-100">
                                        <td class="p-1.5 align-top">{{ $d['wp_post_id'] ?? '' }}</td>
                                        <td class="p-1.5 align-top break-all max-w-[100px]">{{ is_scalar($d['adult_price_meta_raw'] ?? null) ? $d['adult_price_meta_raw'] : json_encode($d['adult_price_meta_raw']) }}</td>
                                        <td class="p-1.5 align-top">{{ $d['parsed_wp_adult'] ?? '—' }}</td>
                                        <td class="p-1.5 align-top">{{ $d['laravel_price_from'] ?? '—' }}</td>
                                        <td class="p-1.5 align-top font-bold">{{ $d['price_label_final'] ?? '—' }}</td>
                                        <td class="p-1.5 align-top">{{ $d['price_source'] ?? '' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            @endif
        </div>
    @endif

    <div id="reservations-main-content" class="space-y-6">
        {{-- Filtres --}}
        <div class="bg-white p-4 sm:p-5 rounded-2xl shadow-custom border border-gray-100/90 space-y-3">
            <div class="flex flex-col xl:flex-row xl:flex-wrap xl:items-center gap-4">
                <div class="flex-1 min-w-[220px] relative">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                    <input type="text" id="ws-filter-search" placeholder="Rechercher par nom, code, sous-titre…" autocomplete="off"
                        class="w-full pl-11 pr-4 py-3 bg-gray-50/90 border border-gray-100 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-blue/25 focus:border-brand-blue focus:bg-white text-brand-dark font-medium placeholder-gray-400 transition-all">
                </div>
                <div class="flex flex-col sm:flex-row gap-3 flex-1 min-w-0">
                    <select id="ws-filter-type" class="sm:min-w-[160px] bg-gray-50/90 border border-gray-100 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-blue/25 focus:border-brand-blue text-brand-dark font-semibold cursor-pointer">
                        <option value="all">Tous les types</option>
                        <option value="package">Packages</option>
                        <option value="vol">Vols</option>
                        <option value="hebergement">Hébergements</option>
                    </select>
                    <div class="relative flex items-center flex-1 min-w-[200px] bg-gray-50/90 border border-gray-100 rounded-xl px-4 py-2.5 focus-within:ring-2 focus-within:ring-brand-blue/25 focus-within:border-brand-blue focus-within:bg-white transition-all">
                        <i class="far fa-calendar-alt text-brand-blue mr-3 shrink-0"></i>
                        <input type="text" id="ws-date-range-picker" readonly placeholder="Plage de dates de départ…" class="bg-transparent border-none outline-none text-brand-dark font-medium text-sm w-full cursor-pointer placeholder-gray-400">
                    </div>
                    <button type="button" id="ws-filters-reset" class="shrink-0 px-4 py-3 rounded-xl text-sm font-bold text-brand-blue border border-brand-blue/30 bg-white hover:bg-brand-light/80 transition-colors" title="Effacer recherche, type et dates">
                        Réinitialiser
                    </button>
                </div>
                <div class="flex items-center justify-between sm:justify-end gap-3 xl:ml-auto">
                    <span class="text-xs text-gray-500 font-medium whitespace-nowrap"><span id="ws-row-visible-count">{{ $catalogRows->count() }}</span> / {{ $catalogRows->count() }} affichée(s)</span>
                    <div class="inline-flex bg-gray-100/90 rounded-xl p-1 border border-gray-100">
                        <button type="button" id="btn-view-calendar" class="px-3 sm:px-4 py-2 rounded-lg text-gray-500 hover:text-brand-blue font-bold text-xs flex items-center gap-2 transition-all">
                            <i class="far fa-calendar-alt"></i><span class="hidden sm:inline">Calendrier</span>
                        </button>
                        <button type="button" id="btn-view-list" class="px-3 sm:px-4 py-2 rounded-lg bg-white shadow-md text-brand-blue font-bold text-xs flex items-center gap-2 border border-gray-100/80">
                            <i class="fas fa-list"></i><span class="hidden sm:inline">Liste</span>
                        </button>
                    </div>
                </div>
            </div>
            <p class="text-[11px] text-gray-500">La plage de dates filtre sur les lignes qui ont une <strong>date de départ</strong> ; les voyages sans date (—) restent affichés. Utilisez <strong>Réinitialiser</strong> si le tableau semble incomplet.</p>
        </div>

        {{-- Tableau --}}
        <div id="reservations-list-view" class="bg-white rounded-2xl shadow-custom border border-gray-100/90 overflow-hidden">
            <div class="px-5 sm:px-6 py-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 bg-gradient-to-r from-gray-50/80 to-white">
                <h2 class="font-bold text-lg text-brand-dark flex items-center gap-2">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-light text-brand-blue"><i class="fas fa-th-list text-sm"></i></span>
                    Prestations réservables
                </h2>
                <p class="text-xs text-gray-500">Statistiques = dossiers sur le <strong class="text-brand-dark">voyage</strong> (toutes dates confondues).</p>
            </div>
            <div class="overflow-x-auto max-h-[min(72vh,920px)] overflow-y-auto -mx-px">
                <table class="w-full text-left border-collapse min-w-[1040px]" id="ws-catalog-table">
                    <thead>
                        <tr class="text-[10px] sm:text-[11px] font-bold text-gray-500 uppercase tracking-wider border-b border-gray-200">
                            <th class="py-3.5 px-4 sm:px-5 w-[120px]">Réf. &amp; type</th>
                            <th class="py-3.5 px-4 sm:px-5 min-w-[200px]">Prestation</th>
                            <th class="py-3.5 px-4 sm:px-5 w-[130px]">Départ</th>
                            <th class="py-3.5 px-3 text-center w-[168px]">Statistiques</th>
                            <th class="py-3.5 px-3 sm:px-4 text-right min-w-[280px]">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm">
                        @forelse($catalogRows as $row)
                            @include('admin.reservations.workspace.partials.catalog-row', ['row' => $row])
                        @empty
                            <tr>
                                <td colspan="5" class="py-16 px-6">
                                    <div class="max-w-md mx-auto text-center">
                                        <div class="inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-gray-100 text-gray-400 mb-4 text-2xl">
                                            <i class="fas fa-inbox"></i>
                                        </div>
                                        <p class="text-brand-dark font-bold text-lg mb-2">Aucun voyage dans le catalogue</p>
                                        <p class="text-gray-500 text-sm mb-6">Créez ou synchronisez des fiches dans la table Laravel <strong>voyages</strong>, ou ouvrez les circuits WordPress pour les lier.</p>
                                        <a href="{{ route('admin.circuits.voyages.index') }}" class="inline-flex items-center gap-2 rounded-xl bg-brand-blue text-white font-bold text-sm px-5 py-3 hover:bg-brand-dark transition-colors">
                                            <i class="fas fa-plus-circle"></i> Gérer les voyages
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Calendrier --}}
        <div id="reservations-calendar-view" class="bg-white p-4 sm:p-6 rounded-2xl shadow-custom border border-gray-100/90 hidden">
            <p class="text-sm text-gray-600 mb-4">Cliquez sur un événement pour ouvrir le formulaire <strong>Réserver</strong> de la ligne correspondante (retour automatique en vue liste).</p>
            <div id="workspace-calendar" class="w-full min-h-[540px] fc-workspace"></div>
        </div>
    </div>

    {{-- Formulaire réservation --}}
    <div id="add-reservation-view" class="w-full space-y-6 hidden mt-10">
        <div class="flex items-center gap-4 bg-white p-5 sm:p-6 rounded-2xl shadow-custom border border-gray-100">
            <button type="button" id="btn-back-from-add-reservation" class="w-11 h-11 rounded-full bg-gray-50 hover:bg-brand-light text-gray-500 hover:text-brand-blue flex items-center justify-center transition-colors border border-gray-200 shadow-sm shrink-0" title="Retour au catalogue">
                <i class="fas fa-arrow-left"></i>
            </button>
            <div>
                <h2 class="text-xl font-bold text-brand-dark flex items-center gap-2">
                    <i class="fas fa-user-plus text-brand-blue"></i> Nouvelle réservation
                </h2>
                <p class="text-xs text-gray-500 mt-1 font-medium">Prestation : <span id="add-res-prestation-name" class="text-brand-dark font-bold">—</span></p>
            </div>
        </div>
        <div class="bg-white p-5 sm:p-8 rounded-2xl shadow-custom border border-gray-100">
            @include('admin.reservations.workspace.partials.form', ['clients' => $clients])
        </div>
    </div>
</div>

<script type="application/json" id="workspace-calendar-json">{!! json_encode($workspaceCalendarEvents, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/fr.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
<script src="{{ asset('js/reservation-workspace.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof flatpickr !== 'undefined') {
        flatpickr('#ws-date-range-picker', {
            mode: 'range',
            dateFormat: 'Y-m-d',
            locale: 'fr',
            onChange: function () { applyWsFilters(); }
        });
    }

    var calEl = document.getElementById('workspace-calendar');
    var calJson = document.getElementById('workspace-calendar-json');
    var calendar = null;
    var btnList = document.getElementById('btn-view-list');
    var btnCal = document.getElementById('btn-view-calendar');
    var listView = document.getElementById('reservations-list-view');
    var calView = document.getElementById('reservations-calendar-view');

    if (calEl && calJson && typeof FullCalendar !== 'undefined') {
        var raw = [];
        try { raw = JSON.parse(calJson.textContent || '[]'); } catch (e) {}
        var colors = { package: '#f37a1f', vol: '#0083c4', hebergement: '#d97706' };
        var events = raw.map(function (e) {
            return {
                title: e.title,
                start: e.start,
                backgroundColor: colors[e.type] || '#0083c4',
                borderColor: colors[e.type] || '#0083c4',
                textColor: e.type === 'hebergement' ? '#1f2937' : '#fff',
                extendedProps: {
                    code: e.code,
                    voyage_id: e.voyage_id,
                    travel_date_id: e.travel_date_id,
                    prestation_type: e.prestation_type,
                    label: e.label
                }
            };
        });
        calendar = new FullCalendar.Calendar(calEl, {
            initialView: 'dayGridMonth',
            locale: 'fr',
            headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek' },
            buttonText: { today: "Aujourd'hui", month: 'Mois', week: 'Semaine' },
            events: events,
            height: 'auto',
            eventClick: function (info) {
                info.jsEvent.preventDefault();
                var p = info.event.extendedProps || {};
                var code = String(p.code || '');
                if (btnList) btnList.click();
                var safe = code.replace(/\\/g, '\\\\').replace(/"/g, '\\"');
                var row = code ? document.querySelector('tr.ws-catalog-row[data-row-code="' + safe + '"]') : null;
                if (row) {
                    row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    row.classList.add('ws-ring-pulse');
                    setTimeout(function () { row.classList.remove('ws-ring-pulse'); }, 1800);
                }
                var btn = code ? document.querySelector('.btn-show-add-reservation[data-row-code="' + safe + '"]') : null;
                if (btn) btn.click();
            }
        });
    }

    if (btnList && btnCal && listView && calView) {
        btnList.addEventListener('click', function () {
            btnList.classList.add('bg-white', 'shadow-md', 'text-brand-blue', 'border', 'border-gray-100/80');
            btnList.classList.remove('text-gray-500');
            btnCal.classList.remove('bg-white', 'shadow-md', 'text-brand-blue', 'border', 'border-gray-100/80');
            btnCal.classList.add('text-gray-500');
            listView.classList.remove('hidden');
            calView.classList.add('hidden');
        });
        btnCal.addEventListener('click', function () {
            btnCal.classList.add('bg-white', 'shadow-md', 'text-brand-blue', 'border', 'border-gray-100/80');
            btnCal.classList.remove('text-gray-500');
            btnList.classList.remove('bg-white', 'shadow-md', 'text-brand-blue', 'border', 'border-gray-100/80');
            btnList.classList.add('text-gray-500');
            listView.classList.add('hidden');
            calView.classList.remove('hidden');
            if (calendar) setTimeout(function () { calendar.render(); }, 80);
        });
    }

    var searchEl = document.getElementById('ws-filter-search');
    var typeEl = document.getElementById('ws-filter-type');
    var rangeInput = document.getElementById('ws-date-range-picker');
    var resetBtn = document.getElementById('ws-filters-reset');
    if (searchEl) searchEl.addEventListener('input', applyWsFilters);
    if (typeEl) typeEl.addEventListener('change', applyWsFilters);
    if (resetBtn) {
        resetBtn.addEventListener('click', function () {
            if (searchEl) searchEl.value = '';
            if (typeEl) typeEl.value = 'all';
            if (rangeInput && rangeInput._flatpickr) rangeInput._flatpickr.clear();
            applyWsFilters();
        });
    }

    window.applyWsFilters = function applyWsFilters() {
        var q = (searchEl && searchEl.value) ? searchEl.value.toLowerCase().trim() : '';
        var t = typeEl ? typeEl.value : 'all';
        var rows = document.querySelectorAll('#ws-catalog-table tbody tr.ws-catalog-row');
        var visible = 0;
        var range = null;
        if (rangeInput && rangeInput._flatpickr && rangeInput._flatpickr.selectedDates.length === 2) {
            var a = rangeInput._flatpickr.selectedDates[0];
            var b = rangeInput._flatpickr.selectedDates[1];
            range = { start: a <= b ? a : b, end: a <= b ? b : a };
        }
        rows.forEach(function (tr) {
            var ok = true;
            if (t !== 'all' && tr.getAttribute('data-type') !== t) ok = false;
            if (ok && q) {
                var blob = (tr.getAttribute('data-search') || '')
                    + ' ' + (tr.getAttribute('data-name') || '')
                    + ' ' + (tr.getAttribute('data-code') || '');
                if (blob.toLowerCase().indexOf(q) === -1) ok = false;
            }
            if (ok && range) {
                var dep = tr.getAttribute('data-dep');
                if (dep) {
                    var d = new Date(dep + 'T12:00:00');
                    if (d < range.start || d > range.end) ok = false;
                }
            }
            tr.classList.toggle('hidden', !ok);
            if (ok) visible++;
        });
        var c = document.getElementById('ws-row-visible-count');
        if (c) c.textContent = String(visible);
    };

    applyWsFilters();
});
</script>
@endpush
