@extends('layouts.master-ajinsafro')
@section('title')
    Avis clients
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Avis clients'])
@endsection
@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
