@extends('client.layout')
@section('title', 'Mes réservations')
@section('page_title', 'Mes réservations')

@section('content')
    <div class="card">
        <div class="card-body">
            @if($reservations->isEmpty())
                <div class="text-muted">Aucune réservation.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Statut</th>
                            <th>Voyage</th>
                            <th>Date</th>
                            <th class="text-end">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($reservations as $r)
                            <tr>
                                <td>#{{ $r->id }}</td>
                                <td>{{ $r->statusLabelFr() }}</td>
                                <td>{{ $r->tour?->title ?: ($r->tour_id ? ('Tour #'.$r->tour_id) : '—') }}</td>
                                <td>{{ $r->created_at?->format('d/m/Y H:i') }}</td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-soft-primary" href="{{ route('client.reservations.show', $r) }}">Détail</a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end mt-3">
                    {{ $reservations->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection

