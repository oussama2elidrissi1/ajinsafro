@extends('layouts.admin-v6')
@section('title')
    Transferts des circuits
@endsection
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Transferts des circuits</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.circuits.index') }}">Circuits</a></li>
                        <li class="breadcrumb-item active">Transferts</li>
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

    @if (!empty($wpConnectionFailed))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <strong>Connexion WordPress indisponible.</strong> Vérifiez la configuration de la base WP.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <p class="text-muted mb-3">
                        Transfert aller (jour 1) : aéroport &rarr; hôtel. Transfert retour (dernier jour) : hôtel &rarr; aéroport.
                    </p>
                    @if($tours->isEmpty())
                        <p class="text-muted mb-0">Aucun circuit. <a href="{{ route('admin.circuits.voyages.create') }}">Créer un circuit</a> puis revenir ici pour définir les transferts.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover table-centered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="60">ID</th>
                                        <th>Titre du circuit</th>
                                        <th>Transfert aller</th>
                                        <th>Transfert retour</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($tours as $tour)
                                        @php
                                            $tr = $transfersByTour[$tour->ID] ?? ['arrival' => null, 'departure' => null, 'total' => 0];
                                            $arrival = $tr['arrival'];
                                            $departure = $tr['departure'];
                                            $isSet = $arrival || $departure;
                                        @endphp
                                        <tr>
                                            <td><strong>{{ $tour->ID }}</strong></td>
                                            <td>
                                                <a href="{{ route('admin.circuits.voyages.edit', $tour->ID) }}" class="text-body">{{ $tour->post_title }}</a>
                                                @if(($tr['total'] ?? 0) > 2)
                                                    {{-- Cette page n'edite que le premier transfert de chaque sens. --}}
                                                    <div class="text-muted font-size-12">
                                                        {{ $tr['total'] }} transferts enregistrés &middot;
                                                        <a href="{{ route('admin.circuits.voyages.edit', $tour->ID) }}?tab=flights">tout gérer dans le circuit</a>
                                                    </div>
                                                @endif
                                            </td>
                                            @foreach ([$arrival, $departure] as $leg)
                                                <td>
                                                    @if($leg && ($leg->from_label || $leg->to_label))
                                                        {{ $leg->from_label ?: 'Départ à préciser' }}
                                                        <span class="text-muted">&rarr;</span>
                                                        {{ $leg->to_label ?: 'Arrivée à préciser' }}
                                                        @if($leg->pickup_time)
                                                            <div class="text-muted font-size-12">Prise en charge {{ $leg->pickup_time }}</div>
                                                        @endif
                                                    @else
                                                        <span class="text-muted">&mdash;</span>
                                                    @endif
                                                </td>
                                            @endforeach
                                            <td class="text-end">
                                                <a href="{{ route('admin.circuits.tour-transfers.edit', $tour->ID) }}" class="btn btn-sm btn-soft-primary waves-effect waves-light">
                                                    {{ $isSet ? 'Modifier' : 'Définir' }}
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-center mt-3">
                            {{ $tours->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
