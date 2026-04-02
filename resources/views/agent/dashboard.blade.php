@extends('layouts.master-ajinsafro')

@section('title', 'Tableau de bord')

@push('css')
    <link href="{{ URL::asset('css/agent-dashboard.css') }}" rel="stylesheet" type="text/css" />
@endpush

@section('content')
@php
    use Illuminate\Support\Facades\Route;

    $user = auth()->user();
    $displayName = $user?->name;

    $reservationsListUrl = null;
    if (Route::has('admin.reservations.index') && $user->can('reservations.all.view')) {
        $reservationsListUrl = route('admin.reservations.index');
    } elseif (Route::has('admin.reservations.index') && $user->can('reservations.pending.view')) {
        $reservationsListUrl = route('admin.reservations.index', ['status' => 'EN_COURS']);
    } elseif (Route::has('admin.reservations.index') && $user->can('reservations.confirmed.view')) {
        $reservationsListUrl = route('admin.reservations.index', ['status' => 'VALIDEE']);
    }

    $canOpenReservation = Route::has('admin.reservations.show') && $user->can('reservations.view');
@endphp

@include('agent.partials.dashboard-header', [
    'user' => $user,
    'isManager' => $isManager ?? false,
    'stats' => $stats ?? [],
    'quickRange' => $quickRange ?? null,
])

@if($isManager ?? false)
    <div class="mb-4 rounded-xl border border-[#0083c4]/20 bg-[#e6f3fa]/40 px-4 py-3 text-sm text-[#0e3a5a]">
        <i class="fas fa-info-circle text-[#0083c4] mr-1"></i>
        Les listes et le calendrier incluent <strong>vos dossiers</strong> et ceux des commerciaux dont vous êtes le manager (filtre agence appliqué).
    </div>
@endif

@include('agent.partials.dashboard-kpis', ['stats' => $stats, 'subtitle' => ($isManager ?? false) ? 'Vue consolidée (vous + équipe)' : null])

@include('agent.partials.dashboard-filters', [
    'filterAgentOptions' => $filterAgentOptions ?? collect(),
    'filterAgentId' => $filterAgentId ?? null,
    'filterReservationStatus' => $filterReservationStatus ?? null,
    'filterClientAgentId' => $filterClientAgentId ?? null,
])

@if($isManager ?? false)
    @include('agent.partials.dashboard-manager-panels', [
        'statsPersonal' => $statsPersonal,
        'statsTeamOnly' => $statsTeamOnly,
        'teamAgentStats' => $teamAgentStats,
        'directReports' => $directReports,
    ])
@endif

@include('agent.partials.dashboard-tables', [
    'recentReservations' => $recentReservations,
    'recentClients' => $recentClients,
    'reservationsListUrl' => $reservationsListUrl,
    'canOpenReservation' => $canOpenReservation,
    'isManager' => $isManager ?? false,
    'quickRange' => $quickRange ?? null,
])

@include('agent.partials.dashboard-performance', ['topOffers' => $topOffers ?? ['labels' => [], 'bookings' => [], 'revenue' => []]])

@include('agent.partials.dashboard-calendar', ['calendarEvents' => $calendarEvents])

@include('agent.partials.dashboard-activity', ['recentActivityReservations' => $recentActivityReservations])
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" crossorigin="anonymous"></script>
    <script src="{{ URL::asset('js/agent-dashboard.js') }}"></script>
@endpush
