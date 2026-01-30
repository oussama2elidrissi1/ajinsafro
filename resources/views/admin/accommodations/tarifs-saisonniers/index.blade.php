@extends('layouts.master-ajinsafro')
@section('title')
    Tarifs saisonniers
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Tarifs saisonniers'])
@endsection
@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
