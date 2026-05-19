@extends('layouts.admin-v6')
@section('title')
    DisponibilitÃ©s
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'DisponibilitÃ©s'])
@endsection
@push('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush

