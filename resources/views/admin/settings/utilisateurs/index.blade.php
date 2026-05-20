@extends('layouts.admin-v6')
@section('title')
    Utilisateurs
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Utilisateurs</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.settings.index') }}">Paramètres</a></li>
                        <li class="breadcrumb-item active">Utilisateurs</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-8">
            <form method="GET" action="{{ route('admin.settings.utilisateurs') }}" class="d-flex gap-2">
                <input type="text" name="q" value="{{ $search }}" class="form-control" placeholder="Rechercher par nom ou email...">
                <button type="submit" class="btn btn-primary">Rechercher</button>
            </form>
        </div>
        <div class="col-md-4 text-md-end mt-2 mt-md-0">
            <a href="{{ route('admin.settings.utilisateurs.create') }}" class="btn btn-success">
                <i class="bx bx-user-plus"></i> Ajouter un utilisateur
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nom</th>
                                    <th>Email</th>
                                    <th>Rôle</th>
                                    <th>Mode</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($users as $user)
                                    <tr>
                                        <td>{{ $user->id }}</td>
                                        <td>{{ $user->name }}</td>
                                        <td>{{ $user->email }}</td>
                                        <td>{{ $user->roles->first()->name ?? $user->base_role ?? '�?"' }}</td>
                                        <td>
                                            @if($user->access_mode === 'custom')
                                                <span class="badge bg-warning">Personnalisé</span>
                                            @else
                                                <span class="badge bg-info">Rôle</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($user->is_active)
                                                <span class="badge bg-success">Actif</span>
                                            @else
                                                <span class="badge bg-secondary">Désactivé</span>
                                            @endif
                                        </td>
                                        <td class="d-flex gap-1">
                                            <a href="{{ route('admin.settings.utilisateurs.edit', $user) }}" class="btn btn-sm btn-primary">Modifier</a>
                                            <form method="POST" action="{{ route('admin.settings.utilisateurs.toggle-active', $user) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-warning">{{ $user->is_active ? 'Désactiver' : 'Activer' }}</button>
                                            </form>
                                            @if(auth()->id() !== $user->id)
                                                <form method="POST" action="{{ route('admin.settings.utilisateurs.destroy', $user) }}" onsubmit="return confirm('Supprimer cet utilisateur ?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger">Supprimer</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">Aucun utilisateur trouvé.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $users->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush


