@extends('layouts.admin-v6')

@section('title', 'Nouvelle demande à la carte')
@section('page_title', 'Nouvelle demande à la carte')

@section('content')
    @include('admin.custom-requests.partials.form', [
        'submitLabel' => 'Créer la demande',
    ])
@endsection
