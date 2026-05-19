@extends('layouts.admin-v6')

@section('title', 'Detail commission')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <div>
                <h4 class="mb-1">Detail commission agent</h4>
                <p class="text-muted mb-0">Reservation #{{ $entry->reservation_id }} - {{ $entry->agent?->name ?: 'Agent non renseigne' }}</p>
            </div>
            <a href="{{ route('admin.finance.commissions') }}" class="btn btn-outline-secondary">Retour</a>
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="mb-4">Synthese</h5>
                        <dl class="row mb-0">
                            <dt class="col-sm-4 text-muted">Agent</dt><dd class="col-sm-8 fw-semibold">{{ $entry->agent?->name ?: 'Agent non renseigne' }}</dd>
                            <dt class="col-sm-4 text-muted">Point de vente</dt><dd class="col-sm-8 fw-semibold">{{ $entry->branch?->name ?: 'Non renseigne' }}</dd>
                            <dt class="col-sm-4 text-muted">Voyage</dt><dd class="col-sm-8 fw-semibold">{{ $entry->voyage?->name ?: 'Voyage non renseigne' }}</dd>
                            <dt class="col-sm-4 text-muted">Date depart</dt><dd class="col-sm-8 fw-semibold">{{ $entry->departureDateLabel() ?: 'â€”' }}</dd>
                            <dt class="col-sm-4 text-muted">Client</dt><dd class="col-sm-8 fw-semibold">{{ $entry->client_name ?: 'Client non renseigne' }}</dd>
                            <dt class="col-sm-4 text-muted">Montant reservation</dt><dd class="col-sm-8 fw-semibold">{{ number_format((float) $entry->reservation_total, 2, ',', ' ') }} DH</dd>
                            <dt class="col-sm-4 text-muted">Base commission</dt><dd class="col-sm-8 fw-semibold">{{ number_format((float) $entry->commission_base_amount, 2, ',', ' ') }} DH</dd>
                            <dt class="col-sm-4 text-muted">Commission adulte</dt><dd class="col-sm-8 fw-semibold">{{ number_format((float) $entry->commission_adult, 2, ',', ' ') }} DH</dd>
                            <dt class="col-sm-4 text-muted">Commission enfant</dt><dd class="col-sm-8 fw-semibold">{{ number_format((float) $entry->commission_child, 2, ',', ' ') }} DH</dd>
                            <dt class="col-sm-4 text-muted">Commission bebe</dt><dd class="col-sm-8 fw-semibold">{{ number_format((float) $entry->commission_baby, 2, ',', ' ') }} DH</dd>
                            <dt class="col-sm-4 text-muted">Commission totale</dt><dd class="col-sm-8 fw-semibold">{{ number_format((float) $entry->commission_total, 2, ',', ' ') }} DH</dd>
                            <dt class="col-sm-4 text-muted">Statut commission</dt><dd class="col-sm-8 fw-semibold">{{ $entry->statusLabelFr() }}</dd>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="mb-3">Actions finance</h5>
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <form method="POST" action="{{ route('admin.finance.commissions.confirm', $entry) }}">@csrf<button class="btn btn-outline-primary">Marquer confirme</button></form>
                            <form method="POST" action="{{ route('admin.finance.commissions.payable', $entry) }}">@csrf<button class="btn btn-outline-info">Marquer payable</button></form>
                            <form method="POST" action="{{ route('admin.finance.commissions.paid', $entry) }}">@csrf<button class="btn btn-outline-success">Marquer paye</button></form>
                            <form method="POST" action="{{ route('admin.finance.commissions.cancel', $entry) }}">@csrf<button class="btn btn-outline-danger">Annuler</button></form>
                            <form method="POST" action="{{ route('admin.finance.commissions.reverse', $entry) }}">@csrf<button class="btn btn-outline-dark">Reverser</button></form>
                        </div>

                        <form method="POST" action="{{ route('admin.finance.commissions.adjust', $entry) }}" class="row g-3">
                            @csrf
                            <div class="col-12"><label class="form-label">Commission totale</label><input type="number" step="0.01" min="0" name="commission_total" class="form-control" value="{{ old('commission_total', $entry->commission_total) }}"></div>
                            <div class="col-md-4"><label class="form-label">Adulte</label><input type="number" step="0.01" min="0" name="commission_adult" class="form-control" value="{{ old('commission_adult', $entry->commission_adult) }}"></div>
                            <div class="col-md-4"><label class="form-label">Enfant</label><input type="number" step="0.01" min="0" name="commission_child" class="form-control" value="{{ old('commission_child', $entry->commission_child) }}"></div>
                            <div class="col-md-4"><label class="form-label">Bebe</label><input type="number" step="0.01" min="0" name="commission_baby" class="form-control" value="{{ old('commission_baby', $entry->commission_baby) }}"></div>
                            <div class="col-12"><label class="form-label">Note</label><textarea name="notes" rows="3" class="form-control">{{ old('notes', $entry->notes) }}</textarea></div>
                            <div class="col-12 d-grid"><button class="btn btn-primary">Ajouter ajustement manuel</button></div>
                        </form>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="mb-3">Historique</h5>
                        @forelse($entry->logs as $log)
                            <div class="border-start border-3 ps-3 mb-3">
                                <div class="fw-semibold">{{ ucfirst(str_replace('_', ' ', $log->action)) }}</div>
                                <div class="small text-muted">{{ optional($log->created_at)->format('d/m/Y H:i') }} @if($log->creator) - {{ $log->creator->name }} @endif</div>
                                @if($log->description)
                                    <div class="mt-1">{{ $log->description }}</div>
                                @endif
                                <div class="small text-muted mt-1">
                                    @if($log->old_status || $log->new_status)
                                        {{ $log->old_status ?: 'â€”' }} â†’ {{ $log->new_status ?: 'â€”' }}
                                    @endif
                                    @if($log->old_amount !== null || $log->new_amount !== null)
                                        | {{ number_format((float) ($log->old_amount ?? 0), 2, ',', ' ') }} DH â†’ {{ number_format((float) ($log->new_amount ?? 0), 2, ',', ' ') }} DH
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="text-muted">Aucun historique disponible.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

