@extends('layouts.admin-v6')
@section('title')
    {{ isset($trashed) && $trashed ? 'Clients supprimés' : 'Clients' }}
@endsection
@section('content')
    <x-admin.page-header
        :title="isset($trashed) && $trashed ? 'Corbeille �?" Clients' : 'Liste des clients'"
        subtitle="Gérez, filtrez et consultez la base clients."
        :breadcrumbs="[
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Clients', 'url' => route('admin.customers.index')],
            ['label' => isset($trashed) && $trashed ? 'Corbeille' : 'Liste clients'],
        ]"
    >
        <x-slot name="actions">
            @if (!isset($trashed) || !$trashed)
                <a href="{{ route('admin.customers.clients.create') }}" class="aj-btn aj-btn-primary">
                    <i class="bx bx-plus"></i>
                    <span>Nouveau client</span>
                </a>
                <a href="{{ route('admin.customers.clients.trashed') }}" class="aj-btn aj-btn-soft">
                    <i class="bx bx-trash"></i>
                    <span>Corbeille</span>
                </a>
            @else
                <a href="{{ route('admin.customers.clients.index') }}" class="aj-btn aj-btn-soft">
                    <i class="bx bx-list-ul"></i>
                    <span>Retour à la liste</span>
                </a>
            @endif
        </x-slot>
    </x-admin.page-header>

    <x-admin.flash-messages />

    @if (!isset($trashed) || !$trashed)
        <x-admin.kpi-cards
            :kpis="[
                ['label' => 'Total clients', 'value' => number_format($clients->total(), 0, ',', ' '), 'icon' => 'bx bx-group', 'color' => '-blue', 'note' => 'Base complète'],
                ['label' => 'Actifs', 'value' => number_format($clients->where('status', 'active')->count(), 0, ',', ' '), 'icon' => 'bx bx-badge-check', 'color' => '-green', 'note' => 'En cours'],
                ['label' => 'VIP', 'value' => number_format($clients->where('status', 'vip')->count(), 0, ',', ' '), 'icon' => 'bx bx-star', 'color' => '-orange', 'note' => 'Prioritaires'],
                ['label' => 'Nouveaux ce mois', 'value' => number_format($clients->where('created_at', '>=', now()->startOfMonth())->count(), 0, ',', ' '), 'icon' => 'bx bx-user-plus', 'color' => '-violet', 'note' => 'Inscriptions'],
            ]"
        />
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    @if (isset($trashed) && $trashed)
                        <p class="text-muted mb-3">Clients supprimés (corbeille). Vous pouvez restaurer ou supprimer définitivement.</p>
                        <a href="{{ route('admin.customers.clients.index') }}" class="aj-btn aj-btn-soft mb-3"><i class="bx bx-list-ul me-1"></i> Retour à la liste</a>
                    @endif

                    <form method="GET" class="mb-4">
                        @if (isset($trashed) && $trashed)
                            <input type="hidden" name="trashed" value="1">
                        @endif
                        <div class="aj-filter-grid" style="grid-template-columns: minmax(200px, 1.4fr) repeat(4, minmax(0, .8fr)) minmax(160px, auto) auto;">
                            <div class="aj-field aj-search-wrap">
                                <span class="aj-search-icon"><i class="bx bx-search"></i></span>
                                <input type="text" name="search" class="aj-control" placeholder="Code, nom, email, tél..." value="{{ request('search') }}">
                            </div>
                            @if (!isset($trashed) || !$trashed)
                                <div class="aj-field">
                                    <select name="status" class="aj-control">
                                        <option value="">Tous les statuts</option>
                                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Actif</option>
                                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactif</option>
                                        <option value="blocked" {{ request('status') === 'blocked' ? 'selected' : '' }}>Bloqué</option>
                                        <option value="vip" {{ request('status') === 'vip' ? 'selected' : '' }}>VIP</option>
                                    </select>
                                </div>
                                <div class="aj-field">
                                    <select name="client_type" class="aj-control">
                                        <option value="">Tous les types</option>
                                        <option value="individual" {{ request('client_type') === 'individual' ? 'selected' : '' }}>Particulier</option>
                                        <option value="company" {{ request('client_type') === 'company' ? 'selected' : '' }}>Société</option>
                                        <option value="agency" {{ request('client_type') === 'agency' ? 'selected' : '' }}>Agence</option>
                                    </select>
                                </div>
                                <div class="aj-field">
                                    <select name="source" class="aj-control">
                                        <option value="">Toutes les sources</option>
                                        <option value="website" {{ request('source') === 'website' ? 'selected' : '' }}>Site web</option>
                                        <option value="whatsapp" {{ request('source') === 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
                                        <option value="phone" {{ request('source') === 'phone' ? 'selected' : '' }}>Téléphone</option>
                                        <option value="admin" {{ request('source') === 'admin' ? 'selected' : '' }}>Admin</option>
                                    </select>
                                </div>
                                <div class="aj-field">
                                    <select name="assigned_to" class="aj-control">
                                        <option value="">Tous les agents</option>
                                        @foreach($users as $u)
                                            <option value="{{ $u->id }}" {{ request('assigned_to') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <div class="d-flex flex-wrap gap-2">
                                <button type="submit" class="aj-btn aj-btn-primary w-100">
                                    <i class="bx bx-filter-alt"></i>
                                    <span>Filtrer</span>
                                </button>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="{{ isset($trashed) && $trashed ? route('admin.customers.clients.trashed') : route('admin.customers.clients.index') }}" class="aj-btn aj-btn-soft w-100">
                                    <i class="bx bx-reset"></i>
                                    <span>Réinitialiser</span>
                                </a>
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
                        <x-admin.empty-state
                            :title="isset($trashed) && $trashed ? 'Aucun client dans la corbeille' : 'Aucun client'"
                            :message="isset($trashed) && $trashed ? 'La corbeille est vide.' : 'Aucun client ne correspond à vos critères. Créez votre premier client.'"
                            :action-url="(!isset($trashed) || !$trashed) ? route('admin.customers.clients.create') : null"
                            action-label="Nouveau client"
                        />
                    @else
                        <div class="table-responsive" style="overflow-x:auto;">
                            <table class="aj-table" style="width:100%;border-collapse:separate;border-spacing:0;">
                                <thead>
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
                                                    $typeLabel = $c->client_type === 'individual' ? 'Particulier' : ($c->client_type === 'company' ? 'Société' : 'Agence');
                                                    $typeColor = match($c->client_type) {
                                                        'company' => 'info',
                                                        'agency' => 'neutral',
                                                        default => 'neutral',
                                                    };
                                                @endphp
                                                <x-admin.badge :type="$typeColor" :label="$typeLabel" />
                                            </td>
                                            <td>{{ $c->email ?? '�?"' }}</td>
                                            <td>{{ $c->phone ?? '�?"' }}</td>
                                            <td>{{ $c->whatsapp_number ?? '�?"' }}</td>
                                            <td>{{ $c->nationality ?? '�?"' }}</td>
                                            <td>{{ $c->city ?? '�?"' }}</td>
                                            <td>{{ $c->traveler_category ?? '�?"' }}</td>
                                            <td>{{ $c->budget_display ?? '�?"' }}</td>
                                            <td>
                                                @php
                                                    $statusColor = match($c->status) {
                                                        'active' => 'success',
                                                        'inactive' => 'warning',
                                                        'blocked' => 'danger',
                                                        'vip' => 'info',
                                                        default => 'neutral',
                                                    };
                                                @endphp
                                                <x-admin.badge :type="$statusColor" :label="strtoupper($c->status)" />
                                            </td>
                                            <td>{{ $c->assignedTo?->name ?? '�?"' }}</td>
                                            <td>{{ $c->last_contacted_at?->format('d/m/Y') ?? '�?"' }}</td>
                                            <td>{{ $c->created_at->format('d/m/Y') }}</td>
                                            <td class="text-end">
                                                @if (isset($trashed) && $trashed)
                                                    <form action="{{ route('admin.customers.clients.restore', $c->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="aj-btn aj-btn-soft" style="min-height:32px;padding:0 10px;font-size:12px;">Restaurer</button>
                                                    </form>
                                                    <form action="{{ route('admin.customers.clients.force', $c->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer définitivement ce client ?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="aj-btn aj-btn-soft" style="min-height:32px;padding:0 10px;font-size:12px;color:#d92d20;">Supprimer</button>
                                                    </form>
                                                @else
                                                    <x-admin.action-buttons
                                                        :view-url="route('admin.customers.clients.show', $c)"
                                                        :edit-url="route('admin.customers.clients.edit', $c)"
                                                        :delete-url="route('admin.customers.clients.destroy', $c)"
                                                        delete-confirm="Mettre ce client en corbeille ?"
                                                    />
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if (!isset($trashed) || !$trashed)
                            <div class="d-flex flex-wrap gap-2 mt-3 mb-2">
                                <span class="me-2" style="font-size:13px;font-weight:700;color:#5f6f85;">Actions groupées :</span>
                                <button type="button" class="aj-mini-btn" data-bulk-action="activate" style="color:#067647;">Activer</button>
                                <button type="button" class="aj-mini-btn" data-bulk-action="deactivate" style="color:#b54708;">Désactiver</button>
                                <button type="button" class="aj-mini-btn" data-bulk-action="block" style="color:#d92d20;">Bloquer</button>
                                <button type="button" class="aj-mini-btn" data-bulk-action="vip" style="color:#0550a7;">VIP</button>
                                <button type="button" class="aj-mini-btn" data-bulk-action="delete">Supprimer</button>
                            </div>
                        @endif
                        <x-admin.pagination-footer :paginator="$clients" />
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


