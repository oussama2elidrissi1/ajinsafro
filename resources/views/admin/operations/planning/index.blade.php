@extends('layouts.master-ajinsafro')
@section('title')
    Planning
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Planning'])
@endsection
@push('script')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
