@extends('layouts.admin-v6')
@section('title')
    Créer un hébergement
@endsection
@section('content')
    <x-admin.page-header
        title="Créer un hébergement"
        subtitle="Remplissez la fiche pour créer un nouvel hébergement dans le catalogue WordPress."
        :breadcrumbs="[
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Catalogue Hébergements', 'url' => route('admin.wordpress.hotels.index')],
            ['label' => 'Créer'],
        ]"
    >
        <x-slot name="actions">
            <a href="{{ route('admin.wordpress.hotels.index') }}" class="aj-btn aj-btn-soft">
                <i class="bx bx-arrow-back"></i>
                <span>Retour à la liste</span>
            </a>
        </x-slot>
    </x-admin.page-header>

    <x-admin.flash-messages />

    <form action="{{ route('admin.wordpress.hotels.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Informations de l'hôtel</h4>
                        @include('admin.wordpress.hotels._form', ['hotel' => null, 'stHotel' => null, 'meta' => [], 'galleryUrls' => [], 'featuredUrl' => null])
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary waves-effect waves-light">Créer l'hôtel</button>
                        <a href="{{ route('admin.wordpress.hotels.index') }}" class="btn btn-secondary waves-effect">Annuler</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
@push('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush


