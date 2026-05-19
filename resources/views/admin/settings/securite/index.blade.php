@extends('layouts.admin-v6')
@section('title')
    SÃ©curitÃ©
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'SÃ©curitÃ©'])
@endsection
@push('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush

