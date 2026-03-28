@extends('layouts.master-ajinsafro')

@section('title', 'Participants — '.$voyage->name)

@section('content')
<div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
        <a href="{{ route('admin.reservations.workspace') }}"
           class="inline-flex items-center gap-2 text-sm font-bold text-[#0083c4] hover:underline mb-2">
            <i class="fas fa-arrow-left"></i> Retour au catalogue
        </a>
        <h1 class="text-2xl font-bold text-[#0e3a5a] flex items-center gap-2">
            <i class="fas fa-users text-[#0083c4]"></i> Participants
        </h1>
        <p class="text-sm text-gray-500 mt-1 font-medium">
            {{ $voyage->name }}
            @if($travelDateId)
                <span class="text-gray-400">· travel_date_id {{ $travelDateId }}</span>
            @endif
        </p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('admin.reservations.workspace.prestation.pdf', array_filter(['voyage_id' => $voyage->id, 'travel_date_id' => $travelDateId])) }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-red-50 text-red-600 border border-red-100 text-sm font-bold hover:bg-red-100">
            <i class="fas fa-file-pdf"></i> PDF liste
        </a>
    </div>
</div>

<div class="bg-white rounded-2xl shadow border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm min-w-[800px]">
            <thead class="bg-gray-50 text-[11px] font-bold text-gray-500 uppercase border-b border-gray-100">
            <tr>
                <th class="py-3 px-4">Résa.</th>
                <th class="py-3 px-4">Client</th>
                <th class="py-3 px-4">Participant</th>
                <th class="py-3 px-4">Type</th>
                <th class="py-3 px-4">Document</th>
                <th class="py-3 px-4 text-right">Actions</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
            @forelse($reservations as $reservation)
                @if($reservation->passengers->isEmpty())
                    <tr class="hover:bg-gray-50/80">
                        <td class="py-3 px-4 font-semibold text-[#0e3a5a]">#{{ $reservation->id }}</td>
                        <td class="py-3 px-4 text-gray-600">
                            {{ trim(($reservation->client_first_name ?? '').' '.($reservation->client_last_name ?? '')) ?: '—' }}
                            <span class="text-gray-400 text-xs block mt-0.5">Pas de ligne passager enregistrée</span>
                        </td>
                        <td class="py-3 px-4">—</td>
                        <td class="py-3 px-4">—</td>
                        <td class="py-3 px-4">—</td>
                        <td class="py-3 px-4 text-right">
                            <a href="{{ route('admin.reservations.workspace.reservation.pdf', $reservation) }}"
                               class="text-[#0083c4] font-bold text-xs hover:underline">PDF</a>
                        </td>
                    </tr>
                @else
                    @foreach($reservation->passengers as $p)
                        <tr class="hover:bg-gray-50/80">
                            <td class="py-3 px-4 font-semibold text-[#0e3a5a]">#{{ $reservation->id }}</td>
                            <td class="py-3 px-4 text-gray-600">
                                {{ trim(($reservation->client_first_name ?? '').' '.($reservation->client_last_name ?? '')) ?: '—' }}
                            </td>
                            <td class="py-3 px-4">{{ trim(($p->first_name ?? '').' '.($p->last_name ?? '')) }}</td>
                            <td class="py-3 px-4">{{ $p->type }}</td>
                            <td class="py-3 px-4 text-xs">{{ $p->document_number ?? '—' }}</td>
                            <td class="py-3 px-4 text-right">
                                <a href="{{ route('admin.reservations.workspace.reservation.pdf', $reservation) }}"
                                   class="text-[#0083c4] font-bold text-xs hover:underline">PDF</a>
                            </td>
                        </tr>
                    @endforeach
                @endif
            @empty
                <tr><td colspan="6" class="py-12 text-center text-gray-500">Aucun participant trouvé.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
