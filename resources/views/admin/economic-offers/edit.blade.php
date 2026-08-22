@extends('layouts.admin-v6')

@section('title', 'Modifier offre économique')

@section('content')
    <x-admin.page-header
        :title="$offer->title"
        subtitle="Mettez à jour le contenu, les départs et les tarifs de cette offre."
        :breadcrumbs="[
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Formule économique', 'url' => route('admin.economic-offers.index')],
            ['label' => 'Modification'],
        ]"
    />

    <x-admin.flash-messages />

    <form action="{{ route('admin.economic-offers.update', $offer) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('admin.economic-offers._form')
        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="{{ route('admin.economic-offers.show', $offer) }}" class="aj-btn aj-btn-soft">Voir la fiche</a>
            <button type="submit" class="aj-btn aj-btn-primary">
                <i class="bx bx-save"></i>
                <span>Mettre à jour</span>
            </button>
        </div>
    </form>
@endsection


