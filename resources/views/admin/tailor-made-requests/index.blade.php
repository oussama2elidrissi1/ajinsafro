@extends('layouts.admin-v6')

@section('title', 'Demande a la carte')
@section('page_title', 'Demande a la carte')
@section('hide_admin_footer', '1')

@php
    use App\Models\TailorMadeRequest;
    use Illuminate\Support\Str;

    $voyages = $voyages ?? collect();
    $stats = $stats ?? [];
    $filters = $filters ?? [];
    $currentStatus = $currentStatus ?? 'all';
    $statusOptions = $statusOptions ?? TailorMadeRequest::statusOptions();

    $statusBadge = function (?string $status) {
        $status = (string) ($status ?: TailorMadeRequest::STATUS_NEW);
        return match ($status) {
            TailorMadeRequest::STATUS_NEW => ['label' => 'Nouveau', 'class' => 'is-new'],
            TailorMadeRequest::STATUS_PENDING => ['label' => 'En attente', 'class' => 'is-pending'],
            TailorMadeRequest::STATUS_PROCESSING => ['label' => 'En cours', 'class' => 'is-processing'],
            TailorMadeRequest::STATUS_DONE => ['label' => 'Traite', 'class' => 'is-done'],
            TailorMadeRequest::STATUS_CANCELLED => ['label' => 'Annule', 'class' => 'is-cancelled'],
            default => ['label' => ucfirst(str_replace('_', ' ', $status)), 'class' => 'is-neutral'],
        };
    };
@endphp

