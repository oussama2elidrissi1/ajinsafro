@extends('layouts.admin-v2')
@section('title')
    Nouveau client
@endsection
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Nouveau client</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.customers.index') }}">Clients</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.customers.clients.index') }}">Liste clients</a></li>
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

    <form action="{{ route('admin.customers.clients.store') }}" method="POST">
        @csrf
        @include('admin.customers.clients._form')
        <div class="mb-4">
            <button type="submit" class="btn btn-primary">Enregistrer le client</button>
            <a href="{{ route('admin.customers.clients.index') }}" class="btn btn-secondary">Annuler</a>
        </div>
    </form>
@endsection
