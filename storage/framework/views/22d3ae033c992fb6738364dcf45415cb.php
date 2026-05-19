<?php $__env->startSection('title', 'Dashboard V5'); ?>

<?php
    $dashboardUser = auth()->user();
    $dashboardUserName = $dashboardUser?->name ?? 'Manager';
    $dashboardUserRole = $dashboardUser?->getRoleNames()->first() ?? 'chef_commercial';
    $dashboardInitials = strtoupper(collect(preg_split('/\s+/', trim((string) $dashboardUserName)))->filter()->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->implode(''));
    if ($dashboardInitials === '') {
        $dashboardInitials = 'MC';
    }

    $dashboardBrandName = \App\Models\Setting::getValue('brand_name', 'Ajinsafro.ma');
    $dashboardBrandLogo = \App\Models\Setting::brandLogoUrl('dark');
    $dashboardDateLabel = now('Africa/Casablanca')->locale('fr')->translatedFormat('l d F Y');
    $dashboardDateTimeLabel = now('Africa/Casablanca')->locale('fr')->translatedFormat('l d F Y - H:i');

    $sourceHtml = file_get_contents(resource_path('views/admin/dashboard/index ajinsafro.html'));
    preg_match('/<body[^>]*>(.*)<\/body>/is', $sourceHtml, $matches);
    $bodyHtml = $matches[1] ?? '';
    $bodyHtml = preg_replace('/<script>[\s\S]*?<\/script>\s*$/', '', $bodyHtml);

    $activeLinkClasses = 'block py-2 px-3 text-xs font-bold rounded-lg bg-[#e8f4fc] text-[#0b548b] shadow-sm';
    $inactiveLinkClasses = 'sidebar-item block py-2 px-3 text-xs font-semibold text-slate-500 rounded-lg hover:bg-slate-50 hover:text-slate-900';
    $dashboardLink = function (string $route, string $label) use ($activeLinkClasses, $inactiveLinkClasses): string {
        $active = request()->routeIs($route);
        $classes = $active ? $activeLinkClasses : $inactiveLinkClasses;

        return '<a href="' . e(route($route)) . '" class="' . $classes . '"><span class="dashboard-v5-nav-label">' . e($label) . '</span></a>';
    };

    $dashboardMenuBlock = <<<'HTML'
                <!-- Dashboard Collapsible Group -->
                <div class="space-y-1">
                    <button class="w-full flex items-center justify-between py-2 px-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                        <span class="flex items-center space-x-2">
                            <i data-lucide="layout-dashboard" class="w-4 h-4 text-[#0081bc]"></i>
                            <span class="dashboard-v5-nav-label">Tableau de bord</span>
                        </span>
                        <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400 dashboard-v5-nav-caret"></i>
                    </button>
                    <div class="ml-2 pl-4 border-l border-slate-200 space-y-1 dashboard-v5-submenu">
                        __VUE_GLOBALE__
                        __STATS__
                        __ALERTES__
                        __V2__
                        __V3__
                        __V4__
                        __V5__
                    </div>
                </div>
