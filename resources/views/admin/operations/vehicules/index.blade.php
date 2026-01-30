@extends('layouts.master-ajinsafro')
@section('title')
    Véhicules
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Véhicules'])
@endsection
@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
