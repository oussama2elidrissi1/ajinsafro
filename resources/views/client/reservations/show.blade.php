@extends('client.layout')
@section('title', 'Réservation #'.$reservation->id)
@section('page_title', 'Réservation #'.$reservation->id)

@section('content')
    <div class="row">
        <div class="col-xl-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-2">Résumé</h5>
                    <div><span class="text-muted">Statut:</span> {{ $reservation->statusLabelFr() }}</div>
                    <div><span class="text-muted">Créée le:</span> {{ $reservation->created_at?->format('d/m/Y H:i') }}</div>
                    <div><span class="text-muted">Voyage:</span> {{ $reservation->tour?->title ?: ($reservation->tour_id ? ('Tour #'.$reservation->tour_id) : '—') }}</div>
                    <div><span class="text-muted">Départ:</span> {{ $reservation->travelDate?->travel_date ? $reservation->travelDate->travel_date->format('d/m/Y') : ($reservation->attributes['departure_date'] ?? '—') }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-2">Voyageurs</h5>
                    @if($reservation->passengers->isEmpty())
                        <div class="text-muted">—</div>
                    @else
                        <ul class="mb-0">
                            @foreach($reservation->passengers as $p)
                                <li>{{ trim(($p->first_name ?? '').' '.($p->last_name ?? '')) ?: '—' }} <span class="text-muted">({{ $p->type ?? '—' }})</span></li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