HTML;

    $dashboardMenuBlock = str_replace(
        ['__VUE_GLOBALE__', '__STATS__', '__ALERTES__', '__V2__', '__V3__', '__V4__', '__V5__'],
        [
            $dashboardLink('admin.dashboard.vue-globale', "Vue d'ensemble"),
            $dashboardLink('admin.dashboard.statistiques', 'Statistiques'),
            $dashboardLink('admin.dashboard.alertes', 'Alertes'),
            $dashboardLink('admin.dashboard.v2', 'Dashboard V2'),
            $dashboardLink('admin.dashboard.v3', 'Dashboard V3'),
            $dashboardLink('admin.dashboard.v4', 'Dashboard V4'),
            $dashboardLink('admin.dashboard.v5', 'Dashboard V5'),
        ],
        $dashboardMenuBlock
    );

    $bodyHtml = str_replace(
        '<div class="flex h-screen w-screen overflow-hidden">',
        '<div id="dashboardV5Page" class="dashboard-v5-page flex h-screen w-screen overflow-hidden">',
        $bodyHtml
    );

    $bodyHtml = str_replace(
        '<aside class="w-64 bg-white border-r border-slate-200 flex flex-col shrink-0 hidden lg:flex h-full z-30">',
        '<aside class="dashboard-v5-sidebar w-64 bg-white border-r border-slate-200 flex flex-col shrink-0 hidden lg:flex h-full z-30">',
        $bodyHtml
    );

    $bodyHtml = str_replace(
        '<div class="flex-1 flex flex-col overflow-hidden h-full bg-[#f8fafc]">',
        '<div class="dashboard-v5-main flex-1 flex flex-col overflow-hidden h-full bg-[#f8fafc]">',
        $bodyHtml
    );

    $bodyHtml = str_replace(
        '<div class="h-14 bg-white flex items-center justify-center px-4 shrink-0 border-b border-slate-200 shadow-sm">
                <a href="#" class="flex items-center justify-center shrink-0">
                    <img src="https://booking.ajinsafro.net/storage/home-settings/header/6hZldTmcYICYg6eP8O52lt8GnBe8iuHSi5xiWrdQ.png" alt="Ajinsafro.ma" class="h-8 w-auto max-w-[170px] object-contain transition-opacity duration-300 hover:opacity-95" onerror="this.onerror=null; this.src=\'https://via.placeholder.com/150x32/0b548b/ffffff?text=Ajinsafro.ma\';">
                </a>
            </div>',
        '<div class="h-14 bg-white flex items-center justify-between px-4 shrink-0 border-b border-slate-200 shadow-sm">
                <a href="' . e(route('admin.dashboard.v5')) . '" class="flex items-center justify-center shrink-0">
                    <img src="' . e($dashboardBrandLogo) . '" alt="' . e($dashboardBrandName) . '" class="dashboard-v5-logo-text h-8 w-auto max-w-[170px] object-contain transition-opacity duration-300 hover:opacity-95">
                </a>
                <button id="dashboardV5SidebarToggle" type="button" class="dashboard-v5-sidebar-toggle inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:bg-slate-50 hover:text-slate-700" aria-label="Basculer la sidebar">
                    <i data-lucide="panel-left-close" class="w-4 h-4"></i>
                </button>
            </div>',
        $bodyHtml
    );

    $bodyHtml = str_replace(
        '<div class="p-5 border-b border-slate-100 flex flex-col items-center text-center bg-white">
                <div class="w-14 h-14 rounded-full bg-gradient-to-tr from-[#0081bc] to-[#005a84] flex items-center justify-center text-base font-bold text-white mb-2 shadow-md">
                    M
                </div>
                <div class="text-sm font-bold text-slate-800">Manager</div>
                <div class="text-[11px] font-semibold text-slate-400">chef_commercial</div>
            </div>',
        '<div class="dashboard-v5-desktop-profile p-5 border-b border-slate-100 flex flex-col items-center text-center bg-white">
                <div class="w-14 h-14 rounded-full bg-gradient-to-tr from-[#0081bc] to-[#005a84] flex items-center justify-center text-base font-bold text-white mb-2 shadow-md">' . e($dashboardInitials) . '</div>
                <div class="dashboard-v5-profile-info">
                    <div class="text-sm font-bold text-slate-800">' . e($dashboardUserName) . '</div>
                    <div class="text-[11px] font-semibold text-slate-400">' . e($dashboardUserRole) . '</div>
                </div>
            </div>',
        $bodyHtml
    );

    $bodyHtml = str_replace(
        '<!-- Dashboard Collapsible Group -->
                <div class="space-y-1">
                    <button class="w-full flex items-center justify-between py-2 px-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                        <span class="flex items-center space-x-2">
                            <i data-lucide="layout-dashboard" class="w-4 h-4 text-[#0081bc]"></i>
                            <span>Tableau de bord</span>
                        </span>
                        <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400"></i>
                    </button>
                    <div class="ml-2 pl-4 border-l border-slate-200 space-y-1">
                        <a href="#" class="block py-2 px-3 text-xs font-bold rounded-lg bg-[#e8f4fc] text-[#0b548b] shadow-sm">
                            Vue d\'ensemble
                        </a>
                        <a href="#" class="sidebar-item block py-2 px-3 text-xs font-semibold text-slate-500 rounded-lg hover:bg-slate-50 hover:text-slate-900">
                            Statistiques
                        </a>
                        <a href="#" class="sidebar-item block py-2 px-3 text-xs font-semibold text-slate-500 rounded-lg hover:bg-slate-50 hover:text-slate-900">
                            Alertes
                        </a>
                        <a href="#" class="sidebar-item block py-2 px-3 text-xs font-semibold text-slate-500 rounded-lg hover:bg-slate-50 hover:text-slate-900">
                            Dashboard V2
                        </a>
                    </div>
                </div>',
        $dashboardMenuBlock,
        $bodyHtml
    );

    $bodyHtml = str_replace(
        'placeholder="Rechercher (voyage, agence, réservation...)"',
        'placeholder="Rechercher (voyage, agence, réservation...)"',
        $bodyHtml
    );

    $bodyHtml = str_replace(
        '<img src="https://booking.ajinsafro.net/storage/home-settings/header/6hZldTmcYICYg6eP8O52lt8GnBe8iuHSi5xiWrdQ.png" alt="Ajinsafro" class="h-6 w-auto object-contain bg-white px-2 py-0.5 rounded border border-slate-100">',
        '<img src="' . e($dashboardBrandLogo) . '" alt="' . e($dashboardBrandName) . '" class="h-6 w-auto object-contain bg-white px-2 py-0.5 rounded border border-slate-100">',
        $bodyHtml
    );

    $bodyHtml = str_replace(
        '<div class="text-xs text-slate-500 font-medium">Mardi 19 mai 2026</div>',
        '<div class="text-xs text-slate-500 font-medium">' . e($dashboardDateLabel) . '</div>',
        $bodyHtml
    );

    $bodyHtml = str_replace(
        '<p class="text-xs font-semibold text-slate-400 mt-1">Vue d\'ensemble de votre activité — mardi 19 mai 2026 - 09:53</p>',
        '<p class="text-xs font-semibold text-slate-400 mt-1">Vue d\'ensemble de votre activité — ' . e($dashboardDateTimeLabel) . '</p>',
        $bodyHtml
    );

    $bodyHtml = str_replace(
        '<span>Mardi 19 mai 2026</span>',
        '<span>' . e($dashboardDateLabel) . '</span>',
        $bodyHtml
    );

    $bodyHtml = str_replace(
        '<div class="text-xs font-semibold">Manager</div>
                            <div class="text-[10px] text-white/70">chef_commercial</div>',
        '<div class="text-xs font-semibold">' . e($dashboardUserName) . '</div>
                            <div class="text-[10px] text-white/70">' . e($dashboardUserRole) . '</div>',
        $bodyHtml
    );

    $bodyHtml = str_replace(
        '<div class="w-8 h-8 rounded-full bg-cyan-600 border border-white/30 flex items-center justify-center text-xs font-bold text-white shadow shrink-0">
                            MC
                        </div>',
        '<div class="w-8 h-8 rounded-full bg-cyan-600 border border-white/30 flex items-center justify-center text-xs font-bold text-white shadow shrink-0">' . e($dashboardInitials) . '</div>',
        $bodyHtml
    );

    $bodyHtml = str_replace(
        '<img src="https://booking.ajinsafro.net/storage/home-settings/header/6hZldTmcYICYg6eP8O52lt8GnBe8iuHSi5xiWrdQ.png" alt="Ajinsafro.ma" class="h-7 w-auto object-contain">',
        '<img src="' . e($dashboardBrandLogo) . '" alt="' . e($dashboardBrandName) . '" class="h-7 w-auto object-contain">',
        $bodyHtml
    );

    $dashboardV5 = $dashboardV5 ?? [];
    $stats = $dashboardV5['stats'] ?? [];
    $recentActivity = $dashboardV5['recentActivity'] ?? [];
    $reservationBreakdown = $dashboardV5['reservationBreakdown'] ?? [];
    $monthlyEvolution = $dashboardV5['monthlyEvolution'] ?? [];
    $paymentMethods = $dashboardV5['paymentMethods'] ?? [];
    $latestReservations = $dashboardV5['latestReservations'] ?? [];
    $topTours = $dashboardV5['topTours'] ?? [];
    $activeAgencies = $dashboardV5['activeAgencies'] ?? [];

    $currency = (string) ($stats['currency'] ?? 'DH');
    $currencySymbol = match (strtoupper($currency)) {
        'EUR' => '€',
        'USD' => '$',
        default => 'DH',
    };
    $formatCurrency = function (float $amount) use ($currencySymbol): string {
        return number_format($amount, 0, ',', ' ') . ' ' . $currencySymbol;
    };
    $formatPct = fn (float $value): string => ($value > 0 ? '+' : '') . number_format($value, 1, ',', ' ') . '%';
    $statusLabel = function (string $status): string {
        return match ($status) {
            \App\Models\Reservation::STATUS_PENDING, \App\Models\Reservation::STATUS_DRAFT, \App\Models\Reservation::STATUS_OPTION, \App\Models\Reservation::STATUS_EXPIRED => 'En cours',
            \App\Models\Reservation::STATUS_CONFIRMED, \App\Models\Reservation::STATUS_PARTIALLY_PAID, \App\Models\Reservation::STATUS_PAID => 'Validée',
            \App\Models\Reservation::STATUS_CANCELLED => 'Annulée',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    };
    $statusClass = function (string $status): string {
        return match ($status) {
            \App\Models\Reservation::STATUS_CONFIRMED, \App\Models\Reservation::STATUS_PARTIALLY_PAID, \App\Models\Reservation::STATUS_PAID => 'bg-emerald-50 text-emerald-600 border border-emerald-100',
            \App\Models\Reservation::STATUS_CANCELLED => 'bg-rose-50 text-rose-600 border border-rose-100',
            default => 'bg-amber-50 text-amber-600 border border-amber-100',
        };
    };

    $kpiReservationsTrend = (float) ($stats['reservations_evolution'] ?? 0.0);
    $kpiRevenueTrend = (float) ($stats['revenue_evolution'] ?? 0.0);
    $messagesCount = (int) ($stats['messages'] ?? 0);

    $kpiBlock = '
                <!-- FOUR KPI CARDS GRID (Glassmorphic & Exactly Identical in Size & Geometry) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                    <div class="bg-gradient-to-br from-[#0081bc]/90 to-[#005a84]/95 text-white p-5 rounded-2xl shadow-lg shadow-sky-500/10 border border-white/20 backdrop-blur-md relative overflow-hidden flex flex-col justify-between min-h-[150px] transition-all duration-300 hover:scale-[1.01] hover:shadow-xl">
                        <div class="flex justify-between items-start">
                            <div>
                                <span class="text-[10px] uppercase font-bold text-white/75 tracking-wider">Voyages</span>
                                <div class="text-2xl sm:text-3xl font-extrabold mt-1">' . number_format((int) ($stats['voyages'] ?? 0), 0, ',', ' ') . '</div>
                            </div>
                            <div class="p-2.5 bg-white/10 border border-white/10 rounded-xl text-white backdrop-blur-sm"><i data-lucide="plane" class="w-5 h-5"></i></div>
                        </div>
                        <div class="pt-2"><span class="inline-flex items-center space-x-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-white/10 text-white backdrop-blur-sm"><i data-lucide="check" class="w-3 h-3"></i><span>Catalogue actif</span></span></div>
                        <div class="pt-3 border-t border-white/10 flex items-center justify-between"><span class="text-[11px] text-white/80">Tous les voyages</span><i data-lucide="chevron-right" class="w-3.5 h-3.5 text-white/60"></i></div>
                    </div>
                    <div class="bg-gradient-to-br from-emerald-500/90 to-teal-600/95 text-white p-5 rounded-2xl shadow-lg shadow-emerald-500/10 border border-white/20 backdrop-blur-md relative overflow-hidden flex flex-col justify-between min-h-[150px] transition-all duration-300 hover:scale-[1.01] hover:shadow-xl">
                        <div class="flex justify-between items-start">
                            <div>
                                <span class="text-[10px] uppercase font-bold text-white/75 tracking-wider">Agences</span>
                                <div class="text-2xl sm:text-3xl font-extrabold mt-1">' . number_format((int) ($stats['agencies'] ?? 0), 0, ',', ' ') . '</div>
                            </div>
                            <div class="p-2.5 bg-white/10 border border-white/10 rounded-xl text-white backdrop-blur-sm"><i data-lucide="building-2" class="w-5 h-5"></i></div>
                        </div>
                        <div class="pt-2"><span class="inline-flex items-center space-x-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-white/10 text-white backdrop-blur-sm"><i data-lucide="shield-check" class="w-3 h-3"></i><span>Actives</span></span></div>
                        <div class="pt-3 border-t border-white/10 flex items-center justify-between"><span class="text-[11px] text-white/80">Points de vente actifs</span><i data-lucide="chevron-right" class="w-3.5 h-3.5 text-white/60"></i></div>
                    </div>
                    <div class="bg-gradient-to-br from-amber-500/90 to-orange-600/95 text-white p-5 rounded-2xl shadow-lg shadow-orange-500/10 border border-white/20 backdrop-blur-md relative overflow-hidden flex flex-col justify-between min-h-[150px] transition-all duration-300 hover:scale-[1.01] hover:shadow-xl">
                        <div class="flex justify-between items-start">
                            <div>
                                <span class="text-[10px] uppercase font-bold text-white/75 tracking-wider">Réservations</span>
                                <div class="text-2xl sm:text-3xl font-extrabold mt-1">' . number_format((int) ($stats['reservations'] ?? 0), 0, ',', ' ') . '</div>
                            </div>
                            <div class="p-2.5 bg-white/10 border border-white/10 rounded-xl text-white backdrop-blur-sm"><i data-lucide="calendar" class="w-5 h-5"></i></div>
                        </div>
                        <div class="pt-2"><span class="inline-flex items-center space-x-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-white/15 text-white backdrop-blur-sm"><i data-lucide="trending-up" class="w-3 h-3"></i><span>' . e($formatPct($kpiReservationsTrend)) . ' ce mois</span></span></div>
                        <div class="pt-3 border-t border-white/10 flex items-center justify-between"><span class="text-[11px] text-white/80">Total enregistré</span><i data-lucide="chevron-right" class="w-3.5 h-3.5 text-white/60"></i></div>
                    </div>
                    <div class="bg-gradient-to-br from-[#0096c7]/90 to-[#0077b6]/95 text-white p-5 rounded-2xl shadow-lg shadow-sky-600/10 border border-white/20 backdrop-blur-md relative overflow-hidden flex flex-col justify-between min-h-[150px] transition-all duration-300 hover:scale-[1.01] hover:shadow-xl">
                        <div class="flex justify-between items-start">
                            <div>
                                <span class="text-[10px] uppercase font-bold text-white/75 tracking-wider">Clients</span>
                                <div class="text-2xl sm:text-3xl font-extrabold mt-1">' . number_format((int) ($stats['clients'] ?? 0), 0, ',', ' ') . '</div>
                            </div>
                            <div class="p-2.5 bg-white/10 border border-white/10 rounded-xl text-white backdrop-blur-sm"><i data-lucide="users" class="w-5 h-5"></i></div>
                        </div>
                        <div class="pt-2"><span class="inline-flex items-center space-x-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-white/10 text-white backdrop-blur-sm"><i data-lucide="user-plus" class="w-3 h-3"></i><span>Base clients</span></span></div>
                        <div class="pt-3 border-t border-white/10 flex items-center justify-between"><span class="text-[11px] text-white/80">Clients enregistrés</span><i data-lucide="chevron-right" class="w-3.5 h-3.5 text-white/60"></i></div>
                    </div>
                </div>';
    $bodyHtml = preg_replace('/<!-- FOUR KPI CARDS GRID[\s\S]*?<!-- MIDDLE THREE PANEL PANELS/s', $kpiBlock . "\n\n                <!-- MIDDLE THREE PANEL PANELS", $bodyHtml, 1);

    $messagerieUrl = \Illuminate\Support\Facades\Route::has('admin.messagerie.index') ? route('admin.messagerie.index') : null;
    $middlePanelsBlock = '
                <!-- MIDDLE THREE PANEL PANELS (Activité récente, Chiffre d\'affaires, Messages) -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm flex flex-col justify-between overflow-hidden">
                        <div class="p-5">
                            <div class="flex items-center space-x-2 text-slate-700 font-bold text-sm mb-4"><i data-lucide="clock" class="w-4 h-4 text-blue-500"></i><span>Activité récente</span></div>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between py-1.5 border-b border-slate-50"><span class="text-xs text-slate-500 font-medium">Aujourd\'hui</span><span class="text-xs font-bold text-slate-800">' . number_format((int) ($recentActivity['today'] ?? 0), 0, ',', ' ') . ' résa</span></div>
                                <div class="flex items-center justify-between py-1.5 border-b border-slate-50"><span class="text-xs text-slate-500 font-medium">Cette semaine</span><span class="text-xs font-bold text-slate-800">' . number_format((int) ($recentActivity['week'] ?? 0), 0, ',', ' ') . ' résa</span></div>
                                <div class="flex items-center justify-between py-1.5"><span class="text-xs text-slate-500 font-medium">Ce mois</span><span class="text-xs font-bold text-slate-800">' . number_format((int) ($recentActivity['month'] ?? 0), 0, ',', ' ') . ' réservations</span></div>
                            </div>
                        </div>
                        <div class="bg-slate-50 px-5 py-3 border-t border-slate-100 flex items-center justify-between"><button class="text-[11px] text-[#0b548b] font-bold hover:underline flex items-center space-x-1"><span>Voir le détail</span></button><i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400"></i></div>
                    </div>
                    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm flex flex-col justify-between overflow-hidden">
                        <div class="p-5">
                            <div class="flex items-center space-x-2 text-slate-700 font-bold text-sm mb-3"><i data-lucide="wallet" class="w-4 h-4 text-emerald-500"></i><span>Chiffre d\'affaires</span></div>
                            <div><span class="text-xs text-slate-400 font-medium">Total validé</span><div class="text-2xl font-bold text-slate-800 mt-0.5">' . e($formatCurrency((float) ($stats['revenue_total'] ?? 0))) . '</div></div>
                            <div class="mt-2.5 flex items-center justify-between text-xs"><span class="text-slate-400 font-medium">Ce mois : <b class="text-slate-700 font-bold">' . e($formatCurrency((float) ($stats['revenue_month'] ?? 0))) . '</b></span><span class="text-emerald-600 font-bold bg-emerald-50 px-2 py-0.5 rounded flex items-center space-x-1"><i data-lucide="arrow-up-right" class="w-3 h-3"></i><span>' . e($formatPct($kpiRevenueTrend)) . ' vs mois dernier</span></span></div>
                        </div>
                        <div class="bg-slate-50 px-5 py-3 border-t border-slate-100 flex items-center justify-between"><button class="text-[11px] text-[#0b548b] font-bold hover:underline flex items-center space-x-1"><span>Voir le détail</span></button><i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400"></i></div>
                    </div>
                    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 flex flex-col justify-between relative overflow-hidden md:col-span-2 lg:col-span-1">
                        <div><div class="flex items-center space-x-2 text-slate-700 font-bold text-sm mb-4"><i data-lucide="mail" class="w-4 h-4 text-violet-500"></i><span>Messages</span></div><div><span class="text-xs text-slate-400 font-medium">Boîte Réservations</span><div class="text-3xl font-extrabold text-slate-800 mt-1">' . number_format($messagesCount, 0, ',', ' ') . '</div></div></div>
                        <div class="absolute right-6 top-10 opacity-10 text-slate-400"><i data-lucide="mail-open" class="w-20 h-20"></i></div>
                        <div class="pt-4 mt-2">' . ($messagerieUrl ? '<a href="' . e($messagerieUrl) . '" class="w-full bg-[#0b548b] hover:bg-[#0a4877] text-white text-xs font-semibold py-2.5 px-4 rounded-lg shadow-sm transition flex items-center justify-center space-x-1.5"><i data-lucide="external-link" class="w-3.5 h-3.5"></i><span>Ouvrir la messagerie</span></a>' : '<button type="button" disabled class="w-full bg-slate-200 text-slate-500 text-xs font-semibold py-2.5 px-4 rounded-lg shadow-sm cursor-not-allowed flex items-center justify-center space-x-1.5"><i data-lucide="external-link" class="w-3.5 h-3.5"></i><span>Messagerie indisponible</span></button>') . '</div>
                    </div>
                </div>';
    $bodyHtml = preg_replace('/<!-- MIDDLE THREE PANEL PANELS[\s\S]*?<!-- REPARTITION BAR BLOCK/s', $middlePanelsBlock . "\n\n                <!-- REPARTITION BAR BLOCK", $bodyHtml, 1);

    $repartitionBlock = '
                <!-- REPARTITION BAR BLOCK -->
                <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
                        <h3 class="font-bold text-slate-800 text-sm">Répartition des réservations</h3>
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs">
                            <div class="flex items-center space-x-1.5"><span class="w-2.5 h-2.5 rounded-full bg-amber-400 block"></span><span class="text-slate-500 font-medium">En attente : <b class="text-slate-700 font-bold">' . number_format((int) ($reservationBreakdown['pending'] ?? 0), 0, ',', ' ') . '</b></span></div>
                            <div class="flex items-center space-x-1.5"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500 block"></span><span class="text-slate-500 font-medium">Validées : <b class="text-slate-700 font-bold">' . number_format((int) ($reservationBreakdown['confirmed'] ?? 0), 0, ',', ' ') . '</b></span></div>
                            <div class="flex items-center space-x-1.5"><span class="w-2.5 h-2.5 rounded-full bg-rose-500 block"></span><span class="text-slate-500 font-medium">Annulées : <b class="text-slate-700 font-bold">' . number_format((int) ($reservationBreakdown['cancelled'] ?? 0), 0, ',', ' ') . '</b></span></div>
                            <div class="border-l border-slate-200 pl-3 flex items-center space-x-1 font-medium"><span class="text-slate-400">Total :</span><span class="font-bold text-slate-800">' . number_format((int) ($reservationBreakdown['total'] ?? 0), 0, ',', ' ') . '</span></div>
                        </div>
                    </div>
                    <div class="w-full h-3.5 bg-slate-100 rounded-full flex overflow-hidden">
                        <div class="h-full bg-amber-400 transition-all duration-500" style="width: ' . (($reservationBreakdown['pending_pct'] ?? 0) ?: 0) . '%;" title="En attente"></div>
                        <div class="h-full bg-emerald-500 transition-all duration-500" style="width: ' . (($reservationBreakdown['confirmed_pct'] ?? 0) ?: 0) . '%;" title="Validées"></div>
                        <div class="h-full bg-rose-500 transition-all duration-500" style="width: ' . (($reservationBreakdown['cancelled_pct'] ?? 0) ?: 0) . '%;" title="Annulées"></div>
                        <div class="h-full bg-slate-200 transition-all duration-500" style="width: ' . (($reservationBreakdown['other_pct'] ?? 0) ?: 0) . '%;" title="Autres"></div>
                    </div>
                </div>';
    $bodyHtml = preg_replace('/<!-- REPARTITION BAR BLOCK[\s\S]*?<!-- CHARTS SECTION/s', $repartitionBlock . "\n\n                <!-- CHARTS SECTION", $bodyHtml, 1);

    $maxReservations = max(1, ...array_map(fn ($row) => (int) ($row['reservations'] ?? 0), $monthlyEvolution ?: [['reservations' => 0]]));
    $maxRevenue = max(1.0, ...array_map(fn ($row) => (float) ($row['revenue'] ?? 0), $monthlyEvolution ?: [['revenue' => 0]]));
    $barsHtml = '';
    $labelsHtml = '';
    foreach ($monthlyEvolution as $row) {
        $reservationsVal = (int) ($row['reservations'] ?? 0);
        $barHeight = max(0, min(100, round(($reservationsVal / $maxReservations) * 100)));
        $labelsHtml .= '<span class="w-12 text-center truncate">' . e((string) ($row['label'] ?? '')) . '</span>';
        $barsHtml .= '<div class="flex flex-col items-center justify-end h-full w-8 sm:w-12 group"><div class="text-[10px] font-bold text-blue-600 mb-1 ' . ($reservationsVal > 0 ? 'opacity-100' : 'opacity-0 group-hover:opacity-100') . ' transition-opacity">' . $reservationsVal . '</div><div class="w-full bg-[#0c6ebb] rounded-t-md group-hover:bg-blue-500 transition-all duration-300" style="height: ' . $barHeight . '%;"></div></div>';
    }
    $donutTotal = max(0, (int) ($reservationBreakdown['total'] ?? 0));
    $pPending = (float) ($reservationBreakdown['pending_pct'] ?? 0.0);
    $pConfirmed = (float) ($reservationBreakdown['confirmed_pct'] ?? 0.0);
    $pCancelled = (float) ($reservationBreakdown['cancelled_pct'] ?? 0.0);
    $chartsBlock = '
                <!-- CHARTS SECTION: Dual Axis Evolution & Doughnut Chart -->
                <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                    <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm xl:col-span-2 flex flex-col justify-between overflow-hidden">
                        <div>
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-6"><div><h3 class="font-bold text-slate-800 text-sm">Évolution des réservations & du chiffre d\'affaires</h3><p class="text-[11px] text-slate-400 mt-0.5 font-medium">Aperçu interactif des performances</p></div><span class="self-start sm:self-center bg-blue-50 text-[#0b548b] text-[10px] font-bold px-2.5 py-1 rounded-full border border-blue-100">6 derniers mois</span></div>
                            <div class="flex justify-end space-x-4 mb-4 text-xs font-semibold"><div class="flex items-center space-x-1.5"><span class="w-3.5 h-3.5 rounded bg-blue-600 block"></span><span class="text-slate-500">Réservations</span></div><div class="flex items-center space-x-1.5"><span class="w-3.5 h-1.5 rounded-full bg-amber-500 block"></span><span class="text-slate-500">Chiffre d\'affaires (' . e($currencySymbol) . ')</span></div></div>
                            <div class="relative w-full h-64 mt-2">
                                <div class="absolute inset-x-0 top-0 h-full flex flex-col justify-between pointer-events-none"><div class="border-b border-dashed border-slate-100 w-full h-0"></div><div class="border-b border-dashed border-slate-100 w-full h-0"></div><div class="border-b border-dashed border-slate-100 w-full h-0"></div><div class="border-b border-dashed border-slate-100 w-full h-0"></div><div class="border-b border-slate-100 w-full h-0"></div></div>
                                <div class="absolute left-0 top-0 h-full flex flex-col justify-between text-[10px] font-bold text-slate-400 pb-6 pr-2"><span>' . $maxReservations . '</span><span>' . (int) round($maxReservations * 0.75) . '</span><span>' . (int) round($maxReservations * 0.5) . '</span><span>' . (int) round($maxReservations * 0.25) . '</span><span>0</span></div>
                                <div class="absolute right-0 top-0 h-full flex flex-col justify-between text-[10px] font-bold text-slate-400 pb-6 pl-2 text-right"><span>' . number_format($maxRevenue, 0, ',', ' ') . '</span><span>' . number_format($maxRevenue * 0.75, 0, ',', ' ') . '</span><span>' . number_format($maxRevenue * 0.5, 0, ',', ' ') . '</span><span>' . number_format($maxRevenue * 0.25, 0, ',', ' ') . '</span><span>0</span></div>
                                <div class="absolute inset-x-0 top-0 h-full flex justify-between px-8 sm:px-12 pb-6 pt-2 z-10">' . $barsHtml . '</div>
                            </div>
                            <div class="flex justify-between px-6 sm:px-10 text-[10px] font-bold text-slate-400 border-t border-slate-100 pt-2">' . $labelsHtml . '</div>
                        </div>
                    </div>
                    <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex flex-col justify-between">
                        <div><h3 class="font-bold text-slate-800 text-sm mb-1">Statut des réservations</h3><p class="text-[11px] text-slate-400 mb-6 font-medium">Proportions d\'états récents</p>
                            <div class="relative flex justify-center items-center my-6"><svg class="w-36 h-36 sm:w-40 sm:h-40 transform -rotate-90" viewBox="0 0 36 36"><circle cx="18" cy="18" r="15.915" fill="none" stroke="#f1f5f9" stroke-width="4.5"></circle><circle cx="18" cy="18" r="15.915" fill="none" stroke="#f59e0b" stroke-width="4.5" stroke-dasharray="' . $pPending . ' ' . (100 - $pPending) . '" stroke-dashoffset="0"></circle><circle cx="18" cy="18" r="15.915" fill="none" stroke="#10b981" stroke-width="4.5" stroke-dasharray="' . $pConfirmed . ' ' . (100 - $pConfirmed) . '" stroke-dashoffset="-' . $pPending . '"></circle><circle cx="18" cy="18" r="15.915" fill="none" stroke="#ef4444" stroke-width="4.5" stroke-dasharray="' . $pCancelled . ' ' . (100 - $pCancelled) . '" stroke-dashoffset="-'. ($pPending + $pConfirmed) .'"></circle></svg><div class="absolute text-center"><span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Total</span><div class="text-2xl sm:text-3xl font-extrabold text-slate-800">' . number_format($donutTotal, 0, ',', ' ') . '</div></div></div>
                        </div>
                        <div class="border-t border-slate-50 pt-4 flex items-center justify-around text-[10px] sm:text-[11px] font-bold text-slate-500"><div class="flex items-center space-x-1"><span class="w-2 h-2 rounded-full bg-amber-500 block"></span><span>En cours</span></div><div class="flex items-center space-x-1"><span class="w-2 h-2 rounded-full bg-emerald-500 block"></span><span>Validés</span></div><div class="flex items-center space-x-1"><span class="w-2 h-2 rounded-full bg-rose-500 block"></span><span>Annulés</span></div></div>
                    </div>
                </div>';
    $bodyHtml = preg_replace('/<!-- CHARTS SECTION[\s\S]*?<!-- LOWER GRID/s', $chartsBlock . "\n\n                <!-- LOWER GRID", $bodyHtml, 1);

    $paymentsRows = '';
    foreach (array_slice($paymentMethods, 0, 5) as $method) {
        $paymentsRows .= '<div><div class="flex justify-between items-center text-xs mb-1"><span class="text-slate-500 font-semibold">' . e((string) ($method['label'] ?? 'Non renseigne')) . '</span><span class="font-bold text-slate-800">' . number_format((int) ($method['count'] ?? 0), 0, ',', ' ') . '</span></div><div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden"><div class="bg-[#0b548b] h-full rounded-full" style="width: ' . max(0.0, min(100.0, (float) ($method['percent'] ?? 0.0))) . '%;"></div></div></div>';
    }
    if ($paymentsRows === '') {
        $paymentsRows = '<div class="text-xs text-slate-400">Aucune donnée de paiement disponible.</div>';
    }

    $latestRows = '';
    foreach ($latestReservations as $reservation) {
        $detailUrl = null;
        if (!empty($reservation['dossier_id']) && \Illuminate\Support\Facades\Route::has('admin.reservation-dossiers.show')) {
            $detailUrl = route('admin.reservation-dossiers.show', $reservation['dossier_id']);
        } elseif (\Illuminate\Support\Facades\Route::has('admin.reservations.show')) {
            $detailUrl = route('admin.reservations.show', $reservation['id']);
        }

        $latestRows .= '<tr class="hover:bg-slate-50/50 transition-colors"><td class="py-2.5 px-3 font-bold text-slate-400">' . e((string) $reservation['id']) . '</td><td class="py-2.5 px-3"><div class="font-bold text-slate-800">' . e((string) $reservation['client_name']) . '</div><div class="text-[10px] text-slate-400 font-semibold">' . e((string) ($reservation['client_email'] ?: '-')) . '</div></td><td class="py-2.5 px-3 truncate max-w-[150px]" title="' . e((string) ($reservation['tour_name'] ?: '-')) . '">' . e((string) ($reservation['tour_name'] ?: '-')) . '</td><td class="py-2.5 px-3"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold ' . e($statusClass((string) ($reservation['status'] ?? ''))) . '">' . e($statusLabel((string) ($reservation['status'] ?? ''))) . '</span></td><td class="py-2.5 px-3"><span class="font-bold text-slate-700">' . e((string) ($reservation['payment'] ?: '-')) . '</span></td><td class="py-2.5 px-3">' . e($formatCurrency((float) ($reservation['amount'] ?? 0.0))) . '</td><td class="py-2.5 px-3 text-[11px] text-slate-500 whitespace-nowrap">' . e((string) ($reservation['date'] ?? '')) . '</td><td class="py-2.5 px-3 text-right">' . ($detailUrl ? '<a href="' . e($detailUrl) . '" class="text-slate-400 hover:text-slate-600 p-1 rounded hover:bg-slate-100 inline-flex"><i data-lucide="eye" class="w-3.5 h-3.5"></i></a>' : '<button type="button" disabled class="text-slate-300 p-1 rounded inline-flex cursor-not-allowed"><i data-lucide="eye" class="w-3.5 h-3.5"></i></button>') . '</td></tr>';
    }
    if ($latestRows === '') {
        $latestRows = '<tr><td colspan="8" class="py-5 px-3 text-center text-xs text-slate-400">Aucune réservation disponible.</td></tr>';
    }

    $lowerGridBlock = '
                <!-- LOWER GRID: Paiements Validés & Dernières Réservations -->
                <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                    <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex flex-col justify-between">
                        <div><div class="flex justify-between items-center mb-6"><h3 class="font-bold text-slate-800 text-sm">Paiements validés</h3><span class="text-[10px] text-slate-400 uppercase font-bold">Méthodes</span></div><div class="space-y-4">' . $paymentsRows . '</div></div>
                        <div class="border-t border-slate-100 pt-4 mt-6 flex items-center justify-between"><button class="text-[11px] text-[#0b548b] font-bold hover:underline flex items-center space-x-1"><span>Voir le détail</span></button><i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400"></i></div>
                    </div>
                    <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm xl:col-span-2 overflow-hidden flex flex-col justify-between">
                        <div><div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4"><div><h3 class="font-bold text-slate-800 text-sm">Dernières réservations</h3><p class="text-[11px] text-slate-400 mt-0.5 font-medium">Historique et états de paiements des derniers inscrits</p></div><a href="' . e(route('admin.reservations.index')) . '" class="self-start sm:self-center text-[11px] bg-slate-50 hover:bg-slate-100 text-slate-600 px-3 py-1 rounded-md border border-slate-200 flex items-center space-x-1 font-semibold"><span>Voir toutes</span><i data-lucide="arrow-up-right" class="w-3 h-3"></i></a></div><div class="overflow-x-auto w-full border border-slate-50 rounded-lg"><table class="w-full text-left text-xs text-slate-600 border-collapse min-w-[750px]"><thead><tr class="bg-slate-50/50 border-b border-slate-100 text-[10px] text-slate-400 uppercase tracking-wider"><th class="py-3 px-3 font-bold">#</th><th class="py-3 px-3 font-bold">Client</th><th class="py-3 px-3 font-bold">Voyage</th><th class="py-3 px-3 font-bold">Statut</th><th class="py-3 px-3 font-bold">Paiement</th><th class="py-3 px-3 font-bold">Montant</th><th class="py-3 px-3 font-bold">Date</th><th class="py-3 px-3 font-bold text-right">Actions</th></tr></thead><tbody class="divide-y divide-slate-50 font-medium">' . $latestRows . '</tbody></table></div></div>
                    </div>
                </div>';
    $bodyHtml = preg_replace('/<!-- LOWER GRID[\s\S]*?<!-- TWO LAST PANELS/s', $lowerGridBlock . "\n\n                <!-- TWO LAST PANELS", $bodyHtml, 1);

    $topToursRows = '';
    foreach ($topTours as $tour) {
        $tourReservationsUrl = route('admin.reservations.index', ['tour_id' => $tour['id']]);
        $topToursRows .= '<a href="' . e($tourReservationsUrl) . '" class="flex items-center justify-between p-2.5 bg-slate-50 rounded-lg hover:bg-slate-100/70 transition-colors"><div class="flex items-center space-x-3 overflow-hidden"><div class="w-8 h-8 rounded-lg bg-[#e8f4fc] flex items-center justify-center shrink-0"><i data-lucide="map-pin" class="w-4 h-4 text-[#0b548b]"></i></div><span class="text-xs font-semibold text-slate-700 truncate" title="' . e((string) $tour['name']) . '">' . e((string) $tour['name']) . '</span></div><span class="text-xs font-bold text-[#0b548b] bg-blue-50 px-2 py-1 rounded-md shrink-0">' . number_format((int) $tour['count'], 0, ',', ' ') . '</span></a>';
    }
    if ($topToursRows === '') {
        $topToursRows = '<div class="text-xs text-slate-400">Aucun voyage réservé.</div>';
    }

    $agenciesRows = '';
    foreach ($activeAgencies as $agency) {
        $agenciesRows .= '<div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg"><div class="flex items-center space-x-3"><div class="w-9 h-9 rounded-lg bg-emerald-50 flex items-center justify-center shrink-0 border border-emerald-100"><i data-lucide="building-2" class="w-5 h-5 text-emerald-600"></i></div><div><div class="text-xs font-bold text-slate-800">' . e((string) $agency['name']) . '</div><div class="text-[10px] text-slate-400 font-semibold">' . e(trim((string) (($agency['city'] ?: '') . (($agency['code'] ?? '') ? ' - ' . $agency['code'] : '')))) . ' • ' . number_format((int) ($agency['reservations_count'] ?? 0), 0, ',', ' ') . ' résa</div></div></div><span class="w-2 h-2 rounded-full bg-emerald-500 ring-4 ring-emerald-50"></span></div>';
    }
    if ($agenciesRows === '') {
        $agenciesRows = '<div class="text-xs text-slate-400">Aucune agence active.</div>';
    }

    $lastPanelsBlock = '
                <!-- TWO LAST PANELS: Voyages les plus réservés & Agences actives -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex flex-col justify-between"><div><div class="flex justify-between items-center mb-4"><h3 class="font-bold text-slate-800 text-sm">Voyages les plus réservés</h3><a href="' . e(route('admin.reservations.index')) . '" class="text-[11px] text-[#0b548b] font-bold hover:underline">Voir tous</a></div><div class="space-y-3">' . $topToursRows . '</div></div></div>
                    <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex flex-col justify-between"><div><div class="flex justify-between items-center mb-4"><h3 class="font-bold text-slate-800 text-sm">Agences actives</h3><a href="' . e(route('admin.points-of-sale.index')) . '" class="text-[11px] text-[#0b548b] font-bold hover:underline">Voir toutes</a></div><div class="space-y-3">' . $agenciesRows . '</div></div></div>
                </div>';
    $bodyHtml = preg_replace('/<!-- TWO LAST PANELS[\s\S]*?<\/main>/s', $lastPanelsBlock . "\n\n            </main>", $bodyHtml, 1);
?>

<?php $__env->startPush('styles'); ?>
<style>
    body {
        font-family: 'Montserrat', sans-serif;
    }
    ::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }
    ::-webkit-scrollbar-track {
        background: #f1f5f9;
    }
    ::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 3px;
    }
    ::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
    .sidebar-item {
        transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .sidebar-item:hover {
        transform: translateX(4px);
    }
    @media (min-width: 1024px) {
        .dashboard-v5-page .dashboard-v5-sidebar {
            width: 16rem !important;
            min-width: 16rem !important;
            max-width: 16rem !important;
            transition: width .25s ease;
        }
        .dashboard-v5-page .dashboard-v5-main {
            transition: width .25s ease;
        }
        .dashboard-v5-page.is-sidebar-collapsed .dashboard-v5-sidebar {
            width: 78px !important;
            min-width: 78px !important;
            max-width: 78px !important;
        }
        .dashboard-v5-page.is-sidebar-collapsed .dashboard-v5-sidebar .dashboard-v5-logo-text,
        .dashboard-v5-page.is-sidebar-collapsed .dashboard-v5-sidebar .dashboard-v5-profile-info,
        .dashboard-v5-page.is-sidebar-collapsed .dashboard-v5-sidebar .dashboard-v5-nav-label,
        .dashboard-v5-page.is-sidebar-collapsed .dashboard-v5-sidebar .dashboard-v5-nav-caret,
        .dashboard-v5-page.is-sidebar-collapsed .dashboard-v5-sidebar .dashboard-v5-submenu,
        .dashboard-v5-page.is-sidebar-collapsed .dashboard-v5-sidebar .sidebar-item > span:last-child,
        .dashboard-v5-page.is-sidebar-collapsed .dashboard-v5-sidebar .dashboard-v5-desktop-profile {
            display: none !important;
        }
        .dashboard-v5-page.is-sidebar-collapsed .dashboard-v5-sidebar nav > .space-y-1 > button {
            justify-content: center;
            padding-left: .5rem;
            padding-right: .5rem;
        }
        .dashboard-v5-page.is-sidebar-collapsed .dashboard-v5-sidebar nav .sidebar-item,
        .dashboard-v5-page.is-sidebar-collapsed .dashboard-v5-sidebar nav > a {
            justify-content: center;
        }
        .dashboard-v5-page.is-sidebar-collapsed .dashboard-v5-sidebar .h-14 {
            justify-content: center !important;
            gap: .5rem;
        }
        .dashboard-v5-page.is-sidebar-collapsed .dashboard-v5-sidebar-toggle [data-lucide="panel-left-close"] {
            display: none;
        }
        .dashboard-v5-page.is-sidebar-collapsed .dashboard-v5-sidebar-toggle::before {
            content: ">";
            font-size: 14px;
            font-weight: 700;
            color: #475569;
        }
    }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<?php echo $bodyHtml; ?>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const page = document.getElementById('dashboardV5Page');
    const toggle = document.getElementById('dashboardV5SidebarToggle');
    const key = 'aj-dashboard-v5-sidebar-collapsed';

    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    if (page && toggle && localStorage.getItem(key) === '1') {
        page.classList.add('is-sidebar-collapsed');
    }

    if (page && toggle) {
        toggle.addEventListener('click', function () {
            page.classList.toggle('is-sidebar-collapsed');
            localStorage.setItem(
                key,
                page.classList.contains('is-sidebar-collapsed') ? '1' : '0'
            );
        });
    }

    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const closeMobileMenuBtn = document.getElementById('closeMobileMenuBtn');
    const mobileSidebar = document.getElementById('mobileSidebar');
    const mobileSidebarOverlay = document.getElementById('mobileSidebarOverlay');
    const mobileMenuContainer = document.getElementById('mobileMenuContainer');
    const desktopSidebarNav = document.querySelector('aside nav');

    if (desktopSidebarNav && mobileMenuContainer) {
        mobileMenuContainer.innerHTML = desktopSidebarNav.innerHTML;
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }

    window.toggleDropdown = function (menuId, arrowId) {
        const menu = document.getElementById(menuId);
        const arrow = document.getElementById(arrowId);

        if (!menu || !arrow) {
            return;
        }

        if (menu.classList.contains('hidden')) {
            menu.classList.remove('hidden');
            arrow.classList.add('rotate-180');
        } else {
            menu.classList.add('hidden');
            arrow.classList.remove('rotate-180');
        }
    };

    const toggleMobileMenu = function (isOpen) {
        if (!mobileSidebar || !mobileSidebarOverlay) {
            return;
        }

        if (isOpen) {
            mobileSidebarOverlay.classList.remove('hidden');
            setTimeout(function () {
                mobileSidebarOverlay.classList.add('opacity-100');
            }, 10);
            mobileSidebar.classList.remove('-translate-x-full');
        } else {
            mobileSidebarOverlay.classList.remove('opacity-100');
            mobileSidebarOverlay.classList.add('hidden');
            mobileSidebar.classList.add('-translate-x-full');
        }
    };

    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', function () { toggleMobileMenu(true); });
    }
    if (closeMobileMenuBtn) {
        closeMobileMenuBtn.addEventListener('click', function () { toggleMobileMenu(false); });
    }
    if (mobileSidebarOverlay) {
        mobileSidebarOverlay.addEventListener('click', function () { toggleMobileMenu(false); });
    }
});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.dashboard-v5', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\dashboard\v5\index.blade.php ENDPATH**/ ?>