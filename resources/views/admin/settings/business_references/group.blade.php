@extends('layouts.admin-v2')
@section('title')
    {{ $label }}
@endsection
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <div>
                    <h4 class="page-title mb-1 font-size-18">{{ $label }}</h4>
                    <p class="text-muted small mb-0"><code>{{ $groupKey }}</code></p>
                </div>
                <a href="{{ route('admin.settings.referentiels-metier') }}" class="btn btn-light btn-sm">← Toutes les familles</a>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-header"><strong>Ajouter une valeur</strong></div>
        <div class="card-body">
            <form action="{{ route('admin.settings.referentiels-metier.store', ['groupKey' => $groupKey]) }}" method="POST" class="row g-3">
                @csrf
                @if($groupKey === 'payment_methods')
                    <div class="col-12">
                        <label class="form-label">Meta (JSON) — doit contenir <code>meta_key</code></label>
                        <textarea name="meta_json" class="form-control font-monospace" rows="2" required placeholder='{"meta_key":"is_meta_payment_gateway_st_xxx"}'>{{ old('meta_json', '{"meta_key":""}') }}</textarea>
                    </div>
                @else
                    <div class="col-md-4">
                        <label class="form-label">Valeur (slug)</label>
                        <input type="text" name="value" class="form-control" value="{{ old('value') }}" required>
                    </div>
                @endif
                <div class="col-md-4">
                    <label class="form-label">Libellé</label>
                    <input type="text" name="label" class="form-control" value="{{ old('label') }}" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tri</label>
                    <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', 0) }}" min="0">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <div class="form-check mb-2">
                        <input type="hidden" name="is_active" value="0">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="na" checked>
                        <label class="form-check-label" for="na">Actif</label>
                    </div>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary btn-sm">Ajouter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:22%">Valeur</th>
                            <th>Modification</th>
                            <th style="width:110px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                            <tr>
                                <td><code>{{ $item->value }}</code></td>
                                <td>
                                    <form action="{{ route('admin.settings.referentiels-metier.update', ['groupKey' => $groupKey, 'item' => $item]) }}" method="POST" class="row g-2 align-items-end">
                                        @csrf
                                        @method('PUT')
                                        <div class="col-md-4">
                                            <label class="form-label small mb-0">Libellé</label>
                                            <input type="text" name="label" class="form-control form-control-sm" value="{{ $item->label }}">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small mb-0">Tri</label>
                                            <input type="number" name="sort_order" class="form-control form-control-sm" value="{{ $item->sort_order }}">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label small mb-0">Actif</label>
                                            <div>
                                                <input type="hidden" name="is_active" value="0">
                                                <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked($item->is_active)>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small mb-0">Meta (JSON)</label>
                                            <textarea name="meta_json" class="form-control form-control-sm font-monospace" rows="2">{{ $item->meta ? json_encode($item->meta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : '' }}</textarea>
                                        </div>
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-sm btn-primary">Enregistrer</button>
                                        </div>
                                    </form>
                                </td>
                                <td class="text-end">
                                    <form action="{{ route('admin.settings.referentiels-metier.destroy', ['groupKey' => $groupKey, 'item' => $item]) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-4">Aucune valeur.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
