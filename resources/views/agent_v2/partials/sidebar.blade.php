@php
    $user = auth()->user();
    $roleLabel = $user?->getRoleNames()->first() ?? ($user?->is_admin ? 'admin' : 'utilisateur');
    $roleLabel = \Illuminate\Support\Str::title(\Illuminate\Support\Str::replace('_', ' ', (string) $roleLabel));
    $branchLabel = $user?->branch?->name;
    $brandName = \App\Models\Setting::getValue('brand_name', 'Ajinsafro');
    $brandLogo = \App\Models\Setting::brandLogoUrl('dark');
    $menuItems = $agentPortalAdminMenu ?? [];
    $dashboardActive = request()->routeIs('agent.dashboard');
    $profileActive = request()->routeIs('admin.profile.*');
@endphp

<aside class="w-full lg:w-72 shrink-0">
    <div class="bg-white rounded-2xl shadow-custom overflow-hidden sticky top-28 border border-gray-100">
        <div class="p-6 text-center border-b border-gray-100 bg-[#e6f3fa]/30">
            <img src="{{ $user?->avatar_url }}" alt="Avatar" class="w-20 h-20 rounded-full object-cover border-4 border-white shadow-sm mx-auto mb-3">
            <h3 class="font-bold text-[#0e3a5a] text-lg leading-tight">{{ $user?->name }}</h3>
            <p class="text-[10px] font-bold text-[#f37a1f] uppercase tracking-wider mt-1">{{ $roleLabel }}</p>
            @if($branchLabel)
                <p class="text-[10px] font-semibold text-gray-500 mt-1">{{ $branchLabel }}</p>
            @endif
        </div>

        <nav class="p-4 flex flex-col gap-1.5 max-h-[70vh] overflow-y-auto text-sm">
            <a href="{{ route('agent.dashboard') }}"
               data-partner-nav
               class="flex items-center gap-3 px-4 py-3 rounded-xl mb-1 {{ $dashboardActive ? 'bg-[#e6f3fa]/60 text-[#0083c4] font-semibold' : 'hover:bg-gray-50 text-gray-600 hover:text-[#0083c4] font-medium' }} transition-colors">
                <span class="w-2.5 h-2.5 rounded-full shrink-0 {{ $dashboardActive ? 'bg-[#0083c4]' : 'bg-gray-200' }}"></span>
                <span class="leading-snug">Dashboard</span>
            </a>

            @foreach($menuItems as $node)
                @include('agent_v2.partials.sidebar-node', ['node' => $node, 'depth' => 0])
            @endforeach

            <div class="h-px bg-gray-100 my-2"></div>
            @can('dashboard.view')
                <a href="{{ route('admin.profile.edit') }}"
                   data-partner-nav
                   class="flex items-center gap-3 px-4 py-3 rounded-xl {{ $profileActive ? 'bg-[#e6f3fa]/60 text-[#0083c4] font-semibold' : 'hover:bg-gray-50 text-gray-600 hover:text-[#0083c4] font-medium' }} transition-colors">
                    <span class="w-2.5 h-2.5 rounded-full {{ $profileActive ? 'bg-[#0083c4]' : 'bg-gray-200' }}"></span>
                    Mon profil
                </a>
            @endcan

            <a href="{{ route('logout.get') }}"
               class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-red-50 text-red-500 font-medium transition-colors">
                <span class="w-2.5 h-2.5 rounded-full bg-red-200"></span>
                Se déconnecter
            </a>
        </nav>
    </div>
</aside>
