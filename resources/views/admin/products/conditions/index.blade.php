@extends('layouts.admin-v6')
@section('title')
    Conditions
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Conditions'])
@endsection
@push('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush

