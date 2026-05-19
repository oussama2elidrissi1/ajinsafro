@extends('layouts.admin-v6')
@section('title')
    Alertes
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Alertes'])
@endsection
@push('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush

