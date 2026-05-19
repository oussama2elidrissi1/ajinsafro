@extends('layouts.admin-v6')
@section('title')
    AnnulÃ©es
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'AnnulÃ©es'])
@endsection
@push('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush

