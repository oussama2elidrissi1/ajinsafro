@extends('layouts.master-ajinsafro')
@section('title')
    Offres activités
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Offres activités</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item active">Offres activités</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-12 text-end">
            <a href="{{ route('admin.activity-offers.create') }}" class="btn btn-success">
                <i class="bx bx-plus"></i> Nouvelle offre
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
                                    <th>Catégorie</th>
                                    <th>Durée</th>
                                    <th>Prix</th>
                                    <th>Dispo</th>
                                    <th>Vedette</th>
                                    <th>Actif</th>
                                    <th>Ordre</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($offers as $offer)
                                    <tr>
                                        <td>{{ $offer->id }}</td>
                                        <td>{{ $offer->title }}</td>
                                        <td>{{ $offer->country }}</td>
                                        <td>{{ $offer->city }}</td>
                                        <td>{{ $offer->category }}</td>
                                        <td>{{ $offer->duration_label ?? '-' }}</td>
                                        <td>{{ number_format($offer->price_from, 2) }} {{ $offer->currency }}</td>
                                        <td>{{ $offer->availability_label }}</td>
                                        <td>
                                            @if($offer->is_featured)
                                                <span class="badge bg-warning">Oui</span>
                                            @else
                                                <span class="badge bg-secondary">Non</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($offer->is_active)
                                                <span class="badge bg-success">Oui</span>
                                            @else
                                                <span class="badge bg-secondary">Non</span>
                                            @endif
                                        </td>
                                        <td>{{ $offer->sort_order }}</td>
                                        <td class="d-flex gap-1">
                                            <a href="{{ route('admin.activity-offers.edit', $offer) }}" class="btn btn-sm btn-primary">Modifier</a>
                                            <form method="POST" action="{{ route('admin.activity-offers.destroy', $offer) }}" onsubmit="return confirm('Supprimer cette offre ?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger">Supprimer</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="12" class="text-center text-muted">Aucune offre activité.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($offers->hasPages())
                        <div class="d-flex justify-content-end mt-2">
                            {{ $offers->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
