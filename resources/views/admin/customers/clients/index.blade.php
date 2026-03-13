@extends('layouts.master-ajinsafro')
@section('title')
    {{ isset($trashed) && $trashed ? 'Clients supprimés' : 'Clients' }}
@endsection
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">{{ isset($trashed) && $trashed ? 'Corbeille – Clients' : 'Liste des clients' }}</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.customers.index') }}">Clients</a></li>
                        <li class="breadcrumb-item active">{{ isset($trashed) && $trashed ? 'Corbeille' : 'Liste clients' }}</li>
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
                    @if (isset($trashed) && $trashed)
                        <p class="text-muted mb-3">Clients supprimés (corbeille). Vous pouvez restaurer ou supprimer définitivement.</p>
                        <a href="{{ route('admin.customers.clients.index') }}" class="btn btn-soft-primary btn-sm mb-3"><i class="bx bx-list-ul me-1"></i> Retour à la liste</a>
                    @else
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                            <h4 class="card-title mb-0">Clients</h4>
                            <a href="{{ route('admin.customers.clients.create') }}" class="btn btn-primary waves-effect waves-light">
                                <i class="bx bx-plus me-1"></i> Nouveau client
                            </a>
                            <a href="{{ route('admin.customers.clients.trashed') }}" class="btn btn-outline-secondary btn-sm"><i class="bx bx-trash me-1"></i> Corbeille</a>
                        </div>
                    @endif

                    <form method="GET" class="mb-4">
                        @if (isset($trashed) && $trashed)
                            <input type="hidden" name="trashed" value="1">
                        @endif
                        <div class="row g-2 flex-wrap align-items-end">
                            <div class="col-auto flex-grow-1" style="min-width: 180px;">
                                <label class="form-label small">Recherche</label>
                                <input type="text" name="search" class="form-control form-control-sm" placeholder="Code, nom, email, tél..." value="{{ request('search') }}">
                            </div>
                            @if (!isset($trashed) || !$trashed)
                                <div class="col-auto">
                                    <label class="form-label small">Statut</label>
                                    <select name="status" class="form-select form-select-sm" style="width: auto;">
                                        <option value="">Tous</option>
                                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Actif</option>
                                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactif</option>
                                        <option value="blocked" {{ request('status') === 'blocked' ? 'selected' : '' }}>Bloqué</option>
                                        <option value="vip" {{ request('status') === 'vip' ? 'selected' : '' }}>VIP</option>
                                    </select>
                                </div>
                                <div class="col-auto">
                                    <label class="form-label small">Type</label>
                                    <select name="client_type" class="form-select form-select-sm" style="width: auto;">
                                        <option value="">Tous</option>
                                        <option value="individual" {{ request('client_type') === 'individual' ? 'selected' : '' }}>Particulier</option>
                                        <option value="company" {{ request('client_type') === 'company' ? 'selected' : '' }}>Société</option>
                                        <option value="agency" {{ request('client_type') === 'agency' ? 'selected' : '' }}>Agence</option>
                                    </select>
                                </div>
                                <div class="col-auto">
                                    <label class="form-label small">Source</label>
                                    <select name="source" class="form-select form-select-sm" style="width: auto;">
                                        <option value="">Toutes</option>
                                        <option value="website" {{ request('source') === 'website' ? 'selected' : '' }}>Site web</option>
                                        <option value="whatsapp" {{ request('source') === 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
                                        <option value="phone" {{ request('source') === 'phone' ? 'selected' : '' }}>Téléphone</option>
                                        <option value="admin" {{ request('source') === 'admin' ? 'selected' : '' }}>Admin</option>
                                    </select>
                                </div>
                                <div class="col-auto">
                                    <label class="form-label small">Assigné à</label>
                                    <select name="assigned_to" class="form-select form-select-sm" style="width: auto;">
                                        <option value="">Tous</option>
                                        @foreach($users as $u)
                                            <option value="{{ $u->id }}" {{ request('assigned_to') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <div class="col-auto">
                                <button type="submit" class="btn btn-primary btn-sm"><i class="bx bx-search-alt"></i> Filtrer</button>
                                <a href="{{ isset($trashed) && $trashed ? route('admin.customers.clients.trashed') : route('admin.customers.clients.index') }}" class="btn btn-outline-secondary btn-sm">Réinitialiser</a>
                            </div>
                        </div>
                    </form>

                    @if (isset($trashed) && $trashed)
                        <form action="{{ route('admin.customers.clients.bulk') }}" method="POST" id="bulk-form-trashed">
                            @csrf
                            <input type="hidden" name="action" value="restore">
                            <input type="hidden" name="ids" value="" id="bulk-ids-trashed">
                        </form>
                        <form action="{{ route('admin.customers.clients.bulk') }}" method="POST" id="bulk-form-force">
                            @csrf
                            <input type="hidden" name="action" value="force_delete">
                            <input type="hidden" name="ids" value="" id="bulk-ids-force">
                        </form>
                    @else
                        <form action="{{ route('admin.customers.clients.bulk') }}" method="POST" id="bulk-form">
                            @csrf
                            <input type="hidden" name="action" value="" id="bulk-action">
                        </form>
                    @endif

                    @if($clients->isEmpty())
                        <p class="text-muted mb-0">{{ isset($trashed) && $trashed ? 'Aucun client dans la corbeille.' : 'Aucun client. Créez votre premier client.' }}</p>
                        @if (!isset($trashed) || !$trashed)
                            <a href="{{ route('admin.customers.clients.create') }}" class="btn btn-primary btn-sm mt-2">Créer un client</a>
                        @endif
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover table-centered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        @if (!isset($trashed) || !$trashed)
                                            <th width="40"><input type="checkbox" id="select-all" aria-label="Tout sélectionner"></th>
                                        @endif
                                        <th>Code</th>
                                        <th>Nom</th>
                                        <th>Type</th>
                                        <th>Email</th>
                                        <th>Téléphone</th>
                                        <th>WhatsApp</th>
                                        <th>Nationalité</th>
                                        <th>Ville</th>
                                        <th>Catégorie</th>
                                        <th>Budget</th>
                                        <th>Statut</th>
                                        <th>Assigné</th>
                                        <th>Dernier contact</th>
                                        <th>Créé le</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($clients as $c)
                                        <tr>
                                            @if (!isset($trashed) || !$trashed)
                                                <td><input type="checkbox" class="row-select" value="{{ $c->id }}" form="bulk-form"></td>
                                            @endif
                                            <td><code>{{ $c->client_code }}</code></td>
                                            <td>
                                                <a href="{{ route('admin.customers.clients.show', $c) }}">{{ $c->full_name }}</a>
                                            </td>
                                            <td>
                                                @php
                                                    $typeBadge = match($c->client_type) {
                                                        'company' => 'bg-info',
                                                        'agency' => 'bg-secondary',
                                                        default => 'bg-light text-dark',
                                                    };
                                                @endphp
                                                <span class="badge {{ $typeBadge }}">{{ $c->client_type === 'individual' ? 'Particulier' : ($c->client_type === 'company' ? 'Société' : 'Agence') }}</span>
                                            </td>
                                            <td>{{ $c->email ?? '—' }}</td>
                                            <td>{{ $c->phone ?? '—' }}</td>
                                            <td>{{ $c->whatsapp_number ?? '—' }}</td>
                                            <td>{{ $c->nationality ?? '—' }}</td>
                                            <td>{{ $c->city ?? '—' }}</td>
                                            <td>{{ $c->traveler_category ?? '—' }}</td>
                                            <td>{{ $c->budget_display ?? '—' }}</td>
                                            <td>
                                                @php
                                                    $statusBadge = match($c->status) {
                                                        'active' => 'bg-success',
                                                        'inactive' => 'bg-warning text-dark',
                                                        'blocked' => 'bg-danger',
                                                        'vip' => 'bg-primary',
                                                        default => 'bg-secondary',
                                                    };
                                                @endphp
                                                <span class="badge {{ $statusBadge }}">{{ $c->status }}</span>
                                            </td>
                                            <td>{{ $c->assignedTo?->name ?? '—' }}</td>
                                            <td>{{ $c->last_contacted_at?->format('d/m/Y') ?? '—' }}</td>
                                            <td>{{ $c->created_at->format('d/m/Y') }}</td>
                                            <td class="text-end">
                                                @if (isset($trashed) && $trashed)
                                                    <form action="{{ route('admin.customers.clients.restore', $c->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-soft-success">Restaurer</button>
                                                    </form>
                                                    <form action="{{ route('admin.customers.clients.force', $c->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer définitivement ce client ?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-soft-danger">Supprimer</button>
                                                    </form>
                                                @else
                                                    <a href="{{ route('admin.customers.clients.show', $c) }}" class="btn btn-sm btn-soft-primary">Voir</a>
                                                    <a href="{{ route('admin.customers.clients.edit', $c) }}" class="btn btn-sm btn-soft-secondary">Modifier</a>
                                                    <form action="{{ route('admin.customers.clients.destroy', $c) }}" method="POST" class="d-inline" onsubmit="return confirm('Mettre ce client en corbeille ?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-soft-danger">Supprimer</button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
                            <div>
                                @if (!isset($trashed) || !$trashed)
                                    <span class="me-2">Actions groupées :</span>
                                    <button type="button" class="btn btn-sm btn-outline-success" data-bulk-action="activate">Activer</button>
                                    <button type="button" class="btn btn-sm btn-outline-warning" data-bulk-action="deactivate">Désactiver</button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bulk-action="block">Bloquer</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bulk-action="vip">VIP</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bulk-action="delete">Supprimer</button>
                                @endif
                            </div>
                            <div>{{ $clients->links() }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function() {
    var selectAll = document.getElementById('select-all');
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            document.querySelectorAll('.row-select').forEach(function(cb) { cb.checked = selectAll.checked; });
        });
    }
    document.querySelectorAll('[data-bulk-action]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var ids = [];
            document.querySelectorAll('.row-select:checked').forEach(function(cb) { ids.push(cb.value); });
            if (ids.length === 0) { alert('Sélectionnez au moins un client.'); return; }
            var action = this.getAttribute('data-bulk-action');
            if (action === 'delete' && !confirm('Mettre les clients sélectionnés en corbeille ?')) return;
            var form = document.getElementById('bulk-form');
            form.querySelector('#bulk-action').value = action;
            form.querySelectorAll('[name="ids[]"]').forEach(function(el) { el.remove(); });
            ids.forEach(function(id) {
                var inp = document.createElement('input');
                inp.type = 'hidden';
                inp.name = 'ids[]';
                inp.value = id;
                form.appendChild(inp);
            });
            form.submit();
        });
    });
})();
</script>
@endpush
