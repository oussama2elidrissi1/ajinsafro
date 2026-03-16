@extends('layouts.master-ajinsafro')
@section('title')
    Dashboard AjinsAfro
@endsection
@section('content')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Dashboard AjinsAfro</h4>
                <div class="page-title-right">
                    @if($stats['can_see_all_branches'] ?? false)
                        <a href="{{ route('admin.dashboard.vue-globale') }}" class="btn btn-primary btn-sm">Vue globale</a>
                    @endif
                    <ol class="breadcrumb m-0 ms-2">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item active">Dashboard</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row g-3">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 bg-primary bg-opacity-10 text-primary rounded p-2 me-3">
                            <i class="bx bx-calendar-check font-size-22"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small">Réservations</p>
                            <h5 class="mb-0">{{ $stats['reservations_total'] }}</h5>
                        </div>
                    </div>
                    <a href="{{ route('admin.reservations.index') }}" class="stretched-link"></a>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 bg-success bg-opacity-10 text-success rounded p-2 me-3">
                            <i class="bx bx-user font-size-22"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small">Clients</p>
                            <h5 class="mb-0">{{ $stats['clients_count'] }}</h5>
                        </div>
                    </div>
                    <a href="{{ route('admin.customers.clients.index') }}" class="stretched-link"></a>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 bg-info bg-opacity-10 text-info rounded p-2 me-3">
                            <i class="bx bx-time-five font-size-22"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small">En cours</p>
                            <h5 class="mb-0">{{ $stats['reservations_en_cours'] }}</h5>
                        </div>
                    </div>
                    <a href="{{ route('admin.reservations.en-attente') }}" class="stretched-link"></a>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 bg-warning bg-opacity-10 text-warning rounded p-2 me-3">
                            <i class="bx bx-check-circle font-size-22"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small">Validées</p>
                            <h5 class="mb-0">{{ $stats['reservations_validees'] }}</h5>
                        </div>
                    </div>
                    <a href="{{ route('admin.reservations.confirmees') }}" class="stretched-link"></a>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <p class="text-muted mb-0">Tableau de bord selon votre rôle et votre agence. Consultez la <a href="{{ route('admin.dashboard.vue-globale') }}">Vue globale</a> pour les statistiques détaillées.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
