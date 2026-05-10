@extends('layouts.admin-v2')

@section('title', 'Fiche Group Deal')

@section('content')
<div class="container-fluid">
    <div class="page-title-box d-flex align-items-center justify-content-between">
        <div>
            <h4 class="mb-1">{{ $groupDeal->title }}</h4>
            <p class="text-muted mb-0">{{ $groupDeal->destination ?: 'Destination non renseignée' }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('front.group-deals.show', $groupDeal->slug) }}" target="_blank" class="btn btn-light">Voir la page publique</a>
            <a href="{{ route('admin.group-deals.edit', $groupDeal) }}" class="btn btn-primary">Modifier</a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card h-100"><div class="card-body"><div class="text-muted small">Inscrits actuels</div><div class="h3 mb-0">{{ $stats['current_participants'] }}</div></div></div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100"><div class="card-body"><div class="text-muted small">Prix actuel</div><div class="h3 mb-0">{{ $stats['current_price'] ? number_format($stats['current_price'], 0, ',', ' ') . ' DH' : 'N/A' }}</div></div></div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100"><div class="card-body"><div class="text-muted small">Places restantes</div><div class="h3 mb-0">{{ $stats['remaining_places'] }}</div></div></div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100"><div class="card-body"><div class="text-muted small">Garantie</div><div class="h5 mb-0">{{ $stats['is_guaranteed'] ? 'Voyage garanti' : 'En attente' }}</div></div></div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Progression</h5>
                    <form method="POST" action="{{ route('admin.group-deals.recalculate', $groupDeal) }}">
                        @csrf
                        <button class="btn btn-sm btn-outline-primary">Recalculer</button>
                    </form>
                </div>
                <div class="card-body">
                    <div class="progress mb-3" style="height:12px;">
                        <div class="progress-bar {{ $stats['is_guaranteed'] ? 'bg-success' : 'bg-warning' }}" style="width: {{ $stats['progress_percent'] }}%"></div>
                    </div>
                    <div class="row g-3 small">
                        <div class="col-md-4"><strong>Minimum garanti:</strong> {{ $groupDeal->min_participants }}</div>
                        <div class="col-md-4"><strong>Maximum:</strong> {{ $groupDeal->max_participants }}</div>
                        <div class="col-md-4"><strong>Statut:</strong> {{ $groupDeal->status_label }}</div>
                    </div>
                    <div class="mt-3 text-muted">
                        @if($stats['remaining_to_guarantee'] > 0)
                            Il reste {{ $stats['remaining_to_guarantee'] }} personne(s) pour garantir le départ.
                        @else
                            Le voyage est garanti.
                        @endif
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Paliers de prix</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead>
                            <tr>
                                <th>Min</th>
                                <th>Max</th>
                                <th>Prix</th>
                                <th>Libellé</th>
                                <th>Ordre</th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($groupDeal->pricingTiers as $tier)
                                <tr class="{{ optional($stats['active_tier'])->id === $tier->id ? 'table-warning' : '' }}">
                                    <td><input type="number" name="min_participants" form="tier-form-{{ $tier->id }}" class="form-control" value="{{ $tier->min_participants }}"></td>
                                    <td><input type="number" name="max_people" form="tier-form-{{ $tier->id }}" class="form-control" value="{{ $tier->max_people }}"></td>
                                    <td><input type="number" step="0.01" name="price_per_person" form="tier-form-{{ $tier->id }}" class="form-control" value="{{ $tier->price_per_person }}"></td>
                                    <td><input type="text" name="label" form="tier-form-{{ $tier->id }}" class="form-control" value="{{ $tier->label }}"></td>
                                    <td><input type="number" name="sort_order" form="tier-form-{{ $tier->id }}" class="form-control" value="{{ $tier->sort_order }}"></td>
                                    <td class="text-nowrap">
                                        <form id="tier-form-{{ $tier->id }}" method="POST" action="{{ route('admin.group-deals.tiers.update', [$groupDeal, $tier]) }}" class="d-inline">
                                            @csrf
                                            @method('PUT')
                                            <button class="btn btn-sm btn-primary">OK</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.group-deals.tiers.destroy', [$groupDeal, $tier]) }}" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-light" onclick="return confirm('Supprimer ce palier ?')">Suppr.</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                            <tr>
                                <td><input type="number" name="min_participants" form="tier-create-form" class="form-control" placeholder="4"></td>
                                <td><input type="number" name="max_people" form="tier-create-form" class="form-control" placeholder="8"></td>
                                <td><input type="number" step="0.01" name="price_per_person" form="tier-create-form" class="form-control" placeholder="9000"></td>
                                <td><input type="text" name="label" form="tier-create-form" class="form-control" placeholder="Palier"></td>
                                <td><input type="number" name="sort_order" form="tier-create-form" class="form-control" placeholder="1"></td>
                                <td>
                                    <form id="tier-create-form" method="POST" action="{{ route('admin.group-deals.tiers.store', $groupDeal) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-success">Ajouter</button>
                                    </form>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="mb-0">Participants</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                            <tr>
                                <th>Participant</th>
                                <th>Qté</th>
                                <th>Prix saisi</th>
                                <th>Statut</th>
                                <th>Paiement</th>
                                <th>Date</th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($groupDeal->participants as $participant)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $participant->full_name }}</div>
                                        <div class="small text-muted">{{ $participant->email }}</div>
                                    </td>
                                    <td>{{ $participant->participants_count }}</td>
                                    <td>{{ $participant->selected_price ? number_format((float) $participant->selected_price, 0, ',', ' ') . ' DH' : 'N/A' }}</td>
                                    <td>
                                        <select name="status" form="participant-form-{{ $participant->id }}" class="form-select form-select-sm">
                                            @foreach(['pending' => 'En attente', 'confirmed' => 'Confirmé', 'paid' => 'Payé', 'cancelled' => 'Annulé'] as $key => $label)
                                                <option value="{{ $key }}" @selected($participant->status === $key)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <select name="payment_status" form="participant-form-{{ $participant->id }}" class="form-select form-select-sm">
                                            @foreach(['pending' => 'En attente', 'paid' => 'Payé', 'cancelled' => 'Annulé'] as $key => $label)
                                                <option value="{{ $key }}" @selected($participant->payment_status === $key)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>{{ optional($participant->created_at)->format('d/m/Y H:i') }}</td>
                                    <td>
                                        <form id="participant-form-{{ $participant->id }}" method="POST" action="{{ route('admin.group-deals.participants.update', [$groupDeal, $participant]) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="btn btn-sm btn-outline-primary">Mettre à jour</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted py-4">Aucun participant pour l’instant.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Ajouter un participant</h5></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.group-deals.participants.store', $groupDeal) }}" class="vstack gap-3">
                        @csrf
                        <div>
                            <label class="form-label">Client existant</label>
                            <select name="client_id" class="form-select">
                                <option value="">Saisie libre</option>
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}">{{ $client->full_name ?: $client->email }}{{ $client->email ? ' · '.$client->email : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Nom complet</label>
                            <input type="text" name="full_name" class="form-control">
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Téléphone</label>
                                <input type="text" name="phone" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control">
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Nb personnes</label>
                                <input type="number" min="1" name="participants_count" class="form-control" value="1">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Statut</label>
                                <select name="status" class="form-select">
                                    <option value="pending">En attente</option>
                                    <option value="confirmed">Confirmé</option>
                                    <option value="paid">Payé</option>
                                    <option value="cancelled">Annulé</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Paiement</label>
                                <select name="payment_status" class="form-select">
                                    <option value="pending">En attente</option>
                                    <option value="paid">Payé</option>
                                    <option value="cancelled">Annulé</option>
                                </select>
                            </div>
                        </div>
                        <button class="btn btn-primary">Ajouter le participant</button>
                    </form>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Résumé</h5></div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0 vstack gap-2">
                        <li><strong>Départ:</strong> {{ optional($groupDeal->start_date)->format('d/m/Y') ?: 'N/A' }}</li>
                        <li><strong>Retour:</strong> {{ optional($groupDeal->end_date)->format('d/m/Y') ?: 'N/A' }}</li>
                        <li><strong>Deadline:</strong> {{ optional($groupDeal->registration_deadline)->format('d/m/Y') ?: 'N/A' }}</li>
                        <li><strong>Partage client:</strong> {{ $groupDeal->share_enabled ? 'Activé' : 'Désactivé' }}</li>
                        <li><strong>Meilleur prix:</strong> {{ optional($stats['best_tier'])->price_per_person ? number_format((float) $stats['best_tier']->price_per_person, 0, ',', ' ') . ' DH' : 'N/A' }}</li>
                        <li><strong>Prochain palier:</strong>
                            @if($stats['next_tier'])
                                {{ $stats['next_tier']->min_participants }} pers. → {{ number_format((float) $stats['next_tier']->price_per_person, 0, ',', ' ') }} DH
                            @else
                                Aucun
                            @endif
                        </li>
                    </ul>
                </div>
            </div>

            @if($groupDeal->image_url)
                <div class="card">
                    <div class="card-body p-2">
                        <img src="{{ $groupDeal->image_url }}" alt="{{ $groupDeal->title }}" class="img-fluid rounded">
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
