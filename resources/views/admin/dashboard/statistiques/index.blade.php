@extends('layouts.admin-v2')
@section('title')
    Statistiques
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Statistiques'])
@endsection
@push('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
