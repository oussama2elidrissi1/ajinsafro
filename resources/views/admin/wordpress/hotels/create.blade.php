@extends('layouts.master-ajinsafro')
@section('title')
    Créer un hôtel (WordPress)
@endsection
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Créer un hôtel (WordPress)</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.wordpress.hotels.index') }}">WordPress – Hotels</a></li>
                        <li class="breadcrumb-item active">Créer</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

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
@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
