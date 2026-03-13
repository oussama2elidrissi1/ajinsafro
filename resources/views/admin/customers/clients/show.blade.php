@extends('layouts.master-ajinsafro')
@section('title')
    Fiche client {{ $client->client_code }}
@endsection
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Fiche client – {{ $client->full_name }}</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.customers.index') }}">Clients</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.customers.clients.index') }}">Liste clients</a></li>
                        <li class="breadcrumb-item active">{{ $client->client_code }}</li>
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

    <div class="row mb-3">
        <div class="col-12">
            <a href="{{ route('admin.customers.clients.edit', $client) }}" class="btn btn-primary btn-sm"><i class="bx bx-edit me-1"></i> Modifier</a>
            <a href="{{ route('admin.customers.clients.index') }}" class="btn btn-outline-secondary btn-sm">Retour à la liste</a>
            <form action="{{ route('admin.customers.clients.destroy', $client) }}" method="POST" class="d-inline" onsubmit="return confirm('Mettre ce client en corbeille ?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger btn-sm">Supprimer</button>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Résumé</h5>
                    <p class="mb-1"><strong>Code :</strong> <code>{{ $client->client_code }}</code></p>
                    <p class="mb-1"><strong>Type :</strong>
                        <span class="badge {{ $client->client_type === 'company' ? 'bg-info' : ($client->client_type === 'agency' ? 'bg-secondary' : 'bg-light text-dark') }}">
                            {{ $client->client_type === 'individual' ? 'Particulier' : ($client->client_type === 'company' ? 'Société' : 'Agence') }}
                        </span>
                    </p>
                    <p class="mb-1"><strong>Statut :</strong>
                        @php
                            $statusBadge = match($client->status) {
                                'active' => 'bg-success',
                                'inactive' => 'bg-warning text-dark',
                                'blocked' => 'bg-danger',
                                'vip' => 'bg-primary',
                                default => 'bg-secondary',
                            };
                        @endphp
                        <span class="badge {{ $statusBadge }}">{{ $client->status }}</span>
                    </p>
                    @if($client->source)
                        <p class="mb-1"><strong>Source :</strong> {{ $client->source }}</p>
                    @endif
                    @if($client->assignedTo)
                        <p class="mb-1"><strong>Assigné à :</strong> {{ $client->assignedTo->name }}</p>
                    @endif
                    <p class="mb-0 small text-muted">Créé le {{ $client->created_at->format('d/m/Y H:i') }}
                        @if($client->updated_at && $client->updated_at != $client->created_at)
                            · Modifié le {{ $client->updated_at->format('d/m/Y H:i') }}
                        @endif
                    </p>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-body">
                    <h5 class="card-title">Coordonnées</h5>
                    @if($client->email)<p class="mb-1"><a href="mailto:{{ $client->email }}">{{ $client->email }}</a></p>@endif
                    @if($client->phone)<p class="mb-1">Tél : {{ $client->phone }}</p>@endif
                    @if($client->whatsapp_number)<p class="mb-1">WhatsApp : {{ $client->whatsapp_number }}</p>@endif
                    @if($client->address_line_1 || $client->city)
                        <p class="mb-0">{{ $client->address_line_1 }}{{ $client->address_line_2 ? ', ' . $client->address_line_2 : '' }}<br>
                            {{ $client->city }}{{ $client->postal_code ? ' ' . $client->postal_code : '' }}<br>
                            {{ $client->country_of_residence ?? '' }}</p>
                    @endif
                    @if(!$client->email && !$client->phone && !$client->address_line_1)
                        <p class="text-muted mb-0">—</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title">Documents & Visa</h5>
                    <p class="mb-1"><strong>Passeport :</strong> {{ $client->passport_number ?? '—' }} @if($client->passport_expiry_date) (exp. {{ $client->passport_expiry_date->format('d/m/Y') }}) @endif</p>
                    <p class="mb-1"><strong>CIN / ID :</strong> {{ $client->national_id_number ?? '—' }}</p>
                    <p class="mb-0"><strong>Visa :</strong> {{ $client->visa_required ? 'Requis – ' . $client->visa_status : 'Non requis' }}</p>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title">Préférences voyage</h5>
                    <p class="mb-1"><strong>Catégorie :</strong> {{ $client->traveler_category ?? '—' }}</p>
                    <p class="mb-1"><strong>Destination préférée :</strong> {{ $client->preferred_destination ?? '—' }}</p>
                    <p class="mb-1"><strong>Budget :</strong> {{ $client->budget_display ?? '—' }}</p>
                    @if($client->special_requests)
                        <p class="mb-0"><strong>Demandes spéciales :</strong><br>{{ $client->special_requests }}</p>
                    @endif
                </div>
            </div>

            @if($client->company_name || $client->billing_name)
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Facturation</h5>
                        @if($client->company_name)<p class="mb-1"><strong>Société :</strong> {{ $client->company_name }}</p>@endif
                        <p class="mb-1"><strong>Facturation :</strong> {{ $client->billing_name ?? $client->full_name }}</p>
                        @if($client->billing_email)<p class="mb-0">{{ $client->billing_email }}</p>@endif
                    </div>
                </div>
            @endif

            @if($client->internal_notes)
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Notes internes</h5>
                        <p class="mb-0 text-muted">{{ $client->internal_notes }}</p>
                    </div>
                </div>
            @endif

            {{-- Emplacements futurs : réservations, devis, paiements, documents --}}
            <div class="card mb-3 border-secondary">
                <div class="card-body">
                    <h5 class="card-title text-muted">Réservations / Devis / Paiements</h5>
                    <p class="text-muted small mb-0">À venir : liens vers les réservations, devis et paiements associés à ce client.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
