@extends('layouts.admin-v2')

@section('title', 'Dossiers de reservation')

@push('styles')
<style>
    :root {
      --blue-900: #003d68;
      --blue-800: #00558d;
      --blue-700: #006eb8;
      --blue-100: #e8f4ff;
      --orange: #f97316;
      --green: #12b76a;
      --red: #ef4444;
      --purple: #7c3aed;
      --ink: #102a43;
      --muted: #6b7a90;
      --line: #e5edf6;
      --bg: #f5f8fc;
      --white: #ffffff;
      --shadow: 0 14px 36px rgba(16, 42, 67, 0.08);
      --shadow-soft: 0 8px 24px rgba(16, 42, 67, 0.06);
      --radius-lg: 22px;
      --radius-md: 16px;
    }

    .page-head {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 24px;
      margin-bottom: 28px;
    }

    .page-head h1 {
      font-size: clamp(28px, 3vw, 40px);
      line-height: 1.05;
      letter-spacing: -1px;
      color: #0b2545;
      margin-bottom: 8px;
      font-weight: 800;
    }

    .page-head p {
      color: var(--muted);
      font-weight: 600;
      margin-bottom: 0;
    }

    .primary-btn {
      border: 0;
      border-radius: 14px;
      background: linear-gradient(135deg, var(--blue-700), var(--blue-900));
      color: white !important;
      padding: 14px 24px;
      font-weight: 800;
      box-shadow: 0 14px 30px rgba(0, 110, 184, 0.24);
      cursor: pointer;
      white-space: nowrap;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 10px;
      font-size: 15px;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .primary-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 18px 36px rgba(0, 110, 184, 0.32);
      color: white !important;
    }

    .kpis {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 22px;
      margin-bottom: 28px;
    }

    .kpi-card {
      background: var(--white);
      border: 1px solid var(--line);
      border-radius: var(--radius-lg);
      padding: 24px;
      display: flex;
      align-items: center;
      gap: 18px;
      box-shadow: var(--shadow-soft);
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .kpi-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 12px 28px rgba(16, 42, 67, 0.1);
    }

    .kpi-icon {
      width: 58px;
      height: 58px;
      border-radius: 20px;
      display: grid;
      place-items: center;
      font-size: 26px;
      font-weight: 900;
    }

    .kpi-icon.blue { background: #eaf5ff; color: var(--blue-700); }
    .kpi-icon.orange { background: #fff2e8; color: var(--orange); }
    .kpi-icon.purple { background: #f3edff; color: var(--purple); }
    .kpi-icon.green { background: #e8fff4; color: var(--green); }

    .kpi-card span {
      color: var(--muted);
      font-size: 13px;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.03em;
    }

    .kpi-card strong {
      display: block;
      font-size: 28px;
      line-height: 1.1;
      margin: 4px 0 4px;
      color: #102a43;
      font-weight: 900;
    }

    .kpi-card small {
      color: #7e8fa6;
      font-weight: 600;
      font-size: 12px;
    }

    .panel {
      background: rgba(255, 255, 255, 0.95);
      border: 1px solid var(--line);
      border-radius: 20px;
      box-shadow: var(--shadow);
      padding: 24px;
    }

    .toolbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 16px;
      padding-bottom: 20px;
      border-bottom: 1px solid var(--line);
      margin-bottom: 24px;
      flex-wrap: wrap;
    }

    .tabs {
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
    }

    .tab {
      border: 0;
      border-radius: 12px;
      padding: 10px 18px;
      color: #50637c;
      background: #f8fafc;
      font-weight: 700;
      cursor: pointer;
      text-decoration: none;
      font-size: 14px;
      transition: all 0.2s;
    }

    .tab:hover {
        background: #f1f5f9;
        color: var(--blue-800);
    }

    .tab.active {
      background: var(--blue-800);
      color: white !important;
      box-shadow: 0 8px 16px rgba(0, 85, 141, 0.25);
    }

    .pill-count {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 24px;
      height: 20px;
      margin-left: 8px;
      padding: 0 6px;
      border-radius: 999px;
      font-size: 11px;
      font-weight: 800;
    }

    .pill-count.orange { background: #fff2e8; color: var(--orange); }
    .pill-count.green { background: #e8fff4; color: var(--green); }
    .pill-count.purple { background: #f3edff; color: var(--purple); }

    .reservation-grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 22px;
    }

    .reservation-card {
      overflow: hidden;
      background: white;
      border: 1px solid var(--line);
      border-radius: 18px;
      box-shadow: var(--shadow-soft);
      transition: transform 0.25s ease, box-shadow 0.25s ease;
      display: flex;
      flex-direction: column;
    }

    .reservation-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 20px 40px rgba(16, 42, 67, 0.12);
    }

    .card-image {
      position: relative;
      height: 160px;
      background-size: cover;
      background-position: center;
      background-color: #eef2f6;
    }

    .card-image::after {
      content: "";
      position: absolute;
      inset: 0;
      background: linear-gradient(180deg, rgba(0,0,0,0) 46%, rgba(0,0,0,0.18));
    }

    .no-image-placeholder {
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #94a3b8;
        font-weight: 700;
        font-size: 13px;
        flex-direction: column;
        gap: 10px;
        background: linear-gradient(135deg, #f1f5f9, #e8edf3);
    }

    .status {
      position: absolute;
      z-index: 2;
      top: 16px;
      right: 16px;
      border-radius: 10px;
      padding: 8px 12px;
      font-size: 12px;
      font-weight: 900;
      text-transform: lowercase;
      box-shadow: 0 8px 18px rgba(0,0,0,0.08);
    }

    .status.waiting { background: #fff3e8; color: #ea580c; }
    .status.paid { background: #e8fff4; color: #059669; }
    .status.partial { background: #eef4ff; color: #2454d6; }
    .status.unpaid { background: #fff0f0; color: #dc2626; }

    .card-body {
      padding: 20px 20px 0;
      flex-grow: 1;
    }

    .card-title-row {
      display: flex;
      gap: 12px;
      align-items: flex-start;
      justify-content: space-between;
      margin-bottom: 18px;
    }

    .card-title-row h3 {
      font-size: 18px;
      line-height: 1.32;
      letter-spacing: -0.2px;
      color: #16375c;
      margin: 0;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
      min-height: 48px;
      font-weight: 800;
    }

    .meta-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 14px;
      margin-bottom: 18px;
    }

    .meta-label {
      display: block;
      color: #8a9ab0;
      font-size: 12px;
      font-weight: 800;
      margin-bottom: 5px;
    }

    .meta-value {
      display: block;
      color: #263f60;
      font-size: 14px;
      font-weight: 800;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .card-footer {
      display: grid;
      grid-template-columns: 1fr 1fr 1fr auto;
      gap: 12px;
      align-items: end;
      padding: 18px 20px 20px;
      border-top: 1px solid var(--line);
      background: linear-gradient(180deg, #ffffff, #fbfdff);
    }

    .foot-label {
      display: block;
      font-size: 11px;
      color: #8a9ab0;
      font-weight: 900;
      margin-bottom: 4px;
    }

    .foot-value {
      font-size: 13px;
      color: #16375c;
      font-weight: 900;
      white-space: nowrap;
    }

    .view-btn {
      height: 42px;
      min-width: 68px;
      padding: 0 18px;
      border-radius: 12px;
      border: 1px solid #1d72d2;
      background: #ffffff;
      color: #0b63ce;
      font-weight: 900;
      cursor: pointer;
      transition: all 0.2s;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 14px;
    }

    .view-btn:hover {
      background: #0b63ce;
      color: white !important;
      transform: translateY(-2px);
      box-shadow: 0 6px 14px rgba(11, 99, 206, 0.3);
    }

    .pagination-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding-top: 28px;
      gap: 16px;
    }

    .pagination {
        margin-bottom: 0;
        gap: 7px;
        display: flex;
        align-items: center;
    }

    .page-item .page-link {
        border-radius: 10px !important;
        border: 1px solid var(--line);
        color: #50637c;
        font-weight: 800;
        min-width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0 12px;
        font-size: 14px;
        background: white;
    }

    .page-item.active .page-link {
        background-color: var(--blue-800);
        border-color: var(--blue-800);
        color: white;
        box-shadow: 0 10px 20px rgba(0, 85, 141, 0.18);
    }

    .page-item.disabled .page-link {
        background: #f8fafc;
        color: #bcc6d2;
    }

    .per-page {
      display: flex;
      align-items: center;
      gap: 12px;
      color: var(--muted);
      font-weight: 700;
      font-size: 14px;
    }

    .select-like {
      height: 42px;
      border-radius: 12px;
      border: 1px solid var(--line);
      background: white;
      padding: 0 16px;
      color: #50637c;
      font-weight: 800;
      display: inline-flex;
      align-items: center;
      gap: 10px;
      font-size: 14px;
    }

    .empty-state {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 60px 20px;
      text-align: center;
    }

    .empty-state i {
      font-size: 56px;
      color: #cbd5e1;
      margin-bottom: 16px;
    }

    .empty-state h3 {
      color: #475569;
      font-size: 18px;
      font-weight: 800;
      margin-bottom: 8px;
    }

    .empty-state p {
      color: #94a3b8;
      font-weight: 600;
      max-width: 400px;
    }

    .footer {
      display: flex;
      justify-content: space-between;
      color: #7d8da4;
      font-size: 13px;
      font-weight: 700;
      padding: 24px 4px 8px;
      margin-top: 8px;
    }

    @media (max-width: 1280px) {
      .reservation-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
      .kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }

    @media (max-width: 920px) {
      .reservation-grid { grid-template-columns: 1fr; }
      .toolbar { flex-direction: column; align-items: flex-start; }
      .page-head { flex-direction: column; }
      .card-footer { grid-template-columns: 1fr 1fr; }
      .view-btn { grid-column: 1 / -1; width: 100%; margin-top: 10px; }
    }

    @media (max-width: 560px) {
      .kpis { grid-template-columns: 1fr; }
      .pagination-row { flex-direction: column; align-items: flex-start; }
      .footer { flex-direction: column; align-items: flex-start; gap: 6px; }
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    {{-- Hero section --}}
    <div class="page-head">
        <div>
            <h1>Dossiers de reservation</h1>
            <p>Gerez et suivez toutes les reservations de vos clients.</p>
        </div>
        <a href="{{ route('admin.reservations.create') }}" class="primary-btn">
            <i class="bx bx-plus"></i> Creer un dossier
        </a>
    </div>

    {{-- KPI cards --}}
    <div class="kpis">
        <div class="kpi-card">
            <div class="kpi-icon blue"><i class="bx bx-collection"></i></div>
            <div>
                <span>Total</span>
                <strong>{{ $stats['total'] ?? 0 }}</strong>
                <small>Dossiers enregistres</small>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon orange"><i class="bx bx-time"></i></div>
            <div>
                <span>En attente</span>
                <strong>{{ $stats['pending'] ?? 0 }}</strong>
                <small>A confirmer ou en cours</small>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon purple"><i class="bx bx-hourglass"></i></div>
            <div>
                <span>Restant</span>
                <strong>{{ $stats['remaining'] ?? 0 }}</strong>
                <small>A solder</small>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon green"><i class="bx bx-check-double"></i></div>
            <div>
                <span>Payes</span>
                <strong>{{ $stats['paid'] ?? 0 }}</strong>
                <small>Totalement regles</small>
            </div>
        </div>
    </div>

    {{-- Main panel --}}
    <div class="panel">
        {{-- Toolbar with tabs + search --}}
        <div class="toolbar">
            <div class="tabs">
                @php
                    $currentStatus = $currentStatus ?? 'all';
                @endphp

                <a href="{{ route('admin.reservation-dossiers.index', array_merge(request()->except(['status', 'pending_only', 'payment_complete', 'remaining_only']), ['status' => 'all'])) }}"
                   class="tab {{ $currentStatus == 'all' ? 'active' : '' }}">
                    Tous
                </a>
                <a href="{{ route('admin.reservation-dossiers.index', array_merge(request()->except(['pending_only', 'payment_complete', 'remaining_only']), ['status' => 'pending'])) }}"
                   class="tab {{ $currentStatus == 'pending' ? 'active' : '' }}">
                    En attente <span class="pill-count orange">{{ $stats['pending'] ?? 0 }}</span>
                </a>
                <a href="{{ route('admin.reservation-dossiers.index', array_merge(request()->except(['pending_only', 'payment_complete', 'remaining_only']), ['status' => 'paid'])) }}"
                   class="tab {{ $currentStatus == 'paid' ? 'active' : '' }}">
                    Payes <span class="pill-count green">{{ $stats['paid'] ?? 0 }}</span>
                </a>
                <a href="{{ route('admin.reservation-dossiers.index', array_merge(request()->except(['pending_only', 'payment_complete', 'remaining_only']), ['status' => 'follow_up'])) }}"
                   class="tab {{ $currentStatus == 'follow_up' ? 'active' : '' }}">
                    A suivre <span class="pill-count purple">{{ $stats['remaining'] ?? 0 }}</span>
                </a>
            </div>
            <div class="tool-buttons d-flex gap-2">
                <form action="{{ route('admin.reservation-dossiers.index') }}" method="GET" class="d-flex gap-2">
                    @if($currentStatus && $currentStatus !== 'all')
                        <input type="hidden" name="status" value="{{ $currentStatus }}">
                    @endif
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Rechercher..." style="width: 220px; border-radius: 10px;">
                    <button type="submit" class="btn btn-sm btn-light border" style="border-radius: 10px;">
                        <i class="bx bx-search"></i>
                    </button>
                </form>
            </div>
        </div>

        {{-- Grid or empty state --}}
        @if($dossiers->count() > 0)
            <div class="reservation-grid">
                @foreach($dossiers as $dossier)
                    @php
                        $mainRes = $dossier->mainReservation ?: $dossier->reservations->first();
                        $offer = $mainRes?->offer;
                        $imageUrl = $offer?->featured_image_url;

                        // Statut paiement
                        $pStatus = $dossier->payment_status;
                        $statusClass = 'waiting';
                        $statusLabel = 'en attente';

                        if ($pStatus === 'paid' || ($dossier->remaining_amount !== null && $dossier->remaining_amount <= 0 && $dossier->total_amount > 0)) {
                            $statusClass = 'paid';
                            $statusLabel = 'paye';
                        } elseif ($dossier->paid_amount > 0 && $dossier->remaining_amount > 0) {
                            $statusClass = 'partial';
                            $statusLabel = 'partiel';
                        } elseif ($pStatus === 'unpaid') {
                            $statusClass = 'unpaid';
                            $statusLabel = 'non paye';
                        }
                    @endphp
                    <article class="reservation-card">
                        <div class="card-image" @if($imageUrl) style="background-image:url('{{ $imageUrl }}')" @endif>
                            @if(!$imageUrl)
                                <div class="no-image-placeholder">
                                    <i class="bx bx-image-alt" style="font-size: 28px;"></i>
                                    <span>Visuel indisponible</span>
                                </div>
                            @endif
                            <span class="status {{ $statusClass }}">{{ $statusLabel }}</span>
                        </div>
                        <div class="card-body">
                            <div class="card-title-row">
                                <h3>{{ $offer->name ?? 'Sans offre' }}</h3>
                            </div>
                            <div class="meta-grid">
                                <div>
                                    <span class="meta-label">Dossier</span>
                                    <span class="meta-value">{{ $dossier->dossier_number ?? ('RES-'.$dossier->id) }}</span>
                                </div>
                                <div>
                                    <span class="meta-label">Client</span>
                                    <span class="meta-value">
                                        {{ optional($dossier->client)->full_name
                                            ?? ($mainRes ? trim(($mainRes->client_first_name ?? '').' '.($mainRes->client_last_name ?? '')) : null)
                                            ?? 'Inconnu' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div>
                                <span class="foot-label">Depart</span>
                                <span class="foot-value">
                                    {{ $mainRes?->departure?->start_date
                                        ? $mainRes->departure->start_date->format('d/m/Y')
                                        : '--' }}
                                </span>
                            </div>
                            <div>
                                <span class="foot-label">Total</span>
                                <span class="foot-value">{{ number_format($dossier->total_amount, 2, ',', ' ') }} DH</span>
                            </div>
                            <div>
                                <span class="foot-label">Restant</span>
                                <span class="foot-value">{{ number_format($dossier->remaining_amount, 2, ',', ' ') }} DH</span>
                            </div>
                            <a href="{{ route('admin.reservation-dossiers.show', $dossier) }}" class="view-btn">Voir</a>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <div class="empty-state">
                <i class="bx bx-folder-open"></i>
                <h3>Aucun dossier trouve</h3>
                <p>Aucune reservation ne correspond a vos criteres de recherche. Essayez d'ajuster vos filtres ou creez un nouveau dossier.</p>
            </div>
        @endif

        {{-- Pagination --}}
        <div class="pagination-row">
            <div class="per-page">
                <span>Affichage de {{ $dossiers->firstItem() ?? 0 }} a {{ $dossiers->lastItem() ?? 0 }} sur {{ $dossiers->total() }} dossiers</span>
            </div>
            <div class="pagination">
                {{ $dossiers->links() }}
            </div>
        </div>
    </div>

    {{-- Footer --}}
    <div class="footer">
        <span>&copy; {{ date('Y') }} Ajinsafro.ma &mdash; Tous droits reserves.</span>
        <span>Admin V3</span>
    </div>
</div>
@endsection
