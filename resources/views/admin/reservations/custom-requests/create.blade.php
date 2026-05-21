@extends('layouts.admin-v6')

@section('title', 'Nouvelle demande a la carte')
@section('page_title', 'Nouvelle demande à la carte')

@section('content')
    @include('admin.reservations.custom-requests.form', [
        'customRequest' => $customRequest,
        'formAction' => route('admin.reservations.custom-requests.store'),
        'formMethod' => 'POST',
        'submitLabel' => 'Créer la demande',
    ])
@endsection
