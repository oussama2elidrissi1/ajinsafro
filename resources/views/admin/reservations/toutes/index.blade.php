@extends('layouts.admin-v2')
@section('title')
    Toutes les réservations
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Toutes les réservations'])
@endsection
@push('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
