@extends('layouts.admin-v2')
@section('title')
    Circuits
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Circuits'])
@endsection
@push('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
