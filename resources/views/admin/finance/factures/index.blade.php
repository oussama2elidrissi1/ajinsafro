@extends('layouts.admin-v2')
@section('title')
    Factures
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Factures'])
@endsection
@push('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
