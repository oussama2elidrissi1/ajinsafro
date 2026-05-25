@extends('layouts.admin-v6')
@section('title')
    Options
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Options'])
@endsection
@push('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush

