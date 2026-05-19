@extends('layouts.admin-v6')

@section('title', 'Nouvelle offre Ã©conomique')

@section('content')
    <x-admin.page-header
        title="Nouvelle offre Ã©conomique"
        subtitle="Ajoutez une offre voyage, omra, hÃ©bergement ou activitÃ© Ã  petit budget."
        :breadcrumbs="[
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Formule Ã‰conomique', 'url' => route('admin.economic-offers.index')],
            ['label' => 'CrÃ©ation'],
        ]"
    />

    <x-admin.flash-messages />

    <form action="{{ route('admin.economic-offers.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @include('admin.economic-offers._form')
        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="{{ route('admin.economic-offers.index') }}" class="aj-btn aj-btn-soft">Annuler</a>
            <button type="submit" class="aj-btn aj-btn-primary">
                <i class="bx bx-save"></i>
                <span>Enregistrer</span>
            </button>
        </div>
    </form>
@endsection

