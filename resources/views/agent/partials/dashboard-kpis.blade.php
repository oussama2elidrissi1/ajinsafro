@props([
    'stats' => [],
    'subtitle' => null,
])

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 lg:gap-6">
    <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-custom border border-gray-100 flex items-center gap-4">
        <div class="w-14 h-14 rounded-full bg-blue-50 flex items-center justify-center shrink-0 text-[#0083c4] text-xl">
            <i class="fas fa-suitcase-rolling"></i>
        </div>
        <div>
            <p class="text-[10px] lg:text-[11px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Réservations</p>
            <h4 class="text-2xl lg:text-3xl font-black text-[#0e3a5a] leading-none">{{ $stats['reservations_total'] ?? 0 }}</h4>
            @if($subtitle)
                <p class="text-[10px] text-gray-400 mt-1">{{ $subtitle }}</p>
            @endif
        </div>
    </div>
    <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-custom border border-gray-100 flex items-center gap-4">
        <div class="w-14 h-14 rounded-full bg-yellow-50 flex items-center justify-center shrink-0 text-yellow-600 text-xl">
            <i class="fas fa-hourglass-half"></i>
        </div>
        <div>
            <p class="text-[10px] lg:text-[11px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">En cours</p>
            <h4 class="text-2xl lg:text-3xl font-black text-[#0e3a5a] leading-none">{{ $stats['reservations_en_cours'] ?? 0 }}</h4>
        </div>
    </div>
    <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-custom border border-gray-100 flex items-center gap-4">
        <div class="w-14 h-14 rounded-full bg-green-50 flex items-center justify-center shrink-0 text-green-600 text-xl">
            <i class="fas fa-check-circle"></i>
        </div>
        <div>
            <p class="text-[10px] lg:text-[11px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Validées</p>
            <h4 class="text-2xl lg:text-3xl font-black text-[#0e3a5a] leading-none">{{ $stats['reservations_validees'] ?? 0 }}</h4>
        </div>
    </div>
    <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-custom border border-gray-100 flex items-center gap-4">
        <div class="w-14 h-14 rounded-full bg-orange-50 flex items-center justify-center shrink-0 text-[#f37a1f] text-xl">
            <i class="fas fa-users"></i>
        </div>
        <div>
            <p class="text-[10px] lg:text-[11px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Clients</p>
            <h4 class="text-2xl lg:text-3xl font-black text-[#0e3a5a] leading-none">{{ $stats['clients_count'] ?? 0 }}</h4>
        </div>
    </div>
    <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-custom border border-gray-100 flex items-center gap-4">
        <div class="w-14 h-14 rounded-full bg-purple-50 flex items-center justify-center shrink-0 text-purple-600 text-xl">
            <i class="fas fa-plane-departure"></i>
        </div>
        <div>
            <p class="text-[10px] lg:text-[11px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Voyages (catalogue)</p>
            <h4 class="text-2xl lg:text-3xl font-black text-[#0e3a5a] leading-none">{{ $stats['voyages_count'] ?? 0 }}</h4>
        </div>
    </div>
    <div class="bg-white p-5 lg:p-6 rounded-2xl shadow-custom border border-gray-100 flex items-center gap-4">
        <div class="w-14 h-14 rounded-full bg-indigo-50 flex items-center justify-center shrink-0 text-indigo-600 text-xl">
            <i class="fas fa-calendar-check"></i>
        </div>
        <div>
            <p class="text-[10px] lg:text-[11px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Départs à venir</p>
            <h4 class="text-2xl lg:text-3xl font-black text-[#0e3a5a] leading-none">{{ $stats['departures_upcoming'] ?? 0 }}</h4>
        </div>
    </div>
</div>
