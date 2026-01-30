@extends('layouts.master-ajinsafro')
@section('title')
    Clients
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Clients'])
@endsection
@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
