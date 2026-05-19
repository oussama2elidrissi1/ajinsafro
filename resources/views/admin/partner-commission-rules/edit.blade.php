@extends('layouts.admin-v6')
@section('title', 'Modifier la rÃ¨gle de commission')

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Modifier la rÃ¨gle de commission</h4>
                <a href="{{ route('admin.partner-commission-rules.index') }}" class="btn btn-outline-secondary btn-sm">Retour</a>
            </div>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form action="{{ route('admin.partner-commission-rules.update', $rule) }}" method="post">
        @csrf
        @method('PUT')
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label">Partenaire (vide = rÃ¨gle globale)</label>
                        <select name="partner_id" class="form-select">
                            <option value="">â€” Tous les partenaires</option>
                            @foreach($partners as $p)
                                <option value="{{ $p->id }}" {{ old('partner_id', $rule->partner_id) == $p->id ? 'selected' : '' }}>{{ $p->raison_sociale }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Voyage (vide = tous les voyages)</label>
                        <select name="voyage_id" class="form-select">
                            <option value="">â€” Tous les voyages</option>
                            @foreach($voyages as $v)
                                <option value="{{ $v->id }}" {{ old('voyage_id', $rule->voyage_id) == $v->id ? 'selected' : '' }}>{{ $v->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Type <span class="text-danger">*</span></label>
                        <select name="type" class="form-select" required>
                            <option value="percent" {{ old('type', $rule->type) === 'percent' ? 'selected' : '' }}>Pourcentage</option>
                            <option value="fixed" {{ old('type', $rule->type) === 'fixed' ? 'selected' : '' }}>Montant fixe (DH)</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Valeur <span class="text-danger">*</span></label>
                        <input type="number" name="value" class="form-control" step="0.01" min="0" value="{{ old('value', $rule->value) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Volume min. (optionnel)</label>
                        <input type="number" name="min_volume" class="form-control" min="0" value="{{ old('min_volume', $rule->min_volume) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Valide du</label>
                        <input type="date" name="valid_from" class="form-control" value="{{ old('valid_from', $rule->valid_from?->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Valide au</label>
                        <input type="date" name="valid_until" class="form-control" value="{{ old('valid_until', $rule->valid_until?->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', $rule->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">RÃ¨gle active</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Enregistrer</button>
        <a href="{{ route('admin.partner-commission-rules.index') }}" class="btn btn-secondary">Annuler</a>
    </form>
@endsection

