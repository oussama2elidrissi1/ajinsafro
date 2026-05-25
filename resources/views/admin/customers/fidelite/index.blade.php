@extends('layouts.admin-v6')
@section('title')
    Fidélité
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Fidélité'])
@endsection
@push('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush


