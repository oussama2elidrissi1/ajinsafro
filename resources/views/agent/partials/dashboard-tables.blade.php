@props([
    'recentReservations',
    'recentClients',
    'reservationsListUrl' => null,
    'canOpenReservation' => false,
    'isManager' => false,
    'quickRange' => null,
])

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
    <div class="bg-white rounded-2xl shadow-custom border border-gray-100 flex flex-col overflow-hidden lg:col-span-2">
        <div class="p-5 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-gray-50/50">
            <div>
                <h3 class="font-bold text-[#0e3a5a] mb-0">Reservations overview</h3>
                <p class="text-[11px] text-gray-500 mb-0 mt-1">Dernières réservations (selon filtres)</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <div class="agent-dashboard-range inline-flex items-center rounded-xl border border-gray-200 bg-white p-1">
                    @php
                        $range = $quickRange;
                        $btn = 'px-3 py-1.5 rounded-lg text-[12px] font-bold border border-transparent hover:bg-gray-50 transition-colors';
                    @endphp
                    <a class="{{ $btn }}" href="{{ request()->fullUrlWithQuery(['range' => 'today']) }}" aria-current="{{ $range === 'today' ? 'page' : 'false' }}">Today</a>
                    <a class="{{ $btn }}" href="{{ request()->fullUrlWithQuery(['range' => 'week']) }}" aria-current="{{ $range === 'week' ? 'page' : 'false' }}">This week</a>
                    <a class="{{ $btn }}" href="{{ request()->fullUrlWithQuery(['range' => 'month']) }}" aria-current="{{ $range === 'month' ? 'page' : 'false' }}">This month</a>
                    <a class="{{ $btn }}" href="{{ request()->fullUrlWithQuery(['range' => null]) }}" aria-current="{{ $range === null ? 'page' : 'false' }}">All</a>
                </div>
                @if($reservationsListUrl)
                    <a href="{{ $reservationsListUrl }}" class="text-[10px] font-bold text-[#0083c4] hover:underline uppercase tracking-wider">Voir tout</a>
                @endif
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse whitespace-nowrap min-w-[720px]">
                <thead>
                <tr class="bg-gray-50 border-b border-gray-100 text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                    <th class="py-4 px-6">#</th>
                    <th class="py-4 px-6">Offer</th>
                    <th class="py-4 px-6">Client</th>
                    @if($isManager)
                        <th class="py-4 px-6">Agent</th>
                    @endif
                    <th class="py-4 px-6">Statut</th>
                    <th class="py-4 px-6">Date</th>
                    <th class="py-4 px-6 text-right">Price</th>
                    <th class="py-4 px-6 text-right">Actions</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                @forelse($recentReservations as $reservation)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="py-4 px-6 font-semibold text-gray-800">{{ $reservation->id }}</td>
                        <td class="py-4 px-6 font-semibold text-gray-800">{{ $reservation->tour?->name ?? '—' }}</td>
                        <td class="py-4 px-6 text-gray-600">{{ trim(($reservation->client_first_name ?? '') . ' ' . ($reservation->client_last_name ?? '')) ?: '—' }}</td>
                        @if($isManager)
                            <td class="py-4 px-6 text-xs text-gray-600">{{ $reservation->agent?->name ?? '—' }}</td>
                        @endif
                        <td class="py-4 px-6">
                            @php
                                $status = $reservation->status;
                                $badge = $status === \App\Models\Reservation::STATUS_VALIDEE ? 'bg-green-50 text-green-700 border-green-200' : ($status === \App\Models\Reservation::STATUS_ANNULEE ? 'bg-red-50 text-red-700 border-red-200' : 'bg-yellow-50 text-yellow-700 border-yellow-200');
                            @endphp
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold border {{ $badge }}">{{ $status }}</span>
                        </td>
                        <td class="py-4 px-6 text-gray-500">{{ optional($reservation->created_at)->format('d/m/Y H:i') }}</td>
                        <td class="py-4 px-6 text-right font-bold text-gray-800">
                            {{ number_format((float) ($reservation->paid_amount ?? 0), 0, ',', ' ') }} DH
                        </td>
                        <td class="py-4 px-6 text-right">
                            @if($canOpenReservation)
                                <a href="{{ route('admin.reservations.show', $reservation) }}" class="text-[#0083c4] font-bold text-xs hover:underline">Ouvrir</a>
                            @else
                                <span class="text-gray-400 text-xs">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $isManager ? 8 : 7 }}" class="py-10 px-6 text-center text-gray-500">Aucune réservation trouvée.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-custom border border-gray-100 flex flex-col overflow-hidden">
        <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
            <h3 class="font-bold text-[#0e3a5a]">Derniers clients</h3>
            @if(Route::has('admin.customers.clients.index') && auth()->user()->can('customers.clients.view'))
                <a href="{{ route('admin.customers.clients.index') }}" class="text-[10px] font-bold text-[#0083c4] hover:underline uppercase tracking-wider">Voir tout</a>
            @endif
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                <tr class="bg-gray-50 border-b border-gray-100 text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                    <th class="py-4 px-5">Code</th>
                    <th class="py-4 px-5">Nom</th>
                    <th class="py-4 px-5">Contact</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                @forelse($recentClients as $client)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="py-4 px-5 font-semibold text-gray-800">{{ $client->client_code }}</td>
                        <td class="py-4 px-5 text-gray-700">{{ $client->full_name ?: trim(($client->first_name ?? '') . ' ' . ($client->last_name ?? '')) }}</td>
                        <td class="py-4 px-5 text-gray-500">{{ $client->email ?: ($client->phone ?: '—') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="py-10 px-5 text-center text-gray-500">Aucun client trouvé.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
