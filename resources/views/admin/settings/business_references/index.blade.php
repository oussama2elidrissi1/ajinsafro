@extends('layouts.admin-v6')
@section('title')
    RÃ©fÃ©rence mÃ©tier
@endsection
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h4 class="page-title mb-0 font-size-18">RÃ©fÃ©rence mÃ©tier</h4>
                <div class="d-flex gap-2">
                    <form action="{{ route('admin.settings.referentiels-metier.import-legacy') }}" method="POST" class="d-inline"
                          onsubmit="return confirm('Fusionner les valeurs depuis lâ€™ancien JSON (settings) vers la base ?');">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary btn-sm">Importer ancien JSON</button>
                    </form>
                </div>
            </div>
            <p class="text-muted">Listes dynamiques utilisÃ©es dans les formulaires voyage (types de jour, rÃ©ductions, paiements, etc.).</p>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-3">
        @foreach($groups as $g)
            <div class="col-md-6 col-xl-4">
                <div class="card border shadow-sm h-100">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title font-size-15">{{ $g['label'] }}</h5>
                        <p class="text-muted small mb-2">ClÃ© : <code>{{ $g['key'] }}</code></p>
                        <p class="mb-3"><span class="badge bg-light text-dark border">{{ $g['count'] }} valeur(s)</span></p>
                        <a href="{{ route('admin.settings.referentiels-metier.group', ['groupKey' => $g['key']]) }}" class="btn btn-primary btn-sm mt-auto">GÃ©rer</a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection

