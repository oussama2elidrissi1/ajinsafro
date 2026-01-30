@extends('layouts.master-ajinsafro')
@section('title')
    Sécurité
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Sécurité'])
@endsection
@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
