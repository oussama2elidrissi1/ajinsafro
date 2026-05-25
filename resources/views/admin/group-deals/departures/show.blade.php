@extends('layouts.admin-v6')
@section('title') Départ Group Deal ? {{ $departure->start_date?->format('d/m/Y') }} @endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-flex align-items-center justify-content-between">
            <h4 class="page-title mb-0 font-size-18">
                Départ ? {{ $departure->voyage?->name }}
                <small class="text-muted fw-normal ms-2">{{ $departure->start_date?->format('d/m/Y') }}</small>
            </h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.group-deals.trips.index') }}">Group Deals</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.group-deals.departures.index') }}">Départs</a></li>
                    <li class="breadcrumb-item active">Détail</li>
                </ol>
            </div>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show">
        {{ $errors->first() }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- ?"??"? KPI row ?"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"? --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">Participants confirmés</p>
                <h3 class="mb-0 text-primary">{{ $stats['confirmed_count'] }}</h3>
                <small class="text-muted">seuil : {{ $stats['threshold'] }}</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">Prix actif</p>
                <h3 class="mb-0 text-success">
                    {{ $stats['current_price'] ? number_format($stats['current_price'], 0, ',', ' ').' ?,?' : '?' }}
                </h3>
                @if($stats['active_tier'])
                    <small class="text-muted">{{ $stats['active_tier']->label ?? 'Palier actif' }}</small>
                @else
                    <small class="text-muted">Prix de base</small>
                @endif
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">Prochain palier</p>
                @if($stats['next_tier'])
                    <h3 class="mb-0">{{ number_format($stats['next_tier']->price_per_person, 0, ',', ' ') }} ?,?</h3>
                    <small class="text-muted">à {{ $stats['next_tier']->min_participants }} participants</small>
                @else
                    <h3 class="mb-0 text-muted">?</h3>
                    <small class="text-muted">Dernier palier atteint</small>
                @endif
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">Statut garanti</p>
                @if($departure->is_guaranteed)
                    <span class="badge bg-success fs-6 px-3 py-2">Garanti</span>
                    @if($departure->guaranteed_at)
                        <br><small class="text-muted">{{ $departure->guaranteed_at->format('d/m/Y') }}</small>
                    @endif
                @else
                    <span class="badge bg-warning text-dark fs-6 px-3 py-2">Non garanti</span>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ?"??"? Jauge progression ?"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"? --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="small fw-semibold">Progression vers le seuil garanti</span>
            <span class="small text-muted">{{ $stats['confirmed_count'] }} / {{ $stats['threshold'] }} participants</span>
        </div>
        <div class="progress" style="height: 18px; border-radius: 9px;">
            <div class="progress-bar {{ $departure->is_guaranteed ? 'bg-success' : 'bg-warning' }}"
                 style="width: {{ $stats['progression_pct'] }}%; border-radius: 9px; transition: width .4s ease;">
                @if($stats['progression_pct'] >= 15)
                    {{ $stats['progression_pct'] }}%
                @endif
            </div>
        </div>
        @if(! $departure->is_guaranteed && $stats['threshold'] > $stats['confirmed_count'])
            <p class="text-muted small mt-2 mb-0">
                Il manque <strong>{{ $stats['threshold'] - $stats['confirmed_count'] }}</strong> participant(s) pour que ce départ soit garanti.
            </p>
        @endif
    </div>
</div>

<div class="row g-3">

    {{-- ?"??"? Tableau des participants ?"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"? --}}
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between">
                <h6 class="mb-0">Participants ({{ $stats['confirmed_count'] }} confirmés)</h6>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddParticipant">
                    <i class="bx bx-plus"></i> Ajouter
                </button>
            </div>
            <div class="card-body p-0">
                @if($departure->groupDealParticipants->isEmpty())
                    <div class="p-4 text-center text-muted small">Aucun participant pour ce départ.</div>
                @else
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Client</th>
                                <th>Inscrit le</th>
                                <th>Statut</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($departure->groupDealParticipants as $p)
                            <tr>
                                <td>
                                    @if($p->client)
                                        <strong>{{ $p->client->first_name }} {{ $p->client->last_name }}</strong>
                                        <br><small class="text-muted">{{ $p->client->email }}</small>
                                    @else
                                        <span class="text-muted">?</span>
                                    @endif
                                </td>
                                <td class="small text-muted">{{ $p->joined_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    <span class="badge bg-{{ match($p->status) {
                                        'confirmed' => 'success',
                                        'pending'   => 'warning text-dark',
                                        'cancelled' => 'danger',
                                        default     => 'secondary'
                                    } }}">
                                        {{ match($p->status) {
                                            'confirmed' => 'Confirmé',
                                            'pending'   => 'En attente',
                                            'cancelled' => 'Annulé',
                                            default     => $p->status
                                        } }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                            Action
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            @foreach(['confirmed' => 'Confirmer', 'pending' => 'Mettre en attente', 'cancelled' => 'Annuler'] as $s => $label)
                                                @if($p->status !== $s)
                                                    <li>
                                                        <form method="POST"
                                                              action="{{ route('admin.group-deals.departures.participants.update', [$departure, $p]) }}">
                                                            @csrf @method('PATCH')
                                                            <input type="hidden" name="status" value="{{ $s }}">
                                                            <button type="submit" class="dropdown-item">{{ $label }}</button>
                                                        </form>
                                                    </li>
                                                @endif
                                            @endforeach
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>

    {{-- ?"??"? Infos départ + actions ?"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"? --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white border-bottom">
                <h6 class="mb-0">Informations</h6>
            </div>
            <div class="card-body small">
                <table class="table table-borderless table-sm mb-0">
                    <tr><td class="text-muted">Voyage</td><td><strong>{{ $departure->voyage?->name }}</strong></td></tr>
                    <tr><td class="text-muted">Date début</td><td>{{ $departure->start_date?->format('d/m/Y') ?? '?' }}</td></tr>
                    <tr><td class="text-muted">Date fin</td><td>{{ $departure->end_date?->format('d/m/Y') ?? '?' }}</td></tr>
                    <tr><td class="text-muted">Capacité totale</td><td>{{ $departure->total_capacity ?? '?' }}</td></tr>
                    <tr><td class="text-muted">Seuil garanti</td><td>{{ $departure->guaranteed_threshold }}</td></tr>
                    <tr><td class="text-muted">Statut départ</td><td>{{ $departure->status_label }}</td></tr>
                </table>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h6 class="mb-0">Actions</h6>
            </div>
            <div class="card-body d-grid gap-2">
                <form method="POST" action="{{ route('admin.group-deals.departures.recalculate', $departure) }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary btn-sm w-100">
                        <i class="bx bx-refresh me-1"></i> Recalculer prix & statut
                    </button>
                </form>
                <a href="{{ route('admin.circuits.voyages.show', $departure->voyage_id) }}"
                   class="btn btn-outline-secondary btn-sm">
                    <i class="bx bx-link-external me-1"></i> Voir le voyage
                </a>
            </div>
        </div>
    </div>

</div>

{{-- ?"??"? Modal Ajouter participant ?"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"??"? --}}
<div class="modal fade" id="modalAddParticipant" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.group-deals.departures.participants.store', $departure) }}">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title">Ajouter un participant</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small">ID Client <span class="text-danger">*</span></label>
                        <input type="number" name="client_id" class="form-control form-control-sm"
                               required placeholder="ID du client">
                        <div class="form-text">Entrez l'identifiant numérique du client.</div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">ID Réservation (optionnel)</label>
                        <input type="number" name="reservation_id" class="form-control form-control-sm"
                               placeholder="ID de la réservation liée">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary btn-sm">Ajouter</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush


