@props([
    'filterAgentOptions',
    'filterAgentId' => null,
    'filterReservationStatus' => null,
    'filterClientAgentId' => null,
])

@php
    $statuses = [
        \App\Models\Reservation::STATUS_EN_COURS => 'En cours',
        \App\Models\Reservation::STATUS_VALIDEE => 'Validée',
        \App\Models\Reservation::STATUS_ANNULEE => 'Annulée',
    ];
@endphp

<div class="bg-white rounded-2xl shadow-custom border border-gray-100 p-4 sm:p-5 mt-6">
    <form method="get" action="{{ route('agent.dashboard') }}" class="flex flex-col lg:flex-row flex-wrap gap-3 lg:items-end">
        <div class="flex-1 min-w-[200px]">
            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Agent / commercial</label>
            <select name="agent_id" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm text-[#0e3a5a] font-medium focus:outline-none focus:border-[#0083c4]">
                <option value="">Tous (périmètre visible)</option>
                @foreach($filterAgentOptions as $opt)
                    <option value="{{ $opt->id }}" @selected((int) $filterAgentId === (int) $opt->id)>{{ $opt->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-full sm:w-auto min-w-[160px]">
            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Statut réservation</label>
            <select name="res_status" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm text-[#0e3a5a] font-medium focus:outline-none focus:border-[#0083c4]">
                <option value="">Tous</option>
                @foreach($statuses as $val => $label)
                    <option value="{{ $val }}" @selected($filterReservationStatus === $val)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex-1 min-w-[200px]">
            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">Clients par agent</label>
            <select name="client_agent_id" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm text-[#0e3a5a] font-medium focus:outline-none focus:border-[#0083c4]">
                <option value="">Tous les clients visibles</option>
                @foreach($filterAgentOptions as $opt)
                    <option value="{{ $opt->id }}" @selected((int) $filterClientAgentId === (int) $opt->id)>{{ $opt->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="px-5 py-2.5 rounded-xl bg-[#0083c4] text-white text-sm font-bold shadow-sm hover:opacity-95 transition-opacity">
                <i class="fas fa-filter mr-1"></i> Filtrer
            </button>
            <a href="{{ route('agent.dashboard') }}" class="px-4 py-2.5 rounded-xl border border-gray-200 text-gray-600 text-sm font-semibold hover:bg-gray-50 transition-colors">Réinitialiser</a>
        </div>
    </form>
    <p class="text-[11px] text-gray-500 mt-3 mb-0">
        Les filtres s’appliquent aux tableaux « Dernières réservations », « Derniers clients », au calendrier et à l’activité récente. Les indicateurs en tête de page restent sur le périmètre complet.
    </p>
</div>
