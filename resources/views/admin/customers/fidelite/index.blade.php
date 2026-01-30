@extends('layouts.master-ajinsafro')
@section('title')
    Fidélité
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Fidélité'])
@endsection
@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
