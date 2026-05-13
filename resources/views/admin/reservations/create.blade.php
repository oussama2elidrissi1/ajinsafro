@extends('layouts.admin-v2')

@section('title', 'Créer une réservation')
@section('hidePageFooter', '1')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/reservation-create.css') }}">
@endpush

@section('content')
    <div class="reservation-create">
        <header class="reservation-create__header">
            <nav class="reservation-create__breadcrumb" aria-label="Breadcrumb">
                <a href="{{ route('admin.reservations.index') }}">Réservations</a>
                <span>/</span>
                <span>Nouvelle</span>
            </nav>
            <div class="reservation-create__header-main">
                <div>
                    <h1 class="reservation-create__title">Créer une réservation</h1>
                    <p class="reservation-create__subtitle">Tunnel dédié pour ouvrir un dossier de réservation sans confusion avec le workspace.</p>
                </div>
                <a href="{{ route('admin.reservations.workspace') }}" class="reservation-create__back-link">Retour au workspace</a>
            </div>
        </header>

        @if ($errors->any())
            <div class="reservation-create__alert reservation-create__alert--error">
                <strong>Le dossier contient des erreurs.</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="post" action="{{ route('admin.reservations.store') }}" enctype="multipart/form-data" id="reservation-create-form">
            @csrf
            <input type="hidden" name="extras_json" id="reservation-create-extras-json" value="[]">
            <input type="hidden" name="room_allocations_json" id="reservation-room-allocations-json" value="{{ old('room_allocations_json', '[]') }}">
            <input type="hidden" name="accommodation_mode" id="reservation-accommodation-mode" value="rooms">
            <input type="hidden" name="total_base" id="reservation-total-base-input" value="{{ old('total_base', 0) }}">
            <input type="hidden" name="room_supplement_total" id="reservation-room-supplement-total-input" value="{{ old('room_supplement_total', 0) }}">
            <input type="hidden" name="extras_total" id="reservation-extras-total-input" value="{{ old('extras_total', 0) }}">
            <input type="hidden" name="total_amount" id="reservation-total-amount-input" value="{{ old('total_amount', 0) }}">

            <div class="reservation-create__layout">
                @include('admin.reservations.create.partials.stepper')

                <div class="reservation-create__main">
                    @include('admin.reservations.create.partials.step-prestation')
                    @include('admin.reservations.create.partials.step-client')
                    @include('admin.reservations.create.partials.step-voyageurs')
                    @include('admin.reservations.create.partials.step-extras')
                    @include('admin.reservations.create.partials.step-payment')
                    @include('admin.reservations.create.partials.step-dossier')
                </div>
            </div>
        </form>

        <script type="application/json" id="reservation-create-extras-map">{!! json_encode($extrasByVoyage ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/reservation-create.js') }}"></script>
@endpush
