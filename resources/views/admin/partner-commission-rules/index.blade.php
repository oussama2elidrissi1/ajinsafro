@extends('layouts.admin-v6')
@section('title', 'RÃ¨gles de commission')

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">RÃ¨gles de commission</h4>
                <a href="{{ route('admin.partner-commission-rules.create') }}" class="btn btn-primary"><i class="bx bx-plus me-1"></i> Nouvelle rÃ¨gle</a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="GET" class="mb-3">
                <div class="row g-2 align-items-end">
                    <div class="col-auto">
                        <label class="form-label small">Partenaire</label>
                        <select name="partner_id" class="form-select form-select-sm" style="width: auto;">
                            <option value="">Tous</option>
                            @foreach($partners as $p)
                                <option value="{{ $p->id }}" {{ request('partner_id') == $p->id ? 'selected' : '' }}>{{ $p->raison_sociale }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto">
                        <label class="form-label small">Type</label>
                        <select name="type" class="form-select form-select-sm" style="width: auto;">
                            <option value="">Tous</option>
                            <option value="percent" {{ request('type') === 'percent' ? 'selected' : '' }}>%</option>
                            <option value="fixed" {{ request('type') === 'fixed' ? 'selected' : '' }}>Montant fixe</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary btn-sm">Filtrer</button>
                    </div>
                </div>
            </form>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Partenaire</th>
                            <th>Voyage</th>
                            <th>Type</th>
                            <th>Valeur</th>
                            <th>PÃ©riode</th>
                            <th>Actif</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rules as $rule)
                            <tr>
                                <td>{{ $rule->partner ? $rule->partner->display_name : 'â€” Global' }}</td>
                                <td>{{ $rule->voyage ? $rule->voyage->name : 'â€” Tous' }}</td>
                                <td>{{ $rule->type === 'percent' ? '%' : 'Fixe' }}</td>
                                <td>{{ $rule->type === 'percent' ? $rule->value . ' %' : number_format($rule->value, 0, ',', ' ') . ' DH' }}</td>
                                <td>
                                    {{ $rule->valid_from?->format('d/m/Y') ?? 'â€”' }} â†’ {{ $rule->valid_until?->format('d/m/Y') ?? 'â€”' }}
                                </td>
                                <td><span class="badge bg-{{ $rule->is_active ? 'success' : 'secondary' }}">{{ $rule->is_active ? 'Oui' : 'Non' }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('admin.partner-commission-rules.edit', $rule) }}" class="btn btn-sm btn-outline-primary">Modifier</a>
                                    <form action="{{ route('admin.partner-commission-rules.destroy', $rule) }}" method="post" class="d-inline" onsubmit="return confirm('Supprimer cette rÃ¨gle ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Aucune rÃ¨gle. CrÃ©ez une rÃ¨gle globale (sans partenaire ni voyage) ou par partenaire/voyage.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if(method_exists($rules, 'links'))
                <div class="d-flex justify-content-center mt-3">{{ $rules->links() }}</div>
            @endif
        </div>
    </div>
@endsection

