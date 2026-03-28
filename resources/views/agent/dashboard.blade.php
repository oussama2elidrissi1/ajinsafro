@extends('layouts.master-ajinsafro')

@section('title', 'Tableau de bord')

@section('content')
@php
    use Illuminate\Support\Facades\Route;

    $user = auth()->user();
    $displayName = $user?->name;

    $reservationsListUrl = null;
    if (Route::has('admin.reservations.toutes') && $user->can('reservations.all.view')) {
        $reservationsListUrl = route('admin.reservations.toutes');
    } elseif (Route::has('admin.reservations.en-attente') && $user->can('reservations.pending.view')) {
        $reservationsListUrl = route('admin.reservations.en-attente');
    } elseif (Route::has('admin.reservations.confirmees') && $user->can('reservations.confirmed.view')) {
        $reservationsListUrl = route('admin.reservations.confirmees');
    }

    $canOpenReservation = Route::has('admin.reservations.show') && $user->can('reservations.view');
@endphp

<div class="mb-8 flex flex-col lg:flex-row lg:items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-[#0e3a5a]">Tableau de bord</h1>
        @if($isManager ?? false)
            <p class="text-sm text-gray-500 mt-1">
                Espace manager — vue consolidée équipe et <span class="font-semibold text-[#0083c4]">{{ $displayName }}</span>
            </p>
        @else
            <p class="text-sm text-gray-500 mt-1">Bienvenue dans votre espace agent, <span class="font-semibold text-[#0083c4]">{{ $displayName }}</span>.</p>
        @endif
    </div>
    <div class="flex flex-wrap gap-2">
        @if(Route::has('admin.reservations.create') && $user->can('reservations.create'))
            <a href="{{ route('admin.reservations.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#0083c4] text-white text-sm font-bold shadow-sm hover:opacity-95 transition-opacity">
                <i class="fas fa-plus-circle"></i>
                Nouvelle réservation
            </a>
        @endif
        @if(Route::has('admin.customers.clients.create') && $user->can('customers.clients.view'))
            <a href="{{ route('admin.customers.clients.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border-2 border-[#0083c4] text-[#0083c4] text-sm font-bold bg-white hover:bg-[#e6f3fa]/40 transition-colors">
                <i class="fas fa-user-plus"></i>
                Nouveau client
            </a>
        @endif
    </div>
</div>

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
])

@include('agent.partials.dashboard-calendar', ['calendarEvents' => $calendarEvents])

@include('agent.partials.dashboard-activity', ['recentActivityReservations' => $recentActivityReservations])
@endsection
