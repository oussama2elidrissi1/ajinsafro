@extends('layouts.master-ajinsafro')
@section('title')
    Circuits
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Circuits'])
@endsection
@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
