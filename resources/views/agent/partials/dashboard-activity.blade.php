@props(['recentActivityReservations'])

<div class="bg-white rounded-2xl shadow-custom border border-gray-100 p-5 mt-6">
    <h3 class="font-bold text-[#0e3a5a] mb-4 flex items-center gap-2 border-b border-gray-100 pb-2">
        <i class="fas fa-history text-[#0083c4]"></i>
        Activité récente (dossiers)
    </h3>
    <ul class="space-y-3 text-sm">
        @forelse($recentActivityReservations as $r)
            <li class="flex items-start justify-between gap-3 py-2 border-b border-gray-50 last:border-0">
                <div>
                    <span class="font-semibold text-[#0e3a5a]">#{{ $r->id }}</span>
                    <span class="text-gray-500 text-xs ml-2">{{ trim(($r->client_first_name ?? '') . ' ' . ($r->client_last_name ?? '')) ?: 'Client' }}</span>
                </div>
                <div class="text-right shrink-0">
                    <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded border
                        @if($r->status === \App\Models\Reservation::STATUS_VALIDEE) bg-green-50 text-green-700 border-green-100
                        @elseif($r->status === \App\Models\Reservation::STATUS_ANNULEE) bg-red-50 text-red-700 border-red-100
                        @else bg-yellow-50 text-yellow-800 border-yellow-100 @endif">{{ $r->status }}</span>
                    <p class="text-[10px] text-gray-400 mt-1">{{ optional($r->created_at)->diffForHumans() }}</p>
                </div>
            </li>
        @empty
            <li class="text-gray-500 text-center py-6">Aucune activité récente.</li>
        @endforelse
    </ul>
</div>
