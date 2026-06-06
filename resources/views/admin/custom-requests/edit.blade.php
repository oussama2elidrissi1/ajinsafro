@extends('layouts.admin-v6')

@section('title', 'Modifier '.$customRequest->request_number)
@section('page_title', 'Modifier demande à la carte')

@section('content')
    @include('admin.custom-requests.partials.form', [
        'submitLabel' => 'Mettre à jour',
    ])
@endsection
