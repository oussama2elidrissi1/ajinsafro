@php
    use App\Services\View\AgentPortalLayout;
    use Carbon\Carbon;
    $usePortalTailwind = AgentPortalLayout::shouldUse(auth()->user());

    $workspaceCalendarEvents = $catalogRows->map(function ($r) {
        if (empty($r['departure_date']) || empty($r['voyage_id'])) {
            return null;
        }
        $name = (string) ($r['name'] ?? '');
        if (function_exists('mb_strlen') && mb_strlen($name) > 36) {
            $name = mb_substr($name, 0, 34).'…';
        }
        return [
            'title' => ($r['code'] ?? '').' — '.$name,
            'start' => Carbon::parse($r['departure_date'])->format('Y-m-d'),
            'type' => $r['type'] ?? 'package',
            'code' => $r['code'] ?? '',
            'voyage_id' => (int) $r['voyage_id'],
            'travel_date_id' => $r['travel_date_id'] ?? '',
            'prestation_type' => $r['type'] ?? 'package',
            'label' => ($r['name'] ?? '').' ('.($r['code'] ?? '').')',
        ];
    })->filter()->values()->all();
@endphp
@extends('layouts.master-ajinsafro')

@section('title', 'Espace réservation — Catalogue')

@push('styles')
@if(!$usePortalTailwind)
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
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
@endif
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
    .ws-ring-pulse { animation: wsPulse 1.6s ease-out 1; }
    @keyframes wsPulse {
        0% { box-shadow: 0 0 0 0 rgba(0, 131, 196, 0.45); }
        100% { box-shadow: 0 0 0 12px rgba(0, 131, 196, 0); }
    }
    #ws-catalog-table thead th { position: sticky; top: 0; z-index: 1; background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%); }
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
    /* Workspace catalogue — package meta compact (ligne date · prix · places + badges chambres) */
    #ws-catalog-table .ws-room-badge {
        max-width: 100%;
    }
    #ws-catalog-table .ws-room-badge:hover {
        box-shadow: 0 2px 10px rgba(14, 58, 90, 0.08);
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
        max-height: 90vh;
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
    .ws-md-btn-primary {
        background: #0e3a5a;
        color: #fff;
        border-color: #0e3a5a;
    }
    .ws-md-btn-primary:hover {
        background: #0083c4;
        border-color: #0083c4;
        color: #fff;
    }
    .ws-md-btn-success {
        background: #059669;
        color: #fff;
        border-color: #059669;
    }
    .ws-md-btn-success:hover {
        background: #047857;
        border-color: #047857;
        color: #fff;
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
    body.ws-md-open {
        overflow: hidden !important;
    }
</style>
@endpush

@section('content')
<div class="fade-in max-w-[1680px] mx-auto pb-10">
    {{-- En-tête --}}
    <div class="relative overflow-hidden rounded-2xl border border-gray-200/80 bg-gradient-to-br from-white via-[#f8fcfe] to-[#e6f3fa]/50 shadow-custom mb-8 px-5 sm:px-8 py-6 sm:py-8">
        <div class="absolute top-0 right-0 w-64 h-64 bg-brand-blue/5 rounded-full blur-3xl -translate-y-1/2 translate-x-1/3 pointer-events-none"></div>
        <div class="relative flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
            <div class="min-w-0">
                <p class="text-[10px] sm:text-xs font-bold uppercase tracking-[0.2em] text-brand-blue/80 mb-2">Catalogue réservable</p>
                <h1 class="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-brand-dark tracking-tight">Espace réservation</h1>
                <p class="text-sm text-gray-600 mt-2 max-w-2xl leading-relaxed">
                    Même catalogue que <strong>Circuits / voyages</strong> (tours WordPress <span class="font-mono text-xs bg-white/80 px-1 rounded">st_tours</span>), enrichi par la fiche Laravel quand <span class="font-mono text-xs bg-white/80 px-1 rounded">voyages.wp_post_id</span> correspond. Vols et hébergements en lignes supplémentaires pour les voyages liés.
                </p>
                <div class="flex flex-wrap gap-2 sm:gap-3 mt-5">
                    <span class="inline-flex items-center gap-2 rounded-xl bg-white/90 border border-brand-blue/15 px-3 py-2 text-xs font-semibold text-brand-dark shadow-sm">
                        <i class="fas fa-suitcase-rolling text-brand-blue"></i>
                        {{ $catalogPackageCount ?? $catalogRows->where('type', 'package')->count() }} voyage(s)
                    </span>
                    <span class="inline-flex items-center gap-2 rounded-xl bg-white/90 border border-gray-200/80 px-3 py-2 text-xs font-semibold text-gray-700 shadow-sm">
                        <i class="fas fa-layer-group text-brand-orange"></i>
                        {{ $catalogTotalCount ?? $catalogRows->count() }} ligne(s) prestation
                    </span>
                </div>
            </div>
            <div class="flex flex-col sm:flex-row gap-3 shrink-0">
                <a href="{{ route('admin.circuits.voyages.index') }}" class="inline-flex items-center justify-center gap-2 text-sm font-semibold text-gray-700 bg-white border border-gray-200 rounded-xl px-5 py-3 shadow-sm hover:border-brand-blue/40 hover:text-brand-blue transition-all">
                    <i class="fas fa-route"></i> Circuits / voyages
                </a>
                <a href="{{ route('admin.reservations.toutes') }}" class="inline-flex items-center justify-center gap-2 text-sm font-bold text-white bg-brand-dark hover:bg-brand-blue rounded-xl px-5 py-3 shadow-ws-bar transition-colors">
                    <i class="fas fa-list-ul"></i> Toutes les réservations
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 text-emerald-900 px-4 py-3 text-sm font-medium shadow-sm">{{ session('success') }}</div>
    @endif

    @if(config('app.debug') && isset($catalogMeta))
        <div class="mb-6 rounded-2xl border border-amber-200/90 bg-amber-50/95 px-4 py-3 text-xs text-amber-950 shadow-sm">
            <p class="font-bold uppercase tracking-wide text-amber-800 mb-1">Debug catalogue (APP_DEBUG)</p>
            @if(!empty($catalogMeta['wp_connection_failed']))
                <p class="text-red-800 font-semibold">Connexion WordPress indisponible — aucune ligne catalogue.</p>
            @else
                <p class="text-[11px] text-amber-900/90 mb-2 leading-relaxed">
                    <strong>wp_tours</strong> = nombre de tours WordPress (comme Circuits / voyages). <strong>total_rows</strong> = packages + vols + hébergements dans le tableau.
                </p>
                <p class="font-mono leading-relaxed">
                    wp_tours={{ (int) ($catalogMeta['wp_tour_count'] ?? 0) }},
                    laravel_matched={{ (int) ($catalogMeta['laravel_voyage_matched'] ?? 0) }},
                    packages={{ (int) ($catalogMeta['package_rows'] ?? 0) }},
                    vols={{ (int) ($catalogMeta['vol_rows'] ?? 0) }},
                    hébergements={{ (int) ($catalogMeta['hebergement_rows'] ?? 0) }},
                    total_rows={{ (int) ($catalogMeta['total_rows'] ?? ($catalogTotalCount ?? $catalogRows->count())) }}
                </p>
                @php
                    $p = (int) ($catalogMeta['package_rows'] ?? 0);
                    $v = (int) ($catalogMeta['vol_rows'] ?? 0);
                    $h = (int) ($catalogMeta['hebergement_rows'] ?? 0);
                    $chk = $p + $v + $h;
                    $tot = (int) ($catalogMeta['total_rows'] ?? $catalogRows->count());
                @endphp
                @if($chk === $tot && $p > 0)
                    <p class="text-[11px] text-emerald-800 mt-2 font-medium">Vérification : {{ $p }} + {{ $v }} + {{ $h }} = {{ $tot }} (cohérent).</p>
                @endif
                @if(!empty($catalogMeta['wp_tour_ids']))
                    <p class="text-[10px] font-mono mt-2 break-all text-amber-950/90"><span class="font-sans font-bold text-amber-800">IDs WP (ordre Circuits / voyages, desc ID)</span> : {{ implode(', ', $catalogMeta['wp_tour_ids']) }}</p>
                @endif
                @if(!empty($catalogMeta['laravel_wp_post_ids_matched']))
                    <p class="text-[10px] font-mono mt-1 break-all text-amber-950/90"><span class="font-sans font-bold text-amber-800">wp_post_id Laravel liés</span> : {{ implode(', ', $catalogMeta['laravel_wp_post_ids_matched']) }}</p>
                @endif
                @if(!empty($catalogMeta['wp_tour_ids_without_laravel']))
                    <p class="text-[10px] font-mono mt-1 break-all text-amber-900"><span class="font-sans font-bold">Tours WP sans fiche Laravel</span> : {{ implode(', ', $catalogMeta['wp_tour_ids_without_laravel']) }}</p>
                @endif
                @if(!empty($catalogMeta['laravel_duplicates_wp_post_id']))
                    <p class="text-[10px] mt-2 text-red-800 font-semibold">Doublons voyages.wp_post_id (plusieurs lignes Laravel) :</p>
                    <pre class="text-[10px] font-mono mt-1 overflow-x-auto bg-white/60 rounded-lg p-2 border border-red-100">{{ json_encode($catalogMeta['laravel_duplicates_wp_post_id'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                @endif
                @if(!empty($catalogMeta['package_price_debug']))
                    <p class="text-[10px] font-bold text-amber-900 mt-3 mb-1">Prix packages (meta WP <code class="font-mono">adult_price</code> = colonne Prix Adulte Circuits / voyages)</p>
                    <div class="overflow-x-auto max-h-48 overflow-y-auto border border-amber-200/80 rounded-lg bg-white/70">
                        <table class="w-full text-[9px] font-mono">
                            <thead class="bg-amber-100/80 text-left">
                                <tr>
                                    <th class="p-1.5">WP ID</th>
                                    <th class="p-1.5">meta brut</th>
                                    <th class="p-1.5">parsé</th>
                                    <th class="p-1.5">Laravel price_from</th>
                                    <th class="p-1.5">Affiché</th>
                                    <th class="p-1.5">Source</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($catalogMeta['package_price_debug'] as $d)
                                    <tr class="border-t border-amber-100">
                                        <td class="p-1.5 align-top">{{ $d['wp_post_id'] ?? '' }}</td>
                                        <td class="p-1.5 align-top break-all max-w-[100px]">{{ is_scalar($d['adult_price_meta_raw'] ?? null) ? $d['adult_price_meta_raw'] : json_encode($d['adult_price_meta_raw']) }}</td>
                                        <td class="p-1.5 align-top">{{ $d['parsed_wp_adult'] ?? '—' }}</td>
                                        <td class="p-1.5 align-top">{{ $d['laravel_price_from'] ?? '—' }}</td>
                                        <td class="p-1.5 align-top font-bold">{{ $d['price_label_final'] ?? '—' }}</td>
                                        <td class="p-1.5 align-top">{{ $d['price_source'] ?? '' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
                @if(!empty($catalogMeta['package_departure_debug']))
                    @if(!empty($catalogMeta['package_departure_source_doc']))
                        <p class="text-[10px] text-slate-700 mt-3 mb-1 leading-snug">{{ $catalogMeta['package_departure_source_doc'] }}</p>
                    @endif
                    <p class="text-[10px] font-bold text-amber-900 mb-1">Départs packages (Disponibilité = <code class="font-mono">aj_travel_dates</code> / <code class="font-mono">TravelDate</code>)</p>
                    <div class="overflow-x-auto max-h-56 overflow-y-auto border border-amber-200/80 rounded-lg bg-white/70">
                        <table class="w-full text-[9px] font-mono">
                            <thead class="bg-amber-100/80 text-left">
                                <tr>
                                    <th class="p-1.5">WP ID</th>
                                    <th class="p-1.5">Laravel #</th>
                                    <th class="p-1.5">Dates actives (liste)</th>
                                    <th class="p-1.5">ID retenu</th>
                                    <th class="p-1.5">Date affichée</th>
                                    <th class="p-1.5">Passé</th>
                                    <th class="p-1.5">Flags</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($catalogMeta['package_departure_debug'] as $dd)
                                    <tr class="border-t border-amber-100">
                                        <td class="p-1.5 align-top">{{ $dd['wp_post_id'] ?? '' }}</td>
                                        <td class="p-1.5 align-top">{{ $dd['laravel_voyage_id'] ?? '—' }}</td>
                                        <td class="p-1.5 align-top break-all max-w-[140px]">{{ !empty($dd['active_travel_dates_ymd']) ? implode(', ', $dd['active_travel_dates_ymd']) : '—' }}</td>
                                        <td class="p-1.5 align-top">{{ $dd['picked_travel_date_id'] ?? '—' }}</td>
                                        <td class="p-1.5 align-top font-bold">{{ $dd['picked_date_ymd'] ?? '—' }}</td>
                                        <td class="p-1.5 align-top">{{ !empty($dd['workspace_display_is_past']) ? 'oui' : 'non' }}</td>
                                        <td class="p-1.5 align-top">
                                            @if(!empty($dd['no_laravel_voyage']))<span class="text-amber-800">sans Laravel</span>@endif
                                            @if(!empty($dd['no_availability_rows']))<span class="text-red-800">aucune dispo</span>@endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
                @if(!empty($catalogMeta['package_places_debug']))
                    @if(!empty($catalogMeta['package_places_source_doc']))
                        <p class="text-[10px] text-slate-700 mt-3 mb-1 leading-snug">{{ $catalogMeta['package_places_source_doc'] }}</p>
                    @endif
                    <p class="text-[10px] font-bold text-amber-900 mb-1">Places / chambres (échantillon max 8 packages Laravel — même calcul que l’édition voyage)</p>
                    <pre class="text-[9px] font-mono mt-1 overflow-x-auto max-h-64 overflow-y-auto bg-white/70 rounded-lg p-2 border border-amber-200/80 whitespace-pre-wrap">{{ json_encode($catalogMeta['package_places_debug'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                @endif
            @endif
        </div>
    @endif

    <div id="reservations-main-content" class="space-y-6">
        {{-- Filtres --}}
        <div class="bg-white p-4 sm:p-5 rounded-2xl shadow-custom border border-gray-100/90 space-y-3">
            <div class="flex flex-col xl:flex-row xl:flex-wrap xl:items-center gap-4">
                <div class="flex-1 min-w-[220px] relative">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                    <input type="text" id="ws-filter-search" placeholder="Rechercher par nom, code, sous-titre…" autocomplete="off"
                        class="w-full pl-11 pr-4 py-3 bg-gray-50/90 border border-gray-100 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-blue/25 focus:border-brand-blue focus:bg-white text-brand-dark font-medium placeholder-gray-400 transition-all">
                </div>
                <div class="flex flex-col sm:flex-row gap-3 flex-1 min-w-0">
                    <select id="ws-filter-type" class="sm:min-w-[160px] bg-gray-50/90 border border-gray-100 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-blue/25 focus:border-brand-blue text-brand-dark font-semibold cursor-pointer">
                        <option value="all">Tous les types</option>
                        <option value="package">Packages</option>
                        <option value="vol">Vols</option>
                        <option value="hebergement">Hébergements</option>
                    </select>
                    <div class="relative flex items-center flex-1 min-w-[200px] bg-gray-50/90 border border-gray-100 rounded-xl px-4 py-2.5 focus-within:ring-2 focus-within:ring-brand-blue/25 focus-within:border-brand-blue focus-within:bg-white transition-all">
                        <i class="far fa-calendar-alt text-brand-blue mr-3 shrink-0"></i>
                        <input type="text" id="ws-date-range-picker" readonly placeholder="Plage de dates de départ…" class="bg-transparent border-none outline-none text-brand-dark font-medium text-sm w-full cursor-pointer placeholder-gray-400">
                    </div>
                    <button type="button" id="ws-filters-reset" class="shrink-0 px-4 py-3 rounded-xl text-sm font-bold text-brand-blue border border-brand-blue/30 bg-white hover:bg-brand-light/80 transition-colors" title="Effacer recherche, type et dates">
                        Réinitialiser
                    </button>
                </div>
                <div class="flex items-center justify-between sm:justify-end gap-3 xl:ml-auto">
                    <span class="text-xs text-gray-500 font-medium whitespace-nowrap"><span id="ws-row-visible-count">{{ $catalogRows->count() }}</span> / {{ $catalogRows->count() }} affichée(s)</span>
                    <div class="inline-flex bg-gray-100/90 rounded-xl p-1 border border-gray-100">
                        <button type="button" id="btn-view-calendar" class="px-3 sm:px-4 py-2 rounded-lg text-gray-500 hover:text-brand-blue font-bold text-xs flex items-center gap-2 transition-all">
                            <i class="far fa-calendar-alt"></i><span class="hidden sm:inline">Calendrier</span>
                        </button>
                        <button type="button" id="btn-view-list" class="px-3 sm:px-4 py-2 rounded-lg bg-white shadow-md text-brand-blue font-bold text-xs flex items-center gap-2 border border-gray-100/80">
                            <i class="fas fa-list"></i><span class="hidden sm:inline">Liste</span>
                        </button>
                    </div>
                </div>
            </div>
            <p class="text-[11px] text-gray-500">La plage de dates filtre sur les lignes qui ont une <strong>date de départ</strong> ; les voyages sans date (—) restent affichés. Utilisez <strong>Réinitialiser</strong> si le tableau semble incomplet.</p>
        </div>

        {{-- Tableau --}}
        <div id="reservations-list-view" class="bg-white rounded-2xl shadow-custom border border-gray-100/90 overflow-hidden">
            <div class="px-5 sm:px-6 py-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 bg-gradient-to-r from-gray-50/80 to-white">
                <h2 class="font-bold text-lg text-brand-dark flex items-center gap-2">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-light text-brand-blue"><i class="fas fa-th-list text-sm"></i></span>
                    Prestations réservables
                </h2>
                <p class="text-xs text-gray-500">Statistiques = dossiers sur le <strong class="text-brand-dark">voyage</strong> (toutes dates confondues).</p>
            </div>
            <div class="overflow-x-auto max-h-[min(72vh,920px)] overflow-y-auto -mx-px">
                <table class="w-full text-left border-collapse min-w-[1040px]" id="ws-catalog-table">
                    <thead>
                        <tr class="text-[10px] sm:text-[11px] font-bold text-gray-500 uppercase tracking-wider border-b border-gray-200">
                            <th class="py-3.5 px-4 sm:px-5 w-[120px]">Réf. &amp; type</th>
                            <th class="py-3.5 px-4 sm:px-5 min-w-[200px]">Prestation</th>
                            <th class="py-3.5 px-4 sm:px-5 w-[130px]">Départ</th>
                            <th class="py-3.5 px-3 text-center w-[168px]">Statistiques</th>
                            <th class="py-3.5 px-3 sm:px-4 text-right min-w-[320px]">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm">
                        @forelse($catalogRows as $row)
                            @include('admin.reservations.workspace.partials.catalog-row', ['row' => $row])
                        @empty
                            <tr>
                                <td colspan="5" class="py-16 px-6">
                                    <div class="max-w-md mx-auto text-center">
                                        <div class="inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-gray-100 text-gray-400 mb-4 text-2xl">
                                            <i class="fas fa-inbox"></i>
                                        </div>
                                        <p class="text-brand-dark font-bold text-lg mb-2">Aucun voyage dans le catalogue</p>
                                        <p class="text-gray-500 text-sm mb-6">Créez ou synchronisez des fiches dans la table Laravel <strong>voyages</strong>, ou ouvrez les circuits WordPress pour les lier.</p>
                                        <a href="{{ route('admin.circuits.voyages.index') }}" class="inline-flex items-center gap-2 rounded-xl bg-brand-blue text-white font-bold text-sm px-5 py-3 hover:bg-brand-dark transition-colors">
                                            <i class="fas fa-plus-circle"></i> Gérer les voyages
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Calendrier --}}
        <div id="reservations-calendar-view" class="bg-white p-4 sm:p-6 rounded-2xl shadow-custom border border-gray-100/90 hidden">
            <p class="text-sm text-gray-600 mb-4">Cliquez sur un événement pour ouvrir le formulaire <strong>Réserver</strong> de la ligne correspondante (retour automatique en vue liste).</p>
            <div id="workspace-calendar" class="w-full min-h-[540px] fc-workspace"></div>
        </div>
    </div>

    {{-- Formulaire réservation --}}
    <div id="add-reservation-view" class="w-full space-y-6 hidden mt-10">
        <div class="flex items-center gap-4 bg-white p-5 sm:p-6 rounded-2xl shadow-custom border border-gray-100">
            <button type="button" id="btn-back-from-add-reservation" class="w-11 h-11 rounded-full bg-gray-50 hover:bg-brand-light text-gray-500 hover:text-brand-blue flex items-center justify-center transition-colors border border-gray-200 shadow-sm shrink-0" title="Retour au catalogue">
                <i class="fas fa-arrow-left"></i>
            </button>
            <div>
                <h2 class="text-xl font-bold text-brand-dark flex items-center gap-2">
                    <i class="fas fa-user-plus text-brand-blue"></i> Nouvelle réservation
                </h2>
                <p class="text-xs text-gray-500 mt-1 font-medium">Prestation : <span id="add-res-prestation-name" class="text-brand-dark font-bold">—</span></p>
            </div>
        </div>
        <div class="bg-white p-5 sm:p-8 rounded-2xl shadow-custom border border-gray-100">
            @include('admin.reservations.workspace.partials.form', ['clients' => $clients])
        </div>
    </div>
</div>

<script type="application/json" id="workspace-calendar-json">{!! json_encode($workspaceCalendarEvents, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>
<script type="application/json" id="ws-modal-detail-json">{!! json_encode($catalogRows->mapWithKeys(fn ($r) => [($r['code'] ?? '') => $r['modal_detail'] ?? null])->filter(fn ($v) => $v !== null), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>
@endsection

@push('body-end')
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
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/fr.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
<script src="{{ asset('js/reservation-workspace.js') }}"></script>
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
            if (d.places && d.places.state === 'ok' && d.places.total != null) {
                var pct = d.places.fill_pct != null ? d.places.fill_pct : 0;
                html += '<section class="ws-md-card" aria-labelledby="ws-sec-places">';
                html += '<div class="ws-md-section-head" id="ws-sec-places"><i class="fas fa-users" aria-hidden="true"></i> Places</div>';
                html += '<div class="ws-md-places-row">';
                html += '<div class="ws-md-stat-box"><span>Total</span><strong>' + d.places.total + '</strong></div>';
                html += '<div class="ws-md-stat-box"><span>Réservées</span><strong>' + d.places.reserved + '</strong></div>';
                html += '<div class="ws-md-stat-box"><span>Disponibles</span><strong>' + (d.places.remaining != null ? d.places.remaining : '—') + '</strong></div>';
                html += '</div>';
                html += '<div class="ws-md-progress" role="progressbar" aria-valuenow="' + pct + '" aria-valuemin="0" aria-valuemax="100"><div class="ws-md-progress-bar" style="width:' + pct + '%"></div></div>';
                html += '<p style="margin:0.5rem 0 0;font-size:0.7rem;color:#94a3b8;font-weight:600">' + pct + '% des places réservées</p>';
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
        if (f.tour_id) {
            h += '<button type="button" class="ws-md-btn ws-md-btn-success" id="ws-md-btn-new-res"><i class="fas fa-suitcase-rolling"></i> Nouvelle réservation</button>';
        }
        if (r.edit_voyage) {
            h += '<a href="' + r.edit_voyage + '" class="ws-md-btn ws-md-btn-outline"><i class="fas fa-edit"></i> Modifier le voyage</a>';
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
            if (nb && d.form && d.form.tour_id) {
                nb.addEventListener('click', function onNewRes() {
                    nb.removeEventListener('click', onNewRes);
                    closeWsDetailModal();
                    if (typeof window.wsOpenReservationForm === 'function') {
                        window.wsOpenReservationForm({
                            tourId: d.form.tour_id,
                            travelDateId: d.form.travel_date_id || '',
                            type: d.form.prestation_type || 'package',
                            name: d.form.label || d.title || ''
                        });
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

    if (typeof flatpickr !== 'undefined') {
        flatpickr('#ws-date-range-picker', {
            mode: 'range',
            dateFormat: 'Y-m-d',
            locale: 'fr',
            onChange: function () { applyWsFilters(); }
        });
    }

    var calEl = document.getElementById('workspace-calendar');
    var calJson = document.getElementById('workspace-calendar-json');
    var calendar = null;
    var btnList = document.getElementById('btn-view-list');
    var btnCal = document.getElementById('btn-view-calendar');
    var listView = document.getElementById('reservations-list-view');
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
                        label: e.label
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
                    if (btnList) btnList.click();
                    var safe = code.replace(/\\/g, '\\\\').replace(/"/g, '\\"');
                    var row = code ? document.querySelector('tr.ws-catalog-row[data-row-code="' + safe + '"]') : null;
                    if (row) {
                        row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        row.classList.add('ws-ring-pulse');
                        setTimeout(function () { row.classList.remove('ws-ring-pulse'); }, 1800);
                    }
                    var btn = code ? document.querySelector('.btn-show-add-reservation[data-row-code="' + safe + '"]') : null;
                    if (btn) btn.click();
                }
            });
        }
    } catch (calErr) {
        if (typeof console !== 'undefined' && console.warn) {
            console.warn('Workspace FullCalendar:', calErr);
        }
    }

    if (btnList && btnCal && listView && calView) {
        btnList.addEventListener('click', function () {
            btnList.classList.add('bg-white', 'shadow-md', 'text-brand-blue', 'border', 'border-gray-100/80');
            btnList.classList.remove('text-gray-500');
            btnCal.classList.remove('bg-white', 'shadow-md', 'text-brand-blue', 'border', 'border-gray-100/80');
            btnCal.classList.add('text-gray-500');
            listView.classList.remove('hidden');
            calView.classList.add('hidden');
        });
        btnCal.addEventListener('click', function () {
            btnCal.classList.add('bg-white', 'shadow-md', 'text-brand-blue', 'border', 'border-gray-100/80');
            btnCal.classList.remove('text-gray-500');
            btnList.classList.remove('bg-white', 'shadow-md', 'text-brand-blue', 'border', 'border-gray-100/80');
            btnList.classList.add('text-gray-500');
            listView.classList.add('hidden');
            calView.classList.remove('hidden');
            if (calendar) setTimeout(function () { calendar.render(); }, 80);
        });
    }

    var searchEl = document.getElementById('ws-filter-search');
    var typeEl = document.getElementById('ws-filter-type');
    var rangeInput = document.getElementById('ws-date-range-picker');
    var resetBtn = document.getElementById('ws-filters-reset');
    if (searchEl) searchEl.addEventListener('input', applyWsFilters);
    if (typeEl) typeEl.addEventListener('change', applyWsFilters);
    if (resetBtn) {
        resetBtn.addEventListener('click', function () {
            if (searchEl) searchEl.value = '';
            if (typeEl) typeEl.value = 'all';
            if (rangeInput && rangeInput._flatpickr) rangeInput._flatpickr.clear();
            applyWsFilters();
        });
    }

    window.applyWsFilters = function applyWsFilters() {
        var q = (searchEl && searchEl.value) ? searchEl.value.toLowerCase().trim() : '';
        var t = typeEl ? typeEl.value : 'all';
        var rows = document.querySelectorAll('#ws-catalog-table tbody tr.ws-catalog-row');
        var visible = 0;
        var range = null;
        if (rangeInput && rangeInput._flatpickr && rangeInput._flatpickr.selectedDates.length === 2) {
            var a = rangeInput._flatpickr.selectedDates[0];
            var b = rangeInput._flatpickr.selectedDates[1];
            range = { start: a <= b ? a : b, end: a <= b ? b : a };
        }
        rows.forEach(function (tr) {
            var ok = true;
            if (t !== 'all' && tr.getAttribute('data-type') !== t) ok = false;
            if (ok && q) {
                var blob = (tr.getAttribute('data-search') || '')
                    + ' ' + (tr.getAttribute('data-name') || '')
                    + ' ' + (tr.getAttribute('data-code') || '');
                if (blob.toLowerCase().indexOf(q) === -1) ok = false;
            }
            if (ok && range) {
                var dep = tr.getAttribute('data-dep');
                if (dep) {
                    var d = new Date(dep + 'T12:00:00');
                    if (d < range.start || d > range.end) ok = false;
                }
            }
            tr.classList.toggle('hidden', !ok);
            if (ok) visible++;
        });
        var c = document.getElementById('ws-row-visible-count');
        if (c) c.textContent = String(visible);
    };

    applyWsFilters();

});
</script>
@endpush
