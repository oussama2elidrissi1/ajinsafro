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
            'create_url' => !empty($r['voyage_id'])
                ? route('admin.reservations.create', array_filter([
                    'voyage_id' => (int) $r['voyage_id'],
                    'travel_date_id' => $r['travel_date_id'] ?? null,
                ], fn ($value) => $value !== null && $value !== ''))
                : route('admin.reservations.create'),
        ];
    })->filter()->values()->all();
@endphp
@extends('layouts.admin-v2')

@section('title', 'Espace réservation — Catalogue')

@push('styles')
@if(!$usePortalTailwind)
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
@endif
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="{{ asset('css/reservation-workspace.css') }}">
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
</style>
@endpush

@section('content')
@php
    $catalogScope = $catalogScope ?? 'all';
    $catalogFullCount = $catalogFullCount ?? $catalogRows->count();
    $wsUpcomingCount = $catalogRows->where('ws_has_future', true)->count();
    $wsPackageCount = $catalogRows->where('type', 'package')->count();
    $wsWithPriceCount = $catalogRows->filter(fn ($row) => !empty($row['price_label']))->count();
    $commercialKpis = $commercialKpis ?? [];
    $commercialAssistant = $commercialAssistant ?? [];
@endphp
<div class="fade-in ws-page max-w-[1680px] mx-auto pb-10 overflow-x-hidden">
    <header class="ws-hero">
        <div class="ws-hero__main">
            <p class="ws-hero__eyebrow">Workspace commercial</p>
            <h1 class="ws-hero__title">Espace réservation</h1>
            <p class="ws-hero__sub">Catalogue opérationnel orienté vente: repérez vite le bon voyage, la bonne date, le bon tarif, puis lancez la réservation.</p>
            <div class="ws-kpi-row ws-kpi-row--commercial" aria-label="Indicateurs commerciaux">
                <div class="ws-kpi ws-kpi--accent">
                    <span class="ws-kpi__val">{{ number_format($commercialKpis['near_departures'] ?? 0, 0, ',', ' ') }}</span>
                    <span class="ws-kpi__lbl">Départs les plus proches</span>
                </div>
                <div class="ws-kpi ws-kpi--warn">
                    <span class="ws-kpi__val">{{ number_format($commercialKpis['low_stock'] ?? 0, 0, ',', ' ') }}</span>
                    <span class="ws-kpi__lbl">Circuits presque complets</span>
                </div>
                <div class="ws-kpi ws-kpi--push">
                    <span class="ws-kpi__val">{{ number_format($commercialKpis['push_count'] ?? 0, 0, ',', ' ') }}</span>
                    <span class="ws-kpi__lbl">Voyages à pousser</span>
                </div>
                <div class="ws-kpi ws-kpi--info">
                    <span class="ws-kpi__val">{{ number_format($commercialKpis['cities_count'] ?? 0, 0, ',', ' ') }}</span>
                    <span class="ws-kpi__lbl">Villes de départ</span>
                </div>
                <div class="ws-kpi ws-kpi--success">
                    <span class="ws-kpi__val">{{ number_format($commercialKpis['monthly_sold'] ?? 0, 0, ',', ' ') }}</span>
                    <span class="ws-kpi__lbl">Places vendues ce mois</span>
                </div>
                <div class="ws-kpi ws-kpi--remain">
                    <span class="ws-kpi__val">{{ number_format($commercialKpis['total_remaining'] ?? 0, 0, ',', ' ') }}</span>
                    <span class="ws-kpi__lbl">Places restantes</span>
                </div>
                @if(($commercialKpis['to_configure'] ?? 0) > 0)
                <div class="ws-kpi ws-kpi--configure">
                    <span class="ws-kpi__val">{{ number_format($commercialKpis['to_configure'] ?? 0, 0, ',', ' ') }}</span>
                    <span class="ws-kpi__lbl">À configurer</span>
                </div>
                @endif
            </div>
        </div>
        <div class="ws-hero__actions">
            <a href="{{ route('admin.reservations.index') }}" class="ws-hero__btn ws-hero__btn--primary">
                <i class="fas fa-list-ul" aria-hidden="true"></i> Liste des réservations
            </a>
            <a href="{{ route('admin.reservations.clients') }}" class="ws-hero__btn ws-hero__btn--outline">
                <i class="fas fa-user" aria-hidden="true"></i> Réservations clients
            </a>
        </div>
    </header>

    <!-- Assistant commercial -->
    <section class="ws-assistant-bar mb-6">
        <h2 class="ws-assistant-bar__title"><i class="fas fa-lightbulb" aria-hidden="true"></i> Assistant commercial</h2>
        <div class="ws-assistant-bar__grid">
            <div class="ws-assistant-card">
                <div class="ws-assistant-card__head">
                    <span class="ws-assistant-card__icon"><i class="fas fa-calendar-day"></i></span>
                    <span class="ws-assistant-card__label">Départs les plus proches</span>
                </div>
                <p class="ws-assistant-card__desc">Voyages avec départ dans les 7 prochains jours</p>
                <button type="button" class="ws-assistant-card__btn" data-filter-near>Afficher</button>
            </div>
            <div class="ws-assistant-card">
                <div class="ws-assistant-card__head">
                    <span class="ws-assistant-card__icon"><i class="fas fa-exclamation-circle"></i></span>
                    <span class="ws-assistant-card__label">Faible disponibilité</span>
                </div>
                <p class="ws-assistant-card__desc">Voyages avec moins de 10 places restantes</p>
                <button type="button" class="ws-assistant-card__btn" data-filter-low>Afficher</button>
            </div>
            <div class="ws-assistant-card">
                <div class="ws-assistant-card__head">
                    <span class="ws-assistant-card__icon"><i class="fas fa-rocket"></i></span>
                    <span class="ws-assistant-card__label">Fort potentiel de vente</span>
                </div>
                <p class="ws-assistant-card__desc">Beaucoup de places, bon prix et départ proche</p>
                <button type="button" class="ws-assistant-card__btn" data-filter-potential>Afficher</button>
            </div>
            <div class="ws-assistant-card">
                <div class="ws-assistant-card__head">
                    <span class="ws-assistant-card__icon"><i class="fas fa-city"></i></span>
                    <span class="ws-assistant-card__label">Villes à mentionner</span>
                </div>
                <p class="ws-assistant-card__desc">{{ implode(', ', array_slice(array_keys($commercialAssistant['top_cities'] ?? []), 0, 3)) }}</p>
                <button type="button" class="ws-assistant-card__btn" data-filter-cities>Afficher</button>
            </div>
        </div>
    </section>

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

    <div class="row g-4">
        <div class="col-xl-9 col-lg-8">
            <div id="reservations-main-content" class="space-y-6">
                {{-- Filtres --}}
        <div id="catalogue-workspace" class="ws-toolbar" data-workspace-url="{{ route('admin.reservations.workspace') }}">
            <div class="ws-toolbar__row ws-toolbar__row--search">
                <div class="ws-field ws-field--grow">
                    <label class="ws-field__label" for="ws-filter-search">Recherche rapide</label>
                    <div class="ws-field__input-wrap">
                        <i class="fas fa-search ws-field__icon" aria-hidden="true"></i>
                        <input type="text" id="ws-filter-search" placeholder="Nom, code, destination…" autocomplete="off" class="ws-input">
                    </div>
                </div>
                <div class="ws-toolbar__views">
                    <span class="ws-toolbar__count"><strong id="ws-row-visible-count">{{ $catalogRows->count() }}</strong> / {{ $catalogFullCount }} offres</span>
                    <div class="ws-seg ws-seg--triple" role="group" aria-label="Mode d'affichage">
                        <button type="button" id="btn-view-catalog" class="ws-seg__btn is-active" title="Catalogue"><i class="fas fa-th-large" aria-hidden="true"></i><span class="ws-seg__btn-label-catalog">Catalogue</span></button>
                        <button type="button" id="btn-view-list" class="ws-seg__btn" title="Vue liste (tableau)"><i class="fas fa-table" aria-hidden="true"></i><span>Liste</span></button>
                        <button type="button" id="btn-view-calendar" class="ws-seg__btn" title="Calendrier"><i class="far fa-calendar-alt" aria-hidden="true"></i><span>Calendrier</span></button>
                    </div>
                </div>
            </div>
            <div class="ws-toolbar__row ws-toolbar__row--filters">
                <div class="ws-field">
                    <label class="ws-field__label" for="ws-filter-type">Type</label>
                    <select id="ws-filter-type" class="ws-select">
                        <option value="all">Tous</option>
                        <option value="package">Package</option>
                        <option value="vol">Vol</option>
                        <option value="hebergement">Hébergement</option>
                    </select>
                </div>
                <div class="ws-field">
                    <label class="ws-field__label" for="ws-filter-city">Ville départ</label>
                    <select id="ws-filter-city" class="ws-select">
                        <option value="all">Toutes</option>
                        @php
                            $uniqueCities = collect();
                            foreach ($catalogRows as $row) {
                                foreach ($row['commercial']['villes_list'] ?? [] as $city) {
                                    if ($city !== '') $uniqueCities[$city] = true;
                                }
                            }
                            $uniqueCities = collect(array_keys($uniqueCities->all()))->sort()->values();
                        @endphp
                        @foreach($uniqueCities as $city)
                            <option value="{{ e(strtolower($city)) }}">{{ $city }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="ws-field">
                    <label class="ws-field__label" for="ws-filter-date-status">Période</label>
                    <select id="ws-filter-date-status" class="ws-select" data-ws-catalog-scope="{{ e($catalogScope) }}" title="Période (recharge la liste)">
                        <option value="all" @selected($catalogScope === 'all')>Tous</option>
                        <option value="upcoming" @selected($catalogScope === 'upcoming')>Départs à venir</option>
                        <option value="past" @selected($catalogScope === 'past')>Passés</option>
                        <option value="none" @selected($catalogScope === 'none')>Sans date</option>
                    </select>
                </div>
                <div class="ws-field">
                    <label class="ws-field__label" for="ws-filter-budget">Budget</label>
                    <select id="ws-filter-budget" class="ws-select">
                        <option value="all">Tous</option>
                        <option value="0-3000">< 3 000 MAD</option>
                        <option value="3000-6000">3 000 – 6 000 MAD</option>
                        <option value="6000-10000">6 000 – 10 000 MAD</option>
                        <option value="10000+">> 10 000 MAD</option>
                    </select>
                </div>
                <div class="ws-field">
                    <label class="ws-field__label" for="ws-filter-avail">Disponibilité</label>
                    <select id="ws-filter-avail" class="ws-select">
                        <option value="all" selected>Tous</option>
                        <option value="sellable">Tous les vendables</option>
                        <option value="depart_proche">Départ proche</option>
                        <option value="faible_stock">Faible stock</option>
                        <option value="disponible">Disponible</option>
                        <option value="fort_potentiel">Fort potentiel</option>
                        <option value="ok">Disponible (standard)</option>
                        <option value="almost_full">Presque complet</option>
                        <option value="low">Faible stock (strict)</option>
                        <option value="full">Complet</option>
                        <option value="configure">À configurer</option>
                    </select>
                </div>
                <div class="ws-field">
                    <label class="ws-field__label" for="ws-filter-priority">Priorité</label>
                    <select id="ws-filter-priority" class="ws-select">
                        <option value="all">Toutes</option>
                        <option value="push_urgent">À pousser aujourd'hui</option>
                        <option value="almost_full">Presque complet</option>
                        <option value="high_potential">Fort potentiel</option>
                        <option value="promote">À promouvoir</option>
                        <option value="watch">À surveiller</option>
                    </select>
                </div>
                <div class="ws-field">
                    <label class="ws-field__label" for="ws-filter-res">Réservations</label>
                    <select id="ws-filter-res" class="ws-select">
                        <option value="all">Tous</option>
                        <option value="none">Aucune</option>
                        <option value="any">Au moins une</option>
                        <option value="confirmed">Confirmées</option>
                        <option value="pending">En attente</option>
                    </select>
                </div>
                <div class="ws-field ws-field--grow">
                    <label class="ws-field__label" for="ws-date-range-picker">Plage dates</label>
                    <div class="ws-field__input-wrap">
                        <i class="far fa-calendar-alt ws-field__icon" aria-hidden="true"></i>
                        <input type="text" id="ws-date-range-picker" readonly placeholder="Optionnel" class="ws-input">
                    </div>
                </div>
                <div class="ws-field">
                    <label class="ws-field__label" for="ws-sort">Tri</label>
                    <select id="ws-sort" class="ws-select">
                        <option value="preserve" selected>Priorité commerciale</option>
                        <option value="dep-asc">Date départ ↑</option>
                        <option value="dep-desc">Date départ ↓</option>
                        <option value="price-asc">Prix ↑</option>
                        <option value="price-desc">Prix ↓</option>
                        <option value="places-asc">Places restantes ↑</option>
                        <option value="places-desc">Places restantes ↓</option>
                        <option value="default">Référence</option>
                    </select>
                </div>
                <div class="ws-field ws-field--actions">
                    <button type="button" id="ws-filters-reset" class="ws-btn-reset">Réinitialiser</button>
                </div>
            </div>
        </div>

        {{-- Vue liste (tableau) — défaut --}}
        <div id="ws-view-table" class="ws-table-card hidden">
            <div class="ws-table-card__head">
                <h2 class="ws-table-card__title">Vue liste</h2>
                <p class="ws-table-card__sub">Référence, voyage, départ, prix, capacité et actions. Tri serveur : futurs → sans date → passés.</p>
            </div>
            <div class="ws-table-scroll workspace-list-table-wrapper">
                <table class="ws-data-table ws-data-table--responsive workspace-list-table" aria-label="Catalogue des offres en liste">
                    <thead>
                        <tr>
                            <th scope="col" class="ws-data-table__th-ref">Réf</th>
                            <th scope="col" class="ws-data-table__th-offer">Voyage</th>
                            <th scope="col" class="ws-data-table__th-city">Ville départ</th>
                            <th scope="col" class="ws-data-table__th-dep">Départ</th>
                            <th scope="col" class="ws-data-table__th-price">Prix</th>
                            <th scope="col" class="ws-data-table__th-sold">Vendu</th>
                            <th scope="col" class="ws-data-table__th-remain">Restant</th>
                            <th scope="col" class="ws-data-table__th-cap">Capacité</th>
                            <th scope="col" class="ws-data-table__th-avail">Dispo</th>
                            <th scope="col" class="ws-data-table__th-priority">Priorité</th>
                            <th scope="col" class="ws-data-table__th-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="ws-catalog-table-body">
                        @php
                            $sellableRows = $catalogRows->filter(fn($r) => $r['commercial']['is_sellable'] ?? false);
                            $nonSellableRows = $catalogRows->filter(fn($r) => !($r['commercial']['is_sellable'] ?? false));
                        @endphp
                        @if($sellableRows->isNotEmpty())
                            @foreach($sellableRows as $row)
                                @include('admin.reservations.workspace.partials.catalog-row', ['row' => $row, 'mode' => 'table'])
                            @endforeach
                        @endif
                        @if($nonSellableRows->isNotEmpty())
                            <tr class="ws-catalog-section-divider">
                                <td colspan="11" class="ws-catalog-section-divider__cell">
                                    <span class="ws-catalog-section-divider__label">Voyages non disponibles / à configurer</span>
                                </td>
                            </tr>
                            @foreach($nonSellableRows as $row)
                                @include('admin.reservations.workspace.partials.catalog-row', ['row' => $row, 'mode' => 'table'])
                            @endforeach
                        @endif
                        @if($catalogRows->isEmpty())
                            <tr>
                                <td colspan="11" class="ws-table-empty-cell">
                                    <div class="ws-catalog-empty ws-catalog-empty--inline">
                                        <div class="max-w-md mx-auto text-center py-10 px-6">
                                            <div class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 text-gray-400 mb-3 text-xl">
                                                <i class="fas fa-inbox"></i>
                                            </div>
                                            <p class="text-brand-dark font-bold text-base mb-2">Aucun voyage dans le catalogue</p>
                                            <p class="text-gray-500 text-sm mb-5">Créez ou liez des fiches voyages depuis Circuits / voyages.</p>
                                            <a href="{{ route('admin.circuits.voyages.index') }}" class="inline-flex items-center gap-2 rounded-xl bg-brand-blue text-white font-bold text-sm px-5 py-2.5 hover:bg-brand-dark transition-colors">
                                                <i class="fas fa-plus-circle"></i> Gérer les voyages
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Présentation catalogue (cartes) --}}
        <div id="ws-view-catalog" class="ws-table-card">
            <div class="ws-table-card__head">
                <h2 class="ws-table-card__title">Présentation catalogue</h2>
                <p class="ws-table-card__sub">Même jeu de données et même ordre que la liste (tri serveur, filtre période optionnel).</p>
            </div>
            @php
                $sellableRows = $catalogRows->filter(fn($r) => $r['commercial']['is_sellable'] ?? false);
                $nonSellableRows = $catalogRows->filter(fn($r) => !($r['commercial']['is_sellable'] ?? false));
            @endphp
            <div id="ws-catalog-list">
                @if($sellableRows->isNotEmpty())
                    <div class="ws-catalog-section">
                        <h3 class="ws-catalog-section__title">Voyages disponibles à la vente</h3>
                        <div class="ws-catalog-grid ws-catalog-grid--compact">
                            @foreach($sellableRows as $row)
                                @include('admin.reservations.workspace.partials.catalog-row', ['row' => $row, 'mode' => 'card'])
                            @endforeach
                        </div>
                    </div>
                @endif
                @if($nonSellableRows->isNotEmpty())
                    <div class="ws-catalog-section ws-catalog-section--configure">
                        <h3 class="ws-catalog-section__title ws-catalog-section__title--configure">Voyages non disponibles / à configurer</h3>
                        <div class="ws-catalog-grid ws-catalog-grid--compact">
                            @foreach($nonSellableRows as $row)
                                @include('admin.reservations.workspace.partials.catalog-row', ['row' => $row, 'mode' => 'card'])
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
        <div id="reservations-calendar-view" class="bg-white p-4 sm:p-6 rounded-2xl shadow-custom border border-gray-100/90 hidden">
            <p class="text-sm text-gray-600 mb-4">Clic sur un événement : ouverture de la page dédiée de création de réservation.</p>
            <div id="workspace-calendar" class="w-full min-h-[540px] fc-workspace"></div>
        </div>
        </div>
    </div>
    <div class="col-xl-3 col-lg-4">
        <div class="ws-sale-panel sticky-top">
            <div class="ws-sale-panel__section">
                <h3 class="ws-sale-panel__title"><i class="fas fa-calendar-check" aria-hidden="true"></i> Prochains départs</h3>
                <ul class="ws-sale-panel__list">
                    @foreach($catalogRows->take(8) as $row)
                        @php $c = $row['commercial'] ?? []; @endphp
                        @if(!empty($c['prochaine_date_depart']))
                            <li class="ws-sale-panel__item">
                                <span class="ws-sale-panel__item-date">{{ \Carbon\Carbon::parse($c['prochaine_date_depart'])->locale('fr')->translatedFormat('d M Y') }}</span>
                                <span class="ws-sale-panel__item-name">{{ \Illuminate\Support\Str::limit($row['name'] ?? '', 28) }}</span>
                                @if(!empty($c['ville_depart']))
                                    <span class="ws-sale-panel__item-city">{{ $c['ville_depart'] }}</span>
                                @endif
                                @if($c['places_restantes'] !== null)
                                    <span class="ws-sale-panel__item-remain {{ $c['places_restantes'] <= 5 ? 'ws-sale-panel__item-remain--danger' : '' }}">{{ $c['places_restantes'] }} restantes</span>
                                @endif
                            </li>
                        @endif
                    @endforeach
                </ul>
            </div>
            <div class="ws-sale-panel__section">
                <h3 class="ws-sale-panel__title"><i class="fas fa-exclamation-triangle" aria-hidden="true"></i> Manque de places</h3>
                <ul class="ws-sale-panel__list">
                    @foreach($catalogRows->filter(fn ($r) => ($r['commercial']['places_restantes'] ?? 999) <= 10 && ($r['commercial']['places_restantes'] ?? 0) > 0)->take(8) as $row)
                        @php $c = $row['commercial'] ?? []; @endphp
                        <li class="ws-sale-panel__item">
                            <span class="ws-sale-panel__item-name">{{ \Illuminate\Support\Str::limit($row['name'] ?? '', 28) }}</span>
                            <span class="ws-sale-panel__item-badge {{ $c['places_restantes'] <= 5 ? 'ws-sale-panel__item-badge--danger' : 'ws-sale-panel__item-badge--warn' }}">{{ $c['places_restantes'] }} restantes</span>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="ws-sale-panel__section">
                <h3 class="ws-sale-panel__title"><i class="fas fa-bullhorn" aria-hidden="true"></i> Recommandations</h3>
                <ul class="ws-sale-panel__recommendations">
                    @if(($commercialKpis['near_departures'] ?? 0) > 0)
                        <li>Poussez les circuits à départ proche</li>
                    @endif
                    @if(($commercialKpis['low_stock'] ?? 0) > 0)
                        <li>Mettez en avant la faible disponibilité</li>
                    @endif
                    @if(!empty(array_keys($commercialAssistant['top_cities'] ?? [])[0] ?? null))
                        <li>Proposez {{ implode(' et ', array_slice(array_keys($commercialAssistant['top_cities'] ?? []), 0, 2)) }} en priorité</li>
                    @endif
                    <li>Priorisez les voyages avec forte marge</li>
                </ul>
            </div>
            <div class="ws-sale-panel__section">
                <h3 class="ws-sale-panel__title"><i class="fas fa-handshake" aria-hidden="true"></i> Arguments de vente</h3>
                <ul class="ws-sale-panel__arguments">
                    <li>Départs garantis et confirmés</li>
                    <li>Places limitées, réservez vite</li>
                    <li>Meilleur rapport qualité/prix</li>
                    <li>Accompagnement 24/7</li>
                </ul>
            </div>
        </div>
    </div>
</div>
</div>

<script type="application/json" id="workspace-calendar-json">{!! json_encode($workspaceCalendarEvents, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>
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

@push('scripts’)
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/fr.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
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
    if (!injected || typeof injected !== 'object’ || Array.isArray(injected)) injected = {};
    window.wsModalSettings = Object.assign({}, defaults, injected);
})();
</script>
<script>
document.addEventListener('DOMContentLoaded’, function () {
    /* Modal détail : enregistré en premier (avant FullCalendar) pour éviter qu’une erreur JS bloque le clic. */
    var wsModalJson = document.getElementById('ws-modal-detail-json’);
    var wsModalEl = document.getElementById('ws-voyage-detail-modal’);
    var wsMdTitle = document.getElementById('ws-md-title’);
    var wsMdSub = document.getElementById('ws-md-sub’);
    var wsMdBody = document.getElementById('ws-md-body’);
    var wsMdFooter = document.getElementById('ws-md-footer’);
    function parseWsDetailMap() {
        if (!wsModalJson) return {};
        try { return JSON.parse(wsModalJson.textContent || '{}’); } catch (err) { return {}; }
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
            h += '<div class="ws-md-dep-kpi"><span>Confirmées</span><strong>' + (rs.validee != null ? rs.validee : 0) + '</strong></div>';
            h += '<div class="ws-md-dep-kpi"><span>En attente</span><strong>' + (rs.en_cours != null ? rs.en_cours : 0) + '</strong></div>';
            h += '<div class="ws-md-dep-kpi"><span>Annulées</span><strong>' + (rs.annulee != null ? rs.annulee : 0) + '</strong></div>';
            h += '<div class="ws-md-dep-kpi"><span>Total dossiers</span><strong>' + (rs.total != null ? rs.total : 0) + '</strong></div>';
            h += '<div class="ws-md-dep-kpi"><span>Places restantes</span><strong>' + (dep.remaining != null ? escapeWsHtml(String(dep.remaining)) : '—') + '</strong></div>';
            if (dep.fill_pct != null) {
                h += '<div class="ws-md-dep-kpi"><span>Taux remplissage</span><strong>' + dep.fill_pct + '%</strong></div>';
            }
            h += '</div>';
            if (dep.capacity_note && !(d.rooms && d.rooms.length)) {
                h += '<p style="margin:0.5rem 0 0;font-size:0.75rem;color:#64748b">' + escapeWsHtml(dep.capacity_note) + '</p>';
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

    function wsActivateView(mode) {
        if (btnList) btnList.classList.toggle('is-active', mode === 'list');
        if (btnCal) btnCal.classList.toggle('is-active', mode === 'cal');
        if (btnCatalog) btnCatalog.classList.toggle('is-active', mode === 'catalog');
        if (tableView) tableView.classList.toggle('hidden', mode !== 'list');
        if (catalogView) catalogView.classList.toggle('hidden', mode !== 'catalog');
        if (calView) calView.classList.toggle('hidden', mode !== 'cal');
        if (mode === 'cal' && calendar) setTimeout(function () { calendar.render(); }, 80);
    }

    if (btnList) btnList.addEventListener('click', function () { wsActivateView('list'); });
    if (btnCal) btnCal.addEventListener('click', function () { wsActivateView('cal'); });
    if (btnCatalog) btnCatalog.addEventListener('click', function () { wsActivateView('catalog'); });

    var searchEl = document.getElementById('ws-filter-search');
    var typeEl = document.getElementById('ws-filter-type');
    var dateStatusEl = document.getElementById('ws-filter-date-status');
    var cityEl = document.getElementById('ws-filter-city');
    var budgetEl = document.getElementById('ws-filter-budget');
    var availEl = document.getElementById('ws-filter-avail');
    var priorityEl = document.getElementById('ws-filter-priority');
    var resEl = document.getElementById('ws-filter-res');
    var sortEl = document.getElementById('ws-sort');
    var rangeInput = document.getElementById('ws-date-range-picker');
    var resetBtn = document.getElementById('ws-filters-reset');
    var catalogToolbar = document.getElementById('catalogue-workspace');
    var wsWorkspaceBase = catalogToolbar && catalogToolbar.getAttribute('data-workspace-url')
        ? catalogToolbar.getAttribute('data-workspace-url')
        : (window.location.pathname || '/admin/reservations/workspace');

    function wsNavigateCatalogScope(scope) {
        var u;
        try {
            u = new URL(wsWorkspaceBase, window.location.origin);
        } catch (e) {
            u = new URL(window.location.href);
        }
        if (scope === 'all') {
            u.searchParams.delete('catalog');
        } else {
            u.searchParams.set('catalog', scope);
        }
        window.location.href = u.toString();
    }

    function wsRowCompare(a, b, sort) {
        var ca = (a.getAttribute('data-code') || '');
        var cb = (b.getAttribute('data-code') || '');
        function n(tr, attr) {
            var v = parseInt(tr.getAttribute(attr), 10);
            return isNaN(v) ? 0 : v;
        }
        function prio(tr) {
            var p = tr.getAttribute('data-commercial-priority') || 'standard';
            var map = { push_urgent: 0, almost_full: 1, high_potential: 2, promote: 3, standard: 4, watch: 5 };
            return map[p] !== undefined ? map[p] : 4;
        }
        switch (sort) {
            case 'preserve':
                var pa = prio(a), pb = prio(b);
                if (pa !== pb) return pa - pb;
                var da = n(a, 'data-days-until'), db = n(b, 'data-days-until');
                if (da !== db) return da - db;
                var ra = n(a, 'data-remaining'), rb = n(b, 'data-remaining');
                if (ra !== rb) return ra - rb;
                return ca.localeCompare(cb, 'fr');
            case 'dep-asc': return n(a, 'data-sort-dep') - n(b, 'data-sort-dep');
            case 'dep-desc': return n(b, 'data-sort-dep') - n(a, 'data-sort-dep');
            case 'price-asc': return n(a, 'data-sort-price') - n(b, 'data-sort-price');
            case 'price-desc': return n(b, 'data-sort-price') - n(a, 'data-sort-price');
            case 'places-asc': return n(a, 'data-remaining') - n(b, 'data-remaining');
            case 'places-desc': return n(b, 'data-remaining') - n(a, 'data-remaining');
            default: return ca.localeCompare(cb, 'fr');
        }
    }

    function wsSortContainer(containerId) {
        var list = document.getElementById(containerId);
        if (!list || !sortEl) return;
        var sort = sortEl.value || 'preserve';
        var divider = list.querySelector ? list.querySelector('.ws-catalog-section-divider') : null;
        if (sort === 'preserve') {
            if (divider) divider.style.display = '';
            return;
        }
        if (containerId === 'ws-catalog-list') {
            list.querySelectorAll('.ws-catalog-grid').forEach(function (grid) {
                var rows = Array.prototype.slice.call(grid.querySelectorAll('.ws-catalog-row'));
                rows.sort(function (a, b) { return wsRowCompare(a, b, sort); });
                rows.forEach(function (el) { grid.appendChild(el); });
            });
        } else {
            if (divider) divider.style.display = 'none';
            var rows = Array.prototype.slice.call(list.querySelectorAll('.ws-catalog-row'));
            rows.sort(function (a, b) { return wsRowCompare(a, b, sort); });
            rows.forEach(function (el) { list.appendChild(el); });
        }
    }

    function wsApplySort() {
        wsSortContainer('ws-catalog-list');
        wsSortContainer('ws-catalog-table-body');
    }

    window.applyWsFilters = function applyWsFilters() {
        var q = (searchEl && searchEl.value) ? searchEl.value.toLowerCase().trim() : '';
        var t = typeEl ? typeEl.value : 'all';
        var city = cityEl ? cityEl.value : 'all';
        var budget = budgetEl ? budgetEl.value : 'all';
        var av = availEl ? availEl.value : 'all';
        var pr = priorityEl ? priorityEl.value : 'all';
        var rs = resEl ? resEl.value : 'all';
        var rows = document.querySelectorAll('#ws-catalog-list .ws-catalog-row, #ws-catalog-table-body .ws-catalog-row');
        var visible = 0;
        var seenCodes = {};
        var range = null;
        if (rangeInput && rangeInput._flatpickr && rangeInput._flatpickr.selectedDates.length === 2) {
            var a = rangeInput._flatpickr.selectedDates[0];
            var b = rangeInput._flatpickr.selectedDates[1];
            range = { start: a <= b ? a : b, end: a <= b ? b : a };
        }
        rows.forEach(function (tr) {
            var ok = true;
            if (t !== 'all' && tr.getAttribute('data-type') !== t) ok = false;
            if (ok && city !== 'all') {
                var dc = tr.getAttribute('data-departure-city') || '';
                if (dc.indexOf(city) === -1) ok = false;
            }
            if (ok && budget !== 'all') {
                var price = parseInt(tr.getAttribute('data-price'), 10) || 0;
                if (budget === '0-3000' && price >= 3000) ok = false;
                if (budget === '3000-6000' && (price < 3000 || price >= 6000)) ok = false;
                if (budget === '6000-10000' && (price < 6000 || price >= 10000)) ok = false;
                if (budget === '10000+' && price < 10000) ok = false;
            }
            if (ok && av !== 'all' && av !== 'sellable') {
                var astat = tr.getAttribute('data-avail-status') || 'unknown';
                var isSellableRow = tr.getAttribute('data-is-sellable') === '1';
                var daysUntilRow = parseInt(tr.getAttribute('data-days-until'), 10);
                var priorityRow = tr.getAttribute('data-commercial-priority') || 'standard';
                var remainingRow = parseInt(tr.getAttribute('data-remaining'), 10);

                if (av === 'sellable') {
                    if (!isSellableRow) ok = false;
                } else if (av === 'depart_proche') {
                    if (!isSellableRow || isNaN(daysUntilRow) || daysUntilRow < 0 || daysUntilRow > 7) ok = false;
                } else if (av === 'faible_stock') {
                    if (!isSellableRow || isNaN(remainingRow) || remainingRow > 10 || remainingRow <= 0) ok = false;
                } else if (av === 'disponible') {
                    if (!isSellableRow || astat !== 'ok') ok = false;
                } else if (av === 'fort_potentiel') {
                    if (!isSellableRow || priorityRow !== 'high_potential') ok = false;
                } else if (av === 'ok') {
                    if (astat !== 'ok') ok = false;
                } else if (av === 'almost_full') {
                    if (astat !== 'almost_full') ok = false;
                } else if (av === 'low') {
                    if (astat !== 'low') ok = false;
                } else if (av === 'full') {
                    if (astat !== 'full') ok = false;
                } else if (av === 'unknown') {
                    if (astat !== 'unknown') ok = false;
                } else if (av === 'configure') {
                    if (isSellableRow) ok = false;
                } else if (av === 'places') {
                    if (astat !== 'ok' && astat !== 'low' && astat !== 'almost_full') ok = false;
                }
            }

            // Explicit sellable filter: hide non-sellable rows
            if (ok && availEl && availEl.value === 'sellable') {
                var isSellableRow = tr.getAttribute('data-is-sellable') === '1';
                if (!isSellableRow) ok = false;
            }
            if (ok && pr !== 'all') {
                if (tr.getAttribute('data-commercial-priority') !== pr) ok = false;
            }
            if (ok && rs !== 'all') {
                var st = parseInt(tr.getAttribute('data-stats-total'), 10) || 0;
                var sv = parseInt(tr.getAttribute('data-stats-validee'), 10) || 0;
                var sp = parseInt(tr.getAttribute('data-stats-pending'), 10) || 0;
                if (rs === 'none' && st !== 0) ok = false;
                if (rs === 'any' && st === 0) ok = false;
                if (rs === 'confirmed' && sv < 1) ok = false;
                if (rs === 'pending' && sp < 1) ok = false;
            }
            if (ok && q) {
                var blob = (tr.getAttribute('data-search') || '')
                    + ' ' + (tr.getAttribute('data-name') || '')
                    + ' ' + (tr.getAttribute('data-code') || '')
                    + ' ' + (tr.getAttribute('data-departure-city') || '');
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
            if (ok) {
                var rc = tr.getAttribute('data-row-code') || '_';
                if (!seenCodes[rc]) {
                    seenCodes[rc] = true;
                    visible++;
                }
            }
        });
        var c = document.getElementById('ws-row-visible-count');
        if (c) c.textContent = String(visible);
        var k = document.getElementById('ws-kpi-visible');
        if (k) k.textContent = String(visible);
    };

    function applyWsFiltersAndSort() {
        wsApplySort();
        applyWsFilters();
    }

    if (searchEl) searchEl.addEventListener('input', applyWsFilters);
    if (typeEl) typeEl.addEventListener('change', applyWsFilters);
    if (cityEl) cityEl.addEventListener('change', applyWsFilters);
    if (budgetEl) budgetEl.addEventListener('change', applyWsFilters);
    if (dateStatusEl) {
        dateStatusEl.addEventListener('change', function () {
            var v = dateStatusEl.value || 'all';
            var cur = dateStatusEl.getAttribute('data-ws-catalog-scope') || 'all';
            if (v === cur) return;
            wsNavigateCatalogScope(v);
        });
    }
    if (availEl) availEl.addEventListener('change', applyWsFilters);
    if (priorityEl) priorityEl.addEventListener('change', applyWsFilters);
    if (resEl) resEl.addEventListener('change', applyWsFilters);
    if (sortEl) sortEl.addEventListener('change', applyWsFiltersAndSort);
    if (resetBtn) {
        resetBtn.addEventListener('click', function () {
            if (searchEl) searchEl.value = '';
            if (typeEl) typeEl.value = 'all';
            if (cityEl) cityEl.value = 'all';
            if (budgetEl) budgetEl.value = 'all';
            if (availEl) availEl.value = 'all';
            if (priorityEl) priorityEl.value = 'all';
            if (resEl) resEl.value = 'all';
            if (rangeInput && rangeInput._flatpickr) rangeInput._flatpickr.clear();
            if (dateStatusEl && dateStatusEl.value !== 'all') {
                wsNavigateCatalogScope('all');
                return;
            }
            if (sortEl) sortEl.value = 'preserve';
            applyWsFiltersAndSort();
        });
    }

    // Assistant commercial buttons
    document.querySelectorAll('[data-filter-near]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (availEl) availEl.value = 'depart_proche';
            if (priorityEl) priorityEl.value = 'all';
            if (sortEl) sortEl.value = 'dep-asc';
            applyWsFiltersAndSort();
        });
    });
    document.querySelectorAll('[data-filter-low]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (availEl) availEl.value = 'faible_stock';
            if (priorityEl) priorityEl.value = 'all';
            applyWsFiltersAndSort();
        });
    });
    document.querySelectorAll('[data-filter-potential]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (availEl) availEl.value = 'fort_potentiel';
            if (priorityEl) priorityEl.value = 'all';
            applyWsFiltersAndSort();
        });
    });
    document.querySelectorAll('[data-filter-cities]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (cityEl) cityEl.value = 'all';
            applyWsFiltersAndSort();
        });
    });

    if (typeof flatpickr !== 'undefined' && rangeInput) {
        flatpickr(rangeInput, {
            mode: 'range',
            dateFormat: 'Y-m-d',
            locale: 'fr',
            onChange: function () { applyWsFilters(); }
        });
    }

    applyWsFiltersAndSort();

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
        var capacity = departure.capacity != null ? Number(departure.capacity) : null;
        var remaining = departure.remaining != null ? Number(departure.remaining) : null;
        var fillPct = departure.fill_pct != null ? Number(departure.fill_pct) : 0;
        var hasRooms = !!(detail.rooms && detail.rooms.length);
        var daysUntil = departure.days_until != null ? Number(departure.days_until) : null;
        var rules = [];
        var badgeClass = 'ws-md-report-badge ws-md-report-badge--neutral';

        if (!hasRooms) {
            rules.push({ text: 'Chambres non configurées', type: 'warn' });
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
        html += '<div class="ws-md-departure-kpi ws-md-departure-kpi--ok"><i class="fas fa-check-circle"></i><span>Confirmées</span><strong>' + escapeHtml(reservations.validee != null ? reservations.validee : 0) + '</strong></div>';
        html += '<div class="ws-md-departure-kpi ws-md-departure-kpi--wait"><i class="fas fa-hourglass-half"></i><span>En attente</span><strong>' + escapeHtml(reservations.en_cours != null ? reservations.en_cours : 0) + '</strong></div>';
        html += '<div class="ws-md-departure-kpi ws-md-departure-kpi--cancel"><i class="fas fa-times-circle"></i><span>Annulées</span><strong>' + escapeHtml(reservations.annulee != null ? reservations.annulee : 0) + '</strong></div>';
        html += '<div class="ws-md-departure-kpi ws-md-departure-kpi--remain"><i class="fas fa-chair"></i><span>Restantes</span><strong>' + escapeHtml(departure.remaining != null ? departure.remaining : '—') + '</strong></div>';
        html += '<div class="ws-md-departure-kpi ' + rateClass + '"><i class="fas fa-chart-line"></i><span>Taux</span><strong>' + escapeHtml(fillPct + '%') + '</strong></div>';
        html += '</div>';
        html += '<div class="ws-md-progress ws-md-progress--dep" role="progressbar" aria-valuenow="' + fillPct + '" aria-valuemin="0" aria-valuemax="100"><div class="ws-md-progress-bar" style="width:' + fillPct + '%"></div></div>';
        if (departure.capacity_note && !(detail.rooms && detail.rooms.length)) html += '<p class="ws-md-inline-note">' + escapeHtml(departure.capacity_note) + '</p>';
        html += '<div class="ws-md-departure-info-grid" style="margin-top:1rem">';
        if (detail.duration) html += '<div class="ws-md-departure-info"><span>Durée</span><strong>' + escapeHtml(detail.duration) + '</strong></div>';
        html += '<div class="ws-md-departure-info"><span>Date sélectionnée</span><strong>' + escapeHtml(departure.date_label || '—') + '</strong></div>';
        if (detail.prices && detail.prices.adult_label) html += '<div class="ws-md-departure-info"><span>Prix à partir de</span><strong>' + escapeHtml(detail.prices.adult_label) + '</strong></div>';
        html += '<div class="ws-md-departure-info"><span>Statut du départ</span><strong>' + availabilityBadge(departure) + '</strong></div>';
        if (detail.rooms && detail.rooms.length) {
            var roomLabels = detail.rooms.map(function (room) {
                return (room.room_type || 'Chambre') + ' (' + (room.product || 0) + ')';
            }).join(', ');
            html += '<div class="ws-md-departure-info"><span>Chambres disponibles</span><strong>' + escapeHtml(roomLabels) + '</strong></div>';
        } else {
            html += '<div class="ws-md-departure-info"><span>Chambres disponibles</span><strong>Aucune chambre configurée</strong></div>';
        }
        html += '</div></section>';
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
        if (!departures.length || !selectedDeparture) {
            return '<div class="ws-md-body-inner"><section class="ws-md-card"><p style="margin:0;color:#64748b">Aucun départ disponible pour ce voyage.</p></section></div>';
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
            var canReserve = !isPast && remaining > 0 && isAvailable && hasReserveRoute;
            if (canReserve) {
                html += '<a href="' + selectedDeparture.routes.reserve + '" class="ws-md-btn ws-md-btn-success"><i class="fas fa-suitcase-rolling"></i> Réserver ce départ</a>';
            } else {
                var title = isPast ? 'Départ passé' : (remaining <= 0 ? 'Aucune place restante' : (!isAvailable ? 'Départ indisponible' : 'Réservation non configurée'));
                html += '<button type="button" disabled class="ws-md-btn ws-md-btn-disabled" title="' + title + '"><i class="fas fa-suitcase-rolling"></i> Réserver ce départ</button>';
            }
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
            var canReserve = !isPast && remaining > 0 && isAvailable && hasReserveRoute;
            if (canReserve) {
                html += '<a href="' + departure.routes.reserve + '" class="ws-md-btn ws-md-btn-success"><i class="fas fa-suitcase-rolling"></i> Réserver ce départ</a>';
            } else {
                var title = isPast ? 'Départ passé' : (remaining <= 0 ? 'Aucune place restante' : (!isAvailable ? 'Départ indisponible' : 'Réservation non configurée'));
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
    setWorkspaceView('catalog');
    normalizeWorkspaceButtons();

    document.addEventListener('click', function (event) {
        var target = event.target.closest('[data-ws-detail-trigger],[data-ws-reserve-trigger],[data-ws-select-departure],[data-ws-reserve-departure],[data-ws-md-close],[data-ws-md-backdrop]');
        if (!target) return;
        event.preventDefault();
        event.stopPropagation();
        if (target.hasAttribute('data-ws-detail-trigger')) {
            openSelector(target.getAttribute('data-row-code') || '');
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
