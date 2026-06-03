@extends('layouts.admin-v6')
@section('title', 'Agents partenaire')

@section('content')
<div class="row mb-3">
    <div class="col-12 d-flex align-items-center justify-content-between">
        <h4 class="page-title mb-0 font-size-18">Agents - {{ $partner->display_name }}</h4>
        <a href="{{ route('admin.partners.show', $partner) }}" class="btn btn-outline-secondary btn-sm">Retour</a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Telephone</th>
                        <th>Roles</th>
                        <th>Statut</th>
                        <th>Creation</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($agents as $agent)
                        <tr>
                            <td>{{ $agent->name }}</td>
                            <td>{{ $agent->email }}</td>
                            <td>{{ $agent->phone ?? '-' }}</td>
                            <td>{{ $agent->roles->pluck('name')->join(', ') ?: '-' }}</td>
                            <td><span class="badge {{ $agent->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $agent->is_active ? 'Actif' : 'Desactive' }}</span></td>
                            <td>{{ $agent->created_at?->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Aucun agent partenaire.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $agents->links() }}</div>
    </div>
</div>
@endsection
