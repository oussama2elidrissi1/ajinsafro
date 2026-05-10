@extends('layouts.admin-v2')
@section('title')
    Avis clients
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Avis clients'])
@endsection
@push('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
