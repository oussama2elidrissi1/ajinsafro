@extends('layouts.master-ajinsafro')
@section('title')
    En attente
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'En attente'])
@endsection
@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
