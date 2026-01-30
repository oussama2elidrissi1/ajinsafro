@extends('layouts.master-ajinsafro')
@section('title')
    Voyages
@endsection
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Voyages</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.circuits.index') }}">Circuits</a></li>
                        <li class="breadcrumb-item active">Voyages</li>
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
                        <h4 class="card-title mb-0">Liste des voyages</h4>
                        <a href="{{ route('admin.circuits.voyages.create') }}" class="btn btn-primary waves-effect waves-light">
                            <i class="bx bx-plus me-1"></i> Créer un voyage
                        </a>
                    </div>
                    @if($voyages->isEmpty())
                        <p class="text-muted mb-0">Aucun voyage. <a href="{{ route('admin.circuits.voyages.create') }}">Créer un voyage</a> pour commencer.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover table-centered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nom</th>
                                        <th>Jours programme</th>
                                        <th>Modifié le</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($voyages as $voyage)
                                        <tr>
                                            <td>
                                                <a href="{{ route('admin.circuits.voyages.edit', $voyage) }}" class="text-body fw-medium">{{ $voyage->name }}</a>
                                            </td>
                                            <td>{{ $voyage->program_days_count }} jour(s)</td>
                                            <td>{{ $voyage->updated_at->format('d/m/Y H:i') }}</td>
                                            <td class="text-end">
                                                <a href="{{ route('admin.circuits.voyages.show', $voyage) }}" class="btn btn-sm btn-soft-info waves-effect waves-light me-1">Voir fiche</a>
                                                <a href="{{ route('admin.circuits.voyages.edit', $voyage) }}" class="btn btn-sm btn-soft-primary waves-effect waves-light me-1">Modifier</a>
                                                <form action="{{ route('admin.circuits.voyages.destroy', $voyage) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer ce voyage et tout son programme ?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-soft-danger waves-effect waves-light">Supprimer</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
