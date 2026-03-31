@php
    use Illuminate\Support\Facades\Route;

    $user = auth()->user();
    $current = Route::currentRouteName() ?? '';
    $roleLabel = $user?->getRoleNames()->first() ?? ($user?->is_admin ? 'admin' : 'utilisateur');
    $roleLabel = \Illuminate\Support\Str::title(\Illuminate\Support\Str::replace('_', ' ', (string) $roleLabel));
    $branchLabel = $user?->branch?->name;
    $brandName = \App\Models\Setting::getValue('brand_name', 'Ajinsafro');
    $brandLogo = \App\Models\Setting::brandLogoUrl('dark');

    $navActive = function (string $routeName) use ($current): bool {
        if ($current === $routeName) {
            return true;
        }
        $parts = explode('.', $routeName);
        if (count($parts) < 2) {
            return false;
        }
        $prefix = $parts[0] . '.' . $parts[1];

        return $current === $prefix || str_starts_with($current, $prefix . '.');
    };

    $allowedSections = ['reservations', 'customers', 'circuits', 'operations', 'visa', 'messagerie'];
    $menuSections = collect(config('admin_menu.items', []))
        ->filter(fn ($section) => in_array($section['key'] ?? '', $allowedSections, true))
        ->values();

    $standaloneItems = [];
    $groups = [];
    foreach ($menuSections as $section) {
        $sectionRoute = !empty($section['route']) && Route::has($section['route'])
            ? (string) $section['route']
            : '';
        $children = collect($section['children'] ?? [])
            ->filter(function ($child) use ($user) {
                if (! empty($child['route']) && ! Route::has($child['route'])) {
                    return false;
                }
                if (! empty($child['roles']) && ! $user->hasRole($child['roles'])) {
                    return false;
                }
                if (! empty($child['permission']) && ! $user->can($child['permission'])) {
                    return false;
                }
                return true;
            });

        if (($section['key'] ?? '') === 'circuits'
            && Route::has('admin.circuits.departs-dates')
            && (empty($section['permission']) || $user->can($section['permission']))
        ) {
            $hasDeparts = $children->contains(fn ($c) => ($c['route'] ?? '') === 'admin.circuits.departs-dates');
            if (! $hasDeparts) {
                $children->push([
                    'label' => 'Départs / disponibilités',
                    'route' => 'admin.circuits.departs-dates',
                    'permission' => null,
                ]);
            }
        }

        $hasSectionPermission = empty($section['permission']) || $user->can($section['permission']);
        if (! $hasSectionPermission) {
            continue;
        }

        if ($children->isEmpty()) {
            if ($sectionRoute !== '') {
                $standaloneItems[] = [
                    'label' => (string) ($section['label'] ?? ''),
                    'route' => $sectionRoute,
                    'query' => $section['query'] ?? null,
                ];
            }

            continue;
        }

        $items = $children->map(function ($child) {
            return [
                'label' => (string) ($child['label'] ?? ''),
                'route' => (string) ($child['route'] ?? ''),
                'query' => $child['query'] ?? null,
            ];
        })->filter(fn ($item) => $item['route'] !== '' && Route::has($item['route']))->values()->all();

        if ($items === []) {
            continue;
        }

        $open = collect($items)->contains(fn ($item) => $navActive($item['route']));
        $groups[] = [
            'label' => (string) ($section['label'] ?? ''),
            'items' => $items,
            'open' => $open,
        ];
    }

    $accountItems = [];
    if (Route::has('admin.profile.edit') && $user->can('dashboard.view')) {
        $accountItems[] = ['label' => 'Mon profil', 'route' => 'admin.profile.edit'];
    }
    $accountOpen = collect($accountItems)->contains(fn ($item) => $navActive($item['route']));

    $dashboardActive = $current === 'agent.dashboard';
@endphp

