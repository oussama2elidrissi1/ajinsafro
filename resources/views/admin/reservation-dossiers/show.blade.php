@extends('layouts.admin-v2')

@section('title', 'Dossier de réservation')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h3">Dossier {{ $dossier->dossier_number ?? ('#'.$dossier->id) }}</h1>
            <div>
                <a href="{{ route('admin.reservation-dossiers.index') }}" class="btn btn-outline-secondary">Retour</a>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-8">
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Client</h5>
                        <p>{{ optional($dossier->client)->full_name ?? ($reservation->client_first_name ? $reservation->client_first_name.' '.$reservation->client_last_name : '—') }}</p>

                        <h5 class="card-title">Offre & Départ</h5>
                        <p>{{ optional($reservation->offer)->name ?? '—' }} — {{ optional($reservation->departure)->start_date ? optional($reservation->departure->start_date)->format('d/m/Y') : '—' }}</p>

                        <h5 class="card-title">Réservations liées</h5>
                        <ul>
                            @foreach($dossier->reservations as $res)
                                <li>#{{ $res->id }} — {{ $res->client_first_name ?? '—' }} {{ $res->client_last_name ?? '' }} — {{ $res->status }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Passagers</h5>
                        @if($reservation->passengers->isNotEmpty())
                            <ul>
                                @foreach($reservation->passengers as $p)
                                    <li>{{ $p->first_name ?? $p->name ?? '—' }} {{ $p->last_name ?? '' }}</li>
                                @endforeach
                            </ul>
                        @else
                            <p>Aucun passager enregistré.</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Résumé financier</h5>
                        <p><strong>Total:</strong> {{ number_format($dossier->total_amount ?? 0, 2, ',', ' ') }} DH</p>
                        <p><strong>Payé:</strong> {{ number_format($dossier->paid_amount ?? 0, 2, ',', ' ') }} DH</p>
                        <p><strong>Restant:</strong> {{ number_format($dossier->remaining_amount ?? 0, 2, ',', ' ') }} DH</p>
                        <p><strong>Statut paiement:</strong> {{ $dossier->payment_status ?? '—' }}</p>
                        <p><strong>Statut dossier:</strong> {{ $dossier->dossier_status ?? '—' }}</p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Historique</h5>
                        @if($dossier->histories->isNotEmpty())
                            <ul class="list-unstyled">
                                @foreach($dossier->histories as $h)
                                    <li class="mb-2">
                                        <small class="text-muted">{{ optional($h->created_at)->format('d/m/Y H:i') }} — {{ optional($h->user)->name ?? 'Système' }}</small>
                                        <div>{{ $h->action }} @if($h->note) — {{ $h->note }} @endif</div>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p>Aucun historique.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
