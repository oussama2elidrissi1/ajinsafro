@extends('layouts.master-ajinsafro')
@section('title')
    Options
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Options'])
@endsection
@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
