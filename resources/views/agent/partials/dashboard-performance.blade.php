@props([
    'topOffers' => ['labels' => [], 'bookings' => [], 'revenue' => []],
])

@php
    $labels = $topOffers['labels'] ?? [];
    $bookings = $topOffers['bookings'] ?? [];
    $revenue = $topOffers['revenue'] ?? [];
@endphp

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
    <div class="bg-white rounded-2xl shadow-custom border border-gray-100 overflow-hidden lg:col-span-2">
        <div class="p-5 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
            <div>
                <h3 class="font-bold text-[#0e3a5a] mb-0">Performance</h3>
                <p class="text-[11px] text-gray-500 mb-0 mt-1">Réservations par offre (top 8)</p>
            </div>
        </div>
        <div class="p-5">
            <div class="h-[260px]">
                <canvas id="agentDashboardBookingsChart" data-labels='@json($labels)' data-series='@json($bookings)'></canvas>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-custom border border-gray-100 overflow-hidden">
        <div class="p-5 border-b border-gray-100 bg-gray-50/50">
            <h3 class="font-bold text-[#0e3a5a] mb-0">Best-selling offers</h3>
            <p class="text-[11px] text-gray-500 mb-0 mt-1">Top offres par volume</p>
        </div>
        <div class="p-4">
            @forelse($labels as $i => $name)
                <div class="flex items-center justify-between gap-3 px-3 py-2.5 rounded-xl hover:bg-gray-50 transition-colors">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-gray-800 truncate mb-0">{{ $name }}</p>
                        <p class="text-[11px] text-gray-500 mb-0 mt-0.5">
                            {{ (int) ($bookings[$i] ?? 0) }} réservations · {{ number_format((float) ($revenue[$i] ?? 0), 0, ',', ' ') }} DH
                        </p>
                    </div>
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-[#e6f3fa]/60 border border-[#0083c4]/15 text-[#0083c4] font-black text-sm">
                        {{ $i + 1 }}
                    </span>
                </div>
            @empty
                <div class="py-10 text-center text-gray-500 text-sm">Aucune donnée de performance.</div>
            @endforelse
        </div>
    </div>
</div>

