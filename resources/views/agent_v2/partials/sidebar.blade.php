@php
    use Illuminate\Support\Facades\Route;

    $user = auth()->user();
    $current = Route::currentRouteName() ?? '';
    $roleLabel = $user?->getRoleNames()->first() ?? ($user?->is_admin ? 'admin' : 'utilisateur');
    $roleLabel = \Illuminate\Support\Str::title(\Illuminate\Support\Str::replace('_', ' ', (string) $roleLabel));
    $branchLabel = $user?->branch?->name;

    $allowedSections = ['reservations', 'customers', 'circuits', 'operations', 'visa', 'messagerie'];
    $menuSections = collect(config('admin_menu.items', []))
        ->filter(fn ($section) => in_array($section['key'] ?? '', $allowedSections, true))
        ->values();

    $nav = [
        ['label' => "Vue d'ensemble", 'route' => 'agent.dashboard'],
    ];

    foreach ($menuSections as $section) {
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

        $hasSectionPermission = empty($section['permission']) || $user->can($section['permission']);
        if (! $hasSectionPermission || $children->isEmpty()) {
            continue;
        }

        foreach ($children as $child) {
            $route = $child['route'] ?? null;
            if (! $route || ! Route::has($route)) {
                continue;
            }
            $nav[] = [
                'label' => (string) ($child['label'] ?? ''),
                'route' => $route,
            ];
        }
    }

    if (Route::has('admin.profile.edit') && $user->can('dashboard.view')) {
        $nav[] = ['label' => 'Mon profil', 'route' => 'admin.profile.edit'];
    }

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
        <nav class="p-4 flex flex-col gap-1.5 max-h-[70vh] overflow-y-auto">
            @foreach($nav as $item)
                @php $isActive = $navActive($item['route']); @endphp
                <a href="{{ route($item['route']) }}"
                   data-partner-nav
                   class="flex items-center gap-3 px-4 py-3 rounded-xl {{ $isActive ? 'bg-[#e6f3fa]/60 text-[#0083c4] font-semibold' : 'hover:bg-gray-50 text-gray-600 hover:text-[#0083c4] font-medium' }} text-sm transition-colors">
                    <span class="w-2.5 h-2.5 rounded-full shrink-0 {{ $isActive ? 'bg-[#0083c4]' : 'bg-gray-200' }}"></span>
                    <span class="leading-snug">{{ $item['label'] }}</span>
                </a>
            @endforeach
            <div class="h-px bg-gray-100 my-2"></div>
            <a href="{{ route('logout.get') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-red-50 text-red-500 font-medium text-sm transition-colors">
                <span class="w-2.5 h-2.5 rounded-full shrink-0 bg-red-200"></span>
                Se déconnecter
            </a>
        </nav>
    </div>
</aside>
