@extends('layouts.master-ajinsafro')

@section('title', 'Tableau de bord')

@push('css')
    <link href="{{ URL::asset('css/agent-dashboard.css') }}" rel="stylesheet" type="text/css" />
@endpush

@section('content')
@php
    use App\Models\Reservation;
    use Illuminate\Support\Facades\Route;

    $user = auth()->user();
    $displayName = $user?->name ?: 'Agent';
    $agencyLabel = $user?->branch?->name ?: 'Ajinsafro Tanger';

    $catalogueVoyageUrl = Route::has('admin.reservations.workspace')
        ? route('admin.reservations.workspace')
        : url('/admin/reservations/workspace');
@endphp

<div class="mb-8 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
    <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-[#0e3a5a]">Tableau de bord</h1>
        <p class="text-sm text-gray-500 mt-1">Bienvenue, {{ $displayName }} — {{ $agencyLabel }}.</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ $catalogueVoyageUrl }}" class="btn btn-primary">
            <i class="bx bx-map-alt align-middle" aria-hidden="true"></i>
            <span>Catalogue de voyage</span>
        </a>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 lg:gap-6">
    <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-custom border border-gray-100 flex items-center gap-4">
        <div class="w-14 h-14 rounded-full bg-blue-50 flex items-center justify-center shrink-0">
            <i class="bx bx-briefcase-alt-2 text-[#0083c4] text-2xl"></i>
        </div>
        <div>
            <p class="text-[10px] lg:text-[11px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Réservations</p>
            <h4 class="text-2xl lg:text-3xl font-black text-[#0e3a5a] leading-none">{{ number_format((int) ($stats['reservations_total'] ?? 0), 0, ',', ' ') }}</h4>
        </div>
    </div>
    <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-custom border border-gray-100 flex items-center gap-4">
        <div class="w-14 h-14 rounded-full bg-green-50 flex items-center justify-center shrink-0">
            <i class="bx bx-check-shield text-green-600 text-2xl"></i>
        </div>
        <div>
            <p class="text-[10px] lg:text-[11px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Confirmées</p>
            <h4 class="text-2xl lg:text-3xl font-black text-[#0e3a5a] leading-none">{{ number_format((int) ($stats['reservations_validees'] ?? 0), 0, ',', ' ') }}</h4>
        </div>
    </div>
    <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-custom border border-gray-100 flex items-center gap-4">
        <div class="w-14 h-14 rounded-full bg-purple-50 flex items-center justify-center shrink-0">
            <i class="bx bx-time-five text-purple-600 text-2xl"></i>
        </div>
        <div>
            <p class="text-[10px] lg:text-[11px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">En attente</p>
            <h4 class="text-2xl lg:text-3xl font-black text-[#0e3a5a] leading-none">{{ number_format((int) ($stats['reservations_en_cours'] ?? 0), 0, ',', ' ') }}</h4>
        </div>
    </div>
    <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-custom border border-gray-100 flex items-center gap-4">
        <div class="w-14 h-14 rounded-full bg-orange-50 flex items-center justify-center shrink-0">
            <i class="bx bx-wallet text-[#f37a1f] text-2xl"></i>
        </div>
        <div>
            <p class="text-[10px] lg:text-[11px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Revenus</p>
            <h4 class="text-2xl lg:text-3xl font-black text-[#0e3a5a] leading-none">{{ number_format((float) ($stats['revenue_generated'] ?? 0), 0, ',', ' ') }} DH</h4>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
    <div class="bg-white rounded-2xl shadow-custom border border-gray-100 flex flex-col overflow-hidden col-span-1">
        <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
            <h3 class="font-bold text-[#0e3a5a]">Aujourd'hui</h3>
        </div>
        <div class="p-5 space-y-3">
            <div class="flex items-center justify-between bg-gray-50 border border-gray-100 rounded-xl px-4 py-3">
                <span class="text-sm font-semibold text-[#0e3a5a]">Réservations du jour</span>
                <span class="text-[11px] font-bold text-[#0083c4]">{{ number_format((int) ($todayStats['reservations_today'] ?? 0), 0, ',', ' ') }}</span>
            </div>
            <div class="flex items-center justify-between bg-gray-50 border border-gray-100 rounded-xl px-4 py-3">
                <span class="text-sm font-semibold text-[#0e3a5a]">En attente aujourd'hui</span>
                <span class="text-[11px] font-bold text-[#0083c4]">{{ number_format((int) ($todayStats['pending_today'] ?? 0), 0, ',', ' ') }}</span>
            </div>

            @if(!empty($todayStats['notifications']))
                <div class="pt-2 space-y-2">
                    @foreach(($todayStats['notifications'] ?? []) as $notification)
                        <div class="bg-white border border-gray-100 rounded-xl px-4 py-3 text-sm text-gray-600">{{ $notification }}</div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-custom border border-gray-100 flex flex-col overflow-hidden col-span-2">
        <div class="p-5 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-gray-50/50">
            <div>
                <h3 class="font-bold text-[#0e3a5a]">Mes dernières réservations</h3>
                <p class="text-sm text-gray-500 mt-1">Une vue rapide sur les dossiers les plus récents.</p>
            </div>
            <form method="GET" action="{{ route('agent.dashboard') }}" class="flex items-center gap-2">
                <select name="scope" id="scope" class="form-select" {{ $isManager ? '' : 'disabled' }}>
                    <option value="mine" {{ ($scope ?? 'mine') === 'mine' ? 'selected' : '' }}>Mes réservations</option>
                    @if($isManager)
                        <option value="team" {{ ($scope ?? 'mine') === 'team' ? 'selected' : '' }}>Mon équipe</option>
                    @endif
                </select>
                @unless($isManager)
                    <input type="hidden" name="scope" value="mine">
                @endunless
                <button type="submit" class="btn btn-outline-primary">Filtrer</button>
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse whitespace-nowrap">
                <thead>
                <tr class="bg-gray-50 border-b border-gray-100 text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                    <th class="py-4 px-6">Client</th>
                    <th class="py-4 px-6">Voyage</th>
                    <th class="py-4 px-6">Date</th>
                    <th class="py-4 px-6">Statut</th>
                    <th class="py-4 px-6 text-right">Actions</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                @forelse($recentReservations as $reservation)
                    @php
                        $clientName = trim(($reservation->client_first_name ?? '') . ' ' . ($reservation->client_last_name ?? ''));
                        $status = $reservation->status;
                        $badge = $status === Reservation::STATUS_VALIDEE ? 'bg-green-50 text-green-700 border-green-200' : ($status === Reservation::STATUS_ANNULEE ? 'bg-red-50 text-red-700 border-red-200' : 'bg-yellow-50 text-yellow-700 border-yellow-200');
                        $detailUrl = Route::has('admin.reservations.show') ? route('admin.reservations.show', $reservation) : '#';
                        $displayDate = optional($reservation->travelDate?->date)->format('d/m/Y') ?: optional($reservation->created_at)->format('d/m/Y');
                    @endphp
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="py-4 px-6 font-semibold text-gray-800">{{ $clientName !== '' ? $clientName : 'Client non renseigné' }}</td>
                        <td class="py-4 px-6 text-gray-600">{{ $reservation->tour?->name ?: 'Voyage non renseigné' }}</td>
                        <td class="py-4 px-6 text-gray-500">{{ $displayDate }}</td>
                        <td class="py-4 px-6">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold border {{ $badge }}">{{ $status }}</span>
                        </td>
                        <td class="py-4 px-6 text-right">
                            <a href="{{ $detailUrl }}" class="text-[#0083c4] font-bold text-xs hover:underline">Ouvrir</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-10 px-6 text-center text-gray-500">
                            Aucune réservation récente. <a href="{{ $catalogueVoyageUrl }}" class="text-[#0083c4] font-bold hover:underline">Voir le catalogue</a>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
