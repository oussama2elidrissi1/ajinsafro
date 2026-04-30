@extends('layouts.master-ajinsafro')

@section('title', 'Modifier Group Deal')

@section('content')
<div class="container-fluid">
    <div class="page-title-box d-flex align-items-center justify-content-between">
        <div>
            <h4 class="mb-1">Modifier l'offre</h4>
            <p class="text-muted mb-0">{{ $groupDeal->title }}</p>
        </div>
        <a href="{{ route('admin.group-deals.show', $groupDeal) }}" class="btn btn-light">Retour à la fiche</a>
    </div>

    <form method="POST" action="{{ route('admin.group-deals.update', $groupDeal) }}" enctype="multipart/form-data">
        @include('admin.group-deals.offers._form', ['isEdit' => true])
    </form>
</div>
@endsection
