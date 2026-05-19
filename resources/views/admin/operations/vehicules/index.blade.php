@extends('layouts.admin-v6')
@section('title')
    VÃ©hicules
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'VÃ©hicules'])
@endsection
@push('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush

