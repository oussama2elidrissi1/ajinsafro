@extends('layouts.admin-v6')
@section('title')
    Sécurité
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Sécurité'])
@endsection
@push('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush


