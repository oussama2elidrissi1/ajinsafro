@extends('layouts.admin-v6')
@section('title')
    Compagnies aériennes
@endsection
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Compagnies aériennes</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.circuits.index') }}">Circuits</a></li>
                        <li class="breadcrumb-item active">Compagnies aériennes</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <h4 class="card-title mb-0">Compagnies aériennes (vols des voyages)</h4>
                        <a href="{{ route('admin.circuits.airlines.create') }}" class="btn btn-primary waves-effect waves-light">
                            <i class="bx bx-plus me-1"></i> Nouvelle compagnie
                        </a>
                    </div>
                    @if($airlines->isEmpty())
                        <p class="text-muted mb-0">Aucune compagnie. <a href="{{ route('admin.circuits.airlines.create') }}">Créer une compagnie</a> pour l?Tutiliser dans les vols des voyages.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover table-centered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="60">ID</th>
                                        <th>Nom</th>
                                        <th>Code IATA</th>
                                        <th>Statut</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($airlines as $airline)
                                    <tr>
                                        <td>{{ $airline->id }}</td>
                                        <td>{{ $airline->name }}</td>
                                        <td>{{ $airline->code_iata ?? '?' }}</td>
                                        <td>
                                            @if($airline->is_active)
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-secondary">Inactive</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('admin.circuits.airlines.edit', $airline) }}" class="btn btn-sm btn-soft-primary">Modifier</a>
                                            <form action="{{ route('admin.circuits.airlines.destroy', $airline) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette compagnie ?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-soft-danger">Supprimer</button>
                                            </form>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-center mt-3">
                            {{ $airlines->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection


