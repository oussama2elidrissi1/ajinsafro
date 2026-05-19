@extends('layouts.admin-v6')
@section('title')
    Nouvelle offre activitÃ©
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Nouvelle offre activitÃ©</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.activity-offers.index') }}">Offres activitÃ©s</a></li>
                        <li class="breadcrumb-item active">CrÃ©er</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.activity-offers.store') }}">
                        @csrf
                        @include('admin.activity-offers._form', ['offer' => $offer])
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">CrÃ©er</button>
                            <a href="{{ route('admin.activity-offers.index') }}" class="btn btn-secondary">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

