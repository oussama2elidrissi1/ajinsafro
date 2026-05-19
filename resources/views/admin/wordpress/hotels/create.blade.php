@extends('layouts.admin-v6')
@section('title')
    CrÃ©er un hÃ©bergement
@endsection
@section('content')
    <x-admin.page-header
        title="CrÃ©er un hÃ©bergement"
        subtitle="Remplissez la fiche pour crÃ©er un nouvel hÃ©bergement dans le catalogue WordPress."
        :breadcrumbs="[
            ['label' => 'Admin', 'url' => route('admin.dashboard')],
            ['label' => 'Catalogue HÃ©bergements', 'url' => route('admin.wordpress.hotels.index')],
            ['label' => 'CrÃ©er'],
        ]"
    >
        <x-slot name="actions">
            <a href="{{ route('admin.wordpress.hotels.index') }}" class="aj-btn aj-btn-soft">
                <i class="bx bx-arrow-back"></i>
                <span>Retour Ã  la liste</span>
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
                        <h4 class="card-title mb-4">Informations de l'hÃ´tel</h4>
                        @include('admin.wordpress.hotels._form', ['hotel' => null, 'stHotel' => null, 'meta' => [], 'galleryUrls' => [], 'featuredUrl' => null])
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary waves-effect waves-light">CrÃ©er l'hÃ´tel</button>
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

