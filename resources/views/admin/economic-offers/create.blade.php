@extends('layouts.admin-v6')

@push('styles')
    <link href="{{ URL::asset('css/admin-economic-offer-form.css') }}" rel="stylesheet" type="text/css" />
@endpush

@section('title', 'Nouvelle offre économique')

@section('content')
    <x-admin.page-header
        title="Nouvelle offre économique"
        subtitle="Ajoutez une offre voyage, omra, hébergement ou activité à petit budget."
        :breadcrumbs="[
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Formule économique', 'url' => route('admin.economic-offers.index')],
            ['label' => 'Création'],
        ]"
    >
        <x-slot name="actions">
            <a href="{{ route('admin.economic-offers.index') }}" class="aj-btn aj-btn-soft">
                <i class="bx bx-arrow-back"></i>
                <span>Retour à la liste</span>
            </a>
        </x-slot>
    </x-admin.page-header>

    <x-admin.flash-messages />

    <form action="{{ route('admin.economic-offers.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @include('admin.economic-offers._form', ['submitLabel' => 'Enregistrer l’offre'])
    </form>
@endsection

@push('scripts')
    <script src="{{ URL::asset('js/admin-economic-offer-form.js') }}"></script>
@endpush
