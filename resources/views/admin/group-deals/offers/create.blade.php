@extends('layouts.admin-v6')

@section('title', 'CrÃ©er une offre Group Deal')

@section('content')
<div class="container-fluid">
    <div class="page-title-box d-flex align-items-center justify-content-between">
        <div>
            <h4 class="mb-1">Nouvelle offre Group Deal</h4>
            <p class="text-muted mb-0">DÃ©finissez librement les conditions de garantie et les paliers de prix.</p>
        </div>
        <a href="{{ route('admin.group-deals.index') }}" class="btn btn-light">Retour</a>
    </div>

    <form method="POST" action="{{ route('admin.group-deals.store') }}" enctype="multipart/form-data">
        @include('admin.group-deals.offers._form', ['isEdit' => false])
    </form>
</div>
@endsection

