@extends('layouts.master-ajinsafro')
@section('title')
    Factures
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Factures'])
@endsection
@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
