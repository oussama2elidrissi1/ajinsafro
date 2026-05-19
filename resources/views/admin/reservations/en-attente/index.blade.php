@extends('layouts.admin-v6')
@section('title')
    En attente
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'En attente'])
@endsection
@push('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush

