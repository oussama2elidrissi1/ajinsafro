@props([
    'user',
    'isManager' => false,
    'stats' => [],
    'quickRange' => null,
])

@php
    use Illuminate\Support\Facades\Route;
    use Carbon\Carbon;

    $displayName = $user?->name ?: 'Agent';
    $agencyName = $user?->branch?->name ?: ($user?->partner?->name ?: null);
    $roleLabel = $isManager ? 'Manager' : ($user?->isAgent() ? 'Agent' : ($user?->job_title ?: 'Agent'));
    $today = Carbon::now()->locale('fr')->isoFormat('dddd D MMMM YYYY');

    $total = (int) ($stats['reservations_total'] ?? 0);
    $pending = (int) ($stats['reservations_en_cours'] ?? 0);
    $confirmed = (int) ($stats['reservations_validees'] ?? 0);

    $ctaCreateReservation = Route::has('admin.reservations.create') && $user?->can('reservations.create');
    $ctaOffers = Route::has('admin.circuits.voyages.index');
    $ctaClients = Route::has('admin.customers.clients.index') && $user?->can('customers.clients.view');
@endphp

<div class="agent-dashboard-hero rounded-2xl border border-gray-100 bg-white shadow-custom p-5 sm:p-6 mb-6">
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-5">
        <div class="min-w-0">
            <div class="flex items-center gap-3">
                <img src="{{ $user?->avatar_url }}" alt="{{ $displayName }}" class="w-11 h-11 rounded-xl object-cover ring-1 ring-gray-200">
                <div class="min-w-0">
                    <p class="text-[11px] text-gray-500 mb-0">Bienvenue,</p>
                    <h1 class="text-xl sm:text-2xl font-black text-[#0e3a5a] leading-tight truncate">
                        Welcome back, {{ $displayName }}
                    </h1>
                </div>
            </div>
            <div class="mt-3 flex flex-wrap items-center gap-2 text-[12px] text-gray-600">
                <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-gray-50 border border-gray-200">
                    <i class="fa-regular fa-calendar"></i>
                    <span class="font-semibold">{{ $today }}</span>
                </span>
                <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-[#e6f3fa]/60 border border-[#0083c4]/15 text-[#0e3a5a]">
                    <i class="fa-solid fa-id-badge text-[#0083c4]"></i>
                    <span class="font-semibold">{{ $roleLabel }}</span>
                    @if($agencyName)
                        <span class="text-gray-500">·</span>
                        <span class="text-gray-700">{{ $agencyName }}</span>
                    @endif
                </span>
                <span class="text-gray-500">
                    Résumé: <span class="font-semibold text-gray-700">{{ $total }}</span> réservations ·
                    <span class="font-semibold text-gray-700">{{ $confirmed }}</span> confirmées ·
                    <span class="font-semibold text-gray-700">{{ $pending }}</span> en attente
                </span>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if($ctaCreateReservation)
                <a href="{{ route('admin.reservations.create') }}"
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#0083c4] text-white text-sm font-bold shadow-sm hover:opacity-95 transition-opacity">
                    <i class="fas fa-plus-circle"></i>
                    Créer une réservation
                </a>
            @endif
            @if($ctaOffers)
                <a href="{{ route('admin.circuits.voyages.index') }}"
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-gray-200 text-gray-700 text-sm font-bold bg-white hover:bg-gray-50 transition-colors">
                    <i class="fas fa-compass"></i>
                    Voir les offres
                </a>
            @endif
            @if($ctaClients)
                <a href="{{ route('admin.customers.clients.index') }}"
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-gray-200 text-gray-700 text-sm font-bold bg-white hover:bg-gray-50 transition-colors">
                    <i class="fas fa-users"></i>
                    Gérer les clients
                </a>
            @endif
        </div>
    </div>
</div>

