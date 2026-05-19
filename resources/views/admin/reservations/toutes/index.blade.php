@extends('layouts.admin-v6')
@section('title')
    Toutes les rÃ©servations
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Toutes les rÃ©servations'])
@endsection
@push('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush

