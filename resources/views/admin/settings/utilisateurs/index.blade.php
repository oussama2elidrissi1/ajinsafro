@extends('layouts.master-ajinsafro')
@section('title')
    Utilisateurs
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Utilisateurs'])
@endsection
@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
