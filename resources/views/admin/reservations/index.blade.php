@extends('layouts.admin-v6')

@section('title', 'Reservations')

@php
    use App\Services\ReservationHubTableProfile;

    $hubTableMode = $hubTableMode ?? ReservationHubTableProfile::MODE_OPERATIONS;
    $hubVoyageFiltered = $hubVoyageFiltered ?? false;
    $showCrossAgencyBranchCol = $hubVoyageFiltered
        && ($hubTableMode === ReservationHubTableProfile::MODE_AGENCY || $hubTableMode === ReservationHubTableProfile::MODE_OPERATIONS);
    $hubStats = $hubStats ?? ['total' => 0, 'en_cours' => 0, 'validee' => 0, 'annulee' => 0];
    $filterTourId = $filterTourId ?? null;
    $filterTravelDateId = $filterTravelDateId ?? null;
    $filterSearch = $filterSearch ?? null;
    $filterStatus = $filterStatus ?? null;
    $filterChannel = $filterChannel ?? null;
    $isClientChannel = $filterChannel === 'client';
    $highlightReservationId = $highlightReservationId ?? 0;
    $voyage = $voyage ?? null;
    $voyageOptions = $voyageOptions ?? collect();
    $reservationVisibility = is_array($reservationVisibility ?? null) ? $reservationVisibility : [];
    $limitedReservationPresentation = (bool) ($reservationVisibility['limited_presentation'] ?? false);
    $canViewReservationFinancial = (bool) ($reservationVisibility['view_financial'] ?? false);
    $canViewAssignmentContext = (bool) ($reservationVisibility['view_assignment_context'] ?? false);
    $canViewClientContact = (bool) ($reservationVisibility['view_client_contact'] ?? false);
    $reservationCreated = isset($reservationCreated) && is_array($reservationCreated)
        ? $reservationCreated
        : (session('reservation_created') ?: null);
    $allReservationsUrl = route('admin.reservations.index');
    $resetUrl = $isClientChannel ? route('admin.reservations.index', ['channel' => 'client']) : $allReservationsUrl;
@endphp

