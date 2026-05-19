@extends('layouts.admin-v6')
@section('title', 'Modifier un transfert')
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Modifier un transfert</h4>
                <a href="{{ route('admin.wordpress.transfers.index') }}" class="btn btn-secondary">Retour</a>
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.wordpress.transfers.update', $transfer) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('admin.wordpress.transfers._form', [
            'transfer' => $transfer,
            'stCar' => $stCar,
            'meta' => $meta,
            'featuredUrl' => $featuredUrl,
        ])
        <div class="mt-3">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="{{ route('admin.wordpress.transfers.index') }}" class="btn btn-secondary">Annuler</a>
        </div>
    </form>
@endsection

