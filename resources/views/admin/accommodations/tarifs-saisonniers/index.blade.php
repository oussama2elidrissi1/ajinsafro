@extends('layouts.admin-v6')
@section('title')
    Tarifs saisonniers
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Tarifs saisonniers'])
@endsection
@push('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush

