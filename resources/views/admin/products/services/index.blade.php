@extends('layouts.master-ajinsafro')
@section('title')
    Services
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Services'])
@endsection
@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
