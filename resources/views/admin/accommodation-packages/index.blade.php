@extends('layouts.master-ajinsafro')
@section('title')
    Packs hébergement
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Packs hébergement</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item active">Packs hébergement</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-12 text-end">
            <a href="{{ route('admin.accommodation-packages.create') }}" class="btn btn-success">
                <i class="bx bx-plus"></i> Nouveau pack
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Titre</th>
                                    <th>Pays</th>
                                    <th>Ville</th>
                                    <th>Durée</th>
                                    <th>Pension</th>
                                    <th>Type</th>
                                    <th>Prix</th>
                                    <th>En vedette</th>
                                    <th>Actif</th>
                                    <th>Ordre</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($packages as $package)
                                    <tr>
                                        <td>{{ $package->id }}</td>
                                        <td>{{ $package->title }}</td>
                                        <td>{{ $package->country }}</td>
                                        <td>{{ $package->city }}</td>
                                        <td>{{ $package->duration_days }}j / {{ $package->nights }}n</td>
                                        <td>{{ $package->pension_type ?? '-' }}</td>
                                        <td>{{ $package->accommodation_type ?? '-' }}</td>
                                        <td>{{ number_format($package->price_from, 2) }} {{ $package->currency }}</td>
                                        <td>
                                            @if($package->is_featured)
                                                <span class="badge bg-warning">Oui</span>
                                            @else
                                                <span class="badge bg-secondary">Non</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($package->is_active)
                                                <span class="badge bg-success">Oui</span>
                                            @else
                                                <span class="badge bg-secondary">Non</span>
                                            @endif
                                        </td>
                                        <td>{{ $package->sort_order }}</td>
                                        <td class="d-flex gap-1">
                                            <a href="{{ route('admin.accommodation-packages.edit', $package) }}" class="btn btn-sm btn-primary">Modifier</a>
                                            <form method="POST" action="{{ route('admin.accommodation-packages.destroy', $package) }}" onsubmit="return confirm('Supprimer ce pack ?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger">Supprimer</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="12" class="text-center text-muted">Aucun pack hébergement.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($packages->hasPages())
                        <div class="d-flex justify-content-end mt-2">
                            {{ $packages->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
