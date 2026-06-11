@extends('layouts.admin-v6')

@section('title', 'Types de charges')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <div>
                <h4 class="mb-1">Types de charges</h4>
                <p class="text-muted mb-0">Referentiel utilise dans les sorties de la fiche de voyage interne.</p>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="POST" action="{{ route('admin.settings.charge-types.store') }}" class="row g-3 align-items-end">
                    @csrf
                    <div class="col-md-3"><label class="form-label">Nom</label><input type="text" name="name" class="form-control" required></div>
                    <div class="col-md-4"><label class="form-label">Description</label><input type="text" name="description" class="form-control"></div>
                    <div class="col-md-2"><label class="form-label">Ordre</label><input type="number" name="sort_order" min="0" class="form-control" value="0"></div>
                    <div class="col-md-2">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="type-active" checked>
                            <label class="form-check-label" for="type-active">Actif</label>
                        </div>
                    </div>
                    <div class="col-md-1 d-grid"><button class="btn btn-primary">Ajouter</button></div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th class="px-3">Nom</th><th>Slug</th><th>Description</th><th>Actif</th><th>Ordre</th><th class="text-end px-3">Actions</th></tr>
                    </thead>
                    <tbody>
                        @forelse($chargeTypes as $type)
                            <tr>
                                <form method="POST" action="{{ route('admin.settings.charge-types.update', $type) }}">
                                    @csrf @method('PUT')
                                    <td class="px-3"><input type="text" name="name" class="form-control form-control-sm" value="{{ $type->name }}" required></td>
                                    <td><code>{{ $type->slug }}</code></td>
                                    <td><input type="text" name="description" class="form-control form-control-sm" value="{{ $type->description }}"></td>
                                    <td>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked($type->is_active)>
                                        </div>
                                    </td>
                                    <td style="width:120px"><input type="number" name="sort_order" class="form-control form-control-sm" min="0" value="{{ $type->sort_order }}"></td>
                                    <td class="text-end px-3">
                                        <div class="d-inline-flex gap-1">
                                            <button class="btn btn-sm btn-outline-primary">Enregistrer</button>
                                </form>
                                            <form method="POST" action="{{ route('admin.settings.charge-types.destroy', $type) }}" onsubmit="return confirm('Supprimer ce type de charge ?')">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger">Supprimer</button>
                                            </form>
                                        </div>
                                    </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center py-5 text-muted">Aucun type de charge.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-3">{{ $chargeTypes->links() }}</div>
    </div>
@endsection
