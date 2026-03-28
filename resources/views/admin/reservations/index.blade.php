@extends('layouts.master-ajinsafro')

@section('title', 'Réservations')

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">
                    Réservations
                    @if($status === 'EN_COURS') <span class="badge bg-warning text-dark ms-1">En attente</span>
                    @elseif($status === 'VALIDEE') <span class="badge bg-success ms-1">Confirmées</span>
                    @elseif($status === 'ANNULEE') <span class="badge bg-danger ms-1">Annulées</span>
                    @endif
                </h4>
                <a href="{{ route('admin.reservations.create') }}" class="btn btn-primary">
                    <i class="bx bx-plus me-1"></i> Nouvelle réservation
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 reservations-table">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Client</th>
                                    <th>Voyage</th>
                                    <th>Passagers</th>
                                    <th>Paiement</th>
                                    <th>Statut</th>
                                    <th>Créée le</th>
                                    <th class="text-end pe-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse($reservations as $reservation)
                                <tr>
                                    <td class="ps-3">
                                        @if($reservation->client)
                                            <strong>{{ $reservation->client->full_name }}</strong>
                                            <span class="text-muted small d-block">{{ $reservation->client->client_code }}</span>
                                        @else
                                            {{ trim(($reservation->client_first_name ?? '').' '.($reservation->client_last_name ?? '')) ?: '—' }}
                                        @endif
                                    </td>
                                    <td>{{ $reservation->tour?->name ?? '—' }}</td>
                                    <td>
                                        @php
                                            $names = $reservation->passengers->map(fn($p) => trim(($p->first_name ?? '').' '.($p->last_name ?? '')))->filter()->values();
                                        @endphp
                                        @if($names->isEmpty())
                                            <span class="text-muted">Seul</span>
                                        @else
                                            <span class="text-break">{{ $names->join(', ') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($reservation->payment_type)
                                            <div class="d-flex flex-wrap align-items-center gap-2">
                                                <span class="badge bg-light text-dark">{{ $reservation->payment_type }}</span>
                                                @if($reservation->payment_receipt_path)
                                                    @php
                                                        $path = str_replace('\\', '/', trim($reservation->payment_receipt_path, '/'));
                                                        $receiptUrl = route('admin.reservations.receipt', ['path' => $path]);
                                                        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                                                        $isImage = in_array($ext, ['jpg','jpeg','png','gif','webp'], true);
                                                    @endphp
                                                    @if($isImage)
                                                        <a href="{{ $receiptUrl }}" target="_blank" rel="noopener" class="receipt-thumb d-inline-block rounded border overflow-hidden bg-light" style="width:56px;height:42px;">
                                                            <img src="{{ $receiptUrl }}" alt="Reçu" style="width:100%;height:100%;object-fit:cover;" loading="lazy">
                                                        </a>
                                                    @else
                                                        <a href="{{ $receiptUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary py-1" title="Voir le reçu"><i class="bx bx-file"></i></a>
                                                    @endif
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $statusClass = match($reservation->status) {
                                                \App\Models\Reservation::STATUS_EN_COURS => 'badge bg-warning text-dark',
                                                \App\Models\Reservation::STATUS_VALIDEE => 'badge bg-success',
                                                \App\Models\Reservation::STATUS_ANNULEE => 'badge bg-danger',
                                                default => 'badge bg-secondary',
                                            };
                                        @endphp
                                        <span class="{{ $statusClass }}">{{ $reservation->status }}</span>
                                    </td>
                                    <td>{{ optional($reservation->created_at)->format('d/m/Y H:i') }}</td>
                                    <td class="text-end pe-3">
                                        @if($reservation->status !== \App\Models\Reservation::STATUS_VALIDEE)
                                            <form action="{{ route('admin.reservations.validate', $reservation) }}" method="post" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success" title="Valider"><i class="bx bx-check"></i></button>
                                            </form>
                                        @endif
                                        <a href="{{ route('admin.reservations.edit', $reservation) }}" class="btn btn-sm btn-outline-primary" title="Modifier"><i class="bx bx-pencil"></i></a>
                                        <form action="{{ route('admin.reservations.destroy', $reservation) }}" method="post" class="d-inline" onsubmit="return confirm('Supprimer cette réservation ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer"><i class="bx bx-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-5">Aucune réservation trouvée.</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if(method_exists($reservations, 'links'))
                        <div class="px-3 py-2 border-top">{{ $reservations->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <style>
        .reservations-table th { font-weight: 600; white-space: nowrap; }
        .reservations-table td { vertical-align: middle; }
        .receipt-thumb:hover { opacity: 0.9; }
    </style>
@endsection

@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
