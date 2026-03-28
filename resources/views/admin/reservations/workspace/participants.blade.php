@extends('layouts.master-ajinsafro')

@section('title', 'Participants — '.($prestationDisplayTitle ?? $voyage->name))

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
<style>
    .ws-pax-page { max-width: 1400px; margin: 0 auto; }
    .ws-pax-badge { font-size: 0.65rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; padding: 0.2rem 0.5rem; border-radius: 0.35rem; }
    .ws-pax-badge--ok { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
    .ws-pax-badge--wait { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
    .ws-pax-badge--off { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
    .ws-pax-type { font-size: 0.75rem; color: #64748b; }
</style>
@endpush

@php
    $statusClass = fn (string $s) => match ($s) {
        \App\Models\Reservation::STATUS_VALIDEE => 'ws-pax-badge ws-pax-badge--ok',
        \App\Models\Reservation::STATUS_ANNULEE => 'ws-pax-badge ws-pax-badge--off',
        default => 'ws-pax-badge ws-pax-badge--wait',
    };
    $statusLabel = fn (string $s) => match ($s) {
        \App\Models\Reservation::STATUS_VALIDEE => 'Confirmée',
        \App\Models\Reservation::STATUS_ANNULEE => 'Annulée',
        default => 'En attente',
    };
    $paxTypeLabel = fn (?string $t) => match ($t) {
        'child' => 'Enfant',
        'infant' => 'Bébé',
        default => 'Adulte',
    };
@endphp

@section('content')
<div class="ws-pax-page px-2 px-md-0">
    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <a href="{{ route('admin.reservations.workspace') }}"
               class="d-inline-flex align-items-center gap-2 text-decoration-none fw-bold small mb-2"
               style="color: #0083c4;">
                <i class="fas fa-arrow-left"></i> Retour au catalogue
            </a>
            <h1 class="h3 fw-bold mb-1 d-flex align-items-center gap-2" style="color: #0e3a5a;">
                <i class="fas fa-users" style="color: #0083c4;"></i> Participants
            </h1>
            <p class="text-muted small mb-0 fw-medium">
                <span class="text-dark fw-semibold">{{ $prestationDisplayTitle }}</span>
                @if($voyage->wp_post_id)
                    <span class="text-muted">· WP #{{ $voyage->wp_post_id }}</span>
                @endif
                <span class="text-muted">· Laravel #{{ $voyage->id }}</span>
                @if($travelDateLabel)
                    <span class="d-block d-md-inline mt-1 mt-md-0"><span class="badge bg-light text-dark border">Départ {{ $travelDateLabel }}</span></span>
                @elseif($travelDateId)
                    <span class="text-muted">· travel_date_id {{ $travelDateId }}</span>
                @endif
            </p>
            @if($wpTourTitle && $wpTourTitle !== $voyage->name)
                <p class="small text-muted mb-0 mt-1"><i class="fas fa-database me-1"></i>Fiche Laravel : {{ $voyage->name }}</p>
            @endif
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.reservations.workspace.prestation.pdf', array_filter(['voyage_id' => $voyage->id, 'travel_date_id' => $travelDateId])) }}"
               class="btn btn-danger d-inline-flex align-items-center gap-2 rounded-3 fw-bold shadow-sm">
                <i class="fas fa-file-pdf"></i> Télécharger PDF
            </a>
        </div>
    </div>

    @php
        $totalPax = $reservations->sum(fn ($r) => $r->passengers->count());
        $resaCount = $reservations->count();
    @endphp
    <div class="row g-3 mb-4">
        <div class="col-sm-4">
            <div class="card border-0 shadow-sm rounded-3 h-100" style="border-left: 4px solid #0083c4 !important;">
                <div class="card-body py-3">
                    <div class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 0.08em;">Réservations</div>
                    <div class="h4 mb-0 fw-bold" style="color: #0e3a5a;">{{ $resaCount }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card border-0 shadow-sm rounded-3 h-100" style="border-left: 4px solid #f37a1f !important;">
                <div class="card-body py-3">
                    <div class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 0.08em;">Participants listés</div>
                    <div class="h4 mb-0 fw-bold" style="color: #0e3a5a;">{{ $totalPax }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-light">
                <div class="card-body py-3 small text-muted">
                    Les lignes proviennent des réservations liées à ce voyage (filtre date appliqué si vous venez d’une ligne catalogue avec date).
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="min-width: 920px;">
                <thead class="table-light">
                <tr class="small text-muted text-uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 0.06em;">
                    <th class="py-3 px-3">Réservation</th>
                    <th class="py-3 px-3">Statut</th>
                    <th class="py-3 px-3">Client</th>
                    <th class="py-3 px-3">Participant</th>
                    <th class="py-3 px-3">Type</th>
                    <th class="py-3 px-3">Document</th>
                    <th class="py-3 px-3 text-end">Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse($reservations as $reservation)
                    @if($reservation->passengers->isEmpty())
                        <tr>
                            <td class="px-3 py-3 fw-bold" style="color: #0e3a5a;">#{{ $reservation->id }}</td>
                            <td class="px-3 py-3"><span class="{{ $statusClass($reservation->status) }}">{{ $statusLabel($reservation->status) }}</span></td>
                            <td class="px-3 py-3 text-muted">
                                {{ trim(($reservation->client_first_name ?? '').' '.($reservation->client_last_name ?? '')) ?: '—' }}
                                <div class="small text-warning mt-1"><i class="fas fa-info-circle me-1"></i>Aucun passager enregistré</div>
                            </td>
                            <td class="px-3 py-3">—</td>
                            <td class="px-3 py-3">—</td>
                            <td class="px-3 py-3">—</td>
                            <td class="px-3 py-3 text-end">
                                <a href="{{ route('admin.reservations.edit', $reservation) }}" class="btn btn-sm btn-outline-primary rounded-pill me-1">Modifier</a>
                                <a href="{{ route('admin.reservations.workspace.reservation.pdf', $reservation) }}" class="btn btn-sm btn-outline-danger rounded-pill">PDF</a>
                            </td>
                        </tr>
                    @else
                        @foreach($reservation->passengers as $p)
                            <tr>
                                <td class="px-3 py-3 fw-bold" style="color: #0e3a5a;">#{{ $reservation->id }}</td>
                                <td class="px-3 py-3"><span class="{{ $statusClass($reservation->status) }}">{{ $statusLabel($reservation->status) }}</span></td>
                                <td class="px-3 py-3 text-muted">{{ trim(($reservation->client_first_name ?? '').' '.($reservation->client_last_name ?? '')) ?: '—' }}</td>
                                <td class="px-3 py-3 fw-medium">{{ trim(($p->first_name ?? '').' '.($p->last_name ?? '')) }}</td>
                                <td class="px-3 py-3"><span class="ws-pax-type">{{ $paxTypeLabel($p->type) }}</span></td>
                                <td class="px-3 py-3 small font-monospace">{{ $p->document_number ?? '—' }}</td>
                                <td class="px-3 py-3 text-end text-nowrap">
                                    @if($loop->first)
                                        <a href="{{ route('admin.reservations.edit', $reservation) }}" class="btn btn-sm btn-outline-primary rounded-pill me-1">Modifier</a>
                                    @endif
                                    <a href="{{ route('admin.reservations.workspace.reservation.pdf', $reservation) }}" class="btn btn-sm btn-outline-danger rounded-pill">PDF</a>
                                </td>
                            </tr>
                        @endforeach
                    @endif
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="fas fa-inbox fa-2x mb-3 d-block opacity-50"></i>
                            Aucun participant pour cette prestation avec les filtres actuels.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
