@php
    $user = auth()->user();
    $roleLabel = $user?->getRoleNames()->first() ?? ($user?->is_admin ? 'admin' : 'utilisateur');
    $roleLabel = \Illuminate\Support\Str::title(\Illuminate\Support\Str::replace('_', ' ', (string) $roleLabel));
    $branchLabel = $user?->branch?->name;
    $brandName = \App\Models\Setting::getValue('brand_name', 'Ajinsafro');
    $brandLogo = \App\Models\Setting::brandLogoUrl('dark');
    $menuItems = $agentPortalAdminMenu ?? [];
    $dashboardActive = request()->routeIs('agent.dashboard');
    $profileActive = request()->routeIs('agent.profile');
@endphp

<aside class="w-full lg:w-72 shrink-0">
    <div class="bg-white rounded-2xl shadow-custom overflow-hidden sticky top-28 border border-gray-100">
        <div class="p-6 text-center border-b border-gray-100 bg-[#e6f3fa]/30">
            <img src="{{ $user?->avatar_url }}" alt="Avatar" class="w-20 h-20 rounded-full object-cover border-4 border-white shadow-sm mx-auto mb-3">
            <h3 class="font-semibold text-[#0e3a5a] text-lg leading-tight">{{ $user?->name }}</h3>
            <p class="text-[10px] font-semibold text-[#f37a1f] uppercase tracking-wider mt-1">{{ $roleLabel }}</p>
            @if($branchLabel)
                <p class="text-[10px] text-gray-500 mt-1">{{ $branchLabel }}</p>
            @endif
        </div>

        <nav class="agent-sidebar-menu">
            @can('dashboard.view')
                <a href="{{ route('agent.dashboard') }}"
                   data-partner-nav
                   class="agent-sidebar-link {{ $dashboardActive ? 'active' : '' }}">
                    <i class="bx bx-grid-alt agent-sidebar-icon"></i>
                    <span class="agent-sidebar-text">Dashboard</span>
                </a>
            @endcan

            @foreach($menuItems as $node)
                @include('agent_v2.partials.sidebar-node', ['node' => $node, 'depth' => 0])
            @endforeach

            <div class="agent-sidebar-divider"></div>

            @if($user?->can('dashboard.view') || $user?->can('custom_requests.view'))
                <a href="{{ route('agent.profile') }}"
                   data-partner-nav
                   class="agent-sidebar-link {{ $profileActive ? 'active' : '' }}">
                    <i class="bx bx-user agent-sidebar-icon"></i>
                    <span class="agent-sidebar-text">Mon profil</span>
                </a>
            @endif

            <a href="{{ route('logout.get') }}"
               class="agent-sidebar-link agent-sidebar-logout">
                <i class="bx bx-log-out agent-sidebar-icon"></i>
                <span class="agent-sidebar-text">Se deconnecter</span>
            </a>
        </nav>
    </div>
</aside>
