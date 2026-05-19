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
            <strong>Connexion WordPress indisponible.</strong> VÃ©rifiez la configuration de la base WP.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <p class="text-muted mb-3">
                        Transfert aller (Jour 1) : AÃ©roport â†’ HÃ´tel. Transfert retour (dernier jour) : HÃ´tel â†’ AÃ©roport.
                    </p>
                    @if($tours->isEmpty())
                        <p class="text-muted mb-0">Aucun tour. <a href="{{ route('admin.circuits.voyages.create') }}">CrÃ©er un tour</a> puis revenir ici pour dÃ©finir les transferts.</p>
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
                                        @php $tr = $transfersByTour[$tour->ID] ?? ['arrival' => null, 'departure' => null]; @endphp
                                        <tr>
                                            <td><strong>{{ $tour->ID }}</strong></td>
                                            <td>
                                                <a href="{{ route('admin.circuits.voyages.edit', $tour->ID) }}" class="text-body">{{ $tour->post_title }}</a>
                                            </td>
                                            <td>
                                                @if($tr['arrival'] && ($tr['arrival']->from_label || $tr['arrival']->to_label))
                                                    {{ $tr['arrival']->from_label ?? 'â€”' }} â†’ {{ $tr['arrival']->to_label ?? 'â€”' }}
                                                @else
                                                    <span class="text-muted">â€”</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($tr['departure'] && ($tr['departure']->from_label || $tr['departure']->to_label))
                                                    {{ $tr['departure']->from_label ?? 'â€”' }} â†’ {{ $tr['departure']->to_label ?? 'â€”' }}
                                                @else
                                                    <span class="text-muted">â€”</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <a href="{{ route('admin.circuits.voyages.edit', $tour->ID) }}?tab=flights" class="btn btn-sm btn-soft-primary waves-effect waves-light">GÃ©rer (dans le voyage)</a>
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

