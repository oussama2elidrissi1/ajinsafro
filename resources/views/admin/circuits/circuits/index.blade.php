@extends('layouts.admin-v6')
@section('title')
    Circuits
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Circuits'])
@endsection
@push('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush

