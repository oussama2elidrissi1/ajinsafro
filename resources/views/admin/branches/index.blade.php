@extends('layouts.admin-v6')
@section('title')
    Agences / Points de vente
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Agences / Points de vente</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item active">Agences</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-12 text-end">
            @if(($canCreateBranch ?? false))
                <a href="{{ route('admin.branches.create') }}" class="btn btn-success">
                    <i class="bx bx-plus"></i> Nouvelle agence
                </a>
            @endif
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
                                    <th>Nom</th>
                                    <th>Code</th>
                                    <th>Type</th>
                                    <th>Ville</th>
                                    <th>Utilisateurs</th>
                                    <th>Reservations</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($branches as $branch)
                                    <tr>
                                        <td>{{ $branch->id }}</td>
                                        <td>{{ $branch->name }}</td>
                                        <td><span class="badge bg-secondary">{{ $branch->code }}</span></td>
                                        <td>
                                            @if($branch->type === 'head_office')
                                                <span class="badge bg-primary">Siege</span>
                                            @else
                                                <span class="badge bg-info">Point de vente</span>
                                            @endif
                                        </td>
                                        <td>{{ $branch->city ?? '-' }}</td>
                                        <td>{{ $branch->users_count }}</td>
                                        <td>{{ $branch->reservations_count ?? 0 }}</td>
                                        <td>
                                            @if($branch->is_active)
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-secondary">Inactive</span>
                                            @endif
                                        </td>
                                        <td class="d-flex gap-1">
                                            @can('settings.view')
                                                <a href="{{ route('admin.branches.edit', $branch) }}" class="btn btn-sm btn-primary">Modifier</a>
                                                @if(($branch->users_count ?? 0) === 0)
                                                    <form method="POST" action="{{ route('admin.branches.destroy', $branch) }}" onsubmit="return confirm('Supprimer cette agence ?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger">Supprimer</button>
                                                    </form>
                                                @endif
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted">Aucune agence.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($branches->hasPages())
                        <div class="d-flex justify-content-end mt-2">
                            {{ $branches->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

