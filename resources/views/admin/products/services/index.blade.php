@extends('layouts.admin-v2')
@section('title')
    Services
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Services'])
@endsection
@push('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