@section('content')
    <div id="res-hub-root"
         class="d-none"
         data-res-base="{{ rtrim(url('/admin/reservations'), '/') }}"
         data-csrf="{{ csrf_token() }}"
         data-can-edit="{{ auth()->user()->can('reservations.edit') ? '1' : '0' }}"
         data-can-update="{{ auth()->user()->can('reservations.update') ? '1' : '0' }}"
         @can('reservations.view')
         data-hub-refresh-url="{{ route('admin.reservations.hub-refresh') }}"
         @endcan
         @if(config('app.debug') && auth()->user()->can('reservations.view'))
         data-hub-debug-url="{{ route('admin.reservations.hub-debug') }}"
         @endif
    ></div>

    <div class="res-hub-page">
        <div class="page-title-box res-hub-hero">
            <div class="res-hub-hero__copy">
                <span class="res-hub-hero__eyebrow">Ajinsafro Admin</span>
                <h1 class="page-title res-hub-hero__title mb-0">RESERVATIONS</h1>
                <p class="res-hub-hero__subtitle mb-1">Gerez et suivez toutes les reservations.</p>
                <p class="res-hub-hero__meta mb-0">
                    Filtres, statistiques et liste synchronises en temps reel.
                    @if($showCrossAgencyBranchCol)
                        <span class="d-block mt-1 text-primary">Vision operationnelle partagee active sur ce perimetre.</span>
                    @endif
                </p>
            </div>
            <div class="res-hub-hero__actions">
                @can('reservations.view')
                    <a href="{{ route('admin.reservations.workspace') }}" class="btn res-hub-btn res-hub-btn--soft">
                        <i class="bx bx-grid-alt"></i>
                        <span>Catalogue (reserver)</span>
                    </a>
                @endcan
                <a href="{{ route('admin.reservations.create') }}" class="btn res-hub-btn res-hub-btn--primary">
                    <i class="bx bx-plus"></i>
                    <span>Nouvelle reservation</span>
                </a>
                @if(config('app.debug') && auth()->user()->can('reservations.view'))
                    <button type="button" class="btn res-hub-btn res-hub-btn--danger" id="btn-res-hub-debug" data-bs-toggle="modal" data-bs-target="#resHubDebugModal" title="APP_DEBUG : liste brute des reservations">
                        <i class="bx bx-bug"></i>
                        <span>Debug reservation</span>
                    </button>
                @endif
            </div>
        </div>

        @if(session('success') && empty($reservationCreated))
            <div class="alert alert-success alert-dismissible fade show res-hub-alert" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(is_array($reservationCreated))
            <div class="alert alert-success fade show res-hub-alert res-hub-alert--success mb-4" role="status" id="res-hub-created-banner">
                <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                    <div class="flex-grow-1 min-w-0">
                        <strong class="d-block mb-2"><i class="bx bx-check-circle me-1"></i> Reservation creee avec succes</strong>
                        <dl class="row mb-0 small">
                            <dt class="col-sm-4 col-md-3 text-muted fw-normal mb-1">No reservation</dt>
                            <dd class="col-sm-8 col-md-9 mb-1"><strong>#{{ $reservationCreated['id'] ?? '-' }}</strong></dd>
                            <dt class="col-sm-4 col-md-3 text-muted fw-normal mb-1">Offre liee</dt>
                            <dd class="col-sm-8 col-md-9 mb-1">{{ $reservationCreated['voyage_name'] ?? '-' }}</dd>
                            <dt class="col-sm-4 col-md-3 text-muted fw-normal mb-1">Date de depart</dt>
                            <dd class="col-sm-8 col-md-9 mb-1">{{ $reservationCreated['departure_label'] ?? '-' }}</dd>
                            <dt class="col-sm-4 col-md-3 text-muted fw-normal mb-1">Personnes</dt>
                            <dd class="col-sm-8 col-md-9 mb-1">{{ $reservationCreated['pax_count'] ?? '-' }}</dd>
                            @if($canViewReservationFinancial)
                                <dt class="col-sm-4 col-md-3 text-muted fw-normal mb-1">Total</dt>
                                <dd class="col-sm-8 col-md-9 mb-1">{{ $reservationCreated['total_label'] ?? '-' }}</dd>
                            @endif
                            <dt class="col-sm-4 col-md-3 text-muted fw-normal mb-0">Statut</dt>
                            <dd class="col-sm-8 col-md-9 mb-0">{{ $reservationCreated['status_label'] ?? '-' }}</dd>
                        </dl>
                    </div>
                    <div class="d-flex flex-wrap gap-2 align-items-center flex-shrink-0">
                        <a href="{{ $reservationCreated['urls']['edit'] ?? '#' }}" class="btn res-hub-btn res-hub-btn--primary btn-sm">Voir la reservation</a>
                        <button type="button" class="btn res-hub-btn res-hub-btn--soft btn-sm" data-bs-dismiss="alert">Fermer</button>
                    </div>
                </div>
            </div>
        @endif

        <div class="card res-hub-filter-card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="get" action="{{ route('admin.reservations.index') }}" class="res-hub-filter-grid">
                    @if($filterChannel)
                        <input type="hidden" name="channel" value="{{ $filterChannel }}">
                    @endif
                    <div class="res-hub-field">
                        <label class="form-label">Offre</label>
                        <select name="voyage_id" class="form-select">
                            <option value="">Tous</option>
                            @foreach($voyageOptions as $v)
                                <option value="{{ $v->id }}" @selected((string) $filterTourId === (string) $v->id)>{{ $v->resolved_name ?? $v->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="res-hub-field">
                        <label class="form-label">Depart (TravelDate id)</label>
                        <input type="number" name="travel_date_id" class="form-control" placeholder="ex. 234" value="{{ $filterTravelDateId }}">
                    </div>
                    <div class="res-hub-field">
                        <label class="form-label">Statut</label>
                        <select name="status" class="form-select">
                            <option value="">Tous</option>
                            <option value="{{ \App\Models\Reservation::STATUS_EN_COURS }}" @selected($filterStatus === \App\Models\Reservation::STATUS_EN_COURS)>En attente</option>
                            <option value="{{ \App\Models\Reservation::STATUS_SHARED_ROOM_PENDING }}" @selected($filterStatus === \App\Models\Reservation::STATUS_SHARED_ROOM_PENDING)>En attente de jumelage demi-double</option>
                            <option value="{{ \App\Models\Reservation::STATUS_SHARED_ROOM_PAIRED }}" @selected($filterStatus === \App\Models\Reservation::STATUS_SHARED_ROOM_PAIRED)>Demi-double jumelee</option>
                            <option value="{{ \App\Models\Reservation::STATUS_VALIDEE }}" @selected($filterStatus === \App\Models\Reservation::STATUS_VALIDEE)>Confirmee</option>
                            <option value="{{ \App\Models\Reservation::STATUS_ANNULEE }}" @selected($filterStatus === \App\Models\Reservation::STATUS_ANNULEE)>Annulee</option>
                        </select>
                    </div>
                    <div class="res-hub-field">
                        <label class="form-label">Recherche client</label>
                        <input type="text" name="search" class="form-control" placeholder="{{ $canViewClientContact ? 'Nom, email, telephone...' : 'Nom client ou voyage...' }}" value="{{ $filterSearch }}">
                    </div>
                    <div class="res-hub-field res-hub-field--actions">
                        <button type="submit" class="btn res-hub-btn res-hub-btn--primary w-100">
                            <i class="bx bx-filter-alt"></i>
                            <span>Filtrer</span>
                        </button>
                    </div>
                    <div class="res-hub-field res-hub-field--actions">
                        <a href="{{ $resetUrl }}" class="btn res-hub-btn res-hub-btn--soft w-100">
                            <i class="bx bx-refresh"></i>
                            <span>Reinitialiser</span>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card res-hub-kpi res-hub-kpi--blue border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="res-hub-kpi__icon"><i class="bx bx-calendar-check"></i></div>
                        <div class="res-hub-kpi__copy">
                            <div class="res-hub-kpi__label">Total filtrees</div>
                            <div class="res-hub-kpi__value" id="res-hub-stat-total">{{ $hubStats['total'] }}</div>
                            <div class="res-hub-kpi__meta">Reservations</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card res-hub-kpi res-hub-kpi--amber border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="res-hub-kpi__icon"><i class="bx bx-time-five"></i></div>
                        <div class="res-hub-kpi__copy">
                            <div class="res-hub-kpi__label">En attente</div>
                            <div class="res-hub-kpi__value" id="res-hub-stat-en-cours">{{ $hubStats['en_cours'] }}</div>
                            <div class="res-hub-kpi__meta">Reservations</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card res-hub-kpi res-hub-kpi--green border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="res-hub-kpi__icon"><i class="bx bx-check-circle"></i></div>
                        <div class="res-hub-kpi__copy">
                            <div class="res-hub-kpi__label">Confirmees</div>
                            <div class="res-hub-kpi__value" id="res-hub-stat-validee">{{ $hubStats['validee'] }}</div>
                            <div class="res-hub-kpi__meta">Reservations</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card res-hub-kpi res-hub-kpi--red border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="res-hub-kpi__icon"><i class="bx bx-x-circle"></i></div>
                        <div class="res-hub-kpi__copy">
                            <div class="res-hub-kpi__label">Annulees</div>
                            <div class="res-hub-kpi__value" id="res-hub-stat-annulee">{{ $hubStats['annulee'] }}</div>
                            <div class="res-hub-kpi__meta">Reservations</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card res-hub-table-card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="res-hub-table-head">
                    <div>
                        <h2 class="res-hub-table-title">Liste des reservations</h2>
                        <p class="res-hub-table-subtitle mb-0">Vue operationnelle plus claire, plus lisible et prete pour un usage quotidien.</p>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 reservations-table">
                        <thead>
                            <tr>
                                <th class="ps-3">#</th>
                                <th>Client</th>
                                <th>Offre / voyage</th>
                                @if(!$isClientChannel && !$limitedReservationPresentation)
                                    @if($hubTableMode === ReservationHubTableProfile::MODE_NETWORK)
                                        <th>Point de vente</th>
                                    @elseif($showCrossAgencyBranchCol)
                                        <th>Point de vente</th>
                                    @endif
                                @endif
                                <th>Date depart</th>
                                <th>Passagers</th>
                                @if($isClientChannel)
                                    @if($canViewReservationFinancial)
                                        <th>Paiement</th>
                                    @endif
                                    <th>Statut</th>
                                    @if($canViewAssignmentContext)
                                        <th>Creee le</th>
                                        <th>Chef commercial</th>
                                    @endif
                                @else
                                    @if($limitedReservationPresentation)
                                        <th>Statut</th>
                                    @elseif($hubTableMode === ReservationHubTableProfile::MODE_OPERATIONS)
                                        <th>Statut</th>
                                        @if($canViewReservationFinancial)
                                            <th>Paiement</th>
                                        @endif
                                    @else
                                        @if($canViewReservationFinancial)
                                            <th>Paiement</th>
                                        @endif
                                        <th>Statut</th>
                                    @endif
                                    @if(!$limitedReservationPresentation && $hubTableMode !== ReservationHubTableProfile::MODE_OPERATIONS && $canViewAssignmentContext)
                                        <th>Creee le</th>
                                    @endif
                                    @if(!$limitedReservationPresentation && $hubTableMode === ReservationHubTableProfile::MODE_NETWORK && $canViewAssignmentContext)
                                        <th>Creee par</th>
                                        <th>Reservation effectuee par</th>
                                        <th>Chef commercial</th>
                                    @endif
                                @endif
                                <th class="text-end pe-3" style="min-width:220px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="res-hub-tbody">
                            @include('admin.reservations.partials.hub-table-rows', [
                                'reservations' => $reservations,
                                'highlightReservationId' => $highlightReservationId,
                                'hubTableMode' => $hubTableMode,
                                'hubVoyageFiltered' => $hubVoyageFiltered,
                                'filterChannel' => $filterChannel,
                            ])
                        </tbody>
                    </table>
                </div>
                @if(method_exists($reservations, 'links'))
                    <div class="px-3 py-3 border-top" id="res-hub-pagination">{{ $reservations->links() }}</div>
                @endif
            </div>
        </div>
    </div>

    <div class="modal fade" id="resHubDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content res-hub-detail-modal">
                <div class="modal-header">
                    <h5 class="modal-title" id="resHubDetailTitle">Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body small" id="resHubDetailBody">
                    <p class="text-muted mb-0">Chargement...</p>
                </div>
                <div class="modal-footer border-0 pt-0" id="resHubDetailFooter" style="display:none;">
                    <button type="button" class="btn res-hub-btn res-hub-btn--soft" data-bs-dismiss="modal">Fermer</button>
                    <button type="button" class="btn res-hub-btn res-hub-btn--soft" id="resHubDetailEditBtn" style="display:none;">
                        <i class="bx bx-pencil"></i>
                        <span>Modifier la reservation</span>
                    </button>
                    <button type="button" class="btn res-hub-btn res-hub-btn--primary" id="resHubDetailValidateBtn" style="display:none;">
                        <i class="bx bx-check"></i>
                        <span>Confirmer la reservation</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="resHubValidateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content res-hub-confirm-modal">
                <div class="modal-header border-0 pb-0">
                    <div class="res-hub-confirm-modal__head">
                        <span class="res-hub-confirm-modal__icon">
                            <i class="bx bx-check-shield"></i>
                        </span>
                        <div>
                            <h5 class="modal-title mb-1">Confirmer la validation</h5>
                            <p class="text-muted small mb-0">Cette action mettra la reservation au statut confirme.</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-3">
                    <div class="res-hub-confirm-modal__card">
                        <div class="res-hub-confirm-modal__row">
                            <span>Reservation</span>
                            <strong id="resHubValidateId">-</strong>
                        </div>
                        <div class="res-hub-confirm-modal__row">
                            <span>Client</span>
                            <strong id="resHubValidateClient">-</strong>
                        </div>
                        <div class="res-hub-confirm-modal__row">
                            <span>Offre</span>
                            <strong id="resHubValidateOffer">-</strong>
                        </div>
                        <div class="res-hub-confirm-modal__row">
                            <span>Date depart</span>
                            <strong id="resHubValidateDate">-</strong>
                        </div>
                        <div class="res-hub-confirm-modal__row">
                            <span>Statut actuel</span>
                            <strong id="resHubValidateStatus">-</strong>
                        </div>
                    </div>
                    <div class="alert alert-light border mt-3 mb-0 small">
                        Verifiez les informations avant validation. Cette operation utilise la logique de confirmation existante.
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn res-hub-btn res-hub-btn--soft" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn res-hub-btn res-hub-btn--primary" id="resHubValidateConfirmBtn">
                        <i class="bx bx-check"></i>
                        <span>Valider la reservation</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    @if(config('app.debug') && auth()->user()->can('reservations.view'))
        <div class="modal fade" id="resHubDebugModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Debug - reservations (filtres page actuels)</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small mb-2">Donnees JSON + tableau. Meme requete que la liste. Max 500 lignes. Visible uniquement si <code>APP_DEBUG=true</code>.</p>
                        <ul class="small mb-3" id="resHubDebugMeta"></ul>
                        <pre class="bg-light border rounded p-2 small mb-3" style="max-height:220px;overflow:auto;" id="resHubDebugJson"></pre>
                        <div class="table-responsive" style="max-height:50vh;">
                            <table class="table table-sm table-bordered align-middle mb-0" id="resHubDebugTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Client</th>
                                        <th>tour_id</th>
                                        <th>Offre</th>
                                        <th>Creee par</th>
                                        <th>Agence</th>
                                        <th>wp tour</th>
                                        <th>catalog</th>
                                        <th>vol id</th>
                                        <th>prest.</th>
                                        <th>td id</th>
                                        <th>Depart</th>
                                        <th>Statut</th>
                                        <th>Creee</th>
                                        <th>Pax</th>
                                    </tr>
                                </thead>
                                <tbody id="resHubDebugTbody">
                                    <tr><td colspan="15" class="text-muted">Ouvrez le modal pour charger...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="modal fade" id="resHubPaxModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="resHubPaxTitle">Participants</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0" id="resHubPaxBody">
                    <p class="text-muted p-3 mb-0">Chargement...</p>
                </div>
            </div>
        </div>
    </div>

    <div class="offcanvas offcanvas-end" tabindex="-1" id="resHubEditOffcanvas" style="width:min(960px, 100vw);">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title">Modifier la reservation</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body p-0" style="height:calc(100vh - 56px);">
            <iframe id="resHubEditFrame" class="w-100 h-100 border-0" title="Edition reservation"></iframe>
        </div>
    </div>

    <style>
        .res-hub-page {
            max-width: 100%;
        }
        .res-hub-hero {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1.5rem;
            padding: 1.5rem 0 1.25rem;
            margin-bottom: 1rem;
        }
        .res-hub-hero__copy {
            min-width: 0;
        }
        .res-hub-hero__eyebrow {
            display: inline-block;
            margin-bottom: .55rem;
            color: #2b6de5;
            font-size: .76rem;
            font-weight: 800;
            letter-spacing: .12em;
            text-transform: uppercase;
        }
        .res-hub-hero__title {
            font-size: clamp(1.7rem, 2vw, 2.35rem);
            font-weight: 800;
            letter-spacing: -.04em;
            color: #15315f;
        }
        .res-hub-hero__subtitle {
            margin-top: .35rem;
            color: #334c76;
            font-size: 1rem;
            font-weight: 600;
        }
        .res-hub-hero__meta {
            color: #6d7f9d;
            font-size: .92rem;
        }
        .res-hub-hero__actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: .85rem;
        }
        .res-hub-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .55rem;
            min-height: 46px;
            padding: 0 1rem;
            border-radius: 14px;
            font-weight: 700;
            box-shadow: 0 10px 24px rgba(18, 38, 63, 0.08);
        }
        .res-hub-btn i {
            font-size: 1.05rem;
        }
        .res-hub-btn--soft {
            border: 1px solid #dfe9f8;
            background: #fff;
            color: #32507f;
        }
        .res-hub-btn--primary {
            border: 1px solid transparent;
            background: linear-gradient(135deg, #0f6ee8 0%, #1d4ed8 100%);
            color: #fff;
        }
        .res-hub-btn--danger {
            border: 1px solid #f6c9c9;
            background: #fff;
            color: #dd3d3d;
        }
        .res-hub-alert {
            border: 1px solid #dbe7fb;
            border-radius: 20px;
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            box-shadow: 0 14px 30px rgba(18, 38, 63, 0.06);
        }
        .res-hub-alert--success {
            border-color: #bfe5ce;
        }
        .res-hub-confirm-modal {
            border: 0;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 28px 70px rgba(18, 38, 63, 0.18);
        }
        .res-hub-detail-modal {
            border: 0;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 28px 70px rgba(18, 38, 63, 0.16);
        }
        .res-hub-detail-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem 1.25rem;
        }
        .res-hub-detail-item {
            padding: .9rem 1rem;
            border: 1px solid #e7edf7;
            border-radius: 16px;
            background: linear-gradient(180deg, #fbfdff 0%, #f7faff 100%);
        }
        .res-hub-detail-item__label {
            display: block;
            margin-bottom: .35rem;
            color: #7184a0;
            font-size: .76rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .06em;
        }
        .res-hub-detail-item__value {
            color: #1c375f;
            font-size: .95rem;
            font-weight: 600;
            line-height: 1.45;
            word-break: break-word;
        }
        .res-hub-detail-banner {
            margin-bottom: 1rem;
            padding: 1rem 1.05rem;
            border: 1px solid #d7e7ff;
            border-radius: 18px;
            background: linear-gradient(135deg, rgba(26, 122, 240, 0.08), rgba(33, 87, 215, 0.03));
        }
        .res-hub-detail-banner strong {
            color: #16407a;
        }
        .res-hub-confirm-modal__head {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .res-hub-confirm-modal__icon {
            width: 54px;
            height: 54px;
            border-radius: 18px;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, #1a7af0, #2157d7);
            color: #fff;
            font-size: 1.5rem;
            box-shadow: 0 16px 28px rgba(33, 87, 215, 0.28);
        }
        .res-hub-confirm-modal__card {
            border: 1px solid #e6edf8;
            border-radius: 18px;
            background: linear-gradient(180deg, #fbfdff 0%, #f5f9ff 100%);
            padding: 1rem 1.1rem;
        }
        .res-hub-confirm-modal__row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: .65rem 0;
            border-bottom: 1px solid #e9eef7;
            color: #506887;
            font-size: .92rem;
        }
        .res-hub-confirm-modal__row:last-child {
            border-bottom: 0;
            padding-bottom: 0;
        }
        .res-hub-confirm-modal__row:first-child {
            padding-top: 0;
        }
        .res-hub-confirm-modal__row strong {
            color: #18345f;
            text-align: right;
        }
        .res-hub-filter-card,
        .res-hub-table-card {
            border-radius: 24px;
            background: rgba(255, 255, 255, 0.96);
            box-shadow: 0 20px 50px rgba(18, 38, 63, 0.08);
        }
        .res-hub-filter-card .card-body {
            padding: 1.25rem;
        }
        .res-hub-filter-grid {
            display: grid;
            grid-template-columns: minmax(180px, 1.3fr) minmax(150px, .85fr) minmax(170px, .85fr) minmax(230px, 1.1fr) minmax(130px, .55fr) minmax(130px, .55fr);
            gap: 1rem;
            align-items: end;
        }
        .res-hub-field .form-label {
            margin-bottom: .45rem;
            color: #4a6288;
            font-size: .79rem;
            font-weight: 700;
        }
        .res-hub-field .form-select,
        .res-hub-field .form-control {
            min-height: 48px;
            border: 1px solid #dbe5f2;
            border-radius: 14px;
            padding: .7rem .95rem;
            box-shadow: none;
        }
        .res-hub-field .form-select:focus,
        .res-hub-field .form-control:focus {
            border-color: #4f8df5;
            box-shadow: 0 0 0 4px rgba(79, 141, 245, 0.12);
        }
        .res-hub-kpi {
            overflow: hidden;
            border-radius: 22px;
        }
        .res-hub-kpi .card-body {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1.25rem;
        }
        .res-hub-kpi__icon {
            width: 58px;
            height: 58px;
            border-radius: 18px;
            display: grid;
            place-items: center;
            color: #fff;
            font-size: 1.55rem;
            box-shadow: 0 14px 26px rgba(18, 38, 63, 0.18);
        }
        .res-hub-kpi--blue .res-hub-kpi__icon { background: linear-gradient(135deg, #1a7af0, #2157d7); }
        .res-hub-kpi--amber .res-hub-kpi__icon { background: linear-gradient(135deg, #ffb44d, #f38b16); }
        .res-hub-kpi--green .res-hub-kpi__icon { background: linear-gradient(135deg, #31c777, #149c52); }
        .res-hub-kpi--red .res-hub-kpi__icon { background: linear-gradient(135deg, #ff6b6b, #ec3636); }
        .res-hub-kpi__label {
            color: #6f82a0;
            font-size: .75rem;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }
        .res-hub-kpi__value {
            color: #15315f;
            font-size: 2rem;
            line-height: 1.05;
            font-weight: 800;
        }
        .res-hub-kpi__meta {
            color: #7c8da9;
            font-size: .88rem;
        }
        .res-hub-table-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.25rem 1.35rem 1rem;
            border-bottom: 1px solid #e8eef7;
        }
        .res-hub-table-title {
            margin: 0;
            color: #16335f;
            font-size: 1.1rem;
            font-weight: 800;
        }
        .res-hub-table-subtitle {
            color: #7b8ca7;
            font-size: .9rem;
        }
        .reservations-table thead th {
            padding: 1rem .9rem;
            background: #f8fbff;
            border-bottom: 1px solid #e3ebf7;
            color: #516887;
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .reservations-table tbody td {
            padding: 1rem .9rem;
            border-color: #edf2f8;
            vertical-align: middle;
            color: #21385e;
        }
        .reservations-table tbody tr:hover {
            background: #fbfdff;
        }
        .reservations-table .badge {
            border-radius: 999px;
            padding: .5rem .72rem;
            font-weight: 700;
            font-size: .72rem;
        }
        .res-status-badge--pending {
            background: #fff0d6;
            color: #b86a07;
        }
        .res-status-badge--pairing {
            background: #ffe4bf;
            color: #a94e00;
        }
        .res-status-badge--paired {
            background: #dff6ff;
            color: #0c6d8f;
        }
        .res-status-badge--confirmed {
            background: #dcfce7;
            color: #15803d;
        }
        .res-status-badge--cancelled {
            background: #fee2e2;
            color: #b91c1c;
        }
        .res-status-badge--neutral {
            background: #eef2f7;
            color: #55657f;
        }
        .res-hub-row-highlight {
            --res-hub-highlight-rgb: 25, 135, 84;
            background-color: rgba(var(--res-hub-highlight-rgb), 0.08);
            box-shadow: inset 3px 0 0 0 rgb(var(--res-hub-highlight-rgb));
        }
        #res-hub-pagination {
            background: linear-gradient(180deg, rgba(248, 251, 255, 0.85), rgba(255, 255, 255, 0.95));
        }
        #res-hub-pagination .pagination {
            margin-bottom: 0;
            justify-content: flex-end;
        }
        #res-hub-pagination .page-link {
            border-radius: 10px;
            margin: 0 .15rem;
            border-color: #dbe5f3;
            color: #33517d;
        }
        #res-hub-pagination .active .page-link {
            background: #1a73e8;
            border-color: #1a73e8;
        }
        @media (max-width: 1399px) {
            .res-hub-filter-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }
        @media (max-width: 991px) {
            .res-hub-hero {
                flex-direction: column;
            }
            .res-hub-hero__actions {
                width: 100%;
                justify-content: flex-start;
            }
            .res-hub-filter-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        @media (max-width: 767px) {
            .res-hub-detail-grid {
                grid-template-columns: 1fr;
            }
            .res-hub-filter-grid {
                grid-template-columns: 1fr;
            }
            .res-hub-btn {
                width: 100%;
            }
            .res-hub-hero__actions {
                flex-direction: column;
            }
            .reservations-table thead th,
            .reservations-table tbody td {
                padding: .85rem .7rem;
            }
        }
    </style>

    <!-- Modal Jumelage -->
    <div class="modal fade" id="pairingModal" tabindex="-1" aria-labelledby="pairingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="pairingModalLabel">Jumeler la rÃ©servation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body" id="pairing-modal-body">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2 text-muted">Recherche des rÃ©servations compatibles...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    var root = document.getElementById('res-hub-root');
    if (!root) return;
    var base = root.getAttribute('data-res-base') || '';
    var canEdit = root.getAttribute('data-can-edit') === '1';
    var canUpdate = root.getAttribute('data-can-update') === '1';
    var voyageName = @json($voyage->resolved_name ?? $voyage->name ?? '');
    var allReservationsUrl = @json($allReservationsUrl);
    var hubDebugUrl = root.getAttribute('data-hub-debug-url') || '';
    var hubRefreshUrl = root.getAttribute('data-hub-refresh-url') || '';
    var isClientChannel = (new URLSearchParams(window.location.search)).get('channel') === 'client';

    function applyVoyageHeader() {
        var titleBox = document.querySelector('.page-title-box');
        if (!titleBox) return;

        var leftCol = titleBox.firstElementChild;
        var actions = titleBox.lastElementChild;
        if (!leftCol || !actions) return;

        var title = leftCol.querySelector('.page-title');
        if (title) {
            title.textContent = 'RESERVATIONS';
        }

        var badge = leftCol.querySelector('[data-res-voyage-badge]');
        if (!badge && title && title.parentElement) {
            badge = document.createElement('span');
            badge.className = 'badge rounded-pill bg-primary-subtle text-primary border border-primary-subtle fw-semibold';
            badge.setAttribute('data-res-voyage-badge', '1');
            title.parentElement.appendChild(badge);
        }

        var subtitle = leftCol.querySelector('[data-res-voyage-subtitle]');
        if (!subtitle) {
            subtitle = document.createElement('p');
            subtitle.className = 'text-muted small mb-1';
            subtitle.setAttribute('data-res-voyage-subtitle', '1');
            leftCol.insertBefore(subtitle, leftCol.querySelector('p'));
        }

        var backBtn = actions.querySelector('[data-res-all-link]');
        if (!backBtn && allReservationsUrl) {
            backBtn = document.createElement('a');
            backBtn.className = 'btn btn-outline-secondary btn-sm';
            backBtn.href = allReservationsUrl;
            backBtn.setAttribute('data-res-all-link', '1');
            backBtn.innerHTML = '<i class="bx bx-arrow-back me-1"></i> Toutes les reservations';
            actions.insertBefore(backBtn, actions.firstChild);
        }

        if (voyageName) {
            if (badge) {
                badge.textContent = voyageName;
                badge.classList.remove('d-none');
            }
            if (subtitle) {
                subtitle.textContent = 'Reservations du voyage : ' + voyageName;
            }
            if (backBtn) {
                backBtn.classList.remove('d-none');
            }
        } else {
            if (badge) {
                badge.classList.add('d-none');
            }
            if (subtitle) {
                subtitle.textContent = 'Toutes les reservations.';
            }
            if (backBtn) {
                backBtn.classList.add('d-none');
            }
        }
    }

    applyVoyageHeader();

    (function refineVoyageHeader() {
        var titleBox = document.querySelector('.page-title-box');
        if (!titleBox) return;

        var leftCol = titleBox.firstElementChild;
        var actions = titleBox.lastElementChild;
        if (!leftCol || !actions) return;

        var title = leftCol.querySelector('.page-title');
        if (title) title.textContent = 'RESERVATIONS';

        var badge = leftCol.querySelector('[data-res-voyage-badge]');
        if (badge) badge.remove();

        var subtitle = leftCol.querySelector('[data-res-voyage-subtitle]');
        if (!subtitle) {
            subtitle = document.createElement('p');
            subtitle.className = 'text-muted mb-1';
            subtitle.setAttribute('data-res-voyage-subtitle', '1');
            leftCol.insertBefore(subtitle, leftCol.querySelector('p'));
        } else {
            subtitle.className = 'text-muted mb-1';
        }

        var context = leftCol.querySelector('[data-res-voyage-context]');
        if (!context) {
            context = document.createElement('p');
            context.className = 'text-muted small mb-0';
            context.setAttribute('data-res-voyage-context', '1');
            leftCol.appendChild(context);
        }

        Array.prototype.slice.call(leftCol.querySelectorAll('p')).forEach(function (el) {
            if (el !== subtitle && el !== context) el.remove();
        });

        var backBtn = actions.querySelector('[data-res-all-link]');
        if (voyageName) {
            subtitle.innerHTML = 'Gestion des reservations pour : <span class="fw-semibold text-dark">' + esc(voyageName) + '</span>';
            context.textContent = 'Filtres, statistiques et liste sont synchronises sur le voyage selectionne.';
            if (backBtn) backBtn.classList.remove('d-none');
        } else {
            subtitle.textContent = 'Toutes les reservations.';
            context.textContent = 'Filtres, statistiques et liste sont synchronises.';
            if (backBtn) backBtn.classList.add('d-none');
        }
    })();

    function applyHubRefreshPayload(payload) {
        if (!payload || !payload.hub_stats) return;
        var hs = payload.hub_stats;
        var pairs = [
            ['res-hub-stat-total', hs.total],
            ['res-hub-stat-en-cours', hs.en_cours],
            ['res-hub-stat-validee', hs.validee],
            ['res-hub-stat-annulee', hs.annulee]
        ];
        pairs.forEach(function (p) {
            var el = document.getElementById(p[0]);
            if (el) el.textContent = String(p[1] != null ? p[1] : '0');
        });
        var tbody = document.getElementById('res-hub-tbody');
        if (tbody && payload.tbody_html) tbody.innerHTML = payload.tbody_html;
        var pag = document.getElementById('res-hub-pagination');
        if (pag && typeof payload.pagination_html === 'string') pag.innerHTML = payload.pagination_html;
    }

    function scrollToHighlightedReservationRow() {
        var row = document.getElementById('res-hub-highlight-row');
        if (!row) return;
        setTimeout(function () {
            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 100);
    }

    function fetchAndApplyHubRefresh() {
        if (!hubRefreshUrl) return Promise.resolve();
        var full = hubRefreshUrl + (window.location.search || '');
        return fetch(full, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        }).then(function (r) { return r.ok ? r.json() : Promise.reject(); }).then(applyHubRefreshPayload).catch(function () {});
    }
    function panelUrl(id) { return base + '/' + encodeURIComponent(id) + '/panel'; }
    function editUrl(id) {
        var u = base + '/' + encodeURIComponent(id) + '/edit?embed=1';
        var loc = new URL(window.location.href);
        var q = loc.searchParams;
        ['voyage_id', 'travel_date_id', 'status', 'search'].forEach(function (k) {
            var v = q.get(k);
            if (v) u += '&rq_' + k + '=' + encodeURIComponent(v);
        });
        return u;
    }
    function fetchPanel(id, cb) {
        fetch(panelUrl(id), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        }).then(function (r) { return r.ok ? r.json() : Promise.reject(); }).then(cb).catch(function () {
            cb(null);
        });
    }
    function esc(s) {
        if (s == null) return '';
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }
    function typeLabel(t) {
        if (t === 'child') return 'Enfant';
        if (t === 'infant') return 'Bebe';
        return 'Adulte';
    }

    function validateUrl(id) {
        return base + '/' + encodeURIComponent(id) + '/validate';
    }

    var validateModalEl = document.getElementById('resHubValidateModal');
    var validateModal = validateModalEl ? new bootstrap.Modal(validateModalEl) : null;
    var validateForm = null;
    var detailFooter = document.getElementById('resHubDetailFooter');
    var detailEditBtn = document.getElementById('resHubDetailEditBtn');
    var detailValidateBtn = document.getElementById('resHubDetailValidateBtn');
    var currentDetailReservation = null;

    function setValidateModalField(id, value) {
        var el = document.getElementById(id);
        if (el) el.textContent = value || '-';
    }

    var hubTable = document.querySelector('table.reservations-table');
    if (hubTable) {
        hubTable.addEventListener('click', function (e) {
            var validateBtn = e.target.closest('.btn-res-hub-validate');
            if (validateBtn) {
                validateForm = validateBtn.closest('form');
                setValidateModalField('resHubValidateId', '#' + (validateBtn.getAttribute('data-res-id') || '-'));
                setValidateModalField('resHubValidateClient', validateBtn.getAttribute('data-res-client') || '-');
                setValidateModalField('resHubValidateOffer', validateBtn.getAttribute('data-res-offer') || '-');
                setValidateModalField('resHubValidateDate', validateBtn.getAttribute('data-res-date') || '-');
                setValidateModalField('resHubValidateStatus', validateBtn.getAttribute('data-res-status') || '-');
                if (validateModal) validateModal.show();
                return;
            }
            var detailBtn = e.target.closest('.btn-res-hub-detail');
            if (detailBtn) {
                var id = detailBtn.getAttribute('data-res-id');
                var modal = new bootstrap.Modal(document.getElementById('resHubDetailModal'));
                document.getElementById('resHubDetailTitle').textContent = 'Reservation #' + id;
                document.getElementById('resHubDetailBody').innerHTML = '<p class="text-muted mb-0">Chargement...</p>';
                modal.show();
                fetchPanel(id, function (d) {
                    var el = document.getElementById('resHubDetailBody');
                    if (!d) {
                        el.innerHTML = '<p class="text-danger mb-0">Impossible de charger les details.</p>';
                        if (detailFooter) detailFooter.style.display = 'none';
                        return;
                    }
                    currentDetailReservation = d;
                    var isConfirmable = d.status !== 'confirmed' && d.status !== 'cancelled';
                    var h = '';
                    if (isClientChannel) {
                        h += '<div class="res-hub-detail-banner"><strong>Source : Client web</strong><div class="small text-muted mt-1">Reservation creee directement depuis le site Ajinsafro. Vous pouvez verifier, modifier puis confirmer si necessaire.</div></div>';
                    }
                    h += '<div class="res-hub-detail-grid">';
                    h += '<div class="res-hub-detail-item"><span class="res-hub-detail-item__label">Statut</span><div class="res-hub-detail-item__value">' + esc(d.status || '-') + '</div></div>';
                    h += '<div class="res-hub-detail-item"><span class="res-hub-detail-item__label">Client</span><div class="res-hub-detail-item__value">' + esc(d.client_label || '-') + (d.client_code ? '<div class="small text-muted mt-1">' + esc(d.client_code) + '</div>' : '') + '</div></div>';
                    h += '<div class="res-hub-detail-item"><span class="res-hub-detail-item__label">Offre liee</span><div class="res-hub-detail-item__value">' + esc(d.tour_name || '-') + '</div></div>';
                    h += '<div class="res-hub-detail-item"><span class="res-hub-detail-item__label">Depart</span><div class="res-hub-detail-item__value">' + esc(d.travel_date_label || '-') + (d.travel_date_id ? '<div class="small text-muted mt-1">TravelDate #' + esc(String(d.travel_date_id)) + '</div>' : '') + '</div></div>';
                    h += '<div class="res-hub-detail-item"><span class="res-hub-detail-item__label">Type prestation</span><div class="res-hub-detail-item__value">' + esc(d.prestation_type || '-') + '</div></div>';
                    if (d.payment_type) {
                        h += '<div class="res-hub-detail-item"><span class="res-hub-detail-item__label">Paiement</span><div class="res-hub-detail-item__value">' + esc(d.payment_type) + '</div></div>';
                    }
                    if (d.base_price != null || d.paid_amount != null) {
                        h += '<div class="res-hub-detail-item"><span class="res-hub-detail-item__label">Montants</span><div class="res-hub-detail-item__value">Total : ' + esc(String(d.base_price ?? '-')) + '<br>Paye : ' + esc(String(d.paid_amount ?? '-')) + '</div></div>';
                    }
                    if (d.created_at) {
                        h += '<div class="res-hub-detail-item"><span class="res-hub-detail-item__label">Creee le</span><div class="res-hub-detail-item__value">' + esc(d.created_at) + '</div></div>';
                    }
                    if (!isClientChannel) {
                        if (d.creator_name || d.creator_email) {
                            h += '<div class="res-hub-detail-item"><span class="res-hub-detail-item__label">Creee par</span><div class="res-hub-detail-item__value">' + esc(d.creator_name || '-') + (d.creator_email ? '<div class="small text-muted mt-1">' + esc(d.creator_email) + '</div>' : '') + '</div></div>';
                        }
                        if (d.agency || d.branch) {
                            h += '<div class="res-hub-detail-item"><span class="res-hub-detail-item__label">Point de vente</span><div class="res-hub-detail-item__value">' + esc(d.agency || d.branch) + '</div></div>';
                        }
                    }
                    h += '</div>';
                    el.innerHTML = h;
                    if (detailFooter) detailFooter.style.display = 'flex';
                    if (detailEditBtn) detailEditBtn.style.display = (canEdit && !(d.visibility && d.visibility.limited_presentation)) ? 'inline-flex' : 'none';
                    if (detailValidateBtn) detailValidateBtn.style.display = (canUpdate && isConfirmable) ? 'inline-flex' : 'none';
                });
                return;
            }
            var paxBtn = e.target.closest('.btn-res-hub-pax');
            if (paxBtn) {
                var idP = paxBtn.getAttribute('data-res-id');
                var paxModal = new bootstrap.Modal(document.getElementById('resHubPaxModal'));
                document.getElementById('resHubPaxTitle').textContent = 'Participants - #' + idP;
                document.getElementById('resHubPaxBody').innerHTML = '<p class="text-muted p-3 mb-0">Chargement...</p>';
                paxModal.show();
                fetchPanel(idP, function (d) {
                    var el = document.getElementById('resHubPaxBody');
                    if (!d || !d.passengers || !d.passengers.length) {
                        el.innerHTML = '<p class="text-muted p-3 mb-0">Aucun participant enregistre.</p>';
                        return;
                    }
                    var showDocuments = !!(d.visibility && d.visibility.view_sensitive);
                    var h = '<table class="table table-sm mb-0"><thead><tr><th>Nom</th><th>Type</th>' + (showDocuments ? '<th>Document</th>' : '') + '</tr></thead><tbody>';
                    d.passengers.forEach(function (p) {
                        var name = esc((p.first_name || '') + ' ' + (p.last_name || '')).trim() || '-';
                        h += '<tr><td>' + name + '</td><td>' + esc(typeLabel(p.type)) + '</td>' + (showDocuments ? '<td class="small">' + esc(p.document_number || '-') + '</td>' : '') + '</tr>';
                    });
                    h += '</tbody></table>';
                    el.innerHTML = h;
                });
                return;
            }
            var editBtn = e.target.closest('.btn-res-hub-edit');
            if (editBtn) {
                var idE = editBtn.getAttribute('data-res-id');
                if (frame) frame.src = editUrl(idE);
                var oc = bootstrap.Offcanvas.getOrCreateInstance(offEl);
                oc.show();
            }
        });
    }

    var validateConfirmBtn = document.getElementById('resHubValidateConfirmBtn');
    if (validateConfirmBtn) {
        validateConfirmBtn.addEventListener('click', function () {
            if (!validateForm) return;
            validateConfirmBtn.disabled = true;
            validateConfirmBtn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i><span>Validation...</span>';
            validateForm.submit();
        });
    }

    if (validateModalEl) {
        validateModalEl.addEventListener('hidden.bs.modal', function () {
            if (validateForm && !validateForm.classList.contains('res-hub-validate-form') && validateForm.parentNode) {
                validateForm.parentNode.removeChild(validateForm);
            }
            validateForm = null;
            if (validateConfirmBtn) {
                validateConfirmBtn.disabled = false;
                validateConfirmBtn.innerHTML = '<i class="bx bx-check"></i><span>Valider la reservation</span>';
            }
        });
    }

    if (detailEditBtn) {
        detailEditBtn.addEventListener('click', function () {
            if (!currentDetailReservation || !canEdit) return;
            var id = currentDetailReservation.id;
            if (frame) frame.src = editUrl(id);
            var detailModalEl = document.getElementById('resHubDetailModal');
            var detailModal = detailModalEl ? bootstrap.Modal.getInstance(detailModalEl) : null;
            if (detailModal) detailModal.hide();
            var oc = bootstrap.Offcanvas.getOrCreateInstance(offEl);
            oc.show();
        });
    }

    if (detailValidateBtn) {
        detailValidateBtn.addEventListener('click', function () {
            if (!currentDetailReservation || !canUpdate) return;
            var form = document.createElement('form');
            form.method = 'post';
            form.action = validateUrl(currentDetailReservation.id);
            form.style.display = 'none';
            var csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = root.getAttribute('data-csrf') || '';
            form.appendChild(csrf);
            document.body.appendChild(form);
            validateForm = form;
            setValidateModalField('resHubValidateId', '#' + String(currentDetailReservation.id || '-'));
            setValidateModalField('resHubValidateClient', currentDetailReservation.client_label || '-');
            setValidateModalField('resHubValidateOffer', currentDetailReservation.tour_name || '-');
            setValidateModalField('resHubValidateDate', currentDetailReservation.travel_date_label || '-');
            setValidateModalField('resHubValidateStatus', currentDetailReservation.status || '-');
            var detailModalEl = document.getElementById('resHubDetailModal');
            var detailModal = detailModalEl ? bootstrap.Modal.getInstance(detailModalEl) : null;
            if (detailModal) detailModal.hide();
            if (validateModal) validateModal.show();
        });
    }

    var offEl = document.getElementById('resHubEditOffcanvas');
    var frame = document.getElementById('resHubEditFrame');
    if (offEl) {
        offEl.addEventListener('hidden.bs.offcanvas', function () {
            if (frame) frame.src = 'about:blank';
        });
    }

    var debugModal = document.getElementById('resHubDebugModal');
    if (debugModal && hubDebugUrl) {
        debugModal.addEventListener('show.bs.modal', function () {
            var metaEl = document.getElementById('resHubDebugMeta');
            var jsonEl = document.getElementById('resHubDebugJson');
            var tbody = document.getElementById('resHubDebugTbody');
            if (metaEl) metaEl.innerHTML = '<li>Chargement...</li>';
            if (jsonEl) jsonEl.textContent = '';
            if (tbody) tbody.innerHTML = '<tr><td colspan="15" class="text-muted">Chargement...</td></tr>';
            var u = hubDebugUrl + (window.location.search || '');
            fetch(u, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
                .then(function (r) { return r.ok ? r.json() : Promise.reject(new Error('HTTP ' + r.status)); })
                .then(function (data) {
                    if (jsonEl) jsonEl.textContent = JSON.stringify(data, null, 2);
                    if (metaEl) {
                        var hs = data.hub_stats || {};
                        var f = data.filters || {};
                        metaEl.innerHTML =
                            '<li><strong>Total filtre (stats)</strong> : ' + esc(String(hs.total ?? '-')) + ' - en cours ' + esc(String(hs.en_cours ?? '-')) + ' - validees ' + esc(String(hs.validee ?? '-')) + ' - annulees ' + esc(String(hs.annulee ?? '-')) + '</li>' +
                            '<li><strong>Reservations renvoyees</strong> : ' + esc(String(data.count ?? 0)) + ' (plafond ' + esc(String(data.limit ?? 500)) + ')</li>' +
                            '<li><strong>Filtres GET</strong> : voyage_id=' + esc(String(f.voyage_id || '-')) + ', travel_date_id=' + esc(String(f.travel_date_id || '-')) + ', status=' + esc(String(f.status || '-')) + ', search=' + esc(String(f.search || '-')) + '</li>';
                    }
                    if (tbody) {
                        var list = data.reservations || [];
                        if (!list.length) {
                            tbody.innerHTML = '<tr><td colspan="15" class="text-muted">Aucune reservation.</td></tr>';
                            return;
                        }
                        var h = '';
                        list.forEach(function (row) {
                            var pax = (row.passengers_preview || []).join(', ') || '-';
                            h += '<tr>' +
                                '<td class="text-nowrap">' + esc(String(row.id)) + '</td>' +
                                '<td class="small">' + esc(row.client_snapshot || '-') + '</td>' +
                                '<td>' + esc(String(row.tour_id ?? '-')) + '</td>' +
                                '<td class="small">' + esc(row.tour_name || '-') + '</td>' +
                                '<td class="small">' + esc(row.creator_name || '-') + '</td>' +
                                '<td class="small">' + esc(row.agency_name || '-') + '</td>' +
                                '<td class="small">' + esc(String(row.tour_wp_post_id ?? '-')) + ' / ' + esc(String(row.wp_tour_post_id ?? '-')) + '</td>' +
                                '<td class="small">' + esc(row.catalog_source_code || '-') + '</td>' +
                                '<td>' + esc(String(row.voyage_flight_id ?? '-')) + '</td>' +
                                '<td>' + esc(String(row.prestation_type || '-')) + '</td>' +
                                '<td>' + esc(String(row.travel_date_id ?? '-')) + '</td>' +
                                '<td class="small">' + esc(row.travel_date || '-') + '</td>' +
                                '<td>' + esc(String(row.status || '-')) + '</td>' +
                                '<td class="small text-nowrap">' + esc((row.created_at || '').replace('T', ' ').slice(0, 19)) + '</td>' +
                                '<td class="small">' + esc(String(row.passengers_count ?? 0)) + ' - ' + esc(pax) + '</td>' +
                                '</tr>';
                        });
                        tbody.innerHTML = h;
                    }
                })
                .catch(function () {
                    if (metaEl) metaEl.innerHTML = '<li class="text-danger">Erreur de chargement.</li>';
                    if (tbody) tbody.innerHTML = '<tr><td colspan="15" class="text-danger">Echec du chargement.</td></tr>';
                });
        });
    }

    var createdBanner = document.getElementById('res-hub-created-banner');
    var needsHubRefresh = (hubRefreshUrl && createdBanner) || (hubRefreshUrl && /(?:^|[?&])highlight=/.test(window.location.search));
    if (needsHubRefresh) {
        setTimeout(function () {
            fetchAndApplyHubRefresh().then(function () { scrollToHighlightedReservationRow(); });
        }, 150);
    } else if (document.getElementById('res-hub-highlight-row')) {
        scrollToHighlightedReservationRow();
    }

    // Pairing modal handler
    var pairingModalEl = document.getElementById('pairingModal');
    var pairingModal = pairingModalEl ? bootstrap.Modal.getOrCreateInstance(pairingModalEl) : null;
    var pairingModalBody = document.getElementById('pairing-modal-body');
    var pairingModalLabel = document.getElementById('pairingModalLabel');

    if (pairingModalEl && pairingModalBody) {
        document.body.addEventListener('click', function (e) {
            var btn = e.target.closest('.btn-res-hub-pair');
            if (!btn) return;
            e.preventDefault();
            var resId = btn.getAttribute('data-res-id');
            var resCode = btn.getAttribute('data-res-code');
            if (!resId) return;
            if (pairingModalLabel) {
                pairingModalLabel.textContent = 'Jumeler la rÃ©servation ' + (resCode || '#'+resId);
            }
            pairingModalBody.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">Recherche des rÃ©servations compatibles...</p></div>';
            pairingModal.show();

            var url = base + '/reservations/' + encodeURIComponent(resId) + '/pairing-candidates';
            fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function (r) {
                if (!r.ok) throw new Error('Erreur ' + r.status);
                return r.json();
            })
            .then(function (data) {
                if (data.html) {
                    pairingModalBody.innerHTML = data.html;
                } else {
                    pairingModalBody.innerHTML = '<div class="alert alert-warning border-0">Aucune donnÃ©e reÃ§ue.</div>';
                }
            })
            .catch(function (err) {
                pairingModalBody.innerHTML = '<div class="alert alert-danger border-0">Impossible de charger les candidats de jumelage. ' + (err.message || '') + '</div>';
            });
        });
    }
})();
</script>
@endpush
