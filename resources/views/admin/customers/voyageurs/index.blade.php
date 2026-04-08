@extends('layouts.master-ajinsafro')
@section('title', 'Voyageurs')
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Voyageurs</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.customers.index') }}">Clients</a></li>
                        <li class="breadcrumb-item active">Voyageurs</li>
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
                        <div>
                            <h4 class="card-title mb-0">Base voyageurs</h4>
                            <p class="text-muted mb-0">Source de vérité: table <code>clients</code> (même base utilisée dans les réservations).</p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('admin.customers.clients.create') }}" class="btn btn-primary waves-effect waves-light">
                                <i class="bx bx-plus me-1"></i> Nouveau voyageur
                            </a>
                            <a href="{{ route('admin.customers.clients.index') }}" class="btn btn-outline-secondary">
                                <i class="bx bx-list-ul me-1"></i> Vue complète (Clients)
                            </a>
                        </div>
                    </div>

                    <form method="GET" class="mb-4">
                        <div class="row g-2 align-items-end">
                            <div class="col-12 col-md-6 col-lg-5">
                                <label class="form-label small">Recherche</label>
                                <input type="text" name="search" class="form-control form-control-sm"
                                       placeholder="Code, nom, tél, email, CIN/passeport…"
                                       value="{{ request('search') }}">
                            </div>
                            <div class="col-12 col-md-3 col-lg-2">
                                <label class="form-label small">Statut</label>
                                <select name="status" class="form-select form-select-sm">
                                    <option value="">Tous</option>
                                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Actif</option>
                                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactif</option>
                                    <option value="blocked" {{ request('status') === 'blocked' ? 'selected' : '' }}>Bloqué</option>
                                    <option value="vip" {{ request('status') === 'vip' ? 'selected' : '' }}>VIP</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-3 col-lg-2">
                                <label class="form-label small">Par page</label>
                                <select name="per_page" class="form-select form-select-sm">
                                    @foreach([20, 50, 100] as $pp)
                                        <option value="{{ $pp }}" {{ (int)request('per_page', 20) === $pp ? 'selected' : '' }}>{{ $pp }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-lg-3">
                                <button type="submit" class="btn btn-primary btn-sm"><i class="bx bx-search-alt"></i> Filtrer</button>
                                <a href="{{ route('admin.customers.voyageurs') }}" class="btn btn-outline-secondary btn-sm">Réinitialiser</a>
                            </div>
                        </div>
                    </form>

                    @if($clients->isEmpty())
                        <p class="text-muted mb-0">Aucun voyageur trouvé.</p>
                        <a href="{{ route('admin.customers.clients.create') }}" class="btn btn-primary btn-sm mt-2">Créer un voyageur</a>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                <tr class="text-nowrap">
                                    <th>Code</th>
                                    <th>Nom complet</th>
                                    <th>Téléphone</th>
                                    <th>Email</th>
                                    <th>Ville</th>
                                    <th>CIN / Passeport</th>
                                    <th>Statut</th>
                                    <th>Créé le</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($clients as $c)
                                    @php
                                        $idDoc = $c->national_id_number ?: ($c->passport_number ?: null);
                                        $statusBadge = match($c->status) {
                                            'active' => 'bg-success',
                                            'inactive' => 'bg-warning text-dark',
                                            'blocked' => 'bg-danger',
                                            'vip' => 'bg-primary',
                                            default => 'bg-secondary',
                                        };
                                    @endphp
                                    <tr>
                                        <td><code>{{ $c->client_code }}</code></td>
                                        <td>
                                            <a href="{{ route('admin.customers.clients.show', $c) }}" class="fw-semibold">
                                                {{ $c->full_name ?: trim(($c->first_name ?? '').' '.($c->last_name ?? '')) ?: '—' }}
                                            </a>
                                        </td>
                                        <td>{{ $c->phone ?? '—' }}</td>
                                        <td>{{ $c->email ?? '—' }}</td>
                                        <td>{{ $c->city ?? '—' }}</td>
                                        <td>{{ $idDoc ?: '—' }}</td>
                                        <td><span class="badge {{ $statusBadge }}">{{ $c->status ?? '—' }}</span></td>
                                        <td>{{ $c->created_at?->format('d/m/Y') ?? '—' }}</td>
                                        <td class="text-end text-nowrap">
                                            <a href="{{ route('admin.customers.clients.show', $c) }}" class="btn btn-sm btn-soft-primary">Voir</a>
                                            <a href="{{ route('admin.customers.clients.edit', $c) }}" class="btn btn-sm btn-soft-secondary">Modifier</a>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-end mt-3">
                            {{ $clients->links() }}
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
