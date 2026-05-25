@extends('layouts.admin-v6')
@section('title')
    Politiques & Conditions
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Politiques & Conditions'])
@endsection
@push('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush

