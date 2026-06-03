@php
    $user = auth()->user();
    $partner = $user->partner ?: $user->ownedPartner;
    $current = request()->route()?->getName() ?? '';
    $nav = $user->isPartnerAdmin()
        ? [
            ['label' => 'Tableau de bord', 'route' => 'partner.dashboard'],
            ['label' => 'Catalogue de voyage', 'route' => 'partner.catalogue.index'],
            ['label' => 'Mes reservations', 'route' => 'partner.reservations.index'],
            ['label' => 'Mes agents', 'route' => 'partner.agents.index'],
            ['label' => 'Wallet', 'route' => 'partner.wallet.index'],
            ['label' => 'Profil agence', 'route' => 'partner.profile-agency.edit'],
        ]
        : [
            ['label' => 'Dashboard', 'route' => 'partner.dashboard'],
            ['label' => 'Catalogue de voyage', 'route' => 'partner.catalogue.index'],
            ['label' => 'Mes reservations', 'route' => 'partner.reservations.index'],
            ['label' => 'Reservations a la carte', 'route' => 'partner.reservations-a-la-carte'],
            ['label' => 'Mon profil', 'route' => 'partner.profile.show'],
        ];
@endphp

<aside class="w-full lg:w-72 shrink-0">
    <div class="partner-v2-sidebar-sticky bg-white rounded-2xl shadow-custom overflow-hidden sticky top-28 border border-gray-100">
        <div class="p-6 text-center border-b border-gray-100 bg-[#e6f3fa]/30">
            <img src="{{ $partner?->logo_url ?? asset('build/images/logo-dark.png') }}" alt="{{ $partner?->display_name ?? 'Ajinsafro' }}" class="w-20 h-20 rounded-xl object-contain bg-white border-4 border-white shadow-sm mx-auto mb-3">
            <h3 class="font-bold text-[#0e3a5a] text-lg leading-tight">{{ $partner?->display_name ?? auth()->user()->name }}</h3>
            <p class="text-[10px] font-bold text-[#f37a1f] uppercase tracking-wider mt-1">{{ $user->isPartnerAdmin() ? 'Admin partenaire' : 'Agent partenaire' }}</p>
        </div>
        <nav class="p-4 flex flex-col gap-1.5">
            @foreach($nav as $item)
                @php
                    $isActive = $current === $item['route'] || str_starts_with($current, $item['route']);
                @endphp
                <a href="{{ route($item['route']) }}"
                   data-partner-nav
                   class="flex items-center gap-3 px-4 py-3 rounded-xl {{ $isActive ? 'bg-[#e6f3fa]/60 text-[#0083c4] font-semibold' : 'hover:bg-gray-50 text-gray-600 hover:text-[#0083c4] font-medium' }} text-sm transition-colors">
                    <span class="w-2.5 h-2.5 rounded-full {{ $isActive ? 'bg-[#0083c4]' : 'bg-gray-200' }}"></span>
                    {{ $item['label'] }}
                </a>
            @endforeach
            <div class="h-px bg-gray-100 my-2"></div>
            <form method="POST" action="{{ route('partner.logout') }}">
                @csrf
                <button type="submit" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-red-50 text-red-500 font-medium text-sm transition-colors">
                    <span class="w-2.5 h-2.5 rounded-full bg-red-200"></span>
                    Deconnexion
                </button>
            </form>
        </nav>
    </div>
</aside>
