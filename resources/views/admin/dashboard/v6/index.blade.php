@extends('layouts.dashboard-v5')

@section('title', 'Dashboard V6')

@php
    $dashboardUser = auth()->user();
    $dashboardUserName = $dashboardUser?->name ?? 'Admin';
    $dashboardUserRole = $dashboardUser?->getRoleNames()->first() ?? 'Administrateur';
    $dashboardInitials = strtoupper(collect(preg_split('/\s+/', trim((string) $dashboardUserName)))->filter()->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->implode(''));
    if ($dashboardInitials === '') { $dashboardInitials = 'AD'; }
    $dashboardBrandName = \App\Models\Setting::getValue('brand_name', 'Ajinsafro');
    $dashboardBrandLogo = \App\Models\Setting::brandLogoUrl('dark');
    $dashboardDateLabel = now('Africa/Casablanca')->locale('fr')->translatedFormat('l d F Y');

    $source = file_get_contents(resource_path('views/admin/dashboard/dashboard_v6_ajinsafro_kpi_v4.html'));
    preg_match('/<style>([\s\S]*?)<\/style>/i', $source, $styleMatch);
    preg_match('/<body[^>]*>([\s\S]*?)<\/body>/i', $source, $bodyMatch);
    $v6Css = trim($styleMatch[1] ?? '');
    $v6Body = $bodyMatch[1] ?? '';

    $v6Body = preg_replace('/<script>[\s\S]*?<\/script>\s*$/i', '', $v6Body);
    $v6SidebarHtml = view('admin.partials.sidebar-v2', ['sidebarContext' => 'dashboard-v6'])->render();
    $v6AsideReplacement = '<aside class="sidebar dashboard-v6-sidebar" aria-label="Navigation Ajinsafro">'
        . '<button class="sidebar-toggle" type="button" id="sidebarToggle" title="Ouvrir / fermer le menu" aria-label="Ouvrir ou fermer le menu">'
        . '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg>'
        . '</button>'
        . $v6SidebarHtml
        . '</aside>';
    $v6Body = preg_replace('/<aside class="sidebar"[\s\S]*?<\/aside>/i', $v6AsideReplacement, $v6Body, 1);

    $v6Body = str_replace(
        '<div class="brand-mark" aria-hidden="true">▰</div>',
        '<div class="brand-mark" aria-hidden="true"><img src="' . e($dashboardBrandLogo) . '" alt="' . e($dashboardBrandName) . '" style="width:28px;height:28px;object-fit:contain;filter:brightness(0) invert(1)"></div>',
        $v6Body
    );
    $v6Body = str_replace('<strong>AjinSafro.ma</strong>', '<strong>' . e($dashboardBrandName) . '</strong>', $v6Body);
    $v6Body = str_replace('<div class="avatar">A</div>', '<div class="avatar">' . e($dashboardInitials) . '</div>', $v6Body);
    $v6Body = str_replace('<strong>Admin</strong>', '<strong>' . e($dashboardUserName) . '</strong>', $v6Body);
    $v6Body = str_replace('<span>Administrateur</span>', '<span>' . e($dashboardUserRole) . '</span>', $v6Body);
    $v6Body = str_replace('<h1>Dashboard V6</h1>', '<h1>Dashboard V6</h1>', $v6Body);
    $v6Body = str_replace('📅 mardi 19 mai 2026', '📅 ' . e($dashboardDateLabel), $v6Body);
    $v6Body = str_replace('<button class="primary-btn" type="button">+ Réservations</button>', '<a class="primary-btn" href="' . e(route('admin.reservations.create')) . '">+ Réservations</a>', $v6Body);
    $v6Body = str_replace('<a href="#" class="active">Dashboard V6</a>', '<a href="' . e(route('admin.dashboard.v6')) . '" class="active">Dashboard V6</a>', $v6Body);
    $v6Body = str_replace('<a href="#">Dashboard V5</a>', '<a href="' . e(route('admin.dashboard.v5')) . '">Dashboard V5</a>', $v6Body);
    $v6Body = str_replace('<a href="#">Dashboard V4</a>', '<a href="' . e(route('admin.dashboard.v4')) . '">Dashboard V4</a>', $v6Body);
    $v6Body = str_replace('<a href="#">Dashboard V3</a>', '<a href="' . e(route('admin.dashboard.v3')) . '">Dashboard V3</a>', $v6Body);
    $v6Body = str_replace('<a href="#">Dashboard V2</a>', '<a href="' . e(route('admin.dashboard.v2')) . '">Dashboard V2</a>', $v6Body);
@endphp

@push('styles')
<link href="{{ asset('build/css/icons.min.css') }}" rel="stylesheet">
<link href="{{ asset('css/admin-sidebar-v2.css') }}" rel="stylesheet">
<style>
{{ $v6Css }}

/* Dashboard V6 Laravel fixes: stable collapse + 1280/1366 responsive density. */
.app-shell {
  height: 100vh;
  overflow: hidden;
}

.main {
  height: 100vh;
  overflow-y: auto;
  overflow-x: hidden;
}

