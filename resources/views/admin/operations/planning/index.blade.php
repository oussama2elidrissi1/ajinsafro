@extends('layouts.admin-v2')
@section('title')
    Planning
@endsection
@section('content')
    @include('admin._placeholder', ['title' => 'Planning'])
@endsection
@push('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
