@extends('layouts.admin-v2')
@section('title', 'Revendeurs')

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Revendeurs</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.partners.index') }}">Réseau partenaires</a></li>
                        <li class="breadcrumb-item active">Revendeurs</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="GET" class="mb-3">
                        <div class="row g-2 align-items-end">
                            <div class="col-auto">
                                <input type="text" name="search" class="form-control form-control-sm" placeholder="Raison sociale, responsable, email..." value="{{ request('search') }}" style="min-width: 220px;">
                            </div>
                            <div class="col-auto">
                                <select name="status" class="form-select form-select-sm" style="width: auto;">
                                    <option value="">Tous les statuts</option>
                                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>En attente</option>
                                    <option value="validated" {{ request('status') === 'validated' ? 'selected' : '' }}>Validé</option>
                                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Refusé</option>
                                    <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspendu</option>
                                </select>
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-primary btn-sm"><i class="bx bx-search-alt"></i> Filtrer</button>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Raison sociale / Nom</th>
                                    <th>Responsable</th>
                                    <th>Email</th>
                                    <th>Téléphone</th>
                                    <th>Date d'inscription</th>
                                    <th>Statut</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($partners as $partner)
                                    <tr>
                                        <td>{{ $partner->id }}</td>
                                        <td>
                                            <strong>{{ $partner->raison_sociale }}</strong>
                                            @if($partner->nom_commercial && $partner->nom_commercial !== $partner->raison_sociale)
                                                <span class="text-muted small d-block">{{ $partner->nom_commercial }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $partner->nom_responsable }}</td>
                                        <td>{{ $partner->email }}</td>
                                        <td>{{ $partner->telephone ?? '—' }}</td>
                                        <td>{{ $partner->created_at?->format('d/m/Y H:i') }}</td>
                                        <td>
                                            @php
                                                $badge = match($partner->status) {
                                                    'pending' => 'badge bg-warning text-dark',
                                                    'validated' => 'badge bg-success',
                                                    'rejected' => 'badge bg-danger',
                                                    'suspended' => 'badge bg-secondary',
                                                    default => 'badge bg-light text-dark',
                                                };
                                            @endphp
                                            <span class="{{ $badge }}">{{ $partner->status }}</span>
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('admin.partner-accounts.show', $partner) }}" class="btn btn-sm btn-outline-primary" title="Voir"><i class="bx bx-show"></i></a>
                                            @if($partner->isPending())
                                                <form action="{{ route('admin.partner-accounts.validate', $partner) }}" method="post" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success" title="Valider"><i class="bx bx-check"></i></button>
                                                </form>
                                                <button type="button" class="btn btn-sm btn-outline-danger" title="Refuser" data-bs-toggle="modal" data-bs-target="#reject-modal-{{ $partner->id }}"><i class="bx bx-x"></i></button>
                                                @include('admin.partner-accounts._reject_modal', ['partner' => $partner])
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">Aucun compte partenaire.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if(method_exists($partners, 'links'))
                        <div class="d-flex justify-content-center mt-3">{{ $partners->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