@push('styles')
<style>
    /* Reuse the same visual language as reservation dossiers (rd-*) */
    .reservation-dossiers-page { padding-bottom: 24px; }
    .reservation-dossiers-page .rd-hero { display: none; }

    .reservation-dossiers-page .rd-page-kpis {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: 14px;
        margin-bottom: 24px;
    }

    .reservation-dossiers-page .rd-page-kpi {
        background: #fff;
        border: 1px solid #e5edf6;
        border-radius: 16px;
        box-shadow: 0 4px 12px rgba(16, 42, 67, 0.04);
        padding: 18px;
        display: flex;
        gap: 12px;
        align-items: center;
    }

    .reservation-dossiers-page .rd-page-kpi__icon {
        width: 46px;
        height: 46px;
        display: grid;
        place-items: center;
        border-radius: 14px;
        font-size: 20px;
        flex-shrink: 0;
    }

    .reservation-dossiers-page .rd-page-kpi__label {
        display: block;
        color: #6b7a90;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        margin-bottom: 4px;
    }

    .reservation-dossiers-page .rd-page-kpi__value {
        display: block;
        color: #102a43;
        font-size: 20px;
        font-weight: 900;
        line-height: 1.1;
    }

    .reservation-dossiers-page .rd-panel {
        background: #fff;
        border: 1px solid #e5edf6;
        border-radius: 20px;
        box-shadow: 0 8px 24px rgba(16, 42, 67, 0.06);
        padding: 22px;
    }

    .reservation-dossiers-page .rd-toolbar {
        display: flex;
        flex-direction: column;
        gap: 14px;
        margin-bottom: 22px;
    }

    .reservation-dossiers-page .rd-tabs {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .reservation-dossiers-page .rd-tab {
        display: inline-flex;
        align-items: center;
        padding: 8px 16px;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 700;
        text-decoration: none;
        color: #6b7a90;
        background: #f8fbff;
        border: 1px solid #e5edf6;
        transition: all .2s ease;
    }

    .reservation-dossiers-page .rd-tab.active,
    .reservation-dossiers-page .rd-tab:hover {
        background: #07598f;
        color: #fff;
        border-color: #07598f;
    }

    .reservation-dossiers-page .rd-filter-grid {
        display: grid;
        grid-template-columns: 1fr 220px 220px 220px;
        gap: 12px;
        align-items: end;
    }

    .reservation-dossiers-page .rd-filter-grid .full { grid-column: 1 / -1; }

    .reservation-dossiers-page .rd-card {
        background: #fff;
        border: 1px solid #e5edf6;
        border-radius: 16px;
        box-shadow: 0 4px 12px rgba(16, 42, 67, 0.04);
        margin-bottom: 14px;
        overflow: hidden;
    }

    .reservation-dossiers-page .rd-card__head {
        display: grid;
        grid-template-columns: 180px 1fr auto;
        gap: 18px;
        align-items: center;
        padding: 16px 20px;
    }

    .reservation-dossiers-page .rd-card__media {
        width: 180px;
        height: 115px;
        border-radius: 14px;
        overflow: hidden;
        background: #f5f8fc;
        flex-shrink: 0;
    }

    .reservation-dossiers-page .rd-card__media img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .reservation-dossiers-page .rd-card__placeholder {
        width: 100%;
        height: 100%;
        display: grid;
        place-items: center;
        color: #6b7a90;
        font-size: 12px;
        text-align: center;
        padding: 10px;
    }

    .reservation-dossiers-page .rd-card__placeholder i { font-size: 22px; margin-bottom: 2px; }

    .reservation-dossiers-page .rd-card__main {
        display: flex;
        flex-direction: column;
        gap: 12px;
        min-width: 0;
    }

    .reservation-dossiers-page .rd-card__top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 10px;
        flex-wrap: wrap;
    }

    .reservation-dossiers-page .rd-card__title h3 {
        font-size: 17px;
        font-weight: 800;
        margin: 0;
        color: #102a43;
        line-height: 1.25;
    }

    .reservation-dossiers-page .rd-card__title p {
        margin: 4px 0 0;
        color: #6b7a90;
        font-weight: 600;
        font-size: 13px;
    }

    .reservation-dossiers-page .rd-mini-kpis {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: 10px;
    }

    .reservation-dossiers-page .rd-mini-kpi {
        background: #f8fbff;
        border: 1px solid #e5edf6;
        border-radius: 12px;
        padding: 10px 12px;
        display: flex;
        justify-content: space-between;
        gap: 10px;
        align-items: center;
        font-size: 12px;
        font-weight: 700;
        color: #6b7a90;
    }

    .reservation-dossiers-page .rd-mini-kpi strong { color: #102a43; }

    .reservation-dossiers-page .rd-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        border-radius: 12px;
        padding: 12px 18px;
        font-weight: 700;
        font-size: 14px;
        text-decoration: none;
        border: 1px solid transparent;
        transition: all .2s ease;
        cursor: pointer;
        white-space: nowrap;
    }

    .reservation-dossiers-page .rd-btn-primary {
        color: #fff !important;
        background: linear-gradient(135deg, #0877bd, #073b63);
        box-shadow: 0 8px 20px rgba(8, 119, 189, 0.22);
    }

    .reservation-dossiers-page .rd-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 800;
        border: 1px solid transparent;
        white-space: nowrap;
    }

    .reservation-dossiers-page .rd-badge.is-new { background:#fff7ed; color:#f59e0b; border-color:#fed7aa; }
    .reservation-dossiers-page .rd-badge.is-pending { background:#fff2e8; color:#f97316; border-color:#fed7aa; }
    .reservation-dossiers-page .rd-badge.is-processing { background:#eaf5ff; color:#0877bd; border-color:#cfe7ff; }
    .reservation-dossiers-page .rd-badge.is-done { background:#e8fff4; color:#12b76a; border-color:#bbf7d0; }
    .reservation-dossiers-page .rd-badge.is-cancelled { background:#ffe8e8; color:#ef4444; border-color:#fecaca; }
    .reservation-dossiers-page .rd-badge.is-neutral { background:#f3f4f6; color:#6b7280; border-color:#e5e7eb; }

    .reservation-dossiers-page .rd-table-wrap { padding: 0 20px 18px; }

    .reservation-dossiers-page .rd-row-actions {
        display: flex;
        gap: 8px;
        align-items: center;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .reservation-dossiers-page .rd-mini-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border-radius: 12px;
        border: 1px solid #e5edf6;
        background: #ffffff;
        color: #102a43;
        padding: 8px 12px;
        font-weight: 800;
        font-size: 12px;
        text-decoration: none;
        transition: all .15s ease;
        white-space: nowrap;
    }

    .reservation-dossiers-page .rd-mini-btn:hover { background: #f8fbff; }
    .reservation-dossiers-page .rd-mini-btn--danger { border-color: #fee2e2; color: #b91c1c; }
    .reservation-dossiers-page .rd-mini-btn--danger:hover { background: #dc2626; color: #ffffff; border-color: #dc2626; }

    .reservation-dossiers-page .rd-pagination {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin-top: 18px;
        flex-wrap: wrap;
        color: #6b7a90;
        font-weight: 700;
        font-size: 13px;
    }

    @media (max-width: 1200px) {
        .reservation-dossiers-page .rd-page-kpis { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .reservation-dossiers-page .rd-filter-grid { grid-template-columns: 1fr 1fr; }
        .reservation-dossiers-page .rd-mini-kpis { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }

    @media (max-width: 768px) {
        .reservation-dossiers-page .rd-page-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .reservation-dossiers-page .rd-card__head { grid-template-columns: 1fr; }
        .reservation-dossiers-page .rd-card__media { width: 100%; height: 160px; }
        .reservation-dossiers-page .rd-mini-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .reservation-dossiers-page .rd-row-actions { justify-content: flex-start; }
    }
</style>
@endpush

@section('content')
<div class="reservation-dossiers-page">
    <div class="rd-page-kpis">
        <div class="rd-page-kpi">
            <div class="rd-page-kpi__icon" style="background:#eaf5ff;color:#0877bd;"><i class="bx bx-layer"></i></div>
            <div><span class="rd-page-kpi__label">Demandes actives</span><strong class="rd-page-kpi__value">{{ $stats['active'] ?? 0 }}</strong></div>
        </div>
        <div class="rd-page-kpi">
            <div class="rd-page-kpi__icon" style="background:#eaf5ff;color:#0877bd;"><i class="bx bx-collection"></i></div>
            <div><span class="rd-page-kpi__label">Total demandes</span><strong class="rd-page-kpi__value">{{ $stats['total'] ?? 0 }}</strong></div>
        </div>
        <div class="rd-page-kpi">
            <div class="rd-page-kpi__icon" style="background:#fff2e8;color:#f97316;"><i class="bx bx-time-five"></i></div>
            <div><span class="rd-page-kpi__label">En attente</span><strong class="rd-page-kpi__value">{{ $stats['pending'] ?? 0 }}</strong></div>
        </div>
        <div class="rd-page-kpi">
            <div class="rd-page-kpi__icon" style="background:#eaf5ff;color:#0877bd;"><i class="bx bx-bolt-circle"></i></div>
            <div><span class="rd-page-kpi__label">A traiter</span><strong class="rd-page-kpi__value">{{ $stats['to_process'] ?? 0 }}</strong></div>
        </div>
        <div class="rd-page-kpi">
            <div class="rd-page-kpi__icon" style="background:#e8fff4;color:#12b76a;"><i class="bx bx-check-circle"></i></div>
            <div><span class="rd-page-kpi__label">Traitees</span><strong class="rd-page-kpi__value">{{ $stats['done'] ?? 0 }}</strong></div>
        </div>
        <div class="rd-page-kpi">
            <div class="rd-page-kpi__icon" style="background:#ffe8e8;color:#ef4444;"><i class="bx bx-x-circle"></i></div>
            <div><span class="rd-page-kpi__label">Annulees</span><strong class="rd-page-kpi__value">{{ $stats['cancelled'] ?? 0 }}</strong></div>
        </div>
    </div>

    <div class="rd-panel">
        <div class="rd-toolbar">
            <div class="rd-tabs">
                <a href="{{ route('admin.tailor-made-requests.index', array_merge(request()->except('status'), ['status' => 'all'])) }}" class="rd-tab {{ $currentStatus === 'all' ? 'active' : '' }}">Tous</a>
                <a href="{{ route('admin.tailor-made-requests.index', array_merge(request()->except('status'), ['status' => 'active'])) }}" class="rd-tab {{ $currentStatus === 'active' ? 'active' : '' }}">Actifs</a>
                <a href="{{ route('admin.tailor-made-requests.index', array_merge(request()->except('status'), ['status' => 'pending'])) }}" class="rd-tab {{ $currentStatus === 'pending' ? 'active' : '' }}">En attente</a>
                <a href="{{ route('admin.tailor-made-requests.index', array_merge(request()->except('status'), ['status' => 'processing'])) }}" class="rd-tab {{ $currentStatus === 'processing' ? 'active' : '' }}">En cours</a>
                <a href="{{ route('admin.tailor-made-requests.index', array_merge(request()->except('status'), ['status' => 'done'])) }}" class="rd-tab {{ $currentStatus === 'done' ? 'active' : '' }}">Traite</a>
            </div>

            <form method="GET" action="{{ route('admin.tailor-made-requests.index') }}" class="rd-filter-grid">
                @if($currentStatus !== 'all')
                    <input type="hidden" name="status" value="{{ $currentStatus }}">
                @endif
                <div class="full">
                    <label class="form-label">Recherche voyage / client / telephone</label>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="Ex. Merzouga, Oussama, +2126...">
                </div>
                <div>
                    <label class="form-label">Voyage</label>
                    <select name="voyage_id" class="form-select">
                        <option value="">Tous</option>
                        @foreach(($voyageOptions ?? collect()) as $option)
                            <option value="{{ $option->id }}" @selected((string) ($filters['voyage_id'] ?? '') === (string) $option->id)>{{ $option->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Statut</label>
                    <select name="request_status" class="form-select">
                        <option value="">Tous</option>
                        @foreach($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected((string) ($filters['request_status'] ?? '') === (string) $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Periode</label>
                    <select name="period" class="form-select">
                        <option value="7d" @selected(($filters['period'] ?? '30d') === '7d')>7 derniers jours</option>
                        <option value="30d" @selected(($filters['period'] ?? '30d') === '30d')>30 derniers jours</option>
                        <option value="90d" @selected(($filters['period'] ?? '') === '90d')>90 derniers jours</option>
                        <option value="all" @selected(($filters['period'] ?? '') === 'all')>Toutes les periodes</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Date demandee</label>
                    <input type="date" name="requested_date" value="{{ $filters['requested_date'] ?? '' }}" class="form-control">
                </div>
                <div>
                    <label class="form-label">Lieu de depart</label>
                    <input type="text" name="departure_place" value="{{ $filters['departure_place'] ?? '' }}" class="form-control" placeholder="Ex. Tanger">
                </div>
                <div>
                    <label class="form-label">Par page</label>
                    <select name="per_page" class="form-select">
                        @foreach([6,9,12,18,24] as $n)
                            <option value="{{ $n }}" @selected((int) ($filters['per_page'] ?? 9) === $n)>{{ $n }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="full d-flex gap-2">
                    <button class="rd-btn rd-btn-primary" type="submit"><i class="bx bx-filter-alt"></i><span>Filtrer</span></button>
                    <a class="rd-btn" href="{{ route('admin.tailor-made-requests.index') }}" style="border-color:#e5edf6;background:#fff;"><i class="bx bx-reset"></i><span>Reinitialiser</span></a>
                </div>
            </form>
        </div>

        @if($voyages instanceof \Illuminate\Pagination\LengthAwarePaginator ? $voyages->count() : $voyages->count())
            <div>
                @foreach($voyages as $voyageCard)
                    @php
                        $collapseId = 'voyage-'.Str::slug((string) $voyageCard->key);
                    @endphp
                    <article class="rd-card">
                        <div class="rd-card__head">
                            <div class="rd-card__media">
                                @if(!empty($voyageCard->image_url))
                                    <img src="{{ $voyageCard->image_url }}" alt="{{ $voyageCard->title }}">
                                @else
                                    <div class="rd-card__placeholder">
                                        <div>
                                            <i class="bx bx-image"></i>
                                            <div>Image indisponible</div>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <div class="rd-card__main">
                                <div class="rd-card__top">
                                    <div class="rd-card__title">
                                        <h3>{{ $voyageCard->title }}</h3>
                                        <p>
                                            @if(!empty($voyageCard->destination))<span>{{ $voyageCard->destination }}</span>@endif
                                            <span class="ms-2">|</span>
                                            <span class="ms-2">{{ $voyageCard->requested_date_label }}</span>
                                        </p>
                                    </div>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <span class="rd-badge is-processing">Demandes: {{ $voyageCard->requests_count }}</span>
                                        <span class="rd-badge is-new">Nouveau: {{ $voyageCard->new_count }}</span>
                                        <span class="rd-badge is-pending">En attente: {{ $voyageCard->pending_count }}</span>
                                        <span class="rd-badge is-done">Traite: {{ $voyageCard->done_count }}</span>
                                        <span class="rd-badge is-cancelled">Annule: {{ $voyageCard->cancelled_count }}</span>
                                    </div>
                                </div>

                                <div class="rd-mini-kpis">
                                    <div class="rd-mini-kpi"><span>Total</span> <strong>{{ $voyageCard->requests_count }}</strong></div>
                                    <div class="rd-mini-kpi"><span>Nouveau</span> <strong>{{ $voyageCard->new_count }}</strong></div>
                                    <div class="rd-mini-kpi"><span>En attente</span> <strong>{{ $voyageCard->pending_count }}</strong></div>
                                    <div class="rd-mini-kpi"><span>En cours</span> <strong>{{ $voyageCard->processing_count }}</strong></div>
                                    <div class="rd-mini-kpi"><span>Traite</span> <strong>{{ $voyageCard->done_count }}</strong></div>
                                    <div class="rd-mini-kpi"><span>Annule</span> <strong>{{ $voyageCard->cancelled_count }}</strong></div>
                                </div>
                            </div>

                            <div class="d-flex flex-column gap-2 align-items-end">
                                <button class="rd-btn rd-btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}" aria-expanded="false">
                                    <i class="bx bx-list-ul"></i>
                                    <span>Voir les demandes</span>
                                </button>
                            </div>
                        </div>

                        <div class="collapse" id="{{ $collapseId }}">
                            <div class="rd-table-wrap">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Reference</th>
                                                <th>Client</th>
                                                <th>Telephone</th>
                                                <th>Email</th>
                                                <th>Lieu demande</th>
                                                <th>Date demandee</th>
                                                <th>Voyageurs</th>
                                                <th>Cree le</th>
                                                <th>Statut</th>
                                                <th class="text-end">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($voyageCard->requests as $req)
                                                @php
                                                    $badge = $statusBadge($req->status);
                                                    $detailUrl = route('admin.tailor-made-requests.show', $req);
                                                @endphp
                                                <tr style="cursor:pointer;" onclick="if(!event.target.closest('a,button,select,form,option')){window.location.href='{{ $detailUrl }}'}">
                                                    <td><a href="{{ $detailUrl }}" class="fw-semibold text-decoration-none">{{ $req->reference }}</a></td>
                                                    <td>{{ trim(($req->client_first_name ?? '').' '.($req->client_last_name ?? '')) ?: '-' }}</td>
                                                    <td>{{ $req->client_phone ?: '-' }}</td>
                                                    <td>{{ $req->client_email ?: '-' }}</td>
                                                    <td>{{ $req->custom_departure_place ?: '-' }}</td>
                                                    <td>{{ $req->custom_departure_date ? $req->custom_departure_date->format('d/m/Y') : '-' }}</td>
                                                    <td>{{ $req->travellers_total ?: (($req->adults ?? 0)+($req->children ?? 0)) }}</td>
                                                    <td>{{ $req->created_at ? $req->created_at->format('d/m/Y H:i') : '-' }}</td>
                                                    <td><span class="rd-badge {{ $badge['class'] }}">{{ $badge['label'] }}</span></td>
                                                    <td class="text-end">
                                                        <div class="rd-row-actions">
                                                            <a href="{{ $detailUrl }}" class="rd-mini-btn"><i class="bx bx-show"></i><span>Voir</span></a>
                                                            <a href="{{ $detailUrl }}?print=1" target="_blank" class="rd-mini-btn"><i class="bx bx-printer"></i><span>Imprimer</span></a>
                                                            @can('reservations.update')
                                                                <form method="POST" action="{{ route('admin.tailor-made-requests.status', $req) }}">
                                                                    @csrf
                                                                    @method('PATCH')
                                                                    <select name="status" class="form-select form-select-sm" style="min-width: 160px;" onchange="this.form.submit()">
                                                                        @foreach($statusOptions as $value => $label)
                                                                            <option value="{{ $value }}" @selected((string) $req->status === (string) $value)>{{ $label }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </form>
                                                            @endcan
                                                            @can('reservations.delete')
                                                                <form method="POST" action="{{ route('admin.tailor-made-requests.destroy', $req) }}" class="js-delete-tmr-form" style="display:inline;">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="rd-mini-btn rd-mini-btn--danger" data-confirm-delete="Voulez-vous vraiment supprimer cette demande ? Cette action est irreversible.">
                                                                        <i class="bx bx-trash"></i><span>Supprimer</span>
                                                                    </button>
                                                                </form>
                                                            @endcan
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="10" class="text-center py-4 text-muted">Aucune demande pour ce voyage</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="rd-pagination">
                <div>
                    Affichage de {{ $voyages->firstItem() ?? 0 }} a {{ $voyages->lastItem() ?? 0 }} sur {{ $voyages->total() }} voyages
                </div>
                <div>{{ $voyages->links() }}</div>
            </div>
        @else
            <div class="text-center text-muted py-5">
                <i class="bx bx-layer" style="font-size: 42px;"></i>
                <h3 class="h5 mt-2">Aucune demande a la carte</h3>
                <p class="mb-0">Aucune demande ne correspond aux filtres actuels.</p>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    document.querySelectorAll('.js-delete-tmr-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var message = form.querySelector('[data-confirm-delete]')?.dataset.confirmDelete
                || 'Voulez-vous vraiment supprimer cette demande ?';
            if (confirm(message)) {
                form.submit();
            }
        });
    });
})();
</script>
@endpush
