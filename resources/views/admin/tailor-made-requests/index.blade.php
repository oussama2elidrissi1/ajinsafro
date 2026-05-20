@extends('layouts.admin-v6')

@section('title', 'Demande a la carte')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="mb-sm-0">Demande a la carte</h4>
                        <p class="text-muted mb-0 mt-1">Demandes envoyees depuis les pages publiques (single tour).</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="text-muted">
                                <span class="badge bg-info-subtle text-info me-2">Demande a la carte</span>
                                <span>Total: {{ $requests->total() }}</span>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Voyage</th>
                                        <th>Client</th>
                                        <th>Telephone</th>
                                        <th>Lieu de depart</th>
                                        <th>Date demandee</th>
                                        <th>Voyageurs</th>
                                        <th>Statut</th>
                                        <th>Cree le</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($requests as $req)
                                        <tr>
                                            <td style="min-width: 260px;">
                                                <div class="fw-semibold">
                                                    {{ $req->tour_title ?: ('Voyage #' . ($req->voyage_id ?: '-')) }}
                                                </div>
                                                <div class="text-muted small">
                                                    @if ($req->tour_url)
                                                        <a href="{{ $req->tour_url }}" target="_blank" rel="noopener">Voir sur le site</a>
                                                    @else
                                                        <span>WP: {{ $req->wp_post_id ?: '-' }}</span>
                                                    @endif
                                                    @if ($req->voyage_id)
                                                        <span class="ms-2">Laravel: {{ $req->voyage_id }}</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <div class="fw-semibold">{{ trim(($req->client_first_name ?: '') . ' ' . ($req->client_last_name ?: '')) ?: '-' }}</div>
                                                <div class="text-muted small">{{ $req->client_email ?: '-' }}</div>
                                            </td>
                                            <td>{{ $req->client_phone ?: '-' }}</td>
                                            <td>{{ $req->custom_departure_place ?: '-' }}</td>
                                            <td>{{ $req->custom_departure_date ? $req->custom_departure_date->format('d/m/Y') : '-' }}</td>
                                            <td>{{ $req->travellers_total ?: (($req->adults ?: 0) + ($req->children ?: 0)) }}</td>
                                            <td>
                                                @php
                                                    $status = (string) ($req->status ?: 'new');
                                                    $badge = 'bg-secondary-subtle text-secondary';
                                                    if (in_array($status, ['new', 'nouveau'], true)) $badge = 'bg-warning-subtle text-warning';
                                                    if (in_array($status, ['pending', 'en_attente', 'en attente'], true)) $badge = 'bg-info-subtle text-info';
                                                    if (in_array($status, ['processed', 'traitee', 'traitee'], true)) $badge = 'bg-success-subtle text-success';
                                                @endphp
                                                <span class="badge {{ $badge }}">{{ $status }}</span>
                                            </td>
                                            <td>{{ $req->created_at ? $req->created_at->format('d/m/Y H:i') : '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-4">Aucune demande pour le moment.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">
                            {{ $requests->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

