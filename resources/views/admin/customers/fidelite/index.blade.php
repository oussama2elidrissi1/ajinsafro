@extends('layouts.admin-v6')
@section('title')
    FidÃ©litÃ©
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'FidÃ©litÃ©'])
@endsection
@push('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush

