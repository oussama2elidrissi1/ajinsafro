@extends('layouts.admin-v6')
@section('title')
    Véhicules
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Véhicules'])
@endsection
@push('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush


