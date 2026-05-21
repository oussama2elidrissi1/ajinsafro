@extends('layouts.admin-v6')

@section('title', 'Modifier demande a la carte')
@section('page_title', 'Modifier la demande')

@section('content')
    @include('admin.reservations.custom-requests.form', [
        'customRequest' => $customRequest,
        'formAction' => route('admin.reservations.custom-requests.update', $customRequest),
        'formMethod' => 'PUT',
        'submitLabel' => 'Enregistrer les modifications',
    ])
@endsection
