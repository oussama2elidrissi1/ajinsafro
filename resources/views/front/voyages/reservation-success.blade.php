@extends('layouts.front')

@section('title', 'Demande de réservation reçue - ' . $voyage->name)

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/front-voyage-kiosk.css') }}?v=booking-2step-1">
@endpush

@section('content')
<section class="ksk-success">
    <div class="ksk-container">
        <div class="ksk-success__card">
            <span class="ksk-success__badge"><i class="fas fa-check-circle"></i> Demande reçue</span>
            <h1>Votre demande de réservation a bien été reçue</h1>
            <p>
                Notre équipe Ajinsafro va vérifier votre demande et vous contacter pour la validation finale.
            </p>

            <div class="ksk-success__grid">
                <div class="ksk-success__item">
                    <span>Référence dossier</span>
                    <strong>{{ $reservation->dossier_number ?: ('RES-' . $reservation->id) }}</strong>
                </div>
                <div class="ksk-success__item">
                    <span>Voyage</span>
                    <strong>{{ $voyage->name }}</strong>
                </div>
                <div class="ksk-success__item">
                    <span>Statut</span>
                    <strong>En attente de validation</strong>
                </div>
                <div class="ksk-success__item">
                    <span>Total estimé</span>
                    <strong>{{ number_format((float) ($reservation->total_amount ?? 0), 0, ',', ' ') }} {{ $voyage->currency_symbol }}</strong>
                </div>
            </div>

            <div class="ksk-success__actions">
                <a href="{{ route('front.voyages.show', $voyage->slug) }}" class="ksk-btn ksk-btn--reserve">
                    <i class="fas fa-arrow-left"></i> Retour au voyage
                </a>
                <a href="https://wa.me/212660683464?text={{ rawurlencode('Bonjour, je souhaite suivre ma demande de réservation '.$reservation->dossier_number) }}" target="_blank" rel="noopener" class="ksk-btn ksk-btn--ghost">
                    <i class="fab fa-whatsapp"></i> Contacter Ajinsafro
                </a>
            </div>
        </div>
    </div>
</section>
@endsection