<aside class="w-full lg:w-72 shrink-0">
    <div class="bg-white rounded-2xl shadow-custom overflow-hidden sticky top-6 lg:top-8 border border-gray-100">
        <div class="px-6 py-5 border-b border-gray-100 bg-white">
            <a href="{{ route('agent.dashboard') }}" class="flex items-center justify-center">
                <img src="{{ $brandLogo }}" alt="{{ $brandName }}" class="max-h-10 w-auto">
            </a>
        </div>
        <div class="p-6 text-center border-b border-gray-100 bg-[#e6f3fa]/30">
            <img src="{{ $user?->avatar_url }}" alt="Avatar" class="w-20 h-20 rounded-full object-cover border-4 border-white shadow-sm mx-auto mb-3">
            <h3 class="font-bold text-[#0e3a5a] text-lg leading-tight">{{ $user?->name }}</h3>
            <p class="text-[10px] font-bold text-[#f37a1f] uppercase tracking-wider mt-1">{{ $roleLabel }}</p>
            @if($branchLabel)
                <p class="text-[10px] font-semibold text-gray-500 mt-1">{{ $branchLabel }}</p>
            @endif
        </div>

        <nav class="p-4 flex flex-col gap-1 max-h-[70vh] overflow-y-auto text-sm">
            {{-- Vue d'ensemble --}}
            <a href="{{ route('agent.dashboard') }}"
               data-partner-nav
               class="flex items-center gap-3 px-4 py-3 rounded-xl mb-1 {{ $dashboardActive ? 'bg-[#e6f3fa]/60 text-[#0083c4] font-semibold' : 'hover:bg-gray-50 text-gray-600 hover:text-[#0083c4] font-medium' }} transition-colors">
                <span class="w-2.5 h-2.5 rounded-full shrink-0 {{ $dashboardActive ? 'bg-[#0083c4]' : 'bg-gray-200' }}"></span>
                <span class="leading-snug">Vue d'ensemble</span>
            </a>

            @foreach($standaloneItems as $item)
                @php $isActive = $navActive($item['route']); @endphp
                @php $itemHref = !empty($item['query']) ? route($item['route'], $item['query']) : route($item['route']); @endphp
                <a href="{{ $itemHref }}"
                   data-partner-nav
                   class="flex items-center gap-3 px-4 py-3 rounded-xl mb-1 {{ $isActive ? 'bg-[#e6f3fa]/60 text-[#0083c4] font-semibold' : 'hover:bg-gray-50 text-gray-600 hover:text-[#0083c4] font-medium' }} transition-colors">
                    <span class="w-2.5 h-2.5 rounded-full shrink-0 {{ $isActive ? 'bg-[#0083c4]' : 'bg-gray-200' }}"></span>
                    <span class="leading-snug">{{ $item['label'] }}</span>
                </a>
            @endforeach

            @foreach($groups as $group)
                <details class="agent-nav-group rounded-xl border border-transparent hover:border-gray-100 {{ $group['open'] ? 'bg-gray-50/80' : '' }}" {{ $group['open'] ? 'open' : '' }}>
                    <summary class="flex items-center justify-between gap-2 px-4 py-2.5 cursor-pointer list-none select-none text-[11px] font-bold uppercase tracking-wider text-[#0e3a5a] [&::-webkit-details-marker]:hidden">
                        <span>{{ $group['label'] }}</span>
                        <i class="fas fa-chevron-right text-[10px] text-gray-400 transition-transform agent-nav-chevron"></i>
                    </summary>
                    <div class="pb-2 pt-0 flex flex-col gap-0.5 px-1">
                        @foreach($group['items'] as $item)
                            @php $isActive = $navActive($item['route']); @endphp
                            @php $itemHref = !empty($item['query']) ? route($item['route'], $item['query']) : route($item['route']); @endphp
                            <a href="{{ $itemHref }}"
                               data-partner-nav
                               class="flex items-center gap-3 px-3 py-2.5 rounded-lg {{ $isActive ? 'bg-[#e6f3fa]/60 text-[#0083c4] font-semibold' : 'text-gray-600 hover:bg-white hover:text-[#0083c4] font-medium' }} transition-colors">
                                <span class="w-1.5 h-1.5 rounded-full shrink-0 {{ $isActive ? 'bg-[#0083c4]' : 'bg-gray-300' }}"></span>
                                <span class="leading-snug">{{ $item['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </details>
            @endforeach

            <details class="agent-nav-group rounded-xl border border-transparent hover:border-gray-100 mt-1 {{ $accountOpen ? 'bg-gray-50/80' : '' }}" {{ $accountOpen ? 'open' : '' }}>
                <summary class="flex items-center justify-between gap-2 px-4 py-2.5 cursor-pointer list-none select-none text-[11px] font-bold uppercase tracking-wider text-[#0e3a5a] [&::-webkit-details-marker]:hidden">
                    <span>Compte</span>
                    <i class="fas fa-chevron-right text-[10px] text-gray-400 transition-transform agent-nav-chevron"></i>
                </summary>
                <div class="pb-2 pt-0 flex flex-col gap-0.5 px-1">
                    @foreach($accountItems as $item)
                        @php $isActive = $navActive($item['route']); @endphp
                        <a href="{{ route($item['route']) }}"
                           data-partner-nav
                           class="flex items-center gap-3 px-3 py-2.5 rounded-lg {{ $isActive ? 'bg-[#e6f3fa]/60 text-[#0083c4] font-semibold' : 'text-gray-600 hover:bg-white hover:text-[#0083c4] font-medium' }} transition-colors">
                            <span class="w-1.5 h-1.5 rounded-full shrink-0 {{ $isActive ? 'bg-[#0083c4]' : 'bg-gray-300' }}"></span>
                            <span class="leading-snug">{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                    <a href="{{ route('logout.get') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-red-500 hover:bg-red-50 font-medium transition-colors">
                        <span class="w-1.5 h-1.5 rounded-full shrink-0 bg-red-200"></span>
                        Se déconnecter
                    </a>
                </div>
            </details>
        </nav>
    </div>
</aside>

<style>
    .agent-nav-group[open] > summary .agent-nav-chevron { transform: rotate(90deg); }
</style>
