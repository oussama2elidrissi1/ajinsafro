@extends('layouts.admin-v6')

@section('title', 'Demandes Formule économique')

@section('content')
    <x-admin.page-header
        title="Demandes Formule économique"
        subtitle="Suivez les demandes envoyées depuis la page publique."
        :breadcrumbs="[
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Formule économique', 'url' => route('admin.economic-offers.index')],
            ['label' => 'Demandes'],
        ]"
    />

    <x-admin.flash-messages />

    <x-admin.filter-panel :action="route('admin.economic-offers.requests.index')" :reset-url="route('admin.economic-offers.requests.index')" grid-class="row g-3 align-items-end">
        <x-slot name="fields">
            <div class="col-md-4">
                <label class="form-label">Recherche</label>
                <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control" placeholder="Nom, email, téléphone, offre">
            </div>
            <div class="col-md-3">
                <label class="form-label">Statut</label>
                <select name="status" class="form-select">
                    <option value="">Tous</option>
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </x-slot>
    </x-admin.filter-panel>

    <section class="aj-panel">
        @if($requests->isEmpty())
            <x-admin.empty-state title="Aucune demande" message="Les demandes du site public apparaîtront ici." />
        @else
            <div class="table-responsive">
                <table class="aj-table" style="width:100%;">
                    <thead>
                    <tr>
                        <th>Date</th>
                        <th>Client</th>
                        <th>Offre</th>
                        <th>Départ choisi</th>
                        <th>Statut</th>
                        <th>Agent</th>
                        <th class="text-end">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($requests as $requestItem)
                        <tr>
                            <td>{{ $requestItem->created_at?->format('d/m/Y H:i') }}</td>
                            <td>
                                <div class="fw-bold">{{ $requestItem->full_name }}</div>
                                <div class="text-muted small">{{ $requestItem->phone }} · {{ $requestItem->email }}</div>
                            </td>
                            <td>{{ $requestItem->offer_title ?: optional($requestItem->offer)->title ?: 'Non associée' }}</td>
                            <td>{{ $requestItem->selected_departure_date?->format('d/m/Y') ?: '?' }}</td>
                            <td>
                                <x-admin.badge
                                    :type="match($requestItem->status){
                                        'new' => 'warning',
                                        'contacted' => 'info',
                                        'confirmed' => 'success',
                                        default => 'danger'
                                    }"
                                    :label="$requestItem->status_label"
                                />
                            </td>
                            <td>{{ $requestItem->responsible_agent ?: '?' }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.economic-offers.requests.show', $requestItem) }}" class="aj-btn aj-btn-soft btn-sm">
                                    Voir le détail
                                </a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <x-admin.pagination-footer :paginator="$requests" />
        @endif
    </section>
@endsection


