@extends('layouts.admin-v6')
@section('title')
    Modifier la compagnie aérienne
@endsection
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Modifier : {{ $airline->name }}</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.circuits.index') }}">Circuits</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.circuits.airlines.index') }}">Compagnies aériennes</a></li>
                        <li class="breadcrumb-item active">Modifier</li>
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

    <form action="{{ route('admin.circuits.airlines.update', $airline) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Nom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $airline->name) }}" required>
                        </div>
                        <div class="mb-3">
                            <label for="iata_code" class="form-label">Code IATA</label>
                            <input type="text" class="form-control" id="iata_code" name="iata_code" value="{{ old('iata_code', $airline->code_iata) }}" maxlength="10">
                        </div>
                        <div class="mb-3">
                            <label for="logo_url" class="form-label">URL du logo</label>
                            <input type="text" class="form-control" id="logo_url" name="logo_url" value="{{ old('logo_url', $airline->logo_path) }}">
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $airline->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">Active</label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Enregistrer</button>
                        <a href="{{ route('admin.circuits.airlines.index') }}" class="btn btn-secondary w-100 mt-2">Annuler</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection


