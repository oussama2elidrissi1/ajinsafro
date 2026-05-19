@extends('layouts.admin-v6')
@section('title')
    ConfirmÃ©es
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'ConfirmÃ©es'])
@endsection
@push('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush

