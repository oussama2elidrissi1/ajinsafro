@extends('layouts.admin-v6')
@section('title')
    Annulées
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Annulées'])
@endsection
@push('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush


