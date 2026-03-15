@extends('layouts.partner')
@section('title', 'Client')

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">{{ $client->full_name }}</h4>
                <div>
                    <a href="{{ route('partner.clients.edit', $client) }}" class="btn btn-outline-primary btn-sm">Modifier</a>
                    <a href="{{ route('partner.clients.index') }}" class="btn btn-outline-secondary btn-sm">Retour</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-md-6"><strong>Code client</strong><br>{{ $client->client_code }}</div>
                        <div class="col-md-6"><strong>Nom complet</strong><br>{{ $client->full_name }}</div>
                        <div class="col-md-6"><strong>Email</strong><br>{{ $client->email ?? '—' }}</div>
                        <div class="col-md-6"><strong>Téléphone</strong><br>{{ $client->phone ?? '—' }}</div>
                        <div class="col-md-6"><strong>Ville</strong><br>{{ $client->city ?? '—' }}</div>
                        <div class="col-md-6"><strong>Code postal</strong><br>{{ $client->postal_code ?? '—' }}</div>
                        <div class="col-12"><strong>Adresse</strong><br>{{ $client->address_line_1 ?? '—' }}</div>
                        <div class="col-md-6"><strong>Nationalité</strong><br>{{ $client->nationality ?? '—' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
