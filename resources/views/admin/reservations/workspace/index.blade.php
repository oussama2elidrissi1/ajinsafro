@php
    use App\Services\View\AgentPortalLayout;
    $usePortalTailwind = AgentPortalLayout::shouldUse(auth()->user());
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
                        custom: '0 4px 20px rgba(0,0,0,0.08)',
                    },
                }
            }
        };
    </script>
@endif
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
@endpush

@section('content')
<div class="fade-in">
    <div id="page-header-workspace" class="mb-6 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-brand-dark">Réservations</h1>
            <p class="text-sm text-gray-500 mt-1">Catalogue packages, vols et hébergements — même flux que l’espace agent.</p>
        </div>
        <a href="{{ route('admin.reservations.toutes') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-brand-blue hover:text-brand-dark border border-brand-blue/20 rounded-xl px-4 py-2 bg-white shadow-sm">
            <i class="fas fa-list"></i> Toutes les réservations
        </a>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-xl border border-green-200 bg-green-50 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif

    <div id="reservations-main-content" class="space-y-6">
        <div class="bg-white p-3 sm:p-4 rounded-2xl shadow-custom border border-gray-100 flex flex-wrap items-center gap-3">
            <div class="flex-1 min-w-[200px] w-full sm:w-auto relative shrink-0">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="text" id="ws-filter-search" placeholder="Rechercher (nom, code...)" class="w-full pl-10 pr-4 py-2 bg-gray-50 border border-gray-100 rounded-xl text-sm focus:outline-none focus:border-brand-blue focus:bg-white text-brand-dark font-medium placeholder-gray-400">
            </div>
            <select id="ws-filter-type" class="bg-gray-50 border border-gray-100 rounded-xl px-4 py-2 text-sm focus:outline-none focus:border-brand-blue text-brand-dark font-medium cursor-pointer flex-1 sm:flex-none">
                <option value="all">Tous les types</option>
                <option value="package">Packages</option>
                <option value="vol">Vols</option>
                <option value="hebergement">Hébergements</option>
            </select>
            <div class="relative flex items-center bg-gray-50 border border-gray-100 rounded-xl px-4 py-2 focus-within:border-brand-blue focus-within:bg-white transition-colors flex-1 sm:flex-none min-w-[220px]">
                <i class="far fa-calendar-alt text-brand-blue mr-2"></i>
                <input type="text" id="ws-date-range-picker" placeholder="Du… au…" class="bg-transparent border-none outline-none text-brand-dark font-medium text-sm w-full cursor-pointer placeholder-gray-400">
            </div>
            <div class="flex items-center bg-gray-100 rounded-xl p-1 shrink-0 ml-auto">
                <button type="button" id="btn-view-calendar" class="px-3 py-1.5 rounded-lg text-gray-500 hover:text-brand-blue font-bold text-xs flex items-center gap-2 transition-all">
                    <i class="far fa-calendar-alt"></i> <span class="hidden sm:inline">Calendrier</span>
                </button>
                <button type="button" id="btn-view-list" class="px-3 py-1.5 rounded-lg bg-white shadow-sm text-brand-blue font-bold text-xs flex items-center gap-2 transition-all">
                    <i class="fas fa-list"></i> <span class="hidden sm:inline">Liste</span>
                </button>
            </div>
        </div>

        <div id="reservations-list-view" class="bg-white rounded-2xl shadow-custom border border-gray-100 relative pb-6">
            <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-gray-50/30">
                <h3 class="font-bold text-lg text-brand-dark flex items-center gap-2">
                    <i class="fas fa-list text-brand-blue"></i> Prestations
                </h3>
                <div class="text-xs text-gray-500 font-medium"><span id="ws-row-visible-count">{{ $catalogRows->count() }}</span> / {{ $catalogRows->count() }}</div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse whitespace-nowrap min-w-[900px]" id="ws-catalog-table">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100 text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                            <th class="py-4 px-6">ID &amp; type</th>
                            <th class="py-4 px-6">Prestation</th>
                            <th class="py-4 px-6">Départ</th>
                            <th class="py-4 px-6 text-center">Statistiques</th>
                            <th class="py-4 px-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm">
                        @forelse($catalogRows as $row)
                            @php
                                $q = ['voyage_id' => $row['voyage_id']];
                                if (! empty($row['travel_date_id'])) {
                                    $q['travel_date_id'] = $row['travel_date_id'];
                                }
                                $participantsUrl = route('admin.reservations.workspace.prestation.participants', $q);
                                $pdfUrl = route('admin.reservations.workspace.prestation.pdf', $q);
                                $depLabel = $row['departure_date']
                                    ? \Carbon\Carbon::parse($row['departure_date'])->locale('fr')->translatedFormat('d M Y')
                                    : '—';
                                $typeKey = $row['type'];
                                $badgeClass = match ($typeKey) {
                                    'package' => 'bg-orange-50 text-brand-orange border-orange-100',
                                    'vol' => 'bg-blue-50 text-brand-blue border-blue-100',
                                    default => 'bg-yellow-50 text-yellow-600 border-yellow-100',
                                };
                                $typeShort = match ($typeKey) {
                                    'package' => 'Package',
                                    'vol' => 'Vol',
                                    default => 'Hébergement',
                                };
                                $stats = $row['stats'] ?? ['validee' => 0, 'en_cours' => 0, 'annulee' => 0];
                            @endphp
                            <tr class="hover:bg-gray-50 transition-colors ws-catalog-row"
                                data-type="{{ $typeKey }}"
                                data-code="{{ $row['code'] }}"
                                data-name="{{ e($row['name']) }}"
                                data-dep="{{ $row['departure_date'] ? \Carbon\Carbon::parse($row['departure_date'])->format('Y-m-d') : '' }}">
                                <td class="py-4 px-6 align-middle">
                                    <span class="text-xs font-bold text-gray-500 block mb-1">{{ $row['code'] }}</span>
                                    <span class="px-2 py-0.5 {{ $badgeClass }} text-[9px] font-bold rounded uppercase tracking-wide border">{{ $typeShort }}</span>
                                </td>
                                <td class="py-4 px-6 align-middle">
                                    <p class="font-bold text-brand-dark text-sm">{{ $row['name'] }}</p>
                                    @if(!empty($row['subtitle']))
                                        <p class="text-[10px] text-gray-500 mt-0.5">{{ $row['subtitle'] }}</p>
                                    @endif
                                </td>
                                <td class="py-4 px-6 align-middle">
                                    <p class="text-xs font-bold text-gray-700">{{ $depLabel }}</p>
                                </td>
                                <td class="py-4 px-6 text-center align-middle">
                                    <div class="flex items-center justify-center gap-2 text-xs font-bold">
                                        <div class="flex items-center gap-1.5 bg-green-50 text-green-600 px-2 py-1 rounded border border-green-100" title="Confirmées">
                                            <i class="fas fa-check-circle"></i> <span>{{ $stats['validee'] }}</span>
                                        </div>
                                        <div class="flex items-center gap-1.5 bg-yellow-50 text-yellow-600 px-2 py-1 rounded border border-yellow-100" title="En attente">
                                            <i class="fas fa-hourglass-half"></i> <span>{{ $stats['en_cours'] }}</span>
                                        </div>
                                        <div class="flex items-center gap-1.5 bg-red-50 text-red-500 px-2 py-1 rounded border border-red-100" title="Annulées">
                                            <i class="fas fa-times-circle"></i> <span>{{ $stats['annulee'] }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-6 text-right align-middle">
                                    <div class="flex items-center justify-end gap-2 flex-wrap">
                                        <a href="{{ $participantsUrl }}" class="w-8 h-8 rounded-lg bg-gray-50 text-gray-500 hover:bg-brand-blue hover:text-white transition-colors flex items-center justify-center border border-gray-200" title="Participants">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ $pdfUrl }}" class="w-8 h-8 rounded-lg bg-gray-50 text-gray-500 hover:bg-red-500 hover:text-white transition-colors flex items-center justify-center border border-gray-200" title="PDF prestation">
                                            <i class="fas fa-file-pdf"></i>
                                        </a>
                                        @can('reservations.create')
                                            <button type="button"
                                                class="btn-show-add-reservation bg-green-500 hover:bg-green-600 text-white px-3 py-1.5 rounded-lg text-xs font-bold transition-colors shadow-sm flex items-center justify-center gap-1.5"
                                                data-type="{{ $typeKey }}"
                                                data-name="{{ $row['name'] }} ({{ $row['code'] }})"
                                                data-tour-id="{{ $row['voyage_id'] }}"
                                                data-travel-date-id="{{ $row['travel_date_id'] ?? '' }}">
                                                @if($typeKey === 'vol')
                                                    <i class="fas fa-user-plus text-sm"></i> Ajouter
                                                @else
                                                    <i class="fas fa-plus-circle text-sm"></i> Réserver
                                                @endif
                                            </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-12 text-center text-gray-500 text-sm">Aucune prestation à afficher (vérifiez les départs ou vols à venir).</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div id="reservations-calendar-view" class="bg-white p-5 rounded-2xl shadow-custom border border-gray-100 hidden">
            <div id="workspace-calendar" class="w-full min-h-[520px]"></div>
        </div>
    </div>

    <div id="add-reservation-view" class="w-full space-y-6 hidden mt-6">
        <div class="flex items-center gap-4 bg-white p-5 rounded-2xl shadow-custom border border-gray-100">
            <button type="button" id="btn-back-from-add-reservation" class="w-10 h-10 rounded-full bg-gray-50 hover:bg-brand-light text-gray-500 hover:text-brand-blue flex items-center justify-center transition-colors border border-gray-200 shadow-sm" title="Retour">
                <i class="fas fa-arrow-left"></i>
            </button>
            <div>
                <h2 class="text-xl font-bold text-brand-dark flex items-center gap-2">
                    <i class="fas fa-user-plus text-brand-blue"></i> Nouvelle réservation
                </h2>
                <p class="text-xs text-gray-500 mt-1 font-medium">Pour : <span id="add-res-prestation-name" class="text-brand-dark font-bold">—</span></p>
            </div>
        </div>
        <div class="bg-white p-6 rounded-2xl shadow-custom border border-gray-100">
            @include('admin.reservations.workspace.partials.form', ['clients' => $clients])
        </div>
    </div>
</div>

<script type="application/json" id="workspace-calendar-json">@json(
    $catalogRows->map(function ($r) {
        return [
            'title' => ($r['code'] ?? '').' — '.($r['name'] ?? ''),
            'start' => $r['departure_date'] ? \Carbon\Carbon::parse($r['departure_date'])->format('Y-m-d') : null,
            'type' => $r['type'] ?? 'package',
        ];
    })->filter(fn ($e) => ! empty($e['start']))->values()
)</script>
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
    if (calEl && calJson && typeof FullCalendar !== 'undefined') {
        var raw = [];
        try { raw = JSON.parse(calJson.textContent || '[]'); } catch (e) {}
        var colors = { package: '#f37a1f', vol: '#0083c4', hebergement: '#ffb300' };
        var events = raw.map(function (e) {
            return {
                title: e.title,
                start: e.start,
                backgroundColor: colors[e.type] || '#0083c4',
                borderColor: colors[e.type] || '#0083c4',
                textColor: e.type === 'hebergement' ? '#374151' : '#fff'
            };
        });
        calendar = new FullCalendar.Calendar(calEl, {
            initialView: 'dayGridMonth',
            locale: 'fr',
            headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek' },
            buttonText: { today: "Aujourd'hui", month: 'Mois', week: 'Semaine' },
            events: events,
            height: 'auto'
        });
    }

    var btnList = document.getElementById('btn-view-list');
    var btnCal = document.getElementById('btn-view-calendar');
    var listView = document.getElementById('reservations-list-view');
    var calView = document.getElementById('reservations-calendar-view');
    if (btnList && btnCal && listView && calView) {
        btnList.addEventListener('click', function () {
            btnList.classList.add('bg-white', 'shadow-sm', 'text-brand-blue');
            btnList.classList.remove('text-gray-500');
            btnCal.classList.remove('bg-white', 'shadow-sm', 'text-brand-blue');
            btnCal.classList.add('text-gray-500');
            listView.classList.remove('hidden');
            calView.classList.add('hidden');
        });
        btnCal.addEventListener('click', function () {
            btnCal.classList.add('bg-white', 'shadow-sm', 'text-brand-blue');
            btnCal.classList.remove('text-gray-500');
            btnList.classList.remove('bg-white', 'shadow-sm', 'text-brand-blue');
            btnList.classList.add('text-gray-500');
            listView.classList.add('hidden');
            calView.classList.remove('hidden');
            if (calendar) setTimeout(function () { calendar.render(); }, 80);
        });
    }

    var searchEl = document.getElementById('ws-filter-search');
    var typeEl = document.getElementById('ws-filter-type');
    if (searchEl) searchEl.addEventListener('input', applyWsFilters);
    if (typeEl) typeEl.addEventListener('change', applyWsFilters);

    function applyWsFilters() {
        var q = (searchEl && searchEl.value) ? searchEl.value.toLowerCase().trim() : '';
        var t = typeEl ? typeEl.value : 'all';
        var rows = document.querySelectorAll('#ws-catalog-table tbody tr.ws-catalog-row');
        var visible = 0;
        var rangeInput = document.getElementById('ws-date-range-picker');
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
                var blob = (tr.getAttribute('data-name') + ' ' + tr.getAttribute('data-code')).toLowerCase();
                if (blob.indexOf(q) === -1) ok = false;
            }
            if (ok && range) {
                var dep = tr.getAttribute('data-dep');
                if (!dep) ok = false;
                else {
                    var d = new Date(dep + 'T12:00:00');
                    if (d < range.start || d > range.end) ok = false;
                }
            }
            tr.classList.toggle('hidden', !ok);
            if (ok) visible++;
        });
        var c = document.getElementById('ws-row-visible-count');
        if (c) c.textContent = String(visible);
    }
});
</script>
@endpush
