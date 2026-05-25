@php $modalAjax = $modalAjax ?? false; @endphp
<div class="card border shadow-sm">
    <div class="card-header bg-white py-3 border-bottom">
        <h5 class="mb-0"><i class="bx bx-cog me-1 text-primary"></i> Paramètres du départ</h5>
    </div>
    <div class="card-body">
        <form method="post" action="{{ route('admin.circuits.voyages.departures.settings.update', [$voyage, $departure]) }}">
            @csrf
            @method('PUT')
            @include('admin.circuits.voyages.departures.partials._modal_ajax_hidden', ['modalAjax' => $modalAjax])
            <input type="hidden" name="redirect_to" value="{{ request()->fullUrl() }}">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Date début <span class="text-danger">*</span></label>
                    <input type="date" name="start_date" class="form-control" required
                           value="{{ old('start_date', optional($departure->start_date)->format('Y-m-d')) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date fin</label>
                    <input type="date" name="end_date" class="form-control"
                           value="{{ old('end_date', optional($departure->end_date)->format('Y-m-d')) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Statut <span class="text-danger">*</span></label>
                    <select name="status" class="form-select" required>
                        @foreach($statuses as $st)
                            <option value="{{ $st }}" {{ old('status', $departure->status) === $st ? 'selected' : '' }}>{{ \App\Models\Departure::make(['status' => $st])->status_label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                      <label class="form-label">Places restantes (calculées)</label>
                      <input type="number" class="form-control" min="0"
                          value="{{ (int) ($departure->available_capacity ?? 0) }}" readonly>
                      <small class="text-muted">Valeur calculée automatiquement depuis capacité totale et réservations actives.</small>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Capacité totale</label>
                    <input type="number" name="total_capacity" class="form-control" min="0"
                           value="{{ old('total_capacity', $departure->total_capacity) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Prix de base (MAD)</label>
                    <input type="number" name="base_price" class="form-control" min="0" step="0.01"
                           value="{{ old('base_price', $departure->base_price) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Prix promo (MAD)</label>
                    <input type="number" name="sale_price" class="form-control" min="0" step="0.01"
                           value="{{ old('sale_price', $departure->sale_price) }}">
                </div>
                <div class="col-12">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="3" placeholder="Notes internes??">{{ old('notes', $departure->notes) }}</textarea>
                </div>
            </div>
            <div class="mt-3 text-end">
                <button type="submit" class="btn btn-primary"><i class="bx bx-save"></i> Enregistrer</button>
            </div>
        </form>
    </div>
</div>

