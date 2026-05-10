@extends('layouts.admin-v2')
@section('title')
    Guides & Chauffeurs
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Guides & Chauffeurs'])
@endsection
@push('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
