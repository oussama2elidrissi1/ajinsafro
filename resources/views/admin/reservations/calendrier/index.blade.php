@extends('layouts.admin-v6')
@section('title')
    Calendrier des dÃ©parts
@endsection

@php
    $displayMonth = $month !== '' ? $month : now()->format('Y-m');
    $dmParts = explode('-', $displayMonth);
    $initialYear = (int) ($dmParts[0] ?? now()->year);
    $initialMonth0 = (int) ($dmParts[1] ?? now()->month) - 1;
    if ($initialMonth0 < 0 || $initialMonth0 > 11) {
        $initialYear = now()->year;
        $initialMonth0 = now()->month - 1;
    }
    $ajinCalendarConfig = [
        'eventsUrl' => route('admin.reservations.calendrier.events'),
        'detailsUrl' => route('admin.reservations.calendrier.event-details'),
        'reservationDetailsUrl' => route('admin.reservations.calendrier.reservation-details'),
        'createUrl' => route('admin.reservations.create'),
        'initialYear' => $initialYear,
        'initialMonth0' => $initialMonth0,
        'dateFrom' => $dateFrom ?? '',
        'dateTo' => $dateTo ?? '',
    ];
@endphp

@section('content')
    @include('admin.reservations.calendrier.partials.tailwind-safelist')
    <div class="partner-v2">
    <div class="mb-6 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-[#0e3a5a]">Calendrier des dÃ©parts</h1>
            <p class="text-sm text-gray-500 mt-1">DÃ©parts catalogue et rÃ©servations par date.</p>
        </div>
        <nav class="text-sm text-gray-500" aria-label="Fil d'Ariane">
            <a href="{{ route('admin.dashboard') }}" class="hover:text-[#0083c4]">Admin</a>
            <span class="mx-1">/</span>
            <a href="{{ route('admin.reservations.index') }}" class="hover:text-[#0083c4]">RÃ©servations</a>
            <span class="mx-1">/</span>
            <span class="text-gray-700 font-medium">Calendrier</span>
        </nav>
    </div>

    @include('admin.reservations.calendrier.partials.filters')

    <div class="ajin-cal-shell bg-white rounded-2xl shadow-[0_4px_20px_rgba(0,0,0,0.08)] border border-gray-100 overflow-hidden mb-6 w-full min-w-0">
        <div class="p-4 sm:p-5 border-b border-gray-100 flex flex-wrap items-center justify-between gap-3 bg-gray-50/50">
            <h2 class="font-bold text-lg text-[#0e3a5a] flex items-center gap-2">
                <i class="far fa-calendar-alt text-[#0083c4]"></i>
                <span id="ajin-cal-month-title">â€”</span>
            </h2>
            <div class="flex items-center gap-2">
                <button type="button" id="ajin-cal-prev" class="w-9 h-9 rounded-lg border border-gray-200 flex items-center justify-center text-gray-500 hover:bg-[#e6f3fa] hover:text-[#0083c4] hover:border-[#0083c4] transition-colors" title="Mois prÃ©cÃ©dent">
                    <i class="fas fa-chevron-left text-xs"></i>
                </button>
                <button type="button" id="ajin-cal-today" class="px-3 py-1.5 rounded-lg border border-gray-200 text-xs font-bold text-gray-600 hover:bg-gray-50 transition-colors">Aujourd'hui</button>
                <button type="button" id="ajin-cal-next" class="w-9 h-9 rounded-lg border border-gray-200 flex items-center justify-center text-gray-500 hover:bg-[#e6f3fa] hover:text-[#0083c4] hover:border-[#0083c4] transition-colors" title="Mois suivant">
                    <i class="fas fa-chevron-right text-xs"></i>
                </button>
            </div>
        </div>

        <div class="ajin-cal-weekdays grid grid-cols-7 border-b border-gray-100 bg-gray-50 text-[10px] sm:text-[11px] font-bold uppercase tracking-wider text-center">
            <div class="py-3 text-gray-500">Lun</div>
            <div class="py-3 text-gray-500">Mar</div>
            <div class="py-3 text-gray-500">Mer</div>
            <div class="py-3 text-gray-500">Jeu</div>
            <div class="py-3 text-gray-500">Ven</div>
            <div class="py-3 text-[#f37a1f]">Sam</div>
            <div class="py-3 text-[#f37a1f]">Dim</div>
        </div>

        <div class="overflow-x-auto">
            <div id="ajin-cal-root" class="min-w-[720px]">
                <div id="ajin-cal-grid" class="grid grid-cols-7 bg-gray-100 gap-px"></div>
            </div>
        </div>
    </div>

    @include('admin.reservations.calendrier.partials.modals')

    <script>
        window.AJIN_CALENDAR_CONFIG = @json($ajinCalendarConfig);
    </script>
    @vite(['resources/js/admin-reservations-calendar.js'])
    </div>
@endsection

@push('styles')
    @php
        $useAgentShell = \App\Services\View\AgentPortalLayout::shouldUse(auth()->user());
    @endphp
    @unless($useAgentShell)
        @vite(['resources/css/partner-v2.css'])
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    @endunless
    @vite(['resources/css/ajin-calendar-agent.css'])
@endpush

