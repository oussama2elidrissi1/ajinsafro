@extends('layouts.master-ajinsafro')
@section('title')
    Toutes les réservations
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Toutes les réservations'])
@endsection
@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
