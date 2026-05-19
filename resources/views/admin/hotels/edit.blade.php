@extends('layouts.admin-v6')
@section('title', 'Modifier l\'hÃ´tel')

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Modifier l'hÃ´tel â€“ {{ $hotel->name }}</h4>
                <a href="{{ route('admin.hotels.show', $hotel) }}" class="btn btn-outline-secondary btn-sm">Retour</a>
            </div>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form action="{{ route('admin.hotels.update', $hotel) }}" method="post" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('admin.hotels._form', ['hotel' => $hotel])
        <div class="mb-4">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="{{ route('admin.hotels.show', $hotel) }}" class="btn btn-secondary">Annuler</a>
        </div>
    </form>
@endsection


