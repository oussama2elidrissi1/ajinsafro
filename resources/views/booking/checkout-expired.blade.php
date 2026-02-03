@extends('layouts.front')

@section('title', 'Session expirée')

@section('content')
<div class="container my-5">
    <div class="row">
        <div class="col-lg-6 mx-auto text-center">
            <div class="card shadow-sm">
                <div class="card-body py-5">
                    <div class="mb-4">
                        <i class="bx bx-time-five text-danger" style="font-size: 72px;"></i>
                    </div>
                    <h2 class="mb-3">Délai de réservation expiré</h2>
                    <p class="text-muted mb-4">
                        Le temps de réservation de 15 minutes a expiré. Les prix peuvent avoir changé.
                        Veuillez recommencer votre sélection.
                    </p>
                    <a href="{{ route('front.voyages.show', $checkoutToken->voyage->slug) }}" 
                        class="btn btn-primary btn-lg waves-effect waves-light">
                        <i class="bx bx-arrow-back me-2"></i>
                        Retour au voyage
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
