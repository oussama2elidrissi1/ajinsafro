@extends('layouts.partner')
@section('title', 'Mes clients')

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Mes clients</h4>
                <a href="{{ route('partner.clients.create') }}" class="btn btn-primary"><i class="bx bx-plus me-1"></i> Nouveau client</a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="GET" class="mb-3">
                        <div class="row g-2 align-items-end">
                            <div class="col-auto flex-grow-1" style="min-width: 200px;">
                                <input type="text" name="search" class="form-control form-control-sm" placeholder="Nom, email, tél..." value="{{ request('search') }}">
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-primary btn-sm">Rechercher</button>
                            </div>
                        </div>
                    </form>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Code</th>
                                    <th>Nom</th>
                                    <th>Email</th>
                                    <th>Téléphone</th>
                                    <th class="text-end pe-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($clients as $client)
                                    <tr>
                                        <td class="ps-3">{{ $client->client_code }}</td>
                                        <td>{{ $client->full_name }}</td>
                                        <td>{{ $client->email ?? '—' }}</td>
                                        <td>{{ $client->phone ?? '—' }}</td>
                                        <td class="text-end pe-3">
                                            <a href="{{ route('partner.clients.show', $client) }}" class="btn btn-sm btn-outline-primary" title="Voir"><i class="bx bx-show"></i></a>
                                            <a href="{{ route('partner.clients.edit', $client) }}" class="btn btn-sm btn-outline-secondary" title="Modifier"><i class="bx bx-pencil"></i></a>
                                            <form action="{{ route('partner.clients.destroy', $client) }}" method="post" class="d-inline" onsubmit="return confirm('Supprimer ce client ?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer"><i class="bx bx-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">Aucun client.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if(method_exists($clients, 'links'))
                        <div class="d-flex justify-content-center mt-3">{{ $clients->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