html[data-sidebar="collapsed"] .sidebar,
html[data-sidebar="collapsed"] .sidebar:hover {
  width: var(--sidebar-closed) !important;
}

html[data-sidebar="collapsed"] .main,
html[data-sidebar="collapsed"] .sidebar:hover ~ .main {
  margin-left: var(--sidebar-closed) !important;
}

html[data-sidebar="collapsed"] .dashboard-v6-sidebar .aj-sidebar-v2__label,
html[data-sidebar="collapsed"] .dashboard-v6-sidebar .aj-sidebar-v2__section-title,
html[data-sidebar="collapsed"] .dashboard-v6-sidebar .aj-sidebar-v2__profile,
html[data-sidebar="collapsed"] .dashboard-v6-sidebar .aj-sidebar-v2__account,
html[data-sidebar="collapsed"] .dashboard-v6-sidebar .aj-sidebar-v2__submenu,
html[data-sidebar="collapsed"] .dashboard-v6-sidebar .aj-sidebar-v2__badge,
html[data-sidebar="collapsed"] .dashboard-v6-sidebar .aj-sidebar-v2__chevron {
  display: none !important;
}

html[data-sidebar="collapsed"] .dashboard-v6-sidebar .aj-sidebar-v2__brand,
html[data-sidebar="collapsed"] .dashboard-v6-sidebar .aj-sidebar-v2__link,
html[data-sidebar="collapsed"] .dashboard-v6-sidebar .aj-sidebar-v2__toggle {
  justify-content: center !important;
  padding-left: 0 !important;
  padding-right: 0 !important;
  min-width: 0 !important;
}

html[data-sidebar="collapsed"] .dashboard-v6-sidebar .aj-sidebar-v2 {
  padding-left: 0.35rem !important;
  padding-right: 0.35rem !important;
}

html[data-sidebar="collapsed"] .dashboard-v6-sidebar .aj-sidebar-v2__list--depth-1,
html[data-sidebar="collapsed"] .dashboard-v6-sidebar .aj-sidebar-v2__list--depth-2,
html[data-sidebar="collapsed"] .dashboard-v6-sidebar .aj-sidebar-v2__list--depth-3 {
  display: none !important;
}

html[data-sidebar="collapsed"] .dashboard-v6-sidebar .aj-sidebar-v2__item {
  display: block !important;
}

html[data-sidebar="collapsed"] .dashboard-v6-sidebar .aj-sidebar-v2__item.has-direct-link .aj-sidebar-v2__toggle {
  display: none !important;
}

html[data-sidebar="collapsed"] .dashboard-v6-sidebar .aj-sidebar-v2__item.has-direct-link .aj-sidebar-v2__link--parent {
  width: 44px !important;
  margin: 0 auto !important;
}

html[data-sidebar="collapsed"] .dashboard-v6-sidebar .aj-sidebar-v2__link,
html[data-sidebar="collapsed"] .dashboard-v6-sidebar .aj-sidebar-v2__toggle {
  min-height: 44px !important;
  height: 44px !important;
  width: 44px !important;
  margin: 0 auto !important;
  border-radius: 12px !important;
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
}

html[data-sidebar="collapsed"] .dashboard-v6-sidebar .aj-sidebar-v2__link-group {
  display: block !important;
  text-align: center !important;
}

html[data-sidebar="collapsed"] .dashboard-v6-sidebar .aj-sidebar-v2__icon {
  width: 1.35rem !important;
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
  margin: 0 !important;
  font-size: 1.15rem !important;
  opacity: 1 !important;
  visibility: visible !important;
}

html[data-sidebar="collapsed"] .dashboard-v6-sidebar .aj-sidebar-v2__icon i {
  display: inline-block !important;
  font-size: 1.15rem !important;
  line-height: 1 !important;
}

html[data-sidebar="collapsed"] .dashboard-v6-sidebar .aj-sidebar-v2__brand-link {
  justify-content: center !important;
}

html[data-sidebar="collapsed"] .sidebar .sidebar-toggle {
  right: 20px !important;
}

html[data-sidebar="collapsed"] .dashboard-v6-sidebar .aj-sidebar-v2__brand {
  border-bottom: 0 !important;
  padding-bottom: 0 !important;
  margin-bottom: 10px !important;
}

html[data-sidebar="collapsed"] .dashboard-v6-sidebar .aj-sidebar-v2__brand-logo {
  height: 38px !important;
  max-width: 42px !important;
}

.dashboard-v6-sidebar .aj-sidebar-v2 {
  min-height: 100%;
  padding-top: 1.1rem;
}

.dashboard-v6-sidebar .aj-sidebar-v2__brand-logo {
  filter: none;
  height: 40px !important;
  max-width: 170px !important;
  object-fit: contain;
}

.dashboard-v6-sidebar .aj-sidebar-v2__profile,
.dashboard-v6-sidebar .aj-sidebar-v2__link,
.dashboard-v6-sidebar .aj-sidebar-v2__item.is-active > .aj-sidebar-v2__link,
.dashboard-v6-sidebar .aj-sidebar-v2__item.is-open > .aj-sidebar-v2__link {
  border-radius: 14px;
}

