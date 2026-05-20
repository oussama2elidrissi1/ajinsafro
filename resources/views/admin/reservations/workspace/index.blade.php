@php
    use App\Services\View\AgentPortalLayout;
    use Carbon\Carbon;
    $usePortalTailwind = AgentPortalLayout::shouldUse(auth()->user());
    $useLegacyWorkspaceMarkup = false;
    $workspaceView = $workspaceView ?? 'catalog';
    $workspaceFilters = $workspaceFilters ?? [
        'search' => '',
        'type' => '',
        'destination' => '',
        'date_from' => '',
        'date_to' => '',
        'budget_min' => null,
        'budget_max' => null,
    ];
    $workspaceFilterOptions = $workspaceFilterOptions ?? ['destinations' => []];
    $workspaceRouteName = $workspaceRouteName ?? (request()->routeIs('admin.vente.catalogue') ? 'admin.vente.catalogue' : 'admin.reservations.workspace');
    $workspaceFormUrl = $workspaceFormUrl ?? route($workspaceRouteName);
    $workspaceResetUrl = $workspaceResetUrl ?? route($workspaceRouteName, ['view' => 'catalog']);
    $catalogRows = $workspaceSellableRows ?? $catalogRows;

    $workspaceCalendarEvents = $catalogRows->flatMap(function ($r) {
        $type = (string) ($r['type'] ?? 'package');
        $tourId = (int) ($r['voyage_id'] ?? 0);
        $code = (string) ($r['code'] ?? '');
        if ($tourId <= 0 || $code === '') {
            return [];
        }

        $name = trim((string) ($r['name'] ?? ''));
        $shortTitle = $name;
        if (function_exists('mb_strlen') && mb_strlen($shortTitle) > 28) {
            $shortTitle = mb_substr($shortTitle, 0, 26).'…';
        }

        $destination = trim((string) ($r['voyage_destination'] ?? data_get($r, 'modal_detail.destination', '')));
        $priceLabel = trim((string) ($r['price_label'] ?? data_get($r, 'modal_detail.prices.adult_label', '')));
        $departures = collect(data_get($r, 'modal_detail.departures', []))
            ->filter(fn ($departure) => is_array($departure) && ! empty($departure['date_iso']))
            ->values();

        if ($departures->isNotEmpty()) {
            return $departures->map(function (array $departure) use ($type, $tourId, $code, $name, $shortTitle, $destination, $priceLabel) {
                $confirmed = (int) data_get($departure, 'pax.validee', 0);
                $pending = (int) data_get($departure, 'pax.en_cours', 0);
                $remaining = data_get($departure, 'remaining');
                $capacity = data_get($departure, 'capacity');

                return [
                    'title' => $name,
                    'short_title' => $shortTitle,
                    'destination' => $destination,
                    'departure_date' => (string) $departure['date_iso'],
                    'start' => (string) $departure['date_iso'],
                    'type' => $type,
                    'code' => $code,
                    'tour_id' => $tourId,
                    'voyage_id' => $tourId,
                    'travel_date_id' => $departure['travel_date_id'] ?? '',
                    'prestation_type' => $type,
                    'price' => $priceLabel,
                    'confirmed_places' => $confirmed,
                    'pending_places' => $pending,
                    'remaining_places' => $remaining,
                    'capacity' => $capacity,
                    'status' => $departure['status_key'] ?? 'unknown',
                    'status_label' => $departure['status_label'] ?? 'Disponible',
                    'is_past' => ! empty($departure['is_past']),
                    'routes' => $departure['routes'] ?? [],
                    'label' => $name.' ('.$code.')',
                ];
            })->all();
        }

        if (empty($r['departure_date'])) {
            return [];
        }

        $status = ! empty($r['departure_is_past']) ? 'past' : ((string) ($r['ws_avail'] ?? 'unknown'));
        $stats = (array) ($r['stats'] ?? []);

        return [[
            'title' => $name,
            'short_title' => $shortTitle,
            'destination' => $destination,
            'departure_date' => Carbon::parse($r['departure_date'])->format('Y-m-d'),
            'start' => Carbon::parse($r['departure_date'])->format('Y-m-d'),
            'type' => $type,
            'code' => $code,
            'tour_id' => $tourId,
            'voyage_id' => $tourId,
            'travel_date_id' => $r['travel_date_id'] ?? '',
            'prestation_type' => $type,
            'price' => $priceLabel,
            'confirmed_places' => (int) ($stats['validee'] ?? 0),
            'pending_places' => (int) ($stats['en_cours'] ?? 0),
            'remaining_places' => null,
            'capacity' => null,
            'status' => $status,
            'status_label' => $status === 'past' ? 'Passé' : 'À configurer',
            'is_past' => ! empty($r['departure_is_past']),
            'routes' => [
                'reserve' => !empty($r['voyage_id'])
                    ? route('admin.reservations.create', array_filter([
                        'voyage_id' => (int) $r['voyage_id'],
                        'travel_date_id' => $r['travel_date_id'] ?? null,
                    ], fn ($value) => $value !== null && $value !== ''))
                    : route('admin.reservations.create'),
            ],
            'label' => $name.' ('.$code.')',
        ]];
    })->values()->all();
    $workspaceCalendarSeedDate = $workspaceFilters['date_from']
        ?? (collect($workspaceCalendarEvents)->pluck('departure_date')->filter()->sort()->first() ?: Carbon::today()->format('Y-m-d'));
@endphp
@extends('layouts.admin-v6')

@section('title', 'Catalogue des Voyages & Départs')
@section('page_title', 'Catalogue des Voyages & Départs')

@php
    $breadcrumbs = [
        ['label' => 'Accueil', 'url' => \Illuminate\Support\Facades\Route::has('admin.dashboard.v6') ? route('admin.dashboard.v6') : route('admin.dashboard')],
        ['label' => 'Vente / Catalogue'],
    ];
@endphp

@push('styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    {{-- TODO: remplacer par un build Tailwind local (Vite/PostCSS) avant mise en production --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Poppins', 'sans-serif'] },
                    colors: {
                        brand: {
                            dark: '#0e3a5a',
                            blue: '#0083c4',
                            light: '#e6f3fa',
                            orange: '#f37a1f',
                            yellow: '#ffb300',
                            gray: '#f7f9fc',
                        }
                    },
                    boxShadow: {
                        custom: '0 4px 24px rgba(14,58,90,0.06)',
                        'ws-bar': '0 8px 32px rgba(0,131,196,0.08)',
                    },
                }
            }
        };
    </script>
<link rel="stylesheet" href="{{ asset('css/reservation-workspace.css') }}?v=workspace-fixed-v7">
<style>
    .ws-ring-pulse { animation: wsPulse 1.6s ease-out 1; }
    @keyframes wsPulse {
        0% { box-shadow: 0 0 0 0 rgba(0, 131, 196, 0.45); }
        100% { box-shadow: 0 0 0 12px rgba(0, 131, 196, 0); }
    }
    .ws-catalog-section { position: relative; }
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .line-clamp-3 {
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    @media (min-width: 640px) {
        .sm\:line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    }
    .ws-catalog-grid .ws-room-badge { max-width: 100%; }
    .ws-catalog-grid--compact { align-items: stretch; }
    .ws-offer-card--compact { height: 100%; }
    .ws-offer-card__body--compact {
        display: flex;
        flex-direction: column;
        gap: 0.85rem;
        height: 100%;
    }
    .ws-offer-card__refs {
        margin: -0.2rem 0 0;
        font-size: 0.76rem;
        color: #64748b;
        font-weight: 700;
    }
    .ws-offer-card__section-label {
        font-size: 0.72rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #0e3a5a;
        margin-bottom: 0.45rem;
    }
    .ws-offer-card__departures {
        padding: 0.85rem 0.95rem;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        background: #f8fafc;
    }
    .ws-offer-card__departure-list {
        list-style: none;
        margin: 0;
        padding: 0;
        display: grid;
        gap: 0.45rem;
    }
    .ws-offer-card__departure-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
    }
    .ws-offer-card__departure-item--solo { justify-content: flex-start; }
    .ws-offer-card__departure-date {
        font-size: 0.88rem;
        font-weight: 700;
        color: #1e293b;
        line-height: 1.35;
    }
    .ws-offer-card__departure-status {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.2rem 0.55rem;
        border-radius: 9999px;
        font-size: 0.68rem;
        font-weight: 800;
        white-space: nowrap;
    }
    .ws-offer-card__departure-status--ok { background: #dcfce7; color: #166534; }
    .ws-offer-card__departure-status--warn { background: #fef3c7; color: #92400e; }
    .ws-offer-card__departure-status--full,
    .ws-offer-card__departure-status--muted { background: #e2e8f0; color: #475569; }
    .ws-offer-card__more {
        border: 0;
        background: transparent;
        padding: 0;
        margin-top: 0.6rem;
        color: #0083c4;
        font-size: 0.8rem;
        font-weight: 800;
        text-align: left;
        cursor: pointer;
    }
    .ws-offer-card__actions--compact {
        margin-top: auto;
        display: flex;
        justify-content: center;
        align-items: center;
        padding-top: 16px;
    }
    .ws-offer-card__actions--compact > * { min-width: 0; }
    .ws-offer-card__actions--compact .btn-view {
        min-width: 180px;
        height: 46px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    .ws-md-selector-list {
        display: grid;
        gap: 0.9rem;
    }
    .ws-md-departure-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 0.6rem;
        margin-bottom: 1rem;
    }
    .ws-md-departure-tab {
        border: 1px solid #dbe4ee;
        background: #fff;
        border-radius: 14px;
        padding: 0.75rem 0.9rem;
        min-width: 180px;
        cursor: pointer;
        transition: border-color 0.15s ease, box-shadow 0.15s ease, transform 0.15s ease;
        text-align: left;
    }
    .ws-md-departure-tab:hover {
        border-color: #94c8e7;
        box-shadow: 0 8px 24px rgba(14, 58, 90, 0.08);
        transform: translateY(-1px);
    }
    .ws-md-departure-tab.is-active {
        border-color: #0083c4;
        background: #f0f9ff;
        box-shadow: 0 0 0 1px rgba(0, 131, 196, 0.12);
    }
    .ws-md-departure-tab-date {
        display: block;
        font-size: 0.9rem;
        font-weight: 800;
        color: #0f172a;
    }
    .ws-md-departure-tab-status {
        display: inline-flex;
        margin-top: 0.35rem;
    }
    .ws-md-detail-panel {
        display: grid;
        gap: 1rem;
    }
    .ws-md-footer {
        position: sticky;
        bottom: 0;
        background: #fff;
        z-index: 2;
    }
    .ws-md-footer .ws-md-btn,
    .ws-md-footer-actions .ws-md-btn {
        min-height: 42px;
        max-height: 46px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.45rem;
    }
    .ws-md-selector-card {
        border: 1px solid #dbe4ee;
        border-radius: 14px;
        padding: 1rem;
        background: #fff;
    }
    .ws-md-selector-card-head,
    .ws-md-selector-meta,
    .ws-md-selector-actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        flex-wrap: wrap;
    }
    .ws-md-selector-date {
        font-size: 1rem;
        font-weight: 800;
        color: #0f172a;
    }
    .ws-md-selector-meta { margin-top: 0.8rem; }
    .ws-md-selector-kpi {
        min-width: 112px;
        border-radius: 12px;
        background: #f8fafc;
        padding: 0.7rem 0.8rem;
    }
    .ws-md-selector-kpi span {
        display: block;
        font-size: 0.68rem;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        font-weight: 700;
    }
    .ws-md-selector-kpi strong {
        display: block;
        margin-top: 0.2rem;
        font-size: 0.95rem;
        color: #0f172a;
    }
    .ws-md-departure-kpi-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.75rem;
        margin-bottom: 0.9rem;
    }
    @media (min-width: 768px) {
        .ws-md-departure-kpi-grid {
            grid-template-columns: repeat(6, minmax(0, 1fr));
        }
    }
    .ws-md-departure-kpi {
        position: relative;
        overflow: hidden;
        border-radius: 16px;
        padding: 0.9rem;
        border: 1px solid #e2e8f0;
        background: linear-gradient(180deg, #fff 0%, #f8fafc 100%);
        min-height: 104px;
    }
    .ws-md-departure-kpi i {
        color: rgba(14, 58, 90, 0.25);
        font-size: 1.05rem;
    }
    .ws-md-departure-kpi span {
        display: block;
        margin-top: 0.6rem;
        color: #64748b;
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.05em;
        text-transform: uppercase;
    }
    .ws-md-departure-kpi strong {
        display: block;
        margin-top: 0.15rem;
        color: #0f172a;
        font-size: 1.55rem;
        font-weight: 900;
        font-variant-numeric: tabular-nums;
    }
    .ws-md-departure-kpi--ok { border-color: #bbf7d0; background: linear-gradient(180deg, #f0fdf4, #fff); }
    .ws-md-departure-kpi--ok i { color: #16a34a; }
    .ws-md-departure-kpi--wait { border-color: #fed7aa; background: linear-gradient(180deg, #fff7ed, #fff); }
    .ws-md-departure-kpi--wait i { color: #f37a1f; }
    .ws-md-departure-kpi--cancel { border-color: #fecaca; background: linear-gradient(180deg, #fef2f2, #fff); }
    .ws-md-departure-kpi--cancel i { color: #ef4444; }
    .ws-md-departure-kpi--remain { border-color: #bae6fd; background: linear-gradient(180deg, #f0f9ff, #fff); }
    .ws-md-departure-kpi--remain i { color: #0083c4; }
    .ws-md-departure-kpi--neutral { border-color: #e2e8f0; background: linear-gradient(180deg, #f8fafc, #fff); }
    .ws-md-departure-kpi--neutral i { color: #64748b; }
    .ws-md-departure-kpi--rate-ok { border-color: #bbf7d0; background: linear-gradient(180deg, #f0fdf4, #fff); }
    .ws-md-departure-kpi--rate-ok i { color: #16a34a; }
    .ws-md-departure-kpi--rate-warn { border-color: #fed7aa; background: linear-gradient(180deg, #fff7ed, #fff); }
    .ws-md-departure-kpi--rate-warn i { color: #f37a1f; }
    .ws-md-departure-kpi--rate-full { border-color: #fecaca; background: linear-gradient(180deg, #fef2f2, #fff); }
    .ws-md-departure-kpi--rate-full i { color: #ef4444; }
    .ws-md-departure-kpi--rate-low { border-color: #e2e8f0; background: linear-gradient(180deg, #f8fafc, #fff); }
    .ws-md-departure-kpi--rate-low i { color: #64748b; }
    .ws-md-departure-info-grid {
        display: grid;
        grid-template-columns: repeat(1, minmax(0, 1fr));
        gap: 0.75rem;
    }
    @media (min-width: 640px) {
        .ws-md-departure-info-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    .ws-md-departure-info {
        border-radius: 14px;
        border: 1px solid #e8ecf1;
        background: #f8fafc;
        padding: 0.85rem;
    }
    .ws-md-departure-info span,
    .ws-md-commission-card span {
        display: block;
        color: #64748b;
        font-size: 0.7rem;
        font-weight: 800;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }
    .ws-md-departure-info strong,
    .ws-md-commission-card strong {
        display: block;
        margin-top: 0.25rem;
        color: #0f172a;
        font-weight: 850;
    }
    .ws-md-commission-card {
        border-radius: 16px;
        border: 1px solid rgba(243, 122, 31, 0.25);
        background: linear-gradient(135deg, #fff7ed 0%, #ffffff 62%);
        padding: 1rem;
    }
    .ws-md-commission-card p {
        margin: 0.3rem 0 0;
        color: #9a3412;
        font-size: 0.85rem;
        font-weight: 700;
    }
    .ws-md-report-card {
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        background: #fff;
        padding: 1rem;
    }
    .ws-md-report-header {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.75rem;
    }
    .ws-md-report-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.35rem 0.7rem;
        border-radius: 999px;
        font-size: 0.8rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.02em;
    }
    .ws-md-report-badge--neutral { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
    .ws-md-report-badge--info { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
    .ws-md-report-badge--warn { background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; }
    .ws-md-report-badge--danger { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
    .ws-md-report-list {
        margin: 0.5rem 0 0;
        padding: 0;
        list-style: none;
    }
    .ws-md-report-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.4rem 0;
        font-size: 0.82rem;
        font-weight: 700;
        border-bottom: 1px solid #f1f5f9;
    }
    .ws-md-report-item:last-child { border-bottom: none; }
    .ws-md-report-item--neutral { color: #475569; }
    .ws-md-report-item--info { color: #0369a1; }
    .ws-md-report-item--warn { color: #c2410c; }
    .ws-md-report-item--danger { color: #b91c1c; }
    .ws-md-report-recos {
        margin-top: 0.75rem;
        padding: 0.75rem;
        border-radius: 12px;
        background: #f0f9ff;
        border: 1px solid #bae6fd;
    }
    .ws-md-report-recos strong {
        display: block;
        font-size: 0.78rem;
        font-weight: 900;
        color: #0e3a5a;
        margin-bottom: 0.35rem;
    }
    .ws-md-report-recos ul {
        margin: 0;
        padding-left: 1.1rem;
        font-size: 0.78rem;
        font-weight: 700;
        color: #334155;
    }
    .ws-md-report-recos li { margin: 0.15rem 0; }
    .ws-md-selector-actions { margin-top: 0.9rem; }
    .ws-md-selector-actions .ws-md-btn { margin: 0; }
    .ws-md-inline-note {
        margin: 0.35rem 0 0;
        font-size: 0.78rem;
        color: #64748b;
    }
    @media (max-width: 640px) {
        .ws-offer-card__departure-item,
        .ws-md-selector-card-head,
        .ws-md-selector-meta,
        .ws-md-selector-actions {
            align-items: flex-start;
            flex-direction: column;
        }
        .ws-md-selector-kpi { width: 100%; }
        .ws-offer-card__actions--compact {
            grid-template-columns: 1fr;
        }
        .ws-md-departure-tab {
            width: 100%;
            min-width: 0;
        }
    }

    /* —— Modal détail voyage (workspace) — racine en fin de <body>, hors layout — */
    #ws-modal-root {
        position: static;
    }
    #ws-voyage-detail-modal.ws-md-root {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        bottom: 0 !important;
        width: 100% !important;
        max-width: none !important;
        height: 100% !important;
        max-height: none !important;
        min-height: 100vh !important;
        min-height: 100dvh !important;
        margin: 0 !important;
        padding: 1rem;
        box-sizing: border-box;
        z-index: 99999 !important;
        isolation: isolate;
        display: none;
        align-items: center;
        justify-content: center;
        pointer-events: none;
    }
    #ws-voyage-detail-modal.ws-md-root:not(.hidden) {
        display: flex !important;
        pointer-events: auto;
    }
    .ws-md-overlay {
        position: absolute;
        inset: 0;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
        z-index: 0;
    }
    .ws-md-shell {
        position: relative;
        z-index: 1;
        width: 100%;
        max-width: 900px;
        max-height: min(94vh, 900px);
        max-height: min(94dvh, 900px);
        display: flex;
        flex-direction: column;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2), 0 0 0 1px rgba(255, 255, 255, 0.06) inset;
        overflow: hidden;
        transform: scale(0.96);
        opacity: 0;
        transition: transform 0.25s cubic-bezier(0.34, 1.2, 0.64, 1), opacity 0.22s ease;
    }
    #ws-voyage-detail-modal.ws-md-visible .ws-md-shell {
        transform: scale(1);
        opacity: 1;
    }
    #ws-voyage-detail-modal.ws-md-leaving .ws-md-shell {
        transform: scale(0.98);
        opacity: 0;
    }
    .ws-md-header {
        flex-shrink: 0;
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #e8ecf1;
        background: linear-gradient(180deg, #fafbfc 0%, #fff 100%);
    }
    .ws-md-header-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
    }
    .ws-md-title {
        font-size: 1.125rem;
        font-weight: 800;
        color: #0e3a5a;
        line-height: 1.35;
        margin: 0;
        letter-spacing: -0.02em;
    }
    .ws-md-meta {
        margin-top: 0.5rem;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem 0.75rem;
        font-size: 0.75rem;
        color: #64748b;
        font-family: ui-monospace, monospace;
    }
    .ws-md-badge-status {
        display: inline-flex;
        align-items: center;
        padding: 0.2rem 0.55rem;
        border-radius: 9999px;
        font-size: 0.65rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        background: #e6f3fa;
        color: #0083c4;
        border: 1px solid rgba(0, 131, 196, 0.2);
    }
    .ws-md-close {
        flex-shrink: 0;
        width: 2.5rem;
        height: 2.5rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        background: #fff;
        color: #64748b;
        cursor: pointer;
        transition: background 0.15s, color 0.15s, border-color 0.15s;
    }
    .ws-md-close:hover {
        background: #f1f5f9;
        color: #0e3a5a;
        border-color: #cbd5e1;
    }
    .ws-md-body {
        flex: 1;
        min-height: 0;
        overflow-y: auto;
        overflow-x: hidden;
        padding: 1.25rem 1.5rem 1.5rem;
        background: #f8fafc;
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 #f1f5f9;
    }
    .ws-md-body::-webkit-scrollbar {
        width: 8px;
    }
    .ws-md-body::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 8px;
    }
    .ws-md-body::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 8px;
    }
    .ws-md-body-inner {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    .ws-md-card {
        background: #fff;
        border: 1px solid #e8ecf1;
        border-radius: 12px;
        padding: 1rem 1.15rem;
        box-shadow: 0 1px 3px rgba(14, 58, 90, 0.04);
    }
    .ws-md-section-head {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.75rem;
        font-size: 0.7rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #64748b;
    }
    .ws-md-section-head i {
        color: #0083c4;
        font-size: 0.85rem;
    }
    .ws-md-dl {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0.65rem 1rem;
        font-size: 0.875rem;
    }
    @media (min-width: 640px) {
        .ws-md-dl {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    .ws-md-dl dt {
        color: #94a3b8;
        font-weight: 600;
        font-size: 0.75rem;
    }
    .ws-md-dl dd {
        margin: 0;
        font-weight: 700;
        color: #0f172a;
    }
    .ws-md-date-pills {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    .ws-md-date-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.45rem 0.75rem;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        font-size: 0.8125rem;
        font-weight: 600;
        color: #1e293b;
    }
    .ws-md-date-pill .ws-md-tag {
        font-size: 0.6rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        padding: 0.15rem 0.45rem;
        border-radius: 6px;
    }
    .ws-md-tag-past {
        background: #f1f5f9;
        color: #475569;
    }
    .ws-md-tag-future {
        background: #d1fae5;
        color: #065f46;
    }
    .ws-md-places-row {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.75rem;
        margin-bottom: 0.75rem;
    }
    .ws-md-stat-box {
        padding: 0.65rem 0.75rem;
        border-radius: 10px;
        background: #f8fafc;
        border: 1px solid #e8ecf1;
        text-align: center;
    }
    .ws-md-stat-box span {
        display: block;
        font-size: 0.65rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #94a3b8;
        margin-bottom: 0.25rem;
    }
    .ws-md-stat-box strong {
        font-size: 1.125rem;
        font-weight: 800;
        color: #0e3a5a;
        font-variant-numeric: tabular-nums;
    }
    .ws-md-progress {
        height: 10px;
        border-radius: 9999px;
        background: #e2e8f0;
        overflow: hidden;
        max-width: 100%;
    }
    .ws-md-progress-bar {
        height: 100%;
        border-radius: 9999px;
        background: linear-gradient(90deg, #0083c4, #0e3a5a);
        transition: width 0.35s ease;
    }
    .ws-md-room-pills {
        display: flex;
        flex-wrap: wrap;
        gap: 0.45rem;
    }
    .ws-md-room-pill {
        display: inline-flex;
        align-items: center;
        padding: 0.35rem 0.65rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 700;
        color: #334155;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
    }
    .ws-md-price-main {
        font-size: 1.25rem;
        font-weight: 800;
        color: #0e3a5a;
    }
    .ws-md-stats-row {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    .ws-md-stat-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.4rem 0.65rem;
        border-radius: 10px;
        font-size: 0.75rem;
        font-weight: 800;
        border: 1px solid transparent;
    }
    .ws-md-stat-pill.ok {
        background: #ecfdf5;
        color: #047857;
        border-color: #a7f3d0;
    }
    .ws-md-stat-pill.wait {
        background: #fffbeb;
        color: #b45309;
        border-color: #fde68a;
    }
    .ws-md-stat-pill.cancel {
        background: #fef2f2;
        color: #b91c1c;
        border-color: #fecaca;
    }
    .ws-md-dep-hint {
        font-size: 0.75rem;
        color: #64748b;
        margin: 0 0 0.85rem;
        line-height: 1.45;
    }
    .ws-md-departure-list {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    .ws-md-departure-card {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 0.85rem 1rem;
        background: linear-gradient(180deg, #fafbfc 0%, #fff 100%);
        box-shadow: 0 1px 2px rgba(14, 58, 90, 0.04);
    }
    .ws-md-departure-card--past {
        opacity: 0.9;
    }
    .ws-md-departure-card-head {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.5rem 0.75rem;
        margin-bottom: 0.6rem;
    }
    .ws-md-departure-date {
        font-size: 0.95rem;
        font-weight: 800;
        color: #0e3a5a;
        letter-spacing: -0.02em;
    }
    .ws-md-departure-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem;
        justify-content: flex-end;
    }
    .ws-md-departure-actions a {
        font-size: 0.7rem;
        font-weight: 700;
        padding: 0.35rem 0.55rem;
        border-radius: 8px;
        text-decoration: none;
        border: 1px solid #0f766e;
        background: #0f766e;
        color: #fff;
        transition: background 0.15s, border-color 0.15s, color 0.15s;
    }
    .ws-md-departure-actions a:hover {
        border-color: #115e59;
        color: #fff;
        background: #115e59;
    }
    .ws-md-departure-actions a.ws-md-dep-primary {
        border-color: #2563eb;
        background: #2563eb;
        color: #fff;
    }
    .ws-md-departure-actions a.ws-md-dep-primary:hover {
        background: #1d4ed8;
        border-color: #1d4ed8;
        color: #fff;
    }
    .ws-md-avail-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.2rem 0.55rem;
        border-radius: 9999px;
        font-size: 0.65rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border: 1px solid transparent;
    }
    .ws-md-avail-badge--ok {
        background: #ecfdf5;
        color: #047857;
        border-color: #a7f3d0;
    }
    .ws-md-avail-badge--warn {
        background: #fffbeb;
        color: #b45309;
        border-color: #fde68a;
    }
    .ws-md-avail-badge--full {
        background: #fef2f2;
        color: #b91c1c;
        border-color: #fecaca;
    }
    .ws-md-avail-badge--unknown {
        background: #f1f5f9;
        color: #475569;
        border-color: #e2e8f0;
    }
    .ws-md-departure-kpis {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0.35rem;
        font-size: 0.8125rem;
    }
    @media (min-width: 520px) {
        .ws-md-departure-kpis {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    .ws-md-dep-kpi {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        gap: 0.5rem;
        padding: 0.28rem 0;
        border-bottom: 1px dashed #f1f5f9;
    }
    .ws-md-dep-kpi:last-child {
        border-bottom: none;
    }
    .ws-md-dep-kpi span {
        color: #94a3b8;
        font-weight: 600;
        font-size: 0.72rem;
    }
    .ws-md-dep-kpi strong {
        font-weight: 800;
        color: #0f172a;
        font-variant-numeric: tabular-nums;
    }
    .ws-md-progress--dep {
        margin-top: 0.55rem;
    }
    .ws-md-progress-bar--dep-warn {
        background: linear-gradient(90deg, #f59e0b, #d97706);
    }
    .ws-md-progress-bar--dep-full {
        background: linear-gradient(90deg, #ef4444, #b91c1c);
    }
    .ws-md-footer {
        flex-shrink: 0;
        padding: 1rem 1.5rem;
        border-top: 1px solid #e8ecf1;
        background: #fff;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    @media (min-width: 640px) {
        .ws-md-footer {
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
        }
    }
    .ws-md-footer-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        justify-content: flex-end;
        align-items: center;
    }
    .ws-md-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.45rem;
        padding: 0.6rem 1rem;
        border-radius: 10px;
        font-size: 0.8125rem;
        font-weight: 700;
        cursor: pointer;
        border: 1px solid transparent;
        text-decoration: none;
        transition: background 0.15s, color 0.15s, border-color 0.15s, transform 0.12s;
        white-space: nowrap;
    }
    .ws-md-btn:active {
        transform: scale(0.98);
    }
    .ws-md-btn:focus-visible {
        outline: none;
        box-shadow: 0 0 0 4px rgba(0, 131, 196, 0.18);
    }
    .ws-md-btn-primary {
        background: #0e3a5a;
        color: #fff;
        border-color: #0e3a5a;
    }
    .ws-md-btn-primary i,
    .ws-md-btn-primary .fas {
        color: #fff !important;
    }
    .ws-md-btn-primary:hover {
        background: #0083c4;
        border-color: #0083c4;
        color: #fff;
    }
    .ws-md-btn-primary:hover i,
    .ws-md-btn-primary:hover .fas {
        color: #fff !important;
    }
    .ws-md-btn-success {
        background: #059669;
        color: #fff;
        border-color: #059669;
    }
    .ws-md-btn-success i,
    .ws-md-btn-success .fas {
        color: #fff !important;
    }
    .ws-md-btn-success:hover {
        background: #047857;
        border-color: #047857;
        color: #fff;
    }
    .ws-md-btn-success:hover i,
    .ws-md-btn-success:hover .fas {
        color: #fff !important;
    }
    .ws-md-btn-secondary {
        background: #fff;
        color: #475569;
        border-color: #e2e8f0;
    }
    .ws-md-btn-secondary:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
        color: #0f172a;
    }
    .ws-md-btn-outline {
        background: #fff;
        color: #0083c4;
        border-color: rgba(0, 131, 196, 0.35);
    }
    .ws-md-btn-outline:hover {
        background: #e6f3fa;
        border-color: #0083c4;
    }
    .ws-md-btn-disabled {
        background: #f1f5f9;
        color: #475569;
        border-color: #cbd5e1;
        cursor: not-allowed;
        pointer-events: none;
        opacity: 1;
    }
    @media (max-width: 640px) {
        .ws-md-footer .ws-md-btn,
        .ws-md-footer-actions,
        .ws-md-footer-actions .ws-md-btn {
            width: 100%;
        }
    }
    body.ws-md-open {
        overflow: hidden !important;
    }

    /* —— Boutons du footer modal : forcer texte blanc lisible — */
    #ws-voyage-detail-modal .ws-md-footer .ws-md-btn-primary,
    #ws-voyage-detail-modal .ws-md-footer a.ws-md-btn-primary,
    #ws-voyage-detail-modal .ws-md-footer .ws-md-btn-primary *,
    #ws-voyage-detail-modal .ws-md-footer .ws-md-btn-success,
    #ws-voyage-detail-modal .ws-md-footer a.ws-md-btn-success,
    #ws-voyage-detail-modal .ws-md-footer .ws-md-btn-success * {
        color: #ffffff !important;
    }

    #ws-voyage-detail-modal .ws-md-footer .ws-md-btn-primary {
        background: #0e3a5a !important;
        border-color: #0e3a5a !important;
    }

    #ws-voyage-detail-modal .ws-md-footer .ws-md-btn-primary:hover {
        background: #0083c4 !important;
        border-color: #0083c4 !important;
        color: #ffffff !important;
    }

    #ws-voyage-detail-modal .ws-md-footer .ws-md-btn-success {
        background: #059669 !important;
        border-color: #059669 !important;
    }

    #ws-voyage-detail-modal .ws-md-footer .ws-md-btn-success:hover {
        background: #047857 !important;
        border-color: #047857 !important;
        color: #ffffff !important;
    }

    /* Workspace fixed table v7: loaded after reservation-workspace.css to guarantee the cascade */
    body.aj-admin-compact,
    body.aj-admin-compact .page-content,
    body.aj-admin-compact .main-content {
        overflow-x: hidden !important;
    }

    body.aj-admin-compact .workspace-table-fixed {
        width: 100% !important;
        max-width: 100% !important;
        overflow: hidden !important;
    }

    body.aj-admin-compact .workspace-table-fixed-table {
        width: 100% !important;
        max-width: 100% !important;
        min-width: 0 !important;
        table-layout: fixed !important;
        border-collapse: collapse !important;
    }

    body.aj-admin-compact .workspace-table-fixed-table .col-ref { width: 6%; }
    body.aj-admin-compact .workspace-table-fixed-table .col-voyage { width: 28%; }
    body.aj-admin-compact .workspace-table-fixed-table .col-destination { width: 13%; }
    body.aj-admin-compact .workspace-table-fixed-table .col-depart { width: 10%; }
    body.aj-admin-compact .workspace-table-fixed-table .col-sold { width: 10%; }
    body.aj-admin-compact .workspace-table-fixed-table .col-restant { width: 7%; }
    body.aj-admin-compact .workspace-table-fixed-table .col-capacite { width: 13%; }
    body.aj-admin-compact .workspace-table-fixed-table .col-actions { width: 13%; }

    body.aj-admin-compact .workspace-table-fixed-table th,
    body.aj-admin-compact .workspace-table-fixed-table td {
        box-sizing: border-box !important;
        padding: 4px 5px !important;
        font-size: 10.5px !important;
        line-height: 1.2 !important;
        vertical-align: middle !important;
        overflow: hidden !important;
    }

    body.aj-admin-compact .workspace-table-fixed-table th:nth-child(2),
    body.aj-admin-compact .workspace-table-fixed-table td:nth-child(2),
    body.aj-admin-compact .workspace-table-fixed-table td:nth-child(2) * {
        white-space: normal !important;
        overflow: visible !important;
        text-overflow: unset !important;
        word-break: break-word !important;
        overflow-wrap: anywhere !important;
        max-width: 100% !important;
        min-width: 0 !important;
    }

    body.aj-admin-compact .workspace-table-fixed-table td:nth-child(2) .ws-td__offer-compact {
        display: flex !important;
        flex-direction: column !important;
        gap: 0.2rem !important;
        min-width: 0 !important;
    }

    body.aj-admin-compact .workspace-table-fixed-table td:nth-child(2) .ws-td__title--clamp {
        display: block !important;
        -webkit-line-clamp: unset !important;
        line-clamp: unset !important;
        -webkit-box-orient: unset !important;
        max-height: none !important;
    }

    body.aj-admin-compact .workspace-table-fixed-table td:last-child {
        overflow: visible !important;
        white-space: normal !important;
    }

    body.aj-admin-compact .workspace-table-fixed-table td:last-child .d-flex,
    body.aj-admin-compact .workspace-table-fixed-table td:last-child .actions,
    body.aj-admin-compact .workspace-table-fixed-table td:last-child .btn-group,
    body.aj-admin-compact .workspace-table-fixed-table td:last-child .ws-td__actions {
        display: flex !important;
        flex-wrap: wrap !important;
        gap: 3px !important;
        justify-content: flex-end !important;
        align-items: center !important;
        width: 100% !important;
        min-width: 0 !important;
    }

    body.aj-admin-compact .workspace-table-fixed-table td:last-child .btn,
    body.aj-admin-compact .workspace-table-fixed-table td:last-child .ws-btn {
        min-width: 0 !important;
        max-width: 100% !important;
        height: 24px !important;
        min-height: 24px !important;
        padding: 3px 5px !important;
        font-size: 9.5px !important;
        line-height: 1 !important;
        white-space: nowrap !important;
    }

    @media (max-width: 1366px) {
        body.aj-admin-compact .workspace-table-fixed-table .col-voyage { width: 30%; }
        body.aj-admin-compact .workspace-table-fixed-table .col-actions { width: 12%; }

        body.aj-admin-compact .workspace-table-fixed-table th,
        body.aj-admin-compact .workspace-table-fixed-table td {
            padding: 3px 4px !important;
            font-size: 10px !important;
        }

        body.aj-admin-compact .workspace-table-fixed-table td:last-child .btn,
        body.aj-admin-compact .workspace-table-fixed-table td:last-child .ws-btn {
            height: 22px !important;
            min-height: 22px !important;
            padding: 2px 4px !important;
            font-size: 9px !important;
        }
    }

    /* === FIX: grille catalogue 4 colonnes desktop === */
    .admin-sales-catalogue-page-fix {
        width: 100% !important;
        max-width: none !important;
        margin-left: 0 !important;
        margin-right: 0 !important;
    }

    .admin-sales-catalogue-grid-fix {
        display: grid !important;
        grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
        gap: 18px !important;
    }

    .admin-sales-catalogue-card-fix {
        overflow: hidden !important;
    }

    .admin-sales-catalogue-card-fix .ws-offer-card__media--compact,
    .admin-sales-catalogue-card-fix .ws-offer-card__media {
        height: 150px !important;
        max-height: 150px !important;
        aspect-ratio: auto !important;
    }

    .admin-sales-catalogue-card-fix .ws-offer-card__media img,
    .admin-sales-catalogue-card-fix .ws-offer-card__img {
        height: 150px !important;
        object-fit: cover !important;
    }

    .admin-sales-catalogue-card-fix .ws-offer-card__body--compact {
        padding: 12px !important;
        gap: 0.5rem !important;
    }

    .admin-sales-catalogue-card-fix .ws-offer-card__title--compact {
        font-size: 0.88rem !important;
        line-height: 1.25 !important;
        min-height: 0 !important;
    }

    .admin-sales-catalogue-card-fix .ws-offer-card__departures,
    .admin-sales-catalogue-card-fix .ws-offer-card__commercial-bar {
        padding: 0.5rem 0.6rem !important;
    }

    @media (max-width: 1300px) {
        .admin-sales-catalogue-grid-fix {
            grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
        }
    }

    @media (max-width: 1000px) {
        .admin-sales-catalogue-grid-fix {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }
    }

    @media (max-width: 650px) {
        .admin-sales-catalogue-grid-fix {
            grid-template-columns: 1fr !important;
        }
    }
</style>
@endpush

@section('content')
@php
    $catalogFullCount = $catalogFullCount ?? $catalogRows->count();
    $workspaceUser = auth()->user();
    $isCommercialReservationsOnly = $workspaceUser && $workspaceUser->hasRole('commercial_reservations_only');
    $workspaceUserRole = $workspaceUser?->getRoleNames()->first() ?? 'commercial_reservations_only';
    $workspaceBrandName = \App\Models\Setting::getValue('brand_name', 'Ajinsafro');
    $workspaceBrandLogo = \App\Models\Setting::brandLogoUrl('dark');
    $workspaceUserInitials = strtoupper(collect(preg_split('/\s+/', trim((string) ($workspaceUser?->name ?? 'OA'))))->filter()->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->implode(''));
    if ($workspaceUserInitials === '') { $workspaceUserInitials = 'OA'; }
@endphp
<div class="fade-in ws-page admin-sales-catalogue-page-fix pb-10 overflow-x-hidden"
     style="width:100%;max-width:none;margin:0;">
    @if(session('workspace_store_error'))
        <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 text-red-900 px-4 py-3 text-sm shadow-sm" role="alert">
            <strong class="font-semibold">Enregistrement impossible.</strong>
            <p class="mb-0 mt-1">{{ session('workspace_store_error') }}</p>
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 text-amber-950 px-4 py-3 text-sm shadow-sm" role="alert">
            <strong class="font-semibold">Vérifiez le formulaire :</strong>
            <ul class="mb-0 mt-2 ps-4 list-disc space-y-1">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('success'))
        <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 text-emerald-900 px-4 py-3 text-sm font-medium shadow-sm">{{ session('success') }}</div>
    @endif

    @php
        $sellableRows = $catalogRows->filter(fn($r) => $r['commercial']['is_sellable'] ?? false)->values();
        $allDepRowsV2 = collect();
        foreach ($catalogRows as $rowV2) {
            $isVoyageSellableV2 = $rowV2['commercial']['is_sellable'] ?? false;
            foreach (collect($rowV2['modal_detail']['departures'] ?? [])->values() as $departureV2) {
                $depDateV2 = !empty($departureV2['date_iso']) ? \Carbon\Carbon::parse($departureV2['date_iso']) : null;
                $remainingV2 = $departureV2['remaining'] ?? null;
                $isDepSellableV2 = $isVoyageSellableV2
                    && $depDateV2 !== null
                    && empty($departureV2['is_past'])
                    && ($departureV2['status_key'] ?? 'unknown') !== 'full'
                    && ($remainingV2 === null || $remainingV2 > 0);
                $allDepRowsV2->push(['row' => $rowV2, 'departure' => $departureV2, 'is_sellable' => $isDepSellableV2]);
            }
        }
        $sellableDepRowsV2 = $allDepRowsV2->filter(fn ($i) => $i['is_sellable'])->values();
    @endphp

    @if($useLegacyWorkspaceMarkup)
    <div id="reservations-main-content" class="commercial-v2-main space-y-6">
        <form id="catalogue-workspace" method="GET" action="{{ $workspaceFormUrl }}" class="commercial-v2-filters-wrap">
            <input type="hidden" name="view" id="ws-filter-view" value="{{ $workspaceView }}">
            <div class="commercial-v2-header">
                <div>
                    <h1>Catalogue des Voyages & Départs</h1>
                    <p>Recherchez les départs programmés et initiez un dossier de vente directe en un seul clic.</p>
                </div>
                <div class="commercial-v2-view-switch" role="group" aria-label="Mode d'affichage">
                    <button type="button" id="btn-view-catalog" class="{{ $workspaceView === 'catalog' ? 'is-active' : '' }}"><i class="fas fa-th-large"></i><span>Catalogue</span></button>
                    <button type="button" id="btn-view-list" class="{{ $workspaceView === 'list' ? 'is-active' : '' }}"><i class="fas fa-list"></i><span>Liste</span></button>
                    <button type="button" id="btn-view-calendar" class="{{ $workspaceView === 'calendar' ? 'is-active' : '' }}"><i class="far fa-calendar-alt"></i><span>Calendrier</span></button>
                </div>
            </div>
            <div class="commercial-v2-filters-card">
                <div class="commercial-v2-filters-grid">
                    <div><label for="ws-filter-search">Destination / Titre</label><input type="text" id="ws-filter-search" name="search" value="{{ $workspaceFilters['search'] ?? '' }}" placeholder="Ex: Dakhla, Marrakech..."></div>
                    <div><label for="ws-filter-type">Type de voyage</label><select id="ws-filter-type" name="type"><option value="" {{ ($workspaceFilters['type'] ?? '') === '' ? 'selected' : '' }}>Tous types</option><option value="package" {{ ($workspaceFilters['type'] ?? '') === 'package' ? 'selected' : '' }}>Package</option><option value="vol" {{ ($workspaceFilters['type'] ?? '') === 'vol' ? 'selected' : '' }}>Vol</option><option value="hebergement" {{ ($workspaceFilters['type'] ?? '') === 'hebergement' ? 'selected' : '' }}>Hébergement</option></select></div>
                    <div><label for="ws-filter-date-from">Départ après le</label><input type="date" id="ws-filter-date-from" name="date_from" value="{{ $workspaceFilters['date_from'] ?? '' }}"></div>
                    <div><label for="ws-filter-date-to">Départ avant le</label><input type="date" id="ws-filter-date-to" name="date_to" value="{{ $workspaceFilters['date_to'] ?? '' }}"></div>
                    <input type="hidden" id="ws-filter-budget-min" name="budget_min" value="{{ $workspaceFilters['budget_min'] ?? 0 }}">
                    <input type="hidden" id="ws-filter-budget-max" name="budget_max" value="{{ $workspaceFilters['budget_max'] ?? 30000 }}">
                    <div class="commercial-v2-budget-range">
                        <label for="ws-budget-range-max">Segment budget</label>
                        <div class="commercial-v2-budget-range__labels">
                            <span>MAX</span>
                            <span id="ws-budget-range-value">{{ (int) ($workspaceFilters['budget_max'] ?? 30000) }} MAD</span>
                        </div>
                        <input type="range" id="ws-budget-range-max" min="0" max="100000" step="500" value="{{ (int) ($workspaceFilters['budget_max'] ?? 30000) }}">
                    </div>
                </div>
                <div class="commercial-v2-filters-actions">
                    <button type="submit" class="commercial-v2-apply-btn"><i class="fas fa-filter"></i><span>Filtrer</span></button>
                    <a href="{{ $workspaceResetUrl }}" class="commercial-v2-reset-btn">Réinitialiser les filtres</a>
                </div>
            </div>
        </form>

        <div id="ws-view-table" class="commercial-v2-panel {{ $workspaceView === 'list' ? '' : 'hidden' }}">
            <div class="commercial-v2-list-table-wrap"><table class="commercial-v2-list-table"><thead><tr><th>Réf</th><th>Voyage &amp; Type</th><th>Destination</th><th>Dates (Aller/Retour)</th><th>Vendu / En attente</th><th>Remplissage</th><th>Actions</th></tr></thead><tbody id="ws-catalog-table-body">
                @forelse($sellableDepRowsV2 as $depItemV2)
                    @php
                        $rowV2 = $depItemV2['row']; $departureV2 = $depItemV2['departure'];
                        $depDateV2 = !empty($departureV2['date_iso']) ? \Carbon\Carbon::parse($departureV2['date_iso']) : null;
                        $retDateV2 = !empty($departureV2['return_date_iso']) ? \Carbon\Carbon::parse($departureV2['return_date_iso']) : null;
                        $confirmedV2 = (int) data_get($departureV2, 'pax.validee', $rowV2['stats']['validee'] ?? 0);
                        $pendingV2 = (int) data_get($departureV2, 'pax.en_cours', $rowV2['stats']['en_cours'] ?? 0);
                        $capacityV2 = data_get($departureV2, 'capacity', $rowV2['commercial']['capacity_total'] ?? null);
                        $remainingV2 = data_get($departureV2, 'remaining', $rowV2['commercial']['places_restantes'] ?? null);
                        $fillPctV2 = ($capacityV2 && $capacityV2 > 0) ? min(100, (int) round((($confirmedV2 + $pendingV2) / $capacityV2) * 100)) : 0;
                        $reserveUrlV2 = data_get($departureV2, 'routes.reserve') ?: route('admin.reservations.create', array_filter(['tour_id' => (int) ($rowV2['voyage_id'] ?? 0), 'travel_date_id' => data_get($departureV2, 'travel_date_id')]));
                    @endphp
                    <tr><td><span class="commercial-v2-ref">{{ $rowV2['code'] ?? 'N/A' }}</span></td><td><div class="commercial-v2-voyage-title">{{ $rowV2['name'] ?? 'Voyage' }}</div><div class="commercial-v2-voyage-meta"><span class="commercial-v2-type">{{ strtoupper($rowV2['type_label'] ?? $rowV2['type'] ?? 'PACKAGE') }}</span><span>{{ $rowV2['duration_label'] ?? ($rowV2['duration'] ?? 'Durée non renseignée') }}</span></div></td><td><span class="commercial-v2-destination"><i class="fas fa-map-marker-alt"></i>{{ $rowV2['voyage_destination'] ?? '-' }}</span></td><td><div class="commercial-v2-dates"><span>Du: {{ $depDateV2 ? $depDateV2->format('d/m/Y') : '-' }}</span><span>Au: {{ $retDateV2 ? $retDateV2->format('d/m/Y') : '-' }}</span></div></td><td class="text-center"><span class="commercial-v2-sold">Vendu: {{ $confirmedV2 }}</span><span class="commercial-v2-pending">En attente: {{ $pendingV2 }}</span></td><td><div class="commercial-v2-fill"><div class="commercial-v2-fill-bar"><span style="width: {{ $fillPctV2 }}%"></span></div><small>{{ $remainingV2 !== null ? ('Dispo: '.$remainingV2) : 'Dispo: -' }}</small></div></td><td class="text-center"><div class="commercial-v2-actions"><button type="button" class="commercial-v2-btn commercial-v2-btn-view" data-ws-detail-trigger data-row-code="{{ $rowV2['code'] ?? '' }}" data-travel-date-id="{{ data_get($departureV2, 'travel_date_id', '') }}">Voir</button><a href="{{ $reserveUrlV2 }}" class="commercial-v2-btn commercial-v2-btn-book">Réserver</a></div></td></tr>
                @empty
                    <tr><td colspan="7" class="commercial-v2-empty">Aucune offre réservable disponible.</td></tr>
                @endforelse
            </tbody></table></div>
        </div>

        <div id="ws-view-catalog" class="commercial-v2-panel {{ $workspaceView === 'catalog' ? '' : 'hidden' }}">
            <div class="commercial-v2-cards-grid catalogue-grid">
                @forelse($sellableRows as $row)
                    @php
                        $img = !empty($row['image_url']) ? (string) $row['image_url'] : asset('build/images/placeholder.png');
                        $typeLabel = strtoupper($row['type_label'] ?? $row['type'] ?? 'CIRCUIT');
                        $code = $row['code'] ?? 'N/A';
                        $dest = $row['voyage_destination'] ?? data_get($row, 'modal_detail.destination', '-');
                        $price = $row['price_label'] ?? data_get($row, 'modal_detail.prices.adult_label', '-');
                        $com = $row['commercial'] ?? [];
                        $cap = $com['capacity_total'] ?? null;
                        $rest = $com['places_restantes'] ?? null;
                        $sold = $com['places_vendues'] ?? 0;
                        $departures = collect(data_get($row, 'modal_detail.departures', []));
                    @endphp
                    @if($departures->isNotEmpty())
                        @foreach($departures as $dep)
                            @php
                                $reserveUrl = data_get($dep, 'routes.reserve') ?: route('admin.reservations.create', array_filter([
                                    'tour_id' => (int) ($row['voyage_id'] ?? 0),
                                    'travel_date_id' => data_get($dep, 'travel_date_id'),
                                ]));
                            @endphp
                            <article class="voyage-card commercial-voyage-card">
                                <div class="voyage-card-image">
                                    <img src="{{ $img }}" alt="{{ $row['name'] ?? 'Voyage' }}" loading="lazy">
                                </div>
                                <div class="voyage-card-body">
                                    <div class="voyage-card-badges">
                                        <span class="voyage-chip">{{ $typeLabel }}</span>
                                        <span class="voyage-chip voyage-chip-ref">#{{ $code }}</span>
                                    </div>
                                    <h3>{{ $row['name'] ?? 'Voyage' }}</h3>
                                    <p class="voyage-card-destination"><i class="fas fa-map-marker-alt"></i> {{ $dest }}</p>
                                    <div class="voyage-card-meta">
                                        <span>Prix à partir de</span>
                                        <strong>{{ $price }}</strong>
                                    </div>
                                    <div class="voyage-card-stats">
                                        <span>Capacité: <strong>{{ $cap ?? '-' }}</strong></span>
                                        <span>Vendu: <strong>{{ $sold }}</strong></span>
                                        <span>Restant: <strong>{{ $rest ?? '-' }}</strong></span>
                                    </div>
                                    <div class="voyage-card-actions">
                                        <button type="button" class="btn-view" data-ws-detail-trigger data-row-code="{{ $code }}" data-travel-date-id="{{ data_get($dep, 'travel_date_id', '') }}">Voir</button>
                                        <a href="{{ $reserveUrl }}" class="btn-reserve">Réserver</a>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    @else
                        <article class="voyage-card commercial-voyage-card">
                            <div class="voyage-card-image">
                                <img src="{{ $img }}" alt="{{ $row['name'] ?? 'Voyage' }}" loading="lazy">
                            </div>
                            <div class="voyage-card-body">
                                <div class="voyage-card-badges">
                                    <span class="voyage-chip">{{ $typeLabel }}</span>
                                    <span class="voyage-chip voyage-chip-ref">#{{ $code }}</span>
                                </div>
                                <h3>{{ $row['name'] ?? 'Voyage' }}</h3>
                                <p class="voyage-card-destination"><i class="fas fa-map-marker-alt"></i> {{ $dest }}</p>
                                <div class="voyage-card-meta">
                                    <span>Prix à partir de</span>
                                    <strong>{{ $price }}</strong>
                                </div>
                                <div class="voyage-card-stats">
                                    <span>Capacité: <strong>{{ $cap ?? '-' }}</strong></span>
                                    <span>Vendu: <strong>{{ $sold }}</strong></span>
                                    <span>Restant: <strong>{{ $rest ?? '-' }}</strong></span>
                                </div>
                                <div class="voyage-card-actions">
                                    <button type="button" class="btn-view" data-ws-detail-trigger data-row-code="{{ $code }}">Voir</button>
                                    <a href="{{ route('admin.reservations.create', array_filter(['tour_id' => (int) ($row['voyage_id'] ?? 0)])) }}" class="btn-reserve">Réserver</a>
                                </div>
                            </div>
                        </article>
                    @endif
                @empty
                    <div class="commercial-v2-empty">Aucune offre réservable disponible.</div>
                @endforelse
            </div>
        </div>
        <div id="reservations-calendar-view" class="commercial-v2-panel {{ $workspaceView === 'calendar' ? '' : 'hidden' }}"><div class="ws-calendar-panel"><div id="workspace-calendar" class="w-full min-h-[540px] fc-workspace" data-reset-url="{{ $workspaceResetUrl }}"></div></div></div>
    </div>
    @else
    <div id="reservations-main-content" class="space-y-4">
        <form id="catalogue-workspace" class="ws-toolbar" method="GET" action="{{ $workspaceFormUrl }}">
            <input type="hidden" name="view" id="ws-filter-view" value="{{ $workspaceView }}">
            @if(request()->filled('catalog'))
                <input type="hidden" name="catalog" value="{{ request()->query('catalog') }}">
            @endif
            @if(request()->filled('sort'))
                <input type="hidden" name="sort" value="{{ request()->query('sort') }}">
            @endif
            @if(request()->filled('direction'))
                <input type="hidden" name="direction" value="{{ request()->query('direction') }}">
            @endif
            <div class="ws-toolbar__row ws-toolbar__row--search">
                <div class="ws-field ws-field--grow">
                    <label class="ws-field__label" for="ws-filter-search">Recherche rapide</label>
                    <div class="ws-field__input-wrap">
                        <i class="fas fa-search ws-field__icon" aria-hidden="true"></i>
                        <input type="text" id="ws-filter-search" name="search" value="{{ $workspaceFilters['search'] ?? '' }}" placeholder="Nom, code, destination…" autocomplete="off" class="ws-input">
                    </div>
                </div>
                <div class="ws-toolbar__views">
                    <div class="ws-seg ws-seg--triple" role="group" aria-label="Mode d'affichage">
                        <button type="button" id="btn-view-catalog" class="ws-seg__btn {{ $workspaceView === 'catalog' ? 'is-active' : '' }}" data-ws-target-view="catalog" title="Catalogue"><i class="fas fa-th-large" aria-hidden="true"></i><span class="ws-seg__btn-label-catalog">Catalogue</span></button>
                        <button type="button" id="btn-view-list" class="ws-seg__btn {{ $workspaceView === 'list' ? 'is-active' : '' }}" data-ws-target-view="list" title="Vue liste (tableau)"><i class="fas fa-table" aria-hidden="true"></i><span>Liste</span></button>
                        <button type="button" id="btn-view-calendar" class="ws-seg__btn {{ $workspaceView === 'calendar' ? 'is-active' : '' }}" data-ws-target-view="calendar" title="Calendrier"><i class="far fa-calendar-alt" aria-hidden="true"></i><span>Calendrier</span></button>
                    </div>
                </div>
            </div>
            <div class="ws-toolbar__row ws-toolbar__row--filters-grid">
                <div class="ws-field">
                    <label class="ws-field__label" for="ws-filter-type">Type</label>
                    <select id="ws-filter-type" name="type" class="ws-select">
                        <option value="" {{ ($workspaceFilters['type'] ?? '') === '' ? 'selected' : '' }}>Tous</option>
                        <option value="package" {{ ($workspaceFilters['type'] ?? '') === 'package' ? 'selected' : '' }}>Package</option>
                        <option value="vol" {{ ($workspaceFilters['type'] ?? '') === 'vol' ? 'selected' : '' }}>Vol</option>
                        <option value="hebergement" {{ ($workspaceFilters['type'] ?? '') === 'hebergement' ? 'selected' : '' }}>Hébergement</option>
                    </select>
                </div>
                <div class="ws-field">
                    <label class="ws-field__label" for="ws-filter-destination">Destination</label>
                    <select id="ws-filter-destination" name="destination" class="ws-select">
                        <option value="">Toutes</option>
                        @foreach(($workspaceFilterOptions['destinations'] ?? []) as $destination)
                            <option value="{{ $destination }}" {{ ($workspaceFilters['destination'] ?? '') === $destination ? 'selected' : '' }}>{{ $destination }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="ws-field">
                    <label class="ws-field__label" for="ws-filter-date-from">Date départ Du</label>
                    <input type="date" id="ws-filter-date-from" name="date_from" value="{{ $workspaceFilters['date_from'] ?? '' }}" class="ws-input">
                </div>
                <div class="ws-field">
                    <label class="ws-field__label" for="ws-filter-date-to">Date départ Au</label>
                    <input type="date" id="ws-filter-date-to" name="date_to" value="{{ $workspaceFilters['date_to'] ?? '' }}" class="ws-input">
                </div>
                <input type="hidden" id="ws-filter-budget-min" name="budget_min" value="{{ $workspaceFilters['budget_min'] ?? 0 }}">
                <input type="hidden" id="ws-filter-budget-max" name="budget_max" value="{{ $workspaceFilters['budget_max'] ?? 30000 }}">
                <div class="ws-field ws-field--budget-range full">
                    <label class="ws-field__label" for="ws-budget-range-max">Segment budget</label>
                    <div class="ws-budget-range">
                        <div class="ws-budget-range__labels"><span>MAX</span><span id="ws-budget-range-value">{{ (int) ($workspaceFilters['budget_max'] ?? 30000) }} MAD</span></div>
                        <input type="range" id="ws-budget-range-max" min="0" max="100000" step="500" value="{{ (int) ($workspaceFilters['budget_max'] ?? 30000) }}">
                    </div>
                </div>
                <div class="ws-filter-actions">
                    <button type="submit" id="ws-filters-apply" class="ws-btn-filter"><i class="fas fa-filter" aria-hidden="true"></i><span>Filtrer</span></button>
                    <a href="{{ $workspaceResetUrl }}" id="ws-filters-reset" class="ws-btn-reset">Réinitialiser</a>
                </div>
            </div>
        </form>

        {{-- Vue liste (tableau) — défaut --}}
        <div id="ws-view-table" class="ws-table-card {{ $workspaceView === 'list' ? '' : 'hidden' }}">
            <div class="ws-table-card__head">
                <h2 class="ws-table-card__title">Vue liste</h2>
                <p class="ws-table-card__sub">Référence, voyage, départ, capacité et actions.</p>
            </div>
            <div class="ws-table-scroll workspace-list-table-wrapper workspace-table-fixed">
                <table class="ws-data-table ws-data-table--responsive workspace-list-table workspace-table-fixed-table" aria-label="Catalogue des offres en liste">
    <colgroup>
        <col class="col-ref">
        <col class="col-voyage">
        <col class="col-destination">
        <col class="col-depart">
        <col class="col-sold">
        <col class="col-restant">
        <col class="col-capacite">
        <col class="col-actions">
    </colgroup>
    <thead>
        <tr>
            <th scope="col" class="ws-data-table__th-ref">
                <a href="{{ request()->fullUrlWithQuery(['sort' => 'ref', 'direction' => ($currentSort === 'ref' && $currentDirection === 'asc' ? 'desc' : 'asc')]) }}" class="table-sort-link {{ $currentSort === 'ref' ? 'is-active' : '' }}">
                    Réf
                    <i class="fas {{ $currentSort === 'ref' ? ($currentDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down') : 'fa-sort' }} table-sort-icon" aria-hidden="true"></i>
                </a>
            </th>
            <th scope="col" class="ws-data-table__th-offer">
                <a href="{{ request()->fullUrlWithQuery(['sort' => 'voyage', 'direction' => ($currentSort === 'voyage' && $currentDirection === 'asc' ? 'desc' : 'asc')]) }}" class="table-sort-link {{ $currentSort === 'voyage' ? 'is-active' : '' }}">
                    Voyage
                    <i class="fas {{ $currentSort === 'voyage' ? ($currentDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down') : 'fa-sort' }} table-sort-icon" aria-hidden="true"></i>
                </a>
            </th>
            <th scope="col" class="ws-data-table__th-destination">
                <a href="{{ request()->fullUrlWithQuery(['sort' => 'destination', 'direction' => ($currentSort === 'destination' && $currentDirection === 'asc' ? 'desc' : 'asc')]) }}" class="table-sort-link {{ $currentSort === 'destination' ? 'is-active' : '' }}">
                    Destination
                    <i class="fas {{ $currentSort === 'destination' ? ($currentDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down') : 'fa-sort' }} table-sort-icon" aria-hidden="true"></i>
                </a>
            </th>
            <th scope="col" class="ws-data-table__th-dep">
                <a href="{{ request()->fullUrlWithQuery(['sort' => 'departure_date', 'direction' => ($currentSort === 'departure_date' && $currentDirection === 'asc' ? 'desc' : 'asc')]) }}" class="table-sort-link {{ $currentSort === 'departure_date' ? 'is-active' : '' }}">
                    Départ
                    <i class="fas {{ $currentSort === 'departure_date' ? ($currentDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down') : 'fa-sort' }} table-sort-icon" aria-hidden="true"></i>
                </a>
            </th>
            <th scope="col" class="ws-data-table__th-sold">
                <a href="{{ request()->fullUrlWithQuery(['sort' => 'sold_pending', 'direction' => ($currentSort === 'sold_pending' && $currentDirection === 'asc' ? 'desc' : 'asc')]) }}" class="table-sort-link {{ $currentSort === 'sold_pending' ? 'is-active' : '' }}">
                    Vendu / En attente
                    <i class="fas {{ $currentSort === 'sold_pending' ? ($currentDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down') : 'fa-sort' }} table-sort-icon" aria-hidden="true"></i>
                </a>
            </th>
            <th scope="col" class="ws-data-table__th-remain">
                <a href="{{ request()->fullUrlWithQuery(['sort' => 'remaining', 'direction' => ($currentSort === 'remaining' && $currentDirection === 'asc' ? 'desc' : 'asc')]) }}" class="table-sort-link {{ $currentSort === 'remaining' ? 'is-active' : '' }}">
                    Restant
                    <i class="fas {{ $currentSort === 'remaining' ? ($currentDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down') : 'fa-sort' }} table-sort-icon" aria-hidden="true"></i>
                </a>
            </th>
            <th scope="col" class="ws-data-table__th-cap">
                <a href="{{ request()->fullUrlWithQuery(['sort' => 'capacity', 'direction' => ($currentSort === 'capacity' && $currentDirection === 'asc' ? 'desc' : 'asc')]) }}" class="table-sort-link {{ $currentSort === 'capacity' ? 'is-active' : '' }}">
                    Capacité
                    <i class="fas {{ $currentSort === 'capacity' ? ($currentDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down') : 'fa-sort' }} table-sort-icon" aria-hidden="true"></i>
                </a>
            </th>
            <th scope="col" class="ws-data-table__th-actions">Actions</th>
        </tr>
    </thead>

    <tbody id="ws-catalog-table-body">
        @php
            $today = \Carbon\Carbon::today();
            $allDepRows = collect();
            foreach ($catalogRows as $row) {
                $isVoyageSellable = $row['commercial']['is_sellable'] ?? false;
                $departures = collect($row['modal_detail']['departures'] ?? [])->values();
                if ($departures->isNotEmpty()) {
                    foreach ($departures as $departure) {
                        $depDate = !empty($departure['date_iso']) ? \Carbon\Carbon::parse($departure['date_iso']) : null;
                        $isPast = !empty($departure['is_past']);
                        $statusKey = $departure['status_key'] ?? 'unknown';
                        $remaining = $departure['remaining'] ?? null;
                        $isDepSellable = $isVoyageSellable
                            && $depDate !== null
                            && ! $isPast
                            && $statusKey !== 'full'
                            && ($remaining === null || $remaining > 0);
                        $allDepRows->push([
                            'row' => $row,
                            'departure' => $departure,
                            'sort_date' => $depDate,
                            'is_sellable' => $isDepSellable,
                        ]);
                    }
                } else {
                    $allDepRows->push([
                        'row' => $row,
                        'departure' => null,
                        'sort_date' => null,
                        'is_sellable' => false,
                    ]);
                }
            }
            $sortedDepRows = $allDepRows->sort(function ($a, $b) use ($currentSort, $currentDirection) {
                $cmp = 0;
                switch ($currentSort) {
                    case 'ref':
                        $cmp = strcasecmp((string) ($a['row']['code'] ?? ''), (string) ($b['row']['code'] ?? ''));
                        break;
                    case 'voyage':
                        $cmp = strcasecmp((string) ($a['row']['name'] ?? ''), (string) ($b['row']['name'] ?? ''));
                        break;
                    case 'destination':
                        $cmp = strcasecmp((string) ($a['row']['voyage_destination'] ?? ''), (string) ($b['row']['voyage_destination'] ?? ''));
                        break;
                    case 'departure_date':
                        $tsA = $a['sort_date'] ? $a['sort_date']->timestamp : PHP_INT_MAX;
                        $tsB = $b['sort_date'] ? $b['sort_date']->timestamp : PHP_INT_MAX;
                        $cmp = $tsA <=> $tsB;
                        break;
                    case 'sold_pending':
                        $valA = (int) ($a['row']['stats']['validee'] ?? 0) + (int) ($a['row']['stats']['en_cours'] ?? 0);
                        $valB = (int) ($b['row']['stats']['validee'] ?? 0) + (int) ($b['row']['stats']['en_cours'] ?? 0);
                        $cmp = $valA <=> $valB;
                        break;
                    case 'remaining':
                        $remA = data_get($a['departure'], 'remaining', $a['row']['commercial']['places_restantes'] ?? null);
                        $remB = data_get($b['departure'], 'remaining', $b['row']['commercial']['places_restantes'] ?? null);
                        if ($remA === null && $remB === null) {
                            $cmp = 0;
                        } elseif ($remA === null) {
                            $cmp = 1;
                        } elseif ($remB === null) {
                            $cmp = -1;
                        } else {
                            $cmp = $remA <=> $remB;
                        }
                        break;
                    case 'capacity':
                        $capA = data_get($a['departure'], 'capacity', $a['row']['commercial']['capacity_total'] ?? null);
                        $capB = data_get($b['departure'], 'capacity', $b['row']['commercial']['capacity_total'] ?? null);
                        if ($capA === null && $capB === null) {
                            $cmp = 0;
                        } elseif ($capA === null) {
                            $cmp = 1;
                        } elseif ($capB === null) {
                            $cmp = -1;
                        } else {
                            $cmp = $capA <=> $capB;
                        }
                        break;
                    default:
                        $tsA = $a['sort_date'] ? $a['sort_date']->timestamp : PHP_INT_MAX;
                        $tsB = $b['sort_date'] ? $b['sort_date']->timestamp : PHP_INT_MAX;
                        $cmp = $tsA <=> $tsB;
                        break;
                }
                if ($cmp === 0) {
                    $cmp = strcasecmp((string) ($a['row']['code'] ?? ''), (string) ($b['row']['code'] ?? ''));
                }
                return $currentDirection === 'desc' ? -$cmp : $cmp;
            })->values();
            $sellableDepRows = $sortedDepRows->filter(fn($i) => $i['is_sellable']);
        @endphp

        @if($sellableDepRows->isNotEmpty())
            @foreach($sellableDepRows as $depItem)
                @include('admin.reservations.workspace.partials.catalog-row', ['row' => $depItem['row'], 'mode' => 'table', 'departure' => $depItem['departure']])
            @endforeach
        @endif

        @if($sellableDepRows->isEmpty())
            <tr>
                <td colspan="8" class="ws-table-empty-cell">
                    <div class="ws-catalog-empty ws-catalog-empty--inline">
                        <div class="max-w-md mx-auto text-center py-10 px-6">
                            <div class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 text-gray-400 mb-3 text-xl">
                                <i class="fas fa-inbox"></i>
                            </div>

                            <p class="text-brand-dark font-bold text-base mb-2">
                                Aucune offre réservable disponible pour le moment.
                            </p>
                        </div>
                    </div>
                </td>
            </tr>
        @endif
    </tbody>
</table>
                <div class="ws-pagination" id="ws-pagination">
                    <div class="ws-pagination__info">
                        <span id="ws-pagination-info"></span>
                    </div>
                    <div class="ws-pagination__controls" id="ws-pagination-controls"></div>
                    <div class="ws-pagination__per-page">
                        <label for="ws-per-page">Lignes</label>
                        <select id="ws-per-page">
                            <option value="10" selected>10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- Présentation catalogue (cartes) --}}
        <div id="ws-view-catalog" class="ws-table-card {{ $workspaceView === 'catalog' ? '' : 'hidden' }}">
            <div class="ws-table-card__head">
                <h2 class="ws-table-card__title">Catalogue</h2>
                <p class="ws-table-card__sub">Vue compacte des voyages et départs disponibles.</p>
            </div>
            @php
                $sellableRows = $catalogRows->filter(fn($r) => $r['commercial']['is_sellable'] ?? false);
                $nonSellableRows = $catalogRows->filter(fn($r) => !($r['commercial']['is_sellable'] ?? false));
            @endphp
            <div id="ws-catalog-list">
                @if($sellableRows->isNotEmpty())
                    <div class="ws-catalog-section"
                         style="width:100% !important;max-width:none !important;margin:0 !important;padding-left:0 !important;padding-right:0 !important;">
                        <h3 class="ws-catalog-section__title">Départs disponibles à la vente</h3>
                        <div class="admin-sales-catalogue-grid-fix"
                             style="display:grid !important;grid-template-columns:repeat(4,minmax(0,1fr)) !important;gap:18px !important;width:100% !important;max-width:none !important;margin:0 !important;padding:0 !important;justify-content:stretch !important;justify-items:stretch !important;align-items:stretch !important;">
                            @foreach($sellableDepRowsV2 as $depItem)
                                @include('admin.reservations.workspace.partials.catalog-row', ['row' => $depItem['row'], 'departure' => $depItem['departure'], 'mode' => 'card'])
                            @endforeach
                        </div>
                    </div>
                @endif
                @if($catalogRows->isEmpty())
                    <div class="ws-catalog-empty">
                        <div class="max-w-md mx-auto text-center py-12 px-6">
                            <div class="inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-gray-100 text-gray-400 mb-4 text-2xl">
                                <i class="fas fa-inbox"></i>
                            </div>
                            <p class="text-brand-dark font-bold text-lg mb-2">Aucun voyage dans le catalogue</p>
                            <p class="text-gray-500 text-sm mb-6">Créez ou liez des fiches voyages depuis Circuits / voyages.</p>
                            <a href="{{ route('admin.circuits.voyages.index') }}" class="inline-flex items-center gap-2 rounded-xl bg-brand-blue text-white font-bold text-sm px-5 py-3 hover:bg-brand-dark transition-colors">
                                <i class="fas fa-plus-circle"></i> Gérer les voyages
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Calendrier --}}
        <div id="reservations-calendar-view" class="bg-white p-4 sm:p-6 rounded-2xl shadow-custom border border-gray-100/90 {{ $workspaceView === 'calendar' ? '' : 'hidden' }}">
            <div class="ws-calendar-panel">
                <p class="ws-calendar-panel__hint">Vue mensuelle des départs filtrés. Cliquez sur un départ pour ouvrir son détail.</p>
                <div id="workspace-calendar" class="w-full min-h-[540px] fc-workspace" data-reset-url="{{ $workspaceResetUrl }}"></div>
            </div>
        </div>
    </div>
    </div>
</div>
    @endif

<script type="application/json" id="workspace-calendar-json">{!! json_encode($workspaceCalendarEvents, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>
<script type="application/json" id="workspace-calendar-meta-json">{!! json_encode(['seed_date' => $workspaceCalendarSeedDate, 'reset_url' => $workspaceResetUrl], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>
<script type="application/json" id="ws-modal-detail-json">{!! json_encode($catalogRows->mapWithKeys(fn ($r) => [($r['code'] ?? '') => $r['modal_detail'] ?? null])->filter(fn ($v) => $v !== null), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>
@endsection

@push('scripts')
{{-- Modal hors layout (évite overflow / stacking) — rendu juste avant </body> --}}
<div id="ws-modal-root">
    <div id="ws-voyage-detail-modal" class="ws-md-root hidden" role="dialog" aria-modal="true" aria-labelledby="ws-md-title" aria-hidden="true">
        <div class="ws-md-overlay" data-ws-md-backdrop tabindex="-1" aria-hidden="true"></div>
        <div class="ws-md-shell">
            <header class="ws-md-header">
                <div class="ws-md-header-top">
                    <div class="min-w-0">
                        <h2 id="ws-md-title" class="ws-md-title">—</h2>
                        <div id="ws-md-sub" class="ws-md-meta"></div>
                    </div>
                    <button type="button" class="ws-md-close" data-ws-md-close aria-label="Fermer">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </header>
            <div id="ws-md-body" class="ws-md-body"></div>
            <footer id="ws-md-footer" class="ws-md-footer"></footer>
        </div>
    </div>
</div>
@endpush

@push('scripts')
<script>
(function () {
    var defaults = {
        show_commission: true,
        show_commission_type: true,
        show_commission_amount: true,
        show_commission_percentage: true,
        show_commission_fixed: true,
        show_commission_agent: true,
        show_commission_branch: true,
        show_commission_help: true,
        show_departure_report: true
    };
    var injected = {!! json_encode($wsModalSettings ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!};
    if (!injected || typeof injected !== 'object' || Array.isArray(injected)) injected = {};
    window.wsModalSettings = Object.assign({}, defaults, injected);
})();
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    /* Modal détail : enregistré en premier (avant FullCalendar) pour éviter qu’une erreur JS bloque le clic. */
    var wsModalJson = document.getElementById('ws-modal-detail-json');
    var wsModalEl = document.getElementById('ws-voyage-detail-modal');
    var wsMdTitle = document.getElementById('ws-md-title');
    var wsMdSub = document.getElementById('ws-md-sub');
    var wsMdBody = document.getElementById('ws-md-body');
    var wsMdFooter = document.getElementById('ws-md-footer');
    function parseWsDetailMap() {
        if (!wsModalJson) return {};
        try { return JSON.parse(wsModalJson.textContent || '{}'); } catch (err) { return {}; }
    }
    var wsDetailMap = parseWsDetailMap();
    var wsModalSettings = window.wsModalSettings || {};
    function escapeWsHtml(s) {
        if (s == null || s === '') return '';
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }
    function closeWsDetailModal() {
        if (!wsModalEl) return;
        wsModalEl.classList.remove('ws-md-visible');
        wsModalEl.classList.add('ws-md-leaving');
        document.body.classList.remove('ws-md-open');
        setTimeout(function () {
            wsModalEl.classList.add('hidden');
            wsModalEl.classList.remove('ws-md-leaving');
            wsModalEl.style.removeProperty('display');
            wsModalEl.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = 'auto';
        }, 280);
    }
    function renderWsDeparturesHtml(d) {
        var deps = d.departures;
        if (!deps || !deps.length) return '';
        var capNote = '<p class="ws-md-dep-hint">Capacité calculée depuis la répartition des chambres du départ. Les dossiers sans date de départ ne sont pas inclus ici.</p>';
        var h = '<section class="ws-md-card" aria-labelledby="ws-sec-dep-avail">';
        h += '<div class="ws-md-section-head" id="ws-sec-dep-avail"><i class="fas fa-route" aria-hidden="true"></i> Disponibilités par départ</div>';
        h += capNote;
        h += '<div class="ws-md-departure-list">';
        deps.forEach(function (dep) {
            var rs = dep.reservations || {};
            var pax = dep.pax || {};
            var sk = dep.status_key || 'unknown';
            var badgeClass = 'ws-md-avail-badge ws-md-avail-badge--unknown';
            if (sk === 'available') badgeClass = 'ws-md-avail-badge ws-md-avail-badge--ok';
            else if (sk === 'almost_full') badgeClass = 'ws-md-avail-badge ws-md-avail-badge--warn';
            else if (sk === 'full') badgeClass = 'ws-md-avail-badge ws-md-avail-badge--full';
            var pastClass = dep.is_past ? ' ws-md-departure-card--past' : '';
            h += '<article class="ws-md-departure-card' + pastClass + '">';
            h += '<div class="ws-md-departure-card-head">';
            h += '<div>';
            h += '<div class="ws-md-departure-date">' + escapeWsHtml(dep.date_label || '—');
            if (dep.is_past) h += ' <span class="ws-md-tag ws-md-tag-past">Passé</span>';
            h += '</div>';
            h += '</div>';
            h += '<span class="' + badgeClass + '" title="État de remplissage">' + escapeWsHtml(dep.status_label || '—') + '</span>';
            h += '</div>';
            h += '<div class="ws-md-departure-kpis">';
            h += '<div class="ws-md-dep-kpi"><span>Capacité</span><strong>' + escapeWsHtml(String(dep.capacity != null ? dep.capacity : 0)) + '</strong></div>';
            h += '<div class="ws-md-dep-kpi"><span>Confirmées</span><strong>' + (pax.validee != null ? pax.validee : 0) + '</strong></div>';
            h += '<div class="ws-md-dep-kpi"><span>En attente</span><strong>' + (pax.en_cours != null ? pax.en_cours : 0) + '</strong></div>';
            h += '<div class="ws-md-dep-kpi"><span>Annulées</span><strong>' + (pax.annulee != null ? pax.annulee : 0) + '</strong></div>';
            h += '<div class="ws-md-dep-kpi"><span>Total dossiers</span><strong>' + (rs.total != null ? rs.total : 0) + '</strong></div>';
            h += '<div class="ws-md-dep-kpi"><span>Places restantes</span><strong>' + (dep.remaining != null ? escapeWsHtml(String(dep.remaining)) : '—') + '</strong></div>';
            if (dep.fill_pct != null) {
                h += '<div class="ws-md-dep-kpi"><span>Taux remplissage</span><strong>' + dep.fill_pct + '%</strong></div>';
            }
            h += '</div>';
            if (dep.capacity_note && !(dep.rooms && dep.rooms.length)) {
                h += '<p style="margin:0.5rem 0 0;font-size:0.75rem;color:#64748b">' + escapeWsHtml(dep.capacity_note) + '</p>';
            }
            if (Array.isArray(dep.alerts) && dep.alerts.length) {
                h += '<p style="margin:0.5rem 0 0;font-size:0.75rem;color:#b45309;font-weight:700">' + escapeWsHtml(dep.alerts.join(' · ')) + '</p>';
            }
            if (dep.rooms && dep.rooms.length) {
                h += '<div class="ws-md-departure-room-list" style="margin:0.75rem 0 0">';
                dep.rooms.forEach(function (room) {
                    var full = room.available_rooms <= 0;
                    h += '<div style="display:flex;align-items:center;justify-content:space-between;font-size:0.8rem;padding:0.35rem 0;border-top:1px solid #f1f5f9;">';
                    h += '<span><strong>' + escapeWsHtml(room.room_type || 'Chambre') + '</strong> · ' + room.capacity_per_room + ' pers.</span>';
                    if (full) {
                        h += '<span style="color:#991b1b;font-weight:700;font-size:0.75rem">Complet</span>';
                    } else {
                        h += '<span>' + room.available_rooms + ' / ' + room.total_rooms + ' restantes</span>';
                    }
                    h += '</div>';
                });
                h += '</div>';
            }
            if (dep.capacity_known && dep.fill_pct != null) {
                var barClass = 'ws-md-progress-bar';
                if (sk === 'full') barClass += ' ws-md-progress-bar--dep-full';
                else if (sk === 'almost_full') barClass += ' ws-md-progress-bar--dep-warn';
                h += '<div class="ws-md-progress ws-md-progress--dep" role="progressbar" aria-valuenow="' + dep.fill_pct + '" aria-valuemin="0" aria-valuemax="100"><div class="' + barClass + '" style="width:' + dep.fill_pct + '%"></div></div>';
                h += '<p style="margin:0.35rem 0 0;font-size:0.65rem;color:#94a3b8;font-weight:600">Basé sur les passagers des réservations confirmées / capacité</p>';
            }
            var rts = dep.routes || {};
            if (rts.reserve || rts.reservations) {
                h += '<div class="ws-md-departure-actions">';
                if (rts.reserve) h += '<a href="' + escapeWsHtml(rts.reserve) + '" class="ws-md-dep-primary">Réserver ce départ</a>';
                if (rts.reservations) h += '<a href="' + escapeWsHtml(rts.reservations) + '">Voir les réservations</a>';
                h += '</div>';
            }
            h += '</article>';
        });
        h += '</div></section>';
        return h;
    }
    function renderWsModalBody(d) {
        if (!d) return '';
        if (d.kind === 'package') {
            var html = '<div class="ws-md-body-inner">';
            html += '<section class="ws-md-card" aria-labelledby="ws-sec-info">';
            html += '<div class="ws-md-section-head" id="ws-sec-info"><i class="fas fa-info-circle" aria-hidden="true"></i> Informations générales</div>';
            html += '<dl class="ws-md-dl">';
            if (d.destination) html += '<div><dt>Destination</dt><dd>' + escapeWsHtml(d.destination) + '</dd></div>';
            if (d.duration) html += '<div><dt>Durée</dt><dd>' + escapeWsHtml(d.duration) + '</dd></div>';
            html += '</dl></section>';
            if (d.travel_dates && d.travel_dates.length) {
                html += '<section class="ws-md-card" aria-labelledby="ws-sec-dates">';
                html += '<div class="ws-md-section-head" id="ws-sec-dates"><i class="fas fa-calendar-alt" aria-hidden="true"></i> Dates de disponibilité</div>';
                html += '<div class="ws-md-date-pills">';
                d.travel_dates.forEach(function (td) {
                    var tagClass = td.is_past ? 'ws-md-tag ws-md-tag-past' : 'ws-md-tag ws-md-tag-future';
                    var tag = td.is_past ? 'Passé' : 'À venir';
                    html += '<span class="ws-md-date-pill"><span>' + escapeWsHtml(td.date_label) + '</span><span class="' + tagClass + '">' + tag + '</span></span>';
                });
                html += '</div></section>';
            }
            if (d.departures && d.departures.length) {
                html += renderWsDeparturesHtml(d);
            } else if (d.places && d.places.state === 'ok' && d.places.total != null) {
                var pct = d.places.fill_pct != null ? d.places.fill_pct : 0;
                html += '<section class="ws-md-card" aria-labelledby="ws-sec-places">';
                html += '<div class="ws-md-section-head" id="ws-sec-places"><i class="fas fa-users" aria-hidden="true"></i> Places (vue globale)</div>';
                html += '<p class="ws-md-dep-hint">Aucune date de départ listée : vue agrégée toutes dates.</p>';
                html += '<div class="ws-md-places-row">';
                html += '<div class="ws-md-stat-box"><span>Total</span><strong>' + d.places.total + '</strong></div>';
                html += '<div class="ws-md-stat-box"><span>Réservées</span><strong>' + d.places.reserved + '</strong></div>';
                html += '<div class="ws-md-stat-box"><span>Disponibles</span><strong>' + (d.places.remaining != null ? d.places.remaining : '—') + '</strong></div>';
                html += '</div>';
                html += '<div class="ws-md-progress" role="progressbar" aria-valuenow="' + pct + '" aria-valuemin="0" aria-valuemax="100"><div class="ws-md-progress-bar" style="width:' + pct + '%"></div></div>';
                html += '<p style="margin:0.5rem 0 0;font-size:0.7rem;color:#94a3b8;font-weight:600">' + pct + '% des places réservées (toutes dates confondues)</p>';
                html += '</section>';
            } else if (d.places) {
                html += '<section class="ws-md-card"><div class="ws-md-section-head"><i class="fas fa-users"></i> Places</div>';
                html += '<p style="margin:0;font-size:0.875rem;color:#64748b">Capacité non calculable : <strong>' + escapeWsHtml(String(d.places.state)) + '</strong> (hôtels / chambres)</p></section>';
            }
            if (d.rooms && d.rooms.length) {
                html += '<section class="ws-md-card" aria-labelledby="ws-sec-rooms">';
                html += '<div class="ws-md-section-head" id="ws-sec-rooms"><i class="fas fa-bed" aria-hidden="true"></i> Chambres</div>';
                html += '<div class="ws-md-room-pills">';
                d.rooms.forEach(function (ln) {
                    var rt = ln.room_type || '';
                    var rc = ln.room_count || 0;
                    var cu = ln.capacity_used || 0;
                    var pr = ln.product || 0;
                    var tip = rt + ' : ' + rc + ' × ' + cu + ' = ' + pr;
                    html += '<span class="ws-md-room-pill" title="' + escapeWsHtml(tip) + '">' + escapeWsHtml(rt) + ' <span style="color:#94a3b8;font-weight:800">' + pr + '</span></span>';
                });
                html += '</div></section>';
            }
            html += '<section class="ws-md-card" aria-labelledby="ws-sec-price">';
            html += '<div class="ws-md-section-head" id="ws-sec-price"><i class="fas fa-coins" aria-hidden="true"></i> Tarifs</div>';
            html += '<p class="ws-md-price-main">' + (d.prices && d.prices.adult_label ? escapeWsHtml(d.prices.adult_label) : '—') + ' <span style="font-size:0.75rem;font-weight:600;color:#94a3b8">adulte</span></p>';
            if (d.prices && d.prices.child_label) html += '<p style="margin:0.35rem 0 0;font-size:0.875rem;font-weight:700;color:#334155">Enfant : ' + escapeWsHtml(d.prices.child_label) + '</p>';
            html += '<p style="margin:0.5rem 0 0;font-size:0.7rem;color:#94a3b8">Devise : ' + escapeWsHtml((d.prices && d.prices.currency) || 'MAD') + '</p>';
            html += '</section>';
            if (d.stats) {
                html += '<section class="ws-md-card" aria-labelledby="ws-sec-stats">';
                html += '<div class="ws-md-section-head" id="ws-sec-stats"><i class="fas fa-chart-bar" aria-hidden="true"></i> Réservations (toutes dates)</div>';
                if (d.departures && d.departures.length) {
                    html += '<p class="ws-md-dep-hint" style="margin-top:-0.2rem">Synthèse globale du voyage (toutes dates). Le détail opérationnel est ci-dessus par départ.</p>';
                }
                html += '<div class="ws-md-stats-row">';
                html += '<span class="ws-md-stat-pill ok"><i class="fas fa-check-circle"></i> ' + (d.stats.validee || 0) + ' confirmées</span>';
                html += '<span class="ws-md-stat-pill wait"><i class="fas fa-hourglass-half"></i> ' + (d.stats.en_cours || 0) + ' en attente</span>';
                html += '<span class="ws-md-stat-pill cancel"><i class="fas fa-times-circle"></i> ' + (d.stats.annulee || 0) + ' annulées</span>';
                html += '</div></section>';
            }
            html += '</div>';
            return html;
        }
        var h = '<div class="ws-md-body-inner">';
        h += '<section class="ws-md-card"><div class="ws-md-section-head"><i class="fas fa-tag"></i> ' + escapeWsHtml(d.kind === 'vol' ? 'Vol' : 'Hébergement') + '</div>';
        if (d.departure_date) h += '<p style="margin:0;font-size:0.875rem;color:#334155"><strong>Départ</strong> · ' + escapeWsHtml(d.departure_date) + '</p>';
        h += '</section>';
        if (d.stats) {
            h += '<section class="ws-md-card"><div class="ws-md-section-head"><i class="fas fa-chart-bar"></i> Statistiques</div><div class="ws-md-stats-row">';
            h += '<span class="ws-md-stat-pill ok"><i class="fas fa-check-circle"></i> ' + (d.stats.validee || 0) + '</span>';
            h += '<span class="ws-md-stat-pill wait"><i class="fas fa-hourglass-half"></i> ' + (d.stats.en_cours || 0) + '</span>';
            h += '<span class="ws-md-stat-pill cancel"><i class="fas fa-times-circle"></i> ' + (d.stats.annulee || 0) + '</span>';
            h += '</div></section>';
        }
        h += '</div>';
        return h;
    }
    function renderWsModalFooter(d) {
        var r = d.routes || {};
        var f = d.form || {};
        var h = '';
        h += '<button type="button" class="ws-md-btn ws-md-btn-secondary" data-ws-md-close><i class="fas fa-times"></i> Fermer</button>';
        h += '<div class="ws-md-footer-actions">';
        if (r.reservations) {
            h += '<a href="' + r.reservations + '" class="ws-md-btn ws-md-btn-primary"><i class="fas fa-list-ul"></i> Voir les réservations</a>';
        }
        if (r.public_show) {
            h += '<a href="' + escapeWsHtml(r.public_show) + '" target="_blank" rel="noopener noreferrer" class="ws-md-btn ws-md-btn-outline"><i class="fas fa-external-link-alt"></i> Voir la page client</a>';
        }
        if (f.tour_id) {
            h += '<button type="button" class="ws-md-btn ws-md-btn-success" id="ws-md-btn-new-res"><i class="fas fa-suitcase-rolling"></i> Nouvelle réservation</button>';
        }
        h += '</div>';
        return h;
    }
    function openWsDetailModal(code) {
        wsDetailMap = parseWsDetailMap();
        var d = wsDetailMap[code];
        if (!d || !wsModalEl) return;
        if (wsMdTitle) wsMdTitle.textContent = d.title || '—';
        if (wsMdSub) {
            var subHtml = '';
            if (d.post_status_label) {
                subHtml += '<span class="ws-md-badge-status">' + escapeWsHtml(d.post_status_label) + '</span>';
            }
            var ids = [];
            if (d.wp_post_id) ids.push('WP #' + d.wp_post_id);
            if (d.laravel_voyage_id) ids.push('Laravel #' + d.laravel_voyage_id);
            if (ids.length) {
                subHtml += '<span style="color:#475569;font-weight:600">' + escapeWsHtml(ids.join(' · ')) + '</span>';
            }
            wsMdSub.innerHTML = subHtml || '<span style="color:#94a3b8">—</span>';
        }
        if (wsMdBody) wsMdBody.innerHTML = renderWsModalBody(d);
        if (wsMdFooter) {
            wsMdFooter.innerHTML = renderWsModalFooter(d);
            var nb = document.getElementById('ws-md-btn-new-res');
            if (nb && (d.route_reserver || (d.form && d.form.tour_id))) {
                nb.addEventListener('click', function onNewRes() {
                    nb.removeEventListener('click', onNewRes);
                    closeWsDetailModal();
                    if (d.route_reserver) {
                        window.location.href = d.route_reserver;
                    }
                });
            }
        }
        wsModalEl.classList.remove('hidden');
        wsModalEl.setAttribute('aria-hidden', 'false');
        document.body.classList.add('ws-md-open');
        document.body.style.overflow = 'hidden';
        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                wsModalEl.classList.add('ws-md-visible');
            });
        });
    }
    document.addEventListener('click', function (e) {
        var t = e.target;
        if (t && t.nodeType !== 1) t = t.parentElement;
        if (!t || !t.closest) return;
        var openBtn = t.closest('.btn-ws-open-detail');
        if (openBtn) {
            e.preventDefault();
            e.stopPropagation();
            var code = openBtn.getAttribute('data-row-code') || '';
            openWsDetailModal(code);
            return;
        }
        if (t.closest('[data-ws-md-close]')) {
            e.preventDefault();
            closeWsDetailModal();
            return;
        }
        if (t.getAttribute && t.getAttribute('data-ws-md-backdrop') !== null) {
            closeWsDetailModal();
        }
    }, true);

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape' || !wsModalEl) return;
        if (wsModalEl.classList.contains('hidden')) return;
        e.preventDefault();
        closeWsDetailModal();
    });

    var calEl = document.getElementById('workspace-calendar');
    var calJson = document.getElementById('workspace-calendar-json');
    var calendar = null;
    var btnList = document.getElementById('btn-view-list');
    var btnCal = document.getElementById('btn-view-calendar');
    var btnCatalog = document.getElementById('btn-view-catalog');
    var tableView = document.getElementById('ws-view-table');
    var catalogView = document.getElementById('ws-view-catalog');
    var calView = document.getElementById('reservations-calendar-view');

    try {
        if (calEl && calJson && typeof FullCalendar !== 'undefined') {
            var raw = [];
            try { raw = JSON.parse(calJson.textContent || '[]'); } catch (e) {}
            var colors = { package: '#f37a1f', vol: '#0083c4', hebergement: '#d97706' };
            var events = raw.map(function (e) {
                return {
                    title: e.title,
                    start: e.start,
                    backgroundColor: colors[e.type] || '#0083c4',
                    borderColor: colors[e.type] || '#0083c4',
                    textColor: e.type === 'hebergement' ? '#1f2937' : '#fff',
                    extendedProps: {
                        code: e.code,
                        voyage_id: e.voyage_id,
                        travel_date_id: e.travel_date_id,
                        prestation_type: e.prestation_type,
                        label: e.label,
                        create_url: e.create_url
                    }
                };
            });
            calendar = new FullCalendar.Calendar(calEl, {
                initialView: 'dayGridMonth',
                locale: 'fr',
                headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek' },
                buttonText: { today: "Aujourd'hui", month: 'Mois', week: 'Semaine' },
                events: events,
                height: 'auto',
                eventClick: function (info) {
                    info.jsEvent.preventDefault();
                    var p = info.event.extendedProps || {};
                    var code = String(p.code || '');
                    var createUrl = String(p.create_url || '');
                    if (createUrl) {
                        window.location.href = createUrl;
                        return;
                    }
                    if (btnList) btnList.click();
                    var safe = code.replace(/\\/g, '\\\\').replace(/"/g, '\\"');
                    var row = code
                        ? (document.querySelector('#ws-catalog-list .ws-catalog-row[data-row-code="' + safe + '"]')
                            || document.querySelector('#ws-catalog-table-body .ws-catalog-row[data-row-code="' + safe + '"]'))
                        : null;
                    if (row) {
                        row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        row.classList.add('ws-ring-pulse');
                        setTimeout(function () { row.classList.remove('ws-ring-pulse'); }, 1800);
                    }
                }
            });
        }
    } catch (calErr) {
        if (typeof console !== 'undefined' && console.warn) {
            console.warn('Workspace FullCalendar:', calErr);
        }
    }

    var filterForm = document.getElementById('catalogue-workspace');
    var viewInputEl = document.getElementById('ws-filter-view');
    var searchEl = document.getElementById('ws-filter-search');
    var typeEl = document.getElementById('ws-filter-type');
    var destinationEl = document.getElementById('ws-filter-destination');
    var dateFromEl = document.getElementById('ws-filter-date-from');
    var dateToEl = document.getElementById('ws-filter-date-to');
    var budgetMinEl = document.getElementById('ws-filter-budget-min');
    var budgetMaxEl = document.getElementById('ws-filter-budget-max');
    var budgetRangeMaxEl = document.getElementById('ws-budget-range-max');
    var budgetRangeValueEl = document.getElementById('ws-budget-range-value');
    var applyBtn = document.getElementById('ws-filters-apply');

    if (budgetRangeMaxEl && budgetMinEl && budgetMaxEl) {
        budgetMinEl.value = '0';
        budgetRangeMaxEl.addEventListener('input', function () {
            var maxVal = parseInt(budgetRangeMaxEl.value || '0', 10);
            if (isNaN(maxVal) || maxVal < 0) maxVal = 0;
            budgetMinEl.value = '0';
            budgetMaxEl.value = String(maxVal);
            if (budgetRangeValueEl) budgetRangeValueEl.textContent = maxVal + ' MAD';
        });

        budgetMaxEl.addEventListener('input', function () {
            var maxVal = parseInt(budgetMaxEl.value || '0', 10);
            if (isNaN(maxVal) || maxVal < 0) maxVal = 0;
            budgetMinEl.value = '0';
            budgetMaxEl.value = String(maxVal);
            budgetRangeMaxEl.value = String(maxVal);
            if (budgetRangeValueEl) budgetRangeValueEl.textContent = maxVal + ' MAD';
        });

        if (!budgetMaxEl.value) {
            budgetMaxEl.value = String(parseInt(budgetRangeMaxEl.value || '30000', 10));
        }
        budgetRangeMaxEl.value = budgetMaxEl.value;
        if (budgetRangeValueEl) budgetRangeValueEl.textContent = (parseInt(budgetMaxEl.value || '0', 10) || 0) + ' MAD';
    }

    function wsActivateView(mode) {
        if (btnList) btnList.classList.toggle('is-active', mode === 'list');
        if (btnCal) btnCal.classList.toggle('is-active', mode === 'calendar');
        if (btnCatalog) btnCatalog.classList.toggle('is-active', mode === 'catalog');
        if (tableView) tableView.classList.toggle('hidden', mode !== 'list');
        if (catalogView) catalogView.classList.toggle('hidden', mode !== 'catalog');
        if (calView) calView.classList.toggle('hidden', mode !== 'calendar');
        if (viewInputEl) viewInputEl.value = mode;
        if (mode === 'calendar' && calendar) {
            setTimeout(function () { calendar.render(); }, 80);
        }
        if (mode === 'list') {
            window.wsCurrentPage = 1;
            paginateWsRows();
        }
    }

    window.wsCurrentPage = 1;
    window.wsPerPage = 10;

    window.applyWsFilters = function applyWsFilters() {
        window.wsCurrentPage = 1;
        paginateWsRows();
    };

    function paginateWsRows() {
        var tbody = document.getElementById('ws-catalog-table-body');
        if (!tbody) return;
        var allRows = tbody.querySelectorAll('tr');
        var visibleRows = [];
        var emptyRowId = 'ws-table-empty-sellable-row';
        var existingEmptyRow = document.getElementById(emptyRowId);
        if (existingEmptyRow) {
            existingEmptyRow.remove();
        }
        allRows.forEach(function (row) {
            if (row.classList.contains('ws-table-empty-cell')) return;
            if (row.classList.contains('ws-catalog-section-divider')) {
                row.style.display = 'none';
                return;
            }
            var isSellable = row.getAttribute('data-is-sellable');
            if (isSellable === '0') {
                row.style.display = 'none';
                return;
            }
            if (!row.classList.contains('hidden')) visibleRows.push(row);
        });

        var total = visibleRows.length;
        var perPage = window.wsPerPage || 10;
        var currentPage = window.wsCurrentPage || 1;
        var totalPages = Math.max(1, Math.ceil(total / perPage));

        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;
        window.wsCurrentPage = currentPage;

        var start = (currentPage - 1) * perPage;
        var end = start + perPage;

        visibleRows.forEach(function (row, index) {
            row.style.display = (index >= start && index < end) ? '' : 'none';
        });

        if (total === 0) {
            var emptyRow = document.createElement('tr');
            emptyRow.id = emptyRowId;
            emptyRow.innerHTML = '<td colspan="8" class="ws-table-empty-cell"><div class="ws-catalog-empty ws-catalog-empty--inline"><div class="max-w-md mx-auto text-center py-10 px-6"><div class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 text-gray-400 mb-3 text-xl"><i class="fas fa-inbox"></i></div><p class="text-brand-dark font-bold text-base mb-2">Aucune offre réservable disponible pour le moment.</p></div></div></td>';
            tbody.appendChild(emptyRow);
        }

        renderPaginationControls(total, perPage, currentPage, totalPages);
    }

    function renderPaginationControls(total, perPage, currentPage, totalPages) {
        var info = document.getElementById('ws-pagination-info');
        var controls = document.getElementById('ws-pagination-controls');
        if (!info || !controls) return;

        if (total === 0) {
            info.textContent = '0 résultat';
            controls.innerHTML = '';
            return;
        }

        var start = (currentPage - 1) * perPage + 1;
        var end = Math.min(currentPage * perPage, total);
        info.textContent = start + '–' + end + ' sur ' + total;

        var html = '';
        html += '<button type="button" ' + (currentPage <= 1 ? 'disabled' : '') + ' data-ws-page="' + (currentPage - 1) + '" title="Précédent">Précédent</button>';

        var maxVisible = 5;
        var startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
        var endPage = Math.min(totalPages, startPage + maxVisible - 1);
        if (endPage - startPage + 1 < maxVisible) {
            startPage = Math.max(1, endPage - maxVisible + 1);
        }

        for (var p = startPage; p <= endPage; p++) {
            html += '<button type="button" class="' + (p === currentPage ? 'is-active' : '') + '" data-ws-page="' + p + '" title="Page ' + p + '" aria-current="' + (p === currentPage ? 'page' : 'false') + '">' + p + '</button>';
        }

        html += '<button type="button" ' + (currentPage >= totalPages ? 'disabled' : '') + ' data-ws-page="' + (currentPage + 1) + '" title="Suivant">Suivant</button>';

        controls.innerHTML = html;
    }

    if (btnList) btnList.addEventListener('click', function (event) {
        event.preventDefault();
        wsActivateView('list');
    });
    if (btnCal) btnCal.addEventListener('click', function (event) {
        event.preventDefault();
        wsActivateView('calendar');
    });
    if (btnCatalog) btnCatalog.addEventListener('click', function (event) {
        event.preventDefault();
        wsActivateView('catalog');
    });
    if (filterForm) {
        filterForm.addEventListener('submit', function () {
            if (applyBtn) applyBtn.disabled = true;
            if (viewInputEl && !viewInputEl.value) {
                viewInputEl.value = 'list';
            }
            if (typeof console !== 'undefined' && console.log) {
                console.log('[Workspace filter]', {
                    search: searchEl ? searchEl.value : '',
                    type: typeEl ? typeEl.value : '',
                    destination: destinationEl ? destinationEl.value : '',
                    date_from: dateFromEl ? dateFromEl.value : '',
                    date_to: dateToEl ? dateToEl.value : '',
                    budget_min: budgetMinEl ? budgetMinEl.value : '',
                    budget_max: budgetMaxEl ? budgetMaxEl.value : '',
                    view: viewInputEl ? viewInputEl.value : '',
                });
            }
        });
    }

    var perPageEl = document.getElementById('ws-per-page');
    var paginationControls = document.getElementById('ws-pagination-controls');
    if (perPageEl) {
        perPageEl.addEventListener('change', function () {
            window.wsPerPage = parseInt(this.value, 10) || 10;
            window.wsCurrentPage = 1;
            paginateWsRows();
        });
    }
    if (paginationControls) {
        paginationControls.addEventListener('click', function (e) {
            var btn = e.target.closest('button[data-ws-page]');
            if (!btn) return;
            var p = parseInt(btn.getAttribute('data-ws-page'), 10);
            if (!isNaN(p) && p > 0) {
                window.wsCurrentPage = p;
                paginateWsRows();
            }
        });
    }

    wsActivateView(@json($workspaceView));
    applyWsFilters();

});
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var detailMapEl = document.getElementById('ws-modal-detail-json');
    var modalEl = document.getElementById('ws-voyage-detail-modal');
    var titleEl = document.getElementById('ws-md-title');
    var subEl = document.getElementById('ws-md-sub');
    var bodyEl = document.getElementById('ws-md-body');
    var footerEl = document.getElementById('ws-md-footer');
    var btnCatalog = document.getElementById('btn-view-catalog');
    var btnList = document.getElementById('btn-view-list');
    var btnCalendar = document.getElementById('btn-view-calendar');
    var viewCatalog = document.getElementById('ws-view-catalog');
    var viewList = document.getElementById('ws-view-table');
    var viewCalendar = document.getElementById('reservations-calendar-view');
    var viewInputEl = document.getElementById('ws-filter-view');

    function parseDetails() {
        if (!detailMapEl) return {};
        try { return JSON.parse(detailMapEl.textContent || '{}'); } catch (error) { return {}; }
    }

    var details = parseDetails();

    function escapeHtml(value) {
        if (value == null || value === '') return '';
        var div = document.createElement('div');
        div.textContent = String(value);
        return div.innerHTML;
    }

    function normalizeWorkspaceButtons() {
        document.querySelectorAll('.btn-ws-open-detail').forEach(function (button) {
            button.classList.remove('btn-ws-open-detail');
            button.setAttribute('data-ws-detail-trigger', '1');
        });
        document.querySelectorAll('.btn-ws-open-reserve').forEach(function (button) {
            button.classList.remove('btn-ws-open-reserve');
            button.setAttribute('data-ws-reserve-trigger', '1');
        });
    }

    function setWorkspaceView(mode) {
        if (btnCatalog) btnCatalog.classList.toggle('is-active', mode === 'catalog');
        if (btnList) btnList.classList.toggle('is-active', mode === 'list');
        if (btnCalendar) btnCalendar.classList.toggle('is-active', mode === 'calendar');
        if (viewCatalog) viewCatalog.classList.toggle('hidden', mode !== 'catalog');
        if (viewList) viewList.classList.toggle('hidden', mode !== 'list');
        if (viewCalendar) viewCalendar.classList.toggle('hidden', mode !== 'calendar');
        if (viewInputEl) viewInputEl.value = mode;
        if (mode === 'calendar') {
            renderWorkspaceCalendar();
        }
    }

    function openModal(title, subHtml, bodyHtml, footerHtml) {
        if (!modalEl) return;
        if (titleEl) titleEl.textContent = title || '—';
        if (subEl) subEl.innerHTML = subHtml || '<span style="color:#94a3b8">—</span>';
        if (bodyEl) bodyEl.innerHTML = bodyHtml || '';
        if (footerEl) footerEl.innerHTML = footerHtml || '<button type="button" class="ws-md-btn ws-md-btn-secondary" data-ws-md-close><i class="fas fa-times"></i> Fermer</button>';
        modalEl.classList.remove('hidden');
        modalEl.classList.add('ws-md-visible');
        modalEl.setAttribute('aria-hidden', 'false');
        document.body.classList.add('ws-md-open');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        if (!modalEl) return;
        modalEl.classList.remove('ws-md-visible');
        modalEl.classList.add('hidden');
        modalEl.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('ws-md-open');
        document.body.style.overflow = 'auto';
    }

    function getDetail(code) {
        details = parseDetails();
        return details[code] || null;
    }

    function getDeparture(detail, travelDateId) {
        var departures = Array.isArray(detail && detail.departures) ? detail.departures : [];
        return departures.find(function (departure) {
            return String(departure.travel_date_id || '') === String(travelDateId || '');
        }) || null;
    }

    function modalSub(detail, departure) {
        var bits = [];
        if (detail.post_status_label) bits.push(detail.post_status_label);
        if (detail.wp_post_id) bits.push('WP #' + detail.wp_post_id);
        if (detail.laravel_voyage_id) bits.push('Laravel #' + detail.laravel_voyage_id);
        if (departure && departure.date_label) bits.push(departure.date_label);
        return '<span style="color:#475569;font-weight:600">' + escapeHtml(bits.join(' · ')) + '</span>';
    }

    function availabilityBadge(departure) {
        var statusKey = departure.status_key || 'unknown';
        var cls = 'ws-md-avail-badge ws-md-avail-badge--unknown';
        if (statusKey === 'available') cls = 'ws-md-avail-badge ws-md-avail-badge--ok';
        else if (statusKey === 'almost_full') cls = 'ws-md-avail-badge ws-md-avail-badge--warn';
        else if (statusKey === 'full') cls = 'ws-md-avail-badge ws-md-avail-badge--full';
        var label = departure.is_past ? 'Passé' : (departure.status_label || 'Disponible');
        return '<span class="' + cls + '">' + escapeHtml(label) + '</span>';
    }

    function departureRooms(detail, departure) {
        if (departure && Array.isArray(departure.rooms)) {
            return departure.rooms.filter(Boolean);
        }
        if (detail && Array.isArray(detail.rooms)) {
            return detail.rooms.filter(Boolean);
        }

        return [];
    }

    function commercialCommissionHtml(detail) {
        var s = (typeof window !== 'undefined' && window.wsModalSettings) ? window.wsModalSettings : {};
        if (!s.show_commission) return '';
        var commission = detail && detail.commission ? detail.commission : null;
        var html = '<section class="ws-md-card"><div class="ws-md-section-head"><i class="fas fa-hand-holding-usd"></i> Commission commerciale</div>';
        if (!commission || !commission.configured) {
            html += '<p class="ws-md-inline-note">' + escapeHtml((commission && commission.message) || 'Aucune commission configurée pour cette offre') + '</p></section>';
            return html;
        }
        html += '<div class="ws-md-commission-card">';
        if (s.show_commission_type) {
            html += '<span>' + escapeHtml(commission.type_label || 'Commission') + '</span>';
        }
        if (s.show_commission_amount) {
            if (commission.type === 'percentage' && s.show_commission_percentage) {
                html += '<strong>' + escapeHtml(commission.value_label || '') + ' = ' + escapeHtml(commission.estimated_label || '0 DH') + ' / voyageur</strong>';
            } else if (commission.type !== 'percentage' && s.show_commission_fixed) {
                html += '<strong>' + escapeHtml(commission.value_label || commission.estimated_label || '0 DH') + ' / voyageur</strong>';
            }
        }
        if (s.show_commission_agent && commission.agent_label) {
            html += '<p>Agent : ' + escapeHtml(commission.agent_label) + '</p>';
        }
        if (s.show_commission_branch && commission.branch_label) {
            html += '<p>Agence : ' + escapeHtml(commission.branch_label) + '</p>';
        }
        if (commission.basis_unit_price && s.show_commission_amount) {
            html += '<p>Estimation sur prix unitaire ' + escapeHtml(String(commission.basis_unit_price).replace('.', ',')) + ' ' + escapeHtml(commission.currency || 'MAD') + '.</p>';
        }
        if (s.show_commission_help) {
            html += '<p style="margin-top:0.5rem;font-size:0.75rem;color:#64748b;font-weight:600;">Montant indicatif, sujet à validation finale.</p>';
        }
        html += '</div></section>';
        return html;
    }

    function renderDepartureReport(detail, departure) {
        var s = (typeof window !== 'undefined' && window.wsModalSettings) ? window.wsModalSettings : {};
        if (!s.show_departure_report) return '';
        var reservations = departure.reservations || {};
        var alerts = Array.isArray(departure.alerts) ? departure.alerts : [];
        var capacity = departure.capacity != null ? Number(departure.capacity) : null;
        var remaining = departure.remaining != null ? Number(departure.remaining) : null;
        var fillPct = departure.fill_pct != null ? Number(departure.fill_pct) : 0;
        var rooms = departureRooms(detail, departure);
        var hasRooms = rooms.length > 0;
        var daysUntil = departure.days_until != null ? Number(departure.days_until) : null;
        var rules = [];
        var badgeClass = 'ws-md-report-badge ws-md-report-badge--neutral';

        if (!hasRooms) {
            rules.push({ text: 'Chambres non configurées', type: 'warn' });
        }
        if (alerts.length) {
            alerts.forEach(function (alert) {
                rules.push({ text: alert, type: 'warn' });
            });
        }
        if (remaining !== null && remaining <= 0) {
            rules.push({ text: 'Complet — ne pas vendre', type: 'danger' });
            badgeClass = 'ws-md-report-badge ws-md-report-badge--danger';
        } else if (remaining !== null && remaining < 5) {
            rules.push({ text: 'Stock critique (' + remaining + ' places)', type: 'danger' });
            badgeClass = 'ws-md-report-badge ws-md-report-badge--warn';
        }
        if (daysUntil !== null && daysUntil >= 0 && daysUntil < 7) {
            rules.push({ text: 'Départ imminent (' + daysUntil + ' jours)', type: 'warn' });
        }
        if (capacity !== null && capacity > 0 && fillPct < 30) {
            rules.push({ text: 'Faible remplissage (' + fillPct + '%)', type: 'info' });
        }
        if (!rules.length) {
            rules.push({ text: 'Départ standard', type: 'neutral' });
            badgeClass = 'ws-md-report-badge ws-md-report-badge--neutral';
        }

        var html = '<section class="ws-md-card"><div class="ws-md-section-head"><i class="fas fa-clipboard-check"></i> Rapport du départ</div>';
        html += '<div class="ws-md-report-card">';
        html += '<div class="ws-md-report-header">';
        html += '<span class="' + badgeClass + '">' + escapeHtml(rules[0].text) + '</span>';
        html += '</div>';
        if (rules.length > 1) {
            html += '<ul class="ws-md-report-list">';
            rules.forEach(function (rule) {
                var icon = rule.type === 'danger' ? 'fa-exclamation-circle' : (rule.type === 'warn' ? 'fa-exclamation-triangle' : 'fa-info-circle');
                var cls = 'ws-md-report-item--' + rule.type;
                html += '<li class="ws-md-report-item ' + cls + '"><i class="fas ' + icon + '"></i> ' + escapeHtml(rule.text) + '</li>';
            });
            html += '</ul>';
        }
        var recos = [];
        if (!hasRooms) recos.push('Configurer les chambres dans Laravel pour activer la réservation.');
        if (remaining !== null && remaining > 0 && remaining < 5) recos.push('Pousser la vente : stock très limité, urgence commerciale.');
        if (daysUntil !== null && daysUntil >= 0 && daysUntil < 7 && remaining > 0) recos.push('Contacter les prospects en attente pour ce départ.');
        if (capacity !== null && capacity > 0 && fillPct < 30 && remaining > 0) recos.push('Proposer une promotion ou un bonus pour booster les réservations.');
        if (recos.length) {
            html += '<div class="ws-md-report-recos">';
            html += '<strong><i class="fas fa-lightbulb"></i> Recommandations</strong>';
            html += '<ul>';
            recos.forEach(function (r) { html += '<li>' + escapeHtml(r) + '</li>'; });
            html += '</ul></div>';
        }
        html += '</div></section>';
        return html;
    }

    function departureBody(detail, departure) {
        var reservations = departure.reservations || {};
        var pax = departure.pax || {};
        var rooms = departureRooms(detail, departure);
        var alerts = Array.isArray(departure.alerts) ? departure.alerts : [];
        var fillPct = departure.fill_pct != null ? departure.fill_pct : 0;
        var rateClass = 'ws-md-departure-kpi--rate';
        if (fillPct >= 100) rateClass = 'ws-md-departure-kpi--rate-full';
        else if (fillPct >= 85) rateClass = 'ws-md-departure-kpi--rate-warn';
        else if (fillPct >= 50) rateClass = 'ws-md-departure-kpi--rate-ok';
        else rateClass = 'ws-md-departure-kpi--rate-low';
        var html = '<div class="ws-md-detail-panel">';
        html += '<section class="ws-md-card"><div class="ws-md-section-head"><i class="fas fa-route"></i> Détail du départ</div>';
        html += '<div class="ws-md-departure-kpi-grid">';
        html += '<div class="ws-md-departure-kpi ws-md-departure-kpi--neutral"><i class="fas fa-users"></i><span>Capacité</span><strong>' + escapeHtml(departure.capacity != null ? departure.capacity : '—') + '</strong></div>';
        html += '<div class="ws-md-departure-kpi ws-md-departure-kpi--ok"><i class="fas fa-check-circle"></i><span>Confirmées</span><strong>' + escapeHtml(pax.validee != null ? pax.validee : 0) + '</strong></div>';
        html += '<div class="ws-md-departure-kpi ws-md-departure-kpi--wait"><i class="fas fa-hourglass-half"></i><span>En attente</span><strong>' + escapeHtml(pax.en_cours != null ? pax.en_cours : 0) + '</strong></div>';
        html += '<div class="ws-md-departure-kpi ws-md-departure-kpi--cancel"><i class="fas fa-times-circle"></i><span>Annulées</span><strong>' + escapeHtml(pax.annulee != null ? pax.annulee : 0) + '</strong></div>';
        html += '<div class="ws-md-departure-kpi ws-md-departure-kpi--remain"><i class="fas fa-chair"></i><span>Restantes</span><strong>' + escapeHtml(departure.remaining != null ? departure.remaining : '—') + '</strong></div>';
        html += '<div class="ws-md-departure-kpi ' + rateClass + '"><i class="fas fa-chart-line"></i><span>Taux</span><strong>' + escapeHtml(fillPct + '%') + '</strong></div>';
        html += '</div>';
        html += '<div class="ws-md-progress ws-md-progress--dep" role="progressbar" aria-valuenow="' + fillPct + '" aria-valuemin="0" aria-valuemax="100"><div class="ws-md-progress-bar" style="width:' + fillPct + '%"></div></div>';
        if (departure.capacity_note && !rooms.length) html += '<p class="ws-md-inline-note">' + escapeHtml(departure.capacity_note) + '</p>';
        html += '<p class="ws-md-inline-note">Confirmées: ' + escapeHtml((pax.validee != null ? pax.validee : 0) + ' places dans ' + (reservations.validee != null ? reservations.validee : 0) + ' dossier(s)') + ' · En attente: ' + escapeHtml((pax.en_cours != null ? pax.en_cours : 0) + ' places dans ' + (reservations.en_cours != null ? reservations.en_cours : 0) + ' dossier(s)') + '</p>';
        if (alerts.length) html += '<p class="ws-md-inline-note" style="color:#b45309;font-weight:700">' + escapeHtml(alerts.join(' · ')) + '</p>';
        html += '<div class="ws-md-departure-info-grid" style="margin-top:1rem">';
        if (detail.duration) html += '<div class="ws-md-departure-info"><span>Durée</span><strong>' + escapeHtml(detail.duration) + '</strong></div>';
        html += '<div class="ws-md-departure-info"><span>Date sélectionnée</span><strong>' + escapeHtml(departure.date_label || '—') + '</strong></div>';
        if (detail.prices && detail.prices.adult_label) html += '<div class="ws-md-departure-info"><span>Prix à partir de</span><strong>' + escapeHtml(detail.prices.adult_label) + '</strong></div>';
        html += '<div class="ws-md-departure-info"><span>Statut du départ</span><strong>' + availabilityBadge(departure) + '</strong></div>';
        if (rooms.length) {
            html += '<div class="ws-md-departure-info ws-md-departure-info--rooms"><span>Chambres configurées</span><strong>' + rooms.length + ' type(s)</strong></div>';
        } else {
            html += '<div class="ws-md-departure-info"><span>Chambres disponibles</span><strong>Aucune chambre configurée</strong></div>';
        }
        html += '</div></section>';

        // Bloc détail chambres du départ
        if (rooms.length) {
            html += '<section class="ws-md-card"><div class="ws-md-section-head"><i class="fas fa-bed"></i> Chambres disponibles</div>';
            html += '<div class="ws-md-room-list">';
            rooms.forEach(function (room) {
                var totalRooms = Number(room.total_rooms != null ? room.total_rooms : (room.quantity != null ? room.quantity : 0));
                var remainingRooms = Number(room.remaining_rooms != null ? room.remaining_rooms : (room.available_rooms != null ? room.available_rooms : 0));
                var usedRooms = Number(room.used_rooms != null ? room.used_rooms : Math.max(0, totalRooms - remainingRooms));
                var remainingPlaces = Number(room.remaining_places != null ? room.remaining_places : (room.available_places != null ? room.available_places : 0));
                var capacityPerRoom = Number(room.capacity_per_room != null ? room.capacity_per_room : (room.capacity != null ? room.capacity : (room.capacity_total != null ? room.capacity_total : 0)));
                var supplement = Number(room.supplement != null ? room.supplement : 0);
                var isFull = remainingRooms <= 0;
                var statusClass = isFull ? 'ws-md-room-item--full' : (remainingRooms <= 2 ? 'ws-md-room-item--low' : 'ws-md-room-item--ok');
                html += '<div class="ws-md-room-item ' + statusClass + '" style="display:flex;align-items:center;justify-content:space-between;padding:0.5rem 0;border-bottom:1px solid #e2e8f0;">';
                html += '<div>';
                html += '<strong>' + escapeHtml(room.room_type || room.type || 'Chambre') + '</strong>';
                html += '<span style="display:block;font-size:0.75rem;color:#64748b">' + totalRooms + ' configurées, ' + usedRooms + ' utilisées, ' + remainingRooms + ' restantes</span>';
                html += '<span style="display:block;font-size:0.72rem;color:#94a3b8">Capacité ' + capacityPerRoom + ' pers./chambre' + (remainingPlaces > 0 ? ' · ' + remainingPlaces + ' places restantes' : '') + (supplement > 0 ? ' · Suppl. ' + supplement + ' MAD' : '') + '</span>';
                html += '</div>';
                html += '<div style="text-align:right">';
                if (isFull) {
                    html += '<span style="color:#991b1b;font-weight:700">Complet</span>';
                } else {
                    html += '<span style="color:#0e3a5a;font-weight:800">' + remainingRooms + ' / ' + totalRooms + '</span> <span style="font-size:0.75rem;color:#64748b">restantes</span>';
                }
                html += '</div>';
                html += '</div>';
            });
            html += '</div>';
            var allFull = rooms.every(function (room) {
                return Number(room.remaining_rooms != null ? room.remaining_rooms : (room.available_rooms != null ? room.available_rooms : 0)) <= 0;
            });
            if (allFull) {
                html += '<p style="margin:0.5rem 0 0;font-size:0.75rem;color:#991b1b;font-weight:700">Toutes les chambres sont complètes pour ce départ.</p>';
            }
            html += '</section>';
        }
        html += renderDepartureReport(detail, departure);
        html += commercialCommissionHtml(detail);
        html += '</div>';
        return html;
    }

    function resolveSelectedDeparture(detail, preferredTravelDateId) {
        var departures = Array.isArray(detail && detail.departures) ? detail.departures.slice() : [];
        if (!departures.length) return null;
        if (preferredTravelDateId) {
            var explicit = getDeparture(detail, preferredTravelDateId);
            if (explicit) return explicit;
        }
        var upcoming = departures.find(function (departure) {
            return !departure.is_past;
        });
        if (upcoming) return upcoming;
        return departures[departures.length - 1];
    }

    function selectorBody(code, detail, selectedDeparture) {
        var departures = Array.isArray(detail.departures) ? detail.departures : [];
        if (!departures.length) {
            return '<div class="ws-md-body-inner"><section class="ws-md-card"><div class="ws-md-section-head"><i class="fas fa-calendar-times"></i> Départs</div><p style="margin:0;color:#64748b;font-weight:600">Aucun départ configuré pour ce voyage.</p></section></div>';
        }
        var html = '<div class="ws-md-body-inner"><section class="ws-md-card"><div class="ws-md-section-head"><i class="fas fa-calendar-check"></i> Choisir une date de départ</div>';
        html += '<div class="ws-md-departure-tabs" role="tablist" aria-label="Dates de départ">';
        departures.forEach(function (departure) {
            var isActive = String(selectedDeparture.travel_date_id || '') === String(departure.travel_date_id || '');
            html += '<button type="button" class="ws-md-departure-tab' + (isActive ? ' is-active' : '') + '" role="tab" aria-selected="' + (isActive ? 'true' : 'false') + '" data-ws-select-departure="1" data-row-code="' + escapeHtml(code) + '" data-travel-date-id="' + escapeHtml(departure.travel_date_id) + '">';
            html += '<span class="ws-md-departure-tab-date">' + escapeHtml(departure.date_label || '—') + '</span>';
            html += '<span class="ws-md-departure-tab-status">' + availabilityBadge(departure) + '</span>';
            html += '</button>';
        });
        html += '</div>';
        html += departureBody(detail, selectedDeparture);
        html += '</section></div>';
        return html;
    }

    function selectorFooter(detail, selectedDeparture) {
        var html = '<button type="button" class="ws-md-btn ws-md-btn-secondary" data-ws-md-close><i class="fas fa-times"></i> Fermer</button><div class="ws-md-footer-actions">';
        var resUrl = (selectedDeparture && selectedDeparture.routes && selectedDeparture.routes.reservations)
            ? selectedDeparture.routes.reservations
            : (detail.routes && detail.routes.reservations ? detail.routes.reservations : null);
        if (resUrl) {
            html += '<a href="' + resUrl + '" class="ws-md-btn ws-md-btn-primary"><i class="fas fa-list-ul"></i> Voir les réservations</a>';
        } else {
            html += '<button type="button" disabled class="ws-md-btn ws-md-btn-disabled"><i class="fas fa-list-ul"></i> Voir les réservations</button>';
        }
        if (selectedDeparture) {
            var isPast = selectedDeparture.is_past === true;
            var remaining = Number(selectedDeparture.remaining != null ? selectedDeparture.remaining : 0);
            var statusKey = selectedDeparture.status_key || '';
            var isAvailable = (statusKey === 'available' || statusKey === 'almost_full');
            var hasReserveRoute = selectedDeparture.routes && selectedDeparture.routes.reserve;
            var rooms = departureRooms(detail, selectedDeparture);
            var hasRoomsConfigured = rooms.length > 0;
            var allRoomsFull = hasRoomsConfigured && rooms.every(function (room) {
                return Number(room.remaining_rooms != null ? room.remaining_rooms : (room.available_rooms != null ? room.available_rooms : 0)) <= 0;
            });
            var canReserve = !isPast && remaining > 0 && isAvailable && hasReserveRoute && (!hasRoomsConfigured || !allRoomsFull);
            if (canReserve) {
                html += '<a href="' + selectedDeparture.routes.reserve + '" class="ws-md-btn ws-md-btn-success"><i class="fas fa-suitcase-rolling"></i> Réserver ce départ</a>';
            } else {
                var title = isPast ? 'Départ passé' : (remaining <= 0 ? 'Aucune place restante' : (allRoomsFull ? 'Toutes les chambres sont complètes' : (!isAvailable ? 'Départ indisponible' : 'Réservation non configurée')));
                html += '<button type="button" disabled class="ws-md-btn ws-md-btn-disabled" title="' + title + '"><i class="fas fa-suitcase-rolling"></i> Réserver ce départ</button>';
            }
        } else {
            html += '<button type="button" disabled class="ws-md-btn ws-md-btn-disabled" title="Aucun départ configuré"><i class="fas fa-suitcase-rolling"></i> Réserver ce départ</button>';
        }
        html += '</div>';
        return html;
    }

    function departureFooter(code, detail, departure) {
        var html = '<button type="button" class="ws-md-btn ws-md-btn-secondary" data-ws-md-close><i class="fas fa-times"></i> Fermer</button><div class="ws-md-footer-actions">';
        var resUrl = (departure.routes && departure.routes.reservations)
            ? departure.routes.reservations
            : (detail.routes && detail.routes.reservations ? detail.routes.reservations : null);
        if (resUrl) html += '<a href="' + resUrl + '" class="ws-md-btn ws-md-btn-primary"><i class="fas fa-list-ul"></i> Voir les réservations</a>';
        else html += '<button type="button" disabled class="ws-md-btn ws-md-btn-disabled"><i class="fas fa-list-ul"></i> Voir les réservations</button>';
        if (departure) {
            var isPast = departure.is_past === true;
            var remaining = Number(departure.remaining != null ? departure.remaining : 0);
            var statusKey = departure.status_key || '';
            var isAvailable = (statusKey === 'available' || statusKey === 'almost_full');
            var hasReserveRoute = departure.routes && departure.routes.reserve;
            var rooms = departureRooms(detail, departure);
            var hasRoomsConfigured = rooms.length > 0;
            var allRoomsFull = hasRoomsConfigured && rooms.every(function (room) {
                return Number(room.remaining_rooms != null ? room.remaining_rooms : (room.available_rooms != null ? room.available_rooms : 0)) <= 0;
            });
            var canReserve = !isPast && remaining > 0 && isAvailable && hasReserveRoute && (!hasRoomsConfigured || !allRoomsFull);
            if (canReserve) {
                html += '<a href="' + departure.routes.reserve + '" class="ws-md-btn ws-md-btn-success"><i class="fas fa-suitcase-rolling"></i> Réserver ce départ</a>';
            } else {
                var title = isPast ? 'Départ passé' : (remaining <= 0 ? 'Aucune place restante' : (allRoomsFull ? 'Toutes les chambres sont complètes' : (!isAvailable ? 'Départ indisponible' : 'Réservation non configurée')));
                html += '<button type="button" disabled class="ws-md-btn ws-md-btn-disabled" title="' + title + '"><i class="fas fa-suitcase-rolling"></i> Réserver ce départ</button>';
            }
        }
        html += '</div>';
        return html;
    }

    function openSelector(code, preferredTravelDateId) {
        var detail = getDetail(code);
        if (!detail) return;
        var selectedDeparture = resolveSelectedDeparture(detail, preferredTravelDateId);
        openModal(detail.title || '—', modalSub(detail, selectedDeparture), selectorBody(code, detail, selectedDeparture), selectorFooter(detail, selectedDeparture));
    }

    function openDepartureDetail(code, travelDateId) {
        openSelector(code, travelDateId);
    }

    var calendarJsonEl = document.getElementById('workspace-calendar-json');
    var calendarMetaEl = document.getElementById('workspace-calendar-meta-json');
    var calendarRoot = document.getElementById('workspace-calendar');
    var calendarMeta = (function () {
        if (!calendarMetaEl) return {};
        try { return JSON.parse(calendarMetaEl.textContent || '{}'); } catch (error) { return {}; }
    })();
    var calendarEvents = (function () {
        if (!calendarJsonEl) return [];
        try {
            var parsed = JSON.parse(calendarJsonEl.textContent || '[]');
            return Array.isArray(parsed) ? parsed : [];
        } catch (error) {
            return [];
        }
    })();
    var calendarState = {
        currentMonth: resolveCalendarSeedDate(calendarMeta.seed_date, calendarEvents),
    };

    function resolveCalendarSeedDate(seedDate, events) {
        var base = typeof seedDate === 'string' && seedDate ? seedDate : (events[0] && events[0].departure_date ? events[0].departure_date : null);
        var date = base ? new Date(base + 'T00:00:00') : new Date();
        if (isNaN(date.getTime())) {
            date = new Date();
        }
        return new Date(date.getFullYear(), date.getMonth(), 1);
    }

    function formatCalendarMonth(date) {
        var label = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' }).format(date);
        return label.charAt(0).toUpperCase() + label.slice(1);
    }

    function formatCalendarDateLabel(date) {
        return new Intl.DateTimeFormat('fr-FR', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        }).format(date);
    }

    function normalizeCalendarStatus(event) {
        if (event && event.is_past) return 'past';
        var status = String((event && event.status) || 'unknown');
        if (status === 'ok') return 'available';
        if (status === 'low') return 'almost_full';
        if (['available', 'almost_full', 'full', 'past', 'unknown'].indexOf(status) !== -1) return status;
        return 'unknown';
    }

    function calendarStatusLabel(event) {
        var status = normalizeCalendarStatus(event);
        if (status === 'available') return 'Disponible';
        if (status === 'almost_full') return 'Presque complet';
        if (status === 'full') return 'Complet';
        if (status === 'past') return 'Passé';
        return 'À configurer';
    }

    function groupCalendarEventsByDate(events) {
        var map = {};
        events.forEach(function (event) {
            var key = String(event.departure_date || event.start || '');
            if (!key) return;
            if (!map[key]) map[key] = [];
            map[key].push(event);
        });
        Object.keys(map).forEach(function (key) {
            map[key].sort(function (a, b) {
                var remainingA = a.remaining_places == null ? Number.MAX_SAFE_INTEGER : Number(a.remaining_places);
                var remainingB = b.remaining_places == null ? Number.MAX_SAFE_INTEGER : Number(b.remaining_places);
                if (remainingA !== remainingB) return remainingA - remainingB;
                return String(a.short_title || a.title || '').localeCompare(String(b.short_title || b.title || ''), 'fr');
            });
        });
        return map;
    }

    function renderCalendarEventCard(event, compact) {
        var status = normalizeCalendarStatus(event);
        var confirmed = Number(event.confirmed_places != null ? event.confirmed_places : 0);
        var pending = Number(event.pending_places != null ? event.pending_places : 0);
        var remaining = event.remaining_places != null ? Number(event.remaining_places) : null;
        var title = escapeHtml(event.short_title || event.title || 'Départ');
        var destination = escapeHtml(event.destination || '');
        var price = escapeHtml(event.price || '');
        var statusLabel = escapeHtml(calendarStatusLabel(event));
        var metaLine = confirmed + ' / ' + pending + ' vendus';
        var stockLine = remaining !== null ? remaining + ' restantes' : 'Stock à confirmer';
        var classes = compact ? 'ws-calendar-mobile__item ws-calendar-event ws-calendar-event--' + status : 'ws-calendar-event ws-calendar-event--' + status;

        var html = '<button type="button" class="' + classes + '" data-cal-open-detail="1" data-row-code="' + escapeHtml(event.code || '') + '" data-travel-date-id="' + escapeHtml(event.travel_date_id || '') + '">';
        html += '<span class="' + (compact ? 'ws-calendar-mobile__title' : 'ws-calendar-event__title') + '">' + title + '</span>';
        if (destination) {
            html += '<span class="' + (compact ? 'ws-calendar-mobile__meta' : 'ws-calendar-event__destination') + '">' + destination + '</span>';
        }
        html += '<span class="' + (compact ? 'ws-calendar-mobile__meta' : 'ws-calendar-event__meta') + '">' + escapeHtml(metaLine) + (event.capacity != null ? ' · ' + escapeHtml(String(event.capacity)) + ' pl.' : '') + '</span>';
        html += '<span class="' + (compact ? 'ws-calendar-mobile__meta' : 'ws-calendar-event__price') + '">' + escapeHtml(stockLine) + (price ? ' · ' + price : '') + '</span>';
        html += '<span class="ws-calendar-event__status">' + statusLabel + '</span>';
        html += '</button>';

        return html;
    }

    function renderWorkspaceCalendar() {
        if (!calendarRoot) return;

        var events = Array.isArray(calendarEvents) ? calendarEvents.slice() : [];
        if (!events.length) {
            var resetUrl = escapeHtml((calendarMeta && calendarMeta.reset_url) || (calendarRoot.dataset ? calendarRoot.dataset.resetUrl : '') || '');
            calendarRoot.innerHTML = '<div class="ws-calendar-empty"><p class="ws-calendar-empty__title">Aucun départ trouvé pour cette période.</p><p class="ws-calendar-empty__text">Ajustez la période ou réinitialisez les filtres pour revoir le catalogue complet.</p>' + (resetUrl ? '<a href="' + resetUrl + '" class="ws-calendar-empty__link">Réinitialiser les filtres</a>' : '') + '</div>';
            return;
        }

        var currentMonth = new Date(calendarState.currentMonth.getFullYear(), calendarState.currentMonth.getMonth(), 1);
        var today = new Date();
        today.setHours(0, 0, 0, 0);

        var firstGridDate = new Date(currentMonth);
        var firstWeekday = (firstGridDate.getDay() + 6) % 7;
        firstGridDate.setDate(firstGridDate.getDate() - firstWeekday);

        var lastMonthDay = new Date(currentMonth.getFullYear(), currentMonth.getMonth() + 1, 0);
        var lastGridDate = new Date(lastMonthDay);
        var lastWeekday = (lastGridDate.getDay() + 6) % 7;
        lastGridDate.setDate(lastGridDate.getDate() + (6 - lastWeekday));

        var eventsByDate = groupCalendarEventsByDate(events);
        var currentMonthKey = currentMonth.getFullYear() + '-' + String(currentMonth.getMonth() + 1).padStart(2, '0');
        var monthEvents = events.filter(function (event) {
            return String(event.departure_date || '').slice(0, 7) === currentMonthKey;
        });
        var totalMonthEvents = monthEvents.length;

        var weekdayLabels = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
        var html = '<div class="ws-calendar-shell">';
        html += '<div class="ws-calendar-header">';
        html += '<div><h3 class="ws-calendar-header__title">' + escapeHtml(formatCalendarMonth(currentMonth)) + '</h3><p class="ws-calendar-header__meta">' + escapeHtml(String(totalMonthEvents)) + ' départ(s) affiché(s) pour ce mois.</p></div>';
        html += '<div class="ws-calendar-nav">';
        html += '<button type="button" class="ws-calendar-nav__btn" data-cal-nav="prev"><i class="fas fa-chevron-left"></i><span>Précédent</span></button>';
        html += '<button type="button" class="ws-calendar-nav__btn" data-cal-nav="today"><i class="far fa-dot-circle"></i><span>Aujourd’hui</span></button>';
        html += '<button type="button" class="ws-calendar-nav__btn" data-cal-nav="next"><span>Suivant</span><i class="fas fa-chevron-right"></i></button>';
        html += '</div></div>';

        html += '<div class="ws-calendar-grid">';
        weekdayLabels.forEach(function (label) {
            html += '<div class="ws-calendar-weekday">' + label + '</div>';
        });

        var cursor = new Date(firstGridDate);
        while (cursor <= lastGridDate) {
            var dateKey = cursor.toISOString().slice(0, 10);
            var isOutside = cursor.getMonth() !== currentMonth.getMonth();
            var isToday = cursor.getTime() === today.getTime();
            var dayEvents = eventsByDate[dateKey] || [];
            html += '<div class="ws-calendar-day' + (isOutside ? ' ws-calendar-day--outside' : '') + (isToday ? ' ws-calendar-day--today' : '') + '">';
            html += '<div class="ws-calendar-day__head"><span class="ws-calendar-day__date">' + escapeHtml(String(cursor.getDate())) + '</span>';
            html += '<span class="ws-calendar-day__count">' + (dayEvents.length ? escapeHtml(String(dayEvents.length)) + ' départ(s)' : '') + '</span></div>';
            html += '<div class="ws-calendar-day__events">';
            dayEvents.slice(0, 4).forEach(function (event) {
                html += renderCalendarEventCard(event, false);
            });
            if (dayEvents.length > 4) {
                html += '<div class="ws-calendar-event__meta">+' + escapeHtml(String(dayEvents.length - 4)) + ' autre(s)</div>';
            }
            html += '</div></div>';
            cursor.setDate(cursor.getDate() + 1);
        }
        html += '</div>';

        html += '<div class="ws-calendar-mobile">';
        if (!monthEvents.length) {
            html += '<div class="ws-calendar-empty"><p class="ws-calendar-empty__title">Aucun départ trouvé pour cette période.</p><p class="ws-calendar-empty__text">Naviguez au mois suivant ou réinitialisez les filtres.</p></div>';
        } else {
            var groupedMonthEvents = groupCalendarEventsByDate(monthEvents);
            Object.keys(groupedMonthEvents).sort().forEach(function (dateKey) {
                var date = new Date(dateKey + 'T00:00:00');
                html += '<section class="ws-calendar-mobile__day">';
                html += '<div class="ws-calendar-mobile__date">' + escapeHtml(formatCalendarDateLabel(date)) + '</div>';
                html += '<div class="ws-calendar-mobile__items">';
                groupedMonthEvents[dateKey].forEach(function (event) {
                    html += renderCalendarEventCard(event, true);
                });
                html += '</div></section>';
            });
        }
        html += '</div></div>';

        calendarRoot.innerHTML = html;
    }

    function handleReserve(code) {
        var detail = getDetail(code);
        if (!detail) return;
        var departures = Array.isArray(detail.departures) ? detail.departures : [];
        if (detail.kind === 'package' && departures.length > 1) {
            openSelector(code);
            return;
        }
        if (detail.kind === 'package' && departures.length === 1 && departures[0].routes && departures[0].routes.reserve) {
            window.location.href = departures[0].routes.reserve;
            return;
        }
        if (detail.route_reserver) window.location.href = detail.route_reserver;
    }

    if (btnCatalog) btnCatalog.addEventListener('click', function () { setWorkspaceView('catalog'); });
    if (btnList) btnList.addEventListener('click', function () { setWorkspaceView('list'); });
    if (btnCalendar) btnCalendar.addEventListener('click', function () { setWorkspaceView('calendar'); });
    setWorkspaceView(@json($workspaceView));
    window.addEventListener('resize', renderWorkspaceCalendar);
    normalizeWorkspaceButtons();

    document.addEventListener('click', function (event) {
        var target = event.target.closest('[data-ws-detail-trigger],[data-ws-reserve-trigger],[data-ws-select-departure],[data-ws-reserve-departure],[data-ws-md-close],[data-ws-md-backdrop],[data-cal-open-detail],[data-cal-nav]');
        if (!target) return;
        event.preventDefault();
        event.stopPropagation();
        if (target.hasAttribute('data-cal-nav')) {
            var action = target.getAttribute('data-cal-nav') || '';
            if (action === 'prev') {
                calendarState.currentMonth = new Date(calendarState.currentMonth.getFullYear(), calendarState.currentMonth.getMonth() - 1, 1);
            } else if (action === 'next') {
                calendarState.currentMonth = new Date(calendarState.currentMonth.getFullYear(), calendarState.currentMonth.getMonth() + 1, 1);
            } else {
                var now = new Date();
                calendarState.currentMonth = new Date(now.getFullYear(), now.getMonth(), 1);
            }
            renderWorkspaceCalendar();
            return;
        }
        if (target.hasAttribute('data-cal-open-detail')) {
            openDepartureDetail(target.getAttribute('data-row-code') || '', target.getAttribute('data-travel-date-id') || '');
            return;
        }
        if (target.hasAttribute('data-ws-detail-trigger')) {
            openSelector(target.getAttribute('data-row-code') || '', target.getAttribute('data-travel-date-id') || '');
            return;
        }
        if (target.hasAttribute('data-ws-reserve-trigger')) {
            handleReserve(target.getAttribute('data-row-code') || '');
            return;
        }
        if (target.hasAttribute('data-ws-select-departure')) {
            openDepartureDetail(target.getAttribute('data-row-code') || '', target.getAttribute('data-travel-date-id') || '');
            return;
        }
        if (target.hasAttribute('data-ws-reserve-departure')) {
            var detail = getDetail(target.getAttribute('data-row-code') || '');
            var departure = detail ? getDeparture(detail, target.getAttribute('data-travel-date-id') || '') : null;
            if (departure && departure.routes && departure.routes.reserve) window.location.href = departure.routes.reserve;
            return;
        }
        closeModal();
    }, true);
});
</script>
@endpush