.dashboard-v6-sidebar .aj-sidebar-v2__item.is-active > .aj-sidebar-v2__link,
.dashboard-v6-sidebar .aj-sidebar-v2__item.is-open > .aj-sidebar-v2__link,
.dashboard-v6-sidebar .aj-sidebar-v2__item.is-active > .aj-sidebar-v2__link-group > .aj-sidebar-v2__link,
.dashboard-v6-sidebar .aj-sidebar-v2__item.is-open > .aj-sidebar-v2__link-group > .aj-sidebar-v2__link {
  box-shadow: inset 3px 0 0 #18a9dc;
}

html[data-sidebar="collapsed"] .dashboard-v6-sidebar .aj-sidebar-v2__item.is-active > .aj-sidebar-v2__link,
html[data-sidebar="collapsed"] .dashboard-v6-sidebar .aj-sidebar-v2__item.is-open > .aj-sidebar-v2__link,
html[data-sidebar="collapsed"] .dashboard-v6-sidebar .aj-sidebar-v2__item.is-active > .aj-sidebar-v2__link-group > .aj-sidebar-v2__link,
html[data-sidebar="collapsed"] .dashboard-v6-sidebar .aj-sidebar-v2__item.is-open > .aj-sidebar-v2__link-group > .aj-sidebar-v2__link {
  box-shadow: none !important;
  background: rgba(24, 169, 220, .16) !important;
  border: 1px solid rgba(15, 93, 141, .2) !important;
}

.topbar {
  overflow: visible;
}

.kpi-card {
  grid-template-columns: 1fr 76px !important;
  gap: 10px !important;
}

.kpi-left {
  gap: 10px !important;
}

.kpi-icon {
  width: 44px !important;
  height: 44px !important;
  flex-basis: 44px !important;
  border-radius: 14px !important;
}

.kpi-sparkline {
  width: 76px !important;
}

.kpi-title,
.kpi-note {
  white-space: normal !important;
  overflow: visible !important;
  text-overflow: clip !important;
}

@media (max-width: 1380px) {
  .topbar {
    grid-template-columns: minmax(160px, 220px) minmax(220px, 1fr) auto !important;
    gap: 12px !important;
    padding: 0 16px !important;
  }

  .top-actions {
    gap: 7px !important;
  }

  .chip,
  .primary-btn {
    padding: 0 10px !important;
  }

  .kpi-grid {
    grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
    gap: 12px !important;
  }

  .kpi-card {
    padding: 14px !important;
    min-height: 112px !important;
  }

  .dashboard-grid,
  .dashboard-grid.middle,
  .dashboard-grid.bottom {
    grid-template-columns: 1fr 1fr !important;
  }
}

@media (max-width: 1180px) {
  .kpi-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
  }

  .dashboard-grid,
  .dashboard-grid.middle,
  .dashboard-grid.bottom {
    grid-template-columns: 1fr !important;
  }
}

@media (max-width: 760px) {
  html[data-sidebar="expanded"] .sidebar {
    width: var(--sidebar-open) !important;
    transform: translateX(0) !important;
  }

  html[data-sidebar="collapsed"] .sidebar,
  html[data-sidebar="collapsed"] .sidebar:hover {
    width: 0 !important;
    transform: translateX(-100%) !important;
  }
}
</style>
@endpush

@section('content')
{!! $v6Body !!}
@endsection

@push('scripts')
<script src="{{ asset('js/admin-sidebar-v2.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.lucide && typeof window.lucide.createIcons === 'function') {
        window.lucide.createIcons();
    }

    const root = document.documentElement;
    const saved = localStorage.getItem('aj-v6-sidebar');
    if (saved === 'collapsed' || saved === 'expanded') {
        root.dataset.sidebar = saved;
    } else if (!root.dataset.sidebar) {
        root.dataset.sidebar = 'expanded';
    }

    const toggle = function () {
        const next = root.dataset.sidebar === 'collapsed' ? 'expanded' : 'collapsed';
        root.dataset.sidebar = next;
        localStorage.setItem('aj-v6-sidebar', next);
    };

    document.getElementById('sidebarToggle')?.addEventListener('click', toggle);
    document.getElementById('mobileToggle')?.addEventListener('click', toggle);

    if (window.AjSidebarV2 && typeof window.AjSidebarV2.init === 'function') {
        window.AjSidebarV2.init();
    }

    const sidebarRoot = document.querySelector('.dashboard-v6-sidebar [data-aj-sidebar-v2]');
    if (sidebarRoot) {
        sidebarRoot.addEventListener('click', function (event) {
            const target = event.target.closest('.aj-sidebar-v2__link, .aj-sidebar-v2__toggle');
            if (!target || root.dataset.sidebar !== 'collapsed') return;
            const isLeafLink = target.matches('a.aj-sidebar-v2__link') && target.getAttribute('href') && target.getAttribute('href') !== 'javascript:void(0);';
            if (isLeafLink) return;
            root.dataset.sidebar = 'expanded';
            localStorage.setItem('aj-v6-sidebar', 'expanded');
        });
    }
});
</script>
@endpush
